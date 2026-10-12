<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/orders.php';

const ORDER_NEW_LINES = 6;

function order_new_size_options(): array
{
    $rows = db_fetch_all(
        'SELECT ps.id, ps.size_label, ps.price, ps.sale_price, ps.stock, p.name
         FROM product_sizes ps
         INNER JOIN products p ON p.id = ps.product_id
         WHERE ps.is_active = 1 AND p.is_active = 1 AND p.deleted_at IS NULL
         ORDER BY p.name ASC, ps.sort_order ASC, ps.id ASC'
    );
    $options = [];
    foreach ($rows as $row) {
        $unit = pricing_unit((string) $row['price'], $row['sale_price'] === null ? null : (string) $row['sale_price']);
        $stock = (int) $row['stock'];
        $options[(int) $row['id']] = [
            'label' => $row['name'] . ' — ' . $row['size_label'] . ' · ' . money(money_from_paisa($unit['unit_price_paisa'])) . ' · ' . ($stock > 0 ? $stock . ' in stock' : 'sold out'),
            'in_stock' => $stock > 0,
        ];
    }
    return $options;
}

function order_new_form(): array
{
    $form = [];
    foreach (['customer_name', 'customer_phone', 'customer_email', 'city', 'address', 'postal_code', 'customer_note', 'payment_method', 'coupon_code', 'idem_key', 'price_token', 'price_total'] as $key) {
        $value = request_post($key, '');
        $form[$key] = is_string($value) ? trim(preg_replace('/[^\P{C}\n]+/u', '', $value) ?? '') : '';
    }
    $sizes = request_post('line_size', []);
    $qtys = request_post('line_qty', []);
    $form['lines'] = [];
    for ($i = 0; $i < ORDER_NEW_LINES; $i++) {
        $form['lines'][] = [
            'size_id' => is_array($sizes) && isset($sizes[$i]) && is_string($sizes[$i]) ? (int) $sizes[$i] : 0,
            'qty' => is_array($qtys) && isset($qtys[$i]) && is_string($qtys[$i]) && $qtys[$i] !== '' ? (int) $qtys[$i] : 1,
        ];
    }
    if (!preg_match('/^[a-f0-9]{32}$/', $form['idem_key'])) {
        $form['idem_key'] = bin2hex(random_bytes(16));
    }
    return $form;
}

function order_new_blank(): array
{
    $form = array_fill_keys(['customer_name', 'customer_phone', 'customer_email', 'city', 'address', 'postal_code', 'customer_note', 'coupon_code', 'price_token', 'price_total'], '');
    $methods = payment_methods_enabled();
    $form['payment_method'] = in_array('cod', $methods, true) ? 'cod' : (string) ($methods[0] ?? 'cod');
    $form['idem_key'] = bin2hex(random_bytes(16));
    $form['lines'] = array_fill(0, ORDER_NEW_LINES, ['size_id' => 0, 'qty' => 1]);
    return $form;
}

function order_new_validate(array $form, array $sizeOptions, ?string &$phoneNormalized): array
{
    $errors = [];
    $phoneNormalized = phone_normalize($form['customer_phone']);
    if (mb_strlen($form['customer_name']) < 2 || mb_strlen($form['customer_name']) > 120 || !preg_match('/\p{L}/u', $form['customer_name'])) {
        $errors['customer_name'] = 'Enter the customer\'s full name.';
    }
    if (!preg_match('/^\+923\d{9}$/', $phoneNormalized)) {
        $errors['customer_phone'] = 'Enter a valid Pakistani mobile number, e.g. 0300 1234567.';
    }
    if ($form['customer_email'] !== '' && (mb_strlen($form['customer_email']) > 190 || filter_var($form['customer_email'], FILTER_VALIDATE_EMAIL) === false)) {
        $errors['customer_email'] = 'That email address does not look right.';
    }
    if (mb_strlen($form['city']) < 2 || mb_strlen($form['city']) > 80) {
        $errors['city'] = 'Enter the delivery city.';
    }
    if (mb_strlen($form['address']) < 10 || mb_strlen($form['address']) > 400) {
        $errors['address'] = 'Enter the full delivery address (house, street, area).';
    }
    if (mb_strlen($form['postal_code']) > 12) {
        $errors['postal_code'] = 'That postal code is too long.';
    }
    if (mb_strlen($form['customer_note']) > 500) {
        $errors['customer_note'] = 'Keep the note under 500 characters.';
    }
    if (!in_array($form['payment_method'], payment_methods_enabled(), true)) {
        $errors['payment_method'] = 'Choose how the customer will pay.';
    }
    $chosen = 0;
    foreach ($form['lines'] as $i => $line) {
        if ($line['size_id'] === 0) {
            continue;
        }
        $chosen++;
        if (!isset($sizeOptions[$line['size_id']])) {
            $errors['line_' . $i] = 'Line ' . ($i + 1) . ': choose a perfume from the list.';
        } elseif ($line['qty'] < 1 || $line['qty'] > CART_MAX_QTY_PER_LINE) {
            $errors['line_' . $i] = 'Line ' . ($i + 1) . ': quantity must be 1 to ' . CART_MAX_QTY_PER_LINE . '.';
        }
    }
    if ($chosen === 0) {
        $errors['line_0'] = 'Add at least one perfume.';
    }
    return $errors;
}

function order_new_cart(array $form): array
{
    $items = [];
    foreach ($form['lines'] as $line) {
        if ($line['size_id'] > 0) {
            $items[$line['size_id']] = min(CART_MAX_QTY_PER_LINE, ($items[$line['size_id']] ?? 0) + $line['qty']);
        }
    }
    $pairs = [];
    foreach ($items as $sizeId => $qty) {
        $pairs[] = ['size_id' => $sizeId, 'qty' => $qty];
    }
    $code = $form['coupon_code'] === '' ? null : coupon_normalize_code($form['coupon_code']);
    return ['items' => $pairs, 'coupon_code' => $code, 'updated_at' => time()];
}

function order_new_price_problems(array $priced): array
{
    $problems = [];
    foreach ($priced['removed'] as $removed) {
        $problems[] = $removed['name'] . ' is no longer for sale.';
    }
    foreach ($priced['lines'] as $line) {
        if (!$line['in_stock']) {
            $problems[] = $line['product_name'] . ' ' . $line['size_label'] . ' is sold out.';
        } elseif ($line['qty_adjusted']) {
            $problems[] = 'Only ' . (int) $line['stock'] . ' of ' . $line['product_name'] . ' ' . $line['size_label'] . ' left.';
        }
    }
    if ($priced['coupon_error'] !== null) {
        $problems[] = 'Coupon: ' . (string) ($priced['coupon_error']['message'] ?? 'this code cannot be used on this order.');
    }
    return $problems;
}

$sizeOptions = order_new_size_options();
$errors = [];
$problems = [];
$priced = null;
$form = order_new_blank();

if (request_method() === 'POST') {
    $form = order_new_form();
    $action = (string) request_post('action', 'review');
    $phoneNormalized = null;
    $errors = order_new_validate($form, $sizeOptions, $phoneNormalized);
    if ($errors === []) {
        $cart = order_new_cart($form);
        $priced = cart_resolve($cart, false, $phoneNormalized);
        $problems = order_new_price_problems($priced);
        $token = pricing_reprice_token($priced['grand_total_paisa'], cart_fingerprint($priced));
        $reviewedSame = $form['price_total'] === (string) $priced['grand_total_paisa'] && hash_equals($token, $form['price_token']);
        if ($action === 'create' && $problems === [] && $reviewedSame) {
            $admin = auth_user();
            $result = order_place_manual([
                'customer_name' => $form['customer_name'],
                'customer_phone' => mb_substr($form['customer_phone'], 0, 24),
                'phone_normalized' => $phoneNormalized,
                'customer_email' => $form['customer_email'] === '' ? null : mb_strtolower($form['customer_email']),
                'city' => $form['city'],
                'address' => $form['address'],
                'postal_code' => $form['postal_code'] === '' ? null : $form['postal_code'],
                'customer_note' => $form['customer_note'] === '' ? null : $form['customer_note'],
                'payment_method' => $form['payment_method'],
                'payment_reference' => null,
                'idem_key' => $form['idem_key'],
                'price_token' => $form['price_token'],
                'price_total' => $form['price_total'],
                'ip_hash' => request_ip_hash(),
                'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                'source' => 'phone',
            ], $cart);
            if ($result['outcome'] === 'placed' || $result['outcome'] === 'replay') {
                if ($result['outcome'] === 'placed') {
                    db_insert('admin_activity_log', [
                        'admin_id' => isset($admin['id']) ? (int) $admin['id'] : null,
                        'admin_username' => isset($admin['username']) ? mb_substr((string) $admin['username'], 0, 64) : null,
                        'entity_type' => 'order',
                        'entity_id' => (int) $result['order_id'],
                        'action' => 'order.create_phone',
                        'summary' => mb_substr('Created phone order ' . $result['order_number'] . ' for ' . $form['customer_name'], 0, 255),
                        'ip_hash' => request_ip_hash(),
                        'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                        'created_at' => now_karachi(),
                    ]);
                    try {
                        notify_order_placed((int) $result['order_id']);
                    } catch (Throwable $e) {
                        log_write('warning', 'phone order notification failed', ['order' => $result['order_number'], 'error' => $e->getMessage()]);
                    }
                }
                flash('success', 'Order ' . $result['order_number'] . ' created. Print the invoice or packing slip from this page.');
                redirect('/admin/orders/' . $result['order_number'], 303);
            }
            $problems[] = (string) ($result['message']['text'] ?? 'The order could not be created. Check the lines and try again.');
            $priced = cart_resolve($cart, false, $phoneNormalized);
            $token = pricing_reprice_token($priced['grand_total_paisa'], cart_fingerprint($priced));
        } elseif ($action === 'create' && $problems === [] && !$reviewedSame) {
            $problems[] = 'The total changed since you reviewed it. Check the new total below, then create the order.';
        }
        $form['price_total'] = (string) $priced['grand_total_paisa'];
        $form['price_token'] = $token;
    }
}

render_admin('order-new.php', [
    'form' => $form,
    'errors' => $errors,
    'problems' => $problems,
    'priced' => $priced,
    'sizeOptions' => $sizeOptions,
    'methods' => payment_methods_enabled(),
    'cities' => checkout_cities(),
], ['title' => 'New phone order', 'body_class' => 'admin t-light admin-order-new has-actionbar']);
