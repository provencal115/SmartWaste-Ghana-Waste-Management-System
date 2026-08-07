<?php
/**
 * Backfill GPS coordinates for residents (demo + existing without GPS).
 * Usage: php scripts/seed_resident_gps.php
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../includes/AppConfig.php';
require __DIR__ . '/../includes/Model.php';
require __DIR__ . '/../includes/pages.php';
require __DIR__ . '/../includes/RouteOptimizer.php';

echo "Seeding resident GPS coordinates...\n";

$zoneCoords = [
    'Accra Central'    => [5.5500, -0.2057],
    'East Legon'       => [5.6350, -0.1570],
    'Madina'           => [5.6833, -0.1667],
    'Tema'             => [5.6698, -0.0167],
    'Tema Community 1' => [5.6550, -0.0100],
    'Kumasi Central'   => [6.6885, -1.6244],
    'Cape Coast'       => [5.1053, -1.2466],
];

$residents = Model::fetchAll(
    'SELECT r.id, r.address, r.gps_lat, r.gps_lng, cz.name AS zone_name
     FROM residents r
     LEFT JOIN collection_zones cz ON r.zone_id = cz.id'
);

$updated = 0;
foreach ($residents as $r) {
    if (!empty($r['gps_lat']) && !empty($r['gps_lng']) && (float)$r['gps_lat'] != 0.0) {
        continue;
    }

    $zoneName = $r['zone_name'] ?? 'Accra Central';
    $depot = RouteOptimizer::zoneDepot($zoneName);
    $coords = RouteOptimizer::resolveCoordinates($r, $depot);

    Model::query(
        'UPDATE residents SET gps_lat = ?, gps_lng = ? WHERE id = ?',
        [$coords['lat'], $coords['lng'], $r['id']]
    );
    $updated++;
}

echo "Updated GPS for {$updated} residents.\n";
