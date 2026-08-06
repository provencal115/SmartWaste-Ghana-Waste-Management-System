<?php
$settingsMap = [];
foreach ($settings as $s) {
    $settingsMap[$s['setting_key']] = json_decode($s['setting_value'], true) ?: [];
}
$routeOpt = $settingsMap['route_optimization'] ?? [];
$binFull = $settingsMap['bin_fullness_prediction'] ?? [];
$demand = $settingsMap['demand_prediction'] ?? [];
$autoRes = $settingsMap['auto_reschedule'] ?? [];
$reminder = $settingsMap['reminder_system'] ?? [];
?>
<?php uiPageHeader('System Settings', 'Configure AI automation, reminders, and smart collection features'); ?>

<form method="post" action="<?= baseUrl('admin/settings') ?>" id="settingsForm" data-validate>
    <?= Csrf::field() ?>

    <div class="settings-grid mb-4">
        <!-- AI & Automation -->
        <div class="settings-section animate-in">
            <div class="settings-section-head">
                <div class="settings-section-icon" style="background:var(--color-primary-light);color:var(--color-primary)">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <div>
                    <h5 class="settings-section-title">AI & Automation</h5>
                    <p class="settings-section-desc">Intelligent routing and predictive collection features</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="settings-card">
                        <div class="settings-card-head">
                            <div class="settings-card-icon"><i class="fa-solid fa-route"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Route Optimization</h6>
                                <small class="text-secondary">AI-powered route planning for collectors</small>
                            </div>
                            <div class="form-check form-switch settings-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="routeOptEnabled"
                                       name="route_optimization_enabled" value="1" <?= !empty($routeOpt['enabled']) ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <label class="form-label small fw-semibold">Algorithm</label>
                            <select name="route_optimization_algorithm" class="form-select form-select-sm settings-input">
                                <option value="nearest_neighbor" <?= ($routeOpt['algorithm'] ?? '') === 'nearest_neighbor' ? 'selected' : '' ?>>Nearest Neighbor</option>
                                <option value="genetic" <?= ($routeOpt['algorithm'] ?? '') === 'genetic' ? 'selected' : '' ?>>Genetic Algorithm</option>
                                <option value="cluster_first" <?= ($routeOpt['algorithm'] ?? '') === 'cluster_first' ? 'selected' : '' ?>>Cluster First</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="settings-card">
                        <div class="settings-card-head">
                            <div class="settings-card-icon"><i class="fa-solid fa-chart-line"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Demand Prediction</h6>
                                <small class="text-secondary">Forecast collection volume by zone</small>
                            </div>
                            <div class="form-check form-switch settings-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="demandEnabled"
                                       name="demand_prediction_enabled" value="1" <?= !empty($demand['enabled']) ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <label class="form-label small fw-semibold">Lookback Period (days)</label>
                            <input type="number" name="demand_lookback_days" class="form-control form-control-sm settings-input"
                                   min="7" max="365" value="<?= (int)($demand['lookback_days'] ?? 30) ?>">
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="settings-card">
                        <div class="settings-card-head">
                            <div class="settings-card-icon"><i class="fa-solid fa-trash-can"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Bin Fullness Prediction</h6>
                                <small class="text-secondary">Estimate when bins need collection</small>
                            </div>
                            <div class="form-check form-switch settings-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="binFullEnabled"
                                       name="bin_fullness_enabled" value="1" <?= !empty($binFull['enabled']) ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <label class="form-label small fw-semibold">Alert Threshold (%)</label>
                            <input type="range" name="bin_fullness_threshold" class="form-range settings-range"
                                   min="50" max="100" step="5" value="<?= (int)($binFull['threshold_percent'] ?? 80) ?>"
                                   oninput="this.nextElementSibling.textContent = this.value + '%'">
                            <span class="small fw-bold text-primary"><?= (int)($binFull['threshold_percent'] ?? 80) ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="settings-card">
                        <div class="settings-card-head">
                            <div class="settings-card-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Auto Reschedule</h6>
                                <small class="text-secondary">Automatically reschedule after delays or breakdowns</small>
                            </div>
                            <div class="form-check form-switch settings-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="autoResEnabled"
                                       name="auto_reschedule_enabled" value="1" <?= !empty($autoRes['enabled']) ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <label class="form-label small fw-semibold">Delay Buffer (minutes)</label>
                            <input type="number" name="auto_reschedule_delay" class="form-control form-control-sm settings-input"
                                   min="15" max="480" step="15" value="<?= (int)($autoRes['delay_minutes'] ?? 60) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="settings-section animate-in">
            <div class="settings-section-head">
                <div class="settings-section-icon" style="background:var(--color-warning-light);color:var(--color-warning)">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <div>
                    <h5 class="settings-section-title">Reminders & Notifications</h5>
                    <p class="settings-section-desc">Automated payment and pickup reminders for residents</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="settings-card">
                        <div class="settings-card-head">
                            <div class="settings-card-icon"><i class="fa-solid fa-credit-card"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Payment Reminders</h6>
                                <small class="text-secondary">Days before due date to notify residents</small>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="input-group input-group-sm">
                                <input type="number" name="reminder_payment_days" class="form-control settings-input"
                                       min="1" max="14" value="<?= (int)($reminder['payment_days_before'] ?? 3) ?>">
                                <span class="input-group-text">days before</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="settings-card">
                        <div class="settings-card-head">
                            <div class="settings-card-icon"><i class="fa-solid fa-truck"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Pickup Reminders</h6>
                                <small class="text-secondary">Hours before scheduled collection</small>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="input-group input-group-sm">
                                <input type="number" name="reminder_pickup_hours" class="form-control settings-input"
                                       min="1" max="72" value="<?= (int)($reminder['pickup_hours_before'] ?? 24) ?>">
                                <span class="input-group-text">hours before</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="settings-footer animate-in">
        <button type="submit" class="btn-saas btn-saas-primary btn-saas-lg">
            <i class="fa-solid fa-floppy-disk me-2"></i>Save Settings
        </button>
        <p class="text-secondary small mb-0 mt-2">Changes apply system-wide for all zones and users.</p>
    </div>
</form>

<script>
document.getElementById('settingsForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Save settings?',
        text: 'This will update system-wide configuration.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        confirmButtonText: 'Save Changes',
        customClass: { popup: 'swal-premium' }
    }).then(r => { if (r.isConfirmed) this.submit(); });
});
</script>
