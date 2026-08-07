<?php
/**
 * Global helper functions
 */

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirect(string $route, array $params = []): void
{
    $url = baseUrl($route);
    if ($params) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
    }
    header("Location: {$url}");
    exit;
}

function baseUrl(string $path = ''): string
{
    return rtrim(appConfig()['url'], '/') . '/index.php?url=' . ltrim($path, '/');
}

function asset(string $path): string
{
    $url = rtrim(appConfig()['url'], '/') . '/assets/' . ltrim($path, '/');
    $file = dirname(__DIR__) . '/assets/' . ltrim($path, '/');
    if (is_file($file)) {
        return $url . '?v=' . filemtime($file);
    }
    return $url;
}

/** Base URL prefix for JavaScript fetch/AJAX calls (index.php?url=). */
function jsBaseUrl(): string
{
    return rtrim(appConfig()['url'], '/') . '/index.php?url=';
}

function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse(mixed $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function setFlash(string $type, string $message): void
{
    Auth::start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    Auth::start();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function formatCurrency(float $amount): string
{
    return 'GH₵ ' . number_format($amount, 2);
}

function formatDate(?string $date): string
{
    if (!$date) return '—';
    return date('M j, Y', strtotime($date));
}

function formatDateTime(?string $date): string
{
    if (!$date) return '—';
    return date('M j, Y g:i A', strtotime($date));
}

function validatePassword(string $password): array
{
    $errors = [];
    if (strlen($password) < 8) $errors[] = 'At least 8 characters';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'One uppercase letter';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'One lowercase letter';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'One number';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'One special character';
    return $errors;
}

function generateToken(int $bytes = 16): string
{
    return bin2hex(random_bytes($bytes));
}

function generateReceiptNumber(): string
{
    return 'RCP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function generateBinCode(string $size, string $color): string
{
    return 'BIN-' . strtoupper(substr($size, 0, 1)) . '-' . strtoupper(substr($color, 0, 2)) . '-' . strtoupper(substr(uniqid(), -4));
}

function logActivity(?int $userId, string $action, string $module, ?array $details = null): void
{
    ActivityModel::log($userId, $action, $module, $details);
}

function getPrice(int $planId, string $binSize, ?int $zoneId = null): ?float
{
    return PricingModel::getPrice($planId, $binSize, $zoneId);
}

function truckStatusLabel(string $status): string
{
    return match ($status) {
        'active' => 'Available',
        'on_route' => 'On Route',
        'maintenance' => 'Maintenance',
        'breakdown' => 'Breakdown',
        'retired' => 'Retired',
        default => ucwords(str_replace('_', ' ', $status)),
    };
}

function truckStatusBadge(string $status): string
{
    $map = [
        'active' => 'success',
        'on_route' => 'primary',
        'maintenance' => 'warning',
        'breakdown' => 'danger',
        'retired' => 'secondary',
    ];
    $class = $map[$status] ?? 'secondary';

    return '<span class="status-pill status-' . $class . '"><span class="status-dot"></span>' . e(truckStatusLabel($status)) . '</span>';
}

function statusBadge(string $status): string
{
    $map = [
        'completed' => 'success', 'active' => 'success', 'available' => 'success', 'resolved' => 'success',
        'replied' => 'success', 'new' => 'danger', 'on_route' => 'primary', 'optimised' => 'success',
        'pending' => 'warning', 'scheduled' => 'info', 'in_progress' => 'primary', 'delayed' => 'warning',
        'read' => 'info',
        'failed' => 'danger', 'missed' => 'danger', 'overdue' => 'danger', 'damaged' => 'danger',
        'open' => 'info', 'cancelled' => 'secondary', 'maintenance' => 'warning',
    ];
    $class = $map[$status] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return '<span class="status-pill status-' . $class . '"><span class="status-dot"></span>' . e($label) . '</span>';
}

function clientIp(): ?string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if ($ip === null || $ip === '') {
        return null;
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
}

function binCapacity(string $size): int
{
    return match ($size) {
        'small' => 120,
        'medium' => 240,
        'large' => 360,
        default => 120,
    };
}

function binColors(): array
{
    return ['green' => '#22c55e', 'blue' => '#3b82f6', 'black' => '#1e293b', 'yellow' => '#eab308', 'red' => '#ef4444'];
}

/** Resolve a bin colour name to its hex value. */
function binColorHex(?string $color): string
{
    $colors = binColors();
    $key = strtolower(trim((string)$color));
    return $colors[$key] ?? $colors['green'];
}

/** Effective bin colour from a resident row (assigned dustbin or registration choice). */
function residentBinColor(array $resident): string
{
    return $resident['bin_color'] ?? $resident['selected_bin_color'] ?? 'green';
}

/** Effective bin size from a resident row. */
function residentBinSize(array $resident): ?string
{
    return $resident['bin_size'] ?? $resident['selected_bin_size'] ?? null;
}

function navActive(string $route): string
{
    $current = trim($_GET['url'] ?? 'home', '/');
    return str_starts_with($current, $route) ? 'active' : '';
}
