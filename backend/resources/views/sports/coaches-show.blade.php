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

$canViewCoach = !$isAthlete && (
    $isCoach ||
    $permissionService->hasPermission($userPayload, 'coach.view', $orgId) ||
    $permissionService->hasPermission($userPayload, 'coach.manage', $orgId) ||
    $permissionService->hasPermission($userPayload, 'employee.view', $orgId)
);
$canEditCoach = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'coach.update', $orgId) ||
    $permissionService->hasPermission($userPayload, 'coach.manage', $orgId)
);

$accessDenied = !$canViewCoach;
$accessDeniedMessage = 'Access denied: You do not have permission to view coaching staff profiles.';

$coachService = new \App\Services\Coach\CoachService();
$coach = (!$accessDenied && $coachId > 0) ? $coachService->getCoach($orgId, $coachId) : null;

if ($accessDenied && !headers_sent()) {
    http_response_code(403);
} elseif (!$accessDenied && !$coach && !headers_sent()) {
    http_response_code(404);
}

$fullName = $coach
    ? trim(($coach['first_name'] ?? '') . ' ' . (!empty($coach['middle_name']) ? trim((string)$coach['middle_name']) . ' ' : '') . ($coach['last_name'] ?? ''))
    : '';
$title = $coach ? ($fullName . ' — Coach Profile — KhelSutra') : 'Coach Details — KhelSutra';
$pageTitle = $title;
$activePage = 'coaches';

ob_start();
?>

<div class="ks-page">
    <?php if ($accessDenied): ?>
        <div class="ks-content-card p-5 text-center my-4" style="border: 1px solid #FECACA; background: #FEF2F2; border-radius: 12px;">
            <div class="mb-3"><i class="bi bi-shield-lock-fill fs-1 text-danger"></i></div>
            <h4 class="fw-bold text-danger">403 — Access Forbidden</h4>
            <p class="text-muted small"><?= htmlspecialchars($accessDeniedMessage, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="mt-3">
                <a href="/dashboard" class="btn btn-outline-danger" style="border-radius: 8px; font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Return to Dashboard
                </a>
            </div>
        </div>
    <?php elseif (!$coach): ?>
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
        <?php
        $initials = strtoupper(substr(trim($coach['first_name'] ?? 'C'), 0, 1) . substr(trim($coach['last_name'] ?? 'C'), 0, 1));
        $statusVal = strtolower((string)($coach['coach_status'] ?? ($coach['status'] ?? 'active')));
        $statusBadgeStyle = match ($statusVal) {
            'active' => 'background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0;',
            default  => 'background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1;',
        };

        $expYearsRaw = $coach['experience_years'] ?? null;
        if ($expYearsRaw !== null && $expYearsRaw !== '') {
            $expNum = (float)$expYearsRaw;
            $expClean = ((float)(int)$expNum === $expNum) ? (string)(int)$expNum : rtrim(rtrim(number_format($expNum, 2, '.', ''), '0'), '.');
            $experienceDisplay = $expClean . ' ' . ($expNum == 1.0 ? 'Year' : 'Years');
        } else {
            $experienceDisplay = null;
        }

        // Format address parts cleanly
        $addrParts = array_values(array_filter([
            trim((string)($coach['address_line1'] ?? '')),
            trim((string)($coach['city'] ?? '')),
            trim((string)($coach['state'] ?? '')),
        ], fn($v) => $v !== ''));
        $formattedAddress = !empty($addrParts) ? implode(', ', $addrParts) : '';
        if (!empty($coach['postal_code'])) {
            $formattedAddress .= ($formattedAddress !== '' ? ' - ' : '') . trim((string)$coach['postal_code']);
        }

        $genderDisplay = (!empty($coach['gender']) && $coach['gender'] !== 'not_specified')
            ? ucfirst(str_replace('_', ' ', (string)$coach['gender']))
            : null;

        $empTypeDisplay = !empty($coach['employment_type'])
            ? ucwords(str_replace('_', ' ', (string)$coach['employment_type']))
            : null;

        $activeTeams = $coach['teams'] ?? [];
        $trainingSessions = $coach['training_sessions'] ?? [];

        $emName = trim((string)($coach['emergency_contact_name'] ?? ''));
        $emPhone = trim((string)($coach['emergency_contact_phone'] ?? ''));
        $emRel = trim((string)($coach['emergency_contact_relationship'] ?? ''));
        $hasEmergencyContact = ($emName !== '' || $emPhone !== '' || $emRel !== '');
        ?>

        <!-- Compact Coach Header -->
        <div class="mb-3">
            <a href="/coaches" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1 mb-2" style="font-size: 12.5px;">
                <i class="bi bi-arrow-left"></i> Back to Coaches
            </a>

            <div class="ks-content-card p-3 px-4" style="border-radius: 12px;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 52px; height: 52px; border-radius: 50%; background: #E0F2FE; color: #0284C7; display: flex; align-items: center; justify-content: center; font-size: 19px; font-weight: 700; flex-shrink: 0; border: 2px solid #BAE6FD;">
                            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <h1 class="ks-page-title mb-0" style="font-size: 20px; line-height: 1.25;">
                                    <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>
                                </h1>
                                <span class="badge rounded-pill" style="<?= $statusBadgeStyle ?> font-size: 11px; padding: 4px 10px; font-weight: 600; text-transform: capitalize;">
                                    <?= htmlspecialchars($statusVal, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2 mt-1 small text-muted">
                                <span>Coach Code: <strong class="text-dark"><?= htmlspecialchars($coach['coach_code'] ?? 'Not provided', ENT_QUOTES, 'UTF-8') ?></strong></span>
                                <span>&bull;</span>
                                <span>Employee Code: <strong class="text-dark"><?= htmlspecialchars($coach['employee_code'] ?? 'Not provided', ENT_QUOTES, 'UTF-8') ?></strong></span>
                                <span>&bull;</span>
                                <span>Specialization: <strong class="text-dark"><?= htmlspecialchars($coach['specialization'] ?? 'General Coaching', ENT_QUOTES, 'UTF-8') ?></strong></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($canEditCoach): ?>
                    <div class="d-flex align-items-center gap-2">
                        <a href="/coaches/<?= (int)$coach['coach_profile_id'] ?>/edit" class="ks-btn ks-btn-primary px-3 py-2" id="btnEditCoachHeader">
                            <i class="bi bi-pencil-square"></i>
                            <span>Edit Coach</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start">
            <!-- LEFT / MAIN AREA: Coaching Credentials, Personal & Employment, Assigned Teams -->
            <div class="col-12 col-lg-8">
                <!-- 1. Coaching Credentials & Experience -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-award text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Coaching Credentials &amp; Experience</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <div class="row g-3">
                            <div class="col-12 col-sm-4">
                                <div class="text-muted" style="font-size: 12px;">Specialization</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($coach['specialization']) ? htmlspecialchars($coach['specialization'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="text-muted" style="font-size: 12px;">Experience</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= $experienceDisplay ? htmlspecialchars($experienceDisplay, ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="text-muted" style="font-size: 12px;">License Number</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($coach['license_number']) ? htmlspecialchars($coach['license_number'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 pt-2 border-top">
                                <div class="text-muted" style="font-size: 12px;">Academic Qualifications</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($coach['qualification']) ? htmlspecialchars($coach['qualification'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 pt-2 border-top">
                                <div class="text-muted" style="font-size: 12px;">Certifications</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($coach['certifications']) ? htmlspecialchars($coach['certifications'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Personal & Employment Details -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-person-vcard text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Personal &amp; Employment Details</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <div class="row g-3">
                            <div class="col-12 col-sm-4">
                                <div class="text-muted" style="font-size: 12px;">Designation</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($coach['designation']) ? htmlspecialchars($coach['designation'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="text-muted" style="font-size: 12px;">Department</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($coach['department_name']) ? htmlspecialchars($coach['department_name'], ENT_QUOTES, 'UTF-8') : 'Sports Department' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="text-muted" style="font-size: 12px;">Employment Type</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= $empTypeDisplay ? htmlspecialchars($empTypeDisplay, ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="text-muted" style="font-size: 12px;">Phone</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($coach['phone']) ? htmlspecialchars($coach['phone'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="text-muted" style="font-size: 12px;">Email Address</div>
                                <div class="fw-medium mt-1 text-break" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($coach['email']) ? htmlspecialchars($coach['email'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="text-muted" style="font-size: 12px;">Date of Birth</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($coach['date_of_birth']) ? date('M d, Y', strtotime($coach['date_of_birth'])) : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="text-muted" style="font-size: 12px;">Gender</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= $genderDisplay ? htmlspecialchars($genderDisplay, ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="text-muted" style="font-size: 12px;">Blood Group</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($coach['blood_group']) ? htmlspecialchars($coach['blood_group'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="text-muted" style="font-size: 12px;">Joining Date</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($coach['joining_date']) ? date('M d, Y', strtotime($coach['joining_date'])) : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 pt-2 border-top">
                                <div class="text-muted" style="font-size: 12px;">Address</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= $formattedAddress !== '' ? htmlspecialchars($formattedAddress, ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Assigned Teams & Squads -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-people text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Assigned Teams &amp; Squads</h3>
                        </div>
                    </div>
                    <?php if (empty($activeTeams)): ?>
                        <div class="p-3 px-4 text-muted small">
                            No active team assignments.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" style="width: 100%; font-size: 13px;">
                                <thead class="table-light">
                                    <tr class="small text-muted">
                                        <th class="ps-4">Team / Squad</th>
                                        <th>Sport</th>
                                        <th>Role in Squad</th>
                                        <th>Assigned Since</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activeTeams as $tm): ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold text-dark">
                                                <?= htmlspecialchars($tm['team_name'] ?? ($tm['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td>
                                                <span class="ks-badge ks-badge-blue">
                                                    <?= htmlspecialchars($tm['sport_name'] ?? 'Sports', ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td class="text-capitalize">
                                                <?= htmlspecialchars(str_replace('_', ' ', (string)($tm['coach_role'] ?? 'head_coach')), ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td class="text-muted small">
                                                <?= !empty($tm['start_date']) ? date('M d, Y', strtotime($tm['start_date'])) : 'Not provided' ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="/teams/<?= (int)($tm['team_id'] ?? ($tm['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 11.5px;">
                                                    <i class="bi bi-eye"></i> View Team
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT / SUPPORTING AREA: Recent Training Sessions, Emergency Contact, Administrative Information -->
            <div class="col-12 col-lg-4">
                <!-- 1. Recent Training Sessions -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-clock-history text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Recent Training Sessions</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <?php if (empty($trainingSessions)): ?>
                            <div class="text-muted small">
                                No recent training sessions recorded for this coach.
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($trainingSessions as $ts): ?>
                                    <div class="p-2 px-3 rounded-2 border" style="background: #F8FAFC; border-color: #E2E8F0;">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="fw-semibold text-dark small">
                                                <?= htmlspecialchars($ts['title'] ?? ($ts['session_title'] ?? 'Training Session'), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <span class="badge bg-light text-dark border" style="font-size: 11px;">
                                                <?= !empty($ts['training_date']) ? date('M d', strtotime($ts['training_date'])) : '—' ?>
                                            </span>
                                        </div>
                                        <div class="text-muted mt-1" style="font-size: 12px;">
                                            <?= htmlspecialchars($ts['team_name'] ?? 'General Squad', ENT_QUOTES, 'UTF-8') ?>
                                            &bull;
                                            <?= htmlspecialchars($ts['venue_name'] ?? 'Academy Ground', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. Emergency Contact -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-telephone-inbound text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Emergency Contact</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <?php if ($hasEmergencyContact): ?>
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="text-muted" style="font-size: 12px;">Contact Name</div>
                                    <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                        <?= $emName !== '' ? htmlspecialchars($emName, ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted" style="font-size: 12px;">Phone</div>
                                    <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                        <?= $emPhone !== '' ? htmlspecialchars($emPhone, ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted" style="font-size: 12px;">Relationship</div>
                                    <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                        <?= $emRel !== '' ? htmlspecialchars(ucfirst($emRel), ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small">
                                No emergency contact information provided.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Administrative Information -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-info-circle text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Administrative Information</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <?php $cleanNotes = trim((string)($coach['notes'] ?? '')); ?>
                        <?php if ($cleanNotes !== ''): ?>
                            <div class="mb-3">
                                <div class="text-muted" style="font-size: 12px;">Notes</div>
                                <div class="text-dark small mt-1" style="line-height: 1.5;">
                                    <?= nl2br(htmlspecialchars($cleanNotes, ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex flex-column gap-1 small text-muted <?= $cleanNotes !== '' ? 'pt-2 border-top' : '' ?>" style="font-size: 12px;">
                            <div>Created: <span class="text-dark fw-medium"><?= !empty($coach['created_at']) ? date('M d, Y', strtotime($coach['created_at'])) : 'Not provided' ?></span></div>
                            <div>Last updated: <span class="text-dark fw-medium"><?= !empty($coach['updated_at']) ? date('M d, Y', strtotime($coach['updated_at'])) : 'Not provided' ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
