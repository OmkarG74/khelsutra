<?php
$activePage = 'teams';
$title = 'Teams — KhelSutra';
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

$canViewTeams = $isCoach || $isAthlete
    || $permissionService->hasPermission($userPayload, 'team.view', $orgId)
    || $permissionService->hasPermission($userPayload, 'team.manage', $orgId);

$canCreateTeam = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'team.create', $orgId) ||
    $permissionService->hasPermission($userPayload, 'team.manage', $orgId)
);

$canEditTeam = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'team.update', $orgId) ||
    $permissionService->hasPermission($userPayload, 'team.manage', $orgId)
);

$canDeleteTeam = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'team.manage', $orgId) ||
    $permissionService->hasPermission($userPayload, 'team.update', $orgId)
);

if (!$canViewTeams) {
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo '<div class="p-5 text-center text-danger fw-bold">403 Forbidden: You do not have permission to view teams.</div>';
    return;
}

$teamService = new \App\Services\Team\TeamService();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$search = trim($_GET['search'] ?? '');
$sportId = !empty($_GET['sport_id']) ? (int)$_GET['sport_id'] : null;
$status = trim($_GET['status'] ?? '');

$filterCoachId = null;
$filterAthleteId = null;
$pdo = \App\Services\BaseService::getDatabaseConnection();

if ($isAthlete) {
    $filterAthleteId = (int)($currentUser['athlete_id'] ?? 0);
    if ($filterAthleteId <= 0 && $pdo && $userId > 0) {
        $aStmt = $pdo->prepare("SELECT id FROM athletes WHERE user_id = :uid AND organization_id = :oid AND deleted_at IS NULL LIMIT 1");
        $aStmt->execute([':uid' => $userId, ':oid' => $orgId]);
        $filterAthleteId = (int)($aStmt->fetchColumn() ?: -1);
    }
    if ($filterAthleteId <= 0) {
        $filterAthleteId = -1;
    }
} elseif ($isCoach) {
    $filterCoachId = (int)($currentUser['coach_id'] ?? 0);
    if ($filterCoachId <= 0 && $pdo && $userId > 0) {
        $cStmt = $pdo->prepare("
            SELECT cp.id
            FROM coach_profiles cp
            JOIN employees e ON cp.employee_id = e.id
            WHERE e.user_id = :uid AND cp.organization_id = :oid AND cp.deleted_at IS NULL
            LIMIT 1
        ");
        $cStmt->execute([':uid' => $userId, ':oid' => $orgId]);
        $filterCoachId = (int)($cStmt->fetchColumn() ?: -1);
    }
    if ($filterCoachId <= 0) {
        $filterCoachId = -1;
    }
}

$result = $teamService->listTeams(
    $orgId,
    $page,
    $perPage,
    $search !== '' ? $search : null,
    $sportId,
    $status !== '' ? $status : null,
    $filterCoachId,
    $filterAthleteId
);

$teams = $result['data'] ?? [];
$totalTeams = (int)($result['total'] ?? 0);
$page = (int)($result['page'] ?? $page);
$totalPages = max(1, (int)($result['total_pages'] ?? 1));
$fromCount = (int)($result['from'] ?? ($totalTeams > 0 ? (($page - 1) * $perPage + 1) : 0));
$toCount = (int)($result['to'] ?? ($totalTeams > 0 ? min($totalTeams, ($page - 1) * $perPage + count($teams)) : 0));

// Fetch sports from config
$sportService = new \App\Services\Sport\SportService();
$sports = [];
foreach (config('sports.catalog') ?? [] as $key => $name) {
    $dbId = $sportService->resolveSportId($key);
    $sports[] = ['id' => $dbId, 'name' => $name];
}
usort($sports, fn($a, $b) => strcmp($a['name'], $b['name']));

// Helper to build pagination URLs preserving active filters
$buildPageUrl = function (int $targetPage) use ($search, $sportId, $status): string {
    $params = [];
    if ($search !== '') $params['search'] = $search;
    if (!empty($sportId)) $params['sport_id'] = $sportId;
    if ($status !== '') $params['status'] = $status;
    $params['page'] = max(1, $targetPage);
    return '/teams?' . http_build_query($params);
};

ob_start();
?>

<!-- Page Header -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title"><?= $isCoach ? 'My Coached Teams' : ($isAthlete ? 'My Teams' : 'Teams') ?></h1>
    </div>
    <?php if ($canCreateTeam): ?>
    <div class="ks-header-actions">
        <a href="/teams/create" class="ks-btn ks-btn-primary" id="btnAddTeam">
            <i class="bi bi-plus-lg"></i>
            <span>Add Team</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Table Card & Live Filter Bar -->
<div class="ks-table-card">
    <div class="ks-table-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-shaded" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0" id="teamsCount">Teams (<?= (int)$totalTeams ?>)</span>
        </div>

        <form action="/teams" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0" id="teamsFilterForm">
            <div class="position-relative" style="width: 260px;">
                <i class="bi bi-search position-absolute" style="left: 12px; top: 11px; color: var(--ks-text-muted); font-size: 13px;"></i>
                <input type="text" name="search" id="teamSearchInput" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" class="ks-form-control" style="padding-left: 34px; height: 38px; font-size: 13px;" placeholder="Search team name, code, age group...">
            </div>

            <select name="sport_id" id="teamSportFilter" class="ks-form-select" style="width: 175px; height: 38px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Sports</option>
                <?php foreach ($sports as $sp): ?>
                    <option value="<?= (int)$sp['id'] ?>" <?= $sportId == $sp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status" id="teamStatusFilter" class="ks-form-select" style="width: 140px; height: 38px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>

            <button type="submit" class="btn btn-sm btn-outline-primary" style="height: 38px; padding: 0 12px; font-size: 13px; font-weight: 500;" title="Apply Filters">
                Filter
            </button>

            <?php if ($search !== '' || !empty($sportId) || $status !== ''): ?>
                <a href="/teams" class="btn btn-sm btn-outline-secondary" style="height: 38px; display: flex; align-items: center; padding: 0 12px; font-size: 13px;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <style>
        #teamsTable.ks-table-teams th:nth-child(1),
        #teamsTable.ks-table-teams td:nth-child(1) {
            width: 4% !important;
            min-width: 40px !important;
            max-width: 48px !important;
            padding-right: 8px !important;
        }
        #teamsTable.ks-table-teams th:nth-child(2),
        #teamsTable.ks-table-teams td:nth-child(2) {
            width: 24% !important;
            min-width: 200px !important;
        }
        #teamsTable.ks-table-teams th:nth-child(3),
        #teamsTable.ks-table-teams td:nth-child(3) {
            width: 12% !important;
            min-width: 110px !important;
        }
        #teamsTable.ks-table-teams th:nth-child(4),
        #teamsTable.ks-table-teams td:nth-child(4) {
            width: 15% !important;
            min-width: 130px !important;
        }
        #teamsTable.ks-table-teams th:nth-child(5),
        #teamsTable.ks-table-teams td:nth-child(5) {
            width: 18% !important;
            min-width: 150px !important;
        }
        #teamsTable.ks-table-teams th:nth-child(6),
        #teamsTable.ks-table-teams td:nth-child(6) {
            width: 11% !important;
            min-width: 105px !important;
        }
        #teamsTable.ks-table-teams th:nth-child(7),
        #teamsTable.ks-table-teams td:nth-child(7) {
            width: 8% !important;
            min-width: 80px !important;
        }
        #teamsTable.ks-table-teams th:nth-child(8),
        #teamsTable.ks-table-teams td:nth-child(8) {
            width: 8% !important;
            min-width: 85px !important;
            text-align: right !important;
        }
    </style>

    <div class="table-responsive">
        <table class="ks-table ks-table-teams" id="teamsTable">
            <thead>
                <tr>
                    <th style="width: 44px;">#</th>
                    <th>Team</th>
                    <th>Sport</th>
                    <th>Division &amp; Level</th>
                    <th>Coach</th>
                    <th>Athletes</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teams)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-shield d-block fs-1 mb-2" style="color: var(--ks-text-muted);"></i>
                            <div class="fw-semibold text-navy fs-5">No teams found</div>
                            <div class="small mt-1 mb-3">
                                <?= ($search !== '' || !empty($sportId) || $status !== '')
                                    ? 'No teams match the current filters.'
                                    : 'No team squads registered in this academy yet.' ?>
                            </div>
                            <?php if ($canCreateTeam && $search === '' && empty($sportId) && $status === ''): ?>
                            <a href="/teams/create" class="ks-btn ks-btn-primary d-inline-flex">
                                <i class="bi bi-plus-lg"></i>
                                <span>Add Team</span>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $idx = ($page - 1) * $perPage + 1; foreach ($teams as $team): ?>
                        <?php
                        $tId = (int)($team['id'] ?? 0);
                        $tName = trim((string)($team['name'] ?? 'Team'));
                        $tCode = trim((string)($team['team_code'] ?? ''));
                        $initials = strtoupper(substr($tName, 0, 2));
                        $sportName = !empty($team['sport_name']) ? (string)$team['sport_name'] : 'General';
                        $ageGroup = !empty($team['age_group']) ? (string)$team['age_group'] : 'Open';
                        $genderLabel = !empty($team['gender']) ? ucfirst((string)$team['gender']) : 'Open';
                        $levelLabel = !empty($team['formation_or_level']) ? trim((string)$team['formation_or_level']) : '';
                        $divisionSub = $genderLabel . ($levelLabel !== '' ? ' • ' . $levelLabel : '');
                        $headCoachName = $team['head_coach_name'] ?? null;
                        $headCoachId = (int)($team['head_coach_id'] ?? 0);
                        $extraCoachesCount = (int)($team['extra_coaches_count'] ?? 0);
                        $allCoachesLabel = $team['all_coaches_label'] ?? ($headCoachName ?: 'Unassigned');
                        $athleteCount = (int)($team['athlete_count'] ?? 0);
                        $tStatus = strtolower((string)($team['status'] ?? 'active'));
                        ?>
                        <tr data-team-id="<?= $tId ?>">
                            <td><?= $idx++ ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="ks-user-avatar" style="width: 36px; height: 36px; font-size: 12px; background: #E8F2FF; color: var(--ks-primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-navy">
                                            <a href="/teams/<?= $tId ?>" class="text-navy text-decoration-none hover-primary">
                                                <?= htmlspecialchars($tName, ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </div>
                                        <div class="small text-muted"><?= htmlspecialchars($tCode, ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1" style="font-weight: 500; font-size: 12px;">
                                    <?= htmlspecialchars($sportName, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-medium text-dark"><?= htmlspecialchars($ageGroup, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="small text-muted text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($divisionSub, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($divisionSub, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($headCoachName)): ?>
                                    <div class="d-flex align-items-center flex-wrap gap-1" title="<?= htmlspecialchars((string)$allCoachesLabel, ENT_QUOTES, 'UTF-8') ?>">
                                        <?php if ($headCoachId > 0): ?>
                                            <a href="/coaches/<?= $headCoachId ?>" class="fw-medium text-decoration-none" style="color: var(--ks-primary);">
                                                <?= htmlspecialchars($headCoachName, ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="fw-medium text-dark"><?= htmlspecialchars($headCoachName, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                        <?php if ($extraCoachesCount > 0): ?>
                                            <span class="badge rounded-pill" style="background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; font-size: 11px; font-weight: 600;">
                                                +<?= $extraCoachesCount ?> more
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge rounded-pill" style="background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; font-size: 11.5px; font-weight: 600; padding: 4px 10px;">
                                    <?= $athleteCount ?> <?= $athleteCount === 1 ? 'Athlete' : 'Athletes' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($canEditTeam): ?>
                                <form action="/teams/<?= $tId ?>/status" method="POST" class="d-inline m-0 p-0">
                                    <input type="hidden" name="status" value="<?= $tStatus === 'active' ? 'inactive' : 'active' ?>">
                                    <input type="hidden" name="return_search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="return_sport_id" value="<?= htmlspecialchars((string)($sportId ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="return_status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="return_page" value="<?= (int)$page ?>">
                                    <button type="submit" class="ks-badge ks-badge-<?= $tStatus === 'active' ? 'confirmed' : 'pending' ?> text-capitalize" style="cursor: pointer; border: 1px solid <?= $tStatus === 'active' ? '#BBF7D0' : '#FED7AA' ?>; background-color: <?= $tStatus === 'active' ? '#DCFCE7' : '#FFEDD5' ?>; color: <?= $tStatus === 'active' ? '#166534' : '#9A3412' ?>; padding: 0 10px; font-family: inherit;" title="Click to toggle status to <?= $tStatus === 'active' ? 'Inactive' : 'Active' ?>">
                                        <?= htmlspecialchars($tStatus, ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="ks-badge ks-badge-<?= $tStatus === 'active' ? 'confirmed' : 'pending' ?> text-capitalize" style="padding: 4px 10px;">
                                    <?= htmlspecialchars($tStatus, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <a href="/teams/<?= $tId ?>" class="btn btn-sm btn-outline-secondary" title="View Team" style="padding: 4px 8px; font-size: 12px;">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <?php if ($canEditTeam): ?>
                                    <a href="/teams/<?= $tId ?>/edit" class="btn btn-sm btn-outline-primary" title="Edit Team" style="padding: 4px 8px; font-size: 12px;">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($canDeleteTeam): ?>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger js-archive-team-btn"
                                        title="Archive Team"
                                        style="padding: 4px 8px; font-size: 12px;"
                                        data-team-id="<?= $tId ?>"
                                        data-team-name="<?= htmlspecialchars($tName, ENT_QUOTES, 'UTF-8') ?>"
                                        data-team-code="<?= htmlspecialchars($tCode, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <i class="bi bi-trash"></i> Delete
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

    <!-- Server-Side Pagination Footer -->
    <?php if ($totalTeams > 0): ?>
        <div class="p-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2" id="teamsPaginationFooter">
            <div class="small text-muted" id="teamsPaginationSummary">
                Showing <strong><?= (int)$fromCount ?>–<?= (int)$toCount ?></strong> of <strong><?= (int)$totalTeams ?></strong> <?= $totalTeams === 1 ? 'team' : 'teams' ?>
            </div>
            <nav aria-label="Teams pagination">
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
        </div>
    <?php endif; ?>
</div>

<?php if ($canDeleteTeam): ?>
<!-- Archive / Soft-Delete Team Confirmation Modal -->
<div class="modal fade" id="archiveTeamModal" tabindex="-1" aria-labelledby="archiveTeamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content" style="border-radius: 14px; border: 1px solid #E2E8F0; box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.15);">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #FEF2F2; color: #DC2626; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="bi bi-archive-fill"></i>
                    </div>
                    <h5 class="modal-title fw-bold mb-0" id="archiveTeamModalLabel" style="color: #0F172A; font-size: 17px;">Archive Team?</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="archiveTeamForm" method="POST" action="">
                <input type="hidden" name="return_search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="return_sport_id" value="<?= htmlspecialchars((string)($sportId ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="return_status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="return_page" value="<?= (int)$page ?>">
                <div class="modal-body px-4 py-3">
                    <p class="mb-2" style="color: #1E293B; font-size: 14px;">
                        Are you sure you want to archive <strong id="archiveTeamNameDisplay">this team</strong>
                        <span id="archiveTeamCodeDisplay" class="text-muted small"></span>?
                    </p>
                    <p class="text-muted small mb-0" style="line-height: 1.5;">
                        The team will be removed from active lists while historical matches, training sessions, and roster records are preserved.
                    </p>
                </div>
                <div class="modal-footer border-top-0 pt-1 pb-4 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500; border-radius: 8px; border: 1px solid #CBD5E1;">Cancel</button>
                    <button type="submit" class="btn btn-danger px-3" id="confirmArchiveTeamBtn" style="font-size: 13px; font-weight: 600; border-radius: 8px;">
                        <i class="bi bi-archive me-1"></i> Archive Team
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('archiveTeamModal');
    const formEl = document.getElementById('archiveTeamForm');
    const nameEl = document.getElementById('archiveTeamNameDisplay');
    const codeEl = document.getElementById('archiveTeamCodeDisplay');

    if (modalEl && formEl) {
        let bsModal = null;
        document.querySelectorAll('.js-archive-team-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const teamId = this.getAttribute('data-team-id');
                const teamName = this.getAttribute('data-team-name') || 'this team';
                const teamCode = this.getAttribute('data-team-code') || '';

                formEl.setAttribute('action', '/teams/' + encodeURIComponent(teamId) + '/delete');
                if (nameEl) nameEl.textContent = teamName;
                if (codeEl) codeEl.textContent = teamCode ? '(' + teamCode + ')' : '';

                if (window.bootstrap && window.bootstrap.Modal) {
                    bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                } else {
                    if (confirm('Are you sure you want to archive ' + teamName + '?')) {
                        formEl.submit();
                    }
                }
            });
        });
    }
});
</script>
<?php endif; ?>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
