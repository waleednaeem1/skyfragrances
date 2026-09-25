<?php
defined('SKYFR') || exit;

const HOUSEKEEPING_ATTEMPT_RETENTION_DAYS = 30;
const HOUSEKEEPING_PROOF_ORPHAN_SECONDS = 86400;
const HOUSEKEEPING_PROOF_THUMBNAIL_SUFFIX = '-320.jpg';
const HOUSEKEEPING_SESSION_LIFETIMES = ['shop' => 259200, 'admin' => 43200];

function housekeeping_maybe_run(): void
{
    if (random_int(1, 20) === 1) {
        housekeeping_run();
    }
}

function housekeeping_run(): array
{
    $report = ['rate_limits' => 0, 'login_attempts' => 0, 'sessions' => 0, 'outbox_bodies' => 0, 'mail_previews' => 0, 'proof_orphans' => 0, 'proof_thumbnails' => 0];
    $before = date('Y-m-d H:i:s', time() - HOUSEKEEPING_ATTEMPT_RETENTION_DAYS * 86400);
    try {
        $report['rate_limits'] = db_query('DELETE FROM rate_limits WHERE attempted_at < :before', ['before' => $before])->rowCount();
        $report['login_attempts'] = db_query('DELETE FROM admin_login_attempts WHERE attempted_at < :before', ['before' => $before])->rowCount();
        $report['outbox_bodies'] = mail_outbox_stub_old_bodies();
    } catch (Throwable $e) {
        log_write('warning', 'housekeeping: database prune failed', ['error' => $e->getMessage()]);
    }
    $report['mail_previews'] = mail_preview_prune();
    $report['sessions'] = housekeeping_prune_sessions();
    $report['proof_orphans'] = housekeeping_prune_proof_orphans();
    $report['proof_thumbnails'] = housekeeping_prune_proof_thumbnails();
    log_write('info', 'housekeeping ran', $report);
    return $report;
}

function housekeeping_prune_sessions(): int
{
    $removed = 0;
    foreach (HOUSEKEEPING_SESSION_LIFETIMES as $dir => $lifetime) {
        $path = APP_ROOT . '/storage/sessions/' . $dir;
        if (!is_dir($path)) {
            continue;
        }
        $cutoff = time() - $lifetime;
        foreach (glob($path . '/sess_*') ?: [] as $file) {
            if (is_file($file) && (int) @filemtime($file) < $cutoff && @unlink($file)) {
                $removed++;
            }
        }
    }
    return $removed;
}

function housekeeping_prune_proof_orphans(): int
{
    $root = APP_ROOT . '/storage/proofs';
    if (!is_dir($root)) {
        return 0;
    }
    $removed = 0;
    $cutoff = time() - HOUSEKEEPING_PROOF_ORPHAN_SECONDS;
    foreach (glob($root . '/[0-9][0-9][0-9][0-9]/[0-9][0-9]/*.*') ?: [] as $file) {
        if (!is_file($file) || housekeeping_is_proof_thumbnail($file) || (int) @filemtime($file) >= $cutoff) {
            continue;
        }
        $relative = substr($file, strlen($root) + 1);
        try {
            $referenced = db_exists(
                'SELECT 1 FROM payment_proofs WHERE file_path = :rel OR file_path = :base LIMIT 1',
                ['rel' => $relative, 'base' => basename($file)]
            );
        } catch (Throwable $e) {
            return $removed;
        }
        if (!$referenced && @unlink($file)) {
            $removed++;
        }
    }
    return $removed;
}

function housekeeping_is_proof_thumbnail(string $file): bool
{
    return str_ends_with($file, HOUSEKEEPING_PROOF_THUMBNAIL_SUFFIX);
}

function housekeeping_prune_proof_thumbnails(): int
{
    $root = APP_ROOT . '/storage/proofs';
    if (!is_dir($root)) {
        return 0;
    }
    $removed = 0;
    foreach (glob($root . '/[0-9][0-9][0-9][0-9]/[0-9][0-9]/*' . HOUSEKEEPING_PROOF_THUMBNAIL_SUFFIX) ?: [] as $thumbnail) {
        $source = substr($thumbnail, 0, -strlen(HOUSEKEEPING_PROOF_THUMBNAIL_SUFFIX)) . '.jpg';
        if (!is_file($source) && @unlink($thumbnail)) {
            $removed++;
        }
    }
    return $removed;
}

function housekeeping_storage_usage_bytes(): int
{
    $total = 0;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_ROOT . '/storage', FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $total += (int) $file->getSize();
        }
    }
    return $total;
}
