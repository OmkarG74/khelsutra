<?php

namespace App\Http\Controllers\Api\V1\Athletes;

use App\Http\Controllers\Controller;
use App\Services\Athlete\AthleteService;
use App\Http\Requests\Athletes\StoreAthleteRequest;
use App\Helpers\ApiResponse;

class AthleteController extends Controller
{
    protected AthleteService $athleteService;

    public function __construct(?AthleteService $athleteService = null)
    {
        $this->athleteService = $athleteService ?? new AthleteService();
    }

    public function index(int $organizationId, int $page = 1, int $limit = 15): array
    {
        $athletes = $this->athleteService->listAthletes($organizationId, $page, $limit);
        return ApiResponse::success($athletes, 'Athletes retrieved successfully', 200);
    }

    public function show(int $organizationId, int $id): array
    {
        $athlete = $this->athleteService->getAthlete($organizationId, $id);
        if (!$athlete) {
            return ApiResponse::error('Athlete not found', null, 404);
        }
        return ApiResponse::success($athlete, 'Athlete retrieved successfully', 200);
    }

    public function store(int $organizationId, array $requestData): array
    {
        $request = new StoreAthleteRequest($requestData);
        $errors = $request->validate();
        if (!empty($errors)) {
            return ApiResponse::error('Validation failed', $errors, 422);
        }

        $created = $this->athleteService->registerAthlete($organizationId, $requestData);
        return ApiResponse::success($created, 'Athlete registered successfully', 201);
    }

    public function update(int $organizationId, int $id, array $requestData): array
    {
        $updated = $this->athleteService->updateAthlete($organizationId, $id, $requestData);
        if (!$updated) {
            return ApiResponse::error('Failed to update athlete or record not found', null, 400);
        }
        return ApiResponse::success(null, 'Athlete updated successfully', 200);
    }

    public function destroy(int $organizationId, int $id): array
    {
        $deleted = $this->athleteService->deleteAthlete($organizationId, $id);
        if (!$deleted) {
            return ApiResponse::error('Failed to delete athlete or record not found', null, 400);
        }
        return ApiResponse::success(null, 'Athlete deleted successfully', 200);
    }
}
