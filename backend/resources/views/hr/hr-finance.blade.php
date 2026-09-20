<?php
$activePage = 'hr_finance';
$title = 'HR & Finance Operations — KhelSutra';

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">HR & Finance</h1>
        <p class="ks-page-subtitle">Academy staff payroll, biometric attendance, pending leave approvals, and fee collections.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-secondary" onclick="alert('Exporting Payroll Summary...');">
            <i class="bi bi-file-earmark-excel"></i>
            <span>Export Payroll</span>
        </button>
        <button class="ks-btn ks-btn-primary" onclick="alert('Processing Leaves...');">
            <i class="bi bi-check2-circle"></i>
            <span>Approve Leaves (2)</span>
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-red">
                    <i class="bi bi-calendar-x-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Pending Leave</div>
                    <div class="ks-kpi-value">2</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-danger">Awaiting HR approval</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 14C22 18 42 6 62 20C74 24 82 14 88 8" stroke="#EF4444" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green">
                    <i class="bi bi-currency-rupee fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Fee Collection (Sep)</div>
                    <div class="ks-kpi-value">₹8,45,000</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">+8.4% vs last month</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 22C18 20 28 26 44 14C60 2 72 16 88 4" stroke="#16A34A" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Total Staff & Coaches</div>
                    <div class="ks-kpi-value">24</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">12 Coaches, 12 Support</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 18C20 18 30 24 50 16C70 8 78 4 88 12" stroke="#0B6EF3" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Payroll Disbursed</div>
                    <div class="ks-kpi-value">100%</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">August cycle completed</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 20C16 16 34 8 52 14C70 20 78 12 88 6" stroke="#7C3AED" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Pending Leave Requests Table -->
<div class="ks-table-card mb-4">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar2-range" style="color: var(--ks-danger); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Pending Leave Applications (2)</span>
        </div>
        <span class="ks-badge ks-badge-pending">Action Required</span>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Staff Member</th>
                    <th>Role / Department</th>
                    <th>Leave Type</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>Reason</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="fw-semibold text-navy">Coach Rajesh Kumar</div>
                        <div class="small text-muted">Football Department</div>
                    </td>
                    <td>Head Coach</td>
                    <td><span class="ks-badge ks-badge-blue">Personal Leave</span></td>
                    <td>26 Sep – 27 Sep 2026</td>
                    <td>2 Days</td>
                    <td>Family ceremony in hometown</td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-primary" style="height: 32px; padding: 0 10px; font-size: 12px;">Approve</button>
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">Reject</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="fw-semibold text-navy">Kavita Salunkhe</div>
                        <div class="small text-muted">Physiotherapy & Medical</div>
                    </td>
                    <td>Lead Physio</td>
                    <td><span class="ks-badge ks-badge-cyan">Medical Leave</span></td>
                    <td>24 Sep 2026</td>
                    <td>1 Day</td>
                    <td>Attending National Sports Medicine Conference</td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-primary" style="height: 32px; padding: 0 10px; font-size: 12px;">Approve</button>
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">Reject</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
