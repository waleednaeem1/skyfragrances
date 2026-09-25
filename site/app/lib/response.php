<?php
defined('SKYFR') || exit;

function response_clear_buffers(): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

function http_status(int $code): void
{
    http_response_code($code);
}

function header_no_store(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
}

function redirect(string $path, int $status = 303): never
{
    $target = preg_match('#^https?://#i', $path) ? $path : url($path);
    response_clear_buffers();
    http_response_code($status);
    header('Location: ' . $target);
    exit;
}

function json(array $payload, int $status = 200): never
{
    response_clear_buffers();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header_no_store();
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    exit;
}

function abort_titles(int $status): array
{
    return match (true) {
        $status === 405 => ['Method Not Allowed', 'That request method is not allowed here.'],
        $status === 419 => ['Form Expired', 'This form expired. Please go back and try again.'],
        $status === 429 => ['Too Many Requests', 'Please wait a moment and try again.'],
        $status >= 500  => ['Something Went Wrong', 'We hit a problem on our side. Please try again in a moment.'],
        default         => ['Page Not Found', 'The page you are looking for does not exist or has moved.'],
    };
}

function abort(int $status = 404, string $message = ''): never
{
    [$title, $defaultMessage] = abort_titles($status);
    $message = $message !== '' ? $message : $defaultMessage;
    if (request_is_api() || request_is_ajax()) {
        json(['ok' => false, 'error' => strtolower(str_replace(' ', '_', $title)), 'message' => $message], $status);
    }
    response_clear_buffers();
    ob_start();
    http_response_code($status);
    header('X-Robots-Tag: noindex, follow');
    $head = [
        'title' => $title . ' | Sky Fragrances',
        'meta_description' => '',
        'canonical' => '',
        'robots' => 'noindex,follow',
        'body_class' => 'error error-' . $status,
        'og' => [],
        'jsonld' => [],
    ];
    $data = ['status' => $status, 'heading' => $title, 'message' => $message];
    $isAdmin = defined('SKYFR_ADMIN');
    $viewFile = $isAdmin ? APP_ROOT . '/admin/views/404.php' : APP_ROOT . '/app/views/404.php';
    $layoutFile = $isAdmin ? APP_ROOT . '/admin/views/layout.php' : APP_ROOT . '/app/views/layout.php';
    if (is_file($viewFile) && is_file($layoutFile)) {
        $isAdmin ? render_admin('404.php', $data, $head) : render('404.php', $data, $head);
    }
    abort_plain($status, $title, $message);
}

function abort_plain(int $status, string $title, string $message): never
{
    response_clear_buffers();
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,follow"><title>' . $safeTitle . ' | Sky Fragrances</title></head><body style="font-family:Georgia,serif;background:#0a0a0a;color:#f5f0e8;text-align:center;padding:4rem 1rem"><h1 style="color:#d4b084;font-weight:300">' . $safeTitle . '</h1><p>' . $safeMessage . '</p></body></html>';
    exit;
}

const CSP_BOOTSTRAP_SCRIPT_HASH = 'sha256-/x7W7R75k8Roq0WaVRQX9blP4OufE5xbAdzklGxsgpw=';

function response_csp(): string
{
    $directives = [
        "default-src 'self'",
        "base-uri 'self'",
        "object-src 'none'",
        "frame-ancestors 'none'",
        "form-action 'self'",
        "script-src 'self' '" . CSP_BOOTSTRAP_SCRIPT_HASH . "'",
        "style-src 'self' 'unsafe-inline'",
        "font-src 'self'",
        "img-src 'self' data:",
        "connect-src 'self'",
        "frame-src 'none'",
    ];
    if (APP_ENV === 'production') {
        $directives[] = 'upgrade-insecure-requests';
    }
    return implode('; ', $directives);
}

function response_security_headers(bool $admin = false): void
{
    if (headers_sent()) {
        return;
    }
    header_remove('X-Powered-By');
    header('Content-Security-Policy: ' . response_csp());
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: ' . ($admin ? 'same-origin' : 'strict-origin-when-cross-origin'));
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), interest-cohort=()');
}
