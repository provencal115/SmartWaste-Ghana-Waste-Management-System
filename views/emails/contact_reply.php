<?php
/** @var string $customerName */
/** @var string $replyBody */
/** @var string $adminName */
/** @var array<string, string> $company */
/** @var array<string, mixed> $message */
/** @var array<int, array<string, mixed>> $previousReplies */
/** @var bool $hasLogo */
$originalSubject = trim((string) ($message['subject'] ?? ''));
$originalBody = trim((string) ($message['message'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Reply from Smart Waste Management Ghana</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border-radius:12px;overflow:hidden;">
<tr><td style="background:linear-gradient(135deg,#059669,#047857);padding:24px 32px;text-align:center;">
<?php if (!empty($hasLogo)): ?>
<img src="cid:company_logo" alt="<?= e($company['name']) ?>" width="64" height="64" style="display:block;margin:0 auto 12px;border-radius:10px;background:#fff;padding:6px;">
<?php endif; ?>
<h1 style="margin:0;font-size:20px;color:#fff;">Smart Waste Management Ghana</h1>
</td></tr>
<tr><td style="padding:28px 32px;">
<p style="margin:0 0 16px;font-size:16px;">Dear <?= e($customerName) ?>,</p>
<p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;">Thank you for contacting <strong>Smart Waste Management Ghana</strong>.</p>
<div style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#334155;white-space:pre-wrap;"><?= e($replyBody) ?></div>
<p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#334155;">If you have any further questions, please feel free to contact us.</p>

<?php if ($originalSubject !== '' || $originalBody !== ''): ?>
<div style="margin:24px 0;padding:16px;background:#f8fafc;border-radius:8px;border-left:4px solid #94a3b8;">
<p style="margin:0 0 8px;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;font-weight:600;">Your original message</p>
<?php if ($originalSubject !== ''): ?>
<p style="margin:0 0 8px;font-size:14px;"><strong>Subject:</strong> <?= e($originalSubject) ?></p>
<?php endif; ?>
<?php if ($originalBody !== ''): ?>
<p style="margin:0;font-size:14px;line-height:1.6;white-space:pre-wrap;color:#475569;"><?= e($originalBody) ?></p>
<?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($previousReplies)): ?>
<div style="margin:0 0 8px;padding:16px;background:#f0fdf4;border-radius:8px;border-left:4px solid #059669;">
<p style="margin:0 0 12px;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;color:#047857;font-weight:600;">Earlier replies</p>
<?php foreach ($previousReplies as $prev): ?>
<div style="margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #d1fae5;">
<p style="margin:0 0 6px;font-size:13px;color:#64748b;"><?= e(date('M j, Y g:i A', strtotime($prev['sent_at'] ?? 'now'))) ?></p>
<p style="margin:0;font-size:14px;line-height:1.6;white-space:pre-wrap;"><?= e($prev['reply_body'] ?? '') ?></p>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<p style="margin:24px 0 0;font-size:15px;color:#334155;">Best regards,<br><strong><?= e($company['name']) ?></strong></p>
<p style="margin:16px 0 0;font-size:13px;color:#64748b;"><?= e($company['email']) ?> | <?= e($company['phone']) ?></p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>