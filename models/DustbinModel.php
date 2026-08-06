<?php
require_once __DIR__ . '/../includes/Model.php';

class DustbinModel extends Model
{
    public static function all(?string $status = null, ?string $size = null): array
    {
        $sql = 'SELECT * FROM dustbins WHERE 1=1';
        $params = [];
        if ($status) { $sql .= ' AND status = ?'; $params[] = $status; }
        if ($size) { $sql .= ' AND size = ?'; $params[] = $size; }
        return self::fetchAll($sql . ' ORDER BY created_at DESC', $params);
    }

    public static function create(array $data): int
    {
        $code = generateBinCode($data['size'], $data['color']);
        self::query(
            'INSERT INTO dustbins (bin_code, qr_code, size, color, brand, capacity_liters, status, warehouse_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$code, $code, $data['size'], $data['color'], $data['brand'] ?? 'EcoBin', binCapacity($data['size']), 'available', $data['warehouse_location'] ?? 'Warehouse A']
        );
        return (int) self::lastInsertId();
    }

    public static function findByCode(string $code): ?array
    {
        return self::fetchOne(
            'SELECT d.*, ba.resident_id, u.first_name, u.last_name FROM dustbins d
             LEFT JOIN bin_assignments ba ON ba.dustbin_id = d.id AND ba.is_active = 1
             LEFT JOIN residents r ON ba.resident_id = r.id
             LEFT JOIN users u ON r.user_id = u.id
             WHERE d.bin_code = ? OR d.qr_code = ?',
            [$code, $code]
        );
    }

    public static function stats(): array
    {
        return self::fetchOne(
            "SELECT COUNT(*) AS total_bins,
                    SUM(CASE WHEN status='available' THEN 1 ELSE 0 END) AS available,
                    SUM(CASE WHEN status='assigned' THEN 1 ELSE 0 END) AS assigned,
                    SUM(CASE WHEN status='damaged' THEN 1 ELSE 0 END) AS damaged,
                    SUM(CASE WHEN status='maintenance' THEN 1 ELSE 0 END) AS under_maintenance
             FROM dustbins"
        ) ?? [];
    }

    public static function lowStockAlerts(): array
    {
        return self::fetchAll(
            "SELECT it.*, COALESCE(d.count, 0) AS current_stock
             FROM inventory_thresholds it
             LEFT JOIN (SELECT size, color, COUNT(*) AS count FROM dustbins WHERE status='available' GROUP BY size, color) d
             ON it.bin_size = d.size AND it.bin_color = d.color
             WHERE COALESCE(d.count, 0) < it.minimum_quantity"
        );
    }

    public static function assign(int $residentId, int $dustbinId, int $assignedBy): void
    {
        self::query('UPDATE bin_assignments SET is_active = 0, returned_at = NOW() WHERE resident_id = ? AND is_active = 1', [$residentId]);
        self::query('INSERT INTO bin_assignments (resident_id, dustbin_id, assigned_by) VALUES (?, ?, ?)', [$residentId, $dustbinId, $assignedBy]);
        self::query("UPDATE dustbins SET status = 'assigned' WHERE id = ?", [$dustbinId]);
    }
}

class CollectorModel extends Model
{
    public static function getByUserId(int $userId): ?array
    {
        return self::fetchOne('SELECT * FROM collectors WHERE user_id = ?', [$userId]);
    }

    /**
     * Ensure a collectors profile row exists for this user (auto-repair after failed DB seed).
     */
    public static function ensureForUser(int $userId): ?array
    {
        $existing = self::getByUserId($userId);
        if ($existing) {
            return $existing;
        }

        $count = (int)(self::fetchOne('SELECT COUNT(*) AS c FROM collectors')['c'] ?? 0);
        $employeeId = 'COL-' . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
        $zone = self::fetchOne('SELECT id FROM collection_zones ORDER BY id LIMIT 1');
        $zoneId = $zone['id'] ?? null;

        try {
            self::query(
                'INSERT INTO collectors (user_id, employee_id, zone_id) VALUES (?, ?, ?)',
                [$userId, $employeeId, $zoneId]
            );
        } catch (Throwable $e) {
            // Race or duplicate — fetch again
            return self::getByUserId($userId);
        }

        return self::getByUserId($userId);
    }

    public static function submitReport(array $data): int
    {
        self::query(
            'INSERT INTO collector_reports (collector_id, schedule_id, report_type, description, photo_url, gps_lat, gps_lng) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$data['collector_id'], $data['schedule_id'] ?? null, $data['report_type'], $data['description'], $data['photo_url'] ?? null, $data['gps_lat'] ?? null, $data['gps_lng'] ?? null]
        );
        return (int) self::lastInsertId();
    }

    /** All collectors with user details (for admin route assignment). */
    public static function allWithUsers(): array
    {
        return self::fetchAll(
            "SELECT c.*, u.first_name, u.last_name, u.email, cz.name AS zone_name
             FROM collectors c
             JOIN users u ON c.user_id = u.id
             LEFT JOIN collection_zones cz ON c.zone_id = cz.id
             ORDER BY u.first_name, u.last_name"
        );
    }
}

class TruckModel extends Model
{
    public static function all(): array
    {
        return self::fetchAll('SELECT t.*, cz.name AS zone_name FROM trucks t LEFT JOIN collection_zones cz ON t.zone_id = cz.id');
    }
}

class AdminModel extends Model
{
    public static function dashboardStats(): array
    {
        $ops = operationalStats();

        $revenue = PaymentModel::revenueAnalytics();

        return [
            'active_users' => self::fetchOne('SELECT COUNT(*) AS c FROM users WHERE is_active = 1')['c'] ?? 0,
            'registered_residents' => $ops['registered_residents'],
            'active_customers' => $ops['active_customers'],
            'collections_completed' => $ops['collections_completed'],
            'collections_scheduled' => $ops['collections_scheduled'],
            'collections_pending' => $ops['collections_pending'],
            'collections_missed' => $ops['collections_missed'],
            'active_collections' => self::fetchOne("SELECT COUNT(*) AS c FROM collection_schedules WHERE status IN ('scheduled','in_progress') AND preferred_date = CURDATE()")['c'] ?? 0,
            'total_revenue' => $revenue['total'],
            'outstanding' => (float)(self::fetchOne('SELECT COALESCE(SUM(outstanding_balance),0) AS t FROM residents')['t'] ?? 0),
            'bin_allocation' => self::fetchAll('SELECT status, COUNT(*) AS count FROM dustbins GROUP BY status'),
            'payment_stats' => self::fetchAll('SELECT status, COUNT(*) AS count, SUM(amount) AS total FROM payments GROUP BY status'),
            'revenue_trends' => $revenue['trend_30d'],
            'revenue_trends_7d' => $revenue['trend_7d'],
            'revenue_trends_30d' => $revenue['trend_30d'],
            'revenue_trends_6mo' => $revenue['trend_6mo'],
            'customer_growth' => self::fetchAll("SELECT DATE(created_at) AS date, COUNT(*) AS count FROM residents WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) GROUP BY DATE(created_at) ORDER BY date"),
        ];
    }
}
