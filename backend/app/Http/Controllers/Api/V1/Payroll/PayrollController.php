<?php

namespace App\Http\Controllers\Api\V1\Payroll;

use App\Http\Controllers\Controller;
use App\Services\Payroll\PayrollService;
use App\Helpers\ApiResponse;
use Exception;

class PayrollController extends Controller
{
    protected PayrollService $payrollService;

    public function __construct(?PayrollService $payrollService = null)
    {
        $this->payrollService = $payrollService ?? new PayrollService();
    }

    public function getSalaryStructure(int $orgId, int $employeeId): array
    {
        $struct = $this->payrollService->getSalaryStructure($orgId, $employeeId);
        if (!$struct) {
            return ApiResponse::error('Salary structure not found for this employee.', null, 404);
        }
        return ApiResponse::success($struct, 'Salary structure retrieved', 200);
    }

    public function setSalaryStructure(int $orgId, int $employeeId, array $requestData, ?int $performedBy = null): array
    {
        $struct = $this->payrollService->setSalaryStructure($orgId, $employeeId, $requestData, $performedBy);
        if (!$struct) {
            return ApiResponse::error('Failed to configure salary structure.', null, 422);
        }
        return ApiResponse::success($struct, 'Salary structure configured successfully', 200);
    }

    public function periods(int $orgId): array
    {
        $periods = $this->payrollService->listPeriods($orgId);
        return ApiResponse::success($periods, 'Payroll periods retrieved', 200);
    }

    public function storePeriod(int $orgId, array $requestData, ?int $performedBy = null): array
    {
        if (empty($requestData['period_name']) || empty($requestData['start_date']) || empty($requestData['end_date'])) {
            return ApiResponse::error('Period name, start date, and end date are required.', null, 422);
        }
        $period = $this->payrollService->createPeriod($orgId, $requestData, $performedBy);
        return ApiResponse::success($period, 'Payroll period created successfully', 201);
    }

    public function updatePeriodStatus(int $orgId, int $periodId, array $requestData, ?int $performedBy = null): array
    {
        $status = $requestData['status'] ?? '';
        try {
            $ok = $this->payrollService->updatePeriodStatus($orgId, $periodId, $status, $performedBy);
            if (!$ok) {
                return ApiResponse::error('Failed to update period status.', null, 400);
            }
            return ApiResponse::success(null, "Payroll period status updated to {$status}", 200);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }
    }

    public function records(int $orgId, array $requestData): array
    {
        $periodId = !empty($requestData['period_id']) ? (int)$requestData['period_id'] : null;
        $limit = (int)($requestData['limit'] ?? 50);
        $offset = (int)($requestData['offset'] ?? 0);
        $records = $this->payrollService->listPayrollRecords($orgId, $periodId, $limit, $offset);
        return ApiResponse::success($records, 'Payroll records retrieved', 200);
    }

    public function process(int $orgId, int $periodId, int $employeeId, array $requestData, ?int $performedBy = null): array
    {
        try {
            $res = $this->payrollService->processEmployeePayroll($orgId, $periodId, $employeeId, $requestData, $performedBy);
            return ApiResponse::success($res, 'Employee payroll processed successfully', 200);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }
    }

    public function updatePayment(int $orgId, int $payrollId, array $requestData, ?int $performedBy = null): array
    {
        $status = $requestData['payment_status'] ?? 'paid';
        $date = $requestData['payment_date'] ?? null;
        $ref = $requestData['payment_reference'] ?? null;
        $remarks = $requestData['remarks'] ?? null;

        $ok = $this->payrollService->updatePaymentStatus($orgId, $payrollId, $status, $date, $ref, $remarks, $performedBy);
        if (!$ok) {
            return ApiResponse::error('Failed to update payment status.', null, 400);
        }
        return ApiResponse::success(null, "Payment status updated to {$status}", 200);
    }
}
