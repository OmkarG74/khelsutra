<?php
$pageTitle = 'Add Inventory Item — KhelSutra';
$activePage = 'inventory';
$orgId = current_organization_id();

$db = \App\Services\BaseService::getDatabaseConnection();
$catStmt = $db->prepare("SELECT id, name FROM inventory_categories WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
$catStmt->execute([':org_id' => $orgId]);
$categories = $catStmt ? $catStmt->fetchAll(PDO::FETCH_ASSOC) : [];

ob_start();
?>

<div class="ks-content">
    <!-- Top Back Navigation -->
    <div class="mb-3">
        <a href="/inventory" class="text-decoration-none text-muted small fw-medium">
            <i class="bi bi-arrow-left me-1"></i> Back to Inventory
        </a>
    </div>

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold mb-0" style="color: var(--ks-navy);">Add Inventory Item</h1>
        </div>
    </div>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger mb-4 py-2 px-3 small d-flex align-items-center gap-2" style="border-radius: var(--ks-radius-button);">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <form action="/inventory/create" method="POST" id="createInventoryForm">
        <!-- Section 1: Item Profile -->
        <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                1. Item Profile & Categorization
            </h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Item / Equipment Name <span class="text-danger">*</span></label>
                    <input type="text" name="item_name" class="form-control" placeholder="e.g. FIFA Pro Match Football Size 5" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Category</label>
                    <select name="category_id" class="form-select" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="">General Sports Gear</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Unit of Measurement <span class="text-danger">*</span></label>
                    <select name="unit" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="Pieces">Pieces</option>
                        <option value="Pairs">Pairs</option>
                        <option value="Sets">Sets</option>
                        <option value="Boxes">Boxes</option>
                        <option value="Kits">Kits</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Unit Cost (₹)</label>
                    <input type="number" step="0.01" name="unit_cost" class="form-control" placeholder="e.g. 1499.00" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Storage Location</label>
                    <input type="text" name="location_name" class="form-control" placeholder="e.g. Equipment Room Rack B-4" value="Main Store Room" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
            </div>
        </div>

        <!-- Section 2: Stock Levels & Thresholds -->
        <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                2. Stock Levels & Thresholds
            </h5>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Opening Stock Quantity <span class="text-danger">*</span></label>
                    <input type="number" step="1" name="quantity" class="form-control" value="20" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Minimum Stock Level <span class="text-danger">*</span></label>
                    <input type="number" step="1" name="minimum_stock_level" class="form-control" value="5" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Reorder Alert Level <span class="text-danger">*</span></label>
                    <input type="number" step="1" name="reorder_level" class="form-control" value="10" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
            </div>
        </div>

        <!-- Section 3: Description & Status -->
        <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                3. Item Details & Status
            </h5>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Item Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-semibold text-dark">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Brand specifications, warranty, or usage instructions" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
            <a href="/inventory" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
                <i class="bi bi-check2 me-1"></i> Save Item
            </button>
        </div>
    </form>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
