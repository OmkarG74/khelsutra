<?php
$activePage = 'coaches';
$title = 'Coaches — KhelSutra';

$orgId = current_organization_id();
$search = trim($_GET['search'] ?? '');
$specialization = trim($_GET['specialization'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

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

if ($isAthlete) {
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo '<div class="p-5 text-center text-danger fw-bold">403 Forbidden: Athletes are restricted from accessing Coaching Staff administration.</div>';
    return;
}

// Resolve RBAC permissions
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
$canEditCoach = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'coach.update', $orgId) ||
    $permissionService->hasPermission($userPayload, 'coach.manage', $orgId)
);
$canDeleteCoach = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'coach.manage', $orgId) ||
    $permissionService->hasPermission($userPayload, 'coach.delete', $orgId)
);

$coachService = new \App\Services\Coach\CoachService();
$result = $coachService->listCoaches(
    $orgId,
    $page,
    $perPage,
    $search !== '' ? $search : null,
    $specialization !== '' ? $specialization : null,
    $status !== '' ? $status : null
);

$coaches = $result['data'] ?? [];
$totalCoaches = (int)($result['total'] ?? 0);
$totalPages = max(1, (int)($result['total_pages'] ?? 1));
$page = (int)($result['page'] ?? $page);
$fromCount = (int)($result['from'] ?? 0);
$toCount = (int)($result['to'] ?? 0);

// Distinct specializations scoped strictly to current organization and non-deleted coaches
$specializations = $coachService->getDistinctSpecializations($orgId);
if ($specialization !== '') {
    $hasCurrentSpec = false;
    foreach ($specializations as $spItem) {
        if (strcasecmp($spItem, $specialization) === 0) {
            $hasCurrentSpec = true;
            break;
        }
    }
    if (!$hasCurrentSpec) {
        $specializations[] = $specialization;
    }
}

// Build filtered export URL (preserves active filters, ignores pagination)
$exportParams = [];
if ($search !== '') $exportParams['search'] = $search;
if ($specialization !== '') $exportParams['specialization'] = $specialization;
if ($status !== '') $exportParams['status'] = $status;
$exportUrl = '/coaches/export' . (!empty($exportParams) ? '?' . http_build_query($exportParams) : '');

// Helper to build pagination URLs preserving active filters
$buildPageUrl = function (int $targetPage) use ($search, $specialization, $status): string {
    $params = [];
    if ($search !== '') $params['search'] = $search;
    if ($specialization !== '') $params['specialization'] = $specialization;
    if ($status !== '') $params['status'] = $status;
    $params['page'] = max(1, $targetPage);
    return '/coaches?' . http_build_query($params);
};

// Helper to format experience years cleanly without trailing .00
$formatExpYears = function ($years): string {
    if ($years === null || $years === '') {
        return 'Experience not specified';
    }
    $num = (float)$years;
    $clean = ((float)(int)$num === $num) ? (string)(int)$num : rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
    return $clean . ' ' . ($num == 1.0 ? 'yr experience' : 'yrs experience');
};

ob_start();
?>

<!-- Page Header -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Coaches</h1>
    </div>
    <div class="ks-header-actions">
        <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="ks-btn ks-btn-secondary" id="btnExportCoaches" title="Export filtered coaches to Excel (.xlsx)">
            <i class="bi bi-file-earmark-spreadsheet"></i>
            <span>Export Report</span>
        </a>
        <?php if ($canCreateCoach): ?>
        <a href="/coaches/create" class="ks-btn ks-btn-primary" id="btnAddCoach">
            <i class="bi bi-plus-lg"></i>
            <span>Add Coach</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Table Card & Live Filter Bar -->
<div class="ks-table-card">
    <div class="ks-table-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-badge-fill" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0" id="coachingStaffCount">Coaching Staff (<?= (int)$totalCoaches ?>)</span>
        </div>

        <form action="/coaches" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0" id="coachesFilterForm">
            <div class="position-relative" style="width: 260px;">
                <i class="bi bi-search position-absolute" style="left: 12px; top: 11px; color: var(--ks-text-muted); font-size: 13px;"></i>
                <input type="text" name="search" id="coachSearchInput" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" class="ks-form-control" style="padding-left: 34px; height: 38px; font-size: 13px;" placeholder="Search name, code, specialization...">
            </div>

            <select name="specialization" id="coachSpecializationFilter" class="ks-form-select" style="width: 185px; height: 38px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Specializations</option>
                <?php foreach ($specializations as $spec): ?>
                    <option value="<?= htmlspecialchars($spec, ENT_QUOTES, 'UTF-8') ?>" <?= strcasecmp($specialization, $spec) === 0 ? 'selected' : '' ?>>
                        <?= htmlspecialchars($spec, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status" id="coachStatusFilter" class="ks-form-select" style="width: 140px; height: 38px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>

            <button type="submit" class="btn btn-sm btn-outline-primary" style="height: 38px; padding: 0 12px; font-size: 13px; font-weight: 500;" title="Apply Filters">
                Filter
            </button>

            <?php if ($search !== '' || $specialization !== '' || $status !== ''): ?>
                <a href="/coaches" class="btn btn-sm btn-outline-secondary" style="height: 38px; display: flex; align-items: center; padding: 0 12px; font-size: 13px;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <style>
        #coachesTable.ks-table-coaches th:nth-child(1),
        #coachesTable.ks-table-coaches td:nth-child(1) {
            width: 4% !important;
            min-width: 40px !important;
            max-width: 48px !important;
            padding-right: 8px !important;
        }
        #coachesTable.ks-table-coaches th:nth-child(2),
        #coachesTable.ks-table-coaches td:nth-child(2) {
            width: 22% !important;
            min-width: 190px !important;
        }
        #coachesTable.ks-table-coaches th:nth-child(3),
        #coachesTable.ks-table-coaches td:nth-child(3) {
            width: 12% !important;
            min-width: 110px !important;
        }
        #coachesTable.ks-table-coaches th:nth-child(4),
        #coachesTable.ks-table-coaches td:nth-child(4) {
            width: 18% !important;
            min-width: 150px !important;
        }
        #coachesTable.ks-table-coaches th:nth-child(5),
        #coachesTable.ks-table-coaches td:nth-child(5) {
            width: 16% !important;
            min-width: 135px !important;
        }
        #coachesTable.ks-table-coaches th:nth-child(6),
        #coachesTable.ks-table-coaches td:nth-child(6) {
            width: 13% !important;
            min-width: 125px !important;
        }
        #coachesTable.ks-table-coaches th:nth-child(7),
        #coachesTable.ks-table-coaches td:nth-child(7) {
            width: 7% !important;
            min-width: 75px !important;
        }
        #coachesTable.ks-table-coaches th:nth-child(8),
        #coachesTable.ks-table-coaches td:nth-child(8) {
            width: 8% !important;
            min-width: 85px !important;
            text-align: right !important;
        }
    </style>
    <div class="table-responsive">
        <table class="ks-table ks-table-coaches" id="coachesTable">
            <thead>
                <tr>
                    <th style="width: 44px;">#</th>
                    <th>Coach</th>
                    <th>Code</th>
                    <th>Specialization</th>
                    <th>Assigned Teams</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($coaches)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-person-badge d-block fs-1 mb-2" style="color: var(--ks-text-muted);"></i>
                            <div class="fw-semibold text-navy fs-5">No coaches found</div>
                            <div class="small mt-1 mb-3">
                                <?= ($search !== '' || $specialization !== '' || $status !== '')
                                    ? 'No coaches match the current filters.'
                                    : 'No coaching staff registered in this academy yet.' ?>
                            </div>
                            <?php if ($canCreateCoach && $search === '' && $specialization === '' && $status === ''): ?>
                            <a href="/coaches/create" class="ks-btn ks-btn-primary d-inline-flex">
                                <i class="bi bi-plus-lg"></i>
                                <span>Add Coach</span>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $idx = ($page - 1) * $perPage + 1; foreach ($coaches as $coach): ?>
                        <?php
                        $cId = (int)($coach['coach_profile_id'] ?? ($coach['id'] ?? 0));
                        $cFullName = trim(($coach['first_name'] ?? '') . ' ' . ($coach['last_name'] ?? ''));
                        $initials = strtoupper(substr(trim($coach['first_name'] ?? 'C'), 0, 1) . substr(trim($coach['last_name'] ?? 'C'), 0, 1));
                        $designation = !empty($coach['designation']) ? (string)$coach['designation'] : 'Coach';
                        $activeTeams = $coach['teams'] ?? [];
                        $primaryTeamName = $coach['primary_team_name'] ?? ($activeTeams[0]['team_name'] ?? null);
                        $extraTeamsCount = (int)($coach['extra_teams_count'] ?? max(0, count($activeTeams) - 1));
                        $allTeamsTooltip = $coach['all_teams_label'] ?? ($coach['assigned_teams'] ?? ($primaryTeamName ?: 'Unassigned'));
                        $cStatus = strtolower((string)($coach['coach_status'] ?? ($coach['status'] ?? 'active')));
                        $specDisplay = !empty($coach['specialization']) ? (string)$coach['specialization'] : 'General Coaching';
                        ?>
                        <tr data-coach-id="<?= $cId ?>">
                            <td><?= $idx++ ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="ks-user-avatar" style="width: 36px; height: 36px; font-size: 12px; background: #E8F2FF; color: var(--ks-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">
                                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-navy">
                                            <a href="/coaches/<?= $cId ?>" class="text-navy text-decoration-none hover-primary">
                                                <?= htmlspecialchars($cFullName, ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </div>
                                        <div class="small text-muted"><?= htmlspecialchars($designation, ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-medium text-navy"><?= htmlspecialchars($coach['coach_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td style="max-width: 240px;">
                                <div class="fw-medium text-dark text-truncate" title="<?= htmlspecialchars($specDisplay, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($specDisplay, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="small text-muted"><?= htmlspecialchars($formatExpYears($coach['experience_years'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td style="max-width: 220px;">
                                <?php if (!empty($primaryTeamName)): ?>
                                    <div class="d-flex align-items-center flex-wrap gap-1" title="<?= htmlspecialchars((string)$allTeamsTooltip, ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="fw-medium text-dark text-truncate" style="max-width: 150px;"><?= htmlspecialchars($primaryTeamName, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($extraTeamsCount > 0): ?>
                                            <span class="badge rounded-pill" style="background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; font-size: 11px; font-weight: 600;">
                                                +<?= $extraTeamsCount ?> more
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?= !empty($coach['phone']) ? htmlspecialchars($coach['phone'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted small">Not provided</span>' ?></div>
                                <?php if (!empty($coach['email'])): ?>
                                    <div class="small text-muted text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($coach['email'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($coach['email'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($canEditCoach): ?>
                                <form action="/coaches/<?= $cId ?>/status" method="POST" class="d-inline m-0 p-0">
                                    <input type="hidden" name="status" value="<?= $cStatus === 'active' ? 'inactive' : 'active' ?>">
                                    <input type="hidden" name="return_search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="return_specialization" value="<?= htmlspecialchars($specialization, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="return_status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="return_page" value="<?= (int)$page ?>">
                                    <button type="submit" class="ks-badge ks-badge-<?= $cStatus === 'active' ? 'confirmed' : 'pending' ?> text-capitalize" style="cursor: pointer; border: 1px solid <?= $cStatus === 'active' ? '#BBF7D0' : '#FED7AA' ?>; background-color: <?= $cStatus === 'active' ? '#DCFCE7' : '#FFEDD5' ?>; color: <?= $cStatus === 'active' ? '#166534' : '#9A3412' ?>; padding: 0 10px; font-family: inherit;" title="Click to toggle status to <?= $cStatus === 'active' ? 'Inactive' : 'Active' ?>">
                                        <?= htmlspecialchars($cStatus, ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="ks-badge ks-badge-<?= $cStatus === 'active' ? 'confirmed' : 'pending' ?> text-capitalize" style="padding: 4px 10px;">
                                    <?= htmlspecialchars($cStatus, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <a href="/coaches/<?= $cId ?>" class="btn btn-sm btn-outline-secondary" title="View Profile" style="padding: 4px 8px; font-size: 12px;">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <?php if ($canEditCoach): ?>
                                    <a href="/coaches/<?= $cId ?>/edit" class="btn btn-sm btn-outline-primary" title="Edit Coach" style="padding: 4px 8px; font-size: 12px;">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($canDeleteCoach): ?>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger js-archive-coach-btn"
                                        title="Archive Coach"
                                        style="padding: 4px 8px; font-size: 12px;"
                                        data-coach-id="<?= $cId ?>"
                                        data-coach-name="<?= htmlspecialchars($cFullName, ENT_QUOTES, 'UTF-8') ?>"
                                        data-coach-code="<?= htmlspecialchars($coach['coach_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
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
    <?php if ($totalCoaches > 0): ?>
        <div class="p-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2" id="coachesPaginationFooter">
            <div class="small text-muted" id="coachesPaginationSummary">
                Showing <strong><?= (int)$fromCount ?>–<?= (int)$toCount ?></strong> of <strong><?= (int)$totalCoaches ?></strong> <?= $totalCoaches === 1 ? 'coach' : 'coaches' ?>
            </div>
            <nav aria-label="Coaches pagination">
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

<?php if ($canDeleteCoach): ?>
<!-- Archive / Soft-Delete Coach Confirmation Modal -->
<div class="modal fade" id="archiveCoachModal" tabindex="-1" aria-labelledby="archiveCoachModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content" style="border-radius: 14px; border: 1px solid #E2E8F0; box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.15);">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #FEF2F2; color: #DC2626; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="bi bi-archive-fill"></i>
                    </div>
                    <h5 class="modal-title fw-bold mb-0" id="archiveCoachModalLabel" style="color: #0F172A; font-size: 17px;">Archive Coach?</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="archiveCoachForm" method="POST" action="">
                <input type="hidden" name="return_search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="return_specialization" value="<?= htmlspecialchars($specialization, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="return_status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="return_page" value="<?= (int)$page ?>">
                <div class="modal-body px-4 py-3">
                    <p class="mb-2" style="color: #1E293B; font-size: 14px;">
                        Are you sure you want to archive <strong id="archiveCoachNameDisplay">this coach</strong>
                        <span id="archiveCoachCodeDisplay" class="text-muted small"></span>?
                    </p>
                    <p class="text-muted small mb-0" style="line-height: 1.5;">
                        The coach will be removed from active lists while historical records are preserved.
                    </p>
                </div>
                <div class="modal-footer border-top-0 pt-1 pb-4 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500; border-radius: 8px; border: 1px solid #CBD5E1;">Cancel</button>
                    <button type="submit" class="btn btn-danger px-3" id="confirmArchiveCoachBtn" style="font-size: 13px; font-weight: 600; border-radius: 8px;">
                        <i class="bi bi-archive me-1"></i> Archive Coach
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('archiveCoachModal');
    const formEl = document.getElementById('archiveCoachForm');
    const nameEl = document.getElementById('archiveCoachNameDisplay');
    const codeEl = document.getElementById('archiveCoachCodeDisplay');

    if (modalEl && formEl) {
        let bsModal = null;
        document.querySelectorAll('.js-archive-coach-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const coachId = this.getAttribute('data-coach-id');
                const coachName = this.getAttribute('data-coach-name') || 'this coach';
                const coachCode = this.getAttribute('data-coach-code') || '';

                formEl.setAttribute('action', '/coaches/' + encodeURIComponent(coachId) + '/delete');
                if (nameEl) nameEl.textContent = coachName;
                if (codeEl) codeEl.textContent = coachCode ? '(' + coachCode + ')' : '';

                if (window.bootstrap && window.bootstrap.Modal) {
                    bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                } else {
                    if (confirm('Are you sure you want to archive ' + coachName + '? The coach will be removed from active lists while historical records are preserved.')) {
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
