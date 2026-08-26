<?php uiPageHeader('Payment Management', 'Verify and track all transactions'); ?>
<div class="mb-3">
    <a href="<?= baseUrl('finance/cash-payments') ?>" class="btn-saas btn-saas-outline btn-saas-sm"><i class="fa-solid fa-hand-holding-dollar me-1"></i>Cash Verification</a>
</div>
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
<td class="text-capitalize"><?= e(str_replace('_',' ',$p['payment_method'])) ?></td><td><?= paymentStatusBadge($p) ?></td>
<td>
    <?php if(($p['payment_method'] ?? '') === 'cash' && ($p['verification_status'] ?? '') === 'pending'): ?>
    <a href="<?= baseUrl('finance/cash-payments') ?>" class="btn-saas btn-saas-primary btn-saas-sm"><i class="fa-solid fa-check"></i> Verify</a>
    <?php else: ?>
    <a href="<?= baseUrl('api/invoice') ?>&ref=<?= urlencode($p['invoice_number'] ?? $p['receipt_number']) ?>" target="_blank" class="btn-saas btn-saas-ghost btn-saas-sm"><i class="fa-solid fa-file-invoice"></i></a>
    <?php endif; ?>
</td></tr><?php endforeach; ?></tbody>
<?php uiTableWrapClose(); ?>
