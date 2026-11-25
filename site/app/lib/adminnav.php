<?php
defined('SKYFR') || exit;

function admin_nav_groups(): array
{
    return [
        ['label' => 'Sales', 'tone' => 'c1', 'items' => [
            ['path' => '/admin', 'label' => 'Dashboard', 'icon' => 'grid', 'route' => 'admin.dashboard'],
            ['path' => '/admin/orders', 'label' => 'Orders', 'icon' => 'package', 'route' => 'admin.orders', 'count' => 'orders'],
            ['path' => '/admin/customers', 'label' => 'Customers', 'icon' => 'users', 'route' => 'admin.customers'],
            ['path' => '/admin/sales', 'label' => 'My sales', 'icon' => 'trend', 'route' => 'admin.sales', 'seller_only' => true],
        ]],
        ['label' => 'Money', 'tone' => 'c4', 'items' => [
            ['path' => '/admin/statements', 'label' => 'Statements', 'icon' => 'chart', 'route' => 'admin.statements'],
            ['path' => '/admin/expenses', 'label' => 'Expenses', 'icon' => 'wallet', 'route' => 'admin.expenses'],
        ]],
        ['label' => 'Catalogue', 'tone' => 'c3', 'items' => [
            ['path' => '/admin/products', 'label' => 'Products', 'icon' => 'droplet', 'route' => 'admin.products'],
            ['path' => '/admin/collections', 'label' => 'Collections', 'icon' => 'layers', 'route' => 'admin.collections'],
            ['path' => '/admin/scent-families', 'label' => 'Scent families', 'icon' => 'sparkle', 'route' => 'admin.scent_families', 'hidden' => true],
            ['path' => '/admin/coupons', 'label' => 'Coupons', 'icon' => 'tag', 'route' => 'admin.coupons'],
            ['path' => '/admin/reviews', 'label' => 'Reviews', 'icon' => 'star', 'route' => 'admin.reviews', 'count' => 'reviews'],
        ]],
        ['label' => 'Audience', 'tone' => 'c2', 'items' => [
            ['path' => '/admin/messages', 'label' => 'Messages', 'icon' => 'mail', 'route' => 'admin.messages'],
            ['path' => '/admin/subscribers', 'label' => 'Subscribers', 'icon' => 'user-plus', 'route' => 'admin.subscribers'],
        ]],
        ['label' => 'Store', 'tone' => 'c5', 'items' => [
            ['path' => '/admin/team', 'label' => 'Team', 'icon' => 'id-card', 'route' => 'admin.team'],
            ['path' => '/admin/pages', 'label' => 'Pages', 'icon' => 'file', 'route' => 'admin.pages'],
            ['path' => '/admin/settings', 'label' => 'Settings', 'icon' => 'settings', 'route' => 'admin.settings'],
            ['path' => '/admin/tools', 'label' => 'Tools', 'icon' => 'tool', 'route' => 'admin.tools', 'count' => 'failed_emails'],
            ['path' => '/admin/activity', 'label' => 'Activity log', 'icon' => 'activity', 'route' => 'admin.activity'],
        ]],
    ];
}

function admin_nav(?array $user): array
{
    $groups = [];
    foreach (admin_nav_groups() as $group) {
        $items = array_values(array_filter($group['items'], static fn (array $item): bool => empty($item['hidden']) && team_can_route($item['route'], $user) && (empty($item['seller_only']) || !team_is_owner($user))));
        if ($items !== []) {
            $groups[] = ['items' => $items] + $group;
        }
    }
    return $groups;
}

function admin_nav_matches(string $path, string $itemPath): bool
{
    return $itemPath === '/admin' ? $path === '/admin' : ($path === $itemPath || str_starts_with($path, $itemPath . '/'));
}

function admin_nav_find(string $path): ?array
{
    $best = null;
    foreach (admin_nav_groups() as $group) {
        foreach ($group['items'] as $item) {
            if (admin_nav_matches($path, $item['path']) && ($best === null || strlen($item['path']) > strlen($best['item']['path']))) {
                $best = ['group' => $group, 'item' => $item];
            }
        }
    }
    return $best;
}

function admin_breadcrumbs(string $path, string $title, string $back): array
{
    $match = admin_nav_find($path) ?? ($back !== '' ? admin_nav_find($back) : null);
    $crumbs = $match !== null ? [['label' => $match['group']['label'], 'href' => '']] : [];
    $parent = $back !== '' ? admin_nav_find($back) : null;
    if ($parent !== null && $parent['item']['label'] !== $title) {
        $crumbs[] = ['label' => $parent['item']['label'], 'href' => $back];
    } elseif ($parent === null && $match !== null && $match['item']['path'] !== $path && $match['item']['label'] !== $title) {
        $crumbs[] = ['label' => $match['item']['label'], 'href' => $match['item']['path']];
    }
    $crumbs[] = ['label' => $title, 'href' => ''];
    return $crumbs;
}

function admin_shell_counts(): array
{
    $counts = ['orders' => 0, 'reviews' => 0, 'failed_emails' => 0];
    try {
        $counts['orders'] = (int) db_fetch_column('SELECT COUNT(*) FROM orders');
        $counts['reviews'] = (int) db_fetch_column("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");
        $counts['failed_emails'] = (int) (mail_outbox_counts()['failed'] ?? 0);
    } catch (Throwable $e) {
        log_write('warning', 'Admin shell counts unavailable', ['error' => $e->getMessage()]);
    }
    return $counts;
}

function admin_role_label(array $user): string
{
    if (team_is_owner($user)) {
        return 'Super admin';
    }
    $title = trim((string) ($user['job_title'] ?? ''));
    return $title !== '' ? $title : (team_is_seller($user) ? 'Salesperson' : 'Team member');
}

function admin_palette(array $user): array
{
    $shortcuts = ['/admin' => 'd', '/admin/orders' => 'o', '/admin/customers' => 'c', '/admin/products' => 'p'];
    $jump = [];
    foreach (admin_nav($user) as $group) {
        foreach ($group['items'] as $item) {
            $jump[] = ['label' => $item['label'], 'href' => $item['path'], 'icon' => $item['icon'], 'keywords' => $group['label'], 'shortcut' => $shortcuts[$item['path']] ?? ''];
        }
    }
    $actions = array_values(array_filter([
        team_can('orders', $user) ? ['label' => 'New phone order', 'href' => '/admin/orders/new', 'icon' => 'plus', 'keywords' => 'create add sale', 'shortcut' => 'n'] : null,
        team_can('products', $user) ? ['label' => 'Add product', 'href' => '/admin/products/new', 'icon' => 'plus', 'keywords' => 'create perfume', 'shortcut' => ''] : null,
        team_can('expenses', $user) ? ['label' => 'Add expense', 'href' => '/admin/expenses/new', 'icon' => 'plus', 'keywords' => 'spend bill', 'shortcut' => ''] : null,
        team_can('coupons', $user) ? ['label' => 'New coupon', 'href' => '/admin/coupons/new', 'icon' => 'plus', 'keywords' => 'discount code', 'shortcut' => ''] : null,
        team_can('orders', $user) ? ['label' => 'Export orders CSV', 'href' => '/admin/orders/export.csv', 'icon' => 'download', 'keywords' => 'download spreadsheet', 'shortcut' => ''] : null,
    ]));
    $orders = [];
    if (team_can('orders', $user)) {
        foreach (db_fetch_all('SELECT order_number, customer_name, customer_phone, grand_total, payment_status FROM orders ORDER BY created_at DESC, id DESC LIMIT 6') as $row) {
            $orders[] = ['label' => $row['order_number'] . ' · ' . $row['customer_name'] . ' · ' . money((string) $row['grand_total']), 'href' => '/admin/orders/' . $row['order_number'] . '/view', 'icon' => 'package', 'keywords' => $row['customer_phone'] . ' ' . $row['payment_status'], 'shortcut' => ''];
        }
    }
    $search = array_values(array_filter([
        team_can('orders', $user) ? ['label' => 'Search orders for', 'href' => '/admin/orders?q=', 'icon' => 'search'] : null,
        team_can('customers', $user) ? ['label' => 'Search customers for', 'href' => '/admin/customers?q=', 'icon' => 'search'] : null,
        team_can('products', $user) ? ['label' => 'Search products for', 'href' => '/admin/products?q=', 'icon' => 'search'] : null,
    ]));
    return ['Jump to' => $jump, 'Actions' => $actions, 'Latest orders' => $orders, 'search' => $search];
}
