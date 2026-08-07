<?php
require __DIR__ . '/../includes/AppConfig.php';
require __DIR__ . '/../includes/Model.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/stats.php';
require __DIR__ . '/../models/CollectionModel.php';
require __DIR__ . '/../models/AnalyticsModel.php';

$report = AnalyticsModel::fullReport(AnalyticsModel::parseFilters([]));
$op = $report['operational'];
$perf = $report['performance'];

echo "Operational Intelligence Test\n";
echo "Registered: {$op['registered_residents']}\n";
echo "Total Collections: {$op['total_collections']}\n";
echo "Completion Rate: {$perf['completion_rate']}%\n";
echo "On-Time Rate: {$perf['on_time_rate']}%\n";
echo "Avg Rating: {$report['satisfaction']['average']}\n";
echo "Zones: " . count($report['zones']) . "\n";
echo "Vehicles: " . count($report['vehicles']) . "\n";
echo "Collectors: " . count($report['collectors']) . "\n";
echo "Export rows: " . count(AnalyticsModel::exportRows($report)) . "\n";
