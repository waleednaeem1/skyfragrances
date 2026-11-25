<?php
defined('SKYFR') || exit;
$detailPath = '/admin/orders/' . $order['order_number'];
$badge = static function (string $status, array $extra = []): string {
    ob_start();
    partial_admin('badge.php', ['status' => $status] + $extra);
    return (string) ob_get_clean();
};
$whatsappPrimary = $whatsapp[$whatsappDefault] ?? null;
$ledgerUrl = '/admin/customers/' . ltrim((string) $order['phone_normalized'], '+');
$tracking = trim((string) ($order['tracking_number'] ?? ''));
$pinned = array_values(array_filter($notes, static fn (array $note): bool => (int) ($note['is_pinned'] ?? 0) === 1));
?>
<?php if (!$reconciles): ?>
<div class="adm-banner adm-banner--warn" role="alert"><strong>Totals do not reconcile.</strong> Do not ship. Open Manage order for details.</div>
<?php endif; ?>
<?php partial_admin('page-header.php', [
    'title' => (string) $order['order_number'],
    'subtitle' => 'Placed ' . adm_order_datetime((string) $order['created_at']) . ' · ' . (int) $order['item_count'] . ' item' . ((int) $order['item_count'] === 1 ? '' : 's') . ' · ' . money((string) $order['grand_total']),
    'back' => '/admin/orders',
    'back_label' => 'Orders',
    'actions' => array_values(array_filter([
        ['label' => 'Manage order', 'href' => $detailPath, 'variant' => 'gold'],
        ['label' => 'Edit', 'href' => $detailPath . '/edit', 'variant' => 'ghost'],
        ['label' => 'Invoice', 'href' => $detailPath . '/invoice', 'external' => true, 'variant' => 'ghost'],
        ['label' => 'Packing slip', 'href' => $detailPath . '/packing-slip', 'external' => true, 'variant' => 'ghost'],
        $whatsappPrimary === null ? null : ['label' => 'WhatsApp', 'href' => $whatsappPrimary['href'], 'external' => true, 'variant' => 'ghost'],
    ])),
]); ?>
<p class="adm-cluster">
  <?= $badge((string) $order['status'], ['label' => adm_order_status_label((string) $order['status'])]) ?>
  <?= $badge((string) $order['payment_method'], ['label' => payment_method_label((string) $order['payment_method'])]) ?>
  <?= $badge((string) $order['payment_status'], ['label' => adm_order_payment_label((string) $order['payment_status'])]) ?>
<?php if ((string) ($order['source'] ?? '') === 'phone'): ?>
  <?= $badge('phone', ['label' => 'Phone order', 'tone' => 'slate']) ?>
<?php endif; ?>
</p>
<div class="adm-split">
  <div class="adm-split__main">
<?php partial_admin('order-items.php', ['items' => $items]); ?>
<?php partial_admin('order-totals.php', ['order' => $order]); ?>
<?php if (!empty($order['customer_note']) || $pinned !== []): ?>
    <section class="adm-card">
      <div class="adm-card__head"><h2 class="adm-card__title">Notes</h2></div>
<?php if (!empty($order['customer_note'])): ?>
      <p class="adm-note">From the customer</p>
      <p>&ldquo;<?= e((string) $order['customer_note']) ?>&rdquo;</p>
<?php endif; ?>
<?php foreach ($pinned as $note): ?>
      <p class="adm-note">Pinned note</p>
      <p><?= nl2br(e((string) $note['body'])) ?></p>
<?php endforeach; ?>
    </section>
<?php endif; ?>
<?php partial_admin('order-timeline.php', ['history' => $history]); ?>
  </div>
  <aside class="adm-split__side">
    <section class="adm-card">
      <div class="adm-card__head"><h2 class="adm-card__title">Customer</h2><a class="adm-btn adm-btn--text adm-btn--sm" href="<?= e(url($ledgerUrl)) ?>"><?= $previousCount > 0 ? e(($previousCount + 1) . ' orders') . ' · ' : '' ?>Ledger</a></div>
      <p class="adm-view__name"><?= e((string) $order['customer_name']) ?></p>
      <p><a href="tel:<?= e((string) $order['phone_normalized']) ?>"><?= e((string) $order['customer_phone']) ?></a></p>
<?php if (!empty($order['customer_email'])): ?>
      <p><a href="mailto:<?= e((string) $order['customer_email']) ?>"><?= e((string) $order['customer_email']) ?></a></p>
<?php endif; ?>
      <p class="adm-note">Deliver to</p>
      <p><?= nl2br(e((string) $order['address'])) ?><br><?= e((string) $order['city']) ?><?= !empty($order['postal_code']) ? ' ' . e((string) $order['postal_code']) : '' ?></p>
    </section>
    <section class="adm-card">
      <div class="adm-card__head"><h2 class="adm-card__title">Payment</h2><?= $badge((string) $order['payment_status'], ['label' => adm_order_payment_label((string) $order['payment_status']), 'small' => true]) ?></div>
      <dl class="adm-dl">
        <dt>Method</dt><dd><?= e(payment_method_label((string) $order['payment_method'])) ?></dd>
<?php if (!empty($order['paid_at'])): ?>
        <dt>Paid in full</dt><dd><?= e(adm_order_datetime((string) $order['paid_at'])) ?></dd>
<?php endif; ?>
<?php if (!empty($order['payment_reference'])): ?>
        <dt>Reference</dt><dd class="adm-mono"><?= e((string) $order['payment_reference']) ?></dd>
<?php endif; ?>
<?php if ($latestProof !== null && $proofFileOk): ?>
        <dt>Receipt</dt><dd><a href="<?= e(url($detailPath . '/proof')) ?>" target="_blank" rel="noopener">View payment screenshot</a></dd>
<?php endif; ?>
      </dl>
<?php partial_admin('order-payments.php', ['order' => $order, 'payments' => $payments]); ?>
    </section>
    <section class="adm-card">
      <div class="adm-card__head"><h2 class="adm-card__title">Shipping</h2><?php if (!empty($order['shipped_at'])): ?><span class="adm-muted">Shipped <?= e(adm_order_datetime((string) $order['shipped_at'])) ?></span><?php endif; ?></div>
<?php if ($tracking === '' && empty($order['courier_name'])): ?>
      <p class="adm-muted">Not booked with a courier yet.</p>
<?php else: ?>
      <dl class="adm-dl">
        <dt>Courier</dt><dd><?= e((string) ($order['courier_name'] ?? '—')) ?></dd>
<?php if ($tracking !== ''): ?>
        <dt>Tracking</dt><dd><span class="adm-mono"><?= e($tracking) ?></span><?php if (!empty($order['tracking_url'])): ?> · <a href="<?= e((string) $order['tracking_url']) ?>" target="_blank" rel="noopener">Track</a><?php endif; ?></dd>
<?php endif; ?>
<?php if ($courierStatus !== null && !empty($courierStatus['status_text'])): ?>
        <dt>Courier says</dt><dd><?= e((string) $courierStatus['status_text']) ?></dd>
<?php endif; ?>
<?php if (!empty($order['delivered_at'])): ?>
        <dt>Delivered</dt><dd><?= e(adm_order_datetime((string) $order['delivered_at'])) ?></dd>
<?php endif; ?>
      </dl>
<?php endif; ?>
    </section>
  </aside>
</div>
