<?php
defined('SKYFR') || exit;
$errors = $errors ?? [];
$form = $form ?? [];
$isNew = !empty($isNew);
$err = static fn (string $key): string => (string) ($errors[$key] ?? '');
$labels = ['name' => 'Name', 'slug' => 'Link name', 'tagline' => 'Tagline', 'description' => 'Description', 'mood' => 'Mood', 'image' => 'Image', 'sort_order' => 'Sort order', 'seo_title' => 'SEO title', 'seo_description' => 'SEO description'];
?>
<?php partial_admin('page-header.php', [
    'title' => $isNew ? 'New collection' : (string) $collection['name'],
    'subtitle' => $isNew ? 'A collection is a landing page that groups perfumes.' : $productCount . ' product' . ($productCount === 1 ? '' : 's') . ' in this collection.',
    'back' => '/admin/collections',
    'back_label' => 'Collections',
    'actions' => $isNew ? [] : [['label' => 'View on site', 'href' => url('/collections/' . $collection['slug']), 'external' => true], ['label' => 'Products in it', 'href' => '/admin/products?collection_id=' . (int) $collection['id'] . '&status=all']],
]); ?>
<?php if ($errors !== []): ?>
<div class="adm-summary" role="alert">
  Please fix <?= count($errors) === 1 ? 'this' : 'these ' . count($errors) . ' things' ?> before saving:
  <ul>
<?php foreach ($errors as $key => $message): ?>
    <li><a href="#<?= e($key) ?>"><?= e($labels[$key] ?? ucfirst($key)) ?></a>: <?= e($message) ?></li>
<?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<form method="post" action="<?= e(url($isNew ? '/admin/collections/new' : '/admin/collections/' . $collectionId)) ?>" class="adm-form" enctype="multipart/form-data" data-guard novalidate>
  <?= csrf_field() ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Basics</h2>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'value' => $form['name'], 'required' => true, 'maxlength' => 80, 'error' => $err('name')]); ?>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'slug', 'label' => 'Link name', 'value' => $form['slug'], 'maxlength' => 160, 'help' => 'Web address: ' . SITE_URL . '/collections/…' . ($isNew ? '' : ' Changing it keeps the old link working with a redirect.'), 'error' => $err('slug'), 'attr' => ['data-slug-from' => 'name', 'autocapitalize' => 'none', 'spellcheck' => 'false', 'class' => 'adm-mono']]); ?>
<?php if (!empty($slugHistory)): ?>
    <p class="adm-note">Old links that redirect here: <?php foreach ($slugHistory as $i => $old): ?><?= $i > 0 ? ', ' : '' ?><span class="adm-mono"><?= e($old['old_slug']) ?></span> (<?= e((string) (int) $old['hit_count']) ?> visits)<?php endforeach; ?></p>
<?php endif; ?>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'tagline', 'label' => 'Tagline', 'value' => $form['tagline'], 'maxlength' => 120, 'help' => 'One line under the name on the collection page and home tiles.', 'error' => $err('tagline')]); ?>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'description', 'label' => 'Description', 'value' => $form['description'], 'rows' => 5, 'help' => 'Plain text. Shown at the top of the collection page.', 'error' => $err('description')]); ?>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'mood', 'label' => 'Mood', 'value' => $form['mood'], 'maxlength' => 160, 'help' => 'A few words on the feeling, e.g. "cool mornings, citrus and sea air".', 'error' => $err('mood')]); ?>
  </div>
  <div class="adm-form__section" id="image">
    <h2 class="adm-form__section-title">Image</h2>
<?php if (!$isNew && !empty($collection['image'])): ?>
    <div class="adm-media">
      <img class="adm-thumb" style="width:96px;height:120px" src="<?= e(catalogue_collection_image_url($collection['image'], 'card')) ?>" alt="" width="96" height="120">
      <div class="adm-media__body">
        <span class="adm-media__meta adm-mono"><?= e((string) $collection['image']) ?></span>
        <?php partial_admin('field.php', ['type' => 'checkbox', 'name' => 'remove_image', 'label' => 'Remove this image', 'value' => false, 'compact' => true]); ?>
      </div>
    </div>
<?php endif; ?>
    <div class="adm-field<?= $err('image') !== '' ? ' adm-field--error' : '' ?>">
      <label class="adm-field__label" for="image"><?= !$isNew && !empty($collection['image']) ? 'Replace image' : 'Upload image' ?></label>
      <input class="adm-input" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"<?= $err('image') !== '' ? ' aria-invalid="true" aria-describedby="image-error"' : '' ?>>
<?php if ($err('image') !== ''): ?>
      <p class="adm-field__error" id="image-error"><?= e($err('image')) ?></p>
<?php endif; ?>
      <p class="adm-field__help">JPG, PNG or WEBP up to <?= e(upload_human_size(UPLOAD_MAX_PRODUCT_BYTES)) ?>. Used on the collection hero and the home tiles.</p>
    </div>
  </div>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Visibility</h2>
    <?php partial_admin('field.php', ['type' => 'segment', 'name' => 'is_active', 'label' => 'Status', 'value' => (string) (int) $form['is_active'], 'options' => ['1' => 'Live', '0' => 'Hidden'], 'help' => 'Hidden collections disappear from menus and their landing page; the products in them stay live.']); ?>
    <?php partial_admin('field.php', ['type' => 'checkbox', 'name' => 'show_on_home', 'label' => 'Show in the home page collection band', 'value' => (int) $form['show_on_home'] === 1]); ?>
    <?php partial_admin('field.php', ['type' => 'int', 'name' => 'sort_order', 'label' => 'Sort order', 'value' => (string) $form['sort_order'], 'placeholder' => 'Next available', 'help' => 'Lower numbers show first. You can also use Move up / Move down on the list.', 'error' => $err('sort_order')]); ?>
  </div>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Search engines</h2>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'seo_title', 'label' => 'SEO title', 'value' => $form['seo_title'], 'maxlength' => 60, 'attr' => ['data-counter-warn' => '55'], 'help' => 'Blank uses "Name Collection — Perfumes | Sky Fragrances".', 'error' => $err('seo_title')]); ?>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'seo_description', 'label' => 'SEO description', 'value' => $form['seo_description'], 'rows' => 3, 'maxlength' => 155, 'attr' => ['data-counter-warn' => '140'], 'help' => 'Blank uses the tagline.', 'error' => $err('seo_description')]); ?>
  </div>
  <?php partial_admin('action-bar.php', ['buttons' => [['label' => $isNew ? 'Create collection' : 'Save']]]); ?>
</form>
<?php if (!$isNew): ?>
<div class="adm-card adm-card--danger adm-form">
  <h2 class="adm-card__title">Remove this collection</h2>
<?php if ($productCount > 0): ?>
  <p class="adm-muted"><?= e((string) $productCount) ?> product<?= $productCount === 1 ? ' is' : 's are' ?> in this collection. Move them first, or simply hide the collection — that is usually what you want.</p>
  <div class="adm-cluster">
    <?php partial_admin('button.php', ['label' => 'See those products', 'href' => '/admin/products?collection_id=' . (int) $collection['id'] . '&status=all']); ?>
    <?php partial_admin('button.php', ['label' => (int) $form['is_active'] === 1 ? 'Hide collection' : 'Make live', 'post' => '/admin/collections/' . (int) $collection['id'], 'fields' => ['toggle' => '1']]); ?>
  </div>
<?php else: ?>
  <p class="adm-muted">It holds no products, so it can be deleted for good along with its image and landing page.</p>
  <div class="adm-cluster">
    <?php partial_admin('button.php', ['label' => 'Delete collection', 'variant' => 'danger', 'post' => '/admin/collections/' . (int) $collection['id'] . '/delete', 'confirm' => 'Delete ' . $collection['name'] . "?\nThe landing page and its image are removed. This cannot be undone.", 'confirm_word' => 'DELETE', 'confirm_label' => 'Yes, delete']); ?>
  </div>
<?php endif; ?>
</div>
<?php endif; ?>
