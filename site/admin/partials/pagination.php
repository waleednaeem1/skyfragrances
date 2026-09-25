<?php
defined('SKYFR') || exit;
$page = max(1, (int) ($page ?? 1));
$per = max(1, (int) ($per ?? 20));
$total = max(0, (int) ($total ?? 0));
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);
$query = is_array($query ?? null) ? $query : $_GET;
$path = (string) ($path ?? request_path());
$noun = (string) ($noun ?? 'rows');
$link = static function (int $p) use ($query, $path): string {
    $q = $query;
    $q['page'] = $p;
    unset($q['_csrf']);
    return url($path) . '?' . http_build_query($q);
};
$from = $total === 0 ? 0 : ($page - 1) * $per + 1;
$to = min($total, $page * $per);
$window = [];
for ($p = 1; $p <= $pages; $p++) {
    if ($p === 1 || $p === $pages || abs($p - $page) <= 2) {
        $window[] = $p;
    } elseif (end($window) !== 0) {
        $window[] = 0;
    }
}
?>
<?php if ($total > 0): ?>
<nav class="adm-pager" aria-label="Pagination">
  <p class="adm-pager__info">Showing <?= e((string) $from) ?>&ndash;<?= e((string) $to) ?> of <?= e((string) $total) ?> <?= e($noun) ?></p>
<?php if ($pages > 1): ?>
  <div class="adm-pager__links">
<?php if ($page > 1): ?>
    <a class="adm-pager__link adm-pager__link--prev" href="<?= e($link($page - 1)) ?>" rel="prev">&lsaquo; Prev</a>
<?php else: ?>
    <span class="adm-pager__link adm-pager__link--prev is-disabled" aria-disabled="true">&lsaquo; Prev</span>
<?php endif; ?>
    <span class="adm-pager__pages">
<?php foreach ($window as $p): ?>
<?php if ($p === 0): ?>
      <span class="adm-pager__gap" aria-hidden="true">&hellip;</span>
<?php elseif ($p === $page): ?>
      <span class="adm-pager__num is-active" aria-current="page"><?= e((string) $p) ?></span>
<?php else: ?>
      <a class="adm-pager__num" href="<?= e($link($p)) ?>"><?= e((string) $p) ?></a>
<?php endif; ?>
<?php endforeach; ?>
    </span>
    <span class="adm-pager__compact">Page <?= e((string) $page) ?> of <?= e((string) $pages) ?></span>
<?php if ($page < $pages): ?>
    <a class="adm-pager__link adm-pager__link--next" href="<?= e($link($page + 1)) ?>" rel="next">Next &rsaquo;</a>
<?php else: ?>
    <span class="adm-pager__link adm-pager__link--next is-disabled" aria-disabled="true">Next &rsaquo;</span>
<?php endif; ?>
  </div>
<?php endif; ?>
</nav>
<?php endif; ?>
