<?php
$activePage = 'users';
$title = 'User Details — KhelSutra Platform';

$userId = $id ?? 1;
$userService = new \App\Services\User\UserManagementService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

try {
    $user = $userService->getUserDetails((int)$userId, (int)$orgId);
} catch (\Throwable $e) {
    $user = null;
}

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/users" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Users</a>
        </div>
        <h1 class="ks-page-title"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? 'User Details')) ?></h1>
        <p class="ks-page-subtitle">User account profile, role membership, and activity history.</p>
    </div>
    <div class="ks-header-actions">
        <?php if ($user): ?>
            <a href="/users/<?= $user['id'] ?>/edit" class="ks-btn ks-btn-primary">
                <i class="bi bi-pencil-square"></i>
                <span>Edit User</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$user): ?>
    <div class="ks-card p-5 text-center text-muted">
        <i class="bi bi-person-x fs-1 text-danger"></i>
        <h5 class="mt-3">User Not Found</h5>
        <p>The requested user does not exist or does not belong to your active organisation context.</p>
        <a href="/users" class="ks-btn ks-btn-secondary mt-2">Return to Users</a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <!-- Left: Profile Summary -->
        <div class="col-lg-4">
            <div class="ks-card p-4 text-center">
                <div class="ks-avatar mx-auto mb-3" style="width: 72px; height: 72px; border-radius: 50%; background: #0E1E3B; color: #FFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 24px;">
                    <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? 'S', 0, 1)) ?>
                </div>
                <h4 class="fw-bold text-navy mb-1"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                <p class="text-muted small mb-3">@<?= htmlspecialchars($user['username']) ?></p>

                <div class="d-flex justify-content-center gap-2 mb-4">
                    <span class="ks-badge ks-badge-blue"><?= htmlspecialchars($user['role_name'] ?? 'No Role') ?></span>
                    <?php if ($user['status'] === 'active'): ?>
                        <span class="ks-badge ks-badge-confirmed">Active</span>
                    <?php else: ?>
                        <span class="ks-badge ks-badge-rejected"><?= htmlspecialchars(ucfirst($user['status'])) ?></span>
                    <?php endif; ?>
                </div>

                <hr style="border-color: var(--ks-border-light);">

                <div class="text-start">
                    <div class="mb-3">
                        <span class="ks-kpi-label">User UUID</span>
                        <div class="small fw-semibold text-navy font-monospace"><?= htmlspecialchars($user['uuid']) ?></div>
                    </div>
                    <div class="mb-3">
                        <span class="ks-kpi-label">Email Address</span>
                        <div class="small fw-semibold text-navy"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <div class="mb-3">
                        <span class="ks-kpi-label">Phone</span>
                        <div class="small fw-semibold text-navy"><?= htmlspecialchars($user['phone'] ?? '—') ?></div>
                    </div>
                    <div class="mb-3">
                        <span class="ks-kpi-label">Last Login</span>
                        <div class="small text-muted"><?= htmlspecialchars($user['last_login_at'] ?? 'Never') ?></div>
                    </div>
                    <div>
                        <span class="ks-kpi-label">Created At</span>
                        <div class="small text-muted"><?= htmlspecialchars($user['created_at'] ?? '—') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Organisation Memberships & Role Permissions -->
        <div class="col-lg-8">
            <div class="ks-card p-4 mb-4">
                <h5 class="fw-bold text-navy mb-3">Organisation Memberships</h5>
                <div class="table-responsive">
                    <table class="ks-table">
                        <thead>
                            <tr>
                                <th>Organisation</th>
                                <th>Assigned Role</th>
                                <th>Default Org</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($user['organizations'])): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No organisation associations found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($user['organizations'] as $org): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-navy"><?= htmlspecialchars($org['name']) ?></span>
                                            <span class="badge bg-light text-dark ms-1"><?= htmlspecialchars($org['organization_code']) ?></span>
                                        </td>
                                        <td><span class="ks-badge ks-badge-blue"><?= htmlspecialchars($org['role_name'] ?? 'Member') ?></span></td>
                                        <td>
                                            <?php if (!empty($org['is_default'])): ?>
                                                <i class="bi bi-check-circle-fill text-success"></i> Yes
                                            <?php else: ?>
                                                <span class="text-muted">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($org['status'] === 'active'): ?>
                                                <span class="ks-badge ks-badge-confirmed">Active</span>
                                            <?php else: ?>
                                                <span class="ks-badge ks-badge-rejected"><?= htmlspecialchars(ucfirst($org['status'])) ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ks-card p-4">
                <h5 class="fw-bold text-navy mb-3">Effective RBAC Permissions</h5>
                <p class="text-muted small mb-3">Permissions resolved dynamically from user role and tenant context.</p>
                <div class="d-flex flex-wrap gap-2">
                    <?php 
                    $rbac = new \App\Services\Rbac\PermissionService();
                    $perms = $rbac->getUserPermissions((int)$user['id'], (int)$orgId);
                    if (empty($perms)): ?>
                        <span class="text-muted small">No permissions granted.</span>
                    <?php else: ?>
                        <?php foreach ($perms as $p): ?>
                            <span class="badge bg-light text-navy p-2 border font-monospace" style="font-size: 11px;">
                                <i class="bi bi-shield-check text-primary me-1"></i><?= htmlspecialchars($p) ?>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
