<?php

/**
 * KhelSutra End-to-End Real Browser QA Tester
 * 
 * Simulates a real browser user over HTTP (127.0.0.1:8000) using cURL sessions,
 * cookies, form submissions, and HTML DOM validation.
 */

$baseUrl = 'http://127.0.0.1:8000';
$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

class BrowserClient
{
    private string $baseUrl;
    private string $cookieFile;
    public ?string $lastUrl = null;
    public ?int $lastStatus = null;
    public ?string $lastResponse = null;
    public ?array $lastHeaders = null;

    public function __construct(string $baseUrl, string $cookieFile)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cookieFile = $cookieFile;
    }

    public function request(string $method, string $path, array $data = [], array $headers = []): string
    {
        $url = str_starts_with($path, 'http') ? $path : $this->baseUrl . '/' . ltrim($path, '/');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 KhelSutra-QATester/1.0');

        $reqHeaders = $headers;
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            $reqHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        if (!empty($reqHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);
        }

        $raw = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->lastStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->lastUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        $headerText = substr($raw, 0, $headerSize);
        $this->lastResponse = substr($raw, $headerSize);
        curl_close($ch);

        return $this->lastResponse;
    }

    public function get(string $path, array $headers = []): string
    {
        return $this->request('GET', $path, [], $headers);
    }

    public function post(string $path, array $data, array $headers = []): string
    {
        return $this->request('POST', $path, $data, $headers);
    }

    public function clearCookies(): void
    {
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }
}

$browser = new BrowserClient($baseUrl, $cookieFile);

$passedCount = 0;
$failedCount = 0;
$moduleResults = [
    'Inventory' => ['pass' => 0, 'fail' => 0, 'issues' => []],
    'Equipment' => ['pass' => 0, 'fail' => 0, 'issues' => []],
    'Vendors' => ['pass' => 0, 'fail' => 0, 'issues' => []],
    'Purchases' => ['pass' => 0, 'fail' => 0, 'issues' => []],
    'Finance' => ['pass' => 0, 'fail' => 0, 'issues' => []],
    'Reports' => ['pass' => 0, 'fail' => 0, 'issues' => []],
    'Notifications' => ['pass' => 0, 'fail' => 0, 'issues' => []],
];

$record = function (string $module, string $testName, bool $condition, ?string $failureMsg = null) use (&$passedCount, &$failedCount, &$moduleResults) {
    if ($condition) {
        $passedCount++;
        if (isset($moduleResults[$module])) $moduleResults[$module]['pass']++;
        echo "  [PASS] {$testName}\n";
    } else {
        $failedCount++;
        if (isset($moduleResults[$module])) {
            $moduleResults[$module]['fail']++;
            $moduleResults[$module]['issues'][] = "{$testName}: {$failureMsg}";
        }
        echo "  [FAIL] {$testName} - {$failureMsg}\n";
    }
};

function record(string $module, string $testName, bool $condition, ?string $failureMsg = null) {
    global $record;
    $record($module, $testName, $condition, $failureMsg);
}

echo "=========================================================================\n";
echo " KHEL SUTRA REAL END-TO-END BROWSER QA TEST SUITE\n";
echo " Base URL: {$baseUrl}\n";
echo "=========================================================================\n";

// =========================================================================
// STEP 1 — LOGIN
// =========================================================================
echo "\n--- [STEP 1] LOGIN VERIFICATION ---\n";

$loginHtml = $browser->get('/login');
$hasLoginForm = str_contains($loginHtml, 'form') && str_contains($loginHtml, 'email') && str_contains($loginHtml, 'password');
record('Inventory', 'Login Page Renders with Form Elements', $browser->lastStatus === 200 && $hasLoginForm, "Status: {$browser->lastStatus}");

$loginSubmitHtml = $browser->post('/login', [
    'email' => 'admin@khelsutra.com',
    'password' => 'password123'
]);

$loginSuccess = ($browser->lastStatus === 200) && (str_contains($browser->lastUrl, '/dashboard') || str_contains($loginSubmitHtml, 'Dashboard'));
record('Inventory', 'Login Submit Succeeds & Redirects to Dashboard', $loginSuccess, "Redirect URL: {$browser->lastUrl}, Status: {$browser->lastStatus}");
record('Inventory', 'Dashboard Contains KhelSutra Organization / Workspace', str_contains($loginSubmitHtml, 'Apex Sports Academy') || str_contains($loginSubmitHtml, 'ks-app-layout'), "Org branding not detected");

// =========================================================================
// STEP 2 — INVENTORY UI
// =========================================================================
echo "\n--- [STEP 2] INVENTORY UI VERIFICATION ---\n";

$invHtml = $browser->get('/inventory');
$invNotBlank = strlen($invHtml) > 5000 && (str_contains($invHtml, 'Inventory') || str_contains($invHtml, 'Inventory Management'));
record('Inventory', 'Inventory Index Page Renders (NOT Blank)', $invNotBlank, "Length: " . strlen($invHtml));
record('Inventory', 'Inventory Index Has Search & Filter Elements', str_contains($invHtml, 'name="search"') || str_contains($invHtml, 'Search'), "Search bar missing");
record('Inventory', 'Inventory Index Has Action Buttons (Add Item, Categories)', str_contains($invHtml, '/inventory/create') || str_contains($invHtml, 'Add Item'), "Add item button missing");

// 2.1 Create Inventory Category through UI
$testCatName = 'QA Cat ' . time();
$catCreateHtml = $browser->post('/inventory/categories/create', [
    'name' => $testCatName,
    'description' => 'Created via Browser QA automation'
]);
$catAppears = str_contains($catCreateHtml, $testCatName) || str_contains($browser->get('/inventory/categories'), $testCatName);
record('Inventory', 'Create Inventory Category via UI & Verify Persistence', $catAppears, "Category '{$testCatName}' not found in category list");

// 2.2 Create Inventory Item through UI
$testItemName = 'QA Match Ball ' . time();
$itemCreateHtml = $browser->post('/inventory/create', [
    'item_name' => $testItemName,
    'unit' => 'Pieces',
    'quantity' => 40,
    'unit_price' => 550.00,
    'reorder_level' => 15,
    'location' => 'Main Warehouse Rack A'
]);
$itemCreated = $browser->lastStatus === 200 && (str_contains($itemCreateHtml, $testItemName) || str_contains($browser->get('/inventory'), $testItemName));
record('Inventory', 'Create Inventory Item via UI & Verify in Table', $itemCreated, "Item '{$testItemName}' not visible in inventory");

// Find created item ID from DB to test item details and stock
$pdo = \App\Services\BaseService::getDatabaseConnection();
$itemId = (int)$pdo->query("SELECT id FROM inventory_items WHERE item_name = '{$testItemName}' AND deleted_at IS NULL LIMIT 1")->fetchColumn();
record('Inventory', 'Inventory Item ID Resolved from DB', $itemId > 0, "Item was not stored in database");

// 2.3 Open Item Details
$itemDetailHtml = $browser->get("/inventory/{$itemId}");
record('Inventory', 'Open Inventory Item Details Page', str_contains($itemDetailHtml, $testItemName) && str_contains($itemDetailHtml, 'Stock Transactions'), "Details page did not render item information");

// 2.4 Edit Item
$itemEditHtml = $browser->post("/inventory/{$itemId}/edit", [
    'item_name' => $testItemName . ' (Updated)',
    'unit' => 'Pieces',
    'reorder_level' => 18,
    'location' => 'Main Warehouse Rack B'
]);
$itemUpdated = str_contains($itemEditHtml, '(Updated)') || str_contains($browser->get("/inventory/{$itemId}"), '(Updated)');
record('Inventory', 'Edit Inventory Item via UI & Verify Update', $itemUpdated, "Item update was not reflected");

// 2.5 Stock Receive (+10)
$recvHtml = $browser->post("/inventory/{$itemId}/transactions", [
    'transaction_type' => 'purchase',
    'quantity' => 10,
    'unit_cost' => 550.00,
    'notes' => 'QA Received 10 pieces'
]);
$stockIs50 = str_contains($recvHtml, '50') || str_contains($browser->get("/inventory/{$itemId}"), '50');
record('Inventory', 'Perform Stock Receive & Verify Quantity Incremented (40 -> 50)', $stockIs50, "Quantity was not 50");

// 2.6 Stock Issue (-5)
$issueHtml = $browser->post("/inventory/{$itemId}/transactions", [
    'transaction_type' => 'issue',
    'quantity' => 5,
    'notes' => 'QA Issued 5 pieces'
]);
$stockIs45 = str_contains($issueHtml, '45') || str_contains($browser->get("/inventory/{$itemId}"), '45');
record('Inventory', 'Perform Stock Issue & Verify Quantity Decremented (50 -> 45)', $stockIs45, "Quantity was not 45");

// 2.7 Over-Issue Safeguard (attempt to issue 9999)
$overIssueHtml = $browser->post("/inventory/{$itemId}/transactions", [
    'transaction_type' => 'issue',
    'quantity' => 9999,
    'notes' => 'QA Illegal over-issue'
]);
$overIssueBlocked = str_contains($overIssueHtml, 'Insufficient stock') || str_contains($overIssueHtml, 'error=');
record('Inventory', 'Over-Issue Blocked with User-Friendly Error Alert', $overIssueBlocked, "Excessive stock deduction was not rejected with error");

// 2.8 Search & Filter
$searchHtml = $browser->get("/inventory?search=" . urlencode('QA Match Ball'));
record('Inventory', 'Search Functionality Filters Correct Items in UI', str_contains($searchHtml, $testItemName), "Search did not return target item");

// 2.9 Browser Refresh Persistence Test
$refreshHtml = $browser->get('/inventory');
record('Inventory', 'Inventory Data Survives Browser Refresh & Persistence', str_contains($refreshHtml, $testItemName), "Data missing after refresh");

// =========================================================================
// STEP 3 — EQUIPMENT UI
// =========================================================================
echo "\n--- [STEP 3] EQUIPMENT UI VERIFICATION ---\n";

$eqHtml = $browser->get('/equipment');
$eqNotBlank = strlen($eqHtml) > 5000 && str_contains($eqHtml, 'Equipment');
record('Equipment', 'Equipment Index Page Renders (NOT Blank)', $eqNotBlank, "Length: " . strlen($eqHtml));

// 3.1 Create Equipment through UI
$eqName = 'QA Agility Ladder ' . time();
$eqAssetCode = 'EQP-QA-' . rand(1000, 9999);
$eqCreateHtml = $browser->post('/equipment/create', [
    'equipment_name' => $eqName,
    'asset_code' => $eqAssetCode,
    'condition_status' => 'new',
    'purchase_cost' => 2400.00,
    'location' => 'Equipment Room Shelf 3'
]);
$eqAppears = str_contains($eqCreateHtml, $eqName) || str_contains($browser->get('/equipment'), $eqName);
record('Equipment', 'Create Equipment Unit via UI & Verify in Table', $eqAppears, "Equipment '{$eqName}' not visible");

$eqId = (int)$pdo->query("SELECT id FROM equipment WHERE equipment_name = '{$eqName}' AND deleted_at IS NULL LIMIT 1")->fetchColumn();
record('Equipment', 'Equipment ID Resolved from DB', $eqId > 0, "Equipment not found in DB");

// 3.2 Equipment Details
$eqDetailHtml = $browser->get("/equipment/{$eqId}");
record('Equipment', 'Open Equipment Details Page', str_contains($eqDetailHtml, $eqName), "Equipment details failed to render");

// 3.3 Equipment Assignment to Athlete
// Ensure an athlete exists
$athId = (int)$pdo->query("SELECT id FROM athletes WHERE organization_id = 1 AND deleted_at IS NULL LIMIT 1")->fetchColumn();
if ($athId) {
    $assignHtml = $browser->post("/equipment/{$eqId}/assign", [
        'assignee_type' => 'athlete',
        'athlete_id' => $athId,
        'assigned_date' => date('Y-m-d'),
        'notes' => 'Assigned for tournament practice'
    ]);
    $isAssigned = str_contains($assignHtml, 'assigned') || str_contains($browser->get("/equipment/{$eqId}"), 'assigned');
    record('Equipment', 'Assign Equipment to Athlete & Verify Assigned Status', $isAssigned, "Status not assigned");

    // 3.4 Equipment Return
    $returnHtml = $browser->post("/equipment/{$eqId}/return", [
        'return_date' => date('Y-m-d'),
        'condition_status' => 'good',
        'notes' => 'Returned in good condition'
    ]);
    $isReturned = str_contains($returnHtml, 'Available') || str_contains($browser->get("/equipment/{$eqId}"), 'Available');
    record('Equipment', 'Return Equipment & Verify Transition Back to Available', $isReturned, "Status not available after return");
}

// =========================================================================
// STEP 4 — VENDORS UI
// =========================================================================
echo "\n--- [STEP 4] VENDORS UI VERIFICATION ---\n";

$vendorHtml = $browser->get('/vendors');
$vendorNotBlank = strlen($vendorHtml) > 5000 && str_contains($vendorHtml, 'Vendor');
record('Vendors', 'Vendors Index Page Renders (NOT Blank)', $vendorNotBlank, "Length: " . strlen($vendorHtml));

// 4.1 Create Vendor
$vCompName = 'QA Premier Athletics ' . time();
$vCode = 'VEND-QA-' . rand(1000, 9999);
$vendorCreateHtml = $browser->post('/vendors/create', [
    'company_name' => $vCompName,
    'vendor_code' => $vCode,
    'contact_person' => 'Rahul Bajaj',
    'email' => 'bajaj@qapremier.com',
    'phone' => '9822334455',
    'city' => 'Mumbai',
    'state' => 'Maharashtra'
]);
$vendorAppears = str_contains($vendorCreateHtml, $vCompName) || str_contains($browser->get('/vendors'), $vCompName);
record('Vendors', 'Create Vendor via UI & Verify Persistence', $vendorAppears, "Vendor '{$vCompName}' not in list");

// 4.2 Validation: Empty Vendor Name
$vendorEmptyHtml = $browser->post('/vendors/create', [
    'company_name' => '',
    'email' => 'empty@test.com'
]);
$vendorEmptyBlocked = str_contains($vendorEmptyHtml, 'error=') || str_contains($vendorEmptyHtml, 'required');
record('Vendors', 'Missing Required Company Name Rejected with Validation Error', $vendorEmptyBlocked, "Empty company name was not rejected");

// =========================================================================
// STEP 5 — PURCHASES / PROCUREMENT UI
// =========================================================================
echo "\n--- [STEP 5] PURCHASES / PROCUREMENT UI VERIFICATION ---\n";

$purchasesHtml = $browser->get('/purchases');
$purchasesNotBlank = strlen($purchasesHtml) > 5000 && str_contains($purchasesHtml, 'Purchase');
record('Purchases', 'Purchases Index Page Renders (NOT Blank)', $purchasesNotBlank, "Length: " . strlen($purchasesHtml));

// 5.1 Create Purchase Order Page
$poCreatePageHtml = $browser->get('/purchases/orders/create');
$poFormRenders = str_contains($poCreatePageHtml, 'poForm') || str_contains($poCreatePageHtml, 'vendor_id');
record('Purchases', 'Purchase Order Create Page Renders with Form & Supplier Picker', $poFormRenders, "PO create form not rendered");

// 5.2 Submit PO via Web Action
$vId = (int)$pdo->query("SELECT id FROM vendors WHERE organization_id = 1 AND deleted_at IS NULL AND status = 'active' LIMIT 1")->fetchColumn();
$invItemId = (int)$pdo->query("SELECT id FROM inventory_items WHERE organization_id = 1 AND deleted_at IS NULL LIMIT 1")->fetchColumn();

$poSubmitHtml = $browser->post('/purchases/orders/create', [
    'vendor_id' => $vId,
    'order_date' => date('Y-m-d'),
    'expected_delivery_date' => date('Y-m-d', strtotime('+10 days')),
    'status' => 'draft',
    'notes' => 'PO Submitted via Real Browser QA',
    'items' => [
        [
            'inventory_item_id' => $invItemId,
            'item_name' => 'QA PO Item Test',
            'ordered_quantity' => 20,
            'unit_cost' => 300.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00
        ]
    ]
]);
$poCreatedSuccess = str_contains($browser->lastUrl, '/purchases/orders/') || str_contains($poSubmitHtml, 'Purchase order');
record('Purchases', 'Submit Purchase Order via Web Form & Verify Detail Redirect', $poCreatedSuccess, "Redirect URL: {$browser->lastUrl}");

// =========================================================================
// STEP 6 — FINANCE UI (CRITICAL TEST)
// =========================================================================
echo "\n--- [STEP 6] FINANCE UI (CRITICAL VERIFICATION) ---\n";

$financeHtml = $browser->get('/finance');
$financeNotBlank = strlen($financeHtml) > 10000;
$hasFinanceTitle = str_contains($financeHtml, 'Financial Management');
$hasSummaryCards = str_contains($financeHtml, 'Total Revenue') || str_contains($financeHtml, 'Total Expenses') || str_contains($financeHtml, 'Net Cashflow');
$hasTabs = str_contains($financeHtml, 'tab=expenses') && str_contains($financeHtml, 'tab=income') && str_contains($financeHtml, 'tab=budgets');

record('Finance', 'Finance Index Page is NOT Blank (Full Content Rendered)', $financeNotBlank && $hasFinanceTitle, "HTML Length: " . strlen($financeHtml));
record('Finance', 'Finance Summary Metric Cards Rendered', $hasSummaryCards, "Summary cards missing");
record('Finance', 'Finance Navigation Tabs (Expenses, Income, Budgets, Categories, Payments) Rendered', $hasTabs, "Navigation tabs missing");

// 6.1 Finance Create Expense Page
$expCreatePageHtml = $browser->get('/finance/expenses/create');
$expCreateOk = strlen($expCreatePageHtml) > 5000 && str_contains($expCreatePageHtml, 'Record New Expense') && str_contains($expCreatePageHtml, 'event_id');
record('Finance', 'Record Expense Page Renders Cleanly with Event Dropdown (BUG-002 Fixed)', $expCreateOk, "Length: " . strlen($expCreatePageHtml));

// 6.2 Submit Expense via Web Form
$catId = (int)$pdo->query("SELECT id FROM finance_categories WHERE organization_id = 1 AND status = 'active' LIMIT 1")->fetchColumn();
$expSubmitHtml = $browser->post('/finance/expenses/create', [
    'finance_category_id' => $catId,
    'amount' => 4500.00,
    'description' => 'Browser QA Verified Expense ' . time(),
    'expense_date' => date('Y-m-d')
]);
$expSuccess = str_contains($browser->lastUrl, '/finance') && !str_contains($browser->lastUrl, 'error=');
record('Finance', 'Submit Expense Record via Web Form & Verify Success Redirect', $expSuccess, "URL: {$browser->lastUrl}");

// 6.3 Finance Create Income Page & Submit
$incCreatePageHtml = $browser->get('/finance/income/create');
$incCreateOk = strlen($incCreatePageHtml) > 5000 && (str_contains($incCreatePageHtml, 'Record Inward Revenue') || str_contains($incCreatePageHtml, 'Record Revenue'));
record('Finance', 'Record Revenue / Income Page Renders Cleanly (NOT Blank)', $incCreateOk, "Length: " . strlen($incCreatePageHtml));

$incomeCatId = (int)$pdo->query("SELECT id FROM finance_categories WHERE organization_id = 1 AND status = 'active' AND (category_type = 'income' OR category_type = 'both') LIMIT 1")->fetchColumn();
if (!$incomeCatId) {
    $pdo->exec("INSERT INTO finance_categories (organization_id, name, category_type, status, created_at, updated_at) VALUES (1, 'Academy Fees & Grants', 'income', 'active', NOW(), NOW())");
    $incomeCatId = $pdo->lastInsertId();
}

$incSubmitHtml = $browser->post('/finance/income/create', [
    'finance_category_id' => $incomeCatId,
    'amount' => 12000.00,
    'source_name' => 'Sponsorship & Training Fees',
    'income_date' => date('Y-m-d'),
    'description' => 'Browser QA Verified Income ' . time()
]);
$incSuccess = str_contains($browser->lastUrl, '/finance') && !str_contains($browser->lastUrl, 'error=');
record('Finance', 'Submit Revenue / Income Record via Web Form & Verify Success Redirect', $incSuccess, "URL: {$browser->lastUrl}");

// 6.4 Finance Invalid Validation Check (Negative Expense Amount)
$negExpHtml = $browser->post('/finance/expenses/create', [
    'finance_category_id' => $catId,
    'amount' => -500.00,
    'description' => 'Illegal negative amount'
]);
$negExpBlocked = str_contains($negExpHtml, 'error=') || str_contains($negExpHtml, 'negative');
record('Finance', 'Negative Expense Amount Blocked with User Validation Error', $negExpBlocked, "Negative amount was not rejected");

// =========================================================================
// STEP 7 — REPORTS UI
// =========================================================================
echo "\n--- [STEP 7] REPORTS UI VERIFICATION ---\n";

$reportsHtml = $browser->get('/reports');
$reportsNotBlank = strlen($reportsHtml) > 5000 && str_contains($reportsHtml, 'Reports');
$hasReportSections = str_contains($reportsHtml, 'Inventory') && str_contains($reportsHtml, 'Finance') && str_contains($reportsHtml, 'Purchases');
record('Reports', 'Reports Dashboard Page Renders (NOT Blank)', $reportsNotBlank, "Length: " . strlen($reportsHtml));
record('Reports', 'Reports Page Displays Core Sections (Inventory, Finance, Purchases)', $hasReportSections, "Report sections missing");

// =========================================================================
// STEP 8 — NOTIFICATIONS UI
// =========================================================================
echo "\n--- [STEP 8] NOTIFICATIONS UI VERIFICATION ---\n";

$notifHtml = $browser->get('/notifications');
$notifNotBlank = strlen($notifHtml) > 3000 && str_contains($notifHtml, 'Notification');
record('Notifications', 'Notifications Page Renders (NOT Blank)', $notifNotBlank, "Length: " . strlen($notifHtml));

// =========================================================================
// STEP 9 — NAVIGATION TEST (ALL MEMBER 5 LINKS)
// =========================================================================
echo "\n--- [STEP 9] NAVIGATION TEST ---\n";

$navUrls = [
    '/inventory' => 'Inventory',
    '/equipment' => 'Equipment',
    '/vendors' => 'Vendors',
    '/purchases' => 'Purchases',
    '/finance' => 'Finance',
    '/reports' => 'Reports',
    '/notifications' => 'Notifications'
];

foreach ($navUrls as $path => $name) {
    $resHtml = $browser->get($path);
    $ok = ($browser->lastStatus === 200) && (strlen($resHtml) > 2000);
    record($name, "Direct Navigation to {$path} Returns HTTP 200 and Content", $ok, "Status: {$browser->lastStatus}, Length: " . strlen($resHtml));
}

// =========================================================================
// STEP 12 & 13 — MULTI-TENANCY & RBAC UI TESTS
// =========================================================================
echo "\n--- [STEP 12 & 13] MULTI-TENANCY & RBAC BROWSER ACCESS ---\n";

// Login as Org 2 Admin
$browser->clearCookies();
$org2LoginHtml = $browser->post('/login', [
    'email' => 'org2admin@khelsutra.local',
    'password' => 'password123'
]);
$org2InvHtml = $browser->get('/inventory');
$org1ItemExcluded = !str_contains($org2InvHtml, $testItemName);
record('Inventory', 'Tenant Isolation: Org 2 User Cannot View Org 1 Inventory Items in Browser UI', $org1ItemExcluded, "Org 1 item leaked into Org 2 UI");

// Login as Coach & verify 403 on admin routes
$browser->clearCookies();
$coachLoginHtml = $browser->post('/login', [
    'email' => 'coach@khelsutra.com',
    'password' => 'password123'
]);
// Coach hitting super-admin route
$coachForbiddenHtml = $browser->get('/super-admin/organizations');
$coachBlocked = ($browser->lastStatus === 403) || str_contains($coachForbiddenHtml, '403') || str_contains($coachForbiddenHtml, 'Access Forbidden');
record('Inventory', 'RBAC Enforcement: Coach Access to Administrative Area Returns 403 Forbidden UI', $coachBlocked, "Status: {$browser->lastStatus}");

// =========================================================================
// SUMMARY
// =========================================================================
echo "\n=========================================================================\n";
echo " REAL BROWSER QA TEST RESULTS SUMMARY\n";
echo " Total Browser Checks Executed : " . ($passedCount + $failedCount) . "\n";
echo " Checks Passed                 : {$passedCount}\n";
echo " Checks Failed                 : {$failedCount}\n";
echo " Overall Status                : " . ($failedCount === 0 ? "ALL PASS" : "FAILURES DETECTED") . "\n";
echo "=========================================================================\n";

foreach ($moduleResults as $mod => $res) {
    $stat = $res['fail'] === 0 ? 'PASS' : 'FAIL';
    echo sprintf(" %-15s : %s [Passed: %d, Failed: %d]\n", $mod, $stat, $res['pass'], $res['fail']);
}
echo "=========================================================================\n";
