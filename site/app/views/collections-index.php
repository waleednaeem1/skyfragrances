<?php
defined('SKYFR') || exit;
$collections = is_array($collections ?? null) ? $collections : [];
$intro = trim((string) ($intro ?? ''));
?>
<?php partial('page-hero.php', ['crumbs' => $breadcrumbs ?? [], 'eyebrow' => 'Curated', 'title' => (string) $heading, 'titleId' => 'collections-title', 'intro' => $intro]); ?>
<section class="section collections-list" aria-labelledby="collections-title">
  <div class="shell">
<?php foreach ($collections as $index => $collection): ?>
<?php $sources = $collection['image_sources']; $thumbs = $collection['thumbs']; $count = (int) $collection['product_count']; ?>
    <article class="edition<?= $index % 2 ? ' edition--flip' : '' ?>" aria-labelledby="collection-<?= e((string) $collection['id']) ?>" data-reveal>
      <a class="edition__media" href="<?= e($collection['url']) ?>" tabindex="-1" aria-hidden="true">
<?php if ($sources !== null): ?>
        <picture>
<?php if ($sources['srcset_webp'] !== ''): ?>
          <source type="image/webp" srcset="<?= e($sources['srcset_webp']) ?>" sizes="(min-width: 1024px) 640px, 100vw">
<?php endif; ?>
          <img src="<?= e($sources['src']) ?>"<?= $sources['srcset'] !== '' ? ' srcset="' . e($sources['srcset']) . '" sizes="(min-width: 1024px) 640px, 100vw"' : '' ?> alt="<?= e($collection['name'] . ' collection — ' . (string) setting('store_name', 'Sky Fragrances')) ?>" width="800" height="450" <?= $index === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
        </picture>
<?php endif; ?>
        <span class="collection-card__sweep" aria-hidden="true"></span>
      </a>
      <div class="edition__body">
        <span class="edition__index" aria-hidden="true"><?= e(sprintf('%02d', $index + 1)) ?></span>
<?php if (trim((string) ($collection['tagline'] ?? '')) !== ''): ?>
        <p class="edition__tagline"><?= e((string) $collection['tagline']) ?></p>
<?php endif; ?>
        <h2 class="edition__name" id="collection-<?= e((string) $collection['id']) ?>"><a href="<?= e($collection['url']) ?>"><?= e((string) $collection['name']) ?></a></h2>
<?php if (trim((string) ($collection['description'] ?? '')) !== ''): ?>
        <p class="edition__text"><?= e((string) $collection['description']) ?></p>
<?php endif; ?>
        <p class="edition__meta"><span><?= e($count === 1 ? '1 fragrance' : $count . ' fragrances') ?></span><?php if (trim((string) ($collection['mood'] ?? '')) !== ''): ?><span>Mood · <?= e((string) $collection['mood']) ?></span><?php endif; ?></p>
<?php if ($thumbs !== []): ?>
        <ul class="edition__thumbs" aria-label="From <?= e((string) $collection['name']) ?>">
<?php foreach ($thumbs as $thumb): ?>
          <li>
            <a class="thumb" href="<?= e($thumb['url']) ?>">
              <span class="thumb__media">
<?php if ($thumb['image_set'] !== null): ?>
                <img src="<?= e(product_card_image_src($thumb['image_set'], 'thumb', 'jpg')) ?>" alt="<?= e($thumb['alt']) ?>" width="120" height="150" loading="lazy" decoding="async">
<?php endif; ?>
              </span>
              <span class="thumb__name"><?= e($thumb['name']) ?></span>
              <span class="thumb__price"><?= e($thumb['price_display']) ?></span>
            </a>
          </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
        <a class="btn btn--ghost" href="<?= e($collection['url']) ?>"><span class="btn__label">Explore <?= e((string) $collection['name']) ?><?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16]); ?></span></a>
      </div>
    </article>
<?php endforeach; ?>
  </div>
</section>
