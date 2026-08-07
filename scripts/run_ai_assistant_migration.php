<?php
/**
 * Apply AI customer assistant database migration.
 * Usage: php scripts/run_ai_assistant_migration.php
 */

require_once __DIR__ . '/../includes/AppConfig.php';
require_once __DIR__ . '/../includes/Model.php';
require_once __DIR__ . '/../models/PricingModel.php';

$sqlFile = __DIR__ . '/../database/upgrade_ai_assistant.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Migration file not found: {$sqlFile}\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Could not read migration file.\n");
    exit(1);
}

echo "Applying AI assistant migration...\n";

$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
$ok = 0;
foreach ($statements as $stmt) {
    if ($stmt === '' || str_starts_with($stmt, '--')) {
        continue;
    }
    try {
        Model::query($stmt);
        $ok++;
    } catch (Throwable $e) {
        echo "Warning: " . $e->getMessage() . "\n";
    }
}

SettingModel::upsert('ai_assistant', [
    'enabled'         => true,
    'assistant_name'  => 'SmartWaste Assistant',
    'welcome_message' => '',
    'company_info'    => '',
], null, 'Customer-facing AI chatbot assistant');

Model::query(
    "UPDATE chatbot_knowledge SET response = ?, title = 'Escalation fallback'
     WHERE category = 'fallback'",
    ["I'm not able to confirm that information. I can help you contact our support team.\n\nContact Support: {contact_url}\nPhone: {phone}\nEmail: {email}\n\nTry asking about collections, payments, bin sizes, or tap a suggestion below."]
);

Model::query(
    "UPDATE chatbot_knowledge SET keywords = CONCAT(keywords, ',size bins,what size bins,available bins')
     WHERE category = 'bins' AND title = 'Bin sizes' AND keywords NOT LIKE '%size bins%'"
);

$count = (int)(Model::fetchOne('SELECT COUNT(*) AS c FROM chatbot_knowledge')['c'] ?? 0);
echo "Done. Executed {$ok} statement(s). Knowledge base entries: {$count}\n";
