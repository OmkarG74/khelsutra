<?php
$activePage = 'hr-finance';
$title = 'Add Employee — KhelSutra HR';

$orgId = $_SESSION['current_organization_id'] ?? 1;
$deptService = new \App\Services\Staff\DepartmentService();
$catService = new \App\Services\Staff\EmployeeCategoryService();

$departments = $deptService->listDepartments($orgId);
$categories = $catService->listCategories($orgId);

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/hr/employees" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Roster</a>
        </div>
        <h1 class="ks-page-title">Add New Employee</h1>
        <p class="ks-page-subtitle">Onboard staff, assign department, configure payroll structure, and establish coaching credentials.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <form id="createEmployeeForm" class="ks-card p-4">
            <!-- Section 1: Personal Information -->
            <h5 class="fw-bold text-navy mb-3"><i class="bi bi-person me-2 text-primary"></i>1. Personal Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="ks-form-label">Employee Code <span class="text-danger">*</span></label>
                    <input type="text" name="employee_code" class="ks-form-control" required placeholder="e.g. EMP-1010" value="EMP-<?= rand(1000, 9999) ?>">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="ks-form-control" required placeholder="First name">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="ks-form-control" placeholder="Middle name">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="ks-form-control" required placeholder="Last name">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="ks-form-control">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Gender</label>
                    <select name="gender" class="ks-form-select">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Blood Group</label>
                    <select name="blood_group" class="ks-form-select">
                        <option value="">Select blood group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
            </div>

            <hr class="my-4" style="border-color: var(--ks-border-light);">

            <!-- Section 2: Contact Information -->
            <h5 class="fw-bold text-navy mb-3"><i class="bi bi-geo-alt me-2 text-primary"></i>2. Contact Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="ks-form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" class="ks-form-control" required placeholder="+91 98765 43210">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="ks-form-control" required placeholder="name@khelsutra.com">
                </div>
                <div class="col-12">
                    <label class="ks-form-label">Street Address</label>
                    <input type="text" name="address" class="ks-form-control" placeholder="House / Flat / Street name">
                </div>
                <div class="col-md-3">
                    <label class="ks-form-label">City</label>
                    <input type="text" name="city" class="ks-form-control" placeholder="City">
                </div>
                <div class="col-md-3">
                    <label class="ks-form-label">State</label>
                    <input type="text" name="state" class="ks-form-control" placeholder="State">
                </div>
                <div class="col-md-3">
                    <label class="ks-form-label">Country</label>
                    <input type="text" name="country" class="ks-form-control" value="India">
                </div>
                <div class="col-md-3">
                    <label class="ks-form-label">Postal Code</label>
                    <input type="text" name="postal_code" class="ks-form-control" placeholder="PIN code">
                </div>
            </div>

            <hr class="my-4" style="border-color: var(--ks-border-light);">

            <!-- Section 3: Employment Details -->
            <h5 class="fw-bold text-navy mb-3"><i class="bi bi-briefcase me-2 text-primary"></i>3. Employment Details</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="ks-form-label">Department</label>
                    <select name="department_id" class="ks-form-select">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Employee Category</label>
                    <select name="employee_category_id" class="ks-form-select" id="catSelect">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Designation</label>
                    <input type="text" name="designation" class="ks-form-control" placeholder="e.g. Senior Badminton Coach">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Joining Date <span class="text-danger">*</span></label>
                    <input type="date" name="joining_date" class="ks-form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Employment Type</label>
                    <select name="employment_type" class="ks-form-select">
                        <option value="full_time">Full Time</option>
                        <option value="part_time">Part Time</option>
                        <option value="contract">Contract</option>
                        <option value="intern">Intern</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Employment Status</label>
                    <select name="employment_status" class="ks-form-select">
                        <option value="active" selected>Active</option>
                        <option value="probation">Probation</option>
                        <option value="suspended">Suspended</option>
                        <option value="terminated">Terminated</option>
                    </select>
                </div>
            </div>

            <hr class="my-4" style="border-color: var(--ks-border-light);">

            <!-- Section 4: Emergency Contact -->
            <h5 class="fw-bold text-navy mb-3"><i class="bi bi-telephone-inbound me-2 text-primary"></i>4. Emergency Contact</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="ks-form-label">Contact Name</label>
                    <input type="text" name="emergency_contact_name" class="ks-form-control" placeholder="Full name">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Contact Phone</label>
                    <input type="tel" name="emergency_contact_phone" class="ks-form-control" placeholder="+91 98765 00000">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Relationship</label>
                    <input type="text" name="emergency_contact_relation" class="ks-form-control" placeholder="e.g. Spouse / Parent">
                </div>
            </div>

            <hr class="my-4" style="border-color: var(--ks-border-light);">

            <!-- Section 5: Bank Information -->
            <h5 class="fw-bold text-navy mb-3"><i class="bi bi-bank me-2 text-primary"></i>5. Bank & Payroll Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="ks-form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="ks-form-control" placeholder="e.g. State Bank of India">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Account Number</label>
                    <input type="text" name="bank_account_no" class="ks-form-control" placeholder="Account Number">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">IFSC Code</label>
                    <input type="text" name="bank_ifsc" class="ks-form-control" placeholder="e.g. SBIN0001234">
                </div>
            </div>

            <hr class="my-4" style="border-color: var(--ks-border-light);">

            <!-- Section 6: Notes -->
            <h5 class="fw-bold text-navy mb-3"><i class="bi bi-journal-text me-2 text-primary"></i>6. Additional Notes</h5>
            <div class="mb-4">
                <textarea name="notes" class="ks-form-control" rows="3" placeholder="Background notes, certifications, references..."></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="/hr/employees" class="ks-btn ks-btn-secondary">Cancel</a>
                <button type="submit" class="ks-btn ks-btn-primary" id="btnSubmit">
                    <i class="bi bi-check-lg"></i>
                    <span>Save Employee</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('createEmployeeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span>Saving Employee...</span>';

    const formData = new FormData(this);
    const payload = Object.fromEntries(formData.entries());

    try {
        const res = await fetch('/api/v1/employees', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            alert('Employee record successfully created!');
            window.location.href = '/hr/employees/' + result.data.id;
        } else {
            alert(result.message || 'Failed to save employee');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i><span>Save Employee</span>';
        }
    } catch (err) {
        alert('An error occurred during submission.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i><span>Save Employee</span>';
    }
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
