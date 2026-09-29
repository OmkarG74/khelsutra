<?php

namespace App\Http\Controllers\Api\V1\Matches;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use App\Helpers\ApiResponse;
use PDO;

class MatchController extends Controller
{
    protected ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? BaseService::getDatabaseConnection();
    }

    /**
     * List matches and fixtures filtered by tenant and role context
     */
    public function index(int $organizationId, array $requestData): array
    {
        if (!$this->pdo) {
            return ApiResponse::error('Database unavailable', null, 500);
        }

        $currentUser = $requestData['user'] ?? null;
        $roleSlug = $currentUser['role']['slug'] ?? '';
        $coachId = !empty($requestData['coach_id']) ? (int)$requestData['coach_id'] : (int)($currentUser['coach_id'] ?? 0);
        $athleteId = !empty($requestData['athlete_id']) ? (int)$requestData['athlete_id'] : (int)($currentUser['athlete_id'] ?? 0);
        $status = $requestData['status'] ?? null;
        $limit = (int)($requestData['limit'] ?? 50);

        $sql = "
            SELECT 
                m.id as match_id,
                m.id,
                m.match_reference,
                m.status as match_status,
                m.home_score,
                m.away_score,
                m.winner_team_id,
                m.result_type,
                m.referee_name,
                m.match_notes,
                m.actual_start_time,
                m.actual_end_time,
                f.id as fixture_id,
                f.fixture_reference,
                f.scheduled_date,
                f.scheduled_start_time,
                f.scheduled_end_time,
                f.status as fixture_status,
                f.round_name,
                f.notes as fixture_notes,
                f.home_team_id,
                f.away_team_id,
                ht.name as home_team_name,
                at.name as away_team_name,
                tr.id as tournament_id,
                tr.name as tournament_name,
                s.name as sport_name,
                v.name as venue_name
            FROM matches m
            JOIN fixtures f ON m.fixture_id = f.id AND f.deleted_at IS NULL
            LEFT JOIN teams ht ON f.home_team_id = ht.id
            LEFT JOIN teams at ON f.away_team_id = at.id
            LEFT JOIN tournaments tr ON f.tournament_id = tr.id
            LEFT JOIN sports s ON f.sport_id = s.id
            LEFT JOIN venues v ON f.venue_id = v.id
            WHERE m.organization_id = :oid AND m.deleted_at IS NULL
        ";
        $params = [':oid' => $organizationId];

        if ($status) {
            $sql .= " AND m.status = :status";
            $params[':status'] = $status;
        }

        // Coach filtering: coach's teams
        if ($coachId && ($roleSlug === 'coach' || !empty($currentUser['coach_id']))) {
            $sql .= " AND (
                f.home_team_id IN (SELECT team_id FROM team_coaches WHERE coach_id = :cid AND organization_id = :cid_oid)
                OR f.away_team_id IN (SELECT team_id FROM team_coaches WHERE coach_id = :cid2 AND organization_id = :cid_oid2)
            )";
            $params[':cid'] = $coachId;
            $params[':cid_oid'] = $organizationId;
            $params[':cid2'] = $coachId;
            $params[':cid_oid2'] = $organizationId;
        }

        // Athlete filtering: athlete's teams
        if ($athleteId && ($roleSlug === 'athlete' || !empty($currentUser['athlete_id']))) {
            $sql .= " AND (
                f.home_team_id IN (SELECT team_id FROM team_members WHERE athlete_id = :aid AND is_current = 1)
                OR f.away_team_id IN (SELECT team_id FROM team_members WHERE athlete_id = :aid2 AND is_current = 1)
            )";
            $params[':aid'] = $athleteId;
            $params[':aid2'] = $athleteId;
        }

        $sql .= " ORDER BY f.scheduled_date DESC, f.scheduled_start_time DESC LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return ApiResponse::success($matches, 'Matches retrieved successfully', 200);
    }

    /**
     * Show match details including roster attendance
     */
    public function show(int $organizationId, int $id): array
    {
        if (!$this->pdo) {
            return ApiResponse::error('Database unavailable', null, 500);
        }

        $stmt = $this->pdo->prepare("
            SELECT 
                m.id as match_id,
                m.id,
                m.match_reference,
                m.status as match_status,
                m.home_score,
                m.away_score,
                m.winner_team_id,
                m.result_type,
                m.referee_name,
                m.match_notes,
                m.actual_start_time,
                m.actual_end_time,
                f.id as fixture_id,
                f.fixture_reference,
                f.scheduled_date,
                f.scheduled_start_time,
                f.scheduled_end_time,
                f.status as fixture_status,
                f.round_name,
                f.notes as fixture_notes,
                f.home_team_id,
                f.away_team_id,
                ht.name as home_team_name,
                at.name as away_team_name,
                tr.name as tournament_name,
                s.name as sport_name,
                v.name as venue_name
            FROM matches m
            JOIN fixtures f ON m.fixture_id = f.id AND f.deleted_at IS NULL
            LEFT JOIN teams ht ON f.home_team_id = ht.id
            LEFT JOIN teams at ON f.away_team_id = at.id
            LEFT JOIN tournaments tr ON f.tournament_id = tr.id
            LEFT JOIN sports s ON f.sport_id = s.id
            LEFT JOIN venues v ON f.venue_id = v.id
            WHERE m.id = :id AND m.organization_id = :oid AND m.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':oid' => $organizationId]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            return ApiResponse::error('Match not found', null, 404);
        }

        // Fetch roster athletes for both home and away teams
        $teamIds = array_filter([(int)($match['home_team_id'] ?? 0), (int)($match['away_team_id'] ?? 0)]);
        $roster = [];
        if (!empty($teamIds)) {
            $inClause = implode(',', $teamIds);
            $rStmt = $this->pdo->prepare("
                SELECT DISTINCT 
                    a.id as athlete_id,
                    CONCAT(a.first_name, ' ', a.last_name) as athlete_name,
                    a.athlete_code,
                    a.photo_path,
                    tm.team_id,
                    tm.jersey_number,
                    t.name as team_name,
                    ma.attendance_status,
                    ma.remarks as attendance_remarks
                FROM team_members tm
                JOIN athletes a ON tm.athlete_id = a.id AND a.deleted_at IS NULL
                JOIN teams t ON tm.team_id = t.id
                LEFT JOIN match_attendance ma ON ma.match_id = :mid AND ma.athlete_id = a.id
                WHERE tm.team_id IN ({$inClause}) AND tm.is_current = 1 AND a.organization_id = :oid
                ORDER BY tm.team_id, a.first_name ASC
            ");
            $rStmt->execute([':mid' => $id, ':oid' => $organizationId]);
            $roster = $rStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $match['roster_attendance'] = $roster;

        return ApiResponse::success($match, 'Match details retrieved successfully', 200);
    }
}
