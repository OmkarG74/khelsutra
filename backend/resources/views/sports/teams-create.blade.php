<?php
$activePage = 'teams';
$title = 'Add Team — KhelSutra';
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

$canCreateTeam = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'team.create', $orgId) ||
    $permissionService->hasPermission($userPayload, 'team.manage', $orgId)
);

if (!$canCreateTeam) {
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo '<div class="p-5 text-center text-danger fw-bold">403 Forbidden: You do not have permission to create teams.</div>';
    return;
}

// Sports from config
$sportService = new \App\Services\Sport\SportService();
$sports = [];
foreach (config('sports.catalog') ?? [] as $key => $name) {
    $dbId = $sportService->resolveSportId($key);
    $sports[] = ['id' => (int)$dbId, 'name' => $name];
}
usort($sports, fn($a, $b) => strcmp($a['name'], $b['name']));

$teamService = new \App\Services\Team\TeamService();
$initialCoaches = $teamService->searchAvailableCoaches($orgId, null, null, null, 30);

ob_start();
?>

<div class="ks-page">
    <!-- Page Header -->
    <div class="mb-3">
        <a href="/teams" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1 mb-2" style="font-size: 12.5px;">
            <i class="bi bi-arrow-left"></i> Back to Teams
        </a>
        <div class="ks-page-header mb-0">
            <div>
                <h1 class="ks-page-title mb-1">Add Team</h1>
                <p class="text-muted small mb-0">Configure team profile, assign multi-coach staff, and select sport-eligible registered athletes.</p>
            </div>
            <div class="ks-header-actions">
                <a href="/teams" class="btn btn-outline-secondary px-3 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">Cancel</a>
                <button type="submit" form="createTeamForm" class="ks-btn ks-btn-primary px-4 py-2">
                    <i class="bi bi-check-lg"></i>
                    <span>Save Team</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Step Indicator Bar -->
    <div class="ks-content-card mb-3 p-2 px-3" style="background: #F8FAFC;">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 small">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill text-bg-primary" style="font-size: 11px;">Step 1</span>
                <span class="fw-semibold text-dark">Team Profile &amp; Sport</span>
            </div>
            <i class="bi bi-chevron-right text-muted d-none d-md-inline"></i>
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill text-bg-primary" style="font-size: 11px;">Step 2</span>
                <span class="fw-semibold text-dark">Coaching Staff</span>
            </div>
            <i class="bi bi-chevron-right text-muted d-none d-md-inline"></i>
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill text-bg-primary" style="font-size: 11px;">Step 3</span>
                <span class="fw-semibold text-dark">Registered Athlete Roster</span>
            </div>
            <i class="bi bi-chevron-right text-muted d-none d-md-inline"></i>
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill text-bg-primary" style="font-size: 11px;">Step 4</span>
                <span class="fw-semibold text-dark">Review / Save</span>
            </div>
        </div>
    </div>

    <form action="/teams/create" method="POST" id="createTeamForm">
        <!-- STEP 1: Team Profile & Sport -->
        <div class="ks-content-card mb-3">
            <div class="ks-card-header py-2 px-3">
                <div class="ks-header-left">
                    <i class="bi bi-shield-shaded text-primary fs-6"></i>
                    <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">1. Team Profile &amp; Sport</h3>
                </div>
            </div>
            <div class="p-3 px-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ks-form-label" for="teamNameInput">Team Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="teamNameInput" class="ks-form-control" placeholder="e.g. Apex Strikers Badminton U-18" required value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label" for="teamSportSelect">Sport <span class="text-danger">*</span></label>
                        <select name="sport_id" id="teamSportSelect" class="ks-form-select" required>
                            <option value="">Select sport</option>
                            <?php foreach ($sports as $sp): ?>
                                <option value="<?= (int)$sp['id'] ?>" data-sport-name="<?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?>" <?= (($_POST['sport_id'] ?? '') == $sp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="ks-form-label" for="teamGenderSelect">Gender Division <span class="text-danger">*</span></label>
                        <select name="gender" id="teamGenderSelect" class="ks-form-select" required>
                            <option value="male" <?= (($_POST['gender'] ?? 'male') === 'male') ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
                            <option value="mixed" <?= (($_POST['gender'] ?? '') === 'mixed') ? 'selected' : '' ?>>Mixed</option>
                            <option value="open" <?= (($_POST['gender'] ?? '') === 'open') ? 'selected' : '' ?>>Open</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label" for="teamAgeGroupInput">Age Group <span class="text-danger">*</span></label>
                        <input type="text" name="age_group" id="teamAgeGroupInput" class="ks-form-control" placeholder="e.g. Under-18, Under-16, Senior" value="<?= htmlspecialchars($_POST['age_group'] ?? 'Under-18', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label" for="teamLevelInput">Formation / Competition Level</label>
                        <input type="text" name="formation_or_level" id="teamLevelInput" class="ks-form-control" placeholder="e.g. State Division 1, Academy Elite" value="<?= htmlspecialchars($_POST['formation_or_level'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="ks-form-label" for="teamStatusSelect">Team Status <span class="text-danger">*</span></label>
                        <select name="status" id="teamStatusSelect" class="ks-form-select" required>
                            <option value="active" <?= (($_POST['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="ks-form-label" for="teamDescInput">Description</label>
                        <input type="text" name="description" id="teamDescInput" class="ks-form-control" placeholder="Brief notes on team objective or formation" value="<?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 2: Coaching Staff -->
        <div class="ks-content-card mb-3">
            <div class="ks-card-header py-2 px-3">
                <div class="ks-header-left">
                    <i class="bi bi-person-badge-fill text-primary fs-6"></i>
                    <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">2. Coaching Staff</h3>
                </div>
                <div class="ks-header-right d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border" id="selectedCoachesCountBadge" style="font-size: 11.5px; font-weight: 600;">0 Coaches Selected</span>
                </div>
            </div>
            <div class="p-3 px-4">
                <div class="row g-3">
                    <!-- Left: Coach Search & Available List -->
                    <div class="col-lg-6">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="ks-form-label mb-0" for="coachSearchInput">Search Coaches</label>
                            <a href="/coaches/create" class="text-decoration-none small fw-semibold" style="font-size: 12px; color: var(--ks-primary);">
                                <i class="bi bi-plus-circle me-1"></i>Add Coach
                            </a>
                        </div>
                        <div class="position-relative mb-2">
                            <i class="bi bi-search position-absolute" style="left: 12px; top: 11px; color: var(--ks-text-muted); font-size: 13px;"></i>
                            <input type="text" id="coachSearchInput" class="ks-form-control" style="padding-left: 34px; height: 38px; font-size: 13px;" placeholder="Search coaches by name, code, or specialization..." autocomplete="off">
                        </div>
                        <div class="text-muted mb-2" style="font-size: 11.5px;">Available Coaches</div>
                        <div id="coachSearchResults" class="border rounded-3 p-2" style="max-height: 230px; overflow-y: auto; background: #F8FAFC;">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>

                    <!-- Right: Selected Coaches -->
                    <div class="col-lg-6">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="ks-form-label mb-0">Selected Coaches <span id="selectedCoachesLabelCount" class="text-muted fw-normal">(0)</span></label>
                            <span class="text-muted" style="font-size: 11.5px;">One coach may be marked <strong>Primary</strong></span>
                        </div>
                        <div id="selectedCoachesContainer" class="border rounded-3 p-2" style="min-height: 230px; max-height: 270px; overflow-y: auto; background: #FFFFFF;">
                            <div id="noSelectedCoachesMsg" class="text-center text-muted small py-5">
                                <i class="bi bi-person-badge d-block fs-4 mb-1"></i>
                                No coaches assigned yet. Search and click <strong>Add</strong> to assign coaching staff.
                            </div>
                        </div>
                        <input type="hidden" name="primary_coach_id" id="primaryCoachIdInput" value="">
                        <div id="coachHiddenInputs"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 3: Registered Athlete Roster -->
        <div class="ks-content-card mb-3">
            <div class="ks-card-header py-2 px-3">
                <div class="ks-header-left">
                    <i class="bi bi-people-fill text-primary fs-6"></i>
                    <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">3. Registered Athlete Roster</h3>
                </div>
                <div class="ks-header-right d-flex align-items-center gap-2">
                    <span class="badge rounded-pill" id="rosterSportBadge" style="background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; font-size: 11.5px; font-weight: 600;">
                        Sport: Not Selected
                    </span>
                    <span class="badge bg-light text-dark border" id="selectedAthletesCountBadge" style="font-size: 11.5px; font-weight: 600;">0 Athletes Selected</span>
                </div>
            </div>
            <div class="p-3 px-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 p-2 px-3 rounded-3" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                    <div class="small text-muted">
                        <i class="bi bi-info-circle text-primary me-1"></i>
                        Only registered athletes matching the selected <strong>Team Sport</strong> and <strong>Gender Division</strong> can be added. Athletes can belong to multiple teams.
                    </div>
                    <div class="small">
                        <span class="text-muted me-1">Athlete not registered?</span>
                        <a href="/athletes/create" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 12px; font-weight: 600; border-radius: 6px;">
                            <i class="bi bi-person-plus me-1"></i>Register Athlete
                        </a>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Left: Search Registered Athletes -->
                    <div class="col-lg-6">
                        <label class="ks-form-label mb-1" for="athleteSearchInput">Search Registered Athletes</label>
                        <div class="position-relative mb-2">
                            <i class="bi bi-search position-absolute" style="left: 12px; top: 11px; color: var(--ks-text-muted); font-size: 13px;"></i>
                            <input type="text" id="athleteSearchInput" class="ks-form-control" style="padding-left: 34px; height: 38px; font-size: 13px;" placeholder="Search by athlete name or registration ID (e.g. ATH-...)..." autocomplete="off">
                        </div>
                        <div class="text-muted mb-2" style="font-size: 11.5px;">Search Results</div>
                        <div id="athleteSearchResults" class="border rounded-3 p-2" style="min-height: 230px; max-height: 270px; overflow-y: auto; background: #F8FAFC;">
                            <div class="text-center text-muted small py-5">
                                <i class="bi bi-Dribbble d-block fs-4 mb-1"></i>
                                Please select a <strong>Sport</strong> in Step 1 to search eligible registered athletes.
                            </div>
                        </div>
                    </div>

                    <!-- Right: Selected Team Members -->
                    <div class="col-lg-6">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="ks-form-label mb-0">Selected Members <span id="selectedAthletesLabelCount" class="text-muted fw-normal">(0)</span></label>
                            <button type="button" id="clearSelectedAthletesBtn" class="btn btn-link btn-sm p-0 text-decoration-none text-danger d-none" style="font-size: 12px;">Clear all</button>
                        </div>
                        <div id="selectedAthletesContainer" class="border rounded-3 p-2" style="min-height: 230px; max-height: 310px; overflow-y: auto; background: #FFFFFF;">
                            <div id="noSelectedAthletesMsg" class="text-center text-muted small py-5">
                                <i class="bi bi-people d-block fs-4 mb-1"></i>
                                No athletes selected yet. You can select athletes now or add them later from the Team detail page.
                            </div>
                        </div>
                        <div id="athleteHiddenInputs"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 4: Review / Save -->
        <div class="ks-content-card mb-4">
            <div class="ks-card-header py-2 px-3">
                <div class="ks-header-left">
                    <i class="bi bi-check2-circle text-primary fs-6"></i>
                    <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">4. Review &amp; Save</h3>
                </div>
            </div>
            <div class="p-3 px-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex flex-wrap gap-4 small">
                            <div>
                                <div class="text-muted" style="font-size: 11.5px;">Team &amp; Sport</div>
                                <div class="fw-semibold text-dark" id="reviewTeamSport">—</div>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size: 11.5px;">Division &amp; Age Group</div>
                                <div class="fw-semibold text-dark" id="reviewDivisionAge">—</div>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size: 11.5px;">Coaching Staff</div>
                                <div class="fw-semibold text-dark" id="reviewCoachesSummary">0 Coaches</div>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size: 11.5px;">Registered Roster</div>
                                <div class="fw-semibold text-dark" id="reviewAthletesSummary">0 Athletes</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center justify-content-md-end gap-2">
                        <a href="/teams" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">
                            Cancel
                        </a>
                        <button type="submit" class="ks-btn ks-btn-primary px-4 py-2" id="btnSubmitCreateTeam">
                            <i class="bi bi-check-lg"></i>
                            <span>Save Team</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const initialCoaches = <?= json_encode($initialCoaches, JSON_UNESCAPED_UNICODE) ?>;

    const teamNameInput = document.getElementById('teamNameInput');
    const sportSelect = document.getElementById('teamSportSelect');
    const genderSelect = document.getElementById('teamGenderSelect');
    const ageGroupInput = document.getElementById('teamAgeGroupInput');

    // Coach DOM
    const coachSearchInput = document.getElementById('coachSearchInput');
    const coachSearchResults = document.getElementById('coachSearchResults');
    const selectedCoachesContainer = document.getElementById('selectedCoachesContainer');
    const selectedCoachesCountBadge = document.getElementById('selectedCoachesCountBadge');
    const selectedCoachesLabelCount = document.getElementById('selectedCoachesLabelCount');
    const primaryCoachIdInput = document.getElementById('primaryCoachIdInput');
    const coachHiddenInputs = document.getElementById('coachHiddenInputs');

    // Athlete DOM
    const athleteSearchInput = document.getElementById('athleteSearchInput');
    const athleteSearchResults = document.getElementById('athleteSearchResults');
    const selectedAthletesContainer = document.getElementById('selectedAthletesContainer');
    const selectedAthletesCountBadge = document.getElementById('selectedAthletesCountBadge');
    const selectedAthletesLabelCount = document.getElementById('selectedAthletesLabelCount');
    const clearSelectedAthletesBtn = document.getElementById('clearSelectedAthletesBtn');
    const rosterSportBadge = document.getElementById('rosterSportBadge');
    const athleteHiddenInputs = document.getElementById('athleteHiddenInputs');

    // Review DOM
    const reviewTeamSport = document.getElementById('reviewTeamSport');
    const reviewDivisionAge = document.getElementById('reviewDivisionAge');
    const reviewCoachesSummary = document.getElementById('reviewCoachesSummary');
    const reviewAthletesSummary = document.getElementById('reviewAthletesSummary');

    // State
    let availableCoaches = Array.isArray(initialCoaches) ? initialCoaches : [];
    let selectedCoaches = []; // [{ coach_id, full_name, coach_code, specialization, role, is_primary }]
    let availableAthletes = [];
    let selectedAthletes = []; // [{ id, full_name, athlete_code, sport_name, sport_id, gender, current_teams_label }]

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getSelectedSportName() {
        const opt = sportSelect.options[sportSelect.selectedIndex];
        return opt && opt.value ? (opt.getAttribute('data-sport-name') || opt.textContent.trim()) : '';
    }

    function updateReviewSummary() {
        const tName = teamNameInput.value.trim() || 'Untitled Team';
        const sName = getSelectedSportName() || 'No Sport';
        reviewTeamSport.textContent = `${tName} (${sName})`;

        const gName = genderSelect.value ? genderSelect.value.charAt(0).toUpperCase() + genderSelect.value.slice(1) : 'Open';
        const aGroup = ageGroupInput.value.trim() || 'Open';
        reviewDivisionAge.textContent = `${gName} • ${aGroup}`;

        const primaryCoach = selectedCoaches.find(c => c.is_primary);
        if (selectedCoaches.length === 0) {
            reviewCoachesSummary.textContent = '0 Coaches';
        } else {
            reviewCoachesSummary.textContent = `${selectedCoaches.length} ${selectedCoaches.length === 1 ? 'Coach' : 'Coaches'}` +
                (primaryCoach ? ` (Primary: ${primaryCoach.full_name})` : '');
        }

        reviewAthletesSummary.textContent = `${selectedAthletes.length} ${selectedAthletes.length === 1 ? 'Athlete' : 'Athletes'}`;
    }

    // ==========================================
    // COACHES LOGIC
    // ==========================================
    function renderCoachSearchResults() {
        if (!availableCoaches || availableCoaches.length === 0) {
            coachSearchResults.innerHTML = `
                <div class="text-center text-muted small py-4">
                    <div class="mb-2">No coaches available matching your search.</div>
                    <a href="/coaches/create" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 12px;">
                        <i class="bi bi-plus-lg me-1"></i>Add Coach
                    </a>
                </div>
            `;
            return;
        }

        const html = availableCoaches.map(c => {
            const cId = parseInt(c.coach_id, 10);
            const isSelected = selectedCoaches.some(sc => sc.coach_id === cId);
            const spec = c.specialization || 'General';
            const teamsLabel = c.current_teams_label || '—';
            const sportMatchBadge = c.sport_matches
                ? `<span class="badge rounded-pill ms-1" style="background:#DCFCE7;color:#166534;font-size:10px;">Matches Sport</span>`
                : '';

            return `
                <div class="d-flex align-items-center justify-content-between p-2 mb-1 bg-white border rounded-2">
                    <div>
                        <div class="fw-semibold text-dark" style="font-size: 13px;">
                            ${escapeHtml(c.full_name)}
                            <span class="text-muted fw-normal" style="font-size: 11px;">(${escapeHtml(c.coach_code || '')})</span>
                            ${sportMatchBadge}
                        </div>
                        <div class="text-muted" style="font-size: 11.5px;">
                            ${escapeHtml(spec)} &bull; Current teams: <span class="text-dark">${escapeHtml(teamsLabel)}</span>
                        </div>
                    </div>
                    <div>
                        ${isSelected
                            ? `<span class="badge bg-secondary-subtle text-secondary border" style="font-size: 11px;">Selected</span>`
                            : `<button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 add-coach-btn" data-coach-id="${cId}" style="font-size: 12px; font-weight: 600;">+ Add</button>`
                        }
                    </div>
                </div>
            `;
        }).join('');

        coachSearchResults.innerHTML = html;

        coachSearchResults.querySelectorAll('.add-coach-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const cId = parseInt(this.getAttribute('data-coach-id'), 10);
                const coachObj = availableCoaches.find(x => parseInt(x.coach_id, 10) === cId);
                if (!coachObj) return;
                if (selectedCoaches.some(x => x.coach_id === cId)) return;

                const isFirst = selectedCoaches.length === 0;
                selectedCoaches.push({
                    coach_id: cId,
                    full_name: coachObj.full_name,
                    coach_code: coachObj.coach_code || '',
                    specialization: coachObj.specialization || 'General',
                    role: isFirst ? 'head_coach' : 'assistant_coach',
                    is_primary: isFirst
                });

                renderSelectedCoaches();
                renderCoachSearchResults();
                updateReviewSummary();
            });
        });
    }

    function renderSelectedCoaches() {
        const count = selectedCoaches.length;
        selectedCoachesCountBadge.textContent = `${count} ${count === 1 ? 'Coach' : 'Coaches'} Selected`;
        selectedCoachesLabelCount.textContent = `(${count})`;

        if (count === 0) {
            selectedCoachesContainer.innerHTML = `
                <div class="text-center text-muted small py-5">
                    <i class="bi bi-person-badge d-block fs-4 mb-1"></i>
                    No coaches assigned yet. Search and click <strong>+ Add</strong> to assign coaching staff.
                </div>
            `;
            primaryCoachIdInput.value = '';
            coachHiddenInputs.innerHTML = '';
            return;
        }

        // Ensure at most one primary coach; if none is marked primary, mark the first one
        if (!selectedCoaches.some(c => c.is_primary)) {
            selectedCoaches[0].is_primary = true;
        }

        const primaryCoach = selectedCoaches.find(c => c.is_primary);
        primaryCoachIdInput.value = primaryCoach ? String(primaryCoach.coach_id) : '';

        selectedCoachesContainer.innerHTML = selectedCoaches.map(c => {
            return `
                <div class="p-2 mb-2 border rounded-2" style="background: ${c.is_primary ? '#F8FAFC' : '#FFFFFF'}; border-color: ${c.is_primary ? '#BFDBFE' : '#E2E8F0'} !important;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold text-dark" style="font-size: 13px;">
                                ${escapeHtml(c.full_name)}
                                ${c.is_primary ? `<span class="badge rounded-pill ms-1" style="background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; font-size: 10.5px;">Primary</span>` : ''}
                            </div>
                            <div class="text-muted" style="font-size: 11px;">${escapeHtml(c.specialization)}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm coach-role-select" data-coach-id="${c.coach_id}" style="width: 145px; font-size: 12px; height: 30px; padding-top: 2px; padding-bottom: 2px;">
                                <option value="head_coach" ${c.role === 'head_coach' ? 'selected' : ''}>Head Coach</option>
                                <option value="assistant_coach" ${c.role === 'assistant_coach' ? 'selected' : ''}>Assistant Coach</option>
                                <option value="fitness_coach" ${c.role === 'fitness_coach' ? 'selected' : ''}>Fitness Coach</option>
                                <option value="other" ${c.role === 'other' ? 'selected' : ''}>Other</option>
                            </select>
                            <button type="button" class="btn btn-sm ${c.is_primary ? 'btn-primary' : 'btn-outline-secondary'} set-primary-coach-btn" data-coach-id="${c.coach_id}" style="font-size: 11px; padding: 3px 8px;" title="Set as Primary / Head Coach">
                                ${c.is_primary ? 'Primary' : 'Set Primary'}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-coach-btn" data-coach-id="${c.coach_id}" style="font-size: 11px; padding: 3px 8px;">
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Sync hidden inputs for form submission
        coachHiddenInputs.innerHTML = selectedCoaches.map(c => `
            <input type="hidden" name="coach_ids[]" value="${c.coach_id}">
            <input type="hidden" name="coach_roles[${c.coach_id}]" value="${escapeHtml(c.role)}">
        `).join('');

        selectedCoachesContainer.querySelectorAll('.coach-role-select').forEach(sel => {
            sel.addEventListener('change', function() {
                const cId = parseInt(this.getAttribute('data-coach-id'), 10);
                const target = selectedCoaches.find(x => x.coach_id === cId);
                if (target) {
                    target.role = this.value;
                    renderSelectedCoaches();
                }
            });
        });

        selectedCoachesContainer.querySelectorAll('.set-primary-coach-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const cId = parseInt(this.getAttribute('data-coach-id'), 10);
                selectedCoaches.forEach(x => {
                    x.is_primary = (x.coach_id === cId);
                    if (x.is_primary && x.role === 'assistant_coach') {
                        x.role = 'head_coach';
                    }
                });
                renderSelectedCoaches();
                updateReviewSummary();
            });
        });

        selectedCoachesContainer.querySelectorAll('.remove-coach-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const cId = parseInt(this.getAttribute('data-coach-id'), 10);
                selectedCoaches = selectedCoaches.filter(x => x.coach_id !== cId);
                renderSelectedCoaches();
                renderCoachSearchResults();
                updateReviewSummary();
            });
        });
    }

    let coachSearchTimeout = null;
    function fetchCoaches() {
        const q = coachSearchInput.value.trim();
        const sportName = getSelectedSportName();
        const params = new URLSearchParams();
        if (q) params.set('search', q);
        if (sportName) params.set('sport_name', sportName);

        fetch('/teams/candidates/coaches?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(res => {
            if (res && Array.isArray(res.data)) {
                availableCoaches = res.data;
                renderCoachSearchResults();
            }
        })
        .catch(() => {});
    }

    coachSearchInput.addEventListener('input', function() {
        clearTimeout(coachSearchTimeout);
        coachSearchTimeout = setTimeout(fetchCoaches, 220);
    });

    // ==========================================
    // ATHLETES LOGIC (SPORT-SCOPED)
    // ==========================================
    function renderAthleteSearchResults() {
        const sportId = parseInt(sportSelect.value || '0', 10);
        const sportName = getSelectedSportName();

        if (!sportId) {
            athleteSearchResults.innerHTML = `
                <div class="text-center text-muted small py-5">
                    <i class="bi bi-shield-exclamation d-block fs-4 mb-1"></i>
                    Please select a <strong>Sport</strong> in Step 1 to search eligible registered athletes.
                </div>
            `;
            return;
        }

        if (!availableAthletes || availableAthletes.length === 0) {
            athleteSearchResults.innerHTML = `
                <div class="text-center text-muted small py-4">
                    <div class="fw-semibold text-dark mb-1">No registered ${escapeHtml(sportName)} athletes found.</div>
                    <div class="mb-2" style="font-size: 12px;">Register the athlete first in the Athletes module before adding them to a team.</div>
                    <a href="/athletes/create" class="btn btn-sm btn-outline-primary py-1 px-3" style="font-size: 12px; font-weight: 600;">
                        <i class="bi bi-person-plus me-1"></i>Register Athlete
                    </a>
                </div>
            `;
            return;
        }

        athleteSearchResults.innerHTML = availableAthletes.map(a => {
            const aId = parseInt(a.id, 10);
            const isSelected = selectedAthletes.some(sa => sa.id === aId);
            const genderLabel = a.gender ? (a.gender.charAt(0).toUpperCase() + a.gender.slice(1)) : '—';
            const dobLabel = a.dob_formatted ? ` • DOB: ${a.dob_formatted}` : '';
            const ageLabel = (a.age !== null && a.age !== undefined) ? ` (${a.age} yrs)` : '';
            const teamsLabel = a.current_teams_label || '—';

            return `
                <div class="d-flex align-items-center justify-content-between p-2 mb-1 bg-white border rounded-2">
                    <div>
                        <div class="fw-semibold text-dark" style="font-size: 13px;">
                            ${escapeHtml(a.full_name)}
                            <span class="text-muted fw-normal" style="font-size: 11px;">${escapeHtml(a.athlete_code || '')}</span>
                        </div>
                        <div class="text-muted" style="font-size: 11.5px;">
                            <span class="badge bg-light text-dark border py-0 px-1 me-1" style="font-size: 10.5px;">${escapeHtml(a.sport_name || sportName)}</span>
                            ${escapeHtml(genderLabel + dobLabel + ageLabel)}
                        </div>
                        <div class="text-muted" style="font-size: 11px;">
                            Current teams: <span class="text-dark fw-medium">${escapeHtml(teamsLabel)}</span>
                        </div>
                    </div>
                    <div>
                        ${isSelected
                            ? `<span class="badge bg-secondary-subtle text-secondary border" style="font-size: 11px;">Already added</span>`
                            : `<button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 add-athlete-btn" data-athlete-id="${aId}" style="font-size: 12px; font-weight: 600;">+ Add</button>`
                        }
                    </div>
                </div>
            `;
        }).join('');

        athleteSearchResults.querySelectorAll('.add-athlete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const aId = parseInt(this.getAttribute('data-athlete-id'), 10);
                const athObj = availableAthletes.find(x => parseInt(x.id, 10) === aId);
                if (!athObj) return;
                if (selectedAthletes.some(x => x.id === aId)) return;

                selectedAthletes.push({
                    id: aId,
                    full_name: athObj.full_name,
                    athlete_code: athObj.athlete_code || '',
                    sport_id: parseInt(athObj.current_sport_id, 10),
                    sport_name: athObj.sport_name || getSelectedSportName(),
                    gender: athObj.gender || '',
                    current_teams_label: athObj.current_teams_label || '—'
                });

                renderSelectedAthletes();
                renderAthleteSearchResults();
                updateReviewSummary();
            });
        });
    }

    function renderSelectedAthletes() {
        const count = selectedAthletes.length;
        selectedAthletesCountBadge.textContent = `${count} ${count === 1 ? 'Athlete' : 'Athletes'} Selected`;
        selectedAthletesLabelCount.textContent = `(${count})`;

        if (count > 0) {
            clearSelectedAthletesBtn.classList.remove('d-none');
        } else {
            clearSelectedAthletesBtn.classList.add('d-none');
        }

        if (count === 0) {
            selectedAthletesContainer.innerHTML = `
                <div class="text-center text-muted small py-5">
                    <i class="bi bi-people d-block fs-4 mb-1"></i>
                    No athletes selected yet. You can select athletes now or add them later from the Team detail page.
                </div>
            `;
            athleteHiddenInputs.innerHTML = '';
            return;
        }

        selectedAthletesContainer.innerHTML = selectedAthletes.map(a => `
            <div class="d-flex align-items-center justify-content-between p-2 mb-1 border rounded-2 bg-white">
                <div>
                    <div class="fw-semibold text-dark" style="font-size: 13px;">
                        ${escapeHtml(a.full_name)}
                        <span class="text-muted fw-normal" style="font-size: 11px;">(${escapeHtml(a.athlete_code)})</span>
                    </div>
                    <div class="text-muted" style="font-size: 11px;">
                        ${escapeHtml(a.sport_name)} &bull; Current teams: ${escapeHtml(a.current_teams_label)}
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-athlete-btn" data-athlete-id="${a.id}" style="font-size: 11px; padding: 3px 8px;">
                    Remove
                </button>
            </div>
        `).join('');

        athleteHiddenInputs.innerHTML = selectedAthletes.map(a => `
            <input type="hidden" name="athlete_ids[]" value="${a.id}">
        `).join('');

        selectedAthletesContainer.querySelectorAll('.remove-athlete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const aId = parseInt(this.getAttribute('data-athlete-id'), 10);
                selectedAthletes = selectedAthletes.filter(x => x.id !== aId);
                renderSelectedAthletes();
                renderAthleteSearchResults();
                updateReviewSummary();
            });
        });
    }

    clearSelectedAthletesBtn.addEventListener('click', function() {
        selectedAthletes = [];
        renderSelectedAthletes();
        renderAthleteSearchResults();
        updateReviewSummary();
    });

    let athleteSearchTimeout = null;
    function fetchAthletes() {
        const sportId = parseInt(sportSelect.value || '0', 10);
        const sportName = getSelectedSportName();
        rosterSportBadge.textContent = sportName ? `Sport: ${sportName}` : 'Sport: Not Selected';

        if (!sportId) {
            availableAthletes = [];
            renderAthleteSearchResults();
            return;
        }

        const params = new URLSearchParams();
        params.set('sport_id', String(sportId));
        if (genderSelect.value) {
            params.set('gender', genderSelect.value);
        }
        const q = athleteSearchInput.value.trim();
        if (q) {
            params.set('search', q);
        }

        fetch('/teams/candidates/athletes?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(res => {
            if (res && Array.isArray(res.data)) {
                availableAthletes = res.data;
                renderAthleteSearchResults();
            }
        })
        .catch(() => {});
    }

    athleteSearchInput.addEventListener('input', function() {
        clearTimeout(athleteSearchTimeout);
        athleteSearchTimeout = setTimeout(fetchAthletes, 220);
    });

    sportSelect.addEventListener('change', function() {
        const newSportId = parseInt(this.value || '0', 10);
        // Remove any previously selected athletes that do not match the new sport
        selectedAthletes = selectedAthletes.filter(a => a.sport_id === newSportId);
        renderSelectedAthletes();
        fetchAthletes();
        fetchCoaches();
        updateReviewSummary();
    });

    genderSelect.addEventListener('change', function() {
        const g = this.value.toLowerCase();
        if (g === 'male' || g === 'female') {
            selectedAthletes = selectedAthletes.filter(a => !a.gender || a.gender.toLowerCase() === g);
            renderSelectedAthletes();
        }
        fetchAthletes();
        updateReviewSummary();
    });

    teamNameInput.addEventListener('input', updateReviewSummary);
    ageGroupInput.addEventListener('input', updateReviewSummary);

    // Initial render
    renderCoachSearchResults();
    renderSelectedCoaches();
    fetchAthletes();
    updateReviewSummary();
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
