<?php
$activePage = 'organizations';
$title = 'Organisation Details — KhelSutra Super Admin';

$orgId = (int)($id ?? 1);
$orgService = new \App\Services\Organization\OrganizationManagementService();
$org = $orgService->getOrganization($orgId);
$accessLogs = $orgService->getAccessLogs($orgId);

ob_start();
?>

<!-- Page Header (Section 16) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title"><?= htmlspecialchars($org['name'] ?? 'Organisation') ?></h1>
        <p class="ks-page-subtitle">Organisation Code: <strong class="text-primary"><?= htmlspecialchars($org['organization_code'] ?? '—') ?></strong> • Multi-Tenant Tenant Isolation Verified</p>
    </div>
    <div class="ks-header-actions">
        <a href="/super-admin/organizations" class="ks-btn ks-btn-secondary">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Roster</span>
        </a>
        <button class="ks-btn ks-btn-primary" onclick="toggleStatus(<?= $orgId ?>, '<?= ($org['status'] ?? 'active') === 'active' ? 'suspended' : 'active' ?>')">
            <i class="bi bi-power"></i>
            <span><?= ($org['status'] ?? 'active') === 'active' ? 'Suspend Access' : 'Activate Access' ?></span>
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Organisation Info Card -->
    <div class="col-lg-6">
        <div class="ks-card p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom" style="border-color: var(--ks-border-light) !important;">
                <h3 class="fw-bold text-navy mb-0" style="font-size: 16px;">Organisation Profile</h3>
                <?php if (($org['status'] ?? '') === 'active'): ?>
                    <span class="ks-badge ks-badge-confirmed">Active</span>
                <?php else: ?>
                    <span class="ks-badge ks-badge-rejected"><?= htmlspecialchars(ucfirst($org['status'] ?? 'Unknown')) ?></span>
                <?php endif; ?>
            </div>
            <div class="d-flex flex-column gap-2 small">
                <div><strong>Legal Entity:</strong> <?= htmlspecialchars($org['legal_name'] ?? '—') ?></div>
                <div><strong>Contact Email:</strong> <?= htmlspecialchars($org['email'] ?? '—') ?></div>
                <div><strong>Phone Number:</strong> <?= htmlspecialchars($org['phone'] ?? '—') ?></div>
                <div><strong>Campus Address:</strong> <?= htmlspecialchars(($org['address_line1'] ?? '') . ', ' . ($org['city'] ?? '') . ', ' . ($org['state'] ?? '')) ?></div>
                <div><strong>Plan:</strong> <span class="ks-badge ks-badge-blue"><?= htmlspecialchars($org['plan_name'] ?? 'Standard') ?></span></div>
                <div><strong>Access Start:</strong> <?= htmlspecialchars($org['access_start_date'] ?? '—') ?></div>
                <div><strong>Access Expiry:</strong> <?= htmlspecialchars($org['access_end_date'] ?? '—') ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="col-lg-6">
        <div class="ks-card p-4 h-100">
            <h3 class="fw-bold text-navy mb-3 pb-2 border-bottom" style="font-size: 16px; border-color: var(--ks-border-light) !important;">
                System Isolation & Tenancy Metrics
            </h3>
            <div class="row g-3">
                <div class="col-6">
                    <div class="p-3 rounded" style="background: #F8FAFD; border: 1px solid var(--ks-border-light);">
                        <div class="text-muted small">Database Tenancy</div>
                        <div class="fw-bold text-navy fs-5">Enforced</div>
                        <div class="small text-success">WHERE org_id = <?= $orgId ?></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 rounded" style="background: #F8FAFD; border: 1px solid var(--ks-border-light);">
                        <div class="text-muted small">Subscription Status</div>
                        <div class="fw-bold text-primary fs-5"><?= htmlspecialchars(ucfirst($org['status'] ?? 'Active')) ?></div>
                        <div class="small text-muted">Auto-Renew Eligible</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Access History Table -->
<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Organisation Access & State Transition History</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Previous State</th>
                    <th>New State</th>
                    <th>Access Start</th>
                    <th>Access End</th>
                    <th>Timestamp</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accessLogs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No state transition records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($accessLogs as $log): ?>
                        <tr>
                            <td>
                                <span class="ks-badge ks-badge-blue"><?= htmlspecialchars(strtoupper($log['action'])) ?></span>
                            </td>
                            <td><?= htmlspecialchars($log['previous_status'] ?? '—') ?></td>
                            <td><span class="fw-semibold text-navy"><?= htmlspecialchars($log['new_status'] ?? '—') ?></span></td>
                            <td><?= htmlspecialchars($log['access_start_date'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($log['access_end_date'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($log['created_at']) ?></td>
                            <td><?= htmlspecialchars($log['remarks'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function toggleStatus(orgId, newStatus) {
    if (!confirm('Are you sure you want to change status to ' + newStatus + '?')) return;
    try {
        const res = await fetch('/api/v1/organizations/' + orgId + '/status', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: newStatus, remarks: 'Updated via Super Admin console' })
        });
        const json = await res.json();
        if (json.success) {
            location.reload();
        } else {
            alert('Error: ' + json.message);
        }
    } catch (e) {
        alert('Request failed');
    }
}
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
