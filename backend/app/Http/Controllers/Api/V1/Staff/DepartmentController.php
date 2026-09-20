<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Controller;
use App\Services\Staff\DepartmentService;
use App\Helpers\ApiResponse;

class DepartmentController extends Controller
{
    protected DepartmentService $deptService;

    public function __construct(?DepartmentService $deptService = null)
    {
        $this->deptService = $deptService ?? new DepartmentService();
    }

    public function index(int $orgId): array
    {
        $departments = $this->deptService->listDepartments($orgId);
        return ApiResponse::success($departments, 'Departments retrieved successfully', 200);
    }

    public function show(int $orgId, int $id): array
    {
        $dept = $this->deptService->getDepartment($orgId, $id);
        if (!$dept) {
            return ApiResponse::error('Department not found.', null, 404);
        }
        return ApiResponse::success($dept, 'Department retrieved', 200);
    }

    public function store(int $orgId, array $requestData, ?int $performedBy = null): array
    {
        if (empty($requestData['name'])) {
            return ApiResponse::error('Department name is required.', ['name' => ['The name field is required.']], 422);
        }
        $dept = $this->deptService->createDepartment($orgId, $requestData, $performedBy);
        return ApiResponse::success($dept, 'Department created successfully', 201);
    }

    public function update(int $orgId, int $id, array $requestData, ?int $performedBy = null): array
    {
        $updated = $this->deptService->updateDepartment($orgId, $id, $requestData, $performedBy);
        if (!$updated) {
            return ApiResponse::error('Department not found or update failed.', null, 404);
        }
        return ApiResponse::success($updated, 'Department updated successfully', 200);
    }
}
