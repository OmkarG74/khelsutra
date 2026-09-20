<?php
$athleteId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();
$athleteService = new \App\Services\Athlete\AthleteService();
$athlete = $athleteService->getAthlete($orgId, $athleteId);

$pageTitle = $athlete ? htmlspecialchars(($athlete['first_name'] ?? '') . ' ' . ($athlete['last_name'] ?? '') . ' — Athlete Details') : 'Athlete Details';
$activePage = 'athletes';

ob_start();
?>

<div class="ks-content">
    <?php if (!$athlete): ?>
        <div class="card p-5 text-center" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <div class="mb-3"><i class="bi bi-person-x fs-1 text-muted"></i></div>
            <h4 class="fw-bold" style="color: var(--ks-navy);">Athlete Not Found</h4>
            <p class="text-muted small">The requested athlete record does not exist or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/athletes" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Athletes
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Top Back Navigation & Page Header -->
        <div class="mb-3">
            <a href="/athletes" class="text-decoration-none text-muted small fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Athletes
            </a>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: #E0F2FE; color: #0284C7; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700;">
                    <?= strtoupper(substr($athlete['first_name'] ?? 'A', 0, 1) . substr($athlete['last_name'] ?? '', 0, 1)) ?>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="h4 fw-bold mb-0" style="color: var(--ks-navy);">
                            <?= htmlspecialchars(($athlete['first_name'] ?? '') . ' ' . ($athlete['middle_name'] ? $athlete['middle_name'] . ' ' : '') . ($athlete['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                        <?php
                            $badgeClass = match($athlete['status'] ?? 'active') {
                                'active' => 'badge-success',
                                'injured' => 'badge-warning',
                                'inactive' => 'badge-danger',
                                default => 'badge-secondary'
                            };
                        ?>
                        <span class="badge <?= $badgeClass ?>" style="border-radius: 12px; font-size: 11px; padding: 4px 10px; text-transform: uppercase;">
                            <?= htmlspecialchars($athlete['status'] ?? 'active', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="text-muted small mt-1">Code: <strong style="color: var(--ks-text);"><?= htmlspecialchars($athlete['athlete_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull; Sport: <strong style="color: var(--ks-text);"><?= htmlspecialchars($athlete['sport_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></strong></div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="/athletes/<?= (int)$athlete['id'] ?>/edit" class="btn btn-primary d-inline-flex align-items-center gap-2" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 8px 18px;">
                    <i class="bi bi-pencil-square"></i> Edit Athlete
                </a>
            </div>
        </div>

        <div class="row g-3">
            <!-- Left Column: Primary Details -->
            <div class="col-lg-8">
                <!-- Personal Information Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-person me-2" style="color: var(--ks-blue);"></i> Personal Information
                    </h5>
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="text-muted small">Date of Birth</div>
                            <div class="fw-medium text-dark mt-1"><?= !empty($athlete['date_of_birth']) ? date('M d, Y', strtotime($athlete['date_of_birth'])) : '—' ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Gender</div>
                            <div class="fw-medium text-dark mt-1"><?= htmlspecialchars(ucfirst($athlete['gender'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Blood Group</div>
                            <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($athlete['blood_group'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Phone</div>
                            <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($athlete['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Email Address</div>
                            <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($athlete['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-muted small">Registration Date</div>
                            <div class="fw-medium text-dark mt-1"><?= !empty($athlete['registration_date']) ? date('M d, Y', strtotime($athlete['registration_date'])) : '—' ?></div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Residential Address</div>
                            <div class="fw-medium text-dark mt-1">
                                <?= htmlspecialchars($athlete['address_line1'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($athlete['city'])): ?>, <?= htmlspecialchars($athlete['city'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                <?php if (!empty($athlete['state'])): ?>, <?= htmlspecialchars($athlete['state'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                <?php if (!empty($athlete['postal_code'])): ?> - <?= htmlspecialchars($athlete['postal_code'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                <?php if (empty($athlete['address_line1']) && empty($athlete['city'])): ?>—<?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Guardian Information Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-shield-check me-2" style="color: var(--ks-blue);"></i> Guardian / Emergency Contact
                    </h5>
                    <?php if (!empty($athlete['guardian'])): ?>
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="text-muted small">Guardian Name</div>
                                <div class="fw-medium text-dark mt-1"><?= htmlspecialchars(($athlete['guardian']['first_name'] ?? '') . ' ' . ($athlete['guardian']['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-muted small">Relationship</div>
                                <div class="fw-medium text-dark mt-1"><?= htmlspecialchars(ucfirst($athlete['guardian']['relationship'] ?? 'Guardian'), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-muted small">Phone Number</div>
                                <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($athlete['guardian']['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Email Address</div>
                                <div class="fw-medium text-dark mt-1"><?= htmlspecialchars($athlete['guardian']['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Emergency Contact</div>
                                <div class="fw-medium text-dark mt-1">
                                    <?php if (!empty($athlete['guardian']['is_emergency_contact'])): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Primary Emergency Contact</span>
                                    <?php else: ?>
                                        <span class="text-muted">No</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No guardian record linked. You can add guardian details via Edit Athlete.</p>
                    <?php endif; ?>
                </div>

                <!-- Documents Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h5 class="fw-bold mb-0" style="color: var(--ks-navy); font-size: 15px;">
                            <i class="bi bi-file-earmark-text me-2" style="color: var(--ks-blue);"></i> Athlete Documents
                        </h5>
                    </div>
                    <?php if (!empty($athlete['documents']) && count($athlete['documents']) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr class="text-muted small">
                                        <th>Document Type</th>
                                        <th>File Name</th>
                                        <th>Verification</th>
                                        <th>Uploaded Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($athlete['documents'] as $doc): ?>
                                        <tr>
                                            <td class="fw-medium text-dark"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $doc['document_type'] ?? 'ID Proof')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars($doc['file_name'] ?? 'document.pdf', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?php if (!empty($doc['is_verified'])): ?>
                                                    <span class="badge bg-success-subtle text-success">Verified</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted small"><?= !empty($doc['created_at']) ? date('M d, Y', strtotime($doc['created_at'])) : '—' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No documents uploaded for this athlete yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Team, Sport & Notes -->
            <div class="col-lg-4">
                <!-- Team & Sport Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-trophy me-2" style="color: var(--ks-blue);"></i> Sport & Team Assignment
                    </h5>
                    <div class="mb-3">
                        <div class="text-muted small">Primary Sport</div>
                        <div class="fw-bold fs-6 text-dark mt-1"><?= htmlspecialchars($athlete['sport_name'] ?? 'General Sports', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small">Assigned Team</div>
                        <?php if (!empty($athlete['team_name'])): ?>
                            <div class="fw-bold fs-6 text-primary mt-1">
                                <a href="/teams/<?= (int)($athlete['team_id'] ?? 0) ?>" class="text-decoration-none" style="color: var(--ks-blue);">
                                    <?= htmlspecialchars($athlete['team_name'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small mt-1">Not currently assigned to an active team roster.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Administrative Notes Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-journal-text me-2" style="color: var(--ks-blue);"></i> Administrative Notes
                    </h5>
                    <div class="text-secondary small" style="line-height: 1.6;">
                        <?= htmlspecialchars($athlete['notes'] ?: 'No notes recorded for this athlete.', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <!-- Record Metadata -->
                <div class="card p-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: var(--ks-page-bg);">
                    <div class="text-muted" style="font-size: 11px;">
                        <div>Created: <?= !empty($athlete['created_at']) ? date('M d, Y H:i', strtotime($athlete['created_at'])) : '—' ?></div>
                        <div>Last Updated: <?= !empty($athlete['updated_at']) ? date('M d, Y H:i', strtotime($athlete['updated_at'])) : '—' ?></div>
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
