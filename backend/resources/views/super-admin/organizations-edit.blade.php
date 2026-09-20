<?php
$activePage = 'organizations';
$title = 'Edit Organisation — KhelSutra Super Admin';

$orgId = (int)($id ?? 1);
$orgService = new \App\Services\Organization\OrganizationManagementService();
$org = $orgService->getOrganization($orgId);

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Edit Organisation</h1>
        <p class="ks-page-subtitle">Update metadata and contact details for <strong class="text-primary"><?= htmlspecialchars($org['name'] ?? '') ?></strong>.</p>
    </div>
    <div class="ks-header-actions">
        <a href="/super-admin/organizations/<?= $orgId ?>" class="ks-btn ks-btn-secondary">
            <i class="bi bi-arrow-left"></i>
            <span>Cancel</span>
        </a>
    </div>
</div>

<div class="ks-card p-4">
    <form id="editOrgForm">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="ks-form-label">Organisation Name *</label>
                <input type="text" name="name" class="ks-form-control" value="<?= htmlspecialchars($org['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="ks-form-label">Organisation Code (Read Only)</label>
                <input type="text" class="ks-form-control" value="<?= htmlspecialchars($org['organization_code'] ?? '') ?>" readonly style="background: #F8FAFD;">
            </div>
            <div class="col-md-6">
                <label class="ks-form-label">Legal Name</label>
                <input type="text" name="legal_name" class="ks-form-control" value="<?= htmlspecialchars($org['legal_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="ks-form-label">Subscription Plan</label>
                <input type="text" name="plan_name" class="ks-form-control" value="<?= htmlspecialchars($org['plan_name'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Email</label>
                <input type="email" name="email" class="ks-form-control" value="<?= htmlspecialchars($org['email'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Phone</label>
                <input type="text" name="phone" class="ks-form-control" value="<?= htmlspecialchars($org['phone'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Website</label>
                <input type="text" name="website" class="ks-form-control" value="<?= htmlspecialchars($org['website'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="ks-form-label">Address Line 1</label>
                <input type="text" name="address_line1" class="ks-form-control" value="<?= htmlspecialchars($org['address_line1'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="ks-form-label">City</label>
                <input type="text" name="city" class="ks-form-control" value="<?= htmlspecialchars($org['city'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="ks-form-label">State</label>
                <input type="text" name="state" class="ks-form-control" value="<?= htmlspecialchars($org['state'] ?? '') ?>">
            </div>
            <div class="col-12">
                <label class="ks-form-label">Operational Notes</label>
                <textarea name="notes" class="ks-form-control" rows="3"><?= htmlspecialchars($org['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-4 border-top mt-4" style="border-color: var(--ks-border-light) !important;">
            <a href="/super-admin/organizations/<?= $orgId ?>" class="ks-btn ks-btn-secondary">Cancel</a>
            <button type="submit" class="ks-btn ks-btn-primary">
                <i class="bi bi-check2"></i>
                <span>Save Changes</span>
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('editOrgForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this).entries());
    try {
        const res = await fetch('/api/v1/organizations/<?= $orgId ?>', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            alert('Organisation updated successfully');
            window.location.href = '/super-admin/organizations/<?= $orgId ?>';
        } else {
            alert('Error: ' + json.message);
        }
    } catch (err) {
        alert('Request failed');
    }
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
