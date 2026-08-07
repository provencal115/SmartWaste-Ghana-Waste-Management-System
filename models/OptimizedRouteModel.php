<?php
require_once __DIR__ . '/../includes/Model.php';
require_once __DIR__ . '/../includes/RouteOptimizer.php';

class OptimizedRouteModel extends Model
{
    public static function find(int $id): ?array
    {
        return self::fetchOne(
            'SELECT orr.*, cz.name AS zone_name,
                    CONCAT(u.first_name, " ", u.last_name) AS collector_name,
                    c.employee_id, t.plate_number, t.model AS truck_model
             FROM optimized_routes orr
             JOIN collection_zones cz ON orr.zone_id = cz.id
             JOIN collectors c ON orr.collector_id = c.id
             JOIN users u ON c.user_id = u.id
             LEFT JOIN trucks t ON orr.truck_id = t.id
             WHERE orr.id = ?',
            [$id]
        );
    }

    public static function findCurrent(int $zoneId, int $collectorId, string $date): ?array
    {
        return self::fetchOne(
            'SELECT orr.*, cz.name AS zone_name,
                    CONCAT(u.first_name, " ", u.last_name) AS collector_name,
                    c.employee_id, t.plate_number
             FROM optimized_routes orr
             JOIN collection_zones cz ON orr.zone_id = cz.id
             JOIN collectors c ON orr.collector_id = c.id
             JOIN users u ON c.user_id = u.id
             LEFT JOIN trucks t ON orr.truck_id = t.id
             WHERE orr.zone_id = ? AND orr.collector_id = ? AND orr.collection_date = ?
               AND orr.is_current = 1 AND orr.status != "cancelled"
             ORDER BY orr.version DESC LIMIT 1',
            [$zoneId, $collectorId, $date]
        );
    }

    public static function todayForCollector(int $collectorId): ?array
    {
        $row = self::fetchOne(
            'SELECT orr.*, cz.name AS zone_name, t.plate_number
             FROM optimized_routes orr
             JOIN collection_zones cz ON orr.zone_id = cz.id
             LEFT JOIN trucks t ON orr.truck_id = t.id
             WHERE orr.collector_id = ? AND orr.collection_date = CURDATE()
               AND orr.is_current = 1 AND orr.status != "cancelled"
             ORDER BY orr.version DESC LIMIT 1',
            [$collectorId]
        );

        if ($row) {
            $row['route_data_decoded'] = self::decodeRouteData($row['route_data'] ?? null);
        }

        return $row;
    }

    /**
     * Build and persist an optimised route for a date/zone/collector.
     *
     * @return array<string, mixed>
     */
    public static function optimise(
        string $date,
        int $zoneId,
        int $collectorId,
        ?int $truckId,
        int $adminUserId,
        bool $reoptimise = false,
        ?string $notes = null
    ): array {
        $zone = ZoneModel::find($zoneId);
        if (!$zone) {
            throw new InvalidArgumentException('Zone not found.');
        }

        $schedules = CollectionModel::scheduledForOptimisation($date, $zoneId, $collectorId);
        if (!$schedules) {
            throw new InvalidArgumentException('No scheduled collections found for the selected date and zone.');
        }

        $settings = SettingModel::get('route_optimization') ?? ['algorithm' => 'nearest_neighbor'];
        $algorithm = $settings['algorithm'] ?? 'nearest_neighbor';

        $result = RouteOptimizer::optimize($schedules, (string)$zone['name'], $algorithm);

        $existing = self::findCurrent($zoneId, $collectorId, $date);
        $version = 1;
        if ($existing) {
            if (!$reoptimise) {
                throw new InvalidArgumentException(
                    'An optimised route already exists for this date. Use Re-Optimise to create a new version.'
                );
            }
            self::query(
                'UPDATE optimized_routes SET is_current = 0 WHERE zone_id = ? AND collector_id = ? AND collection_date = ?',
                [$zoneId, $collectorId, $date]
            );
            $version = (int)$existing['version'] + 1;
        }

        $routeName = $zone['name'] . ' Collection Route';
        $collectionRouteId = null;
        $template = self::fetchOne(
            'SELECT id FROM collection_routes WHERE zone_id = ? AND collector_id = ? AND is_active = 1 LIMIT 1',
            [$zoneId, $collectorId]
        );
        if ($template) {
            $collectionRouteId = (int)$template['id'];
        }

        $routeDataJson = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        self::query(
            'INSERT INTO optimized_routes (
                collection_route_id, zone_id, collector_id, truck_id, collection_date, route_name,
                route_data, estimated_distance_km, estimated_duration_min,
                start_lat, start_lng, end_lat, end_lng,
                total_stops, completed_stops, status, algorithm, optimized_at, created_by, version, is_current, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, "optimised", ?, NOW(), ?, ?, 1, ?)',
            [
                $collectionRouteId,
                $zoneId,
                $collectorId,
                $truckId,
                $date,
                $routeName,
                $routeDataJson,
                $result['estimated_distance_km'],
                $result['estimated_duration_min'],
                $result['start_lat'],
                $result['start_lng'],
                $result['end_lat'],
                $result['end_lng'],
                $result['total_stops'],
                $algorithm,
                $adminUserId,
                $version,
                $notes,
            ]
        );

        $optimizedRouteId = (int)self::lastInsertId();

        foreach ($result['stops'] as $stop) {
            self::query(
                'UPDATE collection_schedules SET optimized_route_id = ?, stop_order = ?, route_id = COALESCE(route_id, ?), collector_id = ? WHERE id = ?',
                [
                    $optimizedRouteId,
                    $stop['order'],
                    $collectionRouteId,
                    $collectorId,
                    $stop['schedule_id'],
                ]
            );
        }

        if ($collectionRouteId) {
            RouteModel::saveOptimization($collectionRouteId, $result);
        }

        logActivity($adminUserId, $reoptimise ? 'reoptimise_route' : 'optimise_route', 'routes', [
            'optimized_route_id' => $optimizedRouteId,
            'zone_id' => $zoneId,
            'collector_id' => $collectorId,
            'date' => $date,
            'stops' => $result['total_stops'],
        ]);

        $saved = self::find($optimizedRouteId);
        $saved['route_data_decoded'] = $result;

        return $saved;
    }

    public static function activate(int $id): void
    {
        self::query(
            'UPDATE optimized_routes SET status = "active" WHERE id = ? AND status = "optimised"',
            [$id]
        );
    }

    public static function syncScheduleStatus(int $scheduleId, string $pickupStatus): void
    {
        $schedule = self::fetchOne(
            'SELECT optimized_route_id, pickup_status FROM collection_schedules WHERE id = ?',
            [$scheduleId]
        );
        if (!$schedule || !$schedule['optimized_route_id']) {
            return;
        }

        $routeId = (int)$schedule['optimized_route_id'];
        $route = self::fetchOne('SELECT route_data, total_stops FROM optimized_routes WHERE id = ?', [$routeId]);
        if (!$route) {
            return;
        }

        $data = self::decodeRouteData($route['route_data']);
        if (!$data || empty($data['stops'])) {
            return;
        }

        foreach ($data['stops'] as &$stop) {
            if ((int)($stop['schedule_id'] ?? 0) === $scheduleId) {
                $stop['pickup_status'] = $pickupStatus;
            }
        }
        unset($stop);

        $completed = 0;
        $inProgress = false;
        foreach ($data['stops'] as $stop) {
            if (($stop['pickup_status'] ?? '') === 'completed') {
                $completed++;
            }
            if (($stop['pickup_status'] ?? '') === 'in_progress') {
                $inProgress = true;
            }
        }

        $status = 'in_progress';
        if ($completed >= (int)$route['total_stops']) {
            $status = 'completed';
        } elseif ($completed === 0 && !$inProgress) {
            $status = 'active';
        }

        self::query(
            'UPDATE optimized_routes SET route_data = ?, completed_stops = ?, status = ?,
             completed_at = IF(? = "completed", NOW(), completed_at) WHERE id = ?',
            [
                json_encode($data, JSON_UNESCAPED_UNICODE),
                $completed,
                $status,
                $status,
                $routeId,
            ]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function history(int $limit = 20): array
    {
        return self::fetchAll(
            'SELECT orr.*, cz.name AS zone_name,
                    CONCAT(u.first_name, " ", u.last_name) AS collector_name,
                    t.plate_number
             FROM optimized_routes orr
             JOIN collection_zones cz ON orr.zone_id = cz.id
             JOIN collectors c ON orr.collector_id = c.id
             JOIN users u ON c.user_id = u.id
             LEFT JOIN trucks t ON orr.truck_id = t.id
             ORDER BY orr.optimized_at DESC LIMIT ?',
            [$limit]
        );
    }

    /** @return array<string, mixed> */
    public static function analytics(): array
    {
        try {
            return [
                'total_routes'           => (int)(self::fetchOne('SELECT COUNT(*) AS c FROM optimized_routes')['c'] ?? 0),
                'optimised_routes'       => (int)(self::fetchOne('SELECT COUNT(*) AS c FROM optimized_routes WHERE status = "optimised"')['c'] ?? 0),
                'active_routes'          => (int)(self::fetchOne('SELECT COUNT(*) AS c FROM optimized_routes WHERE status IN ("active","in_progress") AND is_current = 1')['c'] ?? 0),
                'completed_routes'       => (int)(self::fetchOne('SELECT COUNT(*) AS c FROM optimized_routes WHERE status = "completed"')['c'] ?? 0),
                'avg_distance_km'        => round((float)(self::fetchOne('SELECT AVG(estimated_distance_km) AS a FROM optimized_routes WHERE is_current = 1')['a'] ?? 0), 1),
                'avg_duration_min'       => round((float)(self::fetchOne('SELECT AVG(estimated_duration_min) AS a FROM optimized_routes WHERE is_current = 1')['a'] ?? 0), 0),
                'collections_per_vehicle'=> self::fetchAll(
                    'SELECT t.plate_number, COUNT(DISTINCT orr.id) AS route_count,
                            SUM(orr.total_stops) AS total_collections
                     FROM optimized_routes orr
                     JOIN trucks t ON orr.truck_id = t.id
                     WHERE orr.is_current = 1
                     GROUP BY t.id, t.plate_number
                     ORDER BY route_count DESC LIMIT 10'
                ),
                'today_routes'           => (int)(self::fetchOne(
                    'SELECT COUNT(*) AS c FROM optimized_routes WHERE collection_date = CURDATE() AND is_current = 1'
                )['c'] ?? 0),
            ];
        } catch (Throwable) {
            return [
                'total_routes' => 0, 'optimised_routes' => 0, 'active_routes' => 0,
                'completed_routes' => 0, 'avg_distance_km' => 0, 'avg_duration_min' => 0,
                'collections_per_vehicle' => [], 'today_routes' => 0,
            ];
        }
    }

    /** @return array<string, mixed>|null */
    public static function decodeRouteData(mixed $json): ?array
    {
        if (is_array($json)) {
            return $json;
        }
        if (!is_string($json) || $json === '') {
            return null;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }

    public static function progressPercent(array $route): int
    {
        $total = (int)($route['total_stops'] ?? 0);
        if ($total <= 0) {
            return 0;
        }
        $done = (int)($route['completed_stops'] ?? 0);
        return (int)min(100, round(($done / $total) * 100));
    }
}
