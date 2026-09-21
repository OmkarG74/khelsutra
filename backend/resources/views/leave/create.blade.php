<?php
$activePage = 'hr-finance';
$title = 'Apply Leave — KhelSutra Platform';

$leaveService = new \App\Services\Leave\LeaveService();
$empService = new \App\Services\Staff\EmployeeService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

$leaveTypes = $leaveService->listLeaveTypes($orgId);
$employees = $empService->listEmployees($orgId);

ob_start();
?>

<div class="ks-page-header">
    <div class="d-flex align-items-center gap-2 mb-1">
        <a href="/leave" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Leave</a>
    </div>
    <h1 class="ks-page-title">Submit Leave Application</h1>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="ks-card p-4">
            <form id="applyLeaveForm" method="POST" action="/leave/create">
                <h5 class="fw-bold text-navy mb-3">Leave Application Details</h5>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="ks-form-label">Applicant Type <span class="text-danger">*</span></label>
                        <select name="applicant_type" id="applicantTypeSelect" class="ks-form-select" onchange="toggleApplicantSelect()">
                            <option value="employee">Employee / Coach</option>
                            <option value="athlete">Athlete</option>
                        </select>
                    </div>

                    <div class="col-md-6" id="empSelectBlock">
                        <label class="ks-form-label">Select Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" id="employeeIdSelect" class="ks-form-select" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?> (<?= htmlspecialchars($e['employee_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6" id="athSelectBlock" style="display: none;">
                        <label class="ks-form-label">Athlete ID <span class="text-danger">*</span></label>
                        <input type="number" name="athlete_id" id="athleteIdInput" class="ks-form-control" placeholder="Enter Athlete ID">
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type_id" class="ks-form-select" required>
                            <?php foreach ($leaveTypes as $lt): ?>
                                <option value="<?= $lt['id'] ?>"><?= htmlspecialchars($lt['name']) ?> (Max <?= $lt['days_allowed_per_year'] ?> days/yr)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="startDate" class="ks-form-control" required onchange="calculateDays()">
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="endDate" class="ks-form-control" required onchange="calculateDays()">
                    </div>

                    <div class="col-md-6">
                        <label class="ks-form-label">Total Days <span class="text-danger">*</span></label>
                        <input type="number" name="total_days" id="totalDays" class="ks-form-control" required readonly>
                    </div>

                    <div class="col-12">
                        <label class="ks-form-label">Reason for Leave <span class="text-danger">*</span></label>
                        <textarea name="reason" class="ks-form-control" rows="3" required placeholder="State detailed reason for leave request..."></textarea>
                    </div>

                    <div class="col-12">
                        <label class="ks-form-label">Medical Certificate / Attachment (Optional)</label>
                        <input type="text" name="attachment_path" class="ks-form-control" placeholder="attachments/leave/cert.pdf">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="/leave" class="ks-btn ks-btn-secondary">Cancel</a>
                    <button type="submit" class="ks-btn ks-btn-primary" id="btnSubmit">
                        <i class="bi bi-send-fill"></i>
                        <span>Submit Application</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleApplicantSelect() {
    const type = document.getElementById('applicantTypeSelect').value;
    if (type === 'athlete') {
        document.getElementById('empSelectBlock').style.display = 'none';
        document.getElementById('employeeIdSelect').removeAttribute('required');
        document.getElementById('athSelectBlock').style.display = 'block';
        document.getElementById('athleteIdInput').setAttribute('required', 'required');
    } else {
        document.getElementById('empSelectBlock').style.display = 'block';
        document.getElementById('employeeIdSelect').setAttribute('required', 'required');
        document.getElementById('athSelectBlock').style.display = 'none';
        document.getElementById('athleteIdInput').removeAttribute('required');
    }
}

function calculateDays() {
    const s = document.getElementById('startDate').value;
    const e = document.getElementById('endDate').value;
    if (s && e) {
        const start = new Date(s);
        const end = new Date(e);
        const diff = (end - start) / (1000 * 60 * 60 * 24) + 1;
        document.getElementById('totalDays').value = diff > 0 ? diff : 0;
    }
}
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
