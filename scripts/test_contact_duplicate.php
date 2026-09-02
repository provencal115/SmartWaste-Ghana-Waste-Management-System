<?php
/**
 * Verify contact form one-time submission token protection.
 */
require dirname(__DIR__) . '/includes/AppConfig.php';
require dirname(__DIR__) . '/includes/Auth.php';
require dirname(__DIR__) . '/includes/Csrf.php';
require dirname(__DIR__) . '/includes/helpers.php';
require dirname(__DIR__) . '/includes/Model.php';
require dirname(__DIR__) . '/models/ContactMessageModel.php';

Auth::start();
$_SESSION = [];

$failures = 0;

function assertContact(string $label, bool $ok): void
{
    global $failures;
    echo ($ok ? 'OK' : 'FAIL') . ": {$label}\n";
    if (!$ok) {
        $failures++;
    }
}

Auth::start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$field = Csrf::submissionField('contact');
preg_match('/value="([^"]+)"/', $field, $m);
$token = $m[1] ?? '';

assertContact('submission token issued', $token !== '');
assertContact('first submission valid', Csrf::validateSubmission('contact', $token) === 'valid');
assertContact('second submission duplicate', Csrf::validateSubmission('contact', $token) === 'duplicate');
assertContact('unknown token invalid', Csrf::validateSubmission('contact', bin2hex(random_bytes(8))) === 'invalid');

if (ContactMessageModel::tableExists()) {
    $before = ContactMessageModel::stats()['total'];
    $id = ContactMessageModel::create([
        'full_name'  => 'Duplicate Test',
        'email'      => 'dup-test@example.com',
        'phone'      => null,
        'subject'    => 'Token test ' . time(),
        'message'    => 'Single insert verification',
        'ip_address' => '127.0.0.1',
    ]);
    $after = ContactMessageModel::stats()['total'];
    assertContact('single insert increments count by one', $after === $before + 1 && $id > 0);
    ContactMessageModel::delete($id);
} else {
    echo "SKIP: contact_messages table unavailable\n";
}

echo $failures === 0 ? "\nAll contact submission checks passed.\n" : "\n{$failures} check(s) failed.\n";
exit($failures > 0 ? 1 : 0);
