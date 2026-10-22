<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/orders.php';

const ORDER_FORM_LINES = 6;

function order_form_size_options(array $reserved = []): array
{
    $ids = array_map('intval', array_keys($reserved));
    $extra = '';
    $params = [];
    foreach ($ids as $i => $id) {
        $params['r' . $i] = $id;
    }
    if ($params !== []) {
        $extra = ' OR ps.id IN (:' . implode(', :', array_keys($params)) . ')';
    }
    $rows = db_fetch_all(
        'SELECT ps.id, ps.size_label, ps.price, ps.sale_price, ps.stock, p.name
         FROM product_sizes ps
         INNER JOIN products p ON p.id = ps.product_id
         WHERE (ps.is_active = 1 AND p.is_active = 1 AND p.deleted_at IS NULL)' . $extra . '
         ORDER BY p.name ASC, ps.sort_order ASC, ps.id ASC',
        $params
    );
    $options = [];
    foreach ($rows as $row) {
        $unit = pricing_unit((string) $row['price'], $row['sale_price'] === null ? null : (string) $row['sale_price']);
        $available = (int) $row['stock'] + (int) ($reserved[(int) $row['id']] ?? 0);
        $options[(int) $row['id']] = [
            'label' => $row['name'] . ' — ' . $row['size_label'] . ' · ' . money(money_from_paisa($unit['unit_price_paisa'])) . ' · ' . ($available > 0 ? $available . ' available' : 'sold out'),
            'in_stock' => $available > 0,
        ];
    }
    return $options;
}

function order_form_input(): array
{
    $form = [];
    foreach (['customer_name', 'customer_phone', 'customer_email', 'city', 'address', 'postal_code', 'customer_note', 'payment_method', 'coupon_code', 'idem_key', 'price_token', 'price_total'] as $key) {
        $value = request_post($key, '');
        $form[$key] = is_string($value) ? trim(preg_replace('/[^\P{C}\n]+/u', '', $value) ?? '') : '';
    }
    $sizes = request_post('line_size', []);
    $qtys = request_post('line_qty', []);
    $form['lines'] = [];
    for ($i = 0; $i < ORDER_FORM_LINES; $i++) {
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

function order_form_blank(): array
{
    $form = array_fill_keys(['customer_name', 'customer_phone', 'customer_email', 'city', 'address', 'postal_code', 'customer_note', 'coupon_code', 'price_token', 'price_total'], '');
    $methods = payment_methods_enabled();
    $form['payment_method'] = in_array('cod', $methods, true) ? 'cod' : (string) ($methods[0] ?? 'cod');
    $form['idem_key'] = bin2hex(random_bytes(16));
    $form['lines'] = array_fill(0, ORDER_FORM_LINES, ['size_id' => 0, 'qty' => 1]);
    return $form;
}

function order_form_validate(array $form, array $sizeOptions, ?string &$phoneNormalized, bool $checkItems = true, ?array $allowedMethods = null): array
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
    if (!$checkItems) {
        return $errors;
    }
    if (!in_array($form['payment_method'], $allowedMethods ?? payment_methods_enabled(), true)) {
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

function order_form_cart(array $form): array
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

function order_form_price_problems(array $priced): array
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
