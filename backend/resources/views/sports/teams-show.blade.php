<?php
$teamId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();
$teamService = new \App\Services\Team\TeamService();
$team = $teamService->getTeam($orgId, $teamId);

$pageTitle = $team ? htmlspecialchars($team['name'] . ' — Team Details') : 'Team Details';
$activePage = 'teams';

ob_start();
?>

<div class="ks-content">
    <?php if (!$team): ?>
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

            <div class="d-flex gap-2">
                <a href="/teams/<?= (int)$team['id'] ?>/edit" class="btn btn-primary d-inline-flex align-items-center gap-2" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 8px 18px;">
                    <i class="bi bi-pencil-square"></i> Edit Team
                </a>
            </div>
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
                                                <?php if (!empty($cch['is_primary'])): ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Head Coach</span>
                                                <?php else: ?>
                                                    <span class="text-muted small"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $cch['coach_role'] ?? 'assistant')), ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted small"><?= htmlspecialchars($cch['specialization'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars($cch['phone'] ?: ($cch['email'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No coaches currently assigned to this squad.</p>
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
                                            <td class="text-muted small"><?= htmlspecialchars($ath['position'] ?: ucfirst($ath['member_role'] ?? 'player'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars(ucfirst($ath['gender'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <span class="badge <?= ($ath['athlete_status'] ?? 'active') === 'active' ? 'badge-success' : 'badge-secondary' ?>" style="font-size: 10px;">
                                                    <?= htmlspecialchars($ath['athlete_status'] ?? 'active', ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="/athletes/<?= (int)$ath['athlete_id'] ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 11px;">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No active athletes in this squad roster.</p>
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
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
