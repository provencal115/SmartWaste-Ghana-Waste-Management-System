<?php
/**
 * Reusable SMS delivery service — Hubtel, Arkesel, mNotify, SMSGH, or simulate.
 */

class SmsService
{
    /** @var array<string, mixed>|null */
    private static ?array $config = null;

    /** @return array<string, mixed> */
    private static function config(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $config = require __DIR__ . '/../config/sms.php';
        $local = __DIR__ . '/../config/sms.local.php';
        if (is_file($local)) {
            $config = array_replace_recursive($config, require $local);
        }

        self::$config = $config;
        return self::$config;
    }

    /**
     * Send an SMS and persist delivery record.
     *
     * @param array<string, mixed> $meta
     * @return array{success: bool, id: int, status: string}
     */
    public static function send(string $phone, string $message, string $type = 'general', ?int $userId = null, array $meta = []): array
    {
        $phone = self::normalizePhone($phone);
        if ($phone === '') {
            return ['success' => false, 'id' => 0, 'status' => 'failed'];
        }

        if (!SmsMessageModel::tableExists()) {
            error_log('[SmartWaste SMS] sms_messages table missing — message not stored.');
            return ['success' => true, 'id' => 0, 'status' => 'simulated'];
        }

        $config = self::config();
        $provider = strtolower(trim($config['provider'] ?? 'simulate'));

        $smsId = SmsMessageModel::create([
            'user_id'      => $userId,
            'phone'        => $phone,
            'message'      => $message,
            'message_type' => $type,
            'provider'     => $provider,
            'status'       => 'pending',
            'metadata'     => $meta,
        ]);

        if (empty($config['enabled']) || !self::isProviderConfigured($provider, $config)) {
            SmsMessageModel::updateStatus($smsId, 'simulated', 'SIM-' . $smsId);
            error_log('[SmartWaste SMS] Simulated send to ' . $phone . ': ' . mb_substr($message, 0, 120));
            logActivity($userId, 'sms_simulated', 'sms', ['sms_id' => $smsId, 'type' => $type, 'phone' => $phone]);
            return ['success' => true, 'id' => $smsId, 'status' => 'simulated'];
        }

        try {
            $result = match ($provider) {
                'hubtel'  => self::sendHubtel($phone, $message, $config),
                'arkesel' => self::sendArkesel($phone, $message, $config),
                'mnotify' => self::sendMnotify($phone, $message, $config),
                'smsgh'   => self::sendSmsgh($phone, $message, $config),
                default   => self::simulateSend($smsId, $phone, $message),
            };

            if ($result['success']) {
                SmsMessageModel::updateStatus($smsId, 'sent', $result['provider_id'] ?? null);
                logActivity($userId, 'sms_sent', 'sms', ['sms_id' => $smsId, 'type' => $type]);
                return ['success' => true, 'id' => $smsId, 'status' => 'sent'];
            }

            SmsMessageModel::updateStatus($smsId, 'failed', null, $result['error'] ?? 'Unknown error');
            logActivity($userId, 'sms_failed', 'sms', ['sms_id' => $smsId, 'error' => $result['error'] ?? '']);
            return ['success' => false, 'id' => $smsId, 'status' => 'failed'];
        } catch (Throwable $e) {
            SmsMessageModel::updateStatus($smsId, 'failed', null, $e->getMessage());
            error_log('[SmartWaste SMS] ' . $e->getMessage());
            return ['success' => false, 'id' => $smsId, 'status' => 'failed'];
        }
    }

    /** Resend a failed or simulated SMS. */
    public static function resend(int $smsId): bool
    {
        $row = SmsMessageModel::find($smsId);
        if (!$row) {
            return false;
        }

        $result = self::send(
            $row['phone'],
            $row['message'],
            $row['message_type'],
            $row['user_id'] ? (int) $row['user_id'] : null,
            is_array($row['metadata'] ?? null) ? $row['metadata'] : []
        );

        return $result['success'];
    }

    /** Build message from template key and placeholders. */
    public static function template(string $key, array $vars = []): string
    {
        $config = self::config();
        $text = $config['templates'][$key] ?? 'SmartWaste Ghana notification.';
        foreach ($vars as $k => $v) {
            $text = str_replace('{' . $k . '}', (string) $v, $text);
        }
        return preg_replace('/\{[a-z_]+\}/', '', $text) ?? $text;
    }

    /** Notify user by SMS using a template. */
    public static function notifyUser(?int $userId, string $type, array $vars = []): bool
    {
        if (!$userId) {
            return false;
        }
        $user = UserModel::findById($userId);
        if (!$user || empty($user['phone'])) {
            return false;
        }

        $vars['name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Customer';
        $message = self::template($type, $vars);
        $result = self::send($user['phone'], $message, $type, $userId, $vars);

        return $result['success'];
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', trim($phone)) ?? '';
        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '+233')) {
            return $phone;
        }
        if (str_starts_with($phone, '233')) {
            return '+' . $phone;
        }
        if (str_starts_with($phone, '0')) {
            return '+233' . substr($phone, 1);
        }

        return $phone;
    }

    /** @param array<string, mixed> $config */
    private static function isProviderConfigured(string $provider, array $config): bool
    {
        return match ($provider) {
            'hubtel'  => trim($config['hubtel']['client_id'] ?? '') !== '' && trim($config['hubtel']['client_secret'] ?? '') !== '',
            'arkesel' => trim($config['arkesel']['api_key'] ?? '') !== '',
            'mnotify' => trim($config['mnotify']['api_key'] ?? '') !== '',
            'smsgh'   => trim($config['smsgh']['api_key'] ?? '') !== '' && trim($config['smsgh']['api_secret'] ?? '') !== '',
            default   => false,
        };
    }

    /** @param array<string, mixed> $config */
    /** @return array{success: bool, provider_id?: string, error?: string} */
    private static function sendHubtel(string $phone, string $message, array $config): array
    {
        $cfg = $config['hubtel'];
        $payload = [
            'From' => $config['sender_id'] ?? 'SmartWaste',
            'To'   => ltrim($phone, '+'),
            'Content' => $message,
        ];

        return self::httpPost(
            $cfg['endpoint'],
            $payload,
            ['Authorization: Basic ' . base64_encode($cfg['client_id'] . ':' . $cfg['client_secret'])]
        );
    }

    /** @param array<string, mixed> $config */
    private static function sendArkesel(string $phone, string $message, array $config): array
    {
        $cfg = $config['arkesel'];
        $query = http_build_query([
            'action' => 'send-sms',
            'api_key' => $cfg['api_key'],
            'to'      => ltrim($phone, '+'),
            'from'    => $cfg['sender'] ?? $config['sender_id'],
            'sms'     => $message,
        ]);

        return self::httpGet($cfg['endpoint'] . '?' . $query);
    }

    /** @param array<string, mixed> $config */
    private static function sendMnotify(string $phone, string $message, array $config): array
    {
        $cfg = $config['mnotify'];
        $payload = [
            'recipient' => [ltrim($phone, '+')],
            'sender'    => $cfg['sender'] ?? $config['sender_id'],
            'message'   => $message,
            'is_schedule' => false,
        ];

        return self::httpPostJson($cfg['endpoint'] . '?key=' . urlencode($cfg['api_key']), $payload);
    }

    /** @param array<string, mixed> $config */
    private static function sendSmsgh(string $phone, string $message, array $config): array
    {
        $cfg = $config['smsgh'];
        $payload = [
            'From'    => $cfg['sender'] ?? $config['sender_id'],
            'To'      => ltrim($phone, '+'),
            'Content' => $message,
        ];

        return self::httpPostJson(
            $cfg['endpoint'],
            $payload,
            ['Authorization: Basic ' . base64_encode($cfg['api_key'] . ':' . $cfg['api_secret'])]
        );
    }

    /** @return array{success: bool, provider_id?: string} */
    private static function simulateSend(int $smsId, string $phone, string $message): array
    {
        error_log('[SmartWaste SMS] Simulated: ' . $phone . ' — ' . mb_substr($message, 0, 160));
        return ['success' => true, 'provider_id' => 'SIM-' . $smsId];
    }

    /** @param array<int, string> $headers */
    /** @return array{success: bool, provider_id?: string, error?: string} */
    private static function httpPost(string $url, array $payload, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/x-www-form-urlencoded'], $headers),
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'error' => $err];
        }
        if ($code >= 200 && $code < 300) {
            return ['success' => true, 'provider_id' => self::extractProviderId($body)];
        }

        return ['success' => false, 'error' => 'HTTP ' . $code . ': ' . mb_substr((string) $body, 0, 200)];
    }

    /** @return array{success: bool, provider_id?: string, error?: string} */
    private static function httpGet(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            return ['success' => true, 'provider_id' => self::extractProviderId($body)];
        }

        return ['success' => false, 'error' => 'HTTP ' . $code];
    }

    /** @param array<int, string> $headers */
    private static function httpPostJson(string $url, array $payload, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json'], $headers),
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            return ['success' => true, 'provider_id' => self::extractProviderId($body)];
        }

        return ['success' => false, 'error' => 'HTTP ' . $code];
    }

    private static function extractProviderId(?string $body): ?string
    {
        if (!$body) {
            return null;
        }
        $json = json_decode($body, true);
        if (!is_array($json)) {
            return null;
        }
        foreach (['messageId', 'message_id', 'id', 'batch_id', 'MessageId'] as $key) {
            if (!empty($json[$key])) {
                return (string) $json[$key];
            }
        }
        return null;
    }
}
