<?php
defined('SKYFR') || exit;
$eyebrow = trim((string) ($eyebrow ?? ''));
$title = trim((string) ($title ?? ''));
$sub = trim((string) ($sub ?? ''));
$linkHref = trim((string) ($linkHref ?? ''));
$linkLabel = trim((string) ($linkLabel ?? 'View all'));
$linkIcon = (string) ($linkIcon ?? 'arrow-right');
$linkStyle = (string) ($linkStyle ?? 'text');
$center = (bool) ($center ?? false);
$titleId = trim((string) ($titleId ?? ''));
$titleTag = (string) ($titleTag ?? 'h2');
$titleTag = in_array($titleTag, ['h1', 'h2', 'h3'], true) ? $titleTag : 'h2';
$railId = trim((string) ($rail ?? ''));
$emWords = max(0, (int) ($em ?? 1));
if ($title === '') {
    return;
}
?>
<header class="section-header<?= $center ? ' section-header--center' : '' ?>" data-reveal>
  <div class="section-header__text">
<?php if ($eyebrow !== ''): ?>
    <p class="eyebrow"><?= e($eyebrow) ?></p>
<?php endif; ?>
    <<?= $titleTag ?> class="section-header__title"<?= $titleId !== '' ? ' id="' . e($titleId) . '"' : '' ?>><?= em_tail($title, $emWords) ?></<?= $titleTag ?>>
<?php if ($sub !== ''): ?>
    <p class="section-header__sub"><?= e($sub) ?></p>
<?php endif; ?>
  </div>
<?php if ($linkHref !== '' || $railId !== ''): ?>
  <div class="section-header__aside">
<?php if ($linkHref !== ''): ?>
    <a class="btn btn--<?= e($linkStyle) ?>" href="<?= e($linkHref) ?>"<?= preg_match('#^https?://#i', $linkHref) ? ' target="_blank" rel="noopener"' : '' ?>><span class="btn__label"><?= e($linkLabel) ?><?php partial('icon.php', ['name' => $linkIcon, 'size' => 16]); ?></span></a>
<?php endif; ?>
<?php if ($railId !== ''): ?>
    <button class="rail-btn" type="button" data-rail-prev aria-controls="<?= e($railId) ?>" aria-label="Scroll back"><?php partial('icon.php', ['name' => 'arrow-left', 'size' => 18]); ?></button>
    <button class="rail-btn" type="button" data-rail-next aria-controls="<?= e($railId) ?>" aria-label="Scroll forward"><?php partial('icon.php', ['name' => 'arrow-right', 'size' => 18]); ?></button>
<?php endif; ?>
  </div>
<?php endif; ?>
</header>
