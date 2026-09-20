<?php

namespace App\Http\Controllers\Api\V1\Audit;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogService;
use App\Helpers\ApiResponse;

class AuditLogController extends Controller
{
    protected AuditLogService $auditService;

    public function __construct(?AuditLogService $auditService = null)
    {
        $this->auditService = $auditService ?? new AuditLogService();
    }

    public function index(?int $orgId, array $requestData): array
    {
        $limit = (int)($requestData['limit'] ?? 50);
        $offset = (int)($requestData['offset'] ?? 0);
        $logs = $this->auditService->getLogs($orgId, $limit, $offset);
        return ApiResponse::success($logs, 'Audit trail logs retrieved', 200);
    }
}
