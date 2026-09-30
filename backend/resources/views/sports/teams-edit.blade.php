<?php
$teamId = (int)($id ?? ($_GET['id'] ?? 0));
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

$canEditTeam = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'team.update', $orgId) ||
    $permissionService->hasPermission($userPayload, 'team.manage', $orgId)
);

if (!$canEditTeam) {
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo '<div class="p-5 text-center text-danger fw-bold">403 Forbidden: You do not have permission to edit teams.</div>';
    return;
}

$teamService = new \App\Services\Team\TeamService();
$team = $teamService->getTeam($orgId, $teamId);

// Sports from config
$sportService = new \App\Services\Sport\SportService();
$sports = [];
foreach (config('sports.catalog') ?? [] as $key => $name) {
    $dbId = $sportService->resolveSportId($key);
    $sports[] = ['id' => (int)$dbId, 'name' => $name];
}
usort($sports, fn($a, $b) => strcmp($a['name'], $b['name']));

$initialCoaches = $teamService->searchAvailableCoaches($orgId, null, null, $teamId, 30);

// Hydrate current coaches for JS state
$currentCoachesState = [];
if ($team && !empty($team['coaches'])) {
    foreach ($team['coaches'] as $c) {
        $currentCoachesState[] = [
            'coach_id' => (int)($c['coach_profile_id'] ?? $c['coach_id']),
            'full_name' => trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')),
            'coach_code' => $c['coach_code'] ?? '',
            'specialization' => $c['specialization'] ?: 'General',
            'role' => $c['coach_role'] ?? 'head_coach',
            'is_primary' => !empty($c['is_primary']),
        ];
    }
}

// Hydrate current active athletes for JS state
$currentAthletesState = [];
if ($team && !empty($team['current_athletes'])) {
    foreach ($team['current_athletes'] as $a) {
        $currentAthletesState[] = [
            'id' => (int)$a['athlete_id'],
            'full_name' => trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')),
            'athlete_code' => $a['athlete_code'] ?? '',
            'sport_id' => (int)($a['current_sport_id'] ?? $team['sport_id']),
            'sport_name' => $a['sport_name'] ?? ($team['sport_name'] ?? 'General'),
            'gender' => $a['gender'] ?? '',
            'current_teams_label' => $team['name'] ?? '',
        ];
    }
}

$title = $team ? 'Edit Team — ' . htmlspecialchars($team['name']) : 'Edit Team';
$pageTitle = $title;
$activePage = 'teams';

ob_start();
?>

<div class="ks-page">
    <?php if (!$team): ?>
        <div class="ks-content-card p-5 text-center my-4">
            <div class="mb-3"><i class="bi bi-shield-x fs-1 text-muted"></i></div>
            <h4 class="fw-bold text-dark">Team Not Found</h4>
            <p class="text-muted small">The requested team squad does not exist, has been archived, or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/teams" class="btn btn-outline-secondary" style="border-radius: 8px; font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Teams
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Page Header -->
        <div class="mb-3">
            <a href="/teams/<?= (int)$team['id'] ?>" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1 mb-2" style="font-size: 12.5px;">
                <i class="bi bi-arrow-left"></i> Back to Team Details
            </a>
            <div class="ks-page-header mb-0">
                <div>
                    <h1 class="ks-page-title mb-1">Edit Team</h1>
                    <p class="text-muted small mb-0">
                        Updating squad profile, coaching staff, and roster for <strong><?= htmlspecialchars($team['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                        (<?= htmlspecialchars($team['team_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                    </p>
                </div>
                <div class="ks-header-actions">
                    <a href="/teams/<?= (int)$team['id'] ?>" class="btn btn-outline-secondary px-3 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">Cancel</a>
                    <button type="submit" form="editTeamForm" class="ks-btn ks-btn-primary px-4 py-2">
                        <i class="bi bi-check-lg"></i>
                        <span>Save Team</span>
                    </button>
                </div>
            </div>
        </div>

        <form action="/teams/<?= (int)$team['id'] ?>/edit" method="POST" id="editTeamForm">
            <input type="hidden" name="sync_coaches" value="1">
            <input type="hidden" name="sync_roster" value="1">

            <!-- Section 1: Team Profile & Sport -->
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
                            <label class="ks-form-label" for="editTeamName">Team Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editTeamName" class="ks-form-control" value="<?= htmlspecialchars($team['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label" for="editTeamSport">Sport <span class="text-danger">*</span></label>
                            <select name="sport_id" id="editTeamSport" class="ks-form-select" required>
                                <?php foreach ($sports as $sp): ?>
                                    <option value="<?= (int)$sp['id'] ?>" data-sport-name="<?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?>" <?= $team['sport_id'] == $sp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="ks-form-label" for="editTeamGender">Gender Division <span class="text-danger">*</span></label>
                            <select name="gender" id="editTeamGender" class="ks-form-select" required>
                                <option value="male" <?= ($team['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($team['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="mixed" <?= ($team['gender'] ?? '') === 'mixed' ? 'selected' : '' ?>>Mixed</option>
                                <option value="open" <?= ($team['gender'] ?? '') === 'open' ? 'selected' : '' ?>>Open</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label" for="editTeamAgeGroup">Age Group <span class="text-danger">*</span></label>
                            <input type="text" name="age_group" id="editTeamAgeGroup" class="ks-form-control" value="<?= htmlspecialchars($team['age_group'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label" for="editTeamLevel">Formation / Competition Level</label>
                            <input type="text" name="formation_or_level" id="editTeamLevel" class="ks-form-control" value="<?= htmlspecialchars($team['formation_or_level'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="ks-form-label" for="editTeamStatus">Team Status <span class="text-danger">*</span></label>
                            <select name="status" id="editTeamStatus" class="ks-form-select" required>
                                <option value="active" <?= ($team['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($team['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="ks-form-label" for="editTeamDesc">Description</label>
                            <input type="text" name="description" id="editTeamDesc" class="ks-form-control" value="<?= htmlspecialchars($team['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Coaching Staff -->
            <div class="ks-content-card mb-3">
                <div class="ks-card-header py-2 px-3">
                    <div class="ks-header-left">
                        <i class="bi bi-person-badge-fill text-primary fs-6"></i>
                        <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">2. Coaching Staff</h3>
                    </div>
                    <div class="ks-header-right d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border" id="editCoachesCountBadge" style="font-size: 11.5px; font-weight: 600;">0 Coaches Assigned</span>
                    </div>
                </div>
                <div class="p-3 px-4">
                    <div class="row g-3">
                        <!-- Left: Search Available Coaches -->
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="ks-form-label mb-0" for="editCoachSearchInput">Search Available Coaches</label>
                                <a href="/coaches/create" class="text-decoration-none small fw-semibold" style="font-size: 12px; color: var(--ks-primary);">
                                    <i class="bi bi-plus-circle me-1"></i>Add Coach
                                </a>
                            </div>
                            <div class="position-relative mb-2">
                                <i class="bi bi-search position-absolute" style="left: 12px; top: 11px; color: var(--ks-text-muted); font-size: 13px;"></i>
                                <input type="text" id="editCoachSearchInput" class="ks-form-control" style="padding-left: 34px; height: 38px; font-size: 13px;" placeholder="Search coaches by name, code, or specialization..." autocomplete="off">
                            </div>
                            <div class="text-muted mb-2" style="font-size: 11.5px;">Available Coaches</div>
                            <div id="editCoachSearchResults" class="border rounded-3 p-2" style="max-height: 230px; overflow-y: auto; background: #F8FAFC;">
                                <!-- Populated dynamically via JS -->
                            </div>
                        </div>

                        <!-- Right: Assigned Coaches -->
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="ks-form-label mb-0">Assigned Coaches <span id="editCoachesLabelCount" class="text-muted fw-normal">(0)</span></label>
                                <span class="text-muted" style="font-size: 11.5px;">One coach may be designated <strong>Primary</strong></span>
                            </div>
                            <div id="editSelectedCoachesContainer" class="border rounded-3 p-2" style="min-height: 230px; max-height: 270px; overflow-y: auto; background: #FFFFFF;">
                                <!-- Populated dynamically via JS -->
                            </div>
                            <input type="hidden" name="primary_coach_id" id="editPrimaryCoachIdInput" value="">
                            <div id="editCoachHiddenInputs"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Registered Athlete Roster -->
            <div class="ks-content-card mb-4">
                <div class="ks-card-header py-2 px-3">
                    <div class="ks-header-left">
                        <i class="bi bi-people-fill text-primary fs-6"></i>
                        <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">3. Registered Athlete Roster</h3>
                    </div>
                    <div class="ks-header-right d-flex align-items-center gap-2">
                        <span class="badge rounded-pill" id="editRosterSportBadge" style="background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; font-size: 11.5px; font-weight: 600;">
                            Sport: <?= htmlspecialchars($team['sport_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="badge bg-light text-dark border" id="editAthletesCountBadge" style="font-size: 11.5px; font-weight: 600;">0 Athletes Rostered</span>
                    </div>
                </div>
                <div class="p-3 px-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 p-2 px-3 rounded-3" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                        <div class="small text-muted">
                            <i class="bi bi-info-circle text-primary me-1"></i>
                            Only registered athletes matching the <strong>Team Sport</strong> and <strong>Gender Division</strong> can be rostered.
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
                            <label class="ks-form-label mb-1" for="editAthleteSearchInput">Search Registered Athletes</label>
                            <div class="position-relative mb-2">
                                <i class="bi bi-search position-absolute" style="left: 12px; top: 11px; color: var(--ks-text-muted); font-size: 13px;"></i>
                                <input type="text" id="editAthleteSearchInput" class="ks-form-control" style="padding-left: 34px; height: 38px; font-size: 13px;" placeholder="Search by name or registration ID (e.g. ATH-...)..." autocomplete="off">
                            </div>
                            <div class="text-muted mb-2" style="font-size: 11.5px;">Search Results</div>
                            <div id="editAthleteSearchResults" class="border rounded-3 p-2" style="min-height: 230px; max-height: 270px; overflow-y: auto; background: #F8FAFC;">
                                <!-- Populated dynamically via JS -->
                            </div>
                        </div>

                        <!-- Right: Rostered Members -->
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="ks-form-label mb-0">Rostered Members <span id="editAthletesLabelCount" class="text-muted fw-normal">(0)</span></label>
                                <button type="button" id="editClearSelectedAthletesBtn" class="btn btn-link btn-sm p-0 text-decoration-none text-danger d-none" style="font-size: 12px;">Clear all</button>
                            </div>
                            <div id="editSelectedAthletesContainer" class="border rounded-3 p-2" style="min-height: 230px; max-height: 310px; overflow-y: auto; background: #FFFFFF;">
                                <!-- Populated dynamically via JS -->
                            </div>
                            <div id="editAthleteHiddenInputs"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex align-items-center justify-content-end gap-2 mb-4">
                <a href="/teams/<?= (int)$team['id'] ?>" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">
                    Cancel
                </a>
                <button type="submit" class="ks-btn ks-btn-primary px-4 py-2">
                    <i class="bi bi-check-lg"></i>
                    <span>Save Team</span>
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const teamId = <?= (int)($team['id'] ?? 0) ?>;
    if (!teamId) return;

    const initialCoaches = <?= json_encode($initialCoaches, JSON_UNESCAPED_UNICODE) ?>;
    const initialSelectedCoaches = <?= json_encode($currentCoachesState, JSON_UNESCAPED_UNICODE) ?>;
    const initialSelectedAthletes = <?= json_encode($currentAthletesState, JSON_UNESCAPED_UNICODE) ?>;

    const sportSelect = document.getElementById('editTeamSport');
    const genderSelect = document.getElementById('editTeamGender');

    // Coach DOM
    const coachSearchInput = document.getElementById('editCoachSearchInput');
    const coachSearchResults = document.getElementById('editCoachSearchResults');
    const selectedCoachesContainer = document.getElementById('editSelectedCoachesContainer');
    const selectedCoachesCountBadge = document.getElementById('editCoachesCountBadge');
    const selectedCoachesLabelCount = document.getElementById('editCoachesLabelCount');
    const primaryCoachIdInput = document.getElementById('editPrimaryCoachIdInput');
    const coachHiddenInputs = document.getElementById('editCoachHiddenInputs');

    // Athlete DOM
    const athleteSearchInput = document.getElementById('editAthleteSearchInput');
    const athleteSearchResults = document.getElementById('editAthleteSearchResults');
    const selectedAthletesContainer = document.getElementById('editSelectedAthletesContainer');
    const selectedAthletesCountBadge = document.getElementById('editAthletesCountBadge');
    const selectedAthletesLabelCount = document.getElementById('editAthletesLabelCount');
    const clearSelectedAthletesBtn = document.getElementById('editClearSelectedAthletesBtn');
    const rosterSportBadge = document.getElementById('editRosterSportBadge');
    const athleteHiddenInputs = document.getElementById('editAthleteHiddenInputs');

    // State
    let availableCoaches = Array.isArray(initialCoaches) ? initialCoaches : [];
    let selectedCoaches = Array.isArray(initialSelectedCoaches) ? initialSelectedCoaches : [];
    let availableAthletes = [];
    let selectedAthletes = Array.isArray(initialSelectedAthletes) ? initialSelectedAthletes : [];

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

    // ==========================================
    // COACHES LOGIC
    // ==========================================
    function renderCoachSearchResults() {
        if (!availableCoaches || availableCoaches.length === 0) {
            coachSearchResults.innerHTML = `
                <div class="text-center text-muted small py-4">
                    <div class="mb-2">No other coaches available matching your search.</div>
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
                            ? `<span class="badge bg-secondary-subtle text-secondary border" style="font-size: 11px;">Assigned</span>`
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
            });
        });
    }

    function renderSelectedCoaches() {
        const count = selectedCoaches.length;
        selectedCoachesCountBadge.textContent = `${count} ${count === 1 ? 'Coach' : 'Coaches'} Assigned`;
        selectedCoachesLabelCount.textContent = `(${count})`;

        if (count === 0) {
            selectedCoachesContainer.innerHTML = `
                <div class="text-center text-muted small py-5">
                    <i class="bi bi-person-badge d-block fs-4 mb-1"></i>
                    No coaches assigned yet. Search available coaches and click <strong>+ Add</strong>.
                </div>
            `;
            primaryCoachIdInput.value = '';
            coachHiddenInputs.innerHTML = '';
            return;
        }

        // At most one primary coach
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
            });
        });

        selectedCoachesContainer.querySelectorAll('.remove-coach-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const cId = parseInt(this.getAttribute('data-coach-id'), 10);
                selectedCoaches = selectedCoaches.filter(x => x.coach_id !== cId);
                renderSelectedCoaches();
                renderCoachSearchResults();
            });
        });
    }

    let coachSearchTimeout = null;
    function fetchCoaches() {
        const q = coachSearchInput.value.trim();
        const sportName = getSelectedSportName();
        const params = new URLSearchParams();
        params.set('team_id', String(teamId));
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
                    Please select a <strong>Sport</strong> to search eligible registered athletes.
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
                            <span class="text-muted fw-normal" style="font-size: 11px;">(${escapeHtml(a.athlete_code || '')})</span>
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
                            ? `<span class="badge bg-secondary-subtle text-secondary border" style="font-size: 11px;">Already rostered</span>`
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
            });
        });
    }

    function renderSelectedAthletes() {
        const count = selectedAthletes.length;
        selectedAthletesCountBadge.textContent = `${count} ${count === 1 ? 'Athlete' : 'Athletes'} Rostered`;
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
                    No athletes in squad roster. Search eligible athletes and click <strong>+ Add</strong>.
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
            });
        });
    }

    clearSelectedAthletesBtn.addEventListener('click', function() {
        selectedAthletes = [];
        renderSelectedAthletes();
        renderAthleteSearchResults();
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
        params.set('team_id', String(teamId));
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
        // Filter out rostered athletes who do not match the new sport
        selectedAthletes = selectedAthletes.filter(a => a.sport_id === newSportId);
        renderSelectedAthletes();
        fetchAthletes();
        fetchCoaches();
    });

    genderSelect.addEventListener('change', function() {
        const g = this.value.toLowerCase();
        if (g === 'male' || g === 'female') {
            selectedAthletes = selectedAthletes.filter(a => !a.gender || a.gender.toLowerCase() === g);
            renderSelectedAthletes();
        }
        fetchAthletes();
    });

    // Initial render
    renderCoachSearchResults();
    renderSelectedCoaches();
    renderSelectedAthletes();
    fetchAthletes();
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
