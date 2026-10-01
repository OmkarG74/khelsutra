<?php
$activePage = 'organizations';
$title = 'Organisation Management — KhelSutra Super Admin';

$orgService = new \App\Services\Organization\OrganizationManagementService();
$plans = $orgService->getDistinctPlans();

// Overall platform counts for KPI summary
$allOrgs = $orgService->listOrganizations(1000, 0);
$totalOrgs = count($allOrgs);
$activeOrgs = count(array_filter($allOrgs, fn($o) => ($o['status'] ?? '') === 'active'));
$suspendedOrgs = count(array_filter($allOrgs, fn($o) => ($o['status'] ?? '') === 'suspended'));
$subscriptionsCount = count(array_filter($allOrgs, fn($o) => !empty($o['plan_name']) || ($o['status'] ?? '') === 'active'));

// Initial render data (first page, no filters)
$organizations = $orgService->listOrganizations(50, 0);

ob_start();
?>

<!-- Page Header -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Organisations</h1>
    </div>
    <div class="ks-header-actions">
        <a href="/super-admin/organizations/create" class="ks-btn ks-btn-primary">
            <i class="bi bi-plus-lg"></i>
            <span>Create Organisation</span>
        </a>
    </div>
</div>

<!-- Compact KPI Summary Cards -->
<div class="ks-sa-kpi-grid">
    <div class="ks-sa-kpi-card">
        <div class="ks-sa-kpi-left">
            <div class="ks-icon-box ks-icon-blue ks-sa-kpi-icon">
                <i class="bi bi-building"></i>
            </div>
            <span class="ks-sa-kpi-label">Organisations</span>
        </div>
        <div class="ks-sa-kpi-value" id="kpiTotalOrgs"><?= $totalOrgs ?></div>
    </div>

    <div class="ks-sa-kpi-card">
        <div class="ks-sa-kpi-left">
            <div class="ks-icon-box ks-icon-green ks-sa-kpi-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <span class="ks-sa-kpi-label">Active</span>
        </div>
        <div class="ks-sa-kpi-value" id="kpiActiveOrgs"><?= $activeOrgs ?></div>
    </div>

    <div class="ks-sa-kpi-card">
        <div class="ks-sa-kpi-left">
            <div class="ks-icon-box ks-icon-red ks-sa-kpi-icon">
                <i class="bi bi-slash-circle-fill"></i>
            </div>
            <span class="ks-sa-kpi-label">Suspended</span>
        </div>
        <div class="ks-sa-kpi-value" id="kpiSuspendedOrgs"><?= $suspendedOrgs ?></div>
    </div>

    <div class="ks-sa-kpi-card">
        <div class="ks-sa-kpi-left">
            <div class="ks-icon-box ks-icon-purple ks-sa-kpi-icon">
                <i class="bi bi-patch-check-fill"></i>
            </div>
            <span class="ks-sa-kpi-label">Subscriptions</span>
        </div>
        <div class="ks-sa-kpi-value" id="kpiSubsOrgs"><?= $subscriptionsCount ?></div>
    </div>
</div>

<!-- Organisations Table Card with Compact Search & Filter Toolbar -->
<div class="ks-table-card">
    <!-- Compact Toolbar (approx 48px high, single horizontal row on desktop) -->
    <div class="ks-table-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2" style="min-height: 52px; padding: 8px 16px;">
        <div class="d-flex align-items-center gap-2">
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap flex-sm-nowrap w-100 w-lg-auto" style="min-height: 38px;">
            <!-- Search organisation, code, contact... -->
            <div class="position-relative flex-grow-1 flex-sm-grow-0" style="min-width: 250px; max-width: 340px;">
                <i class="bi bi-search position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: var(--ks-text-muted); font-size: 13px; pointer-events: none;"></i>
                <input type="text" id="orgSearchInput" class="ks-form-control" style="padding-left: 34px; height: 38px; font-size: 13px;" placeholder="Search organisation, code, contact...">
            </div>
            <!-- Status Filter -->
            <select id="statusFilter" class="ks-form-select" style="width: 125px; height: 38px; font-size: 13px;">
                <option value="all">Status: All</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="expired">Expired</option>
            </select>
            <!-- Plan Filter -->
            <select id="planFilter" class="ks-form-select" style="width: 165px; height: 38px; font-size: 13px;">
                <option value="all">Plan: All</option>
                <?php foreach ($plans as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                <?php endforeach; ?>
            </select>
            <!-- Clear Filters -->
            <button type="button" id="btnClearFilters" class="ks-btn ks-btn-secondary" style="height: 38px; padding: 0 12px; font-size: 12.5px; white-space: nowrap;" title="Clear search and filters">
                <i class="bi bi-x-circle me-1"></i> Clear Filters
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table" id="orgsTable">
            <thead>
                <tr>
                    <th style="width: 15%;">Organisation Code</th>
                    <th style="width: 24%;">Name</th>
                    <th style="width: 18%;">Contact</th>
                    <th style="width: 13%;">Plan</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 10%;">Access Window</th>
                    <th style="width: 10%; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody id="orgsTableBody">
                <?php if (empty($organizations)): ?>
                    <tr id="emptyRow">
                        <td colspan="7" class="text-center py-4 text-muted">No organisations registered yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($organizations as $org): ?>
                        <tr class="org-row" data-id="<?= (int)$org['id'] ?>">
                            <td class="align-middle">
                                <span class="badge" style="background: #EAF3FF; color: #0B6EF3; font-weight: 600; padding: 5px 9px; border-radius: 6px; font-size: 12px; letter-spacing: 0.3px;">
                                    <?= htmlspecialchars($org['organization_code'] ?? '—') ?>
                                </span>
                            </td>
                            <td class="align-middle">
                                <div>
                                    <span class="fw-bold text-navy" style="font-size: 13.5px;"><?= htmlspecialchars($org['name'] ?? '') ?></span>
                                    <?php if (!empty($org['legal_name'])): ?>
                                        <div class="small text-muted" style="font-size: 11.5px;"><?= htmlspecialchars($org['legal_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="align-middle">
                                <div class="small text-navy fw-medium" style="font-size: 12.5px;"><?= htmlspecialchars($org['email'] ?? '—') ?></div>
                                <div class="small text-muted" style="font-size: 11.5px;"><?= htmlspecialchars($org['phone'] ?? '—') ?></div>
                            </td>
                            <td class="align-middle">
                                <span class="ks-badge ks-badge-blue" style="font-size: 11.5px;"><?= htmlspecialchars($org['plan_name'] ?? 'Standard') ?></span>
                            </td>
                            <td class="align-middle">
                                <?php if (($org['status'] ?? '') === 'active'): ?>
                                    <span class="ks-badge ks-badge-confirmed">Active</span>
                                <?php elseif (($org['status'] ?? '') === 'suspended'): ?>
                                    <span class="ks-badge ks-badge-rejected">Suspended</span>
                                <?php elseif (($org['status'] ?? '') === 'expired'): ?>
                                    <span class="ks-badge ks-badge-pending">Expired</span>
                                <?php else: ?>
                                    <span class="ks-badge ks-badge-scheduled"><?= htmlspecialchars(ucfirst($org['status'] ?? 'Pending')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle">
                                <div class="small text-navy" style="font-size: 12px;"><?= htmlspecialchars($org['access_start_date'] ?? '2026-01-01') ?></div>
                                <div class="small text-muted" style="font-size: 11px;">to <?= htmlspecialchars($org['access_end_date'] ?? '2027-01-01') ?></div>
                            </td>
                            <td class="align-middle" style="text-align: right;">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <a href="/super-admin/organizations/<?= (int)$org['id'] ?>" class="ks-btn ks-btn-secondary" style="height: 30px; padding: 0 9px; font-size: 12px;" title="View Details">
                                        View
                                    </a>
                                    <a href="/super-admin/organizations/<?= (int)$org['id'] ?>/edit" class="ks-btn ks-btn-secondary" style="height: 30px; padding: 0 9px; font-size: 12px;" title="Edit Organisation">
                                        Edit
                                    </a>
                                    <?php if (($org['status'] ?? '') === 'active'): ?>
                                        <button type="button" class="ks-btn ks-btn-secondary text-danger" style="height: 30px; padding: 0 9px; font-size: 12px;" onclick="promptStatusChange(<?= (int)$org['id'] ?>, '<?= htmlspecialchars(addslashes($org['name'] ?? '')) ?>', 'suspended')">
                                            Suspend
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="ks-btn ks-btn-secondary text-success" style="height: 30px; padding: 0 9px; font-size: 12px;" onclick="promptStatusChange(<?= (int)$org['id'] ?>, '<?= htmlspecialchars(addslashes($org['name'] ?? '')) ?>', 'active')">
                                            Activate
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Pagination / Status Footer -->
    <div class="d-flex align-items-center justify-content-between p-3 border-top" style="border-color: var(--ks-border-light) !important; font-size: 12.5px;">
        <span class="text-muted" id="tableSummaryText">
            Showing <strong id="visibleCount" class="text-navy"><?= count($organizations) ?></strong> organisation(s)
        </span>
        <div id="tableSpinner" class="spinner-border spinner-border-sm text-primary d-none" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<!-- Suspend / Activate Confirmation Modal -->
<div class="modal fade" id="statusConfirmModal" tabindex="-1" aria-labelledby="statusConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content" style="border-radius: 14px; border: 1px solid var(--ks-border);">
            <div class="modal-header pb-2" style="border-bottom: 1px solid var(--ks-border-light);">
                <h5 class="modal-title fw-bold text-navy" id="statusConfirmModalLabel">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="d-flex align-items-center gap-3">
                    <div id="statusModalIcon" class="ks-icon-box" style="width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;">
                        <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-navy mb-1" id="statusModalTitle">Suspend Organisation?</h6>
                        <p class="text-muted small mb-0" id="statusModalMessage">Are you sure you want to suspend this organisation?</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer pt-2" style="border-top: 1px solid var(--ks-border-light);">
                <button type="button" class="ks-btn ks-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="ks-btn" id="btnExecuteStatusChange">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
let searchDebounceTimer = null;

const searchInput = document.getElementById('orgSearchInput');
const statusSelect = document.getElementById('statusFilter');
const planSelect = document.getElementById('planFilter');
const clearBtn = document.getElementById('btnClearFilters');
const tableBody = document.getElementById('orgsTableBody');
const visibleCountEl = document.getElementById('visibleCount');
const spinnerEl = document.getElementById('tableSpinner');

// Debounced backend search
searchInput.addEventListener('input', function() {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        fetchFilteredOrganizations();
    }, 300);
});

statusSelect.addEventListener('change', fetchFilteredOrganizations);
planSelect.addEventListener('change', fetchFilteredOrganizations);

clearBtn.addEventListener('click', function() {
    searchInput.value = '';
    statusSelect.value = 'all';
    planSelect.value = 'all';
    fetchFilteredOrganizations();
});

async function fetchFilteredOrganizations() {
    const search = searchInput.value.trim();
    const status = statusSelect.value;
    const plan = planSelect.value;

    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (status && status !== 'all') params.append('status', status);
    if (plan && plan !== 'all') params.append('plan', plan);
    params.append('limit', '100');
    params.append('offset', '0');

    spinnerEl.classList.remove('d-none');

    try {
        const res = await fetch('/api/v1/organizations?' + params.toString(), {
            headers: {
                'Accept': 'application/json'
            }
        });
        const json = await res.json();

        if (json.success && Array.isArray(json.data)) {
            renderTableRows(json.data);
            visibleCountEl.textContent = json.data.length;
        } else {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Error loading organisations.</td></tr>`;
            visibleCountEl.textContent = '0';
        }
    } catch (e) {
        console.error('Fetch error:', e);
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Failed to connect to organisations service.</td></tr>`;
        visibleCountEl.textContent = '0';
    } finally {
        spinnerEl.classList.add('d-none');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function escapeJs(str) {
    if (!str) return '';
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function renderTableRows(orgs) {
    if (orgs.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No organisations match your search/filter criteria.</td></tr>`;
        return;
    }

    let html = '';
    orgs.forEach(org => {
        const code = escapeHtml(org.organization_code || '—');
        const name = escapeHtml(org.name || '');
        const legalName = org.legal_name ? `<div class="small text-muted" style="font-size: 11.5px;">${escapeHtml(org.legal_name)}</div>` : '';
        const email = escapeHtml(org.email || '—');
        const phone = escapeHtml(org.phone || '—');
        const plan = escapeHtml(org.plan_name || 'Standard');
        const status = org.status || 'pending';
        const start = escapeHtml(org.access_start_date || '2026-01-01');
        const end = escapeHtml(org.access_end_date || '2027-01-01');

        let statusBadge = '';
        if (status === 'active') {
            statusBadge = '<span class="ks-badge ks-badge-confirmed">Active</span>';
        } else if (status === 'suspended') {
            statusBadge = '<span class="ks-badge ks-badge-rejected">Suspended</span>';
        } else if (status === 'expired') {
            statusBadge = '<span class="ks-badge ks-badge-pending">Expired</span>';
        } else {
            statusBadge = `<span class="ks-badge ks-badge-scheduled">${escapeHtml(status.charAt(0).toUpperCase() + status.slice(1))}</span>`;
        }

        const safeName = escapeJs(org.name || '');
        let statusBtn = '';
        if (status === 'active') {
            statusBtn = `<button type="button" class="ks-btn ks-btn-secondary text-danger" style="height: 30px; padding: 0 9px; font-size: 12px;" onclick="promptStatusChange(${org.id}, '${safeName}', 'suspended')">Suspend</button>`;
        } else {
            statusBtn = `<button type="button" class="ks-btn ks-btn-secondary text-success" style="height: 30px; padding: 0 9px; font-size: 12px;" onclick="promptStatusChange(${org.id}, '${safeName}', 'active')">Activate</button>`;
        }

        html += `
            <tr class="org-row" data-id="${org.id}">
                <td class="align-middle">
                    <span class="badge" style="background: #EAF3FF; color: #0B6EF3; font-weight: 600; padding: 5px 9px; border-radius: 6px; font-size: 12px; letter-spacing: 0.3px;">
                        ${code}
                    </span>
                </td>
                <td class="align-middle">
                    <div>
                        <span class="fw-bold text-navy" style="font-size: 13.5px;">${name}</span>
                        ${legalName}
                    </div>
                </td>
                <td class="align-middle">
                    <div class="small text-navy fw-medium" style="font-size: 12.5px;">${email}</div>
                    <div class="small text-muted" style="font-size: 11.5px;">${phone}</div>
                </td>
                <td class="align-middle">
                    <span class="ks-badge ks-badge-blue" style="font-size: 11.5px;">${plan}</span>
                </td>
                <td class="align-middle">
                    ${statusBadge}
                </td>
                <td class="align-middle">
                    <div class="small text-navy" style="font-size: 12px;">${start}</div>
                    <div class="small text-muted" style="font-size: 11px;">to ${end}</div>
                </td>
                <td class="align-middle" style="text-align: right;">
                    <div class="d-flex align-items-center justify-content-end gap-1">
                        <a href="/super-admin/organizations/${org.id}" class="ks-btn ks-btn-secondary" style="height: 30px; padding: 0 9px; font-size: 12px;" title="View Details">
                            View
                        </a>
                        <a href="/super-admin/organizations/${org.id}/edit" class="ks-btn ks-btn-secondary" style="height: 30px; padding: 0 9px; font-size: 12px;" title="Edit Organisation">
                            Edit
                        </a>
                        ${statusBtn}
                    </div>
                </td>
            </tr>
        `;
    });

    tableBody.innerHTML = html;
}

let pendingStatusAction = null;

function promptStatusChange(orgId, orgName, targetStatus) {
    pendingStatusAction = { orgId, targetStatus };
    const modalEl = document.getElementById('statusConfirmModal');
    const titleEl = document.getElementById('statusModalTitle');
    const msgEl = document.getElementById('statusModalMessage');
    const btnEl = document.getElementById('btnExecuteStatusChange');
    const iconEl = document.getElementById('statusModalIcon');

    if (targetStatus === 'suspended') {
        titleEl.textContent = 'Suspend Organisation?';
        msgEl.textContent = `Are you sure you want to suspend "${orgName}"? Access for its administrators and users will be paused.`;
        btnEl.className = 'ks-btn btn-danger';
        btnEl.textContent = 'Suspend';
        iconEl.className = 'ks-icon-box ks-icon-red';
        iconEl.innerHTML = '<i class="bi bi-pause-circle-fill text-danger fs-4"></i>';
    } else {
        titleEl.textContent = 'Activate Organisation?';
        msgEl.textContent = `Are you sure you want to activate "${orgName}"? Access for its administrators and users will be restored.`;
        btnEl.className = 'ks-btn ks-btn-primary';
        btnEl.textContent = 'Activate';
        iconEl.className = 'ks-icon-box ks-icon-green';
        iconEl.innerHTML = '<i class="bi bi-play-circle-fill text-success fs-4"></i>';
    }

    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
}

document.getElementById('btnExecuteStatusChange').addEventListener('click', async function() {
    if (!pendingStatusAction) return;
    const { orgId, targetStatus } = pendingStatusAction;
    this.disabled = true;
    this.textContent = 'Updating...';

    try {
        const res = await fetch('/api/v1/organizations/' + orgId + '/status', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                status: targetStatus,
                remarks: 'Status updated via Super Admin Organisations console'
            })
        });

        const json = await res.json();
        if (json.success) {
            if (window.ksToast) {
                window.ksToast(`Organisation status updated to ${targetStatus}.`, 'success');
            }
            const modalEl = document.getElementById('statusConfirmModal');
            const bsModal = bootstrap.Modal.getInstance(modalEl);
            if (bsModal) bsModal.hide();
            fetchFilteredOrganizations();
        } else {
            alert('Error: ' + (json.message || 'Status update failed'));
        }
    } catch (e) {
        alert('Network request failed');
    } finally {
        this.disabled = false;
        this.textContent = 'Confirm';
    }
});
</script>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
