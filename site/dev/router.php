<?php
$siteRoot = dirname(__DIR__);
$rawPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$path = rawurldecode($rawPath);
$query = (string) ($_SERVER['QUERY_STRING'] ?? '');

function dev_deny(int $status, string $message): bool
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    return true;
}

function dev_redirect(string $location): bool
{
    http_response_code(301);
    header('Location: ' . $location);
    return true;
}

function dev_accepts_gzip(): bool
{
    return str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '')), 'gzip');
}

function dev_serve_gzipped(string $file, string $type): bool
{
    header('Content-Type: ' . $type);
    header('Content-Encoding: gzip');
    header('Vary: Accept-Encoding');
    header('Cache-Control: public, max-age=31536000, immutable');
    header('X-Content-Type-Options: nosniff');
    header('Cross-Origin-Resource-Policy: same-origin');
    echo gzencode((string) file_get_contents($file), 6);
    return true;
}

function dev_serve_immutable(string $file, string $type): bool
{
    header('Content-Type: ' . $type);
    header('Content-Length: ' . (string) filesize($file));
    header('Cache-Control: public, max-age=31536000, immutable');
    header('X-Content-Type-Options: nosniff');
    header('Cross-Origin-Resource-Policy: same-origin');
    readfile($file);
    return true;
}

$devGzipTypes = ['css' => 'text/css; charset=utf-8', 'js' => 'application/javascript; charset=utf-8', 'mjs' => 'application/javascript; charset=utf-8', 'json' => 'application/json; charset=utf-8', 'svg' => 'image/svg+xml'];
$devImmutableTypes = ['webp' => 'image/webp', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'ico' => 'image/x-icon', 'woff2' => 'font/woff2'];

if (str_contains($path, "\0") || str_contains($path, '..') || str_contains($path, '\\')) {
    return dev_deny(404, 'Not found');
}
if (preg_match('#^/(app|db|storage|dev)(/|$)#i', $path)) {
    return dev_deny(403, 'Forbidden');
}
if (preg_match('#^/config[^/]*\.php$#i', $path) || preg_match('#(^|/)\.(user\.ini|htaccess|htpasswd|env|git[^/]*)$#i', $path)) {
    return dev_deny(403, 'Forbidden');
}
if (preg_match('#\.(sql|log|bak|old|swp|dist|md)$#i', $path)) {
    return dev_deny(403, 'Forbidden');
}
if (preg_match('#^/index\.php(/.*)?$#i', $path, $m)) {
    return dev_redirect(($m[1] ?? '') === '' ? '/' : $m[1] . ($query === '' ? '' : '?' . $query));
}
if ($path !== '/' && str_ends_with($path, '/') && !is_dir($siteRoot . rtrim($path, '/'))) {
    return dev_redirect(rtrim($path, '/') . ($query === '' ? '' : '?' . $query));
}
if (preg_match('#^/uploads/#i', $path)) {
    if (preg_match('#\.(php|phtml|php[0-9]|phps|pht|phar|inc|cgi|pl|py|sh|shtml)$#i', $path)) {
        return dev_deny(403, 'Forbidden');
    }
    if (is_file($siteRoot . $path)) {
        return false;
    }
    return dev_deny(404, 'Not found');
}
if (preg_match('#^/assets/#i', $path)) {
    if (is_file($siteRoot . $path)) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (dev_accepts_gzip() && isset($devGzipTypes[$ext])) {
            return dev_serve_gzipped($siteRoot . $path, $devGzipTypes[$ext]);
        }
        if (isset($devImmutableTypes[$ext])) {
            return dev_serve_immutable($siteRoot . $path, $devImmutableTypes[$ext]);
        }
        return false;
    }
    return dev_deny(404, 'Not found');
}
if (dev_accepts_gzip() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !preg_match('#^/api/#i', $path)) {
    ini_set('zlib.output_compression', '1');
}
if (preg_match('#^/admin(/.*)?$#i', $path)) {
    require $siteRoot . '/admin/index.php';
    return true;
}
if ($path !== '/' && is_file($siteRoot . $path)) {
    return false;
}
require $siteRoot . '/index.php';
return true;
