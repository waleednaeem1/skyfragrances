<?php
defined('SKYFR') || exit;
$columns = is_array($columns ?? null) ? $columns : [];
$rows = is_array($rows ?? null) ? $rows : [];
$bulk = is_array($bulk ?? null) ? $bulk : null;
$idKey = (string) ($id_key ?? 'id');
$caption = (string) ($caption ?? 'Rows');
$sortParam = (string) ($sort['param'] ?? 'sort');
$sortCurrent = (string) ($sort['current'] ?? '');
$listId = (string) ($list_id ?? 'list-' . substr(md5($caption), 0, 6));
$sortLink = static function (array $column) use ($sortParam, $sortCurrent): array {
    $key = (string) $column['sort'];
    if (!empty($column['sort_plain'])) {
        $next = $key;
        $active = $sortCurrent === $key;
        $dir = '';
    } else {
        $active = str_starts_with($sortCurrent, $key . '_');
        $dir = $active ? substr($sortCurrent, strlen($key) + 1) : '';
        $next = $key . '_' . ($active && $dir === 'desc' ? 'asc' : 'desc');
    }
    $q = $_GET;
    unset($q['page']);
    $q[$sortParam] = $next;
    return ['href' => url(request_path()) . '?' . http_build_query($q), 'active' => $active, 'dir' => $dir];
};
$renderActions = static function (array $actions, string $rowId, string $variantDefault) {
    foreach ($actions as $action) {
        partial_admin('button.php', $action + ['variant' => $variantDefault, 'size' => 'sm']);
    }
};
?>
<div class="adm-data" data-list id="<?= e($listId) ?>">
<?php if ($rows === []): ?>
<?php partial_admin('empty.php', is_array($empty ?? null) ? $empty : ['title' => 'Nothing to show', 'text' => 'Try clearing the filters.']); ?>
<?php else: ?>
<?php if ($bulk !== null): ?>
<form method="post" action="<?= e(url((string) $bulk['action'])) ?>" class="adm-data__bulk" data-bulk-form>
  <?= csrf_field() ?>
<?php foreach ((array) ($bulk['fields'] ?? []) as $bulkField => $bulkValue): ?>
  <input type="hidden" name="<?= e((string) $bulkField) ?>" value="<?= e((string) $bulkValue) ?>">
<?php endforeach; ?>
  <div class="adm-data__toolbar">
    <button type="button" class="adm-btn adm-btn--text adm-btn--sm" data-select-toggle aria-pressed="false" aria-controls="<?= e($listId) ?>">Select</button>
    <label class="adm-check adm-data__selectall" data-select-all-wrap hidden><input type="checkbox" class="adm-check__input" data-select-all><span class="adm-check__box" aria-hidden="true"></span><span class="adm-check__label">All on this page</span></label>
  </div>
<?php endif; ?>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <caption class="u-sr-only"><?= e($caption) ?></caption>
      <thead>
        <tr>
<?php if ($bulk !== null): ?>
          <th scope="col" class="adm-table__th adm-table__th--check"><span class="u-sr-only">Select</span></th>
<?php endif; ?>
<?php foreach ($columns as $column): ?>
<?php $th = 'adm-table__th' . (($column['align'] ?? '') === 'right' ? ' adm-table__th--right' : '') . (!empty($column['class']) ? ' ' . $column['class'] : ''); ?>
<?php if (!empty($column['sort'])): $s = $sortLink($column); ?>
          <th scope="col" class="<?= e($th) ?>"<?= $s['active'] ? ' aria-sort="' . ($s['dir'] === 'asc' ? 'ascending' : 'descending') . '"' : '' ?>><a class="adm-table__sort<?= $s['active'] ? ' is-active' : '' ?>" href="<?= e($s['href']) ?>"><?= e((string) $column['label']) ?><span class="adm-table__sort-mark" aria-hidden="true"><?= $s['active'] ? ($s['dir'] === 'asc' ? ' &uarr;' : ' &darr;') : '' ?></span></a></th>
<?php else: ?>
          <th scope="col" class="<?= e($th) ?>"><?= e((string) $column['label']) ?></th>
<?php endif; ?>
<?php endforeach; ?>
<?php if (!empty(array_filter(array_column($rows, 'actions')))): ?>
          <th scope="col" class="adm-table__th adm-table__th--actions"><span class="u-sr-only">Actions</span></th>
<?php endif; ?>
        </tr>
      </thead>
      <tbody>
<?php foreach ($rows as $row): $rowId = (string) ($row[$idKey] ?? ''); $href = (string) ($row['href'] ?? ''); $actions = is_array($row['actions'] ?? null) ? $row['actions'] : []; ?>
        <tr class="adm-table__row<?= !empty($row['class']) ? ' ' . e((string) $row['class']) : '' ?>"<?= $href !== '' ? ' data-href="' . e(url($href)) . '"' : '' ?>>
<?php if ($bulk !== null): ?>
          <td class="adm-table__td adm-table__td--check"><label class="adm-check adm-check--bare"><input type="checkbox" class="adm-check__input" name="ids[]" value="<?= e($rowId) ?>" data-select-row><span class="adm-check__box" aria-hidden="true"></span><span class="u-sr-only">Select row</span></label></td>
<?php endif; ?>
<?php foreach ($columns as $column): $key = (string) $column['key']; ?>
          <td class="adm-table__td<?= ($column['align'] ?? '') === 'right' ? ' adm-table__td--right' : '' ?><?= !empty($column['class']) ? ' ' . e((string) $column['class']) : '' ?>"><?= $row['cells'][$key] ?? '' ?></td>
<?php endforeach; ?>
<?php if ($actions !== []): ?>
          <td class="adm-table__td adm-table__td--actions"><div class="adm-table__actions"><?php $renderActions($actions, $rowId, 'text'); ?></div></td>
<?php endif; ?>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <ul class="adm-cards">
<?php foreach ($rows as $row): $rowId = (string) ($row[$idKey] ?? ''); $href = (string) ($row['href'] ?? ''); $card = is_array($row['card'] ?? null) ? $row['card'] : []; $actions = is_array($row['actions'] ?? null) ? $row['actions'] : []; $menuId = $listId . '-menu-' . preg_replace('/[^a-z0-9_-]/i', '', $rowId); ?>
    <li class="adm-rowcard<?= !empty($row['class']) ? ' ' . e((string) $row['class']) : '' ?>"<?= $href !== '' ? ' data-href="' . e(url($href)) . '"' : '' ?>>
<?php if ($bulk !== null): ?>
      <label class="adm-check adm-check--bare adm-rowcard__check"><input type="checkbox" class="adm-check__input" name="ids[]" value="<?= e($rowId) ?>" data-select-row><span class="adm-check__box" aria-hidden="true"></span><span class="u-sr-only">Select</span></label>
<?php endif; ?>
      <div class="adm-rowcard__body">
        <div class="adm-rowcard__head">
          <div class="adm-rowcard__title"><?php if ($href !== ''): ?><a class="adm-rowcard__link" href="<?= e(url($href)) ?>"><?= $card['title'] ?? '' ?></a><?php else: ?><?= $card['title'] ?? '' ?><?php endif; ?></div>
<?php if (!empty($card['chip'])): ?>
          <div class="adm-rowcard__chip"><?= $card['chip'] ?></div>
<?php endif; ?>
        </div>
<?php if (!empty($card['sub'])): ?>
        <p class="adm-rowcard__sub"><?= $card['sub'] ?></p>
<?php endif; ?>
<?php foreach (array_slice((array) ($card['lines'] ?? []), 0, 3) as $line): ?>
        <p class="adm-rowcard__line"><?php if (!empty($line['label'])): ?><span class="adm-rowcard__label"><?= e((string) $line['label']) ?>:</span> <?php endif; ?><?= $line['html'] ?? '' ?></p>
<?php endforeach; ?>
<?php if (!empty($card['money']) || !empty($card['aside'])): ?>
        <div class="adm-rowcard__figures"><span class="adm-rowcard__money"><?= $card['money'] ?? '' ?></span><span class="adm-rowcard__aside"><?= $card['aside'] ?? '' ?></span></div>
<?php endif; ?>
      </div>
<?php if ($actions !== []): ?>
      <div class="adm-rowcard__actions">
<?php $renderActions(array_slice($actions, 0, (int) ($card['inline_actions'] ?? 2)), $rowId, 'ghost'); ?>
<?php if (count($actions) > (int) ($card['inline_actions'] ?? 2)): ?>
        <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm adm-rowcard__more" data-sheet-open="<?= e($menuId) ?>" aria-controls="<?= e($menuId) ?>" aria-expanded="false" aria-label="More actions">&#8942;</button>
<?php endif; ?>
      </div>
<?php if (count($actions) > (int) ($card['inline_actions'] ?? 2)): ?>
      <div class="adm-sheet" id="<?= e($menuId) ?>" hidden data-sheet>
        <div class="adm-sheet__panel adm-sheet__panel--menu" role="dialog" aria-modal="true" aria-label="Actions" tabindex="-1">
          <div class="adm-sheet__handle" aria-hidden="true"></div>
          <p class="adm-sheet__title"><?= strip_tags((string) ($card['title'] ?? '')) ?></p>
<?php foreach ($actions as $action): ?>
<?php partial_admin('button.php', $action + ['variant' => 'sheet', 'block' => true]); ?>
<?php endforeach; ?>
          <button type="button" class="adm-sheet__item adm-sheet__item--close" data-sheet-close>Close</button>
        </div>
      </div>
<?php endif; ?>
<?php endif; ?>
    </li>
<?php endforeach; ?>
  </ul>
<?php if ($bulk !== null): ?>
<?php partial_admin('action-bar.php', ['mode' => 'bulk', 'buttons' => (array) ($bulk['buttons'] ?? [])]); ?>
</form>
<?php endif; ?>
<?php endif; ?>
</div>
