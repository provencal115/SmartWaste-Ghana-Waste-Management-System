<?php uiPageHeader('Admin Dashboard', 'System-wide analytics and monitoring', '<a href="' . baseUrl('admin/analytics') . '" class="btn-saas btn-saas-primary btn-saas-sm"><i class="fa-solid fa-chart-line"></i> Full Analytics</a>'); ?>

<link href="<?= asset('css/analytics.css') ?>" rel="stylesheet">

<?php if (($contactStats['unread'] ?? 0) > 0): ?>
<div class="alert alert-warning d-flex align-items-center justify-content-between gap-3 animate-in mb-4" role="alert">
    <div><i class="fa-solid fa-envelope me-2"></i><strong><?= (int) $contactStats['unread'] ?></strong> unread contact message<?= $contactStats['unread'] === 1 ? '' : 's' ?> awaiting review.</div>
    <a href="<?= baseUrl('admin/messages') ?>" class="btn-saas btn-saas-sm btn-saas-primary">View Messages</a>
</div>
<?php endif; ?>

<?php if (!empty($inventoryAlerts)): ?>
<div class="alert alert-danger d-flex align-items-center justify-content-between gap-3 animate-in mb-4" role="alert">
    <div>
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <strong>Low inventory stock:</strong>
        <?= count($inventoryAlerts) ?> bin size<?= count($inventoryAlerts) === 1 ? '' : 's' ?> below minimum threshold.
    </div>
    <a href="<?= baseUrl('inventory/dashboard') ?>" class="btn-saas btn-saas-sm btn-saas-primary">View Forecast</a>
</div>
<?php endif; ?>

<?php uiQuickActions([
    ['icon' => 'fa-chart-line', 'label' => 'Analytics', 'route' => 'admin/analytics'],
    ['icon' => 'fa-users', 'label' => 'Manage Users', 'route' => 'admin/users'],
    ['icon' => 'fa-route', 'label' => 'Route Optimisation', 'route' => 'admin/route-optimisation'],
    ['icon' => 'fa-envelope', 'label' => 'Contact Messages', 'route' => 'admin/messages', 'badge' => $contactStats['unread'] ?? 0],
    ['icon' => 'fa-truck', 'label' => 'Fleet', 'route' => 'admin/trucks'],
    ['icon' => 'fa-comments', 'label' => 'Complaints', 'route' => 'admin/complaints'],
    ['icon' => 'fa-sliders', 'label' => 'Settings', 'route' => 'admin/settings'],
]); ?>

<?php
$op = $analytics['operational'] ?? [];
$perf = $analytics['performance'] ?? [];
$sat = $analytics['satisfaction'] ?? [];
?>

<div class="operational-intelligence-header animate-in mb-3">
    <h5><i class="fa-solid fa-brain me-2 text-success"></i>Operational Intelligence</h5>
    <p>Live KPIs calculated from database records · <a href="<?= baseUrl('admin/analytics') ?>">Open full analytics with filters</a></p>
</div>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Registered Residents', $op['registered_residents'] ?? 0, 'fa-users', 'primary', ($op['active_customers'] ?? 0) . ' active', 0);
    uiKpi('Total Collections', $op['total_collections'] ?? 0, 'fa-calendar-check', 'info', ($op['completed_collections'] ?? 0) . ' completed', 1);
    uiKpi('Pending / Missed', ($op['pending_collections'] ?? 0) . ' / ' . ($op['missed_collections'] ?? 0), 'fa-triangle-exclamation', 'warning', ($op['delayed_collections'] ?? 0) . ' delayed', 2);
    uiKpi('Total Revenue', formatCurrency($op['total_revenue'] ?? 0), 'fa-sack-dollar', 'purple', 'Completed payments', 3);
    ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-gauge-high me-2"></i>Collection Performance</div></div>
            <div class="saas-card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3"><?php uiPerformanceMetric('Completion Rate', $perf['completion_rate'] ?? 0, 'success'); ?></div>
                    <div class="col-6 col-md-3"><?php uiPerformanceMetric('On-Time Rate', $perf['on_time_rate'] ?? 0, 'info'); ?></div>
                    <div class="col-6 col-md-3"><?php uiPerformanceMetric('Missed Rate', $perf['missed_rate'] ?? 0, 'danger'); ?></div>
                    <div class="col-6 col-md-3"><?php uiPerformanceMetric('Delayed Rate', $perf['delayed_rate'] ?? 0, 'warning'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-star me-2"></i>Customer Satisfaction</div></div>
            <div class="saas-card-body text-center d-flex flex-column justify-content-center">
                <div class="satisfaction-score text-success"><?= e(number_format($sat['average'] ?? 0, 1)) ?><span class="fs-5 text-secondary"> / 5</span></div>
                <div class="rating-stars"><?= str_repeat('★', (int)round($sat['average'] ?? 0)) . str_repeat('☆', 5 - (int)round($sat['average'] ?? 0)) ?></div>
                <p class="text-secondary small mb-0 mt-2"><?= (int)($sat['total_ratings'] ?? 0) ?> customer ratings</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Active Vehicles', $op['active_vehicles'] ?? 0, 'fa-truck', 'primary', 'Fleet ready', 0);
    uiKpi('Available Bins', $op['available_bins'] ?? 0, 'fa-dumpster', 'info', 'Inventory ready', 1);
    uiKpi('Collections Today', $collections['today'] ?? 0, 'fa-truck', 'success', ($collections['completed_today'] ?? 0) . ' completed today', 2);
    uiKpi('Staff Accounts', $stats['active_users'] ?? 0, 'fa-user-shield', 'secondary', 'Active users', 3);
    ?>
</div>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Total Messages', $contactStats['total'] ?? 0, 'fa-envelope', 'info', 'Contact form', 0);
    uiKpi('Unread Messages', $contactStats['unread'] ?? 0, 'fa-envelope-open', 'danger', 'Needs attention', 1);
    uiKpi('Messages Today', $contactStats['today'] ?? 0, 'fa-calendar-day', 'success', 'Received today', 2);
    ?>
</div>

<?php if (!empty($routeStats) && ($routeStats['total_routes'] ?? 0) > 0): ?>
<div class="row g-4 mb-4">
    <?php
    uiKpi('Optimised Routes', $routeStats['total_routes'], 'fa-route', 'success', ($routeStats['optimised_routes'] ?? 0) . ' pending dispatch', 0);
    uiKpi('Active Routes', $routeStats['active_routes'], 'fa-truck-fast', 'primary', 'In progress today', 1);
    uiKpi('Avg Route Distance', ($routeStats['avg_distance_km'] ?? 0) . ' km', 'fa-road', 'info', 'Haversine estimate', 2);
    uiKpi('Completed Routes', $routeStats['completed_routes'], 'fa-circle-check', 'purple', 'Historical runs', 3);
    ?>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="glass-card saas-card animate-in h-100 revenue-chart-wrap">
            <div class="saas-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="saas-card-title"><i class="fa-solid fa-chart-line me-2"></i>Revenue Trends</div>
                <div class="btn-group btn-group-sm revenue-period-toggle" role="group" aria-label="Revenue period">
                    <button type="button" class="btn btn-saas-outline active" data-revenue-period="7">7 Days</button>
                    <button type="button" class="btn btn-saas-outline" data-revenue-period="30">30 Days</button>
                    <button type="button" class="btn btn-saas-outline" data-revenue-period="180">6 Months</button>
                    <button type="button" class="btn btn-saas-outline" data-revenue-period="365">1 Year</button>
                </div>
            </div>
            <div class="saas-card-body"><canvas id="revenueChart" height="100"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-chart-pie me-2"></i>Bin Allocation</div></div>
            <div class="saas-card-body"><canvas id="binChart"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-calendar-day me-2"></i>Collection Status</div></div>
            <div class="saas-card-body">
                <div class="collection-status-grid mb-4">
                    <?php uiStatusMini('Today', $collections['today'] ?? 0);
                    uiStatusMini('Completed', $collections['completed_today'] ?? 0);
                    uiStatusMini('Missed (7d)', $collections['missed_week'] ?? 0);
                    uiStatusMini('Active Now', $stats['active_collections']); ?>
                </div>
                <?php
                $total = max(1, ($collections['today'] ?? 0));
                $done = $collections['completed_today'] ?? 0;
                uiProgressBar('Today\'s completion rate', ($done / $total) * 100, $done . ' / ' . $total);
                ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-calendar me-2"></i>Calendar</div></div>
            <div class="saas-card-body"><?php uiCalendarWidget(); ?></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-money-bill-wave me-2"></i>Payment Overview</div></div>
            <div class="saas-card-body">
                <?php if (empty($stats['payment_stats'])): uiEmptyState('fa-wallet', 'No payments yet', 'Payment data will appear here.', null, 'wallet'); ?>
                <?php else: foreach ($stats['payment_stats'] as $ps): ?>
                <div class="list-item">
                    <div><strong class="text-capitalize small"><?= e($ps['status']) ?></strong><br><small class="text-secondary"><?= (int)$ps['count'] ?> transactions</small></div>
                    <span class="fw-bold"><?= formatCurrency((float)($ps['total'] ?? 0)) ?></span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$chart7 = $stats['revenue_trends_7d'] ?? [];
$chart30 = $stats['revenue_trends_30d'] ?? [];
$chart6mo = $stats['revenue_trends_6mo'] ?? [];
$chart1y = PaymentModel::revenueTrendMonthly(12);
?>
<script>document.addEventListener('DOMContentLoaded',()=>{
    initRevenueTrendChart('revenueChart', {
        '7': {
            labels: <?= json_encode(array_map(fn ($r) => date('M j', strtotime($r['date'])), $chart7)) ?>,
            data: <?= json_encode(array_map(fn ($r) => (float) $r['revenue'], $chart7)) ?>
        },
        '30': {
            labels: <?= json_encode(array_map(fn ($r) => date('M j', strtotime($r['date'])), $chart30)) ?>,
            data: <?= json_encode(array_map(fn ($r) => (float) $r['revenue'], $chart30)) ?>
        },
        '180': {
            labels: <?= json_encode(array_column($chart6mo, 'label')) ?>,
            data: <?= json_encode(array_map(fn ($r) => (float) $r['revenue'], $chart6mo)) ?>
        },
        '365': {
            labels: <?= json_encode(array_column($chart1y, 'label')) ?>,
            data: <?= json_encode(array_map(fn ($r) => (float) $r['revenue'], $chart1y)) ?>
        }
    }, '30');
    initPieChart('binChart', <?= json_encode(array_column($stats['bin_allocation']??[],'status')) ?>, <?= json_encode(array_column($stats['bin_allocation']??[],'count')) ?>);
});</script>
