<?php
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$config = require __DIR__ . '/../config/mail.php';
$pass = str_replace(' ', '', $config['smtp']['password'] ?? '');

$scenarios = [
    ['host' => 'smtp.gmail.com', 'port' => 587, 'secure' => PHPMailer::ENCRYPTION_STARTTLS],
    ['host' => 'smtp.gmail.com', 'port' => 465, 'secure' => PHPMailer::ENCRYPTION_SMTPS],
];

foreach ($scenarios as $s) {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $s['host'];
    $mail->Port = $s['port'];
    $mail->SMTPSecure = $s['secure'];
    $mail->SMTPAuth = true;
    $mail->Username = trim($config['smtp']['username'] ?? '');
    $mail->Password = $pass;
    $mail->Timeout = 25;
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
    $mail->Debugoutput = static function ($str) {
        echo rtrim($str) . "\n";
    };

    echo "=== Port {$s['port']} ({$s['secure']}) ===\n";
    try {
        if ($mail->smtpConnect()) {
            echo "RESULT: smtpConnect OK\n";
            $mail->smtpClose();
        }
    } catch (Throwable $e) {
        echo 'RESULT FAIL: ' . $e->getMessage() . "\n";
    }
    echo "\n";
}