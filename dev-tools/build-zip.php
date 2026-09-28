<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Run from the command line: php dev-tools/build-zip.php\n");
}

$repoRoot = dirname(__DIR__);
$siteRoot = $repoRoot . '/site';
$distDir = $repoRoot . '/dist';
$zipPath = $distDir . '/skyfragrances-' . date('Ymd-Hi') . '.zip';

$manifest = require $siteRoot . '/dev/zip-manifest.php';
$excludedExact = $manifest['exclude_exact'];
$excludedPrefixes = $manifest['exclude_prefixes'];
$excludedBasenames = $manifest['exclude_basenames'];
$keepInsideExcludedDirs = $manifest['keep_inside_excluded_dirs'];
$includedPrefixes = $manifest['include_prefixes'] ?? [];

function build_relative(string $root, string $path): string
{
    return str_replace('\\', '/', substr($path, strlen($root) + 1));
}

function build_is_excluded(string $rel, array $exact, array $prefixes, array $basenames, array $keep, array $include = []): bool
{
    if (in_array($rel, $exact, true) || in_array(basename($rel), $basenames, true)) {
        return true;
    }
    foreach ($include as $prefix) {
        if (str_starts_with($rel, $prefix) || str_starts_with($prefix, $rel . '/')) {
            return false;
        }
    }
    if (str_starts_with($rel, 'dev/') || str_starts_with($rel, '.git')) {
        return true;
    }
    foreach ($prefixes as $prefix) {
        if (str_starts_with($rel, $prefix)) {
            return !in_array(basename($rel), $keep, true) || substr_count($rel, '/') !== substr_count($prefix, '/');
        }
    }
    return false;
}

$sample = (string) file_get_contents($siteRoot . '/config.sample.php');
if (!str_contains($sample, 'REPLACE_ME') || preg_match('/[0-9a-f]{64}/', $sample)) {
    exit("config.sample.php looks like it carries real secrets. Aborting.\n");
}

if (!is_dir($distDir) && !mkdir($distDir, 0755, true)) {
    exit("Cannot create dist/.\n");
}

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    exit("Cannot open $zipPath for writing.\n");
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($siteRoot, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
$added = 0;
$skipped = [];
foreach ($iterator as $item) {
    $rel = build_relative($siteRoot, $item->getPathname());
    $relDir = $item->isDir() ? $rel . '/' : $rel;
    if (build_is_excluded($relDir, $excludedExact, $excludedPrefixes, $excludedBasenames, $keepInsideExcludedDirs, $includedPrefixes)) {
        if (!$item->isDir()) {
            $skipped[] = $rel;
        }
        continue;
    }
    if ($item->isDir()) {
        $zip->addEmptyDir($rel);
        continue;
    }
    $zip->addFile($item->getPathname(), $rel);
    $added++;
}
foreach ($manifest['empty_dirs'] as $dir) {
    $zip->addEmptyDir($dir);
    $zip->addFromString($dir . '/.gitkeep', '');
}
$zip->close();

$verify = new ZipArchive();
$verify->open($zipPath);
$names = [];
for ($i = 0; $i < $verify->numFiles; $i++) {
    $names[] = (string) $verify->getNameIndex($i);
}
$verify->close();
$forbidden = array_filter($names, static function (string $name) use ($excludedExact, $manifest): bool {
    if (in_array($name, $excludedExact, true)) {
        return true;
    }
    foreach ($manifest['forbidden_patterns'] as $pattern) {
        if (preg_match($pattern, $name)) {
            return true;
        }
    }
    return false;
});
if ($forbidden !== []) {
    unlink($zipPath);
    exit("ZIP contained forbidden entries:\n  " . implode("\n  ", $forbidden) . "\nAborted.\n");
}
foreach ($manifest['required_files'] as $required) {
    if (!in_array($required, $names, true)) {
        unlink($zipPath);
        exit("ZIP is missing $required. Aborted.\n");
    }
}
$htaccessCount = count(array_filter($names, static fn (string $name): bool => basename($name) === '.htaccess'));
if ($htaccessCount !== $manifest['required_htaccess_count']) {
    unlink($zipPath);
    exit("ZIP holds $htaccessCount .htaccess files, expected {$manifest['required_htaccess_count']}. Aborted.\n");
}

echo "Wrote $zipPath\n$added files added, " . count($skipped) . " local files left out.\n";
foreach (array_slice($skipped, 0, 12) as $rel) {
    echo "  skipped $rel\n";
}
if (count($skipped) > 12) {
    echo '  … and ' . (count($skipped) - 12) . " more\n";
}
