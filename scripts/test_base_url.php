<?php
declare(strict_types=1);

$_SERVER['HTTPS'] = 'off';
$_SERVER['HTTP_HOST'] = 'abc123.ngrok-free.app';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$_SERVER['SCRIPT_NAME'] = '/finalyearproject/index.php';

require __DIR__ . '/../includes/AppConfig.php';
require __DIR__ . '/../includes/helpers.php';

echo appConfig()['url'] . PHP_EOL;
echo asset('css/style.css') . PHP_EOL;
echo baseUrl('auth/login') . PHP_EOL;
