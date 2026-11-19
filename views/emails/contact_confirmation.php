<?php
/** @var array<string, mixed> $message */
/** @var array<string, string> $company */
/** @var string $customerName */
$subjectLine = trim((string) ($message['subject'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We Have Received Your Message</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:'Segoe UI',Arial,Helvetica,sans-serif;color:#1e293b;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(15,23,42,0.1);">
                <tr>
                    <td style="background:linear-gradient(135deg,#059669 0%,#047857 100%);padding:32px 28px;text-align:center;">
                        <?php if (!empty($hasLogo)): ?>
                        <img src="cid:company_logo" alt="<?= e($company['name']) ?>" width="72" height="72" style="display:block;margin:0 auto 14px;border-radius:12px;background:#fff;padding:8px;">
                        <?php endif; ?>
                        <h1 style="margin:0;font-size:22px;line-height:1.3;color:#ffffff;font-weight:700;">Smart Waste Management Ghana</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:36px 32px 28px;">
                        <p style="margin:0 0 18px;font-size:17px;line-height:1.6;font-weight:600;">Dear <?= e($customerName) ?>,</p>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#334155;">
                            Thank you for contacting <strong>Smart Waste Management Ghana</strong>.
                        </p>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#334155;">
                            We have successfully received your message<?php if ($subjectLine !== ''): ?> regarding <strong><?= e($subjectLine) ?></strong><?php endif; ?>.
                        </p>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#334155;">
                            Our team will review your enquiry and get back to you as soon as possible.
                        </p>
                        <p style="margin:0 0 24px;font-size:15px;line-height:1.75;color:#334155;">
                            We appreciate your interest in Smart Waste Management Ghana.
                        </p>
                        <p style="margin:0;font-size:15px;line-height:1.75;color:#334155;">
                            Best regards,<br>
                            <strong>Smart Waste Management Ghana</strong>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:13px;color:#64748b;">
                        <?= e($company['phone'] ?? '') ?><?= !empty($company['phone_alt']) ? ' | ' . e($company['phone_alt']) : '' ?>
                        · <a href="mailto:<?= e($company['email']) ?>" style="color:#059669;"><?= e($company['email']) ?></a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>