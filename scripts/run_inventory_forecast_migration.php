<?php
/**
 * Apply smart inventory forecasting migration.
 * Usage: php scripts/run_inventory_forecast_migration.php
 */

require_once __DIR__ . '/../includes/AppConfig.php';
require_once __DIR__ . '/../includes/Model.php';
require_once __DIR__ . '/../models/PricingModel.php';
require_once __DIR__ . '/../models/ProcurementModel.php';

echo "Applying inventory forecast migration...\n";

if (ProcurementModel::ensureTable()) {
    echo "procurement_requests table ready.\n";
} else {
    echo "Warning: could not create procurement_requests table.\n";
}

SettingModel::upsert('inventory_forecast', [
    'enabled'            => true,
    'lookback_days'      => 90,
    'safety_stock_days'  => 30,
    'reorder_multiplier' => 1.5,
    'minimum_by_size'    => ['small' => 20, 'medium' => 20, 'large' => 20],
], null, 'Smart inventory forecasting settings');

echo "Done.\n";
