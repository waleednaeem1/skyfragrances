<?php
defined('SKYFR') || exit;
$moneyHtml = static function (string $value, array $extra = []): string {
    ob_start();
    partial_admin('money.php', ['value' => $value] + $extra);
    return (string) ob_get_clean();
};
?>
<section class="adm-card" id="totals">
  <div class="adm-card__head"><h2 class="adm-card__title">Totals</h2></div>
  <table class="adm-totals">
    <tr><td>Subtotal</td><td><?= $moneyHtml((string) $order['subtotal']) ?></td></tr>
<?php if (money_paisa((string) $order['discount_total']) > 0): ?>
    <tr><td>Discount<?php if (!empty($order['coupon_code'])): ?> (<?= e((string) $order['coupon_code']) ?><?= $order['coupon_type'] === 'percent' ? ' · ' . e(rtrim(rtrim((string) $order['coupon_value'], '0'), '.')) . '%' : '' ?>)<?php endif; ?></td><td><?= $moneyHtml((string) $order['discount_total'], ['negative' => true]) ?></td></tr>
<?php endif; ?>
    <tr><td>Shipping</td><td><?= $moneyHtml((string) $order['shipping_fee'], ['free_when_zero' => true]) ?></td></tr>
<?php if (money_paisa((string) $order['cod_fee']) > 0): ?>
    <tr><td>COD fee</td><td><?= $moneyHtml((string) $order['cod_fee']) ?></td></tr>
<?php endif; ?>
    <tr class="adm-totals__grand"><td>Grand total</td><td><?= $moneyHtml((string) $order['grand_total'], ['strong' => true]) ?></td></tr>
<?php $balance = order_balance($order); ?>
<?php if ($balance['paid'] > 0): ?>
    <tr><td>Paid</td><td><?= $moneyHtml(money_from_paisa($balance['paid'])) ?></td></tr>
<?php endif; ?>
<?php if ($order['status'] !== 'cancelled' && $order['payment_status'] !== 'refunded'): ?>
    <tr><td>Balance due</td><td><?= $balance['due'] > 0 ? $moneyHtml(money_from_paisa($balance['due']), ['low' => true]) . ($order['payment_method'] === 'cod' ? ' <span class="adm-note">collect on delivery</span>' : '') : $moneyHtml('0.00') ?></td></tr>
<?php endif; ?>
  </table>
</section>
