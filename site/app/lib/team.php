<?php
defined('SKYFR') || exit;

const TEAM_MODULES = [
    'dashboard' => ['Dashboard', 'Sales overview and charts', '/admin'],
    'orders' => ['Orders', 'See, add and update orders', '/admin/orders'],
    'customers' => ['Customers', 'Customer list and ledgers', '/admin/customers'],
    'statements' => ['Statements', 'Profit, product costs and Excel/PDF statements', '/admin/statements'],
    'expenses' => ['Expenses', 'Money spent, money in and bills', '/admin/expenses'],
    'products' => ['Products', 'Products, collections, stock and scent families', '/admin/products'],
    'coupons' => ['Coupons', 'Discount codes and the thank-you coupon', '/admin/coupons'],
    'reviews' => ['Reviews', 'Approve and hide reviews', '/admin/reviews'],
    'messages' => ['Messages', 'Contact form messages', '/admin/messages'],
    'subscribers' => ['Subscribers', 'Newsletter list and export', '/admin/subscribers'],
    'pages' => ['Pages', 'Edit website pages', '/admin/pages'],
    'settings' => ['Settings', 'Store settings, payments and delivery', '/admin/settings'],
    'tools' => ['Tools', 'Email test and maintenance', '/admin/tools'],
    'activity' => ['Activity log', 'Who changed what', '/admin/activity'],
];

const TEAM_ROUTE_ACCESS = [
    'admin.login' => '',
    'admin.logout' => '',
    'admin.password' => '',
    'admin.notfound' => '',
    'admin.dashboard' => 'dashboard',
    'admin.orders' => 'orders',
    'admin.customers' => 'customers',
    'admin.statements' => 'statements',
    'admin.expenses' => 'expenses',
    'admin.products' => 'products',
    'admin.collections' => 'products',
    'admin.scent_families' => 'products',
    'admin.coupons' => 'coupons',
    'admin.reviews' => 'reviews',
    'admin.messages' => 'messages',
    'admin.subscribers' => 'subscribers',
    'admin.pages' => 'pages',
    'admin.settings' => 'settings',
    'admin.tools' => 'tools',
    'admin.activity' => 'activity',
    'admin.sales' => 'seller',
    'admin.team' => 'owner',
];

function team_is_owner(?array $user = null): bool
{
    $user ??= auth_user();
    return $user !== null && (string) ($user['role'] ?? 'owner') === 'owner';
}

function team_is_seller(?array $user = null): bool
{
    $user ??= auth_user();
    return $user !== null && $user['commission_rate'] !== null && $user['commission_rate'] !== '';
}

function team_permissions_decode(?string $json): array
{
    $list = json_decode((string) $json, true);
    return is_array($list) ? array_values(array_intersect(array_keys(TEAM_MODULES), $list)) : [];
}

function team_can(string $module, ?array $user = null): bool
{
    $user ??= auth_user();
    if ($user === null) {
        return false;
    }
    if (team_is_owner($user)) {
        return true;
    }
    return in_array($module, team_permissions_decode($user['permissions'] ?? null), true);
}

function team_route_access(string $routeName): string
{
    foreach (TEAM_ROUTE_ACCESS as $prefix => $access) {
        if ($routeName === $prefix || str_starts_with($routeName, $prefix . '.')) {
            return $access;
        }
    }
    return 'owner';
}

function team_can_route(string $routeName, ?array $user = null): bool
{
    $user ??= auth_user();
    $access = team_route_access($routeName);
    return match ($access) {
        '' => true,
        'owner' => team_is_owner($user),
        'seller' => team_is_owner($user) || team_is_seller($user),
        default => team_can($access, $user),
    };
}

function team_home_url(?array $user = null): string
{
    $user ??= auth_user();
    foreach (TEAM_MODULES as $key => [, , $url]) {
        if (team_can($key, $user)) {
            return $url;
        }
    }
    return team_is_seller($user) ? '/admin/sales' : '/admin/password';
}

function team_rate_basis(?string $rate): int
{
    return $rate === null || $rate === '' ? 0 : (int) round(((float) $rate) * 100);
}

function team_share_of(int $profitPaisa, ?string $rate): int
{
    if ($profitPaisa <= 0) {
        return 0;
    }
    $share = intdiv($profitPaisa * team_rate_basis($rate) + 5000, 10000);
    return intdiv($share + 50, 100) * 100;
}

function team_commission(array $row): array
{
    $net = money_paisa((string) $row['subtotal']) - money_paisa((string) $row['discount_total']);
    $cogs = money_paisa((string) $row['cogs']);
    $profit = $net - $cogs;
    $calculated = team_share_of($profit, $row['commission_rate'] === null ? null : (string) $row['commission_rate']);
    $share = $row['commission_override'] !== null ? money_paisa((string) $row['commission_override']) : $calculated;
    $paid = $row['commission_paid_at'] !== null;
    if ($paid) {
        $share = money_paisa((string) $row['commission_paid_amount']);
    }
    $void = in_array((string) $row['status'], ['cancelled'], true) || (string) $row['payment_status'] === 'refunded';
    $state = match (true) {
        $paid => 'paid',
        $void => 'void',
        (string) $row['status'] === 'delivered' && (string) $row['payment_status'] === 'paid' => 'ready',
        default => 'waiting',
    };
    return [
        'net' => $net,
        'cogs' => $cogs,
        'profit' => $profit,
        'rate' => $row['commission_rate'] === null ? null : (string) $row['commission_rate'],
        'calculated' => $calculated,
        'override' => $row['commission_override'] !== null,
        'share' => $state === 'void' ? 0 : $share,
        'state' => $state,
        'uncosted' => (int) ($row['uncosted'] ?? 0) > 0,
        'paid_warning' => $paid && $void,
    ];
}

function team_state_badge(string $state): array
{
    return match ($state) {
        'paid' => ['label' => 'Paid', 'tone' => 'green'],
        'ready' => ['label' => 'Ready to pay', 'tone' => 'gold'],
        'void' => ['label' => 'No share, order cancelled', 'tone' => 'muted'],
        default => ['label' => 'Waiting for delivery and payment', 'tone' => 'neutral'],
    };
}

function team_commission_sql(string $where, string $extraSelect = ''): string
{
    return "SELECT " . ($extraSelect !== '' ? $extraSelect . ', ' : '') . "o.id, o.order_number, o.created_at, o.status, o.payment_status, o.customer_name, o.customer_phone, o.subtotal, o.discount_total, o.grand_total,
            o.sales_member_id, o.commission_rate, o.commission_override, o.commission_note, o.commission_paid_at, o.commission_paid_amount, o.commission_paid_by,
            u.display_name AS member_name,
            (SELECT COALESCE(SUM(oi.quantity * COALESCE(oi.unit_cost, ps.cost_price, 0)), 0) FROM order_items oi LEFT JOIN product_sizes ps ON ps.id = oi.product_size_id WHERE oi.order_id = o.id) AS cogs,
            (SELECT COUNT(*) FROM order_items oi LEFT JOIN product_sizes ps ON ps.id = oi.product_size_id WHERE oi.order_id = o.id AND oi.unit_cost IS NULL AND ps.cost_price IS NULL) AS uncosted
        FROM orders o LEFT JOIN admin_users u ON u.id = o.sales_member_id
        WHERE o.sales_member_id IS NOT NULL AND " . $where;
}

function team_commission_rows(string $where, array $params, string $order = 'o.created_at DESC, o.id DESC', string $extraSelect = ''): array
{
    $rows = db_fetch_all(team_commission_sql($where, $extraSelect) . ' ORDER BY ' . $order, $params);
    foreach ($rows as &$row) {
        $row['commission'] = team_commission($row);
    }
    unset($row);
    return $rows;
}

function team_commission_for_order(int $orderId): ?array
{
    $rows = team_commission_rows('o.id = :id', ['id' => $orderId]);
    return $rows[0] ?? null;
}

function team_sellers(): array
{
    return db_fetch_all('SELECT id, display_name, commission_rate, is_active FROM admin_users WHERE commission_rate IS NOT NULL ORDER BY is_active DESC, display_name ASC');
}

function team_new_order_member(?array $user, int $chosenId): ?int
{
    if ($user === null) {
        return null;
    }
    if (!team_is_owner($user)) {
        return team_is_seller($user) ? (int) $user['id'] : null;
    }
    if ($chosenId < 1) {
        return null;
    }
    return db_exists('SELECT 1 FROM admin_users WHERE id = :id AND commission_rate IS NOT NULL AND is_active = 1', ['id' => $chosenId]) ? $chosenId : null;
}

function team_assign_order(int $orderId, ?int $memberId, string $now): void
{
    $rate = null;
    if ($memberId !== null) {
        $rate = db_fetch_column('SELECT commission_rate FROM admin_users WHERE id = :id AND commission_rate IS NOT NULL', ['id' => $memberId]);
        if ($rate === null || $rate === false) {
            $memberId = null;
            $rate = null;
        }
    }
    db_update('orders', [
        'sales_member_id' => $memberId,
        'commission_rate' => $rate,
        'commission_override' => null,
        'commission_note' => null,
        'updated_at' => $now,
    ], ['id' => $orderId]);
}
