<?php
/**
 * Apply chatbot database migration.
 * Usage: php scripts/run_chatbot_migration.php
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../includes/Model.php';

$sqlFile = __DIR__ . '/../database/upgrade_chatbot.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Migration file not found.\n");
    exit(1);
}

echo "Applying chatbot migration...\n";

$sql = file_get_contents($sqlFile);
$sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql) ?: []));

$applied = 0;
foreach ($statements as $statement) {
    if ($statement === '' || str_starts_with($statement, '--')) {
        continue;
    }
    try {
        Model::query($statement);
        $applied++;
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'already exists')) {
            echo "Skipped (already applied): " . substr($statement, 0, 60) . "...\n";
            continue;
        }
        fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
        fwrite(STDERR, "Statement: " . substr($statement, 0, 120) . "...\n");
        exit(1);
    }
}

$count = (int)(Model::fetchOne('SELECT COUNT(*) AS c FROM chatbot_knowledge')['c'] ?? 0);
echo "Done. Applied {$applied} statement(s). Knowledge base entries: {$count}\n";
