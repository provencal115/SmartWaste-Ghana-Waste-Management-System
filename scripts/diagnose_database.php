<?php
/**
 * Diagnose SmartWaste database health (read-only).
 * Usage: php scripts/diagnose_database.php
 */
require __DIR__ . '/../includes/AppConfig.php';
require __DIR__ . '/../includes/Model.php';

echo "SmartWaste database diagnosis\n\n";

try {
    $db = Model::fetchOne('SELECT DATABASE() AS db');
    echo 'Connected database: ' . ($db['db'] ?? 'unknown') . "\n";
} catch (Throwable $e) {
    echo "FAIL: Cannot connect — {$e->getMessage()}\n";
    exit(1);
}

$tables = [
    'users', 'roles', 'residents', 'payments', 'contact_messages', 'dustbins', 'collection_schedules',
];

$broken = 0;
foreach ($tables as $table) {
    try {
        Model::fetchOne("SELECT 1 FROM {$table} LIMIT 1");
        echo "OK: {$table}\n";
    } catch (Throwable $e) {
        echo "BROKEN: {$table} — {$e->getMessage()}\n";
        $broken++;
    }
}

if ($broken === 0) {
    echo "\nDatabase tables are reachable.\n";
    exit(0);
}

echo "\n";
echo "{$broken} critical table(s) failed.\n";
echo "This is usually MariaDB/MySQL error 1932 (\"doesn't exist in engine\") after XAMPP\n";
echo "was stopped incorrectly, a version change, or InnoDB dictionary corruption.\n\n";
echo "Your .ibd files may still exist under C:\\xampp\\mysql\\data\\smart_waste_db — back up that\n";
echo "folder before any repair.\n\n";
echo "Typical XAMPP recovery (run in MySQL yourself after backup):\n";
echo "  1. Copy C:\\xampp\\mysql\\data\\smart_waste_db to a backup location.\n";
echo "  2. DROP DATABASE smart_waste_db;\n";
echo "  3. Import: mysql -u root < database/schema.sql\n";
echo "  4. Re-run upgrade SQL/PHP scripts in database/ and scripts/ as needed.\n";
echo "  5. Re-create your real users/data via registration or seeds.\n\n";
echo "Contact form and PHP code can work again once the database is healthy.\n";

exit(1);
