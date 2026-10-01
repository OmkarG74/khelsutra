<?php
$activePage = 'reports';
$title = 'Reports & Analytics — KhelSutra';

$reportService = new \App\Services\Report\ReportService();
$orgId = $_SESSION['current_organization_id'] ?? 1;
$currentRole = $_SESSION['auth']['role']['slug'] ?? 'sports_admin';

// Role-aware default tab: Inventory Manager defaults to 'inventory'
$defaultTab = ($currentRole === 'inventory_manager') ? 'inventory' : 'operational';
$tab = $_GET['tab'] ?? $defaultTab;

$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$status = $_GET['status'] ?? '';
$vendorId = !empty($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : null;
$search = $_GET['search'] ?? '';

$filters = [
    'date_from' => $dateFrom ?: null,
    'date_to' => $dateTo ?: null,
    'category_id' => $categoryId,
    'status' => $status ?: null,
    'vendor_id' => $vendorId,
    'search' => $search ?: null,
];

// Preserved Operational Reports
$reports = $reportService->getOperationalReports($orgId);
$athletesBySport = $reports['athletes_by_sport'] ?? [];
$teamsBySport = $reports['teams_by_sport'] ?? [];
$attendance = $reports['attendance_stats'] ?? [];
$tournaments = $reports['tournament_activity'] ?? [];
$venues = $reports['venue_utilization'] ?? [];
$leave = $reports['leave_stats'] ?? [];
$inventory = $reports['inventory_stats'] ?? [];
$payroll = $reports['payroll_stats'] ?? [];

// Member 5 Reports data loading based on tab
$invSummary = null;
$invValuation = null;
$lowStock = null;
$stockMovements = null;

$purchSummary = null;
$purchOrders = null;
$goodsReceipts = null;
$procSpend = null;

$vendorSpend = null;
$vendorInvoices = null;

$equipSummary = null;
$assignedEquip = null;
$returnedEquip = null;
$overdueEquip = null;
$conditionReport = null;

$finSummary = null;
$incomeSummary = null;
$expenseSummary = null;
$incomeVsExpense = null;
$budgetVsActual = null;

if ($tab === 'inventory') {
    $invSummary = $reportService->getInventorySummary($orgId, $filters);
    $invValuation = $reportService->getInventoryValuation($orgId, $filters);
    $lowStock = $reportService->getLowStockReport($orgId, $filters);
    $stockMovements = $reportService->getStockMovementReport($orgId, array_merge($filters, ['limit' => 25]));
} elseif ($tab === 'purchases') {
    $purchSummary = $reportService->getPurchaseSummary($orgId, $filters);
    $purchOrders = $reportService->getPurchaseRequestOrderStatusReport($orgId, $filters);
    $goodsReceipts = $reportService->getGoodsReceivedReport($orgId, $filters);
    $procSpend = $reportService->getProcurementVendorSpendReport($orgId, $filters);
} elseif ($tab === 'vendors') {
    $vendorSpend = $reportService->getVendorPurchaseSpendReport($orgId, $filters);
    $vendorInvoices = $reportService->getVendorInvoicePaymentReport($orgId, $filters);
} elseif ($tab === 'equipment') {
    $equipSummary = $reportService->getEquipmentInventorySummary($orgId, $filters);
    $assignedEquip = $reportService->getAssignedEquipmentReport($orgId, $filters);
    $returnedEquip = $reportService->getReturnedEquipmentReport($orgId, $filters);
    $overdueEquip = $reportService->getOverdueEquipmentReport($orgId, $filters);
    $conditionReport = $reportService->getEquipmentConditionSummary($orgId, $filters);
} elseif ($tab === 'finance') {
    $finSummary = $reportService->getFinanceSummary($orgId, $filters);
    $incomeSummary = $reportService->getIncomeSummary($orgId, $filters);
    $expenseSummary = $reportService->getExpenseSummary($orgId, $filters);
    $incomeVsExpense = $reportService->getIncomeVsExpenseReport($orgId, $filters);
    $budgetVsActual = $reportService->getBudgetVsActualReport($orgId, $filters);
}

// Order of tabs customized to role priorities
if ($currentRole === 'inventory_manager') {
    $tabDefinitions = [
        'inventory' => ['label' => 'Inventory & Stock', 'icon' => 'bi-boxes'],
        'purchases' => ['label' => 'Procurement & Purchases', 'icon' => 'bi-cart-check'],
        'vendors' => ['label' => 'Vendor Intelligence', 'icon' => 'bi-building'],
        'equipment' => ['label' => 'Equipment & Assets', 'icon' => 'bi-tools'],
        'operational' => ['label' => 'Operational Overview', 'icon' => 'bi-speedometer2'],
        'finance' => ['label' => 'Financial Reports', 'icon' => 'bi-cash-stack'],
    ];
} else {
    $tabDefinitions = [
        'operational' => ['label' => 'Operational Overview', 'icon' => 'bi-speedometer2'],
        'inventory' => ['label' => 'Inventory & Stock', 'icon' => 'bi-boxes'],
        'purchases' => ['label' => 'Procurement & Purchases', 'icon' => 'bi-cart-check'],
        'vendors' => ['label' => 'Vendor Intelligence', 'icon' => 'bi-building'],
        'equipment' => ['label' => 'Equipment & Assets', 'icon' => 'bi-tools'],
        'finance' => ['label' => 'Financial Reports', 'icon' => 'bi-cash-stack'],
    ];
}

ob_start();
?>

<!-- Page Header -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Reports & Analytics</h1>
        <p class="ks-page-subtitle">Comprehensive operational, inventory, procurement, and financial intelligence.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i>
            <span>Print Report</span>
        </button>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav ks-nav-tabs mb-4">
    <?php foreach ($tabDefinitions as $key => $tabDef): ?>
        <li class="nav-item">
            <a class="nav-link <?= $tab === $key ? 'active' : '' ?>" href="/reports?tab=<?= $key ?>">
                <i class="bi <?= $tabDef['icon'] ?> me-1"></i> <?= htmlspecialchars($tabDef['label']) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($tab !== 'operational'): ?>
<!-- Filter Toolbar -->
<div class="ks-filter-bar mb-4">
    <form method="GET" action="/reports" class="row g-2 align-items-center">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <div class="col-md-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar"></i></span>
                <input type="date" name="date_from" class="form-control ks-form-control border-start-0" value="<?= htmlspecialchars($dateFrom) ?>" title="Date From">
            </div>
        </div>
        <div class="col-md-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar-event"></i></span>
                <input type="date" name="date_to" class="form-control ks-form-control border-start-0" value="<?= htmlspecialchars($dateTo) ?>" title="Date To">
            </div>
        </div>
        <?php if (in_array($tab, ['inventory', 'purchases', 'vendors', 'finance'])): ?>
        <div class="col-md-3">
            <select name="status" class="form-select ks-form-select">
                <option value="">All Statuses</option>
                <?php if ($tab === 'inventory'): ?>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                <?php elseif ($tab === 'purchases'): ?>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>Sent</option>
                    <option value="partial" <?= $status === 'partial' ? 'selected' : '' ?>>Partial</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                <?php elseif ($tab === 'vendors'): ?>
                    <option value="unpaid" <?= $status === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                    <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="partial" <?= $status === 'partial' ? 'selected' : '' ?>>Partial</option>
                <?php elseif ($tab === 'finance'): ?>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
                <?php endif; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="col-auto d-flex gap-2 ms-auto">
            <button type="submit" class="ks-btn ks-btn-primary">
                <i class="bi bi-funnel-fill"></i> Filter
            </button>
            <?php if (!empty($dateFrom) || !empty($dateTo) || !empty($status)): ?>
                <a href="/reports?tab=<?= htmlspecialchars($tab) ?>" class="ks-btn ks-btn-secondary" title="Reset Filters">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- TAB: INVENTORY & STOCK                                                    -->
<!-- ========================================================================= -->
<?php if ($tab === 'inventory' && $invSummary): ?>
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue"><i class="bi bi-box-seam"></i></div>
                <div>
                    <div class="ks-kpi-label">Total Inventory Items</div>
                    <div class="ks-kpi-value"><?= (int)$invSummary['summary']['total_items'] ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text"><?= (int)$invSummary['summary']['total_units'] ?> Units on hand</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green"><i class="bi bi-currency-rupee"></i></div>
                <div>
                    <div class="ks-kpi-label">Total Stock Valuation</div>
                    <div class="ks-kpi-value">₹<?= number_format((float)$invSummary['summary']['total_valuation'], 2) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text ks-trend-positive">Asset Book Value</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div>
                    <div class="ks-kpi-label">Low-Stock Items</div>
                    <div class="ks-kpi-value"><?= (int)$invSummary['summary']['low_stock_count'] ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text <?= (int)$invSummary['summary']['low_stock_count'] > 0 ? 'text-warning' : 'text-muted' ?>">Below Minimum Level</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-red"><i class="bi bi-x-circle-fill"></i></div>
                <div>
                    <div class="ks-kpi-label">Out of Stock</div>
                    <div class="ks-kpi-value"><?= (int)$invSummary['summary']['out_of_stock_count'] ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text <?= (int)$invSummary['summary']['out_of_stock_count'] > 0 ? 'text-danger' : 'text-muted' ?>">Zero Stock Balance</span></div>
        </div>
    </div>
</div>

<div class="ks-reports-grid mb-4">
    <!-- Valuation By Category -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <h3 class="ks-header-title">Stock Valuation by Category</h3>
                <span class="ks-badge ks-badge-blue ms-2"><?= count($invSummary['by_category'] ?? []) ?> Categories</span>
            </div>
        </div>
        <div class="ks-table-responsive">
            <table class="table ks-table mb-0">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-center">Items</th>
                        <th class="text-center">Units</th>
                        <th class="text-end">Valuation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invSummary['by_category'])): ?>
                        <tr><td colspan="4" class="text-muted text-center py-4">No inventory categories available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($invSummary['by_category'] as $cat): ?>
                        <tr>
                            <td class="fw-semibold text-navy"><?= htmlspecialchars($cat['category_name']) ?></td>
                            <td class="text-center"><?= (int)$cat['item_count'] ?></td>
                            <td class="text-center"><?= (int)$cat['total_units'] ?></td>
                            <td class="text-end fw-bold ks-col-money">₹<?= number_format((float)$cat['valuation'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Low-Stock Alerts -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <h3 class="ks-header-title">Low-Stock Action Report</h3>
                <span class="ks-badge <?= !empty($lowStock['items']) ? 'ks-badge-red' : 'ks-badge-green' ?> ms-2">
                    <?= count($lowStock['items'] ?? []) ?> Items Deficit
                </span>
            </div>
        </div>
        <div class="ks-table-responsive">
            <table class="table ks-table mb-0">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">In Stock</th>
                        <th class="text-center">Min Level</th>
                        <th class="text-end">Est. Restock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lowStock['items'])): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <div class="ks-empty-state py-2">
                                    <i class="bi bi-shield-check text-success fs-3 mb-1"></i>
                                    <div class="fw-semibold text-dark">Stock Levels Healthy</div>
                                    <div class="small text-muted">All inventory items are currently above minimum threshold.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_slice($lowStock['items'], 0, 8) as $ls): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-navy"><?= htmlspecialchars($ls['item_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($ls['item_code']) ?></small>
                            </td>
                            <td class="text-center">
                                <span class="ks-badge <?= (int)$ls['quantity'] <= 0 ? 'ks-badge-red' : 'ks-badge-amber' ?>">
                                    <?= (int)$ls['quantity'] ?>
                                </span>
                            </td>
                            <td class="text-center text-muted"><?= (int)$ls['minimum_stock_level'] ?></td>
                            <td class="text-end fw-bold ks-col-money">₹<?= number_format((float)$ls['estimated_restock_cost'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Stock Movement History -->
<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="ks-header-left">
            <h3 class="ks-header-title">Recent Stock Movements Log</h3>
            <span class="ks-badge ks-badge-purple ms-2"><?= count($stockMovements['transactions'] ?? []) ?> Logs</span>
        </div>
    </div>
    <div class="ks-table-responsive">
        <table class="table ks-table mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Total Cost</th>
                    <th>Performed By</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stockMovements['transactions'])): ?>
                    <tr><td colspan="7" class="text-muted text-center py-4">No stock transactions found for this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($stockMovements['transactions'] as $tx): ?>
                    <tr>
                        <td><small class="text-muted"><?= htmlspecialchars(substr($tx['transaction_date'], 0, 10)) ?></small></td>
                        <td>
                            <div class="fw-semibold text-navy"><?= htmlspecialchars($tx['item_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($tx['item_code']) ?></small>
                        </td>
                        <td>
                            <?php if ($tx['transaction_type'] === 'purchase' || $tx['transaction_type'] === 'in'): ?>
                                <span class="ks-badge ks-badge-green"><i class="bi bi-arrow-down-left"></i> <?= htmlspecialchars($tx['transaction_type']) ?></span>
                            <?php elseif ($tx['transaction_type'] === 'out' || $tx['transaction_type'] === 'issue'): ?>
                                <span class="ks-badge ks-badge-red"><i class="bi bi-arrow-up-right"></i> <?= htmlspecialchars($tx['transaction_type']) ?></span>
                            <?php else: ?>
                                <span class="ks-badge ks-badge-amber"><?= htmlspecialchars($tx['transaction_type']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-bold"><?= (int)$tx['quantity'] ?></td>
                        <td class="text-end ks-col-money">₹<?= number_format((float)$tx['total_cost'], 2) ?></td>
                        <td><small class="fw-medium text-dark"><?= htmlspecialchars($tx['performed_by_name'] ?: 'System') ?></small></td>
                        <td><small class="text-muted"><?= htmlspecialchars($tx['remarks'] ?? '—') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- TAB: PROCUREMENT & PURCHASES                                              -->
<!-- ========================================================================= -->
<?php if ($tab === 'purchases' && $purchSummary): ?>
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue"><i class="bi bi-file-earmark-text"></i></div>
                <div>
                    <div class="ks-kpi-label">Purchase Requests</div>
                    <div class="ks-kpi-value"><?= (int)$purchSummary['total_requests'] ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text"><?= (int)($purchSummary['requests_by_status']['pending'] ?? 0) ?> Pending Approval</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple"><i class="bi bi-receipt-cutoff"></i></div>
                <div>
                    <div class="ks-kpi-label">Purchase Orders</div>
                    <div class="ks-kpi-value"><?= (int)$purchSummary['total_orders'] ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text"><?= (int)($purchSummary['orders_by_status']['completed']['count'] ?? 0) ?> Fulfilled</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green"><i class="bi bi-currency-rupee"></i></div>
                <div>
                    <div class="ks-kpi-label">Total PO Commitment</div>
                    <div class="ks-kpi-value">₹<?= number_format((float)$purchSummary['total_order_amount'], 2) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text ks-trend-positive">Procurement Spend</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber"><i class="bi bi-truck"></i></div>
                <div>
                    <div class="ks-kpi-label">Goods Receipts</div>
                    <div class="ks-kpi-value"><?= (int)$purchSummary['total_goods_receipts'] ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text">Deliveries Recorded</span></div>
        </div>
    </div>
</div>

<div class="ks-reports-grid mb-4">
    <!-- Recent Purchase Orders -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <h3 class="ks-header-title">Purchase Orders Status</h3>
                <span class="ks-badge ks-badge-blue ms-2"><?= count($purchOrders['orders'] ?? []) ?> Orders</span>
            </div>
        </div>
        <div class="ks-table-responsive">
            <table class="table ks-table mb-0">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Vendor</th>
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($purchOrders['orders'])): ?>
                        <tr><td colspan="5" class="text-muted text-center py-4">No purchase orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($purchOrders['orders'], 0, 10) as $po): ?>
                        <tr>
                            <td class="fw-semibold text-navy"><?= htmlspecialchars($po['po_number']) ?></td>
                            <td><?= htmlspecialchars($po['vendor_name'] ?? '—') ?></td>
                            <td><small class="text-muted"><?= htmlspecialchars($po['order_date']) ?></small></td>
                            <td class="text-end fw-bold ks-col-money">₹<?= number_format((float)$po['total_amount'], 2) ?></td>
                            <td>
                                <?php if ($po['status'] === 'completed'): ?>
                                    <span class="ks-badge ks-badge-green">Completed</span>
                                <?php elseif ($po['status'] === 'sent' || $po['status'] === 'partial'): ?>
                                    <span class="ks-badge ks-badge-blue"><?= htmlspecialchars(ucfirst($po['status'])) ?></span>
                                <?php elseif ($po['status'] === 'cancelled'): ?>
                                    <span class="ks-badge ks-badge-red">Cancelled</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-amber">Draft</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Procurement Vendor Spend -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <h3 class="ks-header-title">Vendor Spend Allocation</h3>
                <span class="ks-badge ks-badge-purple ms-2"><?= count($procSpend['vendors'] ?? []) ?> Vendors</span>
            </div>
        </div>
        <div class="ks-table-responsive">
            <table class="table ks-table mb-0">
                <thead>
                    <tr>
                        <th>Vendor</th>
                        <th class="text-center">POs</th>
                        <th class="text-end">Total Spend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($procSpend['vendors'])): ?>
                        <tr><td colspan="3" class="text-muted text-center py-4">No vendor orders recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($procSpend['vendors'], 0, 8) as $vs): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-navy"><?= htmlspecialchars($vs['company_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($vs['vendor_code']) ?></small>
                            </td>
                            <td class="text-center fw-semibold"><?= (int)$vs['total_orders'] ?></td>
                            <td class="text-end fw-bold ks-col-money">₹<?= number_format((float)$vs['total_spend'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Goods Received Log -->
<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="ks-header-left">
            <h3 class="ks-header-title">Goods Received Delivery Log</h3>
            <span class="ks-badge ks-badge-amber ms-2"><?= count($goodsReceipts['receipts'] ?? []) ?> GRNs</span>
        </div>
    </div>
    <div class="ks-table-responsive">
        <table class="table ks-table mb-0">
            <thead>
                <tr>
                    <th>GRN Reference</th>
                    <th>Date</th>
                    <th>PO Ref</th>
                    <th>Vendor</th>
                    <th>Received By</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($goodsReceipts['receipts'])): ?>
                    <tr><td colspan="6" class="text-muted text-center py-4">No goods receipts registered yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($goodsReceipts['receipts'] as $gr): ?>
                    <tr>
                        <td class="fw-semibold text-navy"><?= htmlspecialchars($gr['receipt_number']) ?></td>
                        <td><small class="text-muted"><?= htmlspecialchars($gr['receipt_date']) ?></small></td>
                        <td><span class="ks-badge ks-badge-purple"><?= htmlspecialchars($gr['po_number']) ?></span></td>
                        <td class="fw-medium text-dark"><?= htmlspecialchars($gr['vendor_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($gr['received_by_name'] ?: 'Staff') ?></td>
                        <td><small class="text-muted"><?= htmlspecialchars($gr['remarks'] ?? '—') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- TAB: VENDOR INTELLIGENCE                                                  -->
<!-- ========================================================================= -->
<?php if ($tab === 'vendors' && $vendorInvoices): ?>
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue"><i class="bi bi-building"></i></div>
                <div>
                    <div class="ks-kpi-label">Active Vendors</div>
                    <div class="ks-kpi-value"><?= (int)($vendorSpend['total_vendors'] ?? 0) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text">Partner Network</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple"><i class="bi bi-receipt"></i></div>
                <div>
                    <div class="ks-kpi-label">Total Invoiced</div>
                    <div class="ks-kpi-value">₹<?= number_format((float)$vendorInvoices['summary']['total_invoiced_amount'], 2) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text"><?= (int)$vendorInvoices['summary']['total_invoices'] ?> Invoices</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <div class="ks-kpi-label">Total Settled</div>
                    <div class="ks-kpi-value">₹<?= number_format((float)$vendorInvoices['summary']['total_paid_amount'], 2) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text ks-trend-positive">Paid in Full</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-red"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="ks-kpi-label">Pending / Overdue</div>
                    <div class="ks-kpi-value">₹<?= number_format((float)$vendorInvoices['summary']['total_outstanding_amount'], 2) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text text-danger"><?= (int)$vendorInvoices['summary']['overdue_invoices_count'] ?> Overdue</span></div>
        </div>
    </div>
</div>

<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="ks-header-left">
            <h3 class="ks-header-title">Vendor Invoices Ledger</h3>
            <span class="ks-badge ks-badge-blue ms-2"><?= count($vendorInvoices['invoices'] ?? []) ?> Invoices</span>
        </div>
    </div>
    <div class="ks-table-responsive">
        <table class="table ks-table mb-0">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Vendor</th>
                    <th>PO Ref</th>
                    <th>Invoice Date</th>
                    <th>Due Date</th>
                    <th class="text-end">Amount</th>
                    <th>Payment Status</th>
                    <th>Overdue Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vendorInvoices['invoices'])): ?>
                    <tr><td colspan="8" class="text-muted text-center py-4">No vendor invoices found.</td></tr>
                <?php else: ?>
                    <?php foreach ($vendorInvoices['invoices'] as $inv): ?>
                    <tr>
                        <td class="fw-semibold text-navy"><?= htmlspecialchars($inv['invoice_number']) ?></td>
                        <td>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($inv['vendor_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($inv['vendor_code']) ?></small>
                        </td>
                        <td><span class="ks-badge ks-badge-purple"><?= htmlspecialchars($inv['po_number'] ?? '—') ?></span></td>
                        <td><small class="text-muted"><?= htmlspecialchars($inv['invoice_date']) ?></small></td>
                        <td><small class="text-muted"><?= htmlspecialchars($inv['due_date']) ?></small></td>
                        <td class="text-end fw-bold ks-col-money">₹<?= number_format((float)$inv['total_amount'], 2) ?></td>
                        <td>
                            <?php if ($inv['payment_status'] === 'paid'): ?>
                                <span class="ks-badge ks-badge-green">Paid</span>
                            <?php elseif ($inv['payment_status'] === 'partial'): ?>
                                <span class="ks-badge ks-badge-amber">Partial</span>
                            <?php else: ?>
                                <span class="ks-badge ks-badge-red">Unpaid</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int)$inv['days_overdue'] > 0): ?>
                                <span class="ks-badge ks-badge-red"><?= (int)$inv['days_overdue'] ?> days overdue</span>
                            <?php else: ?>
                                <span class="ks-badge ks-badge-green"><i class="bi bi-check"></i> On schedule</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- TAB: EQUIPMENT & ASSETS                                                   -->
<!-- ========================================================================= -->
<?php if ($tab === 'equipment' && $equipSummary): ?>
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue"><i class="bi bi-tools"></i></div>
                <div>
                    <div class="ks-kpi-label">Total Equipment</div>
                    <div class="ks-kpi-value"><?= (int)$equipSummary['summary']['total_equipment'] ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text"><?= (int)$equipSummary['summary']['available_count'] ?> Available for assignment</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green"><i class="bi bi-currency-rupee"></i></div>
                <div>
                    <div class="ks-kpi-label">Equipment Asset Value</div>
                    <div class="ks-kpi-value">₹<?= number_format((float)$equipSummary['summary']['total_asset_value'], 2) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text ks-trend-positive">Capital Investment</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple"><i class="bi bi-person-badge"></i></div>
                <div>
                    <div class="ks-kpi-label">Currently Assigned</div>
                    <div class="ks-kpi-value"><?= (int)$equipSummary['summary']['assigned_count'] ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text">In Active Deployment</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-red"><i class="bi bi-alarm-fill"></i></div>
                <div>
                    <div class="ks-kpi-label">Overdue Returns</div>
                    <div class="ks-kpi-value"><?= (int)($overdueEquip['overdue_count'] ?? 0) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text text-danger">Past Expected Return</span></div>
        </div>
    </div>
</div>

<div class="ks-reports-grid mb-4">
    <!-- Active Assignments -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <h3 class="ks-header-title">Active Equipment Assignments</h3>
                <span class="ks-badge ks-badge-blue ms-2"><?= count($assignedEquip['assignments'] ?? []) ?> Active</span>
            </div>
        </div>
        <div class="ks-table-responsive">
            <table class="table ks-table mb-0">
                <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Assignee</th>
                        <th>Assigned Date</th>
                        <th>Expected Return</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignedEquip['assignments'])): ?>
                        <tr><td colspan="4" class="text-muted text-center py-4">No equipment currently assigned.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($assignedEquip['assignments'], 0, 8) as $ea): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-navy"><?= htmlspecialchars($ea['equipment_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($ea['asset_code']) ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($ea['assignee_name']) ?></div>
                                <span class="ks-badge ks-badge-blue"><?= htmlspecialchars(ucfirst($ea['assignee_type'])) ?></span>
                            </td>
                            <td><small class="text-muted"><?= htmlspecialchars($ea['assigned_date']) ?></small></td>
                            <td><small class="fw-bold"><?= htmlspecialchars($ea['expected_return_date'] ?? 'Open-ended') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Condition Breakdown -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <h3 class="ks-header-title">Condition Status Breakdown</h3>
                <span class="ks-badge ks-badge-purple ms-2"><?= count($conditionReport['condition_breakdown'] ?? []) ?> States</span>
            </div>
        </div>
        <div class="ks-table-responsive">
            <table class="table ks-table mb-0">
                <thead>
                    <tr>
                        <th>Condition</th>
                        <th class="text-center">Count</th>
                        <th class="text-end">Asset Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($conditionReport['condition_breakdown'])): ?>
                        <tr><td colspan="3" class="text-muted text-center py-4">No condition records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($conditionReport['condition_breakdown'] as $cb): ?>
                        <tr>
                            <td>
                                <?php if ($cb['condition_status'] === 'new' || $cb['condition_status'] === 'good'): ?>
                                    <span class="ks-badge ks-badge-green"><?= htmlspecialchars(ucfirst($cb['condition_status'])) ?></span>
                                <?php elseif ($cb['condition_status'] === 'fair'): ?>
                                    <span class="ks-badge ks-badge-amber"><?= htmlspecialchars(ucfirst($cb['condition_status'])) ?></span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-red"><?= htmlspecialchars(ucfirst($cb['condition_status'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold"><?= (int)$cb['count'] ?></td>
                            <td class="text-end fw-bold ks-col-money">₹<?= number_format((float)$cb['valuation'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Overdue Returns Alert Table -->
<?php if (!empty($overdueEquip['items'])): ?>
<div class="ks-table-card border border-danger-subtle">
    <div class="ks-table-header bg-danger-subtle py-2">
        <div class="ks-header-left">
            <h3 class="ks-header-title text-danger"><i class="bi bi-exclamation-octagon-fill me-2"></i>Overdue Equipment Alert</h3>
            <span class="ks-badge ks-badge-red ms-2"><?= count($overdueEquip['items']) ?> Overdue</span>
        </div>
    </div>
    <div class="ks-table-responsive">
        <table class="table ks-table mb-0">
            <thead>
                <tr>
                    <th>Asset</th>
                    <th>Assignee</th>
                    <th>Due Date</th>
                    <th>Days Overdue</th>
                    <th class="ks-col-actions">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overdueEquip['items'] as $od): ?>
                <tr>
                    <td>
                        <div class="fw-semibold text-navy"><?= htmlspecialchars($od['equipment_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($od['asset_code']) ?></small>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($od['assignee_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars(ucfirst($od['assignee_type'])) ?></small>
                    </td>
                    <td><span class="text-danger fw-semibold"><?= htmlspecialchars($od['expected_return_date']) ?></span></td>
                    <td><span class="ks-badge ks-badge-red"><?= (int)$od['days_overdue'] ?> Days Overdue</span></td>
                    <td class="ks-col-actions">
                        <a href="/equipment/<?= (int)$od['id'] ?>" class="ks-btn ks-btn-sm ks-btn-secondary">
                            Inspect Return
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- TAB: OPERATIONAL OVERVIEW                                                 -->
<!-- ========================================================================= -->
<?php if ($tab === 'operational'): ?>
<div class="row g-3 mb-4">
    <!-- Training Attendance Rate -->
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Training Attendance</div>
                    <div class="ks-kpi-value"><?= htmlspecialchars((string)($attendance['attendance_rate'] ?? 0)) ?>%</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive"><?= (int)($attendance['present_count'] ?? 0) ?> Present / <?= (int)($attendance['total_records'] ?? 0) ?> Tracked</span>
            </div>
        </div>
    </div>

    <!-- Tournament Activity -->
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Tournament Fixtures</div>
                    <div class="ks-kpi-value"><?= (int)($tournaments['total_fixtures'] ?? 0) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text"><?= (int)($tournaments['completed_matches'] ?? 0) ?> Played &bull; <?= (int)($tournaments['pending_matches'] ?? 0) ?> Pending</span>
            </div>
        </div>
    </div>

    <!-- Inventory Valuation -->
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple">
                    <i class="bi bi-box-seam-fill"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Inventory Assets</div>
                    <div class="ks-kpi-value">₹<?= number_format((float)($inventory['total_valuation'] ?? 0), 2) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text"><?= (int)($inventory['total_items'] ?? 0) ?> Items (<?= (int)($inventory['low_stock_count'] ?? 0) ?> Low Stock)</span>
            </div>
        </div>
    </div>

    <!-- Payroll Disbursed -->
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green">
                    <i class="bi bi-currency-rupee"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Payroll Disbursed</div>
                    <div class="ks-kpi-value">₹<?= number_format((float)($payroll['total_net'] ?? 0), 2) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive"><?= (int)($payroll['total_employees'] ?? 0) ?> Employees on Roster</span>
            </div>
        </div>
    </div>
</div>

<div class="ks-reports-grid mb-4">
    <!-- Athletes Distribution by Sport -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <h3 class="ks-header-title">Athletes by Sport</h3>
                <span class="ks-badge ks-badge-blue ms-2"><?= count($athletesBySport) ?> Sports</span>
            </div>
        </div>
        <div class="ks-table-responsive">
            <table class="table ks-table mb-0">
                <thead>
                    <tr>
                        <th>Sport</th>
                        <th class="text-end">Athlete Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($athletesBySport)): ?>
                        <tr><td colspan="2" class="text-muted text-center py-4">No athletes registered under any sport yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($athletesBySport as $as): ?>
                            <tr>
                                <td><span class="fw-semibold text-navy"><?= htmlspecialchars($as['sport_name']) ?></span></td>
                                <td class="text-end"><span class="ks-badge ks-badge-blue"><?= (int)$as['count'] ?> Athletes</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Teams Distribution by Sport -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <h3 class="ks-header-title">Teams by Sport</h3>
                <span class="ks-badge ks-badge-purple ms-2"><?= count($teamsBySport) ?> Disciplines</span>
            </div>
        </div>
        <div class="ks-table-responsive">
            <table class="table ks-table mb-0">
                <thead>
                    <tr>
                        <th>Sport</th>
                        <th class="text-end">Active Squads</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teamsBySport)): ?>
                        <tr><td colspan="2" class="text-muted text-center py-4">No teams created yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($teamsBySport as $ts): ?>
                            <tr>
                                <td><span class="fw-semibold text-navy"><?= htmlspecialchars($ts['sport_name']) ?></span></td>
                                <td class="text-end"><span class="ks-badge ks-badge-purple"><?= (int)$ts['count'] ?> Teams</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="ks-reports-grid">
    <!-- Venue Utilization -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <h3 class="ks-header-title">Venue Utilization</h3>
                <span class="ks-badge ks-badge-cyan ms-2"><?= count($venues) ?> Venues</span>
            </div>
        </div>
        <div class="ks-table-responsive">
            <table class="table ks-table mb-0">
                <thead>
                    <tr>
                        <th>Venue Name</th>
                        <th class="text-end">Total Bookings</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($venues)): ?>
                        <tr><td colspan="2" class="text-muted text-center py-4">No venues configured.</td></tr>
                    <?php else: ?>
                        <?php foreach ($venues as $v): ?>
                            <tr>
                                <td><span class="fw-semibold text-navy"><?= htmlspecialchars($v['venue_name']) ?></span></td>
                                <td class="text-end"><span class="ks-badge ks-badge-cyan"><?= (int)$v['booking_count'] ?> Bookings</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Staff & Leave Status Summary -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <h3 class="ks-header-title">Staff Leave Summary</h3>
            </div>
        </div>
        <div class="ks-table-responsive">
            <table class="table ks-table mb-0">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th class="text-end">Requests</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="ks-badge ks-badge-amber">Pending Approval</span></td>
                        <td class="text-end fw-bold text-navy"><?= (int)($leave['pending'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td><span class="ks-badge ks-badge-green">Approved</span></td>
                        <td class="text-end fw-bold text-navy"><?= (int)($leave['approved'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td><span class="ks-badge ks-badge-red">Rejected</span></td>
                        <td class="text-end fw-bold text-navy"><?= (int)($leave['rejected'] ?? 0) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- TAB: FINANCIAL REPORTS                                                    -->
<!-- ========================================================================= -->
<?php if ($tab === 'finance' && $finSummary): ?>
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <div class="ks-kpi-label">Total Income</div>
                    <div class="ks-kpi-value">₹<?= number_format((float)$finSummary['total_income'], 2) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text ks-trend-positive">Total Revenue Inflows</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-red"><i class="bi bi-graph-down-arrow"></i></div>
                <div>
                    <div class="ks-kpi-label">Total Expenses</div>
                    <div class="ks-kpi-value">₹<?= number_format((float)$finSummary['total_expense'], 2) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text">Operational Outflows</span></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="ks-kpi-label">Net Operating Margin</div>
                    <div class="ks-kpi-value <?= $finSummary['net_profit_loss'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        ₹<?= number_format((float)$finSummary['net_profit_loss'], 2) ?>
                    </div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text <?= $finSummary['net_profit_loss'] >= 0 ? 'ks-trend-positive' : 'text-danger' ?>">
                    <?= $finSummary['net_profit_loss'] >= 0 ? 'Operating Surplus' : 'Operating Deficit' ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber"><i class="bi bi-pie-chart"></i></div>
                <div>
                    <div class="ks-kpi-label">Budget Utilization</div>
                    <div class="ks-kpi-value"><?= number_format((float)$finSummary['budget_utilization_rate'], 1) ?>%</div>
                </div>
            </div>
            <div class="ks-kpi-bottom"><span class="ks-trend-text">₹<?= number_format((float)$finSummary['budget_spent'], 2) ?> / ₹<?= number_format((float)$finSummary['budget_allocated'], 2) ?></span></div>
        </div>
    </div>
</div>

<div class="ks-reports-grid mb-4">
    <!-- Income by Category -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <h3 class="ks-header-title">Income by Category</h3>
                <span class="ks-badge ks-badge-green ms-2"><?= count($incomeSummary['by_category'] ?? []) ?> Categories</span>
            </div>
        </div>
        <div class="ks-table-responsive">
            <table class="table ks-table mb-0">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-center">Transactions</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($incomeSummary['by_category'])): ?>
                        <tr><td colspan="3" class="text-muted text-center py-4">No income recorded for this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($incomeSummary['by_category'] as $inc): ?>
                        <tr>
                            <td class="fw-semibold text-navy"><?= htmlspecialchars($inc['category_name']) ?></td>
                            <td class="text-center"><?= (int)$inc['transaction_count'] ?></td>
                            <td class="text-end fw-bold text-success ks-col-money">₹<?= number_format((float)$inc['total_amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Expense by Category -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <h3 class="ks-header-title">Expense by Category</h3>
                <span class="ks-badge ks-badge-red ms-2"><?= count($expenseSummary['by_category'] ?? []) ?> Categories</span>
            </div>
        </div>
        <div class="ks-table-responsive">
            <table class="table ks-table mb-0">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-center">Count</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenseSummary['by_category'])): ?>
                        <tr><td colspan="3" class="text-muted text-center py-4">No expenses recorded for this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($expenseSummary['by_category'] as $exp): ?>
                        <tr>
                            <td class="fw-semibold text-navy"><?= htmlspecialchars($exp['category_name']) ?></td>
                            <td class="text-center"><?= (int)$exp['expense_count'] ?></td>
                            <td class="text-end fw-bold text-danger ks-col-money">₹<?= number_format((float)$exp['total_amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Budget vs Actual Variance Report -->
<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="ks-header-left">
            <h3 class="ks-header-title">Budget vs. Actual Variance Analysis</h3>
            <span class="ks-badge ks-badge-purple ms-2"><?= count($budgetVsActual['budgets'] ?? []) ?> Budgets</span>
        </div>
    </div>
    <div class="ks-table-responsive">
        <table class="table ks-table mb-0">
            <thead>
                <tr>
                    <th>Budget Plan</th>
                    <th>Fiscal Period</th>
                    <th class="text-end">Allocated</th>
                    <th class="text-end">Actual Spent</th>
                    <th class="text-end">Variance</th>
                    <th class="text-center">Utilization</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($budgetVsActual['budgets'])): ?>
                    <tr><td colspan="7" class="text-muted text-center py-4">No active budget allocations found.</td></tr>
                <?php else: ?>
                    <?php foreach ($budgetVsActual['budgets'] as $b): ?>
                    <tr>
                        <td class="fw-semibold text-navy"><?= htmlspecialchars($b['budget_name']) ?></td>
                        <td><small class="text-muted"><?= htmlspecialchars($b['start_date']) ?> to <?= htmlspecialchars($b['end_date']) ?></small></td>
                        <td class="text-end ks-col-money">₹<?= number_format((float)$b['allocated_amount'], 2) ?></td>
                        <td class="text-end fw-bold ks-col-money">₹<?= number_format((float)$b['actual_spent'], 2) ?></td>
                        <td class="text-end fw-bold <?= (float)$b['variance'] >= 0 ? 'text-success' : 'text-danger' ?> ks-col-money">
                            ₹<?= number_format((float)$b['variance'], 2) ?>
                        </td>
                        <td class="text-center">
                            <span class="ks-badge <?= (float)$b['utilization_rate'] >= 100 ? 'ks-badge-red' : ((float)$b['utilization_rate'] >= 90 ? 'ks-badge-amber' : 'ks-badge-green') ?>">
                                <?= number_format((float)$b['utilization_rate'], 1) ?>%
                            </span>
                        </td>
                        <td>
                            <span class="ks-badge ks-badge-blue"><?= htmlspecialchars(ucfirst($b['status'])) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
