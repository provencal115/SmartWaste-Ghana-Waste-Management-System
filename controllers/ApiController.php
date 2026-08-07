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

    public function analyticsExport(): void
    {
        $user = Auth::requireLogin();
        $role = $user['role_name'] ?? '';
        if (!in_array($role, ['administrator', 'finance_manager'], true)) {
            http_response_code(403);
            echo 'Access denied';
            exit;
        }

        $filters = AnalyticsModel::parseFilters($_GET);
        $report = AnalyticsModel::fullReport($filters);
        $format = strtolower($_GET['format'] ?? 'csv');
        $filename = 'smartwaste-analytics-' . date('Y-m-d');

        if ($format === 'csv' || $format === 'xlsx') {
            $rows = AnalyticsModel::exportRows($report);
            $mime = $format === 'xlsx'
                ? 'application/vnd.ms-excel'
                : 'text/csv';
            $ext = $format === 'xlsx' ? 'xls' : 'csv';
            header('Content-Type: ' . $mime);
            header('Content-Disposition: attachment; filename="' . $filename . '.' . $ext . '"');
            if ($rows) {
                echo implode(',', array_keys($rows[0])) . "\n";
                foreach ($rows as $row) {
                    echo implode(',', array_map(
                        fn ($v) => '"' . str_replace('"', '""', (string)$v) . '"',
                        $row
                    )) . "\n";
                }
            }
            exit;
        }

        if ($format === 'pdf') {
            $op = $report['operational'];
            $perf = $report['performance'];
            $rev = $report['revenue'];
            $sat = $report['satisfaction'];

            $html = '<html><head><meta charset="utf-8"><style>
                body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#111;padding:24px}
                h1{font-size:18px;color:#16a34a}h2{font-size:13px;margin-top:18px;border-bottom:1px solid #ddd}
                table{width:100%;border-collapse:collapse;margin-top:8px}td,th{border:1px solid #ddd;padding:6px;text-align:left}
                th{background:#f0fdf4}
            </style></head><body>';
            $html .= '<h1>SmartWaste Operational Intelligence Report</h1>';
            $html .= '<p>Generated: ' . date('Y-m-d H:i') . '</p>';
            $html .= '<h2>Operational KPIs</h2><table>';
            foreach ([
                'Registered Residents' => $op['registered_residents'],
                'Active Customers' => $op['active_customers'],
                'Total Collections' => $op['total_collections'],
                'Completed Collections' => $op['completed_collections'],
                'Completion Rate' => $perf['completion_rate'] . '%',
                'On-Time Rate' => $perf['on_time_rate'] . '%',
                'Total Revenue' => formatCurrency($rev['total']),
                'Average Rating' => $sat['average'] . ' / 5',
            ] as $label => $val) {
                $html .= '<tr><th>' . htmlspecialchars($label) . '</th><td>' . htmlspecialchars((string)$val) . '</td></tr>';
            }
            $html .= '</table>';

            $html .= '<h2>Zone Performance</h2><table><tr><th>Zone</th><th>Completed</th><th>Rate</th></tr>';
            foreach ($report['zones'] as $z) {
                $html .= '<tr><td>' . htmlspecialchars($z['name']) . '</td><td>' . $z['completed'] . '</td><td>' . $z['completion_rate'] . '%</td></tr>';
            }
            $html .= '</table>';

            $html .= '<h2>Vehicle Performance</h2><table><tr><th>Vehicle</th><th>Completed</th><th>Missed</th><th>Status</th></tr>';
            foreach ($report['vehicles'] as $v) {
                $html .= '<tr><td>' . htmlspecialchars($v['plate_number']) . '</td><td>' . ($v['completed'] ?? 0) . '</td><td>' . ($v['missed'] ?? 0) . '</td><td>' . htmlspecialchars($v['maintenance_status']) . '</td></tr>';
            }
            $html .= '</table></body></html>';

            if (class_exists(\Dompdf\Dompdf::class)) {
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $dompdf->stream($filename . '.pdf', ['Attachment' => true]);
                exit;
            }

            header('Content-Type: text/html');
            echo $html;
            exit;
        }

        redirect('admin/analytics');
    }
}
