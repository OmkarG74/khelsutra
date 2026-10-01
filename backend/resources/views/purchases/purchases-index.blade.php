<?php
$pageTitle = 'Procurement & Purchasing — KhelSutra';
$activePage = 'purchases';
$orgId = current_organization_id();

$purchaseService = new \App\Services\Purchase\PurchaseService();
$activeTab = trim($_GET['tab'] ?? 'orders');

$db = \App\Services\BaseService::getDatabaseConnection();

// KPI Stats
$statsStmt = $db->prepare("
    SELECT 
        (SELECT COUNT(*) FROM purchase_orders WHERE organization_id = :org_id) as total_pos,
        (SELECT COALESCE(SUM(total_amount), 0) FROM purchase_orders WHERE organization_id = :org_id AND status != 'cancelled') as total_spend,
        (SELECT COUNT(*) FROM purchase_requests WHERE organization_id = :org_id) as total_prs,
        (SELECT COUNT(*) FROM purchase_requests WHERE organization_id = :org_id AND status = 'submitted') as pending_prs,
        (SELECT COUNT(*) FROM goods_receipts WHERE organization_id = :org_id) as total_grns
");
$statsStmt->execute([':org_id' => $orgId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_pos' => 0, 'total_spend' => 0, 'total_prs' => 0, 'pending_prs' => 0, 'total_grns' => 0
];

// Fetch data according to tab
$page = (int)($_GET['page'] ?? 1);
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$orders = [];
$requests = [];
$receipts = [];
$totalPages = 1;
$total = 0;

if ($activeTab === 'requests') {
    $res = $purchaseService->listPurchaseRequests($orgId, $page, 15, $search ?: null, $statusFilter ?: null);
    $requests = $res['data'];
    $total = $res['total'];
    $totalPages = $res['total_pages'];
} elseif ($activeTab === 'receipts') {
    $res = $purchaseService->listGoodsReceipts($orgId, $page, 15);
    $receipts = $res['data'];
    $total = $res['total'];
    $totalPages = $res['total_pages'];
} else {
    $activeTab = 'orders';
    $res = $purchaseService->listPurchaseOrders($orgId, $page, 15, $search ?: null, $statusFilter ?: null);
    $orders = $res['data'];
    $total = $res['total'];
    $totalPages = $res['total_pages'];
}

ob_start();
?>

<div class="ks-content">
    <?php if (!empty($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: var(--ks-radius-button); font-size: 13.5px;">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div><?= htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: var(--ks-radius-button); font-size: 13.5px;">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Standard Page Header -->
    <div class="ks-page-header mb-4">
        <div>
            <h1 class="ks-page-title mb-1">Purchases & Orders</h1>
            <p class="ks-page-subtitle">Manage purchase requisitions, supplier orders, goods receipt notes, and procurement budgets</p>
        </div>
        <div class="ks-header-actions d-flex gap-2">
            <a href="/purchases/requests/create" class="ks-btn ks-btn-secondary">
                <i class="bi bi-file-earmark-plus"></i>
                <span>New Request</span>
            </a>
            <a href="/purchases/orders/create" class="ks-btn ks-btn-primary">
                <i class="bi bi-cart-plus"></i>
                <span>New Purchase Order</span>
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="ks-kpi-card">
                <div class="ks-kpi-top">
                    <div class="ks-icon-box ks-icon-purple">
                        <i class="bi bi-cart-check fs-4"></i>
                    </div>
                    <div>
                        <div class="ks-kpi-label">TOTAL PO COMMITMENT</div>
                        <div class="ks-kpi-value">₹<?= number_format((float)$stats['total_spend'], 2) ?></div>
                    </div>
                </div>
                <div class="ks-kpi-bottom">
                    <span class="ks-trend-text text-muted"><?= (int)$stats['total_pos'] ?> purchase orders issued</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="ks-kpi-card">
                <div class="ks-kpi-top">
                    <div class="ks-icon-box ks-icon-blue">
                        <i class="bi bi-file-earmark-text fs-4"></i>
                    </div>
                    <div>
                        <div class="ks-kpi-label">PURCHASE REQUESTS</div>
                        <div class="ks-kpi-value"><?= (int)$stats['total_prs'] ?></div>
                    </div>
                </div>
                <div class="ks-kpi-bottom">
                    <span class="ks-trend-text text-muted">Internal requisitions</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="ks-kpi-card">
                <div class="ks-kpi-top">
                    <div class="ks-icon-box ks-icon-amber">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                    <div>
                        <div class="ks-kpi-label">PENDING PR APPROVAL</div>
                        <div class="ks-kpi-value <?= (int)$stats['pending_prs'] > 0 ? 'text-warning' : '' ?>"><?= (int)$stats['pending_prs'] ?></div>
                    </div>
                </div>
                <div class="ks-kpi-bottom">
                    <span class="ks-trend-text <?= (int)$stats['pending_prs'] > 0 ? 'ks-trend-negative' : 'text-muted' ?>">Awaiting administrative sign-off</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="ks-kpi-card">
                <div class="ks-kpi-top">
                    <div class="ks-icon-box ks-icon-green">
                        <i class="bi bi-box-seam fs-4"></i>
                    </div>
                    <div>
                        <div class="ks-kpi-label">GOODS RECEIPTS (GRN)</div>
                        <div class="ks-kpi-value text-success"><?= (int)$stats['total_grns'] ?></div>
                    </div>
                </div>
                <div class="ks-kpi-bottom">
                    <span class="ks-trend-text ks-trend-positive">Processed warehouse receipts</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav ks-nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'orders' ? 'active' : '' ?>" href="/purchases?tab=orders">
                <i class="bi bi-cart-check me-2"></i> Purchase Orders (<?= (int)$stats['total_pos'] ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'requests' ? 'active' : '' ?>" href="/purchases?tab=requests">
                <i class="bi bi-file-earmark-text me-2"></i> Purchase Requests (<?= (int)$stats['total_prs'] ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'receipts' ? 'active' : '' ?>" href="/purchases?tab=receipts">
                <i class="bi bi-box-arrow-in-down me-2"></i> Goods Receipts (<?= (int)$stats['total_grns'] ?>)
            </a>
        </li>
    </ul>

    <?php if ($activeTab !== 'receipts'): ?>
        <!-- Filter Toolbar -->
        <div class="ks-filter-bar mb-4">
            <form method="GET" action="/purchases" class="d-flex align-items-center gap-2 flex-wrap m-0">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">
                <div style="flex: 1 1 280px; min-width: 220px; position: relative;">
                    <i class="bi bi-search" style="position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--ks-text-muted); font-size: 13px; pointer-events: none;"></i>
                    <input type="text" name="search" class="form-control ks-form-control" placeholder="Search reference, supplier, remarks..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" style="padding-left: 36px;">
                </div>
                <div style="flex: 0 0 190px;">
                    <select name="status" class="form-select ks-form-select">
                        <option value="">All Statuses</option>
                        <?php if ($activeTab === 'requests'): ?>
                            <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="submitted" <?= $statusFilter === 'submitted' ? 'selected' : '' ?>>Submitted (Pending)</option>
                            <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="converted" <?= $statusFilter === 'converted' ? 'selected' : '' ?>>Converted to PO</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <?php else: ?>
                            <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>Sent to Vendor</option>
                            <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="partially_received" <?= $statusFilter === 'partially_received' ? 'selected' : '' ?>>Partially Received</option>
                            <option value="received" <?= $statusFilter === 'received' ? 'selected' : '' ?>>Fully Received</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="ks-btn ks-btn-primary">
                        <i class="bi bi-funnel"></i>
                        <span>Filter</span>
                    </button>
                    <?php if ($search || $statusFilter): ?>
                        <a href="/purchases?tab=<?= urlencode($activeTab) ?>" class="ks-btn ks-btn-secondary" title="Clear filters">
                            <i class="bi bi-x-circle"></i>
                            <span>Clear</span>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Table View Container -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <?php if ($activeTab === 'orders'): ?>
                    <i class="bi bi-cart-check text-primary fs-5"></i>
                    <h3 class="ks-header-title">Purchase Orders</h3>
                <?php elseif ($activeTab === 'requests'): ?>
                    <i class="bi bi-file-earmark-text text-primary fs-5"></i>
                    <h3 class="ks-header-title">Purchase Requests</h3>
                <?php else: ?>
                    <i class="bi bi-box-arrow-in-down text-primary fs-5"></i>
                    <h3 class="ks-header-title">Goods Receipts (GRN)</h3>
                <?php endif; ?>
                <span class="badge bg-light text-secondary border ms-2 fw-medium" style="font-size: 11px;">
                    <?= number_format($total) ?> <?= $total === 1 ? 'record' : 'records' ?>
                </span>
            </div>
            <?php if ($search || $statusFilter): ?>
                <div class="d-flex align-items-center gap-2 small">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Filtered</span>
                    <a href="/purchases?tab=<?= urlencode($activeTab) ?>" class="text-muted text-decoration-none small"><i class="bi bi-x"></i> Reset</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="ks-table-responsive">
            <?php if ($activeTab === 'orders'): ?>
                <!-- Purchase Orders Table -->
                <table class="table ks-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 170px;">PO Number</th>
                            <th style="min-width: 220px;">Vendor / Supplier</th>
                            <th style="min-width: 130px;">Order Date</th>
                            <th style="min-width: 130px;">Delivery Date</th>
                            <th style="min-width: 100px;">Items</th>
                            <th class="ks-col-money" style="min-width: 150px;">Total Amount</th>
                            <th style="min-width: 120px; text-align: center;">Status</th>
                            <th class="ks-col-actions" style="min-width: 100px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $po): ?>
                                <tr>
                                    <td class="py-3 px-3">
                                        <a href="/purchases/orders/<?= (int)$po['id'] ?>" class="fw-bold text-decoration-none text-primary font-monospace d-block">
                                            <?= htmlspecialchars($po['po_number'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <?php if (!empty($po['request_reference'])): ?>
                                            <div class="text-muted small" style="font-size: 11px;">via <?= htmlspecialchars($po['request_reference'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($po['vendor_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if (!empty($po['vendor_code'])): ?>
                                            <span class="badge bg-light text-secondary border" style="font-family: monospace; font-size: 10.5px;">
                                                <?= htmlspecialchars($po['vendor_code'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3 text-secondary small">
                                        <?= !empty($po['order_date']) ? date('d M Y', strtotime($po['order_date'])) : '—' ?>
                                    </td>
                                    <td class="py-3 px-3 text-secondary small">
                                        <?= !empty($po['expected_delivery_date']) ? date('d M Y', strtotime($po['expected_delivery_date'])) : '—' ?>
                                    </td>
                                    <td class="py-3 px-3 text-secondary small">
                                        <?= (int)$po['item_count'] ?> <?= ((int)$po['item_count'] === 1) ? 'item' : 'items' ?>
                                    </td>
                                    <td class="py-3 px-3 ks-col-money">
                                        <span class="fw-bold font-monospace text-dark">₹<?= number_format((float)$po['total_amount'], 2) ?></span>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <?php
                                        $sBadge = match($po['status']) {
                                            'draft' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                            'sent' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                            'confirmed' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                            'partially_received' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                            'received' => 'bg-success-subtle text-success border border-success-subtle',
                                            'cancelled' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                            default => 'bg-light text-dark'
                                        };
                                        ?>
                                        <span class="badge <?= $sBadge ?> px-2 py-1" style="font-size: 11px;">
                                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $po['status'])), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 ks-col-actions text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="/purchases/orders/<?= (int)$po['id'] ?>" class="btn btn-outline-secondary" title="View Order Details">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="ks-empty-state">
                                        <div class="ks-empty-icon">
                                            <i class="bi bi-cart-x fs-3"></i>
                                        </div>
                                        <div class="ks-empty-title">No purchase orders found</div>
                                        <p class="ks-empty-desc">
                                            <?= ($search || $statusFilter) ? 'No purchase orders match your filter criteria. Try clearing filters.' : 'Issue purchase orders to approved vendors to procure inventory items and equipment.' ?>
                                        </p>
                                        <?php if ($search || $statusFilter): ?>
                                            <a href="/purchases?tab=orders" class="ks-btn ks-btn-secondary">
                                                <i class="bi bi-x-circle me-1"></i> Clear Filters
                                            </a>
                                        <?php else: ?>
                                            <a href="/purchases/orders/create" class="ks-btn ks-btn-primary">
                                                <i class="bi bi-plus-lg me-1"></i> Issue First Purchase Order
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif ($activeTab === 'requests'): ?>
                <!-- Purchase Requests Table -->
                <table class="table ks-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 180px;">Request Reference</th>
                            <th style="min-width: 170px;">Requested By</th>
                            <th style="min-width: 130px;">Request Date</th>
                            <th style="min-width: 100px;">Items</th>
                            <th class="ks-col-money" style="min-width: 150px;">Est. Total Cost</th>
                            <th style="min-width: 120px; text-align: center;">Status</th>
                            <th class="ks-col-actions" style="min-width: 100px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $pr): ?>
                                <tr>
                                    <td class="py-3 px-3">
                                        <a href="/purchases/requests/<?= (int)$pr['id'] ?>" class="fw-bold text-decoration-none text-primary font-monospace d-block">
                                            <?= htmlspecialchars($pr['request_reference'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <?php if (!empty($pr['purpose'])): ?>
                                            <div class="text-muted text-truncate small" style="font-size: 11px; max-width: 250px;" title="<?= htmlspecialchars($pr['purpose'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($pr['purpose'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3 text-dark fw-medium">
                                        <?= htmlspecialchars($pr['requester_name'] ?? 'Staff', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="py-3 px-3 text-secondary small">
                                        <?= !empty($pr['request_date']) ? date('d M Y', strtotime($pr['request_date'])) : '—' ?>
                                    </td>
                                    <td class="py-3 px-3 text-secondary small">
                                        <?= (int)$pr['item_count'] ?> <?= ((int)$pr['item_count'] === 1) ? 'item' : 'items' ?>
                                    </td>
                                    <td class="py-3 px-3 ks-col-money">
                                        <span class="fw-semibold font-monospace text-dark">₹<?= number_format((float)$pr['total_estimated_cost'], 2) ?></span>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <?php
                                        $rBadge = match($pr['status']) {
                                            'draft' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                            'submitted' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                            'approved' => 'bg-success-subtle text-success border border-success-subtle',
                                            'rejected' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                            'converted' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                            'cancelled' => 'bg-light text-muted border',
                                            default => 'bg-light text-dark'
                                        };
                                        ?>
                                        <span class="badge <?= $rBadge ?> px-2 py-1" style="font-size: 11px;">
                                            <?= htmlspecialchars(ucfirst($pr['status']), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 ks-col-actions text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="/purchases/requests/<?= (int)$pr['id'] ?>" class="btn btn-outline-secondary" title="View Request Details">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="ks-empty-state">
                                        <div class="ks-empty-icon">
                                            <i class="bi bi-file-earmark-x fs-3"></i>
                                        </div>
                                        <div class="ks-empty-title">No purchase requests found</div>
                                        <p class="ks-empty-desc">
                                            <?= ($search || $statusFilter) ? 'No requisition requests match your filter criteria.' : 'Create purchase requests to initiate internal requisitions and approval workflows.' ?>
                                        </p>
                                        <?php if ($search || $statusFilter): ?>
                                            <a href="/purchases?tab=requests" class="ks-btn ks-btn-secondary">
                                                <i class="bi bi-x-circle me-1"></i> Clear Filters
                                            </a>
                                        <?php else: ?>
                                            <a href="/purchases/requests/create" class="ks-btn ks-btn-primary">
                                                <i class="bi bi-plus-lg me-1"></i> Create First Purchase Request
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif ($activeTab === 'receipts'): ?>
                <!-- Goods Receipts Table -->
                <table class="table ks-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 170px;">Receipt Number (GRN)</th>
                            <th style="min-width: 150px;">Purchase Order #</th>
                            <th style="min-width: 200px;">Vendor / Supplier</th>
                            <th style="min-width: 130px;">Receipt Date</th>
                            <th style="min-width: 150px;">Received By</th>
                            <th style="min-width: 180px;">Remarks</th>
                            <th class="ks-col-actions" style="min-width: 100px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($receipts)): ?>
                            <?php foreach ($receipts as $gr): ?>
                                <tr>
                                    <td class="py-3 px-3">
                                        <a href="/purchases/receipts/<?= (int)$gr['id'] ?>" class="fw-bold text-decoration-none text-success font-monospace">
                                            <?= htmlspecialchars($gr['receipt_number'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td class="py-3 px-3">
                                        <a href="/purchases/orders/<?= (int)$gr['purchase_order_id'] ?>" class="text-decoration-none font-monospace text-primary fw-medium">
                                            <?= htmlspecialchars($gr['po_number'] ?? 'PO', ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td class="py-3 px-3 text-dark fw-medium">
                                        <?= htmlspecialchars($gr['vendor_name'] ?? 'Supplier', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="py-3 px-3 text-secondary small">
                                        <?= !empty($gr['receipt_date']) ? date('d M Y', strtotime($gr['receipt_date'])) : '—' ?>
                                    </td>
                                    <td class="py-3 px-3 text-dark">
                                        <?= htmlspecialchars($gr['received_by_name'] ?? 'Warehouse Staff', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="py-3 px-3 text-muted text-truncate small" style="max-width: 250px;">
                                        <?= htmlspecialchars($gr['remarks'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="py-3 px-3 ks-col-actions text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="/purchases/receipts/<?= (int)$gr['id'] ?>" class="btn btn-outline-secondary" title="View GRN Details">
                                                <i class="bi bi-eye"></i> View GRN
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="ks-empty-state">
                                        <div class="ks-empty-icon">
                                            <i class="bi bi-box-seam fs-3"></i>
                                        </div>
                                        <div class="ks-empty-title">No goods receipts processed yet</div>
                                        <p class="ks-empty-desc">
                                            When purchase order items are delivered to the warehouse, receive them to generate Goods Receipt Notes (GRN).
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="d-flex align-items-center justify-content-between p-3 border-top" style="font-size: 13px; background: #fff;">
                <span class="text-muted">Showing page <?= $page ?> of <?= $totalPages ?> (<?= $total ?> items)</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?tab=<?= urlencode($activeTab) ?>&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">&laquo;</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?tab=<?= urlencode($activeTab) ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?tab=<?= urlencode($activeTab) ?>&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
