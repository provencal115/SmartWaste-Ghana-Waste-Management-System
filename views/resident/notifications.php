<?php uiPageHeader('Notifications', $unread ? "$unread unread messages" : 'All caught up'); ?>
<div class="d-flex justify-content-end mb-3">
    <form method="POST" action="<?= baseUrl('resident/notifications/read') ?>"><?= Csrf::field() ?>
        <button class="btn-saas btn-saas-ghost btn-saas-sm"><i class="fa-solid fa-check-double"></i> Mark all read</button>
    </form>
</div>
<?php uiGlassCardOpen('Inbox', null, 'fa-inbox'); ?>
<?php if (empty($notifications)): uiEmptyState('fa-bell-slash', 'No notifications', 'We\'ll notify you about pickups, payments, and more.', null, 'bell'); ?>
<?php else: foreach ($notifications as $n): ?>
<div class="list-item <?= $n['is_read'] ? '' : 'unread' ?>">
    <div class="flex-grow-1"><div class="d-flex justify-content-between align-items-start gap-2"><strong><?= e($n['title']) ?></strong><small class="text-secondary text-nowrap"><?= formatDateTime($n['sent_at']) ?></small></div>
    <p class="mb-0 small text-secondary mt-1"><?= e($n['message']) ?></p>
    <span class="status-pill status-info mt-2"><span class="status-dot"></span><?= e(str_replace('_', ' ', $n['type'])) ?></span></div>
</div>
<?php endforeach; endif; ?>
<?php uiGlassCardClose(); ?>
