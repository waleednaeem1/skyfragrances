<?php
defined('SKYFR') || exit;
$eyebrow = trim((string) ($eyebrow ?? ''));
$title = trim((string) ($title ?? ''));
$sub = trim((string) ($sub ?? ''));
$linkHref = trim((string) ($linkHref ?? ''));
$linkLabel = trim((string) ($linkLabel ?? 'View all'));
$center = (bool) ($center ?? false);
$number = trim((string) ($number ?? ''));
$reveal = (bool) ($reveal ?? true);
$titleId = trim((string) ($titleId ?? ''));
$titleTag = (string) ($titleTag ?? 'h2');
$titleTag = in_array($titleTag, ['h1', 'h2', 'h3'], true) ? $titleTag : 'h2';
$classes = 'section-header' . ($center ? ' section-header--center' : '') . ($number !== '' ? ' section-header--numbered' : '') . ($reveal ? ' sf-reveal' : '');
if ($title === '') {
    return;
}
?>
<header class="<?= e($classes) ?>">
<?php if ($eyebrow !== ''): ?>
  <p class="section-header__eyebrow"><?= e($eyebrow) ?></p>
<?php endif; ?>
  <<?= $titleTag ?> class="section-header__title"<?= $titleId !== '' ? ' id="' . e($titleId) . '"' : '' ?><?= $number !== '' ? ' data-num="' . e($number) . '"' : '' ?>><?= e($title) ?></<?= $titleTag ?>>
<?php if ($sub !== ''): ?>
  <p class="section-header__sub"><?= e($sub) ?></p>
<?php endif; ?>
<?php if ($linkHref !== ''): ?>
  <a class="section-header__link" href="<?= e($linkHref) ?>"><?= e($linkLabel) ?> <?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16]); ?></a>
<?php endif; ?>
</header>
