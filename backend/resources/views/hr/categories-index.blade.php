<?php
$activePage = 'hr-finance';
$title = 'Employee Categories — KhelSutra HR';

$catService = new \App\Services\Staff\EmployeeCategoryService();
$orgId = $_SESSION['current_organization_id'] ?? 1;
$categories = $catService->listCategories($orgId);

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/hr/employees" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Roster</a>
        </div>
        <h1 class="ks-page-title">Employee Categories</h1>
        <p class="ks-page-subtitle">Classification tiers for coaches, medical crew, trainers, administrative, and operations personnel.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-primary" onclick="document.getElementById('catModal').style.display='flex'">
            <i class="bi bi-plus-lg"></i>
            <span>Add Category</span>
        </button>
    </div>
</div>

<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-tags" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Configured Employee Categories</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No categories configured yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-navy"><?= htmlspecialchars($c['name']) ?></span>
                            </td>
                            <td>
                                <span class="small text-muted"><?= htmlspecialchars($c['description'] ?? '—') ?></span>
                            </td>
                            <td>
                                <?php if (($c['status'] ?? '') === 'active'): ?>
                                    <span class="ks-badge ks-badge-confirmed">Active</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-rejected"><?= htmlspecialchars(ucfirst($c['status'] ?? 'Inactive')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;" onclick="editCat(<?= htmlspecialchars(json_encode($c)) ?>)">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="catModal" style="display: none; position: fixed; inset: 0; background: rgba(14, 30, 59, 0.45); z-index: 9999; align-items: center; justify-content: center;">
    <div class="ks-card p-4" style="width: 480px; max-width: 90%;">
        <h5 class="fw-bold text-navy mb-3" id="modalCatTitle">Add Category</h5>
        <form id="catForm">
            <input type="hidden" name="id" id="catId">
            <div class="mb-3">
                <label class="ks-form-label">Category Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="catName" class="ks-form-control" required placeholder="e.g. Head Coaches">
            </div>
            <div class="mb-3">
                <label class="ks-form-label">Description</label>
                <textarea name="description" id="catDesc" class="ks-form-control" rows="3" placeholder="Category definition and qualifications..."></textarea>
            </div>
            <div class="mb-4">
                <label class="ks-form-label">Status</label>
                <select name="status" id="catStatus" class="ks-form-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="ks-btn ks-btn-secondary" onclick="document.getElementById('catModal').style.display='none'">Cancel</button>
                <button type="submit" class="ks-btn ks-btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCat(c) {
    document.getElementById('modalCatTitle').textContent = 'Edit Category';
    document.getElementById('catId').value = c.id;
    document.getElementById('catName').value = c.name;
    document.getElementById('catDesc').value = c.description || '';
    document.getElementById('catStatus').value = c.status || 'active';
    document.getElementById('catModal').style.display = 'flex';
}

document.getElementById('catForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id = document.getElementById('catId').value;
    const payload = {
        name: document.getElementById('catName').value,
        description: document.getElementById('catDesc').value,
        status: document.getElementById('catStatus').value
    };

    const url = id ? '/api/v1/employee-categories/' + id : '/api/v1/employee-categories';
    const method = id ? 'PUT' : 'POST';

    try {
        const res = await fetch(url, {
            method: method,
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            window.location.reload();
        } else {
            alert(result.message || 'Error saving category');
        }
    } catch (err) {
        alert('Request failed');
    }
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
