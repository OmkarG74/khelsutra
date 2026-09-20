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
}
