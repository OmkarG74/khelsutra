<?php
$activePage = 'roles';
$title = 'Role Permissions — KhelSutra RBAC';

$roleId = $id ?? 1;
$rbacService = new \App\Services\Rbac\PermissionService();
$allRoles = $rbacService->getAllRoles();
$selectedRole = null;
foreach ($allRoles as $r) {
    if ((int)$r['id'] === (int)$roleId) {
        $selectedRole = $r;
        break;
    }
}

$rolePermissions = $rbacService->getRolePermissions((int)$roleId);

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/roles" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Roles</a>
        </div>
        <h1 class="ks-page-title"><?= htmlspecialchars($selectedRole['name'] ?? 'Role Details') ?></h1>
        <p class="ks-page-subtitle"><?= htmlspecialchars($selectedRole['description'] ?? 'Assigned permissions and access privileges.') ?></p>
    </div>
    <div class="ks-header-actions">
        <span class="ks-badge ks-badge-blue fs-6 px-3 py-2"><?= count($rolePermissions) ?> Permissions Mapped</span>
    </div>
</div>

<div class="ks-card p-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold text-navy mb-1">Assigned Permissions Matrix</h5>
            <p class="text-muted small mb-0">Role capabilities granted in database table <code>role_permissions</code>.</p>
        </div>
    </div>

    <div class="row g-3">
        <?php if (empty($rolePermissions)): ?>
            <div class="col-12 text-center py-4 text-muted">
                No permissions currently assigned to this role in the database.
            </div>
        <?php else: ?>
            <?php foreach ($rolePermissions as $perm): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="p-3 border rounded-3 bg-light h-100" style="border-color: var(--ks-border-light)!important;">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span class="fw-bold text-navy font-monospace" style="font-size: 13px;"><?= htmlspecialchars($perm['name'] ?? $perm) ?></span>
                        </div>
                        <div class="small text-muted" style="font-size: 12px;"><?= htmlspecialchars($perm['description'] ?? '') ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
