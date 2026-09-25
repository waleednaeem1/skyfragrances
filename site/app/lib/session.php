<?php
defined('SKYFR') || exit;

function session_ensure(): bool
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    if (function_exists('bootstrap_session_start')) {
        bootstrap_session_start();
    }
    return session_status() === PHP_SESSION_ACTIVE;
}

function session_regenerate(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    session_regenerate_id(true);
    csrf_rotate();
    $_SESSION['born'] = time();
}

function session_destroy_fully(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $name = session_name();
    $params = session_get_cookie_params();
    session_unset();
    session_destroy();
    if (!headers_sent()) {
        setcookie($name, '', [
            'expires' => time() - 86400,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?: 'Lax',
        ]);
    }
}
