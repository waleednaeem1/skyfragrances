<?php
defined('SKYFR') || exit;
$balance = order_balance($order);
?>
<?php if ($payments !== [] || $balance['due'] > 0): ?>
<div class="adm-paybox">
  <div class="adm-paybox__figures">
    <div><span class="adm-paybox__label">Total</span><span class="adm-paybox__value"><?= e(money(money_from_paisa($balance['total']))) ?></span></div>
    <div><span class="adm-paybox__label">Paid</span><span class="adm-paybox__value"><?= e(money(money_from_paisa($balance['paid']))) ?></span></div>
    <div><span class="adm-paybox__label">Balance due</span><span class="adm-paybox__value<?= $balance['due'] > 0 ? ' adm-paybox__value--due' : '' ?>"><?= e(money(money_from_paisa($balance['due']))) ?></span></div>
  </div>
<?php if ($payments !== []): ?>
  <ol class="adm-paybox__list">
<?php foreach ($payments as $payment): ?>
    <li><span><?= e(adm_order_datetime((string) $payment['paid_at'])) ?><?= !empty($payment['note']) ? ' <span class="adm-muted">· ' . e((string) $payment['note']) . '</span>' : '' ?></span><strong class="adm-money"><?= e(money((string) $payment['amount'])) ?></strong></li>
<?php endforeach; ?>
  </ol>
<?php endif; ?>
</div>
<?php endif; ?>
