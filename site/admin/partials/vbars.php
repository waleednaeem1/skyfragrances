<?php
defined('SKYFR') || exit;
$bars = (array) ($bars ?? []);
$max = max(1, ...array_map(static fn (array $b): int => max(0, (int) $b['value']), $bars ?: [['value' => 1]]));
$tones = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'c7'];
?>
<ol class="adm-vbars<?= !empty($dark) ? ' adm-vbars--dark' : '' ?>"<?= !empty($label) ? ' aria-label="' . e((string) $label) . '"' : '' ?>>
<?php foreach ($bars as $i => $bar): ?>
  <li class="adm-vbars__item adm-tone--<?= e((string) ($bar['tone'] ?? $tones[$i % count($tones)])) ?><?= !empty($bar['current']) ? ' is-current' : '' ?><?= (int) $bar['value'] <= 0 ? ' is-zero' : '' ?>">
<?php if (!empty($values)): ?>
    <span class="adm-vbars__value"><?= e((string) ($bar['display'] ?? $bar['value'])) ?></span>
<?php endif; ?>
    <span class="adm-vbars__bar" style="height: <?= e((string) max(2, round(max(0, (int) $bar['value']) / $max * 100, 1))) ?>%"><span class="u-sr-only"><?= e((string) ($bar['label'] ?? '') . ': ' . ($bar['display'] ?? $bar['value'])) ?></span></span>
<?php if (isset($bar['label']) && empty($dark)): ?>
    <span class="adm-vbars__label" aria-hidden="true"><?= e((string) $bar['label']) ?></span>
<?php endif; ?>
  </li>
<?php endforeach; ?>
</ol>
