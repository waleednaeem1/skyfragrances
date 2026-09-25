<?php
defined('SKYFR') || exit;

$email = mb_strtolower(trim(request_query_string('e', '')));
$token = request_query_string('t', '');
$done = false;

if ($email !== '' && $token !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
    $subscriber = db_fetch('SELECT id, status, unsub_token FROM newsletter_subscribers WHERE email = :email', ['email' => $email]);
    if ($subscriber !== null && hash_equals((string) $subscriber['unsub_token'], $token)) {
        if ($subscriber['status'] !== 'unsubscribed') {
            db_update('newsletter_subscribers', ['status' => 'unsubscribed', 'unsubscribed_at' => now_karachi()], ['id' => $subscriber['id']]);
        }
        $done = true;
    }
}

header_no_store();
$head['title'] = 'Unsubscribed | Sky Fragrances';
$head['canonical'] = canonical('/unsubscribe');

render($route['view'], [
    'heading' => $done ? 'You have been unsubscribed' : 'This unsubscribe link is not valid',
    'message' => $done
        ? 'You will not receive further emails from Sky Fragrances. You are welcome back any time.'
        : 'The link may have expired or been copied incompletely. Please use the link from the latest email you received.',
], $head);
