<?php
$activePage = 'search';
$title = 'Global Search Results — KhelSutra';

$query = trim($_GET['q'] ?? '');
$results = [
    'athletes' => [],
    'teams' => [],
    'coaches' => [],
    'tournaments' => [],
];

if (!empty($query)) {
    $pdo = \App\Services\BaseService::getDatabaseConnection();
    $orgId = current_organization_id();
    $term = "%{$query}%";

    // 1. Athletes
    $stmt = $pdo->prepare("
        SELECT a.id, a.athlete_code, a.first_name, a.last_name, a.email, a.status, s.name as sport_name
        FROM athletes a
        LEFT JOIN sports s ON a.current_sport_id = s.id
        WHERE a.organization_id = :org AND a.deleted_at IS NULL
          AND (a.first_name LIKE :q OR a.last_name LIKE :q OR a.athlete_code LIKE :q OR a.email LIKE :q)
        LIMIT 10
    ");
    $stmt->execute([':org' => $orgId, ':q' => $term]);
    $results['athletes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Teams
    $stmt = $pdo->prepare("
        SELECT t.id, t.team_code, t.name, t.age_group, s.name as sport_name
        FROM teams t
        LEFT JOIN sports s ON t.sport_id = s.id
        WHERE t.organization_id = :org AND t.deleted_at IS NULL
          AND (t.name LIKE :q OR t.team_code LIKE :q)
        LIMIT 10
    ");
    $stmt->execute([':org' => $orgId, ':q' => $term]);
    $results['teams'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Coaches
    $stmt = $pdo->prepare("
        SELECT cp.id as coach_id, cp.coach_code, cp.specialization, e.first_name, e.last_name, e.email
        FROM coach_profiles cp
        JOIN employees e ON cp.employee_id = e.id
        WHERE cp.organization_id = :org AND cp.deleted_at IS NULL AND e.deleted_at IS NULL
          AND (e.first_name LIKE :q OR e.last_name LIKE :q OR cp.coach_code LIKE :q OR cp.specialization LIKE :q)
        LIMIT 10
    ");
    $stmt->execute([':org' => $orgId, ':q' => $term]);
    $results['coaches'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Tournaments
    $stmt = $pdo->prepare("
        SELECT t.id, t.tournament_reference, t.name, t.status, s.name as sport_name
        FROM tournaments t
        LEFT JOIN sports s ON t.sport_id = s.id
        WHERE t.organization_id = :org AND t.deleted_at IS NULL
          AND (t.name LIKE :q OR t.tournament_reference LIKE :q)
        LIMIT 10
    ");
    $stmt->execute([':org' => $orgId, ':q' => $term]);
    $results['tournaments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalFound = count($results['athletes']) + count($results['teams']) + count($results['coaches']) + count($results['tournaments']);

ob_start();
?>

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Search Results</h1>
    </div>
    <div class="ks-header-actions">
        <a href="/dashboard" class="ks-btn ks-btn-secondary">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Dashboard</span>
        </a>
    </div>
</div>

<div class="ks-content-card mb-4">
    <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
        <div class="fw-semibold text-navy">
            Search query: <span class="text-primary">"<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>"</span>
        </div>
        <div class="badge bg-primary px-3 py-2" style="font-size: 12px;">
            <?= $totalFound ?> matching results
        </div>
    </div>

    <div class="p-4">
        <?php if ($totalFound === 0): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-search d-block fs-1 mb-2" style="color: var(--ks-text-muted);"></i>
                <div class="fw-semibold fs-5 text-navy">No records found</div>
                <div class="small mt-1">No athletes, teams, coaches, or tournaments matched your search query.</div>
            </div>
        <?php else: ?>
            <!-- Athletes Results -->
            <?php if (!empty($results['athletes'])): ?>
                <div class="mb-4">
                    <h5 class="fw-bold text-navy mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-person-walking text-primary"></i>
                        <span>Athletes (<?= count($results['athletes']) ?>)</span>
                    </h5>
                    <div class="list-group">
                        <?php foreach ($results['athletes'] as $ath): ?>
                            <a href="/athletes/<?= (int)$ath['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <span class="fw-bold text-navy"><?= htmlspecialchars(($ath['first_name'] ?? '') . ' ' . ($ath['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="badge bg-light text-dark border ms-2"><?= htmlspecialchars($ath['athlete_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="ks-badge ks-badge-blue ms-1"><?= htmlspecialchars($ath['sport_name'] ?? 'Sport', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <span class="btn btn-sm btn-outline-primary">View Details</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Teams Results -->
            <?php if (!empty($results['teams'])): ?>
                <div class="mb-4">
                    <h5 class="fw-bold text-navy mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-shaded text-success"></i>
                        <span>Teams (<?= count($results['teams']) ?>)</span>
                    </h5>
                    <div class="list-group">
                        <?php foreach ($results['teams'] as $tm): ?>
                            <a href="/teams/<?= (int)$tm['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <span class="fw-bold text-navy"><?= htmlspecialchars($tm['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="badge bg-light text-dark border ms-2"><?= htmlspecialchars($tm['team_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="badge bg-success-subtle text-success ms-1"><?= htmlspecialchars($tm['sport_name'] ?? 'Sport', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <span class="btn btn-sm btn-outline-primary">View Details</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Coaches Results -->
            <?php if (!empty($results['coaches'])): ?>
                <div class="mb-4">
                    <h5 class="fw-bold text-navy mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge text-purple" style="color: #7C3AED;"></i>
                        <span>Coaches (<?= count($results['coaches']) ?>)</span>
                    </h5>
                    <div class="list-group">
                        <?php foreach ($results['coaches'] as $c): ?>
                            <a href="/coaches/<?= (int)$c['coach_id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <span class="fw-bold text-navy"><?= htmlspecialchars(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="badge bg-light text-dark border ms-2"><?= htmlspecialchars($c['coach_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-muted small ms-2"><?= htmlspecialchars($c['specialization'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <span class="btn btn-sm btn-outline-primary">View Details</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tournaments Results -->
            <?php if (!empty($results['tournaments'])): ?>
                <div class="mb-4">
                    <h5 class="fw-bold text-navy mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-trophy-fill text-warning"></i>
                        <span>Tournaments (<?= count($results['tournaments']) ?>)</span>
                    </h5>
                    <div class="list-group">
                        <?php foreach ($results['tournaments'] as $tr): ?>
                            <a href="/tournaments/<?= (int)$tr['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <span class="fw-bold text-navy"><?= htmlspecialchars($tr['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="badge bg-light text-dark border ms-2"><?= htmlspecialchars($tr['tournament_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="ks-badge ks-badge-amber ms-1 text-uppercase"><?= htmlspecialchars($tr['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <span class="btn btn-sm btn-outline-primary">View Details</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
