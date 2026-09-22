<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Helpers\ApiResponse;
use App\Services\Operations\VehicleTrackingService;
use Exception;

class VehicleTrackingController
{
    protected VehicleTrackingService $service;

    public function __construct(VehicleTrackingService $service)
    {
        $this->service = $service;
    }

    public function storePosition(int $orgId, array $requestData): array
    {
        try {
            $position = $this->service->recordPosition($orgId, $requestData);
            
            // Check for geofences on position update
            $breached = $this->service->checkGeofenceBreach($orgId, $position->vehicle_id);

            return ApiResponse::success([
                'position' => $position->toArray(),
                'geofences_inside' => $breached
            ], 'Position recorded', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }
}
