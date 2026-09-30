<?php
defined('SKYFR') || exit;
$statusLabels = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'packing' => 'Packing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
$attentionLines = [];
if ($attention['pending_orders'] > 0) {
    $attentionLines[] = ['href' => '/admin/orders?status=pending', 'text' => $attention['pending_orders'] . ' order' . ($attention['pending_orders'] === 1 ? '' : 's') . ' awaiting confirmation', 'tone' => 'gold'];
}
if ($attention['pending_reviews'] > 0) {
    $attentionLines[] = ['href' => '/admin/reviews?status=pending', 'text' => $attention['pending_reviews'] . ' review' . ($attention['pending_reviews'] === 1 ? '' : 's') . ' awaiting moderation', 'tone' => 'gold'];
}
if ($attention['new_messages'] > 0) {
    $attentionLines[] = ['href' => '/admin/messages?status=new', 'text' => $attention['new_messages'] . ' unread message' . ($attention['new_messages'] === 1 ? '' : 's'), 'tone' => 'gold'];
}
if ($attention['unpaid_transfers'] > 0) {
    $attentionLines[] = ['href' => '/admin/orders/cancel-unpaid', 'text' => $attention['unpaid_transfers'] . ' transfer order' . ($attention['unpaid_transfers'] === 1 ? '' : 's') . ' unpaid for more than ' . $attention['hold_hours'] . ' h', 'tone' => 'red'];
}
if (!empty($attention['outbox'])) {
    $attentionLines[] = ['href' => '/admin/tools', 'text' => (string) $attention['outbox']['text'], 'tone' => $attention['outbox']['level'] === 'error' ? 'red' : 'neutral'];
}
?>
<?php if (!empty($installerPresent)): ?>
<div class="adm-banner" role="alert">install.php is still on the server. Delete it now from hPanel File Manager.</div>
<?php endif; ?>
<?php if (!empty($placeholderMethods)): ?>
<div class="adm-banner" role="alert">Customers cannot pay by <?= e(implode(', ', $placeholderMethods)) ?>: the account details are still the REPLACE ME placeholders, so these methods are hidden at checkout. <a href="<?= e(url('/admin/settings?tab=payments')) ?>">Enter the real account details in Settings › Payments</a>.</div>
<?php endif; ?>
<?php if (!empty($maintenanceOn)): ?>
<div class="adm-banner adm-banner--warn" role="status">The shop is in maintenance mode — customers see the closed page. <a href="<?= e(url('/admin/settings?tab=advanced')) ?>">Turn it off in Settings › Advanced</a>.</div>
<?php endif; ?>
<?php if (!empty($sampleRows)): ?>
<div class="adm-banner adm-banner--warn" role="status">This store is still showing sample data. <a href="<?= e(url('/admin/tools/remove-sample-data')) ?>">Remove it in Tools</a> before you go live.</div>
<?php endif; ?>
<?php if (!empty($originMismatch)): ?>
<div class="adm-banner adm-banner--info" role="status">You opened the panel at <?= e($panelOrigin) ?> but the site address is set to <?= e($siteUrl) ?>. <a href="<?= e(url('/admin/settings?tab=advanced')) ?>">Check Settings › Advanced</a>.</div>
<?php endif; ?>
<h1><?= e($greeting) ?><?= $displayName !== '' ? ', ' . e($displayName) : '' ?></h1>
<p class="adm-muted"><?= e($today) ?></p>
<div class="adm-grid">
<?php foreach ($kpis as $kpi): ?>
  <div class="adm-kpi">
    <span class="adm-kpi__value"><?= e($kpi['value']) ?></span>
    <span class="adm-kpi__label"><?= e($kpi['label']) ?></span>
    <p class="adm-note"><?= e($kpi['compare']) ?></p>
  </div>
<?php endforeach; ?>
</div>
<p class="adm-note">Revenue counts confirmed, packing, shipped and delivered orders — everything accepted and not cancelled.</p>
<h2>This month</h2>
<div class="adm-rail" role="list" aria-label="Orders by status this month">
<?php foreach ($statusLabels as $status => $label): ?>
  <a role="listitem" class="adm-chip<?= $status === 'pending' ? ' adm-chip--gold' : ($statusCounts[$status] === 0 ? ' adm-chip--muted' : '') ?>" href="<?= e(url('/admin/orders?status=' . $status)) ?>"><?= e($label) ?> <span class="adm-num"><?= e((string) $statusCounts[$status]) ?></span></a>
<?php endforeach; ?>
</div>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Needs you</h2></div>
<?php if ($attentionLines === []): ?>
  <p class="adm-muted">Nothing is waiting. Enjoy the quiet.</p>
<?php else: ?>
  <ul class="adm-list">
<?php foreach ($attentionLines as $line): ?>
    <li><a href="<?= e(url($line['href'])) ?>"><?= e($line['text']) ?></a><?php partial_admin('badge.php', ['status' => '', 'label' => $line['tone'] === 'red' ? 'Act' : 'Open', 'tone' => $line['tone'], 'small' => true]); ?></li>
<?php endforeach; ?>
  </ul>
<?php endif; ?>
  <p class="adm-note<?= !empty($storage['red']) ? ' adm-stock--low' : '' ?>">storage/ is using <?= e($storage['used_label']) ?><?= $storage['percent'] !== null ? ' · disk ' . e((string) $storage['percent']) . '% full' : '' ?><?= !empty($storage['red']) ? ' — run the privacy purge in Tools' : '' ?>.</p>
</div>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Latest orders</h2><a class="adm-btn adm-btn--text adm-btn--sm" href="<?= e(url('/admin/orders')) ?>">All orders</a></div>
<?php
$orderRows = [];
foreach ($latestOrders as $order) {
    ob_start();
    partial_admin('badge.php', ['status' => (string) $order['status'], 'small' => true]);
    $chip = ob_get_clean();
    ob_start();
    partial_admin('badge.php', ['status' => (string) $order['payment_status'], 'small' => true]);
    $payChip = ob_get_clean();
    ob_start();
    partial_admin('money.php', ['value' => (string) $order['grand_total'], 'strong' => true]);
    $moneyHtml = ob_get_clean();
    $orderRows[] = [
        'id' => (int) $order['id'],
        'href' => '/admin/orders/' . $order['order_number'],
        'cells' => [
            'number' => '<span class="adm-mono">' . e((string) $order['order_number']) . '</span>',
            'customer' => e((string) $order['customer_name']) . ' <span class="adm-muted">' . e((string) $order['city']) . '</span>',
            'status' => $chip . ' ' . $payChip,
            'total' => $moneyHtml,
            'when' => e(date_long((string) $order['created_at'])),
        ],
        'card' => [
            'title' => '<span class="adm-mono">' . e((string) $order['order_number']) . '</span>',
            'chip' => $chip,
            'sub' => e((string) $order['customer_name']) . ' · ' . e((string) $order['city']),
            'lines' => [['label' => 'Placed', 'html' => e(date_long((string) $order['created_at']))]],
            'money' => $moneyHtml,
            'aside' => $payChip,
        ],
    ];
}
partial_admin('table.php', [
    'caption' => 'Latest orders',
    'list_id' => 'dash-orders',
    'columns' => [
        ['key' => 'number', 'label' => 'Order'],
        ['key' => 'customer', 'label' => 'Customer'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'total', 'label' => 'Total', 'align' => 'right', 'class' => 'adm-table__td--money'],
        ['key' => 'when', 'label' => 'Placed'],
    ],
    'rows' => $orderRows,
    'empty' => ['title' => 'No orders yet', 'text' => 'Share your shop link on WhatsApp to get started.'],
]);
?>
</div>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Low stock</h2><a class="adm-btn adm-btn--text adm-btn--sm" href="<?= e(url('/admin/products?stock=low')) ?>">View all</a></div>
<?php if ($lowThreshold === 0): ?>
  <p class="adm-muted">Low-stock alerts are off (threshold 0). Sold-out sizes still show below.</p>
<?php endif; ?>
<?php if ($lowStock === []): ?>
  <p class="adm-muted">Every size is above its threshold.</p>
<?php else: ?>
  <ul class="adm-list">
<?php foreach ($lowStock as $row): $out = (int) $row['stock'] <= 0; ?>
    <li><a href="<?= e(url('/admin/products/' . (int) $row['id'] . '#sizes')) ?>"><?= e((string) $row['name']) ?> — <?= e((string) $row['size_label']) ?></a><span class="adm-stock<?= $out || (int) $row['stock'] <= (int) $row['threshold'] ? ' adm-stock--low' : '' ?>"><?= $out ? 'Sold out' : e((string) $row['stock']) . ' left' ?></span></li>
<?php endforeach; ?>
  </ul>
<?php endif; ?>
</div>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Best sellers · last 30 days</h2></div>
<?php if ($bestSellers === []): ?>
  <p class="adm-muted">No accepted orders in the last 30 days.</p>
<?php else: ?>
  <ol class="adm-list">
<?php foreach ($bestSellers as $i => $row): ?>
    <li><span><span class="adm-num"><?= e((string) ($i + 1)) ?>.</span> <?php if ($row['product_id'] !== null): ?><a href="<?= e(url('/admin/products/' . (int) $row['product_id'])) ?>"><?= e((string) $row['name']) ?></a><?php else: ?><?= e((string) $row['name']) ?><?php endif; ?></span><span class="adm-right"><?= e((string) $row['qty']) ?> sold · <?php partial_admin('money.php', ['value' => (string) $row['rev'], 'muted' => true]); ?></span></li>
<?php endforeach; ?>
  </ol>
<?php endif; ?>
</div>
<?php if (!empty($lastLoginAt)): ?>
<p class="adm-note">Last sign in: <?= e(date_long((string) $lastLoginAt)) ?> · <a href="<?= e(url('/admin/activity')) ?>">Activity log</a></p>
<?php endif; ?>
