<?php
$activePage = 'athletes';
$title = 'Athletes — KhelSutra';

$orgId = current_organization_id();
$search = trim($_GET['search'] ?? '');
$sportId = !empty($_GET['sport_id']) ? (int)$_GET['sport_id'] : null;
$status = !empty($_GET['status']) ? trim($_GET['status']) : null;
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
    $myAthleteId = (int)($currentUser['athlete_id'] ?? 0);
    if ($myAthleteId) {
        if (!headers_sent()) {
            header("Location: /athletes/{$myAthleteId}");
            exit;
        }
        echo "<script>window.location.href='/athletes/{$myAthleteId}';</script>";
        exit;
    }
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

$canCreateAthlete = !$isCoach && !$isAthlete && $permissionService->hasPermission($userPayload, 'athlete.create', $orgId);
$canEditAthlete   = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'athlete.update', $orgId) ||
    $permissionService->hasPermission($userPayload, 'athlete.edit', $orgId)
);
$canDeleteAthlete = !$isCoach && !$isAthlete && $permissionService->hasPermission($userPayload, 'athlete.delete', $orgId);

$athleteService = new \App\Services\Athlete\AthleteService();

$coachId = null;
if ($isCoach) {
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
}

if ($isCoach && !$coachId) {
    $athletes = [];
    $totalAthletes = 0;
    $totalPages = 1;
    $fromCount = 0;
    $toCount = 0;
} else {
    $result = $athleteService->listAthletes(
        $orgId,
        $page,
        $perPage,
        $search !== '' ? $search : null,
        $sportId,
        $status !== '' ? $status : null,
        $coachId
    );
    $athletes = $result['data'] ?? [];
    $totalAthletes = (int)($result['total'] ?? 0);
    $totalPages = max(1, (int)($result['total_pages'] ?? 1));
    $page = (int)($result['page'] ?? $page);
    $fromCount = (int)($result['from'] ?? 0);
    $toCount = (int)($result['to'] ?? 0);
}

$sportService = new \App\Services\Sport\SportService();
$sportsList = [];
foreach (config('sports.catalog') ?? [] as $key => $name) {
    $dbId = $sportService->resolveSportId($key);
    $sportsList[] = ['id' => $dbId, 'name' => $name];
}
usort($sportsList, function ($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// Build filtered export URL (preserves active filters, ignores pagination)
$exportParams = [];
if ($search !== '') $exportParams['search'] = $search;
if (!empty($sportId)) $exportParams['sport_id'] = $sportId;
if ($status !== '' && $status !== null) $exportParams['status'] = $status;
$exportUrl = '/athletes/export' . (!empty($exportParams) ? '?' . http_build_query($exportParams) : '');

// Helper to build pagination URLs preserving active filters
$buildPageUrl = function (int $targetPage) use ($search, $sportId, $status): string {
    $params = [];
    if ($search !== '') $params['search'] = $search;
    if (!empty($sportId)) $params['sport_id'] = $sportId;
    if ($status !== '' && $status !== null) $params['status'] = $status;
    $params['page'] = max(1, $targetPage);
    return '/athletes?' . http_build_query($params);
};

ob_start();
?>

<!-- Page Header -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title"><?= $isCoach ? 'My Squad Athletes' : 'Athletes' ?></h1>
    </div>
    <div class="ks-header-actions">
        <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="ks-btn ks-btn-secondary" id="btnExportAthletes" title="Export filtered athletes to Excel (.xlsx)">
            <i class="bi bi-file-earmark-spreadsheet"></i>
            <span>Export Report</span>
        </a>
        <?php if ($canCreateAthlete): ?>
        <a href="/athletes/create" class="ks-btn ks-btn-primary" id="btnAddAthlete">
            <i class="bi bi-plus-lg"></i>
            <span>Add Athlete</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Table Card & Live Filter Bar -->
<div class="ks-table-card">
    <div class="ks-table-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-lines-fill" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0" id="registeredAthletesCount">Registered Athletes (<?= (int)$totalAthletes ?>)</span>
        </div>
        
        <form action="/athletes" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0" id="athletesFilterForm">
            <div class="position-relative" style="width: 250px;">
                <i class="bi bi-search position-absolute" style="left: 12px; top: 11px; color: var(--ks-text-muted); font-size: 13px;"></i>
                <input type="text" name="search" id="athleteSearchInput" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" class="ks-form-control" style="padding-left: 34px; height: 38px; font-size: 13px;" placeholder="Search name, ID, email, phone...">
            </div>
            
            <select name="sport_id" id="athleteSportFilter" class="ks-form-select" style="width: 150px; height: 38px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Sports</option>
                <?php foreach ($sportsList as $sp): ?>
                    <option value="<?= (int)$sp['id'] ?>" <?= $sportId == $sp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            
            <select name="status" id="athleteStatusFilter" class="ks-form-select" style="width: 135px; height: 38px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                <option value="injured" <?= $status === 'injured' ? 'selected' : '' ?>>Injured</option>
                <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            </select>

            <button type="submit" class="btn btn-sm btn-outline-primary" style="height: 38px; padding: 0 12px; font-size: 13px; font-weight: 500;" title="Apply Search">
                Filter
            </button>

            <?php if ($search !== '' || !empty($sportId) || ($status !== '' && $status !== null)): ?>
                <a href="/athletes" class="btn btn-sm btn-outline-secondary" style="height: 38px; display: flex; align-items: center; padding: 0 12px; font-size: 13px;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="ks-table ks-table-athletes" id="athletesTable">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Athlete Name</th>
                    <th>Reg ID</th>
                    <th>Sport</th>
                    <th>Assigned Team</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($athletes)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-people d-block fs-1 mb-2" style="color: var(--ks-text-muted);"></i>
                            <div class="fw-semibold text-navy fs-5">No athletes found</div>
                            <div class="small mt-1 mb-3">No registered athletes matched the filter criteria in this academy.</div>
                            <?php if ($canCreateAthlete): ?>
                            <a href="/athletes/create" class="ks-btn ks-btn-primary d-inline-flex">
                                <i class="bi bi-plus-lg"></i>
                                <span>Add Athlete</span>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $idx = ($page - 1) * $perPage + 1; foreach ($athletes as $ath): ?>
                        <?php
                        $athFullName = trim(($ath['first_name'] ?? '') . ' ' . ($ath['last_name'] ?? ''));
                        $activeTeams = $ath['teams'] ?? [];
                        $primaryTeamName = $activeTeams[0]['name'] ?? ($ath['team_name'] ?? null);
                        $extraTeamsCount = (int)($ath['extra_teams_count'] ?? max(0, count($activeTeams) - 1));
                        $allTeamsTooltip = $ath['all_teams_label'] ?? ($primaryTeamName ?: 'Unassigned');
                        $genderDisplay = (!empty($ath['gender']) && $ath['gender'] !== 'not_specified')
                            ? ucfirst(str_replace('_', ' ', (string)$ath['gender']))
                            : 'Not specified';
                        ?>
                        <tr data-athlete-id="<?= (int)$ath['id'] ?>">
                            <td><?= $idx++ ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="ks-user-avatar" style="width: 36px; height: 36px; font-size: 12px; background: #E8F2FF; color: var(--ks-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">
                                        <?= strtoupper(substr(trim($ath['first_name'] ?? 'A'), 0, 1) . substr(trim($ath['last_name'] ?? 'A'), 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-navy">
                                            <a href="/athletes/<?= (int)$ath['id'] ?>" class="text-navy text-decoration-none hover-primary">
                                                <?= htmlspecialchars($athFullName, ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </div>
                                        <div class="small text-muted"><?= htmlspecialchars($genderDisplay, ENT_QUOTES, 'UTF-8') ?> • DOB: <?= !empty($ath['date_of_birth']) ? date('d M Y', strtotime($ath['date_of_birth'])) : 'Not provided' ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-medium text-navy"><?= htmlspecialchars($ath['athlete_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <span class="ks-badge ks-badge-blue"><?= htmlspecialchars($ath['sport_name'] ?? 'General Sports', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <?php if (!empty($primaryTeamName)): ?>
                                    <div class="d-flex align-items-center flex-wrap gap-1" title="<?= htmlspecialchars($allTeamsTooltip, ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="fw-medium text-dark"><?= htmlspecialchars($primaryTeamName, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($extraTeamsCount > 0): ?>
                                            <span class="badge rounded-pill" style="background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; font-size: 11px; font-weight: 600;">
                                                +<?= $extraTeamsCount ?> <?= $extraTeamsCount === 1 ? 'team' : 'teams' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?= !empty($ath['phone']) ? htmlspecialchars($ath['phone'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted small">Not provided</span>' ?></div>
                                <?php if (!empty($ath['email'])): ?>
                                    <div class="small text-muted"><?= htmlspecialchars($ath['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $aStatus = $ath['status'] ?? 'active'; ?>
                                <?php if ($canEditAthlete): ?>
                                <form action="/athletes/<?= (int)$ath['id'] ?>/status" method="POST" class="d-inline m-0 p-0">
                                    <input type="hidden" name="status" value="<?= $aStatus === 'active' ? 'inactive' : 'active' ?>">
                                    <input type="hidden" name="return_search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="return_sport_id" value="<?= (int)($sportId ?? 0) ?>">
                                    <input type="hidden" name="return_status" value="<?= htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="return_page" value="<?= (int)$page ?>">
                                    <button type="submit" class="ks-badge ks-badge-<?= $aStatus === 'active' ? 'confirmed' : 'pending' ?> text-capitalize" style="cursor: pointer; border: 1px solid <?= $aStatus === 'active' ? '#BBF7D0' : '#FED7AA' ?>; background-color: <?= $aStatus === 'active' ? '#DCFCE7' : '#FFEDD5' ?>; color: <?= $aStatus === 'active' ? '#166534' : '#9A3412' ?>; padding: 0 10px; font-family: inherit;" title="Click to toggle status to <?= $aStatus === 'active' ? 'Inactive' : 'Active' ?>">
                                        <?= htmlspecialchars($aStatus, ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="ks-badge ks-badge-<?= $aStatus === 'active' ? 'confirmed' : 'pending' ?> text-capitalize" style="padding: 4px 10px;">
                                    <?= htmlspecialchars($aStatus, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <a href="/athletes/<?= (int)$ath['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View Profile" style="padding: 4px 8px; font-size: 12px;">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <?php if ($canEditAthlete): ?>
                                    <a href="/athletes/<?= (int)$ath['id'] ?>/edit" class="btn btn-sm btn-outline-primary" title="Edit Athlete" style="padding: 4px 8px; font-size: 12px;">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($canDeleteAthlete): ?>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger js-archive-athlete-btn"
                                        title="Archive Athlete"
                                        style="padding: 4px 8px; font-size: 12px;"
                                        data-athlete-id="<?= (int)$ath['id'] ?>"
                                        data-athlete-name="<?= htmlspecialchars($athFullName, ENT_QUOTES, 'UTF-8') ?>"
                                        data-athlete-code="<?= htmlspecialchars($ath['athlete_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
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
    <?php if ($totalAthletes > 0): ?>
        <div class="p-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2" id="athletesPaginationFooter">
            <div class="small text-muted" id="athletesPaginationSummary">
                Showing <strong><?= (int)$fromCount ?>–<?= (int)$toCount ?></strong> of <strong><?= (int)$totalAthletes ?></strong> <?= $totalAthletes === 1 ? 'athlete' : 'athletes' ?>
            </div>
            <nav aria-label="Athletes pagination">
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

<?php if ($canDeleteAthlete): ?>
<!-- Archive / Soft-Delete Athlete Confirmation Modal -->
<div class="modal fade" id="archiveAthleteModal" tabindex="-1" aria-labelledby="archiveAthleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content" style="border-radius: 14px; border: 1px solid #E2E8F0; box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.15);">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #FEF2F2; color: #DC2626; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="bi bi-archive-fill"></i>
                    </div>
                    <h5 class="modal-title fw-bold mb-0" id="archiveAthleteModalLabel" style="color: #0F172A; font-size: 17px;">Archive Athlete?</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="archiveAthleteForm" method="POST" action="">
                <input type="hidden" name="return_search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="return_sport_id" value="<?= (int)($sportId ?? 0) ?>">
                <input type="hidden" name="return_status" value="<?= htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="return_page" value="<?= (int)$page ?>">
                <div class="modal-body px-4 py-3">
                    <p class="mb-2" style="color: #1E293B; font-size: 14px;">
                        Are you sure you want to archive <strong id="archiveAthleteNameDisplay">this athlete</strong>
                        <span id="archiveAthleteCodeDisplay" class="text-muted small"></span>?
                    </p>
                    <p class="text-muted small mb-0" style="line-height: 1.5;">
                        The athlete will be removed from active lists, but their historical records (team assignments, documents, medical records, and attendance history) will be safely preserved.
                    </p>
                </div>
                <div class="modal-footer border-top-0 pt-1 pb-4 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500; border-radius: 8px; border: 1px solid #CBD5E1;">Cancel</button>
                    <button type="submit" class="btn btn-danger px-3" id="confirmArchiveAthleteBtn" style="font-size: 13px; font-weight: 600; border-radius: 8px;">
                        <i class="bi bi-archive me-1"></i> Archive Athlete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('archiveAthleteModal');
    const formEl = document.getElementById('archiveAthleteForm');
    const nameEl = document.getElementById('archiveAthleteNameDisplay');
    const codeEl = document.getElementById('archiveAthleteCodeDisplay');

    if (modalEl && formEl) {
        let bsModal = null;
        document.querySelectorAll('.js-archive-athlete-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const athId = this.getAttribute('data-athlete-id');
                const athName = this.getAttribute('data-athlete-name') || 'this athlete';
                const athCode = this.getAttribute('data-athlete-code') || '';

                formEl.setAttribute('action', '/athletes/' + encodeURIComponent(athId) + '/delete');
                if (nameEl) nameEl.textContent = athName;
                if (codeEl) codeEl.textContent = athCode ? '(' + athCode + ')' : '';

                if (window.bootstrap && window.bootstrap.Modal) {
                    bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                } else {
                    if (confirm('Are you sure you want to archive ' + athName + '? The athlete will be removed from active lists but their historical records will be preserved.')) {
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
