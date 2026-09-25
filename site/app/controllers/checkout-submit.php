<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/orders.php';

const CHECKOUT_SESSION_EXPIRED = 'Your session expired. Please review your order and try again.';
const CHECKOUT_RATE_LIMITED = 'Too many attempts. Please wait a few minutes, or message us on WhatsApp to place this order.';

function checkout_fail(array $state, int $status = 200): never
{
    global $head, $route;
    if (cart_get()['items'] === []) {
        flash('info', 'Your cart is empty.');
        redirect('/cart', 303);
    }
    http_status($status);
    header_no_store();
    $head['title'] = 'Checkout | Sky Fragrances';
    $head['canonical'] = canonical('/checkout');
    $head['robots'] = 'noindex,follow';
    render('checkout.php', checkout_view_data($state), $head);
}

function checkout_fill_totals(string $text, array $input): string
{
    $priced = cart_priced(true);
    $cart = cart_present($priced);
    $seen = (string) ($input['price_total'] ?? '');
    $was = '';
    if (preg_match('/^\d{1,12}$/', $seen) && (int) $seen !== $priced['grand_total_paisa']
        && hash_equals(pricing_reprice_token((int) $seen, cart_fingerprint($priced)), (string) ($input['price_token'] ?? ''))) {
        $was = ' (was ' . money(money_from_paisa((int) $seen)) . ')';
    }
    return str_replace(['{total}', '{was}'], [$cart['grand_total_display'], $was], $text);
}

$old = checkout_empty_fields();
foreach ($old as $key => $unused) {
    $old[$key] = trim(request_string($key, ''));
}
$old['payment_reference'] = payment_reference_normalize($old['payment_reference']);
$notice = null;
$errors = [];

$trap = form_trap_status();
if ($trap === 'bot') {
    form_trap_log('checkout', $trap);
    checkout_fail(['old' => $old, 'notice' => ['type' => 'error', 'text' => CHECKOUT_SESSION_EXPIRED]]);
}
if ($trap === 'expired') {
    checkout_fail(['old' => $old, 'notice' => ['type' => 'error', 'text' => 'This form expired. Please review your order and try again.']]);
}
if (rate_limit_hit('checkout', request_ip_hash(), ORDER_CHECKOUT_RATE_MAX, ORDER_CHECKOUT_RATE_WINDOW)) {
    checkout_fail(['old' => $old, 'notice' => ['type' => 'error', 'text' => CHECKOUT_RATE_LIMITED]], 429);
}
$idemKey = request_string('idem_key', '');
if (!preg_match('/^[a-f0-9]{32}$/', $idemKey) || !hash_equals((string) ($_SESSION['idem'] ?? ''), $idemKey)) {
    if (preg_match('/^[a-f0-9]{32}$/', $idemKey) && hash_equals((string) ($_SESSION['idem_done'] ?? ''), $idemKey)) {
        $replay = order_replay($idemKey, cart_get());
        if (isset($replay['order_number']) && ($replay['outcome'] === 'replay_same' || cart_get()['items'] === [])) {
            redirect(order_confirmation_url(['order_number' => $replay['order_number'], 'access_token' => $replay['access_token']]), 303);
        }
        if ($replay['outcome'] === 'replay_different') {
            checkout_fail(['old' => $old, 'notice' => ['type' => 'error', 'text' => $replay['message']['text']], 'rotate_idem' => true]);
        }
    }
    checkout_fail(['old' => $old, 'notice' => ['type' => 'error', 'text' => CHECKOUT_SESSION_EXPIRED], 'rotate_idem' => true]);
}
if (cart_get()['items'] === []) {
    flash('info', 'Your cart is empty.');
    redirect('/cart', 303);
}

$phoneNormalized = phone_normalize($old['customer_phone']);
if (mb_strlen($old['customer_name']) < 2 || mb_strlen($old['customer_name']) > 120 || !preg_match('/\p{L}/u', $old['customer_name'])) {
    $errors['customer_name'] = 'Please enter your full name.';
}
if (!preg_match('/^\+923\d{9}$/', $phoneNormalized)) {
    $errors['customer_phone'] = 'Please enter a valid Pakistani mobile number, e.g. 0300 1234567.';
}
if ($old['customer_email'] !== '' && (mb_strlen($old['customer_email']) > 190 || filter_var($old['customer_email'], FILTER_VALIDATE_EMAIL) === false)) {
    $errors['customer_email'] = 'That email address does not look right.';
}
if (mb_strlen($old['city']) < 2 || mb_strlen($old['city']) > 80) {
    $errors['city'] = 'Please enter your city.';
}
if (mb_strlen($old['address']) < 10 || mb_strlen($old['address']) > 400) {
    $errors['address'] = 'Please enter your full delivery address (house, street, area).';
}
if (mb_strlen($old['postal_code']) > 12) {
    $errors['postal_code'] = 'That postal code is too long.';
}
if (mb_strlen($old['customer_note']) > 500) {
    $errors['customer_note'] = 'Please keep your note under 500 characters.';
}
$enabledMethods = payment_methods_enabled();
if ($enabledMethods === []) {
    checkout_fail(['old' => $old, 'notice' => ['type' => 'error', 'text' => "Online ordering is paused right now. Message us on WhatsApp to place your order and we'll set it up for you."]]);
}
$method = $old['payment_method'];
if (!in_array($method, $enabledMethods, true)) {
    $errors['payment_method'] = 'That payment method is no longer available. Please choose another.';
}
$proofFile = null;
$proofMime = '';
if ($method === 'cod') {
    $codBlocked = payment_cod_blocked_message(cart_priced(false)['grand_total_paisa']);
    if ($codBlocked !== null) {
        $errors['payment_method'] = $codBlocked;
    }
} elseif (payment_method_is_manual($method)) {
    $reference = payment_reference_validate($old['payment_reference']);
    if (!$reference['ok']) {
        $errors['payment_reference'] = $reference['message'];
    }
    $proofFile = $_FILES['payment_proof'] ?? null;
    $proof = payment_proof_validate(is_array($proofFile) ? $proofFile : null);
    if (!$proof['ok']) {
        $errors['payment_proof'] = $proof['error'];
    } else {
        $proofMime = $proof['mime'];
    }
    if ($errors === []) {
        $outstanding = order_outstanding_transfer($phoneNormalized, request_ip_hash());
        if ($outstanding !== null) {
            $errors['payment_method'] = order_outstanding_transfer_message($outstanding);
        }
    }
}
if ($errors !== []) {
    checkout_fail(['old' => $old, 'errors' => $errors, 'notice' => ['type' => 'error', 'text' => 'Please check the highlighted fields.']], 422);
}

$input = [
    'customer_name' => $old['customer_name'],
    'customer_phone' => mb_substr($old['customer_phone'], 0, 24),
    'phone_normalized' => $phoneNormalized,
    'customer_email' => $old['customer_email'] === '' ? null : mb_strtolower($old['customer_email']),
    'city' => $old['city'],
    'address' => $old['address'],
    'postal_code' => $old['postal_code'] === '' ? null : $old['postal_code'],
    'customer_note' => $old['customer_note'] === '' ? null : $old['customer_note'],
    'payment_method' => $method,
    'payment_reference' => payment_method_is_manual($method) ? $old['payment_reference'] : null,
    'idem_key' => $idemKey,
    'price_token' => request_string('price_token', ''),
    'price_total' => request_string('price_total', ''),
    'ip_hash' => request_ip_hash(),
    'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
];

$result = order_place($input);

if ($result['outcome'] === 'placed' || $result['outcome'] === 'replay_same') {
    $order = ['order_number' => $result['order_number'], 'access_token' => $result['access_token']];
    if ($result['outcome'] === 'placed') {
        try {
            if (payment_method_is_manual($method) && is_array($proofFile)) {
                payment_proof_store(order_find((int) $result['order_id']), (string) $proofFile['tmp_name'], $proofMime, $old['payment_reference'], $old['sender_name'] === '' ? null : mb_substr($old['sender_name'], 0, 120), null, request_ip_hash());
            }
        } catch (Throwable $e) {
            log_write('warning', 'checkout: proof post-commit step failed', ['order' => $result['order_number'], 'error' => $e->getMessage()]);
        }
        $sent = notify_order_placed((int) $result['order_id']);
        $_SESSION['last_order'] = ['number' => $result['order_number'], 'email_sent' => $sent['customer'] === true];
    }
    cart_clear();
    unset($_SESSION['idem']);
    $_SESSION['idem_done'] = $idemKey;
    redirect(order_confirmation_url($order), 303);
}

if ($result['outcome'] === 'replay_different') {
    checkout_fail(['old' => $old, 'notice' => ['type' => 'error', 'text' => $result['message']['text']], 'rotate_idem' => true]);
}

if ($result['outcome'] === 'empty' || $result['message']['type'] === 'empty') {
    flash('info', 'Your cart is empty.');
    redirect('/cart', 303);
}

$message = $result['message'];
$notice = ['type' => 'error', 'key' => $message['type'], 'text' => checkout_fill_totals($message['text'], $input)];
if (isset($message['size_id'])) {
    $notice['size_id'] = $message['size_id'];
    $notice['available'] = $message['available'];
}
checkout_fail(['old' => $old, 'notice' => $notice], 409);
