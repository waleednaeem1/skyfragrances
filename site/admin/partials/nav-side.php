<?php
defined('SKYFR') || exit;
$groups = [
    'Sales' => [
        ['/', 'Dashboard', 'grid', 0],
        ['/orders', 'Orders', 'package', (int) ($badges['orders'] ?? 0)],
        ['/customers', 'Customers', 'users', 0],
    ],
    'Catalogue' => [
        ['/products', 'Products', 'droplet', 0],
        ['/collections', 'Collections', 'layers', 0],
        ['/coupons', 'Coupons', 'tag', 0],
        ['/reviews', 'Reviews', 'star', (int) ($badges['reviews'] ?? 0)],
    ],
    'Audience' => [
        ['/messages', 'Messages', 'mail', 0],
        ['/subscribers', 'Subscribers', 'user-plus', 0],
    ],
    'Store' => [
        ['/pages', 'Pages', 'file', 0],
        ['/settings', 'Settings', 'settings', 0],
        ['/tools', 'Tools', 'tool', 0],
    ],
];
?>
<nav class="adm-side" aria-label="Sections">
  <a class="adm-side__brand" href="<?= e(url('/admin')) ?>" aria-label="Sky Fragrances admin home">
    <img src="<?= e(asset('img/brand/monogram-transparent-256.webp')) ?>" alt="" width="36" height="36" decoding="async">
    <span><span class="adm-side__brand-name">Sky Fragrances</span><span class="adm-side__brand-sub">Admin</span></span>
  </a>
<?php foreach ($groups as $group => $items): ?>
  <p class="adm-side__group"><?= e($group) ?></p>
<?php foreach ($items as [$path, $label, $icon, $count]): ?>
<?php $active = $path === '/' ? $currentPath === '/admin' : $isActive($path); ?>
  <a class="adm-side__link<?= $active ? ' is-active' : '' ?>" href="<?= e(url('/admin' . ($path === '/' ? '' : $path))) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
    <?php partial('icon.php', ['name' => $icon, 'size' => 18, 'class' => 'adm-side__icon']); ?>
    <span class="adm-side__label"><?= e($label) ?></span><?php if ($count > 0): ?><span class="adm-nav__badge"><?= e((string) $count) ?></span><?php endif; ?>
  </a>
<?php endforeach; ?>
<?php endforeach; ?>
  <div class="adm-side__foot">
    <a class="adm-side__link<?= $isActive('/password') ? ' is-active' : '' ?>" href="<?= e(url('/admin/password')) ?>"><?php partial('icon.php', ['name' => 'key', 'size' => 18, 'class' => 'adm-side__icon']); ?><span class="adm-side__label">Change password</span></a>
    <form method="post" action="<?= e(url('/admin/logout')) ?>"><?= csrf_field() ?><button type="submit" class="adm-side__link adm-side__link--danger"><?php partial('icon.php', ['name' => 'logout', 'size' => 18, 'class' => 'adm-side__icon']); ?><span class="adm-side__label">Log out</span></button></form>
  </div>
</nav>
