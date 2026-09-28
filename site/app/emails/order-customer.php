<?php
defined('SKYFR') || exit;
$order = $data['order'] ?? [];
$items = $data['items'] ?? [];
$orderNumber = (string) ($order['order_number'] ?? '');
$method = (string) ($order['payment_method'] ?? 'cod');
$name = trim((string) ($order['customer_name'] ?? ''));
$firstName = $name === '' ? '' : explode(' ', $name)[0];
$trackUrl = (string) ($data['track_url'] ?? SITE_URL . '/track');
$orderUrl = (string) ($data['order_url'] ?? '');
$whatsapp = mail_whatsapp_url();
$accountLines = $method === 'cod' ? [] : mail_payment_account_lines($method);
$total = money((string) ($order['grand_total'] ?? '0.00'));
$deliveryTime = (string) setting('delivery_time', '2–4 working days');
$holdHours = setting_int('manual_hold_hours', 48);
$returnsDays = setting_int('returns_days', 7);
$reference = trim((string) ($order['payment_reference'] ?? ''));
$city = trim((string) ($order['city'] ?? ''));
$address = array_filter([
    (string) ($order['address'] ?? ''),
    trim($city . ' ' . (string) ($order['postal_code'] ?? '')),
]);
$note = trim((string) ($order['customer_note'] ?? ''));
?>
<h1 style="margin:0 0 8px;font-size:26px;font-weight:normal;letter-spacing:1px;">Thank you<?= $firstName !== '' ? ', ' . e($firstName) : '' ?>.</h1>
<p style="margin:0 0 20px;">We have your order and are preparing it now. Keep this email: it is your receipt, and the order number below is all we need if you ever want to ask about it.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0e8;margin:0 0 20px;"><tr>
<td style="padding:16px;"><div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#6b6257;">Order number</div>
<div style="font-size:22px;letter-spacing:2px;"><?= e($orderNumber) ?></div>
<div style="font-size:13px;color:#6b6257;">Placed <?= e(date_long((string) ($order['created_at'] ?? ''))) ?></div></td></tr></table>
<?= mail_items_table($items) ?>
<?= mail_totals_table($order) ?>
<h2 style="margin:24px 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#6b6257;">Payment</h2>
<?php if ($method === 'cod'): ?>
<p style="margin:0 0 8px;">Cash on Delivery. Please keep <strong><?= e($total) ?></strong> ready for the courier, who will call before delivering. <?= e((string) setting('cod_note', '')) ?></p>
<?php else: ?>
<p style="margin:0 0 8px;"><?= e(mail_payment_method_label($method)) ?>. Transfer exactly <strong><?= e($total) ?></strong> to the account below if you have not already done so. We verify every transfer by hand, usually within a few hours, and dispatch your order as soon as it clears. An order left unpaid for <?= e((string) $holdHours) ?> hours is cancelled automatically.</p>
<?php if ($accountLines !== []): ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0e8;margin:8px 0;"><tr><td style="padding:12px 16px;">
<?php foreach ($accountLines as $label => $value): ?>
<div style="font-size:14px;"><span style="color:#6b6257;"><?= e($label) ?>:</span> <?= e($value) ?></div>
<?php endforeach; ?>
</td></tr></table>
<?php endif; ?>
<?php if ($reference !== ''): ?>
<p style="margin:0 0 8px;font-size:14px;">Transaction ID received: <strong><?= e($reference) ?></strong>. We will confirm by email and WhatsApp once it is verified.</p>
<?php else: ?>
<p style="margin:0 0 8px;color:#6b6257;font-size:14px;"><?= e((string) setting('manual_payment_note', '')) ?> You can add the transaction ID and screenshot from your order page at any time.</p>
<?php endif; ?>
<?php endif; ?>
<h2 style="margin:24px 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#6b6257;">Delivery</h2>
<p style="margin:0 0 4px;"><?= e($name) ?><br><?= e(implode(', ', $address)) ?><br><?= e((string) ($order['customer_phone'] ?? '')) ?></p>
<?php if ($note !== ''): ?>
<p style="margin:8px 0 0;color:#6b6257;font-size:14px;">Your note: <?= e($note) ?></p>
<?php endif; ?>
<p style="margin:12px 0 0;font-size:14px;">Estimated delivery: <?= e($deliveryTime) ?> from dispatch<?= $city !== '' ? ' to ' . e($city) : '' ?>. The courier calls before delivering, so keep this number reachable.</p>
<?= mail_button($orderUrl !== '' ? $orderUrl : $trackUrl, $orderUrl !== '' ? 'View your order' : 'Track your order') ?>
<p style="margin:0 0 8px;color:#6b6257;font-size:14px;">Need to change anything? <?php if ($whatsapp !== ''): ?><a href="<?= e($whatsapp) ?>" style="color:#0a0a0a;">Message us on WhatsApp</a><?php else: ?>Reply to this email<?php endif; ?> before the parcel ships and we will sort it out.</p>
<p style="margin:0;color:#6b6257;font-size:14px;">Sealed bottles can be exchanged within <?= e((string) $returnsDays) ?> days of delivery; anything damaged or wrong is replaced if you tell us within 48 hours. The full policy is at <a href="<?= e(SITE_URL . '/returns') ?>" style="color:#0a0a0a;"><?= e(SITE_URL . '/returns') ?></a>.</p>
