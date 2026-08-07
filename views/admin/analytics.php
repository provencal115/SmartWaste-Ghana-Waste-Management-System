<?php
$op = $report['operational'];
$perf = $report['performance'];
$rev = $report['revenue'];
$sat = $report['satisfaction'];
$coll = $report['collections'];
$filterAction = baseUrl('admin/analytics');

uiPageHeader(
    'Operational Intelligence',
    'Business intelligence and KPI analytics from live database records',
    '<div class="d-flex flex-wrap gap-2">'
    . '<a href="' . baseUrl('api/analytics/export') . '&format=csv&' . http_build_query(array_filter($filters)) . '" class="btn-saas btn-saas-outline btn-saas-sm"><i class="fa-solid fa-file-csv"></i> CSV</a>'
    . '<a href="' . baseUrl('api/analytics/export') . '&format=pdf&' . http_build_query(array_filter($filters)) . '" class="btn-saas btn-saas-outline btn-saas-sm" target="_blank"><i class="fa-solid fa-file-pdf"></i> PDF</a>'
    . '<a href="' . baseUrl('api/analytics/export') . '&format=xlsx&' . http_build_query(array_filter($filters)) . '" class="btn-saas btn-saas-outline btn-saas-sm"><i class="fa-solid fa-file-excel"></i> Excel</a>'
    . '</div>'
);
?>

<link href="<?= asset('css/analytics.css') ?>" rel="stylesheet">

<div class="glass-card saas-card animate-in mb-4 analytics-filter-card">
    <div class="saas-card-body">
        <form method="get" action="<?= e($filterAction) ?>" class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-2">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label">Zone</label>
                <select name="zone_id" class="form-select">
                    <option value="">All zones</option>
                    <?php foreach ($zones as $z): ?>
                    <option value="<?= (int)$z['id'] ?>"<?= ($filters['zone_id'] ?? null) === (int)$z['id'] ? ' selected' : '' ?>><?= e($z['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label">Collector</label>
                <select name="collector_id" class="form-select">
                    <option value="">All collectors</option>
                    <?php foreach ($collectors as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"<?= ($filters['collector_id'] ?? null) === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['first_name'] . ' ' . $c['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label">Vehicle</label>
                <select name="truck_id" class="form-select">
                    <option value="">All vehicles</option>
                    <?php foreach ($trucks as $t): ?>
                    <option value="<?= (int)$t['id'] ?>"<?= ($filters['truck_id'] ?? null) === (int)$t['id'] ? ' selected' : '' ?>><?= e($t['plate_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <?php foreach (['scheduled', 'in_progress', 'completed', 'delayed', 'missed'] as $st): ?>
                    <option value="<?= e($st) ?>"<?= ($filters['status'] ?? '') === $st ? ' selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $st))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn-saas btn-saas-primary btn-saas-sm"><i class="fa-solid fa-filter"></i> Apply Filters</button>
                <a href="<?= baseUrl('admin/analytics') ?>" class="btn-saas btn-saas-ghost btn-saas-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Registered Residents', $op['registered_residents'], 'fa-users', 'primary', 'Confirmed accounts', 0);
    uiKpi('Active Customers', $op['active_customers'], 'fa-user-check', 'success', 'Active service users', 1);
    uiKpi('Total Collections', $op['total_collections'], 'fa-calendar-check', 'info', 'In selected scope', 2);
    uiKpi('Completed', $op['completed_collections'], 'fa-circle-check', 'success', 'Successfully collected', 3);
    ?>
</div>
<div class="row g-4 mb-4">
    <?php
    uiKpi('Pending', $op['pending_collections'], 'fa-clock', 'warning', 'Awaiting pickup', 0);
    uiKpi('Missed', $op['missed_collections'], 'fa-calendar-xmark', 'danger', 'Requires follow-up', 1);
    uiKpi('Delayed', $op['delayed_collections'], 'fa-hourglass-half', 'warning', 'Behind schedule', 2);
    uiKpi('Total Revenue', formatCurrency($rev['total']), 'fa-sack-dollar', 'purple', formatCurrency($rev['monthly']) . ' this month', 3);
    ?>
</div>
<div class="row g-4 mb-4">
    <?php
    uiKpi('Active Vehicles', $op['active_vehicles'], 'fa-truck', 'primary', 'Fleet operational', 0);
    uiKpi('Available Bins', $op['available_bins'], 'fa-dumpster', 'info', 'Ready to assign', 1);
    uiKpi('Avg Rating', $sat['average'] . ' / 5', 'fa-star', 'warning', $sat['total_ratings'] . ' ratings', 2);
    uiKpi('Completed Txns', $rev['completed_transactions'], 'fa-receipt', 'success', 'Successful payments', 3);
    ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-chart-simple me-2"></i>Collection Performance</div></div>
            <div class="saas-card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3"><?php uiPerformanceMetric('Completion Rate', $perf['completion_rate'], 'success'); ?></div>
                    <div class="col-6 col-md-3"><?php uiPerformanceMetric('On-Time Rate', $perf['on_time_rate'], 'info'); ?></div>
                    <div class="col-6 col-md-3"><?php uiPerformanceMetric('Missed Rate', $perf['missed_rate'], 'danger'); ?></div>
                    <div class="col-6 col-md-3"><?php uiPerformanceMetric('Delayed Rate', $perf['delayed_rate'], 'warning'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-star me-2"></i>Customer Satisfaction</div></div>
            <div class="saas-card-body text-center">
                <div class="satisfaction-score text-success mb-1"><?= e(number_format($sat['average'], 1)) ?><span class="fs-5 text-secondary"> / 5</span></div>
                <div class="rating-stars mb-3"><?= str_repeat('★', (int)round($sat['average'])) . str_repeat('☆', 5 - (int)round($sat['average'])) ?></div>
                <canvas id="ratingChart" height="140"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="glass-card saas-card animate-in revenue-chart-wrap h-100">
            <div class="saas-card-header d-flex flex-wrap justify-content-between gap-2">
                <div class="saas-card-title"><i class="fa-solid fa-chart-line me-2"></i>Revenue Trends</div>
                <div class="btn-group btn-group-sm revenue-period-toggle">
                    <button type="button" class="btn btn-saas-outline" data-revenue-period="7">7 Days</button>
                    <button type="button" class="btn btn-saas-outline active" data-revenue-period="30">30 Days</button>
                    <button type="button" class="btn btn-saas-outline" data-revenue-period="180">6 Months</button>
                    <button type="button" class="btn btn-saas-outline" data-revenue-period="365">1 Year</button>
                </div>
            </div>
            <div class="saas-card-body"><canvas id="revenueChart" height="100"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-wallet me-2"></i>Revenue Summary</div></div>
            <div class="saas-card-body">
                <?php
                uiProgressBar('Weekly Revenue', min(100, ($rev['total'] > 0 ? ($rev['weekly'] / max(1, $rev['total'])) * 100 * 4 : 0)), formatCurrency($rev['weekly']));
                uiProgressBar('Monthly Revenue', min(100, ($rev['total'] > 0 ? ($rev['monthly'] / max(1, $rev['total'])) * 100 * 2 : 0)), formatCurrency($rev['monthly']));
                ?>
                <div class="list-item"><span>Avg Customer Payment</span><strong><?= formatCurrency($rev['avg_customer_payment']) ?></strong></div>
                <div class="list-item"><span>Pending Payments</span><strong><?= (int)$rev['pending_payments'] ?></strong></div>
                <div class="list-item"><span>Overdue Payments</span><strong class="text-danger"><?= (int)$rev['overdue_payments'] ?></strong></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="glass-card saas-card animate-in collection-chart-wrap h-100">
            <div class="saas-card-header d-flex flex-wrap justify-content-between gap-2">
                <div class="saas-card-title"><i class="fa-solid fa-recycle me-2"></i>Collection Trends</div>
                <div class="btn-group btn-group-sm collection-period-toggle">
                    <button type="button" class="btn btn-saas-outline active" data-collection-period="daily">Daily</button>
                    <button type="button" class="btn btn-saas-outline" data-collection-period="weekly">Weekly</button>
                    <button type="button" class="btn btn-saas-outline" data-collection-period="monthly">Monthly</button>
                </div>
            </div>
            <div class="saas-card-body"><canvas id="collectionTrendChart" height="100"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-map-location-dot me-2"></i>Zone Performance</div></div>
            <div class="saas-card-body">
                <?php if (empty($report['zones'])): ?>
                <p class="text-secondary small mb-0">No zone data for the selected filters.</p>
                <?php else: foreach ($report['zones'] as $z): ?>
                <div class="zone-performance-row">
                    <div style="min-width:100px"><strong class="small"><?= e($z['name']) ?></strong></div>
                    <div class="zone-performance-bar">
                        <div class="progress-premium"><div class="progress-premium-bar" data-progress="<?= (float)$z['completion_rate'] ?>" style="width:0"></div></div>
                    </div>
                    <strong class="small text-success"><?= e($z['completion_rate']) ?>%</strong>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="glass-card saas-card animate-in">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-truck me-2"></i>Vehicle Performance</div></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 analytics-table-compact">
                    <thead><tr><th>Vehicle</th><th>Completed</th><th>Missed</th><th>Avg/Day</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($report['vehicles'] as $v): ?>
                    <tr>
                        <td><strong><?= e($v['plate_number']) ?></strong></td>
                        <td><?= (int)$v['completed'] ?></td>
                        <td><?= (int)$v['missed'] ?></td>
                        <td><?= e($v['avg_collections_per_day']) ?></td>
                        <td><?= truckStatusBadge($v['maintenance_status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card saas-card animate-in">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-user-gear me-2"></i>Collector Performance</div></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 analytics-table-compact">
                    <thead><tr><th>Collector</th><th>Completed</th><th>Missed</th><th>Completion</th></tr></thead>
                    <tbody>
                    <?php foreach ($report['collectors'] as $c): ?>
                    <tr>
                        <td><strong><?= e($c['name']) ?></strong><br><small class="text-secondary"><?= e($c['employee_id']) ?></small></td>
                        <td><?= (int)$c['completed'] ?></td>
                        <td><?= (int)$c['missed'] ?></td>
                        <td><span class="badge bg-success-subtle text-success"><?= e($c['completion_rate']) ?>%</span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$chart7 = $rev['trend_7d'] ?? [];
$chart30 = $rev['trend_30d'] ?? [];
$chart6mo = $rev['trend_6mo'] ?? [];
$chart1y = $rev['trend_1y'] ?? [];

$dailyLabels = array_map(fn ($r) => date('M j', strtotime($r['period'])), $coll['daily'] ?? []);
$weeklyLabels = array_map(fn ($r) => date('M j', strtotime($r['period'])), $coll['weekly'] ?? []);
$monthlyLabels = array_map(fn ($r) => $r['period'], $coll['monthly'] ?? []);
?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    initRevenueTrendChart('revenueChart', {
        '7': {
            labels: <?= json_encode(array_map(fn ($r) => date('M j', strtotime($r['date'])), $chart7)) ?>,
            data: <?= json_encode(array_map(fn ($r) => (float)$r['revenue'], $chart7)) ?>
        },
        '30': {
            labels: <?= json_encode(array_map(fn ($r) => date('M j', strtotime($r['date'])), $chart30)) ?>,
            data: <?= json_encode(array_map(fn ($r) => (float)$r['revenue'], $chart30)) ?>
        },
        '180': {
            labels: <?= json_encode(array_column($chart6mo, 'label')) ?>,
            data: <?= json_encode(array_map(fn ($r) => (float)$r['revenue'], $chart6mo)) ?>
        },
        '365': {
            labels: <?= json_encode(array_column($chart1y, 'label')) ?>,
            data: <?= json_encode(array_map(fn ($r) => (float)$r['revenue'], $chart1y)) ?>
        }
    }, '30');

    initCollectionTrendChart('collectionTrendChart', {
        daily: {
            labels: <?= json_encode($dailyLabels) ?>,
            completed: <?= json_encode(array_map(fn ($r) => (int)$r['completed'], $coll['daily'] ?? [])) ?>,
            missed: <?= json_encode(array_map(fn ($r) => (int)$r['missed'], $coll['daily'] ?? [])) ?>,
            delayed: <?= json_encode(array_map(fn ($r) => (int)$r['delayed'], $coll['daily'] ?? [])) ?>
        },
        weekly: {
            labels: <?= json_encode($weeklyLabels) ?>,
            completed: <?= json_encode(array_map(fn ($r) => (int)$r['completed'], $coll['weekly'] ?? [])) ?>,
            missed: <?= json_encode(array_map(fn ($r) => (int)$r['missed'], $coll['weekly'] ?? [])) ?>,
            delayed: <?= json_encode(array_map(fn ($r) => (int)$r['delayed'], $coll['weekly'] ?? [])) ?>
        },
        monthly: {
            labels: <?= json_encode($monthlyLabels) ?>,
            completed: <?= json_encode(array_map(fn ($r) => (int)$r['completed'], $coll['monthly'] ?? [])) ?>,
            missed: <?= json_encode(array_map(fn ($r) => (int)$r['missed'], $coll['monthly'] ?? [])) ?>,
            delayed: <?= json_encode(array_map(fn ($r) => (int)$r['delayed'], $coll['monthly'] ?? [])) ?>
        }
    });

    initRatingChart('ratingChart', <?= json_encode(array_values($sat['distribution'])) ?>);
});
</script>
