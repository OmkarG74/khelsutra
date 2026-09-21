<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Helpers\ApiResponse;
use App\Services\Operations\RoomAllocationService;
use App\Models\AccommodationAllocation;
use Exception;

class RoomAllocationController
{
    protected RoomAllocationService $service;

    public function __construct(RoomAllocationService $service)
    {
        $this->service = $service;
    }

    public function index(int $orgId, array $requestData): array
    {
        $alloc = AccommodationAllocation::where('organization_id', $orgId)->get();
        return ApiResponse::success(['data' => $alloc->toArray()]);
    }

    public function store(int $orgId, array $requestData): array
    {
        try {
            $alloc = $this->service->allocateRoom($orgId, $requestData);
            return ApiResponse::success($alloc->toArray(), 'Room allocated', 201);
        } catch (Exception $e) {
            $status = $e->getCode() == 409 ? 409 : 400;
            return ApiResponse::error($e->getMessage(), null, $status);
        }
    }
}
