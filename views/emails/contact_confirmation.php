<?php
/** @var array<string, mixed> $message */
/** @var array<string, string> $company */
/** @var string $customerName */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We've Received Your Message</title>
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
                        <p style="margin:8px 0 0;font-size:14px;color:rgba(255,255,255,0.9);">Message received successfully</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:36px 32px 28px;">
                        <p style="margin:0 0 18px;font-size:17px;line-height:1.6;font-weight:600;">Dear <?= e($customerName) ?>,</p>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#334155;">
                            Thank you for contacting <strong>Smart Waste Management Ghana</strong>.
                        </p>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#334155;">
                            We have successfully received your message and appreciate you taking the time to get in touch with us.
                        </p>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#334155;">
                            Our support team is currently reviewing your enquiry<?php if (!empty($message['subject'])): ?> regarding <strong><?= e($message['subject']) ?></strong><?php endif; ?> and will respond as soon as possible.
                        </p>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#334155;">
                            If your request is urgent, please contact us using the phone numbers below.
                        </p>
                        <p style="margin:0 0 24px;font-size:15px;line-height:1.75;color:#334155;font-weight:600;">
                            Thank you for choosing Smart Waste Management Ghana.
                        </p>
                        <p style="margin:0;font-size:15px;line-height:1.75;color:#334155;">
                            Kind regards,<br>
                            <strong><?= e($company['name']) ?></strong>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 32px;background:#f0fdf4;border-top:1px solid #bbf7d0;">
                        <p style="margin:0 0 10px;font-size:14px;line-height:1.8;color:#047857;font-weight:600;">
                            &#128222; <?= e($company['phone'] ?? '') ?><?= !empty($company['phone_alt']) ? ' | ' . e($company['phone_alt']) : '' ?>
                        </p>
                        <p style="margin:0 0 10px;font-size:14px;line-height:1.8;color:#047857;">
                            &#9993;&#65039; <a href="mailto:<?= e($company['email']) ?>" style="color:#059669;text-decoration:none;font-weight:600;"><?= e($company['email']) ?></a>
                        </p>
                        <?php if (!empty($company['website'])): ?>
                        <p style="margin:0;font-size:14px;line-height:1.8;color:#047857;">
                            &#127760; <a href="<?= e($company['website']) ?>" style="color:#059669;text-decoration:none;"><?= e(preg_replace('#^https?://#', '', $company['website'])) ?></a>
                        </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
