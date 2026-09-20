<?php

namespace App\Http\Controllers\Api\V1\Organizations;

use App\Http\Controllers\Controller;
use App\Services\Organization\OrganizationManagementService;
use App\Services\Organization\OrganizationSettingsService;
use App\Helpers\ApiResponse;

class OrganizationController extends Controller
{
    protected OrganizationManagementService $orgService;
    protected OrganizationSettingsService $settingsService;

    public function __construct(?OrganizationManagementService $orgService = null, ?OrganizationSettingsService $settingsService = null)
    {
        $this->orgService = $orgService ?? new OrganizationManagementService();
        $this->settingsService = $settingsService ?? new OrganizationSettingsService();
    }

    public function index(array $requestData): array
    {
        $limit = (int)($requestData['limit'] ?? 50);
        $offset = (int)($requestData['offset'] ?? 0);
        $orgs = $this->orgService->listOrganizations($limit, $offset);
        return ApiResponse::success($orgs, 'Organizations retrieved successfully', 200);
    }

    public function show(int $id): array
    {
        $org = $this->orgService->getOrganization($id);
        if (!$org) {
            return ApiResponse::error('Organization not found', null, 404);
        }
        return ApiResponse::success($org, 'Organization details retrieved', 200);
    }

    public function store(array $requestData, ?int $performedBy = null): array
    {
        if (empty($requestData['name'])) {
            return ApiResponse::error('Organization name is required.', ['name' => ['The name field is required.']], 422);
        }

        $newOrg = $this->orgService->createOrganization($requestData, $performedBy);
        if (!$newOrg) {
            return ApiResponse::error('Failed to create organization.', null, 500);
        }

        // If initial sports admin details were provided, set them up
        if (!empty($requestData['admin_email']) && !empty($requestData['admin_first_name'])) {
            $this->orgService->createInitialSportsAdmin((int)$newOrg['id'], [
                'email' => $requestData['admin_email'],
                'first_name' => $requestData['admin_first_name'],
                'last_name' => $requestData['admin_last_name'] ?? '',
                'password' => $requestData['admin_password'] ?? 'SecretPassword123',
                'phone' => $requestData['admin_phone'] ?? null,
            ], $performedBy);
        }

        return ApiResponse::success($newOrg, 'Organization created successfully', 201);
    }

    public function update(int $id, array $requestData, ?int $performedBy = null): array
    {
        $updated = $this->orgService->updateOrganization($id, $requestData, $performedBy);
        if (!$updated) {
            return ApiResponse::error('Organization not found or update failed.', null, 404);
        }
        return ApiResponse::success($updated, 'Organization updated successfully', 200);
    }

    public function updateStatus(int $id, array $requestData, ?int $performedBy = null): array
    {
        $status = $requestData['status'] ?? '';
        $remarks = $requestData['remarks'] ?? null;
        if (!in_array($status, ['pending', 'active', 'suspended', 'expired', 'inactive'], true)) {
            return ApiResponse::error('Invalid organization status.', ['status' => ['Allowed: pending, active, suspended, expired, inactive']], 422);
        }

        $ok = $this->orgService->updateStatus($id, $status, $remarks, $performedBy);
        if (!$ok) {
            return ApiResponse::error('Failed to update organization status.', null, 400);
        }
        return ApiResponse::success(null, "Organization status changed to {$status}", 200);
    }

    public function accessLogs(int $id): array
    {
        $logs = $this->orgService->getAccessLogs($id);
        return ApiResponse::success($logs, 'Access history logs retrieved', 200);
    }

    public function getSettings(int $id): array
    {
        $settings = $this->settingsService->getAll($id);
        return ApiResponse::success($settings, 'Organization settings retrieved', 200);
    }

    public function updateSettings(int $id, array $requestData, ?int $performedBy = null): array
    {
        $settings = $requestData['settings'] ?? [];
        if (!is_array($settings)) {
            return ApiResponse::error('Settings payload must be an array of key-value pairs.', null, 422);
        }

        foreach ($settings as $key => $val) {
            $type = is_bool($val) ? 'boolean' : (is_int($val) ? 'integer' : (is_array($val) ? 'json' : 'string'));
            $this->settingsService->set($id, $key, $val, $type, $performedBy);
        }

        return ApiResponse::success($this->settingsService->getAll($id), 'Settings updated successfully', 200);
    }
}
