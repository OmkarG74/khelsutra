<?php

// KhelSutra Test Suite Runner

if (file_exists(__DIR__ . '/../bootstrap/app.php')) {
    require_once __DIR__ . '/../bootstrap/app.php';
} elseif (file_exists(__DIR__ . '/../backend/bootstrap/app.php')) {
    require_once __DIR__ . '/../backend/bootstrap/app.php';
}

echo "====================================================\n";
echo " KhelSutra Member 1 Automated Test Suite Runner     \n";
echo "====================================================\n\n";

$tests = [
    'Feature: Health Check' => function() {
        require_once __DIR__ . '/Feature/Api/AuthenticationTest.php';
        $t = new \Tests\Feature\Api\AuthenticationTest();
        return $t->testHealthCheckReturnsOk();
    },
    'Feature: Valid Login' => function() {
        require_once __DIR__ . '/Feature/Api/AuthenticationTest.php';
        $t = new \Tests\Feature\Api\AuthenticationTest();
        return $t->testLoginWithValidCredentials();
    },
    'Feature: Invalid Login Rejection' => function() {
        require_once __DIR__ . '/Feature/Api/AuthenticationTest.php';
        $t = new \Tests\Feature\Api\AuthenticationTest();
        return $t->testLoginWithInvalidPasswordFails();
    },
    'Feature: RBAC - All 7 Roles Authentication & Context' => function() {
        require_once __DIR__ . '/Feature/RoleIsolationTest.php';
        $t = new \Tests\Feature\RoleIsolationTest();
        return $t->testAllSevenRolesCanAuthenticate();
    },
    'Feature: RBAC - Role Query Parameter Simulation Removal' => function() {
        require_once __DIR__ . '/Feature/RoleIsolationTest.php';
        $t = new \Tests\Feature\RoleIsolationTest();
        return $t->testRoleSimulationQueryParameterIsIgnored();
    },
    'Feature: Tenant Isolation - Cross-Tenant Parameter Blocking' => function() {
        require_once __DIR__ . '/Feature/RoleIsolationTest.php';
        $t = new \Tests\Feature\RoleIsolationTest();
        return $t->testCrossTenantAccessAttemptIsBlocked();
    },
    'Feature: RBAC - Non-SuperAdmin Blocked From Platform Endpoints' => function() {
        require_once __DIR__ . '/Feature/RoleIsolationTest.php';
        $t = new \Tests\Feature\RoleIsolationTest();
        return $t->testNonSuperAdminRolesCannotAccessSuperAdminEndpoints();
    },
    'Feature: RBAC - Self Role Elevation Defense' => function() {
        require_once __DIR__ . '/Feature/RoleIsolationTest.php';
        $t = new \Tests\Feature\RoleIsolationTest();
        return $t->testSelfRoleElevationIsBlocked();
    },
    'Feature: RBAC - Unauthorized Permission Modification Defense' => function() {
        require_once __DIR__ . '/Feature/RoleIsolationTest.php';
        $t = new \Tests\Feature\RoleIsolationTest();
        return $t->testUnauthorizedPermissionManipulationIsBlocked();
    },
    'Feature: RBAC - Elimination of Switch Org & Role Preview Controls' => function() {
        require_once __DIR__ . '/Feature/RoleIsolationTest.php';
        $t = new \Tests\Feature\RoleIsolationTest();
        return $t->testDemoControlsRemovedFromViews();
    },
    'Feature: Organization SuperAdmin Access' => function() {
        require_once __DIR__ . '/Feature/Api/OrganizationAccessTest.php';
        $t = new \Tests\Feature\Api\OrganizationAccessTest();
        return $t->testSuperAdminBypassesOrganizationRestriction();
    },
    'Feature: Tenant Isolation - Cross-Tenant Employee Forbidden' => function() {
        require_once __DIR__ . '/Feature/TenantIsolationTest.php';
        $t = new \Tests\Feature\TenantIsolationTest();
        return $t->testCrossTenantEmployeeAccessIsForbidden();
    },
    'Feature: Tenant Isolation - Cross-Tenant Leave Forbidden' => function() {
        require_once __DIR__ . '/Feature/TenantIsolationTest.php';
        $t = new \Tests\Feature\TenantIsolationTest();
        return $t->testCrossTenantLeaveAccessIsForbidden();
    },
    'Feature: Tenant Isolation - Cross-Tenant Payroll Forbidden' => function() {
        require_once __DIR__ . '/Feature/TenantIsolationTest.php';
        $t = new \Tests\Feature\TenantIsolationTest();
        return $t->testCrossTenantPayrollAccessIsForbidden();
    },
    'Feature: Tenant Isolation - Scoped Settings' => function() {
        require_once __DIR__ . '/Feature/TenantIsolationTest.php';
        $t = new \Tests\Feature\TenantIsolationTest();
        return $t->testOrganizationSettingsAreTenantScoped();
    },
    'Feature: RBAC - Standard 7 Roles' => function() {
        require_once __DIR__ . '/Feature/RolePermissionTest.php';
        $t = new \Tests\Feature\RolePermissionTest();
        return $t->testStandardSevenRolesExist();
    },
    'Feature: RBAC - Permission Resolution' => function() {
        require_once __DIR__ . '/Feature/RolePermissionTest.php';
        $t = new \Tests\Feature\RolePermissionTest();
        return $t->testUserPermissionResolution();
    },
    'Feature: Staff - Employee Creation and Retrieval' => function() {
        require_once __DIR__ . '/Feature/EmployeeTest.php';
        $t = new \Tests\Feature\EmployeeTest();
        return $t->testEmployeeCreationAndRetrieval();
    },
    'Feature: Staff - Coach Profile Foundation Link' => function() {
        require_once __DIR__ . '/Feature/EmployeeTest.php';
        $t = new \Tests\Feature\EmployeeTest();
        return $t->testEmployeeCoachRelationship();
    },
    'Feature: Attendance - Training Single-Subject XOR Enforcement' => function() {
        require_once __DIR__ . '/Feature/TrainingAttendanceTest.php';
        $t = new \Tests\Feature\TrainingAttendanceTest();
        return $t->testMultipleSubjectsFailsSingleSubjectRule() && $t->testNoSubjectProvidedFails();
    },
    'Feature: Attendance - Valid Employee Training Attendance' => function() {
        require_once __DIR__ . '/Feature/TrainingAttendanceTest.php';
        $t = new \Tests\Feature\TrainingAttendanceTest();
        return $t->testValidEmployeeAttendanceSucceeds();
    },
    'Feature: Attendance - Match Lineup Attendance' => function() {
        require_once __DIR__ . '/Feature/MatchAttendanceTest.php';
        $t = new \Tests\Feature\MatchAttendanceTest();
        return $t->testMatchAttendanceMultipleSubjectsFails() && $t->testValidMatchAttendanceSucceeds();
    },
    'Feature: Leave - End Date Before Start Date Rejection' => function() {
        require_once __DIR__ . '/Feature/LeaveTest.php';
        $t = new \Tests\Feature\LeaveTest();
        return $t->testEndDateBeforeStartDateFails();
    },
    'Feature: Leave - Separation of Duties (No Self Approval)' => function() {
        require_once __DIR__ . '/Feature/LeaveTest.php';
        $t = new \Tests\Feature\LeaveTest();
        return $t->testApplicantCannotApproveOwnRequest();
    },
    'Feature: Leave - Authorized Review Workflow' => function() {
        require_once __DIR__ . '/Feature/LeaveTest.php';
        $t = new \Tests\Feature\LeaveTest();
        return $t->testAuthorizedReviewerCanApprove();
    },
    'Unit: Payroll - Section 62 Decimal-Safe Calculation (40000 - 3500 = 36500)' => function() {
        require_once __DIR__ . '/Unit/PayrollCalculationTest.php';
        $t = new \Tests\Unit\PayrollCalculationTest();
        return $t->testStandardPayrollCalculation();
    },
    'Unit: Payroll - Zero Allowances and OT Calculations' => function() {
        require_once __DIR__ . '/Unit/PayrollCalculationTest.php';
        $t = new \Tests\Unit\PayrollCalculationTest();
        return $t->testZeroAllowancesAndBonusCalculation() && $t->testOvertimeAmountCalculation();
    },
    'Feature: Payroll - Locked Period Protection' => function() {
        require_once __DIR__ . '/Feature/PayrollTest.php';
        $t = new \Tests\Feature\PayrollTest();
        return $t->testLockedPayrollPeriodCannotBeModified();
    },
    'Feature: Payroll - Live Dashboard Summary' => function() {
        require_once __DIR__ . '/Feature/PayrollTest.php';
        $t = new \Tests\Feature\PayrollTest();
        return $t->testPayrollDashboardSummary();
    },
    'Feature: Audit Logs - Password and Credential Redaction' => function() {
        require_once __DIR__ . '/Feature/AuditLogTest.php';
        $t = new \Tests\Feature\AuditLogTest();
        return $t->testAuditLogRedactsPasswords();
    },
    'Feature: Organisation Settings - Typed Casting' => function() {
        require_once __DIR__ . '/Feature/OrganizationSettingsTest.php';
        $t = new \Tests\Feature\OrganizationSettingsTest();
        return $t->testTypedSettingsCasting();
    },
];

$passed = 0;
$failed = 0;

foreach ($tests as $name => $fn) {
    try {
        $res = $fn();
        if ($res) {
            echo " [PASS] {$name}\n";
            $passed++;
        } else {
            echo " [FAIL] {$name}\n";
            $failed++;
        }
    } catch (\Throwable $e) {
        echo " [ERROR] {$name}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n----------------------------------------------------\n";
echo " Results: {$passed} Passed, {$failed} Failed\n";
echo "----------------------------------------------------\n";

exit($failed > 0 ? 1 : 0);
