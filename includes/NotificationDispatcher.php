<?php
/**
 * Unified in-app + SMS notification dispatch.
 */

class NotificationDispatcher
{
    public static function notify(int $userId, string $title, string $message, string $type = 'general', bool $sendSms = true, array $smsVars = []): void
    {
        NotificationModel::send($userId, $title, $message, $type);

        if (!$sendSms || !SmsMessageModel::tableExists()) {
            return;
        }

        $smsType = match ($type) {
            'payment_confirmation' => 'payment_confirmation',
            'pickup_reminder'      => 'pickup_reminder',
            'collection', 'collection_complete' => 'collection_complete',
            default                => $type,
        };

        try {
            $config = require __DIR__ . '/../config/sms.php';
            $vars = array_merge(self::smsVars($userId, $type, $message, $title), $smsVars);

            if (isset($config['templates'][$smsType])) {
                SmsService::notifyUser($userId, $smsType, $vars);
            } else {
                $user = UserModel::findById($userId);
                if ($user && !empty($user['phone'])) {
                    SmsService::send($user['phone'], $title . ': ' . $message, $type, $userId);
                }
            }
        } catch (Throwable $e) {
            error_log('[SmartWaste] SMS dispatch failed: ' . $e->getMessage());
        }
    }

    /** @return array<string, string> */
    private static function smsVars(int $userId, string $type, string $message, string $title): array
    {
        $user = UserModel::findById($userId);
        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Customer';
        $vars = ['name' => $name];

        if ($type === 'payment_confirmation' && preg_match('/GHS?\s*([\d,.]+)/i', $message, $m)) {
            $vars['amount'] = 'GHS ' . $m[1];
        }
        if (preg_match('/Receipt:\s*(\S+)/i', $message, $m)) {
            $vars['receipt'] = $m[1];
        }
        if ($type === 'pickup_reminder' && preg_match('/(\d{4}-\d{2}-\d{2})/', $message, $m)) {
            $vars['date'] = $m[1];
            $vars['time'] = '';
        }
        if ($type === 'collection' || $type === 'collection_complete') {
            $vars['date'] = date('M j, Y');
        }

        return $vars;
    }

    public static function registrationWelcome(int $userId): void
    {
        $user = UserModel::findById($userId);
        if (!$user) {
            return;
        }

        $name = trim($user['first_name'] . ' ' . $user['last_name']);
        NotificationModel::send($userId, 'Welcome!', 'Your SmartWaste account is now active.', 'registration');

        try {
            SmsService::notifyUser($userId, 'registration_welcome', ['name' => $name]);
        } catch (Throwable $e) {
            error_log('[SmartWaste] Welcome SMS failed: ' . $e->getMessage());
        }
    }

    public static function passwordReset(string $email, string $token): void
    {
        $user = UserModel::findByEmail($email);
        if (!$user) {
            return;
        }

        $link = baseUrl('auth/reset') . '&token=' . urlencode($token);

        try {
            Mailer::sendPasswordResetEmail($user, $link);
        } catch (Throwable) {
            // Non-blocking
        }

        try {
            SmsService::notifyUser((int) $user['id'], 'password_reset', ['link' => $link, 'name' => $user['first_name']]);
        } catch (Throwable $e) {
            error_log('[SmartWaste] Password reset SMS failed: ' . $e->getMessage());
        }
    }
}
