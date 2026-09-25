<?php
defined('SKYFR') || exit;
$pageHeading = trim((string) ($heading ?? ''));
if ($pageHeading === '') {
    $pageHeading = (string) ($page['title'] ?? 'Sky Fragrances');
}
$bodyHtml = (string) ($body ?? '');
$showUpdated = !empty($showUpdated) && trim((string) ($updatedAt ?? '')) !== '';
?>
<section class="section section--tight">
  <div class="container container--prose">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <ol class="breadcrumb__list">
        <li class="breadcrumb__item"><a class="breadcrumb__link" href="<?= e(url('/')) ?>">Home</a></li>
        <li class="breadcrumb__item"><span class="breadcrumb__current" aria-current="page"><?= e($pageHeading) ?></span></li>
      </ol>
    </nav>
    <header class="page-head">
      <h1 class="page-head__title"><?= e($pageHeading) ?></h1>
<?php if ($showUpdated): ?>
      <p class="page-head__intro text-muted text-small">Last updated <?= e((string) $updatedAt) ?></p>
<?php endif; ?>
    </header>
<?php if ($bodyHtml === ''): ?>
    <p class="text-muted">This page is being written. Message us on WhatsApp if you have a question in the meantime.</p>
<?php else: ?>
    <div class="prose"><?php echo $bodyHtml; ?></div>
<?php endif; ?>
  </div>
</section>
