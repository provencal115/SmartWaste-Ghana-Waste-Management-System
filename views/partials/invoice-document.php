<?php
/** @var array $payment */
/** @var array $company */
/** @var string $logoSrc */
/** @var string $displayStatus */
/** @var float $amountDue */
/** @var float $amountPaid */
/** @var float $changeDue */
/** @var bool $forPrint */
$invoiceNo = $payment['invoice_number'] ?? $payment['receipt_number'];
$customerName = trim(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? ''));
$customerId = 'RES-' . str_pad((string) ($payment['resident_pk'] ?? $payment['resident_id']), 5, '0', STR_PAD_LEFT);
$address = trim(($payment['address'] ?? '') . ($payment['city'] ? ', ' . $payment['city'] : ''));
$binSize = ucfirst($payment['selected_bin_size'] ?? 'medium');
$collectorName = trim(($payment['collector_first'] ?? '') . ' ' . ($payment['collector_last'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($invoiceNo) ?> - SmartWaste Invoice</title>
    <link href="<?= asset('css/invoice.css') ?>" rel="stylesheet">
</head>
<body class="invoice-body">
<div class="invoice-doc" id="invoiceDocument">
    <header class="invoice-header">
        <div class="invoice-brand">
            <?php if ($logoSrc): ?>
            <img src="<?= e($logoSrc) ?>" alt="SmartWaste" class="invoice-logo" width="64" height="64">
            <?php endif; ?>
            <div>
                <h1><?= e(strtoupper($company['name'])) ?></h1>
                <p class="invoice-company-meta">
                    <?= e($company['address'] ?? '') ?><br>
                    <?= e($company['email'] ?? '') ?> | <?= e($company['phone'] ?? '') ?>
                </p>
            </div>
        </div>
        <div class="invoice-meta-block">
            <div class="invoice-meta-label">Invoice / Receipt</div>
            <div class="invoice-meta-value font-monospace"><?= e($invoiceNo) ?></div>
            <div class="invoice-meta-sub"><?= formatDateTime($payment['paid_at'] ?? $payment['created_at']) ?></div>
            <div class="invoice-ref small">Ref: <?= e($payment['receipt_number']) ?></div>
        </div>
    </header>

    <section class="invoice-section">
        <h2>Customer</h2>
        <table class="invoice-table">
            <tr><th>Name</th><td><?= e($customerName) ?></td></tr>
            <tr><th>Customer ID</th><td><?= e($customerId) ?></td></tr>
            <tr><th>Address / GPS</th><td><?= e($address ?: emptyDisplay()) ?></td></tr>
        </table>
    </section>

    <section class="invoice-section">
        <h2>Service</h2>
        <table class="invoice-table">
            <tr><th>Service</th><td>Waste Collection Service</td></tr>
            <tr><th>Bin Size</th><td><?= e($binSize) ?></td></tr>
            <tr><th>Collection Date</th><td><?= !empty($payment['collection_date']) ? formatDate($payment['collection_date']) : emptyDisplay() ?></td></tr>
        </table>
    </section>

    <section class="invoice-section">
        <h2>Payment</h2>
        <table class="invoice-table invoice-table-highlight">
            <tr><th>Amount Due</th><td><?= formatCurrencyHtml($amountDue) ?></td></tr>
            <tr><th>Amount Paid</th><td><?= formatCurrencyHtml($amountPaid) ?></td></tr>
            <?php if ($changeDue > 0): ?>
            <tr><th>Change Due</th><td><?= formatCurrencyHtml($changeDue) ?></td></tr>
            <?php endif; ?>
            <tr><th>Payment Method</th><td><?= e(ucwords(str_replace('_', ' ', $payment['payment_method'] ?? ''))) ?></td></tr>
            <tr><th>Status</th><td><strong><?= e($displayStatus) ?></strong></td></tr>
        </table>
    </section>

    <?php if ($collectorName !== ''): ?>
    <section class="invoice-section">
        <h2>Collector</h2>
        <table class="invoice-table">
            <tr><th>Name</th><td><?= e($collectorName) ?></td></tr>
            <?php if (!empty($payment['collector_employee_id'])): ?>
            <tr><th>Employee ID</th><td><?= e($payment['collector_employee_id']) ?></td></tr>
            <?php endif; ?>
        </table>
    </section>
    <?php endif; ?>

    <?php if (!empty($payment['verified_at']) && !empty($payment['verifier_first'])): ?>
    <section class="invoice-section">
        <h2>Verification</h2>
        <table class="invoice-table">
            <tr><th>Verified By</th><td><?= e(trim($payment['verifier_first'] . ' ' . ($payment['verifier_last'] ?? ''))) ?></td></tr>
            <tr><th>Verified At</th><td><?= formatDateTime($payment['verified_at']) ?></td></tr>
        </table>
    </section>
    <?php endif; ?>

    <footer class="invoice-footer">
        <p>Thank you for choosing SmartWaste Ghana.</p>
        <p class="small text-muted">This document was generated electronically and is valid without a signature.</p>
    </footer>
</div>

<?php if (empty($forPrint)): ?>
<div class="invoice-actions no-print">
    <button type="button" class="btn-saas btn-saas-primary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print</button>
    <a href="<?= baseUrl('api/invoice') ?>&ref=<?= urlencode($invoiceNo) ?>&format=pdf" class="btn-saas btn-saas-outline"><i class="fa-solid fa-file-pdf me-1"></i> Download PDF</a>
</div>
<?php endif; ?>
</body>
</html>
