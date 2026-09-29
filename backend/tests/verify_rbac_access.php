<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/app.php';

use App\Services\BaseService;
use App\Services\Auth\AuthService;

echo "====================================================\n";
echo " KhelSutra RBAC & IDOR Flow Verification Runner     \n";
echo "====================================================\n\n";

$pdo = BaseService::getDatabaseConnection();
$router = require __DIR__ . '/../routes/api.php';
$authService = new AuthService();

$testsPassed = 0;
$testsFailed = 0;

function runRbacTest(string $name, callable $testFn) {
    global $testsPassed, $testsFailed;
    try {
        $result = $testFn();
        if ($result === true) {
            echo " [PASS] " . $name . "\n";
            $testsPassed++;
        } else {
            echo " [FAIL] " . $name . " (Returned false)\n";
            $testsFailed++;
        }
    } catch (\Throwable $e) {
        echo " [FAIL] " . $name . " (Exception: " . $e->getMessage() . ")\n";
        $testsFailed++;
    }
}

// 1. Authenticate users for all roles
$superAdminAuth = $authService->login('superadmin@khelsutra.local', 'KhelSutra@123');
$sportsAdminAuth = $authService->login('sportsadmin@khelsutra.local', 'KhelSutra@123');
$hrFinanceAuth = $authService->login('hrfinance@khelsutra.local', 'KhelSutra@123');
$coachAuth = $authService->login('coach@khelsutra.local', 'KhelSutra@123');
$athleteAuth = $authService->login('athlete@khelsutra.local', 'KhelSutra@123');
$venueManagerAuth = $authService->login('venue.tournament@khelsutra.local', 'KhelSutra@123');
$inventoryManagerAuth = $authService->login('inventory@khelsutra.local', 'KhelSutra@123');

// Test 1: Athlete blocked from calling /api/v1/payroll/records
runRbacTest("RBAC: Athlete blocked from API Payroll records (403)", function() use ($router, $athleteAuth) {
    $res = $router('/api/v1/payroll/records', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $athleteAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

// Test 2: Athlete blocked from calling /api/v1/users
runRbacTest("RBAC: Athlete blocked from API Users list (403)", function() use ($router, $athleteAuth) {
    $res = $router('/api/v1/users', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $athleteAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

// Test 3: Athlete blocked from calling /api/v1/audit-logs
runRbacTest("RBAC: Athlete blocked from API Audit logs (403)", function() use ($router, $athleteAuth) {
    $res = $router('/api/v1/audit-logs', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $athleteAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

// Test 4: Athlete blocked from creating an athlete (POST /api/v1/athletes)
runRbacTest("RBAC: Athlete blocked from creating new athletes (403)", function() use ($router, $athleteAuth) {
    $res = $router('/api/v1/athletes', 'POST', [
        'headers' => ['authorization' => 'Bearer ' . $athleteAuth['token']],
        'first_name' => 'Hacker',
        'last_name' => 'Test'
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

// Test 5: Athlete blocked from recording performance
runRbacTest("RBAC: Athlete blocked from recording performance evaluation (403)", function() use ($router, $athleteAuth) {
    $res = $router('/api/v1/performance', 'POST', [
        'headers' => ['authorization' => 'Bearer ' . $athleteAuth['token']],
        'athlete_id' => 1,
        'overall_rating' => 9.5
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

// Test 6: Athlete blocked from recording training attendance
runRbacTest("RBAC: Athlete blocked from recording training attendance (403)", function() use ($router, $athleteAuth) {
    $res = $router('/api/v1/attendance/training/1', 'POST', [
        'headers' => ['authorization' => 'Bearer ' . $athleteAuth['token']],
        'attendance' => [['athlete_id' => 1, 'status' => 'present']]
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

// Test 7: Athlete IDOR - Athlete requesting another athlete's details returns 403
runRbacTest("IDOR: Athlete viewing another athlete's details rejected (403)", function() use ($router, $athleteAuth) {
    // rahul.sharma has athlete_id = 1
    $res = $router('/api/v1/athletes/2', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $athleteAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

// Test 8: Athlete viewing own profile returns 200 OK
runRbacTest("IDOR: Athlete viewing own profile allowed (200 OK)", function() use ($router, $athleteAuth) {
    $res = $router('/api/v1/athletes/1', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $athleteAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 200;
});

// Test 9: Coach blocked from calling /api/v1/payroll/records
runRbacTest("RBAC: Coach blocked from API Payroll records (403)", function() use ($router, $coachAuth) {
    $res = $router('/api/v1/payroll/records', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $coachAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

// Test 10: Coach blocked from creating athletes
runRbacTest("RBAC: Coach blocked from creating athletes (403)", function() use ($router, $coachAuth) {
    $res = $router('/api/v1/athletes', 'POST', [
        'headers' => ['authorization' => 'Bearer ' . $coachAuth['token']],
        'first_name' => 'Unauthorized',
        'last_name' => 'Athlete'
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

// Test 11: Coach squad isolation - Coach evaluating athlete outside assigned squad returns 403
runRbacTest("RBAC: Coach evaluating athlete outside assigned squads rejected (403)", function() use ($router, $coachAuth) {
    // athlete 9999 or an unassigned athlete
    $res = $router('/api/v1/performance', 'POST', [
        'headers' => ['authorization' => 'Bearer ' . $coachAuth['token']],
        'athlete_id' => 999999,
        'overall_rating' => 8.0
    ]);
    return ($res['_status_code'] ?? 0) === 403 || ($res['_status_code'] ?? 0) === 404;
});

// Test 12: Non-SuperAdmin blocked from /api/v1/organizations
runRbacTest("RBAC: Sports Administrator blocked from platform organizations (403)", function() use ($router, $sportsAdminAuth) {
    $res = $router('/api/v1/organizations', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $sportsAdminAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

// Test 13: Super Admin allowed on /api/v1/organizations
runRbacTest("RBAC: Super Admin allowed on platform organizations (200 OK)", function() use ($router, $superAdminAuth) {
    $res = $router('/api/v1/organizations', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $superAdminAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 200;
});

// Test 14: Tenant Isolation - Cross-Tenant Access Rejected
runRbacTest("Tenant Isolation: Requesting cross-tenant athlete by ID rejected", function() use ($router, $sportsAdminAuth, $pdo) {
    $stmt = $pdo->query("SELECT id FROM athletes WHERE organization_id = 2 AND deleted_at IS NULL LIMIT 1");
    $org2AthleteId = $stmt ? (int)$stmt->fetchColumn() : 0;
    if (!$org2AthleteId) {
        $pdo->query("INSERT INTO athletes (organization_id, athlete_code, first_name, last_name, gender, date_of_birth, created_at) VALUES (2, 'TEST-ORG2', 'Foreign', 'Athlete', 'male', '2005-01-01', NOW())");
        $org2AthleteId = (int)$pdo->lastInsertId();
    }
    // Sports Admin of Org 1 requesting Org 2 athlete
    $res = $router("/api/v1/athletes/{$org2AthleteId}", 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $sportsAdminAuth['token']]
    ]);
    return in_array($res['_status_code'] ?? 0, [403, 404], true);
});

// Test 15: Cross-Tenant Organization Spoofing via Header Blocked
runRbacTest("Tenant Isolation: Non-SuperAdmin header spoofing (X-Organization-ID: 2) blocked", function() use ($router, $sportsAdminAuth) {
    $res = $router('/api/v1/athletes', 'GET', [
        'headers' => [
            'authorization' => 'Bearer ' . $sportsAdminAuth['token'],
            'x-organization-id' => '2'
        ]
    ]);
    // Should be rejected with 403 Forbidden
    return ($res['_status_code'] ?? 0) === 403;
});

// Test 16: HR & Finance allowed on Payroll records (200 OK)
runRbacTest("RBAC: HR & Finance allowed on Payroll records (200 OK)", function() use ($router, $hrFinanceAuth) {
    $res = $router('/api/v1/payroll/records', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $hrFinanceAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 200;
});

// Test 17: HR & Finance blocked from Users management (403)
runRbacTest("RBAC: HR & Finance blocked from User management (403)", function() use ($router, $hrFinanceAuth) {
    $res = $router('/api/v1/users', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $hrFinanceAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

// Test 18: Inventory Manager allowed on Inventory items (200 OK)
runRbacTest("RBAC: Inventory Manager allowed on stock inventory (200 OK)", function() use ($router, $inventoryManagerAuth) {
    $res = $router('/api/v1/inventory/items', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $inventoryManagerAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 200;
});

// Test 19: Inventory Manager blocked from Payroll (403)
runRbacTest("RBAC: Inventory Manager blocked from Payroll records (403)", function() use ($router, $inventoryManagerAuth) {
    $res = $router('/api/v1/payroll/records', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $inventoryManagerAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

// Test 20: Venue Manager allowed on Venues (200 OK)
runRbacTest("RBAC: Venue Manager allowed on Venues list (200 OK)", function() use ($router, $venueManagerAuth) {
    $res = $router('/api/v1/venues', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $venueManagerAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 200;
});

// Test 21: Venue Manager blocked from Payroll (403)
runRbacTest("RBAC: Venue Manager blocked from Payroll records (403)", function() use ($router, $venueManagerAuth) {
    $res = $router('/api/v1/payroll/records', 'GET', [
        'headers' => ['authorization' => 'Bearer ' . $venueManagerAuth['token']]
    ]);
    return ($res['_status_code'] ?? 0) === 403;
});

echo "\n----------------------------------------------------\n";
echo " Results: {$testsPassed} Passed, {$testsFailed} Failed\n";
echo "----------------------------------------------------\n";

exit($testsFailed === 0 ? 0 : 1);
