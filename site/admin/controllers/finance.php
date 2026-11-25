<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/dashboard-period.php';
require_once __DIR__ . '/order-actions.php';
require_once __DIR__ . '/catalogue.php';

const FINANCE_EXPENSE_CATEGORIES = [
    'stock' => 'Perfume oils & raw materials',
    'packaging' => 'Bottles, boxes, labels & packaging',
    'courier' => 'Courier & delivery',
    'ads' => 'Online ads',
    'influencers' => 'Influencers & collaborations',
    'content' => 'Photography & content',
    'samples' => 'Samples, testers & gifts',
    'salaries' => 'Salaries & wages',
    'rent' => 'Rent & utilities',
    'software' => 'Website, hosting & software',
    'fees' => 'Bank & payment charges',
    'equipment' => 'Equipment & tools',
    'tax' => 'Taxes & government fees',
    'travel' => 'Transport & travel',
    'other' => 'Other expenses',
];
const FINANCE_STOCK_CATEGORIES = ['stock', 'packaging'];
const FINANCE_INCOME_CATEGORIES = [
    'wholesale' => 'Wholesale & bulk sales',
    'investment' => 'Investment & capital',
    'refund' => 'Refunds received',
    'other_income' => 'Other income',
];
const FINANCE_PAID_FROM = ['cash' => 'Cash', 'bank' => 'Bank transfer', 'jazzcash' => 'JazzCash', 'easypaisa' => 'Easypaisa', 'card' => 'Card', 'other' => 'Other'];

function finance_category_label(string $type, string $category): string
{
    return ($type === 'income' ? FINANCE_INCOME_CATEGORIES : FINANCE_EXPENSE_CATEGORIES)[$category] ?? ucfirst(str_replace('_', ' ', $category));
}

function finance_window(DateTimeImmutable $from, DateTimeImmutable $to): array
{
    return ['from' => $from->format('Y-m-d H:i:s'), 'to' => $to->format('Y-m-d H:i:s')];
}

function finance_dates(DateTimeImmutable $from, DateTimeImmutable $to): array
{
    return ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
}

function finance_sales(DateTimeImmutable $from, DateTimeImmutable $to): array
{
    $window = finance_window($from, $to);
    $orders = db_fetch(
        'SELECT COUNT(*) AS orders, COALESCE(SUM(item_count), 0) AS bottles, COALESCE(SUM(subtotal), 0) AS gross, COALESCE(SUM(discount_total), 0) AS discounts,
                COALESCE(SUM(shipping_fee + cod_fee), 0) AS delivery, COALESCE(SUM(grand_total), 0) AS revenue
         FROM orders WHERE status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND created_at >= :from AND created_at < :to',
        $window
    );
    $cost = db_fetch(
        'SELECT COALESCE(SUM(oi.quantity * COALESCE(oi.unit_cost, ps.cost_price)), 0) AS cogs,
                COALESCE(SUM(CASE WHEN COALESCE(oi.unit_cost, ps.cost_price) IS NULL THEN oi.quantity ELSE 0 END), 0) AS uncosted,
                COALESCE(SUM(CASE WHEN oi.unit_cost IS NULL AND ps.cost_price IS NOT NULL THEN oi.quantity ELSE 0 END), 0) AS estimated
         FROM order_items oi JOIN orders o ON o.id = oi.order_id LEFT JOIN product_sizes ps ON ps.id = oi.product_size_id
         WHERE o.status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND o.created_at >= :from AND o.created_at < :to',
        $window
    );
    $net = money_paisa((string) $orders['gross']) - money_paisa((string) $orders['discounts']);
    $cogs = money_paisa((string) $cost['cogs']);
    return [
        'orders' => (int) $orders['orders'],
        'bottles' => (int) $orders['bottles'],
        'gross' => money_paisa((string) $orders['gross']),
        'discounts' => money_paisa((string) $orders['discounts']),
        'net' => $net,
        'delivery' => money_paisa((string) $orders['delivery']),
        'revenue' => money_paisa((string) $orders['revenue']),
        'cogs' => $cogs,
        'profit' => $net - $cogs,
        'margin' => $net > 0 ? ($net - $cogs) / $net : null,
        'uncosted' => (int) $cost['uncosted'],
        'estimated' => (int) $cost['estimated'],
    ];
}

function finance_expenses(DateTimeImmutable $from, DateTimeImmutable $to): array
{
    $dates = finance_dates($from, $to);
    $rows = db_fetch_all(
        'SELECT entry_type, category, COUNT(*) AS n, COALESCE(SUM(amount), 0) AS total FROM expenses
         WHERE deleted_at IS NULL AND entry_date >= :from AND entry_date < :to GROUP BY entry_type, category ORDER BY total DESC',
        $dates
    );
    $result = ['operating' => 0, 'stock' => 0, 'income' => 0, 'expense_rows' => [], 'income_rows' => []];
    foreach ($rows as $row) {
        $amount = money_paisa((string) $row['total']);
        $line = ['category' => (string) $row['category'], 'label' => finance_category_label((string) $row['entry_type'], (string) $row['category']), 'count' => (int) $row['n'], 'amount' => $amount];
        if ($row['entry_type'] === 'income') {
            $result['income'] += $amount;
            $result['income_rows'][] = $line;
            continue;
        }
        $line['stock'] = in_array($row['category'], FINANCE_STOCK_CATEGORIES, true);
        $result[$line['stock'] ? 'stock' : 'operating'] += $amount;
        $result['expense_rows'][] = $line;
    }
    return $result;
}

function finance_commission(DateTimeImmutable $from, DateTimeImmutable $to): int
{
    $total = 0;
    foreach (team_commission_rows('o.status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND o.created_at >= :from AND o.created_at < :to', finance_window($from, $to), 'o.id') as $row) {
        $total += $row['commission']['share'];
    }
    return $total;
}

function finance_statement(array $period): array
{
    $sales = finance_sales($period['from'], $period['to']);
    $previous = finance_sales($period['compare_from'], $period['compare_to']);
    $expenses = finance_expenses($period['from'], $period['to']);
    $previousExpenses = finance_expenses($period['compare_from'], $period['compare_to']);
    $commission = finance_commission($period['from'], $period['to']);
    $previousCommission = finance_commission($period['compare_from'], $period['compare_to']);
    $window = finance_window($period['from'], $period['to']);
    $dates = finance_dates($period['from'], $period['to']);
    $collected = money_paisa((string) db_fetch_column('SELECT COALESCE(SUM(amount), 0) FROM order_payments WHERE voided_at IS NULL AND paid_at >= :from AND paid_at < :to', $window));
    $outstanding = money_paisa((string) db_fetch_column(
        "SELECT COALESCE(SUM(CASE WHEN grand_total > amount_paid THEN grand_total - amount_paid ELSE 0 END), 0) FROM orders WHERE status IN " . DASHBOARD_REVENUE_STATUSES . " AND payment_status <> 'refunded' AND created_at >= :from AND created_at < :to",
        $window
    ));
    $spent = money_paisa((string) db_fetch_column("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE deleted_at IS NULL AND entry_type = 'expense' AND status = 'paid' AND COALESCE(paid_on, entry_date) >= :from AND COALESCE(paid_on, entry_date) < :to", $dates));
    $spent += money_paisa((string) db_fetch_column('SELECT COALESCE(SUM(commission_paid_amount), 0) FROM orders WHERE commission_paid_at >= :from AND commission_paid_at < :to', $window));
    $billsDue = db_fetch("SELECT COUNT(*) AS n, COALESCE(SUM(amount), 0) AS total FROM expenses WHERE deleted_at IS NULL AND entry_type = 'expense' AND status = 'due'");
    $cancelled = db_fetch("SELECT COUNT(*) AS n, COALESCE(SUM(grand_total), 0) AS total FROM orders WHERE status = 'cancelled' AND created_at >= :from AND created_at < :to", $window);
    $pending = db_fetch("SELECT COUNT(*) AS n, COALESCE(SUM(grand_total), 0) AS total FROM orders WHERE status = 'pending' AND created_at >= :from AND created_at < :to", $window);
    return [
        'sales' => $sales,
        'previous' => $previous,
        'expenses' => $expenses,
        'commission' => $commission,
        'net_profit' => $sales['profit'] - $expenses['operating'] - $commission + $expenses['income'],
        'previous_net_profit' => $previous['profit'] - $previousExpenses['operating'] - $previousCommission + $previousExpenses['income'],
        'cash' => [
            'collected' => $collected,
            'outstanding' => $outstanding,
            'spent' => $spent,
            'bills_due' => money_paisa((string) $billsDue['total']),
            'bills_due_count' => (int) $billsDue['n'],
        ],
        'cancelled' => ['count' => (int) $cancelled['n'], 'value' => money_paisa((string) $cancelled['total'])],
        'pending' => ['count' => (int) $pending['n'], 'value' => money_paisa((string) $pending['total'])],
        'buckets' => finance_buckets($period),
        'products' => finance_products($window),
        'methods' => finance_methods($window),
        'cities' => finance_cities($window),
    ];
}

function finance_buckets(array $period): array
{
    $unit = $period['unit'];
    $window = finance_window($period['from'], $period['to']);
    $buckets = [];
    foreach (db_fetch_all('SELECT ' . dashboard_bucket_sql($unit, 'created_at') . ' AS k, COUNT(*) AS n, COALESCE(SUM(grand_total), 0) AS revenue, COALESCE(SUM(subtotal - discount_total), 0) AS net FROM orders WHERE status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND created_at >= :from AND created_at < :to GROUP BY k', $window) as $row) {
        $buckets[(string) $row['k']] = ['orders' => (int) $row['n'], 'revenue' => money_paisa((string) $row['revenue']), 'net' => money_paisa((string) $row['net']), 'cogs' => 0, 'expenses' => 0];
    }
    foreach (db_fetch_all('SELECT ' . dashboard_bucket_sql($unit, 'o.created_at') . ' AS k, COALESCE(SUM(oi.quantity * COALESCE(oi.unit_cost, ps.cost_price, 0)), 0) AS cogs FROM order_items oi JOIN orders o ON o.id = oi.order_id LEFT JOIN product_sizes ps ON ps.id = oi.product_size_id WHERE o.status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND o.created_at >= :from AND o.created_at < :to GROUP BY k', $window) as $row) {
        $key = (string) $row['k'];
        $buckets[$key] = ($buckets[$key] ?? ['orders' => 0, 'revenue' => 0, 'net' => 0, 'cogs' => 0, 'expenses' => 0]);
        $buckets[$key]['cogs'] = money_paisa((string) $row['cogs']);
    }
    if ($unit !== 'hour') {
        $placeholders = ['stock0' => FINANCE_STOCK_CATEGORIES[0], 'stock1' => FINANCE_STOCK_CATEGORIES[1]] + finance_dates($period['from'], $period['to']);
        foreach (db_fetch_all("SELECT " . dashboard_bucket_sql($unit, 'entry_date') . " AS k, COALESCE(SUM(CASE WHEN entry_type = 'expense' THEN amount ELSE 0 END), 0) AS spent, COALESCE(SUM(CASE WHEN entry_type = 'income' THEN amount ELSE 0 END), 0) AS income FROM expenses WHERE deleted_at IS NULL AND NOT (entry_type = 'expense' AND category IN (:stock0, :stock1)) AND entry_date >= :from AND entry_date < :to GROUP BY k", $placeholders) as $row) {
            $key = (string) $row['k'];
            $buckets[$key] = ($buckets[$key] ?? ['orders' => 0, 'revenue' => 0, 'net' => 0, 'cogs' => 0, 'expenses' => 0]);
            $buckets[$key]['expenses'] = money_paisa((string) $row['spent']) - money_paisa((string) $row['income']);
        }
    }
    foreach (team_commission_rows('o.status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND o.created_at >= :from AND o.created_at < :to', $window, 'o.id', dashboard_bucket_sql($unit, 'o.created_at') . ' AS k') as $row) {
        $key = (string) $row['k'];
        $buckets[$key] = ($buckets[$key] ?? ['orders' => 0, 'revenue' => 0, 'net' => 0, 'cogs' => 0, 'expenses' => 0]);
        $buckets[$key]['expenses'] += $row['commission']['share'];
    }
    foreach ($buckets as &$bucket) {
        $bucket['profit'] = $bucket['net'] - $bucket['cogs'];
        $bucket['net_profit'] = $bucket['profit'] - $bucket['expenses'];
    }
    unset($bucket);
    return $buckets;
}

function finance_products(array $window): array
{
    $rows = db_fetch_all(
        'SELECT MAX(oi.product_name) AS name, oi.size_label, COALESCE(SUM(oi.quantity), 0) AS bottles, COALESCE(SUM(oi.line_total - oi.line_discount), 0) AS net,
                COALESCE(SUM(oi.quantity * COALESCE(oi.unit_cost, ps.cost_price)), 0) AS cogs,
                COALESCE(SUM(CASE WHEN COALESCE(oi.unit_cost, ps.cost_price) IS NULL THEN oi.quantity ELSE 0 END), 0) AS uncosted
         FROM order_items oi JOIN orders o ON o.id = oi.order_id LEFT JOIN product_sizes ps ON ps.id = oi.product_size_id
         WHERE o.status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND o.created_at >= :from AND o.created_at < :to
         GROUP BY oi.product_id, oi.size_label ORDER BY net DESC',
        $window
    );
    return array_map(static function (array $row): array {
        $net = money_paisa((string) $row['net']);
        $cogs = money_paisa((string) $row['cogs']);
        return ['name' => (string) $row['name'], 'size' => (string) $row['size_label'], 'bottles' => (int) $row['bottles'], 'net' => $net, 'cogs' => $cogs, 'profit' => $net - $cogs, 'margin' => $net > 0 ? ($net - $cogs) / $net : null, 'uncosted' => (int) $row['uncosted']];
    }, $rows);
}

function finance_methods(array $window): array
{
    $rows = db_fetch_all(
        "SELECT payment_method, COUNT(*) AS n, COALESCE(SUM(grand_total), 0) AS revenue, COALESCE(SUM(amount_paid), 0) AS paid, COALESCE(SUM(CASE WHEN payment_status <> 'refunded' AND grand_total > amount_paid THEN grand_total - amount_paid ELSE 0 END), 0) AS due
         FROM orders WHERE status IN " . DASHBOARD_REVENUE_STATUSES . " AND created_at >= :from AND created_at < :to GROUP BY payment_method ORDER BY revenue DESC",
        $window
    );
    return array_map(static fn (array $row): array => ['label' => payment_method_label((string) $row['payment_method']), 'orders' => (int) $row['n'], 'revenue' => money_paisa((string) $row['revenue']), 'paid' => money_paisa((string) $row['paid']), 'due' => money_paisa((string) $row['due'])], $rows);
}

function finance_cities(array $window): array
{
    $rows = db_fetch_all(
        'SELECT city, COUNT(*) AS n, COALESCE(SUM(grand_total), 0) AS revenue FROM orders WHERE status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND created_at >= :from AND created_at < :to GROUP BY city ORDER BY revenue DESC LIMIT 12',
        $window
    );
    return array_map(static fn (array $row): array => ['city' => (string) $row['city'], 'orders' => (int) $row['n'], 'revenue' => money_paisa((string) $row['revenue'])], $rows);
}

function finance_orders(array $period): array
{
    return db_fetch_all(
        'SELECT o.order_number, o.created_at, o.status, o.customer_name, o.city, o.payment_method, o.payment_status, o.item_count, o.subtotal, o.discount_total, o.shipping_fee, o.cod_fee, o.grand_total, o.amount_paid,
                (SELECT COALESCE(SUM(oi.quantity * COALESCE(oi.unit_cost, ps.cost_price, 0)), 0) FROM order_items oi LEFT JOIN product_sizes ps ON ps.id = oi.product_size_id WHERE oi.order_id = o.id) AS cogs
         FROM orders o WHERE o.created_at >= :from AND o.created_at < :to ORDER BY o.created_at ASC, o.id ASC',
        finance_window($period['from'], $period['to'])
    );
}

function finance_money(int $paisa): string
{
    return ($paisa < 0 ? '−' : '') . money(money_from_paisa(abs($paisa)));
}

function finance_percent(?float $ratio): string
{
    return $ratio === null ? '—' : number_format($ratio * 100, 1) . '%';
}
