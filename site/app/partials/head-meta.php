<?php
defined('SKYFR') || exit;
$storeName = (string) setting('store_name', 'Sky Fragrances');
$title = trim((string) ($head['title'] ?? ''));
if ($title === '') {
    $title = $storeName . ' — Luxury Perfumes in Pakistan';
}
$title = truncate_on_word($title, 60);
$description = trim((string) ($head['meta_description'] ?? ''));
if ($description === '') {
    $description = (string) setting('meta_description', 'Luxury fragrance, made for Pakistan.');
}
$description = truncate_on_word($description, 155);
$robots = (string) ($head['robots'] ?? 'index,follow');
if (!setting_bool('site_indexable', true)) {
    $robots = 'noindex,nofollow';
}
$canonicalUrl = trim((string) ($head['canonical'] ?? ''));
if ($canonicalUrl === '' && !str_contains($robots, 'noindex')) {
    $canonicalUrl = canonical(request_path() === '/' ? '' : request_path());
}
$og = is_array($head['og'] ?? null) ? $head['og'] : [];
$ogType = (string) ($og['type'] ?? 'website');
$ogTitle = (string) ($og['title'] ?? $title);
$ogDescription = (string) ($og['description'] ?? $description);
$ogImage = (string) ($og['image'] ?? '');
if ($ogImage === '') {
    $ogImage = SITE_URL . '/' . ltrim((string) setting('og_default_image', 'assets/img/og-default.jpg'), '/');
}
$ogUrl = (string) ($og['url'] ?? ($canonicalUrl !== '' ? $canonicalUrl : SITE_URL . '/'));
$jsonld = is_array($head['jsonld'] ?? null) ? $head['jsonld'] : [];
$sameAs = [];
foreach (['instagram_url', 'facebook_url', 'tiktok_url', 'youtube_url'] as $socialKey) {
    $socialUrl = (string) setting($socialKey, '');
    if ($socialUrl !== '') {
        $sameAs[] = $socialUrl;
    }
}
$organization = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $storeName,
    'url' => SITE_URL . '/',
    'logo' => SITE_URL . '/' . ltrim((string) setting('logo_path', 'assets/img/logo.png'), '/'),
];
if ($sameAs !== []) {
    $organization['sameAs'] = $sameAs;
}
$whatsappDigits = preg_replace('/\D+/', '', (string) setting('whatsapp', ''));
if ($whatsappDigits !== '') {
    $organization['contactPoint'] = [
        '@type' => 'ContactPoint',
        'telephone' => '+' . $whatsappDigits,
        'contactType' => 'customer service',
        'areaServed' => 'PK',
        'availableLanguage' => ['en', 'ur'],
    ];
}
$jsonld[] = $organization;
$fontDir = APP_ROOT . '/assets/fonts/';
$preloadFonts = [];
foreach (['jost-variable.woff2', 'cormorant-garamond-variable.woff2'] as $fontFile) {
    if (is_file($fontDir . $fontFile)) {
        $preloadFonts[] = $fontFile;
    }
}
$googleVerification = (string) setting('google_verification', '');
$bodyClasses = preg_split('/\s+/', (string) ($head['body_class'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
$heroPreloads = [];
if (in_array('home', $bodyClasses, true)) {
    foreach ([['hero_image_mobile', '(max-width: 767px)'], ['hero_image_desktop', '(min-width: 768px)']] as [$heroKey, $heroMedia]) {
        $heroValue = trim((string) setting($heroKey, ''));
        if ($heroValue !== '' && !str_contains($heroValue, '..')) {
            $heroPreloads[] = [preg_match('#^https?://#i', $heroValue) ? $heroValue : url('/' . ltrim($heroValue, '/')), $heroMedia];
        }
    }
}
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="robots" content="<?= e($robots) ?>">
<?php if ($canonicalUrl !== ''): ?>
<link rel="canonical" href="<?= e($canonicalUrl) ?>">
<?php endif; ?>
<meta name="theme-color" content="#0A0A0A">
<?php if ($googleVerification !== ''): ?>
<meta name="google-site-verification" content="<?= e($googleVerification) ?>">
<?php endif; ?>
<meta property="og:type" content="<?= e($ogType) ?>">
<meta property="og:title" content="<?= e($ogTitle) ?>">
<meta property="og:description" content="<?= e($ogDescription) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta property="og:url" content="<?= e($ogUrl) ?>">
<meta property="og:site_name" content="<?= e($storeName) ?>">
<meta property="og:locale" content="en_PK">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($ogTitle) ?>">
<meta name="twitter:description" content="<?= e($ogDescription) ?>">
<meta name="twitter:image" content="<?= e($ogImage) ?>">
<link rel="icon" href="<?= e(url('/favicon.ico')) ?>" sizes="any">
<link rel="icon" href="<?= e(asset('img/favicon-32.png')) ?>" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="<?= e(asset('img/apple-touch-icon-180.png')) ?>">
<?php foreach ($preloadFonts as $fontFile): ?>
<link rel="preload" href="<?= e(asset('fonts/' . $fontFile)) ?>" as="font" type="font/woff2" crossorigin>
<?php endforeach; ?>
<?php foreach ($heroPreloads as [$heroHref, $heroMedia]): ?>
<link rel="preload" as="image" href="<?= e($heroHref) ?>" media="<?= e($heroMedia) ?>" fetchpriority="high">
<?php endforeach; ?>
<script src="<?= e(asset('js/motion/config.js')) ?>"></script>
<script src="<?= e(asset('js/intro.js')) ?>"></script>
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<script>document.documentElement.classList.add('js');</script>
<?php foreach ($jsonld as $block): ?>
<script type="application/ld+json"><?= ejs($block) ?></script>
<?php endforeach; ?>
