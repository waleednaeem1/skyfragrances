<?php
defined('SKYFR') || exit;
$base = $filters;
unset($base['id']);
$removeUrl = static function (string $key) use ($filters): string {
    $q = array_filter($filters, static fn ($v): bool => $v !== '' && $v !== 0);
    unset($q[$key]);
    if ($key === 'entity') {
        unset($q['id']);
    }
    return '/admin/activity' . ($q === [] ? '' : '?' . http_build_query($q));
};
$active = [];
if ($filters['entity'] !== '') {
    $active[] = ['label' => 'Area: ' . ($entityOptions[$filters['entity']] ?? $filters['entity']) . ($filters['id'] > 0 ? ' #' . $filters['id'] : ''), 'remove_url' => $removeUrl('entity')];
}
if ($filters['action'] !== '') {
    $active[] = ['label' => 'Action: ' . $filters['action'], 'remove_url' => $removeUrl('action')];
}
if ($filters['from'] !== '') {
    $active[] = ['label' => 'From ' . date_short($filters['from']), 'remove_url' => $removeUrl('from')];
}
if ($filters['to'] !== '') {
    $active[] = ['label' => 'To ' . date_short($filters['to']), 'remove_url' => $removeUrl('to')];
}
$from = $total === 0 ? 0 : ($page - 1) * $per + 1;
$to = min($total, $page * $per);
partial_admin('page-header.php', ['title' => 'Activity log', 'subtitle' => 'Everything the panel changed, newest first. Rows are never edited or deleted; account numbers and phone numbers are recorded as “(changed)”.']);
partial_admin('filters.php', [
    'action' => '/admin/activity',
    'search' => ['name' => 'q', 'value' => $filters['q'], 'placeholder' => 'Search the summary', 'maxlength' => 60],
    'fields' => [
        ['type' => 'select', 'name' => 'entity', 'label' => 'Area', 'value' => $filters['entity'], 'options' => $entityOptions],
        ['type' => 'select', 'name' => 'action', 'label' => 'Action', 'value' => $filters['action'], 'options' => $actionOptions],
        ['type' => 'date', 'name' => 'from', 'label' => 'From', 'value' => $filters['from']],
        ['type' => 'date', 'name' => 'to', 'label' => 'To', 'value' => $filters['to']],
    ],
    'hidden' => $filters['id'] > 0 ? ['id' => (string) $filters['id']] : [],
    'active' => $active,
    'clear_url' => '/admin/activity',
    'count_text' => $total === 0 ? 'No activity matches' : 'Showing ' . $from . '–' . $to . ' of ' . $total . ' entries',
    'submit_label' => 'Search',
]);
$tableRows = [];
foreach ($rows as $row) {
    $type = (string) ($row['entity_type'] ?? '');
    $id = $row['entity_id'] === null ? null : (int) $row['entity_id'];
    $href = activity_entity_href($type, $id, $row['order_number'] ?? null);
    $entityLabel = $entityOptions[$type] ?? ucfirst($type);
    if ($type === 'order' && !empty($row['order_number'])) {
        $entityLabel = '<span class="adm-mono">' . e((string) $row['order_number']) . '</span>';
    } elseif ($id !== null && $type !== 'admin_user') {
        $entityLabel = e($entityLabel) . ' <span class="adm-muted">#' . e((string) $id) . '</span>';
    } else {
        $entityLabel = e($entityLabel);
    }
    $entityHtml = $href !== null ? '<a href="' . e(url($href)) . '">' . $entityLabel . '</a>' : $entityLabel;
    $before = activity_json_pretty($row['before_json'] ?? null);
    $after = activity_json_pretty($row['after_json'] ?? null);
    $detail = '';
    if ($before !== '' || $after !== '') {
        $detail = '<details class="adm-note"><summary>Details</summary>'
            . ($before !== '' ? '<p class="adm-note">Before</p><pre class="adm-mono">' . e($before) . '</pre>' : '')
            . ($after !== '' ? '<p class="adm-note">After</p><pre class="adm-mono">' . e($after) . '</pre>' : '')
            . '</details>';
    }
    $action = (string) $row['action'];
    $tone = str_contains($action, 'delete') || str_contains($action, 'fail') || str_contains($action, 'cancel') || str_contains($action, 'reject') ? 'maroon' : (str_contains($action, 'create') || str_ends_with($action, '.ok') || str_contains($action, 'paid') ? 'green' : 'neutral');
    ob_start();
    partial_admin('badge.php', ['status' => $action, 'label' => $action, 'tone' => $tone, 'small' => true]);
    $chip = ob_get_clean();
    $when = e(date_long((string) $row['created_at']));
    $who = e((string) ($row['admin_username'] ?? 'system'));
    $tableRows[] = [
        'id' => (int) $row['id'],
        'cells' => [
            'when' => $when,
            'who' => $who,
            'action' => $chip,
            'entity' => $entityHtml,
            'summary' => e((string) $row['summary']) . $detail,
        ],
        'card' => [
            'title' => e((string) $row['summary']),
            'chip' => $chip,
            'sub' => $entityHtml,
            'lines' => array_values(array_filter([['label' => 'When', 'html' => $when . ' · ' . $who], $detail !== '' ? ['html' => $detail] : null])),
        ],
    ];
}
partial_admin('table.php', [
    'caption' => 'Activity log',
    'list_id' => 'activity-list',
    'columns' => [
        ['key' => 'when', 'label' => 'When'],
        ['key' => 'who', 'label' => 'Who'],
        ['key' => 'action', 'label' => 'Action'],
        ['key' => 'entity', 'label' => 'What'],
        ['key' => 'summary', 'label' => 'Summary'],
    ],
    'rows' => $tableRows,
    'empty' => ['title' => 'No activity yet', 'text' => $active !== [] || $filters['q'] !== '' ? 'Try clearing the filters.' : 'Changes made in the panel will show up here.'],
]);
if ($total > $per) {
    partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'entries', 'path' => '/admin/activity']);
}
