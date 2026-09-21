<?php
$activePage = 'reports';
$title = 'Reports & Analytics — KhelSutra';

$reportService = new \App\Services\Report\ReportService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

$reports = $reportService->getOperationalReports($orgId);

$athletesBySport = $reports['athletes_by_sport'] ?? [];
$teamsBySport = $reports['teams_by_sport'] ?? [];
$attendance = $reports['attendance_stats'] ?? [];
$tournaments = $reports['tournament_activity'] ?? [];
$venues = $reports['venue_utilization'] ?? [];
$leave = $reports['leave_stats'] ?? [];
$inventory = $reports['inventory_stats'] ?? [];
$payroll = $reports['payroll_stats'] ?? [];

ob_start();
?>

<!-- Page Header (Section 8: Page Title + Primary Action) -->
<div class="ks-page-header">
    <h1 class="ks-page-title">Reports</h1>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i>
            <span>Print Report</span>
        </button>
    </div>
</div>

<!-- Operational KPI Overview -->
<div class="row g-3 mb-4">
    <!-- Training Attendance Rate -->
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-calendar-check-fill fs-4"></i>
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
                    <i class="bi bi-trophy-fill fs-4"></i>
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
                    <i class="bi bi-box-seam-fill fs-4"></i>
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
                    <i class="bi bi-currency-rupee fs-4"></i>
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

<div class="ks-reports-grid">
    <!-- Athletes Distribution by Sport -->
    <div>
        <div class="ks-card p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="fw-bold text-navy mb-0" style="font-size: 16px;">Athletes by Sport</h3>
                <span class="ks-badge ks-badge-blue"><?= count($athletesBySport) ?> Sports</span>
            </div>
            <?php if (empty($athletesBySport)): ?>
                <p class="text-muted small mb-0">No athletes registered under any sport yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="ks-table">
                        <thead>
                            <tr>
                                <th>Sport</th>
                                <th style="text-align: right;">Athlete Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($athletesBySport as $as): ?>
                                <tr>
                                    <td><span class="fw-semibold text-navy"><?= htmlspecialchars($as['sport_name']) ?></span></td>
                                    <td style="text-align: right;"><span class="ks-badge ks-badge-blue"><?= (int)$as['count'] ?> Athletes</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Teams Distribution by Sport -->
    <div>
        <div class="ks-card p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="fw-bold text-navy mb-0" style="font-size: 16px;">Teams by Sport</h3>
                <span class="ks-badge ks-badge-purple"><?= count($teamsBySport) ?> Disciplines</span>
            </div>
            <?php if (empty($teamsBySport)): ?>
                <p class="text-muted small mb-0">No teams created yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="ks-table">
                        <thead>
                            <tr>
                                <th>Sport</th>
                                <th style="text-align: right;">Active Squads</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teamsBySport as $ts): ?>
                                <tr>
                                    <td><span class="fw-semibold text-navy"><?= htmlspecialchars($ts['sport_name']) ?></span></td>
                                    <td style="text-align: right;"><span class="ks-badge ks-badge-purple"><?= (int)$ts['count'] ?> Teams</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="ks-reports-grid">
    <!-- Venue Utilization -->
    <div>
        <div class="ks-card p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="fw-bold text-navy mb-0" style="font-size: 16px;">Venue Utilization</h3>
                <span class="ks-badge ks-badge-cyan"><?= count($venues) ?> Venues</span>
            </div>
            <?php if (empty($venues)): ?>
                <p class="text-muted small mb-0">No venues configured.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="ks-table">
                        <thead>
                            <tr>
                                <th>Venue Name</th>
                                <th style="text-align: right;">Total Bookings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($venues as $v): ?>
                                <tr>
                                    <td><span class="fw-semibold text-navy"><?= htmlspecialchars($v['venue_name']) ?></span></td>
                                    <td style="text-align: right;"><span class="ks-badge ks-badge-cyan"><?= (int)$v['booking_count'] ?> Bookings</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Staff & Leave Status Summary -->
    <div>
        <div class="ks-card p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="fw-bold text-navy mb-0" style="font-size: 16px;">Staff Leave Summary</h3>
            </div>
            <div class="table-responsive">
                <table class="ks-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th style="text-align: right;">Requests</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="ks-badge ks-badge-amber">Pending Approval</span></td>
                            <td style="text-align: right;" class="fw-bold text-navy"><?= (int)($leave['pending'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td><span class="ks-badge ks-badge-green">Approved</span></td>
                            <td style="text-align: right;" class="fw-bold text-navy"><?= (int)($leave['approved'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td><span class="ks-badge ks-badge-red">Rejected</span></td>
                            <td style="text-align: right;" class="fw-bold text-navy"><?= (int)($leave['rejected'] ?? 0) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
