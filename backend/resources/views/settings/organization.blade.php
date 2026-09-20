<?php
$activePage = 'settings';
$title = 'Organisation Settings — KhelSutra Platform';

$settingsService = new \App\Services\Organization\OrganizationSettingsService();
$orgId = current_organization_id();

$rawSettings = isset($settings) ? $settings : $settingsService->getSettingRows($orgId);

// Defensive normalization: ensure $settings is always an array of row records
$settings = [];
if (is_array($rawSettings)) {
    if (!empty($rawSettings) && !isset($rawSettings[0]) && is_string(array_key_first($rawSettings))) {
        // Associative map was returned, convert to record list
        foreach ($rawSettings as $k => $v) {
            $settings[] = [
                'setting_key' => (string)$k,
                'setting_value' => is_scalar($v) ? (string)$v : json_encode($v),
                'setting_type' => gettype($v)
            ];
        }
    } else {
        $settings = $rawSettings;
    }
}

ob_start();
?>

<!-- Page Header (Rule 7 & 8: Clean Page Title + Action, No Subtitle) -->
<div class="ks-page-header">
    <h1 class="ks-page-title">Organisation Settings</h1>
    <div class="ks-header-actions">
        <button class="ks-btn ks-btn-primary" onclick="document.getElementById('settingModal').style.display='flex'">
            <i class="bi bi-plus-lg"></i>
            <span>Add Configuration Key</span>
        </button>
    </div>
</div>

<div class="ks-table-card">
    <div class="ks-table-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-gear" style="color: var(--ks-primary); font-size: 18px;"></i>
            <span class="ks-card-title mb-0">Tenant Key-Value Store</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="ks-table">
            <thead>
                <tr>
                    <th>Setting Key</th>
                    <th>Value</th>
                    <th>Data Type</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($settings)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No settings defined yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($settings as $s): ?>
                        <?php
                        $sKey = is_array($s) ? ($s['setting_key'] ?? '') : '';
                        $sVal = is_array($s) ? ($s['setting_value'] ?? '') : (string)$s;
                        $sType = is_array($s) ? ($s['setting_type'] ?? 'string') : 'string';
                        ?>
                        <tr>
                            <td>
                                <code class="fw-bold text-navy" style="font-size: 13px;"><?= htmlspecialchars((string)$sKey, ENT_QUOTES, 'UTF-8') ?></code>
                            </td>
                            <td>
                                <span class="small font-monospace text-dark"><?= htmlspecialchars((string)($sVal !== '' ? $sVal : 'null'), ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <span class="ks-badge ks-badge-blue"><?= htmlspecialchars((string)$sType, ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td style="text-align: right;">
                                <button class="ks-btn ks-btn-secondary" style="height: 32px; padding: 0 10px; font-size: 12px;" onclick="editSetting(<?= htmlspecialchars(json_encode(['setting_key' => $sKey, 'setting_value' => $sVal, 'setting_type' => $sType]), ENT_QUOTES, 'UTF-8') ?>)">
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
<div id="settingModal" style="display: none; position: fixed; inset: 0; background: rgba(14, 30, 59, 0.45); z-index: 9999; align-items: center; justify-content: center;">
    <div class="ks-card p-4" style="width: 500px; max-width: 90%;">
        <h5 class="fw-bold text-navy mb-3">Save Organisation Setting</h5>
        <form id="settingForm">
            <div class="mb-3">
                <label class="ks-form-label">Setting Key <span class="text-danger">*</span></label>
                <input type="text" name="setting_key" id="setKey" class="ks-form-control" required placeholder="e.g. academy.max_athletes_per_coach">
            </div>
            <div class="mb-3">
                <label class="ks-form-label">Setting Type <span class="text-danger">*</span></label>
                <select name="setting_type" id="setType" class="ks-form-select">
                    <option value="string">string</option>
                    <option value="integer">integer</option>
                    <option value="decimal">decimal</option>
                    <option value="boolean">boolean</option>
                    <option value="json">json</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="ks-form-label">Setting Value <span class="text-danger">*</span></label>
                <textarea name="setting_value" id="setVal" class="ks-form-control" rows="3" required placeholder="Value..."></textarea>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="ks-btn ks-btn-secondary" onclick="document.getElementById('settingModal').style.display='none'">Cancel</button>
                <button type="submit" class="ks-btn ks-btn-primary">Save Setting</button>
            </div>
        </form>
    </div>
</div>

<script>
function editSetting(s) {
    document.getElementById('setKey').value = s.setting_key || '';
    document.getElementById('setType').value = s.setting_type || 'string';
    document.getElementById('setVal').value = s.setting_value || '';
    document.getElementById('settingModal').style.display = 'flex';
}

document.getElementById('settingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const payload = {
        setting_key: document.getElementById('setKey').value,
        setting_type: document.getElementById('setType').value,
        setting_value: document.getElementById('setVal').value
    };

    try {
        const res = await fetch('/api/v1/settings/organization', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            window.location.reload();
        } else {
            alert(result.message || 'Error saving setting');
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
