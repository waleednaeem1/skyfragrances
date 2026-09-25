<?php
defined('SKYFR') || exit;
$badge = static function (array $b): string {
    ob_start();
    partial_admin('badge.php', $b);
    return (string) ob_get_clean();
};
$moneyHtml = static function (string $value, array $extra = []): string {
    ob_start();
    partial_admin('money.php', ['value' => $value] + $extra);
    return (string) ob_get_clean();
};
$tableRows = [];
foreach ($coupons as $c) {
    $editUrl = '/admin/coupons/' . (int) $c['id'];
    $actions = [
        ['label' => 'Edit', 'href' => $editUrl],
        ['label' => (int) $c['is_active'] === 1 ? 'Disable' : 'Enable', 'post' => '/admin/coupons/' . (int) $c['id'] . '/toggle', 'fields' => ['return' => $returnTo]],
        ['label' => 'Copy code', 'type' => 'button', 'attr' => ['data-copy' => (string) $c['code']]],
        ['label' => 'Share on WhatsApp', 'href' => (string) $c['share_url'], 'external' => true],
    ];
    $minHtml = money_paisa((string) $c['min_order_total']) > 0 ? $moneyHtml((string) $c['min_order_total']) : '<span class="adm-muted">None</span>';
    $perPhone = $c['per_phone_limit'] === null ? '' : ' <span class="adm-muted">(' . e((string) (int) $c['per_phone_limit']) . ' per phone)</span>';
    $tableRows[] = [
        'id' => (int) $c['id'],
        'href' => $editUrl,
        'cells' => [
            'code' => '<span class="adm-mono">' . e((string) $c['code']) . '</span>',
            'discount' => e((string) $c['discount_label']),
            'min' => $minHtml,
            'usage' => '<span class="adm-num">' . e((string) $c['usage_label']) . '</span>' . $perPhone,
            'window' => '<span class="adm-note">' . e((string) $c['window_label']) . '</span>',
            'status' => $badge($c['state_badge']),
        ],
        'card' => [
            'title' => '<span class="adm-mono">' . e((string) $c['code']) . '</span>',
            'chip' => $badge($c['state_badge'] + ['small' => true]),
            'sub' => e((string) $c['window_label']),
            'lines' => [
                ['label' => 'Discount', 'html' => e((string) $c['discount_label'])],
                ['label' => 'Min order', 'html' => $minHtml],
                ['label' => 'Used', 'html' => e((string) $c['usage_label']) . $perPhone],
            ],
            'inline_actions' => 2,
        ],
        'actions' => $actions,
    ];
}
$statusOptions = ['' => 'All statuses (' . (int) $counts['all'] . ')'];
foreach ($states as $key => $label) {
    $statusOptions[$key] = $label . ' (' . (int) ($counts[$key] ?? 0) . ')';
}
$from = $total === 0 ? 0 : ($page - 1) * $per + 1;
$to = min($total, $page * $per);
?>
<?php partial_admin('page-header.php', [
    'title' => 'Coupons',
    'subtitle' => (int) $counts['active'] . ' active · ' . (int) $counts['scheduled'] . ' scheduled',
    'actions' => [['label' => 'New coupon', 'href' => '/admin/coupons/new', 'variant' => 'gold']],
]); ?>
<?php partial_admin('filters.php', [
    'action' => '/admin/coupons',
    'search' => ['name' => 'q', 'value' => $filters['q'], 'placeholder' => 'Search by code', 'maxlength' => 40, 'label' => 'Search coupons'],
    'fields' => [
        ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'value' => $filters['status'], 'options' => $statusOptions],
        ['type' => 'select', 'name' => 'type', 'label' => 'Type', 'value' => $filters['type'], 'options' => ['' => 'Any type', 'percent' => 'Percent off', 'fixed' => 'Fixed amount']],
        ['type' => 'select', 'name' => 'sort', 'label' => 'Sort', 'value' => $filters['sort'], 'options' => ['created_desc' => 'Newest first', 'created_asc' => 'Oldest first', 'code_asc' => 'Code A–Z', 'code_desc' => 'Code Z–A', 'used_desc' => 'Most used', 'expires_asc' => 'Expiring soonest']],
    ],
    'active' => $activeChips,
    'clear_url' => '/admin/coupons',
    'count_text' => $total === 0 ? 'No coupons match' : 'Showing ' . $from . '–' . $to . ' of ' . $total . ' coupons',
]); ?>
<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'coupons']); ?>
<?php partial_admin('table.php', [
    'caption' => 'Coupons',
    'list_id' => 'coupons-list',
    'columns' => [
        ['key' => 'code', 'label' => 'Code', 'sort' => 'code'],
        ['key' => 'discount', 'label' => 'Discount'],
        ['key' => 'min', 'label' => 'Min order', 'align' => 'right'],
        ['key' => 'usage', 'label' => 'Used', 'sort' => 'used'],
        ['key' => 'window', 'label' => 'Valid', 'sort' => 'expires'],
        ['key' => 'status', 'label' => 'Status'],
    ],
    'sort' => ['param' => 'sort', 'current' => $filters['sort']],
    'rows' => $tableRows,
    'empty' => $activeChips === []
        ? ['title' => 'No coupons yet', 'text' => 'Create a code your customers can apply at checkout.', 'action' => ['label' => 'New coupon', 'href' => '/admin/coupons/new']]
        : ['title' => 'No coupons match', 'text' => 'Try a different status or clear the filters.', 'action' => ['label' => 'Clear filters', 'href' => '/admin/coupons', 'variant' => 'ghost']],
]); ?>
<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'coupons']); ?>
