<?php
require_once __DIR__ . '/../includes/Controller.php';

class CollectorController extends Controller
{
    private function collectorProfile(array $user): ?array
    {
        return CollectorModel::ensureForUser((int)$user['id']);
    }

    private function zoneId(?array $collector): ?int
    {
        return $collector && !empty($collector['zone_id']) ? (int)$collector['zone_id'] : null;
    }

    public function dashboard(): void
    {
        $user = $this->requireRole(['collector']);
        $collector = $this->collectorProfile($user);
        $filter = $_GET['filter'] ?? 'today';
        $allowed = ['all', 'today', 'upcoming', 'completed', 'missed', 'cancelled'];
        if (!in_array($filter, $allowed, true)) {
            $filter = 'today';
        }

        $zoneId = $this->zoneId($collector);
        $pickups = $collector ? CollectionModel::forCollector((int)$collector['id'], $filter, $zoneId) : [];
        $stats = $collector ? CollectionModel::collectorStats((int)$collector['id'], $zoneId) : [
            'today_total' => 0, 'today_completed' => 0, 'today_pending' => 0, 'today_in_progress' => 0,
        ];
        $todayRoute = $collector ? OptimizedRouteModel::todayForCollector((int)$collector['id']) : null;
        $todayStops = $collector ? CollectionModel::todayRouteForCollector((int)$collector['id'], $zoneId) : [];

        $this->view('collector/dashboard', compact('user', 'collector', 'pickups', 'stats', 'filter', 'todayRoute', 'todayStops'));
    }

    public function routes(): void
    {
        $user = $this->requireRole(['collector']);
        $collector = $this->collectorProfile($user);
        $zoneId = $this->zoneId($collector);
        $schedule = $collector ? CollectionModel::todayRouteForCollector((int)$collector['id'], $zoneId) : [];
        $optimizedRoute = $collector ? OptimizedRouteModel::todayForCollector((int)$collector['id']) : null;
        $this->view('collector/routes', compact('schedule', 'collector', 'optimizedRoute'));
    }

    public function scan(): void
    {
        $this->requireRole(['collector']);
        $this->view('collector/scan');
    }

    public function scanPost(): void
    {
        $this->requireRole(['collector']);
        $code = trim($_POST['code'] ?? '');
        $bin = DustbinModel::findByCode($code);
        if (isAjax()) {
            $this->json(['success' => (bool)$bin, 'data' => $bin]);
        }
        $this->view('collector/scan', compact('bin', 'code'));
    }

    public function updatePickup(): void
    {
        $user = $this->requireRole(['collector']);
        $this->validateCsrf();
        $collector = $this->collectorProfile($user);
        if (!$collector) {
            setFlash('error', 'Collector profile could not be loaded. Please contact an administrator.');
            redirect('collector/dashboard');
        }

        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        $status = $_POST['pickup_status'] ?? 'pending';
        $allowed = ['pending', 'in_progress', 'completed', 'missed', 'delayed'];
        if (!in_array($status, $allowed, true)) {
            setFlash('error', 'Invalid pickup status.');
            redirect('collector/dashboard');
        }

        $zoneId = $this->zoneId($collector);
        $pickup = CollectionModel::findForCollector($scheduleId, (int)$collector['id'], $zoneId);
        if (!$pickup) {
            setFlash('error', 'Pickup not found or not assigned to your zone.');
            redirect('collector/dashboard');
        }

        $notes = trim($_POST['collector_notes'] ?? '') ?: null;
        $proof = null;

        if (!empty($_FILES['proof_photo']['tmp_name']) && is_uploaded_file($_FILES['proof_photo']['tmp_name'])) {
            $dir = __DIR__ . '/../assets/uploads/proofs';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $ext = pathinfo($_FILES['proof_photo']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'proof_' . $scheduleId . '_' . time() . '.' . preg_replace('/[^a-z0-9]/i', '', $ext);
            if (move_uploaded_file($_FILES['proof_photo']['tmp_name'], $dir . '/' . $filename)) {
                $proof = 'uploads/proofs/' . $filename;
            }
        }

        CollectionModel::updatePickup($scheduleId, $status, (int)$collector['id'], $proof, $notes);

        if ($status === 'completed') {
            $residentUserId = (int) Model::fetchOne('SELECT user_id FROM residents WHERE id = ?', [$pickup['resident_id']])['user_id'];
            NotificationDispatcher::notify(
                $residentUserId,
                'Collection Complete',
                'Your waste was collected on ' . date('M j, Y') . '.',
                'collection_complete',
                true,
                ['date' => date('M j, Y')]
            );
        }

        setFlash('success', 'Pickup updated successfully.');
        $filter = $_POST['redirect_filter'] ?? 'today';
        redirect('collector/dashboard', ['filter' => $filter]);
    }

    public function payments(): void
    {
        $user = $this->requireRole(['collector']);
        $collector = $this->collectorProfile($user);
        $payments = $collector ? PaymentModel::forCollector((int) $collector['id']) : [];
        $this->view('collector/payments', compact('user', 'collector', 'payments'));
    }

    public function cashPayment(): void
    {
        $user = $this->requireRole(['collector']);
        $collector = $this->collectorProfile($user);
        if (!$collector) {
            setFlash('error', 'Collector profile could not be loaded.');
            redirect('collector/dashboard');
        }

        $scheduleId = (int) ($_GET['schedule_id'] ?? 0);
        $zoneId = $this->zoneId($collector);
        $schedule = CollectionModel::findForCollector($scheduleId, (int) $collector['id'], $zoneId);
        if (!$schedule) {
            setFlash('error', 'Collection not found or not assigned to you.');
            redirect('collector/dashboard');
        }

        $amountDue = (float) ($schedule['outstanding_balance'] ?? 0);
        if ($amountDue <= 0) {
            $residentRow = Model::fetchOne('SELECT service_fee, outstanding_balance FROM residents WHERE id = ?', [$schedule['resident_id']]);
            $amountDue = (float) ($residentRow['outstanding_balance'] ?? 0);
            if ($amountDue <= 0) {
                $amountDue = (float) ($residentRow['service_fee'] ?? 0);
            }
        }

        $customerName = trim(($schedule['first_name'] ?? '') . ' ' . ($schedule['last_name'] ?? ''));
        $pendingPayment = PaymentModel::hasPendingCashForSchedule($scheduleId)
            ? Model::fetchOne(
                "SELECT * FROM payments WHERE schedule_id = ? AND payment_method = 'cash'
                 AND verification_status IN ('pending','review') ORDER BY id DESC LIMIT 1",
                [$scheduleId]
            )
            : null;

        $this->view('collector/cash-payment', compact('schedule', 'amountDue', 'customerName', 'pendingPayment', 'collector'));
    }

    public function cashPaymentPost(): void
    {
        $user = $this->requireRole(['collector']);
        $this->validateCsrf();
        $collector = $this->collectorProfile($user);
        if (!$collector) {
            setFlash('error', 'Collector profile could not be loaded.');
            redirect('collector/dashboard');
        }

        $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
        $zoneId = $this->zoneId($collector);
        $schedule = CollectionModel::findForCollector($scheduleId, (int) $collector['id'], $zoneId);
        if (!$schedule) {
            setFlash('error', 'Collection not found or not assigned to you.');
            redirect('collector/dashboard');
        }

        if (PaymentModel::hasPendingCashForSchedule($scheduleId)) {
            setFlash('error', 'A cash payment for this collection is already pending verification.');
            redirect('collector/cash-payment', ['schedule_id' => $scheduleId]);
        }

        $amountDue = (float) ($_POST['amount_due'] ?? 0);
        $amountReceived = (float) ($_POST['amount_received'] ?? 0);

        if ($amountReceived + 0.001 < $amountDue) {
            setFlash('error', 'Amount received is less than the amount due.');
            redirect('collector/cash-payment', ['schedule_id' => $scheduleId]);
        }

        if (empty($_FILES['evidence']['tmp_name'])) {
            setFlash('error', 'Payment evidence photo is required.');
            redirect('collector/cash-payment', ['schedule_id' => $scheduleId]);
        }

        $receipt = generateCashReceiptReference();
        $invoiceNo = PaymentModel::hasCashColumns() ? generateInvoiceNumber() : null;

        $paymentId = PaymentModel::submitCollectorCash([
            'resident_id'          => (int) $schedule['resident_id'],
            'amount'               => $amountDue,
            'amount_due'           => $amountDue,
            'amount_received'      => $amountReceived,
            'payment_method'       => 'cash',
            'payment_plan_id'      => null,
            'status'               => 'pending',
            'verification_status'  => 'pending',
            'transaction_ref'      => 'CASH-' . strtoupper(substr(uniqid(), -8)),
            'receipt_number'       => $receipt,
            'invoice_number'       => $invoiceNo,
            'paid_at'              => null,
            'collector_id'         => (int) $collector['id'],
            'schedule_id'          => $scheduleId,
            'notes'                => $amountReceived > $amountDue
                ? 'Change due: ' . formatCurrencyPlain($amountReceived - $amountDue)
                : null,
        ]);

        $upload = savePaymentEvidenceUpload($paymentId, $_FILES['evidence']);
        if (!$upload['ok']) {
            Model::query('DELETE FROM payments WHERE id = ? AND status = ?', [$paymentId, 'pending']);
            setFlash('error', $upload['error'] ?? 'Invalid payment evidence.');
            redirect('collector/cash-payment', ['schedule_id' => $scheduleId]);
        }

        PaymentModel::updateEvidence($paymentId, $upload['path']);

        $residentUserId = (int) Model::fetchOne('SELECT user_id FROM residents WHERE id = ?', [$schedule['resident_id']])['user_id'];
        NotificationDispatcher::notify(
            $residentUserId,
            'Cash Payment Submitted',
            'Cash payment of ' . formatCurrencyPlain($amountDue) . ' submitted by collector — pending verification. Ref: ' . $receipt,
            'payment_confirmation',
            false,
            ['amount' => formatCurrencyPlain($amountDue), 'receipt' => $receipt]
        );

        logActivity((int) $user['id'], 'cash_payment_submitted', 'payments', [
            'payment_id' => $paymentId,
            'schedule_id' => $scheduleId,
            'receipt' => $receipt,
            'amount_received' => $amountReceived,
        ]);

        setFlash('success', 'Cash payment submitted — pending verification. Reference: ' . $receipt);
        redirect('collector/payments');
    }

    public function reports(): void
    {
        $this->requireRole(['collector']);
        $this->view('collector/reports');
    }

    public function reportsPost(): void
    {
        $user = $this->requireRole(['collector']);
        $this->validateCsrf();
        $collector = $this->collectorProfile($user);
        if (!$collector) {
            setFlash('error', 'Collector profile could not be loaded. Please contact an administrator.');
            redirect('collector/reports');
        }
        CollectorModel::submitReport([
            'collector_id' => $collector['id'],
            'report_type' => $_POST['report_type'],
            'description' => trim($_POST['description']),
        ]);
        setFlash('success', 'Report submitted.');
        redirect('collector/reports');
    }
}
