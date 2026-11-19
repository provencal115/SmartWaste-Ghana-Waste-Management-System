<?php
/**
 * Email + base URL audit (safe; uses admin inbox only unless EMAIL_TEST_TO is set).
 */
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/finalyearproject/index.php';
$_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? 'off';

require dirname(__DIR__) . '/includes/AppConfig.php';
require dirname(__DIR__) . '/includes/Model.php';
require dirname(__DIR__) . '/models/PricingModel.php';
require dirname(__DIR__) . '/models/ContactMessageModel.php';
require dirname(__DIR__) . '/includes/helpers.php';
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/includes/Mailer.php';

$failures = 0;

function auditCheck(string $label, bool $ok): void
{
    global $failures;
    echo ($ok ? 'PASS' : 'FAIL') . ": {$label}\n";
    if (!$ok) {
        $failures++;
    }
}

$adminLink = baseUrl('admin/messages');
auditCheck('Admin messages link has no /scripts/', !str_contains($adminLink, '/scripts'));
auditCheck('Admin link uses index.php router', str_contains($adminLink, '/index.php?url=admin/messages'));

$config = require dirname(__DIR__) . '/config/mail.php';
$local = dirname(__DIR__) . '/config/mail.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}
$pass = $config['smtp']['password'] ?? '';
auditCheck('SMTP password configured (mail.local.php)', $pass !== '');
auditCheck('Admin notify email valid', filter_var($config['contact']['admin_notify_email'] ?? '', FILTER_VALIDATE_EMAIL) !== false);

$message = null;
try {
    if (ContactMessageModel::tableExists()) {
        $message = Model::fetchOne(
            'SELECT * FROM contact_messages ORDER BY id DESC LIMIT 1'
        );
    }
} catch (Throwable) {
}

if ($message) {
    $message['id'] = (int) $message['id'];
    auditCheck('Latest contact message has positive ID', $message['id'] > 0);
    $okAdmin = Mailer::sendContactAdminNotification($message);
    auditCheck('Admin notification send', $okAdmin);
} else {
    echo "SKIP: No contact_messages row for live admin notification test\n";
}

$selfTest = getenv('EMAIL_TEST_TO') ?: ($config['contact']['admin_notify_email'] ?? '');
if ($selfTest && filter_var($selfTest, FILTER_VALIDATE_EMAIL)) {
    $fixture = $message ?: [
        'id' => 1,
        'full_name' => 'Audit Test Customer',
        'email' => $selfTest,
        'phone' => '+233200000000',
        'subject' => 'Audit test',
        'message' => 'Automated audit message body.',
    ];
    $fixture['email'] = $selfTest;
    $okReply = Mailer::sendContactReply($fixture, 'This is an automated audit reply.', 'SmartWaste Support');
    auditCheck('Reply email send to configured test address', $okReply);
}

echo $failures === 0 ? "\nEmail audit completed.\n" : "\n{$failures} check(s) failed.\n";
exit($failures > 0 ? 1 : 0);