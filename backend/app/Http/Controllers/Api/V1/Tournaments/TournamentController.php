<?php

namespace App\Http\Controllers\Api\V1\Tournaments;

use App\Http\Controllers\Controller;
use App\Services\Tournament\TournamentService;
use App\Http\Requests\Tournaments\StoreTournamentRequest;
use App\Helpers\ApiResponse;

class TournamentController extends Controller
{
    protected TournamentService $tournamentService;

    public function __construct(?TournamentService $tournamentService = null)
    {
        $this->tournamentService = $tournamentService ?? new TournamentService();
    }

    public function index(int $organizationId, int $page = 1, int $limit = 15): array
    {
        $tournaments = $this->tournamentService->listTournaments($organizationId, $page, $limit);
        return ApiResponse::success($tournaments, 'Tournaments retrieved successfully', 200);
    }

    public function show(int $organizationId, int $id): array
    {
        $tournament = $this->tournamentService->getTournament($organizationId, $id);
        if (!$tournament) {
            return ApiResponse::error('Tournament not found', null, 404);
        }
        return ApiResponse::success($tournament, 'Tournament retrieved successfully', 200);
    }

    public function store(int $organizationId, array $requestData): array
    {
        $request = new StoreTournamentRequest($requestData);
        $errors = $request->validate();
        if (!empty($errors)) {
            return ApiResponse::error('Validation failed', $errors, 422);
        }

        $created = $this->tournamentService->createTournament($organizationId, $requestData);
        return ApiResponse::success($created, 'Tournament created successfully', 201);
    }
}
