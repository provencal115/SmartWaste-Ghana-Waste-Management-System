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

    /** Send confirmation email to the customer after Contact Us form submission. */
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

        $mail = self::createSmtpMailer();
        if (!$mail) {
            self::logMailError('contact_customer_confirm_skipped', 'SMTP credentials not configured.', ['message_id' => $message['id'] ?? null]);
            return false;
        }

        $customerName = trim($message['full_name'] ?? '') ?: 'Valued Customer';
        $company = $config['company'];

        try {
            $mail->addAddress($email, $customerName);
            $mail->addReplyTo($config['from_email'], $config['from_name']);
            $mail->isHTML(true);
            $mail->Subject = $config['contact']['customer_confirm_subject']
                ?? "We've Received Your Message – Smart Waste Management Ghana";

            $logoPath = siteLogoAbsolutePath();
            $hasLogo  = $logoPath !== null && is_file($logoPath);
            if ($hasLogo) {
                $mail->addEmbeddedImage($logoPath, 'company_logo', basename($logoPath));
            }

            ob_start();
            require __DIR__ . '/../views/emails/contact_confirmation.php';
            $mail->Body = (string) ob_get_clean();
            $mail->AltBody = self::contactConfirmationPlainText($customerName, $message, $company);

            $mail->send();
            logActivity(null, 'contact_customer_confirm_sent', 'mail', [
                'message_id' => $message['id'] ?? null,
                'email'      => $email,
            ]);
            return true;
        } catch (Throwable $e) {
            self::logMailError('contact_customer_confirm_failed', $e->getMessage(), [
                'message_id' => $message['id'] ?? null,
                'email'      => $email,
            ]);
            return false;
        }
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

        $mail = self::createSmtpMailer();
        if (!$mail) {
            return false;
        }

        try {
            $mail->addAddress($adminEmail, 'Administrator');
            $mail->isHTML(true);
            $mail->Subject = $config['contact']['admin_notify_subject'] ?? 'New Contact Message';
            $company = $config['company'];
            $viewUrl = baseUrl('admin/messages');

            ob_start();
            require __DIR__ . '/../views/emails/contact_admin.php';
            $mail->Body = (string) ob_get_clean();
            $mail->AltBody = sprintf(
                "New contact message #%d\nFrom: %s <%s>\nSubject: %s\n\n%s\n\nView: %s",
                (int) ($message['id'] ?? 0),
                $message['full_name'] ?? '',
                $message['email'] ?? '',
                $message['subject'] ?? '',
                $message['message'] ?? '',
                $viewUrl
            );

            $mail->send();
            logActivity(null, 'contact_admin_notify_sent', 'mail', ['message_id' => $message['id'] ?? null]);
            return true;
        } catch (Throwable $e) {
            self::logMailError('contact_admin_notify_failed', $e->getMessage(), ['message_id' => $message['id'] ?? null]);
            return false;
        }
    }

    /** Send a reply to the customer who submitted a contact message. */
    public static function sendContactReply(array $message, string $replyBody, string $adminName): bool
    {
        $config = self::config();
        if (empty($config['enabled'])) {
            return false;
        }

        $email = trim($message['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $mail = self::createSmtpMailer();
        if (!$mail) {
            return false;
        }

        try {
            $mail->addAddress($email, $message['full_name'] ?? '');
            $mail->addReplyTo($config['from_email'], $config['from_name']);
            $mail->isHTML(true);
            $prefix = $config['contact']['reply_subject_prefix'] ?? 'Re: ';
            $mail->Subject = $prefix . ($message['subject'] ?? 'Your enquiry');
            $company = $config['company'];
            $customerName = $message['full_name'] ?? 'Customer';

            ob_start();
            require __DIR__ . '/../views/emails/contact_reply.php';
            $mail->Body = (string) ob_get_clean();
            $mail->AltBody = "Dear {$customerName},\n\n{$replyBody}\n\nKind regards,\n{$adminName}\n{$company['name']}";

            $mail->send();
            logActivity(null, 'contact_reply_sent', 'mail', ['message_id' => $message['id'] ?? null, 'email' => $email]);
            return true;
        } catch (Throwable $e) {
            self::logMailError('contact_reply_failed', $e->getMessage(), ['message_id' => $message['id'] ?? null]);
            return false;
        }
    }

    private static function createSmtpMailer(): ?PHPMailer
    {
        $config = self::config();
        $smtpUser = trim($config['smtp']['username'] ?? '');
        $smtpPass = $config['smtp']['password'] ?? '';
        if ($smtpUser === '' || $smtpPass === '') {
            return null;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $config['smtp']['host'];
        $mail->Port       = (int) $config['smtp']['port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = ($config['smtp']['encryption'] ?? 'tls') === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($config['from_email'], $config['from_name']);

        return $mail;
    }

    /** @param array<string, mixed> $message */
    /** @param array<string, string> $company */
    private static function contactConfirmationPlainText(string $customerName, array $message, array $company): string
    {
        $subject = $message['subject'] ?? '';
        $subjectLine = $subject !== '' ? " regarding \"{$subject}\"" : '';
        $phones = $company['phone'] ?? '';
        if (!empty($company['phone_alt'])) {
            $phones .= ' | ' . $company['phone_alt'];
        }

        return <<<TEXT
Dear {$customerName},

Thank you for contacting Smart Waste Management Ghana.

We have successfully received your message and appreciate you taking the time to get in touch with us.

Our support team is currently reviewing your enquiry{$subjectLine} and will respond as soon as possible.

If your request is urgent, please contact us using the phone numbers below.

Thank you for choosing Smart Waste Management Ghana.

Kind regards,
{$company['name']}

{$phones}
{$company['email']}
TEXT;
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
