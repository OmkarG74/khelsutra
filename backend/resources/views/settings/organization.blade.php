<?php
$activePage = 'settings';
$title = 'Organisation Settings — KhelSutra Platform';

$settingsService = new \App\Services\Organization\OrganizationSettingsService();
$orgId = current_organization_id();
$currentRoleId = (int)($_SESSION['auth']['user']['role_id'] ?? 0);
$isSuperAdmin = ($currentRoleId === 1 || ($_SESSION['auth']['user']['role'] ?? '') === 'super_admin');

// Fetch live organization metadata
$orgService = new \App\Services\Organization\OrganizationManagementService();
$org = $orgService->getOrganization($orgId) ?: [
    'id' => $orgId,
    'name' => 'Apex Sports Academy',
    'organization_code' => 'ORG-DEMO',
    'legal_name' => 'Apex Sports Academy Pvt Ltd',
    'email' => 'contact@apexsports.org',
    'phone' => '+91 98765 43210',
    'website' => 'https://apexsports.org',
    'address_line1' => 'Sector 4, Sports City Complex',
    'address_line2' => '',
    'city' => 'Navi Mumbai',
    'state' => 'Maharashtra',
    'country' => 'India',
    'postal_code' => '400706',
    'status' => 'active',
    'plan_name' => 'Standard Sports ERP',
    'notes' => 'Primary academy campus with comprehensive training facilities.'
];

// Flash messages
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

ob_start();
?>

<!-- Page Header (Clean Page Title + Action) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Organisation Settings</h1>
        <p class="ks-page-subtitle">Manage the configuration and profile used by your organization.</p>
    </div>
    <div class="ks-header-actions">
        <a href="/audit-logs" class="ks-btn ks-btn-secondary">
            <i class="bi bi-journal-text"></i>
            <span>View Audit Logs</span>
        </a>
    </div>
</div>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: var(--ks-border-radius-sm); font-size: 14px; font-weight: 500;">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: var(--ks-border-radius-sm); font-size: 14px; font-weight: 500;">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
        <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- SECTION 1: ORGANIZATION PROFILE & CAMPUS IDENTITY -->
    <div class="col-12">
        <div class="ks-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-user-avatar" style="width: 48px; height: 48px; background: #E8F2FF; color: var(--ks-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold text-navy mb-0" style="font-size: 18px;"><?= htmlspecialchars($org['name'] ?? 'Apex Sports Academy', ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="ks-badge ks-badge-blue"><?= htmlspecialchars($org['organization_code'] ?? 'ORG-DEMO', ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="ks-badge ks-badge-confirmed"><?= ucfirst(htmlspecialchars($org['status'] ?? 'active', ENT_QUOTES, 'UTF-8')) ?></span>
                            <span class="small text-muted">• Subscription: <strong><?= htmlspecialchars($org['plan_name'] ?? 'Standard Sports ERP', ENT_QUOTES, 'UTF-8') ?></strong></span>
                        </div>
                    </div>
                </div>
                <button type="button" class="ks-btn ks-btn-primary" id="btnToggleEditProfile" onclick="toggleEditProfile()">
                    <i class="bi bi-pencil-square"></i>
                    <span>Edit Organisation Profile</span>
                </button>
            </div>

            <!-- View Mode -->
            <div id="profileViewMode">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="small text-muted mb-1 text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Legal Entity Name</div>
                        <div class="fw-bold text-navy" style="font-size: 14px;"><?= htmlspecialchars($org['legal_name'] ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted mb-1 text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Contact Email</div>
                        <div class="fw-bold text-navy" style="font-size: 14px;">
                            <?php if (!empty($org['email'])): ?>
                                <a href="mailto:<?= htmlspecialchars($org['email'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-primary">
                                    <?= htmlspecialchars($org['email'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted mb-1 text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Phone Contact</div>
                        <div class="fw-bold text-navy" style="font-size: 14px;"><?= htmlspecialchars($org['phone'] ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="col-md-4">
                        <div class="small text-muted mb-1 text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Official Website</div>
                        <div class="fw-bold text-navy" style="font-size: 14px;">
                            <?php if (!empty($org['website'])): ?>
                                <a href="<?= htmlspecialchars($org['website'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="text-decoration-none text-primary">
                                    <?= htmlspecialchars($org['website'], ENT_QUOTES, 'UTF-8') ?> <i class="bi bi-box-arrow-up-right small"></i>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="small text-muted mb-1 text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Campus / Headquarters Address</div>
                        <div class="fw-bold text-navy" style="font-size: 14px;">
                            <?php
                            $addrParts = array_filter([
                                $org['address_line1'] ?? '',
                                $org['address_line2'] ?? '',
                                $org['city'] ?? '',
                                $org['state'] ?? '',
                                $org['country'] ?? 'India',
                                $org['postal_code'] ?? ''
                            ]);
                            echo !empty($addrParts) ? htmlspecialchars(implode(', ', $addrParts), ENT_QUOTES, 'UTF-8') : '—';
                            ?>
                        </div>
                    </div>

                    <?php if (!empty($org['notes'])): ?>
                    <div class="col-12">
                        <div class="small text-muted mb-1 text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Operational Notes</div>
                        <div class="small text-secondary"><?= nl2br(htmlspecialchars($org['notes'], ENT_QUOTES, 'UTF-8')) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Edit Mode (Inline Form) -->
            <div id="profileEditMode" style="display: none;">
                <form action="/settings/profile" method="POST" id="formOrgProfile">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="ks-form-label">Organisation Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="ks-form-control" value="<?= htmlspecialchars($org['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Organisation Code (Read-Only Slug)</label>
                            <input type="text" class="ks-form-control" value="<?= htmlspecialchars($org['organization_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly style="background: #F8FAFD; cursor: not-allowed;">
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Legal Entity Name</label>
                            <input type="text" name="legal_name" class="ks-form-control" value="<?= htmlspecialchars($org['legal_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Official Contact Email</label>
                            <input type="email" name="email" class="ks-form-control" value="<?= htmlspecialchars($org['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Phone Contact</label>
                            <input type="text" name="phone" class="ks-form-control" value="<?= htmlspecialchars($org['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Official Website</label>
                            <input type="url" name="website" class="ks-form-control" value="<?= htmlspecialchars($org['website'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="https://...">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Country</label>
                            <input type="text" name="country" class="ks-form-control" value="<?= htmlspecialchars($org['country'] ?? 'India', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Address Line 1</label>
                            <input type="text" name="address_line1" class="ks-form-control" value="<?= htmlspecialchars($org['address_line1'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="ks-form-label">Address Line 2</label>
                            <input type="text" name="address_line2" class="ks-form-control" value="<?= htmlspecialchars($org['address_line2'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">City</label>
                            <input type="text" name="city" class="ks-form-control" value="<?= htmlspecialchars($org['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">State / Province</label>
                            <input type="text" name="state" class="ks-form-control" value="<?= htmlspecialchars($org['state'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ks-form-label">Postal / PIN Code</label>
                            <input type="text" name="postal_code" class="ks-form-control" value="<?= htmlspecialchars($org['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-12">
                            <label class="ks-form-label">Operational Notes</label>
                            <textarea name="notes" class="ks-form-control" rows="2"><?= htmlspecialchars($org['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-4" style="border-color: var(--ks-border-light) !important;">
                        <button type="button" class="ks-btn ks-btn-secondary" onclick="toggleEditProfile()">Cancel</button>
                        <button type="submit" class="ks-btn ks-btn-primary">
                            <i class="bi bi-check2"></i>
                            <span>Save Organisation Profile</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($isSuperAdmin): ?>
<!-- ADVANCED SYSTEM CONFIGURATION (RESTRICTED TO SUPER ADMIN) -->
<?php
$rawSettings = isset($settings) ? $settings : $settingsService->getSettingRows($orgId);
$settingsList = [];
if (is_array($rawSettings)) {
    if (!empty($rawSettings) && !isset($rawSettings[0]) && is_string(array_key_first($rawSettings))) {
        foreach ($rawSettings as $k => $v) {
            $settingsList[] = [
                'setting_key' => (string)$k,
                'setting_value' => is_scalar($v) ? (string)$v : json_encode($v),
                'setting_type' => gettype($v)
            ];
        }
    } else {
        $settingsList = $rawSettings;
    }
}
?>
<div class="ks-table-card mt-4">
    <div class="ks-table-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-gear" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Tenant Key-Value Store (Super Admin)</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table ks-table-settings">
            <thead>
                <tr>
                    <th>Setting Key</th>
                    <th>Value</th>
                    <th>Data Type</th>
                    <th>Scope</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($settingsList)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No settings defined yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($settingsList as $s): ?>
                        <?php
                        $sKey = is_array($s) ? ($s['setting_key'] ?? '') : '';
                        $sVal = is_array($s) ? ($s['setting_value'] ?? '') : (string)$s;
                        $sType = is_array($s) ? ($s['setting_type'] ?? 'string') : 'string';
                        $isDevTestKey = str_starts_with((string)$sKey, 'test.');
                        ?>
                        <tr>
                            <td>
                                <code class="fw-bold text-navy" style="font-size: 13px;"><?= htmlspecialchars((string)$sKey, ENT_QUOTES, 'UTF-8') ?></code>
                            </td>
                            <td>
                                <span class="small font-monospace text-dark"><?= htmlspecialchars((string)($sVal !== '' ? $sVal : 'null'), ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <span class="ks-badge ks-badge-blue"><?= htmlspecialchars((string)$sType, ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <?php if ($isDevTestKey): ?>
                                    <span class="ks-badge ks-badge-secondary" style="font-size: 11px;">Test / Dev Key</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-confirmed" style="font-size: 11px;">Tenant Custom</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function toggleEditProfile() {
    const viewEl = document.getElementById('profileViewMode');
    const editEl = document.getElementById('profileEditMode');
    const btn = document.getElementById('btnToggleEditProfile');
    if (editEl.style.display === 'none') {
        editEl.style.display = 'block';
        viewEl.style.display = 'none';
        btn.style.display = 'none';
    } else {
        editEl.style.display = 'none';
        viewEl.style.display = 'block';
        btn.style.display = 'inline-flex';
    }
}
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
