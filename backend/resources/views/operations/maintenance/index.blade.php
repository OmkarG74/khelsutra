<?php
$title = "Maintenance";
$pageHeader = "Venue Maintenance";
$pageSubheader = "Track and manage facility repairs and maintenance tasks.";
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2">
        <input type="text" class="ks-form-control" placeholder="Search tickets...">
    </div>
    <div class="d-flex gap-2">
        <button class="ks-btn ks-btn-primary" data-bs-toggle="modal" data-bs-target="#newMaintenanceModal">
            <i class="fas fa-plus me-1"></i> New Ticket
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
                    <th style="width: 25%;">Issue</th>
                    <th style="width: 15%;">Venue ID</th>
                    <th style="width: 15%;">Priority</th>
                    <th style="width: 15%;">Status</th>
                </tr>
            </thead>
            <tbody id="ticketsTableBody">
                <!-- Loaded via JS -->
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="newMaintenanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--ks-radius-modal); border: 1px solid var(--ks-border); box-shadow: var(--ks-shadow-modal);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy" style="font-size: 18px;">Create Maintenance Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <form id="maintenanceForm">
                    <div class="mb-3">
                        <label class="ks-form-label">Venue ID *</label>
                        <input type="number" class="ks-form-control" name="venue_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="ks-form-label">Issue Title *</label>
                        <input type="text" class="ks-form-control" name="issue_title" required>
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
                <button type="button" class="ks-btn ks-btn-primary" onclick="saveTicket()">Create</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadTickets);

function loadTickets() {
    fetch('/api/v1/maintenance')
        .then(res => res.json())
        .then(res => {
            const tbody = document.getElementById('ticketsTableBody');
            tbody.innerHTML = '';
            if (res.data && res.data.data) {
                res.data.data.forEach((t, index) => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><span class="fw-medium text-navy">${t.maintenance_reference}</span></td>
                            <td>${t.issue_title}</td>
                            <td>${t.venue_id}</td>
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

function saveTicket() {
    const form = document.getElementById('maintenanceForm');
    const data = Object.fromEntries(new FormData(form).entries());
    
    fetch('/api/v1/maintenance', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(res => res.json()).then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('newMaintenanceModal')).hide();
            form.reset();
            loadTickets();
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
