<?php
$activePage = 'athletes';
$title = 'Athletes Management — KhelSutra';

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Athletes</h1>
        <p class="ks-page-subtitle">Manage athlete profiles, documents, teams, medical clearances, and performance.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-secondary" onclick="alert('Exporting Athlete Directory...');">
            <i class="bi bi-download"></i>
            <span>Export Report</span>
        </button>
        <button class="ks-btn ks-btn-primary" data-bs-toggle="modal" data-bs-target="#newAthleteModal">
            <i class="bi bi-plus-lg"></i>
            <span>+ Add Athlete</span>
        </button>
    </div>
</div>

<!-- KPI Stat Overview Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Total Athletes</div>
                    <div class="ks-kpi-value">142</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">
                    <i class="bi bi-arrow-up-short fs-5 align-middle"></i> +12 this month
                </span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 22C18 20 28 26 44 14C60 2 72 16 88 4" stroke="#16A34A" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green">
                    <i class="bi bi-check-circle-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Active Registrations</div>
                    <div class="ks-kpi-value">128</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">90.1% active participation</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 18C20 18 30 24 50 16C70 8 78 4 88 12" stroke="#16A34A" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber">
                    <i class="bi bi-heart-pulse-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Medical Pending</div>
                    <div class="ks-kpi-value">9</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-warning">Awaiting doctor review</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 12C22 18 42 6 62 20C74 24 82 14 88 8" stroke="#F59E0B" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple">
                    <i class="bi bi-shield-shaded fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Academy Teams</div>
                    <div class="ks-kpi-value">8</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">U-14, U-16, U-18 & Senior</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 20C16 16 34 8 52 14C70 20 78 12 88 6" stroke="#7C3AED" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Table Card & Filter Bar -->
<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-lines-fill" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Registered Athletes Roster</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="position-relative" style="width: 260px;">
                <i class="bi bi-search position-absolute" style="left: 12px; top: 12px; color: var(--ks-text-muted); font-size: 13px;"></i>
                <input type="text" class="ks-form-control" style="padding-left: 34px; height: 38px; font-size: 13px;" placeholder="Search by name, ID...">
            </div>
            <select class="ks-form-select" style="width: 140px; height: 38px; font-size: 13px;">
                <option value="">All Sports</option>
                <option value="football">Football</option>
                <option value="cricket">Cricket</option>
                <option value="badminton">Badminton</option>
                <option value="athletics">Athletics</option>
            </select>
            <select class="ks-form-select" style="width: 130px; height: 38px; font-size: 13px;">
                <option value="">All Status</option>
                <option value="active">Confirmed</option>
                <option value="pending">Pending</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Athlete Name</th>
                    <th>Reg ID</th>
                    <th>Sport & Category</th>
                    <th>Assigned Team</th>
                    <th>Attendance</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="ks-user-avatar" style="width: 34px; height: 34px; font-size: 12px; background: #E8F2FF; color: var(--ks-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                AS
                            </div>
                            <div>
                                <div class="fw-semibold text-navy">Aarav Sharma</div>
                                <div class="small text-muted">aarav.sharma@example.com</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="fw-medium text-navy">ATH-2026-001</span></td>
                    <td>
                        <span class="ks-badge ks-badge-blue">Football • Striker</span>
                    </td>
                    <td>Titans U-18</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                <div class="progress-bar bg-success" style="width: 94%;"></div>
                            </div>
                            <span class="small fw-semibold">94%</span>
                        </div>
                    </td>
                    <td><span class="ks-badge ks-badge-confirmed">Confirmed</span></td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">
                            View Profile
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="ks-user-avatar" style="width: 34px; height: 34px; font-size: 12px; background: #DCFCE7; color: var(--ks-success); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                RV
                            </div>
                            <div>
                                <div class="fw-semibold text-navy">Rohan Verma</div>
                                <div class="small text-muted">rohan.v@example.com</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="fw-medium text-navy">ATH-2026-004</span></td>
                    <td>
                        <span class="ks-badge ks-badge-cyan">Cricket • All-Rounder</span>
                    </td>
                    <td>Strikers U-14</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                <div class="progress-bar bg-success" style="width: 88%;"></div>
                            </div>
                            <span class="small fw-semibold">88%</span>
                        </div>
                    </td>
                    <td><span class="ks-badge ks-badge-confirmed">Confirmed</span></td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">
                            View Profile
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="ks-user-avatar" style="width: 34px; height: 34px; font-size: 12px; background: #FEF3C7; color: #B45309; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                SN
                            </div>
                            <div>
                                <div class="fw-semibold text-navy">Sneha Nair</div>
                                <div class="small text-muted">sneha.nair@example.com</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="fw-medium text-navy">ATH-2026-012</span></td>
                    <td>
                        <span class="ks-badge ks-badge-purple">Badminton • Singles</span>
                    </td>
                    <td>Apex Senior</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                <div class="progress-bar bg-warning" style="width: 72%;"></div>
                            </div>
                            <span class="small fw-semibold">72%</span>
                        </div>
                    </td>
                    <td><span class="ks-badge ks-badge-pending">Medical Pending</span></td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">
                            View Profile
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="ks-user-avatar" style="width: 34px; height: 34px; font-size: 12px; background: #F3E8FF; color: var(--ks-purple); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                VK
                            </div>
                            <div>
                                <div class="fw-semibold text-navy">Vikram Kulkarni</div>
                                <div class="small text-muted">vikram.k@example.com</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="fw-medium text-navy">ATH-2026-018</span></td>
                    <td>
                        <span class="ks-badge ks-badge-blue">Athletics • 400m Sprint</span>
                    </td>
                    <td>Phoenix Track Squad</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                <div class="progress-bar bg-success" style="width: 96%;"></div>
                            </div>
                            <span class="small fw-semibold">96%</span>
                        </div>
                    </td>
                    <td><span class="ks-badge ks-badge-confirmed">Confirmed</span></td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">
                            View Profile
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Table Pagination Footer -->
    <div class="p-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ks-border-light) !important;">
        <div class="small text-muted">Showing 1 to 4 of 142 registered athletes</div>
        <div class="d-flex gap-1">
            <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;" disabled>Previous</button>
            <button class="ks-btn ks-btn-primary" style="height: 32px; padding: 0 12px; font-size: 12px;">1</button>
            <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">2</button>
            <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">3</button>
            <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Next</button>
        </div>
    </div>
</div>

<!-- Modal for New Athlete (Section 26) -->
<div class="modal fade" id="newAthleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--ks-radius-modal); border: 1px solid var(--ks-border); box-shadow: var(--ks-shadow-modal);">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold text-navy" style="font-size: 18px;">Register New Athlete</h5>
                    <p class="small text-muted mb-0">Add candidate profile to academy roster</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <form id="athleteForm">
                    <div class="mb-3">
                        <label class="ks-form-label">Full Name *</label>
                        <input type="text" class="ks-form-control" placeholder="e.g. Rahul Patil" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="ks-form-label">Date of Birth</label>
                            <input type="date" class="ks-form-control">
                        </div>
                        <div class="col-6">
                            <label class="ks-form-label">Gender</label>
                            <select class="ks-form-select">
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="ks-form-label">Primary Sport *</label>
                        <select class="ks-form-select" required>
                            <option value="1">Football</option>
                            <option value="2">Cricket</option>
                            <option value="3">Badminton</option>
                            <option value="4">Athletics</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="ks-form-label">Contact Email / Guardian</label>
                        <input type="email" class="ks-form-control" placeholder="parent@example.com">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="ks-btn ks-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="ks-btn ks-btn-primary" onclick="alert('Athlete registration submitted.');" data-bs-dismiss="modal">Save Athlete</button>
            </div>
        </div>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
