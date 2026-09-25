<?php
defined('SKYFR') || exit;
$mode = (string) ($mode ?? 'save');
$buttons = is_array($buttons ?? null) ? $buttons : [];
?>
<?php if ($mode === 'bulk'): ?>
<div class="adm-actionbar adm-actionbar--bulk" data-bulk-bar hidden>
  <span class="adm-actionbar__count" data-bulk-count aria-live="polite">0 selected</span>
  <div class="adm-actionbar__buttons">
<?php foreach ($buttons as $button): ?>
<?php partial_admin('button.php', $button + ['type' => 'submit', 'name' => 'action', 'size' => 'sm', 'variant' => 'ghost']); ?>
<?php endforeach; ?>
  </div>
</div>
<?php else: ?>
<div class="adm-actionbar<?= !empty($static) ? ' adm-actionbar--static' : '' ?>" data-save-bar>
<?php if (!empty($note)): ?>
  <span class="adm-actionbar__note"><?= e((string) $note) ?></span>
<?php endif; ?>
  <div class="adm-actionbar__buttons">
<?php foreach ($buttons as $button): ?>
<?php partial_admin('button.php', $button + ['type' => 'submit', 'variant' => 'gold']); ?>
<?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
