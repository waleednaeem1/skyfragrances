<?php
define('SKYFR', 1);
define('SKYFR_ADMIN', 1);

$adminConfigFile = dirname(__DIR__) . '/config.php';
$adminSecure = false;
if (is_file($adminConfigFile)) {
    $adminConfig = require $adminConfigFile;
    $adminSecure = ($adminConfig['env'] ?? 'production') === 'production';
    unset($adminConfig);
}
session_name('SFADMIN');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/admin',
    'domain' => '',
    'secure' => $adminSecure,
    'httponly' => true,
    'samesite' => 'Strict',
]);

require dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_ROOT . '/app/router.php';

header('X-Robots-Tag: noindex, nofollow');
header_no_store();

function admin_after_response(): void
{
    $fatal = error_get_last();
    if ($fatal !== null && ($fatal['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) {
        return;
    }
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        flush();
    }
    try {
        mail_outbox_drain();
        housekeeping_maybe_run();
    } catch (Throwable $e) {
        log_write('warning', 'admin after-response hook failed', ['error' => $e->getMessage()]);
    }
}
register_shutdown_function('admin_after_response');

$method = request_method();
$rawPath = request_path_raw();
$path = request_path();
if (request_path_is_unsafe($rawPath)) {
    abort(404);
}

$adminPath = preg_replace('#^/admin(?=/|$)#', '', $path) ?? '';
$adminPath = $adminPath === '' ? '/' : $adminPath;
$isPublicAdminPath = in_array($adminPath, ['/login', '/logout'], true);

if (!$isPublicAdminPath) {
    auth_require();
}

if ($method === 'POST') {
    if (request_origin_is_foreign()) {
        log_write('warning', 'Admin POST rejected: foreign origin', ['path' => $adminPath]);
        abort(403, 'Cross-site requests are not allowed.');
    }
    $tooLarge = request_body_too_large();
    if ($tooLarge !== null) {
        $tooLargeMessage = 'Your upload was too large. The limit is ' . upload_human_size($tooLarge) . ' per save. Add fewer photos at a time.';
        if (request_is_ajax()) {
            json(['ok' => false, 'error' => 'too_large', 'message' => $tooLargeMessage], 413);
        }
        flash('error', $tooLargeMessage);
        redirect($isPublicAdminPath ? '/admin/login' : '/admin' . ($adminPath === '/' ? '' : $adminPath), 303);
    }
    try {
        csrf_check(request_csrf_token_sent());
    } catch (Throwable $csrfError) {
        log_write('warning', 'Admin CSRF failure', ['path' => $adminPath]);
        if (request_is_ajax()) {
            json(['ok' => false, 'error' => 'csrf'], 419);
        }
        flash('error', 'This form expired. Please go back and try again.');
        redirect($isPublicAdminPath ? '/admin/login' : '/admin' . ($adminPath === '/' ? '' : $adminPath), 303);
    }
}

$compiled = router_compile(require APP_ROOT . '/app/routes-admin.php');
$match = router_match($compiled, $method, $adminPath);
$route = $match['route'];
$params = $match['params'];
$head = router_head_defaults($route ?? []);
$head['robots'] = 'noindex,nofollow';

if ($match['status'] === 405) {
    header('Allow: ' . implode(', ', $match['allow']));
    http_status(405);
} elseif ($match['status'] === 404) {
    http_status(404);
}

if ($route === null) {
    abort($match['status'] === 405 ? 405 : 404);
}

$controllerFile = APP_ROOT . '/admin/controllers/' . $route['controller'];
if (!is_file($controllerFile)) {
    log_write('error', 'Admin controller file missing', ['controller' => $route['controller'], 'path' => $adminPath]);
    abort(500);
}

require $controllerFile;

log_write('error', 'Admin controller returned without rendering', ['controller' => $route['controller'], 'path' => $adminPath]);
abort(500);
