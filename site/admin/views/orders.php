<?php
defined('SKYFR') || exit;
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
$chipUrl = static fn (array $overrides): string => adm_orders_query_url([], $overrides);
$isChipActive = static fn (string $key, string $value): bool => $key === 'pay' ? $filters['pay'] === $value : ($filters['status'] === [$value] && $filters['pay'] === '');
$rail = [
    ['label' => 'Needs payment review', 'count' => $attention['review'], 'url' => $chipUrl(['pay' => 'awaiting_verification']), 'tone' => 'gold', 'active' => $isChipActive('pay', 'awaiting_verification'), 'hide_zero' => true],
    ['label' => 'New orders', 'count' => $attention['pending'], 'url' => $chipUrl(['status' => ['pending']]), 'tone' => 'gold', 'active' => $isChipActive('status', 'pending'), 'hide_zero' => false],
    ['label' => 'To pack', 'count' => $attention['confirmed'], 'url' => $chipUrl(['status' => ['confirmed']]), 'tone' => '', 'active' => $isChipActive('status', 'confirmed'), 'hide_zero' => false],
    ['label' => 'To ship', 'count' => $attention['packing'], 'url' => $chipUrl(['status' => ['packing']]), 'tone' => '', 'active' => $isChipActive('status', 'packing'), 'hide_zero' => false],
    ['label' => 'In transit', 'count' => $attention['shipped'], 'url' => $chipUrl(['status' => ['shipped']]), 'tone' => 'muted', 'active' => $isChipActive('status', 'shipped'), 'hide_zero' => false],
];
$statusOptions = ['' => 'All statuses'];
foreach (ORDER_STATUSES as $s) {
    $statusOptions[$s] = adm_order_status_label($s);
}
$methodOptions = ['' => 'All methods'];
foreach (PAYMENT_METHODS as $m) {
    $methodOptions[$m] = payment_method_label($m);
}
$payOptions = ['' => 'Paid & unpaid'];
foreach (PAYMENT_STATUSES as $p) {
    $payOptions[$p] = adm_order_payment_label($p);
}
$rangeOptions = ['' => 'Any date', 'today' => 'Today', 'yesterday' => 'Yesterday', 'week' => 'Last 7 days', 'month' => 'This month', 'last_month' => 'Last month'];
$rows = [];
foreach ($orders as $o) {
    $number = '<span class="adm-mono">' . e((string) $o['order_number']) . '</span>';
    $itemsText = (int) $o['item_count'] . ' item' . ((int) $o['item_count'] === 1 ? '' : 's');
    $dateHtml = '<span style="white-space:nowrap">' . e(adm_order_date_compact((string) $o['created_at'])) . '</span>' . ($o['is_new'] ? ' <span class="adm-badge adm-badge--gold adm-badge--sm">new</span>' : '');
    $paymentHtml = $badge((string) $o['payment_method'], ['label' => payment_method_label((string) $o['payment_method']), 'small' => true]) . ' ' . $badge((string) $o['payment_status'], ['label' => adm_order_payment_label((string) $o['payment_status']), 'small' => true]) . ((int) $o['has_proof'] === 1 ? ' <span title="Proof attached" aria-label="Proof attached">&#128206;</span>' : '');
    $statusHtml = $badge((string) $o['status'], ['label' => adm_order_status_label((string) $o['status'])]) . ($o['on_hold'] ? ' ' . $badge('hold', ['label' => 'Unpaid ' . $o['hold_days'] . 'd', 'tone' => 'red', 'small' => true]) : '');
    $tracking = trim((string) ($o['tracking_number'] ?? ''));
    $courierHtml = $tracking === '' ? '<span class="adm-muted">' . e((string) ($o['courier_name'] ?? '')) . '</span>' : e((string) $o['courier_name']) . ' <span class="adm-mono">' . e(mb_strimwidth($tracking, 0, 14, '…')) . '</span> <button type="button" class="adm-btn adm-btn--text adm-btn--sm" data-copy="' . e($tracking) . '">Copy</button>';
    $actions = [['label' => 'Call', 'href' => 'tel:' . $o['phone_normalized']]];
    if ($o['whatsapp'] !== null) {
        $actions[] = ['label' => 'WhatsApp', 'href' => $o['whatsapp'], 'external' => true];
    }
    $actions[] = ['label' => 'Print slip', 'href' => '/admin/orders/' . $o['order_number'] . '/packing-slip', 'external' => true];
    $rows[] = [
        'id' => (int) $o['id'],
        'href' => '/admin/orders/' . $o['order_number'],
        'class' => $o['on_hold'] ? 'is-hold' : '',
        'cells' => [
            'order' => '<span style="white-space:nowrap">' . $number . '</span><br><span class="adm-muted">' . e($itemsText) . '</span>',
            'date' => $dateHtml,
            'customer' => e((string) $o['customer_name']) . '<br><span class="adm-muted"><a href="tel:' . e((string) $o['phone_normalized']) . '">' . e((string) $o['customer_phone']) . '</a> · ' . e((string) $o['city']) . '</span>',
            'total' => $moneyHtml((string) $o['grand_total']),
            'payment' => $paymentHtml,
            'status' => $statusHtml,
            'courier' => $courierHtml,
        ],
        'card' => [
            'title' => $number,
            'chip' => $statusHtml,
            'sub' => e(adm_order_date_compact((string) $o['created_at'])) . ' · ' . e($itemsText) . ($o['is_new'] ? ' · <span class="adm-badge adm-badge--gold adm-badge--sm">new</span>' : ''),
            'lines' => [['html' => e((string) $o['customer_name']) . ' · ' . e((string) $o['city'])]],
            'money' => $moneyHtml((string) $o['grand_total']),
            'aside' => $paymentHtml,
            'inline_actions' => 2,
        ],
        'actions' => $actions,
    ];
}
?>
<?php partial_admin('page-header.php', [
    'title' => 'Orders',
    'subtitle' => $total === 0 ? 'No orders yet' : $total . ' ' . ($total === 1 ? 'order' : 'orders') . ' match',
    'actions' => [
        ['label' => 'Export CSV', 'href' => $exportUrl, 'size' => 'sm'],
    ],
]); ?>

<?php if ($attention['hold'] > 0): ?>
<div class="adm-banner adm-banner--warn" role="status"><?= e((string) $attention['hold']) ?> transfer <?= $attention['hold'] === 1 ? 'order is' : 'orders are' ?> still unpaid after <?= e((string) $holdHours) ?> hours and holding stock. <a href="<?= e(url('/admin/orders/cancel-unpaid')) ?>">Review and cancel them</a></div>
<?php endif; ?>

<nav class="adm-rail" aria-label="Needs attention">
<?php foreach ($rail as $chip): ?>
<?php if ($chip['hide_zero'] && $chip['count'] === 0) { continue; } ?>
  <a class="adm-chip<?= $chip['tone'] !== '' ? ' adm-chip--' . e($chip['tone']) : '' ?><?= $chip['active'] ? ' is-active' : '' ?>" href="<?= e(url($chip['url'])) ?>"<?= $chip['active'] ? ' aria-current="true"' : '' ?>><?= e($chip['label']) ?> <strong class="adm-num"><?= e((string) $chip['count']) ?></strong></a>
<?php endforeach; ?>
</nav>

<?php partial_admin('filters.php', [
    'action' => '/admin/orders',
    'search' => ['name' => 'q', 'value' => $filters['q'], 'placeholder' => 'Order number, phone or name', 'maxlength' => 60, 'label' => 'Search orders'],
    'fields' => [
        ['type' => 'select', 'name' => 'status[]', 'id' => 'f-status', 'label' => 'Status', 'value' => $filters['status'][0] ?? '', 'options' => $statusOptions],
        ['type' => 'select', 'name' => 'method', 'id' => 'f-method', 'label' => 'Payment method', 'value' => $filters['method'], 'options' => $methodOptions],
        ['type' => 'select', 'name' => 'pay', 'id' => 'f-pay', 'label' => 'Payment', 'value' => $filters['pay'], 'options' => $payOptions],
        ['type' => 'select', 'name' => 'range', 'id' => 'f-range', 'label' => 'Quick range', 'value' => '', 'options' => $rangeOptions],
        ['type' => 'date', 'name' => 'from', 'id' => 'f-from', 'label' => 'From', 'value' => $filters['from']],
        ['type' => 'date', 'name' => 'to', 'id' => 'f-to', 'label' => 'To', 'value' => $filters['to']],
        ['type' => 'select', 'name' => 'proof', 'id' => 'f-proof', 'label' => 'Payment proof', 'value' => $filters['proof'], 'options' => ['' => 'Any', 'yes' => 'Attached', 'no' => 'None']],
        ['type' => 'select', 'name' => 'per', 'id' => 'f-per', 'label' => 'Per page', 'value' => (string) $filters['per'], 'options' => ['25' => '25', '50' => '50', '100' => '100']],
    ],
    'hidden' => $filters['sort'] !== 'date_desc' ? ['sort' => $filters['sort']] : [],
    'active' => $activeChips,
    'clear_url' => $activeChips !== [] ? $clearUrl : null,
]); ?>

<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'orders', 'path' => '/admin/orders', 'query' => $query]); ?>

<?php partial_admin('table.php', [
    'caption' => 'Orders',
    'list_id' => 'orders-list',
    'columns' => [
        ['key' => 'order', 'label' => 'Order'],
        ['key' => 'date', 'label' => 'Date', 'sort' => 'date'],
        ['key' => 'customer', 'label' => 'Customer'],
        ['key' => 'total', 'label' => 'Total', 'align' => 'right', 'sort' => 'total', 'class' => 'adm-table__td--money'],
        ['key' => 'payment', 'label' => 'Payment'],
        ['key' => 'status', 'label' => 'Status', 'sort' => 'status', 'sort_plain' => true],
        ['key' => 'courier', 'label' => 'Courier'],
    ],
    'sort' => ['param' => 'sort', 'current' => $filters['sort']],
    'rows' => $rows,
    'bulk' => [
        'action' => '/admin/orders/bulk',
        'buttons' => [
            ['label' => 'Mark confirmed', 'value' => 'confirmed', 'confirm' => "Mark the selected orders as Confirmed?\nOrders that are not Pending are skipped. Each customer with an email gets the confirmation email.", 'confirm_label' => 'Yes, confirm them'],
            ['label' => 'Mark packing', 'value' => 'packing', 'confirm' => "Mark the selected orders as Packing?\nOrders that are not Confirmed are skipped.", 'confirm_label' => 'Yes, mark packing'],
            ['label' => 'Mark shipped…', 'value' => 'shipped'],
            ['label' => 'Print slips', 'value' => 'packing_slips'],
            ['label' => 'Export CSV', 'value' => 'export'],
        ],
    ],
    'empty' => ['title' => $total === 0 && $activeChips === [] ? 'No orders yet' : 'No orders match', 'text' => $activeChips === [] ? 'New orders appear here the moment a customer places them.' : 'Try removing a filter or widening the date range.', 'action' => $activeChips === [] ? null : ['label' => 'Clear filters', 'href' => '/admin/orders']],
]); ?>

<?php if ($bulkShip !== []): ?>
<section class="adm-card" id="bulk-ship">
  <div class="adm-card__head"><h2 class="adm-card__title">Mark <?= e((string) count($bulkShip)) ?> selected as Shipped</h2></div>
  <form method="post" action="<?= e(url('/admin/orders/bulk')) ?>" class="adm-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="shipped">
<?php foreach ($bulkShip as $bs): ?>
    <input type="hidden" name="ids[]" value="<?= e((string) $bs['id']) ?>">
<?php endforeach; ?>
    <p class="adm-note">One courier for all: <?php foreach ($bulkShip as $i => $bs): ?><?= $i > 0 ? ', ' : '' ?><span class="adm-mono"><?= e((string) $bs['order_number']) ?></span><?= trim((string) $bs['tracking_number']) === '' ? ' <span class="adm-badge adm-badge--red adm-badge--sm">no tracking</span>' : '' ?><?php endforeach; ?>. Tracking numbers are never set in bulk — every order must already have one saved.</p>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'courier_name', 'id' => 'bulk-courier', 'label' => 'Courier', 'value' => '', 'required' => true, 'maxlength' => 60, 'attr' => ['list' => 'bulk-courier-list', 'autocomplete' => 'off']]); ?>
    <datalist id="bulk-courier-list"><?php foreach (ADM_ORDER_COURIERS as $courier): ?><option value="<?= e($courier) ?>"></option><?php endforeach; ?></datalist>
    <div class="adm-cluster">
      <?php partial_admin('button.php', ['label' => 'Mark all as Shipped', 'variant' => 'gold', 'confirm' => "Mark these orders as Shipped?\nEach customer with an email gets the shipped email with courier and tracking. The whole batch is refused if any order is not ready.", 'confirm_label' => 'Yes, mark shipped']); ?>
      <?php partial_admin('button.php', ['label' => 'Cancel', 'variant' => 'text', 'href' => '/admin/orders']); ?>
    </div>
  </form>
</section>
<?php endif; ?>

<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'orders', 'path' => '/admin/orders', 'query' => $query]); ?>
