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
$teamsStmt = $db->prepare("SELECT id, name, sport_id FROM teams WHERE organization_id = :org_id AND deleted_at IS NULL ORDER BY name ASC");
$teamsStmt->execute([':org_id' => $orgId]);
$teams = $teamsStmt ? $teamsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$pageTitle = $athlete ? 'Edit Athlete — ' . htmlspecialchars($athlete['first_name'] . ' ' . $athlete['last_name']) : 'Edit Athlete';
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
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="/athletes/<?= (int)$athlete['id'] ?>" class="text-decoration-none text-muted small fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Athlete Details
            </a>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="h4 fw-bold mb-0" style="color: var(--ks-navy);">Edit Athlete: <?= htmlspecialchars($athlete['first_name'] . ' ' . $athlete['last_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="text-muted small mt-1">Code: <strong><?= htmlspecialchars($athlete['athlete_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull; Registered: <?= htmlspecialchars($athlete['registration_date'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <form action="/athletes/<?= (int)$athlete['id'] ?>/edit" method="POST" id="editAthleteForm">
            <!-- Section 1: Personal Information -->
            <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                    1. Personal Information
                </h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($athlete['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($athlete['middle_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($athlete['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($athlete['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="male" <?= ($athlete['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= ($athlete['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="other" <?= ($athlete['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Blood Group</label>
                        <select name="blood_group" class="form-select" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="">Select blood group</option>
                            <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                                <option value="<?= $bg ?>" <?= ($athlete['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($athlete['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($athlete['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Address Line</label>
                        <input type="text" name="address_line1" class="form-control" value="<?= htmlspecialchars($athlete['address_line1'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">City</label>
                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($athlete['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">State</label>
                        <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($athlete['state'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Postal Code</label>
                        <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($athlete['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                </div>
            </div>

            <!-- Section 2: Sport & Status -->
            <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                    2. Sports & Roster Information
                </h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Primary Sport <span class="text-danger">*</span></label>
                        <select name="current_sport_id" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="">Select sport</option>
                            <?php foreach ($sports as $sp): ?>
                                <option value="<?= (int)$sp['id'] ?>" <?= ($athlete['current_sport_id'] ?? '') == $sp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Assigned Team</label>
                        <select name="team_id" class="form-select" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="">No team assignment</option>
                            <?php foreach ($teams as $tm): ?>
                                <option value="<?= (int)$tm['id'] ?>" <?= ($athlete['team_id'] ?? '') == $tm['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tm['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Athlete Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="active" <?= ($athlete['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="injured" <?= ($athlete['status'] ?? '') === 'injured' ? 'selected' : '' ?>>Injured</option>
                            <option value="inactive" <?= ($athlete['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="suspended" <?= ($athlete['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Administrative Notes</label>
                        <textarea name="notes" class="form-control" rows="3" style="font-size: 13px; border-radius: var(--ks-radius-button);"><?= htmlspecialchars($athlete['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Section 3: Guardian Details -->
            <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                    3. Guardian / Emergency Contact
                </h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Guardian First Name</label>
                        <input type="text" name="guardian_first_name" class="form-control" value="<?= htmlspecialchars($athlete['guardian']['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Guardian Last Name</label>
                        <input type="text" name="guardian_last_name" class="form-control" value="<?= htmlspecialchars($athlete['guardian']['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Relationship</label>
                        <select name="guardian_relationship" class="form-select" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="Father" <?= ($athlete['guardian']['relationship'] ?? '') === 'Father' ? 'selected' : '' ?>>Father</option>
                            <option value="Mother" <?= ($athlete['guardian']['relationship'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother</option>
                            <option value="Guardian" <?= ($athlete['guardian']['relationship'] ?? '') === 'Guardian' ? 'selected' : '' ?>>Legal Guardian</option>
                            <option value="Other" <?= ($athlete['guardian']['relationship'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Guardian Phone</label>
                        <input type="text" name="guardian_phone" class="form-control" value="<?= htmlspecialchars($athlete['guardian']['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Guardian Email</label>
                        <input type="email" name="guardian_email" class="form-control" value="<?= htmlspecialchars($athlete['guardian']['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
                <a href="/athletes/<?= (int)$athlete['id'] ?>" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                    <i class="bi bi-check2 me-1"></i> Save Changes
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
