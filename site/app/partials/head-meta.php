<?php
defined('SKYFR') || exit;
$storeName = (string) setting('store_name', 'Sky Fragrances');
$brandSuffix = ' | ' . $storeName;
$title = trim(preg_replace('/\s+/u', ' ', (string) ($head['title'] ?? '')) ?? '');
if ($title === '') {
    $title = $storeName . ' — Luxury Perfumes in Pakistan';
}
if (mb_strlen($title, 'UTF-8') > 60 && str_ends_with($title, $brandSuffix)) {
    $title = trim(mb_substr($title, 0, -mb_strlen($brandSuffix, 'UTF-8'), 'UTF-8'));
}
if (mb_strlen($title, 'UTF-8') > 60) {
    $titleCut = mb_substr($title, 0, 60, 'UTF-8');
    $titleSpace = mb_strrpos($titleCut, ' ', 0, 'UTF-8');
    $title = preg_replace('/[\s,;:.\-–—|]+$/u', '', $titleSpace === false ? $titleCut : mb_substr($titleCut, 0, $titleSpace, 'UTF-8')) ?? $titleCut;
}
$description = trim(preg_replace('/\s+/u', ' ', (string) ($head['meta_description'] ?? '')) ?? '');
if ($description === '') {
    $description = (string) setting('meta_description', 'Luxury fragrance, made for Pakistan.');
}
if (mb_strlen($description, 'UTF-8') > 155) {
    $descriptionCut = mb_substr($description, 0, 155, 'UTF-8');
    $descriptionProbe = $descriptionCut . ' ';
    $sentenceEnd = 0;
    foreach (['. ', '! ', '? '] as $sentenceMark) {
        $sentenceEnd = max($sentenceEnd, (int) mb_strrpos($descriptionProbe, $sentenceMark, 0, 'UTF-8'));
    }
    if ($sentenceEnd > 60) {
        $description = mb_substr($descriptionCut, 0, $sentenceEnd + 1, 'UTF-8');
    } else {
        $descriptionSpace = mb_strrpos($descriptionCut, ' ', 0, 'UTF-8');
        $description = preg_replace('/[\s,;:\-–—]+$/u', '', $descriptionSpace === false ? $descriptionCut : mb_substr($descriptionCut, 0, $descriptionSpace, 'UTF-8')) ?? $descriptionCut;
    }
}
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
$ogTitle = trim((string) ($og['title'] ?? $title));
if ($ogTitle !== $brandSuffix && str_ends_with($ogTitle, $brandSuffix)) {
    $ogTitle = trim(mb_substr($ogTitle, 0, -mb_strlen($brandSuffix, 'UTF-8'), 'UTF-8'));
}
$ogDescription = (string) ($og['description'] ?? $description);
$ogImage = (string) ($og['image'] ?? '');
$ogImagePath = strtolower((string) (parse_url($ogImage, PHP_URL_PATH) ?: ''));
if ($ogImage === '' || !str_ends_with($ogImagePath, '-og.jpg')) {
    $ogImage = SITE_URL . '/' . ltrim((string) setting('og_default_image', 'assets/img/og-default.jpg'), '/');
}
$ogWidth = 1200;
$ogHeight = 630;
if (str_starts_with($ogImage, SITE_URL . '/')) {
    $ogLocal = rawurldecode(substr($ogImage, strlen(SITE_URL)));
    if (!str_contains($ogLocal, '..') && is_file(APP_ROOT . $ogLocal)) {
        $ogSize = @getimagesize(APP_ROOT . $ogLocal);
        if (is_array($ogSize) && (int) $ogSize[0] > 0 && (int) $ogSize[1] > 0) {
            $ogWidth = (int) $ogSize[0];
            $ogHeight = (int) $ogSize[1];
        }
    }
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
$motionPage = array_intersect(['home', 'listing', 'collections', 'product', 'quiz'], $bodyClasses) !== [];
$isHomePage = in_array('home', $bodyClasses, true);
$hasCriticalCss = is_file(APP_ROOT . '/assets/css/critical.css');
$deferSiteCss = $hasCriticalCss && array_intersect(['home', 'listing', 'product'], $bodyClasses) !== [] && !str_starts_with(request_path(), '/search');
$motionVersion = 0;
foreach (array_merge(glob(APP_ROOT . '/assets/js/motion/*.js') ?: [], glob(APP_ROOT . '/assets/js/vendor/*.js') ?: []) as $motionFile) {
    $motionVersion = max($motionVersion, (int) @filemtime($motionFile));
}
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
<meta property="og:image:width" content="<?= e((string) $ogWidth) ?>">
<meta property="og:image:height" content="<?= e((string) $ogHeight) ?>">
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
<link rel="preload" href="<?= e(BASE_PATH . '/assets/fonts/' . $fontFile) ?>" as="font" type="font/woff2" crossorigin>
<?php endforeach; ?>
<?php foreach ($heroPreloads as [$heroHref, $heroMedia]): ?>
<link rel="preload" as="image" href="<?= e($heroHref) ?>" media="<?= e($heroMedia) ?>" fetchpriority="high">
<?php endforeach; ?>
<script src="<?= e(asset('js/motion/gate.js')) ?>"<?= $isHomePage ? ' data-page="home" data-intro="' . e(asset('js/intro.js')) . '"' : '' ?>></script>
<?php if ($deferSiteCss): ?>
<link rel="stylesheet" href="<?= e(asset('css/critical.css')) ?>">
<link rel="preload" href="<?= e(asset('css/site.css')) ?>" as="style" fetchpriority="low">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>" media="print" data-media="all">
<noscript><link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>"></noscript>
<?php elseif ($hasCriticalCss): ?>
<link rel="stylesheet" href="<?= e(asset('css/critical.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<?php else: ?>
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<?php endif; ?>
<?php if ($motionPage): ?>
<link id="sf-motion-css" rel="stylesheet" href="<?= e(asset('css/motion.css')) ?>">
<?php endif; ?>
<script>document.documentElement.classList.add('js');</script>
<?php if ($deferSiteCss): ?>
<script src="<?= e(asset('js/css.js')) ?>" async></script>
<?php endif; ?>
<script src="<?= e(asset('js/motion/config.js')) ?>" defer></script>
<script type="module" src="<?= e(asset('js/motion/core.js')) ?>" defer data-motion-v="<?= e((string) $motionVersion) ?>"></script>
<?php foreach ($jsonld as $block): ?>
<script type="application/ld+json"><?= ejs($block) ?></script>
<?php endforeach; ?>
