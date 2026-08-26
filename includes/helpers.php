<?php
/**
 * Global helper functions
 */

function initAppEncoding(): void
{
    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
        mb_http_output('UTF-8');
        mb_regex_encoding('UTF-8');
    }
    ini_set('default_charset', 'UTF-8');
}

function sendUtf8HtmlHeaders(): void
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
}

function currencySymbolPlain(): string
{
    return "\u{20B5}";
}

/** Plain-text currency (SMS, email body, chatbot, CLI). */
function formatCurrencyPlain(float $amount): string
{
    return 'GH' . currencySymbolPlain() . ' ' . number_format($amount, 2, '.', ',');
}

/** HTML-safe currency — displays as GH₵ in browsers and PDFs. */
function formatCurrencyHtml(float $amount): string
{
    return 'GH&#8373; ' . number_format($amount, 2, '.', ',');
}

/** ASCII placeholder for empty table cells (avoids em-dash mojibake in PDF). */
function emptyDisplay(): string
{
    return '-';
}

/**
 * Normalize HTML for Dompdf — replace symbols/fonts may not render reliably.
 */
function pdfSafeHtml(string $html): string
{
    $map = [
        "\xE2\x82\xB5" => '&#8373;',
        "\xE2\x80\x94" => '-',
        "\xE2\x80\x93" => '-',
        "\xC2\xB7"     => ' | ',
        "\xE2\x80\x99" => "'",
        "\xE2\x80\x9C" => '"',
        "\xE2\x80\x9D" => '"',
    ];

    return str_replace(array_keys($map), array_values($map), $html);
}

function dompdfInstance(): \Dompdf\Dompdf
{
    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    return new \Dompdf\Dompdf($options);
}

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
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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
    return formatCurrencyPlain($amount);
}

function formatDate(?string $date): string
{
    if (!$date) {
        return emptyDisplay();
    }
    return date('M j, Y', strtotime($date));
}

function formatDateTime(?string $date): string
{
    if (!$date) {
        return emptyDisplay();
    }
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

/**
 * Parse and validate a full name for profile updates.
 *
 * @return array{ok: bool, first_name?: string, last_name?: string, error?: string}
 */
function parseFullName(string $fullName): array
{
    $fullName = preg_replace('/\s+/u', ' ', trim($fullName)) ?? '';

    if ($fullName === '' || mb_strlen($fullName) < 2) {
        return ['ok' => false, 'error' => 'Please enter your full name (at least 2 characters).'];
    }
    if (mb_strlen($fullName) > 201) {
        return ['ok' => false, 'error' => 'Name is too long.'];
    }
    if (!preg_match("/^[\p{L}\p{M}'.\- ]+$/u", $fullName)) {
        return ['ok' => false, 'error' => 'Name contains invalid characters.'];
    }

    $parts = explode(' ', $fullName, 2);
    $firstName = $parts[0];
    $lastName = $parts[1] ?? '';

    if (mb_strlen($firstName) > 100 || mb_strlen($lastName) > 100) {
        return ['ok' => false, 'error' => 'Name is too long.'];
    }

    return [
        'ok' => true,
        'first_name' => $firstName,
        'last_name' => $lastName,
    ];
}

function formatUserFullName(array $user): string
{
    return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
}

function generateToken(int $bytes = 16): string
{
    return bin2hex(random_bytes($bytes));
}

function generateReceiptNumber(): string
{
    return 'RCP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/** Unique cash payment reference: SW-CASH-YYYYMMDD-#### */
function generateCashReceiptReference(): string
{
    $prefix = 'SW-CASH-' . date('Ymd') . '-';
    for ($i = 0; $i < 20; $i++) {
        $ref = $prefix . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $exists = Model::fetchOne('SELECT id FROM payments WHERE receipt_number = ? LIMIT 1', [$ref]);
        if (!$exists) {
            return $ref;
        }
    }
    return $prefix . strtoupper(substr(uniqid(), -4));
}

/** Unique invoice number: SW-INV-YYYY-###### */
function generateInvoiceNumber(): string
{
    $year = date('Y');
    $prefix = 'SW-INV-' . $year . '-';
    if (PaymentModel::hasCashColumns()) {
        $row = Model::fetchOne(
            "SELECT invoice_number FROM payments WHERE invoice_number LIKE ? ORDER BY invoice_number DESC LIMIT 1",
            [$prefix . '%']
        );
        $seq = 1;
        if ($row && preg_match('/-(\d+)$/', $row['invoice_number'], $m)) {
            $seq = (int) $m[1] + 1;
        }
        return $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
    return $prefix . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Validate and store cash payment evidence image.
 *
 * @return array{ok: bool, path?: string, error?: string}
 */
function savePaymentEvidenceUpload(int $paymentId, array $file): array
{
    $maxBytes = 3 * 1024 * 1024;

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Payment evidence photo is required.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed. Please try again.'];
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        return ['ok' => false, 'error' => 'Image must be 3 MB or smaller.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    if (!$mime || !isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Only JPG, PNG, and WEBP images are allowed.'];
    }

    $dir = rtrim(appConfig()['upload_path'], '/\\') . '/payment-evidence';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return ['ok' => false, 'error' => 'Could not create upload directory.'];
    }

    $filename = 'cash_' . $paymentId . '_' . time() . '.' . $allowed[$mime];
    $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return ['ok' => false, 'error' => 'Could not save image.'];
    }

    return ['ok' => true, 'path' => 'uploads/payment-evidence/' . $filename];
}

/** User-facing payment status label. */
function paymentDisplayStatus(array $payment): string
{
    $method = $payment['payment_method'] ?? '';
    $status = $payment['status'] ?? 'pending';
    $vStatus = $payment['verification_status'] ?? 'none';

    if ($method === 'cash') {
        if ($vStatus === 'rejected' || $status === 'failed') {
            return 'Rejected';
        }
        if ($vStatus === 'review') {
            return 'Under Review';
        }
        if ($status === 'completed' || $vStatus === 'approved') {
            return 'Paid';
        }
        if ($vStatus === 'pending' || $status === 'pending') {
            return 'Pending Verification';
        }
    }

    return match ($status) {
        'completed' => 'Paid',
        'pending'   => 'Pending',
        'failed'    => 'Failed',
        'overdue'   => 'Overdue',
        'refunded'  => 'Refunded',
        default     => ucwords(str_replace('_', ' ', $status)),
    };
}

function paymentStatusBadge(array $payment): string
{
    $label = paymentDisplayStatus($payment);
    $map = [
        'Paid'                  => 'success',
        'Pending Verification'  => 'warning',
        'Under Review'          => 'info',
        'Rejected'              => 'danger',
        'Pending'               => 'warning',
        'Failed'                => 'danger',
        'Overdue'               => 'danger',
        'Refunded'              => 'secondary',
    ];
    $class = $map[$label] ?? 'secondary';
    return '<span class="status-pill status-' . $class . '"><span class="status-dot"></span>' . e($label) . '</span>';
}

function paymentEvidenceUrl(?string $path): ?string
{
    if (!$path) {
        return null;
    }
    $full = dirname(__DIR__) . '/assets/' . ltrim($path, '/');
    return is_file($full) ? asset($path) : null;
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

/** True on the main dashboard page for the logged-in role (e.g. admin/dashboard). */
function isRoleDashboardPage(): bool
{
    $current = trim($_GET['url'] ?? '', '/');
    $routes = array_values(appConfig()['dashboard_routes'] ?? []);
    return in_array($current, $routes, true);
}

/** Time-based greeting using the authenticated user's first name. */
function dashboardGreeting(?array $user): string
{
    if (!$user) {
        return '';
    }
    $name = trim($user['first_name'] ?? '');
    if ($name === '') {
        $name = 'User';
    }
    $hour = (int) date('G');
    if ($hour >= 5 && $hour < 12) {
        $period = 'Good Morning';
    } elseif ($hour >= 12 && $hour < 17) {
        $period = 'Good Afternoon';
    } else {
        $period = 'Good Evening';
    }
    return "{$period}, {$name}";
}

function userAvatarInitials(?array $user): string
{
    return strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
}

function userAvatarStoragePath(?string $avatarUrl): ?string
{
    if (!$avatarUrl) {
        return null;
    }
    $path = dirname(__DIR__) . '/assets/' . ltrim($avatarUrl, '/');
    return is_file($path) ? $path : null;
}

function userAvatarAssetUrl(?array $user): ?string
{
    $url = trim($user['avatar_url'] ?? '');
    if ($url === '' || !userAvatarStoragePath($url)) {
        return null;
    }
    return asset($url);
}

/**
 * Validate and store a profile avatar upload for the given user.
 *
 * @return array{ok: bool, path?: string, error?: string}
 */
function saveUserAvatarUpload(int $userId, array $file): array
{
    $maxBytes = 2 * 1024 * 1024;

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'No file uploaded.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed. Please try again.'];
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        return ['ok' => false, 'error' => 'Image must be 2 MB or smaller.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!$mime || !isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Only JPG, PNG, and WEBP images are allowed.'];
    }

    $dir = rtrim(appConfig()['upload_path'], '/\\') . '/avatars';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return ['ok' => false, 'error' => 'Could not create upload directory.'];
    }

    $existing = UserModel::findById($userId);
    if ($existing && !empty($existing['avatar_url'])) {
        $old = userAvatarStoragePath($existing['avatar_url']);
        if ($old) {
            @unlink($old);
        }
    }

    $filename = 'avatar_' . $userId . '_' . time() . '.' . $allowed[$mime];
    $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return ['ok' => false, 'error' => 'Could not save image.'];
    }

    return ['ok' => true, 'path' => 'uploads/avatars/' . $filename];
}

initAppEncoding();
