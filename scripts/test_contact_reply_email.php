<?php
/**
 * Test admin contact reply email: php scripts/test_contact_reply_email.php recipient@example.com
 */
require dirname(__DIR__) . '/includes/AppConfig.php';
require dirname(__DIR__) . '/includes/Model.php';
require dirname(__DIR__) . '/models/PricingModel.php';
require dirname(__DIR__) . '/includes/images.php';
require dirname(__DIR__) . '/includes/helpers.php';
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/includes/Mailer.php';

$testEmail = $argv[1] ?? '';

if ($testEmail === '' || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/test_contact_reply_email.php recipient@example.com\n");
    exit(1);
}

$message = [
    'id'         => 1,
    'full_name'  => 'Test Customer',
    'email'      => $testEmail,
    'subject'    => 'Test enquiry',
    'message'    => 'Original customer message.',
];

$ok = Mailer::sendContactReply($message, 'Thank you for your message. This is a test reply from SmartWaste.', 'System Administrator');
echo $ok ? "Reply email sent to {$testEmail}\n" : "Failed — check PHP error log and system_logs for contact_reply_failed.\n";
exit($ok ? 0 : 1);