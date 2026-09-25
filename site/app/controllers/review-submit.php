<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/forms.php';

const REVIEW_THANKS = 'Thank you. Your review will appear once it has been approved.';

$slug = $params['slug'];
$product = db_fetch('SELECT id, slug FROM products WHERE slug = :slug AND is_active = 1 AND deleted_at IS NULL', ['slug' => $slug]);
if ($product === null) {
    abort(404);
}
$back = '/product/' . $product['slug'] . '#reviews';

$trap = form_trap_status();
if ($trap === 'bot') {
    form_trap_log('review', $trap);
    flash('success', REVIEW_THANKS);
    redirect($back);
}
if ($trap === 'expired') {
    flash('error', 'This form expired. Please try again.');
    redirect($back);
}
if (rate_limit_over('review', request_ip_hash(), 3, 86400)) {
    flash('error', 'Too many reviews from this connection. Please try again tomorrow.');
    redirect($back);
}

$name = trim(request_string('customer_name', ''));
$city = trim(request_string('customer_city', ''));
$rating = filter_var(request_post('rating', 0), FILTER_VALIDATE_INT);
$rating = $rating === false ? 0 : (int) $rating;
$title = trim(request_string('title', ''));
$body = trim(request_string('body', ''));

$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 100 || !preg_match('/\p{L}/u', $name)) {
    $errors[] = 'Please enter your name.';
}
if ($rating < 1 || $rating > 5) {
    $errors[] = 'Please choose a rating from 1 to 5.';
}
if (mb_strlen($body) < 10 || mb_strlen($body) > 2000) {
    $errors[] = 'Please write at least a sentence about the perfume.';
}
if ($errors !== []) {
    flash('error', implode(' ', $errors));
    redirect($back);
}

rate_limit_record('review', request_ip_hash(), true);
db_insert('reviews', [
    'product_id' => $product['id'],
    'customer_name' => $name,
    'customer_city' => $city !== '' ? mb_substr($city, 0, 80) : null,
    'rating' => $rating,
    'title' => $title !== '' ? mb_substr($title, 0, 160) : null,
    'body' => $body,
    'status' => 'pending',
    'is_sample' => 0,
    'ip_hash' => request_ip_hash(),
    'created_at' => now_karachi(),
]);

flash('success', REVIEW_THANKS);
redirect($back);
