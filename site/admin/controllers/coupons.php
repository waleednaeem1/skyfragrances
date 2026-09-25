<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/coupons.php';

function coupons_log(?int $adminId, string $username, string $action, ?int $entityId, string $summary, ?array $before = null, ?array $after = null): void
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

function coupons_state(array $coupon, string $now): string
{
    if ((int) $coupon['is_active'] !== 1) {
        return 'disabled';
    }
    if ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
        return 'used_up';
    }
    if ($coupon['expires_at'] !== null && $coupon['expires_at'] < $now) {
        return 'expired';
    }
    if ($coupon['starts_at'] !== null && $coupon['starts_at'] > $now) {
        return 'scheduled';
    }
    return 'active';
}

function coupons_state_badge(string $state): array
{
    return match ($state) {
        'active' => ['status' => 'active', 'label' => 'Active', 'tone' => 'green'],
        'scheduled' => ['status' => 'scheduled', 'label' => 'Scheduled', 'tone' => 'slate'],
        'expired' => ['status' => 'expired', 'label' => 'Expired', 'tone' => 'muted'],
        'used_up' => ['status' => 'used_up', 'label' => 'Used up', 'tone' => 'maroon'],
        default => ['status' => 'disabled', 'label' => 'Disabled', 'tone' => 'muted'],
    };
}

function coupons_state_sql(string $state, array &$params): string
{
    $now = static function () use (&$params): string {
        $key = 'state_now' . (count($params) + 1);
        $params[$key] = now_karachi();
        return ':' . $key;
    };
    $notUsedUp = '(usage_limit IS NULL OR used_count < usage_limit)';
    return match ($state) {
        'disabled' => 'is_active = 0',
        'used_up' => 'is_active = 1 AND usage_limit IS NOT NULL AND used_count >= usage_limit',
        'expired' => 'is_active = 1 AND ' . $notUsedUp . ' AND expires_at IS NOT NULL AND expires_at < ' . $now(),
        'scheduled' => 'is_active = 1 AND ' . $notUsedUp . ' AND (expires_at IS NULL OR expires_at >= ' . $now() . ') AND starts_at IS NOT NULL AND starts_at > ' . $now(),
        'active' => 'is_active = 1 AND ' . $notUsedUp . ' AND (expires_at IS NULL OR expires_at >= ' . $now() . ') AND (starts_at IS NULL OR starts_at <= ' . $now() . ')',
        default => '1 = 1',
    };
}

function coupons_discount_label(array $coupon): string
{
    if ($coupon['type'] === 'percent') {
        return rtrim(rtrim((string) $coupon['value'], '0'), '.') . '% off';
    }
    return money((string) $coupon['value']) . ' off';
}

function coupons_share_text(array $coupon): string
{
    $line = 'Use code ' . $coupon['code'] . ' for ' . coupons_discount_label($coupon) . ' at Sky Fragrances';
    if (money_paisa((string) $coupon['min_order_total']) > 0) {
        $line .= ' on orders above ' . money((string) $coupon['min_order_total']);
    }
    if ($coupon['expires_at'] !== null) {
        $line .= ', valid till ' . date_short($coupon['expires_at']);
    }
    return $line . '. Shop at ' . settings_site_url() . '/shop';
}

$admin = auth_user();
$adminId = (int) ($admin['id'] ?? 0);
$adminName = (string) ($admin['username'] ?? '');

if ($route['name'] === 'admin.coupons.toggle') {
    $coupon = db_fetch('SELECT id, code, is_active FROM coupons WHERE id = :id', ['id' => (int) $params['id']]);
    if ($coupon === null) {
        flash('error', 'That coupon no longer exists.');
        redirect('/admin/coupons', 303);
    }
    $next = (int) $coupon['is_active'] === 1 ? 0 : 1;
    db_update('coupons', ['is_active' => $next, 'updated_at' => now_karachi()], ['id' => (int) $coupon['id']]);
    coupons_log($adminId, $adminName, 'coupon.toggle', (int) $coupon['id'], $coupon['code'] . ($next === 1 ? ' activated' : ' deactivated'), ['is_active' => (int) $coupon['is_active']], ['is_active' => $next]);
    flash('success', $coupon['code'] . ($next === 1 ? ' is active again.' : ' is now disabled. Customers can no longer apply it.'));
    $return = (string) request_post('return', '');
    redirect(preg_match('~^/admin/coupons(?:\?[^#\s]*)?$~', $return) ? $return : '/admin/coupons', 303);
}

$now = now_karachi();
$per = max(5, min(100, setting_int('admin_rows_per_page', 20)));
$page = max(1, (int) request_query('page', 1));
$q = coupon_normalize_code((string) request_query('q', ''));
$q = mb_substr($q, 0, 40);
$stateFilter = (string) request_query('status', '');
$typeFilter = (string) request_query('type', '');
$sort = (string) request_query('sort', 'created_desc');
$states = ['active' => 'Active', 'scheduled' => 'Scheduled', 'expired' => 'Expired', 'used_up' => 'Used up', 'disabled' => 'Disabled'];
if (!isset($states[$stateFilter])) {
    $stateFilter = '';
}
if (!in_array($typeFilter, COUPON_TYPES, true)) {
    $typeFilter = '';
}

$where = [];
$sqlParams = [];
if ($q !== '') {
    $where[] = 'code LIKE :q';
    $sqlParams['q'] = '%' . addcslashes($q, '%_\\') . '%';
}
if ($stateFilter !== '') {
    $where[] = coupons_state_sql($stateFilter, $sqlParams);
}
if ($typeFilter !== '') {
    $where[] = 'type = :type';
    $sqlParams['type'] = $typeFilter;
}
$whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
$orderBy = match ($sort) {
    'code_asc' => 'code ASC',
    'code_desc' => 'code DESC',
    'used_desc' => 'used_count DESC, code ASC',
    'used_asc' => 'used_count ASC, code ASC',
    'expires_asc' => 'expires_at IS NULL, expires_at ASC, code ASC',
    'expires_desc' => 'expires_at IS NULL, expires_at DESC, code ASC',
    'created_asc' => 'created_at ASC, id ASC',
    default => 'created_at DESC, id DESC',
};
if (!in_array($sort, ['code_asc', 'code_desc', 'used_desc', 'used_asc', 'expires_asc', 'expires_desc', 'created_asc', 'created_desc'], true)) {
    $sort = 'created_desc';
}

$total = (int) db_fetch_column('SELECT COUNT(*) FROM coupons' . $whereSql, $sqlParams);
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);
$listSql = 'SELECT id, code, type, value, min_order_total, usage_limit, per_phone_limit, used_count, starts_at, expires_at, is_active, created_at FROM coupons' . $whereSql . ' ORDER BY ' . $orderBy . ' LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per);
$coupons = db_fetch_all($listSql, $sqlParams);

$counts = ['all' => (int) db_fetch_column('SELECT COUNT(*) FROM coupons')];
foreach (array_keys($states) as $stateKey) {
    $countParams = [];
    $counts[$stateKey] = (int) db_fetch_column('SELECT COUNT(*) FROM coupons WHERE ' . coupons_state_sql($stateKey, $countParams), $countParams);
}

$rows = [];
foreach ($coupons as $coupon) {
    $state = coupons_state($coupon, $now);
    $coupon['state'] = $state;
    $coupon['state_badge'] = coupons_state_badge($state);
    $coupon['discount_label'] = coupons_discount_label($coupon);
    $coupon['usage_label'] = (string) (int) $coupon['used_count'] . ' / ' . ($coupon['usage_limit'] === null ? "\u{221E}" : (string) (int) $coupon['usage_limit']);
    $coupon['window_label'] = ($coupon['starts_at'] === null ? 'Any time' : date_short($coupon['starts_at'])) . " \u{2192} " . ($coupon['expires_at'] === null ? 'No expiry' : date_short($coupon['expires_at']));
    $coupon['share_url'] = 'https://wa.me/?text=' . rawurlencode(coupons_share_text($coupon));
    $rows[] = $coupon;
}

$query = $_GET;
unset($query['page']);
$removeUrl = static function (string $key) use ($query): string {
    $copy = $query;
    unset($copy[$key]);
    return '/admin/coupons' . ($copy === [] ? '' : '?' . http_build_query($copy));
};
$active = [];
if ($q !== '') {
    $active[] = ['label' => 'Code: ' . $q, 'remove_url' => $removeUrl('q')];
}
if ($stateFilter !== '') {
    $active[] = ['label' => 'Status: ' . $states[$stateFilter], 'remove_url' => $removeUrl('status')];
}
if ($typeFilter !== '') {
    $active[] = ['label' => 'Type: ' . ($typeFilter === 'percent' ? 'Percent' : 'Fixed amount'), 'remove_url' => $removeUrl('type')];
}

render_admin('coupons.php', [
    'coupons' => $rows,
    'counts' => $counts,
    'states' => $states,
    'filters' => ['q' => $q, 'status' => $stateFilter, 'type' => $typeFilter, 'sort' => $sort],
    'activeChips' => $active,
    'page' => $page,
    'per' => $per,
    'total' => $total,
    'returnTo' => '/admin/coupons' . ($query === [] ? '' : '?' . http_build_query($query)),
], ['title' => 'Coupons', 'body_class' => (string) $route['body_class'], 'create_url' => '/admin/coupons/new']);
