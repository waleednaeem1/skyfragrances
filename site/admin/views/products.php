<?php
defined('SKYFR') || exit;
$products = $products ?? [];
$filters = $filters ?? [];
$query = $query ?? [];
$threshold = (int) ($threshold ?? 5);
$badge = static function (string $status, array $extra = []): string {
    ob_start();
    partial_admin('badge.php', ['status' => $status] + $extra);
    return (string) ob_get_clean();
};
$moneyHtml = static function (string $value): string {
    ob_start();
    partial_admin('money.php', ['value' => $value]);
    return (string) ob_get_clean();
};
$statusOf = static fn (array $p): array => $p['deleted_at'] !== null ? ['retired', 'Retired', 'muted'] : ((int) $p['is_active'] === 1 ? ['live', 'Live', 'green'] : ['hidden', 'Hidden', 'muted']);
$rows = [];
foreach ($products as $p) {
    $id = (int) $p['id'];
    [$statusKey, $statusLabel, $statusTone] = $statusOf($p);
    $sizeCount = (int) $p['size_count'];
    $stockState = $sizeCount === 0 ? 'none' : ((int) $p['out_count'] === $sizeCount ? 'out' : ((int) $p['low_count'] > 0 ? 'low' : 'ok'));
    $stockHtml = '<span class="adm-stock' . ($stockState === 'out' || $stockState === 'low' ? ' adm-stock--low' : '') . '">' . ($sizeCount === 0 ? 'No sizes' : e((string) (int) $p['stock_total']) . ($stockState === 'out' ? ' · sold out' : ($stockState === 'low' ? ' · low' : ''))) . '</span>';
    if ($p['price_min'] === null) {
        $priceHtml = '<span class="adm-muted">No price</span>';
    } elseif (money_paisa((string) $p['price_min']) === money_paisa((string) $p['price_max'])) {
        $priceHtml = $moneyHtml((string) $p['price_min']);
    } else {
        $priceHtml = $moneyHtml((string) $p['price_min']) . ' – ' . $moneyHtml((string) $p['price_max']);
    }
    $flags = ((int) $p['is_featured'] === 1 ? ' ' . $badge('featured', ['label' => '★ Featured', 'tone' => 'champagne', 'small' => true]) : '')
        . ((int) $p['is_new'] === 1 ? ' ' . $badge('new', ['label' => 'New', 'small' => true]) : '');
    $thumb = '<img class="adm-thumb" src="' . e(catalogue_product_image_url($id, $p['image'], 'thumb')) . '" alt="" width="48" height="60" loading="lazy">';
    $media = '<div class="adm-media">' . $thumb . '<div class="adm-media__body"><span class="adm-media__title">' . e($p['name']) . '</span><span class="adm-media__meta adm-mono">' . e($p['slug']) . '</span></div></div>';
    $cardTitle = '<div class="adm-media">' . $thumb . '<span class="adm-media__title">' . e($p['name']) . '</span></div>';
    $actions = [
        ['label' => 'Edit', 'href' => '/admin/products/' . $id],
        ['label' => 'View on site', 'href' => url('/product/' . $p['slug']), 'external' => true],
        ['label' => 'Duplicate', 'post' => '/admin/products/' . $id . '/duplicate'],
    ];
    if ($p['deleted_at'] !== null) {
        $actions[] = ['label' => 'Restore', 'post' => '/admin/products/' . $id . '/toggle'];
    } else {
        $actions[] = ['label' => (int) $p['is_active'] === 1 ? 'Hide' : 'Make live', 'post' => '/admin/products/' . $id . '/toggle'];
        $actions[] = ['label' => 'Delete', 'variant' => 'danger', 'post' => '/admin/products/' . $id . '/delete', 'confirm' => "Delete " . $p['name'] . "?\nIf it appears on any order it is hidden instead and kept for order history. Otherwise it and its photos are removed for good.", 'confirm_word' => 'DELETE', 'confirm_label' => 'Yes, delete'];
    }
    $rows[] = [
        'id' => $id,
        'href' => '/admin/products/' . $id,
        'class' => $stockState === 'out' ? 'is-out' : '',
        'cells' => [
            'name' => $media,
            'collection' => e((string) ($p['collection_name'] ?? '—')),
            'gender' => e(CATALOGUE_GENDER_LABELS[$p['gender']] ?? ucfirst((string) $p['gender'])),
            'price' => $priceHtml,
            'stock' => $stockHtml,
            'status' => $badge($statusKey, ['label' => $statusLabel, 'tone' => $statusTone]) . $flags,
            'sold' => '<span class="adm-num">' . e((string) (int) $p['sold_30d']) . '</span>',
        ],
        'card' => [
            'title' => $cardTitle,
            'chip' => $badge($statusKey, ['label' => $statusLabel, 'tone' => $statusTone]),
            'sub' => trim($flags) !== '' ? trim($flags) : null,
            'lines' => [
                ['label' => 'Collection', 'html' => e((string) ($p['collection_name'] ?? '—')) . ' · ' . e(CATALOGUE_GENDER_LABELS[$p['gender']] ?? '')],
                ['label' => 'Price', 'html' => $priceHtml],
            ],
            'money' => '<span class="adm-muted" style="font-weight:400;font-size:.8rem">Stock</span> ' . $stockHtml,
            'inline_actions' => 1,
        ],
        'actions' => $actions,
    ];
}
$bulkButtons = [
    ['label' => 'Make live', 'value' => 'live'], ['label' => 'Hide', 'value' => 'hide'],
    ['label' => 'Feature', 'value' => 'feature'], ['label' => 'Unfeature', 'value' => 'unfeature'],
    ['label' => 'Mark as new', 'value' => 'new'], ['label' => 'Clear new', 'value' => 'clear_new'],
    ['label' => 'Move to collection…', 'value' => 'move'],
    ['label' => 'Delete', 'value' => 'delete', 'variant' => 'danger', 'confirm' => "Delete the selected products?\nProducts on past orders are hidden instead of removed. The rest are deleted with their photos.", 'confirm_word' => 'DELETE', 'confirm_label' => 'Yes, delete'],
];
?>
<?php partial_admin('page-header.php', ['title' => 'Products', 'subtitle' => $total . ' in the catalogue', 'actions' => [
    ['label' => 'Add product', 'href' => '/admin/products/new', 'variant' => 'gold'],
    ['label' => 'Collections', 'href' => '/admin/collections'],
]]); ?>
<?php if (!empty($bulkMove) && is_array($bulkMove)): ?>
<div class="adm-card adm-form">
  <h2 class="adm-card__title">Move <?= e((string) count($bulkMove['ids'])) ?> <?= count($bulkMove['ids']) === 1 ? 'product' : 'products' ?> to a collection</h2>
  <p class="adm-muted"><?= e(implode(', ', array_slice($bulkMove['names'], 0, 6))) ?><?= count($bulkMove['names']) > 6 ? ' and ' . e((string) (count($bulkMove['names']) - 6)) . ' more' : '' ?></p>
  <form method="post" action="<?= e(url('/admin/products/bulk')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="move">
<?php foreach ($bulkMove['ids'] as $moveId): ?>
    <input type="hidden" name="ids[]" value="<?= e((string) (int) $moveId) ?>">
<?php endforeach; ?>
    <?php partial_admin('field.php', ['type' => 'select', 'name' => 'collection_id', 'label' => 'Collection', 'options' => ['' => 'Choose a collection'] + $collectionsOptions, 'required' => true, 'error' => request_post('collection_id') !== null ? 'Choose a collection to move them into.' : '']); ?>
    <div class="adm-cluster">
      <?php partial_admin('button.php', ['label' => 'Move', 'variant' => 'gold']); ?>
      <?php partial_admin('button.php', ['label' => 'Cancel', 'href' => '/admin/products', 'variant' => 'text']); ?>
    </div>
  </form>
</div>
<?php endif; ?>
<?php if ($outCount > 0 || $lowCount > 0): ?>
<div class="adm-rail" aria-label="Stock attention">
<?php if ($outCount > 0): ?>
  <a class="adm-chip adm-chip--gold<?= ($filters['stock'] ?? '') === 'out' ? ' is-active' : '' ?>" href="<?= e(url('/admin/products?stock=out')) ?>">Sold out · <?= e((string) $outCount) ?></a>
<?php endif; ?>
<?php if ($lowCount > 0): ?>
  <a class="adm-chip<?= ($filters['stock'] ?? '') === 'low' ? ' is-active' : '' ?>" href="<?= e(url('/admin/products?stock=low')) ?>">Low stock · <?= e((string) $lowCount) ?></a>
<?php endif; ?>
</div>
<?php endif; ?>
<?php partial_admin('filters.php', [
    'action' => '/admin/products',
    'search' => ['name' => 'q', 'value' => $filters['q'] ?? '', 'placeholder' => 'Search name, slug or SKU', 'maxlength' => 60],
    'fields' => [
        ['type' => 'select', 'name' => 'collection_id', 'label' => 'Collection', 'value' => (string) ($filters['collection_id'] ?? ''), 'options' => ['' => 'All collections'] + $collectionsOptions],
        ['type' => 'select', 'name' => 'gender', 'label' => 'Gender', 'value' => $filters['gender'] ?? '', 'options' => ['' => 'Any gender'] + CATALOGUE_GENDER_LABELS],
        ['type' => 'select', 'name' => 'scent_family', 'label' => 'Scent family', 'value' => $filters['scent_family'] ?? '', 'options' => ['' => 'Any family'] + $familyOptions],
        ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'value' => $filters['status'] ?? '', 'options' => ['' => 'Live and hidden', 'live' => 'Live', 'hidden' => 'Hidden', 'retired' => 'Retired', 'all' => 'Everything']],
        ['type' => 'select', 'name' => 'stock', 'label' => 'Stock', 'value' => $filters['stock'] ?? '', 'options' => ['' => 'Any stock', 'low' => 'Low stock', 'out' => 'Sold out']],
        ['type' => 'select', 'name' => 'flag', 'label' => 'Flag', 'value' => $filters['flag'] ?? '', 'options' => ['' => 'Any', 'featured' => 'Featured', 'new' => 'New', 'on_sale' => 'On sale']],
        ['type' => 'select', 'name' => 'sort', 'label' => 'Sort', 'value' => $sort, 'options' => ['date_desc' => 'Newest', 'name_asc' => 'Name A–Z', 'price_asc' => 'Price low → high', 'price_desc' => 'Price high → low', 'stock_asc' => 'Stock low → high', 'best' => 'Best selling 30d']],
    ],
    'active' => $activeChips,
    'clear_url' => '/admin/products',
    'count_text' => '',
]); ?>
<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'products', 'query' => $query]); ?>
<?php partial_admin('table.php', [
    'caption' => 'Products',
    'list_id' => 'products-list',
    'columns' => [
        ['key' => 'name', 'label' => 'Product', 'sort' => 'name'],
        ['key' => 'collection', 'label' => 'Collection'],
        ['key' => 'gender', 'label' => 'Gender'],
        ['key' => 'price', 'label' => 'Price', 'sort' => 'price', 'class' => 'adm-table__td--money'],
        ['key' => 'stock', 'label' => 'Stock', 'sort' => 'stock', 'align' => 'right'],
        ['key' => 'sold', 'label' => 'Sold 30d', 'sort' => 'best', 'sort_plain' => true, 'align' => 'right'],
        ['key' => 'status', 'label' => 'Status'],
    ],
    'sort' => ['param' => 'sort', 'current' => $sort],
    'rows' => $rows,
    'bulk' => ['action' => '/admin/products/bulk', 'buttons' => $bulkButtons],
    'empty' => $total === 0 && $query === []
        ? ['title' => 'No products yet', 'text' => 'Add your first perfume. Sizes, prices and photos all live on the product form.', 'action' => ['label' => 'Add product', 'href' => '/admin/products/new']]
        : ['title' => 'No products match', 'text' => 'Try a different search or clear the filters.', 'action' => ['label' => 'Clear filters', 'href' => '/admin/products', 'variant' => 'ghost']],
]); ?>
<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'products', 'query' => $query]); ?>
<?php if ($threshold === 0): ?>
<p class="adm-note">Low-stock alerts are off (Settings › Store › Low stock threshold is 0). Sold-out sizes are still flagged.</p>
<?php endif; ?>
