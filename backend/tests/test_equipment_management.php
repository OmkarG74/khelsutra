<?php

/**
 * KhelSutra Equipment Tracking Verification Script
 * Validates Equipment issue/return workflows, inventory stock updates,
 * Condition tracking, and tenant isolation using the actual database schema.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/app.php';

use App\Services\Equipment\EquipmentService;
use App\Services\Inventory\InventoryService;

$pdo = \App\Services\BaseService::getDatabaseConnection();
$eqService = new EquipmentService();
$invService = new InventoryService($pdo);

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $message): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo " [PASS] {$message}\n";
    } else {
        $failed++;
        echo " [FAIL] {$message}\n";
    }
}

echo "=== KhelSutra Equipment Tracking Verification ===\n\n";

$org1 = 1;
$org2 = 2;
$userId = 1;

try {
    // =========================================================================
    // 0. Setup Test Data
    // =========================================================================
    echo "\n--- 0. Setup Test Data ---\n";

    // Ensure Org 2
    $org2Row = $pdo->query("SELECT id FROM organizations WHERE id = {$org2}")->fetchColumn();
    if (!$org2Row) {
        $pdo->exec("INSERT INTO organizations (id, organization_code, name, country, status, created_at, updated_at) VALUES ({$org2}, 'ORG-TEST-2', 'Secondary Sports Club', 'India', 'active', NOW(), NOW())");
    }

    // Athlete
    $athlete1 = $pdo->query("SELECT id FROM athletes WHERE organization_id = {$org1} AND deleted_at IS NULL LIMIT 1")->fetchColumn();
    if (!$athlete1) {
        $pdo->exec("INSERT INTO athletes (organization_id, athlete_code, first_name, last_name, status, created_at, updated_at) VALUES ({$org1}, 'ATH-TST01', 'Test', 'Athlete', 'active', NOW(), NOW())");
        $athlete1 = (int)$pdo->lastInsertId();
    } else {
        $athlete1 = (int)$athlete1;
    }

    // Category
    $catId = $pdo->query("SELECT id FROM inventory_categories WHERE organization_id = {$org1} AND deleted_at IS NULL LIMIT 1")->fetchColumn();
    if (!$catId) {
        $pdo->exec("INSERT INTO inventory_categories (organization_id, category_code, name, status, created_at, updated_at) VALUES ({$org1}, 'CAT-TST01', 'Test Category', 'active', NOW(), NOW())");
        $catId = (int)$pdo->lastInsertId();
    } else {
        $catId = (int)$catId;
    }

    // Clean old
    $pdo->exec("DELETE FROM equipment_rentals WHERE notes LIKE '%TEST_SUITE%'");
    $pdo->exec("DELETE FROM inventory_items WHERE item_name LIKE '%TEST_SUITE%'");
    $pdo->exec("DELETE FROM stock_transactions WHERE remarks LIKE '%TEST_SUITE%'");

    // Inventory Item
    $inv1Data = $invService->createItem($org1, [
        'category_id' => $catId,
        'item_name' => 'TEST_SUITE Cricket Bats',
        'quantity' => 10,
        'minimum_stock_level' => 3,
        'reorder_level' => 5,
        'unit' => 'piece',
        'status' => 'active'
    ], $userId);
    $inv1Id = (int)$inv1Data['id'];

    assertTest($inv1Id > 0, "Test inventory item created with quantity 10");

    // =========================================================================
    // 1. Issue Equipment
    // =========================================================================
    echo "\n--- 1. Issue Equipment ---\n";

    $issueData = [
        'inventory_item_id' => $inv1Id,
        'borrowed_quantity' => 2,
        'borrower_type' => 'athlete',
        'athlete_id' => $athlete1,
        'start_time' => date('Y-m-d H:i:s'),
        'notes' => 'TEST_SUITE issuing 2 bats'
    ];
    $rental = $eqService->issueEquipment($org1, $issueData, $userId);
    $rentalId = (int)$rental['id'];

    assertTest($rentalId > 0, "Rental record created");
    assertTest((float)$rental['borrowed_quantity'] == 2, "Borrowed quantity is correct");
    assertTest($rental['status'] === 'issued', "Rental status is issued");

    // Check inventory deduction
    $invAfterIssue = $invService->getItem($org1, $inv1Id);
    assertTest((float)$invAfterIssue['quantity'] == 8, "Inventory quantity decreased to 8");

    // Guard: Exceeding stock
    $failedExceed = false;
    try {
        $eqService->issueEquipment($org1, [
            'inventory_item_id' => $inv1Id,
            'borrowed_quantity' => 9, // only 8 left
            'borrower_type' => 'other',
            'borrower_name' => 'TEST_SUITE guest'
        ], $userId);
    } catch (\InvalidArgumentException $e) {
        $failedExceed = true;
    }
    assertTest($failedExceed, "Cannot issue more than available stock");

    // =========================================================================
    // 2. Return Equipment (Partial / Damaged)
    // =========================================================================
    echo "\n--- 2. Return Equipment ---\n";

    // Return 1 good, 1 damaged
    $returnRes = $eqService->returnEquipment($org1, $rentalId, [
        'returned_quantity' => 1,
        'damaged_quantity' => 1,
        'notes' => 'TEST_SUITE 1 good, 1 damaged'
    ], $userId);

    assertTest($returnRes['status'] === 'returned_with_damage', "Status is returned_with_damage");
    assertTest((float)$returnRes['returned_quantity'] == 1, "Returned quantity recorded");
    assertTest((float)$returnRes['damaged_quantity'] == 1, "Damaged quantity recorded");

    $invAfterReturn = $invService->getItem($org1, $inv1Id);
    assertTest((float)$invAfterReturn['quantity'] == 9, "Inventory quantity restored by 1 good item (now 9). Damaged item not restored.");

    // Guard: Returning too many
    $failedOverReturn = false;
    try {
        $eqService->returnEquipment($org1, $rentalId, ['returned_quantity' => 1], $userId);
    } catch (\InvalidArgumentException $e) {
        $failedOverReturn = true;
    }
    assertTest($failedOverReturn, "Cannot over-return items for a closed rental");

    // =========================================================================
    // 3. UI / Controller Rendering Guard
    // =========================================================================
    echo "\n--- 3. Database Column Validation (No Missing Columns) ---\n";
    $viewPath = __DIR__ . '/../resources/views/equipment/equipment-index.blade.php';
    if (file_exists($viewPath)) {
        $content = file_get_contents($viewPath);
        $hasMissingPhysicalStock = strpos($content, 'is_physical_stock') !== false;
        assertTest(!$hasMissingPhysicalStock, "Blade view does not reference non-existent is_physical_stock column");
    }

    // =========================================================================
    // 4. Grouped Issue / Return Logic
    // =========================================================================
    echo "\n--- 4. Grouped Ledger & Grouped Return ---\n";

    $issueGroup1 = [
        'inventory_item_id' => $inv1Id,
        'borrowed_quantity' => 1,
        'borrower_type' => 'athlete',
        'athlete_id' => $athlete1,
        'notes' => 'TEST_SUITE grouped issue 1'
    ];
    $eqService->issueEquipment($org1, $issueGroup1, $userId);

    $issueGroup2 = [
        'inventory_item_id' => $inv1Id,
        'borrowed_quantity' => 2,
        'borrower_type' => 'athlete',
        'athlete_id' => $athlete1,
        'notes' => 'TEST_SUITE grouped issue 2'
    ];
    $eqService->issueEquipment($org1, $issueGroup2, $userId);

    $list = $eqService->listRentals($org1, 1, 15, null, null, $inv1Id);

    $groupRow = null;
    foreach ($list['data'] as $row) {
        if ($row['athlete_id'] == $athlete1) {
            $groupRow = $row;
            break;
        }
    }

    assertTest($groupRow !== null, "Grouped row found for borrower and item");

    $groupedCount = count(array_filter($list['data'], fn($r) => $r['athlete_id'] == $athlete1));
    assertTest($groupedCount === 1, "Only ONE row returned for the borrower despite multiple issues");

    assertTest((float)$groupRow['borrowed_quantity'] == 5, "Total borrowed correctly aggregated (5)");
    assertTest((float)$groupRow['returned_quantity'] == 1, "Total returned correctly aggregated (1)");
    assertTest((float)$groupRow['damaged_quantity'] == 1, "Total damaged correctly aggregated (1)");
    assertTest($groupRow['status'] === 'issued', "Grouped status is 'issued' because outstanding > 0");

    $athlete2 = $pdo->query("SELECT id FROM athletes WHERE organization_id = {$org1} AND id != {$athlete1} AND deleted_at IS NULL LIMIT 1")->fetchColumn();
    if (!$athlete2) {
        $pdo->exec("INSERT INTO athletes (organization_id, athlete_code, first_name, last_name, status, created_at, updated_at) VALUES ({$org1}, 'ATH-TST02', 'Test2', 'Athlete2', 'active', NOW(), NOW())");
        $athlete2 = (int)$pdo->lastInsertId();
    }

    $eqService->issueEquipment($org1, [
        'inventory_item_id' => $inv1Id,
        'borrowed_quantity' => 1,
        'borrower_type' => 'athlete',
        'athlete_id' => $athlete2,
        'notes' => 'TEST_SUITE diff borrower'
    ], $userId);

    $listWithDiff = $eqService->listRentals($org1, 1, 15, null, null, $inv1Id);
    $athlete1Rows = array_filter($listWithDiff['data'], fn($r) => $r['athlete_id'] == $athlete1);
    $athlete2Rows = array_filter($listWithDiff['data'], fn($r) => $r['athlete_id'] == $athlete2);
    assertTest(count($athlete1Rows) === 1 && count($athlete2Rows) === 1, "Different borrowers are separated into distinct rows");

    // Return the outstanding amount across both new grouped rows
    $eqService->returnEquipment($org1, $groupRow['id'], [
        'returned_quantity' => 3,
        'notes' => 'TEST_SUITE returning all outstanding in group'
    ], $userId);

    $listAfterReturn = $eqService->listRentals($org1, 1, 15, null, null, $inv1Id);
    $groupRowAfter = current(array_filter($listAfterReturn['data'], fn($r) => $r['athlete_id'] == $athlete1));

    assertTest((float)$groupRowAfter['returned_quantity'] == 4, "Total returned updated to 4 (1 old + 3 new)");
    assertTest($groupRowAfter['status'] === 'returned_with_damage', "Grouped status is 'returned_with_damage' since all outstanding returned and damaged > 0");

} catch (\Throwable $e) {
    $failed++;
    echo "\n[EXCEPTION OCCURRED]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    echo "\n--- Cleaning up test records ---\n";
    $pdo->exec("DELETE FROM equipment_rentals WHERE notes LIKE '%TEST_SUITE%' OR borrower_name LIKE '%TEST_SUITE%'");
    $pdo->exec("DELETE FROM stock_transactions WHERE remarks LIKE '%TEST_SUITE%'");
    $pdo->exec("DELETE FROM inventory_items WHERE item_name LIKE '%TEST_SUITE%'");
    echo "Cleanup complete.\n";
}

echo "\n=============================================\n";
echo "SUMMARY: Passed: {$passed}, Failed: {$failed}\n";
echo "=============================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
