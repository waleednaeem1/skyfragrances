<?php
define('SKYFR', 1);
require __DIR__ . '/app/bootstrap.php';
require_once APP_ROOT . '/app/router.php';

$method = request_method();
$rawPath = request_path_raw();
$path = request_path();

if (request_path_is_unsafe($rawPath)) {
    abort(404);
}

if ($method === 'GET' && $path === '/__rewrite-probe') {
    http_status(200);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    echo 'SKYFR-REWRITE-OK';
    exit;
}

if (($_SERVER['REDIRECT_STATUS'] ?? '') === '404' && !str_starts_with($path, '/api/')) {
    $rawPath = $path = '/__missing__';
}

if ($rawPath !== $path && $method === 'GET') {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    redirect($path . ($query !== '' ? '?' . $query : ''), 301);
}

$compiled = router_compile(require APP_ROOT . '/app/routes.php');
$match = router_match($compiled, $method, $path);
$route = $match['route'];
$params = $match['params'];
$head = router_head_defaults($route);

if ($match['status'] === 405) {
    header('Allow: ' . implode(', ', $match['allow']));
    http_status(405);
} elseif ($match['status'] === 404) {
    http_status(404);
}

if ($method === 'POST' && $route['name'] !== 'notfound') {
    $tooLarge = request_body_too_large();
    if ($tooLarge !== null) {
        $tooLargeMessage = 'Your upload was too large. The limit is ' . upload_human_size($tooLarge) . ' per submission.';
        if (request_is_api()) {
            json(['ok' => false, 'error' => 'too_large', 'message' => $tooLargeMessage], 413);
        }
        flash('error', $tooLargeMessage);
        redirect(request_return_path($path), 303);
    }
    try {
        csrf_check(request_csrf_token_sent());
    } catch (Throwable $csrfError) {
        if (request_is_api()) {
            json(['ok' => false, 'error' => 'csrf'], 419);
        }
        flash('error', 'Your session expired. Please try again.');
        redirect(request_return_path($path), 303);
    }
}

$controllerFile = APP_ROOT . '/app/controllers/' . $route['controller'];
if (!is_file($controllerFile)) {
    log_write('error', 'Controller file missing', ['controller' => $route['controller'], 'path' => $path]);
    abort(500);
}

require $controllerFile;

log_write('error', 'Controller returned without rendering', ['controller' => $route['controller'], 'path' => $path]);
abort(500);
