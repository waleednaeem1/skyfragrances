<?php
defined('SKYFR') || exit;

const CATALOGUE_SEASONS = ['Spring', 'Summer', 'Monsoon', 'Autumn', 'Winter', 'All year'];
const CATALOGUE_OCCASIONS = ['Daily', 'Daytime', 'Office', 'Evening', 'Formal', 'Weddings', 'Celebrations', 'Gifting', 'Signature', 'Layering', 'Outdoors'];
const CATALOGUE_GENDER_LABELS = ['him' => 'For him', 'her' => 'For her', 'unisex' => 'Unisex'];
const CATALOGUE_SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
const CATALOGUE_STEM_PATTERN = '/^(product|collection)-(\d+)-([a-f0-9]{10})\.(webp|jpg)$/';

function catalogue_actor(): array
{
    $admin = auth_user();
    return [
        'by' => 'admin',
        'admin_id' => isset($admin['id']) ? (int) $admin['id'] : null,
        'admin_username' => isset($admin['username']) ? (string) $admin['username'] : null,
    ];
}

function catalogue_log(string $entityType, ?int $entityId, string $action, string $summary, ?array $before = null, ?array $after = null): void
{
    $actor = catalogue_actor();
    $encode = static fn (?array $data): ?string => $data === null ? null : mb_substr((string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR), 0, 60000);
    db_insert('admin_activity_log', [
        'admin_id' => $actor['admin_id'],
        'admin_username' => $actor['admin_username'] === null ? null : mb_substr($actor['admin_username'], 0, 64),
        'entity_type' => mb_substr($entityType, 0, 32),
        'entity_id' => $entityId,
        'action' => mb_substr($action, 0, 48),
        'summary' => mb_substr($summary, 0, 255),
        'before_json' => $encode($before),
        'after_json' => $encode($after),
        'ip_hash' => request_ip_hash(),
        'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        'created_at' => now_karachi(),
    ]);
}

function catalogue_changed_fields(array $before, array $after, array $fields): array
{
    $changed = [];
    foreach ($fields as $field) {
        $old = $before[$field] ?? null;
        $new = $after[$field] ?? null;
        if ((string) ($old ?? '') !== (string) ($new ?? '')) {
            $changed[] = $field;
        }
    }
    return $changed;
}

function catalogue_csv_parse(?string $value): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string) $value)), 'strlen'));
}

function catalogue_csv_join(array $items, int $maxLength): ?string
{
    $clean = [];
    foreach ($items as $item) {
        $item = trim(preg_replace('/\s+/u', ' ', str_replace(',', ' ', (string) $item)) ?? '');
        if ($item !== '' && !in_array($item, $clean, true)) {
            $clean[] = $item;
        }
    }
    if ($clean === []) {
        return null;
    }
    return mb_substr(implode(', ', $clean), 0, $maxLength);
}

function catalogue_slug_taken(string $table, string $slug, ?int $excludeId = null): bool
{
    $sql = 'SELECT id FROM ' . db_identifier($table) . ' WHERE slug = :slug';
    $params = ['slug' => $slug];
    if ($excludeId !== null) {
        $sql .= ' AND id <> :id';
        $params['id'] = $excludeId;
    }
    return db_exists($sql . ' LIMIT 1', $params);
}

function catalogue_slug_unique(string $table, string $base, ?int $excludeId = null): string
{
    $base = $base === '' ? 'item' : $base;
    $candidate = $base;
    for ($n = 2; catalogue_slug_taken($table, $candidate, $excludeId); $n++) {
        $suffix = '-' . $n;
        $candidate = substr($base, 0, 160 - strlen($suffix)) . $suffix;
    }
    return $candidate;
}

function catalogue_slug_rename(string $entityType, int $entityId, string $oldSlug, string $newSlug): void
{
    if ($oldSlug === $newSlug) {
        return;
    }
    $now = now_karachi();
    db_query('DELETE FROM slug_redirects WHERE entity_type = :type AND old_slug = :old', ['type' => $entityType, 'old' => $oldSlug]);
    db_insert('slug_redirects', ['entity_type' => $entityType, 'old_slug' => $oldSlug, 'new_slug' => $newSlug, 'entity_id' => $entityId, 'created_at' => $now]);
    db_query('UPDATE slug_redirects SET new_slug = :new WHERE entity_type = :type AND new_slug = :old', ['new' => $newSlug, 'type' => $entityType, 'old' => $oldSlug]);
    db_query('DELETE FROM slug_redirects WHERE entity_type = :type AND old_slug = :new', ['type' => $entityType, 'new' => $newSlug]);
}

function catalogue_slug_history(string $entityType, int $entityId): array
{
    return db_fetch_all(
        'SELECT old_slug, hit_count, created_at FROM slug_redirects WHERE entity_type = :type AND entity_id = :id ORDER BY created_at DESC LIMIT 10',
        ['type' => $entityType, 'id' => $entityId]
    );
}

function catalogue_slug_input(string $raw, string $fallbackName): string
{
    $slug = slugify($raw !== '' ? $raw : $fallbackName);
    return substr($slug, 0, 160);
}

function catalogue_money_input(mixed $raw): ?string
{
    $value = str_replace([',', ' ', "\u{00A0}"], '', trim((string) $raw));
    if ($value === '') {
        return null;
    }
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value)) {
        return '';
    }
    return money_from_paisa(money_paisa($value));
}

function catalogue_money_field(?string $stored): string
{
    if ($stored === null || $stored === '') {
        return '';
    }
    $paisa = money_paisa($stored);
    return $paisa % 100 === 0 ? (string) intdiv($paisa, 100) : money_from_paisa($paisa);
}

function catalogue_int_input(mixed $raw, int $min, int $max): ?int
{
    $value = trim((string) $raw);
    if ($value === '') {
        return null;
    }
    $int = filter_var($value, FILTER_VALIDATE_INT);
    if ($int === false || $int < $min || $int > $max) {
        return PHP_INT_MIN;
    }
    return $int;
}

function catalogue_text(mixed $raw, int $maxLength): ?string
{
    $value = trim(preg_replace('/\r\n?/', "\n", (string) $raw) ?? '');
    if ($value === '') {
        return null;
    }
    return mb_substr($value, 0, $maxLength);
}

function catalogue_low_threshold(): int
{
    return max(0, setting_int('low_stock_threshold', 5));
}

function catalogue_collections_options(bool $withBlank = true): array
{
    $options = $withBlank ? ['' => 'Choose a collection'] : [];
    foreach (db_fetch_all('SELECT id, name, is_active FROM collections ORDER BY sort_order ASC, name ASC') as $row) {
        $options[(string) $row['id']] = $row['name'] . ((int) $row['is_active'] === 1 ? '' : ' (hidden)');
    }
    return $options;
}

function catalogue_families_options(bool $withBlank = true): array
{
    $options = $withBlank ? ['' => 'No family'] : [];
    foreach (db_fetch_all('SELECT name, is_active FROM scent_families ORDER BY sort_order ASC, name ASC') as $row) {
        $options[$row['name']] = $row['name'] . ((int) $row['is_active'] === 1 ? '' : ' (hidden)');
    }
    return $options;
}

function catalogue_image_stem(?string $filename): ?string
{
    if ($filename === null || !preg_match(CATALOGUE_STEM_PATTERN, $filename, $m)) {
        return null;
    }
    return $m[1] . '-' . $m[2] . '-' . $m[3];
}

function catalogue_legacy_derivative(string $filename, string $size): string
{
    $ext = image_webp_supported() ? 'webp' : 'jpg';
    return preg_replace('/\.(webp|jpe?g|png)$/i', '', $filename) . '-' . $size . '.' . $ext;
}

function catalogue_product_image_url(int $productId, ?string $filename, string $size = 'thumb'): string
{
    if ($filename === null || $filename === '') {
        return asset('img/placeholder-4x5.svg');
    }
    $stem = catalogue_image_stem($filename);
    if ($stem !== null) {
        return image_url($stem, $size);
    }
    $relative = str_contains($filename, '/') ? $filename : $productId . '/' . $filename;
    return url('/uploads/products/' . catalogue_legacy_derivative($relative, $size));
}

function catalogue_collection_image_url(?string $image, string $size = 'card'): string
{
    if ($image === null || $image === '') {
        return asset('img/placeholder-4x5.svg');
    }
    $stem = catalogue_image_stem(basename($image));
    if ($stem !== null) {
        return image_url($stem, $size);
    }
    return url('/uploads/' . catalogue_legacy_derivative($image, $size));
}

function catalogue_write_base_copy(string $dir, string $stem): string
{
    foreach (['webp', 'jpg'] as $ext) {
        $zoom = $dir . '/' . $stem . '-zoom.' . $ext;
        if (is_file($zoom)) {
            $base = $dir . '/' . $stem . '.' . $ext;
            if (!@copy($zoom, $base)) {
                throw new RuntimeException('Cannot write base image');
            }
            return $stem . '.' . $ext;
        }
    }
    throw new RuntimeException('Zoom derivative missing');
}

function catalogue_image_dimensions(string $dir, string $stem): array
{
    foreach (['webp', 'jpg'] as $ext) {
        $info = @getimagesize($dir . '/' . $stem . '-zoom.' . $ext);
        if (is_array($info)) {
            return [(int) $info[0], (int) $info[1]];
        }
    }
    return [0, 0];
}

function catalogue_remove_image_files(string $filename): void
{
    $stem = catalogue_image_stem(basename($filename));
    if ($stem === null) {
        return;
    }
    $shared = (int) db_fetch_column('SELECT COUNT(*) FROM product_images WHERE filename = :f', ['f' => $filename])
        + (int) db_fetch_column('SELECT COUNT(*) FROM collections WHERE image = :f', ['f' => 'collections/' . basename($filename)]);
    if ($shared > 0) {
        return;
    }
    image_delete($stem);
    $dir = image_dir_for_stem($stem, 'card');
    if ($dir !== null) {
        foreach (['webp', 'jpg'] as $ext) {
            $base = APP_ROOT . $dir . '/' . $stem . '.' . $ext;
            if (is_file($base)) {
                @unlink($base);
            }
        }
    }
}

function catalogue_product_has_orders(int $productId): bool
{
    return db_exists('SELECT id FROM order_items WHERE product_id = :id LIMIT 1', ['id' => $productId]);
}

function catalogue_size_has_orders(int $sizeId): bool
{
    return db_exists('SELECT id FROM order_items WHERE product_size_id = :id LIMIT 1', ['id' => $sizeId]);
}

function catalogue_product_sizes(int $productId): array
{
    return db_fetch_all(
        'SELECT id, size_label, size_ml, sku, price, sale_price, stock, low_stock_threshold, is_default, sort_order
         FROM product_sizes WHERE product_id = :id AND is_active = 1 ORDER BY sort_order ASC, size_ml ASC, id ASC',
        ['id' => $productId]
    );
}

function catalogue_product_images(int $productId): array
{
    return db_fetch_all(
        'SELECT id, product_id, filename, alt_text, width, height, sort_order, is_primary
         FROM product_images WHERE product_id = :id ORDER BY is_primary DESC, sort_order ASC, id ASC',
        ['id' => $productId]
    );
}

function catalogue_size_range(array $sizes): string
{
    $labels = array_values(array_filter(array_map(static fn (array $s): string => trim((string) $s['size_label']), $sizes), 'strlen'));
    if ($labels === []) {
        return '';
    }
    if (count($labels) === 1) {
        return $labels[0];
    }
    $last = array_pop($labels);
    return implode(', ', $labels) . ' and ' . $last;
}

function catalogue_default_alt(string $productName, array $sizes): string
{
    $range = catalogue_size_range($sizes);
    return mb_substr($productName . ' — ' . ($range !== '' ? $range . ' ' : '') . 'perfume by Sky Fragrances', 0, 125);
}

function catalogue_stock_state(array $sizes, int $threshold): string
{
    if ($sizes === []) {
        return 'none';
    }
    $allOut = true;
    $anyLow = false;
    foreach ($sizes as $size) {
        $stock = (int) $size['stock'];
        $limit = $size['low_stock_threshold'] === null ? $threshold : (int) $size['low_stock_threshold'];
        if ($stock > 0) {
            $allOut = false;
        }
        if ($stock > 0 && $limit > 0 && $stock <= $limit) {
            $anyLow = true;
        }
    }
    return $allOut ? 'out' : ($anyLow ? 'low' : 'ok');
}

function catalogue_tile_html(array $image, array $product, array $sizes): string
{
    $id = (int) $image['id'];
    $productId = (int) $product['id'];
    $alt = (string) ($image['alt_text'] ?? '');
    $primary = (int) ($image['is_primary'] ?? 0) === 1;
    $base = '/admin/products/' . $productId . '/images/' . $id;
    $thumb = catalogue_product_image_url($productId, (string) $image['filename'], 'card');
    $html = '<li class="adm-tile' . ($primary ? ' is-primary' : '') . '" data-sortable-item data-id="' . $id . '">';
    $html .= '<img class="adm-tile__img" src="' . e($thumb) . '" alt="" width="' . (int) ($image['width'] ?? 0) . '" height="' . (int) ($image['height'] ?? 0) . '" loading="lazy">';
    $html .= '<button type="button" class="adm-tile__handle" data-sortable-handle aria-label="Drag to reorder">&#8801;</button>';
    $html .= '<span class="adm-tile__primary" data-tile-primary-badge' . ($primary ? '' : ' hidden') . '><span class="adm-badge adm-badge--gold adm-badge--sm">Primary</span></span>';
    $html .= '<div class="adm-tile__tools">';
    $html .= '<button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" data-move="up" aria-label="Move earlier">&uarr;</button>';
    $html .= '<button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" data-move="down" aria-label="Move later">&darr;</button>';
    $html .= '<button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" data-image-action="' . e(url($base . '/primary')) . '" data-image-primary="' . $id . '" aria-label="Make primary">&#9733;</button>';
    $html .= '<button type="button" class="adm-btn adm-btn--danger adm-btn--sm" data-image-action="' . e(url($base . '/delete')) . '" data-image-delete="' . $id . '" data-confirm="Delete this photo?' . "\n" . 'The file is removed from the server. This cannot be undone." aria-label="Delete photo">&times;</button>';
    $html .= '</div>';
    $html .= '<div class="adm-tile__alt adm-field adm-field--compact"><label class="adm-field__label" for="img-alt-' . $id . '">Alt text <span class="adm-field__req" aria-hidden="true">*</span></label>';
    $html .= '<input class="adm-input" id="img-alt-' . $id . '" name="images[' . $id . '][alt]" type="text" value="' . e($alt) . '" maxlength="125" data-counter data-counter-warn="110" placeholder="' . e(catalogue_default_alt((string) $product['name'], $sizes)) . '"></div>';
    $html .= '</li>';
    return $html;
}

function catalogue_image_insert(int $productId, string $filename, string $alt, int $width, int $height): int
{
    $hasPrimary = db_exists('SELECT id FROM product_images WHERE product_id = :id AND is_primary = 1 LIMIT 1', ['id' => $productId]);
    $maxSort = (int) db_fetch_column('SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = :id', ['id' => $productId]);
    return db_insert('product_images', [
        'product_id' => $productId,
        'filename' => $filename,
        'alt_text' => mb_substr($alt, 0, 255),
        'width' => min(65535, $width),
        'height' => min(65535, $height),
        'sort_order' => min(65535, $maxSort + 10),
        'is_primary' => $hasPrimary ? 0 : 1,
        'created_at' => now_karachi(),
    ]);
}

function catalogue_image_copy(int $fromProductId, string $filename, int $toProductId): string
{
    $stem = catalogue_image_stem($filename);
    if ($stem === null) {
        return $filename;
    }
    $fromDir = APP_ROOT . '/uploads/products/' . $fromProductId;
    $toDir = APP_ROOT . '/uploads/products/' . $toProductId;
    image_ensure_dir($toDir);
    $newStem = image_stem('product', $toProductId, $filename);
    $sizes = array_merge(array_keys(IMAGE_PRODUCT_SIZES), ['']);
    foreach ($sizes as $size) {
        foreach (['webp', 'jpg'] as $ext) {
            $source = $fromDir . '/' . $stem . ($size === '' ? '' : '-' . $size) . '.' . $ext;
            if (is_file($source)) {
                @copy($source, $toDir . '/' . $newStem . ($size === '' ? '' : '-' . $size) . '.' . $ext);
            }
        }
    }
    $og = APP_ROOT . '/uploads/og/' . $stem . '-og.jpg';
    if (is_file($og)) {
        @copy($og, APP_ROOT . '/uploads/og/' . $newStem . '-og.jpg');
    }
    return $newStem . '.' . pathinfo($filename, PATHINFO_EXTENSION);
}
