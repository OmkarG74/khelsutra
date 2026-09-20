<?php
$activePage = 'hr-finance';
$title = 'Edit Employee — KhelSutra HR';

$empId = $id ?? 1;
$empService = new \App\Services\Staff\EmployeeService();
$deptService = new \App\Services\Staff\DepartmentService();
$catService = new \App\Services\Staff\EmployeeCategoryService();

$orgId = $_SESSION['current_organization_id'] ?? 1;

try {
    $emp = $empService->getEmployeeDetails((int)$empId, (int)$orgId);
} catch (\Throwable $e) {
    $emp = null;
}

$departments = $deptService->listDepartments($orgId);
$categories = $catService->listCategories($orgId);

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/hr/employees/<?= $empId ?>" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Profile</a>
        </div>
        <h1 class="ks-page-title">Edit Employee</h1>
        <p class="ks-page-subtitle">Update employee profile, contact numbers, department, bank account, and employment status.</p>
    </div>
</div>

<?php if (!$emp): ?>
    <div class="ks-card p-5 text-center text-muted">
        <i class="bi bi-person-x fs-1 text-danger"></i>
        <h5 class="mt-3">Employee Not Found</h5>
        <a href="/hr/employees" class="ks-btn ks-btn-secondary mt-2">Return to Employees</a>
    </div>
<?php else: ?>
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <form id="editEmployeeForm" class="ks-card p-4">
                <!-- Section 1: Personal Information -->
                <h5 class="fw-bold text-navy mb-3">1. Personal Information</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="ks-form-label">Employee Code</label>
                        <input type="text" class="ks-form-control" value="<?= htmlspecialchars($emp['employee_code']) ?>" readonly disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="ks-form-control" required value="<?= htmlspecialchars($emp['first_name']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="ks-form-control" value="<?= htmlspecialchars($emp['middle_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="ks-form-control" required value="<?= htmlspecialchars($emp['last_name']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="ks-form-control" value="<?= htmlspecialchars($emp['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Blood Group</label>
                        <input type="text" name="blood_group" class="ks-form-control" value="<?= htmlspecialchars($emp['blood_group'] ?? '') ?>">
                    </div>
                </div>

                <hr class="my-4" style="border-color: var(--ks-border-light);">

                <!-- Section 2: Contact Details -->
                <h5 class="fw-bold text-navy mb-3">2. Contact Details</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="ks-form-label">Phone <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="ks-form-control" required value="<?= htmlspecialchars($emp['phone']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="ks-form-control" required value="<?= htmlspecialchars($emp['email']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Address</label>
                        <input type="text" name="address" class="ks-form-control" value="<?= htmlspecialchars($emp['address'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="ks-form-label">City</label>
                        <input type="text" name="city" class="ks-form-control" value="<?= htmlspecialchars($emp['city'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="ks-form-label">State</label>
                        <input type="text" name="state" class="ks-form-control" value="<?= htmlspecialchars($emp['state'] ?? '') ?>">
                    </div>
                </div>

                <hr class="my-4" style="border-color: var(--ks-border-light);">

                <!-- Section 3: Employment Details -->
                <h5 class="fw-bold text-navy mb-3">3. Employment Details</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="ks-form-label">Department</label>
                        <select name="department_id" class="ks-form-select">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= ($emp['department_id'] == $d['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Category</label>
                        <select name="employee_category_id" class="ks-form-select">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($emp['employee_category_id'] == $c['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Designation</label>
                        <input type="text" name="designation" class="ks-form-control" value="<?= htmlspecialchars($emp['designation'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Status</label>
                        <select name="employment_status" class="ks-form-select">
                            <option value="active" <?= ($emp['employment_status'] === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="probation" <?= ($emp['employment_status'] === 'probation') ? 'selected' : '' ?>>Probation</option>
                            <option value="suspended" <?= ($emp['employment_status'] === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                            <option value="terminated" <?= ($emp['employment_status'] === 'terminated') ? 'selected' : '' ?>>Terminated</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="/hr/employees/<?= $empId ?>" class="ks-btn ks-btn-secondary">Cancel</a>
                    <button type="submit" class="ks-btn ks-btn-primary" id="btnSubmit">
                        <i class="bi bi-save"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('editEmployeeForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span>Saving...</span>';

        const formData = new FormData(this);
        const payload = Object.fromEntries(formData.entries());

        try {
            const res = await fetch('/api/v1/employees/<?= $empId ?>', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.success) {
                alert('Employee record updated successfully!');
                window.location.href = '/hr/employees/<?= $empId ?>';
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
