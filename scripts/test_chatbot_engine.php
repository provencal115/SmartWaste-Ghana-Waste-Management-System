<?php
require __DIR__ . '/../includes/AppConfig.php';
require __DIR__ . '/../includes/Model.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/pages.php';
require __DIR__ . '/../models/ChatbotModel.php';
require __DIR__ . '/../includes/ChatbotEngine.php';

$engine = new ChatbotEngine();
$tests = [
    'Hello',
    'How do I register?',
    'Mobile Money payment',
    'What bin sizes are available?',
    'xyz unknown question',
];

foreach ($tests as $q) {
    $r = $engine->reply($q);
    echo "Q: {$q}\n";
    echo 'Matched: ' . ($r['matched'] ? 'yes' : 'no') . ' | Category: ' . ($r['category'] ?? '-') . "\n";
    echo mb_strimwidth($r['response'], 0, 120, '…') . "\n\n";
}

echo 'Knowledge entries: ' . count(ChatbotModel::allKnowledge()) . "\n";
echo 'Suggestions: ' . count($engine->suggestions()) . "\n";
