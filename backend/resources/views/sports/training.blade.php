<?php
$pageTitle = 'Training — KhelSutra';
$title = $pageTitle;
$activePage = 'training';
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

$canCreate = !$isAthlete && (
           $permissionService->hasPermission($userPayload, 'training.create', $orgId)
        || $permissionService->hasPermission($userPayload, 'training.manage', $orgId)
        || in_array('training.create', $userPermissions, true)
        || in_array('training.manage', $userPermissions, true)
);

$canEdit = !$isAthlete && (
         $permissionService->hasPermission($userPayload, 'training.update', $orgId)
      || $permissionService->hasPermission($userPayload, 'training.manage', $orgId)
      || in_array('training.update', $userPermissions, true)
      || in_array('training.manage', $userPermissions, true)
);

$canDelete = !$isAthlete && !$isCoach && (
           $permissionService->hasPermission($userPayload, 'training.manage', $orgId)
        || in_array('training.manage', $userPermissions, true)
);

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10; // Exact project requirement: 10 sessions per page
$search = trim($_GET['search'] ?? '');
$teamId = !empty($_GET['team_id']) ? (int)$_GET['team_id'] : null;
$coachId = !empty($_GET['coach_id']) ? (int)$_GET['coach_id'] : null;
$date = trim($_GET['date'] ?? '');
$status = trim($_GET['status'] ?? '');

$db = \App\Services\BaseService::getDatabaseConnection();

$scopedCoachId = $coachId;
$scopedAthleteId = null;
if ($isCoach && $db) {
    $myCoachId = (int)($currentUser['coach_id'] ?? ($_SESSION['auth']['coach_id'] ?? 0));
    if (!$myCoachId && $userId > 0) {
        $cStmt = $db->prepare("
            SELECT cp.id
            FROM coach_profiles cp
            JOIN employees e ON cp.employee_id = e.id
            WHERE e.user_id = :uid AND cp.organization_id = :oid AND cp.deleted_at IS NULL
            LIMIT 1
        ");
        $cStmt->execute([':uid' => $userId, ':oid' => $orgId]);
        $myCoachId = (int)($cStmt->fetchColumn() ?: 0);
    }
    if ($myCoachId > 0) {
        $scopedCoachId = $myCoachId;
    }
} elseif ($isAthlete && $db) {
    $myAthleteId = (int)($currentUser['athlete_id'] ?? ($_SESSION['auth']['athlete_id'] ?? 0));
    if (!$myAthleteId && $userId > 0) {
        $aStmt = $db->prepare("SELECT id FROM athletes WHERE user_id = :uid AND organization_id = :oid AND deleted_at IS NULL LIMIT 1");
        $aStmt->execute([':uid' => $userId, ':oid' => $orgId]);
        $myAthleteId = (int)($aStmt->fetchColumn() ?: 0);
    }
    if ($myAthleteId > 0) {
        $scopedAthleteId = $myAthleteId;
    }
}

$filterTeams = [];
$filterCoaches = [];
if ($db) {
    $tStmt = $db->prepare("SELECT id, name FROM teams WHERE organization_id = :org_id AND deleted_at IS NULL ORDER BY name ASC");
    $tStmt->execute([':org_id' => $orgId]);
    $filterTeams = $tStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $cStmt = $db->prepare("
        SELECT cp.id as coach_id, CONCAT(e.first_name, ' ', e.last_name) as coach_name
        FROM coach_profiles cp
        JOIN employees e ON cp.employee_id = e.id AND e.deleted_at IS NULL
        WHERE cp.organization_id = :org_id AND cp.deleted_at IS NULL
        ORDER BY e.first_name ASC, e.last_name ASC
    ");
    $cStmt->execute([':org_id' => $orgId]);
    $filterCoaches = $cStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$trainService = new \App\Services\Training\TrainingService();
$result = $trainService->listSessions(
    $orgId,
    $page,
    $perPage,
    $search !== '' ? $search : null,
    $date !== '' ? $date : null,
    $status !== '' ? $status : null,
    $teamId,
    $scopedCoachId,
    $scopedAthleteId
);

$sessions = $result['data'] ?? [];
$total = (int)($result['total'] ?? 0);
$page = (int)($result['page'] ?? $page);
$totalPages = max(1, (int)($result['total_pages'] ?? 1));
$fromCount = (int)($result['from'] ?? ($total > 0 ? (($page - 1) * $perPage) + 1 : 0));
$toCount = (int)($result['to'] ?? ($total > 0 ? min($total, $fromCount + count($sessions) - 1) : 0));

$hasActiveFilters = ($search !== '' || $teamId !== null || $coachId !== null || $date !== '' || $status !== '');

$buildPageUrl = function (int $targetPage) use ($search, $teamId, $coachId, $date, $status): string {
    $params = [];
    if ($search !== '') $params['search'] = $search;
    if ($teamId !== null) $params['team_id'] = $teamId;
    if ($coachId !== null) $params['coach_id'] = $coachId;
    if ($date !== '') $params['date'] = $date;
    if ($status !== '') $params['status'] = $status;
    $params['page'] = max(1, $targetPage);
    return '/training?' . http_build_query($params);
};

ob_start();
?>

<!-- Page Header (Section 1 & 27) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Training</h1>
        <p class="ks-page-subtitle">Scheduled training sessions and squad practice programs.</p>
    </div>
    <div class="ks-header-actions">
        <a href="/attendance/training" class="ks-btn ks-btn-secondary" id="btnAttendanceHistory" title="View historical attendance audit records">
            <i class="bi bi-clock-history"></i>
            <span>Attendance History</span>
        </a>
        <?php if ($canCreate): ?>
        <a href="/training/create" class="ks-btn ks-btn-primary" id="btnAddTraining">
            <i class="bi bi-plus-lg"></i>
            <span>Add Training Session</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Main Training Card: Clean Session-First List (Sections 2, 3, 27) -->
<div class="ks-table-card mb-4">
    <!-- Server-Side Filter Bar (Sections 10 & 11) -->
    <div class="ks-table-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-stopwatch-fill" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0" id="trainingSessionsCount">Training Sessions (<?= (int)$total ?>)</span>
        </div>

        <form action="/training" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0" id="trainingFilterForm">
            <div class="position-relative" style="width: 220px;">
                <i class="bi bi-search position-absolute" style="left: 12px; top: 11px; color: var(--ks-text-muted); font-size: 13px;"></i>
                <input
                    type="text"
                    name="search"
                    id="trainingSearchInput"
                    value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                    class="ks-form-control"
                    style="padding-left: 34px; height: 38px; font-size: 13px;"
                    placeholder="Search sessions..."
                >
            </div>

            <?php if (!$isCoach): ?>
            <select name="coach_id" id="trainingCoachFilter" class="ks-form-select" style="width: 155px; height: 38px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Coaches</option>
                <?php foreach ($filterCoaches as $fc): ?>
                    <option value="<?= (int)$fc['coach_id'] ?>" <?= $coachId === (int)$fc['coach_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($fc['coach_name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <select name="team_id" id="trainingTeamFilter" class="ks-form-select" style="width: 150px; height: 38px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Teams</option>
                <?php foreach ($filterTeams as $ft): ?>
                    <option value="<?= (int)$ft['id'] ?>" <?= $teamId === (int)$ft['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ft['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input
                type="date"
                name="date"
                id="trainingDateFilter"
                value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>"
                class="ks-form-control"
                style="width: 145px; height: 38px; font-size: 13px;"
                onchange="this.form.submit()"
                title="Filter by session date"
            >

            <select name="status" id="trainingStatusFilter" class="ks-form-select" style="width: 135px; height: 38px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>

            <button type="submit" class="btn btn-sm btn-outline-primary" style="height: 38px; padding: 0 12px; font-size: 13px; font-weight: 500;" title="Apply Filters">
                Filter
            </button>

            <?php if ($hasActiveFilters): ?>
                <a href="/training" class="btn btn-sm btn-outline-secondary" style="height: 38px; display: flex; align-items: center; padding: 0 12px; font-size: 13px;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Clean Session Table (Section 3 & 27) -->
    <div class="table-responsive">
        <table class="ks-table align-middle mb-0" id="trainingSessionsTable">
            <thead>
                <tr>
                    <th style="width: 16%;">Date &amp; Time</th>
                    <th style="width: 28%;">Training Session</th>
                    <th style="width: 18%;">Coach</th>
                    <th style="width: 18%;">Team / Sport</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 10%; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sessions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-stopwatch d-block fs-1 mb-2" style="color: var(--ks-text-muted);"></i>
                            <div class="fw-semibold text-navy fs-5">No training sessions found</div>
                            <div class="small mt-1 mb-3">
                                <?= $hasActiveFilters
                                    ? 'No training sessions match the current filters.'
                                    : 'No training sessions have been scheduled yet.' ?>
                            </div>
                            <?php if ($canCreate && !$hasActiveFilters): ?>
                            <a href="/training/create" class="ks-btn ks-btn-primary d-inline-flex">
                                <i class="bi bi-plus-lg"></i>
                                <span>Add Training Session</span>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sessions as $sess): ?>
                        <?php
                            $sessId = (int)$sess['id'];
                            $sessTitle = trim((string)($sess['title'] ?: ($sess['training_type'] ?? 'Training Session')));
                            $sessRef = trim((string)($sess['training_reference'] ?? ''));
                            $sessStatus = strtolower(trim((string)($sess['status'] ?? 'scheduled')));
                            $statusBadgeClass = match ($sessStatus) {
                                'completed' => 'ks-badge-completed',
                                'in_progress' => 'ks-badge-pending',
                                'cancelled' => 'ks-badge-rejected',
                                default => 'ks-badge-scheduled',
                            };
                            $statusLabel = ucwords(str_replace('_', ' ', $sessStatus));
                        ?>
                        <tr data-session-id="<?= $sessId ?>">
                            <!-- 1. Date & Time -->
                            <td>
                                <div class="fw-semibold text-dark" style="font-size: 13.5px;">
                                    <?= !empty($sess['training_date']) ? date('d M Y', strtotime($sess['training_date'])) : '—' ?>
                                </div>
                                <div class="small text-muted font-monospace">
                                    <?= htmlspecialchars(substr((string)($sess['start_time'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                    &ndash;
                                    <?= htmlspecialchars(substr((string)($sess['end_time'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </td>

                            <!-- 2. Training Session -->
                            <td>
                                <div class="fw-semibold text-navy">
                                    <a href="/training/<?= $sessId ?>" class="text-navy text-decoration-none hover-primary">
                                        <?= htmlspecialchars($sessTitle, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </div>
                                <?php if ($sessRef !== ''): ?>
                                    <div class="small text-muted font-monospace">
                                        <?= htmlspecialchars($sessRef, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- 3. Coach -->
                            <td>
                                <?php if (!empty($sess['coach_name'])): ?>
                                    <span class="fw-medium text-dark"><?= htmlspecialchars($sess['coach_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- 4. Team / Sport -->
                            <td>
                                <?php if (!empty($sess['team_name'])): ?>
                                    <div class="fw-medium text-dark">
                                        <?php if (!empty($sess['team_id'])): ?>
                                            <a href="/teams/<?= (int)$sess['team_id'] ?>" class="text-dark text-decoration-none hover-primary">
                                                <?= htmlspecialchars($sess['team_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        <?php else: ?>
                                            <?= htmlspecialchars($sess['team_name'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($sess['sport_name'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars($sess['sport_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- 5. Status -->
                            <td>
                                <span class="ks-badge <?= $statusBadgeClass ?> text-capitalize">
                                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <!-- 6. Action -->
                            <td style="text-align: right;">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <a href="/training/<?= $sessId ?>" class="btn btn-sm btn-primary" title="View Session Detail &amp; Athletes" style="padding: 4px 12px; font-size: 12.5px; font-weight: 500;">
                                        View
                                    </a>
                                    <?php if ($canEdit): ?>
                                    <a href="/training/<?= $sessId ?>/edit" class="btn btn-sm btn-outline-secondary" title="Edit Session" style="padding: 4px 8px; font-size: 12px;">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($canDelete): ?>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger js-archive-training-btn"
                                        title="Archive Session"
                                        style="padding: 4px 8px; font-size: 12px;"
                                        data-session-id="<?= $sessId ?>"
                                        data-session-title="<?= htmlspecialchars($sessTitle, ENT_QUOTES, 'UTF-8') ?>"
                                        data-session-ref="<?= htmlspecialchars($sessRef, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Server-Side Pagination Footer (Section 12 & 27) -->
    <?php if ($total > 0): ?>
        <div class="p-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2" id="trainingPaginationFooter">
            <div class="small text-muted" id="trainingPaginationSummary">
                Showing <strong><?= (int)$fromCount ?>–<?= (int)$toCount ?></strong> of <strong><?= (int)$total ?></strong> training <?= $total === 1 ? 'session' : 'sessions' ?>
            </div>
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Training pagination">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page > 1 ? htmlspecialchars($buildPageUrl($page - 1), ENT_QUOTES, 'UTF-8') : '#' ?>" tabindex="<?= $page <= 1 ? '-1' : '0' ?>">Previous</a>
                    </li>
                    <?php
                    $window = 2;
                    $startPage = max(1, $page - $window);
                    $endPage = min($totalPages, $page + $window);
                    if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= htmlspecialchars($buildPageUrl(1), ENT_QUOTES, 'UTF-8') ?>">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars($buildPageUrl($p), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= htmlspecialchars($buildPageUrl($totalPages), ENT_QUOTES, 'UTF-8') ?>"><?= $totalPages ?></a>
                        </li>
                    <?php endif; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page < $totalPages ? htmlspecialchars($buildPageUrl($page + 1), ENT_QUOTES, 'UTF-8') : '#' ?>" tabindex="<?= $page >= $totalPages ? '-1' : '0' ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($canDelete): ?>
<!-- Archive Training Session Confirmation Modal -->
<div class="modal fade" id="archiveTrainingModal" tabindex="-1" aria-labelledby="archiveTrainingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: 1px solid var(--ks-border);">
            <div class="modal-header border-bottom-0 pb-0">
                <div class="d-flex align-items-center gap-2 text-danger">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <h5 class="modal-title fw-bold mb-0" id="archiveTrainingModalLabel">Archive Training Session</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-2 text-dark">
                    Are you sure you want to archive <strong id="archiveTrainingTitleText">this session</strong>
                    (<span id="archiveTrainingRefText" class="text-muted"></span>)?
                </p>
                <div class="p-3 rounded-3 small" style="background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA;">
                    <i class="bi bi-info-circle me-1"></i>
                    The session will be archived (`soft-deleted`) so historical athlete attendance records remain intact.
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <form id="archiveTrainingForm" method="POST" action="" class="m-0">
                    <input type="hidden" name="return_search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="return_team_id" value="<?= (int)($teamId ?? 0) ?>">
                    <input type="hidden" name="return_coach_id" value="<?= (int)($coachId ?? 0) ?>">
                    <input type="hidden" name="return_date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="return_status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="return_page" value="<?= (int)$page ?>">
                    <button type="submit" class="btn btn-sm btn-danger px-3 fw-semibold">
                        <i class="bi bi-archive me-1"></i> Confirm Archive
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('archiveTrainingModal');
    var formEl = document.getElementById('archiveTrainingForm');
    var titleEl = document.getElementById('archiveTrainingTitleText');
    var refEl = document.getElementById('archiveTrainingRefText');

    document.querySelectorAll('.js-archive-training-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var sessId = this.getAttribute('data-session-id');
            var sessTitle = this.getAttribute('data-session-title') || 'this session';
            var sessRef = this.getAttribute('data-session-ref') || '';

            if (formEl) formEl.action = '/training/' + encodeURIComponent(sessId) + '/delete';
            if (titleEl) titleEl.textContent = sessTitle;
            if (refEl) refEl.textContent = sessRef;

            if (modalEl && window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else if (confirm('Are you sure you want to archive ' + sessTitle + ' (' + sessRef + ')?')) {
                if (formEl) formEl.submit();
            }
        });
    });
});
</script>
<?php endif; ?>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
