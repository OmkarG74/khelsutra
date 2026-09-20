<?php

namespace App\Http\Controllers\Api\V1\Leave;

use App\Http\Controllers\Controller;
use App\Services\Leave\LeaveService;
use App\Helpers\ApiResponse;
use Exception;

class LeaveController extends Controller
{
    protected LeaveService $leaveService;

    public function __construct(?LeaveService $leaveService = null)
    {
        $this->leaveService = $leaveService ?? new LeaveService();
    }

    public function types(int $orgId): array
    {
        $types = $this->leaveService->listLeaveTypes($orgId);
        return ApiResponse::success($types, 'Leave types retrieved', 200);
    }

    public function storeType(int $orgId, array $requestData, ?int $performedBy = null): array
    {
        if (empty($requestData['name'])) {
            return ApiResponse::error('Leave type name is required.', ['name' => ['The name field is required.']], 422);
        }
        $type = $this->leaveService->createLeaveType($orgId, $requestData, $performedBy);
        return ApiResponse::success($type, 'Leave type created successfully', 201);
    }

    public function index(int $orgId, array $requestData): array
    {
        $status = $requestData['status'] ?? null;
        $limit = (int)($requestData['limit'] ?? 50);
        $offset = (int)($requestData['offset'] ?? 0);
        $requests = $this->leaveService->listLeaveRequests($orgId, $status, $limit, $offset);
        return ApiResponse::success($requests, 'Leave requests retrieved', 200);
    }

    public function show(int $orgId, int $id): array
    {
        $req = $this->leaveService->getLeaveRequest($orgId, $id);
        if (!$req) {
            return ApiResponse::error('Leave request not found.', null, 404);
        }
        return ApiResponse::success($req, 'Leave request retrieved', 200);
    }

    public function store(int $orgId, array $requestData, ?int $performedBy = null): array
    {
        try {
            $leave = $this->leaveService->applyLeave($orgId, $requestData, $performedBy);
            if (!$leave) {
                return ApiResponse::error('Failed to submit leave request. Required fields missing.', null, 422);
            }
            return ApiResponse::success($leave, 'Leave application submitted', 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }
    }

    public function review(int $orgId, int $id, array $requestData, int $approverUserId): array
    {
        $status = $requestData['status'] ?? '';
        $rejectionReason = $requestData['rejection_reason'] ?? null;

        if (!in_array($status, ['approved', 'rejected', 'cancelled'], true)) {
            return ApiResponse::error('Status must be approved, rejected, or cancelled.', null, 422);
        }

        try {
            $ok = $this->leaveService->reviewLeave($orgId, $id, $status, $rejectionReason, $approverUserId);
            if (!$ok) {
                return ApiResponse::error('Failed to update leave status. Request may not be in pending state.', null, 400);
            }
            return ApiResponse::success(null, "Leave application {$status}", 200);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }
    }
}
