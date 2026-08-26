<?php uiPageHeader('Payments & Invoices', 'Manage your service payments and receipts'); ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="glass-card saas-card animate-in">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-credit-card me-2"></i>Make Payment</div></div>
            <div class="saas-card-body saas-form">
                <form method="POST" action="<?= baseUrl('resident/payments') ?>">
                    <?= Csrf::field() ?>
                    <div class="mb-3"><label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" id="residentPaymentMethod">
                            <option value="mobile_money">Mobile Money</option>
                            <option value="bank_card">Bank Card</option>
                            <option value="cash">Cash (pay collector or submit for verification)</option>
                        </select>
                        <div class="form-text" id="cashHelpText" style="display:none">Cash payments require finance verification before they are marked as paid.</div>
                    </div>
                    <div class="mb-4"><label class="form-label">Amount (GHS)</label>
                        <input type="number" step="0.01" name="amount" value="<?= e($resident['service_fee']) ?>" class="form-control form-control-lg" required></div>
                    <button class="btn-saas btn-saas-primary w-100 justify-content-center"><i class="fa-solid fa-lock"></i> Pay Securely</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <?php uiGlassCardOpen('Invoices & Payment History', null, 'fa-receipt'); ?>
        <?php if (empty($payments)): uiEmptyState('fa-wallet', 'No payments yet', 'Your payment history will appear here.', null, 'wallet'); ?>
        <?php else: ?>
        <div class="table-responsive saas-table-wrapper"><table class="saas-table">
            <thead><tr><th>Invoice</th><th>Date</th><th>Amount</th><th>Method</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody><?php foreach ($payments as $p):
                $inv = $p['invoice_number'] ?? $p['receipt_number'];
            ?><tr>
                <td class="font-monospace small"><?= e($inv) ?></td>
                <td class="small"><?= formatDateTime($p['paid_at'] ?? $p['created_at']) ?></td>
                <td><?= formatCurrency($p['amount']) ?></td>
                <td class="text-capitalize"><?= e(str_replace('_', ' ', $p['payment_method'])) ?></td>
                <td><?= paymentStatusBadge($p) ?></td>
                <td>
                    <a href="<?= baseUrl('api/invoice') ?>&ref=<?= urlencode($inv) ?>" target="_blank" class="btn-saas btn-saas-ghost btn-saas-sm" title="View Invoice"><i class="fa-solid fa-eye"></i></a>
                    <a href="<?= baseUrl('api/invoice') ?>&ref=<?= urlencode($inv) ?>&format=pdf" class="btn-saas btn-saas-ghost btn-saas-sm" title="Download PDF"><i class="fa-solid fa-file-pdf"></i></a>
                </td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php endif; ?>
        <?php uiGlassCardClose(); ?>
    </div>
</div>
<script>
document.getElementById('residentPaymentMethod')?.addEventListener('change', function () {
    document.getElementById('cashHelpText').style.display = this.value === 'cash' ? 'block' : 'none';
});
</script>
