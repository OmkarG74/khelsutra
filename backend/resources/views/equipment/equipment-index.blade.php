<?php
$pageTitle = 'Equipment Tracking — KhelSutra';
$activePage = 'equipment';
$orgId = current_organization_id();

$eqService = new \App\Services\Equipment\EquipmentService();
$page = (int)($_GET['page'] ?? 1);
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$itemId = !empty($_GET['inventory_item_id']) ? (int)$_GET['inventory_item_id'] : null;

$result = $eqService->listRentals($orgId, $page, 15, $search ?: null, $status ?: null, $itemId);
$rentalsList = $result['data'] ?? [];
$total = $result['total'] ?? 0;
$totalPages = $result['total_pages'] ?? 1;

$db = \App\Services\BaseService::getDatabaseConnection();

// KPI Stats
$statStmt = $db->prepare("
    SELECT
        COUNT(*) as total_rentals,
        SUM(CASE WHEN status IN ('issued', 'partially_returned') THEN borrowed_quantity - returned_quantity - damaged_quantity ELSE 0 END) as active_items_in_use,
        SUM(CASE WHEN status IN ('returned', 'returned_with_damage', 'lost') THEN 1 ELSE 0 END) as completed_issues
    FROM equipment_rentals
    WHERE organization_id = :org_id AND deleted_at IS NULL
");
$statStmt->execute([':org_id' => $orgId]);
$stats = $statStmt->fetch(\PDO::FETCH_ASSOC);

// Fetch inventory items for dropdowns (only physical stock items with active status)
$invStmt = $db->prepare("
    SELECT id, category_id, item_name, item_code, quantity
    FROM inventory_items
    WHERE organization_id = :org_id AND deleted_at IS NULL AND status = 'active'
    ORDER BY item_name ASC
");
$invStmt->execute([':org_id' => $orgId]);
$inventoryItems = $invStmt->fetchAll(\PDO::FETCH_ASSOC);

// Fetch categories for Equipment filtering
$catStmt = $db->prepare("SELECT id, name FROM inventory_categories WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
$catStmt->execute([':org_id' => $orgId]);
$categories = $catStmt->fetchAll(\PDO::FETCH_ASSOC);

// Fetch Entities for dropdowns
$athStmt = $db->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM athletes WHERE organization_id = :org_id AND deleted_at IS NULL ORDER BY first_name ASC");
$athStmt->execute([':org_id' => $orgId]);
$athletes = $athStmt->fetchAll(\PDO::FETCH_ASSOC);

$empStmt = $db->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM employees WHERE organization_id = :org_id AND deleted_at IS NULL ORDER BY first_name ASC");
$empStmt->execute([':org_id' => $orgId]);
$employees = $empStmt->fetchAll(\PDO::FETCH_ASSOC);

$coachStmt = $db->prepare("SELECT cp.id, CONCAT(emp.first_name, ' ', emp.last_name) as name FROM coach_profiles cp JOIN employees emp ON cp.employee_id = emp.id WHERE cp.organization_id = :org_id AND cp.deleted_at IS NULL ORDER BY emp.first_name ASC");
$coachStmt->execute([':org_id' => $orgId]);
$coaches = $coachStmt->fetchAll(\PDO::FETCH_ASSOC);

$teamStmt = $db->prepare("SELECT id, name FROM teams WHERE organization_id = :org_id AND deleted_at IS NULL ORDER BY name ASC");
$teamStmt->execute([':org_id' => $orgId]);
$teams = $teamStmt->fetchAll(\PDO::FETCH_ASSOC);

$venueStmt = $db->prepare("SELECT id, name FROM venues WHERE organization_id = :org_id AND deleted_at IS NULL ORDER BY name ASC");
$venueStmt->execute([':org_id' => $orgId]);
$venues = $venueStmt->fetchAll(\PDO::FETCH_ASSOC);

// Error/Success messages
$successMsg = $_GET['success'] ?? null;
$errorMsg = $_GET['error'] ?? null;
ob_start();
?>

<div class="ks-content">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1 text-gray-800">Equipment Tracking</h1>
                <p class="text-muted mb-0">Manage equipment borrowing and returns</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#issueModal">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Issue Equipment
                </button>
            </div>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($successMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($errorMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Issues</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format((float)($stats['total_rentals'] ?? 0)) ?></div>
                        </div>
                        <div class="col-auto"><i class="bi bi-journal-text fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Equipment In Use</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format((float)($stats['active_items_in_use'] ?? 0)) ?> items</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-box-arrow-right fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Returned</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format((float)($stats['completed_issues'] ?? 0)) ?></div>
                        </div>
                        <div class="col-auto"><i class="bi bi-check2-circle fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center bg-light">
            <h6 class="m-0 font-weight-bold text-primary">Issue/Return Ledger</h6>

            <form method="GET" action="/equipment" class="row g-2 align-items-center mt-2 mt-md-0">
                <div class="col-auto">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search item or borrower..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="issued" <?= $status === 'issued' ? 'selected' : '' ?>>Issued</option>
                        <option value="partially_returned" <?= $status === 'partially_returned' ? 'selected' : '' ?>>Partially Returned</option>
                        <option value="returned" <?= $status === 'returned' ? 'selected' : '' ?>>Returned (Good)</option>
                        <option value="returned_with_damage" <?= $status === 'returned_with_damage' ? 'selected' : '' ?>>Returned (Damaged)</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <?php if ($search || $status): ?>
                        <a href="/equipment" class="btn btn-secondary btn-sm"><i class="bi bi-x-circle"></i> Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 px-3 text-muted fw-semibold">ID</th>
                            <th class="py-3 px-3 text-muted fw-semibold">Item</th>
                            <th class="py-3 px-3 text-muted fw-semibold">Borrower</th>
                            <th class="py-3 px-3 text-muted fw-semibold">Qty</th>
                            <th class="py-3 px-3 text-muted fw-semibold">Date Issued</th>
                            <th class="py-3 px-3 text-muted fw-semibold">Status</th>
                            <th class="py-3 px-3 text-muted fw-semibold text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rentalsList)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fa-3x mb-3 d-block"></i>
                                    No equipment issues found matching your criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rentalsList as $rent): ?>
                                <?php
                                    $statusBadge = 'bg-secondary';
                                    if ($rent['status'] === 'issued') $statusBadge = 'bg-primary';
                                    elseif ($rent['status'] === 'partially_returned') $statusBadge = 'bg-warning text-dark';
                                    elseif ($rent['status'] === 'returned') $statusBadge = 'bg-success';
                                    elseif ($rent['status'] === 'returned_with_damage') $statusBadge = 'bg-danger';
                                ?>
                                <tr>
                                    <td class="px-3 fw-bold">#<?= $rent['id'] ?></td>
                                    <td class="px-3">
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($rent['item_name']) ?></div>
                                        <div class="text-xs text-muted"><?= htmlspecialchars($rent['item_code'] ?? 'N/A') ?></div>
                                    </td>
                                    <td class="px-3">
                                        <div class="fw-semibold"><?= htmlspecialchars($rent['resolved_borrower_name'] ?? 'Unknown') ?></div>
                                        <div class="text-xs text-muted">Type: <?= ucfirst(htmlspecialchars($rent['borrower_type'])) ?></div>
                                    </td>
                                    <td class="px-3">
                                        <div>Borrow: <strong><?= (float)$rent['borrowed_quantity'] ?></strong></div>
                                        <div class="text-xs text-success">Ret: <?= (float)$rent['returned_quantity'] ?></div>
                                        <div class="text-xs text-danger">Dam: <?= (float)$rent['damaged_quantity'] ?></div>
                                    </td>
                                    <td class="px-3">
                                        <?= date('M d, Y H:i', strtotime($rent['start_time'])) ?>
                                    </td>
                                    <td class="px-3">
                                        <span class="badge <?= $statusBadge ?> px-2 py-1 rounded-pill">
                                            <?= ucfirst(str_replace('_', ' ', $rent['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-3 text-end">
                                        <div class="d-inline-flex gap-1">
                                        <?php if (in_array($rent['status'], ['issued', 'partially_returned'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center" style="width: 85px;" data-bs-toggle="modal" data-bs-target="#returnModal<?= $rent['id'] ?>">
                                                <i class="bi bi-box-arrow-in-down me-1"></i> Return
                                            </button>

                                            <!-- Return Modal Placeholder -->
                                            <!-- The new structured return flow will be implemented here once the schema migration for 'lost_quantity' is approved. -->
                                            <div class="modal fade" id="returnModal<?= $rent['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content text-start">
                                                        <form method="POST" action="/equipment/<?= $rent['id'] ?>/return">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">RETURN EQUIPMENT</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted mb-0">Item</label>
                                                                    <div class="fw-bold fs-5"><?= htmlspecialchars($rent['item_name']) ?></div>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted mb-0">Borrower</label>
                                                                    <div class="fw-bold"><?= htmlspecialchars($rent['resolved_borrower_name'] ?? '') ?></div>
                                                                </div>

                                                                <?php
                                                                    $rem = (float)$rent['borrowed_quantity'] - (float)$rent['returned_quantity'] - (float)$rent['damaged_quantity'];
                                                                ?>
                                                                <div class="mb-4">
                                                                    <label class="form-label text-muted mb-0">Remaining to Return</label>
                                                                    <div class="fw-bold text-primary fs-5"><?= $rem ?> items</div>
                                                                </div>

                                                                <hr>
                                                                <h6 class="mb-3">RETURN DETAILS</h6>

                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Return Date <span class="text-danger">*</span></label>
                                                                        <input type="date" name="return_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Return Time <span class="text-danger">*</span></label>
                                                                        <input type="time" name="return_time" class="form-control" required value="<?= date('H:i') ?>">
                                                                    </div>
                                                                </div>

                                                                <hr>
                                                                <h6 class="mb-3">QUANTITY</h6>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Number of Items Returned <span class="text-danger">*</span></label>
                                                                    <input type="number" name="returned_quantity" class="form-control" min="0" max="<?= $rem ?>" value="<?= $rem ?>" step="any" required>
                                                                </div>

                                                                <hr>
                                                                <h6 class="mb-3">CONDITION</h6>

                                                                <div class="mb-4">
                                                                    <label class="form-label d-block">Condition <span class="text-danger">*</span></label>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="radio" name="condition_on_return" id="condGood_<?= $rent['id'] ?>" value="Good" required>
                                                                        <label class="form-check-label" for="condGood_<?= $rent['id'] ?>">Good</label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="radio" name="condition_on_return" id="condAvg_<?= $rent['id'] ?>" value="Average" required>
                                                                        <label class="form-check-label" for="condAvg_<?= $rent['id'] ?>">Average</label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="radio" name="condition_on_return" id="condBad_<?= $rent['id'] ?>" value="Bad" required>
                                                                        <label class="form-check-label" for="condBad_<?= $rent['id'] ?>">Bad</label>
                                                                    </div>
                                                                </div>

                                                                <hr>
                                                                <h6 class="mb-3">DAMAGED</h6>

                                                                <div class="mb-3">
                                                                    <label class="form-label d-block">Damaged? <span class="text-danger">*</span></label>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input damage-radio-no" type="radio" name="is_damaged" id="damagedNo_<?= $rent['id'] ?>" value="No" checked required onclick="document.getElementById('damagedWrapper_<?= $rent['id'] ?>').style.display = 'none';">
                                                                        <label class="form-check-label" for="damagedNo_<?= $rent['id'] ?>">No</label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input damage-radio-yes" type="radio" name="is_damaged" id="damagedYes_<?= $rent['id'] ?>" value="Yes" required onclick="document.getElementById('damagedWrapper_<?= $rent['id'] ?>').style.display = 'block';">
                                                                        <label class="form-check-label" for="damagedYes_<?= $rent['id'] ?>">Yes</label>
                                                                    </div>
                                                                </div>

                                                                <div class="mb-4" id="damagedWrapper_<?= $rent['id'] ?>" style="display: none;">
                                                                    <label class="form-label">Damaged Quantity <span class="text-danger">*</span></label>
                                                                    <input type="number" name="damaged_quantity" class="form-control" min="0" max="<?= $rem ?>" value="0" step="any">
                                                                </div>

                                                                <hr>
                                                                <h6 class="mb-3">LOST</h6>

                                                                <div class="mb-3">
                                                                    <label class="form-label d-block">Lost? <span class="text-danger">*</span></label>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input lost-radio-no" type="radio" name="is_lost" id="lostNo_<?= $rent['id'] ?>" value="No" checked required onclick="document.getElementById('lostWrapper_<?= $rent['id'] ?>').style.display = 'none';">
                                                                        <label class="form-check-label" for="lostNo_<?= $rent['id'] ?>">No</label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input lost-radio-yes" type="radio" name="is_lost" id="lostYes_<?= $rent['id'] ?>" value="Yes" required onclick="document.getElementById('lostWrapper_<?= $rent['id'] ?>').style.display = 'block';">
                                                                        <label class="form-check-label" for="lostYes_<?= $rent['id'] ?>">Yes</label>
                                                                    </div>
                                                                </div>

                                                                <div class="mb-4" id="lostWrapper_<?= $rent['id'] ?>" style="display: none;">
                                                                    <label class="form-label">Lost Quantity <span class="text-danger">*</span></label>
                                                                    <input type="number" name="lost_quantity" class="form-control" min="0" max="<?= $rem ?>" value="0" step="any">
                                                                </div>

                                                                <hr>
                                                                <h6 class="mb-3">RETURN NOTES</h6>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Return Notes</label>
                                                                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes about condition, damage or loss"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Process Return</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" style="width: 85px;" disabled>Returned</button>
                                        <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="p-3 border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted text-sm">Showing page <?= $page ?> of <?= $totalPages ?></span>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">Previous</a>
                            </li>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Issue Equipment Modal -->
<div class="modal fade" id="issueModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content text-start">
            <form method="POST" action="/equipment/issue">
                <div class="modal-header">
                    <h5 class="modal-title">Issue Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="issueItemsContainer">
                        <div class="issue-item-row mb-3 p-3 border rounded position-relative bg-light">
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <label class="form-label">Equipment Category <span class="text-danger">*</span></label>
                                    <select class="form-select category-select" onchange="filterInventoryItems(this)" required>
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Inventory Item <span class="text-danger">*</span></label>
                                    <select name="inventory_item_id[]" class="form-select item-select" required disabled>
                                        <option value="">-- Select Item --</option>
                                        <?php foreach ($inventoryItems as $item): ?>
                                            <option value="<?= $item['id'] ?>" data-category="<?= $item['category_id'] ?>" data-max="<?= (float)$item['quantity'] ?>">
                                                <?= htmlspecialchars($item['item_name']) ?> (Available: <?= (float)$item['quantity'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Qty <span class="text-danger">*</span></label>
                                    <input type="number" name="borrowed_quantity[]" class="form-control qty-input" value="1" min="0.01" step="any" required>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 mt-1 me-1 remove-row-btn" style="display: none;" onclick="removeIssueRow(this)">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addAnotherItemBtn" onclick="addIssueRow()">
                            <i class="bi bi-plus-circle"></i> Add Another Item
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Borrower Type <span class="text-danger">*</span></label>
                        <select name="borrower_type" class="form-select" required onchange="toggleBorrowerFields(this.value)">
                            <option value="athlete">Athlete</option>
                            <option value="coach">Coach</option>
                            <option value="employee">Employee</option>
                            <option value="team">Team</option>
                            <option value="venue">Venue</option>
                            <option value="other">Other / External</option>
                        </select>
                    </div>

                    <div class="mb-3" id="borrowerNameField" style="display: none;">
                        <label class="form-label">Borrower Name (External) <span class="text-danger">*</span></label>
                        <input type="text" name="borrower_name" class="form-control" placeholder="e.g. John Doe (Guest)">
                    </div>

                    <!-- Dynamic Select Fields -->
                    <div class="mb-3 borrower-select-group" id="borrower_athlete_group">
                        <label class="form-label">Athlete <span class="text-danger">*</span></label>
                        <select name="athlete_id" class="form-select borrower-select-input" required>
                            <option value="">-- Select Athlete --</option>
                            <?php foreach ($athletes as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 borrower-select-group" id="borrower_coach_group" style="display: none;">
                        <label class="form-label">Coach <span class="text-danger">*</span></label>
                        <select name="temp_coach_id" class="form-select borrower-select-input">
                            <option value="">-- Select Coach --</option>
                            <?php foreach ($coaches as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 borrower-select-group" id="borrower_employee_group" style="display: none;">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="temp_employee_id" class="form-select borrower-select-input">
                            <option value="">-- Select Employee --</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 borrower-select-group" id="borrower_team_group" style="display: none;">
                        <label class="form-label">Team <span class="text-danger">*</span></label>
                        <select name="temp_team_id" class="form-select borrower-select-input">
                            <option value="">-- Select Team --</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 borrower-select-group" id="borrower_venue_group" style="display: none;">
                        <label class="form-label">Venue <span class="text-danger">*</span></label>
                        <select name="temp_venue_id" class="form-select borrower-select-input">
                            <option value="">-- Select Venue --</option>
                            <?php foreach ($venues as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Expected Return Time</label>
                        <input type="datetime-local" name="expected_return_time" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Purpose of rental..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Issue Equipment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterInventoryItems(categorySelect) {
    const categoryId = categorySelect.value;
    const row = categorySelect.closest('.issue-item-row');
    const itemSelect = row.querySelector('.item-select');
    const options = itemSelect.querySelectorAll('option[data-category]');

    itemSelect.value = ''; // Reset selection

    if (!categoryId) {
        itemSelect.disabled = true;
        options.forEach(opt => opt.style.display = 'none');
        return;
    }

    itemSelect.disabled = false;
    options.forEach(opt => {
        if (opt.getAttribute('data-category') === categoryId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
}

function addIssueRow() {
    const container = document.getElementById('issueItemsContainer');
    const rows = container.querySelectorAll('.issue-item-row');
    const firstRow = rows[0];

    const newRow = firstRow.cloneNode(true);

    // Reset values in cloned row
    const catSelect = newRow.querySelector('.category-select');
    catSelect.value = '';

    const itemSelect = newRow.querySelector('.item-select');
    itemSelect.value = '';
    itemSelect.disabled = true;
    itemSelect.querySelectorAll('option[data-category]').forEach(opt => opt.style.display = 'none');

    const qtyInput = newRow.querySelector('.qty-input');
    qtyInput.value = '1';

    // Show remove button
    const removeBtn = newRow.querySelector('.remove-row-btn');
    removeBtn.style.display = 'block';

    container.appendChild(newRow);

    // If more than 1 row, show remove button on first row as well
    if (container.querySelectorAll('.issue-item-row').length > 1) {
        firstRow.querySelector('.remove-row-btn').style.display = 'block';
    }
}

function removeIssueRow(btn) {
    const container = document.getElementById('issueItemsContainer');
    const rows = container.querySelectorAll('.issue-item-row');

    if (rows.length > 1) {
        btn.closest('.issue-item-row').remove();
    }

    // Hide remove button if only 1 row remains
    const remainingRows = container.querySelectorAll('.issue-item-row');
    if (remainingRows.length === 1) {
        remainingRows[0].querySelector('.remove-row-btn').style.display = 'none';
    }
}

// Client-side validation before submit
document.querySelector('#issueModal form').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');

    // Prevent double submission if already processing
    if (submitBtn.disabled) {
        e.preventDefault();
        return;
    }

    const itemSelects = document.querySelectorAll('.issue-item-row .item-select');
    const qtys = document.querySelectorAll('.issue-item-row .qty-input');

    let valid = true;
    let selectedItems = new Set();

    itemSelects.forEach((select, index) => {
        if (select.value) {
            if (selectedItems.has(select.value)) {
                alert('You have selected duplicate items. Please combine quantities for the same item.');
                valid = false;
                return;
            }
            selectedItems.add(select.value);

            const maxQty = parseFloat(select.options[select.selectedIndex].getAttribute('data-max'));
            const enteredQty = parseFloat(qtys[index].value);

            if (enteredQty > maxQty) {
                alert('Quantity requested for ' + select.options[select.selectedIndex].text + ' exceeds available stock (' + maxQty + ').');
                valid = false;
                return;
            }
        }
    });

    if (!valid) {
        e.preventDefault();
    } else {
        // Disable button to prevent double click
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
    }
});

function toggleBorrowerFields(type) {
    const nameField = document.getElementById('borrowerNameField');
    const groups = document.querySelectorAll('.borrower-select-group');
    const inputs = document.querySelectorAll('.borrower-select-input');

    // Hide all dynamic dropdowns and reset their names/required
    groups.forEach(group => group.style.display = 'none');
    inputs.forEach(input => {
        input.removeAttribute('required');
        input.name = 'temp_' + input.name.replace('temp_', ''); // unset active name
    });

    if (type === 'other') {
        nameField.style.display = 'block';
    } else {
        nameField.style.display = 'none';
        // Show correct dropdown and make it required with the right name
        const activeGroup = document.getElementById('borrower_' + type + '_group');
        if (activeGroup) {
            activeGroup.style.display = 'block';
            const activeInput = activeGroup.querySelector('select');
            if (activeInput) {
                activeInput.setAttribute('required', 'required');
                activeInput.name = type + '_id';
            }
        }
    }
}
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
