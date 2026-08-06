<?php uiPageHeader('Dashboard', 'Your waste management overview'); ?>

<?php uiQuickActions([
    ['icon' => 'fa-calendar-plus', 'label' => 'Schedule Pickup', 'route' => 'resident/schedule'],
    ['icon' => 'fa-credit-card', 'label' => 'Make Payment', 'route' => 'resident/payments'],
    ['icon' => 'fa-message', 'label' => 'Send Feedback', 'route' => 'resident/feedback'],
]); ?>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Assigned Bin', e($resident['bin_code'] ?? 'Pending'), 'fa-dumpster', 'primary', null, 0);
    uiKpi('Next Pickup', !empty($upcoming[0]) ? formatDate($upcoming[0]['preferred_date']) : 'None scheduled', 'fa-calendar-day', 'success', null, 1);
    uiKpi('Payment Status', $resident['outstanding_balance'] ? 'Outstanding' : 'Up to date', 'fa-wallet', $resident['outstanding_balance'] ? 'warning' : 'success', $resident['outstanding_balance'] ? formatCurrency($resident['outstanding_balance']) : null, 2);
    uiKpi('Notifications', count(array_filter($notifications, fn($n) => !$n['is_read'])), 'fa-bell', 'info', 'unread', 3);
    ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <?php uiGlassCardOpen('Your Bin', null, 'fa-trash-can'); ?>
        <?php if ($resident['bin_size']): ?>
        <div class="text-center py-2">
            <?php uiMiniBin(residentBinSize($resident), residentBinColor($resident), 'mx-auto mb-3'); ?>
            <div class="list-item border-0 px-0"><span class="text-secondary">Bin ID</span><strong class="font-monospace small"><?= e($resident['bin_code'] ?? 'Pending') ?></strong></div>
            <div class="list-item border-0 px-0"><span class="text-secondary">Size</span><strong><?= e(ucfirst(residentBinSize($resident))) ?></strong></div>
            <div class="list-item border-0 px-0"><span class="text-secondary">Colour</span><strong><?= e(ucfirst(residentBinColor($resident))) ?></strong></div>
            <div class="list-item border-0 px-0"><span class="text-secondary">Plan</span><strong><?= e($resident['payment_plan_name'] ?? '—') ?></strong></div>
        </div>
        <?php else: uiEmptyState('fa-clock', 'Bin Pending', 'Your bin will be assigned shortly after confirmation.', null, 'inventory'); endif; ?>
        <?php uiGlassCardClose(); ?>
    </div>
    <div class="col-lg-4">
        <?php uiGlassCardOpen('Upcoming Pickups', '<a href="' . baseUrl('resident/schedule') . '" class="btn-saas btn-saas-ghost btn-saas-sm"><i class="fa-solid fa-plus"></i></a>', 'fa-calendar'); ?>
        <?php if (empty($upcoming)): uiEmptyState('fa-calendar-xmark', 'No Pickups Scheduled', 'Schedule your next collection.', '<a href="' . baseUrl('resident/schedule') . '" class="btn-saas btn-saas-primary btn-saas-sm"><i class="fa-solid fa-plus"></i> Schedule Now</a>', 'calendar'); ?>
        <?php else: foreach ($upcoming as $p): ?>
        <div class="list-item"><div><strong><?= formatDate($p['preferred_date']) ?></strong><br><small class="text-secondary"><?= e($p['preferred_time'] ?? 'Any time') ?></small></div><?= statusBadge($p['status']) ?></div>
        <?php endforeach; endif; ?>
        <?php uiGlassCardClose(); ?>
    </div>
    <div class="col-lg-4">
        <?php uiGlassCardOpen('Calendar', null, 'fa-calendar-days'); ?>
        <?php uiCalendarWidget(); ?>
        <?php uiGlassCardClose(); ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <?php uiGlassCardOpen('Recent Notifications', '<a href="' . baseUrl('resident/notifications') . '" class="btn-saas btn-saas-ghost btn-saas-sm">View all</a>', 'fa-bell'); ?>
        <?php if (empty($notifications)): uiEmptyState('fa-bell-slash', 'No notifications', 'You\'re all caught up!', null, 'bell'); ?>
        <?php else: ?>
        <div class="activity-timeline">
            <?php foreach (array_slice($notifications, 0, 4) as $n): ?>
            <?php uiTimelineItem($n['title'], substr($n['message'], 0, 80), formatDateTime($n['created_at']), $n['is_read'] ? 'info' : 'success'); ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php uiGlassCardClose(); ?>
    </div>
    <div class="col-lg-6">
        <?php uiGlassCardOpen('Recent Payments', '<a href="' . baseUrl('resident/payments') . '" class="btn-saas btn-saas-ghost btn-saas-sm">View all</a>', 'fa-credit-card'); ?>
        <?php if (empty($payments)): uiEmptyState('fa-wallet', 'No payments yet', 'Your payment history will appear here.', null, 'wallet'); ?>
        <?php else: foreach (array_slice($payments, 0, 5) as $pay): ?>
        <div class="list-item"><div><strong class="small font-monospace"><?= e($pay['receipt_number']) ?></strong><br><small class="text-secondary"><?= formatDateTime($pay['paid_at'] ?? $pay['created_at']) ?></small></div><div class="text-end"><?= statusBadge($pay['status']) ?><br><strong class="small"><?= formatCurrency($pay['amount']) ?></strong></div></div>
        <?php endforeach; endif; ?>
        <?php uiGlassCardClose(); ?>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-12">
        <?php uiGlassCardOpen('Service History', null, 'fa-clock-rotate-left'); ?>
        <?php if (empty($history)): uiEmptyState('fa-truck', 'No history yet', 'Completed collections will appear here.', null, 'truck'); ?>
        <?php else: foreach (array_slice($history, 0, 5) as $h): ?>
        <div class="list-item"><span><?= formatDate($h['preferred_date']) ?></span><?= statusBadge($h['pickup_status']) ?></div>
        <?php endforeach; endif; ?>
        <?php uiGlassCardClose(); ?>
    </div>
</div>
