<?php
$activePage = 'coaches';
$title = 'Add Coach — KhelSutra';

$orgId = current_organization_id();
$db = \App\Services\BaseService::getDatabaseConnection();

// Fetch departments for dropdown
$deptStmt = $db ? $db->prepare("SELECT id, name FROM departments WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC") : null;
if ($deptStmt) {
    $deptStmt->execute([':org_id' => $orgId]);
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} else {
    $departments = [];
}

// Fetch active teams
$teamsStmt = $db ? $db->prepare("SELECT id, name FROM teams WHERE organization_id = :org_id AND deleted_at IS NULL ORDER BY name ASC") : null;
if ($teamsStmt) {
    $teamsStmt->execute([':org_id' => $orgId]);
    $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} else {
    $teams = [];
}

ob_start();
?>

<!-- Top Back Navigation -->
<div class="mb-3">
    <a href="/coaches" class="text-decoration-none text-muted small fw-medium">
        <i class="bi bi-arrow-left me-1"></i> Back to Coaches
    </a>
</div>

<!-- Page Header (Section 8: Page Title + Action) -->
<div class="ks-page-header">
    <h1 class="ks-page-title">Add Coach</h1>
</div>

<form action="/coaches/create" method="POST" id="createCoachForm">
    <!-- Section 1: Personal Information -->
    <div class="ks-card p-4 mb-4" style="background: #fff;">
        <h5 class="fw-bold mb-3 text-navy" style="font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
            1. Personal Information
        </h5>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="ks-form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="ks-form-control" required>
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Middle Name</label>
                <input type="text" name="middle_name" class="ks-form-control">
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="ks-form-control" required>
            </div>

            <div class="col-md-4">
                <label class="ks-form-label">Date of Birth <span class="text-danger">*</span></label>
                <input type="date" name="date_of_birth" class="ks-form-control" required>
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Gender <span class="text-danger">*</span></label>
                <select name="gender" class="ks-form-select" required>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Blood Group</label>
                <select name="blood_group" class="ks-form-select">
                    <option value="">Select blood group</option>
                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                        <option value="<?= htmlspecialchars($bg, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($bg, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="ks-form-label">Phone Number</label>
                <input type="text" name="phone" class="ks-form-control" placeholder="+91 9876543210">
            </div>
            <div class="col-md-6">
                <label class="ks-form-label">Email Address</label>
                <input type="email" name="email" class="ks-form-control" placeholder="coach@example.com">
            </div>

            <div class="col-12">
                <label class="ks-form-label">Address Line</label>
                <input type="text" name="address_line1" class="ks-form-control">
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">City</label>
                <input type="text" name="city" class="ks-form-control">
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">State</label>
                <input type="text" name="state" class="ks-form-control">
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Postal Code</label>
                <input type="text" name="postal_code" class="ks-form-control">
            </div>
        </div>
    </div>

    <!-- Section 2: Employment & Department -->
    <div class="ks-card p-4 mb-4" style="background: #fff;">
        <h5 class="fw-bold mb-3 text-navy" style="font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
            2. Employment & Department
        </h5>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="ks-form-label">Designation <span class="text-danger">*</span></label>
                <input type="text" name="designation" class="ks-form-control" value="Head Coach" required>
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Department</label>
                <select name="department_id" class="ks-form-select">
                    <option value="">General Coaching</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= (int)$dept['id'] ?>"><?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Employment Type</label>
                <select name="employment_type" class="ks-form-select">
                    <option value="full_time">Full Time</option>
                    <option value="part_time">Part Time</option>
                    <option value="contract">Contract</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Joining Date</label>
                <input type="date" name="joining_date" class="ks-form-control" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
    </div>

    <!-- Section 3: Coaching Credentials & Profile -->
    <div class="ks-card p-4 mb-4" style="background: #fff;">
        <h5 class="fw-bold mb-3 text-navy" style="font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
            3. Coaching Profile & Credentials
        </h5>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="ks-form-label">Specialization <span class="text-danger">*</span></label>
                <input type="text" name="specialization" class="ks-form-control" placeholder="e.g. Football Tactics, Spin Bowling, Sprinting" required>
            </div>
            <div class="col-md-3">
                <label class="ks-form-label">Experience (Years)</label>
                <input type="number" step="0.5" name="experience_years" class="ks-form-control" value="3.0">
            </div>
            <div class="col-md-3">
                <label class="ks-form-label">Coach Status <span class="text-danger">*</span></label>
                <select name="status" class="ks-form-select" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="ks-form-label">Academic Qualifications</label>
                <input type="text" name="qualification" class="ks-form-control" placeholder="e.g. B.P.Ed, Sports Science Diploma">
            </div>
            <div class="col-md-6">
                <label class="ks-form-label">License / Registration Number</label>
                <input type="text" name="license_number" class="ks-form-control" placeholder="e.g. AFC-A-8849">
            </div>

            <div class="col-12">
                <label class="ks-form-label">Certifications & Badges</label>
                <textarea name="certifications" class="ks-form-control" rows="2" placeholder="e.g. FIFA B License, First Aid Certified, Strength & Conditioning Specialist"></textarea>
            </div>
        </div>
    </div>

    <!-- Section 4: Initial Team Assignment (Optional) -->
    <div class="ks-card p-4 mb-4" style="background: #fff;">
        <h5 class="fw-bold mb-3 text-navy" style="font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
            4. Squad / Team Assignment (Optional)
        </h5>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="ks-form-label">Assign to Team</label>
                <select name="team_id" class="ks-form-select">
                    <option value="">No initial team assignment</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= (int)$team['id'] ?>"><?= htmlspecialchars($team['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="ks-form-label">Role in Squad</label>
                <select name="coach_role" class="ks-form-select">
                    <option value="head_coach">Head Coach</option>
                    <option value="assistant_coach">Assistant Coach</option>
                    <option value="fitness_coach">Fitness / Conditioning Coach</option>
                    <option value="other">Specialist Coach</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Form Actions (Standard Bottom/Right Section 12) -->
    <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
        <a href="/coaches" class="ks-btn ks-btn-secondary">
            Cancel
        </a>
        <button type="submit" class="ks-btn ks-btn-primary">
            <i class="bi bi-check2 me-1"></i> Save Coach
        </button>
    </div>
</form>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
