<?php
/**
 * Optional external AI provider — API key stays server-side only.
 * Falls back to keyword engine when disabled or unconfigured.
 */
class ChatbotAiProvider
{
    public static function isAvailable(): bool
    {
        $config = self::config();
        return !empty($config['enabled']) && !empty($config['api_key']);
    }

    /** @return array<string, mixed> */
    public static function config(): array
    {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        $defaults = [
            'enabled'  => false,
            'provider' => 'openai',
            'api_key'  => '',
            'model'    => 'gpt-4o-mini',
            'base_url' => 'https://api.openai.com/v1',
        ];

        $file = __DIR__ . '/../config/ai.php';
        if (is_file($file)) {
            $loaded = require $file;
            if (is_array($loaded)) {
                $defaults = array_merge($defaults, $loaded);
            }
        }

        $envKey = getenv('OPENAI_API_KEY') ?: getenv('SMARTWASTE_AI_API_KEY');
        if (is_string($envKey) && trim($envKey) !== '') {
            $defaults['api_key'] = trim($envKey);
        }

        $config = $defaults;
        return $config;
    }

    /**
     * @param list<array{role: string, content: string}> $messages
     */
    public static function complete(array $messages, string $systemPrompt): ?string
    {
        if (!self::isAvailable()) {
            return null;
        }

        $cfg = self::config();
        $payload = [
            'model'    => $cfg['model'],
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages
            ),
            'max_tokens' => 400,
            'temperature' => 0.3,
        ];

        $url = rtrim($cfg['base_url'], '/') . '/chat/completions';

        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $cfg['api_key'],
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);

        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $code < 200 || $code >= 300) {
            return null;
        }

        $data = json_decode($raw, true);
        $text = $data['choices'][0]['message']['content'] ?? null;

        return is_string($text) && trim($text) !== '' ? trim($text) : null;
    }
}
