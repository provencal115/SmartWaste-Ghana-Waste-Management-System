<?php
/**
 * Professional SmartWaste invoice / receipt rendering (HTML + Dompdf PDF).
 */
class InvoiceService
{
    public static function findPaymentForInvoice(string $reference, ?array $user = null): ?array
    {
        $payment = PaymentModel::findDetailedByReference($reference);
        if (!$payment) {
            return null;
        }

        if ($user) {
            $role = $user['role_name'] ?? '';
            if ($role === 'resident') {
                $resident = ResidentModel::getByUserId((int) $user['id']);
                if (!$resident || (int) $payment['resident_id'] !== (int) $resident['id']) {
                    return null;
                }
            } elseif ($role === 'collector') {
                $collector = CollectorModel::ensureForUser((int) $user['id']);
                if (!$collector || (int) ($payment['collector_id'] ?? 0) !== (int) $collector['id']) {
                    return null;
                }
            } elseif (!in_array($role, ['administrator', 'finance_manager'], true)) {
                return null;
            }
        }

        return $payment;
    }

    public static function renderHtml(array $payment, bool $forPrint = false): string
    {
        $config = appConfig();
        $company = [
            'name'    => $config['name'] ?? 'SmartWaste Ghana',
            'email'   => 'support@smartwaste.gh',
            'phone'   => '+233 20 000 0000',
            'address' => 'Accra, Greater Accra, Ghana',
        ];

        try {
            $mail = require __DIR__ . '/../config/mail.php';
            if (!empty($mail['company'])) {
                $company = array_merge($company, array_filter($mail['company']));
            }
        } catch (Throwable) {
            // use defaults
        }

        $logoPath = dirname(__DIR__) . '/assets/images/logo.png';
        $logoSrc = is_file($logoPath) ? siteLogo() : '';

        $displayStatus = paymentDisplayStatus($payment);
        $amountDue = (float) ($payment['amount_due'] ?? $payment['amount']);
        $amountPaid = (float) ($payment['amount_received'] ?? $payment['amount']);
        $changeDue = max(0, $amountPaid - $amountDue);

        ob_start();
        require __DIR__ . '/../views/partials/invoice-document.php';
        return (string) ob_get_clean();
    }

    public static function streamPdf(array $payment, string $filename): void
    {
        $html = self::renderHtml($payment, true);
        $cssPath = dirname(__DIR__) . '/assets/css/invoice.css';
        if (is_file($cssPath)) {
            $html = '<style>' . file_get_contents($cssPath) . '</style>' . $html;
        }

        $html = pdfSafeHtml($html);

        if (!class_exists(\Dompdf\Dompdf::class)) {
            sendUtf8HtmlHeaders();
            echo $html;
            return;
        }

        $dompdf = dompdfInstance();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => true]);
    }

    public static function outputHtml(array $payment): void
    {
        sendUtf8HtmlHeaders();
        echo self::renderHtml($payment, true);
    }
}
