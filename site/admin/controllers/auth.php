<?php
defined('SKYFR') || exit;

$isLogout = ($route['name'] ?? '') === 'admin.logout';

if ($isLogout) {
    if (auth_user() === null) {
        redirect('/admin/login', 302);
    }
    if (request_method() === 'POST') {
        auth_logout();
        redirect('/admin/login?reason=out');
    }
    render_admin('login.php', [
        'mode' => 'logout',
        'username' => '',
        'error' => null,
        'lockMinutes' => 0,
        'next' => null,
        'notice' => null,
    ], ['title' => 'Log out', 'robots' => 'noindex, nofollow', 'body_class' => 'admin admin-login']);
}

if (auth_user() !== null) {
    redirect('/admin');
}

$reasonMessages = [
    'idle' => 'You were signed out after two hours without activity.',
    'expired' => 'Your session reached its 12-hour limit. Please sign in again.',
    'changed' => 'Your session was closed because the browser changed.',
    'out' => 'You have been signed out.',
];
$reason = (string) request_query('reason', '');
$notice = $reasonMessages[$reason] ?? null;
$next = auth_safe_next((string) request_query('next', ''));
$username = '';
$error = null;
$lockMinutes = 0;

if (request_method() === 'POST') {
    $username = mb_substr(trim((string) request_post('username', '')), 0, 64);
    $password = (string) request_post('password', '');
    $next = auth_safe_next((string) request_post('next', ''));
    $lockMinutes = auth_lock_minutes($username, request_ip_hash());
    if ($lockMinutes === 0 && auth_attempt($username, $password)) {
        redirect($next ?? '/admin');
    }
    $lockMinutes = auth_lock_minutes($username, request_ip_hash());
    if ($lockMinutes > 0) {
        http_status(429);
        $error = 'Too many failed attempts. Try again in ' . $lockMinutes . ' minute' . ($lockMinutes === 1 ? '' : 's') . '.';
    } else {
        $error = 'Wrong username or password.';
    }
    $notice = null;
}

render_admin('login.php', [
    'mode' => 'login',
    'username' => $username,
    'error' => $error,
    'lockMinutes' => $lockMinutes,
    'next' => $next,
    'notice' => $notice,
], ['title' => 'Sign in', 'robots' => 'noindex, nofollow', 'body_class' => 'admin admin-login']);
