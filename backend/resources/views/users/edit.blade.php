<?php
$activePage = 'users';
$title = 'Edit User — KhelSutra Platform';

$userId = $id ?? 1;
$userService = new \App\Services\User\UserManagementService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

try {
    $user = $userService->getUserDetails((int)$userId, (int)$orgId);
} catch (\Throwable $e) {
    $user = null;
}

$rbacService = new \App\Services\Rbac\PermissionService();
$roles = $rbacService->getAllRoles();

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/users/<?= $userId ?>" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to User Details</a>
        </div>
        <h1 class="ks-page-title">Edit User</h1>
        <p class="ks-page-subtitle">Update user profile, status, or role assignments within organisation context.</p>
    </div>
</div>

<?php if (!$user): ?>
    <div class="ks-card p-5 text-center text-muted">
        <i class="bi bi-person-x fs-1 text-danger"></i>
        <h5 class="mt-3">User Not Found</h5>
        <a href="/users" class="ks-btn ks-btn-secondary mt-2">Return to Users</a>
    </div>
<?php else: ?>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="ks-card p-4">
                <form id="editUserForm">
                    <h5 class="fw-bold text-navy mb-3">1. Personal Information</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="ks-form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="ks-form-control" required value="<?= htmlspecialchars($user['first_name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="ks-form-control" required value="<?= htmlspecialchars($user['last_name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Phone</label>
                            <input type="tel" name="phone" class="ks-form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Status</label>
                            <select name="status" class="ks-form-select">
                                <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="fw-bold text-navy mb-3">2. Security & Role</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="ks-form-label">Assigned Role</label>
                            <select name="role_id" class="ks-form-select">
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= ($user['role_name'] === $r['name']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Reset Password (Optional)</label>
                            <input type="password" name="password" class="ks-form-control" placeholder="Leave blank to preserve current">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="/users/<?= $userId ?>" class="ks-btn ks-btn-secondary">Cancel</a>
                        <button type="submit" class="ks-btn ks-btn-primary" id="btnSave">
                            <i class="bi bi-save"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('editUserForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSave');
        btn.disabled = true;
        btn.innerHTML = '<span>Saving...</span>';

        const formData = new FormData(this);
        const payload = Object.fromEntries(formData.entries());
        if (!payload.password) delete payload.password;

        try {
            const res = await fetch('/api/v1/users/<?= $userId ?>', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.success) {
                alert('User details updated successfully!');
                window.location.href = '/users/<?= $userId ?>';
            } else {
                alert(result.message || 'Update failed');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-save"></i><span>Save Changes</span>';
            }
        } catch (err) {
            alert('An error occurred during update.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save"></i><span>Save Changes</span>';
        }
    });
    </script>
<?php endif; ?>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
