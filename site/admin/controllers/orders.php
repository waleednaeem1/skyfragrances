<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/order-actions.php';

const ADM_ORDERS_BULK_ACTIONS = ['confirmed', 'packing', 'shipped', 'packing_slips', 'export'];
const ADM_ORDERS_ATTENTION_CACHE_SECONDS = 60;
const ADM_ORDERS_NEW_SECONDS = 7200;

function adm_orders_ids_from_post(): array
{
    $raw = request_post('ids', []);
    $ids = [];
    foreach (is_array($raw) ? $raw : [] as $value) {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        if ($int !== false && $int > 0) {
            $ids[$int] = $int;
        }
    }
    return array_slice(array_values($ids), 0, 100);
}

function adm_orders_attention_counts(): array
{
    $cacheFile = APP_ROOT . '/storage/cache/orders-attention.json';
    if (is_file($cacheFile) && (int) @filemtime($cacheFile) > time() - ADM_ORDERS_ATTENTION_CACHE_SECONDS) {
        $cached = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($cached) && isset($cached['review'])) {
            return $cached;
        }
    }
    $counts = ['review' => 0, 'pending' => 0, 'confirmed' => 0, 'packing' => 0, 'shipped' => 0, 'hold' => 0];
    foreach (db_fetch_all("SELECT status, COUNT(*) AS c FROM orders WHERE status IN ('pending', 'confirmed', 'packing', 'shipped') GROUP BY status") as $row) {
        $counts[(string) $row['status']] = (int) $row['c'];
    }
    $counts['review'] = (int) db_fetch_column("SELECT COUNT(*) FROM orders WHERE payment_status = 'awaiting_verification' AND status <> 'cancelled'");
    $counts['hold'] = count(adm_orders_unpaid_transfer_rows());
    @file_put_contents($cacheFile, json_encode($counts), LOCK_EX);
    return $counts;
}

function adm_orders_attention_clear(): void
{
    @unlink(APP_ROOT . '/storage/cache/orders-attention.json');
}

function adm_orders_query_url(array $get, array $overrides): string
{
    $q = array_merge($get, $overrides);
    foreach ($q as $key => $value) {
        if ($value === '' || $value === null || $value === []) {
            unset($q[$key]);
        }
    }
    unset($q['page']);
    return '/admin/orders' . ($q === [] ? '' : '?' . http_build_query($q));
}

function adm_orders_active_chips(array $f, array $get): array
{
    $chips = [];
    foreach ($f['status'] as $status) {
        $remaining = array_values(array_diff($f['status'], [$status]));
        $chips[] = ['label' => 'Status: ' . adm_order_status_label($status), 'remove_url' => adm_orders_query_url($get, ['status' => $remaining])];
    }
    if ($f['method'] !== '') {
        $chips[] = ['label' => 'Method: ' . payment_method_label($f['method']), 'remove_url' => adm_orders_query_url($get, ['method' => ''])];
    }
    if ($f['pay'] !== '') {
        $chips[] = ['label' => 'Payment: ' . adm_order_payment_label($f['pay']), 'remove_url' => adm_orders_query_url($get, ['pay' => ''])];
    }
    if ($f['from'] !== '' || $f['to'] !== '') {
        $chips[] = ['label' => 'Dates: ' . ($f['from'] !== '' ? $f['from'] : '…') . ' → ' . ($f['to'] !== '' ? $f['to'] : '…'), 'remove_url' => adm_orders_query_url($get, ['from' => '', 'to' => ''])];
    }
    if ($f['proof'] !== '') {
        $chips[] = ['label' => 'Proof: ' . ($f['proof'] === 'yes' ? 'attached' : 'none'), 'remove_url' => adm_orders_query_url($get, ['proof' => ''])];
    }
    if ($f['q'] !== '') {
        $chips[] = ['label' => 'Search: ' . $f['q'], 'remove_url' => adm_orders_query_url($get, ['q' => ''])];
    }
    return $chips;
}

function adm_orders_date_preset(string $preset): array
{
    $today = date('Y-m-d');
    return match ($preset) {
        'today' => [$today, $today],
        'yesterday' => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))],
        'week' => [date('Y-m-d', strtotime('-6 days')), $today],
        'month' => [date('Y-m-01'), $today],
        'last_month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
        default => ['', ''],
    };
}

function adm_orders_bulk_transition(array $orders, string $to, string $label): void
{
    $updated = 0;
    $skipped = [];
    foreach ($orders as $order) {
        if (!order_transition_allowed((string) $order['status'], $to) || !adm_order_reconciles($order)) {
            $skipped[] = $order['order_number'] . ' (' . strtolower(adm_order_status_label((string) $order['status'])) . ')';
            continue;
        }
        $result = order_transition((int) $order['id'], $to, adm_actor(), '', ['courier_name' => $order['courier_name'], 'tracking_number' => $order['tracking_number'], 'tracking_url' => $order['tracking_url']]);
        if (!$result['ok']) {
            $skipped[] = $order['order_number'];
            continue;
        }
        adm_order_log($order, 'order.status_change', 'Status changed from ' . adm_order_status_label((string) $result['from']) . ' to ' . $label . ' (bulk)', ['status' => $result['from']], ['status' => $to]);
        $updated++;
    }
    adm_orders_attention_clear();
    $text = $updated . ' marked ' . $label . ($skipped !== [] ? ', ' . count($skipped) . ' skipped: ' . implode(', ', array_slice($skipped, 0, 6)) . (count($skipped) > 6 ? '…' : '') : '') . '.';
    flash($updated > 0 ? 'success' : 'info', $text);
}

function adm_orders_bulk_shipped(array $orders): never
{
    $courier = mb_substr(trim((string) request_post('courier_name', '')), 0, 60);
    if ($courier === '') {
        session_ensure();
        $_SESSION['adm_bulk_ship'] = array_map(static fn (array $o): int => (int) $o['id'], $orders);
        flash('info', 'Choose the courier for the ' . count($orders) . ' selected order' . (count($orders) === 1 ? '' : 's') . ' below.');
        redirect('/admin/orders?bulkship=1#bulk-ship', 303);
    }
    unset($_SESSION['adm_bulk_ship']);
    $blocking = [];
    foreach ($orders as $order) {
        if (!in_array($order['status'], ['confirmed', 'packing'], true)) {
            $blocking[] = $order['order_number'] . ' is ' . strtolower(adm_order_status_label((string) $order['status']));
        } elseif (trim((string) ($order['tracking_number'] ?? '')) === '') {
            $blocking[] = $order['order_number'] . ' has no tracking number';
        }
    }
    if ($blocking !== []) {
        flash('error', 'Nothing was shipped. ' . implode('; ', array_slice($blocking, 0, 6)) . (count($blocking) > 6 ? '…' : '') . '. Add tracking on each order first, then try again.');
        redirect('/admin/orders', 303);
    }
    $updated = 0;
    foreach ($orders as $order) {
        $result = order_transition((int) $order['id'], 'shipped', adm_actor(), '', ['courier_name' => $courier, 'tracking_number' => $order['tracking_number'], 'tracking_url' => $order['tracking_url']]);
        if ($result['ok']) {
            adm_order_log($order, 'order.status_change', 'Status changed from ' . adm_order_status_label((string) $result['from']) . ' to Shipped (' . $courier . ' ' . $order['tracking_number'] . ', bulk)', ['status' => $result['from'], 'courier_name' => $order['courier_name']], ['status' => 'shipped', 'courier_name' => $courier]);
            $updated++;
        }
    }
    adm_orders_attention_clear();
    flash($updated > 0 ? 'success' : 'error', $updated . ' of ' . count($orders) . ' marked Shipped with ' . $courier . '.');
    redirect('/admin/orders', 303);
}

function adm_orders_handle_bulk(): never
{
    $action = (string) request_post('action', '');
    $ids = adm_orders_ids_from_post();
    $back = request_return_path('/admin/orders');
    if (!in_array($action, ADM_ORDERS_BULK_ACTIONS, true) || $ids === []) {
        flash('error', $ids === [] ? 'Select at least one order first.' : 'Choose a bulk action.');
        redirect($back, 303);
    }
    if ($action === 'packing_slips') {
        $first = db_fetch_column('SELECT order_number FROM orders WHERE id = :id', ['id' => $ids[0]]);
        if ($first === null) {
            flash('error', 'Those orders no longer exist.');
            redirect($back, 303);
        }
        redirect('/admin/orders/' . $first . '/packing-slip?ids=' . implode(',', $ids), 303);
    }
    if ($action === 'export') {
        redirect('/admin/orders/export.csv?ids=' . implode(',', $ids), 303);
    }
    $orders = [];
    foreach ($ids as $id) {
        $order = order_find($id);
        if ($order !== null) {
            $orders[] = $order;
        }
    }
    if ($orders === []) {
        flash('error', 'Those orders no longer exist.');
        redirect($back, 303);
    }
    match ($action) {
        'confirmed' => adm_orders_bulk_transition($orders, 'confirmed', 'Confirmed'),
        'packing' => adm_orders_bulk_transition($orders, 'packing', 'Packing'),
        default => adm_orders_bulk_shipped($orders),
    };
    redirect($back, 303);
}

function adm_orders_handle_cancel_unpaid(): never
{
    $hours = setting_int('manual_hold_hours', 48);
    $rows = adm_orders_unpaid_transfer_rows($hours);
    if (request_method() === 'POST') {
        $reason = mb_substr(trim((string) request_post('reason', '')), 0, 200);
        if ($reason === '') {
            flash('error', 'Type one reason — it goes on every cancelled order and in the customer email.');
            redirect('/admin/orders/cancel-unpaid', 303);
        }
        if ((string) request_post('confirm_word', '') !== 'CANCEL') {
            flash('error', 'Type CANCEL to confirm cancelling these orders.');
            redirect('/admin/orders/cancel-unpaid', 303);
        }
        $cancelled = [];
        $failed = [];
        foreach ($rows as $row) {
            $order = order_find((int) $row['id']);
            $result = $order === null ? ['ok' => false] : order_transition((int) $row['id'], 'cancelled', adm_actor(), $reason);
            if (!$result['ok']) {
                $failed[] = $row['order_number'];
                continue;
            }
            adm_order_log($order, 'order.status_change', 'Cancelled unpaid transfer order older than ' . $hours . ' h (' . $reason . ')', ['status' => $result['from']], ['status' => 'cancelled']);
            if ($result['stock_summary'] !== null) {
                adm_order_log($order, 'order.stock_restored', 'Stock restored on cancel: ' . $result['stock_summary']);
            }
            $cancelled[] = $row['order_number'];
        }
        adm_orders_attention_clear();
        if ($cancelled === [] && $failed === []) {
            flash('info', 'No unpaid transfer orders older than ' . $hours . ' hours — nothing was cancelled.');
        } else {
            flash($failed === [] ? 'success' : 'info', count($cancelled) . ' order' . (count($cancelled) === 1 ? '' : 's') . ' cancelled and stock restored' . ($failed !== [] ? '; could not cancel ' . implode(', ', $failed) : '') . '.');
        }
        redirect('/admin/orders', 303);
    }
    render_admin('orders-cancel-unpaid.php', [
        'rows' => $rows,
        'hours' => $hours,
        'holdTotal' => array_sum(array_map(static fn (array $r): int => money_paisa((string) $r['grand_total']), $rows)),
    ], [
        'title' => 'Cancel unpaid transfers',
        'body_class' => 'admin t-light admin-orders',
        'back' => '/admin/orders',
    ]);
}

$routeName = (string) ($route['name'] ?? '');
if ($routeName === 'admin.orders.bulk') {
    adm_orders_handle_bulk();
}
if ($routeName === 'admin.orders.cancel_unpaid') {
    adm_orders_handle_cancel_unpaid();
}

$get = $_GET;
$bulkShip = [];
if ((string) ($get['bulkship'] ?? '') === '1') {
    session_ensure();
    foreach ((array) ($_SESSION['adm_bulk_ship'] ?? []) as $bulkId) {
        $bulkRow = db_fetch('SELECT id, order_number, status, tracking_number FROM orders WHERE id = :id', ['id' => (int) $bulkId]);
        if ($bulkRow !== null) {
            $bulkShip[] = $bulkRow;
        }
    }
}
unset($get['bulkship']);
$preset = (string) ($get['range'] ?? '');
if ($preset !== '') {
    [$get['from'], $get['to']] = adm_orders_date_preset($preset);
    unset($get['range']);
}
$filters = adm_orders_filters($get);
if (adm_orders_search_kind($filters['q']) === 'number') {
    $hit = order_find_by_number($filters['q']);
    if ($hit !== null) {
        redirect('/admin/orders/' . $hit['order_number'], 303);
    }
}

$built = adm_orders_where($filters, 'prefix');
$total = (int) db_fetch_column('SELECT COUNT(*) FROM orders o WHERE ' . $built['where'], $built['params']);
if ($total === 0 && adm_orders_search_kind($filters['q']) === 'name') {
    $built = adm_orders_where($filters, 'contains');
    $total = (int) db_fetch_column('SELECT COUNT(*) FROM orders o WHERE ' . $built['where'], $built['params']);
}
$per = $filters['per'];
$pages = max(1, (int) ceil($total / $per));
$page = min($filters['page'], $pages);
$offset = ($page - 1) * $per;
$orders = $total === 0 ? [] : db_fetch_all(
    'SELECT o.id, o.order_number, o.status, o.payment_method, o.payment_status, o.customer_name, o.customer_phone, o.phone_normalized, o.city, o.grand_total, o.item_count, o.courier_name, o.tracking_number, o.created_at, o.stock_restored_at,
        EXISTS (SELECT 1 FROM payment_proofs pp WHERE pp.order_id = o.id AND pp.file_path IS NOT NULL) AS has_proof
     FROM orders o WHERE ' . $built['where'] . ' ORDER BY ' . $built['order'] . ' LIMIT ' . (int) $per . ' OFFSET ' . (int) $offset,
    $built['params']
);
$holdHours = setting_int('manual_hold_hours', 48);
$holdCutoff = time() - $holdHours * 3600;
foreach ($orders as &$row) {
    $placed = strtotime((string) $row['created_at']);
    $row['is_new'] = $placed > time() - ADM_ORDERS_NEW_SECONDS;
    $row['on_hold'] = payment_method_is_manual((string) $row['payment_method']) && $row['status'] === 'pending' && $row['payment_status'] !== 'paid' && $placed < $holdCutoff;
    $row['hold_days'] = $row['on_hold'] ? max(1, (int) floor((time() - $placed) / 86400)) : 0;
    $row['whatsapp'] = adm_order_whatsapp_link($row, 'Assalam-o-Alaikum ' . adm_order_first_name($row) . ", about your Sky Fragrances order " . $row['order_number'] . ':');
}
unset($row);

$attention = adm_orders_attention_counts();
$countText = $total === 0 ? 'No orders match' : 'Showing ' . ($offset + 1) . '–' . min($total, $offset + $per) . ' of ' . $total . ' ' . ($total === 1 ? 'order' : 'orders');
$queryForLinks = $get;
if (isset($queryForLinks['page'])) {
    unset($queryForLinks['page']);
}

render_admin('orders.php', [
    'orders' => $orders,
    'filters' => $filters,
    'get' => $get,
    'query' => $queryForLinks,
    'total' => $total,
    'page' => $page,
    'per' => $per,
    'attention' => $attention,
    'countText' => $countText,
    'activeChips' => adm_orders_active_chips($filters, $get),
    'holdHours' => $holdHours,
    'clearUrl' => '/admin/orders',
    'bulkShip' => $bulkShip,
    'exportUrl' => adm_orders_query_url($get, []) === '/admin/orders' ? '/admin/orders/export.csv' : '/admin/orders/export.csv' . substr(adm_orders_query_url($get, []), strlen('/admin/orders')),
], [
    'title' => 'Orders',
    'body_class' => 'admin t-light admin-orders',
    'wide' => true,
    'badges' => ['orders' => $attention['pending'] + $attention['confirmed'], 'reviews' => (int) db_fetch_column("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")],
]);
