<?php
$activePage = 'tournaments';
$title = 'Tournaments — KhelSutra';
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

$canViewTournaments = $isCoach || $isAthlete
    || $permissionService->hasPermission($userPayload, 'tournament.view', $orgId)
    || $permissionService->hasPermission($userPayload, 'tournament.manage', $orgId);

$canCreateTournament = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'tournament.create', $orgId) ||
    $permissionService->hasPermission($userPayload, 'tournament.manage', $orgId)
);

$canEditTournament = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'tournament.update', $orgId) ||
    $permissionService->hasPermission($userPayload, 'tournament.manage', $orgId)
);

$canDeleteTournament = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'tournament.manage', $orgId)
);

if (!$canViewTournaments) {
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo '<div class="p-5 text-center text-danger fw-bold">403 Forbidden: You do not have permission to view tournaments.</div>';
    return;
}

$tournService = new \App\Services\Tournament\TournamentService();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$search = trim($_GET['search'] ?? '');
$sportId = !empty($_GET['sport_id']) ? (int)$_GET['sport_id'] : null;
$status = trim($_GET['status'] ?? '');

$result = $tournService->listTournaments(
    $orgId,
    $page,
    $perPage,
    $search !== '' ? $search : null,
    $status !== '' ? $status : null,
    $sportId
);

$tournaments = $result['data'] ?? [];
$totalTournaments = (int)($result['total'] ?? 0);
$page = (int)($result['page'] ?? $page);
$totalPages = max(1, (int)($result['total_pages'] ?? 1));
$fromCount = (int)($result['from'] ?? ($totalTournaments > 0 ? (($page - 1) * $perPage + 1) : 0));
$toCount = (int)($result['to'] ?? ($totalTournaments > 0 ? min($totalTournaments, ($page - 1) * $perPage + count($tournaments)) : 0));

$sportService = new \App\Services\Sport\SportService();
$sports = [];
foreach (config('sports.catalog') ?? [] as $key => $name) {
    $dbId = $sportService->resolveSportId($key);
    $sports[] = ['id' => $dbId, 'name' => $name];
}
usort($sports, fn($a, $b) => strcmp($a['name'], $b['name']));

$buildPageUrl = function (int $targetPage) use ($search, $sportId, $status): string {
    $params = [];
    if ($search !== '') $params['search'] = $search;
    if (!empty($sportId)) $params['sport_id'] = $sportId;
    if ($status !== '') $params['status'] = $status;
    $params['page'] = max(1, $targetPage);
    return '/tournaments?' . http_build_query($params);
};

ob_start();
?>

<!-- Page Header -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Tournaments</h1>
    </div>
    <?php if ($canCreateTournament): ?>
    <div class="ks-header-actions">
        <a href="/tournaments/create" class="ks-btn ks-btn-primary" id="btnAddTournament">
            <i class="bi bi-plus-lg"></i>
            <span>Add Tournament</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Table Card & Live Filter Bar -->
<div class="ks-table-card">
    <div class="ks-table-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-trophy-fill" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0" id="tournamentsCount">Tournaments (<?= (int)$totalTournaments ?>)</span>
        </div>

        <form action="/tournaments" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0" id="tournamentsFilterForm">
            <div class="position-relative" style="width: 260px;">
                <i class="bi bi-search position-absolute" style="left: 12px; top: 11px; color: var(--ks-text-muted); font-size: 13px;"></i>
                <input type="text" name="search" id="tournamentSearchInput" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" class="ks-form-control" style="padding-left: 34px; height: 38px; font-size: 13px;" placeholder="Search tournament, code, organizer...">
            </div>

            <select name="sport_id" id="tournamentSportFilter" class="ks-form-select" style="width: 175px; height: 38px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Sports</option>
                <?php foreach ($sports as $sp): ?>
                    <option value="<?= (int)$sp['id'] ?>" <?= $sportId == $sp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status" id="tournamentStatusFilter" class="ks-form-select" style="width: 165px; height: 38px; font-size: 13px;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="registration_open" <?= $status === 'registration_open' ? 'selected' : '' ?>>Registration Open</option>
                <option value="registration_closed" <?= $status === 'registration_closed' ? 'selected' : '' ?>>Registration Closed</option>
                <option value="ongoing" <?= $status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>

            <button type="submit" class="btn btn-sm btn-outline-primary" style="height: 38px; padding: 0 12px; font-size: 13px; font-weight: 500;" title="Apply Filters">
                Filter
            </button>

            <?php if ($search !== '' || !empty($sportId) || $status !== ''): ?>
                <a href="/tournaments" class="btn btn-sm btn-outline-secondary" style="height: 38px; display: flex; align-items: center; padding: 0 12px; font-size: 13px;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <style>
        #tournamentsTable.ks-table-tournaments th:nth-child(1),
        #tournamentsTable.ks-table-tournaments td:nth-child(1) {
            width: 4% !important;
            min-width: 40px !important;
            max-width: 48px !important;
            padding-right: 8px !important;
        }
        #tournamentsTable.ks-table-tournaments th:nth-child(2),
        #tournamentsTable.ks-table-tournaments td:nth-child(2) {
            width: 24% !important;
            min-width: 210px !important;
        }
        #tournamentsTable.ks-table-tournaments th:nth-child(3),
        #tournamentsTable.ks-table-tournaments td:nth-child(3) {
            width: 15% !important;
            min-width: 130px !important;
        }
        #tournamentsTable.ks-table-tournaments th:nth-child(4),
        #tournamentsTable.ks-table-tournaments td:nth-child(4) {
            width: 15% !important;
            min-width: 130px !important;
        }
        #tournamentsTable.ks-table-tournaments th:nth-child(5),
        #tournamentsTable.ks-table-tournaments td:nth-child(5) {
            width: 16% !important;
            min-width: 140px !important;
        }
        #tournamentsTable.ks-table-tournaments th:nth-child(6),
        #tournamentsTable.ks-table-tournaments td:nth-child(6) {
            width: 10% !important;
            min-width: 95px !important;
        }
        #tournamentsTable.ks-table-tournaments th:nth-child(7),
        #tournamentsTable.ks-table-tournaments td:nth-child(7) {
            width: 8% !important;
            min-width: 85px !important;
        }
        #tournamentsTable.ks-table-tournaments th:nth-child(8),
        #tournamentsTable.ks-table-tournaments td:nth-child(8) {
            width: 8% !important;
            min-width: 85px !important;
            text-align: right !important;
        }
    </style>

    <div class="table-responsive">
        <table class="ks-table ks-table-tournaments" id="tournamentsTable">
            <thead>
                <tr>
                    <th style="width: 44px;">#</th>
                    <th>Tournament</th>
                    <th>Sport &amp; Format</th>
                    <th>Dates</th>
                    <th>Venue / Location</th>
                    <th>Teams</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tournaments)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-trophy d-block fs-1 mb-2" style="color: var(--ks-text-muted);"></i>
                            <div class="fw-semibold text-navy fs-5">No tournaments found</div>
                            <div class="small mt-1 mb-3">
                                <?= ($search !== '' || !empty($sportId) || $status !== '')
                                    ? 'No tournaments match the current filters.'
                                    : 'No tournaments registered in this academy yet.' ?>
                            </div>
                            <?php if ($canCreateTournament && $search === '' && empty($sportId) && $status === ''): ?>
                            <a href="/tournaments/create" class="ks-btn ks-btn-primary d-inline-flex">
                                <i class="bi bi-plus-lg"></i>
                                <span>Add Tournament</span>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $idx = ($page - 1) * $perPage + 1; foreach ($tournaments as $tourn): ?>
                        <?php
                        $tId = (int)($tourn['id'] ?? 0);
                        $tName = trim((string)($tourn['name'] ?? 'Tournament'));
                        $tRef = trim((string)($tourn['tournament_reference'] ?? ''));
                        $sportName = !empty($tourn['sport_name']) ? (string)$tourn['sport_name'] : 'General';
                        $levelName = !empty($tourn['level_name']) ? (string)$tourn['level_name'] : 'State';
                        $formatName = !empty($tourn['format_name']) ? (string)$tourn['format_name'] : 'Knockout';
                        $venueLabel = !empty($tourn['primary_venue_name']) ? (string)$tourn['primary_venue_name'] : (!empty($tourn['location_name']) ? (string)$tourn['location_name'] : 'TBD');
                        $cityState = trim(($tourn['city'] ?? '') . (!empty($tourn['state']) ? ', ' . $tourn['state'] : ''));
                        $extraVenuesCount = (int)($tourn['extra_venues_count'] ?? 0);
                        $allVenuesTooltip = $tourn['all_venues_label'] ?? $venueLabel;
                        $teamsCount = (int)($tourn['enrolled_teams_count'] ?? 0);
                        $tStatus = strtolower((string)($tourn['status'] ?? 'draft'));

                        $statusBadgeClass = match ($tStatus) {
                            'ongoing' => 'ks-badge-confirmed',
                            'registration_open' => 'ks-badge-scheduled',
                            'completed' => 'ks-badge-completed',
                            'cancelled' => 'ks-badge-rejected',
                            default => 'ks-badge-pending',
                        };
                        ?>
                        <tr data-tournament-id="<?= $tId ?>">
                            <td><?= $idx++ ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="ks-user-avatar" style="width: 36px; height: 36px; font-size: 15px; background: #FEF3C7; color: #D97706; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="bi bi-trophy-fill"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-navy">
                                            <a href="/tournaments/<?= $tId ?>" class="text-navy text-decoration-none hover-primary">
                                                <?= htmlspecialchars($tName, ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </div>
                                        <div class="small text-muted"><?= htmlspecialchars($tRef, ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark"><?= htmlspecialchars($sportName, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($formatName . ' • ' . $levelName, ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">
                                    <?= !empty($tourn['start_date']) ? date('d M Y', strtotime($tourn['start_date'])) : '—' ?>
                                </div>
                                <div class="small text-muted">
                                    to <?= !empty($tourn['end_date']) ? date('d M Y', strtotime($tourn['end_date'])) : '—' ?>
                                </div>
                            </td>
                            <td style="max-width: 210px;">
                                <div class="d-flex align-items-center flex-wrap gap-1" title="<?= htmlspecialchars((string)$allVenuesTooltip, ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="fw-medium text-dark text-truncate" style="max-width: 155px;">
                                        <?= htmlspecialchars($venueLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if ($extraVenuesCount > 0): ?>
                                        <span class="badge rounded-pill" style="background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; font-size: 10.5px; font-weight: 600;">
                                            +<?= $extraVenuesCount ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($cityState !== ''): ?>
                                    <div class="small text-muted text-truncate"><?= htmlspecialchars($cityState, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge rounded-pill" style="background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; font-size: 11.5px; font-weight: 600; padding: 4px 10px;">
                                    <?= $teamsCount ?> <?= $teamsCount === 1 ? 'Team' : 'Teams' ?>
                                </span>
                            </td>
                            <td>
                                <span class="ks-badge <?= $statusBadgeClass ?> text-capitalize" style="padding: 4px 10px;">
                                    <?= htmlspecialchars(str_replace('_', ' ', $tStatus), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <a href="/tournaments/<?= $tId ?>" class="btn btn-sm btn-outline-secondary" title="View Tournament" style="padding: 4px 8px; font-size: 12px;">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <?php if ($canEditTournament): ?>
                                    <a href="/tournaments/<?= $tId ?>/edit" class="btn btn-sm btn-outline-primary" title="Edit Tournament" style="padding: 4px 8px; font-size: 12px;">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($canDeleteTournament): ?>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger js-archive-tournament-btn"
                                        title="Archive Tournament"
                                        style="padding: 4px 8px; font-size: 12px;"
                                        data-tournament-id="<?= $tId ?>"
                                        data-tournament-name="<?= htmlspecialchars($tName, ENT_QUOTES, 'UTF-8') ?>"
                                        data-tournament-ref="<?= htmlspecialchars($tRef, ENT_QUOTES, 'UTF-8') ?>"
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
    <?php if ($totalTournaments > 0): ?>
        <div class="p-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2" id="tournamentsPaginationFooter">
            <div class="small text-muted" id="tournamentsPaginationSummary">
                Showing <strong><?= (int)$fromCount ?>–<?= (int)$toCount ?></strong> of <strong><?= (int)$totalTournaments ?></strong> <?= $totalTournaments === 1 ? 'tournament' : 'tournaments' ?>
            </div>
            <nav aria-label="Tournaments pagination">
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

<?php if ($canDeleteTournament): ?>
<!-- Archive / Soft-Delete Tournament Confirmation Modal -->
<div class="modal fade" id="archiveTournamentModal" tabindex="-1" aria-labelledby="archiveTournamentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content" style="border-radius: 14px; border: 1px solid #E2E8F0; box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.15);">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #FEF2F2; color: #DC2626; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="bi bi-archive-fill"></i>
                    </div>
                    <h5 class="modal-title fw-bold mb-0" id="archiveTournamentModalLabel" style="color: #0F172A; font-size: 17px;">Archive Tournament?</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="archiveTournamentForm" method="POST" action="">
                <input type="hidden" name="return_search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="return_sport_id" value="<?= htmlspecialchars((string)($sportId ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="return_status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="return_page" value="<?= (int)$page ?>">
                <div class="modal-body px-4 py-3">
                    <p class="mb-2" style="color: #1E293B; font-size: 14px;">
                        Are you sure you want to archive <strong id="archiveTournamentNameDisplay">this tournament</strong>
                        <span id="archiveTournamentRefDisplay" class="text-muted small"></span>?
                    </p>
                    <p class="text-muted small mb-0" style="line-height: 1.5;">
                        The tournament will be removed from active lists while historical fixtures, match results, and standings are preserved.
                    </p>
                </div>
                <div class="modal-footer border-top-0 pt-1 pb-4 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal" style="font-size: 13px; font-weight: 500; border-radius: 8px; border: 1px solid #CBD5E1;">Cancel</button>
                    <button type="submit" class="btn btn-danger px-3" id="confirmArchiveTournamentBtn" style="font-size: 13px; font-weight: 600; border-radius: 8px;">
                        <i class="bi bi-archive me-1"></i> Archive Tournament
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('archiveTournamentModal');
    const formEl = document.getElementById('archiveTournamentForm');
    const nameEl = document.getElementById('archiveTournamentNameDisplay');
    const refEl = document.getElementById('archiveTournamentRefDisplay');

    if (modalEl && formEl) {
        let bsModal = null;
        document.querySelectorAll('.js-archive-tournament-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const tId = this.getAttribute('data-tournament-id');
                const tName = this.getAttribute('data-tournament-name') || 'this tournament';
                const tRef = this.getAttribute('data-tournament-ref') || '';

                formEl.setAttribute('action', '/tournaments/' + encodeURIComponent(tId) + '/delete');
                if (nameEl) nameEl.textContent = tName;
                if (refEl) refEl.textContent = tRef ? '(' + tRef + ')' : '';

                if (window.bootstrap && window.bootstrap.Modal) {
                    bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                } else {
                    if (confirm('Are you sure you want to archive ' + tName + '?')) {
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
