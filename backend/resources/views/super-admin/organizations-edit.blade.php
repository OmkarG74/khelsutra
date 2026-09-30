<?php
$activePage = 'organizations';
$title = 'Edit Organisation — KhelSutra Super Admin';

$orgId = (int)($id ?? 1);
$orgService = new \App\Services\Organization\OrganizationManagementService();
$org = $orgService->getOrganization($orgId);
$admins = $org ? $orgService->getOrganizationAdmins($orgId) : [];

$selectedEditAdminId = (int)($adminId ?? ($_GET['edit_admin'] ?? 0));
$showAddAdminInitial = (($action ?? ($_GET['action'] ?? '')) === 'add-admin');
$selectedAdmin = null;
if ($selectedEditAdminId > 0 && !empty($admins)) {
    foreach ($admins as $a) {
        if ((int)$a['id'] === $selectedEditAdminId) {
            $selectedAdmin = $a;
            break;
        }
    }
}

ob_start();
?>

<!-- Page Header -->
<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge" style="background: #EAF3FF; color: #0B6EF3; font-weight: 700; padding: 4px 8px; border-radius: 6px; font-size: 12px; letter-spacing: 0.5px;">
                <?= htmlspecialchars($org['organization_code'] ?? '—') ?>
            </span>
            <span class="ks-badge ks-badge-blue"><?= htmlspecialchars($org['plan_name'] ?? 'Standard Sports ERP') ?></span>
        </div>
        <h1 class="ks-page-title">Edit Organisation</h1>
    </div>
    <div class="ks-header-actions">
        <a href="/super-admin/organizations/<?= $orgId ?>" class="ks-btn ks-btn-secondary">
            <i class="bi bi-eye"></i>
            <span>View Organisation</span>
        </a>
        <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary">
            <i class="bi bi-arrow-left"></i>
            <span>Back to List</span>
        </a>
    </div>
</div>

<?php if (!$org): ?>
    <div class="ks-card p-5 text-center text-muted">
        <i class="bi bi-building-x fs-1 text-danger"></i>
        <h5 class="mt-3 text-navy fw-bold">Organisation Not Found</h5>
        <p>The requested organisation does not exist.</p>
        <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary mt-2">Return to Organisations</a>
    </div>
<?php else: ?>

    <!-- SECTION 1: Organisation Details Form -->
    <div class="ks-card p-4 mb-4">
        <form id="orgDetailsForm">
            <!-- Header with Status Badge -->
            <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-building text-primary" style="font-size: 16px;"></i>
                    <h3 class="fw-bold text-navy mb-0" style="font-size: 15px;">Organisation Details</h3>
                </div>
                <div>
                    <?php if (($org['status'] ?? '') === 'active'): ?>
                        <span class="ks-badge ks-badge-confirmed">Active</span>
                    <?php elseif (($org['status'] ?? '') === 'suspended'): ?>
                        <span class="ks-badge ks-badge-rejected">Suspended</span>
                    <?php else: ?>
                        <span class="ks-badge ks-badge-scheduled"><?= htmlspecialchars(ucfirst($org['status'] ?? 'Pending')) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 1. Organisation Information -->
            <div class="mb-4">
                <h6 class="fw-bold text-navy mb-2" style="font-size: 13.5px;">1. Organisation Information</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ks-form-label">Organisation Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="ks-form-control" value="<?= htmlspecialchars($org['name'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label">Organisation Code</label>
                        <input type="text" name="organization_code" class="ks-form-control" value="<?= htmlspecialchars($org['organization_code'] ?? '') ?>" readonly style="background: #F8FAFD; cursor: default;" title="Organisation code cannot be altered.">
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label">Legal / Registered Name</label>
                        <input type="text" name="legal_name" class="ks-form-control" value="<?= htmlspecialchars($org['legal_name'] ?? '') ?>" placeholder="Registered Trust / Private Ltd">
                    </div>
                </div>
            </div>

            <!-- 2. Contact Information -->
            <div class="mb-4">
                <h6 class="fw-bold text-navy mb-2" style="font-size: 13.5px;">2. Contact Information</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="ks-form-label">Contact Email</label>
                        <input type="email" name="email" class="ks-form-control" value="<?= htmlspecialchars($org['email'] ?? '') ?>" placeholder="contact@academy.org">
                    </div>

                    <div class="col-md-4">
                        <label class="ks-form-label">Phone Number</label>
                        <input type="text" name="phone" class="ks-form-control" value="<?= htmlspecialchars($org['phone'] ?? '') ?>" placeholder="+91 98765 43210">
                    </div>

                    <div class="col-md-4">
                        <label class="ks-form-label">Website</label>
                        <input type="text" name="website" class="ks-form-control" value="<?= htmlspecialchars($org['website'] ?? '') ?>" placeholder="https://academy.org">
                    </div>
                </div>
            </div>

            <!-- 3. Address -->
            <div class="mb-4">
                <h6 class="fw-bold text-navy mb-2" style="font-size: 13.5px;">3. Address</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ks-form-label">Address Line 1</label>
                        <input type="text" name="address_line1" class="ks-form-control" value="<?= htmlspecialchars($org['address_line1'] ?? '') ?>" placeholder="Campus / Ground street address">
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label">Address Line 2</label>
                        <input type="text" name="address_line2" class="ks-form-control" value="<?= htmlspecialchars($org['address_line2'] ?? '') ?>" placeholder="Suite / Floor / Landmark">
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <label class="ks-form-label">City</label>
                        <input type="text" name="city" class="ks-form-control" value="<?= htmlspecialchars($org['city'] ?? '') ?>" placeholder="Pune">
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <label class="ks-form-label">State</label>
                        <input type="text" name="state" class="ks-form-control" value="<?= htmlspecialchars($org['state'] ?? '') ?>" placeholder="Maharashtra">
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <label class="ks-form-label">Country</label>
                        <input type="text" name="country" class="ks-form-control" value="<?= htmlspecialchars($org['country'] ?? 'India') ?>" placeholder="India">
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <label class="ks-form-label">Postal Code</label>
                        <input type="text" name="postal_code" class="ks-form-control" value="<?= htmlspecialchars($org['postal_code'] ?? '') ?>" placeholder="411001">
                    </div>
                </div>
            </div>

            <!-- 4. Subscription / Access -->
            <div class="mb-4">
                <h6 class="fw-bold text-navy mb-2" style="font-size: 13.5px;">4. Subscription / Access</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="ks-form-label">Subscription Plan</label>
                        <select name="plan_name" class="ks-form-select">
                            <option value="Standard Sports ERP" <?= (($org['plan_name'] ?? '') === 'Standard Sports ERP' || empty($org['plan_name'])) ? 'selected' : '' ?>>Standard Sports ERP</option>
                            <option value="Enterprise" <?= (($org['plan_name'] ?? '') === 'Enterprise') ? 'selected' : '' ?>>Enterprise</option>
                            <option value="Enterprise Academy" <?= (($org['plan_name'] ?? '') === 'Enterprise Academy') ? 'selected' : '' ?>>Enterprise Academy</option>
                            <option value="High Performance Elite" <?= (($org['plan_name'] ?? '') === 'High Performance Elite') ? 'selected' : '' ?>>High Performance Elite</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="ks-form-label">Access Start Date</label>
                        <input type="date" name="access_start_date" class="ks-form-control" value="<?= htmlspecialchars($org['access_start_date'] ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="ks-form-label">Access End Date</label>
                        <input type="date" name="access_end_date" class="ks-form-control" value="<?= htmlspecialchars($org['access_end_date'] ?? date('Y-m-d', strtotime('+1 year'))) ?>">
                    </div>

                    <div class="col-12">
                        <label class="ks-form-label">Operational Notes</label>
                        <textarea name="notes" class="ks-form-control" rows="2" placeholder="Internal remarks..."><?= htmlspecialchars($org['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top" style="border-color: var(--ks-border-light) !important;">
                <a href="/super-admin/organizations/<?= $orgId ?>" class="ks-btn ks-btn-secondary">Cancel</a>
                <button type="submit" class="ks-btn ks-btn-primary" id="btnSaveOrgDetails">
                    <i class="bi bi-check2"></i>
                    <span>Save Organisation Details</span>
                </button>
            </div>
        </form>
    </div>

    <!-- SECTION 2: Organisation Administrators Management (ONLY on Edit Organisation) -->
    <div class="ks-card p-4 mb-4" id="admin-section">
        <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom" style="border-color: var(--ks-border-light) !important;">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock text-primary" style="font-size: 16px;"></i>
                    <h3 class="fw-bold text-navy mb-0" style="font-size: 15px;">Organisation Administrators</h3>
                </div>
            </div>
            <div>
                <button type="button" class="ks-btn ks-btn-primary" id="btnAddAdmin" style="height: 32px; padding: 0 12px; font-size: 12.5px;">
                    <i class="bi bi-person-plus-fill me-1"></i>  Add Administrator
                </button>
            </div>
        </div>

        <!-- Edit Administrator Form (Dedicated Section for updating an existing administrator) -->
        <div id="editAdminFormContainer" class="p-3 mb-3 rounded <?= $selectedAdmin ? '' : 'd-none' ?>" style="background: #F8FAFD; border: 1px solid var(--ks-border); box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge" style="background: #EAF3FF; color: #0B6EF3; font-weight: 700; padding: 4px 8px; border-radius: 4px; font-size: 11px;">EDIT</span>
                    <h6 class="fw-bold text-navy mb-0" id="editAdminHeaderTitle" style="font-size: 14px;">
                        Edit Organisation Administrator: <?= htmlspecialchars(trim(($selectedAdmin['first_name'] ?? '') . ' ' . ($selectedAdmin['last_name'] ?? ''))) ?>
                    </h6>
                </div>
                <button type="button" class="btn-close btn-sm" id="btnCloseEditAdmin" aria-label="Close"></button>
            </div>
            <form id="editAdminForm">
                <input type="hidden" id="editAdminId" name="admin_id" value="<?= (int)($selectedAdmin['id'] ?? 0) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ks-form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" id="editFirstName" name="first_name" class="ks-form-control" value="<?= htmlspecialchars($selectedAdmin['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Last Name</label>
                        <input type="text" id="editLastName" name="last_name" class="ks-form-control" value="<?= htmlspecialchars($selectedAdmin['last_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" id="editEmail" name="email" class="ks-form-control" value="<?= htmlspecialchars($selectedAdmin['email'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Phone Number</label>
                        <input type="text" id="editPhone" name="phone" class="ks-form-control" value="<?= htmlspecialchars($selectedAdmin['phone'] ?? '') ?>" placeholder="+91 98765 43210">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Username</label>
                        <input type="text" id="editUsername" name="username" class="ks-form-control" value="<?= htmlspecialchars($selectedAdmin['username'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Status</label>
                        <select id="editStatus" name="status" class="ks-form-select">
                            <option value="active" <?= (($selectedAdmin['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (($selectedAdmin['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Reset Password (Optional)</label>
                        <div class="position-relative">
                            <input type="password" id="editPassword" name="password" class="ks-form-control ks-password-input" placeholder="Leave blank to keep current" style="padding-right: 38px;">
                            <button type="button" class="btn btn-link position-absolute p-0 text-muted ks-password-toggle" 
                                    style="right: 10px; top: 50%; transform: translateY(-50%); border: none; background: transparent; text-decoration: none; display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; z-index: 5;" 
                                    aria-label="Show password" title="Show password" onclick="togglePasswordVisibility(this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
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
                <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top" style="border-color: var(--ks-border-light) !important;">
                    <button type="button" class="ks-btn ks-btn-secondary" id="btnCancelEditAdmin">Cancel</button>
                    <button type="submit" class="ks-btn ks-btn-primary" id="btnSubmitEditAdmin">
                        <i class="bi bi-check2 me-1"></i> Update Administrator
                    </button>
                </div>
            </form>
        </div>

        <!-- 1. List of Assigned Administrators (ALWAYS on top) -->
        <div class="d-flex flex-column gap-2" id="savedAdminsContainer">
            <?php if (empty($admins)): ?>
                <div class="p-3 rounded text-muted small text-center" id="noSavedAdminsNotice" style="background: #F8FAFD; border: 1px solid var(--ks-border-light);">
                    <i class="bi bi-person-x fs-3 d-block text-muted mb-1"></i>
                    No Organisation Administrators currently assigned. Click <strong>+ Add Administrator</strong> to assign one.
                </div>
            <?php else: ?>
                <?php foreach ($admins as $index => $admin): ?>
                    <?php
                    $fInitial = substr($admin['first_name'] ?? 'A', 0, 1);
                    $lInitial = substr($admin['last_name'] ?? 'D', 0, 1);
                    $initials = strtoupper($fInitial . $lInitial);
                    $fullName = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''));
                    $isActive = (($admin['status'] ?? 'active') === 'active');
                    $isBeingEdited = ($selectedEditAdminId === (int)$admin['id']);
                    ?>
                    <!-- Compact Administrator Card -->
                    <div class="ks-admin-card p-3 rounded <?= $isBeingEdited ? 'd-none' : '' ?>" style="background: #FFFFFF; border: 1px solid var(--ks-border); box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);" id="adminCard-<?= (int)$admin['id'] ?>" data-admin-id="<?= (int)$admin['id'] ?>">
                        <!-- Top Row: Left (Avatar, Existing badge, Name, Role badge) | Right (Status badge) -->
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="ks-avatar" style="width: 32px; height: 32px; border-radius: 6px; background: #0E1E3B; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; flex-shrink: 0;">
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

                        <!-- Secondary Row: Email | Phone | Username -->
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

                        <!-- Bottom Actions: Edit | Deactivate/Activate | Remove from Org -->
                        <div class="d-flex justify-content-end gap-1 mt-2 pt-2 border-top" style="border-color: var(--ks-border-light) !important;">
                            <button type="button" class="ks-btn ks-btn-secondary" style="height: 28px; padding: 0 10px; font-size: 11.5px;" onclick='startEditAdmin(<?= json_encode($admin, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                <i class="bi bi-pencil me-1"></i> Edit
                            </button>
                            <?php if ($isActive): ?>
                                <button type="button" class="ks-btn ks-btn-secondary text-danger" style="height: 28px; padding: 0 10px; font-size: 11.5px;" onclick="promptAdminStatusChange(<?= $orgId ?>, <?= (int)$admin['id'] ?>, '<?= htmlspecialchars(addslashes($fullName)) ?>', 'inactive')">
                                    <i class="bi bi-pause-circle me-1"></i> Deactivate
                                </button>
                            <?php else: ?>
                                <button type="button" class="ks-btn ks-btn-secondary text-success" style="height: 28px; padding: 0 10px; font-size: 11.5px;" onclick="promptAdminStatusChange(<?= $orgId ?>, <?= (int)$admin['id'] ?>, '<?= htmlspecialchars(addslashes($fullName)) ?>', 'active')">
                                    <i class="bi bi-play-circle me-1"></i> Activate
                                </button>
                            <?php endif; ?>
                            <button type="button" class="ks-btn ks-btn-secondary text-danger" style="height: 28px; padding: 0 10px; font-size: 11.5px;" onclick="promptRemoveAdmin(<?= $orgId ?>, <?= (int)$admin['id'] ?>, '<?= htmlspecialchars(addslashes($fullName)) ?>')">
                                <i class="bi bi-trash me-1"></i> Remove
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 2. Container for Appended New Administrator Forms (ALWAYS appears BELOW saved administrators) -->
        <div class="d-flex flex-column gap-3 mt-3" id="newAdminFormsContainer"></div>
    </div>

    <!-- Modals for Administrator Status Change and Removal -->
    <div class="modal fade" id="adminStatusConfirmModal" tabindex="-1" aria-labelledby="adminStatusConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
            <div class="modal-content" style="border-radius: 14px; border: 1px solid var(--ks-border);">
                <div class="modal-header pb-2" style="border-bottom: 1px solid var(--ks-border-light);">
                    <h5 class="modal-title fw-bold text-navy" id="adminStatusConfirmModalLabel">Confirm Status Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="d-flex align-items-center gap-3">
                        <div id="adminModalIcon" class="ks-icon-box" style="width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0;">
                            <i class="bi bi-person-exclamation fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-navy mb-1" id="adminModalTitle">Change Status?</h6>
                            <p class="text-muted small mb-0" id="adminModalMessage">Are you sure?</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer pt-2" style="border-top: 1px solid var(--ks-border-light);">
                    <button type="button" class="ks-btn ks-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="ks-btn" id="btnExecuteAdminStatus">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adminRemoveConfirmModal" tabindex="-1" aria-labelledby="adminRemoveConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content" style="border-radius: 14px; border: 1px solid var(--ks-border);">
                <div class="modal-header pb-2" style="border-bottom: 1px solid var(--ks-border-light);">
                    <h5 class="modal-title fw-bold text-navy" id="adminRemoveConfirmModalLabel">Remove Administrator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="ks-icon-box ks-icon-red" style="width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0;">
                            <i class="bi bi-person-x-fill text-danger fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-navy mb-1" id="adminRemoveModalTitle">Remove from Organisation?</h6>
                            <p class="text-muted small mb-0" id="adminRemoveModalMessage">
                                Are you sure you want to remove this administrator from this organisation? Their user account will remain intact, but their administrator privileges for this academy will be revoked.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer pt-2" style="border-top: 1px solid var(--ks-border-light);">
                    <button type="button" class="ks-btn ks-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="ks-btn btn-danger" id="btnExecuteAdminRemove">Remove Administrator</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    const orgId = <?= $orgId ?>;

    // Password visibility toggle
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

    // 1. Save Organisation Details (and any UNSAVED new administrator forms)
    document.getElementById('orgDetailsForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (this.dataset.saving === 'true') {
            return;
        }

        // Check only UNSAVED new administrator forms
        const newAdminCards = Array.from(document.querySelectorAll('#newAdminFormsContainer .new-admin-form-card'));
        const pendingAdmins = [];

        for (const card of newAdminCards) {
            // CRITICAL: Skip any forms that are already saved individually!
            if (card.dataset.saved === 'true') {
                continue;
            }
            const form = card.querySelector('form');
            if (!form || form.dataset.saved === 'true') {
                continue;
            }

            const fd = new FormData(form);
            const data = Object.fromEntries(fd.entries());
            const firstName = (data.first_name || '').trim();
            const email = (data.email || '').trim();
            const phone = (data.phone || '').trim();
            const username = (data.username || '').trim();

            // If user filled in anything in this unsaved form
            if (firstName !== '' || email !== '' || phone !== '' || username !== '') {
                if (!firstName) {
                    alert('Please provide a First Name for all unsaved administrator forms, or remove unneeded forms.');
                    card.querySelector('[name="first_name"]')?.focus();
                    return;
                }
                if (!email) {
                    alert('Please provide an Email Address for all unsaved administrator forms, or remove unneeded forms.');
                    card.querySelector('[name="email"]')?.focus();
                    return;
                }
                pendingAdmins.push({ card, form, data });
            }
        }

        this.dataset.saving = 'true';
        const btn = document.getElementById('btnSaveOrgDetails');
        btn.disabled = true;
        btn.innerHTML = '<span>Saving Organisation Details...</span>';

        const formData = new FormData(this);
        const payload = Object.fromEntries(formData.entries());

        try {
            const res = await fetch(`/api/v1/organizations/${orgId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (!json.success) {
                alert('Update failed: ' + (json.message || 'Validation error'));
                this.dataset.saving = 'false';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2"></i> <span>Save Organisation Details</span>';
                return;
            }

            // Process any pending UNSAVED administrator forms (each created exactly ONCE)
            if (pendingAdmins.length > 0) {
                for (let i = 0; i < pendingAdmins.length; i++) {
                    const item = pendingAdmins[i];
                    // Double check in case saved concurrently
                    if (item.card.dataset.saved === 'true' || item.form.dataset.saved === 'true') {
                        continue;
                    }
                    item.card.dataset.saving = 'true';
                    item.form.dataset.saving = 'true';

                    btn.innerHTML = `<span>Saving Administrators (${i + 1}/${pendingAdmins.length})...</span>`;
                    const aRes = await fetch(`/api/v1/organizations/${orgId}/admins`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(item.data)
                    });
                    const aJson = await aRes.json();
                    if (!aJson.success) {
                        alert(`Organisation saved, but failed to create administrator (${item.data.email}): ` + (aJson.message || 'Validation error'));
                        setTimeout(() => {
                            window.location.href = `/super-admin/organizations/${orgId}/edit#admin-section`;
                            window.location.reload();
                        }, 1000);
                        return;
                    }

                    // Mark form and card as saved
                    item.card.dataset.saving = 'false';
                    item.form.dataset.saving = 'false';
                    item.card.dataset.saved = 'true';
                    item.form.dataset.saved = 'true';
                }
            }

            if (window.ksToast) {
                window.ksToast('Organisation details and administrators saved successfully!', 'success');
            } else {
                alert('Organisation details and administrators saved successfully!');
            }
            setTimeout(() => {
                window.location.href = `/super-admin/organizations/${orgId}/edit#admin-section`;
                window.location.reload();
            }, 600);
        } catch (err) {
            alert('Request failed: ' + err.message);
            this.dataset.saving = 'false';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2"></i> <span>Save Organisation Details</span>';
        }
    });

    // Dynamic Repeatable Administrator Forms
    const savedAdminsCount = <?= count($admins) ?>;
    let newAdminFormCounter = 0;

    function appendNewAdministratorForm(initialData = {}) {
        newAdminFormCounter++;
        const adminNumber = savedAdminsCount + newAdminFormCounter;
        const formId = 'new-admin-' + adminNumber;

        const card = document.createElement('div');
        card.className = 'new-admin-form-card p-3 mb-3 rounded';
        card.id = formId;
        card.dataset.adminId = formId;
        card.dataset.saved = 'false';
        card.dataset.saving = 'false';
        card.style.background = '#F8FAFD';
        card.style.border = '1px solid var(--ks-border)';
        card.style.boxShadow = '0 1px 3px rgba(15, 23, 42, 0.04)';

        const firstNameVal = initialData.first_name ? String(initialData.first_name).replace(/"/g, '&quot;') : '';
        const lastNameVal = initialData.last_name ? String(initialData.last_name).replace(/"/g, '&quot;') : '';
        const emailVal = initialData.email ? String(initialData.email).replace(/"/g, '&quot;') : '';
        const phoneVal = initialData.phone ? String(initialData.phone).replace(/"/g, '&quot;') : '';
        const usernameVal = initialData.username ? String(initialData.username).replace(/"/g, '&quot;') : '';
        const passwordVal = initialData.password ? String(initialData.password).replace(/"/g, '&quot;') : 'SecretPassword123';
        const statusVal = initialData.status === 'inactive' ? 'inactive' : 'active';

        card.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge admin-status-badge" style="background: #EAF3FF; color: #0B6EF3; font-weight: 700; padding: 4px 8px; border-radius: 4px; font-size: 11px;">NEW</span>
                    <h6 class="fw-bold text-navy mb-0" style="font-size: 14px;">Admin ${adminNumber}</h6>
                </div>
                <button type="button" class="btn-close btn-sm" aria-label="Remove" title="Remove Admin ${adminNumber}" onclick="removeNewAdminForm('${formId}')"></button>
            </div>
            <form id="form-${formId}" data-saved="false" data-saving="false" onsubmit="event.preventDefault(); saveSingleNewAdmin('${formId}');">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ks-form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="ks-form-control" value="${firstNameVal}" placeholder="e.g. Mayur" required>
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Last Name</label>
                        <input type="text" name="last_name" class="ks-form-control" value="${lastNameVal}" placeholder="e.g. Ghadi">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="ks-form-control" value="${emailVal}" placeholder="admin@academy.org" required>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Phone Number</label>
                        <input type="text" name="phone" class="ks-form-control" value="${phoneVal}" placeholder="+91 98765 43210">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Username</label>
                        <input type="text" name="username" class="ks-form-control" value="${usernameVal}" placeholder="e.g. mayurghadi">
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Initial Password <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="password" name="password" class="ks-form-control ks-password-input" value="${passwordVal}" required style="padding-right: 38px;">
                            <button type="button" class="btn btn-link position-absolute p-0 text-muted ks-password-toggle" 
                                    style="right: 10px; top: 50%; transform: translateY(-50%); border: none; background: transparent; text-decoration: none; display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; z-index: 5;" 
                                    aria-label="Show password" title="Show password" onclick="togglePasswordVisibility(this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Status</label>
                        <select name="status" class="ks-form-select">
                            <option value="active" ${statusVal === 'active' ? 'selected' : ''}>Active</option>
                            <option value="inactive" ${statusVal === 'inactive' ? 'selected' : ''}>Inactive</option>
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
                <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top" style="border-color: var(--ks-border-light) !important;">
                    <button type="button" class="ks-btn ks-btn-secondary btn-remove-new-admin" onclick="removeNewAdminForm('${formId}')">
                        <i class="bi bi-x-circle me-1"></i> Cancel / Remove
                    </button>
                    <button type="submit" class="ks-btn ks-btn-primary btn-save-admin">
                        <i class="bi bi-person-check-fill me-1"></i> Save Administrator
                    </button>
                </div>
            </form>
        `;

        const container = document.getElementById('newAdminFormsContainer');
        container.appendChild(card);
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function removeNewAdminForm(formId) {
        const card = document.getElementById(formId);
        if (card) {
            card.remove();
        }
    }

    async function saveSingleNewAdmin(formId) {
        const card = document.getElementById(formId);
        if (!card) return;
        const form = card.querySelector('form');
        if (!form) return;

        // Check if already saving or saved to prevent double submission
        if (card.dataset.saving === 'true' || form.dataset.saving === 'true') {
            return;
        }
        if (card.dataset.saved === 'true' || form.dataset.saved === 'true') {
            return;
        }

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        const firstName = (payload.first_name || '').trim();
        const email = (payload.email || '').trim();

        if (!firstName) {
            alert('First name is required for this administrator.');
            card.querySelector('[name="first_name"]')?.focus();
            return;
        }
        if (!email) {
            alert('A valid email address is required for this administrator.');
            card.querySelector('[name="email"]')?.focus();
            return;
        }

        // Lock form immediately against duplicate clicks
        card.dataset.saving = 'true';
        form.dataset.saving = 'true';
        const btn = card.querySelector('.btn-save-admin');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span>Saving...</span>';
        }

        try {
            const res = await fetch(`/api/v1/organizations/${orgId}/admins`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.success) {
                // Permanently mark as saved
                card.dataset.saving = 'false';
                form.dataset.saving = 'false';
                card.dataset.saved = 'true';
                form.dataset.saved = 'true';

                // Update visual state on this form card
                const badge = card.querySelector('.admin-status-badge');
                if (badge) {
                    badge.style.background = '#E8F5E9';
                    badge.style.color = '#2E7D32';
                    badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> SAVED';
                }
                if (btn) {
                    btn.className = 'ks-btn btn-success disabled';
                    btn.style.pointerEvents = 'none';
                    btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Saved';
                }
                const removeBtn = card.querySelector('.btn-remove-new-admin');
                if (removeBtn) removeBtn.classList.add('d-none');
                const closeBtn = card.querySelector('.btn-close');
                if (closeBtn) closeBtn.classList.add('d-none');

                form.querySelectorAll('input, select').forEach(el => {
                    el.readOnly = true;
                    if (el.tagName === 'SELECT') el.disabled = true;
                });

                if (window.ksToast) {
                    window.ksToast('Organisation Administrator created and assigned!', 'success');
                } else {
                    alert('Organisation Administrator created and assigned!');
                }
            } else {
                alert('Error: ' + (json.message || 'Validation error'));
                card.dataset.saving = 'false';
                form.dataset.saving = 'false';
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-person-check-fill me-1"></i> Save Administrator';
                }
            }
        } catch (err) {
            alert('Request failed: ' + err.message);
            card.dataset.saving = 'false';
            form.dataset.saving = 'false';
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-person-check-fill me-1"></i> Save Administrator';
            }
        }
    }

    const btnAddAdmin = document.getElementById('btnAddAdmin');
    if (btnAddAdmin) {
        btnAddAdmin.addEventListener('click', () => appendNewAdministratorForm());
    }

    <?php if ($showAddAdminInitial): ?>
    document.addEventListener('DOMContentLoaded', () => {
        appendNewAdministratorForm();
    });
    <?php endif; ?>

    // Edit Admin Container & Handlers
    const editAdminContainer = document.getElementById('editAdminFormContainer');
    let currentlyEditingAdminId = <?= $selectedAdmin ? (int)$selectedAdmin['id'] : 'null' ?>;

    function startEditAdmin(admin) {
        // If another administrator was being edited, restore their card first
        if (currentlyEditingAdminId && currentlyEditingAdminId !== admin.id) {
            const prevCard = document.getElementById(`adminCard-${currentlyEditingAdminId}`);
            if (prevCard) {
                prevCard.classList.remove('d-none');
            }
        }

        currentlyEditingAdminId = admin.id;

        const targetCard = document.getElementById(`adminCard-${admin.id}`);
        if (targetCard) {
            // STEP 1: Hide the administrator's existing card
            targetCard.classList.add('d-none');
            // Move edit form in place of the hidden card
            targetCard.parentNode.insertBefore(editAdminContainer, targetCard);
        }

        document.getElementById('editAdminId').value = admin.id;
        document.getElementById('editFirstName').value = admin.first_name || '';
        document.getElementById('editLastName').value = admin.last_name || '';
        document.getElementById('editEmail').value = admin.email || '';
        document.getElementById('editPhone').value = admin.phone || '';
        document.getElementById('editUsername').value = admin.username || '';
        document.getElementById('editStatus').value = admin.status || 'active';
        document.getElementById('editPassword').value = '';

        const fullName = `${admin.first_name || ''} ${admin.last_name || ''}`.trim() || 'Administrator';
        document.getElementById('editAdminHeaderTitle').textContent = `Edit Organisation Administrator: ${fullName}`;

        // Ensure submit button is in its normal primary state
        const btn = document.getElementById('btnSubmitEditAdmin');
        if (btn) {
            btn.disabled = false;
            btn.className = 'ks-btn ks-btn-primary';
            btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Update Administrator';
        }

        // STEP 2: Show ONLY the edit form for that administrator
        editAdminContainer.classList.remove('d-none');
        editAdminContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function closeEditAdmin() {
        if (currentlyEditingAdminId) {
            const card = document.getElementById(`adminCard-${currentlyEditingAdminId}`);
            if (card) {
                card.classList.remove('d-none');
            }
            currentlyEditingAdminId = null;
        }
        editAdminContainer.classList.add('d-none');
    }

    document.getElementById('btnCloseEditAdmin').addEventListener('click', closeEditAdmin);
    document.getElementById('btnCancelEditAdmin').addEventListener('click', closeEditAdmin);

    // Edit Admin Form Submit
    document.getElementById('editAdminForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitEditAdmin');
        if (!btn || btn.disabled || this.dataset.saving === 'true') {
            return;
        }

        const adminId = document.getElementById('editAdminId').value;
        const originalHtml = '<i class="bi bi-check2 me-1"></i> Update Administrator';

        btn.disabled = true;
        this.dataset.saving = 'true';
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Updating...';

        const formData = new FormData(this);
        const payload = Object.fromEntries(formData.entries());

        try {
            const res = await fetch(`/api/v1/organizations/${orgId}/admins/${adminId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.success) {
                // Update the administrator card in the DOM immediately
                const card = document.getElementById(`adminCard-${adminId}`);
                if (card) {
                    const fullName = `${payload.first_name || ''} ${payload.last_name || ''}`.trim() || 'Administrator';
                    const fInitial = (payload.first_name || 'A').charAt(0).toUpperCase();
                    const lInitial = (payload.last_name || 'D').charAt(0).toUpperCase();
                    const initials = fInitial + lInitial;
                    const isActive = (payload.status === 'active');

                    // Update avatar initials
                    const avatar = card.querySelector('.ks-avatar');
                    if (avatar) avatar.textContent = initials;

                    // Update name
                    const nameEl = card.querySelector('.fw-bold.text-navy');
                    if (nameEl) nameEl.textContent = fullName;

                    // Update status badge
                    const topRowRight = card.querySelector('.d-flex.align-items-center.justify-content-between.mb-2 > div:last-child');
                    if (topRowRight) {
                        topRowRight.innerHTML = isActive 
                            ? '<span class="ks-badge ks-badge-confirmed" style="font-size: 11.5px;">Active</span>' 
                            : '<span class="ks-badge ks-badge-rejected" style="font-size: 11.5px;">Inactive</span>';
                    }

                    // Update email, phone, username rows
                    const textElements = card.querySelectorAll('.row.g-2 .text-navy');
                    if (textElements.length >= 3) {
                        textElements[0].textContent = payload.email || '—';
                        textElements[1].textContent = payload.phone || '—';
                        textElements[2].textContent = payload.username ? '@' + payload.username : '—';
                    }

                    // Update the edit button's payload so subsequent edits carry updated values
                    const editBtn = card.querySelector('button[onclick*="startEditAdmin"]');
                    if (editBtn) {
                        const updatedAdminObj = {
                            id: parseInt(adminId),
                            first_name: payload.first_name || '',
                            last_name: payload.last_name || '',
                            email: payload.email || '',
                            phone: payload.phone || '',
                            username: payload.username || '',
                            status: payload.status || 'active'
                        };
                        editBtn.setAttribute('onclick', `startEditAdmin(${JSON.stringify(updatedAdminObj)})`);
                    }

                    // RESTORE the updated card back to visible!
                    card.classList.remove('d-none');
                }

                currentlyEditingAdminId = null;

                // Close edit form container
                editAdminContainer.classList.add('d-none');

                if (window.ksToast) {
                    window.ksToast('Organisation Administrator updated successfully!', 'success');
                } else {
                    alert('Organisation Administrator updated successfully!');
                }
            } else {
                alert('Error: ' + (json.message || 'Update failed'));
            }
        } catch (err) {
            alert('Request failed: ' + err.message);
        } finally {
            // ALWAYS restore button to its normal primary state
            btn.disabled = false;
            this.dataset.saving = 'false';
            btn.className = 'ks-btn ks-btn-primary';
            btn.innerHTML = originalHtml;
        }
    });

    // Admin Status toggle modal
    let pendingAdminStatus = null;
    function promptAdminStatusChange(orgId, adminId, adminName, targetStatus) {
        pendingAdminStatus = { orgId, adminId, targetStatus };
        const modalEl = document.getElementById('adminStatusConfirmModal');
        const titleEl = document.getElementById('adminModalTitle');
        const msgEl = document.getElementById('adminModalMessage');
        const btnEl = document.getElementById('btnExecuteAdminStatus');
        const iconEl = document.getElementById('adminModalIcon');

        if (targetStatus === 'inactive') {
            titleEl.textContent = 'Deactivate Administrator?';
            msgEl.textContent = `Are you sure you want to deactivate "${adminName}"? They will lose access to administrative features for this organisation.`;
            btnEl.className = 'ks-btn btn-danger';
            btnEl.textContent = 'Deactivate';
            iconEl.className = 'ks-icon-box ks-icon-red';
            iconEl.innerHTML = '<i class="bi bi-pause-circle-fill text-danger fs-4"></i>';
        } else {
            titleEl.textContent = 'Activate Administrator?';
            msgEl.textContent = `Are you sure you want to activate "${adminName}"? Their administrative access will be restored.`;
            btnEl.className = 'ks-btn ks-btn-primary';
            btnEl.textContent = 'Activate';
            iconEl.className = 'ks-icon-box ks-icon-green';
            iconEl.innerHTML = '<i class="bi bi-play-circle-fill text-success fs-4"></i>';
        }

        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    }

    document.getElementById('btnExecuteAdminStatus').addEventListener('click', async function() {
        if (!pendingAdminStatus) return;
        const { orgId, adminId, targetStatus } = pendingAdminStatus;
        this.disabled = true;
        this.textContent = 'Updating...';

        try {
            const res = await fetch(`/api/v1/organizations/${orgId}/admins/${adminId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: targetStatus })
            });

            const json = await res.json();
            if (json.success) {
                if (window.ksToast) {
                    window.ksToast(`Administrator status changed to ${targetStatus}.`, 'success');
                }
                setTimeout(() => window.location.reload(), 500);
            } else {
                alert('Error: ' + (json.message || 'Status change failed'));
                this.disabled = false;
                this.textContent = 'Confirm';
            }
        } catch (e) {
            alert('Network request failed');
            this.disabled = false;
            this.textContent = 'Confirm';
        }
    });

    // Remove Admin modal
    let pendingRemoveAdmin = null;
    function promptRemoveAdmin(orgId, adminId, adminName) {
        pendingRemoveAdmin = { orgId, adminId };
        const modalEl = document.getElementById('adminRemoveConfirmModal');
        document.getElementById('adminRemoveModalTitle').textContent = `Remove ${adminName}?`;
        document.getElementById('adminRemoveModalMessage').textContent = `Are you sure you want to remove ${adminName} as an administrator of this organisation? The user account will NOT be deleted, but they will no longer be an administrator for this organisation.`;
        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    }

    document.getElementById('btnExecuteAdminRemove').addEventListener('click', async function() {
        if (!pendingRemoveAdmin) return;
        const { orgId, adminId } = pendingRemoveAdmin;
        this.disabled = true;
        this.textContent = 'Removing...';

        try {
            const res = await fetch(`/api/v1/organizations/${orgId}/admins/${adminId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const json = await res.json();
            if (json.success) {
                if (window.ksToast) {
                    window.ksToast('Administrator removed from organisation.', 'success');
                }
                setTimeout(() => window.location.reload(), 500);
            } else {
                alert('Error: ' + (json.message || 'Removal failed'));
                this.disabled = false;
                this.textContent = 'Remove Administrator';
            }
        } catch (e) {
            alert('Network request failed');
            this.disabled = false;
            this.textContent = 'Remove Administrator';
        }
    });
    </script>
<?php endif; ?>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
