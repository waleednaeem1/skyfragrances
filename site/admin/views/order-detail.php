<?php
defined('SKYFR') || exit;
$detailPath = '/admin/orders/' . $order['order_number'];
$badge = static function (string $status, array $extra = []): string {
    ob_start();
    partial_admin('badge.php', ['status' => $status] + $extra);
    return (string) ob_get_clean();
};
$moneyHtml = static function (string $value, array $extra = []): string {
    ob_start();
    partial_admin('money.php', ['value' => $value] + $extra);
    return (string) ob_get_clean();
};
$copyAddress = implode("\n", array_filter([$order['customer_name'], $order['address'], $order['city'], $order['postal_code'], $order['customer_phone']]));
$paymentLabel = adm_order_payment_label((string) $order['payment_status']);
$whatsappPrimary = $whatsapp[$whatsappDefault] ?? null;
?>
<?php if (!$reconciles): ?>
<div class="adm-banner adm-banner--warn" role="alert"><strong>Totals do not reconcile.</strong> Do not ship. Contact the developer. Status changes are disabled on this order.</div>
<?php endif; ?>
<?php if ($lastStatusMailFailed): ?>
<div class="adm-banner adm-banner--warn" role="status">The last status email to the customer could not be sent. Use WhatsApp to keep them informed.</div>
<?php endif; ?>
<?php if ($holdDays !== null): ?>
<div class="adm-banner adm-banner--warn" role="status">Unpaid for <?= e((string) $holdDays) ?> day<?= $holdDays === 1 ? '' : 's' ?>. Stock is held for this transfer order until it is paid or cancelled.</div>
<?php endif; ?>
<?php if ($duplicateRef !== null): ?>
<div class="adm-banner adm-banner--warn" role="status">This transaction ID is already on <a href="<?= e(url('/admin/orders/' . $duplicateRef)) ?>"><?= e((string) $duplicateRef) ?></a>. Check before marking paid.</div>
<?php endif; ?>

<?php partial_admin('page-header.php', [
    'title' => (string) $order['order_number'],
    'subtitle' => 'Placed ' . adm_order_datetime((string) $order['created_at']) . ' · ' . (int) $order['item_count'] . ' item' . ((int) $order['item_count'] === 1 ? '' : 's') . ' · ' . money((string) $order['grand_total']),
    'back' => '/admin/orders',
    'back_label' => 'Orders',
    'actions' => [
        ['label' => 'Invoice', 'href' => $detailPath . '/invoice', 'external' => true, 'size' => 'sm'],
        ['label' => 'Packing slip', 'href' => $detailPath . '/packing-slip', 'external' => true, 'size' => 'sm'],
    ],
]); ?>
<p class="adm-cluster">
  <?= $badge((string) $order['status'], ['label' => adm_order_status_label((string) $order['status'])]) ?>
  <?= $badge((string) $order['payment_method'], ['label' => payment_method_label((string) $order['payment_method'])]) ?>
  <?= $badge((string) $order['payment_status'], ['label' => $paymentLabel]) ?>
<?php if ($latestProof !== null): ?>
  <span class="adm-muted">&#128206; proof attached</span>
<?php endif; ?>
</p>

<section class="adm-card" id="payment">
  <div class="adm-card__head"><h2 class="adm-card__title">Payment</h2><?= $badge((string) $order['payment_status'], ['label' => $paymentLabel, 'small' => true]) ?></div>
  <dl class="adm-list">
    <div><dt class="adm-muted">Method</dt><dd><?= e(payment_method_label((string) $order['payment_method'])) ?></dd></div>
<?php if (!empty($order['payment_account_snapshot'])): ?>
    <div><dt class="adm-muted">Paid to</dt><dd><?= e((string) $order['payment_account_snapshot']) ?></dd></div>
<?php endif; ?>
<?php if (!empty($order['payment_reference'])): ?>
    <div><dt class="adm-muted">Transaction ID</dt><dd><span class="adm-mono"><?= e((string) $order['payment_reference']) ?></span> <button type="button" class="adm-btn adm-btn--text adm-btn--sm" data-copy="<?= e((string) $order['payment_reference']) ?>">Copy</button></dd></div>
<?php endif; ?>
<?php if (!empty($order['paid_at'])): ?>
    <div><dt class="adm-muted">Paid at</dt><dd><?= e(adm_order_datetime((string) $order['paid_at'])) ?></dd></div>
<?php endif; ?>
  </dl>
<?php if ($latestProof !== null): ?>
  <div class="adm-media">
<?php if ($proofFileOk): ?>
    <a href="<?= e(url($detailPath . '/proof')) ?>" target="_blank" rel="noopener"><img class="adm-thumb" style="width:160px;height:auto;max-height:240px;object-fit:contain" src="<?= e(url($detailPath . '/proof?w=320')) ?>" alt="Payment proof for <?= e((string) $order['order_number']) ?>" width="160" loading="lazy"></a>
<?php endif; ?>
    <div class="adm-media__body">
      <p class="adm-media__title"><?= $proofFileOk ? 'Payment screenshot' : 'Proof missing — request on WhatsApp' ?></p>
      <p class="adm-media__meta"><?= e((string) ($latestProof['original_name'] ?? '')) ?> · <?= e(upload_human_size((int) $latestProof['byte_size'])) ?> · uploaded <?= e(adm_order_datetime((string) $latestProof['created_at'])) ?><?= $proofCount > 1 ? ' · ' . e((string) $proofCount) . ' uploads' : '' ?></p>
<?php if (!empty($latestProof['sender_name']) || !empty($latestProof['amount_claimed'])): ?>
      <p class="adm-media__meta"><?= !empty($latestProof['sender_name']) ? 'Sender: ' . e((string) $latestProof['sender_name']) : '' ?><?= !empty($latestProof['amount_claimed']) ? ' · Claimed ' . e(money((string) $latestProof['amount_claimed'])) : '' ?></p>
<?php endif; ?>
<?php if ($proofFileOk): ?>
      <a class="adm-btn adm-btn--text adm-btn--sm" href="<?= e(url($detailPath . '/proof')) ?>" target="_blank" rel="noopener">Open full size</a>
<?php endif; ?>
    </div>
  </div>
<?php elseif ($isManual): ?>
  <p class="adm-muted">No payment proof uploaded.</p>
<?php endif; ?>
<?php if (in_array($order['payment_status'], ['unpaid', 'awaiting_verification', 'failed'], true)): ?>
  <form method="post" action="<?= e(url($detailPath . '/payment')) ?>" class="adm-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="mark_paid">
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'money', 'name' => 'amount', 'id' => 'pay-amount', 'label' => 'Amount received', 'value' => money_attr((string) $order['grand_total']), 'help' => 'Order total is ' . money((string) $order['grand_total']) . '. A different amount is recorded in the timeline.', 'compact' => true]); ?>
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'note', 'id' => 'pay-note', 'label' => 'Reference / notes', 'value' => '', 'maxlength' => 200, 'compact' => true]); ?>
    </div>
    <?php partial_admin('button.php', ['label' => $order['payment_status'] === 'awaiting_verification' ? 'Approve payment — mark as paid' : 'Mark as paid', 'variant' => 'gold', 'confirm' => "Mark this order as paid?\nThis records that you received " . money((string) $order['grand_total']) . ' by ' . payment_method_label((string) $order['payment_method']) . '. The order status stays ' . adm_order_status_label((string) $order['status']) . '.', 'confirm_label' => 'Yes, mark paid']); ?>
  </form>
<?php if ($isManual && $order['payment_status'] !== 'failed'): ?>
  <form method="post" action="<?= e(url($detailPath . '/payment')) ?>" class="adm-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="reject">
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'note', 'id' => 'reject-note', 'label' => 'Reason for rejecting the proof', 'value' => '', 'required' => true, 'maxlength' => 200, 'placeholder' => 'e.g. amount on screenshot is Rs. 4,000 not Rs. 4,950', 'compact' => true]); ?>
    <?php partial_admin('button.php', ['label' => 'Reject proof', 'variant' => 'danger', 'size' => 'sm', 'confirm' => "Reject this payment proof?\nThe proof is kept, the payment goes back to not verified and you can send the customer the WhatsApp message.", 'confirm_label' => 'Yes, reject']); ?>
  </form>
<?php endif; ?>
<?php elseif ($order['payment_status'] === 'paid'): ?>
  <details>
    <summary class="adm-muted">Record a refund</summary>
    <form method="post" action="<?= e(url($detailPath . '/payment')) ?>" class="adm-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="refund">
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'note', 'id' => 'refund-note', 'label' => 'Refund reason', 'value' => '', 'required' => true, 'maxlength' => 200, 'compact' => true]); ?>
      <?php partial_admin('button.php', ['label' => 'Mark refunded', 'variant' => 'danger', 'size' => 'sm', 'confirm' => "Mark this payment as refunded?\nThis only records the refund. Move the money in your bank or wallet app yourself.", 'confirm_label' => 'Yes, mark refunded']); ?>
    </form>
  </details>
<?php endif; ?>
</section>

<section class="adm-card" id="whatsapp">
  <div class="adm-card__head"><h2 class="adm-card__title">WhatsApp</h2></div>
<?php if (!$phoneDialable): ?>
  <p class="adm-muted">Phone number cannot be dialled.</p>
<?php else: ?>
<?php if ($whatsappPrimary !== null): ?>
  <?php partial_admin('button.php', ['label' => 'Send "' . $whatsappPrimary['label'] . '" message', 'href' => $whatsappPrimary['href'], 'external' => true, 'variant' => 'gold', 'block' => true]); ?>
<?php endif; ?>
  <details>
    <summary class="adm-muted">Other messages</summary>
    <div class="adm-stack">
<?php foreach ($whatsapp as $key => $message): ?>
<?php if ($key === $whatsappDefault) { continue; } ?>
      <?php partial_admin('button.php', ['label' => $message['label'], 'href' => $message['href'], 'external' => true, 'variant' => 'ghost', 'size' => 'sm']); ?>
<?php endforeach; ?>
    </div>
  </details>
  <p class="adm-note">WhatsApp opens with the text ready — nothing is sent until you tap send.</p>
<?php endif; ?>
</section>

<section class="adm-card adm-card--flush" id="items">
  <div class="adm-card__head"><h2 class="adm-card__title">Items</h2></div>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <caption class="u-sr-only">Order items</caption>
      <thead><tr><th scope="col" class="adm-table__th">Product</th><th scope="col" class="adm-table__th">SKU</th><th scope="col" class="adm-table__th adm-table__th--right">Unit</th><th scope="col" class="adm-table__th adm-table__th--right">Qty</th><th scope="col" class="adm-table__th adm-table__th--right">Line</th></tr></thead>
      <tbody>
<?php foreach ($items as $item): ?>
        <tr class="adm-table__row">
          <td class="adm-table__td"><div class="adm-media"><img class="adm-thumb adm-thumb--sm" src="<?= e($item['image_url']) ?>" alt="" width="48" loading="lazy"><div class="adm-media__body"><p class="adm-media__title"><?php if (!$item['product_deleted']): ?><a href="<?= e(url('/product/' . $item['product_slug'])) ?>" target="_blank" rel="noopener"><?= e((string) $item['product_name']) ?></a><?php else: ?><?= e((string) $item['product_name']) ?> <span class="adm-muted">product deleted</span><?php endif; ?></p><p class="adm-media__meta"><?= e((string) $item['size_label']) ?></p></div></div></td>
          <td class="adm-table__td adm-mono"><?= e((string) $item['sku']) ?></td>
          <td class="adm-table__td adm-table__td--right adm-table__td--money"><?= $moneyHtml((string) $item['unit_price_charged']) ?><?php if ($item['live_price'] !== null): ?><br><span class="adm-note">now <?= e(money($item['live_price'])) ?></span><?php endif; ?></td>
          <td class="adm-table__td adm-table__td--right adm-num"><?= (int) $item['quantity'] ?></td>
          <td class="adm-table__td adm-table__td--right adm-table__td--money"><?= $moneyHtml((string) $item['line_total'], ['strong' => true]) ?></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <ul class="adm-cards">
<?php foreach ($items as $item): ?>
    <li class="adm-rowcard"><div class="adm-rowcard__body"><div class="adm-media"><img class="adm-thumb adm-thumb--sm" src="<?= e($item['image_url']) ?>" alt="" width="48" loading="lazy"><div class="adm-media__body"><p class="adm-media__title"><?= e((string) $item['product_name']) ?> <?= e((string) $item['size_label']) ?><?= $item['product_deleted'] ? ' <span class="adm-muted">(deleted)</span>' : '' ?></p><p class="adm-media__meta"><span class="adm-mono"><?= e((string) $item['sku']) ?></span> · <?= e(money((string) $item['unit_price_charged'])) ?> × <?= (int) $item['quantity'] ?><?= $item['live_price'] !== null ? ' · now ' . e(money($item['live_price'])) : '' ?></p></div></div><div class="adm-rowcard__figures"><span class="adm-rowcard__money"><?= $moneyHtml((string) $item['line_total'], ['strong' => true]) ?></span></div></div></li>
<?php endforeach; ?>
  </ul>
</section>

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
<?php if ($order['payment_status'] === 'paid'): ?>
    <tr><td>Paid</td><td><?= $moneyHtml((string) $order['grand_total']) ?></td></tr>
    <tr><td>Balance due</td><td><?= $moneyHtml('0.00') ?></td></tr>
<?php elseif ($order['payment_method'] === 'cod' && $order['status'] !== 'cancelled'): ?>
    <tr><td>Balance due</td><td><?= $moneyHtml((string) $order['grand_total'], ['low' => true]) ?> <span class="adm-note">collect on delivery</span></td></tr>
<?php endif; ?>
  </table>
</section>

<section class="adm-card" id="status">
  <div class="adm-card__head"><h2 class="adm-card__title">Status</h2><?= $badge((string) $order['status'], ['label' => adm_order_status_label((string) $order['status']), 'small' => true]) ?></div>
<?php if ($legalTargets === []): ?>
  <p class="adm-muted">This order is <?= e(strtolower(adm_order_status_label((string) $order['status']))) ?>. Nothing further can change; add a note if something needs recording.</p>
<?php elseif (!$reconciles): ?>
  <p class="adm-muted">Status changes are disabled until the totals reconcile.</p>
<?php else: ?>
  <div class="adm-cluster">
<?php foreach ($legalTargets as $target): ?>
<?php if ($target === 'cancelled' || $target === 'shipped' || order_transition_requires_reason((string) $order['status'], $target)) { continue; } ?>
    <?php partial_admin('button.php', [
        'label' => 'Mark ' . strtolower(adm_order_status_label($target)),
        'variant' => ($primaryAction['to'] ?? '') === $target ? 'gold' : 'ghost',
        'post' => $detailPath . '/status',
        'fields' => ['to' => $target],
        'confirm' => $target === 'delivered' ? "Mark this order as delivered?\nThis cannot be undone. A wrong tap is corrected with a note." : null,
        'confirm_label' => 'Yes, mark delivered',
    ]); ?>
<?php endforeach; ?>
<?php if (in_array('shipped', $legalTargets, true)): ?>
    <a class="adm-btn adm-btn--<?= ($primaryAction['to'] ?? '') === 'shipped' ? 'gold' : 'ghost' ?>" href="#shipping">Mark shipped…</a>
<?php endif; ?>
  </div>
<?php foreach ($legalTargets as $target): ?>
<?php if ($target === 'cancelled' || !order_transition_requires_reason((string) $order['status'], $target)) { continue; } ?>
  <form method="post" action="<?= e(url($detailPath . '/status')) ?>" class="adm-form">
    <?= csrf_field() ?>
    <input type="hidden" name="to" value="<?= e($target) ?>">
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'note', 'id' => 'back-' . $target, 'label' => 'Reason to move back to ' . adm_order_status_label($target), 'value' => '', 'required' => true, 'maxlength' => 200, 'compact' => true]); ?>
    <?php partial_admin('button.php', ['label' => 'Back to ' . strtolower(adm_order_status_label($target)), 'variant' => 'ghost', 'size' => 'sm', 'confirm' => 'Move this order back to ' . adm_order_status_label($target) . "?\n" . ($order['status'] === 'shipped' ? 'The courier and tracking details will be cleared.' : 'Use this only to fix a wrong tap.'), 'confirm_label' => 'Yes, move back']); ?>
  </form>
<?php endforeach; ?>
<?php endif; ?>
</section>

<section class="adm-card" id="customer">
  <div class="adm-card__head"><h2 class="adm-card__title">Customer</h2><?php if ($previousCount > 0): ?><span class="adm-muted"><?= e((string) ($previousCount + 1)) ?><?= match ($previousCount + 1) { 2 => 'nd', 3 => 'rd', default => 'th' } ?> order</span><?php endif; ?></div>
  <p><strong><?= e((string) $order['customer_name']) ?></strong></p>
  <p><a href="tel:<?= e((string) $order['phone_normalized']) ?>"><?= e((string) $order['customer_phone']) ?></a> <button type="button" class="adm-btn adm-btn--text adm-btn--sm" data-copy="<?= e((string) $order['customer_phone']) ?>">Copy</button></p>
  <p><?php if (!empty($order['customer_email'])): ?><a href="mailto:<?= e((string) $order['customer_email']) ?>"><?= e((string) $order['customer_email']) ?></a><?php else: ?><span class="adm-muted">— no email provided</span><?php endif; ?></p>
  <div class="adm-note"><?= nl2br(e((string) $order['address'])) ?><br><?= e((string) $order['city']) ?><?= !empty($order['postal_code']) ? ' ' . e((string) $order['postal_code']) : '' ?></div>
  <p class="adm-cluster">
    <a class="adm-btn adm-btn--ghost adm-btn--sm" href="tel:<?= e((string) $order['phone_normalized']) ?>">Call</a>
    <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" data-copy="<?= e($copyAddress) ?>">Copy address</button>
  </p>
<?php if (!empty($order['customer_note'])): ?>
  <blockquote class="adm-note">&ldquo;<?= nl2br(e((string) $order['customer_note'])) ?>&rdquo;</blockquote>
<?php endif; ?>
  <p class="adm-muted">Placed <?= e(adm_order_datetime((string) $order['created_at'])) ?> · <?= $isMobileUa ? 'Mobile' : 'Desktop' ?></p>
<?php if ($previousOrders !== []): ?>
  <p class="adm-muted">Previous: <?php foreach ($previousOrders as $i => $prev): ?><?= $i > 0 ? ', ' : '' ?><a href="<?= e(url('/admin/orders/' . $prev['order_number'])) ?>"><?= e((string) $prev['order_number']) ?></a><?php endforeach; ?></p>
<?php endif; ?>
<?php if (!$contactFrozen): ?>
  <details>
    <summary class="adm-muted">Correct customer details</summary>
    <form method="post" action="<?= e(url($detailPath . '/notes')) ?>" class="adm-form" data-guard>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="contact">
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'customer_name', 'label' => 'Name', 'value' => $order['customer_name'], 'required' => true, 'maxlength' => 120]); ?>
      <div class="adm-field--pair">
        <?php partial_admin('field.php', ['type' => 'phone', 'name' => 'customer_phone', 'label' => 'Phone', 'value' => $order['customer_phone'], 'required' => true, 'maxlength' => 24]); ?>
        <?php partial_admin('field.php', ['type' => 'email', 'name' => 'customer_email', 'label' => 'Email', 'value' => (string) ($order['customer_email'] ?? ''), 'maxlength' => 190]); ?>
      </div>
      <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'address', 'label' => 'Address', 'value' => $order['address'], 'required' => true, 'rows' => 3, 'maxlength' => 400]); ?>
      <div class="adm-field--pair">
        <?php partial_admin('field.php', ['type' => 'text', 'name' => 'city', 'label' => 'City', 'value' => $order['city'], 'required' => true, 'maxlength' => 80]); ?>
        <?php partial_admin('field.php', ['type' => 'text', 'name' => 'postal_code', 'label' => 'Postal code', 'value' => (string) ($order['postal_code'] ?? ''), 'maxlength' => 12]); ?>
      </div>
      <?php partial_admin('button.php', ['label' => 'Save customer details', 'variant' => 'ghost', 'size' => 'sm']); ?>
    </form>
  </details>
<?php else: ?>
  <p class="adm-note">Customer details are frozen — the parcel is already labelled.</p>
<?php endif; ?>
</section>

<section class="adm-card" id="shipping">
  <div class="adm-card__head"><h2 class="adm-card__title">Shipping</h2><?php if (!empty($order['shipped_at'])): ?><span class="adm-muted">Shipped <?= e(adm_order_datetime((string) $order['shipped_at'])) ?></span><?php endif; ?></div>
<?php if (!empty($order['tracking_number'])): ?>
  <p><?= e((string) $order['courier_name']) ?> · <span class="adm-mono"><?= e((string) $order['tracking_number']) ?></span> <button type="button" class="adm-btn adm-btn--text adm-btn--sm" data-copy="<?= e((string) $order['tracking_number']) ?>">Copy</button><?php if (!empty($order['tracking_url']) && str_starts_with(strtolower((string) $order['tracking_url']), 'https://')): ?> · <a href="<?= e((string) $order['tracking_url']) ?>" target="_blank" rel="noopener">Track</a><?php endif; ?></p>
<?php endif; ?>
<?php if (!empty($order['delivered_at'])): ?>
  <p class="adm-muted">Delivered <?= e(adm_order_datetime((string) $order['delivered_at'])) ?></p>
<?php endif; ?>
<?php if (!in_array($order['status'], ['delivered', 'cancelled'], true)): ?>
  <form method="post" action="<?= e(url($detailPath . '/shipping')) ?>" class="adm-form" data-guard>
    <?= csrf_field() ?>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'courier_name', 'label' => 'Courier', 'value' => (string) ($order['courier_name'] ?? ''), 'maxlength' => 60, 'attr' => ['list' => 'courier-list', 'autocomplete' => 'off']]); ?>
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'tracking_number', 'label' => 'Tracking number', 'value' => (string) ($order['tracking_number'] ?? ''), 'maxlength' => 60, 'attr' => ['autocapitalize' => 'characters', 'autocomplete' => 'off']]); ?>
    </div>
    <datalist id="courier-list"><?php foreach ($couriers as $courier): ?><option value="<?= e($courier) ?>"></option><?php endforeach; ?></datalist>
    <?php partial_admin('field.php', ['type' => 'url', 'name' => 'tracking_url', 'label' => 'Tracking link (optional, https only)', 'value' => (string) ($order['tracking_url'] ?? ''), 'maxlength' => 255, 'placeholder' => 'https://']); ?>
    <div class="adm-cluster">
      <?php partial_admin('button.php', ['label' => 'Save courier details', 'variant' => 'ghost', 'size' => 'sm']); ?>
<?php if (in_array('shipped', $legalTargets, true) && $reconciles): ?>
      <?php partial_admin('button.php', ['label' => 'Save & mark shipped', 'variant' => 'gold', 'size' => 'sm', 'name' => 'mark_shipped', 'value' => '1']); ?>
<?php endif; ?>
    </div>
<?php if ($order['status'] === 'shipped' && isset($whatsapp['shipped'])): ?>
    <p><a class="adm-btn adm-btn--ghost adm-btn--sm" href="<?= e($whatsapp['shipped']['href']) ?>" target="_blank" rel="noopener">Send tracking on WhatsApp</a></p>
<?php endif; ?>
  </form>
<?php endif; ?>
</section>

<section class="adm-card" id="timeline">
  <div class="adm-card__head"><h2 class="adm-card__title">Timeline</h2></div>
  <ol class="adm-timeline">
<?php foreach ($history as $entry): $isCoin = $entry['field'] === 'payment_status'; ?>
    <li class="adm-timeline__item<?= $isCoin ? ' adm-timeline__item--coin' : '' ?>">
      <div class="adm-timeline__head"><?= $isCoin ? $badge((string) $entry['to_status'], ['label' => adm_order_payment_label((string) $entry['to_status']), 'small' => true]) : $badge((string) $entry['to_status'], ['label' => adm_order_status_label((string) $entry['to_status']), 'small' => true]) ?> <span class="adm-timeline__time"><?= e(adm_order_datetime((string) $entry['created_at'])) ?></span></div>
      <p class="adm-timeline__meta">by <?= $entry['changed_by'] === 'admin' ? 'Admin' . (!empty($entry['admin_username']) ? ' (' . e((string) $entry['admin_username']) . ')' : '') : ucfirst(e((string) $entry['changed_by'])) ?></p>
<?php if (!empty($entry['note'])): ?>
      <p class="adm-timeline__note">&ldquo;<?= e((string) $entry['note']) ?>&rdquo;</p>
<?php endif; ?>
    </li>
<?php endforeach; ?>
  </ol>
</section>

<section class="adm-card" id="notes">
  <div class="adm-card__head"><h2 class="adm-card__title">Internal notes</h2></div>
<?php if ($notes === []): ?>
  <p class="adm-muted">No notes yet. The customer never sees these.</p>
<?php else: ?>
  <ul class="adm-list">
<?php foreach ($notes as $note): ?>
    <li><?= $note['is_pinned'] ? '&#128204; ' : '' ?><?= nl2br(e((string) $note['body'])) ?><br><span class="adm-muted"><?= e((string) ($note['admin_name'] ?? 'admin')) ?> · <?= e(adm_order_datetime((string) $note['created_at'])) ?></span></li>
<?php endforeach; ?>
  </ul>
<?php endif; ?>
  <form method="post" action="<?= e(url($detailPath . '/notes')) ?>" class="adm-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="note">
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'body', 'id' => 'note-body', 'label' => 'Add a note', 'value' => '', 'rows' => 3, 'maxlength' => 1000, 'required' => true, 'compact' => true]); ?>
    <?php partial_admin('field.php', ['type' => 'checkbox', 'name' => 'pin', 'id' => 'note-pin', 'label' => 'Pin to top', 'value' => false, 'compact' => true]); ?>
    <?php partial_admin('button.php', ['label' => 'Add note', 'variant' => 'ghost', 'size' => 'sm']); ?>
  </form>
</section>

<section class="adm-card" id="activity">
  <details>
    <summary class="adm-card__title">Activity (last <?= e((string) count($activity)) ?>)</summary>
<?php if ($activity === []): ?>
    <p class="adm-muted">No admin activity recorded yet.</p>
<?php else: ?>
    <ul class="adm-list">
<?php foreach ($activity as $row): ?>
      <li><?= e((string) $row['summary']) ?><br><span class="adm-muted"><?= e((string) ($row['admin_username'] ?? '')) ?> · <span class="adm-mono"><?= e((string) $row['action']) ?></span> · <?= e(adm_order_datetime((string) $row['created_at'])) ?></span></li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </details>
</section>

<section class="adm-card adm-card--danger" id="danger">
  <div class="adm-card__head"><h2 class="adm-card__title">Danger zone</h2></div>
<?php if ($order['status'] === 'cancelled'): ?>
  <p class="adm-muted">Cancelled <?= e(adm_order_datetime((string) $order['cancelled_at'])) ?><?= !empty($order['cancel_reason']) ? ' — ' . e((string) $order['cancel_reason']) : '' ?></p>
<?php if ($order['stock_restored_at'] !== null): ?>
  <p>Stock restored <?= e(adm_order_datetime((string) $order['stock_restored_at'])) ?>.</p>
<?php else: ?>
  <p>Stock has <strong>not</strong> been returned to the shelf. Tap this only when the parcel is physically back.</p>
  <?php partial_admin('button.php', ['label' => 'Parcel received back — restore stock', 'variant' => 'gold', 'post' => $detailPath . '/stock-back', 'confirm' => "Restore stock for this order?\nEvery item goes back into stock once. This cannot be repeated.", 'confirm_label' => 'Yes, restore stock']); ?>
<?php endif; ?>
<?php elseif (in_array('cancelled', $legalTargets, true) && $reconciles): ?>
  <form method="post" action="<?= e(url($detailPath . '/status')) ?>" class="adm-form">
    <?= csrf_field() ?>
    <input type="hidden" name="to" value="cancelled">
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'note', 'id' => 'cancel-reason', 'label' => 'Reason for cancelling', 'value' => '', 'required' => true, 'maxlength' => 200, 'compact' => true, 'help' => $order['status'] === 'shipped' ? 'The parcel is with the courier: stock is NOT restored now. Use "Parcel received back" when it returns.' : 'Stock for every item is restored automatically, once.']); ?>
    <?php partial_admin('button.php', ['label' => 'Cancel this order — permanent', 'variant' => 'danger', 'confirm' => "Cancel this order?\nThis cannot be undone. The customer gets the cancellation email and the order stays in your history as cancelled.", 'confirm_label' => 'Yes, cancel order']); ?>
  </form>
<?php else: ?>
  <p class="adm-muted">Nothing to do here. Orders are never deleted.</p>
<?php endif; ?>
</section>

<form method="post" action="<?= e(url($detailPath . '/status')) ?>" class="adm-inline-form">
  <?= csrf_field() ?>
<?php if ($primaryAction !== null && $reconciles && $primaryAction['to'] !== 'shipped'): ?>
  <input type="hidden" name="to" value="<?= e($primaryAction['to']) ?>">
<?php endif; ?>
  <?php partial_admin('action-bar.php', ['buttons' => array_values(array_filter([
      $primaryAction === null || !$reconciles ? null : ($primaryAction['to'] === 'shipped'
          ? ['label' => 'Mark shipped…', 'href' => $detailPath . '#shipping', 'variant' => 'gold']
          : ['label' => $primaryAction['label'], 'variant' => 'gold', 'confirm' => $primaryAction['to'] === 'delivered' ? "Mark this order as delivered?\nThis cannot be undone." : null, 'confirm_label' => 'Yes, mark delivered']),
      $whatsappPrimary === null ? null : ['label' => 'WhatsApp', 'href' => $whatsappPrimary['href'], 'external' => true, 'variant' => 'ghost'],
      ['label' => 'Print', 'href' => $detailPath . '/packing-slip', 'external' => true, 'variant' => 'ghost'],
  ]))]); ?>
</form>
