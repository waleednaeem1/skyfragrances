<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/cart.php';

if (request_origin_is_foreign()) {
    json(['ok' => false, 'error' => 'origin'], 403);
}

$q = trim(request_query_string('q', ''));
$q = mb_substr(preg_replace('/\s+/u', ' ', $q) ?? '', 0, 80);
if (mb_strlen($q) < 2) {
    json(['ok' => true, 'q' => $q, 'products' => [], 'collections' => []]);
}

$like = '%' . addcslashes($q, '%_\\') . '%';
$prefix = addcslashes($q, '%_\\') . '%';

$products = db_fetch_all(
    'SELECT p.id, p.name, p.slug, p.scent_family,
        (SELECT MIN(COALESCE(s.sale_price, s.price)) FROM product_sizes s WHERE s.product_id = p.id AND s.is_active = 1) AS price_from,
        (SELECT i.filename FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.sort_order ASC LIMIT 1) AS image
     FROM products p
     WHERE p.is_active = 1 AND p.deleted_at IS NULL
       AND (p.name LIKE :like1 OR p.scent_family LIKE :like2 OR p.short_description LIKE :like3 OR p.notes_top LIKE :like4 OR p.notes_heart LIKE :like5 OR p.notes_base LIKE :like6)
     ORDER BY (CASE WHEN p.name LIKE :prefix THEN 0 WHEN p.name LIKE :like7 THEN 1 ELSE 2 END), p.is_featured DESC, p.sort_order ASC
     LIMIT 5',
    ['like1' => $like, 'like2' => $like, 'like3' => $like, 'like4' => $like, 'like5' => $like, 'like6' => $like, 'prefix' => $prefix, 'like7' => $like]
);
$collections = db_fetch_all(
    'SELECT name, slug FROM collections WHERE is_active = 1 AND name LIKE :like ORDER BY sort_order ASC LIMIT 3',
    ['like' => $like]
);

json([
    'ok' => true,
    'q' => $q,
    'products' => array_map(static fn (array $p) => [
        'name' => $p['name'],
        'family' => $p['scent_family'],
        'url' => url('/product/' . $p['slug']),
        'price' => $p['price_from'] !== null ? money((string) $p['price_from']) : null,
        'image' => cart_image_url((int) $p['id'], $p['image']),
    ], $products),
    'collections' => array_map(static fn (array $c) => ['name' => $c['name'], 'url' => url('/collections/' . $c['slug'])], $collections),
]);
