<?php
$activePage = 'tournaments';
$title = 'Add Tournament — KhelSutra';
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
$isCoach = ($currentRoleSlug === 'coach') || ($currentRoleId === 4) || !empty($currentUser['coach_id']);

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

$canCreateTournament = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'tournament.create', $orgId) ||
    $permissionService->hasPermission($userPayload, 'tournament.manage', $orgId)
);

if (!$canCreateTournament) {
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo '<div class="p-5 text-center text-danger fw-bold">403 Forbidden: You do not have permission to create tournaments.</div>';
    return;
}

$db = \App\Services\BaseService::getDatabaseConnection();

// Sports
$sportService = new \App\Services\Sport\SportService();
$sports = [];
foreach (config('sports.catalog') ?? [] as $key => $name) {
    $dbId = $sportService->resolveSportId($key);
    $sports[] = ['id' => $dbId, 'name' => $name];
}
usort($sports, fn($a, $b) => strcmp($a['name'], $b['name']));

$levels = [];
$formats = [];
$venues = [];
$teams = [];

if ($db) {
    $lvlStmt = $db->query("SELECT id, name FROM tournament_levels ORDER BY id ASC");
    $levels = $lvlStmt ? $lvlStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $fmtStmt = $db->query("SELECT id, name FROM tournament_formats ORDER BY id ASC");
    $formats = $fmtStmt ? $fmtStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $venueStmt = $db->prepare("SELECT v.id, v.name, GROUP_CONCAT(vs.sport_id) as sport_ids FROM venues v LEFT JOIN venue_sports vs ON v.id = vs.venue_id WHERE v.organization_id = :org_id AND v.status = 'active' AND v.deleted_at IS NULL GROUP BY v.id, v.name ORDER BY v.name ASC");
    $venueStmt->execute([':org_id' => $orgId]);
    $venues = $venueStmt ? $venueStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $teamStmt = $db->prepare("SELECT id, name, team_code, sport_id FROM teams WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
    $teamStmt->execute([':org_id' => $orgId]);
    $teams = $teamStmt ? $teamStmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

ob_start();
?>

<div class="ks-page">
    <!-- Page Header -->
    <div class="mb-3">
        <a href="/tournaments" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1 mb-2" style="font-size: 12.5px;">
            <i class="bi bi-arrow-left"></i> Back to Tournaments
        </a>
        <div class="ks-page-header mb-0">
            <div>
                <h1 class="ks-page-title mb-1">Add Tournament</h1>
                <p class="text-muted small mb-0">Create a new tournament championship, configure format, and enroll participating squads.</p>
            </div>
            <div class="ks-header-actions">
                <a href="/tournaments" class="btn btn-outline-secondary px-3 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">Cancel</a>
                <button type="submit" form="createTournamentForm" class="ks-btn ks-btn-primary px-4 py-2">
                    <i class="bi bi-check-lg"></i>
                    <span>Save Tournament</span>
                </button>
            </div>
        </div>
    </div>

    <form action="/tournaments/create" method="POST" id="createTournamentForm">
        <!-- Section 1: Tournament Profile -->
        <div class="ks-content-card mb-3">
            <div class="ks-card-header py-2 px-3">
                <div class="ks-header-left">
                    <i class="bi bi-trophy-fill text-primary fs-6"></i>
                    <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">1. Tournament Information</h3>
                </div>
            </div>
            <div class="p-3 px-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ks-form-label">Tournament Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="ks-form-control" placeholder="e.g. Maharashtra Inter-Academy Football Championship 2026" required>
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Sport <span class="text-danger">*</span></label>
                        <select name="sport_id" id="sportSelect" class="ks-form-select" required>
                            <option value="">Select sport</option>
                            <?php foreach ($sports as $s): ?>
                                <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="ks-form-label">Competition Level <span class="text-danger">*</span></label>
                        <select name="tournament_level_id" class="ks-form-select" required>
                            <?php foreach ($levels as $lvl): ?>
                                <option value="<?= (int)$lvl['id'] ?>"><?= htmlspecialchars($lvl['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Tournament Format <span class="text-danger">*</span></label>
                        <select name="tournament_format_id" class="ks-form-select" required>
                            <?php foreach ($formats as $fmt): ?>
                                <option value="<?= (int)$fmt['id'] ?>"><?= htmlspecialchars($fmt['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="ks-form-select" required>
                            <option value="draft">Draft</option>
                            <option value="registration_open">Registration Open</option>
                            <option value="ongoing">Ongoing</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="ks-form-label">Organizer / Sponsoring Body</label>
                        <input type="text" name="organizer_name" class="ks-form-control" value="Apex Sports Academy">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Dates & Location -->
        <div class="ks-content-card mb-3">
            <div class="ks-card-header py-2 px-3">
                <div class="ks-header-left">
                    <i class="bi bi-geo-alt-fill text-primary fs-6"></i>
                    <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">2. Dates &amp; Location</h3>
                </div>
            </div>
            <div class="p-3 px-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ks-form-label">Tournament Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="ks-form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Tournament End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="ks-form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label">Primary Venue <span class="text-danger">*</span></label>
                        <select name="venue_id" id="venueSelect" class="ks-form-select" required>
                            <option value="">Select venue</option>
                            <?php foreach ($venues as $v): ?>
                                <option value="<?= (int)$v['id'] ?>" data-sports="<?= htmlspecialchars($v['sport_ids'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="ks-form-label">City</label>
                        <input type="text" name="city" class="ks-form-control" value="Mumbai">
                    </div>
                    <div class="col-md-3">
                        <label class="ks-form-label">State</label>
                        <input type="text" name="state" class="ks-form-control" value="Maharashtra">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Participating Teams -->
        <div class="ks-content-card mb-3">
            <div class="ks-card-header py-2 px-3">
                <div class="ks-header-left">
                    <i class="bi bi-people-fill text-primary fs-6"></i>
                    <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">3. Participating Squads (Initial Enrolment)</h3>
                </div>
            </div>
            <div class="p-3 px-4">
                <p class="text-muted small mb-3">Select teams to enroll into this tournament. Standings will be initialized automatically.</p>

                <div class="row g-2" id="teamSquadContainer" style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($teams as $tm): ?>
                        <div class="col-md-4 col-sm-6 team-squad-item" data-sport="<?= (int)($tm['sport_id'] ?? 0) ?>">
                            <div class="form-check p-2 border rounded" style="background: #F8FAFC;">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="team_ids[]" value="<?= (int)$tm['id'] ?>" id="tm_<?= (int)$tm['id'] ?>">
                                <label class="form-check-label small fw-medium text-dark" for="tm_<?= (int)$tm['id'] ?>">
                                    <?= htmlspecialchars($tm['name'], ENT_QUOTES, 'UTF-8') ?>
                                    <span class="text-muted" style="font-size: 11px;">(<?= htmlspecialchars($tm['team_code'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-muted small mb-0 mt-2" id="noTeamsForSport" style="display: none;">No teams available for this sport.</p>
            </div>
        </div>

        <!-- Section 4: Rules & Guidelines -->
        <div class="ks-content-card mb-4">
            <div class="ks-card-header py-2 px-3">
                <div class="ks-header-left">
                    <i class="bi bi-card-text text-primary fs-6"></i>
                    <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">4. Description &amp; Competition Rules</h3>
                </div>
            </div>
            <div class="p-3 px-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="ks-form-label">Tournament Description</label>
                        <textarea name="description" class="ks-form-control" rows="2" style="height: auto;" placeholder="Overview of the tournament, eligibility criteria, and prize pool"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="ks-form-label">Rules &amp; Regulations</label>
                        <textarea name="rules" class="ks-form-control" rows="2" style="height: auto;" placeholder="Match duration, points system, tie-breaker criteria"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex align-items-center justify-content-end gap-2 mb-4">
            <a href="/tournaments" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">
                Cancel
            </a>
            <button type="submit" class="ks-btn ks-btn-primary px-4 py-2">
                <i class="bi bi-check-lg"></i>
                <span>Save Tournament</span>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sportSelect = document.getElementById('sportSelect');
    const venueSelect = document.getElementById('venueSelect');
    
    if (sportSelect && venueSelect) {
        const allOptions = Array.from(venueSelect.options).map(opt => opt.cloneNode(true));
        
        sportSelect.addEventListener('change', function() {
            const selectedSport = this.value;
            const currentSelectedVenue = venueSelect.value;
            
            venueSelect.innerHTML = '';
            
            allOptions.forEach(opt => {
                if (opt.value === '') {
                    venueSelect.appendChild(opt.cloneNode(true));
                } else {
                    const sportsStr = opt.getAttribute('data-sports') || '';
                    const sportsArr = sportsStr.split(',');
                    
                    if (!selectedSport || sportsStr === '' || sportsArr.includes(selectedSport)) {
                        venueSelect.appendChild(opt.cloneNode(true));
                    }
                }
            });
            
            let match = Array.from(venueSelect.options).find(opt => opt.value === currentSelectedVenue);
            venueSelect.value = match ? currentSelectedVenue : '';

            const teamItems = document.querySelectorAll('.team-squad-item');
            const noTeamsMsg = document.getElementById('noTeamsForSport');
            let visibleCount = 0;
            teamItems.forEach(function(item) {
                const teamSport = item.getAttribute('data-sport');
                if (!selectedSport || teamSport === selectedSport) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                    const cb = item.querySelector('input[type="checkbox"]');
                    if (cb) cb.checked = false;
                }
            });
            if (noTeamsMsg) {
                noTeamsMsg.style.display = (selectedSport && visibleCount === 0) ? '' : 'none';
            }
        });
        
        const initialVenue = venueSelect.value;
        sportSelect.dispatchEvent(new Event('change'));
        if (initialVenue) venueSelect.value = initialVenue;
    }
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
