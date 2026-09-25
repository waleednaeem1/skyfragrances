<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/forms.php';

const NEWSLETTER_THANKS = 'Thank you for subscribing.';

if (request_origin_is_foreign()) {
    json(['ok' => false, 'error' => 'origin', 'message' => 'Request blocked.'], 403);
}

function newsletter_respond(bool $ok, string $message, string $error = '', int $status = 200): never
{
    if (!request_is_ajax() && request_json() === []) {
        flash($ok ? 'success' : 'error', $message);
        redirect(request_return_path('/') . ($ok ? '#newsletter' : ''), 303);
    }
    json(['ok' => $ok, 'error' => $error, 'message' => $message], $ok ? 200 : $status);
}

$email = mb_strtolower(trim(request_string('email', '')));
$source = request_string('source', 'footer');
$source = in_array($source, ['footer', 'home', 'checkout'], true) ? $source : 'footer';

if (trim(request_string(FORM_HONEYPOT_FIELD, '')) !== '') {
    form_trap_log('newsletter', 'bot');
    newsletter_respond(true, NEWSLETTER_THANKS);
}
if (rate_limit_over('newsletter', request_ip_hash(), 5, 3600)) {
    newsletter_respond(false, 'Too many sign-ups from this connection. Please try again later.', 'rate_limit', 429);
}
if ($email === '' || mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    newsletter_respond(false, 'Please enter a valid email address.', 'email', 422);
}
rate_limit_record('newsletter', request_ip_hash(), true);

$existing = db_fetch('SELECT id, status FROM newsletter_subscribers WHERE email = :email', ['email' => $email]);
if ($existing === null) {
    db_insert('newsletter_subscribers', [
        'email' => $email,
        'status' => 'subscribed',
        'source' => $source,
        'unsub_token' => bin2hex(random_bytes(20)),
        'ip_hash' => request_ip_hash(),
        'subscribed_at' => now_karachi(),
    ]);
} elseif ($existing['status'] !== 'subscribed') {
    db_update('newsletter_subscribers', ['status' => 'subscribed', 'source' => $source, 'subscribed_at' => now_karachi(), 'unsubscribed_at' => null], ['id' => (int) $existing['id']]);
}

newsletter_respond(true, NEWSLETTER_THANKS);
