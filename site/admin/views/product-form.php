<?php
defined('SKYFR') || exit;
$errors = $errors ?? [];
$form = $form ?? [];
$sizes = $sizes ?? [];
$images = $images ?? [];
$alts = $alts ?? [];
$isNew = !empty($isNew);
$err = static fn (string $key): string => (string) ($errors[$key] ?? '');
$labels = [
    'name' => 'Name', 'slug' => 'Link name', 'collection_id' => 'Collection', 'gender' => 'Gender', 'scent_family' => 'Scent family',
    'short_description' => 'Short description', 'description' => 'Description', 'notes_top' => 'Top notes', 'notes_heart' => 'Heart notes', 'notes_base' => 'Base notes',
    'longevity' => 'Longevity', 'sillage' => 'Sillage', 'sort_order' => 'Sort order', 'published_at' => 'Publish date', 'seo_title' => 'SEO title', 'seo_description' => 'SEO description', 'sizes' => 'Sizes',
];
$errorLabel = static function (string $key) use ($labels): string {
    if (isset($labels[$key])) {
        return $labels[$key];
    }
    if (preg_match('/^sizes-(\d+)-(.+)$/', $key, $m)) {
        return ucfirst(str_replace('_', ' ', $m[2]));
    }
    if (str_starts_with($key, 'img-alt-')) {
        return 'Photo alt text';
    }
    return ucfirst(str_replace(['_', '-'], ' ', $key));
};
$seasonOptions = [];
foreach (array_unique(array_merge(CATALOGUE_SEASONS, catalogue_csv_parse($form['best_season']))) as $season) {
    $seasonOptions[$season] = $season;
}
$occasionOptions = [];
foreach (array_unique(array_merge(CATALOGUE_OCCASIONS, catalogue_csv_parse($form['occasion']))) as $occasion) {
    $occasionOptions[$occasion] = $occasion;
}
$sizeRow = static function (string $i, array $size, int $threshold) use ($err): void {
    $n = static fn (string $key): string => 'sizes[' . $i . '][' . $key . ']';
    $id = static fn (string $key): string => 'sizes-' . $i . '-' . $key;
    $persisted = ($size['id'] ?? '') !== '';
    ?>
  <div class="adm-repeater__row" data-repeater-row>
    <div class="adm-repeater__head">
      <span class="adm-repeater__title">Size <span data-repeater-index><?= e(is_numeric($i) ? (string) ((int) $i + 1) : '') ?></span></span>
      <div class="adm-repeater__tools">
        <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" data-move="up" aria-label="Move up">&uarr;</button>
        <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" data-move="down" aria-label="Move down">&darr;</button>
        <button type="button" class="adm-btn adm-btn--danger adm-btn--sm" data-repeater-remove<?= $persisted ? ' data-confirm="Remove this size?' . "\n" . 'Removing a size deletes it from the shop. Past orders keep their own copy of the price." data-confirm-label="Remove size" data-confirm-danger="1"' : '' ?> aria-label="Remove size">Remove</button>
      </div>
    </div>
    <input type="hidden" name="<?= e($n('id')) ?>" value="<?= e((string) ($size['id'] ?? '')) ?>">
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'text', 'name' => $n('size_label'), 'id' => $id('size_label'), 'label' => 'Label', 'value' => $size['size_label'] ?? '', 'required' => true, 'placeholder' => '50ml', 'maxlength' => 20, 'error' => $err($id('size_label')), 'attr' => ['data-size-label' => '1', 'autocapitalize' => 'none']]); ?>
      <?php partial_admin('field.php', ['type' => 'int', 'name' => $n('size_ml'), 'id' => $id('size_ml'), 'label' => 'Millilitres', 'value' => $size['size_ml'] ?? '', 'placeholder' => '50', 'error' => $err($id('size_ml')), 'attr' => ['data-size-ml' => '1']]); ?>
    </div>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => $n('sku'), 'id' => $id('sku'), 'label' => 'SKU', 'value' => $size['sku'] ?? '', 'placeholder' => 'Leave blank to generate', 'maxlength' => 40, 'help' => 'Your stock code. Must be unique across the shop.', 'error' => $err($id('sku')), 'attr' => ['data-size-sku' => '1', 'autocapitalize' => 'characters', 'spellcheck' => 'false', 'class' => 'adm-mono']]); ?>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'money', 'name' => $n('price'), 'id' => $id('price'), 'label' => 'Price', 'value' => $size['price'] ?? '', 'required' => true, 'error' => $err($id('price'))]); ?>
      <?php partial_admin('field.php', ['type' => 'money', 'name' => $n('sale_price'), 'id' => $id('sale_price'), 'label' => 'Sale price', 'value' => $size['sale_price'] ?? '', 'help' => 'Blank = not on sale.', 'error' => $err($id('sale_price'))]); ?>
    </div>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'int', 'name' => $n('stock'), 'id' => $id('stock'), 'label' => 'Stock', 'value' => $size['stock'] ?? '0', 'required' => true, 'error' => $err($id('stock')), 'help' => ($size['stock'] ?? '') !== '' && is_numeric($size['stock']) && (int) $size['stock'] <= 0 ? 'Sold out — customers see SOLD OUT for this size.' : ((($size['low_stock_threshold'] ?? '') !== '' ? (int) $size['low_stock_threshold'] : $threshold) > 0 && is_numeric($size['stock'] ?? '') && (int) $size['stock'] > 0 && (int) $size['stock'] <= (($size['low_stock_threshold'] ?? '') !== '' ? (int) $size['low_stock_threshold'] : $threshold) ? 'Low stock — at or below the alert level.' : '')]); ?>
      <?php partial_admin('field.php', ['type' => 'int', 'name' => $n('low_stock_threshold'), 'id' => $id('low_stock_threshold'), 'label' => 'Low-stock alert at', 'value' => $size['low_stock_threshold'] ?? '', 'placeholder' => (string) $threshold, 'help' => 'Blank uses the shop default (' . $threshold . ').', 'error' => $err($id('low_stock_threshold'))]); ?>
    </div>
    <label class="adm-check"><input type="radio" class="adm-check__input" name="default_size" value="<?= e($i) ?>"<?= !empty($size['is_default']) ? ' checked' : '' ?>><span class="adm-check__box" aria-hidden="true"></span><span class="adm-check__label">Default size (selected first on the product page)</span></label>
  </div>
    <?php
};
?>
<?php partial_admin('page-header.php', [
    'title' => $isNew ? 'New product' : (string) ($product['name'] ?? 'Edit product'),
    'subtitle' => $isNew ? 'Name, sizes and prices first. Photos come after the first save.' : ($hasOrders ? 'On ' . $orderLines . ' order line' . ($orderLines === 1 ? '' : 's') . ' — it can be hidden but never deleted.' : 'Not on any order yet.'),
    'back' => '/admin/products',
    'back_label' => 'Products',
    'actions' => $isNew ? [] : [['label' => 'View on site', 'href' => url('/product/' . $product['slug']), 'external' => true]],
]); ?>
<?php if ($errors !== []): ?>
<div class="adm-summary" role="alert">
  Please fix <?= count($errors) === 1 ? 'this' : 'these ' . count($errors) . ' things' ?> before saving:
  <ul>
<?php foreach ($errors as $key => $message): ?>
    <li><a href="#<?= e($key) ?>"><?= e($errorLabel($key)) ?></a>: <?= e($message) ?></li>
<?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<form method="post" action="<?= e(url($isNew ? '/admin/products/new' : '/admin/products/' . $productId)) ?>" class="adm-form" id="product-form" data-guard novalidate>
  <?= csrf_field() ?>
  <div class="adm-form__section" id="basics">
    <h2 class="adm-form__section-title">Basics</h2>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'value' => $form['name'], 'required' => true, 'maxlength' => 120, 'error' => $err('name'), 'attr' => ['autocomplete' => 'off']]); ?>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'slug', 'label' => 'Link name', 'value' => $form['slug'], 'maxlength' => 160, 'help' => 'Appears in the web address: ' . SITE_URL . '/product/…  Fills in from the name until you edit it.' . ($isNew ? '' : ' Changing it keeps the old link working with a redirect.'), 'error' => $err('slug'), 'attr' => ['data-slug-from' => 'name', 'autocapitalize' => 'none', 'spellcheck' => 'false', 'class' => 'adm-mono']]); ?>
<?php if (!empty($slugHistory)): ?>
    <p class="adm-note">Old links that redirect here: <?php foreach ($slugHistory as $i => $old): ?><?= $i > 0 ? ', ' : '' ?><span class="adm-mono"><?= e($old['old_slug']) ?></span> (<?= e((string) (int) $old['hit_count']) ?> visits)<?php endforeach; ?></p>
<?php endif; ?>
    <?php partial_admin('field.php', ['type' => 'select', 'name' => 'collection_id', 'label' => 'Collection', 'value' => $form['collection_id'], 'options' => $collectionsOptions, 'required' => true, 'error' => $err('collection_id')]); ?>
    <?php partial_admin('field.php', ['type' => 'radio', 'name' => 'gender', 'label' => 'Gender', 'value' => $form['gender'], 'options' => CATALOGUE_GENDER_LABELS, 'required' => true, 'error' => $err('gender')]); ?>
    <?php partial_admin('field.php', ['type' => 'select', 'name' => 'scent_family', 'label' => 'Scent family', 'value' => $form['scent_family'], 'options' => $familyOptions, 'help' => 'Drives the /scent landing pages and the Scent Finder. Manage the list under Scent families.', 'error' => $err('scent_family')]); ?>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'short_description', 'label' => 'Short description', 'value' => $form['short_description'], 'rows' => 2, 'maxlength' => 200, 'help' => 'One line under the name on cards and in search results.', 'error' => $err('short_description')]); ?>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'value' => $form['description'], 'rows' => 8, 'help' => 'Plain text. Leave a blank line between paragraphs.', 'error' => $err('description')]); ?>
  </div>
  <div class="adm-form__section" id="sizes">
    <h2 class="adm-form__section-title">Sizes, prices and stock</h2>
<?php if ($err('sizes') !== ''): ?>
    <p class="adm-field__error" id="sizes-error"><?= e($err('sizes')) ?></p>
<?php endif; ?>
    <div data-repeater="sizes">
      <div data-repeater-rows>
<?php foreach ($sizes as $i => $size): ?>
<?php $sizeRow((string) $i, $size, (int) $threshold); ?>
<?php endforeach; ?>
      </div>
      <template data-repeater-template><?php $sizeRow('__i__', product_form_size_defaults(), (int) $threshold); ?></template>
      <button type="button" class="adm-btn adm-btn--ghost adm-btn--block" data-repeater-add>+ Add size</button>
    </div>
    <p class="adm-note">Stock here is an absolute number. Orders reduce it automatically; cancellations put it back.</p>
  </div>
  <div class="adm-form__section" id="images">
    <h2 class="adm-form__section-title">Photos</h2>
<?php if ($isNew): ?>
    <p class="adm-muted">Save the product first, then add photos here. Use portrait shots — they are cropped to 4:5.</p>
<?php else: ?>
    <div class="adm-dropzone" data-uploader="<?= e(url('/admin/products/' . $productId . '/images')) ?>" data-uploader-target="product-tiles" data-uploader-max="<?= e((string) UPLOAD_MAX_PRODUCT_BYTES) ?>">
      <label class="adm-btn adm-btn--gold adm-btn--block" for="product-photo-input">Choose photos</label>
      <input type="file" id="product-photo-input" class="adm-dropzone__input" name="image" form="product-upload-form" accept="image/jpeg,image/png,image/webp" multiple>
      <p class="adm-note">JPG, PNG or WEBP up to <?= e(upload_human_size(UPLOAD_MAX_PRODUCT_BYTES)) ?> each. Drop files here on desktop. Cropped to 4:5; the first photo is the main one.</p>
      <button type="submit" class="adm-btn adm-btn--ghost" form="product-upload-form" data-nojs-only>Upload chosen photo</button>
      <p data-uploader-status role="status" class="adm-field__error"></p>
    </div>
    <p class="adm-muted" data-images-empty<?= $images === [] ? '' : ' hidden' ?>>No photos yet. Products without a photo show a placeholder on the shop.</p>
    <ul class="adm-tiles" id="product-tiles" data-sortable data-sortable-url="<?= e(url('/admin/products/' . $productId . '/images/order')) ?>">
<?php foreach ($images as $image): ?>
<?= catalogue_tile_html(['alt_text' => $alts[(int) $image['id']] ?? $image['alt_text']] + $image, $product, catalogue_product_sizes($productId)) ?>
<?php endforeach; ?>
    </ul>
<?php foreach ($images as $image): ?>
<?php if ($err('img-alt-' . (int) $image['id']) !== ''): ?>
    <p class="adm-field__error" id="img-alt-<?= e((string) (int) $image['id']) ?>-error"><?= e($err('img-alt-' . (int) $image['id'])) ?></p>
<?php endif; ?>
<?php endforeach; ?>
    <p class="adm-note">Drag the ≡ handle or use the arrows to reorder; the order saves immediately. Alt text saves with the product and is required — it is what Google and screen readers read.</p>
<?php endif; ?>
  </div>
  <div class="adm-form__section" id="scent">
    <h2 class="adm-form__section-title">Scent profile</h2>
<?php foreach (['notes_top' => ['Top notes', 'What you smell first, for the opening 15 minutes.'], 'notes_heart' => ['Heart notes', 'The character of the perfume for the next few hours.'], 'notes_base' => ['Base notes', 'What lingers on skin and clothes.']] as $key => [$label, $help]): ?>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => $key, 'label' => $label, 'value' => $form[$key], 'maxlength' => 255, 'placeholder' => 'Bergamot, Pink Pepper, Violet Leaf', 'help' => $help . ' Separate notes with commas, in the perfumer\'s order.', 'error' => $err($key), 'attr' => ['data-notes-preview' => $key . '-chips']]); ?>
    <div class="adm-cluster" id="<?= e($key) ?>-chips" aria-hidden="true"></div>
<?php endforeach; ?>
    <?php partial_admin('field.php', ['type' => 'segment', 'name' => 'longevity', 'label' => 'Longevity (hours on skin)', 'value' => $form['longevity'], 'options' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'], 'help' => '1 = under 2 hours, 5 = all day. Leave unset to hide the meter.', 'error' => $err('longevity')]); ?>
    <?php partial_admin('field.php', ['type' => 'segment', 'name' => 'sillage', 'label' => 'Sillage (how far it projects)', 'value' => $form['sillage'], 'options' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'], 'help' => '1 = skin scent, 5 = fills a room.', 'error' => $err('sillage')]); ?>
    <?php partial_admin('field.php', ['type' => 'checkgroup', 'name' => 'best_season', 'label' => 'Best season', 'value' => catalogue_csv_parse($form['best_season']), 'options' => $seasonOptions]); ?>
    <?php partial_admin('field.php', ['type' => 'checkgroup', 'name' => 'occasion', 'label' => 'Occasion', 'value' => catalogue_csv_parse($form['occasion']), 'options' => $occasionOptions]); ?>
  </div>
  <div class="adm-form__section" id="visibility">
    <h2 class="adm-form__section-title">Visibility</h2>
    <?php partial_admin('field.php', ['type' => 'segment', 'name' => 'is_active', 'label' => 'Status', 'value' => (string) (int) $form['is_active'], 'options' => ['1' => 'Live', '0' => 'Hidden'], 'help' => 'Hidden products keep their link but never show in the shop.']); ?>
    <?php partial_admin('field.php', ['type' => 'checkbox', 'name' => 'is_featured', 'label' => 'Featured on the home page', 'value' => (int) $form['is_featured'] === 1]); ?>
    <?php partial_admin('field.php', ['type' => 'checkbox', 'name' => 'is_new', 'label' => 'Show the NEW badge', 'value' => (int) $form['is_new'] === 1, 'help' => 'NEW also shows automatically for 30 days after the publish date.']); ?>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'date', 'name' => 'published_at', 'label' => 'Publish date', 'value' => $form['published_at'], 'help' => 'Orders New Arrivals. Blank sorts last.', 'error' => $err('published_at')]); ?>
      <?php partial_admin('field.php', ['type' => 'int', 'name' => 'sort_order', 'label' => 'Sort order', 'value' => (string) $form['sort_order'], 'help' => 'Lower numbers show first.', 'error' => $err('sort_order')]); ?>
    </div>
  </div>
  <div class="adm-form__section" id="seo">
    <h2 class="adm-form__section-title">Search engines</h2>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'seo_title', 'label' => 'SEO title', 'value' => $form['seo_title'], 'maxlength' => 60, 'attr' => ['data-counter-warn' => '55'], 'help' => 'Blank uses "Name — Family perfume". "| Sky Fragrances" is added automatically.', 'error' => $err('seo_title')]); ?>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'seo_description', 'label' => 'SEO description', 'value' => $form['seo_description'], 'rows' => 3, 'maxlength' => 155, 'attr' => ['data-counter-warn' => '140'], 'help' => 'Blank uses the short description.', 'error' => $err('seo_description')]); ?>
    <div class="adm-card" data-seo-preview data-seo-base="<?= e(SITE_URL) ?>" aria-label="Google preview">
      <p class="adm-note" style="margin:0 0 4px">How it may look on Google</p>
      <p class="adm-mono adm-note" data-seo-url style="margin:0"></p>
      <p style="margin:2px 0;color:#1a0dab;font-size:1.05rem" data-seo-title></p>
      <p class="adm-muted" data-seo-desc style="margin:0"></p>
    </div>
  </div>
  <?php partial_admin('action-bar.php', ['note' => $isNew ? 'Photos can be added after saving.' : 'Saves every section above.', 'buttons' => [
      ['label' => $isNew ? 'Create product' : 'Save', 'name' => 'after', 'value' => $isNew ? 'stay' : 'list'],
      ['label' => 'Save & add another', 'name' => 'after', 'value' => 'add', 'variant' => 'ghost'],
  ]]); ?>
</form>
<?php if (!$isNew): ?>
<form method="post" action="<?= e(url('/admin/products/' . $productId . '/images')) ?>" enctype="multipart/form-data" id="product-upload-form" hidden>
  <?= csrf_field() ?>
</form>
<div class="adm-card adm-card--danger adm-form">
  <h2 class="adm-card__title">Remove this product</h2>
<?php if ($hasOrders): ?>
  <p class="adm-muted">This perfume is on <?= e((string) $orderLines) ?> order line<?= $orderLines === 1 ? '' : 's' ?>, so it cannot be deleted. Deleting hides it from the shop and keeps it on your past orders and invoices.</p>
<?php else: ?>
  <p class="adm-muted">No orders reference it yet, so deleting removes the product, its sizes and its photos for good. Prefer hiding it if you might bring it back.</p>
<?php endif; ?>
  <div class="adm-cluster">
    <?php partial_admin('button.php', ['label' => (int) $form['is_active'] === 1 ? 'Hide from shop' : 'Make live', 'post' => '/admin/products/' . $productId . '/toggle']); ?>
    <?php partial_admin('button.php', ['label' => $hasOrders ? 'Delete (hides it)' : 'Delete for good', 'variant' => 'danger', 'post' => '/admin/products/' . $productId . '/delete', 'confirm' => 'Delete ' . $product['name'] . "?\n" . ($hasOrders ? 'It stays on past orders and is hidden from the shop.' : 'The product, its sizes and photos are removed. This cannot be undone.'), 'confirm_word' => 'DELETE', 'confirm_label' => 'Yes, delete']); ?>
  </div>
</div>
<?php endif; ?>
<script src="<?= e(asset('js/admin-catalogue.js')) ?>" defer></script>
