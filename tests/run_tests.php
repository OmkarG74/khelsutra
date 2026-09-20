<?php

// KhelSutra Test Suite Runner

if (file_exists(__DIR__ . '/../bootstrap/app.php')) {
    require_once __DIR__ . '/../bootstrap/app.php';
} elseif (file_exists(__DIR__ . '/../backend/bootstrap/app.php')) {
    require_once __DIR__ . '/../backend/bootstrap/app.php';
}

echo "========================================\n";
echo " KhelSutra Automated Test Suite Runner  \n";
echo "========================================\n\n";

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
    'Feature: Organization SuperAdmin Access' => function() {
        require_once __DIR__ . '/Feature/Api/OrganizationAccessTest.php';
        $t = new \Tests\Feature\Api\OrganizationAccessTest();
        return $t->testSuperAdminBypassesOrganizationRestriction();
    },
    'Feature: Athletes Query' => function() {
        require_once __DIR__ . '/Feature/Api/AthleteTest.php';
        $t = new \Tests\Feature\Api\AthleteTest();
        return $t->testListAthletesReturnsArray();
    },
    'Feature: Teams Query' => function() {
        require_once __DIR__ . '/Feature/Api/TeamTest.php';
        $t = new \Tests\Feature\Api\TeamTest();
        return $t->testListTeamsReturnsArray();
    },
    'Feature: Tournaments Query' => function() {
        require_once __DIR__ . '/Feature/Api/TournamentTest.php';
        $t = new \Tests\Feature\Api\TournamentTest();
        return $t->testListTournamentsReturnsArray();
    },
    'Feature: Venues Query' => function() {
        require_once __DIR__ . '/Feature/Api/VenueBookingTest.php';
        $t = new \Tests\Feature\Api\VenueBookingTest();
        return $t->testListVenuesReturnsArray();
    },
    'Feature: Permission Gate' => function() {
        require_once __DIR__ . '/Feature/Api/PermissionTest.php';
        $t = new \Tests\Feature\Api\PermissionTest();
        return $t->testRequirePermissionBlocksUnauthorizedUser();
    },
    'Unit: AthleteService Instantiation' => function() {
        require_once __DIR__ . '/Unit/Services/AthleteServiceTest.php';
        $t = new \Tests\Unit\Services\AthleteServiceTest();
        return $t->testAthleteServiceInstantiates();
    },
    'Unit: TeamService Instantiation' => function() {
        require_once __DIR__ . '/Unit/Services/TeamServiceTest.php';
        $t = new \Tests\Unit\Services\TeamServiceTest();
        return $t->testTeamServiceInstantiates();
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

echo "\n----------------------------------------\n";
echo " Results: {$passed} Passed, {$failed} Failed\n";
echo "----------------------------------------\n";

exit($failed > 0 ? 1 : 0);
