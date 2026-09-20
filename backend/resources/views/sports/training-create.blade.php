<?php
$pageTitle = 'Schedule Training — KhelSutra';
$activePage = 'training';
$orgId = current_organization_id();

$db = \App\Services\BaseService::getDatabaseConnection();

// Teams
$teamsStmt = $db->prepare("SELECT id, name FROM teams WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
$teamsStmt->execute([':org_id' => $orgId]);
$teams = $teamsStmt ? $teamsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Coaches
$coachStmt = $db->prepare("
    SELECT cp.id as coach_id, e.first_name, e.last_name, cp.specialization
    FROM coach_profiles cp
    JOIN employees e ON cp.employee_id = e.id
    WHERE cp.organization_id = :org_id AND cp.status = 'active' AND cp.deleted_at IS NULL AND e.deleted_at IS NULL
    ORDER BY e.first_name ASC
");
$coachStmt->execute([':org_id' => $orgId]);
$coaches = $coachStmt ? $coachStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Venues
$venueStmt = $db->prepare("SELECT id, name FROM venues WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
$venueStmt->execute([':org_id' => $orgId]);
$venues = $venueStmt ? $venueStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Facilities
$facStmt = $db->prepare("SELECT id, venue_id, name FROM venue_facilities WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
$facStmt->execute([':org_id' => $orgId]);
$facilities = $facStmt ? $facStmt->fetchAll(PDO::FETCH_ASSOC) : [];

ob_start();
?>

<div class="ks-content">
    <!-- Top Back Navigation -->
    <div class="mb-3">
        <a href="/training" class="text-decoration-none text-muted small fw-medium">
            <i class="bi bi-arrow-left me-1"></i> Back to Training
        </a>
    </div>

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold mb-0" style="color: var(--ks-navy);">Schedule Training</h1>
        </div>
    </div>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger mb-4 py-2 px-3 small d-flex align-items-center gap-2" style="border-radius: var(--ks-radius-button);">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <form action="/training/create" method="POST" id="createTrainingForm">
        <!-- Section 1: Session Details -->
        <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                1. Session Information
            </h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Session Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Morning Conditioning & Passing Drills" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Training Type <span class="text-danger">*</span></label>
                    <select name="training_type" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="Tactical Drills">Tactical Drills</option>
                        <option value="Physical Conditioning">Physical Conditioning</option>
                        <option value="Skill & Technique">Skill & Technique</option>
                        <option value="Match Simulation">Match Simulation</option>
                        <option value="Recovery & Rehab">Recovery & Rehab</option>
                        <option value="General Practice">General Practice</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Squad / Team <span class="text-danger">*</span></label>
                    <select name="team_id" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="">Select team</option>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Lead Coach <span class="text-danger">*</span></label>
                    <select name="coach_id" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="">Select coach</option>
                        <?php foreach ($coaches as $c): ?>
                            <option value="<?= (int)$c['coach_id'] ?>"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (' . $c['specialization'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 2: Date, Time & Venue -->
        <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                2. Schedule & Facility Slot
            </h5>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Training Date <span class="text-danger">*</span></label>
                    <input type="date" name="training_date" class="form-control" value="<?= date('Y-m-d') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Start Time <span class="text-danger">*</span></label>
                    <input type="time" name="start_time" class="form-control" value="07:00" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">End Time <span class="text-danger">*</span></label>
                    <input type="time" name="end_time" class="form-control" value="09:00" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Venue <span class="text-danger">*</span></label>
                    <select name="venue_id" id="venue_select" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="">Select venue</option>
                        <?php foreach ($venues as $v): ?>
                            <option value="<?= (int)$v['id'] ?>"><?= htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Facility Slot / Court</label>
                    <select name="facility_id" class="form-select" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="">General Grounds</option>
                        <?php foreach ($facilities as $f): ?>
                            <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 3: Objectives & Notes -->
        <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                3. Objectives & Drill Guidelines
            </h5>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-semibold text-dark">Session Objectives</label>
                    <textarea name="objectives" class="form-control" rows="2" placeholder="e.g. Focus on transition from midfield to attacking third with high press" style="font-size: 13px; border-radius: var(--ks-radius-button);"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold text-dark">Coaching Notes / Equipment Required</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Requires 20 agility cones, 15 bibs, 10 match footballs" style="font-size: 13px; border-radius: var(--ks-radius-button);"></textarea>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
            <a href="/training" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                <i class="bi bi-check2 me-1"></i> Schedule Session
            </button>
        </div>
    </form>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
