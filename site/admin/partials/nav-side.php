<?php
defined('SKYFR') || exit;
$groups = array_filter(array_map(static fn (array $items): array => array_values(array_filter($items, static fn (array $item): bool => team_can_route($item[4]) && ($item[0] !== '/sales' || !team_is_owner()))), [
    'Sales' => [
        ['/', 'Dashboard', 'grid', 0, 'admin.dashboard'],
        ['/orders', 'Orders', 'package', (int) ($badges['orders'] ?? 0), 'admin.orders'],
        ['/customers', 'Customers', 'users', 0, 'admin.customers'],
        ['/sales', 'My sales', 'chart', 0, 'admin.sales'],
    ],
    'Money' => [
        ['/statements', 'Statements', 'chart', 0, 'admin.statements'],
        ['/expenses', 'Expenses', 'wallet', 0, 'admin.expenses'],
    ],
    'Catalogue' => [
        ['/products', 'Products', 'droplet', 0, 'admin.products'],
        ['/collections', 'Collections', 'layers', 0, 'admin.collections'],
        ['/coupons', 'Coupons', 'tag', 0, 'admin.coupons'],
        ['/reviews', 'Reviews', 'star', (int) ($badges['reviews'] ?? 0), 'admin.reviews'],
    ],
    'Audience' => [
        ['/messages', 'Messages', 'mail', 0, 'admin.messages'],
        ['/subscribers', 'Subscribers', 'user-plus', 0, 'admin.subscribers'],
    ],
    'Store' => [
        ['/team', 'Team', 'users', 0, 'admin.team'],
        ['/pages', 'Pages', 'file', 0, 'admin.pages'],
        ['/settings', 'Settings', 'settings', 0, 'admin.settings'],
        ['/tools', 'Tools', 'tool', 0, 'admin.tools'],
        ['/activity', 'Activity log', 'file', 0, 'admin.activity'],
    ],
]));
$homeUrl = team_home_url();
?>
<nav class="adm-side" aria-label="Sections">
  <a class="adm-side__brand" href="<?= e(url($homeUrl)) ?>" aria-label="Sky Fragrances admin home">
    <img src="<?= e(asset('img/brand/monogram-transparent-256.webp')) ?>" alt="" width="36" height="36" decoding="async">
    <span><span class="adm-side__brand-name">Sky Fragrances</span><span class="adm-side__brand-sub">Admin</span></span>
  </a>
<?php foreach ($groups as $group => $items): ?>
  <p class="adm-side__group"><?= e($group) ?></p>
<?php foreach ($items as [$path, $label, $icon, $count, $routeName]): ?>
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
