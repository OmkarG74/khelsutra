<?php
$tournId = (int)($id ?? ($_GET['id'] ?? 0));
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

$canEditTournament = !$isCoach && !$isAthlete && (
    $permissionService->hasPermission($userPayload, 'tournament.update', $orgId) ||
    $permissionService->hasPermission($userPayload, 'tournament.manage', $orgId)
);

if (!$canEditTournament) {
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo '<div class="p-5 text-center text-danger fw-bold">403 Forbidden: You do not have permission to edit tournaments.</div>';
    return;
}

$tournService = new \App\Services\Tournament\TournamentService();
$tournament = $tournService->getTournament($orgId, $tournId);

$db = \App\Services\BaseService::getDatabaseConnection();
$sportService = new \App\Services\Sport\SportService();
$sports = [];
foreach (config('sports.catalog') ?? [] as $key => $name) {
    $dbId = $sportService->resolveSportId($key);
    $sports[] = ['id' => $dbId, 'name' => $name];
}
usort($sports, fn($a, $b) => strcmp($a['name'], $b['name']));

$levels = [];
$formats = [];
if ($db) {
    $lvlStmt = $db->query("SELECT id, name FROM tournament_levels ORDER BY id ASC");
    $levels = $lvlStmt ? $lvlStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $fmtStmt = $db->query("SELECT id, name FROM tournament_formats ORDER BY id ASC");
    $formats = $fmtStmt ? $fmtStmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

$title = $tournament ? 'Edit Tournament — ' . htmlspecialchars($tournament['name']) : 'Edit Tournament';
$pageTitle = $title;
$activePage = 'tournaments';

ob_start();
?>

<div class="ks-page">
    <?php if (!$tournament): ?>
        <div class="ks-content-card p-5 text-center my-4">
            <div class="mb-3"><i class="bi bi-trophy fs-1 text-muted"></i></div>
            <h4 class="fw-bold text-dark">Tournament Not Found</h4>
            <p class="text-muted small">The requested tournament championship does not exist, has been archived, or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/tournaments" class="btn btn-outline-secondary" style="border-radius: 8px; font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Tournaments
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Page Header -->
        <div class="mb-3">
            <a href="/tournaments/<?= (int)$tournament['id'] ?>" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1 mb-2" style="font-size: 12.5px;">
                <i class="bi bi-arrow-left"></i> Back to Tournament Details
            </a>
            <div class="ks-page-header mb-0">
                <div>
                    <h1 class="ks-page-title mb-1">Edit Tournament</h1>
                    <p class="text-muted small mb-0">
                        Updating tournament settings for <strong><?= htmlspecialchars($tournament['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                        (<?= htmlspecialchars($tournament['tournament_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                    </p>
                </div>
                <div class="ks-header-actions">
                    <a href="/tournaments/<?= (int)$tournament['id'] ?>" class="btn btn-outline-secondary px-3 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">Cancel</a>
                    <button type="submit" form="editTournamentForm" class="ks-btn ks-btn-primary px-4 py-2">
                        <i class="bi bi-check-lg"></i>
                        <span>Save Tournament</span>
                    </button>
                </div>
            </div>
        </div>

        <form action="/tournaments/<?= (int)$tournament['id'] ?>/edit" method="POST" id="editTournamentForm">
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
                            <input type="text" name="name" class="ks-form-control" value="<?= htmlspecialchars($tournament['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Sport <span class="text-danger">*</span></label>
                            <select name="sport_id" id="sportSelect" class="ks-form-select" required>
                                <?php foreach ($sports as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>" <?= $tournament['sport_id'] == $s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="ks-form-label">Competition Level <span class="text-danger">*</span></label>
                            <select name="tournament_level_id" class="ks-form-select" required>
                                <?php foreach ($levels as $lvl): ?>
                                    <option value="<?= (int)$lvl['id'] ?>" <?= $tournament['tournament_level_id'] == $lvl['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($lvl['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Tournament Format <span class="text-danger">*</span></label>
                            <select name="tournament_format_id" class="ks-form-select" required>
                                <?php foreach ($formats as $fmt): ?>
                                    <option value="<?= (int)$fmt['id'] ?>" <?= $tournament['tournament_format_id'] == $fmt['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($fmt['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="ks-form-select" required>
                                <option value="draft" <?= ($tournament['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="registration_open" <?= ($tournament['status'] ?? '') === 'registration_open' ? 'selected' : '' ?>>Registration Open</option>
                                <option value="registration_closed" <?= ($tournament['status'] ?? '') === 'registration_closed' ? 'selected' : '' ?>>Registration Closed</option>
                                <option value="ongoing" <?= ($tournament['status'] ?? '') === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="completed" <?= ($tournament['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= ($tournament['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="ks-form-label">Organizer Name</label>
                            <input type="text" name="organizer_name" class="ks-form-control" value="<?= htmlspecialchars($tournament['organizer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
                            <label class="ks-form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="ks-form-control" value="<?= htmlspecialchars($tournament['start_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="ks-form-control" value="<?= htmlspecialchars($tournament['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="ks-form-label">Location Name</label>
                            <input type="text" name="location_name" class="ks-form-control" value="<?= htmlspecialchars($tournament['location_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="ks-form-label">City</label>
                            <input type="text" name="city" class="ks-form-control" value="<?= htmlspecialchars($tournament['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="ks-form-label">State</label>
                            <input type="text" name="state" class="ks-form-control" value="<?= htmlspecialchars($tournament['state'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Rules & Guidelines -->
            <div class="ks-content-card mb-4">
                <div class="ks-card-header py-2 px-3">
                    <div class="ks-header-left">
                        <i class="bi bi-card-text text-primary fs-6"></i>
                        <h3 class="ks-header-title mb-0" style="font-size: 14.5px;">3. Description &amp; Competition Rules</h3>
                    </div>
                </div>
                <div class="p-3 px-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="ks-form-label">Tournament Description</label>
                            <textarea name="description" class="ks-form-control" rows="2" style="height: auto;"><?= htmlspecialchars($tournament['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="ks-form-label">Rules &amp; Regulations</label>
                            <textarea name="rules" class="ks-form-control" rows="2" style="height: auto;"><?= htmlspecialchars($tournament['rules'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex align-items-center justify-content-end gap-2 mb-4">
                <a href="/tournaments/<?= (int)$tournament['id'] ?>" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px; font-size: 13px; font-weight: 500;">
                    Cancel
                </a>
                <button type="submit" class="ks-btn ks-btn-primary px-4 py-2">
                    <i class="bi bi-check-lg"></i>
                    <span>Save Tournament</span>
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
