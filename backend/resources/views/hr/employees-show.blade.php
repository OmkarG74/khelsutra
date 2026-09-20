<?php
$activePage = 'hr-finance';
$title = 'Employee Profile — KhelSutra HR';

$empId = $id ?? 1;
$empService = new \App\Services\Staff\EmployeeService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

try {
    $emp = $empService->getEmployeeDetails((int)$empId, (int)$orgId);
} catch (\Throwable $e) {
    $emp = null;
}

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/hr/employees" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Roster</a>
        </div>
        <h1 class="ks-page-title"><?= htmlspecialchars(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? 'Employee Profile')) ?></h1>
        <p class="ks-page-subtitle">Roster credentials, coaching profile, personal details, documents, and payroll structures.</p>
    </div>
    <div class="ks-header-actions">
        <?php if ($emp): ?>
            <a href="/hr/employees/<?= $emp['id'] ?>/documents" class="ks-btn ks-btn-secondary">
                <i class="bi bi-files"></i>
                <span>Documents</span>
            </a>
            <a href="/hr/employees/<?= $emp['id'] ?>/edit" class="ks-btn ks-btn-primary">
                <i class="bi bi-pencil-square"></i>
                <span>Edit Profile</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$emp): ?>
    <div class="ks-card p-5 text-center text-muted">
        <i class="bi bi-person-x fs-1 text-danger"></i>
        <h5 class="mt-3">Employee Not Found</h5>
        <p>The requested employee record does not exist or is outside your current active organisation context.</p>
        <a href="/hr/employees" class="ks-btn ks-btn-secondary mt-2">Return to Employees</a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <!-- Left: Summary Card -->
        <div class="col-lg-4">
            <div class="ks-card p-4 text-center">
                <div class="ks-avatar mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 50%; background: #0E1E3B; color: #FFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 26px;">
                    <?= strtoupper(substr($emp['first_name'] ?? 'E', 0, 1) . substr($emp['last_name'] ?? 'M', 0, 1)) ?>
                </div>
                <h4 class="fw-bold text-navy mb-1"><?= htmlspecialchars(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')) ?></h4>
                <p class="text-muted small mb-2"><?= htmlspecialchars($emp['designation'] ?? 'Staff Member') ?></p>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge" style="background: #EAF3FF; color: #0B6EF3; font-weight: 600; padding: 6px 10px; border-radius: 6px;">
                        <?= htmlspecialchars($emp['employee_code']) ?>
                    </span>
                    <?php if (($emp['employment_status'] ?? '') === 'active'): ?>
                        <span class="ks-badge ks-badge-confirmed">Active</span>
                    <?php else: ?>
                        <span class="ks-badge ks-badge-rejected"><?= htmlspecialchars(ucfirst($emp['employment_status'] ?? 'Inactive')) ?></span>
                    <?php endif; ?>
                </div>

                <hr style="border-color: var(--ks-border-light);">

                <div class="text-start">
                    <div class="mb-3">
                        <span class="ks-kpi-label">Department</span>
                        <div class="fw-bold text-navy small"><?= htmlspecialchars($emp['department_name'] ?? 'General') ?></div>
                    </div>
                    <div class="mb-3">
                        <span class="ks-kpi-label">Category</span>
                        <div class="fw-semibold text-navy small"><?= htmlspecialchars($emp['category_name'] ?? 'General') ?></div>
                    </div>
                    <div class="mb-3">
                        <span class="ks-kpi-label">Employment Type</span>
                        <div class="text-capitalize small text-muted"><?= htmlspecialchars($emp['employment_type'] ?? 'full_time') ?></div>
                    </div>
                    <div class="mb-3">
                        <span class="ks-kpi-label">Joining Date</span>
                        <div class="small text-navy"><?= htmlspecialchars($emp['joining_date'] ?? '—') ?></div>
                    </div>
                    <div>
                        <span class="ks-kpi-label">Organisation ID</span>
                        <div class="small text-muted">Org #<?= htmlspecialchars((string)$emp['organization_id']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Coach Profile Badge if Coach Profile exists -->
            <?php if (!empty($emp['coach_profile'])): 
                $coach = $emp['coach_profile'];
            ?>
                <div class="ks-card p-4 mt-4" style="border-left: 4px solid var(--ks-primary);">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-whistle-fill text-primary fs-5"></i>
                        <h6 class="fw-bold text-navy mb-0">Coach Profile Foundation</h6>
                    </div>
                    <div class="small mb-2">
                        <span class="text-muted">Coach Code:</span>
                        <code class="fw-bold text-navy"><?= htmlspecialchars($coach['coach_code']) ?></code>
                    </div>
                    <div class="small mb-2">
                        <span class="text-muted">Specialization:</span>
                        <span class="fw-semibold text-navy"><?= htmlspecialchars($coach['specialization'] ?? 'General') ?></span>
                    </div>
                    <div class="small mb-2">
                        <span class="text-muted">License:</span>
                        <span class="text-navy"><?= htmlspecialchars($coach['license_number'] ?? 'N/A') ?></span>
                    </div>
                    <div class="small">
                        <span class="text-muted">Experience:</span>
                        <span class="text-navy"><?= htmlspecialchars((string)($coach['experience_years'] ?? 0)) ?> Years</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Profile Sections -->
        <div class="col-lg-8">
            <!-- 1. Personal & Contact -->
            <div class="ks-card p-4 mb-4">
                <h5 class="fw-bold text-navy mb-3"><i class="bi bi-person-lines-fill text-primary me-2"></i>Personal & Contact Information</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <span class="ks-kpi-label">Email Address</span>
                        <div class="small fw-semibold text-navy"><?= htmlspecialchars($emp['email'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="ks-kpi-label">Phone</span>
                        <div class="small fw-semibold text-navy"><?= htmlspecialchars($emp['phone'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="ks-kpi-label">Date of Birth</span>
                        <div class="small text-navy"><?= htmlspecialchars($emp['date_of_birth'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="ks-kpi-label">Gender</span>
                        <div class="small text-navy text-capitalize"><?= htmlspecialchars($emp['gender'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="ks-kpi-label">Blood Group</span>
                        <div class="small text-navy"><?= htmlspecialchars($emp['blood_group'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="ks-kpi-label">City, State</span>
                        <div class="small text-navy"><?= htmlspecialchars(($emp['city'] ?? '') . ($emp['state'] ? ', ' . $emp['state'] : '')) ?></div>
                    </div>
                    <div class="col-12">
                        <span class="ks-kpi-label">Full Address</span>
                        <div class="small text-navy"><?= htmlspecialchars($emp['address'] ?? '—') ?> <?= htmlspecialchars($emp['postal_code'] ? ' - ' . $emp['postal_code'] : '') ?></div>
                    </div>
                </div>
            </div>

            <!-- 2. Emergency Contact -->
            <div class="ks-card p-4 mb-4">
                <h5 class="fw-bold text-navy mb-3"><i class="bi bi-shield-exclamation text-primary me-2"></i>Emergency Contact</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <span class="ks-kpi-label">Contact Name</span>
                        <div class="small fw-semibold text-navy"><?= htmlspecialchars($emp['emergency_contact_name'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="ks-kpi-label">Emergency Phone</span>
                        <div class="small fw-semibold text-navy"><?= htmlspecialchars($emp['emergency_contact_phone'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="ks-kpi-label">Relationship</span>
                        <div class="small text-navy"><?= htmlspecialchars($emp['emergency_contact_relation'] ?? '—') ?></div>
                    </div>
                </div>
            </div>

            <!-- 3. Bank Information -->
            <div class="ks-card p-4 mb-4">
                <h5 class="fw-bold text-navy mb-3"><i class="bi bi-bank text-primary me-2"></i>Bank & Financial Details</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <span class="ks-kpi-label">Bank Name</span>
                        <div class="small fw-semibold text-navy"><?= htmlspecialchars($emp['bank_name'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="ks-kpi-label">Account Number</span>
                        <div class="small fw-semibold text-navy font-monospace"><?= htmlspecialchars($emp['bank_account_no'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="ks-kpi-label">IFSC Code</span>
                        <div class="small fw-semibold text-navy font-monospace"><?= htmlspecialchars($emp['bank_ifsc'] ?? '—') ?></div>
                    </div>
                </div>
            </div>

            <!-- 4. Notes -->
            <?php if (!empty($emp['notes'])): ?>
                <div class="ks-card p-4">
                    <h5 class="fw-bold text-navy mb-2"><i class="bi bi-journal-text text-primary me-2"></i>Notes</h5>
                    <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($emp['notes'])) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
