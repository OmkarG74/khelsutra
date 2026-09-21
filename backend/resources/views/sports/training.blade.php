<?php
$pageTitle = 'Training — KhelSutra';
$activePage = 'training';
$orgId = current_organization_id();

$trainService = new \App\Services\Training\TrainingService();
$page = (int)($_GET['page'] ?? 1);
$search = trim($_GET['search'] ?? '');
$date = trim($_GET['date'] ?? '');
$status = trim($_GET['status'] ?? '');

$result = $trainService->listSessions($orgId, $page, 15, $search ?: null, $date ?: null, $status ?: null);
$sessions = $result['data'] ?? [];
$total = $result['total'] ?? 0;
$totalPages = $result['total_pages'] ?? 1;

ob_start();
?>

<div class="ks-content">
    <!-- Clean Page Header Standard -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold mb-0" style="color: var(--ks-navy); letter-spacing: -0.02em;">Training</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="/training/create" class="btn btn-primary d-inline-flex align-items-center gap-2" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 600; font-size: 13px; padding: 9px 18px;">
                <i class="bi bi-plus-lg"></i> Schedule Training
            </a>
        </div>
    </div>

    <!-- Search & Filters Toolbar -->
    <div class="card p-3 mb-4" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff;">
        <form method="GET" action="/training" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0" style="border-color: var(--ks-border); border-radius: var(--ks-radius-button) 0 0 var(--ks-radius-button);">
                        <i class="bi bi-search text-muted" style="font-size: 13px;"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search session title, team, drill type..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" style="border-color: var(--ks-border); border-radius: 0 var(--ks-radius-button) var(--ks-radius-button) 0; font-size: 13px;">
                </div>
            </div>
            <div class="col-md-3">
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" style="border-color: var(--ks-border); border-radius: var(--ks-radius-button); font-size: 13px;">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select" style="border-color: var(--ks-border); border-radius: var(--ks-radius-button); font-size: 13px;">
                    <option value="">All Statuses</option>
                    <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 500; font-size: 13px;">
                    Filter
                </button>
                <?php if ($search || $date || $status): ?>
                    <a href="/training" class="btn btn-outline-secondary" style="border-radius: var(--ks-radius-button); font-size: 13px;" title="Reset filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Training Sessions Table -->
    <div class="card" style="border: 1px solid var(--ks-border); border-radius: var(--ks-radius-card); background: #fff; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead style="background: var(--ks-page-bg); border-bottom: 1px solid var(--ks-border);">
                    <tr>
                        <th class="py-3 px-3 text-muted fw-semibold" style="width: 250px;">Session / Title</th>
                        <th class="py-3 px-3 text-muted fw-semibold">Team & Sport</th>
                        <th class="py-3 px-3 text-muted fw-semibold">Coach</th>
                        <th class="py-3 px-3 text-muted fw-semibold">Date & Time</th>
                        <th class="py-3 px-3 text-muted fw-semibold">Venue / Facility</th>
                        <th class="py-3 px-3 text-muted fw-semibold">Status</th>
                        <th class="py-3 px-3 text-muted fw-semibold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($sessions)): ?>
                        <?php foreach ($sessions as $sess): ?>
                            <tr>
                                <td class="py-3 px-3">
                                    <a href="/training/<?= (int)$sess['id'] ?>" class="fw-semibold text-decoration-none text-dark d-block">
                                        <?= htmlspecialchars($sess['title'] ?: ($sess['training_type'] ?? 'Training Session'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <span class="badge bg-light text-secondary border px-2 py-0" style="font-family: monospace; font-size: 10px;">
                                        <?= htmlspecialchars($sess['training_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="fw-medium text-dark"><?= htmlspecialchars($sess['team_name'] ?? 'General Academy', ENT_QUOTES, 'UTF-8') ?></div>
                                    <span class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($sess['sport_name'] ?? 'Sport', ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($sess['training_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td class="py-3 px-3 text-dark">
                                    <?= htmlspecialchars($sess['coach_name'] ?? 'Unassigned', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="fw-medium text-dark"><?= date('M d, Y', strtotime($sess['training_date'])) ?></div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        <?= htmlspecialchars(substr($sess['start_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars(substr($sess['end_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="text-dark"><?= htmlspecialchars($sess['venue_name'] ?? 'Academy Grounds', ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($sess['facility_name'])): ?>
                                        <span class="badge bg-light text-muted border px-1" style="font-size: 10px;"><?= htmlspecialchars($sess['facility_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-3">
                                    <?php
                                        $badge = match($sess['status'] ?? 'scheduled') {
                                            'completed' => 'badge-success',
                                            'in_progress' => 'badge-warning',
                                            'cancelled' => 'badge-danger',
                                            default => 'badge-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $badge ?>" style="border-radius: 12px; font-size: 11px; padding: 4px 10px; text-transform: capitalize;">
                                        <?= htmlspecialchars(str_replace('_', ' ', $sess['status'] ?? 'scheduled'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/training/<?= (int)$sess['id'] ?>" class="btn btn-outline-secondary" style="border-radius: 6px 0 0 6px;" title="View Session & Attendance">
                                            <i class="bi bi-card-checklist"></i>
                                        </a>
                                        <a href="/training/<?= (int)$sess['id'] ?>/edit" class="btn btn-outline-secondary" style="border-radius: 0 6px 6px 0;" title="Edit Session">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted mb-2"><i class="bi bi-stopwatch fs-2"></i></div>
                                <h6 class="fw-bold" style="color: var(--ks-navy);">No training sessions found</h6>
                                <p class="text-muted small mb-3">No sessions have been scheduled matching the selected filters.</p>
                                <a href="/training/create" class="btn btn-sm btn-primary" style="background: var(--ks-blue); border-color: var(--ks-blue); border-radius: var(--ks-radius-button); font-weight: 500;">
                                    <i class="bi bi-plus-lg me-1"></i> Schedule Training
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total > 0): ?>
            <div class="card-footer d-flex align-items-center justify-content-between py-3 px-3 bg-white" style="border-top: 1px solid var(--ks-border);">
                <div class="text-muted small">
                    Showing <strong><?= count($sessions) ?></strong> of <strong><?= (int)$total ?></strong> sessions
                </div>
                <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>&status=<?= urlencode($status) ?>">Previous</a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>&status=<?= urlencode($status) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>&status=<?= urlencode($status) ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
