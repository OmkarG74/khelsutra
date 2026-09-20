<?php
$activePage = 'hr-finance';
$title = 'Departments — KhelSutra HR';

$deptService = new \App\Services\Staff\DepartmentService();
$orgId = $_SESSION['current_organization_id'] ?? 1;
$departments = $deptService->listDepartments($orgId);

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/hr/employees" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Roster</a>
        </div>
        <h1 class="ks-page-title">Departments</h1>
        <p class="ks-page-subtitle">Organizational business units and staff groupings scoped to your academy.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-primary" onclick="document.getElementById('deptModal').style.display='flex'">
            <i class="bi bi-plus-lg"></i>
            <span>Add Department</span>
        </button>
    </div>
</div>

<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-diagram-3" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Active Academy Departments</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Department Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departments)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No departments configured for this organisation.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($departments as $d): ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-navy"><?= htmlspecialchars($d['name']) ?></span>
                            </td>
                            <td>
                                <span class="small text-muted"><?= htmlspecialchars($d['description'] ?? '—') ?></span>
                            </td>
                            <td>
                                <?php if (($d['status'] ?? '') === 'active'): ?>
                                    <span class="ks-badge ks-badge-confirmed">Active</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-rejected"><?= htmlspecialchars(ucfirst($d['status'] ?? 'Inactive')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;" onclick="editDept(<?= htmlspecialchars(json_encode($d)) ?>)">
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

<!-- Add/Edit Department Modal -->
<div id="deptModal" style="display: none; position: fixed; inset: 0; background: rgba(14, 30, 59, 0.45); z-index: 9999; align-items: center; justify-content: center;">
    <div class="ks-card p-4" style="width: 480px; max-width: 90%;">
        <h5 class="fw-bold text-navy mb-3" id="modalTitle">Add Department</h5>
        <form id="deptForm">
            <input type="hidden" name="id" id="deptId">
            <div class="mb-3">
                <label class="ks-form-label">Department Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="deptName" class="ks-form-control" required placeholder="e.g. Sports Operations">
            </div>
            <div class="mb-3">
                <label class="ks-form-label">Description</label>
                <textarea name="description" id="deptDesc" class="ks-form-control" rows="3" placeholder="Scope and responsibilities..."></textarea>
            </div>
            <div class="mb-4">
                <label class="ks-form-label">Status</label>
                <select name="status" id="deptStatus" class="ks-form-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="ks-btn ks-btn-secondary" onclick="document.getElementById('deptModal').style.display='none'">Cancel</button>
                <button type="submit" class="ks-btn ks-btn-primary">Save Department</button>
            </div>
        </form>
    </div>
</div>

<script>
function editDept(d) {
    document.getElementById('modalTitle').textContent = 'Edit Department';
    document.getElementById('deptId').value = d.id;
    document.getElementById('deptName').value = d.name;
    document.getElementById('deptDesc').value = d.description || '';
    document.getElementById('deptStatus').value = d.status || 'active';
    document.getElementById('deptModal').style.display = 'flex';
}

document.getElementById('deptForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id = document.getElementById('deptId').value;
    const payload = {
        name: document.getElementById('deptName').value,
        description: document.getElementById('deptDesc').value,
        status: document.getElementById('deptStatus').value
    };

    const url = id ? '/api/v1/departments/' + id : '/api/v1/departments';
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
            alert(result.message || 'Error saving department');
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
