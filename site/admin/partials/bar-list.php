<?php
defined('SKYFR') || exit;
$rows = (array) ($rows ?? []);
$max = max(1, ...array_map(static fn (array $r): int => max(0, (int) $r['value']), $rows ?: [['value' => 1]]));
$tones = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6'];
?>
<ul class="adm-barlist">
<?php foreach ($rows as $i => $row): ?>
  <li class="adm-barlist__item adm-tone--<?= e((string) ($row['tone'] ?? $tones[$i % count($tones)])) ?>">
    <span class="adm-barlist__head"><span class="adm-barlist__label"><?php if (!empty($row['href'])): ?><a href="<?= e(url((string) $row['href'])) ?>"><?= e((string) $row['label']) ?></a><?php else: ?><?= e((string) $row['label']) ?><?php endif; ?></span><span class="adm-barlist__value"><?= e((string) $row['display']) ?></span></span>
    <span class="adm-barlist__track" aria-hidden="true"><span class="adm-barlist__bar" style="width: <?= e((string) round(max(0, (int) $row['value']) / $max * 100, 1)) ?>%"></span></span>
  </li>
<?php endforeach; ?>
</ul>
