<?php
$activePage = 'search';
$title = 'Global Search Results — KhelSutra';

$auth = $_SESSION['auth'] ?? null;
$isSuperAdmin = ((int)($auth['role']['id'] ?? 0) === 1) || (($auth['role']['name'] ?? '') === 'Super Admin');

$query = trim($_GET['q'] ?? '');
$results = [
    'organizations' => [],
    'users' => [],
    'sections' => [],
    'athletes' => [],
    'teams' => [],
    'coaches' => [],
    'tournaments' => [],
];

if (!empty($query)) {
    $pdo = \App\Services\BaseService::getDatabaseConnection();
    $orgId = current_organization_id();
    $term = "%{$query}%";

    // 0. Platform Sections
    $allSections = [
        ['title' => 'Dashboard', 'subtitle' => 'Platform executive overview & statistics', 'url' => '/dashboard', 'icon' => 'bi-speedometer2', 'badge' => 'Section', 'keywords' => ['dashboard', 'overview', 'stats']],
        ['title' => 'Organisations', 'subtitle' => 'Manage sports academies & organisations directory', 'url' => '/super-admin/organizations', 'icon' => 'bi-building', 'badge' => 'Super Admin', 'keywords' => ['organisations', 'organizations', 'academies', 'orgs']],
        ['title' => 'Users', 'subtitle' => 'Global user accounts, roles & directory', 'url' => '/users', 'icon' => 'bi-people', 'badge' => 'Directory', 'keywords' => ['users', 'accounts', 'directory', 'people', 'staff']],
        ['title' => 'Audit Logs', 'subtitle' => 'Platform security & operational activity logs', 'url' => '/audit', 'icon' => 'bi-shield-check', 'badge' => 'Security', 'keywords' => ['audit', 'logs', 'security', 'history']],
        ['title' => 'Account Settings', 'subtitle' => 'Personal account settings & security', 'url' => '/settings', 'icon' => 'bi-person-circle', 'badge' => 'Settings', 'keywords' => ['profile', 'account', 'settings', 'password']],
        ['title' => 'Notifications', 'subtitle' => 'Alerts, updates & activity notifications', 'url' => '/notifications', 'icon' => 'bi-bell', 'badge' => 'Alerts', 'keywords' => ['notifications', 'alerts', 'updates']],
        ['title' => 'Roles & Permissions', 'subtitle' => 'RBAC access control & authorization', 'url' => '/roles', 'icon' => 'bi-shield-lock', 'badge' => 'Security', 'keywords' => ['roles', 'permissions', 'rbac']]
    ];

    $lq = mb_strtolower($query);
    foreach ($allSections as $s) {
        if (!$isSuperAdmin && $s['title'] === 'Organisations') continue;
        $matched = (mb_stripos($s['title'], $lq) !== false) || (mb_stripos($s['subtitle'], $lq) !== false);
        if (!$matched) {
            foreach ($s['keywords'] as $kw) {
                if (mb_stripos($kw, $lq) !== false || mb_stripos($lq, $kw) !== false) {
                    $matched = true;
                    break;
                }
            }
        }
        if ($matched) {
            $results['sections'][] = $s;
        }
    }

    if ($pdo) {
        // 1. Organisations (Super Admin or Tenant)
        $orgSql = "
            SELECT id, name, organization_code, legal_name, email, phone, status, plan_name
            FROM organizations
            WHERE deleted_at IS NULL
              AND (name LIKE :q1 OR organization_code LIKE :q2 OR legal_name LIKE :q3 OR email LIKE :q4 OR phone LIKE :q5)
        ";
        if (!$isSuperAdmin) {
            $orgSql .= " AND id = " . (int)$orgId;
        }
        $orgSql .= " ORDER BY name ASC LIMIT 10";

        try {
            $stmt = $pdo->prepare($orgSql);
            $stmt->execute([':q1' => $term, ':q2' => $term, ':q3' => $term, ':q4' => $term, ':q5' => $term]);
            $results['organizations'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        // 2. Users / Accounts
        $userSql = "
            SELECT u.id, u.first_name, u.last_name, u.username, u.email, u.phone, u.status,
                   r.name as role_name, o.name as org_name
            FROM users u
            LEFT JOIN organization_users ou ON u.id = ou.user_id
            LEFT JOIN roles r ON ou.role_id = r.id
            LEFT JOIN organizations o ON ou.organization_id = o.id
            WHERE u.deleted_at IS NULL
              AND (
                  u.first_name LIKE :q1 OR u.last_name LIKE :q2 OR CONCAT(u.first_name, ' ', u.last_name) LIKE :q3
                  OR u.email LIKE :q4 OR u.username LIKE :q5 OR u.phone LIKE :q6 OR r.name LIKE :q7
              )
        ";
        if (!$isSuperAdmin) {
            $userSql .= " AND ou.organization_id = " . (int)$orgId;
        }
        $userSql .= " GROUP BY u.id ORDER BY u.id DESC LIMIT 10";

        try {
            $stmt = $pdo->prepare($userSql);
            $stmt->execute([
                ':q1' => $term, ':q2' => $term, ':q3' => $term,
                ':q4' => $term, ':q5' => $term, ':q6' => $term, ':q7' => $term
            ]);
            $results['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        // 3. Athletes
        $stmt = $pdo->prepare("
            SELECT a.id, a.athlete_code, a.first_name, a.last_name, a.email, a.status, s.name as sport_name
            FROM athletes a
            LEFT JOIN sports s ON a.current_sport_id = s.id
            WHERE a.organization_id = :org AND a.deleted_at IS NULL
              AND (a.first_name LIKE :q OR a.last_name LIKE :q OR a.athlete_code LIKE :q OR a.email LIKE :q)
            LIMIT 10
        ");
        $stmt->execute([':org' => $orgId, ':q' => $term]);
        $results['athletes'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 4. Teams
        $stmt = $pdo->prepare("
            SELECT t.id, t.team_code, t.name, t.age_group, s.name as sport_name
            FROM teams t
            LEFT JOIN sports s ON t.sport_id = s.id
            WHERE t.organization_id = :org AND t.deleted_at IS NULL
              AND (t.name LIKE :q OR t.team_code LIKE :q)
            LIMIT 10
        ");
        $stmt->execute([':org' => $orgId, ':q' => $term]);
        $results['teams'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 5. Coaches
        $stmt = $pdo->prepare("
            SELECT cp.id as coach_id, cp.coach_code, cp.specialization, e.first_name, e.last_name, e.email
            FROM coach_profiles cp
            JOIN employees e ON cp.employee_id = e.id
            WHERE cp.organization_id = :org AND cp.deleted_at IS NULL AND e.deleted_at IS NULL
              AND (e.first_name LIKE :q OR e.last_name LIKE :q OR cp.coach_code LIKE :q OR cp.specialization LIKE :q)
            LIMIT 10
        ");
        $stmt->execute([':org' => $orgId, ':q' => $term]);
        $results['coaches'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // 6. Tournaments
        $stmt = $pdo->prepare("
            SELECT t.id, t.tournament_reference, t.name, t.status, s.name as sport_name
            FROM tournaments t
            LEFT JOIN sports s ON t.sport_id = s.id
            WHERE t.organization_id = :org AND t.deleted_at IS NULL
              AND (t.name LIKE :q OR t.tournament_reference LIKE :q)
            LIMIT 10
        ");
        $stmt->execute([':org' => $orgId, ':q' => $term]);
        $results['tournaments'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

$totalFound = count($results['organizations']) + count($results['users']) + count($results['sections'])
            + count($results['athletes']) + count($results['teams']) + count($results['coaches']) + count($results['tournaments']);

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
                <div class="small mt-1">No organisations, users, sections, athletes, teams, or competitions matched your search query.</div>
            </div>
        <?php else: ?>

            <!-- Organisations Results -->
            <?php if (!empty($results['organizations'])): ?>
                <div class="mb-4">
                    <h5 class="fw-bold text-navy mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-building text-primary"></i>
                        <span>Organisations (<?= count($results['organizations']) ?>)</span>
                    </h5>
                    <div class="list-group">
                        <?php foreach ($results['organizations'] as $org): ?>
                            <a href="/super-admin/organizations/<?= (int)$org['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <span class="fw-bold text-navy"><?= htmlspecialchars($org['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if (!empty($org['organization_code'])): ?>
                                        <span class="badge bg-light text-dark border ms-2"><?= htmlspecialchars($org['organization_code'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($org['plan_name'])): ?>
                                        <span class="badge bg-primary-subtle text-primary ms-1"><?= htmlspecialchars($org['plan_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($org['email'])): ?>
                                        <span class="text-muted small ms-2"><?= htmlspecialchars($org['email'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge <?= ($org['status'] ?? 'active') === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> text-capitalize">
                                        <?= htmlspecialchars($org['status'] ?? 'active', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span class="btn btn-sm btn-outline-primary">View Organisation</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Users Results -->
            <?php if (!empty($results['users'])): ?>
                <div class="mb-4">
                    <h5 class="fw-bold text-navy mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-people text-purple" style="color: #7C3AED;"></i>
                        <span>Users (<?= count($results['users']) ?>)</span>
                    </h5>
                    <div class="list-group">
                        <?php foreach ($results['users'] as $u): ?>
                            <?php
                            $fullName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                            if (empty($fullName)) $fullName = $u['username'] ?? 'User';
                            $roleName = ((int)($u['role_id'] ?? 0) === 2 || ($u['role_name'] ?? '') === 'Sports Administrator')
                                ? 'Organisation Admin'
                                : ($u['role_name'] ?? 'Member');
                            ?>
                            <a href="/users/<?= (int)$u['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <span class="fw-bold text-navy"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="badge bg-purple-subtle text-purple border ms-2" style="background: #F3E8FF; color: #7C3AED;"><?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if (!empty($u['email'])): ?>
                                        <span class="text-muted small ms-2"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($u['org_name'])): ?>
                                        <span class="text-muted small ms-2">· <?= htmlspecialchars($u['org_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="btn btn-sm btn-outline-primary">View User</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Sections Results -->
            <?php if (!empty($results['sections'])): ?>
                <div class="mb-4">
                    <h5 class="fw-bold text-navy mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-grid text-secondary"></i>
                        <span>Platform Sections (<?= count($results['sections']) ?>)</span>
                    </h5>
                    <div class="list-group">
                        <?php foreach ($results['sections'] as $sec): ?>
                            <a href="<?= htmlspecialchars($sec['url'], ENT_QUOTES, 'UTF-8') ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi <?= htmlspecialchars($sec['icon'], ENT_QUOTES, 'UTF-8') ?> fs-5 text-primary"></i>
                                    <div>
                                        <div class="fw-bold text-navy"><?= htmlspecialchars($sec['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($sec['subtitle'], ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                </div>
                                <span class="btn btn-sm btn-outline-secondary">Go to Section</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

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
