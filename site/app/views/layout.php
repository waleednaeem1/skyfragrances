<?php
defined('SKYFR') || exit;
$head = is_array($head ?? null) ? $head : [];
$head['body_class'] = trim((string) ($head['body_class'] ?? ''));
$bodyClasses = preg_split('/\s+/', $head['body_class'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
$isHome = in_array('home', $bodyClasses, true);
$isProduct = in_array('product', $bodyClasses, true);
$chromeCollections = [];
try {
    $chromeCollections = db_fetch_all('SELECT id, name, slug, tagline, image FROM collections WHERE is_active = 1 ORDER BY sort_order ASC, name ASC LIMIT 8');
} catch (Throwable $chromeError) {
    log_write('warning', 'Header collections unavailable', ['error' => $chromeError->getMessage()]);
}
$noJsNavLinks = [
    ['/shop', 'Shop'],
    ['/collections', 'Collections'],
    ['/for-him', 'For Him'],
    ['/for-her', 'For Her'],
    ['/unisex', 'Unisex'],
    ['/scent-finder', 'Scent Finder'],
    ['/track', 'Track Order'],
    ['/contact', 'Contact'],
];
?>
<!doctype html>
<html lang="en">
<head>
<?php partial('head-meta.php', ['head' => $head]); ?>
<meta name="csrf-token" content="<?= session_status() === PHP_SESSION_ACTIVE ? e(csrf_token()) : '' ?>">
</head>
<body class="<?= e($head['body_class']) ?>" data-base="<?= e(BASE_PATH) ?>" data-motion="micro">
<a class="skip-link" href="#main">Skip to content</a>
<?php if ($isHome) { partial('intro.php'); } ?>
<?php partial('announcement-bar.php'); ?>
<div class="header-sentinel" aria-hidden="true"></div>
<?php partial('header.php', ['isHome' => $isHome, 'collections' => $chromeCollections]); ?>
<?php partial('nav-mobile.php', ['collections' => $chromeCollections]); ?>
<main id="main" class="site-main" tabindex="-1">
<?php partial('flash.php'); ?>
<?php echo $content; ?>
</main>
<nav class="site-footer__nav-fallback container" id="site-nav-fallback" aria-label="Site">
  <ul class="cluster">
<?php foreach ($noJsNavLinks as [$fallbackPath, $fallbackLabel]): ?>
    <li><a class="btn btn--text" href="<?= e(url($fallbackPath)) ?>"><span class="btn__label"><?= e($fallbackLabel) ?></span></a></li>
<?php endforeach; ?>
  </ul>
</nav>
<?php partial('footer.php', ['hideNewsletter' => $isHome]); ?>
<?php partial('cart-drawer.php'); ?>
<?php partial('whatsapp-button.php'); ?>
<?php partial('toast-region.php'); ?>
<?php partial('transition-overlay.php', ['bodyClasses' => $bodyClasses]); ?>
<script src="<?= e(asset('js/reveal.js')) ?>" defer></script>
<script src="<?= e(asset('js/ui.js')) ?>" defer></script>
<script src="<?= e(asset('js/cart.js')) ?>" defer></script>
<?php if ($isProduct): ?>
<script src="<?= e(asset('js/product.js')) ?>" defer></script>
<?php endif; ?>
<script src="<?= e(asset('js/forms.js')) ?>" defer></script>
</body>
</html>
