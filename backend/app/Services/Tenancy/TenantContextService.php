<?php

namespace App\Services\Tenancy;

use App\Services\BaseService;
use PDO;

class TenantContextService extends BaseService
{
    /**
     * Resolve and verify tenant context for request.
     *
     * @param array $requestData
     * @param array|null $user
     * @return array|null Returns validated organization array or null if unauthorized
     */
    public function resolveTenant(array $requestData, ?array $user): ?array
    {
        if (!$this->pdo || !$user) {
            return null;
        }

        $userId = (int)$user['id'];
        $isSuperAdmin = ($user['role']['name'] ?? '') === 'Super Admin' || ($user['role_id'] ?? 0) === 1;

        // Requested organization from header or payload
        $requestedOrgId = isset($requestData['headers']['x-organization-id'])
            ? (int)$requestData['headers']['x-organization-id']
            : (isset($requestData['organization_id']) ? (int)$requestData['organization_id'] : null);

        // Super Admin can access any existing active organisation
        if ($isSuperAdmin) {
            if ($requestedOrgId) {
                $stmt = $this->pdo->prepare("SELECT * FROM organizations WHERE id = :id AND deleted_at IS NULL LIMIT 1");
                $stmt->execute([':id' => $requestedOrgId]);
                $org = $stmt->fetch();
                if ($org) return $org;
            }
            // Default to first organization or primary
            $stmt = $this->pdo->query("SELECT * FROM organizations WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1");
            return $stmt->fetch() ?: null;
        }

        // Regular user: MUST have an active organization_users entry
        if ($requestedOrgId) {
            $stmt = $this->pdo->prepare("
                SELECT o.*, ou.role_id, r.name as role_name 
                FROM organization_users ou 
                JOIN organizations o ON ou.organization_id = o.id 
                JOIN roles r ON ou.role_id = r.id 
                WHERE ou.user_id = :user_id 
                  AND ou.organization_id = :org_id 
                  AND ou.access_status = 'active' 
                  AND o.deleted_at IS NULL 
                  AND o.status = 'active' 
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId, ':org_id' => $requestedOrgId]);
            $membership = $stmt->fetch();
            return $membership ?: null; // Rejection if not a verified member
        }

        // Default to user's first active organization membership
        $stmt = $this->pdo->prepare("
            SELECT o.*, ou.role_id, r.name as role_name 
            FROM organization_users ou 
            JOIN organizations o ON ou.organization_id = o.id 
            JOIN roles r ON ou.role_id = r.id 
            WHERE ou.user_id = :user_id 
              AND ou.access_status = 'active' 
              AND o.deleted_at IS NULL 
              AND o.status = 'active' 
            ORDER BY o.id ASC 
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Check if user has explicit access to a specific organization.
     */
    public function hasAccess(int $userId, int $orgId, bool $isSuperAdmin = false): bool
    {
        if ($isSuperAdmin) {
            return true;
        }
        if (!$this->pdo) return false;

        $stmt = $this->pdo->prepare("
            SELECT 1 FROM organization_users ou 
            JOIN organizations o ON ou.organization_id = o.id 
            WHERE ou.user_id = :user_id 
              AND ou.organization_id = :org_id 
              AND ou.access_status = 'active' 
              AND o.status = 'active' 
              AND o.deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId, ':org_id' => $orgId]);
        return (bool)$stmt->fetchColumn();
    }
}
