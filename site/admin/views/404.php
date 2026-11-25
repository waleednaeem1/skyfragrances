<?php
defined('SKYFR') || exit;
$status = (string) (int) ($status ?? 404);
$heading = (string) ($heading ?? 'Page not found');
$message = (string) ($message ?? '');
?>
<section class="adm-card adm-notfound">
  <p class="adm-notfound__code" aria-hidden="true"><?= e($status[0]) ?><span><?= e(substr($status, 1, 1)) ?></span><?= e(substr($status, 2)) ?></p>
  <h1 class="adm-notfound__title"><?= e($heading) ?></h1>
<?php if ($message !== ''): ?>
  <p class="adm-notfound__text"><?= e($message) ?></p>
<?php else: ?>
  <p class="adm-notfound__text">There is nothing at <code class="adm-tag"><?= e((string) ($requestedPath ?? request_path())) ?></code>.</p>
<?php endif; ?>
<?php if ((int) $status >= 500): ?>
  <p class="adm-note">If this keeps happening, note the time and tell the developer.</p>
<?php endif; ?>
  <div class="adm-cluster adm-notfound__actions">
    <?php partial_admin('button.php', ['label' => 'Back to dashboard', 'href' => '/admin', 'variant' => 'primary']); ?>
    <?php partial_admin('button.php', ['label' => 'Search', 'type' => 'button', 'icon' => 'search', 'variant' => 'secondary', 'attr' => ['data-palette-open' => '']]); ?>
  </div>
</section>
