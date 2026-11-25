<?php
defined('SKYFR') || exit;
$s = $statement['sales'];
$p = $statement['previous'];
$x = $statement['expenses'];
$cash = $statement['cash'];
$fm = static fn (int $paisa): string => finance_money($paisa);
$tile = static function (string $label, string $value, ?array $delta = null, string $note = '', bool $alert = false): void {
    echo '<div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">' . e($label) . '</span><span class="adm-kpi__value' . ($alert ? ' adm-kpi__value--alert' : '') . '">' . e($value) . '</span>';
    if ($delta !== null || $note !== '') {
        echo '<p class="adm-delta adm-delta--' . e($delta['tone'] ?? 'flat') . '">' . ($delta !== null ? '<span class="adm-delta__pill">' . e($delta['text']) . '</span> ' : '') . '<span>' . e($note) . '</span></p>';
    }
    echo '</div>';
};
$vs = ' vs ' . $period['compare_label'];
$maxExpense = max(1, ...array_map(static fn (array $r): int => $r['amount'], $x['expense_rows'] ?: [['amount' => 1]]));
?>
<?php partial_admin('page-header.php', [
    'title' => 'Statements',
    'subtitle' => 'Sales, costs, expenses and profit for any period. Sales count confirmed, packing, shipped and delivered orders by order date.',
    'actions' => [
        ['label' => 'Download Excel', 'href' => $exportUrl, 'variant' => 'gold'],
        ['label' => 'PDF', 'href' => $printUrl, 'external' => true, 'variant' => 'ghost'],
    ],
]); ?>
<?php partial_admin('period-picker.php', ['picker' => $picker]); ?>
<?php if ($s['uncosted'] > 0): ?>
<div class="adm-banner adm-banner--warn" role="status"><?= e($s['uncosted'] . ' bottle' . ($s['uncosted'] === 1 ? '' : 's')) ?> sold in this period <?= $s['uncosted'] === 1 ? 'has' : 'have' ?> no cost entered, so profit shows higher than it really is. <a href="<?= e(url('/admin/products')) ?>">Add "Cost per bottle" to each size in Products</a>.</div>
<?php elseif ($s['estimated'] > 0): ?>
<div class="adm-banner adm-banner--info" role="status"><?= e($s['estimated'] . ' bottle' . ($s['estimated'] === 1 ? '' : 's')) ?> were sold before costs were entered; their profit uses today's cost per bottle.</div>
<?php endif; ?>
<div class="adm-grid">
<?php $tile('Net sales', $fm($s['net']), dashboard_delta($s['net'], $p['net']), $vs); ?>
<?php $tile('Gross profit', $fm($s['profit']), dashboard_delta($s['profit'], $p['profit']), 'Margin ' . finance_percent($s['margin'])); ?>
<?php $tile('Net profit', $fm($statement['net_profit']), dashboard_delta($statement['net_profit'], $statement['previous_net_profit']), 'After ' . $fm($x['operating'] + $statement['commission']) . ' expenses' . ($statement['commission'] > 0 ? ' and shares' : ''), $statement['net_profit'] < 0); ?>
<?php $tile('Orders', number_format($s['orders']), dashboard_delta($s['orders'], $p['orders']), number_format($s['bottles']) . ' bottles'); ?>
<?php $tile('Collected', $fm($cash['collected']), null, 'Money received in this period'); ?>
<?php $tile('To collect', $fm($cash['outstanding']), null, 'Still due on these orders', $cash['outstanding'] > 0); ?>
<?php $tile('Spent', $fm($cash['spent']), null, 'Expenses, stock and shares paid'); ?>
<?php $tile('Bills to pay', $fm($cash['bills_due']), null, $cash['bills_due_count'] . ' open bill' . ($cash['bills_due_count'] === 1 ? '' : 's'), $cash['bills_due'] > 0); ?>
</div>
<div class="adm-duo">
  <div class="adm-card">
    <div class="adm-card__head"><h2 class="adm-card__title">Net sales</h2><span class="adm-muted"><?= e($fm($s['net'])) ?></span></div>
<?php partial_admin('column-chart.php', ['chart' => $salesChart]); ?>
  </div>
  <div class="adm-card">
    <div class="adm-card__head"><h2 class="adm-card__title">Net profit</h2><span class="adm-muted"><?= e($fm($statement['net_profit'])) ?></span></div>
<?php partial_admin('column-chart.php', ['chart' => $profitChart]); ?>
  </div>
</div>
<div class="adm-duo">
  <section class="adm-card">
    <div class="adm-card__head"><h2 class="adm-card__title">Income statement</h2><span class="adm-muted"><?= e($period['label']) ?></span></div>
    <table class="adm-pl">
      <tr><td>Gross sales <span class="adm-note">perfumes at the prices charged</span></td><td><?= e($fm($s['gross'])) ?></td></tr>
      <tr><td>Less discounts</td><td>&minus; <?= e($fm($s['discounts'])) ?></td></tr>
      <tr class="adm-pl__total"><td>Net sales</td><td><?= e($fm($s['net'])) ?></td></tr>
      <tr><td>Less cost of goods sold</td><td>&minus; <?= e($fm($s['cogs'])) ?></td></tr>
      <tr class="adm-pl__total"><td>Gross profit <span class="adm-note"><?= e(finance_percent($s['margin'])) ?> margin</span></td><td><?= e($fm($s['profit'])) ?></td></tr>
      <tr><td>Less operating expenses</td><td>&minus; <?= e($fm($x['operating'])) ?></td></tr>
<?php if ($statement['commission'] > 0): ?>
      <tr><td>Less sales commission <span class="adm-note">salespeople's share of profit</span></td><td>&minus; <?= e($fm($statement['commission'])) ?></td></tr>
<?php endif; ?>
      <tr><td>Add other income</td><td><?= e($fm($x['income'])) ?></td></tr>
      <tr class="adm-pl__grand<?= $statement['net_profit'] < 0 ? ' is-loss' : '' ?>"><td><?= $statement['net_profit'] < 0 ? 'Net loss' : 'Net profit' ?></td><td><?= e($fm($statement['net_profit'])) ?></td></tr>
      <tr class="adm-pl__aside"><td>Delivery and COD fees charged</td><td><?= e($fm($s['delivery'])) ?></td></tr>
      <tr class="adm-pl__aside"><td>Total billed to customers</td><td><?= e($fm($s['revenue'])) ?></td></tr>
      <tr class="adm-pl__aside"><td>Stock bought <span class="adm-note">already counted through cost per bottle</span></td><td><?= e($fm($x['stock'])) ?></td></tr>
      <tr class="adm-pl__aside"><td>Cancelled orders</td><td><?= e($statement['cancelled']['count'] . ' · ' . $fm($statement['cancelled']['value'])) ?></td></tr>
      <tr class="adm-pl__aside"><td>Awaiting confirmation</td><td><?= e($statement['pending']['count'] . ' · ' . $fm($statement['pending']['value'])) ?></td></tr>
    </table>
  </section>
  <section class="adm-card">
    <div class="adm-card__head"><h2 class="adm-card__title">Expenses by category</h2><a class="adm-btn adm-btn--text adm-btn--sm" href="<?= e(url(dashboard_url($period['query'], '/admin/expenses'))) ?>">Open expenses</a></div>
<?php if ($x['expense_rows'] === [] && $x['income_rows'] === []): ?>
    <p class="adm-muted">No expenses recorded for this period. <a href="<?= e(url('/admin/expenses/new')) ?>">Add an expense</a>.</p>
<?php else: ?>
    <ul class="adm-hbars">
<?php foreach ($x['expense_rows'] as $row): ?>
      <li><span class="adm-hbars__label"><?= e($row['label']) ?><?= $row['stock'] ? ' <span class="adm-note">stock</span>' : '' ?></span><span class="adm-hbars__track"><span class="adm-hbars__bar" style="width: <?= e((string) round($row['amount'] / $maxExpense * 100, 1)) ?>%"></span></span><span class="adm-hbars__value"><?= e($fm($row['amount'])) ?></span></li>
<?php endforeach; ?>
<?php foreach ($x['income_rows'] as $row): ?>
      <li><span class="adm-hbars__label"><?= e($row['label']) ?> <span class="adm-note">income</span></span><span class="adm-hbars__track"></span><span class="adm-hbars__value adm-hbars__value--in">+ <?= e($fm($row['amount'])) ?></span></li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </section>
</div>
<section class="adm-card adm-card--flush">
  <div class="adm-card__head"><h2 class="adm-card__title">By <?= e($period['unit']) ?></h2></div>
  <div class="adm-table-wrap adm-table-wrap--always">
    <table class="adm-table">
      <thead><tr><th class="adm-table__th"><?= e(ucfirst($period['unit'])) ?></th><th class="adm-table__th adm-table__th--right">Orders</th><th class="adm-table__th adm-table__th--right">Net sales</th><th class="adm-table__th adm-table__th--right">Cost of goods</th><th class="adm-table__th adm-table__th--right">Gross profit</th><th class="adm-table__th adm-table__th--right">Expenses</th><th class="adm-table__th adm-table__th--right">Net profit</th></tr></thead>
      <tbody>
<?php foreach ($rows as $row): if ($row['orders'] === 0 && $row['expenses'] === 0 && $period['unit'] !== 'month') { continue; } ?>
        <tr class="adm-table__row"><td class="adm-table__td"><?= $row['href'] !== null ? '<a class="adm-link" href="' . e(url($row['href'])) . '">' . e($row['label']) . '</a>' : e($row['label']) ?></td><td class="adm-table__td adm-table__td--right"><?= e((string) $row['orders']) ?></td><td class="adm-table__td adm-table__td--money"><?= e($fm($row['net'])) ?></td><td class="adm-table__td adm-table__td--money"><?= e($fm($row['cogs'])) ?></td><td class="adm-table__td adm-table__td--money"><?= e($fm($row['profit'])) ?></td><td class="adm-table__td adm-table__td--money"><?= e($fm($row['expenses'])) ?></td><td class="adm-table__td adm-table__td--money<?= $row['net_profit'] < 0 ? ' adm-stock--low' : '' ?>"><strong><?= e($fm($row['net_profit'])) ?></strong></td></tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<section class="adm-card adm-card--flush">
  <div class="adm-card__head"><h2 class="adm-card__title">Perfumes</h2><span class="adm-muted"><?= e(count($statement['products']) . ' sold') ?></span></div>
  <div class="adm-table-wrap adm-table-wrap--always">
    <table class="adm-table">
      <thead><tr><th class="adm-table__th">Perfume</th><th class="adm-table__th">Size</th><th class="adm-table__th adm-table__th--right">Bottles</th><th class="adm-table__th adm-table__th--right">Net sales</th><th class="adm-table__th adm-table__th--right">Cost</th><th class="adm-table__th adm-table__th--right">Profit</th><th class="adm-table__th adm-table__th--right">Margin</th></tr></thead>
      <tbody>
<?php foreach ($statement['products'] as $row): ?>
        <tr class="adm-table__row"><td class="adm-table__td"><?= e($row['name']) ?></td><td class="adm-table__td"><?= e($row['size']) ?></td><td class="adm-table__td adm-table__td--right"><?= e((string) $row['bottles']) ?></td><td class="adm-table__td adm-table__td--money"><?= e($fm($row['net'])) ?></td><td class="adm-table__td adm-table__td--money"><?= $row['uncosted'] > 0 ? '<span class="adm-note">no cost</span>' : e($fm($row['cogs'])) ?></td><td class="adm-table__td adm-table__td--money"><strong><?= e($fm($row['profit'])) ?></strong></td><td class="adm-table__td adm-table__td--right"><?= e(finance_percent($row['margin'])) ?></td></tr>
<?php endforeach; ?>
<?php if ($statement['products'] === []): ?>
        <tr><td class="adm-table__td adm-muted" colspan="7">No perfumes sold in this period.</td></tr>
<?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<div class="adm-duo">
  <section class="adm-card adm-card--flush">
    <div class="adm-card__head"><h2 class="adm-card__title">Payment methods</h2></div>
    <div class="adm-table-wrap adm-table-wrap--always">
      <table class="adm-table">
        <thead><tr><th class="adm-table__th">Method</th><th class="adm-table__th adm-table__th--right">Orders</th><th class="adm-table__th adm-table__th--right">Billed</th><th class="adm-table__th adm-table__th--right">Paid</th><th class="adm-table__th adm-table__th--right">Due</th></tr></thead>
        <tbody>
<?php foreach ($statement['methods'] as $row): ?>
          <tr class="adm-table__row"><td class="adm-table__td"><?= e($row['label']) ?></td><td class="adm-table__td adm-table__td--right"><?= e((string) $row['orders']) ?></td><td class="adm-table__td adm-table__td--money"><?= e($fm($row['revenue'])) ?></td><td class="adm-table__td adm-table__td--money"><?= e($fm($row['paid'])) ?></td><td class="adm-table__td adm-table__td--money<?= $row['due'] > 0 ? ' adm-stock--low' : '' ?>"><?= e($fm($row['due'])) ?></td></tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <section class="adm-card adm-card--flush">
    <div class="adm-card__head"><h2 class="adm-card__title">Top cities</h2></div>
    <div class="adm-table-wrap adm-table-wrap--always">
      <table class="adm-table">
        <thead><tr><th class="adm-table__th">City</th><th class="adm-table__th adm-table__th--right">Orders</th><th class="adm-table__th adm-table__th--right">Billed</th></tr></thead>
        <tbody>
<?php foreach ($statement['cities'] as $row): ?>
          <tr class="adm-table__row"><td class="adm-table__td"><?= e($row['city']) ?></td><td class="adm-table__td adm-table__td--right"><?= e((string) $row['orders']) ?></td><td class="adm-table__td adm-table__td--money"><?= e($fm($row['revenue'])) ?></td></tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
