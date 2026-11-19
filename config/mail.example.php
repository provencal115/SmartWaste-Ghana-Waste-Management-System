<?php
/**
 * Copy this file to mail.local.php and fill in your Gmail SMTP credentials.
 * mail.local.php is loaded after mail.php and overrides those settings.
 *
 * Example mail.local.php:
 *
 * <?php
 * return [
 *     'smtp' => [
 *         'host'       => 'smtp.gmail.com',
 *         'port'       => 465,
 *         'encryption' => 'ssl',
 *         'username' => 'yourname@gmail.com',
 *         'password' => 'your-16-char-app-password',
 *     ],
 *
 *     'contact' => [
 *         'admin_notify_email' => 'your-admin@gmail.com',
 *     ],
 * ];
 *
 * Store the Gmail App Password only in mail.local.php (gitignored).
 * Primary SMTP: port 587 + tls. The app falls back to 465 + ssl if 587 fails (common on XAMPP).
 */
return [];
