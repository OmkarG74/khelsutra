<?php
$activePage = 'training';
$title = 'Add Training Session — KhelSutra';
$pageTitle = $title;
$orgId = current_organization_id();

$currentRoleSlug = $_SESSION['auth']['role']['slug'] ?? ($_SESSION['role_slug'] ?? 'sports_admin');
$currentRoleId = (int)($_SESSION['auth']['role']['id'] ?? 2);
$currentUser = $_SESSION['auth']['user'] ?? [
    'id' => 102,
    'email' => 'sportsadmin@khelsutra.local',
    'role_id' => 2,
];
$userId = current_user_id() ?? (int)($currentUser['id'] ?? 102);

$isAthlete = ($currentRoleSlug === 'athlete') || ($currentRoleId === 5) || !empty($currentUser['athlete_id']);

$permissionService = new \App\Services\Rbac\PermissionService();
$userPayload = array_merge(
    $_SESSION['auth'] ?? [],
    $currentUser,
    [
        'id' => $userId,
        'role_id' => $currentRoleId,
        'role' => $_SESSION['auth']['role'] ?? ['id' => $currentRoleId, 'slug' => $currentRoleSlug],
        'permissions' => $_SESSION['auth']['permissions'] ?? ($currentUser['permissions'] ?? []),
    ]
);
$userPermissions = $permissionService->getUserPermissions($userId, $orgId);

$canCreate = !$isAthlete && (
           $permissionService->hasPermission($userPayload, 'training.create', $orgId)
        || $permissionService->hasPermission($userPayload, 'training.manage', $orgId)
        || in_array('training.create', $userPermissions, true)
        || in_array('training.manage', $userPermissions, true)
);

if (!$canCreate) {
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo '<div class="p-5 text-center text-danger fw-bold">403 Forbidden: You do not have permission to schedule training sessions.</div>';
    return;
}

$db = \App\Services\BaseService::getDatabaseConnection();

$teams = [];
$coaches = [];
$venues = [];
$facilities = [];

if ($db) {
    $teamsStmt = $db->prepare("SELECT id, name FROM teams WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
    $teamsStmt->execute([':org_id' => $orgId]);
    $teams = $teamsStmt ? $teamsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $coachStmt = $db->prepare("
        SELECT cp.id as coach_id, e.first_name, e.last_name, cp.specialization
        FROM coach_profiles cp
        JOIN employees e ON cp.employee_id = e.id AND e.deleted_at IS NULL
        WHERE cp.organization_id = :org_id AND cp.status = 'active' AND cp.deleted_at IS NULL
        ORDER BY e.first_name ASC
    ");
    $coachStmt->execute([':org_id' => $orgId]);
    $coaches = $coachStmt ? $coachStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $venueStmt = $db->prepare("SELECT id, name FROM venues WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
    $venueStmt->execute([':org_id' => $orgId]);
    $venues = $venueStmt ? $venueStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $facStmt = $db->prepare("SELECT id, venue_id, name FROM venue_facilities WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
    $facStmt->execute([':org_id' => $orgId]);
    $facilities = $facStmt ? $facStmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

ob_start();
?>

<div class="ks-page">
    <!-- Page Header -->
    <div class="mb-3">
        <a href="/training" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1 mb-2" style="font-size: 12.5px;">
            <i class="bi bi-arrow-left"></i> Back to Training
        </a>
        <div class="ks-page-header mb-0">
            <div>
                <h1 class="ks-page-title mb-1">Add Training Session</h1>
                <p class="text-muted small mb-0">Schedule a team training session, assign a lead coach and venue, and set drill objectives.</p>
            </div>
            <div class="ks-header-actions">
                <a href="/training" class="btn btn-outline-secondary px-3 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">Cancel</a>
                <button type="submit" form="createTrainingForm" class="ks-btn ks-btn-primary px-4 py-2">
                    <i class="bi bi-check-lg"></i>
                    <span>Save Training Session</span>
                </button>
            </div>
        </div>
    </div>

    <form action="/training/create" method="POST" id="createTrainingForm">
        <!-- Section 1: Session Information -->
        <div class="ks-content-card mb-3">
            <div class="ks-card-header py-2 px-3">
                <div class="ks-header-left">
                    <i class="bi bi-stopwatch text-primary fs-6"></i>
                    <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">1. Session Information</h3>
                </div>
            </div>
            <div class="p-3 px-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ks-form-label" for="training-title">Session Title <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            id="training-title"
                            name="title"
                            class="ks-form-control"
                            placeholder="e.g. Morning Conditioning &amp; Passing Drills"
                            required
                        >
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label" for="training-type">Training Type <span class="text-danger">*</span></label>
                        <select id="training-type" name="training_type" class="ks-form-select" required>
                            <option value="Tactical Drills">Tactical Drills</option>
                            <option value="Physical Conditioning">Physical Conditioning</option>
                            <option value="Skill & Technique">Skill &amp; Technique</option>
                            <option value="Match Simulation">Match Simulation</option>
                            <option value="Recovery & Rehab">Recovery &amp; Rehab</option>
                            <option value="General Practice">General Practice</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label" for="training-team">Team <span class="text-danger">*</span></label>
                        <select id="training-team" name="team_id" class="ks-form-select" required>
                            <option value="">Select team</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label" for="training-coach">Lead Coach <span class="text-danger">*</span></label>
                        <select id="training-coach" name="coach_id" class="ks-form-select" required>
                            <option value="">Select coach</option>
                            <?php foreach ($coaches as $c): ?>
                                <option value="<?= (int)$c['coach_id'] ?>">
                                    <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . (!empty($c['specialization']) ? ' (' . $c['specialization'] . ')' : ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Date, Time & Venue -->
        <div class="ks-content-card mb-3">
            <div class="ks-card-header py-2 px-3">
                <div class="ks-header-left">
                    <i class="bi bi-calendar-event text-primary fs-6"></i>
                    <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">2. Schedule &amp; Venue</h3>
                </div>
            </div>
            <div class="p-3 px-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="ks-form-label" for="training-date">Training Date <span class="text-danger">*</span></label>
                        <input
                            type="date"
                            id="training-date"
                            name="training_date"
                            class="ks-form-control"
                            value="<?= date('Y-m-d') ?>"
                            required
                        >
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label" for="training-start-time">Start Time <span class="text-danger">*</span></label>
                        <input
                            type="time"
                            id="training-start-time"
                            name="start_time"
                            class="ks-form-control"
                            value="07:00"
                            required
                        >
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label" for="training-end-time">End Time <span class="text-danger">*</span></label>
                        <input
                            type="time"
                            id="training-end-time"
                            name="end_time"
                            class="ks-form-control"
                            value="09:00"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label" for="venue_select">Venue <span class="text-danger">*</span></label>
                        <select name="venue_id" id="venue_select" class="ks-form-select" required>
                            <option value="">Select venue</option>
                            <?php foreach ($venues as $v): ?>
                                <option value="<?= (int)$v['id'] ?>"><?= htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label" for="facility_select">Facility Slot / Court</label>
                        <select name="facility_id" id="facility_select" class="ks-form-select">
                            <option value="">General Grounds</option>
                            <?php foreach ($facilities as $f): ?>
                                <option value="<?= (int)$f['id'] ?>" data-venue-id="<?= (int)$f['venue_id'] ?>">
                                    <?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Objectives & Notes -->
        <div class="ks-content-card mb-4">
            <div class="ks-card-header py-2 px-3">
                <div class="ks-header-left">
                    <i class="bi bi-journal-text text-primary fs-6"></i>
                    <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">3. Objectives &amp; Coaching Notes</h3>
                </div>
            </div>
            <div class="p-3 px-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="ks-form-label" for="training-objectives">Session Objectives</label>
                        <textarea
                            id="training-objectives"
                            name="objectives"
                            class="ks-form-control"
                            rows="2"
                            style="height: auto;"
                            placeholder="e.g. Focus on transition from midfield to attacking third with high press"
                        ></textarea>
                    </div>
                    <div class="col-12">
                        <label class="ks-form-label" for="training-notes">Coaching Notes / Equipment Required</label>
                        <textarea
                            id="training-notes"
                            name="notes"
                            class="ks-form-control"
                            rows="2"
                            style="height: auto;"
                            placeholder="e.g. Requires 20 agility cones, 15 bibs, 10 match footballs"
                        ></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Standardized Form Actions -->
        <div class="d-flex align-items-center justify-content-end gap-2 mb-5">
            <a href="/training" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">
                Cancel
            </a>
            <button type="submit" class="ks-btn ks-btn-primary px-4 py-2" id="btnSaveTrainingSession">
                <i class="bi bi-check-lg"></i>
                <span>Save Training Session</span>
            </button>
        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var venueSelect = document.getElementById('venue_select');
        var facSelect = document.getElementById('facility_select');
        if (!venueSelect || !facSelect) return;

        function filterFacilities() {
            var selectedVenue = venueSelect.value;
            Array.from(facSelect.options).forEach(function (opt) {
                if (!opt.value) return;
                var optVenue = opt.getAttribute('data-venue-id');
                var match = !selectedVenue || optVenue === selectedVenue;
                opt.hidden = !match;
                if (!match && opt.selected) {
                    facSelect.value = '';
                }
            });
        }

        venueSelect.addEventListener('change', filterFacilities);
        filterFacilities();
    });
    </script>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
