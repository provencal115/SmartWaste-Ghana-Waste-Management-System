<?php
/**
 * Smoke-test welcome email delivery.
 * Run: php scripts/test_welcome_email.php recipient@example.com
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/images.php';
require_once __DIR__ . '/../includes/Model.php';
require_once __DIR__ . '/../models/ActivityModel.php';

foreach (glob(__DIR__ . '/../models/*.php') as $model) {
    require_once $model;
}

if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
require_once __DIR__ . '/../includes/Mailer.php';

$email = $argv[1] ?? '';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/test_welcome_email.php recipient@example.com\n");
    exit(1);
}

$sent = Mailer::sendWelcomeEmail([
    'id'         => 0,
    'email'      => $email,
    'first_name' => 'Test',
    'last_name'  => 'Resident',
]);

echo $sent ? "Welcome email sent to {$email}\n" : "Failed to send welcome email — check config/mail.local.php and PHP error log.\n";
exit($sent ? 0 : 1);
