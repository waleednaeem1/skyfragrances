<?php
defined('SKYFR') || exit;

partial('product-card.php', []);
partial('collection-card.php', []);

const COLLECTIONS_INDEX_THUMBS = 3;

$collections = db_fetch_all(
    'SELECT c.id, c.name, c.slug, c.tagline, c.description, c.mood, c.image,
        (SELECT COUNT(*) FROM products p WHERE p.collection_id = c.id AND p.is_active = 1 AND p.deleted_at IS NULL) AS product_count
     FROM collections c WHERE c.is_active = 1 ORDER BY c.sort_order ASC, c.name ASC'
);
if ($collections === []) {
    redirect('/shop', 302);
}

$candidates = db_fetch_all(
    'SELECT p.id, p.collection_id, p.name, p.slug, s.min_price,
        (SELECT i.filename FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.sort_order ASC, i.id ASC LIMIT 1) AS image,
        (SELECT i.alt_text FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.sort_order ASC, i.id ASC LIMIT 1) AS image_alt
     FROM products p
     JOIN (SELECT product_id, MIN(CASE WHEN sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price THEN sale_price ELSE price END) AS min_price
           FROM product_sizes WHERE is_active = 1 GROUP BY product_id) s ON s.product_id = p.id
     WHERE p.is_active = 1 AND p.deleted_at IS NULL AND p.collection_id IS NOT NULL
     ORDER BY p.collection_id ASC, s.min_price ASC, p.id ASC'
);
$thumbsByCollection = [];
foreach ($candidates as $candidate) {
    $collectionId = (int) $candidate['collection_id'];
    if (count($thumbsByCollection[$collectionId] ?? []) >= COLLECTIONS_INDEX_THUMBS) {
        continue;
    }
    $thumbsByCollection[$collectionId][] = [
        'name' => (string) $candidate['name'],
        'url' => url('/product/' . $candidate['slug']),
        'image_set' => product_card_image_set((int) $candidate['id'], $candidate['image']),
        'alt' => trim((string) ($candidate['image_alt'] ?? '')) !== '' ? (string) $candidate['image_alt'] : $candidate['name'] . ' eau de parfum',
        'price_display' => money((string) $candidate['min_price']),
    ];
}

$storeName = (string) setting('store_name', 'Sky Fragrances');
$listItems = [];
foreach ($collections as $index => &$collection) {
    $collection['product_count'] = (int) $collection['product_count'];
    $collection['url'] = url('/collections/' . $collection['slug']);
    $collection['thumbs'] = $thumbsByCollection[(int) $collection['id']] ?? [];
    $collection['image_sources'] = collection_card_image_sources($collection['image'] ?? null, 'card');
    $listItems[] = ['@type' => 'ListItem', 'position' => $index + 1, 'url' => canonical('/collections/' . $collection['slug']), 'name' => (string) $collection['name']];
}
unset($collection);

$intro = (string) setting('collections_intro', 'Five skies, five moods. Each collection is a time of day, bottled — find the one that sounds like you.');

$head['title'] = 'Our Collections | ' . $storeName;
$head['meta_description'] = 'Explore the ' . $storeName . ' collections — curated luxury perfumes for every mood, season and occasion, delivered across Pakistan.';
$head['canonical'] = canonical('/collections');
$head['jsonld'] = [
    ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Collections', 'item' => canonical('/collections')],
    ]],
    ['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => 'Our Collections', 'url' => canonical('/collections'), 'mainEntity' => ['@type' => 'ItemList', 'numberOfItems' => count($collections), 'itemListElement' => $listItems]],
];

render($route['view'], [
    'heading' => 'Our Collections',
    'intro' => $intro,
    'collections' => $collections,
    'breadcrumbs' => [['label' => 'Home', 'url' => '/'], ['label' => 'Collections', 'url' => '']],
], $head);
