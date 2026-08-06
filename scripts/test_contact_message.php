<?php
require dirname(__DIR__) . '/includes/AppConfig.php';
require dirname(__DIR__) . '/includes/Model.php';
require dirname(__DIR__) . '/models/ContactMessageModel.php';

$id = ContactMessageModel::create([
    'full_name'  => 'Test User',
    'email'      => 'test@example.com',
    'phone'      => '+233200000000',
    'subject'    => 'Test Subject',
    'message'    => 'Test message body',
    'ip_address' => '127.0.0.1',
]);

echo "Created message #{$id}\n";
print_r(ContactMessageModel::stats());
