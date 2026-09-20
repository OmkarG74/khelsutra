<?php
$activePage = 'hr-finance';
$title = 'Payroll Periods — KhelSutra Payroll';

$payrollService = new \App\Services\Payroll\PayrollService();
$orgId = $_SESSION['current_organization_id'] ?? 1;
$periods = $payrollService->listPeriods($orgId);

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/payroll" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Payroll</a>
        </div>
        <h1 class="ks-page-title">Payroll Periods</h1>
        <p class="ks-page-subtitle">Monthly payroll cycles, processing milestones, and locked period ledger immutability.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-primary" onclick="document.getElementById('periodModal').style.display='flex'">
            <i class="bi bi-plus-lg"></i>
            <span>Create Period</span>
        </button>
    </div>
</div>

<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar2-range" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Configured Payroll Cycles</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Period Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Processed Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($periods)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No payroll periods created yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($periods as $p): ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-navy"><?= htmlspecialchars($p['period_name']) ?></span>
                            </td>
                            <td>
                                <span class="small text-navy font-monospace"><?= htmlspecialchars($p['start_date']) ?></span>
                            </td>
                            <td>
                                <span class="small text-navy font-monospace"><?= htmlspecialchars($p['end_date']) ?></span>
                            </td>
                            <td>
                                <?php if ($p['status'] === 'locked'): ?>
                                    <span class="ks-badge ks-badge-rejected"><i class="bi bi-lock-fill me-1"></i>Locked</span>
                                <?php elseif ($p['status'] === 'processed'): ?>
                                    <span class="ks-badge ks-badge-confirmed">Processed</span>
                                <?php elseif ($p['status'] === 'processing'): ?>
                                    <span class="ks-badge ks-badge-blue">Processing</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-scheduled">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="small text-muted"><?= htmlspecialchars($p['processed_at'] ?? 'Pending') ?></span>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($p['status'] !== 'locked'): ?>
                                    <button class="ks-btn ks-btn-secondary text-danger" style="height: 32px; padding: 0 10px; font-size: 12px;" onclick="lockPeriod(<?= $p['id'] ?>)">
                                        <i class="bi bi-lock"></i> Lock Period
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Immutable</span>
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
<div id="periodModal" style="display: none; position: fixed; inset: 0; background: rgba(14, 30, 59, 0.45); z-index: 9999; align-items: center; justify-content: center;">
    <div class="ks-card p-4" style="width: 480px; max-width: 90%;">
        <h5 class="fw-bold text-navy mb-3">Create Payroll Period</h5>
        <form id="periodForm">
            <div class="mb-3">
                <label class="ks-form-label">Period Name <span class="text-danger">*</span></label>
                <input type="text" name="period_name" class="ks-form-control" required placeholder="e.g. October 2026 Payroll">
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="ks-form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="ks-form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">End Date <span class="text-danger">*</span></label>
                    <input type="date" name="end_date" class="ks-form-control" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="ks-form-label">Initial Status</label>
                <select name="status" class="ks-form-select">
                    <option value="draft">Draft</option>
                    <option value="processing">Processing</option>
                </select>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="ks-btn ks-btn-secondary" onclick="document.getElementById('periodModal').style.display='none'">Cancel</button>
                <button type="submit" class="ks-btn ks-btn-primary">Create Period</button>
            </div>
        </form>
    </div>
</div>

<script>
async function lockPeriod(id) {
    if (!confirm('Are you sure you want to lock this payroll period? Once locked, all calculations become permanent and immutable.')) return;
    try {
        const res = await fetch('/api/v1/payroll/periods/' + id + '/lock', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'}
        });
        const result = await res.json();
        if (result.success) {
            alert('Period locked successfully!');
            window.location.reload();
        } else {
            alert(result.message || 'Failed to lock period');
        }
    } catch (e) {
        alert('Request failed');
    }
}

document.getElementById('periodForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const payload = Object.fromEntries(formData.entries());

    try {
        const res = await fetch('/api/v1/payroll/periods', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            window.location.reload();
        } else {
            alert(result.message || 'Error creating period');
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
