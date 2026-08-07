<?php
/**
 * Apply route optimisation database migration.
 * Usage: php scripts/run_route_optimization_migration.php
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../includes/Model.php';

$sqlFile = __DIR__ . '/../database/upgrade_route_optimization.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Migration file not found.\n");
    exit(1);
}

echo "Applying route optimisation migration...\n";

$sql = file_get_contents($sqlFile);
$sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;

// Split on semicolon at end of statement (handles PREPARE blocks)
$statements = preg_split('/;\s*(?=\n|$)/', $sql) ?: [];

foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement === '') {
        continue;
    }
    try {
        Model::query($statement);
        echo "OK: " . substr(str_replace("\n", ' ', $statement), 0, 70) . "...\n";
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'already exists')) {
            echo "Skipped (exists): " . substr($statement, 0, 50) . "...\n";
            continue;
        }
        fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Done.\n";
