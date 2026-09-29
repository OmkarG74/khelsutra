<?php
$activePage = 'organizations';
$title = 'Create Organisation — KhelSutra Super Admin';

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Create Organisation</h1>
        <p class="ks-page-subtitle">Onboard a new multi-tenant sports academy and configure its initial Organisation Administrators.</p>
    </div>
    <div class="ks-header-actions">
        <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary">
            <i class="bi bi-arrow-left"></i>
            <span>Cancel & Back</span>
        </a>
    </div>
</div>

<?php
$mode = 'create';
$org = [];
$admins = [];
include __DIR__ . '/partials/organization-form.blade.php';
?>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
