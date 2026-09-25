<?php
defined('SKYFR') || exit;

function url(string $path = ''): string
{
    if ($path === '') {
        return BASE_PATH . '/';
    }
    if ($path[0] !== '/') {
        $path = '/' . $path;
    }
    return BASE_PATH . $path;
}

function asset(string $relPath): string
{
    $relPath = ltrim($relPath, '/');
    $version = @filemtime(APP_ROOT . '/assets/' . $relPath);
    return BASE_PATH . '/assets/' . $relPath . '?v=' . ($version === false ? 0 : $version);
}

function canonical(string $path): string
{
    if ($path !== '' && $path[0] !== '/') {
        $path = '/' . $path;
    }
    return SITE_URL . $path;
}

function current_url(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if (BASE_PATH !== '' && str_starts_with($uri, BASE_PATH)) {
        $uri = substr($uri, strlen(BASE_PATH));
    }
    if ($uri === '' || $uri[0] !== '/') {
        $uri = '/' . $uri;
    }
    return SITE_URL . $uri;
}
