<?php
defined('SKYFR') || exit;
$lines = is_array($lines ?? null) ? $lines : [];
$flaggedSizeId = (int) ($flaggedSizeId ?? 0);
?>
<div class="summary__lines">
<?php foreach ($lines as $line): ?>
<?php $isFlagged = $flaggedSizeId > 0 && (int) $line['size_id'] === $flaggedSizeId; ?>
  <div class="summary__line">
    <span class="summary__line-media"><img class="summary__line-img" src="<?= e($line['image_url']) ?>" alt="" width="200" height="250" loading="lazy" decoding="async"></span>
    <span><span class="summary__line-name"><?= e($line['product_name']) ?></span><br><span class="summary__line-meta"><?= e($line['size_label']) ?> × <?= e((string) $line['qty']) ?><?php if ($isFlagged || !$line['in_stock'] || $line['notice'] !== ''): ?> <span class="status-pill status-pill--danger"><?= e($line['in_stock'] ? ($line['notice'] !== '' ? 'Updated' : 'Check') : 'Sold out') ?></span><?php endif; ?></span></span>
    <span class="summary__line-total"><?= $line['in_stock'] ? e($line['line_total_display']) : '—' ?></span>
  </div>
<?php endforeach; ?>
</div>
