<?php
$activePage = 'organizations';
$title = 'Create Organisation — KhelSutra Super Admin';

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Create Organisation</h1>
        <p class="ks-page-subtitle">Onboard a new multi-tenant sports academy and configure its initial Sports Administrator.</p>
    </div>
    <div class="ks-header-actions">
        <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary">
            <i class="bi bi-arrow-left"></i>
            <span>Cancel & Back</span>
        </a>
    </div>
</div>

<div class="ks-card p-4">
    <form action="/api/v1/organizations" method="POST" id="createOrgForm">
        <!-- Section 1: Organisation Information -->
        <div class="mb-4">
            <h3 class="fw-bold text-navy pb-2 border-bottom" style="font-size: 16px; border-color: var(--ks-border-light) !important;">
                1. Organisation Identity & Location
            </h3>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="ks-form-label">Organisation Name *</label>
                    <input type="text" name="name" class="ks-form-control" placeholder="e.g. Phoenix Sports Academy" required>
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Organisation Code *</label>
                    <input type="text" name="organization_code" class="ks-form-control" placeholder="e.g. ORG-PHOENIX" required>
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Legal Name</label>
                    <input type="text" name="legal_name" class="ks-form-control" placeholder="Registered Trust / Private Ltd">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Subscription Plan</label>
                    <select name="plan_name" class="ks-form-select">
                        <option value="Standard Sports ERP" selected>Standard Sports ERP</option>
                        <option value="Enterprise Academy">Enterprise Academy</option>
                        <option value="High Performance Elite">High Performance Elite</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Email</label>
                    <input type="email" name="email" class="ks-form-control" placeholder="contact@academy.org">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Phone</label>
                    <input type="text" name="phone" class="ks-form-control" placeholder="+91 98765 43210">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Website</label>
                    <input type="text" name="website" class="ks-form-control" placeholder="https://academy.org">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Address Line 1</label>
                    <input type="text" name="address_line1" class="ks-form-control" placeholder="Campus / Ground street address">
                </div>
                <div class="col-md-2">
                    <label class="ks-form-label">City</label>
                    <input type="text" name="city" class="ks-form-control" placeholder="Pune">
                </div>
                <div class="col-md-2">
                    <label class="ks-form-label">State</label>
                    <input type="text" name="state" class="ks-form-control" placeholder="Maharashtra">
                </div>
                <div class="col-md-2">
                    <label class="ks-form-label">Postal Code</label>
                    <input type="text" name="postal_code" class="ks-form-control" placeholder="411001">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Access Start Date</label>
                    <input type="date" name="access_start_date" class="ks-form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Access End Date</label>
                    <input type="date" name="access_end_date" class="ks-form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                </div>
            </div>
        </div>

        <!-- Section 2: Initial Sports Administrator Setup (Rule 18) -->
        <div class="mb-4">
            <h3 class="fw-bold text-navy pb-2 border-bottom" style="font-size: 16px; border-color: var(--ks-border-light) !important;">
                2. Initial Sports Administrator Provisioning
            </h3>
            <p class="small text-muted mb-3">Provision initial administrator account with Role 2 (Sports Administrator) for this academy.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="ks-form-label">Admin First Name *</label>
                    <input type="text" name="admin_first_name" class="ks-form-control" placeholder="e.g. Vikram" required>
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Admin Last Name</label>
                    <input type="text" name="admin_last_name" class="ks-form-control" placeholder="e.g. Joshi">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Admin Email Address *</label>
                    <input type="email" name="admin_email" class="ks-form-control" placeholder="admin@phoenixsports.org" required>
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Initial Password *</label>
                    <input type="password" name="admin_password" class="ks-form-control" value="SecretPassword123" required>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-3 border-top" style="border-color: var(--ks-border-light) !important;">
            <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary">Cancel</a>
            <button type="submit" class="ks-btn ks-btn-primary">
                <i class="bi bi-check2-circle"></i>
                <span>Create & Provision Organisation</span>
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('createOrgForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    try {
        const res = await fetch('/api/v1/organizations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            alert('Organisation created successfully!');
            window.location.href = '/super-admin/organizations';
        } else {
            alert('Error: ' + (json.message || 'Validation failed'));
        }
    } catch (err) {
        alert('Request failed: ' + err.message);
    }
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
