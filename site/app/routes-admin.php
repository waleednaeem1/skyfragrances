<?php
defined('SKYFR') || exit;

$r = static fn (string $name, string $method, string $path, string $controller, ?string $view, string $bodyClass, array $where = []): array => [
    'name' => $name,
    'method' => $method,
    'path' => $path,
    'controller' => $controller,
    'view' => $view,
    'where' => $where,
    'preset' => [],
    'robots' => 'noindex, nofollow',
    'body_class' => 'admin t-light ' . $bodyClass,
];

$o = ['order' => 'order'];
$n = ['id' => 'num'];

return [
    $r('admin.login', 'GET|POST', '/login', 'auth.php', 'login.php', 'admin-login'),
    $r('admin.logout', 'GET|POST', '/logout', 'auth.php', 'login.php', 'admin-login'),
    $r('admin.dashboard', 'GET', '/', 'dashboard.php', 'dashboard.php', 'admin-dashboard'),
    $r('admin.password', 'GET|POST', '/password', 'password.php', 'password.php', 'admin-password'),

    $r('admin.orders', 'GET', '/orders', 'orders.php', 'orders.php', 'admin-orders'),
    $r('admin.orders.export', 'GET', '/orders/export.csv', 'export.php', null, 'admin-orders'),
    $r('admin.orders.bulk', 'POST', '/orders/bulk', 'orders.php', null, 'admin-orders'),
    $r('admin.orders.cancel_unpaid', 'GET|POST', '/orders/cancel-unpaid', 'orders.php', 'orders-cancel-unpaid.php', 'admin-orders'),
    $r('admin.orders.show', 'GET', '/orders/{order}', 'order-detail.php', 'order-detail.php', 'admin-order', $o),
    $r('admin.orders.status', 'POST', '/orders/{order}/status', 'order-detail.php', null, 'admin-order', $o),
    $r('admin.orders.payment', 'POST', '/orders/{order}/payment', 'order-detail.php', null, 'admin-order', $o),
    $r('admin.orders.shipping', 'POST', '/orders/{order}/shipping', 'order-detail.php', null, 'admin-order', $o),
    $r('admin.orders.notes', 'POST', '/orders/{order}/notes', 'order-detail.php', null, 'admin-order', $o),
    $r('admin.orders.restore_stock', 'POST', '/orders/{order}/restore-stock', 'order-detail.php', null, 'admin-order', $o),
    $r('admin.orders.stock_back', 'POST', '/orders/{order}/stock-back', 'order-detail.php', null, 'admin-order', $o),
    $r('admin.orders.proof', 'GET', '/orders/{order}/proof', 'order-detail.php', null, 'admin-order', $o),
    $r('admin.orders.invoice', 'GET', '/orders/{order}/invoice', 'order-print.php', 'order-invoice.php', 'admin-print', $o),
    $r('admin.orders.packing_slip', 'GET', '/orders/{order}/packing-slip', 'order-print.php', 'order-packing-slip.php', 'admin-print', $o),

    $r('admin.products', 'GET', '/products', 'products.php', 'products.php', 'admin-products'),
    $r('admin.products.bulk', 'POST', '/products/bulk', 'products.php', null, 'admin-products'),
    $r('admin.products.new', 'GET|POST', '/products/new', 'product-form.php', 'product-form.php', 'admin-product-form'),
    $r('admin.products.edit', 'GET|POST', '/products/{id}', 'product-form.php', 'product-form.php', 'admin-product-form', $n),
    $r('admin.products.delete', 'POST', '/products/{id}/delete', 'products.php', null, 'admin-products', $n),
    $r('admin.products.duplicate', 'POST', '/products/{id}/duplicate', 'products.php', null, 'admin-products', $n),
    $r('admin.products.toggle', 'POST', '/products/{id}/toggle', 'products.php', null, 'admin-products', $n),
    $r('admin.products.images', 'POST', '/products/{id}/images', 'api/images.php', null, 'admin-product-form', $n),
    $r('admin.products.images.order', 'POST', '/products/{id}/images/order', 'api/images.php', null, 'admin-product-form', $n),
    $r('admin.products.images.primary', 'POST', '/products/{id}/images/{img}/primary', 'api/images.php', null, 'admin-product-form', ['id' => 'num', 'img' => 'num']),
    $r('admin.products.images.delete', 'POST', '/products/{id}/images/{img}/delete', 'api/images.php', null, 'admin-product-form', ['id' => 'num', 'img' => 'num']),

    $r('admin.collections', 'GET', '/collections', 'collections.php', 'collections.php', 'admin-collections'),
    $r('admin.collections.new', 'GET|POST', '/collections/new', 'collection-form.php', 'collection-form.php', 'admin-collection-form'),
    $r('admin.collections.edit', 'GET|POST', '/collections/{id}', 'collection-form.php', 'collection-form.php', 'admin-collection-form', $n),
    $r('admin.collections.delete', 'POST', '/collections/{id}/delete', 'collections.php', null, 'admin-collections', $n),

    $r('admin.scent_families', 'GET', '/scent-families', 'scent-families.php', 'scent-families.php', 'admin-scent-families'),
    $r('admin.scent_families.new', 'GET|POST', '/scent-families/new', 'scent-families.php', 'scent-family-form.php', 'admin-scent-family-form'),
    $r('admin.scent_families.edit', 'GET|POST', '/scent-families/{id}', 'scent-families.php', 'scent-family-form.php', 'admin-scent-family-form', $n),
    $r('admin.scent_families.toggle', 'POST', '/scent-families/{id}/toggle', 'scent-families.php', null, 'admin-scent-families', $n),
    $r('admin.coupons', 'GET', '/coupons', 'coupons.php', 'coupons.php', 'admin-coupons'),
    $r('admin.coupons.new', 'GET|POST', '/coupons/new', 'coupon-form.php', 'coupon-form.php', 'admin-coupon-form'),
    $r('admin.coupons.edit', 'GET|POST', '/coupons/{id}', 'coupon-form.php', 'coupon-form.php', 'admin-coupon-form', $n),
    $r('admin.coupons.toggle', 'POST', '/coupons/{id}/toggle', 'coupons.php', null, 'admin-coupons', $n),

    $r('admin.reviews', 'GET', '/reviews', 'reviews.php', 'reviews.php', 'admin-reviews'),
    $r('admin.reviews.bulk', 'POST', '/reviews/bulk', 'reviews.php', null, 'admin-reviews'),
    $r('admin.reviews.status', 'POST', '/reviews/{id}/status', 'reviews.php', null, 'admin-reviews', $n),

    $r('admin.messages', 'GET', '/messages', 'messages.php', 'messages.php', 'admin-messages'),
    $r('admin.messages.show', 'GET', '/messages/{id}', 'messages.php', 'message-detail.php', 'admin-messages', $n),
    $r('admin.messages.status', 'POST', '/messages/{id}/status', 'messages.php', null, 'admin-messages', $n),

    $r('admin.subscribers', 'GET', '/subscribers', 'subscribers.php', 'subscribers.php', 'admin-subscribers'),
    $r('admin.subscribers.export', 'GET', '/subscribers/export.csv', 'export.php', null, 'admin-subscribers'),
    $r('admin.subscribers.status', 'POST', '/subscribers/{id}/status', 'subscribers.php', null, 'admin-subscribers', $n),

    $r('admin.pages', 'GET', '/pages', 'pages.php', 'pages.php', 'admin-pages'),
    $r('admin.pages.edit', 'GET|POST', '/pages/{slug}', 'pages.php', 'page-form.php', 'admin-page-form', ['slug' => 'slug']),

    $r('admin.settings', 'GET|POST', '/settings', 'settings.php', 'settings.php', 'admin-settings'),
    $r('admin.settings.https_permanent', 'POST', '/settings/https-permanent', 'settings.php', null, 'admin-settings'),

    $r('admin.tools', 'GET', '/tools', 'tools.php', 'tools.php', 'admin-tools'),
    $r('admin.tools.remove_sample_data', 'GET|POST', '/tools/remove-sample-data', 'tools.php', 'tools-confirm.php', 'admin-tools'),
    $r('admin.tools.purge', 'GET|POST', '/tools/purge', 'tools.php', 'tools-confirm.php', 'admin-tools'),
    $r('admin.tools.regenerate_images', 'GET|POST', '/tools/regenerate-images', 'tools.php', 'tools-progress.php', 'admin-tools'),

    $r('admin.activity', 'GET', '/activity', 'activity.php', 'activity.php', 'admin-activity'),

    ['name' => 'admin.notfound', 'method' => 'GET|POST', 'path' => '*', 'controller' => 'notfound.php', 'view' => '404.php', 'where' => [], 'preset' => [], 'robots' => 'noindex, nofollow', 'body_class' => 'admin t-light admin-notfound'],
];
