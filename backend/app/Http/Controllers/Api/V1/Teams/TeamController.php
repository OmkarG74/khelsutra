<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Http\Controllers\Controller;
use App\Services\Team\TeamService;
use App\Http\Requests\Teams\StoreTeamRequest;
use App\Helpers\ApiResponse;

class TeamController extends Controller
{
    protected TeamService $teamService;

    public function __construct(?TeamService $teamService = null)
    {
        $this->teamService = $teamService ?? new TeamService();
    }

    public function index(int $organizationId, int $page = 1, int $limit = 15, array $requestData = []): array
    {
        $currentUser = $requestData['user'] ?? null;
        $roleSlug = $currentUser['role']['slug'] ?? '';
        $isAthlete = ($roleSlug === 'athlete') || !empty($currentUser['athlete_id']);
        $isCoach = ($roleSlug === 'coach') || !empty($currentUser['coach_id']);
        $pdo = $this->teamService->getPdo();

        if ($isAthlete && $pdo) {
            $athleteId = (int)($currentUser['athlete_id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT t.*, s.name as sport_name 
                FROM teams t 
                JOIN team_members tm ON t.id = tm.team_id AND tm.is_current = 1
                LEFT JOIN sports s ON t.sport_id = s.id
                WHERE tm.athlete_id = :aid AND t.organization_id = :oid AND t.deleted_at IS NULL
                ORDER BY t.name ASC
            ");
            $stmt->execute([':aid' => $athleteId, ':oid' => $organizationId]);
            $teams = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            return ApiResponse::success(['data' => $teams, 'total' => count($teams)], 'Teams retrieved successfully', 200);
        }

        if ($isCoach && $pdo) {
            $coachId = (int)($currentUser['coach_id'] ?? 0);
            if (!$coachId && !empty($currentUser['id'])) {
                $cStmt = $pdo->prepare("
                    SELECT cp.id 
                    FROM coach_profiles cp 
                    JOIN employees e ON cp.employee_id = e.id 
                    WHERE e.user_id = :uid AND cp.organization_id = :oid AND cp.deleted_at IS NULL 
                    LIMIT 1
                ");
                $cStmt->execute([':uid' => (int)$currentUser['id'], ':oid' => $organizationId]);
                $coachId = (int)($cStmt->fetchColumn() ?: 0);
            }

            if ($coachId) {
                $stmt = $pdo->prepare("
                    SELECT t.*, s.name as sport_name 
                    FROM teams t 
                    JOIN team_coaches tc ON t.id = tc.team_id 
                    LEFT JOIN sports s ON t.sport_id = s.id
                    WHERE tc.coach_id = :cid AND t.organization_id = :oid AND t.deleted_at IS NULL
                    ORDER BY t.name ASC
                ");
                $stmt->execute([':cid' => $coachId, ':oid' => $organizationId]);
                $teams = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                return ApiResponse::success(['data' => $teams, 'total' => count($teams)], 'Teams retrieved successfully', 200);
            }
        }

        $teams = $this->teamService->listTeams($organizationId, $page, $limit);
        return ApiResponse::success($teams, 'Teams retrieved successfully', 200);
    }

    public function show(int $organizationId, int $id): array
    {
        $team = $this->teamService->getTeam($organizationId, $id);
        if (!$team) {
            return ApiResponse::error('Team not found', null, 404);
        }
        return ApiResponse::success($team, 'Team retrieved successfully', 200);
    }

    public function store(int $organizationId, array $requestData): array
    {
        $request = new StoreTeamRequest($requestData);
        $errors = $request->validate();
        if (!empty($errors)) {
            return ApiResponse::error('Validation failed', $errors, 422);
        }

        $created = $this->teamService->createTeam($organizationId, $requestData);
        return ApiResponse::success($created, 'Team created successfully', 201);
    }

    public function athletes(int $organizationId, int $teamId, array $requestData = []): array
    {
        $pdo = $this->teamService->getPdo();
        if (!$pdo) {
            return ApiResponse::success(['data' => [], 'total' => 0], 'No athletes found', 200);
        }

        $stmt = $pdo->prepare("
            SELECT DISTINCT 
                a.id, a.athlete_code, a.first_name, a.middle_name, a.last_name,
                a.date_of_birth, a.gender, a.phone, a.email, a.photo_path, a.status,
                s.name as sport_name,
                t.id as team_id, t.name as team_name,
                tm.jersey_number, tm.member_role
            FROM teams t
            JOIN team_members tm ON t.id = tm.team_id AND tm.is_current = 1
            JOIN athletes a ON tm.athlete_id = a.id AND a.deleted_at IS NULL
            LEFT JOIN sports s ON a.current_sport_id = s.id
            WHERE t.id = :tid AND t.organization_id = :oid AND t.deleted_at IS NULL
            ORDER BY a.first_name ASC
        ");
        $stmt->execute([':tid' => $teamId, ':oid' => $organizationId]);
        $athletes = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return ApiResponse::success([
            'data' => $athletes,
            'total' => count($athletes),
        ], 'Team athletes retrieved successfully', 200);
    }
}
