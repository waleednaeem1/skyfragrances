<?php
defined('SKYFR') || exit;
$values = array_values(array_map('intval', (array) ($values ?? [])));
if (count($values) < 2) {
    return;
}
$width = 100;
$height = 32;
$low = min(0, min($values));
$span = max(1, max($values) - $low);
$step = $width / (count($values) - 1);
$points = [];
foreach ($values as $i => $value) {
    $points[] = round($i * $step, 2) . ',' . round($height - 2 - (($value - $low) / $span) * ($height - 4), 2);
}
$line = implode(' ', $points);
?>
<svg class="adm-spark adm-tone--<?= e((string) ($tone ?? 'c1')) ?>" viewBox="0 0 <?= $width ?> <?= $height ?>" preserveAspectRatio="none" aria-hidden="true" focusable="false"><polygon class="adm-spark__area" points="0,<?= $height ?> <?= $line ?> <?= $width ?>,<?= $height ?>"/><polyline class="adm-spark__line" points="<?= $line ?>"/></svg>
