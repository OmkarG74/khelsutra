<?php
$pageTitle = 'Add Venue — KhelSutra';
$activePage = 'venues';
$orgId = current_organization_id();

ob_start();
?>

<div class="ks-content">
    <!-- Top Back Navigation -->
    <div class="mb-3">
        <a href="/venues" class="text-decoration-none text-muted small fw-medium">
            <i class="bi bi-arrow-left me-1"></i> Back to Venues
        </a>
    </div>

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold mb-0" style="color: var(--ks-navy);">Add Venue</h1>
        </div>
    </div>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger mb-4 py-2 px-3 small d-flex align-items-center gap-2" style="border-radius: var(--ks-radius-button);">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <form action="/venues/create" method="POST" id="createVenueForm">
        <!-- Section 1: Venue Information -->
        <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                1. Venue Profile & Details
            </h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Venue Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Apex Olympic Sports Arena" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Venue Type <span class="text-danger">*</span></label>
                    <select name="venue_type" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="Sports Complex">Sports Complex</option>
                        <option value="Stadium">Stadium</option>
                        <option value="Indoor Arena">Indoor Arena</option>
                        <option value="Training Ground">Training Ground</option>
                        <option value="Swimming Pool">Swimming Complex</option>
                        <option value="Badminton Hall">Badminton Hall</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Spectator Capacity</label>
                    <input type="number" name="capacity" class="form-control" placeholder="e.g. 5000" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Opening Time <span class="text-danger">*</span></label>
                    <input type="time" name="opening_time" class="form-control" value="06:00" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Closing Time <span class="text-danger">*</span></label>
                    <input type="time" name="closing_time" class="form-control" value="22:00" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Operating Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="under_maintenance">Under Maintenance</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-semibold text-dark">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Main features, floodlights, turf quality" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
            </div>
        </div>

        <!-- Section 2: Address & Location -->
        <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                2. Location & Address
            </h5>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-semibold text-dark">Address Line</label>
                    <input type="text" name="address_line1" class="form-control" placeholder="Plot 10, Sports City Complex" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">City <span class="text-danger">*</span></label>
                    <input type="text" name="city" class="form-control" value="Mumbai" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">State <span class="text-danger">*</span></label>
                    <input type="text" name="state" class="form-control" value="Maharashtra" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Postal Code</label>
                    <input type="text" name="postal_code" class="form-control" value="400001" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
            </div>
        </div>

        <!-- Section 3: Initial Facility Slot -->
        <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                3. Primary Facility Slot (Optional Initial Field / Court)
            </h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Facility Name</label>
                    <input type="text" name="facility_name" class="form-control" placeholder="e.g. Main Turf Pitch 1" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-dark">Facility Type</label>
                    <input type="text" name="facility_type" class="form-control" placeholder="e.g. Grass Turf, Wooden Court" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-dark">Slot Capacity</label>
                    <input type="number" name="facility_capacity" class="form-control" placeholder="e.g. 50" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
            <a href="/venues" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                <i class="bi bi-check2 me-1"></i> Save Venue
            </button>
        </div>
    </form>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
