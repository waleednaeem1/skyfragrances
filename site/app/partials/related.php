<?php
defined('SKYFR') || exit;
$related = is_array($related ?? null) ? $related : [];
if ($related === []) {
    return;
}
$sectionId = (string) ($sectionId ?? 'related');
$monogram = asset('img/brand/monogram-transparent-512.png');
?>
<section class="section section--divided" id="<?= e($sectionId) ?>" aria-labelledby="<?= e($sectionId) ?>-title">
  <div class="container">
    <header class="section-header sf-reveal">
      <p class="section-header__eyebrow">Keep exploring</p>
      <h2 class="section-header__title h2" id="<?= e($sectionId) ?>-title">You May Also Like</h2>
      <a class="section-header__link" href="<?= e(url('/shop')) ?>">View all <?php partial('icon.php', ['name' => 'arrow-right', 'size' => 18]); ?></a>
    </header>
    <div class="rail rail--cards sf-reveal sf-reveal--stagger">
<?php foreach ($related as $card): ?>
      <article class="product-card<?= $card['sold_out'] ? ' is-sold-out' : '' ?>">
<?php if ($card['image'] !== null): ?>
        <a class="product-card__media" href="<?= e($card['url']) ?>" tabindex="-1" aria-hidden="true">
          <img class="product-card__img" src="<?= e($card['image']['src']) ?>" srcset="<?= e($card['image']['srcset']) ?>" sizes="(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 62vw" alt="<?= e($card['image']['alt']) ?>" width="600" height="750" loading="lazy" decoding="async">
<?php if ($card['hover'] !== null): ?>
          <img class="product-card__img product-card__img--alt" src="<?= e($card['hover']['src']) ?>" srcset="<?= e($card['hover']['srcset']) ?>" sizes="(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 62vw" alt="" width="600" height="750" loading="lazy" decoding="async">
<?php endif; ?>
<?php if ($card['badges'] !== []): ?>
          <div class="badge-stack"><?php foreach ($card['badges'] as $badge): ?><span class="badge <?= e($badge['class']) ?>"><?= e($badge['text']) ?></span><?php endforeach; ?></div>
<?php endif; ?>
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
          <h3 class="product-card__name"><a class="product-card__link" href="<?= e($card['url']) ?>"><?= e($card['name']) ?></a></h3>
          <p class="product-card__meta">
<?php if ($card['family'] !== ''): ?>
            <span class="product-card__family"><?= e($card['family']) ?></span>
<?php endif; ?>
<?php if ($card['size_labels'] !== ''): ?>
            <span><?= e($card['size_labels']) ?></span>
<?php endif; ?>
          </p>
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
<?php endforeach; ?>
    </div>
  </div>
</section>
