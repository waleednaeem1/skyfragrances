<?php
defined('SKYFR') || exit;

const ACTIVITY_ENTITY_LABELS = [
    'order' => 'Orders',
    'product' => 'Products',
    'collection' => 'Collections',
    'scent_family' => 'Scent families',
    'coupon' => 'Coupons',
    'review' => 'Reviews',
    'message' => 'Messages',
    'subscriber' => 'Subscribers',
    'page' => 'Pages',
    'setting' => 'Settings',
    'admin_user' => 'Sign-ins & password',
    'export' => 'Exports',
    'tool' => 'Tools',
];

function activity_clean_token(mixed $raw, int $max = 48): string
{
    $value = trim((string) (is_string($raw) ? $raw : ''));
    return preg_match('/^[a-z0-9_.-]{1,' . $max . '}$/i', $value) ? strtolower($value) : '';
}

function activity_entity_href(string $type, ?int $id, ?string $orderNumber): ?string
{
    if ($id === null && $type !== 'setting') {
        return null;
    }
    return match ($type) {
        'order' => $orderNumber !== null && $orderNumber !== '' ? '/admin/orders/' . $orderNumber : null,
        'product' => '/admin/products/' . $id,
        'collection' => '/admin/collections/' . $id,
        'coupon' => '/admin/coupons/' . $id,
        'message' => '/admin/messages/' . $id,
        'setting' => '/admin/settings',
        default => null,
    };
}

function activity_json_pretty(?string $json): string
{
    if ($json === null || trim($json) === '') {
        return '';
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return mb_substr($json, 0, 2000);
    }
    $lines = [];
    foreach ($decoded as $key => $value) {
        $shown = is_scalar($value) || $value === null ? (string) ($value ?? 'null') : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $lines[] = $key . ': ' . mb_substr((string) $shown, 0, 160);
    }
    return mb_substr(implode("\n", $lines), 0, 2000);
}

$per = max(10, min(100, setting_int('admin_rows_per_page', 20)));
$page = max(1, (int) request_query('page', 1));
$filters = [
    'entity' => activity_clean_token(request_query('entity')),
    'id' => max(0, (int) request_query('id', 0)),
    'action' => activity_clean_token(request_query('action')),
    'q' => mb_substr(trim((string) (is_string(request_query('q')) ? request_query('q') : '')), 0, 60),
    'from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) request_query('from', '')) ? (string) request_query('from') : '',
    'to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) request_query('to', '')) ? (string) request_query('to') : '',
];
if ($filters['entity'] !== '' && !isset(ACTIVITY_ENTITY_LABELS[$filters['entity']])) {
    $filters['entity'] = '';
}

$where = [];
$bind = [];
if ($filters['entity'] !== '') {
    $where[] = 'l.entity_type = :entity';
    $bind['entity'] = $filters['entity'];
}
if ($filters['id'] > 0) {
    $where[] = 'l.entity_id = :eid';
    $bind['eid'] = $filters['id'];
}
if ($filters['action'] !== '') {
    $where[] = 'l.action = :action';
    $bind['action'] = $filters['action'];
}
if ($filters['q'] !== '') {
    $where[] = 'l.summary LIKE :q';
    $bind['q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']) . '%';
}
if ($filters['from'] !== '') {
    $where[] = 'l.created_at >= :from';
    $bind['from'] = $filters['from'] . ' 00:00:00';
}
if ($filters['to'] !== '') {
    $where[] = 'l.created_at < :to';
    $bind['to'] = (new DateTimeImmutable($filters['to']))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
}
$whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

$total = (int) db_fetch_column('SELECT COUNT(*) FROM admin_activity_log l' . $whereSql, $bind);
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);
$rows = db_fetch_all(
    "SELECT l.id, l.admin_username, l.entity_type, l.entity_id, l.action, l.summary, l.before_json, l.after_json, l.created_at,
            CASE WHEN l.entity_type = 'order' THEN o.order_number ELSE NULL END AS order_number
     FROM admin_activity_log l
     LEFT JOIN orders o ON l.entity_type = 'order' AND o.id = l.entity_id" . $whereSql . '
     ORDER BY l.id DESC LIMIT :lim OFFSET :off',
    $bind + ['lim' => $per, 'off' => ($page - 1) * $per]
);

$actionOptions = ['' => 'Any action'];
foreach (db_fetch_all('SELECT DISTINCT action FROM admin_activity_log ORDER BY action ASC LIMIT 200') as $row) {
    $actionOptions[(string) $row['action']] = (string) $row['action'];
}
$entityOptions = ['' => 'Everything'] + ACTIVITY_ENTITY_LABELS;

render_admin('activity.php', [
    'rows' => $rows,
    'total' => $total,
    'page' => $page,
    'per' => $per,
    'filters' => $filters,
    'entityOptions' => $entityOptions,
    'actionOptions' => $actionOptions,
], ['title' => 'Activity log'] + $head);
