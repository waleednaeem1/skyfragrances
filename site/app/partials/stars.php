<?php
defined('SKYFR') || exit;
$rating = max(0.0, min(5.0, (float) ($rating ?? 0)));
$count = (int) ($count ?? 0);
$size = (string) ($size ?? '');
$href = (string) ($href ?? '');
$showAvg = (bool) ($showAvg ?? false);
$showCount = (bool) ($showCount ?? true);
$classes = 'stars' . (in_array($size, ['md', 'lg'], true) ? ' stars--' . $size : '') . ($href !== '' ? ' stars--link' : '');
$label = 'Rated ' . number_format($rating, 1) . ' out of 5 from ' . $count . ' ' . ($count === 1 ? 'review' : 'reviews');
$tag = $href !== '' ? 'a' : 'span';
$starPath = 'M12 2.6l2.85 6.05 6.65.8-4.9 4.6 1.3 6.55L12 17.35l-5.9 3.25 1.3-6.55-4.9-4.6 6.65-.8L12 2.6z';
?>
<<?= $tag ?> class="<?= e($classes) ?>"<?php if ($href !== ''): ?> href="<?= e($href) ?>"<?php else: ?> role="img"<?php endif; ?> aria-label="<?= e($label) ?>">
  <span class="stars__row">
<?php for ($i = 1; $i <= 5; $i++): ?>
<?php $fill = max(0.0, min(1.0, $rating - ($i - 1))); ?>
<?php if ($fill >= 0.98): ?>
    <svg class="stars__star" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="<?= $starPath ?>"/></svg>
<?php elseif ($fill <= 0.02): ?>
    <svg class="stars__star stars__star--empty" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="<?= $starPath ?>"/></svg>
<?php else: ?>
    <span class="stars__star stars__star--partial" style="--fill:<?= e((string) round($fill * 100)) ?>%" aria-hidden="true"></span>
<?php endif; ?>
<?php endfor; ?>
  </span>
<?php if ($showCount): ?>
  <span class="stars__count">(<?= e((string) $count) ?>)</span>
<?php endif; ?>
<?php if ($showAvg): ?>
  <span class="stars__avg"><?= e(number_format($rating, 1)) ?></span>
<?php endif; ?>
</<?= $tag ?>>
