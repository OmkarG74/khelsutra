<?php
$teamId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();
$teamService = new \App\Services\Team\TeamService();
$team = $teamService->getTeam($orgId, $teamId);

$db = \App\Services\BaseService::getDatabaseConnection();

// Sports
$sportsStmt = $db->query("SELECT id, name FROM sports ORDER BY name ASC");
$sports = $sportsStmt ? $sportsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

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

// Find current primary coach id
$primaryCoachId = null;
if (!empty($team['coaches'])) {
    foreach ($team['coaches'] as $c) {
        if (!empty($c['is_primary'])) {
            $primaryCoachId = $c['coach_profile_id'];
            break;
        }
    }
}

$pageTitle = $team ? 'Edit Team — ' . htmlspecialchars($team['name']) : 'Edit Team';
$activePage = 'teams';

ob_start();
?>

<div class="ks-content">
    <?php if (!$team): ?>
        <div class="card p-5 text-center" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <div class="mb-3"><i class="bi bi-shield-x fs-1 text-muted"></i></div>
            <h4 class="fw-bold" style="color: var(--ks-navy);">Team Not Found</h4>
            <p class="text-muted small">The requested team squad does not exist or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/teams" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Teams
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="/teams/<?= (int)$team['id'] ?>" class="text-decoration-none text-muted small fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Team Details
            </a>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h4 fw-bold mb-0" style="color: var(--ks-navy);">Edit Team: <?= htmlspecialchars($team['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="text-muted small mt-1">Code: <strong><?= htmlspecialchars($team['team_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></div>
            </div>
        </div>

        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger mb-4 py-2 px-3 small d-flex align-items-center gap-2" style="border-radius: var(--ks-radius-button);">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <form action="/teams/<?= (int)$team['id'] ?>/edit" method="POST" id="editTeamForm">
            <!-- Section 1: Team Profile -->
            <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                    1. Team Profile & Sport
                </h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Team Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($team['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Sport <span class="text-danger">*</span></label>
                        <select name="sport_id" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <?php foreach ($sports as $sp): ?>
                                <option value="<?= (int)$sp['id'] ?>" <?= $team['sport_id'] == $sp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Gender Division <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="male" <?= ($team['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= ($team['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="mixed" <?= ($team['gender'] ?? '') === 'mixed' ? 'selected' : '' ?>>Mixed</option>
                            <option value="open" <?= ($team['gender'] ?? '') === 'open' ? 'selected' : '' ?>>Open</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Age Group <span class="text-danger">*</span></label>
                        <input type="text" name="age_group" class="form-control" value="<?= htmlspecialchars($team['age_group'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Formation / Competition Level</label>
                        <input type="text" name="formation_or_level" class="form-control" value="<?= htmlspecialchars($team['formation_or_level'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Team Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="active" <?= ($team['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($team['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold text-dark">Description</label>
                        <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($team['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                </div>
            </div>

            <!-- Section 2: Coaching Assignment -->
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
                                <option value="<?= (int)$c['coach_id'] ?>" <?= $primaryCoachId == $c['coach_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (' . $c['specialization'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
                <a href="/teams/<?= (int)$team['id'] ?>" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
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
