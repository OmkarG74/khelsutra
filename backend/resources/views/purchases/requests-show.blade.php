<?php
$pageTitle = 'Purchase Request Details — KhelSutra';
$activePage = 'purchases';
$orgId = current_organization_id();

$id = (int)($id ?? ($data['id'] ?? ($_GET['id'] ?? 0)));
$purchaseService = new \App\Services\Purchase\PurchaseService();
$pr = $purchaseService->getPurchaseRequest($orgId, $id);

$db = \App\Services\BaseService::getDatabaseConnection();
$linkedPo = null;
if ($pr && $pr['status'] === 'converted') {
    $poStmt = $db->prepare("SELECT id, po_number FROM purchase_orders WHERE purchase_request_id = :pr_id AND organization_id = :org_id LIMIT 1");
    $poStmt->execute([':pr_id' => $id, ':org_id' => $orgId]);
    $linkedPo = $poStmt->fetch(PDO::FETCH_ASSOC);
}

ob_start();
?>

<div class="ks-content">
    <?php if (!empty($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: var(--ks-radius-button); font-size: 13px;">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div><?= htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: var(--ks-radius-button); font-size: 13px;">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!$pr): ?>
        <div class="card p-5 text-center" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
            <i class="bi bi-exclamation-circle text-danger fs-1 mb-3"></i>
            <h4 class="fw-bold mb-2">Purchase Request Not Found</h4>
            <p class="text-muted small mb-4">The requested purchase requisition does not exist or access was denied.</p>
            <div>
                <a href="/purchases?tab=requests" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-size: 13px;">
                    Back to Procurement
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Header -->
        <div class="ks-page-header mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <a href="/purchases?tab=requests" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left"></i> Purchases & Orders</a>
                    <span class="text-muted small">/</span>
                    <span class="text-dark small fw-semibold"><?= htmlspecialchars($pr['request_reference'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h1 class="ks-page-title mb-0">
                        <?= htmlspecialchars($pr['request_reference'], ENT_QUOTES, 'UTF-8') ?>
                    </h1>
                    <?php
                    $rBadge = match($pr['status']) {
                        'draft' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                        'submitted' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                        'approved' => 'bg-success-subtle text-success border border-success-subtle',
                        'rejected' => 'bg-danger-subtle text-danger border border-danger-subtle',
                        'converted' => 'bg-primary-subtle text-primary border border-primary-subtle',
                        'cancelled' => 'bg-light text-muted border',
                        default => 'bg-light text-dark'
                    };
                    ?>
                    <span class="badge <?= $rBadge ?> px-3 py-2 fw-semibold" style="font-size: 12px;">
                        <?= htmlspecialchars(ucfirst($pr['status']), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </div>

            <!-- Workflow Action Buttons -->
            <div class="ks-header-actions d-flex gap-2">
                <?php if ($pr['status'] === 'draft'): ?>
                    <form method="POST" action="/purchases/requests/<?= (int)$pr['id'] ?>/submit" class="d-inline">
                        <button type="submit" class="ks-btn ks-btn-primary">
                            <i class="bi bi-send-check me-1"></i> Submit for Approval
                        </button>
                    </form>
                    <form method="POST" action="/purchases/requests/<?= (int)$pr['id'] ?>/cancel" class="d-inline" onsubmit="return confirm('Cancel this requisition request?');">
                        <button type="submit" class="ks-btn ks-btn-danger">
                            <i class="bi bi-x-circle me-1"></i> Cancel Request
                        </button>
                    </form>
                <?php elseif ($pr['status'] === 'submitted'): ?>
                    <form method="POST" action="/purchases/requests/<?= (int)$pr['id'] ?>/approve" class="d-inline">
                        <button type="submit" class="ks-btn ks-btn-success">
                            <i class="bi bi-check-circle me-1"></i> Approve Request
                        </button>
                    </form>
                    <button type="button" class="ks-btn ks-btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle me-1"></i> Reject
                    </button>
                <?php elseif ($pr['status'] === 'approved'): ?>
                    <a href="/purchases/orders/create?purchase_request_id=<?= (int)$pr['id'] ?>" class="ks-btn ks-btn-primary">
                        <i class="bi bi-cart-plus me-1"></i> Generate Purchase Order
                    </a>
                <?php elseif ($pr['status'] === 'converted' && $linkedPo): ?>
                    <a href="/purchases/orders/<?= (int)$linkedPo['id'] ?>" class="ks-btn ks-btn-secondary">
                        <i class="bi bi-box-arrow-up-right me-1"></i> View Order (<?= htmlspecialchars($linkedPo['po_number'], ENT_QUOTES, 'UTF-8') ?>)
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($pr['status'] === 'rejected' && !empty($pr['rejection_reason'])): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius: var(--ks-radius-card); font-size: 13px;">
                <i class="bi bi-x-octagon-fill fs-5"></i>
                <div>
                    <strong>Rejection Reason:</strong> <?= htmlspecialchars($pr['rejection_reason'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Details Grid -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <!-- Requisition Line Items -->
                <div class="ks-table-card">
                    <div class="ks-table-header">
                        <div class="ks-header-left">
                            <div class="ks-icon-box ks-icon-blue" style="width: 28px; height: 28px; font-size: 13px;">
                                <i class="bi bi-list-check"></i>
                            </div>
                            <h3 class="ks-header-title">Requisition Line Items</h3>
                            <span class="badge bg-light text-secondary border ms-2 fw-medium" style="font-size: 11px;">
                                <?= count($pr['items']) ?> items
                            </span>
                        </div>
                    </div>
                    <div class="ks-table-responsive">
                        <table class="table ks-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width: 200px;">Item Name</th>
                                    <th style="min-width: 170px;">Stock Link</th>
                                    <th style="min-width: 100px; text-align: center;">Quantity</th>
                                    <th class="ks-col-money" style="min-width: 130px;">Est. Unit Cost</th>
                                    <th class="ks-col-money" style="min-width: 140px;">Est. Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalEst = 0;
                                foreach ($pr['items'] as $item): 
                                    $totalEst += (float)$item['estimated_total'];
                                ?>
                                    <tr>
                                        <td class="py-3 px-3">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if (!empty($item['description'])): ?>
                                                <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-3 text-muted">
                                            <?php if (!empty($item['item_code'])): ?>
                                                <a href="/inventory/<?= (int)$item['inventory_item_id'] ?>" class="text-decoration-none fw-medium">
                                                    <?= htmlspecialchars($item['stock_item_name'] ?? $item['item_code'], ENT_QUOTES, 'UTF-8') ?>
                                                    <span class="badge bg-light text-dark border ms-1" style="font-size: 10px;">Stock: <?= (float)($item['in_stock_quantity'] ?? 0) ?></span>
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Custom Asset</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-3 text-center fw-semibold font-monospace">
                                            <?= number_format((float)$item['quantity'], 2) ?>
                                        </td>
                                        <td class="py-3 px-3 ks-col-money font-monospace text-muted">
                                            ₹<?= number_format((float)$item['estimated_unit_cost'], 2) ?>
                                        </td>
                                        <td class="py-3 px-3 ks-col-money font-monospace fw-bold text-dark">
                                            ₹<?= number_format((float)$item['estimated_total'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot style="background: #fafafa;">
                                <tr>
                                    <th colspan="4" class="text-end text-muted fw-normal">Total Estimated Cost:</th>
                                    <th class="ks-col-money font-monospace fw-bold text-primary">₹<?= number_format($totalEst, 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sidebar: Overview & Audit -->
            <div class="col-lg-4">
                <div class="card mb-4 border-0 shadow-sm" style="border-radius: var(--ks-radius-card); background: #fff;">
                    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center gap-2">
                        <div class="ks-icon-box ks-icon-blue" style="width: 28px; height: 28px; font-size: 13px;">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <h6 class="fw-bold mb-0" style="color: var(--ks-navy); font-size: 14px;">
                            Request Details
                        </h6>
                    </div>
                    <div class="card-body p-3" style="font-size: 13px;">
                        <div class="mb-2">
                            <span class="text-muted d-block small">Requested By</span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($pr['requester_name'] ?? 'Staff', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted d-block small">Request Date</span>
                            <span class="text-dark"><?= !empty($pr['request_date']) ? date('d M Y', strtotime($pr['request_date'])) : '—' ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted d-block small">Required By Date</span>
                            <span class="text-dark"><?= !empty($pr['required_date']) ? date('d M Y', strtotime($pr['required_date'])) : '—' ?></span>
                        </div>
                        <?php if (!empty($pr['purpose'])): ?>
                            <div class="mb-2 pt-2 border-top">
                                <span class="text-muted d-block small">Purpose / Justification</span>
                                <div class="text-dark"><?= htmlspecialchars($pr['purpose'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Approval Sign-off Card -->
                <div class="card border-0 shadow-sm" style="border-radius: var(--ks-radius-card); background: #fff;">
                    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center gap-2">
                        <div class="ks-icon-box ks-icon-green" style="width: 28px; height: 28px; font-size: 13px;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h6 class="fw-bold mb-0" style="color: var(--ks-navy); font-size: 14px;">
                            Sign-off Status
                        </h6>
                    </div>
                    <div class="card-body p-3" style="font-size: 13px;">
                        <?php if ($pr['status'] === 'approved' || $pr['status'] === 'converted'): ?>
                            <div class="mb-2 text-success fw-semibold">
                                <i class="bi bi-check-circle-fill me-1"></i> Approved by <?= htmlspecialchars($pr['approver_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <span class="text-muted small">on <?= !empty($pr['approved_at']) ? date('d M Y, h:i A', strtotime($pr['approved_at'])) : '—' ?></span>
                        <?php elseif ($pr['status'] === 'rejected'): ?>
                            <div class="mb-2 text-danger fw-semibold">
                                <i class="bi bi-x-circle-fill me-1"></i> Rejected
                            </div>
                        <?php elseif ($pr['status'] === 'submitted'): ?>
                            <div class="text-warning-emphasis fw-semibold">
                                <i class="bi bi-clock-history me-1"></i> Pending Administrative Review
                            </div>
                        <?php else: ?>
                            <div class="text-muted">
                                <i class="bi bi-pencil me-1"></i> In Draft Status (Unsubmitted)
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow" style="border-radius: var(--ks-radius-card);">
                    <form method="POST" action="/purchases/requests/<?= (int)$pr['id'] ?>/reject">
                        <div class="modal-header border-bottom px-4 py-3">
                            <h5 class="modal-title fw-bold" style="font-size: 16px; color: var(--ks-navy);">Reject Purchase Request</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-dark">Reason for Rejection <span class="text-danger">*</span></label>
                                <textarea name="rejection_reason" class="form-control ks-form-control" rows="3" required placeholder="Specify budget limitation, alternate stock availability, or clarification required..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer px-4 py-3 border-top">
                            <button type="button" class="ks-btn ks-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="ks-btn ks-btn-danger">
                                <i class="bi bi-x-circle me-1"></i> Reject Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
