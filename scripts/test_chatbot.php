<?php
require_once __DIR__ . '/../includes/AppConfig.php';
require_once __DIR__ . '/../includes/Model.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pages.php';
foreach (glob(__DIR__ . '/../models/*.php') as $m) {
    require_once $m;
}
require_once __DIR__ . '/../includes/ChatbotAiProvider.php';
require_once __DIR__ . '/../includes/ChatbotAccountService.php';
require_once __DIR__ . '/../includes/ChatbotEngine.php';

$engine = new ChatbotEngine();

echo "Enabled: " . ($engine->isEnabled() ? 'yes' : 'no') . "\n";
echo "Name: " . $engine->assistantName() . "\n\n";

$r1 = $engine->reply('What size bins are available?', null);
echo "General: " . substr($r1['response'], 0, 80) . "... [{$r1['source']}]\n";

$r2 = $engine->reply('xyzzy unknown question 12345', null);
echo "Fallback escalate: " . ($r2['escalate'] ? 'yes' : 'no') . " [{$r2['source']}]\n";

$residents = Model::fetchAll(
    "SELECT u.id, u.first_name, u.role_id, r.name AS role_name
     FROM users u JOIN roles r ON u.role_id = r.id
     WHERE r.name = 'resident' LIMIT 1"
);
if ($residents) {
    $user = $residents[0];
    $user['role_name'] = 'resident';
    $r3 = $engine->reply('When is my next collection?', $user);
    echo "\nResident collection: " . substr($r3['response'], 0, 120) . "... [{$r3['source']}]\n";
}
