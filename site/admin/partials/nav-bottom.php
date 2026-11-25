<?php
defined('SKYFR') || exit;
$ordersCount = (int) ($badges['orders'] ?? 0);
$reviewsCount = (int) ($badges['reviews'] ?? 0);
$more = array_values(array_filter([
    ['/admin', 'Dashboard', 'admin.dashboard'],
    ['/admin/customers', 'Customers', 'admin.customers'],
    ['/admin/sales', 'My sales', team_is_owner() ? '' : 'admin.sales'],
    ['/admin/statements', 'Statements', 'admin.statements'],
    ['/admin/expenses', 'Expenses', 'admin.expenses'],
    ['/admin/collections', 'Collections', 'admin.collections'],
    ['/admin/coupons', 'Coupons', 'admin.coupons'],
    ['/admin/messages', 'Messages', 'admin.messages'],
    ['/admin/subscribers', 'Subscribers', 'admin.subscribers'],
    ['/admin/team', 'Team', 'admin.team'],
    ['/admin/pages', 'Pages', 'admin.pages'],
    ['/admin/settings', 'Settings', 'admin.settings'],
    ['/admin/tools', 'Tools', 'admin.tools'],
    ['/admin/activity', 'Activity log', 'admin.activity'],
    ['/admin/password', 'Change password', 'admin.password'],
], static fn (array $item): bool => $item[2] !== '' && team_can_route($item[2])));
$canOrders = team_can('orders');
$canProducts = team_can('products');
$canReviews = team_can('reviews');
$canCreate = $canOrders || $canProducts || team_can('coupons');
?>
<nav class="adm-nav" aria-label="Quick navigation" data-bottom-nav>
<?php if ($canOrders): ?>
  <a class="adm-nav__item<?= $isActive('/orders') ? ' is-active' : '' ?>" href="<?= e(url('/admin/orders')) ?>"<?= $isActive('/orders') ? ' aria-current="page"' : '' ?>>
    <span class="adm-nav__label">Orders</span><?php if ($ordersCount > 0): ?><span class="adm-nav__badge"><?= e((string) min($ordersCount, 99)) ?></span><?php endif; ?>
  </a>
<?php endif; ?>
<?php if ($canProducts): ?>
  <a class="adm-nav__item<?= $isActive('/products') ? ' is-active' : '' ?>" href="<?= e(url('/admin/products')) ?>"<?= $isActive('/products') ? ' aria-current="page"' : '' ?>><span class="adm-nav__label">Products</span></a>
<?php endif; ?>
<?php if ($canCreate): ?>
  <a class="adm-nav__item adm-nav__plus" href="<?= e(url($createUrl)) ?>" aria-label="Create new"><span class="adm-nav__plus-disc" aria-hidden="true">+</span></a>
<?php endif; ?>
<?php if ($canReviews): ?>
  <a class="adm-nav__item<?= $isActive('/reviews') ? ' is-active' : '' ?>" href="<?= e(url('/admin/reviews')) ?>"<?= $isActive('/reviews') ? ' aria-current="page"' : '' ?>>
    <span class="adm-nav__label">Reviews</span><?php if ($reviewsCount > 0): ?><span class="adm-nav__badge"><?= e((string) min($reviewsCount, 99)) ?></span><?php endif; ?>
  </a>
<?php endif; ?>
  <button type="button" class="adm-nav__item" data-sheet-open="more-sheet" aria-controls="more-sheet" aria-expanded="false"><span class="adm-nav__label">More</span></button>
</nav>
<div class="adm-sheet" id="more-sheet" hidden data-sheet>
  <div class="adm-sheet__panel" role="dialog" aria-modal="true" aria-label="More sections" tabindex="-1">
    <div class="adm-sheet__handle" aria-hidden="true"></div>
<?php foreach ($more as [$href, $label]): ?>
    <a class="adm-sheet__item" href="<?= e(url($href)) ?>"><?= e($label) ?></a>
<?php endforeach; ?>
    <form method="post" action="<?= e(url('/admin/logout')) ?>"><?= csrf_field() ?><button type="submit" class="adm-sheet__item adm-sheet__item--danger">Log out</button></form>
    <button type="button" class="adm-sheet__item adm-sheet__item--close" data-sheet-close>Close</button>
  </div>
</div>
