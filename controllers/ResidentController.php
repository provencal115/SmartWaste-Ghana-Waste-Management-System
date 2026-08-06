<?php
require_once __DIR__ . '/../includes/Controller.php';

class ResidentController extends Controller
{
    public function dashboard(): void
    {
        $user = $this->requireRole(['resident']);
        $resident = ResidentModel::getByUserId($user['id']);
        $upcoming = CollectionModel::upcoming($resident['id']);
        $history = CollectionModel::history($resident['id']);
        $notifications = NotificationModel::forUser($user['id'], 10);
        $payments = PaymentModel::forResident($resident['id']);
        $this->view('resident/dashboard', compact('user', 'resident', 'upcoming', 'history', 'notifications', 'payments'));
    }

    public function schedule(): void
    {
        $this->requireRole(['resident']);
        $this->view('resident/schedule');
    }

    public function schedulePost(): void
    {
        $user = $this->requireRole(['resident']);
        $this->validateCsrf();
        $resident = ResidentModel::getByUserId($user['id']);
        CollectionModel::schedule([
            'resident_id' => $resident['id'],
            'schedule_type' => $_POST['schedule_type'] ?? 'one_time',
            'preferred_date' => $_POST['preferred_date'],
            'preferred_time' => $_POST['preferred_time'] ?? null,
            'recurrence_pattern' => $_POST['recurrence_pattern'] ?? null,
            'collection_notes' => trim($_POST['collection_notes'] ?? ''),
        ]);
        NotificationDispatcher::notify(
            $user['id'],
            'Pickup Scheduled',
            'Your pickup has been scheduled for ' . $_POST['preferred_date'],
            'pickup_reminder',
            true,
            [
                'date' => $_POST['preferred_date'],
                'time' => !empty($_POST['preferred_time']) ? ' at ' . $_POST['preferred_time'] : '',
            ]
        );
        setFlash('success', 'Pickup scheduled successfully.');
        redirect('resident/schedule');
    }

    public function payments(): void
    {
        $user = $this->requireRole(['resident']);
        $resident = ResidentModel::getByUserId($user['id']);
        $payments = PaymentModel::forResident($resident['id']);
        $this->view('resident/payments', compact('resident', 'payments'));
    }

    public function paymentsPost(): void
    {
        $user = $this->requireRole(['resident']);
        $this->validateCsrf();
        $resident = ResidentModel::getByUserId($user['id']);
        $amount = (float)($_POST['amount'] ?? $resident['service_fee']);
        $method = $_POST['payment_method'] ?? 'mobile_money';
        $status = $method === 'cash' ? 'pending' : 'completed';
        $receipt = generateReceiptNumber();

        PaymentModel::create([
            'resident_id' => $resident['id'],
            'amount' => $amount,
            'payment_method' => $method,
            'status' => $status,
            'transaction_ref' => 'TXN-' . strtoupper(uniqid()),
            'receipt_number' => $receipt,
            'paid_at' => $status === 'completed' ? date('Y-m-d H:i:s') : null,
        ]);

        if ($status === 'completed') {
            ResidentModel::reduceBalance($resident['id'], $amount);
        }
        NotificationDispatcher::notify(
            $user['id'],
            'Payment ' . ucfirst($status),
            formatCurrency($amount) . ' via ' . $method . '. Receipt: ' . $receipt,
            'payment_confirmation',
            $status === 'completed',
            ['amount' => formatCurrency($amount), 'receipt' => $receipt]
        );
        setFlash('success', 'Payment processed. Receipt: ' . $receipt);
        redirect('resident/payments');
    }

    public function feedback(): void
    {
        $user = $this->requireRole(['resident']);
        $resident = ResidentModel::getByUserId($user['id']);
        $complaints = ComplaintModel::forResident($resident['id']);
        $this->view('resident/feedback', compact('complaints'));
    }

    public function feedbackPost(): void
    {
        $user = $this->requireRole(['resident']);
        $this->validateCsrf();
        $resident = ResidentModel::getByUserId($user['id']);
        ComplaintModel::create([
            'resident_id' => $resident['id'],
            'subject' => trim($_POST['subject']),
            'description' => trim($_POST['description']),
            'category' => $_POST['category'] ?? 'other',
            'rating' => (int)($_POST['rating'] ?? 5),
        ]);
        setFlash('success', 'Feedback submitted successfully.');
        redirect('resident/feedback');
    }

    public function notifications(): void
    {
        $user = $this->requireRole(['resident']);
        $notifications = NotificationModel::forUser($user['id']);
        $unread = NotificationModel::unreadCount($user['id']);
        $this->view('resident/notifications', compact('notifications', 'unread'));
    }

    public function markNotificationsRead(): void
    {
        $user = $this->requireRole(['resident']);
        $this->validateCsrf();
        NotificationModel::markRead($user['id'], !empty($_POST['id']) ? (int)$_POST['id'] : null);
        redirect('resident/notifications');
    }
}
