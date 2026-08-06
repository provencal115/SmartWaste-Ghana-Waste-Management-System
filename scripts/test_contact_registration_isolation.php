<?php
/**
 * Verify Contact Us emails do not block customer registration.
 * Usage: php scripts/test_contact_registration_isolation.php
 */
require __DIR__ . '/../includes/Model.php';
require __DIR__ . '/../models/UserModel.php';
require __DIR__ . '/../models/ContactMessageModel.php';

$testEmail = 'contact-reg-test-' . time() . '@example.com';

echo "Testing contact/registration isolation for {$testEmail}\n\n";

if (!ContactMessageModel::tableExists()) {
    echo "SKIP: contact_messages table missing\n";
    exit(1);
}

$messageId = ContactMessageModel::create([
    'full_name'  => 'Contact Only Visitor',
    'email'      => $testEmail,
    'phone'      => null,
    'subject'    => 'Registration isolation test',
    'message'    => 'This person only sent a contact message.',
    'ip_address' => '127.0.0.1',
]);

echo "Created contact message #{$messageId}\n";
echo 'isRegisteredEmail (after contact): ' . (UserModel::isRegisteredEmail($testEmail) ? 'BLOCKED (bad)' : 'AVAILABLE (good)') . "\n";
echo 'findByEmail (after contact): ' . (UserModel::findByEmail($testEmail) ? 'FOUND (bad)' : 'NOT FOUND (good)') . "\n";

if (UserModel::isRegisteredEmail($testEmail)) {
    echo "\nFAIL: Contact-only email is treated as registered.\n";
    ContactMessageModel::delete($messageId);
    exit(1);
}

$role = Model::fetchOne("SELECT id FROM roles WHERE name = 'resident'");
$userId = UserModel::create([
    'role_id'             => $role['id'],
    'email'               => UserModel::normalizeEmail($testEmail),
    'password_hash'       => password_hash('TestPass123!', PASSWORD_BCRYPT),
    'first_name'          => 'Contact',
    'last_name'           => 'Tester',
    'phone'               => '',
    'verification_token'  => bin2hex(random_bytes(16)),
]);

echo "\nCreated pending registration user #{$userId}\n";
echo 'isRegisteredEmail (pending, unconfirmed): ' . (UserModel::isRegisteredEmail($testEmail) ? 'BLOCKED (bad)' : 'AVAILABLE (good)') . "\n";

if (UserModel::isRegisteredEmail($testEmail)) {
    echo "\nFAIL: Pending registration should not count as fully registered.\n";
    UserModel::deletePendingRegistration($userId);
    ContactMessageModel::delete($messageId);
    exit(1);
}

UserModel::activate($userId);
Model::query('UPDATE residents SET registration_confirmed = 1 WHERE user_id = ?', [$userId]);

echo 'isRegisteredEmail (after activate): ' . (UserModel::isRegisteredEmail($testEmail) ? 'BLOCKED (good)' : 'AVAILABLE (bad)') . "\n";

if (!UserModel::isRegisteredEmail($testEmail)) {
    echo "\nFAIL: Activated account should be registered.\n";
    UserModel::deletePendingRegistration($userId);
    ContactMessageModel::delete($messageId);
    exit(1);
}

// Cleanup test data
UserModel::deletePendingRegistration($userId);
if (UserModel::findByEmail($testEmail)) {
    Model::query('DELETE FROM users WHERE id = ?', [$userId]);
}
ContactMessageModel::delete($messageId);

echo "\nPASS: Contact messages and completed registrations are isolated correctly.\n";
