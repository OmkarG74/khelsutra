<?php
$coachId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();

$currentRoleSlug = $_SESSION['auth']['role']['slug'] ?? ($_SESSION['role_slug'] ?? 'sports_admin');
$currentRoleId = (int)($_SESSION['auth']['role']['id'] ?? 2);
$currentUser = $_SESSION['auth']['user'] ?? [
    'id' => 102,
    'email' => 'sportsadmin@khelsutra.local',
    'role_id' => 2,
];
$userId = current_user_id() ?? (int)($currentUser['id'] ?? 102);

$isAthlete = ($currentRoleSlug === 'athlete') || ($currentRoleId === 5) || !empty($currentUser['athlete_id']);
$isCoach = ($currentRoleSlug === 'coach') || ($currentRoleId === 4) || !empty($currentUser['coach_id']);

$permissionService = new \App\Services\Rbac\PermissionService();
$userPayload = array_merge(
    $_SESSION['auth'] ?? [],
    $currentUser,
    [
        'id' => $userId,
        'role_id' => $currentRoleId,
        'role' => $_SESSION['auth']['role'] ?? ['id' => $currentRoleId, 'slug' => $currentRoleSlug],
        'permissions' => $_SESSION['auth']['permissions'] ?? ($currentUser['permissions'] ?? []),
    ]
);

$canEditCoach = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'coach.update', $orgId) ||
    $permissionService->hasPermission($userPayload, 'coach.manage', $orgId)
);

if (!$canEditCoach) {
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo '<div class="p-5 text-center text-danger fw-bold">403 Forbidden: You do not have permission to edit coaching staff records.</div>';
    return;
}

$coachService = new \App\Services\Coach\CoachService();
$coach = $coachId > 0 ? $coachService->getCoach($orgId, $coachId) : null;

if (!$coach && !headers_sent()) {
    http_response_code(404);
}

$pdo = \App\Services\BaseService::getDatabaseConnection();
$departments = [];
$teams = [];
if ($pdo) {
    $dStmt = $pdo->prepare("SELECT id, name FROM departments WHERE organization_id = :org_id AND deleted_at IS NULL ORDER BY name ASC");
    $dStmt->execute([':org_id' => $orgId]);
    $departments = $dStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $tStmt = $pdo->prepare("SELECT id, name, team_code FROM teams WHERE organization_id = :org_id AND deleted_at IS NULL AND status = 'active' ORDER BY id DESC");
    $tStmt->execute([':org_id' => $orgId]);
    $rawTeams = $tStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $seenNames = [];
    foreach ($rawTeams as $rt) {
        $norm = strtolower(trim((string)($rt['name'] ?? '')));
        if ($norm !== '' && !isset($seenNames[$norm])) {
            $seenNames[$norm] = true;
            $teams[] = $rt;
        }
    }
    usort($teams, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
}

$fullName = $coach ? trim(($coach['first_name'] ?? '') . ' ' . ($coach['last_name'] ?? '')) : '';
$title = $coach ? ('Edit ' . $fullName . ' — KhelSutra') : 'Edit Coach — KhelSutra';
$pageTitle = $title;
$activePage = 'coaches';

// Format experience value cleanly (e.g. 9.00 -> 9, 18.50 -> 18.5)
$expVal = '';
if ($coach && isset($coach['experience_years']) && $coach['experience_years'] !== null && $coach['experience_years'] !== '') {
    $expNum = (float)$coach['experience_years'];
    $expVal = ((float)(int)$expNum === $expNum) ? (string)(int)$expNum : rtrim(rtrim(number_format($expNum, 2, '.', ''), '0'), '.');
}

ob_start();
?>

<div class="ks-page">
    <?php if (!$coach): ?>
        <div class="ks-content-card p-5 text-center my-4">
            <div class="mb-3"><i class="bi bi-person-x fs-1 text-muted"></i></div>
            <h4 class="fw-bold text-dark">Coach Not Found</h4>
            <p class="text-muted small">The requested coach record does not exist, has been archived, or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/coaches" class="btn btn-outline-secondary" style="border-radius: 8px; font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Coaches
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Page Header -->
        <div class="mb-3">
            <a href="/coaches/<?= (int)$coach['coach_profile_id'] ?>" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1 mb-2" style="font-size: 12.5px;">
                <i class="bi bi-arrow-left"></i> Back to Coach Profile
            </a>
            <div class="ks-page-header mb-0">
                <div>
                    <h1 class="ks-page-title mb-1">Edit Coach: <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="text-muted small mb-0">Coach Code: <strong><?= htmlspecialchars($coach['coach_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull; Employee Code: <strong><?= htmlspecialchars($coach['employee_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></p>
                </div>
                <div class="ks-header-actions">
                    <a href="/coaches/<?= (int)$coach['coach_profile_id'] ?>" class="btn btn-outline-secondary px-3 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">Cancel</a>
                    <button type="submit" form="editCoachForm" class="ks-btn ks-btn-primary px-4 py-2">
                        <i class="bi bi-check-lg"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </div>
        </div>

        <form id="editCoachForm" action="/coaches/<?= (int)$coach['coach_profile_id'] ?>/edit" method="POST">
            <!-- 1. Personal Information -->
            <div class="ks-content-card mb-3">
                <div class="ks-card-header py-2 px-3">
                    <div class="ks-header-left">
                        <i class="bi bi-person-fill text-primary fs-6"></i>
                        <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">1. Personal Information</h3>
                    </div>
                </div>
                <div class="p-3 px-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="ks-form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="ks-form-control" value="<?= htmlspecialchars($coach['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="ks-form-control" value="<?= htmlspecialchars($coach['middle_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="ks-form-control" value="<?= htmlspecialchars($coach['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="ks-form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" class="ks-form-control" value="<?= htmlspecialchars($coach['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="ks-form-select" required>
                                <option value="male" <?= ($coach['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($coach['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="other" <?= ($coach['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Blood Group</label>
                            <select name="blood_group" class="ks-form-select">
                                <option value="">Select blood group</option>
                                <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                                    <option value="<?= $bg ?>" <?= ($coach['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="ks-form-label">Phone Number</label>
                            <input type="tel" name="phone" class="ks-form-control" value="<?= htmlspecialchars($coach['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Email Address</label>
                            <input type="email" name="email" class="ks-form-control" value="<?= htmlspecialchars($coach['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="col-12">
                            <label class="ks-form-label">Address Line</label>
                            <input type="text" name="address_line1" class="ks-form-control" value="<?= htmlspecialchars($coach['address_line1'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="ks-form-label">City</label>
                            <input type="text" name="city" class="ks-form-control" value="<?= htmlspecialchars($coach['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">State</label>
                            <input type="text" name="state" class="ks-form-control" value="<?= htmlspecialchars($coach['state'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="ks-form-control" value="<?= htmlspecialchars($coach['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Employment & Department -->
            <div class="ks-content-card mb-3">
                <div class="ks-card-header py-2 px-3">
                    <div class="ks-header-left">
                        <i class="bi bi-briefcase-fill text-primary fs-6"></i>
                        <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">2. Employment &amp; Department</h3>
                    </div>
                </div>
                <div class="p-3 px-4">
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <label class="ks-form-label">Designation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" class="ks-form-control" value="<?= htmlspecialchars($coach['designation'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="ks-form-label">Department</label>
                            <select name="department_id" class="ks-form-select">
                                <option value="">General Coaching</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>" <?= ($coach['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="ks-form-label">Employment Type</label>
                            <select name="employment_type" class="ks-form-select">
                                <option value="full_time" <?= ($coach['employment_type'] ?? '') === 'full_time' ? 'selected' : '' ?>>Full Time</option>
                                <option value="part_time" <?= ($coach['employment_type'] ?? '') === 'part_time' ? 'selected' : '' ?>>Part Time</option>
                                <option value="contract" <?= ($coach['employment_type'] ?? '') === 'contract' ? 'selected' : '' ?>>Contract</option>
                                <option value="visiting" <?= ($coach['employment_type'] ?? '') === 'visiting' ? 'selected' : '' ?>>Visiting</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="ks-form-label">Joining Date</label>
                            <input type="date" name="joining_date" class="ks-form-control" value="<?= htmlspecialchars(!empty($coach['joining_date']) ? date('Y-m-d', strtotime($coach['joining_date'])) : '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Coaching Profile & Credentials -->
            <div class="ks-content-card mb-3">
                <div class="ks-card-header py-2 px-3">
                    <div class="ks-header-left">
                        <i class="bi bi-award-fill text-primary fs-6"></i>
                        <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">3. Coaching Profile &amp; Credentials</h3>
                    </div>
                </div>
                <div class="p-3 px-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="ks-form-label">Specialization <span class="text-danger">*</span></label>
                            <input type="text" name="specialization" class="ks-form-control" value="<?= htmlspecialchars($coach['specialization'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="ks-form-label">Experience (Years)</label>
                            <input type="number" step="0.5" min="0" max="60" name="experience_years" class="ks-form-control" value="<?= htmlspecialchars($expVal, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="ks-form-label">Coach Status <span class="text-danger">*</span></label>
                            <select name="status" class="ks-form-select" required>
                                <option value="active" <?= ($coach['coach_status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($coach['coach_status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="ks-form-label">Academic Qualifications</label>
                            <input type="text" name="qualification" class="ks-form-control" value="<?= htmlspecialchars($coach['qualification'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">License / Registration Number</label>
                            <input type="text" name="license_number" class="ks-form-control" value="<?= htmlspecialchars($coach['license_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="col-12">
                            <label class="ks-form-label">Certifications &amp; Badges</label>
                            <textarea name="certifications" class="ks-form-control" rows="2"><?= htmlspecialchars($coach['certifications'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Additional Squad Assignment -->
            <div class="ks-content-card mb-3">
                <div class="ks-card-header py-2 px-3">
                    <div class="ks-header-left">
                        <i class="bi bi-people-fill text-primary fs-6"></i>
                        <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">4. Assign Additional Squad (Optional)</h3>
                    </div>
                </div>
                <div class="p-3 px-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="ks-form-label">Assign to Team</label>
                            <select name="team_id" class="ks-form-select">
                                <option value="">No new team assignment</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Role in Squad</label>
                            <select name="coach_role" class="ks-form-select">
                                <option value="head_coach">Head Coach</option>
                                <option value="assistant_coach">Assistant Coach</option>
                                <option value="fitness_trainer">Fitness Trainer</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex justify-content-end gap-2 mt-3 mb-4">
                <a href="/coaches/<?= (int)$coach['coach_profile_id'] ?>" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">Cancel</a>
                <button type="submit" class="ks-btn ks-btn-primary px-4 py-2">
                    <i class="bi bi-check-lg"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
