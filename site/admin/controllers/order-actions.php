<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/orders.php';
require_once APP_ROOT . '/app/lib/outbox.php';

const ADM_ORDER_PII_FIELDS = ['customer_name', 'customer_phone', 'phone_normalized', 'customer_email', 'address', 'postal_code'];
const ADM_ORDER_COURIERS = ['TCS', 'Leopards', 'M&P', 'BlueEx', 'PostEx', 'Trax', 'Daewoo', 'Pakistan Post'];
const ADM_ORDER_TRACKING_PATTERN = '/^[A-Za-z0-9\- ]{1,60}$/';
const ADM_ORDER_PROOF_VIEW_LOG_SECONDS = 3600;
const ADM_ORDER_EXPORT_CAP = 5000;
const ADM_ORDER_STATUS_LABELS = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'packing' => 'Packing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
const ADM_ORDER_PAYMENT_LABELS = ['unpaid' => 'Unpaid', 'awaiting_verification' => 'Review needed', 'paid' => 'Paid', 'failed' => 'Not verified', 'refunded' => 'Refunded'];
const ADM_ORDER_STATUS_RANK = ['pending' => 1, 'confirmed' => 2, 'packing' => 3, 'shipped' => 4, 'delivered' => 5, 'cancelled' => 6];

function adm_actor(): array
{
    $admin = auth_user() ?? [];
    return ['by' => 'admin', 'admin_id' => isset($admin['id']) ? (int) $admin['id'] : null, 'admin_username' => (string) ($admin['username'] ?? '')];
}

function adm_order_pii_mask(array $fields): array
{
    foreach ($fields as $key => $value) {
        if (in_array($key, ADM_ORDER_PII_FIELDS, true)) {
            $fields[$key] = '(changed)';
        }
    }
    return $fields;
}

function adm_order_log(?array $order, string $action, string $summary, ?array $before = null, ?array $after = null): void
{
    $actor = adm_actor();
    db_insert('admin_activity_log', [
        'admin_id' => $actor['admin_id'],
        'admin_username' => mb_substr($actor['admin_username'], 0, 64),
        'entity_type' => 'order',
        'entity_id' => $order === null ? null : (int) $order['id'],
        'action' => mb_substr($action, 0, 48),
        'summary' => mb_substr($summary, 0, 255),
        'before_json' => $before === null ? null : json_encode(adm_order_pii_mask($before), JSON_UNESCAPED_UNICODE),
        'after_json' => $after === null ? null : json_encode(adm_order_pii_mask($after), JSON_UNESCAPED_UNICODE),
        'ip_hash' => request_ip_hash(),
        'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        'created_at' => now_karachi(),
    ]);
}

function adm_order_path(array $order, string $suffix = ''): string
{
    return '/admin/orders/' . $order['order_number'] . $suffix;
}

function adm_order_load(array $params): array
{
    $order = order_find_by_number((string) ($params['order'] ?? ''));
    if ($order === null) {
        abort(404);
    }
    return $order;
}

function adm_order_datetime(?string $dt): string
{
    return $dt ? date('j M Y, g:i a', strtotime($dt)) : '';
}

function adm_order_date_compact(?string $dt): string
{
    return $dt ? date('j M, g:i a', strtotime($dt)) : '';
}

function adm_order_status_label(string $status): string
{
    return ADM_ORDER_STATUS_LABELS[$status] ?? ucfirst($status);
}

function adm_order_payment_label(string $status): string
{
    return ADM_ORDER_PAYMENT_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function adm_order_reconciles(array $order): bool
{
    $expected = money_paisa((string) $order['subtotal']) - money_paisa((string) $order['discount_total']) + money_paisa((string) $order['shipping_fee']) + money_paisa((string) $order['cod_fee']);
    return $expected === money_paisa((string) $order['grand_total']);
}

function adm_order_hold_days(array $order): ?int
{
    if (!payment_method_is_manual((string) $order['payment_method']) || $order['status'] !== 'pending' || $order['payment_status'] === 'paid') {
        return null;
    }
    $age = time() - strtotime((string) $order['created_at']);
    if ($age < setting_int('manual_hold_hours', 48) * 3600) {
        return null;
    }
    return max(1, (int) floor($age / 86400));
}

function adm_order_first_name(array $order): string
{
    $parts = preg_split('/\s+/', trim((string) $order['customer_name'])) ?: [];
    return $parts[0] ?? '';
}

function adm_order_items_lines(array $items): string
{
    $lines = [];
    foreach ($items as $item) {
        $lines[] = $item['product_name'] . ' ' . $item['size_label'] . ' ×' . (int) $item['quantity'];
    }
    return implode("\n", $lines);
}

function adm_order_items_summary(array $items): string
{
    return str_replace("\n", ' | ', adm_order_items_lines($items));
}

function adm_order_whatsapp_digits(array $order): ?string
{
    $digits = preg_replace('/\D+/', '', (string) $order['phone_normalized']) ?? '';
    return strlen($digits) === 12 && str_starts_with($digits, '92') ? $digits : null;
}

function adm_order_whatsapp_link(array $order, string $text): ?string
{
    $digits = adm_order_whatsapp_digits($order);
    return $digits === null ? null : 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
}

function adm_order_whatsapp_messages(array $order, array $items): array
{
    $name = adm_order_first_name($order);
    $number = (string) $order['order_number'];
    $total = money((string) $order['grand_total']);
    $days = trim((string) preg_replace('/\s*working\s+days\s*$/iu', '', (string) setting('delivery_time', '2–4 working days')));
    $trackUrl = SITE_URL . '/track';
    $site = (string) (parse_url(SITE_URL, PHP_URL_HOST) ?: 'skyfragrances.com');
    $sign = "Sky Fragrances — More Than Just A Scent";
    $greet = 'Assalam-o-Alaikum ' . $name . ",\n\n";
    $courier = trim((string) ($order['courier_name'] ?? ''));
    $tracking = trim((string) ($order['tracking_number'] ?? ''));
    $shippedMid = 'Courier: ' . $courier . "\n" . ($tracking === '' ? "The tracking number will follow shortly.\n" : 'Tracking number: ' . $tracking . "\n");
    $shippedMid .= ($order['payment_method'] === 'cod' ? 'Amount to pay on delivery: ' : 'Amount: ') . $total . "\n\n";
    return [
        'confirmed' => ['label' => 'Confirmed', 'text' => $greet . "Thank you for your order with Sky Fragrances.\n\nOrder: " . $number . "\n" . adm_order_items_lines($items) . "\nTotal: " . $total . "\n\nYour order is confirmed and we are preparing it now. Delivery usually takes " . $days . " working days.\n\nYou can track it any time at " . $trackUrl . "\n\n" . $sign],
        'packing' => ['label' => 'Packing', 'text' => $greet . 'Good news — your Sky Fragrances order ' . $number . " is being packed today and will be handed to the courier shortly.\n\nWe will send you the tracking number as soon as it ships.\n\n" . $sign],
        'shipped' => ['label' => 'Shipped', 'text' => $greet . 'Your Sky Fragrances order ' . $number . " has been shipped.\n\n" . $shippedMid . 'Please keep your phone available so the rider can reach you. Track your order at ' . $trackUrl . "\n\n" . $sign],
        'delivered' => ['label' => 'Delivered', 'text' => $greet . 'Your Sky Fragrances order ' . $number . " has been delivered. We hope you love it.\n\nIf you have a moment, a short review on " . $site . " genuinely helps us — and if anything is not right, just reply here and we will sort it out.\n\nThank you for choosing Sky Fragrances."],
        'cancelled' => ['label' => 'Cancelled', 'text' => $greet . 'Your Sky Fragrances order ' . $number . " has been cancelled.\n\nIf this was not what you expected, please reply to this message and we will help you place it again.\n\nSorry for the inconvenience.\n\n" . $sign],
        'request_proof' => ['label' => 'Request payment proof', 'text' => 'We have received your order ' . $number . ' for ' . $total . '. Please send the payment screenshot and transaction ID here so we can confirm it.'],
        'not_verified' => ['label' => 'Payment not verified', 'text' => 'We could not verify the payment for order ' . $number . '. Could you please check the transaction ID and send the screenshot again?'],
    ];
}

function adm_order_whatsapp_default(array $order): string
{
    if (payment_method_is_manual((string) $order['payment_method']) && $order['status'] === 'pending') {
        return $order['payment_status'] === 'failed' ? 'not_verified' : ($order['payment_status'] === 'paid' ? 'confirmed' : 'request_proof');
    }
    return $order['status'] === 'pending' ? 'confirmed' : (string) $order['status'];
}

function adm_order_primary_action(array $order): ?array
{
    return match ((string) $order['status']) {
        'pending' => ['to' => 'confirmed', 'label' => 'Mark confirmed'],
        'confirmed' => ['to' => 'packing', 'label' => 'Mark packing'],
        'packing' => ['to' => 'shipped', 'label' => 'Mark shipped'],
        'shipped' => ['to' => 'delivered', 'label' => 'Mark delivered'],
        default => null,
    };
}

function adm_order_flash_result(array $result, string $successMessage = ''): void
{
    if ($result['ok']) {
        flash('success', $successMessage !== '' ? $successMessage : (string) ($result['message'] ?? 'Saved.'));
        return;
    }
    flash('error', (string) ($result['message'] ?? 'That could not be done.'));
}

function adm_order_post_status(array $order): never
{
    $to = strtolower(trim((string) request_post('to', '')));
    $note = trim((string) request_post('note', ''));
    if (!in_array($to, ORDER_STATUSES, true)) {
        flash('error', 'Choose a valid status.');
        redirect(adm_order_path($order), 303);
    }
    if (!adm_order_reconciles($order)) {
        flash('error', 'Totals do not reconcile. The status cannot be changed until the developer has checked this order.');
        redirect(adm_order_path($order), 303);
    }
    if (!order_transition_allowed((string) $order['status'], $to)) {
        flash('error', "That status change isn't allowed. The order is currently " . adm_order_status_label((string) $order['status']) . '.');
        redirect(adm_order_path($order), 303);
    }
    if (order_transition_requires_reason((string) $order['status'], $to) && $note === '') {
        flash('error', 'A reason is required for this change.');
        redirect(adm_order_path($order), 303);
    }
    $result = order_transition((int) $order['id'], $to, adm_actor(), $note);
    if (!$result['ok']) {
        flash('error', (string) $result['message']);
        redirect(adm_order_path($order), 303);
    }
    $fromLabel = adm_order_status_label((string) $result['from']);
    $toLabel = adm_order_status_label($to);
    $summary = 'Status changed from ' . $fromLabel . ' to ' . $toLabel . ($note !== '' ? ' (' . $note . ')' : '');
    adm_order_log($order, 'order.status_change', $summary, ['status' => $result['from']], ['status' => $to]);
    if ($to === 'cancelled') {
        if ($result['stock_summary'] !== null) {
            adm_order_log($order, 'order.stock_restored', 'Stock restored on cancel: ' . $result['stock_summary']);
            flash('success', 'Order cancelled. Stock restored: ' . $result['stock_summary'] . '.');
        } elseif ($order['stock_restored_at'] !== null) {
            flash('info', 'Order cancelled. Stock was already restored on ' . adm_order_datetime((string) $order['stock_restored_at']) . ' — nothing changed.');
        } else {
            flash('info', 'Order cancelled. Stock was not restored because the parcel is with the courier — use "Parcel received back" when it returns.');
        }
    } else {
        flash('success', 'Order marked ' . $toLabel . '.');
    }
    redirect(adm_order_path($order), 303);
}

function adm_order_pending_proof(array $order): ?array
{
    foreach (order_proofs((int) $order['id']) as $proof) {
        if ($proof['review_status'] === 'pending') {
            return $proof;
        }
    }
    return null;
}

function adm_order_post_payment(array $order): never
{
    $action = (string) request_post('action', '');
    $note = trim((string) request_post('note', ''));
    $orderId = (int) $order['id'];
    $before = ['payment_status' => $order['payment_status']];
    if ($action === 'mark_paid') {
        $amount = trim((string) request_post('amount', ''));
        $amount = $amount === '' ? null : str_replace(',', '', $amount);
        if ($amount !== null && !preg_match('/^\d{1,8}(\.\d{1,2})?$/', $amount)) {
            flash('error', 'Enter the amount received as a plain number, for example 4950.');
            redirect(adm_order_path($order), 303);
        }
        $proof = adm_order_pending_proof($order);
        $result = $proof !== null && ($amount === null || money_paisa($amount) === money_paisa((string) $order['grand_total']))
            ? payment_review($orderId, (int) $proof['id'], true, adm_actor(), $note)
            : payment_mark_paid($orderId, adm_actor(), $amount, $note);
        if ($result['ok']) {
            $summary = 'Payment marked as received — ' . payment_method_label((string) $order['payment_method']) . ($amount !== null ? ', ' . money($amount) : '');
            adm_order_log($order, 'order.mark_paid', $summary, $before, ['payment_status' => 'paid']);
        }
        adm_order_flash_result($result, 'Payment recorded. The order status is unchanged — confirm it when you are ready.');
        redirect(adm_order_path($order), 303);
    }
    if ($action === 'reject') {
        if ($note === '') {
            flash('error', 'A short reason is required when rejecting a payment.');
            redirect(adm_order_path($order), 303);
        }
        $proof = adm_order_pending_proof($order);
        $result = payment_review($orderId, $proof === null ? null : (int) $proof['id'], false, adm_actor(), $note);
        if ($result['ok']) {
            adm_order_log($order, 'order.payment_rejected', 'Payment proof rejected: ' . $note, $before, ['payment_status' => 'failed']);
        }
        adm_order_flash_result($result, 'Payment marked as not verified. Send the "Payment not verified" WhatsApp message below.');
        redirect(adm_order_path($order), 303);
    }
    if ($action === 'refund') {
        if ($order['payment_status'] !== 'paid') {
            flash('error', 'Only a paid order can be marked refunded.');
            redirect(adm_order_path($order), 303);
        }
        if ($note === '') {
            flash('error', 'A reason is required to record a refund.');
            redirect(adm_order_path($order), 303);
        }
        db_transaction(static function () use ($orderId, $note): void {
            $now = now_karachi();
            $moved = db_query("UPDATE orders SET payment_status = 'refunded', updated_at = :now WHERE id = :id AND payment_status = 'paid'", ['now' => $now, 'id' => $orderId])->rowCount() === 1;
            if (!$moved) {
                throw new RuntimeException('Order is no longer paid');
            }
            order_history_insert($orderId, 'payment_status', 'paid', 'refunded', 'Refund recorded: ' . $note, adm_actor(), $now);
        });
        adm_order_log($order, 'order.refunded', 'Payment marked refunded: ' . $note, $before, ['payment_status' => 'refunded']);
        flash('success', 'Refund recorded. Money movement happens outside this system.');
        redirect(adm_order_path($order), 303);
    }
    flash('error', 'Unknown payment action.');
    redirect(adm_order_path($order), 303);
}

function adm_order_shipping_input(): array
{
    $courier = mb_substr(trim((string) request_post('courier_name', '')), 0, 60);
    $tracking = trim((string) request_post('tracking_number', ''));
    $url = trim((string) request_post('tracking_url', ''));
    $errors = [];
    if ($tracking !== '' && !preg_match(ADM_ORDER_TRACKING_PATTERN, $tracking)) {
        $errors[] = 'Tracking number may only contain letters, digits, spaces and dashes (max 60).';
    }
    if ($url !== '' && (!str_starts_with(strtolower($url), 'https://') || mb_strlen($url) > 255 || filter_var($url, FILTER_VALIDATE_URL) === false)) {
        $errors[] = 'Tracking link must start with https:// and be a valid address.';
    }
    return ['courier_name' => $courier, 'tracking_number' => $tracking, 'tracking_url' => $url, 'errors' => $errors];
}

function adm_order_post_shipping(array $order): never
{
    $input = adm_order_shipping_input();
    if ($input['errors'] !== []) {
        flash('error', implode(' ', $input['errors']));
        redirect(adm_order_path($order), 303);
    }
    $markShipped = request_post('mark_shipped') === '1';
    if ($markShipped) {
        if (!adm_order_reconciles($order)) {
            flash('error', 'Totals do not reconcile. Do not ship this order until the developer has checked it.');
            redirect(adm_order_path($order), 303);
        }
        if (!order_transition_allowed((string) $order['status'], 'shipped')) {
            flash('error', "That status change isn't allowed. The order is currently " . adm_order_status_label((string) $order['status']) . '.');
            redirect(adm_order_path($order), 303);
        }
        if ($input['courier_name'] === '' || $input['tracking_number'] === '') {
            flash('error', 'Add the courier name and tracking number before marking the order shipped.');
            redirect(adm_order_path($order), 303);
        }
        $result = order_transition((int) $order['id'], 'shipped', adm_actor(), '', $input);
        if ($result['ok']) {
            adm_order_log($order, 'order.status_change', 'Status changed from ' . adm_order_status_label((string) $result['from']) . ' to Shipped (' . $input['courier_name'] . ' ' . $input['tracking_number'] . ')', ['status' => $result['from'], 'courier_name' => $order['courier_name'], 'tracking_number' => $order['tracking_number']], ['status' => 'shipped', 'courier_name' => $input['courier_name'], 'tracking_number' => $input['tracking_number']]);
        }
        adm_order_flash_result($result, 'Order marked Shipped. Send the tracking on WhatsApp below.');
        redirect(adm_order_path($order), 303);
    }
    if (in_array($order['status'], ['delivered', 'cancelled'], true)) {
        flash('error', 'Courier details cannot be changed on a ' . strtolower(adm_order_status_label((string) $order['status'])) . ' order.');
        redirect(adm_order_path($order), 303);
    }
    $before = ['courier_name' => $order['courier_name'], 'tracking_number' => $order['tracking_number'], 'tracking_url' => $order['tracking_url']];
    $after = ['courier_name' => $input['courier_name'] === '' ? null : $input['courier_name'], 'tracking_number' => $input['tracking_number'] === '' ? null : $input['tracking_number'], 'tracking_url' => $input['tracking_url'] === '' ? null : $input['tracking_url']];
    if ($before == $after) {
        flash('info', 'No changes to save.');
        redirect(adm_order_path($order), 303);
    }
    $now = now_karachi();
    db_transaction(static function () use ($order, $after, $now): void {
        db_update('orders', $after + ['updated_at' => $now], ['id' => (int) $order['id']]);
        if ($order['status'] === 'shipped') {
            order_history_insert((int) $order['id'], 'status', 'shipped', 'shipped', 'Tracking updated: ' . trim(($after['courier_name'] ?? '') . ' ' . ($after['tracking_number'] ?? '')), adm_actor(), $now);
        }
    });
    adm_order_log($order, 'order.tracking_set', 'Courier details saved: ' . trim(($after['courier_name'] ?? '—') . ' ' . ($after['tracking_number'] ?? '')), $before, $after);
    flash('success', 'Courier details saved.');
    redirect(adm_order_path($order), 303);
}

function adm_order_contact_frozen(array $order): bool
{
    return in_array($order['status'], ['shipped', 'delivered', 'cancelled'], true);
}

function adm_order_post_notes(array $order): never
{
    $action = (string) request_post('action', 'note');
    if ($action === 'note') {
        $body = trim((string) request_post('body', ''));
        if ($body === '' || mb_strlen($body) > 1000) {
            flash('error', 'Write a note of up to 1,000 characters.');
            redirect(adm_order_path($order, '#notes'), 303);
        }
        $actor = adm_actor();
        db_insert('order_notes', [
            'order_id' => (int) $order['id'],
            'body' => $body,
            'author_type' => 'admin',
            'admin_id' => $actor['admin_id'],
            'admin_name' => mb_substr($actor['admin_username'], 0, 64),
            'is_pinned' => request_post('pin') === '1' ? 1 : 0,
            'created_at' => now_karachi(),
        ]);
        adm_order_log($order, 'order.note_edited', 'Internal note added (' . mb_strlen($body) . ' chars)');
        flash('success', 'Note added.');
        redirect(adm_order_path($order, '#notes'), 303);
    }
    if ($action !== 'contact') {
        flash('error', 'Unknown notes action.');
        redirect(adm_order_path($order), 303);
    }
    if (adm_order_contact_frozen($order)) {
        flash('error', 'Customer details are frozen once the order is shipped. Talk to the courier instead.');
        redirect(adm_order_path($order), 303);
    }
    $name = mb_substr(trim((string) request_post('customer_name', '')), 0, 120);
    $phone = mb_substr(trim((string) request_post('customer_phone', '')), 0, 24);
    $email = mb_substr(trim((string) request_post('customer_email', '')), 0, 190);
    $city = mb_substr(trim((string) request_post('city', '')), 0, 80);
    $address = mb_substr(trim((string) request_post('address', '')), 0, 400);
    $postal = mb_substr(trim((string) request_post('postal_code', '')), 0, 12);
    $errors = [];
    if (mb_strlen($name) < 2) {
        $errors[] = 'Name is required.';
    }
    $normalized = phone_normalize($phone);
    if (!preg_match('/^\+92\d{10}$/', $normalized)) {
        $errors[] = 'Phone must be a Pakistani mobile number, for example 0300 1234567.';
    }
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Email address is not valid.';
    }
    if ($city === '' || mb_strlen($address) < 5) {
        $errors[] = 'City and address are required.';
    }
    if ($errors !== []) {
        flash('error', implode(' ', $errors));
        redirect(adm_order_path($order, '#customer'), 303);
    }
    $after = ['customer_name' => $name, 'customer_phone' => $phone, 'phone_normalized' => $normalized, 'customer_email' => $email === '' ? null : $email, 'city' => $city, 'address' => $address, 'postal_code' => $postal === '' ? null : $postal];
    $before = array_intersect_key($order, $after);
    $changed = array_keys(array_filter($after, static fn ($v, $k) => (string) ($before[$k] ?? '') !== (string) ($v ?? ''), ARRAY_FILTER_USE_BOTH));
    if ($changed === []) {
        flash('info', 'No changes to save.');
        redirect(adm_order_path($order, '#customer'), 303);
    }
    $now = now_karachi();
    db_transaction(static function () use ($order, $after, $changed, $now): void {
        db_update('orders', $after + ['updated_at' => $now], ['id' => (int) $order['id']]);
        order_history_insert((int) $order['id'], 'status', (string) $order['status'], (string) $order['status'], 'Customer details corrected: ' . implode(', ', $changed), adm_actor(), $now);
    });
    adm_order_log($order, 'order.contact_edited', 'Customer details corrected: ' . implode(', ', $changed), array_intersect_key($before, array_flip($changed)), array_intersect_key($after, array_flip($changed)));
    flash('success', 'Customer details updated.');
    redirect(adm_order_path($order, '#customer'), 303);
}

function adm_order_post_restore_stock(array $order): never
{
    $result = stock_restore_order((int) $order['id'], adm_actor());
    if ($result['ok']) {
        adm_order_log($order, 'order.stock_restored', 'Stock restored manually: ' . $result['summary'], ['stock_restored_at' => null], ['stock_restored_at' => $result['restored_at']]);
        flash('success', 'Stock restored: ' . $result['summary'] . '.');
    } elseif ($result['reason'] === 'already_restored') {
        flash('info', 'Stock was already restored on ' . adm_order_datetime((string) $result['restored_at']) . ' — nothing changed.');
    } elseif ($result['reason'] === 'not_cancelled') {
        flash('error', 'Stock can only be restored on a cancelled order.');
    } else {
        flash('error', 'Stock could not be restored.');
    }
    redirect(adm_order_path($order), 303);
}

function adm_order_latest_proof(array $order): ?array
{
    $proofs = order_proofs((int) $order['id']);
    return $proofs[0] ?? null;
}

function adm_order_proof_thumbnail(string $fullPath): ?string
{
    $thumb = substr($fullPath, 0, -4) . '-320.jpg';
    if (is_file($thumb)) {
        return $thumb;
    }
    $src = image_decode($fullPath);
    if ($src === null) {
        return null;
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1, 320 / max(1, $w));
    $outW = max(1, (int) round($w * $scale));
    $outH = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($outW, $outH);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $outW, $outH, $w, $h);
    imagedestroy($src);
    $ok = image_write_jpeg($dst, $thumb, 80);
    imagedestroy($dst);
    return $ok ? $thumb : null;
}

function adm_order_proof_view_logged_recently(array $order): bool
{
    $actor = adm_actor();
    return db_exists(
        "SELECT 1 FROM admin_activity_log WHERE entity_type = 'order' AND entity_id = :id AND action = 'order.proof_viewed' AND admin_id = :admin AND created_at > :since LIMIT 1",
        ['id' => (int) $order['id'], 'admin' => $actor['admin_id'], 'since' => date('Y-m-d H:i:s', time() - ADM_ORDER_PROOF_VIEW_LOG_SECONDS)]
    );
}

function adm_order_stream_proof(array $order): never
{
    $proof = adm_order_latest_proof($order);
    $relPath = $proof === null ? null : (string) ($proof['file_path'] ?? '');
    $full = $relPath === null || $relPath === '' ? null : image_proof_path($relPath);
    if ($full === null) {
        abort(404);
    }
    $root = realpath(APP_ROOT . '/storage/proofs');
    $real = realpath($full);
    if ($root === false || $real === false || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
        abort(404);
    }
    if (request_query('w') === '320') {
        $thumb = adm_order_proof_thumbnail($real);
        $real = $thumb ?? $real;
    }
    if (!adm_order_proof_view_logged_recently($order)) {
        adm_order_log($order, 'order.proof_viewed', 'Payment proof viewed');
    }
    response_clear_buffers();
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . (string) filesize($real));
    header('Content-Disposition: inline; filename="proof-' . $order['order_number'] . '.jpg"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    header("Content-Security-Policy: default-src 'none'; img-src 'self'");
    readfile($real);
    exit;
}

function adm_order_handle_post(array $route, array $order): never
{
    match ((string) ($route['name'] ?? '')) {
        'admin.orders.status' => adm_order_post_status($order),
        'admin.orders.payment' => adm_order_post_payment($order),
        'admin.orders.shipping' => adm_order_post_shipping($order),
        'admin.orders.notes' => adm_order_post_notes($order),
        'admin.orders.restore_stock', 'admin.orders.stock_back' => adm_order_post_restore_stock($order),
        default => abort(404),
    };
}

function adm_orders_filters(array $get): array
{
    $statusRaw = $get['status'] ?? [];
    $statusRaw = is_array($statusRaw) ? $statusRaw : [$statusRaw];
    $status = array_values(array_intersect(array_map('strval', $statusRaw), ORDER_STATUSES));
    $method = (string) ($get['method'] ?? '');
    $pay = (string) ($get['pay'] ?? '');
    $dateOk = static fn (string $d): bool => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1 && checkdate((int) substr($d, 5, 2), (int) substr($d, 8, 2), (int) substr($d, 0, 4));
    $from = (string) ($get['from'] ?? '');
    $to = (string) ($get['to'] ?? '');
    $per = (int) ($get['per'] ?? 25);
    $ids = [];
    foreach (explode(',', (string) ($get['ids'] ?? '')) as $id) {
        if (ctype_digit($id) && (int) $id > 0) {
            $ids[] = (int) $id;
        }
    }
    return [
        'status' => $status,
        'method' => in_array($method, PAYMENT_METHODS, true) ? $method : '',
        'pay' => in_array($pay, PAYMENT_STATUSES, true) ? $pay : '',
        'from' => $dateOk($from) ? $from : '',
        'to' => $dateOk($to) ? $to : '',
        'proof' => in_array($get['proof'] ?? '', ['yes', 'no'], true) ? (string) $get['proof'] : '',
        'sort' => in_array($get['sort'] ?? '', ['date_desc', 'date_asc', 'total_desc', 'total_asc', 'status'], true) ? (string) $get['sort'] : 'date_desc',
        'per' => in_array($per, [25, 50, 100], true) ? $per : 25,
        'page' => max(1, (int) ($get['page'] ?? 1)),
        'q' => mb_substr(trim((string) ($get['q'] ?? '')), 0, 60),
        'ids' => array_slice(array_unique($ids), 0, 100),
    ];
}

function adm_orders_search_kind(string $q): string
{
    if ($q === '') {
        return 'none';
    }
    if (preg_match(ORDER_NUMBER_PATTERN, strtoupper($q))) {
        return 'number';
    }
    if (preg_match('/^[0-9+\-\s]{7,}$/', $q)) {
        return 'phone';
    }
    return 'name';
}

function adm_orders_where(array $f, string $nameMode = 'prefix'): array
{
    $where = [];
    $params = [];
    if ($f['status'] !== []) {
        $marks = [];
        foreach ($f['status'] as $n => $status) {
            $marks[] = ':st' . $n;
            $params['st' . $n] = $status;
        }
        $where[] = 'o.status IN (' . implode(',', $marks) . ')';
    }
    if ($f['method'] !== '') {
        $where[] = 'o.payment_method = :method';
        $params['method'] = $f['method'];
    }
    if ($f['pay'] !== '') {
        $where[] = 'o.payment_status = :pay';
        $params['pay'] = $f['pay'];
    }
    if ($f['from'] !== '') {
        $where[] = 'o.created_at >= :from';
        $params['from'] = $f['from'] . ' 00:00:00';
    }
    if ($f['to'] !== '') {
        $where[] = 'o.created_at <= :to';
        $params['to'] = $f['to'] . ' 23:59:59';
    }
    if ($f['proof'] === 'yes') {
        $where[] = 'EXISTS (SELECT 1 FROM payment_proofs pp WHERE pp.order_id = o.id)';
    } elseif ($f['proof'] === 'no') {
        $where[] = 'NOT EXISTS (SELECT 1 FROM payment_proofs pp WHERE pp.order_id = o.id)';
    }
    if ($f['ids'] !== []) {
        $marks = [];
        foreach ($f['ids'] as $n => $id) {
            $marks[] = ':id' . $n;
            $params['id' . $n] = $id;
        }
        $where[] = 'o.id IN (' . implode(',', $marks) . ')';
    }
    $kind = adm_orders_search_kind($f['q']);
    if ($kind === 'number') {
        $where[] = 'o.order_number = :number';
        $params['number'] = strtoupper($f['q']);
    } elseif ($kind === 'phone') {
        $normalized = phone_normalize($f['q']);
        if (strlen($normalized) >= 13) {
            $where[] = 'o.phone_normalized = :phone';
            $params['phone'] = $normalized;
        } else {
            $where[] = 'o.phone_normalized LIKE :phone';
            $params['phone'] = $normalized . '%';
        }
    } elseif ($kind === 'name') {
        $escaped = addcslashes($f['q'], '%_\\');
        $where[] = 'o.customer_name LIKE :name';
        $params['name'] = ($nameMode === 'contains' ? '%' : '') . $escaped . '%';
    }
    $order = match ($f['sort']) {
        'date_asc' => 'o.created_at ASC, o.id ASC',
        'total_desc' => 'o.grand_total DESC, o.id DESC',
        'total_asc' => 'o.grand_total ASC, o.id ASC',
        'status' => "CASE o.status WHEN 'pending' THEN 1 WHEN 'confirmed' THEN 2 WHEN 'packing' THEN 3 WHEN 'shipped' THEN 4 WHEN 'delivered' THEN 5 ELSE 6 END ASC, o.created_at DESC",
        default => 'o.created_at DESC, o.id DESC',
    };
    return ['where' => $where === [] ? '1=1' : implode(' AND ', $where), 'params' => $params, 'order' => $order];
}

function adm_orders_summary_text(array $f): string
{
    $parts = [];
    foreach (['status' => 'status', 'method' => 'method', 'pay' => 'payment', 'from' => 'from', 'to' => 'to', 'proof' => 'proof', 'q' => 'search'] as $key => $label) {
        $value = is_array($f[$key]) ? implode('+', $f[$key]) : (string) $f[$key];
        if ($value !== '') {
            $parts[] = $label . '=' . $value;
        }
    }
    if ($f['ids'] !== []) {
        $parts[] = count($f['ids']) . ' selected ids';
    }
    return $parts === [] ? 'no filters' : implode(', ', $parts);
}

function adm_orders_unpaid_transfer_rows(?int $hours = null): array
{
    $hours = $hours ?? setting_int('manual_hold_hours', 48);
    return db_fetch_all(
        "SELECT id, order_number, customer_name, city, payment_method, payment_status, grand_total, created_at FROM orders WHERE payment_method IN ('bank', 'jazzcash', 'easypaisa') AND status = 'pending' AND payment_status IN ('unpaid', 'awaiting_verification', 'failed') AND created_at < :before ORDER BY created_at ASC",
        ['before' => date('Y-m-d H:i:s', time() - max(1, $hours) * 3600)]
    );
}

function adm_orders_items_by_order(array $orderIds): array
{
    if ($orderIds === []) {
        return [];
    }
    $marks = [];
    $params = [];
    foreach (array_values($orderIds) as $n => $id) {
        $marks[] = ':o' . $n;
        $params['o' . $n] = (int) $id;
    }
    $grouped = [];
    foreach (db_fetch_all('SELECT * FROM order_items WHERE order_id IN (' . implode(',', $marks) . ') ORDER BY order_id ASC, id ASC', $params) as $item) {
        $grouped[(int) $item['order_id']][] = $item;
    }
    return $grouped;
}

function adm_order_print_payload(array $order, ?array $items = null): array
{
    $items = $items ?? order_items((int) $order['id']);
    return [
        'order' => $order,
        'items' => $items,
        'itemCount' => array_sum(array_map(static fn (array $i): int => (int) $i['quantity'], $items)),
        'isCod' => $order['payment_method'] === 'cod',
        'isPaid' => $order['payment_status'] === 'paid',
        'collectAmount' => $order['payment_method'] === 'cod' && $order['payment_status'] !== 'paid' && $order['status'] !== 'cancelled' ? money((string) $order['grand_total']) : null,
    ];
}
