<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/orders.php';

if (request_origin_is_foreign()) {
    json(['ok' => false, 'error' => 'origin', 'message' => 'Request blocked.'], 403);
}

function cart_api_payload(bool $ok, array $messages = [], string $error = ''): array
{
    $cart = cart_present(cart_priced(true));
    foreach ($cart['messages'] as $message) {
        $messages[] = $message;
    }
    return [
        'ok' => $ok,
        'error' => $error,
        'count' => $cart['item_count'],
        'lines' => array_map(static fn (array $line): array => [
            'size_id' => $line['size_id'],
            'product_id' => $line['product_id'],
            'name' => $line['product_name'],
            'url' => $line['url'],
            'size_label' => $line['size_label'],
            'image_url' => $line['image_url'],
            'qty' => $line['qty'],
            'max_qty' => $line['max_qty'],
            'stock' => $line['stock'],
            'in_stock' => $line['in_stock'],
            'qty_adjusted' => $line['qty_adjusted'],
            'unit_price' => $line['unit_price'],
            'unit_price_display' => $line['unit_price_display'],
            'compare_at' => $line['compare_at'],
            'compare_at_display' => $line['compare_at_display'],
            'line_total' => $line['line_total'],
            'line_total_display' => $line['line_total_display'],
            'notice' => $line['notice'],
        ], $cart['lines']),
        'subtotal' => $cart['subtotal'],
        'subtotal_display' => $cart['subtotal_display'],
        'discount' => $cart['discount'],
        'discount_display' => $cart['discount_display'],
        'shipping' => $cart['shipping'],
        'shipping_display' => $cart['shipping_display'],
        'free_shipping' => $cart['free_shipping'],
        'free_shipping_remaining' => $cart['remaining'],
        'free_shipping_remaining_display' => $cart['remaining_display'],
        'progress' => $cart['progress'],
        'total' => $cart['grand_total'],
        'total_display' => $cart['grand_total_display'],
        'coupon' => $cart['coupon'] === null ? null : ['code' => $cart['coupon']['code'], 'discount' => $cart['coupon']['discount'], 'discount_display' => $cart['coupon']['discount_display'], 'message' => $cart['coupon']['message']],
        'can_checkout' => $cart['can_checkout'],
        'is_empty' => $cart['is_empty'],
        'messages' => $messages,
        'csrf' => csrf_token(),
    ];
}

function cart_api_respond(bool $ok, array $messages = [], string $error = '', int $status = 200): never
{
    $payload = cart_api_payload($ok, $messages, $error);
    if (request_method() === 'POST' && !request_is_ajax() && request_json() === []) {
        foreach ($payload['messages'] as $message) {
            flash($message['type'] === 'error' ? 'error' : ($message['type'] === 'success' ? 'success' : 'info'), $message['text']);
        }
        redirect(request_return_path('/cart'), 303);
    }
    json($payload, $ok ? 200 : $status);
}

$action = $route['name'];

if ($action === 'api.cart.get') {
    json(cart_api_payload(true));
}

$sizeId = (int) request_input('size_id', 0);
$qtyRaw = request_input('qty', 1);
$qty = filter_var($qtyRaw, FILTER_VALIDATE_INT);
$qty = $qty === false ? 0 : (int) $qty;

if ($action === 'api.cart.add') {
    $row = $sizeId > 0 ? (cart_load_rows([$sizeId])[$sizeId] ?? null) : null;
    if ($row === null || !cart_row_purchasable($row)) {
        cart_api_respond(false, [['type' => 'error', 'key' => 'unavailable', 'text' => 'That item is no longer available.']], 'unavailable', 404);
    }
    if ((int) $row['stock'] <= 0) {
        cart_api_respond(false, [['type' => 'error', 'key' => 'sold_out', 'text' => $row['product_name'] . ' (' . $row['size_label'] . ') is sold out.']], 'sold_out', 409);
    }
    $result = cart_add($sizeId, max(1, $qty));
    if (!$result['ok']) {
        cart_api_respond(false, [['type' => 'error', 'key' => 'cart_full', 'text' => $result['message']]], 'cart_full', 409);
    }
    cart_api_respond(true, [['type' => 'success', 'key' => 'added', 'text' => $row['product_name'] . ' (' . $row['size_label'] . ') added to your cart.']]);
}

if ($action === 'api.cart.update') {
    if ($sizeId <= 0) {
        cart_api_respond(false, [['type' => 'error', 'key' => 'invalid', 'text' => 'That item is not in your cart.']], 'invalid', 422);
    }
    cart_update($sizeId, $qty);
    cart_api_respond(true, $qty > CART_MAX_QTY_PER_LINE ? [['type' => 'info', 'key' => 'max_qty', 'text' => 'Maximum ' . CART_MAX_QTY_PER_LINE . ' per size per order.']] : []);
}

if ($action === 'api.cart.remove') {
    $removedRow = $sizeId > 0 ? (cart_load_rows([$sizeId])[$sizeId] ?? null) : null;
    cart_remove($sizeId);
    $label = $removedRow === null ? 'Item' : $removedRow['product_name'] . ' (' . $removedRow['size_label'] . ')';
    cart_api_respond(true, [['type' => 'success', 'key' => 'removed', 'text' => 'Removed ' . $label . '.']]);
}

if ($action === 'api.cart.coupon') {
    $couponAction = request_string('action', 'apply');
    if ($couponAction === 'remove') {
        cart_set_coupon(null);
        cart_api_respond(true, [['type' => 'info', 'key' => 'coupon_removed', 'text' => coupon_message('removed')]]);
    }
    $code = coupon_normalize_code(request_string('code', ''));
    if ($code === '') {
        cart_api_respond(false, [['type' => 'error', 'key' => 'empty_field', 'text' => coupon_message('empty_field')]], 'coupon', 422);
    }
    if (coupon_rate_limited()) {
        cart_api_respond(false, [['type' => 'error', 'key' => 'invalid', 'text' => coupon_message('rate_limited')]], 'coupon', 429);
    }
    coupon_rate_record();
    $previous = cart_get()['coupon_code'];
    $priced = cart_priced(false);
    $validation = coupon_validate(coupon_find($code), $code, $priced['subtotal_paisa'], now_karachi());
    if (!$validation['ok']) {
        cart_api_respond(false, [['type' => 'error', 'key' => $validation['key'], 'text' => $validation['message']]], 'coupon', 422);
    }
    cart_set_coupon($code);
    $messages = [['type' => 'success', 'key' => 'applied', 'text' => $validation['message']]];
    if ($previous !== null && $previous !== $code) {
        array_unshift($messages, ['type' => 'info', 'key' => 'replaced', 'text' => coupon_message('replaced', ['old' => $previous, 'new' => $code])]);
    }
    cart_api_respond(true, $messages);
}

json(['ok' => false, 'error' => 'not_found', 'message' => 'Unknown cart action.'], 404);
