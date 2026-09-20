<?php

namespace Tests\Feature;

use App\Services\Rbac\PermissionService;

class RolePermissionTest
{
    /**
     * Test the 7 standard platform roles (Section 20)
     */
    public function testStandardSevenRolesExist(): bool
    {
        $service = new PermissionService();
        $roles = $service->getAllRoles();
        $roleNames = array_column($roles, 'name');

        $requiredRoles = [
            'Super Admin',
            'Sports Administrator',
            'HR & Finance',
            'Coach',
            'Athlete',
            'Venue & Tournament Manager',
            'Inventory Manager'
        ];

        foreach ($requiredRoles as $role) {
            if (!in_array($role, $roleNames)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Test permission checks and user overrides
     */
    public function testUserPermissionResolution(): bool
    {
        $service = new PermissionService();
        // Super Admin (user 1) should have organization.create
        $canSuperAdminCreateOrg = $service->hasPermission(1, 'organization.create', 1);

        // HR & Finance (role 3) should have employee.create but not organization.suspend
        $hrRoleId = 3;
        $canHrCreateEmp = $service->roleHasPermission($hrRoleId, 'employee.create');
        $canHrSuspendOrg = $service->roleHasPermission($hrRoleId, 'organization.suspend');

        return ($canSuperAdminCreateOrg && $canHrCreateEmp && !$canHrSuspendOrg);
    }
}
