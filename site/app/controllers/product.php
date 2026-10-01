<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/cart.php';
require_once APP_ROOT . '/app/lib/forms.php';

const PRODUCT_LONGEVITY_WORDS = [1 => 'Up to 2 hours', 2 => '3–4 hours', 3 => '5–6 hours', 4 => '7–9 hours', 5 => '10+ hours'];
const PRODUCT_SILLAGE_WORDS = [1 => 'Intimate', 2 => 'Soft', 3 => 'Moderate', 4 => 'Strong', 5 => 'Very strong'];
const PRODUCT_SEASON_ICONS = ['summer', 'winter', 'autumn', 'spring', 'monsoon'];
const PRODUCT_IMAGE_WIDTHS = ['thumb' => 200, 'card' => 600, 'zoom' => 1400];
const PRODUCT_REVIEWS_VISIBLE = 8;
const PRODUCT_REVIEWS_MAX = 200;
const PRODUCT_RELATED_COUNT = 4;
const PRODUCT_NEW_DAYS = 30;
const PRODUCT_JSONLD_REVIEWS = 5;
const PRODUCT_NOTE_FRAGMENTS_MAX = 6;

function product_absolute_url(string $path): string
{
    if (BASE_PATH !== '' && str_starts_with($path, BASE_PATH)) {
        $path = substr($path, strlen(BASE_PATH));
    }
    return SITE_URL . $path;
}

function product_image_variant(int $productId, string $filename, string $size, string $ext): string
{
    if (preg_match('/^product-\d+-[a-f0-9]{10}$/', $filename)) {
        return image_url($filename, $size, $ext);
    }
    $folder = str_contains($filename, '/') ? dirname($filename) : (string) $productId;
    $stem = pathinfo(basename($filename), PATHINFO_FILENAME);
    $derived = '/uploads/products/' . $folder . '/' . $stem . '-' . $size . '.' . $ext;
    if (is_file(APP_ROOT . $derived)) {
        return url($derived);
    }
    $original = '/uploads/products/' . $folder . '/' . basename($filename);
    if (is_file(APP_ROOT . $original)) {
        return url($original);
    }
    return asset(brand_asset('img/placeholder-4x5.svg'));
}

function product_image_srcset(int $productId, string $filename, string $ext): string
{
    $parts = [];
    foreach (PRODUCT_IMAGE_WIDTHS as $size => $width) {
        $parts[] = product_image_variant($productId, $filename, $size, $ext) . ' ' . $width . 'w';
    }
    return implode(', ', $parts);
}

function product_present_image(int $productId, string $productName, array $row, int $index): array
{
    $filename = (string) $row['filename'];
    $alt = trim((string) ($row['alt_text'] ?? ''));
    if ($alt === '') {
        $alt = $productName . ' eau de parfum by Sky Fragrances — image ' . ($index + 1);
    }
    return [
        'index' => $index,
        'alt' => $alt,
        'src' => product_image_variant($productId, $filename, 'card', 'jpg'),
        'webp' => product_image_variant($productId, $filename, 'card', 'webp'),
        'srcset' => product_image_srcset($productId, $filename, 'jpg'),
        'srcset_webp' => product_image_srcset($productId, $filename, 'webp'),
        'zoom' => product_image_variant($productId, $filename, 'zoom', 'jpg'),
        'zoom_webp' => product_image_variant($productId, $filename, 'zoom', 'webp'),
        'thumb' => product_image_variant($productId, $filename, 'thumb', 'jpg'),
        'thumb_webp' => product_image_variant($productId, $filename, 'thumb', 'webp'),
        'og' => product_image_og($filename),
    ];
}

function product_image_og(string $filename): string
{
    $folder = str_contains($filename, '/') ? dirname($filename) . '/' : '';
    $stem = pathinfo(basename($filename), PATHINFO_FILENAME);
    $derived = '/uploads/og/' . $folder . $stem . '-og.jpg';
    return is_file(APP_ROOT . $derived) ? url($derived) : '';
}

function product_meta_description(array $product, array $sizes): string
{
    $own = trim((string) ($product['seo_description'] ?? ''));
    if ($own !== '') {
        return $own;
    }
    $short = trim(html_entity_decode(strip_tags((string) ($product['short_description'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($short === '') {
        return '';
    }
    $labels = array_values(array_filter(array_map(static fn (array $size): string => trim((string) $size['label']), $sizes)));
    $tail = ($labels !== [] ? implode(' and ', $labels) . '. ' : '') . 'Cash on delivery across Pakistan.';
    if (mb_strlen($short . ' ' . $tail, 'UTF-8') <= 155) {
        return $short . ' ' . $tail;
    }
    return mb_strlen($short, 'UTF-8') <= 155 ? $short : '';
}

function product_present_size(array $row): array
{
    $unit = pricing_unit((string) $row['price'], $row['sale_price']);
    $unitPaisa = $unit['unit_price_paisa'];
    $comparePaisa = $unit['compare_at_paisa'];
    $savePercent = $comparePaisa !== null && $comparePaisa > 0 ? intdiv(($comparePaisa - $unitPaisa) * 100, $comparePaisa) : 0;
    $stock = (int) $row['stock'];
    return [
        'id' => (int) $row['id'],
        'label' => (string) $row['size_label'],
        'size_ml' => $row['size_ml'] === null ? null : (int) $row['size_ml'],
        'sku' => (string) $row['sku'],
        'stock' => $stock,
        'in_stock' => $stock > 0,
        'is_default' => (int) $row['is_default'] === 1,
        'on_sale' => $comparePaisa !== null,
        'price' => money_from_paisa($comparePaisa ?? $unitPaisa),
        'price_display' => money(money_from_paisa($comparePaisa ?? $unitPaisa)),
        'effective_price' => money_from_paisa($unitPaisa),
        'effective_display' => money(money_from_paisa($unitPaisa)),
        'sale_price_display' => $comparePaisa !== null ? money(money_from_paisa($unitPaisa)) : null,
        'save_percent' => $savePercent,
        'max_qty' => max(1, min(CART_MAX_QTY_PER_LINE, $stock)),
    ];
}

function product_pick_default_size(array $sizes, int $requestedId): ?array
{
    if ($sizes === []) {
        return null;
    }
    foreach ($sizes as $size) {
        if ($requestedId > 0 && $size['id'] === $requestedId) {
            return $size;
        }
    }
    $firstInStock = null;
    foreach ($sizes as $size) {
        if ($size['in_stock']) {
            $firstInStock = $size;
            break;
        }
    }
    foreach ($sizes as $size) {
        if ($size['is_default']) {
            return $size['in_stock'] || $firstInStock === null ? $size : $firstInStock;
        }
    }
    return $firstInStock ?? $sizes[0];
}

function product_split_list(?string $raw): array
{
    if ($raw === null || trim($raw) === '') {
        return [];
    }
    return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn (string $v): bool => $v !== ''));
}

function product_note_fragments(array $notes): array
{
    $words = [];
    foreach ($notes as $note) {
        foreach (preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($note, 'UTF-8')) ?: [] as $word) {
            if (mb_strlen($word, 'UTF-8') >= 4 && !in_array($word, $words, true)) {
                $words[] = $word;
            }
        }
    }
    return array_slice($words, 0, PRODUCT_NOTE_FRAGMENTS_MAX);
}

function product_related_ids(array $product, array $sizes): array
{
    $minPaisa = null;
    foreach ($sizes as $size) {
        $paisa = money_paisa($size['effective_price']);
        $minPaisa = $minPaisa === null ? $paisa : min($minPaisa, $paisa);
    }
    $minPaisa ??= 0;
    $bind = [
        'current_id' => (int) $product['id'],
        'collection_id' => $product['collection_id'] === null ? null : (int) $product['collection_id'],
        'family' => (string) ($product['scent_family'] ?? ''),
        'gender' => (string) $product['gender'],
        'gender_unisex' => (string) $product['gender'],
        'price_low' => money_from_paisa(intdiv($minPaisa * 75, 100)),
        'price_high' => money_from_paisa(intdiv($minPaisa * 125, 100)),
    ];
    $noteTerms = [];
    foreach (product_note_fragments(array_merge(product_split_list($product['notes_heart']), product_split_list($product['notes_base']))) as $i => $word) {
        $noteTerms[] = 'p.notes_heart LIKE :heart_' . $i . ' OR p.notes_base LIKE :base_' . $i;
        $bind['heart_' . $i] = '%' . $word . '%';
        $bind['base_' . $i] = '%' . $word . '%';
    }
    $noteScore = $noteTerms === [] ? '0' : 'CASE WHEN (' . implode(' OR ', $noteTerms) . ') THEN 3 ELSE 0 END';
    $sql = 'SELECT scored.id FROM (
        SELECT p.id, p.sales_count, p.rating_avg,
            (CASE WHEN p.collection_id = :collection_id THEN 5 ELSE 0 END
             + CASE WHEN p.scent_family = :family THEN 4 ELSE 0 END
             + ' . $noteScore . '
             + CASE WHEN p.gender = :gender OR p.gender = \'unisex\' OR :gender_unisex = \'unisex\' THEN 2 ELSE 0 END
             + CASE WHEN x.min_price BETWEEN :price_low AND :price_high THEN 2 ELSE 0 END
             + CASE WHEN x.on_sale = 1 THEN 1 ELSE 0 END
             - CASE WHEN x.total_stock = 0 THEN 6 ELSE 0 END) AS score
        FROM products p
        JOIN (SELECT product_id, MIN(COALESCE(sale_price, price)) AS min_price, SUM(stock) AS total_stock,
                     MAX(CASE WHEN sale_price IS NOT NULL AND sale_price < price THEN 1 ELSE 0 END) AS on_sale
              FROM product_sizes WHERE is_active = 1 GROUP BY product_id) x ON x.product_id = p.id
        WHERE p.is_active = 1 AND p.deleted_at IS NULL AND p.id <> :current_id
    ) scored
    WHERE scored.score > 0
    ORDER BY scored.score DESC, scored.sales_count DESC, scored.rating_avg DESC, scored.id DESC
    LIMIT ' . PRODUCT_RELATED_COUNT;
    $ids = array_map('intval', array_column(db_fetch_all($sql, $bind), 'id'));
    if (count($ids) < PRODUCT_RELATED_COUNT) {
        $exclude = array_merge([(int) $product['id']], $ids);
        $fillBind = [];
        $placeholders = [];
        foreach ($exclude as $i => $id) {
            $placeholders[] = ':ex_' . $i;
            $fillBind['ex_' . $i] = $id;
        }
        $fill = db_fetch_all(
            'SELECT p.id FROM products p
             JOIN product_sizes s ON s.product_id = p.id AND s.is_active = 1
             WHERE p.is_active = 1 AND p.deleted_at IS NULL AND p.id NOT IN (' . implode(', ', $placeholders) . ')
             GROUP BY p.id, p.sales_count, p.rating_avg
             ORDER BY p.sales_count DESC, p.rating_avg DESC, p.id DESC
             LIMIT ' . (PRODUCT_RELATED_COUNT - count($ids)),
            $fillBind
        );
        $ids = array_merge($ids, array_map('intval', array_column($fill, 'id')));
    }
    return count($ids) === PRODUCT_RELATED_COUNT ? $ids : [];
}

function product_is_new(array $row): bool
{
    if ((int) ($row['is_new'] ?? 0) === 1) {
        return true;
    }
    $since = $row['published_at'] ?? $row['created_at'] ?? null;
    return $since !== null && strtotime((string) $since) >= time() - PRODUCT_NEW_DAYS * 86400;
}

function product_badges(bool $soldOut, int $savePercent, bool $isNew): array
{
    $badges = [];
    if ($soldOut) {
        $badges[] = ['class' => 'badge--sold-out', 'text' => 'Sold out'];
    }
    if ($savePercent > 0) {
        $badges[] = ['class' => 'badge--sale', 'text' => 'Sale −' . $savePercent . '%'];
    }
    if ($isNew) {
        $badges[] = ['class' => 'badge--new', 'text' => 'New'];
    }
    return array_slice($badges, 0, 2);
}

function product_related_cards(array $ids): array
{
    if ($ids === []) {
        return [];
    }
    $bind = [];
    $placeholders = [];
    foreach ($ids as $i => $id) {
        $placeholders[] = ':id_' . $i;
        $bind['id_' . $i] = $id;
    }
    $rows = db_fetch_all(
        'SELECT p.id, p.name, p.slug, p.scent_family, p.is_new, p.rating_avg, p.rating_count, p.published_at, p.created_at,
            x.price_from, x.total_stock, x.max_save, x.size_count, x.size_labels,
            (SELECT i.filename FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.sort_order ASC LIMIT 1) AS image,
            (SELECT i.alt_text FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.sort_order ASC LIMIT 1) AS image_alt,
            (SELECT i.filename FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.sort_order ASC LIMIT 1 OFFSET 1) AS image_hover
         FROM products p
         JOIN (SELECT product_id, COUNT(*) AS size_count, MIN(COALESCE(sale_price, price)) AS price_from, SUM(stock) AS total_stock,
                      MAX(CASE WHEN sale_price IS NOT NULL AND sale_price < price THEN FLOOR((price - sale_price) * 100 / price) ELSE 0 END) AS max_save,
                      GROUP_CONCAT(size_label ORDER BY sort_order ASC SEPARATOR \' · \') AS size_labels
               FROM product_sizes WHERE is_active = 1 GROUP BY product_id) x ON x.product_id = p.id
         WHERE p.id IN (' . implode(', ', $placeholders) . ')',
        $bind
    );
    $byId = [];
    foreach ($rows as $row) {
        $byId[(int) $row['id']] = $row;
    }
    $cards = [];
    foreach ($ids as $id) {
        if (!isset($byId[$id])) {
            continue;
        }
        $row = $byId[$id];
        $productId = (int) $row['id'];
        $soldOut = (int) $row['total_stock'] === 0;
        $image = $row['image'] !== null ? product_present_image($productId, (string) $row['name'], ['filename' => $row['image'], 'alt_text' => $row['image_alt']], 0) : null;
        $hover = $row['image_hover'] !== null ? product_present_image($productId, (string) $row['name'], ['filename' => $row['image_hover'], 'alt_text' => ''], 1) : null;
        $cards[] = [
            'id' => $productId,
            'name' => (string) $row['name'],
            'url' => url('/product/' . $row['slug']),
            'family' => (string) ($row['scent_family'] ?? ''),
            'size_labels' => (string) ($row['size_labels'] ?? ''),
            'price_from' => money((string) $row['price_from']),
            'has_from' => (int) $row['size_count'] > 1,
            'on_sale' => (int) $row['max_save'] > 0,
            'sold_out' => $soldOut,
            'rating_avg' => (float) $row['rating_avg'],
            'rating_count' => (int) $row['rating_count'],
            'badges' => product_badges($soldOut, (int) $row['max_save'], product_is_new($row)),
            'image' => $image,
            'hover' => $hover,
        ];
    }
    return $cards;
}

function product_rating_summary(int $productId): array
{
    $row = db_fetch(
        'SELECT COUNT(*) AS total, AVG(rating) AS average,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS star5,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS star4,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS star3,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) AS star2,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) AS star1
         FROM reviews WHERE product_id = :id AND status = :status',
        ['id' => $productId, 'status' => 'approved']
    );
    $total = (int) ($row['total'] ?? 0);
    $histogram = [];
    foreach ([5, 4, 3, 2, 1] as $star) {
        $count = (int) ($row['star' . $star] ?? 0);
        $histogram[] = ['star' => $star, 'count' => $count, 'percent' => $total > 0 ? (int) round($count * 100 / $total) : 0];
    }
    $average = $total > 0 ? round((float) $row['average'], 1) : 0.0;
    return [
        'count' => $total,
        'average' => $average,
        'average_display' => $total > 0 ? number_format($average, 1) : '',
        'histogram' => $histogram,
        'aria' => 'Rated ' . number_format($average, 1) . ' out of 5 from ' . $total . ' ' . ($total === 1 ? 'review' : 'reviews'),
    ];
}

function product_present_review(array $row): array
{
    $body = trim((string) ($row['body'] ?? ''));
    return [
        'id' => (int) $row['id'],
        'rating' => (int) $row['rating'],
        'title' => trim((string) ($row['title'] ?? '')),
        'body' => $body,
        'is_long' => mb_strlen($body, 'UTF-8') > 320,
        'author' => (string) $row['customer_name'],
        'city' => trim((string) ($row['customer_city'] ?? '')),
        'date' => date_short($row['created_at']),
        'date_iso' => date('Y-m-d', strtotime((string) $row['created_at'])),
    ];
}

function product_meter(string $label, ?int $value, array $words): ?array
{
    $value = (int) $value;
    if ($value < 1 || $value > 5) {
        return null;
    }
    return [
        'label' => $label,
        'value' => $value,
        'word' => $words[$value],
        'aria' => $label . ': ' . $value . ' out of 5, ' . $words[$value],
    ];
}

function product_season_chips(?string $raw): array
{
    $chips = [];
    foreach (preg_split('/\s*(?:,|&|\band\b|\/)\s*/i', (string) $raw) ?: [] as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $icon = '';
        foreach (PRODUCT_SEASON_ICONS as $season) {
            if (stripos($part, $season) !== false) {
                $icon = $season;
                break;
            }
        }
        $chips[] = ['text' => $part, 'icon' => $icon];
    }
    return $chips;
}

function product_whatsapp(array $product, ?array $defaultSize): array
{
    $digits = preg_replace('/\D+/', '', (string) setting('whatsapp', '')) ?? '';
    if ($digits === '') {
        return ['digits' => '', 'base' => '', 'href' => '', 'in' => '', 'out' => ''];
    }
    $link = canonical('/product/' . $product['slug']);
    $intro = "Assalam-o-Alaikum. I'd like to order from Sky Fragrances.\n\nProduct: " . $product['name'] . "\n";
    $in = $intro . "Size: {size}\nPrice: {price}\nLink: " . $link . "\n\nPlease confirm availability and delivery.";
    $out = $intro . "Size: {size} (shown as out of stock)\nLink: " . $link . "\n\nPlease let me know when this size is back in stock.";
    $base = 'https://wa.me/' . $digits;
    $message = str_replace("Size: {size}\nPrice: {price}\n", '', $in);
    if ($defaultSize !== null) {
        $template = $defaultSize['in_stock'] ? $in : $out;
        $message = str_replace(['{size}', '{price}'], [$defaultSize['label'], $defaultSize['effective_display']], $template);
    }
    return ['digits' => $digits, 'base' => $base, 'href' => $base . '?text=' . rawurlencode($message), 'in' => $in, 'out' => $out];
}

function product_jsonld(array $product, array $sizes, ?array $defaultSize, array $images, array $rating, array $reviews, array $collection): array
{
    $productUrl = canonical('/product/' . $product['slug']);
    $inStock = false;
    $low = null;
    $high = null;
    foreach ($sizes as $size) {
        $inStock = $inStock || $size['in_stock'];
        $paisa = money_paisa($size['effective_price']);
        $low = $low === null ? $paisa : min($low, $paisa);
        $high = $high === null ? $paisa : max($high, $paisa);
    }
    $availability = 'https://schema.org/' . ($inStock ? 'InStock' : 'OutOfStock');
    $validUntil = date('Y-m-d', strtotime('+12 months'));
    $offer = [
        '@type' => count($sizes) > 1 ? 'AggregateOffer' : 'Offer',
        'priceCurrency' => 'PKR',
        'availability' => $availability,
        'itemCondition' => 'https://schema.org/NewCondition',
        'priceValidUntil' => $validUntil,
        'url' => $productUrl,
    ];
    if (count($sizes) > 1) {
        $offer['lowPrice'] = money_attr(money_from_paisa((int) $low));
        $offer['highPrice'] = money_attr(money_from_paisa((int) $high));
        $offer['offerCount'] = count($sizes);
    } else {
        $offer['price'] = money_attr(money_from_paisa((int) ($low ?? 0)));
    }
    $block = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => (string) $product['name'],
        'description' => excerpt((string) ($product['short_description'] ?? $product['description'] ?? ''), 300),
        'sku' => $defaultSize['sku'] ?? '',
        'brand' => ['@type' => 'Brand', 'name' => 'Sky Fragrances'],
        'url' => $productUrl,
        'image' => array_map(static fn (array $image): string => product_absolute_url($image['zoom']), $images),
        'offers' => $offer,
    ];
    if ($block['image'] === []) {
        unset($block['image']);
    }
    if ($rating['count'] >= 1) {
        $block['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format($rating['average'], 1),
            'reviewCount' => $rating['count'],
            'bestRating' => 5,
            'worstRating' => 1,
        ];
        $block['review'] = [];
        foreach (array_slice($reviews, 0, PRODUCT_JSONLD_REVIEWS) as $review) {
            $entry = [
                '@type' => 'Review',
                'author' => ['@type' => 'Person', 'name' => $review['author']],
                'datePublished' => $review['date_iso'],
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $review['rating'], 'bestRating' => 5, 'worstRating' => 1],
                'reviewBody' => $review['body'],
            ];
            if ($review['title'] !== '') {
                $entry['name'] = $review['title'];
            }
            $block['review'][] = $entry;
        }
    }
    $crumbs = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Shop', 'item' => canonical('/shop')],
    ];
    if ($collection !== []) {
        $crumbs[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $collection['name'], 'item' => canonical('/collections/' . $collection['slug'])];
    }
    $crumbs[] = ['@type' => 'ListItem', 'position' => count($crumbs) + 1, 'name' => (string) $product['name'], 'item' => $productUrl];
    return [$block, ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $crumbs]];
}

$slug = $params['slug'];
$product = db_fetch(
    'SELECT p.*, c.name AS collection_name, c.slug AS collection_slug, sf.slug AS scent_family_slug
     FROM products p
     LEFT JOIN collections c ON c.id = p.collection_id AND c.is_active = 1
     LEFT JOIN scent_families sf ON sf.name = p.scent_family AND sf.is_active = 1
     WHERE p.slug = :slug AND p.is_active = 1 AND p.deleted_at IS NULL',
    ['slug' => $slug]
);

if ($product === null) {
    $renamed = db_fetch('SELECT id, new_slug FROM slug_redirects WHERE entity_type = :type AND old_slug = :slug', ['type' => 'product', 'slug' => $slug]);
    if ($renamed !== null) {
        db_query('UPDATE slug_redirects SET hit_count = hit_count + 1 WHERE id = :id', ['id' => $renamed['id']]);
        redirect('/product/' . $renamed['new_slug'], 301);
    }
    abort(404);
}

$productId = (int) $product['id'];
$productName = (string) $product['name'];

$sizes = array_map('product_present_size', db_fetch_all(
    'SELECT id, size_label, size_ml, sku, price, sale_price, stock, is_default
     FROM product_sizes WHERE product_id = :id AND is_active = 1 ORDER BY sort_order ASC, size_ml ASC, id ASC',
    ['id' => $productId]
));
$requestedSize = filter_var(request_query('size', 0), FILTER_VALIDATE_INT);
$defaultSize = product_pick_default_size($sizes, $requestedSize === false ? 0 : (int) $requestedSize);

$allSoldOut = $sizes !== [] && !array_filter($sizes, static fn (array $size): bool => $size['in_stock']);
$deepestSave = $sizes === [] ? 0 : max(array_column($sizes, 'save_percent'));
$badges = product_badges($allSoldOut, $deepestSave, product_is_new($product));

$images = [];
foreach (db_fetch_all('SELECT filename, alt_text FROM product_images WHERE product_id = :id ORDER BY is_primary DESC, sort_order ASC, id ASC', ['id' => $productId]) as $index => $row) {
    $images[] = product_present_image($productId, $productName, $row, $index);
}

$rating = product_rating_summary($productId);
$reviews = array_map('product_present_review', db_fetch_all(
    'SELECT id, customer_name, customer_city, rating, title, body, created_at
     FROM reviews WHERE product_id = :id AND status = :status ORDER BY created_at DESC, id DESC LIMIT ' . PRODUCT_REVIEWS_MAX,
    ['id' => $productId, 'status' => 'approved']
));

$related = product_related_cards(product_related_ids($product, $sizes));

$notes = [
    'top' => product_split_list($product['notes_top']),
    'heart' => product_split_list($product['notes_heart']),
    'base' => product_split_list($product['notes_base']),
];
$meters = array_values(array_filter([
    product_meter('Longevity', $product['longevity'] === null ? null : (int) $product['longevity'], PRODUCT_LONGEVITY_WORDS),
    product_meter('Sillage', $product['sillage'] === null ? null : (int) $product['sillage'], PRODUCT_SILLAGE_WORDS),
]));
$seasons = product_season_chips($product['best_season']);
$occasions = product_split_list($product['occasion']);

$collection = $product['collection_slug'] !== null
    ? ['name' => (string) $product['collection_name'], 'slug' => (string) $product['collection_slug'], 'url' => url('/collections/' . $product['collection_slug'])]
    : [];
$familyUrl = $product['scent_family_slug'] !== null ? url('/scent/' . $product['scent_family_slug']) : '';
$genderLabel = match ((string) $product['gender']) { 'him' => 'For Him', 'her' => 'For Her', default => 'Unisex' };

$threshold = setting_money('free_shipping_threshold', PRICING_DEFAULT_FREE_THRESHOLD);
$trust = [
    ['icon' => 'cod', 'title' => 'Cash on Delivery', 'text' => 'Pay when your parcel arrives.'],
    ['icon' => 'truck', 'title' => 'Free delivery above ' . money($threshold), 'text' => 'Nationwide, tracked to your door.'],
    ['icon' => 'clock', 'title' => (string) setting('delivery_time', '2–4 working days'), 'text' => 'Typical delivery time across Pakistan.'],
];
$accordions = array_values(array_filter([
    ['id' => 'description', 'title' => 'Description', 'body' => trim((string) ($product['description'] ?? '')), 'open' => true],
    ['id' => 'how-to-wear', 'title' => 'How to wear it', 'body' => trim((string) setting('pdp_howto', 'Apply to pulse points — wrists, neck and behind the ears — on moisturised skin. Do not rub; let the scent settle and bloom over the first twenty minutes.')), 'open' => false],
    ['id' => 'shipping', 'title' => 'Shipping & Returns', 'body' => trim((string) setting('pdp_shipping_blurb', 'Delivery across Pakistan in ' . setting('delivery_time', '2–4 working days') . '. Free delivery on orders above ' . money($threshold) . '. Unopened bottles can be exchanged within 7 days.')), 'open' => false],
], static fn (array $item): bool => $item['body'] !== ''));

$whatsapp = product_whatsapp($product, $defaultSize);
$sizesJson = [
    'product' => ['id' => $productId, 'name' => $productName],
    'sizes' => array_map(static fn (array $size): array => [
        'id' => $size['id'],
        'label' => $size['label'],
        'sku' => $size['sku'],
        'price_display' => $size['price_display'],
        'sale_price_display' => $size['sale_price_display'],
        'save_percent' => $size['save_percent'],
        'stock' => $size['stock'],
    ], $sizes),
];

$familyLabel = $product['scent_family'] ? $product['scent_family'] . ' perfume' : 'luxury perfume';
$pageTitle = trim((string) ($product['seo_title'] ?: ($productName . ' — ' . $familyLabel)));
$head['title'] = str_ends_with(mb_strtolower($pageTitle, 'UTF-8'), 'sky fragrances') ? $pageTitle : $pageTitle . ' | Sky Fragrances';
$head['meta_description'] = product_meta_description($product, $sizes);
$head['canonical'] = canonical('/product/' . $product['slug']);
$head['og'] = ['type' => 'product', 'title' => $productName];
foreach ($images as $image) {
    if ($image['og'] !== '') {
        $head['og']['image'] = product_absolute_url($image['og']);
        break;
    }
}
$head['jsonld'] = product_jsonld($product, $sizes, $defaultSize, $images, $rating, $reviews, $collection);

render($route['view'], [
    'heading' => $productName,
    'product' => $product,
    'productId' => $productId,
    'collection' => $collection,
    'familyUrl' => $familyUrl,
    'genderLabel' => $genderLabel,
    'sizes' => $sizes,
    'defaultSize' => $defaultSize,
    'sizesJson' => $sizesJson,
    'allSoldOut' => $allSoldOut,
    'badges' => $badges,
    'images' => $images,
    'rating' => $rating,
    'reviews' => $reviews,
    'reviewsVisible' => PRODUCT_REVIEWS_VISIBLE,
    'related' => $related,
    'notes' => $notes,
    'meters' => $meters,
    'seasons' => $seasons,
    'occasions' => $occasions,
    'trust' => $trust,
    'accordions' => $accordions,
    'whatsapp' => $whatsapp,
    'trapField' => form_trap_field(),
    'reviewAction' => url('/product/' . $product['slug'] . '/review'),
    'cartAction' => url('/cart'),
], $head);
