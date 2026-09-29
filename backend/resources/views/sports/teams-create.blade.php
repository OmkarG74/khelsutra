<?php
$pageTitle = 'Create Team — KhelSutra';
$activePage = 'teams';
$orgId = current_organization_id();

$db = \App\Services\BaseService::getDatabaseConnection();

// Sports from config
$sportService = new \App\Services\Sport\SportService();
$sports = [];
foreach (config('sports.catalog') ?? [] as $key => $name) {
    $dbId = $sportService->resolveSportId($key);
    $sports[] = ['id' => $dbId, 'name' => $name];
}
usort($sports, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// Active Coaches
$coachStmt = $db->prepare("
    SELECT cp.id as coach_id, cp.coach_code, cp.specialization, e.first_name, e.last_name
    FROM coach_profiles cp
    JOIN employees e ON cp.employee_id = e.id
    WHERE cp.organization_id = :org_id AND cp.status = 'active' AND cp.deleted_at IS NULL AND e.deleted_at IS NULL
    ORDER BY e.first_name ASC
");
$coachStmt->execute([':org_id' => $orgId]);
$coaches = $coachStmt ? $coachStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Active Athletes
$athStmt = $db->prepare("
    SELECT id, athlete_code, first_name, last_name, current_sport_id, gender, date_of_birth
    FROM athletes
    WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL
    ORDER BY first_name ASC
");
$athStmt->execute([':org_id' => $orgId]);
$athletes = $athStmt ? $athStmt->fetchAll(PDO::FETCH_ASSOC) : [];

ob_start();
?>

<div class="ks-content">
    <!-- Top Back Navigation -->
    <div class="mb-3">
        <a href="/teams" class="text-decoration-none text-muted small fw-medium">
            <i class="bi bi-arrow-left me-1"></i> Back to Teams
        </a>
    </div>

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold mb-0" style="color: var(--ks-navy);">Create Team</h1>
        </div>
    </div>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger mb-4 py-2 px-3 small d-flex align-items-center gap-2" style="border-radius: var(--ks-radius-button);">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <form action="/teams/create" method="POST" id="createTeamForm">
        <!-- Section 1: Team Information -->
        <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                1. Team Profile & Sport
            </h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Team Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Apex Titans Football U-18" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Sport <span class="text-danger">*</span></label>
                    <select name="sport_id" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="">Select sport</option>
                        <?php foreach ($sports as $sp): ?>
                            <option value="<?= (int)$sp['id'] ?>"><?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Gender Division <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="mixed">Mixed</option>
                        <option value="open">Open</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Age Group <span class="text-danger">*</span></label>
                    <input type="text" name="age_group" class="form-control" placeholder="e.g. Under-18, Under-16, Senior" value="Under-18" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Formation / Competition Level</label>
                    <input type="text" name="formation_or_level" class="form-control" placeholder="e.g. State Division 1, Academy Elite" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Team Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-semibold text-dark">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Brief notes on team objective or formation" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
            </div>
        </div>

        <!-- Section 2: Coach Assignment -->
        <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                2. Coaching Assignment
            </h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Primary Head Coach</label>
                    <select name="coach_id" class="form-select" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="">No head coach assigned</option>
                        <?php foreach ($coaches as $c): ?>
                            <option value="<?= (int)$c['coach_id'] ?>">
                                <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (' . $c['specialization'] . ')', ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 3: Initial Athlete Selection -->
        <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                3. Squad Roster Athletes (Optional Initial Roster)
            </h5>
            <p class="text-muted small mb-3">Select athletes to enroll in this squad. You can also assign or transfer athletes anytime later.</p>

            <div class="row g-2" id="rosterContainer" style="max-height: 220px; overflow-y: auto; padding-right: 5px;">
                <?php foreach ($athletes as $ath): ?>
                    <div class="col-md-4 col-sm-6 athlete-item" 
                         data-sport="<?= (int)$ath['current_sport_id'] ?>" 
                         data-gender="<?= htmlspecialchars($ath['gender'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                         data-dob="<?= htmlspecialchars($ath['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-check p-2 border rounded" style="background: var(--ks-page-bg);">
                            <input class="form-check-input ms-0 me-2 athlete-checkbox" type="checkbox" name="athlete_ids[]" value="<?= (int)$ath['id'] ?>" id="ath_<?= (int)$ath['id'] ?>">
                            <label class="form-check-label small fw-medium text-dark" for="ath_<?= (int)$ath['id'] ?>">
                                <?= htmlspecialchars($ath['first_name'] . ' ' . $ath['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                <span class="text-muted" style="font-size: 11px;">(<?= htmlspecialchars($ath['athlete_code'], ENT_QUOTES, 'UTF-8') ?>)</span>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div id="noEligibleAthletesMessage" class="col-12 text-center text-muted small py-3" style="display: none;">
                    No eligible athletes found for the selected Sport, Gender, and Age Group.
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
            <a href="/teams" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                <i class="bi bi-check2 me-1"></i> Create Team
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sportSelect = document.querySelector('select[name="sport_id"]');
    const genderSelect = document.querySelector('select[name="gender"]');
    const ageGroupInput = document.querySelector('input[name="age_group"]');
    const athleteItems = document.querySelectorAll('.athlete-item');
    const noAthletesMessage = document.getElementById('noEligibleAthletesMessage');

    function calculateAge(dobStr) {
        if (!dobStr) return null;
        const dob = new Date(dobStr);
        if (isNaN(dob.getTime())) return null;
        const diff_ms = Date.now() - dob.getTime();
        const age_dt = new Date(diff_ms); 
        return Math.abs(age_dt.getUTCFullYear() - 1970);
    }

    function isAgeEligible(age, ageGroupStr) {
        if (age === null) return true; 
        
        ageGroupStr = ageGroupStr.toLowerCase().trim();
        if (ageGroupStr === 'senior' || ageGroupStr === 'open' || ageGroupStr === '') return true;
        
        let match = ageGroupStr.match(/u(?:nder)?[-_ ]?(\d+)/);
        if (match) {
            const maxAge = parseInt(match[1], 10);
            return age <= maxAge;
        }
        
        match = ageGroupStr.match(/over[-_ ]?(\d+)/);
        if (match) {
            const minAge = parseInt(match[1], 10);
            return age >= minAge;
        }

        return true; 
    }

    function filterRoster() {
        const selectedSportVal = sportSelect.value;
        const selectedGender = genderSelect.value.toLowerCase();
        const selectedAgeGroup = ageGroupInput.value;

        let visibleCount = 0;

        athleteItems.forEach(item => {
            const athSport = item.getAttribute('data-sport');
            const athGender = item.getAttribute('data-gender').toLowerCase();
            const athDob = item.getAttribute('data-dob');
            const checkbox = item.querySelector('.athlete-checkbox');

            let isEligible = true;

            // 1. Sport (value is directly the ID)
            if (selectedSportVal && athSport !== selectedSportVal) {
                isEligible = false;
            }

            // 2. Gender
            if (selectedGender !== 'mixed' && selectedGender !== 'open' && selectedGender !== '') {
                if (athGender !== selectedGender) {
                    isEligible = false;
                }
            }

            // 3. Age Group
            const athAge = calculateAge(athDob);
            if (!isAgeEligible(athAge, selectedAgeGroup)) {
                isEligible = false;
            }

            if (isEligible) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
                if (checkbox.checked) {
                    checkbox.checked = false;
                }
            }
        });

        if (visibleCount === 0) {
            noAthletesMessage.style.display = 'block';
        } else {
            noAthletesMessage.style.display = 'none';
        }
    }

    sportSelect.addEventListener('change', filterRoster);
    genderSelect.addEventListener('change', filterRoster);
    ageGroupInput.addEventListener('input', filterRoster);

    // Initial load filtering
    filterRoster();
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
