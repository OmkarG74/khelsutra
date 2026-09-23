<?php ob_start(); ?>

<div class="ks-page-header">
    <div class="ks-header-left">
        <h1 class="ks-page-title">Housekeeping</h1>
        <p class="ks-page-subtitle">Schedule and manage facility cleaning and upkeep tasks</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-4">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber"><i class="bi bi-hourglass-split"></i></div>
                <div><div class="ks-kpi-label">PENDING TASKS</div><div class="ks-kpi-value" id="kpi-pending">—</div></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue"><i class="bi bi-arrow-clockwise"></i></div>
                <div><div class="ks-kpi-label">IN PROGRESS</div><div class="ks-kpi-value" id="kpi-progress">—</div></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green"><i class="bi bi-check-circle-fill"></i></div>
                <div><div class="ks-kpi-label">COMPLETED TODAY</div><div class="ks-kpi-value" id="kpi-completed">—</div></div>
            </div>
        </div>
    </div>
</div>

<div class="ks-filter-bar mb-3">
    <div class="ks-filter-grid">
        <div class="btn-group" role="group">
            <input type="radio" class="btn-check" name="dateFilter" id="dateToday" autocomplete="off" value="today" onchange="loadHousekeeping()">
            <label class="btn btn-outline-primary" for="dateToday">Today</label>
            
            <input type="radio" class="btn-check" name="dateFilter" id="dateUpcoming" autocomplete="off" value="upcoming" onchange="loadHousekeeping()">
            <label class="btn btn-outline-primary" for="dateUpcoming">Upcoming</label>
            
            <input type="radio" class="btn-check" name="dateFilter" id="dateAll" autocomplete="off" value="all" checked onchange="loadHousekeeping()">
            <label class="btn btn-outline-primary" for="dateAll">All</label>
        </div>

        <input type="text" class="ks-form-control" id="filterSearch" placeholder="Search..." oninput="loadHousekeeping()">
        <select class="ks-form-select" id="filterPriority" onchange="loadHousekeeping()">
            <option value="">All Priorities</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
        </select>
        <select class="ks-form-select" id="filterStatus" onchange="loadHousekeeping()">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="assigned">Assigned</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
        <button class="ks-btn ks-btn-secondary" onclick="clearFilters()"><i class="bi bi-x-circle"></i> Clear</button>
    </div>
</div>

<div class="ks-content-card">
    <div class="ks-card-header">
        <div class="ks-header-left">
            <i class="bi bi-stars" style="color:var(--ks-primary);font-size:18px;"></i>
            <h3 class="ks-header-title">Housekeeping Tasks</h3>
            <span class="ks-badge ks-badge-scheduled ms-2" id="total-count">0</span>
        </div>
        <button class="ks-btn ks-btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
            <i class="bi bi-plus-lg"></i> Create Task
        </button>
    </div>
    <div class="ks-table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Task Type</th>
                    <th>Scheduled Date</th>
                    <th>Priority</th>
                    <th>Assigned Employee</th>
                    <th>Status</th>
                    <th>Quick Action</th>
                </tr>
            </thead>
            <tbody id="tableBody">
            </tbody>
        </table>
    </div>
</div>

<!-- Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: var(--ks-radius-modal); border: 1px solid var(--ks-border); box-shadow: var(--ks-shadow-modal);">
            <div class="modal-header">
                <h5 class="modal-title">Create Housekeeping Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createForm" onsubmit="event.preventDefault(); submitCreateTask();">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Task Type <span class="text-danger">*</span></label>
                            <input type="text" class="ks-form-control" id="createType" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select class="ks-form-select" id="createPriority" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Venue ID <span class="text-danger">*</span></label>
                            <input type="number" class="ks-form-control" id="createVenue" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Facility ID</label>
                            <input type="number" class="ks-form-control" id="createFacility">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assigned Employee ID</label>
                            <input type="number" class="ks-form-control" id="createEmployee">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                            <input type="date" class="ks-form-control" id="createDate" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Time</label>
                            <input type="time" class="ks-form-control" id="createStart">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Time</label>
                            <input type="time" class="ks-form-control" id="createEnd">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="ks-form-control" id="createDesc" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea class="ks-form-control" id="createRemarks" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="ks-btn ks-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="createForm" class="ks-btn ks-btn-primary">Create Task</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentCreateModal = null;

document.addEventListener('DOMContentLoaded', () => {
    currentCreateModal = new bootstrap.Modal(document.getElementById('createTaskModal'));
    loadHousekeeping();
});

function clearFilters() {
    document.getElementById('dateAll').checked = true;
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterPriority').value = '';
    document.getElementById('filterStatus').value = '';
    loadHousekeeping();
}

async function loadHousekeeping() {
    const search = document.getElementById('filterSearch').value;
    const priority = document.getElementById('filterPriority').value;
    const status = document.getElementById('filterStatus').value;
    const dateFilter = document.querySelector('input[name="dateFilter"]:checked').value;
    
    const params = new URLSearchParams();
    if(search) params.append('search', search);
    if(priority) params.append('priority', priority);
    if(status) params.append('status', status);

    try {
        const res = await fetch(`/api/v1/housekeeping?${params.toString()}`);
        const json = await res.json();
        if(json.success) {
            let data = json.data.data;
            
            // Client-side date filtering if API doesn't support 'dateFilter' enum directly
            const todayStr = new Date().toISOString().split('T')[0];
            if(dateFilter === 'today') {
                data = data.filter(t => t.scheduled_date === todayStr);
            } else if (dateFilter === 'upcoming') {
                data = data.filter(t => t.scheduled_date > todayStr);
            }

            renderTable(data);
            updateKPIs(data, todayStr);
        } else {
            ksToast('Failed to load tasks', 'error');
        }
    } catch(e) {
        ksToast('Error loading tasks', 'error');
    }
}

function updateKPIs(data, todayStr) {
    let pending = 0, inProgress = 0, completedToday = 0;
    data.forEach(t => {
        if(t.status === 'pending') pending++;
        if(t.status === 'in_progress') inProgress++;
        if(t.status === 'completed' && t.scheduled_date === todayStr) completedToday++;
    });
    document.getElementById('kpi-pending').textContent = pending;
    document.getElementById('kpi-progress').textContent = inProgress;
    document.getElementById('kpi-completed').textContent = completedToday;
    document.getElementById('total-count').textContent = data.length;
}

function getPriorityBadge(priority) {
    switch(priority) {
        case 'critical': return '<span class="ks-badge ks-badge-rejected">Critical</span>';
        case 'high': return '<span class="ks-badge ks-badge-pending" style="background:#FEF3C7;color:#7C2D12">High</span>';
        case 'medium': return '<span class="ks-badge ks-badge-pending">Medium</span>';
        case 'low': return '<span class="ks-badge ks-badge-scheduled">Low</span>';
        default: return `<span class="ks-badge ks-badge-scheduled">${ksEscape(priority)}</span>`;
    }
}

function getStatusBadge(status) {
    switch(status) {
        case 'pending': return '<span class="ks-badge ks-badge-pending">Pending</span>';
        case 'assigned': return '<span class="ks-badge ks-badge-scheduled">Assigned</span>';
        case 'in_progress': return '<span class="ks-badge ks-badge-pending">In Progress</span>';
        case 'completed': return '<span class="ks-badge ks-badge-confirmed">Completed</span>';
        case 'cancelled': return '<span class="ks-badge ks-badge-cancelled">Cancelled</span>';
        default: return `<span class="ks-badge ks-badge-scheduled">${ksEscape(status)}</span>`;
    }
}

function getActionsDropdown(ticket) {
    const s = ticket.status;
    if (s === 'completed' || s === 'cancelled') {
        return `<span class="text-muted"><i class="bi bi-lock"></i> Read Only</span>`;
    }

    let items = '';
    if (s === 'pending') {
        items += `<li><a class="dropdown-item" href="#" onclick="updateStatus(${ticket.id}, 'assigned')">Assign</a></li>`;
        items += `<li><a class="dropdown-item" href="#" onclick="updateStatus(${ticket.id}, 'cancelled')">Cancel</a></li>`;
    } else if (s === 'assigned') {
        items += `<li><a class="dropdown-item" href="#" onclick="updateStatus(${ticket.id}, 'in_progress')">Start</a></li>`;
        items += `<li><a class="dropdown-item" href="#" onclick="updateStatus(${ticket.id}, 'cancelled')">Cancel</a></li>`;
    } else if (s === 'in_progress') {
        items += `<li><a class="dropdown-item" href="#" onclick="updateStatus(${ticket.id}, 'completed')">Complete</a></li>`;
        items += `<li><a class="dropdown-item" href="#" onclick="updateStatus(${ticket.id}, 'cancelled')">Cancel</a></li>`;
    }

    return `
        <div class="dropdown">
            <button class="ks-btn ks-btn-sm ks-btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                Action
            </button>
            <ul class="dropdown-menu">
                ${items}
            </ul>
        </div>
    `;
}

function renderTable(data) {
    const tbody = document.getElementById('tableBody');
    if(data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="ks-empty-state">No tasks found.</div></td></tr>`;
        return;
    }

    tbody.innerHTML = data.map(t => {
        let slot = t.scheduled_start_time ? (t.scheduled_start_time + (t.scheduled_end_time ? ' - ' + t.scheduled_end_time : '')) : '';
        return `
            <tr>
                <td><strong>${ksEscape(t.task_reference)}</strong></td>
                <td>
                    <div class="fw-bold">${ksEscape(t.task_type)}</div>
                    <div class="small text-muted">Venue: ${ksEscape(t.venue_id || '-')} ${t.facility_id ? '| Facility: '+ksEscape(t.facility_id) : ''}</div>
                </td>
                <td>
                    <div>${ksEscape(t.scheduled_date || '-')}</div>
                    ${slot ? `<div class="small text-muted">${ksEscape(slot)}</div>` : ''}
                </td>
                <td>${getPriorityBadge(t.priority)}</td>
                <td>${ksEscape(t.assigned_employee_id || '-')}</td>
                <td>${getStatusBadge(t.status)}</td>
                <td>${getActionsDropdown(t)}</td>
            </tr>
        `;
    }).join('');
}

async function updateStatus(id, newStatus) {
    event.preventDefault();
    try {
        const res = await fetch(`/api/v1/housekeeping/${id}`, {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({ status: newStatus })
        });
        const json = await res.json();
        if(json.success) {
            ksToast('Status updated', 'success');
            loadHousekeeping();
        } else {
            ksToast('Update failed', 'error');
        }
    } catch(e) {
        ksToast('Error updating status', 'error');
    }
}

async function submitCreateTask() {
    const form = document.getElementById('createForm');
    if(!form.reportValidity()) return;

    const payload = {
        task_type: document.getElementById('createType').value,
        priority: document.getElementById('createPriority').value,
        venue_id: document.getElementById('createVenue').value,
        facility_id: document.getElementById('createFacility').value || null,
        assigned_employee_id: document.getElementById('createEmployee').value || null,
        scheduled_date: document.getElementById('createDate').value,
        scheduled_start_time: document.getElementById('createStart').value || null,
        scheduled_end_time: document.getElementById('createEnd').value || null,
        description: document.getElementById('createDesc').value,
        remarks: document.getElementById('createRemarks').value
    };

    try {
        const res = await fetch('/api/v1/housekeeping', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if(json.success) {
            ksToast('Task created', 'success');
            form.reset();
            currentCreateModal.hide();
            loadHousekeeping();
        } else {
            ksToast('Creation failed', 'error');
        }
    } catch(e) {
        ksToast('Error creating task', 'error');
    }
}
</script>

<?php 
$slot = ob_get_clean(); 
include __DIR__ . '/../../layouts/app.blade.php'; 
?>
