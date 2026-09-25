<?php
defined('SKYFR') || exit;

const RATE_LIMIT_BUCKETS = ['checkout', 'track', 'proof', 'contact', 'newsletter', 'review', 'coupon'];
const RATE_LIMIT_RETENTION_SECONDS = 30 * 86400;

function rate_limit_subject(string $value): string
{
    return hash('sha256', config('security.app_key', '') . '|' . $value);
}

function rate_limit_count(string $bucket, string $subjectHash, int $windowSeconds): int
{
    $row = db_fetch(
        'SELECT COUNT(*) AS hits FROM rate_limits WHERE bucket = :bucket AND subject_hash = :subject AND attempted_at > :since',
        ['bucket' => $bucket, 'subject' => $subjectHash, 'since' => date('Y-m-d H:i:s', time() - $windowSeconds)]
    );
    return (int) ($row['hits'] ?? 0);
}

function rate_limit_record(string $bucket, string $subjectHash, bool $success = false): void
{
    db_insert('rate_limits', [
        'bucket' => $bucket,
        'subject_hash' => $subjectHash,
        'attempted_at' => now_karachi(),
        'was_success' => $success ? 1 : 0,
    ]);
}

function rate_limit_hit(string $bucket, string $subjectHash, int $max, int $windowSeconds): bool
{
    if (!in_array($bucket, RATE_LIMIT_BUCKETS, true)) {
        throw new InvalidArgumentException('Unknown rate limit bucket ' . $bucket);
    }
    rate_limit_purge();
    if (rate_limit_count($bucket, $subjectHash, $windowSeconds) >= $max) {
        log_write('warning', 'rate limit reached', ['bucket' => $bucket, 'subject' => substr($subjectHash, 0, 12)]);
        return true;
    }
    rate_limit_record($bucket, $subjectHash);
    return false;
}

function rate_limit_purge(): void
{
    if (random_int(1, 20) !== 1) {
        return;
    }
    db_query(
        'DELETE FROM rate_limits WHERE attempted_at < :before',
        ['before' => date('Y-m-d H:i:s', time() - RATE_LIMIT_RETENTION_SECONDS)]
    );
}

function rate_limit_guard(string $bucket, int $max, int $windowSeconds, string $message): void
{
    if (!rate_limit_hit($bucket, request_ip_hash(), $max, $windowSeconds)) {
        return;
    }
    if (request_is_api() || request_is_ajax()) {
        json(['ok' => false, 'error' => 'rate_limit', 'message' => $message], 429);
    }
    abort(429, $message);
}

function rate_limit_weighted_count(string $bucket, string $subjectHash, int $windowSeconds, int $successWeightDivisor = 5): int
{
    $row = db_fetch(
        'SELECT SUM(CASE WHEN was_success = 1 THEN 0 ELSE 1 END) AS misses, SUM(CASE WHEN was_success = 1 THEN 1 ELSE 0 END) AS hits
         FROM rate_limits WHERE bucket = :bucket AND subject_hash = :subject AND attempted_at > :since',
        ['bucket' => $bucket, 'subject' => $subjectHash, 'since' => date('Y-m-d H:i:s', time() - $windowSeconds)]
    );
    return (int) ($row['misses'] ?? 0) + intdiv((int) ($row['hits'] ?? 0), max(1, $successWeightDivisor));
}

function rate_limit_over(string $bucket, string $subjectHash, int $max, int $windowSeconds): bool
{
    return rate_limit_count($bucket, $subjectHash, $windowSeconds) >= $max;
}

function rate_limit_forget(string $bucket, string $subjectHash): int
{
    return db_query('DELETE FROM rate_limits WHERE bucket = :bucket AND subject_hash = :subject', ['bucket' => $bucket, 'subject' => $subjectHash])->rowCount();
}
