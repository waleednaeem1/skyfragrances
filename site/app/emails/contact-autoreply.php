<?php
defined('SKYFR') || exit;
$name = trim((string) ($data['name'] ?? ''));
$subject = trim((string) ($data['subject'] ?? ''));
$replyWindow = trim((string) setting('contact_reply_time', ''));
$whatsapp = mail_whatsapp_url();
?>
<h1 style="margin:0 0 8px;font-size:24px;font-weight:normal;letter-spacing:1px;">We have received your message<?= $name !== '' ? ', ' . e($name) : '' ?></h1>
<p style="margin:0 0 16px;">Thank you for writing to Sky Fragrances. <?= $replyWindow !== '' ? 'We usually reply within ' . e($replyWindow) . '.' : 'We will reply as soon as we can.' ?></p>
<?php if ($subject !== ''): ?>
<p style="margin:0 0 16px;color:#6b6257;font-size:14px;">Subject: <?= e($subject) ?></p>
<?php endif; ?>
<p style="margin:0 0 8px;">Helpful pages while you wait:</p>
<p style="margin:0 0 16px;"><a href="<?= e(SITE_URL . '/faq') ?>" style="color:#0a0a0a;">FAQ</a> · <a href="<?= e(SITE_URL . '/shipping') ?>" style="color:#0a0a0a;">Shipping</a> · <a href="<?= e(SITE_URL . '/returns') ?>" style="color:#0a0a0a;">Returns</a></p>
<p style="margin:0;color:#6b6257;font-size:14px;"><?php if ($whatsapp !== ''): ?>Need a faster answer? <a href="<?= e($whatsapp) ?>" style="color:#0a0a0a;">Message us on WhatsApp</a>.<?php else: ?>Reply to this email if you need to add anything.<?php endif; ?></p>
