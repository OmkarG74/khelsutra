<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Helpers\ApiResponse;
use App\Services\Operations\TransportTripService;
use App\Models\TransportTrip;
use Exception;

class TransportTripController
{
    protected TransportTripService $service;

    public function __construct(TransportTripService $service)
    {
        $this->service = $service;
    }

    public function index(int $orgId, array $requestData): array
    {
        $trips = TransportTrip::where('organization_id', $orgId)->get();
        return ApiResponse::success(['data' => $trips->toArray()]);
    }

    public function store(int $orgId, array $requestData): array
    {
        try {
            $trip = $this->service->createTrip($orgId, $requestData);
            return ApiResponse::success($trip->toArray(), 'Trip created', 201);
        } catch (Exception $e) {
            $status = $e->getCode() == 409 ? 409 : 400;
            return ApiResponse::error($e->getMessage(), null, $status);
        }
    }

    public function addPassenger(int $orgId, int $id, array $requestData): array
    {
        try {
            $passenger = $this->service->addPassenger($orgId, $id, $requestData);
            return ApiResponse::success($passenger->toArray(), 'Passenger added', 201);
        } catch (Exception $e) {
            $status = $e->getCode() == 409 ? 409 : 400;
            return ApiResponse::error($e->getMessage(), null, $status);
        }
    }
}
