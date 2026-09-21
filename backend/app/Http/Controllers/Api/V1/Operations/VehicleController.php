<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Helpers\ApiResponse;
use App\Services\Operations\VehicleService;
use App\Models\Vehicle;
use Exception;

class VehicleController
{
    protected VehicleService $service;

    public function __construct(VehicleService $service)
    {
        $this->service = $service;
    }

    public function index(int $orgId, array $requestData): array
    {
        $vehicles = Vehicle::where('organization_id', $orgId)->get();
        return ApiResponse::success(['data' => $vehicles->toArray()]);
    }

    public function store(int $orgId, array $requestData): array
    {
        try {
            $vehicle = $this->service->createVehicle($orgId, $requestData);
            return ApiResponse::success($vehicle->toArray(), 'Vehicle created', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }
}
