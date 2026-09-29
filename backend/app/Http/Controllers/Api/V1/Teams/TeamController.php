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

    public function index(int $organizationId, int $page = 1, int $limit = 15): array
    {
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
