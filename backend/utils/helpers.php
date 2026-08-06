<?php

function jsonResponse(mixed $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $status = 400): void {
    jsonResponse(['success' => false, 'message' => $message], $status);
}

function getJsonInput(): array {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}

function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword(string $password): array {
    $errors = [];
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain an uppercase letter';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'Password must contain a lowercase letter';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain a number';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'Password must contain a special character';
    return $errors;
}

function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

function generateReceiptNumber(): string {
    return 'RCP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function generateBinCode(string $size, string $color): string {
    $sizeCode = strtoupper(substr($size, 0, 1));
    $colorCode = strtoupper(substr($color, 0, 2));
    return 'BIN-' . $sizeCode . '-' . $colorCode . '-' . strtoupper(substr(uniqid(), -4));
}

function logActivity(PDO $db, ?int $userId, string $action, string $module, ?array $details = null): void {
    $stmt = $db->prepare('INSERT INTO system_logs (user_id, action, module, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $userId,
        $action,
        $module,
        $details ? json_encode($details) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}

function sendNotification(PDO $db, int $userId, string $title, string $message, string $type = 'general', string $channel = 'in_app'): void {
    $stmt = $db->prepare('INSERT INTO notifications (user_id, title, message, type, channel) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $title, $message, $type, $channel]);
}

function getBinCapacity(string $size): int {
    return match($size) {
        'small' => 120,
        'medium' => 240,
        'large' => 360,
        default => 120,
    };
}

function getPricing(PDO $db, string $binSize, int $paymentPlanId, ?int $zoneId = null): ?float {
    $sql = 'SELECT price FROM pricing_policies WHERE bin_size = ? AND payment_plan_id = ? AND is_active = 1';
    $params = [$binSize, $paymentPlanId];
    if ($zoneId) {
        $sql .= ' AND (zone_id = ? OR zone_id IS NULL) ORDER BY zone_id DESC LIMIT 1';
        $params[] = $zoneId;
    } else {
        $sql .= ' AND zone_id IS NULL LIMIT 1';
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ? (float)$row['price'] : null;
}
