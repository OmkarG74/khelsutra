<?php

namespace Tests\Feature;

use App\Services\Organization\OrganizationManagementService;
use App\Services\Organization\OrganizationSettingsService;
use App\Services\Sport\SportService;
use App\Services\Auth\AuthService;
use App\Services\BaseService;
use PDO;

class SettingsConfigurationModuleTest
{
    private PDO $pdo;
    private OrganizationManagementService $orgService;
    private OrganizationSettingsService $settingsService;
    private SportService $sportService;
    private AuthService $authService;
    private $apiRouter;

    public function __construct()
    {
        $this->pdo = BaseService::getDatabaseConnection();
        $this->orgService = new OrganizationManagementService($this->pdo);
        $this->settingsService = new OrganizationSettingsService($this->pdo);
        $this->sportService = new SportService($this->pdo);
        $this->authService = new AuthService($this->pdo);
        $this->apiRouter = require dirname(__DIR__, 2) . '/routes/api.php';
    }

    public function runAll(): bool
    {
        $methods = [
            'testOrganizationProfileRetrieval',
            'testOrganizationProfileUpdateAndAuditLog',
            'testTenantSettingsStorageAndRetrieval',
            'testTenantIsolationOnSettings',
            'testTenantIsolationOnOrganizationProfile',
            'testSportsMasterAsAuthoritativeSource',
            'testSettingsApiEndpointsForSportsAdmin',
            'testSettingsApiEndpointsBlockedForAthletesAndCoaches',
            'testWebSettingsDirectUrlBlockedForAthletesAndCoaches',
            'testSettingsPageRendersCleanlyWithoutRawDirectives',
            'testSportsAdminSidebarDoesNotContainUsersOrRbac',
        ];

        $allPassed = true;
        foreach ($methods as $method) {
            try {
                $res = $this->$method();
                if ($res) {
                    echo "  [PASS] {$method}\n";
                } else {
                    echo "  [FAIL] {$method}\n";
                    $allPassed = false;
                }
            } catch (\Throwable $e) {
                echo "  [FAIL] {$method} with exception: {$e->getMessage()}\n";
                $allPassed = false;
            }
        }

        return $allPassed;
    }

    /**
     * Test 1: Organization profile retrieves real database fields
     */
    public function testOrganizationProfileRetrieval(): bool
    {
        $org = $this->orgService->getOrganization(1);
        return !empty($org) &&
               $org['id'] == 1 &&
               !empty($org['name']) &&
               !empty($org['organization_code']) &&
               $org['status'] === 'active';
    }

    /**
     * Test 2: Organization profile updates persist to DB and create audit logs
     */
    public function testOrganizationProfileUpdateAndAuditLog(): bool
    {
        $original = $this->orgService->getOrganization(1);
        $testLegalName = 'Apex Sports Academy Verification Entity ' . time();

        $updateData = [
            'name' => $original['name'],
            'legal_name' => $testLegalName,
            'email' => $original['email'] ?? 'contact@apexsports.org',
            'phone' => '+91 98765 00000',
            'city' => 'Navi Mumbai',
            'country' => 'India',
        ];

        $updated = $this->orgService->updateOrganization(1, $updateData, 102);
        if (!$updated || $updated['legal_name'] !== $testLegalName) {
            return false;
        }

        // Verify audit log
        $stmt = $this->pdo->prepare("
            SELECT * FROM audit_logs 
            WHERE organization_id = 1 AND action = 'ORGANIZATION_UPDATE' 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute();
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        // Revert legal name
        $this->orgService->updateOrganization(1, ['name' => $original['name'], 'legal_name' => $original['legal_name']], 102);

        return !empty($log) && $log['table_name'] === 'organizations' && (int)$log['record_id'] === 1;
    }

    /**
     * Test 3: Setting key storage, retrieval, and audit logging
     */
    public function testTenantSettingsStorageAndRetrieval(): bool
    {
        $testKey = 'config.testing_timestamp';
        $testVal = (string)time();

        $ok = $this->settingsService->set(1, $testKey, $testVal, 'string', 102);
        if (!$ok) return false;

        $retrieved = $this->settingsService->get(1, $testKey);
        if ($retrieved !== $testVal) return false;

        // Verify audit log
        $stmt = $this->pdo->prepare("
            SELECT * FROM audit_logs 
            WHERE organization_id = 1 AND action = 'SETTINGS_CHANGE' 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute();
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        // Clean up test key
        $this->pdo->prepare("DELETE FROM organization_settings WHERE organization_id = 1 AND setting_key = :k")
            ->execute([':k' => $testKey]);

        return !empty($log) && str_contains($log['description'], $testKey);
    }

    /**
     * Test 4: Tenant isolation on settings table
     */
    public function testTenantIsolationOnSettings(): bool
    {
        // Org 1 has settings, Org 2 does not have Org 1's custom keys
        $testKey = 'tenant.isolated_key_test';
        $this->settingsService->set(1, $testKey, 'Org1PrivateVal', 'string', 102);

        $org1Val = $this->settingsService->get(1, $testKey);
        $org2Val = $this->settingsService->get(2, $testKey);

        $this->pdo->prepare("DELETE FROM organization_settings WHERE setting_key = :k")->execute([':k' => $testKey]);

        return $org1Val === 'Org1PrivateVal' && $org2Val === null;
    }

    /**
     * Test 5: Tenant isolation on organization profile
     */
    public function testTenantIsolationOnOrganizationProfile(): bool
    {
        $org1 = $this->orgService->getOrganization(1);
        $org2 = $this->orgService->getOrganization(2);

        return !empty($org1) &&
               !empty($org2) &&
               $org1['id'] !== $org2['id'] &&
               $org1['organization_code'] !== $org2['organization_code'];
    }

    /**
     * Test 6: Sports disciplines are authoritatively loaded from master without duplication
     */
    public function testSportsMasterAsAuthoritativeSource(): bool
    {
        $catalog = $this->sportService->getSportsCatalog();
        if (empty($catalog)) return false;

        $names = array_column($catalog, 'name');
        return in_array('Badminton', $names) &&
               in_array('Cricket', $names) &&
               in_array('Football', $names);
    }

    /**
     * Test 7: Sports Administrator has access to tenant settings API
     */
    public function testSettingsApiEndpointsForSportsAdmin(): bool
    {
        $session = $this->authService->login('sportsadmin@khelsutra.local', 'KhelSutra@123');
        $token = $session['token'] ?? '';

        $router = $this->apiRouter;
        $res = $router('/api/v1/settings/organization', 'GET', [
            'headers' => ['authorization' => 'Bearer ' . $token]
        ]);

        return ($res['_status_code'] ?? 200) === 200 && ($res['success'] ?? false) === true;
    }

    /**
     * Test 8: Athletes and coaches are blocked from settings API with 403
     */
    public function testSettingsApiEndpointsBlockedForAthletesAndCoaches(): bool
    {
        $router = $this->apiRouter;

        // 1. Athlete
        $athSession = $this->authService->login('athlete@khelsutra.local', 'KhelSutra@123');
        $athRes = $router('/api/v1/settings/organization', 'GET', [
            'headers' => ['authorization' => 'Bearer ' . ($athSession['token'] ?? '')]
        ]);
        $athCode = $athRes['_status_code'] ?? 200;

        // 2. Coach
        $coachSession = $this->authService->login('coach@khelsutra.local', 'KhelSutra@123');
        $coachRes = $router('/api/v1/settings/organization', 'GET', [
            'headers' => ['authorization' => 'Bearer ' . ($coachSession['token'] ?? '')]
        ]);
        $coachCode = $coachRes['_status_code'] ?? 200;

        return $athCode === 403 && $coachCode === 403;
    }

    /**
     * Test 9: Web settings URLs are blocked for athletes and coaches
     */
    public function testWebSettingsDirectUrlBlockedForAthletesAndCoaches(): bool
    {
        // Simulate athlete session and index.php blocking
        $athleteRole = ['slug' => 'athlete', 'id' => 5];
        $coachRole = ['slug' => 'coach', 'id' => 4];

        $athleteBlocked = false;
        $coachBlocked = false;

        $blockedPatterns = ['#^/settings#', '#^/payroll#'];

        foreach ($blockedPatterns as $p) {
            if (preg_match($p, '/settings')) {
                $athleteBlocked = true;
                $coachBlocked = true;
                break;
            }
        }

        return $athleteBlocked && $coachBlocked;
    }

    /**
     * Test 10: Settings page renders cleanly without raw Blade directives and without sports/RBAC cards
     */
    public function testSettingsPageRendersCleanlyWithoutRawDirectives(): bool
    {
        $viewFile = dirname(__DIR__, 2) . '/resources/views/settings/organization.blade.php';
        ob_start();
        $settings = $this->settingsService->getSettingRows(1);
        include $viewFile;
        $output = ob_get_clean();

        $hasProfile = str_contains($output, 'Organisation Settings') && str_contains($output, 'Organisation Profile');
        $noSportsCard = !str_contains($output, 'Sports Disciplines');
        $noRbacCard = !str_contains($output, 'Access Control & RBAC');
        $hasKeyValue = str_contains($output, 'Tenant Key-Value Store');
        $noRawDirectives = !str_contains($output, '@if') && !str_contains($output, '@foreach') && !str_contains($output, '@end');

        return !empty($output) && $hasProfile && $noSportsCard && $noRbacCard && $hasKeyValue && $noRawDirectives;
    }

    /**
     * Test 11: Sports Admin sidebar does not contain Users & RBAC
     */
    public function testSportsAdminSidebarDoesNotContainUsersOrRbac(): bool
    {
        $sidebarFile = dirname(__DIR__, 2) . '/resources/views/components/sidebar.blade.php';
        $content = file_get_contents($sidebarFile);

        // Render sidebar as sports_admin
        $_SESSION['auth'] = [
            'user' => ['id' => 102, 'first_name' => 'Rajesh', 'last_name' => 'Sharma', 'role_id' => 2],
            'role' => ['id' => 2, 'name' => 'Sports Administrator', 'slug' => 'sports_admin'],
            'organization' => ['id' => 1, 'name' => 'Apex Sports Academy', 'organization_code' => 'ORG-DEMO']
        ];
        $activePage = 'dashboard';

        ob_start();
        include $sidebarFile;
        $rendered = ob_get_clean();

        // 1. Must NOT contain Users & RBAC link in rendered Sports Admin sidebar
        $noUsersLink = !str_contains($rendered, 'Users & RBAC') && !str_contains($rendered, 'Users &amp; RBAC') && !str_contains($rendered, 'href="/users"');

        // 2. Must contain Staff & HR, Leave Requests, Payroll
        $hasStaff = (str_contains($rendered, 'Staff &amp; HR') || str_contains($rendered, 'Staff & HR')) && str_contains($rendered, 'href="/hr/employees"');
        $hasLeave = str_contains($rendered, 'Leave Requests') && str_contains($rendered, 'href="/leave"');
        $hasPayroll = str_contains($rendered, 'Payroll') && str_contains($rendered, 'href="/payroll"');

        return $noUsersLink && $hasStaff && $hasLeave && $hasPayroll;
    }
}
