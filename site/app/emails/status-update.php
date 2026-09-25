<?php
defined('SKYFR') || exit;
$order = $data['order'] ?? [];
$status = (string) ($data['status'] ?? $order['status'] ?? 'pending');
$orderNumber = (string) ($order['order_number'] ?? '');
$sentence = trim((string) ($data['message'] ?? ''));
if ($sentence === '') {
    $sentence = mail_status_sentence($status);
}
$trackUrl = (string) ($data['track_url'] ?? SITE_URL . '/track');
$courier = trim((string) ($order['courier_name'] ?? ''));
$tracking = trim((string) ($order['tracking_number'] ?? ''));
$trackingUrl = trim((string) ($order['tracking_url'] ?? ''));
if (!str_starts_with(strtolower($trackingUrl), 'https://')) {
    $trackingUrl = '';
}
$reason = trim((string) ($order['cancel_reason'] ?? ''));
$whatsapp = mail_whatsapp_url();
$method = (string) ($order['payment_method'] ?? 'cod');
?>
<h1 style="margin:0 0 8px;font-size:24px;font-weight:normal;letter-spacing:1px;">Order <?= e($orderNumber) ?> — <?= e(ucfirst($status)) ?></h1>
<p style="margin:0 0 20px;">Hello <?= e((string) ($order['customer_name'] ?? '')) ?>, <?= e($sentence) ?></p>
<?php if ($status === 'shipped' && ($courier !== '' || $tracking !== '')): ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0e8;margin:0 0 16px;"><tr><td style="padding:16px;">
<?php if ($courier !== ''): ?><div><span style="color:#6b6257;">Courier:</span> <?= e($courier) ?></div><?php endif; ?>
<?php if ($tracking !== ''): ?><div><span style="color:#6b6257;">Tracking number:</span> <strong><?= e($tracking) ?></strong></div><?php endif; ?>
<?php if ($trackingUrl !== ''): ?><div style="padding-top:6px;"><a href="<?= e($trackingUrl) ?>" style="color:#0a0a0a;">Track with the courier</a></div><?php endif; ?>
</td></tr></table>
<?php if ($method === 'cod'): ?>
<p style="margin:0 0 16px;">Please keep <strong><?= e(money((string) ($order['grand_total'] ?? '0.00'))) ?></strong> ready for the courier.</p>
<?php endif; ?>
<?php endif; ?>
<?php if ($status === 'cancelled' && $reason !== ''): ?>
<p style="margin:0 0 16px;color:#6b6257;"><?= e($reason) ?></p>
<?php endif; ?>
<?= mail_button($trackUrl, 'Track your order') ?>
<p style="margin:0;color:#6b6257;font-size:14px;"><?php if ($whatsapp !== ''): ?>Need help? <a href="<?= e($whatsapp) ?>" style="color:#0a0a0a;">Message us on WhatsApp</a>.<?php else: ?>Need help? Reply to this email.<?php endif; ?></p>
