<?php
$activePage = 'transport';
$title = 'Transport Management — KhelSutra';
ob_start();
?>
?>
<div class="ks-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="ks-page-title mb-1">Transport <h2 class="ks-header-title">Transport & Logistics</h2> Logistics</h2>
            <p class="ks-page-subtitle">Manage fleet vehicles, trip scheduling, and passenger logistics</p>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-blue"><i class="bi bi-truck"></i></div>
                <div>
                    <div class="ks-kpi-label">TOTAL FLEET</div>
                    <div class="ks-kpi-value" id="kpi-total">0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-green"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="ks-kpi-label">AVAILABLE</div>
                    <div class="ks-kpi-value" id="kpi-available">0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-amber"><i class="bi bi-geo-alt-fill"></i></div>
                <div>
                    <div class="ks-kpi-label">IN TRANSIT</div>
                    <div class="ks-kpi-value" id="kpi-transit">0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="ks-kpi-card">
            <div class="ks-kpi-top">
                <div class="ks-icon-box ks-icon-red"><i class="bi bi-tools"></i></div>
                <div>
                    <div class="ks-kpi-label">MAINTENANCE</div>
                    <div class="ks-kpi-value" id="kpi-maintenance">0</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- LEFT COLUMN: Fleet -->
    <div class="col-lg-6">
        <div class="ks-content-card">
            <div class="ks-card-header">
                <div class="ks-header-left">
                    <i class="bi bi-truck" style="color:var(--ks-primary);font-size:18px;"></i>
                    <h3 class="ks-header-title">Fleet Management</h3>
                </div>
                <button class="ks-btn ks-btn-primary" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                    <i class="bi bi-plus-lg"></i> Add Vehicle
                </button>
            </div>
            <div id="fleetContainer" class="d-flex flex-column gap-3 p-3">
                <!-- Vehicles will be populated here -->
                <div class="ks-empty-state">Loading fleet...</div>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Trips -->
    <div class="col-lg-6">
        <div class="ks-content-card">
            <div class="ks-card-header">
                <div class="ks-header-left">
                    <i class="bi bi-map" style="color:var(--ks-primary);font-size:18px;"></i>
                    <h3 class="ks-header-title mb-0">Trip Schedule</h3>
                    <span class="ks-badge ks-badge-scheduled ms-2" id="trip-count">0</span>
                </div>
            </div>
            <div id="tripsContainer" class="d-flex flex-column gap-3 p-3">
                <!-- Trips will be populated here -->
                <div class="ks-empty-state">Loading trips...</div>
            </div>
        </div>
    </div>
</div>

<!-- Add Vehicle Modal -->
<div class="modal fade" id="addVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: var(--ks-radius-modal); border: 1px solid var(--ks-border); box-shadow: var(--ks-shadow-modal);">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addVehicleForm" onsubmit="addVehicle(event)">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Vehicle Number *</label>
                            <input type="text" class="ks-form-control" id="v_number" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vehicle Type *</label>
                            <input type="text" class="ks-form-control" id="v_type" placeholder="e.g. Bus, Van, SUV" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Make</label>
                            <input type="text" class="ks-form-control" id="v_make">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Model</label>
                            <input type="text" class="ks-form-control" id="v_model">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Year</label>
                            <input type="number" class="ks-form-control" id="v_year" min="1990" max="2100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Capacity (Passengers)</label>
                            <input type="number" class="ks-form-control" id="v_capacity" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Driver Employee ID</label>
                            <input type="number" class="ks-form-control" id="v_driver">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance Expiry Date</label>
                            <input type="date" class="ks-form-control" id="v_insurance_expiry">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Registration Expiry Date</label>
                            <input type="date" class="ks-form-control" id="v_registration_expiry">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="ks-form-select" id="v_status">
                                <option value="available">Available</option>
                                <option value="assigned">Assigned</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="ks-form-control" id="v_notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ks-btn ks-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ks-btn ks-btn-primary">Save Vehicle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Plan Trip Modal -->
<div class="modal fade" id="planTripModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: var(--ks-radius-modal); border: 1px solid var(--ks-border); box-shadow: var(--ks-shadow-modal);">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Plan Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="planTripForm" onsubmit="planTrip(event)">
                <div class="modal-body">
                    <div id="planTripConflictError" class="alert alert-danger d-none" role="alert"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Vehicle *</label>
                            <select class="ks-form-select" id="pt_vehicle_id" required>
                                <option value="">Select a vehicle...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Trip Date *</label>
                            <input type="date" class="ks-form-control" id="pt_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Origin *</label>
                            <input type="text" class="ks-form-control" id="pt_origin" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Destination *</label>
                            <input type="text" class="ks-form-control" id="pt_destination" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Departure Time</label>
                            <input type="time" class="ks-form-control" id="pt_departure">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Return Time</label>
                            <input type="time" class="ks-form-control" id="pt_return">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Purpose *</label>
                            <input type="text" class="ks-form-control" id="pt_purpose" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Driver Employee ID</label>
                            <input type="number" class="ks-form-control" id="pt_driver">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Event ID</label>
                            <input type="number" class="ks-form-control" id="pt_event_id">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="ks-form-control" id="pt_notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ks-btn ks-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ks-btn ks-btn-primary">Schedule Trip</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Track Vehicle Modal -->
<div class="modal fade" id="trackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--ks-radius-modal); border: 1px solid var(--ks-border); box-shadow: var(--ks-shadow-modal);">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Live Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="trackDetails">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Locating vehicle...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="ks-btn ks-btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Trip Drawer Overlay -->
<div id="tripDrawer-overlay" onclick="ksDrawerClose('tripDrawer')"
     style="position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1040;opacity:0;visibility:hidden;transition:opacity .3s;"></div>
<!-- Trip Drawer -->
<div id="tripDrawer"
     style="position:fixed;top:0;right:0;width:520px;height:100vh;background:#fff;z-index:1050;
            transform:translateX(100%);visibility:hidden;transition:transform .3s ease;
            overflow-y:auto;box-shadow:-4px 0 24px rgba(0,0,0,.12);">
    <div style="padding:24px 24px 0;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--ks-border);padding-bottom:16px;margin-bottom:20px;">
        <div>
            <h5 style="font-weight:700;color:var(--ks-navy);margin:0;" id="drawerTripTitle">Trip Details</h5>
            <div id="drawerTripStatus" class="mt-1"></div>
        </div>
        <button class="btn-close" onclick="ksDrawerClose('tripDrawer')"></button>
    </div>
    <div style="padding:0 24px 24px;" id="drawerContent">
        <!-- Drawer content injected via JS -->
    </div>
</div>

<style>
.ks-vehicle-card {
    border: 1px solid var(--ks-border);
    border-radius: var(--ks-radius-modal);
    padding: 16px;
    background: #fff;
}
.ks-trip-card {
    border: 1px solid var(--ks-border);
    border-radius: var(--ks-radius-modal);
    padding: 16px;
    background: #fdfdfd;
}
</style>

<script>
let globalVehicles = [];
let globalTrips = [];
let currentTripId = null;

document.addEventListener('DOMContentLoaded', () => {
    loadVehicles();
    loadTrips();
});

function getBadgeClassForVehicleStatus(status) {
    if(status === 'available') return 'ks-badge-confirmed';
    if(status === 'assigned') return 'ks-badge-scheduled';
    if(status === 'maintenance') return 'ks-badge-pending';
    return 'ks-badge-cancelled';
}

function getBadgeClassForTripStatus(status) {
    if(status === 'planned') return 'ks-badge-scheduled';
    if(status === 'in_progress') return 'ks-badge-pending';
    if(status === 'completed') return 'ks-badge-confirmed';
    if(status === 'cancelled') return 'ks-badge-cancelled';
    return 'ks-badge-scheduled';
}

function isExpiringSoon(dateString) {
    if (!dateString) return false;
    const expiry = new Date(dateString);
    const today = new Date();
    const diffTime = expiry - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays >= 0 && diffDays <= 30;
}

async function loadVehicles() {
    try {
        const res = await fetch('/api/v1/vehicles');
        if(!res.ok) throw new Error('Failed to load vehicles');
        const json = await res.json();
        globalVehicles = json.data?.data || json.data || [];
        
        let availableCount = 0;
        let transitCount = 0; // mapping assigned/in_use to transit for KPI
        let maintenanceCount = 0;
        
        const container = document.getElementById('fleetContainer');
        const select = document.getElementById('pt_vehicle_id');
        select.innerHTML = '<option value="">Select a vehicle...</option>';

        if(globalVehicles.length === 0) {
            container.innerHTML = '<div class="ks-empty-state">No vehicles in fleet.</div>';
        } else {
            container.innerHTML = '';
            globalVehicles.forEach(v => {
                // KPIs
                if(v.status === 'available') availableCount++;
                if(v.status === 'assigned') transitCount++;
                if(v.status === 'maintenance') maintenanceCount++;

                // Select
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.textContent = `${v.vehicle_number} - ${v.vehicle_type}`;
                select.appendChild(opt);

                // UI Card
                const expiryBadges = [];
                if(isExpiringSoon(v.insurance_expiry_date)) {
                    expiryBadges.push(`<span class="ks-badge ks-badge-rejected ms-1">Insurance Expires Soon</span>`);
                }
                if(isExpiringSoon(v.registration_expiry_date)) {
                    expiryBadges.push(`<span class="ks-badge ks-badge-pending ms-1" style="background:#ffb020;color:#000;">Registration Expires Soon</span>`);
                }

                const cardHtml = `
                    <div class="ks-vehicle-card d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1 fw-bold"><i class="bi bi-truck me-2 text-primary"></i>${ksEscape(v.vehicle_number)}</h5>
                                <div class="text-muted small">${ksEscape(v.vehicle_type)} • ${ksEscape(v.make || '')} ${ksEscape(v.model || '')} ${v.year || ''}</div>
                            </div>
                            <div>
                                <span class="ks-badge ${getBadgeClassForVehicleStatus(v.status)}">${ksEscape(v.status.toUpperCase())}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="ks-badge bg-light text-dark border"><i class="bi bi-people"></i> ${v.capacity || 0}</span>
                            ${expiryBadges.join('')}
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-2">
                            <button class="ks-btn ks-btn-secondary ks-btn-sm" onclick="openTrackModal(${v.id})"><i class="bi bi-geo-alt-fill me-1"></i> Track</button>
                            <button class="ks-btn ks-btn-primary ks-btn-sm" onclick="openPlanTrip(${v.id})"><i class="bi bi-calendar-plus me-1"></i> Plan Trip</button>
                            <button class="ks-btn ks-btn-secondary ks-btn-sm text-danger" onclick="deleteVehicle(${v.id})"><i class="bi bi-trash me-1"></i> Delete</button>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', cardHtml);
            });
        }

        document.getElementById('kpi-total').textContent = globalVehicles.length;
        document.getElementById('kpi-available').textContent = availableCount;
        document.getElementById('kpi-transit').textContent = transitCount;
        document.getElementById('kpi-maintenance').textContent = maintenanceCount;
    } catch(err) {
        ksToast('Error loading fleet', 'error');
    }
}

async function loadTrips() {
    try {
        const res = await fetch('/api/v1/trips');
        if(!res.ok) throw new Error('Failed to load trips');
        const json = await res.json();
        globalTrips = json.data?.data || json.data || [];
        
        const container = document.getElementById('tripsContainer');
        document.getElementById('trip-count').textContent = globalTrips.length;

        if(globalTrips.length === 0) {
            container.innerHTML = '<div class="ks-empty-state">No scheduled trips.</div>';
        } else {
            container.innerHTML = '';
            globalTrips.forEach(t => {
                const passCount = t.passenger_count !== undefined ? t.passenger_count : '?';
                const v = globalVehicles.find(v => v.id == t.vehicle_id);
                const vName = v ? v.vehicle_number : 'Unknown Vehicle';

                const cardHtml = `
                    <div class="ks-trip-card d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-light text-dark border">REF: ${ksEscape(t.trip_reference || t.id)}</span>
                            <span class="text-muted small"><i class="bi bi-calendar"></i> ${ksEscape(t.trip_date)}</span>
                        </div>
                        <div class="fw-bold my-1">
                            ${ksEscape(t.origin)} <i class="bi bi-arrow-right text-muted mx-1"></i> ${ksEscape(t.destination)}
                        </div>
                        <div class="text-muted small mb-1">
                            <i class="bi bi-clock"></i> ${ksEscape(t.departure_time || 'TBD')} - ${ksEscape(t.return_time || 'TBD')}
                            <span class="ms-3"><i class="bi bi-info-circle"></i> ${ksEscape(t.purpose)}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="d-flex gap-2 align-items-center">
                                <span class="ks-badge ${getBadgeClassForTripStatus(t.status)}">${ksEscape(t.status.toUpperCase())}</span>
                                <span class="ks-badge bg-light text-dark border"><i class="bi bi-people"></i> ${passCount}</span>
                            </div>
                            <button class="ks-btn ks-btn-secondary ks-btn-sm" onclick="openTripDrawer(${t.id})"><i class="bi bi-eye"></i> Details</button>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', cardHtml);
            });
        }
    } catch(err) {
        ksToast('Error loading trips', 'error');
    }
}

async function addVehicle(e) {
    e.preventDefault();
    const form = e.target;
    if(!form.reportValidity()) return;

    const payload = {
        vehicle_number: document.getElementById('v_number').value,
        vehicle_type: document.getElementById('v_type').value,
        make: document.getElementById('v_make').value,
        model: document.getElementById('v_model').value,
        year: document.getElementById('v_year').value ? parseInt(document.getElementById('v_year').value) : null,
        capacity: document.getElementById('v_capacity').value ? parseInt(document.getElementById('v_capacity').value) : null,
        driver_employee_id: document.getElementById('v_driver').value ? parseInt(document.getElementById('v_driver').value) : null,
        insurance_expiry_date: document.getElementById('v_insurance_expiry').value || null,
        registration_expiry_date: document.getElementById('v_registration_expiry').value || null,
        status: document.getElementById('v_status').value,
        notes: document.getElementById('v_notes').value
    };

    try {
        const res = await fetch('/api/v1/vehicles', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        if(res.ok) {
            ksToast('Vehicle added successfully', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addVehicleModal')).hide();
            form.reset();
            loadVehicles();
        } else {
            ksToast('Failed to add vehicle', 'error');
        }
    } catch(err) {
        ksToast('Error adding vehicle', 'error');
    }
}

async function deleteVehicle(id) {
    if(!confirm('Are you sure you want to delete this vehicle?')) return;
    try {
        const res = await fetch(`/api/v1/vehicles/${id}`, { method: 'DELETE' });
        if(res.ok) {
            ksToast('Vehicle deleted', 'success');
            loadVehicles();
        } else {
            ksToast('Failed to delete vehicle', 'error');
        }
    } catch(err) {
        ksToast('Error deleting vehicle', 'error');
    }
}

function openPlanTrip(vehicleId) {
    const form = document.getElementById('planTripForm');
    form.reset();
    document.getElementById('planTripConflictError').classList.add('d-none');
    document.getElementById('pt_vehicle_id').value = vehicleId;
    new bootstrap.Modal(document.getElementById('planTripModal')).show();
}

async function planTrip(e) {
    e.preventDefault();
    const form = e.target;
    if(!form.reportValidity()) return;

    const payload = {
        vehicle_id: document.getElementById('pt_vehicle_id').value,
        trip_date: document.getElementById('pt_date').value,
        origin: document.getElementById('pt_origin').value,
        destination: document.getElementById('pt_destination').value,
        departure_time: document.getElementById('pt_departure').value || null,
        return_time: document.getElementById('pt_return').value || null,
        purpose: document.getElementById('pt_purpose').value,
        driver_employee_id: document.getElementById('pt_driver').value ? parseInt(document.getElementById('pt_driver').value) : null,
        event_id: document.getElementById('pt_event_id').value ? parseInt(document.getElementById('pt_event_id').value) : null,
        notes: document.getElementById('pt_notes').value,
        status: 'planned'
    };

    try {
        const res = await fetch('/api/v1/trips', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        
        if(res.status === 409) {
            const errDiv = document.getElementById('planTripConflictError');
            errDiv.textContent = 'Scheduling conflict: Vehicle is already booked for this time.';
            errDiv.classList.remove('d-none');
            return;
        }

        if(res.ok) {
            ksToast('Trip scheduled successfully', 'success');
            bootstrap.Modal.getInstance(document.getElementById('planTripModal')).hide();
            form.reset();
            loadTrips();
        } else {
            ksToast('Failed to schedule trip', 'error');
        }
    } catch(err) {
        ksToast('Error scheduling trip', 'error');
    }
}

async function openTrackModal(vehicleId) {
    new bootstrap.Modal(document.getElementById('trackModal')).show();
    const detailsDiv = document.getElementById('trackDetails');
    detailsDiv.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Locating vehicle...</p>
        </div>`;
    
    try {
        const res = await fetch(`/api/v1/vehicles/${vehicleId}/location`);
        if(res.ok) {
            const data = (await res.json()).data;
            if(data && data.latitude && data.longitude) {
                detailsDiv.innerHTML = `
                    <div class="mb-3">
                        <strong>Latitude:</strong> ${ksEscape(data.latitude)}<br>
                        <strong>Longitude:</strong> ${ksEscape(data.longitude)}<br>
                        <strong>Speed:</strong> ${ksEscape(data.speed || '0')} km/h<br>
                        <strong>Last Updated:</strong> ${ksEscape(data.updated_at || 'Recently')}
                    </div>
                    <a href="https://maps.google.com/?q=${data.latitude},${data.longitude}" target="_blank" class="ks-btn ks-btn-primary w-100">
                        <i class="bi bi-map"></i> Open in Google Maps
                    </a>
                `;
            } else {
                detailsDiv.innerHTML = `<div class="alert alert-warning">No live location available. GPS not reporting.</div>`;
            }
        } else {
            detailsDiv.innerHTML = `<div class="alert alert-warning">No live location available. GPS not reporting.</div>`;
        }
    } catch(err) {
        detailsDiv.innerHTML = `<div class="alert alert-danger">Error fetching location data.</div>`;
    }
}

function openTripDrawer(tripId) {
    currentTripId = tripId;
    const trip = globalTrips.find(t => t.id == tripId);
    if(!trip) return;

    document.getElementById('drawerTripTitle').textContent = `Trip: ${trip.trip_reference || trip.id}`;
    document.getElementById('drawerTripStatus').innerHTML = `<span class="ks-badge ${getBadgeClassForTripStatus(trip.status)}">${trip.status.toUpperCase()}</span>`;

    let actionButtons = '';
    if (trip.status === 'planned') {
        actionButtons = `
            <button class="ks-btn ks-btn-primary w-100 mb-2" onclick="updateTripStatus(${trip.id}, 'in_progress')"><i class="bi bi-play"></i> Start Trip</button>
            <button class="ks-btn ks-btn-secondary text-danger w-100" onclick="updateTripStatus(${trip.id}, 'cancelled')"><i class="bi bi-x-circle"></i> Cancel Trip</button>
        `;
    } else if (trip.status === 'in_progress') {
        actionButtons = `
            <button class="ks-btn ks-btn-primary w-100" onclick="updateTripStatus(${trip.id}, 'completed')"><i class="bi bi-check-circle"></i> Complete Trip</button>
        `;
    } else {
        actionButtons = `<div class="alert alert-secondary py-2 text-center mb-0">This trip is read-only.</div>`;
    }

    const html = `
        <div class="mb-4">
            <h6 class="fw-bold text-muted mb-2">Summary</h6>
            <div class="card p-3 shadow-sm border-0 bg-light">
                <div class="mb-2"><strong>Vehicle:</strong> ${ksEscape(globalVehicles.find(v => v.id == trip.vehicle_id)?.vehicle_number || trip.vehicle_id)}</div>
                <div class="mb-2"><strong>Date:</strong> ${ksEscape(trip.trip_date)}</div>
                <div class="mb-2"><strong>Route:</strong> ${ksEscape(trip.origin)} &rarr; ${ksEscape(trip.destination)}</div>
                <div class="mb-2"><strong>Times:</strong> ${ksEscape(trip.departure_time || 'N/A')} - ${ksEscape(trip.return_time || 'N/A')}</div>
                <div class="mb-2"><strong>Purpose:</strong> ${ksEscape(trip.purpose)}</div>
                <div><strong>Driver ID:</strong> ${trip.driver_employee_id || 'N/A'}</div>
            </div>
        </div>

        <div class="mb-4">
            <h6 class="fw-bold text-muted mb-2">Actions</h6>
            ${actionButtons}
        </div>

        <div class="mb-4">
            <h6 class="fw-bold text-muted mb-2">Passengers</h6>
            <div id="drawerPassengers" class="mb-3"><div class="spinner-border spinner-border-sm text-primary"></div> Loading passengers...</div>
            
            ${trip.status === 'planned' ? `
            <div class="card p-3 shadow-sm border-0 bg-light">
                <h6 class="fw-bold fs-6 mb-2">Add Passenger</h6>
                <form id="addPassengerForm" onsubmit="addPassenger(event)">
                    <div class="mb-2">
                        <select class="ks-form-select" id="p_type" onchange="togglePassengerType()">
                            <option value="athlete">Athlete</option>
                            <option value="employee">Employee</option>
                            <option value="coach">Coach</option>
                            <option value="name">External Name</option>
                        </select>
                    </div>
                    <div class="mb-2" id="p_id_container">
                        <input type="number" class="ks-form-control" id="p_id" placeholder="Enter ID" required>
                    </div>
                    <div class="mb-2 d-none" id="p_name_container">
                        <input type="text" class="ks-form-control" id="p_name" placeholder="Enter Full Name">
                    </div>
                    <button type="submit" class="ks-btn ks-btn-secondary ks-btn-sm w-100"><i class="bi bi-plus-lg"></i> Add Passenger</button>
                </form>
            </div>` : ''}
        </div>

        <div class="mb-2">
            <h6 class="fw-bold text-muted mb-2">Route History</h6>
            <button class="ks-btn ks-btn-secondary w-100" onclick="loadRouteHistory(${trip.id})">View Route History</button>
            <div id="drawerRouteHistory" class="mt-3"></div>
        </div>
    `;

    document.getElementById('drawerContent').innerHTML = html;
    ksDrawerOpen('tripDrawer');
    loadPassengers(trip.id);
}

function togglePassengerType() {
    const type = document.getElementById('p_type').value;
    const idContainer = document.getElementById('p_id_container');
    const nameContainer = document.getElementById('p_name_container');
    const idInput = document.getElementById('p_id');
    const nameInput = document.getElementById('p_name');

    if (type === 'name') {
        idContainer.classList.add('d-none');
        nameContainer.classList.remove('d-none');
        idInput.required = false;
        nameInput.required = true;
    } else {
        idContainer.classList.remove('d-none');
        nameContainer.classList.add('d-none');
        idInput.required = true;
        nameInput.required = false;
        idInput.placeholder = `Enter ${type.charAt(0).toUpperCase() + type.slice(1)} ID`;
    }
}

async function updateTripStatus(tripId, newStatus) {
    try {
        const payload = { status: newStatus };
        if(newStatus === 'completed') {
            const cost = prompt('Optional: Enter actual cost notes for this trip');
            if(cost) payload.notes = cost;
        }

        const res = await fetch(`/api/v1/trips/${tripId}`, {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        if(res.ok) {
            ksToast('Trip status updated', 'success');
            await loadTrips();
            openTripDrawer(tripId); // Refresh drawer
        } else {
            ksToast('Failed to update status', 'error');
        }
    } catch(err) {
        ksToast('Error updating status', 'error');
    }
}

async function loadPassengers(tripId) {
    const container = document.getElementById('drawerPassengers');
    try {
        const res = await fetch(`/api/v1/trips/${tripId}/passengers`);
        if(res.ok) {
            const data = await res.json();
            const pass = data.data || [];
            if(pass.length === 0) {
                container.innerHTML = '<div class="text-muted small">No passengers added yet.</div>';
            } else {
                let html = '<ul class="list-group list-group-flush border rounded">';
                pass.forEach(p => {
                    const identifier = p.passenger_type === 'name' ? p.passenger_name : `ID: ${p.athlete_id || p.employee_id || p.coach_id}`;
                    html += `<li class="list-group-item d-flex justify-content-between py-2 px-3 small">
                                <span><i class="bi bi-person me-2"></i>${ksEscape(identifier)}</span>
                                <span class="badge bg-secondary">${ksEscape(p.passenger_type)}</span>
                             </li>`;
                });
                html += '</ul>';
                container.innerHTML = html;
            }
        } else {
            container.innerHTML = '<div class="text-muted small alert alert-warning">Passenger list not available from this endpoint.</div>';
        }
    } catch(err) {
        container.innerHTML = '<div class="text-muted small alert alert-warning">Passenger list not available from this endpoint.</div>';
    }
}

async function addPassenger(e) {
    e.preventDefault();
    if(!currentTripId) return;
    const type = document.getElementById('p_type').value;
    
    const payload = { passenger_type: type };
    if(type === 'name') {
        payload.passenger_name = document.getElementById('p_name').value;
    } else if(type === 'athlete') {
        payload.athlete_id = document.getElementById('p_id').value;
    } else if(type === 'employee') {
        payload.employee_id = document.getElementById('p_id').value;
    } else if(type === 'coach') {
        payload.coach_id = document.getElementById('p_id').value;
    }

    try {
        const res = await fetch(`/api/v1/trips/${currentTripId}/passengers`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        if(res.ok) {
            ksToast('Passenger added', 'success');
            document.getElementById('addPassengerForm').reset();
            loadPassengers(currentTripId);
        } else {
            ksToast('Failed to add passenger', 'error');
        }
    } catch(err) {
        ksToast('Error adding passenger', 'error');
    }
}

async function loadRouteHistory(tripId) {
    const container = document.getElementById('drawerRouteHistory');
    container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
    
    try {
        const res = await fetch(`/api/v1/trips/${tripId}/route-history`);
        if(res.ok) {
            const data = await res.json();
            const history = data.data || [];
            if(history.length === 0) {
                container.innerHTML = '<div class="alert alert-secondary py-2 small">No route data available.</div>';
            } else {
                let html = '<div class="table-responsive"><table class="table table-sm table-bordered" style="font-size:0.85rem"><thead><tr><th>Time</th><th>Lat/Lng</th><th>Speed</th></tr></thead><tbody>';
                history.forEach(pos => {
                    html += `<tr>
                        <td>${ksEscape(pos.updated_at || pos.time || '')}</td>
                        <td>${ksEscape(pos.latitude)}, ${ksEscape(pos.longitude)}</td>
                        <td>${ksEscape(pos.speed || 0)} km/h</td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
                container.innerHTML = html;
            }
        } else {
            container.innerHTML = '<div class="alert alert-warning py-2 small">No route data available.</div>';
        }
    } catch(err) {
        container.innerHTML = '<div class="alert alert-danger py-2 small">Error loading route data.</div>';
    }
}

async function autoPlanTrip() {
    const origin = document.getElementById('planOrigin').value;
    const dest = document.getElementById('planDest').value;
    const date = document.getElementById('planDate').value;
    const eventId = document.getElementById('planEvent').value;
    
    if (!origin || !dest || !date) {
        ksToast('Please fill origin, destination, and date first', 'error');
        return;
    }
    
    const res = await fetch('/api/v1/trips/auto-plan', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify({
            origin: origin,
            destination: dest,
            trip_date: date,
            event_id: eventId,
            seat_buffer: 0
        })
    });
    
    const json = await res.json();
    if(json.success && json.data.plans) {
        const plan = json.data.plans[0];
        if (plan) {
            document.getElementById('autoPlanResult').innerHTML = `
                <strong>Auto-plan success:</strong> Assigning ${plan.vehicles.length} vehicle(s). Excess seats: ${plan.excess_seats}.
                <input type="hidden" id="planAutoId" value="${plan.plan_id}">
            `;
            ksToast('Auto-plan applied', 'success');
        } else if (json.data.shortfall) {
            document.getElementById('autoPlanResult').innerHTML = `
                <strong class="text-danger">Shortfall:</strong> Need ${json.data.shortfall} more seats.
            `;
        }
    } else {
        ksToast('Auto-plan failed: ' + (json.message || ''), 'error');
    }
}

</script>
<?php
$slot = ob_get_clean();
include __DIR__ . '/../../layouts/app.blade.php';
?>
