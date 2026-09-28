<?php
defined('SKYFR') || exit;
$products = is_array($products ?? null) ? $products : [];
$facetGroups = is_array($facetGroups ?? null) ? $facetGroups : [];
$priceBands = is_array($priceBands ?? null) ? $priceBands : [];
$chips = is_array($chips ?? null) ? $chips : [];
$pageLinks = is_array($pageLinks ?? null) ? $pageLinks : [];
$bestSellers = is_array($bestSellers ?? null) ? $bestSellers : [];
$state = is_array($state ?? null) ? $state : [];
$sorts = is_array($sorts ?? null) ? $sorts : [];
$total = (int) ($total ?? 0);
$page = (int) ($page ?? 1);
$pages = (int) ($pages ?? 1);
$perPage = (int) ($perPage ?? 24);
$activeCount = (int) ($activeCount ?? 0);
$searchQuery = (string) ($searchQuery ?? '');
$searchLanding = (bool) ($searchLanding ?? false);
$landingKind = (string) ($landingKind ?? 'shop');
$heroImage = is_array($heroImage ?? null) ? $heroImage : null;
$formAction = url((string) ($basePath ?? '/shop'));
$landingCount = $total === 1 ? '1 fragrance' : $total . ' fragrances';
$hiddenState = static function (array $except = []) use ($state): void {
    foreach (['collection', 'gender', 'family', 'price', 'on_sale', 'in_stock', 'q', 'sort'] as $key) {
        if (in_array($key, $except, true)) {
            continue;
        }
        $value = $state[$key] ?? '';
        if (is_array($value)) {
            $value = implode(',', $value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '';
        }
        if ((string) $value === '') {
            continue;
        }
        echo '<input type="hidden" name="' . e($key) . '" value="' . e((string) $value) . '">' . "\n";
    }
};
$renderFilters = static function (string $suffix, bool $withSort) use ($facetGroups, $priceBands, $state, $sorts, $sort, $formAction, $hiddenState, $clearUrl, $total): void {
    ?>
<form class="filters" method="get" action="<?= e($formAction) ?>" id="filters<?= e($suffix) ?>">
<?php $hiddenState($withSort ? ['collection', 'gender', 'family', 'price', 'on_sale', 'in_stock', 'sort'] : ['collection', 'gender', 'family', 'price', 'on_sale', 'in_stock']); ?>
<?php if ($withSort): ?>
  <details class="filters__group" open>
    <summary class="filters__group-title">Sort by</summary>
    <div class="filters__options" role="radiogroup" aria-label="Sort by">
<?php foreach ($sorts as $sortValue => $sortLabel): ?>
      <label class="filters__option<?= $sortValue === $sort ? ' is-active' : '' ?>"><span class="filters__option-label"><input class="field__checkbox" type="radio" name="sort" value="<?= e($sortValue) ?>"<?= $sortValue === $sort ? ' checked' : '' ?>><?= e($sortLabel) ?></span></label>
<?php endforeach; ?>
    </div>
  </details>
<?php endif; ?>
<?php foreach ($facetGroups as $group): ?>
  <details class="filters__group" open>
    <summary class="filters__group-title"><?= e($group['title']) ?></summary>
    <div class="filters__options">
<?php foreach ($group['options'] as $option): ?>
<?php $disabled = $option['count'] === 0 && !$option['active']; ?>
      <label class="filters__option<?= $option['active'] ? ' is-active' : '' ?><?= $disabled ? ' is-disabled' : '' ?>">
        <span class="filters__option-label"><input class="field__checkbox" type="checkbox" name="<?= e($group['key']) ?>[]" value="<?= e($option['value']) ?>"<?= $option['active'] ? ' checked' : '' ?><?= $disabled ? ' disabled' : '' ?>><?= e($option['label']) ?></span>
        <span class="filters__option-count"><?= e((string) $option['count']) ?></span>
      </label>
<?php endforeach; ?>
    </div>
  </details>
<?php endforeach; ?>
<?php if ($priceBands !== []): ?>
  <details class="filters__group" open>
    <summary class="filters__group-title">Price</summary>
    <div class="filters__options" role="radiogroup" aria-label="Price">
      <label class="filters__option<?= ($state['price'] ?? '') === '' ? ' is-active' : '' ?>"><span class="filters__option-label"><input class="field__checkbox" type="radio" name="price" value=""<?= ($state['price'] ?? '') === '' ? ' checked' : '' ?>>Any price</span></label>
<?php foreach ($priceBands as $band): ?>
<?php $bandActive = ($state['price'] ?? '') === $band['value']; $bandDisabled = $band['count'] === 0 && !$bandActive; ?>
      <label class="filters__option<?= $bandActive ? ' is-active' : '' ?><?= $bandDisabled ? ' is-disabled' : '' ?>">
        <span class="filters__option-label"><input class="field__checkbox" type="radio" name="price" value="<?= e($band['value']) ?>"<?= $bandActive ? ' checked' : '' ?><?= $bandDisabled ? ' disabled' : '' ?>><?= e($band['label']) ?></span>
        <span class="filters__option-count"><?= e((string) $band['count']) ?></span>
      </label>
<?php endforeach; ?>
    </div>
  </details>
<?php endif; ?>
  <details class="filters__group" open>
    <summary class="filters__group-title">Availability</summary>
    <div class="filters__options">
<?php if (!isset($preset['on_sale'])): ?>
      <label class="filters__option<?= !empty($state['on_sale']) ? ' is-active' : '' ?>"><span class="filters__option-label"><input class="field__checkbox" type="checkbox" name="on_sale" value="1"<?= !empty($state['on_sale']) ? ' checked' : '' ?>>On Sale Only</span></label>
<?php endif; ?>
      <label class="filters__option<?= !empty($state['in_stock']) ? ' is-active' : '' ?>"><span class="filters__option-label"><input class="field__checkbox" type="checkbox" name="in_stock" value="1"<?= !empty($state['in_stock']) ? ' checked' : '' ?>>In Stock Only</span></label>
    </div>
  </details>
  <div class="filters__foot">
    <button class="btn btn--primary btn--block filters__form-submit" type="submit"><span class="btn__label"><?= $withSort ? 'Show results' : 'Apply filters' ?></span></button>
    <a class="btn btn--text filters__reset" href="<?= e($clearUrl) ?>"><span class="btn__label">Clear all</span></a>
  </div>
</form>
    <?php
};
?>
<?php if (in_array($landingKind, ['collection', 'gender'], true)): ?>
<section class="hero hero--strip<?= $heroImage === null ? ' hero--empty' : '' ?>" aria-labelledby="listing-title">
<?php if ($heroImage !== null): ?>
  <div class="hero__media">
    <picture>
<?php if ($heroImage['srcset_webp'] !== ''): ?>
      <source type="image/webp" srcset="<?= e($heroImage['srcset_webp']) ?>" sizes="100vw">
<?php endif; ?>
      <img class="hero__img" src="<?= e($heroImage['src']) ?>"<?= $heroImage['srcset'] !== '' ? ' srcset="' . e($heroImage['srcset']) . '" sizes="100vw"' : '' ?> alt="" width="1600" height="900" style="--hero-opacity:.55" loading="eager" fetchpriority="high" decoding="async">
    </picture>
  </div>
<?php endif; ?>
  <div class="hero__scrim" aria-hidden="true"></div>
  <div class="hero__body">
<?php if ($landingKind === 'collection' && trim((string) ($landing['tagline'] ?? '')) !== ''): ?>
    <p class="hero__eyebrow hero__enter" style="--i:0"><?= e((string) $landing['tagline']) ?></p>
<?php endif; ?>
    <h1 class="hero__title hero__enter text-gold-grad" id="listing-title" style="--i:1"><?= e($heading) ?></h1>
<?php if (trim((string) $intro) !== ''): ?>
    <p class="hero__sub hero__enter" style="--i:2"><?= e($intro) ?></p>
<?php endif; ?>
<?php if (trim((string) ($mood ?? '')) !== ''): ?>
    <p class="hero__sub hero__enter lead" style="--i:3"><?= e((string) $mood) ?></p>
<?php endif; ?>
    <p class="hero__trust hero__enter" style="--i:4"><?= e($landingCount) ?></p>
  </div>
</section>
<?php else: ?>
<div class="container">
  <?php partial('breadcrumb.php', ['items' => array_map(static fn (array $crumb): array => ['label' => $crumb[0], 'url' => substr((string) $crumb[1], strlen(BASE_PATH))], $breadcrumbs ?? [])]); ?>
  <header class="page-head">
    <h1 class="page-head__title h1" id="listing-title"><?= e($heading) ?></h1>
<?php if (trim((string) $intro) !== ''): ?>
    <p class="page-head__intro text-muted"><?= e($intro) ?></p>
<?php endif; ?>
<?php if ($landingKind === 'search'): ?>
    <form class="form listing__search" role="search" method="get" action="<?= e(url('/search')) ?>">
      <div class="field field--inline">
        <label class="field__label u-sr-only" for="f-search-q">Search the catalogue</label>
        <div class="field__control">
          <input class="field__input field__input--search js-suggest-input" id="f-search-q" name="q" type="search" value="<?= e($searchQuery) ?>" maxlength="60" autocomplete="off" placeholder="Try “oud”, “rose” or “fresh”" data-suggest-target="suggest-page" aria-describedby="hint-search-q">
          <div class="suggest" id="suggest-page" role="listbox"></div>
        </div>
        <button class="btn btn--primary" type="submit"><span class="btn__label">Search</span></button>
        <p class="field__hint" id="hint-search-q">Search by name, scent family, note or SKU.</p>
      </div>
    </form>
<?php if ($searchLanding): ?>
    <div class="chips">
<?php foreach (['oud', 'rose', 'fresh', 'amber', 'vetiver'] as $example): ?>
      <a class="chip chip--note" href="<?= e(url('/search?q=' . eu($example))) ?>"><span><?= e($example) ?></span></a>
<?php endforeach; ?>
    </div>
<?php endif; ?>
<?php endif; ?>
  </header>
</div>
<?php endif; ?>
<?php if ($landingKind === 'gender' && ($preset['gender'] ?? '') === 'unisex'): ?>
<div class="container"><p class="text-small text-muted">Unisex fragrances also appear under For Him and For Her, so the counts across the three pages will not add up.</p></div>
<?php endif; ?>
<?php if ($landingKind === 'collection' && $total === 0): ?>
<section class="section section--tight"><div class="container center"><p class="lead">This collection is being restocked.</p></div></section>
<?php elseif ($landingKind === 'gender' && $total === 0): ?>
<section class="section section--tight"><div class="container center"><p class="lead">Nothing here yet — these are landing soon.</p></div></section>
<?php endif; ?>
<?php if (!$searchLanding): ?>
<section class="section section--flush-top" aria-labelledby="listing-title">
  <div class="container listing-layout">
    <aside class="listing__filters" aria-label="Filters">
      <?php $renderFilters('', false); ?>
    </aside>
    <div class="listing__results">
      <div class="toolbar">
        <p class="toolbar__count" role="status"><?php if ($total === 0): ?>No fragrances<?php elseif ($page > 1): ?>Showing <?= e((string) (($page - 1) * $perPage + 1)) ?>–<?= e((string) min($total, $page * $perPage)) ?> of <?= e((string) $total) ?><?php else: ?><?= e($landingCount) ?><?php endif; ?></p>
        <form class="toolbar__sort" method="get" action="<?= e($formAction) ?>">
          <?php $hiddenState(['sort']); ?>
          <label class="toolbar__sort-label" for="sort">Sort</label>
          <select class="toolbar__select" id="sort" name="sort">
<?php foreach ($sorts as $sortValue => $sortLabel): ?>
            <option value="<?= e($sortValue) ?>"<?= $sortValue === $sort ? ' selected' : '' ?>><?= e($sortLabel) ?></option>
<?php endforeach; ?>
          </select>
          <button class="btn btn--xs toolbar__submit" type="submit"><span class="btn__label">Sort</span></button>
          <button class="btn btn--ghost btn--xs toolbar__filter-toggle" type="button" data-dialog-open="filters-drawer"><span class="btn__label">Filter &amp; Sort<?= $activeCount > 0 ? ' (' . e((string) $activeCount) . ')' : '' ?></span></button>
        </form>
      </div>
<?php if ($chips !== []): ?>
      <div class="chips" aria-label="Active filters">
<?php foreach ($chips as $chip): ?>
        <a class="chip" href="<?= e($chip['url']) ?>" aria-label="Remove filter <?= e($chip['label']) ?>"><span><?= e($chip['label']) ?></span><span class="chip__remove" aria-hidden="true">×</span></a>
<?php endforeach; ?>
<?php if (count($chips) >= 2): ?>
        <a class="chip chip--clear" href="<?= e($clearUrl) ?>">Clear all</a>
<?php endif; ?>
      </div>
<?php endif; ?>
<?php if ($products !== []): ?>
      <h2 class="u-sr-only">Results</h2>
      <div class="grid grid--products js-filter-bar-anchor" data-motion="cards">
<?php foreach ($products as $index => $product): ?>
        <?php partial('product-card.php', ['product' => $product, 'eager' => $index < 2 && $page === 1]); ?>
<?php endforeach; ?>
      </div>
<?php endif; ?>
<?php if ($pages > 1): ?>
      <nav class="pagination" aria-label="Pagination">
<?php if ($prevUrl !== ''): ?>
        <a class="pagination__prev" href="<?= e($prevUrl) ?>" rel="prev">← Prev</a>
<?php else: ?>
        <span class="pagination__prev is-disabled" aria-disabled="true">← Prev</span>
<?php endif; ?>
        <ol class="pagination__list">
<?php foreach ($pageLinks as $link): ?>
<?php if ($link['gap']): ?>
          <li class="pagination__item"><span class="pagination__gap" aria-hidden="true">…</span></li>
<?php else: ?>
          <li class="pagination__item"><a class="pagination__link" href="<?= e($link['url']) ?>"<?= $link['current'] ? ' aria-current="page"' : '' ?>><?= e((string) $link['number']) ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
        </ol>
        <span class="pagination__status">Page <?= e((string) $page) ?> of <?= e((string) $pages) ?></span>
<?php if ($nextUrl !== ''): ?>
        <a class="pagination__next" href="<?= e($nextUrl) ?>" rel="next">Next →</a>
<?php else: ?>
        <span class="pagination__next is-disabled" aria-disabled="true">Next →</span>
<?php endif; ?>
      </nav>
<?php endif; ?>
<?php if ($total === 0 && !in_array($landingKind, ['collection', 'gender'], true)): ?>
      <div class="empty-state">
        <?php partial('icon.php', ['name' => 'search', 'size' => 40, 'class' => 'empty-state__icon']); ?>
<?php if ($landingKind === 'search'): ?>
        <p class="empty-state__title">No fragrances match “<?= e($searchQuery) ?>”.</p>
        <p class="empty-state__text">Check the spelling, or try a scent note like “amber”.</p>
        <div class="empty-state__action"><a class="btn btn--primary" href="<?= e(url('/shop')) ?>"><span class="btn__label">Browse all fragrances</span></a></div>
<?php else: ?>
        <p class="empty-state__title">No fragrances match that combination.</p>
<?php if ($emptyHint !== ''): ?>
        <p class="empty-state__text"><?= e($emptyHint) ?></p>
<?php endif; ?>
        <div class="empty-state__action"><a class="btn btn--primary" href="<?= e($clearUrl) ?>"><span class="btn__label">Clear all filters</span></a></div>
<?php endif; ?>
      </div>
<?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($total === 0 && $bestSellers !== []): ?>
<section class="section section--divided" aria-labelledby="popular-title">
  <div class="container">
    <?php partial('section-header.php', ['eyebrow' => 'Loved most', 'title' => 'Popular right now', 'titleId' => 'popular-title', 'linkHref' => url('/best-sellers'), 'linkLabel' => 'View all']); ?>
    <div class="grid grid--products sf-reveal sf-reveal--stagger">
<?php foreach ($bestSellers as $product): ?>
      <?php partial('product-card.php', ['product' => $product]); ?>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($total === 0 && setting_bool('quiz_band_enabled', true)): ?>
<section class="section section--glow section--divided" aria-labelledby="finder-title">
  <div class="container container--narrow center">
    <?php partial('section-header.php', ['eyebrow' => (string) setting('quiz_band_heading', 'Not sure where to start?'), 'title' => 'Find your signature scent in 60 seconds', 'sub' => (string) setting('quiz_band_text', 'Five quick questions about how you like to smell, and we match you with the perfumes that agree.'), 'center' => true, 'titleId' => 'finder-title']); ?>
    <div class="btn-row cluster cluster--center sf-reveal">
      <a class="btn btn--primary" href="<?= e(url('/scent-finder')) ?>"><?php partial('icon.php', ['name' => 'compass', 'size' => 18, 'class' => 'btn__icon']); ?><span class="btn__label">Start the Scent Finder</span></a>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if (!$searchLanding): ?>
<div class="filter-bar js-filter-bar">
  <button class="btn btn--ghost" type="button" data-dialog-open="filters-drawer"><span class="btn__label">Filter &amp; Sort<?= $activeCount > 0 ? ' (' . e((string) $activeCount) . ')' : '' ?></span></button>
</div>
<div class="drawer-overlay js-filters-overlay" hidden></div>
<aside id="filters-drawer" class="drawer drawer--left drawer--filters" role="dialog" aria-modal="true" aria-labelledby="filters-drawer-title" aria-hidden="true" data-overlay=".js-filters-overlay" hidden>
  <div class="drawer__head">
    <h2 class="drawer__title" id="filters-drawer-title">Filter &amp; Sort</h2>
    <button class="drawer__close" type="button" data-dialog-close aria-label="Close filters"><?php partial('icon.php', ['name' => 'close']); ?></button>
  </div>
  <div class="drawer__body">
    <?php $renderFilters('-m', true); ?>
  </div>
</aside>
<?php endif; ?>
