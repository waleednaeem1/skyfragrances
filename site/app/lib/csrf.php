<?php
defined('SKYFR') || exit;

function csrf_token(): string
{
    session_ensure();
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(?string $t = null): void
{
    $submitted = $t ?? request_csrf_token_sent();
    $expected = $_SESSION['_csrf'] ?? null;
    if (!is_string($submitted) || $submitted === '' || !is_string($expected) || $expected === '' || !hash_equals($expected, $submitted)) {
        log_write('warning', 'CSRF check failed', ['path' => log_path(), 'ip_hash' => request_ip_hash()]);
        throw new RuntimeException('csrf', 419);
    }
}

function csrf_rotate(): void
{
    session_ensure();
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}
