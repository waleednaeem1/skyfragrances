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
    <div class="adm-searchbar">
      <?php partial('icon.php', ['name' => 'search', 'size' => 16, 'class' => 'adm-searchbar__icon']); ?>
      <label class="u-sr-only" for="adm-search"><?= e((string) ($search['label'] ?? 'Search')) ?></label>
      <input class="adm-searchbar__input" id="adm-search" type="search" name="<?= e((string) ($search['name'] ?? 'q')) ?>" value="<?= e((string) ($search['value'] ?? '')) ?>" placeholder="<?= e((string) ($search['placeholder'] ?? 'Search')) ?>" maxlength="<?= e((string) ($search['maxlength'] ?? 60)) ?>" autocomplete="off" enterkeyhint="search">
    </div>
<?php endif; ?>
<?php if ($fields !== []): ?>
    <button type="button" class="adm-btn adm-btn--secondary adm-filters__toggle" data-filters-toggle aria-expanded="false" aria-controls="adm-filters-panel"><?php partial('icon.php', ['name' => 'filter', 'size' => 15]); ?>Filters<?= $activeCount > 0 ? ' (' . e((string) $activeCount) . ')' : '' ?></button>
<?php endif; ?>
    <button type="submit" class="adm-btn adm-btn--secondary adm-filters__submit"><?= e($submitLabel) ?></button>
  </div>
<?php if ($fields !== []): ?>
  <div class="adm-filters__panel" id="adm-filters-panel" data-filters-panel>
<?php foreach ($fields as $i => $field): ?>
<?php if (($field['type'] ?? '') === 'select'): $fid = 'adm-filter-' . preg_replace('/[^a-z0-9_-]+/i', '-', (string) $field['name']); ?>
    <div class="adm-fchip<?= (string) ($field['value'] ?? '') !== '' && (string) ($field['value'] ?? '') !== (string) array_key_first((array) ($field['options'] ?? [])) ? ' is-set' : '' ?>">
      <label class="adm-fchip__label" for="<?= e($fid) ?>"><?= e((string) ($field['label'] ?? '')) ?></label>
      <select class="adm-fchip__select" id="<?= e($fid) ?>" name="<?= e((string) $field['name']) ?>" data-autosubmit>
<?php foreach ((array) ($field['options'] ?? []) as $optValue => $optLabel): ?>
        <option value="<?= e((string) $optValue) ?>"<?= (string) $optValue === (string) ($field['value'] ?? '') ? ' selected' : '' ?>><?= e((string) $optLabel) ?></option>
<?php endforeach; ?>
      </select>
      <?php partial('icon.php', ['name' => 'chevron-down', 'size' => 14, 'class' => 'adm-fchip__chevron']); ?>
    </div>
<?php else: ?>
    <div class="adm-fchip adm-fchip--field"><?php partial_admin('field.php', $field + ['compact' => true, 'autosubmit' => true]); ?></div>
<?php endif; ?>
<?php endforeach; ?>
<?php if (!empty($clear_url) && $activeCount > 0): ?>
    <a class="adm-btn adm-btn--ghost adm-btn--sm" href="<?= e(url((string) $clear_url)) ?>">Clear all</a>
<?php endif; ?>
    <button type="submit" class="adm-btn adm-btn--primary adm-btn--sm adm-filters__apply"><?= e($submitLabel) ?></button>
  </div>
<?php endif; ?>
<?php if ($active !== [] || !empty($count_text)): ?>
  <div class="adm-filters__meta">
<?php if ($active !== []): ?>
    <ul class="adm-filters__chips" aria-label="Active filters">
<?php foreach ($active as $chip): ?>
      <li><a class="adm-chip adm-chip--remove" href="<?= e(url((string) ($chip['remove_url'] ?? $action))) ?>"><?= e((string) ($chip['label'] ?? '')) ?> <span aria-hidden="true">&times;</span><span class="u-sr-only"> remove filter</span></a></li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php if (!empty($count_text)): ?>
    <p class="adm-filters__count" role="status"><?= e((string) $count_text) ?></p>
<?php endif; ?>
  </div>
<?php endif; ?>
</form>
