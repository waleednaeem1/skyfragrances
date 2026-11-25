<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/coupons.php';
require_once __DIR__ . '/catalogue.php';

function coupon_reward_form(array $config): array
{
    return [
        'enabled' => $config['enabled'] ? '1' : '0',
        'type' => $config['type'],
        'value' => $config['type'] === 'percent' ? (string) (int) $config['value'] : catalogue_money_field($config['value']),
        'min_order' => money_paisa($config['min_order']) > 0 ? catalogue_money_field($config['min_order']) : '',
        'valid_days' => (string) $config['valid_days'],
    ];
}

function coupon_reward_state(array $row, string $now): array
{
    if ($row['used_order'] !== null) {
        return ['label' => 'Used on ' . $row['used_order'], 'tone' => 'green'];
    }
    if ((int) $row['is_active'] !== 1) {
        return ['label' => 'Stopped', 'tone' => 'muted'];
    }
    if ($row['expires_at'] !== null && (string) $row['expires_at'] < $now) {
        return ['label' => 'Expired', 'tone' => 'muted'];
    }
    if ((int) $row['orders_count'] >= 2) {
        return ['label' => 'Ordered again without it', 'tone' => 'neutral'];
    }
    if ((int) $row['orders_count'] === 0) {
        return ['label' => 'First order cancelled', 'tone' => 'maroon'];
    }
    return ['label' => 'Waiting for 2nd order', 'tone' => 'gold'];
}

$now = now_karachi();
$config = reward_settings();
$form = coupon_reward_form($config);
$errors = [];

if (request_method() === 'POST') {
    $form = [
        'enabled' => request_post('enabled', '') === '1' ? '1' : '0',
        'type' => request_post('type', '') === 'fixed' ? 'fixed' : 'percent',
        'value' => trim((string) request_post('value', '')),
        'min_order' => trim((string) request_post('min_order', '')),
        'valid_days' => trim((string) request_post('valid_days', '')),
    ];
    $value = $form['type'] === 'fixed' ? (string) catalogue_money_input($form['value']) : $form['value'];
    $problem = reward_value_problem($form['type'], $value);
    if ($problem !== null) {
        $errors['value'] = $problem;
    }
    $min = $form['min_order'] === '' ? '0' : catalogue_money_input($form['min_order']);
    if ($min === null || $min === '') {
        $errors['min_order'] = 'Enter an amount in rupees, or leave it empty for no minimum.';
    }
    if (preg_match('/^[0-9]{1,3}$/', $form['valid_days']) !== 1 || (int) $form['valid_days'] > 365) {
        $errors['valid_days'] = 'Enter a number of days from 0 to 365. Use 0 if the code should never expire.';
    }
    if ($errors === []) {
        $changes = [
            'reward_enabled' => $form['enabled'],
            'reward_type' => $form['type'],
            'reward_value' => $form['type'] === 'percent' ? (string) (int) $value : money_from_paisa(money_paisa($value)),
            'reward_min_order' => money_from_paisa(money_paisa((string) $min)),
            'reward_valid_days' => (string) (int) $form['valid_days'],
        ];
        $before = ['reward_enabled' => $config['enabled'] ? '1' : '0', 'reward_type' => $config['type'], 'reward_value' => $config['value'], 'reward_min_order' => $config['min_order'], 'reward_valid_days' => (string) $config['valid_days']];
        db_transaction(static function () use ($changes, $now): void {
            foreach ($changes as $key => $setting) {
                db_query(
                    'INSERT INTO settings (setting_key, setting_value, setting_group, updated_at) VALUES (:k, :v, :g, :t) ON DUPLICATE KEY UPDATE setting_value = :v2, setting_group = :g2, updated_at = :t2',
                    ['k' => $key, 'v' => $setting, 'g' => 'rewards', 't' => $now, 'v2' => $setting, 'g2' => 'rewards', 't2' => $now]
                );
            }
        });
        settings_cache_clear();
        settings_load();
        $label = reward_discount_label($changes['reward_type'], $changes['reward_value']);
        catalogue_log('settings', null, 'reward.settings', 'Thank-you coupon ' . ($form['enabled'] === '1' ? 'on: ' . $label : 'off'), $before, $changes);
        flash('success', $form['enabled'] === '1'
            ? 'Saved. Every first website order now gets a thank-you code for ' . $label . ' their second order.'
            : 'Saved. The thank-you coupon is off, so new orders get no code. Codes already given still work until they expire.');
        redirect('/admin/coupons/reward', 303);
    }
}

$per = max(5, min(100, setting_int('admin_rows_per_page', 20)));
$total = (int) db_fetch_column('SELECT COUNT(*) FROM coupons WHERE kind = :k', ['k' => REWARD_KIND]);
$page = min(max(1, (int) request_query('page', 1)), max(1, (int) ceil($total / $per)));
$codes = db_fetch_all(
    "SELECT c.id, c.code, c.type, c.value, c.min_order_total, c.used_count, c.expires_at, c.is_active, c.reward_phone, c.created_at,
        o.order_number AS issued_order, o.customer_name, o.customer_phone,
        uo.order_number AS used_order, r.discount_amount,
        (SELECT COUNT(*) FROM orders x WHERE x.phone_normalized = c.reward_phone AND x.status <> 'cancelled') AS orders_count
     FROM coupons c
     LEFT JOIN orders o ON o.id = c.reward_order_id
     LEFT JOIN coupon_redemptions r ON r.coupon_id = c.id AND r.status = 'applied'
     LEFT JOIN orders uo ON uo.id = r.order_id
     WHERE c.kind = :k
     ORDER BY c.created_at DESC, c.id DESC
     LIMIT " . $per . ' OFFSET ' . (($page - 1) * $per),
    ['k' => REWARD_KIND]
);
foreach ($codes as &$code) {
    $code['state'] = coupon_reward_state($code, $now);
    $code['view'] = reward_view($code);
}
unset($code);
$stats = db_fetch(
    "SELECT COUNT(*) AS orders, COALESCE(SUM(o.grand_total), 0) AS billed, COALESCE(SUM(r.discount_amount), 0) AS discount
     FROM coupon_redemptions r JOIN coupons c ON c.id = r.coupon_id JOIN orders o ON o.id = r.order_id
     WHERE c.kind = :k AND r.status = 'applied' AND o.status <> 'cancelled'",
    ['k' => REWARD_KIND]
);
$waiting = (int) db_fetch_column(
    "SELECT COUNT(*) FROM coupons c WHERE c.kind = :k AND c.is_active = 1 AND c.used_count = 0 AND (c.expires_at IS NULL OR c.expires_at >= :now)
     AND (SELECT COUNT(*) FROM orders x WHERE x.phone_normalized = c.reward_phone AND x.status <> 'cancelled') = 1",
    ['k' => REWARD_KIND, 'now' => $now]
);

render_admin('coupon-reward.php', [
    'config' => $config,
    'form' => $form,
    'errors' => $errors,
    'codes' => $codes,
    'total' => $total,
    'page' => $page,
    'per' => $per,
    'stats' => ['issued' => $total, 'waiting' => $waiting, 'used' => (int) $stats['orders'], 'billed' => (string) $stats['billed'], 'discount' => (string) $stats['discount']],
    'preview' => ['code' => REWARD_CODE_PREFIX . 'K7M2QX', 'label' => reward_discount_label($config['type'], $config['value']), 'min_order' => money_paisa($config['min_order']) > 0 ? money($config['min_order']) : '', 'expires' => $config['valid_days'] > 0 ? date_short((new DateTimeImmutable($now))->modify('+' . $config['valid_days'] . ' days')->format('Y-m-d H:i:s')) : '', 'usable' => true],
    'returnTo' => '/admin/coupons/reward' . ($page > 1 ? '?page=' . $page : ''),
], ['title' => 'Thank-you coupon', 'body_class' => (string) $route['body_class'], 'back' => '/admin/coupons']);
