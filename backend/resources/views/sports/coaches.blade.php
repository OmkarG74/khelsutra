<?php
$activePage = 'coaches';
$title = 'Coaching Staff — KhelSutra';

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Coaching Staff</h1>
        <p class="ks-page-subtitle">Manage certified coaching personnel, specializations, squad assignments, and credentials.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-secondary" onclick="alert('Exporting Coaches Directory...');">
            <i class="bi bi-download"></i>
            <span>Export Roster</span>
        </button>
        <button class="ks-btn ks-btn-primary" onclick="alert('Open Add Coach Modal');">
            <i class="bi bi-plus-lg"></i>
            <span>+ Add Coach</span>
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
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
                <span class="ks-trend-text">Across 6 academy disciplines</span>
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
                    <i class="bi bi-stopwatch-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Active Sessions Today</div>
                    <div class="ks-kpi-value">4</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">3 Morning, 1 Evening</span>
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
                    <i class="bi bi-patch-check-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Certifications Verified</div>
                    <div class="ks-kpi-value">100%</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">All AIFF/BCCI/BWF verified</span>
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
                    <i class="bi bi-star-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Avg Rating</div>
                    <div class="ks-kpi-value">4.9 / 5</div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Based on 184 athlete reviews</span>
                <svg class="ks-sparkline" viewBox="0 0 90 28" fill="none">
                    <path d="M2 20C16 16 34 8 52 14C70 20 78 12 88 6" stroke="#F59E0B" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Coaches Grid & Directory -->
<div class="row g-3">
    <!-- Coach 1 -->
    <div class="col-lg-4 col-md-6">
        <div class="ks-card p-4 h-100">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-user-avatar" style="width: 48px; height: 48px; background: #E8F2FF; color: var(--ks-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px;">
                        CR
                    </div>
                    <div>
                        <h3 class="fw-bold text-navy mb-0" style="font-size: 16px;">Coach Rajesh Kumar</h3>
                        <div class="small text-muted">AIFF Pro License • 11 Yrs Exp</div>
                    </div>
                </div>
                <span class="ks-badge ks-badge-confirmed">Active</span>
            </div>
            <div class="mb-3">
                <span class="ks-badge ks-badge-blue me-1">Football</span>
                <span class="ks-badge ks-badge-cyan">Tactical Conditioning</span>
            </div>
            <div class="small text-muted mb-3">
                <div><i class="bi bi-shield me-2"></i><strong>Assigned Squad:</strong> Titans U-18</div>
                <div class="mt-1"><i class="bi bi-geo-alt me-2"></i><strong>Facility:</strong> Ground A - Main Arena</div>
            </div>
            <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ks-border-light) !important;">
                <span class="small fw-semibold text-navy"><i class="bi bi-star-fill text-warning me-1"></i> 4.95 Rating</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">View Profile</button>
            </div>
        </div>
    </div>

    <!-- Coach 2 -->
    <div class="col-lg-4 col-md-6">
        <div class="ks-card p-4 h-100">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-user-avatar" style="width: 48px; height: 48px; background: #DCFCE7; color: var(--ks-success); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px;">
                        CA
                    </div>
                    <div>
                        <h3 class="fw-bold text-navy mb-0" style="font-size: 16px;">Coach Amit Deshmukh</h3>
                        <div class="small text-muted">BCCI Level 3 Certified • 9 Yrs Exp</div>
                    </div>
                </div>
                <span class="ks-badge ks-badge-confirmed">Active</span>
            </div>
            <div class="mb-3">
                <span class="ks-badge ks-badge-cyan me-1">Cricket</span>
                <span class="ks-badge ks-badge-blue">Bowling & Nets</span>
            </div>
            <div class="small text-muted mb-3">
                <div><i class="bi bi-shield me-2"></i><strong>Assigned Squad:</strong> Strikers U-14</div>
                <div class="mt-1"><i class="bi bi-geo-alt me-2"></i><strong>Facility:</strong> Court 2 - Turf Ground / Nets 1 & 2</div>
            </div>
            <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ks-border-light) !important;">
                <span class="small fw-semibold text-navy"><i class="bi bi-star-fill text-warning me-1"></i> 4.90 Rating</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">View Profile</button>
            </div>
        </div>
    </div>

    <!-- Coach 3 -->
    <div class="col-lg-4 col-md-6">
        <div class="ks-card p-4 h-100">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="ks-user-avatar" style="width: 48px; height: 48px; background: #F3E8FF; color: var(--ks-purple); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px;">
                        CP
                    </div>
                    <div>
                        <h3 class="fw-bold text-navy mb-0" style="font-size: 16px;">Coach Priya Menon</h3>
                        <div class="small text-muted">BWF Level 2 High Performance • 7 Yrs Exp</div>
                    </div>
                </div>
                <span class="ks-badge ks-badge-confirmed">Active</span>
            </div>
            <div class="mb-3">
                <span class="ks-badge ks-badge-purple me-1">Badminton</span>
                <span class="ks-badge ks-badge-blue">Agility & Footwork</span>
            </div>
            <div class="small text-muted mb-3">
                <div><i class="bi bi-shield me-2"></i><strong>Assigned Squad:</strong> Apex Senior Squad</div>
                <div class="mt-1"><i class="bi bi-geo-alt me-2"></i><strong>Facility:</strong> Indoor Badminton Court 1</div>
            </div>
            <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--ks-border-light) !important;">
                <span class="small fw-semibold text-navy"><i class="bi bi-star-fill text-warning me-1"></i> 4.98 Rating</span>
                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">View Profile</button>
            </div>
        </div>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
