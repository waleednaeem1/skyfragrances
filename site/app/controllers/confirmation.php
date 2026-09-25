<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/orders.php';
require_once APP_ROOT . '/app/lib/outbox.php';

function confirmation_miss(string $orderNumber): never
{
    rate_limit_record('track', request_ip_hash());
    rate_limit_purge();
    redirect('/track?order=' . eu($orderNumber), 303);
}

function confirmation_view_data(array $order, array $state = []): array
{
    $name = trim((string) $order['customer_name']);
    $isManual = payment_method_is_manual((string) $order['payment_method']);
    $lastOrder = $_SESSION['last_order'] ?? null;
    $emailSent = is_array($lastOrder) && ($lastOrder['number'] ?? '') === $order['order_number'] && !empty($lastOrder['email_sent']);
    if (!$emailSent && $order['customer_email'] !== null) {
        $emailSent = outbox_order_mail_status((int) $order['id'], 'order-customer') === 'sent';
    }
    $proofs = order_proofs((int) $order['id']);
    $latestProof = $proofs[0] ?? null;
    return [
        'heading' => 'Thank you, ' . ($name === '' ? 'friend' : explode(' ', $name)[0]) . '.',
        'order' => $order,
        'orderNumber' => $order['order_number'],
        'confirmationUrl' => order_confirmation_url($order),
        'firstName' => $name === '' ? '' : explode(' ', $name)[0],
        'placedAt' => date_long($order['created_at']),
        'status' => $order['status'],
        'statusLabel' => ucfirst((string) $order['status']),
        'paymentStatus' => $order['payment_status'],
        'paymentMethod' => $order['payment_method'],
        'paymentMethodLabel' => payment_method_label((string) $order['payment_method']),
        'isManual' => $isManual,
        'isCod' => $order['payment_method'] === 'cod',
        'isPaid' => $order['payment_status'] === 'paid',
        'items' => order_view_items((int) $order['id']),
        'totals' => order_view_totals($order),
        'grandTotalDisplay' => money((string) $order['grand_total']),
        'showAddress' => order_address_visible($order),
        'address' => ['name' => $order['customer_name'], 'phone' => $order['customer_phone'], 'address' => $order['address'], 'city' => $order['city'], 'postal_code' => $order['postal_code'], 'note' => $order['customer_note']],
        'emailAddress' => $order['customer_email'],
        'emailSent' => $emailSent,
        'whatsappUrl' => order_whatsapp_url("Assalam-o-Alaikum! I've just placed order " . $order['order_number'] . '.'),
        'trackUrl' => url('/track?order=' . eu((string) $order['order_number'])),
        'accountLines' => $isManual ? payment_account_lines((string) $order['payment_method']) : [],
        'accountSnapshot' => $order['payment_account_snapshot'],
        'manualPaymentNote' => (string) setting('manual_payment_note', ''),
        'deliveryTime' => (string) setting('delivery_time', '2–4 working days'),
        'transactionRef' => $order['payment_reference'],
        'latestProof' => $latestProof === null ? null : ['review_status' => $latestProof['review_status'], 'review_note' => $latestProof['review_note'], 'created_at' => date_long($latestProof['created_at']), 'file_missing' => $latestProof['file_path'] === null],
        'canUploadProof' => payment_proof_can_upload($order) && !payment_proof_upload_limited((int) $order['id']),
        'proofOld' => $state['proofOld'] ?? ['payment_reference' => (string) ($order['payment_reference'] ?? ''), 'sender_name' => ''],
        'proofErrors' => $state['proofErrors'] ?? [],
        'notice' => $state['notice'] ?? null,
        'trapField' => form_trap_field(),
    ];
}

$orderNumber = strtoupper($params['order']);
$token = request_method() === 'POST' ? request_string('t', request_query_string('t', '')) : request_query_string('t', '');
$order = order_find_by_number($orderNumber);

if ($order === null || !preg_match('/^[a-f0-9]{32}$/', $token) || !hash_equals((string) $order['access_token'], $token)) {
    confirmation_miss($orderNumber);
}

header_no_store();
header('Referrer-Policy: no-referrer');
$head['title'] = 'Order ' . $order['order_number'] . ' | Sky Fragrances';
$head['canonical'] = '';
$head['robots'] = 'noindex,nofollow';

if (request_method() === 'POST') {
    $proofOld = ['payment_reference' => payment_reference_normalize(request_string('payment_reference', '')), 'sender_name' => trim(request_string('sender_name', ''))];
    if (!payment_proof_can_upload($order)) {
        http_status(403);
        render($route['view'], confirmation_view_data($order, ['notice' => ['type' => 'error', 'text' => 'This order is not accepting a new payment receipt.']]), $head);
    }
    $trap = form_trap_status();
    if ($trap !== 'ok') {
        form_trap_log('proof', $trap);
        render($route['view'], confirmation_view_data($order, ['proofOld' => $proofOld, 'notice' => ['type' => 'error', 'text' => 'This form expired. Please try again.']]), $head);
    }
    if (payment_proof_upload_limited((int) $order['id'])) {
        http_status(429);
        render($route['view'], confirmation_view_data($order, ['proofOld' => $proofOld, 'notice' => ['type' => 'error', 'text' => 'Too many upload attempts for this order. Please message us on WhatsApp.']]), $head);
    }
    payment_proof_record_submission();
    $errors = [];
    $reference = payment_reference_validate($proofOld['payment_reference']);
    if (!$reference['ok']) {
        $errors['payment_reference'] = $reference['message'];
    }
    $file = $_FILES['payment_proof'] ?? null;
    $proof = payment_proof_validate(is_array($file) ? $file : null);
    if (!$proof['ok']) {
        $errors['payment_proof'] = $proof['error'];
    }
    if ($errors !== []) {
        http_status(422);
        render($route['view'], confirmation_view_data($order, ['proofOld' => $proofOld, 'proofErrors' => $errors, 'notice' => ['type' => 'error', 'text' => 'Please check the highlighted fields.']]), $head);
    }
    payment_proof_store($order, (string) $file['tmp_name'], $proof['mime'], $reference['value'], $proofOld['sender_name'] === '' ? null : mb_substr($proofOld['sender_name'], 0, 120), null, request_ip_hash());
    payment_proof_record_stored((int) $order['id']);
    flash('success', "Thank you. We're verifying your payment and will confirm once it clears — usually within a few hours.");
    redirect(order_confirmation_url($order), 303);
}

render($route['view'], confirmation_view_data($order), $head);
