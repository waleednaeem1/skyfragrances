<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/order-actions.php';
require_once __DIR__ . '/order-form.php';

const ORDER_EDIT_PAYMENT_STATUSES = ['unpaid' => 'Unpaid', 'paid' => 'Paid'];

function order_edit_money_in(string $raw): ?int
{
    $clean = str_replace([',', ' ', 'Rs.', 'Rs', 'rs.', 'rs'], '', trim($raw));
    if ($clean === '' || !preg_match('/^\d{1,9}(\.\d{1,2})?$/', $clean)) {
        return null;
    }
    return money_paisa($clean);
}

function order_edit_money_out(string $value): string
{
    $paisa = money_paisa($value);
    return $paisa % 100 === 0 ? (string) intdiv($paisa, 100) : money_from_paisa($paisa);
}

function order_edit_split_items(array $items): array
{
    $editable = [];
    $fixed = [];
    foreach ($items as $item) {
        if ($item['product_size_id'] === null) {
            $fixed[] = $item;
        } else {
            $editable[] = $item;
        }
    }
    return [$editable, $fixed];
}

function order_edit_form_from_order(array $order, array $editableItems): array
{
    $form = order_form_blank();
    foreach (['customer_name', 'customer_phone', 'customer_email', 'city', 'address', 'postal_code', 'customer_note', 'payment_method', 'payment_status'] as $key) {
        $form[$key] = (string) ($order[$key] ?? '');
    }
    $form['delivery_fee'] = order_edit_money_out((string) $order['shipping_fee']);
    $form['discount'] = order_edit_money_out((string) $order['discount_total']);
    $lines = [];
    foreach ($editableItems as $item) {
        $lines[] = ['size_id' => (int) $item['product_size_id'], 'qty' => (int) $item['quantity'], 'price' => order_edit_money_out((string) $item['unit_price_charged'])];
    }
    $total = max(ORDER_FORM_LINES, count($lines) + 2);
    for ($i = count($lines); $i < $total; $i++) {
        $lines[] = ['size_id' => 0, 'qty' => 1, 'price' => ''];
    }
    $form['lines'] = $lines;
    return $form;
}

function order_edit_input(int $lineCount): array
{
    $form = order_form_input();
    $sizes = request_post('line_size', []);
    $qtys = request_post('line_qty', []);
    $prices = request_post('line_price', []);
    $form['lines'] = [];
    for ($i = 0; $i < $lineCount; $i++) {
        $form['lines'][] = [
            'size_id' => is_array($sizes) && isset($sizes[$i]) && is_string($sizes[$i]) ? (int) $sizes[$i] : 0,
            'qty' => is_array($qtys) && isset($qtys[$i]) && is_string($qtys[$i]) && $qtys[$i] !== '' ? (int) $qtys[$i] : 1,
            'price' => is_array($prices) && isset($prices[$i]) && is_string($prices[$i]) ? trim($prices[$i]) : '',
        ];
    }
    foreach (['delivery_fee', 'discount', 'payment_status'] as $key) {
        $value = request_post($key, '');
        $form[$key] = is_string($value) ? trim($value) : '';
    }
    return $form;
}

function order_edit_validate(array $form, array $sizeOptions, ?string &$phoneNormalized, array $allowedMethods, array $paymentStatuses, int $fixedSubtotal): array
{
    $errors = order_form_validate(['lines' => []] + $form, $sizeOptions, $phoneNormalized, false);
    if (!in_array($form['payment_method'], $allowedMethods, true)) {
        $errors['payment_method'] = 'Choose how the customer pays.';
    }
    if (!isset($paymentStatuses[$form['payment_status']])) {
        $errors['payment_status'] = 'Choose the payment status.';
    }
    $chosen = 0;
    $subtotal = $fixedSubtotal;
    foreach ($form['lines'] as $i => $line) {
        if ($line['size_id'] === 0) {
            continue;
        }
        $chosen++;
        if (!isset($sizeOptions[$line['size_id']])) {
            $errors['line_' . $i] = 'Line ' . ($i + 1) . ': choose a perfume from the list.';
            continue;
        }
        if ($line['qty'] < 1 || $line['qty'] > 999) {
            $errors['line_' . $i] = 'Line ' . ($i + 1) . ': quantity must be 1 to 999.';
            continue;
        }
        if ($line['price'] !== '' && order_edit_money_in($line['price']) === null) {
            $errors['line_' . $i] = 'Line ' . ($i + 1) . ': enter the price as a number, e.g. 4950.';
            continue;
        }
        $subtotal += (order_edit_money_in($line['price']) ?? 0) * $line['qty'];
    }
    if ($chosen === 0 && $fixedSubtotal === 0) {
        $errors['line_0'] = 'Keep at least one perfume on the order.';
    }
    if (order_edit_money_in($form['delivery_fee']) === null) {
        $errors['delivery_fee'] = 'Enter the delivery fee as a number, 0 for free.';
    }
    $discount = order_edit_money_in($form['discount']);
    if ($discount === null) {
        $errors['discount'] = 'Enter the discount as a number, 0 for none.';
    }
    return $errors;
}

function order_edit_lines(array $form): array
{
    $sizeIds = [];
    foreach ($form['lines'] as $line) {
        if ($line['size_id'] > 0) {
            $sizeIds[] = $line['size_id'];
        }
    }
    $rows = cart_load_rows($sizeIds);
    $lines = [];
    foreach ($form['lines'] as $line) {
        if ($line['size_id'] === 0 || !isset($rows[$line['size_id']])) {
            continue;
        }
        $row = $rows[$line['size_id']];
        $unit = pricing_unit((string) $row['price'], $row['sale_price'] === null ? null : (string) $row['sale_price']);
        $price = $line['price'] === '' ? $unit['unit_price_paisa'] : (int) order_edit_money_in($line['price']);
        $lines[] = [
            'size_id' => (int) $row['size_id'],
            'qty' => $line['qty'],
            'row' => $row,
            'list_paisa' => money_paisa((string) $row['price']),
            'sale_paisa' => $row['sale_price'] === null ? null : money_paisa((string) $row['sale_price']),
            'unit_paisa' => $price,
            'total_paisa' => $price * $line['qty'],
        ];
    }
    return $lines;
}

function order_edit_save(array $order, array $form, array $lines, string $phoneNormalized): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $locked = db_fetch('SELECT * FROM orders WHERE id = :id FOR UPDATE', ['id' => (int) $order['id']]);
        $items = order_items((int) $order['id']);
        [$editable, $fixed] = order_edit_split_items($items);
        $holdsStock = (string) $locked['status'] !== 'cancelled' && $locked['stock_restored_at'] === null;
        if ($holdsStock) {
            stock_return_items($editable);
        }
        db_query('DELETE FROM order_items WHERE order_id = :id AND product_size_id IS NOT NULL', ['id' => (int) $order['id']]);
        $fixedSubtotal = 0;
        $fixedCount = 0;
        foreach ($fixed as $item) {
            $fixedSubtotal += money_paisa((string) $item['line_total']);
            $fixedCount += (int) $item['quantity'];
        }
        $subtotal = $fixedSubtotal;
        $count = $fixedCount;
        foreach ($lines as $line) {
            $subtotal += $line['total_paisa'];
            $count += $line['qty'];
        }
        $discount = min((int) order_edit_money_in($form['discount']), $subtotal);
        $shipping = (int) order_edit_money_in($form['delivery_fee']);
        $cod = money_paisa((string) $locked['cod_fee']);
        $shares = pricing_apportion_discount(array_map(static fn (array $l): int => $l['total_paisa'], $lines), $discount);
        $now = now_karachi();
        foreach ($lines as $i => $line) {
            $row = $line['row'];
            db_insert('order_items', [
                'order_id' => (int) $order['id'],
                'product_id' => (int) $row['product_id'],
                'product_size_id' => $line['size_id'],
                'product_name' => mb_substr((string) $row['product_name'], 0, 160),
                'product_slug' => mb_substr((string) $row['product_slug'], 0, 180),
                'size_label' => mb_substr((string) $row['size_label'], 0, 32),
                'size_ml' => $row['size_ml'],
                'sku' => mb_substr((string) $row['sku'], 0, 48),
                'image_filename' => $row['image'],
                'unit_price' => money_from_paisa($line['list_paisa']),
                'sale_price' => $line['sale_paisa'] === null ? null : money_from_paisa($line['sale_paisa']),
                'unit_price_charged' => money_from_paisa($line['unit_paisa']),
                'quantity' => $line['qty'],
                'line_total' => money_from_paisa($line['total_paisa']),
                'line_discount' => money_from_paisa($shares[$i] ?? 0),
                'created_at' => $now,
            ]);
        }
        if ($holdsStock && $lines !== []) {
            stock_decrement_lines(array_map(static fn (array $l): array => ['size_id' => $l['size_id'], 'qty' => $l['qty']], $lines));
        }
        $update = order_edit_detail_columns($form, $phoneNormalized) + [
            'payment_method' => $form['payment_method'],
            'payment_account_snapshot' => payment_method_is_manual($form['payment_method']) ? payment_account_snapshot($form['payment_method']) : null,
            'subtotal' => money_from_paisa($subtotal),
            'discount_total' => money_from_paisa($discount),
            'shipping_fee' => money_from_paisa($shipping),
            'grand_total' => money_from_paisa($subtotal - $discount + $shipping + $cod),
            'item_count' => $count,
            'updated_at' => $now,
        ];
        if ($form['payment_status'] !== (string) $locked['payment_status']) {
            $update['payment_status'] = $form['payment_status'];
            $update['paid_at'] = $form['payment_status'] === 'paid' ? ($locked['paid_at'] ?? $now) : null;
        }
        db_update('orders', $update, ['id' => (int) $order['id']]);
        $pdo->commit();
        return ['before' => $items, 'after_total' => $update['grand_total'], 'locked' => $locked];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function order_edit_detail_columns(array $form, string $phoneNormalized): array
{
    return [
        'customer_name' => $form['customer_name'],
        'customer_phone' => mb_substr($form['customer_phone'], 0, 24),
        'phone_normalized' => $phoneNormalized,
        'customer_email' => $form['customer_email'] === '' ? null : mb_strtolower($form['customer_email']),
        'city' => $form['city'],
        'address' => $form['address'],
        'postal_code' => $form['postal_code'] === '' ? null : $form['postal_code'],
        'customer_note' => $form['customer_note'] === '' ? null : $form['customer_note'],
    ];
}

function order_edit_items_summary(array $items): string
{
    $parts = [];
    foreach ($items as $item) {
        $parts[] = $item['product_name'] . ' ' . $item['size_label'] . ' x' . (int) $item['quantity'] . ' @ ' . money((string) $item['unit_price_charged']);
    }
    return implode(', ', $parts);
}

$order = adm_order_load($params);
$detailPath = '/admin/orders/' . $order['order_number'];
$items = order_items((int) $order['id']);
[$editableItems, $fixedItems] = order_edit_split_items($items);
$fixedSubtotal = 0;
foreach ($fixedItems as $item) {
    $fixedSubtotal += money_paisa((string) $item['line_total']);
}
$reserved = [];
foreach ($editableItems as $item) {
    $reserved[(int) $item['product_size_id']] = ($reserved[(int) $item['product_size_id']] ?? 0) + (int) $item['quantity'];
}
$holdsStock = (string) $order['status'] !== 'cancelled' && $order['stock_restored_at'] === null;
$sizeOptions = order_form_size_options($holdsStock ? $reserved : array_fill_keys(array_keys($reserved), 0));
$paymentStatuses = ORDER_EDIT_PAYMENT_STATUSES;
if (!isset($paymentStatuses[(string) $order['payment_status']])) {
    $paymentStatuses = [(string) $order['payment_status'] => adm_order_payment_label((string) $order['payment_status'])] + $paymentStatuses;
}
$allowedMethods = array_values(array_unique(array_merge(PAYMENT_METHODS, [(string) $order['payment_method']])));
$form = order_edit_form_from_order($order, $editableItems);
$errors = [];

if (request_method() === 'POST') {
    $form = order_edit_input(count($form['lines']));
    $phoneNormalized = null;
    $errors = order_edit_validate($form, $sizeOptions, $phoneNormalized, $allowedMethods, $paymentStatuses, $fixedSubtotal);
    if ($errors === []) {
        try {
            $result = order_edit_save($order, $form, order_edit_lines($form), $phoneNormalized);
            $fresh = order_find((int) $order['id']);
            adm_order_log($order, 'order.edit', 'Edited order ' . $order['order_number'] . ': ' . order_edit_items_summary($result['before']) . ' → ' . order_edit_items_summary(order_items((int) $order['id'])) . '; total ' . money((string) $order['grand_total']) . ' → ' . money((string) $result['after_total']), $order, $fresh);
            flash('success', 'Order ' . $order['order_number'] . ' saved. Total ' . money((string) $result['after_total']) . '. Reprint the invoice if you already sent one.');
            redirect($detailPath, 303);
        } catch (Throwable $e) {
            $conflict = stock_conflict_details($e);
            if ($conflict === null) {
                log_write('error', 'order edit failed', ['order' => $order['order_number'], 'error' => $e->getMessage()]);
                $errors['line_0'] = 'The order could not be saved. Nothing was changed. Try again.';
            } else {
                $row = cart_load_rows([$conflict['size_id']])[$conflict['size_id']] ?? null;
                $errors['line_0'] = 'Not enough stock of ' . ($row === null ? 'one perfume' : $row['product_name'] . ' ' . $row['size_label']) . ': only ' . (int) $conflict['available'] . ' available. Nothing was changed. Lower the quantity or add stock first.';
            }
        }
    }
}

$methodLabels = [];
foreach ($allowedMethods as $method) {
    $methodLabels[] = $method;
}

render_admin('order-edit.php', [
    'order' => $order,
    'form' => $form,
    'errors' => $errors,
    'problems' => [],
    'priced' => null,
    'sizeOptions' => $sizeOptions,
    'methods' => $methodLabels,
    'paymentStatusOptions' => $paymentStatuses,
    'cities' => checkout_cities(),
    'fixedItems' => $fixedItems,
], ['title' => 'Edit ' . $order['order_number'], 'body_class' => 'admin t-light admin-order-edit has-actionbar']);
