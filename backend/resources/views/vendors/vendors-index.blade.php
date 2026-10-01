<?php
$pageTitle = 'Vendors & Suppliers — KhelSutra';
$activePage = 'vendors';
$orgId = current_organization_id();

$vendorService = new \App\Services\Vendor\VendorService();
$page = (int)($_GET['page'] ?? 1);
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$vendorType = trim($_GET['vendor_type'] ?? '');

$result = $vendorService->listVendors($orgId, $page, 15, $search ?: null, $status ?: null, $vendorType ?: null);
$vendors = $result['data'] ?? [];
$total = $result['total'] ?? 0;
$totalPages = $result['total_pages'] ?? 1;

$db = \App\Services\BaseService::getDatabaseConnection();

// KPI Stats
$statStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_vendors,
        SUM(CASE WHEN v.status = 'active' THEN 1 ELSE 0 END) as active_vendors,
        (SELECT COALESCE(SUM(vi.total_amount), 0) FROM vendor_invoices vi WHERE vi.organization_id = :org_id) as total_invoiced,
        (SELECT COUNT(*) FROM vendor_invoices vi WHERE vi.organization_id = :org_id AND vi.payment_status = 'unpaid') as unpaid_invoices_count
    FROM vendors v
    WHERE v.organization_id = :org_id AND v.deleted_at IS NULL
");
$statStmt->execute([':org_id' => $orgId]);
$stats = $statStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_vendors' => 0, 'active_vendors' => 0, 'total_invoiced' => 0, 'unpaid_invoices_count' => 0];

// Distinct vendor types for filter dropdown
$typesStmt = $db->prepare("
    SELECT DISTINCT vendor_type 
    FROM vendors 
    WHERE organization_id = :org_id AND vendor_type IS NOT NULL AND vendor_type != '' AND deleted_at IS NULL
    ORDER BY vendor_type ASC
");
$typesStmt->execute([':org_id' => $orgId]);
$vendorTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

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
            <h1 class="ks-page-title mb-1">Vendors & Suppliers</h1>
            <p class="ks-page-subtitle">Manage approved suppliers, procurement partners, and vendor invoices</p>
        </div>
        <div class="ks-header-actions">
            <a href="/vendors/create" class="ks-btn ks-btn-primary">
                <i class="bi bi-plus-lg"></i>
                <span>Add Vendor</span>
            </a>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="ks-kpi-card">
                <div class="ks-kpi-top">
                    <div class="ks-icon-box ks-icon-blue">
                        <i class="bi bi-truck fs-4"></i>
                    </div>
                    <div>
                        <div class="ks-kpi-label">TOTAL VENDORS</div>
                        <div class="ks-kpi-value"><?= number_format($stats['total_vendors'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="ks-kpi-bottom">
                    <span class="ks-trend-text text-muted">Registered supplier partners</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="ks-kpi-card">
                <div class="ks-kpi-top">
                    <div class="ks-icon-box ks-icon-green">
                        <i class="bi bi-check2-circle fs-4"></i>
                    </div>
                    <div>
                        <div class="ks-kpi-label">ACTIVE SUPPLIERS</div>
                        <div class="ks-kpi-value text-success"><?= number_format($stats['active_vendors'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="ks-kpi-bottom">
                    <span class="ks-trend-text ks-trend-positive">Authorized for procurement</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="ks-kpi-card">
                <div class="ks-kpi-top">
                    <div class="ks-icon-box ks-icon-purple">
                        <i class="bi bi-receipt fs-4"></i>
                    </div>
                    <div>
                        <div class="ks-kpi-label">TOTAL INVOICED</div>
                        <div class="ks-kpi-value">₹<?= number_format((float)($stats['total_invoiced'] ?? 0), 2) ?></div>
                    </div>
                </div>
                <div class="ks-kpi-bottom">
                    <span class="ks-trend-text text-muted">Lifetime vendor billing</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="ks-kpi-card">
                <div class="ks-kpi-top">
                    <div class="ks-icon-box ks-icon-amber">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                    <div>
                        <div class="ks-kpi-label">UNPAID INVOICES</div>
                        <div class="ks-kpi-value <?= ($stats['unpaid_invoices_count'] ?? 0) > 0 ? 'text-warning' : '' ?>"><?= number_format($stats['unpaid_invoices_count'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="ks-kpi-bottom">
                    <span class="ks-trend-text <?= ($stats['unpaid_invoices_count'] ?? 0) > 0 ? 'ks-trend-negative' : 'text-muted' ?>">Pending payment settlement</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filters Toolbar -->
    <div class="ks-filter-bar mb-4">
        <form method="GET" action="/vendors">
            <div style="position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--ks-text-muted); font-size: 13px; pointer-events: none;"></i>
                <input type="text" name="search" class="form-control ks-form-control" placeholder="Search company name, code, contact person, city, GSTIN..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" style="padding-left: 36px;">
            </div>
            <div>
                <select name="status" class="form-select ks-form-select">
                    <option value="">All Vendor Statuses</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="blacklisted" <?= $status === 'blacklisted' ? 'selected' : '' ?>>Blacklisted</option>
                </select>
            </div>
            <div>
                <select name="vendor_type" class="form-select ks-form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($vendorTypes as $vt): ?>
                        <option value="<?= htmlspecialchars($vt, ENT_QUOTES, 'UTF-8') ?>" <?= $vendorType === $vt ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $vt)), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="ks-btn ks-btn-primary">
                    <i class="bi bi-funnel"></i>
                    <span>Filter</span>
                </button>
                <?php if ($search || $status || $vendorType): ?>
                    <a href="/vendors" class="ks-btn ks-btn-secondary" title="Reset all filters">
                        <i class="bi bi-x-circle"></i>
                        <span>Clear</span>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Vendors Table Card -->
    <div class="ks-table-card">
        <div class="ks-table-header">
            <div class="ks-header-left">
                <i class="bi bi-truck text-primary fs-5"></i>
                <h3 class="ks-header-title">Vendor Directory</h3>
                <span class="badge bg-light text-secondary border ms-2 fw-medium" style="font-size: 11px;">
                    <?= number_format($total) ?> <?= $total === 1 ? 'partner' : 'partners' ?>
                </span>
            </div>
            <?php if ($search || $status || $vendorType): ?>
                <div class="d-flex align-items-center gap-2 small">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Filtered</span>
                    <a href="/vendors" class="text-muted text-decoration-none small"><i class="bi bi-x"></i> Reset</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="ks-table-responsive">
            <table class="table ks-table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="min-width: 250px;">Company / Vendor</th>
                        <th style="min-width: 150px;">Contact Person</th>
                        <th style="min-width: 180px;">Communication</th>
                        <th style="min-width: 140px;">Location</th>
                        <th class="ks-col-money" style="min-width: 150px;">Billing & Invoices</th>
                        <th style="min-width: 100px;">Status</th>
                        <th class="ks-col-actions" style="min-width: 130px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vendors)): ?>
                        <?php foreach ($vendors as $v): ?>
                            <tr>
                                <td class="py-3 px-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background: var(--ks-primary-light); color: var(--ks-primary); font-size: 17px;">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <div>
                                            <a href="/vendors/<?= (int)$v['id'] ?>" class="fw-semibold text-dark text-decoration-none d-block">
                                                <?= htmlspecialchars($v['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                            <div class="d-flex align-items-center gap-1 mt-1">
                                                <span class="badge bg-light text-secondary border" style="font-family: monospace; font-size: 11px; padding: 2px 6px;">
                                                    <?= htmlspecialchars($v['vendor_code'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <?php if (!empty($v['vendor_type'])): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size: 10.5px; padding: 2px 6px;">
                                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $v['vendor_type'])), ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <?php if (!empty($v['contact_person'])): ?>
                                        <div class="text-dark fw-medium"><?= htmlspecialchars($v['contact_person'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic small">Not specified</span>
                                    <?php endif; ?>
                                    <?php if (!empty($v['gst_number'])): ?>
                                        <div class="text-muted" style="font-size: 11px; font-family: monospace;">GST: <?= htmlspecialchars($v['gst_number'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-3">
                                    <?php if (!empty($v['phone'])): ?>
                                        <div class="small">
                                            <a href="tel:<?= htmlspecialchars($v['phone'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-secondary">
                                                <i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($v['phone'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($v['email'])): ?>
                                        <div class="small mt-1">
                                            <a href="mailto:<?= htmlspecialchars($v['email'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-secondary">
                                                <i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($v['email'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (empty($v['phone']) && empty($v['email'])): ?>
                                        <span class="text-muted fst-italic small">No contact details</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="text-secondary small d-flex align-items-center gap-1">
                                        <i class="bi bi-geo-alt text-muted"></i>
                                        <span><?= htmlspecialchars(trim(($v['city'] ?? '') . ', ' . ($v['state'] ?? '')) ?: 'India', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 ks-col-money">
                                    <div class="fw-semibold text-dark">₹<?= number_format((float)($v['total_invoiced_amount'] ?? 0), 2) ?></div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        <?= (int)($v['total_invoices_count'] ?? 0) ?> <?= ((int)($v['total_invoices_count'] ?? 0) === 1) ? 'bill' : 'bills' ?>
                                        <?php if ((int)($v['unpaid_invoices_count'] ?? 0) > 0): ?>
                                            &bull; <span class="text-danger fw-medium"><?= (int)$v['unpaid_invoices_count'] ?> unpaid</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <?php
                                    $vBadge = match($v['status']) {
                                        'active' => 'bg-success-subtle text-success border border-success-subtle',
                                        'inactive' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                        'blacklisted' => 'bg-danger text-white',
                                        default => 'bg-light text-dark border'
                                    };
                                    ?>
                                    <span class="badge <?= $vBadge ?> fw-semibold" style="font-size: 11px; padding: 4px 8px;">
                                        <?= htmlspecialchars(ucfirst($v['status']), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 ks-col-actions text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/vendors/<?= (int)$v['id'] ?>" class="btn btn-outline-secondary" title="View Profile & Invoices">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="/vendors/<?= (int)$v['id'] ?>/edit" class="btn btn-outline-secondary" title="Edit Vendor">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="/vendors/<?= (int)$v['id'] ?>/status" class="d-inline" style="display: contents;">
                                            <?php if ($v['status'] === 'active'): ?>
                                                <input type="hidden" name="status" value="inactive">
                                                <button type="submit" class="btn btn-outline-secondary text-warning" title="Deactivate Vendor">
                                                    <i class="bi bi-pause-circle"></i>
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="btn btn-outline-secondary text-success" title="Activate Vendor">
                                                    <i class="bi bi-play-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                        <form method="POST" action="/vendors/<?= (int)$v['id'] ?>/delete" class="d-inline" style="display: contents;" onsubmit="return confirm('Are you sure you want to remove this vendor?');">
                                            <button type="submit" class="btn btn-outline-secondary text-danger" title="Delete Vendor">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="ks-empty-state">
                                    <div class="ks-empty-icon">
                                        <i class="bi bi-truck fs-3"></i>
                                    </div>
                                    <div class="ks-empty-title">No vendors found</div>
                                    <p class="ks-empty-desc">
                                        <?= ($search || $status || $vendorType) 
                                            ? 'No suppliers match your active filter criteria. Try clearing search or status filters.' 
                                            : 'Add equipment suppliers, merchandise vendors, and service contractors to manage procurement.' ?>
                                    </p>
                                    <?php if ($search || $status || $vendorType): ?>
                                        <a href="/vendors" class="ks-btn ks-btn-secondary">
                                            <i class="bi bi-x-circle me-1"></i> Clear Filters
                                        </a>
                                    <?php else: ?>
                                        <a href="/vendors/create" class="ks-btn ks-btn-primary">
                                            <i class="bi bi-plus-lg me-1"></i> Add First Vendor
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="d-flex align-items-center justify-content-between p-3 border-top" style="font-size: 13px; background: #fff;">
                <span class="text-muted">Showing page <?= $page ?> of <?= $totalPages ?> (<?= $total ?> items)</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&vendor_type=<?= urlencode($vendorType) ?>">&laquo;</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&vendor_type=<?= urlencode($vendorType) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&vendor_type=<?= urlencode($vendorType) ?>">&raquo;</a>
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
