<?php
defined('SKYFR') || exit;
$name = (string) ($name ?? '');
$brandWords = preg_split('/\s+/', trim((string) setting('store_name', 'Sky Fragrances'))) ?: ['Sky'];
$brandMain = (string) array_shift($brandWords);
$brandSub = implode(' ', $brandWords);
$nameLength = mb_strlen($name, 'UTF-8');
$nameSize = $nameLength <= 8 ? 'lg' : ($nameLength <= 14 ? 'md' : 'sm');
?>
<span class="bottle tone--<?= e((string) ($tone ?? 'gold')) ?>">
  <span class="bottle__cap"></span>
  <span class="bottle__neck"></span>
  <span class="bottle__body">
    <span class="bottle__shine"></span>
    <span class="bottle__sweep"></span>
    <span class="bottle__base"></span>
    <span class="bottle__label">
      <span class="bottle__brand"><?= e($brandMain) ?></span>
<?php if ($brandSub !== ''): ?>
      <span class="bottle__sub"><?= e($brandSub) ?></span>
<?php endif; ?>
      <span class="bottle__name bottle__name--<?= e($nameSize) ?>"><?= e($name) ?></span>
    </span>
  </span>
</span>
