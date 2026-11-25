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
    $who = '<span class="adm-media__title">' . e((string) $c['name']) . '</span><span class="adm-media__meta">' . e((string) $c['phone']) . ' · ' . e((string) $c['city']) . '</span>';
    $ordersHtml = '<strong>' . e((string) $orders) . '</strong>' . ((int) $c['cancelled'] > 0 ? ' <span class="adm-muted">+' . e((string) $c['cancelled']) . ' cancelled</span>' : '');
    $owing = $toCollect > 0 ? $money($toCollect, ['strong' => true, 'low' => true]) : '<span class="adm-muted">—</span>';
    $rows[] = [
        'id' => customers_key((string) $c['phone_normalized']),
        'href' => customers_url((string) $c['phone_normalized']),
        'cells' => [
            'customer' => '<div class="adm-media__body">' . $who . '</div>',
            'orders' => $ordersHtml . $repeat,
            'month' => e((string) (int) $c['orders_month']),
            'year' => e((string) (int) $c['orders_year']),
            'ordered' => $money(money_paisa((string) $c['ordered'])),
            'collected' => $money(money_paisa((string) $c['collected']), ['muted' => true]),
            'owing' => $owing,
            'last' => e(date_long((string) $c['last_order'])),
        ],
        'card' => [
            'title' => e((string) $c['name']) . $repeat,
            'sub' => e((string) $c['phone']) . ' · ' . e((string) $c['city']),
            'lines' => [
                ['label' => 'Orders', 'html' => $ordersHtml . ' · this month ' . e((string) (int) $c['orders_month']) . ' · this year ' . e((string) (int) $c['orders_year'])],
                ['label' => 'Collected', 'html' => $money(money_paisa((string) $c['collected']))],
                ['label' => 'To collect', 'html' => $owing],
                ['label' => 'Last order', 'html' => e(date_long((string) $c['last_order']))],
            ],
            'money' => $money(money_paisa((string) $c['ordered']), ['strong' => true]),
        ],
    ];
}
$overviewToCollect = money_paisa((string) ($overview['to_collect'] ?? '0'));
?>
<?php partial_admin('page-header.php', [
    'title' => 'Customers',
    'subtitle' => 'Every customer by phone number, with their orders and what has been paid. Open one to see their full ledger.',
]); ?>
<div class="adm-grid adm-grid--3">
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Customers</span><span class="adm-kpi__value"><?= e(number_format((int) ($overview['customers'] ?? 0))) ?></span></div>
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Repeat customers</span><span class="adm-kpi__value"><?= e(number_format((int) ($overview['repeat_customers'] ?? 0))) ?></span></div>
  <a class="adm-kpi adm-kpi--stat adm-kpi--link" href="<?= e(url('/admin/customers?show=owing&sort=owed')) ?>"><span class="adm-kpi__label">Payment to collect</span><span class="adm-kpi__value<?= $overviewToCollect > 0 ? ' adm-kpi__value--alert' : '' ?>"><?= e(money(money_from_paisa($overviewToCollect))) ?></span></a>
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
<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'customers', 'query' => $query]); ?>
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
    'empty' => ['title' => 'No customers yet', 'text' => $filters['q'] !== '' || $filters['show'] !== '' ? 'Try clearing the search or filters.' : 'Customers appear here after their first order.'],
]); ?>
<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'customers', 'query' => $query]); ?>
