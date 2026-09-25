<?php
defined('SKYFR') || exit;

const COUPON_CODE_PATTERN = '/^[A-Z0-9-]{1,40}$/';
const COUPON_RATE_HOUR_MAX = 10;
const COUPON_RATE_DAY_MAX = 50;

function coupon_messages(): array
{
    return [
        'invalid' => "That code isn't valid. Please check the spelling and try again.",
        'not_started' => "That code isn't valid. Please check the spelling and try again.",
        'expired' => 'This code expired on {date}.',
        'exhausted' => 'This code has reached its usage limit.',
        'empty_cart' => 'Add something to your cart before applying a code.',
        'below_min' => 'This code needs a minimum order of {min}. Add {shortfall} more to use it.',
        'phone_limit' => 'This code has already been used with this phone number.',
        'no_effect' => "This code doesn't change your total.",
        'replaced' => '{old} was replaced by {new}.',
        'dropped' => '{code} is no longer valid and has been removed from your order. Your total has been updated.',
        'applied' => '{code} applied — you saved {amount}.',
        'removed' => 'Coupon removed.',
        'empty_field' => 'Enter a coupon code.',
        'rate_limited' => "That code isn't valid. Please check the spelling and try again.",
    ];
}

function coupon_message(string $key, array $vars = []): string
{
    $text = coupon_messages()[$key] ?? coupon_messages()['invalid'];
    foreach ($vars as $name => $value) {
        $text = str_replace('{' . $name . '}', (string) $value, $text);
    }
    return $text;
}

function coupon_normalize_code(?string $raw): string
{
    return strtoupper(trim((string) $raw));
}

function coupon_code_is_wellformed(string $code): bool
{
    return $code !== '' && preg_match(COUPON_CODE_PATTERN, $code) === 1;
}

function coupon_find(string $code, bool $forUpdate = false): ?array
{
    if (!coupon_code_is_wellformed($code)) {
        return null;
    }
    $sql = 'SELECT id, code, type, value, min_order_total, usage_limit, per_phone_limit, used_count, starts_at, expires_at, is_active FROM coupons WHERE code = :code' . ($forUpdate ? ' FOR UPDATE' : '');
    return db_fetch($sql, ['code' => $code]);
}

function coupon_validate(?array $coupon, string $code, int $subtotalPaisa, string $now, ?string $phoneNormalized = null): array
{
    $fail = static fn (string $key, array $vars = []): array => ['ok' => false, 'key' => $key, 'message' => coupon_message($key, $vars), 'discount_paisa' => 0];
    if (!coupon_code_is_wellformed($code) || $coupon === null) {
        return $fail('invalid');
    }
    if ((int) $coupon['is_active'] !== 1 || !in_array($coupon['type'], COUPON_TYPES, true)) {
        return $fail('invalid');
    }
    if ($coupon['type'] === 'percent' && pricing_percent_value($coupon) === null) {
        log_write('warning', 'coupon: percent value is not a whole number', ['code' => (string) $coupon['code'], 'value' => (string) $coupon['value']]);
        return $fail('invalid');
    }
    if ($coupon['starts_at'] !== null && $coupon['starts_at'] > $now) {
        return $fail('not_started');
    }
    if ($coupon['expires_at'] !== null && $coupon['expires_at'] < $now) {
        return $fail('expired', ['date' => date_short($coupon['expires_at'])]);
    }
    if ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
        return $fail('exhausted');
    }
    if ($subtotalPaisa <= 0) {
        return $fail('empty_cart');
    }
    $minPaisa = money_paisa((string) $coupon['min_order_total']);
    if ($subtotalPaisa < $minPaisa) {
        return $fail('below_min', ['min' => money(money_from_paisa($minPaisa)), 'shortfall' => money(money_from_paisa($minPaisa - $subtotalPaisa))]);
    }
    if ($phoneNormalized !== null && $coupon['per_phone_limit'] !== null && coupon_phone_uses((int) $coupon['id'], $phoneNormalized) >= (int) $coupon['per_phone_limit']) {
        return $fail('phone_limit');
    }
    $discount = pricing_discount($subtotalPaisa, $coupon);
    if ($discount <= 0) {
        return $fail('no_effect');
    }
    return ['ok' => true, 'key' => 'applied', 'message' => coupon_message('applied', ['code' => $coupon['code'], 'amount' => money(money_from_paisa($discount))]), 'discount_paisa' => $discount];
}

function coupon_phone_uses(int $couponId, string $phoneNormalized): int
{
    return (int) db_fetch_column(
        "SELECT COUNT(*) FROM coupon_redemptions WHERE coupon_id = :coupon AND phone_normalized = :phone AND status = 'applied'",
        ['coupon' => $couponId, 'phone' => $phoneNormalized]
    );
}

function coupon_claim_use(int $couponId, string $now): bool
{
    $affected = db_query(
        'UPDATE coupons SET used_count = used_count + 1 WHERE id = :id AND is_active = 1 AND (expires_at IS NULL OR expires_at >= :now) AND (usage_limit IS NULL OR used_count < usage_limit)',
        ['id' => $couponId, 'now' => $now]
    )->rowCount();
    return $affected === 1;
}

function coupon_record_redemption(array $coupon, int $orderId, string $phoneNormalized, int $discountPaisa, int $subtotalPaisa, string $now): int
{
    return db_insert('coupon_redemptions', [
        'coupon_id' => (int) $coupon['id'],
        'order_id' => $orderId,
        'code' => (string) $coupon['code'],
        'phone_normalized' => $phoneNormalized,
        'discount_amount' => money_from_paisa($discountPaisa),
        'order_subtotal' => money_from_paisa($subtotalPaisa),
        'status' => 'applied',
        'created_at' => $now,
    ]);
}

function coupon_revert_for_order(int $orderId): bool
{
    $redemption = db_fetch("SELECT id, coupon_id FROM coupon_redemptions WHERE order_id = :order AND status = 'applied' FOR UPDATE", ['order' => $orderId]);
    if ($redemption === null) {
        return false;
    }
    $reverted = db_query("UPDATE coupon_redemptions SET status = 'reverted' WHERE id = :id AND status = 'applied'", ['id' => (int) $redemption['id']])->rowCount();
    if ($reverted !== 1) {
        return false;
    }
    db_query('UPDATE coupons SET used_count = GREATEST(CAST(used_count AS SIGNED) - 1, 0) WHERE id = :id', ['id' => (int) $redemption['coupon_id']]);
    return true;
}

function coupon_rate_limited(): bool
{
    $ipHash = request_ip_hash();
    return rate_limit_count('coupon', $ipHash, 3600) >= COUPON_RATE_HOUR_MAX
        || rate_limit_count('coupon', $ipHash, 86400) >= COUPON_RATE_DAY_MAX;
}

function coupon_rate_record(): void
{
    rate_limit_record('coupon', request_ip_hash());
    rate_limit_purge();
}
