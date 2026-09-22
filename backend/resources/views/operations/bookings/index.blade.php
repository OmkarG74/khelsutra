<?php
$title = "Bookings";
$pageHeader = "Venue Bookings";
$pageSubheader = "Manage facility reservations and event schedules.";
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2">
        <input type="date" class="ks-form-control" id="filterDate" onchange="loadBookings()">
    </div>
    <div class="d-flex gap-2">
        <button class="ks-btn ks-btn-primary" data-bs-toggle="modal" data-bs-target="#newBookingModal">
            <i class="fas fa-plus me-1"></i> New Booking
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
                    <th style="width: 20%;">Date & Time</th>
                    <th style="width: 25%;">Venue / Facility</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 20%; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="bookingsTableBody">
                <!-- Data loaded via JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for New Booking -->
<div class="modal fade" id="newBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--ks-radius-modal); border: 1px solid var(--ks-border); box-shadow: var(--ks-shadow-modal);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy" style="font-size: 18px;">Create Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <form id="bookingForm">
                    <input type="hidden" name="booked_by_user_id" value="1"> <!-- Mock user -->
                    <div class="mb-3">
                        <label class="ks-form-label">Venue ID *</label>
                        <input type="number" class="ks-form-control" name="venue_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="ks-form-label">Facility ID (Optional)</label>
                        <input type="number" class="ks-form-control" name="facility_id">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <label class="ks-form-label">Date *</label>
                            <input type="date" class="ks-form-control" name="booking_date" required>
                        </div>
                        <div class="col-4">
                            <label class="ks-form-label">Start Time *</label>
                            <input type="time" class="ks-form-control" name="start_time" required>
                        </div>
                        <div class="col-4">
                            <label class="ks-form-label">End Time *</label>
                            <input type="time" class="ks-form-control" name="end_time" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="ks-btn ks-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="ks-btn ks-btn-primary" onclick="saveBooking()">Book</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadBookings);

function loadBookings() {
    let url = '/api/v1/bookings';
    let date = document.getElementById('filterDate').value;
    if (date) url += '?booking_date=' + date;

    fetch(url)
        .then(res => res.json())
        .then(res => {
            const tbody = document.getElementById('bookingsTableBody');
            tbody.innerHTML = '';
            if (res.data && res.data.data) {
                const escapeHtml = (unsafe) => {
                    if (unsafe == null) return '';
                    return String(unsafe)
                         .replace(/&/g, "&amp;")
                         .replace(/</g, "&lt;")
                         .replace(/>/g, "&gt;")
                         .replace(/"/g, "&quot;")
                         .replace(/'/g, "&#039;");
                };
                res.data.data.forEach((b, index) => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><span class="fw-medium text-navy">${escapeHtml(b.booking_reference)}</span></td>
                            <td>${escapeHtml(b.booking_date)} <br><small class="text-muted">${escapeHtml(b.start_time)} - ${escapeHtml(b.end_time)}</small></td>
                            <td>Venue: ${escapeHtml(b.venue_id)} <br><small class="text-muted">Facility: ${escapeHtml(b.facility_id) || 'All'}</small></td>
                            <td>
                                <span class="ks-badge ks-badge-${b.status === 'approved' ? 'success' : (b.status === 'cancelled' ? 'danger' : 'warning')}">
                                    ${escapeHtml(b.status)}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                ${b.status !== 'cancelled' ? `<button onclick="cancelBooking(${b.id})" class="ks-btn ks-btn-secondary text-danger" style="height: 32px; padding: 0 10px; font-size: 12px;">Cancel</button>` : ''}
                            </td>
                        </tr>
                    `;
                });
            }
        });
}

function saveBooking() {
    const form = document.getElementById('bookingForm');
    const data = Object.fromEntries(new FormData(form).entries());
    
    // Check availability first
    fetch(`/api/v1/venues/${data.venue_id}/availability?date=${data.booking_date}&start_time=${data.start_time}&end_time=${data.end_time}&facility_id=${data.facility_id}`)
        .then(res => res.json())
        .then(res => {
            if (res.data && res.data.available) {
                // Proceed to book
                fetch('/api/v1/bookings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                }).then(r => r.json()).then(r => {
                    if (r.success) {
                        bootstrap.Modal.getInstance(document.getElementById('newBookingModal')).hide();
                        form.reset();
                        loadBookings();
                    } else {
                        alert('Error: ' + JSON.stringify(r.errors || r.message));
                    }
                });
            } else {
                alert('Conflict: ' + (res.data ? res.data.conflict : 'Unknown error'));
            }
        });
}

function cancelBooking(id) {
    if (confirm("Are you sure you want to cancel this booking?")) {
        fetch('/api/v1/bookings/' + id + '/cancel', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({reason: 'User cancelled'}) })
            .then(res => res.json())
            .then(res => {
                if (res.success) loadBookings();
                else alert(res.message);
            });
    }
}
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../../layouts/app.blade.php';
?>
