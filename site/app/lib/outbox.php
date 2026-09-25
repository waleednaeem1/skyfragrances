<?php
defined('SKYFR') || exit;

const OUTBOX_PURGE_ONE_IN = 20;

function outbox_release_response(): void
{
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
        return;
    }
    if (!headers_sent()) {
        header('Connection: close');
        header('Content-Length: ' . (int) array_sum(array_column(ob_get_status(true), 'buffer_used')));
    }
    while (ob_get_level() > 0) {
        @ob_end_flush();
    }
    flush();
}

function outbox_drain_allowed(): bool
{
    $fatal = error_get_last();
    if ($fatal !== null && ($fatal['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) {
        return false;
    }
    if (function_exists('auth_user') && auth_user() === null) {
        return false;
    }
    $status = http_response_code();
    return !is_int($status) || $status < 300 || $status >= 400;
}

function outbox_drain_after_response(int $maxSends = MAIL_DRAIN_MAX_SENDS, int $budgetSeconds = MAIL_DRAIN_BUDGET_SECONDS): array
{
    $result = ['sent' => 0, 'failed' => 0, 'retried' => 0, 'purged' => false, 'skipped' => true];
    if (!outbox_drain_allowed()) {
        return $result;
    }
    $result['skipped'] = false;
    outbox_release_response();
    ignore_user_abort(true);
    try {
        $result = array_merge($result, mail_outbox_drain($maxSends, $budgetSeconds));
    } catch (Throwable $e) {
        log_write('warning', 'outbox: drain failed', ['error' => $e->getMessage()]);
    }
    if (random_int(1, OUTBOX_PURGE_ONE_IN) === 1) {
        try {
            housekeeping_run();
            $result['purged'] = true;
        } catch (Throwable $e) {
            log_write('warning', 'outbox: purge failed', ['error' => $e->getMessage()]);
        }
    }
    return $result;
}

function outbox_dashboard_notice(): ?array
{
    $counts = mail_outbox_counts();
    if ($counts['queued'] > 0 && !mail_transport_available()) {
        return ['level' => 'error', 'text' => 'SMTP is not configured — ' . $counts['queued'] . ' email' . ($counts['queued'] === 1 ? '' : 's') . ' cannot be sent until the smtp block in config.php is filled in.', 'queued' => $counts['queued'], 'failed' => $counts['failed']];
    }
    if ($counts['failed'] > 0) {
        return ['level' => 'error', 'text' => $counts['failed'] . ' email' . ($counts['failed'] === 1 ? '' : 's') . ' could not be sent.', 'queued' => $counts['queued'], 'failed' => $counts['failed']];
    }
    if ($counts['queued'] > 0) {
        return ['level' => 'info', 'text' => $counts['queued'] . ' email' . ($counts['queued'] === 1 ? '' : 's') . ' waiting to send.', 'queued' => $counts['queued'], 'failed' => 0];
    }
    return null;
}

function outbox_order_mail_status(int $orderId, string $template): ?string
{
    return db_fetch_column(
        'SELECT status FROM email_outbox WHERE order_id = :id AND template = :template ORDER BY id DESC LIMIT 1',
        ['id' => $orderId, 'template' => $template]
    );
}
