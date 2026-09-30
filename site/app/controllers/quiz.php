<?php
defined('SKYFR') || exit;

partial('product-card.php', []);

const QUIZ_RESULT_COUNT = 3;
const QUIZ_MAX_SCORE = 33;
const QUIZ_QUESTIONS = [
    ['key' => 'who', 'question' => 'Who is this fragrance for?', 'helper' => 'We will weight the matches towards them.', 'options' => [
        ['For him', 'Grounded, warm, built to last', 'compass'],
        ['For her', 'Luminous, soft, certain', 'sparkle'],
        ['Doesn’t matter — surprise me', 'Show me everything', 'star'],
    ]],
    ['key' => 'where', 'question' => 'Where will you wear it most?', 'helper' => 'Pick the occasion that comes up most often.', 'options' => [
        ['Work and daytime', 'Office, errands, daylight', 'droplet'],
        ['Evenings and dinners', 'Dates, late tables, city nights', 'star'],
        ['Weddings and big occasions', 'Shaadi season and celebrations', 'sparkle'],
        ['Every day, all day', 'One bottle for everything', 'refresh'],
    ]],
    ['key' => 'smell', 'question' => 'Which of these smells best to you?', 'helper' => 'Go with your first instinct.', 'options' => [
        ['Citrus, sea air, clean linen', 'Fresh and bright', 'droplet'],
        ['Rose, jasmine, soft petals', 'Floral and tender', 'sparkle'],
        ['Oud, leather, incense', 'Deep and smoky', 'star'],
        ['Vanilla, amber, warm spice', 'Warm and sweet', 'quote'],
        ['Rain, grass, cut wood', 'Green and earthy', 'compass'],
    ]],
    ['key' => 'presence', 'question' => 'How much presence do you want?', 'helper' => 'How far should people be able to notice it?', 'options' => [
        ['Close to the skin', 'Only people near me notice', 'droplet'],
        ['Noticeable, not loud', 'An arm’s length', 'star'],
        ['I want to be remembered', 'Fills the room', 'sparkle'],
    ]],
    ['key' => 'when', 'question' => 'When do you wear fragrance most?', 'helper' => 'Heat changes everything about how a scent behaves.', 'options' => [
        ['Karachi summer heat', 'April to October', 'droplet'],
        ['Winter and cold evenings', 'November to February', 'star'],
        ['All year round', 'Whatever the weather', 'refresh'],
    ]],
];
const QUIZ_OCCASION_WORDS = [1 => ['office', 'day', 'daily'], 2 => ['evening', 'dinner', 'date'], 3 => ['wedding', 'occasion', 'special', 'signature'], 4 => ['daily', 'everyday', 'signature']];
const QUIZ_FAMILY_WORDS = [1 => ['fresh', 'citrus', 'aquatic', 'marine', 'musk'], 2 => ['floral', 'rose', 'jasmine', 'white floral'], 3 => ['oud', 'leather', 'smoky', 'woody', 'incense'], 4 => ['amber', 'gourmand', 'oriental', 'vanilla', 'spicy'], 5 => ['green', 'vetiver', 'petrichor', 'aromatic', 'woody']];
const QUIZ_SILLAGE_TARGET = [1 => 2, 2 => 3, 3 => 5];
const QUIZ_SEASON_WORDS = [1 => ['summer', 'spring', 'all'], 2 => ['winter', 'autumn', 'all'], 3 => ['all', 'year']];
const QUIZ_SMELL_READOUT = [1 => 'Fresh and clean', 2 => 'Soft and floral', 3 => 'Deep and smoky', 4 => 'Warm and sweet', 5 => 'Green and earthy'];
const QUIZ_PRESENCE_READOUT = [1 => 'kept close to the skin', 2 => 'noticeable without being loud', 3 => 'made to be remembered'];
const QUIZ_LONGEVITY_WORDS = [1 => 'Up to 2 hours', 2 => '3–4 hours', 3 => '5–6 hours', 4 => '7–9 hours', 5 => '10+ hours'];
const QUIZ_SILLAGE_WORDS = [1 => 'Intimate', 2 => 'Soft', 3 => 'Moderate', 4 => 'Strong', 5 => 'Very strong'];

function quiz_answer_code(array $answers): string
{
    return implode('-', $answers);
}

function quiz_parse_answers(): ?array
{
    $raw = request_query('a');
    if (is_string($raw) && preg_match('/^[1-5](?:-[1-5]){4}$/', $raw)) {
        $answers = array_map('intval', explode('-', $raw));
    } else {
        $answers = [];
        foreach (QUIZ_QUESTIONS as $i => $question) {
            $value = request_query('q' . ($i + 1));
            if (!is_string($value) || !preg_match('/^[1-5]$/', $value)) {
                return null;
            }
            $answers[] = (int) $value;
        }
        redirect('/scent-finder/result?a=' . quiz_answer_code($answers), 302);
    }
    foreach ($answers as $i => $answer) {
        if ($answer > count(QUIZ_QUESTIONS[$i]['options'])) {
            return null;
        }
    }
    return $answers;
}

function quiz_like_any(string $column, array $words, array &$bind): string
{
    $parts = [];
    foreach ($words as $word) {
        $name = 'w' . count($bind);
        $bind[$name] = '%' . addcslashes($word, '%_\\') . '%';
        $parts[] = 'LOWER(COALESCE(' . $column . ", '')) LIKE :" . $name;
    }
    return '(' . implode(' OR ', $parts) . ')';
}

function quiz_score_sql(array $answers, array &$bind): string
{
    [$who, $where, $smell, $presence, $when] = $answers;
    $terms = [];
    if ($who === 3) {
        $terms[] = "(CASE WHEN p.gender = 'unisex' THEN 3 ELSE 1 END)";
    } else {
        $bind['gender'] = $who === 1 ? 'him' : 'her';
        $terms[] = "(CASE WHEN p.gender = :gender THEN 6 WHEN p.gender = 'unisex' THEN 3 ELSE -8 END)";
    }
    $terms[] = '(CASE WHEN ' . quiz_like_any('p.occasion', QUIZ_OCCASION_WORDS[$where], $bind) . ' THEN 5 ELSE 0 END)';
    $terms[] = '(CASE WHEN ' . quiz_like_any('p.scent_family', QUIZ_FAMILY_WORDS[$smell], $bind) . ' THEN 8 ELSE 0 END)';
    $terms[] = '(CASE WHEN ' . quiz_like_any("CONCAT_WS(' ', p.notes_top, p.notes_heart, p.notes_base)", QUIZ_FAMILY_WORDS[$smell], $bind) . ' THEN 4 ELSE 0 END)';
    $bind['sillage_target'] = QUIZ_SILLAGE_TARGET[$presence];
    $terms[] = 'GREATEST(-4, 4 - 2 * ABS(COALESCE(p.sillage, 3) - :sillage_target))';
    $terms[] = '(CASE WHEN ' . quiz_like_any('p.best_season', QUIZ_SEASON_WORDS[$when], $bind) . ' THEN 4 ELSE 0 END)';
    if ($when === 1) {
        $terms[] = '(CASE WHEN COALESCE(p.longevity, 3) <= 3 THEN 2 ELSE 0 END)';
    } elseif ($when === 2) {
        $terms[] = '(CASE WHEN COALESCE(p.longevity, 3) >= 4 THEN 2 ELSE 0 END)';
    }
    return '(' . implode(' + ', $terms) . ')';
}

function quiz_attach_details(array $rows): array
{
    if ($rows === []) {
        return [];
    }
    $bind = [];
    $placeholders = [];
    foreach ($rows as $i => $row) {
        $placeholders[] = ':p' . $i;
        $bind['p' . $i] = (int) $row['id'];
    }
    $in = implode(', ', $placeholders);
    $sizesByProduct = [];
    foreach (db_fetch_all('SELECT id, product_id, size_label, size_ml, price, sale_price, stock, is_active FROM product_sizes WHERE is_active = 1 AND product_id IN (' . $in . ') ORDER BY product_id ASC, sort_order ASC, size_ml ASC', $bind) as $size) {
        $sizesByProduct[(int) $size['product_id']][] = $size;
    }
    $imagesByProduct = [];
    foreach (db_fetch_all('SELECT product_id, filename, alt_text FROM product_images WHERE product_id IN (' . $in . ') ORDER BY product_id ASC, is_primary DESC, sort_order ASC, id ASC', $bind) as $image) {
        $imagesByProduct[(int) $image['product_id']][] = $image;
    }
    foreach ($rows as &$row) {
        $productId = (int) $row['id'];
        $row['sizes'] = $sizesByProduct[$productId] ?? [];
        $images = $imagesByProduct[$productId] ?? [];
        $row['image'] = $images[0]['filename'] ?? null;
        $row['image_alt'] = $images[0]['alt_text'] ?? '';
        $row['image_alt_filename'] = $images[1]['filename'] ?? null;
        $row['url'] = url('/product/' . $row['slug']);
    }
    unset($row);
    return $rows;
}

function quiz_note_list(?string $notes): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string) $notes)), static fn (string $n): bool => $n !== ''));
}

function quiz_hero_card(array $product): array
{
    $pricing = product_card_pricing($product);
    $longevity = max(0, min(5, (int) ($product['longevity'] ?? 0)));
    $sillage = max(0, min(5, (int) ($product['sillage'] ?? 0)));
    $meters = [];
    if ($longevity > 0) {
        $meters[] = ['label' => 'Longevity', 'value' => $longevity, 'word' => QUIZ_LONGEVITY_WORDS[$longevity], 'aria' => 'Longevity: ' . $longevity . ' out of 5, ' . QUIZ_LONGEVITY_WORDS[$longevity]];
    }
    if ($sillage > 0) {
        $meters[] = ['label' => 'Sillage', 'value' => $sillage, 'word' => QUIZ_SILLAGE_WORDS[$sillage], 'aria' => 'Sillage: ' . $sillage . ' out of 5, ' . QUIZ_SILLAGE_WORDS[$sillage]];
    }
    $cheapest = $pricing['cheapest'];
    return [
        'product' => $product,
        'image_set' => product_card_image_set((int) $product['id'], $product['image'] ?? null),
        'alt' => product_card_alt($product, $pricing['labels']),
        'notes' => ['top' => quiz_note_list($product['notes_top'] ?? null), 'heart' => quiz_note_list($product['notes_heart'] ?? null), 'base' => quiz_note_list($product['notes_base'] ?? null)],
        'meters' => $meters,
        'size_labels' => $pricing['labels'],
        'sold_out' => $pricing['stock'] <= 0,
        'single_size_id' => count($pricing['labels']) === 1 && $cheapest !== null ? (int) $cheapest['id'] : 0,
        'is_sale' => $pricing['is_sale'],
        'price_display' => $cheapest !== null ? money(money_from_paisa($cheapest['effective'])) : '',
        'was_display' => $pricing['is_sale'] ? money(money_from_paisa($cheapest['list'])) : '',
        'from' => count($pricing['labels']) > 1,
        'match' => isset($product['score']) ? max(0, min(100, (int) round((int) $product['score'] * 100 / QUIZ_MAX_SCORE))) : 0,
    ];
}

$storeName = (string) setting('store_name', 'Sky Fragrances');
$whatsappDigits = preg_replace('/\D+/', '', (string) setting('whatsapp', '')) ?? '';

if ($route['name'] === 'quiz') {
    $head['title'] = 'Scent Finder — Find Your Perfume | ' . $storeName;
    $head['meta_description'] = 'Answer five quick questions and let the ' . $storeName . ' Scent Finder match you with the three perfumes made for you.';
    $head['canonical'] = canonical('/scent-finder');
    $head['jsonld'][] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Scent Finder', 'item' => canonical('/scent-finder')],
    ]];
    render($route['view'], [
        'heading' => 'Find Your Scent',
        'questions' => QUIZ_QUESTIONS,
        'total' => count(QUIZ_QUESTIONS),
        'resultUrl' => url('/scent-finder/result'),
    ], $head);
}

$answers = quiz_parse_answers();
if ($answers === null) {
    redirect('/scent-finder', 302);
}
$answerCode = quiz_answer_code($answers);

$bind = [];
$scoreSql = quiz_score_sql($answers, $bind);
$productSelect = 'p.id, p.name, p.slug, p.gender, p.scent_family, p.short_description, p.notes_top, p.notes_heart, p.notes_base, p.longevity, p.sillage, p.is_new, p.is_featured, p.rating_avg, p.rating_count, p.published_at, c.name AS collection_name, c.slug AS collection_slug';
$sizesJoin = 'JOIN (SELECT product_id, SUM(stock) AS total_stock FROM product_sizes WHERE is_active = 1 GROUP BY product_id) s ON s.product_id = p.id LEFT JOIN collections c ON c.id = p.collection_id AND c.is_active = 1';
$scored = db_fetch_all(
    'SELECT ' . $productSelect . ', ' . $scoreSql . ' AS score FROM products p ' . $sizesJoin
    . ' WHERE p.is_active = 1 AND p.deleted_at IS NULL AND s.total_stock > 0'
    . ' ORDER BY score DESC, p.is_featured DESC, p.sales_count DESC, p.rating_avg DESC, p.id DESC LIMIT ' . QUIZ_RESULT_COUNT,
    $bind
);
$matches = array_values(array_filter($scored, static fn (array $row): bool => (int) $row['score'] > 0));

$fillers = [];
if (count($matches) < QUIZ_RESULT_COUNT) {
    $fillBind = [];
    $exclude = '';
    if ($matches !== []) {
        $ids = [];
        foreach ($matches as $i => $match) {
            $ids[] = ':x' . $i;
            $fillBind['x' . $i] = (int) $match['id'];
        }
        $exclude = ' AND p.id NOT IN (' . implode(', ', $ids) . ')';
    }
    $fillers = db_fetch_all(
        'SELECT ' . $productSelect . ' FROM products p ' . $sizesJoin
        . ' WHERE p.is_active = 1 AND p.deleted_at IS NULL AND s.total_stock > 0' . $exclude
        . ' ORDER BY p.sales_count DESC, p.is_featured DESC, p.rating_avg DESC, p.id DESC LIMIT ' . (QUIZ_RESULT_COUNT - count($matches)),
        $fillBind
    );
}
$matches = quiz_attach_details($matches);
$fillers = quiz_attach_details($fillers);
$hero = $matches !== [] ? quiz_hero_card($matches[0]) : null;
$readout = QUIZ_SMELL_READOUT[$answers[2]] . ', ' . QUIZ_PRESENCE_READOUT[$answers[3]] . '.';
$shareUrl = canonical('/scent-finder/result?a=' . $answerCode);

$head['title'] = 'Your Scent Match | ' . $storeName;
$head['meta_description'] = $readout . ' See the three ' . $storeName . ' perfumes the Scent Finder picked.';
$head['robots'] = 'noindex,follow';
$head['canonical'] = $shareUrl;
if ($hero !== null && $hero['image_set'] !== null) {
    $head['og'] = ['image' => SITE_URL . substr(product_card_image_src($hero['image_set'], 'zoom', 'jpg'), strlen(BASE_PATH))];
}

render($route['view'], [
    'heading' => 'Your Scent Match',
    'readout' => $readout,
    'answers' => $answers,
    'answerCode' => $answerCode,
    'hero' => $hero,
    'alsoTrying' => array_slice($matches, 1),
    'fillers' => $fillers,
    'shareUrl' => $shareUrl,
    'retakeUrl' => url('/scent-finder'),
    'whatsappUrl' => $whatsappDigits !== '' ? 'https://wa.me/' . $whatsappDigits . '?text=' . rawurlencode('Assalam-o-Alaikum. I took the Scent Finder and would like a recommendation.') : '',
], $head);
