<?php
$activePage = 'athletes';
$title = 'Add Athlete — KhelSutra';

$orgId = current_organization_id();
$pdo = \App\Services\BaseService::getDatabaseConnection();
$sportsList = $pdo->query("SELECT id, name FROM sports WHERE status = 'active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT id, name, team_code FROM teams WHERE organization_id = :org_id AND deleted_at IS NULL ORDER BY name ASC");
$stmt->execute([':org_id' => $orgId]);
$teamsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<!-- Rule 10: Create Page Header (Back link + Title + Action Bar) -->
<div class="mb-3">
    <a href="/athletes" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
        <i class="bi bi-arrow-left"></i> Back to Athletes
    </a>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h1 class="ks-page-title mb-0">Add Athlete</h1>
        <div class="d-flex align-items-center gap-2">
            <a href="/athletes" class="btn btn-outline-secondary px-3 py-2" style="border-radius: 8px; font-size: 13px;">Cancel</a>
            <button type="submit" form="createAthleteForm" class="ks-btn ks-btn-primary px-4 py-2">
                <i class="bi bi-check-lg"></i>
                <span>Save Athlete</span>
            </button>
        </div>
    </div>
</div>

<form id="createAthleteForm" action="/athletes/create" method="POST">
    <!-- Section 1: Personal Information -->
    <div class="ks-content-card mb-4">
        <div class="ks-card-header">
            <div class="ks-header-left">
                <i class="bi bi-person-fill text-primary fs-5"></i>
                <h3 class="ks-header-title">Personal Information</h3>
            </div>
        </div>
        <div class="p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="ks-form-label">First Name *</label>
                    <input type="text" name="first_name" class="ks-form-control" required placeholder="e.g. Aarav">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="ks-form-control" placeholder="Optional">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Last Name *</label>
                    <input type="text" name="last_name" class="ks-form-control" required placeholder="e.g. Patel">
                </div>

                <div class="col-md-4">
                    <label class="ks-form-label">Date of Birth *</label>
                    <input type="date" name="date_of_birth" class="ks-form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Gender *</label>
                    <select name="gender" class="ks-form-select" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Blood Group</label>
                    <select name="blood_group" class="ks-form-select">
                        <option value="">Select Blood Group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="ks-form-label">Phone Number *</label>
                    <input type="tel" name="phone" class="ks-form-control" required placeholder="+91 98765 43210">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Email Address</label>
                    <input type="email" name="email" class="ks-form-control" placeholder="athlete@example.com">
                </div>

                <div class="col-12">
                    <label class="ks-form-label">Residential Address</label>
                    <input type="text" name="address_line1" class="ks-form-control" placeholder="Street Address, Area, Apartment">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">City</label>
                    <input type="text" name="city" class="ks-form-control" value="Pune">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Postal Code</label>
                    <input type="text" name="postal_code" class="ks-form-control" placeholder="411001">
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Sport & Squad Assignment -->
    <div class="ks-content-card mb-4">
        <div class="ks-card-header">
            <div class="ks-header-left">
                <i class="bi bi-trophy-fill text-primary fs-5"></i>
                <h3 class="ks-header-title">Sport & Team Assignment</h3>
            </div>
        </div>
        <div class="p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="ks-form-label">Primary Sport *</label>
                    <select name="current_sport_id" class="ks-form-select" required>
                        <?php foreach ($sportsList as $sp): ?>
                            <option value="<?= $sp['id'] ?>"><?= htmlspecialchars($sp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Assign to Squad / Team</label>
                    <select name="team_id" class="ks-form-select">
                        <option value="">No Team (Individual Roster)</option>
                        <?php foreach ($teamsList as $tm): ?>
                            <option value="<?= $tm['id'] ?>"><?= htmlspecialchars($tm['name']) ?> (<?= htmlspecialchars($tm['team_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Guardian Information -->
    <div class="ks-content-card mb-4">
        <div class="ks-card-header">
            <div class="ks-header-left">
                <i class="bi bi-shield-check text-primary fs-5"></i>
                <h3 class="ks-header-title">Guardian & Emergency Contact</h3>
            </div>
        </div>
        <div class="p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="ks-form-label">Guardian Full Name *</label>
                    <input type="text" name="guardian_name" class="ks-form-control" required placeholder="Parent or Guardian Name">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Relationship *</label>
                    <input type="text" name="guardian_relationship" class="ks-form-control" required placeholder="e.g. Father, Mother, Guardian">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Guardian Contact Number *</label>
                    <input type="tel" name="guardian_phone" class="ks-form-control" required placeholder="+91 98765 00000">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Guardian Email</label>
                    <input type="email" name="guardian_email" class="ks-form-control" placeholder="guardian@example.com">
                </div>
                <div class="col-md-6 d-flex align-items-center pt-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_emergency_contact" value="1" id="chkEmergency" checked>
                        <label class="form-check-label small fw-medium" for="chkEmergency">
                            Mark as primary emergency contact
                        </label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="ks-form-label">Medical or Operational Notes</label>
                    <textarea name="notes" class="ks-form-control" rows="2" placeholder="Any allergies, special coaching needs, or training requirements"></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Rule 12: Form Actions at Bottom -->
    <div class="d-flex align-items-center justify-content-end gap-2 pb-5">
        <a href="/athletes" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px; font-size: 13px;">Cancel</a>
        <button type="submit" class="ks-btn ks-btn-primary px-4 py-2">
            <i class="bi bi-check-lg"></i>
            <span>Create Athlete Profile</span>
        </button>
    </div>
</form>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
