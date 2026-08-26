<?php uiPageHeader('Finance Dashboard', 'Revenue and payment analytics'); ?>

<?php uiQuickActions([
    ['icon' => 'fa-money-bill-wave', 'label' => 'Payments', 'route' => 'finance/payments'],
    ['icon' => 'fa-hand-holding-dollar', 'label' => 'Cash Verification', 'route' => 'finance/cash-payments'],
    ['icon' => 'fa-tags', 'label' => 'Pricing', 'route' => 'finance/pricing'],
    ['icon' => 'fa-file-export', 'label' => 'Reports', 'route' => 'finance/reports'],
]); ?>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Total Revenue', formatCurrency($stats['total_revenue'] ?? 0), 'fa-sack-dollar', 'purple', 'All completed payments', 0);
    uiKpi('Daily Revenue', formatCurrency($stats['daily']), 'fa-calendar-day', 'success', 'Today', 1);
    uiKpi('Weekly Revenue', formatCurrency($stats['weekly']), 'fa-calendar-week', 'primary', 'Last 7 days', 2);
    uiKpi('Monthly Revenue', formatCurrency($stats['monthly']), 'fa-calendar', 'info', 'Last 30 days', 3);
    ?>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="glass-card saas-card animate-in revenue-chart-wrap">
            <div class="saas-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="saas-card-title"><i class="fa-solid fa-chart-line me-2"></i>Revenue Trends</div>
                <div class="btn-group btn-group-sm revenue-period-toggle" role="group" aria-label="Revenue period">
                    <button type="button" class="btn btn-saas-outline active" data-revenue-period="7">7 Days</button>
                    <button type="button" class="btn btn-saas-outline" data-revenue-period="30">30 Days</button>
                    <button type="button" class="btn btn-saas-outline" data-revenue-period="180">6 Months</button>
                    <button type="button" class="btn btn-saas-outline" data-revenue-period="365">1 Year</button>
                </div>
            </div>
            <div class="saas-card-body"><canvas id="revenueTrendChart" height="100"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-chart-bar me-2"></i>Revenue by Method</div></div>
            <div class="saas-card-body"><canvas id="methodChart"></canvas></div>
        </div>
    </div>
</div>

<?php
$chart7 = $stats['trend_7d'] ?? [];
$chart30 = $stats['trend_30d'] ?? [];
$chart6mo = $stats['trend_6mo'] ?? [];
$chart1y = $stats['monthly_trend'] ?? PaymentModel::revenueTrendMonthly(12);
?>
<script>document.addEventListener('DOMContentLoaded',()=>{
    initRevenueTrendChart('revenueTrendChart', {
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
    initBarChart('methodChart', <?= json_encode(array_map(fn ($m) => ucwords(str_replace('_', ' ', $m['payment_method'])), $stats['by_method'] ?? [])) ?>, <?= json_encode(array_map(fn ($m) => (float) $m['total'], $stats['by_method'] ?? [])) ?>);
});</script>
