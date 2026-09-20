<?php
$activePage = 'reports';
$title = 'Reports & Analytics — KhelSutra';

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Reports & Analytics</h1>
        <p class="ks-page-subtitle">Generate operational audits, athlete fitness progress records, attendance analysis, and financial reports.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-secondary" onclick="alert('Exporting PDF Reports...');">
            <i class="bi bi-file-earmark-pdf"></i>
            <span>Export PDF</span>
        </button>
        <button class="ks-btn ks-btn-primary" onclick="alert('Running Report Generator...');">
            <i class="bi bi-play-fill"></i>
            <span>Generate Report</span>
        </button>
    </div>
</div>

<!-- Report Categories Grid -->
<div class="row g-3 mb-4">
    <!-- Category 1 -->
    <div class="col-lg-4 col-md-6">
        <div class="ks-card p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="ks-icon-box ks-icon-blue" style="width: 46px; height: 46px; border-radius: 12px;">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-navy mb-0" style="font-size: 16px;">Athlete Performance</h4>
                    <span class="small text-muted">Fitness benchmarks & match stats</span>
                </div>
            </div>
            <p class="small text-muted mb-4">
                Detailed progression reports covering VO2 max, sprint timings, match ratings, and disciplinary logs across squads.
            </p>
            <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ks-border-light) !important;">
                <span class="small text-muted">Last generated 2 days ago</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Run Report</button>
            </div>
        </div>
    </div>

    <!-- Category 2 -->
    <div class="col-lg-4 col-md-6">
        <div class="ks-card p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="ks-icon-box ks-icon-green" style="width: 46px; height: 46px; border-radius: 12px;">
                    <i class="bi bi-calendar-check-fill fs-4"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-navy mb-0" style="font-size: 16px;">Attendance & Sessions</h4>
                    <span class="small text-muted">Training slot compliance</span>
                </div>
            </div>
            <p class="small text-muted mb-4">
                Monthly biometric attendance summaries, coach session logs, and drill completion percentages by age group.
            </p>
            <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ks-border-light) !important;">
                <span class="small text-muted">Updated today, 10:00 AM</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Run Report</button>
            </div>
        </div>
    </div>

    <!-- Category 3 -->
    <div class="col-lg-4 col-md-6">
        <div class="ks-card p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="ks-icon-box ks-icon-purple" style="width: 46px; height: 46px; border-radius: 12px;">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-navy mb-0" style="font-size: 16px;">Financial & Fee Ledger</h4>
                    <span class="small text-muted">Collections & expenditures</span>
                </div>
            </div>
            <p class="small text-muted mb-4">
                Fee payments, outstanding dues by cohort, payroll disbursements, and equipment procurement summaries.
            </p>
            <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ks-border-light) !important;">
                <span class="small text-muted">Monthly cycle closing</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Run Report</button>
            </div>
        </div>
    </div>
</div>

<!-- Recent Generated Reports Table -->
<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Recently Generated Reports</span>
        </div>
        <button class="ks-btn ks-btn-secondary" style="height: 32px; font-size: 12px;">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Report Title</th>
                    <th>Module</th>
                    <th>Date Generated</th>
                    <th>Generated By</th>
                    <th>Format</th>
                    <th style="text-align: right;">Download</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="fw-semibold text-navy">September 2026 Sports Operations Summary</span></td>
                    <td><span class="ks-badge ks-badge-blue">Operations</span></td>
                    <td>23 Sep 2026, 09:30</td>
                    <td>Sports Administrator</td>
                    <td>PDF (2.4 MB)</td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;"><i class="bi bi-download"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><span class="fw-semibold text-navy">Titans U-18 Biometric Fitness & Attendance</span></td>
                    <td><span class="ks-badge ks-badge-cyan">Training</span></td>
                    <td>22 Sep 2026, 18:15</td>
                    <td>Coach Rajesh</td>
                    <td>CSV (140 KB)</td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;"><i class="bi bi-download"></i></button>
                    </td>
                </tr>
                <tr>
                    <td><span class="fw-semibold text-navy">Q3 Equipment Audit & Wear-Tear Analysis</span></td>
                    <td><span class="ks-badge ks-badge-purple">Inventory</span></td>
                    <td>20 Sep 2026, 14:00</td>
                    <td>Inventory Manager</td>
                    <td>PDF (1.1 MB)</td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;"><i class="bi bi-download"></i></button>
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
