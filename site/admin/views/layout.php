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
$backUrl = (string) ($head['back'] ?? '');
$flashes = flash_take();
$counts = $admin !== null ? admin_shell_counts() : [];
$navData = ['currentPath' => $currentPath, 'counts' => $counts, 'nav' => $admin !== null ? admin_nav($admin) : []];
?>
<!doctype html>
<html lang="en" class="adm-root" data-theme="colorful">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="theme-color" content="#F4F4F1">
<title><?= e($pageTitle) ?></title>
<link rel="icon" href="<?= e(url('/favicon.ico')) ?>">
<link rel="preload" href="<?= e(asset('fonts/geist-variable.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>" data-base="<?= e(BASE_PATH) ?>">
<?php if ($admin === null): ?>
<div class="adm-login">
  <main class="adm-login__panel" id="main">
    <div class="adm-login__inner">
      <?php partial_admin('flash.php', ['flashes' => $flashes]); ?>
      <?= $content ?>
    </div>
  </main>
  <aside class="adm-login__art" aria-hidden="true">
    <p class="adm-login__tag"><span>SKY/ADMIN</span><span><?= e(date('Y')) ?></span></p>
    <div class="adm-login__bars"><span></span><span></span><span></span><span></span><span class="is-hi"></span><span></span></div>
    <p class="adm-login__slogan">Every order, bottle and rupee — in one place.</p>
    <p class="adm-login__tagline"><?= e((string) setting('store_tagline', 'More Than Just A Scent')) ?>.</p>
  </aside>
</div>
<?php else: ?>
<a class="adm-skip" href="#main">Skip to content</a>
<div class="adm-app">
<?php partial_admin('nav-side.php', $navData); ?>
  <div class="adm-frame">
    <div class="adm-canvas">
<?php partial_admin('topbar.php', ['counts' => $counts, 'crumbs' => admin_breadcrumbs($currentPath, $title === '' ? 'Dashboard' : $title, $backUrl)]); ?>
      <main class="adm-main<?= !empty($head['wide']) ? ' adm-main--wide' : '' ?>" id="main" tabindex="-1">
        <?php partial_admin('flash.php', ['flashes' => $flashes]); ?>
        <?= $content ?>
      </main>
    </div>
  </div>
</div>
<?php partial_admin('confirm.php'); ?>
<?php partial_admin('palette.php', ['palette' => admin_palette($admin)]); ?>
<?php endif; ?>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
