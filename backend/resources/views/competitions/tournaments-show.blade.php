<?php
$tournId = (int)($id ?? ($_GET['id'] ?? 0));
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
$userPermissions = $userId > 0 ? $permissionService->getUserPermissions($userId, $orgId) : [];
$userPayload = array_merge(
    $_SESSION['auth'] ?? [],
    $currentUser,
    [
        'id' => $userId,
        'role_id' => $currentRoleId,
        'role' => $_SESSION['auth']['role'] ?? ['id' => $currentRoleId, 'slug' => $currentRoleSlug],
        'permissions' => $_SESSION['auth']['permissions'] ?? ($userPermissions ?: []),
    ]
);

$canViewTournament = $isCoach || $isAthlete
    || $permissionService->hasPermission($userPayload, 'tournament.view', $orgId)
    || $permissionService->hasPermission($userPayload, 'tournament.manage', $orgId);

$canManageTournaments = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'tournament.manage', $orgId) ||
    $permissionService->hasPermission($userPayload, 'tournament.update', $orgId) ||
    in_array('tournament.manage', $userPermissions, true) ||
    in_array('tournament.update', $userPermissions, true)
);

if (!$canViewTournament) {
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo '<div class="p-5 text-center text-danger fw-bold">403 Forbidden: You do not have permission to view this tournament.</div>';
    return;
}

$tournService = new \App\Services\Tournament\TournamentService();
$tournament = $tournService->getTournament($orgId, $tournId);

$db = \App\Services\BaseService::getDatabaseConnection();
$allTeams = [];
if ($db && $tournament) {
    $teamStmt = $db->prepare("SELECT id, name, team_code, sport_id FROM teams WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
    $teamStmt->execute([':org_id' => $orgId]);
    $allTeams = $teamStmt ? $teamStmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

$eligibleVenues = ($tournament && $canManageTournaments) ? $tournService->getEligibleVenues($orgId, $tournId) : [];

// Active participating teams (excluding withdrawn)
$activeEnrolledTeams = array_values(array_filter($tournament['participating_teams'] ?? [], function ($pt) {
    return ($pt['status'] ?? '') !== 'withdrawn';
}));

$title = $tournament ? htmlspecialchars($tournament['name'] . ' — Tournament Details') : 'Tournament Details';
$pageTitle = $title;
$activePage = 'tournaments';

ob_start();
?>

<div class="ks-page">
    <?php if (!$tournament): ?>
        <div class="ks-content-card p-5 text-center my-4">
            <div class="mb-3"><i class="bi bi-trophy fs-1 text-muted"></i></div>
            <h4 class="fw-bold text-dark">Tournament Not Found</h4>
            <p class="text-muted small">The requested tournament championship does not exist, has been archived, or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/tournaments" class="btn btn-outline-secondary" style="border-radius: 8px; font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Tournaments
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php
        $statusVal = strtolower((string)($tournament['status'] ?? 'draft'));
        $statusBadgeClass = match ($statusVal) {
            'ongoing' => 'ks-badge-confirmed',
            'registration_open' => 'ks-badge-scheduled',
            'completed' => 'ks-badge-completed',
            'cancelled' => 'ks-badge-rejected',
            default => 'ks-badge-pending',
        };
        $fixturesList = $tournament['fixtures'] ?? [];
        $standingsList = $tournament['standings'] ?? [];
        $participatingTeams = $tournament['participating_teams'] ?? [];
        $assignedVenues = $tournament['venues'] ?? [];
        $isClosedStatus = in_array($statusVal, ['completed', 'cancelled'], true);
        ?>

        <!-- Compact Tournament Header -->
        <div class="mb-3">
            <a href="/tournaments" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1 mb-2" style="font-size: 12.5px;">
                <i class="bi bi-arrow-left"></i> Back to Tournaments
            </a>

            <div class="ks-content-card p-3 px-4" style="border-radius: 12px;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 52px; height: 52px; border-radius: 12px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; border: 2px solid #FDE68A;">
                            <i class="bi bi-trophy-fill"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <h1 class="ks-page-title mb-0" style="font-size: 20px; line-height: 1.25;">
                                    <?= htmlspecialchars($tournament['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </h1>
                                <span class="ks-badge <?= $statusBadgeClass ?> text-capitalize" style="padding: 4px 10px;">
                                    <?= htmlspecialchars(str_replace('_', ' ', $statusVal), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2 mt-1 small text-muted">
                                <span>Ref: <strong class="text-dark"><?= htmlspecialchars($tournament['tournament_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></span>
                                <span>&bull;</span>
                                <span>Sport: <strong class="text-dark"><?= htmlspecialchars($tournament['sport_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></strong></span>
                                <span>&bull;</span>
                                <span>Level: <strong class="text-dark"><?= htmlspecialchars($tournament['level_name'] ?? 'State', ENT_QUOTES, 'UTF-8') ?></strong></span>
                                <span>&bull;</span>
                                <span>Format: <strong class="text-dark"><?= htmlspecialchars($tournament['format_name'] ?? 'Knockout', ENT_QUOTES, 'UTF-8') ?></strong></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($canManageTournaments): ?>
                    <div class="d-flex align-items-center gap-2">
                        <a href="/tournaments/<?= (int)$tournament['id'] ?>/edit" class="ks-btn ks-btn-primary px-3 py-2" id="btnEditTournamentHeader">
                            <i class="bi bi-pencil-square"></i>
                            <span>Edit Tournament</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start">
            <!-- Left Column: Tournament Information, Fixtures & Matches, Standings -->
            <div class="col-12 col-lg-8">
                <!-- 1. Tournament Information -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-info-circle text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Tournament Information</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <div class="row g-3">
                            <div class="col-6 col-sm-3">
                                <div class="text-muted" style="font-size: 12px;">Start Date</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($tournament['start_date']) ? date('d M Y', strtotime($tournament['start_date'])) : '—' ?>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="text-muted" style="font-size: 12px;">End Date</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= !empty($tournament['end_date']) ? date('d M Y', strtotime($tournament['end_date'])) : '—' ?>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="text-muted" style="font-size: 12px;">Location / City</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= htmlspecialchars(trim(($tournament['location_name'] ?? '') . (!empty($tournament['city']) ? ', ' . $tournament['city'] : '')) ?: 'TBD', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="text-muted" style="font-size: 12px;">Organizer</div>
                                <div class="fw-medium mt-1" style="font-size: 13.5px; color: #0F172A;">
                                    <?= htmlspecialchars($tournament['organizer_name'] ?: 'Academy', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Fixtures & Matches -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-calendar-event text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Fixtures &amp; Matches</h3>
                            <span class="badge bg-light text-dark border ms-1" style="font-size: 11.5px; font-weight: 600;">
                                <?= count($fixturesList) ?>
                            </span>
                        </div>
                        <div class="ks-header-right">
                            <?php if ($canManageTournaments &&
                                      !$isClosedStatus &&
                                      in_array($tournament['format_name'] ?? '', ['League', 'Round Robin', 'Knockout'], true) &&
                                      empty($fixturesList) &&
                                      count($activeEnrolledTeams) >= 2): ?>
                                <form action="/tournaments/<?= (int)$tournament['id'] ?>/fixtures/generate" method="POST" class="d-inline m-0" onsubmit="return confirm('Generate fixtures for all participating teams?');">
                                    <button type="submit" class="ks-btn ks-btn-primary" style="height: 32px; font-size: 12px; padding: 0 12px;">
                                        <i class="bi bi-magic"></i>
                                        <span>Generate Fixtures</span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="p-3 px-4">
                        <?php if (!empty($fixturesList)): ?>
                            <div class="d-flex flex-column gap-3 mb-3">
                                <?php foreach ($fixturesList as $fix): ?>
                                    <?php
                                    $isCompletedMatch = ($fix['match_status'] ?? '') === 'completed';
                                    ?>
                                    <div class="p-3 border rounded-3" style="background: #F8FAFC; border-color: #E2E8F0 !important;">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-white text-dark border" style="font-size: 11px; font-weight: 600;">
                                                    <?= htmlspecialchars($fix['round_name'] ?: 'Match', ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <span class="text-muted small"><?= htmlspecialchars($fix['fixture_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 small text-muted">
                                                <span><i class="bi bi-calendar3 me-1"></i><?= !empty($fix['scheduled_date']) ? date('d M Y', strtotime($fix['scheduled_date'])) : '—' ?></span>
                                                <span>&bull;</span>
                                                <span><i class="bi bi-clock me-1"></i><?= htmlspecialchars(substr($fix['scheduled_start_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?></span>
                                                <span>&bull;</span>
                                                <span><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($fix['venue_name'] ?? 'Main Field', ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        </div>

                                        <?php if ($isCompletedMatch): ?>
                                            <div class="d-flex align-items-center justify-content-between py-2 px-3 bg-white border rounded-3">
                                                <div class="text-end flex-grow-1" style="width: 40%;">
                                                    <div class="fw-bold text-navy" style="font-size: 14px;"><?= htmlspecialchars($fix['home_team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                    <span class="text-muted" style="font-size: 11px;">Home</span>
                                                </div>
                                                <div class="text-center px-3" style="min-width: 110px;">
                                                    <div class="fw-bold text-primary" style="font-size: 18px;">
                                                        <?= (int)$fix['home_score'] ?> &ndash; <?= (int)$fix['away_score'] ?>
                                                    </div>
                                                    <span class="ks-badge ks-badge-confirmed" style="font-size: 10px; padding: 2px 8px;">Completed</span>
                                                </div>
                                                <div class="text-start flex-grow-1" style="width: 40%;">
                                                    <div class="fw-bold text-navy" style="font-size: 14px;"><?= htmlspecialchars($fix['away_team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                    <span class="text-muted" style="font-size: 11px;">Away</span>
                                                </div>
                                            </div>
                                        <?php elseif ($canManageTournaments): ?>
                                            <form action="/tournaments/<?= (int)$tournament['id'] ?>/matches/<?= (int)$fix['id'] ?>/result" method="POST" class="py-2 px-3 bg-white border rounded-3">
                                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                    <div class="text-end flex-grow-1" style="min-width: 110px;">
                                                        <div class="fw-bold text-navy" style="font-size: 14px;"><?= htmlspecialchars($fix['home_team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                        <span class="text-muted" style="font-size: 11px;">Home</span>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                                        <input type="number" name="home_score" class="ks-form-control text-center fw-semibold" placeholder="0" min="0" required style="width: 60px; height: 36px; font-size: 14px;">
                                                        <span class="text-muted fw-bold">&ndash;</span>
                                                        <input type="number" name="away_score" class="ks-form-control text-center fw-semibold" placeholder="0" min="0" required style="width: 60px; height: 36px; font-size: 14px;">
                                                    </div>
                                                    <div class="text-start flex-grow-1" style="min-width: 110px;">
                                                        <div class="fw-bold text-navy" style="font-size: 14px;"><?= htmlspecialchars($fix['away_team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                        <span class="text-muted" style="font-size: 11px;">Away</span>
                                                    </div>
                                                    <div>
                                                        <button type="submit" class="ks-btn ks-btn-primary" style="height: 36px; font-size: 12.5px; padding: 0 14px;">
                                                            Save Score
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-between py-2 px-3 bg-white border rounded-3">
                                                <div class="text-end flex-grow-1" style="width: 40%;">
                                                    <div class="fw-bold text-navy" style="font-size: 14px;"><?= htmlspecialchars($fix['home_team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                    <span class="text-muted" style="font-size: 11px;">Home</span>
                                                </div>
                                                <div class="text-center px-3">
                                                    <span class="ks-badge ks-badge-scheduled" style="font-size: 11px;">VS</span>
                                                </div>
                                                <div class="text-start flex-grow-1" style="width: 40%;">
                                                    <div class="fw-bold text-navy" style="font-size: 14px;"><?= htmlspecialchars($fix['away_team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                    <span class="text-muted" style="font-size: 11px;">Away</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-calendar2-x d-block fs-3 mb-1"></i>
                                <div class="fw-medium text-dark">No fixtures available</div>
                                <div class="small">No matches have been scheduled for this tournament yet.</div>
                            </div>
                        <?php endif; ?>

                        <?php if ($canManageTournaments && !$isClosedStatus): ?>
                            <div class="mt-3 pt-3 border-top">
                                <h6 class="fw-semibold text-navy mb-2" style="font-size: 13px;">+ Schedule New Fixture</h6>
                                <form action="/tournaments/<?= (int)$tournament['id'] ?>/fixtures/create" method="POST" class="row g-2 align-items-end">
                                    <div class="col-md-3">
                                        <label class="ks-form-label mb-1">Round</label>
                                        <input type="text" name="round_name" class="ks-form-control" style="height: 36px; font-size: 12.5px;" value="Round 1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="ks-form-label mb-1">Home Team</label>
                                        <select name="home_team_id" class="ks-form-select" style="height: 36px; font-size: 12.5px;" required>
                                            <option value="">Select Home</option>
                                            <?php foreach ((!empty($activeEnrolledTeams) ? $activeEnrolledTeams : $allTeams) as $tm): ?>
                                                <?php $tmId = (int)($tm['team_id'] ?? ($tm['id'] ?? 0)); $tmLabel = $tm['team_name'] ?? ($tm['name'] ?? ''); ?>
                                                <option value="<?= $tmId ?>"><?= htmlspecialchars($tmLabel, ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="ks-form-label mb-1">Away Team</label>
                                        <select name="away_team_id" class="ks-form-select" style="height: 36px; font-size: 12.5px;" required>
                                            <option value="">Select Away</option>
                                            <?php foreach ((!empty($activeEnrolledTeams) ? $activeEnrolledTeams : $allTeams) as $tm): ?>
                                                <?php $tmId = (int)($tm['team_id'] ?? ($tm['id'] ?? 0)); $tmLabel = $tm['team_name'] ?? ($tm['name'] ?? ''); ?>
                                                <option value="<?= $tmId ?>"><?= htmlspecialchars($tmLabel, ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="ks-form-label mb-1">Venue</label>
                                        <select name="venue_id" class="ks-form-select" style="height: 36px; font-size: 12.5px;" required>
                                            <?php if (!empty($assignedVenues)): ?>
                                                <option value="">Select Venue</option>
                                                <?php foreach ($assignedVenues as $vn): ?>
                                                    <option value="<?= (int)($vn['venue_id'] ?? $vn['id']) ?>">
                                                        <?= htmlspecialchars($vn['venue_name'] ?? ($vn['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                        <?= !empty($vn['is_primary']) ? ' (Primary)' : '' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="">Assign a venue first</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="ks-form-label mb-1">Match Date</label>
                                        <input type="date" name="scheduled_date" class="ks-form-control" style="height: 36px; font-size: 12.5px;" value="<?= htmlspecialchars($tournament['start_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="ks-form-label mb-1">Start Time</label>
                                        <input type="time" name="scheduled_start_time" class="ks-form-control" style="height: 36px; font-size: 12.5px;" value="15:00" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="ks-btn ks-btn-primary w-100" style="height: 36px; font-size: 12.5px;">
                                            <i class="bi bi-plus-lg"></i>
                                            <span>Add Match</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Official Standings -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-list-ol text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Official Standings</h3>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <?php if (!empty($standingsList)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                                    <thead>
                                        <tr class="text-muted" style="font-size: 11.5px; text-transform: uppercase;">
                                            <th style="width: 40px;">#</th>
                                            <th>Team</th>
                                            <th class="text-center">P</th>
                                            <th class="text-center">W</th>
                                            <th class="text-center">D</th>
                                            <th class="text-center">L</th>
                                            <th class="text-center">GF</th>
                                            <th class="text-center">GA</th>
                                            <th class="text-center">GD</th>
                                            <th class="text-center fw-bold">PTS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $rank = 1; foreach ($standingsList as $row): ?>
                                            <tr>
                                                <td class="py-2 fw-bold text-muted"><?= $rank++ ?></td>
                                                <td class="py-2 fw-semibold text-navy">
                                                    <a href="/teams/<?= (int)$row['team_id'] ?>" class="text-navy text-decoration-none">
                                                        <?= htmlspecialchars($row['team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                                    </a>
                                                </td>
                                                <td class="py-2 text-center"><?= (int)$row['played'] ?></td>
                                                <td class="py-2 text-center text-success fw-medium"><?= (int)$row['won'] ?></td>
                                                <td class="py-2 text-center text-muted"><?= (int)$row['drawn'] ?></td>
                                                <td class="py-2 text-center text-danger"><?= (int)$row['lost'] ?></td>
                                                <td class="py-2 text-center"><?= (int)$row['scored'] ?></td>
                                                <td class="py-2 text-center"><?= (int)$row['conceded'] ?></td>
                                                <td class="py-2 text-center <?= (int)$row['difference'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= (int)$row['difference'] ?></td>
                                                <td class="py-2 text-center fw-bold text-primary"><?= (int)$row['points'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small py-1">No standings calculated yet. Standings populate automatically as match results are recorded.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Participating Teams, Assigned Venues, Rules -->
            <div class="col-12 col-lg-4">
                <!-- Participating Teams -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-people text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Participating Teams</h3>
                        </div>
                        <div class="ks-header-right">
                            <span class="badge bg-light text-dark border" style="font-size: 11.5px; font-weight: 600;">
                                <?= count($activeEnrolledTeams) ?> Active
                            </span>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <?php
                        $activeParticipatingTeamIds = [];
                        foreach ($participatingTeams as $pt) {
                            if (($pt['status'] ?? 'approved') !== 'withdrawn') {
                                $activeParticipatingTeamIds[] = (int)$pt['team_id'];
                            }
                        }
                        ?>
                        <?php if (!empty($participatingTeams)): ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($participatingTeams as $tm): ?>
                                    <?php $isWithdrawn = ($tm['status'] ?? '') === 'withdrawn'; ?>
                                    <div class="d-flex align-items-center justify-content-between p-2 px-3 border rounded-3" style="background: #F8FAFC; border-color: #E2E8F0 !important;">
                                        <div>
                                            <a href="/teams/<?= (int)$tm['team_id'] ?>" class="fw-semibold text-decoration-none <?= $isWithdrawn ? 'text-muted text-decoration-line-through' : 'text-navy' ?>" style="font-size: 13px;">
                                                <?= htmlspecialchars($tm['team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                            <div class="text-muted" style="font-size: 11px;">
                                                <?= htmlspecialchars($tm['team_code'] ?? '', ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($tm['age_group'] ?: 'Open', ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <?php if ($isWithdrawn): ?>
                                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 10px;">Withdrawn</span>
                                            <?php else: ?>
                                                <span class="ks-badge ks-badge-confirmed" style="font-size: 10px; padding: 2px 8px;">Confirmed</span>
                                                <?php if ($canManageTournaments && !$isClosedStatus): ?>
                                                    <form action="/tournaments/<?= (int)$tournament['id'] ?>/teams/<?= (int)$tm['team_id'] ?>/remove" method="POST" class="d-inline m-0" onsubmit="return confirm('Withdraw this team from the tournament?');">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" style="padding: 2px 6px; font-size: 10.5px;">Withdraw</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small py-1">No teams currently enrolled in this tournament.</div>
                        <?php endif; ?>

                        <?php if ($canManageTournaments && !$isClosedStatus): ?>
                            <?php
                            $tournSportId = (int)($tournament['sport_id'] ?? 0);
                            $eligibleTeams = array_filter($allTeams, function ($t) use ($activeParticipatingTeamIds, $tournSportId) {
                                $sameSport = $tournSportId <= 0 || (int)($t['sport_id'] ?? 0) === $tournSportId;
                                return $sameSport && !in_array((int)$t['id'], $activeParticipatingTeamIds, true);
                            });
                            ?>
                            <?php if (!empty($eligibleTeams)): ?>
                                <div class="mt-3 pt-3 border-top">
                                    <form action="/tournaments/<?= (int)$tournament['id'] ?>/teams/add" method="POST" class="d-flex flex-column gap-2">
                                        <label class="ks-form-label mb-0" for="team_id">Register Team</label>
                                        <div class="d-flex gap-2">
                                            <select name="team_id" id="team_id" class="ks-form-select" style="height: 36px; font-size: 12.5px;" required>
                                                <option value="">Select team...</option>
                                                <?php foreach ($eligibleTeams as $et): ?>
                                                    <option value="<?= (int)$et['id'] ?>">
                                                        <?= htmlspecialchars($et['name'], ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="ks-btn ks-btn-primary text-nowrap" style="height: 36px; font-size: 12.5px; padding: 0 12px;">
                                                <i class="bi bi-plus-lg"></i>
                                                <span>Register</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Assigned Venues -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header py-2 px-3">
                        <div class="ks-header-left">
                            <i class="bi bi-geo-alt text-primary fs-6"></i>
                            <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Assigned Venues</h3>
                        </div>
                        <div class="ks-header-right">
                            <span class="badge bg-light text-dark border" style="font-size: 11.5px; font-weight: 600;">
                                <?= count($assignedVenues) ?>
                            </span>
                        </div>
                    </div>
                    <div class="p-3 px-4">
                        <?php if (!empty($assignedVenues)): ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($assignedVenues as $vn): ?>
                                    <div class="d-flex align-items-center justify-content-between p-2 px-3 border rounded-3" style="background: #F8FAFC; border-color: #E2E8F0 !important;">
                                        <div>
                                            <div class="fw-semibold text-navy" style="font-size: 13px;">
                                                <?= htmlspecialchars($vn['venue_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                                <?php if (!empty($vn['is_primary'])): ?>
                                                    <span class="badge rounded-pill ms-1" style="background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; font-size: 10px;">Primary</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 11px;">
                                                <?= htmlspecialchars($vn['city'] ?? '', ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($vn['venue_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                        <?php if ($canManageTournaments && !$isClosedStatus): ?>
                                            <form action="/tournaments/<?= (int)$tournament['id'] ?>/venues/<?= (int)($vn['venue_id'] ?? $vn['id']) ?>/remove" method="POST" class="d-inline m-0" onsubmit="return confirm('Remove this venue from the tournament?');">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" style="padding: 2px 6px; font-size: 10.5px;">Remove</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small py-1">No venues assigned yet.</div>
                        <?php endif; ?>

                        <?php if ($canManageTournaments && !$isClosedStatus && !empty($eligibleVenues)): ?>
                            <div class="mt-3 pt-3 border-top">
                                <form action="/tournaments/<?= (int)$tournament['id'] ?>/venues/add" method="POST" class="d-flex flex-column gap-2">
                                    <label class="ks-form-label mb-0" for="venue_id">Assign Venue</label>
                                    <div class="d-flex gap-2">
                                        <select name="venue_id" id="venue_id" class="ks-form-select" style="height: 36px; font-size: 12.5px;" required>
                                            <option value="">Select venue...</option>
                                            <?php foreach ($eligibleVenues as $ev): ?>
                                                <option value="<?= (int)$ev['id'] ?>">
                                                    <?= htmlspecialchars($ev['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($ev['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="ks-btn ks-btn-primary text-nowrap" style="height: 36px; font-size: 12.5px; padding: 0 12px;">
                                            <i class="bi bi-plus-lg"></i>
                                            <span>Assign</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Rules & Description -->
                <?php if (!empty($tournament['description']) || !empty($tournament['rules'])): ?>
                    <div class="ks-content-card mb-3">
                        <div class="ks-card-header py-2 px-3">
                            <div class="ks-header-left">
                                <i class="bi bi-card-text text-primary fs-6"></i>
                                <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">Rules &amp; Guidelines</h3>
                            </div>
                        </div>
                        <div class="p-3 px-4">
                            <?php if (!empty($tournament['description'])): ?>
                                <div class="text-dark small mb-2" style="line-height: 1.5;"><?= htmlspecialchars($tournament['description'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <?php if (!empty($tournament['rules'])): ?>
                                <div class="text-muted small" style="line-height: 1.5;"><?= htmlspecialchars($tournament['rules'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
