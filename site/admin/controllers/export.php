<?php
defined('SKYFR') || exit;

$exportRoute = (string) ($route['name'] ?? '');

if ($exportRoute === 'admin.subscribers.export') {
    require __DIR__ . '/subscribers.php';
}

if ($exportRoute === 'admin.orders.export' && is_file(__DIR__ . '/orders-export.php')) {
    require __DIR__ . '/orders-export.php';
}

log_write('error', 'Admin export route has no handler', ['route' => $exportRoute]);
abort(404);
