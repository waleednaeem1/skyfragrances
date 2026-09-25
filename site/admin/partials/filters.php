<?php
defined('SKYFR') || exit;
$action = (string) ($action ?? request_path());
$fields = is_array($fields ?? null) ? $fields : [];
$search = is_array($search ?? null) ? $search : null;
$active = is_array($active ?? null) ? $active : [];
$hidden = is_array($hidden ?? null) ? $hidden : [];
$activeCount = count($active);
$submitLabel = (string) ($submit_label ?? 'Apply');
?>
<form method="get" action="<?= e(url($action)) ?>" class="adm-filters" data-filters role="search">
<?php foreach ($hidden as $hiddenName => $hiddenValue): ?>
  <input type="hidden" name="<?= e((string) $hiddenName) ?>" value="<?= e((string) $hiddenValue) ?>">
<?php endforeach; ?>
  <div class="adm-filters__bar">
<?php if ($search !== null): ?>
    <label class="u-sr-only" for="adm-search"><?= e((string) ($search['label'] ?? 'Search')) ?></label>
    <input class="adm-input adm-filters__search" id="adm-search" type="search" name="<?= e((string) ($search['name'] ?? 'q')) ?>" value="<?= e((string) ($search['value'] ?? '')) ?>" placeholder="<?= e((string) ($search['placeholder'] ?? 'Search')) ?>" maxlength="<?= e((string) ($search['maxlength'] ?? 60)) ?>" autocomplete="off" enterkeyhint="search">
<?php endif; ?>
<?php if ($fields !== []): ?>
    <button type="button" class="adm-btn adm-btn--ghost adm-filters__toggle" data-filters-toggle aria-expanded="false" aria-controls="adm-filters-panel">Filters<?= $activeCount > 0 ? ' (' . e((string) $activeCount) . ')' : '' ?></button>
<?php endif; ?>
    <button type="submit" class="adm-btn adm-btn--ghost adm-filters__submit"><?= e($submitLabel) ?></button>
  </div>
<?php if ($fields !== []): ?>
  <div class="adm-filters__panel" id="adm-filters-panel" data-filters-panel>
    <div class="adm-filters__grid">
<?php foreach ($fields as $field): ?>
<?php partial_admin('field.php', $field + ['compact' => true, 'autosubmit' => true]); ?>
<?php endforeach; ?>
    </div>
    <div class="adm-filters__panel-actions">
      <button type="submit" class="adm-btn adm-btn--gold"><?= e($submitLabel) ?></button>
<?php if (!empty($clear_url)): ?>
      <a class="adm-btn adm-btn--text" href="<?= e(url((string) $clear_url)) ?>">Clear all</a>
<?php endif; ?>
    </div>
  </div>
<?php endif; ?>
<?php if ($active !== [] || !empty($count_text)): ?>
  <div class="adm-filters__meta">
<?php if ($active !== []): ?>
    <ul class="adm-filters__chips" aria-label="Active filters">
<?php foreach ($active as $chip): ?>
      <li><a class="adm-chip adm-chip--remove" href="<?= e(url((string) ($chip['remove_url'] ?? $action))) ?>"><?= e((string) ($chip['label'] ?? '')) ?> <span aria-hidden="true">&times;</span><span class="u-sr-only"> remove filter</span></a></li>
<?php endforeach; ?>
<?php if (!empty($clear_url)): ?>
      <li><a class="adm-chip adm-chip--clear" href="<?= e(url((string) $clear_url)) ?>">Clear all</a></li>
<?php endif; ?>
    </ul>
<?php endif; ?>
<?php if (!empty($count_text)): ?>
    <p class="adm-filters__count" role="status"><?= e((string) $count_text) ?></p>
<?php endif; ?>
  </div>
<?php endif; ?>
</form>
