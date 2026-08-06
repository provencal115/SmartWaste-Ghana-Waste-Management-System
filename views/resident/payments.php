<?php uiPageHeader('Payments', 'Manage your service payments and receipts'); ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="glass-card saas-card animate-in">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-credit-card me-2"></i>Make Payment</div></div>
            <div class="saas-card-body saas-form">
                <form method="POST" action="<?= baseUrl('resident/payments') ?>">
                    <?= Csrf::field() ?>
                    <div class="mb-3"><label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="mobile_money"><i class="fa-solid fa-mobile"></i> Mobile Money</option>
                            <option value="bank_card">Bank Card</option>
                            <option value="cash">Cash</option>
                        </select></div>
                    <div class="mb-4"><label class="form-label">Amount (GHS)</label>
                        <input type="number" step="0.01" name="amount" value="<?= e($resident['service_fee']) ?>" class="form-control form-control-lg" required></div>
                    <button class="btn-saas btn-saas-primary w-100 justify-content-center"><i class="fa-solid fa-lock"></i> Pay Securely</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <?php uiGlassCardOpen('Payment History', null, 'fa-receipt'); ?>
        <?php if (empty($payments)): uiEmptyState('fa-wallet', 'No payments yet', 'Your payment history will appear here.', null, 'wallet'); ?>
        <?php else: ?>
        <div class="table-responsive saas-table-wrapper"><table class="saas-table">
            <thead><tr><th>Receipt</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($payments as $p): ?><tr>
                <td><a href="<?= baseUrl('api/receipt') ?>&no=<?= urlencode($p['receipt_number']) ?>" target="_blank" class="fw-medium"><i class="fa-solid fa-file-pdf me-1"></i><?= e($p['receipt_number']) ?></a></td>
                <td><?= formatCurrency($p['amount']) ?></td><td class="text-capitalize"><?= e(str_replace('_', ' ', $p['payment_method'])) ?></td>
                <td><?= statusBadge($p['status']) ?></td></tr><?php endforeach; ?></tbody>
        </table></div>
        <?php endif; ?>
        <?php uiGlassCardClose(); ?>
    </div>
</div>
