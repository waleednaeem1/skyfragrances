<?php
defined('SKYFR') || exit;
partial('product-card.php', []);
$status = (int) ($status ?? 404);
$pageHeading = $status === 404 ? 'This page has drifted off.' : trim((string) ($heading ?? 'Something went wrong'));
$pageMessage = $status === 404
    ? 'The link may be old, or the page may have moved. Try a search, or start again from one of these.'
    : trim((string) ($message ?? ''));
$quickLinks = [
    ['/shop', 'Shop All'],
    ['/collections', 'Collections'],
    ['/track', 'Track Order'],
    ['/contact', 'Contact'],
];
$popular = [];
if ($status === 404 && function_exists('db_fetch_all')) {
    try {
        $popular = db_fetch_all('SELECT p.id, p.name, p.slug, p.scent_family, p.is_new, p.rating_avg, p.rating_count, p.published_at,
            (SELECT i.filename FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.sort_order ASC LIMIT 1) AS image,
            (SELECT i.alt_text FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.sort_order ASC LIMIT 1) AS image_alt,
            (SELECT GROUP_CONCAT(s.size_label ORDER BY s.sort_order ASC SEPARATOR \',\') FROM product_sizes s WHERE s.product_id = p.id AND s.is_active = 1) AS size_labels,
            (SELECT COALESCE(SUM(s.stock), 0) FROM product_sizes s WHERE s.product_id = p.id AND s.is_active = 1) AS stock_total,
            (SELECT MIN(COALESCE(s.sale_price, s.price)) FROM product_sizes s WHERE s.product_id = p.id AND s.is_active = 1) AS price_from
            FROM products p WHERE p.is_active = 1 AND p.deleted_at IS NULL
            ORDER BY p.is_featured DESC, p.sales_count DESC, p.rating_avg DESC, p.id DESC LIMIT 4');
    } catch (Throwable $popularError) {
        $popular = [];
    }
}
?>
<section class="section" aria-labelledby="not-found-title">
  <div class="container container--narrow">
    <div class="empty-state">
      <p class="h-display text-gold-grad" aria-hidden="true"><?= e((string) $status) ?></p>
      <h1 class="empty-state__title h2" id="not-found-title"><?= e($pageHeading) ?></h1>
<?php if ($pageMessage !== ''): ?>
      <p class="empty-state__text"><?= e($pageMessage) ?></p>
<?php endif; ?>
<?php if ($status === 404): ?>
      <form class="form" action="<?= e(url('/search')) ?>" method="get" role="search">
        <div class="field field--inline">
          <label class="field__label u-sr-only" for="not-found-search">Search perfumes</label>
          <div class="field__control">
            <input class="field__input field__input--search js-suggest-input" id="not-found-search" type="search" name="q" placeholder="Search perfumes" autocomplete="off" data-suggest-target="suggest-404" aria-describedby="err-not-found-search">
            <div class="suggest" id="suggest-404" role="listbox" aria-label="Suggestions"></div>
          </div>
          <button class="btn btn--primary" type="submit"><span class="btn__label">Search</span></button>
          <p class="field__error" id="err-not-found-search"></p>
        </div>
      </form>
<?php endif; ?>
      <div class="empty-state__action">
        <ul class="cluster cluster--center">
<?php foreach ($quickLinks as [$linkPath, $linkLabel]): ?>
          <li><a class="btn btn--ghost btn--sm" href="<?= e(url($linkPath)) ?>"><span class="btn__label"><?= e($linkLabel) ?></span></a></li>
<?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</section>
<?php if ($popular !== []): ?>
<section class="section section--divided section--tight" aria-labelledby="popular-title">
  <div class="container">
    <?php partial('section-header.php', ['eyebrow' => 'Meanwhile', 'title' => 'Popular right now', 'titleId' => 'popular-title', 'linkHref' => url('/best-sellers'), 'linkLabel' => 'View all', 'reveal' => false]); ?>
    <div class="grid grid--products">
<?php foreach ($popular as $product): ?>
      <?php partial('product-card.php', ['product' => $product]); ?>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
