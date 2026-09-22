<?php
$title = "Housekeeping";
$pageHeader = "Housekeeping Tasks";
$pageSubheader = "Manage daily cleaning and facility prep tasks.";
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2">
        <input type="text" class="ks-form-control" placeholder="Search tasks...">
    </div>
    <div class="d-flex gap-2">
        <button class="ks-btn ks-btn-primary" data-bs-toggle="modal" data-bs-target="#newHousekeepingModal">
            <i class="fas fa-plus me-1"></i> New Task
        </button>
    </div>
</div>

<div class="ks-card" style="padding: 0;">
    <div class="table-responsive">
        <table class="table ks-table mb-0">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 15%;">Reference</th>
                    <th style="width: 25%;">Type</th>
                    <th style="width: 15%;">Venue / Facility</th>
                    <th style="width: 15%;">Priority</th>
                    <th style="width: 15%;">Status</th>
                </tr>
            </thead>
            <tbody id="tasksTableBody">
                <!-- Loaded via JS -->
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="newHousekeepingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--ks-radius-modal); border: 1px solid var(--ks-border); box-shadow: var(--ks-shadow-modal);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy" style="font-size: 18px;">Create Housekeeping Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <form id="housekeepingForm">
                    <div class="mb-3">
                        <label class="ks-form-label">Venue ID *</label>
                        <input type="number" class="ks-form-control" name="venue_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="ks-form-label">Facility ID (Optional)</label>
                        <input type="number" class="ks-form-control" name="facility_id">
                    </div>
                    <div class="mb-3">
                        <label class="ks-form-label">Task Type *</label>
                        <input type="text" class="ks-form-control" name="task_type" required>
                    </div>
                    <div class="mb-3">
                        <label class="ks-form-label">Priority *</label>
                        <select class="ks-form-select" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="ks-btn ks-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="ks-btn ks-btn-primary" onclick="saveTask()">Create</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadTasks);

function loadTasks() {
    fetch('/api/v1/housekeeping')
        .then(res => res.json())
        .then(res => {
            const tbody = document.getElementById('tasksTableBody');
            tbody.innerHTML = '';
            if (res.data && res.data.data) {
                res.data.data.forEach((t, index) => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><span class="fw-medium text-navy">${t.task_reference}</span></td>
                            <td>${t.task_type}</td>
                            <td>Venue: ${t.venue_id}</td>
                            <td>${t.priority}</td>
                            <td>
                                <span class="ks-badge ks-badge-warning">${t.status}</span>
                            </td>
                        </tr>
                    `;
                });
            }
        });
}

function saveTask() {
    const form = document.getElementById('housekeepingForm');
    const data = Object.fromEntries(new FormData(form).entries());
    
    fetch('/api/v1/housekeeping', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(res => res.json()).then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('newHousekeepingModal')).hide();
            form.reset();
            loadTasks();
        } else {
            alert('Error: ' + JSON.stringify(res.errors || res.message));
        }
    });
}
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../../layouts/app.blade.php';
?>
