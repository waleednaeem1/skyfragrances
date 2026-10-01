<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/catalogue.php';

const PAGES_FAQ_TEMPLATE = 'page-faq';

function pages_faq_parse(string $body): array
{
    $items = [];
    preg_match_all('/<h3>(.*?)<\/h3>(.*?)(?=<h3>|$)/is', $body, $pairs, PREG_SET_ORDER);
    foreach ($pairs as $pair) {
        $question = trim(html_entity_decode(strip_tags($pair[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $answer = trim($pair[2]);
        if ($question === '' && $answer === '') {
            continue;
        }
        $items[] = ['question' => $question, 'answer' => pages_faq_answer_to_text($answer)];
    }
    return $items;
}

function pages_faq_answer_to_text(string $html): string
{
    $text = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
    $text = preg_replace('/<\/p>\s*<p>/i', "\n\n", $text) ?? $text;
    $text = preg_replace('/<\/(li|ul|ol|blockquote|h2)>/i', "\n", $text) ?? $text;
    $text = strip_tags($text, '<strong><em><a><b><i>');
    return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function pages_faq_answer_to_html(string $text): string
{
    $text = trim(str_replace("\r\n", "\n", $text));
    if ($text === '') {
        return '';
    }
    if (preg_match('/<(p|ul|ol|blockquote)\b/i', $text)) {
        return $text;
    }
    $paragraphs = preg_split('/\n{2,}/', $text) ?: [];
    $html = '';
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph === '') {
            continue;
        }
        $safe = preg_match('/<(strong|em|a|b|i)\b/i', $paragraph) ? $paragraph : e($paragraph);
        $html .= '<p>' . nl2br($safe, false) . '</p>';
    }
    return $html;
}

function pages_faq_from_post(array &$errors): array
{
    $raw = request_post('faq', []);
    $items = [];
    if (!is_array($raw)) {
        $raw = [];
    }
    $n = 0;
    foreach ($raw as $row) {
        if (!is_array($row)) {
            continue;
        }
        $n++;
        $question = trim(preg_replace('/\s+/u', ' ', (string) ($row['question'] ?? '')) ?? '');
        $answer = trim(str_replace("\r\n", "\n", (string) ($row['answer'] ?? '')));
        if ($question === '' && $answer === '') {
            continue;
        }
        if ($question === '') {
            $errors['faq_' . $n] = 'Question ' . $n . ' needs a question line.';
        } elseif (mb_strlen($question) > 200) {
            $errors['faq_' . $n] = 'Question ' . $n . ' is too long (200 characters max).';
        }
        if ($answer === '') {
            $errors['faq_' . $n] = 'Question ' . $n . ' needs an answer.';
        }
        $items[] = ['question' => mb_substr($question, 0, 200), 'answer' => $answer];
    }
    if ($items === []) {
        $errors['faq_1'] = 'Add at least one question and answer.';
    }
    return $items;
}

function pages_faq_to_body(array $items): string
{
    $body = '';
    foreach ($items as $item) {
        $body .= '<h3>' . e($item['question']) . '</h3>' . pages_faq_answer_to_html($item['answer']);
    }
    return $body;
}

function pages_snapshot(array $page): array
{
    return [
        'title' => (string) $page['title'],
        'heading' => $page['heading'],
        'body_length' => mb_strlen((string) $page['body']),
        'is_active' => (int) $page['is_active'],
        'seo_title' => $page['seo_title'],
        'seo_description' => $page['seo_description'],
    ];
}

const PAGES_DEFAULT_COLUMNS = ['title', 'heading', 'body', 'body_format', 'template', 'seo_title', 'seo_description'];

function pages_body_saved(int $pageId, string $body): bool
{
    $row = db_fetch('SELECT body FROM content_pages WHERE id = :id', ['id' => $pageId]);
    return $row !== null && (string) $row['body'] === $body;
}

function pages_default_row(string $slug): ?array
{
    $defaults = default_copy()['pages'][$slug] ?? null;
    if (!is_array($defaults)) {
        return null;
    }
    $row = [];
    foreach (PAGES_DEFAULT_COLUMNS as $column) {
        if (array_key_exists($column, $defaults)) {
            $row[$column] = $defaults[$column] === null ? null : (string) $defaults[$column];
        }
    }
    return isset($row['title'], $row['body']) && trim($row['body']) !== '' ? $row : null;
}

$routeName = (string) ($route['name'] ?? '');
$now = now_karachi();

if ($routeName === 'admin.pages.restore_default') {
    $slug = strtolower((string) $params['slug']);
    $page = db_fetch('SELECT id, slug, title, heading, body, body_format, template, is_system, is_active, sort_order, seo_title, seo_description FROM content_pages WHERE slug = :slug', ['slug' => $slug]);
    if ($page === null) {
        flash('error', 'There is no page called /' . $slug . '.');
        redirect('/admin/pages', 303);
    }
    $data = pages_default_row($slug);
    if ($data === null) {
        flash('error', 'There is no default text for /' . $page['slug'] . ', so nothing was changed.');
        redirect('/admin/pages/' . $page['slug'], 303);
    }
    $changed = catalogue_changed_fields($page, $data, array_keys($data));
    if ($changed === []) {
        flash('info', $page['title'] . ' already has the default text.');
        redirect('/admin/pages/' . $page['slug'], 303);
    }
    db_transaction(static function () use ($data, $now, $page, $changed): void {
        db_update('content_pages', $data + ['updated_at' => $now], ['id' => (int) $page['id']]);
        catalogue_log('page', (int) $page['id'], 'page.restore_default', 'Restored the default text on /' . $page['slug'] . ' (' . implode(', ', $changed) . ')', pages_snapshot($page), pages_snapshot($data + $page));
    });
    if (!pages_body_saved((int) $page['id'], (string) $data['body'])) {
        log_write('warning', 'Page restore did not persist', ['slug' => $page['slug']]);
        flash('error', 'The default text could not be saved on this server, so /' . $page['slug'] . ' still shows the old wording. Please tell your developer.');
        redirect('/admin/pages/' . $page['slug'], 303);
    }
    flash('success', 'Default text restored on ' . $data['title'] . '. See it live at ' . settings_site_url() . '/' . $page['slug'] . '.');
    redirect('/admin/pages/' . $page['slug'], 303);
}

if ($routeName === 'admin.pages.edit') {
    $slug = strtolower((string) $params['slug']);
    $page = db_fetch('SELECT id, slug, title, heading, body, body_format, template, is_system, is_active, sort_order, seo_title, seo_description, created_at, updated_at FROM content_pages WHERE slug = :slug', ['slug' => $slug]);
    if ($page === null) {
        flash('error', 'There is no page called /' . $slug . '.');
        redirect('/admin/pages', 303);
    }
    $isFaq = $page['template'] === PAGES_FAQ_TEMPLATE;
    $isSystem = (int) $page['is_system'] === 1;
    $form = [
        'title' => (string) $page['title'],
        'heading' => (string) ($page['heading'] ?? ''),
        'body' => (string) $page['body'],
        'is_active' => (int) $page['is_active'],
        'seo_title' => (string) ($page['seo_title'] ?? ''),
        'seo_description' => (string) ($page['seo_description'] ?? ''),
    ];
    $faqItems = $isFaq ? pages_faq_parse((string) $page['body']) : [];
    $errors = [];

    if (request_method() === 'POST') {
        $form['title'] = trim(preg_replace('/\s+/u', ' ', (string) request_post('title', '')) ?? '');
        $form['heading'] = trim(preg_replace('/\s+/u', ' ', (string) request_post('heading', '')) ?? '');
        $form['seo_title'] = trim(preg_replace('/\s+/u', ' ', (string) request_post('seo_title', '')) ?? '');
        $form['seo_description'] = trim(preg_replace('/\s+/u', ' ', (string) request_post('seo_description', '')) ?? '');
        $form['is_active'] = $isSystem ? 1 : (request_post('is_active') !== null ? 1 : 0);
        if ($form['title'] === '') {
            $errors['title'] = 'Give the page a title.';
        } elseif (mb_strlen($form['title']) > 160) {
            $errors['title'] = 'Keep the title under 160 characters.';
        }
        if (mb_strlen($form['heading']) > 160) {
            $errors['heading'] = 'Keep the heading under 160 characters.';
        }
        if (mb_strlen($form['seo_title']) > 160) {
            $errors['seo_title'] = 'Keep the SEO title under 160 characters.';
        }
        if (mb_strlen($form['seo_description']) > 255) {
            $errors['seo_description'] = 'Keep the description under 255 characters.';
        }
        if ($isFaq) {
            $faqItems = pages_faq_from_post($errors);
            $form['body'] = pages_faq_to_body($faqItems);
        } else {
            $form['body'] = (string) request_post('body', '');
        }
        $cleanBody = sanitize_html($form['body']);
        if (trim(strip_tags($cleanBody)) === '') {
            $errors['body'] = $isFaq ? 'Add at least one question and answer.' : 'The page needs some content.';
        } elseif (strlen($cleanBody) > 4000000) {
            $errors['body'] = 'That is too much content for one page.';
        }
        if ($errors === []) {
            $data = [
                'title' => mb_substr($form['title'], 0, 160),
                'heading' => $form['heading'] === '' ? null : mb_substr($form['heading'], 0, 160),
                'body' => $cleanBody,
                'is_active' => $form['is_active'],
                'seo_title' => $form['seo_title'] === '' ? null : mb_substr($form['seo_title'], 0, 160),
                'seo_description' => $form['seo_description'] === '' ? null : mb_substr($form['seo_description'], 0, 255),
            ];
            $after = pages_snapshot($data + $page);
            $before = pages_snapshot($page);
            if ($after === $before && $cleanBody === (string) $page['body']) {
                flash('info', 'No changes to save.');
                redirect('/admin/pages/' . $page['slug'], 303);
            }
            db_transaction(static function () use ($data, $now, $page, $before, $after): void {
                db_update('content_pages', $data + ['updated_at' => $now], ['id' => (int) $page['id']]);
                catalogue_log('page', (int) $page['id'], 'page.update', 'Updated page /' . $page['slug'] . ' (' . implode(', ', catalogue_changed_fields($before, $after, array_keys($after))) . ')', $before, $after);
            });
            if (!pages_body_saved((int) $page['id'], (string) $data['body'])) {
                log_write('warning', 'Page save did not persist', ['slug' => $page['slug']]);
                flash('error', 'The page could not be saved on this server, so /' . $page['slug'] . ' still shows the old wording. Please tell your developer.');
                redirect('/admin/pages/' . $page['slug'], 303);
            }
            flash('success', $data['title'] . ' saved. See it live at ' . settings_site_url() . '/' . $page['slug'] . ($form['is_active'] === 1 ? '' : ' once you make it live') . '.');
            redirect('/admin/pages/' . $page['slug'], 303);
        }
        flash('error', 'Nothing was saved. Fix the ' . count($errors) . ' highlighted field' . (count($errors) === 1 ? '' : 's') . ' and try again.');
    }

    render_admin('page-form.php', [
        'page' => $page,
        'form' => $form,
        'errors' => $errors,
        'isFaq' => $isFaq,
        'isSystem' => $isSystem,
        'faqItems' => $faqItems,
        'siteUrl' => url('/' . $page['slug']),
        'hasDefault' => pages_default_row($slug) !== null,
    ], ['title' => $page['title'], 'body_class' => (string) $route['body_class'], 'back' => '/admin/pages']);
}

$pages = db_fetch_all('SELECT id, slug, title, template, is_system, is_active, sort_order, updated_at, created_at FROM content_pages ORDER BY sort_order ASC, title ASC');
$liveCount = 0;
foreach ($pages as $row) {
    $liveCount += (int) $row['is_active'] === 1 ? 1 : 0;
}

render_admin('pages.php', [
    'pages' => $pages,
    'liveCount' => $liveCount,
], ['title' => 'Pages', 'body_class' => (string) $route['body_class']]);
