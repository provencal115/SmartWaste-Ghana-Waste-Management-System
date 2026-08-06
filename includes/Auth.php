<?php
/**
 * Authentication & Session Management
 */
class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_strict_mode', '1');

            $secure = isHttpsRequest();
            $basePath = appBasePath();
            $cookiePath = $basePath !== '' ? $basePath . '/' : '/';

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => $cookiePath,
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            if ($secure) {
                ini_set('session.cookie_secure', '1');
            }

            session_start();
        }
    }

    public static function login(array $user): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['last_activity'] = time();
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        self::start();
        if (!isset($_SESSION['user_id'], $_SESSION['last_activity'])) return false;

        $timeout = appConfig()['session_timeout'];
        if (time() - $_SESSION['last_activity'] > $timeout) {
            self::logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function user(): ?array
    {
        if (!self::check()) return null;
        return UserModel::findById($_SESSION['user_id']);
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if (!$user) {
            setFlash('error', 'Please login to continue.');
            redirect('auth/login');
        }
        return $user;
    }

    public static function requireRole(array $roles): array
    {
        $user = self::requireLogin();
        if (in_array($user['role_name'], $roles, true)) {
            return $user;
        }

        $config = appConfig();
        $roleLabel = $config['roles'][$user['role_name']] ?? ucwords(str_replace('_', ' ', $user['role_name']));
        setFlash('error', "Access denied. Your account ({$roleLabel}) cannot access that page.");

        $target = $config['dashboard_routes'][$user['role_name']] ?? 'home';
        $current = trim($_GET['url'] ?? '', '/');

        // Prevent redirect loops when already on the user's dashboard
        if ($current !== $target) {
            redirect($target);
        }

        redirect('home');
    }

    public static function guest(): void
    {
        if (self::check()) {
            $user = self::user();
            $config = appConfig();
            redirect($config['dashboard_routes'][$user['role_name']] ?? 'home');
        }
    }
}
