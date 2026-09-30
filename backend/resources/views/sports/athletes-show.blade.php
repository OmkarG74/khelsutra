<?php
$athleteId = (int)($id ?? ($_GET['id'] ?? 0));
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

$canEditAthlete = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'athlete.update', $orgId) ||
    $permissionService->hasPermission($userPayload, 'athlete.edit', $orgId)
);
$canDeleteAthlete = !$isCoach && !$isAthlete && $permissionService->hasPermission($userPayload, 'athlete.delete', $orgId);

$accessDenied = false;
$accessDeniedMessage = 'Access denied: You are not authorized to view this athlete profile.';

if ($isAthlete) {
    $myAthleteId = (int)($currentUser['athlete_id'] ?? 0);
    if ($myAthleteId !== $athleteId) {
        $accessDenied = true;
        $accessDeniedMessage = 'Access denied: Athletes are strictly restricted to viewing their own profile.';
    }
} elseif ($isCoach) {
    $coachId = (int)($currentUser['coach_id'] ?? 0);
    $pdo = \App\Services\BaseService::getDatabaseConnection();
    if (!$coachId && !empty($currentUser['id']) && $pdo) {
        $cStmt = $pdo->prepare("
            SELECT cp.id 
            FROM coach_profiles cp 
            JOIN employees e ON cp.employee_id = e.id 
            WHERE e.user_id = :uid AND cp.organization_id = :oid AND cp.deleted_at IS NULL 
            LIMIT 1
        ");
        $cStmt->execute([':uid' => (int)$currentUser['id'], ':oid' => $orgId]);
        $coachId = (int)($cStmt->fetchColumn() ?: 0);
    }
    $isAuthorizedCoach = false;
    if ($coachId && $pdo) {
        $tcStmt = $pdo->prepare("
            SELECT 1 
            FROM team_coaches tc
            JOIN team_members tm ON tc.team_id = tm.team_id AND tm.is_current = 1
            JOIN teams t ON tm.team_id = t.id AND t.deleted_at IS NULL
            WHERE tc.coach_id = :cid AND tm.athlete_id = :aid AND tc.organization_id = :oid
            LIMIT 1
        ");
        $tcStmt->execute([':cid' => $coachId, ':aid' => $athleteId, ':oid' => $orgId]);
        $isAuthorizedCoach = (bool)$tcStmt->fetchColumn();
    }
    if (!$isAuthorizedCoach) {
        $accessDenied = true;
        $accessDeniedMessage = 'Access denied: You are not authorized to view athletes outside your assigned squads.';
    }
}

$athleteService = new \App\Services\Athlete\AthleteService();
$athlete = (!$accessDenied) ? $athleteService->getAthlete($orgId, $athleteId) : null;

if ($accessDenied && !headers_sent()) {
    http_response_code(403);
} elseif (!$accessDenied && !$athlete && !headers_sent()) {
    http_response_code(404);
}

$fullName = $athlete
    ? trim(($athlete['first_name'] ?? '') . ' ' . (!empty($athlete['middle_name']) ? $athlete['middle_name'] . ' ' : '') . ($athlete['last_name'] ?? ''))
    : '';
$title = $athlete ? ($fullName . ' — Athlete Profile — KhelSutra') : 'Athlete Details — KhelSutra';
$pageTitle = $title;
$activePage = 'athletes';

$pwdReset = $_SESSION['athlete_password_reset'] ?? null;
if ($pwdReset && ($pwdReset['athlete_id'] ?? 0) === $athleteId) {
    unset($_SESSION['athlete_password_reset']);
} else {
    $pwdReset = null;
}

$accProvisioned = $_SESSION['athlete_account_provisioned'] ?? null;
if ($accProvisioned) {
    unset($_SESSION['athlete_account_provisioned']);
}

ob_start();
?>

<div class="ks-page">
    <?php if ($accessDenied): ?>
        <div class="ks-content-card p-5 text-center my-4" style="border: 1px solid #FECACA; background: #FEF2F2; border-radius: 12px;">
            <div class="mb-3"><i class="bi bi-shield-lock-fill fs-1 text-danger"></i></div>
            <h4 class="fw-bold text-danger">403 — Access Forbidden</h4>
            <p class="text-muted small"><?= htmlspecialchars($accessDeniedMessage, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="mt-3">
                <a href="<?= $isAthlete ? '/dashboard' : '/athletes' ?>" class="btn btn-outline-danger" style="border-radius: 8px; font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Return to <?= $isAthlete ? 'Dashboard' : 'My Squad Athletes' ?>
                </a>
            </div>
        </div>
    <?php elseif (!$athlete): ?>
        <div class="ks-content-card p-5 text-center my-4">
            <div class="mb-3"><i class="bi bi-person-x fs-1 text-muted"></i></div>
            <h4 class="fw-bold text-dark">Athlete Not Found</h4>
            <p class="text-muted small">The requested athlete record does not exist, has been archived, or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/athletes" class="btn btn-outline-secondary" style="border-radius: 8px; font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Athletes
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php
        $initials = strtoupper(substr(trim($athlete['first_name'] ?? 'A'), 0, 1) . substr(trim($athlete['last_name'] ?? 'A'), 0, 1));
        $statusVal = strtolower($athlete['status'] ?? 'active');
        $statusBadgeStyle = match ($statusVal) {
            'active'    => 'background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0;',
            'injured'   => 'background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A;',
            'suspended' => 'background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA;',
            default     => 'background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1;',
        };
        $activeTeams = $athlete['teams'] ?? [];
        if (empty($activeTeams) && !empty($athlete['team_name'])) {
            $activeTeams = [[
                'id' => (int)($athlete['team_id'] ?? 0),
                'name' => $athlete['team_name'],
                'member_role' => 'player',
            ]];
        }

        // Format address parts cleanly
        $addrParts = array_values(array_filter([
            trim((string)($athlete['address_line1'] ?? '')),
            trim((string)($athlete['city'] ?? '')),
            trim((string)($athlete['state'] ?? '')),
        ], fn($v) => $v !== ''));
        $formattedAddress = !empty($addrParts) ? implode(', ', $addrParts) : '';
        if (!empty($athlete['postal_code'])) {
            $formattedAddress .= ($formattedAddress !== '' ? ' - ' : '') . trim((string)$athlete['postal_code']);
        }

        $genderDisplay = (!empty($athlete['gender']) && $athlete['gender'] !== 'not_specified')
            ? ucfirst(str_replace('_', ' ', (string)$athlete['gender']))
            : null;
        ?>

        <!-- Compact Athlete Header -->
        <div class="mb-3">
            <?php if (!$isAthlete): ?>
            <a href="/athletes" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1 mb-2" style="font-size: 12.5px;">
                <i class="bi bi-arrow-left"></i> Back to Athletes
            </a>
            <?php endif; ?>

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
                                <span>Reg ID: <strong class="text-dark"><?= htmlspecialchars($athlete['athlete_code'] ?? 'Not provided', ENT_QUOTES, 'UTF-8') ?></strong></span>
                                <span>&bull;</span>
                                <span>Primary Sport: <strong class="text-dark"><?= htmlspecialchars($athlete['sport_name'] ?? 'Not provided', ENT_QUOTES, 'UTF-8') ?></strong></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($canEditAthlete): ?>
                    <div class="d-flex align-items-center gap-2">
                        <a href="/athletes/<?= (int)$athlete['id'] ?>/edit" class="ks-btn ks-btn-primary px-3 py-2" id="btnEditAthleteHeader">
                            <i class="bi bi-pencil-square"></i>
                            <span>Edit Athlete</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($pwdReset): ?>
            <div class="alert alert-warning d-flex align-items-center justify-content-between mb-3 p-3 rounded-3" style="border-left: 4px solid #F59E0B;">
                <div>
                    <strong class="d-block mb-1"><i class="bi bi-key-fill me-1"></i> Temporary Password Reset Successfully</strong>
                    <span class="small">New Temporary Password: <code class="fs-6 px-2 py-1 bg-white rounded border fw-bold text-dark"><?= htmlspecialchars($pwdReset['temp_password'], ENT_QUOTES, 'UTF-8') ?></code></span>
                    <div class="text-muted small mt-1">Please copy and communicate this to the athlete. It will not be displayed again.</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark ms-3" onclick="navigator.clipboard.writeText('<?= addslashes($pwdReset['temp_password']) ?>'); if(window.ksToast){ksToast('Password copied to clipboard');}">
                    <i class="bi bi-clipboard me-1"></i> Copy
                </button>
            </div>
        <?php endif; ?>

        <?php if ($accProvisioned): ?>
            <div class="alert alert-success d-flex align-items-center justify-content-between mb-3 p-3 rounded-3" style="border-left: 4px solid #10B981;">
                <div>
                    <strong class="d-block mb-1"><i class="bi bi-person-check-fill me-1"></i> Login Account Created Successfully</strong>
                    <div class="small">Login Email: <strong><?= htmlspecialchars($accProvisioned['email'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div class="small mt-1">Temporary Password: <code class="fs-6 px-2 py-1 bg-white rounded border fw-bold text-dark"><?= htmlspecialchars($accProvisioned['temp_password'], ENT_QUOTES, 'UTF-8') ?></code></div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark ms-3" onclick="navigator.clipboard.writeText('<?= addslashes($accProvisioned['temp_password']) ?>'); if(window.ksToast){ksToast('Password copied to clipboard');}">
                    <i class="bi bi-clipboard me-1"></i> Copy Password
                </button>
            </div>
        <?php endif; ?>

        <div class="row g-3 align-items-start">
            <!-- LEFT / MAIN AREA: Personal Information, Guardian, Documents -->
            <div class="col-12 col-lg-8">
                <!-- 1. Personal Information -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-person-vcard text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Personal Information</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <div class="text-muted" style="font-size: 12px;">Date of Birth</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($athlete['date_of_birth']) ? date('M d, Y', strtotime($athlete['date_of_birth'])) : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="text-muted" style="font-size: 12px;">Gender</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= $genderDisplay ? htmlspecialchars($genderDisplay, ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="text-muted" style="font-size: 12px;">Phone</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($athlete['phone']) ? htmlspecialchars($athlete['phone'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="text-muted" style="font-size: 12px;">Email</div>
                                <div class="fw-medium mt-1 text-break" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($athlete['email']) ? htmlspecialchars($athlete['email'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="text-muted" style="font-size: 12px;">Blood Group</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($athlete['blood_group']) ? htmlspecialchars($athlete['blood_group'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="text-muted" style="font-size: 12px;">Registration Date</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?php if (!empty($athlete['registration_date'])): ?>
                                        <?= date('M d, Y', strtotime($athlete['registration_date'])) ?>
                                    <?php elseif (!empty($athlete['created_at'])): ?>
                                        <?= date('M d, Y', strtotime($athlete['created_at'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted fw-normal">Not provided</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-12 pt-1 border-top">
                                <div class="text-muted" style="font-size: 12px;">Address</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= $formattedAddress !== '' ? htmlspecialchars($formattedAddress, ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Guardian / Emergency Contact -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-shield-check text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Guardian / Emergency Contact</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <?php if (!empty($athlete['guardian'])): ?>
                            <?php
                            $gName = trim((string)($athlete['guardian']['full_name'] ?? (($athlete['guardian']['first_name'] ?? '') . ' ' . ($athlete['guardian']['last_name'] ?? ''))));
                            $gRel = trim((string)($athlete['guardian']['relationship'] ?? ''));
                            $gPhone = trim((string)($athlete['guardian']['phone'] ?? ''));
                            $gEmail = trim((string)($athlete['guardian']['email'] ?? ''));
                            ?>
                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <div class="text-muted" style="font-size: 12px;">Guardian Name</div>
                                    <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                        <?= $gName !== '' ? htmlspecialchars($gName, ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="text-muted" style="font-size: 12px;">Relationship</div>
                                    <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                        <?= $gRel !== '' ? htmlspecialchars(ucfirst($gRel), ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="text-muted" style="font-size: 12px;">Phone</div>
                                    <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                        <?= $gPhone !== '' ? htmlspecialchars($gPhone, ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="text-muted" style="font-size: 12px;">Email</div>
                                    <div class="fw-medium mt-1 text-break" style="font-size: 13.5px; color: #0F172A;">
                                        <?= $gEmail !== '' ? htmlspecialchars($gEmail, ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not provided</span>' ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 py-1">
                                <span class="text-muted small">No guardian or emergency contact has been added.</span>
                                <?php if ($canEditAthlete): ?>
                                    <a href="/athletes/<?= (int)$athlete['id'] ?>/edit" class="btn btn-sm btn-outline-primary" style="font-size: 12px; border-radius: 6px;">
                                        <i class="bi bi-pencil me-1"></i> Edit Athlete
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Athlete Documents -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3 d-flex align-items-center justify-content-between">
                        <div class="ks-header-left">
                            <i class="bi bi-file-earmark-text text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Athlete Documents</h3>
                        </div>
                        <?php if ($canEditAthlete): ?>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocModal" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: 6px; font-size: 12px; font-weight: 600;">
                            <i class="bi bi-upload me-1"></i> Upload Document
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="p-0">
                        <?php if (!empty($athlete['documents']) && count($athlete['documents']) > 0): ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0 ks-table-athlete-docs" style="width: 100%; font-size: 13px;">
                                    <thead class="table-light">
                                        <tr class="small text-muted">
                                            <th class="ps-3" style="width: 22%;">Document Type</th>
                                            <th style="width: 30%;">Document Name / Number</th>
                                            <th style="width: 18%;">Validity</th>
                                            <th style="width: 16%;">Uploaded Date</th>
                                            <th class="text-end pe-3" style="width: 14%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($athlete['documents'] as $doc): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <span class="badge bg-light text-dark border">
                                                        <?= htmlspecialchars(\App\Services\Athlete\AthleteDocumentService::DOCUMENT_TYPES[$doc['document_type']] ?? ucfirst(str_replace('_', ' ', $doc['document_type'])), ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($doc['document_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <?php if (!empty($doc['document_number'])): ?>
                                                        <div class="text-muted small">No: <?= htmlspecialchars($doc['document_number'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-muted">
                                                    <?php if (!empty($doc['expiry_date'])): ?>
                                                        Exp: <?= date('M d, Y', strtotime($doc['expiry_date'])) ?>
                                                    <?php elseif (!empty($doc['issue_date'])): ?>
                                                        Issued: <?= date('M d, Y', strtotime($doc['issue_date'])) ?>
                                                    <?php else: ?>
                                                        Not provided
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-muted">
                                                    <?= !empty($doc['created_at']) ? date('M d, Y', strtotime($doc['created_at'])) : 'Not provided' ?>
                                                </td>
                                                <td class="text-end text-nowrap pe-3">
                                                    <div class="d-inline-flex align-items-center gap-1">
                                                        <a href="/athletes/<?= (int)$athlete['id'] ?>/documents/<?= (int)$doc['id'] ?>/view" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 11px;" title="View Document">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
                                                        <a href="/athletes/<?= (int)$athlete['id'] ?>/documents/<?= (int)$doc['id'] ?>/download" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 11px;" title="Download Document">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                        <?php if ($canEditAthlete || $canDeleteAthlete): ?>
                                                        <form action="/athletes/<?= (int)$athlete['id'] ?>/documents/<?= (int)$doc['id'] ?>/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this document?');">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size: 11px;" title="Delete Document">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted small">
                                <i class="bi bi-file-earmark-arrow-up fs-4 d-block mb-1 text-secondary"></i>
                                No documents uploaded for this athlete yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT / SUPPORTING AREA: Sport & Team, Account Access, Administrative Information -->
            <div class="col-12 col-lg-4">
                <!-- 1. Sport & Team (Prominent top card in supporting column) -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-trophy text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Sport &amp; Team</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <div class="mb-3">
                            <div class="text-muted" style="font-size: 12px;">Primary Sport</div>
                            <div class="mt-1">
                                <span class="ks-badge ks-badge-blue" style="font-size: 12.5px; padding: 5px 12px;">
                                    <?= htmlspecialchars($athlete['sport_name'] ?? 'Not provided', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted mb-1" style="font-size: 12px;">Assigned Team(s)</div>
                            <?php if (!empty($activeTeams)): ?>
                                <div class="d-flex flex-column gap-2 mt-1">
                                    <?php foreach ($activeTeams as $tm): ?>
                                        <a href="/teams/<?= (int)$tm['id'] ?>" class="d-flex align-items-center justify-content-between text-decoration-none p-2 rounded-2 border" style="background: #F8FAFC; border-color: #E2E8F0; color: #0F172A;">
                                            <span class="fw-semibold small text-primary">
                                                <i class="bi bi-people-fill me-1"></i>
                                                <?= htmlspecialchars($tm['name'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <i class="bi bi-chevron-right text-muted" style="font-size: 11px;"></i>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small mt-1">Not currently assigned to an active team.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 2. Athlete Account Access -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-person-badge text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Athlete Account Access</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <?php if (!empty($athlete['user_account'])): ?>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <span class="text-muted d-block" style="font-size: 12px;">Login Status</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 mt-1">
                                        <?= htmlspecialchars(ucfirst($athlete['user_account']['user_status'] ?? 'Active'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block" style="font-size: 12px;">Role</span>
                                    <strong class="text-dark small d-block mt-1"><?= htmlspecialchars($athlete['user_account']['role_name'] ?? 'Athlete', ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                            </div>

                            <div class="mb-2">
                                <span class="text-muted d-block" style="font-size: 12px;">Login Identifier</span>
                                <strong class="text-dark small d-block text-break mt-1"><?= htmlspecialchars($athlete['user_account']['email'] ?? 'Not provided', ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>

                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size: 12px;">Last Login</span>
                                <span class="text-secondary small d-block mt-1">
                                    <?= !empty($athlete['user_account']['last_login_at']) ? date('M d, Y H:i', strtotime($athlete['user_account']['last_login_at'])) : 'Never logged in' ?>
                                </span>
                            </div>

                            <?php if ($canEditAthlete): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#resetPasswordModal" style="border-radius: 6px; font-size: 12px; font-weight: 500;">
                                <i class="bi bi-key me-1"></i> Reset Password
                            </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="py-1">
                                <p class="text-muted small mb-2">No login account is currently provisioned for this athlete.</p>
                                <?php if ($canEditAthlete): ?>
                                <button type="button" class="ks-btn ks-btn-primary btn-sm w-100 justify-content-center" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                                    <i class="bi bi-person-plus"></i>
                                    <span>Provision Login Account</span>
                                </button>
                                <?php endif; ?>
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
                        <?php $cleanNotes = trim((string)($athlete['notes'] ?? '')); ?>
                        <?php if ($cleanNotes !== ''): ?>
                            <div class="mb-3">
                                <div class="text-muted" style="font-size: 12px;">Notes</div>
                                <div class="text-dark small mt-1" style="line-height: 1.5;">
                                    <?= nl2br(htmlspecialchars($cleanNotes, ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex flex-column gap-1 small text-muted <?= $cleanNotes !== '' ? 'pt-2 border-top' : '' ?>" style="font-size: 12px;">
                            <div>Created: <span class="text-dark fw-medium"><?= !empty($athlete['created_at']) ? date('M d, Y', strtotime($athlete['created_at'])) : 'Not provided' ?></span></div>
                            <div>Last updated: <span class="text-dark fw-medium"><?= !empty($athlete['updated_at']) ? date('M d, Y', strtotime($athlete['updated_at'])) : 'Not provided' ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($canEditAthlete): ?>
        <!-- MODAL: Upload Document -->
        <div class="modal fade" id="uploadDocModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="/athletes/<?= (int)$athlete['id'] ?>/documents/upload" method="POST" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-dark fs-6"><i class="bi bi-upload text-primary me-2"></i>Upload Athlete Document</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="ks-form-label">Document Type <span class="text-danger">*</span></label>
                                <select name="document_type" class="ks-form-select" required>
                                    <option value="id_proof">Government ID / Aadhaar</option>
                                    <option value="birth_certificate">Birth Certificate</option>
                                    <option value="passport">Passport</option>
                                    <option value="medical_certificate">Medical Certificate</option>
                                    <option value="sports_certificate">Sports Certificate</option>
                                    <option value="consent_form">Consent Form</option>
                                    <option value="other">Other Document</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="ks-form-label">Document Title</label>
                                <input type="text" name="document_name" class="ks-form-control" placeholder="e.g. Aadhaar Card / Medical Clearance">
                            </div>
                            <div class="mb-3">
                                <label class="ks-form-label">Document Number</label>
                                <input type="text" name="document_number" class="ks-form-control" placeholder="Optional identifier">
                            </div>
                            <div class="mb-3">
                                <label class="ks-form-label">Select File <span class="text-danger">*</span></label>
                                <input type="file" name="document_file" class="ks-form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                                <small class="text-muted">PDF, JPG, PNG up to 10MB.</small>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="ks-form-label">Issue Date</label>
                                    <input type="date" name="issue_date" class="ks-form-control">
                                </div>
                                <div class="col-6">
                                    <label class="ks-form-label">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="ks-form-control">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="ks-form-label">Notes / Remarks</label>
                                <input type="text" name="notes" class="ks-form-control" placeholder="Optional notes">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="ks-btn ks-btn-primary btn-sm">Upload Document</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: Provision Account -->
        <div class="modal fade" id="createAccountModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="/athletes/<?= (int)$athlete['id'] ?>/account/create" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-dark fs-6"><i class="bi bi-person-plus text-primary me-2"></i>Provision Athlete Login Account</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">
                                This provisions a secure login account with the <strong>Athlete</strong> role, enabling access to the Flutter mobile app and web portal.
                            </p>
                            <div class="mb-3">
                                <label class="ks-form-label">Login Email <span class="text-danger">*</span></label>
                                <input type="email" name="login_email" class="ks-form-control" required value="<?= htmlspecialchars($athlete['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="athlete@example.com">
                            </div>
                            <div class="mb-3">
                                <label class="ks-form-label">Username (Optional)</label>
                                <input type="text" name="username" class="ks-form-control" placeholder="Leave blank to auto-generate">
                            </div>
                            <div class="mb-2">
                                <label class="ks-form-label">Temporary Password</label>
                                <input type="password" name="password" class="ks-form-control" placeholder="Leave blank to auto-generate a secure password">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="ks-btn ks-btn-primary btn-sm">Create Login Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: Reset Password -->
        <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="/athletes/<?= (int)$athlete['id'] ?>/account/reset-password" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-dark fs-6"><i class="bi bi-key text-primary me-2"></i>Reset Athlete Password</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">
                                You can specify a new temporary password or leave it blank to auto-generate a secure temporary password.
                            </p>
                            <div class="mb-3">
                                <label class="ks-form-label">New Password (Optional)</label>
                                <input type="password" name="password" class="ks-form-control" placeholder="Leave blank to auto-generate">
                                <small class="text-muted">Will be encrypted using bcrypt and shown once for delivery.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning btn-sm text-dark fw-bold">Reset Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
