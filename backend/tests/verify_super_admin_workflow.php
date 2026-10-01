<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/app.php';

use App\Services\BaseService;
use App\Services\Auth\AuthService;
use App\Services\Organization\OrganizationManagementService;
use App\Services\User\UserManagementService;

echo "=================================================================\n";
echo " KhelSutra Super Admin Workflow & Multi-Admin Verification Suite \n";
echo "=================================================================\n\n";

$pdo = BaseService::getDatabaseConnection();
if (!$pdo) {
    echo "[FAIL] Could not connect to database.\n";
    exit(1);
}

$router = require __DIR__ . '/../routes/api.php';
$authService = new AuthService();
$orgService = new OrganizationManagementService($pdo);
$userService = new UserManagementService($pdo);

$testsPassed = 0;
$testsFailed = 0;

function assertTest(string $name, callable $fn) {
    global $testsPassed, $testsFailed;
    try {
        $result = $fn();
        if ($result === true) {
            echo " [PASS] " . $name . "\n";
            $testsPassed++;
        } else {
            echo " [FAIL] " . $name . " (Returned false)\n";
            $testsFailed++;
        }
    } catch (\Throwable $e) {
        echo " [FAIL] " . $name . " (Exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . ")\n";
        $testsFailed++;
    }
}

// 1. Authenticate users
$superAdminAuth = $authService->login('superadmin@khelsutra.local', 'KhelSutra@123');
if (!$superAdminAuth) {
    // Try alternative demo super admin email
    $superAdminAuth = $authService->login('admin@khelsutra.com', 'SecretPassword123');
}
$sportsAdminAuth = $authService->login('sportsadmin@khelsutra.local', 'KhelSutra@123');
$athleteAuth = $authService->login('athlete@khelsutra.local', 'KhelSutra@123');

// TEST 1: Super Admin Authentication & Platform Context
assertTest("AUTH: Super Admin login resolves correctly", function() use ($superAdminAuth) {
    if (!$superAdminAuth) return false;
    $roleId = (int)($superAdminAuth['role']['id'] ?? 0);
    $roleSlug = $superAdminAuth['role']['slug'] ?? '';
    return ($roleId === 1 || $roleSlug === 'super_admin');
});

// TEST 2: Direct Organisations List API
assertTest("ORGANISATIONS: Super Admin can list all organisations directly", function() use ($router, $superAdminAuth) {
    $res = $router('/api/v1/organizations', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . ($superAdminAuth['token'] ?? '')]
    ]);
    return ($res['_status_code'] ?? 0) === 200 && isset($res['data']);
});

// TEST 3: Unauthorized user blocked from Organisations API (Security 403)
assertTest("SECURITY: Non-Super Admin (Athlete) blocked from /api/v1/organizations (403)", function() use ($router, $athleteAuth) {
    $res = $router('/api/v1/organizations', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . ($athleteAuth['token'] ?? '')]
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

// TEST 4: Create Organisation with 1 Administrator
$testOrgCode1 = 'TEST-ORG-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
$createdOrg1Id = null;

assertTest("WORKFLOW: Create Organisation with 1 Administrator", function() use ($router, $superAdminAuth, $testOrgCode1, &$createdOrg1Id) {
    $adminEmail = 'admin1_' . strtolower(substr(bin2hex(random_bytes(3)), 0, 5)) . '@testacademy.org';
    $res = $router('/api/v1/organizations', 'POST', [
        'headers' => ['authorization' => 'Bearer ' . ($superAdminAuth['token'] ?? '')],
        'name' => 'Titan Sports Academy',
        'organization_code' => $testOrgCode1,
        'plan_name' => 'Standard Sports ERP',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'admins' => [
            [
                'first_name' => 'Vikram',
                'last_name' => 'Joshi',
                'email' => $adminEmail,
                'phone' => '+91 9876543201',
                'password' => 'Secret123!'
            ]
        ]
    ]);

    if (($res['_status_code'] ?? 0) !== 201) return false;
    $createdOrg1Id = (int)($res['data']['id'] ?? 0);
    $admins = $res['data']['admins'] ?? [];
    return ($createdOrg1Id > 0 && count($admins) === 1 && $admins[0]['email'] === $adminEmail);
});

// TEST 5: Create Organisation with Multiple Administrators (2 Admins)
$testOrgCode2 = 'TEST-ORG-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
$createdOrg2Id = null;

assertTest("WORKFLOW: Create Organisation with 2 Administrators in single workflow", function() use ($router, $superAdminAuth, $testOrgCode2, &$createdOrg2Id) {
    $adminEmailA = 'admin2a_' . strtolower(substr(bin2hex(random_bytes(3)), 0, 5)) . '@testacademy.org';
    $adminEmailB = 'admin2b_' . strtolower(substr(bin2hex(random_bytes(3)), 0, 5)) . '@testacademy.org';
    $res = $router('/api/v1/organizations', 'POST', [
        'headers' => ['authorization' => 'Bearer ' . ($superAdminAuth['token'] ?? '')],
        'name' => 'Olympus High Performance Center',
        'organization_code' => $testOrgCode2,
        'plan_name' => 'High Performance Elite',
        'city' => 'Bangalore',
        'state' => 'Karnataka',
        'admins' => [
            [
                'first_name' => 'Rohit',
                'last_name' => 'Sharma',
                'email' => $adminEmailA,
                'phone' => '+91 9876543202',
                'password' => 'Secret123!'
            ],
            [
                'first_name' => 'Priya',
                'last_name' => 'Nair',
                'email' => $adminEmailB,
                'phone' => '+91 9876543203',
                'password' => 'Secret123!'
            ]
        ]
    ]);

    if (($res['_status_code'] ?? 0) !== 201) return false;
    $createdOrg2Id = (int)($res['data']['id'] ?? 0);
    $admins = $res['data']['admins'] ?? [];
    return ($createdOrg2Id > 0 && count($admins) === 2);
});

// TEST 6: Transaction Safety: Rollback on failure (duplicate email in admin list)
assertTest("TRANSACTION SAFETY: Rollback on failure - no partial organisation created", function() use ($router, $superAdminAuth, $pdo) {
    $failOrgCode = 'FAIL-ORG-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    $dupEmail = 'duplicate_' . strtolower(substr(bin2hex(random_bytes(3)), 0, 5)) . '@testacademy.org';

    $res = $router('/api/v1/organizations', 'POST', [
        'headers' => ['authorization' => 'Bearer ' . ($superAdminAuth['token'] ?? '')],
        'name' => 'Doomed Academy',
        'organization_code' => $failOrgCode,
        'admins' => [
            ['first_name' => 'Admin1', 'email' => $dupEmail],
            ['first_name' => 'Admin2', 'email' => $dupEmail] // Duplicate!
        ]
    ]);

    // Should return 422 validation error
    $code = $res['_status_code'] ?? 0;
    if ($code !== 422 && $code !== 409) return false;

    // Verify database: the organisation MUST NOT exist
    $checkStmt = $pdo->prepare("SELECT id FROM organizations WHERE organization_code = :code");
    $checkStmt->execute([':code' => $failOrgCode]);
    $exists = $checkStmt->fetchColumn();

    return ($exists === false);
});

// TEST 7: View Organisation (Loads all fields + admins)
assertTest("WORKFLOW: View Organisation returns full details and administrators", function() use ($router, $superAdminAuth, $createdOrg2Id) {
    if (!$createdOrg2Id) return false;
    $res = $router('/api/v1/organizations/' . $createdOrg2Id, 'GET', [
        'headers' => ['authorization' => 'Bearer ' . ($superAdminAuth['token'] ?? '')]
    ]);

    if (($res['_status_code'] ?? 0) !== 200) return false;
    $org = $res['data'] ?? [];
    return (!empty($org['name']) && !empty($org['organization_code']) && count($org['admins'] ?? []) === 2);
});

// TEST 8: Edit Organisation (Update fields)
assertTest("WORKFLOW: Edit Organisation updates metadata correctly", function() use ($router, $superAdminAuth, $createdOrg1Id) {
    if (!$createdOrg1Id) return false;
    $res = $router('/api/v1/organizations/' . $createdOrg1Id, 'PUT', [
        'headers' => ['authorization' => 'Bearer ' . ($superAdminAuth['token'] ?? '')],
        'name' => 'Titan Sports Academy (Updated)',
        'city' => 'Navi Mumbai',
        'notes' => 'Updated operational details via Super Admin console'
    ]);

    if (($res['_status_code'] ?? 0) !== 200) return false;
    return (($res['data']['name'] ?? '') === 'Titan Sports Academy (Updated)' && ($res['data']['city'] ?? '') === 'Navi Mumbai');
});

// TEST 9: Suspend Organisation
assertTest("WORKFLOW: Suspend Organisation changes status to suspended", function() use ($router, $superAdminAuth, $createdOrg1Id, $orgService) {
    if (!$createdOrg1Id) return false;
    $res = $router('/api/v1/organizations/' . $createdOrg1Id . '/status', 'PATCH', [
        'headers' => ['authorization' => 'Bearer ' . ($superAdminAuth['token'] ?? '')],
        'status' => 'suspended',
        'remarks' => 'Suspended for testing'
    ]);

    if (($res['_status_code'] ?? 0) !== 200) return false;
    $org = $orgService->getOrganization($createdOrg1Id);
    return (($org['status'] ?? '') === 'suspended');
});

// TEST 10: Activate Organisation
assertTest("WORKFLOW: Activate Organisation restores status to active", function() use ($router, $superAdminAuth, $createdOrg1Id, $orgService) {
    if (!$createdOrg1Id) return false;
    $res = $router('/api/v1/organizations/' . $createdOrg1Id . '/status', 'PATCH', [
        'headers' => ['authorization' => 'Bearer ' . ($superAdminAuth['token'] ?? '')],
        'status' => 'active',
        'remarks' => 'Restored for testing'
    ]);

    if (($res['_status_code'] ?? 0) !== 200) return false;
    $org = $orgService->getOrganization($createdOrg1Id);
    return (($org['status'] ?? '') === 'active');
});

// TEST 11: Users Directory - Organisation Admins appear with role Organisation Admin
assertTest("USERS: Organisation Admins appear in Users list with Organisation Admin role (role_id 2)", function() use ($userService, $createdOrg2Id) {
    if (!$createdOrg2Id) return false;
    $orgAdmins = $userService->listUsers($createdOrg2Id);
    if (empty($orgAdmins)) return false;
    foreach ($orgAdmins as $adminUser) {
        if ((int)($adminUser['role_id'] ?? 0) !== 2) return false;
    }
    return true;
});

// TEST 12: User Management Service getUserDetails works without error
assertTest("USERS: getUserDetails retrieves user profile and organisation memberships", function() use ($userService) {
    $details = $userService->getUserDetails(1, 1);
    if (!$details) return false;
    return (!empty($details['email']) && isset($details['organizations']));
});

// Cleanup test organisations
if ($createdOrg1Id) {
    $pdo->exec("DELETE FROM organization_users WHERE organization_id = {$createdOrg1Id}");
    $pdo->exec("DELETE FROM organizations WHERE id = {$createdOrg1Id}");
}
if ($createdOrg2Id) {
    $pdo->exec("DELETE FROM organization_users WHERE organization_id = {$createdOrg2Id}");
    $pdo->exec("DELETE FROM organizations WHERE id = {$createdOrg2Id}");
}

echo "\n=================================================================\n";
echo " TEST SUMMARY: {$testsPassed} PASSED, {$testsFailed} FAILED\n";
echo "=================================================================\n";

if ($testsFailed === 0) {
    echo "SUCCESS: ALL SUPER ADMIN WORKFLOW TESTS PASSED 100%!\n";
    exit(0);
} else {
    echo "ERROR: {$testsFailed} tests failed.\n";
    exit(1);
}
