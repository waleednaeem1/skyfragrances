<?php
defined('SKYFR') || exit;
$failed = (int) ($counts['failed_emails'] ?? 0);
$offline = maintenance_active();
?>
<header class="adm-topbar">
  <nav class="adm-crumbs" aria-label="Breadcrumb">
    <ol class="adm-crumbs__list">
<?php foreach ($crumbs as $i => $crumb): $last = $i === count($crumbs) - 1; ?>
      <li class="adm-crumbs__item"><?php if ($crumb['href'] !== '' && !$last): ?><a href="<?= e(url($crumb['href'])) ?>"><?= e($crumb['label']) ?></a><?php else: ?><span<?= $last ? ' aria-current="page"' : '' ?>><?= e($crumb['label']) ?></span><?php endif; ?></li>
<?php endforeach; ?>
    </ol>
  </nav>
  <div class="adm-topbar__tools">
    <span class="adm-shopstate<?= $offline ? ' is-off' : '' ?>"><span class="adm-shopstate__dot" aria-hidden="true"></span><?= $offline ? 'Shop offline' : 'Shop online' ?></span>
<?php if (team_can('tools')): ?>
    <a class="adm-iconbtn" href="<?= e(url('/admin/tools')) ?>"><?php partial('icon.php', ['name' => 'bell', 'size' => 17]); ?><?php if ($failed > 0): ?><span class="adm-bell__dot" aria-hidden="true"></span><?php endif; ?><span class="u-sr-only"><?= $failed > 0 ? 'Notifications, ' . $failed . ' ' . ($failed === 1 ? 'email' : 'emails') . ' could not be sent' : 'Notifications, nothing needs attention' ?></span></a>
<?php endif; ?>
    <a class="adm-btn adm-btn--secondary adm-btn--sm" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View shop<?php partial('icon.php', ['name' => 'external', 'size' => 14]); ?></a>
  </div>
</header>
