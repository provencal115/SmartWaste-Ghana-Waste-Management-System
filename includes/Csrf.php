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
                jsonResponse(['success' => false, 'message' => 'Invalid request. Please refresh the page and try again.'], 403);
            }
            setFlash('error', 'Invalid request. Please try again.');
            redirect($_SERVER['HTTP_REFERER'] ?? 'home');
        }
    }

    private const SUBMISSION_TTL = 3600;

    public static function submissionField(string $scope): string
    {
        Auth::start();
        self::pruneSubmissionTokens($scope);
        $token = bin2hex(random_bytes(16));
        $_SESSION['submission_tokens'][$scope][$token] = time();

        return '<input type="hidden" name="submission_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /** @return 'valid'|'duplicate'|'invalid' */
    public static function validateSubmission(string $scope, string $token): string
    {
        Auth::start();
        self::pruneSubmissionTokens($scope);

        if ($token === '') {
            return 'invalid';
        }

        if (!empty($_SESSION['submission_consumed'][$scope][$token])) {
            return 'duplicate';
        }

        if (empty($_SESSION['submission_tokens'][$scope][$token])) {
            return 'invalid';
        }

        unset($_SESSION['submission_tokens'][$scope][$token]);
        $_SESSION['submission_consumed'][$scope][$token] = time();

        return 'valid';
    }

    private static function pruneSubmissionTokens(string $scope): void
    {
        $now = time();
        foreach (['submission_tokens', 'submission_consumed'] as $bucket) {
            if (empty($_SESSION[$bucket][$scope])) {
                continue;
            }
            foreach ($_SESSION[$bucket][$scope] as $t => $ts) {
                if ($now - (int) $ts > self::SUBMISSION_TTL) {
                    unset($_SESSION[$bucket][$scope][$t]);
                }
            }
        }
    }
}
