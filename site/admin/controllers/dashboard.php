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

$monthRows = dashboard_bucket_rows($monthStart);
$todayAgg = dashboard_fold($monthRows, static fn (string $d): bool => $d === $today);
$yesterdayAgg = dashboard_fold($monthRows, static fn (string $d): bool => $d === $yesterday);
if ($yesterday < substr($monthStart, 0, 10)) {
    $yesterdayAgg = dashboard_fold(dashboard_bucket_rows($yesterday . ' 00:00:00', $monthStart), static fn (): bool => true);
}
$monthAgg = dashboard_fold($monthRows, static fn (): bool => true);
$prevMonthAgg = dashboard_fold(dashboard_bucket_rows($prevMonthStart, $monthStart), static fn (): bool => true);

$statusCounts = array_fill_keys(ORDER_STATUSES, 0);
foreach (db_fetch_all('SELECT status, COUNT(*) AS c FROM orders WHERE created_at >= :from GROUP BY status', ['from' => $monthStart]) as $row) {
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
     WHERE o.status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND o.created_at >= :d30
     GROUP BY oi.product_id ORDER BY qty DESC, rev DESC LIMIT 5',
    ['d30' => $d30]
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
    'kpis' => [
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
