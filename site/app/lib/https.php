<?php
defined('SKYFR') || exit;

const HTTPS_REDIRECT_TEMPORARY = 'RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=302,NE]';
const HTTPS_REDIRECT_PERMANENT = 'RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301,NE]';

function https_selfcheck(string $host): array
{
    $host = strtolower(trim($host));
    if ($host === '' || !preg_match('/^[a-z0-9.-]+$/', $host) || in_array($host, ['localhost', '127.0.0.1'], true)) {
        return ['ok' => false, 'detail' => 'The host ' . $host . ' cannot be checked over HTTPS.'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'detail' => 'PHP curl is not available, so the certificate could not be checked.'];
    }
    $url = 'https://' . $host . '/robots.txt';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'SkyFragrances/1.0 https-check',
    ]);
    curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($status === 200) {
        return ['ok' => true, 'detail' => $url . ' answered 200 with a valid certificate.'];
    }
    return ['ok' => false, 'detail' => $error !== '' ? $error : ($status === 0 ? 'No answer from ' . $url : $url . ' answered HTTP ' . $status)];
}

function https_redirect_is_permanent(string $root): bool
{
    $text = (string) @file_get_contents($root . '/.htaccess');
    return str_contains($text, HTTPS_REDIRECT_PERMANENT);
}

function https_redirect_make_permanent(string $root): bool
{
    $path = $root . '/.htaccess';
    $text = @file_get_contents($path);
    if ($text === false) {
        return false;
    }
    if (str_contains($text, HTTPS_REDIRECT_PERMANENT)) {
        return true;
    }
    if (!str_contains($text, HTTPS_REDIRECT_TEMPORARY)) {
        return false;
    }
    $updated = str_replace(HTTPS_REDIRECT_TEMPORARY, HTTPS_REDIRECT_PERMANENT, $text);
    $temp = $root . '/.htaccess.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($temp, $updated, LOCK_EX) === false) {
        return false;
    }
    @chmod($temp, 0644);
    if (!@rename($temp, $path)) {
        @unlink($temp);
        return false;
    }
    return true;
}

function https_make_permanent_if_ready(string $root, string $host): array
{
    $check = https_selfcheck($host);
    if (!$check['ok']) {
        return ['ok' => false, 'flipped' => false, 'detail' => $check['detail']];
    }
    $flipped = https_redirect_make_permanent($root);
    return ['ok' => true, 'flipped' => $flipped, 'detail' => $flipped ? 'HTTPS redirect is now permanent (301).' : 'HTTPS is confirmed but .htaccess could not be updated; the redirect stays temporary (302).'];
}
