<?php
require_once __DIR__ . '/../includes/Model.php';

/**
 * Business intelligence — all metrics from live database records.
 */
class AnalyticsModel extends Model
{
    /** @return array<string, mixed> */
    public static function parseFilters(array $input): array
    {
        return [
            'date_from'    => self::validDate($input['date_from'] ?? null),
            'date_to'      => self::validDate($input['date_to'] ?? null),
            'zone_id'      => !empty($input['zone_id']) ? (int)$input['zone_id'] : null,
            'truck_id'     => !empty($input['truck_id']) ? (int)$input['truck_id'] : null,
            'collector_id' => !empty($input['collector_id']) ? (int)$input['collector_id'] : null,
            'status'       => self::validCollectionStatus($input['status'] ?? null),
        ];
    }

    /** @return array<string, mixed> */
    public static function fullReport(array $filters): array
    {
        return [
            'filters'      => $filters,
            'operational'  => self::operationalKpis($filters),
            'performance'  => self::collectionPerformance($filters),
            'revenue'      => self::revenueMetrics($filters),
            'collections'  => self::collectionTrends($filters),
            'zones'        => self::zonePerformance($filters),
            'vehicles'     => self::vehiclePerformance($filters),
            'collectors'   => self::collectorPerformance($filters),
            'satisfaction' => self::customerSatisfaction($filters),
        ];
    }

    /** @return array<string, mixed> */
    public static function operationalKpis(array $filters): array
    {
        $ops = operationalStats();
        $scoped = self::collectionCounts($filters);

        $availableBins = (int)(self::fetchOne(
            "SELECT COUNT(*) AS c FROM dustbins WHERE status = 'available'"
        )['c'] ?? 0);

        $activeVehicles = (int)(self::fetchOne(
            "SELECT COUNT(*) AS c FROM trucks WHERE status IN ('active', 'on_route')"
        )['c'] ?? 0);

        $revenue = self::revenueTotals($filters);

        return [
            'registered_residents'  => $ops['registered_residents'],
            'active_customers'      => $ops['active_customers'],
            'total_collections'     => $scoped['total'],
            'completed_collections' => $scoped['completed'],
            'pending_collections'   => $scoped['pending'],
            'missed_collections'    => $scoped['missed'],
            'delayed_collections'   => $scoped['delayed'],
            'in_progress'           => $scoped['in_progress'],
            'completion_rate'       => self::rate($scoped['completed'], $scoped['total']),
            'on_time_rate'          => self::rate($scoped['on_time'], max(1, $scoped['completed'])),
            'total_revenue'         => $revenue['total'],
            'active_vehicles'       => $activeVehicles,
            'available_bins'        => $availableBins,
            'avg_rating'            => self::customerSatisfaction($filters)['average'],
        ];
    }

    /** @return array<string, mixed> */
    public static function collectionPerformance(array $filters): array
    {
        $c = self::collectionCounts($filters);
        $total = max(1, $c['total']);

        return [
            'completion_rate' => self::rate($c['completed'], $c['total']),
            'missed_rate'     => self::rate($c['missed'], $total),
            'delayed_rate'    => self::rate($c['delayed'], $total),
            'on_time_rate'    => self::rate($c['on_time'], max(1, $c['completed'])),
            'counts'          => $c,
        ];
    }

    /** @return array<string, mixed> */
    public static function revenueMetrics(array $filters): array
    {
        $totals = self::revenueTotals($filters);
        $paymentCounts = self::paymentStatusCounts($filters);

        $completedTx = (int)($paymentCounts['completed']['count'] ?? 0);
        $avgPayment = $completedTx > 0 ? round($totals['total'] / $completedTx, 2) : 0.0;

        return array_merge($totals, [
            'avg_customer_payment'  => $avgPayment,
            'completed_transactions'=> $completedTx,
            'pending_payments'      => (int)($paymentCounts['pending']['count'] ?? 0),
            'overdue_payments'      => self::overduePaymentCount($filters),
            'trend_7d'              => self::revenueTrend($filters, 7, 'day'),
            'trend_30d'             => self::revenueTrend($filters, 30, 'day'),
            'trend_6mo'             => self::revenueTrend($filters, 6, 'month'),
            'trend_1y'              => self::revenueTrend($filters, 12, 'month'),
            'by_method'             => self::revenueByMethod($filters),
        ]);
    }

    /** @return array<string, mixed> */
    public static function collectionTrends(array $filters): array
    {
        $base = self::collectionJoinSql($filters);

        $daily = self::fetchAll(
            "SELECT DATE(cs.preferred_date) AS period,
                    SUM(CASE WHEN cs.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN cs.status = 'missed' THEN 1 ELSE 0 END) AS missed,
                    SUM(CASE WHEN cs.status = 'delayed' OR cs.pickup_status = 'delayed' THEN 1 ELSE 0 END) AS delayed_cnt,
                    COUNT(*) AS total
             FROM collection_schedules cs
             {$base['joins']}
             WHERE {$base['where']}
             GROUP BY DATE(cs.preferred_date)
             ORDER BY period DESC
             LIMIT 30",
            $base['params']
        );

        $weekly = self::fetchAll(
            "SELECT YEARWEEK(cs.preferred_date, 1) AS period_key,
                    MIN(cs.preferred_date) AS period,
                    SUM(CASE WHEN cs.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN cs.status = 'missed' THEN 1 ELSE 0 END) AS missed,
                    SUM(CASE WHEN cs.status = 'delayed' OR cs.pickup_status = 'delayed' THEN 1 ELSE 0 END) AS delayed_cnt,
                    COUNT(*) AS total
             FROM collection_schedules cs
             {$base['joins']}
             WHERE {$base['where']}
             GROUP BY YEARWEEK(cs.preferred_date, 1)
             ORDER BY period_key DESC
             LIMIT 12",
            $base['params']
        );

        $monthly = self::fetchAll(
            "SELECT DATE_FORMAT(cs.preferred_date, '%Y-%m') AS period,
                    SUM(CASE WHEN cs.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN cs.status = 'missed' THEN 1 ELSE 0 END) AS missed,
                    SUM(CASE WHEN cs.status = 'delayed' OR cs.pickup_status = 'delayed' THEN 1 ELSE 0 END) AS delayed_cnt,
                    COUNT(*) AS total
             FROM collection_schedules cs
             {$base['joins']}
             WHERE {$base['where']}
             GROUP BY DATE_FORMAT(cs.preferred_date, '%Y-%m')
             ORDER BY period DESC
             LIMIT 12",
            $base['params']
        );

        return [
            'daily'   => array_reverse($daily),
            'weekly'  => array_reverse($weekly),
            'monthly' => array_reverse($monthly),
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function zonePerformance(array $filters): array
    {
        $base = self::collectionJoinSql($filters, false);

        $rows = self::fetchAll(
            "SELECT cz.id, cz.name,
                    COUNT(*) AS total,
                    SUM(CASE WHEN cs.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN cs.status = 'missed' THEN 1 ELSE 0 END) AS missed,
                    SUM(CASE WHEN cs.status = 'delayed' OR cs.pickup_status = 'delayed' THEN 1 ELSE 0 END) AS delayed_cnt
             FROM collection_schedules cs
             JOIN residents r ON cs.resident_id = r.id
             JOIN collection_zones cz ON r.zone_id = cz.id
             {$base['extra_joins']}
             WHERE {$base['where']}
             GROUP BY cz.id, cz.name
             ORDER BY completed DESC, cz.name",
            $base['params']
        );

        return array_map(static function ($row) {
            $total = (int)$row['total'];
            return [
                'zone_id'         => (int)$row['id'],
                'name'            => $row['name'],
                'total'           => $total,
                'completed'       => (int)$row['completed'],
                'missed'          => (int)$row['missed'],
                'delayed'         => (int)$row['delayed_cnt'],
                'completion_rate' => self::rate((int)$row['completed'], $total),
            ];
        }, $rows);
    }

    /** @return list<array<string, mixed>> */
    public static function vehiclePerformance(array $filters): array
    {
        $trucks = self::fetchAll(
            "SELECT id, plate_number, model, status FROM trucks WHERE status != 'retired' ORDER BY plate_number"
        );

        $results = [];
        foreach ($trucks as $t) {
            if ($filters['truck_id'] && (int)$filters['truck_id'] !== (int)$t['id']) {
                continue;
            }

            $where = [
                "cs.status != 'cancelled'",
                'COALESCE(orr.truck_id, cr.truck_id) = ?',
            ];
            $params = [(int)$t['id']];

            if ($filters['date_from']) {
                $where[] = 'cs.preferred_date >= ?';
                $params[] = $filters['date_from'];
            }
            if ($filters['date_to']) {
                $where[] = 'cs.preferred_date <= ?';
                $params[] = $filters['date_to'];
            }
            if ($filters['zone_id']) {
                $where[] = 'r.zone_id = ?';
                $params[] = $filters['zone_id'];
            }
            if ($filters['collector_id']) {
                $where[] = 'cs.collector_id = ?';
                $params[] = $filters['collector_id'];
            }

            $stats = self::fetchOne(
                'SELECT COUNT(*) AS total,
                        SUM(CASE WHEN cs.status = \'completed\' THEN 1 ELSE 0 END) AS completed,
                        SUM(CASE WHEN cs.status = \'missed\' THEN 1 ELSE 0 END) AS missed,
                        MIN(cs.preferred_date) AS first_date,
                        MAX(cs.preferred_date) AS last_date
                 FROM collection_schedules cs
                 JOIN residents r ON cs.resident_id = r.id
                 LEFT JOIN optimized_routes orr ON cs.optimized_route_id = orr.id
                 LEFT JOIN collection_routes cr ON cs.route_id = cr.id
                 WHERE ' . implode(' AND ', $where),
                $params
            ) ?: [];

            $completed = (int)($stats['completed'] ?? 0);
            $first = $stats['first_date'] ?? null;
            $last = $stats['last_date'] ?? null;
            $days = 1;
            if ($first && $last) {
                $days = max(1, (int)((strtotime($last) - strtotime($first)) / 86400) + 1);
            }

            $results[] = [
                'id'                    => (int)$t['id'],
                'plate_number'          => $t['plate_number'],
                'model'                 => $t['model'],
                'maintenance_status'    => $t['status'],
                'total_collections'     => (int)($stats['total'] ?? 0),
                'completed'             => $completed,
                'missed'                => (int)($stats['missed'] ?? 0),
                'avg_collections_per_day' => round($completed / $days, 1),
            ];
        }

        usort($results, static fn ($a, $b) => $b['completed'] <=> $a['completed']);

        return $results;
    }

    /** @return list<array<string, mixed>> */
    public static function collectorPerformance(array $filters): array
    {
        $base = self::collectionJoinSql($filters);

        $rows = self::fetchAll(
            "SELECT c.id, c.employee_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS name,
                    COUNT(*) AS total,
                    SUM(CASE WHEN cs.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN cs.status = 'missed' THEN 1 ELSE 0 END) AS missed
             FROM collectors c
             JOIN users u ON c.user_id = u.id
             JOIN collection_schedules cs ON cs.collector_id = c.id
             {$base['joins']}
             WHERE {$base['where']}
             GROUP BY c.id, c.employee_id, u.first_name, u.last_name
             ORDER BY completed DESC
             LIMIT 15",
            $base['params']
        );

        return array_map(static function ($row) {
            $total = (int)$row['total'];
            return [
                'id'              => (int)$row['id'],
                'employee_id'     => $row['employee_id'],
                'name'            => $row['name'],
                'total'           => $total,
                'completed'       => (int)$row['completed'],
                'missed'          => (int)$row['missed'],
                'completion_rate' => self::rate((int)$row['completed'], $total),
            ];
        }, $rows);
    }

    /** @return array<string, mixed> */
    public static function customerSatisfaction(array $filters): array
    {
        $where = ['c.rating IS NOT NULL'];
        $params = [];
        $joins = 'JOIN residents r ON c.resident_id = r.id';

        if ($filters['date_from']) {
            $where[] = 'DATE(c.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if ($filters['date_to']) {
            $where[] = 'DATE(c.created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        if ($filters['zone_id']) {
            $where[] = 'r.zone_id = ?';
            $params[] = $filters['zone_id'];
        }

        $sqlWhere = implode(' AND ', $where);

        $avg = (float)(self::fetchOne(
            "SELECT AVG(c.rating) AS a FROM complaints c {$joins} WHERE {$sqlWhere}",
            $params
        )['a'] ?? 0);

        $distribution = self::fetchAll(
            "SELECT c.rating, COUNT(*) AS count FROM complaints c {$joins}
             WHERE {$sqlWhere} GROUP BY c.rating ORDER BY c.rating",
            $params
        );

        $distMap = array_fill(1, 5, 0);
        foreach ($distribution as $d) {
            $distMap[(int)$d['rating']] = (int)$d['count'];
        }

        $totalRatings = array_sum($distMap);

        return [
            'average'       => round($avg, 1),
            'total_ratings' => $totalRatings,
            'distribution'  => $distMap,
        ];
    }

    /** @return array<string, int> */
    private static function collectionCounts(array $filters): array
    {
        $base = self::collectionJoinSql($filters);

        $row = self::fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN cs.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN cs.status IN ('scheduled', 'rescheduled') AND cs.pickup_status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN cs.status = 'missed' OR cs.pickup_status = 'missed' THEN 1 ELSE 0 END) AS missed,
                SUM(CASE WHEN cs.status = 'delayed' OR cs.pickup_status = 'delayed' THEN 1 ELSE 0 END) AS delayed_count,
                SUM(CASE WHEN cs.status = 'in_progress' OR cs.pickup_status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count,
                SUM(CASE WHEN cs.status = 'completed'
                    AND cs.completed_at IS NOT NULL
                    AND DATE(cs.completed_at) <= cs.preferred_date THEN 1 ELSE 0 END) AS on_time_count
             FROM collection_schedules cs
             {$base['joins']}
             WHERE {$base['where']}",
            $base['params']
        ) ?: [];

        return [
            'total'       => (int)($row['total'] ?? 0),
            'completed'   => (int)($row['completed'] ?? 0),
            'pending'     => (int)($row['pending'] ?? 0),
            'missed'      => (int)($row['missed'] ?? 0),
            'delayed'     => (int)($row['delayed_count'] ?? 0),
            'in_progress' => (int)($row['in_progress_count'] ?? 0),
            'on_time'     => (int)($row['on_time_count'] ?? 0),
        ];
    }

    /** @return array{joins: string, extra_joins: string, where: string, params: list<mixed>} */
    private static function collectionJoinSql(array $filters, bool $includeResidentJoin = true): array
    {
        $joins = '';
        $extraJoins = '';
        $where = ["cs.status != 'cancelled'"];
        $params = [];

        if ($includeResidentJoin) {
            $joins .= ' JOIN residents r ON cs.resident_id = r.id';
        }

        $joins .= ' LEFT JOIN optimized_routes orr ON cs.optimized_route_id = orr.id';
        $joins .= ' LEFT JOIN collection_routes cr ON cs.route_id = cr.id';
        $extraJoins = ' LEFT JOIN optimized_routes orr ON cs.optimized_route_id = orr.id LEFT JOIN collection_routes cr ON cs.route_id = cr.id';

        if ($filters['date_from']) {
            $where[] = 'cs.preferred_date >= ?';
            $params[] = $filters['date_from'];
        }
        if ($filters['date_to']) {
            $where[] = 'cs.preferred_date <= ?';
            $params[] = $filters['date_to'];
        }
        if ($filters['zone_id']) {
            $where[] = 'r.zone_id = ?';
            $params[] = $filters['zone_id'];
        }
        if ($filters['collector_id']) {
            $where[] = 'cs.collector_id = ?';
            $params[] = $filters['collector_id'];
        }
        if ($filters['truck_id']) {
            $where[] = 'COALESCE(orr.truck_id, cr.truck_id) = ?';
            $params[] = $filters['truck_id'];
        }
        if ($filters['status']) {
            $where[] = '(cs.status = ? OR cs.pickup_status = ?)';
            $params[] = $filters['status'];
            $params[] = $filters['status'];
        }

        return [
            'joins'       => $joins,
            'extra_joins' => $extraJoins,
            'where'       => implode(' AND ', $where),
            'params'      => $params,
        ];
    }

    /** @return array{total: float, weekly: float, monthly: float} */
    private static function revenueTotals(array $filters): array
    {
        $where = ["p.status = 'completed'"];
        $params = [];
        $joins = '';

        if ($filters['date_from']) {
            $where[] = 'DATE(p.paid_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if ($filters['date_to']) {
            $where[] = 'DATE(p.paid_at) <= ?';
            $params[] = $filters['date_to'];
        }
        if ($filters['zone_id']) {
            $joins = 'JOIN residents r ON p.resident_id = r.id';
            $where[] = 'r.zone_id = ?';
            $params[] = $filters['zone_id'];
        }

        $sqlWhere = implode(' AND ', $where);

        $total = (float)(self::fetchOne(
            "SELECT COALESCE(SUM(p.amount), 0) AS t FROM payments p {$joins} WHERE {$sqlWhere}",
            $params
        )['t'] ?? 0);

        $weekly = (float)(self::fetchOne(
            "SELECT COALESCE(SUM(p.amount), 0) AS t FROM payments p {$joins}
             WHERE {$sqlWhere} AND p.paid_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            $params
        )['t'] ?? 0);

        $monthly = (float)(self::fetchOne(
            "SELECT COALESCE(SUM(p.amount), 0) AS t FROM payments p {$joins}
             WHERE {$sqlWhere} AND p.paid_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            $params
        )['t'] ?? 0);

        return ['total' => $total, 'weekly' => $weekly, 'monthly' => $monthly];
    }

    /** @return array<string, array{count: int, total: float}> */
    private static function paymentStatusCounts(array $filters): array
    {
        $joins = '';
        $where = ['1=1'];
        $params = [];

        if ($filters['zone_id']) {
            $joins = 'JOIN residents r ON p.resident_id = r.id';
            $where[] = 'r.zone_id = ?';
            $params[] = $filters['zone_id'];
        }

        $rows = self::fetchAll(
            'SELECT p.status, COUNT(*) AS count, COALESCE(SUM(p.amount), 0) AS total
             FROM payments p ' . $joins . '
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY p.status',
            $params
        );

        $out = [];
        foreach ($rows as $row) {
            $out[$row['status']] = ['count' => (int)$row['count'], 'total' => (float)$row['total']];
        }
        return $out;
    }

    private static function overduePaymentCount(array $filters): int
    {
        $joins = '';
        $where = ["p.status IN ('pending', 'failed')", 'p.due_date IS NOT NULL', 'p.due_date < CURDATE()'];
        $params = [];

        if ($filters['zone_id']) {
            $joins = 'JOIN residents r ON p.resident_id = r.id';
            $where[] = 'r.zone_id = ?';
            $params[] = $filters['zone_id'];
        }

        return (int)(self::fetchOne(
            'SELECT COUNT(*) AS c FROM payments p ' . $joins . ' WHERE ' . implode(' AND ', $where),
            $params
        )['c'] ?? 0);
    }

    /** @return list<array{date?: string, label?: string, revenue: float}> */
    private static function revenueTrend(array $filters, int $period, string $granularity): array
    {
        $joins = '';
        $where = ["p.status = 'completed'", 'p.paid_at IS NOT NULL'];
        $params = [];

        if ($filters['date_from']) {
            $where[] = 'DATE(p.paid_at) >= ?';
            $params[] = $filters['date_from'];
        } else {
            if ($granularity === 'day') {
                $where[] = 'p.paid_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)';
                $params[] = $period;
            } else {
                $where[] = 'p.paid_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)';
                $params[] = $period;
            }
        }
        if ($filters['date_to']) {
            $where[] = 'DATE(p.paid_at) <= ?';
            $params[] = $filters['date_to'];
        }
        if ($filters['zone_id']) {
            $joins = 'JOIN residents r ON p.resident_id = r.id';
            $where[] = 'r.zone_id = ?';
            $params[] = $filters['zone_id'];
        }

        if ($granularity === 'month') {
            $rows = self::fetchAll(
                'SELECT DATE_FORMAT(p.paid_at, "%Y-%m") AS month, SUM(p.amount) AS revenue
                 FROM payments p ' . $joins . '
                 WHERE ' . implode(' AND ', $where) . '
                 GROUP BY DATE_FORMAT(p.paid_at, "%Y-%m")
                 ORDER BY month',
                $params
            );
            return array_map(static function ($r) {
                $ts = strtotime($r['month'] . '-01');
                return [
                    'month'   => $r['month'],
                    'label'   => $ts ? date('M Y', $ts) : $r['month'],
                    'revenue' => (float)$r['revenue'],
                ];
            }, $rows);
        }

        $rows = self::fetchAll(
            'SELECT DATE(p.paid_at) AS date, SUM(p.amount) AS revenue
             FROM payments p ' . $joins . '
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY DATE(p.paid_at)
             ORDER BY date',
            $params
        );

        return array_map(static fn ($r) => [
            'date'    => $r['date'],
            'revenue' => (float)$r['revenue'],
        ], $rows);
    }

    /** @return list<array<string, mixed>> */
    private static function revenueByMethod(array $filters): array
    {
        $joins = '';
        $where = ["p.status = 'completed'"];
        $params = [];

        if ($filters['zone_id']) {
            $joins = 'JOIN residents r ON p.resident_id = r.id';
            $where[] = 'r.zone_id = ?';
            $params[] = $filters['zone_id'];
        }

        return self::fetchAll(
            'SELECT p.payment_method, SUM(p.amount) AS total, COUNT(*) AS count
             FROM payments p ' . $joins . '
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY p.payment_method ORDER BY total DESC',
            $params
        );
    }

    private static function rate(int $part, int $whole): float
    {
        if ($whole <= 0) {
            return 0.0;
        }
        return round(($part / $whole) * 100, 1);
    }

    private static function validDate(?string $date): ?string
    {
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }
        return $date;
    }

    private static function validCollectionStatus(?string $status): ?string
    {
        $allowed = ['scheduled', 'in_progress', 'completed', 'delayed', 'missed', 'rescheduled', 'pending'];
        return in_array($status, $allowed, true) ? $status : null;
    }

    /** @return list<array<string, string>> */
    public static function exportRows(array $report): array
    {
        $rows = [];
        $op = $report['operational'];
        $perf = $report['performance'];
        $rev = $report['revenue'];
        $sat = $report['satisfaction'];

        $rows[] = ['Section' => 'Operational KPIs', 'Metric' => 'Registered Residents', 'Value' => (string)$op['registered_residents']];
        $rows[] = ['Section' => 'Operational KPIs', 'Metric' => 'Active Customers', 'Value' => (string)$op['active_customers']];
        $rows[] = ['Section' => 'Operational KPIs', 'Metric' => 'Total Collections', 'Value' => (string)$op['total_collections']];
        $rows[] = ['Section' => 'Operational KPIs', 'Metric' => 'Completed Collections', 'Value' => (string)$op['completed_collections']];
        $rows[] = ['Section' => 'Operational KPIs', 'Metric' => 'Completion Rate', 'Value' => $perf['completion_rate'] . '%'];
        $rows[] = ['Section' => 'Operational KPIs', 'Metric' => 'On-Time Rate', 'Value' => $perf['on_time_rate'] . '%'];
        $rows[] = ['Section' => 'Revenue', 'Metric' => 'Total Revenue', 'Value' => (string)$rev['total']];
        $rows[] = ['Section' => 'Revenue', 'Metric' => 'Monthly Revenue', 'Value' => (string)$rev['monthly']];
        $rows[] = ['Section' => 'Satisfaction', 'Metric' => 'Average Rating', 'Value' => $sat['average'] . ' / 5'];

        foreach ($report['zones'] as $z) {
            $rows[] = ['Section' => 'Zone Performance', 'Metric' => $z['name'], 'Value' => $z['completion_rate'] . '%'];
        }
        foreach ($report['vehicles'] as $v) {
            $rows[] = ['Section' => 'Vehicle Performance', 'Metric' => $v['plate_number'], 'Value' => ($v['completed'] ?? 0) . ' completed'];
        }

        return $rows;
    }
}
