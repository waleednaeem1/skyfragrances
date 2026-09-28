<?php
declare(strict_types=1);

if (PHP_SAPI === 'cli') {
    exit("Copy this file to public_html/reset-password.php with File Manager and open it in your browser.\n");
}

date_default_timezone_set('Asia/Karachi');
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');

const RP_MIN_PASSWORD = 12;
const RP_PENDING_TTL = 3600;
const RP_COOKIE = 'skyfr_reset';

function rp_e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rp_page(string $title, string $body, int $status = 200): never
{
    http_response_code($status);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . rp_e($title) . ' · Sky Fragrances</title>'
        . '<style>body{margin:0;background:#0b0b0d;color:#f2ede4;font:16px/1.55 Georgia,serif;padding:32px 16px}main{max-width:620px;margin:0 auto}h1{font-weight:400;letter-spacing:.04em;font-size:22px;margin:0 0 20px}h1 small{display:block;font-size:12px;letter-spacing:.3em;text-transform:uppercase;color:#c9a962;margin-bottom:8px}.box{border:1px solid #2a2a30;border-radius:6px;padding:20px 22px;margin:0 0 18px;background:#121216}.box.bad{border-color:#8a3b3b}.box.good{border-color:#3b7a4d}code{background:#1c1c22;padding:2px 6px;border-radius:3px;font-size:14px;word-break:break-all}label{display:block;margin:14px 0 6px;font-size:14px;color:#c8c2b6}input{width:100%;box-sizing:border-box;background:#0b0b0d;border:1px solid #3a3a42;color:#f2ede4;padding:10px 12px;border-radius:4px;font-size:16px}button{margin-top:18px;background:#c9a962;color:#111;border:0;border-radius:4px;padding:12px 20px;font-size:15px;letter-spacing:.06em;cursor:pointer}ol{padding-left:20px}li{margin:6px 0}p{margin:10px 0}</style></head><body><main><h1><small>Sky Fragrances</small>' . rp_e($title) . '</h1>' . $body . '</main></body></html>';
    exit;
}

function rp_now(): string
{
    return date('Y-m-d H:i:s');
}

function rp_pdo(array $db): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', (string) $db['host'], (int) $db['port'], (string) $db['name']);
    $pdo = new PDO($dsn, (string) $db['user'], (string) $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 8,
    ]);
    $pdo->exec("SET time_zone = '+05:00'");
    return $pdo;
}

function rp_pending_read(string $file): ?array
{
    if (!is_file($file)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data) || !isset($data['nonce'], $data['csrf'], $data['owner'], $data['created'])
        || !preg_match('/^[0-9a-f]{16}$/', (string) $data['nonce']) || time() - (int) $data['created'] > RP_PENDING_TTL) {
        return null;
    }
    return $data;
}

function rp_pending_start(string $file): array
{
    $secret = bin2hex(random_bytes(24));
    $data = ['nonce' => bin2hex(random_bytes(8)), 'csrf' => bin2hex(random_bytes(16)), 'owner' => hash('sha256', $secret), 'created' => time()];
    if (file_put_contents($file, json_encode($data), LOCK_EX) === false) {
        rp_page('Cannot write to storage', '<div class="box bad"><p>The folder <code>storage</code> is not writable, so this tool cannot start. In File Manager right-click <code>storage</code> → Permissions → 755, then reload.</p></div>', 500);
    }
    @chmod($file, 0600);
    setcookie(RP_COOKIE, $secret, ['expires' => time() + RP_PENDING_TTL, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'httponly' => true, 'samesite' => 'Strict']);
    $_COOKIE[RP_COOKIE] = $secret;
    return $data;
}

function rp_is_owner(array $pending): bool
{
    $cookie = (string) ($_COOKIE[RP_COOKIE] ?? '');
    return $cookie !== '' && hash_equals((string) $pending['owner'], hash('sha256', $cookie));
}

function rp_cleanup(string $pendingFile, string $nonceFile): bool
{
    @unlink($nonceFile);
    @unlink($pendingFile);
    @chmod(__FILE__, 0644);
    @unlink(__FILE__);
    return !is_file(__FILE__);
}

$root = __DIR__;
if (!is_file($root . '/config.php') || !is_dir($root . '/storage')) {
    rp_page('Copy this file first', '<div class="box bad"><p>This tool only works from the <code>public_html</code> folder, next to <code>config.php</code>.</p><ol><li>In hPanel File Manager open <code>public_html/app/tools</code>.</li><li>Right-click <code>reset-password.php</code> → <strong>Copy</strong> → destination <code>/public_html</code>.</li><li>Open <code>https://skyfragrances.com/reset-password.php</code>.</li></ol></div>', 403);
}

define('SKYFR', 1);
$config = require $root . '/config.php';
if (!is_array($config) || !isset($config['db']) || !is_array($config['db'])) {
    rp_page('config.php is damaged', '<div class="box bad"><p><code>config.php</code> could not be read. Compare it with <code>config.sample.php</code> or ask the developer.</p></div>', 500);
}

$pendingFile = $root . '/storage/.reset-pending';
$pending = rp_pending_read($pendingFile);
if ($pending === null) {
    if (is_file($pendingFile)) {
        $stale = json_decode((string) file_get_contents($pendingFile), true);
        if (is_array($stale) && preg_match('/^[0-9a-f]{16}$/', (string) ($stale['nonce'] ?? ''))) {
            @unlink($root . '/storage/reset-' . $stale['nonce']);
        }
        @unlink($pendingFile);
    }
    $pending = rp_pending_start($pendingFile);
}
$nonceFile = $root . '/storage/reset-' . $pending['nonce'];
$restartHelp = '<p>To start over: in File Manager delete <code>storage/.reset-pending</code> and any <code>storage/reset-…</code> file, then reload this page.</p>';

if (!rp_is_owner($pending)) {
    rp_page('A reset is already in progress', '<div class="box bad"><p>Another browser opened this page in the last hour, and only that browser can finish the reset.</p>' . $restartHelp . '</div>', 403);
}

if (!is_file($nonceFile)) {
    rp_page('Prove you own this hosting account', '<div class="box"><p>Before a password can be changed, show that you can reach the server\'s files:</p><ol><li>In hPanel File Manager open <code>public_html/storage</code> (tick <em>Show hidden files</em>).</li><li>Press <strong>New file</strong> and name it exactly:<br><code>reset-' . rp_e((string) $pending['nonce']) . '</code></li><li>Leave it empty and save. Come back here and <strong>reload this page</strong>.</li></ol><p>The name changes if you take more than an hour.</p>' . $restartHelp . '</div>');
}

try {
    $pdo = rp_pdo($config['db']);
    $admins = $pdo->query('SELECT id, username FROM admin_users ORDER BY id')->fetchAll();
} catch (Throwable $e) {
    rp_page('Database not reachable', '<div class="box bad"><p>The database in <code>config.php</code> did not answer. Check Databases → Management in hPanel, or ask the developer.</p></div>', 500);
}
if (count($admins) !== 1) {
    rp_cleanup($pendingFile, $nonceFile);
    rp_page('Ask the developer', '<div class="box bad"><p>This tool only works when the shop has exactly one admin account; it found ' . count($admins) . '. It has removed itself. Ask the developer to reset the password.</p></div>', 409);
}
$admin = $admins[0];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['_csrf'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $repeat = (string) ($_POST['password_repeat'] ?? '');
    if (!hash_equals((string) $pending['csrf'], $csrf)) {
        $error = 'This form has expired. Reload the page and try again.';
    } elseif (strlen($password) < RP_MIN_PASSWORD) {
        $error = 'Use at least ' . RP_MIN_PASSWORD . ' characters.';
    } elseif ($password !== $repeat) {
        $error = 'The two passwords do not match.';
    } else {
        $now = rp_now();
        $pdo->beginTransaction();
        $update = $pdo->prepare('UPDATE admin_users SET password_hash = :hash, is_active = 1, password_changed_at = :t1, updated_at = :t2 WHERE id = :id');
        $update->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':t1' => $now, ':t2' => $now, ':id' => (int) $admin['id']]);
        $clear = $pdo->prepare('DELETE FROM admin_login_attempts WHERE username = :u');
        $clear->execute([':u' => (string) $admin['username']]);
        $pdo->commit();
        $gone = rp_cleanup($pendingFile, $nonceFile);
        $selfNote = $gone
            ? '<div class="box good"><p><strong>This file has deleted itself.</strong> Reload this address once: it must show the shop\'s page-not-found.</p></div>'
            : '<div class="box bad"><p><strong>Delete <code>public_html/reset-password.php</code> now</strong> in File Manager — it could not remove itself.</p></div>';
        rp_page('Password changed', '<div class="box good"><p>The password for <strong>' . rp_e((string) $admin['username']) . '</strong> has been changed and any login lock-out has been cleared.</p><p><a href="/admin/login" style="color:#c9a962">Go to the admin login</a></p></div>' . $selfNote);
    }
}

rp_page('Choose a new admin password', ($error !== '' ? '<div class="box bad"><p>' . rp_e($error) . '</p></div>' : '')
    . '<div class="box"><p>Account: <strong>' . rp_e((string) $admin['username']) . '</strong></p><form method="post" autocomplete="off"><input type="hidden" name="_csrf" value="' . rp_e((string) $pending['csrf']) . '">'
    . '<label for="p1">New password (at least ' . RP_MIN_PASSWORD . ' characters)</label><input id="p1" name="password" type="password" minlength="' . RP_MIN_PASSWORD . '" required autocomplete="new-password">'
    . '<label for="p2">Repeat it</label><input id="p2" name="password_repeat" type="password" minlength="' . RP_MIN_PASSWORD . '" required autocomplete="new-password">'
    . '<button type="submit">Change the password and remove this file</button></form></div>');
