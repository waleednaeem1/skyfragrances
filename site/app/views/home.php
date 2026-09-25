<?php
defined('SKYFR') || exit;
partial('product-card.php', []);
partial('collection-card.php', []);
$hero = is_array($hero ?? null) ? $hero : [];
$collections = is_array($collections ?? null) ? $collections : [];
$bestSellers = is_array($bestSellers ?? null) ? $bestSellers : [];
$newArrivals = is_array($newArrivals ?? null) ? $newArrivals : [];
$genderTiles = is_array($genderTiles ?? null) ? $genderTiles : [];
$quizBand = is_array($quizBand ?? null) ? $quizBand : null;
$reviews = is_array($reviews ?? null) ? $reviews : [];
$instagram = is_array($instagram ?? null) ? $instagram : ['handle' => '', 'url' => '', 'tiles' => [], 'fromCatalogue' => false];
$deliveryTime = trim((string) ($deliveryTime ?? '2–4 working days'));
$heroDesktop = trim((string) ($hero['image_desktop'] ?? ''));
$heroMobile = trim((string) ($hero['image_mobile'] ?? ''));
$heroDesktopUrl = $heroDesktop !== '' && !str_contains($heroDesktop, '..') ? (preg_match('#^https?://#i', $heroDesktop) ? $heroDesktop : url('/' . ltrim($heroDesktop, '/'))) : '';
$heroMobileUrl = $heroMobile !== '' && !str_contains($heroMobile, '..') ? (preg_match('#^https?://#i', $heroMobile) ? $heroMobile : url('/' . ltrim($heroMobile, '/'))) : '';
$heroHasImage = $heroDesktopUrl !== '' || $heroMobileUrl !== '';
$heroOpacity = max(0, min(100, (int) ($hero['overlay_opacity'] ?? 60))) / 100;
$heroCtaUrl = trim((string) ($hero['cta_url'] ?? '/shop'));
$heroSecondaryUrl = trim((string) ($hero['secondary_url'] ?? '/scent-finder'));
$heroHref = static fn (string $path): string => preg_match('#^https?://#i', $path) ? $path : url($path === '' ? '/shop' : $path);
$firstSectionId = $collections !== [] ? 'collections' : ($bestSellers !== [] ? 'best-sellers' : 'why-us');
$trustItems = [
    ['droplet', 'Long-Lasting Fragrance', 'Eau de parfum concentration that stays from the morning commute to the last conversation of the night.'],
    ['banknote', 'Cash on Delivery Nationwide', 'Pay the courier at your door, anywhere in Pakistan. Prepay by bank, JazzCash or Easypaisa if you prefer.'],
    ['truck', 'Fast Delivery', 'Packed by hand, tracked, and with you in ' . $deliveryTime . '.'],
    ['refresh', 'Easy Exchange', 'Not the scent you hoped for? Sealed bottles are exchanged within seven days, no questions.'],
];
?>
<section class="hero<?= $heroHasImage ? '' : ' hero--empty' ?>" aria-labelledby="hero-title">
<?php if ($heroHasImage): ?>
  <div class="hero__media">
    <picture>
<?php if ($heroMobileUrl !== '' && $heroDesktopUrl !== ''): ?>
      <source media="(max-width: 767px)" srcset="<?= e($heroMobileUrl) ?>">
<?php endif; ?>
      <img class="hero__img" src="<?= e($heroDesktopUrl !== '' ? $heroDesktopUrl : $heroMobileUrl) ?>" alt="" width="1920" height="1080" style="--hero-opacity:<?= e(number_format($heroOpacity, 2, '.', '')) ?>" loading="eager" fetchpriority="high" decoding="async">
    </picture>
  </div>
<?php endif; ?>
  <div class="hero__scrim" aria-hidden="true"></div>
  <div class="hero__body">
    <p class="hero__eyebrow hero__enter" style="--i:0">Eau de Parfum · Made for Pakistan</p>
    <h1 class="hero__title hero__enter text-gold-grad" id="hero-title" style="--i:1"><?= e((string) ($hero['heading'] ?? 'More Than Just A Scent')) ?></h1>
<?php if (trim((string) ($hero['subheading'] ?? '')) !== ''): ?>
    <p class="hero__sub hero__enter" style="--i:2"><?= e((string) $hero['subheading']) ?></p>
<?php endif; ?>
    <div class="hero__actions hero__enter" style="--i:3">
      <a class="btn btn--primary" href="<?= e($heroHref($heroCtaUrl)) ?>"><span class="btn__label"><?= e((string) ($hero['cta_label'] ?? 'Shop The Collection')) ?></span></a>
<?php if (trim((string) ($hero['secondary_label'] ?? '')) !== ''): ?>
      <a class="btn btn--text" href="<?= e($heroHref($heroSecondaryUrl)) ?>"><span class="btn__label"><?= e((string) $hero['secondary_label']) ?></span> <?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16, 'class' => 'btn__icon']); ?></a>
<?php endif; ?>
    </div>
  </div>
  <a class="hero__cue js-hero-cue" href="#<?= e($firstSectionId) ?>" aria-label="Scroll to the collections"><?php partial('icon.php', ['name' => 'chevron-down', 'size' => 22]); ?></a>
</section>
<?php if ($collections !== []): ?>
<section class="section" id="collections" aria-labelledby="collections-title">
  <div class="container">
    <?php partial('section-header.php', ['eyebrow' => 'Curated', 'title' => 'Shop by Collection', 'titleId' => 'collections-title', 'sub' => 'Five skies, five moods. Each collection is a time of day, bottled.', 'linkHref' => url('/collections'), 'linkLabel' => 'All collections']); ?>
    <div class="rail grid--mosaic sf-reveal sf-reveal--stagger">
<?php foreach ($collections as $index => $collection): ?>
      <?php partial('collection-card.php', ['collection' => $collection, 'variant' => $index === 0 ? 'large' : '', 'sizes' => $index === 0 ? '(min-width: 768px) 50vw, 78vw' : '(min-width: 768px) 25vw, 78vw']); ?>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($bestSellers !== []): ?>
<section class="section section--divided" id="best-sellers" aria-labelledby="best-sellers-title">
  <div class="container">
    <?php partial('section-header.php', ['eyebrow' => 'Loved most', 'title' => 'Best Sellers', 'titleId' => 'best-sellers-title', 'sub' => 'The bottles our customers finish, then order again.', 'linkHref' => url('/best-sellers'), 'linkLabel' => 'View all']); ?>
    <div class="rail rail--cards sf-reveal sf-reveal--stagger">
<?php foreach ($bestSellers as $index => $product): ?>
      <?php partial('product-card.php', ['product' => $product, 'eager' => $index < 2]); ?>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($newArrivals !== []): ?>
<section class="section section--divided" id="new-arrivals" aria-labelledby="new-arrivals-title">
  <div class="container">
    <?php partial('section-header.php', ['eyebrow' => 'Just landed', 'title' => 'New Arrivals', 'titleId' => 'new-arrivals-title', 'sub' => 'The latest compositions to leave the studio.', 'linkHref' => url('/new-arrivals'), 'linkLabel' => 'View all']); ?>
    <div class="grid grid--products sf-reveal sf-reveal--stagger">
<?php foreach ($newArrivals as $index => $product): ?>
<?php if ($index >= 4): ?>
      <div class="u-hide-md-down"><?php partial('product-card.php', ['product' => $product]); ?></div>
<?php else: ?>
      <?php partial('product-card.php', ['product' => $product]); ?>
<?php endif; ?>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($genderTiles !== []): ?>
<section class="section section--divided" id="for-whom" aria-labelledby="for-whom-title">
  <div class="container">
    <?php partial('section-header.php', ['eyebrow' => 'Who is it for?', 'title' => 'For Him, For Her, For Everyone', 'titleId' => 'for-whom-title', 'sub' => 'Three doors into the same house. Walk through whichever feels like you.']); ?>
    <div class="grid grid--tiles sf-reveal sf-reveal--stagger">
<?php foreach ($genderTiles as $tile): ?>
      <?php partial('collection-card.php', ['collection' => ['name' => $tile['name'], 'slug' => $tile['slug'], 'tagline' => $tile['tagline'], 'image' => $tile['image'] !== '' ? ltrim((string) $tile['image'], '/') : null, 'image_alt' => $tile['name'] . ' — Sky Fragrances'], 'href' => url('/' . $tile['slug']), 'variant' => 'wide gender', 'sizes' => '(min-width: 768px) 33vw, 100vw']); ?>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($quizBand !== null): ?>
<section class="section section--glow section--divided" id="scent-finder" aria-labelledby="scent-finder-title">
  <div class="container container--narrow center">
    <?php partial('section-header.php', ['eyebrow' => $quizBand['eyebrow'], 'title' => $quizBand['heading'], 'sub' => $quizBand['text'], 'center' => true, 'titleId' => 'scent-finder-title']); ?>
    <div class="btn-row cluster cluster--center sf-reveal">
      <a class="btn btn--primary" href="<?= e(url('/scent-finder')) ?>"><?php partial('icon.php', ['name' => 'compass', 'size' => 18, 'class' => 'btn__icon']); ?><span class="btn__label"><?= e($quizBand['cta_label']) ?></span></a>
    </div>
  </div>
</section>
<?php endif; ?>
<section class="section t-light" id="why-us" aria-labelledby="why-us-title">
  <div class="container">
    <?php partial('section-header.php', ['eyebrow' => 'The Sky Fragrances promise', 'title' => 'Why choose us', 'center' => true, 'titleId' => 'why-us-title']); ?>
    <div class="trust sf-reveal sf-reveal--stagger">
<?php foreach ($trustItems as [$trustIcon, $trustTitle, $trustText]): ?>
      <div class="trust__item">
        <?php partial('icon.php', ['name' => $trustIcon, 'size' => 32, 'class' => 'trust__icon']); ?>
        <h3 class="trust__title"><?= e($trustTitle) ?></h3>
        <p class="trust__text"><?= e($trustText) ?></p>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php if ($reviews !== []): ?>
<section class="section section--divided" id="voices" aria-labelledby="voices-title">
  <div class="container">
    <?php partial('section-header.php', ['eyebrow' => 'In their words', 'title' => 'Customer Voices', 'sub' => 'Unedited, from people who wore the perfume before they wrote about it.', 'titleId' => 'voices-title']); ?>
    <div class="rail rail--reviews sf-reveal sf-reveal--stagger">
<?php foreach ($reviews as $review): ?>
<?php
    $reviewer = trim((string) $review['customer_name']);
    $reviewerCity = trim((string) ($review['customer_city'] ?? ''));
    $reviewMeta = $reviewer . ($reviewerCity !== '' ? ' — ' . $reviewerCity : '');
?>
      <article class="voice">
        <?php partial('stars.php', ['rating' => (float) $review['rating'], 'count' => 0, 'showCount' => false]); ?>
<?php if (trim((string) ($review['title'] ?? '')) !== ''): ?>
        <p class="voice__title"><?= e((string) $review['title']) ?></p>
<?php endif; ?>
        <p class="voice__quote"><?= e((string) $review['body']) ?></p>
        <p class="voice__meta"><?= e($reviewMeta) ?></p>
        <a class="voice__product" href="<?= e(url('/product/' . $review['product_slug'])) ?>"><?= e((string) $review['product_name']) ?> →</a>
      </article>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($instagram['tiles'] !== [] && (trim((string) $instagram['handle']) !== '' || trim((string) $instagram['url']) !== '')): ?>
<?php
    $instagramHandle = trim((string) $instagram['handle']);
    $instagramUrl = trim((string) $instagram['url']);
    $instagramTitle = $instagramHandle !== '' ? (str_starts_with($instagramHandle, '@') ? $instagramHandle : '@' . $instagramHandle) : 'Sky Fragrances on Instagram';
?>
<section class="section section--divided section--flush-bottom" id="instagram" aria-labelledby="instagram-title">
  <div class="container">
    <?php partial('section-header.php', ['eyebrow' => 'Follow along', 'title' => $instagramTitle, 'sub' => $instagram['fromCatalogue'] ? 'The bottles, as they leave the studio. New drops and behind-the-scenes on Instagram.' : 'The studio, the bottles and the people wearing them.', 'linkHref' => $instagramUrl !== '' ? $instagramUrl : '', 'linkLabel' => 'Follow', 'titleId' => 'instagram-title']); ?>
  </div>
  <div class="container">
    <div class="grid grid--instagram sf-reveal sf-reveal--stagger">
<?php foreach ($instagram['tiles'] as $tile): ?>
<?php
    $tileProduct = $tile['product'];
    $tileHref = $tile['url'] !== '' ? $tile['url'] : ($tileProduct !== null ? url('/product/' . $tileProduct['slug']) : $instagramUrl);
    $tileExternal = preg_match('#^https?://#i', $tileHref) === 1;
    $tileSet = $tileProduct !== null ? product_card_image_set((int) $tileProduct['id'], $tileProduct['image'] ?? null) : null;
    $tileSrc = $tileProduct !== null ? product_card_image_src($tileSet, 'card', 'jpg') : $tile['image'];
    $tileSrcset = $tileProduct !== null ? product_card_image_srcset($tileSet, 'jpg', ['thumb', 'card']) : '';
    $tileWebp = $tileProduct !== null ? product_card_image_srcset($tileSet, 'webp', ['thumb', 'card']) : '';
?>
      <a class="insta" href="<?= e($tileHref) ?>"<?= $tileExternal ? ' target="_blank" rel="noopener"' : '' ?> aria-label="<?= e($tileExternal ? 'Open on Instagram: ' . $tile['alt'] : $tile['alt']) ?>">
        <picture>
<?php if ($tileWebp !== ''): ?>
          <source type="image/webp" srcset="<?= e($tileWebp) ?>" sizes="(min-width: 1200px) 16vw, 33vw">
<?php endif; ?>
          <img class="insta__img" src="<?= e($tileSrc) ?>"<?= $tileSrcset !== '' ? ' srcset="' . e($tileSrcset) . '" sizes="(min-width: 1200px) 16vw, 33vw"' : '' ?> alt="<?= e($tile['alt']) ?>" width="240" height="300" loading="lazy" decoding="async">
        </picture>
        <span class="insta__veil" aria-hidden="true"><?php partial('icon.php', ['name' => $tileExternal ? 'instagram' : 'arrow-right', 'size' => 28]); ?></span>
      </a>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<section class="section section--divided" id="join" aria-labelledby="newsletter-title">
  <div class="container container--narrow center">
    <?php partial('newsletter.php', ['variant' => 'band', 'source' => 'home', 'headingId' => 'newsletter-title']); ?>
  </div>
</section>
