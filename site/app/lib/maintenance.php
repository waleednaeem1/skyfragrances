<?php
defined('SKYFR') || exit;

const MAINTENANCE_FLAG_FILE = '/storage/MAINTENANCE';
const MAINTENANCE_BYPASS_COOKIE = 'sf_bypass';
const MAINTENANCE_BYPASS_SECONDS = 43200;

function maintenance_flag_present(): bool
{
    return is_file(APP_ROOT . MAINTENANCE_FLAG_FILE);
}

function maintenance_setting_on(): bool
{
    return isset($GLOBALS['skyfr_settings']) && setting_bool('maintenance_mode', false);
}

function maintenance_active(): bool
{
    return maintenance_flag_present() || maintenance_setting_on();
}

function maintenance_exempt(): bool
{
    return defined('SKYFR_ADMIN') || defined('SKYFR_CRON') || PHP_SAPI === 'cli';
}

function maintenance_bypass_secret(): string
{
    return isset($GLOBALS['skyfr_settings']) ? trim((string) setting('maintenance_bypass', '')) : '';
}

function maintenance_bypass_cookie_value(): string
{
    return hash_hmac('sha256', 'maintenance-bypass', config('security.app_key', '') . '|' . maintenance_bypass_secret());
}

function maintenance_bypassed(): bool
{
    $cookie = (string) ($_COOKIE[MAINTENANCE_BYPASS_COOKIE] ?? '');
    return maintenance_bypass_secret() !== '' && $cookie !== '' && hash_equals(maintenance_bypass_cookie_value(), $cookie);
}

function maintenance_bypass_attempt(): ?string
{
    if (request_method() !== 'POST' || !isset($_POST['bypass_key'])) {
        return null;
    }
    session_ensure();
    try {
        csrf_check(request_csrf_token_sent());
    } catch (Throwable $e) {
        return 'This form expired. Please try again.';
    }
    $secret = maintenance_bypass_secret();
    $sent = trim((string) $_POST['bypass_key']);
    if ($secret === '' || $sent === '' || !hash_equals($secret, $sent)) {
        log_write('warning', 'maintenance bypass refused', ['ip_hash' => request_ip_hash()]);
        return 'That key is not right.';
    }
    setcookie(MAINTENANCE_BYPASS_COOKIE, maintenance_bypass_cookie_value(), [
        'expires' => time() + MAINTENANCE_BYPASS_SECONDS,
        'path' => BASE_PATH . '/',
        'domain' => '',
        'secure' => APP_ENV === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    redirect(request_path_raw(), 303);
}

function maintenance_gate(): void
{
    if (maintenance_exempt() || !maintenance_active() || maintenance_bypassed()) {
        return;
    }
    $error = maintenance_bypass_attempt();
    maintenance_render($error);
}

function maintenance_render(?string $error = null): never
{
    if (request_is_api() || request_is_ajax()) {
        response_clear_buffers();
        http_response_code(503);
        header('Retry-After: 3600');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'maintenance', 'message' => 'The shop is briefly closed for maintenance.']);
        exit;
    }
    $loaded = isset($GLOBALS['skyfr_settings']);
    $store = $loaded ? (string) setting('store_name', 'Sky Fragrances') : 'Sky Fragrances';
    $message = $loaded ? (string) setting('maintenance_message', '') : '';
    if ($message === '') {
        $message = 'We are refreshing the store and will be back shortly.';
    }
    $whatsapp = $loaded ? preg_replace('/\D+/', '', (string) setting('whatsapp', '')) : '';
    $form = '';
    if (maintenance_bypass_secret() !== '') {
        $form = '<details><summary>Shop owner</summary><form method="post" action="' . e(request_path_raw()) . '">' . csrf_field()
            . ($error !== null ? '<p class="err">' . e($error) . '</p>' : '')
            . '<p><input type="password" name="bypass_key" placeholder="Bypass key" autocomplete="off"> <button type="submit">Preview the shop</button></p></form></details>';
    }
    $html = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . e($store) . ' — back shortly</title>'
        . '<style>body{margin:0;background:#0A0A0A;color:#F5F0E8;font-family:Georgia,serif;display:flex;min-height:100vh;align-items:center;justify-content:center;text-align:center;padding:2rem}h1{font-weight:300;font-size:2rem;color:#D4B084;margin:0 0 1rem}p{max-width:34rem;line-height:1.6;margin:0 auto 1rem}a.wa{display:inline-block;margin-top:.5rem;padding:.75rem 1.5rem;border:1px solid #D4B084;color:#D4B084;text-decoration:none;letter-spacing:.1em;text-transform:uppercase;font-size:.8rem}details{margin-top:2.5rem;color:#8a8078;font-size:.85rem}input{padding:.6rem;background:#141210;border:1px solid #3a342e;color:#F5F0E8;font:inherit}button{padding:.6rem 1rem;background:#D4B084;border:0;color:#0A0A0A;font:inherit;cursor:pointer}.err{color:#ff6b61}</style></head><body><div>'
        . '<h1>' . e($store) . '</h1><p>' . e($message) . '</p>'
        . ($whatsapp !== '' ? '<a class="wa" href="https://wa.me/' . e($whatsapp) . '">Message us on WhatsApp</a>' : '')
        . $form . '</div></body></html>';
    response_clear_buffers();
    http_response_code(503);
    header('Retry-After: 3600');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}
