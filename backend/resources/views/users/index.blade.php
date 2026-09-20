<?php
$activePage = 'users';
$title = 'User Management — KhelSutra Platform';

$userService = new \App\Services\User\UserManagementService();
$orgId = $_SESSION['current_organization_id'] ?? 1;
$users = $userService->listUsers($orgId);

ob_start();
?>

<!-- Page Header (Section 19 & 49) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">User Management</h1>
        <p class="ks-page-subtitle">Platform accounts, organisation membership, RBAC roles, and security access controls.</p>
    </div>
    <div class="ks-header-actions">
        <a href="/users/create" class="ks-btn ks-btn-primary">
            <i class="bi bi-person-plus-fill"></i>
            <span>+ Create User</span>
        </a>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Total Users</div>
                    <div class="ks-kpi-value"><?= count($users) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">Active in Organisation</span>
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
                    <div class="ks-kpi-label">Active Accounts</div>
                    <div class="ks-kpi-value"><?= count(array_filter($users, fn($u) => ($u['status'] ?? '') === 'active')) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-success">Authenticated</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple">
                    <i class="bi bi-person-badge-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Assigned Roles</div>
                    <div class="ks-kpi-value">7 Standard</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">RBAC Matrix</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber">
                    <i class="bi bi-lock-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Tenant Scoped</div>
                    <div class="ks-kpi-value">Org #<?= htmlspecialchars((string)$orgId) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">Isolated Environment</span>
            </div>
        </div>
    </div>
</div>

<!-- Users Table Card -->
<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-lines-fill" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Registered Users</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="position-relative" style="width: 260px;">
                <i class="bi bi-search position-absolute" style="left: 12px; top: 12px; color: var(--ks-text-muted); font-size: 13px;"></i>
                <input type="text" class="ks-form-control" style="padding-left: 34px; height: 38px; font-size: 13px;" placeholder="Search user by name, email...">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email / Username</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No users found for this organisation.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="ks-avatar" style="width: 34px; height: 34px; border-radius: 50%; background: #0E1E3B; color: #FFF; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 13px;">
                                        <?= strtoupper(substr($u['first_name'] ?? 'U', 0, 1) . substr($u['last_name'] ?? 'S', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-navy"><?= htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($u['uuid'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold text-navy"><?= htmlspecialchars($u['email']) ?></div>
                                <div class="small text-muted">@<?= htmlspecialchars($u['username'] ?? '') ?></div>
                            </td>
                            <td>
                                <span class="small text-navy"><?= htmlspecialchars($u['phone'] ?? '—') ?></span>
                            </td>
                            <td>
                                <span class="ks-badge ks-badge-blue"><?= htmlspecialchars($u['role_name'] ?? 'Unassigned') ?></span>
                            </td>
                            <td>
                                <?php if (($u['status'] ?? '') === 'active'): ?>
                                    <span class="ks-badge ks-badge-confirmed">Active</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-rejected"><?= htmlspecialchars(ucfirst($u['status'] ?? 'Inactive')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="small text-muted"><?= htmlspecialchars($u['last_login_at'] ?? 'Never') ?></span>
                            </td>
                            <td style="text-align: right;">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="/users/<?= $u['id'] ?>" class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">
                                        View
                                    </a>
                                    <a href="/users/<?= $u['id'] ?>/edit" class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">
                                        Edit
                                    </a>
                                </div>
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
