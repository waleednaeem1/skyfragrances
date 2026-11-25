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
        'actions' => [
            ['label' => 'Edit ' . $p['title'], 'href' => $editUrl, 'icon' => 'pencil'],
            ['label' => 'View ' . $p['title'] . ' on the site', 'icon' => 'external', 'href' => url('/' . $p['slug']), 'external' => true],
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
        ['key' => 'updated', 'label' => 'Last edited', 'class' => 'adm-nowrap'],
        ['key' => 'status', 'label' => 'Status'],
    ],
    'rows' => $rows,
    'empty' => ['icon' => 'file', 'title' => 'No pages found', 'text' => 'The installer seeds the six standard pages. Run the installer or import db/seed.sql to restore them.'],
]); ?>
