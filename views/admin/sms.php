<?php
function smsStatusBadge(string $status): string
{
    return match ($status) {
        'sent'       => '<span class="status-pill status-success"><span class="status-dot"></span>Sent</span>',
        'simulated'  => '<span class="status-pill status-info"><span class="status-dot"></span>Simulated</span>',
        'failed'     => '<span class="status-pill status-danger"><span class="status-dot"></span>Failed</span>',
        default      => '<span class="status-pill status-warning"><span class="status-dot"></span>Pending</span>',
    };
}
?>
<?php uiPageHeader('SMS Notifications', 'Outgoing SMS history and delivery status'); ?>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Total SMS', $stats['total'], 'fa-comment-sms', 'primary', 'All messages', 0);
    uiKpi('Delivered', $stats['sent'], 'fa-check-double', 'success', 'Sent & simulated', 1);
    uiKpi('Failed', $stats['failed'], 'fa-triangle-exclamation', 'danger', 'Needs resend', 2);
    uiKpi('Sent Today', $stats['today'], 'fa-calendar-day', 'info', date('M j, Y'), 3);
    ?>
</div>

<div class="glass-card saas-card animate-in mb-4">
    <div class="saas-card-body">
        <form method="get" action="<?= baseUrl('admin/sms') ?>" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Search</label>
                <input type="search" name="q" class="form-control" placeholder="Phone, message, name…" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <?php foreach (['pending','sent','simulated','failed'] as $s): ?>
                    <option value="<?= e($s) ?>"<?= $filters['status'] === $s ? ' selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Type</label>
                <select name="type" class="form-select">
                    <option value="">All types</option>
                    <?php foreach (['registration_welcome','payment_confirmation','pickup_reminder','collection_complete','password_reset'] as $t): ?>
                    <option value="<?= e($t) ?>"<?= $filters['type'] === $t ? ' selected' : '' ?>><?= ucwords(str_replace('_', ' ', $t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn-saas btn-saas-primary btn-saas-sm flex-grow-1"><i class="fa-solid fa-filter"></i> Filter</button>
                <a href="<?= baseUrl('admin/sms') ?>" class="btn-saas btn-saas-ghost btn-saas-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($messages)): ?>
<?php uiEmptyState('fa-comment-sms', 'No SMS messages yet', 'SMS notifications will appear here when the system sends registration, payment, or collection alerts.', null, 'route'); ?>
<?php else: ?>
<?php uiTableWrapOpen('Search SMS in table…', true); ?>
<thead>
<tr>
<?= uiSortableTh('ID') ?>
<?= uiSortableTh('Recipient') ?>
<?= uiSortableTh('Phone') ?>
<?= uiSortableTh('Type') ?>
<th>Message</th>
<?= uiSortableTh('Provider') ?>
<?= uiSortableTh('Status') ?>
<?= uiSortableTh('Date') ?>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($messages as $m): ?>
<tr>
<td class="text-secondary">#<?= (int) $m['id'] ?></td>
<td>
    <?php if (!empty($m['first_name'])): ?>
    <strong><?= e($m['first_name'] . ' ' . ($m['last_name'] ?? '')) ?></strong>
    <?php if (!empty($m['email'])): ?><br><small class="text-secondary"><?= e($m['email']) ?></small><?php endif; ?>
    <?php else: ?>
    <span class="text-secondary">Guest</span>
    <?php endif; ?>
</td>
<td><?= e($m['phone']) ?></td>
<td><span class="badge bg-light text-dark border"><?= e(ucwords(str_replace('_', ' ', $m['message_type']))) ?></span></td>
<td class="small text-secondary" style="max-width:240px"><?= e(mb_strimwidth($m['message'], 0, 100, '…')) ?></td>
<td class="small"><?= e(ucfirst($m['provider'])) ?></td>
<td><?= smsStatusBadge($m['status']) ?></td>
<td class="small text-secondary"><?= formatDateTime($m['created_at']) ?></td>
<td>
    <?php if (in_array($m['status'], ['failed', 'simulated'], true)): ?>
    <form method="post" action="<?= baseUrl('admin/sms') ?>" class="d-inline sms-resend-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="resend">
        <input type="hidden" name="sms_id" value="<?= (int) $m['id'] ?>">
        <button type="submit" class="btn-saas btn-saas-ghost btn-saas-sm" title="Resend SMS"><i class="fa-solid fa-rotate-right"></i></button>
    </form>
    <?php else: ?>
    <span class="text-secondary small">—</span>
    <?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
<?php uiTableWrapClose(); ?>
<?php endif; ?>

<script>
document.querySelectorAll('.sms-resend-form').forEach(form => {
    form.addEventListener('submit', e => {
        e.preventDefault();
        confirmDelete('Resend this SMS message?', () => form.submit());
    });
});
</script>
