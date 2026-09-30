<?php
$teamId = (int)($id ?? ($_GET['id'] ?? 0));
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

$canViewTeam = $isCoach || $isAthlete
    || $permissionService->hasPermission($userPayload, 'team.view', $orgId)
    || $permissionService->hasPermission($userPayload, 'team.manage', $orgId);

$canEditTeam = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'team.update', $orgId) ||
    $permissionService->hasPermission($userPayload, 'team.manage', $orgId)
);

$canManageCoaches = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'team.coaches.manage', $orgId) ||
    $permissionService->hasPermission($userPayload, 'team.manage', $orgId)
);

$canManageRoster = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'team.members.manage', $orgId) ||
    $permissionService->hasPermission($userPayload, 'team.manage', $orgId)
);

$accessDenied = !$canViewTeam;
$accessDeniedMessage = 'Access denied: You are not authorized to view this team.';

$pdo = \App\Services\BaseService::getDatabaseConnection();

if (!$accessDenied && $isAthlete && $pdo) {
    $athleteId = (int)($currentUser['athlete_id'] ?? 0);
    if ($athleteId <= 0 && $userId > 0) {
        $aStmt = $pdo->prepare("SELECT id FROM athletes WHERE user_id = :uid AND organization_id = :oid AND deleted_at IS NULL LIMIT 1");
        $aStmt->execute([':uid' => $userId, ':oid' => $orgId]);
        $athleteId = (int)($aStmt->fetchColumn() ?: 0);
    }
    $tmStmt = $pdo->prepare("SELECT 1 FROM team_members WHERE team_id = :tid AND athlete_id = :aid AND organization_id = :oid AND is_current = 1 LIMIT 1");
    $tmStmt->execute([':tid' => $teamId, ':aid' => $athleteId, ':oid' => $orgId]);
    if (!$tmStmt->fetchColumn()) {
        $accessDenied = true;
        $accessDeniedMessage = 'Access denied: You are not a rostered athlete in this team squad.';
    }
} elseif (!$accessDenied && $isCoach && $pdo) {
    $coachId = (int)($currentUser['coach_id'] ?? 0);
    if ($coachId <= 0 && $userId > 0) {
        $cStmt = $pdo->prepare("
            SELECT cp.id 
            FROM coach_profiles cp 
            JOIN employees e ON cp.employee_id = e.id 
            WHERE e.user_id = :uid AND cp.organization_id = :oid AND cp.deleted_at IS NULL 
            LIMIT 1
        ");
        $cStmt->execute([':uid' => $userId, ':oid' => $orgId]);
        $coachId = (int)($cStmt->fetchColumn() ?: 0);
    }
    $tcStmt = $pdo->prepare("SELECT 1 FROM team_coaches WHERE team_id = :tid AND coach_id = :cid AND organization_id = :oid LIMIT 1");
    $tcStmt->execute([':tid' => $teamId, ':cid' => $coachId, ':oid' => $orgId]);
    if (!$tcStmt->fetchColumn()) {
        $accessDenied = true;
        $accessDeniedMessage = 'Access denied: You are not assigned to coach this team squad.';
    }
}

$teamService = new \App\Services\Team\TeamService();
$team = (!$accessDenied) ? $teamService->getTeam($orgId, $teamId) : null;

$eligibleAthletes = [];
if ($team && $canManageRoster && $pdo) {
    $eligibleAthletesStmt = $pdo->prepare("
        SELECT a.id, a.athlete_code, a.first_name, a.last_name
        FROM athletes a
        WHERE a.organization_id = :org_id 
          AND a.status = 'active' 
          AND a.deleted_at IS NULL
          AND a.current_sport_id = :sport_id
          AND (
              :team_gender NOT IN ('male', 'female')
              OR a.gender = :team_gender
          )
          AND a.id NOT IN (
              SELECT tm.athlete_id 
              FROM team_members tm 
              WHERE tm.team_id = :team_id 
                AND tm.organization_id = :org_id2 
                AND tm.is_current = 1
          )
        ORDER BY a.first_name ASC, a.last_name ASC
    ");
    $teamGender = strtolower(trim($team['gender'] ?? ''));
    $eligibleAthletesStmt->execute([
        ':org_id' => $orgId, 
        ':team_id' => $teamId, 
        ':org_id2' => $orgId, 
        ':sport_id' => $team['sport_id'],
        ':team_gender' => $teamGender
    ]);
    $eligibleAthletes = $eligibleAthletesStmt ? $eligibleAthletesStmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

$eligibleCoaches = ($team && $canManageCoaches) ? $teamService->getEligibleCoaches($orgId, $teamId) : [];

$title = $team ? htmlspecialchars($team['name'] . ' — Team Details') : 'Team Details';
$pageTitle = $title;
$activePage = 'teams';

ob_start();
?>

<div class="ks-page">
    <?php if ($accessDenied): ?>
        <div class="ks-content-card p-5 text-center my-4" style="border: 1px solid #FECACA; background: #FEF2F2; border-radius: 12px;">
            <div class="mb-3"><i class="bi bi-shield-lock-fill fs-1 text-danger"></i></div>
            <h4 class="fw-bold text-danger">403 — Access Forbidden</h4>
            <p class="text-muted small"><?= htmlspecialchars($accessDeniedMessage, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="mt-3">
                <a href="<?= $isAthlete ? '/dashboard' : '/teams' ?>" class="btn btn-outline-danger" style="border-radius: 8px; font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Return to <?= $isAthlete ? 'Dashboard' : 'Teams' ?>
                </a>
            </div>
        </div>
    <?php elseif (!$team): ?>
        <div class="ks-content-card p-5 text-center my-4">
            <div class="mb-3"><i class="bi bi-shield-x fs-1 text-muted"></i></div>
            <h4 class="fw-bold text-dark">Team Not Found</h4>
            <p class="text-muted small">The requested team squad does not exist, has been archived, or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/teams" class="btn btn-outline-secondary" style="border-radius: 8px; font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Teams
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php
        $initials = strtoupper(substr(trim($team['name'] ?? 'T'), 0, 2));
        $statusVal = strtolower((string)($team['status'] ?? 'active'));
        $statusBadgeStyle = match ($statusVal) {
            'active' => 'background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0;',
            default  => 'background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1;',
        };
        $coachesList = $team['coaches'] ?? [];
        $currentAthletes = $team['current_athletes'] ?? [];
        $historicalAthletes = $team['historical_athletes'] ?? [];
        $upcomingTraining = $team['upcoming_training'] ?? [];
        $upcomingMatches = $team['upcoming_matches'] ?? [];
        ?>

        <!-- Compact Team Header -->
        <div class="mb-3">
            <a href="/teams" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1 mb-2" style="font-size: 12.5px;">
                <i class="bi bi-arrow-left"></i> Back to Teams
            </a>

            <div class="ks-content-card p-3 px-4" style="border-radius: 12px;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 52px; height: 52px; border-radius: 12px; background: #E0F2FE; color: #0284C7; display: flex; align-items: center; justify-content: center; font-size: 19px; font-weight: 700; flex-shrink: 0; border: 2px solid #BAE6FD;">
                            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <h1 class="ks-page-title mb-0" style="font-size: 20px; line-height: 1.25;">
                                    <?= htmlspecialchars($team['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </h1>
                                <span class="badge rounded-pill" style="<?= $statusBadgeStyle ?> font-size: 11px; padding: 4px 10px; font-weight: 600; text-transform: capitalize;">
                                    <?= htmlspecialchars($statusVal, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2 mt-1 small text-muted">
                                <span>Team Code: <strong class="text-dark"><?= htmlspecialchars($team['team_code'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong></span>
                                <span>&bull;</span>
                                <span>Sport: <strong class="text-dark"><?= htmlspecialchars($team['sport_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></strong></span>
                                <span>&bull;</span>
                                <span>Age Group: <strong class="text-dark"><?= htmlspecialchars($team['age_group'] ?: 'Open', ENT_QUOTES, 'UTF-8') ?></strong></span>
                                <span>&bull;</span>
                                <span>Division: <strong class="text-dark"><?= htmlspecialchars(ucfirst($team['gender'] ?? 'Open'), ENT_QUOTES, 'UTF-8') ?></strong></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($canEditTeam): ?>
                    <div class="d-flex align-items-center gap-2">
                        <a href="/teams/<?= (int)$team['id'] ?>/edit" class="ks-btn ks-btn-primary px-3 py-2" id="btnEditTeamHeader">
                            <i class="bi bi-pencil-square"></i>
                            <span>Edit Team</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start">
            <!-- Left Column: Team Information, Coaching Staff, Athlete Roster -->
            <div class="col-12 col-lg-8">
                <!-- 1. Team Information -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-shield-check text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Team Information</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <div class="row g-3">
                            <div class="col-6 col-sm-3">
                                <div class="text-muted" style="font-size: 12px;">Sport</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= htmlspecialchars($team['sport_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="text-muted" style="font-size: 12px;">Age Group</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= htmlspecialchars($team['age_group'] ?: 'Open', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="text-muted" style="font-size: 12px;">Gender Division</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= htmlspecialchars(ucfirst($team['gender'] ?? 'Open'), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="text-muted" style="font-size: 12px;">Formation / Level</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($team['formation_or_level']) ? htmlspecialchars($team['formation_or_level'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Standard</span>' ?>
                                </div>
                            </div>
                            <?php if (!empty($team['description'])): ?>
                            <div class="col-12 pt-2 border-top">
                                <div class="text-muted" style="font-size: 12px;">Description &amp; Notes</div>
                                <div class="mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= htmlspecialchars($team['description'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 2. Assigned Coaching Staff -->
                <!-- 2. Assigned Coaching Staff -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-person-badge text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Coaching Staff</h3>
                        </div>
                        <div class="ks-header-right d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark border" style="font-size: 11.5px; font-weight: 600;">
                                <?= count($coachesList) ?> <?= count($coachesList) === 1 ? 'Coach' : 'Coaches' ?>
                            </span>
                            <?php if ($canManageCoaches && !empty($eligibleCoaches)): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="collapse" data-bs-target="#assignCoachCollapse" aria-expanded="false" style="font-size: 12px; font-weight: 600;">
                                    <i class="bi bi-plus-lg me-1"></i>Add Coach
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <?php if (!empty($coachesList)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0" style="font-size: 13px;">
                                    <thead>
                                        <tr class="text-muted" style="font-size: 11.5px; text-transform: uppercase;">
                                            <th>Coach</th>
                                            <th>Role</th>
                                            <th>Specialization</th>
                                            <th>Contact</th>
                                            <?php if ($canManageCoaches): ?>
                                                <th class="text-end">Actions</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($coachesList as $cch): ?>
                                            <tr>
                                                <td class="py-2">
                                                    <a href="/coaches/<?= (int)$cch['coach_profile_id'] ?>" class="fw-semibold text-decoration-none" style="color: var(--ks-primary);">
                                                        <?= htmlspecialchars(trim(($cch['first_name'] ?? '') . ' ' . ($cch['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                                    </a>
                                                    <?php if (!empty($cch['coach_code'])): ?>
                                                        <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($cch['coach_code'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-2">
                                                    <span class="fw-medium text-dark"><?= htmlspecialchars(format_coach_role($cch['coach_role'] ?? 'head_coach'), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php if (!empty($cch['is_primary'])): ?>
                                                        <span class="badge rounded-pill ms-1" style="background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; font-size: 10px; font-weight: 600;">Primary</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-2 text-muted"><?= htmlspecialchars($cch['specialization'] ?: 'General', ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="py-2 text-muted"><?= htmlspecialchars($cch['phone'] ?: ($cch['email'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                                <?php if ($canManageCoaches): ?>
                                                    <td class="py-2 text-end">
                                                        <div class="d-inline-flex align-items-center justify-content-end gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-primary edit-coach-btn" style="padding: 3px 8px; font-size: 11.5px;"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editCoachModal"
                                                                data-coach-id="<?= (int)$cch['coach_id'] ?>"
                                                                data-coach-name="<?= htmlspecialchars(trim(($cch['first_name'] ?? '') . ' ' . ($cch['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                                                data-role="<?= htmlspecialchars($cch['coach_role'] ?? 'head_coach', ENT_QUOTES, 'UTF-8') ?>"
                                                                data-is-primary="<?= !empty($cch['is_primary']) ? '1' : '0' ?>">
                                                                Edit
                                                            </button>
                                                            <form action="/teams/<?= (int)$team['id'] ?>/coaches/<?= (int)$cch['coach_id'] ?>/remove" method="POST" class="d-inline m-0" onsubmit="return confirm('Remove this coach from the active coaching staff?');">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" style="padding: 3px 8px; font-size: 11.5px;">Remove</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small py-2">No coaches currently assigned to this squad.</div>
                        <?php endif; ?>

                        <?php if ($canManageCoaches): ?>
                            <div class="collapse <?= empty($coachesList) ? 'show' : '' ?> mt-3 pt-3 border-top" id="assignCoachCollapse">
                                <?php if (!empty($eligibleCoaches)): ?>
                                    <form action="/teams/<?= (int)$team['id'] ?>/coaches/assign" method="POST" class="row g-2 align-items-end">
                                        <div class="col-md-5">
                                            <label class="ks-form-label mb-1" for="coach_id">Assign Coach to Staff</label>
                                            <select name="coach_id" id="coach_id" class="ks-form-select" style="height: 36px; font-size: 13px;" required>
                                                <option value="">Select eligible coach...</option>
                                                <?php foreach ($eligibleCoaches as $ec): ?>
                                                    <option value="<?= (int)$ec['coach_id'] ?>">
                                                        <?= htmlspecialchars($ec['first_name'] . ' ' . $ec['last_name'] . ' (' . ($ec['specialization'] ?: 'General') . ')', ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="ks-form-label mb-1" for="coach_role">Role</label>
                                            <select name="coach_role" id="coach_role" class="ks-form-select" style="height: 36px; font-size: 13px;">
                                                <option value="head_coach">Head Coach</option>
                                                <option value="assistant_coach">Assistant Coach</option>
                                                <option value="fitness_coach">Fitness Coach</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="assign_is_primary" <?= empty($coachesList) ? 'checked' : '' ?>>
                                                <label class="form-check-label small" for="assign_is_primary" style="font-size: 11.5px;">Primary Coach</label>
                                            </div>
                                            <button type="submit" class="ks-btn ks-btn-primary w-100" style="height: 36px; font-size: 13px;">
                                                <i class="bi bi-plus-lg"></i>
                                                <span>Assign Coach</span>
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="text-muted small py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <span>No other coaches available in your organisation.</span>
                                        <a href="/coaches/create" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 12px;">
                                            <i class="bi bi-person-plus me-1"></i>Add Coach
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Athlete Roster -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-people text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Athlete Roster</h3>
                        </div>
                        <div class="ks-header-right d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark border" style="font-size: 11.5px; font-weight: 600;">
                                <?= count($currentAthletes) ?> <?= count($currentAthletes) === 1 ? 'Athlete' : 'Athletes' ?>
                            </span>
                            <?php if ($canManageRoster): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="collapse" data-bs-target="#addAthleteCollapse" aria-expanded="false" style="font-size: 12px; font-weight: 600;">
                                    <i class="bi bi-plus-lg me-1"></i>Add Athlete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <?php if (!empty($currentAthletes)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                                    <thead>
                                        <tr class="text-muted" style="font-size: 11.5px; text-transform: uppercase;">
                                            <th style="width: 55px;">Jersey</th>
                                            <th>Athlete</th>
                                            <th>Sport</th>
                                            <th>Role / Position</th>
                                            <th>DOB / Age</th>
                                            <th>Gender</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($currentAthletes as $ath): ?>
                                            <?php
                                            $displayRole = '';
                                            if (($ath['member_role'] ?? '') === 'other' && !empty($ath['position'])) {
                                                $displayRole = $ath['position'];
                                            } elseif (($ath['member_role'] ?? '') === 'vice_captain') {
                                                $displayRole = 'Vice Captain';
                                            } else {
                                                $displayRole = ucfirst($ath['member_role'] ?? 'player');
                                            }
                                            $athStatus = strtolower((string)($ath['athlete_status'] ?? 'active'));
                                            $sportDisplay = $ath['sport_name'] ?? ($team['sport_name'] ?? 'General');
                                            $dobFormatted = !empty($ath['date_of_birth']) ? date('d M Y', strtotime($ath['date_of_birth'])) : '—';
                                            $athAge = !empty($ath['date_of_birth']) ? \Carbon\Carbon::parse($ath['date_of_birth'])->age : null;
                                            ?>
                                            <tr>
                                                <td class="py-2 fw-bold text-muted">
                                                    <?= !empty($ath['jersey_number']) ? '#' . htmlspecialchars((string)$ath['jersey_number'], ENT_QUOTES, 'UTF-8') : '—' ?>
                                                </td>
                                                <td class="py-2">
                                                    <a href="/athletes/<?= (int)$ath['athlete_id'] ?>" class="fw-semibold text-decoration-none text-navy">
                                                        <?= htmlspecialchars(trim(($ath['first_name'] ?? '') . ' ' . ($ath['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                                    </a>
                                                    <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($ath['athlete_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                </td>
                                                <td class="py-2">
                                                    <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 11px; font-weight: 500;">
                                                        <?= htmlspecialchars($sportDisplay, ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>
                                                <td class="py-2">
                                                    <span class="fw-medium text-dark"><?= htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8') ?></span>
                                                </td>
                                                <td class="py-2 text-muted" style="font-size: 12px;">
                                                    <?= htmlspecialchars($dobFormatted, ENT_QUOTES, 'UTF-8') ?>
                                                    <?= ($athAge !== null) ? ' (' . $athAge . ' yrs)' : '' ?>
                                                </td>
                                                <td class="py-2 text-muted"><?= htmlspecialchars(ucfirst($ath['gender'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="py-2">
                                                    <span class="ks-badge ks-badge-<?= $athStatus === 'active' ? 'confirmed' : 'pending' ?> text-capitalize" style="padding: 2px 8px; font-size: 11px;">
                                                        <?= htmlspecialchars($athStatus, ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>
                                                <td class="py-2 text-end">
                                                    <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                                        <a href="/athletes/<?= (int)$ath['athlete_id'] ?>" class="btn btn-sm btn-outline-secondary" style="padding: 3px 8px; font-size: 11.5px;">View</a>
                                                        <?php if ($canManageRoster): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-primary edit-roster-btn" style="padding: 3px 8px; font-size: 11.5px;"
                                                            data-athlete-id="<?= (int)$ath['athlete_id'] ?>"
                                                            data-athlete-name="<?= htmlspecialchars(trim(($ath['first_name'] ?? '') . ' ' . ($ath['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                                            data-jersey="<?= htmlspecialchars($ath['jersey_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                            data-role="<?= htmlspecialchars($ath['member_role'] ?? 'player', ENT_QUOTES, 'UTF-8') ?>"
                                                            data-position="<?= htmlspecialchars($ath['position'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editRosterModal">
                                                            Edit
                                                        </button>
                                                        <form action="/teams/<?= (int)$team['id'] ?>/roster/<?= (int)$ath['athlete_id'] ?>/remove" method="POST" class="d-inline m-0" onsubmit="return confirm('Remove this athlete from the active squad?');">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="padding: 3px 8px; font-size: 11.5px;">Remove</button>
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
                            <div class="text-muted small py-2">No active athletes in this squad roster.</div>
                        <?php endif; ?>

                        <?php if ($canManageRoster): ?>
                            <div class="collapse <?= empty($currentAthletes) ? 'show' : '' ?> mt-3 pt-3 border-top" id="addAthleteCollapse">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <div class="small text-muted">
                                        Add eligible registered <strong><?= htmlspecialchars($team['sport_name'] ?? 'Sport') ?></strong> athletes to this squad.
                                    </div>
                                    <div class="small">
                                        <span class="text-muted me-1">Athlete not registered?</span>
                                        <a href="/athletes/create" class="text-decoration-none fw-semibold" style="color: var(--ks-primary);">
                                            <i class="bi bi-person-plus me-1"></i>Register Athlete
                                        </a>
                                    </div>
                                </div>
                                <?php if (!empty($eligibleAthletes)): ?>
                                    <form action="/teams/<?= (int)$team['id'] ?>/roster/add" method="POST" class="row g-2 align-items-end">
                                        <div class="col-md-4">
                                            <label class="ks-form-label mb-1" for="athlete_id">Select Registered Athlete</label>
                                            <select name="athlete_id" id="athlete_id" class="ks-form-select" style="height: 36px; font-size: 13px;" required>
                                                <option value="">Select eligible athlete...</option>
                                                <?php foreach ($eligibleAthletes as $ea): ?>
                                                    <option value="<?= (int)$ea['id'] ?>">
                                                        <?= htmlspecialchars($ea['first_name'] . ' ' . $ea['last_name'] . ' (' . $ea['athlete_code'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="ks-form-label mb-1" for="jersey_number">Jersey #</label>
                                            <input type="text" name="jersey_number" id="jersey_number" class="ks-form-control" style="height: 36px; font-size: 13px;" placeholder="e.g. 10" maxlength="10">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="ks-form-label mb-1" for="member_role">Role / Position</label>
                                            <select name="member_role" id="member_role" class="ks-form-select" style="height: 36px; font-size: 13px;">
                                                <option value="player">Player</option>
                                                <option value="captain">Captain</option>
                                                <option value="vice_captain">Vice Captain</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-none" id="custom_position_container">
                                            <label class="ks-form-label mb-1" for="position">Custom Position</label>
                                            <input type="text" name="position" id="position" class="ks-form-control" style="height: 36px; font-size: 13px;" placeholder="Enter position">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="ks-btn ks-btn-primary w-100" style="height: 36px; font-size: 13px;">
                                                <i class="bi bi-plus-lg"></i>
                                                <span>Add Athlete</span>
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="p-3 text-center text-muted small border rounded-3 bg-light">
                                        <div class="fw-semibold text-dark mb-1">No other eligible registered <?= htmlspecialchars($team['sport_name'] ?? 'sport') ?> athletes available.</div>
                                        <div class="mb-2">All registered athletes for this sport are either currently rostered or not registered yet.</div>
                                        <a href="/athletes/create" class="btn btn-sm btn-outline-primary py-1 px-3">
                                            <i class="bi bi-person-plus me-1"></i>Register Athlete
                                        </a>
                                    </div>
                                <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 4. Former Squad Members -->
                <?php if (!empty($historicalAthletes)): ?>
                    <div class="ks-content-card mb-3">
                        <div class="ks-card-header py-2 px-3">
                            <div class="ks-header-left">
                                <i class="bi bi-clock-history text-primary fs-6"></i>
                                <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Former Squad Members</h3>
                            </div>
                        </div>
                        <div class="p-3 px-4">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0" style="font-size: 13px;">
                                    <thead>
                                        <tr class="text-muted" style="font-size: 11.5px; text-transform: uppercase;">
                                            <th>Athlete</th>
                                            <th>Role</th>
                                            <th>Departed Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historicalAthletes as $ha): ?>
                                            <tr>
                                                <td class="py-2 fw-medium text-dark">
                                                    <?= htmlspecialchars($ha['first_name'] . ' ' . $ha['last_name'] . ' (' . $ha['athlete_code'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td class="py-2 text-muted"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ha['member_role'] ?? 'player')), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="py-2 text-muted"><?= !empty($ha['end_date']) ? date('d M Y', strtotime($ha['end_date'])) : '—' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Training & Matches -->
            <div class="col-12 col-lg-4">
                <!-- Upcoming Training -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-calendar2-week text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Training Sessions</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <?php if (!empty($upcomingTraining)): ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($upcomingTraining as $train): ?>
                                    <div class="p-2 px-3 border rounded-3" style="background: #F8FAFC; border-color: #E2E8F0 !important;">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <a href="/training/<?= (int)$train['id'] ?>" class="fw-semibold text-decoration-none text-navy" style="font-size: 13px;">
                                                <?= htmlspecialchars($train['title'] ?: ($train['training_type'] ?? 'Training Session'), ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                            <span class="badge bg-light text-dark border text-capitalize" style="font-size: 10.5px;">
                                                <?= htmlspecialchars($train['status'] ?? 'scheduled', ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                        <div class="text-muted mt-1" style="font-size: 11.5px;">
                                            <i class="bi bi-calendar3 me-1"></i> <?= !empty($train['training_date']) ? date('d M Y', strtotime($train['training_date'])) : '—' ?> &bull; <?= htmlspecialchars(substr($train['start_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 11.5px;">
                                            <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($train['venue_name'] ?? 'Academy Grounds', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small py-1">No scheduled training sessions for this team.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Matches -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-trophy text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Scheduled Fixtures</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <?php if (!empty($upcomingMatches)): ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($upcomingMatches as $match): ?>
                                    <div class="p-2 px-3 border rounded-3" style="background: #F8FAFC; border-color: #E2E8F0 !important;">
                                        <div class="fw-semibold text-navy" style="font-size: 13px;">
                                            <?= htmlspecialchars($match['home_team_name'] ?? 'Home', ENT_QUOTES, 'UTF-8') ?> vs <?= htmlspecialchars($match['away_team_name'] ?? 'Away', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="text-muted mt-1" style="font-size: 11.5px;">
                                            <i class="bi bi-calendar-event me-1"></i> <?= !empty($match['scheduled_date']) ? date('d M Y', strtotime($match['scheduled_date'])) : '—' ?> &bull; <?= htmlspecialchars(substr($match['scheduled_start_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 11.5px;">
                                            <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($match['venue_name'] ?? 'Main Field', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small py-1">No upcoming tournament matches scheduled.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Roster Modal -->
    <div class="modal fade" id="editRosterModal" tabindex="-1" aria-labelledby="editRosterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px; border: 1px solid #E2E8F0;">
                <form id="editRosterForm" method="POST" action="">
                    <div class="modal-header pb-2 border-bottom-0">
                        <h6 class="modal-title fw-bold text-navy" id="editRosterModalLabel">Edit Roster Details</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 12px;"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <div class="mb-3">
                            <span class="small fw-semibold text-muted">Athlete: </span>
                            <span class="small fw-bold text-dark" id="editRosterAthleteName"></span>
                        </div>
                        <div class="mb-2">
                            <label class="ks-form-label mb-1" for="edit_jersey_number">Jersey #</label>
                            <input type="text" name="jersey_number" id="edit_jersey_number" class="ks-form-control" style="height: 36px; font-size: 13px;" placeholder="e.g. 10" maxlength="10">
                        </div>
                        <div class="mb-2">
                            <label class="ks-form-label mb-1" for="edit_member_role">Role / Position</label>
                            <select name="member_role" id="edit_member_role" class="ks-form-select" style="height: 36px; font-size: 13px;">
                                <option value="player">Player</option>
                                <option value="captain">Captain</option>
                                <option value="vice_captain">Vice Captain</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-2 d-none" id="edit_custom_position_container">
                            <label class="ks-form-label mb-1" for="edit_position">Custom Position</label>
                            <input type="text" name="position" id="edit_position" class="ks-form-control" style="height: 36px; font-size: 13px;" placeholder="Enter custom position">
                        </div>
                    </div>
                    <div class="modal-footer pt-1 pb-3 border-top-0 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="ks-btn ks-btn-primary" style="height: 34px; font-size: 12.5px;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Coach Modal -->
    <div class="modal fade" id="editCoachModal" tabindex="-1" aria-labelledby="editCoachModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px; border: 1px solid #E2E8F0;">
                <form id="editCoachForm" method="POST" action="">
                    <div class="modal-header pb-2 border-bottom-0">
                        <h6 class="modal-title fw-bold text-navy" id="editCoachModalLabel">Edit Coach Role</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 12px;"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <div class="mb-3">
                            <span class="small fw-semibold text-muted">Coach: </span>
                            <span class="small fw-bold text-dark" id="editCoachName"></span>
                        </div>
                        <div class="mb-2">
                            <label class="ks-form-label mb-1" for="edit_coach_role">Role</label>
                            <select name="coach_role" id="edit_coach_role" class="ks-form-select" style="height: 36px; font-size: 13px;">
                                <option value="head_coach">Head Coach</option>
                                <option value="assistant_coach">Assistant Coach</option>
                                <option value="fitness_coach">Fitness Coach</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-check mt-3 mb-2">
                            <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="edit_coach_is_primary">
                            <label class="form-check-label small fw-semibold text-dark" for="edit_coach_is_primary">
                                Designate as Primary / Head Coach
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer pt-1 pb-3 border-top-0 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="ks-btn ks-btn-primary" style="height: 34px; font-size: 12.5px;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-roster-btn');
    const editForm = document.getElementById('editRosterForm');
    const editAthleteName = document.getElementById('editRosterAthleteName');
    const editJersey = document.getElementById('edit_jersey_number');
    const editRole = document.getElementById('edit_member_role');
    const editCustomPosContainer = document.getElementById('edit_custom_position_container');
    const editCustomPosInput = document.getElementById('edit_position');
    const teamId = <?= (int)$teamId ?>;

    const roleSelect = document.getElementById('member_role');
    const customPosContainer = document.getElementById('custom_position_container');
    const customPosInput = document.getElementById('position');

    if (roleSelect && customPosContainer) {
        roleSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                customPosContainer.classList.remove('d-none');
                customPosInput.required = true;
            } else {
                customPosContainer.classList.add('d-none');
                customPosInput.required = false;
                customPosInput.value = '';
            }
        });
    }

    if (editRole && editCustomPosContainer) {
        editRole.addEventListener('change', function() {
            if (this.value === 'other') {
                editCustomPosContainer.classList.remove('d-none');
                editCustomPosInput.required = true;
            } else {
                editCustomPosContainer.classList.add('d-none');
                editCustomPosInput.required = false;
                editCustomPosInput.value = '';
            }
        });
    }

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const athleteId = this.getAttribute('data-athlete-id');
            editAthleteName.textContent = this.getAttribute('data-athlete-name');
            editJersey.value = this.getAttribute('data-jersey');
            
            const role = this.getAttribute('data-role');
            editRole.value = role ? role : 'player';

            const position = this.getAttribute('data-position');
            editCustomPosInput.value = position || '';
            if (editRole.value === 'other') {
                editCustomPosContainer.classList.remove('d-none');
                editCustomPosInput.required = true;
            } else {
                editCustomPosContainer.classList.add('d-none');
                editCustomPosInput.required = false;
            }

            editForm.action = `/teams/${teamId}/roster/${athleteId}/edit`;
        });
    });

    const editCoachBtns = document.querySelectorAll('.edit-coach-btn');
    const editCoachForm = document.getElementById('editCoachForm');
    const editCoachName = document.getElementById('editCoachName');
    const editCoachRoleSelect = document.getElementById('edit_coach_role');
    const editCoachIsPrimary = document.getElementById('edit_coach_is_primary');

    editCoachBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const coachId = this.getAttribute('data-coach-id');
            editCoachName.textContent = this.getAttribute('data-coach-name');
            
            const role = this.getAttribute('data-role');
            editCoachRoleSelect.value = role ? role : 'head_coach';

            const isPrimary = this.getAttribute('data-is-primary') === '1';
            if (editCoachIsPrimary) {
                editCoachIsPrimary.checked = isPrimary;
            }

            editCoachForm.action = `/teams/${teamId}/coaches/${coachId}/edit`;
        });
    });
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
