<?php
/**
 * Reusable Organisation Form Component
 * Modes: 'create' | 'view' | 'edit'
 * 
 * Preserves the exact visual styling, typography, colors, and layouts of KhelSutra.
 */

$mode = $mode ?? 'create';
$org = $org ?? [];
$admins = $admins ?? ($org['admins'] ?? []);
$isView = ($mode === 'view');
$isEdit = ($mode === 'edit');
$isCreate = ($mode === 'create');
$readOnlyAttr = $isView ? 'readonly style="background: #F8FAFD; cursor: default;"' : '';
$disabledAttr = $isView ? 'disabled style="background: #F8FAFD; cursor: default;"' : '';
?>

<div class="ks-card p-4">
    <form id="orgMasterForm">
        <!-- 1. Organisation Information -->
        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-building text-primary" style="font-size: 16px;"></i>
                    <h3 class="fw-bold text-navy mb-0" style="font-size: 15px;">
                        1. Organisation Information
                    </h3>
                </div>
                <?php if ($isView && !empty($org['status'])): ?>
                    <div>
                        <?php if ($org['status'] === 'active'): ?>
                            <span class="ks-badge ks-badge-confirmed">Active</span>
                        <?php elseif ($org['status'] === 'suspended'): ?>
                            <span class="ks-badge ks-badge-rejected">Suspended</span>
                        <?php else: ?>
                            <span class="ks-badge ks-badge-scheduled"><?= htmlspecialchars(ucfirst($org['status'])) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="ks-form-label">Organisation Name <?= !$isView ? '<span class="text-danger">*</span>' : '' ?></label>
                    <input type="text" name="name" class="ks-form-control" value="<?= htmlspecialchars($org['name'] ?? '') ?>" placeholder="e.g. Phoenix Sports Academy" <?= $isView ? $readOnlyAttr : 'required' ?>>
                </div>

                <div class="col-md-6">
                    <label class="ks-form-label">Organisation Code <?= $isCreate ? '<span class="text-danger">*</span>' : '' ?></label>
                    <?php if ($isCreate): ?>
                        <input type="text" name="organization_code" class="ks-form-control" value="<?= htmlspecialchars($org['organization_code'] ?? 'ORG-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6))) ?>" placeholder="e.g. ORG-PHOENIX" required>
                    <?php else: ?>
                        <input type="text" name="organization_code" class="ks-form-control" value="<?= htmlspecialchars($org['organization_code'] ?? '') ?>" readonly style="background: #F8FAFD; cursor: default;">
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="ks-form-label">Legal / Registered Name</label>
                    <input type="text" name="legal_name" class="ks-form-control" value="<?= htmlspecialchars($org['legal_name'] ?? '') ?>" placeholder="Registered Trust / Private Ltd" <?= $readOnlyAttr ?>>
                </div>

                <div class="col-md-6">
                    <label class="ks-form-label">Initial Status</label>
                    <?php if ($isView): ?>
                        <input type="text" class="ks-form-control" value="<?= htmlspecialchars(ucfirst($org['status'] ?? 'Active')) ?>" <?= $readOnlyAttr ?>>
                    <?php else: ?>
                        <select name="status" class="ks-form-select">
                            <option value="active" <?= (($org['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="pending" <?= (($org['status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="inactive" <?= (($org['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2. Contact Information -->
        <div class="mb-4">
            <div class="pb-2 mb-3 border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-envelope text-primary" style="font-size: 16px;"></i>
                    <h3 class="fw-bold text-navy mb-0" style="font-size: 15px;">
                        2. Contact Information
                    </h3>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="ks-form-label">Contact Email</label>
                    <input type="email" name="email" class="ks-form-control" value="<?= htmlspecialchars($org['email'] ?? '') ?>" placeholder="contact@academy.org" <?= $readOnlyAttr ?>>
                </div>

                <div class="col-md-4">
                    <label class="ks-form-label">Phone Number</label>
                    <input type="text" name="phone" class="ks-form-control" value="<?= htmlspecialchars($org['phone'] ?? '') ?>" placeholder="+91 98765 43210" <?= $readOnlyAttr ?>>
                </div>

                <div class="col-md-4">
                    <label class="ks-form-label">Website</label>
                    <input type="text" name="website" class="ks-form-control" value="<?= htmlspecialchars($org['website'] ?? '') ?>" placeholder="https://academy.org" <?= $readOnlyAttr ?>>
                </div>
            </div>
        </div>

        <!-- 3. Address -->
        <div class="mb-4">
            <div class="pb-2 mb-3 border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-geo-alt text-primary" style="font-size: 16px;"></i>
                    <h3 class="fw-bold text-navy mb-0" style="font-size: 15px;">
                        3. Address
                    </h3>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="ks-form-label">Address Line 1</label>
                    <input type="text" name="address_line1" class="ks-form-control" value="<?= htmlspecialchars($org['address_line1'] ?? '') ?>" placeholder="Campus / Ground street address" <?= $readOnlyAttr ?>>
                </div>

                <div class="col-md-6">
                    <label class="ks-form-label">Address Line 2</label>
                    <input type="text" name="address_line2" class="ks-form-control" value="<?= htmlspecialchars($org['address_line2'] ?? '') ?>" placeholder="Suite / Block / Landmark" <?= $readOnlyAttr ?>>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label class="ks-form-label">City</label>
                    <input type="text" name="city" class="ks-form-control" value="<?= htmlspecialchars($org['city'] ?? '') ?>" placeholder="Pune" <?= $readOnlyAttr ?>>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label class="ks-form-label">State</label>
                    <input type="text" name="state" class="ks-form-control" value="<?= htmlspecialchars($org['state'] ?? '') ?>" placeholder="Maharashtra" <?= $readOnlyAttr ?>>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label class="ks-form-label">Country</label>
                    <input type="text" name="country" class="ks-form-control" value="<?= htmlspecialchars($org['country'] ?? 'India') ?>" placeholder="India" <?= $readOnlyAttr ?>>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label class="ks-form-label">Postal Code</label>
                    <input type="text" name="postal_code" class="ks-form-control" value="<?= htmlspecialchars($org['postal_code'] ?? '') ?>" placeholder="411001" <?= $readOnlyAttr ?>>
                </div>
            </div>
        </div>

        <!-- 4. Subscription / Access -->
        <div class="mb-4">
            <div class="pb-2 mb-3 border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-card-checklist text-primary" style="font-size: 16px;"></i>
                    <h3 class="fw-bold text-navy mb-0" style="font-size: 15px;">
                        4. Subscription / Access
                    </h3>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="ks-form-label">Subscription Plan</label>
                    <?php if ($isView): ?>
                        <input type="text" class="ks-form-control" value="<?= htmlspecialchars($org['plan_name'] ?? 'Standard Sports ERP') ?>" <?= $readOnlyAttr ?>>
                    <?php else: ?>
                        <select name="plan_name" class="ks-form-select">
                            <option value="Standard Sports ERP" <?= (($org['plan_name'] ?? '') === 'Standard Sports ERP' || empty($org['plan_name'])) ? 'selected' : '' ?>>Standard Sports ERP</option>
                            <option value="Enterprise" <?= (($org['plan_name'] ?? '') === 'Enterprise') ? 'selected' : '' ?>>Enterprise</option>
                            <option value="Enterprise Academy" <?= (($org['plan_name'] ?? '') === 'Enterprise Academy') ? 'selected' : '' ?>>Enterprise Academy</option>
                            <option value="High Performance Elite" <?= (($org['plan_name'] ?? '') === 'High Performance Elite') ? 'selected' : '' ?>>High Performance Elite</option>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="ks-form-label">Access Start Date</label>
                    <input type="date" name="access_start_date" class="ks-form-control" value="<?= htmlspecialchars($org['access_start_date'] ?? date('Y-m-d')) ?>" <?= $readOnlyAttr ?>>
                </div>

                <div class="col-md-4">
                    <label class="ks-form-label">Access End Date</label>
                    <input type="date" name="access_end_date" class="ks-form-control" value="<?= htmlspecialchars($org['access_end_date'] ?? date('Y-m-d', strtotime('+1 year'))) ?>" <?= $readOnlyAttr ?>>
                </div>

                <div class="col-12">
                    <label class="ks-form-label">Operational Notes</label>
                    <textarea name="notes" class="ks-form-control" rows="2" placeholder="Internal operational remarks..." <?= $readOnlyAttr ?>><?= htmlspecialchars($org['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- 5. Organisation Administrators -->
        <div class="mb-4">
            <div class="pb-2 mb-3 border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock text-primary" style="font-size: 16px;"></i>
                    <h3 class="fw-bold text-navy mb-0" style="font-size: 15px;">
                        5. Organisation Administrators
                    </h3>
                </div>
            </div>

            <?php if ($isCreate): ?>
                <!-- Dynamic Admins Container for Create Mode -->
                <div id="adminsContainer">
                    <!-- Admin 1 (Always Present) -->
                    <div class="ks-admin-row p-3 mb-3 rounded" style="background: #F8FAFD; border: 1px solid var(--ks-border-light);" data-admin-index="0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-navy admin-number-badge" style="font-size: 13.5px;">Admin 1</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="ks-form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="admins[0][first_name]" class="ks-form-control" placeholder="e.g. Vikram" required>
                            </div>
                            <div class="col-md-6">
                                <label class="ks-form-label">Last Name</label>
                                <input type="text" name="admins[0][last_name]" class="ks-form-control" placeholder="e.g. Joshi">
                            </div>
                            <div class="col-md-4">
                                <label class="ks-form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="admins[0][email]" class="ks-form-control" placeholder="admin@phoenixsports.org" required>
                            </div>
                            <div class="col-md-4">
                                <label class="ks-form-label">Phone Number</label>
                                <input type="text" name="admins[0][phone]" class="ks-form-control" placeholder="+91 98765 43210">
                            </div>
                            <div class="col-md-4">
                                <label class="ks-form-label">Username</label>
                                <input type="text" name="admins[0][username]" class="ks-form-control" placeholder="e.g. vikram.joshi">
                            </div>
                            <div class="col-md-4">
                                <label class="ks-form-label">Initial Password <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <input type="password" name="admins[0][password]" class="ks-form-control ks-password-input" value="SecretPassword123" required style="padding-right: 38px;">
                                    <button type="button" class="btn btn-link position-absolute p-0 text-muted ks-password-toggle" 
                                            style="right: 10px; top: 50%; transform: translateY(-50%); border: none; background: transparent; text-decoration: none; display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; z-index: 5;" 
                                            aria-label="Show password" title="Show password" onclick="togglePasswordVisibility(this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="ks-form-label">Status</label>
                                <select name="admins[0][status]" class="ks-form-select">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="ks-form-label">Assigned Role</label>
                                <div class="p-2 rounded d-flex align-items-center justify-content-between" style="background: #FFFFFF; border: 1px solid var(--ks-border); height: 38px;">
                                    <span class="badge" style="background: #EAF3FF; color: #0B6EF3; font-weight: 600; font-size: 11px;">
                                        Organisation Admin
                                    </span>
                                    <span class="text-muted small" style="font-size: 11px;">(Fixed Role)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Another Admin Action -->
                <div class="mb-3">
                    <button type="button" class="ks-btn ks-btn-secondary" id="btnAddAdmin" onclick="addAdminRow()">
                        <i class="bi bi-person-plus-fill me-1"></i>
                        <span> Add Another Administrator</span>
                    </button>
                </div>

            <?php else: ?>
                <!-- VIEW and EDIT Mode: Show Existing Administrators in Clean Cards -->
                <div class="d-flex flex-column gap-2">
                    <?php if (empty($admins)): ?>
                        <div class="p-3 rounded text-muted small text-center" style="background: #F8FAFD; border: 1px solid var(--ks-border-light);">
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
                            <div class="ks-admin-card p-3 rounded" style="background: #FFFFFF; border: 1px solid var(--ks-border); box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="ks-avatar" style="width: 32px; height: 32px; border-radius: 6px; background: #0E1E3B; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; letter-spacing: 0.5px;">
                                            <?= htmlspecialchars($initials) ?>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-navy" style="font-size: 13.5px;"><?= htmlspecialchars($fullName) ?></span>
                                            <span class="badge ms-1" style="background: #EAF3FF; color: #0B6EF3; font-weight: 600; font-size: 11px; padding: 2px 7px; border-radius: 4px;">
                                                Organisation Admin
                                            </span>
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
                                <div class="row g-2 py-1 small" style="font-size: 12.5px; color: var(--ks-text-muted);">
                                    <div class="col-md-4 col-sm-6">
                                        <span class="text-muted">Email:</span>
                                        <span class="text-navy fw-medium ms-1"><?= htmlspecialchars($admin['email'] ?? '—') ?></span>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <span class="text-muted">Phone:</span>
                                        <span class="text-navy fw-medium ms-1"><?= htmlspecialchars($admin['phone'] ?? '—') ?></span>
                                    </div>
                                    <div class="col-md-4 col-sm-12">
                                        <span class="text-muted">Username:</span>
                                        <span class="text-navy fw-medium ms-1"><?= !empty($admin['username']) ? '@' . htmlspecialchars($admin['username']) : '—' ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </div>

        <!-- FORM ACTIONS -->
        <div class="d-flex justify-content-end gap-2 pt-3 border-top" style="border-color: var(--ks-border-light) !important;">
            <?php if ($isView): ?>
                <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Organisations</span>
                </a>
                <a href="/super-admin/organizations/<?= (int)($org['id'] ?? 1) ?>/edit" class="ks-btn ks-btn-primary">
                    <i class="bi bi-pencil-square"></i>
                    <span>Edit Organisation</span>
                </a>
            <?php elseif ($isEdit): ?>
                <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary">
                    <span>Cancel</span>
                </a>
                <button type="submit" class="ks-btn ks-btn-primary" id="btnSaveOrg">
                    <i class="bi bi-check2"></i>
                    <span>Save Changes</span>
                </button>
            <?php else: ?>
                <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary">
                    <span>Cancel</span>
                </a>
                <button type="submit" class="ks-btn ks-btn-primary" id="btnSaveOrg">
                    <i class="bi bi-check2-circle"></i>
                    <span>Create Organisation</span>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($isCreate): ?>
<script>
let adminRowCounter = 1;

function togglePasswordVisibility(btn) {
    const parent = btn.closest('.position-relative');
    if (!parent) return;
    const input = parent.querySelector('.ks-password-input') || parent.querySelector('input');
    if (!input) return;
    const icon = btn.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        btn.setAttribute('aria-label', 'Hide password');
        btn.setAttribute('title', 'Hide password');
        if (icon) {
            icon.className = 'bi bi-eye-slash';
        }
    } else {
        input.type = 'password';
        btn.setAttribute('aria-label', 'Show password');
        btn.setAttribute('title', 'Show password');
        if (icon) {
            icon.className = 'bi bi-eye';
        }
    }
}

function addAdminRow() {
    adminRowCounter++;
    const container = document.getElementById('adminsContainer');
    const index = adminRowCounter - 1;
    
    const row = document.createElement('div');
    row.className = 'ks-admin-row p-3 mb-3 rounded';
    row.style.background = '#F8FAFD';
    row.style.border = '1px solid var(--ks-border-light)';
    row.setAttribute('data-admin-index', index);
    
    row.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold text-navy admin-number-badge" style="font-size: 13.5px;">Admin ${adminRowCounter}</span>
            <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1" style="font-size: 12px; padding: 3px 8px;" onclick="removeAdminRow(this)">
                <i class="bi bi-trash"></i>
                <span>Remove</span>
            </button>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="ks-form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" name="admins[${index}][first_name]" class="ks-form-control" placeholder="e.g. Priya" required>
            </div>
            <div class="col-md-6">
                <label class="ks-form-label">Last Name</label>
                <input type="text" name="admins[${index}][last_name]" class="ks-form-control" placeholder="e.g. Verma">
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="admins[${index}][email]" class="ks-form-control" placeholder="priya@phoenixsports.org" required>
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Phone Number</label>
                <input type="text" name="admins[${index}][phone]" class="ks-form-control" placeholder="+91 98765 43211">
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Username</label>
                <input type="text" name="admins[${index}][username]" class="ks-form-control" placeholder="e.g. priya.verma">
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Initial Password <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="password" name="admins[${index}][password]" class="ks-form-control ks-password-input" value="SecretPassword123" required style="padding-right: 38px;">
                    <button type="button" class="btn btn-link position-absolute p-0 text-muted ks-password-toggle" 
                            style="right: 10px; top: 50%; transform: translateY(-50%); border: none; background: transparent; text-decoration: none; display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; z-index: 5;" 
                            aria-label="Show password" title="Show password" onclick="togglePasswordVisibility(this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Status</label>
                <select name="admins[${index}][status]" class="ks-form-select">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Assigned Role</label>
                <div class="p-2 rounded d-flex align-items-center justify-content-between" style="background: #FFFFFF; border: 1px solid var(--ks-border); height: 38px;">
                    <span class="badge" style="background: #EAF3FF; color: #0B6EF3; font-weight: 600; font-size: 11px;">
                        Organisation Admin
                    </span>
                    <span class="text-muted small" style="font-size: 11px;">(Fixed Role)</span>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(row);
    reindexAdminBadges();
}

function removeAdminRow(btn) {
    const row = btn.closest('.ks-admin-row');
    if (row) {
        row.remove();
        reindexAdminBadges();
    }
}

function reindexAdminBadges() {
    const rows = document.querySelectorAll('#adminsContainer .ks-admin-row');
    rows.forEach((r, idx) => {
        const badge = r.querySelector('.admin-number-badge');
        if (badge) {
            badge.textContent = 'Admin ' + (idx + 1);
        }
    });
}

document.getElementById('orgMasterForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveOrg');
    btn.disabled = true;
    btn.innerHTML = '<span>Creating Organisation...</span>';

    // Parse formData with nested admins
    const formData = new FormData(this);
    const payload = {};
    const adminsMap = {};

    for (let [key, val] of formData.entries()) {
        const adminMatch = key.match(/^admins\[(\d+)\]\[([a-zA-Z0-9_]+)\]$/);
        if (adminMatch) {
            const idx = adminMatch[1];
            const field = adminMatch[2];
            if (!adminsMap[idx]) adminsMap[idx] = {};
            adminsMap[idx][field] = val;
        } else {
            payload[key] = val;
        }
    }

    payload.admins = Object.values(adminsMap);

    try {
        const res = await fetch('/api/v1/organizations', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.success) {
            if (window.ksToast) {
                window.ksToast('Organisation and administrator(s) created successfully!', 'success');
            } else {
                alert('Organisation and administrator(s) created successfully!');
            }
            setTimeout(() => {
                window.location.href = '/super-admin/organizations';
            }, 600);
        } else {
            const msg = json.message || 'Validation failed';
            if (window.ksToast) {
                window.ksToast(msg, 'danger');
            } else {
                alert('Error: ' + msg);
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle"></i> <span>Create Organisation</span>';
        }
    } catch (err) {
        alert('Request failed: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle"></i> <span>Create Organisation</span>';
    }
});
</script>
<?php endif; ?>
