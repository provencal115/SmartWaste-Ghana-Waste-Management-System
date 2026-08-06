<?php
/** @var string $fullName */
/** @var array<string, string> $company */
/** @var string $loginUrl */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Smart Waste Management Ghana</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:'Segoe UI',Arial,Helvetica,sans-serif;color:#1e293b;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(15,23,42,0.1);">
                <tr>
                    <td style="background:linear-gradient(135deg,#059669 0%,#047857 100%);padding:32px 28px;text-align:center;">
                        <?php if (!empty($hasLogo)): ?>
                        <img src="cid:company_logo" alt="<?= e($company['name']) ?>" width="80" height="80" style="display:block;margin:0 auto 16px;border-radius:14px;background:#fff;padding:10px;">
                        <?php endif; ?>
                        <h1 style="margin:0;font-size:24px;line-height:1.3;color:#ffffff;font-weight:800;">Smart Waste Management Ghana</h1>
                        <p style="margin:10px 0 0;font-size:14px;color:rgba(255,255,255,0.9);">Cleaner communities across Ghana</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:36px 32px 28px;">
                        <p style="margin:0 0 18px;font-size:17px;line-height:1.6;font-weight:600;">Dear <?= e($fullName) ?>,</p>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#334155;">
                            Welcome to <strong>Smart Waste Management Ghana</strong>.
                        </p>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#334155;">
                            Thank you for registering with us and becoming part of our growing community committed to creating cleaner and healthier neighbourhoods across Ghana.
                        </p>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#334155;">
                            We are delighted to have you as one of our valued customers.
                        </p>
                        <p style="margin:0 0 12px;font-size:15px;line-height:1.75;color:#334155;font-weight:600;">
                            Your account has been created successfully, and you can now enjoy our services, including:
                        </p>
                        <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 20px;width:100%;">
                            <?php foreach ([
                                'Scheduling waste collection',
                                'Choosing your preferred bin size',
                                'Managing your subscription',
                                'Making secure payments',
                                'Tracking collection schedules',
                                'Receiving important service notifications',
                            ] as $item): ?>
                            <tr>
                                <td style="padding:6px 0;font-size:15px;line-height:1.6;color:#334155;vertical-align:top;width:20px;">&#8226;</td>
                                <td style="padding:6px 0 6px 4px;font-size:15px;line-height:1.6;color:#334155;"><?= e($item) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#334155;">
                            Our dedicated team is committed to providing reliable, professional, and environmentally responsible waste collection services.
                        </p>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#334155;">
                            We appreciate your trust in Smart Waste Management and look forward to serving you.
                        </p>
                        <p style="margin:0 0 24px;font-size:15px;line-height:1.75;color:#334155;">
                            If you have any questions, please feel free to contact our customer support team at any time.
                        </p>
                        <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 28px;">
                            <tr>
                                <td style="border-radius:10px;background:#059669;box-shadow:0 4px 14px rgba(5,150,105,0.35);">
                                    <a href="<?= e($loginUrl) ?>" style="display:inline-block;padding:16px 32px;font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;letter-spacing:0.02em;">Login to Your Account</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#334155;font-weight:600;">
                            Thank you for choosing Smart Waste Management Ghana.
                        </p>
                        <p style="margin:0 0 8px;font-size:15px;line-height:1.75;color:#334155;">
                            We look forward to serving you.
                        </p>
                        <p style="margin:24px 0 0;font-size:15px;line-height:1.75;color:#334155;">
                            <strong>Kind regards,</strong><br>
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
