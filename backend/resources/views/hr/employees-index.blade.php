<?php
$activePage = 'hr-finance';
$title = 'Employee Directory — KhelSutra HR';

$empService = new \App\Services\Staff\EmployeeService();
$deptService = new \App\Services\Staff\DepartmentService();
$catService = new \App\Services\Staff\EmployeeCategoryService();

$orgId = $_SESSION['current_organization_id'] ?? 1;

$departments = $deptService->listDepartments($orgId);
$categories = $catService->listCategories($orgId);

// Query filters
$filters = [
    'search' => $_GET['search'] ?? null,
    'department_id' => !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null,
    'employee_category_id' => !empty($_GET['employee_category_id']) ? (int)$_GET['employee_category_id'] : null,
    'employment_status' => $_GET['employment_status'] ?? null,
];

$employees = $empService->listEmployees($orgId, $filters);

ob_start();
?>

<!-- Page Header (Section 24, 45 & 47) -->
<div class="ks-page-header">
    <h1 class="ks-page-title">Employees & Staff</h1>
    <div class="ks-header-actions">
        <a href="/hr/departments" class="ks-btn ks-btn-secondary">
            <i class="bi bi-diagram-3"></i>
            <span>Departments</span>
        </a>
        <a href="/hr/employee-categories" class="ks-btn ks-btn-secondary">
            <i class="bi bi-tags"></i>
            <span>Categories</span>
        </a>
        <a href="/hr/employees/create" class="ks-btn ks-btn-primary">
            <i class="bi bi-person-plus-fill"></i>
            <span>+ Add Employee</span>
        </a>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Total Roster</div>
                    <div class="ks-kpi-value"><?= count($employees) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">Active in Organisation</span>
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
                    <div class="ks-kpi-label">Active Staff</div>
                    <div class="ks-kpi-value"><?= count(array_filter($employees, fn($e) => ($e['employment_status'] ?? '') === 'active')) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-success">On Duty</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple">
                    <i class="bi bi-whistle-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Coaching Staff</div>
                    <div class="ks-kpi-value"><?= count(array_filter($employees, fn($e) => !empty($e['coach_id']))) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Linked to Coach Profiles</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber">
                    <i class="bi bi-building-check fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Departments</div>
                    <div class="ks-kpi-value"><?= count($departments) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Functional Units</span>
            </div>
        </div>
    </div>
</div>

<!-- Employees Table Card (Section 47) -->
<div class="ks-table-card">
    <div class="ks-table-header flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-badge" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Staff Roster</span>
        </div>
        <!-- Filters Form -->
        <form method="GET" action="/hr/employees" class="d-flex align-items-center flex-wrap gap-2">
            <div class="position-relative" style="width: 220px;">
                <i class="bi bi-search position-absolute" style="left: 10px; top: 11px; color: var(--ks-text-muted); font-size: 12px;"></i>
                <input type="text" name="search" class="ks-form-control" style="padding-left: 30px; height: 36px; font-size: 13px;" placeholder="Search name, code..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>
            <select name="department_id" class="ks-form-select" style="height: 36px; font-size: 13px; width: 160px;" onchange="this.form.submit()">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= ($filters['department_id'] == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="employee_category_id" class="ks-form-select" style="height: 36px; font-size: 13px; width: 150px;" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($filters['employee_category_id'] == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="employment_status" class="ks-form-select" style="height: 36px; font-size: 13px; width: 130px;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="active" <?= ($filters['employment_status'] === 'active') ? 'selected' : '' ?>>Active</option>
                <option value="probation" <?= ($filters['employment_status'] === 'probation') ? 'selected' : '' ?>>Probation</option>
                <option value="suspended" <?= ($filters['employment_status'] === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                <option value="terminated" <?= ($filters['employment_status'] === 'terminated') ? 'selected' : '' ?>>Terminated</option>
            </select>
            <?php if (!empty($filters['search']) || !empty($filters['department_id']) || !empty($filters['employee_category_id']) || !empty($filters['employment_status'])): ?>
                <a href="/hr/employees" class="ks-btn ks-btn-secondary" style="height: 36px; padding: 0 10px; font-size: 12px;">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Employee Code</th>
                    <th>Department</th>
                    <th>Category</th>
                    <th>Designation</th>
                    <th>Employment Type</th>
                    <th>Status</th>
                    <th>Joining Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No employees match the selected criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $e): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="ks-avatar" style="width: 34px; height: 34px; border-radius: 50%; background: #0E1E3B; color: #FFF; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 13px;">
                                        <?= strtoupper(substr($e['first_name'] ?? 'E', 0, 1) . substr($e['last_name'] ?? 'M', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-navy"><?= htmlspecialchars(($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? '')) ?></span>
                                        <div class="small text-muted"><?= htmlspecialchars($e['email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background: #EAF3FF; color: #0B6EF3; font-weight: 600; padding: 6px 10px; border-radius: 6px;">
                                    <?= htmlspecialchars($e['employee_code']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="small fw-semibold text-navy"><?= htmlspecialchars($e['department_name'] ?? 'General') ?></span>
                            </td>
                            <td>
                                <span class="small text-muted"><?= htmlspecialchars($e['category_name'] ?? 'Uncategorized') ?></span>
                            </td>
                            <td>
                                <span class="small text-navy"><?= htmlspecialchars($e['designation'] ?? '—') ?></span>
                            </td>
                            <td>
                                <span class="small text-muted text-capitalize"><?= htmlspecialchars($e['employment_type'] ?? 'full_time') ?></span>
                            </td>
                            <td>
                                <?php if (($e['employment_status'] ?? '') === 'active'): ?>
                                    <span class="ks-badge ks-badge-confirmed">Active</span>
                                <?php elseif (($e['employment_status'] ?? '') === 'probation'): ?>
                                    <span class="ks-badge ks-badge-pending">Probation</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-rejected"><?= htmlspecialchars(ucfirst($e['employment_status'] ?? 'Inactive')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="small text-muted"><?= htmlspecialchars($e['joining_date'] ?? '—') ?></span>
                            </td>
                            <td style="text-align: right;">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="/hr/employees/<?= $e['id'] ?>" class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">
                                        View
                                    </a>
                                    <a href="/hr/employees/<?= $e['id'] ?>/edit" class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
