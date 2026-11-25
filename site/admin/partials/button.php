<?php
defined('SKYFR') || exit;
$label = (string) ($label ?? '');
$iconOnly = !empty($icon_only) && !empty($icon);
$content = (!empty($icon) ? view_capture(APP_ROOT . '/app/partials/icon.php', ['name' => (string) $icon, 'size' => 15]) : '') . ($iconOnly ? '<span class="u-sr-only">' . e($label) . '</span>' : e($label));
$variant = (string) ($variant ?? 'ghost');
$size = (string) ($size ?? '');
$classes = 'adm-btn adm-btn--' . $variant . ($size !== '' ? ' adm-btn--' . $size : '') . (!empty($block) ? ' adm-btn--block' : '') . ($iconOnly ? ' adm-btn--icon' : '') . (!empty($class) ? ' ' . $class : '');
$title = $iconOnly ? ' title="' . e($label) . '"' : '';
$attrs = '';
foreach ((array) ($attr ?? []) as $attrName => $attrValue) {
    if (!preg_match('/^[a-z][a-z0-9-]*$/', (string) $attrName)) {
        continue;
    }
    $attrs .= ' ' . $attrName . '="' . e((string) $attrValue) . '"';
}
if (!empty($confirm)) {
    $attrs .= ' data-confirm="' . e((string) $confirm) . '"';
    if (!empty($confirm_word)) {
        $attrs .= ' data-confirm-word="' . e((string) $confirm_word) . '"';
    }
    if (!empty($confirm_label)) {
        $attrs .= ' data-confirm-label="' . e((string) $confirm_label) . '"';
    }
    if ($variant === 'danger') {
        $attrs .= ' data-confirm-danger="1"';
    }
}
?>
<?php if (!empty($post)): ?>
<form method="post" action="<?= e(url((string) $post)) ?>" class="adm-inline-form"<?= !empty($confirm) ? $attrs : '' ?>>
  <?= csrf_field() ?>
<?php foreach ((array) ($fields ?? []) as $fieldName => $fieldValue): ?>
  <input type="hidden" name="<?= e((string) $fieldName) ?>" value="<?= e((string) $fieldValue) ?>">
<?php endforeach; ?>
  <button type="submit" class="<?= e($classes) ?>"<?= $title ?><?= !empty($disabled) ? ' disabled' : '' ?>><?= $content ?></button>
</form>
<?php elseif (!empty($href)): ?>
<a class="<?= e($classes) ?>" href="<?= e(str_starts_with((string) $href, 'http') ? (string) $href : url((string) $href)) ?>"<?= !empty($external) ? ' target="_blank" rel="noopener"' : '' ?><?= $title . $attrs ?>><?= $content ?></a>
<?php else: ?>
<button type="<?= e((string) ($type ?? 'submit')) ?>" class="<?= e($classes) ?>"<?= !empty($name) ? ' name="' . e((string) $name) . '"' : '' ?><?= isset($value) ? ' value="' . e((string) $value) . '"' : '' ?><?= !empty($disabled) ? ' disabled' : '' ?><?= $title . $attrs ?>><?= $content ?></button>
<?php endif; ?>
