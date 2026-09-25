<?php
defined('SKYFR') || exit;
$pageHeading = trim((string) ($heading ?? ''));
if ($pageHeading === '') {
    $pageHeading = 'Sky Fragrances';
}
$pageMessage = trim((string) ($message ?? ''));
$actions = is_array($actions ?? null) ? $actions : [['/shop', 'Continue shopping'], ['/contact', 'Contact us']];
?>
<section class="section" aria-labelledby="simple-title">
  <div class="container container--narrow">
    <div class="panel panel--sunken center">
      <h1 class="h2" id="simple-title"><?= e($pageHeading) ?></h1>
<?php if ($pageMessage !== ''): ?>
      <p class="lead text-muted"><?= e($pageMessage) ?></p>
<?php endif; ?>
      <div class="btn-row cluster cluster--center">
<?php foreach ($actions as $index => [$actionPath, $actionLabel]): ?>
        <a class="btn <?= $index === 0 ? 'btn--primary' : 'btn--text' ?>" href="<?= e(url($actionPath)) ?>"><span class="btn__label"><?= e($actionLabel) ?></span></a>
<?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
