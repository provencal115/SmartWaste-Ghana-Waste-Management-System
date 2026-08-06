<?php
require_once __DIR__ . '/../includes/Controller.php';

class ApiController extends Controller
{
    public function pricing(): void
    {
        $planId = (int)($_GET['plan_id'] ?? 0);
        $binSize = $_GET['bin_size'] ?? 'medium';
        $zoneId = !empty($_GET['zone_id']) ? (int)$_GET['zone_id'] : null;
        $price = getPrice($planId, $binSize, $zoneId);
        $this->json(['success' => true, 'price' => $price]);
    }

    public function export(): void
    {
        Auth::requireLogin();
        $type = $_GET['type'] ?? 'residents';
        $format = $_GET['format'] ?? 'csv';

        $data = match ($type) {
            'residents' => ResidentModel::all(),
            'payments' => PaymentModel::all(),
            'inventory' => DustbinModel::all(),
            'complaints' => ComplaintModel::all(),
            default => [],
        };

        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $type . '-report.csv"');
            if ($data) {
                echo implode(',', array_keys($data[0])) . "\n";
                foreach ($data as $row) {
                    echo implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string)$v) . '"', $row)) . "\n";
                }
            }
            exit;
        }
        redirect('admin/reports');
    }

    public function receipt(): void
    {
        $user = Auth::requireLogin();
        $receiptNo = $_GET['no'] ?? '';
        $payment = Model::fetchOne('SELECT p.*, u.first_name, u.last_name FROM payments p JOIN residents r ON p.resident_id = r.id JOIN users u ON r.user_id = u.id WHERE p.receipt_number = ?', [$receiptNo]);
        if (!$payment) {
            setFlash('error', 'Receipt not found.');
            redirect('home');
        }
        // Simple HTML receipt (Dompdf can be added via composer)
        header('Content-Type: text/html');
        echo '<html><body style="font-family:sans-serif;padding:40px">';
        echo '<h2>SmartWaste Payment Receipt</h2>';
        echo '<p><strong>Receipt No:</strong> ' . e($payment['receipt_number']) . '</p>';
        echo '<p><strong>Customer:</strong> ' . e($payment['first_name'] . ' ' . $payment['last_name']) . '</p>';
        echo '<p><strong>Amount:</strong> ' . formatCurrency($payment['amount']) . '</p>';
        echo '<p><strong>Method:</strong> ' . e($payment['payment_method']) . '</p>';
        echo '<p><strong>Date:</strong> ' . formatDateTime($payment['paid_at'] ?? $payment['created_at']) . '</p>';
        echo '<p><em>Thank you for your payment.</em></p>';
        echo '</body></html>';
        exit;
    }
}
