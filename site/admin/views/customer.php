<?php
defined('SKYFR') || exit;
$money = static function (int $paisa, array $extra = []): string {
    ob_start();
    partial_admin('money.php', ['value' => money_from_paisa($paisa)] + $extra);
    return (string) ob_get_clean();
};
$badge = static function (string $status, array $extra = []): string {
    ob_start();
    partial_admin('badge.php', ['status' => $status, 'small' => true] + $extra);
    return (string) ob_get_clean();
};
$all = $periods[2]['totals'];
$orderLink = static fn (array $order): string => '<a class="adm-mono adm-link" href="' . e(url('/admin/orders/' . $order['order_number'] . '/view')) . '">' . e((string) $order['order_number']) . '</a>';
$rows = [];
foreach ($ledger as $i => $entry) {
    $order = $entry['order'];
    $method = payment_method_label((string) $order['payment_method']);
    if ($entry['kind'] === 'order') {
        $what = 'Order ' . $orderLink($order) . ' <span class="adm-muted">· ' . e((string) $order['item_count']) . ' item' . ((int) $order['item_count'] === 1 ? '' : 's') . ' · ' . e($method) . '</span> '
            . $badge((string) $order['status'], ['label' => adm_order_status_label((string) $order['status'])]) . ' ' . $badge((string) $order['payment_status'], ['label' => adm_order_payment_label((string) $order['payment_status'])]);
    } elseif ($entry['kind'] === 'payment') {
        $what = 'Payment received for ' . $orderLink($order) . ' <span class="adm-muted">· ' . e($method) . '</span>';
    } else {
        $what = 'Refund for ' . $orderLink($order);
    }
    $charged = $entry['charge'] > 0 ? $money($entry['charge']) : ($entry['void'] > 0 ? '<s class="adm-muted">' . e(money(money_from_paisa($entry['void']))) . '</s>' : '<span class="adm-muted">—</span>');
    $received = $entry['received'] > 0 ? $money($entry['received'], ['strong' => true]) : '<span class="adm-muted">—</span>';
    $balance = $entry['balance'];
    $balanceHtml = $balance === 0
        ? '<span class="adm-muted">Settled</span>'
        : $money(abs($balance), ['strong' => true, 'low' => $balance > 0]) . '<span class="adm-note adm-ledger__side">' . ($balance > 0 ? 'to collect' : 'owed to customer') . '</span>';
    $rows[] = [
        'id' => $i,
        'cells' => [
            'date' => e(date_long($entry['at'])),
            'entry' => $what,
            'charged' => $charged,
            'received' => $received,
            'balance' => $balanceHtml,
        ],
    ];
}
$headerActions = [['label' => 'Call', 'href' => 'tel:' . $customer['phone_normalized'], 'variant' => 'ghost']];
if ($whatsapp !== null) {
    $headerActions[] = ['label' => 'WhatsApp', 'href' => $whatsapp, 'external' => true, 'variant' => 'gold'];
}
?>
<?php partial_admin('page-header.php', [
    'title' => $customer['name'],
    'subtitle' => $customer['phone'] . ' · ' . $customer['city'] . ' · Customer since ' . date_long($customer['first_order']),
    'back' => '/admin/customers',
    'back_label' => 'Customers',
    'actions' => $headerActions,
]); ?>
<?php if ($refundDue > 0): ?>
<div class="adm-banner adm-banner--warn" role="status"><?= e(money(money_from_paisa($refundDue))) ?> was paid for orders that were later cancelled. Refund it, then mark those orders Refunded so the ledger settles.</div>
<?php endif; ?>
<div class="adm-grid">
  <?php partial_admin('kpi-card.php', ['label' => 'Orders', 'icon' => 'package', 'tone' => 'c1', 'value' => (string) $all['orders'], 'note' => $cancelled > 0 ? $cancelled . ' cancelled, not counted' : 'None cancelled']); ?>
  <?php partial_admin('kpi-card.php', ['label' => 'Ordered', 'icon' => 'cart', 'tone' => 'c2', 'value' => money(money_from_paisa($all['ordered']))]); ?>
  <?php partial_admin('kpi-card.php', ['label' => 'Collected', 'icon' => 'banknote', 'tone' => 'c4', 'value' => money(money_from_paisa($all['collected']))]); ?>
  <?php partial_admin('kpi-card.php', ['label' => 'To collect', 'icon' => 'wallet', 'tone' => 'c5', 'value' => money(money_from_paisa($all['to_collect'])), 'alert' => $all['to_collect'] > 0]); ?>
</div>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">By period</h2></div>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th scope="col" class="adm-table__th">Period</th><th scope="col" class="adm-table__th adm-table__th--right">Orders</th><th scope="col" class="adm-table__th adm-table__th--right">Ordered</th><th scope="col" class="adm-table__th adm-table__th--right">Collected</th><th scope="col" class="adm-table__th adm-table__th--right">To collect</th></tr></thead>
      <tbody>
<?php foreach ($periods as $period): $t = $period['totals']; ?>
        <tr><th scope="row" class="adm-table__td"><?= e($period['label']) ?></th><td class="adm-table__td adm-table__td--right"><?= e((string) $t['orders']) ?></td><td class="adm-table__td adm-table__td--right"><?= $money($t['ordered']) ?></td><td class="adm-table__td adm-table__td--right"><?= $money($t['collected']) ?></td><td class="adm-table__td adm-table__td--right"><?= $t['to_collect'] > 0 ? $money($t['to_collect'], ['strong' => true, 'low' => true]) : '<span class="adm-muted">—</span>' ?></td></tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Ledger</h2><span class="adm-muted"><?= e(count($ledger) . ' entr' . (count($ledger) === 1 ? 'y' : 'ies')) ?></span></div>
  <p class="adm-note">Each order is charged when it is placed and credited when its payment is marked paid. Cancelled orders are shown crossed out and not charged. The balance is what this customer still owes.</p>
<?php partial_admin('table.php', [
    'caption' => 'Ledger for ' . $customer['name'],
    'list_id' => 'customer-ledger',
    'columns' => [
        ['key' => 'date', 'label' => 'Date'],
        ['key' => 'entry', 'label' => 'Entry'],
        ['key' => 'charged', 'label' => 'Charged', 'align' => 'right', 'class' => 'adm-table__td--money'],
        ['key' => 'received', 'label' => 'Received', 'align' => 'right', 'class' => 'adm-table__td--money'],
        ['key' => 'balance', 'label' => 'Balance', 'align' => 'right', 'class' => 'adm-table__td--money'],
    ],
    'rows' => $rows,
]); ?>
</div>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Contact details used</h2></div>
  <dl class="adm-dl">
    <dt>Names</dt><dd><?= e(implode(', ', $customer['names'])) ?></dd>
<?php if ($customer['emails'] !== []): ?>
    <dt>Email</dt><dd><?= e(implode(', ', $customer['emails'])) ?></dd>
<?php endif; ?>
    <dt>Addresses</dt><dd><?= implode('<br>', array_map('e', $customer['addresses'])) ?></dd>
  </dl>
</div>
