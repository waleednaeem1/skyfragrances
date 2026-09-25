<?php
defined('SKYFR') || exit;

function flash(string $type, string $message): void
{
    if (!in_array($type, ['success', 'error', 'info'], true)) {
        $type = 'info';
    }
    session_ensure();
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flash_take(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return [];
    }
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($messages) ? $messages : [];
}
