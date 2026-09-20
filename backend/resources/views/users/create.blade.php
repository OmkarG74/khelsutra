<?php
$activePage = 'users';
$title = 'Create User — KhelSutra Platform';

$rbacService = new \App\Services\Rbac\PermissionService();
$roles = $rbacService->getAllRoles();

$orgService = new \App\Services\Organization\OrganizationManagementService();
$orgs = $orgService->listOrganizations();

ob_start();
?>

<!-- Page Header -->
<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/users" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Users</a>
        </div>
        <h1 class="ks-page-title">Create User</h1>
        <p class="ks-page-subtitle">Provision a new platform user, assign RBAC roles, and establish organisation access.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="ks-card p-4">
            <form id="createUserForm">
                <h5 class="fw-bold text-navy mb-3">1. Account Information</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="ks-form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="ks-form-control" required placeholder="e.g. Ramesh">
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="ks-form-control" required placeholder="e.g. Patil">
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="ks-form-control" required placeholder="e.g. ramesh.patil">
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="ks-form-control" required placeholder="e.g. ramesh@khelsutra.com">
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Phone Number</label>
                        <input type="tel" name="phone" class="ks-form-control" placeholder="e.g. +91 98765 43210">
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="ks-form-control" required placeholder="Minimum 8 characters">
                    </div>
                </div>

                <hr class="my-4" style="border-color: var(--ks-border-light);">

                <h5 class="fw-bold text-navy mb-3">2. Organisation & Role Assignment</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="ks-form-label">Organisation <span class="text-danger">*</span></label>
                        <select name="organization_id" class="ks-form-select" required>
                            <?php foreach ($orgs as $o): ?>
                                <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['name']) ?> (<?= htmlspecialchars($o['organization_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Role <span class="text-danger">*</span></label>
                        <select name="role_id" class="ks-form-select" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?> — <?= htmlspecialchars($r['description'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Initial Status</label>
                        <select name="status" class="ks-form-select">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="/users" class="ks-btn ks-btn-secondary">Cancel</a>
                    <button type="submit" class="ks-btn ks-btn-primary" id="btnSubmit">
                        <i class="bi bi-check-lg"></i>
                        <span>Create Platform User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('createUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span>Creating...</span>';

    const formData = new FormData(this);
    const payload = Object.fromEntries(formData.entries());

    try {
        const res = await fetch('/api/v1/users', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            alert('User successfully created!');
            window.location.href = '/users';
        } else {
            alert(result.message || 'Error creating user');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i><span>Create Platform User</span>';
        }
    } catch (err) {
        alert('An error occurred during submission.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i><span>Create Platform User</span>';
    }
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
