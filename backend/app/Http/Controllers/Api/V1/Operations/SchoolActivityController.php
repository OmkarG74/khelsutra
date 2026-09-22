<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Helpers\ApiResponse;
use App\Services\Operations\EventService;
use App\Models\SchoolActivity;
use Exception;

class SchoolActivityController
{
    protected EventService $service;

    public function __construct(EventService $service)
    {
        $this->service = $service;
    }

    public function index(int $orgId, array $requestData): array
    {
        $activities = SchoolActivity::where('organization_id', $orgId)->get();
        return ApiResponse::success(['data' => $activities->toArray()]);
    }

    public function store(int $orgId, array $requestData): array
    {
        try {
            $activity = $this->service->createSchoolActivity($orgId, $requestData);
            return ApiResponse::success($activity->toArray(), 'School activity created', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }
}
