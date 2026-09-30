<?php
$activePage = 'organizations';
$title = 'View Organisation — KhelSutra Super Admin';

$orgId = (int)($id ?? 1);
$orgService = new \App\Services\Organization\OrganizationManagementService();
$org = $orgService->getOrganization($orgId);
$admins = $org ? $orgService->getOrganizationAdmins($orgId) : [];
$accessLogs = $org ? $orgService->getAccessLogs($orgId) : [];

ob_start();
?>

<?php if (!$org): ?>
    <div class="ks-page-header d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="ks-page-title mb-0" style="font-size: 20px; font-weight: 700; color: var(--ks-navy);">Organisation Details</h1>
        </div>
        <div class="ks-header-actions">
            <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary" style="height: 36px; padding: 0 14px; font-size: 13px; font-weight: 500;">
                <i class="bi bi-arrow-left me-1"></i>
                <span>Back to Organisations</span>
            </a>
        </div>
    </div>
    <div class="ks-card p-5 text-center text-muted" style="background: #FFFFFF; border: 1px solid var(--ks-border); border-radius: 12px;">
        <i class="bi bi-building-x fs-1 text-danger"></i>
        <h5 class="mt-3 text-navy fw-bold">Organisation Not Found</h5>
        <p class="small text-muted mb-3">The requested organisation does not exist or has been removed.</p>
        <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary">Return to Organisations</a>
    </div>
<?php else: ?>
    <!-- Page Header (Read-Only: Title + Badges + Back Button ONLY) -->
    <div class="ks-page-header d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <span class="badge" style="background: #EAF3FF; color: #0B6EF3; font-weight: 700; padding: 4px 9px; border-radius: 6px; font-size: 12px; letter-spacing: 0.5px;">
                    <?= htmlspecialchars($org['organization_code'] ?? '—') ?>
                </span>
                <?php if (($org['status'] ?? '') === 'active'): ?>
                    <span class="ks-badge ks-badge-confirmed">Active</span>
                <?php elseif (($org['status'] ?? '') === 'suspended'): ?>
                    <span class="ks-badge ks-badge-rejected">Suspended</span>
                <?php elseif (($org['status'] ?? '') === 'expired'): ?>
                    <span class="ks-badge ks-badge-pending">Expired</span>
                <?php else: ?>
                    <span class="ks-badge ks-badge-scheduled"><?= htmlspecialchars(ucfirst($org['status'] ?? 'Pending')) ?></span>
                <?php endif; ?>
                <span class="ks-badge ks-badge-blue"><?= htmlspecialchars($org['plan_name'] ?? 'Standard Sports ERP') ?></span>
            </div>
            <h1 class="ks-page-title mb-0" style="font-size: 22px; font-weight: 700; color: #0E1E3B;">
                <?= htmlspecialchars($org['name'] ?? 'Organisation') ?>
            </h1>
        </div>
        <div class="ks-header-actions">
            <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary" style="height: 36px; padding: 0 14px; font-size: 13px; font-weight: 500;">
                <i class="bi bi-arrow-left me-1"></i>
                <span>Back to Organisations</span>
            </a>
        </div>
    </div>

    <!-- SECTION 1 — ORGANISATION INFORMATION (3 Compact Tinted Info Cards) -->
    <div class="ks-card p-4 mb-3" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom" style="border-color: #EDF2F7 !important;">
            <div class="d-flex align-items-center gap-2">
                <div class="ks-icon-box" style="width: 32px; height: 32px; border-radius: 8px; background: #EAF3FF; color: #0B6EF3; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0;">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-navy mb-0" style="font-size: 14.5px;">Organisation Information</h3>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4 col-sm-6 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        Organisation Name
                    </div>
                    <div class="fw-bold text-navy" style="font-size: 14px; color: #0E1E3B;">
                        <?= htmlspecialchars($org['name'] ?? '—') ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        Organisation Code
                    </div>
                    <div class="mt-1">
                        <span class="badge" style="background: #FFFFFF; color: #0B6EF3; border: 1px solid #DCE5F1; font-family: monospace; font-size: 12px; font-weight: 700; padding: 4px 8px; border-radius: 6px;">
                            <?= htmlspecialchars($org['organization_code'] ?? '—') ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        Legal / Registered Name
                    </div>
                    <div class="fw-semibold text-navy" style="font-size: 14px; color: #0E1E3B;">
                        <?= htmlspecialchars($org['legal_name'] ?? '—') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2 — CONTACT INFORMATION (3 Compact Tinted Info Cards) -->
    <div class="ks-card p-4 mb-3" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom" style="border-color: #EDF2F7 !important;">
            <div class="d-flex align-items-center gap-2">
                <div class="ks-icon-box" style="width: 32px; height: 32px; border-radius: 8px; background: #E8F5E9; color: #2E7D32; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0;">
                    <i class="bi bi-envelope"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-navy mb-0" style="font-size: 14.5px;">Contact Information</h3>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4 col-sm-6 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        Contact Email
                    </div>
                    <div class="fw-semibold text-navy" style="font-size: 13.5px; word-break: break-all;">
                        <?php if (!empty($org['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($org['email']) ?>" class="text-decoration-none text-navy fw-semibold">
                                <?= htmlspecialchars($org['email']) ?>
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        Phone Number
                    </div>
                    <div class="fw-semibold text-navy" style="font-size: 13.5px;">
                        <?= htmlspecialchars($org['phone'] ?? '—') ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        Website
                    </div>
                    <div class="fw-semibold text-navy" style="font-size: 13.5px; word-break: break-all;">
                        <?php if (!empty($org['website'])): ?>
                            <a href="<?= htmlspecialchars($org['website']) ?>" target="_blank" rel="noopener noreferrer" class="text-primary text-decoration-none fw-semibold">
                                <?= htmlspecialchars($org['website']) ?> <i class="bi bi-box-arrow-up-right small ms-1"></i>
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 3 — ADDRESS (Responsive Grid: Wider Street Lines + Compact City/State/Country/Zip) -->
    <div class="ks-card p-4 mb-3" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom" style="border-color: #EDF2F7 !important;">
            <div class="d-flex align-items-center gap-2">
                <div class="ks-icon-box" style="width: 32px; height: 32px; border-radius: 8px; background: #FFF3E0; color: #E65100; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0;">
                    <i class="bi bi-geo-alt"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-navy mb-0" style="font-size: 14.5px;">Address</h3>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        Address Line 1
                    </div>
                    <div class="fw-semibold text-navy" style="font-size: 13.5px;">
                        <?= htmlspecialchars($org['address_line1'] ?? '—') ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        Address Line 2
                    </div>
                    <div class="fw-semibold text-navy" style="font-size: 13.5px;">
                        <?= htmlspecialchars($org['address_line2'] ?? '—') ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        City
                    </div>
                    <div class="fw-semibold text-navy" style="font-size: 13.5px;">
                        <?= htmlspecialchars($org['city'] ?? '—') ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        State
                    </div>
                    <div class="fw-semibold text-navy" style="font-size: 13.5px;">
                        <?= htmlspecialchars($org['state'] ?? '—') ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        Country
                    </div>
                    <div class="fw-semibold text-navy" style="font-size: 13.5px;">
                        <?= htmlspecialchars($org['country'] ?? 'India') ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        Postal Code
                    </div>
                    <div class="fw-semibold text-navy" style="font-size: 13.5px;">
                        <?= htmlspecialchars($org['postal_code'] ?? '—') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 4 — SUBSCRIPTION / ACCESS (3 Compact Cards + Operational Notes) -->
    <div class="ks-card p-4 mb-3" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom" style="border-color: #EDF2F7 !important;">
            <div class="d-flex align-items-center gap-2">
                <div class="ks-icon-box" style="width: 32px; height: 32px; border-radius: 8px; background: #F3E8FF; color: #7C3AED; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0;">
                    <i class="bi bi-credit-card"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-navy mb-0" style="font-size: 14.5px;">Subscription / Access</h3>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4 col-sm-6 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        Subscription Plan
                    </div>
                    <div class="mt-1">
                        <span class="ks-badge ks-badge-blue" style="font-size: 11.5px; padding: 4px 10px;">
                            <?= htmlspecialchars($org['plan_name'] ?? 'Standard Sports ERP') ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        Access Start Date
                    </div>
                    <div class="fw-semibold text-navy" style="font-size: 13.5px;">
                        <?= htmlspecialchars($org['access_start_date'] ?? '—') ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-12 col-12">
                <div class="p-3 rounded h-100" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                        Access End Date
                    </div>
                    <div class="fw-semibold text-navy" style="font-size: 13.5px;">
                        <?= htmlspecialchars($org['access_end_date'] ?? '—') ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($org['notes'])): ?>
                <div class="col-12 mt-3">
                    <div class="p-3 rounded" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: #64748B; margin-bottom: 4px;">
                            Operational Notes
                        </div>
                        <div class="text-navy small" style="line-height: 1.6;">
                            <?= nl2br(htmlspecialchars($org['notes'])) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 5 — ORGANISATION ADMINISTRATORS (Read-Only List, Zero Action Buttons) -->
    <div class="ks-card p-4 mb-3" id="admin-section" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom" style="border-color: #EDF2F7 !important;">
            <div class="d-flex align-items-center gap-2">
                <div class="ks-icon-box" style="width: 32px; height: 32px; border-radius: 8px; background: #E0F2FE; color: #0284C7; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0;">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-navy mb-0" style="font-size: 14.5px;">Organisation Administrators</h3>
                    <p class="small text-muted mb-0" style="font-size: 11.5px;">
                    </p>
                </div>
            </div>
            <span class="badge" style="background: #F1F5F9; color: #475569; font-weight: 600; font-size: 11.5px; padding: 4px 8px; border-radius: 6px;">
                <?= count($admins) ?> <?= count($admins) === 1 ? 'Administrator' : 'Administrators' ?>
            </span>
        </div>

        <div class="d-flex flex-column gap-2" id="adminsListContainer">
            <?php if (empty($admins)): ?>
                <div class="p-3 rounded text-muted small text-center" style="background: #F8FAFD; border: 1px solid #E9EFF6; border-radius: 10px;">
                    <i class="bi bi-person-x fs-3 d-block text-muted mb-1"></i>
                    No Organisation Administrators currently assigned.
                </div>
            <?php else: ?>
                <?php foreach ($admins as $index => $admin): ?>
                    <?php
                    $fInitial = substr($admin['first_name'] ?? 'A', 0, 1);
                    $lInitial = substr($admin['last_name'] ?? 'D', 0, 1);
                    $initials = strtoupper($fInitial . $lInitial);
                    $fullName = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''));
                    $isActive = (($admin['status'] ?? 'active') === 'active');
                    ?>
                    <!-- Compact Read-Only Administrator Card (ZERO action buttons) -->
                    <div class="ks-admin-card p-3 rounded" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 10px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);">
                        <!-- Top Row: Left (Avatar, Existing badge, Name, Role badge) | Right (Status badge) -->
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="ks-avatar" style="width: 34px; height: 34px; border-radius: 8px; background: #0E1E3B; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12.5px; letter-spacing: 0.5px; flex-shrink: 0;">
                                    <?= htmlspecialchars($initials) ?>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="badge" style="background: #F1F5F9; color: #475569; font-weight: 700; padding: 2px 6px; border-radius: 4px; font-size: 10px; letter-spacing: 0.5px;">EXISTING</span>
                                        <span class="fw-bold text-navy" style="font-size: 13.5px;"><?= htmlspecialchars($fullName) ?></span>
                                        <span class="badge ms-1" style="background: #EAF3FF; color: #0B6EF3; font-weight: 600; font-size: 11px; padding: 2px 7px; border-radius: 4px;">
                                            Organisation Admin
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <?php if ($isActive): ?>
                                    <span class="ks-badge ks-badge-confirmed" style="font-size: 11.5px;">Active</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-rejected" style="font-size: 11.5px;">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Secondary Row: Email | Phone | Username in Clean Tinted Boxes -->
                        <div class="row g-2 pt-1">
                            <div class="col-md-4 col-sm-6">
                                <div class="px-2 py-1 rounded" style="background: #F8FAFD; border: 1px solid #EDF2F7; font-size: 12px;">
                                    <span class="text-muted">Email:</span>
                                    <span class="text-navy fw-semibold ms-1"><?= htmlspecialchars($admin['email'] ?? '—') ?></span>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="px-2 py-1 rounded" style="background: #F8FAFD; border: 1px solid #EDF2F7; font-size: 12px;">
                                    <span class="text-muted">Phone:</span>
                                    <span class="text-navy fw-semibold ms-1"><?= htmlspecialchars($admin['phone'] ?? '—') ?></span>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <div class="px-2 py-1 rounded" style="background: #F8FAFD; border: 1px solid #EDF2F7; font-size: 12px;">
                                    <span class="text-muted">Username:</span>
                                    <span class="text-navy fw-semibold ms-1"><?= !empty($admin['username']) ? '@' . htmlspecialchars($admin['username']) : '—' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 6 — ACCESS & STATUS HISTORY (Read-Only Table) -->
    <?php if (!empty($accessLogs)): ?>
        <div class="ks-card p-4 mb-4" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
            <div class="d-flex align-items-center gap-2 pb-3 mb-3 border-bottom" style="border-color: #EDF2F7 !important;">
                <div class="ks-icon-box" style="width: 32px; height: 32px; border-radius: 8px; background: #F1F5F9; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0;">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-navy mb-0" style="font-size: 14.5px;">Access & Status History</h3>
                </div>
            </div>
            <div class="table-responsive">
                <table class="ks-table" style="font-size: 12.5px;">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Previous Status</th>
                            <th>New Status</th>
                            <th>Effective Dates</th>
                            <th>Remarks</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($accessLogs, 0, 5) as $log): ?>
                            <tr>
                                <td><span class="fw-semibold text-navy"><?= htmlspecialchars(ucfirst($log['action'] ?? '—')) ?></span></td>
                                <td><span class="text-muted"><?= htmlspecialchars($log['previous_status'] ?? '—') ?></span></td>
                                <td>
                                    <?php if (($log['new_status'] ?? '') === 'active'): ?>
                                        <span class="ks-badge ks-badge-confirmed" style="font-size: 11px;">Active</span>
                                    <?php else: ?>
                                        <span class="ks-badge ks-badge-rejected" style="font-size: 11px;"><?= htmlspecialchars(ucfirst($log['new_status'] ?? '—')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        <?= htmlspecialchars(($log['access_start_date'] ?? '—') . ' to ' . ($log['access_end_date'] ?? '—')) ?>
                                    </span>
                                </td>
                                <td><span class="text-muted small"><?= htmlspecialchars($log['remarks'] ?? '—') ?></span></td>
                                <td><span class="text-muted small"><?= htmlspecialchars($log['created_at'] ?? '—') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
