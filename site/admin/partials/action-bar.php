<?php
defined('SKYFR') || exit;
$mode = (string) ($mode ?? 'save');
$buttons = is_array($buttons ?? null) ? $buttons : [];
?>
<?php if ($mode === 'bulk'): ?>
<div class="adm-bulkbar" data-bulk-bar hidden>
  <span class="adm-bulkbar__count" data-bulk-count aria-live="polite">0 selected</span>
  <span class="adm-bulkbar__sep" aria-hidden="true"></span>
  <div class="adm-bulkbar__buttons">
<?php foreach ($buttons as $button): ?>
<?php partial_admin('button.php', $button + ['type' => 'submit', 'name' => 'action', 'size' => 'sm', 'variant' => 'bulk']); ?>
<?php endforeach; ?>
  </div>
  <button type="button" class="adm-bulkbar__clear" data-bulk-clear><?php partial('icon.php', ['name' => 'close', 'size' => 16]); ?><span class="u-sr-only">Clear selection</span></button>
</div>
<?php else: ?>
<div class="adm-actionbar<?= !empty($static) ? ' adm-actionbar--static' : '' ?>" data-save-bar>
<?php if (!empty($note)): ?>
  <span class="adm-actionbar__note"><span class="adm-actionbar__dot" aria-hidden="true"></span><?= e((string) $note) ?></span>
<?php endif; ?>
  <div class="adm-actionbar__buttons">
<?php foreach ($buttons as $button): ?>
<?php partial_admin('button.php', $button + ['type' => 'submit', 'variant' => 'gold']); ?>
<?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
