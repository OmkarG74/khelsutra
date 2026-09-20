<?php

namespace App\Services\Rbac;

use App\Services\BaseService;
use App\Services\Audit\AuditLogService;
use PDO;

class PermissionService extends BaseService
{
    protected AuditLogService $auditLog;

    public function __construct(?PDO $pdo = null, ?AuditLogService $auditLog = null)
    {
        parent::__construct($pdo);
        $this->auditLog = $auditLog ?? new AuditLogService($this->pdo);
    }

    public function getRoles(): array
    {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->query("SELECT * FROM roles ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAllRoles(): array
    {
        return $this->getRoles();
    }

    public function getPermissions(): array
    {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->query("SELECT * FROM permissions ORDER BY module ASC, name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAllPermissions(): array
    {
        return $this->getPermissions();
    }

    public function getRolePermissions(int $roleId): array
    {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->prepare("
            SELECT p.* 
            FROM permissions p 
            JOIN role_permissions rp ON p.id = rp.permission_id 
            WHERE rp.role_id = :role_id 
            ORDER BY p.module ASC, p.name ASC
        ");
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function roleHasPermission(int $roleId, string $permission): bool
    {
        if (!$this->pdo) return false;
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = :role_id AND p.name = :perm
            LIMIT 1
        ");
        $stmt->execute([':role_id' => $roleId, ':perm' => $permission]);
        return (bool)$stmt->fetchColumn();
    }

    public function getUserPermissions(int $userId, int $orgId): array
    {
        if (!$this->pdo) return [];
        $stmt = $this->pdo->prepare("
            SELECT p.name FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN organization_users ou ON rp.role_id = ou.role_id
            WHERE ou.user_id = :uid AND ou.organization_id = :org_id
        ");
        $stmt->execute([':uid' => $userId, ':org_id' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function hasPermission(array|int $user, string $permission, ?int $orgId = null): bool
    {
        if (is_int($user)) {
            $user = ['id' => $user, 'role_id' => ($user === 1 ? 1 : 2)];
        }
        // Super Admin bypasses all checks
        $roleName = $user['role']['name'] ?? '';
        if ($roleName === 'Super Admin' || ($user['role_id'] ?? 0) === 1) {
            return true;
        }

        // Check user permission overrides first
        $userId = (int)($user['id'] ?? 0);
        $targetOrgId = $orgId ?? (int)($user['organization']['id'] ?? 1);

        if ($this->pdo && $userId && $targetOrgId) {
            $stmt = $this->pdo->prepare("
                SELECT upo.override_type 
                FROM user_permission_overrides upo 
                JOIN permissions p ON upo.permission_id = p.id 
                WHERE upo.user_id = :user_id 
                  AND upo.organization_id = :org_id 
                  AND p.name = :perm
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId, ':org_id' => $targetOrgId, ':perm' => $permission]);
            $override = $stmt->fetchColumn();

            if ($override === 'deny') return false;
            if ($override === 'grant') return true;
        }

        // Fall back to compiled permissions in user payload
        if (!empty($user['permissions']) && is_array($user['permissions'])) {
            return in_array($permission, $user['permissions'], true);
        }

        return false;
    }

    public function setPermissionOverride(int $orgId, int $userId, int $permissionId, string $type, ?int $performedBy = null): bool
    {
        if (!$this->pdo || !in_array($type, ['grant', 'deny'], true)) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO user_permission_overrides (organization_id, user_id, permission_id, override_type, created_by, created_at)
            VALUES (:org_id, :user_id, :perm_id, :type, :created_by, NOW())
            ON DUPLICATE KEY UPDATE override_type = :type2, created_by = :created_by2, created_at = NOW()
        ");

        $ok = $stmt->execute([
            ':org_id' => $orgId,
            ':user_id' => $userId,
            ':perm_id' => $permissionId,
            ':type' => $type,
            ':created_by' => $performedBy,
            ':type2' => $type,
            ':created_by2' => $performedBy,
        ]);

        if ($ok) {
            $this->auditLog->log(
                $orgId,
                $performedBy,
                'PERMISSION_CHANGE',
                'RBAC',
                'user_permission_overrides',
                $userId,
                null,
                ['permission_id' => $permissionId, 'override_type' => $type],
                "Permission override set to {$type} for user #{$userId}"
            );
        }

        return $ok;
    }
}
