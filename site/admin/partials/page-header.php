<?php
defined('SKYFR') || exit;
$title = (string) ($title ?? '');
$subtitle = (string) ($subtitle ?? '');
$actions = is_array($actions ?? null) ? $actions : [];
$backHref = (string) ($back ?? '');
$backLabel = (string) ($back_label ?? 'Back');
?>
<div class="adm-page">
  <div class="adm-page__heading">
<?php if ($backHref !== ''): ?>
    <a class="adm-page__back" href="<?= e(url($backHref)) ?>">&larr; <?= e($backLabel) ?></a>
<?php endif; ?>
    <h1 class="adm-page__title"><?= e($title) ?></h1>
<?php if ($subtitle !== ''): ?>
    <p class="adm-page__sub"><?= e($subtitle) ?></p>
<?php endif; ?>
  </div>
<?php if ($actions !== []): ?>
  <div class="adm-page__actions">
<?php foreach ($actions as $action): ?>
<?php partial_admin('button.php', $action); ?>
<?php endforeach; ?>
  </div>
<?php endif; ?>
</div>
