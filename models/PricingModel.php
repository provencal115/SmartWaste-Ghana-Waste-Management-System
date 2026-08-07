<?php
require_once __DIR__ . '/../includes/Model.php';

class PricingModel extends Model
{
    public static function getPrice(int $planId, string $binSize, ?int $zoneId = null): ?float
    {
        $sql = 'SELECT price FROM pricing_policies WHERE payment_plan_id = ? AND bin_size = ? AND is_active = 1';
        $params = [$planId, $binSize];
        if ($zoneId) {
            $sql .= ' AND (zone_id = ? OR zone_id IS NULL) ORDER BY zone_id DESC LIMIT 1';
            $params[] = $zoneId;
        } else {
            $sql .= ' AND zone_id IS NULL LIMIT 1';
        }
        $row = self::fetchOne($sql, $params);
        return $row ? (float)$row['price'] : null;
    }

    public static function plans(): array
    {
        return self::fetchAll('SELECT * FROM payment_plans WHERE is_active = 1');
    }

    public static function allPolicies(): array
    {
        return self::fetchAll(
            'SELECT p.*, pp.name AS plan_name, pp.frequency, cz.name AS zone_name
             FROM pricing_policies p
             JOIN payment_plans pp ON p.payment_plan_id = pp.id
             LEFT JOIN collection_zones cz ON p.zone_id = cz.id
             WHERE p.is_active = 1'
        );
    }

    public static function updatePrice(int $id, float $price): void
    {
        self::query('UPDATE pricing_policies SET price = ? WHERE id = ?', [$price, $id]);
    }
}

class ZoneModel extends Model
{
    public static function all(): array
    {
        return self::fetchAll('SELECT * FROM collection_zones WHERE is_active = 1 ORDER BY name');
    }

    public static function allWithStats(): array
    {
        return self::fetchAll(
            "SELECT cz.*,
                    (SELECT COUNT(*) FROM residents r WHERE r.zone_id = cz.id) AS resident_count,
                    (SELECT COUNT(*) FROM collection_schedules cs
                     JOIN residents r ON cs.resident_id = r.id
                     WHERE r.zone_id = cz.id AND cs.status IN ('scheduled','in_progress')) AS scheduled_pickups
             FROM collection_zones cz
             ORDER BY cz.is_active DESC, cz.name"
        );
    }

    public static function find(int $id): ?array
    {
        return self::fetchOne('SELECT * FROM collection_zones WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        self::query(
            'INSERT INTO collection_zones (name, description, region, is_active) VALUES (?, ?, ?, ?)',
            [$data['name'], $data['description'] ?? '', $data['region'] ?? 'Ghana', $data['is_active'] ?? 1]
        );
        return (int) self::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        self::query(
            'UPDATE collection_zones SET name = ?, description = ?, region = ?, is_active = ? WHERE id = ?',
            [$data['name'], $data['description'] ?? '', $data['region'] ?? 'Ghana', $data['is_active'] ?? 1, $id]
        );
    }

    public static function delete(int $id): void
    {
        self::query('UPDATE collection_zones SET is_active = 0 WHERE id = ?', [$id]);
    }
}

class SettingModel extends Model
{
    public static function all(): array
    {
        return self::fetchAll('SELECT * FROM smart_settings ORDER BY setting_key');
    }

    public static function get(string $key): ?array
    {
        $row = self::fetchOne('SELECT setting_value FROM smart_settings WHERE setting_key = ?', [$key]);
        if (!$row || empty($row['setting_value'])) {
            return null;
        }
        $decoded = json_decode((string)$row['setting_value'], true);
        return is_array($decoded) ? $decoded : null;
    }

    public static function update(string $key, array $value, ?int $userId = null): void
    {
        self::query(
            'UPDATE smart_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = ?',
            [json_encode($value), $userId, $key]
        );
    }

    public static function upsert(string $key, array $value, ?int $userId = null, ?string $description = null): void
    {
        $exists = self::fetchOne('SELECT id FROM smart_settings WHERE setting_key = ?', [$key]);
        if ($exists) {
            self::update($key, $value, $userId);
            return;
        }

        self::query(
            'INSERT INTO smart_settings (setting_key, setting_value, description, updated_by) VALUES (?, ?, ?, ?)',
            [$key, json_encode($value), $description, $userId]
        );
    }
}

class ActivityModel extends Model
{
    public static function log(?int $userId, string $action, string $module, ?array $details = null): void
    {
        self::query(
            'INSERT INTO system_logs (user_id, action, module, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $action, $module, $details ? json_encode($details) : null, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]
        );
    }

    public static function recent(int $limit = 100): array
    {
        return self::fetchAll(
            'SELECT sl.*, u.first_name, u.last_name FROM system_logs sl LEFT JOIN users u ON sl.user_id = u.id ORDER BY sl.created_at DESC LIMIT ?',
            [$limit]
        );
    }
}

class NotificationModel extends Model
{
    public static function send(int $userId, string $title, string $message, string $type = 'general', string $channel = 'in_app'): void
    {
        self::query(
            'INSERT INTO notifications (user_id, title, message, type, channel) VALUES (?, ?, ?, ?, ?)',
            [$userId, $title, $message, $type, $channel]
        );
    }

    public static function forUser(int $userId, int $limit = 50): array
    {
        return self::fetchAll('SELECT * FROM notifications WHERE user_id = ? ORDER BY sent_at DESC LIMIT ?', [$userId, $limit]);
    }

    public static function unreadCount(int $userId): int
    {
        $row = self::fetchOne('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0', [$userId]);
        return (int)($row['c'] ?? 0);
    }

    public static function markRead(int $userId, ?int $id = null): void
    {
        if ($id) {
            self::query('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', [$id, $userId]);
        } else {
            self::query('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$userId]);
        }
    }
}
