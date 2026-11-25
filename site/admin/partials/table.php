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
$rowForms = [];
$renderAction = static function (array $action) use ($bulk, &$rowForms): void {
    if ($bulk !== null && !empty($action['post'])) {
        $fields = (array) ($action['fields'] ?? []);
        $formId = 'row-form-' . substr(md5((string) $action['post'] . '?' . http_build_query($fields)), 0, 12);
        $rowForms[$formId] = ['post' => (string) $action['post'], 'fields' => $fields];
        $action['attr'] = (array) ($action['attr'] ?? []) + ['form' => $formId];
        unset($action['post'], $action['fields']);
    }
    $defaults = empty($action['icon']) ? ['variant' => 'text', 'size' => 'sm'] : ['variant' => 'secondary', 'size' => 'sm', 'icon_only' => true];
    partial_admin('button.php', $action + $defaults);
};
$hasActions = !empty(array_filter(array_column($rows, 'actions')));
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
<?php partial_admin('action-bar.php', ['mode' => 'bulk', 'buttons' => (array) ($bulk['buttons'] ?? [])]); ?>
<?php endif; ?>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <caption class="u-sr-only"><?= e($caption) ?></caption>
      <thead>
        <tr>
<?php if ($bulk !== null): ?>
          <th scope="col" class="adm-table__th adm-table__th--check"><label class="adm-check adm-check--bare"><input type="checkbox" class="adm-check__input" data-select-all><span class="adm-check__box" aria-hidden="true"></span><span class="u-sr-only">Select all rows on this page</span></label></th>
<?php endif; ?>
<?php foreach ($columns as $column): ?>
<?php $th = 'adm-table__th' . (($column['align'] ?? '') === 'right' ? ' adm-table__th--right' : '') . (!empty($column['class']) ? ' ' . $column['class'] : ''); ?>
<?php if (!empty($column['sort'])): $s = $sortLink($column); ?>
          <th scope="col" class="<?= e($th) ?>"<?= $s['active'] ? ' aria-sort="' . ($s['dir'] === 'asc' ? 'ascending' : 'descending') . '"' : '' ?>><a class="adm-table__sort<?= $s['active'] ? ' is-active' : '' ?>" href="<?= e($s['href']) ?>"><?= e((string) $column['label']) ?><span class="adm-table__sort-mark" aria-hidden="true"><?= $s['active'] ? ($s['dir'] === 'asc' ? ' &uarr;' : ' &darr;') : '' ?></span></a></th>
<?php else: ?>
          <th scope="col" class="<?= e($th) ?>"><?= e((string) $column['label']) ?></th>
<?php endif; ?>
<?php endforeach; ?>
<?php if ($hasActions): ?>
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
<?php if ($hasActions): ?>
          <td class="adm-table__td adm-table__td--actions"><div class="adm-table__actions"><?php foreach ($actions as $action) { $renderAction($action); } ?></div></td>
<?php endif; ?>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php if ($bulk !== null): ?>
</form>
<?php foreach ($rowForms as $formId => $rowForm): ?>
<form id="<?= e($formId) ?>" method="post" action="<?= e(url($rowForm['post'])) ?>" hidden>
  <?= csrf_field() ?>
<?php foreach ($rowForm['fields'] as $fieldName => $fieldValue): ?>
  <input type="hidden" name="<?= e((string) $fieldName) ?>" value="<?= e((string) $fieldValue) ?>">
<?php endforeach; ?>
</form>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>
</div>
