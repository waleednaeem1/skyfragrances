<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/order-actions.php';

const CUSTOMERS_SORTS = ['recent' => 'Latest order', 'orders' => 'Most orders', 'spent' => 'Highest spend', 'owed' => 'Most to collect'];
const CUSTOMERS_SHOWS = ['' => 'All customers', 'repeat' => 'Repeat customers (2+ orders)', 'owing' => 'Payment to collect'];
const CUSTOMERS_SEPARATOR = '|~|';

function customers_key(string $phoneNormalized): string
{
    return ltrim($phoneNormalized, '+');
}

function customers_url(string $phoneNormalized): string
{
    return '/admin/customers/' . customers_key($phoneNormalized);
}

function customers_sums_sql(): string
{
    return "COUNT(*) AS orders_all,
        SUM(status <> 'cancelled') AS orders,
        SUM(status = 'cancelled') AS cancelled,
        COALESCE(SUM(CASE WHEN status <> 'cancelled' AND payment_status <> 'refunded' THEN grand_total END), 0) AS ordered,
        COALESCE(SUM(amount_paid), 0) AS collected,
        COALESCE(SUM(CASE WHEN status <> 'cancelled' AND payment_status <> 'refunded' AND grand_total > amount_paid THEN grand_total - amount_paid END), 0) AS to_collect,
        COALESCE(SUM(CASE WHEN status = 'cancelled' THEN amount_paid END), 0) AS refund_due";
}

function customers_latest_sql(string $column): string
{
    return 'SUBSTRING_INDEX(GROUP_CONCAT(' . $column . " ORDER BY created_at DESC, id DESC SEPARATOR '" . CUSTOMERS_SEPARATOR . "'), '" . CUSTOMERS_SEPARATOR . "', 1)";
}

function customers_filters(): array
{
    $show = (string) request_query('show', '');
    $sort = (string) request_query('sort', 'recent');
    return [
        'q' => mb_substr(trim((string) request_query('q', '')), 0, 60),
        'show' => isset(CUSTOMERS_SHOWS[$show]) ? $show : '',
        'sort' => isset(CUSTOMERS_SORTS[$sort]) ? $sort : 'recent',
    ];
}

function customers_query(array $filters, DateTimeImmutable $now): array
{
    $bind = ['month' => $now->format('Y-m-01 00:00:00'), 'year' => $now->format('Y-01-01 00:00:00')];
    $where = '';
    if ($filters['q'] !== '') {
        $match = ['customer_name LIKE :name'];
        $bind['name'] = '%' . addcslashes($filters['q'], '%_\\') . '%';
        $digits = ltrim((string) preg_replace('/\D+/', '', $filters['q']), '0');
        if (strlen($digits) >= 4) {
            $match[] = 'phone_normalized LIKE :phone';
            $bind['phone'] = '%' . $digits . '%';
        }
        $where = ' WHERE phone_normalized IN (SELECT phone_normalized FROM orders WHERE ' . implode(' OR ', $match) . ')';
    }
    $having = match ($filters['show']) {
        'repeat' => ' HAVING orders >= 2',
        'owing' => ' HAVING to_collect > 0',
        default => '',
    };
    $order = match ($filters['sort']) {
        'orders' => 'orders DESC, last_order DESC',
        'spent' => 'ordered DESC, last_order DESC',
        'owed' => 'to_collect DESC, last_order DESC',
        default => 'last_order DESC',
    };
    $sql = 'SELECT phone_normalized, ' . customers_latest_sql('customer_name') . ' AS name, ' . customers_latest_sql('customer_phone') . ' AS phone, ' . customers_latest_sql('city') . ' AS city, '
        . customers_sums_sql() . ",
        SUM(status <> 'cancelled' AND created_at >= :month) AS orders_month,
        SUM(status <> 'cancelled' AND created_at >= :year) AS orders_year,
        MIN(created_at) AS first_order, MAX(created_at) AS last_order
        FROM orders" . $where . ' GROUP BY phone_normalized' . $having;
    return ['sql' => $sql, 'order' => $order, 'bind' => $bind];
}

function customers_period_totals(array $orders, string $from): array
{
    $totals = ['orders' => 0, 'ordered' => 0, 'collected' => 0, 'to_collect' => 0];
    foreach ($orders as $order) {
        if ((string) $order['created_at'] < $from) {
            continue;
        }
        $balance = order_balance($order);
        $cancelled = $order['status'] === 'cancelled';
        $totals['orders'] += $cancelled ? 0 : 1;
        $totals['ordered'] += $cancelled || $order['payment_status'] === 'refunded' ? 0 : $balance['total'];
        $totals['collected'] += $balance['paid'];
        $totals['to_collect'] += $balance['due'];
    }
    return $totals;
}

function customers_ledger(array $orders, array $paymentsByOrder): array
{
    $entries = [];
    foreach ($orders as $order) {
        $amount = money_paisa((string) $order['grand_total']);
        $counts = $order['status'] !== 'cancelled' && $order['payment_status'] !== 'refunded';
        $entries[] = ['at' => (string) $order['created_at'], 'kind' => 'order', 'order' => $order, 'charge' => $counts ? $amount : 0, 'received' => 0, 'void' => !$counts ? $amount : 0];
        $received = 0;
        foreach ($paymentsByOrder[(int) $order['id']] ?? [] as $payment) {
            $received += money_paisa((string) $payment['amount']);
            $entries[] = ['at' => (string) $payment['paid_at'], 'kind' => 'payment', 'order' => $order, 'charge' => 0, 'received' => money_paisa((string) $payment['amount']), 'void' => 0];
        }
        if ($order['payment_status'] === 'refunded' && $received > 0) {
            $entries[] = ['at' => (string) ($order['updated_at'] ?? $order['created_at']), 'kind' => 'refund', 'order' => $order, 'charge' => $received, 'received' => 0, 'void' => 0];
        }
    }
    usort($entries, static fn (array $a, array $b): int => [$a['at'], $a['kind'] === 'order' ? 0 : 1] <=> [$b['at'], $b['kind'] === 'order' ? 0 : 1]);
    $balance = 0;
    foreach ($entries as &$entry) {
        $balance += $entry['charge'] - $entry['received'];
        $entry['balance'] = $balance;
    }
    unset($entry);
    return $entries;
}

$karachi = new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi'));
$routeName = (string) ($route['name'] ?? 'admin.customers');

if ($routeName === 'admin.customers.show') {
    $key = (string) $params['phone'];
    $orders = preg_match('/^[0-9]{8,15}$/', $key) === 1
        ? db_fetch_all('SELECT id, order_number, status, payment_method, payment_status, customer_name, customer_phone, phone_normalized, customer_email, city, address, grand_total, amount_paid, item_count, source, paid_at, created_at, updated_at FROM orders WHERE phone_normalized = :p ORDER BY created_at ASC, id ASC', ['p' => '+' . $key])
        : [];
    if ($orders === []) {
        flash('error', 'No orders were found for that customer.');
        redirect('/admin/customers', 303);
    }
    $latest = $orders[count($orders) - 1];
    $paymentsByOrder = [];
    foreach (db_fetch_all('SELECT p.order_id, p.amount, p.paid_at FROM order_payments p JOIN orders o ON o.id = p.order_id WHERE o.phone_normalized = :p AND p.voided_at IS NULL ORDER BY p.paid_at ASC, p.id ASC', ['p' => '+' . $key]) as $payment) {
        $paymentsByOrder[(int) $payment['order_id']][] = $payment;
    }
    $distinct = static fn (string $column): array => array_values(array_unique(array_filter(array_map(static fn (array $o): string => trim((string) $o[$column]), array_reverse($orders)), 'strlen')));
    render_admin('customer.php', [
        'customer' => [
            'name' => (string) $latest['customer_name'],
            'phone' => (string) $latest['customer_phone'],
            'phone_normalized' => '+' . $key,
            'email' => (string) ($latest['customer_email'] ?? ''),
            'city' => (string) $latest['city'],
            'first_order' => (string) $orders[0]['created_at'],
            'names' => $distinct('customer_name'),
            'emails' => $distinct('customer_email'),
            'addresses' => array_slice(array_values(array_unique(array_map(static fn (array $o): string => trim($o['address'] . ', ' . $o['city'], ', '), array_reverse($orders)))), 0, 4),
        ],
        'periods' => [
            ['label' => 'This month', 'totals' => customers_period_totals($orders, $karachi->format('Y-m-01 00:00:00'))],
            ['label' => 'This year', 'totals' => customers_period_totals($orders, $karachi->format('Y-01-01 00:00:00'))],
            ['label' => 'All time', 'totals' => customers_period_totals($orders, '0000-00-00 00:00:00')],
        ],
        'cancelled' => count(array_filter($orders, static fn (array $o): bool => $o['status'] === 'cancelled')),
        'refundDue' => array_sum(array_map(static fn (array $o): int => $o['status'] === 'cancelled' ? money_paisa((string) $o['amount_paid']) : 0, $orders)),
        'ledger' => customers_ledger($orders, $paymentsByOrder),
        'whatsapp' => adm_order_whatsapp_link($latest, 'Assalam-o-Alaikum ' . adm_order_first_name($latest) . ','),
    ], ['title' => (string) $latest['customer_name'], 'body_class' => (string) $route['body_class'], 'back' => '/admin/customers']);
}

$filters = customers_filters();
$built = customers_query($filters, $karachi);
$per = max(5, min(100, setting_int('admin_rows_per_page', 20)));
$total = (int) db_fetch_column('SELECT COUNT(*) FROM (' . $built['sql'] . ') customers', $built['bind']);
$page = min(max(1, (int) request_query('page', 1)), max(1, (int) ceil($total / $per)));
$customers = db_fetch_all($built['sql'] . ' ORDER BY ' . $built['order'] . ' LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $built['bind']);
$overview = db_fetch('SELECT COUNT(*) AS customers, COALESCE(SUM(orders >= 2), 0) AS repeat_customers, COALESCE(SUM(to_collect), 0) AS to_collect FROM (' . customers_query(['q' => '', 'show' => '', 'sort' => 'recent'], $karachi)['sql'] . ') c', ['month' => $built['bind']['month'], 'year' => $built['bind']['year']]);
$query = array_filter(['q' => $filters['q'], 'show' => $filters['show'], 'sort' => $filters['sort'] === 'recent' ? '' : $filters['sort']], 'strlen');

render_admin('customers.php', [
    'customers' => $customers,
    'filters' => $filters,
    'query' => $query,
    'total' => $total,
    'page' => $page,
    'per' => $per,
    'overview' => $overview,
], ['title' => 'Customers', 'body_class' => (string) $route['body_class']]);
