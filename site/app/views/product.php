<?php
defined('SKYFR') || exit;
$sizes = is_array($sizes ?? null) ? $sizes : [];
$images = is_array($images ?? null) ? $images : [];
$related = is_array($related ?? null) ? $related : [];
$collection = is_array($collection ?? null) ? $collection : [];
$whatsapp = is_array($whatsapp ?? null) ? $whatsapp : ['href' => ''];
$hasWhatsapp = ($whatsapp['href'] ?? '') !== '';
$selected = $defaultSize ?? null;
$stockLine = ['class' => '', 'text' => 'In stock'];
if ($selected !== null && $selected['stock'] <= 0) {
    $stockLine = ['class' => ' stock-line--out', 'text' => 'Out of stock'];
} elseif ($selected !== null && $selected['stock'] < 10) {
    $stockLine = ['class' => ' stock-line--low', 'text' => 'Only ' . $selected['stock'] . ' left'];
}
$selectedOut = $selected === null || !$selected['in_stock'];
$hasComposition = $notes['top'] !== [] || $notes['heart'] !== [] || $notes['base'] !== [] || $meters !== [] || $seasons !== [] || $occasions !== [];
$attrLines = static fn (string $text): string => str_replace("\n", '&#10;', e($text));
$seasonIcons = [
    'summer' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
    'winter' => '<path d="M12 2v20M2 12h20M5 5l14 14M19 5L5 19"/>',
    'autumn' => '<path d="M4 20c6-1 12-6 16-16-8 1-14 6-16 16z"/><path d="M4 20l8-8"/>',
    'spring' => '<circle cx="12" cy="12" r="2.5"/><path d="M12 3a3 3 0 0 1 0 6 3 3 0 0 1 0-6zM12 15a3 3 0 0 1 0 6 3 3 0 0 1 0-6zM3 12a3 3 0 0 1 6 0 3 3 0 0 1-6 0zM15 12a3 3 0 0 1 6 0 3 3 0 0 1-6 0z"/>',
    'monsoon' => '<path d="M7 15a4 4 0 0 1 .5-8 5.5 5.5 0 0 1 10.5 1.5 3.3 3.3 0 0 1-.5 6.5H7z"/><path d="M8 18l-1 3M12 18l-1 3M16 18l-1 3"/>',
];
$trustIcons = [
    'cod' => '<rect x="2.5" y="6" width="19" height="12" rx="1"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9.5h.01M18 14.5h.01"/>',
    'truck' => '<path d="M2.5 7h11v9h-11zM13.5 10h4l3 3v3h-7z"/><circle cx="6.5" cy="17.5" r="1.5"/><circle cx="17" cy="17.5" r="1.5"/>',
    'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
];
$stickyThumb = $images !== [] ? $images[0]['thumb'] : asset('img/brand/monogram-transparent-512.png');
?>
<div class="container">
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <ol class="breadcrumb__list">
      <li class="breadcrumb__item"><a class="breadcrumb__link" href="<?= e(url('/')) ?>">Home</a></li>
      <li class="breadcrumb__item"><a class="breadcrumb__link" href="<?= e(url('/shop')) ?>">Shop</a></li>
<?php if ($collection !== []): ?>
      <li class="breadcrumb__item"><a class="breadcrumb__link" href="<?= e($collection['url']) ?>"><?= e($collection['name']) ?></a></li>
<?php endif; ?>
      <li class="breadcrumb__item"><span class="breadcrumb__current" aria-current="page"><?= e($product['name']) ?></span></li>
    </ol>
  </nav>
  <div class="split split--pdp">
    <div class="split__media">
      <?php partial('gallery.php', ['images' => $images, 'badges' => $badges, 'productName' => $product['name']]); ?>
    </div>
    <aside class="split__aside">
      <div class="stack">
<?php if ($collection !== []): ?>
        <p class="u-track"><a href="<?= e($collection['url']) ?>"><?= e($collection['name']) ?></a></p>
<?php endif; ?>
        <h1 class="h1"><?= e($product['name']) ?></h1>
        <p class="text-muted text-small">
          <span><?= e($genderLabel) ?></span>
<?php if (!empty($product['scent_family'])): ?>
          <span aria-hidden="true"> · </span>
<?php if ($familyUrl !== ''): ?>
          <a href="<?= e($familyUrl) ?>"><?= e($product['scent_family']) ?></a>
<?php else: ?>
          <span><?= e($product['scent_family']) ?></span>
<?php endif; ?>
<?php endif; ?>
        </p>
<?php if ($rating['count'] >= 1): ?>
        <p><?php partial('stars.php', ['rating' => $rating['average'], 'count' => $rating['count'], 'size' => 'md', 'href' => '#reviews', 'showAvg' => true]); ?></p>
<?php endif; ?>
<?php if ($selected !== null): ?>
        <p class="price price--lg js-price-block<?= $selected['on_sale'] ? ' price--sale' : '' ?>">
          <span class="price__now" data-price-now><?= e($selected['effective_display']) ?></span>
          <s class="price__was" data-price-was<?= $selected['on_sale'] ? '' : ' hidden' ?>><?= $selected['on_sale'] ? e($selected['price_display']) : '' ?></s>
          <span class="badge badge--save" data-save-pill<?= $selected['save_percent'] > 0 ? '' : ' hidden' ?>><?= $selected['save_percent'] > 0 ? 'Save ' . e((string) $selected['save_percent']) . '%' : '' ?></span>
          <span class="price__tax">Inclusive of all taxes.</span>
        </p>
<?php endif; ?>
<?php if (!empty($product['short_description'])): ?>
        <p class="lead"><?= e($product['short_description']) ?></p>
<?php endif; ?>
<?php if ($sizes !== []): ?>
        <form class="js-add-to-cart-form stack" id="add-to-cart-form" action="<?= e(url('/api/cart/add')) ?>" method="post">
          <?= csrf_field() ?>
          <fieldset class="size-picker" role="radiogroup">
            <legend class="size-picker__legend"><span>Size</span><span class="size-picker__legend-value" data-sticky-size><?= e($selected['label']) ?></span></legend>
            <div class="size-picker__options">
<?php foreach ($sizes as $size): ?>
              <label class="size-chip">
                <input class="size-chip__input" type="radio" name="size_id" value="<?= e((string) $size['id']) ?>" data-size-input<?= $size['id'] === $selected['id'] ? ' checked' : '' ?> required>
                <span class="size-chip__card">
                  <span class="size-chip__label"><?= e($size['label']) ?></span>
                  <span class="size-chip__price">
<?php if ($size['on_sale']): ?>
                    <span class="size-chip__sale"><?= e($size['effective_display']) ?></span><s class="size-chip__was"><?= e($size['price_display']) ?></s>
<?php else: ?>
                    <?= e($size['price_display']) ?>
<?php endif; ?>
<?php if (!$size['in_stock']): ?>
                    <span class="size-chip__stock">Out of stock</span>
<?php endif; ?>
                  </span>
                </span>
              </label>
<?php endforeach; ?>
            </div>
            <p class="size-picker__error">Choose a size.</p>
          </fieldset>
          <p class="stock-line<?= e($stockLine['class']) ?>" data-stock-line><?= e($stockLine['text']) ?></p>
          <p class="text-micro text-muted">SKU <span data-sku><?= e($selected['sku']) ?></span></p>
<?php if (!$allSoldOut): ?>
          <div class="cluster">
            <div class="qty js-qty" data-max="<?= e((string) $selected['max_qty']) ?>" data-product-qty>
              <button class="qty__btn" type="button" data-qty-dec aria-label="Decrease quantity"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M5 12h14"/></svg></button>
              <input class="qty__input" type="text" inputmode="numeric" pattern="[0-9]*" name="qty" value="1" aria-label="Quantity">
              <button class="qty__btn" type="button" data-qty-inc aria-label="Increase quantity"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M12 5v14M5 12h14"/></svg></button>
            </div>
            <span class="cart-line__max text-micro text-muted" data-qty-max hidden>Max <?= e((string) $selected['max_qty']) ?></span>
          </div>
<?php else: ?>
          <input type="hidden" name="qty" value="1">
<?php endif; ?>
          <button class="btn btn--primary btn--block js-add-to-cart js-sticky-anchor" type="submit" data-label-add="Add to Cart" data-label-sold-out="Sold Out"<?= $selectedOut ? ' disabled aria-disabled="true"' : '' ?>><span class="btn__label"><?= $selectedOut ? 'Sold Out' : 'Add to Cart' ?></span></button>
<?php if ($hasWhatsapp): ?>
          <a class="btn <?= $selectedOut ? 'btn--primary' : 'btn--ghost' ?> btn--whatsapp btn--block js-whatsapp-order" href="<?= e($whatsapp['href']) ?>" target="_blank" rel="noopener" data-wa-base="<?= e($whatsapp['base']) ?>" data-wa-in="<?= $attrLines($whatsapp['in']) ?>" data-wa-out="<?= $attrLines($whatsapp['out']) ?>"><?php partial('icon.php', ['name' => 'whatsapp', 'size' => 20]); ?><span class="btn__label"><?= $selectedOut ? 'Ask on WhatsApp' : 'Order on WhatsApp' ?></span></a>
<?php endif; ?>
        </form>
<?php elseif ($hasWhatsapp): ?>
        <a class="btn btn--primary btn--whatsapp btn--block js-whatsapp-order" href="<?= e($whatsapp['href']) ?>" target="_blank" rel="noopener"><?php partial('icon.php', ['name' => 'whatsapp', 'size' => 20]); ?><span class="btn__label">Ask on WhatsApp</span></a>
<?php endif; ?>
        <div class="trust trust--row">
<?php foreach ($trust as $item): ?>
          <div class="trust__item">
            <svg class="trust__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $trustIcons[$item['icon']] ?? '' ?></svg>
            <div>
              <p class="trust__title"><?= e($item['title']) ?></p>
              <p class="trust__text"><?= e($item['text']) ?></p>
            </div>
          </div>
<?php endforeach; ?>
        </div>
<?php if ($accordions !== []): ?>
        <div class="accordion">
<?php foreach ($accordions as $item): ?>
          <div class="accordion__item">
            <h2 class="accordion__title h4"><button class="accordion__trigger js-accordion-trigger" type="button" aria-expanded="<?= $item['open'] ? 'true' : 'false' ?>" aria-controls="p-<?= e($item['id']) ?>" id="t-<?= e($item['id']) ?>"><?= e($item['title']) ?><span class="accordion__glyph" aria-hidden="true"></span></button></h2>
            <div class="accordion__panel" id="p-<?= e($item['id']) ?>" role="region" aria-labelledby="t-<?= e($item['id']) ?>">
              <div class="accordion__inner"><div class="accordion__body"><p><?= nl2br(e($item['body'])) ?></p></div></div>
            </div>
          </div>
<?php endforeach; ?>
        </div>
<?php endif; ?>
      </div>
    </aside>
  </div>
</div>
<?php if ($allSoldOut): ?>
<?php partial('related.php', ['related' => $related, 'sectionId' => 'related']); ?>
<?php endif; ?>
<?php if ($hasComposition): ?>
<section class="section section--divided" id="composition" aria-labelledby="composition-title">
  <div class="container container--narrow">
    <header class="section-header section-header--center sf-reveal">
      <p class="section-header__eyebrow">Inside the bottle</p>
      <h2 class="section-header__title h2" id="composition-title">The Composition</h2>
    </header>
    <div class="stack">
      <?php partial('notes-pyramid.php', ['notes' => $notes]); ?>
      <?php partial('meters.php', ['meters' => $meters]); ?>
<?php if ($seasons !== [] || $occasions !== []): ?>
      <div class="grid grid--2 sf-reveal">
<?php if ($seasons !== []): ?>
        <div>
          <h3 class="u-track text-small">Best season</h3>
          <div class="chips">
<?php foreach ($seasons as $chip): ?>
            <span class="chip chip--static chip--note"><?php if ($chip['icon'] !== ''): ?><svg class="chip__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $seasonIcons[$chip['icon']] ?></svg><?php endif; ?><span><?= e($chip['text']) ?></span></span>
<?php endforeach; ?>
          </div>
        </div>
<?php endif; ?>
<?php if ($occasions !== []): ?>
        <div>
          <h3 class="u-track text-small">Occasion</h3>
          <div class="chips">
<?php foreach ($occasions as $occasion): ?>
            <span class="chip chip--static chip--note"><span><?= e($occasion) ?></span></span>
<?php endforeach; ?>
          </div>
        </div>
<?php endif; ?>
      </div>
<?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<section class="section section--divided" id="reviews" aria-labelledby="reviews-title">
  <div class="container container--narrow">
    <?php partial('reviews.php', ['reviews' => $reviews, 'rating' => $rating, 'reviewsVisible' => $reviewsVisible, 'productName' => $product['name'], 'reviewAction' => $reviewAction, 'trapField' => $trapField]); ?>
  </div>
</section>
<?php if (!$allSoldOut): ?>
<?php partial('related.php', ['related' => $related, 'sectionId' => 'related']); ?>
<?php endif; ?>
<?php if ($sizes !== []): ?>
<div class="sticky-bar js-sticky-bar" aria-label="Add to cart">
  <div class="sticky-bar__info">
    <img class="sticky-bar__thumb" src="<?= e($stickyThumb) ?>" alt="" width="48" height="48" loading="lazy" decoding="async">
    <div class="sticky-bar__text">
      <span class="sticky-bar__name"><?= e($product['name']) ?></span>
      <button class="sticky-bar__size js-open-sizes" type="button" aria-label="Change size"><span class="sticky-bar__size-text"><span data-sticky-size><?= e($selected['label']) ?></span> · <span class="sticky-bar__price" data-sticky-price><?= e($selected['effective_display']) ?></span></span></button>
    </div>
  </div>
  <button class="btn btn--primary sticky-bar__action js-add-to-cart" type="submit" form="add-to-cart-form" data-sticky-add data-label-add="Add" data-label-sold-out="Sold Out"<?= $selectedOut ? ' disabled aria-disabled="true"' . ($hasWhatsapp ? ' hidden' : '') : '' ?>><span class="btn__label"><?= $selectedOut ? 'Sold Out' : 'Add' ?></span></button>
<?php if ($hasWhatsapp): ?>
  <a class="btn btn--ghost btn--whatsapp sticky-bar__action js-whatsapp-order" href="<?= e($whatsapp['href']) ?>" target="_blank" rel="noopener" data-wa-base="<?= e($whatsapp['base']) ?>" data-wa-in="<?= $attrLines($whatsapp['in']) ?>" data-wa-out="<?= $attrLines($whatsapp['out']) ?>" data-sticky-whatsapp<?= $selectedOut ? '' : ' hidden' ?>><span class="btn__label">WhatsApp</span></a>
<?php endif; ?>
</div>
<?php endif; ?>
<?php if ($images !== []): ?>
<div id="lightbox" class="modal modal--lightbox" role="dialog" aria-modal="true" aria-label="Product images" aria-hidden="true" hidden>
  <div class="modal__overlay"></div>
  <div class="modal__panel">
    <button class="modal__close" type="button" data-dialog-close aria-label="Close"><?php partial('icon.php', ['name' => 'close', 'size' => 24]); ?></button>
    <div class="modal__body">
      <div class="lightbox__rail">
<?php foreach ($images as $image): ?>
        <div class="lightbox__slide">
          <picture>
            <source type="image/webp" srcset="<?= e($image['zoom_webp']) ?>">
            <img class="lightbox__img" src="<?= e($image['zoom']) ?>" alt="<?= e($image['alt']) ?>" width="1400" height="1750" loading="lazy" decoding="async">
          </picture>
        </div>
<?php endforeach; ?>
      </div>
      <p class="lightbox__counter">1 / <?= e((string) count($images)) ?></p>
    </div>
  </div>
</div>
<?php endif; ?>
<script type="application/json" id="sf-sizes"><?= ejs($sizesJson) ?></script>
