<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/catalogue.php';

const PRODUCTS_SORTS = ['date_desc', 'date_asc', 'name_asc', 'name_desc', 'price_asc', 'price_desc', 'stock_asc', 'stock_desc', 'best'];
const PRODUCTS_BULK_ACTIONS = ['live', 'hide', 'feature', 'unfeature', 'new', 'clear_new', 'move', 'delete'];

function products_ids_from_post(): array
{
    $raw = request_post('ids', []);
    if (!is_array($raw)) {
        return [];
    }
    $ids = [];
    foreach ($raw as $value) {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        if ($int !== false && $int > 0) {
            $ids[$int] = $int;
        }
    }
    return array_values($ids);
}

function products_existing(array $ids): array
{
    if ($ids === []) {
        return [];
    }
    $names = [];
    $bind = [];
    foreach (array_values($ids) as $i => $id) {
        $names[] = ':id' . $i;
        $bind['id' . $i] = $id;
    }
    return db_fetch_all('SELECT id, name, slug, is_active, is_featured, is_new, deleted_at, collection_id FROM products WHERE id IN (' . implode(', ', $names) . ')', $bind);
}

function products_delete_one(array $product): string
{
    $id = (int) $product['id'];
    if (catalogue_product_has_orders($id)) {
        db_update('products', ['is_active' => 0, 'deleted_at' => now_karachi(), 'updated_at' => now_karachi()], ['id' => $id]);
        catalogue_log('product', $id, 'product.retire', 'Retired ' . $product['name'] . ' (kept for order history)');
        return 'retired';
    }
    $images = db_fetch_all('SELECT filename FROM product_images WHERE product_id = :id', ['id' => $id]);
    db_delete('products', ['id' => $id]);
    foreach ($images as $image) {
        catalogue_remove_image_files((string) $image['filename']);
    }
    $dir = APP_ROOT . '/uploads/products/' . $id;
    if (is_dir($dir)) {
        @rmdir($dir);
    }
    catalogue_log('product', $id, 'product.delete', 'Deleted ' . $product['name'] . ' and its photos');
    return 'deleted';
}

function products_duplicate(array $source): int
{
    $sourceId = (int) $source['id'];
    $product = db_fetch('SELECT * FROM products WHERE id = :id', ['id' => $sourceId]);
    $now = now_karachi();
    $copy = $product;
    unset($copy['id'], $copy['rating_avg'], $copy['rating_count'], $copy['sales_count']);
    $copy['name'] = mb_substr($product['name'] . ' (copy)', 0, 120);
    $copy['slug'] = catalogue_slug_unique('products', substr(slugify($product['name'] . ' copy'), 0, 150));
    $copy['is_active'] = 0;
    $copy['deleted_at'] = null;
    $copy['created_at'] = $now;
    $copy['updated_at'] = null;
    $newId = db_insert('products', $copy);
    foreach (catalogue_product_sizes($sourceId) as $size) {
        $sku = mb_substr($size['sku'], 0, 34) . '-COPY';
        for ($n = 2; db_exists('SELECT id FROM product_sizes WHERE sku = :sku', ['sku' => $sku]); $n++) {
            $sku = mb_substr($size['sku'], 0, 33) . '-COPY' . $n;
        }
        db_insert('product_sizes', [
            'product_id' => $newId, 'size_label' => $size['size_label'], 'size_ml' => $size['size_ml'], 'sku' => $sku,
            'price' => $size['price'], 'sale_price' => $size['sale_price'], 'stock' => 0, 'low_stock_threshold' => $size['low_stock_threshold'],
            'is_default' => $size['is_default'], 'is_active' => 1, 'sort_order' => $size['sort_order'], 'created_at' => $now,
        ]);
    }
    foreach (catalogue_product_images($sourceId) as $image) {
        db_insert('product_images', [
            'product_id' => $newId, 'filename' => catalogue_image_copy($sourceId, (string) $image['filename'], $newId), 'alt_text' => $image['alt_text'],
            'width' => $image['width'], 'height' => $image['height'], 'sort_order' => $image['sort_order'], 'is_primary' => $image['is_primary'], 'created_at' => $now,
        ]);
    }
    catalogue_log('product', $newId, 'product.duplicate', 'Duplicated ' . $product['name'] . ' as ' . $copy['name']);
    return $newId;
}

function products_return_url(): string
{
    return request_return_path('/admin/products');
}

$routeName = (string) ($route['name'] ?? '');

if (in_array($routeName, ['admin.products.delete', 'admin.products.duplicate', 'admin.products.toggle'], true)) {
    $product = db_fetch('SELECT id, name, slug, is_active, deleted_at FROM products WHERE id = :id', ['id' => (int) $params['id']]);
    if ($product === null) {
        flash('error', 'That product no longer exists.');
        redirect('/admin/products', 303);
    }
    if ($routeName === 'admin.products.delete') {
        if ((string) request_post('confirm_word', '') !== 'DELETE') {
            flash('error', 'Type DELETE to confirm removing ' . $product['name'] . '.');
            redirect(products_return_url(), 303);
        }
        $outcome = db_transaction(static fn (): string => products_delete_one($product));
        flash('success', $outcome === 'retired'
            ? $product['name'] . ' is hidden from the shop. It stays on your past orders.'
            : $product['name'] . ' was deleted along with its photos.');
        redirect('/admin/products', 303);
    }
    if ($routeName === 'admin.products.duplicate') {
        $newId = db_transaction(static fn (): int => products_duplicate($product));
        flash('success', 'Copy of ' . $product['name'] . ' created as hidden with stock 0. Edit it now.');
        redirect('/admin/products/' . $newId, 303);
    }
    if ($product['deleted_at'] !== null) {
        db_update('products', ['deleted_at' => null, 'is_active' => 0, 'updated_at' => now_karachi()], ['id' => (int) $product['id']]);
        catalogue_log('product', (int) $product['id'], 'product.restore', 'Restored ' . $product['name'] . ' (still hidden)');
        flash('success', $product['name'] . ' restored. It is still hidden until you make it live.');
        redirect(products_return_url(), 303);
    }
    $live = (int) $product['is_active'] === 1 ? 0 : 1;
    db_update('products', ['is_active' => $live, 'updated_at' => now_karachi()], ['id' => (int) $product['id']]);
    catalogue_log('product', (int) $product['id'], $live ? 'product.live' : 'product.hide', ($live ? 'Made live: ' : 'Hidden: ') . $product['name']);
    flash('success', $product['name'] . ($live ? ' is now live.' : ' is hidden from the shop.'));
    redirect(products_return_url(), 303);
}

if ($routeName === 'admin.products.bulk') {
    $action = (string) request_post('action', '');
    $ids = products_ids_from_post();
    if (!in_array($action, PRODUCTS_BULK_ACTIONS, true)) {
        flash('error', 'Choose a bulk action first.');
        redirect(products_return_url(), 303);
    }
    if ($ids === []) {
        flash('error', 'Select at least one product first.');
        redirect(products_return_url(), 303);
    }
    $existing = products_existing($ids);
    $missing = count($ids) - count($existing);
    $noun = static fn (int $n): string => $n === 1 ? 'product' : 'products';
    $tail = $missing > 0 ? ' — ' . $missing . ' no longer exist.' : '.';
    if ($action === 'move') {
        $collectionId = catalogue_int_input(request_post('collection_id', ''), 1, 4294967295);
        if ($collectionId === null || $collectionId === PHP_INT_MIN || !db_exists('SELECT id FROM collections WHERE id = :id', ['id' => $collectionId])) {
            $bulkMove = ['ids' => array_column($existing, 'id'), 'names' => array_column($existing, 'name')];
        } else {
            $now = now_karachi();
            foreach ($existing as $row) {
                db_update('products', ['collection_id' => $collectionId, 'updated_at' => $now], ['id' => (int) $row['id']]);
            }
            $collectionName = (string) db_fetch_column('SELECT name FROM collections WHERE id = :id', ['id' => $collectionId]);
            catalogue_log('product', null, 'product.bulk_move', count($existing) . ' moved to ' . $collectionName, null, ['ids' => array_column($existing, 'id')]);
            flash('success', count($existing) . ' of ' . count($ids) . ' ' . $noun(count($ids)) . ' moved to ' . $collectionName . $tail);
            redirect('/admin/products', 303);
        }
    } elseif ($action === 'delete') {
        if ((string) request_post('confirm_word', '') !== 'DELETE') {
            flash('error', 'Type DELETE to confirm removing ' . count($existing) . ' ' . $noun(count($existing)) . '.');
            redirect(products_return_url(), 303);
        }
        $outcomes = db_transaction(static function () use ($existing): array {
            $out = ['retired' => 0, 'deleted' => 0];
            foreach ($existing as $row) {
                $out[products_delete_one($row)]++;
            }
            return $out;
        });
        $parts = [];
        if ($outcomes['deleted'] > 0) {
            $parts[] = $outcomes['deleted'] . ' deleted';
        }
        if ($outcomes['retired'] > 0) {
            $parts[] = $outcomes['retired'] . ' hidden (they appear on past orders)';
        }
        flash('success', implode(', ', $parts) . ' of ' . count($ids) . ' selected' . $tail);
        redirect('/admin/products', 303);
    } else {
        $sets = [
            'live' => [['is_active' => 1], 'made live'], 'hide' => [['is_active' => 0], 'hidden'],
            'feature' => [['is_featured' => 1], 'featured'], 'unfeature' => [['is_featured' => 0], 'unfeatured'],
            'new' => [['is_new' => 1], 'marked as new'], 'clear_new' => [['is_new' => 0], 'cleared of the NEW badge'],
        ];
        [$data, $verb] = $sets[$action];
        $now = now_karachi();
        foreach ($existing as $row) {
            db_update('products', $data + ['updated_at' => $now], ['id' => (int) $row['id']]);
        }
        catalogue_log('product', null, 'product.bulk_' . $action, count($existing) . ' ' . $verb, null, ['ids' => array_column($existing, 'id')]);
        flash('success', count($existing) . ' of ' . count($ids) . ' ' . $noun(count($ids)) . ' ' . $verb . $tail);
        redirect('/admin/products', 303);
    }
}

$threshold = catalogue_low_threshold();
$per = max(5, min(100, setting_int('admin_rows_per_page', 20)));
$page = max(1, (int) request_query('page', 1));
$q = mb_substr(trim((string) request_query('q', '')), 0, 60);
$collectionFilter = catalogue_int_input(request_query('collection_id', ''), 1, 4294967295);
$collectionFilter = $collectionFilter === PHP_INT_MIN ? null : $collectionFilter;
$genderFilter = in_array(request_query('gender', ''), GENDERS, true) ? (string) request_query('gender') : '';
$familyFilter = mb_substr(trim((string) request_query('scent_family', '')), 0, 60);
$statusFilter = in_array(request_query('status', ''), ['all', 'live', 'hidden', 'retired'], true) ? (string) request_query('status') : '';
$stockFilter = in_array(request_query('stock', ''), ['low', 'out'], true) ? (string) request_query('stock') : '';
$flagFilter = in_array(request_query('flag', ''), ['featured', 'new', 'on_sale'], true) ? (string) request_query('flag') : '';
$sort = in_array(request_query('sort', ''), PRODUCTS_SORTS, true) ? (string) request_query('sort') : 'date_desc';

$where = [];
$bind = ['thr1' => $threshold, 'thr2' => $threshold, 'since' => date('Y-m-d H:i:s', time() - 30 * 86400)];
$where[] = match ($statusFilter) {
    'all' => '1 = 1',
    'live' => 'p.is_active = 1 AND p.deleted_at IS NULL',
    'hidden' => 'p.is_active = 0 AND p.deleted_at IS NULL',
    'retired' => 'p.deleted_at IS NOT NULL',
    default => 'p.deleted_at IS NULL',
};
if ($q !== '') {
    $bind['q1'] = $bind['q2'] = $bind['q3'] = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(p.name LIKE :q1 OR p.slug LIKE :q2 OR EXISTS (SELECT 1 FROM product_sizes sq WHERE sq.product_id = p.id AND sq.sku LIKE :q3))';
}
if ($collectionFilter !== null) {
    $bind['collection_id'] = $collectionFilter;
    $where[] = 'p.collection_id = :collection_id';
}
if ($genderFilter !== '') {
    $bind['gender'] = $genderFilter;
    $where[] = 'p.gender = :gender';
}
if ($familyFilter !== '') {
    $bind['family'] = $familyFilter;
    $where[] = 'p.scent_family = :family';
}
if ($flagFilter === 'featured') {
    $where[] = 'p.is_featured = 1';
} elseif ($flagFilter === 'new') {
    $where[] = 'p.is_new = 1';
} elseif ($flagFilter === 'on_sale') {
    $where[] = 'EXISTS (SELECT 1 FROM product_sizes so WHERE so.product_id = p.id AND so.is_active = 1 AND so.sale_price IS NOT NULL)';
}
$inner = 'SELECT p.id, p.name, p.slug, p.gender, p.scent_family, p.is_active, p.is_featured, p.is_new, p.deleted_at, p.created_at, p.published_at, p.sort_order, c.name AS collection_name,
    (SELECT MIN(COALESCE(s.sale_price, s.price)) FROM product_sizes s WHERE s.product_id = p.id AND s.is_active = 1) AS price_min,
    (SELECT MAX(COALESCE(s.sale_price, s.price)) FROM product_sizes s WHERE s.product_id = p.id AND s.is_active = 1) AS price_max,
    (SELECT COALESCE(SUM(s.stock), 0) FROM product_sizes s WHERE s.product_id = p.id AND s.is_active = 1) AS stock_total,
    (SELECT COUNT(*) FROM product_sizes s WHERE s.product_id = p.id AND s.is_active = 1) AS size_count,
    (SELECT COUNT(*) FROM product_sizes s WHERE s.product_id = p.id AND s.is_active = 1 AND s.stock <= 0) AS out_count,
    (SELECT COUNT(*) FROM product_sizes s WHERE s.product_id = p.id AND s.is_active = 1 AND s.stock > 0 AND COALESCE(s.low_stock_threshold, :thr1) > 0 AND s.stock <= COALESCE(s.low_stock_threshold, :thr2)) AS low_count,
    (SELECT i.filename FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.sort_order ASC LIMIT 1) AS image,
    (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.product_id = p.id AND o.status <> \'cancelled\' AND o.created_at >= :since) AS sold_30d
    FROM products p LEFT JOIN collections c ON c.id = p.collection_id WHERE ' . implode(' AND ', $where);
$outer = match ($stockFilter) {
    'low' => ' WHERE x.low_count > 0',
    'out' => ' WHERE x.size_count > 0 AND x.out_count = x.size_count',
    default => '',
};
$orderBy = match ($sort) {
    'date_asc' => 'x.created_at ASC, x.id ASC',
    'name_asc' => 'x.name ASC',
    'name_desc' => 'x.name DESC',
    'price_asc' => 'x.price_min IS NULL, x.price_min ASC, x.name ASC',
    'price_desc' => 'x.price_min IS NULL, x.price_min DESC, x.name ASC',
    'stock_asc' => 'x.stock_total ASC, x.name ASC',
    'stock_desc' => 'x.stock_total DESC, x.name ASC',
    'best' => 'x.sold_30d DESC, x.name ASC',
    default => 'x.created_at DESC, x.id DESC',
};
$wrapped = 'SELECT * FROM (' . $inner . ') AS x' . $outer;
$total = (int) db_fetch_column('SELECT COUNT(*) FROM (' . $wrapped . ') AS counted', $bind);
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);
$products = db_fetch_all($wrapped . ' ORDER BY ' . $orderBy . ' LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $bind);

$query = array_filter([
    'q' => $q, 'collection_id' => $collectionFilter, 'gender' => $genderFilter, 'scent_family' => $familyFilter,
    'status' => $statusFilter, 'stock' => $stockFilter, 'flag' => $flagFilter, 'sort' => $sort === 'date_desc' ? '' : $sort,
], static fn ($v): bool => $v !== '' && $v !== null);
$removeUrl = static function (string $key) use ($query): string {
    $rest = $query;
    unset($rest[$key]);
    return '/admin/products' . ($rest === [] ? '' : '?' . http_build_query($rest));
};
$collectionsOptions = catalogue_collections_options(false);
$familyOptions = catalogue_families_options(false);
$activeChips = [];
if ($collectionFilter !== null && isset($collectionsOptions[(string) $collectionFilter])) {
    $activeChips[] = ['label' => 'Collection: ' . $collectionsOptions[(string) $collectionFilter], 'remove_url' => $removeUrl('collection_id')];
}
if ($genderFilter !== '') {
    $activeChips[] = ['label' => CATALOGUE_GENDER_LABELS[$genderFilter], 'remove_url' => $removeUrl('gender')];
}
if ($familyFilter !== '') {
    $activeChips[] = ['label' => 'Family: ' . $familyFilter, 'remove_url' => $removeUrl('scent_family')];
}
if ($statusFilter !== '') {
    $activeChips[] = ['label' => 'Status: ' . ucfirst($statusFilter), 'remove_url' => $removeUrl('status')];
}
if ($stockFilter !== '') {
    $activeChips[] = ['label' => $stockFilter === 'low' ? 'Low stock' : 'Sold out', 'remove_url' => $removeUrl('stock')];
}
if ($flagFilter !== '') {
    $activeChips[] = ['label' => ['featured' => 'Featured', 'new' => 'New', 'on_sale' => 'On sale'][$flagFilter], 'remove_url' => $removeUrl('flag')];
}

$outCount = (int) db_fetch_column('SELECT COUNT(*) FROM products p WHERE p.deleted_at IS NULL AND p.is_active = 1 AND EXISTS (SELECT 1 FROM product_sizes s WHERE s.product_id = p.id AND s.is_active = 1) AND NOT EXISTS (SELECT 1 FROM product_sizes s WHERE s.product_id = p.id AND s.is_active = 1 AND s.stock > 0)');
$lowCount = (int) db_fetch_column('SELECT COUNT(DISTINCT p.id) FROM products p JOIN product_sizes s ON s.product_id = p.id AND s.is_active = 1 WHERE p.deleted_at IS NULL AND p.is_active = 1 AND s.stock > 0 AND COALESCE(s.low_stock_threshold, :thr1) > 0 AND s.stock <= COALESCE(s.low_stock_threshold, :thr2)', ['thr1' => $threshold, 'thr2' => $threshold]);

render_admin('products.php', [
    'products' => $products,
    'total' => $total,
    'page' => $page,
    'per' => $per,
    'sort' => $sort,
    'query' => $query,
    'filters' => ['q' => $q, 'collection_id' => $collectionFilter, 'gender' => $genderFilter, 'scent_family' => $familyFilter, 'status' => $statusFilter, 'stock' => $stockFilter, 'flag' => $flagFilter],
    'activeChips' => $activeChips,
    'collectionsOptions' => $collectionsOptions,
    'familyOptions' => $familyOptions,
    'threshold' => $threshold,
    'outCount' => $outCount,
    'lowCount' => $lowCount,
    'bulkMove' => $bulkMove ?? null,
], ['title' => 'Products', 'body_class' => 'admin t-light admin-products', 'wide' => true]);
