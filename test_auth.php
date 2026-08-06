<?php
/**
 * CLI auth verification — run: php test_auth.php
 * Delete after verification in production.
 */
require __DIR__ . '/includes/Model.php';
require __DIR__ . '/models/UserModel.php';
require __DIR__ . '/models/DustbinModel.php';

$config = require __DIR__ . '/config/app.php';
$accounts = [
    'admin@smartwaste.gh' => 'administrator',
    'finance@smartwaste.gh' => 'finance_manager',
    'inventory@smartwaste.gh' => 'inventory_manager',
    'collector@smartwaste.gh' => 'collector',
];

$passed = 0;
$failed = 0;

foreach ($accounts as $email => $expectedRole) {
    $u = UserModel::findByEmail($email);
    if (!$u) {
        echo "FAIL: $email — user not found\n";
        $failed++;
        continue;
    }
    if (!password_verify('password', $u['password_hash'])) {
        echo "FAIL: $email — password hash invalid\n";
        $failed++;
        continue;
    }
    if ($u['role_name'] !== $expectedRole) {
        echo "FAIL: $email — role is '{$u['role_name']}', expected '$expectedRole'\n";
        $failed++;
        continue;
    }
    if (!$u['is_active']) {
        echo "FAIL: $email — account inactive\n";
        $failed++;
        continue;
    }
    $route = $config['dashboard_routes'][$u['role_name']] ?? null;
    if (!$route) {
        echo "FAIL: $email — no dashboard route configured\n";
        $failed++;
        continue;
    }
    if ($expectedRole === 'collector') {
        $c = CollectorModel::ensureForUser((int)$u['id']);
        if (!$c) {
            echo "FAIL: $email — collector profile missing and could not be created\n";
            $failed++;
            continue;
        }
    }
    echo "OK: $email → role={$u['role_name']} → $route\n";
    $passed++;
}

echo "\nResult: $passed passed, $failed failed\n";
exit($failed > 0 ? 1 : 0);
