<?php uiPageHeader('My Collections — Payments', 'Cash payments submitted for your collections'); ?>

<?php uiGlassCardOpen('Payment Records', null, 'fa-receipt'); ?>
<?php if (empty($payments)): ?>
<?php uiEmptyState('fa-wallet', 'No payment records', 'Cash payments you submit will appear here.', null, 'wallet'); ?>
<?php else: ?>
<div class="table-responsive saas-table-wrapper">
<table class="saas-table">
    <thead>
        <tr>
            <th>Reference</th>
            <th>Customer</th>
            <th>Collection</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Status</th>
            <th>Date</th>
            <th>Evidence</th>
            <th>Invoice</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
            <td class="font-monospace small"><?= e($p['receipt_number']) ?></td>
            <td><?= e($p['first_name'] . ' ' . $p['last_name']) ?></td>
            <td><?= !empty($p['preferred_date']) ? formatDate($p['preferred_date']) : '—' ?></td>
            <td class="fw-medium"><?= formatCurrency($p['amount_received'] ?? $p['amount']) ?></td>
            <td class="text-capitalize"><?= e(str_replace('_', ' ', $p['payment_method'])) ?></td>
            <td><?= paymentStatusBadge($p) ?></td>
            <td class="small"><?= formatDateTime($p['created_at']) ?></td>
            <td>
                <?php if ($ev = paymentEvidenceUrl($p['evidence_url'] ?? null)): ?>
                <a href="<?= e($ev) ?>" target="_blank" rel="noopener"><img src="<?= e($ev) ?>" alt="Evidence" class="cash-evidence-thumb"></a>
                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td>
                <a href="<?= baseUrl('api/invoice') ?>&ref=<?= urlencode($p['invoice_number'] ?? $p['receipt_number']) ?>" target="_blank" class="btn-saas btn-saas-ghost btn-saas-sm"><i class="fa-solid fa-file-invoice"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php uiGlassCardClose(); ?>
