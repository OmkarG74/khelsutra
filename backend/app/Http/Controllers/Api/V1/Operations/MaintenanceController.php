<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Helpers\ApiResponse;
use App\Services\Operations\MaintenanceService;
use App\Models\VenueMaintenance;

class MaintenanceController
{
    protected MaintenanceService $service;

    public function __construct()
    {
        $this->service = new MaintenanceService();
    }

    public function index(int $orgId, array $requestData): array
    {
        $query = VenueMaintenance::where('organization_id', $orgId);
        $tickets = $query->orderBy('created_at', 'desc')->get();
        return ApiResponse::success(['data' => $tickets->toArray()]);
    }

    public function store(int $orgId, array $requestData): array
    {
        try {
            $ticket = $this->service->createTicket($orgId, $requestData);
            return ApiResponse::success($ticket->toArray(), 'Maintenance ticket created', 201);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }
}
