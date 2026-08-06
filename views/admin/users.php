<?php
function welcomeEmailBadge(?string $status): string
{
    return match ($status) {
        'sent'    => '<span class="status-pill status-success" title="Welcome email sent"><span class="status-dot"></span>Sent &#10003;</span>',
        'failed'  => '<span class="status-pill status-danger" title="Welcome email failed"><span class="status-dot"></span>Failed &#10007;</span>',
        default   => '<span class="status-pill status-warning" title="Welcome email pending"><span class="status-dot"></span>Pending &#8987;</span>',
    };
}
?>
<?php uiPageHeader('User Management', count($users) . ' registered users'); ?>
<?php uiTableWrapOpen('Search users...', true, 'exportTableCsv(null,\'users\')'); ?>
<thead><tr>
<?= uiSortableTh('Name') ?>
<?= uiSortableTh('Email') ?>
<?= uiSortableTh('Role') ?>
<?= uiSortableTh('Status') ?>
<th>Welcome Email</th>
<?= uiSortableTh('Last Login') ?>
<th>Actions</th>
</tr></thead>
<tbody><?php foreach ($users as $u): ?><tr>
<td><div class="d-flex align-items-center gap-2"><div class="sidebar-avatar" style="width:32px;height:32px;font-size:0.7rem;border-radius:10px"><?= strtoupper(substr($u['first_name'],0,1).substr($u['last_name'],0,1)) ?></div><strong><?= e($u['first_name'].' '.$u['last_name']) ?></strong></div></td>
<td class="text-secondary"><?= e($u['email']) ?></td>
<td><span class="status-pill status-info"><span class="status-dot"></span><?= ucwords(str_replace('_',' ',$u['role_name'])) ?></span></td>
<td><?= statusBadge($u['is_active']?'active':'cancelled') ?></td>
<td><?= welcomeEmailBadge($u['welcome_email_status'] ?? 'pending') ?></td>
<td class="text-secondary small"><?= formatDateTime($u['last_login']) ?></td>
<td>
    <?php if ($u['role_name'] === 'resident' && $u['is_active']): ?>
    <form method="post" action="<?= baseUrl('admin/users') ?>" class="d-inline">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="resend_welcome">
        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
        <button type="submit" class="btn-saas btn-saas-ghost btn-saas-sm" title="Resend welcome email"><i class="fa-solid fa-envelope"></i></button>
    </form>
    <?php else: ?>
    <span class="text-secondary small">—</span>
    <?php endif; ?>
</td>
</tr><?php endforeach; ?></tbody>
<?php uiTableWrapClose(); ?>
