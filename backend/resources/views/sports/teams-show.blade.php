<?php
$teamId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();

$currentRoleSlug = $_SESSION['auth']['role']['slug'] ?? ($_SESSION['role_slug'] ?? 'sports_admin');
$currentUser = $_SESSION['auth']['user'] ?? null;
$isAthlete = ($currentRoleSlug === 'athlete') || !empty($currentUser['athlete_id']);
$isCoach = ($currentRoleSlug === 'coach') || !empty($currentUser['coach_id']);

$accessDenied = false;
$accessDeniedMessage = 'Access denied: You are not authorized to view this team.';

$pdo = \App\Services\BaseService::getDatabaseConnection();

if ($isAthlete && $pdo) {
    $athleteId = (int)($currentUser['athlete_id'] ?? 0);
    $tmStmt = $pdo->prepare("SELECT 1 FROM team_members WHERE team_id = :tid AND athlete_id = :aid AND organization_id = :oid AND is_current = 1 LIMIT 1");
    $tmStmt->execute([':tid' => $teamId, ':aid' => $athleteId, ':oid' => $orgId]);
    if (!$tmStmt->fetchColumn()) {
        $accessDenied = true;
        $accessDeniedMessage = 'Access denied: You are not a rostered athlete in this team squad.';
    }
} elseif ($isCoach && $pdo) {
    $coachId = (int)($currentUser['coach_id'] ?? 0);
    if (!$coachId && !empty($currentUser['id'])) {
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
if ($team && !$isAthlete && !$isCoach && $pdo) {
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

// Eligible coaches via service (no raw database queries in Blade)
$eligibleCoaches = ($team && !$isAthlete && !$isCoach) ? $teamService->getEligibleCoaches($orgId, $teamId) : [];

// RBAC check for coach management visibility
$permissionService = new \App\Services\Rbac\PermissionService();
$currentUserId = current_user_id() ?? 0;
$userPermissions = $currentUserId > 0 ? $permissionService->getUserPermissions($currentUserId, $orgId) : [];
$canManageCoaches = (!$isAthlete && !$isCoach) && (in_array('team.coaches.manage', $userPermissions, true) || in_array('team.manage', $userPermissions, true));

$pageTitle = $team ? htmlspecialchars($team['name'] . ' — Team Details') : 'Team Details';
$activePage = 'teams';

ob_start();
?>

<div class="ks-content">
    <?php if ($accessDenied): ?>
        <div class="card p-5 text-center my-4" style="border: 1px solid #FECACA; background: #FEF2F2; border-radius: var(--ks-radius-card);">
            <div class="mb-3"><i class="bi bi-shield-lock-fill fs-1 text-danger"></i></div>
            <h4 class="fw-bold text-danger">403 — Access Forbidden</h4>
            <p class="text-muted small"><?= htmlspecialchars($accessDeniedMessage, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="mt-3">
                <a href="<?= $isAthlete ? '/dashboard' : '/teams' ?>" class="btn btn-outline-danger" style="border-radius: var(--ks-radius-button); font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Return to <?= $isAthlete ? 'Dashboard' : 'My Coached Teams' ?>
                </a>
            </div>
        </div>
    <?php elseif (!$team): ?>
        <div class="card p-5 text-center" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <div class="mb-3"><i class="bi bi-shield-x fs-1 text-muted"></i></div>
            <h4 class="fw-bold" style="color: var(--ks-navy);">Team Not Found</h4>
            <p class="text-muted small">The requested team squad does not exist or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/teams" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Teams
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="/teams" class="text-decoration-none text-muted small fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Teams
            </a>
        </div>

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success mb-4 py-2 px-3 small d-flex align-items-center gap-2" style="border-radius: var(--ks-radius-button);">
                <i class="bi bi-check-circle-fill"></i>
                <div><?= htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger mb-4 py-2 px-3 small d-flex align-items-center gap-2" style="border-radius: var(--ks-radius-button);">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: #E0F2FE; color: #0284C7; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700;">
                    <?= htmlspecialchars(strtoupper(substr($team['name'] ?? 'T', 0, 2)), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="h4 fw-bold mb-0" style="color: var(--ks-navy);"><?= htmlspecialchars($team['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="badge <?= ($team['status'] ?? 'active') === 'active' ? 'badge-success' : 'badge-secondary' ?>" style="border-radius: 12px; font-size: 11px; padding: 4px 10px; text-transform: uppercase;">
                            <?= htmlspecialchars($team['status'] ?? 'active', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="text-muted small mt-1">
                        Code: <strong style="color: var(--ks-text);"><?= htmlspecialchars($team['team_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull;
                        Sport: <strong style="color: var(--ks-text);"><?= htmlspecialchars($team['sport_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></strong> &bull;
                        Age Group: <strong style="color: var(--ks-text);"><?= htmlspecialchars($team['age_group'] ?: 'Open', ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
            </div>

            <?php if (!$isCoach && !$isAthlete): ?>
            <div class="d-flex gap-2">
                <a href="/teams/<?= (int)$team['id'] ?>/edit" class="btn btn-primary d-inline-flex align-items-center gap-2" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 8px 18px;">
                    <i class="bi bi-pencil-square"></i> Edit Team
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="row g-3">
            <!-- Left Column: Rosters & Coaches -->
            <div class="col-lg-8">
                <!-- Coaching Staff -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-person-badge me-2" style="color: var(--ks-blue);"></i> Assigned Coaching Staff
                    </h5>
                    <?php if (!empty($team['coaches']) && count($team['coaches']) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr class="text-muted small">
                                        <th>Coach</th>
                                        <th>Role</th>
                                        <th>Specialization</th>
                                        <th>Contact</th>
                                        <?php if ($canManageCoaches): ?>
                                            <th class="text-end">Action</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($team['coaches'] as $cch): ?>
                                        <tr>
                                            <td class="fw-semibold">
                                                <a href="/coaches/<?= (int)$cch['coach_profile_id'] ?>" class="text-decoration-none" style="color: var(--ks-blue);">
                                                    <?= htmlspecialchars($cch['first_name'] . ' ' . $cch['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="text-dark small fw-medium"><?= htmlspecialchars(format_coach_role($cch['coach_role'] ?? 'head_coach'), ENT_QUOTES, 'UTF-8') ?></span>
                                            </td>
                                            <td class="text-muted small"><?= htmlspecialchars($cch['specialization'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars($cch['phone'] ?: ($cch['email'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <?php if ($canManageCoaches): ?>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 me-1 edit-coach-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editCoachModal"
                                                        data-coach-id="<?= (int)$cch['coach_id'] ?>"
                                                        data-coach-name="<?= htmlspecialchars($cch['first_name'] . ' ' . $cch['last_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-role="<?= htmlspecialchars($cch['coach_role'] ?? 'head_coach', ENT_QUOTES, 'UTF-8') ?>">
                                                        Edit
                                                    </button>
                                                    <form action="/teams/<?= (int)$team['id'] ?>/coaches/<?= (int)$cch['coach_id'] ?>/remove" method="POST" class="d-inline m-0" onsubmit="return confirm('Remove this coach from the active coaching staff?');">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">Remove</button>
                                                    </form>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No coaches currently assigned to this squad.</p>
                    <?php endif; ?>

                    <!-- Assign Coach to Staff -->
                    <?php if ($canManageCoaches && !empty($eligibleCoaches)): ?>
                        <div class="mt-3 pt-3 border-top">
                            <form action="/teams/<?= (int)$team['id'] ?>/coaches/assign" method="POST" class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold text-muted mb-1" for="coach_id">Assign Coach to Staff</label>
                                    <select name="coach_id" id="coach_id" class="form-select form-select-sm" required>
                                        <option value="">-- Select Eligible Coach --</option>
                                        <?php foreach ($eligibleCoaches as $ec): ?>
                                            <option value="<?= (int)$ec['coach_id'] ?>">
                                                <?= htmlspecialchars($ec['first_name'] . ' ' . $ec['last_name'] . ' (' . ($ec['specialization'] ?: 'General') . ')', ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold text-muted mb-1" for="coach_role">Role</label>
                                    <select name="coach_role" id="coach_role" class="form-select form-select-sm">
                                        <option value="head_coach">Head Coach</option>
                                        <option value="assistant_coach">Assistant Coach</option>
                                        <option value="fitness_coach">Fitness Coach</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex flex-column justify-content-end pb-1">
                                    <button type="submit" class="btn btn-sm btn-primary w-100" style="background: var(--ks-blue); border-color: var(--ks-blue);">
                                        <i class="bi bi-plus-lg me-1"></i> Assign
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Current Athletes Roster -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h5 class="fw-bold mb-0" style="color: var(--ks-navy); font-size: 15px;">
                            <i class="bi bi-people me-2" style="color: var(--ks-blue);"></i> Current Active Roster (<?= count($team['current_athletes'] ?? []) ?>)
                        </h5>
                    </div>
                    <?php if (!empty($team['current_athletes']) && count($team['current_athletes']) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr class="text-muted small">
                                        <th style="width: 60px;">Jersey</th>
                                        <th>Athlete</th>
                                        <th>Position / Role</th>
                                        <th>Gender</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($team['current_athletes'] as $ath): ?>
                                        <tr>
                                            <td class="fw-bold text-muted"><?= $ath['jersey_number'] ? '#' . htmlspecialchars($ath['jersey_number'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                            <td class="fw-semibold">
                                                <a href="/athletes/<?= (int)$ath['athlete_id'] ?>" class="text-decoration-none text-dark">
                                                    <?= htmlspecialchars($ath['first_name'] . ' ' . $ath['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                                <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($ath['athlete_code'], ENT_QUOTES, 'UTF-8') ?></div>
                                            </td>
                                            <?php
                                                $displayRole = '';
                                                if (($ath['member_role'] ?? '') === 'other' && !empty($ath['position'])) {
                                                    $displayRole = $ath['position'];
                                                } elseif (($ath['member_role'] ?? '') === 'vice_captain') {
                                                    $displayRole = 'Vice Captain';
                                                } else {
                                                    $displayRole = ucfirst($ath['member_role'] ?? 'player');
                                                }
                                            ?>
                                            <td class="text-muted small"><?= htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars(ucfirst($ath['gender'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <span class="badge <?= ($ath['athlete_status'] ?? 'active') === 'active' ? 'badge-success' : 'badge-secondary' ?>" style="font-size: 10px;">
                                                    <?= htmlspecialchars($ath['athlete_status'] ?? 'active', ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                                    <a href="/athletes/<?= (int)$ath['athlete_id'] ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 11px;">View</a>
                                                    
                                                    <?php if ($canManageRoster ?? true): // fallback to true for UI since server validates ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 edit-roster-btn" style="font-size: 11px;"
                                                            data-athlete-id="<?= (int)$ath['athlete_id'] ?>"
                                                            data-athlete-name="<?= htmlspecialchars($ath['first_name'] . ' ' . $ath['last_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-jersey="<?= htmlspecialchars($ath['jersey_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                            data-role="<?= htmlspecialchars($ath['member_role'] ?? 'player', ENT_QUOTES, 'UTF-8') ?>"
                                                            data-position="<?= htmlspecialchars($ath['position'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editRosterModal">
                                                        Edit
                                                    </button>
                                                    <?php endif; ?>

                                                    <form action="/teams/<?= (int)$team['id'] ?>/roster/<?= (int)$ath['athlete_id'] ?>/remove" method="POST" class="d-inline m-0" onsubmit="return confirm('Remove this athlete from the active squad?');">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 11px;">Remove</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No active athletes in this squad roster.</p>
                    <?php endif; ?>

                    <!-- Minimal Roster Extension: Add Athlete to Squad -->
                    <?php if (!empty($eligibleAthletes)): ?>
                        <div class="mt-3 pt-3 border-top">
                            <form action="/teams/<?= (int)$team['id'] ?>/roster/add" method="POST" class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold text-muted mb-1" for="athlete_id">Add Athlete to Squad</label>
                                    <select name="athlete_id" id="athlete_id" class="form-select form-select-sm" required>
                                        <option value="">-- Select Eligible Athlete --</option>
                                        <?php foreach ($eligibleAthletes as $ea): ?>
                                            <option value="<?= (int)$ea['id'] ?>">
                                                <?= htmlspecialchars($ea['first_name'] . ' ' . $ea['last_name'] . ' (' . $ea['athlete_code'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold text-muted mb-1" for="jersey_number">Jersey #</label>
                                    <input type="text" name="jersey_number" id="jersey_number" class="form-control form-control-sm" placeholder="e.g. 10" maxlength="10">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold text-muted mb-1" for="member_role">Role / Pos</label>
                                    <select name="member_role" id="member_role" class="form-select form-select-sm">
                                        <option value="player">Player</option>
                                        <option value="captain">Captain</option>
                                        <option value="vice_captain">Vice Captain</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-none" id="custom_position_container">
                                    <label class="form-label small fw-semibold text-muted mb-1" for="position">Custom Position</label>
                                    <input type="text" name="position" id="position" class="form-control form-control-sm" placeholder="Enter custom position">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-primary w-100" style="background: var(--ks-blue); border-color: var(--ks-blue);">
                                        <i class="bi bi-plus-lg me-1"></i> Add
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Historical Squad Members -->
                <?php if (!empty($team['historical_athletes']) && count($team['historical_athletes']) > 0): ?>
                    <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                        <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                            <i class="bi bi-clock-history me-2" style="color: var(--ks-blue);"></i> Former Squad Members
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr class="text-muted small">
                                        <th>Athlete</th>
                                        <th>Role</th>
                                        <th>Departed Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($team['historical_athletes'] as $ha): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ha['first_name'] . ' ' . $ha['last_name'] . ' (' . $ha['athlete_code'] . ')', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars($ha['member_role'] ?? 'player', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-muted small"><?= !empty($ha['end_date']) ? date('M d, Y', strtotime($ha['end_date'])) : '—' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Training & Matches -->
            <div class="col-lg-4">
                <!-- Upcoming Training -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-stopwatch me-2" style="color: var(--ks-blue);"></i> Upcoming Training
                    </h5>
                    <?php if (!empty($team['upcoming_training']) && count($team['upcoming_training']) > 0): ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($team['upcoming_training'] as $train): ?>
                                <div class="p-2 border rounded" style="background: var(--ks-page-bg);">
                                    <div class="fw-semibold small text-dark"><?= htmlspecialchars($train['title'] ?: ($train['training_type'] ?? 'Training Session'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        <i class="bi bi-calendar3 me-1"></i> <?= date('M d, Y', strtotime($train['training_date'])) ?> &bull; <?= htmlspecialchars(substr($train['start_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($train['venue_name'] ?? 'Academy Grounds', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No scheduled training sessions for this team.</p>
                    <?php endif; ?>
                </div>

                <!-- Upcoming Matches -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-trophy me-2" style="color: var(--ks-blue);"></i> Upcoming Matches
                    </h5>
                    <?php if (!empty($team['upcoming_matches']) && count($team['upcoming_matches']) > 0): ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($team['upcoming_matches'] as $match): ?>
                                <div class="p-2 border rounded" style="background: var(--ks-page-bg);">
                                    <div class="fw-semibold small text-dark"><?= htmlspecialchars($match['home_team_name'] ?? 'Home', ENT_QUOTES, 'UTF-8') ?> vs <?= htmlspecialchars($match['away_team_name'] ?? 'Away', ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        <i class="bi bi-calendar-event me-1"></i> <?= !empty($match['scheduled_date']) ? date('M d, Y', strtotime($match['scheduled_date'])) : '—' ?> &bull; <?= htmlspecialchars(substr($match['scheduled_start_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($match['venue_name'] ?? 'Main Field', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No upcoming tournament matches scheduled.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <!-- Edit Roster Modal -->
    <div class="modal fade" id="editRosterModal" tabindex="-1" aria-labelledby="editRosterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content" style="border-radius: var(--ks-radius-card);">
                <form id="editRosterForm" method="POST" action="">
                    <div class="modal-header pb-2 border-bottom-0">
                        <h6 class="modal-title fw-bold" id="editRosterModalLabel" style="color: var(--ks-navy);">Edit Roster Details</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 12px;"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <div class="mb-3">
                            <span class="small fw-semibold text-muted">Athlete: </span>
                            <span class="small fw-bold text-dark" id="editRosterAthleteName"></span>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-muted mb-1" for="edit_jersey_number">Jersey #</label>
                            <input type="text" name="jersey_number" id="edit_jersey_number" class="form-control form-control-sm" placeholder="e.g. 10" maxlength="10">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-muted mb-1" for="edit_member_role">Role / Pos</label>
                            <select name="member_role" id="edit_member_role" class="form-select form-select-sm">
                                <option value="player">Player</option>
                                <option value="captain">Captain</option>
                                <option value="vice_captain">Vice Captain</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-2 d-none" id="edit_custom_position_container">
                            <label class="form-label small fw-semibold text-muted mb-1" for="edit_position">Custom Position</label>
                            <input type="text" name="position" id="edit_position" class="form-control form-control-sm" placeholder="Enter custom position">
                        </div>
                    </div>
                    <div class="modal-footer pt-1 pb-2 border-top-0 d-flex justify-content-end gap-1">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary" style="background: var(--ks-blue); border-color: var(--ks-blue);">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Coach Modal -->
    <div class="modal fade" id="editCoachModal" tabindex="-1" aria-labelledby="editCoachModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content" style="border-radius: var(--ks-radius-card);">
                <form id="editCoachForm" method="POST" action="">
                    <div class="modal-header pb-2 border-bottom-0">
                        <h6 class="modal-title fw-bold" id="editCoachModalLabel" style="color: var(--ks-navy);">Edit Coach Role</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 12px;"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <div class="mb-3">
                            <span class="small fw-semibold text-muted">Coach: </span>
                            <span class="small fw-bold text-dark" id="editCoachName"></span>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-muted mb-1" for="edit_coach_role">Role</label>
                            <select name="coach_role" id="edit_coach_role" class="form-select form-select-sm">
                                <option value="head_coach">Head Coach</option>
                                <option value="assistant_coach">Assistant Coach</option>
                                <option value="fitness_coach">Fitness Coach</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer pt-1 pb-2 border-top-0 d-flex justify-content-end gap-1">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary" style="background: var(--ks-blue); border-color: var(--ks-blue);">Save Changes</button>
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
            if(role) {
                editRole.value = role;
            } else {
                editRole.value = 'player';
            }

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

    editCoachBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const coachId = this.getAttribute('data-coach-id');
            editCoachName.textContent = this.getAttribute('data-coach-name');
            
            const role = this.getAttribute('data-role');
            if(role) {
                editCoachRoleSelect.value = role;
            } else {
                editCoachRoleSelect.value = 'head_coach';
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
