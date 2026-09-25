<?php
defined('SKYFR') || exit;

$admin = auth_user();
$errors = [];

if (request_method() === 'POST') {
    $current = (string) request_post('current_password', '');
    $new = (string) request_post('new_password', '');
    $confirm = (string) request_post('confirm_password', '');
    if ($current === '') {
        $errors['current_password'] = 'Enter your current password.';
    }
    $problem = auth_password_problem($new, $current, (string) $admin['username']);
    if ($problem !== null) {
        $errors['new_password'] = $problem;
    }
    if (!hash_equals($new, $confirm)) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
    if ($errors === [] && !auth_change_password((int) $admin['id'], $current, $new)) {
        if (auth_lock_minutes((string) $admin['username'], request_ip_hash()) > 0) {
            auth_logout();
            redirect('/admin/login?reason=expired');
        }
        $errors['current_password'] = 'Current password is wrong.';
    }
    if ($errors === []) {
        flash('success', 'Password changed.');
        redirect('/admin/password');
    }
}

render_admin('password.php', [
    'errors' => $errors,
    'lastLoginAt' => $admin['last_login_at'] ?? null,
    'passwordChangedAt' => $admin['password_changed_at'] ?? null,
    'recentLogins' => auth_recent_logins((string) $admin['username']),
], ['title' => 'Change password', 'robots' => 'noindex, nofollow', 'body_class' => 'admin admin-password']);
