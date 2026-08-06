<?php
/**
 * CSRF Protection
 */
class Csrf
{
    public static function token(): string
    {
        Auth::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    public static function validate(): void
    {
        Auth::start();
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            if (isAjax()) {
                jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
            }
            setFlash('error', 'Invalid request. Please try again.');
            redirect($_SERVER['HTTP_REFERER'] ?? 'home');
        }
    }
}
