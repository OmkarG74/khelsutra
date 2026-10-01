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
        $search = !empty($requestData['search']) ? trim($requestData['search']) : null;
        $status = !empty($requestData['status']) ? trim($requestData['status']) : null;
        $plan = !empty($requestData['plan']) ? trim($requestData['plan']) : null;

        $orgs = $this->orgService->listOrganizations($limit, $offset, $search, $status, $plan);
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
            return ApiResponse::error('Organisation name is required.', ['name' => ['The name field is required.']], 422);
        }

        // Normalize administrators array
        $admins = $requestData['admins'] ?? [];
        if (empty($admins)) {
            if (!empty($requestData['admin_email']) && !empty($requestData['admin_first_name'])) {
                $admins[] = [
                    'first_name' => $requestData['admin_first_name'],
                    'last_name' => $requestData['admin_last_name'] ?? '',
                    'email' => $requestData['admin_email'],
                    'phone' => $requestData['admin_phone'] ?? null,
                    'password' => $requestData['admin_password'] ?? 'SecretPassword123',
                ];
            }
        }

        try {
            if (!empty($admins)) {
                $newOrg = $this->orgService->createOrganizationWithAdmins($requestData, $admins, $performedBy);
            } else {
                $newOrg = $this->orgService->createOrganization($requestData, $performedBy);
            }

            if (!$newOrg) {
                return ApiResponse::error('Failed to create organisation.', null, 500);
            }

            return ApiResponse::success($newOrg, 'Organisation and administrator(s) created successfully', 201);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to create organisation: ' . $e->getMessage(), null, 409);
        }
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

    public function getProfileSettings(int $id): array
    {
        $org = $this->orgService->getOrganization($id);
        if (!$org) {
            return ApiResponse::error('Organisation not found', null, 404);
        }

        $allSettings = $this->settingsService->getAll($id);

        $payload = [
            'id' => (int)$org['id'],
            'name' => (string)($org['name'] ?? ''),
            'organization_code' => (string)($org['organization_code'] ?? ''),
            'email' => (string)($org['email'] ?? ''),
            'phone' => (string)($org['phone'] ?? ''),
            'address' => (string)($org['address_line1'] ?? ($allSettings['academic.address'] ?? '')),
            'timezone' => (string)($allSettings['academic.timezone'] ?? ($allSettings['timezone'] ?? 'Asia/Kolkata (IST +05:30)')),
            'currency' => (string)($allSettings['academic.currency'] ?? ($allSettings['currency'] ?? 'INR (₹) - Indian Rupee')),
            'attendance_threshold' => (int)($allSettings['attendance.threshold'] ?? ($allSettings['min_attendance_rate'] ?? 75)),
            'settings' => $allSettings,
        ];

        return ApiResponse::success($payload, 'Organisation profile settings retrieved', 200);
    }

    public function updateProfileSettings(int $id, array $requestData, ?int $performedBy = null): array
    {
        $org = $this->orgService->getOrganization($id);
        if (!$org) {
            return ApiResponse::error('Organisation not found', null, 404);
        }

        // 1. Validation
        $errors = [];

        $name = trim((string)($requestData['name'] ?? ''));
        if ($name === '') {
            $errors['name'] = ['The organisation name field is required.'];
        }

        $code = strtoupper(trim((string)($requestData['organization_code'] ?? ($requestData['code'] ?? ''))));
        if ($code === '') {
            $errors['organization_code'] = ['The organisation code field is required.'];
        }

        $email = trim((string)($requestData['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ['The contact email must be a valid email address.'];
        }

        $phone = trim((string)($requestData['phone'] ?? ''));

        $address = trim((string)($requestData['address'] ?? ($requestData['address_line1'] ?? '')));

        $timezone = trim((string)($requestData['timezone'] ?? 'Asia/Kolkata (IST +05:30)'));

        $currency = trim((string)($requestData['currency'] ?? 'INR (₹) - Indian Rupee'));

        $rawThreshold = $requestData['attendance_threshold'] ?? 75;
        if (!is_numeric($rawThreshold) || (float)$rawThreshold < 0 || (float)$rawThreshold > 100) {
            $errors['attendance_threshold'] = ['Attendance threshold must be a valid percentage between 0 and 100.'];
        }
        $threshold = (int)round((float)$rawThreshold);

        if (!empty($errors)) {
            return ApiResponse::error('Validation failed.', $errors, 422);
        }

        // 2. Persist to organizations table and organization_settings table
        try {
            $orgUpdateData = [
                'name' => $name,
                'organization_code' => $code,
                'email' => $email,
                'phone' => $phone,
                'address_line1' => $address,
                'settings' => [
                    'academic.address' => $address,
                    'academic.timezone' => $timezone,
                    'timezone' => $timezone,
                    'academic.currency' => $currency,
                    'currency' => $currency,
                    'attendance.threshold' => $threshold,
                    'min_attendance_rate' => $threshold,
                ],
            ];

            $updatedOrg = $this->orgService->updateOrganization($id, $orgUpdateData, $performedBy);
            if (!$updatedOrg) {
                return ApiResponse::error('Failed to update organisation record.', null, 500);
            }

            // Sync active PHP session organization details if updated
            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['auth']['organization']['id']) && (int)$_SESSION['auth']['organization']['id'] === $id) {
                $_SESSION['auth']['organization']['name'] = $name;
                $_SESSION['auth']['organization']['organization_code'] = $code;
            }

            $responsePayload = [
                'id' => (int)$updatedOrg['id'],
                'name' => (string)($updatedOrg['name'] ?? ''),
                'organization_code' => (string)($updatedOrg['organization_code'] ?? ''),
                'email' => (string)($updatedOrg['email'] ?? ''),
                'phone' => (string)($updatedOrg['phone'] ?? ''),
                'address' => (string)($updatedOrg['address_line1'] ?? $address),
                'timezone' => $timezone,
                'currency' => $currency,
                'attendance_threshold' => $threshold,
            ];

            return ApiResponse::success($responsePayload, 'Organisation settings updated successfully.', 200);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), ['organization_code' => [$e->getMessage()]], 422);
        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to update settings: ' . $e->getMessage(), null, 500);
        }
    }

    public function getAdmins(int $orgId): array
    {
        $org = $this->orgService->getOrganization($orgId);
        if (!$org) {
            return ApiResponse::error('Organization not found', null, 404);
        }
        $admins = $this->orgService->getOrganizationAdmins($orgId);
        return ApiResponse::success($admins, 'Organisation administrators retrieved', 200);
    }

    public function showAdmin(int $orgId, int $adminId): array
    {
        $admin = $this->orgService->getOrganizationAdmin($orgId, $adminId);
        if (!$admin) {
            return ApiResponse::error('Organisation administrator not found', null, 404);
        }
        return ApiResponse::success($admin, 'Administrator details retrieved', 200);
    }

    public function storeAdmin(int $orgId, array $requestData, ?int $performedBy = null): array
    {
        $org = $this->orgService->getOrganization($orgId);
        if (!$org) {
            return ApiResponse::error('Organization not found', null, 404);
        }

        try {
            $admin = $this->orgService->addOrganizationAdmin($orgId, $requestData, $performedBy);
            return ApiResponse::success($admin, 'Organisation Administrator assigned successfully', 201);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to create administrator: ' . $e->getMessage(), null, 409);
        }
    }

    public function updateAdmin(int $orgId, int $adminId, array $requestData, ?int $performedBy = null): array
    {
        $admin = $this->orgService->getOrganizationAdmin($orgId, $adminId);
        if (!$admin) {
            return ApiResponse::error('Organisation administrator not found', null, 404);
        }

        try {
            $updated = $this->orgService->updateOrganizationAdmin($orgId, $adminId, $requestData, $performedBy);
            return ApiResponse::success($updated, 'Organisation Administrator updated successfully', 200);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to update administrator: ' . $e->getMessage(), null, 409);
        }
    }

    public function updateAdminStatus(int $orgId, int $adminId, array $requestData, ?int $performedBy = null): array
    {
        $admin = $this->orgService->getOrganizationAdmin($orgId, $adminId);
        if (!$admin) {
            return ApiResponse::error('Organisation administrator not found', null, 404);
        }

        $status = $requestData['status'] ?? '';
        if (!in_array($status, ['active', 'inactive'], true)) {
            return ApiResponse::error('Invalid administrator status. Allowed: active, inactive', null, 422);
        }

        $ok = $this->orgService->updateOrganizationAdminStatus($orgId, $adminId, $status, $performedBy);
        if (!$ok) {
            return ApiResponse::error('Failed to update administrator status.', null, 400);
        }
        return ApiResponse::success(null, "Administrator status updated to {$status}", 200);
    }

    public function destroyAdmin(int $orgId, int $adminId, ?int $performedBy = null): array
    {
        $admin = $this->orgService->getOrganizationAdmin($orgId, $adminId);
        if (!$admin) {
            return ApiResponse::error('Organisation administrator not found', null, 404);
        }

        try {
            $ok = $this->orgService->removeOrganizationAdmin($orgId, $adminId, $performedBy);
            if (!$ok) {
                return ApiResponse::error('Failed to remove administrator from organisation.', null, 400);
            }
            return ApiResponse::success(null, 'Administrator removed from organisation successfully', 200);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to remove administrator: ' . $e->getMessage(), null, 409);
        }
    }
}
