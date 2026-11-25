<?php
defined('SKYFR') || exit;
$fm = static fn (int $paisa): string => finance_money($paisa);
$money = static fn (string $amount): string => money($amount);
$spent = $totals['operating'] + $totals['stock'];
$billsTotal = array_sum(array_map(static fn (array $b): int => money_paisa((string) $b['amount']), $bills));
$overdue = count(array_filter($bills, static fn (array $b): bool => !empty($b['due_date']) && $b['due_date'] < $today));
$maxRow = max(1, ...array_map(static fn (array $r): int => $r['amount'], $totals['expense_rows'] ?: [['amount' => 1]]));
$rows = [];
foreach ($entries as $row) {
    $id = (int) $row['id'];
    $isIncome = $row['entry_type'] === 'income';
    $label = finance_category_label((string) $row['entry_type'], (string) $row['category']);
    $amount = ($isIncome ? '+ ' : '') . $money((string) $row['amount']);
    $statusHtml = $row['status'] === 'due'
        ? '<span class="adm-badge adm-badge--sm adm-badge--red">' . (!empty($row['due_date']) ? 'Due ' . e(date('j M', strtotime((string) $row['due_date']))) : 'To pay') . '</span>'
        : '<span class="adm-badge adm-badge--sm adm-badge--' . ($isIncome ? 'blue' : 'green') . '">' . ($isIncome ? 'Received' : 'Paid') . '</span>';
    $actions = [['label' => 'Edit', 'href' => '/admin/expenses/' . $id . '/edit']];
    if ($row['status'] === 'due') {
        $actions[] = ['label' => 'Mark paid', 'post' => '/admin/expenses/' . $id . '/paid', 'fields' => ['return' => $returnTo]];
    }
    $actions[] = ['label' => 'Remove', 'variant' => 'danger', 'post' => '/admin/expenses/' . $id . '/delete', 'fields' => ['return' => $returnTo], 'confirm' => "Remove this entry?\n" . $row['title'] . ' — ' . money((string) $row['amount']) . "\nIt will no longer count in statements.", 'confirm_label' => 'Remove'];
    $details = '<span class="adm-media__title">' . e((string) $row['title']) . '</span><span class="adm-media__meta">' . e($label) . (!empty($row['vendor']) ? ' · ' . e((string) $row['vendor']) : '') . (!empty($row['reference']) ? ' · ' . e((string) $row['reference']) : '') . '</span>';
    $rows[] = [
        'id' => $id,
        'cells' => [
            'date' => e(date('j M Y', strtotime((string) $row['entry_date']))),
            'details' => '<div class="adm-media__body">' . $details . '</div>',
            'paid' => e(FINANCE_PAID_FROM[(string) ($row['paid_from'] ?? '')] ?? '—'),
            'status' => $statusHtml,
            'amount' => '<strong class="adm-money' . ($isIncome ? ' adm-money--in' : '') . '">' . e($amount) . '</strong>',
        ],
        'card' => [
            'title' => e((string) $row['title']),
            'chip' => $statusHtml,
            'sub' => e(date('j M Y', strtotime((string) $row['entry_date']))) . ' · ' . e($label),
            'money' => '<strong class="adm-money' . ($isIncome ? ' adm-money--in' : '') . '">' . e($amount) . '</strong>',
            'inline_actions' => 1,
        ],
        'actions' => $actions,
    ];
}
$filters = ['' => 'All', 'expense' => 'Expenses', 'income' => 'Money in', 'due' => 'To pay'];
?>
<?php partial_admin('page-header.php', [
    'title' => 'Expenses',
    'subtitle' => 'Everything the business spends and any money that comes in besides shop sales. These figures feed Statements.',
    'actions' => [
        ['label' => 'Add expense', 'href' => '/admin/expenses/new', 'variant' => 'gold'],
        ['label' => 'Add money in', 'href' => '/admin/expenses/new?type=income', 'variant' => 'ghost'],
        ['label' => 'Statements', 'href' => dashboard_url($period['query'], '/admin/statements'), 'variant' => 'ghost'],
    ],
]); ?>
<?php partial_admin('period-picker.php', ['picker' => $picker]); ?>
<div class="adm-grid">
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Spent</span><span class="adm-kpi__value"><?= e($fm($spent)) ?></span><p class="adm-delta adm-delta--flat"><span><?= e($period['label']) ?></span></p></div>
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Running costs</span><span class="adm-kpi__value"><?= e($fm($totals['operating'])) ?></span><p class="adm-delta adm-delta--flat"><span>Taken off profit</span></p></div>
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Stock bought</span><span class="adm-kpi__value"><?= e($fm($totals['stock'])) ?></span><p class="adm-delta adm-delta--flat"><span>Oils, bottles and packaging</span></p></div>
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Money in</span><span class="adm-kpi__value"><?= e($fm($totals['income'])) ?></span><p class="adm-delta adm-delta--flat"><span>Besides shop sales</span></p></div>
</div>
<div class="adm-duo">
  <section class="adm-card">
    <div class="adm-card__head"><h2 class="adm-card__title">Bills to pay</h2><span class="adm-muted"><?= e($fm($billsTotal)) ?><?= $overdue > 0 ? ' · ' . e($overdue . ' overdue') : '' ?></span></div>
<?php if ($bills === []): ?>
    <p class="adm-muted">Nothing waiting to be paid. Add an expense as "Still to pay" to keep track of upcoming bills.</p>
<?php else: ?>
    <ul class="adm-list">
<?php foreach ($bills as $bill): $late = !empty($bill['due_date']) && $bill['due_date'] < $today; ?>
      <li><span><a href="<?= e(url('/admin/expenses/' . (int) $bill['id'] . '/edit')) ?>"><?= e((string) $bill['title']) ?></a><span class="adm-media__meta<?= $late ? ' adm-stock--low' : '' ?>"><?= !empty($bill['due_date']) ? ($late ? 'Overdue since ' : 'Due ') . e(date('j M Y', strtotime((string) $bill['due_date']))) : 'No due date' ?><?= !empty($bill['vendor']) ? ' · ' . e((string) $bill['vendor']) : '' ?></span></span><span class="adm-cluster"><strong class="adm-money"><?= e(money((string) $bill['amount'])) ?></strong><?php partial_admin('button.php', ['label' => 'Mark paid', 'variant' => 'ghost', 'size' => 'sm', 'post' => '/admin/expenses/' . (int) $bill['id'] . '/paid', 'fields' => ['return' => $returnTo]]); ?></span></li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </section>
  <section class="adm-card">
    <div class="adm-card__head"><h2 class="adm-card__title">Where the money went</h2><span class="adm-muted"><?= e($period['label']) ?></span></div>
<?php if ($totals['expense_rows'] === []): ?>
    <p class="adm-muted">No expenses in this period yet.</p>
<?php else: ?>
    <ul class="adm-hbars">
<?php foreach ($totals['expense_rows'] as $row): ?>
      <li><span class="adm-hbars__label"><?= e($row['label']) ?><?= $row['stock'] ? ' <span class="adm-note">stock</span>' : '' ?></span><span class="adm-hbars__track"><span class="adm-hbars__bar" style="width: <?= e((string) round($row['amount'] / $maxRow * 100, 1)) ?>%"></span></span><span class="adm-hbars__value"><?= e($fm($row['amount'])) ?></span></li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </section>
</div>
<nav class="adm-rail" aria-label="Show">
<?php foreach ($filters as $key => $label): ?>
  <a class="adm-chip<?= $filter === $key ? ' is-active' : '' ?>" href="<?= e(url(dashboard_url($period['query'] + ($key === '' ? [] : ['show' => $key]), '/admin/expenses'))) ?>"><?= e($label) ?></a>
<?php endforeach; ?>
</nav>
<?php partial_admin('table.php', [
    'caption' => 'Expenses and money in',
    'list_id' => 'expenses-list',
    'columns' => [
        ['key' => 'date', 'label' => 'Date'],
        ['key' => 'details', 'label' => 'What for'],
        ['key' => 'paid', 'label' => 'Paid with'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'amount', 'label' => 'Amount', 'align' => 'right', 'class' => 'adm-table__td--money'],
    ],
    'rows' => $rows,
    'empty' => ['title' => 'Nothing recorded for ' . $period['label'], 'text' => 'Add what you spend — stock, ads, courier, salaries — and the profit in Statements becomes your real profit.'],
]); ?>
