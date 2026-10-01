<?php
defined('SKYFR') || exit;

const THEME_LIGHT_ASSETS = [
    'monogram-transparent-256.webp' => 'monogram-gilt-256.webp',
    'monogram-transparent-256.png' => 'monogram-gilt-256.png',
    'monogram-transparent-512.png' => 'monogram-gilt-512.png',
    'lockup-transparent-800.webp' => 'lockup-gilt-800.webp',
    'lockup-transparent-800.png' => 'lockup-gilt-800.png',
    'lockup-on-black-800.webp' => 'lockup-transparent-800.webp',
    'placeholder-4x5.svg' => 'placeholder-4x5-light.svg',
];

function storefront_theme(): string
{
    if (defined('APP_ENV') && APP_ENV === 'development') {
        $override = getenv('SKYFR_THEME');
        if ($override === 'light' || $override === 'dark') {
            return $override;
        }
    }
    return setting('site_theme') === 'light' ? 'light' : 'dark';
}

function brand_asset(string $name): string
{
    $path = ltrim($name, '/');
    if (!str_contains($path, '/')) {
        $path = (str_starts_with($path, 'placeholder') ? 'img/' : 'img/brand/') . $path;
    }
    if (defined('SKYFR_ADMIN') || storefront_theme() !== 'light') {
        return $path;
    }
    $slash = strrpos($path, '/');
    $swap = THEME_LIGHT_ASSETS[substr($path, $slash + 1)] ?? null;
    if ($swap === null) {
        return $path;
    }
    $candidate = substr($path, 0, $slash + 1) . $swap;
    return is_file(APP_ROOT . '/assets/' . $candidate) ? $candidate : $path;
}
