<?php
defined('SKYFR') || exit;

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function request_path_raw(): string
{
    static $path = null;
    if ($path !== null) {
        return $path;
    }
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $cut = strpos($uri, '?');
    if ($cut !== false) {
        $uri = substr($uri, 0, $cut);
    }
    $uri = rawurldecode($uri);
    if (BASE_PATH !== '' && stripos($uri, BASE_PATH) === 0) {
        $uri = substr($uri, strlen(BASE_PATH));
    }
    $uri = '/' . trim(preg_replace('#/+#', '/', $uri), '/');
    $path = $uri;
    return $path;
}

function request_path(): string
{
    return strtolower(request_path_raw());
}

function request_path_is_unsafe(string $path): bool
{
    return str_contains($path, '..') || str_contains($path, "\0") || str_contains($path, '\\');
}

function request_query(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

function request_post(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

function request_json(): array
{
    static $decoded = null;
    if ($decoded !== null) {
        return $decoded;
    }
    $decoded = [];
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
        return $decoded;
    }
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return $decoded;
    }
    $data = json_decode($raw, true);
    $decoded = is_array($data) ? $data : [];
    return $decoded;
}

function request_input(string $key, mixed $default = null): mixed
{
    return request_json()[$key] ?? $_POST[$key] ?? $default;
}

function request_scalar_string(mixed $value, string $default): string
{
    return is_scalar($value) ? (string) $value : $default;
}

function request_string(string $key, string $default = ''): string
{
    return request_scalar_string(request_input($key, $default), $default);
}

function request_query_string(string $key, string $default = ''): string
{
    return request_scalar_string(request_query($key, $default), $default);
}

function log_path(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $cut = strpos($uri, '?');
    return $cut === false ? $uri : substr($uri, 0, $cut);
}

function request_header(string $name): ?string
{
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    if (isset($_SERVER[$key])) {
        return (string) $_SERVER[$key];
    }
    if ($name === 'Content-Type' && isset($_SERVER['CONTENT_TYPE'])) {
        return (string) $_SERVER['CONTENT_TYPE'];
    }
    return null;
}

function request_is_https(): bool
{
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    if ($https !== '' && $https !== 'off') {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
        return true;
    }
    return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function request_ip_in_cidr(string $ip, string $cidr): bool
{
    $cidr = trim($cidr);
    if ($cidr === '') {
        return false;
    }
    [$subnet, $bits] = array_pad(explode('/', $cidr, 2), 2, null);
    $ipBin = @inet_pton($ip);
    $subnetBin = @inet_pton(trim((string) $subnet));
    if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
        return false;
    }
    $maxBits = strlen($ipBin) * 8;
    $bits = $bits === null ? $maxBits : (int) $bits;
    if ($bits < 0 || $bits > $maxBits) {
        return false;
    }
    $fullBytes = intdiv($bits, 8);
    if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
        return false;
    }
    $remaining = $bits % 8;
    if ($remaining === 0) {
        return true;
    }
    $mask = (0xFF << (8 - $remaining)) & 0xFF;
    return (ord($ipBin[$fullBytes]) & $mask) === (ord($subnetBin[$fullBytes]) & $mask);
}

function request_trusted_proxies(): array
{
    $list = function_exists('setting') ? setting('trusted_proxies') : null;
    if ($list === null || trim((string) $list) === '') {
        $list = config('trusted_proxies', []);
    }
    if (is_string($list)) {
        $list = explode(',', $list);
    }
    if (!is_array($list)) {
        return [];
    }
    return array_values(array_filter(array_map(static fn ($cidr) => is_string($cidr) ? trim($cidr) : '', $list), static fn (string $cidr): bool => $cidr !== ''));
}

function request_remote_is_trusted_proxy(string $remote): bool
{
    foreach (request_trusted_proxies() as $cidr) {
        if (request_ip_in_cidr($remote, $cidr)) {
            return true;
        }
    }
    return false;
}

function request_forwarded_candidate(): string
{
    $forwarded = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
    foreach (array_reverse(array_map('trim', explode(',', $forwarded))) as $hop) {
        if ($hop !== '' && !request_remote_is_trusted_proxy($hop)) {
            return $hop;
        }
    }
    if (config('edge_is_cloudflare', false) === true) {
        return trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
    }
    return '';
}

function request_public_ip_is_valid(string $candidate): bool
{
    return $candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

function client_ip(): string
{
    static $ip = null;
    if ($ip !== null) {
        return $ip;
    }
    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $ip = $remote;
    if (!request_remote_is_trusted_proxy($remote)) {
        return $ip;
    }
    $candidate = request_forwarded_candidate();
    if (request_public_ip_is_valid($candidate)) {
        $ip = $candidate;
    }
    return $ip;
}

function request_ip(): string
{
    return client_ip();
}

function request_ip_hash(): string
{
    return hash('sha256', config('security.app_key', '') . '|' . request_ip());
}

function request_is_ajax(): bool
{
    if (strcasecmp(request_header('X-Requested-With') ?? '', 'XMLHttpRequest') === 0) {
        return true;
    }
    $accept = request_header('Accept') ?? '';
    return str_contains($accept, 'application/json') && !str_contains($accept, 'text/html');
}

function request_is_api(): bool
{
    return str_starts_with(request_path(), '/api/');
}

function request_host(): string
{
    $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($host === '') {
        $host = strtolower((string) (parse_url(SITE_URL, PHP_URL_HOST) ?: ''));
    }
    if ($host !== '' && $host[0] === '[') {
        $close = strpos($host, ']');
        return $close === false ? $host : substr($host, 0, $close + 1);
    }
    $colon = strpos($host, ':');
    return $colon === false ? $host : substr($host, 0, $colon);
}

function request_origin(): string
{
    return (request_is_https() ? 'https' : 'http') . '://' . request_host();
}

function request_origin_normalize(string $url): string
{
    $parts = parse_url(trim($url));
    if (!is_array($parts) || empty($parts['host']) || empty($parts['scheme'])) {
        return '';
    }
    return strtolower((string) $parts['scheme']) . '://' . strtolower((string) $parts['host']);
}

function request_origin_is_foreign(): bool
{
    $origin = request_header('Origin');
    if ($origin === null || $origin === '' || $origin === 'null') {
        $referer = request_header('Referer');
        if ($referer === null || $referer === '') {
            return false;
        }
        $origin = $referer;
    }
    $sent = request_origin_normalize($origin);
    $own = request_origin();
    if ($sent === '' || request_host() === '') {
        return true;
    }
    return !hash_equals($own, $sent);
}

function request_ini_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '' || $value === '-1') {
        return PHP_INT_MAX;
    }
    $unit = strtolower(substr($value, -1));
    $number = (int) $value;
    return match ($unit) {
        'g' => $number * 1073741824,
        'm' => $number * 1048576,
        'k' => $number * 1024,
        default => $number,
    };
}

function request_body_too_large(): ?int
{
    if (request_method() !== 'POST') {
        return null;
    }
    $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $limit = request_ini_bytes((string) ini_get('post_max_size'));
    if ($length > 0 && $length > $limit && $_POST === [] && $_FILES === []) {
        return $limit;
    }
    return null;
}

function request_return_path(string $fallback): string
{
    $referer = request_header('Referer');
    if ($referer !== null && $referer !== '' && !request_origin_is_foreign()) {
        $parts = parse_url($referer);
        $path = (string) ($parts['path'] ?? '');
        if ($path !== '' && $path[0] === '/' && !str_starts_with($path, '//')) {
            if (BASE_PATH !== '' && stripos($path, BASE_PATH) === 0) {
                $path = substr($path, strlen(BASE_PATH));
            }
            return ($path === '' ? '/' : $path) . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
        }
    }
    return $fallback;
}

function request_csrf_token_sent(): ?string
{
    $header = request_header('X-CSRF-Token');
    if ($header !== null && $header !== '') {
        return $header;
    }
    $body = request_input('_csrf');
    return is_string($body) ? $body : null;
}
