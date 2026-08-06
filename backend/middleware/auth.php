<?php

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

function requireAuth(): array {
    startSession();
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
        jsonError('Unauthorized', 401);
    }
    
    $timeout = 3600;
    if (time() - $_SESSION['last_activity'] > $timeout) {
        session_destroy();
        jsonError('Session expired', 401);
    }
    
    $_SESSION['last_activity'] = time();
    
    return [
        'id' => $_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'email' => $_SESSION['email'],
        'name' => $_SESSION['name'] ?? '',
    ];
}

function requireRole(array $allowedRoles): array {
    $user = requireAuth();
    if (!in_array($user['role'], $allowedRoles)) {
        jsonError('Forbidden', 403);
    }
    return $user;
}

function setUserSession(array $user): void {
    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function destroySession(): void {
    startSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function getCsrfToken(): string {
    startSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrf(): void {
    startSession();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonError('Invalid CSRF token', 403);
    }
}

function getUserById(PDO $db, int $id): ?array {
    $stmt = $db->prepare('
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}
