<?php
define('SKYFR', 1);
define('SKYFR_CRON', 1);

$cronIsCli = PHP_SAPI === 'cli';
if (!$cronIsCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
}

require __DIR__ . '/app/bootstrap.php';

$cronKey = trim((string) config('security.cron_key', ''));
$cronSent = $cronIsCli ? '' : trim((string) ($_GET['key'] ?? ''));
if (!$cronIsCli && ($cronKey === '' || $cronSent === '' || !hash_equals($cronKey, $cronSent))) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$cronDrained = mail_outbox_drain(10, 40);
housekeeping_run();
echo 'sent=' . $cronDrained['sent'] . ' retried=' . $cronDrained['retried'] . ' failed=' . $cronDrained['failed'] . PHP_EOL;
