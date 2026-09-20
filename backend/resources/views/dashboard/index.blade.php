<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — KhelSutra Sports ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --ks-primary: #1e3a8a;
            --ks-accent: #f97316;
            --ks-sidebar-width: 260px;
        }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .sidebar {
            width: var(--ks-sidebar-width);
            background-color: #0f172a;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        .sidebar .brand {
            padding: 1.25rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .nav-link {
            color: #94a3b8;
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #ffffff;
            background-color: rgba(255,255,255,0.08);
            border-left: 3px solid var(--ks-accent);
        }
        .main-wrapper {
            margin-left: var(--ks-sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .top-navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.85rem 1.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .content-body {
            padding: 1.75rem;
            flex-grow: 1;
        }
        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1.25rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }
        .card-table {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../components/sidebar.blade.php'; ?>

    <div class="main-wrapper">
        <!-- Top Navbar -->
        <?php include __DIR__ . '/../components/navbar.blade.php'; ?>

        <!-- Dashboard Content -->
        <main class="content-body">
            <!-- Breadcrumbs & Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-0">Sports Operations Dashboard</h4>
                    <p class="text-muted small mb-0">Overview of active athletes, coaching sessions, venues, and fixtures</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i> Export Report</button>
                    <button class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> New Registration</button>
                </div>
            </div>

            <!-- Operational Metrics Cards (Section 18) -->
            <div class="row g-3 mb-4">
                <!-- Total Athletes -->
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold">Total Athletes</span>
                                <h3 class="fw-bold my-1">142</h3>
                                <span class="text-success small fw-medium"><i class="bi bi-arrow-up"></i> +12 this month</span>
                            </div>
                            <span class="p-2 bg-primary-subtle text-primary rounded"><i class="bi bi-person-walking fs-5"></i></span>
                        </div>
                    </div>
                </div>

                <!-- Total Coaches -->
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold">Total Coaches</span>
                                <h3 class="fw-bold my-1">12</h3>
                                <span class="text-muted small">Active across 6 sports</span>
                            </div>
                            <span class="p-2 bg-info-subtle text-info rounded"><i class="bi bi-person-badge fs-5"></i></span>
                        </div>
                    </div>
                </div>

                <!-- Total Teams -->
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold">Total Teams</span>
                                <h3 class="fw-bold my-1">8</h3>
                                <span class="text-muted small">U-14, U-16, U-18 & Senior</span>
                            </div>
                            <span class="p-2 bg-success-subtle text-success rounded"><i class="bi bi-people-fill fs-5"></i></span>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Tournaments -->
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold">Upcoming Tournaments</span>
                                <h3 class="fw-bold my-1">3</h3>
                                <span class="text-warning small fw-medium"><i class="bi bi-calendar-event"></i> Next in 4 days</span>
                            </div>
                            <span class="p-2 bg-warning-subtle text-warning rounded"><i class="bi bi-award-fill fs-5"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Metrics Row -->
            <div class="row g-3 mb-4">
                <!-- Upcoming Matches -->
                <div class="col-md-3">
                    <div class="stat-card">
                        <span class="text-muted small text-uppercase fw-semibold">Upcoming Matches</span>
                        <h4 class="fw-bold my-1">5</h4>
                        <span class="text-muted small">Scheduled this week</span>
                    </div>
                </div>
                <!-- Today's Training -->
                <div class="col-md-3">
                    <div class="stat-card">
                        <span class="text-muted small text-uppercase fw-semibold">Today's Training</span>
                        <h4 class="fw-bold my-1">4</h4>
                        <span class="text-primary small">3 Morning, 1 Evening</span>
                    </div>
                </div>
                <!-- Venue Bookings -->
                <div class="col-md-2">
                    <div class="stat-card">
                        <span class="text-muted small text-uppercase fw-semibold">Venue Bookings</span>
                        <h4 class="fw-bold my-1">6</h4>
                        <span class="text-muted small">Courts & grounds</span>
                    </div>
                </div>
                <!-- Pending Leave -->
                <div class="col-md-2">
                    <div class="stat-card">
                        <span class="text-muted small text-uppercase fw-semibold">Pending Leave</span>
                        <h4 class="fw-bold my-1 text-danger">2</h4>
                        <span class="text-muted small">Awaiting approval</span>
                    </div>
                </div>
                <!-- Low Inventory & Approvals -->
                <div class="col-md-2">
                    <div class="stat-card">
                        <span class="text-muted small text-uppercase fw-semibold">Low Inventory</span>
                        <h4 class="fw-bold my-1 text-warning">3</h4>
                        <span class="text-muted small">Items below threshold</span>
                    </div>
                </div>
            </div>

            <!-- Tables Row -->
            <div class="row g-3">
                <!-- Upcoming Fixtures & Matches -->
                <div class="col-lg-7">
                    <div class="card-table">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Upcoming Fixtures & Matches</h6>
                            <a href="#fixtures" class="small text-decoration-none">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Tournament</th>
                                        <th>Teams</th>
                                        <th>Venue Facility</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    <tr>
                                        <td class="fw-semibold">State Cup 2026</td>
                                        <td>Titans U-18 vs Phoenix FC</td>
                                        <td>Ground A - Main Arena</td>
                                        <td>22 Sep, 15:30</td>
                                        <td><span class="badge bg-primary-subtle text-primary">Scheduled</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">District Youth League</td>
                                        <td>Strikers U-14 vs St. Jude Academy</td>
                                        <td>Court 2 - Turf Ground</td>
                                        <td>23 Sep, 09:00</td>
                                        <td><span class="badge bg-primary-subtle text-primary">Scheduled</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Monsoon Shield</td>
                                        <td>Apex Senior vs Blue Hawks</td>
                                        <td>Olympic Track & Ground</td>
                                        <td>25 Sep, 16:00</td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">Confirmed</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Today's Training & Bookings -->
                <div class="col-lg-5">
                    <div class="card-table">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Today's Sessions & Facility Slots</h6>
                            <span class="badge bg-success-subtle text-success">Live</span>
                        </div>
                        <div class="p-3">
                            <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded bg-light border">
                                <div>
                                    <div class="fw-semibold small">Football Drill & Conditioning</div>
                                    <div class="text-muted small">Coach Rajesh • Ground A</div>
                                </div>
                                <span class="badge bg-success">06:30 - 08:30</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded bg-light border">
                                <div>
                                    <div class="fw-semibold small">Cricket Net Practice & Bowling</div>
                                    <div class="text-muted small">Coach Amit • Nets 1 & 2</div>
                                </div>
                                <span class="badge bg-primary">16:00 - 18:00</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light border">
                                <div>
                                    <div class="fw-semibold small">Badminton Agility & Footwork</div>
                                    <div class="text-muted small">Coach Priya • Indoor Court 1</div>
                                </div>
                                <span class="badge bg-primary">17:30 - 19:30</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
