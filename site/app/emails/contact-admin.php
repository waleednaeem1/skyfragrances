<?php
defined('SKYFR') || exit;
$name = (string) ($data['name'] ?? '');
$email = trim((string) ($data['email'] ?? ''));
$phone = trim((string) ($data['phone'] ?? ''));
$subject = trim((string) ($data['subject'] ?? ''));
$message = (string) ($data['message'] ?? '');
$orderNumber = trim((string) ($data['order_number'] ?? ''));
$createdAt = (string) ($data['created_at'] ?? '');
$phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';
$labelStyle = 'padding:6px 0;color:#6b6257;font-size:14px;width:120px;vertical-align:top;';
?>
<h1 style="margin:0 0 8px;font-size:24px;font-weight:normal;letter-spacing:1px;">New message from the contact form</h1>
<p style="margin:0 0 16px;color:#6b6257;font-size:14px;">Received <?= e($createdAt) ?>. Reply to this email to answer <?= e($name) ?> directly.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
<tr><td style="<?= $labelStyle ?>">Name</td><td style="padding:6px 0;"><?= e($name) ?></td></tr>
<?php if ($email !== ''): ?><tr><td style="<?= $labelStyle ?>">Email</td><td style="padding:6px 0;"><a href="mailto:<?= e($email) ?>" style="color:#0a0a0a;"><?= e($email) ?></a></td></tr><?php endif; ?>
<?php if ($phone !== ''): ?><tr><td style="<?= $labelStyle ?>">Phone</td><td style="padding:6px 0;"><a href="tel:<?= e($phone) ?>" style="color:#0a0a0a;"><?= e($phone) ?></a><?php if ($phoneDigits !== ''): ?> · <a href="https://wa.me/<?= e($phoneDigits) ?>" style="color:#0a0a0a;">WhatsApp</a><?php endif; ?></td></tr><?php endif; ?>
<?php if ($subject !== ''): ?><tr><td style="<?= $labelStyle ?>">Subject</td><td style="padding:6px 0;"><?= e($subject) ?></td></tr><?php endif; ?>
<?php if ($orderNumber !== ''): ?><tr><td style="<?= $labelStyle ?>">Order</td><td style="padding:6px 0;"><a href="<?= e(SITE_URL . '/admin/orders/' . $orderNumber) ?>" style="color:#0a0a0a;"><?= e($orderNumber) ?></a></td></tr><?php endif; ?>
</table>
<h2 style="margin:16px 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#6b6257;">Message</h2>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0e8;margin:0 0 16px;"><tr><td style="padding:16px;white-space:pre-line;"><?= e($message) ?></td></tr></table>
<p style="margin:0;color:#6b6257;font-size:14px;">All messages are also kept in the admin panel under Messages.</p>
