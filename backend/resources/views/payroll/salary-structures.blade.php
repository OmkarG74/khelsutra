<?php
$activePage = 'hr-finance';
$title = 'Salary Structures — KhelSutra Payroll';

$payrollService = new \App\Services\Payroll\PayrollService();
$empService = new \App\Services\Staff\EmployeeService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

$structures = $payrollService->listSalaryStructures($orgId);
$employees = $empService->listEmployees($orgId);

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/payroll" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Payroll</a>
        </div>
        <h1 class="ks-page-title">Salary Structures</h1>
        <p class="ks-page-subtitle">Configure employee basic compensation, standard allowances, baseline deductions, and overtime rates.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-primary" onclick="document.getElementById('structModal').style.display='flex'">
            <i class="bi bi-plus-lg"></i>
            <span>Set Salary Structure</span>
        </button>
    </div>
</div>

<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-cash-stack" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Active Salary Structures</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Effective From</th>
                    <th>Basic Salary</th>
                    <th>Allowances</th>
                    <th>Deduction</th>
                    <th>Overtime Rate</th>
                    <th>Tax Default</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($structures)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No salary structures configured yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($structures as $s): ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-navy"><?= htmlspecialchars(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?></span>
                                <div class="small text-muted"><?= htmlspecialchars($s['employee_code'] ?? '') ?></div>
                            </td>
                            <td>
                                <span class="small text-navy font-monospace"><?= htmlspecialchars($s['effective_from']) ?></span>
                            </td>
                            <td>
                                <span class="fw-bold text-navy">₹<?= number_format((float)$s['basic_salary'], 2) ?></span>
                            </td>
                            <td>
                                <span class="small text-navy">₹<?= number_format((float)$s['allowances'], 2) ?></span>
                            </td>
                            <td>
                                <span class="small text-danger">₹<?= number_format((float)$s['deduction'], 2) ?></span>
                            </td>
                            <td>
                                <span class="small text-navy">₹<?= number_format((float)$s['overtime_rate'], 2) ?> / hr</span>
                            </td>
                            <td>
                                <span class="small text-danger">₹<?= number_format((float)$s['tax_default'], 2) ?></span>
                            </td>
                            <td>
                                <?php if (($s['status'] ?? '') === 'active'): ?>
                                    <span class="ks-badge ks-badge-confirmed">Active</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-rejected"><?= htmlspecialchars(ucfirst($s['status'] ?? 'Inactive')) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="structModal" style="display: none; position: fixed; inset: 0; background: rgba(14, 30, 59, 0.45); z-index: 9999; align-items: center; justify-content: center;">
    <div class="ks-card p-4" style="width: 580px; max-width: 90%;">
        <h5 class="fw-bold text-navy mb-3">Define Salary Structure</h5>
        <form id="structForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="ks-form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="ks-form-select" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?> (<?= htmlspecialchars($e['employee_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Effective From <span class="text-danger">*</span></label>
                    <input type="date" name="effective_from" class="ks-form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Basic Salary (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="basic_salary" class="ks-form-control" required placeholder="30000.00">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Allowances (₹)</label>
                    <input type="number" step="0.01" name="allowances" class="ks-form-control" value="0.00">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Standard Deductions (₹)</label>
                    <input type="number" step="0.01" name="deduction" class="ks-form-control" value="0.00">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Overtime Rate (₹ / hr)</label>
                    <input type="number" step="0.01" name="overtime_rate" class="ks-form-control" value="0.00">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Default Bonus (₹)</label>
                    <input type="number" step="0.01" name="bonus_default" class="ks-form-control" value="0.00">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Default Tax (₹)</label>
                    <input type="number" step="0.01" name="tax_default" class="ks-form-control" value="0.00">
                </div>
                <div class="col-md-4">
                    <label class="ks-form-label">Other Deductions (₹)</label>
                    <input type="number" step="0.01" name="other_deductions_default" class="ks-form-control" value="0.00">
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="ks-btn ks-btn-secondary" onclick="document.getElementById('structModal').style.display='none'">Cancel</button>
                <button type="submit" class="ks-btn ks-btn-primary">Save Structure</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('structForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const payload = Object.fromEntries(formData.entries());

    try {
        const res = await fetch('/api/v1/payroll/salary-structures', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            window.location.reload();
        } else {
            alert(result.message || 'Error saving salary structure');
        }
    } catch (err) {
        alert('Request failed');
    }
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
