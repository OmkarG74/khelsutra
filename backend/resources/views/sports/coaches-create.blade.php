<?php
$activePage = 'coaches';
$title = 'Add Coach — KhelSutra';
$pageTitle = $title;

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

$canCreateCoach = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'coach.create', $orgId) ||
    $permissionService->hasPermission($userPayload, 'coach.manage', $orgId)
);

if (!$canCreateCoach) {
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo '<div class="p-5 text-center text-danger fw-bold">403 Forbidden: You do not have permission to register coaching staff.</div>';
    return;
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

ob_start();
?>

<div class="ks-page">
    <!-- Page Header -->
    <div class="mb-3">
        <a href="/coaches" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1 mb-2" style="font-size: 12.5px;">
            <i class="bi bi-arrow-left"></i> Back to Coaches
        </a>
        <div class="ks-page-header mb-0">
            <div>
                <h1 class="ks-page-title mb-1">Add Coach</h1>
                <p class="text-muted small mb-0">Register a new member of the academy coaching staff.</p>
            </div>
            <div class="ks-header-actions">
                <a href="/coaches" class="btn btn-outline-secondary px-3 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">Cancel</a>
                <button type="submit" form="createCoachForm" class="ks-btn ks-btn-primary px-4 py-2">
                    <i class="bi bi-check-lg"></i>
                    <span>Save Coach</span>
                </button>
            </div>
        </div>
    </div>

    <form id="createCoachForm" action="/coaches/create" method="POST">
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
                        <input type="text" name="first_name" class="ks-form-control" required placeholder="e.g. Vikram" value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="ks-form-control" placeholder="Optional" value="<?= htmlspecialchars($_POST['middle_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="ks-form-control" required placeholder="e.g. Rathore" value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="ks-form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="ks-form-control" required value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="ks-form-select" required>
                            <option value="male" <?= (($_POST['gender'] ?? 'male') === 'male') ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
                            <option value="other" <?= (($_POST['gender'] ?? '') === 'other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Blood Group</label>
                        <select name="blood_group" class="ks-form-select">
                            <option value="">Select blood group</option>
                            <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                                <option value="<?= $bg ?>" <?= (($_POST['blood_group'] ?? '') === $bg) ? 'selected' : '' ?>><?= $bg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label">Phone Number</label>
                        <input type="tel" name="phone" class="ks-form-control" placeholder="+91 98765 43210" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Email Address</label>
                        <input type="email" name="email" class="ks-form-control" placeholder="coach@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-12">
                        <label class="ks-form-label">Address Line</label>
                        <input type="text" name="address_line1" class="ks-form-control" placeholder="Flat / Building, Street, Area" value="<?= htmlspecialchars($_POST['address_line1'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="ks-form-label">City</label>
                        <input type="text" name="city" class="ks-form-control" placeholder="e.g. Pune" value="<?= htmlspecialchars($_POST['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">State</label>
                        <input type="text" name="state" class="ks-form-control" placeholder="e.g. Maharashtra" value="<?= htmlspecialchars($_POST['state'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Postal Code</label>
                        <input type="text" name="postal_code" class="ks-form-control" placeholder="e.g. 411001" value="<?= htmlspecialchars($_POST['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
                        <input type="text" name="designation" class="ks-form-control" required placeholder="e.g. Head Coach" value="<?= htmlspecialchars($_POST['designation'] ?? 'Head Coach', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="ks-form-label">Department</label>
                        <select name="department_id" class="ks-form-select">
                            <option value="">General Coaching</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= (int)$d['id'] ?>" <?= (($_POST['department_id'] ?? '') == $d['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="ks-form-label">Employment Type</label>
                        <select name="employment_type" class="ks-form-select">
                            <option value="full_time" <?= (($_POST['employment_type'] ?? 'full_time') === 'full_time') ? 'selected' : '' ?>>Full Time</option>
                            <option value="part_time" <?= (($_POST['employment_type'] ?? '') === 'part_time') ? 'selected' : '' ?>>Part Time</option>
                            <option value="contract" <?= (($_POST['employment_type'] ?? '') === 'contract') ? 'selected' : '' ?>>Contract</option>
                            <option value="visiting" <?= (($_POST['employment_type'] ?? '') === 'visiting') ? 'selected' : '' ?>>Visiting</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="ks-form-label">Joining Date</label>
                        <input type="date" name="joining_date" class="ks-form-control" value="<?= htmlspecialchars($_POST['joining_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
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
                        <input type="text" name="specialization" class="ks-form-control" required placeholder="e.g. Football Tactics, Spin Bowling, Sprinting" value="<?= htmlspecialchars($_POST['specialization'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="ks-form-label">Experience (Years)</label>
                        <input type="number" step="0.5" min="0" max="60" name="experience_years" class="ks-form-control" placeholder="e.g. 5" value="<?= htmlspecialchars($_POST['experience_years'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="ks-form-label">Coach Status <span class="text-danger">*</span></label>
                        <select name="status" class="ks-form-select" required>
                            <option value="active" <?= (($_POST['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label">Academic Qualifications</label>
                        <input type="text" name="qualification" class="ks-form-control" placeholder="e.g. B.P.Ed, Sports Science Diploma" value="<?= htmlspecialchars($_POST['qualification'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">License / Registration Number</label>
                        <input type="text" name="license_number" class="ks-form-control" placeholder="e.g. AFC-A-8849" value="<?= htmlspecialchars($_POST['license_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-12">
                        <label class="ks-form-label">Certifications &amp; Badges</label>
                        <textarea name="certifications" class="ks-form-control" rows="2" placeholder="e.g. FIFA B License, First Aid Certified, Strength & Conditioning Specialist"><?= htmlspecialchars($_POST['certifications'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Squad / Team Assignment -->
        <div class="ks-content-card mb-3">
            <div class="ks-card-header py-2 px-3">
                <div class="ks-header-left">
                    <i class="bi bi-people-fill text-primary fs-6"></i>
                    <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">4. Squad / Team Assignment (Optional)</h3>
                </div>
            </div>
            <div class="p-3 px-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ks-form-label">Assign to Team</label>
                        <select name="team_id" class="ks-form-select">
                            <option value="">No initial team assignment</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= (int)$t['id'] ?>" <?= (($_POST['team_id'] ?? '') == $t['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Role in Squad</label>
                        <select name="coach_role" class="ks-form-select">
                            <option value="head_coach" <?= (($_POST['coach_role'] ?? 'head_coach') === 'head_coach') ? 'selected' : '' ?>>Head Coach</option>
                            <option value="assistant_coach" <?= (($_POST['coach_role'] ?? '') === 'assistant_coach') ? 'selected' : '' ?>>Assistant Coach</option>
                            <option value="fitness_trainer" <?= (($_POST['coach_role'] ?? '') === 'fitness_trainer') ? 'selected' : '' ?>>Fitness Trainer</option>
                            <option value="physiotherapist" <?= (($_POST['coach_role'] ?? '') === 'physiotherapist') ? 'selected' : '' ?>>Physiotherapist</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex justify-content-end gap-2 mt-3 mb-4">
            <a href="/coaches" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">Cancel</a>
            <button type="submit" class="ks-btn ks-btn-primary px-4 py-2">
                <i class="bi bi-check-lg"></i>
                <span>Save Coach</span>
            </button>
        </div>
    </form>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
