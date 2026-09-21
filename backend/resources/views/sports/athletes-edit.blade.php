<?php
$athleteId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();
$athleteService = new \App\Services\Athlete\AthleteService();
$athlete = $athleteService->getAthlete($orgId, $athleteId);

// Fetch sports list
$db = \App\Services\BaseService::getDatabaseConnection();
$sportsStmt = $db->query("SELECT id, name FROM sports ORDER BY name ASC");
$sports = $sportsStmt ? $sportsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Fetch active teams
$teamsStmt = $db->prepare("SELECT id, name, sport_id, team_code FROM teams WHERE organization_id = :org_id AND deleted_at IS NULL ORDER BY name ASC");
$teamsStmt->execute([':org_id' => $orgId]);
$teams = $teamsStmt ? $teamsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$pageTitle = $athlete ? 'Edit Athlete — ' . htmlspecialchars($athlete['first_name'] . ' ' . $athlete['last_name']) : 'Edit Athlete';
$activePage = 'athletes';

$errorMsg = $_GET['error'] ?? null;
$successMsg = $_GET['success'] ?? null;

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
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="/athletes/<?= (int)$athlete['id'] ?>" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1 mb-2">
                <i class="bi bi-arrow-left"></i> Back to Athlete Details
            </a>
            <div class="ks-page-header">
                <div>
                    <h1 class="ks-page-title mb-1">Edit Athlete: <?= htmlspecialchars($athlete['first_name'] . ' ' . $athlete['last_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <div class="text-muted small">Code: <strong class="text-dark"><?= htmlspecialchars($athlete['athlete_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull; Registered: <?= htmlspecialchars($athlete['registration_date'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="ks-page-actions">
                    <a href="/athletes/<?= (int)$athlete['id'] ?>" class="btn btn-outline-secondary px-3 py-2" style="border-radius: 8px; font-size: 13px;">Cancel</a>
                    <button type="submit" form="editAthleteForm" class="ks-btn ks-btn-primary px-4 py-2">
                        <i class="bi bi-check2"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </div>
        </div>

        <form action="/athletes/<?= (int)$athlete['id'] ?>/edit" method="POST" id="editAthleteForm">
            <!-- Section 1: Personal Information -->
            <div class="ks-content-card mb-4">
                <div class="ks-card-header">
                    <div class="ks-header-left">
                        <i class="bi bi-person-fill text-primary fs-5"></i>
                        <h3 class="ks-header-title">1. Personal Information</h3>
                    </div>
                </div>

                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="ks-form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="ks-form-control" value="<?= htmlspecialchars($athlete['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="ks-form-control" value="<?= htmlspecialchars($athlete['middle_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="ks-form-control" value="<?= htmlspecialchars($athlete['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="ks-form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" class="ks-form-control" value="<?= htmlspecialchars($athlete['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="ks-form-select" required>
                                <option value="male" <?= ($athlete['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($athlete['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="other" <?= ($athlete['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Blood Group</label>
                            <select name="blood_group" class="ks-form-select">
                                <option value="">Select blood group</option>
                                <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                                    <option value="<?= $bg ?>" <?= ($athlete['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="ks-form-label">Phone Number</label>
                            <input type="text" name="phone" class="ks-form-control" value="<?= htmlspecialchars($athlete['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Email Address</label>
                            <input type="email" name="email" class="ks-form-control" value="<?= htmlspecialchars($athlete['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="col-12">
                            <label class="ks-form-label">Address Line</label>
                            <input type="text" name="address_line1" class="ks-form-control" value="<?= htmlspecialchars($athlete['address_line1'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">City</label>
                            <input type="text" name="city" class="ks-form-control" value="<?= htmlspecialchars($athlete['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">State</label>
                            <input type="text" name="state" class="ks-form-control" value="<?= htmlspecialchars($athlete['state'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="ks-form-control" value="<?= htmlspecialchars($athlete['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Sport & Status -->
            <div class="ks-content-card mb-4">
                <div class="ks-card-header">
                    <div class="ks-header-left">
                        <i class="bi bi-trophy-fill text-primary fs-5"></i>
                        <h3 class="ks-header-title">2. Sports & Roster Information</h3>
                    </div>
                </div>

                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="ks-form-label">Primary Sport <span class="text-danger">*</span></label>
                            <select name="current_sport_id" class="ks-form-select" required>
                                <option value="">Select sport</option>
                                <?php foreach ($sports as $sp): ?>
                                    <option value="<?= (int)$sp['id'] ?>" <?= ($athlete['current_sport_id'] ?? '') == $sp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Assigned Team</label>
                            <select name="team_id" class="ks-form-select">
                                <option value="">No team assignment</option>
                                <?php foreach ($teams as $tm): ?>
                                    <option value="<?= (int)$tm['id'] ?>" <?= ($athlete['team_id'] ?? '') == $tm['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tm['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($tm['team_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Athlete Status <span class="text-danger">*</span></label>
                            <select name="status" class="ks-form-select" required>
                                <option value="active" <?= ($athlete['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="injured" <?= ($athlete['status'] ?? '') === 'injured' ? 'selected' : '' ?>>Injured</option>
                                <option value="inactive" <?= ($athlete['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="suspended" <?= ($athlete['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="ks-form-label">Administrative Notes</label>
                            <textarea name="notes" class="ks-form-control" rows="3"><?= htmlspecialchars($athlete['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Guardian Details -->
            <div class="ks-content-card mb-4">
                <div class="ks-card-header">
                    <div class="ks-header-left">
                        <i class="bi bi-shield-check text-primary fs-5"></i>
                        <h3 class="ks-header-title">3. Guardian / Emergency Contact</h3>
                    </div>
                </div>

                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="ks-form-label">Guardian First Name</label>
                            <input type="text" name="guardian_first_name" class="ks-form-control" value="<?= htmlspecialchars($athlete['guardian']['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Guardian Last Name</label>
                            <input type="text" name="guardian_last_name" class="ks-form-control" value="<?= htmlspecialchars($athlete['guardian']['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Relationship</label>
                            <select name="guardian_relationship" class="ks-form-select">
                                <option value="Father" <?= ($athlete['guardian']['relationship'] ?? '') === 'Father' ? 'selected' : '' ?>>Father</option>
                                <option value="Mother" <?= ($athlete['guardian']['relationship'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother</option>
                                <option value="Guardian" <?= ($athlete['guardian']['relationship'] ?? '') === 'Guardian' ? 'selected' : '' ?>>Legal Guardian</option>
                                <option value="Other" <?= ($athlete['guardian']['relationship'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Guardian Phone</label>
                            <input type="text" name="guardian_phone" class="ks-form-control" value="<?= htmlspecialchars($athlete['guardian']['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Guardian Email</label>
                            <input type="email" name="guardian_email" class="ks-form-control" value="<?= htmlspecialchars($athlete['guardian']['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Section 4: Manage Documents -->
        <div class="ks-content-card mb-4">
            <div class="ks-card-header d-flex align-items-center justify-content-between">
                <div class="ks-header-left">
                    <i class="bi bi-file-earmark-text text-primary fs-5"></i>
                    <h3 class="ks-header-title">4. Athlete Documents</h3>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#uploadDocCollapse">
                    <i class="bi bi-plus-lg me-1"></i> Upload New Document
                </button>
            </div>
            <div class="p-4">
                <!-- Inline Upload Form (Collapsible) -->
                <div class="collapse mb-4" id="uploadDocCollapse">
                    <div class="p-3 bg-light rounded border">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-upload text-primary me-2"></i>Attach New Document</h6>
                        <form action="/athletes/<?= (int)$athlete['id'] ?>/documents/upload" method="POST" enctype="multipart/form-data">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="ks-form-label">Document Type *</label>
                                    <select name="document_type" class="ks-form-select form-select-sm" required>
                                        <option value="id_proof">Government ID / Aadhaar</option>
                                        <option value="birth_certificate">Birth Certificate</option>
                                        <option value="passport">Passport</option>
                                        <option value="medical_certificate">Medical Certificate</option>
                                        <option value="sports_certificate">Sports Certificate</option>
                                        <option value="consent_form">Consent Form</option>
                                        <option value="other">Other Document</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="ks-form-label">Document Title</label>
                                    <input type="text" name="document_name" class="ks-form-control form-control-sm" placeholder="e.g. Aadhaar Card">
                                </div>
                                <div class="col-md-3">
                                    <label class="ks-form-label">Document Number</label>
                                    <input type="text" name="document_number" class="ks-form-control form-control-sm" placeholder="Optional">
                                </div>
                                <div class="col-md-3">
                                    <label class="ks-form-label">Select File * (PDF, JPG, PNG)</label>
                                    <input type="file" name="document_file" class="ks-form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="ks-form-label">Issue Date</label>
                                    <input type="date" name="issue_date" class="ks-form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="ks-form-label">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="ks-form-control form-control-sm">
                                </div>
                                <div class="col-md-4">
                                    <label class="ks-form-label">Remarks</label>
                                    <input type="text" name="notes" class="ks-form-control form-control-sm" placeholder="Optional notes">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="ks-btn ks-btn-primary btn-sm w-100 justify-content-center">
                                        Upload
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Existing Documents Table -->
                <?php if (!empty($athlete['documents']) && count($athlete['documents']) > 0): ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="width: 100%;">
                            <thead class="table-light">
                                <tr class="small text-muted">
                                    <th>Document Type</th>
                                    <th>Title / Number</th>
                                    <th>Validity</th>
                                    <th>Uploaded</th>
                                    <th class="text-end" style="width: 1%;">Actions</th>
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
                                                <a href="/athletes/<?= (int)$athlete['id'] ?>/documents/<?= (int)$doc['id'] ?>/view" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 11px;">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <a href="/athletes/<?= (int)$athlete['id'] ?>/documents/<?= (int)$doc['id'] ?>/download" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 11px;">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <form action="/athletes/<?= (int)$athlete['id'] ?>/documents/<?= (int)$doc['id'] ?>/delete" method="POST" class="d-inline" onsubmit="return confirm('Delete this document?');">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size: 11px;">
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
                    <p class="text-muted small mb-0">No documents attached yet. Click <strong>Upload New Document</strong> above to add one.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section 5: Athlete Account / Login Access -->
        <div class="ks-content-card mb-5">
            <div class="ks-card-header">
                <div class="ks-header-left">
                    <i class="bi bi-person-lock text-primary fs-5"></i>
                    <h3 class="ks-header-title">5. Athlete Account / Login Access</h3>
                </div>
            </div>
            <div class="p-4">
                <?php if (!empty($athlete['user_account'])): ?>
                    <div class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Linked Login Email:</span>
                            <strong class="text-dark fs-6"><?= htmlspecialchars($athlete['user_account']['email'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">Role & Status:</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                            <span class="badge bg-light text-dark border ms-1">Athlete (Role #5)</span>
                        </div>
                        <div class="col-md-5 text-end">
                            <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-medium" data-bs-toggle="modal" data-bs-target="#editResetPasswordModal">
                                <i class="bi bi-key me-1"></i> Reset Password
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <strong class="d-block text-dark small mb-1">No Login Account Linked</strong>
                            <p class="text-muted small mb-0">This athlete cannot currently sign in to the Flutter mobile app or web portal.</p>
                        </div>
                        <button type="button" class="ks-btn ks-btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editCreateAccountModal">
                            <i class="bi bi-person-plus"></i>
                            <span>Provision Account</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bottom Actions -->
        <div class="d-flex align-items-center justify-content-end gap-2 mb-5">
            <a href="/athletes/<?= (int)$athlete['id'] ?>" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px; font-size: 13px;">Cancel</a>
            <button type="submit" form="editAthleteForm" class="ks-btn ks-btn-primary px-4 py-2">
                <i class="bi bi-check2"></i>
                <span>Save Changes</span>
            </button>
        </div>

        <!-- MODALS FOR EDIT VIEW -->
        <?php if (empty($athlete['user_account'])): ?>
            <div class="modal fade" id="editCreateAccountModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <form action="/athletes/<?= (int)$athlete['id'] ?>/account/create" method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold text-dark fs-6"><i class="bi bi-person-plus text-primary me-2"></i>Provision Athlete Login Account</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="ks-form-label">Login Email <span class="text-danger">*</span></label>
                                    <input type="email" name="login_email" class="ks-form-control" required value="<?= htmlspecialchars($athlete['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="ks-form-label">Temporary Password (Optional)</label>
                                    <input type="password" name="password" class="ks-form-control" placeholder="Leave blank to auto-generate">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="ks-btn ks-btn-primary btn-sm">Provision Account</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="modal fade" id="editResetPasswordModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <form action="/athletes/<?= (int)$athlete['id'] ?>/account/reset-password" method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold text-dark fs-6"><i class="bi bi-key text-primary me-2"></i>Reset Athlete Password</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="ks-form-label">New Temporary Password (Optional)</label>
                                    <input type="password" name="password" class="ks-form-control" placeholder="Leave blank to auto-generate">
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
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
