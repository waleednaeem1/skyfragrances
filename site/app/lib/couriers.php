<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/orders.php';

const COURIER_SYNC_STATUSES = ['pending', 'confirmed', 'packing', 'shipped'];
const COURIER_RECHECK_SECONDS = 7200;
const COURIER_SYNC_EVERY_SECONDS = 1800;
const COURIER_LABELS = ['postex' => 'PostEx', 'trax' => 'Trax'];

function courier_key_for(string $courierName): ?string
{
    $name = strtolower($courierName);
    if (str_contains($name, 'postex')) {
        return 'postex';
    }
    if (str_contains($name, 'trax') || str_contains($name, 'sonic')) {
        return 'trax';
    }
    return null;
}

function courier_credential(string $key): string
{
    return trim((string) setting($key === 'postex' ? 'postex_api_token' : 'trax_api_key', ''));
}

function courier_ensure_table(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    db_query('CREATE TABLE IF NOT EXISTS order_courier_status (
        order_id INT UNSIGNED NOT NULL,
        courier VARCHAR(16) NOT NULL,
        tracking_number VARCHAR(60) NOT NULL,
        status_text VARCHAR(190) NULL DEFAULT NULL,
        status_class VARCHAR(16) NULL DEFAULT NULL,
        error VARCHAR(190) NULL DEFAULT NULL,
        checked_at DATETIME NOT NULL,
        changed_at DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $done = true;
}

function courier_http_get(string $url, array $headers): array
{
    if (!function_exists('curl_init')) {
        return ['status' => 0, 'body' => '', 'error' => 'PHP curl is not available on this server.'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'SkyFragrances/1.0 courier-tracking',
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    return ['status' => $status, 'body' => is_string($body) ? $body : '', 'error' => $error];
}

function courier_request(string $key, string $tracking): array
{
    $token = courier_credential($key);
    if ($token === '') {
        return ['status' => 0, 'body' => '', 'error' => 'Add the ' . COURIER_LABELS[$key] . ' key in Settings › Couriers first.'];
    }
    if ($key === 'postex') {
        return courier_http_get('https://api.postex.pk/services/integration/api/order/v1/track-order/' . rawurlencode($tracking), ['token: ' . $token, 'Accept: application/json']);
    }
    return courier_http_get('https://sonic.pk/api/shipment/track?tracking_number=' . rawurlencode($tracking) . '&type=0', ['Authorization: ' . $token, 'Accept: application/json']);
}

function courier_status_candidates(mixed $node, string $path, array &$found): void
{
    if (!is_array($node)) {
        return;
    }
    foreach ($node as $field => $value) {
        $here = $path . '.' . $field;
        if (is_array($value)) {
            courier_status_candidates($value, $here, $found);
            continue;
        }
        if (!is_string($value) || trim($value) === '' || preg_match('/^\d+$/', trim($value))) {
            continue;
        }
        if (preg_match('/status|remark|activity|message|description|state/i', (string) $field) && !preg_match('/^(statusmessage|message)$/i', (string) $field) || preg_match('/transactionstatusmessage$/i', (string) $field)) {
            $found[] = ['path' => $here, 'text' => trim($value)];
        }
    }
}

function courier_parse(string $key, string $body): array
{
    $json = json_decode($body, true);
    if (!is_array($json)) {
        return ['ok' => false, 'text' => '', 'detail' => 'The courier did not answer with data we can read.'];
    }
    $primary = null;
    if ($key === 'postex') {
        $dist = $json['dist'] ?? null;
        if (is_array($dist)) {
            if (!empty($dist['transactionStatus']) && is_string($dist['transactionStatus'])) {
                $primary = $dist['transactionStatus'];
            } elseif (!empty($dist['transactionStatusHistory']) && is_array($dist['transactionStatusHistory'])) {
                $last = end($dist['transactionStatusHistory']);
                $primary = is_array($last) ? (string) ($last['transactionStatusMessage'] ?? '') : null;
            }
        }
        if ($primary === null && isset($json['statusCode']) && (string) $json['statusCode'] !== '200') {
            return ['ok' => false, 'text' => '', 'detail' => 'PostEx said: ' . (string) ($json['statusMessage'] ?? 'request refused')];
        }
    } else {
        $details = $json['details'] ?? null;
        if (is_array($details)) {
            foreach (['current_status', 'status_name', 'shipment_status', 'status'] as $field) {
                if (!empty($details[$field]) && is_string($details[$field]) && !preg_match('/^\d+$/', $details[$field])) {
                    $primary = $details[$field];
                    break;
                }
            }
            if ($primary === null && !empty($details['tracking_history']) && is_array($details['tracking_history'])) {
                $first = reset($details['tracking_history']);
                $last = end($details['tracking_history']);
                $pick = static fn ($row): string => is_array($row) ? (string) ($row['status'] ?? $row['message'] ?? '') : '';
                $primary = courier_newest_row($details['tracking_history']) ?? ($pick($first) !== '' ? $pick($first) : $pick($last));
            }
        }
        if ($primary === null && isset($json['status']) && (string) $json['status'] !== '0' && !empty($json['message'])) {
            return ['ok' => false, 'text' => '', 'detail' => 'Trax said: ' . (string) $json['message']];
        }
    }
    if ($primary === null || trim($primary) === '') {
        $found = [];
        courier_status_candidates($json, '', $found);
        if ($found === []) {
            return ['ok' => false, 'text' => '', 'detail' => 'The courier answered, but no status was found in the reply.'];
        }
        $primary = $found[count($found) - 1]['text'];
    }
    return ['ok' => true, 'text' => mb_substr(trim($primary), 0, 190), 'detail' => ''];
}

function courier_newest_row(array $rows): ?string
{
    $best = null;
    $bestTime = null;
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $text = (string) ($row['status'] ?? $row['message'] ?? '');
        $time = null;
        foreach (['date_time', 'datetime', 'created_at', 'date'] as $field) {
            if (!empty($row[$field]) && ($parsed = strtotime((string) $row[$field])) !== false) {
                $time = $parsed;
                break;
            }
        }
        if ($text !== '' && $time !== null && ($bestTime === null || $time >= $bestTime)) {
            $best = $text;
            $bestTime = $time;
        }
    }
    return $best;
}

function courier_classify(string $text): string
{
    $t = strtolower($text);
    if (preg_match('/return|rto|refus|cancel/', $t)) {
        return 'returned';
    }
    if (preg_match('/deliver/', $t) && !preg_match('/out for deliver|undeliver|not deliver|un-deliver|attempt|deliver(y)? (fail|pending|attempt)|re-?deliver/', $t)) {
        return 'delivered';
    }
    if (preg_match('/transit|route|picked|pick ?up|arriv|dispatch|warehouse|hub|out for deliver|received|attempt|booked|shipped|in process|on the way/', $t)) {
        return 'in_transit';
    }
    return 'other';
}

function courier_check(string $key, string $tracking): array
{
    $response = courier_request($key, $tracking);
    if ($response['status'] === 0 && $response['error'] !== '') {
        return ['ok' => false, 'text' => '', 'class' => 'other', 'detail' => $response['error'], 'http' => 0, 'raw' => ''];
    }
    $parsed = courier_parse($key, $response['body']);
    if (!$parsed['ok'] && $response['status'] >= 400) {
        $parsed['detail'] = COURIER_LABELS[$key] . ' answered with error ' . $response['status'] . ($response['status'] === 401 || $response['status'] === 403 ? '. Check the key in Settings › Couriers.' : '. Check the tracking number.');
    }
    return $parsed + ['class' => $parsed['ok'] ? courier_classify($parsed['text']) : 'other', 'http' => $response['status'], 'raw' => mb_substr($response['body'], 0, 1500)];
}

function courier_sync_order(array $order): array
{
    $key = courier_key_for((string) ($order['courier_name'] ?? ''));
    $tracking = trim((string) ($order['tracking_number'] ?? ''));
    if ($key === null || $tracking === '') {
        return ['ok' => false, 'message' => 'Only PostEx and Trax parcels with a tracking number are checked automatically.'];
    }
    courier_ensure_table();
    $result = courier_check($key, $tracking);
    $now = now_karachi();
    $previous = db_fetch('SELECT status_text FROM order_courier_status WHERE order_id = :id', ['id' => (int) $order['id']]);
    $changed = $result['ok'] && ($previous === null || (string) $previous['status_text'] !== $result['text']);
    db_query(
        'INSERT INTO order_courier_status (order_id, courier, tracking_number, status_text, status_class, error, checked_at, changed_at)
         VALUES (:id, :c, :t, :s, :k, :e, :now, :changed)
         ON DUPLICATE KEY UPDATE courier = :c2, tracking_number = :t2, status_text = COALESCE(:s2, status_text), status_class = COALESCE(:k2, status_class), error = :e2, checked_at = :now2, changed_at = COALESCE(:changed2, changed_at)',
        [
            'id' => (int) $order['id'], 'c' => $key, 't' => $tracking, 's' => $result['ok'] ? $result['text'] : null, 'k' => $result['ok'] ? $result['class'] : null, 'e' => $result['ok'] ? null : mb_substr($result['detail'], 0, 190), 'now' => $now, 'changed' => $changed ? $now : null,
            'c2' => $key, 't2' => $tracking, 's2' => $result['ok'] ? $result['text'] : null, 'k2' => $result['ok'] ? $result['class'] : null, 'e2' => $result['ok'] ? null : mb_substr($result['detail'], 0, 190), 'now2' => $now, 'changed2' => $changed ? $now : null,
        ]
    );
    if (!$result['ok']) {
        return ['ok' => false, 'message' => $result['detail']];
    }
    $status = (string) $order['status'];
    $label = COURIER_LABELS[$key];
    $moved = null;
    if ($result['class'] === 'delivered' && $status !== 'delivered' && $status !== 'cancelled') {
        $moved = order_transition((int) $order['id'], 'delivered', order_actor_system(), 'Delivered according to ' . $label . ': ' . $result['text']);
    } elseif ($result['class'] === 'in_transit' && in_array($status, ['pending', 'confirmed', 'packing'], true)) {
        $moved = order_transition((int) $order['id'], 'shipped', order_actor_system(), 'Picked up by ' . $label . ': ' . $result['text']);
    }
    $text = $label . ': ' . $result['text'];
    if ($moved !== null && !empty($moved['ok'])) {
        $text .= ' — order marked ' . (string) ($moved['order']['status'] ?? '') . '.';
    } elseif ($result['class'] === 'returned') {
        $text .= ' — the parcel is coming back. Check it and cancel the order when it arrives.';
    }
    return ['ok' => true, 'message' => $text, 'class' => $result['class'], 'changed' => $changed];
}

function courier_sync_due(int $limit): array
{
    $report = ['checked' => 0, 'moved' => 0, 'failed' => 0];
    if (courier_credential('postex') === '' && courier_credential('trax') === '') {
        return $report;
    }
    courier_ensure_table();
    $rows = db_fetch_all(
        "SELECT o.* FROM orders o
         LEFT JOIN order_courier_status c ON c.order_id = o.id
         WHERE o.status IN ('pending', 'confirmed', 'packing', 'shipped')
           AND o.tracking_number IS NOT NULL AND o.tracking_number <> ''
           AND (LOWER(o.courier_name) LIKE '%postex%' OR LOWER(o.courier_name) LIKE '%trax%' OR LOWER(o.courier_name) LIKE '%sonic%')
           AND (c.checked_at IS NULL OR c.checked_at < :before)
         ORDER BY c.checked_at IS NOT NULL, c.checked_at ASC
         LIMIT " . max(1, min(100, $limit)),
        ['before' => date('Y-m-d H:i:s', time() - COURIER_RECHECK_SECONDS)]
    );
    foreach ($rows as $order) {
        $key = courier_key_for((string) $order['courier_name']);
        if ($key === null || courier_credential($key) === '') {
            continue;
        }
        $before = (string) $order['status'];
        $result = courier_sync_order($order);
        $report['checked']++;
        if (!$result['ok']) {
            $report['failed']++;
        } elseif ((string) (order_find((int) $order['id'])['status'] ?? $before) !== $before) {
            $report['moved']++;
        }
    }
    return $report;
}

function courier_sync_maybe(): void
{
    $stamp = APP_ROOT . '/storage/cache/courier-sync.txt';
    if (is_file($stamp) && time() - (int) @filemtime($stamp) < COURIER_SYNC_EVERY_SECONDS) {
        return;
    }
    @touch($stamp);
    try {
        courier_sync_due(15);
    } catch (Throwable $e) {
        log_write('warning', 'courier sync failed', ['error' => $e->getMessage()]);
    }
}

function courier_status_for(int $orderId): ?array
{
    courier_ensure_table();
    return db_fetch('SELECT * FROM order_courier_status WHERE order_id = :id', ['id' => $orderId]);
}
