<?php

namespace App\Http\Controllers\Api\V1\Achievements;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use App\Helpers\ApiResponse;
use PDO;

class AchievementController extends Controller
{
    protected ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? BaseService::getDatabaseConnection();
    }

    public function index(int $organizationId, array $requestData): array
    {
        if (!$this->pdo) {
            return ApiResponse::error('Database unavailable', null, 500);
        }

        $athleteId = !empty($requestData['athlete_id']) ? (int)$requestData['athlete_id'] : null;
        $limit = (int)($requestData['limit'] ?? 20);

        $sql = "
            SELECT ach.*, 
                   CONCAT(a.first_name, ' ', a.last_name) as athlete_name,
                   a.athlete_code,
                   t.name as tournament_name
            FROM achievements ach
            JOIN athletes a ON ach.athlete_id = a.id AND a.deleted_at IS NULL
            LEFT JOIN tournaments t ON ach.tournament_id = t.id
            WHERE ach.organization_id = :oid AND ach.deleted_at IS NULL
        ";
        $params = [':oid' => $organizationId];

        if ($athleteId) {
            $sql .= " AND ach.athlete_id = :aid";
            $params[':aid'] = $athleteId;
        }

        $sql .= " ORDER BY ach.achievement_date DESC, ach.id DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return ApiResponse::success($achievements, 'Achievements retrieved successfully', 200);
    }

    public function store(int $organizationId, array $requestData, ?int $performedBy = null): array
    {
        if (!$this->pdo) {
            return ApiResponse::error('Database unavailable', null, 500);
        }

        if (empty($requestData['title'])) {
            return ApiResponse::error('Title is required', ['title' => ['Title is required']], 422);
        }
        if (empty($requestData['athlete_id'])) {
            return ApiResponse::error('Athlete is required', ['athlete_id' => ['Athlete ID is required']], 422);
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO achievements (
                organization_id, athlete_id, tournament_id, title, achievement_type,
                position_or_medal, achievement_date, description, certificate_path, created_at, updated_at
            ) VALUES (
                :oid, :aid, :tid, :title, :atype,
                :pos, :adate, :desc, :cert, NOW(), NOW()
            )
        ");
        $stmt->execute([
            ':oid' => $organizationId,
            ':aid' => (int)$requestData['athlete_id'],
            ':tid' => !empty($requestData['tournament_id']) ? (int)$requestData['tournament_id'] : null,
            ':title' => trim($requestData['title']),
            ':atype' => $requestData['achievement_type'] ?? 'Tournament',
            ':pos' => $requestData['position_or_medal'] ?? null,
            ':adate' => $requestData['achievement_date'] ?? date('Y-m-d'),
            ':desc' => $requestData['description'] ?? null,
            ':cert' => $requestData['certificate_path'] ?? null,
        ]);

        return ApiResponse::success([
            'id' => (int)$this->pdo->lastInsertId(),
            'title' => $requestData['title'],
        ], 'Achievement created successfully', 201);
    }
}
