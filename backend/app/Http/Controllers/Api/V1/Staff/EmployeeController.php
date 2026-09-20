<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Controller;
use App\Services\Staff\EmployeeService;
use App\Helpers\ApiResponse;

class EmployeeController extends Controller
{
    protected EmployeeService $employeeService;

    public function __construct(?EmployeeService $employeeService = null)
    {
        $this->employeeService = $employeeService ?? new EmployeeService();
    }

    public function index(int $orgId, array $requestData): array
    {
        $limit = (int)($requestData['limit'] ?? 50);
        $offset = (int)($requestData['offset'] ?? 0);
        $filters = [
            'status' => $requestData['status'] ?? null,
            'department_id' => $requestData['department_id'] ?? null,
            'category_id' => $requestData['category_id'] ?? null,
            'search' => $requestData['search'] ?? null,
        ];
        $employees = $this->employeeService->listEmployees($orgId, $filters, $limit, $offset);
        return ApiResponse::success($employees, 'Employees retrieved successfully', 200);
    }

    public function show(int $orgId, int $id): array
    {
        $employee = $this->employeeService->getEmployee($orgId, $id);
        if (!$employee) {
            return ApiResponse::error('Employee not found in organization.', null, 404);
        }
        return ApiResponse::success($employee, 'Employee details retrieved', 200);
    }

    public function store(int $orgId, array $requestData, ?int $performedBy = null): array
    {
        if (empty($requestData['first_name'])) {
            return ApiResponse::error('First name is required.', ['first_name' => ['The first name field is required.']], 422);
        }

        $employee = $this->employeeService->createEmployee($orgId, $requestData, $performedBy);
        if (!$employee) {
            return ApiResponse::error('Failed to create employee.', null, 500);
        }
        return ApiResponse::success($employee, 'Employee registered successfully', 201);
    }

    public function update(int $orgId, int $id, array $requestData, ?int $performedBy = null): array
    {
        $updated = $this->employeeService->updateEmployee($orgId, $id, $requestData, $performedBy);
        if (!$updated) {
            return ApiResponse::error('Employee not found or update failed.', null, 404);
        }
        return ApiResponse::success($updated, 'Employee updated successfully', 200);
    }
}
