<?php
$activePage = 'teams';
$title = 'Teams & Squads — KhelSutra';

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Teams & Squads</h1>
        <p class="ks-page-subtitle">Rosters, age-group classifications, assigned coaches, and tournament rosters.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-secondary" onclick="alert('Exporting Squad Lists...');">
            <i class="bi bi-download"></i>
            <span>Export Rosters</span>
        </button>
        <button class="ks-btn ks-btn-primary" onclick="alert('Open Create Team Dialog');">
            <i class="bi bi-plus-lg"></i>
            <span>+ Create Team</span>
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green">
                    <i class="bi bi-shield-shaded fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Total Teams</div>
                    <div class="ks-kpi-value">8</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">U-14, U-16, U-18 & Senior</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 20C22 18 36 26 52 14C68 2 76 12 88 4" stroke="#16A34A" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-calendar2-check-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Upcoming Matches</div>
                    <div class="ks-kpi-value">5</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">Scheduled this week</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 18C20 18 30 24 50 16C70 8 78 4 88 12" stroke="#0B6EF3" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-gold">
                    <i class="bi bi-trophy-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Active Competitions</div>
                    <div class="ks-kpi-value">3</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">State Cup & District League</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 22C18 20 38 24 58 10C70 4 78 8 88 2" stroke="#F4B000" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Athletes Rostered</div>
                    <div class="ks-kpi-value">114</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">80.3% squad utilization</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 16C18 12 36 22 54 14C72 6 80 16 88 8" stroke="#7C3AED" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Teams Cards Grid -->
<div class="row g-3">
    <!-- Team 1 -->
    <div class="col-lg-6">
        <div class="ks-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-icon-box ks-icon-blue" style="width: 48px; height: 48px; border-radius: 12px;">
                        <i class="bi bi-shield-fill fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold text-navy mb-0" style="font-size: 17px;">Titans U-18</h3>
                        <span class="small text-muted">Football • Under-18 Championship Squad</span>
                    </div>
                </div>
                <span class="ks-badge ks-badge-confirmed">Active Squad</span>
            </div>
            <div class="row g-2 mb-3 py-2 border-top border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="col-4">
                    <div class="text-muted small">Players</div>
                    <div class="fw-bold text-navy">22 Athletes</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small">Head Coach</div>
                    <div class="fw-bold text-navy">Coach Rajesh</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small">Next Match</div>
                    <div class="fw-bold text-primary">22 Sep, 15:30</div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i> Ground A - Main Arena</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Manage Roster</button>
            </div>
        </div>
    </div>

    <!-- Team 2 -->
    <div class="col-lg-6">
        <div class="ks-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-icon-box ks-icon-green" style="width: 48px; height: 48px; border-radius: 12px;">
                        <i class="bi bi-shield-shaded fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold text-navy mb-0" style="font-size: 17px;">Strikers U-14</h3>
                        <span class="small text-muted">Cricket • Junior Development Squad</span>
                    </div>
                </div>
                <span class="ks-badge ks-badge-confirmed">Active Squad</span>
            </div>
            <div class="row g-2 mb-3 py-2 border-top border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="col-4">
                    <div class="text-muted small">Players</div>
                    <div class="fw-bold text-navy">18 Athletes</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small">Head Coach</div>
                    <div class="fw-bold text-navy">Coach Amit</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small">Next Match</div>
                    <div class="fw-bold text-primary">23 Sep, 09:00</div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i> Court 2 - Turf Ground</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Manage Roster</button>
            </div>
        </div>
    </div>

    <!-- Team 3 -->
    <div class="col-lg-6">
        <div class="ks-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-icon-box ks-icon-purple" style="width: 48px; height: 48px; border-radius: 12px;">
                        <i class="bi bi-shield-check fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold text-navy mb-0" style="font-size: 17px;">Apex Senior</h3>
                        <span class="small text-muted">Badminton & Multi-Sport • Senior Elite</span>
                    </div>
                </div>
                <span class="ks-badge ks-badge-confirmed">Active Squad</span>
            </div>
            <div class="row g-2 mb-3 py-2 border-top border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="col-4">
                    <div class="text-muted small">Players</div>
                    <div class="fw-bold text-navy">16 Athletes</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small">Head Coach</div>
                    <div class="fw-bold text-navy">Coach Priya</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small">Next Match</div>
                    <div class="fw-bold text-primary">25 Sep, 16:00</div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i> Olympic Track & Ground</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Manage Roster</button>
            </div>
        </div>
    </div>

    <!-- Team 4 -->
    <div class="col-lg-6">
        <div class="ks-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-icon-box ks-icon-cyan" style="width: 48px; height: 48px; border-radius: 12px;">
                        <i class="bi bi-shield fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold text-navy mb-0" style="font-size: 17px;">Phoenix Track Squad</h3>
                        <span class="small text-muted">Athletics • Sprint & Endurance</span>
                    </div>
                </div>
                <span class="ks-badge ks-badge-cyan">Training Phase</span>
            </div>
            <div class="row g-2 mb-3 py-2 border-top border-bottom" style="border-color: var(--ks-border-light) !important;">
                <div class="col-4">
                    <div class="text-muted small">Athletes</div>
                    <div class="fw-bold text-navy">14 Runners</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small">Lead Coach</div>
                    <div class="fw-bold text-navy">Coach Sarah</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small">Trial Date</div>
                    <div class="fw-bold text-primary">28 Sep, 07:00</div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i> Synthetic Track A</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Manage Roster</button>
            </div>
        </div>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
