<?php uiPageHeader('Admin Dashboard', 'System-wide analytics and monitoring', '<a href="' . baseUrl('admin/reports') . '" class="btn-saas btn-saas-outline btn-saas-sm"><i class="fa-solid fa-file-export"></i> Export</a>'); ?>

<?php if (($contactStats['unread'] ?? 0) > 0): ?>
<div class="alert alert-warning d-flex align-items-center justify-content-between gap-3 animate-in mb-4" role="alert">
    <div><i class="fa-solid fa-envelope me-2"></i><strong><?= (int) $contactStats['unread'] ?></strong> unread contact message<?= $contactStats['unread'] === 1 ? '' : 's' ?> awaiting review.</div>
    <a href="<?= baseUrl('admin/messages') ?>" class="btn-saas btn-saas-sm btn-saas-primary">View Messages</a>
</div>
<?php endif; ?>

<?php uiQuickActions([
    ['icon' => 'fa-users', 'label' => 'Manage Users', 'route' => 'admin/users'],
    ['icon' => 'fa-envelope', 'label' => 'Contact Messages', 'route' => 'admin/messages', 'badge' => $contactStats['unread'] ?? 0],
    ['icon' => 'fa-truck', 'label' => 'Fleet', 'route' => 'admin/trucks'],
    ['icon' => 'fa-comments', 'label' => 'Complaints', 'route' => 'admin/complaints'],
    ['icon' => 'fa-sliders', 'label' => 'Settings', 'route' => 'admin/settings'],
]); ?>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Registered Residents', $stats['registered_residents'] ?? 0, 'fa-users', 'primary', ($stats['active_customers'] ?? 0) . ' active customers', 0);
    uiKpi('Collections Today', $collections['today'] ?? 0, 'fa-truck', 'success', ($collections['completed_today'] ?? 0) . ' completed today', 1);
    uiKpi('Collections Completed', $collections['completed_total'] ?? 0, 'fa-recycle', 'info', ($collections['scheduled_total'] ?? 0) . ' scheduled total', 2);
    uiKpi('Total Revenue', formatCurrency($stats['total_revenue']), 'fa-sack-dollar', 'purple', null, 3);
    ?>
</div>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Scheduled Collections', $collections['scheduled_total'] ?? 0, 'fa-calendar-check', 'info', ($collections['pending_total'] ?? 0) . ' upcoming', 0);
    uiKpi('Missed Collections', $collections['missed_total'] ?? 0, 'fa-calendar-xmark', 'warning', ($collections['missed_week'] ?? 0) . ' this week', 1);
    uiKpi('Outstanding', formatCurrency($stats['outstanding']), 'fa-exclamation-circle', 'danger', 'Customer balances', 2);
    uiKpi('Staff Accounts', $stats['active_users'] ?? 0, 'fa-user-shield', 'secondary', 'Active system users', 3);
    ?>
</div>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Total Messages', $contactStats['total'] ?? 0, 'fa-envelope', 'info', 'Contact form', 0);
    uiKpi('Unread Messages', $contactStats['unread'] ?? 0, 'fa-envelope-open', 'danger', 'Needs attention', 1);
    uiKpi('Messages Today', $contactStats['today'] ?? 0, 'fa-calendar-day', 'success', 'Received today', 2);
    ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="glass-card saas-card animate-in h-100 revenue-chart-wrap">
            <div class="saas-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="saas-card-title"><i class="fa-solid fa-chart-line me-2"></i>Revenue Trends</div>
                <div class="btn-group btn-group-sm revenue-period-toggle" role="group" aria-label="Revenue period">
                    <button type="button" class="btn btn-saas-outline active" data-revenue-period="7">7 Days</button>
                    <button type="button" class="btn btn-saas-outline" data-revenue-period="30">30 Days</button>
                    <button type="button" class="btn btn-saas-outline" data-revenue-period="180">6 Months</button>
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
        }
    }, '30');
    initPieChart('binChart', <?= json_encode(array_column($stats['bin_allocation']??[],'status')) ?>, <?= json_encode(array_column($stats['bin_allocation']??[],'count')) ?>);
});</script>
