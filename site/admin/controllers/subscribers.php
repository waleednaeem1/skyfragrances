<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/catalogue.php';

const SUBSCRIBERS_STATUSES = ['subscribed', 'unsubscribed'];
const SUBSCRIBERS_SOURCES = ['footer' => 'Footer', 'home' => 'Home page', 'checkout' => 'Checkout', 'admin' => 'Added by you'];

function subscribers_filters(string $defaultStatus): array
{
    $status = (string) request_query('status', $defaultStatus);
    if (!in_array($status, ['subscribed', 'unsubscribed', 'all'], true)) {
        $status = $defaultStatus;
    }
    $source = (string) request_query('source', '');
    if (!isset(SUBSCRIBERS_SOURCES[$source])) {
        $source = '';
    }
    $q = mb_substr(trim((string) request_query('q', '')), 0, 60);
    return ['status' => $status, 'source' => $source, 'q' => $q];
}

function subscribers_where(array $filters, array &$bind): string
{
    $where = ['1 = 1'];
    if ($filters['status'] !== 'all') {
        $where[] = 'status = :status';
        $bind['status'] = $filters['status'];
    }
    if ($filters['source'] !== '') {
        $where[] = 'source = :source';
        $bind['source'] = $filters['source'];
    }
    if ($filters['q'] !== '') {
        $where[] = 'email LIKE :q';
        $bind['q'] = '%' . addcslashes($filters['q'], '%_\\') . '%';
    }
    return ' WHERE ' . implode(' AND ', $where);
}

function subscribers_csv_cell(?string $value): string
{
    $value = str_replace("\0", '', (string) $value);
    $lead = ltrim($value, " \t\r\n");
    if ($lead !== '' && in_array($lead[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $value;
    }
    return $value;
}

function subscribers_csv_date(?string $datetime): string
{
    return $datetime === null || $datetime === '' ? '' : date('Y-m-d H:i', strtotime($datetime));
}

function subscribers_export(array $filters): never
{
    $bind = [];
    $whereSql = subscribers_where($filters, $bind);
    $total = (int) db_fetch_column('SELECT COUNT(*) FROM newsletter_subscribers' . $whereSql, $bind);
    $summary = array_filter(['status' => $filters['status'], 'source' => $filters['source'], 'q' => $filters['q'] === '' ? '' : '(set)'], 'strlen');
    catalogue_log('subscriber', null, 'subscribers.export', 'Exported ' . $total . ' subscriber' . ($total === 1 ? '' : 's') . ' (' . http_build_query($summary, '', ', ') . ')', null, ['filters' => $summary, 'rows' => $total]);
    $stamp = (new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi')))->format('Y-m-d');
    $filename = 'sky-subscribers-' . ($filters['status'] === 'all' ? 'all-' : ($filters['status'] === 'unsubscribed' ? 'unsubscribed-' : '')) . $stamp . '.csv';
    response_clear_buffers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');
    header_no_store();
    $out = fopen('php://output', 'wb');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Email', 'Status', 'Source', 'Subscribed At', 'Unsubscribed At'], ',', '"', '', "\r\n");
    $pdo = db();
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    try {
        $rows = db_query('SELECT email, status, source, subscribed_at, unsubscribed_at FROM newsletter_subscribers' . $whereSql . ' ORDER BY subscribed_at DESC, id DESC', $bind);
        while (($row = $rows->fetch()) !== false) {
            fputcsv($out, [
                subscribers_csv_cell($row['email']),
                subscribers_csv_cell($row['status']),
                subscribers_csv_cell($row['source']),
                subscribers_csv_date($row['subscribed_at']),
                subscribers_csv_date($row['unsubscribed_at']),
            ], ',', '"', '', "\r\n");
        }
        $rows->closeCursor();
    } finally {
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }
    fclose($out);
    exit;
}

function subscribers_return_url(): string
{
    return request_return_path('/admin/subscribers');
}

$routeName = (string) ($route['name'] ?? '');
$now = now_karachi();

if ($routeName === 'admin.subscribers.export') {
    subscribers_export(subscribers_filters('subscribed'));
}

if ($routeName === 'admin.subscribers.status') {
    $subscriber = db_fetch('SELECT id, email, status, source FROM newsletter_subscribers WHERE id = :id', ['id' => (int) $params['id']]);
    if ($subscriber === null) {
        flash('error', 'That subscriber no longer exists.');
        redirect('/admin/subscribers', 303);
    }
    $to = (string) request_post('to', '');
    if ($to === 'delete') {
        if ((string) request_post('confirm_word', '') !== 'DELETE') {
            flash('error', 'Type DELETE to confirm removing ' . $subscriber['email'] . '.');
            redirect(subscribers_return_url(), 303);
        }
        db_delete('newsletter_subscribers', ['id' => (int) $subscriber['id']]);
        catalogue_log('subscriber', (int) $subscriber['id'], 'subscriber.delete', 'Deleted subscriber #' . (int) $subscriber['id'] . ' (' . $subscriber['status'] . ')');
        flash('success', $subscriber['email'] . ' deleted. If they sign up again they start fresh.');
        redirect(subscribers_return_url(), 303);
    }
    if (!in_array($to, SUBSCRIBERS_STATUSES, true)) {
        flash('error', 'Choose subscribe or unsubscribe.');
        redirect(subscribers_return_url(), 303);
    }
    if ($to === $subscriber['status']) {
        flash('info', $subscriber['email'] . ' is already ' . $to . '.');
        redirect(subscribers_return_url(), 303);
    }
    db_update('newsletter_subscribers', ['status' => $to, 'unsubscribed_at' => $to === 'unsubscribed' ? $now : null], ['id' => (int) $subscriber['id'], 'status' => (string) $subscriber['status']]);
    catalogue_log('subscriber', (int) $subscriber['id'], 'subscriber.' . $to, ucfirst($to) . ' subscriber #' . (int) $subscriber['id'], ['status' => $subscriber['status']], ['status' => $to]);
    flash('success', $to === 'unsubscribed'
        ? $subscriber['email'] . ' unsubscribed. The row is kept as proof they opted out.'
        : $subscriber['email'] . ' is subscribed again.');
    redirect(subscribers_return_url(), 303);
}

$per = max(5, min(100, setting_int('admin_rows_per_page', 20)));
$page = max(1, (int) request_query('page', 1));
$filters = subscribers_filters('all');
$bind = [];
$whereSql = subscribers_where($filters, $bind);
$total = (int) db_fetch_column('SELECT COUNT(*) FROM newsletter_subscribers' . $whereSql, $bind);
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);
$subscribers = db_fetch_all('SELECT id, email, status, source, subscribed_at, unsubscribed_at FROM newsletter_subscribers' . $whereSql . ' ORDER BY subscribed_at DESC, id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $bind);

$counts = ['subscribed' => 0, 'unsubscribed' => 0];
foreach (db_fetch_all('SELECT status, COUNT(*) AS n FROM newsletter_subscribers GROUP BY status') as $row) {
    $counts[(string) $row['status']] = (int) $row['n'];
}
$counts['all'] = $counts['subscribed'] + $counts['unsubscribed'];
$query = array_filter(['status' => $filters['status'] === 'all' ? '' : $filters['status'], 'source' => $filters['source'], 'q' => $filters['q']], 'strlen');
$removeUrl = static function (string $key) use ($query): string {
    $rest = $query;
    unset($rest[$key]);
    return '/admin/subscribers' . ($rest === [] ? '' : '?' . http_build_query($rest));
};
$activeChips = [];
if ($filters['status'] !== 'all') {
    $activeChips[] = ['label' => 'Status: ' . ucfirst($filters['status']), 'remove_url' => $removeUrl('status')];
}
if ($filters['source'] !== '') {
    $activeChips[] = ['label' => 'Source: ' . SUBSCRIBERS_SOURCES[$filters['source']], 'remove_url' => $removeUrl('source')];
}
if ($filters['q'] !== '') {
    $activeChips[] = ['label' => 'Search: ' . $filters['q'], 'remove_url' => $removeUrl('q')];
}
$exportQuery = $query;
if (!isset($exportQuery['status'])) {
    $exportQuery['status'] = 'all';
}

render_admin('subscribers.php', [
    'subscribers' => $subscribers,
    'total' => $total,
    'page' => $page,
    'per' => $per,
    'filters' => $filters,
    'query' => $query,
    'counts' => $counts,
    'activeChips' => $activeChips,
    'sources' => SUBSCRIBERS_SOURCES,
    'exportUrl' => '/admin/subscribers/export.csv?' . http_build_query($exportQuery),
    'exportCount' => $total,
    'returnTo' => '/admin/subscribers' . ($query === [] ? '' : '?' . http_build_query($query)),
], ['title' => 'Subscribers', 'body_class' => (string) $route['body_class']]);
