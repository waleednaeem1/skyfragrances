<?php
defined('SKYFR') || exit;
?>
<div class="adm-empty">
  <p class="adm-empty__title"><?= e((string) ($title ?? 'Nothing here yet')) ?></p>
<?php if (!empty($text)): ?>
  <p class="adm-empty__text"><?= e((string) $text) ?></p>
<?php endif; ?>
<?php if (!empty($action) && is_array($action)): ?>
  <div class="adm-empty__action"><?php partial_admin('button.php', $action + ['variant' => 'gold']); ?></div>
<?php endif; ?>
</div>
