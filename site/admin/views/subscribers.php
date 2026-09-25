<?php
defined('SKYFR') || exit;
$subscribers = $subscribers ?? [];
$badge = static function (string $status, array $extra = []): string {
    ob_start();
    partial_admin('badge.php', ['status' => $status] + $extra);
    return (string) ob_get_clean();
};
$rows = [];
foreach ($subscribers as $s) {
    $id = (int) $s['id'];
    $statusPost = '/admin/subscribers/' . $id . '/status';
    $actions = [
        $s['status'] === 'subscribed'
            ? ['label' => 'Unsubscribe', 'post' => $statusPost, 'fields' => ['to' => 'unsubscribed']]
            : ['label' => 'Resubscribe', 'post' => $statusPost, 'fields' => ['to' => 'subscribed']],
        ['label' => 'Copy email', 'type' => 'button', 'attr' => ['data-copy' => (string) $s['email']]],
        ['label' => 'Delete', 'variant' => 'danger', 'post' => $statusPost, 'fields' => ['to' => 'delete'], 'confirm' => "Delete " . $s['email'] . "?\nThe address is removed for good. If they opted out, prefer Unsubscribe so the opt-out stays on record.", 'confirm_word' => 'DELETE', 'confirm_label' => 'Yes, delete'],
    ];
    $sourceLabel = $sources[$s['source']] ?? ucfirst((string) $s['source']);
    $joined = '<time class="adm-note" datetime="' . e(date_iso($s['subscribed_at'])) . '">' . e(date_long($s['subscribed_at'])) . '</time>';
    $left = $s['unsubscribed_at'] === null ? '' : '<time class="adm-note" datetime="' . e(date_iso($s['unsubscribed_at'])) . '">' . e(date_long($s['unsubscribed_at'])) . '</time>';
    $rows[] = [
        'id' => $id,
        'cells' => [
            'email' => '<span class="adm-mono">' . e((string) $s['email']) . '</span>',
            'status' => $badge((string) $s['status']),
            'source' => e($sourceLabel),
            'subscribed' => $joined,
            'unsubscribed' => $left === '' ? '<span class="adm-muted">—</span>' : $left,
        ],
        'card' => [
            'title' => '<span class="adm-mono">' . e((string) $s['email']) . '</span>',
            'chip' => $badge((string) $s['status'], ['small' => true]),
            'lines' => array_values(array_filter([
                ['label' => 'Source', 'html' => e($sourceLabel)],
                ['label' => 'Subscribed', 'html' => $joined],
                $left === '' ? null : ['label' => 'Unsubscribed', 'html' => $left],
            ])),
            'inline_actions' => 1,
        ],
        'actions' => $actions,
    ];
}
$exportLabel = 'Export CSV (' . (int) $exportCount . ')';
?>
<?php partial_admin('page-header.php', [
    'title' => 'Subscribers',
    'subtitle' => 'Subscribed: ' . (int) $counts['subscribed'] . ' · Unsubscribed: ' . (int) $counts['unsubscribed'],
    'actions' => [['label' => $exportLabel, 'href' => $exportUrl, 'variant' => 'gold', 'disabled' => $exportCount === 0]],
]); ?>
<?php partial_admin('filters.php', [
    'action' => '/admin/subscribers',
    'search' => ['name' => 'q', 'value' => $filters['q'], 'placeholder' => 'Search by email', 'maxlength' => 60, 'label' => 'Search subscribers'],
    'fields' => [
        ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'value' => $filters['status'] === 'all' ? '' : $filters['status'], 'options' => ['' => 'All (' . (int) $counts['all'] . ')', 'subscribed' => 'Subscribed (' . (int) $counts['subscribed'] . ')', 'unsubscribed' => 'Unsubscribed (' . (int) $counts['unsubscribed'] . ')']],
        ['type' => 'select', 'name' => 'source', 'label' => 'Source', 'value' => $filters['source'], 'options' => ['' => 'Any source'] + $sources],
    ],
    'active' => $activeChips,
    'clear_url' => '/admin/subscribers',
    'count_text' => $total === 0 ? 'No subscribers match' : null,
    'submit_label' => 'Search',
]); ?>
<p class="adm-note">The export honours the filters above and opens cleanly in Excel. Paste the list into whichever mail tool you use — the shop never sends bulk mail itself.</p>
<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'subscribers']); ?>
<?php partial_admin('table.php', [
    'caption' => 'Newsletter subscribers',
    'list_id' => 'subscribers-list',
    'columns' => [
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'source', 'label' => 'Source'],
        ['key' => 'subscribed', 'label' => 'Subscribed'],
        ['key' => 'unsubscribed', 'label' => 'Unsubscribed'],
    ],
    'rows' => $rows,
    'empty' => $activeChips === []
        ? ['title' => 'No subscribers yet', 'text' => 'The newsletter box in the shop footer adds people here.']
        : ['title' => 'No subscribers match', 'text' => 'Try another email or clear the filters.', 'action' => ['label' => 'Clear filters', 'href' => '/admin/subscribers', 'variant' => 'ghost']],
]); ?>
<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'subscribers']); ?>
