<?php
defined('SKYFR') || exit;
$order = $data['order'] ?? [];
$items = $data['items'] ?? [];
$orderNumber = (string) ($order['order_number'] ?? '');
$method = (string) ($order['payment_method'] ?? 'cod');
$trackUrl = (string) ($data['track_url'] ?? SITE_URL . '/track');
$orderUrl = (string) ($data['order_url'] ?? '');
$whatsapp = mail_whatsapp_url();
$accountLines = $method === 'cod' ? [] : mail_payment_account_lines($method);
$address = array_filter([
    (string) ($order['address'] ?? ''),
    trim((string) ($order['city'] ?? '') . ' ' . (string) ($order['postal_code'] ?? '')),
]);
?>
<h1 style="margin:0 0 8px;font-size:26px;font-weight:normal;letter-spacing:1px;">Thank you, <?= e((string) ($order['customer_name'] ?? '')) ?></h1>
<p style="margin:0 0 20px;color:#6b6257;">We have received your order and will be in touch shortly.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0e8;margin:0 0 20px;"><tr>
<td style="padding:16px;"><div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#6b6257;">Order number</div>
<div style="font-size:22px;letter-spacing:2px;"><?= e($orderNumber) ?></div>
<div style="font-size:13px;color:#6b6257;">Placed <?= e((string) ($order['created_at'] ?? '')) ?></div></td></tr></table>
<?= mail_items_table($items) ?>
<?= mail_totals_table($order) ?>
<h2 style="margin:24px 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#6b6257;">Payment</h2>
<p style="margin:0 0 8px;"><?= e(mail_payment_method_label($method)) ?></p>
<?php if ($method === 'cod'): ?>
<p style="margin:0 0 8px;">Please keep <strong><?= e(money((string) ($order['grand_total'] ?? '0.00'))) ?></strong> ready for the courier. <?= e((string) setting('cod_note', '')) ?></p>
<?php else: ?>
<p style="margin:0 0 8px;">Please transfer <strong><?= e(money((string) ($order['grand_total'] ?? '0.00'))) ?></strong> to the account below if you have not already done so.</p>
<?php if ($accountLines !== []): ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0e8;margin:8px 0;"><tr><td style="padding:12px 16px;">
<?php foreach ($accountLines as $label => $value): ?>
<div style="font-size:14px;"><span style="color:#6b6257;"><?= e($label) ?>:</span> <?= e($value) ?></div>
<?php endforeach; ?>
</td></tr></table>
<?php endif; ?>
<p style="margin:0 0 8px;color:#6b6257;font-size:14px;"><?= e((string) setting('manual_payment_note', '')) ?></p>
<?php endif; ?>
<h2 style="margin:24px 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#6b6257;">Delivery address</h2>
<p style="margin:0 0 4px;"><?= e((string) ($order['customer_name'] ?? '')) ?><br><?= e(implode(', ', $address)) ?><br><?= e((string) ($order['customer_phone'] ?? '')) ?></p>
<p style="margin:12px 0 0;color:#6b6257;font-size:14px;">Estimated delivery: <?= e((string) setting('delivery_time', '2–4 working days')) ?>.</p>
<?= mail_button($orderUrl !== '' ? $orderUrl : $trackUrl, 'Track your order') ?>
<p style="margin:0;color:#6b6257;font-size:14px;">Questions? <?php if ($whatsapp !== ''): ?><a href="<?= e($whatsapp) ?>" style="color:#0a0a0a;">Message us on WhatsApp</a>.<?php else: ?>Reply to this email.<?php endif; ?> Easy exchange within our returns window.</p>
