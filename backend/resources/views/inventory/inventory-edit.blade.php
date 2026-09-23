<?php
$itemId = (int)($id ?? ($_GET['id'] ?? 0));
$orgId = current_organization_id();
$invService = new \App\Services\Inventory\InventoryService();
$item = $invService->getItem($orgId, $itemId);

$db = \App\Services\BaseService::getDatabaseConnection();
$catStmt = $db->prepare("SELECT id, name FROM inventory_categories WHERE organization_id = :org_id AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
$catStmt->execute([':org_id' => $orgId]);
$categories = $catStmt ? $catStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$pageTitle = $item ? 'Edit Inventory Item — ' . htmlspecialchars($item['item_name']) : 'Edit Item';
$activePage = 'inventory';

ob_start();
?>

<div class="ks-content">
    <?php if (!$item): ?>
        <div class="card p-5 text-center" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <div class="mb-3"><i class="bi bi-box-seam fs-1 text-muted"></i></div>
            <h4 class="fw-bold" style="color: var(--ks-navy);">Item Not Found</h4>
            <p class="text-muted small">The requested inventory item does not exist or does not belong to your organisation.</p>
            <div class="mt-3">
                <a href="/inventory" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Inventory
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="/inventory/<?= (int)$item['id'] ?>" class="text-decoration-none text-muted small fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Item Details
            </a>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h4 fw-bold mb-0" style="color: var(--ks-navy);">Edit Item: <?= htmlspecialchars($item['item_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="text-muted small mt-1">Code: <strong><?= htmlspecialchars($item['item_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></div>
            </div>
        </div>

        <form action="/inventory/<?= (int)$item['id'] ?>/edit" method="POST" id="editInventoryForm">
            <!-- Section 1: Item Profile -->
            <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                    1. Item Profile & Categorization
                </h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Item / Equipment Name <span class="text-danger">*</span></label>
                        <input type="text" name="item_name" class="form-control" value="<?= htmlspecialchars($item['item_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Category</label>
                        <select name="category_id" class="form-select" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="">General Sports Gear</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>" <?= ($item['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Unit of Measurement <span class="text-danger">*</span></label>
                        <select name="unit" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <?php foreach (['Pieces', 'Pairs', 'Sets', 'Boxes', 'Kits'] as $u): ?>
                                <option value="<?= $u ?>" <?= ($item['unit'] ?? '') === $u ? 'selected' : '' ?>><?= $u ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Unit Cost (₹)</label>
                        <input type="number" step="0.01" name="unit_cost" class="form-control" value="<?= (float)($item['unit_cost'] ?? 0) ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Storage Location</label>
                        <input type="text" name="location_name" class="form-control" value="<?= htmlspecialchars($item['location_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                </div>
            </div>

            <!-- Section 2: Stock Levels & Safeguards -->
            <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                    2. Stock Safeguards & Alert Levels
                </h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Minimum Safety Stock <span class="text-danger">*</span></label>
                        <input type="number" step="1" name="minimum_stock_level" class="form-control" value="<?= (float)($item['minimum_stock_level'] ?? 5) ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Reorder Alert Level <span class="text-danger">*</span></label>
                        <input type="number" step="1" name="reorder_level" class="form-control" value="<?= (float)($item['reorder_level'] ?? 10) ?>" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                </div>
            </div>

            <!-- Section 3: Status & Description -->
            <div class="card p-4 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
                <h5 class="fw-bold mb-3" style="color: var(--ks-navy); font-size: 15px; border-bottom: 1px solid var(--ks-border-light); padding-bottom: 10px;">
                    3. Status & Description
                </h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Item Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required style="font-size: 13px; border-radius: var(--ks-radius-button);">
                            <option value="active" <?= ($item['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($item['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="discontinued" <?= ($item['status'] ?? '') === 'discontinued' ? 'selected' : '' ?>>Discontinued</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold text-dark">Description</label>
                        <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size: 13px; border-radius: var(--ks-radius-button);">
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
                <a href="/inventory/<?= (int)$item['id'] ?>" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 10px 24px;">
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
