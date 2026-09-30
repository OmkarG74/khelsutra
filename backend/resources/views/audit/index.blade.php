<?php
$activePage = 'audit';
$title = 'Audit Logs — KhelSutra Platform';

$auditService = new \App\Services\Audit\AuditLogService();
$authRole = $_SESSION['auth']['role'] ?? [];
$isSuperAdmin = ((int)($authRole['id'] ?? 0) === 1) 
             || (($authRole['slug'] ?? '') === 'super_admin') 
             || (($authRole['name'] ?? '') === 'Super Admin');

$orgId = $isSuperAdmin ? null : ($_SESSION['current_organization_id'] ?? ($_SESSION['auth']['organization']['id'] ?? 1));

$filters = [
    'module' => $_GET['module'] ?? null,
    'action' => $_GET['action'] ?? null,
];

$logs = $auditService->getLogs($orgId, 50, 0, $filters);

ob_start();
?>

<!-- Page Header (Section 42 & 45) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Platform Audit Trail</h1>
    </div>
    </div>

<?php if ($isSuperAdmin): ?>
    <?php
        $totalLogsCount = count($logs);
        $todayLogsCount = count(array_filter($logs, fn($l) => !empty($l['created_at']) && str_starts_with($l['created_at'], date('Y-m-d'))));
        $uniqueUsersCount = count(array_unique(array_filter(array_column($logs, 'user_id'))));
        $uniqueModulesCount = count(array_unique(array_filter(array_column($logs, 'module'))));
    ?>
    <div class="ks-sa-kpi-grid">
        <div class="ks-sa-kpi-card">
            <div class="ks-sa-kpi-left">
                <div class="ks-icon-box ks-icon-blue ks-sa-kpi-icon">
                    <i class="bi bi-journal-text"></i>
                </div>
                <span class="ks-sa-kpi-label">Total Events</span>
            </div>
            <div class="ks-sa-kpi-value"><?= $totalLogsCount ?></div>
        </div>

        <div class="ks-sa-kpi-card">
            <div class="ks-sa-kpi-left">
                <div class="ks-icon-box ks-icon-green ks-sa-kpi-icon">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <span class="ks-sa-kpi-label">Today</span>
            </div>
            <div class="ks-sa-kpi-value"><?= $todayLogsCount ?></div>
        </div>

        <div class="ks-sa-kpi-card">
            <div class="ks-sa-kpi-left">
                <div class="ks-icon-box ks-icon-purple ks-sa-kpi-icon">
                    <i class="bi bi-people"></i>
                </div>
                <span class="ks-sa-kpi-label">Users</span>
            </div>
            <div class="ks-sa-kpi-value"><?= $uniqueUsersCount ?></div>
        </div>

        <div class="ks-sa-kpi-card">
            <div class="ks-sa-kpi-left">
                <div class="ks-icon-box ks-icon-amber ks-sa-kpi-icon">
                    <i class="bi bi-grid-fill"></i>
                </div>
                <span class="ks-sa-kpi-label">Modules</span>
            </div>
            <div class="ks-sa-kpi-value"><?= $uniqueModulesCount ?></div>
        </div>
    </div>
<?php endif; ?>

<div class="ks-table-card">
    <div class="ks-table-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-journal-check" style="color: var(--ks-primary); font-size: 16px;"></i>
            <span class="ks-card-title mb-0">System Activity Ledger</span>
        </div>
        <form method="GET" action="/audit-logs" class="d-flex align-items-center gap-2">
            <select name="module" class="ks-form-select" style="height: 32px; font-size: 12px; width: 140px; padding: 0 8px;" onchange="this.form.submit()">
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
