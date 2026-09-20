<?php
$activePage = 'venues';
$title = 'Venues & Facility Bookings — KhelSutra';

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Venues & Bookings</h1>
        <p class="ks-page-subtitle">Manage academy grounds, court reservations, floodlight slots, and maintenance status.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-secondary" onclick="alert('Viewing Maintenance Log...');">
            <i class="bi bi-tools"></i>
            <span>Maintenance Log</span>
        </button>
        <button class="ks-btn ks-btn-primary" onclick="alert('Reserve Facility Slot');">
            <i class="bi bi-plus-lg"></i>
            <span>+ Book Facility</span>
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple">
                    <i class="bi bi-building-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Venue Bookings</div>
                    <div class="ks-kpi-value">6</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Courts & grounds reserved today</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 18C20 18 30 24 50 16C70 8 78 4 88 12" stroke="#7C3AED" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-geo-alt-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Total Facilities</div>
                    <div class="ks-kpi-value">5</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">All operational</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 22C18 20 28 26 44 14C60 2 72 16 88 4" stroke="#0B6EF3" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green">
                    <i class="bi bi-lightning-charge-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Floodlight Readiness</div>
                    <div class="ks-kpi-value">100%</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Night sessions approved</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 14C22 10 42 18 62 12C74 8 82 14 88 6" stroke="#16A34A" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber">
                    <i class="bi bi-clock-history fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Available Slots</div>
                    <div class="ks-kpi-value">8</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-warning">Evening slots filling fast</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 20C16 16 34 8 52 14C70 20 78 12 88 6" stroke="#F59E0B" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Facilities Grid -->
<div class="row g-3">
    <!-- Facility 1 -->
    <div class="col-lg-6">
        <div class="ks-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-icon-box ks-icon-green" style="width: 46px; height: 46px; border-radius: 12px;">
                        <i class="bi bi-bounding-box fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold text-navy mb-0" style="font-size: 16px;">Ground A - Main Arena</h3>
                        <span class="small text-muted">Natural Grass Football Turf • 2,500 Seating</span>
                    </div>
                </div>
                <span class="ks-badge ks-badge-confirmed">Operational</span>
            </div>
            <div class="small text-muted mb-3">
                <div><i class="bi bi-check2-circle text-success me-1"></i> FIFA Certified surface • Floodlit</div>
                <div class="mt-1"><i class="bi bi-calendar-event me-1"></i> <strong>Current Allocation:</strong> State Cup Match (22 Sep, 15:30)</div>
            </div>
            <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ks-border-light) !important;">
                <span class="small fw-semibold text-navy">4 Bookings Today</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Slot Schedule</button>
            </div>
        </div>
    </div>

    <!-- Facility 2 -->
    <div class="col-lg-6">
        <div class="ks-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-icon-box ks-icon-blue" style="width: 46px; height: 46px; border-radius: 12px;">
                        <i class="bi bi-grid-3x3-gap-fill fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold text-navy mb-0" style="font-size: 16px;">Court 2 - Turf Ground & Nets</h3>
                        <span class="small text-muted">Synthetic Turf Cricket Pitch & 4 Practice Nets</span>
                    </div>
                </div>
                <span class="ks-badge ks-badge-confirmed">Operational</span>
            </div>
            <div class="small text-muted mb-3">
                <div><i class="bi bi-check2-circle text-success me-1"></i> Bowling Machine Installed • Floodlit</div>
                <div class="mt-1"><i class="bi bi-calendar-event me-1"></i> <strong>Current Allocation:</strong> Net Practice & Youth League</div>
            </div>
            <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ks-border-light) !important;">
                <span class="small fw-semibold text-navy">3 Bookings Today</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Slot Schedule</button>
            </div>
        </div>
    </div>

    <!-- Facility 3 -->
    <div class="col-lg-6">
        <div class="ks-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-icon-box ks-icon-purple" style="width: 46px; height: 46px; border-radius: 12px;">
                        <i class="bi bi-circle-square fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold text-navy mb-0" style="font-size: 16px;">Indoor Badminton Complex</h3>
                        <span class="small text-muted">4 Wooden Synthetic Courts • Climate Controlled</span>
                    </div>
                </div>
                <span class="ks-badge ks-badge-confirmed">Operational</span>
            </div>
            <div class="small text-muted mb-3">
                <div><i class="bi bi-check2-circle text-success me-1"></i> BWF Standard Matting • LED Anti-Glare</div>
                <div class="mt-1"><i class="bi bi-calendar-event me-1"></i> <strong>Current Allocation:</strong> Elite Squad Agility Drills</div>
            </div>
            <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ks-border-light) !important;">
                <span class="small fw-semibold text-navy">2 Courts Reserved</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Slot Schedule</button>
            </div>
        </div>
    </div>

    <!-- Facility 4 -->
    <div class="col-lg-6">
        <div class="ks-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-icon-box ks-icon-cyan" style="width: 46px; height: 46px; border-radius: 12px;">
                        <i class="bi bi-stopwatch fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold text-navy mb-0" style="font-size: 16px;">Olympic Track & Field Ground</h3>
                        <span class="small text-muted">8-Lane Synthetic Track & Long Jump Pit</span>
                    </div>
                </div>
                <span class="ks-badge ks-badge-cyan">Maintenance Check</span>
            </div>
            <div class="small text-muted mb-3">
                <div><i class="bi bi-tools text-warning me-1"></i> Line marking inspection scheduled 13:00 - 14:30</div>
                <div class="mt-1"><i class="bi bi-calendar-event me-1"></i> <strong>Next Fixture:</strong> Monsoon Shield (25 Sep)</div>
            </div>
            <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ks-border-light) !important;">
                <span class="small fw-semibold text-navy">Opens 15:00</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">Slot Schedule</button>
            </div>
        </div>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
