<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Helpers\ApiResponse;
use App\Services\Operations\EventService;
use App\Models\Event;
use Exception;

class EventController
{
    protected EventService $service;

    public function __construct(EventService $service)
    {
        $this->service = $service;
    }

    public function index(int $orgId, array $requestData): array
    {
        $events = Event::where('organization_id', $orgId)->get();
        return ApiResponse::success(['data' => $events->toArray()]);
    }

    public function store(int $orgId, array $requestData): array
    {
        try {
            $event = $this->service->createEvent($orgId, $requestData);
            return ApiResponse::success($event->toArray(), 'Event created', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }

    public function addParticipant(int $orgId, int $id, array $requestData): array
    {
        try {
            $participant = $this->service->addParticipant($orgId, $id, $requestData);
            return ApiResponse::success($participant->toArray(), 'Participant added', 201);
        } catch (Exception $e) {
            $status = $e->getCode() == 409 ? 409 : 400;
            return ApiResponse::error($e->getMessage(), null, $status);
        }
    }

    public function recordExpense(int $orgId, int $id, array $requestData): array
    {
        try {
            $amount = (float)($requestData['amount'] ?? 0);
            $description = $requestData['description'] ?? 'Event Expense';
            $vendorId = isset($requestData['vendor_id']) ? (int)$requestData['vendor_id'] : null;
            
            $expenseId = $this->service->recordEventExpense($orgId, $id, $amount, $description, $vendorId);
            return ApiResponse::success(['expense_id' => $expenseId], 'Expense recorded', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 400);
        }
    }
}
