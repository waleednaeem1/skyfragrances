<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/stock.php';
require_once __DIR__ . '/payments.php';
require_once __DIR__ . '/forms.php';
require_once __DIR__ . '/notifications.php';

const ORDER_NUMBER_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
const ORDER_NUMBER_PATTERN = '/^SF-[0-9]{6}-[A-HJ-NP-Z2-9]{4}$/';
const ORDER_NUMBER_RETRIES = 5;
const ORDER_DEADLOCK_RETRIES = 2;
const ORDER_TRANSITIONS = [
    'pending' => ['confirmed', 'cancelled'],
    'confirmed' => ['packing', 'shipped', 'cancelled'],
    'packing' => ['confirmed', 'shipped', 'cancelled'],
    'shipped' => ['packing', 'delivered', 'cancelled'],
    'delivered' => [],
    'cancelled' => [],
];
const ORDER_STOCK_AUTO_RESTORE_FROM = ['pending', 'confirmed', 'packing'];
const ORDER_CHECKOUT_RATE_MAX = 10;
const ORDER_CHECKOUT_RATE_WINDOW = 600;
const ORDER_COD_FLAG_PER_PHONE_DAY = 5;
const ORDER_COD_FLAG_PER_IP_DAY = 10;
const ORDER_ERR_EMPTY = 4101;
const ORDER_ERR_REMOVED = 4102;
const ORDER_ERR_REPRICE = 4103;
const ORDER_ERR_COUPON_DROPPED = 4104;
const ORDER_ERR_COUPON_EXHAUSTED = 4105;
const ORDER_ERR_PHONE_LIMIT = 4106;
const ORDER_ERR_REPLAY = 4107;
const ORDER_ERR_SOLD_OUT = 4108;
const ORDER_ERR_COUPON_EXPIRED = 4109;
const ORDER_ERR_TRANSFER_OUTSTANDING = 4110;
const ORDER_ERR_CODES = [ORDER_ERR_EMPTY, ORDER_ERR_REMOVED, ORDER_ERR_REPRICE, ORDER_ERR_COUPON_DROPPED, ORDER_ERR_COUPON_EXHAUSTED, ORDER_ERR_PHONE_LIMIT, ORDER_ERR_REPLAY, ORDER_ERR_SOLD_OUT, ORDER_ERR_COUPON_EXPIRED, ORDER_ERR_TRANSFER_OUTSTANDING];
const ORDER_COUPON_CONFLICT_TYPES = ['coupon_dropped', 'coupon_exhausted', 'coupon_expired', 'phone_limit'];

function order_error(int $code, string $detail = ''): RuntimeException
{
    return new RuntimeException($detail, $code);
}

function order_error_is_conflict(Throwable $e): bool
{
    return in_array($e->getCode(), ORDER_ERR_CODES, true) || stock_conflict_details($e) !== null;
}

function order_coupon_error(array $couponError): RuntimeException
{
    return match ($couponError['key']) {
        'phone_limit' => order_error(ORDER_ERR_PHONE_LIMIT, $couponError['code']),
        'exhausted' => order_error(ORDER_ERR_COUPON_EXHAUSTED, $couponError['code']),
        'expired' => order_error(ORDER_ERR_COUPON_EXPIRED, $couponError['code'] . '|' . $couponError['message']),
        default => order_error(ORDER_ERR_COUPON_DROPPED, $couponError['code']),
    };
}

function order_number_generate(): string
{
    $suffix = '';
    for ($i = 0; $i < 4; $i++) {
        $suffix .= ORDER_NUMBER_ALPHABET[random_int(0, strlen(ORDER_NUMBER_ALPHABET) - 1)];
    }
    return 'SF-' . date('ymd') . '-' . $suffix;
}

function order_number_is_wellformed(string $number): bool
{
    return preg_match(ORDER_NUMBER_PATTERN, $number) === 1;
}

function order_actor_system(): array
{
    return ['by' => 'system', 'admin_id' => null, 'admin_username' => null];
}

function order_history_insert(int $orderId, string $field, ?string $from, string $to, ?string $note, array $actor, ?string $now = null): int
{
    return db_insert('order_status_history', [
        'order_id' => $orderId,
        'field' => $field,
        'from_status' => $from,
        'to_status' => $to,
        'note' => $note === null || $note === '' ? null : mb_substr($note, 0, 500),
        'changed_by' => in_array($actor['by'] ?? '', ['customer', 'admin', 'system'], true) ? $actor['by'] : 'system',
        'admin_id' => isset($actor['admin_id']) ? (int) $actor['admin_id'] : null,
        'admin_username' => isset($actor['admin_username']) ? mb_substr((string) $actor['admin_username'], 0, 64) : null,
        'ip_hash' => PHP_SAPI === 'cli' ? null : request_ip_hash(),
        'created_at' => $now ?? now_karachi(),
    ]);
}

function order_find(int $id): ?array
{
    return db_fetch('SELECT * FROM orders WHERE id = :id', ['id' => $id]);
}

function order_find_by_number(string $number): ?array
{
    $number = strtoupper(trim($number));
    if (!order_number_is_wellformed($number)) {
        return null;
    }
    return db_fetch('SELECT * FROM orders WHERE order_number = :number', ['number' => $number]);
}

function order_items(int $orderId): array
{
    return db_fetch_all('SELECT * FROM order_items WHERE order_id = :id ORDER BY id ASC', ['id' => $orderId]);
}

function order_history(int $orderId, string $field = 'status'): array
{
    return db_fetch_all(
        'SELECT field, from_status, to_status, created_at FROM order_status_history WHERE order_id = :id AND field = :field ORDER BY created_at ASC, id ASC',
        ['id' => $orderId, 'field' => $field]
    );
}

function order_proofs(int $orderId): array
{
    return db_fetch_all('SELECT * FROM payment_proofs WHERE order_id = :id ORDER BY created_at DESC, id DESC', ['id' => $orderId]);
}

function order_items_fingerprint(int $orderId): string
{
    $pairs = [];
    foreach (db_fetch_all('SELECT product_size_id, quantity FROM order_items WHERE order_id = :id', ['id' => $orderId]) as $row) {
        if ($row['product_size_id'] !== null) {
            $pairs[(int) $row['product_size_id']] = (int) $row['quantity'];
        }
    }
    return pricing_fingerprint($pairs);
}

function order_outstanding_transfer(string $phoneNormalized, string $ipHash, int $excludeOrderId = 0): ?array
{
    foreach (['phone' => ['phone_normalized', $phoneNormalized], 'ip' => ['ip_hash', $ipHash]] as $matched => [$column, $value]) {
        $row = db_fetch(
            "SELECT order_number FROM orders WHERE {$column} = :value AND payment_method IN ('bank', 'jazzcash', 'easypaisa') AND status = 'pending' AND payment_status IN ('unpaid', 'awaiting_verification', 'failed') AND id <> :exclude ORDER BY created_at DESC LIMIT 1",
            ['value' => $value, 'exclude' => $excludeOrderId]
        );
        if ($row !== null) {
            return ['order_number' => (string) $row['order_number'], 'matched' => $matched];
        }
    }
    return null;
}

function order_outstanding_transfer_message(array $outstanding): string
{
    if ($outstanding['matched'] === 'phone') {
        return 'You already have a transfer order waiting for verification (' . $outstanding['order_number'] . '). Once it is verified you can place another, or message us on WhatsApp.';
    }
    return 'A transfer order from this connection is still waiting for verification. Once it is verified you can place another, or message us on WhatsApp.';
}

function order_recent_count(string $column, string $value, int $seconds): int
{
    $column = in_array($column, ['phone_normalized', 'ip_hash'], true) ? $column : 'phone_normalized';
    return (int) db_fetch_column(
        "SELECT COUNT(*) FROM orders WHERE {$column} = :value AND created_at > :since AND status <> 'cancelled'",
        ['value' => $value, 'since' => date('Y-m-d H:i:s', time() - $seconds)]
    );
}

function order_is_deadlock(Throwable $e): bool
{
    if (!$e instanceof PDOException) {
        return false;
    }
    $info = $e->errorInfo ?? [];
    return ($info[0] ?? '') === '40001' || (int) ($info[1] ?? 0) === 1213 || (int) ($info[1] ?? 0) === 1205;
}

function order_duplicate_key(Throwable $e): ?string
{
    if (!$e instanceof PDOException || (int) ($e->errorInfo[1] ?? 0) !== 1062) {
        return null;
    }
    $message = $e->getMessage();
    if (str_contains($message, 'uk_orders_idempotency')) {
        return 'idempotency';
    }
    if (str_contains($message, 'uk_orders_number')) {
        return 'number';
    }
    return 'other';
}

function order_insert_header(array $input, array $priced, string $idemKey, string $accessToken, string $now): array
{
    $coupon = $priced['coupon'];
    $row = [
        'access_token' => $accessToken,
        'idempotency_key' => $idemKey,
        'status' => 'pending',
        'payment_method' => $input['payment_method'],
        'payment_status' => 'unpaid',
        'customer_name' => $input['customer_name'],
        'customer_phone' => $input['customer_phone'],
        'phone_normalized' => $input['phone_normalized'],
        'customer_email' => $input['customer_email'],
        'city' => $input['city'],
        'address' => $input['address'],
        'postal_code' => $input['postal_code'],
        'customer_note' => $input['customer_note'],
        'subtotal' => money_from_paisa($priced['subtotal_paisa']),
        'discount_total' => money_from_paisa($priced['discount_paisa']),
        'shipping_fee' => money_from_paisa($priced['shipping_paisa']),
        'cod_fee' => '0.00',
        'grand_total' => money_from_paisa($priced['grand_total_paisa']),
        'item_count' => $priced['item_count'],
        'coupon_id' => $coupon === null ? null : $coupon['id'],
        'coupon_code' => $coupon === null ? null : $coupon['code'],
        'coupon_type' => $coupon === null ? null : $coupon['type'],
        'coupon_value' => $coupon === null ? null : $coupon['value'],
        'payment_reference' => $input['payment_reference'],
        'payment_account_snapshot' => payment_method_is_manual($input['payment_method']) ? payment_account_snapshot($input['payment_method']) : null,
        'ip_hash' => $input['ip_hash'],
        'user_agent' => $input['user_agent'],
        'source' => 'web',
        'created_at' => $now,
        'updated_at' => $now,
    ];
    for ($attempt = 0; $attempt < ORDER_NUMBER_RETRIES; $attempt++) {
        $row['order_number'] = order_number_generate();
        try {
            $orderId = db_insert('orders', $row);
            return ['id' => $orderId, 'order_number' => $row['order_number']];
        } catch (PDOException $e) {
            $duplicate = order_duplicate_key($e);
            if ($duplicate === 'idempotency') {
                throw order_error(ORDER_ERR_REPLAY, $idemKey);
            }
            if ($duplicate !== 'number' || $attempt === ORDER_NUMBER_RETRIES - 1) {
                throw $e;
            }
        }
    }
    throw new RuntimeException('order number exhausted');
}

function order_insert_items(int $orderId, array $priced, string $now): void
{
    $lineTotals = [];
    foreach ($priced['lines'] as $i => $line) {
        $lineTotals[$i] = $line['line_total_paisa'];
    }
    $shares = pricing_apportion_discount($lineTotals, $priced['discount_paisa']);
    foreach ($priced['lines'] as $i => $line) {
        db_insert('order_items', [
            'order_id' => $orderId,
            'product_id' => $line['product_id'],
            'product_size_id' => $line['size_id'],
            'product_name' => mb_substr($line['product_name'], 0, 160),
            'product_slug' => mb_substr($line['product_slug'], 0, 180),
            'size_label' => mb_substr($line['size_label'], 0, 32),
            'size_ml' => $line['size_ml'],
            'sku' => mb_substr($line['sku'], 0, 48),
            'image_filename' => $line['image'],
            'unit_price' => money_from_paisa($line['list_price_paisa']),
            'sale_price' => $line['sale_price_paisa'] === null ? null : money_from_paisa($line['sale_price_paisa']),
            'unit_price_charged' => money_from_paisa($line['unit_price_paisa']),
            'quantity' => $line['qty'],
            'line_total' => money_from_paisa($line['line_total_paisa']),
            'line_discount' => money_from_paisa($shares[$i] ?? 0),
            'created_at' => $now,
        ]);
    }
}

function order_seen_matches(array $input, array $priced): bool
{
    $token = (string) ($input['price_token'] ?? '');
    $seenTotal = (string) ($input['price_total'] ?? '');
    if ($token === '' || !preg_match('/^\d{1,12}$/', $seenTotal)) {
        return false;
    }
    $expected = pricing_reprice_token((int) $seenTotal, cart_fingerprint($priced));
    return hash_equals($expected, $token) && (int) $seenTotal === $priced['grand_total_paisa'];
}

function order_place_attempt(array $input, array $cart): array
{
    $pdo = db();
    $pdo->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
    $pdo->beginTransaction();
    try {
        if (db_exists('SELECT 1 FROM orders WHERE idempotency_key = :key LIMIT 1', ['key' => $input['idem_key']])) {
            throw order_error(ORDER_ERR_REPLAY, $input['idem_key']);
        }
        $priced = cart_resolve($cart, true, $input['phone_normalized']);
        if ($priced['removed'] !== []) {
            throw order_error(ORDER_ERR_REMOVED, $priced['removed'][0]['name'] . ($priced['removed'][0]['size_label'] !== '' ? ' (' . $priced['removed'][0]['size_label'] . ')' : ''));
        }
        if ($priced['has_sold_out']) {
            foreach ($priced['lines'] as $line) {
                if (!$line['in_stock']) {
                    throw order_error(ORDER_ERR_SOLD_OUT, $line['product_name'] . ' ' . $line['size_label']);
                }
            }
        }
        if ($priced['lines'] === [] || $priced['item_count'] === 0) {
            throw order_error(ORDER_ERR_EMPTY);
        }
        if ($priced['coupon_error'] !== null) {
            throw order_coupon_error($priced['coupon_error']);
        }
        foreach ($priced['lines'] as $line) {
            if ($line['qty_adjusted']) {
                throw stock_conflict((int) $line['size_id'], (int) $line['stock']);
            }
        }
        if (!order_seen_matches($input, $priced)) {
            throw order_error(ORDER_ERR_REPRICE, (string) $priced['grand_total_paisa']);
        }
        $now = now_karachi();
        $accessToken = bin2hex(random_bytes(16));
        $header = order_insert_header($input, $priced, $input['idem_key'], $accessToken, $now);
        order_insert_items($header['id'], $priced, $now);
        stock_decrement_lines($priced['lines']);
        if (payment_method_is_manual($input['payment_method'])) {
            $outstanding = order_outstanding_transfer($input['phone_normalized'], (string) $input['ip_hash'], $header['id']);
            if ($outstanding !== null) {
                throw order_error(ORDER_ERR_TRANSFER_OUTSTANDING, order_outstanding_transfer_message($outstanding));
            }
        }
        if ($priced['coupon'] !== null) {
            if (!coupon_claim_use($priced['coupon']['id'], $now)) {
                throw order_error(ORDER_ERR_COUPON_EXHAUSTED, $priced['coupon']['code']);
            }
            coupon_record_redemption(['id' => $priced['coupon']['id'], 'code' => $priced['coupon']['code']], $header['id'], $input['phone_normalized'], $priced['discount_paisa'], $priced['subtotal_paisa'], $now);
        }
        order_history_insert($header['id'], 'status', null, 'pending', null, order_actor_system(), $now);
        $pdo->commit();
        return ['outcome' => 'placed', 'order_id' => $header['id'], 'order_number' => $header['order_number'], 'access_token' => $accessToken, 'priced' => $priced];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function order_coupon_expired_text(string $detail): string
{
    [$code, $reason] = array_pad(explode('|', $detail, 2), 2, '');
    $reason = $reason === '' ? coupon_message('expired', ['date' => 'an earlier date']) : $reason;
    return 'Coupon ' . $code . ' has been removed from your order. ' . $reason . ' Your order total is now {total}.';
}

function order_conflict_message(Throwable $e, array $cart): array
{
    $stock = stock_conflict_details($e);
    if ($stock !== null) {
        $row = cart_load_rows([$stock['size_id']])[$stock['size_id']] ?? null;
        $name = $row === null ? 'An item' : $row['product_name'] . ' ' . $row['size_label'];
        $text = $stock['available'] > 0
            ? 'Sorry — ' . $name . ' sold out while you were checking out. Only ' . $stock['available'] . ' left. Adjust the quantity to continue.'
            : 'Sorry — ' . $name . ' sold out while you were checking out. Remove it to continue.';
        return ['type' => 'stock', 'text' => $text, 'size_id' => $stock['size_id'], 'available' => $stock['available']];
    }
    return match ($e->getCode()) {
        ORDER_ERR_EMPTY => ['type' => 'empty', 'text' => 'Your cart is empty.'],
        ORDER_ERR_REMOVED => ['type' => 'removed', 'text' => $e->getMessage() . ' is no longer available and has been removed from your order. Your new total is {total}.'],
        ORDER_ERR_SOLD_OUT => ['type' => 'sold_out', 'text' => 'Sorry — ' . $e->getMessage() . ' is now sold out. Remove it to continue.'],
        ORDER_ERR_REPRICE => ['type' => 'reprice', 'text' => 'Some prices in your cart have changed. Your new total is {total}{was}. Please review your order and place it again.'],
        ORDER_ERR_COUPON_DROPPED => ['type' => 'coupon_dropped', 'text' => coupon_message('dropped', ['code' => $e->getMessage()])],
        ORDER_ERR_COUPON_EXHAUSTED => ['type' => 'coupon_exhausted', 'text' => 'Coupon ' . $e->getMessage() . ' has just reached its usage limit. Your order total is now {total}.'],
        ORDER_ERR_COUPON_EXPIRED => ['type' => 'coupon_expired', 'text' => order_coupon_expired_text($e->getMessage())],
        ORDER_ERR_PHONE_LIMIT => ['type' => 'phone_limit', 'text' => coupon_message('phone_limit') . ' Your order total is now {total}.'],
        ORDER_ERR_TRANSFER_OUTSTANDING => ['type' => 'transfer_outstanding', 'text' => $e->getMessage()],
        default => ['type' => 'error', 'text' => "We couldn't complete your order just now. Nothing has been charged and your cart is safe. Please try again, or message us on WhatsApp."],
    };
}

function order_place(array $input): array
{
    $cart = cart_get();
    if ($cart['items'] === []) {
        return ['outcome' => 'empty', 'message' => ['type' => 'empty', 'text' => 'Your cart is empty.']];
    }
    for ($attempt = 0; ; $attempt++) {
        try {
            return order_place_attempt($input, $cart);
        } catch (Throwable $e) {
            if (order_is_deadlock($e) && $attempt < ORDER_DEADLOCK_RETRIES) {
                usleep(100000);
                continue;
            }
            if ($e->getCode() === ORDER_ERR_REPLAY) {
                return order_replay($input['idem_key'], $cart);
            }
            if (order_error_is_conflict($e)) {
                $message = order_conflict_message($e, $cart);
                if (in_array($message['type'], ORDER_COUPON_CONFLICT_TYPES, true)) {
                    cart_set_coupon(null);
                }
                return ['outcome' => 'conflict', 'message' => $message];
            }
            log_write('error', 'order placement failed', ['error' => $e->getMessage(), 'code' => $e->getCode(), 'ip_hash' => $input['ip_hash'] ?? '', 'fingerprint' => pricing_fingerprint(cart_pairs($cart))]);
            return ['outcome' => 'conflict', 'message' => order_conflict_message($e, $cart)];
        }
    }
}

function order_replay(string $idemKey, array $cart): array
{
    $existing = db_fetch('SELECT id, order_number, access_token FROM orders WHERE idempotency_key = :key', ['key' => $idemKey]);
    if ($existing === null) {
        return ['outcome' => 'conflict', 'message' => order_conflict_message(new RuntimeException('replay without row'), $cart)];
    }
    $same = hash_equals(order_items_fingerprint((int) $existing['id']), pricing_fingerprint(cart_pairs($cart)));
    return [
        'outcome' => $same ? 'replay_same' : 'replay_different',
        'order_id' => (int) $existing['id'],
        'order_number' => (string) $existing['order_number'],
        'access_token' => (string) $existing['access_token'],
        'message' => $same ? null : ['type' => 'replay', 'text' => 'Your previous order ' . $existing['order_number'] . ' was already placed. This is a new order — please review and place it.'],
    ];
}

function order_confirmation_url(array $order): string
{
    return url('/order/' . $order['order_number'] . '?t=' . $order['access_token']);
}

function order_transition_allowed(string $from, string $to): bool
{
    return in_array($to, ORDER_TRANSITIONS[$from] ?? [], true);
}

function order_transition_requires_reason(string $from, string $to): bool
{
    return $to === 'cancelled' || ($from === 'packing' && $to === 'confirmed') || ($from === 'shipped' && $to === 'packing');
}

function order_transition(int $orderId, string $to, array $actor, string $note = '', array $shipping = []): array
{
    $note = trim($note);
    $result = db_transaction(static function () use ($orderId, $to, $actor, $note, $shipping): array {
        $order = db_fetch('SELECT * FROM orders WHERE id = :id FOR UPDATE', ['id' => $orderId]);
        if ($order === null) {
            return ['ok' => false, 'message' => 'Order not found.', 'order' => null];
        }
        $from = (string) $order['status'];
        if (!order_transition_allowed($from, $to)) {
            return ['ok' => false, 'message' => "That status change isn't allowed. The order is currently " . $from . '.', 'order' => $order];
        }
        if (order_transition_requires_reason($from, $to) && $note === '') {
            return ['ok' => false, 'message' => 'A reason is required for this change.', 'order' => $order];
        }
        $now = now_karachi();
        $update = ['status' => $to, 'updated_at' => $now];
        if ($to === 'shipped') {
            $courier = trim((string) ($shipping['courier_name'] ?? $order['courier_name'] ?? ''));
            $tracking = trim((string) ($shipping['tracking_number'] ?? $order['tracking_number'] ?? ''));
            $trackingUrl = trim((string) ($shipping['tracking_url'] ?? $order['tracking_url'] ?? ''));
            if ($courier === '') {
                return ['ok' => false, 'message' => 'Add the courier name before marking the order shipped. The tracking number can be added later.', 'order' => $order];
            }
            $update['courier_name'] = mb_substr($courier, 0, 60);
            $update['tracking_number'] = $tracking === '' ? null : mb_substr($tracking, 0, 60);
            $update['tracking_url'] = str_starts_with(strtolower($trackingUrl), 'https://') ? mb_substr($trackingUrl, 0, 255) : null;
            $update['shipped_at'] = $now;
        }
        if ($from === 'shipped' && $to === 'packing') {
            $update['shipped_at'] = null;
            $update['courier_name'] = null;
            $update['tracking_number'] = null;
            $update['tracking_url'] = null;
        }
        if ($to === 'delivered') {
            $update['delivered_at'] = $now;
        }
        $stockSummary = null;
        $couponReverted = false;
        if ($to === 'cancelled') {
            $update['cancelled_at'] = $now;
            $update['cancel_reason'] = mb_substr($note, 0, 200);
            if (in_array($from, ORDER_STOCK_AUTO_RESTORE_FROM, true) && $order['stock_restored_at'] === null) {
                $update['stock_restored_at'] = $now;
            }
            $couponReverted = coupon_revert_for_order($orderId);
        }
        $affected = db_update('orders', $update, ['id' => $orderId, 'status' => $from, 'stock_restored_at' => $order['stock_restored_at']]);
        if ($affected !== 1) {
            throw new RuntimeException('order changed concurrently');
        }
        if (isset($update['stock_restored_at'])) {
            $stockSummary = stock_restore_summary(stock_return_items(stock_restorable_items($orderId)));
        }
        $historyNote = $note;
        if ($stockSummary !== null) {
            $historyNote = trim($historyNote . ($historyNote === '' ? '' : ' — ') . 'Stock restored: ' . $stockSummary);
        } elseif ($to === 'cancelled') {
            $historyNote = trim($historyNote . ' — Stock not restored (parcel with courier); use Parcel received back when it returns');
        }
        if ($couponReverted) {
            $historyNote = trim($historyNote . ' — Coupon use returned');
        }
        order_history_insert($orderId, 'status', $from, $to, $historyNote, $actor, $now);
        $order = array_merge($order, $update);
        return ['ok' => true, 'message' => 'Order marked ' . $to . '.', 'order' => $order, 'from' => $from, 'stock_summary' => $stockSummary, 'coupon_reverted' => $couponReverted];
    });
    if ($result['ok']) {
        notify_order_status($orderId, $to);
    }
    return $result;
}

function order_cancel_unpaid_transfers(?int $hours, array $actor): array
{
    $hours = $hours ?? setting_int('manual_hold_hours', 48);
    $before = date('Y-m-d H:i:s', time() - max(1, $hours) * 3600);
    $ids = db_fetch_all(
        "SELECT id, order_number FROM orders WHERE payment_method IN ('bank', 'jazzcash', 'easypaisa') AND status = 'pending' AND payment_status IN ('unpaid', 'awaiting_verification', 'failed') AND created_at < :before ORDER BY id ASC",
        ['before' => $before]
    );
    $cancelled = [];
    $failed = [];
    foreach ($ids as $row) {
        $result = order_transition((int) $row['id'], 'cancelled', $actor, 'Unpaid transfer order older than ' . $hours . ' hours');
        $result['ok'] ? $cancelled[] = $row['order_number'] : $failed[] = $row['order_number'];
    }
    return ['cancelled' => $cancelled, 'failed' => $failed, 'hours' => $hours];
}

function checkout_idem_key(bool $rotate = false): string
{
    session_ensure();
    if ($rotate || empty($_SESSION['idem']) || !is_string($_SESSION['idem'])) {
        $_SESSION['idem'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['idem'];
}

function checkout_empty_fields(): array
{
    return ['customer_name' => '', 'customer_phone' => '', 'customer_email' => '', 'city' => '', 'address' => '', 'postal_code' => '', 'customer_note' => '', 'payment_method' => '', 'payment_reference' => '', 'sender_name' => ''];
}

function checkout_cities(): array
{
    return ['Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta', 'Gujranwala', 'Sialkot', 'Hyderabad', 'Sukkur', 'Bahawalpur', 'Sargodha', 'Abbottabad', 'Sahiwal', 'Okara', 'Gujrat', 'Jhelum', 'Mardan', 'Larkana', 'Sheikhupura', 'Rahim Yar Khan', 'Dera Ghazi Khan', 'Mirpur', 'Muzaffarabad', 'Gilgit', 'Wah Cantt', 'Kasur', 'Jhang', 'Mingora', 'Nawabshah', 'Chiniot', 'Kamoke', 'Hafizabad', 'Kohat', 'Khanewal', 'Dera Ismail Khan', 'Turbat', 'Muzaffargarh'];
}

function checkout_view_data(array $state = []): array
{
    $priced = cart_priced(true);
    $cart = cart_present($priced);
    $methods = payment_methods_for_view();
    $codBlocked = payment_cod_blocked_message($priced['grand_total_paisa']);
    $old = array_merge(checkout_empty_fields(), $state['old'] ?? []);
    if ($old['payment_method'] === '' && $methods !== []) {
        $old['payment_method'] = $methods[0]['key'];
    }
    return [
        'heading' => 'Checkout',
        'cart' => $cart,
        'paymentMethods' => $methods,
        'orderingPaused' => $methods === [],
        'codBlockedMessage' => $codBlocked,
        'old' => $old,
        'errors' => $state['errors'] ?? [],
        'notice' => $state['notice'] ?? null,
        'idemKey' => checkout_idem_key(!empty($state['rotate_idem'])),
        'priceToken' => pricing_reprice_token($priced['grand_total_paisa'], cart_fingerprint($priced)),
        'priceTotal' => $priced['grand_total_paisa'],
        'trapField' => form_trap_field(),
        'cities' => checkout_cities(),
        'manualPaymentNote' => (string) setting('manual_payment_note', ''),
        'deliveryTime' => (string) setting('delivery_time', '2–4 working days'),
        'whatsapp' => (string) setting('whatsapp', ''),
    ];
}

function order_address_visible(array $order): bool
{
    $closedAt = $order['delivered_at'] ?? $order['cancelled_at'] ?? null;
    if ($closedAt === null) {
        return true;
    }
    return strtotime((string) $closedAt) > time() - 30 * 86400;
}

function order_whatsapp_url(string $text): string
{
    $number = preg_replace('/\D+/', '', (string) setting('whatsapp', '')) ?? '';
    return $number === '' ? '' : 'https://wa.me/' . $number . '?text=' . rawurlencode($text);
}

function order_view_items(int $orderId): array
{
    $items = [];
    foreach (order_items($orderId) as $item) {
        $items[] = $item + [
            'image_url' => cart_image_url((int) ($item['product_id'] ?? 0), $item['image_filename']),
            'url' => url('/product/' . $item['product_slug']),
            'unit_price_display' => money((string) $item['unit_price_charged']),
            'compare_at_display' => $item['sale_price'] !== null && money_paisa((string) $item['sale_price']) < money_paisa((string) $item['unit_price']) ? money((string) $item['unit_price']) : null,
            'line_total_display' => money((string) $item['line_total']),
        ];
    }
    return $items;
}

function order_view_totals(array $order): array
{
    return [
        'subtotal' => money((string) $order['subtotal']),
        'discount' => money_paisa((string) $order['discount_total']) > 0 ? money((string) $order['discount_total']) : null,
        'coupon_code' => $order['coupon_code'],
        'shipping' => money_paisa((string) $order['shipping_fee']) > 0 ? money((string) $order['shipping_fee']) : 'Free',
        'cod_fee' => money_paisa((string) $order['cod_fee']) > 0 ? money((string) $order['cod_fee']) : null,
        'grand_total' => money((string) $order['grand_total']),
    ];
}
