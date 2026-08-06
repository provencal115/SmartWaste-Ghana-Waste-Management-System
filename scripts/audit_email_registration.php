<?php
require __DIR__ . '/../includes/Model.php';
require __DIR__ . '/../models/UserModel.php';
require __DIR__ . '/../models/ContactMessageModel.php';

$testEmail = $argv[1] ?? '';

echo "=== Email registration audit ===\n\n";

try {
    $users = Model::fetchAll('SELECT id, email, is_active, first_name, last_name, created_at FROM users ORDER BY created_at DESC LIMIT 30');
    echo "USERS (recent " . count($users) . "):\n";
    foreach ($users as $u) {
        echo "  #{$u['id']} {$u['email']} active={$u['is_active']} ({$u['first_name']} {$u['last_name']}) @ {$u['created_at']}\n";
    }
} catch (Throwable $e) {
    echo "Users query failed: {$e->getMessage()}\n";
}

echo "\n";

try {
    $contacts = Model::fetchAll('SELECT id, email, full_name, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 30');
    echo "CONTACT MESSAGES (recent " . count($contacts) . "):\n";
    foreach ($contacts as $c) {
        echo "  #{$c['id']} {$c['email']} ({$c['full_name']}) @ {$c['created_at']}\n";
    }
} catch (Throwable $e) {
    echo "Contact messages query failed: {$e->getMessage()}\n";
}

if ($testEmail !== '') {
    echo "\n--- Lookup: {$testEmail} ---\n";
    $user = UserModel::findByEmail($testEmail);
    echo 'UserModel::findByEmail: ' . ($user ? "FOUND user #{$user['id']}" : 'NOT FOUND') . "\n";
    echo 'UserModel::isRegisteredEmail: ' . (UserModel::isRegisteredEmail($testEmail) ? 'REGISTERED' : 'NOT REGISTERED') . "\n";
    try {
        $cm = Model::fetchOne('SELECT id, full_name FROM contact_messages WHERE email = ? LIMIT 1', [$testEmail]);
        echo 'contact_messages: ' . ($cm ? "FOUND message #{$cm['id']}" : 'NOT FOUND') . "\n";
    } catch (Throwable $e) {
        echo 'contact_messages lookup failed: ' . $e->getMessage() . "\n";
    }
}

// Cross-check: emails in contact_messages that also exist in users
echo "\n--- Emails in BOTH contact_messages AND users ---\n";
try {
    $overlap = Model::fetchAll(
        'SELECT cm.email, cm.full_name AS contact_name, u.id AS user_id, u.is_active, u.first_name, u.last_name
         FROM contact_messages cm
         INNER JOIN users u ON LOWER(TRIM(u.email)) = LOWER(TRIM(cm.email))
         GROUP BY cm.email, cm.full_name, u.id, u.is_active, u.first_name, u.last_name
         ORDER BY cm.email'
    );
    if ($overlap === []) {
        echo "  (none)\n";
    } else {
        foreach ($overlap as $row) {
            echo "  {$row['email']} — contact: {$row['contact_name']}, user #{$row['user_id']} ({$row['first_name']} {$row['last_name']}) active={$row['is_active']}\n";
        }
    }
} catch (Throwable $e) {
    echo "Overlap query failed: {$e->getMessage()}\n";
}
