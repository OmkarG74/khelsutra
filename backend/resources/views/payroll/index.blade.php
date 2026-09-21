<?php
$activePage = 'hr-finance';
$title = 'Payroll Management — KhelSutra Platform';

$payrollService = new \App\Services\Payroll\PayrollService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

$summary = $payrollService->getPayrollDashboardSummary($orgId);
$records = $payrollService->listPayrollRecords($orgId);
$periods = $payrollService->listPeriods($orgId);

ob_start();
?>

<!-- Page Header (Section 36 & 52) -->
<div class="ks-page-header">
    <h1 class="ks-page-title">Payroll</h1>
    <div class="ks-header-actions">
        <a href="/payroll/periods" class="ks-btn ks-btn-secondary">
            <i class="bi bi-calendar-range"></i>
            <span>Payroll Periods</span>
        </a>
        <a href="/payroll/salary-structures" class="ks-btn ks-btn-secondary">
            <i class="bi bi-sliders"></i>
            <span>Salary Structures</span>
        </a>
    </div>
</div>

<!-- KPI Dashboard Cards (Section 52: Values from database) -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Employees in Payroll</div>
                    <div class="ks-kpi-value"><?= htmlspecialchars((string)($summary['employee_count'] ?? 0)) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive"><?= htmlspecialchars($summary['current_period_name'] ?? 'Active Period') ?></span>
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
                    <div class="ks-kpi-label">Gross Salary</div>
                    <div class="ks-kpi-value">₹<?= number_format((float)($summary['total_gross'] ?? 0), 2) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-success">Basic + Allowances + OT + Bonus</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber">
                    <i class="bi bi-scissors fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Total Deductions</div>
                    <div class="ks-kpi-value">₹<?= number_format((float)($summary['total_deductions'] ?? 0), 2) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-warning">Tax + Deductions + Other</span>
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
                    <div class="ks-kpi-label">Net Salary Disbursed</div>
                    <div class="ks-kpi-value">₹<?= number_format((float)($summary['total_net'] ?? 0), 2) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Pending Payments: <?= htmlspecialchars((string)($summary['pending_payments'] ?? 0)) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Payroll Records Table (Section 52) -->
<div class="ks-table-card">
    <div class="ks-table-header flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-receipt" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Disbursement Roster</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table ks-table-payroll">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Period</th>
                    <th class="text-end ks-col-money">Gross</th>
                    <th class="text-end ks-col-money">Deductions</th>
                    <th class="text-end ks-col-money">Net Salary</th>
                    <th>Payment Status</th>
                    <th>Payment Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No payroll disbursement records generated yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $p): ?>
                        <tr>
                            <td>
                                <div>
                                    <span class="fw-bold text-navy"><?= htmlspecialchars(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')) ?></span>
                                    <div class="small text-muted"><?= htmlspecialchars($p['employee_code'] ?? '') ?> • <?= htmlspecialchars($p['designation'] ?? 'Staff') ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark fw-bold"><?= htmlspecialchars($p['period_name'] ?? 'Period') ?></span>
                            </td>
                            <td class="text-end ks-col-money">
                                <span class="fw-bold text-navy">₹<?= number_format((float)$p['gross_salary'], 2) ?></span>
                            </td>
                            <td class="text-end ks-col-money">
                                <span class="small text-danger">₹<?= number_format((float)$p['tax'] + (float)$p['deductions'] + (float)$p['other_deductions'], 2) ?></span>
                            </td>
                            <td class="text-end ks-col-money">
                                <span class="fw-bold text-success">₹<?= number_format((float)$p['net_salary'], 2) ?></span>
                            </td>
                            <td>
                                <?php if ($p['payment_status'] === 'paid'): ?>
                                    <span class="ks-badge ks-badge-confirmed">Paid</span>
                                <?php elseif ($p['payment_status'] === 'processed'): ?>
                                    <span class="ks-badge ks-badge-blue">Processed</span>
                                <?php elseif ($p['payment_status'] === 'cancelled'): ?>
                                    <span class="ks-badge ks-badge-rejected">Cancelled</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-pending">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="small text-muted"><?= htmlspecialchars($p['payment_date'] ?? '—') ?></span>
                            </td>
                            <td style="text-align: right;">
                                <a href="/payroll/<?= $p['id'] ?>" class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">
                                    View Slip
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
