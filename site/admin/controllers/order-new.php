<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/order-form.php';

$sizeOptions = order_form_size_options();
$errors = [];
$problems = [];
$priced = null;
$form = order_form_blank();

if (request_method() === 'POST') {
    $form = order_form_input();
    $action = (string) request_post('action', 'review');
    $phoneNormalized = null;
    $errors = order_form_validate($form, $sizeOptions, $phoneNormalized);
    if ($errors === []) {
        $cart = order_form_cart($form);
        $priced = cart_resolve($cart, false, $phoneNormalized);
        $problems = order_form_price_problems($priced);
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
