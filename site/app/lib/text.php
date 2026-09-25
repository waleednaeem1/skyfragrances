<?php
defined('SKYFR') || exit;

function e(?string $v): string
{
    return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

function ejs(mixed $v): string
{
    $json = json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    return $json === false ? 'null' : $json;
}

function eu(string $v): string
{
    return rawurlencode($v);
}

function slugify(string $s): string
{
    $s = trim(mb_strtolower($s, 'UTF-8'));
    if (function_exists('transliterator_transliterate')) {
        $ascii = transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
    } else {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    }
    if (is_string($ascii) && $ascii !== '') {
        $s = mb_strtolower($ascii, 'UTF-8');
    }
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    $s = trim($s, '-');
    return substr($s, 0, 190);
}

function truncate_on_word(string $s, int $chars): string
{
    $s = trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
    if (mb_strlen($s, 'UTF-8') <= $chars) {
        return $s;
    }
    $cut = mb_substr($s, 0, max(1, $chars - 1), 'UTF-8');
    $lastSpace = mb_strrpos($cut, ' ', 0, 'UTF-8');
    if ($lastSpace !== false && $lastSpace > (int) ($chars * 0.5)) {
        $cut = mb_substr($cut, 0, $lastSpace, 'UTF-8');
    }
    return rtrim($cut, " ,;:.-–—") . '…';
}

function excerpt(string $s, int $chars): string
{
    $plain = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return truncate_on_word($plain, $chars);
}

function phone_normalize(string $raw): string
{
    $digits = preg_replace('/\D+/', '', $raw) ?? '';
    if ($digits === '') {
        return '';
    }
    if ($digits[0] === '0') {
        $digits = substr($digits, 1);
    }
    if (str_starts_with($digits, '92') && strlen($digits) > 10) {
        $digits = substr($digits, 2);
    }
    return '+92' . $digits;
}

function date_short(?string $datetime): string
{
    return $datetime ? date('d M Y', strtotime($datetime)) : '';
}

function date_long(?string $datetime): string
{
    return $datetime ? date('d M Y, g:i A', strtotime($datetime)) : '';
}

function date_iso(?string $datetime): string
{
    return $datetime ? date('Y-m-d\TH:i:s', strtotime($datetime)) . '+05:00' : '';
}

const SANITIZE_HTML_TAGS = ['p', 'br', 'strong', 'em', 'b', 'i', 'ul', 'ol', 'li', 'a', 'h2', 'h3', 'blockquote'];

function sanitize_html_href_ok(string $href): bool
{
    $href = trim($href);
    if ($href === '' || str_starts_with($href, '//') || preg_match('/[\x00-\x1F\x7F]/', $href)) {
        return false;
    }
    if ($href[0] === '/' || $href[0] === '#') {
        return true;
    }
    $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https', 'mailto'], true);
}

function sanitize_html_node(DOMNode $node): void
{
    for ($child = $node->lastChild; $child !== null; $child = $previous) {
        $previous = $child->previousSibling;
        if ($child instanceof DOMElement) {
            sanitize_html_node($child);
            $tag = strtolower($child->tagName);
            if (!in_array($tag, SANITIZE_HTML_TAGS, true)) {
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'noscript', 'template', 'svg', 'math'], true)) {
                    $node->removeChild($child);
                    continue;
                }
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            $href = $tag === 'a' && $child->hasAttribute('href') ? $child->getAttribute('href') : null;
            foreach (iterator_to_array($child->attributes) as $attribute) {
                $child->removeAttribute($attribute->nodeName);
            }
            if ($tag === 'a' && $href !== null && sanitize_html_href_ok($href)) {
                $child->setAttribute('href', trim($href));
                $child->setAttribute('rel', 'nofollow noopener');
            }
            continue;
        }
        if (!$child instanceof DOMText) {
            $node->removeChild($child);
        }
    }
}

function sanitize_html(?string $html): string
{
    $html = trim((string) $html);
    if ($html === '') {
        return '';
    }
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>', LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$loaded) {
        return e($html);
    }
    $body = $document->getElementsByTagName('body')->item(0);
    if ($body === null) {
        return '';
    }
    sanitize_html_node($body);
    $out = '';
    foreach ($body->childNodes as $child) {
        $out .= $document->saveHTML($child);
    }
    return trim($out);
}
