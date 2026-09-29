<?php

namespace App\Helpers;

class OperationsPermissionHelper
{
    /**
     * Map operations action to existing permissions.
     */
    public static function getPermissions(string $action): array
    {
        $map = [
            'view_venues' => ['venue.view', 'venue.manage', 'housekeeping.manage'],
            'create_venue' => ['venue.create'],
            'update_venue' => ['venue.update'],
            'manage_venue' => ['venue.manage'],
            
            'create_booking' => ['booking.create'],
            'manage_booking' => ['venue.booking.manage'],
            
            'manage_maintenance' => ['venue.maintenance.manage'],
            'manage_housekeeping' => ['housekeeping.manage'],
            
            'view_events' => ['event.view'],
            'manage_events' => ['event.manage'],
            
            'manage_transport' => ['transport.manage'],
            'view_transport' => ['event.view', 'transport.manage'],
            
            'manage_accommodation' => ['accommodation.manage'],
            'view_accommodation' => ['event.view', 'accommodation.manage'],
            
            'manage_finance' => ['finance.manage'],
        ];

        return $map[$action] ?? [];
    }

    public static function hasAny(int $userId, string $action): bool
    {
        $perms = self::getPermissions($action);
        if (empty($perms)) return false;

        $user = $_SESSION['auth']['user'] ?? null;
        $roleId = 0;
        $userPermissions = [];

        if ($user) {
            $roleId = isset($user['role_id']) ? (int)$user['role_id'] : ($_SESSION['auth']['role']['id'] ?? 0);
            $userPermissions = $_SESSION['auth']['permissions'] ?? [];
        } elseif ($userId > 0) {
            $pdo = \App\Services\BaseService::getDatabaseConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT role_id FROM organization_users WHERE user_id = :uid AND access_status = 'active' LIMIT 1");
                $stmt->execute([':uid' => $userId]);
                $roleId = (int)($stmt->fetchColumn() ?: 0);
                if ($roleId > 0) {
                    $permService = new \App\Services\Rbac\PermissionService();
                    $rolePerms = $permService->getRolePermissions($roleId);
                    $userPermissions = array_column($rolePerms, 'name');
                }
            }
        } else {
            return false;
        }

        // Super Admin (1) or Sports Administrator (2) have global access
        if ($roleId === 1 || $roleId === 2) {
            return true;
        }

        // Check user permission overrides (deny/grant)
        if ($userId > 0) {
            $pdo = \App\Services\BaseService::getDatabaseConnection();
            if ($pdo) {
                foreach ($perms as $perm) {
                    $ovStmt = $pdo->prepare("
                        SELECT upo.override_type 
                        FROM user_permission_overrides upo 
                        JOIN permissions p ON upo.permission_id = p.id 
                        WHERE upo.user_id = :uid AND p.name = :pname
                        LIMIT 1
                    ");
                    $ovStmt->execute([':uid' => $userId, ':pname' => $perm]);
                    $ovr = $ovStmt->fetchColumn();
                    if ($ovr === 'deny') return false;
                    if ($ovr === 'grant') return true;
                }
            }
        }

        // Check if the user has ANY of the required permissions
        foreach ($perms as $perm) {
            if (in_array($perm, $userPermissions, true)) {
                return true;
            }
        }

        return false;
    }
}
