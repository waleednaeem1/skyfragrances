<?php
defined('SKYFR') || exit;
partial('collection-card.php', []);
$collections = is_array($collections ?? null) ? array_slice($collections, 0, 8) : [];
$cartCount = function_exists('cart_count') ? (int) cart_count() : 0;
$storeName = (string) setting('store_name', 'Sky Fragrances');
$currentPath = request_path();
$navLinks = [
    ['/shop', 'Shop'],
    ['/collections', 'Collections'],
    ['/for-him', 'For Him'],
    ['/for-her', 'For Her'],
    ['/unisex', 'Unisex'],
    ['/scent-finder', 'Scent Finder'],
];
$cartLabel = $cartCount === 0 ? 'Cart, empty' : 'Cart, ' . $cartCount . ($cartCount === 1 ? ' item' : ' items');
?>
<header class="site-header js-site-header">
  <div class="site-header__inner">
    <a class="site-header__burger js-nav-toggle" href="#site-nav-fallback" role="button" aria-expanded="false" aria-controls="site-nav" aria-label="Open menu">
      <?php partial('icon.php', ['name' => 'menu']); ?>
    </a>
    <nav class="site-header__nav" aria-label="Primary">
      <ul class="site-header__list">
<?php foreach ($navLinks as [$navPath, $navLabel]): ?>
<?php $isCurrent = $currentPath === $navPath || ($navPath !== '/shop' && str_starts_with($currentPath, $navPath . '/')); ?>
<?php if ($navPath === '/collections' && $collections !== []): ?>
        <li class="site-header__item site-header__item--has-panel">
          <a class="site-header__link js-panel-trigger" href="<?= e(url($navPath)) ?>" aria-expanded="false" aria-controls="collections-panel"<?= $isCurrent ? ' aria-current="page"' : '' ?>><?= e($navLabel) ?> <?php partial('icon.php', ['name' => 'chevron-down', 'size' => 14]); ?></a>
          <div class="site-header__panel" id="collections-panel">
            <div class="site-header__panel-grid">
<?php foreach ($collections as $panelCollection): ?>
<?php $panelImage = collection_image_set($panelCollection['image'] ?? null); ?>
              <a class="site-header__panel-item" href="<?= e(url('/collections/' . $panelCollection['slug'])) ?>">
                <span class="site-header__panel-media">
<?php if ($panelImage !== null): ?>
                  <img class="site-header__panel-img" src="<?= e(collection_image_src($panelImage, 'webp')) ?>" alt="" width="800" height="450" loading="lazy" decoding="async">
<?php endif; ?>
                </span>
                <span class="site-header__panel-name"><?= e((string) $panelCollection['name']) ?></span>
              </a>
<?php endforeach; ?>
            </div>
            <p class="site-header__panel-foot"><a class="btn btn--text btn--sm" href="<?= e(url('/collections')) ?>"><span class="btn__label">View all collections <?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16]); ?></span></a></p>
          </div>
        </li>
<?php else: ?>
        <li class="site-header__item"><a class="site-header__link" href="<?= e(url($navPath)) ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>><?= e($navLabel) ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
      </ul>
    </nav>
    <a class="site-header__logo" href="<?= e(url('/')) ?>" aria-label="<?= e($storeName) ?> home">
      <picture>
        <source type="image/webp" srcset="<?= e(asset(brand_asset('img/brand/monogram-transparent-256.webp'))) ?>">
        <img src="<?= e(asset(brand_asset('img/brand/monogram-transparent-256.png'))) ?>" alt="" width="34" height="34" decoding="async">
      </picture>
      <span class="site-header__wordmark" aria-hidden="true"><?= e($storeName) ?></span>
    </a>
    <div class="site-header__actions">
      <button class="site-header__action site-header__action--search js-search-open" type="button" aria-label="Search" aria-expanded="false" aria-controls="site-search">
        <?php partial('icon.php', ['name' => 'search']); ?>
      </button>
      <a class="site-header__action site-header__action--track" href="<?= e(url('/track')) ?>" aria-label="Track order">
        <?php partial('icon.php', ['name' => 'package']); ?>
      </a>
      <a class="site-header__action site-header__cart js-cart-open" href="<?= e(url('/cart')) ?>" aria-label="<?= e($cartLabel) ?>" aria-controls="cart-drawer" aria-expanded="false">
        <?php partial('icon.php', ['name' => 'cart']); ?>
<?php if ($cartCount > 0): ?>
        <span class="site-header__count js-cart-count"><?= e((string) $cartCount) ?></span>
<?php endif; ?>
      </a>
    </div>
  </div>
</header>
<div class="search-overlay js-search-overlay" id="site-search" role="dialog" aria-modal="true" aria-label="Search" hidden>
  <div class="search-overlay__inner">
    <div class="search-overlay__top">
      <img src="<?= e(asset(brand_asset('img/brand/monogram-transparent-256.png'))) ?>" alt="" width="34" height="34" loading="lazy" decoding="async">
      <button class="search-overlay__close js-search-close" type="button" aria-label="Close search"><?php partial('icon.php', ['name' => 'close', 'size' => 20]); ?></button>
    </div>
    <form class="search-overlay__form" role="search" action="<?= e(url('/search')) ?>" method="get">
      <label class="u-sr-only" for="header-search">Search perfumes</label>
      <?php partial('icon.php', ['name' => 'search', 'size' => 26, 'class' => 'search-overlay__icon']); ?>
      <input class="search-overlay__input js-suggest-input" id="header-search" type="search" name="q" placeholder="Search perfumes, notes, collections" autocomplete="off" data-suggest-target="suggest-hdr" role="combobox" aria-autocomplete="list" aria-controls="suggest-hdr">
    </form>
    <nav class="chips search-overlay__chips" aria-label="Popular searches">
<?php foreach ([['/search?q=oud', 'Oud'], ['/search?q=rose', 'Rose'], ['/for-him', 'For him'], ['/for-her', 'For her'], ['/best-sellers', 'Best sellers'], ['/new-arrivals', 'New arrivals']] as [$quickPath, $quickLabel]): ?>
      <a class="chip" href="<?= e(url($quickPath)) ?>"><?= e($quickLabel) ?></a>
<?php endforeach; ?>
    </nav>
    <div class="suggest search-overlay__results" id="suggest-hdr" role="listbox" aria-label="Suggestions"></div>
  </div>
</div>
