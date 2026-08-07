<?php
/**
 * Simulate controller data loading for key dashboards (no HTTP).
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/AppConfig.php';
require_once __DIR__ . '/../includes/Model.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pages.php';
require_once __DIR__ . '/../includes/stats.php';
require_once __DIR__ . '/../includes/ui.php';
require_once __DIR__ . '/../includes/RouteOptimizer.php';
require_once __DIR__ . '/../includes/ChatbotAiProvider.php';
require_once __DIR__ . '/../includes/ChatbotAccountService.php';
require_once __DIR__ . '/../includes/ChatbotEngine.php';

foreach (glob(__DIR__ . '/../models/*.php') as $m) {
    require_once $m;
}

$checks = [
    'Admin dashboard data' => function () {
        AdminModel::dashboardStats();
        AnalyticsModel::fullReport(AnalyticsModel::parseFilters([]));
        InventoryForecastModel::lowStockAlerts();
    },
    'Inventory dashboard data' => function () {
        ProcurementModel::ensureTable();
        InventoryForecastModel::totals();
        InventoryForecastModel::forecastBySize();
        InventoryForecastModel::trendCharts(6);
        ProcurementModel::stats();
    },
    'Inventory bins data' => function () {
        DustbinModel::all();
        DustbinModel::stats();
    },
    'Inventory reports data' => function () {
        DustbinModel::all();
        DustbinModel::stats();
    },
    'Procurement data' => function () {
        ProcurementModel::all();
        ProcurementModel::stats();
    },
    'Resident data' => function () {
        $u = Model::fetchOne("SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'resident' LIMIT 1");
        if ($u) {
            ResidentModel::getByUserId((int)$u['id']);
        }
    },
    'Collector data' => function () {
        $u = Model::fetchOne("SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'collector' LIMIT 1");
        if ($u) {
            CollectorModel::getByUserId((int)$u['id']);
        }
    },
    'Finance data' => function () {
        PaymentModel::revenueAnalytics();
    },
];

$failed = 0;
foreach ($checks as $name => $fn) {
    try {
        $fn();
        echo "PASS: {$name}\n";
    } catch (Throwable $e) {
        $failed++;
        echo "FAIL: {$name} — {$e->getMessage()}\n";
    }
}

exit($failed > 0 ? 1 : 0);
