<?php
/**
 * Welcome and transactional email delivery via PHPMailer + SMTP.
 */

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    /** @var array<string, mixed>|null */
    private static ?array $config = null;

    private static ?string $lastSmtpError = null;

    public static function lastSmtpError(): ?string
    {
        return self::$lastSmtpError;
    }

    /** @return array<string, mixed> */
    private static function config(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $config = require __DIR__ . '/../config/mail.php';
        $local = __DIR__ . '/../config/mail.local.php';
        if (is_file($local)) {
            $config = array_replace_recursive($config, require $local);
        }

        self::$config = $config;

        if (empty(self::$config['company']['website'])) {
            self::$config['company']['website'] = rtrim(appConfig()['url'], '/');
        }

        return self::$config;
    }

    /**
     * Send the welcome email after successful registration.
     *
     * @return bool True when sent; false when skipped or failed (errors are logged).
     */
    public static function sendWelcomeEmail(array $user): bool
    {
        $config = self::config();
        $userId = isset($user['id']) ? (int) $user['id'] : 0;

        if (empty($config['enabled'])) {
            if ($userId) {
                UserModel::setWelcomeEmailStatus($userId, 'failed');
            }
            return false;
        }

        $email = trim($user['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::logError('Welcome email skipped: invalid recipient.', ['user_id' => $userId ?: null]);
            if ($userId) {
                UserModel::setWelcomeEmailStatus($userId, 'failed');
            }
            return false;
        }

        $mail = self::createSmtpMailer();
        if (!$mail) {
            self::logError('Welcome email skipped: SMTP credentials not configured.', [
                'user_id' => $userId ?: null,
                'email'   => $email,
            ]);
            if ($userId) {
                UserModel::setWelcomeEmailStatus($userId, 'failed');
            }
            return false;
        }

        $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        if ($fullName === '') {
            $fullName = 'Valued Customer';
        }

        try {
            $mail->addAddress($email, $fullName);
            $mail->isHTML(true);
            $mail->Subject = $config['welcome']['subject'] ?? 'Welcome to Smart Waste Management Ghana!';

            $logoPath = siteLogoAbsolutePath();
            $hasLogo  = $logoPath !== null && is_file($logoPath);
            if ($hasLogo) {
                $mail->addEmbeddedImage($logoPath, 'company_logo', basename($logoPath));
            }

            $loginUrl = baseUrl('auth/login');
            $company  = $config['company'];

            ob_start();
            require __DIR__ . '/../views/emails/welcome.php';
            $mail->Body = (string) ob_get_clean();
            $mail->AltBody = self::welcomePlainText($fullName, $company, $loginUrl);

            $mail->send();

            if ($userId) {
                UserModel::setWelcomeEmailStatus($userId, 'sent');
            }

            logActivity($userId ?: null, 'welcome_email_sent', 'mail', ['email' => $email]);
            return true;
        } catch (MailException $e) {
            if ($userId) {
                UserModel::setWelcomeEmailStatus($userId, 'failed');
            }
            self::logError('Welcome email PHPMailer error: ' . $e->getMessage(), [
                'user_id' => $userId ?: null,
                'email'   => $email,
            ]);
            return false;
        } catch (Throwable $e) {
            if ($userId) {
                UserModel::setWelcomeEmailStatus($userId, 'failed');
            }
            self::logError('Welcome email unexpected error: ' . $e->getMessage(), [
                'user_id' => $userId ?: null,
                'email'   => $email,
            ]);
            return false;
        }
    }

    /** Send password reset link email (non-blocking; returns false on failure). */
    public static function sendPasswordResetEmail(array $user, string $resetLink): bool
    {
        $config = self::config();
        if (empty($config['enabled'])) {
            return false;
        }

        $email = trim($user['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $mail = self::createSmtpMailer();
        if (!$mail) {
            return false;
        }

        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Customer';
        $company = $config['company'];

        try {
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Password — Smart Waste Management Ghana';
            $mail->Body = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;padding:24px;">'
                . '<h2 style="color:#047857;">Password Reset</h2>'
                . '<p>Dear ' . htmlspecialchars($name) . ',</p>'
                . '<p>We received a request to reset your password. Click the button below (valid for 1 hour):</p>'
                . '<p><a href="' . htmlspecialchars($resetLink) . '" style="display:inline-block;padding:14px 28px;background:#059669;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">Reset Password</a></p>'
                . '<p style="color:#64748b;font-size:13px;">If you did not request this, please ignore this email.</p>'
                . '<p>Kind regards,<br><strong>' . htmlspecialchars($company['name']) . '</strong></p></div>';
            $mail->AltBody = "Dear {$name},\n\nReset your password: {$resetLink}\n\nValid for 1 hour.\n\n{$company['name']}";
            $mail->send();
            return true;
        } catch (Throwable $e) {
            self::logMailError('password_reset_email_failed', $e->getMessage(), ['user_id' => $user['id'] ?? null]);
            return false;
        }
    }

    /** Send automatic confirmation to the visitor email on contact_messages (public form; no login). */
    public static function sendContactCustomerConfirmation(array $message): bool
    {
        $config = self::config();
        if (empty($config['enabled'])) {
            self::logMailError('contact_customer_confirm_skipped', 'Mail disabled in config.', ['message_id' => $message['id'] ?? null]);
            return false;
        }

        $email = trim($message['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::logMailError('contact_customer_confirm_skipped', 'Invalid customer email.', ['message_id' => $message['id'] ?? null]);
            return false;
        }

        $customerName = trim($message['full_name'] ?? '') ?: 'Valued Customer';
        $company = $config['company'];

        $sent = self::deliverSmtp(
            function (PHPMailer $mail) use ($config, $email, $customerName, $message, $company) {
                $mail->addAddress($email, $customerName);
                $mail->addReplyTo($config['from_email'], $config['from_name']);
                $mail->isHTML(true);
                $mail->Subject = $config['contact']['customer_confirm_subject']
                    ?? 'We Have Received Your Message – Smart Waste Management Ghana';

                $logoPath = siteLogoAbsolutePath();
                if ($logoPath !== null && is_file($logoPath)) {
                    $mail->addEmbeddedImage($logoPath, 'company_logo', basename($logoPath));
                }

                ob_start();
                require __DIR__ . '/../views/emails/contact_confirmation.php';
                $mail->Body = (string) ob_get_clean();
                $mail->AltBody = self::contactConfirmationPlainText($customerName, $message, $company);
            },
            'contact_customer_confirm_failed',
            ['message_id' => $message['id'] ?? null, 'email' => $email]
        );

        if (!$sent) {
            return false;
        }

        logActivity(null, 'contact_customer_confirm_sent', 'mail', [
            'message_id' => $message['id'] ?? null,
            'email'      => $email,
        ]);
        return true;
    }

    /** Notify administrator of a new contact form submission. */
    public static function sendContactAdminNotification(array $message): bool
    {
        $config = self::config();
        if (empty($config['enabled'])) {
            return false;
        }

        $adminEmail = trim($config['contact']['admin_notify_email'] ?? $config['company']['email'] ?? '');
        if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $messageId = (int) ($message['id'] ?? 0);
        $viewUrl = $messageId > 0
            ? baseUrl('admin/messages') . '&id=' . $messageId
            : baseUrl('admin/messages');

        $sent = self::deliverSmtp(
            function (PHPMailer $mail) use ($config, $adminEmail, $message, $viewUrl) {
                $mail->addAddress($adminEmail, 'Administrator');
                $mail->isHTML(true);
                $mail->Subject = $config['contact']['admin_notify_subject'] ?? 'New Contact Message';
                $company = $config['company'];

                ob_start();
                require __DIR__ . '/../views/emails/contact_admin.php';
                $mail->Body = (string) ob_get_clean();
                $mail->AltBody = sprintf(
                    "New contact message #%d\nFrom: %s <%s>\nPhone: %s\nSubject: %s\n\n%s\n\nView: %s",
                    (int) ($message['id'] ?? 0),
                    $message['full_name'] ?? '',
                    $message['email'] ?? '',
                    $message['phone'] ?? '—',
                    $message['subject'] ?? '',
                    $message['message'] ?? '',
                    $viewUrl
                );
            },
            'contact_admin_notify_failed',
            ['message_id' => $messageId]
        );

        if ($sent) {
            logActivity(null, 'contact_admin_notify_sent', 'mail', ['message_id' => $messageId]);
        }

        return $sent;
    }

    /**
     * Admin reply to the visitor email on contact_messages (public form — no user account).
     *
     * @param array<string, mixed> $message contact_messages row; optional replies[] for thread history
     */
    public static function sendContactReply(array $message, string $replyBody, string $adminName): bool
    {
        $config = self::config();
        if (empty($config['enabled'])) {
            return false;
        }

        $email = trim($message['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::logMailError('contact_reply_skipped', 'Invalid visitor email on contact message.', [
                'message_id' => $message['id'] ?? null,
            ]);
            return false;
        }

        $customerName = trim($message['full_name'] ?? '') ?: 'Customer';
        $previousReplies = is_array($message['replies'] ?? null) ? $message['replies'] : [];

        $subjectLine = trim((string) ($message['subject'] ?? ''));
        if ($subjectLine === '') {
            $subjectLine = 'Your enquiry';
        }
        $prefix = $config['contact']['reply_subject_prefix'] ?? 'Re: ';
        $suffix = $config['contact']['reply_subject_suffix'] ?? ' – Smart Waste Management Ghana';
        $replySubject = $prefix . $subjectLine . $suffix;

        $sent = self::deliverSmtp(
            function (PHPMailer $mail) use ($config, $email, $message, $replyBody, $adminName, $customerName, $previousReplies, $replySubject) {
                $mail->addAddress($email, $customerName);
                $mail->addReplyTo($config['from_email'], $config['from_name']);
                $mail->isHTML(true);
                $mail->Subject = $replySubject;
                $company = $config['company'];

                $logoPath = siteLogoAbsolutePath();
                $hasLogo = $logoPath !== null && is_file($logoPath);
                if ($hasLogo) {
                    $mail->addEmbeddedImage($logoPath, 'company_logo', basename($logoPath));
                }

                ob_start();
                require __DIR__ . '/../views/emails/contact_reply.php';
                $mail->Body = (string) ob_get_clean();
                $mail->AltBody = self::contactReplyPlainText($customerName, $replyBody, $message, $previousReplies, $company);
            },
            'contact_reply_failed',
            ['message_id' => $message['id'] ?? null, 'email' => $email]
        );

        if ($sent) {
            logActivity(null, 'contact_reply_sent', 'mail', ['message_id' => $message['id'] ?? null, 'email' => $email]);
        }

        return $sent;
    }

    /** @return list<array{port: int, encryption: string}> */
    private static function smtpProfiles(): array
    {
        $smtp = self::config()['smtp'] ?? [];
        $profiles = [[
            'port'       => (int) ($smtp['port'] ?? 587),
            'encryption' => strtolower((string) ($smtp['encryption'] ?? 'tls')),
        ]];
        if (!empty($smtp['alternate']) && is_array($smtp['alternate'])) {
            $profiles[] = [
                'port'       => (int) ($smtp['alternate']['port'] ?? 465),
                'encryption' => strtolower((string) ($smtp['alternate']['encryption'] ?? 'ssl')),
            ];
        }

        return $profiles;
    }

    /**
     * @param callable(PHPMailer): void $configure
     * @param array<string, mixed> $logContext
     */
    private static function deliverSmtp(callable $configure, string $failAction, array $logContext): bool
    {
        self::$lastSmtpError = null;
        $lastError = null;
        foreach (self::smtpProfiles() as $index => $profile) {
            $mail = self::createSmtpMailer($profile);
            if (!$mail) {
                self::$lastSmtpError = 'SMTP credentials are not configured.';
                self::logMailError($failAction, self::$lastSmtpError, $logContext);
                return false;
            }

            try {
                $configure($mail);
                $mail->send();
                if ($index > 0) {
                    error_log('[SmartWaste Mail] Sent using alternate SMTP port ' . $profile['port']);
                }
                return true;
            } catch (Throwable $e) {
                $lastError = $e;
            }
        }

        if ($lastError) {
            self::$lastSmtpError = $lastError->getMessage();
            self::logMailError($failAction, self::$lastSmtpError, $logContext);
        }

        return false;
    }

    /** @param array{port: int, encryption: string}|null $profile */
    private static function createSmtpMailer(?array $profile = null): ?PHPMailer
    {
        $config = self::config();
        $smtpUser = trim($config['smtp']['username'] ?? '');
        $smtpPass = $config['smtp']['password'] ?? '';
        if ($smtpUser === '' || $smtpPass === '') {
            return null;
        }

        $encryption = strtolower((string) ($config['smtp']['encryption'] ?? 'tls'));
        $port = (int) ($config['smtp']['port'] ?? 587);
        if ($profile) {
            $port = (int) $profile['port'];
            $encryption = strtolower((string) $profile['encryption']);
        }

        if ($encryption === 'ssl' || $port === 465) {
            $secure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $secure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host         = $config['smtp']['host'] ?? 'smtp.gmail.com';
        $mail->Port         = $port;
        $mail->SMTPAuth     = true;
        $mail->SMTPAutoTLS  = true;
        $mail->Username     = $smtpUser;
        $mail->Password     = str_replace(' ', '', (string) $smtpPass);
        $mail->SMTPSecure   = $secure;
        $mail->Timeout      = 30;
        $mail->SMTPOptions  = [
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ],
        ];
        $mail->CharSet      = 'UTF-8';
        $mail->setFrom($config['from_email'], $config['from_name']);

        return $mail;
    }

    /** @param array<string, mixed> $message */
    /** @param array<string, string> $company */
    private static function contactConfirmationPlainText(string $customerName, array $message, array $company): string
    {
        $subject = trim((string) ($message['subject'] ?? ''));
        $regarding = $subject !== '' ? " regarding {$subject}" : '';

        return <<<TEXT
Dear {$customerName},

Thank you for contacting Smart Waste Management Ghana.

We have successfully received your message{$regarding}.

Our team will review your enquiry and get back to you as soon as possible.

We appreciate your interest in Smart Waste Management Ghana.

Best regards,
Smart Waste Management Ghana
TEXT;
    }

    /**
     * @param array<string, mixed> $message
     * @param array<int, array<string, mixed>> $previousReplies
     * @param array<string, string> $company
     */
    private static function contactReplyPlainText(
        string $customerName,
        string $replyBody,
        array $message,
        array $previousReplies,
        array $company
    ): string {
        $lines = [
            "Dear {$customerName},",
            '',
            'Thank you for contacting Smart Waste Management Ghana.',
            '',
            $replyBody,
            '',
            'If you have any further questions, please feel free to contact us.',
            '',
        ];

        $origSubject = trim((string) ($message['subject'] ?? ''));
        $origBody = trim((string) ($message['message'] ?? ''));
        if ($origSubject !== '' || $origBody !== '') {
            $lines[] = '--- Your original message ---';
            if ($origSubject !== '') {
                $lines[] = "Subject: {$origSubject}";
            }
            if ($origBody !== '') {
                $lines[] = $origBody;
            }
            $lines[] = '';
        }

        foreach ($previousReplies as $prev) {
            $lines[] = '--- Earlier reply ---';
            $lines[] = (string) ($prev['reply_body'] ?? '');
            $lines[] = '';
        }

        $lines[] = 'Best regards,';
        $lines[] = $company['name'] ?? 'Smart Waste Management Ghana';

        return implode("\n", $lines);
    }

    /** @param array<string, string> $company */
    private static function welcomePlainText(string $fullName, array $company, string $loginUrl): string
    {
        return <<<TEXT
Dear {$fullName},

Thank you for registering with Smart Waste Management Ghana.

We are delighted to welcome you to our growing community. Our team is committed to providing reliable, safe, and environmentally responsible waste collection services.

You can now log in to your account to schedule collections, manage your subscription, track payments, and receive important service notifications.

Log in: {$loginUrl}

Thank you for choosing Smart Waste Management. We look forward to serving you.

Kind regards,
{$company['name']}

{$company['email']} | {$company['phone']}
{$company['address']}
TEXT;
    }

    /** @param array<string, mixed> $context */
    private static function logMailError(string $action, string $message, array $context = []): void
    {
        error_log('[SmartWaste Mail] ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE));
        logActivity(null, $action, 'mail', array_merge(['message' => $message], $context));
    }

    /** @param array<string, mixed> $context */
    private static function logError(string $message, array $context = []): void
    {
        $line = '[SmartWaste Mail] ' . $message;
        if ($context) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        error_log($line);

        $userId = isset($context['user_id']) ? (int) $context['user_id'] : null;
        logActivity($userId, 'welcome_email_failed', 'mail', [
            'message' => $message,
            'context' => $context,
        ]);
    }
}
