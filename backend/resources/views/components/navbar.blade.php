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
    $navUserId = (int)($auth['user']['id'] ?? 1);
    $navOrgId = (int)($auth['organization']['id'] ?? 1);
} else {
    // Default Sports Administrator context
    $userName = 'Rajesh Sharma';
    $userRole = 'Sports Administrator';
    $initials = 'RS';
    $isSuperAdmin = false;
    $orgBadgeText = 'Apex Sports Academy (ORG-DEMO)';
    $navUserId = 1;
    $navOrgId = 1;
}

if (!function_exists('ksFormatTimeAgo')) {
    function ksFormatTimeAgo($datetime) {
        $time = strtotime($datetime);
        if (!$time) return 'Just now';
        $diff = time() - $time;
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) {
            $m = floor($diff / 60);
            return $m . ' min' . ($m > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 86400) {
            $h = floor($diff / 3600);
            return $h . ' hour' . ($h > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 172800) return 'Yesterday';
        if ($diff < 604800) {
            $d = floor($diff / 86400);
            return $d . ' days ago';
        }
        return date('d M', $time);
    }
}

$navNotifService = new \App\Services\Notification\NotificationService();
$navUnreadCount = 0;
$navRecentNotifs = ['data' => [], 'total' => 0];
try {
    $navUnreadCount = $navNotifService->getUnreadCount($navOrgId, $navUserId);
    $navRecentNotifs = $navNotifService->getUserNotifications($navOrgId, $navUserId, 1, 15);
    if (empty($navRecentNotifs['data']) && $isSuperAdmin) {
        $stmt = \App\Services\BaseService::getDatabaseConnection()->prepare("
            SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC, id DESC LIMIT 15
        ");
        $stmt->execute([':user_id' => $navUserId]);
        $globalData = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!empty($globalData)) {
            $navRecentNotifs['data'] = $globalData;
            $navRecentNotifs['total'] = count($globalData);
            $unreadStmt = \App\Services\BaseService::getDatabaseConnection()->prepare("
                SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0
            ");
            $unreadStmt->execute([':user_id' => $navUserId]);
            $navUnreadCount = (int)$unreadStmt->fetchColumn();
        }
    }
} catch (\Throwable $e) {}
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

    <!-- Global Header Search (Platform Search for Super Admin & Directory) -->
    <div class="ks-global-search m-0 position-relative" id="ksGlobalSearchContainer">
        <form action="/search" method="GET" class="m-0" id="ksGlobalSearchForm" autocomplete="off">
            <i class="bi bi-search ks-search-input-icon" id="ksSearchIcon"></i>
            <div class="spinner-border spinner-border-sm text-primary ks-search-spinner d-none" id="ksSearchSpinner" role="status" style="width: 14px; height: 14px; position: absolute; right: 14px; top: 13px; z-index: 3;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <input type="text" name="q" id="ksSearchInput" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="ks-search-input" placeholder="Search organisations, users, sections..." aria-label="Global Platform Search" autocomplete="off" spellcheck="false">
        </form>

        <!-- Live Search Dropdown -->
        <div class="ks-search-dropdown shadow" id="ksSearchDropdown" style="display: none;">
            <div class="ks-search-dropdown-header d-flex justify-content-between align-items-center">
                <span class="ks-search-header-title" id="ksSearchHeaderTitle">PLATFORM SECTIONS</span>
                <span class="ks-search-esc-hint">ESC to close</span>
            </div>
            <div class="ks-search-results-list" id="ksSearchResultsList">
                <!-- Populated dynamically via JS -->
            </div>
            <div class="ks-search-dropdown-footer d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-1">
                    <kbd class="ks-search-kbd">↑</kbd>
                    <kbd class="ks-search-kbd">↓</kbd>
                    <span class="ms-1">Navigate</span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <kbd class="ks-search-kbd">Enter</kbd>
                    <span class="ms-1">Open</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Right -->
    <div class="ks-header-right">
        <!-- Notification Bell (Section 12) & Right-Side Panel -->
        <div class="ks-notification-wrapper position-relative">
            <button class="ks-notification-btn" type="button" aria-label="Notifications" id="ksNavNotifBtn" onclick="ksToggleNotificationPanel(event)">
                <i class="bi bi-bell fs-5"></i>
                <span class="ks-notification-badge <?= $navUnreadCount === 0 ? 'd-none' : '' ?>" id="ks-nav-notif-badge">
                    <?= $navUnreadCount > 99 ? '99+' : $navUnreadCount ?>
                </span>
            </button>

            <!-- Right-Side Vertical Notification Panel -->
            <div class="ks-notification-panel" id="ksNotificationPanel" style="display: none;">
                <!-- Header -->
                <div class="ks-notif-panel-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-navy" style="font-size: 14px;">Notifications</span>
                        <span class="badge <?= $navUnreadCount > 0 ? 'bg-primary-subtle text-primary' : 'bg-light text-muted' ?>" id="ks-nav-notif-count" style="font-size: 11px;">
                            <?= $navUnreadCount ?> New
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-muted <?= $navUnreadCount === 0 ? 'd-none' : '' ?>" style="font-size: 11px;" id="ks-nav-mark-all-btn" onclick="ksNavbarMarkAllRead(event)">
                            Mark all read
                        </button>
                        <button type="button" class="btn-close ms-1" style="font-size: 10px;" aria-label="Close" onclick="ksCloseNotificationPanel(event)"></button>
                    </div>
                </div>

                <!-- Body / Notification List -->
                <div class="ks-notif-panel-body" id="ksNotifPanelBody">
                    <?php if (empty($navRecentNotifs['data'])): ?>
                        <div class="px-3 py-5 text-center text-muted" id="ks-nav-notif-empty">
                            <i class="bi bi-bell-slash d-block fs-3 mb-2 text-secondary opacity-50"></i>
                            <div class="small fw-semibold text-dark">No new notifications</div>
                            <div style="font-size: 11.5px;" class="text-muted mt-1">You're all caught up!</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($navRecentNotifs['data'] as $notifItem): ?>
                            <?php
                            $isUnread = empty($notifItem['is_read']);
                            $nType = $notifItem['notification_type'] ?? '';
                            $nIcon = 'bi-bell';
                            $nColor = 'text-primary';
                            if ($nType === 'low_stock') {
                                $nIcon = 'bi-box-seam';
                                $nColor = 'text-danger';
                            } elseif ($nType === 'equipment_overdue') {
                                $nIcon = 'bi-clock-history';
                                $nColor = 'text-warning';
                            } elseif ($nType === 'budget_alert') {
                                $nIcon = 'bi-cash-stack';
                                $nColor = 'text-danger';
                            }
                            ?>
                            <div class="ks-notif-panel-item <?= $isUnread ? 'unread' : '' ?>" id="ks-nav-notif-<?= $notifItem['id'] ?>" onclick="ksNavbarMarkRead(event, <?= $notifItem['id'] ?>)">
                                <span class="ks-notif-dot <?= $isUnread ? '' : 'read' ?>"></span>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between align-items-baseline gap-1 mb-1">
                                        <span class="fw-semibold text-truncate text-navy" style="font-size: 13px;">
                                            <i class="bi <?= $nIcon ?> <?= $nColor ?> me-1" style="font-size: 12px;"></i>
                                            <?= htmlspecialchars($notifItem['title']) ?>
                                        </span>
                                        <span class="text-muted text-nowrap ms-2" style="font-size: 11px;">
                                            <?= ksFormatTimeAgo($notifItem['created_at']) ?>
                                        </span>
                                    </div>
                                    <div class="text-muted small" style="font-size: 12px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?= htmlspecialchars($notifItem['message']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Profile (Section 13) -->
        <div class="dropdown">
            <div class="ks-user-widget dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="ks-avatar-circle"><?= htmlspecialchars($initials) ?></div>
                <div class="d-none d-sm-block text-start">
                    <div class="ks-user-name"><?= htmlspecialchars($userName) ?></div>
                    <div class="ks-user-role"><?= htmlspecialchars($userRole) ?></div>
                </div>
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

<script>
function ksToggleNotificationPanel(event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    const panel = document.getElementById('ksNotificationPanel');
    if (!panel) return;
    const isShowing = (panel.style.display !== 'none');
    if (isShowing) {
        panel.style.display = 'none';
        panel.classList.remove('show');
    } else {
        panel.style.display = 'flex';
        panel.classList.add('show');
    }
}

function ksCloseNotificationPanel(event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    const panel = document.getElementById('ksNotificationPanel');
    if (panel) {
        panel.style.display = 'none';
        panel.classList.remove('show');
    }
}

document.addEventListener('click', function(event) {
    const panel = document.getElementById('ksNotificationPanel');
    const btn = document.getElementById('ksNavNotifBtn');
    if (!panel || panel.style.display === 'none') return;
    if (!panel.contains(event.target) && !btn.contains(event.target)) {
        panel.style.display = 'none';
        panel.classList.remove('show');
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const panel = document.getElementById('ksNotificationPanel');
        if (panel && panel.style.display !== 'none') {
            panel.style.display = 'none';
            panel.classList.remove('show');
        }
    }
});

function ksNavbarMarkRead(event, notifId) {
    if (event) {
        event.stopPropagation();
    }
    const item = document.getElementById('ks-nav-notif-' + notifId);
    if (!item) return;

    const wasUnread = item.classList.contains('unread');
    if (wasUnread) {
        item.classList.remove('unread');
        const dot = item.querySelector('.ks-notif-dot');
        if (dot) dot.classList.add('read');

        const badge = document.getElementById('ks-nav-notif-badge');
        const countText = document.getElementById('ks-nav-notif-count');
        const markAllBtn = document.getElementById('ks-nav-mark-all-btn');

        let current = parseInt(badge?.innerText || '0', 10);
        let newCount = Math.max(0, current - 1);

        if (badge) {
            badge.innerText = newCount > 99 ? '99+' : newCount;
            if (newCount === 0) badge.classList.add('d-none');
        }
        if (countText) {
            countText.innerText = newCount + ' New';
            if (newCount === 0) countText.className = 'badge bg-light text-muted';
        }
        if (newCount === 0 && markAllBtn) {
            markAllBtn.classList.add('d-none');
        }
    }

    fetch('/notifications/read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ notification_id: notifId })
    })
    .then(res => res.json())
    .then(data => {
        if (data && typeof data.unread_count !== 'undefined') {
            const unread = data.unread_count;
            const badge = document.getElementById('ks-nav-notif-badge');
            const countText = document.getElementById('ks-nav-notif-count');
            const markAllBtn = document.getElementById('ks-nav-mark-all-btn');
            if (badge) {
                badge.innerText = unread > 99 ? '99+' : unread;
                if (unread === 0) badge.classList.add('d-none');
                else badge.classList.remove('d-none');
            }
            if (countText) {
                countText.innerText = unread + ' New';
                if (unread === 0) countText.className = 'badge bg-light text-muted';
                else countText.className = 'badge bg-primary-subtle text-primary';
            }
            if (markAllBtn) {
                if (unread === 0) markAllBtn.classList.add('d-none');
                else markAllBtn.classList.remove('d-none');
            }
        }
    })
    .catch(() => {});
}

function ksNavbarMarkAllRead(event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    fetch('/notifications/read-all', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(() => {
        const badge = document.getElementById('ks-nav-notif-badge');
        const countText = document.getElementById('ks-nav-notif-count');
        const markAllBtn = document.getElementById('ks-nav-mark-all-btn');
        if (badge) badge.classList.add('d-none');
        if (countText) {
            countText.innerText = '0 New';
            countText.className = 'badge bg-light text-muted';
        }
        if (markAllBtn) markAllBtn.classList.add('d-none');
        document.querySelectorAll('#ksNotifPanelBody .ks-notif-panel-item').forEach(el => {
            el.classList.remove('unread');
            const dot = el.querySelector('.ks-notif-dot');
            if (dot) dot.classList.add('read');
        });
    })
    .catch(() => {});
}

// ========================================================
// GLOBAL HEADER SEARCH (Super Admin Platform Search)
// ========================================================
(function() {
    const searchContainer = document.getElementById('ksGlobalSearchContainer');
    const searchInput = document.getElementById('ksSearchInput');
    const searchForm = document.getElementById('ksGlobalSearchForm');
    const searchDropdown = document.getElementById('ksSearchDropdown');
    const searchSpinner = document.getElementById('ksSearchSpinner');
    const searchIcon = document.getElementById('ksSearchIcon');
    const resultsList = document.getElementById('ksSearchResultsList');
    const headerTitle = document.getElementById('ksSearchHeaderTitle');

    if (!searchInput || !searchDropdown || !resultsList) return;

    let debounceTimer = null;
    let currentSelectedIndex = -1;
    let currentResults = [];
    const isSuperAdmin = <?= $isSuperAdmin ? 'true' : 'false' ?>;

    const defaultSections = [
        {
            title: 'Dashboard',
            subtitle: 'Executive overview & statistics',
            url: '/dashboard',
            icon: 'bi-speedometer2',
            badge: 'Section'
        },
        ...(isSuperAdmin ? [{
            title: 'Organisations',
            subtitle: 'Sports academies & organisations directory',
            url: '/super-admin/organizations',
            icon: 'bi-building',
            badge: 'Super Admin',
            badgeClass: 'admin'
        }] : []),
        {
            title: 'Users',
            subtitle: 'User accounts & directory',
            url: '/users',
            icon: 'bi-people',
            badge: 'Directory'
        },
        {
            title: 'Audit Logs',
            subtitle: 'Platform security & operational activity',
            url: '/audit',
            icon: 'bi-shield-check',
            badge: 'Security'
        },
        {
            title: 'Account Settings',
            subtitle: 'Personal profile settings & security',
            url: '/settings',
            icon: 'bi-person-circle',
            badge: 'Settings'
        }
    ];

    function showDropdown() {
        searchDropdown.style.display = 'block';
    }

    function hideDropdown() {
        searchDropdown.style.display = 'none';
        currentSelectedIndex = -1;
    }

    function renderDefaultSections() {
        if (headerTitle) headerTitle.textContent = 'PLATFORM SECTIONS';
        currentResults = [];
        let html = '<div class="ks-search-section-label">SECTIONS</div>';
        
        defaultSections.forEach((sec, idx) => {
            currentResults.push(sec);
            const badgeClass = sec.badgeClass ? `ks-search-badge ${sec.badgeClass}` : 'ks-search-badge';
            html += `
                <a href="${sec.url}" class="ks-search-item" data-index="${idx}" data-url="${sec.url}">
                    <div class="ks-search-item-icon">
                        <i class="bi ${sec.icon}"></i>
                    </div>
                    <div class="ks-search-item-content">
                        <div class="ks-search-item-title">${escapeHtml(sec.title)}</div>
                        <div class="ks-search-item-subtitle">${escapeHtml(sec.subtitle)}</div>
                    </div>
                    <span class="${badgeClass}">${escapeHtml(sec.badge)}</span>
                </a>
            `;
        });

        resultsList.innerHTML = html;
        currentSelectedIndex = -1;
        showDropdown();
    }

    function renderSearchResults(data, query) {
        if (headerTitle) headerTitle.textContent = 'SEARCH RESULTS';
        currentResults = [];
        let html = '';
        let totalCount = 0;

        const orgs = data.organizations || [];
        const users = data.users || [];
        const sections = data.sections || [];

        // 1. ORGANISATIONS GROUP
        if (orgs.length > 0) {
            html += '<div class="ks-search-section-label">ORGANISATIONS</div>';
            orgs.forEach(org => {
                const idx = currentResults.length;
                currentResults.push(org);
                const isAct = (org.status === 'active');
                const badgeClass = isAct ? 'ks-search-badge active-status' : 'ks-search-badge suspended-status';
                const badgeText = isAct ? 'Active' : (org.badge || 'Suspended');

                html += `
                    <a href="${org.url}" class="ks-search-item" data-index="${idx}" data-url="${org.url}">
                        <div class="ks-search-item-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="ks-search-item-content">
                            <div class="ks-search-item-title">${escapeHtml(org.title)}</div>
                            <div class="ks-search-item-subtitle">${escapeHtml(org.subtitle || org.email || '')}</div>
                        </div>
                        <span class="${badgeClass}">${escapeHtml(badgeText)}</span>
                    </a>
                `;
            });
            totalCount += orgs.length;
        }

        // 2. USERS GROUP
        if (users.length > 0) {
            html += '<div class="ks-search-section-label">USERS</div>';
            users.forEach(user => {
                const idx = currentResults.length;
                currentResults.push(user);
                html += `
                    <a href="${user.url}" class="ks-search-item" data-index="${idx}" data-url="${user.url}">
                        <div class="ks-search-item-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="ks-search-item-content">
                            <div class="ks-search-item-title">${escapeHtml(user.title)}</div>
                            <div class="ks-search-item-subtitle">${escapeHtml(user.subtitle || user.email || '')}</div>
                        </div>
                        <span class="ks-search-badge">${escapeHtml(user.badge || 'User')}</span>
                    </a>
                `;
            });
            totalCount += users.length;
        }

        // 3. SECTIONS GROUP
        if (sections.length > 0) {
            html += '<div class="ks-search-section-label">SECTIONS</div>';
            sections.forEach(sec => {
                const idx = currentResults.length;
                currentResults.push(sec);
                let iconClass = sec.icon || 'bi-speedometer2';
                if (sec.title === 'Dashboard') iconClass = 'bi-speedometer2';
                else if (sec.title === 'Organisations') iconClass = 'bi-building';
                else if (sec.title === 'Users') iconClass = 'bi-people';
                else if (sec.title === 'Audit Logs') iconClass = 'bi-shield-check';
                else if (sec.title === 'Account Settings') iconClass = 'bi-person-circle';

                html += `
                    <a href="${sec.url}" class="ks-search-item" data-index="${idx}" data-url="${sec.url}">
                        <div class="ks-search-item-icon">
                            <i class="bi ${iconClass}"></i>
                        </div>
                        <div class="ks-search-item-content">
                            <div class="ks-search-item-title">${escapeHtml(sec.title)}</div>
                            <div class="ks-search-item-subtitle">${escapeHtml(sec.subtitle || '')}</div>
                        </div>
                        <span class="ks-search-badge">Section</span>
                    </a>
                `;
            });
            totalCount += sections.length;
        }

        // EMPTY STATE
        if (totalCount === 0) {
            html = `
                <div class="text-center py-4 px-3 text-muted">
                    <i class="bi bi-search d-block fs-3 mb-2 opacity-50" style="color: #64748B;"></i>
                    <div class="fw-semibold" style="font-size: 13.5px; color: #0F2747;">No results found</div>
                    <div class="small mt-1" style="font-size: 12px; color: #64748B;">No organisations, users, or sections matched "<strong>${escapeHtml(query)}</strong>"</div>
                </div>
            `;
        }

        resultsList.innerHTML = html;
        currentSelectedIndex = -1;
        showDropdown();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function highlightItem(index) {
        const items = resultsList.querySelectorAll('.ks-search-item');
        if (!items || items.length === 0) return;

        items.forEach(el => el.classList.remove('ks-search-item-active'));

        if (index < 0) {
            currentSelectedIndex = -1;
            return;
        }
        if (index >= items.length) {
            index = 0;
        }

        currentSelectedIndex = index;
        const target = items[currentSelectedIndex];
        if (target) {
            target.classList.add('ks-search-item-active');
            target.scrollIntoView({ block: 'nearest' });
        }
    }

    function doSearch(query) {
        const trimmed = query.trim();
        if (trimmed.length === 0) {
            if (searchSpinner) searchSpinner.classList.add('d-none');
            renderDefaultSections();
            return;
        }

        if (searchSpinner) searchSpinner.classList.remove('d-none');

        fetch(`/api/v1/search/global?q=${encodeURIComponent(trimmed)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (searchSpinner) searchSpinner.classList.add('d-none');
            const data = (res && res.data) ? res.data : { organizations: [], users: [], sections: [] };
            renderSearchResults(data, trimmed);
        })
        .catch(err => {
            if (searchSpinner) searchSpinner.classList.add('d-none');
            // If fetch fails, show fallback matching default sections
            const lower = trimmed.toLowerCase();
            const filteredSections = defaultSections.filter(s => 
                s.title.toLowerCase().includes(lower) || s.subtitle.toLowerCase().includes(lower)
            );
            renderSearchResults({ organizations: [], users: [], sections: filteredSections }, trimmed);
        });
    }

    // Input events
    searchInput.addEventListener('focus', function() {
        const query = searchInput.value.trim();
        if (query.length === 0) {
            renderDefaultSections();
        } else {
            doSearch(query);
        }
    });

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = searchInput.value;
        debounceTimer = setTimeout(() => {
            doSearch(query);
        }, 220);
    });

    // Keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        const isOpen = (searchDropdown.style.display !== 'none');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!isOpen) {
                renderDefaultSections();
                return;
            }
            const items = resultsList.querySelectorAll('.ks-search-item');
            if (items.length > 0) {
                const nextIdx = (currentSelectedIndex + 1) >= items.length ? 0 : (currentSelectedIndex + 1);
                highlightItem(nextIdx);
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (!isOpen) return;
            const items = resultsList.querySelectorAll('.ks-search-item');
            if (items.length > 0) {
                const prevIdx = (currentSelectedIndex - 1) < 0 ? (items.length - 1) : (currentSelectedIndex - 1);
                highlightItem(prevIdx);
            }
        } else if (e.key === 'Enter') {
            if (isOpen && currentSelectedIndex >= 0) {
                e.preventDefault();
                const items = resultsList.querySelectorAll('.ks-search-item');
                const selected = items[currentSelectedIndex];
                if (selected && selected.dataset.url) {
                    window.location.href = selected.dataset.url;
                    return;
                }
            } else if (isOpen && currentResults.length > 0) {
                // If enter pressed without active arrow selection, open the first top match
                e.preventDefault();
                const first = currentResults[0];
                if (first && first.url) {
                    window.location.href = first.url;
                    return;
                }
            }
            // Otherwise let form submit to /search?q=...
        } else if (e.key === 'Escape') {
            e.preventDefault();
            hideDropdown();
            searchInput.blur();
        }
    });

    // Click outside handler
    document.addEventListener('click', function(e) {
        if (!searchContainer.contains(e.target)) {
            hideDropdown();
        }
    });
})();
</script>

<style>
/* ========================================================
   GLOBAL HEADER SEARCH & COMMAND PALETTE STYLING
   ======================================================== */
.ks-global-search {
    position: relative;
    width: 100%;
    max-width: 460px;
}

/* Search input icon (Bootstrap Icons) */
.ks-global-search .ks-search-input-icon {
    position: absolute !important;
    left: 15px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    font-size: 17px !important;
    line-height: 1 !important;
    color: #64748B !important;
    pointer-events: none !important;
    z-index: 3 !important;
}

/* Search Input */
.ks-global-search .ks-search-input {
    width: 100%;
    height: 40px;
    background-color: #F8FAFD;
    border: 1px solid #DCE5F1;
    border-radius: 10px;
    padding: 0 38px 0 44px;
    font-size: 13.5px;
    font-family: inherit;
    color: #0F2747;
    outline: none;
    transition: all 0.15s ease;
}

.ks-global-search .ks-search-input:focus {
    background-color: #FFFFFF;
    border-color: #0B6EF3;
    box-shadow: 0 0 0 3px rgba(11, 110, 243, 0.12);
}

.ks-global-search .ks-search-input::placeholder {
    color: #8E9CAE;
    font-size: 13.5px;
}

/* Dropdown Container */
.ks-global-search .ks-search-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    width: 100%;
    min-width: 480px;
    max-width: 540px;
    background: #FFFFFF;
    border: 1px solid #DCE5F1;
    border-radius: 12px;
    box-shadow: 0 14px 32px -4px rgba(15, 39, 71, 0.15), 0 4px 12px rgba(0, 0, 0, 0.04);
    z-index: 1080;
    overflow: hidden;
}

/* Dropdown Top Header */
.ks-global-search .ks-search-dropdown-header {
    padding: 10px 18px;
    background: #FFFFFF;
    border-bottom: 1px solid #EDF2F7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ks-global-search .ks-search-header-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748B;
}

.ks-global-search .ks-search-esc-hint {
    font-size: 11px;
    color: #94A3B8;
    font-weight: 500;
}

/* Section Label */
.ks-global-search .ks-search-section-label {
    padding: 7px 18px 5px 18px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748B;
    background-color: #F8FAFD;
    border-bottom: 1px solid #EDF2F7;
    border-top: 1px solid #EDF2F7;
}

.ks-global-search .ks-search-section-label:first-child {
    border-top: none;
}

/* Results List */
.ks-global-search .ks-search-results-list {
    max-height: 420px;
    overflow-y: auto;
    background: #FFFFFF;
}

/* Result Row Item */
.ks-global-search .ks-search-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 9px 18px;
    text-decoration: none;
    color: inherit;
    border-left: 3px solid transparent;
    transition: background-color 0.15s ease, border-left-color 0.15s ease;
    cursor: pointer;
    background: #FFFFFF;
}

.ks-global-search .ks-search-item:hover,
.ks-global-search .ks-search-item.ks-search-item-active {
    background-color: #F1F7FF !important;
    border-left-color: #0B6EF3 !important;
    text-decoration: none;
}

/* Icon Container */
.ks-global-search .ks-search-item-icon {
    width: 38px;
    height: 38px;
    min-width: 38px;
    min-height: 38px;
    border-radius: 9px;
    background-color: #EDF3FB;
    color: #0F2747;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.ks-global-search .ks-search-item:hover .ks-search-item-icon,
.ks-global-search .ks-search-item.ks-search-item-active .ks-search-item-icon {
    background-color: #DFEDFF;
    color: #0B6EF3;
}

/* CRITICAL: Overriding any global .ks-global-search i style collision */
.ks-global-search .ks-search-dropdown i,
.ks-global-search .ks-search-item-icon i {
    position: static !important;
    top: auto !important;
    left: auto !important;
    transform: none !important;
    pointer-events: auto !important;
    font-size: 18px !important;
    line-height: 1 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

/* Content */
.ks-global-search .ks-search-item-content {
    flex-grow: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.ks-global-search .ks-search-item-title {
    font-size: 14.5px;
    font-weight: 600;
    color: #0F2747;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ks-global-search .ks-search-item-subtitle {
    font-size: 12.5px;
    color: #64748B;
    line-height: 1.35;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Right Badge */
.ks-global-search .ks-search-badge {
    height: 24px;
    padding: 0 9px;
    font-size: 11.5px;
    font-weight: 500;
    border-radius: 6px;
    background-color: #F1F5F9;
    color: #475569;
    border: 1px solid #E2E8F0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    white-space: nowrap;
    align-self: center;
}

.ks-global-search .ks-search-badge.admin {
    background-color: #EAF3FF;
    color: #0B6EF3;
    border-color: #D3E5FF;
}

.ks-global-search .ks-search-badge.active-status {
    background-color: #E8F8F0;
    color: #0E9F6E;
    border-color: #C3EEDA;
}

.ks-global-search .ks-search-badge.suspended-status {
    background-color: #FDF2F2;
    color: #E02424;
    border-color: #FBD5D5;
}

/* Dropdown Footer */
.ks-global-search .ks-search-dropdown-footer {
    padding: 8px 18px;
    background: #F8FAFD;
    border-top: 1px solid #EDF2F7;
    font-size: 11.5px;
    color: #64748B;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ks-global-search .ks-search-kbd {
    background-color: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 10.5px;
    font-family: inherit;
    color: #475569;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
    line-height: 1;
    display: inline-block;
}

@media (max-width: 992px) {
    .ks-global-search {
        max-width: 320px;
    }
    .ks-global-search .ks-search-dropdown {
        min-width: 360px;
    }
}

@media (max-width: 768px) {
    .ks-global-search .ks-search-dropdown {
        min-width: 310px;
        left: -30px;
        right: 0;
    }
}
</style>
