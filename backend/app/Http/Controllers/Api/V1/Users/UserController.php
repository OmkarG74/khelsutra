<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Http\Controllers\Controller;
use App\Services\User\UserManagementService;
use App\Helpers\ApiResponse;

class UserController extends Controller
{
    protected UserManagementService $userService;

    public function __construct(?UserManagementService $userService = null)
    {
        $this->userService = $userService ?? new UserManagementService();
    }

    public function index(int $orgId, array $requestData): array
    {
        $limit = (int)($requestData['limit'] ?? 50);
        $offset = (int)($requestData['offset'] ?? 0);
        $users = $this->userService->listUsers($orgId, $limit, $offset);
        return ApiResponse::success($users, 'Users retrieved successfully', 200);
    }

    public function show(int $orgId, int $id): array
    {
        $user = $this->userService->getUser($id);
        if (!$user || ($user['organization_id'] && (int)$user['organization_id'] !== $orgId)) {
            return ApiResponse::error('User not found in current organization.', null, 404);
        }
        return ApiResponse::success($user, 'User details retrieved', 200);
    }

    public function store(int $orgId, array $requestData, ?int $performedBy = null): array
    {
        if (empty($requestData['email']) || empty($requestData['first_name'])) {
            return ApiResponse::error('Email and first name are required.', [
                'email' => empty($requestData['email']) ? ['The email field is required.'] : [],
                'first_name' => empty($requestData['first_name']) ? ['The first name field is required.'] : [],
            ], 422);
        }

        // Role assignment protection
        $targetRoleId = (int)($requestData['role_id'] ?? 2);
        if ($targetRoleId === 1) {
            return ApiResponse::error('DENIED: Unauthorized role assignment. Super Admin role cannot be created.', null, 403);
        }

        $newUser = $this->userService->createUser($requestData, $orgId, $performedBy);
        if (!$newUser) {
            return ApiResponse::error('Failed to create user. A user with this email may already exist.', null, 409);
        }
        return ApiResponse::success($newUser, 'User created successfully', 201);
    }

    public function update(int $orgId, int $id, array $requestData, ?int $performedBy = null): array
    {
        $user = $this->userService->getUser($id);
        if (!$user || ($user['organization_id'] && (int)$user['organization_id'] !== $orgId)) {
            return ApiResponse::error('User not found in current organization.', null, 404);
        }

        // Role elevation / self-modification protection
        if (isset($requestData['role_id'])) {
            $targetRoleId = (int)$requestData['role_id'];
            if ($id === $performedBy && $targetRoleId !== (int)$user['role_id']) {
                return ApiResponse::error('DENIED: A user cannot change their own role.', null, 403);
            }
            if ($targetRoleId === 1) {
                return ApiResponse::error('DENIED: Unauthorized role assignment. Super Admin role cannot be assigned.', null, 403);
            }
        }

        $requestData['organization_id'] = $orgId;
        $updated = $this->userService->updateUser($id, $requestData, $performedBy);
        return ApiResponse::success($updated, 'User updated successfully', 200);
    }

    public function setStatus(int $orgId, int $id, array $requestData, ?int $performedBy = null): array
    {
        $status = $requestData['status'] ?? '';
        if (!in_array($status, ['active', 'inactive', 'locked'], true)) {
            return ApiResponse::error('Invalid status value.', ['status' => ['Allowed: active, inactive, locked']], 422);
        }

        $ok = $this->userService->setUserStatus($id, $status, $performedBy);
        if (!$ok) {
            return ApiResponse::error('Failed to update user status.', null, 400);
        }
        return ApiResponse::success(null, "User status updated to {$status}", 200);
    }
}
