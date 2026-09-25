<?php
defined('SKYFR') || exit;
$running = ($mode ?? 'idle') === 'running';
partial_admin('page-header.php', [
    'title' => $running ? 'Regenerating images' : 'Regenerate images',
    'subtitle' => $running ? 'Keep this tab open. It continues on its own every few seconds until every image is rebuilt.' : 'Rebuilds every thumbnail, card, zoom and share image from the largest copy on disk.',
    'back' => '/admin/tools',
    'back_label' => 'Tools',
]);
?>
<?php if ($running): $percent = $total > 0 ? (int) floor(min(100, $done * 100 / $total)) : 100; ?>
<meta http-equiv="refresh" content="1;url=<?= e(url($nextUrl)) ?>">
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title"><?= e((string) $done) ?> of <?= e((string) $total) ?> done</h2><span class="adm-num"><?= e((string) $percent) ?>%</span></div>
  <div class="adm-tile__progress" role="progressbar" aria-valuemin="0" aria-valuemax="<?= e((string) $total) ?>" aria-valuenow="<?= e((string) $done) ?>" aria-label="Images rebuilt"><div class="adm-tile__progress-bar" style="width:<?= e((string) $percent) ?>%"></div></div>
<?php if ($failed > 0): ?>
  <p class="adm-note adm-stock--low"><?= e((string) $failed) ?> could not be rebuilt because no source file was found. They are listed in the app log.</p>
<?php endif; ?>
  <p class="adm-muted" role="status" aria-live="polite">Working on the next batch…</p>
  <div class="adm-cluster">
<?php partial_admin('button.php', ['label' => 'Continue now', 'variant' => 'gold', 'href' => $nextUrl]); ?>
<?php partial_admin('button.php', ['label' => 'Stop', 'variant' => 'text', 'href' => '/admin/tools']); ?>
  </div>
  <p class="adm-note">If the page does not move on by itself, press Continue now. Stopping keeps everything rebuilt so far; start again from Tools to finish.</p>
</div>
<?php else: ?>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Ready</h2></div>
  <p class="adm-muted"><?= e((string) $counts['images']) ?> product photo<?= $counts['images'] === 1 ? '' : 's' ?> and <?= e((string) $counts['collections']) ?> collection image<?= $counts['collections'] === 1 ? '' : 's' ?> will be rebuilt. <?= $webp ? 'JPEG and WebP copies are written.' : 'This server has no WebP support, so JPEG copies only.' ?></p>
  <p class="adm-note">The shop keeps working while it runs. Each batch takes about twenty seconds and the page moves on by itself.</p>
  <div class="adm-cluster">
<?php partial_admin('button.php', ['label' => 'Start', 'variant' => 'gold', 'post' => '/admin/tools/regenerate-images', 'disabled' => $counts['total'] === 0, 'confirm' => "Rebuild every image on the site?\nIt takes about a second per photo and continues automatically in this tab.", 'confirm_label' => 'Start']); ?>
<?php partial_admin('button.php', ['label' => 'Back to Tools', 'variant' => 'text', 'href' => '/admin/tools']); ?>
  </div>
</div>
<?php endif; ?>
