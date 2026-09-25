<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/catalogue.php';

const REVIEWS_STATUSES = ['pending', 'approved', 'rejected'];
const REVIEWS_BULK_ACTIONS = ['approve', 'reject', 'pending', 'delete_sample'];
const REVIEWS_STATUS_LABELS = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

function reviews_ids_from_post(): array
{
    $raw = request_post('ids', []);
    if (!is_array($raw)) {
        return [];
    }
    $ids = [];
    foreach ($raw as $value) {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        if ($int !== false && $int > 0) {
            $ids[$int] = $int;
        }
    }
    return array_values($ids);
}

function reviews_recompute_rating(int $productId): void
{
    db_query(
        "UPDATE products SET
            rating_avg = COALESCE((SELECT ROUND(AVG(r.rating), 2) FROM reviews r WHERE r.product_id = :p1 AND r.status = 'approved'), 0.00),
            rating_count = (SELECT COUNT(*) FROM reviews r2 WHERE r2.product_id = :p2 AND r2.status = 'approved')
         WHERE id = :id",
        ['p1' => $productId, 'p2' => $productId, 'id' => $productId]
    );
}

function reviews_set_status(array $review, string $to, ?int $adminId, string $now): bool
{
    if (!in_array($to, REVIEWS_STATUSES, true) || $review['status'] === $to) {
        return false;
    }
    $changed = db_update('reviews', [
        'status' => $to,
        'moderated_by' => $adminId,
        'approved_at' => $to === 'approved' ? $now : null,
        'rejected_at' => $to === 'rejected' ? $now : null,
    ], ['id' => (int) $review['id'], 'status' => (string) $review['status']]);
    if ($changed !== 1) {
        return false;
    }
    reviews_recompute_rating((int) $review['product_id']);
    catalogue_log('review', (int) $review['id'], 'review.' . $to, ucfirst($to) . ' review by ' . $review['customer_name'] . ' on ' . ($review['product_name'] ?? 'product #' . $review['product_id']), ['status' => $review['status']], ['status' => $to]);
    return true;
}

function reviews_delete(array $review): void
{
    db_delete('reviews', ['id' => (int) $review['id']]);
    reviews_recompute_rating((int) $review['product_id']);
    catalogue_log('review', (int) $review['id'], 'review.delete', 'Deleted review by ' . $review['customer_name'] . ' on ' . ($review['product_name'] ?? 'product #' . $review['product_id']) . ((int) $review['is_sample'] === 1 ? ' (sample)' : ''));
}

function reviews_fetch_many(array $ids): array
{
    if ($ids === []) {
        return [];
    }
    $names = [];
    $bind = [];
    foreach (array_values($ids) as $i => $id) {
        $names[] = ':id' . $i;
        $bind['id' . $i] = $id;
    }
    return db_fetch_all('SELECT r.id, r.product_id, r.customer_name, r.status, r.is_sample, p.name AS product_name FROM reviews r JOIN products p ON p.id = r.product_id WHERE r.id IN (' . implode(', ', $names) . ')', $bind);
}

function reviews_relative(?string $datetime, string $now): string
{
    if ($datetime === null || $datetime === '') {
        return '';
    }
    $seconds = max(0, strtotime($now) - strtotime($datetime));
    if ($seconds < 60) {
        return 'just now';
    }
    if ($seconds < 3600) {
        $n = intdiv($seconds, 60);
        return $n . ' minute' . ($n === 1 ? '' : 's') . ' ago';
    }
    if ($seconds < 86400) {
        $n = intdiv($seconds, 3600);
        return $n . ' hour' . ($n === 1 ? '' : 's') . ' ago';
    }
    $days = intdiv($seconds, 86400);
    if ($days === 1) {
        return 'yesterday';
    }
    if ($days < 14) {
        return $days . ' days ago';
    }
    if ($days < 60) {
        return intdiv($days, 7) . ' weeks ago';
    }
    return date_short($datetime);
}

function reviews_stars(int $rating): string
{
    $rating = max(0, min(5, $rating));
    return str_repeat("\u{2605}", $rating) . str_repeat("\u{2606}", 5 - $rating);
}

function reviews_return_url(): string
{
    return request_return_path('/admin/reviews');
}

$routeName = (string) ($route['name'] ?? '');
$admin = auth_user();
$adminId = isset($admin['id']) ? (int) $admin['id'] : null;
$now = now_karachi();

if ($routeName === 'admin.reviews.status') {
    $review = db_fetch('SELECT r.id, r.product_id, r.customer_name, r.status, r.is_sample, p.name AS product_name FROM reviews r JOIN products p ON p.id = r.product_id WHERE r.id = :id', ['id' => (int) $params['id']]);
    if ($review === null) {
        flash('error', 'That review no longer exists.');
        redirect('/admin/reviews', 303);
    }
    $to = (string) request_post('to', '');
    if ($to === 'delete') {
        if ((string) request_post('confirm_word', '') !== 'DELETE') {
            flash('error', 'Type DELETE to confirm removing the review by ' . $review['customer_name'] . '.');
            redirect(reviews_return_url(), 303);
        }
        db_transaction(static function () use ($review): void {
            reviews_delete($review);
        });
        flash('success', 'Review by ' . $review['customer_name'] . ' deleted. ' . $review['product_name'] . "'s rating was recalculated.");
        redirect(reviews_return_url(), 303);
    }
    if (!in_array($to, REVIEWS_STATUSES, true)) {
        flash('error', 'Choose approve, reject or return to pending.');
        redirect(reviews_return_url(), 303);
    }
    $changed = db_transaction(static fn (): bool => reviews_set_status($review, $to, $adminId, $now));
    if (!$changed) {
        flash('info', 'That review was already ' . $to . '.');
    } else {
        flash('success', match ($to) {
            'approved' => 'Review by ' . $review['customer_name'] . ' is live on ' . $review['product_name'] . '.',
            'rejected' => 'Review by ' . $review['customer_name'] . ' rejected. It stays on record but never shows on the shop.',
            default => 'Review by ' . $review['customer_name'] . ' returned to the pending queue.',
        });
    }
    redirect(reviews_return_url(), 303);
}

if ($routeName === 'admin.reviews.bulk') {
    $action = (string) request_post('action', '');
    if (!in_array($action, REVIEWS_BULK_ACTIONS, true)) {
        flash('error', 'Choose a bulk action first.');
        redirect(reviews_return_url(), 303);
    }
    if ($action === 'delete_sample') {
        if ((string) request_post('confirm_word', '') !== 'DELETE') {
            flash('error', 'Type DELETE to confirm removing every sample review.');
            redirect(reviews_return_url(), 303);
        }
        $samples = db_fetch_all('SELECT r.id, r.product_id, r.customer_name, r.status, r.is_sample, p.name AS product_name FROM reviews r JOIN products p ON p.id = r.product_id WHERE r.is_sample = 1');
        if ($samples === []) {
            flash('info', 'There are no sample reviews left to delete.');
            redirect('/admin/reviews', 303);
        }
        db_transaction(static function () use ($samples): void {
            $productIds = [];
            foreach ($samples as $sample) {
                db_delete('reviews', ['id' => (int) $sample['id']]);
                $productIds[(int) $sample['product_id']] = true;
            }
            foreach (array_keys($productIds) as $productId) {
                reviews_recompute_rating($productId);
            }
            catalogue_log('review', null, 'review.delete_sample', count($samples) . ' sample reviews deleted', null, ['ids' => array_column($samples, 'id')]);
        });
        flash('success', count($samples) . ' sample review' . (count($samples) === 1 ? '' : 's') . ' deleted. Only real customer reviews remain.');
        redirect('/admin/reviews', 303);
    }
    $ids = reviews_ids_from_post();
    if ($ids === []) {
        flash('error', 'Select at least one review first.');
        redirect(reviews_return_url(), 303);
    }
    $to = ['approve' => 'approved', 'reject' => 'rejected', 'pending' => 'pending'][$action];
    $reviews = reviews_fetch_many($ids);
    $done = db_transaction(static function () use ($reviews, $to, $adminId, $now): int {
        $count = 0;
        foreach ($reviews as $review) {
            if (reviews_set_status($review, $to, $adminId, $now)) {
                $count++;
            }
        }
        return $count;
    });
    $skipped = count($ids) - $done;
    $verb = ['approved' => 'approved', 'rejected' => 'rejected', 'pending' => 'returned to pending'][$to];
    flash($done === 0 ? 'info' : 'success', $done . ' of ' . count($ids) . ' review' . (count($ids) === 1 ? '' : 's') . ' ' . $verb . ($skipped > 0 ? ' — ' . $skipped . ' already were, or no longer exist.' : '.'));
    redirect(reviews_return_url(), 303);
}

$per = max(5, min(100, setting_int('admin_rows_per_page', 20)));
$page = max(1, (int) request_query('page', 1));
$statusFilter = (string) request_query('status', 'pending');
if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'all'], true)) {
    $statusFilter = 'pending';
}
$productFilter = catalogue_int_input(request_query('product_id', ''), 1, 4294967295);
$productFilter = $productFilter === PHP_INT_MIN ? null : $productFilter;
$ratingFilter = catalogue_int_input(request_query('rating', ''), 1, 5);
$ratingFilter = $ratingFilter === PHP_INT_MIN ? null : $ratingFilter;
$sampleOnly = (string) request_query('sample', '') === '1';
$q = mb_substr(trim((string) request_query('q', '')), 0, 60);
$sort = (string) request_query('sort', '');
if (!in_array($sort, ['date_asc', 'date_desc', 'rating_asc', 'rating_desc'], true)) {
    $sort = $statusFilter === 'pending' ? 'date_asc' : 'date_desc';
}

$where = ['1 = 1'];
$bind = [];
if ($statusFilter !== 'all') {
    $where[] = 'r.status = :status';
    $bind['status'] = $statusFilter;
}
if ($productFilter !== null) {
    $where[] = 'r.product_id = :product_id';
    $bind['product_id'] = $productFilter;
}
if ($ratingFilter !== null) {
    $where[] = 'r.rating = :rating';
    $bind['rating'] = $ratingFilter;
}
if ($sampleOnly) {
    $where[] = 'r.is_sample = 1';
}
if ($q !== '') {
    $bind['q1'] = $bind['q2'] = $bind['q3'] = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(r.customer_name LIKE :q1 OR r.title LIKE :q2 OR r.body LIKE :q3)';
}
$whereSql = ' WHERE ' . implode(' AND ', $where);
$orderBy = match ($sort) {
    'date_asc' => 'r.created_at ASC, r.id ASC',
    'rating_asc' => 'r.rating ASC, r.created_at DESC',
    'rating_desc' => 'r.rating DESC, r.created_at DESC',
    default => 'r.created_at DESC, r.id DESC',
};
$total = (int) db_fetch_column('SELECT COUNT(*) FROM reviews r' . $whereSql, $bind);
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);
$reviews = db_fetch_all(
    'SELECT r.id, r.product_id, r.customer_name, r.customer_city, r.rating, r.title, r.body, r.status, r.is_sample, r.created_at, r.approved_at, r.rejected_at,
        p.name AS product_name, p.slug AS product_slug,
        (SELECT i.filename FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.sort_order ASC LIMIT 1) AS product_image
     FROM reviews r JOIN products p ON p.id = r.product_id' . $whereSql . ' ORDER BY ' . $orderBy . ' LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per),
    $bind
);
foreach ($reviews as &$review) {
    $review['relative'] = reviews_relative($review['created_at'], $now);
    $review['stars'] = reviews_stars((int) $review['rating']);
    $review['thumb'] = catalogue_product_image_url((int) $review['product_id'], $review['product_image'], 'thumb');
}
unset($review);

$counts = ['all' => 0];
foreach (db_fetch_all('SELECT status, COUNT(*) AS n FROM reviews GROUP BY status') as $row) {
    $counts[(string) $row['status']] = (int) $row['n'];
    $counts['all'] += (int) $row['n'];
}
foreach (REVIEWS_STATUSES as $statusKey) {
    $counts[$statusKey] = $counts[$statusKey] ?? 0;
}
$sampleCount = (int) db_fetch_column('SELECT COUNT(*) FROM reviews WHERE is_sample = 1');
$productOptions = ['' => 'Any product'];
foreach (db_fetch_all('SELECT DISTINCT p.id, p.name FROM products p JOIN reviews r ON r.product_id = p.id ORDER BY p.name ASC') as $row) {
    $productOptions[(string) $row['id']] = (string) $row['name'];
}

$query = array_filter([
    'status' => $statusFilter === 'pending' ? '' : $statusFilter,
    'product_id' => $productFilter, 'rating' => $ratingFilter, 'sample' => $sampleOnly ? '1' : '', 'q' => $q,
    'sort' => $sort === ($statusFilter === 'pending' ? 'date_asc' : 'date_desc') ? '' : $sort,
], static fn ($v): bool => $v !== '' && $v !== null);
$removeUrl = static function (string $key) use ($query): string {
    $rest = $query;
    unset($rest[$key]);
    return '/admin/reviews' . ($rest === [] ? '' : '?' . http_build_query($rest));
};
$activeChips = [];
if ($productFilter !== null && isset($productOptions[(string) $productFilter])) {
    $activeChips[] = ['label' => 'Product: ' . $productOptions[(string) $productFilter], 'remove_url' => $removeUrl('product_id')];
}
if ($ratingFilter !== null) {
    $activeChips[] = ['label' => $ratingFilter . ' star' . ($ratingFilter === 1 ? '' : 's'), 'remove_url' => $removeUrl('rating')];
}
if ($sampleOnly) {
    $activeChips[] = ['label' => 'Sample data only', 'remove_url' => $removeUrl('sample')];
}
if ($q !== '') {
    $activeChips[] = ['label' => 'Search: ' . $q, 'remove_url' => $removeUrl('q')];
}
$tabs = [];
foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label) {
    $tabQuery = $query;
    unset($tabQuery['status'], $tabQuery['sort']);
    if ($key !== 'pending') {
        $tabQuery = ['status' => $key] + $tabQuery;
    }
    $tabs[] = ['label' => $label, 'href' => '/admin/reviews' . ($tabQuery === [] ? '' : '?' . http_build_query($tabQuery)), 'active' => $statusFilter === $key, 'count' => $counts[$key]];
}

render_admin('reviews.php', [
    'reviews' => $reviews,
    'total' => $total,
    'page' => $page,
    'per' => $per,
    'sort' => $sort,
    'query' => $query,
    'filters' => ['status' => $statusFilter, 'product_id' => $productFilter, 'rating' => $ratingFilter, 'sample' => $sampleOnly, 'q' => $q],
    'activeChips' => $activeChips,
    'tabs' => $tabs,
    'counts' => $counts,
    'sampleCount' => $sampleCount,
    'productOptions' => $productOptions,
    'returnTo' => '/admin/reviews' . ($query === [] ? '' : '?' . http_build_query($query)),
], ['title' => 'Reviews', 'body_class' => (string) $route['body_class'], 'wide' => true]);
