<?php
/**
 * Secure resident account lookups for the chatbot (own data only).
 */
class ChatbotAccountService
{
    /**
     * @return array<string, mixed>|null
     */
    public static function contextForUser(?array $user): ?array
    {
        if (!$user || ($user['role_name'] ?? '') !== 'resident') {
            return null;
        }

        $resident = ResidentModel::getByUserId((int)$user['id']);
        if (!$resident) {
            return null;
        }

        return [
            'user_id'     => (int)$user['id'],
            'resident_id' => (int)$resident['id'],
            'first_name'  => $user['first_name'] ?? '',
            'resident'    => $resident,
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array{response: string, category: string}|null
     */
    public static function tryAnswer(string $message, array $context): ?array
    {
        $text = strtolower(trim($message));
        $residentId = (int)$context['resident_id'];
        $resident = $context['resident'];

        if (self::matches($text, ['next collection', 'next pickup', 'when is my collection', 'when is my pickup', 'my collection date', 'next scheduled collection', 'when will my bin be collected'])) {
            return ['response' => self::nextCollection($residentId), 'category' => 'account_schedule'];
        }

        if (self::matches($text, ['what bin', 'my bin', 'bin do i have', 'assigned bin', 'which bin', 'my container'])) {
            return ['response' => self::binInfo($resident), 'category' => 'account_bin'];
        }

        if (self::matches($text, ['overdue', 'outstanding balance', 'balance due', 'payment overdue', 'is my payment overdue', 'do i owe', 'account balance'])) {
            return ['response' => self::paymentStatus($residentId, $resident), 'category' => 'account_payment'];
        }

        if (self::matches($text, ['how much do i pay', 'how much do i owe', 'my bill', 'my fee', 'service fee', 'what do i pay', 'my plan cost'])) {
            return ['response' => self::myPricing($resident), 'category' => 'account_pricing'];
        }

        if (self::matches($text, ['payment history', 'my payments', 'my receipts', 'last payment', 'recent payment'])) {
            return ['response' => self::paymentHistory($residentId), 'category' => 'account_payment'];
        }

        if (self::matches($text, ['my address', 'update address', 'change address', 'update my address', 'collection address'])) {
            return ['response' => self::addressInfo($resident), 'category' => 'account_profile'];
        }

        if (self::matches($text, ['missed collection', 'missed pickup', 'report missed', 'collector missed me', 'did not collect'])) {
            return ['response' => self::missedCollectionHelp(), 'category' => 'account_complaint'];
        }

        if (self::matches($text, ['new bin', 'get a bin', 'request bin', 'additional bin', 'replace bin', 'need a bin', 'get a new bin'])) {
            return ['response' => self::newBinHelp(), 'category' => 'account_bin'];
        }

        return null;
    }

    /** @param list<string> $phrases */
    private static function matches(string $text, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if ($text === $phrase || str_contains($text, $phrase)) {
                return true;
            }
        }
        return false;
    }

    private static function nextCollection(int $residentId): string
    {
        $row = Model::fetchOne(
            "SELECT preferred_date, preferred_time FROM collection_schedules
             WHERE resident_id = ? AND status IN ('scheduled', 'in_progress', 'rescheduled')
               AND preferred_date >= CURDATE()
             ORDER BY preferred_date ASC, preferred_time ASC LIMIT 1",
            [$residentId]
        );

        if (!$row) {
            return "You don't have an upcoming collection scheduled yet.\n\nBook one here: " . baseUrl('resident/schedule');
        }

        $date = formatDate($row['preferred_date']);
        $day = date('l', strtotime($row['preferred_date']));

        if (!empty($row['preferred_time'])) {
            $start = date('g:i A', strtotime($row['preferred_time']));
            $end = date('g:i A', strtotime($row['preferred_time']) + 3 * 3600);
            $timePart = "between {$start} and {$end}";
        } else {
            $timePart = 'during standard hours (8:00 AM – 4:00 PM)';
        }

        return "Your next scheduled collection is {$day}, {$date} {$timePart}.\n\nView schedule: " . baseUrl('resident/schedule');
    }

    /** @param array<string, mixed> $resident */
    private static function binInfo(array $resident): string
    {
        $size = $resident['bin_size'] ?? $resident['selected_bin_size'] ?? 'medium';
        $color = ucfirst((string)($resident['bin_color'] ?? $resident['selected_bin_color'] ?? 'green'));
        $liters = binCapacity($size);
        $code = $resident['bin_code'] ?? null;

        $msg = "You currently have a {$liters}L {$color} bin assigned to your account.";
        if ($code) {
            $msg .= "\n\nBin ID: {$code}";
        }
        return $msg . "\n\nDashboard: " . baseUrl('resident/dashboard');
    }

    /** @param array<string, mixed> $resident */
    private static function paymentStatus(int $residentId, array $resident): string
    {
        $overdue = Model::fetchAll(
            "SELECT amount, due_date FROM payments WHERE resident_id = ?
             AND status IN ('pending', 'failed') AND (due_date IS NULL OR due_date < CURDATE())
             ORDER BY due_date ASC",
            [$residentId]
        );

        if ($overdue) {
            $total = array_sum(array_map(fn ($p) => (float)$p['amount'], $overdue));
            return 'Your account currently has ' . count($overdue) . ' overdue payment(s) totalling '
                . formatCurrency($total) . ".\n\nPay now: " . baseUrl('resident/payments');
        }

        $balance = (float)($resident['outstanding_balance'] ?? 0);
        if ($balance > 0) {
            return 'Your outstanding balance is ' . formatCurrency($balance) . ".\n\nPay here: " . baseUrl('resident/payments');
        }

        return "Your account is up to date — no overdue payments.\n\nPayments: " . baseUrl('resident/payments');
    }

    /** @param array<string, mixed> $resident */
    private static function myPricing(array $resident): string
    {
        $plan = $resident['payment_plan_name'] ?? 'your plan';
        $fee = (float)($resident['service_fee'] ?? 0);
        $liters = binCapacity($resident['bin_size'] ?? $resident['selected_bin_size'] ?? 'medium');

        $msg = "Your service is on the {$plan} plan for a {$liters}L bin.";
        if ($fee > 0) {
            $msg .= ' Registered fee: ' . formatCurrency($fee) . '.';
        }
        $balance = (float)($resident['outstanding_balance'] ?? 0);
        if ($balance > 0) {
            $msg .= ' Outstanding: ' . formatCurrency($balance) . '.';
        }
        return $msg . "\n\nPayments: " . baseUrl('resident/payments');
    }

    private static function paymentHistory(int $residentId): string
    {
        $payments = array_slice(PaymentModel::forResident($residentId), 0, 3);
        if (!$payments) {
            return 'No payments on your account yet. ' . baseUrl('resident/payments');
        }

        $lines = ['Your recent payments:'];
        foreach ($payments as $p) {
            $lines[] = '• ' . formatCurrency((float)$p['amount']) . ' — ' . ucfirst((string)$p['status'])
                . ' (' . formatDate($p['paid_at'] ?? $p['created_at']) . ')';
        }
        $lines[] = "\nFull history: " . baseUrl('resident/payments');
        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $resident */
    private static function addressInfo(array $resident): string
    {
        $addr = trim(($resident['address'] ?? '') . ($resident['city'] ? ', ' . $resident['city'] : ''));
        $zone = $resident['zone_name'] ?? '';
        $msg = "Your registered address:\n{$addr}";
        if ($zone) {
            $msg .= "\nZone: {$zone}";
        }
        $msg .= "\n\nTo update your address, contact support: " . baseUrl('contact')
            . ' or ' . (companyInfo()['phone'] ?? '');
        return $msg;
    }

    private static function missedCollectionHelp(): string
    {
        return "Report a missed collection via Feedback: " . baseUrl('resident/feedback')
            . "\nOr Contact Us: " . baseUrl('contact');
    }

    private static function newBinHelp(): string
    {
        return "Request a bin via " . baseUrl('contact') . " or register at " . baseUrl('auth/register')
            . "\n\nSizes: 120L, 240L, 360L.";
    }
}
