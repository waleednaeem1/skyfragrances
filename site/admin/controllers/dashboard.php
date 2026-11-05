<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/outbox.php';
require_once APP_ROOT . '/app/lib/payments.php';

const DASHBOARD_REVENUE_STATUSES = "('confirmed', 'packing', 'shipped', 'delivered')";

function dashboard_compare(int $current, int $previous, bool $money): string
{
    $glyph = $current > $previous ? '▲ +' : ($current < $previous ? '▼ −' : '= ');
    $diff = abs($current - $previous);
    $label = $money ? money(money_from_paisa($diff)) : (string) $diff;
    return $glyph . $label . ' vs ' . ($money ? money(money_from_paisa($previous)) : (string) $previous);
}

function dashboard_bucket_rows(string $from, ?string $to = null): array
{
    $sql = 'SELECT DATE(created_at) AS d, COUNT(*) AS c, COALESCE(SUM(grand_total), 0) AS v FROM orders WHERE status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND created_at >= :from';
    $params = ['from' => $from];
    if ($to !== null) {
        $sql .= ' AND created_at < :to';
        $params['to'] = $to;
    }
    return db_fetch_all($sql . ' GROUP BY DATE(created_at)', $params);
}

function dashboard_fold(array $rows, callable $keep): array
{
    $count = 0;
    $paisa = 0;
    foreach ($rows as $row) {
        if ($keep((string) $row['d'])) {
            $count += (int) $row['c'];
            $paisa += money_paisa((string) $row['v']);
        }
    }
    return ['count' => $count, 'paisa' => $paisa];
}

function dashboard_first_month(DateTimeImmutable $now): DateTimeImmutable
{
    $thisMonth = $now->modify('first day of this month')->setTime(0, 0);
    $firstOrderAt = db_fetch_column('SELECT MIN(created_at) FROM orders');
    return is_string($firstOrderAt) ? min($thisMonth, new DateTimeImmutable(substr($firstOrderAt, 0, 7) . '-01', $now->getTimezone())) : $thisMonth;
}

function dashboard_period_options(DateTimeImmutable $now, DateTimeImmutable $first): array
{
    $thisMonth = $now->modify('first day of this month')->setTime(0, 0);
    $options = ['' => 'This month'];
    for ($year = (int) $now->format('Y'); $year >= (int) $first->format('Y'); $year--) {
        $current = $year === (int) $now->format('Y');
        $options[(string) $year] = $current ? $year . ' so far' : 'All of ' . $year;
        $month = $current ? $thisMonth->modify('-1 month') : new DateTimeImmutable($year . '-12-01', $now->getTimezone());
        while ((int) $month->format('Y') === $year && $month >= $first) {
            $options[$month->format('Y-m')] = $month->format('F Y');
            $month = $month->modify('-1 month');
        }
    }
    return $options;
}

function dashboard_period(string $key, DateTimeImmutable $now): array
{
    $tz = $now->getTimezone();
    if (strlen($key) === 4) {
        $start = new DateTimeImmutable($key . '-01-01', $tz);
        $current = $key === $now->format('Y');
        $endOfToday = $now->modify('+1 day')->setTime(0, 0);
        return [
            'kind' => 'year',
            'key' => $key,
            'label' => $current ? $key . ' so far' : $key,
            'from' => $start,
            'to' => $current ? $endOfToday : $start->modify('+1 year'),
            'prev_from' => $start->modify('-1 year'),
            'prev_to' => $current ? $endOfToday->modify('-1 year') : $start,
            'prev_label' => $current ? 'same time last year' : 'in ' . $start->modify('-1 year')->format('Y'),
        ];
    }
    $start = new DateTimeImmutable($key . '-01', $tz);
    return [
        'kind' => 'month',
        'key' => $key,
        'label' => $start->format('F Y'),
        'from' => $start,
        'to' => $start->modify('first day of next month'),
        'prev_from' => $start->modify('first day of last month'),
        'prev_to' => $start,
        'prev_label' => 'in ' . $start->modify('first day of last month')->format('F Y'),
    ];
}

function dashboard_range_totals(DateTimeImmutable $from, DateTimeImmutable $to): array
{
    $totals = dashboard_fold(dashboard_bucket_rows($from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')), static fn (): bool => true);
    $totals['bottles'] = (int) db_fetch_column(
        'SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND o.created_at >= :from AND o.created_at < :to',
        ['from' => $from->format('Y-m-d H:i:s'), 'to' => $to->format('Y-m-d H:i:s')]
    );
    $totals['average'] = $totals['count'] > 0 ? intdiv($totals['paisa'], $totals['count']) : 0;
    return $totals;
}

function dashboard_period_kpis(array $period): array
{
    $current = dashboard_range_totals($period['from'], $period['to']);
    $previous = dashboard_range_totals($period['prev_from'], $period['prev_to']);
    $suffix = ' ' . $period['prev_label'];
    return [
        ['label' => 'Revenue', 'value' => money(money_from_paisa($current['paisa'])), 'compare' => dashboard_compare($current['paisa'], $previous['paisa'], true) . $suffix],
        ['label' => 'Orders', 'value' => (string) $current['count'], 'compare' => dashboard_compare($current['count'], $previous['count'], false) . $suffix],
        ['label' => 'Average order', 'value' => money(money_from_paisa($current['average'])), 'compare' => dashboard_compare($current['average'], $previous['average'], true) . $suffix],
        ['label' => 'Bottles sold', 'value' => (string) $current['bottles'], 'compare' => dashboard_compare($current['bottles'], $previous['bottles'], false) . $suffix],
    ];
}

function dashboard_year_months(array $period, DateTimeImmutable $now, DateTimeImmutable $first): array
{
    $totals = [];
    foreach (dashboard_bucket_rows($period['from']->format('Y-m-d H:i:s'), $period['to']->format('Y-m-d H:i:s')) as $row) {
        $month = substr((string) $row['d'], 0, 7);
        $totals[$month]['count'] = ($totals[$month]['count'] ?? 0) + (int) $row['c'];
        $totals[$month]['paisa'] = ($totals[$month]['paisa'] ?? 0) + money_paisa((string) $row['v']);
    }
    $months = [];
    for ($month = max($period['from'], $first); $month < $period['to']; $month = $month->modify('first day of next month')) {
        $key = $month->format('Y-m');
        $months[] = [
            'label' => $month->format('F'),
            'period' => $key === $now->format('Y-m') ? '' : $key,
            'count' => $totals[$key]['count'] ?? 0,
            'paisa' => $totals[$key]['paisa'] ?? 0,
        ];
    }
    return $months;
}

function dashboard_storage(): array
{
    $used = housekeeping_storage_usage_bytes();
    $total = @disk_total_space(APP_ROOT . '/storage');
    $free = @disk_free_space(APP_ROOT . '/storage');
    $percent = null;
    if (is_float($total) && $total > 0 && is_float($free)) {
        $percent = (int) round((($total - $free) / $total) * 100);
    }
    return ['used' => $used, 'used_label' => upload_human_size($used), 'percent' => $percent, 'red' => $percent !== null && $percent >= 80];
}

$admin = auth_user();
$karachi = new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi'));
$hour = (int) $karachi->format('G');
$today = $karachi->format('Y-m-d');
$yesterday = $karachi->modify('-1 day')->format('Y-m-d');
$monthStart = $karachi->format('Y-m-01 00:00:00');
$prevMonthStart = $karachi->modify('first day of last month')->format('Y-m-01 00:00:00');
$d30 = $karachi->modify('-30 days')->format('Y-m-d H:i:s');

$firstMonth = dashboard_first_month($karachi);
$periodOptions = dashboard_period_options($karachi, $firstMonth);
$periodKey = (string) request_query_string('period', '');
$period = $periodKey !== '' && preg_match('/^[0-9]{4}(-[0-9]{2})?$/', $periodKey) === 1 && array_key_exists($periodKey, $periodOptions) ? dashboard_period($periodKey, $karachi) : null;
$rangeFrom = $period === null ? $monthStart : $period['from']->format('Y-m-d H:i:s');
$rangeTo = $period === null ? $karachi->modify('+1 day')->format('Y-m-d 00:00:00') : $period['to']->format('Y-m-d H:i:s');

$monthRows = dashboard_bucket_rows($monthStart);
$todayAgg = dashboard_fold($monthRows, static fn (string $d): bool => $d === $today);
$yesterdayAgg = dashboard_fold($monthRows, static fn (string $d): bool => $d === $yesterday);
if ($yesterday < substr($monthStart, 0, 10)) {
    $yesterdayAgg = dashboard_fold(dashboard_bucket_rows($yesterday . ' 00:00:00', $monthStart), static fn (): bool => true);
}
$monthAgg = dashboard_fold($monthRows, static fn (): bool => true);
$prevMonthAgg = dashboard_fold(dashboard_bucket_rows($prevMonthStart, $monthStart), static fn (): bool => true);

$statusCounts = array_fill_keys(ORDER_STATUSES, 0);
foreach (db_fetch_all('SELECT status, COUNT(*) AS c FROM orders WHERE created_at >= :from AND created_at < :to GROUP BY status', ['from' => $rangeFrom, 'to' => $rangeTo]) as $row) {
    if (isset($statusCounts[(string) $row['status']])) {
        $statusCounts[(string) $row['status']] = (int) $row['c'];
    }
}

$holdHours = setting_int('manual_hold_hours', 48);
$attention = [
    'pending_orders' => (int) db_fetch_column("SELECT COUNT(*) FROM orders WHERE status = 'pending'"),
    'pending_reviews' => (int) db_fetch_column("SELECT COUNT(*) FROM reviews WHERE status = 'pending'"),
    'new_messages' => (int) db_fetch_column("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'"),
    'unpaid_transfers' => (int) db_fetch_column(
        "SELECT COUNT(*) FROM orders WHERE payment_method IN ('bank', 'jazzcash', 'easypaisa') AND status = 'pending' AND payment_status IN ('unpaid', 'awaiting_verification', 'failed') AND created_at < :before",
        ['before' => $karachi->modify('-' . max(1, $holdHours) . ' hours')->format('Y-m-d H:i:s')]
    ),
    'outbox' => outbox_dashboard_notice(),
    'hold_hours' => $holdHours,
];
$confirmedCount = (int) db_fetch_column("SELECT COUNT(*) FROM orders WHERE status = 'confirmed'");

$latestOrders = db_fetch_all('SELECT id, order_number, customer_name, city, grand_total, status, payment_status, payment_method, created_at FROM orders ORDER BY created_at DESC, id DESC LIMIT 5');

$threshold = setting_int('low_stock_threshold', 5);
$lowStock = db_fetch_all(
    'SELECT p.id, p.name, p.slug, ps.id AS size_id, ps.size_label, ps.sku, ps.stock, COALESCE(ps.low_stock_threshold, :t1) AS threshold
     FROM product_sizes ps JOIN products p ON p.id = ps.product_id
     WHERE p.is_active = 1 AND p.deleted_at IS NULL AND ps.is_active = 1 AND ps.stock <= COALESCE(ps.low_stock_threshold, :t2)
     ORDER BY (ps.stock <= 0) DESC, ps.stock ASC, p.name ASC LIMIT 8',
    ['t1' => $threshold, 't2' => $threshold]
);

$bestSellers = db_fetch_all(
    'SELECT oi.product_id, MAX(oi.product_name) AS name, SUM(oi.quantity) AS qty, COALESCE(SUM(oi.line_total), 0) AS rev
     FROM order_items oi JOIN orders o ON o.id = oi.order_id
     WHERE o.status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND o.created_at >= :from AND o.created_at < :to
     GROUP BY oi.product_id ORDER BY qty DESC, rev DESC LIMIT 5',
    ['from' => $period === null ? $d30 : $rangeFrom, 'to' => $rangeTo]
);

$manifest = is_file(APP_ROOT . '/db/sample-manifest.php') ? require APP_ROOT . '/db/sample-manifest.php' : ['products' => []];
$sampleRows = (int) db_fetch_column('SELECT COUNT(*) FROM reviews WHERE is_sample = 1');
if ($sampleRows === 0 && ($manifest['products'] ?? []) !== []) {
    $slugParams = [];
    foreach (array_values($manifest['products']) as $i => $slug) {
        $slugParams['s' . $i] = (string) $slug;
    }
    $sampleRows = (int) db_fetch_column('SELECT COUNT(*) FROM products WHERE deleted_at IS NULL AND slug IN (:' . implode(', :', array_keys($slugParams)) . ')', $slugParams);
}

$placeholderMethods = [];
foreach (PAYMENT_MANUAL_METHODS as $manualMethod) {
    if (setting_bool($manualMethod . '_enabled', false) && payment_account_lines($manualMethod) === []) {
        $placeholderMethods[] = payment_method_label($manualMethod);
    }
}

$panelOrigin = request_origin();
$siteUrl = settings_site_url();
$originMismatch = $siteUrl !== '' && request_origin_normalize($siteUrl) !== '' && !hash_equals(request_origin_normalize($siteUrl), $panelOrigin);

render_admin('dashboard.php', [
    'greeting' => $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening'),
    'today' => $karachi->format('l, j M Y'),
    'displayName' => (string) ($admin['display_name'] ?? $admin['username'] ?? ''),
    'installerPresent' => is_file(APP_ROOT . '/install.php'),
    'sampleRows' => $sampleRows,
    'placeholderMethods' => $placeholderMethods,
    'maintenanceOn' => maintenance_active(),
    'originMismatch' => $originMismatch,
    'panelOrigin' => $panelOrigin,
    'siteUrl' => $siteUrl,
    'periodOptions' => $periodOptions,
    'periodKey' => $period === null ? '' : $period['key'],
    'periodLabel' => $period === null ? 'This month' : $period['label'],
    'periodRange' => ['from' => substr($rangeFrom, 0, 10), 'to' => (new DateTimeImmutable($rangeTo))->modify('-1 second')->format('Y-m-d')],
    'bestSellersLabel' => $period === null ? 'last 30 days' : $period['label'],
    'yearMonths' => $period !== null && $period['kind'] === 'year' ? dashboard_year_months($period, $karachi, $firstMonth) : [],
    'kpis' => $period !== null ? dashboard_period_kpis($period) : [
        ['label' => 'Revenue today', 'value' => money(money_from_paisa($todayAgg['paisa'])), 'compare' => dashboard_compare($todayAgg['paisa'], $yesterdayAgg['paisa'], true) . ' yesterday'],
        ['label' => 'Revenue this month', 'value' => money(money_from_paisa($monthAgg['paisa'])), 'compare' => dashboard_compare($monthAgg['paisa'], $prevMonthAgg['paisa'], true) . ' last month'],
        ['label' => 'Orders today', 'value' => (string) $todayAgg['count'], 'compare' => dashboard_compare($todayAgg['count'], $yesterdayAgg['count'], false) . ' yesterday'],
        ['label' => 'Orders this month', 'value' => (string) $monthAgg['count'], 'compare' => dashboard_compare($monthAgg['count'], $prevMonthAgg['count'], false) . ' last month'],
    ],
    'statusCounts' => $statusCounts,
    'attention' => $attention,
    'storage' => dashboard_storage(),
    'latestOrders' => $latestOrders,
    'lowStock' => $lowStock,
    'lowThreshold' => $threshold,
    'bestSellers' => $bestSellers,
    'lastLoginAt' => $admin['last_login_at'] ?? null,
], ['title' => 'Dashboard', 'badges' => ['orders' => $attention['pending_orders'] + $confirmedCount, 'reviews' => $attention['pending_reviews']]] + $head);
