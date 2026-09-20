<?php
$activePage = 'hr-finance';
$title = 'Employee Documents — KhelSutra HR';

$empId = $id ?? 1;
$empService = new \App\Services\Staff\EmployeeService();
$docService = new \App\Services\Staff\EmployeeDocumentService();
$orgId = $_SESSION['current_organization_id'] ?? 1;

try {
    $emp = $empService->getEmployeeDetails((int)$empId, (int)$orgId);
} catch (\Throwable $e) {
    $emp = null;
}

$documents = $docService->listDocuments((int)$empId, (int)$orgId);

ob_start();
?>

<div class="ks-page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="/hr/employees/<?= $empId ?>" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Back to Profile</a>
        </div>
        <h1 class="ks-page-title">Employee Documents</h1>
        <p class="ks-page-subtitle">Identity credentials, certifications, licenses, and contracts for <?= htmlspecialchars(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? 'Employee')) ?>.</p>
    </div>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-primary" onclick="document.getElementById('uploadDocModal').style.display='flex'">
            <i class="bi bi-upload"></i>
            <span>Upload Document</span>
        </button>
    </div>
</div>

<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-folder-check" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Registered Documents</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Document Type</th>
                    <th>Document Number</th>
                    <th>Storage File Path</th>
                    <th>Issue Date</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No documents uploaded for this employee yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-navy text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $doc['document_type'])) ?></span>
                            </td>
                            <td>
                                <code class="text-navy fw-semibold"><?= htmlspecialchars($doc['document_number'] ?? '—') ?></code>
                            </td>
                            <td>
                                <span class="small text-muted font-monospace"><?= htmlspecialchars($doc['file_path']) ?></span>
                            </td>
                            <td>
                                <span class="small text-navy"><?= htmlspecialchars($doc['issue_date'] ?? '—') ?></span>
                            </td>
                            <td>
                                <span class="small text-navy"><?= htmlspecialchars($doc['expiry_date'] ?? '—') ?></span>
                            </td>
                            <td>
                                <?php if (!empty($doc['expiry_date']) && strtotime($doc['expiry_date']) < time()): ?>
                                    <span class="ks-badge ks-badge-rejected">Expired</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-confirmed">Valid</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;">
                                    <i class="bi bi-box-arrow-up-right"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadDocModal" style="display: none; position: fixed; inset: 0; background: rgba(14, 30, 59, 0.45); z-index: 9999; align-items: center; justify-content: center;">
    <div class="ks-card p-4" style="width: 520px; max-width: 90%;">
        <h5 class="fw-bold text-navy mb-3">Upload Employee Document</h5>
        <form id="docForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="ks-form-label">Document Type <span class="text-danger">*</span></label>
                    <select name="document_type" class="ks-form-select" required>
                        <option value="id_proof">ID Proof / Aadhaar / Passport</option>
                        <option value="coaching_license">Coaching License</option>
                        <option value="contract">Employment Contract</option>
                        <option value="medical_certificate">Medical Certificate</option>
                        <option value="educational_degree">Educational Degree</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Document Number</label>
                    <input type="text" name="document_number" class="ks-form-control" placeholder="e.g. DOC-2026-99">
                </div>
                <div class="col-12">
                    <label class="ks-form-label">File Path / S3 Storage URI <span class="text-danger">*</span></label>
                    <input type="text" name="file_path" class="ks-form-control" required placeholder="documents/emp-<?= $empId ?>/id_proof.pdf">
                    <div class="small text-muted mt-1">AWS S3 / local disk storage reference path.</div>
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Issue Date</label>
                    <input type="date" name="issue_date" class="ks-form-control">
                </div>
                <div class="col-md-6">
                    <label class="ks-form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="ks-form-control">
                </div>
                <div class="col-12">
                    <label class="ks-form-label">Notes</label>
                    <textarea name="notes" class="ks-form-control" rows="2" placeholder="Document verification notes..."></textarea>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="ks-btn ks-btn-secondary" onclick="document.getElementById('uploadDocModal').style.display='none'">Cancel</button>
                <button type="submit" class="ks-btn ks-btn-primary">Upload Document</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('docForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const payload = Object.fromEntries(formData.entries());

    try {
        const res = await fetch('/api/v1/employees/<?= $empId ?>/documents', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            window.location.reload();
        } else {
            alert(result.message || 'Error recording document');
        }
    } catch (err) {
        alert('Upload request failed');
    }
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
