<?php

require_once __DIR__ . '/config/cors.php';
setupCORS();

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('/finalyearproject/backend/', '', $path);
$path = str_replace('/backend/', '', $path);
$path = trim($path, '/');

$routes = [
    'api/auth' => __DIR__ . '/api/auth.php',
    'api/auth.php' => __DIR__ . '/api/auth.php',
    'api/residents' => __DIR__ . '/api/residents.php',
    'api/residents.php' => __DIR__ . '/api/residents.php',
    'api/collectors' => __DIR__ . '/api/collectors.php',
    'api/collectors.php' => __DIR__ . '/api/collectors.php',
    'api/inventory' => __DIR__ . '/api/inventory.php',
    'api/inventory.php' => __DIR__ . '/api/inventory.php',
    'api/admin' => __DIR__ . '/api/admin.php',
    'api/admin.php' => __DIR__ . '/api/admin.php',
    'api/finance' => __DIR__ . '/api/finance.php',
    'api/finance.php' => __DIR__ . '/api/finance.php',
    'api/notifications' => __DIR__ . '/api/notifications.php',
    'api/notifications.php' => __DIR__ . '/api/notifications.php',
    'api/reports' => __DIR__ . '/api/reports.php',
    'api/reports.php' => __DIR__ . '/api/reports.php',
];

if (isset($routes[$path])) {
    require $routes[$path];
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
}
