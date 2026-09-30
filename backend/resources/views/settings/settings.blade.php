<?php
$activePage = 'profile';
$title = 'Academy Settings — KhelSutra';

$orgId = current_organization_id();
$orgService = new \App\Services\Organization\OrganizationManagementService();
$settingsService = new \App\Services\Organization\OrganizationSettingsService();

$org = $orgService->getOrganization($orgId) ?: [];
$settings = $settingsService->getAll($orgId);

// Resolve initial values from database
$orgName = (string)($org['name'] ?? 'Apex Sports Academy');
$orgCode = (string)($org['organization_code'] ?? 'ORG-DEMO');
$orgEmail = (string)($org['email'] ?? 'admin@khelsutra.com');
$orgPhone = (string)($org['phone'] ?? '+91 98765 43210');
$orgAddress = (string)($org['address_line1'] ?? ($settings['academic.address'] ?? 'Sector 4, Sports City Complex, Navi Mumbai, Maharashtra 400706'));

$currentTimezone = (string)($settings['academic.timezone'] ?? ($settings['timezone'] ?? 'Asia/Kolkata (IST +05:30)'));
$currentCurrency = (string)($settings['academic.currency'] ?? ($settings['currency'] ?? 'INR (₹) - Indian Rupee'));
$currentThreshold = (string)($settings['attendance.threshold'] ?? ($settings['min_attendance_rate'] ?? '75'));

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Academy Settings</h1>
    </div>
</div>

<!-- Settings Form Body (Section 24 Forms) -->
<div class="ks-card p-3 p-md-4 w-100">
    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom" style="border-color: var(--ks-border-light) !important;">
        <h3 class="fw-bold text-navy mb-0" style="font-size: 16px;">Organisation Profile & Identity</h3>
    </div>

    <!-- Alert Container -->
    <div id="settingsAlertContainer" class="mb-3"></div>

    <form id="orgProfileSettingsForm" action="/settings/save" method="POST">
        <input type="hidden" name="organization_id" value="<?= (int)$orgId ?>">

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="ks-form-label">Organisation Name *</label>
                <input type="text" id="settingOrgName" name="name" class="ks-form-control" value="<?= htmlspecialchars($orgName) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="ks-form-label">Organisation Code (Unique Slug) *</label>
                <input type="text" id="settingOrgCode" name="organization_code" class="ks-form-control" value="<?= htmlspecialchars($orgCode) ?>" required style="background: #FFFFFF;">
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="ks-form-label">Contact Email</label>
                <input type="email" id="settingOrgEmail" name="email" class="ks-form-control" value="<?= htmlspecialchars($orgEmail) ?>">
            </div>
            <div class="col-md-6">
                <label class="ks-form-label">Phone Contact</label>
                <input type="text" id="settingOrgPhone" name="phone" class="ks-form-control" value="<?= htmlspecialchars($orgPhone) ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="ks-form-label">Campus / Headquarters Address</label>
            <input type="text" id="settingOrgAddress" name="address" class="ks-form-control" value="<?= htmlspecialchars($orgAddress) ?>">
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="ks-form-label">Default Academic Timezone</label>
                <select id="settingOrgTimezone" name="timezone" class="ks-form-select">
                    <option value="Asia/Kolkata (IST +05:30)" <?= (str_contains($currentTimezone, 'Asia/Kolkata') || str_contains($currentTimezone, 'IST')) ? 'selected' : '' ?>>Asia/Kolkata (IST +05:30)</option>
                    <option value="UTC (GMT +00:00)" <?= (str_contains($currentTimezone, 'UTC') || str_contains($currentTimezone, 'GMT')) ? 'selected' : '' ?>>UTC (GMT +00:00)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Currency Symbol</label>
                <select id="settingOrgCurrency" name="currency" class="ks-form-select">
                    <option value="INR (₹) - Indian Rupee" <?= (str_contains($currentCurrency, 'INR') || str_contains($currentCurrency, '₹')) ? 'selected' : '' ?>>INR (₹) - Indian Rupee</option>
                    <option value="USD ($) - US Dollar" <?= (str_contains($currentCurrency, 'USD') || str_contains($currentCurrency, '$')) ? 'selected' : '' ?>>USD ($) - US Dollar</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="ks-form-label">Attendance Threshold (%)</label>
                <input type="number" id="settingOrgAttendance" name="attendance_threshold" class="ks-form-control" min="0" max="100" value="<?= htmlspecialchars($currentThreshold) ?>" required>
            </div>
        </div>

        <div class="pt-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--ks-border-light) !important;">
            <button type="button" id="btnResetSettings" class="ks-btn ks-btn-secondary">Reset to Defaults</button>
            <button type="submit" id="btnSaveSettings" class="ks-btn ks-btn-primary">Save Settings</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('orgProfileSettingsForm');
    const saveBtn = document.getElementById('btnSaveSettings');
    const resetBtn = document.getElementById('btnResetSettings');
    const alertBox = document.getElementById('settingsAlertContainer');

    // Default configuration values
    const defaultValues = {
        name: <?= json_encode($orgName) ?>,
        organization_code: <?= json_encode($orgCode) ?>,
        email: <?= json_encode($orgEmail) ?>,
        phone: <?= json_encode($orgPhone) ?>,
        address: <?= json_encode($orgAddress) ?>,
        timezone: <?= json_encode($currentTimezone) ?>,
        currency: <?= json_encode($currentCurrency) ?>,
        attendance_threshold: <?= json_encode($currentThreshold) ?>
    };

    function showAlert(type, message) {
        if (!alertBox) return;
        const iconClass = type === 'success' ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger';
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const borderCol = type === 'success' ? 'var(--ks-success, #16A34A)' : 'var(--ks-danger, #DC2626)';

        alertBox.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 shadow-sm" role="alert" style="border-radius: 8px; font-size: 13.5px; border-left: 4px solid ${borderCol};">
                <i class="bi ${iconClass} fs-5"></i>
                <span class="fw-medium text-dark flex-grow-1">${escapeHtml(message)}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 10px;"></button>
            </div>
        `;
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearAlert() {
        if (alertBox) alertBox.innerHTML = '';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Reset to Defaults (Restores original loaded values)
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            document.getElementById('settingOrgName').value = defaultValues.name;
            document.getElementById('settingOrgCode').value = defaultValues.organization_code;
            document.getElementById('settingOrgEmail').value = defaultValues.email;
            document.getElementById('settingOrgPhone').value = defaultValues.phone;
            document.getElementById('settingOrgAddress').value = defaultValues.address;
            document.getElementById('settingOrgTimezone').value = defaultValues.timezone;
            document.getElementById('settingOrgCurrency').value = defaultValues.currency;
            document.getElementById('settingOrgAttendance').value = defaultValues.attendance_threshold;
            clearAlert();
        });
    }

    // Save Settings Submit Handler
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            if (saveBtn.disabled) return;

            clearAlert();

            // Collect values
            const name = (document.getElementById('settingOrgName').value || '').trim();
            const code = (document.getElementById('settingOrgCode').value || '').trim();
            const email = (document.getElementById('settingOrgEmail').value || '').trim();
            const phone = (document.getElementById('settingOrgPhone').value || '').trim();
            const address = (document.getElementById('settingOrgAddress').value || '').trim();
            const timezone = document.getElementById('settingOrgTimezone').value;
            const currency = document.getElementById('settingOrgCurrency').value;
            const attendance = (document.getElementById('settingOrgAttendance').value || '').trim();

            // Client-side validations
            if (!name) {
                showAlert('danger', 'Organisation Name is required.');
                document.getElementById('settingOrgName').focus();
                return;
            }

            if (!code) {
                showAlert('danger', 'Organisation Code is required.');
                document.getElementById('settingOrgCode').focus();
                return;
            }

            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showAlert('danger', 'Please enter a valid email address.');
                document.getElementById('settingOrgEmail').focus();
                return;
            }

            if (attendance === '' || isNaN(attendance) || Number(attendance) < 0 || Number(attendance) > 100) {
                showAlert('danger', 'Attendance threshold must be a valid percentage between 0 and 100.');
                document.getElementById('settingOrgAttendance').focus();
                return;
            }

            const payload = {
                organization_id: <?= (int)$orgId ?>,
                name: name,
                organization_code: code,
                email: email,
                phone: phone,
                address: address,
                timezone: timezone,
                currency: currency,
                attendance_threshold: Number(attendance)
            };

            // Section 8: Button Loading State
            saveBtn.disabled = true;
            const originalButtonText = saveBtn.innerHTML;
            saveBtn.innerHTML = 'Saving...';

            try {
                // Call backend endpoint
                const response = await fetch('/api/v1/settings/organization', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                let result = null;
                try {
                    result = await response.json();
                } catch (jsonErr) {
                    result = null;
                }

                if (response.ok && result && result.success) {
                    // Update default values to newly saved data
                    if (result.data) {
                        defaultValues.name = result.data.name || name;
                        defaultValues.organization_code = result.data.organization_code || code;
                        defaultValues.email = result.data.email || email;
                        defaultValues.phone = result.data.phone || phone;
                        defaultValues.address = result.data.address || address;
                        defaultValues.timezone = result.data.timezone || timezone;
                        defaultValues.currency = result.data.currency || currency;
                        defaultValues.attendance_threshold = (result.data.attendance_threshold !== undefined) ? result.data.attendance_threshold : attendance;

                        // Synchronize input fields
                        document.getElementById('settingOrgName').value = defaultValues.name;
                        document.getElementById('settingOrgCode').value = defaultValues.organization_code;
                        document.getElementById('settingOrgEmail').value = defaultValues.email;
                        document.getElementById('settingOrgPhone').value = defaultValues.phone;
                        document.getElementById('settingOrgAddress').value = defaultValues.address;
                        document.getElementById('settingOrgTimezone').value = defaultValues.timezone;
                        document.getElementById('settingOrgCurrency').value = defaultValues.currency;
                        document.getElementById('settingOrgAttendance').value = defaultValues.attendance_threshold;
                    }

                    showAlert('success', 'Organisation settings updated successfully.');

                    // Update top navbar badge if present
                    const orgBadge = document.querySelector('.ks-org-selector span');
                    if (orgBadge && !orgBadge.textContent.includes('KhelSutra Platform')) {
                        orgBadge.textContent = `${defaultValues.name} (${defaultValues.organization_code})`;
                    }
                } else {
                    let errMsg = (result && result.message) ? result.message : 'Failed to save organisation settings.';
                    if (response.status === 401) errMsg = 'Authentication failed. Please sign in again.';
                    if (response.status === 403) errMsg = 'Access forbidden: Insufficient permissions to modify organisation settings.';
                    if (response.status === 404) errMsg = 'Organisation not found.';
                    if (response.status === 422 && result && result.errors) {
                        const firstErrKey = Object.keys(result.errors)[0];
                        if (firstErrKey && Array.isArray(result.errors[firstErrKey]) && result.errors[firstErrKey][0]) {
                            errMsg = result.errors[firstErrKey][0];
                        }
                    } else if (response.status >= 500) {
                        errMsg = 'Internal server error occurred while persisting settings. Please try again.';
                    }

                    showAlert('danger', errMsg);
                }
            } catch (networkErr) {
                showAlert('danger', 'Network error: Unable to connect to server. Please check your connection.');
            } finally {
                // Restore button state
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalButtonText;
            }
        });
    }
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
