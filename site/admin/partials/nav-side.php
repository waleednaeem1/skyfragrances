<?php
defined('SKYFR') || exit;
$user = auth_user();
$name = trim((string) ($user['display_name'] ?? '')) ?: (string) ($user['username'] ?? '');
$host = (string) (parse_url(SITE_URL, PHP_URL_HOST) ?: 'skyfragrances.com');
$countLabels = ['orders' => 'orders', 'reviews' => 'reviews waiting', 'failed_emails' => 'emails failed'];
?>
<aside class="adm-side" aria-label="Admin">
  <div class="adm-side__top">
    <a class="adm-brand" href="<?= e(url(team_home_url($user))) ?>">
      <span class="adm-brand__mark" aria-hidden="true">S<span>F</span></span>
      <span class="adm-brand__text"><span class="adm-brand__name">Sky Fragrances</span><span class="adm-brand__sub">Admin · <?= e($host) ?></span></span>
    </a>
    <button type="button" class="adm-iconbtn adm-side__toggle" data-side-toggle aria-controls="adm-side-body" aria-expanded="false"><?php partial('icon.php', ['name' => 'menu', 'size' => 18]); ?><span class="u-sr-only">Menu</span></button>
  </div>
  <div class="adm-side__body" id="adm-side-body">
    <button type="button" class="adm-searchbtn" data-palette-open aria-haspopup="dialog"><?php partial('icon.php', ['name' => 'search', 'size' => 15]); ?><span class="adm-searchbtn__label">Search or jump to…</span><kbd class="adm-kbd" data-palette-mod>⌘K</kbd></button>
    <nav class="adm-side__nav" aria-label="Main">
<?php foreach ($nav as $group): ?>
      <div class="adm-navgroup adm-tone--<?= e($group['tone']) ?>">
        <span class="adm-navgroup__label"><?= e($group['label']) ?></span>
<?php foreach ($group['items'] as $item): $active = admin_nav_matches($currentPath, $item['path']); $count = isset($item['count']) ? (int) ($counts[$item['count']] ?? 0) : 0; ?>
        <a class="adm-navlink<?= $active ? ' is-active' : '' ?>" href="<?= e(url($item['path'])) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
          <span class="adm-navlink__icon"><?php partial('icon.php', ['name' => $item['icon'], 'size' => 15]); ?></span>
          <span class="adm-navlink__label"><?= e($item['label']) ?></span>
<?php if ($count > 0): ?>
          <span class="adm-navlink__count<?= $item['count'] !== 'orders' ? ' is-alert' : '' ?>"><?= e((string) $count) ?><span class="u-sr-only"> <?= e($countLabels[$item['count']] ?? '') ?></span></span>
<?php endif; ?>
        </a>
<?php endforeach; ?>
      </div>
<?php endforeach; ?>
    </nav>
    <div class="adm-side__foot">
      <div class="adm-user">
        <span class="adm-user__avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($name, 0, 1))) ?></span>
        <span class="adm-user__text"><span class="adm-user__name"><?= e($name) ?></span><span class="adm-user__role"><?= e($user !== null ? admin_role_label($user) : '') ?></span></span>
      </div>
      <a class="adm-footlink<?= admin_nav_matches($currentPath, '/admin/password') ? ' is-active' : '' ?>" href="<?= e(url('/admin/password')) ?>"><?php partial('icon.php', ['name' => 'key', 'size' => 15]); ?>Change password</a>
      <form method="post" action="<?= e(url('/admin/logout')) ?>"><?= csrf_field() ?><button type="submit" class="adm-footlink adm-footlink--danger"><?php partial('icon.php', ['name' => 'logout', 'size' => 15]); ?>Log out</button></form>
    </div>
  </div>
</aside>
