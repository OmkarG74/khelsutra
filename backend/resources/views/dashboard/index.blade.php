<?php
$activePage = 'dashboard';
$title = 'Sports Operations Dashboard — KhelSutra';

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Sports Operations Dashboard</h1>
        <p class="ks-page-subtitle">Overview of active athletes, coaching sessions, venues, and fixtures</p>
    </div>
    <div class="ks-header-actions">
        <!-- Date Indicator Widget -->
        <div class="ks-date-widget">
            <i class="bi bi-calendar-check fs-5"></i>
            <div>
                <div class="ks-date-text">Tuesday, 23 Sep 2026</div>
                <div class="ks-time-text">10:24 AM</div>
            </div>
        </div>

        <!-- Secondary Button -->
        <button class="ks-btn ks-btn-secondary" onclick="alert('Exporting Sports Operations Summary...');">
            <i class="bi bi-download"></i>
            <span>Export Report</span>
        </button>

        <!-- Primary Button -->
        <a href="/athletes?action=new" class="ks-btn ks-btn-primary">
            <i class="bi bi-plus-lg"></i>
            <span>+ New Registration</span>
        </a>
    </div>
</div>

<!-- Primary KPI Row (4 Cards matching Reference Screenshot) -->
<div class="row g-3 mb-3">
    <!-- Card 1: Total Athletes -->
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
                <!-- Green Sparkline SVG -->
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 22C18 20 28 26 44 14C60 2 72 16 88 4" stroke="#16A34A" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Coaches -->
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple">
                    <i class="bi bi-person-badge-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Total Coaches</div>
                    <div class="ks-kpi-value">12</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Active across 6 sports</span>
                <!-- Purple Sparkline SVG -->
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 18C20 18 30 24 50 16C70 8 78 4 88 12" stroke="#7C3AED" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Card 3: Total Teams -->
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
                <!-- Green Sparkline SVG -->
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 24C16 22 34 26 52 14C68 4 76 10 88 4" stroke="#16A34A" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Card 4: Upcoming Tournaments -->
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber">
                    <i class="bi bi-trophy-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Upcoming Tournaments</div>
                    <div class="ks-kpi-value">3</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text" style="color: #D97706; font-weight: 600;">
                    <i class="bi bi-calendar3 me-1"></i> Next in 4 days
                </span>
                <!-- Amber Sparkline SVG -->
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 20C18 20 32 25 50 18C68 11 74 16 88 6" stroke="#F59E0B" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Secondary KPI Row (5 Compact Cards matching Reference Screenshot) -->
<div class="row g-3 mb-4">
    <!-- Compact 1: Upcoming Matches -->
    <div class="col-xl col-md-4 col-sm-6">
        <a href="/tournaments#fixtures" class="ks-compact-card">
            <div class="ks-compact-left">
                <div class="ks-compact-icon ks-icon-cyan">
                    <i class="bi bi-calendar-event fs-5"></i>
                </div>
                <div>
                    <div class="ks-compact-title">Upcoming Matches</div>
                    <div class="ks-compact-value">5</div>
                    <div class="ks-compact-sub">Scheduled this week</div>
                </div>
            </div>
            <i class="bi bi-chevron-right ks-compact-chevron" style="color: #06B6D4;"></i>
        </a>
    </div>

    <!-- Compact 2: Today's Training -->
    <div class="col-xl col-md-4 col-sm-6">
        <a href="/training" class="ks-compact-card">
            <div class="ks-compact-left">
                <div class="ks-compact-icon ks-icon-red">
                    <i class="bi bi-arrows-expand fs-5"></i>
                </div>
                <div>
                    <div class="ks-compact-title">Today's Training</div>
                    <div class="ks-compact-value">4</div>
                    <div class="ks-compact-sub" style="color: var(--ks-primary); font-weight: 600;">3 Morning, 1 Evening</div>
                </div>
            </div>
            <i class="bi bi-chevron-right ks-compact-chevron" style="color: #6366F1;"></i>
        </a>
    </div>

    <!-- Compact 3: Venue Bookings -->
    <div class="col-xl col-md-4 col-sm-6">
        <a href="/venues" class="ks-compact-card">
            <div class="ks-compact-left">
                <div class="ks-compact-icon ks-icon-purple">
                    <i class="bi bi-building fs-5"></i>
                </div>
                <div>
                    <div class="ks-compact-title">Venue Bookings</div>
                    <div class="ks-compact-value">6</div>
                    <div class="ks-compact-sub">Courts & grounds</div>
                </div>
            </div>
            <i class="bi bi-chevron-right ks-compact-chevron" style="color: #7C3AED;"></i>
        </a>
    </div>

    <!-- Compact 4: Pending Leave -->
    <div class="col-xl col-md-6 col-sm-6">
        <a href="/hr-finance#leave" class="ks-compact-card">
            <div class="ks-compact-left">
                <div class="ks-compact-icon ks-icon-red">
                    <i class="bi bi-file-earmark-text fs-5"></i>
                </div>
                <div>
                    <div class="ks-compact-title">Pending Leave</div>
                    <div class="ks-compact-value" style="color: var(--ks-danger);">2</div>
                    <div class="ks-compact-sub">Awaiting approval</div>
                </div>
            </div>
            <i class="bi bi-chevron-right ks-compact-chevron" style="color: var(--ks-danger);"></i>
        </a>
    </div>

    <!-- Compact 5: Low Inventory -->
    <div class="col-xl col-md-6 col-sm-12">
        <a href="/inventory" class="ks-compact-card">
            <div class="ks-compact-left">
                <div class="ks-compact-icon ks-icon-amber">
                    <i class="bi bi-box-seam fs-5"></i>
                </div>
                <div>
                    <div class="ks-compact-title">Low Inventory</div>
                    <div class="ks-compact-value" style="color: var(--ks-warning);">3</div>
                    <div class="ks-compact-sub">Items below threshold</div>
                </div>
            </div>
            <i class="bi bi-chevron-right ks-compact-chevron" style="color: var(--ks-warning);"></i>
        </a>
    </div>
</div>

<!-- Main Operations Grid (Tables & Live Sessions) -->
<div class="row g-4 mb-4">
    <!-- Left Column: Upcoming Fixtures & Matches Table -->
    <div class="col-lg-7">
        <div class="ks-content-card h-100">
            <div class="ks-card-header">
                <div class="ks-header-left">
                    <i class="bi bi-trophy-fill" style="color: var(--ks-gold); font-size: 18px;"></i>
                    <h3 class="ks-header-title">Upcoming Fixtures & Matches</h3>
                </div>
                <a href="/tournaments#fixtures" class="ks-header-link">
                    <span>View All</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="ks-table-responsive">
                <table class="ks-table">
                    <thead>
                        <tr>
                            <th>Tournament</th>
                            <th>Teams</th>
                            <th>Venue Facility</th>
                            <th>Schedule</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-trophy-fill" style="color: var(--ks-gold);"></i>
                                    <span class="fw-semibold">State Cup 2026</span>
                                </div>
                            </td>
                            <td class="text-secondary fw-medium">Titans U-18 vs Phoenix FC</td>
                            <td class="text-secondary">Ground A - Main Arena</td>
                            <td class="text-secondary">22 Sep, 15:30</td>
                            <td>
                                <span class="ks-badge ks-badge-scheduled">Scheduled</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-people-fill text-primary"></i>
                                    <span class="fw-semibold">District Youth League</span>
                                </div>
                            </td>
                            <td class="text-secondary fw-medium">Strikers U-14 vs St. Jude Academy</td>
                            <td class="text-secondary">Court 2 - Turf Ground</td>
                            <td class="text-secondary">23 Sep, 09:00</td>
                            <td>
                                <span class="ks-badge ks-badge-scheduled">Scheduled</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-shield-fill-check text-primary"></i>
                                    <span class="fw-semibold">Monsoon Shield</span>
                                </div>
                            </td>
                            <td class="text-secondary fw-medium">Apex Senior vs Blue Hawks</td>
                            <td class="text-secondary">Olympic Track & Ground</td>
                            <td class="text-secondary">25 Sep, 16:00</td>
                            <td>
                                <span class="ks-badge ks-badge-confirmed">Confirmed</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Today's Sessions & Facility Slots -->
    <div class="col-lg-5">
        <div class="ks-content-card h-100">
            <div class="ks-card-header">
                <div class="ks-header-left">
                    <i class="bi bi-clock-history text-primary" style="font-size: 18px;"></i>
                    <h3 class="ks-header-title">Today's Sessions & Facility Slots</h3>
                </div>
                <span class="ks-badge ks-badge-live">
                    <span class="ks-dot-live me-1"></span> Live
                </span>
            </div>
            <div class="p-3">
                <!-- Session 1 -->
                <div class="ks-session-item">
                    <div class="ks-session-left">
                        <div class="ks-session-icon">
                            <i class="bi bi-bullseye"></i>
                        </div>
                        <div>
                            <div class="ks-session-name">Football Drill & Conditioning</div>
                            <div class="ks-session-meta">Coach Rajesh • Ground A</div>
                        </div>
                    </div>
                    <div class="ks-session-right">
                        <span class="ks-time-pill ks-time-green">06:30 - 08:30</span>
                        <i class="bi bi-chevron-right text-muted" style="font-size: 13px;"></i>
                    </div>
                </div>

                <!-- Session 2 -->
                <div class="ks-session-item">
                    <div class="ks-session-left">
                        <div class="ks-session-icon" style="color: #D97706;">
                            <i class="bi bi-activity"></i>
                        </div>
                        <div>
                            <div class="ks-session-name">Cricket Net Practice & Bowling</div>
                            <div class="ks-session-meta">Coach Amit • Nets 1 & 2</div>
                        </div>
                    </div>
                    <div class="ks-session-right">
                        <span class="ks-time-pill ks-time-blue">16:00 - 18:00</span>
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
                            <div class="ks-session-meta">Coach Priya • Indoor Court 1</div>
                        </div>
                    </div>
                    <div class="ks-session-right">
                        <span class="ks-time-pill ks-time-purple">17:30 - 19:30</span>
                        <i class="bi bi-chevron-right text-muted" style="font-size: 13px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Decorative Bottom Promotional Banner (Section 31) -->
<?php include __DIR__ . '/../components/promo-banner.blade.php'; ?>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
