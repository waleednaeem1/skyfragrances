<?php
defined('SKYFR') || exit;
$families = $families ?? [];
$orphans = $orphans ?? [];
$badge = static function (string $status): string {
    ob_start();
    partial_admin('badge.php', ['status' => $status]);
    return (string) ob_get_clean();
};
$rows = [];
foreach ($families as $f) {
    $id = (int) $f['id'];
    $live = (int) $f['is_active'] === 1;
    $title = '<span class="adm-media__title">' . e($f['name']) . '</span><span class="adm-media__meta adm-mono">/scent/' . e($f['slug']) . '</span>';
    $rows[] = [
        'id' => $id,
        'href' => '/admin/scent-families/' . $id,
        'cells' => [
            'name' => '<div class="adm-media__body">' . $title . '</div>',
            'intro' => '<span class="adm-muted">' . e(truncate_on_word((string) ($f['intro'] ?? ''), 90)) . '</span>',
            'products' => '<span class="adm-num">' . e((string) (int) $f['product_count']) . '</span>',
            'order' => '<span class="adm-num">' . e((string) (int) $f['sort_order']) . '</span>',
            'status' => $badge($live ? 'live' : 'hidden'),
        ],
        'card' => [
            'title' => '<span class="adm-media__title">' . e($f['name']) . '</span>',
            'chip' => $badge($live ? 'live' : 'hidden'),
            'lines' => [
                ['label' => 'Products', 'html' => e((string) (int) $f['product_count'])],
                ['label' => 'Order', 'html' => e((string) (int) $f['sort_order'])],
            ],
            'inline_actions' => 2,
        ],
        'actions' => [
            ['label' => 'Edit', 'href' => '/admin/scent-families/' . $id],
            ['label' => 'Products', 'href' => '/admin/products?scent_family=' . rawurlencode((string) $f['name']) . '&status=all'],
            ['label' => 'View on site', 'href' => url('/scent/' . $f['slug']), 'external' => true],
            ['label' => $live ? 'Hide' : 'Make live', 'post' => '/admin/scent-families/' . $id . '/toggle', 'confirm' => $live && (int) $f['product_count'] > 0 ? 'Hide ' . $f['name'] . "?\nIts /scent page stops working until you make it live again. The " . (int) $f['product_count'] . ' products in it stay on sale.' : null],
        ],
    ];
}
?>
<?php partial_admin('page-header.php', ['title' => 'Scent families', 'subtitle' => 'The closed list a product can belong to. Each one owns a /scent landing page.', 'actions' => [
    ['label' => 'Add scent family', 'href' => '/admin/scent-families/new', 'variant' => 'gold'],
    ['label' => 'Collections', 'href' => '/admin/collections'],
]]); ?>
<?php if ($orphans !== []): ?>
<div class="adm-banner adm-banner--warn">Some products carry a family name that is not in this list: <?php foreach ($orphans as $i => $o): ?><?= $i > 0 ? ', ' : '' ?><strong><?= e((string) $o['scent_family']) ?></strong> (<?= e((string) (int) $o['n']) ?>)<?php endforeach; ?>. Add it here with exactly that name, or re-assign those products.</div>
<?php endif; ?>
<?php partial_admin('table.php', [
    'caption' => 'Scent families',
    'list_id' => 'families-list',
    'columns' => [
        ['key' => 'name', 'label' => 'Family'],
        ['key' => 'intro', 'label' => 'Intro'],
        ['key' => 'products', 'label' => 'Products', 'align' => 'right'],
        ['key' => 'order', 'label' => 'Order', 'align' => 'right'],
        ['key' => 'status', 'label' => 'Status'],
    ],
    'rows' => $rows,
    'empty' => ['title' => 'No scent families yet', 'text' => 'Add Fresh & Citrus, Floral, Amber & Spice, Oud & Smoke and Green & Earthy to start.', 'action' => ['label' => 'Add scent family', 'href' => '/admin/scent-families/new']],
]); ?>
<p class="adm-note">Families cannot be deleted — a hidden family keeps its name on products and can be made live again. Renaming one renames it on every product.</p>
