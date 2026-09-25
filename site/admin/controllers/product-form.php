<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/catalogue.php';

function product_form_defaults(): array
{
    return [
        'id' => null, 'name' => '', 'slug' => '', 'collection_id' => '', 'gender' => 'unisex', 'scent_family' => '',
        'short_description' => '', 'description' => '', 'notes_top' => '', 'notes_heart' => '', 'notes_base' => '',
        'longevity' => '', 'sillage' => '', 'best_season' => '', 'occasion' => '', 'is_featured' => 0, 'is_new' => 0,
        'is_active' => 1, 'sort_order' => 0, 'published_at' => date('Y-m-d'), 'seo_title' => '', 'seo_description' => '',
    ];
}

function product_form_size_defaults(): array
{
    return ['id' => '', 'size_label' => '', 'size_ml' => '', 'sku' => '', 'price' => '', 'sale_price' => '', 'stock' => '0', 'low_stock_threshold' => ''];
}

function product_form_sizes_from_post(): array
{
    $raw = request_post('sizes', []);
    $rows = [];
    if (!is_array($raw)) {
        return $rows;
    }
    foreach ($raw as $row) {
        if (!is_array($row)) {
            continue;
        }
        $clean = product_form_size_defaults();
        foreach ($clean as $key => $default) {
            $clean[$key] = trim((string) ($row[$key] ?? $default));
        }
        $rows[] = $clean;
    }
    return array_slice($rows, 0, 20);
}

function product_form_sku_suggest(string $productName, string $ml, int $index, array $taken): string
{
    $initials = '';
    foreach (preg_split('/\s+/', trim($productName)) ?: [] as $word) {
        $letter = strtoupper(substr(preg_replace('/[^a-z]/i', '', $word) ?? '', 0, 1));
        if ($letter !== '') {
            $initials .= $letter;
        }
    }
    $initials = $initials === '' ? 'SKU' : substr($initials, 0, 4);
    $base = 'SF-' . $initials . '-' . ($ml !== '' ? str_pad($ml, 3, '0', STR_PAD_LEFT) : 'S' . ($index + 1));
    $candidate = $base;
    for ($n = 2; in_array($candidate, $taken, true) || db_exists('SELECT id FROM product_sizes WHERE sku = :sku', ['sku' => $candidate]); $n++) {
        $candidate = $base . '-' . $n;
    }
    return $candidate;
}

function product_form_validate(array &$form, array &$sizes, ?int $productId, array $existingSizeIds): array
{
    $errors = [];
    $form['name'] = trim(preg_replace('/\s+/u', ' ', $form['name']) ?? '');
    if ($form['name'] === '') {
        $errors['name'] = 'Give the product a name.';
    } elseif (mb_strlen($form['name']) > 120) {
        $errors['name'] = 'Keep the name to 120 characters.';
    }
    $form['slug'] = catalogue_slug_input($form['slug'], $form['name']);
    if ($form['slug'] === '' || !preg_match(CATALOGUE_SLUG_PATTERN, $form['slug'])) {
        $errors['slug'] = 'The link name can only use lowercase letters, numbers and hyphens.';
    } elseif (catalogue_slug_taken('products', $form['slug'], $productId)) {
        $errors['slug'] = 'Another product already uses the link name "' . $form['slug'] . '". Choose a different one.';
    }
    $collectionId = catalogue_int_input($form['collection_id'], 1, 4294967295);
    if ($collectionId === null || $collectionId === PHP_INT_MIN || !db_exists('SELECT id FROM collections WHERE id = :id', ['id' => $collectionId])) {
        $errors['collection_id'] = 'Choose the collection this perfume belongs to.';
    }
    if (!in_array($form['gender'], GENDERS, true)) {
        $errors['gender'] = 'Choose who the perfume is for.';
    }
    if ($form['scent_family'] !== '' && !db_exists('SELECT id FROM scent_families WHERE name = :n', ['n' => $form['scent_family']])) {
        $errors['scent_family'] = 'That scent family is not in the list. Add it under Scent families first.';
    }
    if (mb_strlen($form['short_description']) > 200) {
        $errors['short_description'] = 'Keep the short description to 200 characters.';
    }
    foreach (['notes_top' => 'Top notes', 'notes_heart' => 'Heart notes', 'notes_base' => 'Base notes'] as $key => $label) {
        if (mb_strlen($form[$key]) > 255) {
            $errors[$key] = $label . ' must fit in 255 characters. Use fewer notes.';
        }
    }
    foreach (['longevity' => 'Longevity', 'sillage' => 'Sillage'] as $key => $label) {
        if ($form[$key] !== '' && catalogue_int_input($form[$key], 1, 5) === PHP_INT_MIN) {
            $errors[$key] = $label . ' must be between 1 and 5.';
        }
    }
    if (catalogue_int_input($form['sort_order'], 0, 65535) === PHP_INT_MIN) {
        $errors['sort_order'] = 'Sort order must be a whole number from 0 to 65535.';
    }
    if ($form['published_at'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['published_at'])) {
        $errors['published_at'] = 'Enter the publish date as YYYY-MM-DD.';
    }
    if (mb_strlen($form['seo_title']) > 60) {
        $errors['seo_title'] = 'Keep the SEO title to 60 characters so Google shows all of it.';
    }
    if (mb_strlen($form['seo_description']) > 155) {
        $errors['seo_description'] = 'Keep the SEO description to 155 characters.';
    }
    return $errors + product_form_validate_sizes($form['name'], $sizes, $productId, $existingSizeIds);
}

function product_form_validate_sizes(string $productName, array &$sizes, ?int $productId, array $existingSizeIds): array
{
    $errors = [];
    if ($sizes === []) {
        $errors['sizes'] = 'Add at least one size with a price.';
        return $errors;
    }
    $skus = [];
    $defaultIndex = catalogue_int_input(request_post('default_size', ''), 0, 19);
    $defaultIndex = ($defaultIndex === null || $defaultIndex === PHP_INT_MIN || !isset($sizes[$defaultIndex])) ? 0 : $defaultIndex;
    foreach ($sizes as $i => &$size) {
        $n = $i + 1;
        $prefix = 'sizes-' . $i . '-';
        $size['is_default'] = $i === $defaultIndex ? 1 : 0;
        $size['id'] = ($size['id'] !== '' && in_array((int) $size['id'], $existingSizeIds, true)) ? (int) $size['id'] : null;
        if ($size['size_label'] === '') {
            $errors[$prefix . 'size_label'] = 'Size ' . $n . ' needs a label such as 50ml.';
        } elseif (mb_strlen($size['size_label']) > 20) {
            $errors[$prefix . 'size_label'] = 'Size ' . $n . ': keep the label to 20 characters.';
        }
        $ml = catalogue_int_input($size['size_ml'], 1, 65535);
        if ($ml === PHP_INT_MIN) {
            $errors[$prefix . 'size_ml'] = 'Size ' . $n . ': millilitres must be a whole number.';
        } elseif ($ml === null && preg_match('/(\d+)\s*ml/i', $size['size_label'], $m)) {
            $size['size_ml'] = $m[1];
        }
        $size['sku'] = strtoupper(preg_replace('/\s+/', '', $size['sku']) ?? '');
        if ($size['sku'] === '') {
            $size['sku'] = product_form_sku_suggest($productName, $size['size_ml'], $i, $skus);
        } elseif (!preg_match('/^[A-Z0-9][A-Z0-9-]{1,39}$/', $size['sku'])) {
            $errors[$prefix . 'sku'] = 'Size ' . $n . ': SKU can only use letters, numbers and hyphens (2–40 characters).';
        } elseif (in_array($size['sku'], $skus, true)) {
            $errors[$prefix . 'sku'] = 'Size ' . $n . ': SKU ' . $size['sku'] . ' is repeated in this form.';
        } else {
            $clash = db_fetch('SELECT ps.id, ps.is_active, p.name FROM product_sizes ps JOIN products p ON p.id = ps.product_id WHERE ps.sku = :sku' . ($size['id'] !== null ? ' AND ps.id <> :own' : ''), ['sku' => $size['sku']] + ($size['id'] !== null ? ['own' => $size['id']] : []));
            if ($clash !== null) {
                $errors[$prefix . 'sku'] = 'Size ' . $n . ': SKU ' . $size['sku'] . ' is already used by ' . $clash['name'] . ((int) $clash['is_active'] === 1 ? '.' : ' (a removed size). Pick another.');
            }
        }
        $skus[] = $size['sku'];
        $price = catalogue_money_input($size['price']);
        if ($price === null || $price === '' || money_paisa($price) <= 0) {
            $errors[$prefix . 'price'] = 'Size ' . $n . ' needs a price above zero, in rupees.';
        } else {
            $size['price'] = $price;
        }
        $sale = catalogue_money_input($size['sale_price']);
        if ($sale === '') {
            $errors[$prefix . 'sale_price'] = 'Size ' . $n . ': the sale price must be a number in rupees.';
        } elseif ($sale !== null && money_paisa($sale) <= 0) {
            $errors[$prefix . 'sale_price'] = 'Size ' . $n . ': leave the sale price blank when there is no sale.';
        } elseif ($sale !== null && $price !== null && $price !== '' && money_paisa($sale) >= money_paisa($price)) {
            $errors[$prefix . 'sale_price'] = 'Size ' . $n . ': the sale price must be lower than the regular price.';
        } else {
            $size['sale_price'] = $sale;
        }
        $stock = catalogue_int_input($size['stock'], 0, 4294967295);
        if ($stock === null || $stock === PHP_INT_MIN) {
            $errors[$prefix . 'stock'] = 'Size ' . $n . ': stock must be a whole number, 0 or more.';
        } else {
            $size['stock'] = $stock;
        }
        $threshold = catalogue_int_input($size['low_stock_threshold'], 0, 255);
        if ($threshold === PHP_INT_MIN) {
            $errors[$prefix . 'low_stock_threshold'] = 'Size ' . $n . ': the low-stock alert level must be 0–255, or blank for the shop default.';
        } else {
            $size['low_stock_threshold'] = $threshold;
        }
    }
    unset($size);
    return $errors;
}

function product_form_alt_from_post(array $images): array
{
    $raw = request_post('images', []);
    $alts = [];
    foreach ($images as $image) {
        $id = (int) $image['id'];
        $posted = is_array($raw) && isset($raw[$id]) && is_array($raw[$id]) ? trim((string) ($raw[$id]['alt'] ?? '')) : (string) $image['alt_text'];
        $alts[$id] = mb_substr(preg_replace('/\s+/u', ' ', $posted) ?? '', 0, 125);
    }
    return $alts;
}

function product_form_row(array $form, ?int $productId): array
{
    $now = now_karachi();
    $published = $form['published_at'] === '' ? null : $form['published_at'] . ' 00:00:00';
    return [
        'collection_id' => (int) $form['collection_id'],
        'name' => $form['name'],
        'slug' => $form['slug'],
        'gender' => $form['gender'],
        'scent_family' => $form['scent_family'] === '' ? null : $form['scent_family'],
        'short_description' => catalogue_text($form['short_description'], 255),
        'description' => catalogue_text($form['description'], 65000),
        'notes_top' => catalogue_csv_join(catalogue_csv_parse($form['notes_top']), 255),
        'notes_heart' => catalogue_csv_join(catalogue_csv_parse($form['notes_heart']), 255),
        'notes_base' => catalogue_csv_join(catalogue_csv_parse($form['notes_base']), 255),
        'longevity' => $form['longevity'] === '' ? null : (int) $form['longevity'],
        'sillage' => $form['sillage'] === '' ? null : (int) $form['sillage'],
        'best_season' => catalogue_csv_join(catalogue_csv_parse($form['best_season']), 60),
        'occasion' => catalogue_csv_join(catalogue_csv_parse($form['occasion']), 120),
        'is_featured' => (int) $form['is_featured'],
        'is_new' => (int) $form['is_new'],
        'is_active' => (int) $form['is_active'],
        'sort_order' => (int) $form['sort_order'],
        'published_at' => $published,
        'seo_title' => catalogue_text($form['seo_title'], 160),
        'seo_description' => catalogue_text($form['seo_description'], 255),
        'updated_at' => $now,
    ] + ($productId === null ? ['created_at' => $now, 'rating_avg' => '0.00', 'rating_count' => 0, 'sales_count' => 0] : []);
}

function product_form_save_sizes(int $productId, array $sizes, array $existing): array
{
    $now = now_karachi();
    $keep = [];
    $stockNotes = [];
    foreach ($sizes as $i => $size) {
        $data = [
            'size_label' => $size['size_label'], 'size_ml' => $size['size_ml'] === '' ? null : (int) $size['size_ml'], 'sku' => $size['sku'],
            'price' => $size['price'], 'sale_price' => $size['sale_price'], 'stock' => (int) $size['stock'], 'low_stock_threshold' => $size['low_stock_threshold'],
            'is_default' => (int) $size['is_default'], 'is_active' => 1, 'sort_order' => ($i + 1) * 10, 'updated_at' => $now,
        ];
        if ($size['id'] !== null && isset($existing[$size['id']])) {
            $before = $existing[$size['id']];
            db_update('product_sizes', $data, ['id' => $size['id']]);
            $keep[] = $size['id'];
            if ((int) $before['stock'] !== (int) $size['stock']) {
                $stockNotes[] = $size['size_label'] . ' ' . (int) $before['stock'] . ' → ' . (int) $size['stock'];
            }
            continue;
        }
        $keep[] = db_insert('product_sizes', $data + ['product_id' => $productId, 'created_at' => $now]);
        if ((int) $size['stock'] > 0) {
            $stockNotes[] = $size['size_label'] . ' new at ' . (int) $size['stock'];
        }
    }
    $removed = [];
    foreach ($existing as $id => $row) {
        if (in_array($id, $keep, true)) {
            continue;
        }
        if (catalogue_size_has_orders($id)) {
            db_update('product_sizes', ['is_active' => 0, 'is_default' => 0, 'updated_at' => $now], ['id' => $id]);
        } else {
            db_delete('product_sizes', ['id' => $id]);
        }
        $removed[] = $row['size_label'];
    }
    return ['stock' => $stockNotes, 'removed' => $removed];
}

function product_form_save(array $form, array $sizes, array $alts, ?array $product, array $existingSizes): array
{
    return db_transaction(static function () use ($form, $sizes, $alts, $product, $existingSizes): array {
        $productId = $product === null ? null : (int) $product['id'];
        $row = product_form_row($form, $productId);
        $notes = [];
        if ($productId === null) {
            $productId = db_insert('products', $row);
            $sizeReport = product_form_save_sizes($productId, $sizes, []);
            catalogue_log('product', $productId, 'product.create', 'Created ' . $row['name'] . ' with ' . count($sizes) . ' size' . (count($sizes) === 1 ? '' : 's'), null, $row);
            return ['id' => $productId, 'renamed' => false, 'notes' => $notes];
        }
        $renamed = $product['slug'] !== $row['slug'];
        if ($product['published_at'] !== null && substr((string) $product['published_at'], 0, 10) === substr((string) $row['published_at'], 0, 10)) {
            $row['published_at'] = $product['published_at'];
        }
        db_update('products', $row, ['id' => $productId]);
        if ($renamed) {
            catalogue_slug_rename('product', $productId, (string) $product['slug'], $row['slug']);
            catalogue_log('product', $productId, 'product.slug', 'Link changed from ' . $product['slug'] . ' to ' . $row['slug'] . ' (old link redirects)');
        }
        $sizeReport = product_form_save_sizes($productId, $sizes, $existingSizes);
        foreach ($alts as $imageId => $alt) {
            db_update('product_images', ['alt_text' => $alt], ['id' => $imageId, 'product_id' => $productId]);
        }
        $changed = catalogue_changed_fields($product, $row, array_diff(array_keys(product_form_defaults()), ['id']));
        if ($sizeReport['stock'] !== []) {
            catalogue_log('product', $productId, 'product.stock', 'Stock set on ' . $row['name'] . ': ' . implode(', ', $sizeReport['stock']));
        }
        if ($sizeReport['removed'] !== []) {
            $changed[] = 'sizes removed (' . implode(', ', $sizeReport['removed']) . ')';
        }
        $summary = $changed === [] ? 'Saved ' . $row['name'] . ' with no field changes' : 'Updated ' . $row['name'] . ': ' . implode(', ', $changed);
        catalogue_log('product', $productId, 'product.update', $summary, array_intersect_key($product, $row), $row);
        return ['id' => $productId, 'renamed' => $renamed, 'notes' => $notes];
    });
}

$isNew = ($route['name'] ?? '') === 'admin.products.new';
$product = null;
$existingSizes = [];
$images = [];
if (!$isNew) {
    $product = db_fetch('SELECT * FROM products WHERE id = :id', ['id' => (int) $params['id']]);
    if ($product === null) {
        abort(404, 'That product does not exist. It may have been deleted.');
    }
    foreach (catalogue_product_sizes((int) $product['id']) as $sizeRow) {
        $existingSizes[(int) $sizeRow['id']] = $sizeRow;
    }
    $images = catalogue_product_images((int) $product['id']);
}

$form = product_form_defaults();
if ($product !== null) {
    foreach ($form as $key => $default) {
        $form[$key] = $product[$key] ?? $default;
    }
    $form['published_at'] = $product['published_at'] === null ? '' : substr((string) $product['published_at'], 0, 10);
    $form['collection_id'] = (string) ($product['collection_id'] ?? '');
    foreach (['scent_family', 'short_description', 'description', 'notes_top', 'notes_heart', 'notes_base', 'longevity', 'sillage', 'best_season', 'occasion', 'seo_title', 'seo_description'] as $nullable) {
        $form[$nullable] = (string) ($product[$nullable] ?? '');
    }
}
$sizes = [];
foreach ($existingSizes as $sizeRow) {
    $sizes[] = ['id' => (string) $sizeRow['id'], 'size_label' => $sizeRow['size_label'], 'size_ml' => (string) ($sizeRow['size_ml'] ?? ''), 'sku' => $sizeRow['sku'], 'price' => catalogue_money_field($sizeRow['price']), 'sale_price' => catalogue_money_field($sizeRow['sale_price']), 'stock' => (string) $sizeRow['stock'], 'low_stock_threshold' => (string) ($sizeRow['low_stock_threshold'] ?? ''), 'is_default' => (int) $sizeRow['is_default']];
}
if ($sizes === []) {
    $sizes[] = product_form_size_defaults() + ['is_default' => 1];
}
$alts = [];
foreach ($images as $image) {
    $alts[(int) $image['id']] = (string) ($image['alt_text'] ?? '');
}
$errors = [];

if (request_method() === 'POST') {
    foreach (['name', 'slug', 'collection_id', 'gender', 'scent_family', 'short_description', 'description', 'notes_top', 'notes_heart', 'notes_base', 'longevity', 'sillage', 'sort_order', 'published_at', 'seo_title', 'seo_description'] as $key) {
        $form[$key] = trim((string) request_post($key, ''));
    }
    foreach (['best_season', 'occasion'] as $multi) {
        $picked = request_post($multi, []);
        $form[$multi] = implode(', ', array_map('strval', is_array($picked) ? $picked : []));
    }
    $form['is_active'] = request_post('is_active', '1') === '0' ? 0 : 1;
    $form['is_featured'] = request_post('is_featured') === '1' ? 1 : 0;
    $form['is_new'] = request_post('is_new') === '1' ? 1 : 0;
    if ($form['sort_order'] === '') {
        $form['sort_order'] = '0';
    }
    $sizes = product_form_sizes_from_post();
    $alts = product_form_alt_from_post($images);
    $errors = product_form_validate($form, $sizes, $product === null ? null : (int) $product['id'], array_keys($existingSizes));
    foreach ($alts as $imageId => $alt) {
        if ($alt === '') {
            $errors['img-alt-' . $imageId] = 'Every photo needs alt text. Describe what is in the picture.';
        }
    }
    if ($errors === []) {
        $result = product_form_save($form, $sizes, $alts, $product, $existingSizes);
        $after = (string) request_post('after', 'list');
        $viewUrl = url('/product/' . $form['slug']);
        if ($product === null) {
            flash('success', $form['name'] . ' created. Add photos below, then make it live when it is ready.');
            redirect($after === 'add' ? '/admin/products/new' : '/admin/products/' . $result['id'], 303);
        }
        flash('success', $form['name'] . ' saved.' . ($result['renamed'] ? ' The old link will redirect here.' : '') . ' View on site: ' . $viewUrl);
        if ($after === 'add') {
            redirect('/admin/products/new', 303);
        }
        if ($after === 'stay') {
            redirect('/admin/products/' . $result['id'], 303);
        }
        redirect('/admin/products', 303);
    }
    if ($sizes === []) {
        $sizes[] = product_form_size_defaults() + ['is_default' => 1];
    }
    http_status(422);
}

$productId = $product === null ? null : (int) $product['id'];
render_admin('product-form.php', [
    'isNew' => $isNew,
    'product' => $product,
    'productId' => $productId,
    'form' => $form,
    'sizes' => $sizes,
    'images' => $images,
    'alts' => $alts,
    'errors' => $errors,
    'collectionsOptions' => catalogue_collections_options(),
    'familyOptions' => catalogue_families_options(),
    'threshold' => catalogue_low_threshold(),
    'slugHistory' => $productId === null ? [] : catalogue_slug_history('product', $productId),
    'hasOrders' => $productId !== null && catalogue_product_has_orders($productId),
    'orderLines' => $productId === null ? 0 : (int) db_fetch_column('SELECT COUNT(*) FROM order_items WHERE product_id = :id', ['id' => $productId]),
], [
    'title' => $isNew ? 'New product' : 'Edit product',
    'body_class' => 'admin t-light admin-product-form',
    'back' => '/admin/products',
]);
