<?php
// Resolve current active role strictly from authenticated session (never from query parameters)
$currentRole = $_SESSION['auth']['role']['slug'] ?? 'sports_admin';
$activePage = $activePage ?? 'dashboard';

// Role-aware navigation definitions for all 7 application roles
$navMenus = [
    'sports_admin' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-house-door-fill', 'url' => '/dashboard'],
        ['key' => 'athletes', 'label' => 'Athletes', 'icon' => 'bi-person-walking', 'url' => '/athletes'],
        ['key' => 'coaches', 'label' => 'Coaches', 'icon' => 'bi-person-badge', 'url' => '/coaches'],
        ['key' => 'teams', 'label' => 'Teams', 'icon' => 'bi-people-fill', 'url' => '/teams'],
        ['key' => 'tournaments', 'label' => 'Tournaments', 'icon' => 'bi-trophy-fill', 'url' => '/tournaments'],
        ['key' => 'training', 'label' => 'Training', 'icon' => 'bi-stopwatch-fill', 'url' => '/training'],
        ['key' => 'venues', 'label' => 'Venues & Bookings', 'icon' => 'bi-geo-alt-fill', 'url' => '/venues'],
        ['key' => 'maintenance', 'label' => 'Maintenance', 'icon' => 'bi-tools', 'url' => '/operations/maintenance'],
        ['key' => 'events', 'label' => 'Events', 'icon' => 'bi-calendar-event', 'url' => '/operations/events'],
        ['key' => 'transport', 'label' => 'Transport', 'icon' => 'bi-truck', 'url' => '/operations/transport'],
        ['key' => 'accommodation', 'label' => 'Accommodation', 'icon' => 'bi-building-fill-add', 'url' => '/operations/accommodation'],
        ['key' => 'inventory', 'label' => 'Inventory', 'icon' => 'bi-box-seam-fill', 'url' => '/inventory'],
        ['key' => 'equipment', 'label' => 'Equipment', 'icon' => 'bi-tag-fill', 'url' => '/equipment'],
        ['key' => 'vendors', 'label' => 'Vendors & Suppliers', 'icon' => 'bi-truck', 'url' => '/vendors'],
        ['key' => 'purchases', 'label' => 'Purchases & Orders', 'icon' => 'bi-cart-check-fill', 'url' => '/purchases'],
        ['key' => 'finance', 'label' => 'Finance & Accounting', 'icon' => 'bi-wallet-fill', 'url' => '/finance'],
        ['key' => 'hr-finance', 'label' => 'Staff & HR', 'icon' => 'bi-briefcase-fill', 'url' => '/hr/employees'],
        ['key' => 'leave', 'label' => 'Leave Requests', 'icon' => 'bi-calendar-check', 'url' => '/leave'],
        ['key' => 'payroll', 'label' => 'Payroll', 'icon' => 'bi-cash-coin', 'url' => '/payroll'],
        ['key' => 'reports', 'label' => 'Reports', 'icon' => 'bi-bar-chart-fill', 'url' => '/reports'],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'bi-gear-fill', 'url' => '/settings/organization'],
    ],
    'coach' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-house-door-fill', 'url' => '/dashboard'],
        ['key' => 'teams', 'label' => 'Teams', 'icon' => 'bi-people-fill', 'url' => '/teams'],
        ['key' => 'training', 'label' => 'Training & Attendance', 'icon' => 'bi-stopwatch-fill', 'url' => '/training'],
        ['key' => 'athletes', 'label' => 'Athletes', 'icon' => 'bi-person-walking', 'url' => '/athletes'],
        ['key' => 'tournaments', 'label' => 'Tournaments', 'icon' => 'bi-trophy-fill', 'url' => '/tournaments'],
        ['key' => 'fixtures', 'label' => 'Fixtures', 'icon' => 'bi-calendar-event', 'url' => '/tournaments#fixtures'],
        ['key' => 'leave', 'label' => 'My Leave', 'icon' => 'bi-calendar-x', 'url' => '/leave'],
        ['key' => 'performance', 'label' => 'Performance', 'icon' => 'bi-graph-up-arrow', 'url' => '/athletes#performance'],
        ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'bi-bell-fill', 'url' => '/settings#notifications'],
    ],
    'athlete' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-house-door-fill', 'url' => '/dashboard'],
        ['key' => 'profile', 'label' => 'My Profile', 'icon' => 'bi-person-badge', 'url' => '/athletes'],
        ['key' => 'teams', 'label' => 'My Team', 'icon' => 'bi-people-fill', 'url' => '/teams'],
        ['key' => 'training', 'label' => 'Training', 'icon' => 'bi-stopwatch-fill', 'url' => '/training'],
        ['key' => 'attendance', 'label' => 'My Attendance', 'icon' => 'bi-calendar-check', 'url' => '/attendance/history'],
        ['key' => 'leave', 'label' => 'Apply Leave', 'icon' => 'bi-calendar-plus', 'url' => '/leave/create'],
        ['key' => 'performance', 'label' => 'Performance', 'icon' => 'bi-graph-up-arrow', 'url' => '/athletes'],
        ['key' => 'achievements', 'label' => 'Achievements', 'icon' => 'bi-award-fill', 'url' => '/athletes'],
        ['key' => 'tournaments', 'label' => 'Tournaments', 'icon' => 'bi-trophy-fill', 'url' => '/tournaments'],
        ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'bi-bell-fill', 'url' => '/settings#notifications'],
    ],
    'hr_finance' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-house-door-fill', 'url' => '/dashboard'],
        ['key' => 'hr-finance', 'label' => 'Staff Directory', 'icon' => 'bi-briefcase-fill', 'url' => '/hr/employees'],
        ['key' => 'training', 'label' => 'Training Attendance', 'icon' => 'bi-calendar-check', 'url' => '/attendance/training'],
        ['key' => 'matches', 'label' => 'Match Attendance', 'icon' => 'bi-trophy', 'url' => '/attendance/matches'],
        ['key' => 'leave', 'label' => 'Leave Requests', 'icon' => 'bi-calendar-x', 'url' => '/leave'],
        ['key' => 'payroll', 'label' => 'Payroll Operations', 'icon' => 'bi-cash-coin', 'url' => '/payroll'],
        ['key' => 'finance', 'label' => 'Finance & Accounting', 'icon' => 'bi-wallet-fill', 'url' => '/finance'],
        ['key' => 'salary-structures', 'label' => 'Salary Structures', 'icon' => 'bi-cash-stack', 'url' => '/payroll/salary-structures'],
        ['key' => 'periods', 'label' => 'Payroll Periods', 'icon' => 'bi-calendar-range', 'url' => '/payroll/periods'],
        ['key' => 'reports', 'label' => 'Reports', 'icon' => 'bi-bar-chart-fill', 'url' => '/reports'],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'bi-gear-fill', 'url' => '/settings/organization'],
    ],
    'venue_manager' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-house-door-fill', 'url' => '/dashboard'],
        ['key' => 'venues', 'label' => 'Venues & Bookings', 'icon' => 'bi-geo-alt-fill', 'url' => '/venues'],
        ['key' => 'events', 'label' => 'Events', 'icon' => 'bi-calendar-event', 'url' => '/operations/events'],
        ['key' => 'transport', 'label' => 'Transport', 'icon' => 'bi-truck', 'url' => '/operations/transport'],
        ['key' => 'accommodation', 'label' => 'Accommodation', 'icon' => 'bi-building-fill-add', 'url' => '/operations/accommodation'],
        ['key' => 'maintenance', 'label' => 'Maintenance', 'icon' => 'bi-tools', 'url' => '/operations/maintenance'],
        ['key' => 'tournaments', 'label' => 'Tournaments', 'icon' => 'bi-trophy-fill', 'url' => '/tournaments'],
        ['key' => 'reports', 'label' => 'Reports', 'icon' => 'bi-bar-chart-fill', 'url' => '/reports'],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'bi-gear-fill', 'url' => '/settings/organization'],
    ],
    'inventory_manager' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-house-door-fill', 'url' => '/dashboard'],
        ['key' => 'inventory', 'label' => 'Stock Inventory', 'icon' => 'bi-box-seam-fill', 'url' => '/inventory'],
        ['key' => 'equipment', 'label' => 'Equipment Tracking', 'icon' => 'bi-tag-fill', 'url' => '/equipment'],
        ['key' => 'vendors', 'label' => 'Vendors & Suppliers', 'icon' => 'bi-truck', 'url' => '/vendors'],
        ['key' => 'purchases', 'label' => 'Purchases & Orders', 'icon' => 'bi-cart-check-fill', 'url' => '/purchases'],
        ['key' => 'reports', 'label' => 'Reports', 'icon' => 'bi-bar-chart-fill', 'url' => '/reports'],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'bi-gear-fill', 'url' => '/settings/organization'],
    ],
    'super_admin' => [
        ['key' => 'dashboard', 'label' => 'Platform Dashboard', 'icon' => 'bi-speedometer2', 'url' => '/dashboard'],
        ['key' => 'organizations', 'label' => 'Organisations', 'icon' => 'bi-building-fill', 'url' => '/super-admin/organizations'],
        ['key' => 'users', 'label' => 'Platform Users', 'icon' => 'bi-people-fill', 'url' => '/users'],
        ['key' => 'roles', 'label' => 'Roles & RBAC', 'icon' => 'bi-shield-lock-fill', 'url' => '/roles'],
        ['key' => 'sports', 'label' => 'Sports Catalog', 'icon' => 'bi-trophy-fill', 'url' => '/tournaments'],
        ['key' => 'finance', 'label' => 'Finance & Accounting', 'icon' => 'bi-wallet-fill', 'url' => '/finance'],
        ['key' => 'audit', 'label' => 'Audit Trail', 'icon' => 'bi-journal-check', 'url' => '/audit-logs'],
        ['key' => 'reports', 'label' => 'Global Analytics', 'icon' => 'bi-graph-up', 'url' => '/reports'],
        ['key' => 'settings', 'label' => 'Global Settings', 'icon' => 'bi-sliders', 'url' => '/settings/organization'],
    ],
];

$menuItems = $navMenus[$currentRole] ?? $navMenus['sports_admin'];

// Grouped navigation structure for Sports Administrator role only
$sportsAdminStructure = [
    'dashboard' => ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-house-door-fill', 'url' => '/dashboard'],
    'groups' => [
        [
            'key' => 'sports',
            'label' => 'SPORTS',
            'icon' => 'bi-trophy-fill',
            'children' => [
                ['key' => 'athletes', 'label' => 'Athletes', 'icon' => 'bi-person-walking', 'url' => '/athletes'],
                ['key' => 'coaches', 'label' => 'Coaches', 'icon' => 'bi-person-badge', 'url' => '/coaches'],
                ['key' => 'teams', 'label' => 'Teams', 'icon' => 'bi-people-fill', 'url' => '/teams'],
                ['key' => 'tournaments', 'label' => 'Tournaments', 'icon' => 'bi-trophy-fill', 'url' => '/tournaments'],
                ['key' => 'training', 'label' => 'Training', 'icon' => 'bi-stopwatch-fill', 'url' => '/training'],
            ]
        ],
        [
            'key' => 'facilities_operations',
            'label' => 'FACILITIES & OPERATIONS',
            'icon' => 'bi-geo-alt-fill',
            'children' => [
                ['key' => 'venues', 'label' => 'Venues & Bookings', 'icon' => 'bi-geo-alt-fill', 'url' => '/venues'],
                ['key' => 'maintenance', 'label' => 'Maintenance', 'icon' => 'bi-tools', 'url' => '/operations/maintenance'],
                ['key' => 'events', 'label' => 'Events', 'icon' => 'bi-calendar-event', 'url' => '/operations/events'],
                ['key' => 'transport', 'label' => 'Transport', 'icon' => 'bi-truck', 'url' => '/operations/transport'],
                ['key' => 'accommodation', 'label' => 'Accommodation', 'icon' => 'bi-building-fill-add', 'url' => '/operations/accommodation'],
            ]
        ],
        [
            'key' => 'inventory_procurement',
            'label' => 'INVENTORY',
            'icon' => 'bi-box-seam-fill',
            'children' => [
                ['key' => 'inventory', 'label' => 'Inventory', 'icon' => 'bi-box-seam-fill', 'url' => '/inventory'],
                ['key' => 'equipment', 'label' => 'Equipment', 'icon' => 'bi-tag-fill', 'url' => '/equipment'],
                ['key' => 'vendors', 'label' => 'Vendors & Suppliers', 'icon' => 'bi-truck', 'url' => '/vendors'],
                ['key' => 'purchases', 'label' => 'Purchases & Orders', 'icon' => 'bi-cart-check-fill', 'url' => '/purchases'],
            ]
        ],
        [
            'key' => 'people_hr',
            'label' => 'PEOPLE & HR',
            'icon' => 'bi-people-fill',
            'children' => [
                ['key' => 'hr-finance', 'label' => 'Staff & HR', 'icon' => 'bi-briefcase-fill', 'url' => '/hr/employees'],
                ['key' => 'leave', 'label' => 'Leave Requests', 'icon' => 'bi-calendar-check', 'url' => '/leave'],
                ['key' => 'payroll', 'label' => 'Payroll', 'icon' => 'bi-cash-coin', 'url' => '/payroll'],
            ]
        ],
    ],
    'finance' => ['key' => 'finance', 'label' => 'Finance & Accounting', 'icon' => 'bi-wallet-fill', 'url' => '/finance'],
    'reports' => ['key' => 'reports', 'label' => 'Reports', 'icon' => 'bi-bar-chart-fill', 'url' => '/reports'],
    'settings' => ['key' => 'settings', 'label' => 'Settings', 'icon' => 'bi-gear-fill', 'url' => '/settings/organization'],
];

$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';

$isItemActive = function($item) use ($activePage, $currentUri) {
    if ($activePage === $item['key']) return true;
    $u = $item['url'];
    if ($u === '/dashboard') {
        return $currentUri === '/' || $currentUri === '/dashboard';
    }
    if ($item['key'] === 'venues' && (str_starts_with($currentUri, '/venues') || str_starts_with($currentUri, '/operations/venues') || str_starts_with($currentUri, '/operations/facilities'))) return true;
    if ($item['key'] === 'training' && (str_starts_with($currentUri, '/training') || str_starts_with($currentUri, '/attendance/training'))) return true;
    if ($item['key'] === 'users' && (str_starts_with($currentUri, '/users') || str_starts_with($currentUri, '/roles') || str_starts_with($currentUri, '/permissions'))) return true;
    if ($item['key'] === 'hr-finance' && str_starts_with($currentUri, '/hr')) return true;
    if ($item['key'] === 'leave' && str_starts_with($currentUri, '/leave')) return true;
    if ($item['key'] === 'payroll' && str_starts_with($currentUri, '/payroll')) return true;
    if ($item['key'] === 'athletes' && str_starts_with($currentUri, '/athletes')) return true;
    if ($item['key'] === 'coaches' && str_starts_with($currentUri, '/coaches')) return true;
    if ($item['key'] === 'teams' && str_starts_with($currentUri, '/teams')) return true;
    if ($item['key'] === 'tournaments' && str_starts_with($currentUri, '/tournaments')) return true;
    if ($item['key'] === 'maintenance' && str_starts_with($currentUri, '/operations/maintenance')) return true;
    if ($item['key'] === 'events' && (str_starts_with($currentUri, '/operations/events') || str_starts_with($currentUri, '/operations/school-activities'))) return true;
    if ($item['key'] === 'transport' && str_starts_with($currentUri, '/operations/transport')) return true;
    if ($item['key'] === 'accommodation' && str_starts_with($currentUri, '/operations/accommodation')) return true;
    if ($item['key'] === 'inventory' && str_starts_with($currentUri, '/inventory')) return true;
    if ($item['key'] === 'equipment' && str_starts_with($currentUri, '/equipment')) return true;
    if ($item['key'] === 'vendors' && str_starts_with($currentUri, '/vendors')) return true;
    if ($item['key'] === 'purchases' && str_starts_with($currentUri, '/purchases')) return true;
    if ($item['key'] === 'finance' && str_starts_with($currentUri, '/finance')) return true;
    if ($item['key'] === 'reports' && str_starts_with($currentUri, '/reports')) return true;
    if ($item['key'] === 'settings' && str_starts_with($currentUri, '/settings')) return true;
    return $u !== '/' && str_starts_with($currentUri, $u);
};
?>

<aside class="ks-sidebar<?= $currentRole === 'sports_admin' ? ' ks-sidebar-sports-admin' : '' ?>" id="ksSidebar">
    <!-- Brand Logo Area -->
    <div class="ks-sidebar-brand">
        <div class="ks-logo-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94.63 1.5 1.98 2.63 3.61 2.96V19H7v2h10v-2h-4v-3.1c1.63-.33 2.98-1.46 3.61-2.96C19.08 12.63 21 10.55 21 8V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/>
            </svg>
        </div>
        <div>
            <div class="ks-brand-title">KhelSutra</div>
            <div class="ks-brand-subtitle">Sports Academy</div>
        </div>
    </div>

    <!-- Main Navigation Items -->
    <ul class="ks-sidebar-nav">
        <?php if ($currentRole === 'sports_admin'): ?>
            <?php
                // Top-Level Dashboard
                $dashboardItem = $sportsAdminStructure['dashboard'];
                $isDashActive = $isItemActive($dashboardItem);
            ?>
            <li class="ks-nav-top-item">
                <a href="<?= htmlspecialchars($dashboardItem['url']) ?>" class="ks-nav-link ks-nav-link-dashboard <?= $isDashActive ? 'active' : '' ?>">
                    <i class="bi <?= $dashboardItem['icon'] ?>"></i>
                    <span><?= htmlspecialchars($dashboardItem['label']) ?></span>
                </a>
            </li>

            <?php foreach ($sportsAdminStructure['groups'] as $group): ?>
                <?php
                    $groupActive = false;
                    foreach ($group['children'] as $child) {
                        if ($isItemActive($child)) {
                            $groupActive = true;
                            break;
                        }
                    }
                ?>
                <li class="ks-nav-group <?= $groupActive ? 'expanded has-active' : '' ?>" data-group-key="<?= htmlspecialchars($group['key']) ?>">
                    <button type="button" class="ks-nav-group-header" aria-expanded="<?= $groupActive ? 'true' : 'false' ?>">
                        <span class="ks-group-title"><?= htmlspecialchars($group['label']) ?></span>
                        <i class="bi bi-chevron-down ks-group-chevron"></i>
                    </button>
                    <ul class="ks-nav-group-items">
                        <?php foreach ($group['children'] as $child): ?>
                            <?php $isChildAct = $isItemActive($child); ?>
                            <li>
                                <a href="<?= htmlspecialchars($child['url']) ?>" class="ks-nav-sublink <?= $isChildAct ? 'active' : '' ?>">
                                    <i class="bi <?= $child['icon'] ?>"></i>
                                    <span><?= htmlspecialchars($child['label']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>

            <?php
                // Top-Level Independent Links: Finance & Accounting, Reports, Settings
                $independentItems = [
                    $sportsAdminStructure['finance'],
                    $sportsAdminStructure['reports'],
                    $sportsAdminStructure['settings'],
                ];
            ?>
            <?php foreach ($independentItems as $idx => $indItem): ?>
                <?php $isIndActive = $isItemActive($indItem); ?>
                <li class="ks-nav-independent-item<?= $idx === 0 ? ' ks-nav-independent-first' : '' ?>">
                    <a href="<?= htmlspecialchars($indItem['url']) ?>" class="ks-nav-link <?= $isIndActive ? 'active' : '' ?>">
                        <i class="bi <?= $indItem['icon'] ?>"></i>
                        <span><?= htmlspecialchars($indItem['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Anchored Sidebar Footer (Branding Image FIRST, Sign Out SECOND) for Sports Admin -->
        <div class="ks-sidebar-footer">
            <div class="ks-sidebar-footer-signout">
                <a href="/logout" class="ks-nav-link ks-nav-signout-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </div>

        <?php else: ?>
            <?php // For all other 6 roles: render the original flat navigation structure unchanged ?>
            <?php foreach ($menuItems as $item): ?>
                <?php 
                    $isActive = ($activePage === $item['key']); 
                    $url = $item['url'];
                ?>
                <li>
                    <a href="<?= htmlspecialchars($url) ?>" class="ks-nav-link <?= $isActive ? 'active' : '' ?>">
                        <i class="bi <?= $item['icon'] ?>"></i>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>

            <!-- Sign Out Action -->
            <li style="margin-top: 6px; padding-top: 6px; border-top: 1px solid rgba(255,255,255,0.06);">
                <a href="/logout" class="ks-nav-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Sign Out</span>
                </a>
            </li>
        </ul>

        <!-- Bottom Decorative Section with Master Sports Image -->
        <div class="ks-sidebar-decorative">
            <img src="/assets/images/khelsutra-sidebar-athletes.png" 
                 alt="KhelSutra Athletes: PLAY • TRAIN • GROW" 
                 class="ks-sidebar-athletes-img" 
                 loading="lazy">
        </div>
        <?php endif; ?>
</aside>

<!-- Mobile Overlay -->
<div class="ks-sidebar-overlay" id="ksSidebarOverlay"></div>

<?php if ($currentRole === 'sports_admin'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var groupHeaders = document.querySelectorAll('.ks-nav-group-header');
    groupHeaders.forEach(function(header) {
        header.addEventListener('click', function(e) {
            e.preventDefault();
            var group = this.closest('.ks-nav-group');
            if (group) {
                var isExpanded = group.classList.toggle('expanded');
                this.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            }
            if (e.detail > 0) {
                this.blur();
            }
        });
    });
});
</script>
<?php endif; ?>
