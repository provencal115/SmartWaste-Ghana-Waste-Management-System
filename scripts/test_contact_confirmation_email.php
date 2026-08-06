<?php
/**
 * Manual test: php scripts/test_contact_confirmation_email.php your@email.com
 */
require dirname(__DIR__) . '/includes/AppConfig.php';
require dirname(__DIR__) . '/includes/helpers.php';
require dirname(__DIR__) . '/includes/images.php';
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/includes/Mailer.php';

$testEmail = $argv[1] ?? 'test@example.com';

$message = [
    'id'         => 999,
    'full_name'  => 'Test Customer',
    'email'      => $testEmail,
    'phone'      => '+233551234567',
    'subject'    => 'Test enquiry subject',
    'message'    => 'This is a test contact form message.',
];

$ok = Mailer::sendContactCustomerConfirmation($message);
echo $ok ? "Confirmation email sent to {$testEmail}\n" : "Failed to send confirmation email (check error log).\n";
