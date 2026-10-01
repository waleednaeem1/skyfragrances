<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$usage = "Usage: php dev-tools/theme-switch.php dark|light|status [path/to/site]\n";
$mode = $argv[1] ?? '';
if (!in_array($mode, ['dark', 'light', 'status'], true)) {
    fwrite(STDERR, $usage);
    exit(2);
}

$siteRoot = realpath($argv[2] ?? (__DIR__ . '/../site'));
if ($siteRoot === false || !is_file($siteRoot . '/config.php')) {
    fwrite(STDERR, "No config.php under " . ($argv[2] ?? 'site/') . ". Aborted.\n");
    exit(2);
}

define('SKYFR', true);
$config = require $siteRoot . '/config.php';
if (!is_array($config) || !is_array($config['db'] ?? null)) {
    fwrite(STDERR, "config.php did not return a db block. Aborted.\n");
    exit(2);
}

$db = $config['db'];
$host = strtolower((string) ($db['host'] ?? ''));
$name = (string) ($db['name'] ?? '');
$env = (string) ($config['env'] ?? '');
$localHosts = ['127.0.0.1', 'localhost', '::1', '[::1]'];

if (!in_array($host, $localHosts, true)) {
    fwrite(STDERR, "Refusing: DB host '$host' is not local.\n");
    exit(3);
}
if ($env !== 'development') {
    fwrite(STDERR, "Refusing: config env is '$env', not 'development'.\n");
    exit(3);
}
if (!preg_match('/_(dev|test|local)$/', $name)) {
    fwrite(STDERR, "Refusing: database '$name' does not end in _dev, _test or _local.\n");
    exit(3);
}

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, (int) ($db['port'] ?? 3306), $name);
try {
    $pdo = new PDO($dsn, (string) ($db['user'] ?? ''), (string) ($db['pass'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, 'Could not connect: ' . $e->getMessage() . "\n");
    exit(4);
}

$read = static function () use ($pdo): ?string {
    $row = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_theme'")->fetch();
    return $row === false ? null : (string) $row['setting_value'];
};

$before = $read();
if ($mode === 'status') {
    echo 'site_theme = ' . ($before === null ? '(no row, storefront is dark)' : "'$before'") . " in $name@$host\n";
    exit(0);
}

$now = (new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi')))->format('Y-m-d H:i:s');
$stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, setting_group, updated_at) VALUES (:k, :v, :g, :t) ON DUPLICATE KEY UPDATE setting_value = :v2, setting_group = :g2, updated_at = :t2');
$stmt->execute(['k' => 'site_theme', 'v' => $mode, 'g' => 'appearance', 't' => $now, 'v2' => $mode, 'g2' => 'appearance', 't2' => $now]);

$cleared = [];
foreach (['storage/cache/settings.json', 'storage/cache/settings.php'] as $rel) {
    $file = $siteRoot . '/' . $rel;
    if (is_file($file) && @unlink($file)) {
        $cleared[] = $rel;
    }
}

$after = $read();
echo 'site_theme: ' . ($before ?? '(no row)') . " -> $after in $name@$host\n";
echo 'cache cleared: ' . ($cleared ? implode(', ', $cleared) : 'nothing cached') . "\n";
echo "Servers started with SKYFR_THEME set ignore this setting; restart them without it.\n";
exit($after === $mode ? 0 : 1);
