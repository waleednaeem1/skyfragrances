<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/orders.php';

const TRACK_IP_15MIN_MAX = 10;
const TRACK_IP_DAY_MAX = 40;
const TRACK_PHONE_DAY_MAX = 20;
const TRACK_FLOOR_MICROSECONDS = 250000;
const TRACK_MISS_MESSAGE = "We couldn't find an order with those details. Please check the order number and the phone number used when ordering, or message us on WhatsApp.";
const TRACK_LIMIT_MESSAGE = 'Too many attempts. Please wait 15 minutes, or message us on WhatsApp.';
const TRACK_STAGES = ['pending', 'confirmed', 'packing', 'shipped', 'delivered'];

function track_stage_copy(string $status): string
{
    return match ($status) {
        'pending' => 'We have received your order and will confirm it shortly.',
        'confirmed' => 'Your order is confirmed and will be dispatched soon.',
        'packing' => 'Your parcel is being packed.',
        'shipped' => 'Your parcel is with the courier.',
        'delivered' => 'Your parcel has been delivered.',
        'cancelled' => 'This order was cancelled.',
        default => '',
    };
}

function track_phone_subject(string $phoneNormalized): string
{
    return rate_limit_subject('track-phone:' . $phoneNormalized);
}

function track_limited(string $phoneNormalized): bool
{
    $ipHash = request_ip_hash();
    if (rate_limit_weighted_count('track', $ipHash, 900) >= TRACK_IP_15MIN_MAX || rate_limit_weighted_count('track', $ipHash, 86400) >= TRACK_IP_DAY_MAX) {
        return true;
    }
    return $phoneNormalized !== '' && rate_limit_weighted_count('track', track_phone_subject($phoneNormalized), 86400) >= TRACK_PHONE_DAY_MAX;
}

function track_record(string $phoneNormalized, bool $success): void
{
    rate_limit_record('track', request_ip_hash(), $success);
    if ($phoneNormalized !== '') {
        rate_limit_record('track', track_phone_subject($phoneNormalized), $success);
    }
    rate_limit_purge();
}

function track_pad_response(float $startedAt): void
{
    $elapsed = (int) ((microtime(true) - $startedAt) * 1000000);
    if ($elapsed < TRACK_FLOOR_MICROSECONDS) {
        usleep(TRACK_FLOOR_MICROSECONDS - $elapsed);
    }
}

function track_lookup(string $orderNumber, string $phoneNormalized): ?array
{
    $orderNumber = strtoupper(trim($orderNumber));
    if (!order_number_is_wellformed($orderNumber) || !preg_match('/^\+92\d{10}$/', $phoneNormalized)) {
        return null;
    }
    return db_fetch(
        'SELECT id, order_number, status, payment_method, payment_status, customer_name, city, grand_total, item_count, courier_name, tracking_number, tracking_url, created_at, shipped_at, delivered_at, cancelled_at FROM orders WHERE order_number = :number AND phone_normalized = :phone LIMIT 1',
        ['number' => $orderNumber, 'phone' => $phoneNormalized]
    );
}

function track_timeline(array $order): array
{
    $reached = [];
    foreach (order_history((int) $order['id'], 'status') as $row) {
        $reached[$row['to_status']] = $row['created_at'];
    }
    $current = (string) $order['status'];
    $currentIndex = array_search($current, TRACK_STAGES, true);
    $stages = [];
    foreach (TRACK_STAGES as $index => $stage) {
        $state = $currentIndex === false ? 'future' : ($index < $currentIndex ? 'done' : ($index === $currentIndex ? 'current' : 'future'));
        $stages[] = [
            'key' => $stage,
            'label' => ucfirst($stage),
            'state' => $state,
            'date' => isset($reached[$stage]) && $state !== 'future' ? date_short($reached[$stage]) : '',
            'copy' => $state === 'current' ? track_stage_copy($stage) : '',
        ];
    }
    return $stages;
}

function track_view_order(array $order): array
{
    $name = trim((string) $order['customer_name']);
    $firstName = $name === '' ? '' : explode(' ', $name)[0];
    $trackingUrl = trim((string) ($order['tracking_url'] ?? ''));
    return [
        'order_number' => $order['order_number'],
        'first_name' => $firstName,
        'status' => $order['status'],
        'status_label' => ucfirst((string) $order['status']),
        'status_copy' => track_stage_copy((string) $order['status']),
        'is_cancelled' => $order['status'] === 'cancelled',
        'placed_at' => date_short($order['created_at']),
        'cancelled_at' => date_short($order['cancelled_at']),
        'city' => $order['city'],
        'grand_total_display' => money((string) $order['grand_total']),
        'item_count' => (int) $order['item_count'],
        'payment_method_label' => payment_method_label((string) $order['payment_method']),
        'is_paid' => $order['payment_status'] === 'paid',
        'courier_name' => $order['status'] === 'shipped' || $order['status'] === 'delivered' ? $order['courier_name'] : null,
        'tracking_number' => $order['status'] === 'shipped' || $order['status'] === 'delivered' ? $order['tracking_number'] : null,
        'tracking_url' => str_starts_with(strtolower($trackingUrl), 'https://') ? $trackingUrl : null,
        'delivery_time' => (string) setting('delivery_time', '2–4 working days'),
        'items' => array_map(static fn (array $item): array => ['product_name' => $item['product_name'], 'size_label' => $item['size_label'], 'quantity' => (int) $item['quantity'], 'image_url' => cart_image_url((int) ($item['product_id'] ?? 0), $item['image_filename'])], order_items((int) $order['id'])),
        'timeline' => track_timeline($order),
    ];
}
