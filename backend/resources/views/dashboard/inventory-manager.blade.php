<?php
$activePage = 'dashboard';
$title = 'Inventory Management Dashboard — KhelSutra';

$orgId = \App\Helpers\AuthContext::getOrganizationId();
$reportService = new \App\Services\Report\ReportService();
$metrics = $reportService->getInventoryDashboardMetrics($orgId);

$inventoryService = new \App\Services\Inventory\InventoryService();
$lowStockItems = $inventoryService->getLowStockItems($orgId);
$outOfStockItems = $inventoryService->getOutOfStockItems($orgId);

ob_start();
?>

<!-- Page Header -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Inventory Management Dashboard</h1>
    </div>
    <div class="ks-header-actions">
        <!-- Date Indicator Widget -->
        <div class="ks-date-widget">
            <i class="bi bi-calendar-check fs-5"></i>
            <div>
                <div class="ks-date-text"><?= date('l, d M Y') ?></div>
                <div class="ks-time-text"><?= date('h:i A') ?></div>
            </div>
        </div>

        <a href="/inventory" class="ks-btn ks-btn-secondary">
            <i class="bi bi-box-seam"></i>
            <span>View Inventory</span>
        </a>

        <a href="/inventory/create" class="ks-btn ks-btn-primary">
            <i class="bi bi-plus-lg"></i>
            <span>Add Stock Item</span>
        </a>
    </div>
</div>

<!-- Primary KPI Row (Inventory Metrics) -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Inventory Items -->
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-box-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Total Inventory Items</div>
                    <div class="ks-kpi-value"><?= (int)$metrics['total_items'] ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Tracked catalog items</span>
            </div>
        </div>
    </div>

    <!-- Card 2: Ready to Use -->
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green">
                    <i class="bi bi-check-circle-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Ready to Use</div>
                    <div class="ks-kpi-value"><?= (int)$metrics['ready_to_use'] ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">
                    <i class="bi bi-check fs-5 align-middle"></i> Available in stock
                </span>
            </div>
        </div>
    </div>

    <!-- Card 3: Low Stock -->
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber">
                    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Low Stock</div>
                    <div class="ks-kpi-value"><?= (int)$metrics['low_stock'] ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-warning">Below reorder level</span>
            </div>
        </div>
    </div>

    <!-- Card 4: Out of Stock -->
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-red">
                    <i class="bi bi-x-circle-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Out of Stock</div>
                    <div class="ks-kpi-value"><?= (int)$metrics['out_of_stock'] ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-danger">Zero available stock</span>
            </div>
        </div>
    </div>
</div>

<!-- Main Inventory Tables Row -->
<div class="row g-4 mb-4">
    <!-- Left Column: Low Stock - Needs Attention -->
    <div class="col-lg-6">
        <div class="ks-content-card h-100">
            <div class="ks-card-header">
                <div class="ks-header-left">
                    <i class="bi bi-exclamation-triangle-fill" style="color: var(--ks-warning); font-size: 18px;"></i>
                    <h3 class="ks-header-title">Low Stock — Needs Attention</h3>
                </div>
                <a href="/inventory?status=low_stock" class="ks-header-link">
                    <span>View All</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="ks-table-responsive">
                <table class="ks-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Reorder Level</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lowStockItems)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-check-circle d-block fs-3 mb-2 text-success"></i>
                                    All items are sufficiently stocked.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach (array_slice($lowStockItems, 0, 8) as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-navy"><?= htmlspecialchars($item['item_name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($item['item_code']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                                    <td class="fw-bold"><?= (float)$item['quantity'] ?></td>
                                    <td><?= (float)$item['reorder_level'] ?></td>
                                    <td>
                                        <span class="ks-badge" style="background: rgba(245,158,11,0.1); color: #B45309;">Low Stock</span>
                                    </td>
                                    <td>
                                        <a href="/inventory/<?= $item['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Out of Stock -->
    <div class="col-lg-6">
        <div class="ks-content-card h-100">
            <div class="ks-card-header">
                <div class="ks-header-left">
                    <i class="bi bi-x-circle-fill" style="color: var(--ks-danger); font-size: 18px;"></i>
                    <h3 class="ks-header-title">Out of Stock</h3>
                </div>
                <a href="/inventory" class="ks-header-link">
                    <span>View Inventory</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="ks-table-responsive">
                <table class="ks-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($outOfStockItems)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-box-seam d-block fs-3 mb-2"></i>
                                    No items are currently out of stock.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach (array_slice($outOfStockItems, 0, 8) as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-navy"><?= htmlspecialchars($item['item_name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($item['item_code']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                                    <td class="fw-bold text-danger"><?= (float)$item['quantity'] ?></td>
                                    <td>
                                        <span class="ks-badge ks-badge-cancelled">Out of Stock</span>
                                    </td>
                                    <td>
                                        <a href="/inventory/<?= $item['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Operations: Quick Actions -->
<div class="ks-content-card mb-4">
    <div class="ks-card-header">
        <div class="ks-header-left">
            <i class="bi bi-lightning-charge-fill" style="color: var(--ks-gold); font-size: 18px;"></i>
            <h3 class="ks-header-title">Quick Operational Actions</h3>
        </div>
    </div>
    <div class="p-3">
        <div class="d-flex flex-wrap gap-2">
            <a href="/inventory/create" class="btn btn-outline-primary d-flex align-items-center gap-2" style="border-radius: 8px; font-weight: 500; font-size: 13px;">
                <i class="bi bi-plus-circle"></i> Add Stock Item
            </a>
            <a href="/inventory" class="btn btn-outline-primary d-flex align-items-center gap-2" style="border-radius: 8px; font-weight: 500; font-size: 13px;">
                <i class="bi bi-list-ul"></i> View Inventory
            </a>
            <a href="/purchases" class="btn btn-outline-primary d-flex align-items-center gap-2" style="border-radius: 8px; font-weight: 500; font-size: 13px;">
                <i class="bi bi-cart"></i> View Purchases
            </a>
        </div>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
