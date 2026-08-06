<?php
require dirname(__DIR__) . '/includes/AppConfig.php';
require dirname(__DIR__) . '/includes/Model.php';
require dirname(__DIR__) . '/models/UserModel.php';
require dirname(__DIR__) . '/models/SmsMessageModel.php';
require dirname(__DIR__) . '/includes/SmsService.php';

$result = SmsService::send('+233551234567', 'Test SMS from SmartWaste system.', 'registration_welcome', null);
echo "SMS result: " . json_encode($result) . PHP_EOL;
print_r(SmsMessageModel::stats());
