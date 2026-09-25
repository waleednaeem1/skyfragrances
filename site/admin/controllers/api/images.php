<?php
defined('SKYFR') || exit;

require_once dirname(__DIR__) . '/catalogue.php';

function images_reply(bool $ok, array $payload, int $productId, string $flashMessage, int $status = 200): never
{
    if (request_is_ajax()) {
        json(['ok' => $ok] + $payload, $status);
    }
    flash($ok ? 'success' : 'error', $flashMessage);
    redirect('/admin/products/' . $productId . '#images', 303);
}

function images_ids(int $productId): array
{
    return array_map('intval', array_column(catalogue_product_images($productId), 'id'));
}

function images_renumber(int $productId, array $orderedIds): void
{
    foreach (array_values($orderedIds) as $i => $imageId) {
        db_update('product_images', ['sort_order' => ($i + 1) * 10, 'is_primary' => $i === 0 ? 1 : 0], ['id' => $imageId, 'product_id' => $productId]);
    }
}

$productId = (int) $params['id'];
$product = db_fetch('SELECT id, name, slug FROM products WHERE id = :id', ['id' => $productId]);
if ($product === null) {
    abort(404, 'That product does not exist.');
}
$routeName = (string) ($route['name'] ?? '');

if ($routeName === 'admin.products.images') {
    $files = upload_files_from_request('image');
    if ($files === []) {
        images_reply(false, ['error' => 'no_file', 'message' => 'Choose a photo to upload.'], $productId, 'Choose a photo to upload.', 422);
    }
    $check = upload_validate_product($files[0]);
    if (!$check['ok']) {
        images_reply(false, ['error' => 'invalid', 'message' => $check['error']], $productId, $check['error'], 422);
    }
    if ((int) db_fetch_column('SELECT COUNT(*) FROM product_images WHERE product_id = :id', ['id' => $productId]) >= 12) {
        images_reply(false, ['error' => 'limit', 'message' => 'A product can have at most 12 photos. Delete one first.'], $productId, 'A product can have at most 12 photos. Delete one first.', 422);
    }
    try {
        $stem = image_derive_product((string) $files[0]['tmp_name'], $productId);
        $dir = APP_ROOT . '/uploads/products/' . $productId;
        $filename = catalogue_write_base_copy($dir, $stem);
        [$width, $height] = catalogue_image_dimensions($dir, $stem);
    } catch (Throwable $e) {
        log_write('error', 'Product image processing failed', ['product' => $productId, 'error' => $e->getMessage()]);
        images_reply(false, ['error' => 'processing', 'message' => 'The photo could not be processed. Try a JPG exported at a smaller size.'], $productId, 'The photo could not be processed. Try a JPG exported at a smaller size.', 500);
    }
    $sizes = catalogue_product_sizes($productId);
    $imageId = catalogue_image_insert($productId, $filename, catalogue_default_alt((string) $product['name'], $sizes), $width, $height);
    catalogue_log('product', $productId, 'product.image_add', 'Added a photo to ' . $product['name']);
    $image = db_fetch('SELECT id, product_id, filename, alt_text, width, height, sort_order, is_primary FROM product_images WHERE id = :id', ['id' => $imageId]);
    images_reply(true, ['id' => $imageId, 'html' => catalogue_tile_html($image, $product, $sizes), 'ids' => images_ids($productId)], $productId, 'Photo added to ' . $product['name'] . '. Check its alt text and save.');
}

if ($routeName === 'admin.products.images.order') {
    $posted = request_input('ids', []);
    $current = images_ids($productId);
    $ordered = [];
    foreach (is_array($posted) ? $posted : [] as $value) {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id !== false && in_array((int) $id, $current, true) && !in_array((int) $id, $ordered, true)) {
            $ordered[] = (int) $id;
        }
    }
    foreach ($current as $id) {
        if (!in_array($id, $ordered, true)) {
            $ordered[] = $id;
        }
    }
    db_transaction(static fn () => images_renumber($productId, $ordered));
    images_reply(true, ['ids' => $ordered], $productId, 'Photo order saved.');
}

$image = db_fetch('SELECT id, filename, is_primary FROM product_images WHERE id = :img AND product_id = :pid', ['img' => (int) $params['img'], 'pid' => $productId]);
if ($image === null) {
    images_reply(false, ['error' => 'missing', 'message' => 'That photo is no longer on this product.', 'ids' => images_ids($productId)], $productId, 'That photo is no longer on this product.', 404);
}

if ($routeName === 'admin.products.images.primary') {
    $ordered = array_values(array_merge([(int) $image['id']], array_diff(images_ids($productId), [(int) $image['id']])));
    db_transaction(static fn () => images_renumber($productId, $ordered));
    catalogue_log('product', $productId, 'product.image_primary', 'Changed the main photo of ' . $product['name']);
    images_reply(true, ['ids' => $ordered], $productId, 'Main photo updated.');
}

if ($routeName === 'admin.products.images.delete') {
    db_transaction(static function () use ($productId, $image): void {
        db_delete('product_images', ['id' => (int) $image['id']]);
        images_renumber($productId, images_ids($productId));
    });
    catalogue_remove_image_files((string) $image['filename']);
    @rmdir(APP_ROOT . '/uploads/products/' . $productId);
    catalogue_log('product', $productId, 'product.image_delete', 'Deleted a photo from ' . $product['name']);
    images_reply(true, ['ids' => images_ids($productId), 'deleted' => (int) $image['id']], $productId, 'Photo deleted.');
}

abort(404);
