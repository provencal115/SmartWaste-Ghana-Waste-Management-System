<?php
require_once __DIR__ . '/../includes/Model.php';

class ResidentModel extends Model
{
    public static function create(array $data): int
    {
        self::query(
            'INSERT INTO residents (user_id, zone_id, address, city, selected_bin_size, selected_bin_color, payment_plan_id, service_fee, owns_existing_bin, registration_confirmed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)',
            [$data['user_id'], $data['zone_id'] ?? null, $data['address'], $data['city'] ?? 'Accra', $data['bin_size'], $data['bin_color'] ?? 'green', $data['payment_plan_id'], $data['service_fee'], $data['owns_existing_bin'] ?? 0]
        );
        return (int) self::lastInsertId();
    }

    public static function confirm(int $userId): void
    {
        self::query('UPDATE residents SET registration_confirmed = 1 WHERE user_id = ?', [$userId]);
    }

    public static function getByUserId(int $userId): ?array
    {
        return self::fetchOne(
            'SELECT r.*, pp.name AS payment_plan_name, pp.frequency,
                    d.bin_code,
                    COALESCE(d.color, r.selected_bin_color) AS bin_color,
                    COALESCE(d.size, r.selected_bin_size) AS bin_size,
                    d.capacity_liters, cz.name AS zone_name
             FROM residents r
             LEFT JOIN payment_plans pp ON r.payment_plan_id = pp.id
             LEFT JOIN bin_assignments ba ON ba.resident_id = r.id AND ba.is_active = 1
             LEFT JOIN dustbins d ON ba.dustbin_id = d.id
             LEFT JOIN collection_zones cz ON r.zone_id = cz.id
             WHERE r.user_id = ?',
            [$userId]
        );
    }

    public static function assignBin(int $userId): void
    {
        $resident = self::getByUserId($userId);
        if (!$resident || !empty($resident['owns_existing_bin'])) {
            return;
        }

        $bin = self::fetchOne(
            "SELECT id FROM dustbins WHERE size = ? AND color = ? AND status = 'available' LIMIT 1",
            [$resident['selected_bin_size'], $resident['selected_bin_color']]
        );
        if (!$bin) return;

        self::query('INSERT INTO bin_assignments (resident_id, dustbin_id) VALUES (?, ?)', [$resident['id'], $bin['id']]);
        self::query("UPDATE dustbins SET status = 'assigned' WHERE id = ?", [$bin['id']]);
    }

    public static function all(): array
    {
        return self::fetchAll(
            'SELECT r.*, u.first_name, u.last_name, u.email, u.phone, cz.name AS zone_name
             FROM residents r JOIN users u ON r.user_id = u.id LEFT JOIN collection_zones cz ON r.zone_id = cz.id'
        );
    }

    public static function reduceBalance(int $id, float $amount): void
    {
        self::query('UPDATE residents SET outstanding_balance = GREATEST(0, outstanding_balance - ?) WHERE id = ?', [$amount, $id]);
    }
}
