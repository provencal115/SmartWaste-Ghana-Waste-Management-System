<?php
require_once __DIR__ . '/../includes/Model.php';

/**
 * Smart inventory forecasting from live dustbin and assignment records.
 */
class InventoryForecastModel extends Model
{
    /** @return array<string, mixed> */
    public static function settings(): array
    {
        $defaults = [
            'enabled'            => true,
            'lookback_days'      => 90,
            'safety_stock_days'  => 30,
            'reorder_multiplier' => 1.5,
            'minimum_by_size'    => ['small' => 20, 'medium' => 20, 'large' => 20],
        ];

        try {
            $stored = SettingModel::get('inventory_forecast');
            if (is_array($stored)) {
                return array_merge($defaults, $stored);
            }
        } catch (Throwable $e) {
            // ignore
        }

        return $defaults;
    }

    /** @return list<string> */
    public static function sizes(): array
    {
        return ['small', 'medium', 'large'];
    }

    public static function sizeLabel(string $size): string
    {
        return binCapacity($size) . 'L';
    }

    /**
     * Lifecycle counts grouped by bin size (120L / 240L / 360L).
     *
     * @return array<string, array<string, int>>
     */
    public static function lifecycleBySize(): array
    {
        $rows = self::fetchAll(
            "SELECT d.size,
                    COUNT(*) AS total,
                    SUM(CASE WHEN d.status = 'available' THEN 1 ELSE 0 END) AS available,
                    SUM(CASE WHEN d.status = 'assigned' THEN 1 ELSE 0 END) AS assigned,
                    SUM(CASE WHEN d.status = 'damaged' THEN 1 ELSE 0 END) AS damaged,
                    SUM(CASE WHEN d.status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance,
                    SUM(CASE WHEN d.status = 'retired' THEN 1 ELSE 0 END) AS retired,
                    SUM(CASE WHEN d.status = 'lost' THEN 1 ELSE 0 END) AS lost
             FROM dustbins d
             GROUP BY d.size"
        );

        $returned = self::fetchAll(
            "SELECT d.size, COUNT(DISTINCT d.id) AS returned
             FROM dustbins d
             JOIN bin_assignments ba ON ba.dustbin_id = d.id
             WHERE ba.returned_at IS NOT NULL
               AND d.status = 'available'
             GROUP BY d.size"
        );
        $returnedMap = [];
        foreach ($returned as $r) {
            $returnedMap[$r['size']] = (int)$r['returned'];
        }

        $active = self::fetchAll(
            "SELECT d.size, COUNT(*) AS active
             FROM dustbins d
             JOIN bin_assignments ba ON ba.dustbin_id = d.id AND ba.is_active = 1
             WHERE d.status = 'assigned'
             GROUP BY d.size"
        );
        $activeMap = [];
        foreach ($active as $r) {
            $activeMap[$r['size']] = (int)$r['active'];
        }

        $out = [];
        foreach (self::sizes() as $size) {
            $row = null;
            foreach ($rows as $r) {
                if ($r['size'] === $size) {
                    $row = $r;
                    break;
                }
            }
            $out[$size] = [
                'label'       => self::sizeLabel($size),
                'total'       => (int)($row['total'] ?? 0),
                'available'   => (int)($row['available'] ?? 0),
                'assigned'    => (int)($row['assigned'] ?? 0),
                'active'      => (int)($activeMap[$size] ?? 0),
                'damaged'     => (int)($row['damaged'] ?? 0),
                'maintenance' => (int)($row['maintenance'] ?? 0),
                'returned'    => (int)($returnedMap[$size] ?? 0),
                'retired'     => (int)($row['retired'] ?? 0),
                'lost'        => (int)($row['lost'] ?? 0),
            ];
        }

        return $out;
    }

    /** @return array<string, int> */
    public static function totals(): array
    {
        $lifecycle = self::lifecycleBySize();
        $totals = [
            'total'       => 0,
            'available'   => 0,
            'assigned'    => 0,
            'active'      => 0,
            'damaged'     => 0,
            'maintenance' => 0,
            'returned'    => 0,
            'retired'     => 0,
            'lost'        => 0,
        ];
        foreach ($lifecycle as $row) {
            foreach ($totals as $key => $_) {
                $totals[$key] += $row[$key];
            }
        }
        return $totals;
    }

    public static function minimumForSize(string $size): int
    {
        $settings = self::settings();
        $configured = (int)($settings['minimum_by_size'][$size] ?? 0);
        if ($configured > 0) {
            return $configured;
        }

        $row = self::fetchOne(
            'SELECT MIN(minimum_quantity) AS min_qty FROM inventory_thresholds WHERE bin_size = ?',
            [$size]
        );
        return max(5, (int)($row['min_qty'] ?? 10));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function stockBySize(): array
    {
        $lifecycle = self::lifecycleBySize();
        $out = [];
        foreach (self::sizes() as $size) {
            $row = $lifecycle[$size];
            $out[] = [
                'size'      => $size,
                'label'     => $row['label'],
                'available' => $row['available'],
                'total'     => $row['total'],
                'minimum'   => self::minimumForSize($size),
            ];
        }
        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function lowStockAlerts(): array
    {
        $alerts = [];
        foreach (self::forecastBySize() as $forecast) {
            if ($forecast['current_stock'] < $forecast['minimum']) {
                $alerts[] = [
                    'size'                 => $forecast['size'],
                    'label'                => $forecast['label'],
                    'current_stock'        => $forecast['current_stock'],
                    'minimum'              => $forecast['minimum'],
                    'recommended_reorder'  => $forecast['recommended_reorder'],
                    'status'               => $forecast['status'],
                    'avg_monthly_usage'    => $forecast['avg_monthly_usage'],
                    'estimated_depletion'  => $forecast['estimated_depletion_days'],
                    'limited_data'         => $forecast['limited_data'],
                    'no_history'           => $forecast['no_history'],
                ];
            }
        }
        return $alerts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forecastBySize(): array
    {
        $settings = self::settings();
        $lookback = max(30, (int)$settings['lookback_days']);
        $multiplier = max(1.0, (float)$settings['reorder_multiplier']);
        $safetyDays = max(7, (int)$settings['safety_stock_days']);

        $usageRows = self::fetchAll(
            "SELECT d.size,
                    COUNT(*) AS assignment_count,
                    COUNT(DISTINCT DATE_FORMAT(ba.assigned_at, '%Y-%m')) AS active_months,
                    MIN(ba.assigned_at) AS first_assignment,
                    MAX(ba.assigned_at) AS last_assignment
             FROM bin_assignments ba
             JOIN dustbins d ON d.id = ba.dustbin_id
             WHERE ba.assigned_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY d.size",
            [$lookback]
        );

        $usageMap = [];
        foreach ($usageRows as $row) {
            $usageMap[$row['size']] = $row;
        }

        $lifecycle = self::lifecycleBySize();
        $forecasts = [];

        foreach (self::sizes() as $size) {
            $current = (int)$lifecycle[$size]['available'];
            $minimum = self::minimumForSize($size);
            $usage = $usageMap[$size] ?? null;

            $assignmentCount = (int)($usage['assignment_count'] ?? 0);
            $activeMonths = (int)($usage['active_months'] ?? 0);

            $noHistory = $assignmentCount === 0;
            $limitedData = !$noHistory && ($assignmentCount < 3 || $activeMonths < 2);

            $avgMonthlyUsage = 0.0;
            if ($assignmentCount > 0) {
                $divisor = max(1, $activeMonths > 0 ? $activeMonths : (int)ceil($lookback / 30));
                $avgMonthlyUsage = round($assignmentCount / $divisor, 1);
            }

            $estimatedDepletion = null;
            $depletionLabel = 'N/A — insufficient usage history';
            if ($noHistory) {
                $depletionLabel = 'N/A — insufficient usage history';
            } elseif ($current === 0) {
                $estimatedDepletion = 0;
                $depletionLabel = 'Out of stock';
            } elseif ($avgMonthlyUsage > 0) {
                $dailyUsage = $avgMonthlyUsage / 30;
                if ($dailyUsage > 0) {
                    $estimatedDepletion = (int)round($current / $dailyUsage);
                    $depletionLabel = (string)$estimatedDepletion . ' days';
                }
            }

            $shortfall = max(0, $minimum - $current);
            $recommendedReorder = null;
            if (!$noHistory && ($shortfall > 0 || $avgMonthlyUsage > 0)) {
                $usageBuffer = (int)ceil($avgMonthlyUsage * $multiplier);
                $recommendedReorder = max($shortfall + max($usageBuffer, (int)ceil($minimum * 0.5)), max($shortfall, 1));
            } elseif ($shortfall > 0) {
                $recommendedReorder = $shortfall;
            }

            if ($current === 0 || $current < $minimum) {
                $status = 'critical';
            } elseif (!$noHistory && $estimatedDepletion !== null && $estimatedDepletion <= $safetyDays) {
                $status = 'critical';
            } elseif ($current < (int)ceil($minimum * 1.5) || (!$noHistory && $estimatedDepletion !== null && $estimatedDepletion <= ($safetyDays * 2))) {
                $status = 'warning';
            } else {
                $status = 'ok';
            }

            $forecasts[] = [
                'size'                     => $size,
                'label'                    => self::sizeLabel($size),
                'current_stock'            => $current,
                'minimum'                  => $minimum,
                'avg_monthly_usage'        => $avgMonthlyUsage,
                'assignment_count'       => $assignmentCount,
                'estimated_depletion_days' => $estimatedDepletion,
                'depletion_label'          => $depletionLabel,
                'recommended_reorder'      => $recommendedReorder,
                'status'                   => $status,
                'limited_data'             => $limitedData,
                'no_history'               => $noHistory,
                'lookback_days'            => $lookback,
            ];
        }

        return $forecasts;
    }

    /**
     * Chart datasets for inventory trends (last N months).
     *
     * @return array<string, mixed>
     */
    public static function trendCharts(int $months = 12): array
    {
        $months = max(3, min(24, $months));
        $labels = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $labels[] = date('M Y', strtotime("-{$i} months"));
        }
        $monthKeys = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKeys[] = date('Y-m', strtotime("-{$i} months"));
        }

        $allocations = self::monthlyCounts('assignment', $months);
        $returns = self::monthlyCounts('return', $months);
        $damaged = self::monthlyDamagedCounts($months);
        $usage = self::monthlyUsageTotals($months);

        $stockNow = [];
        foreach (self::sizes() as $size) {
            $stockNow[$size] = (int)(self::lifecycleBySize()[$size]['available'] ?? 0);
        }

        return [
            'labels'      => $labels,
            'month_keys'  => $monthKeys,
            'allocations' => self::mapSeries($monthKeys, $allocations),
            'returns'     => self::mapSeries($monthKeys, $returns),
            'damaged'     => self::mapSeries($monthKeys, $damaged),
            'usage'       => self::mapSeries($monthKeys, $usage),
            'stock_now'   => $stockNow,
            'by_size'     => [
                'small'  => self::monthlyCountsBySize('assignment', $months),
                'medium' => self::monthlyCountsBySize('assignment', $months),
                'large'  => self::monthlyCountsBySize('assignment', $months),
            ],
        ];
    }

    /** @return array<string, int> */
    private static function monthlyCounts(string $type, int $months): array
    {
        if ($type === 'assignment') {
            $rows = self::fetchAll(
                "SELECT DATE_FORMAT(ba.assigned_at, '%Y-%m') AS month_key, COUNT(*) AS cnt
                 FROM bin_assignments ba
                 WHERE ba.assigned_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                 GROUP BY month_key",
                [$months]
            );
        } else {
            $rows = self::fetchAll(
                "SELECT DATE_FORMAT(ba.returned_at, '%Y-%m') AS month_key, COUNT(*) AS cnt
                 FROM bin_assignments ba
                 WHERE ba.returned_at IS NOT NULL
                   AND ba.returned_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                 GROUP BY month_key",
                [$months]
            );
        }

        $map = [];
        foreach ($rows as $row) {
            $map[$row['month_key']] = (int)$row['cnt'];
        }
        return $map;
    }

    /** @return array<string, array<string, int>> */
    private static function monthlyCountsBySize(string $type, int $months): array
    {
        if ($type === 'assignment') {
            $rows = self::fetchAll(
                "SELECT d.size, DATE_FORMAT(ba.assigned_at, '%Y-%m') AS month_key, COUNT(*) AS cnt
                 FROM bin_assignments ba
                 JOIN dustbins d ON d.id = ba.dustbin_id
                 WHERE ba.assigned_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                 GROUP BY d.size, month_key",
                [$months]
            );
        } else {
            $rows = self::fetchAll(
                "SELECT d.size, DATE_FORMAT(ba.returned_at, '%Y-%m') AS month_key, COUNT(*) AS cnt
                 FROM bin_assignments ba
                 JOIN dustbins d ON d.id = ba.dustbin_id
                 WHERE ba.returned_at IS NOT NULL
                   AND ba.returned_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                 GROUP BY d.size, month_key",
                [$months]
            );
        }

        $map = ['small' => [], 'medium' => [], 'large' => []];
        foreach ($rows as $row) {
            $map[$row['size']][$row['month_key']] = (int)$row['cnt'];
        }
        return $map;
    }

    /** @return array<string, int> */
    private static function monthlyDamagedCounts(int $months): array
    {
        $rows = self::fetchAll(
            "SELECT DATE_FORMAT(im.created_at, '%Y-%m') AS month_key, COUNT(*) AS cnt
             FROM inventory_movements im
             WHERE im.movement_type IN ('repair', 'loss')
               AND im.created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month_key",
            [$months]
        );

        if (!$rows) {
            $rows = self::fetchAll(
                "SELECT DATE_FORMAT(updated_at, '%Y-%m') AS month_key, COUNT(*) AS cnt
                 FROM dustbins
                 WHERE status = 'damaged'
                   AND updated_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                 GROUP BY month_key",
                [$months]
            );
        }

        $map = [];
        foreach ($rows as $row) {
            $map[$row['month_key']] = (int)$row['cnt'];
        }
        return $map;
    }

    /** @return array<string, int> */
    private static function monthlyUsageTotals(int $months): array
    {
        return self::monthlyCounts('assignment', $months);
    }

    /**
     * @param list<string> $monthKeys
     * @param array<string, int> $data
     * @return list<int>
     */
    private static function mapSeries(array $monthKeys, array $data): array
    {
        $series = [];
        foreach ($monthKeys as $key) {
            $series[] = (int)($data[$key] ?? 0);
        }
        return $series;
    }

    /** @return list<array<string, mixed>> */
    public static function recentMovements(int $limit = 8): array
    {
        try {
            return self::fetchAll(
                "SELECT im.*, d.bin_code, d.size, d.capacity_liters, u.first_name, u.last_name
                 FROM inventory_movements im
                 JOIN dustbins d ON d.id = im.dustbin_id
                 LEFT JOIN users u ON u.id = im.performed_by
                 ORDER BY im.created_at DESC
                 LIMIT ?",
                [$limit]
            );
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function hasTrendData(array $trends): bool
    {
        $series = array_merge(
            $trends['allocations'] ?? [],
            $trends['returns'] ?? [],
            $trends['damaged'] ?? [],
            $trends['usage'] ?? []
        );
        return array_sum(array_map('intval', $series)) > 0;
    }
}
