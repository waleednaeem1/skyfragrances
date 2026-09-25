<?php
defined('SKYFR') || exit;
$items = [
    ['/', 'Dashboard', 0],
    ['/orders', 'Orders', (int) ($badges['orders'] ?? 0)],
    ['/products', 'Products', 0],
    ['/collections', 'Collections', 0],
    ['/coupons', 'Coupons', 0],
    ['/reviews', 'Reviews', (int) ($badges['reviews'] ?? 0)],
    ['/messages', 'Messages', 0],
    ['/subscribers', 'Subscribers', 0],
    ['/pages', 'Pages', 0],
    ['/settings', 'Settings', 0],
];
?>
<nav class="adm-side" aria-label="Sections">
<?php foreach ($items as [$path, $label, $count]): ?>
<?php $active = $path === '/' ? $currentPath === '/admin' : $isActive($path); ?>
  <a class="adm-side__link<?= $active ? ' is-active' : '' ?>" href="<?= e(url('/admin' . ($path === '/' ? '' : $path))) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
    <?= e($label) ?><?php if ($count > 0): ?> <span class="adm-nav__badge"><?= e((string) $count) ?></span><?php endif; ?>
  </a>
<?php endforeach; ?>
  <hr class="adm-side__rule">
  <a class="adm-side__link<?= $isActive('/tools') ? ' is-active' : '' ?>" href="<?= e(url('/admin/tools')) ?>">Tools</a>
  <a class="adm-side__link<?= $isActive('/password') ? ' is-active' : '' ?>" href="<?= e(url('/admin/password')) ?>">Change password</a>
  <form method="post" action="<?= e(url('/admin/logout')) ?>"><?= csrf_field() ?><button type="submit" class="adm-side__link adm-side__link--danger">Log out</button></form>
</nav>
