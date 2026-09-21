<?php
$venueId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();
$venueService = new \App\Services\Venue\VenueService();
$venue = $venueService->getVenue($orgId, $venueId);

$pageTitle = $venue ? 'Edit Venue — ' . htmlspecialchars($venue['name']) : 'Edit Venue';
$activePage = 'venues';

ob_start();
?>

<div class="ks-content">
    <?php if (!$venue): ?>
        <div class="card p-5 text-center" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <div class="mb-3"><i class="bi bi-geo-alt-fill fs-1 text-muted"></i></div>
            <h4 class="fw-bold" style="color: var(--ks-navy);">Venue Not Found</h4>
            <p class="text-muted small">The requested venue facility does not exist or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/venues" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Venues
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="/venues/<?= (int)$venue['id'] ?>" class="text-decoration-none text-muted small fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Venue Details
            </a>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h4 fw-bold mb-0" style="color: var(--ks-navy);">Edit Venue: <?= htmlspecialchars($venue['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="text-muted small mt-1">Code: <strong><?= htmlspecialchars($venue['venue_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></div>
            </div>
        </div>

        <form action="/venues/<?= (int)$venue['id'] ?>/edit" method="POST" id="editVenueForm">
            <!-- Section 1: Venue Information -->
            <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                    1. Venue Profile & Details
                </h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Venue Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($venue['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Venue Type <span class="text-danger">*</span></label>
                        <select name="venue_type" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <?php foreach (['Sports Complex', 'Stadium', 'Indoor Arena', 'Training Ground', 'Swimming Pool', 'Badminton Hall'] as $vt): ?>
                                <option value="<?= $vt ?>" <?= ($venue['venue_type'] ?? '') === $vt ? 'selected' : '' ?>><?= $vt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Spectator Capacity</label>
                        <input type="number" name="capacity" class="form-control" value="<?= htmlspecialchars($venue['capacity'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Opening Time <span class="text-danger">*</span></label>
                        <input type="time" name="opening_time" class="form-control" value="<?= htmlspecialchars(substr($venue['opening_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Closing Time <span class="text-danger">*</span></label>
                        <input type="time" name="closing_time" class="form-control" value="<?= htmlspecialchars(substr($venue['closing_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Operating Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="active" <?= ($venue['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($venue['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="under_maintenance" <?= ($venue['status'] ?? '') === 'under_maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold text-dark">Description</label>
                        <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($venue['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
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
                        <input type="text" name="address_line1" class="form-control" value="<?= htmlspecialchars($venue['address_line1'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">City <span class="text-danger">*</span></label>
                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($venue['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">State <span class="text-danger">*</span></label>
                        <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($venue['state'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Postal Code</label>
                        <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($venue['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
                <a href="/venues/<?= (int)$venue['id'] ?>" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                    <i class="bi bi-check2 me-1"></i> Save Changes
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
