<?php
$amountDue = (float) ($amountDue ?? 0);
$hasPending = !empty($pendingPayment);
?>
<?php uiPageHeader('Collect Cash Payment', 'Submit cash payment with evidence for verification'); ?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="glass-card saas-card animate-in">
            <div class="saas-card-header">
                <div class="saas-card-title"><i class="fa-solid fa-money-bill-wave me-2 text-success"></i>Cash Collection</div>
            </div>
            <div class="saas-card-body saas-form">
                <div class="mb-4">
                    <div class="list-item border rounded-3 px-3 py-2 mb-2"><span class="text-secondary small d-block">Customer</span><strong><?= e($customerName) ?></strong></div>
                    <div class="row g-2">
                        <div class="col-6"><div class="list-item border rounded-3 px-3 py-2"><span class="text-secondary small d-block">Collection</span><strong>#<?= str_pad((string)$schedule['id'], 5, '0', STR_PAD_LEFT) ?></strong><div class="small text-secondary"><?= formatDate($schedule['preferred_date']) ?></div></div></div>
                        <div class="col-6"><div class="list-item border rounded-3 px-3 py-2"><span class="text-secondary small d-block">Amount Due</span><strong class="text-success fs-5"><?= formatCurrency($amountDue) ?></strong></div></div>
                    </div>
                </div>

                <?php if ($hasPending): ?>
                <div class="alert alert-warning">
                    A cash payment for this collection is already <strong>Pending Verification</strong>.
                    Reference: <code><?= e($pendingPayment['receipt_number']) ?></code>
                </div>
                <?php else: ?>
                <form method="post" action="<?= baseUrl('collector/cash-payment') ?>" enctype="multipart/form-data" id="cashPaymentForm">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="schedule_id" value="<?= (int) $schedule['id'] ?>">
                    <input type="hidden" name="amount_due" value="<?= e((string) $amountDue) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Method</label>
                        <input type="text" class="form-control" value="Cash" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="amount_received" class="form-label fw-semibold">Amount Received (GHS)</label>
                        <input type="number" step="0.01" min="0" name="amount_received" id="amount_received" class="form-control form-control-lg" required value="<?= e((string) $amountDue) ?>">
                        <div id="amountError" class="text-danger small mt-1 d-none">Amount received is less than the amount due.</div>
                    </div>

                    <div id="changeDueBox" class="cash-change-alert mb-3 d-none">
                        Change Due: <span id="changeDueValue">GH&#8373; 0.00</span>
                    </div>

                    <div class="mb-3">
                        <label for="evidence" class="form-label fw-semibold">Payment Evidence</label>
                        <input type="file" name="evidence" id="evidence" class="form-control" accept="image/jpeg,image/png,image/webp" capture="environment" required>
                        <div class="form-text">Take a photo or upload JPG, PNG, WEBP · Max 3 MB</div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="confirmCash" required>
                        <label class="form-check-label" for="confirmCash">I confirm the cash amount and evidence are correct.</label>
                    </div>

                    <button type="submit" class="btn-saas btn-saas-primary w-100 justify-content-center" id="submitCashBtn">
                        <i class="fa-solid fa-check me-2"></i>Submit Cash Payment
                    </button>
                </form>
                <?php endif; ?>

                <a href="<?= baseUrl('collector/dashboard') ?>" class="btn-saas btn-saas-ghost w-100 mt-3 justify-content-center"><i class="fa-solid fa-arrow-left me-2"></i>Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const due = <?= json_encode($amountDue) ?>;
    const received = document.getElementById('amount_received');
    const err = document.getElementById('amountError');
    const changeBox = document.getElementById('changeDueBox');
    const changeVal = document.getElementById('changeDueValue');
    const form = document.getElementById('cashPaymentForm');
    if (!received || !form) return;

    function updateAmounts() {
        const val = parseFloat(received.value) || 0;
        if (val + 0.001 < due) {
            err.classList.remove('d-none');
            changeBox.classList.add('d-none');
        } else {
            err.classList.add('d-none');
            if (val > due + 0.001) {
                changeBox.classList.remove('d-none');
                changeVal.textContent = 'GH\u20B5 ' + (val - due).toFixed(2);
            } else {
                changeBox.classList.add('d-none');
            }
        }
    }
    received.addEventListener('input', updateAmounts);
    form.addEventListener('submit', (e) => {
        updateAmounts();
        if (!err.classList.contains('d-none')) {
            e.preventDefault();
        }
    });
});
</script>
