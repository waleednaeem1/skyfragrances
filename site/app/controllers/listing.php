<?php
defined('SKYFR') || exit;

const LISTING_PER_PAGE = 24;
const LISTING_SORTS = [
    'featured' => 'Featured',
    'newest' => 'Newest',
    'price-asc' => 'Price: Low to High',
    'price-desc' => 'Price: High to Low',
    'best' => 'Best Selling',
    'rating' => 'Top Rated',
];
const LISTING_PARAM_ORDER = ['collection', 'gender', 'family', 'price', 'on_sale', 'in_stock', 'q', 'sort', 'page'];
const LISTING_PRICE_CAP = 999999;

function listing_resolve_preset(array $preset, array $params): array
{
    foreach ($preset as $key => $value) {
        if (is_string($value) && preg_match('/^\{([a-z_]+)\}$/', $value, $m)) {
            $preset[$key] = $params[$m[1]] ?? '';
        }
    }
    return $preset;
}

function listing_redirect_if_renamed(string $entityType, string $slug, string $pathPrefix): void
{
    $redirect = db_fetch(
        'SELECT id, new_slug FROM slug_redirects WHERE entity_type = :type AND old_slug = :slug',
        ['type' => $entityType, 'slug' => $slug]
    );
    if ($redirect === null) {
        return;
    }
    db_query('UPDATE slug_redirects SET hit_count = hit_count + 1 WHERE id = :id', ['id' => $redirect['id']]);
    redirect($pathPrefix . '/' . $redirect['new_slug'], 301);
}

function listing_slug_list(mixed $raw): array
{
    if (is_array($raw)) {
        $raw = implode(',', array_filter($raw, 'is_string'));
    }
    if (!is_string($raw) || $raw === '') {
        return [];
    }
    $values = array_filter(array_map('trim', explode(',', strtolower($raw))), static fn (string $v): bool => preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $v) === 1);
    $values = array_values(array_unique($values));
    sort($values);
    return $values;
}

function listing_price_band(mixed $raw): ?array
{
    if (!is_string($raw) || !preg_match('/^([0-9]{0,7})-([0-9]{0,7})$/', $raw, $m)) {
        return null;
    }
    $min = $m[1] === '' ? null : min(LISTING_PRICE_CAP, (int) $m[1]);
    $max = $m[2] === '' ? null : min(LISTING_PRICE_CAP, (int) $m[2]);
    if (($min === null && $max === null) || ($min !== null && $max !== null && $min >= $max)) {
        return null;
    }
    return ['min' => $min, 'max' => $max];
}

function listing_price_band_value(?array $band): string
{
    if ($band === null) {
        return '';
    }
    return ($band['min'] === null ? '' : (string) $band['min']) . '-' . ($band['max'] === null ? '' : (string) $band['max']);
}

function listing_bind(array &$bind, mixed $value): string
{
    $name = 'b' . count($bind);
    $bind[$name] = $value;
    return ':' . $name;
}

function listing_like(string $value): string
{
    return '%' . addcslashes($value, '%_\\') . '%';
}

function listing_sizes_subquery(): string
{
    return '(SELECT product_id,
        MIN(CASE WHEN sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price THEN sale_price ELSE price END) AS min_price,
        MAX(CASE WHEN sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price THEN sale_price ELSE price END) AS max_price,
        SUM(stock) AS total_stock,
        COUNT(*) AS size_count,
        MAX(CASE WHEN sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price THEN 1 ELSE 0 END) AS on_sale
        FROM product_sizes WHERE is_active = 1 GROUP BY product_id) s';
}

function listing_terms(string $q): array
{
    $terms = array_values(array_filter(array_unique(explode(' ', trim($q))), static fn (string $t): bool => mb_strlen($t) >= 2));
    return array_slice($terms, 0, 5);
}

function listing_in_list(array &$bind, array $values): string
{
    $placeholders = [];
    foreach ($values as $value) {
        $placeholders[] = listing_bind($bind, $value);
    }
    return '(' . implode(', ', $placeholders) . ')';
}

function listing_from(array $filters, array $skip, array &$bind): string
{
    $where = ['p.is_active = 1', 'p.deleted_at IS NULL'];
    if (!in_array('collection', $skip, true) && $filters['collection_ids'] !== []) {
        $where[] = 'p.collection_id IN ' . listing_in_list($bind, $filters['collection_ids']);
    }
    if (!in_array('gender', $skip, true) && $filters['gender'] !== []) {
        $where[] = 'p.gender IN ' . listing_in_list($bind, $filters['gender']);
    }
    if (!in_array('family', $skip, true) && $filters['family_names'] !== []) {
        $where[] = 'p.scent_family IN ' . listing_in_list($bind, $filters['family_names']);
    }
    if (!in_array('price', $skip, true) && $filters['price'] !== null) {
        if ($filters['price']['min'] !== null) {
            $where[] = 's.min_price >= ' . listing_bind($bind, (string) $filters['price']['min']);
        }
        if ($filters['price']['max'] !== null) {
            $where[] = 's.min_price < ' . listing_bind($bind, (string) $filters['price']['max']);
        }
    }
    if (!in_array('on_sale', $skip, true) && $filters['on_sale']) {
        $where[] = 's.on_sale = 1';
    }
    if (!in_array('in_stock', $skip, true) && $filters['in_stock']) {
        $where[] = 's.total_stock > 0';
    }
    foreach ($filters['terms'] as $term) {
        $like = listing_like($term);
        $where[] = '(p.name LIKE ' . listing_bind($bind, $like)
            . ' OR p.scent_family LIKE ' . listing_bind($bind, $like)
            . " OR CONCAT_WS(' ', p.notes_top, p.notes_heart, p.notes_base) LIKE " . listing_bind($bind, $like)
            . ' OR p.short_description LIKE ' . listing_bind($bind, $like)
            . ' OR p.description LIKE ' . listing_bind($bind, $like)
            . ' OR EXISTS (SELECT 1 FROM product_sizes k WHERE k.product_id = p.id AND k.is_active = 1 AND k.sku = ' . listing_bind($bind, strtoupper($term)) . '))';
    }
    return ' FROM products p JOIN ' . listing_sizes_subquery() . ' ON s.product_id = p.id WHERE ' . implode(' AND ', $where);
}

function listing_score_sql(string $q, array &$bind): string
{
    if ($q === '') {
        return '0';
    }
    $like = listing_like($q);
    $prefix = addcslashes($q, '%_\\') . '%';
    return '((CASE WHEN p.name = ' . listing_bind($bind, $q) . ' THEN 100 WHEN p.name LIKE ' . listing_bind($bind, $prefix) . ' THEN 80 WHEN p.name LIKE ' . listing_bind($bind, $like) . ' THEN 60 ELSE 0 END)'
        . ' + (CASE WHEN p.scent_family LIKE ' . listing_bind($bind, $like) . ' THEN 40 ELSE 0 END)'
        . " + (CASE WHEN CONCAT_WS(' ', p.notes_top, p.notes_heart, p.notes_base) LIKE " . listing_bind($bind, $like) . ' THEN 30 ELSE 0 END)'
        . ' + (CASE WHEN p.short_description LIKE ' . listing_bind($bind, $like) . ' THEN 20 ELSE 0 END)'
        . ' + (CASE WHEN p.description LIKE ' . listing_bind($bind, $like) . ' THEN 10 ELSE 0 END)'
        . ' + (CASE WHEN EXISTS (SELECT 1 FROM product_sizes k WHERE k.product_id = p.id AND k.is_active = 1 AND k.sku = ' . listing_bind($bind, strtoupper($q)) . ') THEN 100 ELSE 0 END))';
}

function listing_order_sql(string $sort, bool $searching): string
{
    $order = match ($sort) {
        'newest' => 'p.published_at DESC',
        'price-asc' => 's.min_price ASC',
        'price-desc' => 's.min_price DESC',
        'best' => 'p.sales_count DESC, p.rating_avg DESC',
        'rating' => 'p.rating_avg DESC, p.rating_count DESC',
        default => 'p.is_featured DESC, p.sort_order ASC',
    };
    return ($searching ? 'score DESC, ' : '') . '(s.total_stock = 0) ASC, ' . $order . ', p.id DESC';
}

function listing_count(array $filters, array $skip = []): int
{
    $bind = [];
    $from = listing_from($filters, $skip, $bind);
    return (int) db_fetch_column('SELECT COUNT(*)' . $from, $bind);
}

function listing_rows(array $filters, string $sort, int $limit, int $offset): array
{
    $bind = [];
    $from = listing_from($filters, [], $bind);
    $score = listing_score_sql($filters['q'], $bind);
    $rows = db_fetch_all(
        'SELECT p.id, p.name, p.slug, p.gender, p.scent_family, p.short_description, p.is_new, p.is_featured, p.rating_avg, p.rating_count, p.sales_count, p.published_at, p.collection_id,
            s.min_price, s.max_price, s.total_stock, s.size_count, s.on_sale, ' . $score . ' AS score'
        . $from . ' ORDER BY ' . listing_order_sql($sort, $filters['q'] !== '') . ' LIMIT ' . $limit . ' OFFSET ' . $offset,
        $bind
    );
    return listing_enrich($rows);
}

function listing_enrich(array $rows): array
{
    if ($rows === []) {
        return [];
    }
    $bind = [];
    $ids = substr(listing_in_list($bind, array_map(static fn (array $row): int => (int) $row['id'], $rows)), 1, -1);
    $sizesByProduct = [];
    foreach (db_fetch_all('SELECT id, product_id, size_label, price, sale_price, stock, is_default FROM product_sizes WHERE is_active = 1 AND product_id IN (' . $ids . ') ORDER BY sort_order ASC, size_ml ASC, id ASC', $bind) as $size) {
        $sizesByProduct[(int) $size['product_id']][] = $size;
    }
    $imagesByProduct = [];
    foreach (db_fetch_all('SELECT product_id, filename, alt_text FROM product_images WHERE product_id IN (' . $ids . ') ORDER BY is_primary DESC, sort_order ASC, id ASC', $bind) as $image) {
        $imagesByProduct[(int) $image['product_id']][] = $image;
    }
    foreach ($rows as &$row) {
        $id = (int) $row['id'];
        $row['sizes'] = $sizesByProduct[$id] ?? [];
        $images = $imagesByProduct[$id] ?? [];
        $row['image'] = $images[0]['filename'] ?? null;
        $row['image_alt'] = $images[0]['alt_text'] ?? '';
        $row['image_alt_filename'] = $images[1]['filename'] ?? null;
        $row['stock_total'] = (int) $row['total_stock'];
        $row['price_from'] = $row['min_price'];
        $row['url'] = url('/product/' . $row['slug']);
    }
    unset($row);
    return $rows;
}

function listing_best_sellers(int $limit): array
{
    return listing_rows(listing_empty_filters(), 'best', $limit, 0);
}

function listing_empty_filters(): array
{
    return ['collection_ids' => [], 'gender' => [], 'family_names' => [], 'price' => null, 'on_sale' => false, 'in_stock' => false, 'terms' => [], 'q' => ''];
}

function listing_facet_counts(array $filters, string $skip, string $column): array
{
    $bind = [];
    $from = listing_from($filters, [$skip], $bind);
    return db_fetch_pairs('SELECT ' . $column . ' AS facet, COUNT(*)' . $from . ' AND ' . $column . ' IS NOT NULL GROUP BY ' . $column, $bind);
}

function listing_price_bands(array $filters): array
{
    $bind = [];
    $from = listing_from($filters, ['price'], $bind);
    $range = db_fetch('SELECT MIN(s.min_price) AS lo, MAX(s.min_price) AS hi' . $from, $bind);
    if ($range === null || $range['lo'] === null) {
        return [];
    }
    $lo = (int) floor(((float) $range['lo']) / 1000) * 1000;
    $hi = (int) ceil(((float) $range['hi']) / 1000) * 1000;
    if ($hi <= $lo) {
        $hi = $lo + 1000;
    }
    $step = max(1000, (int) ceil(($hi - $lo) / 3 / 1000) * 1000);
    $edges = [$lo + $step, $lo + 2 * $step, $lo + 3 * $step];
    $bands = [
        ['min' => null, 'max' => $edges[0], 'label' => 'Under ' . money((string) $edges[0])],
        ['min' => $edges[0], 'max' => $edges[1], 'label' => money((string) $edges[0]) . '–' . money((string) $edges[1])],
        ['min' => $edges[1], 'max' => $edges[2], 'label' => money((string) $edges[1]) . '–' . money((string) $edges[2])],
        ['min' => $edges[2], 'max' => null, 'label' => money((string) $edges[2]) . '+'],
    ];
    $bind = [];
    $from = listing_from($filters, ['price'], $bind);
    $cases = [];
    foreach ($bands as $i => $band) {
        $lower = $band['min'] === null ? '1=1' : 's.min_price >= ' . listing_bind($bind, (string) $band['min']);
        $upper = $band['max'] === null ? '1=1' : 's.min_price < ' . listing_bind($bind, (string) $band['max']);
        $cases[] = 'SUM(CASE WHEN ' . $lower . ' AND ' . $upper . ' THEN 1 ELSE 0 END) AS c' . $i;
    }
    $counts = db_fetch('SELECT ' . implode(', ', $cases) . $from, $bind) ?? [];
    foreach ($bands as $i => &$band) {
        $band['count'] = (int) ($counts['c' . $i] ?? 0);
        $band['value'] = listing_price_band_value($band);
    }
    unset($band);
    return $bands;
}

function listing_state_query(array $state): string
{
    $pairs = [];
    foreach (LISTING_PARAM_ORDER as $key) {
        $value = $state[$key] ?? null;
        if (is_array($value)) {
            if ($value === []) {
                continue;
            }
            sort($value);
            $pairs[$key] = implode(',', $value);
        } elseif (is_bool($value)) {
            if ($value) {
                $pairs[$key] = '1';
            }
        } elseif ($key === 'page') {
            if ((int) $value > 1) {
                $pairs[$key] = (string) (int) $value;
            }
        } elseif (is_string($value) && $value !== '') {
            $pairs[$key] = $value;
        }
    }
    return str_replace('%2C', ',', http_build_query($pairs, '', '&', PHP_QUERY_RFC3986));
}

function listing_url(string $basePath, array $state, array $changes = []): string
{
    $state = array_replace($state, $changes);
    if (!array_key_exists('page', $changes)) {
        $state['page'] = 1;
    }
    $query = listing_state_query($state);
    return url($basePath . ($query === '' ? '' : '?' . $query));
}

function listing_without_value(array $state, string $group, string $value): array
{
    $state[$group] = array_values(array_filter($state[$group], static fn (string $v): bool => $v !== $value));
    return $state;
}

function listing_shop_pretty_path(array $state, string $defaultSort): ?string
{
    $dimensions = [
        'collection' => count($state['collection']),
        'gender' => count($state['gender']),
        'family' => count($state['family']),
        'on_sale' => $state['on_sale'] ? 1 : 0,
    ];
    $active = array_filter($dimensions);
    $other = $state['price'] !== '' || $state['in_stock'] || $state['q'] !== '';
    if ($other || count($active) > 1 || array_sum($dimensions) > 1) {
        return null;
    }
    if ($active === []) {
        return match ($state['sort']) {
            'newest' => '/new-arrivals',
            'best' => '/best-sellers',
            default => null,
        };
    }
    if ($state['sort'] !== '' && $state['sort'] !== $defaultSort) {
        return null;
    }
    $dimension = array_key_first($active);
    return match ($dimension) {
        'collection' => '/collections/' . $state['collection'][0],
        'gender' => match ($state['gender'][0]) { 'him' => '/for-him', 'her' => '/for-her', default => '/unisex' },
        'family' => '/scent/' . $state['family'][0],
        default => '/sale',
    };
}

function listing_gender_heading(string $gender): string
{
    return match ($gender) {
        'him' => 'Perfumes For Him',
        'her' => 'Perfumes For Her',
        default => 'Unisex Fragrances',
    };
}

function listing_gender_intro(string $gender): string
{
    $defaults = [
        'him' => 'Grounded, warm and built to last through a Karachi evening — oud, leather, vetiver and spice.',
        'her' => 'Luminous florals, soft ambers and roses that stay close through the heat — made to be remembered.',
        'unisex' => 'Fragrance without a side of the aisle. Clean citrus, green woods and smoky ouds anyone can wear.',
    ];
    return (string) setting('gender_intro_' . $gender, $defaults[$gender] ?? '');
}

function listing_title_with_store(string $title, string $storeName): string
{
    return str_contains($title, $storeName) ? $title : $title . ' | ' . $storeName;
}

partial('product-card.php', []);
partial('collection-card.php', []);

$preset = listing_resolve_preset($route['preset'] ?? [], $params);
$landing = null;
$landingKind = 'shop';
if (isset($preset['collection'])) {
    $landingKind = 'collection';
    $landing = db_fetch('SELECT id, name, slug, tagline, description, mood, image, seo_title, seo_description FROM collections WHERE slug = :slug AND is_active = 1', ['slug' => strtolower((string) $preset['collection'])]);
    if ($landing === null) {
        listing_redirect_if_renamed('collection', strtolower((string) $preset['collection']), '/collections');
        abort(404);
    }
}
if (isset($preset['family'])) {
    $landingKind = 'family';
    $landing = db_fetch('SELECT id, name, slug, intro, seo_title, seo_description FROM scent_families WHERE slug = :slug AND is_active = 1', ['slug' => strtolower((string) $preset['family'])]);
    if ($landing === null) {
        listing_redirect_if_renamed('scent', strtolower((string) $preset['family']), '/scent');
        abort(404);
    }
}
if (isset($preset['gender'])) {
    $landingKind = 'gender';
}
if (isset($preset['on_sale']) || isset($preset['sort'])) {
    $landingKind = 'merch';
}
if ($route['name'] === 'search') {
    $landingKind = 'search';
}

$allCollections = db_fetch_all('SELECT id, name, slug FROM collections WHERE is_active = 1 ORDER BY sort_order ASC, name ASC');
$allFamilies = db_fetch_all('SELECT id, name, slug FROM scent_families WHERE is_active = 1 ORDER BY sort_order ASC, name ASC');
$collectionBySlug = array_column($allCollections, null, 'slug');
$familyBySlug = array_column($allFamilies, null, 'slug');

$searchQuery = isset($searchQuery) ? (string) $searchQuery : '';
$searchLanding = $route['name'] === 'search' && mb_strlen($searchQuery) < 2;
$defaultSort = (string) ($preset['sort'] ?? (isset($preset['on_sale']) ? 'price-asc' : 'featured'));

$state = [
    'collection' => isset($preset['collection']) ? [] : array_values(array_filter(listing_slug_list(request_query('collection')), static fn (string $s): bool => isset($collectionBySlug[$s]))),
    'gender' => isset($preset['gender']) ? [] : array_values(array_intersect(listing_slug_list(request_query('gender')), GENDERS)),
    'family' => isset($preset['family']) ? [] : array_values(array_filter(listing_slug_list(request_query('family')), static fn (string $s): bool => isset($familyBySlug[$s]))),
    'price' => listing_price_band_value(listing_price_band(request_query('price'))),
    'on_sale' => !isset($preset['on_sale']) && request_query('on_sale') === '1',
    'in_stock' => request_query('in_stock') === '1',
    'q' => $searchLanding ? '' : $searchQuery,
    'sort' => '',
    'page' => 1,
];
$requestedSort = request_query('sort');
if (is_string($requestedSort)) {
    $requestedSort = str_replace('_', '-', strtolower($requestedSort));
}
if (is_string($requestedSort) && isset(LISTING_SORTS[$requestedSort]) && $requestedSort !== $defaultSort) {
    $state['sort'] = $requestedSort;
}
$sort = $state['sort'] === '' ? $defaultSort : $state['sort'];
$requestedPage = request_query('page');
if ($requestedPage !== null && (!is_string($requestedPage) || !preg_match('/^[1-9][0-9]{0,4}$/', $requestedPage))) {
    abort(404);
}
$state['page'] = $requestedPage === null ? 1 : (int) $requestedPage;

$basePath = (string) $route['path'];
if ($landing !== null) {
    $basePath = str_replace('{slug}', (string) $landing['slug'], $basePath);
}
if ($route['name'] === 'shop') {
    $prettyPath = listing_shop_pretty_path($state, $defaultSort);
    if ($prettyPath !== null) {
        $prettyState = ['page' => $state['page'], 'in_stock' => false, 'price' => '', 'q' => '', 'sort' => '', 'collection' => [], 'gender' => [], 'family' => [], 'on_sale' => false];
        redirect(listing_url($prettyPath, $prettyState, ['page' => $state['page']]), 301);
    }
}
$canonicalQuery = listing_state_query($state);
if ((string) ($_SERVER['QUERY_STRING'] ?? '') !== $canonicalQuery) {
    redirect(url($basePath . ($canonicalQuery === '' ? '' : '?' . $canonicalQuery)), 301);
}

$filters = [
    'collection_ids' => isset($preset['collection']) ? [(int) $landing['id']] : array_map(static fn (string $s): int => (int) $collectionBySlug[$s]['id'], $state['collection']),
    'gender' => isset($preset['gender']) ? [(string) $preset['gender']] : $state['gender'],
    'family_names' => isset($preset['family']) ? [(string) $landing['name']] : array_map(static fn (string $s): string => (string) $familyBySlug[$s]['name'], $state['family']),
    'price' => listing_price_band($state['price']),
    'on_sale' => isset($preset['on_sale']) || $state['on_sale'],
    'in_stock' => $state['in_stock'],
    'terms' => $searchLanding ? [] : listing_terms($searchQuery),
    'q' => $searchLanding ? '' : $searchQuery,
];

$total = $searchLanding ? 0 : listing_count($filters);
$pages = max(1, (int) ceil($total / LISTING_PER_PAGE));
$page = $state['page'];
if ($page > $pages) {
    abort(404);
}
$products = $searchLanding ? [] : listing_rows($filters, $sort, LISTING_PER_PAGE, ($page - 1) * LISTING_PER_PAGE);
if ($route['name'] === 'search' && $total === 1 && count($products) === 1) {
    $skuHit = db_fetch_column(
        'SELECT 1 FROM product_sizes WHERE product_id = :product AND is_active = 1 AND sku = :sku LIMIT 1',
        ['product' => (int) $products[0]['id'], 'sku' => strtoupper($searchQuery)]
    );
    if ($skuHit !== null) {
        redirect('/product/' . $products[0]['slug'], 302);
    }
}

$facetGroups = [];
if (!isset($preset['collection'])) {
    $counts = $searchLanding ? [] : listing_facet_counts($filters, 'collection', 'p.collection_id');
    $options = [];
    foreach ($allCollections as $collection) {
        $options[] = ['value' => $collection['slug'], 'label' => $collection['name'], 'count' => (int) ($counts[$collection['id']] ?? 0), 'active' => in_array($collection['slug'], $state['collection'], true)];
    }
    $facetGroups[] = ['key' => 'collection', 'title' => 'Collection', 'options' => $options];
}
if (!isset($preset['gender'])) {
    $counts = $searchLanding ? [] : listing_facet_counts($filters, 'gender', 'p.gender');
    $options = [];
    foreach (['him' => 'For Him', 'her' => 'For Her', 'unisex' => 'Unisex'] as $value => $label) {
        $options[] = ['value' => $value, 'label' => $label, 'count' => (int) ($counts[$value] ?? 0), 'active' => in_array($value, $state['gender'], true)];
    }
    $facetGroups[] = ['key' => 'gender', 'title' => 'Gender', 'options' => $options];
}
if (!isset($preset['family'])) {
    $counts = $searchLanding ? [] : listing_facet_counts($filters, 'family', 'p.scent_family');
    $options = [];
    foreach ($allFamilies as $family) {
        $options[] = ['value' => $family['slug'], 'label' => $family['name'], 'count' => (int) ($counts[$family['name']] ?? 0), 'active' => in_array($family['slug'], $state['family'], true)];
    }
    $facetGroups[] = ['key' => 'family', 'title' => 'Scent family', 'options' => $options];
}
$priceBands = $searchLanding ? [] : listing_price_bands($filters);

$chips = [];
foreach ($facetGroups as $group) {
    foreach ($group['options'] as $option) {
        if ($option['active']) {
            $chips[] = ['label' => $option['label'], 'url' => listing_url($basePath, listing_without_value($state, $group['key'], $option['value']))];
        }
    }
}
if ($state['price'] !== '') {
    $activeBand = null;
    foreach ($priceBands as $band) {
        if ($band['value'] === $state['price']) {
            $activeBand = $band['label'];
        }
    }
    if ($activeBand === null) {
        $activeBand = 'Price ' . str_replace('-', ' – ', $state['price']);
    }
    $chips[] = ['label' => $activeBand, 'url' => listing_url($basePath, $state, ['price' => ''])];
}
if ($state['on_sale']) {
    $chips[] = ['label' => 'On Sale Only', 'url' => listing_url($basePath, $state, ['on_sale' => false])];
}
if ($state['in_stock']) {
    $chips[] = ['label' => 'In Stock Only', 'url' => listing_url($basePath, $state, ['in_stock' => false])];
}
$clearUrl = listing_url($basePath, $state, ['collection' => [], 'gender' => [], 'family' => [], 'price' => '', 'on_sale' => false, 'in_stock' => false, 'sort' => '']);
$activeCount = count($chips);

$emptyHint = '';
if ($total === 0 && !$searchLanding) {
    $labels = ['collection' => 'collection', 'gender' => 'gender', 'family' => 'scent family', 'price' => 'price', 'on_sale' => 'sale', 'in_stock' => 'in stock'];
    $bestGain = -1;
    foreach ($labels as $key => $label) {
        $isActive = match ($key) {
            'collection' => $state['collection'] !== [],
            'gender' => $state['gender'] !== [],
            'family' => $state['family'] !== [],
            'price' => $state['price'] !== '',
            'on_sale' => $state['on_sale'],
            default => $state['in_stock'],
        };
        if (!$isActive) {
            continue;
        }
        $gain = listing_count($filters, [$key]);
        if ($gain > $bestGain) {
            $bestGain = $gain;
            $emptyHint = 'Try removing the ' . $label . ' filter';
        }
    }
}
$bestSellers = ($total === 0 || $searchLanding) ? listing_best_sellers(8) : [];

$activeGroups = count(array_filter([$state['collection'], $state['gender'], $state['family']], static fn (array $g): bool => $g !== [])) + ($state['price'] !== '' ? 1 : 0) + ($state['on_sale'] ? 1 : 0) + ($state['in_stock'] ? 1 : 0);
$multiValue = max(count($state['collection']), count($state['gender']), count($state['family'])) > 1;
$noindex = $route['name'] === 'search' || $state['price'] !== '' || $state['sort'] !== '' || $activeGroups > 1 || $multiValue;
$canonicalState = $state;
if ($activeGroups > 1 || $multiValue) {
    $canonicalState = array_replace($canonicalState, ['collection' => [], 'gender' => [], 'family' => [], 'price' => '', 'on_sale' => false, 'in_stock' => false]);
}
$canonicalState['sort'] = '';
$canonicalState['price'] = '';
$canonicalQueryString = listing_state_query($canonicalState);
$canonicalPath = $route['name'] === 'search' ? canonical('/search') : canonical($basePath . ($canonicalQueryString === '' ? '' : '?' . $canonicalQueryString));

$prevUrl = $page > 1 ? listing_url($basePath, $state, ['page' => $page - 1]) : '';
$nextUrl = $page < $pages ? listing_url($basePath, $state, ['page' => $page + 1]) : '';
$pageLinks = [];
if ($pages > 1) {
    $window = array_unique(array_filter(array_merge([1, $pages], range(max(1, $page - 2), min($pages, $page + 2))), static fn (int $n): bool => $n >= 1 && $n <= $pages));
    sort($window);
    $previous = 0;
    foreach ($window as $n) {
        if ($n - $previous > 1) {
            $pageLinks[] = ['gap' => true];
        }
        $pageLinks[] = ['gap' => false, 'number' => $n, 'url' => listing_url($basePath, $state, ['page' => $n]), 'current' => $n === $page];
        $previous = $n;
    }
}

$storeName = (string) setting('store_name', 'Sky Fragrances');
$heading = 'Shop All Fragrances';
$intro = '';
$mood = '';
$heroImage = null;
$title = 'Shop All Perfumes | ' . $storeName;
$metaDescription = '';
$breadcrumbs = [['Home', url('/')], ['Shop', url('/shop')]];
switch ($landingKind) {
    case 'collection':
        $heading = (string) $landing['name'];
        $intro = (string) ($landing['description'] ?? '');
        $mood = (string) ($landing['mood'] ?? '');
        $heroImage = collection_card_image_sources($landing['image'] ?? null, 'zoom');
        $title = listing_title_with_store((string) ($landing['seo_title'] ?: $landing['name'] . ' Collection — Perfumes'), $storeName);
        $metaDescription = (string) ($landing['seo_description'] ?: ($landing['tagline'] ?: $intro));
        $breadcrumbs = [['Home', url('/')], ['Collections', url('/collections')], [$heading, url($basePath)]];
        break;
    case 'family':
        $heading = $landing['name'] . ' Perfumes';
        $intro = (string) ($landing['intro'] ?? '');
        $title = listing_title_with_store((string) ($landing['seo_title'] ?: $landing['name'] . ' Perfumes in Pakistan'), $storeName);
        $metaDescription = (string) ($landing['seo_description'] ?: $intro);
        $breadcrumbs[] = [$heading, url($basePath)];
        break;
    case 'gender':
        $heading = listing_gender_heading((string) $preset['gender']);
        $intro = listing_gender_intro((string) $preset['gender']);
        $genderKey = match ((string) $preset['gender']) { 'him' => 'men', 'her' => 'women', default => 'unisex' };
        $heroFile = (string) setting('gender_hero_' . $preset['gender'], (string) setting('gender_tile_' . $genderKey . '_image', ''));
        $heroImage = $heroFile !== '' ? collection_card_image_sources($heroFile, 'zoom') : null;
        $title = match ((string) $preset['gender']) { 'him' => 'Perfumes For Him', 'her' => 'Perfumes For Her', default => 'Unisex Perfumes' } . ' | ' . $storeName;
        $metaDescription = $intro;
        $breadcrumbs[] = [$heading, url($basePath)];
        break;
    case 'merch':
        $heading = match ($route['name']) { 'new' => 'New Arrivals', 'best' => 'Best Sellers', default => 'Sale' };
        $intro = match ($route['name']) {
            'new' => 'The latest additions to the house — fresh from the atelier.',
            'best' => 'The fragrances Pakistan keeps coming back for.',
            default => 'Signature scents at a softer price, while stock lasts.',
        };
        $title = match ($route['name']) { 'new' => 'New Arrivals', 'best' => 'Best Selling Perfumes', default => 'Sale — Perfume Offers' } . ' | ' . $storeName;
        $metaDescription = $intro . ' Cash on delivery across Pakistan.';
        $breadcrumbs[] = [$heading, url($basePath)];
        break;
    case 'search':
        $heading = $searchLanding ? 'Search' : 'Search results for "' . $searchQuery . '"';
        $title = ($searchLanding ? 'Search' : 'Search: "' . $searchQuery . '"') . ' | ' . $storeName;
        $metaDescription = 'Search the ' . $storeName . ' catalogue.';
        $breadcrumbs = [['Home', url('/')], ['Search', url('/search')]];
        break;
    default:
        $intro = (string) setting('shop_intro', '');
        $metaDescription = 'Every ' . $storeName . ' eau de parfum in one place — for him, for her and unisex. Cash on delivery nationwide.';
}
if ($chips !== [] && !$noindex) {
    $filterLabel = implode(', ', array_column($chips, 'label'));
    $title = str_replace(' | ' . $storeName, ' — ' . $filterLabel . ' | ' . $storeName, $title);
    $metaDescription = $total . ($total === 1 ? ' perfume' : ' perfumes') . ' in ' . $heading . ' — ' . $filterLabel . '. ' . $metaDescription;
}
if ($page > 1) {
    $title = str_replace(' | ' . $storeName, ' — Page ' . $page . ' | ' . $storeName, $title);
    $metaDescription = rtrim($metaDescription, '. ') . ' — Page ' . $page;
}

$breadcrumbList = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => []];
foreach ($breadcrumbs as $i => [$crumbName, $crumbUrl]) {
    $breadcrumbList['itemListElement'][] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumbName, 'item' => canonical(substr($crumbUrl, strlen(BASE_PATH)))];
}
$jsonld = [$breadcrumbList];
if ($products !== [] && !$noindex) {
    $itemList = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => $heading, 'numberOfItems' => $total, 'itemListElement' => []];
    foreach ($products as $i => $product) {
        $itemList['itemListElement'][] = ['@type' => 'ListItem', 'position' => ($page - 1) * LISTING_PER_PAGE + $i + 1, 'url' => canonical('/product/' . $product['slug']), 'name' => $product['name']];
    }
    $jsonld[] = $itemList;
}

$head['title'] = $title;
$head['meta_description'] = $metaDescription;
$head['robots'] = $noindex ? 'noindex,follow' : 'index,follow';
$head['canonical'] = $canonicalPath;
$head['jsonld'] = $jsonld;

render($route['view'], [
    'heading' => $heading,
    'landingKind' => $landingKind,
    'landing' => $landing,
    'intro' => $intro,
    'mood' => $mood,
    'heroImage' => $heroImage,
    'preset' => $preset,
    'state' => $state,
    'basePath' => $basePath,
    'sort' => $sort,
    'defaultSort' => $defaultSort,
    'sorts' => LISTING_SORTS,
    'products' => $products,
    'total' => $total,
    'page' => $page,
    'pages' => $pages,
    'perPage' => LISTING_PER_PAGE,
    'facetGroups' => $facetGroups,
    'priceBands' => $priceBands,
    'chips' => $chips,
    'clearUrl' => $clearUrl,
    'activeCount' => $activeCount,
    'prevUrl' => $prevUrl,
    'nextUrl' => $nextUrl,
    'pageLinks' => $pageLinks,
    'emptyHint' => $emptyHint,
    'bestSellers' => $bestSellers,
    'searchQuery' => $searchQuery,
    'searchLanding' => $searchLanding,
    'breadcrumbs' => $breadcrumbs,
    'whatsapp' => (string) setting('whatsapp', ''),
], $head);
