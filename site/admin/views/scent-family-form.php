<?php
defined('SKYFR') || exit;
$errors = $errors ?? [];
$form = $form ?? [];
$isNew = !empty($isNew);
$err = static fn (string $key): string => (string) ($errors[$key] ?? '');
$labels = ['name' => 'Name', 'slug' => 'Link name', 'intro' => 'Intro', 'sort_order' => 'Sort order', 'seo_title' => 'SEO title', 'seo_description' => 'SEO description'];
?>
<?php partial_admin('page-header.php', [
    'title' => $isNew ? 'New scent family' : (string) $family['name'],
    'subtitle' => $isNew ? 'Products pick from this list; the name is what shoppers see.' : $productCount . ' product' . ($productCount === 1 ? '' : 's') . ' in this family.',
    'back' => '/admin/scent-families',
    'back_label' => 'Scent families',
    'actions' => $isNew ? [] : [['label' => 'View on site', 'href' => url('/scent/' . $family['slug']), 'external' => true], ['label' => 'Products in it', 'href' => '/admin/products?scent_family=' . rawurlencode((string) $family['name']) . '&status=all']],
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
<form method="post" action="<?= e(url($isNew ? '/admin/scent-families/new' : '/admin/scent-families/' . (int) $family['id'])) ?>" class="adm-form" data-guard novalidate>
  <?= csrf_field() ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Basics</h2>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'value' => $form['name'], 'required' => true, 'maxlength' => 60, 'help' => $isNew ? 'Shown on product pages and filters.' : 'Renaming updates every product in this family at once.', 'error' => $err('name')]); ?>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'slug', 'label' => 'Link name', 'value' => $form['slug'], 'maxlength' => 160, 'help' => 'Web address: ' . SITE_URL . '/scent/…' . ($isNew ? '' : ' Changing it keeps the old link working with a redirect.'), 'error' => $err('slug'), 'attr' => ['data-slug-from' => 'name', 'autocapitalize' => 'none', 'spellcheck' => 'false', 'class' => 'adm-mono']]); ?>
<?php if (!empty($slugHistory)): ?>
    <p class="adm-note">Old links that redirect here: <?php foreach ($slugHistory as $i => $old): ?><?= $i > 0 ? ', ' : '' ?><span class="adm-mono"><?= e($old['old_slug']) ?></span> (<?= e((string) (int) $old['hit_count']) ?> visits)<?php endforeach; ?></p>
<?php endif; ?>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'intro', 'label' => 'Intro', 'value' => $form['intro'], 'rows' => 4, 'help' => 'Two or three sentences at the top of the /scent page. Plain text.', 'error' => $err('intro')]); ?>
  </div>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Visibility</h2>
    <?php partial_admin('field.php', ['type' => 'segment', 'name' => 'is_active', 'label' => 'Status', 'value' => (string) (int) $form['is_active'], 'options' => ['1' => 'Live', '0' => 'Hidden'], 'help' => 'Hidden families keep their name on products but their /scent page stops working.']); ?>
    <?php partial_admin('field.php', ['type' => 'int', 'name' => 'sort_order', 'label' => 'Sort order', 'value' => (string) $form['sort_order'], 'placeholder' => 'Next available', 'help' => 'Lower numbers show first in filters.', 'error' => $err('sort_order')]); ?>
  </div>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Search engines</h2>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'seo_title', 'label' => 'SEO title', 'value' => $form['seo_title'], 'maxlength' => 60, 'attr' => ['data-counter-warn' => '55'], 'help' => 'Blank uses "Name Perfumes in Pakistan | Sky Fragrances".', 'error' => $err('seo_title')]); ?>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'seo_description', 'label' => 'SEO description', 'value' => $form['seo_description'], 'rows' => 3, 'maxlength' => 155, 'attr' => ['data-counter-warn' => '140'], 'help' => 'Blank uses the first sentence of the intro.', 'error' => $err('seo_description')]); ?>
  </div>
  <?php partial_admin('action-bar.php', ['buttons' => [['label' => $isNew ? 'Create scent family' : 'Save']]]); ?>
</form>
