<?php
defined('SKYFR') || exit;

$slug = ltrim($route['path'], '/');
$page = db_fetch(
    'SELECT id, slug, title, heading, body, body_format, template, seo_title, seo_description, updated_at FROM content_pages WHERE slug = :slug AND is_active = 1',
    ['slug' => $slug]
);

if ($page === null) {
    $renamed = db_fetch('SELECT id, new_slug FROM slug_redirects WHERE entity_type = :type AND old_slug = :slug', ['type' => 'page', 'slug' => $slug]);
    if ($renamed !== null) {
        db_query('UPDATE slug_redirects SET hit_count = hit_count + 1 WHERE id = :id', ['id' => $renamed['id']]);
        redirect('/' . $renamed['new_slug'], 301);
    }
    abort(404);
}

$body = sanitize_html((string) $page['body']);
$faqItems = [];
if ($route['name'] === 'page.faq') {
    preg_match_all('/<h3>(.*?)<\/h3>(.*?)(?=<h3>|$)/is', $body, $pairs, PREG_SET_ORDER);
    foreach ($pairs as $pair) {
        $question = trim(html_entity_decode(strip_tags($pair[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $answer = trim($pair[2]);
        if ($question === '' || $answer === '') {
            continue;
        }
        $faqItems[] = ['question' => $question, 'answer' => $answer];
    }
}

function page_meta_description(array $page, string $body): string
{
    $own = trim((string) ($page['seo_description'] ?? ''));
    if ($own !== '') {
        return $own;
    }
    $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    $built = '';
    foreach (preg_split('/(?<=[.!?])\s+/u', $plain) ?: [] as $sentence) {
        $candidate = trim($built . ' ' . $sentence);
        if ($candidate === '' || mb_strlen($candidate, 'UTF-8') > 155) {
            break;
        }
        $built = $candidate;
    }
    return preg_match('/[.!?]$/u', $built) ? $built : '';
}

function page_sections(string $body): array
{
    $sections = [];
    $html = preg_replace_callback('#<h2([^>]*)>(.*?)</h2>#is', static function (array $match) use (&$sections): string {
        $text = trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($text === '' || preg_match('/\bid\s*=/i', $match[1])) {
            return $match[0];
        }
        $id = 'section-' . (slugify($text) ?: (string) (count($sections) + 1));
        $sections[] = ['id' => $id, 'label' => $text];
        return '<h2 id="' . e($id) . '"' . $match[1] . '>' . $match[2] . '</h2>';
    }, $body) ?? $body;
    return [$html, $sections];
}

$heading = (string) ($page['heading'] ?: $page['title']);
$title = (string) ($page['seo_title'] ?: $page['title']);
$head['title'] = str_ends_with($title, 'Sky Fragrances') ? $title : $title . ' | Sky Fragrances';
$head['meta_description'] = page_meta_description($page, $body);
$head['canonical'] = canonical('/' . $page['slug']);
$head['jsonld'][] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => $heading, 'item' => canonical('/' . $page['slug'])],
]];

if ($faqItems !== []) {
    $head['jsonld'][] = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(static fn (array $item): array => [
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(html_entity_decode(strip_tags($item['answer']), ENT_QUOTES | ENT_HTML5, 'UTF-8'))],
        ], $faqItems),
    ];
}

[$body, $sections] = page_sections($body);
$stats = [];
if ($page['slug'] === 'about') {
    $productCount = (int) db_fetch_column('SELECT COUNT(*) FROM products WHERE is_active = 1 AND deleted_at IS NULL');
    $returnsDays = max(0, setting_int('returns_days', 7));
    $stats = array_values(array_filter([
        $productCount > 0 ? [(string) $productCount, $productCount === 1 ? 'fragrance in the studio' : 'fragrances in the studio'] : null,
        [preg_replace('/\s*working days$/u', '', (string) setting('delivery_time', '2–4 working days')), 'working days to your door'],
        $returnsDays > 0 ? [(string) $returnsDays, 'day exchange on sealed bottles'] : null,
    ]));
}

render($route['view'], [
    'heading' => $page['heading'] ?: $page['title'],
    'page' => $page,
    'body' => $body,
    'sections' => $sections,
    'stats' => $stats,
    'faqItems' => $faqItems,
    'showUpdated' => in_array($page['slug'], ['privacy', 'terms', 'returns'], true),
    'updatedAt' => date_short($page['updated_at']),
], $head);
