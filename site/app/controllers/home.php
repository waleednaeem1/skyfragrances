<?php
defined('SKYFR') || exit;

function home_clamp_count(string $key, int $default): int
{
    return max(4, min(12, setting_int($key, $default)));
}

function home_attach_sizes_and_images(array ...$sets): array
{
    $ids = [];
    foreach ($sets as $set) {
        foreach ($set as $row) {
            $ids[(int) $row['id']] = true;
        }
    }
    if ($ids === []) {
        return $sets;
    }
    $placeholders = [];
    $params = [];
    foreach (array_keys($ids) as $index => $id) {
        $placeholders[] = ':p' . $index;
        $params['p' . $index] = $id;
    }
    $in = implode(', ', $placeholders);
    $sizesByProduct = [];
    foreach (db_fetch_all('SELECT id, product_id, size_label, size_ml, price, sale_price, stock, is_active FROM product_sizes WHERE is_active = 1 AND product_id IN (' . $in . ') ORDER BY product_id ASC, sort_order ASC, size_ml ASC', $params) as $size) {
        $sizesByProduct[(int) $size['product_id']][] = $size;
    }
    $imagesByProduct = [];
    foreach (db_fetch_all('SELECT product_id, filename, alt_text FROM product_images WHERE product_id IN (' . $in . ') ORDER BY product_id ASC, is_primary DESC, sort_order ASC, id ASC', $params) as $image) {
        $imagesByProduct[(int) $image['product_id']][] = $image;
    }
    foreach ($sets as &$set) {
        foreach ($set as &$row) {
            $productId = (int) $row['id'];
            $row['sizes'] = $sizesByProduct[$productId] ?? [];
            $images = $imagesByProduct[$productId] ?? [];
            $row['image'] = $images[0]['filename'] ?? null;
            $row['image_alt'] = $images[0]['alt_text'] ?? '';
            $row['image_alt_filename'] = $images[1]['filename'] ?? null;
        }
        unset($row);
    }
    unset($set);
    return $sets;
}

$productSelect = 'SELECT p.id, p.name, p.slug, p.gender, p.scent_family, p.short_description, p.is_new, p.is_featured, p.rating_avg, p.rating_count, p.published_at
    FROM products p WHERE p.is_active = 1 AND p.deleted_at IS NULL';

$collections = db_fetch_all('SELECT id, name, slug, tagline, image FROM collections WHERE is_active = 1 AND show_on_home = 1 ORDER BY sort_order ASC, name ASC LIMIT 5');
if (count($collections) < 3) {
    $collections = db_fetch_all('SELECT id, name, slug, tagline, image FROM collections WHERE is_active = 1 ORDER BY sort_order ASC, name ASC LIMIT 5');
}
if ($collections !== []) {
    $counts = db_fetch_pairs('SELECT collection_id, COUNT(*) FROM products WHERE is_active = 1 AND deleted_at IS NULL AND collection_id IS NOT NULL GROUP BY collection_id');
    foreach ($collections as &$collection) {
        $collection['product_count'] = (int) ($counts[(int) $collection['id']] ?? 0);
    }
    unset($collection);
}

$bestSellerLimit = home_clamp_count('home_bestsellers_count', 8);
$newLimit = home_clamp_count('home_new_count', 8);
$bestSellers = db_fetch_all($productSelect . ' ORDER BY p.is_featured DESC, CASE WHEN p.is_featured = 1 THEN p.sort_order ELSE 0 END ASC, p.sales_count DESC, p.rating_avg DESC, p.id DESC LIMIT ' . $bestSellerLimit);
$newArrivals = db_fetch_all($productSelect . ' ORDER BY p.published_at DESC, p.id DESC LIMIT ' . $newLimit);
[$bestSellers, $newArrivals] = home_attach_sizes_and_images($bestSellers, $newArrivals);
$hasCatalogue = count($bestSellers) >= 4;

$reviews = [];
if (setting_bool('home_reviews_enabled', true)) {
    $reviews = db_fetch_all('SELECT r.customer_name, r.customer_city, r.rating, r.title, r.body, r.created_at, p.name AS product_name, p.slug AS product_slug
        FROM reviews r INNER JOIN products p ON p.id = r.product_id
        WHERE r.status = :status AND p.is_active = 1 AND p.deleted_at IS NULL AND r.rating >= 4 AND CHAR_LENGTH(r.body) >= 60
        ORDER BY r.created_at DESC, r.id DESC LIMIT 3', ['status' => 'approved']);
    if (count($reviews) < 3) {
        $reviews = [];
    }
}

$instagramTiles = [];
for ($tile = 1; $tile <= 6; $tile++) {
    $tileImage = trim((string) setting('instagram_tile_' . $tile . '_image', ''));
    if ($tileImage === '' || str_contains($tileImage, '..')) {
        continue;
    }
    $tileUrl = trim((string) setting('instagram_tile_' . $tile . '_url', ''));
    $instagramTiles[] = [
        'image' => preg_match('#^https?://#i', $tileImage) ? $tileImage : url('/' . ltrim($tileImage, '/')),
        'url' => preg_match('#^https://#i', $tileUrl) ? $tileUrl : '',
        'alt' => 'Sky Fragrances on Instagram',
        'product' => null,
    ];
}
$instagramFromCatalogue = count($instagramTiles) < 3;
if ($instagramFromCatalogue) {
    $instagramTiles = [];
    $seen = [];
    foreach (array_merge($bestSellers, $newArrivals) as $row) {
        if (isset($seen[(int) $row['id']]) || empty($row['image'])) {
            continue;
        }
        $seen[(int) $row['id']] = true;
        $instagramTiles[] = ['image' => '', 'url' => '', 'alt' => $row['name'] . ' by Sky Fragrances', 'product' => $row];
        if (count($instagramTiles) === 6) {
            break;
        }
    }
    if (count($instagramTiles) < 3) {
        $instagramTiles = [];
    }
}

$thresholdDisplay = money(setting_money('free_shipping_threshold', '3000.00'));
$heroTrustDefault = 'Cash on Delivery nationwide · Free delivery above ' . $thresholdDisplay;
$heroTrust = str_replace(['{threshold}', 'Rs. {threshold}'], [$thresholdDisplay, $thresholdDisplay], (string) setting('hero_trust_line', $heroTrustDefault));

$head['title'] = 'Sky Fragrances — Luxury Perfumes in Pakistan';
$head['meta_description'] = (string) setting('meta_description', 'Luxury perfumes in Pakistan. Long-lasting eau de parfum for him, her and unisex. Cash on delivery nationwide, fast delivery, easy exchange.');
$head['canonical'] = canonical('/');
$head['og'] = ['type' => 'website', 'title' => $head['title'], 'url' => canonical('/')];
$head['jsonld'][] = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => (string) setting('store_name', 'Sky Fragrances'),
    'url' => SITE_URL . '/',
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => ['@type' => 'EntryPoint', 'urlTemplate' => SITE_URL . '/search?q={search_term_string}'],
        'query-input' => 'required name=search_term_string',
    ],
];

render($route['view'], [
    'heading' => (string) setting('hero_heading', 'More Than Just A Scent'),
    'hero' => [
        'heading' => (string) setting('hero_heading', 'More Than Just A Scent'),
        'subheading' => (string) setting('hero_subheading', 'Long-lasting eau de parfum, crafted for Pakistan and delivered to your door.'),
        'cta_label' => (string) setting('hero_cta_label', 'Shop The Collection'),
        'cta_url' => (string) setting('hero_cta_url', '/shop'),
        'secondary_label' => (string) setting('hero_secondary_label', 'Take the Scent Finder'),
        'secondary_url' => (string) setting('hero_secondary_url', '/scent-finder'),
        'image_desktop' => (string) setting('hero_image_desktop', ''),
        'image_mobile' => (string) setting('hero_image_mobile', ''),
        'overlay_opacity' => max(0, min(100, setting_int('hero_overlay_opacity', 60))),
        'trust' => $heroTrust,
    ],
    'collections' => $collections,
    'bestSellers' => $hasCatalogue ? $bestSellers : [],
    'newArrivals' => $hasCatalogue ? $newArrivals : [],
    'genderTiles' => [
        ['slug' => 'for-him', 'name' => 'For Him', 'image' => (string) setting('gender_tile_men_image', ''), 'tagline' => (string) setting('gender_tile_men_caption', 'Bold. Grounded. Warm.')],
        ['slug' => 'for-her', 'name' => 'For Her', 'image' => (string) setting('gender_tile_women_image', ''), 'tagline' => (string) setting('gender_tile_women_caption', 'Luminous. Soft. Certain.')],
        ['slug' => 'unisex', 'name' => 'Unisex', 'image' => (string) setting('gender_tile_unisex_image', ''), 'tagline' => (string) setting('gender_tile_unisex_caption', 'Neither. Both. Entirely yours.')],
    ],
    'quizBand' => setting_bool('quiz_band_enabled', true) ? [
        'eyebrow' => (string) setting('quiz_band_heading', 'Not sure where to start?'),
        'heading' => 'Find your signature scent in 60 seconds',
        'text' => (string) setting('quiz_band_text', 'Five quick questions about how you like to smell, and we match you with the perfumes that agree.'),
        'cta_label' => (string) setting('quiz_band_cta_label', 'Start the Scent Finder'),
    ] : null,
    'deliveryTime' => (string) setting('delivery_time', '2–4 working days'),
    'reviews' => $reviews,
    'instagram' => [
        'handle' => (string) setting('instagram_handle', '@skyfragrances'),
        'url' => (string) setting('instagram_url', ''),
        'tiles' => $instagramTiles,
        'fromCatalogue' => $instagramFromCatalogue,
    ],
], $head);
