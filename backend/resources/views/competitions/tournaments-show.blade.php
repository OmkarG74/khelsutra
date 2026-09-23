<?php
$tournId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();
$tournService = new \App\Services\Tournament\TournamentService();
$tournament = $tournService->getTournament($orgId, $tournId);

$db = \App\Services\BaseService::getDatabaseConnection();
// Teams for adding fixture
$teamStmt = $db->prepare("SELECT id, name FROM teams WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
$teamStmt->execute([':org_id' => $orgId]);
$allTeams = $teamStmt ? $teamStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Eligible venues via service (no raw database queries in Blade)
$eligibleVenues = $tournament ? $tournService->getEligibleVenues($orgId, $tournId) : [];

// Active participating teams (excluding withdrawn)
$activeEnrolledTeams = array_filter($tournament['participating_teams'] ?? [], function($pt) {
    return ($pt['status'] ?? '') !== 'withdrawn';
});

// RBAC check for tournament management visibility
$permissionService = new \App\Services\Rbac\PermissionService();
$currentUserId = current_user_id() ?? 0;
$userPermissions = $currentUserId > 0 ? $permissionService->getUserPermissions($currentUserId, $orgId) : [];
$userPayload = array_merge(
    $_SESSION['auth'] ?? [],
    $_SESSION['auth']['user'] ?? ($currentUser ?? []),
    ['id' => $currentUserId, 'permissions' => $userPermissions]
);
$canManageTournaments = $permissionService->hasPermission($userPayload, 'tournament.manage', $orgId)
                     || $permissionService->hasPermission($userPayload, 'tournament.update', $orgId)
                     || in_array('tournament.manage', $userPermissions, true)
                     || in_array('tournament.update', $userPermissions, true);

$pageTitle = $tournament ? htmlspecialchars($tournament['name'] . ' — Tournament Details') : 'Tournament Details';
$activePage = 'tournaments';

ob_start();
?>

<div class="ks-content">
    <?php if (!$tournament): ?>
        <div class="card p-5 text-center" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <div class="mb-3"><i class="bi bi-trophy-fill fs-1 text-muted"></i></div>
            <h4 class="fw-bold" style="color: var(--ks-navy);">Tournament Not Found</h4>
            <p class="text-muted small">The requested tournament championship does not exist or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/tournaments" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Tournaments
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="/tournaments" class="text-decoration-none text-muted small fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Tournaments
            </a>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="h4 fw-bold mb-0" style="color: var(--ks-navy);"><?= htmlspecialchars($tournament['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php
                            $badge = match($tournament['status'] ?? 'draft') {
                                'ongoing' => 'badge-success',
                                'registration_open' => 'badge-primary',
                                'completed' => 'badge-secondary',
                                default => 'badge-warning'
                            };
                        ?>
                        <span class="badge <?= $badge ?>" style="border-radius: 12px; font-size: 11px; padding: 4px 10px; text-transform: capitalize;">
                            <?= htmlspecialchars(str_replace('_', ' ', $tournament['status'] ?? 'draft'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="text-muted small mt-1">
                        Ref: <strong style="color: var(--ks-text);"><?= htmlspecialchars($tournament['tournament_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull;
                        Sport: <strong style="color: var(--ks-text);"><?= htmlspecialchars($tournament['sport_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></strong> &bull;
                        Level: <strong style="color: var(--ks-text);"><?= htmlspecialchars($tournament['level_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull;
                        Format: <strong style="color: var(--ks-text);"><?= htmlspecialchars($tournament['format_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="/tournaments/<?= (int)$tournament['id'] ?>/edit" class="btn btn-primary d-inline-flex align-items-center gap-2" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 8px 18px;">
                    <i class="bi bi-pencil-square"></i> Edit Tournament
                </a>
            </div>
        </div>

        <div class="row g-3">
            <!-- Left Column: Fixtures, Matches & Standings -->
            <div class="col-lg-8">
                <!-- Fixtures & Matches Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h5 class="fw-bold mb-0" style="color: var(--ks-navy); font-size: 15px;">
                            <i class="bi bi-calendar-event me-2" style="color: var(--ks-blue);"></i> Fixtures & Matches (<?= count($tournament['fixtures'] ?? []) ?>)
                        </h5>
                        <?php if ($canManageTournaments && 
                                   !in_array($tournament['status'] ?? '', ['completed', 'cancelled'], true) && 
                                   in_array($tournament['format_name'] ?? '', ['League', 'Round Robin', 'Knockout'], true) && 
                                   empty($tournament['fixtures']) && 
                                   count($activeEnrolledTeams) >= 2): ?>
                            <form action="/tournaments/<?= (int)$tournament['id'] ?>/fixtures/generate" method="POST" class="d-inline m-0" onsubmit="return confirm('Generate fixtures for all participating teams?');">
                                <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" style="background: var(--ks-blue); border-color: var(--ks-blue); font-size: 12px; padding: 4px 12px; border-radius: var(--ks-radius-button);">
                                    <i class="bi bi-magic"></i> Generate Fixtures
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($tournament['fixtures']) && count($tournament['fixtures']) > 0): ?>
                        <div class="d-flex flex-column gap-3 mb-4">
                            <?php foreach ($tournament['fixtures'] as $fix): ?>
                                <div class="p-3 border rounded" style="background: #fff;">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="badge bg-light text-secondary border small"><?= htmlspecialchars($fix['round_name'] ?: 'Match', ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($fix['fixture_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="text-muted small">
                                            <i class="bi bi-calendar3 me-1"></i> <?= date('M d, Y', strtotime($fix['scheduled_date'])) ?> &bull; <?= htmlspecialchars(substr($fix['scheduled_start_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>

                                    <?php if (($fix['match_status'] ?? '') === 'completed'): ?>
                                        <div class="row align-items-center py-2">
                                            <div class="col-5 text-end">
                                                <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($fix['home_team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                <span class="text-muted small">Home</span>
                                            </div>
                                            <div class="col-2 text-center">
                                                <div class="fw-bold fs-5 text-primary">
                                                    <?= (int)$fix['home_score'] ?> - <?= (int)$fix['away_score'] ?>
                                                </div>
                                                <span class="badge bg-success-subtle text-success small" style="font-size: 10px;">Final</span>
                                            </div>
                                            <div class="col-5 text-start">
                                                <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($fix['away_team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                <span class="text-muted small">Away</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Active Score Entry Row: Clean Horizontal Match Control -->
                                        <form action="/tournaments/<?= (int)$tournament['id'] ?>/matches/<?= (int)$fix['id'] ?>/result" method="POST" class="py-2">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 gap-md-3">
                                                <div class="d-flex align-items-center justify-content-end text-end flex-grow-1" style="min-width: 110px;">
                                                    <div>
                                                        <div class="fw-bold fs-6 text-dark text-truncate" style="max-width: 170px;"><?= htmlspecialchars($fix['home_team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                        <span class="text-muted small">Home</span>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-center gap-2 flex-shrink-0">
                                                    <input type="number" name="home_score" class="form-control text-center fw-semibold ks-score-input" placeholder="0" min="0" required
                                                           style="width: 60px; height: 38px; font-size: 15px; padding: 4px 6px; border-radius: var(--ks-radius-input, 6px); border-color: var(--ks-border);">
                                                    <span class="text-muted fw-bold fs-5 px-1" style="line-height: 1; user-select: none;">&ndash;</span>
                                                    <input type="number" name="away_score" class="form-control text-center fw-semibold ks-score-input" placeholder="0" min="0" required
                                                           style="width: 60px; height: 38px; font-size: 15px; padding: 4px 6px; border-radius: var(--ks-radius-input, 6px); border-color: var(--ks-border);">
                                                </div>
                                                <div class="d-flex align-items-center justify-content-start text-start flex-grow-1" style="min-width: 110px;">
                                                    <div>
                                                        <div class="fw-bold fs-6 text-dark text-truncate" style="max-width: 170px;"><?= htmlspecialchars($fix['away_team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                        <span class="text-muted small">Away</span>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center flex-shrink-0">
                                                    <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center"
                                                            style="background: var(--ks-blue); border-color: var(--ks-blue); font-size: 13px; font-weight: 600; height: 38px; padding: 0 16px; border-radius: var(--ks-radius-button); white-space: nowrap;">
                                                        Save Score
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php endif; ?>

                                    <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-2">
                                        <span class="text-muted small">
                                            <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($fix['venue_name'] ?? 'Main Field', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <?php if (($fix['match_status'] ?? '') === 'completed'): ?>
                                            <span class="badge bg-light text-secondary border small">Result Recorded</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary small" style="font-size: 10px;">Scheduled</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-4">No fixtures scheduled yet for this tournament.</p>
                    <?php endif; ?>

                    <!-- Add Fixture Section -->
                    <div class="card p-3" style="background: var(--ks-page-bg); border: 1px dashed var(--ks-border); border-radius: var(--ks-radius-button);">
                        <h6 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 13px;">+ Schedule New Fixture</h6>
                        <form action="/tournaments/<?= (int)$tournament['id'] ?>/fixtures/create" method="POST" class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Round</label>
                                <input type="text" name="round_name" class="form-control form-control-sm" value="Round 1" required style="font-size: 12px;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Home Team</label>
                                <select name="home_team_id" class="form-select form-select-sm" required style="font-size: 12px;">
                                    <option value="">Select Home</option>
                                    <?php foreach ($allTeams as $tm): ?>
                                        <option value="<?= (int)$tm['id'] ?>"><?= htmlspecialchars($tm['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Away Team</label>
                                <select name="away_team_id" class="form-select form-select-sm" required style="font-size: 12px;">
                                    <option value="">Select Away</option>
                                    <?php foreach ($allTeams as $tm): ?>
                                        <option value="<?= (int)$tm['id'] ?>"><?= htmlspecialchars($tm['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Venue</label>
                                <select name="venue_id" class="form-select form-select-sm" required style="font-size: 12px;">
                                    <?php if (!empty($tournament['venues']) && count($tournament['venues']) > 0): ?>
                                        <option value="">Select Venue</option>
                                        <?php foreach ($tournament['venues'] as $vn): ?>
                                            <option value="<?= (int)($vn['venue_id'] ?? $vn['id']) ?>">
                                                <?= htmlspecialchars($vn['venue_name'] ?? ($vn['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                <?= !empty($vn['is_primary']) ? ' (Primary)' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">No venues assigned yet</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Match Date</label>
                                <input type="date" name="scheduled_date" class="form-control form-control-sm" value="<?= htmlspecialchars($tournament['start_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 12px;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Start Time</label>
                                <input type="time" name="scheduled_start_time" class="form-control form-control-sm" value="15:00" required style="font-size: 12px;">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-sm btn-primary w-100" style="background: var(--ks-blue); border-color: var(--ks-blue); font-size: 12px; height: 31px;">
                                    <i class="bi bi-plus-lg me-1"></i> Add Match
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Standings Table Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-list-ol me-2" style="color: var(--ks-blue);"></i> Official Standings
                    </h5>
                    <?php if (!empty($tournament['standings']) && count($tournament['standings']) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                                <thead style="background: var(--ks-page-bg);">
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th>Squad Name</th>
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
                                    <?php $rank = 1; ?>
                                    <?php foreach ($tournament['standings'] as $row): ?>
                                        <tr>
                                            <td class="fw-bold text-muted"><?= $rank++ ?></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-center"><?= (int)$row['played'] ?></td>
                                            <td class="text-center text-success fw-medium"><?= (int)$row['won'] ?></td>
                                            <td class="text-center text-muted"><?= (int)$row['drawn'] ?></td>
                                            <td class="text-center text-danger"><?= (int)$row['lost'] ?></td>
                                            <td class="text-center"><?= (int)$row['scored'] ?></td>
                                            <td class="text-center"><?= (int)$row['conceded'] ?></td>
                                            <td class="text-center <?= (int)$row['difference'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= (int)$row['difference'] ?></td>
                                            <td class="text-center fw-bold text-primary"><?= (int)$row['points'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No standings calculated yet. Standings will populate as tournament results are recorded.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Enrolled Teams Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-people me-2" style="color: var(--ks-blue);"></i> Enrolled Squads (<?= count($tournament['participating_teams'] ?? []) ?>)
                    </h5>
                    <?php
                        $activeParticipatingTeamIds = [];
                        foreach ($tournament['participating_teams'] ?? [] as $pt) {
                            if (($pt['status'] ?? 'approved') !== 'withdrawn') {
                                $activeParticipatingTeamIds[] = (int)$pt['team_id'];
                            }
                        }
                    ?>
                    <?php if (!empty($tournament['participating_teams']) && count($tournament['participating_teams']) > 0): ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($tournament['participating_teams'] as $tm): ?>
                                <?php $isWithdrawn = ($tm['status'] ?? '') === 'withdrawn'; ?>
                                <div class="d-flex align-items-center justify-content-between p-2 border rounded" style="background: var(--ks-page-bg);">
                                    <div>
                                        <div class="fw-semibold <?= $isWithdrawn ? 'text-muted text-decoration-line-through' : 'text-dark' ?> small"><?= htmlspecialchars($tm['team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                        <span class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($tm['team_code'] ?? '', ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($tm['age_group'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($isWithdrawn): ?>
                                            <span class="badge bg-secondary-subtle text-secondary small" style="font-size: 10px;">Withdrawn</span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success small" style="font-size: 10px;">Confirmed</span>
                                            <?php if (!in_array($tournament['status'] ?? '', ['completed', 'cancelled'], true)): ?>
                                                <form action="/tournaments/<?= (int)$tournament['id'] ?>/teams/<?= (int)$tm['team_id'] ?>/remove" method="POST" class="d-inline m-0" onsubmit="return confirm('Withdraw this team from the tournament?');">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 10px; line-height: 1.4;">
                                                        Withdraw
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No teams currently enrolled in this tournament.</p>
                    <?php endif; ?>

                    <!-- Minimal Extension: Register Existing Team -->
                    <?php if (!in_array($tournament['status'] ?? '', ['completed', 'cancelled'], true)): ?>
                        <?php
                            $eligibleTeams = array_filter($allTeams, function($t) use ($activeParticipatingTeamIds) {
                                return !in_array((int)$t['id'], $activeParticipatingTeamIds, true);
                            });
                        ?>
                        <?php if (!empty($eligibleTeams)): ?>
                            <div class="mt-3 pt-3 border-top">
                                <form action="/tournaments/<?= (int)$tournament['id'] ?>/teams/add" method="POST" class="d-flex flex-column gap-2">
                                    <label class="form-label small fw-semibold text-muted mb-0" for="team_id">Register Team</label>
                                    <div class="d-flex gap-2">
                                        <select name="team_id" id="team_id" class="form-select form-select-sm" required style="font-size: 12px;">
                                            <option value="">-- Select Team --</option>
                                            <?php foreach ($eligibleTeams as $et): ?>
                                                <option value="<?= (int)$et['id'] ?>">
                                                    <?= htmlspecialchars($et['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary text-nowrap" style="background: var(--ks-blue); border-color: var(--ks-blue); font-size: 12px; padding: 4px 12px;">
                                            <i class="bi bi-plus-lg me-1"></i> Register
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Assigned Venues Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-geo-alt me-2" style="color: var(--ks-blue);"></i> Assigned Venues (<?= count($tournament['venues'] ?? []) ?>)
                    </h5>
                    <?php if (!empty($tournament['venues']) && count($tournament['venues']) > 0): ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($tournament['venues'] as $vn): ?>
                                <div class="d-flex align-items-center justify-content-between p-2 border rounded" style="background: var(--ks-page-bg);">
                                    <div>
                                        <div class="fw-semibold text-dark small"><?= htmlspecialchars($vn['venue_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                        <span class="text-muted" style="font-size: 11px;">
                                            <?= htmlspecialchars($vn['city'] ?? '', ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($vn['venue_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            <?php if (!empty($vn['is_primary'])): ?>
                                                &bull; <span class="badge bg-primary-subtle text-primary" style="font-size: 9px;">Primary</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($canManageTournaments && !in_array($tournament['status'] ?? '', ['completed', 'cancelled'], true)): ?>
                                            <form action="/tournaments/<?= (int)$tournament['id'] ?>/venues/<?= (int)($vn['venue_id'] ?? $vn['id']) ?>/remove" method="POST" class="d-inline m-0" onsubmit="return confirm('Remove this venue from the tournament?');">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 10px; line-height: 1.4;">
                                                    Remove
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No venues assigned yet.</p>
                    <?php endif; ?>

                    <!-- Minimal Extension: Assign Available Venue -->
                    <?php if ($canManageTournaments && !in_array($tournament['status'] ?? '', ['completed', 'cancelled'], true) && !empty($eligibleVenues)): ?>
                        <div class="mt-3 pt-3 border-top">
                            <form action="/tournaments/<?= (int)$tournament['id'] ?>/venues/add" method="POST" class="d-flex flex-column gap-2">
                                <label class="form-label small fw-semibold text-muted mb-0" for="venue_id">Assign Venue</label>
                                <div class="d-flex gap-2">
                                    <select name="venue_id" id="venue_id" class="form-select form-select-sm" required style="font-size: 12px;">
                                        <option value="">-- Select Venue --</option>
                                        <?php foreach ($eligibleVenues as $ev): ?>
                                            <option value="<?= (int)$ev['id'] ?>">
                                                <?= htmlspecialchars($ev['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($ev['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary text-nowrap" style="background: var(--ks-blue); border-color: var(--ks-blue); font-size: 12px; padding: 4px 12px;">
                                        <i class="bi bi-plus-lg me-1"></i> Assign
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Rules & Description Card -->
                <?php if (!empty($tournament['description']) || !empty($tournament['rules'])): ?>
                    <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                        <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                            <i class="bi bi-card-text me-2" style="color: var(--ks-blue);"></i> Rules & Guidelines
                        </h5>
                        <?php if (!empty($tournament['description'])): ?>
                            <div class="text-dark small mb-2" style="line-height: 1.5;"><?= htmlspecialchars($tournament['description'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <?php if (!empty($tournament['rules'])): ?>
                            <div class="text-muted small" style="line-height: 1.5;"><?= htmlspecialchars($tournament['rules'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
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
