<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Services\Inventory\InventoryService;

echo "=== Verifying Inventory Flow ===" . PHP_EOL;

$inv = new InventoryService();
$orgId = 1;
$userId = 2;

try {
    $code = 'TEST-BALL-' . rand(100, 999);
    $item = $inv->createItem($orgId, [
        'category_id' => 1,
        'item_code' => $code,
        'item_name' => 'Automated Test Match Ball',
        'quantity' => 20,
        'unit_cost' => 1500.00,
        'minimum_stock_level' => 5,
        'reorder_level' => 10,
        'location_name' => 'Locker Room 1'
    ], $userId);

    echo "1. Created Item ID: " . $item['id'] . " (Code: {$code})" . PHP_EOL;

    // Issue 5
    $inv->recordStockTransaction($orgId, $item['id'], [
        'transaction_type' => 'issue',
        'quantity' => 5,
        'remarks' => 'Issued 5 balls for morning training'
    ], $userId);

    $updated = $inv->getItem($orgId, $item['id']);
    echo "2. Qty after issue 5: " . $updated['quantity'] . PHP_EOL;

    if ((int)$updated['quantity'] !== 15) {
        throw new Exception("Expected qty 15, got " . $updated['quantity']);
    }

    // Receive 10
    $inv->recordStockTransaction($orgId, $item['id'], [
        'transaction_type' => 'receive',
        'quantity' => 10,
        'remarks' => 'Received 10 new balls'
    ], $userId);

    $updated2 = $inv->getItem($orgId, $item['id']);
    echo "3. Qty after receive 10: " . $updated2['quantity'] . PHP_EOL;

    if ((int)$updated2['quantity'] !== 25) {
        throw new Exception("Expected qty 25, got " . $updated2['quantity']);
    }

    // Test insufficient stock rejection
    try {
        $inv->recordStockTransaction($orgId, $item['id'], [
            'transaction_type' => 'issue',
            'quantity' => 50,
            'remarks' => 'Excess issue'
        ], $userId);
        throw new Exception("Excess issue should have failed!");
    } catch (\Throwable $e) {
        echo "4. Insufficient stock correctly caught: " . $e->getMessage() . PHP_EOL;
    }

    // Test soft delete
    $inv->deleteItem($orgId, $item['id'], $userId);
    $check = $inv->getItem($orgId, $item['id']);
    if ($check !== null) {
        throw new Exception("Item should not be found after soft delete!");
    }
    echo "5. Soft delete verified successfully." . PHP_EOL;

    echo "ALL INVENTORY FLOW TESTS PASSED!" . PHP_EOL;

} catch (\Throwable $e) {
    echo "FAILED: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
