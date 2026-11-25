<?php
defined('SKYFR') || exit;
$s = $statement['sales'];
$x = $statement['expenses'];
$cash = $statement['cash'];
$fm = static fn (int $paisa): string => finance_money($paisa);
$maxRow = max(1, ...array_map(static fn (array $r): int => max(0, $r['net']), $rows ?: [['net' => 1]]));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Statement <?= e($period['label']) ?> · <?= e($store['name']) ?></title>
<style>
@font-face { font-family: "Geist"; src: url("<?= e(asset('fonts/geist-variable.woff2')) ?>") format("woff2"); font-weight: 100 900; font-display: swap; }
@font-face { font-family: "Geist Mono"; src: url("<?= e(asset('fonts/geist-mono-variable.woff2')) ?>") format("woff2"); font-weight: 100 900; font-display: swap; }
:root { --ink: #0B0B0C; --muted: #64645E; --line: #E6E6E1; --hair: #EFEFEB; --canvas: #E9E9E4; --paper: #FFFFFF; --accent: #D4F84B; --ok: #13693A; --bad: #B42318; --bar: #7B61FF; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body { background: var(--canvas); color: var(--ink); font: 400 9.5pt/1.45 "Geist", "Helvetica Neue", Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; font-variant-numeric: lining-nums tabular-nums; }
.toolbar { position: sticky; top: 0; z-index: 2; display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; padding: 12px 20px; background: var(--ink); color: #C9C9C2; font-size: 13px; }
.toolbar a, .toolbar button { border-color: #B9DD2A; background: var(--accent); color: var(--ink); }
.toolbar button { border-color: #B9DD2A; background: var(--accent); color: var(--ink); }
.toolbar span { flex: 1 1 260px; }
.sheet { position: relative; background: var(--paper); width: 210mm; min-height: 297mm; margin: 18px auto; padding: 15mm 15mm 13mm; box-shadow: 0 12px 32px rgba(11, 11, 12, .10); }
.head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10mm; margin-bottom: 7mm; }
.brandmark { display: flex; align-items: center; gap: 3mm; }
.mark { display: flex; align-items: center; justify-content: center; flex: none; width: 10mm; height: 10mm; border-radius: 2.5mm; background: var(--ink); color: #FFFFFF; font: 600 10pt/1 "Geist", Arial, sans-serif; letter-spacing: -0.04em; }
.mark span { color: var(--accent); }
.brandname { font-size: 12pt; font-weight: 600; }
.doc { text-align: right; }
.eyebrow { font-size: 7.5pt; font-weight: 500; letter-spacing: .28em; text-transform: uppercase; color: var(--ink); margin: 0 0 1.5mm; }
.doc h1 { font-family: "Geist", Arial, sans-serif; font-weight: 500; font-size: 26pt; line-height: 1; margin: 0 0 2mm; }
.doc p { margin: 0; color: var(--muted); font-size: 8.5pt; }
.tiles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 3mm; margin-bottom: 6mm; }
.tile { border: 1px solid var(--line); border-radius: 2.5mm; padding: 3mm 3.5mm; background: #fff; }
.tile span { display: block; font-size: 6.8pt; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); }
.tile strong { display: block; margin-top: 1mm; font-family: "Geist", Arial, sans-serif; font-size: 15pt; font-weight: 600; line-height: 1.1; }
.tile.loss strong { color: var(--bad); }
h2 { font-family: "Geist", Arial, sans-serif; font-weight: 600; font-size: 13pt; margin: 6mm 0 2.5mm; }
.cols { display: grid; grid-template-columns: 1fr 1fr; gap: 7mm; }
table { width: 100%; border-collapse: collapse; }
th { text-align: left; font-size: 6.8pt; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); font-weight: 500; padding: 1.6mm 1.5mm; border-bottom: 1px solid var(--line); }
td { padding: 1.5mm 1.5mm; border-bottom: 1px solid var(--hair); }
.r { text-align: right; white-space: nowrap; }
.pl td:last-child { text-align: right; white-space: nowrap; }
.pl .total td { font-weight: 600; border-top: 1px solid var(--line); }
.pl .grand td { font-family: "Geist", Arial, sans-serif; font-size: 13pt; font-weight: 600; border-top: 1.5px solid var(--ink); border-bottom: 0; }
.pl .grand.loss td { color: var(--bad); }
.pl .aside td { color: var(--muted); font-size: 8.5pt; }
.bar { display: block; height: 2.2mm; background: var(--bar); border-radius: 1mm; min-width: .6mm; }
.note { font-size: 7.8pt; color: var(--muted); margin: 2mm 0 0; }
.foot { margin-top: 8mm; padding-top: 3mm; border-top: 1px solid var(--line); display: flex; justify-content: space-between; font-size: 7.5pt; color: var(--muted); }
@media screen and (max-width: 820px) {
  .sheet { width: auto; min-height: 0; margin: 10px; padding: 20px 16px; overflow-x: auto; }
  .tiles { grid-template-columns: repeat(2, 1fr); }
  .cols { grid-template-columns: 1fr; }
  .head { flex-direction: column; }
  .doc { text-align: left; }
}
@media print {
  @page { size: A4; margin: 0; }
  body { background: #fff; }
  .toolbar { position: sticky; top: 0; z-index: 2; display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; padding: 12px 20px; background: var(--ink); color: #C9C9C2; font-size: 13px; }
  .sheet { margin: 0; box-shadow: none; }
  tr { page-break-inside: avoid; }
}
</style>
</head>
<body data-autoprint>
<div class="toolbar">
  <a href="<?= e(url($returnUrl)) ?>">&larr; Back to statements</a>
  <span>Choose "Save as PDF" in the print window to keep or share this statement.</span>
  <button type="button" data-print>Print or save as PDF</button>
</div>
<main class="sheet">
  <header class="head">
    <div class="brandmark"><span class="mark" aria-hidden="true">S<span>F</span></span><span class="brandname"><?= e($store['name']) ?></span></div>
    <div class="doc">
      <p class="eyebrow">Financial statement</p>
      <h1><?= e($period['label']) ?></h1>
      <p>Prepared <?= e($printedAt) ?></p>
    </div>
  </header>
  <section class="tiles">
    <div class="tile"><span>Net sales</span><strong><?= e($fm($s['net'])) ?></strong></div>
    <div class="tile"><span>Gross profit</span><strong><?= e($fm($s['profit'])) ?></strong></div>
    <div class="tile<?= $statement['net_profit'] < 0 ? ' loss' : '' ?>"><span>Net profit</span><strong><?= e($fm($statement['net_profit'])) ?></strong></div>
    <div class="tile"><span>Orders · bottles</span><strong><?= e(number_format($s['orders']) . ' · ' . number_format($s['bottles'])) ?></strong></div>
  </section>
  <div class="cols">
    <section>
      <h2>Income statement</h2>
      <table class="pl">
        <tr><td>Gross sales</td><td><?= e($fm($s['gross'])) ?></td></tr>
        <tr><td>Less discounts</td><td>&minus; <?= e($fm($s['discounts'])) ?></td></tr>
        <tr class="total"><td>Net sales</td><td><?= e($fm($s['net'])) ?></td></tr>
        <tr><td>Less cost of goods sold</td><td>&minus; <?= e($fm($s['cogs'])) ?></td></tr>
        <tr class="total"><td>Gross profit · <?= e(finance_percent($s['margin'])) ?></td><td><?= e($fm($s['profit'])) ?></td></tr>
        <tr><td>Less operating expenses</td><td>&minus; <?= e($fm($x['operating'])) ?></td></tr>
<?php if ($statement['commission'] > 0): ?>
        <tr><td>Less sales commission</td><td>&minus; <?= e($fm($statement['commission'])) ?></td></tr>
<?php endif; ?>
        <tr><td>Add other income</td><td><?= e($fm($x['income'])) ?></td></tr>
        <tr class="grand<?= $statement['net_profit'] < 0 ? ' loss' : '' ?>"><td><?= $statement['net_profit'] < 0 ? 'Net loss' : 'Net profit' ?></td><td><?= e($fm($statement['net_profit'])) ?></td></tr>
        <tr class="aside"><td>Delivery and COD fees charged</td><td><?= e($fm($s['delivery'])) ?></td></tr>
        <tr class="aside"><td>Total billed to customers</td><td><?= e($fm($s['revenue'])) ?></td></tr>
        <tr class="aside"><td>Stock bought (in cost of goods)</td><td><?= e($fm($x['stock'])) ?></td></tr>
      </table>
<?php if ($s['uncosted'] > 0): ?>
      <p class="note"><?= e($s['uncosted']) ?> bottles have no cost entered, so profit is shown higher than it really is.</p>
<?php endif; ?>
    </section>
    <section>
      <h2>Cash</h2>
      <table class="pl">
        <tr><td>Money collected from customers</td><td><?= e($fm($cash['collected'])) ?></td></tr>
        <tr><td>Still to collect on these orders</td><td><?= e($fm($cash['outstanding'])) ?></td></tr>
        <tr><td>Paid out for expenses and stock</td><td><?= e($fm($cash['spent'])) ?></td></tr>
        <tr><td>Bills still to pay</td><td><?= e($fm($cash['bills_due'])) ?></td></tr>
        <tr class="aside"><td>Cancelled orders</td><td><?= e($statement['cancelled']['count'] . ' · ' . $fm($statement['cancelled']['value'])) ?></td></tr>
        <tr class="aside"><td>Awaiting confirmation</td><td><?= e($statement['pending']['count'] . ' · ' . $fm($statement['pending']['value'])) ?></td></tr>
      </table>
      <h2>Expenses by category</h2>
      <table>
<?php foreach ($x['expense_rows'] as $row): ?>
        <tr><td><?= e($row['label']) ?><?= $row['stock'] ? ' (stock)' : '' ?></td><td class="r"><?= e($fm($row['amount'])) ?></td></tr>
<?php endforeach; ?>
<?php foreach ($x['income_rows'] as $row): ?>
        <tr><td><?= e($row['label']) ?> (income)</td><td class="r">+ <?= e($fm($row['amount'])) ?></td></tr>
<?php endforeach; ?>
<?php if ($x['expense_rows'] === [] && $x['income_rows'] === []): ?>
        <tr><td colspan="2">No expenses recorded.</td></tr>
<?php endif; ?>
      </table>
    </section>
  </div>
  <h2>By <?= e($period['unit']) ?></h2>
  <table>
    <tr><th><?= e(ucfirst($period['unit'])) ?></th><th class="r">Orders</th><th></th><th class="r">Net sales</th><th class="r">Cost</th><th class="r">Expenses</th><th class="r">Net profit</th></tr>
<?php foreach ($rows as $row): if ($row['orders'] === 0 && $row['expenses'] === 0) { continue; } ?>
    <tr><td><?= e($row['label']) ?></td><td class="r"><?= e((string) $row['orders']) ?></td><td style="width:26%"><span class="bar" style="width: <?= e((string) round(max(0, $row['net']) / $maxRow * 100, 1)) ?>%"></span></td><td class="r"><?= e($fm($row['net'])) ?></td><td class="r"><?= e($fm($row['cogs'])) ?></td><td class="r"><?= e($fm($row['expenses'])) ?></td><td class="r"><strong><?= e($fm($row['net_profit'])) ?></strong></td></tr>
<?php endforeach; ?>
  </table>
  <h2>Perfumes</h2>
  <table>
    <tr><th>Perfume</th><th>Size</th><th class="r">Bottles</th><th class="r">Net sales</th><th class="r">Cost</th><th class="r">Profit</th><th class="r">Margin</th></tr>
<?php foreach ($statement['products'] as $row): ?>
    <tr><td><?= e($row['name']) ?></td><td><?= e($row['size']) ?></td><td class="r"><?= e((string) $row['bottles']) ?></td><td class="r"><?= e($fm($row['net'])) ?></td><td class="r"><?= $row['uncosted'] > 0 ? 'no cost' : e($fm($row['cogs'])) ?></td><td class="r"><?= e($fm($row['profit'])) ?></td><td class="r"><?= e(finance_percent($row['margin'])) ?></td></tr>
<?php endforeach; ?>
  </table>
  <div class="cols">
    <section>
      <h2>Payment methods</h2>
      <table>
        <tr><th>Method</th><th class="r">Orders</th><th class="r">Billed</th><th class="r">Due</th></tr>
<?php foreach ($statement['methods'] as $row): ?>
        <tr><td><?= e($row['label']) ?></td><td class="r"><?= e((string) $row['orders']) ?></td><td class="r"><?= e($fm($row['revenue'])) ?></td><td class="r"><?= e($fm($row['due'])) ?></td></tr>
<?php endforeach; ?>
      </table>
    </section>
    <section>
      <h2>Top cities</h2>
      <table>
        <tr><th>City</th><th class="r">Orders</th><th class="r">Billed</th></tr>
<?php foreach ($statement['cities'] as $row): ?>
        <tr><td><?= e($row['city']) ?></td><td class="r"><?= e((string) $row['orders']) ?></td><td class="r"><?= e($fm($row['revenue'])) ?></td></tr>
<?php endforeach; ?>
      </table>
    </section>
  </div>
  <footer class="foot"><span><?= e($store['name']) ?> · <?= e($store['site']) ?></span><span>Sales count confirmed, packing, shipped and delivered orders by order date.</span></footer>
</main>
<script src="<?= e(asset('js/admin-print.js')) ?>" defer></script>
</body>
</html>
