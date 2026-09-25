<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/catalogue.php';

function scent_form_defaults(): array
{
    return ['name' => '', 'slug' => '', 'intro' => '', 'sort_order' => '', 'is_active' => 1, 'seo_title' => '', 'seo_description' => ''];
}

function scent_form_validate(array &$form, ?int $familyId): array
{
    $errors = [];
    $form['name'] = trim(preg_replace('/\s+/u', ' ', $form['name']) ?? '');
    if ($form['name'] === '') {
        $errors['name'] = 'Give the scent family a name, such as Woody or Fresh & Citrus.';
    } elseif (mb_strlen($form['name']) > 60) {
        $errors['name'] = 'Keep the name to 60 characters.';
    } elseif (db_exists('SELECT id FROM scent_families WHERE name = :n' . ($familyId !== null ? ' AND id <> :id' : ''), ['n' => $form['name']] + ($familyId !== null ? ['id' => $familyId] : []))) {
        $errors['name'] = 'A scent family called "' . $form['name'] . '" already exists.';
    }
    $form['slug'] = catalogue_slug_input($form['slug'], $form['name']);
    if ($form['slug'] === '' || !preg_match(CATALOGUE_SLUG_PATTERN, $form['slug'])) {
        $errors['slug'] = 'The link name can only use lowercase letters, numbers and hyphens.';
    } elseif (catalogue_slug_taken('scent_families', $form['slug'], $familyId)) {
        $errors['slug'] = 'Another scent family already uses the link name "' . $form['slug'] . '".';
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

function scent_form_save(array $form, ?array $family): int
{
    return db_transaction(static function () use ($form, $family): int {
        $now = now_karachi();
        $row = [
            'name' => $form['name'],
            'slug' => $form['slug'],
            'intro' => catalogue_text($form['intro'], 65000),
            'sort_order' => $form['sort_order'] === '' ? (int) db_fetch_column('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM scent_families') : (int) $form['sort_order'],
            'is_active' => (int) $form['is_active'],
            'seo_title' => catalogue_text($form['seo_title'], 160),
            'seo_description' => catalogue_text($form['seo_description'], 255),
            'updated_at' => $now,
        ];
        if ($family === null) {
            $id = db_insert('scent_families', $row + ['created_at' => $now]);
            catalogue_log('scent_family', $id, 'scent_family.create', 'Created scent family ' . $row['name'], null, $row);
            return $id;
        }
        $id = (int) $family['id'];
        db_update('scent_families', $row, ['id' => $id]);
        if ($family['name'] !== $row['name']) {
            $moved = db_query('UPDATE products SET scent_family = :new, updated_at = :now WHERE scent_family = :old', ['new' => $row['name'], 'now' => $now, 'old' => $family['name']])->rowCount();
            catalogue_log('scent_family', $id, 'scent_family.rename', 'Renamed ' . $family['name'] . ' to ' . $row['name'] . ' on ' . $moved . ' product' . ($moved === 1 ? '' : 's'));
        }
        if ($family['slug'] !== $row['slug']) {
            catalogue_slug_rename('scent', $id, (string) $family['slug'], $row['slug']);
            catalogue_log('scent_family', $id, 'scent_family.slug', 'Link changed from ' . $family['slug'] . ' to ' . $row['slug'] . ' (old link redirects)');
        }
        $changed = catalogue_changed_fields($family, $row, array_keys(scent_form_defaults()));
        catalogue_log('scent_family', $id, 'scent_family.update', $changed === [] ? 'Saved ' . $row['name'] . ' with no field changes' : 'Updated ' . $row['name'] . ': ' . implode(', ', $changed), array_intersect_key($family, $row), $row);
        return $id;
    });
}

$routeName = (string) ($route['name'] ?? '');

if ($routeName === 'admin.scent_families.toggle') {
    $family = db_fetch('SELECT id, name, is_active FROM scent_families WHERE id = :id', ['id' => (int) $params['id']]);
    if ($family === null) {
        flash('error', 'That scent family no longer exists.');
        redirect('/admin/scent-families', 303);
    }
    $live = (int) $family['is_active'] === 1 ? 0 : 1;
    db_update('scent_families', ['is_active' => $live, 'updated_at' => now_karachi()], ['id' => (int) $family['id']]);
    catalogue_log('scent_family', (int) $family['id'], $live ? 'scent_family.live' : 'scent_family.hide', ($live ? 'Made live: ' : 'Hidden: ') . $family['name']);
    flash('success', $family['name'] . ($live ? ' is live again at /scent/.' : ' is hidden. Its products stay live; the /scent page now 404s.'));
    redirect('/admin/scent-families', 303);
}

if ($routeName === 'admin.scent_families') {
    $families = db_fetch_all(
        'SELECT sf.id, sf.name, sf.slug, sf.intro, sf.sort_order, sf.is_active,
            (SELECT COUNT(*) FROM products p WHERE p.scent_family = sf.name AND p.deleted_at IS NULL) AS product_count
         FROM scent_families sf ORDER BY sf.sort_order ASC, sf.name ASC'
    );
    $orphans = db_fetch_all('SELECT p.scent_family, COUNT(*) AS n FROM products p WHERE p.deleted_at IS NULL AND p.scent_family IS NOT NULL AND p.scent_family NOT IN (SELECT name FROM scent_families) GROUP BY p.scent_family');
    render_admin('scent-families.php', ['families' => $families, 'orphans' => $orphans], ['title' => 'Scent families', 'body_class' => 'admin t-light admin-scent-families', 'create_url' => '/admin/scent-families/new']);
}

$isNew = $routeName === 'admin.scent_families.new';
$family = null;
if (!$isNew) {
    $family = db_fetch('SELECT * FROM scent_families WHERE id = :id', ['id' => (int) $params['id']]);
    if ($family === null) {
        abort(404, 'That scent family does not exist.');
    }
}
$form = scent_form_defaults();
if ($family !== null) {
    foreach ($form as $key => $default) {
        $form[$key] = (string) ($family[$key] ?? $default);
    }
    $form['is_active'] = (int) $family['is_active'];
}
$errors = [];
if (request_method() === 'POST') {
    foreach (['name', 'slug', 'intro', 'sort_order', 'seo_title', 'seo_description'] as $key) {
        $form[$key] = trim((string) request_post($key, ''));
    }
    $form['is_active'] = request_post('is_active', '1') === '0' ? 0 : 1;
    $errors = scent_form_validate($form, $family === null ? null : (int) $family['id']);
    if ($errors === []) {
        $renamedCount = $family === null || $family['name'] === $form['name'] ? 0 : (int) db_fetch_column('SELECT COUNT(*) FROM products WHERE scent_family = :n', ['n' => $family['name']]);
        scent_form_save($form, $family);
        $renamed = $family !== null && $family['slug'] !== $form['slug'];
        flash('success', $form['name'] . ($family === null ? ' created.' : ' saved.') . ($family !== null && $family['name'] !== $form['name'] && $renamedCount > 0 ? ' ' . $renamedCount . ' product' . ($renamedCount === 1 ? ' was' : 's were') . ' moved to the new name.' : '') . ($renamed ? ' The old link will redirect here.' : '') . ' View on site: ' . url('/scent/' . $form['slug']));
        redirect('/admin/scent-families', 303);
    }
    http_status(422);
}
render_admin('scent-family-form.php', [
    'isNew' => $isNew,
    'family' => $family,
    'form' => $form,
    'errors' => $errors,
    'productCount' => $family === null ? 0 : (int) db_fetch_column('SELECT COUNT(*) FROM products WHERE scent_family = :n AND deleted_at IS NULL', ['n' => $family['name']]),
    'slugHistory' => $family === null ? [] : catalogue_slug_history('scent', (int) $family['id']),
], ['title' => $isNew ? 'New scent family' : 'Edit scent family', 'body_class' => 'admin t-light admin-scent-family-form', 'back' => '/admin/scent-families', 'create_url' => '/admin/scent-families/new']);
