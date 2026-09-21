<?php
$coachId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();
$coachService = new \App\Services\Coach\CoachService();
$coach = $coachService->getCoach($orgId, $coachId);

$pageTitle = $coach ? htmlspecialchars(($coach['first_name'] ?? '') . ' ' . ($coach['last_name'] ?? '') . ' — Coach Details') : 'Coach Details';
$activePage = 'coaches';

ob_start();
?>

<div class="ks-content">
    <?php if (!$coach): ?>
        <div class="card p-5 text-center" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <div class="mb-3"><i class="bi bi-person-x fs-1 text-muted"></i></div>
            <h4 class="fw-bold" style="color: var(--ks-navy);">Coach Not Found</h4>
            <p class="text-muted small">The requested coach record does not exist or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/coaches" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Coaches
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="/coaches" class="text-decoration-none text-muted small fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Coaches
            </a>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: #EEF2FF; color: #4F46E5; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700;">
                    <?= strtoupper(substr($coach['first_name'] ?? 'C', 0, 1) . substr($coach['last_name'] ?? '', 0, 1)) ?>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="h4 fw-bold mb-0" style="color: var(--ks-navy);">
                            <?= htmlspecialchars(($coach['first_name'] ?? '') . ' ' . ($coach['middle_name'] ? $coach['middle_name'] . ' ' : '') . ($coach['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                        <span class="badge <?= ($coach['coach_status'] ?? 'active') === 'active' ? 'badge-success' : 'badge-secondary' ?>" style="border-radius: 12px; font-size: 11px; padding: 4px 10px; text-transform: uppercase;">
                            <?= htmlspecialchars($coach['coach_status'] ?? 'active', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="text-muted small mt-1">
                        Coach Code: <strong style="color: var(--ks-text);"><?= htmlspecialchars($coach['coach_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull;
                        Employee Code: <strong style="color: var(--ks-text);"><?= htmlspecialchars($coach['employee_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull;
                        Specialization: <strong style="color: var(--ks-text);"><?= htmlspecialchars($coach['specialization'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="/coaches/<?= (int)$coach['coach_profile_id'] ?>/edit" class="btn btn-primary d-inline-flex align-items-center gap-2" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 8px 18px;">
                    <i class="bi bi-pencil-square"></i> Edit Coach
                </a>
            </div>
        </div>

        <div class="row g-3">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Coaching Profile Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-award me-2" style="color: var(--ks-blue);"></i> Coaching Credentials & Experience
                    </h5>
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="text-muted small">Specialization</div>
                            <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($coach['specialization'] ?? 'General Coaching', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Experience</div>
                            <div class="fw-medium text-dark mt-1"><?= (float)($coach['experience_years'] ?? 0) ?> Years</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">License Number</div>
                            <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($coach['license_number'] ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Academic Qualifications</div>
                            <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($coach['qualification'] ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Certifications</div>
                            <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($coach['certifications'] ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Personal & Employment Information -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-person me-2" style="color: var(--ks-blue);"></i> Personal & Employment Details
                    </h5>
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="text-muted small">Designation</div>
                            <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($coach['designation'] ?? 'Coach', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Department</div>
                            <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($coach['department_name'] ?? 'Sports Department', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Employment Type</div>
                            <div class="fw-medium text-dark mt-1"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $coach['employment_type'] ?? 'full_time')), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Phone</div>
                            <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($coach['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Email Address</div>
                            <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($coach['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Date of Birth</div>
                            <div class="fw-medium text-dark mt-1"><?= !empty($coach['date_of_birth']) ? date('M d, Y', strtotime($coach['date_of_birth'])) : '—' ?></div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Address</div>
                            <div class="fw-medium text-dark mt-1">
                                <?= htmlspecialchars($coach['address_line1'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($coach['city'])): ?>, <?= htmlspecialchars($coach['city'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                <?php if (!empty($coach['state'])): ?>, <?= htmlspecialchars($coach['state'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                <?php if (empty($coach['address_line1']) && empty($coach['city'])): ?>—<?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assigned Squads / Teams -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-people me-2" style="color: var(--ks-blue);"></i> Assigned Teams & Squads
                    </h5>
                    <?php if (!empty($coach['teams']) && count($coach['teams']) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr class="text-muted small">
                                        <th>Team Name</th>
                                        <th>Sport</th>
                                        <th>Coach Role</th>
                                        <th>Primary Coach</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($coach['teams'] as $tm): ?>
                                        <tr>
                                            <td class="fw-semibold">
                                                <a href="/teams/<?= (int)$tm['team_id'] ?>" class="text-decoration-none" style="color: var(--ks-blue);">
                                                    <?= htmlspecialchars($tm['team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                            </td>
                                            <td class="text-muted small"><?= htmlspecialchars($tm['sport_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-dark small"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $tm['coach_role'] ?? 'head_coach')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?php if (!empty($tm['is_primary'])): ?>
                                                    <span class="badge bg-success-subtle text-success">Head / Primary</span>
                                                <?php else: ?>
                                                    <span class="text-muted small">Assistant</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">This coach is not currently assigned to any team squads.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Recent Training Sessions -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-clock-history me-2" style="color: var(--ks-blue);"></i> Recent Training Sessions
                    </h5>
                    <?php if (!empty($coach['training_sessions']) && count($coach['training_sessions']) > 0): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($coach['training_sessions'] as $sess): ?>
                                <div class="p-2 border rounded" style="background: var(--ks-page-bg);">
                                    <div class="d-flex justify-content-between">
                                        <strong class="small text-dark"><?= htmlspecialchars($sess['team_name'] ?? 'Squad Training', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span class="badge bg-light text-secondary border"><?= htmlspecialchars($sess['status'] ?? 'scheduled', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="text-muted" style="font-size: 11px; margin-top: 3px;">
                                        <i class="bi bi-calendar3 me-1"></i> <?= !empty($sess['training_date']) ? date('M d, Y', strtotime($sess['training_date'])) : '—' ?> &bull; <?= htmlspecialchars(substr($sess['start_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars(substr($sess['end_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($sess['venue_name'] ?? 'Academy Grounds', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No recent training sessions recorded for this coach.</p>
                    <?php endif; ?>
                </div>

                <!-- Emergency Contact -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-telephone-inbound me-2" style="color: var(--ks-blue);"></i> Emergency Contact
                    </h5>
                    <div class="small">
                        <div class="text-muted">Contact Name</div>
                        <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($coach['emergency_contact_name'] ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted mt-2">Phone</div>
                        <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($coach['emergency_contact_phone'] ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted mt-2">Relationship</div>
                        <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($coach['emergency_contact_relationship'] ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
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
