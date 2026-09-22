<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Helpers\ApiResponse;
use App\Services\Operations\HousekeepingService;
use App\Models\HousekeepingTask;

class HousekeepingController
{
    protected HousekeepingService $service;

    public function __construct()
    {
        $this->service = new HousekeepingService();
    }

    public function index(int $orgId, array $requestData): array
    {
        $query = HousekeepingTask::where('organization_id', $orgId);
        $tasks = $query->orderBy('created_at', 'desc')->get();
        return ApiResponse::success(['data' => $tasks->toArray()]);
    }

    public function store(int $orgId, array $requestData): array
    {
        try {
            $task = $this->service->createTask($orgId, $requestData);
            return ApiResponse::success($task->toArray(), 'Housekeeping task created', 201);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }

    public function update(int $orgId, int $id, array $requestData): array
    {
        try {
            $task = $this->service->updateTask($orgId, $id, $requestData);
            return ApiResponse::success($task->toArray(), 'Housekeeping task updated');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }
}
