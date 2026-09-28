<?php

namespace App\Http\Controllers\Api\V1\Performance;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use App\Helpers\ApiResponse;
use PDO;

class PerformanceController extends Controller
{
    protected ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? BaseService::getDatabaseConnection();
    }

    /**
     * List configurable performance metrics
     */
    public function metrics(int $organizationId, array $requestData): array
    {
        if (!$this->pdo) {
            return ApiResponse::error('Database unavailable', null, 500);
        }

        $sportId = !empty($requestData['sport_id']) ? (int)$requestData['sport_id'] : null;
        $sql = "SELECT * FROM performance_metrics WHERE status = 'active'";
        $params = [];

        if ($sportId) {
            $sql .= " AND sport_id = :sid";
            $params[':sid'] = $sportId;
        }

        $sql .= " ORDER BY name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $metrics = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return ApiResponse::success($metrics, 'Performance metrics retrieved', 200);
    }

    /**
     * List performance evaluations with dynamic metric values
     */
    public function index(int $organizationId, array $requestData): array
    {
        if (!$this->pdo) {
            return ApiResponse::error('Database unavailable', null, 500);
        }

        $athleteId = !empty($requestData['athlete_id']) ? (int)$requestData['athlete_id'] : null;
        $sportId = !empty($requestData['sport_id']) ? (int)$requestData['sport_id'] : null;
        $limit = (int)($requestData['limit'] ?? 20);

        $sql = "
            SELECT 
                ap.*,
                CONCAT(a.first_name, ' ', a.last_name) as athlete_name,
                a.athlete_code,
                s.name as sport_name,
                t.name as team_name,
                ts.title as training_session_title
            FROM athlete_performance ap
            JOIN athletes a ON ap.athlete_id = a.id AND a.deleted_at IS NULL
            LEFT JOIN sports s ON ap.sport_id = s.id
            LEFT JOIN teams t ON ap.team_id = t.id
            LEFT JOIN training_sessions ts ON ap.training_session_id = ts.id
            WHERE ap.organization_id = :oid AND ap.deleted_at IS NULL
        ";
        $params = [':oid' => $organizationId];

        if ($athleteId) {
            $sql .= " AND ap.athlete_id = :aid";
            $params[':aid'] = $athleteId;
        }
        if ($sportId) {
            $sql .= " AND ap.sport_id = :sid";
            $params[':sid'] = $sportId;
        }

        $sql .= " ORDER BY ap.evaluation_date DESC, ap.id DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Attach metric values to each performance evaluation
        if (!empty($records)) {
            $perfIds = array_column($records, 'id');
            $placeholders = implode(',', array_fill(0, count($perfIds), '?'));
            $valStmt = $this->pdo->prepare("
                SELECT apv.*, pm.name as metric_name, pm.code as metric_code, pm.unit, pm.metric_type
                FROM athlete_performance_values apv
                JOIN performance_metrics pm ON apv.metric_id = pm.id
                WHERE apv.performance_id IN ({$placeholders})
                ORDER BY pm.name ASC
            ");
            $valStmt->execute($perfIds);
            $values = $valStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $groupedValues = [];
            foreach ($values as $v) {
                $groupedValues[$v['performance_id']][] = $v;
            }

            foreach ($records as &$rec) {
                $rec['values'] = $groupedValues[$rec['id']] ?? [];
            }
        }

        return ApiResponse::success($records, 'Performance records retrieved', 200);
    }

    /**
     * Store new performance evaluation
     */
    public function store(int $organizationId, array $requestData, ?int $performedBy = null): array
    {
        if (!$this->pdo) {
            return ApiResponse::error('Database unavailable', null, 500);
        }

        $athleteId = !empty($requestData['athlete_id']) ? (int)$requestData['athlete_id'] : null;
        if (!$athleteId) {
            return ApiResponse::error('Athlete is required', ['athlete_id' => ['Athlete ID is required']], 422);
        }

        // Resolve athlete sport if not passed
        $sportId = !empty($requestData['sport_id']) ? (int)$requestData['sport_id'] : null;
        if (!$sportId) {
            $athStmt = $this->pdo->prepare("SELECT current_sport_id FROM athletes WHERE id = :id AND organization_id = :oid");
            $athStmt->execute([':id' => $athleteId, ':oid' => $organizationId]);
            $sportId = (int)($athStmt->fetchColumn() ?: 1);
        }

        $evalDate = $requestData['evaluation_date'] ?? date('Y-m-d');
        $overallRating = isset($requestData['overall_rating']) ? (float)$requestData['overall_rating'] : null;
        $remarks = $requestData['coach_remarks'] ?? ($requestData['remarks'] ?? null);
        $teamId = !empty($requestData['team_id']) ? (int)$requestData['team_id'] : null;
        $sessionId = !empty($requestData['training_session_id']) ? (int)$requestData['training_session_id'] : null;

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO athlete_performance (
                    organization_id, athlete_id, sport_id, team_id, training_session_id,
                    evaluation_date, overall_rating, coach_remarks, created_by, created_at, updated_at
                ) VALUES (
                    :oid, :aid, :sid, :tid, :tsid,
                    :edate, :rating, :remarks, :by, NOW(), NOW()
                )
            ");
            $stmt->execute([
                ':oid' => $organizationId,
                ':aid' => $athleteId,
                ':sid' => $sportId,
                ':tid' => $teamId,
                ':tsid' => $sessionId,
                ':edate' => $evalDate,
                ':rating' => $overallRating,
                ':remarks' => $remarks,
                ':by' => $performedBy,
            ]);
            $performanceId = (int)$this->pdo->lastInsertId();

            // Insert metric values if provided
            $metricsList = $requestData['metrics'] ?? [];
            if (!empty($metricsList) && is_array($metricsList)) {
                $valInsert = $this->pdo->prepare("
                    INSERT INTO athlete_performance_values (performance_id, metric_id, numeric_value, text_value, created_at)
                    VALUES (:pid, :mid, :nval, :tval, NOW())
                ");
                foreach ($metricsList as $m) {
                    $metricId = !empty($m['metric_id']) ? (int)$m['metric_id'] : null;
                    if ($metricId) {
                        $nVal = isset($m['numeric_value']) && $m['numeric_value'] !== '' ? (float)$m['numeric_value'] : (isset($m['value']) && is_numeric($m['value']) ? (float)$m['value'] : null);
                        $tVal = isset($m['text_value']) ? $m['text_value'] : (isset($m['value']) && !is_numeric($m['value']) ? (string)$m['value'] : null);
                        $valInsert->execute([
                            ':pid' => $performanceId,
                            ':mid' => $metricId,
                            ':nval' => $nVal,
                            ':tval' => $tVal,
                        ]);
                    }
                }
            }

            $this->pdo->commit();

            return ApiResponse::success([
                'id' => $performanceId,
                'athlete_id' => $athleteId,
                'evaluation_date' => $evalDate,
                'overall_rating' => $overallRating,
            ], 'Performance evaluation recorded successfully', 201);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return ApiResponse::error('Failed to record performance: ' . $e->getMessage(), null, 500);
        }
    }
}
