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

function build_abort(ZipArchive $zip, string $zipPath, string $message): never
{
    $zip->close();
    unlink($zipPath);
    exit($message . "\nAborted.\n");
}

$installSource = (string) $verify->getFromName('install.php');
$literalMap = [
    'INSTALL_DENY_HTACCESS' => ['app/.htaccess', 'db/.htaccess', 'admin/controllers/.htaccess', 'admin/views/.htaccess', 'admin/partials/.htaccess'],
    'INSTALL_STORAGE_HTACCESS' => ['storage/.htaccess'],
    'INSTALL_UPLOADS_HTACCESS' => ['uploads/.htaccess'],
    'INSTALL_ASSETS_HTACCESS' => ['assets/.htaccess'],
    'INSTALL_ADMIN_HTACCESS' => ['admin/.htaccess'],
];
foreach ($literalMap as $constant => $paths) {
    if (!preg_match("/const {$constant} = <<<'HTACCESS'\n(.*?)\nHTACCESS;/s", $installSource, $literal)) {
        build_abort($verify, $zipPath, "install.php no longer defines $constant.");
    }
    foreach ($paths as $rel) {
        if ((string) $verify->getFromName($rel) !== $literal[1]) {
            build_abort($verify, $zipPath, "$rel differs from the $constant literal in install.php.");
        }
    }
}

$headMeta = (string) $verify->getFromName('app/partials/head-meta.php');
$responseLib = (string) $verify->getFromName('app/lib/response.php');
preg_match_all('#<script>(.*?)</script>#s', $headMeta, $inlineScripts);
if (count($inlineScripts[1]) !== 1) {
    build_abort($verify, $zipPath, 'head-meta.php must carry exactly one inline <script> (the hashed bootstrap).');
}
$bootstrapHash = 'sha256-' . base64_encode(hash('sha256', $inlineScripts[1][0], true));
if (!str_contains($responseLib, "'" . $bootstrapHash . "'")) {
    build_abort($verify, $zipPath, "CSP_BOOTSTRAP_SCRIPT_HASH in response.php is not $bootstrapHash; the inline bootstrap changed.");
}

$inlineScriptPattern = '#<script(?![^>]*\bsrc=)(?![^>]*type="application/(?:ld\+json|json)")[^>]*>#i';
$guardPattern = "#defined\\('SKYFR'\\) \\|\\| exit;#";
$forbiddenExtension = '#(\.(md|log|bak|orig|tmp|swp|old|dist)|~)$#i';
foreach ($names as $name) {
    if (str_ends_with($name, '/')) {
        continue;
    }
    if (preg_match($forbiddenExtension, $name) || str_contains($name, 'sess_') || (str_starts_with($name, 'uploads/') && preg_match('#\.(php|phtml|phar|inc|cgi|pl|py|sh|shtml)#i', $name)) || (str_ends_with($name, '.sql') && !str_starts_with($name, 'db/'))) {
        build_abort($verify, $zipPath, "ZIP contains a file that must never ship: $name");
    }
    if (!str_ends_with($name, '.php') || str_starts_with($name, 'app/lib/vendor/') || str_starts_with($name, 'app/tools/')) {
        continue;
    }
    $content = (string) $verify->getFromName($name);
    if (preg_match('#^(app/(views|partials)/|admin/(views|partials)/)#', $name) && $name !== 'app/partials/head-meta.php' && preg_match($inlineScriptPattern, $content)) {
        build_abort($verify, $zipPath, "$name carries an inline <script> the CSP would block.");
    }
    $guarded = preg_match('#^(app/|admin/(controllers|views|partials)/|db/sample-manifest\.php$)#', $name) === 1;
    if ($guarded && basename($name) !== 'index.php' && !preg_match($guardPattern, substr($content, 0, 160))) {
        build_abort($verify, $zipPath, "$name is missing the SKYFR guard in its first lines.");
    }
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

$flatCopy = $distDir . '/skyfragrances-public_html.zip';
copy($zipPath, $flatCopy);
$wrappedPath = $distDir . '/skyfragrances-public_html-folder.zip';
$src = new ZipArchive();
$dst = new ZipArchive();
if ($src->open($zipPath) === true && $dst->open($wrappedPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
    for ($i = 0; $i < $src->numFiles; $i++) {
        $entry = (string) $src->getNameIndex($i);
        if (str_ends_with($entry, '/')) {
            $dst->addEmptyDir('public_html/' . $entry);
        } else {
            $dst->addFromString('public_html/' . $entry, (string) $src->getFromIndex($i));
        }
    }
    $dst->close();
    $src->close();
    echo "Wrote $flatCopy and $wrappedPath (folder-wrapped for hPanel)\n";
} else {
    exit("Could not write the folder-wrapped ZIP.\n");
}
