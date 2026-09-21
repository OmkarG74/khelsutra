<?php
$activePage = 'athletes';
$title = 'Add Athlete — KhelSutra';

$orgId = current_organization_id();
$pdo = \App\Services\BaseService::getDatabaseConnection();
$sportsList = $pdo->query("SELECT id, name FROM sports WHERE status = 'active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT id, name, team_code, sport_id FROM teams WHERE organization_id = :org_id AND deleted_at IS NULL ORDER BY name ASC");
$stmt->execute([':org_id' => $orgId]);
$teamsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for one-time success message in session
$successData = $_SESSION['athlete_created_success'] ?? null;
if ($successData && isset($_GET['created'])) {
    unset($_SESSION['athlete_created_success']);
} else {
    $successData = null;
}

$errorMessage = $_GET['error'] ?? null;

ob_start();
?>

<div class="ks-page">
    <?php if ($successData): ?>
        <!-- ONE-TIME SUCCESS STATE -->
        <div class="ks-content-card mb-4 border-success-subtle shadow-sm" style="border-left: 5px solid #10B981;">
            <div class="p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #D1FAE5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <h3 class="h4 fw-bold mb-1" style="color: #065F46;">Athlete Created Successfully</h3>
                        <div class="text-muted small">The athlete profile has been registered and verified in the organisation directory.</div>
                    </div>
                </div>

                <div class="row g-3 p-3 bg-light rounded-3 mb-3" style="border: 1px solid #E2E8F0;">
                    <div class="col-md-3 col-sm-6">
                        <div class="text-muted small">Athlete Name</div>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($successData['name'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="text-muted small">Registration Code</div>
                        <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($successData['athlete_code'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="text-muted small">Sport & Team</div>
                        <div class="fw-medium text-dark"><?= htmlspecialchars($successData['sport_name'], ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($successData['team_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="text-muted small">Uploaded Documents</div>
                        <div class="fw-medium text-dark"><i class="bi bi-file-earmark-check text-success me-1"></i><?= (int)$successData['uploaded_docs_count'] ?> Document(s)</div>
                    </div>
                </div>

                <?php if (!empty($successData['has_account'])): ?>
                    <!-- Athlete Account Credentials -->
                    <div class="p-3 rounded-3 mb-4" style="background: #EFF6FF; border: 1px solid #BFDBFE;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold text-primary" style="font-size: 14px;">
                                <i class="bi bi-person-badge me-1"></i> Athlete Login Account Provisioned
                            </span>
                            <span class="badge bg-success" style="font-size: 11px;">Active</span>
                        </div>
                        <div class="row g-2 small">
                            <div class="col-md-4">
                                <span class="text-muted">Username / Email:</span>
                                <strong class="d-block text-dark"><?= htmlspecialchars($successData['login_email'] ?? $successData['login_username'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Role:</span>
                                <strong class="d-block text-dark">Athlete (Role #5)</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Temporary Password:</span>
                                <div class="input-group input-group-sm mt-1">
                                    <input type="text" class="form-control font-monospace" id="oneTimePassword" readonly value="<?= htmlspecialchars($successData['temp_password'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="background: #fff; font-weight: 600; font-size: 13px;">
                                    <button class="btn btn-outline-primary" type="button" onclick="copyPassword()">
                                        <i class="bi bi-clipboard me-1"></i> Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="text-muted small mt-2 fst-italic" style="font-size: 11px;">
                            <i class="bi bi-shield-exclamation text-warning me-1"></i>
                            This temporary password is shown only once to authorized administrators. It is safely hashed in the database and cannot be recovered if closed.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-2 rounded bg-light text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Login Account: <strong>Not Created</strong> (Can be provisioned later via Athlete Details/Edit).
                    </div>
                <?php endif; ?>

                <div class="d-flex align-items-center gap-2">
                    <a href="/athletes/<?= (int)$successData['id'] ?>" class="ks-btn ks-btn-primary px-4 py-2">
                        <i class="bi bi-person-lines-fill"></i> View Athlete Details
                    </a>
                    <a href="/athletes/<?= (int)$successData['id'] ?>/edit" class="btn btn-outline-secondary px-3 py-2" style="border-radius: 8px; font-size: 13px;">
                        <i class="bi bi-pencil"></i> Edit Athlete
                    </a>
                    <a href="/athletes" class="btn btn-outline-secondary px-3 py-2 ms-auto" style="border-radius: 8px; font-size: 13px;">
                        <i class="bi bi-arrow-left"></i> Back to Athletes List
                    </a>
                </div>
            </div>
        </div>
        <script>
            function copyPassword() {
                var copyText = document.getElementById("oneTimePassword");
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(copyText.value);
                alert("Temporary credentials copied to clipboard!");
            }
        </script>
    <?php endif; ?>

    <!-- Rule 10: Create Page Header (Back link + Title + Action Bar) -->
    <div class="mb-3">
        <a href="/athletes" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
            <i class="bi bi-arrow-left"></i> Back to Athletes
        </a>
        <div class="ks-page-header">
            <div>
                <h1 class="ks-page-title mb-1">Create Athlete</h1>
                <p class="text-muted small mb-0">Basic registration information required to register an athlete in KhelSutra.</p>
            </div>
            <div class="ks-page-actions">
                <a href="/athletes" class="btn btn-outline-secondary px-3 py-2" style="border-radius: 8px; font-size: 13px;">Cancel</a>
                <button type="submit" form="createAthleteForm" class="ks-btn ks-btn-primary px-4 py-2">
                    <i class="bi bi-check-lg"></i>
                    <span>Create Athlete</span>
                </button>
            </div>
        </div>
    </div>

    <form id="createAthleteForm" action="/athletes/create" method="POST" enctype="multipart/form-data">
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
                        <input type="text" name="first_name" class="ks-form-control" required placeholder="e.g. Aarav" value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="ks-form-control" placeholder="Optional" value="<?= htmlspecialchars($_POST['middle_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="ks-form-control" required placeholder="e.g. Patel" value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="ks-form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" id="dobInput" class="ks-form-control" required value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8') ?>" onchange="checkMinorStatus()">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="ks-form-select" required>
                            <option value="">Select Gender</option>
                            <option value="male" <?= (($_POST['gender'] ?? '') === 'male') ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
                            <option value="other" <?= (($_POST['gender'] ?? '') === 'other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Blood Group</label>
                        <select name="blood_group" class="ks-form-select">
                            <option value="">Select Blood Group</option>
                            <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                                <option value="<?= $bg ?>" <?= (($_POST['blood_group'] ?? '') === $bg) ? 'selected' : '' ?>><?= $bg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label">Phone Number</label>
                        <input type="tel" name="phone" id="athletePhone" class="ks-form-control" placeholder="+91 98765 43210" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Email Address</label>
                        <input type="email" name="email" id="athleteEmail" class="ks-form-control" placeholder="athlete@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" oninput="syncLoginEmail()">
                    </div>

                    <div class="col-12">
                        <label class="ks-form-label">Address Line</label>
                        <input type="text" name="address_line1" class="ks-form-control" placeholder="Flat / Building, Street, Area" value="<?= htmlspecialchars($_POST['address_line1'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">City</label>
                        <input type="text" name="city" class="ks-form-control" value="<?= htmlspecialchars($_POST['city'] ?? 'Pune', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">State</label>
                        <input type="text" name="state" class="ks-form-control" value="<?= htmlspecialchars($_POST['state'] ?? 'Maharashtra', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Postal Code</label>
                        <input type="text" name="postal_code" class="ks-form-control" placeholder="411001" value="<?= htmlspecialchars($_POST['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Sports & Roster Information -->
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
                        <select name="current_sport_id" id="sportSelect" class="ks-form-select" required onchange="filterTeamsBySport()">
                            <option value="">Select Primary Sport</option>
                            <?php foreach ($sportsList as $sp): ?>
                                <option value="<?= $sp['id'] ?>" <?= (($_POST['current_sport_id'] ?? '') == $sp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Assigned Team (Optional)</label>
                        <select name="team_id" id="teamSelect" class="ks-form-select">
                            <option value="">No Team (Individual Athlete)</option>
                            <?php foreach ($teamsList as $tm): ?>
                                <option value="<?= $tm['id'] ?>" data-sport-id="<?= $tm['sport_id'] ?? '' ?>" <?= (($_POST['team_id'] ?? '') == $tm['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tm['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($tm['team_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Athlete Status <span class="text-danger">*</span></label>
                        <select name="status" class="ks-form-select" required>
                            <option value="active" <?= (($_POST['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            <option value="injured" <?= (($_POST['status'] ?? '') === 'injured') ? 'selected' : '' ?>>Injured</option>
                            <option value="suspended" <?= (($_POST['status'] ?? '') === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Registration Date</label>
                        <input type="date" name="registration_date" class="ks-form-control" value="<?= htmlspecialchars($_POST['registration_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="ks-form-label">Administrative Notes</label>
                        <input type="text" name="notes" class="ks-form-control" placeholder="Any special coaching requirements, medical alerts, or notes" value="<?= htmlspecialchars($_POST['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Guardian / Emergency Contact -->
        <div class="ks-content-card mb-4">
            <div class="ks-card-header">
                <div class="ks-header-left">
                    <i class="bi bi-shield-check text-primary fs-5"></i>
                    <h3 class="ks-header-title">3. Guardian / Emergency Contact</h3>
                </div>
                <div id="minorNotice" class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1" style="display: none;">
                    <i class="bi bi-exclamation-circle me-1"></i> Minor Athlete Detected (< 18 Years)
                </div>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="ks-form-label">Guardian First Name</label>
                        <input type="text" name="guardian_first_name" id="guardianFirstName" class="ks-form-control" placeholder="First Name" value="<?= htmlspecialchars($_POST['guardian_first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Guardian Last Name</label>
                        <input type="text" name="guardian_last_name" id="guardianLastName" class="ks-form-control" placeholder="Last Name" value="<?= htmlspecialchars($_POST['guardian_last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Relationship</label>
                        <select name="guardian_relationship" class="ks-form-select">
                            <option value="Father" <?= (($_POST['guardian_relationship'] ?? '') === 'Father') ? 'selected' : '' ?>>Father</option>
                            <option value="Mother" <?= (($_POST['guardian_relationship'] ?? '') === 'Mother') ? 'selected' : '' ?>>Mother</option>
                            <option value="Guardian" <?= (($_POST['guardian_relationship'] ?? 'Guardian') === 'Guardian') ? 'selected' : '' ?>>Legal Guardian</option>
                            <option value="Spouse" <?= (($_POST['guardian_relationship'] ?? '') === 'Spouse') ? 'selected' : '' ?>>Spouse</option>
                            <option value="Sibling" <?= (($_POST['guardian_relationship'] ?? '') === 'Sibling') ? 'selected' : '' ?>>Sibling</option>
                            <option value="Other" <?= (($_POST['guardian_relationship'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label">Guardian Phone</label>
                        <input type="tel" name="guardian_phone" id="guardianPhone" class="ks-form-control" placeholder="+91 98765 00000" value="<?= htmlspecialchars($_POST['guardian_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Guardian Email</label>
                        <input type="email" name="guardian_email" class="ks-form-control" placeholder="guardian@example.com" value="<?= htmlspecialchars($_POST['guardian_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-12">
                        <div class="form-check pt-1">
                            <input class="form-check-input" type="checkbox" name="is_emergency_contact" value="1" id="chkEmergency" checked>
                            <label class="form-check-label small fw-medium text-dark" for="chkEmergency">
                                Designate this guardian/contact as the primary emergency contact
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Athlete Documents (Repeatable UI) -->
        <div class="ks-content-card mb-4">
            <div class="ks-card-header d-flex align-items-center justify-content-between">
                <div class="ks-header-left">
                    <i class="bi bi-file-earmark-arrow-up-fill text-primary fs-5"></i>
                    <h3 class="ks-header-title">4. Athlete Documents</h3>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addDocumentRow()">
                    <i class="bi bi-plus-lg me-1"></i> Add Document
                </button>
            </div>
            <div class="p-4">
                <p class="text-muted small mb-3">
                    Attach identity proof, birth certificate, medical clearance, or sports certificates. Supported formats: <strong>PDF, JPG, PNG</strong> (Max 10MB per file).
                </p>

                <div id="documentsContainer">
                    <!-- Dynamic Document Rows will be inserted here -->
                </div>
            </div>
        </div>

        <!-- Section 5: Athlete Account / Login Access -->
        <div class="ks-content-card mb-4">
            <div class="ks-card-header">
                <div class="ks-header-left">
                    <i class="bi bi-person-lock text-primary fs-5"></i>
                    <h3 class="ks-header-title">5. Athlete Account / Login Access</h3>
                </div>
            </div>
            <div class="p-4">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" name="create_account" value="1" id="chkCreateAccount" onchange="toggleAccountSection()" <?= (!empty($_POST['create_account'])) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold text-dark" for="chkCreateAccount" style="font-size: 14px;">
                        Create login account for this athlete
                    </label>
                    <div class="text-muted small">Allows the athlete to access KhelSutra.</div>
                </div>

                <div id="accountConfigSection" style="display: none;" class="p-3 bg-light rounded-3 border">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="ks-form-label">Login Email / Username <span class="text-danger">*</span></label>
                            <input type="email" name="login_email" id="loginEmailInput" class="ks-form-control" placeholder="athlete.login@example.com" value="<?= htmlspecialchars($_POST['login_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <small class="text-muted">Unique login identifier for the Athlete role in your organisation.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Account Status</label>
                            <select name="account_status" class="ks-form-select">
                                <option value="active" <?= (($_POST['account_status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active (Immediate login allowed)</option>
                                <option value="pending" <?= (($_POST['account_status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending Setup</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="ks-form-label d-block mb-2">Authentication Method</label>
                            <div class="d-flex align-items-center gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="auth_method" id="authMethodAuto" value="auto" checked onchange="toggleManualPassword()">
                                    <label class="form-check-label small fw-medium" for="authMethodAuto">
                                        Auto-generate secure temporary password (Recommended)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="auth_method" id="authMethodManual" value="manual" onchange="toggleManualPassword()">
                                    <label class="form-check-label small fw-medium" for="authMethodManual">
                                        Specify temporary password manually
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6" id="manualPasswordField" style="display: none;">
                            <label class="ks-form-label">Specify Password (Min 8 characters)</label>
                            <input type="password" name="password" id="manualPasswordInput" class="ks-form-control" placeholder="Enter temporary password">
                        </div>

                        <div class="col-12 text-muted small">
                            <i class="bi bi-shield-lock text-success me-1"></i>
                            Role assigned: <strong>Athlete (Role ID 5)</strong>. Passwords are encrypted using bcrypt hashing. The one-time credentials will be shown upon creation for secure delivery.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 6: Review & Form Actions -->
        <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-3 border mb-5">
            <div class="text-muted small">
                <i class="bi bi-info-circle me-1"></i> Please review all athlete details before submission.
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="/athletes" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px; font-size: 13px;">Cancel</a>
                <button type="submit" class="ks-btn ks-btn-primary px-4 py-2">
                    <i class="bi bi-check-lg"></i>
                    <span>Create Athlete Profile</span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    var docIndex = 0;

    function addDocumentRow() {
        var container = document.getElementById('documentsContainer');
        var rowId = 'doc_row_' + docIndex;

        var div = document.createElement('div');
        div.id = rowId;
        div.className = 'p-3 mb-3 bg-light rounded border';
        div.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-2">
                <strong class="text-dark small"><i class="bi bi-file-earmark-text text-primary me-1"></i> Document #` + (docIndex + 1) + `</strong>
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeDocumentRow('` + rowId + `')">
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-dark mb-1">Document Type *</label>
                    <select name="doc_type[]" class="form-select form-select-sm" required onchange="handleDocTypeChange(this, '` + rowId + `')">
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
                    <label class="form-label small fw-semibold text-dark mb-1">Document Title / Name</label>
                    <input type="text" name="doc_name[]" class="form-control form-control-sm doc-name-input" placeholder="e.g. Aadhaar Card">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-dark mb-1">Document Number</label>
                    <input type="text" name="doc_number[]" class="form-control form-control-sm" placeholder="e.g. 1234-5678-9012">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-dark mb-1">Upload File *</label>
                    <input type="file" name="doc_files[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-dark mb-1">Issue Date</label>
                    <input type="date" name="doc_issue_date[]" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-dark mb-1">Expiry Date</label>
                    <input type="date" name="doc_expiry_date[]" class="form-control form-control-sm">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark mb-1">Notes / Remarks</label>
                    <input type="text" name="doc_notes[]" class="form-control form-control-sm" placeholder="e.g. Verified original on registration">
                </div>
            </div>
        `;
        container.appendChild(div);
        docIndex++;
    }

    function removeDocumentRow(rowId) {
        var el = document.getElementById(rowId);
        if (el) el.remove();
    }

    function handleDocTypeChange(selectElem, rowId) {
        var row = document.getElementById(rowId);
        if (!row) return;
        var nameInput = row.querySelector('.doc-name-input');
        if (nameInput) {
            var selectedText = selectElem.options[selectElem.selectedIndex].text;
            if (selectElem.value !== 'other') {
                nameInput.value = selectedText;
            } else {
                nameInput.value = '';
                nameInput.placeholder = 'Specify document title';
            }
        }
    }

    function toggleAccountSection() {
        var chk = document.getElementById('chkCreateAccount');
        var sec = document.getElementById('accountConfigSection');
        if (chk.checked) {
            sec.style.display = 'block';
            syncLoginEmail();
        } else {
            sec.style.display = 'none';
        }
    }

    function syncLoginEmail() {
        var athEmail = document.getElementById('athleteEmail').value.trim();
        var loginEmailInput = document.getElementById('loginEmailInput');
        if (athEmail && (!loginEmailInput.value || loginEmailInput.value === loginEmailInput.dataset.synced)) {
            loginEmailInput.value = athEmail;
            loginEmailInput.dataset.synced = athEmail;
        }
    }

    function toggleManualPassword() {
        var isManual = document.getElementById('authMethodManual').checked;
        var field = document.getElementById('manualPasswordField');
        var input = document.getElementById('manualPasswordInput');
        if (isManual) {
            field.style.display = 'block';
            input.required = true;
        } else {
            field.style.display = 'none';
            input.required = false;
        }
    }

    function checkMinorStatus() {
        var dobVal = document.getElementById('dobInput').value;
        if (!dobVal) return;
        var dob = new Date(dobVal);
        var diff = Date.now() - dob.getTime();
        var ageDate = new Date(diff);
        var age = Math.abs(ageDate.getUTCFullYear() - 1970);
        var notice = document.getElementById('minorNotice');
        var gFirst = document.getElementById('guardianFirstName');
        var gLast = document.getElementById('guardianLastName');
        var gPhone = document.getElementById('guardianPhone');

        if (age < 18) {
            notice.style.display = 'inline-block';
            notice.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> Minor Athlete (' + age + ' Years) — Guardian Details Required';
            gFirst.required = true;
            gLast.required = true;
            gPhone.required = true;
        } else {
            notice.style.display = 'none';
            gFirst.required = false;
            gLast.required = false;
            gPhone.required = false;
        }
    }

    function filterTeamsBySport() {
        var sportId = document.getElementById('sportSelect').value;
        var teamSelect = document.getElementById('teamSelect');
        var options = teamSelect.querySelectorAll('option');

        options.forEach(function(opt) {
            if (!opt.value) {
                opt.style.display = 'block';
                return;
            }
            var teamSportId = opt.getAttribute('data-sport-id');
            if (!sportId || !teamSportId || teamSportId === sportId) {
                opt.style.display = 'block';
            } else {
                opt.style.display = 'none';
            }
        });
    }

    // Initialize initial state on load
    document.addEventListener('DOMContentLoaded', function() {
        toggleAccountSection();
        checkMinorStatus();
        filterTeamsBySport();
    });
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
