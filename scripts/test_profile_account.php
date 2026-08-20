<?php
require_once __DIR__ . '/../includes/AppConfig.php';
require_once __DIR__ . '/../includes/Model.php';
require_once __DIR__ . '/../includes/helpers.php';

foreach (glob(__DIR__ . '/../models/*.php') as $m) {
    require_once $m;
}

$failed = 0;

function assertTest(bool $ok, string $label): void
{
    global $failed;
    echo ($ok ? 'PASS' : 'FAIL') . ": {$label}\n";
    if (!$ok) {
        $failed++;
    }
}

$parsed = parseFullName('John Smith');
assertTest($parsed['ok'] && $parsed['first_name'] === 'John' && $parsed['last_name'] === 'Smith', 'parseFullName two words');

$parsed = parseFullName('Admin');
assertTest($parsed['ok'] && $parsed['first_name'] === 'Admin' && $parsed['last_name'] === '', 'parseFullName single word');

$parsed = parseFullName('A');
assertTest(!$parsed['ok'], 'parseFullName too short');

assertTest(formatUserFullName(['first_name' => 'John', 'last_name' => 'Smith']) === 'John Smith', 'formatUserFullName');
assertTest(formatUserFullName(['first_name' => 'Admin', 'last_name' => '']) === 'Admin', 'formatUserFullName single');

$user = Model::fetchOne("SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = 'admin@smartwaste.gh' LIMIT 1");
if ($user) {
    assertTest(password_verify('password', $user['password_hash']), 'admin password hash verifies');
} else {
    echo "SKIP: admin user not in database\n";
}

exit($failed > 0 ? 1 : 0);
