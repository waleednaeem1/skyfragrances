<?php
defined('SKYFR') || exit;
$admin = auth_user();
$title = trim((string) ($head['title'] ?? ''));
$pageTitle = ($title === '' ? 'Admin' : $title) . ' · Sky Fragrances';
$bodyClass = trim((string) ($head['body_class'] ?? 'admin t-light'));
if (!str_contains($bodyClass, 't-light')) {
    $bodyClass .= ' t-light';
}
$currentPath = request_path();
$isActive = static fn (string $prefix): bool => $currentPath === '/admin' . $prefix || ($prefix !== '' && str_starts_with($currentPath, '/admin' . $prefix . '/'));
$badges = ['orders' => 0, 'reviews' => 0];
if ($admin !== null) {
    if (isset($head['badges']) && is_array($head['badges'])) {
        $badges = $head['badges'] + $badges;
    } else {
        try {
            $badges['orders'] = (int) db_fetch_column("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'confirmed')");
            $badges['reviews'] = (int) db_fetch_column("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");
        } catch (Throwable $badgeError) {
            log_write('warning', 'Admin nav badges unavailable', ['error' => $badgeError->getMessage()]);
        }
    }
}
$createUrl = (string) ($head['create_url'] ?? ($isActive('/coupons') ? '/admin/coupons/new' : ($isActive('/collections') ? '/admin/collections/new' : '/admin/products/new')));
$backUrl = (string) ($head['back'] ?? '');
$flashes = flash_take();
$navData = ['isActive' => $isActive, 'currentPath' => $currentPath, 'badges' => $badges, 'createUrl' => $createUrl];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="theme-color" content="#0A0A0A">
<title><?= e($pageTitle) ?></title>
<link rel="icon" href="<?= e(url('/favicon.ico')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>" data-base="<?= e(BASE_PATH) ?>">
<?php if ($admin === null): ?>
<div class="adm-login">
  <div class="adm-login__inner">
    <?php partial_admin('flash.php', ['flashes' => $flashes]); ?>
    <?= $content ?>
  </div>
</div>
<?php else: ?>
<a class="adm-skip" href="#main">Skip to content</a>
<header class="adm-top">
<?php if ($backUrl !== ''): ?>
  <a class="adm-top__back" href="<?= e(url($backUrl)) ?>" aria-label="Back">&larr;</a>
<?php else: ?>
  <a class="adm-top__logo" href="<?= e(url('/admin')) ?>" aria-label="Dashboard"><img src="<?= e(asset('img/brand/monogram-transparent-256.webp')) ?>" alt="Sky Fragrances" width="34" height="34" decoding="async"></a>
<?php endif; ?>
  <span class="adm-top__title"><?= e($title === '' ? 'Dashboard' : $title) ?></span>
  <a class="adm-top__link" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">Shop</a>
</header>
<?php partial_admin('nav-side.php', $navData); ?>
<main class="adm-main<?= !empty($head['wide']) ? ' adm-main--wide' : '' ?>" id="main" tabindex="-1">
  <?php partial_admin('flash.php', ['flashes' => $flashes]); ?>
  <?= $content ?>
</main>
<?php partial_admin('nav-bottom.php', $navData); ?>
<?php partial_admin('confirm.php'); ?>
<?php endif; ?>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
