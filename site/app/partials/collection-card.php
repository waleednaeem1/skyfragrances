<?php
defined('SKYFR') || exit;

if (!function_exists('collection_image_set')) {
    function collection_image_set(?string $image): ?array
    {
        $image = trim((string) $image);
        if ($image === '' || str_contains($image, '..')) {
            return null;
        }
        $stem = preg_replace('/\.(webp|jpe?g|png)$/i', '', basename($image)) ?? basename($image);
        $dir = str_contains($image, '/') ? '/' . trim(dirname($image), '/') : '/uploads/collections';
        if (!str_starts_with($dir, '/uploads/') && !str_starts_with($dir, '/assets/')) {
            $dir = '/uploads/' . ltrim($dir, '/');
        }
        $set = ['sizes' => [], 'base' => null];
        foreach (IMAGE_COLLECTION_SIZES as $size => [$width]) {
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
        if (is_file(APP_ROOT . $dir . '/' . basename($image))) {
            $set['base'] = url($dir . '/' . basename($image));
        }
        return ($set['sizes'] === [] && $set['base'] === null) ? null : $set;
    }

    function collection_image_src(?array $set, string $ext = 'jpg', string $preferred = 'card'): string
    {
        if ($set === null) {
            return '';
        }
        foreach (array_unique([$preferred, 'card', 'zoom']) as $size) {
            $candidate = $set['sizes'][$size][$ext] ?? $set['sizes'][$size]['jpg'] ?? $set['sizes'][$size]['webp'] ?? null;
            if ($candidate !== null) {
                return $candidate;
            }
        }
        return (string) $set['base'];
    }

    function collection_image_srcset(?array $set, string $ext): string
    {
        $parts = [];
        foreach ($set['sizes'] ?? [] as $entry) {
            if ($entry[$ext] !== null) {
                $parts[] = $entry[$ext] . ' ' . $entry['w'] . 'w';
            }
        }
        return implode(', ', $parts);
    }

    function collection_card_image_sources(?string $image, string $preferred = 'zoom'): ?array
    {
        $set = collection_image_set($image);
        if ($set === null) {
            return null;
        }
        return [
            'src' => collection_image_src($set, 'jpg', $preferred),
            'webp' => collection_image_src($set, 'webp', $preferred),
            'srcset' => collection_image_srcset($set, 'jpg'),
            'srcset_webp' => collection_image_srcset($set, 'webp'),
        ];
    }

    function setting_image_url(string $key): string
    {
        $value = trim((string) setting($key, ''));
        if ($value === '' || str_contains($value, '..')) {
            return '';
        }
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }
        return url('/' . ltrim($value, '/'));
    }
}

$collection = is_array($collection ?? null) ? $collection : [];
if ($collection === [] || !isset($collection['name'], $collection['slug'])) {
    return;
}
$variant = (string) ($variant ?? '');
$eyebrow = trim((string) ($eyebrow ?? ''));
$count = (int) ($collection['product_count'] ?? -1);
$imageSet = collection_image_set($collection['image'] ?? null);
$href = trim((string) ($href ?? url('/collections/' . $collection['slug'])));
$variantClasses = '';
foreach (preg_split('/\s+/', $variant, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $variantName) {
    if (in_array($variantName, ['large', 'wide', 'gender', 'empty'], true)) {
        $variantClasses .= ' collection-card--' . $variantName;
    }
}
$classes = 'collection-card' . $variantClasses . ($imageSet === null && !str_contains($variantClasses, '--empty') ? ' collection-card--empty' : '');
$sizes = (string) ($sizes ?? '(min-width: 1024px) 24vw, (min-width: 768px) 33vw, 78vw');
$alt = trim((string) ($collection['image_alt'] ?? ($collection['name'] . ' collection — ' . (string) setting('store_name', 'Sky Fragrances'))));
$jpgSrcset = collection_image_srcset($imageSet, 'jpg');
$webpSrcset = collection_image_srcset($imageSet, 'webp');
$eager = (bool) ($eager ?? false);
?>
<a class="<?= e($classes) ?>" href="<?= e($href) ?>">
  <span class="collection-card__media">
<?php if ($imageSet !== null): ?>
    <picture>
<?php if ($webpSrcset !== ''): ?>
      <source type="image/webp" srcset="<?= e($webpSrcset) ?>" sizes="<?= e($sizes) ?>">
<?php endif; ?>
      <img class="collection-card__img" src="<?= e(collection_image_src($imageSet, 'jpg')) ?>"<?= $jpgSrcset !== '' ? ' srcset="' . e($jpgSrcset) . '" sizes="' . e($sizes) . '"' : '' ?> alt="<?= e($alt) ?>" width="800" height="450" <?= $eager ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
    </picture>
<?php endif; ?>
  </span>
  <span class="collection-card__scrim" aria-hidden="true"></span>
  <span class="collection-card__body">
<?php if ($eyebrow !== ''): ?>
    <span class="collection-card__eyebrow"><?= e($eyebrow) ?></span>
<?php endif; ?>
    <span class="collection-card__name"><?= e((string) $collection['name']) ?></span>
<?php if (trim((string) ($collection['tagline'] ?? '')) !== ''): ?>
    <span class="collection-card__tagline"><?= e((string) $collection['tagline']) ?></span>
<?php endif; ?>
<?php if ($count >= 0): ?>
    <span class="collection-card__count"><?= e($count === 1 ? '1 fragrance' : $count . ' fragrances') ?></span>
<?php endif; ?>
    <span class="collection-card__rule" aria-hidden="true"></span>
  </span>
</a>
