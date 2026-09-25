<?php
define('SKYFR', 1);
$_SERVER['REQUEST_URI'] = '/checkout';
$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
require dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_ROOT . '/app/lib/orders.php';
require_once APP_ROOT . '/app/lib/track.php';
require_once APP_ROOT . '/app/lib/outbox.php';
while (ob_get_level() > 0) {
    ob_end_flush();
}

$failures = 0;
function check(bool $ok, string $label): void
{
    global $failures;
    echo ($ok ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;
    if (!$ok) {
        $failures++;
    }
}
function stock_of(int $sizeId): int
{
    return (int) db_fetch_column('SELECT stock FROM product_sizes WHERE id = :id', ['id' => $sizeId]);
}
function used_count(string $code): int
{
    return (int) db_fetch_column('SELECT used_count FROM coupons WHERE code = :c', ['c' => $code]);
}
function order_count(): int
{
    return (int) db_fetch_column('SELECT COUNT(*) FROM orders');
}
function make_input(string $phone, string $idem, array $priced, string $method = 'cod', ?string $ref = null): array
{
    return [
        'customer_name' => 'Test Buyer', 'customer_phone' => $phone, 'phone_normalized' => phone_normalize($phone),
        'customer_email' => 'buyer@example.com', 'city' => 'Lahore', 'address' => 'House 12, Street 4, Model Town', 'postal_code' => null,
        'customer_note' => null, 'payment_method' => $method, 'payment_reference' => $ref, 'idem_key' => $idem,
        'price_token' => pricing_reprice_token($priced['grand_total_paisa'], cart_fingerprint($priced)),
        'price_total' => (string) $priced['grand_total_paisa'], 'ip_hash' => request_ip_hash(), 'user_agent' => 'commerce-test',
    ];
}

function cleanup_leftovers(): void
{
    foreach (db_fetch_all("SELECT id, stock_restored_at FROM orders WHERE user_agent = 'commerce-test'") as $left) {
        if ($left['stock_restored_at'] === null) {
            foreach (order_items((int) $left['id']) as $item) {
                if ($item['product_size_id'] !== null) {
                    db_query('UPDATE product_sizes SET stock = stock + :q WHERE id = :id', ['q' => (int) $item['quantity'], 'id' => (int) $item['product_size_id']]);
                }
            }
        }
        db_transaction(static fn () => coupon_revert_for_order((int) $left['id']));
        foreach (order_proofs((int) $left['id']) as $proof) {
            if ($proof['file_path'] !== null && is_file(APP_ROOT . '/storage/proofs/' . $proof['file_path'])) {
                @unlink(APP_ROOT . '/storage/proofs/' . $proof['file_path']);
            }
        }
        db_query('DELETE FROM email_outbox WHERE order_id = :id', ['id' => (int) $left['id']]);
        db_query('DELETE FROM orders WHERE id = :id', ['id' => (int) $left['id']]);
    }
}

session_ensure();
$_SESSION['cart'] = null;
cleanup_leftovers();
$createdOrders = [];
$sizeA = 1;
$sizeB = 6;
$sizeC = 3;
$stockA0 = stock_of($sizeA);
$stockB0 = stock_of($sizeB);
$stockC0 = stock_of($sizeC);
$used0 = used_count('WELCOME10');
$orders0 = order_count();
$admin = ['by' => 'admin', 'admin_id' => 1, 'admin_username' => 'tester'];

check(pricing_percent_discount(495500, 10) === 49600, 'percent discount rounds half-up to the rupee (Rs. 495.50 -> Rs. 496)');
check(pricing_percent_discount(495400, 10) === 49500, 'percent discount rounds down below the half (Rs. 495.40 -> Rs. 495)');
check(pricing_discount(60000, ['type' => 'fixed', 'value' => '1000.00']) === 60000, 'fixed coupon clamps to the subtotal');
check(pricing_apportion_discount([100000, 50000, 50000], 10001) === [5001, 2500, 2500], 'largest-remainder apportion sums exactly');

cart_add($sizeA, 1);
cart_add($sizeB, 2);
cart_set_coupon('welcome10');
$priced = cart_priced(true);
check($priced['subtotal_paisa'] === 895000 + 2 * 795000, 'subtotal uses sale price when lower');
check($priced['discount_paisa'] === 248500, 'WELCOME10 gives 10% rounded (Rs. 2,485)');
check($priced['shipping_paisa'] === 0 && $priced['free_shipping'] === true, 'free shipping on pre-discount subtotal');
check($priced['grand_total_paisa'] === 2236500, 'grand total = subtotal - discount + shipping');
$presented = cart_present($priced);
check($presented['grand_total_display'] === "Rs.\u{00A0}22,365" && $presented['shipping_display'] === 'Free', 'display strings');

$idemA = bin2hex(random_bytes(16));
$result = order_place(make_input('0300 1234567', $idemA, $priced));
check($result['outcome'] === 'placed', 'COD order placed: ' . ($result['order_number'] ?? json_encode($result['message'] ?? null)));
$codId = (int) ($result['order_id'] ?? 0);
$createdOrders[] = $codId;
check(stock_of($sizeA) === $stockA0 - 1 && stock_of($sizeB) === $stockB0 - 2, 'stock decremented once per line');
check(used_count('WELCOME10') === $used0 + 1, 'coupon used_count incremented once');
check((int) db_fetch_column("SELECT COUNT(*) FROM coupon_redemptions WHERE order_id = :o AND status = 'applied'", ['o' => $codId]) === 1, 'one redemption ledger row');
$orderRow = order_find($codId);
check($orderRow['grand_total'] === '22365.00' && $orderRow['discount_total'] === '2485.00' && $orderRow['shipping_fee'] === '0.00' && (int) $orderRow['item_count'] === 3, 'money snapshot frozen on the order');
check(order_number_is_wellformed($orderRow['order_number']) && strlen($orderRow['access_token']) === 32, 'order number format and access token');
$items = order_items($codId);
check(count($items) === 2 && array_sum(array_map(static fn ($i) => money_paisa($i['line_discount']), $items)) === 248500, 'line_discount apportioned to the discount total');
check(count(order_history($codId)) === 1 && order_history($codId)[0]['to_status'] === 'pending', 'birth history row');

$replay = order_place(make_input('0300 1234567', $idemA, $priced));
check($replay['outcome'] === 'replay_same' && $replay['order_id'] === $codId && order_count() === $orders0 + 1, 'same idempotency key replays without a second order');
check(stock_of($sizeA) === $stockA0 - 1 && used_count('WELCOME10') === $used0 + 1, 'replay touched neither stock nor coupon');

cart_set_coupon('WELCOME10');
$priced2 = cart_priced(true);
$phoneLimited = order_place(make_input('+92 300 1234567', bin2hex(random_bytes(16)), $priced2));
check($phoneLimited['outcome'] === 'conflict' && $phoneLimited['message']['type'] === 'phone_limit' && order_count() === $orders0 + 1, 'per-phone coupon limit blocks inside the transaction, no order written');
check(cart_get()['coupon_code'] === null, 'coupon dropped from the session after the conflict');

$tokenStale = make_input('0300 1234567', bin2hex(random_bytes(16)), $priced2);
$tokenStale['price_total'] = (string) ($priced2['grand_total_paisa'] + 100);
$stale = order_place($tokenStale);
check($stale['outcome'] === 'conflict' && $stale['message']['type'] === 'reprice' && order_count() === $orders0 + 1, 'stale re-price token rolls back with the re-confirm notice');

cart_clear();
cart_add($sizeC, 1);
$priced3 = cart_priced(true);
check($priced3['shipping_paisa'] === 0 && $priced3['grand_total_paisa'] === 545000, 'single Cirrus 50ml over the threshold ships free');
$bankPhone = '0311 7654321';
$bankResult = order_place(make_input($bankPhone, bin2hex(random_bytes(16)), $priced3, 'bank', 'TXN123456'));
check($bankResult['outcome'] === 'placed', 'bank transfer order placed: ' . ($bankResult['order_number'] ?? json_encode($bankResult['message'] ?? null)));
$bankId = (int) ($bankResult['order_id'] ?? 0);
$createdOrders[] = $bankId;
check(stock_of($sizeC) === $stockC0 - 1, 'bank order decremented stock once');
$bankOrder = order_find($bankId);
check($bankOrder['payment_status'] === 'unpaid' && $bankOrder['payment_reference'] === 'TXN123456', 'manual order starts unpaid with the reference snapshot');

$png = imagecreatetruecolor(320, 240);
imagefilledrectangle($png, 0, 0, 319, 239, imagecolorallocate($png, 212, 176, 132));
$tmpPng = tempnam(sys_get_temp_dir(), 'proof') . '.png';
imagepng($png, $tmpPng);
imagedestroy($png);
$stored = payment_proof_store($bankOrder, $tmpPng, 'image/png', 'TXN123456', 'Test Sender', null, request_ip_hash());
$proofFile = $stored['file_path'] === null ? null : APP_ROOT . '/storage/proofs/' . $stored['file_path'];
check($stored['status_moved'] === true && $proofFile !== null && is_file($proofFile), 'proof written post-commit and payment_status moved to awaiting_verification');
check(order_find($bankId)['payment_status'] === 'awaiting_verification', 'guarded UPDATE moved the payment status');
$again = payment_proof_store(order_find($bankId), $tmpPng, 'image/png', 'TXN123456', null, null, request_ip_hash());
check($again['status_moved'] === false, 'second proof cannot move the status again');
$secondProofFile = $again['file_path'] === null ? null : APP_ROOT . '/storage/proofs/' . $again['file_path'];
check(order_outstanding_transfer(phone_normalize($bankPhone), 'other') !== null, 'outstanding transfer blocks a second transfer order for the phone');
check(payment_proof_can_upload(order_find($bankId)) === false, 'no re-upload while awaiting verification');
$rejected = payment_review($bankId, (int) $stored['proof_id'], false, $admin, 'Amount short by Rs. 50');
check($rejected['ok'] && order_find($bankId)['payment_status'] === 'failed' && payment_proof_can_upload(order_find($bankId)), 'rejection sets failed and reopens the upload');
$approved = payment_review($bankId, (int) $stored['proof_id'], true, $admin);
check($approved['ok'] && order_find($bankId)['payment_status'] === 'paid' && order_find($bankId)['paid_at'] !== null, 'approval sets paid');

$oversellStock = stock_of($sizeC);
try {
    db_transaction(static function () use ($sizeA, $sizeC, $oversellStock): void {
        stock_decrement_lines([['size_id' => $sizeC, 'qty' => $oversellStock + 1], ['size_id' => $sizeA, 'qty' => 1]]);
    });
    check(false, 'oversell should throw');
} catch (RuntimeException $e) {
    $details = stock_conflict_details($e);
    check($details !== null && $details['size_id'] === $sizeC, 'oversell attempt fails on the guard');
}
check(stock_of($sizeC) === $oversellStock && stock_of($sizeA) === $stockA0 - 1, 'oversell rolled back cleanly, earlier line untouched');

$track = track_lookup($orderRow['order_number'], phone_normalize('03001234567'));
check($track !== null && track_lookup($orderRow['order_number'], '+923009999999') === null, 'track lookup needs both number and phone');
$timeline = track_timeline($track);
check($timeline[0]['state'] === 'current' && $timeline[1]['state'] === 'future', 'timeline marks pending as current');

$cancel = order_transition($codId, 'cancelled', $admin, 'Test cancel');
check($cancel['ok'] && $cancel['stock_summary'] !== null, 'cancel from pending restores stock: ' . ($cancel['stock_summary'] ?? ''));
check(stock_of($sizeA) === $stockA0 && stock_of($sizeB) === $stockB0, 'stock back to the starting value exactly once');
check(used_count('WELCOME10') === $used0 && db_fetch_column('SELECT status FROM coupon_redemptions WHERE order_id = :o', ['o' => $codId]) === 'reverted', 'coupon use returned and ledger reverted');
$cancelAgain = order_transition($codId, 'cancelled', $admin, 'Again');
check($cancelAgain['ok'] === false && stock_of($sizeA) === $stockA0, 'cancelled is terminal, stock not restored twice');
$restoreAgain = stock_restore_order($codId, $admin);
check($restoreAgain['ok'] === false && $restoreAgain['reason'] === 'already_restored' && stock_of($sizeA) === $stockA0, 'explicit restore refuses a second time');

$confirm = order_transition($bankId, 'confirmed', $admin);
$ship = order_transition($bankId, 'shipped', $admin, '', ['courier_name' => 'TCS', 'tracking_number' => 'TCS123', 'tracking_url' => 'https://tcs.example/TCS123']);
check($confirm['ok'] && $ship['ok'] && order_find($bankId)['shipped_at'] !== null, 'confirmed -> shipped with courier');
$cancelShipped = order_transition($bankId, 'cancelled', $admin, 'Refused at door');
check($cancelShipped['ok'] && $cancelShipped['stock_summary'] === null && order_find($bankId)['stock_restored_at'] === null && stock_of($sizeC) === $stockC0 - 1, 'cancel from shipped leaves stock with the parcel');
$back = stock_restore_order($bankId, $admin);
check($back['ok'] && stock_of($sizeC) === $stockC0, 'parcel received back restores stock once');
check(stock_restore_order($bankId, $admin)['reason'] === 'already_restored' && stock_of($sizeC) === $stockC0, 'second parcel-back is a no-op');

$numbers = db_fetch_all('SELECT order_number FROM orders WHERE id IN (' . implode(',', array_map('intval', $createdOrders)) . ')');
check(count(array_unique(array_column($numbers, 'order_number'))) === count($createdOrders), 'order numbers unique');
check((int) db_fetch_column("SELECT COUNT(*) FROM email_outbox WHERE order_id IN (" . implode(',', array_map('intval', $createdOrders)) . ")") >= 1, 'transactional emails queued in the outbox');

foreach ($createdOrders as $id) {
    db_query('DELETE FROM email_outbox WHERE order_id = :id', ['id' => $id]);
    db_query('DELETE FROM orders WHERE id = :id', ['id' => $id]);
}
foreach ([$proofFile, $secondProofFile, $tmpPng] as $file) {
    if ($file !== null && is_file($file)) {
        @unlink($file);
    }
}
db_query('DELETE FROM rate_limits WHERE subject_hash = :s', ['s' => request_ip_hash()]);
cart_clear();
check(order_count() === $orders0 && stock_of($sizeA) === $stockA0 && stock_of($sizeB) === $stockB0 && stock_of($sizeC) === $stockC0 && used_count('WELCOME10') === $used0, 'test orders deleted, stock and coupon counters back to start');
echo ($failures === 0 ? 'ALL PASSED' : $failures . ' FAILURE(S)') . PHP_EOL;
exit($failures === 0 ? 0 : 1);
