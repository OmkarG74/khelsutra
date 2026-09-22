<?php
$title = "Facilities";
$pageHeader = "Venue Facilities";
$pageSubheader = "Manage specific areas within a venue.";
ob_start();
// In real app we'd fetch venue name based on $venueId passed via view data
$venueId = $data['venueId'] ?? 0;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="/operations/venues" class="text-decoration-none text-muted mb-2 d-inline-block">
            <i class="fas fa-arrow-left me-1"></i> Back to Venues
        </a>
    </div>
    <div class="d-flex gap-2">
        <button class="ks-btn ks-btn-primary" data-bs-toggle="modal" data-bs-target="#newFacilityModal">
            <i class="fas fa-plus me-1"></i> Add Facility
        </button>
    </div>
</div>

<div class="ks-card" style="padding: 0;">
    <div class="table-responsive">
        <table class="table ks-table mb-0">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Facility Name</th>
                    <th style="width: 20%;">Type</th>
                    <th style="width: 15%;">Capacity</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 15%; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="facilitiesTableBody">
                <!-- Data loaded via JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for New Facility -->
<div class="modal fade" id="newFacilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--ks-radius-modal); border: 1px solid var(--ks-border); box-shadow: var(--ks-shadow-modal);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy" style="font-size: 18px;">Add New Facility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <form id="facilityForm">
                    <div class="mb-3">
                        <label class="ks-form-label">Facility Name *</label>
                        <input type="text" class="ks-form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="ks-form-label">Facility Type</label>
                        <input type="text" class="ks-form-control" name="facility_type" placeholder="e.g. Indoor Court, Swimming Pool">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="ks-form-label">Capacity</label>
                            <input type="number" class="ks-form-control" name="capacity">
                        </div>
                        <div class="col-6">
                            <label class="ks-form-label">Status *</label>
                            <select class="ks-form-select" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="under_maintenance">Under Maintenance</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="ks-btn ks-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="ks-btn ks-btn-primary" onclick="saveFacility()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
const venueId = <?= json_encode($venueId) ?>;

document.addEventListener('DOMContentLoaded', loadFacilities);

function loadFacilities() {
    fetch('/api/v1/venues/' + venueId + '/facilities')
        .then(res => res.json())
        .then(res => {
            const tbody = document.getElementById('facilitiesTableBody');
            tbody.innerHTML = '';
            if (res.data && res.data.data) {
                res.data.data.forEach((f, index) => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><div class="fw-semibold text-navy">${f.name}</div></td>
                            <td>${f.facility_type || '-'}</td>
                            <td>${f.capacity || '-'}</td>
                            <td>
                                <span class="ks-badge ks-badge-${f.status === 'active' ? 'success' : 'warning'}">
                                    ${f.status}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <button onclick="deleteFacility(${f.id})" class="ks-btn ks-btn-secondary text-danger" style="height: 32px; padding: 0 10px; font-size: 12px;">Del</button>
                            </td>
                        </tr>
                    `;
                });
            }
        });
}

function saveFacility() {
    const form = document.getElementById('facilityForm');
    const data = Object.fromEntries(new FormData(form).entries());
    
    fetch('/api/v1/venues/' + venueId + '/facilities', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(res => res.json()).then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('newFacilityModal')).hide();
            form.reset();
            loadFacilities();
        } else {
            alert('Error: ' + JSON.stringify(res.errors || res.message));
        }
    });
}

function deleteFacility(id) {
    if (confirm("Are you sure you want to delete this facility?")) {
        fetch('/api/v1/venues/' + venueId + '/facilities/' + id, { method: 'DELETE' })
            .then(res => res.json())
            .then(res => {
                if (res.success) loadFacilities();
                else alert(res.message);
            });
    }
}
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../../layouts/app.blade.php';
?>
