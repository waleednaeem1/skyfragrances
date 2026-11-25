<?php
defined('SKYFR') || exit;
$money = static function (int $paisa, array $extra = []): string {
    ob_start();
    partial_admin('money.php', ['value' => money_from_paisa($paisa)] + $extra);
    return (string) ob_get_clean();
};
$badge = static function (string $label, string $tone): string {
    ob_start();
    partial_admin('badge.php', ['status' => '', 'label' => $label, 'tone' => $tone, 'small' => true]);
    return (string) ob_get_clean();
};
$rows = [];
foreach ($customers as $c) {
    $orders = (int) $c['orders'];
    $toCollect = money_paisa((string) $c['to_collect']);
    $repeat = $orders >= 2 ? ' ' . $badge('Repeat', 'gold') : '';
    $initials = implode('', array_map(static fn (string $w): string => mb_strtoupper(mb_substr($w, 0, 1)), array_slice(preg_split('/\s+/', trim((string) $c['name'])) ?: [], 0, 2)));
    $who = '<span class="adm-avatar" aria-hidden="true">' . e($initials) . '</span><span class="adm-media__body"><span class="adm-media__title">' . e((string) $c['name']) . '</span><span class="adm-media__meta">' . e((string) $c['phone']) . ' · ' . e((string) $c['city']) . '</span></span>';
    $ordersHtml = '<strong>' . e((string) $orders) . '</strong>' . ((int) $c['cancelled'] > 0 ? ' <span class="adm-muted">+' . e((string) $c['cancelled']) . ' cancelled</span>' : '');
    $owing = $toCollect > 0 ? $money($toCollect, ['strong' => true, 'low' => true]) : '<span class="adm-muted">—</span>';
    $rows[] = [
        'id' => customers_key((string) $c['phone_normalized']),
        'href' => customers_url((string) $c['phone_normalized']),
        'cells' => [
            'customer' => '<div class="adm-media">' . $who . '</div>',
            'orders' => $ordersHtml . $repeat,
            'month' => e((string) (int) $c['orders_month']),
            'year' => e((string) (int) $c['orders_year']),
            'ordered' => $money(money_paisa((string) $c['ordered'])),
            'collected' => $money(money_paisa((string) $c['collected']), ['muted' => true]),
            'owing' => $owing,
            'last' => e(date_long((string) $c['last_order'])),
        ],
    ];
}
$overviewToCollect = money_paisa((string) ($overview['to_collect'] ?? '0'));
?>
<?php partial_admin('page-header.php', [
    'title' => 'Customers',
    'subtitle' => 'Every customer by phone number, with their orders and what has been paid. Open one to see their full ledger.',
]); ?>
<?php
$totalCustomers = (int) ($overview['customers'] ?? 0);
$repeatCustomers = (int) ($overview['repeat_customers'] ?? 0);
$repeatShare = $totalCustomers > 0 ? (int) round($repeatCustomers / $totalCustomers * 100) : 0;
?>
<div class="adm-grid adm-grid--3">
  <?php partial_admin('kpi-card.php', ['label' => 'Customers', 'value' => number_format($totalCustomers), 'note' => 'Unique phone numbers', 'icon' => 'users', 'tone' => 'c1']); ?>
  <?php partial_admin('kpi-card.php', ['label' => 'Repeat customers', 'value' => number_format($repeatCustomers), 'note' => '2 or more orders', 'icon' => 'refresh', 'tone' => 'c4', 'href' => '/admin/customers?show=repeat']); ?>
  <?php partial_admin('kpi-card.php', ['label' => 'Payment to collect', 'value' => money(money_from_paisa($overviewToCollect)), 'note' => 'Still due on orders', 'icon' => 'wallet', 'tone' => 'c3', 'alert' => $overviewToCollect > 0, 'href' => '/admin/customers?show=owing&sort=owed']); ?>
</div>
<div class="adm-bento adm-bento--three">
  <section class="adm-card" aria-labelledby="mix-title">
    <div class="adm-card__head"><div><h2 class="adm-card__title" id="mix-title">Customer mix</h2><p class="adm-card__sub"><?= e(number_format($totalCustomers)) ?> customers</p></div></div>
    <?php partial_admin('donut.php', ['legend' => true, 'center' => $repeatShare . '%', 'caption' => 'repeat', 'segments' => [['label' => 'One-time', 'value' => $totalCustomers - $repeatCustomers, 'tone' => 'c2'], ['label' => 'Repeat (2+)', 'value' => $repeatCustomers, 'tone' => 'c1']]]); ?>
  </section>
  <section class="adm-card" aria-labelledby="owing-title">
    <div class="adm-card__head"><h2 class="adm-card__title" id="owing-title">Most to collect</h2></div>
<?php if ($topOwing === []): ?>
    <p class="adm-muted">Nobody owes anything.</p>
<?php else: ?>
    <?php partial_admin('bar-list.php', ['rows' => array_map(static fn (array $c): array => ['label' => (string) $c['name'], 'value' => money_paisa((string) $c['to_collect']), 'display' => money((string) $c['to_collect']), 'tone' => 'c3', 'href' => customers_url((string) $c['phone_normalized'])], $topOwing)]); ?>
<?php endif; ?>
  </section>
  <section class="adm-card adm-card--tint adm-tone--c1" aria-labelledby="where-title">
    <div class="adm-card__head"><h2 class="adm-card__title adm-card__title--tone" id="where-title">Where they are</h2></div>
    <?php partial_admin('bar-list.php', ['rows' => array_map(static fn (array $c): array => ['label' => (string) $c['city'], 'value' => (int) $c['n'], 'display' => (string) (int) $c['n']], $cityMix)]); ?>
  </section>
</div>
<?php partial_admin('filters.php', [
    'action' => '/admin/customers',
    'search' => ['name' => 'q', 'value' => $filters['q'], 'placeholder' => 'Name or phone number', 'maxlength' => 60, 'label' => 'Search customers'],
    'fields' => [
        ['type' => 'select', 'name' => 'show', 'label' => 'Show', 'value' => $filters['show'], 'options' => CUSTOMERS_SHOWS],
        ['type' => 'select', 'name' => 'sort', 'label' => 'Sort by', 'value' => $filters['sort'], 'options' => CUSTOMERS_SORTS],
    ],
    'clear_url' => '/admin/customers',
    'count_text' => $total === 0 ? 'No customers match' : null,
    'submit_label' => 'Search',
]); ?>
<?php partial_admin('pagination.php', ['top' => true, 'page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'customers', 'query' => $query]); ?>
<?php partial_admin('table.php', [
    'caption' => 'Customers',
    'list_id' => 'customers-list',
    'columns' => [
        ['key' => 'customer', 'label' => 'Customer'],
        ['key' => 'orders', 'label' => 'Orders'],
        ['key' => 'month', 'label' => 'This month', 'align' => 'right'],
        ['key' => 'year', 'label' => 'This year', 'align' => 'right'],
        ['key' => 'ordered', 'label' => 'Ordered', 'align' => 'right', 'class' => 'adm-table__td--money'],
        ['key' => 'collected', 'label' => 'Collected', 'align' => 'right', 'class' => 'adm-table__td--money'],
        ['key' => 'owing', 'label' => 'To collect', 'align' => 'right', 'class' => 'adm-table__td--money'],
        ['key' => 'last', 'label' => 'Last order'],
    ],
    'rows' => $rows,
    'empty' => ['icon' => 'users', 'title' => 'No customers yet', 'text' => $filters['q'] !== '' || $filters['show'] !== '' ? 'Try clearing the search or filters.' : 'Customers appear here after their first order.'],
]); ?>
<?php partial_admin('pagination.php', ['info' => false, 'page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'customers', 'query' => $query]); ?>
