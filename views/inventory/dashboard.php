<?php
$statusLabels = [
    'critical' => ['label' => 'Critical', 'icon' => 'fa-circle', 'class' => 'critical'],
    'warning'  => ['label' => 'Low', 'icon' => 'fa-circle', 'class' => 'warning'],
    'ok'       => ['label' => 'Healthy', 'icon' => 'fa-circle', 'class' => 'ok'],
];
?>
<link href="<?= asset('css/inventory-forecast.css') ?>" rel="stylesheet">

<?php uiPageHeader(
    'Smart Inventory Forecasting',
    'Predict stock depletion, receive low-stock alerts, and plan procurement from live records',
    '<a href="' . baseUrl('inventory/procurement') . '" class="btn-saas btn-saas-primary btn-saas-sm"><i class="fa-solid fa-cart-shopping"></i> Procurement</a>'
); ?>

<?php uiQuickActions([
    ['icon' => 'fa-plus', 'label' => 'Add Bin', 'route' => 'inventory/bins'],
    ['icon' => 'fa-cart-shopping', 'label' => 'New Request', 'route' => 'inventory/procurement'],
    ['icon' => 'fa-download', 'label' => 'Export', 'route' => 'inventory/reports'],
]); ?>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Total Bins', $totals['total'], 'fa-dumpster', 'primary', 'All warehouse bins', 0);
    uiKpi('Available', $totals['available'], 'fa-circle-check', 'success', 'Ready to assign', 1);
    uiKpi('Assigned', $totals['assigned'], 'fa-user-check', 'info', 'With residents', 2);
    uiKpi('Damaged', $totals['damaged'], 'fa-triangle-exclamation', 'danger', 'Needs repair/replace', 3);
    ?>
</div>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Maintenance', $totals['maintenance'], 'fa-wrench', 'warning', 'Under repair', 0);
    uiKpi('Returned', $totals['returned'], 'fa-rotate-left', 'secondary', 'Back in warehouse', 1);
    uiKpi('Retired', $totals['retired'], 'fa-box-archive', 'secondary', 'End of lifecycle', 2);
    uiKpi('Pending Orders', $procurementStats['pending'] ?? 0, 'fa-clock', 'info', 'Procurement queue', 3);
    ?>
</div>

<?php if ($alerts): ?>
<div class="glass-card saas-card mb-4 animate-in">
    <div class="saas-card-header">
        <div class="saas-card-title" style="color:var(--color-warning)">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>Low Stock Alerts
        </div>
    </div>
    <div class="saas-card-body pt-0">
        <?php foreach ($alerts as $alert): ?>
        <div class="inv-alert-banner <?= e($alert['status']) ?> mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <strong><i class="fa-solid fa-triangle-exclamation me-1"></i> LOW STOCK — <?= e($alert['label']) ?> bins</strong>
                <span class="inv-status-pill <?= e($alert['status']) ?>">
                    <i class="fa-solid fa-circle" style="font-size:0.45rem"></i>
                    <?= e(ucfirst($alert['status'])) ?>
                </span>
            </div>
            <p class="mb-2 small">Stock has fallen below the configured minimum threshold.</p>
            <div class="row g-2 small">
                <div class="col-sm-4"><strong>Current stock:</strong> <?= (int)$alert['current_stock'] ?></div>
                <div class="col-sm-4"><strong>Minimum:</strong> <?= (int)$alert['minimum'] ?></div>
                <div class="col-sm-4"><strong>Recommended reorder:</strong> <?= $alert['recommended_reorder'] !== null ? (int)$alert['recommended_reorder'] : 'N/A' ?></div>
            </div>
            <?php if (!empty($alert['limited_data'])): ?>
            <div class="inv-limited-note mt-2">
                <i class="fa-solid fa-circle-info"></i>
                Limited historical data — forecast based on available records.
            </div>
            <?php elseif (!empty($alert['no_history'])): ?>
            <div class="inv-limited-note mt-2">
                <i class="fa-solid fa-circle-info"></i>
                No sufficient inventory history available for forecasting.
            </div>
            <?php endif; ?>
            <?php if (!empty($procurementReady) && $alert['recommended_reorder'] !== null): ?>
            <div class="mt-3">
                <a href="<?= baseUrl('inventory/procurement') ?>&size=<?= e($alert['size']) ?>&qty=<?= (int)$alert['recommended_reorder'] ?>"
                   class="btn-saas btn-saas-primary btn-saas-sm">
                    <i class="fa-solid fa-cart-plus"></i> Create Procurement Request
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header">
                <div class="saas-card-title"><i class="fa-solid fa-layer-group me-2"></i>Bin Lifecycle by Size</div>
            </div>
            <div class="saas-card-body">
                <div class="inv-lifecycle-grid">
                    <?php foreach ($lifecycle as $size => $row): ?>
                    <div class="inv-size-card">
                        <h6><i class="fa-solid fa-trash-can" style="color:var(--color-primary)"></i> <?= e($row['label']) ?> Bins</h6>
                        <?php
                        $metrics = [
                            'Total' => $row['total'],
                            'Available' => $row['available'],
                            'Assigned' => $row['assigned'],
                            'Active' => $row['active'],
                            'Damaged' => $row['damaged'],
                            'Maintenance' => $row['maintenance'],
                            'Returned' => $row['returned'],
                            'Retired' => $row['retired'],
                        ];
                        foreach ($metrics as $label => $value): ?>
                        <div class="inv-metric-row">
                            <span><?= e($label) ?></span>
                            <strong><?= (int)$value ?></strong>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header">
                <div class="saas-card-title"><i class="fa-solid fa-warehouse me-2"></i>Current Stock Levels</div>
            </div>
            <div class="saas-card-body">
                <div class="row g-3">
                    <?php foreach ($stock as $item): ?>
                    <div class="col-12">
                        <div class="inv-stock-card">
                            <div class="text-secondary small fw-semibold mb-1"><?= e($item['label']) ?> Bins</div>
                            <div class="inv-stock-value"><?= (int)$item['available'] ?></div>
                            <div class="small text-secondary">Available · Min <?= (int)$item['minimum'] ?> · Total <?= (int)$item['total'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="glass-card saas-card animate-in">
            <div class="saas-card-header">
                <div class="saas-card-title"><i class="fa-solid fa-chart-line me-2"></i>Demand Forecast & Procurement</div>
                <small class="text-secondary">Based on <?= (int)$settings['lookback_days'] ?>-day assignment history</small>
            </div>
            <div class="saas-card-body">
                <div class="row g-3">
                    <?php foreach ($forecasts as $f):
                        $st = $statusLabels[$f['status']] ?? $statusLabels['ok'];
                    ?>
                    <div class="col-lg-4">
                        <div class="inv-forecast-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6 class="mb-0 fw-bold"><?= e($f['label']) ?> Bins</h6>
                                <span class="inv-status-pill <?= e($st['class']) ?>">
                                    <i class="fa-solid <?= e($st['icon']) ?>" style="font-size:0.45rem"></i>
                                    <?= e($st['label']) ?>
                                </span>
                            </div>
                            <div class="inv-metric-row"><span>Current stock</span><strong><?= (int)$f['current_stock'] ?></strong></div>
                            <div class="inv-metric-row"><span>Avg monthly usage</span><strong><?= !empty($f['no_history']) ? 'N/A' : e(number_format((float)$f['avg_monthly_usage'], 1)) ?></strong></div>
                            <div class="inv-metric-row">
                                <span>Est. depletion</span>
                                <strong><?= e($f['depletion_label'] ?? 'N/A — insufficient usage history') ?></strong>
                            </div>
                            <div class="inv-metric-row"><span>Minimum threshold</span><strong><?= (int)$f['minimum'] ?></strong></div>
                            <?php if ($f['recommended_reorder'] !== null): ?>
                            <div class="inv-procure-rec mt-3">
                                <i class="fa-solid fa-lightbulb me-1"></i>
                                Recommended order: <strong><?= (int)$f['recommended_reorder'] ?> × <?= e($f['label']) ?> bins</strong>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($f['no_history'])): ?>
                            <div class="inv-limited-note">
                                <i class="fa-solid fa-circle-info"></i>
                                No sufficient inventory history available for forecasting.
                            </div>
                            <?php elseif (!empty($f['limited_data'])): ?>
                            <div class="inv-limited-note">
                                <i class="fa-solid fa-circle-info"></i>
                                Limited historical data — forecast based on available records.
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($procurementReady) && $f['recommended_reorder'] !== null): ?>
                            <a href="<?= baseUrl('inventory/procurement') ?>&size=<?= e($f['size']) ?>&qty=<?= (int)$f['recommended_reorder'] ?>"
                               class="btn-saas btn-saas-outline btn-saas-sm w-100 mt-3">
                                <i class="fa-solid fa-cart-shopping"></i> Request Procurement
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="glass-card saas-card animate-in inv-chart-card h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-chart-area me-2"></i>Bin Allocation Trend</div></div>
            <div class="saas-card-body">
                <?php if (empty($hasTrendData)): ?>
                    <p class="text-secondary small mb-0 p-3">No inventory data available for allocation trends yet.</p>
                <?php else: ?>
                    <canvas id="invAllocChart" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card saas-card animate-in inv-chart-card h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-rotate-left me-2"></i>Returns & Damaged</div></div>
            <div class="saas-card-body">
                <?php if (empty($hasTrendData)): ?>
                    <p class="text-secondary small mb-0 p-3">No inventory data available for returns and damage trends yet.</p>
                <?php else: ?>
                    <canvas id="invReturnChart" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card saas-card animate-in inv-chart-card h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-chart-column me-2"></i>Monthly Usage by Size</div></div>
            <div class="saas-card-body">
                <?php if (empty($hasTrendData)): ?>
                    <p class="text-secondary small mb-0 p-3">No inventory data available for usage trends yet.</p>
                <?php else: ?>
                    <canvas id="invUsageChart" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card saas-card animate-in inv-chart-card h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-chart-pie me-2"></i>Current Stock by Size</div></div>
            <div class="saas-card-body">
                <?php if (($totals['available'] ?? 0) < 1): ?>
                    <p class="text-secondary small mb-0 p-3">No available stock to display.</p>
                <?php else: ?>
                    <canvas id="invStockChart" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-arrows-turn-to-dots me-2"></i>Recent Movements</div></div>
            <div class="saas-card-body pt-0">
                <?php if (!$movements): ?>
                    <?php uiEmptyState('fa-arrows-turn-to-dots', 'No movements yet', 'Movements appear when bins are delivered, assigned, or returned.'); ?>
                <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                    <div class="inv-movement-item">
                        <div>
                            <strong><?= e(ucfirst(str_replace('_', ' ', $m['movement_type']))) ?></strong>
                            <div class="text-secondary"><?= e($m['bin_code']) ?> · <?= e(binCapacity($m['size'])) ?>L</div>
                        </div>
                        <div class="text-end text-secondary">
                            <div><?= e(formatDateTime($m['created_at'])) ?></div>
                            <div><?= e(trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''))) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-chart-line me-2"></i>Monthly Usage</div></div>
            <div class="saas-card-body inv-chart-card">
                <?php if (empty($hasTrendData)): ?>
                    <p class="text-secondary small mb-0 p-3">No inventory data available for monthly usage yet.</p>
                <?php else: ?>
                    <canvas id="invMonthlyChart" height="200"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    if (typeof Chart === 'undefined') return;

    const labels = <?= json_encode($trends['labels']) ?>;
    const monthKeys = <?= json_encode($trends['month_keys']) ?>;
    const bySize = <?= json_encode($trends['by_size']) ?>;
    const stockNow = <?= json_encode($trends['stock_now']) ?>;
    const hasTrendData = <?= !empty($hasTrendData) ? 'true' : 'false' ?>;

    function mapSizeSeries(map, keys) {
        return keys.map(function (k) { return (map && map[k]) ? map[k] : 0; });
    }

    if (hasTrendData) {
        if (document.getElementById('invAllocChart')) {
            initMultiLineChart('invAllocChart', labels, [
                { label: '120L', data: mapSizeSeries(bySize.small, monthKeys) },
                { label: '240L', data: mapSizeSeries(bySize.medium, monthKeys) },
                { label: '360L', data: mapSizeSeries(bySize.large, monthKeys) }
            ]);
        }
        if (document.getElementById('invReturnChart')) {
            initMultiLineChart('invReturnChart', labels, [
                { label: 'Returns', data: <?= json_encode($trends['returns']) ?> },
                { label: 'Damaged / Repair', data: <?= json_encode($trends['damaged']) ?> }
            ]);
        }
        if (document.getElementById('invUsageChart')) {
            initStackedBarChart('invUsageChart', labels, [
                { label: '120L', data: mapSizeSeries(bySize.small, monthKeys), backgroundColor: 'rgba(16,185,129,0.75)' },
                { label: '240L', data: mapSizeSeries(bySize.medium, monthKeys), backgroundColor: 'rgba(99,102,241,0.75)' },
                { label: '360L', data: mapSizeSeries(bySize.large, monthKeys), backgroundColor: 'rgba(245,158,11,0.75)' }
            ]);
        }
        if (document.getElementById('invMonthlyChart')) {
            initLineChart('invMonthlyChart', labels, <?= json_encode($trends['usage']) ?>, 'Assignments');
        }
    }

    if (document.getElementById('invStockChart')) {
        initPieChart('invStockChart',
            ['120L', '240L', '360L'],
            [stockNow.small || 0, stockNow.medium || 0, stockNow.large || 0]
        );
    }
})();
</script>
