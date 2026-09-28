<?php
declare(strict_types=1);

$src = $argv[1] ?? '';
$outCritical = $argv[2] ?? '';
$outRemainder = $argv[3] ?? '';
$dryRun = ($argv[4] ?? '') === '--dry';
if ($src === '' || !is_file($src)) {
    fwrite(STDERR, "usage: split-css.php site.css critical.css remainder.css [--dry]\n");
    exit(1);
}
$lines = file($src, FILE_IGNORE_NEW_LINES);
$n = count($lines);

$opens = [];
foreach ($lines as $i => $line) {
    if (preg_match('/^@layer ([a-z]+) \{$/', $line, $m)) {
        $opens[] = [$i, $m[1]];
    }
}
$blocks = [];
foreach ($opens as [$start, $name]) {
    $end = null;
    for ($j = $start + 1; $j < $n; $j++) {
        if ($lines[$j] === '}') {
            $end = $j;
            break;
        }
    }
    if ($end === null) {
        fwrite(STDERR, "unterminated layer $name at line " . ($start + 1) . "\n");
        exit(1);
    }
    $blocks[] = ['name' => $name, 'open' => $start, 'close' => $end];
}
$names = array_column($blocks, 'name');
$expected = ['tokens', 'reset', 'base', 'layout', 'components', 'utilities', 'overrides', 'components'];
if ($names !== $expected) {
    fwrite(STDERR, 'unexpected layer shape: ' . implode(',', $names) . "\n");
    exit(1);
}
[$tokens, $reset, $base, $layout, $components, $utilities, $overrides, $intro] = $blocks;

$criticalRanges = [
    ['.announcement', '.cart-line'],
    ['.field', '.form'],
    ['.filters', '.chips'],
    ['.toolbar', '.gallery'],
    ['.gallery', '.insta'],
    ['.placeholder', '.prose'],
    ['.qty', '.review'],
    ['.site-header', '.skeleton'],
    ['.stars', '.timeline'],
    ['.trust', '.voice'],
];

function find_line(array $lines, string $needle, int $from, int $to): int
{
    $pattern = '/^  ' . preg_quote($needle, '/') . '(?![\\w-])/';
    for ($i = $from; $i <= $to; $i++) {
        if (preg_match($pattern, $lines[$i]) === 1) {
            return $i;
        }
    }
    fwrite(STDERR, "marker not found: $needle\n");
    exit(1);
}

$compStart = $components['open'] + 1;
$compEnd = $components['close'] - 1;
$critical = [];
$remainder = [];
$isCritical = array_fill($compStart, $compEnd - $compStart + 1, false);
foreach ($criticalRanges as [$fromNeedle, $toNeedle]) {
    $from = find_line($lines, $fromNeedle, $compStart, $compEnd);
    $to = find_line($lines, $toNeedle, $from + 1, $compEnd) - 1;
    for ($i = $from; $i <= $to; $i++) {
        $isCritical[$i] = true;
    }
}
$compCritical = [];
$compRemainder = [];
for ($i = $compStart; $i <= $compEnd; $i++) {
    if ($isCritical[$i]) {
        $compCritical[] = $lines[$i];
    } else {
        $compRemainder[] = $lines[$i];
    }
}

$ovStart = $overrides['open'] + 1;
$ovEnd = $overrides['close'] - 1;
$ovOverlay = find_line($lines, '.drawer[hidden]', $ovStart, $ovEnd);
$ovReveal = find_line($lines, 'html.js .sf-reveal', $ovOverlay, $ovEnd);
$tLight = find_line($lines, '.t-light', $ovReveal, $ovEnd);
$ovCritical = array_merge(array_slice($lines, $ovStart, $ovOverlay - $ovStart), array_slice($lines, $ovReveal, $tLight - $ovReveal));
$ovRemainder = array_merge(array_slice($lines, $ovOverlay, $ovReveal - $ovOverlay), [''], array_slice($lines, $tLight, $ovEnd - $tLight + 1));

$trimEdges = static function (array $part): array {
    while ($part !== [] && trim($part[0]) === '') {
        array_shift($part);
    }
    while ($part !== [] && trim(end($part)) === '') {
        array_pop($part);
    }
    return $part;
};

$layerOrder = $lines[0];
$prefix = array_slice($lines, 1, $components['open'] - 1);
$criticalOut = array_merge(
    [$layerOrder],
    $trimEdges($prefix) === [] ? [] : array_merge([''], $trimEdges($prefix)),
    ['', '@layer components {'],
    $trimEdges($compCritical),
    ['}', ''],
    array_slice($lines, $utilities['open'], $utilities['close'] - $utilities['open'] + 1),
    ['', '@layer overrides {'],
    $trimEdges($ovCritical),
    ['}', ''],
    array_slice($lines, $intro['open'], $intro['close'] - $intro['open'] + 1)
);
$remainderOut = array_merge(
    [$layerOrder],
    ['', '@layer components {'],
    $trimEdges($compRemainder),
    ['}', ''],
    ['@layer overrides {'],
    $trimEdges($ovRemainder),
    ['}']
);

$tail = array_slice($lines, $intro['close'] + 1);
if ($trimEdges($tail) !== []) {
    fwrite(STDERR, "unexpected content after the last layer block\n");
    exit(1);
}

$multiset = static function (array $list): array {
    $out = [];
    foreach ($list as $line) {
        if (trim($line) === '' || $line === '}' || $line === '@layer components {' || $line === '@layer overrides {' || str_starts_with($line, '@layer tokens, ')) {
            continue;
        }
        $out[$line] = ($out[$line] ?? 0) + 1;
    }
    ksort($out);
    return $out;
};
if ($multiset($lines) !== $multiset(array_merge($criticalOut, $remainderOut))) {
    fwrite(STDERR, "line multiset mismatch between source and split output\n");
    exit(1);
}

$criticalText = implode("\n", $criticalOut) . "\n";
$remainderText = implode("\n", $remainderOut) . "\n";
printf("source %d bytes / %d lines\n", strlen(implode("\n", $lines) . "\n"), $n);
printf("critical %d bytes / %d lines (gzip %d)\n", strlen($criticalText), count($criticalOut), strlen(gzencode($criticalText, 6)));
printf("remainder %d bytes / %d lines (gzip %d)\n", strlen($remainderText), count($remainderOut), strlen(gzencode($remainderText, 6)));
if ($dryRun) {
    exit(0);
}
file_put_contents($outCritical, $criticalText);
file_put_contents($outRemainder, $remainderText);
echo "written\n";
