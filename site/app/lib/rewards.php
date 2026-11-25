<?php
defined('SKYFR') || exit;

const REWARD_KIND = 'second_order';
const REWARD_CODE_PREFIX = 'THANKS-';
const REWARD_CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

function reward_settings(): array
{
    return [
        'enabled' => setting_bool('reward_enabled', false),
        'type' => (string) setting('reward_type', 'percent') === 'fixed' ? 'fixed' : 'percent',
        'value' => (string) setting('reward_value', '10'),
        'min_order' => setting_money('reward_min_order', '0.00'),
        'valid_days' => max(0, min(365, setting_int('reward_valid_days', 60))),
    ];
}

function reward_value_problem(string $type, string $value): ?string
{
    if ($type === 'percent') {
        return preg_match('/^[0-9]{1,2}$/', $value) === 1 && (int) $value >= 1 && (int) $value <= 90 ? null : 'Enter a whole percent from 1 to 90.';
    }
    return preg_match('/^[0-9]{1,6}(\.[0-9]{1,2})?$/', $value) === 1 && money_paisa($value) > 0 ? null : 'Enter the discount in rupees.';
}

function reward_discount_label(string $type, string $value): string
{
    return $type === 'percent' ? (int) $value . '% off' : money($value) . ' off';
}

function reward_active_orders(string $phoneNormalized): int
{
    return (int) db_fetch_column("SELECT COUNT(*) FROM orders WHERE phone_normalized = :p AND status <> 'cancelled'", ['p' => $phoneNormalized]);
}

function reward_breaks_rule(array $coupon, string $phoneNormalized): bool
{
    return ($coupon['kind'] ?? 'standard') === REWARD_KIND
        && ((string) ($coupon['reward_phone'] ?? '') !== $phoneNormalized || reward_active_orders($phoneNormalized) !== 1);
}

function reward_code_new(): string
{
    $code = REWARD_CODE_PREFIX;
    for ($i = 0; $i < 6; $i++) {
        $code .= REWARD_CODE_ALPHABET[random_int(0, strlen(REWARD_CODE_ALPHABET) - 1)];
    }
    return $code;
}

function reward_issue(int $orderId, string $phoneNormalized, string $now): ?string
{
    $config = reward_settings();
    if (!$config['enabled'] || reward_value_problem($config['type'], $config['value']) !== null) {
        return null;
    }
    if (reward_active_orders($phoneNormalized) !== 1 || db_exists('SELECT 1 FROM coupons WHERE reward_phone = :p', ['p' => $phoneNormalized])) {
        return null;
    }
    $expires = $config['valid_days'] > 0 ? (new DateTimeImmutable($now))->modify('+' . $config['valid_days'] . ' days')->format('Y-m-d 23:59:59') : null;
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $code = reward_code_new();
        try {
            db_insert('coupons', [
                'code' => $code,
                'kind' => REWARD_KIND,
                'type' => $config['type'],
                'value' => $config['type'] === 'percent' ? (int) $config['value'] . '.00' : money_from_paisa(money_paisa($config['value'])),
                'min_order_total' => $config['min_order'],
                'usage_limit' => 1,
                'per_phone_limit' => 1,
                'used_count' => 0,
                'starts_at' => $now,
                'expires_at' => $expires,
                'is_active' => 1,
                'reward_phone' => $phoneNormalized,
                'reward_order_id' => $orderId,
                'created_at' => $now,
            ]);
            return $code;
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) !== 1062) {
                throw $e;
            }
            if (str_contains($e->getMessage(), 'uk_coupons_reward_phone')) {
                return null;
            }
        }
    }
    return null;
}

function reward_issue_after_order(int $orderId, string $phoneNormalized, string $now): void
{
    try {
        reward_issue($orderId, $phoneNormalized, $now);
    } catch (Throwable $e) {
        log_write('warning', 'reward: thank-you code not issued', ['order_id' => $orderId, 'error' => $e->getMessage()]);
    }
}

function reward_view(array $row): array
{
    $usable = (int) $row['is_active'] === 1
        && (int) $row['used_count'] === 0
        && ($row['expires_at'] === null || (string) $row['expires_at'] >= now_karachi())
        && reward_active_orders((string) $row['reward_phone']) === 1;
    $view = [
        'code' => (string) $row['code'],
        'label' => reward_discount_label((string) $row['type'], (string) $row['value']),
        'min_order' => money_paisa((string) $row['min_order_total']) > 0 ? money((string) $row['min_order_total']) : '',
        'expires' => $row['expires_at'] === null ? '' : date_short((string) $row['expires_at']),
        'usable' => $usable,
    ];
    return $view + ['terms' => reward_terms($view)];
}

function reward_for_order(int $orderId): ?array
{
    $row = db_fetch('SELECT code, type, value, min_order_total, expires_at, is_active, used_count, reward_phone FROM coupons WHERE kind = :k AND reward_order_id = :o', ['k' => REWARD_KIND, 'o' => $orderId]);
    return $row === null ? null : reward_view($row);
}

function reward_terms(array $reward): string
{
    $terms = 'Use it once, on your next order from the same phone number';
    if ($reward['min_order'] !== '') {
        $terms .= ', on orders of ' . $reward['min_order'] . ' or more';
    }
    if ($reward['expires'] !== '') {
        $terms .= ', until ' . $reward['expires'];
    }
    return $terms . '.';
}

function reward_message(array $reward, string $firstName): string
{
    return 'Assalam-o-Alaikum' . ($firstName !== '' ? ' ' . $firstName : '') . ', thank you for your first order with ' . (string) setting('store_name', 'Sky Fragrances') . '. '
        . 'Here is ' . $reward['label'] . ' your next order: use code ' . $reward['code'] . ' at checkout. ' . reward_terms($reward) . ' ' . SITE_URL;
}
