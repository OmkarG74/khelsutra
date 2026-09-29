<?php

namespace Database\Seeders;

use PDO;

class RolePermissionSeeder
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function run(): void
    {
        // 1. Fetch existing roles dynamically by name
        $rolesStmt = $this->pdo->query("SELECT id, name FROM roles");
        $roles = [];
        while ($row = $rolesStmt->fetch(PDO::FETCH_ASSOC)) {
            $roles[$row['name']] = (int)$row['id'];
        }

        // 2. Fetch existing permissions dynamically by name
        $permsStmt = $this->pdo->query("SELECT id, name FROM permissions");
        $permissions = [];
        while ($row = $permsStmt->fetch(PDO::FETCH_ASSOC)) {
            $permissions[$row['name']] = (int)$row['id'];
        }

        // 3. Define appropriate default permission mappings for Member 5 roles
        $mappings = [
            'Inventory Manager' => [
                'inventory.view',
                'inventory.manage',
                'equipment.manage',
                'vendor.manage',
                'purchase.manage',
                'report.view',
                'notification.view',
                'notification.manage',
            ],
            'HR & Finance' => [
                'finance.view',
                'finance.manage',
                'vendor.manage',
                'purchase.manage',
                'report.view',
                'notification.view',
                'notification.manage',
                'payroll.view',
                'payroll.manage',
                'employee.view',
            ],
            // Sports Administrator has full organization-level access
            'Sports Administrator' => array_keys($permissions),
            // Super Admin has system-wide access
            'Super Admin' => array_keys($permissions),
        ];

        $insertStmt = $this->pdo->prepare("
            INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
            VALUES (:role_id, :permission_id, NOW())
        ");

        $totalSeeded = 0;
        foreach ($mappings as $roleName => $permNames) {
            $roleId = $roles[$roleName] ?? null;
            if (!$roleId) continue;

            foreach ($permNames as $pName) {
                $permId = $permissions[$pName] ?? null;
                if (!$permId) continue;

                $insertStmt->execute([
                    ':role_id' => $roleId,
                    ':permission_id' => $permId
                ]);
                if ($insertStmt->rowCount() > 0) {
                    $totalSeeded++;
                }
            }
        }

        echo "Role permissions seeded successfully! Total new role-permission grants: {$totalSeeded}\n";
    }
}

// Support direct invocation from CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=khelsutra;charset=utf8mb4', 'root', '');
    $seeder = new RolePermissionSeeder($pdo);
    $seeder->run();
}
