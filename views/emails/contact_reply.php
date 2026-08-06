<?php
/** @var string $customerName */
/** @var string $replyBody */
/** @var string $adminName */
/** @var array<string, string> $company */
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Reply from Smart Waste Management</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border-radius:12px;overflow:hidden;">
<tr><td style="background:linear-gradient(135deg,#059669,#047857);padding:24px 32px;">
<h1 style="margin:0;font-size:20px;color:#fff;">Smart Waste Management Ghana</h1>
</td></tr>
<tr><td style="padding:28px 32px;">
<p style="margin:0 0 16px;font-size:16px;">Dear <?= e($customerName) ?>,</p>
<div style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;white-space:pre-wrap;"><?= e($replyBody) ?></div>
<p style="margin:24px 0 0;font-size:15px;color:#334155;">Kind regards,<br><strong><?= e($adminName) ?></strong><br><?= e($company['name']) ?></p>
<p style="margin:16px 0 0;font-size:13px;color:#64748b;"><?= e($company['email']) ?> | <?= e($company['phone']) ?></p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
