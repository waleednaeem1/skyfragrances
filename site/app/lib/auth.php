<?php
defined('SKYFR') || exit;

const AUTH_IDLE_SECONDS = 7200;
const AUTH_ABSOLUTE_SECONDS = 43200;
const AUTH_LOCK_WINDOW_SECONDS = 900;
const AUTH_DELAY_USER_FAILURES = 5;
const AUTH_DELAY_MICROSECONDS = 2000000;
const AUTH_LOCK_IP_FAILURES = 10;
const AUTH_DEVICE_COOKIE = 'SFDEV';
const AUTH_DEVICE_COOKIE_DAYS = 365;
const AUTH_DEVICE_MAX = 5;
const AUTH_PASSWORD_MIN_LENGTH = 12;
const AUTH_BCRYPT_COST = 12;
const AUTH_DUMMY_HASH = '$2y$12$ETDMthczz/Rq3F9Z9yk1GubbfKySXLaxSe0hHP9sjtN6plC9NX9eq';

function auth_user(): ?array
{
    static $resolved = false;
    static $user = null;
    if ($resolved) {
        return $user;
    }
    $resolved = true;
    $adminId = (int) ($_SESSION['admin_id'] ?? 0);
    if ($adminId < 1) {
        return null;
    }
    $reason = auth_session_expiry_reason();
    if ($reason !== null) {
        auth_drop_session($reason);
        return null;
    }
    $row = db_fetch(
        'SELECT id, username, email, display_name, is_active, last_login_at, password_changed_at
         FROM admin_users WHERE id = :id',
        ['id' => $adminId]
    );
    if ($row === null || (int) $row['is_active'] !== 1) {
        auth_drop_session('expired');
        return null;
    }
    $_SESSION['last_seen'] = time();
    $user = $row;
    return $user;
}

function auth_session_expiry_reason(): ?string
{
    $now = time();
    $loginAt = (int) ($_SESSION['login_at'] ?? 0);
    $lastSeen = (int) ($_SESSION['last_seen'] ?? 0);
    if ($loginAt < 1 || $now - $loginAt > AUTH_ABSOLUTE_SECONDS) {
        return 'expired';
    }
    if ($lastSeen < 1 || $now - $lastSeen > AUTH_IDLE_SECONDS) {
        return 'idle';
    }
    $storedUa = (string) ($_SESSION['ua_hash'] ?? '');
    if ($storedUa === '' || !hash_equals($storedUa, auth_ua_hash())) {
        return 'changed';
    }
    return null;
}

function auth_gate_reason(): ?string
{
    return $GLOBALS['auth_gate_reason'] ?? null;
}

function auth_drop_session(string $reason): void
{
    $GLOBALS['auth_gate_reason'] = $reason;
    session_destroy_fully();
}

function auth_ua_hash(): string
{
    return hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
}

function auth_require(): void
{
    if (auth_user() !== null) {
        return;
    }
    $query = [];
    $reason = auth_gate_reason();
    if ($reason !== null) {
        $query['reason'] = $reason;
    }
    $next = auth_safe_next(request_path());
    if ($next !== null && $next !== '/admin' && request_method() === 'GET') {
        $query['next'] = $next;
    }
    $suffix = $query === [] ? '' : '?' . http_build_query($query);
    redirect('/admin/login' . $suffix, 302);
}

function auth_safe_next(?string $candidate): ?string
{
    $candidate = trim((string) $candidate);
    if ($candidate === '' || $candidate[0] !== '/' || str_starts_with($candidate, '//')) {
        return null;
    }
    if (str_contains($candidate, '\\') || preg_match('/[\x00-\x1F\x7F]/', $candidate)) {
        return null;
    }
    if ($candidate !== '/admin' && !str_starts_with($candidate, '/admin/')) {
        return null;
    }
    if (str_starts_with($candidate, '/admin/login') || str_starts_with($candidate, '/admin/logout')) {
        return null;
    }
    return strlen($candidate) > 500 ? null : $candidate;
}

function auth_window_start(): string
{
    return date('Y-m-d H:i:s', time() - AUTH_LOCK_WINDOW_SECONDS);
}

function auth_recent_failures(string $column, string $value, ?string $ipHash = null): array
{
    $sql = match ($column) {
        'username' => 'SELECT COUNT(*) AS fails, MIN(attempted_at) AS oldest FROM admin_login_attempts WHERE username = :value AND was_success = 0 AND attempted_at > :since',
        'ip_hash' => 'SELECT COUNT(*) AS fails, MIN(attempted_at) AS oldest FROM admin_login_attempts WHERE ip_hash = :value AND was_success = 0 AND attempted_at > :since',
        default => throw new InvalidArgumentException('Unknown login-attempt column'),
    };
    $params = ['value' => $value, 'since' => auth_window_start()];
    if ($ipHash !== null) {
        $sql .= ' AND ip_hash = :ip_hash';
        $params['ip_hash'] = $ipHash;
    }
    $row = db_fetch($sql, $params);
    return ['fails' => (int) ($row['fails'] ?? 0), 'oldest' => $row['oldest'] ?? null];
}

function auth_lock_minutes(string $username, string $ipHash): int
{
    $ip = auth_recent_failures('ip_hash', $ipHash);
    if ($ip['fails'] < AUTH_LOCK_IP_FAILURES || $ip['oldest'] === null) {
        return 0;
    }
    $unlockAt = strtotime((string) $ip['oldest']) + AUTH_LOCK_WINDOW_SECONDS;
    return max(1, (int) ceil(($unlockAt - time()) / 60));
}

function auth_is_locked(string $ipHash): bool
{
    return auth_recent_failures('ip_hash', $ipHash)['fails'] >= AUTH_LOCK_IP_FAILURES;
}

function auth_device_hash(string $cookieValue): string
{
    return hash('sha256', config('security.app_key', '') . '|' . $cookieValue);
}

function auth_known_devices(?string $json): array
{
    $list = json_decode((string) $json, true);
    if (!is_array($list)) {
        return [];
    }
    return array_values(array_filter($list, static fn ($h): bool => is_string($h) && preg_match('/^[a-f0-9]{64}$/', $h) === 1));
}

function auth_device_is_known(?string $knownDevicesJson): bool
{
    $cookie = (string) ($_COOKIE[AUTH_DEVICE_COOKIE] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $cookie)) {
        return false;
    }
    $hash = auth_device_hash($cookie);
    foreach (auth_known_devices($knownDevicesJson) as $known) {
        if (hash_equals($known, $hash)) {
            return true;
        }
    }
    return false;
}

function auth_device_issue(int $adminId, ?string $knownDevicesJson): void
{
    $cookie = bin2hex(random_bytes(32));
    $devices = array_slice(array_merge([auth_device_hash($cookie)], auth_known_devices($knownDevicesJson)), 0, AUTH_DEVICE_MAX);
    try {
        db_update('admin_users', ['known_devices' => json_encode($devices)], ['id' => $adminId]);
    } catch (Throwable $e) {
        log_write('warning', 'known-device cookie not stored', ['error' => $e->getMessage()]);
        return;
    }
    if (headers_sent()) {
        return;
    }
    setcookie(AUTH_DEVICE_COOKIE, $cookie, [
        'expires' => time() + AUTH_DEVICE_COOKIE_DAYS * 86400,
        'path' => BASE_PATH . '/admin',
        'domain' => '',
        'secure' => APP_ENV === 'production',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

function auth_username_delay(string $username, ?string $knownDevicesJson): void
{
    if ($username === '') {
        return;
    }
    $fails = auth_recent_failures('username', $username)['fails'];
    if ($fails < 1) {
        return;
    }
    if ($fails >= AUTH_DELAY_USER_FAILURES && !auth_device_is_known($knownDevicesJson)) {
        usleep(AUTH_DELAY_MICROSECONDS);
        return;
    }
    usleep(random_int(100000, 400000));
}

function auth_record_attempt(string $username, string $ipHash, bool $success): void
{
    db_insert('admin_login_attempts', [
        'ip_hash' => $ipHash,
        'username' => mb_substr($username, 0, 64),
        'attempted_at' => now_karachi(),
        'was_success' => $success ? 1 : 0,
    ]);
}

function auth_clear_failures(string $username): void
{
    db_query(
        'DELETE FROM admin_login_attempts WHERE username = :username AND was_success = 0',
        ['username' => $username]
    );
}

function auth_purge_attempts(): void
{
    db_query(
        'DELETE FROM admin_login_attempts WHERE attempted_at < :before',
        ['before' => date('Y-m-d H:i:s', time() - 7 * 86400)]
    );
}

function auth_username_hash(string $username): string
{
    return hash('sha256', config('security.app_key', '') . '|' . mb_strtolower(trim($username)));
}

function auth_username_label(string $username): string
{
    return mb_substr($username, 0, 3) . '~' . substr(auth_username_hash($username), 0, 12);
}

function auth_log_activity(?int $adminId, string $username, string $action, string $summary): void
{
    db_insert('admin_activity_log', [
        'admin_id' => $adminId,
        'admin_username' => mb_substr($username, 0, 64),
        'entity_type' => 'admin_user',
        'entity_id' => $adminId,
        'action' => $action,
        'summary' => mb_substr($summary, 0, 255),
        'before_json' => null,
        'after_json' => null,
        'ip_hash' => request_ip_hash(),
        'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        'created_at' => now_karachi(),
    ]);
}

function auth_fetch_login_row(string $username): ?array
{
    try {
        return db_fetch(
            'SELECT id, username, password_hash, is_active, known_devices FROM admin_users WHERE username = :username',
            ['username' => $username]
        );
    } catch (PDOException $e) {
        log_write('warning', 'admin_users.known_devices missing; login continues without known-device exemption');
        return db_fetch(
            'SELECT id, username, password_hash, is_active FROM admin_users WHERE username = :username',
            ['username' => $username]
        );
    }
}

function auth_attempt(string $username, string $password): bool
{
    $username = mb_strtolower(trim($username));
    $ipHash = request_ip_hash();
    auth_purge_attempts();
    if ($username === '' || $password === '' || auth_lock_minutes($username, $ipHash) > 0) {
        return false;
    }
    $row = auth_fetch_login_row($username);
    auth_username_delay($username, $row['known_devices'] ?? null);
    $hash = ($row !== null && (int) $row['is_active'] === 1) ? $row['password_hash'] : AUTH_DUMMY_HASH;
    $verified = password_verify($password, $hash);
    if ($row === null || (int) $row['is_active'] !== 1 || !$verified) {
        auth_record_attempt($username, $ipHash, false);
        auth_log_activity($row['id'] ?? null, $username, 'login.fail', 'Failed login for ' . auth_username_label($username));
        log_write('warning', 'admin login failed', ['username_hash' => auth_username_hash($username)]);
        return false;
    }
    $adminId = (int) $row['id'];
    if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => AUTH_BCRYPT_COST])) {
        db_update('admin_users', [
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => AUTH_BCRYPT_COST]),
        ], ['id' => $adminId]);
    }
    session_regenerate();
    csrf_rotate();
    $now = time();
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_username'] = $row['username'];
    $_SESSION['login_at'] = $now;
    $_SESSION['last_seen'] = $now;
    $_SESSION['ua_hash'] = auth_ua_hash();
    auth_record_attempt($username, $ipHash, true);
    auth_clear_failures($username);
    auth_device_issue($adminId, $row['known_devices'] ?? null);
    db_update('admin_users', [
        'last_login_at' => now_karachi(),
        'last_login_ip_hash' => $ipHash,
        'updated_at' => now_karachi(),
    ], ['id' => $adminId]);
    auth_log_activity($adminId, $row['username'], 'login.ok', 'Signed in');
    return true;
}

function auth_logout(): void
{
    $adminId = (int) ($_SESSION['admin_id'] ?? 0);
    $username = (string) ($_SESSION['admin_username'] ?? '');
    if ($adminId > 0) {
        auth_log_activity($adminId, $username, 'logout', 'Signed out');
    }
    session_destroy_fully();
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 86400,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'],
    ]);
    header('Clear-Site-Data: "cache"');
}

function auth_password_problem(string $new, string $current, string $username): ?string
{
    if (mb_strlen($new) < AUTH_PASSWORD_MIN_LENGTH) {
        return 'Use at least ' . AUTH_PASSWORD_MIN_LENGTH . ' characters.';
    }
    if (hash_equals($current, $new)) {
        return 'The new password must be different from the current one.';
    }
    if (mb_strtolower($new) === mb_strtolower(trim($username))) {
        return 'The password must not be your username.';
    }
    return null;
}

function auth_change_password(int $adminId, string $current, string $new): bool
{
    $row = db_fetch(
        'SELECT id, username, password_hash FROM admin_users WHERE id = :id AND is_active = 1',
        ['id' => $adminId]
    );
    if ($row === null) {
        return false;
    }
    if (!password_verify($current, $row['password_hash'])) {
        auth_record_attempt((string) $row['username'], request_ip_hash(), false);
        auth_log_activity($adminId, (string) $row['username'], 'password.fail', 'Wrong current password on the change-password form');
        return false;
    }
    if (auth_password_problem($new, $current, $row['username']) !== null) {
        return false;
    }
    db_update('admin_users', [
        'password_hash' => password_hash($new, PASSWORD_BCRYPT, ['cost' => AUTH_BCRYPT_COST]),
        'password_changed_at' => now_karachi(),
        'updated_at' => now_karachi(),
    ], ['id' => $adminId]);
    auth_clear_failures($row['username']);
    session_regenerate();
    csrf_rotate();
    $_SESSION['last_seen'] = time();
    auth_log_activity($adminId, $row['username'], 'password.change', 'Password changed');
    return true;
}

function auth_recent_logins(string $username, int $limit = 10): array
{
    return db_fetch_all(
        'SELECT attempted_at, was_success FROM admin_login_attempts
         WHERE username = :username ORDER BY attempted_at DESC LIMIT :limit',
        ['username' => mb_strtolower($username), 'limit' => max(1, min(50, $limit))]
    );
}
