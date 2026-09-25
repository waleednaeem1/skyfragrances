<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/catalogue.php';

function collection_form_defaults(): array
{
    return ['name' => '', 'slug' => '', 'tagline' => '', 'description' => '', 'mood' => '', 'sort_order' => '', 'is_active' => 1, 'show_on_home' => 0, 'seo_title' => '', 'seo_description' => ''];
}

function collection_form_move(array $collection, string $direction): void
{
    $neighbour = $direction === 'up'
        ? db_fetch('SELECT id, sort_order FROM collections WHERE (sort_order < :s OR (sort_order = :s2 AND id < :id)) ORDER BY sort_order DESC, id DESC LIMIT 1', ['s' => (int) $collection['sort_order'], 's2' => (int) $collection['sort_order'], 'id' => (int) $collection['id']])
        : db_fetch('SELECT id, sort_order FROM collections WHERE (sort_order > :s OR (sort_order = :s2 AND id > :id)) ORDER BY sort_order ASC, id ASC LIMIT 1', ['s' => (int) $collection['sort_order'], 's2' => (int) $collection['sort_order'], 'id' => (int) $collection['id']]);
    if ($neighbour === null) {
        flash('info', $collection['name'] . ' is already ' . ($direction === 'up' ? 'first' : 'last') . '.');
        redirect('/admin/collections', 303);
    }
    $mine = (int) $collection['sort_order'];
    $theirs = (int) $neighbour['sort_order'];
    if ($mine === $theirs) {
        $theirs = $direction === 'up' ? max(0, $mine - 10) : $mine + 10;
    }
    db_transaction(static function () use ($collection, $neighbour, $mine, $theirs): void {
        $now = now_karachi();
        db_update('collections', ['sort_order' => $theirs, 'updated_at' => $now], ['id' => (int) $collection['id']]);
        db_update('collections', ['sort_order' => $mine, 'updated_at' => $now], ['id' => (int) $neighbour['id']]);
    });
    catalogue_log('collection', (int) $collection['id'], 'collection.reorder', 'Moved ' . $collection['name'] . ' ' . $direction);
    flash('success', $collection['name'] . ' moved ' . $direction . '.');
    redirect('/admin/collections', 303);
}

function collection_form_validate(array &$form, ?int $collectionId): array
{
    $errors = [];
    $form['name'] = trim(preg_replace('/\s+/u', ' ', $form['name']) ?? '');
    if ($form['name'] === '') {
        $errors['name'] = 'Give the collection a name.';
    } elseif (mb_strlen($form['name']) > 80) {
        $errors['name'] = 'Keep the name to 80 characters.';
    }
    $form['slug'] = catalogue_slug_input($form['slug'], $form['name']);
    if ($form['slug'] === '' || !preg_match(CATALOGUE_SLUG_PATTERN, $form['slug'])) {
        $errors['slug'] = 'The link name can only use lowercase letters, numbers and hyphens.';
    } elseif (catalogue_slug_taken('collections', $form['slug'], $collectionId)) {
        $errors['slug'] = 'Another collection already uses the link name "' . $form['slug'] . '".';
    }
    if (mb_strlen($form['tagline']) > 120) {
        $errors['tagline'] = 'Keep the tagline to 120 characters.';
    }
    if (mb_strlen($form['mood']) > 160) {
        $errors['mood'] = 'Keep the mood line to 160 characters.';
    }
    if ($form['sort_order'] !== '' && catalogue_int_input($form['sort_order'], 0, 65535) === PHP_INT_MIN) {
        $errors['sort_order'] = 'Sort order must be a whole number from 0 to 65535.';
    }
    if (mb_strlen($form['seo_title']) > 60) {
        $errors['seo_title'] = 'Keep the SEO title to 60 characters.';
    }
    if (mb_strlen($form['seo_description']) > 155) {
        $errors['seo_description'] = 'Keep the SEO description to 155 characters.';
    }
    return $errors;
}

function collection_form_store_image(array $file, int $collectionId): array
{
    $check = upload_validate_product($file);
    if (!$check['ok']) {
        return ['ok' => false, 'error' => $check['error']];
    }
    try {
        $stem = image_derive_collection((string) $file['tmp_name'], $collectionId);
        $filename = catalogue_write_base_copy(APP_ROOT . '/uploads/collections', $stem);
    } catch (Throwable $e) {
        log_write('error', 'Collection image processing failed', ['collection' => $collectionId, 'error' => $e->getMessage()]);
        return ['ok' => false, 'error' => 'The image could not be processed. Try a JPG exported at a smaller size.'];
    }
    return ['ok' => true, 'image' => 'collections/' . $filename];
}

$isNew = ($route['name'] ?? '') === 'admin.collections.new';
$collection = null;
if (!$isNew) {
    $collection = db_fetch('SELECT * FROM collections WHERE id = :id', ['id' => (int) $params['id']]);
    if ($collection === null) {
        abort(404, 'That collection does not exist. It may have been deleted.');
    }
    if (request_method() === 'POST' && in_array(request_post('move'), ['up', 'down'], true)) {
        collection_form_move($collection, (string) request_post('move'));
    }
    if (request_method() === 'POST' && request_post('toggle') === '1') {
        $live = (int) $collection['is_active'] === 1 ? 0 : 1;
        db_update('collections', ['is_active' => $live, 'updated_at' => now_karachi()], ['id' => (int) $collection['id']]);
        catalogue_log('collection', (int) $collection['id'], $live ? 'collection.live' : 'collection.hide', ($live ? 'Made live: ' : 'Hidden: ') . $collection['name']);
        flash('success', $collection['name'] . ($live ? ' is now live.' : ' is hidden. Its products stay live.'));
        redirect(request_return_path('/admin/collections'), 303);
    }
}

$form = collection_form_defaults();
if ($collection !== null) {
    foreach ($form as $key => $default) {
        $form[$key] = (string) ($collection[$key] ?? $default);
    }
    $form['is_active'] = (int) $collection['is_active'];
    $form['show_on_home'] = (int) $collection['show_on_home'];
}
$errors = [];
$productCount = $collection === null ? 0 : (int) db_fetch_column('SELECT COUNT(*) FROM products WHERE collection_id = :id', ['id' => (int) $collection['id']]);

if (request_method() === 'POST') {
    foreach (['name', 'slug', 'tagline', 'description', 'mood', 'sort_order', 'seo_title', 'seo_description'] as $key) {
        $form[$key] = trim((string) request_post($key, ''));
    }
    $form['is_active'] = request_post('is_active', '1') === '0' ? 0 : 1;
    $form['show_on_home'] = request_post('show_on_home') === '1' ? 1 : 0;
    $errors = collection_form_validate($form, $collection === null ? null : (int) $collection['id']);
    $upload = upload_files_from_request('image');
    $hasUpload = $upload !== [] && (int) ($upload[0]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($hasUpload && $errors === []) {
        $probe = upload_validate_product($upload[0]);
        if (!$probe['ok']) {
            $errors['image'] = $probe['error'];
        }
    }
    if ($errors === []) {
        $now = now_karachi();
        $row = [
            'name' => $form['name'], 'slug' => $form['slug'], 'tagline' => catalogue_text($form['tagline'], 160), 'description' => catalogue_text($form['description'], 65000),
            'mood' => catalogue_text($form['mood'], 255), 'is_active' => $form['is_active'], 'show_on_home' => $form['show_on_home'],
            'seo_title' => catalogue_text($form['seo_title'], 160), 'seo_description' => catalogue_text($form['seo_description'], 255), 'updated_at' => $now,
        ];
        $row['sort_order'] = $form['sort_order'] === '' ? ($collection === null ? (int) db_fetch_column('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM collections') : (int) $collection['sort_order']) : (int) $form['sort_order'];
        $collectionId = db_transaction(static function () use ($row, $collection, $now): int {
            if ($collection === null) {
                $id = db_insert('collections', $row + ['created_at' => $now]);
                catalogue_log('collection', $id, 'collection.create', 'Created collection ' . $row['name'], null, $row);
                return $id;
            }
            $id = (int) $collection['id'];
            db_update('collections', $row, ['id' => $id]);
            if ($collection['slug'] !== $row['slug']) {
                catalogue_slug_rename('collection', $id, (string) $collection['slug'], $row['slug']);
                catalogue_log('collection', $id, 'collection.slug', 'Link changed from ' . $collection['slug'] . ' to ' . $row['slug'] . ' (old link redirects)');
            }
            $changed = catalogue_changed_fields($collection, $row, array_keys(collection_form_defaults()));
            catalogue_log('collection', $id, 'collection.update', $changed === [] ? 'Saved ' . $row['name'] . ' with no field changes' : 'Updated ' . $row['name'] . ': ' . implode(', ', $changed), array_intersect_key($collection, $row), $row);
            return $id;
        });
        $oldImage = $collection === null ? null : $collection['image'];
        $imageNote = '';
        if ($hasUpload) {
            $stored = collection_form_store_image($upload[0], $collectionId);
            if ($stored['ok']) {
                db_update('collections', ['image' => $stored['image'], 'updated_at' => now_karachi()], ['id' => $collectionId]);
                if ($oldImage !== null && $oldImage !== '' && $oldImage !== $stored['image']) {
                    catalogue_remove_image_files((string) $oldImage);
                }
                catalogue_log('collection', $collectionId, 'collection.image', 'New image on ' . $row['name']);
            } else {
                $imageNote = ' The image was not saved: ' . $stored['error'];
            }
        } elseif (request_post('remove_image') === '1' && $oldImage !== null && $oldImage !== '') {
            db_update('collections', ['image' => null, 'updated_at' => now_karachi()], ['id' => $collectionId]);
            catalogue_remove_image_files((string) $oldImage);
            catalogue_log('collection', $collectionId, 'collection.image', 'Image removed from ' . $row['name']);
        }
        $renamed = $collection !== null && $collection['slug'] !== $row['slug'];
        flash($imageNote === '' ? 'success' : 'error', $row['name'] . ($collection === null ? ' created.' : ' saved.') . ($renamed ? ' The old link will redirect here.' : '') . ' View on site: ' . url('/collections/' . $row['slug']) . $imageNote);
        redirect($imageNote === '' ? '/admin/collections' : '/admin/collections/' . $collectionId, 303);
    }
    http_status(422);
}

render_admin('collection-form.php', [
    'isNew' => $isNew,
    'collection' => $collection,
    'collectionId' => $collection === null ? null : (int) $collection['id'],
    'form' => $form,
    'errors' => $errors,
    'productCount' => $productCount,
    'slugHistory' => $collection === null ? [] : catalogue_slug_history('collection', (int) $collection['id']),
], ['title' => $isNew ? 'New collection' : 'Edit collection', 'body_class' => 'admin t-light admin-collection-form', 'back' => '/admin/collections', 'create_url' => '/admin/collections/new']);
