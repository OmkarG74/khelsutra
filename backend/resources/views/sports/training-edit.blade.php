<?php
$sessionId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();
$trainService = new \App\Services\Training\TrainingService();
$session = $trainService->getSession($orgId, $sessionId);

$db = \App\Services\BaseService::getDatabaseConnection();

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

$pageTitle = $session ? 'Edit Training Session — ' . htmlspecialchars($session['training_reference']) : 'Edit Training Session';
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
            <a href="/training/<?= (int)$session['id'] ?>" class="text-decoration-none text-muted small fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Session Details
            </a>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h4 fw-bold mb-0" style="color: var(--ks-navy);">Edit Session: <?= htmlspecialchars($session['training_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="text-muted small mt-1">Squad: <strong><?= htmlspecialchars($session['team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull; Sport: <strong><?= htmlspecialchars($session['sport_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></div>
            </div>
        </div>

        <form action="/training/<?= (int)$session['id'] ?>/edit" method="POST" id="editTrainingForm">
            <!-- Section 1: Session Details -->
            <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                    1. Session Information
                </h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Session Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($session['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Training Type <span class="text-danger">*</span></label>
                        <select name="training_type" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <?php foreach (['Tactical Drills', 'Physical Conditioning', 'Skill & Technique', 'Match Simulation', 'Recovery & Rehab', 'General Practice'] as $type): ?>
                                <option value="<?= $type ?>" <?= ($session['training_type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Lead Coach <span class="text-danger">*</span></label>
                        <select name="coach_id" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="">Select coach</option>
                            <?php foreach ($coaches as $c): ?>
                                <option value="<?= (int)$c['coach_id'] ?>" <?= ($session['coach_id'] ?? '') == $c['coach_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (' . $c['specialization'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Session Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="scheduled" <?= ($session['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="in_progress" <?= ($session['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= ($session['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= ($session['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
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
                        <input type="date" name="training_date" class="form-control" value="<?= htmlspecialchars($session['training_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars(substr($session['start_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars(substr($session['end_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Venue <span class="text-danger">*</span></label>
                        <select name="venue_id" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <?php foreach ($venues as $v): ?>
                                <option value="<?= (int)$v['id'] ?>" <?= ($session['venue_id'] ?? '') == $v['id'] ? 'selected' : '' ?>><?= htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Facility Slot</label>
                        <select name="facility_id" class="form-select" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="">General Grounds</option>
                            <?php foreach ($facilities as $f): ?>
                                <option value="<?= (int)$f['id'] ?>" <?= ($session['facility_id'] ?? '') == $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?></option>
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
                        <textarea name="objectives" class="form-control" rows="2" style="font-size: 13px; border-radius: var(--ks-radius-button);"><?= htmlspecialchars($session['objectives'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Coaching Notes / Equipment</label>
                        <textarea name="notes" class="form-control" rows="2" style="font-size: 13px; border-radius: var(--ks-radius-button);"><?= htmlspecialchars($session['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
                <a href="/training/<?= (int)$session['id'] ?>" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
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
