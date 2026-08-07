<?php
require_once __DIR__ . '/../includes/Model.php';

class RouteModel extends Model
{
    public static function allWithStats(): array
    {
        return self::fetchAll(
            "SELECT cr.*, cz.name AS zone_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS collector_name,
                    c.employee_id,
                    t.plate_number,
                    (SELECT COUNT(*) FROM collection_schedules cs WHERE cs.route_id = cr.id AND cs.status IN ('scheduled','in_progress')) AS scheduled_pickups
             FROM collection_routes cr
             JOIN collection_zones cz ON cr.zone_id = cz.id
             LEFT JOIN collectors c ON cr.collector_id = c.id
             LEFT JOIN users u ON c.user_id = u.id
             LEFT JOIN trucks t ON cr.truck_id = t.id
             ORDER BY cz.name, cr.name"
        );
    }

    public static function find(int $id): ?array
    {
        return self::fetchOne('SELECT * FROM collection_routes WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        self::query(
            'INSERT INTO collection_routes (name, zone_id, collector_id, truck_id, is_active) VALUES (?, ?, ?, ?, ?)',
            [
                $data['name'],
                $data['zone_id'],
                $data['collector_id'] ?: null,
                $data['truck_id'] ?: null,
                $data['is_active'] ?? 1,
            ]
        );
        return (int) self::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        self::query(
            'UPDATE collection_routes SET name = ?, zone_id = ?, collector_id = ?, truck_id = ?, is_active = ? WHERE id = ?',
            [
                $data['name'],
                $data['zone_id'],
                $data['collector_id'] ?: null,
                $data['truck_id'] ?: null,
                $data['is_active'] ?? 1,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        self::query('DELETE FROM collection_routes WHERE id = ?', [$id]);
    }

    public static function forCollector(int $collectorId, ?int $zoneId = null): array
    {
        return self::fetchAll(
            'SELECT * FROM collection_routes WHERE collector_id = ? AND is_active = 1',
            [$collectorId]
        );
    }

    public static function saveOptimization(int $routeId, array $result): void
    {
        self::query(
            'UPDATE collection_routes SET route_data = ?, is_optimized = 1 WHERE id = ?',
            [json_encode($result, JSON_UNESCAPED_UNICODE), $routeId]
        );
    }
}
