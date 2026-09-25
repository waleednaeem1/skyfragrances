<?php
defined('SKYFR') || exit;
$messages = $messages ?? [];
$badge = static function (string $status, array $extra = []): string {
    ob_start();
    partial_admin('badge.php', ['status' => $status] + $extra);
    return (string) ob_get_clean();
};
$rows = [];
foreach ($messages as $m) {
    $id = (int) $m['id'];
    $detailUrl = '/admin/messages/' . $id;
    $statusPost = $detailUrl . '/status';
    $preview = truncate_on_word((string) $m['message'], 160);
    $subject = trim((string) ($m['subject'] ?? ''));
    $subjectHtml = $subject === '' ? '<span class="adm-muted">No subject</span>' : e($subject);
    $contact = [];
    if (!empty($m['phone'])) {
        $contact[] = '<span class="adm-mono">' . e((string) $m['phone']) . '</span>';
    }
    if (!empty($m['email'])) {
        $contact[] = e((string) $m['email']);
    }
    $contactHtml = $contact === [] ? '<span class="adm-muted">No contact details</span>' : implode('<br>', $contact);
    $when = '<time class="adm-note" datetime="' . e(date_iso($m['created_at'])) . '">' . e(date_long($m['created_at'])) . '</time>';
    $actions = [['label' => 'Open', 'href' => $detailUrl]];
    if ($m['status'] !== 'replied') {
        $actions[] = ['label' => 'Mark replied', 'post' => $statusPost, 'fields' => ['to' => 'replied', 'return' => $returnTo]];
    }
    if ($m['status'] !== 'archived') {
        $actions[] = ['label' => 'Archive', 'post' => $statusPost, 'fields' => ['to' => 'archived', 'return' => $returnTo]];
    }
    if ($m['status'] === 'archived' || $m['status'] === 'read') {
        $actions[] = ['label' => 'Mark as new', 'post' => $statusPost, 'fields' => ['to' => 'new', 'return' => $returnTo]];
    }
    $actions[] = ['label' => 'Delete', 'variant' => 'danger', 'post' => $statusPost, 'fields' => ['to' => 'delete'], 'confirm' => "Delete this message?\nThe message from " . $m['name'] . " and their contact details are removed for good.", 'confirm_word' => 'DELETE', 'confirm_label' => 'Yes, delete'];
    $rows[] = [
        'id' => $id,
        'href' => $detailUrl,
        'class' => $m['status'] === 'new' ? 'is-unread' : '',
        'cells' => [
            'name' => '<strong>' . e((string) $m['name']) . '</strong>' . (!empty($m['order_number']) ? '<br><span class="adm-mono adm-muted">' . e((string) $m['order_number']) . '</span>' : ''),
            'subject' => $subjectHtml . '<br><span class="adm-note">' . e($preview) . '</span>',
            'contact' => $contactHtml,
            'received' => $when,
            'status' => $badge((string) $m['status']),
        ],
        'card' => [
            'title' => e((string) $m['name']),
            'chip' => $badge((string) $m['status'], ['small' => true]),
            'sub' => '<span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">' . e($preview) . '</span>',
            'lines' => [
                ['label' => 'Subject', 'html' => $subjectHtml],
                ['label' => 'Phone', 'html' => !empty($m['phone']) ? '<span class="adm-mono">' . e((string) $m['phone']) . '</span>' : '<span class="adm-muted">None</span>'],
                ['label' => 'Received', 'html' => $when],
            ],
            'inline_actions' => 1,
        ],
        'actions' => $actions,
    ];
}
$noun = $filters['status'] === 'all' ? 'messages' : $filters['status'] . ' messages';
?>
<?php partial_admin('page-header.php', [
    'title' => 'Messages',
    'subtitle' => (int) $counts['new'] . ' new · ' . (int) $counts['read'] . ' read, not yet answered',
]); ?>
<?php partial_admin('tabs.php', ['tabs' => $tabs, 'label' => 'Message status']); ?>
<?php partial_admin('filters.php', [
    'action' => '/admin/messages',
    'search' => ['name' => 'q', 'value' => $filters['q'], 'placeholder' => 'Name, phone, email or words', 'maxlength' => 60, 'label' => 'Search messages'],
    'hidden' => array_filter(['status' => $filters['status'] === 'new' ? '' : $filters['status']]),
    'active' => $activeChips,
    'clear_url' => '/admin/messages' . ($filters['status'] === 'new' ? '' : '?status=' . $filters['status']),
    'count_text' => $total === 0 ? 'No ' . $noun . ' match' : null,
    'submit_label' => 'Search',
]); ?>
<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'messages']); ?>
<?php partial_admin('table.php', [
    'caption' => 'Contact messages',
    'list_id' => 'messages-list',
    'columns' => [
        ['key' => 'name', 'label' => 'From'],
        ['key' => 'subject', 'label' => 'Subject'],
        ['key' => 'contact', 'label' => 'Contact'],
        ['key' => 'received', 'label' => 'Received'],
        ['key' => 'status', 'label' => 'Status'],
    ],
    'rows' => $rows,
    'empty' => $activeChips === []
        ? ($filters['status'] === 'new'
            ? ['title' => 'No new messages', 'text' => 'Messages from the Contact page appear here. You are all caught up.']
            : ['title' => 'Nothing here', 'text' => 'No ' . $noun . ' yet.'])
        : ['title' => 'No messages match', 'text' => 'Try another name, phone number or word.', 'action' => ['label' => 'Clear search', 'href' => '/admin/messages', 'variant' => 'ghost']],
]); ?>
<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'messages']); ?>
