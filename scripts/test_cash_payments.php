<?php
require_once __DIR__ . '/../includes/AppConfig.php';
require_once __DIR__ . '/../includes/Model.php';
require_once __DIR__ . '/../includes/helpers.php';

foreach (glob(__DIR__ . '/../models/*.php') as $m) {
    require_once $m;
}

$failed = 0;
function ok(bool $c, string $m): void { global $failed; echo ($c ? 'PASS' : 'FAIL') . ": {$m}\n"; if (!$c) $failed++; }

ok(PaymentModel::hasCashColumns(), 'cash columns exist');
$ref = generateCashReceiptReference();
ok(str_starts_with($ref, 'SW-CASH-'), 'cash reference format');
$inv = generateInvoiceNumber();
ok(str_starts_with($inv, 'SW-INV-'), 'invoice number format');
ok(PaymentModel::fetchOne('SELECT id FROM payments WHERE invoice_number = ?', [$inv]) === null, 'invoice unique');

$stats = PaymentModel::cashStats();
ok(is_array($stats) && array_key_exists('pending', $stats), 'cashStats shape');

exit($failed > 0 ? 1 : 0);
