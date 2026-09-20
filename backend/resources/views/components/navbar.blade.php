<?php
$currentRole = strtolower($_GET['role'] ?? 'sports_admin');

$roleLabels = [
    'sports_admin' => ['name' => 'Sports Administrator', 'sub' => 'Administrator', 'initials' => 'SA'],
    'coach' => ['name' => 'Rajesh Sharma', 'sub' => 'Head Coach', 'initials' => 'RS'],
    'athlete' => ['name' => 'Aarav Patel', 'sub' => 'Athlete (Football)', 'initials' => 'AP'],
    'hr_finance' => ['name' => 'Pooja Mehta', 'sub' => 'HR & Finance Lead', 'initials' => 'PM'],
    'venue_manager' => ['name' => 'Vikram Joshi', 'sub' => 'Venue & Tournament Manager', 'initials' => 'VJ'],
    'inventory_manager' => ['name' => 'Karan Singhania', 'sub' => 'Inventory Manager', 'initials' => 'KS'],
    'super_admin' => ['name' => 'Platform Super Admin', 'sub' => 'KhelSutra Provider', 'initials' => 'SU'],
];

$userMeta = $roleLabels[$currentRole] ?? $roleLabels['sports_admin'];
?>

<header class="ks-top-header">
    <div class="d-flex align-items-center gap-3">
        <!-- Mobile Toggle Button -->
        <button class="ks-mobile-toggle" id="ksMobileToggle" aria-label="Toggle navigation">
            <i class="bi bi-list"></i>
        </button>

        <!-- Dynamic Organisation Selector (Section 10) -->
        <div class="dropdown">
            <div class="ks-org-selector dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-building"></i>
                <span>Apex Sports Academy (ORG-DEMO)</span>
                <i class="bi bi-chevron-down" style="font-size: 11px;"></i>
            </div>
            <ul class="dropdown-menu shadow-sm" style="font-size: 13px; border-radius: 10px;">
                <li><h6 class="dropdown-header text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Switch Organisation</h6></li>
                <li><a class="dropdown-item active fw-semibold" href="#"><i class="bi bi-check2 me-2"></i> Apex Sports Academy (ORG-DEMO)</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-building me-2"></i> National Football Academy (ORG-NFA)</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-building me-2"></i> Olympic Excellence Center (ORG-OEC)</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-primary" href="/settings#orgs"><i class="bi bi-plus-circle me-2"></i> Manage Organisations</a></li>
            </ul>
        </div>
    </div>

    <!-- Global Search (Section 11) -->
    <div class="ks-global-search">
        <i class="bi bi-search"></i>
        <input type="text" class="ks-search-input" placeholder="Search athletes, teams, tournaments..." aria-label="Global Search">
    </div>

    <!-- Header Right (Section 12, 13 & Role Switcher) -->
    <div class="ks-header-right">
        <!-- Role Preview Switcher Dropdown (Allows testing all 7 roles instantly) -->
        <div class="dropdown d-none d-md-block">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" style="font-size: 12px; border-radius: 8px; font-weight: 500;">
                <i class="bi bi-person-gear me-1"></i> Role: <?= htmlspecialchars($userMeta['sub']) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px; border-radius: 10px;">
                <li><h6 class="dropdown-header text-uppercase" style="font-size: 11px;">Preview Role Navigation</h6></li>
                <li><a class="dropdown-item <?= $currentRole === 'sports_admin' ? 'active' : '' ?>" href="?role=sports_admin">Sports Administrator</a></li>
                <li><a class="dropdown-item <?= $currentRole === 'coach' ? 'active' : '' ?>" href="?role=coach">Coach (Rajesh Sharma)</a></li>
                <li><a class="dropdown-item <?= $currentRole === 'athlete' ? 'active' : '' ?>" href="?role=athlete">Athlete (Aarav Patel)</a></li>
                <li><a class="dropdown-item <?= $currentRole === 'hr_finance' ? 'active' : '' ?>" href="?role=hr_finance">HR & Finance</a></li>
                <li><a class="dropdown-item <?= $currentRole === 'venue_manager' ? 'active' : '' ?>" href="?role=venue_manager">Venue & Tournament Manager</a></li>
                <li><a class="dropdown-item <?= $currentRole === 'inventory_manager' ? 'active' : '' ?>" href="?role=inventory_manager">Inventory Manager</a></li>
                <li><a class="dropdown-item <?= $currentRole === 'super_admin' ? 'active' : '' ?>" href="?role=super_admin">Super Admin</a></li>
            </ul>
        </div>

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
                <div class="ks-avatar-circle"><?= $userMeta['initials'] ?></div>
                <div class="d-none d-sm-block text-start">
                    <div class="ks-user-name"><?= htmlspecialchars($userMeta['name']) ?></div>
                    <div class="ks-user-role"><?= htmlspecialchars($userMeta['sub']) ?></div>
                </div>
                <i class="bi bi-chevron-down text-muted" style="font-size: 11px;"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px; border-radius: 10px;">
                <li><a class="dropdown-item" href="/settings#profile"><i class="bi bi-person me-2"></i> My Profile</a></li>
                <li><a class="dropdown-item" href="/settings"><i class="bi bi-gear me-2"></i> Account Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="/login"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
            </ul>
        </div>
    </div>
</header>
