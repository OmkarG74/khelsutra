<?php
$activePage = 'hr-finance';
$title = 'Leave Review — KhelSutra Platform';

$leaveId = $id ?? 1;
$leaveService = new \App\Services\Leave\LeaveService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

try {
    $req = $leaveService->getLeaveRequestDetails((int)$leaveId, (int)$orgId);
} catch (\Throwable $e) {
    $req = null;
}

ob_start();
?>

<div class="ks-page-header">
    <div class="d-flex align-items-center gap-2 mb-1">
        <a href="/leave" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Leave</a>
    </div>
    <h1 class="ks-page-title">Leave Request #<?= htmlspecialchars((string)($req['id'] ?? '')) ?></h1>
</div>

<?php if (!$req): ?>
    <div class="ks-card p-5 text-center text-muted">
        <i class="bi bi-calendar-x fs-1 text-danger"></i>
        <h5 class="mt-3">Leave Request Not Found</h5>
        <p>The requested application does not exist or does not belong to your active organisation context.</p>
        <a href="/leave" class="ks-btn ks-btn-secondary mt-2">Return to Leave</a>
    </div>
<?php else: ?>
    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="ks-card p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold text-navy mb-0">Application Summary</h5>
                    <?php if ($req['status'] === 'pending'): ?>
                        <span class="ks-badge ks-badge-pending">Pending Review</span>
                    <?php elseif ($req['status'] === 'approved'): ?>
                        <span class="ks-badge ks-badge-confirmed">Approved</span>
                    <?php elseif ($req['status'] === 'rejected'): ?>
                        <span class="ks-badge ks-badge-rejected">Rejected</span>
                    <?php else: ?>
                        <span class="ks-badge ks-badge-scheduled"><?= htmlspecialchars(ucfirst($req['status'])) ?></span>
                    <?php endif; ?>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <span class="ks-kpi-label">Applicant Name</span>
                        <div class="fw-bold text-navy"><?= htmlspecialchars($req['applicant_name'] ?? 'Applicant') ?></div>
                        <div class="small text-muted text-capitalize"><?= htmlspecialchars($req['applicant_type']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <span class="ks-kpi-label">Leave Policy</span>
                        <div class="fw-bold text-navy"><?= htmlspecialchars($req['leave_type_name'] ?? 'General') ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="ks-kpi-label">Start Date</span>
                        <div class="small fw-semibold text-navy font-monospace"><?= htmlspecialchars($req['start_date']) ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="ks-kpi-label">End Date</span>
                        <div class="small fw-semibold text-navy font-monospace"><?= htmlspecialchars($req['end_date']) ?></div>
                    </div>
                    <div class="col-md-4">
                        <span class="ks-kpi-label">Duration</span>
                        <div class="badge bg-light text-dark fw-bold"><?= htmlspecialchars((string)$req['total_days']) ?> Days</div>
                    </div>
                    <div class="col-12">
                        <span class="ks-kpi-label">Reason</span>
                        <div class="p-3 bg-light rounded text-navy small"><?= nl2br(htmlspecialchars($req['reason'])) ?></div>
                    </div>
                    <?php if (!empty($req['rejection_reason'])): ?>
                        <div class="col-12">
                            <span class="ks-kpi-label text-danger">Rejection Reason</span>
                            <div class="p-3 bg-light rounded text-danger small border border-danger"><?= nl2br(htmlspecialchars($req['rejection_reason'])) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($req['attachment_path'])): ?>
                        <div class="col-12">
                            <span class="ks-kpi-label">Attachment</span>
                            <div>
                                <a href="<?= htmlspecialchars($req['attachment_path']) ?>" target="_blank" class="small text-primary font-monospace">
                                    <i class="bi bi-file-earmark-pdf me-1"></i><?= htmlspecialchars($req['attachment_path']) ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Approval / Rejection Action Panel (Only if Pending) -->
            <?php if ($req['status'] === 'pending'): ?>
                <div class="ks-card p-4">
                    <h5 class="fw-bold text-navy mb-2">HR Authorization & Separation of Duties</h5>
                    <p class="small text-muted mb-3">Backend enforces that applicants cannot approve their own requests.</p>
                    <div class="d-flex gap-3">
                        <button class="ks-btn ks-btn-primary" onclick="approveLeave()">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Approve Leave</span>
                        </button>
                        <button class="ks-btn ks-btn-secondary text-danger" onclick="promptReject()">
                            <i class="bi bi-x-circle-fill"></i>
                            <span>Reject Leave</span>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    async function approveLeave() {
        if (!confirm('Are you sure you want to approve this leave request?')) return;
        try {
            const res = await fetch('/api/v1/leave/requests/<?= $req['id'] ?>/review', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: JSON.stringify({action: 'approve'})
            });
            const result = await res.json();
            if (result.success) {
                alert('Leave approved successfully!');
                window.location.reload();
            } else {
                alert(result.message || 'Approval failed');
            }
        } catch (e) {
            alert('Request failed');
        }
    }

    async function promptReject() {
        const reason = prompt('Please enter the reason for rejection:');
        if (!reason) return;

        try {
            const res = await fetch('/api/v1/leave/requests/<?= $req['id'] ?>/review', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: JSON.stringify({action: 'reject', rejection_reason: reason})
            });
            const result = await res.json();
            if (result.success) {
                alert('Leave request rejected.');
                window.location.reload();
            } else {
                alert(result.message || 'Rejection failed');
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
