<?php
/** @var array<string, mixed> $message */
/** @var array<string, string> $company */
/** @var string $viewUrl */
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>New Contact Message</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border-radius:12px;overflow:hidden;">
<tr><td style="background:linear-gradient(135deg,#059669,#047857);padding:24px 32px;">
<h1 style="margin:0;font-size:20px;color:#fff;">New Contact Message</h1>
</td></tr>
<tr><td style="padding:28px 32px;">
<p style="margin:0 0 12px;font-size:15px;"><strong>Message ID:</strong> #<?= (int) ($message['id'] ?? 0) ?></p>
<p style="margin:0 0 12px;font-size:15px;"><strong>From:</strong> <?= e($message['full_name'] ?? '') ?> &lt;<?= e($message['email'] ?? '') ?>&gt;</p>
<?php if (!empty($message['phone'])): ?>
<p style="margin:0 0 12px;font-size:15px;"><strong>Phone:</strong> <?= e($message['phone']) ?></p>
<?php endif; ?>
<p style="margin:0 0 12px;font-size:15px;"><strong>Subject:</strong> <?= e($message['subject'] ?? '') ?></p>
<div style="margin:16px 0;padding:16px;background:#f8fafc;border-radius:8px;border-left:4px solid #059669;">
<p style="margin:0;font-size:15px;line-height:1.7;white-space:pre-wrap;"><?= e($message['message'] ?? '') ?></p>
</div>
<p style="margin:24px 0 0;"><a href="<?= e($viewUrl) ?>" style="display:inline-block;padding:12px 24px;background:#059669;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">View in Admin Dashboard</a></p>
</td></tr>
<tr><td style="padding:16px 32px;background:#f8fafc;font-size:13px;color:#64748b;"><?= e($company['name']) ?></td></tr>
</table>
</td></tr>
</table>
</body>
</html>
