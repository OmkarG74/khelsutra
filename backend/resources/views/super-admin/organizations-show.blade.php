<?php
$activePage = 'organizations';
$title = 'Organisation Details — KhelSutra Super Admin';

$orgId = (int)($id ?? 1);
$orgService = new \App\Services\Organization\OrganizationManagementService();
$org = $orgService->getOrganization($orgId);
$admins = $org['admins'] ?? $orgService->getOrganizationAdmins($orgId);

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title"><?= htmlspecialchars($org['name'] ?? 'Organisation') ?></h1>
        <p class="ks-page-subtitle">Organisation Code: <strong class="text-primary"><?= htmlspecialchars($org['organization_code'] ?? '—') ?></strong> • Full-Page View Mode</p>
    </div>
    <div class="ks-header-actions">
        <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Organisations</span>
        </a>
        <?php if ($org): ?>
            <a href="/super-admin/organizations/<?= $orgId ?>/edit" class="ks-btn ks-btn-primary">
                <i class="bi bi-pencil-square"></i>
                <span>Edit Organisation</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$org): ?>
    <div class="ks-card p-5 text-center text-muted">
        <i class="bi bi-building-x fs-1 text-danger"></i>
        <h5 class="mt-3">Organisation Not Found</h5>
        <p>The requested organisation does not exist or has been removed.</p>
        <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary mt-2">Return to Organisations</a>
    </div>
<?php else: ?>
    <?php
    $mode = 'view';
    include __DIR__ . '/partials/organization-form.blade.php';
    ?>
<?php endif; ?>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
