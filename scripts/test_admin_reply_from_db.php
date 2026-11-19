<?php
/**
 * Simulate admin reply using the latest contact message row (visitor email from DB).
 * php scripts/test_admin_reply_from_db.php [message_id]
 */
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/finalyearproject/index.php';

require dirname(__DIR__) . '/includes/AppConfig.php';
require dirname(__DIR__) . '/includes/Model.php';
require dirname(__DIR__) . '/models/PricingModel.php';
require dirname(__DIR__) . '/models/ContactMessageModel.php';
require dirname(__DIR__) . '/includes/helpers.php';
require dirname(__DIR__) . '/includes/images.php';
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/includes/Mailer.php';

$id = isset($argv[1]) ? (int) $argv[1] : 0;
if ($id <= 0) {
    $row = ContactMessageModel::fetchOne('SELECT id FROM contact_messages ORDER BY id DESC LIMIT 1');
    $id = (int) ($row['id'] ?? 0);
}
if ($id <= 0) {
    fwrite(STDERR, "No contact messages in database.\n");
    exit(1);
}

$contactRow = ContactMessageModel::find($id);
$message = ContactMessageModel::findWithReplies($id);
if (!$contactRow || !$message) {
    fwrite(STDERR, "Message #{$id} not found.\n");
    exit(1);
}

$visitorEmail = trim($contactRow['email'] ?? '');
echo "Message #{$id} visitor: {$visitorEmail}\n";

$body = 'This is an automated admin reply test at ' . date('Y-m-d H:i:s') . '.';
$ok = Mailer::sendContactReply($message, $body, 'Dashboard Admin Test');

if ($ok) {
    echo "SMTP accepted reply to {$visitorEmail}\n";
    exit(0);
}

echo "Failed: " . (Mailer::lastSmtpError() ?? 'unknown') . "\n";
exit(1);