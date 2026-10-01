<?php
defined('SKYFR') || exit;
$images = is_array($images ?? null) ? $images : [];
$badges = is_array($badges ?? null) ? $badges : [];
$productName = (string) ($productName ?? 'Product');
$sizesAttr = '(min-width: 1024px) 58vw, 100vw';
$monogram = asset(brand_asset('img/brand/monogram-transparent-512.png'));
?>
<?php if ($images === []): ?>
<div class="gallery gallery--single js-gallery">
  <div class="gallery__main" data-gallery-main>
<?php if ($badges !== []): ?>
    <div class="gallery__badges"><div class="badge-stack"><?php foreach ($badges as $badge): ?><span class="badge <?= e($badge['class']) ?>"><?= e($badge['text']) ?></span><?php endforeach; ?></div></div>
<?php endif; ?>
    <div class="placeholder" role="img" aria-label="<?= e($productName) ?> — image coming soon">
      <img class="placeholder__mark" src="<?= e($monogram) ?>" alt="" width="512" height="512" loading="eager" decoding="async">
    </div>
  </div>
</div>
<?php return; endif; ?>
<div class="gallery js-gallery<?= count($images) === 1 ? ' gallery--single' : '' ?>">
  <div class="gallery__thumbs">
<?php foreach ($images as $image): ?>
    <button class="gallery__thumb<?= $image['index'] === 0 ? ' is-active' : '' ?>" type="button" data-gallery-thumb data-src="<?= e($image['src']) ?>" data-webp="<?= e($image['srcset_webp']) ?>" data-srcset="<?= e($image['srcset']) ?>" data-alt="<?= e($image['alt']) ?>" aria-label="Image <?= e((string) ($image['index'] + 1)) ?>" aria-current="<?= $image['index'] === 0 ? 'true' : 'false' ?>">
      <img class="gallery__thumb-img" src="<?= e($image['thumb']) ?>" alt="" width="200" height="250" loading="lazy" decoding="async">
    </button>
<?php endforeach; ?>
  </div>
  <div class="gallery__main gallery--desktop" data-gallery-main data-index="0">
<?php if ($badges !== []): ?>
    <div class="gallery__badges"><div class="badge-stack"><?php foreach ($badges as $badge): ?><span class="badge <?= e($badge['class']) ?>"><?= e($badge['text']) ?></span><?php endforeach; ?></div></div>
<?php endif; ?>
    <picture>
      <source type="image/webp" srcset="<?= e($images[0]['srcset_webp']) ?>" sizes="<?= e($sizesAttr) ?>">
      <img class="gallery__img" src="<?= e($images[0]['src']) ?>" srcset="<?= e($images[0]['srcset']) ?>" sizes="<?= e($sizesAttr) ?>" alt="<?= e($images[0]['alt']) ?>" width="1400" height="1750" loading="eager" fetchpriority="high" decoding="async">
    </picture>
    <button class="gallery__open" type="button" data-lightbox-open="0" aria-label="Open image full screen"></button>
    <span class="gallery__zoom-hint" aria-hidden="true">Hover to zoom</span>
  </div>
  <div class="gallery__rail" data-gallery-rail aria-label="Product images">
<?php foreach ($images as $image): ?>
    <div class="gallery__slide">
      <picture>
        <source type="image/webp" srcset="<?= e($image['srcset_webp']) ?>" sizes="<?= e($sizesAttr) ?>">
        <img class="gallery__slide-img" src="<?= e($image['src']) ?>" srcset="<?= e($image['srcset']) ?>" sizes="<?= e($sizesAttr) ?>" alt="<?= e($image['alt']) ?>" width="600" height="750"<?= $image['index'] === 0 ? ' loading="eager" fetchpriority="high"' : ' loading="lazy"' ?> decoding="async">
      </picture>
      <button class="gallery__open" type="button" data-lightbox-open="<?= e((string) $image['index']) ?>" aria-label="Open image <?= e((string) ($image['index'] + 1)) ?> full screen"></button>
    </div>
<?php endforeach; ?>
  </div>
<?php if (count($images) > 1): ?>
  <div class="gallery__dots">
<?php foreach ($images as $image): ?>
    <button class="gallery__dot<?= $image['index'] === 0 ? ' is-active' : '' ?>" type="button" data-gallery-dot aria-label="Image <?= e((string) ($image['index'] + 1)) ?>" aria-current="<?= $image['index'] === 0 ? 'true' : 'false' ?>"></button>
<?php endforeach; ?>
  </div>
<?php endif; ?>
</div>
