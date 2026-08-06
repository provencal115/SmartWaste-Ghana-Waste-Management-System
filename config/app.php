<?php
/**
 * Application configuration
 *
 * Base URL:
 * - Leave `url` as `auto` (default) to detect from the current request (localhost, ngrok, production).
 * - Or set a fixed URL in config/app.local.php, e.g. 'url' => 'https://xxxx.ngrok-free.app/finalyearproject'
 * - Or set the APP_URL environment variable.
 */
return [
    'name' => 'SmartWaste Ghana',

    /** @var string|auto Use 'auto' for dynamic detection, or a full base URL without trailing slash. */
    'url' => 'auto',
    'auto_detect_url' => true,

    'timezone' => 'Africa/Accra',
    'session_timeout' => 3600,
    'upload_path' => __DIR__ . '/../assets/uploads/',
    'roles' => [
        'resident' => 'Resident',
        'collector' => 'Garbage Collector',
        'inventory_manager' => 'Inventory Manager',
        'administrator' => 'Administrator',
        'finance_manager' => 'Finance Manager',
    ],
    'dashboard_routes' => [
        'resident' => 'resident/dashboard',
        'collector' => 'collector/dashboard',
        'inventory_manager' => 'inventory/dashboard',
        'administrator' => 'admin/dashboard',
        'finance_manager' => 'finance/dashboard',
    ],
];
