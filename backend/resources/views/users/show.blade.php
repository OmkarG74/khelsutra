<?php
$activePage = 'users';
$title = 'User Details — KhelSutra Platform';

$userId = $id ?? 1;
$userService = new \App\Services\User\UserManagementService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

try {
    $user = $userService->getUserDetails((int)$userId, (int)$orgId);
} catch (\Throwable $e) {
    $user = null;
}

if ($user) {
    $fInitial = !empty($user['first_name']) ? mb_substr(trim($user['first_name']), 0, 1) : '';
    $lInitial = !empty($user['last_name']) ? mb_substr(trim($user['last_name']), 0, 1) : '';
    $initials = strtoupper($fInitial . $lInitial);
    if (empty($initials)) {
        $initials = !empty($user['username']) ? strtoupper(mb_substr(trim($user['username']), 0, 2)) : 'U';
    }

    $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    if (empty($fullName)) {
        $fullName = $user['username'] ?? 'User Details';
    }

    $showRole = ((int)($user['role_id'] ?? 0) === 2 || ($user['role_name'] ?? '') === 'Sports Administrator' || ($user['role_name'] ?? '') === 'Organisation Admin') 
        ? 'Organisation Admin' 
        : ($user['role_name'] ?? 'No Role');

    $formattedLastLogin = 'Never';
    if (!empty($user['last_login_at'])) {
        $ts = strtotime($user['last_login_at']);
        $formattedLastLogin = $ts ? date('d M Y, h:i A', $ts) : htmlspecialchars($user['last_login_at']);
    }

    $formattedCreatedAt = '—';
    if (!empty($user['created_at'])) {
        $ts = strtotime($user['created_at']);
        $formattedCreatedAt = $ts ? date('d M Y, h:i A', $ts) : htmlspecialchars($user['created_at']);
    }
}

ob_start();
?>

<?php if (!$user): ?>
    <div class="ks-page-header d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="ks-page-title mb-0" style="font-size: 20px; font-weight: 700; color: var(--ks-navy);">User Details</h1>
        </div>
        <div class="ks-header-actions">
            <a href="/users" class="ks-btn ks-btn-secondary" style="height: 36px; padding: 0 14px; font-size: 13px; font-weight: 500;">
                <i class="bi bi-arrow-left me-1"></i>
                <span>Back to Users</span>
            </a>
        </div>
    </div>

    <div class="ks-card p-5 text-center text-muted" style="background: #FFFFFF; border: 1px solid var(--ks-border); border-radius: 12px;">
        <i class="bi bi-person-x fs-1 text-danger"></i>
        <h5 class="mt-3 text-navy fw-bold">User Not Found</h5>
        <p class="small text-muted mb-3">The requested user does not exist or has been removed.</p>
        <a href="/users" class="ks-btn ks-btn-secondary">Return to Users</a>
    </div>
<?php else: ?>
    <!-- Compact Page Header -->
    <div class="ks-page-header d-flex align-items-center justify-content-between mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <h1 class="ks-page-title mb-0" style="font-size: 20px; font-weight: 700; color: var(--ks-navy);">
                    <?= htmlspecialchars($fullName) ?>
                </h1>
                <?php if (!empty($user['username'])): ?>
                    <span class="text-muted small fw-medium" style="font-size: 13.5px;">@<?= htmlspecialchars($user['username']) ?></span>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-2 small text-muted" style="font-size: 13px;">
                <span class="fw-medium text-navy"><?= htmlspecialchars($showRole) ?></span>
                <span>·</span>
                <?php if (($user['status'] ?? '') === 'active'): ?>
                    <span class="text-success fw-semibold"><i class="bi bi-circle-fill" style="font-size: 7px; vertical-align: middle;"></i> Active</span>
                <?php else: ?>
                    <span class="text-danger fw-semibold"><i class="bi bi-circle-fill" style="font-size: 7px; vertical-align: middle;"></i> <?= htmlspecialchars(ucfirst($user['status'] ?? 'Inactive')) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="ks-header-actions">
            <a href="/users" class="ks-btn ks-btn-secondary" style="height: 36px; padding: 0 14px; font-size: 13px; font-weight: 500;">
                <i class="bi bi-arrow-left me-1"></i>
                <span>Back to Users</span>
            </a>
        </div>
    </div>

    <!-- Main Two-Column Layout -->
    <div class="row g-4 align-items-start">
        <!-- Left Column: User Profile Card (Read-Only) -->
        <div class="col-lg-4 col-xl-4">
            <div class="ks-card p-4" style="background: #FFFFFF; border: 1px solid var(--ks-border); border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
                <!-- Avatar & Identity Summary -->
                <div class="text-center pb-3 border-bottom" style="border-color: var(--ks-border-light) !important;">
                    <div class="ks-avatar mx-auto mb-3" style="width: 76px; height: 76px; border-radius: 50%; background: #0E1E3B; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 24px; letter-spacing: 0.5px; box-shadow: 0 2px 6px rgba(14, 30, 59, 0.15);">
                        <?= htmlspecialchars($initials) ?>
                    </div>
                    <h4 class="fw-bold text-navy mb-1" style="font-size: 17px;"><?= htmlspecialchars($fullName) ?></h4>
                    <div class="text-muted small mb-2" style="font-size: 13px;"><?= !empty($user['username']) ? '@' . htmlspecialchars($user['username']) : '—' ?></div>

                    <div class="d-flex align-items-center justify-content-center gap-2 mb-1 flex-wrap">
                        <span class="ks-badge ks-badge-blue" style="font-size: 11.5px; padding: 3px 9px; font-weight: 600;">
                            <?= htmlspecialchars($showRole) ?>
                        </span>
                        <?php if (($user['status'] ?? '') === 'active'): ?>
                            <span class="ks-badge ks-badge-confirmed" style="font-size: 11.5px; padding: 3px 9px;">Active</span>
                        <?php else: ?>
                            <span class="ks-badge ks-badge-rejected" style="font-size: 11.5px; padding: 3px 9px;"><?= htmlspecialchars(ucfirst($user['status'] ?? 'Inactive')) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profile Information Hierarchy -->
                <div class="pt-3 d-flex flex-column gap-3">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px; margin-bottom: 2px;">Email Address</div>
                        <div class="text-navy fw-semibold" style="font-size: 13.5px; word-break: break-all;"><?= htmlspecialchars($user['email'] ?? '—') ?></div>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px; margin-bottom: 2px;">Phone</div>
                        <div class="text-navy fw-semibold" style="font-size: 13.5px;"><?= htmlspecialchars($user['phone'] ?? '—') ?></div>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px; margin-bottom: 2px;">User UUID</div>
                        <div class="text-navy fw-semibold font-monospace small" style="font-size: 12px; word-break: break-all;"><?= htmlspecialchars($user['uuid'] ?? '—') ?></div>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px; margin-bottom: 2px;">Last Login</div>
                        <div class="text-navy fw-medium" style="font-size: 13px;"><?= $formattedLastLogin ?></div>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px; margin-bottom: 2px;">Created At</div>
                        <div class="text-navy fw-medium" style="font-size: 13px;"><?= $formattedCreatedAt ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Organisation Memberships Card (Read-Only) -->
        <div class="col-lg-8 col-xl-8">
            <div class="ks-card p-4" style="background: #FFFFFF; border: 1px solid var(--ks-border); border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
                <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom" style="border-color: var(--ks-border-light) !important;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-building text-primary" style="font-size: 16px;"></i>
                        <h3 class="fw-bold text-navy mb-0" style="font-size: 15px;">Organisation Memberships</h3>
                    </div>
                    <span class="badge" style="background: #F1F5F9; color: #475569; font-weight: 600; font-size: 11.5px; padding: 4px 8px; border-radius: 6px;">
                        <?= count($user['organizations'] ?? []) ?> <?= count($user['organizations'] ?? []) === 1 ? 'Organisation' : 'Organisations' ?>
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table mb-0 align-middle" style="font-size: 13.5px;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--ks-border); color: var(--ks-text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                                <th class="py-2 px-2" style="font-weight: 600; width: 45%;">Organisation</th>
                                <th class="py-2 px-2" style="font-weight: 600; width: 35%;">Role</th>
                                <th class="py-2 px-2 text-end" style="font-weight: 600; width: 20%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($user['organizations'])): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4 small">
                                        <i class="bi bi-building-x fs-4 d-block mb-1 text-muted"></i>
                                        No organisation memberships found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($user['organizations'] as $org): ?>
                                    <?php 
                                        $isThisOrgAdmin = ((int)($org['role_id'] ?? 0) === 2 || ($org['role_name'] ?? '') === 'Sports Administrator' || ($org['role_name'] ?? '') === 'Organisation Admin');
                                        $roleDisplay = $isThisOrgAdmin ? 'Organisation Admin' : ($org['role_name'] ?? 'Member');
                                        $isOrgActive = (($org['status'] ?? 'active') === 'active');
                                        $isDefault = !empty($org['is_default']);
                                    ?>
                                    <tr style="border-bottom: 1px solid var(--ks-border-light);">
                                        <td class="py-3 px-2">
                                            <div class="d-flex flex-column">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-bold text-navy" style="font-size: 13.5px;"><?= htmlspecialchars($org['name']) ?></span>
                                                    <?php if ($isDefault): ?>
                                                        <span class="badge" style="background: #F1F5F9; color: #475569; font-size: 10px; font-weight: 600; padding: 2px 5px;" title="Current Context Organisation">Default</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mt-1">
                                                    <span class="badge" style="background: #F8FAFD; color: #64748B; border: 1px solid var(--ks-border); font-size: 11px; font-family: monospace; font-weight: 600; padding: 2px 6px;">
                                                        <?= htmlspecialchars($org['organization_code'] ?? '—') ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-2">
                                            <span class="ks-badge ks-badge-blue" style="font-size: 11.5px; padding: 3px 9px;">
                                                <?= htmlspecialchars($roleDisplay) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-2 text-end">
                                            <?php if ($isOrgActive): ?>
                                                <span class="ks-badge ks-badge-confirmed" style="font-size: 11.5px; padding: 3px 9px;">Active</span>
                                            <?php else: ?>
                                                <span class="ks-badge ks-badge-rejected" style="font-size: 11.5px; padding: 3px 9px;"><?= htmlspecialchars(ucfirst($org['status'] ?? 'Inactive')) ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
