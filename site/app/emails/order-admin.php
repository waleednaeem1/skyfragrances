<?php
defined('SKYFR') || exit;
$order = $data['order'] ?? [];
$items = $data['items'] ?? [];
$flags = $data['flags'] ?? [];
$orderNumber = (string) ($order['order_number'] ?? '');
$method = (string) ($order['payment_method'] ?? 'cod');
$phone = (string) ($order['customer_phone'] ?? '');
$phoneDigits = preg_replace('/\D+/', '', (string) ($order['phone_normalized'] ?? $phone)) ?? '';
$adminUrl = (string) ($data['admin_url'] ?? SITE_URL . '/admin/orders/' . $orderNumber);
$transactionRef = (string) ($data['transaction_ref'] ?? $order['payment_reference'] ?? '');
$note = trim((string) ($order['customer_note'] ?? ''));
$email = trim((string) ($order['customer_email'] ?? ''));
$paymentStatus = str_replace('_', ' ', (string) ($order['payment_status'] ?? 'unpaid'));
$sectionStyle = 'margin:16px 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#6b6257;';
?>
<h1 style="margin:0 0 8px;font-size:24px;font-weight:normal;letter-spacing:1px;">New order <?= e($orderNumber) ?></h1>
<p style="margin:0 0 16px;font-size:18px;"><strong><?= e(money((string) ($order['grand_total'] ?? '0.00'))) ?></strong> · <?= e(mail_payment_method_label($method)) ?> · <?= e((string) ($order['city'] ?? '')) ?></p>
<?php if ($flags !== []): ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fdecea;margin:0 0 16px;"><tr><td style="padding:12px 16px;color:#8a1c1c;font-size:14px;">
<div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;">Please check before confirming</div>
<?php foreach ($flags as $flag): ?>
<div style="padding-top:4px;"><?= e((string) $flag) ?></div>
<?php endforeach; ?>
</td></tr></table>
<?php endif; ?>
<?= mail_button($adminUrl, 'Open in admin') ?>
<h2 style="<?= $sectionStyle ?>">Customer</h2>
<p style="margin:0 0 4px;"><?= e((string) ($order['customer_name'] ?? '')) ?></p>
<p style="margin:0 0 4px;"><a href="tel:<?= e($phoneDigits !== '' ? '+' . $phoneDigits : $phone) ?>" style="color:#0a0a0a;"><?= e($phone) ?></a>
<?php if ($phoneDigits !== ''): ?> · <a href="https://wa.me/<?= e($phoneDigits) ?>" style="color:#0a0a0a;">WhatsApp</a><?php endif; ?></p>
<?php if ($email !== ''): ?><p style="margin:0 0 4px;"><a href="mailto:<?= e($email) ?>" style="color:#0a0a0a;"><?= e($email) ?></a></p><?php endif; ?>
<h2 style="<?= $sectionStyle ?>">Delivery address</h2>
<p style="margin:0;white-space:pre-line;"><?= e((string) ($order['address'] ?? '')) ?><br><?= e(trim((string) ($order['city'] ?? '') . ' ' . (string) ($order['postal_code'] ?? ''))) ?></p>
<?php if ($note !== ''): ?>
<h2 style="<?= $sectionStyle ?>">Customer note</h2>
<p style="margin:0;white-space:pre-line;"><?= e($note) ?></p>
<?php endif; ?>
<h2 style="<?= $sectionStyle ?>">Items</h2>
<?= mail_items_table($items) ?>
<?= mail_totals_table($order) ?>
<h2 style="<?= $sectionStyle ?>">Payment</h2>
<p style="margin:0;"><?= e(mail_payment_method_label($method)) ?> · <?= e($paymentStatus) ?><?php if ($transactionRef !== ''): ?> · Transaction ID <?= e($transactionRef) ?><?php endif; ?></p>
<?php if ($method !== 'cod'): ?>
<p style="margin:8px 0 0;color:#6b6257;font-size:14px;">Check the screenshot against the account statement before marking it paid. The order is not dispatched until it is verified.</p>
<?php endif; ?>
