<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/Feature/TeamRosterManagementComprehensiveTest.php';

echo "========================================================\n";
echo " KhelSutra — Team Creation & Roster Management Test Suite \n";
echo "========================================================\n\n";

$testSuite = new \Tests\Feature\TeamRosterManagementComprehensiveTest();
$results = $testSuite->runAllTests();

$allPassed = true;
$count = 0;
foreach ($results as $testName => $passed) {
    $count++;
    $status = $passed ? "[ PASS ]" : "[ FAIL ]";
    echo sprintf("%-65s %s\n", $testName, $status);
    if (!$passed) {
        $allPassed = false;
    }
}

echo "\n--------------------------------------------------------\n";
echo "Total Tests Executed: " . $count . "\n";
echo "Final Result: " . ($allPassed ? "ALL 28 TESTS PASSED SUCCESSFULLY!" : "SOME TESTS FAILED!") . "\n";
echo "--------------------------------------------------------\n";

exit($allPassed ? 0 : 1);
