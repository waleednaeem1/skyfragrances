<?php
defined('SKYFR') || exit;
$items = is_array($items ?? null) ? array_values($items) : [];
if ($items === []) {
    return;
}
$lastIndex = count($items) - 1;
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <ol class="breadcrumb__list">
<?php foreach ($items as $index => $crumb): ?>
<?php $crumbLabel = (string) ($crumb['label'] ?? ''); $crumbUrl = (string) ($crumb['url'] ?? ''); ?>
<?php if ($index === $lastIndex || $crumbUrl === ''): ?>
    <li class="breadcrumb__item"><span class="breadcrumb__current" aria-current="page"><?= e($crumbLabel) ?></span></li>
<?php else: ?>
    <li class="breadcrumb__item"><a class="breadcrumb__link" href="<?= e(url($crumbUrl)) ?>"><?= e($crumbLabel) ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
  </ol>
</nav>
