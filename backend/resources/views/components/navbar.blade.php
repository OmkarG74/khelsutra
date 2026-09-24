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
            
            <button class="ks-notification-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications" id="ksNotifBtn">
                <i class="bi bi-bell fs-5"></i>
                <span class="ks-notification-badge" id="ksNotifBadge" style="display:none;">0</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="width: 320px; border-radius: 12px; font-size: 13px; padding: 12px 0;" id="ksNotifDropdown">
                <li class="px-3 pb-2 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Notifications</span>
                    <span class="badge bg-primary-subtle text-primary" id="ksNotifHeaderBadge">0 New</span>
                </li>
                <div id="ksNotifList">
                    <li class="p-3 text-center text-muted small">Loading...</li>
                </div>
                <li class="pt-2 border-top text-center">
                    <button class="btn btn-sm btn-link text-decoration-none small text-primary fw-semibold" onclick="ksMarkAllRead()">Mark all as read</button>
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

<script>
let notifTimer;
function ksPollNotifications() {
    fetch('/api/v1/notifications')
    .then(r => r.json())
    .then(d => {
        if(!d.success) return;
        let unread = d.data.filter(x => !x.is_read);
        let list = document.getElementById('ksNotifList');
        document.getElementById('ksNotifBadge').style.display = unread.length > 0 ? 'flex' : 'none';
        document.getElementById('ksNotifBadge').innerText = unread.length;
        document.getElementById('ksNotifHeaderBadge').innerText = unread.length + ' New';
        
        if (d.data.length === 0) {
            list.innerHTML = '<li class="p-3 text-center text-muted small">No notifications</li>';
            return;
        }
        
        list.innerHTML = d.data.slice(0, 5).map(n => `
            <li>
                <a class="dropdown-item py-2 d-flex gap-2 ${n.is_read ? 'opacity-75' : 'bg-light'}" href="#" onclick="ksMarkRead(${n.id})">
                    <i class="bi ${n.is_read ? 'bi-bell' : 'bi-bell-fill'} text-primary mt-1"></i>
                    <div>
                        <div class="fw-semibold">${n.title}</div>
                        <div class="text-muted small">${n.message}</div>
                    </div>
                </a>
            </li>
        `).join('');
    }).catch(e => console.error(e));
}

function ksMarkRead(id) {
    fetch('/api/v1/notifications/' + id + '/read', {
        method: 'PATCH',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'}
    }).then(() => ksPollNotifications());
}

function ksMarkAllRead() {
    // Demo implementation
    ksPollNotifications(); 
}

document.addEventListener('DOMContentLoaded', () => {
    ksPollNotifications();
    notifTimer = setInterval(ksPollNotifications, 30000); // 30s polling
});
</script>
