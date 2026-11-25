<?php
defined('SKYFR') || exit;
$related = is_array($related ?? null) ? $related : [];
if ($related === []) {
    return;
}
$sectionId = (string) ($sectionId ?? 'related');
$monogram = asset(brand_asset('img/brand/monogram-transparent-512.png'));
$cardSizes = '(min-width: 1200px) 22vw, (min-width: 768px) 30vw, 62vw';
$cardSrcset = static function (array $image, string $ext): string {
    $thumb = (string) ($ext === 'webp' ? ($image['thumb_webp'] ?? '') : ($image['thumb'] ?? ''));
    $card = (string) ($ext === 'webp' ? ($image['webp'] ?? '') : ($image['src'] ?? ''));
    $parts = [];
    if ($thumb !== '' && !str_ends_with($thumb, '.svg')) {
        $parts[] = $thumb . ' 200w';
    }
    if ($card !== '' && !str_ends_with($card, '.svg')) {
        $parts[] = $card . ' 600w';
    }
    return implode(', ', $parts);
};
?>
<section class="section section--ruled" id="<?= e($sectionId) ?>" aria-labelledby="<?= e($sectionId) ?>-title">
  <div class="shell">
    <?php partial('section-header.php', ['eyebrow' => 'Keep exploring', 'title' => 'You may also like', 'em' => 2, 'titleId' => $sectionId . '-title', 'linkHref' => url('/shop'), 'linkLabel' => 'View all', 'rail' => 'rail-' . $sectionId]); ?>
    <div class="rail" id="rail-<?= e($sectionId) ?>" data-rail tabindex="0" aria-label="Related fragrances, scroll sideways">
<?php foreach ($related as $card): ?>
      <div class="rail__item">
      <article class="product-card tone--<?= e(scent_tone($card['family'])) ?><?= $card['sold_out'] ? ' is-sold-out' : '' ?>">
<?php if ($card['image'] !== null): ?>
        <a class="product-card__media" href="<?= e($card['url']) ?>" tabindex="-1" aria-hidden="true">
          <span class="product-card__glow"></span>
<?php $webpSrcset = $cardSrcset($card['image'], 'webp'); $jpgSrcset = $cardSrcset($card['image'], 'jpg'); ?>
          <picture>
<?php if ($webpSrcset !== ''): ?>
            <source type="image/webp" srcset="<?= e($webpSrcset) ?>" sizes="<?= e($cardSizes) ?>">
<?php endif; ?>
            <img class="product-card__img" src="<?= e($card['image']['src']) ?>"<?= $jpgSrcset !== '' ? ' srcset="' . e($jpgSrcset) . '" sizes="' . e($cardSizes) . '"' : '' ?> alt="<?= e($card['image']['alt']) ?>" width="600" height="750" loading="lazy" decoding="async">
          </picture>
<?php if ($card['hover'] !== null): ?>
          <img class="product-card__img product-card__img--alt" src="<?= e($card['hover']['src']) ?>" alt="" width="600" height="750" loading="lazy" decoding="async">
<?php endif; ?>
<?php if ($card['badges'] !== []): ?>
          <div class="badge-stack"><?php foreach ($card['badges'] as $badge): ?><span class="badge <?= e($badge['class']) ?>"><?= e($badge['text']) ?></span><?php endforeach; ?></div>
<?php endif; ?>
          <span class="product-card__sweep"></span>
        </a>
<?php else: ?>
        <div class="placeholder product-card__media" role="img" aria-label="<?= e($card['name']) ?> — image coming soon">
          <img class="placeholder__mark" src="<?= e($monogram) ?>" alt="" width="512" height="512" loading="lazy" decoding="async">
<?php if ($card['badges'] !== []): ?>
          <div class="badge-stack"><?php foreach ($card['badges'] as $badge): ?><span class="badge <?= e($badge['class']) ?>"><?= e($badge['text']) ?></span><?php endforeach; ?></div>
<?php endif; ?>
        </div>
<?php endif; ?>
        <div class="product-card__body">
          <p class="product-card__meta"><?= e(implode(' · ', array_filter([$card['family'], $card['size_labels']]))) ?></p>
          <h3 class="product-card__name"><a class="product-card__link" href="<?= e($card['url']) ?>"><?= e($card['name']) ?></a></h3>
<?php if ($card['rating_count'] >= 1): ?>
          <p class="product-card__rating"><?php partial('stars.php', ['rating' => $card['rating_avg'], 'count' => $card['rating_count']]); ?></p>
<?php endif; ?>
          <p class="product-card__price price<?= $card['on_sale'] ? ' price--sale' : '' ?>">
<?php if ($card['has_from']): ?>
            <span class="price__from">From</span>
<?php endif; ?>
            <span class="price__now"><?= e($card['price_from']) ?></span>
          </p>
        </div>
        <div class="product-card__action">
          <a class="btn btn--ghost btn--block btn--card" href="<?= e($card['url']) ?>"><span class="btn__label"><?= $card['sold_out'] ? 'View details' : 'Choose size' ?></span></a>
        </div>
      </article>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>
