<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/track.php';

$orderView = null;
$orderNumberInput = strtoupper(trim(request_method() === 'POST' ? request_string('order_number', '') : request_query_string('order', '')));
$phoneInput = '';
$error = null;
$prefill = $orderNumberInput !== '' && request_method() !== 'POST';

if (request_method() === 'POST') {
    $startedAt = microtime(true);
    $phoneInput = trim(request_string('phone', ''));
    $phoneNormalized = $phoneInput === '' ? '' : phone_normalize($phoneInput);
    $trap = form_trap_status();
    if ($trap === 'bot') {
        form_trap_log('track', $trap);
        $error = TRACK_MISS_MESSAGE;
    } elseif ($trap === 'expired') {
        $error = 'This form expired. Please try again.';
    } elseif (track_limited($phoneNormalized)) {
        http_status(429);
        $error = TRACK_LIMIT_MESSAGE;
    } elseif (!order_number_is_wellformed($orderNumberInput) || !preg_match('/^\+92\d{10}$/', $phoneNormalized)) {
        track_record($phoneNormalized, false);
        $error = 'Please enter your order number (like SF-260925-K7QF) and the phone number used for the order.';
    } else {
        $order = track_lookup($orderNumberInput, $phoneNormalized);
        track_record($phoneNormalized, $order !== null);
        if ($order === null) {
            $error = TRACK_MISS_MESSAGE;
        } else {
            $orderView = track_view_order($order);
        }
    }
    track_pad_response($startedAt);
    header_no_store();
    $head['robots'] = 'noindex,follow';
}

$head['title'] = $orderView !== null ? 'Order ' . $orderView['order_number'] . ' — Status | Sky Fragrances' : 'Track Your Order | Sky Fragrances';
$head['meta_description'] = 'Track your Sky Fragrances order with your order number and phone number.';
$head['canonical'] = canonical('/track');

render($route['view'], [
    'heading' => 'Track Your Order',
    'order' => $orderView,
    'orderNumberInput' => $orderNumberInput,
    'phoneInput' => $phoneInput,
    'prefilled' => $prefill,
    'error' => $error,
    'trapField' => form_trap_field(),
    'whatsappUrl' => order_whatsapp_url('Assalam-o-Alaikum! I need help tracking my Sky Fragrances order.'),
], $head);
