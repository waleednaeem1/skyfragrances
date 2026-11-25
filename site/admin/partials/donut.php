<?php
defined('SKYFR') || exit;
$segments = array_values((array) ($segments ?? []));
$drawn = array_filter($segments, static fn (array $s): bool => (int) $s['value'] > 0);
$total = (int) ($total ?? array_sum(array_map(static fn (array $s): int => (int) $s['value'], $segments)));
$offset = 0.0;
?>
<div class="adm-donut<?= !empty($legend) ? ' adm-donut--legend' : '' ?>">
  <div class="adm-donut__chart">
    <svg class="adm-donut__svg" viewBox="0 0 120 120" aria-hidden="true" focusable="false">
      <circle class="adm-donut__track" cx="60" cy="60" r="50"/>
<?php foreach ($total > 0 ? $drawn : [] as $segment): $share = min(100, (int) $segment['value'] / $total * 100); ?>
      <circle class="adm-donut__seg adm-tone--<?= e((string) $segment['tone']) ?>" cx="60" cy="60" r="50" pathLength="100" stroke-dasharray="<?= e(number_format($share, 3, '.', '')) ?> 100" stroke-dashoffset="<?= e(number_format(-$offset, 3, '.', '')) ?>"/>
<?php $offset += $share; endforeach; ?>
    </svg>
    <span class="adm-donut__center"><strong><?= e((string) ($center ?? '')) ?></strong><?php if (!empty($caption)): ?><span><?= e((string) $caption) ?></span><?php endif; ?></span>
  </div>
<?php if (!empty($legend)): ?>
  <ul class="adm-legend">
<?php foreach ($segments as $segment): ?>
    <li class="adm-legend__item"><span class="adm-legend__dot adm-tone--<?= e((string) $segment['tone']) ?>" aria-hidden="true"></span><span class="adm-legend__label"><?= e((string) $segment['label']) ?></span><span class="adm-legend__value"><?= e((string) ($segment['display'] ?? ($total > 0 ? round((int) $segment['value'] / $total * 100) . '%' : '0%'))) ?></span></li>
<?php endforeach; ?>
  </ul>
<?php endif; ?>
</div>
