<?php uiPageHeader('Payment Management', 'Verify and track all transactions'); ?>
<?php uiTableWrapOpen('Search payments...', true, 'exportTableCsv(null,\'payments\')'); ?>
<thead><tr>
<?= uiSortableTh('Receipt') ?>
<?= uiSortableTh('Resident') ?>
<?= uiSortableTh('Amount') ?>
<?= uiSortableTh('Method') ?>
<?= uiSortableTh('Status') ?>
<th>Action</th>
</tr></thead>
<tbody><?php foreach ($payments as $p): ?><tr>
<td class="font-monospace small"><?= e($p['receipt_number']) ?></td>
<td><?= e($p['first_name'].' '.$p['last_name']) ?></td><td class="fw-medium"><?= formatCurrency($p['amount']) ?></td>
<td class="text-capitalize"><?= e(str_replace('_',' ',$p['payment_method'])) ?></td><td><?= statusBadge($p['status']) ?></td>
<td><?php if($p['status']==='pending' && $p['payment_method']==='cash'): ?><form method="POST" action="<?= baseUrl('finance/verify') ?>" class="d-inline"><?= Csrf::field() ?><input type="hidden" name="payment_id" value="<?= $p['id'] ?>"><button class="btn-saas btn-saas-primary btn-saas-sm"><i class="fa-solid fa-check"></i> Verify</button></form><?php else: ?><span class="text-muted">—</span><?php endif; ?></td></tr><?php endforeach; ?></tbody>
<?php uiTableWrapClose(); ?>
