<?php
$activePage = 'hr-finance';
$title = 'HR & Finance — KhelSutra';

$leaveService = new \App\Services\Leave\LeaveService();
$empService = new \App\Services\Staff\EmployeeService();
$payrollService = new \App\Services\Payroll\PayrollService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

$pendingLeaves = $leaveService->listLeaveRequests($orgId, ['status' => 'pending']);
$allEmployees = $empService->listEmployees($orgId);
$payrollPeriods = $payrollService->listPeriods($orgId);
$payrollSummary = $payrollService->getPayrollDashboardSummary($orgId);

ob_start();
?>

<!-- Page Header (Section 8: Page Title + Primary Actions) -->
<div class="ks-page-header">
    <h1 class="ks-page-title">HR & Finance</h1>
    <div class="ks-header-actions">
        <a href="/hr/employees" class="ks-btn ks-btn-secondary">
            <i class="bi bi-people"></i>
            <span>Staff Roster</span>
        </a>
        <a href="/leave" class="ks-btn ks-btn-secondary">
            <i class="bi bi-calendar2-check"></i>
            <span>Leave Requests</span>
        </a>
        <a href="/payroll" class="ks-btn ks-btn-primary">
            <i class="bi bi-wallet2"></i>
            <span>Payroll</span>
        </a>
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
                    <div class="ks-kpi-value"><?= count($pendingLeaves) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text <?= count($pendingLeaves) > 0 ? 'text-danger' : 'ks-trend-positive' ?>">
                    <?= count($pendingLeaves) > 0 ? 'Requires administrative review' : 'All leave caught up' ?>
                </span>
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
                    <div class="ks-kpi-label">Active Employees</div>
                    <div class="ks-kpi-value"><?= count($allEmployees) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Coaches & administrative staff</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green">
                    <i class="bi bi-calendar-range fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Payroll Periods</div>
                    <div class="ks-kpi-value"><?= count($payrollPeriods) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">Active compensation cycles</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Payroll Enrolled</div>
                    <div class="ks-kpi-value"><?= (int)($payrollSummary['employee_count'] ?? 0) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Assigned salary structures</span>
            </div>
        </div>
    </div>
</div>

<!-- Pending Leave Requests Table -->
<div class="ks-table-card mb-4">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar2-range" style="color: var(--ks-danger); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Pending Leave Applications (<?= count($pendingLeaves) ?>)</span>
        </div>
        <a href="/leave" class="ks-btn ks-btn-secondary" style="height: 32px; font-size: 12px;">
            View All Leaves
        </a>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Staff / Athlete</th>
                    <th>Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Days</th>
                    <th>Reason</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pendingLeaves)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-check2-circle fs-3 text-success d-block mb-1"></i>
                            No pending leave applications.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pendingLeaves as $pl): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-navy"><?= htmlspecialchars($pl['applicant_name'] ?? 'Staff Member') ?></div>
                                <div class="small text-muted"><?= htmlspecialchars(ucfirst($pl['applicant_type'] ?? 'employee')) ?></div>
                            </td>
                            <td><span class="ks-badge ks-badge-blue"><?= htmlspecialchars($pl['leave_type_name'] ?? 'Leave') ?></span></td>
                            <td><?= htmlspecialchars($pl['start_date']) ?></td>
                            <td><?= htmlspecialchars($pl['end_date']) ?></td>
                            <td><?= htmlspecialchars((string)($pl['days_count'] ?? 1)) ?> Days</td>
                            <td><span class="small text-muted"><?= htmlspecialchars($pl['reason'] ?? '—') ?></span></td>
                            <td style="text-align: right;">
                                <a href="/leave/<?= $pl['id'] ?>" class="ks-btn ks-btn-primary" style="height: 32px; padding: 0 10px; font-size: 12px;">
                                    Review Request
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
