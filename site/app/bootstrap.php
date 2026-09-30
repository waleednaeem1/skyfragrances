<?php
defined('SKYFR') || exit;

define('APP_ROOT', dirname(__DIR__));

const ORDER_STATUSES = ['pending', 'confirmed', 'packing', 'shipped', 'delivered', 'cancelled'];
const PAYMENT_METHODS = ['cod', 'bank', 'jazzcash', 'easypaisa'];
const PAYMENT_STATUSES = ['unpaid', 'awaiting_verification', 'paid', 'failed', 'refunded'];
const REVIEW_STATUSES = ['pending', 'approved', 'rejected'];
const GENDERS = ['him', 'her', 'unisex'];
const COUPON_TYPES = ['percent', 'fixed'];

function config(string $dotKey, mixed $default = null): mixed
{
    $value = $GLOBALS['config'] ?? null;
    foreach (explode('.', $dotKey) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function now_karachi(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi')))->format('Y-m-d H:i:s');
}

function log_write(string $level, string $message, array $context = []): void
{
    $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
    $rank = $levels[$level] ?? 3;
    $threshold = (defined('APP_ENV') && APP_ENV === 'production') ? 2 : 0;
    if ($rank < $threshold) {
        return;
    }
    $dir = APP_ROOT . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $stamp = (new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi')))->format('Y-m-d H:i:s P');
    $line = '[' . $stamp . '] ' . $level . ' ' . str_replace(["\r", "\n"], ' ', $message);
    if ($context !== []) {
        $json = @json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        $line .= ' | ' . ($json === false ? '{}' : $json);
    }
    if (strlen($line) > 4096) {
        $line = substr($line, 0, 4093) . '...';
    }
    @file_put_contents($dir . '/app-' . date('Y-m') . '.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function bootstrap_render_500(string $incidentId, ?Throwable $throwable = null): void
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
    }
    $safeId = htmlspecialchars($incidentId, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Something went wrong - Sky Fragrances</title>'
        . '<style>body{margin:0;background:#0A0A0A;color:#F5F0E8;font-family:Georgia,serif;display:flex;min-height:100vh;align-items:center;justify-content:center;text-align:center;padding:2rem}h1{font-weight:300;font-size:2rem;color:#D4B084;margin:0 0 1rem}p{max-width:34rem;line-height:1.6}code{color:#D4B084}pre{text-align:left;overflow:auto;background:#151515;padding:1rem;font-size:.8rem;color:#ccc}</style></head><body><div>'
        . '<h1>Something went wrong</h1><p>We could not complete that request. Please try again in a moment or message us on WhatsApp.</p><p>Reference: <code>' . $safeId . '</code></p>';
    if ($throwable !== null && defined('APP_ENV') && APP_ENV !== 'production') {
        echo '<pre>' . htmlspecialchars(get_class($throwable) . ': ' . $throwable->getMessage() . "\n" . $throwable->getFile() . ':' . $throwable->getLine() . "\n\n" . $throwable->getTraceAsString(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . '</pre>';
    }
    echo '</div></body></html>';
}

function bootstrap_handle_throwable(Throwable $throwable): void
{
    $incidentId = bin2hex(random_bytes(4));
    log_write('error', 'incident=' . $incidentId . ' ' . get_class($throwable) . ': ' . $throwable->getMessage(), [
        'file' => $throwable->getFile(),
        'line' => $throwable->getLine(),
        'path' => $_SERVER['REQUEST_URI'] ?? '',
        'trace' => substr($throwable->getTraceAsString(), 0, 2000),
    ]);
    bootstrap_render_500($incidentId, $throwable);
    exit;
}

function bootstrap_handle_shutdown(): void
{
    $error = error_get_last();
    $fatalTypes = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;
    if ($error !== null && ($error['type'] & $fatalTypes)) {
        $incidentId = bin2hex(random_bytes(4));
        log_write('error', 'incident=' . $incidentId . ' fatal: ' . $error['message'], [
            'file' => $error['file'],
            'line' => $error['line'],
            'path' => $_SERVER['REQUEST_URI'] ?? '',
        ]);
        bootstrap_render_500($incidentId);
        return;
    }
    $buffered = array_sum(array_column(ob_get_status(true), 'buffer_used'));
    if (PHP_SAPI !== 'cli' && !headers_sent() && http_response_code() === 200 && $buffered === 0) {
        $incidentId = bin2hex(random_bytes(4));
        log_write('error', 'incident=' . $incidentId . ' controller returned without rendering', ['path' => $_SERVER['REQUEST_URI'] ?? '']);
        bootstrap_render_500($incidentId);
    }
}

function bootstrap_render_unconfigured(): void
{
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    header('Retry-After: 600');
    header('Cache-Control: no-store');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sky Fragrances is not configured yet</title>'
        . '<style>body{margin:0;background:#0A0A0A;color:#F5F0E8;font-family:Georgia,serif;display:flex;min-height:100vh;align-items:center;justify-content:center;text-align:center;padding:2rem}h1{font-weight:300;font-size:2rem;color:#D4B084;margin:0 0 1rem}code{color:#D4B084}</style></head><body><div>'
        . '<h1>Sky Fragrances is not configured yet.</h1><p>Run <code>install.php</code>.</p></div></body></html>';
    exit;
}

if (!is_file(APP_ROOT . '/config.php')) {
    bootstrap_render_unconfigured();
}
$config = require APP_ROOT . '/config.php';
if (!is_array($config) || !isset($config['db']) || !is_array($config['db'])) {
    bootstrap_render_unconfigured();
}

define('APP_ENV', ($config['env'] ?? 'production') === 'development' ? 'development' : 'production');
define('BASE_PATH', '');

error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'production' ? '0' : '1');
ini_set('display_startup_errors', APP_ENV === 'production' ? '0' : '1');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/storage/logs/php-error.log');
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
        log_write('warning', 'deprecated: ' . $message, ['file' => $file, 'line' => $line]);
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler('bootstrap_handle_throwable');
register_shutdown_function('bootstrap_handle_shutdown');
ob_start();

date_default_timezone_set('Asia/Karachi');
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'C');

function bootstrap_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE || headers_sent()) {
        return;
    }
    $ownsSession = in_array(session_name(), ['', 'PHPSESSID'], true);
    if ($ownsSession) {
        session_name('SFSHOP');
    }
    $cookie = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => defined('SKYFR_ADMIN') ? 0 : 259200,
        'path' => $ownsSession ? BASE_PATH . '/' : $cookie['path'],
        'domain' => '',
        'secure' => APP_ENV === 'production',
        'httponly' => true,
        'samesite' => $ownsSession ? 'Lax' : ($cookie['samesite'] ?: 'Lax'),
    ]);
    $sessionDir = APP_ROOT . '/storage/sessions/' . (defined('SKYFR_ADMIN') ? 'admin' : 'shop');
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0700, true);
    }
    session_save_path($sessionDir);
    ini_set('session.gc_maxlifetime', defined('SKYFR_ADMIN') ? '43200' : '259200');
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_start();
    if (!isset($_SESSION['born'])) {
        $_SESSION['born'] = time();
    }
    if (empty($_SESSION['_csrf']) && session_name() === 'SFSHOP' && (time() - (int) $_SESSION['born']) > 1800) {
        session_regenerate();
    }
    csrf_token();
}

function bootstrap_session_wanted(): bool
{
    if (defined('SKYFR_ADMIN') || isset($_COOKIE['SFSHOP'])) {
        return true;
    }
    if (defined('SKYFR_CRON') || PHP_SAPI === 'cli') {
        return false;
    }
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
        return true;
    }
    $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? '/'));
    $cut = strpos($uri, '?');
    $path = $cut === false ? $uri : substr($uri, 0, $cut);
    foreach (['/api/cart', '/api/session', '/checkout', '/track', '/cart', '/order/'] as $prefix) {
        if (str_starts_with($path, $prefix)) {
            return true;
        }
    }
    return false;
}

function bootstrap_define_site_url(bool $settingsLoaded): void
{
    if (defined('SITE_URL')) {
        return;
    }
    define('SITE_URL', $settingsLoaded ? settings_site_url() : rtrim((string) config('base_url', ''), '/'));
}

foreach (['text', 'money', 'url', 'db', 'settings', 'session', 'csrf', 'flash', 'request', 'response', 'ratelimit', 'view', 'upload', 'image', 'mail', 'auth', 'https', 'maintenance', 'housekeeping'] as $bootstrapLib) {
    require_once APP_ROOT . '/app/lib/' . $bootstrapLib . '.php';
}
unset($bootstrapLib);

if (PHP_SAPI !== 'cli') {
    response_security_headers(defined('SKYFR_ADMIN'));
}

$bootstrapSettingsLoaded = false;
if (maintenance_flag_present() && !maintenance_exempt()) {
    try {
        settings_load();
        $bootstrapSettingsLoaded = true;
    } catch (Throwable $bootstrapSettingsError) {
        log_write('warning', 'maintenance flag set and settings unavailable', ['error' => $bootstrapSettingsError->getMessage()]);
    }
    bootstrap_define_site_url($bootstrapSettingsLoaded);
    maintenance_gate();
}
if (!$bootstrapSettingsLoaded) {
    settings_load();
}
bootstrap_define_site_url(true);
unset($bootstrapSettingsLoaded, $bootstrapSettingsError);

maintenance_gate();

if (bootstrap_session_wanted()) {
    bootstrap_session_start();
}
