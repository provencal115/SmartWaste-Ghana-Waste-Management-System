<?php
/**
 * Nearest-neighbour route optimiser using straight-line (Haversine) distance.
 * Structured for future swap-in of a road-network routing API.
 */
class RouteOptimizer
{
    /** Average road factor applied to straight-line distance (estimate only). */
    private const ROAD_FACTOR = 1.35;

    /** Assumed average travel speed for duration estimates (km/h). */
    private const AVG_SPEED_KMH = 28;

    /** Minutes per stop (collection service time). */
    private const MINUTES_PER_STOP = 8;

    /** @var array<string, array{lat: float, lng: float, label: string}> */
    private static array $zoneCenters = [
        'accra central' => ['lat' => 5.5500, 'lng' => -0.2057, 'label' => 'Accra Central Depot'],
        'east legon'    => ['lat' => 5.6350, 'lng' => -0.1570, 'label' => 'East Legon Depot'],
        'madina'        => ['lat' => 5.6833, 'lng' => -0.1667, 'label' => 'Madina Depot'],
        'tema'          => ['lat' => 5.6698, 'lng' => -0.0167, 'label' => 'Tema Depot'],
        'tema community 1' => ['lat' => 5.6550, 'lng' => -0.0100, 'label' => 'Tema Community 1 Depot'],
        'kumasi central' => ['lat' => 6.6885, 'lng' => -1.6244, 'label' => 'Kumasi Central Depot'],
        'cape coast'    => ['lat' => 5.1053, 'lng' => -1.2466, 'label' => 'Cape Coast Depot'],
    ];

    /**
     * @param list<array<string, mixed>> $schedules Rows with resident/schedule fields
     * @return array<string, mixed>
     */
    public static function optimize(array $schedules, string $zoneName, string $algorithm = 'nearest_neighbor'): array
    {
        $depot = self::zoneDepot($zoneName);
        $stops = [];

        foreach ($schedules as $row) {
            $coords = self::resolveCoordinates($row, $depot);
            $stops[] = [
                'schedule_id'   => (int)$row['id'],
                'resident_id'   => (int)$row['resident_id'],
                'customer_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                'address'       => trim(($row['address'] ?? '') . ($row['city'] ? ', ' . $row['city'] : '')),
                'bin_size'      => $row['assigned_bin_size'] ?? $row['selected_bin_size'] ?? 'medium',
                'preferred_time'=> $row['preferred_time'] ?? null,
                'pickup_status' => $row['pickup_status'] ?? 'pending',
                'priority'      => self::stopPriority($row),
                'lat'           => $coords['lat'],
                'lng'           => $coords['lng'],
                'location_source' => $coords['source'],
                'location_label'  => $coords['label'],
            ];
        }

        $ordered = match ($algorithm) {
            'cluster_first' => self::clusterFirst($stops, $depot),
            default         => self::nearestNeighbor($stops, $depot),
        };

        $totalDistance = 0.0;
        $prev = $depot;
        foreach ($ordered as $i => &$stop) {
            $stop['order'] = $i + 1;
            $dist = self::haversineKm($prev['lat'], $prev['lng'], $stop['lat'], $stop['lng']);
            $stop['distance_from_prev_km'] = round($dist, 2);
            $totalDistance += $dist;
            $prev = $stop;
        }
        unset($stop);

        $returnDist = self::haversineKm($prev['lat'], $prev['lng'], $depot['lat'], $depot['lng']);
        $totalDistance += $returnDist;
        $roadDistance = round($totalDistance * self::ROAD_FACTOR, 2);

        $travelMinutes = (int)round(($roadDistance / self::AVG_SPEED_KMH) * 60);
        $serviceMinutes = count($ordered) * self::MINUTES_PER_STOP;
        $durationMinutes = $travelMinutes + $serviceMinutes;

        return [
            'algorithm'              => $algorithm,
            'distance_method'        => 'haversine_straight_line',
            'distance_note'          => 'Distances use straight-line Haversine estimates with a road factor — not live road-network routing.',
            'depot'                  => $depot,
            'stops'                  => $ordered,
            'total_stops'            => count($ordered),
            'estimated_distance_km'  => $roadDistance,
            'estimated_duration_min' => $durationMinutes,
            'start_lat'              => $depot['lat'],
            'start_lng'              => $depot['lng'],
            'end_lat'                => $depot['lat'],
            'end_lng'                => $depot['lng'],
            'return_distance_km'     => round($returnDist * self::ROAD_FACTOR, 2),
            'optimized_at'           => date('c'),
        ];
    }

    /** @return array{lat: float, lng: float, label: string} */
    public static function zoneDepot(string $zoneName): array
    {
        $key = strtolower(trim($zoneName));
        if (isset(self::$zoneCenters[$key])) {
            return self::$zoneCenters[$key];
        }

        $info = companyInfo();
        return [
            'lat'   => (float)($info['map_lat'] ?? 5.6037),
            'lng'   => (float)($info['map_lng'] ?? -0.1870),
            'label' => 'SmartWaste Depot — ' . $zoneName,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array{lat: float, lng: float, source: string, label: string}
     */
    public static function resolveCoordinates(array $row, array $depot): array
    {
        $lat = $row['gps_lat'] ?? null;
        $lng = $row['gps_lng'] ?? null;

        if ($lat !== null && $lng !== null && (float)$lat != 0.0 && (float)$lng != 0.0) {
            return [
                'lat'    => (float)$lat,
                'lng'    => (float)$lng,
                'source' => 'gps',
                'label'  => 'GPS coordinates',
            ];
        }

        $address = trim(($row['address'] ?? '') . ' ' . ($row['city'] ?? ''));
        $hash = crc32($address . ($row['resident_id'] ?? $row['id'] ?? ''));
        $latOffset = (($hash & 0xFF) - 128) / 6000.0;
        $lngOffset = ((($hash >> 8) & 0xFF) - 128) / 6000.0;

        return [
            'lat'    => round($depot['lat'] + $latOffset, 6),
            'lng'    => round($depot['lng'] + $lngOffset, 6),
            'source' => 'address_estimate',
            'label'  => $address !== '' ? 'Estimated from address (GPS unavailable)' : 'Zone estimate (no GPS or address)',
        ];
    }

    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** @param list<array<string, mixed>> $stops */
    private static function nearestNeighbor(array $stops, array $depot): array
    {
        if (count($stops) <= 1) {
            return $stops;
        }

        $remaining = $stops;
        $ordered = [];
        $current = $depot;

        while ($remaining) {
            $bestIdx = 0;
            $bestScore = PHP_FLOAT_MAX;

            foreach ($remaining as $idx => $stop) {
                $dist = self::haversineKm($current['lat'], $current['lng'], $stop['lat'], $stop['lng']);
                $score = $dist - ($stop['priority'] * 0.05);
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestIdx = $idx;
                }
            }

            $next = $remaining[$bestIdx];
            unset($remaining[$bestIdx]);
            $remaining = array_values($remaining);
            $ordered[] = $next;
            $current = $next;
        }

        return $ordered;
    }

    /** @param list<array<string, mixed>> $stops */
    private static function clusterFirst(array $stops, array $depot): array
    {
        usort($stops, function ($a, $b) use ($depot) {
            $da = self::haversineKm($depot['lat'], $depot['lng'], $a['lat'], $a['lng']);
            $db = self::haversineKm($depot['lat'], $depot['lng'], $b['lat'], $b['lng']);
            return $da <=> $db;
        });

        return self::nearestNeighbor($stops, $depot);
    }

    /** @param array<string, mixed> $row */
    private static function stopPriority(array $row): int
    {
        $priority = 0;
        if (($row['pickup_status'] ?? '') === 'delayed') {
            $priority += 3;
        }
        if (!empty($row['preferred_time'])) {
            $hour = (int)date('G', strtotime((string)$row['preferred_time']));
            if ($hour <= 9) {
                $priority += 2;
            }
        }
        if (($row['schedule_type'] ?? '') === 'one_time') {
            $priority += 1;
        }
        return $priority;
    }

    public static function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $m > 0 ? "{$h} hr {$m} min" : "{$h} hr";
    }
}
