<?php
$activePage = 'hr-finance';
$title = 'Payslip Details — KhelSutra Payroll';

$payId = $id ?? 1;
$payrollService = new \App\Services\Payroll\PayrollService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

try {
    $pay = $payrollService->getPayrollDetails((int)$payId, (int)$orgId);
} catch (\Throwable $e) {
    $pay = null;
}

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/payroll" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Payroll</a>
        </div>
        <h1 class="ks-page-title">Payslip & Disbursement #<?= htmlspecialchars((string)($pay['id'] ?? '')) ?></h1>
        <p class="ks-page-subtitle">Detailed compensation statement, decimal-verified earnings, and statutory deductions.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i>
            <span>Print Payslip</span>
        </button>
    </div>
</div>

<?php if (!$pay): ?>
    <div class="ks-card p-5 text-center text-muted">
        <i class="bi bi-receipt-cutoff fs-1 text-danger"></i>
        <h5 class="mt-3">Payroll Record Not Found</h5>
        <p>The requested payroll record does not exist or does not belong to your active organisation context.</p>
        <a href="/payroll" class="ks-btn ks-btn-secondary mt-2">Return to Payroll</a>
    </div>
<?php else: ?>
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="ks-card p-5">
                <!-- Header / Academy info -->
                <div class="d-flex align-items-center justify-content-between pb-4 mb-4 border-bottom" style="border-color: var(--ks-border-light)!important;">
                    <div>
                        <h3 class="fw-bold text-navy mb-1">KhelSutra Sports Academy</h3>
                        <p class="text-muted small mb-0">Monthly Salary Disbursement Slip • <?= htmlspecialchars($pay['period_name'] ?? 'Payroll Period') ?></p>
                    </div>
                    <div>
                        <?php if ($pay['payment_status'] === 'paid'): ?>
                            <span class="ks-badge ks-badge-confirmed fs-6 px-3 py-2">Paid</span>
                        <?php elseif ($pay['payment_status'] === 'processed'): ?>
                            <span class="ks-badge ks-badge-blue fs-6 px-3 py-2">Processed</span>
                        <?php else: ?>
                            <span class="ks-badge ks-badge-pending fs-6 px-3 py-2"><?= htmlspecialchars(ucfirst($pay['payment_status'])) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Employee Summary -->
                <div class="row g-3 mb-4 pb-4 border-bottom" style="border-color: var(--ks-border-light)!important;">
                    <div class="col-md-6">
                        <span class="ks-kpi-label">Employee Name</span>
                        <div class="h5 fw-bold text-navy mb-1"><?= htmlspecialchars(($pay['first_name'] ?? '') . ' ' . ($pay['last_name'] ?? '')) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($pay['designation'] ?? 'Staff Member') ?> • <?= htmlspecialchars($pay['department_name'] ?? 'Operations') ?></div>
                    </div>
                    <div class="col-md-3">
                        <span class="ks-kpi-label">Employee Code</span>
                        <div class="small fw-semibold text-navy font-monospace"><?= htmlspecialchars($pay['employee_code'] ?? '—') ?></div>
                        <span class="ks-kpi-label mt-2">Payment Date</span>
                        <div class="small text-navy"><?= htmlspecialchars($pay['payment_date'] ?? 'Pending') ?></div>
                    </div>
                    <div class="col-md-3">
                        <span class="ks-kpi-label">Bank Name</span>
                        <div class="small fw-semibold text-navy"><?= htmlspecialchars($pay['bank_name'] ?? '—') ?></div>
                        <span class="ks-kpi-label mt-2">Account No</span>
                        <div class="small text-navy font-monospace"><?= htmlspecialchars($pay['bank_account_no'] ?? '—') ?></div>
                    </div>
                </div>

                <!-- Earnings vs Deductions Table -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <h6 class="fw-bold text-navy mb-3 border-bottom pb-2">Gross Earnings</h6>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted">Basic Salary</span>
                                <span class="fw-semibold text-navy">₹<?= number_format((float)$pay['basic_salary'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted">Allowances</span>
                                <span class="fw-semibold text-navy">₹<?= number_format((float)$pay['allowances'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted">Overtime (<?= (float)$pay['overtime_hours'] ?> hrs)</span>
                                <span class="fw-semibold text-navy">₹<?= number_format((float)$pay['overtime_amount'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted">Performance Bonus</span>
                                <span class="fw-semibold text-navy">₹<?= number_format((float)$pay['bonus'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between pt-2 border-top fw-bold text-navy">
                                <span>Gross Total</span>
                                <span>₹<?= number_format((float)$pay['gross_salary'], 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <h6 class="fw-bold text-navy mb-3 border-bottom pb-2">Deductions</h6>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted">Income Tax</span>
                                <span class="fw-semibold text-danger">₹<?= number_format((float)$pay['tax'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted">Standard Deductions</span>
                                <span class="fw-semibold text-danger">₹<?= number_format((float)$pay['deductions'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted">Other Deductions</span>
                                <span class="fw-semibold text-danger">₹<?= number_format((float)$pay['other_deductions'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between pt-4 border-top fw-bold text-danger">
                                <span>Total Deductions</span>
                                <span>₹<?= number_format((float)$pay['tax'] + (float)$pay['deductions'] + (float)$pay['other_deductions'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Total Banner -->
                <div class="p-4 rounded-3 text-center mb-4" style="background: var(--ks-primary-light); border: 1px solid rgba(11, 110, 243, 0.2);">
                    <div class="small text-muted text-uppercase fw-bold letter-spacing-1">Net Take Home Pay</div>
                    <div class="display-6 fw-bold text-navy mt-1">₹<?= number_format((float)$pay['net_salary'], 2) ?></div>
                </div>

                <!-- Payment Status Management -->
                <?php if ($pay['payment_status'] !== 'paid'): ?>
                    <div class="d-flex justify-content-end gap-2">
                        <button class="ks-btn ks-btn-primary" onclick="markPaid()">
                            <i class="bi bi-check2-all"></i>
                            <span>Mark as Paid & Disbursed</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    async function markPaid() {
        const ref = prompt('Enter payment transaction reference:');
        if (!ref) return;

        try {
            const res = await fetch('/api/v1/payroll/<?= $pay['id'] ?>', {
                method: 'PUT',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: JSON.stringify({
                    payment_status: 'paid',
                    payment_reference: ref,
                    payment_date: '<?= date('Y-m-d') ?>'
                })
            });
            const result = await res.json();
            if (result.success) {
                alert('Payment status updated to Paid!');
                window.location.reload();
            } else {
                alert(result.message || 'Error updating payment');
            }
        } catch (e) {
            alert('Request failed');
        }
    }
    </script>
<?php endif; ?>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
