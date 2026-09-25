<?php
defined('SKYFR') || exit;
$pages = $pages ?? [];
$badge = static function (string $status, array $extra = []): string {
    ob_start();
    partial_admin('badge.php', ['status' => $status] + $extra);
    return (string) ob_get_clean();
};
$rows = [];
foreach ($pages as $p) {
    $editUrl = '/admin/pages/' . $p['slug'];
    $live = (int) $p['is_active'] === 1;
    $kind = $p['template'] === 'page-faq' ? 'Questions and answers' : 'Article';
    $updated = $p['updated_at'] !== null ? date_long($p['updated_at']) : 'Never edited';
    $rows[] = [
        'id' => (int) $p['id'],
        'href' => $editUrl,
        'cells' => [
            'title' => '<strong>' . e((string) $p['title']) . '</strong><br><span class="adm-mono adm-muted">/' . e((string) $p['slug']) . '</span>',
            'kind' => e($kind),
            'updated' => '<span class="adm-note">' . e($updated) . '</span>',
            'status' => $badge($live ? 'live' : 'hidden', ['label' => $live ? 'Live' : 'Hidden']),
        ],
        'card' => [
            'title' => e((string) $p['title']),
            'chip' => $badge($live ? 'live' : 'hidden', ['label' => $live ? 'Live' : 'Hidden', 'small' => true]),
            'sub' => '<span class="adm-mono">/' . e((string) $p['slug']) . '</span>',
            'lines' => [
                ['label' => 'Type', 'html' => e($kind)],
                ['label' => 'Updated', 'html' => e($updated)],
            ],
            'inline_actions' => 2,
        ],
        'actions' => [
            ['label' => 'Edit', 'href' => $editUrl],
            ['label' => 'View on site', 'href' => url('/' . $p['slug']), 'external' => true],
        ],
    ];
}
?>
<?php partial_admin('page-header.php', [
    'title' => 'Pages',
    'subtitle' => count($pages) . ' pages · ' . (int) $liveCount . ' live. About, delivery, returns, privacy, terms and the FAQ — edit the words without touching any files.',
]); ?>
<?php partial_admin('table.php', [
    'caption' => 'Content pages',
    'list_id' => 'pages-list',
    'columns' => [
        ['key' => 'title', 'label' => 'Page'],
        ['key' => 'kind', 'label' => 'Type'],
        ['key' => 'updated', 'label' => 'Last edited'],
        ['key' => 'status', 'label' => 'Status'],
    ],
    'rows' => $rows,
    'empty' => ['title' => 'No pages found', 'text' => 'The installer seeds the six standard pages. Run the installer or import db/seed.sql to restore them.'],
]); ?>
