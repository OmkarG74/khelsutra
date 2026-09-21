<?php
$athleteId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();
$athleteService = new \App\Services\Athlete\AthleteService();
$athlete = $athleteService->getAthlete($orgId, $athleteId);

$pageTitle = $athlete ? htmlspecialchars(($athlete['first_name'] ?? '') . ' ' . ($athlete['last_name'] ?? '') . ' — Athlete Details') : 'Athlete Details';
$activePage = 'athletes';

$successMsg = $_GET['success'] ?? null;
$errorMsg = $_GET['error'] ?? null;
$pwdReset = $_SESSION['athlete_password_reset'] ?? null;
if ($pwdReset && ($pwdReset['athlete_id'] ?? 0) === $athleteId) {
    unset($_SESSION['athlete_password_reset']);
} else {
    $pwdReset = null;
}

$accProvisioned = $_SESSION['athlete_account_provisioned'] ?? null;
if ($accProvisioned) {
    unset($_SESSION['athlete_account_provisioned']);
}

ob_start();
?>

<div class="ks-page">
    <?php if (!$athlete): ?>
        <div class="ks-content-card p-5 text-center">
            <div class="mb-3"><i class="bi bi-person-x fs-1 text-muted"></i></div>
            <h4 class="fw-bold text-dark">Athlete Not Found</h4>
            <p class="text-muted small">The requested athlete record does not exist or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/athletes" class="btn btn-outline-secondary" style="border-radius: 8px; font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Athletes
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Top Back Navigation & Page Header -->
        <div class="mb-3">
            <a href="/athletes" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1 mb-2">
                <i class="bi bi-arrow-left"></i> Back to Athletes
            </a>
            <div class="ks-page-header">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 52px; height: 52px; border-radius: 50%; background: #E0F2FE; color: #0284C7; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700;">
                        <?= strtoupper(substr($athlete['first_name'] ?? 'A', 0, 1) . substr($athlete['last_name'] ?? '', 0, 1)) ?>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h1 class="ks-page-title mb-0">
                                <?= htmlspecialchars(($athlete['first_name'] ?? '') . ' ' . ($athlete['middle_name'] ? $athlete['middle_name'] . ' ' : '') . ($athlete['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </h1>
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
                        <div class="text-muted small mt-1">
                            Code: <strong class="text-dark"><?= htmlspecialchars($athlete['athlete_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull; 
                            Sport: <strong class="text-dark"><?= htmlspecialchars($athlete['sport_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>
                </div>

                <div class="ks-page-actions">
                    <a href="/athletes/<?= (int)$athlete['id'] ?>/edit" class="ks-btn ks-btn-primary px-3 py-2">
                        <i class="bi bi-pencil-square"></i>
                        <span>Edit Athlete</span>
                    </a>
                </div>
            </div>
        </div>

        <?php if ($pwdReset): ?>
            <div class="alert alert-warning d-flex align-items-center justify-content-between mb-4 p-3 rounded-3" style="border-left: 4px solid #F59E0B;">
                <div>
                    <strong class="d-block mb-1"><i class="bi bi-key-fill me-1"></i> Temporary Password Reset Successfully</strong>
                    <span class="small">New Temporary Password: <code class="fs-6 px-2 py-1 bg-white rounded border fw-bold text-dark"><?= htmlspecialchars($pwdReset['temp_password'], ENT_QUOTES, 'UTF-8') ?></code></span>
                    <div class="text-muted small mt-1">Please copy and communicate this to the athlete. It is not displayed again.</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark ms-3" onclick="navigator.clipboard.writeText('<?= addslashes($pwdReset['temp_password']) ?>'); alert('Password copied to clipboard!');">
                    <i class="bi bi-clipboard me-1"></i> Copy
                </button>
            </div>
        <?php endif; ?>

        <?php if ($accProvisioned): ?>
            <div class="alert alert-success d-flex align-items-center justify-content-between mb-4 p-3 rounded-3" style="border-left: 4px solid #10B981;">
                <div>
                    <strong class="d-block mb-1"><i class="bi bi-person-check-fill me-1"></i> Login Account Created Successfully</strong>
                    <div class="small">Login Email: <strong><?= htmlspecialchars($accProvisioned['email'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <div class="small mt-1">Temporary Password: <code class="fs-6 px-2 py-1 bg-white rounded border fw-bold text-dark"><?= htmlspecialchars($accProvisioned['temp_password'], ENT_QUOTES, 'UTF-8') ?></code></div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark ms-3" onclick="navigator.clipboard.writeText('<?= addslashes($accProvisioned['temp_password']) ?>'); alert('Password copied to clipboard!');">
                    <i class="bi bi-clipboard me-1"></i> Copy Password
                </button>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <!-- Left Column: Primary Details & Documents -->
            <div class="col-lg-8">
                <!-- Personal Information Card -->
                <div class="ks-content-card mb-4">
                    <div class="ks-card-header">
                        <div class="ks-header-left">
                            <i class="bi bi-person-fill text-primary fs-5"></i>
                            <h3 class="ks-header-title">Personal Information</h3>
                        </div>
                    </div>
                    <div class="p-4">
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
                </div>

                <!-- Guardian Information Card -->
                <div class="ks-content-card mb-4">
                    <div class="ks-card-header">
                        <div class="ks-header-left">
                            <i class="bi bi-shield-check text-primary fs-5"></i>
                            <h3 class="ks-header-title">Guardian / Emergency Contact</h3>
                        </div>
                    </div>
                    <div class="p-4">
                        <?php if (!empty($athlete['guardian'])): ?>
                            <div class="row g-3">
                                <div class="col-sm-4">
                                    <div class="text-muted small">Guardian Name</div>
                                    <div class="fw-medium text-dark mt-1"><?= htmlspecialchars(($athlete['guardian']['full_name'] ?? (($athlete['guardian']['first_name'] ?? '') . ' ' . ($athlete['guardian']['last_name'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></div>
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
                                    <div class="text-muted small">Emergency Contact Status</div>
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
                            <p class="text-muted small mb-0">No guardian record linked. Guardian details can be updated via Edit Athlete.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Athlete Documents Section -->
                <div class="ks-content-card mb-4">
                    <div class="ks-card-header d-flex align-items-center justify-content-between">
                        <div class="ks-header-left">
                            <i class="bi bi-file-earmark-text text-primary fs-5"></i>
                            <h3 class="ks-header-title">Athlete Documents</h3>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocModal" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: 6px; font-size: 12px; font-weight: 600;">
                            <i class="bi bi-upload me-1"></i> Upload Document
                        </button>
                    </div>
                    <div class="p-0">
                        <?php if (!empty($athlete['documents']) && count($athlete['documents']) > 0): ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0 ks-table-athlete-docs" style="width: 100%;">
                                    <thead class="table-light">
                                        <tr class="small text-muted">
                                            <th style="width: 22%;">Document Type</th>
                                            <th style="width: 32%;">Document Name / Number</th>
                                            <th style="width: 18%;">Validity</th>
                                            <th style="width: 16%;">Uploaded Date</th>
                                            <th class="text-end" style="width: 12%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($athlete['documents'] as $doc): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        <?= htmlspecialchars(\App\Services\Athlete\AthleteDocumentService::DOCUMENT_TYPES[$doc['document_type']] ?? ucfirst(str_replace('_', ' ', $doc['document_type'])), ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($doc['document_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <?php if (!empty($doc['document_number'])): ?>
                                                        <div class="text-muted small">No: <?= htmlspecialchars($doc['document_number'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-muted">
                                                    <?php if (!empty($doc['expiry_date'])): ?>
                                                        Exp: <?= date('M d, Y', strtotime($doc['expiry_date'])) ?>
                                                    <?php elseif (!empty($doc['issue_date'])): ?>
                                                        Issued: <?= date('M d, Y', strtotime($doc['issue_date'])) ?>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-muted">
                                                    <?= !empty($doc['created_at']) ? date('M d, Y', strtotime($doc['created_at'])) : '—' ?>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <div class="d-inline-flex align-items-center gap-1">
                                                        <a href="/athletes/<?= (int)$athlete['id'] ?>/documents/<?= (int)$doc['id'] ?>/view" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 11px;" title="View in Browser">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
                                                        <a href="/athletes/<?= (int)$athlete['id'] ?>/documents/<?= (int)$doc['id'] ?>/download" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 11px;" title="Download File">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                        <form action="/athletes/<?= (int)$athlete['id'] ?>/documents/<?= (int)$doc['id'] ?>/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this document?');">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size: 11px;" title="Delete Document">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted small">
                                <i class="bi bi-file-earmark-arrow-up fs-2 d-block mb-2 text-secondary"></i>
                                No documents uploaded for this athlete yet. Click <strong>Upload Document</strong> to attach identity proofs or certificates.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Account Status, Team & Notes -->
            <div class="col-lg-4">
                <!-- Athlete Login Account Card -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header">
                        <div class="ks-header-left">
                            <i class="bi bi-person-badge text-primary fs-5"></i>
                            <h3 class="ks-header-title">Athlete Account Access</h3>
                        </div>
                    </div>
                    <div class="p-3">
                        <?php if (!empty($athlete['user_account'])): ?>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <span class="text-muted small d-block">Login Status</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 mt-1">
                                        <?= htmlspecialchars(ucfirst($athlete['user_account']['user_status'] ?? 'Active'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted small d-block">Role</span>
                                    <strong class="text-dark small"><?= htmlspecialchars($athlete['user_account']['role_name'] ?? 'Athlete', ENT_QUOTES, 'UTF-8') ?> (Role #<?= (int)($athlete['user_account']['role_id'] ?? 5) ?>)</strong>
                                </div>
                            </div>

                            <div class="mb-2">
                                <span class="text-muted small d-block">Login Identifier:</span>
                                <strong class="text-dark small d-block text-truncate"><?= htmlspecialchars($athlete['user_account']['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>

                            <div class="mb-3">
                                <span class="text-muted small d-block">Last Login:</span>
                                <span class="text-secondary small"><?= !empty($athlete['user_account']['last_login_at']) ? date('M d, Y H:i', strtotime($athlete['user_account']['last_login_at'])) : 'Never logged in' ?></span>
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#resetPasswordModal" style="border-radius: 6px; font-size: 12px; font-weight: 500;">
                                <i class="bi bi-key me-1"></i> Reset Password
                            </button>
                        <?php else: ?>
                            <div class="text-center py-2">
                                <p class="text-muted small mb-3">No login account currently provisioned for this athlete.</p>
                                <button type="button" class="ks-btn ks-btn-primary btn-sm w-100 justify-content-center" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                                    <i class="bi bi-person-plus"></i>
                                    <span>Provision Login Account</span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Team & Sport Card -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header">
                        <div class="ks-header-left">
                            <i class="bi bi-trophy text-primary fs-5"></i>
                            <h3 class="ks-header-title">Sport & Team</h3>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="mb-3">
                            <div class="text-muted small">Primary Sport</div>
                            <div class="fw-bold fs-6 text-dark mt-1"><?= htmlspecialchars($athlete['sport_name'] ?? 'General Sports', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div>
                            <div class="text-muted small">Assigned Team</div>
                            <?php if (!empty($athlete['team_name'])): ?>
                                <div class="fw-bold fs-6 text-primary mt-1">
                                    <a href="/teams/<?= (int)($athlete['team_id'] ?? 0) ?>" class="text-decoration-none" style="color: var(--ks-blue);">
                                        <?= htmlspecialchars($athlete['team_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small mt-1">Not currently assigned to an active team.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Administrative Notes Card -->
                <div class="ks-content-card mb-3">
                    <div class="ks-card-header">
                        <div class="ks-header-left">
                            <i class="bi bi-journal-text text-primary fs-5"></i>
                            <h3 class="ks-header-title">Administrative Notes</h3>
                        </div>
                    </div>
                    <div class="p-3 text-secondary small" style="line-height: 1.6;">
                        <?= htmlspecialchars($athlete['notes'] ?: 'No notes recorded for this athlete.', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <!-- Record Metadata -->
                <div class="p-3 bg-light rounded-3 border text-muted" style="font-size: 11px;">
                    <div>Created: <?= !empty($athlete['created_at']) ? date('M d, Y H:i', strtotime($athlete['created_at'])) : '—' ?></div>
                    <div>Last Updated: <?= !empty($athlete['updated_at']) ? date('M d, Y H:i', strtotime($athlete['updated_at'])) : '—' ?></div>
                    <div>Organisation ID: <?= (int)$orgId ?></div>
                </div>
            </div>
        </div>

        <!-- MODAL: Upload Document -->
        <div class="modal fade" id="uploadDocModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="/athletes/<?= (int)$athlete['id'] ?>/documents/upload" method="POST" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-dark fs-6"><i class="bi bi-upload text-primary me-2"></i>Upload Athlete Document</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="ks-form-label">Document Type <span class="text-danger">*</span></label>
                                <select name="document_type" class="ks-form-select" required>
                                    <option value="id_proof">Government ID / Aadhaar</option>
                                    <option value="birth_certificate">Birth Certificate</option>
                                    <option value="passport">Passport</option>
                                    <option value="medical_certificate">Medical Certificate</option>
                                    <option value="sports_certificate">Sports Certificate</option>
                                    <option value="consent_form">Consent Form</option>
                                    <option value="other">Other Document</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="ks-form-label">Document Title</label>
                                <input type="text" name="document_name" class="ks-form-control" placeholder="e.g. Aadhaar Card / Medical Clearance">
                            </div>
                            <div class="mb-3">
                                <label class="ks-form-label">Document Number</label>
                                <input type="text" name="document_number" class="ks-form-control" placeholder="Optional identifier">
                            </div>
                            <div class="mb-3">
                                <label class="ks-form-label">Select File <span class="text-danger">*</span></label>
                                <input type="file" name="document_file" class="ks-form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                                <small class="text-muted">PDF, JPG, PNG up to 10MB.</small>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="ks-form-label">Issue Date</label>
                                    <input type="date" name="issue_date" class="ks-form-control">
                                </div>
                                <div class="col-6">
                                    <label class="ks-form-label">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="ks-form-control">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="ks-form-label">Notes / Remarks</label>
                                <input type="text" name="notes" class="ks-form-control" placeholder="Optional notes">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="ks-btn ks-btn-primary btn-sm">Upload Document</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: Provision Account -->
        <div class="modal fade" id="createAccountModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="/athletes/<?= (int)$athlete['id'] ?>/account/create" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-dark fs-6"><i class="bi bi-person-plus text-primary me-2"></i>Provision Athlete Login Account</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">
                                This provisions a secure login account with the <strong>Athlete (Role #5)</strong> role, enabling access to the Flutter mobile app and web portal.
                            </p>
                            <div class="mb-3">
                                <label class="ks-form-label">Login Email <span class="text-danger">*</span></label>
                                <input type="email" name="login_email" class="ks-form-control" required value="<?= htmlspecialchars($athlete['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="athlete@example.com">
                            </div>
                            <div class="mb-3">
                                <label class="ks-form-label">Username (Optional)</label>
                                <input type="text" name="username" class="ks-form-control" placeholder="Leave blank to auto-generate">
                            </div>
                            <div class="mb-2">
                                <label class="ks-form-label">Temporary Password</label>
                                <input type="password" name="password" class="ks-form-control" placeholder="Leave blank to auto-generate a secure password">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="ks-btn ks-btn-primary btn-sm">Create Login Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: Reset Password -->
        <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="/athletes/<?= (int)$athlete['id'] ?>/account/reset-password" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-dark fs-6"><i class="bi bi-key text-primary me-2"></i>Reset Athlete Password</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">
                                You can specify a new temporary password or leave it blank to auto-generate a secure temporary password.
                            </p>
                            <div class="mb-3">
                                <label class="ks-form-label">New Password (Optional)</label>
                                <input type="password" name="password" class="ks-form-control" placeholder="Leave blank to auto-generate">
                                <small class="text-muted">Will be encrypted using bcrypt and shown once for delivery.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning btn-sm text-dark fw-bold">Reset Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
