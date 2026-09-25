<?php
defined('SKYFR') || exit;
$reviews = $reviews ?? [];
$badge = static function (string $status, array $extra = []): string {
    ob_start();
    partial_admin('badge.php', ['status' => $status] + $extra);
    return (string) ob_get_clean();
};
$rows = [];
foreach ($reviews as $r) {
    $id = (int) $r['id'];
    $statusPost = '/admin/reviews/' . $id . '/status';
    $actions = [];
    if ($r['status'] !== 'approved') {
        $actions[] = ['label' => 'Approve', 'post' => $statusPost, 'fields' => ['to' => 'approved']];
    }
    if ($r['status'] !== 'rejected') {
        $actions[] = ['label' => 'Reject', 'post' => $statusPost, 'fields' => ['to' => 'rejected']];
    }
    if ($r['status'] !== 'pending') {
        $actions[] = ['label' => 'Return to pending', 'post' => $statusPost, 'fields' => ['to' => 'pending']];
    }
    $actions[] = ['label' => 'Delete', 'variant' => 'danger', 'post' => $statusPost, 'fields' => ['to' => 'delete'], 'confirm' => "Delete this review?\nThe words by " . $r['customer_name'] . " are removed for good and the product rating is recalculated.", 'confirm_word' => 'DELETE', 'confirm_label' => 'Yes, delete'];
    $product = '<a class="adm-media" href="' . e(url('/admin/products/' . (int) $r['product_id'])) . '"><img class="adm-thumb adm-thumb--sm" src="' . e((string) $r['thumb']) . '" alt="" width="40" height="50" loading="lazy"><span class="adm-media__body"><span class="adm-media__title">' . e((string) $r['product_name']) . '</span></span></a>';
    $customer = '<strong>' . e((string) $r['customer_name']) . '</strong>' . ($r['customer_city'] !== null && $r['customer_city'] !== '' ? '<br><span class="adm-muted">' . e((string) $r['customer_city']) . '</span>' : '');
    $rating = '<span class="adm-num" aria-label="' . e((string) (int) $r['rating']) . ' out of 5">' . e((string) $r['stars']) . ' ' . e((string) (int) $r['rating']) . '</span>';
    $text = ($r['title'] !== null && $r['title'] !== '' ? '<strong>' . e((string) $r['title']) . '</strong><br>' : '') . nl2br(e((string) $r['body']));
    $when = '<time class="adm-note" datetime="' . e(date_iso($r['created_at'])) . '" title="' . e(date_long($r['created_at'])) . '">' . e((string) $r['relative']) . '</time>';
    $sample = (int) $r['is_sample'] === 1 ? ' ' . $badge('sample', ['label' => 'Sample', 'tone' => 'slate', 'small' => true]) : '';
    $rows[] = [
        'id' => $id,
        'cells' => [
            'product' => $product,
            'customer' => $customer,
            'rating' => $rating,
            'review' => $text,
            'received' => $when,
            'status' => $badge((string) $r['status']) . $sample,
        ],
        'card' => [
            'title' => e((string) $r['customer_name']) . ($r['customer_city'] !== null && $r['customer_city'] !== '' ? ' <span class="adm-muted">· ' . e((string) $r['customer_city']) . '</span>' : ''),
            'chip' => $badge((string) $r['status'], ['small' => true]) . $sample,
            'sub' => $product,
            'lines' => [
                ['html' => $rating . ' <span class="adm-muted">· </span>' . $when],
                ['html' => $text],
            ],
            'inline_actions' => 2,
        ],
        'actions' => $actions,
    ];
}
$headerActions = [];
if ($sampleCount > 0) {
    $headerActions[] = ['label' => 'Show sample data', 'href' => '/admin/reviews?status=all&sample=1', 'variant' => 'ghost', 'size' => 'sm'];
    $headerActions[] = ['label' => 'Delete all sample reviews', 'variant' => 'danger', 'size' => 'sm', 'post' => '/admin/reviews/bulk', 'fields' => ['action' => 'delete_sample'], 'confirm' => "Delete all " . $sampleCount . " sample reviews?\nThese came with the demo catalogue and must go before launch. Real customer reviews are not touched.", 'confirm_word' => 'DELETE', 'confirm_label' => 'Yes, delete them'];
}
$statusNoun = $filters['status'] === 'all' ? 'reviews' : $filters['status'] . ' reviews';
$emptyByStatus = [
    'pending' => ['title' => 'No reviews waiting', 'text' => 'New reviews from the shop land here. Nothing shows on the site until you approve it.'],
    'approved' => ['title' => 'No approved reviews yet', 'text' => 'Approve a pending review and it appears on the product page.'],
    'rejected' => ['title' => 'No rejected reviews', 'text' => 'Rejected reviews stay here for the record.'],
    'all' => ['title' => 'No reviews yet', 'text' => 'Customers can leave a review on any product page.'],
];
?>
<?php partial_admin('page-header.php', [
    'title' => 'Reviews',
    'subtitle' => (int) $counts['pending'] . ' waiting · ' . (int) $counts['approved'] . ' live on the shop' . ($sampleCount > 0 ? ' · ' . $sampleCount . ' sample rows to delete before launch' : ''),
    'actions' => $headerActions,
]); ?>
<?php partial_admin('tabs.php', ['tabs' => $tabs, 'label' => 'Review status']); ?>
<?php partial_admin('filters.php', [
    'action' => '/admin/reviews',
    'search' => ['name' => 'q', 'value' => $filters['q'], 'placeholder' => 'Search name or words', 'maxlength' => 60, 'label' => 'Search reviews'],
    'hidden' => array_filter(['status' => $filters['status'] === 'pending' ? '' : $filters['status']]),
    'fields' => [
        ['type' => 'select', 'name' => 'product_id', 'label' => 'Product', 'value' => (string) ($filters['product_id'] ?? ''), 'options' => $productOptions],
        ['type' => 'select', 'name' => 'rating', 'label' => 'Rating', 'value' => (string) ($filters['rating'] ?? ''), 'options' => ['' => 'Any rating', '5' => '5 stars', '4' => '4 stars', '3' => '3 stars', '2' => '2 stars', '1' => '1 star']],
        ['type' => 'select', 'name' => 'sample', 'label' => 'Sample data', 'value' => $filters['sample'] ? '1' : '', 'options' => ['' => 'Real and sample', '1' => 'Sample data only']],
        ['type' => 'select', 'name' => 'sort', 'label' => 'Sort', 'value' => $sort, 'options' => ['date_asc' => 'Oldest first', 'date_desc' => 'Newest first', 'rating_desc' => 'Highest rating', 'rating_asc' => 'Lowest rating']],
    ],
    'active' => $activeChips,
    'clear_url' => '/admin/reviews' . ($filters['status'] === 'pending' ? '' : '?status=' . $filters['status']),
    'count_text' => $total === 0 ? 'No ' . $statusNoun . ' match' : null,
]); ?>
<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'reviews']); ?>
<?php partial_admin('table.php', [
    'caption' => 'Reviews',
    'list_id' => 'reviews-list',
    'columns' => [
        ['key' => 'product', 'label' => 'Product'],
        ['key' => 'customer', 'label' => 'Customer'],
        ['key' => 'rating', 'label' => 'Rating', 'sort' => 'rating'],
        ['key' => 'review', 'label' => 'Review'],
        ['key' => 'received', 'label' => 'Received', 'sort' => 'date'],
        ['key' => 'status', 'label' => 'Status'],
    ],
    'sort' => ['param' => 'sort', 'current' => $sort],
    'rows' => $rows,
    'bulk' => [
        'action' => '/admin/reviews/bulk',
        'buttons' => [
            ['label' => 'Approve', 'value' => 'approve'],
            ['label' => 'Reject', 'value' => 'reject'],
            ['label' => 'Return to pending', 'value' => 'pending'],
        ],
    ],
    'empty' => $activeChips === []
        ? $emptyByStatus[$filters['status']]
        : ['title' => 'No reviews match', 'text' => 'Try another product or rating, or clear the filters.', 'action' => ['label' => 'Clear filters', 'href' => '/admin/reviews', 'variant' => 'ghost']],
]); ?>
<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'reviews']); ?>
