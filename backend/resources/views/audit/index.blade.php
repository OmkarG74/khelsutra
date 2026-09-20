<?php
$activePage = 'settings';
$title = 'Audit Logs — KhelSutra Platform';

$auditService = new \App\Services\Audit\AuditLogService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

$filters = [
    'module' => $_GET['module'] ?? null,
    'action' => $_GET['action'] ?? null,
];

$logs = $auditService->getLogs($orgId, $filters, 50);

ob_start();
?>

<!-- Page Header (Section 42 & 45) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Platform Audit Trail</h1>
        <p class="ks-page-subtitle">Immutable security ledger capturing compliance events, role modifications, and administrative operations.</p>
    </div>
    <div class="ks-header-actions">
        <span class="ks-badge ks-badge-confirmed fs-6 px-3 py-2">
            <i class="bi bi-shield-check me-1"></i>Creds Redacted
        </span>
    </div>
</div>

<div class="ks-table-card">
    <div class="ks-table-header flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-journal-check" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">System Activity Ledger</span>
        </div>
        <form method="GET" action="/audit-logs" class="d-flex align-items-center gap-2">
            <select name="module" class="ks-form-select" style="height: 36px; font-size: 13px; width: 150px;" onchange="this.form.submit()">
                <option value="">All Modules</option>
                <option value="auth" <?= ($filters['module'] === 'auth') ? 'selected' : '' ?>>Auth</option>
                <option value="organization" <?= ($filters['module'] === 'organization') ? 'selected' : '' ?>>Organization</option>
                <option value="user" <?= ($filters['module'] === 'user') ? 'selected' : '' ?>>User</option>
                <option value="employee" <?= ($filters['module'] === 'employee') ? 'selected' : '' ?>>Employee</option>
                <option value="attendance" <?= ($filters['module'] === 'attendance') ? 'selected' : '' ?>>Attendance</option>
                <option value="leave" <?= ($filters['module'] === 'leave') ? 'selected' : '' ?>>Leave</option>
                <option value="payroll" <?= ($filters['module'] === 'payroll') ? 'selected' : '' ?>>Payroll</option>
                <option value="settings" <?= ($filters['module'] === 'settings') ? 'selected' : '' ?>>Settings</option>
            </select>
        </form>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No audit events logged for this filter criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td>
                                <span class="small text-muted font-monospace"><?= htmlspecialchars($l['created_at']) ?></span>
                            </td>
                            <td>
                                <span class="fw-bold text-navy small">User #<?= htmlspecialchars((string)($l['user_id'] ?? 'Sys')) ?></span>
                            </td>
                            <td>
                                <span class="ks-badge ks-badge-blue text-capitalize"><?= htmlspecialchars($l['module']) ?></span>
                            </td>
                            <td>
                                <code class="fw-bold text-navy"><?= htmlspecialchars($l['action']) ?></code>
                            </td>
                            <td>
                                <span class="small text-navy"><?= htmlspecialchars($l['description'] ?? '—') ?></span>
                            </td>
                            <td>
                                <span class="small text-muted font-monospace"><?= htmlspecialchars($l['ip_address'] ?? '127.0.0.1') ?></span>
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
