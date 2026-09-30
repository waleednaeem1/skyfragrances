<?php
defined('SKYFR') || exit;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Packing slip<?= count($payloads) > 1 ? 's (' . count($payloads) . ')' : ' ' . e((string) $payloads[0]['order']['order_number']) ?> · Sky Fragrances</title>
<style>
:root { --ink: #0A0A0A; --line: #D9D2C5; --muted: #6B6257; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #F5F0E8; color: var(--ink); font: 12pt/1.4 Jost, "Helvetica Neue", Arial, sans-serif; }
.toolbar { display: flex; gap: 8px; justify-content: space-between; align-items: center; padding: 10px 16px; background: var(--ink); color: #F5F0E8; font-size: 14px; }
.toolbar a, .toolbar button { color: #D4B084; background: none; border: 1px solid #D4B084; border-radius: 6px; padding: 8px 14px; font: inherit; text-decoration: none; cursor: pointer; min-height: 44px; }
.slip { background: #fff; max-width: 190mm; margin: 16px auto; padding: 12mm; }
.slip + .slip { page-break-before: always; }
.head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; border-bottom: 2px solid var(--ink); padding-bottom: 10px; margin-bottom: 12px; }
.logo { width: 46mm; height: auto; display: block; }
.brand { font-family: "Cormorant Garamond", Georgia, serif; font-size: 18pt; letter-spacing: 0.12em; text-transform: uppercase; margin: 0; }
.num { text-align: right; }
.num .label { font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.14em; color: var(--muted); }
.num strong { display: block; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 24pt; letter-spacing: 0.06em; }
.num .meta { font-size: 9.5pt; color: var(--muted); }
.ship { font-size: 16pt; line-height: 1.35; border: 1px solid var(--ink); padding: 10px 14px; margin-bottom: 12px; }
.ship .label { font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.14em; color: var(--muted); display: block; margin-bottom: 4px; }
.ship .phone { display: block; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 18pt; margin-top: 4px; }
table { width: 100%; border-collapse: collapse; }
th { text-align: left; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.12em; color: var(--muted); border-bottom: 1px solid var(--ink); padding: 6px 4px; }
td { padding: 9px 4px; border-bottom: 1px solid var(--line); vertical-align: middle; font-size: 13pt; }
.tick { width: 12mm; font-size: 18pt; }
.qty { text-align: right; font-weight: 700; font-size: 16pt; white-space: nowrap; }
.mono { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 10pt; }
.collect { border: 3px solid var(--ink); padding: 12px 14px; font-weight: 700; font-size: 15pt; letter-spacing: 0.06em; text-transform: uppercase; margin: 12px 0; text-align: center; }
.note { border: 1px dashed var(--ink); padding: 10px 14px; margin: 12px 0; font-size: 12pt; }
.note .label { font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.14em; color: var(--muted); display: block; margin-bottom: 4px; }
.foot { display: flex; justify-content: space-between; gap: 24px; margin-top: 18px; padding-top: 10px; border-top: 1px solid var(--line); font-size: 10pt; color: var(--muted); }
.sign { flex: 1; border-bottom: 1px solid var(--ink); height: 34px; }
.count { font-size: 10pt; color: var(--muted); margin: 6px 0 0; text-align: right; }
@media screen and (max-width: 600px) {
  .slip { margin: 8px; padding: 16px; }
  .head { flex-wrap: wrap; }
  .logo { width: 40mm; }
  .num { text-align: left; }
  .num strong { font-size: 18pt; }
  table .hide-narrow { display: none; }
  .foot { flex-wrap: wrap; }
}
@media print {
  html, body { background: #fff; }
  .toolbar { display: none !important; }
  .slip { margin: 0 auto; padding: 0; max-width: none; }
  a[href]::after { content: ""; }
  tr { page-break-inside: avoid; }
  thead { display: table-header-group; }
}
@page { size: A4; margin: 12mm; }
@media print and (max-width: 100mm) {
  @page { size: 80mm auto; margin: 3mm; }
  body { font-size: 9pt; }
  .logo { width: 40mm; }
  .ship { font-size: 12pt; }
  .hide-narrow { display: none !important; }
}
</style>
</head>
<body<?= $autoprint ? ' data-autoprint' : '' ?>>
<div class="toolbar">
  <a href="<?= e(url($returnUrl)) ?>">&larr; Back to order<?= count($payloads) > 1 ? 's' : '' ?></a>
  <span><?= count($payloads) > 1 ? e((string) count($payloads)) . ' slips, one per page.' : 'No prices are printed on the slip.' ?></span>
  <button type="button" data-print>Print</button>
</div>
<?php foreach ($payloads as $p): $o = $p['order']; ?>
<main class="slip">
  <header class="head">
    <div>
<?php if ($logoUri !== ''): ?>
      <img class="logo" src="<?= e($logoUri) ?>" alt="<?= e($store['name']) ?>">
<?php else: ?>
      <p class="brand"><?= e($store['name']) ?></p>
<?php endif; ?>
      <div class="mono hide-narrow"><?= e($store['site']) ?><?= $store['phone'] !== '' ? ' · ' . e($store['phone']) : '' ?></div>
    </div>
    <div class="num">
      <span class="label">Packing slip</span>
      <strong><?= e((string) $o['order_number']) ?></strong>
      <span class="meta">Placed <?= e(adm_order_datetime((string) $o['created_at'])) ?> · <?= e(payment_method_label((string) $o['payment_method'])) ?></span>
    </div>
  </header>
  <section class="ship">
    <span class="label">Ship to</span>
    <strong><?= e((string) $o['customer_name']) ?></strong><br>
    <?= nl2br(e((string) $o['address'])) ?><br>
    <?= e((string) $o['city']) ?><?= !empty($o['postal_code']) ? ' ' . e((string) $o['postal_code']) : '' ?>
    <span class="phone"><?= e((string) $o['customer_phone']) ?></span>
  </section>
<?php if ($p['collectAmount'] !== null): ?>
  <div class="collect">Collect <?= e($p['collectAmount']) ?> — cash on delivery</div>
<?php endif; ?>
  <table>
    <thead><tr><th class="tick">&#9744;</th><th class="hide-narrow">SKU</th><th>Product</th><th>Size</th><th class="qty">Qty</th></tr></thead>
    <tbody>
<?php foreach ($p['items'] as $item): ?>
      <tr><td class="tick">&#9744;</td><td class="mono hide-narrow"><?= e((string) $item['sku']) ?></td><td><?= e((string) $item['product_name']) ?></td><td><?= e((string) $item['size_label']) ?></td><td class="qty">&times; <?= (int) $item['quantity'] ?></td></tr>
<?php endforeach; ?>
    </tbody>
  </table>
  <p class="count"><?= e((string) $p['itemCount']) ?> item<?= $p['itemCount'] === 1 ? '' : 's' ?> in total</p>
<?php if (!empty($o['customer_note'])): ?>
  <div class="note"><span class="label">Customer note</span><?= nl2br(e((string) $o['customer_note'])) ?></div>
<?php endif; ?>
  <footer class="foot hide-narrow">
    <div><?= !empty($o['courier_name']) ? 'Courier: ' . e((string) $o['courier_name']) . (!empty($o['tracking_number']) ? ' · <span class="mono">' . e((string) $o['tracking_number']) . '</span>' : '') : 'Courier: ____________' ?></div>
    <div style="flex:1"><div class="sign"></div>Packed by / date</div>
  </footer>
</main>
<?php endforeach; ?>
<script src="<?= e(asset('js/admin-print.js')) ?>" defer></script>
</body>
</html>
