<?php
/**
 * Verify UTF-8 encoding helpers and invoice rendering.
 */
require_once __DIR__ . '/../includes/AppConfig.php';
require_once __DIR__ . '/../includes/Model.php';
require_once __DIR__ . '/../includes/helpers.php';
initAppEncoding();

$failures = 0;

function assertEncoding(string $label, bool $ok): void
{
    global $failures;
    if ($ok) {
        echo "OK: {$label}\n";
    } else {
        echo "FAIL: {$label}\n";
        $failures++;
    }
}

assertEncoding('formatCurrencyHtml contains cedi entity', str_contains(formatCurrencyHtml(140.0), 'GH&#8373; 140.00'));
assertEncoding('formatCurrency contains cedi symbol', str_contains(formatCurrency(140.0), currencySymbolPlain()));
assertEncoding('formatCurrencyPlain contains cedi symbol', str_contains(formatCurrencyPlain(140.0), currencySymbolPlain()));
assertEncoding('emptyDisplay is ASCII dash', emptyDisplay() === '-');
assertEncoding('formatDate empty uses dash', formatDate(null) === '-');
assertEncoding('pdfSafeHtml converts cedi', str_contains(pdfSafeHtml(formatCurrencyPlain(1)), '&#8373;'));
assertEncoding('pdfSafeHtml converts em dash', pdfSafeHtml("test\xE2\x80\x94value") === 'test-value');

$html = formatCurrencyHtml(140.0);
assertEncoding('HTML currency not mojibake', !str_contains($html, 'â'));

$row = Model::fetchOne('SELECT receipt_number FROM payments ORDER BY id DESC LIMIT 1');
if ($row && class_exists('InvoiceService')) {
    require_once __DIR__ . '/../includes/InvoiceService.php';
    require_once __DIR__ . '/../models/CollectionModel.php';
    require_once __DIR__ . '/../models/ResidentModel.php';
    require_once __DIR__ . '/../models/CollectorModel.php';

    $payment = InvoiceService::findPaymentForInvoice($row['receipt_number']);
    if ($payment) {
        $invoiceHtml = InvoiceService::renderHtml($payment, true);
        assertEncoding('Invoice HTML has cedi entity', str_contains($invoiceHtml, 'GH&#8373;'));
        assertEncoding('Invoice HTML no mojibake cedi', !str_contains($invoiceHtml, 'â'));
        assertEncoding('Invoice HTML no mojibake dash', !str_contains($invoiceHtml, 'â??'));
    } else {
        echo "SKIP: payment detail not found for latest receipt\n";
    }
} else {
    echo "SKIP: no payments or InvoiceService unavailable\n";
}

echo $failures === 0 ? "\nAll encoding checks passed.\n" : "\n{$failures} check(s) failed.\n";
exit($failures > 0 ? 1 : 0);
