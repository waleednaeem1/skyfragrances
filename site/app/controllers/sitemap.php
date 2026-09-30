<?php
defined('SKYFR') || exit;

function sitemap_lastmod(?string $datetime): string
{
    $time = $datetime ? strtotime($datetime) : false;
    return date(DATE_W3C, $time === false ? time() : $time);
}

function sitemap_entry(string $path, ?string $lastmod): string
{
    return '  <url><loc>' . e(canonical($path)) . '</loc><lastmod>' . e(sitemap_lastmod($lastmod)) . '</lastmod></url>' . "\n";
}

function sitemap_build(): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $latestProduct = db_fetch_column('SELECT MAX(COALESCE(updated_at, created_at)) FROM products WHERE is_active = 1 AND deleted_at IS NULL');
    $latestSetting = db_fetch_column('SELECT MAX(updated_at) FROM settings');
    $latestSite = max((string) $latestProduct, (string) $latestSetting) ?: null;
    foreach (['/', '/shop', '/collections', '/for-him', '/for-her', '/unisex', '/new-arrivals', '/best-sellers', '/sale'] as $path) {
        $xml .= sitemap_entry($path, $latestProduct);
    }
    $xml .= sitemap_entry('/scent-finder', $latestSite);
    $xml .= sitemap_entry('/contact', $latestSite);
    foreach (db_fetch_all('SELECT slug, updated_at, created_at FROM content_pages WHERE is_active = 1 ORDER BY sort_order ASC') as $row) {
        $xml .= sitemap_entry('/' . $row['slug'], $row['updated_at'] ?? $row['created_at']);
    }
    foreach (db_fetch_all('SELECT slug, updated_at, created_at FROM collections WHERE is_active = 1 ORDER BY sort_order ASC') as $row) {
        $xml .= sitemap_entry('/collections/' . $row['slug'], $row['updated_at'] ?? $row['created_at']);
    }
    foreach (db_fetch_all('SELECT sf.slug, sf.updated_at, sf.created_at FROM scent_families sf WHERE sf.is_active = 1 AND EXISTS (SELECT 1 FROM products p WHERE p.scent_family = sf.name AND p.is_active = 1 AND p.deleted_at IS NULL) ORDER BY sf.sort_order ASC') as $row) {
        $xml .= sitemap_entry('/scent/' . $row['slug'], $row['updated_at'] ?? $row['created_at']);
    }
    foreach (db_fetch_all('SELECT slug, updated_at, created_at FROM products WHERE is_active = 1 AND deleted_at IS NULL ORDER BY sort_order ASC, id ASC') as $row) {
        $xml .= sitemap_entry('/product/' . $row['slug'], $row['updated_at'] ?? $row['created_at']);
    }
    return $xml . '</urlset>' . "\n";
}

function sitemap_output(string $contentType, string $body): never
{
    response_clear_buffers();
    http_response_code(200);
    header('Content-Type: ' . $contentType);
    header('X-Robots-Tag: noindex');
    header('Cache-Control: public, max-age=3600');
    echo $body;
    exit;
}

if (!setting_bool('site_indexable', true)) {
    abort(404);
}

$cacheFile = APP_ROOT . '/storage/cache/sitemap.xml';
$cacheFresh = is_file($cacheFile) && (time() - (int) filemtime($cacheFile)) < 3600;
$xml = $cacheFresh ? (string) file_get_contents($cacheFile) : sitemap_build();
if (!$cacheFresh) {
    $tmp = $cacheFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $xml, LOCK_EX) !== false) {
        @rename($tmp, $cacheFile);
    }
}

sitemap_output('application/xml; charset=utf-8', $xml);
