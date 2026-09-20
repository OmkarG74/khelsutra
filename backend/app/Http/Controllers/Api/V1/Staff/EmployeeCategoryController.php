<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Controller;
use App\Services\Staff\EmployeeCategoryService;
use App\Helpers\ApiResponse;

class EmployeeCategoryController extends Controller
{
    protected EmployeeCategoryService $categoryService;

    public function __construct(?EmployeeCategoryService $categoryService = null)
    {
        $this->categoryService = $categoryService ?? new EmployeeCategoryService();
    }

    public function index(int $orgId): array
    {
        $categories = $this->categoryService->listCategories($orgId);
        return ApiResponse::success($categories, 'Employee categories retrieved', 200);
    }

    public function show(int $orgId, int $id): array
    {
        $category = $this->categoryService->getCategory($orgId, $id);
        if (!$category) {
            return ApiResponse::error('Category not found.', null, 404);
        }
        return ApiResponse::success($category, 'Category retrieved', 200);
    }

    public function store(int $orgId, array $requestData, ?int $performedBy = null): array
    {
        if (empty($requestData['name'])) {
            return ApiResponse::error('Category name is required.', ['name' => ['The name field is required.']], 422);
        }
        $cat = $this->categoryService->createCategory($orgId, $requestData, $performedBy);
        return ApiResponse::success($cat, 'Category created successfully', 201);
    }

    public function update(int $orgId, int $id, array $requestData, ?int $performedBy = null): array
    {
        $updated = $this->categoryService->updateCategory($orgId, $id, $requestData, $performedBy);
        if (!$updated) {
            return ApiResponse::error('Category not found or update failed.', null, 404);
        }
        return ApiResponse::success($updated, 'Category updated successfully', 200);
    }
}
