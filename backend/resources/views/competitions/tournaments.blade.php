<?php
$activePage = 'tournaments';
$title = 'Tournaments & Fixtures — KhelSutra';

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Tournaments & Fixtures</h1>
        <p class="ks-page-subtitle">Organise championship brackets, match schedules, venue allocations, and official standings.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-secondary" onclick="alert('Exporting Tournament Schedules...');">
            <i class="bi bi-download"></i>
            <span>Export Fixtures</span>
        </button>
        <button class="ks-btn ks-btn-primary" onclick="alert('Open New Tournament Modal');">
            <i class="bi bi-plus-lg"></i>
            <span>+ New Tournament</span>
        </button>
    </div>
</div>

<!-- Tournaments Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="ks-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-icon-box ks-icon-gold" style="width: 44px; height: 44px; border-radius: 12px;">
                        <i class="bi bi-trophy-fill fs-5"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-navy mb-0" style="font-size: 16px;">State Cup 2026</h4>
                        <span class="small text-muted">Football • Knockout Tournament</span>
                    </div>
                </div>
                <span class="ks-badge ks-badge-scheduled">Next in 4 days</span>
            </div>
            <div class="d-flex justify-content-between text-muted small mb-2">
                <span>Teams Registered: <strong class="text-navy">16 Squads</strong></span>
                <span>Matches: <strong class="text-navy">15 Fixtures</strong></span>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-3 border-top" style="border-color: var(--ks-border-light) !important;">
                <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i> Ground A - Main Arena</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">View Brackets</button>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="ks-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-icon-box ks-icon-blue" style="width: 44px; height: 44px; border-radius: 12px;">
                        <i class="bi bi-trophy-fill fs-5"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-navy mb-0" style="font-size: 16px;">District Youth League</h4>
                        <span class="small text-muted">Cricket • Round Robin Group Stage</span>
                    </div>
                </div>
                <span class="ks-badge ks-badge-scheduled">Active League</span>
            </div>
            <div class="d-flex justify-content-between text-muted small mb-2">
                <span>Teams Registered: <strong class="text-navy">12 Squads</strong></span>
                <span>Matches: <strong class="text-navy">30 Fixtures</strong></span>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-3 border-top" style="border-color: var(--ks-border-light) !important;">
                <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i> Court 2 - Turf Ground</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">View Brackets</button>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="ks-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-icon-box ks-icon-green" style="width: 44px; height: 44px; border-radius: 12px;">
                        <i class="bi bi-shield-check fs-5"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-navy mb-0" style="font-size: 16px;">Monsoon Shield</h4>
                        <span class="small text-muted">Athletics & Badminton • Invitational</span>
                    </div>
                </div>
                <span class="ks-badge ks-badge-confirmed">Confirmed</span>
            </div>
            <div class="d-flex justify-content-between text-muted small mb-2">
                <span>Athletes Enrolled: <strong class="text-navy">88 Athletes</strong></span>
                <span>Events: <strong class="text-navy">12 Categories</strong></span>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-3 border-top" style="border-color: var(--ks-border-light) !important;">
                <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i> Olympic Track & Ground</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">View Brackets</button>
            </div>
        </div>
    </div>
</div>

<!-- Master Fixtures Table Card (matches Reference Table) -->
<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-trophy" style="color: var(--ks-gold); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Upcoming Fixtures & Matches</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="position-relative" style="width: 260px;">
                <i class="bi bi-search position-absolute" style="left: 12px; top: 12px; color: var(--ks-text-muted); font-size: 13px;"></i>
                <input type="text" class="ks-form-control" style="padding-left: 34px; height: 38px; font-size: 13px;" placeholder="Filter matches, teams...">
            </div>
            <select class="ks-form-select" style="width: 150px; height: 38px; font-size: 13px;">
                <option value="">All Tournaments</option>
                <option value="state_cup">State Cup 2026</option>
                <option value="youth_league">District Youth League</option>
                <option value="monsoon">Monsoon Shield</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Tournament</th>
                    <th>Teams</th>
                    <th>Venue Facility</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-trophy-fill" style="color: var(--ks-gold);"></i>
                            <span class="fw-semibold text-navy">State Cup 2026</span>
                        </div>
                    </td>
                    <td><span class="fw-medium text-navy">Titans U-18 vs Phoenix FC</span></td>
                    <td>Ground A - Main Arena</td>
                    <td>22 Sep, 15:30</td>
                    <td><span class="ks-badge ks-badge-scheduled">Scheduled</span></td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">Match Center</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-people-fill" style="color: var(--ks-primary);"></i>
                            <span class="fw-semibold text-navy">District Youth League</span>
                        </div>
                    </td>
                    <td><span class="fw-medium text-navy">Strikers U-14 vs St. Jude Academy</span></td>
                    <td>Court 2 - Turf Ground</td>
                    <td>23 Sep, 09:00</td>
                    <td><span class="ks-badge ks-badge-scheduled">Scheduled</span></td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">Match Center</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check" style="color: var(--ks-success);"></i>
                            <span class="fw-semibold text-navy">Monsoon Shield</span>
                        </div>
                    </td>
                    <td><span class="fw-medium text-navy">Apex Senior vs Blue Hawks</span></td>
                    <td>Olympic Track & Ground</td>
                    <td>25 Sep, 16:00</td>
                    <td><span class="ks-badge ks-badge-confirmed">Confirmed</span></td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">Match Center</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-trophy-fill" style="color: var(--ks-gold);"></i>
                            <span class="fw-semibold text-navy">State Cup 2026 (Semi-Final)</span>
                        </div>
                    </td>
                    <td><span class="fw-medium text-navy">TBD vs TBD</span></td>
                    <td>Ground A - Main Arena</td>
                    <td>27 Sep, 17:00</td>
                    <td><span class="ks-badge ks-badge-pending">Pending Draw</span></td>
                    <td style="text-align: right;">
                        <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">Match Center</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Table Footer -->
    <div class="p-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ks-border-light) !important;">
        <div class="small text-muted">Showing 4 active upcoming fixtures</div>
        <div class="d-flex gap-1">
            <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;" disabled>Previous</button>
            <button class="ks-btn ks-btn-primary" style="height: 32px; padding: 0 12px; font-size: 12px;">1</button>
            <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Next</button>
        </div>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
