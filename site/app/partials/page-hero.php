<?php
defined('SKYFR') || exit;
$crumbs = is_array($crumbs ?? null) ? $crumbs : [];
$eyebrow = trim((string) ($eyebrow ?? ''));
$title = trim((string) ($title ?? ''));
$intro = trim((string) ($intro ?? ''));
$titleId = trim((string) ($titleId ?? 'page-title'));
$emWords = max(0, (int) ($em ?? 1));
$after = (string) ($after ?? '');
?>
<header class="page-hero">
  <div class="page-hero__mist" aria-hidden="true" data-loop><span></span><span></span></div>
  <div class="page-hero__sparks" aria-hidden="true"><?php for ($spark = 0; $spark < 8; $spark++): ?><span></span><?php endfor; ?></div>
  <div class="shell page-hero__inner">
<?php if ($crumbs !== []): ?>
    <?php partial('breadcrumb.php', ['items' => $crumbs]); ?>
<?php endif; ?>
<?php if ($eyebrow !== ''): ?>
    <p class="eyebrow"><?= e($eyebrow) ?></p>
<?php endif; ?>
    <h1 class="page-hero__title" id="<?= e($titleId) ?>"><?= em_tail($title, $emWords) ?></h1>
<?php if ($intro !== ''): ?>
    <p class="page-hero__intro"><?= e($intro) ?></p>
<?php endif; ?>
<?= $after ?>
  </div>
</header>
