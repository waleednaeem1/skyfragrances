<?php
defined('SKYFR') || exit;
partial('product-card.php', []);
partial('collection-card.php', []);
$hero = is_array($hero ?? null) ? $hero : [];
$collections = is_array($collections ?? null) ? $collections : [];
$families = is_array($families ?? null) ? $families : [];
$bestSellers = is_array($bestSellers ?? null) ? $bestSellers : [];
$newArrivals = is_array($newArrivals ?? null) ? $newArrivals : [];
$genderTiles = is_array($genderTiles ?? null) ? $genderTiles : [];
$quizBand = is_array($quizBand ?? null) ? $quizBand : null;
$reviews = is_array($reviews ?? null) ? $reviews : [];
$perks = is_array($perks ?? null) ? $perks : [];
$instagram = is_array($instagram ?? null) ? $instagram : ['handle' => '', 'url' => '', 'tiles' => [], 'fromCatalogue' => false];
$deliveryTime = trim((string) ($deliveryTime ?? '2–4 working days'));
$featureCollection = $collections[0] ?? null;
$moreCollections = array_slice($collections, 1);
$finderWords = ['Summer', 'Fresh', 'Warm', 'Sweet', 'Woody', 'Floral', 'Smoky', 'Office', 'Weddings', 'Evening'];
$trustItems = [
    ['droplet', 'Long-lasting fragrance', 'Eau de parfum concentration that stays from the morning commute to the last conversation of the night.'],
    ['banknote', 'Cash on delivery nationwide', 'Pay the courier at your door, anywhere in Pakistan. Or prepay by bank, JazzCash or Easypaisa.'],
    ['truck', 'Fast delivery', 'Packed by hand, tracked, and with you in ' . $deliveryTime . '.'],
    ['refresh', 'Easy exchange', 'Not the scent you hoped for? Sealed bottles are exchanged within seven days, no questions.'],
];
$rail = static function (string $id, array $products): void {
    echo '<div class="rail" id="' . e($id) . '" data-rail tabindex="0" aria-label="Products, scroll sideways">';
    foreach ($products as $index => $product) {
        echo '<div class="rail__item">';
        partial('product-card.php', ['product' => $product, 'sizes' => '(min-width: 1200px) 300px, (min-width: 768px) 40vw, 72vw']);
        echo '</div>';
    }
    echo '</div>';
};
?>
<?php partial('home-hero.php', ['hero' => $hero]); ?>
<?php if ($featureCollection !== null): ?>
<section class="section" id="collections" aria-labelledby="collections-title">
  <div class="shell">
    <?php partial('section-header.php', ['eyebrow' => 'Curated', 'title' => 'Shop by Collection', 'titleId' => 'collections-title', 'sub' => 'Five skies, five moods. Each collection is a time of day, bottled.', 'linkHref' => url('/collections'), 'linkLabel' => 'All collections']); ?>
    <div class="curated">
      <div class="curated__feature" data-reveal>
        <?php partial('collection-card.php', ['collection' => $featureCollection, 'variant' => 'feature', 'sizes' => '(min-width: 1024px) 760px, 100vw']); ?>
      </div>
<?php if ($families !== []): ?>
      <nav class="curated__families" aria-labelledby="families-title" data-reveal>
        <p class="curated__label" id="families-title">Or shop by scent family</p>
        <ul class="families">
<?php foreach ($families as $family): ?>
          <li>
            <a class="family tone--<?= e(scent_tone($family['name'])) ?>" href="<?= e(url('/scent/' . $family['slug'])) ?>">
              <span class="family__orb" aria-hidden="true"></span>
              <span class="family__text">
                <span class="family__name"><?= e((string) $family['name']) ?></span>
<?php if (trim((string) $family['intro']) !== ''): ?>
                <span class="family__notes"><?= e(truncate_on_word((string) strtok((string) $family['intro'], '.'), 48)) ?></span>
<?php endif; ?>
              </span>
              <?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16, 'class' => 'family__arrow']); ?>
            </a>
          </li>
<?php endforeach; ?>
        </ul>
      </nav>
<?php endif; ?>
    </div>
<?php if ($moreCollections !== []): ?>
    <div class="collection-grid" data-reveal>
<?php foreach ($moreCollections as $collection): ?>
      <?php partial('collection-card.php', ['collection' => $collection, 'sizes' => '(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw']); ?>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </div>
</section>
<?php endif; ?>
<?php if ($bestSellers !== []): ?>
<section class="section section--ruled" id="best-sellers" aria-labelledby="best-sellers-title">
  <div class="shell">
    <?php partial('section-header.php', ['eyebrow' => 'Loved most', 'title' => 'Best Sellers', 'titleId' => 'best-sellers-title', 'sub' => 'The bottles our customers finish, then order again.', 'linkHref' => url('/best-sellers'), 'linkLabel' => 'View all', 'rail' => 'rail-best']); ?>
    <?php $rail('rail-best', $bestSellers); ?>
  </div>
</section>
<?php endif; ?>
<?php if ($genderTiles !== []): ?>
<section class="section section--ruled" id="for-whom" aria-labelledby="for-whom-title">
  <div class="shell">
    <?php partial('section-header.php', ['eyebrow' => 'Who is it for?', 'title' => 'For Him, For Her, For Everyone', 'em' => 2, 'center' => true, 'titleId' => 'for-whom-title', 'sub' => 'Three doors into the same house. Walk through whichever feels like you.']); ?>
    <div class="doors" data-reveal>
<?php foreach ($genderTiles as $tile): ?>
<?php
    $doorImage = trim((string) $tile['image']);
    $doorImageUrl = $doorImage !== '' && !str_contains($doorImage, '..') ? (preg_match('#^https?://#i', $doorImage) ? $doorImage : url('/' . ltrim($doorImage, '/'))) : '';
    $doorProduct = $tile['product'];
    $doorSet = $doorImageUrl === '' && $doorProduct !== null ? product_card_image_set((int) $doorProduct['id'], $doorProduct['image'] ?? null) : null;
?>
      <a class="door door--<?= e($tile['tone']) ?>" href="<?= e(url('/' . $tile['slug'])) ?>">
        <span class="door__panel">
<?php if ($doorImageUrl !== ''): ?>
          <img class="door__img door__img--cover" src="<?= e($doorImageUrl) ?>" alt="" width="800" height="1000" loading="lazy" decoding="async">
<?php elseif ($doorSet !== null): ?>
          <img class="door__img" src="<?= e(product_card_image_src($doorSet, 'card', 'jpg')) ?>" alt="" width="600" height="750" loading="lazy" decoding="async">
<?php endif; ?>
          <span class="door__sweep" aria-hidden="true"></span>
        </span>
        <span class="door__body">
          <span class="door__name"><?= e($tile['name']) ?></span>
<?php if (trim((string) $tile['tagline']) !== ''): ?>
          <span class="door__tagline"><?= e((string) $tile['tagline']) ?></span>
<?php endif; ?>
          <span class="door__count"><?= e($tile['count'] === 1 ? '1 fragrance' : $tile['count'] . ' fragrances') ?></span>
          <span class="door__go" aria-hidden="true"><?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16]); ?></span>
        </span>
      </a>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($newArrivals !== []): ?>
<section class="section section--ruled" id="new-arrivals" aria-labelledby="new-arrivals-title">
  <div class="shell">
    <?php partial('section-header.php', ['eyebrow' => 'Just landed', 'title' => 'New Arrivals', 'titleId' => 'new-arrivals-title', 'sub' => 'The latest compositions to leave the studio.', 'linkHref' => url('/new-arrivals'), 'linkLabel' => 'View all', 'rail' => 'rail-new']); ?>
    <?php $rail('rail-new', $newArrivals); ?>
  </div>
</section>
<?php endif; ?>
<?php if ($quizBand !== null): ?>
<section class="finder-band" id="scent-finder" aria-labelledby="scent-finder-title">
  <div class="finder-band__glow" aria-hidden="true"></div>
  <div class="shell finder-band__inner">
    <div class="finder-band__copy" data-reveal>
      <p class="eyebrow"><?= e($quizBand['eyebrow']) ?></p>
      <h2 class="finder-band__title" id="scent-finder-title">Find your signature scent in <em>60 seconds</em></h2>
      <p class="finder-band__text"><?= e($quizBand['text']) ?></p>
      <a class="btn btn--primary" href="<?= e(url('/scent-finder')) ?>"><span class="btn__label"><?= e($quizBand['cta_label']) ?><?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16]); ?></span></a>
    </div>
    <div class="orbit" aria-hidden="true" data-loop>
      <span class="orbit__ring"></span>
      <span class="orbit__core">60s</span>
      <span class="orbit__words">
<?php foreach ($finderWords as $index => $word): ?>
        <span class="orbit__word" style="--a:<?= $index * 36 ?>deg"><span><?= e($word) ?></span></span>
<?php endforeach; ?>
      </span>
    </div>
  </div>
</section>
<?php endif; ?>
<section class="section" id="why-us" aria-labelledby="why-us-title">
  <div class="shell">
    <?php partial('section-header.php', ['eyebrow' => 'The Sky Fragrances promise', 'title' => 'Why choose us', 'em' => 2, 'center' => true, 'titleId' => 'why-us-title']); ?>
    <ol class="promise" data-reveal>
<?php foreach ($trustItems as $index => [$trustIcon, $trustTitle, $trustText]): ?>
      <li class="promise__item">
        <span class="promise__icon"><?php partial('icon.php', ['name' => $trustIcon, 'size' => 20]); ?></span>
        <span class="promise__num" aria-hidden="true"><?= e(sprintf('%02d', $index + 1)) ?></span>
        <h3 class="promise__title"><?= e($trustTitle) ?></h3>
        <p class="promise__text"><?= e($trustText) ?></p>
      </li>
<?php endforeach; ?>
    </ol>
  </div>
</section>
<?php if ($reviews !== []): ?>
<section class="voices" id="voices" aria-labelledby="voices-title" data-loop>
  <h2 class="u-sr-only" id="voices-title">Customer voices</h2>
  <div class="voices__stage" data-voices>
<?php foreach ($reviews as $index => $review): ?>
<?php
    $reviewer = trim((string) $review['customer_name']);
    $reviewerCity = trim((string) ($review['customer_city'] ?? ''));
?>
    <figure class="voice<?= $index === 0 ? ' is-active' : '' ?>" data-voice<?= $index > 0 ? ' aria-hidden="true"' : '' ?>>
      <?php partial('stars.php', ['rating' => (float) $review['rating'], 'count' => 0, 'showCount' => false]); ?>
      <blockquote class="voice__quote">“<?= e((string) $review['body']) ?>”</blockquote>
      <figcaption class="voice__meta"><?= e(implode(' · ', array_filter([$reviewer, $reviewerCity]))) ?> · <a href="<?= e(url('/product/' . $review['product_slug'])) ?>"><?= e((string) $review['product_name']) ?></a></figcaption>
    </figure>
<?php endforeach; ?>
  </div>
<?php if (count($reviews) > 1): ?>
  <div class="voices__dots" aria-hidden="true"><?php foreach ($reviews as $index => $review): ?><span class="<?= $index === 0 ? 'is-active' : '' ?>"></span><?php endforeach; ?></div>
<?php endif; ?>
</section>
<?php endif; ?>
<?php if ($perks !== []): ?>
<div class="marquee marquee--perks" aria-hidden="true" data-loop>
  <div class="marquee__track">
<?php for ($copy = 0; $copy < 3; $copy++): ?>
<?php foreach ($perks as $perk): ?>
    <span class="marquee__item"><span class="marquee__star">✦</span><?= e($perk) ?></span>
<?php endforeach; ?>
<?php endfor; ?>
  </div>
</div>
<?php endif; ?>
<?php if ($instagram['tiles'] !== [] && (trim((string) $instagram['handle']) !== '' || trim((string) $instagram['url']) !== '')): ?>
<?php
    $instagramHandle = trim((string) $instagram['handle']);
    $instagramUrl = trim((string) $instagram['url']);
    $instagramTitle = $instagramHandle !== '' ? (str_starts_with($instagramHandle, '@') ? $instagramHandle : '@' . $instagramHandle) : 'Sky Fragrances on Instagram';
?>
<section class="section" id="instagram" aria-labelledby="instagram-title">
  <div class="shell">
    <?php partial('section-header.php', ['eyebrow' => 'On Instagram', 'title' => $instagramTitle, 'em' => 0, 'sub' => $instagram['fromCatalogue'] ? 'The bottles, as they leave the studio. New drops and behind-the-scenes on Instagram.' : 'The studio, the bottles and the people wearing them.', 'linkHref' => $instagramUrl, 'linkLabel' => 'Follow', 'linkIcon' => 'instagram', 'linkStyle' => 'ghost', 'titleId' => 'instagram-title']); ?>
    <div class="insta-grid" data-reveal>
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
          <source type="image/webp" srcset="<?= e($tileWebp) ?>" sizes="(min-width: 1200px) 200px, 33vw">
<?php endif; ?>
          <img class="insta__img" src="<?= e($tileSrc) ?>"<?= $tileSrcset !== '' ? ' srcset="' . e($tileSrcset) . '" sizes="(min-width: 1200px) 200px, 33vw"' : '' ?> alt="<?= e($tile['alt']) ?>" width="240" height="240" loading="lazy" decoding="async">
        </picture>
        <span class="insta__veil" aria-hidden="true"><?php partial('icon.php', ['name' => $tileExternal ? 'instagram' : 'arrow-right', 'size' => 22]); ?></span>
      </a>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<section class="join" id="join" aria-labelledby="newsletter-title">
  <div class="join__mist" aria-hidden="true" data-loop><span></span><span></span><span></span></div>
  <div class="join__inner" data-reveal>
    <img class="join__mark" src="<?= e(asset(brand_asset('img/brand/monogram-transparent-256.png'))) ?>" alt="" width="56" height="56" loading="lazy" decoding="async">
    <?php partial('newsletter.php', ['variant' => 'band', 'source' => 'home', 'headingId' => 'newsletter-title']); ?>
  </div>
</section>
