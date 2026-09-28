<?php
defined('SKYFR') || exit;
$order = $data['order'] ?? [];
$items = $data['items'] ?? [];
$status = (string) ($data['status'] ?? $order['status'] ?? 'pending');
$orderNumber = (string) ($order['order_number'] ?? '');
$name = trim((string) ($order['customer_name'] ?? ''));
$firstName = $name === '' ? '' : explode(' ', $name)[0];
$greeting = 'Hello' . ($firstName !== '' ? ' ' . $firstName : '') . '.';
$custom = trim((string) ($data['message'] ?? ''));
$custom = $custom === '' ? '' : mb_strtoupper(mb_substr($custom, 0, 1)) . mb_substr($custom, 1);
$trackUrl = (string) ($data['track_url'] ?? SITE_URL . '/track');
$orderUrl = (string) ($data['order_url'] ?? '');
$courier = trim((string) ($order['courier_name'] ?? ''));
$tracking = trim((string) ($order['tracking_number'] ?? ''));
$trackingUrl = trim((string) ($order['tracking_url'] ?? ''));
if (!str_starts_with(strtolower($trackingUrl), 'https://')) {
    $trackingUrl = '';
}
$reason = trim((string) ($order['cancel_reason'] ?? ''));
$reason = $reason === '' || preg_match('/[.!?]$/u', $reason) ? $reason : $reason . '.';
$whatsapp = mail_whatsapp_url();
$method = (string) ($order['payment_method'] ?? 'cod');
$isCod = $method === 'cod';
$isPaid = (string) ($order['payment_status'] ?? '') === 'paid';
$total = money((string) ($order['grand_total'] ?? '0.00'));
$city = trim((string) ($order['city'] ?? ''));
$phone = trim((string) ($order['customer_phone'] ?? ''));
$deliveryTime = (string) setting('delivery_time', '2–4 working days');
$returnsDays = setting_int('returns_days', 7);
$reference = trim((string) ($order['payment_reference'] ?? ''));
$accountLines = $isCod ? [] : mail_payment_account_lines($method);
$heading = match ($status) {
    'confirmed' => 'Your order is confirmed',
    'shipped' => 'Your order is on its way',
    'delivered' => 'Delivered',
    'cancelled' => 'Order ' . $orderNumber . ' has been cancelled',
    'paid' => 'Payment received',
    'payment not verified' => 'We could not verify your payment',
    default => 'Order ' . $orderNumber,
};
$link = static fn (string $url, string $label): string => '<a href="' . e($url) . '" style="color:#0a0a0a;">' . e($label) . '</a>';
$helpLine = $whatsapp !== '' ? 'Questions? ' . $link($whatsapp, 'Message us on WhatsApp') . '.' : 'Questions? Reply to this email.';
?>
<h1 style="margin:0 0 8px;font-size:24px;font-weight:normal;letter-spacing:1px;"><?= e($heading) ?></h1>
<p style="margin:0 0 6px;color:#6b6257;font-size:14px;">Order <?= e($orderNumber) ?></p>
<?php if ($status === 'confirmed'): ?>
<p style="margin:0 0 16px;"><?= e($greeting) ?> Your order is confirmed and we are preparing it now. It leaves us within one working day and should reach <?= e($city !== '' ? $city : 'you') ?> in <?= e($deliveryTime) ?>. We will send the courier and tracking details as soon as it ships.</p>
<?php if ($isCod): ?>
<p style="margin:0 0 16px;">Please keep <strong><?= e($total) ?></strong> ready for the courier, who will call before delivering.</p>
<?php endif; ?>
<?= mail_button($trackUrl, 'Track your order') ?>
<?php elseif ($status === 'shipped'): ?>
<p style="margin:0 0 16px;"><?= e($greeting) ?> Your order has been handed to the courier<?= $city !== '' ? ' and is on its way to ' . e($city) : '' ?>. Delivery usually takes <?= e($deliveryTime) ?>.</p>
<?php if ($courier !== '' || $tracking !== ''): ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0e8;margin:0 0 16px;"><tr><td style="padding:16px;">
<?php if ($courier !== ''): ?><div><span style="color:#6b6257;">Courier:</span> <?= e($courier) ?></div><?php endif; ?>
<?php if ($tracking !== ''): ?><div><span style="color:#6b6257;">Tracking number:</span> <strong><?= e($tracking) ?></strong></div><?php else: ?><div style="color:#6b6257;">The tracking number will follow shortly.</div><?php endif; ?>
<?php if ($trackingUrl !== ''): ?><div style="padding-top:6px;"><?= $link($trackingUrl, 'Track with the courier') ?></div><?php endif; ?>
</td></tr></table>
<?php endif; ?>
<p style="margin:0 0 16px;"><?php if ($isCod): ?>Please keep <strong><?= e($total) ?></strong> ready in cash. <?php endif; ?>The rider will call<?= $phone !== '' ? ' ' . e($phone) : '' ?> before delivering, so keep that number reachable.</p>
<?= mail_button($trackUrl, 'Track your order') ?>
<?php elseif ($status === 'delivered'): ?>
<p style="margin:0 0 16px;"><?= e($greeting) ?> Your order has been delivered. We hope you love it.</p>
<?php if ($items !== []): ?>
<p style="margin:0 0 8px;">If you have a moment, a few honest lines help the next person choose:</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
<?php foreach ($items as $item): ?>
<?php $slug = trim((string) ($item['product_slug'] ?? '')); ?>
<tr><td style="padding:4px 0;font-size:14px;"><?= e((string) ($item['product_name'] ?? '')) ?><?= $slug !== '' ? ' · ' . $link(SITE_URL . '/product/' . $slug . '#reviews', 'Write a review') : '' ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<p style="margin:0 0 16px;color:#6b6257;font-size:14px;">Not right? A sealed bottle can be exchanged within <?= e((string) $returnsDays) ?> days of delivery, and anything damaged or wrong is replaced if you tell us within 48 hours. <?= $link(SITE_URL . '/returns', 'Read the policy') ?>.</p>
<?= mail_button(SITE_URL . '/shop', 'Shop again') ?>
<?php elseif ($status === 'cancelled'): ?>
<p style="margin:0 0 16px;"><?= e($greeting) ?> Your order has been cancelled<?= $reason !== '' ? '. ' . e($reason) : '.' ?></p>
<?php if ($isPaid): ?>
<p style="margin:0 0 16px;">Your payment of <strong><?= e($total) ?></strong> will be refunded to the account it came from within 5 working days.</p>
<?php else: ?>
<p style="margin:0 0 16px;">Nothing is owed.</p>
<?php endif; ?>
<p style="margin:0 0 16px;">If this was not what you expected, <?= $whatsapp !== '' ? $link($whatsapp, 'message us on WhatsApp') : 'reply to this email' ?> and we will help you place it again.</p>
<?= mail_button(SITE_URL . '/shop', 'Shop again') ?>
<?php elseif ($status === 'paid'): ?>
<p style="margin:0 0 16px;"><?= e($greeting) ?> We have received your payment of <strong><?= e($total) ?></strong><?= $reference !== '' ? ' (transaction ' . e($reference) . ')' : '' ?>. Your order is confirmed and leaves us within one working day; delivery usually takes <?= e($deliveryTime) ?>.</p>
<?= mail_button($trackUrl, 'Track your order') ?>
<?php elseif ($status === 'payment not verified'): ?>
<p style="margin:0 0 16px;"><?= e($greeting) ?> <?= e($custom !== '' ? preg_replace('/\s*Please check the transfer.*$/s', '', $custom) : "We couldn't match your receipt to a payment on our account.") ?></p>
<p style="margin:0 0 8px;">Please check that the transfer went to the account below for exactly <strong><?= e($total) ?></strong>, then upload a clear screenshot of the completed transfer from your order page.</p>
<?php if ($accountLines !== []): ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0e8;margin:8px 0 16px;"><tr><td style="padding:12px 16px;">
<?php foreach ($accountLines as $label => $value): ?>
<div style="font-size:14px;"><span style="color:#6b6257;"><?= e($label) ?>:</span> <?= e($value) ?></div>
<?php endforeach; ?>
</td></tr></table>
<?php endif; ?>
<?= mail_button($orderUrl !== '' ? $orderUrl : $trackUrl, 'Upload a new receipt') ?>
<?php else: ?>
<p style="margin:0 0 16px;"><?= e($greeting) ?> <?= e($custom !== '' ? $custom : mail_status_sentence($status)) ?></p>
<?= mail_button($trackUrl, 'Track your order') ?>
<?php endif; ?>
<p style="margin:0;color:#6b6257;font-size:14px;"><?= $helpLine ?></p>
