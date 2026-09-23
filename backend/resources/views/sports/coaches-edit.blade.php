<?php
$coachId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();
$coachService = new \App\Services\Coach\CoachService();
$coach = $coachService->getCoach($orgId, $coachId);

$db = \App\Services\BaseService::getDatabaseConnection();

// Fetch departments
$deptStmt = $db->prepare("SELECT id, name FROM departments WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
$deptStmt->execute([':org_id' => $orgId]);
$departments = $deptStmt ? $deptStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Fetch active teams
$teamsStmt = $db->prepare("SELECT id, name FROM teams WHERE organization_id = :org_id AND deleted_at IS NULL ORDER BY name ASC");
$teamsStmt->execute([':org_id' => $orgId]);
$teams = $teamsStmt ? $teamsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$pageTitle = $coach ? 'Edit Coach — ' . htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) : 'Edit Coach';
$activePage = 'coaches';

ob_start();
?>

<div class="ks-content">
    <?php if (!$coach): ?>
        <div class="card p-5 text-center" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <div class="mb-3"><i class="bi bi-person-x fs-1 text-muted"></i></div>
            <h4 class="fw-bold" style="color: var(--ks-navy);">Coach Not Found</h4>
            <p class="text-muted small">The requested coach record does not exist or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/coaches" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Coaches
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="/coaches/<?= (int)$coach['coach_profile_id'] ?>" class="text-decoration-none text-muted small fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Coach Details
            </a>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h4 fw-bold mb-0" style="color: var(--ks-navy);">Edit Coach: <?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="text-muted small mt-1">Code: <strong><?= htmlspecialchars($coach['coach_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull; Employee Code: <strong><?= htmlspecialchars($coach['employee_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></div>
            </div>
        </div>

        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger mb-4 py-2 px-3 small d-flex align-items-center gap-2" style="border-radius: var(--ks-radius-button);">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <form action="/coaches/<?= (int)$coach['coach_profile_id'] ?>/edit" method="POST" id="editCoachForm">
            <!-- Section 1: Personal Details -->
            <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                    1. Personal Information
                </h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($coach['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($coach['middle_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($coach['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($coach['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="male" <?= ($coach['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= ($coach['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="other" <?= ($coach['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Blood Group</label>
                        <select name="blood_group" class="form-select" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="">Select blood group</option>
                            <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                                <option value="<?= $bg ?>" <?= ($coach['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($coach['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($coach['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Address Line</label>
                        <input type="text" name="address_line1" class="form-control" value="<?= htmlspecialchars($coach['address_line1'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">City</label>
                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($coach['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">State</label>
                        <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($coach['state'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Postal Code</label>
                        <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($coach['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                </div>
            </div>

            <!-- Section 2: Employment & Department -->
            <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                    2. Employment & Department
                </h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Designation <span class="text-danger">*</span></label>
                        <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($coach['designation'] ?? 'Coach', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Department</label>
                        <select name="department_id" class="form-select" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="">General Coaching</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= (int)$dept['id'] ?>" <?= ($coach['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Employment Type</label>
                        <select name="employment_type" class="form-select" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="full_time" <?= ($coach['employment_type'] ?? '') === 'full_time' ? 'selected' : '' ?>>Full Time</option>
                            <option value="part_time" <?= ($coach['employment_type'] ?? '') === 'part_time' ? 'selected' : '' ?>>Part Time</option>
                            <option value="contract" <?= ($coach['employment_type'] ?? '') === 'contract' ? 'selected' : '' ?>>Contract</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 3: Coaching Credentials & Profile -->
            <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                    3. Coaching Profile & Credentials
                </h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Specialization <span class="text-danger">*</span></label>
                        <input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($coach['specialization'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark">Experience (Years)</label>
                        <input type="number" step="0.5" name="experience_years" class="form-control" value="<?= (float)($coach['experience_years'] ?? 0) ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark">Coach Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="active" <?= ($coach['coach_status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($coach['coach_status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Academic Qualifications</label>
                        <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($coach['qualification'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">License / Registration Number</label>
                        <input type="text" name="license_number" class="form-control" value="<?= htmlspecialchars($coach['license_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Certifications & Badges</label>
                        <textarea name="certifications" class="form-control" rows="2" style="font-size: 13px; border-radius: var(--ks-radius-button);"><?= htmlspecialchars($coach['certifications'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
                <a href="/coaches/<?= (int)$coach['coach_profile_id'] ?>" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                    <i class="bi bi-check2 me-1"></i> Save Changes
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
