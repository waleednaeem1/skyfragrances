<?php
defined('SKYFR') || exit;

if (!function_exists('product_card_image_set')) {
    function product_card_image_set(int $productId, ?string $filename): ?array
    {
        $filename = trim((string) $filename);
        if ($filename === '' || str_contains($filename, '..')) {
            return null;
        }
        $stem = preg_replace('/\.(webp|jpe?g|png)$/i', '', basename($filename)) ?? basename($filename);
        if (preg_match('/^product-\d+-[a-f0-9]{10}$/', $stem)) {
            $dir = image_dir_for_stem($stem, 'card');
        } elseif (str_contains($filename, '/')) {
            $dir = '/uploads/products/' . trim(dirname($filename), '/');
        } else {
            $dir = '/uploads/products/' . $productId;
        }
        if ($dir === null) {
            return null;
        }
        $set = ['sizes' => [], 'base' => null];
        foreach (IMAGE_PRODUCT_SIZES as $size => [$width]) {
            $webp = is_file(APP_ROOT . $dir . '/' . $stem . '-' . $size . '.webp');
            $jpg = is_file(APP_ROOT . $dir . '/' . $stem . '-' . $size . '.jpg');
            if ($webp || $jpg) {
                $set['sizes'][$size] = [
                    'w' => $width,
                    'webp' => $webp ? url($dir . '/' . $stem . '-' . $size . '.webp') : null,
                    'jpg' => $jpg ? url($dir . '/' . $stem . '-' . $size . '.jpg') : null,
                ];
            }
        }
        if (is_file(APP_ROOT . $dir . '/' . basename($filename))) {
            $set['base'] = url($dir . '/' . basename($filename));
        }
        return ($set['sizes'] === [] && $set['base'] === null) ? null : $set;
    }

    function product_card_image_src(?array $set, string $preferred = 'card', string $ext = 'jpg'): string
    {
        if ($set === null) {
            return asset('img/placeholder-4x5.svg');
        }
        foreach (array_unique([$preferred, 'card', 'zoom', 'thumb']) as $size) {
            $candidate = $set['sizes'][$size][$ext] ?? $set['sizes'][$size]['jpg'] ?? $set['sizes'][$size]['webp'] ?? null;
            if ($candidate !== null) {
                return $candidate;
            }
        }
        return $set['base'] ?? asset('img/placeholder-4x5.svg');
    }

    function product_card_image_srcset(?array $set, string $ext, array $only = []): string
    {
        $parts = [];
        foreach ($set['sizes'] ?? [] as $size => $entry) {
            if ($only !== [] && !in_array($size, $only, true)) {
                continue;
            }
            if ($entry[$ext] !== null) {
                $parts[] = $entry[$ext] . ' ' . $entry['w'] . 'w';
            }
        }
        return implode(', ', $parts);
    }

    function product_card_is_new(array $product): bool
    {
        if ((int) ($product['is_new'] ?? 0) === 1) {
            return true;
        }
        $published = strtotime((string) ($product['published_at'] ?? ''));
        return $published !== false && $published >= strtotime('-30 days');
    }

    function product_card_alt(array $product, array $sizeLabels): string
    {
        $alt = trim((string) ($product['image_alt'] ?? ''));
        if ($alt !== '') {
            return $alt;
        }
        $range = $sizeLabels === [] ? 'eau de parfum' : implode(' and ', $sizeLabels) . ' eau de parfum';
        return $product['name'] . ' — ' . $range . ' by ' . (string) setting('store_name', 'Sky Fragrances');
    }

    function product_card_pricing(array $product): array
    {
        $sizes = is_array($product['sizes'] ?? null) ? $product['sizes'] : [];
        $labels = [];
        $stock = 0;
        $cheapest = null;
        foreach ($sizes as $size) {
            if ((int) ($size['is_active'] ?? 1) !== 1) {
                continue;
            }
            $labels[] = (string) $size['size_label'];
            $stock += (int) ($size['stock'] ?? 0);
            $effective = money_paisa(effective_price($size['sale_price'] ?? null, (string) $size['price']));
            if ($cheapest === null || $effective < $cheapest['effective']) {
                $cheapest = ['effective' => $effective, 'list' => money_paisa((string) $size['price']), 'id' => (int) $size['id'], 'stock' => (int) ($size['stock'] ?? 0)];
            }
        }
        if ($sizes === []) {
            $labels = array_values(array_filter(array_map('trim', explode(',', (string) ($product['size_labels'] ?? '')))));
            $stock = (int) ($product['stock_total'] ?? $product['total_stock'] ?? 0);
            $from = (string) ($product['price_from'] ?? $product['min_price'] ?? '0');
            $cheapest = ['effective' => money_paisa($from), 'list' => money_paisa((string) ($product['compare_at'] ?? $from)), 'id' => (int) ($product['single_size_id'] ?? 0), 'stock' => $stock];
        }
        $isSale = $cheapest !== null && $cheapest['effective'] > 0 && $cheapest['list'] > $cheapest['effective'];
        return [
            'labels' => $labels,
            'stock' => $stock,
            'cheapest' => $cheapest,
            'is_sale' => $isSale,
            'save_percent' => $isSale ? (int) round(100 - ($cheapest['effective'] * 100 / max(1, $cheapest['list']))) : 0,
        ];
    }
}

$product = is_array($product ?? null) ? $product : [];
if ($product === [] || !isset($product['id'], $product['name'], $product['slug'])) {
    return;
}
$eager = (bool) ($eager ?? false);
$compact = (bool) ($compact ?? false);
$productId = (int) $product['id'];
$productUrl = url('/product/' . $product['slug']);
$pricing = product_card_pricing($product);
$sizeLabels = $pricing['labels'];
$cheapest = $pricing['cheapest'];
$soldOut = $pricing['stock'] <= 0;
$isSale = $pricing['is_sale'];
$badges = [];
if ($soldOut) {
    $badges[] = ['sold-out', 'Sold Out'];
}
if ($isSale && $pricing['save_percent'] > 0) {
    $badges[] = ['sale', 'Sale −' . $pricing['save_percent'] . '%'];
}
if (product_card_is_new($product)) {
    $badges[] = ['new', 'New'];
}
$badges = array_slice($badges, 0, 2);
$imageSet = product_card_image_set($productId, $product['image'] ?? null);
$hoverSet = product_card_image_set($productId, $product['image_alt_filename'] ?? null);
$alt = product_card_alt($product, $sizeLabels);
$ratingAvg = (float) ($product['rating_avg'] ?? 0);
$ratingCount = (int) ($product['rating_count'] ?? 0);
$singleSizeId = count($sizeLabels) === 1 && $cheapest !== null ? (int) $cheapest['id'] : 0;
$cardClasses = 'product-card' . ($soldOut ? ' is-sold-out' : '') . ($compact ? ' product-card--compact' : '');
$imageSizes = (string) ($sizes ?? '(min-width: 1200px) 22vw, (min-width: 768px) 30vw, 46vw');
$jpgSrcset = product_card_image_srcset($imageSet, 'jpg', ['thumb', 'card']);
$webpSrcset = product_card_image_srcset($imageSet, 'webp', ['thumb', 'card']);
?>
<article class="<?= e($cardClasses) ?>">
<?php if ($imageSet !== null): ?>
  <a class="product-card__media" href="<?= e($productUrl) ?>" tabindex="-1" aria-hidden="true">
    <picture>
<?php if ($webpSrcset !== ''): ?>
      <source type="image/webp" srcset="<?= e($webpSrcset) ?>" sizes="<?= e($imageSizes) ?>">
<?php endif; ?>
      <img class="product-card__img" src="<?= e(product_card_image_src($imageSet, 'card', 'jpg')) ?>"<?= $jpgSrcset !== '' ? ' srcset="' . e($jpgSrcset) . '" sizes="' . e($imageSizes) . '"' : '' ?> alt="<?= e($alt) ?>" width="600" height="750" <?= $eager ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
    </picture>
<?php if ($hoverSet !== null): ?>
    <img class="product-card__img product-card__img--alt" src="<?= e(product_card_image_src($hoverSet, 'card', 'jpg')) ?>" alt="" width="600" height="750" loading="lazy" decoding="async">
<?php endif; ?>
<?php if ($badges !== []): ?>
    <div class="badge-stack">
<?php foreach ($badges as [$badgeType, $badgeLabel]): ?>
      <span class="badge badge--<?= e($badgeType) ?>"><?= e($badgeLabel) ?></span>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </a>
<?php else: ?>
  <?php partial('placeholder.php', ['label' => (string) $product['name'], 'class' => 'product-card__media']); ?>
<?php endif; ?>
  <div class="product-card__body">
    <h3 class="product-card__name"><a class="product-card__link" href="<?= e($productUrl) ?>"><?= e((string) $product['name']) ?></a></h3>
<?php $metaParts = array_values(array_filter([trim((string) ($product['scent_family'] ?? '')), implode(' · ', $sizeLabels)], static fn (string $part): bool => $part !== '')); ?>
<?php if ($metaParts !== []): ?>
    <p class="product-card__meta"><?= e(implode(' · ', $metaParts)) ?></p>
<?php endif; ?>
<?php if ($ratingCount > 0): ?>
    <p class="product-card__rating"><?php partial('stars.php', ['rating' => $ratingAvg, 'count' => $ratingCount]); ?></p>
<?php endif; ?>
<?php if ($cheapest !== null && $cheapest['effective'] > 0): ?>
    <p class="product-card__price price<?= $isSale ? ' price--sale' : '' ?>">
<?php if (count($sizeLabels) > 1): ?>
      <span class="price__from">From</span>
<?php endif; ?>
      <span class="price__now"><?= e(money(money_from_paisa($cheapest['effective']))) ?></span>
<?php if ($isSale): ?>
      <s class="price__was"><?= e(money(money_from_paisa($cheapest['list']))) ?></s>
<?php endif; ?>
    </p>
<?php endif; ?>
  </div>
<?php if (!$compact): ?>
  <div class="product-card__action">
<?php if ($soldOut): ?>
    <button class="btn btn--ghost btn--block btn--card" type="button" disabled><span class="btn__label">Sold Out</span></button>
<?php elseif ($singleSizeId > 0): ?>
    <form class="js-add-to-cart-form" action="<?= e(url('/api/cart/add')) ?>" method="post">
      <?= session_status() === PHP_SESSION_ACTIVE ? csrf_field() : '' ?>
      <input type="hidden" name="size_id" value="<?= e((string) $singleSizeId) ?>">
      <input type="hidden" name="qty" value="1">
      <button class="btn btn--primary btn--block btn--card js-add-to-cart" type="submit"><span class="btn__label">Add to Cart</span></button>
    </form>
<?php else: ?>
    <a class="btn btn--ghost btn--block btn--card" href="<?= e($productUrl . '#sizes') ?>"><span class="btn__label">Choose Size</span></a>
<?php endif; ?>
  </div>
<?php endif; ?>
</article>
