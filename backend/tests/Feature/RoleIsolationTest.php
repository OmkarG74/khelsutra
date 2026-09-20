<?php

namespace Tests\Feature;

use App\Services\Auth\AuthService;
use App\Helpers\ApiResponse;

class RoleIsolationTest
{
    protected AuthService $authService;
    protected $apiRouter;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->apiRouter = require __DIR__ . '/../../routes/api.php';
    }

    /**
     * Test 1-3: Verify all 7 local development accounts can login and receive their authentic role and organisation.
     */
    public function testAllSevenRolesCanAuthenticate(): bool
    {
        $testUsers = [
            'superadmin@khelsutra.local' => ['role_id' => 1, 'role_name' => 'Super Admin', 'is_platform' => true],
            'sportsadmin@khelsutra.local' => ['role_id' => 2, 'role_name' => 'Sports Administrator', 'org_id' => 1],
            'hrfinance@khelsutra.local' => ['role_id' => 3, 'role_name' => 'HR & Finance', 'org_id' => 1],
            'coach@khelsutra.local' => ['role_id' => 4, 'role_name' => 'Coach', 'org_id' => 1],
            'athlete@khelsutra.local' => ['role_id' => 5, 'role_name' => 'Athlete', 'org_id' => 1],
            'venue.tournament@khelsutra.local' => ['role_id' => 6, 'role_name' => 'Venue & Tournament Manager', 'org_id' => 1],
            'inventory@khelsutra.local' => ['role_id' => 7, 'role_name' => 'Inventory Manager', 'org_id' => 1],
        ];

        foreach ($testUsers as $email => $expected) {
            $session = $this->authService->login($email, 'KhelSutra@123');
            if (!$session || empty($session['token'])) {
                echo "Failed to login for {$email}\n";
                return false;
            }

            if ((int)$session['role']['id'] !== $expected['role_id']) {
                echo "Role mismatch for {$email}: expected {$expected['role_id']}, got {$session['role']['id']}\n";
                return false;
            }

            if (!empty($expected['is_platform'])) {
                if ($session['organization']['organization_code'] !== 'PLATFORM') {
                    echo "Super Admin did not receive platform organisation context\n";
                    return false;
                }
            } else {
                if ((int)$session['organization']['id'] !== $expected['org_id']) {
                    echo "Organisation mismatch for {$email}: expected {$expected['org_id']}, got {$session['organization']['id']}\n";
                    return false;
                }
            }

            // Verify /api/v1/auth/me returns authentic user using the issued Bearer token
            $meResponse = ($this->apiRouter)('/api/v1/auth/me', 'GET', [
                'headers' => ['authorization' => 'Bearer ' . $session['token']]
            ]);

            if (empty($meResponse['success']) || (int)$meResponse['data']['role']['id'] !== $expected['role_id']) {
                echo "Auth/me failed for {$email}\n";
                return false;
            }
        }

        return true;
    }

    /**
     * Test 4: Role simulation via query parameter ?role= or payload is completely ignored.
     */
    public function testRoleSimulationQueryParameterIsIgnored(): bool
    {
        $session = $this->authService->login('sportsadmin@khelsutra.local', 'KhelSutra@123');
        $token = $session['token'];

        // Request with attempted ?role=super_admin query string
        $response = ($this->apiRouter)('/api/v1/auth/me', 'GET', [
            'headers' => ['authorization' => 'Bearer ' . $token],
            'role' => 'super_admin',
            'role_id' => 1
        ]);

        // User MUST remain Sports Administrator (role_id 2)
        return ($response['success'] === true && (int)$response['data']['role']['id'] === 2);
    }

    /**
     * Test 5 & 12: Tenant isolation - non-superadmin cannot access another organisation by providing organisation_id.
     */
    public function testCrossTenantAccessAttemptIsBlocked(): bool
    {
        $session = $this->authService->login('sportsadmin@khelsutra.local', 'KhelSutra@123');
        $token = $session['token'];

        // Sports Administrator belongs to Org 1. Attempt accessing Org 2 (National Football Academy)
        $responseWithHeader = ($this->apiRouter)('/api/v1/users', 'GET', [
            'headers' => [
                'authorization' => 'Bearer ' . $token,
                'x-organization-id' => '2'
            ]
        ]);

        $codeHeader = $responseWithHeader['_status_code'] ?? ($responseWithHeader['status_code'] ?? 200);
        if ($codeHeader !== 403) {
            echo "Failed: Cross-tenant via X-Organization-ID was not blocked with 403 (got {$codeHeader})\n";
            return false;
        }

        $responseWithBody = ($this->apiRouter)('/api/v1/users', 'GET', [
            'headers' => ['authorization' => 'Bearer ' . $token],
            'organization_id' => 2
        ]);

        $codeBody = $responseWithBody['_status_code'] ?? ($responseWithBody['status_code'] ?? 200);
        if ($codeBody !== 403) {
            echo "Failed: Cross-tenant via organization_id parameter was not blocked with 403 (got {$codeBody})\n";
            return false;
        }

        return true;
    }

    /**
     * Test 6-11: Verify that none of the 6 normal organisation roles can access Super Admin endpoints (/api/v1/organizations*).
     */
    public function testNonSuperAdminRolesCannotAccessSuperAdminEndpoints(): bool
    {
        $nonSuperAdminEmails = [
            'sportsadmin@khelsutra.local',
            'coach@khelsutra.local',
            'athlete@khelsutra.local',
            'hrfinance@khelsutra.local',
            'inventory@khelsutra.local',
            'venue.tournament@khelsutra.local'
        ];

        foreach ($nonSuperAdminEmails as $email) {
            $session = $this->authService->login($email, 'KhelSutra@123');
            $token = $session['token'];

            $res = ($this->apiRouter)('/api/v1/organizations', 'GET', [
                'headers' => ['authorization' => 'Bearer ' . $token]
            ]);

            $code = $res['_status_code'] ?? ($res['status_code'] ?? 200);
            if ($code !== 403) {
                echo "Failed: {$email} was not blocked from /api/v1/organizations (got {$code})\n";
                return false;
            }
        }

        // Super Admin must be allowed (status 200)
        $superAdminSession = $this->authService->login('superadmin@khelsutra.local', 'KhelSutra@123');
        $superAdminRes = ($this->apiRouter)('/api/v1/organizations', 'GET', [
            'headers' => ['authorization' => 'Bearer ' . $superAdminSession['token']]
        ]);

        $superCode = $superAdminRes['_status_code'] ?? ($superAdminRes['status_code'] ?? 200);
        return ($superCode === 200 && $superAdminRes['success'] === true);
    }

    /**
     * Test: User cannot change their own role or elevate to Super Admin.
     */
    public function testSelfRoleElevationIsBlocked(): bool
    {
        $session = $this->authService->login('sportsadmin@khelsutra.local', 'KhelSutra@123');
        $token = $session['token'];
        $userId = $session['user']['id'];

        // Attempt self role change
        $controller = new \App\Http\Controllers\Api\V1\Users\UserController();
        $response = $controller->update(1, $userId, ['role_id' => 1], $userId);

        $code = $response['_status_code'] ?? ($response['status_code'] ?? 200);
        return ($code === 403);
    }

    /**
     * Test: Non-admin users cannot manipulate permissions.
     */
    public function testUnauthorizedPermissionManipulationIsBlocked(): bool
    {
        $coachSession = $this->authService->login('coach@khelsutra.local', 'KhelSutra@123');
        $token = $coachSession['token'];

        $res = ($this->apiRouter)('/api/v1/permissions/override', 'POST', [
            'headers' => ['authorization' => 'Bearer ' . $token],
            'user_id' => 7,
            'permission_id' => 1,
            'override_type' => 'grant'
        ]);

        $code = $res['_status_code'] ?? ($res['status_code'] ?? 200);
        return ($code === 403);
    }

    /**
     * Test 13-14: Verify that "Switch Organisation" and "Preview Role Navigation" markup does NOT exist in navbar.
     */
    public function testDemoControlsRemovedFromViews(): bool
    {
        $navbarContent = file_get_contents(__DIR__ . '/../../resources/views/components/navbar.blade.php');
        $sidebarContent = file_get_contents(__DIR__ . '/../../resources/views/components/sidebar.blade.php');
        $loginContent = file_get_contents(__DIR__ . '/../../resources/views/auth/login.blade.php');

        // Verify "Switch Organisation" is removed
        if (stripos($navbarContent, 'Switch Organisation') !== false || stripos($navbarContent, 'Switch Organization') !== false) {
            echo "Switch Organisation still found in navbar.blade.php\n";
            return false;
        }

        // Verify "Preview Role Navigation" is removed
        if (stripos($navbarContent, 'Preview Role Navigation') !== false) {
            echo "Preview Role Navigation still found in navbar.blade.php\n";
            return false;
        }

        // Verify role query parameter appending is removed from sidebar
        if (strpos($sidebarContent, "role=' . \$currentRole") !== false || strpos($sidebarContent, '$_GET[\'role\']') !== false) {
            echo "Role query simulation still found in sidebar.blade.php\n";
            return false;
        }

        // Verify role switcher dropdown is removed from login
        if (strpos($loginContent, '<select class="ks-form-select" name="role">') !== false) {
            echo "Role switcher select dropdown still found in login.blade.php\n";
            return false;
        }

        return true;
    }
}
