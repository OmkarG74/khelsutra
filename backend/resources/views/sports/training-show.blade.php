<?php
$sessionId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();
$trainService = new \App\Services\Training\TrainingService();
$session = $trainService->getSession($orgId, $sessionId);

$pageTitle = $session ? htmlspecialchars(($session['title'] ?: ($session['training_type'] ?? 'Training Session')) . ' — Training Details') : 'Training Session';
$activePage = 'training';

ob_start();
?>

<div class="ks-content">
    <?php if (!$session): ?>
        <div class="card p-5 text-center" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <div class="mb-3"><i class="bi bi-stopwatch fs-1 text-muted"></i></div>
            <h4 class="fw-bold" style="color: var(--ks-navy);">Training Session Not Found</h4>
            <p class="text-muted small">The requested training session does not exist or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/training" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Training
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="/training" class="text-decoration-none text-muted small fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Training
            </a>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h2 class="h4 fw-bold mb-0" style="color: var(--ks-navy);">
                        <?= htmlspecialchars($session['title'] ?: ($session['training_type'] ?? 'Training Session'), ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                    <?php
                        $badge = match($session['status'] ?? 'scheduled') {
                            'completed' => 'badge-success',
                            'in_progress' => 'badge-warning',
                            'cancelled' => 'badge-danger',
                            default => 'badge-secondary'
                        };
                    ?>
                    <span class="badge <?= $badge ?>" style="border-radius: 12px; font-size: 11px; padding: 4px 10px; text-transform: uppercase;">
                        <?= htmlspecialchars(str_replace('_', ' ', $session['status'] ?? 'scheduled'), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <div class="text-muted small mt-1">
                    Ref: <strong style="color: var(--ks-text);"><?= htmlspecialchars($session['training_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull;
                    Squad: <strong style="color: var(--ks-text);"><?= htmlspecialchars($session['team_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></strong> &bull;
                    Sport: <strong style="color: var(--ks-text);"><?= htmlspecialchars($session['sport_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="/training/<?= (int)$session['id'] ?>/edit" class="btn btn-primary d-inline-flex align-items-center gap-2" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 8px 18px;">
                    <i class="bi bi-pencil-square"></i> Edit Session
                </a>
            </div>
        </div>

        <div class="row g-3">
            <!-- Left Column: Session Details & Attendance Sheet -->
            <div class="col-lg-8">
                <!-- Session Metadata Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-info-circle me-2" style="color: var(--ks-blue);"></i> Session Schedule & Venue
                    </h5>
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="text-muted small">Date</div>
                            <div class="fw-semibold text-dark mt-1"><?= date('l, M d, Y', strtotime($session['training_date'])) ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Time Slot</div>
                            <div class="fw-semibold text-dark mt-1"><?= htmlspecialchars(substr($session['start_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars(substr($session['end_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Training Type</div>
                            <div class="fw-semibold text-dark mt-1"><?= htmlspecialchars($session['training_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Assigned Coach</div>
                            <div class="fw-semibold text-dark mt-1"><?= htmlspecialchars($session['coach_name'] ?? 'Unassigned', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Venue</div>
                            <div class="fw-semibold text-dark mt-1"><?= htmlspecialchars($session['venue_name'] ?? 'Academy Grounds', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Facility Slot</div>
                            <div class="fw-semibold text-dark mt-1"><?= htmlspecialchars($session['facility_name'] ?? 'Main Field', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php if (!empty($session['objectives'])): ?>
                            <div class="col-12">
                                <div class="text-muted small">Objectives</div>
                                <div class="text-dark small mt-1" style="line-height: 1.5;"><?= htmlspecialchars($session['objectives'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Athlete Attendance Sheet -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h5 class="fw-bold mb-0" style="color: var(--ks-navy); font-size: 15px;">
                            <i class="bi bi-clipboard-check me-2" style="color: var(--ks-blue);"></i> Athlete Attendance Roll (<?= count($session['roster_attendance'] ?? []) ?>)
                        </h5>
                    </div>

                    <?php if (!empty($session['roster_attendance']) && count($session['roster_attendance']) > 0): ?>
                        <form action="/training/<?= (int)$session['id'] ?>/attendance" method="POST">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-3" style="font-size: 13px;">
                                    <thead style="background: var(--ks-page-bg);">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Athlete Name</th>
                                            <th>Code</th>
                                            <th style="width: 280px;">Attendance Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($session['roster_attendance'] as $row): ?>
                                            <tr>
                                                <td class="fw-bold text-muted"><?= $row['jersey_number'] ? '#' . htmlspecialchars($row['jersey_number'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                                <td class="fw-semibold text-dark"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-muted small" style="font-family: monospace;"><?= htmlspecialchars($row['athlete_code'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm w-100" role="group">
                                                        <?php foreach (['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'excused' => 'Excused'] as $val => $lbl): ?>
                                                            <input type="radio" class="btn-check" name="attendance[<?= (int)$row['athlete_id'] ?>]" id="att_<?= (int)$row['athlete_id'] ?>_<?= $val ?>" value="<?= $val ?>" <?= ($row['attendance_status'] ?? 'present') === $val ? 'checked' : '' ?>>
                                                            <label class="btn btn-outline-secondary py-1 px-2" style="font-size: 11px;" for="att_<?= (int)$row['athlete_id'] ?>_<?= $val ?>"><?= $lbl ?></label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 8px 20px;">
                                    <i class="bi bi-check2 me-1"></i> Save Attendance
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No active athletes in this squad to take attendance for.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Notes Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-card-text me-2" style="color: var(--ks-blue);"></i> Coaching Notes
                    </h5>
                    <div class="text-secondary small" style="line-height: 1.6;">
                        <?= htmlspecialchars($session['notes'] ?: 'No additional notes provided for this session.', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <!-- Metadata Card -->
                <div class="card p-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: var(--ks-page-bg);">
                    <div class="text-muted" style="font-size: 11px;">
                        <div>Created: <?= !empty($session['created_at']) ? date('M d, Y H:i', strtotime($session['created_at'])) : '—' ?></div>
                        <div>Last Updated: <?= !empty($session['updated_at']) ? date('M d, Y H:i', strtotime($session['updated_at'])) : '—' ?></div>
                        <div>Organisation ID: <?= (int)$orgId ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
