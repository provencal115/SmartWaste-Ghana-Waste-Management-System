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
        $user = $this->requireRole(['finance_manager']);
        $this->validateCsrf();
        PaymentModel::verifyCash((int)$_POST['payment_id'], $user['id']);
        $payment = PaymentModel::fetchOne('SELECT p.*, r.user_id FROM payments p JOIN residents r ON r.id = p.resident_id WHERE p.id = ?', [(int)$_POST['payment_id']]);
        if ($payment && !empty($payment['user_id'])) {
            NotificationDispatcher::notify(
                (int) $payment['user_id'],
                'Payment Confirmed',
                formatCurrency((float) $payment['amount']) . ' cash payment verified. Receipt: ' . ($payment['receipt_number'] ?? ''),
                'payment_confirmation',
                true,
                ['amount' => formatCurrency((float) $payment['amount']), 'receipt' => $payment['receipt_number'] ?? '']
            );
        }
        setFlash('success', 'Cash payment verified.');
        redirect('finance/payments');
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
