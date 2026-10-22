<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/order-actions.php';
require_once __DIR__ . '/order-form.php';

const ORDER_EDIT_ITEM_STATUSES = ['pending', 'confirmed'];
const ORDER_EDIT_LOCKED_STATUSES = ['delivered', 'cancelled'];

function order_edit_items_lock(array $order): string
{
    if (!in_array((string) $order['status'], ORDER_EDIT_ITEM_STATUSES, true)) {
        return 'Perfumes and payment can only be changed before the order is packed. You can still update the customer details.';
    }
    if ((string) $order['payment_status'] !== 'unpaid') {
        return 'This order already has a payment recorded, so its perfumes and total are locked. You can still update the customer details.';
    }
    if (!empty($order['coupon_code'])) {
        return 'This order used coupon ' . $order['coupon_code'] . ', so its perfumes are locked. To change them, cancel it and create a new phone order.';
    }
    if (!empty($order['stock_restored_at'])) {
        return 'Stock for this order was already returned, so its perfumes are locked.';
    }
    return '';
}

function order_edit_pairs(array $items): array
{
    $pairs = [];
    foreach ($items as $item) {
        if ($item['product_size_id'] !== null) {
            $id = (int) $item['product_size_id'];
            $pairs[$id] = ($pairs[$id] ?? 0) + (int) $item['quantity'];
        }
    }
    ksort($pairs);
    return $pairs;
}

function order_edit_form_from_order(array $order, array $items): array
{
    $form = order_form_blank();
    foreach (['customer_name', 'customer_phone', 'customer_email', 'city', 'address', 'postal_code', 'customer_note', 'payment_method'] as $key) {
        $form[$key] = (string) ($order[$key] ?? '');
    }
    $lines = [];
    foreach (order_edit_pairs($items) as $sizeId => $qty) {
        $lines[] = ['size_id' => $sizeId, 'qty' => $qty];
    }
    for ($i = count($lines); $i < ORDER_FORM_LINES; $i++) {
        $lines[] = ['size_id' => 0, 'qty' => 1];
    }
    $form['lines'] = array_slice($lines, 0, max(ORDER_FORM_LINES, count($lines)));
    return $form;
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

function order_edit_reprice(array $oldItems, array $cart, string $phoneNormalized): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        stock_return_items($oldItems);
        return cart_resolve($cart, true, $phoneNormalized);
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

function order_edit_apply(array $order, array $cart, array $form, string $phoneNormalized): array
{
    $pdo = db();
    $pdo->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
    $pdo->beginTransaction();
    try {
        $locked = db_fetch('SELECT * FROM orders WHERE id = :id FOR UPDATE', ['id' => (int) $order['id']]);
        if ($locked === null || order_edit_items_lock($locked) !== '') {
            $pdo->rollBack();
            return ['outcome' => 'locked'];
        }
        $items = order_items((int) $order['id']);
        stock_return_items($items);
        $priced = cart_resolve($cart, true, $phoneNormalized);
        $problems = order_form_price_problems($priced);
        $token = pricing_reprice_token($priced['grand_total_paisa'], cart_fingerprint($priced));
        if ($problems !== [] || $form['price_total'] !== (string) $priced['grand_total_paisa'] || !hash_equals($token, $form['price_token'])) {
            $pdo->rollBack();
            return ['outcome' => 'review', 'priced' => $priced, 'token' => $token, 'problems' => $problems];
        }
        $now = now_karachi();
        db_query('DELETE FROM order_items WHERE order_id = :id', ['id' => (int) $order['id']]);
        order_insert_items((int) $order['id'], $priced, $now);
        stock_decrement_lines($priced['lines']);
        db_update('orders', order_edit_detail_columns($form, $phoneNormalized) + [
            'payment_method' => $form['payment_method'],
            'payment_account_snapshot' => payment_method_is_manual($form['payment_method']) ? payment_account_snapshot($form['payment_method']) : null,
            'subtotal' => money_from_paisa($priced['subtotal_paisa']),
            'discount_total' => money_from_paisa($priced['discount_paisa']),
            'shipping_fee' => money_from_paisa($priced['shipping_paisa']),
            'grand_total' => money_from_paisa($priced['grand_total_paisa']),
            'item_count' => $priced['item_count'],
            'updated_at' => $now,
        ], ['id' => (int) $order['id']]);
        $pdo->commit();
        return ['outcome' => 'saved', 'priced' => $priced, 'before' => $items];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function order_edit_items_summary(array $items): string
{
    $parts = [];
    foreach ($items as $item) {
        $parts[] = $item['product_name'] . ' ' . $item['size_label'] . ' x' . (int) $item['quantity'];
    }
    return implode(', ', $parts);
}

$order = adm_order_load($params);
$detailPath = '/admin/orders/' . $order['order_number'];
if (in_array((string) $order['status'], ORDER_EDIT_LOCKED_STATUSES, true)) {
    flash('error', ucfirst((string) $order['status']) . ' orders cannot be edited.');
    redirect($detailPath, 303);
}
$lockNote = order_edit_items_lock($order);
$itemsEditable = $lockNote === '';
$oldItems = order_items((int) $order['id']);
$reserved = order_edit_pairs($oldItems);
if ($itemsEditable && count($reserved) > ORDER_FORM_LINES) {
    $lockNote = 'This order has more than ' . ORDER_FORM_LINES . ' different perfumes, so they can only be changed by cancelling it and creating a new phone order. You can still update the customer details.';
    $itemsEditable = false;
}
$sizeOptions = order_form_size_options($reserved);
$form = order_edit_form_from_order($order, $oldItems);
$errors = [];
$problems = [];
$priced = null;

if (request_method() === 'POST') {
    $posted = order_form_input();
    if (!$itemsEditable) {
        $posted['lines'] = $form['lines'];
        $posted['payment_method'] = $form['payment_method'];
    }
    $posted['coupon_code'] = '';
    $form = $posted;
    $phoneNormalized = null;
    $allowedMethods = array_values(array_unique(array_merge(payment_methods_enabled(), [(string) $order['payment_method']])));
    $errors = order_form_validate($form, $sizeOptions, $phoneNormalized, $itemsEditable, $allowedMethods);
    if ($errors === []) {
        $cart = order_form_cart($form);
        $newPairs = [];
        foreach ($cart['items'] as $pair) {
            $newPairs[(int) $pair['size_id']] = (int) $pair['qty'];
        }
        ksort($newPairs);
        $itemsChanged = $itemsEditable && ($newPairs !== $reserved || $form['payment_method'] !== (string) $order['payment_method']);
        $beforeDetails = order_edit_detail_columns(order_edit_form_from_order($order, $oldItems), (string) $order['phone_normalized']);
        $afterDetails = order_edit_detail_columns($form, $phoneNormalized);
        try {
            if ($itemsChanged) {
                $result = order_edit_apply($order, $cart, $form, $phoneNormalized);
                if ($result['outcome'] === 'saved') {
                    adm_order_log($order, 'order.edit', 'Edited order ' . $order['order_number'] . ': ' . order_edit_items_summary($result['before']) . ' → ' . order_edit_items_summary(order_items((int) $order['id'])) . '; total ' . money((string) $order['grand_total']) . ' → ' . money(money_from_paisa($result['priced']['grand_total_paisa'])), $beforeDetails + ['payment_method' => $order['payment_method'], 'grand_total' => $order['grand_total']], $afterDetails + ['payment_method' => $form['payment_method'], 'grand_total' => money_from_paisa($result['priced']['grand_total_paisa'])]);
                    flash('success', 'Order ' . $order['order_number'] . ' updated. New total ' . money(money_from_paisa($result['priced']['grand_total_paisa'])) . '. Reprint the invoice if you already sent one.');
                    redirect($detailPath, 303);
                }
                if ($result['outcome'] === 'locked') {
                    flash('error', 'This order changed while you were editing it, so nothing was saved. Check it and try again.');
                    redirect($detailPath, 303);
                }
                $priced = $result['priced'];
                $problems = $result['problems'];
                if ($problems === []) {
                    $problems[] = 'Check the new total below. Press Save changes again to apply it.';
                }
                $form['price_total'] = (string) $priced['grand_total_paisa'];
                $form['price_token'] = $result['token'];
            } else {
                if ($afterDetails !== $beforeDetails) {
                    db_transaction(static function () use ($order, $afterDetails): void {
                        db_update('orders', $afterDetails + ['updated_at' => now_karachi()], ['id' => (int) $order['id']]);
                    });
                    adm_order_log($order, 'order.edit', 'Edited customer details on ' . $order['order_number'], $beforeDetails, $afterDetails);
                    flash('success', 'Customer details saved on ' . $order['order_number'] . '.');
                } else {
                    flash('info', 'Nothing changed.');
                }
                redirect($detailPath, 303);
            }
        } catch (Throwable $e) {
            $conflict = stock_conflict_details($e);
            if ($conflict === null) {
                log_write('error', 'order edit failed', ['order' => $order['order_number'], 'error' => $e->getMessage()]);
                $problems[] = 'The order could not be saved. Nothing was changed. Try again.';
            } else {
                $problems[] = 'One of the perfumes just sold out (only ' . (int) $conflict['available'] . ' left). Nothing was changed. Adjust the quantity and save again.';
            }
            $priced = order_edit_reprice(order_items((int) $order['id']), $cart, $phoneNormalized);
            $form['price_total'] = (string) $priced['grand_total_paisa'];
            $form['price_token'] = pricing_reprice_token($priced['grand_total_paisa'], cart_fingerprint($priced));
        }
    }
}

render_admin('order-edit.php', [
    'order' => $order,
    'form' => $form,
    'errors' => $errors,
    'problems' => $problems,
    'priced' => $priced,
    'sizeOptions' => $sizeOptions,
    'methods' => $itemsEditable ? array_values(array_unique(array_merge(payment_methods_enabled(), [(string) $order['payment_method']]))) : [(string) $order['payment_method']],
    'cities' => checkout_cities(),
    'itemsEditable' => $itemsEditable,
    'lockNote' => $lockNote,
    'lockedItems' => $oldItems,
], ['title' => 'Edit ' . $order['order_number'], 'body_class' => 'admin t-light admin-order-edit has-actionbar']);
