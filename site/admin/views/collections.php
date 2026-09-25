<?php
defined('SKYFR') || exit;
$collections = $collections ?? [];
$badge = static function (string $status, array $extra = []): string {
    ob_start();
    partial_admin('badge.php', ['status' => $status] + $extra);
    return (string) ob_get_clean();
};
$rows = [];
$count = count($collections);
foreach ($collections as $i => $c) {
    $id = (int) $c['id'];
    $live = (int) $c['is_active'] === 1;
    $products = (int) $c['product_count'];
    $thumb = '<img class="adm-thumb" src="' . e(catalogue_collection_image_url($c['image'], 'card')) . '" alt="" width="48" height="60" loading="lazy">';
    $media = '<div class="adm-media">' . $thumb . '<div class="adm-media__body"><span class="adm-media__title">' . e($c['name']) . '</span><span class="adm-media__meta adm-mono">' . e($c['slug']) . '</span></div></div>';
    $cardTitle = '<div class="adm-media">' . $thumb . '<span class="adm-media__title">' . e($c['name']) . '</span></div>';
    $chip = $badge($live ? 'live' : 'hidden') . ((int) $c['show_on_home'] === 1 ? ' ' . $badge('home', ['label' => 'Home', 'tone' => 'champagne', 'small' => true]) : '');
    $actions = [
        ['label' => 'Edit', 'href' => '/admin/collections/' . $id],
        ['label' => 'Move up', 'post' => '/admin/collections/' . $id, 'fields' => ['move' => 'up'], 'disabled' => $i === 0],
        ['label' => 'Move down', 'post' => '/admin/collections/' . $id, 'fields' => ['move' => 'down'], 'disabled' => $i === $count - 1],
        ['label' => 'View on site', 'href' => url('/collections/' . $c['slug']), 'external' => true],
        ['label' => 'Products in it', 'href' => '/admin/products?collection_id=' . $id . '&status=all'],
        ['label' => $live ? 'Hide' : 'Make live', 'post' => '/admin/collections/' . $id, 'fields' => ['toggle' => '1']],
    ];
    if ($products === 0) {
        $actions[] = ['label' => 'Delete', 'variant' => 'danger', 'post' => '/admin/collections/' . $id . '/delete', 'confirm' => 'Delete ' . $c['name'] . "?\nIt has no products. The landing page and its image are removed for good.", 'confirm_word' => 'DELETE', 'confirm_label' => 'Yes, delete'];
    }
    $rows[] = [
        'id' => $id,
        'href' => '/admin/collections/' . $id,
        'cells' => [
            'name' => $media,
            'products' => '<span class="adm-num">' . e((string) $products) . '</span>' . ((int) $c['live_count'] !== $products ? ' <span class="adm-muted">(' . e((string) (int) $c['live_count']) . ' live)</span>' : ''),
            'order' => '<span class="adm-num">' . e((string) (int) $c['sort_order']) . '</span>',
            'status' => $chip,
        ],
        'card' => [
            'title' => $cardTitle,
            'chip' => $badge($live ? 'live' : 'hidden'),
            'lines' => [
                ['label' => 'Products', 'html' => e((string) $products) . ((int) $c['live_count'] !== $products ? ' (' . e((string) (int) $c['live_count']) . ' live)' : '')],
                ['label' => 'Order', 'html' => e((string) (int) $c['sort_order']) . ((int) $c['show_on_home'] === 1 ? ' · on the home page' : '')],
            ],
            'inline_actions' => 3,
        ],
        'actions' => $actions,
    ];
}
?>
<?php partial_admin('page-header.php', ['title' => 'Collections', 'subtitle' => count($collections) . ' collection' . (count($collections) === 1 ? '' : 's') . '. Order here is the order on the shop.', 'actions' => [
    ['label' => 'Add collection', 'href' => '/admin/collections/new', 'variant' => 'gold'],
    ['label' => 'Scent families', 'href' => '/admin/scent-families'],
]]); ?>
<?php if (!empty($uncollected)): ?>
<div class="adm-banner adm-banner--warn"><?= e((string) $uncollected) ?> product<?= $uncollected === 1 ? ' has' : 's have' ?> no collection and will not appear on any collection page. <a href="<?= e(url('/admin/products?status=all')) ?>">Review products</a>.</div>
<?php endif; ?>
<?php partial_admin('table.php', [
    'caption' => 'Collections',
    'list_id' => 'collections-list',
    'columns' => [
        ['key' => 'name', 'label' => 'Collection'],
        ['key' => 'products', 'label' => 'Products', 'align' => 'right'],
        ['key' => 'order', 'label' => 'Order', 'align' => 'right'],
        ['key' => 'status', 'label' => 'Status'],
    ],
    'rows' => $rows,
    'empty' => ['title' => 'No collections yet', 'text' => 'Collections group perfumes into landing pages such as /collections/midnight-meridian.', 'action' => ['label' => 'Add collection', 'href' => '/admin/collections/new']],
]); ?>
<p class="adm-note">Hiding a collection keeps its products live; deleting is only possible once it is empty.</p>
