<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/pricing.php';
require_once __DIR__ . '/coupons.php';

const CART_MAX_QTY_PER_LINE = 10;
const CART_MAX_LINES = 20;

function cart_session_active(): bool
{
    return session_status() === PHP_SESSION_ACTIVE;
}

function cart_get(): array
{
    $cart = cart_session_active() ? ($_SESSION['cart'] ?? null) : null;
    if (!is_array($cart) || !isset($cart['items']) || !is_array($cart['items'])) {
        return ['items' => [], 'coupon_code' => null, 'updated_at' => 0];
    }
    $items = [];
    foreach ($cart['items'] as $line) {
        $sizeId = (int) ($line['size_id'] ?? 0);
        $qty = (int) ($line['qty'] ?? 0);
        if ($sizeId > 0 && $qty > 0 && !isset($items[$sizeId])) {
            $items[$sizeId] = ['size_id' => $sizeId, 'qty' => min($qty, CART_MAX_QTY_PER_LINE)];
        }
    }
    $code = $cart['coupon_code'] ?? null;
    return [
        'items' => array_values($items),
        'coupon_code' => is_string($code) && $code !== '' ? coupon_normalize_code($code) : null,
        'updated_at' => (int) ($cart['updated_at'] ?? 0),
    ];
}

function cart_save(array $cart): void
{
    session_ensure();
    $_SESSION['cart'] = [
        'items' => array_values($cart['items']),
        'coupon_code' => $cart['coupon_code'] ?? null,
        'updated_at' => time(),
    ];
}

function cart_clear(): void
{
    if (cart_session_active()) {
        unset($_SESSION['cart']);
    }
}

function cart_pairs(?array $cart = null): array
{
    $pairs = [];
    foreach (($cart ?? cart_get())['items'] as $line) {
        $pairs[$line['size_id']] = $line['qty'];
    }
    return $pairs;
}

function cart_count(): int
{
    if (!cart_session_active()) {
        return 0;
    }
    return array_sum(array_column(cart_get()['items'], 'qty'));
}

function cart_add(int $sizeId, int $qty): array
{
    $cart = cart_get();
    $qty = max(1, min(CART_MAX_QTY_PER_LINE, $qty));
    $found = false;
    foreach ($cart['items'] as &$line) {
        if ($line['size_id'] === $sizeId) {
            $line['qty'] = min(CART_MAX_QTY_PER_LINE, $line['qty'] + $qty);
            $found = true;
        }
    }
    unset($line);
    if (!$found) {
        if (count($cart['items']) >= CART_MAX_LINES) {
            return ['ok' => false, 'message' => 'Your cart is full. Please check out first.'];
        }
        $cart['items'][] = ['size_id' => $sizeId, 'qty' => $qty];
    }
    cart_save($cart);
    return ['ok' => true, 'message' => ''];
}

function cart_update(int $sizeId, int $qty): void
{
    $cart = cart_get();
    if ($qty < 1) {
        cart_remove($sizeId);
        return;
    }
    foreach ($cart['items'] as &$line) {
        if ($line['size_id'] === $sizeId) {
            $line['qty'] = min(CART_MAX_QTY_PER_LINE, $qty);
        }
    }
    unset($line);
    cart_save($cart);
}

function cart_remove(int $sizeId): void
{
    $cart = cart_get();
    $cart['items'] = array_values(array_filter($cart['items'], static fn (array $line): bool => $line['size_id'] !== $sizeId));
    cart_save($cart);
}

function cart_set_coupon(?string $code): void
{
    $cart = cart_get();
    $cart['coupon_code'] = $code === null || $code === '' ? null : coupon_normalize_code($code);
    cart_save($cart);
}

function cart_set_items(array $pairs): void
{
    $cart = cart_get();
    $cart['items'] = [];
    foreach ($pairs as $sizeId => $qty) {
        if ((int) $qty > 0) {
            $cart['items'][] = ['size_id' => (int) $sizeId, 'qty' => min(CART_MAX_QTY_PER_LINE, (int) $qty)];
        }
    }
    cart_save($cart);
}

function cart_load_rows(array $sizeIds, bool $forUpdate = false): array
{
    $sizeIds = array_values(array_unique(array_map('intval', $sizeIds)));
    sort($sizeIds, SORT_NUMERIC);
    if ($sizeIds === []) {
        return [];
    }
    $placeholders = [];
    $params = [];
    foreach ($sizeIds as $i => $id) {
        $placeholders[] = ':id' . $i;
        $params['id' . $i] = $id;
    }
    $sql = 'SELECT ps.id AS size_id, ps.product_id, ps.size_label, ps.size_ml, ps.sku, ps.price, ps.sale_price, ps.stock, ps.is_active AS size_active,
                   p.name AS product_name, p.slug AS product_slug, p.is_active AS product_active, p.deleted_at,
                   (SELECT i.filename FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.sort_order ASC LIMIT 1) AS image
            FROM product_sizes ps
            INNER JOIN products p ON p.id = ps.product_id
            WHERE ps.id IN (' . implode(', ', $placeholders) . ')
            ORDER BY ps.id ASC' . ($forUpdate ? ' FOR UPDATE' : '');
    $rows = [];
    foreach (db_fetch_all($sql, $params) as $row) {
        $rows[(int) $row['size_id']] = $row;
    }
    return $rows;
}

function cart_row_purchasable(array $row): bool
{
    return (int) $row['size_active'] === 1 && (int) $row['product_active'] === 1 && $row['deleted_at'] === null;
}

function cart_price(array $lines, ?array $coupon, array $settings, string $now, ?string $phoneNormalized = null): array
{
    $priced = [];
    $subtotal = 0;
    $itemCount = 0;
    $hasSoldOut = false;
    foreach ($lines as $line) {
        $unit = pricing_unit((string) $line['price'], $line['sale_price'] === null ? null : (string) $line['sale_price']);
        $stock = max(0, (int) $line['stock']);
        $requested = max(1, min(CART_MAX_QTY_PER_LINE, (int) $line['qty']));
        $qty = $stock > 0 ? min($requested, $stock) : $requested;
        $inStock = $stock > 0;
        $lineTotal = $inStock ? $unit['unit_price_paisa'] * $qty : 0;
        if ($inStock) {
            $subtotal += $lineTotal;
            $itemCount += $qty;
        } else {
            $hasSoldOut = true;
        }
        $priced[] = [
            'size_id' => (int) $line['size_id'],
            'product_id' => (int) $line['product_id'],
            'product_name' => (string) $line['product_name'],
            'product_slug' => (string) $line['product_slug'],
            'size_label' => (string) $line['size_label'],
            'size_ml' => $line['size_ml'] === null ? null : (int) $line['size_ml'],
            'sku' => (string) $line['sku'],
            'image' => $line['image'] === null ? null : (string) $line['image'],
            'list_price_paisa' => money_paisa((string) $line['price']),
            'sale_price_paisa' => $line['sale_price'] === null ? null : money_paisa((string) $line['sale_price']),
            'unit_price_paisa' => $unit['unit_price_paisa'],
            'compare_at_paisa' => $unit['compare_at_paisa'],
            'qty' => $qty,
            'line_total_paisa' => $lineTotal,
            'stock' => $stock,
            'max_qty' => min(CART_MAX_QTY_PER_LINE, max(0, $stock)),
            'qty_adjusted' => $inStock && $qty < $requested,
            'in_stock' => $inStock,
        ];
    }
    $couponResult = null;
    $couponError = null;
    $discount = 0;
    if ($coupon !== null) {
        $validation = coupon_validate($coupon['row'], $coupon['code'], $subtotal, $now, $phoneNormalized);
        if ($validation['ok']) {
            $discount = $validation['discount_paisa'];
            $couponResult = [
                'id' => (int) $coupon['row']['id'],
                'code' => (string) $coupon['row']['code'],
                'type' => (string) $coupon['row']['type'],
                'value' => (string) $coupon['row']['value'],
                'discount_paisa' => $discount,
                'message' => $validation['message'],
            ];
        } else {
            $couponError = ['key' => $validation['key'], 'message' => $validation['message'], 'code' => $coupon['code']];
        }
    }
    $shipping = pricing_shipping($subtotal, $settings);
    $grandTotal = $subtotal - $discount + $shipping['shipping_paisa'];
    if ($grandTotal < 0) {
        throw new LogicException('Negative grand total');
    }
    return [
        'lines' => $priced,
        'removed' => [],
        'subtotal_paisa' => $subtotal,
        'coupon' => $couponResult,
        'coupon_error' => $couponError,
        'discount_paisa' => $discount,
        'shipping_paisa' => $shipping['shipping_paisa'],
        'free_shipping' => $shipping['free_shipping'],
        'threshold_paisa' => $settings['threshold_paisa'],
        'shipping_fee_paisa' => $settings['shipping_fee_paisa'],
        'remaining_paisa' => max(0, $settings['threshold_paisa'] - $subtotal),
        'grand_total_paisa' => $grandTotal,
        'item_count' => $itemCount,
        'has_sold_out' => $hasSoldOut,
    ];
}

function cart_removed_line(array $row, string $reason): array
{
    $name = (string) $row['product_name'];
    $size = (string) $row['size_label'];
    return [
        'size_id' => (int) $row['size_id'],
        'name' => $name,
        'size_label' => $size,
        'reason' => $reason,
        'message' => $name . ' (' . $size . ') is no longer available and has been removed from your cart.',
    ];
}

function cart_resolve(array $cart, bool $forUpdate, ?string $phoneNormalized = null): array
{
    $rows = cart_load_rows(array_column($cart['items'], 'size_id'), $forUpdate);
    $lines = [];
    $removed = [];
    $seenProducts = [];
    foreach ($cart['items'] as $line) {
        $row = $rows[$line['size_id']] ?? null;
        if ($row === null) {
            $removed[] = ['size_id' => $line['size_id'], 'name' => 'An item', 'size_label' => '', 'reason' => 'deleted', 'message' => 'An item in your cart is no longer available and has been removed.'];
            continue;
        }
        if (!cart_row_purchasable($row)) {
            $reason = (int) $row['size_active'] === 1 ? 'unavailable' : 'deleted';
            if ($reason === 'unavailable' && isset($seenProducts[(int) $row['product_id']])) {
                continue;
            }
            $seenProducts[(int) $row['product_id']] = true;
            $removed[] = cart_removed_line($row, $reason);
            continue;
        }
        $row['qty'] = $line['qty'];
        $lines[] = $row;
    }
    $coupon = null;
    if ($cart['coupon_code'] !== null) {
        $coupon = ['code' => $cart['coupon_code'], 'row' => coupon_find($cart['coupon_code'], $forUpdate)];
    }
    $priced = cart_price($lines, $coupon, pricing_settings(), now_karachi(), $phoneNormalized);
    $priced['removed'] = $removed;
    $priced['coupon_code'] = $cart['coupon_code'];
    return $priced;
}

function cart_priced(bool $syncSession = true): array
{
    $cart = cart_get();
    $priced = cart_resolve($cart, false);
    if ($syncSession && cart_session_active() && $cart['items'] !== []) {
        $pairs = [];
        foreach ($priced['lines'] as $line) {
            $pairs[$line['size_id']] = $line['qty'];
        }
        $changed = $pairs !== cart_pairs($cart);
        $couponDropped = $priced['coupon_error'] !== null;
        if ($changed || $couponDropped) {
            $cart['items'] = [];
            foreach ($pairs as $sizeId => $qty) {
                $cart['items'][] = ['size_id' => $sizeId, 'qty' => $qty];
            }
            if ($couponDropped) {
                $cart['coupon_code'] = null;
            }
            cart_save($cart);
        }
    }
    return $priced;
}

function cart_fingerprint(array $priced): string
{
    $pairs = [];
    foreach ($priced['lines'] as $line) {
        if ($line['in_stock']) {
            $pairs[$line['size_id']] = $line['qty'];
        }
    }
    return pricing_fingerprint($pairs);
}

function cart_image_url(int $productId, ?string $filename): string
{
    $filename = trim((string) $filename);
    if ($filename === '' || str_contains($filename, '..')) {
        return asset('img/placeholder-4x5.svg');
    }
    $stem = pathinfo(basename($filename), PATHINFO_FILENAME);
    if (preg_match('/^product-\d+-[a-f0-9]{10}$/', $stem)) {
        return image_url($stem, 'thumb');
    }
    $dir = '/uploads/products/' . (str_contains($filename, '/') ? trim(dirname($filename), '/') : (string) $productId);
    foreach (['thumb.webp', 'thumb.jpg', 'card.webp', 'card.jpg'] as $variant) {
        if (is_file(APP_ROOT . $dir . '/' . $stem . '-' . $variant)) {
            return url($dir . '/' . $stem . '-' . $variant);
        }
    }
    if (is_file(APP_ROOT . $dir . '/' . basename($filename))) {
        return url($dir . '/' . basename($filename));
    }
    return asset('img/placeholder-4x5.svg');
}

function cart_present(array $priced): array
{
    $lines = [];
    foreach ($priced['lines'] as $line) {
        $lines[] = $line + [
            'url' => url('/product/' . $line['product_slug']),
            'image_url' => cart_image_url($line['product_id'], $line['image']),
            'unit_price' => money_from_paisa($line['unit_price_paisa']),
            'unit_price_display' => money(money_from_paisa($line['unit_price_paisa'])),
            'compare_at' => $line['compare_at_paisa'] === null ? null : money_from_paisa($line['compare_at_paisa']),
            'compare_at_display' => $line['compare_at_paisa'] === null ? null : money(money_from_paisa($line['compare_at_paisa'])),
            'line_total' => money_from_paisa($line['line_total_paisa']),
            'line_total_display' => money(money_from_paisa($line['line_total_paisa'])),
            'notice' => !$line['in_stock']
                ? 'Sold out'
                : ($line['qty_adjusted'] ? 'Only ' . $line['stock'] . " left — we've updated your quantity." : ''),
        ];
    }
    $messages = [];
    foreach ($priced['removed'] as $removed) {
        $messages[] = ['type' => 'info', 'key' => 'removed', 'text' => $removed['message']];
    }
    foreach ($lines as $line) {
        if ($line['qty_adjusted']) {
            $messages[] = ['type' => 'info', 'key' => 'qty_adjusted', 'text' => 'Only ' . $line['stock'] . ' left of ' . $line['product_name'] . ' (' . $line['size_label'] . ") — we've updated your quantity."];
        }
    }
    if ($priced['coupon_error'] !== null) {
        $messages[] = ['type' => 'error', 'key' => 'coupon_dropped', 'text' => coupon_message('dropped', ['code' => $priced['coupon_error']['code']])];
    }
    if ($priced['has_sold_out']) {
        $messages[] = ['type' => 'error', 'key' => 'sold_out', 'text' => 'Remove the sold-out items to continue.'];
    }
    $progress = pricing_progress($priced['subtotal_paisa'], $priced['threshold_paisa']);
    $isEmpty = $priced['lines'] === [];
    $coupon = $priced['coupon'];
    if ($coupon !== null) {
        $coupon['discount'] = money_from_paisa($coupon['discount_paisa']);
        $coupon['discount_display'] = money($coupon['discount']);
    }
    return [
        'lines' => $lines,
        'removed' => $priced['removed'],
        'messages' => $messages,
        'is_empty' => $isEmpty,
        'item_count' => $priced['item_count'],
        'line_count' => count($lines),
        'subtotal' => money_from_paisa($priced['subtotal_paisa']),
        'subtotal_display' => money(money_from_paisa($priced['subtotal_paisa'])),
        'discount' => money_from_paisa($priced['discount_paisa']),
        'discount_display' => money(money_from_paisa($priced['discount_paisa'])),
        'shipping' => money_from_paisa($priced['shipping_paisa']),
        'shipping_display' => $priced['subtotal_paisa'] > 0 && $priced['shipping_paisa'] === 0 ? 'Free' : money(money_from_paisa($priced['shipping_paisa'])),
        'shipping_fee' => money_from_paisa($priced['shipping_fee_paisa']),
        'shipping_fee_display' => money(money_from_paisa($priced['shipping_fee_paisa'])),
        'free_shipping' => $priced['free_shipping'],
        'threshold' => money_from_paisa($priced['threshold_paisa']),
        'threshold_display' => money(money_from_paisa($priced['threshold_paisa'])),
        'remaining' => money_from_paisa($priced['remaining_paisa']),
        'remaining_display' => money(money_from_paisa($priced['remaining_paisa'])),
        'progress' => ['visible' => $progress['visible'], 'percent' => $progress['percent'], 'message' => $progress['message']],
        'grand_total' => money_from_paisa($priced['grand_total_paisa']),
        'grand_total_display' => money(money_from_paisa($priced['grand_total_paisa'])),
        'coupon' => $coupon,
        'coupon_error' => $priced['coupon_error'],
        'has_sold_out' => $priced['has_sold_out'],
        'can_checkout' => !$isEmpty && !$priced['has_sold_out'] && $priced['item_count'] > 0,
    ];
}
