<?php
defined('SKYFR') || exit;
$flashes = is_array($flashes ?? null) ? $flashes : [];
$flashLabels = ['success' => 'Done', 'error' => 'Problem', 'info' => 'Note'];
$flashIcons = ['success' => 'check', 'error' => 'alert', 'info' => 'info'];
?>
<?php foreach ($flashes as $flashItem): ?>
<?php $flashType = in_array($flashItem['type'] ?? '', ['success', 'error', 'info'], true) ? $flashItem['type'] : 'info'; ?>
<div class="adm-flash adm-flash--<?= e($flashType) ?>" role="<?= $flashType === 'error' ? 'alert' : 'status' ?>" data-flash>
  <?php partial('icon.php', ['name' => $flashIcons[$flashType], 'size' => 18, 'class' => 'adm-flash__icon']); ?>
  <span class="adm-flash__text"><span class="u-sr-only"><?= e($flashLabels[$flashType]) ?>: </span><?= e((string) ($flashItem['message'] ?? '')) ?></span>
<?php if (!empty($flashItem['link']) && !empty($flashItem['link_label'])): ?>
  <a class="adm-flash__link" href="<?= e((string) $flashItem['link']) ?>"><?= e((string) $flashItem['link_label']) ?></a>
<?php endif; ?>
  <button type="button" class="adm-flash__close" data-flash-close><?php partial('icon.php', ['name' => 'close', 'size' => 16]); ?><span class="u-sr-only">Dismiss</span></button>
</div>
<?php endforeach; ?>
