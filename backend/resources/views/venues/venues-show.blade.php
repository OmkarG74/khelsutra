<?php
$venueId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();
$venueService = new \App\Services\Venue\VenueService();
$venue = $venueService->getVenue($orgId, $venueId);

$pageTitle = $venue ? htmlspecialchars($venue['name'] . ' — Venue Details') : 'Venue Details';
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
            <a href="/venues" class="text-decoration-none text-muted small fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Venues
            </a>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: #EDE9FE; color: #7C3AED; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="h4 fw-bold mb-0" style="color: var(--ks-navy);"><?= htmlspecialchars($venue['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
                        <span class="badge <?= ($venue['status'] ?? 'active') === 'active' ? 'badge-success' : 'badge-secondary' ?>" style="border-radius: 12px; font-size: 11px; padding: 4px 10px; text-transform: capitalize;">
                            <?= htmlspecialchars(str_replace('_', ' ', $venue['status'] ?? 'active'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="text-muted small mt-1">
                        Code: <strong style="color: var(--ks-text);"><?= htmlspecialchars($venue['venue_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull;
                        Type: <strong style="color: var(--ks-text);"><?= htmlspecialchars($venue['venue_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> &bull;
                        City: <strong style="color: var(--ks-text);"><?= htmlspecialchars($venue['city'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="/venues/bookings/create?venue_id=<?= (int)$venue['id'] ?>" class="btn btn-outline-primary d-inline-flex align-items-center gap-2" style="border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 8px 18px;">
                    <i class="bi bi-calendar-plus"></i> Book Slot
                </a>
                <a href="/venues/<?= (int)$venue['id'] ?>/edit" class="btn btn-primary d-inline-flex align-items-center gap-2" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 8px 18px;">
                    <i class="bi bi-pencil-square"></i> Edit Venue
                </a>
            </div>
        </div>

        <div class="row g-3">
            <!-- Left Column: Facilities & Bookings -->
            <div class="col-lg-8">
                <!-- Facilities List & Add Facility Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h5 class="fw-bold mb-0" style="color: var(--ks-navy); font-size: 15px;">
                            <i class="bi bi-grid me-2" style="color: var(--ks-blue);"></i> Facility Slots & Courts (<?= count($venue['facilities'] ?? []) ?>)
                        </h5>
                    </div>

                    <?php if (!empty($venue['facilities']) && count($venue['facilities']) > 0): ?>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                                <thead style="background: var(--ks-page-bg);">
                                    <tr>
                                        <th>Facility / Court Name</th>
                                        <th>Type</th>
                                        <th>Capacity</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($venue['facilities'] as $fac): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($fac['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars($fac['facility_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-muted small"><?= $fac['capacity'] ? number_format($fac['capacity']) . ' players' : '—' ?></td>
                                            <td>
                                                <span class="badge <?= ($fac['status'] ?? 'active') === 'active' ? 'badge-success' : 'badge-secondary' ?>" style="font-size: 10px;">
                                                    <?= htmlspecialchars($fac['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="/venues/bookings/create?venue_id=<?= (int)$venue['id'] ?>&facility_id=<?= (int)$fac['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 11px;">
                                                    Reserve Slot
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-4">No individual court or facility slots defined yet.</p>
                    <?php endif; ?>

                    <!-- Add Facility Form -->
                    <div class="card p-3" style="background: var(--ks-page-bg); border: 1px dashed var(--ks-border); border-radius: var(--ks-radius-button);">
                        <h6 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 13px;">+ Add New Facility / Court Slot</h6>
                        <form action="/venues/<?= (int)$venue['id'] ?>/facilities/create" method="POST" class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label small text-muted mb-1">Facility Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Synthetic Badminton Court 1" required style="font-size: 12px;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted mb-1">Facility Type</label>
                                <input type="text" name="facility_type" class="form-control form-control-sm" placeholder="e.g. Wooden Court, Turf" value="Court" style="font-size: 12px;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Capacity</label>
                                <input type="number" name="capacity" class="form-control form-control-sm" placeholder="e.g. 10" style="font-size: 12px;">
                            </div>
                            <div class="col-12 text-end mt-2">
                                <button type="submit" class="btn btn-sm btn-primary" style="background: var(--ks-blue); border-color: var(--ks-blue); font-size: 12px; padding: 5px 16px;">
                                    <i class="bi bi-plus-lg me-1"></i> Add Facility
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Active & Upcoming Bookings -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h5 class="fw-bold mb-0" style="color: var(--ks-navy); font-size: 15px;">
                            <i class="bi bi-calendar-check me-2" style="color: var(--ks-blue);"></i> Active & Upcoming Bookings (<?= count($venue['bookings'] ?? []) ?>)
                        </h5>
                        <a href="/venues/bookings/create?venue_id=<?= (int)$venue['id'] ?>" class="btn btn-sm btn-primary" style="background: var(--ks-blue); border-color: var(--ks-blue); font-size: 11px;">
                            + New Booking
                        </a>
                    </div>

                    <?php if (!empty($venue['bookings']) && count($venue['bookings']) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                                <thead style="background: var(--ks-page-bg);">
                                    <tr>
                                        <th>Booking Ref</th>
                                        <th>Facility Slot</th>
                                        <th>Date & Slot</th>
                                        <th>Purpose / Squad</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($venue['bookings'] as $bkg): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark" style="font-family: monospace; font-size: 11px;">
                                                <?= htmlspecialchars($bkg['booking_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td class="fw-medium text-dark"><?= htmlspecialchars($bkg['facility_name'] ?? 'Main Arena', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-muted small">
                                                <div><?= date('M d, Y', strtotime($bkg['booking_date'])) ?></div>
                                                <div style="font-size: 11px;"><?= htmlspecialchars(substr($bkg['start_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars(substr($bkg['end_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?></div>
                                            </td>
                                            <td class="text-muted small">
                                                <div class="text-dark fw-medium"><?= htmlspecialchars($bkg['purpose'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php if (!empty($bkg['team_name'])): ?><span class="badge bg-light text-secondary border px-1" style="font-size: 10px;"><?= htmlspecialchars($bkg['team_name'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-success-subtle text-success" style="font-size: 10px; text-transform: capitalize;">
                                                    <?= htmlspecialchars($bkg['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No active bookings recorded for this venue.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Location & Timing Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-clock me-2" style="color: var(--ks-blue);"></i> Operational Timings
                    </h5>
                    <div class="row g-2 small">
                        <div class="col-6">
                            <div class="text-muted">Opening Time</div>
                            <div class="fw-semibold text-dark mt-1"><?= htmlspecialchars(substr($venue['opening_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">Closing Time</div>
                            <div class="fw-semibold text-dark mt-1"><?= htmlspecialchars(substr($venue['closing_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="text-muted">Address</div>
                            <div class="fw-medium text-dark mt-1">
                                <?= htmlspecialchars($venue['address_line1'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($venue['city'])): ?>, <?= htmlspecialchars($venue['city'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                <?php if (!empty($venue['state'])): ?>, <?= htmlspecialchars($venue['state'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($venue['capacity'])): ?>
                            <div class="col-12 mt-2">
                                <div class="text-muted">Total Spectator Capacity</div>
                                <div class="fw-semibold text-dark mt-1"><?= number_format($venue['capacity']) ?> Seats</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Maintenance Logs Card -->
                <div class="card p-4 mb-3" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                    <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                        <i class="bi bi-tools me-2" style="color: var(--ks-blue);"></i> Maintenance Status
                    </h5>
                    <?php if (!empty($venue['maintenance']) && count($venue['maintenance']) > 0): ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($venue['maintenance'] as $mnt): ?>
                                <div class="p-2 border rounded" style="background: var(--ks-page-bg);">
                                    <div class="d-flex justify-content-between">
                                        <strong class="small text-dark"><?= htmlspecialchars($mnt['issue_title'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span class="badge bg-light text-secondary border small"><?= htmlspecialchars($mnt['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="text-muted" style="font-size: 11px;">Scheduled: <?= htmlspecialchars($mnt['scheduled_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">All facilities are in good operational standing.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
