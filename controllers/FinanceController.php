<?php
require_once __DIR__ . '/../includes/Controller.php';

class FinanceController extends Controller
{
    public function dashboard(): void
    {
        $this->requireRole(['finance_manager']);
        $stats = PaymentModel::financeStats();
        $this->view('finance/dashboard', compact('stats'));
    }

    public function payments(): void
    {
        $this->requireRole(['finance_manager']);
        $payments = PaymentModel::all();
        $this->view('finance/payments', compact('payments'));
    }

    public function verifyCash(): void
    {
        $this->cashVerify();
    }

    public function cashPayments(): void
    {
        $this->requireRole(['finance_manager']);
        $stats = PaymentModel::cashStats();
        $payments = PaymentModel::pendingCashVerification(['status' => 'pending']);
        $this->view('finance/cash-payments', compact('stats', 'payments'));
    }

    public function cashVerify(): void
    {
        $user = $this->requireRole(['finance_manager', 'administrator']);
        $this->validateCsrf();

        $paymentId = (int) ($_POST['payment_id'] ?? 0);
        $action = $_POST['action'] ?? 'approve';
        $notes = trim($_POST['notes'] ?? '') ?: null;

        $payment = PaymentModel::findById($paymentId);
        if (!$payment || ($payment['payment_method'] ?? '') !== 'cash') {
            setFlash('error', 'Payment not found.');
            redirect('finance/cash-payments');
        }

        if (!PaymentModel::processVerification($paymentId, $action, (int) $user['id'], $notes)) {
            setFlash('error', 'Could not process verification.');
            redirect('finance/cash-payments');
        }

        $logAction = match ($action) {
            'approve' => 'cash_payment_approved',
            'reject'  => 'cash_payment_rejected',
            default   => 'cash_payment_review',
        };
        logActivity((int) $user['id'], $logAction, 'payments', ['payment_id' => $paymentId]);

        if ($action === 'approve') {
            $payment = PaymentModel::findDetailed($paymentId);
            if ($payment && !empty($payment['resident_id'])) {
                $residentUserId = (int) Model::fetchOne('SELECT user_id FROM residents WHERE id = ?', [$payment['resident_id']])['user_id'];
                NotificationDispatcher::notify(
                    $residentUserId,
                    'Payment Confirmed',
                    formatCurrencyPlain((float) ($payment['amount_received'] ?? $payment['amount'])) . ' cash payment verified. Receipt: ' . ($payment['receipt_number'] ?? ''),
                    'payment_confirmation',
                    true,
                    ['amount' => formatCurrencyPlain((float) ($payment['amount_received'] ?? $payment['amount'])), 'receipt' => $payment['receipt_number'] ?? '']
                );
            }
            setFlash('success', 'Cash payment approved and marked as paid.');
        } elseif ($action === 'reject') {
            setFlash('success', 'Cash payment rejected.');
        } else {
            setFlash('success', 'Cash payment marked for review.');
        }

        redirect('finance/cash-payments');
    }

    public function pricing(): void
    {
        $this->requireRole(['finance_manager']);
        $pricing = PricingModel::allPolicies();
        $this->view('finance/pricing', compact('pricing'));
    }

    public function pricingPost(): void
    {
        $this->requireRole(['finance_manager']);
        $this->validateCsrf();
        PricingModel::updatePrice((int)$_POST['id'], (float)$_POST['price']);
        setFlash('success', 'Pricing updated.');
        redirect('finance/pricing');
    }

    public function reports(): void
    {
        $this->requireRole(['finance_manager']);
        $this->view('finance/reports');
    }
}
