<?php
defined('SKYFR') || exit;
$name = trim((string) ($data['name'] ?? ''));
$firstName = $name === '' ? '' : explode(' ', $name)[0];
$subject = trim((string) ($data['subject'] ?? ''));
$replyWindow = trim((string) setting('contact_reply_time', 'one working day'));
$whatsapp = mail_whatsapp_url();
$links = [
    ['/faq', 'FAQ'],
    ['/shipping', 'Shipping'],
    ['/returns', 'Returns & Exchange'],
    ['/track', 'Track Order'],
];
?>
<h1 style="margin:0 0 8px;font-size:24px;font-weight:normal;letter-spacing:1px;">We have your message<?= $firstName !== '' ? ', ' . e($firstName) : '' ?>.</h1>
<p style="margin:0 0 16px;">Thank you for writing to Sky Fragrances. A person reads every message and we reply within <?= e($replyWindow) ?>, sooner on WhatsApp.</p>
<?php if ($subject !== ''): ?>
<p style="margin:0 0 16px;color:#6b6257;font-size:14px;">Subject: <?= e($subject) ?></p>
<?php endif; ?>
<p style="margin:0 0 8px;">While you wait, the answers to the questions we hear most are here:</p>
<p style="margin:0 0 16px;"><?php foreach ($links as $index => [$path, $label]): ?><?= $index > 0 ? ' · ' : '' ?><a href="<?= e(SITE_URL . $path) ?>" style="color:#0a0a0a;"><?= e($label) ?></a><?php endforeach; ?></p>
<p style="margin:0 0 16px;color:#6b6257;font-size:14px;">If your message is about an order, keep the order number handy. It starts with SF- and is on your confirmation page.</p>
<p style="margin:0;color:#6b6257;font-size:14px;"><?php if ($whatsapp !== ''): ?>Need a faster answer? <a href="<?= e($whatsapp) ?>" style="color:#0a0a0a;">Message us on WhatsApp</a>.<?php else: ?>Reply to this email if you need to add anything.<?php endif; ?></p>
