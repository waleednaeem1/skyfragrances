<?php
defined('SKYFR') || exit;
$hero = is_array($hero ?? null) ? $hero : null;
$alsoTrying = is_array($alsoTrying ?? null) ? $alsoTrying : [];
$fillers = is_array($fillers ?? null) ? $fillers : [];
$shareUrl = (string) ($shareUrl ?? '');
$retakeUrl = (string) ($retakeUrl ?? url('/scent-finder'));
$whatsappUrl = (string) ($whatsappUrl ?? '');
?>
<div class="container container--narrow">
  <?php partial('breadcrumb.php', ['items' => [['label' => 'Home', 'url' => '/'], ['label' => 'Scent Finder', 'url' => '/scent-finder'], ['label' => 'Your match', 'url' => '']]]); ?>
  <header class="page-head page-head--center">
    <p class="u-track text-muted">Scent Finder</p>
    <h1 class="page-head__title h1 text-gold-grad" id="result-title"><?= e((string) $heading) ?></h1>
    <p class="page-head__intro lead measure"><?= e((string) ($readout ?? '')) ?></p>
  </header>
</div>
<?php if ($hero !== null): ?>
<?php $product = $hero['product']; ?>
<section class="section section--flush-top" aria-labelledby="match-title">
  <div class="container">
    <article class="split split--pdp">
<?php if ($hero['image_set'] !== null): ?>
      <a class="product-card__media" href="<?= e($product['url']) ?>" tabindex="-1" aria-hidden="true">
        <picture>
<?php if (product_card_image_srcset($hero['image_set'], 'webp') !== ''): ?>
          <source type="image/webp" srcset="<?= e(product_card_image_srcset($hero['image_set'], 'webp')) ?>" sizes="(min-width: 768px) 50vw, 100vw">
<?php endif; ?>
          <img class="product-card__img" src="<?= e(product_card_image_src($hero['image_set'], 'card', 'jpg')) ?>"<?= product_card_image_srcset($hero['image_set'], 'jpg') !== '' ? ' srcset="' . e(product_card_image_srcset($hero['image_set'], 'jpg')) . '" sizes="(min-width: 768px) 50vw, 100vw"' : '' ?> alt="<?= e($hero['alt']) ?>" width="600" height="750" loading="eager" fetchpriority="high" decoding="async">
        </picture>
      </a>
<?php else: ?>
      <?php partial('placeholder.php', ['label' => (string) $product['name'], 'class' => 'product-card__media']); ?>
<?php endif; ?>
      <div class="split__aside stack">
        <p class="u-track text-muted">Your top match</p>
        <h2 class="h2" id="match-title"><a class="product-card__link" href="<?= e($product['url']) ?>"><?= e((string) $product['name']) ?></a></h2>
        <p class="text-muted">
<?php if (trim((string) ($product['collection_name'] ?? '')) !== ''): ?>
          <a href="<?= e(url('/collections/' . $product['collection_slug'])) ?>"><?= e((string) $product['collection_name']) ?></a> ·
<?php endif; ?>
<?php if (trim((string) ($product['scent_family'] ?? '')) !== ''): ?>
          <span><?= e((string) $product['scent_family']) ?></span>
<?php endif; ?>
<?php if ($hero['size_labels'] !== []): ?>
          · <span><?= e(implode(' · ', $hero['size_labels'])) ?></span>
<?php endif; ?>
        </p>
<?php if (trim((string) ($product['short_description'] ?? '')) !== ''): ?>
        <p class="lead"><?= e((string) $product['short_description']) ?></p>
<?php endif; ?>
<?php $pyramidLine = array_filter([implode(', ', $hero['notes']['top']), implode(', ', $hero['notes']['heart']), implode(', ', $hero['notes']['base'])]); ?>
<?php if ($pyramidLine !== []): ?>
        <p class="text-small"><span class="u-track text-muted">Notes</span> <?= e(implode(' → ', $pyramidLine)) ?></p>
<?php endif; ?>
        <?php partial('meters.php', ['meters' => $hero['meters']]); ?>
<?php if ($hero['price_display'] !== ''): ?>
        <p class="price price--lg<?= $hero['is_sale'] ? ' price--sale' : '' ?>">
<?php if ($hero['from']): ?>
          <span class="price__from">From</span>
<?php endif; ?>
          <span class="price__now"><?= e($hero['price_display']) ?></span>
<?php if ($hero['is_sale']): ?>
          <s class="price__was"><?= e($hero['was_display']) ?></s>
<?php endif; ?>
          <span class="price__tax">Inclusive of all taxes.</span>
        </p>
<?php endif; ?>
        <div class="btn-row">
<?php if ($hero['sold_out']): ?>
          <button class="btn btn--ghost btn--block" type="button" disabled><span class="btn__label">Sold Out</span></button>
<?php elseif ($hero['single_size_id'] > 0): ?>
          <form class="js-add-to-cart-form" action="<?= e(url('/api/cart/add')) ?>" method="post">
            <?= session_status() === PHP_SESSION_ACTIVE ? csrf_field() : '' ?>
            <input type="hidden" name="size_id" value="<?= e((string) $hero['single_size_id']) ?>">
            <input type="hidden" name="qty" value="1">
            <button class="btn btn--primary btn--block js-add-to-cart" type="submit"><span class="btn__label">Add to Cart</span></button>
          </form>
<?php else: ?>
          <a class="btn btn--primary btn--block" href="<?= e($product['url'] . '#sizes') ?>"><span class="btn__label">Choose a Size</span></a>
<?php endif; ?>
          <a class="btn btn--ghost btn--block" href="<?= e($product['url']) ?>"><span class="btn__label">View Details</span></a>
        </div>
      </div>
    </article>
  </div>
</section>
<?php endif; ?>
<?php if ($alsoTrying !== []): ?>
<section class="section section--divided" aria-labelledby="also-title">
  <div class="container">
    <?php partial('section-header.php', ['eyebrow' => 'Close seconds', 'title' => 'Also worth trying', 'titleId' => 'also-title']); ?>
    <div class="grid grid--products sf-reveal sf-reveal--stagger">
<?php foreach ($alsoTrying as $product): ?>
      <?php partial('product-card.php', ['product' => $product]); ?>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($fillers !== []): ?>
<section class="section section--divided" aria-labelledby="popular-title">
  <div class="container">
    <?php partial('section-header.php', ['eyebrow' => $hero === null ? 'Nothing scored yet' : 'To round it out', 'title' => 'Popular right now', 'titleId' => 'popular-title', 'sub' => $hero === null ? 'Your answers did not match anything in stock just now, so here is what everyone else is wearing.' : '', 'linkHref' => url('/best-sellers'), 'linkLabel' => 'View all']); ?>
    <div class="grid grid--products sf-reveal sf-reveal--stagger">
<?php foreach ($fillers as $product): ?>
      <?php partial('product-card.php', ['product' => $product]); ?>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($hero === null && $fillers === []): ?>
<section class="section" aria-labelledby="result-title">
  <div class="container">
    <div class="empty-state">
      <?php partial('icon.php', ['name' => 'compass', 'size' => 40, 'class' => 'empty-state__icon']); ?>
      <p class="empty-state__title">No fragrances match that combination.</p>
      <p class="empty-state__text">The shelves are being restocked. Message us and we will point you to the right bottle.</p>
      <div class="empty-state__action btn-row">
        <a class="btn btn--primary" href="<?= e(url('/shop')) ?>"><span class="btn__label">Browse all fragrances</span></a>
<?php if ($whatsappUrl !== ''): ?>
        <a class="btn btn--ghost btn--whatsapp" href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener"><?php partial('icon.php', ['name' => 'whatsapp', 'size' => 18, 'class' => 'btn__icon']); ?><span class="btn__label">Ask on WhatsApp</span></a>
<?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
<section class="section section--tight" aria-label="Share or retake">
  <div class="container container--narrow">
    <div class="btn-row cluster cluster--center">
      <button class="btn btn--ghost js-copy" type="button" data-copy="<?= e($shareUrl) ?>"><span class="btn__label">Share your match</span></button>
      <a class="btn btn--text" href="<?= e($retakeUrl) ?>"><span class="btn__label">Retake the quiz</span></a>
    </div>
    <p class="text-small text-muted center"><a href="<?= e($shareUrl) ?>"><?= e($shareUrl) ?></a></p>
  </div>
</section>
<section class="section section--divided" aria-label="Newsletter">
  <div class="container container--narrow">
    <?php partial('newsletter.php', ['variant' => 'band', 'source' => 'home']); ?>
  </div>
</section>
