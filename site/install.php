<?php
define('SKYFR', 1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Karachi');
mb_internal_encoding('UTF-8');
set_time_limit(120);

const INSTALL_ROOT = __DIR__;
const INSTALL_SCHEMA = __DIR__ . '/db/schema.sql';
const INSTALL_SEED = __DIR__ . '/db/seed.sql';
const INSTALL_LOCK = __DIR__ . '/storage/.installed';
const INSTALL_KEY = __DIR__ . '/storage/.install-key';
const INSTALL_PROBE_TOKEN = __DIR__ . '/storage/.install-probe';
const INSTALL_CONFIG = __DIR__ . '/config.php';
const INSTALL_ROBOTS = __DIR__ . '/robots.txt';
const INSTALL_MIN_PASSWORD = 12;
const INSTALL_MIN_MEMORY_BYTES = 134217728;
const INSTALL_PIXEL_OVERHEAD_BYTES = 50331648;
const INSTALL_PROBE_TIMEOUT = 5;
const INSTALL_MAX_UPLOAD_BYTES = 6291456;
const INSTALL_PROBE_SENTINEL = 'SKYFR-REWRITE-OK';
const INSTALL_CANONICAL_HOST = 'skyfragrances.com';
const INSTALL_RUNTIME_DIRS = [
    'storage', 'storage/logs', 'storage/cache', 'storage/sessions', 'storage/sessions/shop', 'storage/sessions/admin', 'storage/proofs',
    'uploads', 'uploads/products', 'uploads/collections', 'uploads/og', 'uploads/settings',
];
const INSTALL_STUB_PHP = "<?php http_response_code(404);\n";
const INSTALL_DENY_HTACCESS = <<<'HTACCESS'
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
  Order allow,deny
  Deny from all
</IfModule>
<FilesMatch "\.(php|phtml|inc|sql|ini|log|json|md|txt|bak|old|save|swp|dist|example)$">
  <IfModule mod_authz_core.c>
    Require all denied
  </IfModule>
  <IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
  </IfModule>
</FilesMatch>
HTACCESS;
const INSTALL_STORAGE_HTACCESS = <<<'HTACCESS'
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
  Order allow,deny
  Deny from all
</IfModule>
<FilesMatch ".">
  <IfModule mod_authz_core.c>
    Require all denied
  </IfModule>
  <IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
  </IfModule>
</FilesMatch>
HTACCESS;
const INSTALL_ASSETS_HTACCESS = <<<'HTACCESS'
ErrorDocument 404 "Not found"

<IfModule mod_headers.c>
<FilesMatch "\.(css|js|jpe?g|png|webp|svg|ico|woff2)$">
  Header set Cache-Control "public, max-age=31536000, immutable"
  Header unset Pragma
</FilesMatch>
</IfModule>
HTACCESS;
const INSTALL_ADMIN_HTACCESS = <<<'HTACCESS'
<IfModule mod_headers.c>
Header always set X-Robots-Tag "noindex, nofollow"
Header always set Cache-Control "no-store, no-cache, must-revalidate, private"
</IfModule>
HTACCESS;
const INSTALL_UPLOADS_HTACCESS = <<<'HTACCESS'
ErrorDocument 404 "Not found"

RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .phps .pht .phar .cgi .pl .py .shtml
RemoveType    .php .phtml .php3 .php4 .php5 .php7 .php8 .phps .pht .phar .cgi .pl .py .shtml

<IfModule mod_mime.c>
  AddType text/plain .php .phtml .php3 .php4 .php5 .php7 .php8 .phps .pht .phar .inc .cgi .pl .py .shtml .html .htm
</IfModule>

<FilesMatch "(?i)\.(php|phtml|php[0-9]|phps|pht|phar|inc|cgi|pl|py|sh|shtml|htaccess|htpasswd)$">
  <IfModule mod_authz_core.c>
    Require all denied
  </IfModule>
  <IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
  </IfModule>
</FilesMatch>

<FilesMatch "(?i)\.(jpe?g|png|webp|gif|ico|avif)$">
  <IfModule mod_authz_core.c>
    Require all granted
  </IfModule>
  <IfModule mod_headers.c>
    Header set Cache-Control "public, max-age=31536000, immutable"
    Header set X-Content-Type-Options "nosniff"
    Header set Content-Disposition "inline"
  </IfModule>
</FilesMatch>
HTACCESS;

function inst_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

function inst_now(): string
{
    return date('Y-m-d H:i:s');
}

function inst_random_hex(int $bytes): string
{
    return bin2hex(random_bytes($bytes));
}

function inst_is_https(): bool
{
    if (($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
        return true;
    }
    return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function inst_base_path(): string
{
    $dir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/install.php')));
    $dir = rtrim($dir, '/');
    return $dir === '' || $dir === '.' ? '' : $dir;
}

function inst_request_host(): string
{
    $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($host === '' || !preg_match('/^[a-z0-9.-]+(:\d+)?$/', $host)) {
        $host = strtolower(trim((string) ($_SERVER['SERVER_NAME'] ?? 'localhost')));
    }
    return preg_match('/^[a-z0-9.-]+(:\d+)?$/', $host) ? $host : 'localhost';
}

function inst_request_base_url(): string
{
    return (inst_is_https() ? 'https' : 'http') . '://' . inst_request_host() . inst_base_path();
}

function inst_host_points_here(): bool
{
    $host = preg_replace('/:\d+$/', '', inst_request_host()) ?? '';
    $serverAddr = (string) ($_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? '');
    if ($host === '' || $serverAddr === '') {
        return true;
    }
    if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
        return $host === $serverAddr || in_array($host, ['127.0.0.1', '::1'], true) || inst_self_probe();
    }
    $resolved = gethostbyname($host);
    return $resolved === $serverAddr || in_array($resolved, ['127.0.0.1', '::1'], true) || inst_self_probe();
}

function inst_self_probe(): bool
{
    static $result = null;
    if ($result !== null) {
        return $result;
    }
    $result = false;
    if (PHP_SAPI === 'cli-server' || !function_exists('curl_init') || !is_file(__FILE__)) {
        return false;
    }
    $token = inst_random_hex(16);
    if (!inst_write_atomic(INSTALL_PROBE_TOKEN, $token . "\n", 0600)) {
        return false;
    }
    $nonce = inst_random_hex(8);
    $probe = inst_http_probe(inst_request_base_url() . '/install.php?probe=' . $nonce, true);
    @unlink(INSTALL_PROBE_TOKEN);
    $result = $probe['status'] === 200 && hash_equals(hash('sha256', $nonce . $token), trim($probe['body']));
    return $result;
}

function inst_answer_probe(string $nonce): never
{
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    $token = is_file(INSTALL_PROBE_TOKEN) ? trim((string) file_get_contents(INSTALL_PROBE_TOKEN)) : '';
    if ($token === '' || !preg_match('/^[a-f0-9]{16}$/', $nonce)) {
        http_response_code(404);
        exit('Not found');
    }
    exit(hash('sha256', $nonce . $token));
}

function inst_ini_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '' || $value === '-1') {
        return PHP_INT_MAX;
    }
    $number = (int) $value;
    return match (strtolower(substr($value, -1))) {
        'g' => $number * 1073741824,
        'm' => $number * 1048576,
        'k' => $number * 1024,
        default => $number,
    };
}

function inst_pixel_budget(): int
{
    $limit = inst_ini_bytes((string) ini_get('memory_limit'));
    if ($limit === PHP_INT_MAX) {
        return 40000000;
    }
    return max(1000000, min(40000000, intdiv($limit - INSTALL_PIXEL_OVERHEAD_BYTES - memory_get_usage(), 5)));
}

function inst_install_key(): string
{
    if (is_file(INSTALL_KEY)) {
        return trim((string) file_get_contents(INSTALL_KEY));
    }
    $key = inst_random_hex(16);
    if (!is_dir(dirname(INSTALL_KEY))) {
        @mkdir(dirname(INSTALL_KEY), 0755, true);
    }
    return inst_write_atomic(INSTALL_KEY, $key . "\n", 0600) ? $key : '';
}

function inst_install_key_ok(): bool
{
    if (!empty($_SESSION['inst_key_ok'])) {
        return true;
    }
    $expected = inst_install_key();
    $sent = trim((string) ($_POST['install_key'] ?? ''));
    if ($expected === '' || $sent === '' || !hash_equals($expected, $sent)) {
        return false;
    }
    $_SESSION['inst_key_ok'] = 1;
    return true;
}

function inst_install_key_field(): string
{
    if (!empty($_SESSION['inst_key_ok'])) {
        return '';
    }
    inst_install_key();
    return '<div class="box"><h2>Prove you own this hosting account</h2><p>The installer wrote a one-time key to <strong>storage/.install-key</strong>. Open hPanel → File Manager → public_html → storage (tick "Show hidden files"), open <strong>.install-key</strong> and copy the 32 characters into this box. You only need to do this once.</p>'
        . inst_field('text', 'install_key', 'Install key', '', 'Anyone who can open this page could otherwise point your shop at their own database.') . '</div>';
}

function inst_install_key_remove(): void
{
    if (is_file(INSTALL_KEY)) {
        @unlink(INSTALL_KEY);
    }
}

function inst_session_start(): void
{
    session_name('SFINSTALL');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => inst_base_path() . '/',
        'secure' => inst_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function inst_csrf_token(): string
{
    if (empty($_SESSION['inst_csrf'])) {
        $_SESSION['inst_csrf'] = inst_random_hex(32);
    }
    return $_SESSION['inst_csrf'];
}

function inst_csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . inst_e(inst_csrf_token()) . '">';
}

function inst_csrf_check(): void
{
    $sent = (string) ($_POST['_token'] ?? '');
    if ($sent === '' || !hash_equals(inst_csrf_token(), $sent)) {
        inst_page('Form expired', '<div class="box bad"><h2>This form has expired</h2><p>Please go back and submit it again. Nothing was changed.</p><p><a class="btn" href="install.php">Start again</a></p></div>', 0);
    }
}

function inst_post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function inst_phone_normalize(string $raw): string
{
    $digits = preg_replace('/\D+/', '', $raw) ?? '';
    if ($digits === '') {
        return '';
    }
    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    } elseif (str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }
    if (str_starts_with($digits, '92') && strlen($digits) > 10) {
        $digits = substr($digits, 2);
    }
    return '+92' . $digits;
}

function inst_http_probe(string $url, bool $withBody = true): array
{
    $none = ['status' => null, 'body' => '', 'redirect' => ''];
    if (PHP_SAPI === 'cli-server' || !function_exists('curl_init')) {
        return $none;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => !$withBody,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => INSTALL_PROBE_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'SkyFragrancesInstaller/1.0',
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $redirect = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    if ($status <= 0) {
        return $none;
    }
    return ['status' => $status, 'body' => is_string($body) ? $body : '', 'redirect' => $redirect];
}

function inst_probe_redirects_to_https(array $probe): bool
{
    return in_array($probe['status'], [301, 302, 307, 308], true) && str_starts_with(strtolower($probe['redirect']), 'https://');
}

function inst_http_get(string $url): ?string
{
    $probe = inst_http_probe($url, true);
    if ($probe['status'] === null) {
        return null;
    }
    if (inst_probe_redirects_to_https($probe)) {
        return INSTALL_PROBE_SENTINEL;
    }
    return $probe['body'];
}

function inst_http_status(string $url): ?int
{
    $probe = inst_http_probe($url, false);
    if ($probe['status'] === null) {
        return null;
    }
    return inst_probe_redirects_to_https($probe) ? 403 : $probe['status'];
}

function inst_split_sql(string $sql): array
{
    $statements = [];
    $current = '';
    foreach (preg_split('/\r\n|\r|\n/', $sql) as $line) {
        $trimmed = trim($line);
        if ($current === '' && ($trimmed === '' || str_starts_with($trimmed, '--'))) {
            continue;
        }
        $current .= $line . "\n";
        if (str_ends_with(rtrim($trimmed), ';')) {
            $statements[] = trim($current);
            $current = '';
        }
    }
    if (trim($current) !== '') {
        $statements[] = trim($current);
    }
    return $statements;
}

function inst_pdo(array $db): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], (int) $db['port'], $db['name']);
    $pdo = new PDO($dsn, (string) $db['user'], (string) $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_TIMEOUT => 8,
    ]);
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET time_zone = '+05:00'");
    $pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    return $pdo;
}

function inst_server_version_ok(PDO $pdo): array
{
    $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    $isMaria = stripos($version, 'mariadb') !== false;
    preg_match('/(\d+)\.(\d+)/', $version, $m);
    $major = (int) ($m[1] ?? 0);
    $minor = (int) ($m[2] ?? 0);
    $ok = $isMaria ? ($major > 10 || ($major === 10 && $minor >= 4)) : ($major >= 8);
    return ['ok' => $ok, 'version' => $version, 'flavour' => $isMaria ? 'MariaDB' : 'MySQL'];
}

function inst_table_count(PDO $pdo, string $dbName): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :schema');
    $stmt->execute([':schema' => $dbName]);
    return (int) $stmt->fetchColumn();
}

function inst_setting(PDO $pdo, string $key): ?string
{
    try {
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = :k');
        $stmt->execute([':k' => $key]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : (string) $value;
    } catch (PDOException $e) {
        return null;
    }
}

function inst_admin_count(PDO $pdo): ?int
{
    try {
        return (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    } catch (PDOException $e) {
        return null;
    }
}

function inst_config_load(): ?array
{
    if (!is_file(INSTALL_CONFIG)) {
        return null;
    }
    try {
        $config = include INSTALL_CONFIG;
    } catch (Throwable $e) {
        return null;
    }
    return is_array($config) && isset($config['db']) && is_array($config['db']) ? $config : null;
}

function inst_config_source(array $config): string
{
    return "<?php\ndefined('SKYFR') || exit;\n\nreturn " . var_export($config, true) . ";\n";
}

function inst_write_atomic(string $path, string $content, int $mode): bool
{
    $dir = dirname($path);
    if (!is_dir($dir) || !is_writable($dir)) {
        return false;
    }
    $temp = $dir . '/.' . basename($path) . '.' . inst_random_hex(4) . '.tmp';
    if (file_put_contents($temp, $content, LOCK_EX) === false) {
        return false;
    }
    @chmod($temp, $mode);
    if (!rename($temp, $path)) {
        @unlink($temp);
        return false;
    }
    return true;
}

function inst_state(): array
{
    $locked = is_file(INSTALL_LOCK);
    $config = inst_config_load();
    if ($config === null) {
        if ($locked && !is_file(INSTALL_CONFIG)) {
            @unlink(INSTALL_LOCK);
            return ['stage' => 'fresh', 'config' => null, 'detail' => '', 'notice' => 'A leftover lock file (storage/.installed) from another copy of the files was removed because there is no config.php next to it.'];
        }
        return ['stage' => is_file(INSTALL_CONFIG) ? 'config_bad' : 'fresh', 'config' => null, 'detail' => is_file(INSTALL_CONFIG) ? 'config.php exists but could not be read as a settings file.' : ''];
    }
    try {
        $pdo = inst_pdo($config['db']);
    } catch (Throwable $e) {
        return ['stage' => 'config_bad', 'config' => $config, 'detail' => 'config.php exists but the database details inside it do not connect.'];
    }
    if (inst_setting($pdo, 'install_completed_at') !== null) {
        if (!$locked) {
            inst_write_atomic(INSTALL_LOCK, inst_now() . "\n", 0644);
        }
        return ['stage' => 'installed', 'config' => $config, 'detail' => 'The settings table says the installation was completed.'];
    }
    if ($locked) {
        @unlink(INSTALL_LOCK);
    }
    $tables = inst_table_count($pdo, (string) $config['db']['name']);
    if ($tables === 0) {
        return ['stage' => 'resume', 'config' => $config, 'detail' => ''];
    }
    $admins = inst_admin_count($pdo);
    if ($admins === 0) {
        return ['stage' => 'partial', 'config' => $config, 'detail' => 'The database has ' . $tables . ' tables from an unfinished installation and no admin account.'];
    }
    return ['stage' => 'occupied', 'config' => $config, 'detail' => 'The database already contains ' . $tables . ' tables' . ($admins === null ? '' : ' and an admin account') . '.'];
}

function inst_requirements(): array
{
    $rows = [];
    $php = PHP_VERSION;
    if (!version_compare($php, '8.2.0', '>=')) {
        $rows[] = ['fail', 'PHP version', $php . ' — this shop needs PHP 8.2 or newer. In hPanel open Advanced → PHP Configuration and choose PHP 8.2.'];
    } elseif (version_compare($php, '8.4.0', '>=')) {
        $rows[] = ['warn', 'PHP version', $php . ' — tested on PHP 8.2 and 8.3. If anything misbehaves, choose PHP 8.3 in hPanel → Advanced → PHP Configuration.'];
    } else {
        $rows[] = ['pass', 'PHP version', $php];
    }
    $memory = inst_ini_bytes((string) ini_get('memory_limit'));
    $memoryLabel = $memory === PHP_INT_MAX ? 'unlimited' : ini_get('memory_limit');
    $rows[] = $memory >= INSTALL_MIN_MEMORY_BYTES
        ? ['pass', 'PHP memory limit', $memoryLabel . ' — product photos up to ' . number_format(inst_pixel_budget() / 1000000, 1) . ' megapixels can be resized']
        : ['fail', 'PHP memory limit', $memoryLabel . ' — at least 128M is needed to resize product photos. In hPanel open Advanced → PHP Configuration → PHP Options and raise memory_limit.'];
    $postMax = inst_ini_bytes((string) ini_get('post_max_size'));
    $uploadMax = inst_ini_bytes((string) ini_get('upload_max_filesize'));
    $rows[] = $postMax >= INSTALL_MAX_UPLOAD_BYTES && $uploadMax >= INSTALL_MAX_UPLOAD_BYTES
        ? ['pass', 'Upload size limits', 'post_max_size ' . ini_get('post_max_size') . ', upload_max_filesize ' . ini_get('upload_max_filesize')]
        : ['fail', 'Upload size limits', 'post_max_size is ' . ini_get('post_max_size') . ' and upload_max_filesize is ' . ini_get('upload_max_filesize') . ' — both must be at least 6M so a product photo can be uploaded. In hPanel open Advanced → PHP Configuration → PHP Options.'];
    foreach (['pdo_mysql' => 'talks to the database', 'mbstring' => 'handles text', 'fileinfo' => 'checks uploaded images', 'gd' => 'resizes product photos', 'openssl' => 'creates secure keys'] as $ext => $why) {
        $rows[] = extension_loaded($ext)
            ? ['pass', 'PHP extension ' . $ext, 'installed']
            : ['fail', 'PHP extension ' . $ext, 'missing — it ' . $why . '. In hPanel open Advanced → PHP Configuration → PHP Extensions and tick ' . $ext . '.'];
    }
    $rows[] = extension_loaded('curl')
        ? ['pass', 'PHP extension curl', 'installed']
        : ['warn', 'PHP extension curl', 'missing — the installer cannot run its own web checks, but the shop will still work.'];
    $rows[] = function_exists('imagewebp')
        ? ['pass', 'WebP images', 'supported — product photos will be small and fast']
        : ['warn', 'WebP images', 'not supported by this PHP build — JPEG will be used instead. The shop works either way.'];
    $rows[] = is_writable(INSTALL_ROOT)
        ? ['pass', 'Website folder is writable', 'config.php can be created for you']
        : ['warn', 'Website folder is writable', 'not writable — you will be shown the config.php contents to paste into File Manager.'];
    foreach (INSTALL_RUNTIME_DIRS as $rel) {
        $path = INSTALL_ROOT . '/' . $rel;
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        $rows[] = is_dir($path) && is_writable($path)
            ? ['pass', 'Folder ' . $rel, 'exists and is writable']
            : ['fail', 'Folder ' . $rel, 'cannot be created or written. In File Manager create the folder and set its permissions to 755.'];
    }
    $rows[] = is_file(INSTALL_ROOT . '/.htaccess')
        ? ['pass', 'Protection file .htaccess', 'present']
        : ['fail', 'Protection file .htaccess', 'missing. Your ZIP tool skipped hidden files. Re-upload the ZIP or copy this file from it using File Manager (tick "Show hidden files").'];
    foreach (inst_write_protection_files(false) as $row) {
        $rows[] = $row;
    }
    $rows[] = inst_trusted_proxies() === []
        ? ['pass', 'Client IP detection', 'visitors connect to this server directly; their own addresses will be used for rate limits']
        : ['warn', 'Client IP detection', 'requests arrive through a proxy (' . implode(', ', inst_trusted_proxies()) . '); it will be recorded as trusted so visitors are told apart correctly.'];
    $rows[] = is_file(INSTALL_SCHEMA)
        ? ['pass', 'Database blueprint db/schema.sql', 'present']
        : ['fail', 'Database blueprint db/schema.sql', 'missing — upload the complete ZIP again.'];
    $rows[] = is_file(INSTALL_SEED)
        ? ['pass', 'Sample data db/seed.sql', 'present']
        : ['warn', 'Sample data db/seed.sql', 'missing — the shop will start empty.'];
    $base = inst_base_path();
    if ($base !== '') {
        $rows[] = ['fail', 'Sub-folder install', 'The shop files are in a sub-folder (' . $base . '). They must sit directly in public_html so that skyfragrances.com/shop and the other addresses work. Move the files up one level in File Manager and reload.'];
    }
    if (is_file(INSTALL_ROOT . '/default.php')) {
        $rows[] = ['warn', 'Hostinger placeholder page', 'default.php is still in public_html. Delete it in File Manager so it can never be shown instead of the shop.'];
    }
    if (is_dir(INSTALL_ROOT . '/public_html')) {
        $rows[] = ['fail', 'Nested public_html', 'There is a public_html folder inside the website folder — the ZIP was extracted one level too deep. Move its contents up into public_html.'];
    }
    if (!inst_host_points_here()) {
        $rows[] = ['warn', 'Pretty web addresses', 'could not be checked because ' . inst_request_host() . ' does not point at this server yet (this is normal before your domain is connected). Test /shop in your browser after installing.'];
        return $rows;
    }
    $probe = inst_http_get(inst_request_base_url() . '/__rewrite-probe');
    if ($probe === null) {
        $rows[] = ['warn', 'Pretty web addresses', 'could not be checked from this server (this is normal before your domain points here). Test /shop in your browser after installing.'];
    } elseif (str_contains($probe, INSTALL_PROBE_SENTINEL)) {
        $rows[] = ['pass', 'Pretty web addresses', 'working'];
    } else {
        $rows[] = ['warn', 'Pretty web addresses', 'the test address did not answer as expected. If /shop shows "not found" after installing, make sure the .htaccess file is in the website folder.'];
    }
    return $rows;
}

function inst_rows_have(array $rows, string $status): bool
{
    foreach ($rows as $row) {
        if ($row[0] === $status) {
            return true;
        }
    }
    return false;
}

function inst_render_rows(array $rows): string
{
    $html = '<table class="checks">';
    foreach ($rows as [$status, $label, $detail]) {
        $word = ['pass' => 'OK', 'warn' => 'Note', 'fail' => 'Problem'][$status];
        $html .= '<tr class="' . inst_e($status) . '"><td class="st">' . $word . '</td><td><strong>' . inst_e($label) . '</strong><br><span>' . inst_e($detail) . '</span></td></tr>';
    }
    return $html . '</table>';
}

function inst_run_sql_file(PDO $pdo, string $path): array
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        return ['ok' => false, 'count' => 0, 'index' => 0, 'error' => 'The file could not be read.', 'statement' => ''];
    }
    $statements = inst_split_sql($sql);
    foreach ($statements as $i => $statement) {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            return ['ok' => false, 'count' => count($statements), 'index' => $i + 1, 'error' => $e->getMessage(), 'statement' => mb_substr($statement, 0, 400)];
        }
    }
    return ['ok' => true, 'count' => count($statements), 'index' => count($statements), 'error' => '', 'statement' => ''];
}

function inst_setting_upsert(PDO $pdo, string $key, string $value, string $group): void
{
    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, setting_group, updated_at) VALUES (:k, :v, :g, :t) ON DUPLICATE KEY UPDATE setting_value = :v2, updated_at = :t2');
    $now = inst_now();
    $stmt->execute([':k' => $key, ':v' => $value, ':g' => $group, ':t' => $now, ':v2' => $value, ':t2' => $now]);
}

function inst_protection_files(): array
{
    return [
        'app/.htaccess' => INSTALL_DENY_HTACCESS,
        'db/.htaccess' => INSTALL_DENY_HTACCESS,
        'storage/.htaccess' => INSTALL_STORAGE_HTACCESS,
        'uploads/.htaccess' => INSTALL_UPLOADS_HTACCESS,
        'assets/.htaccess' => INSTALL_ASSETS_HTACCESS,
        'admin/.htaccess' => INSTALL_ADMIN_HTACCESS,
        'admin/controllers/.htaccess' => INSTALL_DENY_HTACCESS,
        'admin/views/.htaccess' => INSTALL_DENY_HTACCESS,
        'admin/partials/.htaccess' => INSTALL_DENY_HTACCESS,
    ];
}

function inst_stub_files(): array
{
    return ['db/index.php', 'storage/index.php', 'admin/controllers/index.php', 'admin/views/index.php', 'admin/partials/index.php'];
}

function inst_write_protection_files(bool $overwrite): array
{
    $rows = [];
    foreach (inst_protection_files() as $rel => $text) {
        $path = INSTALL_ROOT . '/' . $rel;
        if (!is_dir(dirname($path))) {
            @mkdir(dirname($path), 0755, true);
        }
        $current = is_file($path) ? (string) file_get_contents($path) : null;
        if ($current === $text || ($current !== null && !$overwrite)) {
            $rows[] = ['pass', 'Protection file ' . $rel, 'present'];
            continue;
        }
        $rows[] = inst_write_atomic($path, $text, 0644)
            ? ['pass', 'Protection file ' . $rel, $current === null ? 'written' : 'refreshed']
            : ['fail', 'Protection file ' . $rel, 'could not be written. In File Manager copy it from the ZIP (tick "Show hidden files").'];
    }
    foreach (inst_stub_files() as $rel) {
        $path = INSTALL_ROOT . '/' . $rel;
        if (!is_file($path) || filesize($path) === 0) {
            inst_write_atomic($path, INSTALL_STUB_PHP, 0644);
        }
    }
    return $rows;
}

function inst_prepare_dirs(): array
{
    $rows = [];
    foreach (INSTALL_RUNTIME_DIRS as $rel) {
        $path = INSTALL_ROOT . '/' . $rel;
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        $rows[] = is_dir($path) && is_writable($path)
            ? ['pass', 'Folder ' . $rel, 'ready']
            : ['fail', 'Folder ' . $rel, 'could not be created — create it in File Manager with permissions 755.'];
    }
    foreach (inst_write_protection_files(true) as $row) {
        $rows[] = $row;
    }
    return $rows;
}

function inst_selftest_uploads(): array
{
    $probeName = '.probe-' . inst_random_hex(6) . '.php';
    $probePath = INSTALL_ROOT . '/uploads/' . $probeName;
    if (file_put_contents($probePath, "<?php echo 'EXEC';") === false) {
        return ['warn', 'Uploads folder cannot run programs', 'could not be tested (the test file could not be written).'];
    }
    $body = inst_http_get(inst_request_base_url() . '/uploads/' . $probeName);
    @unlink($probePath);
    if ($body === null) {
        return ['warn', 'Uploads folder cannot run programs', 'Could not verify — open ' . inst_request_base_url() . '/uploads/' . $probeName . ' on your phone; it must NOT show the word EXEC. (The test file was removed; upload any .php to uploads/ to repeat the test.)'];
    }
    if (trim($body) === 'EXEC') {
        return ['fail', 'Uploads folder cannot run programs', 'FAIL — a program placed in uploads/ was executed. Do not go live. Check that uploads/.htaccess exists and contact your developer.'];
    }
    return ['pass', 'Uploads folder cannot run programs', 'PASS'];
}

function inst_selftest_denied(): array
{
    $rows = [];
    foreach (['app/bootstrap.php', 'db/schema.sql', 'storage/.htaccess', 'storage/.installed', 'config.php', 'admin/controllers/dashboard.php'] as $rel) {
        $status = inst_http_status(inst_request_base_url() . '/' . $rel);
        if ($status === null) {
            $rows[] = ['warn', 'Private file ' . $rel, 'Could not verify — open ' . inst_request_base_url() . '/' . $rel . ' on your phone; it must show an error, never the file.'];
        } elseif ($status === 200) {
            $rows[] = ['fail', 'Private file ' . $rel, 'is readable from the internet. Check that the .htaccess files were uploaded.'];
        } else {
            $rows[] = ['pass', 'Private file ' . $rel, 'blocked (HTTP ' . $status . ')'];
        }
    }
    return $rows;
}

function inst_redirect(string $query): never
{
    header('Location: install.php' . ($query === '' ? '' : '?' . $query), true, 303);
    exit;
}

function inst_page(string $title, string $body, int $step): never
{
    $steps = [1 => 'Check server', 2 => 'Database', 3 => 'Admin & data', 4 => 'Finish'];
    $nav = '';
    foreach ($steps as $n => $label) {
        $class = $n === $step ? 'on' : ($n < $step ? 'done' : '');
        $nav .= '<li class="' . $class . '"><span>' . $n . '</span>' . inst_e($label) . '</li>';
    }
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . inst_e($title) . ' — Sky Fragrances installer</title><style>'
        . 'body{margin:0;font:16px/1.5 Georgia,serif;background:#0a0a0a;color:#f5f0e8}'
        . 'header{padding:20px 24px;border-bottom:1px solid #2a2520}header b{color:#d4b084;letter-spacing:.2em;font-size:14px}'
        . 'main{max-width:820px;margin:0 auto;padding:24px 16px 64px}h1{font-weight:400;font-size:30px;margin:0 0 8px}h2{font-weight:400;font-size:22px;margin:0 0 10px}'
        . 'ol.steps{display:flex;gap:8px;list-style:none;padding:0;margin:0 0 28px;flex-wrap:wrap}ol.steps li{flex:1 1 150px;padding:10px 12px;border:1px solid #2a2520;color:#8a8078;font-size:14px}'
        . 'ol.steps li span{display:inline-block;width:22px;height:22px;line-height:22px;text-align:center;border-radius:50%;background:#2a2520;margin-right:8px}'
        . 'ol.steps li.on{border-color:#d4b084;color:#f5f0e8}ol.steps li.on span{background:#d4b084;color:#0a0a0a}ol.steps li.done{color:#d4b084}'
        . '.box{background:#141210;border:1px solid #2a2520;padding:20px 22px;margin:0 0 20px}.box.bad{border-color:#b3261e;background:#2a0f0d}.box.good{border-color:#3f7f4f}.box.danger{border:3px solid #ff3b30;background:#7a0d0a;padding:28px}'
        . '.box.danger h2{font-size:28px;margin:0 0 12px;color:#fff}.box.danger code{display:block;background:#0a0a0a;padding:10px;margin:12px 0;font-size:15px;word-break:break-all}'
        . 'table.checks{width:100%;border-collapse:collapse;margin:0 0 20px}table.checks td{padding:10px 8px;border-bottom:1px solid #2a2520;vertical-align:top}table.checks td span{color:#b5aa9d;font-size:14px}'
        . 'td.st{width:78px;font-size:13px;font-weight:bold;letter-spacing:.05em;text-transform:uppercase}tr.pass td.st{color:#7fcf8f}tr.warn td.st{color:#e0b458}tr.fail td.st{color:#ff6b61}'
        . 'label{display:block;margin:14px 0 4px;color:#d4b084;font-size:14px;letter-spacing:.05em}input[type=text],input[type=password],input[type=email],input[type=number],input[type=url],textarea{width:100%;box-sizing:border-box;padding:11px 12px;background:#0a0a0a;color:#f5f0e8;border:1px solid #3a342e;font:inherit;font-size:16px}'
        . 'input:focus,textarea:focus{outline:none;border-color:#d4b084}small{color:#b5aa9d;display:block;margin-top:4px}.check{display:flex;gap:10px;align-items:flex-start;margin:16px 0;color:#f5f0e8;font-size:16px}.check input{margin-top:5px}'
        . '.btn{display:inline-block;padding:13px 26px;background:#d4b084;color:#0a0a0a;text-decoration:none;border:0;font:inherit;font-size:16px;cursor:pointer;margin:8px 8px 0 0}.btn.ghost{background:transparent;color:#d4b084;border:1px solid #d4b084}.btn.red{background:#fff;color:#7a0d0a;font-weight:bold}'
        . 'pre{white-space:pre-wrap;word-break:break-all;background:#0a0a0a;padding:12px;font-size:13px;color:#e0b458}p.err{color:#ff6b61}'
        . '</style></head><body><header><b>SKY FRAGRANCES</b> · Installer</header><main>'
        . ($step > 0 ? '<ol class="steps">' . $nav . '</ol>' : '')
        . $body . '</main></body></html>';
    exit;
}

function inst_field(string $type, string $name, string $label, string $value = '', string $hint = '', bool $required = true): string
{
    $html = '<label for="f_' . inst_e($name) . '">' . inst_e($label) . '</label>';
    $html .= '<input type="' . inst_e($type) . '" id="f_' . inst_e($name) . '" name="' . inst_e($name) . '" value="' . inst_e($value) . '"' . ($required ? ' required' : '') . ($type === 'password' ? ' autocomplete="new-password"' : ' autocomplete="off"') . '>';
    if ($hint !== '') {
        $html .= '<small>' . inst_e($hint) . '</small>';
    }
    return $html;
}

function inst_page_config_bad(array $state): never
{
    $body = '<h1>Check server</h1><div class="box bad"><h2>config.php exists but its database does not answer</h2><p>' . inst_e($state['detail']) . '</p>'
        . '<p>For safety the installer will not overwrite an existing config.php. Either:</p><ol><li>Fix the database: in hPanel → Databases check that the database and user still exist and that the password matches the one in config.php, then <a href="install.php" style="color:#d4b084">reload this page</a>; or</li>'
        . '<li>Start again: in hPanel → File Manager → public_html delete <strong>config.php</strong> by hand, then reload this page and enter the database details afresh.</li></ol></div>';
    inst_page('Check server', $body, 1);
}

function inst_page_requirements(array $state): never
{
    if ($state['stage'] === 'config_bad') {
        inst_page_config_bad($state);
    }
    $rows = inst_requirements();
    $body = '<h1>Welcome</h1><p>This installer sets up your Sky Fragrances shop in four short steps. First, a quick check of the server.</p>';
    if (!empty($state['notice'])) {
        $body .= '<div class="box"><p>' . inst_e($state['notice']) . '</p></div>';
    }
    if ($state['stage'] === 'occupied') {
        $body .= '<div class="box bad"><h2>The database is not empty</h2><p>' . inst_e($state['detail']) . ' The installer never deletes existing tables.</p><p>Either create a new, empty database in hPanel → Databases and enter its details, or empty this database in phpMyAdmin first.</p><p><a class="btn" href="install.php?step=2">Enter different database details</a></p></div>';
        inst_page('Check server', $body, 1);
    }
    if ($state['stage'] === 'partial') {
        $body .= '<div class="box bad"><h2>An earlier attempt did not finish</h2><p>' . inst_e($state['detail']) . ' You can continue: the unfinished tables will be replaced when you confirm this on the next screen.</p></div>';
    }
    $body .= inst_render_rows($rows);
    if (inst_rows_have($rows, 'fail')) {
        $body .= '<div class="box bad"><p>Please fix the items marked <strong>Problem</strong>, then <a href="install.php?step=1" style="color:#d4b084">reload this page</a>. Items marked <strong>Note</strong> do not stop the installation.</p></div>';
    } else {
        $next = in_array($state['stage'], ['resume', 'partial'], true) ? 3 : 2;
        $body .= '<p>' . (inst_rows_have($rows, 'warn') ? 'Everything essential is in place. Items marked <strong>Note</strong> are for information only.' : 'Everything is in place.') . '</p>';
        $body .= '<a class="btn" href="install.php?step=' . $next . '">Continue</a>';
        if ($next === 3) {
            $body .= '<a class="btn ghost" href="install.php?step=2">Change database details</a>';
        }
    }
    inst_page('Check server', $body, 1);
}

function inst_page_db_form(array $state, string $error = '', array $old = []): never
{
    $cfg = $state['config'] ?? [];
    $db = $cfg['db'] ?? [];
    $smtp = $cfg['smtp'] ?? [];
    $v = fn (string $k, string $d = '') => $old[$k] ?? $d;
    $body = '<h1>Database</h1><p>Copy these details from hPanel → Databases → MySQL Databases. Nothing is written until the connection has been tested.</p>';
    if ($error !== '') {
        $body .= '<div class="box bad"><p class="err">' . inst_e($error) . '</p></div>';
    }
    $body .= '<form method="post" action="install.php?step=2" class="box">' . inst_csrf_field() . '<input type="hidden" name="action" value="db_save">' . inst_install_key_field();
    $body .= inst_field('text', 'db_host', 'Database host', $v('db_host', (string) ($db['host'] ?? 'localhost')), 'Usually "localhost" on Hostinger.');
    $body .= inst_field('number', 'db_port', 'Database port', $v('db_port', (string) ($db['port'] ?? '3306')), 'Usually 3306.');
    $body .= inst_field('text', 'db_name', 'Database name', $v('db_name', (string) ($db['name'] ?? '')), 'For example u123456789_skyfrag.');
    $body .= inst_field('text', 'db_user', 'Database username', $v('db_user', (string) ($db['user'] ?? '')));
    $body .= inst_field('password', 'db_pass', 'Database password', '', 'The password you set when creating the database.', false);
    $body .= '<h2 style="margin-top:28px">Shop address</h2>';
    $body .= inst_field('url', 'base_url', 'Shop web address', $v('base_url', (string) ($cfg['base_url'] ?? inst_request_base_url())), 'You opened this page at ' . inst_request_base_url() . ' — use that unless you know why not. It is only used for links in emails, the sitemap and social previews; it can be changed later in Admin → Settings → Advanced.');
    $body .= '<h2 style="margin-top:28px">Email sending (optional)</h2><p><small>Create a mailbox in hPanel → Emails first. Leave the host empty to skip for now; order emails will then be saved to a log instead of sent.</small></p>';
    $body .= inst_field('text', 'smtp_host', 'SMTP host', $v('smtp_host', (string) ($smtp['host'] ?? 'smtp.hostinger.com')), '', false);
    $body .= inst_field('number', 'smtp_port', 'SMTP port', $v('smtp_port', (string) ($smtp['port'] ?? '587')), '', false);
    $body .= inst_field('email', 'smtp_user', 'Mailbox address', $v('smtp_user', (string) ($smtp['user'] ?? '')), 'For example orders@skyfragrances.com', false);
    $body .= inst_field('password', 'smtp_pass', 'Mailbox password', '', '', false);
    $body .= inst_field('text', 'smtp_from_name', 'Sender name', $v('smtp_from_name', (string) ($smtp['from_name'] ?? 'Sky Fragrances')), '', false);
    $body .= '<p><button class="btn" type="submit">Test connection and continue</button><a class="btn ghost" href="install.php?step=1">Back</a></p></form>';
    inst_page('Database', $body, 2);
}

function inst_page_admin_form(array $state, string $error = '', array $old = [], ?array $mailTest = null): never
{
    $v = fn (string $k, string $d = '') => $old[$k] ?? $d;
    $body = '<h1>Admin account & sample data</h1><p>This creates the one account you will use to log in at <strong>/admin</strong>. Choose a long password you do not use anywhere else.</p>';
    if ($error !== '') {
        $body .= '<div class="box bad"><p class="err">' . inst_e($error) . '</p></div>';
    }
    if ($mailTest !== null) {
        $body .= '<div class="box ' . ($mailTest['ok'] ? 'good' : 'bad') . '"><h2>' . ($mailTest['ok'] ? 'Test email sent' : 'Test email failed') . '</h2><p>' . inst_e($mailTest['detail']) . '</p></div>';
    }
    $body .= '<form method="post" action="install.php?step=3" class="box">' . inst_csrf_field() . '<input type="hidden" name="action" value="install">' . inst_install_key_field();
    $body .= inst_field('text', 'admin_username', 'Admin username', $v('admin_username'), 'Choose your own, for example sky.owner — not "admin", which is the first name an attacker tries. Letters, numbers, dots, dashes and underscores; 3 to 64 characters.');
    $body .= inst_field('email', 'admin_email', 'Your email address', $v('admin_email'), 'Used for order notifications and shown on the contact page.');
    $body .= inst_field('password', 'admin_password', 'Admin password', '', 'At least ' . INSTALL_MIN_PASSWORD . ' characters.');
    $body .= inst_field('password', 'admin_password2', 'Repeat the password');
    $body .= inst_field('text', 'store_name', 'Store name', $v('store_name', 'Sky Fragrances'));
    $body .= inst_field('text', 'whatsapp', 'WhatsApp number', $v('whatsapp'), 'For example 0300 1234567. Customers will message this number.', false);
    $body .= '<label class="check"><input type="checkbox" name="seed" value="1"' . (isset($old['seed']) && $old['seed'] === '' ? '' : ' checked') . '> Install 12 sample perfumes, 5 collections and the WELCOME10 coupon (recommended — you can delete them later from Admin → Tools).</label>';
    if ($state['stage'] === 'partial') {
        $body .= '<label class="check"><input type="checkbox" name="replace_partial" value="1"> I understand the unfinished tables from the earlier attempt will be replaced.</label>';
    }
    $smtpHost = trim((string) ($state['config']['smtp']['host'] ?? ''));
    $body .= '<p><button class="btn" type="submit">Create the shop</button>'
        . ($smtpHost !== '' ? '<button class="btn ghost" type="submit" name="action" value="test_email" formnovalidate>Send test email to the address above</button>' : '')
        . '<a class="btn ghost" href="install.php?step=1">Back</a></p>'
        . ($smtpHost !== '' ? '<small>Press "Send test email" first and confirm it arrives; the order emails use the same mailbox.</small>' : '<small>No mailbox was entered on the Database step, so emails will be saved to a log instead of sent. You can add SMTP details later in config.php.</small>')
        . '</form>';
    inst_page('Admin account', $body, 3);
}

function inst_page_finish(array $results, string $notice = '', ?array $checks = null, bool $selfDeleted = false): never
{
    $adminUrl = inst_request_base_url() . '/admin/login';
    if ($selfDeleted) {
        $body = '<div class="box good"><h2>Your shop is installed and install.php has deleted itself</h2><p>Nothing else needs cleaning up. Log in at <a href="' . inst_e($adminUrl) . '" style="color:#d4b084">' . inst_e($adminUrl) . '</a>.</p></div>'
            . '<div class="box danger"><h2>Take screenshots of this page now</h2><p>This page is shown once. The installer has already deleted itself, so reloading or closing it shows the shop\'s "page not found" and the report below cannot be opened again. Scroll down and screenshot the whole page, then send the screenshots to your developer.</p></div>';
    } else {
        $body = '<div class="box danger"><h2>Delete install.php now</h2><p>Your shop is installed, but this file could not delete itself. Anyone who can open it while it exists can see your server details, so it must go.</p>'
            . '<p>Open hPanel → File Manager → public_html and delete this file:</p><code>' . inst_e(__FILE__) . '</code>'
            . '<form method="post" action="install.php" style="display:inline">' . inst_csrf_field() . '<input type="hidden" name="action" value="self_delete"><button class="btn red" type="submit">Delete install.php for me</button></form>'
            . '<form method="post" action="install.php" style="display:inline">' . inst_csrf_field() . '<input type="hidden" name="action" value="check_gone"><button class="btn ghost" type="submit" style="color:#fff;border-color:#fff">Check whether it is gone</button></form></div>';
    }
    if ($notice !== '') {
        $body .= '<div class="box"><p>' . inst_e($notice) . '</p></div>';
    }
    if ($results !== []) {
        $body .= '<h2>Installation report</h2>' . inst_render_rows($results);
    }
    $checks = $checks ?? ($_SESSION['inst_checks'] ?? []);
    $body .= '<div class="box"><h2>Security checks</h2><p>These asked your own website a few questions: is uploads/ blocked from running programs, are private files hidden. Rows marked Note could not be verified from the server; open the address they name on your phone and confirm what they ask.</p>'
        . ($checks !== [] ? inst_render_rows($checks) : '<p>Not run yet.</p>')
        . ($selfDeleted ? '' : '<form method="post" action="install.php">' . inst_csrf_field() . '<input type="hidden" name="action" value="checks"><button class="btn ghost" type="submit">' . ($checks === [] ? 'Run the checks' : 'Run the checks again') . '</button></form>')
        . '</div>';
    $body .= '<div class="box good"><h2>Next steps</h2><ol>' . ($selfDeleted ? '' : '<li>Delete install.php (above).</li>') . '<li>Log in at <a href="' . inst_e($adminUrl) . '" style="color:#d4b084">' . inst_e($adminUrl) . '</a>. Your phone becomes a known device on the first login.</li><li>Open Admin → Settings and fill in your phone, WhatsApp, bank details and announcement text.</li><li>Replace the sample perfumes with your own products, then delete the sample data from Admin → Tools before going live.</li><li>If HTTPS was not confirmed above, press Settings → Advanced → Make HTTPS permanent once the padlock shows.</li></ol></div>';
    inst_page('Finished', $body, 4);
}

function inst_page_refuse(array $state): never
{
    $body = '<div class="box danger"><h2>Sky Fragrances is already installed — delete install.php</h2><p>' . inst_e($state['detail']) . ' This installer will not run again. Leaving the file here is a security risk.</p>'
        . '<p>Open hPanel → File Manager → public_html and delete this file:</p><code>' . inst_e(__FILE__) . '</code>'
        . '<form method="post" action="install.php" style="display:inline">' . inst_csrf_field() . '<input type="hidden" name="action" value="self_delete"><button class="btn red" type="submit">Delete install.php for me</button></form>'
        . '<a class="btn ghost" style="color:#fff;border-color:#fff" href="' . inst_e(inst_request_base_url() . '/admin/login') . '">Go to the admin login</a></div>';
    inst_page('Already installed', $body, 0);
}

function inst_handle_self_delete(): never
{
    $deleted = @unlink(__FILE__);
    $body = $deleted
        ? '<div class="box good"><h2>install.php has been deleted</h2><p>Well done. Your shop is ready.</p><a class="btn" href="' . inst_e(inst_request_base_url() . '/admin/login') . '">Go to the admin login</a></div>'
        : '<div class="box danger"><h2>Could not delete the file automatically</h2><p>Please delete it by hand in hPanel → File Manager → public_html:</p><code>' . inst_e(__FILE__) . '</code></div>';
    inst_page('Delete install.php', $body, 0);
}

function inst_handle_check_gone(): never
{
    $status = inst_http_status(inst_request_base_url() . '/install.php');
    $results = $_SESSION['inst_results'] ?? [];
    if ($status === null) {
        inst_page_finish($results, 'The check could not reach your website from the server. Open ' . inst_request_base_url() . '/install.php in your browser: it should say "not found".');
    }
    inst_page_finish($results, $status === 404 || $status === 410 ? 'install.php is gone. Well done.' : 'install.php is still there (HTTP ' . $status . '). Please delete it.');
}

function inst_handle_checks(): never
{
    $_SESSION['inst_checks'] = inst_run_security_checks();
    inst_redirect('step=4');
}

function inst_trusted_proxies(): array
{
    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '');
    if ($remote === '' || $forwarded === '' || filter_var($remote, FILTER_VALIDATE_IP) === false) {
        return [];
    }
    return [$remote . (str_contains($remote, ':') ? '/128' : '/32')];
}

function inst_write_robots(string $baseUrl): void
{
    if (!is_file(INSTALL_ROBOTS)) {
        return;
    }
    $lines = preg_split('/\r\n|\r|\n/', (string) file_get_contents(INSTALL_ROBOTS)) ?: [];
    $kept = array_values(array_filter($lines, static fn (string $line): bool => !str_starts_with($line, 'Sitemap:')));
    while ($kept !== [] && trim((string) end($kept)) === '') {
        array_pop($kept);
    }
    $kept[] = 'Sitemap: ' . $baseUrl . '/sitemap.xml';
    inst_write_atomic(INSTALL_ROBOTS, implode("\n", $kept) . "\n", 0644);
}

function inst_send_test_email(array $smtp, string $to): array
{
    $dir = INSTALL_ROOT . '/app/lib/vendor/PHPMailer/';
    foreach (['Exception.php', 'PHPMailer.php', 'SMTP.php'] as $file) {
        if (!is_file($dir . $file)) {
            return ['ok' => false, 'detail' => 'The email library (app/lib/vendor/PHPMailer) is missing from the upload.'];
        }
        require_once $dir . $file;
    }
    try {
        $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->SMTPAuth = true;
        $mailer->Host = (string) ($smtp['host'] ?? '');
        $mailer->Port = (int) ($smtp['port'] ?? 587);
        $secure = strtolower((string) ($smtp['secure'] ?? 'tls'));
        $mailer->SMTPSecure = $secure === 'ssl' ? 'ssl' : ($secure === 'tls' ? 'tls' : '');
        $mailer->SMTPAutoTLS = $secure !== '';
        $mailer->Username = (string) ($smtp['user'] ?? '');
        $mailer->Password = (string) ($smtp['pass'] ?? '');
        $mailer->Timeout = 5;
        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom($mailer->Username, (string) ($smtp['from_name'] ?? 'Sky Fragrances'), false);
        $mailer->addAddress($to);
        $mailer->Subject = 'Sky Fragrances — test email from the installer';
        $mailer->Body = 'This message confirms that your shop can send email. Order confirmations and alerts will come from this mailbox.';
        $mailer->send();
        return ['ok' => true, 'detail' => 'Sent to ' . $to . ' from ' . $mailer->Username . '. Check the inbox (and spam) on your phone before continuing.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'detail' => 'The mail server refused: ' . $e->getMessage() . ' — check the mailbox password in config.php, or go back to the Database step and re-enter the email settings.'];
    }
}

function inst_handle_test_email(array $state): never
{
    if (!in_array($state['stage'], ['resume', 'partial'], true)) {
        inst_redirect('step=1');
    }
    $old = [];
    foreach (['admin_username', 'admin_email', 'store_name', 'whatsapp', 'seed'] as $k) {
        $old[$k] = inst_post($k);
    }
    if (filter_var($old['admin_email'], FILTER_VALIDATE_EMAIL) === false) {
        inst_page_admin_form($state, 'Enter your email address first, then press "Send test email".', $old);
    }
    $smtp = $state['config']['smtp'] ?? [];
    if (trim((string) ($smtp['host'] ?? '')) === '') {
        inst_page_admin_form($state, '', $old, ['ok' => false, 'detail' => 'No mailbox was entered on the Database step, so there is nothing to test.']);
    }
    inst_page_admin_form($state, '', $old, inst_send_test_email($smtp, $old['admin_email']));
}

function inst_handle_db_save(array $state): never
{
    if ($state['stage'] === 'config_bad') {
        inst_page_config_bad($state);
    }
    if (inst_base_path() !== '') {
        inst_redirect('step=1');
    }
    $old = [];
    foreach (['db_host', 'db_port', 'db_name', 'db_user', 'base_url', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_from_name'] as $k) {
        $old[$k] = inst_post($k);
    }
    $db = ['host' => $old['db_host'], 'port' => (int) ($old['db_port'] === '' ? 3306 : $old['db_port']), 'name' => $old['db_name'], 'user' => $old['db_user'], 'pass' => (string) ($_POST['db_pass'] ?? '')];
    if ($db['host'] === '' || $db['name'] === '' || $db['user'] === '') {
        inst_page_db_form($state, 'Please fill in the database host, name and username.', $old);
    }
    $baseUrl = rtrim($old['base_url'], '/');
    if (!preg_match('#^https?://[a-z0-9.-]+(:\d+)?$#i', $baseUrl)) {
        inst_page_db_form($state, 'The shop web address must look like https://skyfragrances.com (no folder and no slash at the end).', $old);
    }
    $enteredHost = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
    if ($enteredHost === INSTALL_CANONICAL_HOST || $enteredHost === 'www.' . INSTALL_CANONICAL_HOST) {
        $baseUrl = 'https://' . INSTALL_CANONICAL_HOST;
    }
    try {
        $pdo = inst_pdo($db);
    } catch (Throwable $e) {
        inst_page_db_form($state, 'Could not connect to the database. Check the database name, username and password in hPanel → Databases. The server said: ' . $e->getMessage(), $old);
    }
    $version = inst_server_version_ok($pdo);
    if (!$version['ok']) {
        inst_page_db_form($state, 'This database server is ' . $version['version'] . '. The shop needs MySQL 8.0 or MariaDB 10.4 or newer.', $old);
    }
    try {
        $pdo->exec('CREATE TABLE sf_install_probe (id INT UNSIGNED NOT NULL) ENGINE=InnoDB');
        $pdo->exec('DROP TABLE sf_install_probe');
    } catch (Throwable $e) {
        inst_page_db_form($state, 'The connection works, but this database user is not allowed to create tables. In hPanel → Databases give the user ALL PRIVILEGES on this database. The server said: ' . $e->getMessage(), $old);
    }
    $existing = $state['config'] ?? [];
    $security = $existing['security'] ?? [];
    $host = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
    $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.test') || str_ends_with($host, '.local');
    $smtpPass = (string) ($_POST['smtp_pass'] ?? '');
    if ($old['smtp_user'] === '' && $smtpPass === '') {
        $old['smtp_host'] = '';
    }
    $smtpPort = (int) ($old['smtp_port'] === '' ? 587 : $old['smtp_port']);
    $config = [
        'env' => $isLocal ? 'development' : 'production',
        'base_url' => $baseUrl,
        'db' => $db,
        'smtp' => [
            'host' => $old['smtp_host'],
            'port' => $smtpPort,
            'secure' => $smtpPort === 465 ? 'ssl' : 'tls',
            'user' => $old['smtp_user'],
            'pass' => $smtpPass,
            'from_name' => $old['smtp_from_name'] === '' ? 'Sky Fragrances' : $old['smtp_from_name'],
        ],
        'security' => [
            'app_key' => (string) ($security['app_key'] ?? inst_random_hex(32)),
            'cron_key' => (string) ($security['cron_key'] ?? inst_random_hex(16)),
        ],
        'trusted_proxies' => $existing['trusted_proxies'] ?? inst_trusted_proxies(),
    ];
    $source = inst_config_source($config);
    if (inst_write_atomic(INSTALL_CONFIG, $source, 0600)) {
        inst_redirect('step=3');
    }
    $body = '<h1>Create config.php by hand</h1><div class="box bad"><p>The connection works, but this server does not let the installer write files in the website folder.</p><p>In hPanel → File Manager → public_html create a new file called <strong>config.php</strong>, paste the text below into it, save, then click Continue.</p></div>'
        . '<textarea rows="24" readonly onclick="this.select()">' . inst_e($source) . '</textarea>'
        . '<p><a class="btn" href="install.php?step=3">I have created config.php — continue</a></p>';
    inst_page('Create config.php', $body, 2);
}

function inst_handle_install(array $state): never
{
    if (!in_array($state['stage'], ['resume', 'partial'], true) || inst_base_path() !== '') {
        inst_redirect('step=1');
    }
    $old = [];
    foreach (['admin_username', 'admin_email', 'store_name', 'whatsapp', 'seed'] as $k) {
        $old[$k] = inst_post($k);
    }
    $password = (string) ($_POST['admin_password'] ?? '');
    $password2 = (string) ($_POST['admin_password2'] ?? '');
    if (!preg_match('/^[A-Za-z0-9._-]{3,64}$/', $old['admin_username'])) {
        inst_page_admin_form($state, 'The username may only contain letters, numbers, dots, dashes and underscores (3 to 64 characters).', $old);
    }
    if (filter_var($old['admin_email'], FILTER_VALIDATE_EMAIL) === false) {
        inst_page_admin_form($state, 'Please enter a valid email address.', $old);
    }
    if (mb_strlen($password) < INSTALL_MIN_PASSWORD) {
        inst_page_admin_form($state, 'The password must be at least ' . INSTALL_MIN_PASSWORD . ' characters long.', $old);
    }
    if ($password !== $password2) {
        inst_page_admin_form($state, 'The two passwords do not match.', $old);
    }
    if ($old['store_name'] === '') {
        inst_page_admin_form($state, 'Please enter the store name.', $old);
    }
    if ($state['stage'] === 'partial' && inst_post('replace_partial') !== '1') {
        inst_page_admin_form($state, 'Please tick the box confirming that the unfinished tables may be replaced.', $old);
    }
    if (!is_file(INSTALL_SCHEMA)) {
        inst_page_admin_form($state, 'The database blueprint db/schema.sql is missing. Upload the complete ZIP again.', $old);
    }
    $config = $state['config'];
    try {
        $pdo = inst_pdo($config['db']);
    } catch (Throwable $e) {
        inst_redirect('step=2');
    }
    $schema = inst_run_sql_file($pdo, INSTALL_SCHEMA);
    if (!$schema['ok']) {
        inst_page_admin_form($state, 'Creating the database tables stopped at statement ' . $schema['index'] . ' of ' . $schema['count'] . '. The database said: ' . $schema['error'] . ' — Statement: ' . $schema['statement'] . ' — Fix the cause (or send this message to your developer), then reload and try again.', $old);
    }
    $results = [['pass', 'Database tables', $schema['count'] . ' statements ran without errors']];
    $seed = $old['seed'] === '1';
    if ($seed && is_file(INSTALL_SEED)) {
        $seeded = inst_run_sql_file($pdo, INSTALL_SEED);
        if (!$seeded['ok']) {
            inst_page_admin_form(inst_state(), 'The tables were created, but adding the sample data stopped at statement ' . $seeded['index'] . ' of ' . $seeded['count'] . '. The database said: ' . $seeded['error'] . ' — Statement: ' . $seeded['statement'] . ' — Reload and try again; tick the confirmation box to replace the unfinished tables.', $old);
        }
        $results[] = ['pass', 'Sample data', $seeded['count'] . ' statements ran without errors'];
    } else {
        $results[] = ['pass', 'Sample data', $seed ? 'skipped — db/seed.sql is missing' : 'skipped by your choice — the shop starts empty'];
    }
    $now = inst_now();
    if (!inst_write_atomic(INSTALL_LOCK, $now . "\n", 0644)) {
        inst_page_admin_form(inst_state(), 'The tables were created, but the lock file storage/.installed could not be written. In File Manager make sure the storage folder has permissions 755, then reload and try again (tick the confirmation box).', $old);
    }
    $results[] = ['pass', 'Installation lock', 'storage/.installed written — this installer will not run again'];
    $insert = $pdo->prepare('INSERT INTO admin_users (username, email, password_hash, display_name, is_active, password_changed_at, created_at, updated_at) VALUES (:u, :e, :p, :d, 1, :t1, :t2, :t3)');
    $insert->execute([':u' => $old['admin_username'], ':e' => $old['admin_email'], ':p' => password_hash($password, PASSWORD_DEFAULT), ':d' => $old['admin_username'], ':t1' => $now, ':t2' => $now, ':t3' => $now]);
    $results[] = ['pass', 'Admin account', 'created for ' . $old['admin_username']];
    $host = strtolower((string) parse_url((string) $config['base_url'], PHP_URL_HOST));
    $basePath = rtrim((string) parse_url((string) $config['base_url'], PHP_URL_PATH), '/');
    $indexable = ($host === INSTALL_CANONICAL_HOST || $host === 'www.' . INSTALL_CANONICAL_HOST) && $basePath === '';
    inst_setting_upsert($pdo, 'store_name', $old['store_name'], 'store');
    if (trim((string) inst_setting($pdo, 'contact_email')) === '') {
        inst_setting_upsert($pdo, 'contact_email', $old['admin_email'], 'contact');
    }
    if (trim((string) inst_setting($pdo, 'order_notify_email')) === '') {
        inst_setting_upsert($pdo, 'order_notify_email', $old['admin_email'], 'contact');
    }
    if ($old['whatsapp'] !== '') {
        inst_setting_upsert($pdo, 'whatsapp', inst_phone_normalize($old['whatsapp']), 'contact');
    }
    inst_setting_upsert($pdo, 'images_webp_enabled', function_exists('imagewebp') ? '1' : '0', 'advanced');
    inst_setting_upsert($pdo, 'site_indexable', $indexable ? '1' : '0', 'advanced');
    inst_setting_upsert($pdo, 'maintenance_bypass', inst_random_hex(8), 'advanced');
    inst_setting_upsert($pdo, 'trusted_proxies', implode(',', $config['trusted_proxies'] ?? []), 'advanced');
    $results[] = $indexable
        ? ['pass', 'Search engines', 'allowed to index the shop']
        : ['warn', 'Search engines', 'told not to index this copy because it is not at https://' . INSTALL_CANONICAL_HOST . '. When the shop moves to its real address, switch site_indexable on in Admin → Settings → Advanced.'];
    inst_setting_upsert($pdo, 'install_completed_at', $now, 'advanced');
    foreach (inst_prepare_dirs() as $row) {
        $results[] = $row;
    }
    inst_write_robots((string) $config['base_url']);
    $results[] = ($config['trusted_proxies'] ?? []) === []
        ? ['pass', 'Client IP detection', 'visitors connect directly; their own addresses are used for rate limits']
        : ['warn', 'Client IP detection', 'requests arrive through a proxy at ' . implode(', ', $config['trusted_proxies']) . '; it has been recorded as trusted so visitors are told apart correctly.'];
    $https = inst_https_permanent($host);
    inst_setting_upsert($pdo, 'https_permanent', $https[0] === 'pass' ? '1' : '0', 'advanced');
    $results[] = $https;
    $results[] = @chmod(INSTALL_CONFIG, 0400)
        ? ['pass', 'config.php locked', 'read-only (0400); nothing on the site ever rewrites it']
        : ['warn', 'config.php locked', 'could not be made read-only. In File Manager set its permissions to 0400.'];
    $checks = inst_run_security_checks();
    inst_install_key_remove();
    $_SESSION['inst_results'] = $results;
    $_SESSION['inst_checks'] = $checks;
    $_SESSION['inst_done'] = 1;
    if (($config['env'] ?? '') !== 'development' && @unlink(__FILE__)) {
        inst_page_finish($results, '', $checks, true);
    }
    inst_redirect('step=4');
}

function inst_https_permanent(string $host): array
{
    require_once INSTALL_ROOT . '/app/lib/https.php';
    $host = preg_replace('/:\d+$/', '', strtolower($host)) ?? '';
    $local = in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.test') || str_ends_with($host, '.local');
    if ($local || PHP_SAPI === 'cli-server') {
        return ['warn', 'HTTPS redirect', 'left temporary (302) because this is a local copy.'];
    }
    if (https_redirect_is_permanent(INSTALL_ROOT)) {
        return ['pass', 'HTTPS redirect', 'already permanent (301).'];
    }
    if (!inst_host_points_here()) {
        return ['warn', 'HTTPS redirect', 'left temporary (302) because ' . $host . ' does not point at this server yet. Once the padlock shows, press Settings → Advanced → Make HTTPS permanent.'];
    }
    $outcome = https_make_permanent_if_ready(INSTALL_ROOT, $host);
    if ($outcome['flipped']) {
        return ['pass', 'HTTPS redirect', 'HTTPS confirmed with a valid certificate; the redirect is now permanent (301).'];
    }
    return ['warn', 'HTTPS redirect', 'HTTPS is not confirmed yet (' . $outcome['detail'] . '). The redirect stays temporary (302); press Settings → Advanced → Make HTTPS permanent once the padlock shows.'];
}

function inst_run_security_checks(): array
{
    if (!inst_host_points_here()) {
        $base = inst_request_base_url();
        return [['warn', 'Security checks', 'skipped because ' . inst_request_host() . ' does not point at this server yet. Once the domain is connected, open ' . $base . '/db/schema.sql, ' . $base . '/storage/.htaccess and ' . $base . '/app/bootstrap.php on your phone: each must show an error page, never the file.']];
    }
    $rows = [inst_selftest_uploads()];
    foreach (inst_selftest_denied() as $row) {
        $rows[] = $row;
    }
    return $rows;
}

set_exception_handler(function (Throwable $e): void {
    inst_page('Something went wrong', '<div class="box bad"><h2>The installer hit an unexpected problem</h2><p>Nothing dangerous happened, but this step did not finish. The server said:</p><pre>' . inst_e(get_class($e) . ': ' . $e->getMessage()) . '</pre><p>Reload the page to try again. If it keeps happening, send the message above to your developer.</p><p><a class="btn" href="install.php">Back to the installer</a></p></div>', 0);
});
if (isset($_GET['probe']) && is_string($_GET['probe'])) {
    inst_answer_probe($_GET['probe']);
}
inst_session_start();
$state = inst_state();
$method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$action = $method === 'POST' ? inst_post('action') : '';
if ($method === 'POST') {
    inst_csrf_check();
}
$finished = in_array($state['stage'], ['installed', 'locked'], true);
if ($action === 'self_delete' && $finished) {
    inst_handle_self_delete();
}
if ($action === 'check_gone' && $finished) {
    inst_handle_check_gone();
}
if ($action === 'checks' && $finished && !empty($_SESSION['inst_done'])) {
    inst_handle_checks();
}
if ($finished) {
    inst_install_key_remove();
    if (!empty($_SESSION['inst_done'])) {
        inst_page_finish($_SESSION['inst_results'] ?? []);
    }
    inst_page_refuse($state);
}
if (in_array($action, ['db_save', 'install', 'test_email'], true) && !inst_install_key_ok()) {
    $keyOld = [];
    foreach ($_POST as $k => $v) {
        if (is_string($v) && !in_array($k, ['db_pass', 'smtp_pass', 'admin_password', 'admin_password2', '_token', 'install_key', 'action'], true)) {
            $keyOld[$k] = $v;
        }
    }
    $action === 'db_save'
        ? inst_page_db_form($state, 'The install key did not match. Copy it again from storage/.install-key.', $keyOld)
        : inst_page_admin_form($state, 'The install key did not match. Copy it again from storage/.install-key.', $keyOld);
}
if ($action === 'test_email') {
    inst_handle_test_email($state);
}
if ($action === 'db_save') {
    inst_handle_db_save($state);
}
if ($action === 'install') {
    inst_handle_install($state);
}
$step = max(1, min(4, (int) ($_GET['step'] ?? 1)));
if ($step === 2) {
    if ($state['stage'] === 'config_bad') {
        inst_page_config_bad($state);
    }
    inst_page_db_form($state);
}
if ($step === 3) {
    if (in_array($state['stage'], ['resume', 'partial'], true)) {
        inst_page_admin_form($state);
    }
    inst_redirect($state['stage'] === 'occupied' ? 'step=1' : 'step=2');
}
inst_page_requirements($state);
