<?php
$sessionId = (int)($id ?? ($_GET['id'] ?? 0));
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
$userPermissions = $permissionService->getUserPermissions($userId, $orgId);

$canView = $isCoach || $isAthlete
        || $permissionService->hasPermission($userPayload, 'training.view', $orgId)
        || $permissionService->hasPermission($userPayload, 'training.manage', $orgId)
        || in_array('training.view', $userPermissions, true)
        || in_array('training.manage', $userPermissions, true);

$canEdit = !$isAthlete && (
         $permissionService->hasPermission($userPayload, 'training.update', $orgId)
      || $permissionService->hasPermission($userPayload, 'training.manage', $orgId)
      || in_array('training.update', $userPermissions, true)
      || in_array('training.manage', $userPermissions, true)
);

$canRecord = !$isAthlete && (
           $permissionService->hasPermission($userPayload, 'attendance.manage', $orgId)
        || $permissionService->hasPermission($userPayload, 'training.manage', $orgId)
        || $permissionService->hasPermission($userPayload, 'training.update', $orgId)
        || in_array('attendance.manage', $userPermissions, true)
        || in_array('training.manage', $userPermissions, true)
        || in_array('training.update', $userPermissions, true)
);

$trainService = new \App\Services\Training\TrainingService();
$session = $canView ? $trainService->getSession($orgId, $sessionId) : null;

// Athlete roster filters and pagination (Sections 22, 23, 24)
$athPage = max(1, (int)($_GET['ath_page'] ?? 1));
$athLimit = 10; // Exact project requirement: 10 athletes per page
$athSearch = trim($_GET['ath_search'] ?? '');
$athStatus = trim($_GET['ath_status'] ?? '');

$athleteResult = $session ? $trainService->getSessionAthletes(
    $orgId,
    $sessionId,
    $athPage,
    $athLimit,
    $athSearch !== '' ? $athSearch : null,
    $athStatus !== '' ? $athStatus : null
) : [
    'data' => [], 'total' => 0, 'page' => 1, 'limit' => 10, 'total_pages' => 1, 'from' => 0, 'to' => 0, 'summary' => [], 'all_roster' => []
];

$roster = $athleteResult['data'] ?? [];
$allRoster = $athleteResult['all_roster'] ?? [];
$athTotal = (int)($athleteResult['total'] ?? 0);
$athTotalPages = max(1, (int)($athleteResult['total_pages'] ?? 1));
$athFrom = (int)($athleteResult['from'] ?? 0);
$athTo = (int)($athleteResult['to'] ?? 0);
$summary = $athleteResult['summary'] ?? ($session['attendance_summary'] ?? []);

$hasActiveAthFilters = ($athSearch !== '' || $athStatus !== '');

$buildAthPageUrl = function (int $targetPage) use ($sessionId, $athSearch, $athStatus): string {
    $params = [];
    if ($athSearch !== '') $params['ath_search'] = $athSearch;
    if ($athStatus !== '') $params['ath_status'] = $athStatus;
    $params['ath_page'] = max(1, $targetPage);
    return '/training/' . $sessionId . '?' . http_build_query($params) . '#sessionAthletesSection';
};

$pageTitle = $session
    ? htmlspecialchars(($session['title'] ?: ($session['training_type'] ?? 'Training Session')) . ' — Session Details', ENT_QUOTES, 'UTF-8')
    : 'Training Session — KhelSutra';
$title = $pageTitle;
$activePage = 'training';

ob_start();
?>

<div class="ks-page">
    <?php if (!$canView): ?>
        <div class="ks-content-card p-5 text-center my-4" style="border: 1px solid #FECACA; background: #FEF2F2; border-radius: 12px;">
            <div class="mb-3"><i class="bi bi-shield-lock-fill fs-1 text-danger"></i></div>
            <h4 class="fw-bold text-danger">403 — Access Forbidden</h4>
            <p class="text-muted small">You do not have permission to view training session details.</p>
            <div class="mt-3">
                <a href="/training" class="btn btn-outline-danger" style="border-radius: 8px; font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Training
                </a>
            </div>
        </div>
    <?php elseif (!$session): ?>
        <div class="ks-content-card p-5 text-center my-4">
            <div class="mb-3"><i class="bi bi-stopwatch fs-1 text-muted"></i></div>
            <h4 class="fw-bold text-navy">Training Session Not Found</h4>
            <p class="text-muted small mb-3">The requested training session does not exist or does not belong to your organisation.</p>
            <a href="/training" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Training
            </a>
        </div>
    <?php else: ?>
        <?php
            $sessTitle = trim((string)($session['title'] ?: ($session['training_type'] ?? 'Training Session')));
            $sessRef = trim((string)($session['training_reference'] ?? ''));
            $sessStatus = strtolower(trim((string)($session['status'] ?? 'scheduled')));
            $statusBadgeClass = match ($sessStatus) {
                'completed' => 'ks-badge-completed',
                'in_progress' => 'ks-badge-pending',
                'cancelled' => 'ks-badge-rejected',
                default => 'ks-badge-scheduled',
            };
            $statusLabel = ucwords(str_replace('_', ' ', $sessStatus));
            $sessionDateFormatted = !empty($session['training_date']) ? date('d F Y', strtotime($session['training_date'])) : '—';
            $sessionTimeFormatted = htmlspecialchars(substr((string)($session['start_time'] ?? ''), 0, 5) . ' – ' . substr((string)($session['end_time'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8');
            $hasAttendanceLogged = (($summary['marked'] ?? 0) > 0);
        ?>

        <!-- Back Link (Section 15 & 28) -->
        <div class="mb-2">
            <a href="/training" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1" style="font-size: 13px;">
                <i class="bi bi-arrow-left"></i> Back to Training
            </a>
        </div>

        <!-- Session Detail Header (Section 15 & 28) -->
        <div class="ks-page-header mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h1 class="ks-page-title mb-0"><?= htmlspecialchars($sessTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                    <span class="ks-badge <?= $statusBadgeClass ?> text-capitalize"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="d-flex align-items-center gap-3 flex-wrap text-muted small mt-2">
                    <span>
                        <i class="bi bi-calendar-event me-1"></i>
                        <strong class="text-dark"><?= $sessionDateFormatted ?></strong>
                    </span>
                    <span>&bull;</span>
                    <span>
                        <i class="bi bi-clock me-1"></i>
                        <strong class="text-dark"><?= $sessionTimeFormatted ?></strong>
                    </span>
                </div>
            </div>

            <?php if ($canEdit): ?>
                <div class="ks-header-actions">
                    <a href="/training/<?= (int)$session['id'] ?>/edit" class="ks-btn ks-btn-primary" id="btnEditTrainingSession">
                        <i class="bi bi-pencil"></i>
                        <span>Edit Session</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section 16 & 17: SESSION DETAILS -->
        <div class="ks-content-card mb-4">
            <div class="ks-card-header py-2 px-3">
                <div class="ks-header-left">
                    <i class="bi bi-info-circle-fill text-primary fs-6"></i>
                    <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">SESSION DETAILS</h3>
                </div>
            </div>
            <div class="p-3 px-4">
                <div class="row g-3">
                    <div class="col-sm-4 col-md-3">
                        <div class="text-muted small">Session Name</div>
                        <div class="fw-semibold text-dark mt-1">
                            <?= htmlspecialchars($sessTitle, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <div class="text-muted small">Session ID</div>
                        <div class="fw-semibold text-dark mt-1 font-monospace">
                            <?= htmlspecialchars($sessRef ?: ('#' . $session['id']), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <div class="text-muted small">Session Type</div>
                        <div class="fw-semibold text-dark mt-1">
                            <?= htmlspecialchars($session['training_type'] ?: 'Tactical Drill', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <div class="text-muted small">Date</div>
                        <div class="fw-semibold text-dark mt-1">
                            <?= $sessionDateFormatted ?>
                        </div>
                    </div>

                    <div class="col-sm-4 col-md-3">
                        <div class="text-muted small">Time</div>
                        <div class="fw-semibold text-dark mt-1 font-monospace">
                            <?= $sessionTimeFormatted ?>
                        </div>
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <div class="text-muted small">Coach</div>
                        <div class="fw-semibold text-dark mt-1">
                            <?= htmlspecialchars($session['coach_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <div class="text-muted small">Team</div>
                        <div class="fw-semibold text-dark mt-1">
                            <?php if (!empty($session['team_id']) && !empty($session['team_name'])): ?>
                                <a href="/teams/<?= (int)$session['team_id'] ?>" class="text-decoration-none hover-primary" style="color: var(--ks-primary);">
                                    <?= htmlspecialchars($session['team_name'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($session['team_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <div class="text-muted small">Sport</div>
                        <div class="fw-semibold text-dark mt-1">
                            <?= htmlspecialchars($session['sport_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <div class="col-sm-4 col-md-3">
                        <div class="text-muted small">Venue</div>
                        <div class="fw-semibold text-dark mt-1">
                            <?= htmlspecialchars($session['venue_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($session['facility_name'])): ?>
                                <span class="text-muted fw-normal">&bull; <?= htmlspecialchars($session['facility_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <div class="text-muted small">Status</div>
                        <div class="mt-1">
                            <span class="ks-badge <?= $statusBadgeClass ?> text-capitalize"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div class="col-sm-8 col-md-6">
                        <div class="text-muted small">Description / Objectives</div>
                        <div class="text-dark mt-1" style="font-size: 13.5px; line-height: 1.4;">
                            <?= !empty($session['objectives']) ? nl2br(htmlspecialchars($session['objectives'], ENT_QUOTES, 'UTF-8')) : (!empty($session['notes']) ? nl2br(htmlspecialchars($session['notes'], ENT_QUOTES, 'UTF-8')) : '<span class="text-muted">—</span>') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 18, 19, 20, 22, 23, 24, 25, 28: SESSION ATHLETES -->
        <div class="ks-table-card mb-4" id="sessionAthletesSection">
            <!-- Header with Search & Status Filter -->
            <div class="ks-table-header flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill" style="color: var(--ks-primary); font-size: 18px;"></i>
                    <span class="ks-card-title mb-0">SESSION ATHLETES (<?= (int)$athTotal ?>)</span>
                </div>

                <div class="d-flex align-items-center flex-wrap gap-2">
                    <form action="/training/<?= $sessionId ?>" method="GET" class="d-flex align-items-center gap-2 m-0">
                        <div class="position-relative" style="width: 200px;">
                            <i class="bi bi-search position-absolute" style="left: 10px; top: 10px; color: var(--ks-text-muted); font-size: 12px;"></i>
                            <input
                                type="text"
                                name="ath_search"
                                value="<?= htmlspecialchars($athSearch, ENT_QUOTES, 'UTF-8') ?>"
                                class="ks-form-control"
                                style="padding-left: 30px; height: 36px; font-size: 12.5px;"
                                placeholder="Search athlete..."
                            >
                        </div>

                        <select name="ath_status" class="ks-form-select" style="width: 140px; height: 36px; font-size: 12.5px;" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="present" <?= $athStatus === 'present' ? 'selected' : '' ?>>Present</option>
                            <option value="absent" <?= $athStatus === 'absent' ? 'selected' : '' ?>>Absent</option>
                            <option value="late" <?= $athStatus === 'late' ? 'selected' : '' ?>>Late</option>
                            <option value="excused" <?= $athStatus === 'excused' ? 'selected' : '' ?>>Excused</option>
                            <option value="not_marked" <?= $athStatus === 'not_marked' ? 'selected' : '' ?>>Not Marked</option>
                        </select>

                        <button type="submit" class="btn btn-sm btn-outline-primary" style="height: 36px; padding: 0 10px; font-size: 12.5px;">Filter</button>
                        <?php if ($hasActiveAthFilters): ?>
                            <a href="/training/<?= $sessionId ?>" class="btn btn-sm btn-outline-secondary" style="height: 36px; display: flex; align-items: center; padding: 0 10px; font-size: 12.5px;">Clear</a>
                        <?php endif; ?>
                    </form>

                    <?php if ($canRecord && !empty($allRoster)): ?>
                        <button type="button" class="ks-btn ks-btn-primary ms-1" id="btnToggleAttendanceEdit" onclick="toggleAttendanceEdit()" style="padding: 6px 14px; font-size: 13px;">
                            <i class="bi bi-pencil-square"></i>
                            <span id="btnToggleAttendanceEditText"><?= $hasAttendanceLogged ? 'Edit Attendance' : 'Mark Training Attendance' ?></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($roster) && !$hasActiveAthFilters): ?>
                <!-- Empty State (Section 33) -->
                <div class="py-5 text-center text-muted">
                    <i class="bi bi-people d-block fs-1 mb-2" style="color: var(--ks-text-muted);"></i>
                    <div class="fw-semibold text-navy fs-6">No athletes currently assigned to this session's team</div>
                    <div class="small mt-1 mb-3">
                        Roster will be populated from <?= htmlspecialchars($session['team_name'] ?? 'the assigned team', ENT_QUOTES, 'UTF-8') ?>.
                    </div>
                    <?php if (!empty($session['team_id'])): ?>
                    <a href="/teams/<?= (int)$session['team_id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-person-plus me-1"></i> Manage Team Roster
                    </a>
                    <?php endif; ?>
                </div>
            <?php elseif (empty($roster) && $hasActiveAthFilters): ?>
                <div class="py-5 text-center text-muted">
                    <div class="fw-semibold text-navy fs-6">No athletes match the current search or status filter</div>
                    <div class="small mt-1 mb-3">Try clearing the athlete filter.</div>
                    <a href="/training/<?= $sessionId ?>" class="btn btn-outline-secondary btn-sm">Clear Filter</a>
                </div>
            <?php else: ?>
                <!-- View Mode Table (Sections 18, 19, 20) -->
                <div id="attendanceViewMode" class="table-responsive">
                    <table class="ks-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 25%;">Athlete Name</th>
                                <th style="width: 18%;">Registration ID</th>
                                <th style="width: 15%;">Attendance Status</th>
                                <th style="width: 12%;">Check In</th>
                                <th style="width: 12%;">Check Out</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roster as $idx => $row): ?>
                                <?php
                                    $athId = (int)$row['athlete_id'];
                                    $athName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                    $athCode = $row['athlete_code'] ?? '';
                                    $attStatus = strtolower(trim((string)($row['attendance_status'] ?? 'not_marked')));
                                    $badgeClass = match ($attStatus) {
                                        'present' => 'ks-badge-confirmed',
                                        'absent' => 'ks-badge-rejected',
                                        'late' => 'ks-badge-pending',
                                        'excused' => 'ks-badge-scheduled',
                                        default => '',
                                    };
                                    $statusLabel = $attStatus === 'not_marked' ? 'Not Marked' : ucfirst($attStatus);
                                    $rowNumber = $athFrom + $idx;
                                ?>
                                <tr>
                                    <td class="text-muted fw-medium"><?= $rowNumber ?></td>
                                    <td>
                                        <a href="/athletes/<?= $athId ?>" class="fw-semibold text-navy text-decoration-none hover-primary">
                                            <?= htmlspecialchars($athName) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="small font-monospace text-dark fw-medium">
                                            <?= htmlspecialchars($athCode ?: '—') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($badgeClass !== ''): ?>
                                            <span class="ks-badge <?= $badgeClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-light text-muted border" style="font-size: 11px;">
                                                Not Marked
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="small font-monospace text-dark">
                                            <?= htmlspecialchars($row['check_in_time'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small font-monospace text-dark">
                                            <?= htmlspecialchars($row['check_out_time'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small text-muted">
                                            <?= !empty($row['remarks']) ? htmlspecialchars($row['remarks']) : '—' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Athlete Server-Side Pagination Footer (Section 22 & 28) -->
                    <?php if ($athTotal > 0): ?>
                        <div class="p-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="small text-muted">
                                Showing <strong><?= (int)$athFrom ?>–<?= (int)$athTo ?></strong> of <strong><?= (int)$athTotal ?></strong> <?= $athTotal === 1 ? 'athlete' : 'athletes' ?>
                            </div>
                            <?php if ($athTotalPages > 1): ?>
                            <nav aria-label="Session athletes pagination">
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?= $athPage <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= $athPage > 1 ? htmlspecialchars($buildAthPageUrl($athPage - 1), ENT_QUOTES, 'UTF-8') : '#' ?>" tabindex="<?= $athPage <= 1 ? '-1' : '0' ?>">Previous</a>
                                    </li>
                                    <?php for ($p = 1; $p <= $athTotalPages; $p++): ?>
                                        <li class="page-item <?= $p === $athPage ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= htmlspecialchars($buildAthPageUrl($p), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $athPage >= $athTotalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= $athPage < $athTotalPages ? htmlspecialchars($buildAthPageUrl($athPage + 1), ENT_QUOTES, 'UTF-8') : '#' ?>" tabindex="<?= $athPage >= $athTotalPages ? '-1' : '0' ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Edit Mode Form (Sections 25 & 28) -->
                <?php if ($canRecord): ?>
                    <div id="attendanceEditMode" style="display: none;">
                        <form action="/training/<?= (int)$session['id'] ?>/attendance" method="POST" id="training-attendance-form">
                            <div class="p-2 px-3 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="small text-muted">
                                    Select attendance status for each athlete in this session on <strong><?= $sessionDateFormatted ?></strong>.
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-success" id="btn-mark-all-present" style="font-size: 12px; font-weight: 500;">
                                        <i class="bi bi-check-all me-1"></i> Mark All Present
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAttendanceEdit(false)" style="font-size: 12px;">
                                        Cancel
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="ks-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th style="width: 25%;">Athlete</th>
                                            <th style="width: 15%;">Registration ID</th>
                                            <th style="width: 32%;">Record Status</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allRoster as $idx => $row): ?>
                                            <?php
                                                $athId = (int)$row['athlete_id'];
                                                $athName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                                $athCode = $row['athlete_code'] ?? '';
                                                $attStatus = strtolower(trim((string)($row['attendance_status'] ?? 'not_marked')));
                                                $checkedVal = $attStatus === 'not_marked' ? 'present' : $attStatus;
                                            ?>
                                            <tr>
                                                <td class="text-muted fw-medium"><?= $idx + 1 ?></td>
                                                <td>
                                                    <span class="fw-semibold text-navy"><?= htmlspecialchars($athName) ?></span>
                                                </td>
                                                <td>
                                                    <span class="small font-monospace text-dark"><?= htmlspecialchars($athCode ?: '—') ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm w-100" role="group" aria-label="Attendance for <?= htmlspecialchars($athName) ?>">
                                                        <input type="radio" class="btn-check js-att-radio" name="attendance[<?= $athId ?>][status]" id="att_<?= $athId ?>_present" value="present" <?= $checkedVal === 'present' ? 'checked' : '' ?>>
                                                        <label class="btn btn-outline-success py-1 px-2" style="font-size: 12px;" for="att_<?= $athId ?>_present">Present</label>

                                                        <input type="radio" class="btn-check js-att-radio" name="attendance[<?= $athId ?>][status]" id="att_<?= $athId ?>_absent" value="absent" <?= $checkedVal === 'absent' ? 'checked' : '' ?>>
                                                        <label class="btn btn-outline-danger py-1 px-2" style="font-size: 12px;" for="att_<?= $athId ?>_absent">Absent</label>

                                                        <input type="radio" class="btn-check js-att-radio" name="attendance[<?= $athId ?>][status]" id="att_<?= $athId ?>_late" value="late" <?= $checkedVal === 'late' ? 'checked' : '' ?>>
                                                        <label class="btn btn-outline-warning py-1 px-2" style="font-size: 12px;" for="att_<?= $athId ?>_late">Late</label>

                                                        <input type="radio" class="btn-check js-att-radio" name="attendance[<?= $athId ?>][status]" id="att_<?= $athId ?>_excused" value="excused" <?= $checkedVal === 'excused' ? 'checked' : '' ?>>
                                                        <label class="btn btn-outline-secondary py-1 px-2" style="font-size: 12px;" for="att_<?= $athId ?>_excused">Excused</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        name="attendance[<?= $athId ?>][remarks]"
                                                        class="ks-form-control"
                                                        placeholder="Optional remarks (e.g. sick, traffic)..."
                                                        value="<?= htmlspecialchars((string)($row['remarks'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                        style="height: 32px; font-size: 12.5px;"
                                                    >
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top bg-white">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAttendanceEdit(false)">
                                    Cancel
                                </button>
                                <button type="submit" class="ks-btn ks-btn-primary" id="btnSaveAttendance">
                                    <i class="bi bi-check2"></i>
                                    <span>Save Attendance</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <script>
                    function toggleAttendanceEdit(forceState) {
                        var viewEl = document.getElementById('attendanceViewMode');
                        var editEl = document.getElementById('attendanceEditMode');
                        var btnText = document.getElementById('btnToggleAttendanceEditText');
                        if (!viewEl || !editEl) return;

                        var shouldShowEdit = (typeof forceState === 'boolean') ? forceState : (editEl.style.display === 'none');
                        if (shouldShowEdit) {
                            viewEl.style.display = 'none';
                            editEl.style.display = 'block';
                            if (btnText) btnText.textContent = 'Cancel Edit';
                        } else {
                            viewEl.style.display = 'block';
                            editEl.style.display = 'none';
                            if (btnText) btnText.textContent = '<?= $hasAttendanceLogged ? "Edit Attendance" : "Mark Training Attendance" ?>';
                        }
                    }

                    document.addEventListener('DOMContentLoaded', function () {
                        var markAllBtn = document.getElementById('btn-mark-all-present');
                        if (!markAllBtn) return;
                        markAllBtn.addEventListener('click', function () {
                            document.querySelectorAll('.js-att-radio[value="present"]').forEach(function (radio) {
                                radio.checked = true;
                            });
                        });
                    });
                    </script>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
