<?php
require_once __DIR__ . '/../includes/AppConfig.php';
require_once __DIR__ . '/../includes/Model.php';
require_once __DIR__ . '/../includes/helpers.php';
foreach (glob(__DIR__ . '/../models/*.php') as $m) {
    require_once $m;
}

echo "Totals: " . json_encode(InventoryForecastModel::totals()) . "\n";
echo "Alerts: " . count(InventoryForecastModel::lowStockAlerts()) . "\n";
foreach (InventoryForecastModel::forecastBySize() as $f) {
    echo $f['label'] . " stock={$f['current_stock']} usage={$f['avg_monthly_usage']} status={$f['status']} limited=" . ($f['limited_data'] ? 'yes' : 'no') . "\n";
}
