<?php
$activePage = 'settings';
$title = 'Academy Settings — KhelSutra';

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Academy Settings</h1>
        <p class="ks-page-subtitle">Configure organisation profile, sporting disciplines, multi-tenant RBAC permissions, and alerts.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-secondary" onclick="location.reload();">
            <i class="bi bi-arrow-counterclockwise"></i>
            <span>Discard Changes</span>
        </button>
        <button class="ks-btn ks-btn-primary" onclick="alert('Settings saved successfully.');">
            <i class="bi bi-check2"></i>
            <span>Save Changes</span>
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Settings Navigation / Profile Card -->
    <div class="col-lg-3">
        <div class="ks-card p-3 mb-3">
            <div class="text-center py-3 border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="ks-user-avatar mx-auto mb-2" style="width: 56px; height: 56px; background: #E8F2FF; color: var(--ks-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px;">
                    <i class="bi bi-building"></i>
                </div>
                <h4 class="fw-bold text-navy mb-0" style="font-size: 16px;">Apex Sports Academy</h4>
                <div class="small text-primary fw-medium">ORG-DEMO</div>
            </div>
            <div class="d-flex flex-column gap-1 pt-3">
                <a href="#general" class="ks-nav-link active" style="padding: 10px 14px; font-size: 13px;">
                    <i class="bi bi-sliders"></i> Organisation Profile
                </a>
                <a href="#sports" class="ks-nav-link" style="color: var(--ks-text); background: transparent; padding: 10px 14px; font-size: 13px;">
                    <i class="bi bi-trophy"></i> Sports Disciplines
                </a>
                <a href="#rbac" class="ks-nav-link" style="color: var(--ks-text); background: transparent; padding: 10px 14px; font-size: 13px;">
                    <i class="bi bi-shield-lock"></i> Roles & RBAC
                </a>
                <a href="#notifications" class="ks-nav-link" style="color: var(--ks-text); background: transparent; padding: 10px 14px; font-size: 13px;">
                    <i class="bi bi-bell"></i> Notifications
                </a>
            </div>
        </div>
    </div>

    <!-- Settings Form Body (Section 24 Forms) -->
    <div class="col-lg-9">
        <div class="ks-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom" style="border-color: var(--ks-border-light) !important;">
                <h3 class="fw-bold text-navy mb-0" style="font-size: 18px;">Organisation Profile & Identity</h3>
                <span class="ks-badge ks-badge-confirmed">Multi-Tenant Verified</span>
            </div>

            <form>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="ks-form-label">Organisation Name *</label>
                        <input type="text" class="ks-form-control" value="Apex Sports Academy" required>
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Organisation Code (Unique Slug) *</label>
                        <input type="text" class="ks-form-control" value="ORG-DEMO" readonly style="background: #F8FAFD;">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="ks-form-label">Contact Email</label>
                        <input type="email" class="ks-form-control" value="admin@khelsutra.com">
                    </div>
                    <div class="col-md-6">
                        <label class="ks-form-label">Phone Contact</label>
                        <input type="text" class="ks-form-control" value="+91 98765 43210">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="ks-form-label">Campus / Headquarters Address</label>
                    <input type="text" class="ks-form-control" value="Sector 4, Sports City Complex, Navi Mumbai, Maharashtra 400706">
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="ks-form-label">Default Academic Timezone</label>
                        <select class="ks-form-select">
                            <option selected>Asia/Kolkata (IST +05:30)</option>
                            <option>UTC (GMT +00:00)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Currency Symbol</label>
                        <select class="ks-form-select">
                            <option selected>INR (₹) - Indian Rupee</option>
                            <option>USD ($) - US Dollar</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="ks-form-label">Attendance Threshold (%)</label>
                        <input type="number" class="ks-form-control" value="75">
                    </div>
                </div>

                <div class="pt-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--ks-border-light) !important;">
                    <button type="button" class="ks-btn ks-btn-secondary">Reset to Defaults</button>
                    <button type="button" class="ks-btn ks-btn-primary" onclick="alert('Settings saved successfully.');">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
