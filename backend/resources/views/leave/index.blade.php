<?php
$activePage = 'hr-finance';
$title = 'Leave Management — KhelSutra Platform';

$leaveService = new \App\Services\Leave\LeaveService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

$leaveTypes = $leaveService->listLeaveTypes($orgId);

$filters = [
    'status' => $_GET['status'] ?? null,
    'applicant_type' => $_GET['applicant_type'] ?? null,
];

$leaveRequests = $leaveService->listLeaveRequests($orgId, $filters);

ob_start();
?>

<!-- Page Header (Section 33 & 51) -->
<div class="ks-page-header">
    <h1 class="ks-page-title">Leave Management</h1>
    <div class="ks-header-actions">
        <a href="/leave/create" class="ks-btn ks-btn-primary">
            <i class="bi bi-calendar-plus-fill"></i>
            <span>+ Apply Leave</span>
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber">
                    <i class="bi bi-hourglass-split fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Pending Approval</div>
                    <div class="ks-kpi-value"><?= count(array_filter($leaveRequests, fn($r) => $r['status'] === 'pending')) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-warning">Requires HR Action</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green">
                    <i class="bi bi-calendar-check-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Approved</div>
                    <div class="ks-kpi-value"><?= count(array_filter($leaveRequests, fn($r) => $r['status'] === 'approved')) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-success">Active Leave</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-collection-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Leave Types</div>
                    <div class="ks-kpi-value"><?= count($leaveTypes) ?> Policies</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">Configured Policies</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple">
                    <i class="bi bi-shield-check fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Separation of Duties</div>
                    <div class="ks-kpi-value">Enforced</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">No Self-Approval</span>
            </div>
        </div>
    </div>
</div>

<!-- Leave Requests Table (Section 51) -->
<div class="ks-table-card">
    <div class="ks-table-header flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar3" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Leave Applications</span>
        </div>
        <form method="GET" action="/leave" class="d-flex align-items-center gap-2">
            <select name="status" class="ks-form-select" style="height: 36px; font-size: 13px; width: 140px;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" <?= ($filters['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= ($filters['status'] === 'approved') ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= ($filters['status'] === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                <option value="cancelled" <?= ($filters['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <select name="applicant_type" class="ks-form-select" style="height: 36px; font-size: 13px; width: 140px;" onchange="this.form.submit()">
                <option value="">All Applicants</option>
                <option value="employee" <?= ($filters['applicant_type'] === 'employee') ? 'selected' : '' ?>>Employee / Coach</option>
                <option value="athlete" <?= ($filters['applicant_type'] === 'athlete') ? 'selected' : '' ?>>Athlete</option>
            </select>
        </form>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Applicant Type</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Total Days</th>
                    <th>Status</th>
                    <th>Applied Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaveRequests)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No leave applications found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leaveRequests as $req): ?>
                        <tr>
                            <td>
                                <div>
                                    <span class="fw-bold text-navy"><?= htmlspecialchars($req['applicant_name'] ?? 'Applicant') ?></span>
                                    <div class="small text-muted"><?= htmlspecialchars($req['department_name'] ?? 'Org Member') ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="ks-badge ks-badge-blue text-capitalize"><?= htmlspecialchars($req['applicant_type']) ?></span>
                            </td>
                            <td>
                                <span class="fw-semibold text-navy small"><?= htmlspecialchars($req['leave_type_name'] ?? 'General') ?></span>
                            </td>
                            <td>
                                <span class="small text-navy font-monospace"><?= htmlspecialchars($req['start_date']) ?></span>
                            </td>
                            <td>
                                <span class="small text-navy font-monospace"><?= htmlspecialchars($req['end_date']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark fw-bold"><?= htmlspecialchars((string)$req['total_days']) ?> Days</span>
                            </td>
                            <td>
                                <?php if ($req['status'] === 'pending'): ?>
                                    <span class="ks-badge ks-badge-pending">Pending</span>
                                <?php elseif ($req['status'] === 'approved'): ?>
                                    <span class="ks-badge ks-badge-confirmed">Approved</span>
                                <?php elseif ($req['status'] === 'rejected'): ?>
                                    <span class="ks-badge ks-badge-rejected">Rejected</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-scheduled"><?= htmlspecialchars(ucfirst($req['status'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="small text-muted"><?= htmlspecialchars(substr($req['created_at'] ?? '', 0, 10)) ?></span>
                            </td>
                            <td style="text-align: right;">
                                <a href="/leave/<?= $req['id'] ?>" class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">
                                    View / Review
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
