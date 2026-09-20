<?php
$activePage = 'training';
$title = 'Training & Conditioning — KhelSutra';

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Training & Conditioning</h1>
        <p class="ks-page-subtitle">Plan daily drill schedules, tactical conditioning, coach assignments, and facility slot allocations.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-secondary" onclick="alert('Exporting Attendance Sheet...');">
            <i class="bi bi-download"></i>
            <span>Attendance Sheet</span>
        </button>
        <button class="ks-btn ks-btn-primary" onclick="alert('Schedule Training Session');">
            <i class="bi bi-plus-lg"></i>
            <span>+ Schedule Session</span>
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-red">
                    <i class="bi bi-activity fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Today's Training</div>
                    <div class="ks-kpi-value">4</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">3 Morning, 1 Evening</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 18C20 18 30 24 50 16C70 8 78 4 88 12" stroke="#EF4444" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Athletes Attending</div>
                    <div class="ks-kpi-value">92</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">94.2% check-in rate</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 22C18 20 28 26 44 14C60 2 72 16 88 4" stroke="#0B6EF3" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple">
                    <i class="bi bi-geo-alt-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Courts Booked</div>
                    <div class="ks-kpi-value">6</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Main Arena, Nets & Hall</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 20C16 16 34 8 52 14C70 20 78 12 88 6" stroke="#7C3AED" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green">
                    <i class="bi bi-check-all fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Completed Drills</div>
                    <div class="ks-kpi-value">2</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">Morning batch logged</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 14C22 10 42 18 62 12C74 8 82 14 88 6" stroke="#16A34A" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Sessions & Facility Slots Container (matches Section 41) -->
<div class="ks-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Today's Scheduled Sessions & Facility Slots</span>
        </div>
        <span class="ks-badge ks-badge-live">● Live</span>
    </div>

    <div class="d-flex flex-column gap-3">
        <!-- Session 1 -->
        <div class="ks-session-item">
            <div class="ks-session-left">
                <div class="ks-session-icon" style="color: #0B6EF3;">
                    <i class="bi bi-dribbble"></i>
                </div>
                <div>
                    <div class="ks-session-name">Football Drill & Conditioning</div>
                    <div class="ks-session-meta">Coach Rajesh • Ground A - Main Arena • 22 Athletes</div>
                </div>
            </div>
            <div class="ks-session-right">
                <span class="ks-time-pill ks-time-green">06:30 - 08:30</span>
                <span class="ks-badge ks-badge-confirmed">Completed</span>
                <i class="bi bi-chevron-right text-muted" style="font-size: 13px;"></i>
            </div>
        </div>

        <!-- Session 2 -->
        <div class="ks-session-item">
            <div class="ks-session-left">
                <div class="ks-session-icon" style="color: #D97706;">
                    <i class="bi bi-bullseye"></i>
                </div>
                <div>
                    <div class="ks-session-name">Cricket Net Practice & Bowling Drills</div>
                    <div class="ks-session-meta">Coach Amit • Nets 1 & 2 • 18 Athletes</div>
                </div>
            </div>
            <div class="ks-session-right">
                <span class="ks-time-pill ks-time-blue">16:00 - 18:00</span>
                <span class="ks-badge ks-badge-scheduled">Scheduled</span>
                <i class="bi bi-chevron-right text-muted" style="font-size: 13px;"></i>
            </div>
        </div>

        <!-- Session 3 -->
        <div class="ks-session-item">
            <div class="ks-session-left">
                <div class="ks-session-icon" style="color: #7C3AED;">
                    <i class="bi bi-lightning-charge"></i>
                </div>
                <div>
                    <div class="ks-session-name">Badminton Agility & Footwork</div>
                    <div class="ks-session-meta">Coach Priya • Indoor Court 1 • 12 Athletes</div>
                </div>
            </div>
            <div class="ks-session-right">
                <span class="ks-time-pill ks-time-purple">17:30 - 19:30</span>
                <span class="ks-badge ks-badge-scheduled">Scheduled</span>
                <i class="bi bi-chevron-right text-muted" style="font-size: 13px;"></i>
            </div>
        </div>

        <!-- Session 4 -->
        <div class="ks-session-item">
            <div class="ks-session-left">
                <div class="ks-session-icon" style="color: #06B6D4;">
                    <i class="bi bi-speedometer2"></i>
                </div>
                <div>
                    <div class="ks-session-name">Sprint Intervals & Lactate Clearance</div>
                    <div class="ks-session-meta">Coach Sarah • Synthetic Track • 14 Athletes</div>
                </div>
            </div>
            <div class="ks-session-right">
                <span class="ks-time-pill ks-time-blue">18:00 - 19:30</span>
                <span class="ks-badge ks-badge-scheduled">Scheduled</span>
                <i class="bi bi-chevron-right text-muted" style="font-size: 13px;"></i>
            </div>
        </div>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
