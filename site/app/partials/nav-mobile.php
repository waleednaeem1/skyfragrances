<?php
defined('SKYFR') || exit;
$collections = is_array($collections ?? null) ? array_slice($collections, 0, 8) : [];
$whatsappDigits = preg_replace('/\D+/', '', (string) setting('whatsapp', ''));
$currentPath = request_path();
$navLinks = [
    ['/shop', 'Shop'],
    ['/collections', 'Collections'],
    ['/for-him', 'For Him'],
    ['/for-her', 'For Her'],
    ['/unisex', 'Unisex'],
    ['/scent-finder', 'Scent Finder'],
];
$secondaryLinks = [
    ['/track', 'Track Order'],
    ['/contact', 'Contact'],
];
?>
<div class="drawer-overlay js-nav-overlay" hidden></div>
<nav id="site-nav" class="nav-mobile js-nav-mobile" role="dialog" aria-modal="true" aria-label="Menu" aria-hidden="true" hidden>
  <div class="nav-mobile__head">
    <button class="nav-mobile__close js-nav-close" type="button" aria-label="Close menu">
      <?php partial('icon.php', ['name' => 'close']); ?>
    </button>
  </div>
  <form class="nav-mobile__search" action="<?= e(url('/search')) ?>" method="get" role="search">
    <label class="u-sr-only" for="nav-search">Search perfumes</label>
    <div class="field__control">
      <input id="nav-search" class="nav-mobile__input js-suggest-input" type="search" name="q" placeholder="Search perfumes" autocomplete="off" data-suggest-target="suggest-nav" role="combobox" aria-autocomplete="list" aria-controls="suggest-nav">
      <div class="suggest" id="suggest-nav" role="listbox" aria-label="Suggestions"></div>
    </div>
  </form>
  <ul class="nav-mobile__list">
<?php foreach ($navLinks as [$navPath, $navLabel]): ?>
<?php $isCurrent = $currentPath === $navPath || ($navPath !== '/shop' && str_starts_with($currentPath, $navPath . '/')); ?>
    <li class="nav-mobile__item">
      <a class="nav-mobile__link" href="<?= e(url($navPath)) ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>><?= e($navLabel) ?></a>
<?php if ($navPath === '/collections' && $collections !== []): ?>
      <ul class="nav-mobile__sub">
<?php foreach ($collections as $subCollection): ?>
        <li><a class="nav-mobile__sublink" href="<?= e(url('/collections/' . $subCollection['slug'])) ?>"><?= e((string) $subCollection['name']) ?></a></li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </li>
<?php endforeach; ?>
  </ul>
  <ul class="nav-mobile__list nav-mobile__list--secondary">
<?php foreach ($secondaryLinks as [$navPath, $navLabel]): ?>
    <li class="nav-mobile__item"><a class="nav-mobile__link" href="<?= e(url($navPath)) ?>"<?= $currentPath === $navPath ? ' aria-current="page"' : '' ?>><?= e($navLabel) ?></a></li>
<?php endforeach; ?>
<?php if ($whatsappDigits !== ''): ?>
    <li class="nav-mobile__item"><a class="nav-mobile__link nav-mobile__link--whatsapp" href="<?= e('https://wa.me/' . $whatsappDigits) ?>" target="_blank" rel="noopener"><?php partial('icon.php', ['name' => 'whatsapp', 'size' => 20]); ?> <?= e('+' . $whatsappDigits) ?></a></li>
<?php endif; ?>
  </ul>
</nav>
