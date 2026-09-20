<?php
$activePage = 'tournaments';
$title = 'Match Attendance — KhelSutra Platform';

$attService = new \App\Services\Attendance\AttendanceService();
$empService = new \App\Services\Staff\EmployeeService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

$employees = $empService->listEmployees($orgId);

$filters = [
    'match_id' => !empty($_GET['match_id']) ? (int)$_GET['match_id'] : null,
    'attendance_status' => $_GET['attendance_status'] ?? null,
];

$records = $attService->listMatchAttendance($orgId, $filters);

ob_start();
?>

<!-- Page Header (Section 32 & 45) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Match Attendance</h1>
        <p class="ks-page-subtitle">Competition lineup and fixture participation tracking (Member 3 Integration Foundation).</p>
    </div>
    <div class="ks-header-actions">
        <a href="/attendance/history" class="ks-btn ks-btn-secondary">
            <i class="bi bi-clock-history"></i>
            <span>Attendance Logs</span>
        </a>
        <button class="ks-btn ks-btn-primary" onclick="document.getElementById('markMatchModal').style.display='flex'">
            <i class="bi bi-trophy-fill"></i>
            <span>Mark Match Attendance</span>
        </button>
    </div>
</div>

<!-- Attendance Table -->
<div class="ks-table-card">
    <div class="ks-table-header flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-trophy" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Match Lineup & Roster Attendance</span>
        </div>
        <form method="GET" action="/attendance/matches" class="d-flex align-items-center gap-2">
            <select name="attendance_status" class="ks-form-select" style="height: 36px; font-size: 13px; width: 150px;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="present" <?= ($filters['attendance_status'] === 'present') ? 'selected' : '' ?>>Present</option>
                <option value="absent" <?= ($filters['attendance_status'] === 'absent') ? 'selected' : '' ?>>Absent</option>
                <option value="late" <?= ($filters['attendance_status'] === 'late') ? 'selected' : '' ?>>Late</option>
                <option value="excused" <?= ($filters['attendance_status'] === 'excused') ? 'selected' : '' ?>>Excused</option>
            </select>
        </form>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Match ID</th>
                    <th>Participant Type</th>
                    <th>Participant Name</th>
                    <th>Status</th>
                    <th>Check In Time</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No match attendance records logged yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $rec): ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark fw-bold">Match #<?= htmlspecialchars((string)$rec['match_id']) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($rec['athlete_id'])): ?>
                                    <span class="ks-badge ks-badge-blue">Athlete</span>
                                <?php elseif (!empty($rec['coach_id'])): ?>
                                    <span class="ks-badge ks-badge-scheduled">Coach</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-pending">Staff</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-bold text-navy"><?= htmlspecialchars($rec['participant_name'] ?? 'ID #' . ($rec['athlete_id'] ?? $rec['coach_id'] ?? $rec['employee_id'])) ?></span>
                            </td>
                            <td>
                                <?php if ($rec['attendance_status'] === 'present'): ?>
                                    <span class="ks-badge ks-badge-confirmed">Present</span>
                                <?php elseif ($rec['attendance_status'] === 'late'): ?>
                                    <span class="ks-badge ks-badge-pending">Late</span>
                                <?php elseif ($rec['attendance_status'] === 'excused'): ?>
                                    <span class="ks-badge ks-badge-scheduled">Excused</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-rejected">Absent</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="small text-navy font-monospace"><?= htmlspecialchars($rec['check_in_time'] ?? '—') ?></span>
                            </td>
                            <td>
                                <span class="small text-muted"><?= htmlspecialchars($rec['remarks'] ?? '—') ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="markMatchModal" style="display: none; position: fixed; inset: 0; background: rgba(14, 30, 59, 0.45); z-index: 9999; align-items: center; justify-content: center;">
    <div class="ks-card p-4" style="width: 500px; max-width: 90%;">
        <h5 class="fw-bold text-navy mb-3">Mark Match Attendance</h5>
        <form id="markMatchForm">
            <div class="mb-3">
                <label class="ks-form-label">Match ID <span class="text-danger">*</span></label>
                <input type="number" name="match_id" class="ks-form-control" required value="1">
            </div>
            <div class="mb-3">
                <label class="ks-form-label">Select Official / Staff</label>
                <select name="employee_id" class="ks-form-select">
                    <option value="">Select Staff</option>
                    <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="ks-form-label">Status <span class="text-danger">*</span></label>
                    <select name="attendance_status" class="ks-form-select" required>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="late">Late</option>
                        <option value="excused">Excused</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Check In Time</label>
                    <input type="time" name="check_in_time" class="ks-form-control" value="<?= date('H:i') ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="ks-form-label">Remarks</label>
                <input type="text" name="remarks" class="ks-form-control" placeholder="Match notes...">
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="ks-btn ks-btn-secondary" onclick="document.getElementById('markMatchModal').style.display='none'">Cancel</button>
                <button type="submit" class="ks-btn ks-btn-primary">Save Lineup Attendance</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('markMatchForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const payload = Object.fromEntries(formData.entries());

    try {
        const res = await fetch('/api/v1/attendance/matches', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            window.location.reload();
        } else {
            alert(result.message || 'Error recording match attendance');
        }
    } catch (err) {
        alert('Submission failed');
    }
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
