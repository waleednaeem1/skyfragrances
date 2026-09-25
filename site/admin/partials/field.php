<?php
defined('SKYFR') || exit;
$type = (string) ($type ?? 'text');
$name = (string) ($name ?? '');
$id = (string) ($id ?? preg_replace('/[^a-z0-9_-]+/i', '_', $name));
$label = (string) ($label ?? '');
$value = $value ?? '';
$options = is_array($options ?? null) ? $options : [];
$error = (string) ($error ?? '');
$help = (string) ($help ?? '');
$required = !empty($required);
$describedBy = trim(($error !== '' ? $id . '-error ' : '') . ($help !== '' ? $id . '-help' : ''));
$extra = '';
foreach ((array) ($attr ?? []) as $attrName => $attrValue) {
    if (preg_match('/^[a-z][a-z0-9-]*$/', (string) $attrName)) {
        $extra .= ' ' . $attrName . '="' . e((string) $attrValue) . '"';
    }
}
if ($required) {
    $extra .= ' required aria-required="true"';
}
if ($error !== '') {
    $extra .= ' aria-invalid="true"';
}
if ($describedBy !== '') {
    $extra .= ' aria-describedby="' . e($describedBy) . '"';
}
if (!empty($autosubmit) && in_array($type, ['select', 'date'], true)) {
    $extra .= ' data-autosubmit';
}
if (isset($inputmode)) {
    $extra .= ' inputmode="' . e((string) $inputmode) . '"';
} elseif ($type === 'money' || $type === 'int') {
    $extra .= ' inputmode="numeric"';
} elseif ($type === 'phone') {
    $extra .= ' inputmode="tel"';
}
$wrapClass = 'adm-field' . (!empty($compact) ? ' adm-field--compact' : '') . ($error !== '' ? ' adm-field--error' : '') . (!empty($class) ? ' ' . $class : '');
$inputType = match ($type) { 'money', 'int' => 'text', 'phone' => 'tel', 'email' => 'email', 'url' => 'url', 'date' => 'date', 'password' => 'password', 'number' => 'number', 'search' => 'search', default => 'text' };
?>
<div class="<?= e($wrapClass) ?>">
<?php if ($type === 'checkbox'): ?>
  <label class="adm-check">
    <input type="checkbox" class="adm-check__input" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e((string) ($checked_value ?? '1')) ?>"<?= !empty($value) ? ' checked' : '' ?><?= $extra ?>>
    <span class="adm-check__box" aria-hidden="true"></span>
    <span class="adm-check__label"><?= e($label) ?></span>
  </label>
<?php else: ?>
<?php if ($label !== ''): ?>
  <<?= in_array($type, ['radio', 'segment', 'checkgroup'], true) ? 'span' : 'label' ?> class="adm-field__label"<?= in_array($type, ['radio', 'segment', 'checkgroup'], true) ? ' id="' . e($id) . '-label"' : ' for="' . e($id) . '"' ?>><?= e($label) ?><?php if ($required): ?> <span class="adm-field__req" aria-hidden="true">*</span><?php endif; ?></<?= in_array($type, ['radio', 'segment', 'checkgroup'], true) ? 'span' : 'label' ?>>
<?php endif; ?>
<?php if ($type === 'select'): ?>
  <select class="adm-input adm-select" id="<?= e($id) ?>" name="<?= e($name) ?>"<?= $extra ?>>
<?php foreach ($options as $optValue => $optLabel): ?>
    <option value="<?= e((string) $optValue) ?>"<?= (string) $optValue === (string) $value ? ' selected' : '' ?>><?= e((string) $optLabel) ?></option>
<?php endforeach; ?>
  </select>
<?php elseif ($type === 'textarea'): ?>
  <textarea class="adm-input adm-textarea" id="<?= e($id) ?>" name="<?= e($name) ?>" rows="<?= e((string) ($rows ?? 4)) ?>"<?= isset($maxlength) ? ' maxlength="' . e((string) $maxlength) . '" data-counter' : '' ?><?= $extra ?>><?= e((string) $value) ?></textarea>
<?php elseif ($type === 'radio' || $type === 'segment'): ?>
  <div class="<?= $type === 'segment' ? 'adm-segment' : 'adm-radios' ?>" role="radiogroup" aria-labelledby="<?= e($id) ?>-label">
<?php foreach ($options as $optValue => $optLabel): ?>
    <label class="<?= $type === 'segment' ? 'adm-segment__item' : 'adm-radio' ?>">
      <input type="radio" class="<?= $type === 'segment' ? 'adm-segment__input' : 'adm-radio__input' ?>" name="<?= e($name) ?>" value="<?= e((string) $optValue) ?>"<?= (string) $optValue === (string) $value ? ' checked' : '' ?><?= $required ? ' required' : '' ?>>
      <span class="<?= $type === 'segment' ? 'adm-segment__label' : 'adm-radio__label' ?>"><?= e((string) $optLabel) ?></span>
    </label>
<?php endforeach; ?>
  </div>
<?php elseif ($type === 'checkgroup'): ?>
  <div class="adm-checks" role="group" aria-labelledby="<?= e($id) ?>-label">
<?php $selected = is_array($value) ? array_map('strval', $value) : array_filter(array_map('trim', explode(',', (string) $value))); ?>
<?php foreach ($options as $optValue => $optLabel): ?>
    <label class="adm-check"><input type="checkbox" class="adm-check__input" name="<?= e($name) ?>[]" value="<?= e((string) $optValue) ?>"<?= in_array((string) $optValue, $selected, true) ? ' checked' : '' ?>><span class="adm-check__box" aria-hidden="true"></span><span class="adm-check__label"><?= e((string) $optLabel) ?></span></label>
<?php endforeach; ?>
  </div>
<?php elseif ($type === 'money'): ?>
  <div class="adm-input-group"><span class="adm-input-group__prefix" aria-hidden="true">Rs.</span><input class="adm-input" id="<?= e($id) ?>" name="<?= e($name) ?>" type="text" value="<?= e((string) $value) ?>" placeholder="0"<?= $extra ?>></div>
<?php else: ?>
  <input class="adm-input" id="<?= e($id) ?>" name="<?= e($name) ?>" type="<?= e($inputType) ?>" value="<?= e((string) $value) ?>"<?= isset($placeholder) ? ' placeholder="' . e((string) $placeholder) . '"' : '' ?><?= isset($maxlength) ? ' maxlength="' . e((string) $maxlength) . '" data-counter' : '' ?><?= $extra ?>>
<?php endif; ?>
<?php endif; ?>
<?php if ($error !== ''): ?>
  <p class="adm-field__error" id="<?= e($id) ?>-error"><?= e($error) ?></p>
<?php endif; ?>
<?php if ($help !== ''): ?>
  <p class="adm-field__help" id="<?= e($id) ?>-help"><?= e($help) ?></p>
<?php endif; ?>
</div>
