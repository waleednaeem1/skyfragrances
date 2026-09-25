<?php
defined('SKYFR') || exit;
$label = trim((string) ($label ?? ''));
$ratio = (string) ($ratio ?? '4x5');
$extraClass = trim((string) ($class ?? ''));
$ratioClass = in_array($ratio, ['3x4', '1x1', '16x9'], true) ? ' placeholder--' . $ratio : '';
$ariaLabel = $label !== '' ? $label . ' — image coming soon' : 'Image coming soon';
?>
<div class="<?= e(trim('placeholder' . $ratioClass . ' ' . $extraClass)) ?>" role="img" aria-label="<?= e($ariaLabel) ?>">
  <svg class="placeholder__mark" viewBox="0 0 120 120" width="120" height="120" aria-hidden="true" focusable="false"><text x="60" y="78" text-anchor="middle" font-family="Cormorant Garamond, Georgia, serif" font-weight="300" font-size="64" letter-spacing="4" fill="currentColor">SF</text></svg>
</div>
