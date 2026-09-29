<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Run from the command line: php dev-tools/extract-default-copy.php [--check]\n");
}

const COPY_SETTINGS_ALLOW = [
    'hero_trust_line',
    'newsletter_heading',
    'newsletter_text',
    'quiz_band_text',
    'cod_note',
    'manual_payment_note',
    'maintenance_message',
    'whatsapp_reply_template',
    'contact_reply_time',
    'returns_days',
    'footer_blurb',
];
const COPY_PAGE_SKIP = ['id', 'created_at', 'updated_at'];

$repoRoot = dirname(__DIR__);
$seedPath = $repoRoot . '/site/db/seed.sql';
$dataPath = $repoRoot . '/site/app/data/default-copy.php';
$checkOnly = in_array('--check', array_slice($argv, 1), true);

function copy_fail(string $message): never
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function copy_split_statements(string $sql): array
{
    $statements = [];
    $length = strlen($sql);
    $buffer = '';
    $quote = null;
    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        if ($quote !== null) {
            $buffer .= $char;
            if ($char === '\\' && $i + 1 < $length) {
                $buffer .= $sql[++$i];
                continue;
            }
            if ($char === $quote) {
                $quote = null;
            }
            continue;
        }
        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $buffer .= $char;
            continue;
        }
        if ($char === '-' && substr($sql, $i, 2) === '--' && ($i + 2 >= $length || ctype_space($sql[$i + 2]))) {
            $end = strpos($sql, "\n", $i);
            $i = $end === false ? $length : $end;
            continue;
        }
        if ($char === '#') {
            $end = strpos($sql, "\n", $i);
            $i = $end === false ? $length : $end;
            continue;
        }
        if ($char === '/' && substr($sql, $i, 2) === '/*') {
            $end = strpos($sql, '*/', $i + 2);
            $i = $end === false ? $length : $end + 1;
            continue;
        }
        if ($char === ';') {
            if (trim($buffer) !== '') {
                $statements[] = trim($buffer);
            }
            $buffer = '';
            continue;
        }
        $buffer .= $char;
    }
    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }
    return $statements;
}

function copy_decode_string(string $raw, string $quote): string
{
    $out = '';
    $length = strlen($raw);
    $map = ['0' => "\0", 'b' => "\x08", 'n' => "\n", 'r' => "\r", 't' => "\t", 'Z' => "\x1a", '\\' => '\\', '%' => '\\%', '_' => '\\_'];
    for ($i = 0; $i < $length; $i++) {
        $char = $raw[$i];
        if ($char === '\\' && $i + 1 < $length) {
            $next = $raw[++$i];
            $out .= $map[$next] ?? $next;
            continue;
        }
        if ($char === $quote && $i + 1 < $length && $raw[$i + 1] === $quote) {
            $out .= $quote;
            $i++;
            continue;
        }
        $out .= $char;
    }
    return $out;
}

function copy_read_string(string $text, int &$pos): string
{
    $quote = $text[$pos];
    $length = strlen($text);
    $start = ++$pos;
    while ($pos < $length) {
        $char = $text[$pos];
        if ($char === '\\') {
            $pos += 2;
            continue;
        }
        if ($char === $quote) {
            if ($pos + 1 < $length && $text[$pos + 1] === $quote) {
                $pos += 2;
                continue;
            }
            $raw = substr($text, $start, $pos - $start);
            $pos++;
            return copy_decode_string($raw, $quote);
        }
        $pos++;
    }
    copy_fail('Unterminated string literal in seed.sql');
}

function copy_read_expression(string $text, int &$pos): string
{
    $length = strlen($text);
    $start = $pos;
    $depth = 0;
    while ($pos < $length) {
        $char = $text[$pos];
        if ($char === "'" || $char === '"') {
            copy_read_string($text, $pos);
            continue;
        }
        if ($char === '(') {
            $depth++;
        } elseif ($char === ')') {
            $depth--;
            if ($depth === 0) {
                $pos++;
                return substr($text, $start, $pos - $start);
            }
        }
        $pos++;
    }
    copy_fail('Unbalanced parentheses in seed.sql');
}

function copy_skip_space(string $text, int &$pos): void
{
    $length = strlen($text);
    while ($pos < $length && ctype_space($text[$pos])) {
        $pos++;
    }
}

function copy_read_values(string $text, int &$pos): array
{
    $values = [];
    $length = strlen($text);
    while ($pos < $length) {
        copy_skip_space($text, $pos);
        if ($pos >= $length) {
            break;
        }
        $char = $text[$pos];
        if ($char === "'" || $char === '"') {
            $values[] = copy_read_string($text, $pos);
        } elseif ($char === '(') {
            $values[] = ['expr' => copy_read_expression($text, $pos)];
        } else {
            $start = $pos;
            while ($pos < $length && $text[$pos] !== ',' && $text[$pos] !== ')') {
                if ($text[$pos] === '(') {
                    copy_read_expression($text, $pos);
                    continue;
                }
                $pos++;
            }
            $token = trim(substr($text, $start, $pos - $start));
            if (strcasecmp($token, 'NULL') === 0) {
                $values[] = null;
            } elseif (preg_match('/^-?\d+$/', $token)) {
                $values[] = (int) $token;
            } else {
                $values[] = $token;
            }
        }
        copy_skip_space($text, $pos);
        if ($pos >= $length) {
            copy_fail('Value list does not close in seed.sql');
        }
        if ($text[$pos] === ',') {
            $pos++;
            continue;
        }
        if ($text[$pos] === ')') {
            $pos++;
            return $values;
        }
        copy_fail('Unexpected character in value list: ' . $text[$pos]);
    }
    copy_fail('Value list does not close in seed.sql');
}

function copy_parse_insert(string $statement): ?array
{
    if (!preg_match('/^INSERT\s+(?:IGNORE\s+)?INTO\s+`?([a-z_][a-z0-9_]*)`?\s*\(([^)]*)\)\s*VALUES\s*\(/is', $statement, $head, PREG_OFFSET_CAPTURE)) {
        return null;
    }
    $table = strtolower($head[1][0]);
    $columns = array_map(static fn (string $column): string => trim($column, " `\t\r\n"), explode(',', $head[2][0]));
    $length = strlen($statement);
    $pos = $head[0][1] + strlen($head[0][0]);
    $rows = [];
    while (true) {
        $values = copy_read_values($statement, $pos);
        if (count($columns) !== count($values)) {
            copy_fail('Column and value counts differ for ' . $table . ' row ' . (count($rows) + 1) . ' in seed.sql');
        }
        $rows[] = array_combine($columns, $values);
        copy_skip_space($statement, $pos);
        if ($pos >= $length) {
            return ['table' => $table, 'rows' => $rows];
        }
        if ($statement[$pos] !== ',') {
            copy_fail('Unexpected text after the value list for ' . $table . ' in seed.sql: ' . substr($statement, $pos, 40));
        }
        $pos++;
        copy_skip_space($statement, $pos);
        if ($pos >= $length || $statement[$pos] !== '(') {
            copy_fail('Expected another value list after "," for ' . $table . ' in seed.sql');
        }
        $pos++;
    }
}

function copy_collect_row(string $table, array $row, array &$out): void
{
    if ($table === 'settings') {
        $key = (string) ($row['setting_key'] ?? '');
        if (in_array($key, COPY_SETTINGS_ALLOW, true) && array_key_exists('setting_value', $row) && !is_array($row['setting_value'])) {
            $out['settings'][$key] = $row['setting_value'] === null ? null : (string) $row['setting_value'];
        }
        return;
    }
    if ($table !== 'content_pages') {
        return;
    }
    $slug = (string) ($row['slug'] ?? '');
    if ($slug === '') {
        copy_fail('A content_pages row in seed.sql has no slug');
    }
    $page = [];
    foreach ($row as $column => $value) {
        if (in_array($column, COPY_PAGE_SKIP, true)) {
            continue;
        }
        if (is_array($value)) {
            copy_fail('content_pages.' . $column . ' for /' . $slug . ' is an expression, not a literal');
        }
        $page[$column] = $value;
    }
    $out['pages'][$slug] = $page;
}

function copy_collect(string $sql): array
{
    $out = ['pages' => [], 'settings' => []];
    foreach (copy_split_statements($sql) as $statement) {
        $insert = copy_parse_insert($statement);
        if ($insert === null) {
            continue;
        }
        foreach ($insert['rows'] as $row) {
            copy_collect_row($insert['table'], $row, $out);
        }
    }
    return $out;
}

function copy_export(mixed $value, int $depth): string
{
    if (is_array($value)) {
        if ($value === []) {
            return '[]';
        }
        $pad = str_repeat('    ', $depth + 1);
        $lines = ["["];
        foreach ($value as $key => $item) {
            $lines[] = $pad . var_export($key, true) . ' => ' . copy_export($item, $depth + 1) . ',';
        }
        $lines[] = str_repeat('    ', $depth) . ']';
        return implode("\n", $lines);
    }
    if ($value === null) {
        return 'null';
    }
    if (is_int($value)) {
        return (string) $value;
    }
    return var_export((string) $value, true);
}

function copy_render(array $copy): string
{
    return "<?php\ndefined('SKYFR') || exit;\n\nreturn " . copy_export($copy, 0) . ";\n";
}

function copy_loadable(string $path): bool
{
    if (!defined('SKYFR')) {
        define('SKYFR', 1);
    }
    $loaded = require $path;
    return is_array($loaded) && is_array($loaded['pages'] ?? null) && is_array($loaded['settings'] ?? null);
}

if (!is_file($seedPath)) {
    copy_fail('seed.sql not found at ' . $seedPath);
}
$copy = copy_collect((string) file_get_contents($seedPath));
if ($copy['pages'] === []) {
    copy_fail('seed.sql yielded no content_pages rows; the extractor did not recognise the statements');
}
if ($copy['settings'] === []) {
    copy_fail('seed.sql yielded none of the allow-listed wording settings');
}
$rendered = copy_render($copy);
$summary = count($copy['pages']) . ' pages, ' . count($copy['settings']) . ' settings';

if ($checkOnly) {
    if (!is_file($dataPath)) {
        copy_fail('site/app/data/default-copy.php is missing. Run: php dev-tools/extract-default-copy.php');
    }
    if ((string) file_get_contents($dataPath) !== $rendered) {
        copy_fail('site/app/data/default-copy.php is stale relative to site/db/seed.sql. Run: php dev-tools/extract-default-copy.php');
    }
    if (!copy_loadable($dataPath)) {
        copy_fail('site/app/data/default-copy.php does not return the expected array');
    }
    echo "default-copy.php is up to date ($summary).\n";
    exit(0);
}

$dataDir = dirname($dataPath);
if (!is_dir($dataDir) && !mkdir($dataDir, 0755, true)) {
    copy_fail('Cannot create ' . $dataDir);
}
if (file_put_contents($dataPath, $rendered, LOCK_EX) === false) {
    copy_fail('Cannot write ' . $dataPath);
}
if (!copy_loadable($dataPath)) {
    copy_fail('The written file does not return the expected array');
}
echo "Wrote site/app/data/default-copy.php ($summary).\n";
