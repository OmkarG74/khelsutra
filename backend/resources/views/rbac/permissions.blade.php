<?php
$activePage = 'roles';
$title = 'All Permissions — KhelSutra RBAC';

$rbacService = new \App\Services\Rbac\PermissionService();
$permissions = $rbacService->getAllPermissions();

// Group permissions by module prefix (e.g. organization.view -> organization)
$grouped = [];
foreach ($permissions as $p) {
    $parts = explode('.', $p['name']);
    $mod = $parts[0] ?? 'general';
    $grouped[$mod][] = $p;
}

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/roles" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Roles</a>
        </div>
        <h1 class="ks-page-title">Platform Permissions Directory</h1>
        <p class="ks-page-subtitle">Canonical permission repository defined in <code>permissions</code> table (81 permissions across modules).</p>
    </div>
    <div class="ks-header-actions">
        <span class="ks-badge ks-badge-blue fs-6 px-3 py-2"><?= count($permissions) ?> Total Permissions</span>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($grouped as $module => $modulePerms): ?>
        <div class="col-lg-6">
            <div class="ks-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h5 class="fw-bold text-navy mb-0 text-capitalize">
                        <i class="bi bi-folder-fill text-primary me-2"></i><?= htmlspecialchars($module) ?> Module
                    </h5>
                    <span class="badge bg-light text-navy fw-semibold"><?= count($modulePerms) ?></span>
                </div>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($modulePerms as $mp): ?>
                        <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light border" style="border-color: var(--ks-border-light)!important;">
                            <div>
                                <code class="fw-bold text-navy" style="font-size: 13px;"><?= htmlspecialchars($mp['name']) ?></code>
                                <div class="small text-muted" style="font-size: 11px;"><?= htmlspecialchars($mp['description'] ?? '') ?></div>
                            </div>
                            <span class="badge bg-white text-muted border">ID #<?= $mp['id'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
