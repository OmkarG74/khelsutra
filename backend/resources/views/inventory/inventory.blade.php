<?php
$activePage = 'inventory';
$title = 'Inventory & Equipment — KhelSutra';

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Inventory & Equipment</h1>
        <p class="ks-page-subtitle">Track athletic gear, low-stock threshold warnings, equipment issues, and vendor procurement.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-secondary" onclick="alert('Generating Purchase Order...');">
            <i class="bi bi-cart-plus"></i>
            <span>Purchase Order</span>
        </button>
        <button class="ks-btn ks-btn-primary" onclick="alert('Open Add Equipment Dialog');">
            <i class="bi bi-plus-lg"></i>
            <span>+ Add Equipment</span>
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber">
                    <i class="bi bi-box-seam-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Low Inventory</div>
                    <div class="ks-kpi-value">3</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-danger">Items below safety threshold</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 10C22 18 42 8 62 22C74 24 82 18 88 12" stroke="#EF4444" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-tags-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Total SKUs Tracked</div>
                    <div class="ks-kpi-value">148</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">Gear across 6 sports</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 22C18 20 28 26 44 14C60 2 72 16 88 4" stroke="#0B6EF3" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green">
                    <i class="bi bi-shield-check fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">In Good Condition</div>
                    <div class="ks-kpi-value">96.4%</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Inspected weekly by staff</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 14C22 10 42 18 62 12C74 8 82 14 88 6" stroke="#16A34A" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple">
                    <i class="bi bi-arrow-repeat fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Active Checkouts</div>
                    <div class="ks-kpi-value">46</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Issued to teams today</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 20C16 16 34 8 52 14C70 20 78 12 88 6" stroke="#7C3AED" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Low Inventory Warning Banner -->
<div class="ks-card p-3 mb-4 d-flex align-items-center justify-content-between" style="background: #FEF3C7; border: 1px solid #FDE68A;">
    <div class="d-flex align-items-center gap-3">
        <div class="ks-icon-box ks-icon-amber" style="background: #FFFFFF;">
            <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
        </div>
        <div>
            <div class="fw-bold text-navy" style="font-size: 15px;">Low Stock Alert: 3 items below minimum threshold</div>
            <div class="small text-muted">Football Match Balls (Size 5), Feather Shuttles (Aerosensa 30), and Cones require immediate reorder.</div>
        </div>
    </div>
    <button class="ks-btn ks-btn-primary" style="background: #D97706; border-color: #D97706;">
        <i class="bi bi-cart-check"></i> Reorder Now
    </button>
</div>

<!-- Equipment Table Card -->
<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-box-seam" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Equipment Catalog & Stock Status</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="position-relative" style="width: 260px;">
                <i class="bi bi-search position-absolute" style="left: 12px; top: 12px; color: var(--ks-text-muted); font-size: 13px;"></i>
                <input type="text" class="ks-form-control" style="padding-left: 34px; height: 38px; font-size: 13px;" placeholder="Search equipment SKU...">
            </div>
            <select class="ks-form-select" style="width: 140px; height: 38px; font-size: 13px;">
                <option value="">All Sports</option>
                <option value="football">Football</option>
                <option value="cricket">Cricket</option>
                <option value="badminton">Badminton</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th>SKU Code</th>
                    <th>Category</th>
                    <th>In Stock</th>
                    <th>Reorder Level</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="fw-semibold text-navy">FIFA Quality Pro Footballs (Size 5)</div>
                        <div class="small text-muted">Manufacturer: Mitre / Match Balls</div>
                    </td>
                    <td><span class="fw-medium text-navy">EQ-FB-002</span></td>
                    <td>Football Gear</td>
                    <td><strong class="text-danger">4 Units</strong></td>
                    <td>15 Units</td>
                    <td><span class="ks-badge ks-badge-rejected">Critical Low</span></td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">Restock</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="fw-semibold text-navy">Yonex Aerosensa 30 Feather Shuttles</div>
                        <div class="small text-muted">Box of 12 Tubes</div>
                    </td>
                    <td><span class="fw-medium text-navy">EQ-BD-015</span></td>
                    <td>Badminton Gear</td>
                    <td><strong class="text-warning">2 Boxes</strong></td>
                    <td>10 Boxes</td>
                    <td><span class="ks-badge ks-badge-pending">Low Stock</span></td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">Restock</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="fw-semibold text-navy">Agility Training Marker Cones (Set of 50)</div>
                        <div class="small text-muted">Multi-Color Saucer Cones</div>
                    </td>
                    <td><span class="fw-medium text-navy">EQ-TR-008</span></td>
                    <td>Training Gear</td>
                    <td><strong class="text-warning">3 Sets</strong></td>
                    <td>8 Sets</td>
                    <td><span class="ks-badge ks-badge-pending">Low Stock</span></td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">Restock</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="fw-semibold text-navy">SG Club Leather Cricket Balls (Red)</div>
                        <div class="small text-muted">4-Piece Match Grade Leather</div>
                    </td>
                    <td><span class="fw-medium text-navy">EQ-CR-001</span></td>
                    <td>Cricket Gear</td>
                    <td><strong class="text-success">42 Units</strong></td>
                    <td>20 Units</td>
                    <td><span class="ks-badge ks-badge-confirmed">Adequate</span></td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">Manage</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Table Footer -->
    <div class="p-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ks-border-light) !important;">
        <div class="small text-muted">Showing 4 of 148 equipment items</div>
        <div class="d-flex gap-1">
            <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;" disabled>Previous</button>
            <button class="ks-btn ks-btn-primary" style="height: 32px; padding: 0 12px; font-size: 12px;">1</button>
            <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Next</button>
        </div>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
