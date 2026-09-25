<?php
defined('SKYFR') || exit;
$collections = is_array($collections ?? null) ? $collections : [];
$intro = trim((string) ($intro ?? ''));
?>
<div class="container">
  <?php partial('breadcrumb.php', ['items' => $breadcrumbs ?? []]); ?>
  <header class="page-head page-head--center">
    <p class="u-track text-muted">Curated</p>
    <h1 class="page-head__title h1" id="collections-title"><?= e((string) $heading) ?></h1>
<?php if ($intro !== ''): ?>
    <p class="page-head__intro lead measure"><?= e($intro) ?></p>
<?php endif; ?>
  </header>
</div>
<section class="section section--flush-top" aria-labelledby="collections-title">
  <div class="container">
    <div class="grid grid--2 sf-reveal sf-reveal--stagger">
<?php foreach ($collections as $index => $collection): ?>
<?php $sources = $collection['image_sources']; $thumbs = $collection['thumbs']; $count = (int) $collection['product_count']; ?>
      <article class="stack" aria-labelledby="collection-<?= e((string) $collection['id']) ?>">
        <a class="collection-card collection-card--wide<?= $sources === null ? ' collection-card--empty' : '' ?>" href="<?= e($collection['url']) ?>" aria-labelledby="collection-<?= e((string) $collection['id']) ?>">
          <span class="collection-card__media">
<?php if ($sources !== null): ?>
            <picture>
<?php if ($sources['srcset_webp'] !== ''): ?>
              <source type="image/webp" srcset="<?= e($sources['srcset_webp']) ?>" sizes="(min-width: 768px) 50vw, 100vw">
<?php endif; ?>
              <img class="collection-card__img" src="<?= e($sources['src']) ?>"<?= $sources['srcset'] !== '' ? ' srcset="' . e($sources['srcset']) . '" sizes="(min-width: 768px) 50vw, 100vw"' : '' ?> alt="<?= e($collection['name'] . ' collection — ' . (string) setting('store_name', 'Sky Fragrances')) ?>" width="800" height="450" <?= $index === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
            </picture>
<?php endif; ?>
          </span>
          <span class="collection-card__scrim" aria-hidden="true"></span>
          <span class="collection-card__body">
<?php if (trim((string) ($collection['tagline'] ?? '')) !== ''): ?>
            <span class="collection-card__eyebrow u-track"><?= e((string) $collection['tagline']) ?></span>
<?php endif; ?>
            <span class="collection-card__name h2" id="collection-<?= e((string) $collection['id']) ?>"><?= e((string) $collection['name']) ?></span>
            <span class="collection-card__count"><?= e($count === 1 ? '1 fragrance' : $count . ' fragrances') ?></span>
            <span class="collection-card__rule" aria-hidden="true"></span>
          </span>
        </a>
<?php if (trim((string) ($collection['description'] ?? '')) !== ''): ?>
        <p class="text-muted"><?= e((string) $collection['description']) ?></p>
<?php endif; ?>
<?php if (trim((string) ($collection['mood'] ?? '')) !== ''): ?>
        <p class="lead"><?= e((string) $collection['mood']) ?></p>
<?php endif; ?>
<?php if ($thumbs !== []): ?>
        <ul class="grid grid--thumbs" aria-label="From <?= e((string) $collection['name']) ?>">
<?php foreach ($thumbs as $thumb): ?>
          <li>
            <a class="product-card product-card--compact" href="<?= e($thumb['url']) ?>">
<?php if ($thumb['image_set'] !== null): ?>
              <span class="product-card__media"><img class="product-card__img" src="<?= e(product_card_image_src($thumb['image_set'], 'thumb', 'jpg')) ?>"<?= product_card_image_srcset($thumb['image_set'], 'jpg') !== '' ? ' srcset="' . e(product_card_image_srcset($thumb['image_set'], 'jpg')) . '" sizes="(min-width: 768px) 16vw, 30vw"' : '' ?> alt="<?= e($thumb['alt']) ?>" width="600" height="750" loading="lazy" decoding="async"></span>
<?php else: ?>
              <?php partial('placeholder.php', ['label' => $thumb['name'], 'class' => 'product-card__media']); ?>
<?php endif; ?>
              <span class="product-card__body"><span class="product-card__name text-small"><?= e($thumb['name']) ?></span> <span class="price text-small"><span class="price__now"><?= e($thumb['price_display']) ?></span></span></span>
            </a>
          </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
        <p><a class="btn btn--text" href="<?= e($collection['url']) ?>"><span class="btn__label">Explore <?= e((string) $collection['name']) ?></span> <?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16, 'class' => 'btn__icon']); ?></a></p>
      </article>
<?php endforeach; ?>
    </div>
  </div>
</section>
