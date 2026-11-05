<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/outbox.php';
require_once APP_ROOT . '/app/lib/payments.php';
require_once __DIR__ . '/dashboard-period.php';

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
$firstDay = dashboard_first_day($karachi->setTime(0, 0));
$period = dashboard_period($_GET, $karachi, $firstDay);
$window = ['from' => $period['from']->format('Y-m-d H:i:s'), 'to' => $period['to']->format('Y-m-d H:i:s')];
$kpis = dashboard_kpis($period);

$statusCounts = array_fill_keys(ORDER_STATUSES, 0);
foreach (db_fetch_all('SELECT status, COUNT(*) AS c FROM orders WHERE created_at >= :from AND created_at < :to GROUP BY status', $window) as $row) {
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
    $window
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
    'picker' => dashboard_picker($period, $karachi),
    'periodLabel' => $period['label'],
    'periodDates' => ['from' => $period['from']->format('Y-m-d'), 'to' => $period['to']->modify('-1 day')->format('Y-m-d')],
    'kpis' => $kpis['cards'],
    'chart' => dashboard_chart($period, $karachi),
    'chartTotals' => ['revenue' => money(money_from_paisa($kpis['revenue'])), 'orders' => $kpis['orders']],
    'statusCounts' => $statusCounts,
    'attention' => $attention,
    'storage' => dashboard_storage(),
    'latestOrders' => $latestOrders,
    'lowStock' => $lowStock,
    'lowThreshold' => $threshold,
    'bestSellers' => $bestSellers,
    'lastLoginAt' => $admin['last_login_at'] ?? null,
], ['title' => 'Dashboard', 'badges' => ['orders' => $attention['pending_orders'] + $confirmedCount, 'reviews' => $attention['pending_reviews']]] + $head);
