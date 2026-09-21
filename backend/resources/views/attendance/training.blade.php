<?php
$activePage = 'training';
$title = 'Training Attendance — KhelSutra Platform';

$attService = new \App\Services\Attendance\AttendanceService();
$empService = new \App\Services\Staff\EmployeeService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

$employees = $empService->listEmployees($orgId);

// Query filters
$filters = [
    'training_session_id' => !empty($_GET['training_session_id']) ? (int)$_GET['training_session_id'] : null,
    'attendance_status' => $_GET['attendance_status'] ?? null,
];

$records = $attService->listTrainingAttendance($orgId, $filters);

ob_start();
?>

<!-- Page Header (Section 31 & 45) -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Training Attendance</h1>
        <p class="ks-page-subtitle">Mark and monitor training session presence for athletes, coaches, and staff (Member 2 Foundation).</p>
    </div>
    <div class="ks-header-actions">
        <a href="/attendance/history" class="ks-btn ks-btn-secondary">
            <i class="bi bi-clock-history"></i>
            <span>Attendance History</span>
        </a>
        <button class="ks-btn ks-btn-primary" onclick="document.getElementById('markTrainingModal').style.display='flex'">
            <i class="bi bi-check2-circle"></i>
            <span>Mark Training Attendance</span>
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue">
                    <i class="bi bi-card-checklist fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Recorded Sessions</div>
                    <div class="ks-kpi-value"><?= count($records) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text ks-trend-positive">Total Logged</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green">
                    <i class="bi bi-person-check-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Present</div>
                    <div class="ks-kpi-value"><?= count(array_filter($records, fn($r) => $r['attendance_status'] === 'present')) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-success">Active Participation</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber">
                    <i class="bi bi-clock-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Late</div>
                    <div class="ks-kpi-value"><?= count(array_filter($records, fn($r) => $r['attendance_status'] === 'late')) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text text-warning">Tardiness Tracked</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-purple">
                    <i class="bi bi-person-x-fill fs-4"></i>
                </div>
                <div>
                    <div class="ks-kpi-label">Absent / Excused</div>
                    <div class="ks-kpi-value"><?= count(array_filter($records, fn($r) => in_array($r['attendance_status'], ['absent', 'excused']))) ?></div>
                </div>
            </div>
            <div class="ks-kpi-bottom">
                <span class="ks-trend-text">Documented Absences</span>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Table -->
<div class="ks-table-card">
    <div class="ks-table-header flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar2-check" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Training Attendance Roster</span>
        </div>
        <form method="GET" action="/attendance/training" class="d-flex align-items-center gap-2">
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
        <table class="ks-table ks-table-training-attendance">
            <thead>
                <tr>
                    <th>Session ID</th>
                    <th>Participant Type</th>
                    <th>Participant Name</th>
                    <th>Status</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No training attendance records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $rec): ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark fw-bold">Session #<?= htmlspecialchars((string)$rec['training_session_id']) ?></span>
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
                                <span class="small text-navy font-monospace"><?= htmlspecialchars($rec['check_out_time'] ?? '—') ?></span>
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

<!-- Mark Modal -->
<div id="markTrainingModal" style="display: none; position: fixed; inset: 0; background: rgba(14, 30, 59, 0.45); z-index: 9999; align-items: center; justify-content: center;">
    <div class="ks-card p-4" style="width: 520px; max-width: 90%;">
        <h5 class="fw-bold text-navy mb-3">Mark Training Attendance</h5>
        <p class="small text-muted mb-3">Single-participant rule enforced: athlete XOR coach XOR staff.</p>
        <form id="markTrainingForm">
            <div class="mb-3">
                <label class="ks-form-label">Training Session ID <span class="text-danger">*</span></label>
                <input type="number" name="training_session_id" class="ks-form-control" required value="1">
            </div>
            <div class="mb-3">
                <label class="ks-form-label">Participant Type</label>
                <select id="participantType" class="ks-form-select" onchange="toggleSubjectInput()">
                    <option value="employee">Staff / Coach Employee</option>
                    <option value="athlete">Athlete ID</option>
                    <option value="coach">Coach ID</option>
                </select>
            </div>
            <div class="mb-3" id="employeeSelectDiv">
                <label class="ks-form-label">Select Employee</label>
                <select name="employee_id" class="ks-form-select" id="empSelectInput">
                    <option value="">Select Employee</option>
                    <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?> (<?= htmlspecialchars($e['employee_code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3" id="rawSubjectDiv" style="display: none;">
                <label class="ks-form-label" id="rawSubjectLabel">Subject ID</label>
                <input type="number" id="rawSubjectId" class="ks-form-control" placeholder="Enter ID">
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="ks-form-label">Attendance Status <span class="text-danger">*</span></label>
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
                <input type="text" name="remarks" class="ks-form-control" placeholder="Session notes...">
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="ks-btn ks-btn-secondary" onclick="document.getElementById('markTrainingModal').style.display='none'">Cancel</button>
                <button type="submit" class="ks-btn ks-btn-primary">Record Attendance</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSubjectInput() {
    const type = document.getElementById('participantType').value;
    if (type === 'employee') {
        document.getElementById('employeeSelectDiv').style.display = 'block';
        document.getElementById('rawSubjectDiv').style.display = 'none';
    } else {
        document.getElementById('employeeSelectDiv').style.display = 'none';
        document.getElementById('rawSubjectDiv').style.display = 'block';
        document.getElementById('rawSubjectLabel').textContent = type === 'athlete' ? 'Athlete ID' : 'Coach ID';
    }
}

document.getElementById('markTrainingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const type = document.getElementById('participantType').value;
    const formData = new FormData(this);
    const payload = Object.fromEntries(formData.entries());

    if (type === 'athlete') {
        payload.athlete_id = document.getElementById('rawSubjectId').value;
        delete payload.employee_id;
    } else if (type === 'coach') {
        payload.coach_id = document.getElementById('rawSubjectId').value;
        delete payload.employee_id;
    }

    try {
        const res = await fetch('/api/v1/attendance/training', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            window.location.reload();
        } else {
            alert(result.message || 'Error recording attendance');
        }
    } catch (err) {
        alert('Attendance submission failed');
    }
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
