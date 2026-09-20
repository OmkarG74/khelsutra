<?php
$activePage = 'roles';
$title = 'Roles & RBAC — KhelSutra Platform';

$rbacService = new \App\Services\Rbac\PermissionService();
$roles = $rbacService->getAllRoles();

ob_start();
?>

<!-- Page Header (Section 20 & 21) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Roles & Access Control (RBAC)</h1>
        <p class="ks-page-subtitle">The 7 approved standard platform roles, permission mappings, and access enforcement.</p>
    </div>
    <div class="ks-header-actions">
        <a href="/permissions" class="ks-btn ks-btn-secondary">
            <i class="bi bi-shield-lock"></i>
            <span>View All Permissions (81)</span>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($roles as $r): ?>
        <div class="col-xl-4 col-md-6">
            <div class="ks-card p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="ks-icon-box ks-icon-blue">
                            <i class="bi bi-person-badge-fill fs-4"></i>
                        </div>
                        <span class="ks-badge ks-badge-blue">ID #<?= $r['id'] ?></span>
                    </div>
                    <h5 class="fw-bold text-navy mb-2"><?= htmlspecialchars($r['name']) ?></h5>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($r['description'] ?? 'Standard platform role') ?></p>
                </div>
                <div class="pt-3 border-top d-flex align-items-center justify-content-between" style="border-color: var(--ks-border-light)!important;">
                    <div class="small">
                        <span class="text-muted">Slug:</span>
                        <code class="text-navy fw-semibold"><?= htmlspecialchars($r['slug'] ?? strtolower(str_replace(' ', '_', $r['name']))) ?></code>
                    </div>
                    <a href="/roles/<?= $r['id'] ?>" class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">
                        Permissions <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="ks-card p-4">
    <div class="d-flex align-items-center gap-3">
        <div class="ks-icon-box ks-icon-amber">
            <i class="bi bi-shield-exclamation fs-3"></i>
        </div>
        <div>
            <h6 class="fw-bold text-navy mb-1">Strict RBAC Compliance Rule (Section 20)</h6>
            <p class="text-muted small mb-0">
                The platform strictly enforces the 7 pre-approved roles: <strong>Super Admin, Sports Administrator, HR & Finance, Coach, Athlete, Venue & Tournament Manager, and Inventory Manager</strong>. Creating unauthorized ad-hoc roles or bypassing permission middleware is strictly prohibited.
            </p>
        </div>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
