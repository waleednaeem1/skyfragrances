<?php
defined('SKYFR') || exit;
$tabs = is_array($tabs ?? null) ? $tabs : [];
?>
<nav class="adm-tabs" aria-label="<?= e((string) ($label ?? 'Sections')) ?>">
<?php foreach ($tabs as $tab): ?>
  <a class="adm-tabs__link<?= !empty($tab['active']) ? ' is-active' : '' ?>" href="<?= e(url((string) ($tab['href'] ?? '#'))) ?>"<?= !empty($tab['active']) ? ' aria-current="page"' : '' ?>><?= e((string) ($tab['label'] ?? '')) ?><?php if (!empty($tab['count'])): ?> <span class="adm-tabs__count"><?= e((string) $tab['count']) ?></span><?php endif; ?></a>
<?php endforeach; ?>
</nav>
