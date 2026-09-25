<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/catalogue.php';

if (($route['name'] ?? '') === 'admin.collections.delete') {
    $collection = db_fetch('SELECT id, name, slug, image FROM collections WHERE id = :id', ['id' => (int) $params['id']]);
    if ($collection === null) {
        flash('error', 'That collection no longer exists.');
        redirect('/admin/collections', 303);
    }
    $count = (int) db_fetch_column('SELECT COUNT(*) FROM products WHERE collection_id = :id', ['id' => (int) $collection['id']]);
    if ($count > 0) {
        flash('error', $count . ' product' . ($count === 1 ? ' is' : 's are') . ' in ' . $collection['name'] . '. Move them first, or hide the collection instead.');
        redirect('/admin/collections', 303);
    }
    if ((string) request_post('confirm_word', '') !== 'DELETE') {
        flash('error', 'Type DELETE to confirm removing ' . $collection['name'] . '.');
        redirect('/admin/collections', 303);
    }
    db_transaction(static function () use ($collection): void {
        db_delete('collections', ['id' => (int) $collection['id']]);
        db_query('DELETE FROM slug_redirects WHERE entity_type = :type AND entity_id = :id', ['type' => 'collection', 'id' => (int) $collection['id']]);
    });
    if (!empty($collection['image'])) {
        catalogue_remove_image_files((string) $collection['image']);
    }
    catalogue_log('collection', (int) $collection['id'], 'collection.delete', 'Deleted collection ' . $collection['name']);
    flash('success', $collection['name'] . ' deleted.');
    redirect('/admin/collections', 303);
}

$collections = db_fetch_all(
    'SELECT c.id, c.name, c.slug, c.tagline, c.image, c.sort_order, c.is_active, c.show_on_home,
        (SELECT COUNT(*) FROM products p WHERE p.collection_id = c.id AND p.deleted_at IS NULL) AS product_count,
        (SELECT COUNT(*) FROM products p WHERE p.collection_id = c.id AND p.deleted_at IS NULL AND p.is_active = 1) AS live_count
     FROM collections c ORDER BY c.sort_order ASC, c.name ASC'
);

render_admin('collections.php', [
    'collections' => $collections,
    'uncollected' => (int) db_fetch_column('SELECT COUNT(*) FROM products WHERE collection_id IS NULL AND deleted_at IS NULL'),
], ['title' => 'Collections', 'body_class' => 'admin t-light admin-collections', 'create_url' => '/admin/collections/new']);
