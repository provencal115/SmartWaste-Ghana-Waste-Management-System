<?php uiPageHeader('Inventory Dashboard', 'Warehouse and stock overview'); ?>

<?php uiQuickActions([
    ['icon' => 'fa-plus', 'label' => 'Add Bin', 'route' => 'inventory/bins'],
    ['icon' => 'fa-download', 'label' => 'Export Report', 'route' => 'inventory/reports'],
]); ?>

<div class="row g-4 mb-4">
    <?php uiKpi('Total Bins', $stats['total_bins'] ?? 0, 'fa-dumpster', 'primary', null, 0);
    uiKpi('Available', $stats['available'] ?? 0, 'fa-circle-check', 'success', null, 1);
    uiKpi('Assigned', $stats['assigned'] ?? 0, 'fa-user-check', 'info', null, 2);
    uiKpi('Maintenance', $stats['under_maintenance'] ?? 0, 'fa-wrench', 'warning', null, 3); ?>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <?php if ($alerts): ?>
        <div class="glass-card saas-card mb-4 animate-in" style="border-left:4px solid var(--color-warning)!important">
            <div class="saas-card-header"><div class="saas-card-title" style="color:var(--color-warning)"><i class="fa-solid fa-triangle-exclamation me-2"></i>Low Stock Alerts</div></div>
            <div class="saas-card-body pt-0"><?php foreach ($alerts as $a): ?>
            <div class="list-item"><span class="fw-medium"><?= ucfirst($a['bin_size']) ?> · <?= ucfirst($a['bin_color']) ?></span>
            <span class="status-pill status-warning"><span class="status-dot"></span><?= $a['current_stock'] ?> / <?= $a['minimum_quantity'] ?> min</span></div>
            <?php endforeach; ?></div>
        </div>
        <?php endif; ?>
        <div class="glass-card saas-card animate-in">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-chart-pie me-2"></i>Stock Distribution</div></div>
            <div class="saas-card-body">
                <?php
                $total = max(1, ($stats['total_bins'] ?? 1));
                uiProgressBar('Available', (($stats['available'] ?? 0) / $total) * 100, (string)($stats['available'] ?? 0));
                uiProgressBar('Assigned', (($stats['assigned'] ?? 0) / $total) * 100, (string)($stats['assigned'] ?? 0));
                uiProgressBar('Damaged', (($stats['damaged'] ?? 0) / $total) * 100, (string)($stats['damaged'] ?? 0));
                uiProgressBar('Maintenance', (($stats['under_maintenance'] ?? 0) / $total) * 100, (string)($stats['under_maintenance'] ?? 0));
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
</div>
