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
    'actions' => array_values(array_filter([
        ['label' => 'Edit', 'href' => $detailPath . '/edit', 'size' => 'sm'],
        ['label' => 'Invoice', 'href' => $detailPath . '/invoice', 'external' => true, 'size' => 'sm'],
        ['label' => 'Packing slip', 'href' => $detailPath . '/packing-slip', 'external' => true, 'size' => 'sm'],
    ])),
]); ?>
<p class="adm-cluster">
  <?= $badge((string) $order['status'], ['label' => adm_order_status_label((string) $order['status'])]) ?>
  <?= $badge((string) $order['payment_method'], ['label' => payment_method_label((string) $order['payment_method'])]) ?>
  <?= $badge((string) $order['payment_status'], ['label' => $paymentLabel]) ?>
<?php if ((string) ($order['source'] ?? '') === 'phone'): ?>
  <?= $badge('phone', ['label' => 'Phone order', 'tone' => 'slate']) ?>
<?php endif; ?>
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
    <div><dt class="adm-muted">Paid in full</dt><dd><?= e(adm_order_datetime((string) $order['paid_at'])) ?></dd></div>
<?php endif; ?>
  </dl>
<?php partial_admin('order-payments.php', ['order' => $order, 'payments' => $payments]); ?>
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
<?php if (in_array($order['payment_status'], ['unpaid', 'awaiting_verification', 'failed', 'partial'], true) && order_balance($order)['due'] > 0): ?>
  <form method="post" action="<?= e(url($detailPath . '/payment')) ?>" class="adm-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="mark_paid">
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'money', 'name' => 'amount', 'id' => 'pay-amount', 'label' => 'Amount received', 'value' => money_attr(money_from_paisa(order_balance($order)['due'])), 'help' => 'Balance due is ' . money(money_from_paisa(order_balance($order)['due'])) . '. Enter less to record a part payment.', 'compact' => true]); ?>
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'note', 'id' => 'pay-note', 'label' => 'Reference / notes', 'value' => '', 'maxlength' => 200, 'compact' => true]); ?>
    </div>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'datetime', 'name' => 'paid_at', 'id' => 'pay-date', 'label' => 'Payment received on', 'value' => order_datetime_input(), 'required' => true, 'compact' => true, 'help' => 'Change it if the money arrived on an earlier day.', 'attr' => ['max' => order_datetime_input()]]); ?>
    </div>
    <?php partial_admin('button.php', ['label' => $order['payment_status'] === 'awaiting_verification' ? 'Approve and record payment' : 'Record payment', 'variant' => 'gold', 'confirm' => "Record this payment?\nIf it is less than the balance due, the order is marked Part paid and the rest stays due.", 'confirm_label' => 'Record payment']); ?>
  </form>
<?php if ($isManual && !in_array($order['payment_status'], ['failed', 'partial'], true)): ?>
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

<?php partial_admin('order-items.php', ['items' => $items]); ?>

<?php partial_admin('order-totals.php', ['order' => $order]); ?>

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
        'confirm' => $target === 'delivered' ? "Mark this order as delivered?\nIf it was a mistake you can move it back from here." : null,
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
  <div class="adm-card__head"><h2 class="adm-card__title">Customer</h2><a class="adm-btn adm-btn--text adm-btn--sm" href="<?= e(url('/admin/customers/' . ltrim((string) $order['phone_normalized'], '+'))) ?>"><?= $previousCount > 0 ? e(($previousCount + 1) . ' orders') . ' · ' : '' ?>Customer ledger</a></div>
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
  <p><a class="adm-btn adm-btn--ghost adm-btn--sm" href="<?= e(url($detailPath . '/edit')) ?>">Edit customer details</a></p>
</section>

<section class="adm-card" id="shipping">
  <div class="adm-card__head"><h2 class="adm-card__title">Shipping</h2><?php if (!empty($order['shipped_at'])): ?><span class="adm-muted">Shipped <?= e(adm_order_datetime((string) $order['shipped_at'])) ?></span><?php endif; ?></div>
<?php if (!empty($order['tracking_number'])): ?>
  <p><?= e((string) $order['courier_name']) ?> · <span class="adm-mono"><?= e((string) $order['tracking_number']) ?></span> <button type="button" class="adm-btn adm-btn--text adm-btn--sm" data-copy="<?= e((string) $order['tracking_number']) ?>">Copy</button><?php if (!empty($order['tracking_url']) && str_starts_with(strtolower((string) $order['tracking_url']), 'https://')): ?> · <a href="<?= e((string) $order['tracking_url']) ?>" target="_blank" rel="noopener">Track</a><?php endif; ?></p>
<?php endif; ?>
<?php if (!empty($order['delivered_at'])): ?>
  <p class="adm-muted">Delivered <?= e(adm_order_datetime((string) $order['delivered_at'])) ?></p>
<?php endif; ?>
<?php if ($courierKey !== null && !empty($order['tracking_number'])): ?>
  <div class="adm-note">
<?php if ($courierStatus !== null && !empty($courierStatus['status_text'])): ?>
    <p><strong><?= e(COURIER_LABELS[$courierKey]) ?>:</strong> <?= e((string) $courierStatus['status_text']) ?><?php if ((string) $courierStatus['status_class'] === 'returned'): ?> <?= $badge('cancelled', ['label' => 'Coming back', 'small' => true]) ?><?php endif; ?></p>
<?php endif; ?>
<?php if ($courierStatus !== null && !empty($courierStatus['error'])): ?>
    <p class="adm-field__error"><?= e((string) $courierStatus['error']) ?></p>
<?php endif; ?>
    <p class="adm-muted"><?= $courierStatus === null ? 'Not checked yet. It is checked automatically every few hours while the order is open.' : 'Last checked ' . e(adm_order_datetime((string) $courierStatus['checked_at'])) . '. Checked automatically every few hours while the order is open.' ?></p>
    <?php partial_admin('button.php', ['label' => 'Check ' . COURIER_LABELS[$courierKey] . ' now', 'variant' => 'ghost', 'size' => 'sm', 'post' => $detailPath . '/courier-check']); ?>
  </div>
<?php endif; ?>
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
</section>

<?php partial_admin('order-timeline.php', ['history' => $history]); ?>

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
