<?php
$activePage = 'training';
$title = 'Attendance History — KhelSutra Platform';

$attService = new \App\Services\Attendance\AttendanceService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

$trainingRecords = $attService->listTrainingAttendance($orgId);
$matchRecords = $attService->listMatchAttendance($orgId);

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/attendance/training" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Training Attendance</a>
        </div>
        <h1 class="ks-page-title">Attendance Audit & History</h1>
        <p class="ks-page-subtitle">Unified logs across training sessions and tournament matches.</p>
    </div>
</div>

<div class="ks-card p-4 mb-4">
    <h5 class="fw-bold text-navy mb-3"><i class="bi bi-stopwatch text-primary me-2"></i>Training Sessions Log</h5>
    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Session</th>
                    <th>Participant</th>
                    <th>Status</th>
                    <th>Time</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trainingRecords)): ?>
                    <tr><td colspan="5" class="text-center py-3 text-muted">No training attendance recorded.</td></tr>
                <?php else: ?>
                    <?php foreach ($trainingRecords as $r): ?>
                        <tr>
                            <td>Session #<?= htmlspecialchars((string)$r['training_session_id']) ?></td>
                            <td class="fw-semibold text-navy"><?= htmlspecialchars($r['participant_name'] ?? 'Participant') ?></td>
                            <td><span class="ks-badge ks-badge-confirmed"><?= htmlspecialchars(ucfirst($r['attendance_status'])) ?></span></td>
                            <td class="font-monospace small text-muted"><?= htmlspecialchars($r['check_in_time'] ?? '—') ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($r['remarks'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ks-card p-4">
    <h5 class="fw-bold text-navy mb-3"><i class="bi bi-trophy text-primary me-2"></i>Matches Log</h5>
    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Match</th>
                    <th>Participant</th>
                    <th>Status</th>
                    <th>Time</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($matchRecords)): ?>
                    <tr><td colspan="5" class="text-center py-3 text-muted">No match attendance recorded.</td></tr>
                <?php else: ?>
                    <?php foreach ($matchRecords as $r): ?>
                        <tr>
                            <td>Match #<?= htmlspecialchars((string)$r['match_id']) ?></td>
                            <td class="fw-semibold text-navy"><?= htmlspecialchars($r['participant_name'] ?? 'Participant') ?></td>
                            <td><span class="ks-badge ks-badge-confirmed"><?= htmlspecialchars(ucfirst($r['attendance_status'])) ?></span></td>
                            <td class="font-monospace small text-muted"><?= htmlspecialchars($r['check_in_time'] ?? '—') ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($r['remarks'] ?? '—') ?></td>
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
