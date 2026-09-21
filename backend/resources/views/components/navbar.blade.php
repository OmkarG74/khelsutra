<?php
$auth = $_SESSION['auth'] ?? null;

if ($auth) {
    $userName = trim(($auth['user']['first_name'] ?? '') . ' ' . ($auth['user']['last_name'] ?? ''));
    $userRole = $auth['role']['name'] ?? 'Sports Administrator';
    $fInit = substr($auth['user']['first_name'] ?? 'S', 0, 1);
    $lInit = substr($auth['user']['last_name'] ?? 'A', 0, 1);
    $initials = strtoupper($fInit . $lInit);
    $isSuperAdmin = ((int)($auth['role']['id'] ?? 0) === 1) || ($userRole === 'Super Admin');
    $orgBadgeText = $isSuperAdmin ? 'KhelSutra Platform' : ($auth['organization']['name'] ?? 'Apex Sports Academy') . ' (' . ($auth['organization']['organization_code'] ?? 'ORG-DEMO') . ')';
} else {
    // Default Sports Administrator context
    $userName = 'Rajesh Sharma';
    $userRole = 'Sports Administrator';
    $initials = 'RS';
    $isSuperAdmin = false;
    $orgBadgeText = 'Apex Sports Academy (ORG-DEMO)';
}
?>

<header class="ks-top-header">
    <div class="d-flex align-items-center gap-3">
        <!-- Mobile Toggle Button -->
        <button class="ks-mobile-toggle" id="ksMobileToggle" aria-label="Toggle navigation">
            <i class="bi bi-list"></i>
        </button>

        <!-- Static Organisation Context (No Switcher - Tenant Enforced) -->
        <div class="ks-org-selector" style="cursor: default;">
            <i class="bi bi-building"></i>
            <span class="fw-semibold"><?= htmlspecialchars($orgBadgeText) ?></span>
        </div>
    </div>

    <!-- Global Search (Rule 41: Functional Search) -->
    <form action="/search" method="GET" class="ks-global-search m-0">
        <i class="bi bi-search"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="ks-search-input" placeholder="Search athletes, teams, tournaments..." aria-label="Global Search">
    </form>

    <!-- Header Right -->
    <div class="ks-header-right">
        <!-- Notification Bell (Section 12) -->
        <div class="dropdown">
            <button class="ks-notification-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                <i class="bi bi-bell fs-5"></i>
                <span class="ks-notification-badge">3</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="width: 320px; border-radius: 12px; font-size: 13px; padding: 12px 0;">
                <li class="px-3 pb-2 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Notifications</span>
                    <span class="badge bg-primary-subtle text-primary">3 New</span>
                </li>
                <li>
                    <a class="dropdown-item py-2 d-flex gap-2" href="#">
                        <i class="bi bi-calendar-event text-primary mt-1"></i>
                        <div>
                            <div class="fw-semibold">New Tournament Fixture</div>
                            <div class="text-muted small">Titans U-18 scheduled for 22 Sep</div>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2 d-flex gap-2" href="#">
                        <i class="bi bi-clock-history text-warning mt-1"></i>
                        <div>
                            <div class="fw-semibold">Leave Request Pending</div>
                            <div class="text-muted small">Coach Amit requested 2 days leave</div>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2 d-flex gap-2" href="#">
                        <i class="bi bi-box-seam text-danger mt-1"></i>
                        <div>
                            <div class="fw-semibold">Low Inventory Alert</div>
                            <div class="text-muted small">Football stock below threshold (3 items)</div>
                        </div>
                    </a>
                </li>
                <li class="pt-2 border-top text-center">
                    <a href="/settings#notifications" class="text-decoration-none small text-primary fw-semibold">View All Notifications</a>
                </li>
            </ul>
        </div>

        <!-- User Profile (Section 13) -->
        <div class="dropdown">
            <div class="ks-user-widget dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="ks-avatar-circle"><?= htmlspecialchars($initials) ?></div>
                <div class="d-none d-sm-block text-start">
                    <div class="ks-user-name"><?= htmlspecialchars($userName) ?></div>
                    <div class="ks-user-role"><?= htmlspecialchars($userRole) ?></div>
                </div>
                <i class="bi bi-chevron-down text-muted" style="font-size: 11px;"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px; border-radius: 10px;">
                <li><a class="dropdown-item" href="/settings#profile"><i class="bi bi-person me-2"></i> My Profile</a></li>
                <li><a class="dropdown-item" href="/settings"><i class="bi bi-gear me-2"></i> Account Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="/logout"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
            </ul>
        </div>
    </div>
</header>
