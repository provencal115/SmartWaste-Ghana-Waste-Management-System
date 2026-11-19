<?php
/**
 * Test admin notification for new contact messages:
 * php scripts/test_contact_admin_notify.php
 */
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/finalyearproject/index.php';
$_SERVER['HTTPS'] = 'off';

require dirname(__DIR__) . '/includes/AppConfig.php';
require dirname(__DIR__) . '/includes/Model.php';
require dirname(__DIR__) . '/models/PricingModel.php';
require dirname(__DIR__) . '/includes/helpers.php';
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/includes/Mailer.php';

$config = require dirname(__DIR__) . '/config/mail.php';
$local = dirname(__DIR__) . '/config/mail.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

$target = trim($config['contact']['admin_notify_email'] ?? '');
echo "admin_notify_email: {$target}\n";

if ($target === '' || !filter_var($target, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid admin_notify_email\n");
    exit(1);
}

require dirname(__DIR__) . '/models/ContactMessageModel.php';

$message = ContactMessageModel::tableExists()
    ? Model::fetchOne('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 1')
    : null;

if (!$message) {
    $message = [
        'id'         => 1,
        'full_name'  => 'Test Customer',
        'email'      => 'customer.example@example.com',
        'phone'      => '+233200000000',
        'subject'    => 'Test contact notification',
        'message'    => 'This is a test of the customer-to-admin notification email.',
    ];
}

echo 'Message ID: ' . (int) ($message['id'] ?? 0) . "\n";
echo 'Dashboard link: ' . baseUrl('admin/messages') . '&id=' . (int) ($message['id'] ?? 0) . "\n";

$ok = Mailer::sendContactAdminNotification($message);
echo $ok
    ? "Admin notification sent to {$target}\n"
    : "Failed to send admin notification — check PHP error log.\n";
exit($ok ? 0 : 1);