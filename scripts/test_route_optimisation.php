<?php
require __DIR__ . '/../includes/AppConfig.php';
require __DIR__ . '/../includes/Model.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/pages.php';
require __DIR__ . '/../includes/RouteOptimizer.php';
require __DIR__ . '/../models/PricingModel.php';
require __DIR__ . '/../models/CollectionModel.php';
require __DIR__ . '/../models/RouteModel.php';
require __DIR__ . '/../models/DustbinModel.php';
require __DIR__ . '/../models/OptimizedRouteModel.php';

$date = date('Y-m-d');
$zone = Model::fetchOne('SELECT id, name FROM collection_zones WHERE name = ?', ['East Legon']);
$collector = Model::fetchOne('SELECT c.id FROM collectors c JOIN users u ON c.user_id = u.id WHERE u.email = ?', ['collector@smartwaste.gh']);
$truck = Model::fetchOne('SELECT id FROM trucks WHERE plate_number = ?', ['SW-002']);

if (!$zone || !$collector) {
    echo "Missing zone or collector\n";
    exit(1);
}

$count = count(CollectionModel::scheduledForOptimisation($date, (int)$zone['id'], (int)$collector['id']));
echo "Scheduled for {$zone['name']} on {$date}: {$count}\n";

if ($count === 0) {
    echo "No schedules for today in East Legon — try a future date from seed data.\n";
    exit(0);
}

try {
    $route = OptimizedRouteModel::optimise($date, (int)$zone['id'], (int)$collector['id'], $truck ? (int)$truck['id'] : null, 1, true);
    echo "Optimised route #{$route['id']}: {$route['total_stops']} stops, {$route['estimated_distance_km']} km\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
