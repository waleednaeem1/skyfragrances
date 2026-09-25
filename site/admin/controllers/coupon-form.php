<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/coupons.php';

function coupon_form_log(?int $adminId, string $username, string $action, ?int $entityId, string $summary, ?array $before, ?array $after): void
{
    db_insert('admin_activity_log', [
        'admin_id' => $adminId,
        'admin_username' => mb_substr($username, 0, 64),
        'entity_type' => 'coupon',
        'entity_id' => $entityId,
        'action' => $action,
        'summary' => mb_substr($summary, 0, 255),
        'before_json' => $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE),
        'after_json' => $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE),
        'ip_hash' => request_ip_hash(),
        'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        'created_at' => now_karachi(),
    ]);
}

function coupon_form_datetime_in(?string $stored): string
{
    if ($stored === null || $stored === '') {
        return '';
    }
    $time = strtotime($stored);
    return $time === false ? '' : date('Y-m-d\TH:i', $time);
}

function coupon_form_datetime_parse(string $raw, bool $endOfDay): ?string
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    $formats = ['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d H:i:s', 'Y-m-d'];
    foreach ($formats as $format) {
        $parsed = DateTimeImmutable::createFromFormat('!' . $format, $raw, new DateTimeZone('Asia/Karachi'));
        if ($parsed !== false && $parsed->format($format) === $raw) {
            if ($format === 'Y-m-d' && $endOfDay) {
                $parsed = $parsed->setTime(23, 59, 59);
            }
            return $parsed->format('Y-m-d H:i:s');
        }
    }
    return 'invalid';
}

function coupon_form_snapshot(array $c): array
{
    return [
        'code' => (string) $c['code'],
        'type' => (string) $c['type'],
        'value' => (string) $c['value'],
        'min_order_total' => (string) $c['min_order_total'],
        'usage_limit' => $c['usage_limit'] === null ? null : (int) $c['usage_limit'],
        'per_phone_limit' => $c['per_phone_limit'] === null ? null : (int) $c['per_phone_limit'],
        'starts_at' => $c['starts_at'],
        'expires_at' => $c['expires_at'],
        'is_active' => (int) $c['is_active'],
    ];
}

function coupon_form_ledger(int $couponId): array
{
    $row = db_fetch(
        "SELECT
            COALESCE(SUM(status = 'applied'), 0) AS applied,
            COALESCE(SUM(status = 'reverted'), 0) AS reverted,
            COALESCE(SUM(CASE WHEN status = 'applied' THEN discount_amount ELSE 0 END), 0) AS discount_total,
            COALESCE(SUM(CASE WHEN status = 'applied' THEN order_subtotal ELSE 0 END), 0) AS subtotal_total,
            COUNT(DISTINCT CASE WHEN status = 'applied' THEN phone_normalized END) AS phones,
            MAX(CASE WHEN status = 'applied' THEN created_at END) AS last_used_at
         FROM coupon_redemptions WHERE coupon_id = :id",
        ['id' => $couponId]
    ) ?? [];
    $recent = db_fetch_all(
        "SELECT r.created_at, r.discount_amount, r.order_subtotal, r.status, o.order_number
         FROM coupon_redemptions r
         JOIN orders o ON o.id = r.order_id
         WHERE r.coupon_id = :id
         ORDER BY r.created_at DESC, r.id DESC
         LIMIT 8",
        ['id' => $couponId]
    );
    return [
        'applied' => (int) ($row['applied'] ?? 0),
        'reverted' => (int) ($row['reverted'] ?? 0),
        'discount_total' => money_from_paisa(money_paisa((string) ($row['discount_total'] ?? '0'))),
        'subtotal_total' => money_from_paisa(money_paisa((string) ($row['subtotal_total'] ?? '0'))),
        'phones' => (int) ($row['phones'] ?? 0),
        'last_used_at' => $row['last_used_at'] ?? null,
        'recent' => $recent,
    ];
}

$admin = auth_user();
$adminId = (int) ($admin['id'] ?? 0);
$adminName = (string) ($admin['username'] ?? '');
$isNew = $route['name'] === 'admin.coupons.new';
$coupon = null;
if (!$isNew) {
    $coupon = db_fetch('SELECT * FROM coupons WHERE id = :id', ['id' => (int) $params['id']]);
    if ($coupon === null) {
        flash('error', 'That coupon no longer exists.');
        redirect('/admin/coupons', 303);
    }
}
$usedCount = $coupon === null ? 0 : (int) $coupon['used_count'];

$form = [
    'code' => $coupon['code'] ?? '',
    'type' => $coupon['type'] ?? 'percent',
    'value' => $coupon === null ? '' : ($coupon['type'] === 'percent' ? (string) (int) round((float) $coupon['value']) : money_from_paisa(money_paisa((string) $coupon['value']))),
    'min_order_total' => $coupon === null ? '0' : money_from_paisa(money_paisa((string) $coupon['min_order_total'])),
    'usage_limit' => $coupon === null || $coupon['usage_limit'] === null ? '' : (string) (int) $coupon['usage_limit'],
    'per_phone_limit' => $coupon === null || $coupon['per_phone_limit'] === null ? '' : (string) (int) $coupon['per_phone_limit'],
    'starts_at' => coupon_form_datetime_in($coupon['starts_at'] ?? null),
    'expires_at' => coupon_form_datetime_in($coupon['expires_at'] ?? null),
    'is_active' => $coupon === null ? 1 : (int) $coupon['is_active'],
];
$errors = [];

if (request_method() === 'POST') {
    $form['code'] = mb_substr(coupon_normalize_code((string) request_post('code', '')), 0, 40);
    $form['type'] = (string) request_post('type', 'percent');
    $form['value'] = trim((string) request_post('value', ''));
    $form['min_order_total'] = trim((string) request_post('min_order_total', '0'));
    $form['usage_limit'] = trim((string) request_post('usage_limit', ''));
    $form['per_phone_limit'] = trim((string) request_post('per_phone_limit', ''));
    $form['starts_at'] = trim((string) request_post('starts_at', ''));
    $form['expires_at'] = trim((string) request_post('expires_at', ''));
    $form['is_active'] = request_post('is_active') !== null ? 1 : 0;

    if ($form['code'] === '') {
        $errors['code'] = 'Enter a code.';
    } elseif (mb_strlen($form['code']) > 32 || !preg_match('/^[A-Z0-9-]+$/', $form['code'])) {
        $errors['code'] = 'Use up to 32 letters, numbers and dashes only.';
    } else {
        $dupeParams = ['code' => $form['code']];
        $dupeSql = 'SELECT 1 FROM coupons WHERE code = :code';
        if ($coupon !== null) {
            $dupeSql .= ' AND id <> :id';
            $dupeParams['id'] = (int) $coupon['id'];
        }
        if (db_exists($dupeSql, $dupeParams)) {
            $errors['code'] = 'That code already exists.';
        }
    }

    if (!in_array($form['type'], COUPON_TYPES, true)) {
        $errors['type'] = 'Choose percent or fixed amount.';
        $form['type'] = 'percent';
    }

    $valueStored = null;
    if ($form['type'] === 'percent') {
        if (!preg_match('/^\d{1,3}$/', $form['value']) || (int) $form['value'] < 1 || (int) $form['value'] > 90) {
            $errors['value'] = 'Percent must be a whole number from 1 to 90.';
        } else {
            $valueStored = (string) (int) $form['value'] . '.00';
        }
    } else {
        $valuePaisa = money_paisa(str_replace(',', '', $form['value']));
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', str_replace(',', '', $form['value'])) || $valuePaisa <= 0 || $valuePaisa > 100000 * 100) {
            $errors['value'] = 'Enter an amount above 0 and up to Rs. 100,000.';
        } else {
            $valueStored = money_from_paisa($valuePaisa);
        }
    }

    $minRaw = str_replace(',', '', $form['min_order_total']);
    $minStored = '0.00';
    if ($minRaw !== '') {
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $minRaw) || money_paisa($minRaw) > 99999999 * 100) {
            $errors['min_order_total'] = 'Enter 0 or a positive amount.';
        } else {
            $minStored = money_from_paisa(money_paisa($minRaw));
        }
    }

    $usageStored = null;
    if ($form['usage_limit'] !== '') {
        if (!preg_match('/^\d{1,9}$/', $form['usage_limit']) || (int) $form['usage_limit'] < 1) {
            $errors['usage_limit'] = 'Leave blank for unlimited, or enter a whole number of 1 or more.';
        } elseif ((int) $form['usage_limit'] < $usedCount) {
            $errors['usage_limit'] = 'This code has already been used ' . $usedCount . ' times. The limit cannot be lower than that.';
        } else {
            $usageStored = (int) $form['usage_limit'];
        }
    }

    $perPhoneStored = null;
    if ($form['per_phone_limit'] !== '') {
        if (!preg_match('/^\d{1,5}$/', $form['per_phone_limit']) || (int) $form['per_phone_limit'] < 1 || (int) $form['per_phone_limit'] > 65535) {
            $errors['per_phone_limit'] = 'Leave blank for no limit, or enter a whole number of 1 or more.';
        } else {
            $perPhoneStored = (int) $form['per_phone_limit'];
        }
    }

    $startsStored = coupon_form_datetime_parse($form['starts_at'], false);
    if ($startsStored === 'invalid') {
        $errors['starts_at'] = 'Enter a valid date and time.';
        $startsStored = null;
    }
    $expiresStored = coupon_form_datetime_parse($form['expires_at'], true);
    if ($expiresStored === 'invalid') {
        $errors['expires_at'] = 'Enter a valid date and time.';
        $expiresStored = null;
    }
    if ($startsStored !== null && $expiresStored !== null && $expiresStored <= $startsStored) {
        $errors['expires_at'] = 'Expiry must be after the start.';
    }

    if ($errors === []) {
        $now = now_karachi();
        $data = [
            'code' => $form['code'],
            'type' => $form['type'],
            'value' => $valueStored,
            'min_order_total' => $minStored,
            'usage_limit' => $usageStored,
            'per_phone_limit' => $perPhoneStored,
            'starts_at' => $startsStored,
            'expires_at' => $expiresStored,
            'is_active' => $form['is_active'],
        ];
        try {
            if ($coupon === null) {
                $newId = db_insert('coupons', $data + ['used_count' => 0, 'created_at' => $now, 'updated_at' => null]);
                coupon_form_log($adminId, $adminName, 'coupon.create', $newId, 'Created coupon ' . $form['code'], null, coupon_form_snapshot($data));
                flash('success', $form['code'] . ' created. Customers can apply it at checkout' . ($form['is_active'] === 1 ? '.' : ' once you enable it.'));
            } else {
                db_update('coupons', $data + ['updated_at' => $now], ['id' => (int) $coupon['id']]);
                coupon_form_log($adminId, $adminName, 'coupon.update', (int) $coupon['id'], 'Updated coupon ' . $form['code'], coupon_form_snapshot($coupon), coupon_form_snapshot($data));
                flash('success', $form['code'] . ' saved.');
            }
            redirect('/admin/coupons', 303);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $errors['code'] = 'That code already exists.';
            } else {
                throw $exception;
            }
        }
    }
}

$ledger = $coupon === null ? null : coupon_form_ledger((int) $coupon['id']);
$title = $coupon === null ? 'New coupon' : $coupon['code'];

render_admin('coupon-form.php', [
    'isNew' => $isNew,
    'coupon' => $coupon,
    'form' => $form,
    'errors' => $errors,
    'usedCount' => $usedCount,
    'ledger' => $ledger,
    'shareUrl' => $coupon === null ? '' : 'https://wa.me/?text=' . rawurlencode('Use code ' . $coupon['code'] . ' at Sky Fragrances. ' . settings_site_url() . '/shop'),
], ['title' => $title, 'body_class' => (string) $route['body_class'], 'back' => '/admin/coupons', 'create_url' => '/admin/coupons/new']);
