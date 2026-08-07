<?php
/**
 * Regression smoke test — simulates key controller paths.
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

$errors = [];
$passed = [];

function test(string $name, callable $fn): void
{
    global $errors, $passed;
    try {
        $fn();
        $passed[] = $name;
        echo "PASS: {$name}\n";
    } catch (Throwable $e) {
        $errors[] = "{$name}: " . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine();
        echo "FAIL: {$name} — " . $e->getMessage() . "\n";
    }
}

// DB tables
test('table dustbins', fn() => Model::fetchOne('SELECT COUNT(*) AS c FROM dustbins'));
test('table bin_assignments', fn() => Model::fetchOne('SELECT COUNT(*) AS c FROM bin_assignments'));
test('table inventory_movements', fn() => Model::fetchOne('SELECT COUNT(*) AS c FROM inventory_movements'));
test('table inventory_thresholds', fn() => Model::fetchOne('SELECT COUNT(*) AS c FROM inventory_thresholds'));
test('table procurement_requests', function () {
    $ready = ProcurementModel::isAvailable();
    echo $ready ? ' (exists)' : ' (missing — graceful fallback active)';
});
test('table smart_settings', fn() => Model::fetchOne('SELECT COUNT(*) AS c FROM smart_settings'));

test('DustbinModel::stats', fn() => DustbinModel::stats());
test('DustbinModel::all', fn() => DustbinModel::all());
test('DustbinModel::lowStockAlerts', fn() => DustbinModel::lowStockAlerts());
test('InventoryForecastModel::totals', fn() => InventoryForecastModel::totals());
test('InventoryForecastModel::lifecycleBySize', fn() => InventoryForecastModel::lifecycleBySize());
test('InventoryForecastModel::forecastBySize', fn() => InventoryForecastModel::forecastBySize());
test('InventoryForecastModel::trendCharts', fn() => InventoryForecastModel::trendCharts(6));
test('InventoryForecastModel::recentMovements', fn() => InventoryForecastModel::recentMovements(3));
test('ProcurementModel::stats (graceful)', function () {
    $stats = ProcurementModel::stats();
    if (!is_array($stats) || !isset($stats['pending'])) {
        throw new RuntimeException('Invalid procurement stats shape');
    }
});
test('ProcurementModel::all (graceful)', fn() => ProcurementModel::all());
test('AdminModel::dashboardStats', fn() => AdminModel::dashboardStats());
test('AnalyticsModel::fullReport', fn() => AnalyticsModel::fullReport(AnalyticsModel::parseFilters([])));
test('ChatbotEngine construct', fn() => new ChatbotEngine());
test('SettingModel::get inventory_forecast', fn() => SettingModel::get('inventory_forecast'));

echo "\n=== SUMMARY ===\n";
echo count($passed) . ' passed, ' . count($errors) . " failed\n";
if ($errors) {
    foreach ($errors as $e) echo "  - {$e}\n";
    exit(1);
}
