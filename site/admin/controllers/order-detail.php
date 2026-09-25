<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/order-actions.php';

$order = adm_order_load($params);
$routeName = (string) ($route['name'] ?? '');

if ($routeName === 'admin.orders.proof') {
    adm_order_stream_proof($order);
}
if (request_method() === 'POST') {
    adm_order_handle_post($route, $order);
}

$orderId = (int) $order['id'];
$items = order_items($orderId);
$sizeIds = array_values(array_filter(array_map(static fn (array $i) => $i['product_size_id'] === null ? null : (int) $i['product_size_id'], $items)));
$liveSizes = [];
if ($sizeIds !== []) {
    $placeholders = [];
    $bind = [];
    foreach ($sizeIds as $n => $sizeId) {
        $placeholders[] = ':s' . $n;
        $bind['s' . $n] = $sizeId;
    }
    foreach (db_fetch_all('SELECT ps.id, ps.price, ps.sale_price, p.deleted_at FROM product_sizes ps INNER JOIN products p ON p.id = ps.product_id WHERE ps.id IN (' . implode(',', $placeholders) . ')', $bind) as $row) {
        $liveSizes[(int) $row['id']] = $row;
    }
}
foreach ($items as &$item) {
    $live = $item['product_size_id'] === null ? null : ($liveSizes[(int) $item['product_size_id']] ?? null);
    $item['product_deleted'] = $live === null || $live['deleted_at'] !== null;
    $livePrice = $live === null ? null : effective_price($live['sale_price'], (string) $live['price']);
    $item['live_price'] = $livePrice !== null && money_paisa($livePrice) !== money_paisa((string) $item['unit_price_charged']) ? $livePrice : null;
    $item['image_url'] = cart_image_url((int) ($item['product_id'] ?? 0), $item['image_filename']);
}
unset($item);

$history = db_fetch_all('SELECT * FROM order_status_history WHERE order_id = :id ORDER BY created_at ASC, id ASC', ['id' => $orderId]);
$notes = db_fetch_all('SELECT * FROM order_notes WHERE order_id = :id ORDER BY is_pinned DESC, created_at DESC, id DESC', ['id' => $orderId]);
$activity = db_fetch_all("SELECT admin_username, action, summary, created_at FROM admin_activity_log WHERE entity_type = 'order' AND entity_id = :id ORDER BY created_at DESC, id DESC LIMIT 10", ['id' => $orderId]);
$proofs = order_proofs($orderId);
$latestProof = $proofs[0] ?? null;
$proofFileOk = $latestProof !== null && !empty($latestProof['file_path']) && image_proof_path((string) $latestProof['file_path']) !== null;
$previousOrders = db_fetch_all('SELECT order_number, status, grand_total, created_at FROM orders WHERE phone_normalized = :p AND id <> :id ORDER BY created_at DESC LIMIT 5', ['p' => (string) $order['phone_normalized'], 'id' => $orderId]);
$previousCount = (int) db_fetch_column('SELECT COUNT(*) FROM orders WHERE phone_normalized = :p AND id <> :id', ['p' => (string) $order['phone_normalized'], 'id' => $orderId]);
$duplicateRef = null;
if (!empty($order['payment_reference'])) {
    $duplicateRef = db_fetch_column('SELECT order_number FROM orders WHERE payment_reference = :ref AND id <> :id ORDER BY created_at DESC LIMIT 1', ['ref' => (string) $order['payment_reference'], 'id' => $orderId]);
}
$messages = adm_order_whatsapp_messages($order, $items);
$whatsapp = [];
foreach ($messages as $key => $message) {
    $link = adm_order_whatsapp_link($order, $message['text']);
    if ($link !== null) {
        $whatsapp[$key] = ['label' => $message['label'], 'href' => $link];
    }
}
$lastStatusMail = outbox_order_mail_status($orderId, 'status-update');
$legalTargets = ORDER_TRANSITIONS[(string) $order['status']] ?? [];

render_admin('order-detail.php', [
    'order' => $order,
    'items' => $items,
    'history' => $history,
    'notes' => $notes,
    'activity' => $activity,
    'latestProof' => $latestProof,
    'proofCount' => count($proofs),
    'proofFileOk' => $proofFileOk,
    'previousOrders' => $previousOrders,
    'previousCount' => $previousCount,
    'duplicateRef' => $duplicateRef,
    'whatsapp' => $whatsapp,
    'whatsappDefault' => adm_order_whatsapp_default($order),
    'phoneDialable' => adm_order_whatsapp_digits($order) !== null,
    'reconciles' => adm_order_reconciles($order),
    'holdDays' => adm_order_hold_days($order),
    'legalTargets' => $legalTargets,
    'primaryAction' => adm_order_primary_action($order),
    'contactFrozen' => adm_order_contact_frozen($order),
    'lastStatusMailFailed' => $lastStatusMail === 'failed',
    'couriers' => ADM_ORDER_COURIERS,
    'isManual' => payment_method_is_manual((string) $order['payment_method']),
    'isMobileUa' => (bool) preg_match('/Mobile|Android|iPhone/i', (string) ($order['user_agent'] ?? '')),
], [
    'title' => (string) $order['order_number'],
    'body_class' => 'admin t-light admin-order has-actionbar',
    'back' => '/admin/orders',
]);
