<?php
defined('SKYFR') || exit;
$couponLabel = static function (array $o): string {
    if (empty($o['coupon_code'])) {
        return 'Discount';
    }
    $pct = $o['coupon_type'] === 'percent' ? ' · ' . rtrim(rtrim((string) $o['coupon_value'], '0'), '.') . '%' : '';
    return 'Discount (' . $o['coupon_code'] . $pct . ')';
};
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Invoice <?= e((string) $payloads[0]['order']['order_number']) ?> · Sky Fragrances</title>
<style>
:root { --ink: #0A0A0A; --gold: #B08D57; --line: #D9D2C5; --muted: #6B6257; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #F5F0E8; color: var(--ink); font: 11pt/1.45 Jost, "Helvetica Neue", Arial, sans-serif; }
.toolbar { display: flex; gap: 8px; justify-content: space-between; align-items: center; padding: 10px 16px; background: var(--ink); color: #F5F0E8; font-size: 14px; }
.toolbar a, .toolbar button { color: #D4B084; background: none; border: 1px solid #D4B084; border-radius: 6px; padding: 8px 14px; font: inherit; text-decoration: none; cursor: pointer; min-height: 44px; }
.sheet { background: #fff; max-width: 190mm; margin: 16px auto; padding: 14mm 12mm; }
.head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; border-bottom: 2px solid var(--ink); padding-bottom: 10px; margin-bottom: 14px; }
.logo { width: 52mm; height: auto; display: block; }
.brand { font-family: "Cormorant Garamond", Georgia, serif; font-size: 20pt; letter-spacing: 0.12em; text-transform: uppercase; margin: 0; }
.brand small { display: block; font: italic 10pt Georgia, serif; letter-spacing: 0; text-transform: none; color: var(--muted); }
.store { font-size: 9.5pt; color: var(--muted); margin-top: 6px; }
.doc { text-align: right; }
.doc h1 { font-family: "Cormorant Garamond", Georgia, serif; font-weight: 400; font-size: 26pt; letter-spacing: 0.2em; margin: 0 0 4px; }
.doc .num { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 14pt; }
.doc .meta { font-size: 9.5pt; color: var(--muted); }
.cols { display: flex; gap: 24px; margin-bottom: 14px; }
.cols > div { flex: 1; }
.label { font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.14em; color: var(--muted); margin-bottom: 3px; }
table { width: 100%; border-collapse: collapse; margin-top: 6px; }
th { text-align: left; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.12em; color: var(--muted); border-bottom: 1px solid var(--ink); padding: 6px 4px; }
td { padding: 7px 4px; border-bottom: 1px solid var(--line); vertical-align: top; }
.r { text-align: right; white-space: nowrap; }
.mono { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 9.5pt; }
.totals { width: 80mm; margin: 12px 0 0 auto; }
.totals td { border: 0; padding: 4px; }
.totals tr.grand td { border-top: 1px solid var(--ink); font-weight: 600; font-size: 12.5pt; padding-top: 8px; }
.gold { color: var(--gold); }
.stamp { display: inline-block; border: 2px solid var(--gold); color: var(--gold); border-radius: 4px; padding: 6px 12px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; margin-top: 10px; }
.collect { border: 3px solid var(--ink); padding: 10px 14px; font-weight: 700; font-size: 13pt; letter-spacing: 0.06em; text-transform: uppercase; margin-top: 10px; }
.foot { margin-top: 18px; padding-top: 10px; border-top: 1px solid var(--line); font-size: 9.5pt; color: var(--muted); }
.foot .thanks { color: var(--ink); font-family: "Cormorant Garamond", Georgia, serif; font-size: 12pt; margin: 0 0 6px; }
@media screen and (max-width: 600px) {
  .sheet { margin: 8px; padding: 16px; }
  .head { flex-wrap: wrap; }
  .logo { width: 40mm; }
  .doc { text-align: left; }
  .doc h1 { font-size: 20pt; letter-spacing: 0.12em; }
  .cols { display: block; }
  .cols > div + div { margin-top: 12px; }
  table .hide-narrow { display: none; }
  .totals { width: 100%; }
}
@media print {
  html, body { background: #fff; }
  .toolbar { display: none !important; }
  .sheet { margin: 0 auto; padding: 0; max-width: none; }
  a[href]::after { content: ""; }
  tr { page-break-inside: avoid; }
  thead { display: table-header-group; }
}
@page { size: A4; margin: 12mm; }
@media print and (max-width: 100mm) {
  @page { size: 80mm auto; margin: 3mm; }
  body { font-size: 9pt; }
  .logo { width: 40mm; }
  .hide-narrow { display: none !important; }
  .cols { display: block; }
  .totals { width: 100%; }
}
</style>
</head>
<body<?= $autoprint ? ' data-autoprint' : '' ?>>
<div class="toolbar">
  <a href="<?= e(url($returnUrl)) ?>">&larr; Back to order</a>
  <span>Use your browser's Print or Share → Print if the dialog does not open.</span>
  <button type="button" data-print>Print</button>
</div>
<?php foreach ($payloads as $p): $o = $p['order']; ?>
<main class="sheet">
  <header class="head">
    <div>
<?php if ($logoUri !== ''): ?>
      <img class="logo" src="<?= e($logoUri) ?>" alt="<?= e($store['name']) ?>">
<?php else: ?>
      <p class="brand"><?= e($store['name']) ?><small><?= e($store['tagline']) ?></small></p>
<?php endif; ?>
      <div class="store hide-narrow"><?= $store['address'] !== '' ? nl2br(e($store['address'])) . '<br>' : '' ?><?= $store['phone'] !== '' ? e($store['phone']) . ' · ' : '' ?><?= $store['email'] !== '' ? e($store['email']) . ' · ' : '' ?><?= e($store['site']) ?></div>
    </div>
    <div class="doc">
      <h1>Invoice</h1>
      <div class="num"><?= e((string) $o['order_number']) ?></div>
      <div class="meta">Order date <?= e(adm_order_datetime((string) $o['created_at'])) ?><br>Printed <?= e($printedAt) ?></div>
    </div>
  </header>
  <section class="cols">
    <div>
      <div class="label">Bill to</div>
      <strong><?= e((string) $o['customer_name']) ?></strong><br>
      <?= e((string) $o['customer_phone']) ?><br>
<?php if (!empty($o['customer_email'])): ?>
      <?= e((string) $o['customer_email']) ?><br>
<?php endif; ?>
      <?= nl2br(e((string) $o['address'])) ?><br><?= e((string) $o['city']) ?><?= !empty($o['postal_code']) ? ' ' . e((string) $o['postal_code']) : '' ?>
    </div>
    <div class="hide-narrow">
      <div class="label">Payment</div>
      <?= e(payment_method_label((string) $o['payment_method'])) ?><?= !empty($o['payment_reference']) ? ' · ref <span class="mono">' . e((string) $o['payment_reference']) . '</span>' : '' ?><br>
      <div class="label" style="margin-top:8px">Status</div>
      <?= e(adm_order_status_label((string) $o['status'])) ?><?= !empty($o['courier_name']) ? ' · ' . e((string) $o['courier_name']) . (!empty($o['tracking_number']) ? ' <span class="mono">' . e((string) $o['tracking_number']) . '</span>' : '') : '' ?>
    </div>
  </section>
  <table>
    <thead><tr><th class="hide-narrow">SKU</th><th>Product</th><th>Size</th><th class="r">Unit</th><th class="r">Qty</th><th class="r">Total</th></tr></thead>
    <tbody>
<?php foreach ($p['items'] as $item): ?>
      <tr><td class="mono hide-narrow"><?= e((string) $item['sku']) ?></td><td><?= e((string) $item['product_name']) ?></td><td><?= e((string) $item['size_label']) ?></td><td class="r"><?= e(money((string) $item['unit_price_charged'])) ?></td><td class="r"><?= (int) $item['quantity'] ?></td><td class="r"><?= e(money((string) $item['line_total'])) ?></td></tr>
<?php endforeach; ?>
    </tbody>
  </table>
  <table class="totals">
    <tr><td>Subtotal</td><td class="r"><?= e(money((string) $o['subtotal'])) ?></td></tr>
<?php if (money_paisa((string) $o['discount_total']) > 0): ?>
    <tr><td><?= e($couponLabel($o)) ?></td><td class="r">&minus; <?= e(money((string) $o['discount_total'])) ?></td></tr>
<?php endif; ?>
    <tr><td>Shipping</td><td class="r"><?= money_paisa((string) $o['shipping_fee']) === 0 ? '<span class="gold">Free</span>' : e(money((string) $o['shipping_fee'])) ?></td></tr>
<?php if (money_paisa((string) $o['cod_fee']) > 0): ?>
    <tr><td>COD fee</td><td class="r"><?= e(money((string) $o['cod_fee'])) ?></td></tr>
<?php endif; ?>
    <tr class="grand"><td>Grand total</td><td class="r"><?= e(money((string) $o['grand_total'])) ?></td></tr>
<?php if ($p['isPaid']): ?>
    <tr><td>Paid</td><td class="r"><?= e(money((string) $o['grand_total'])) ?></td></tr>
    <tr><td>Balance due</td><td class="r"><?= e(money('0.00')) ?></td></tr>
<?php endif; ?>
  </table>
<?php if ($p['isPaid']): ?>
  <div class="stamp">Paid <?= e(adm_order_datetime((string) $o['paid_at'])) ?></div>
<?php elseif ($p['collectAmount'] !== null): ?>
  <div class="collect">Cash on delivery — <?= e($p['collectAmount']) ?> to be collected</div>
<?php elseif ($o['status'] === 'cancelled'): ?>
  <div class="stamp" style="color:#6E2A2A;border-color:#6E2A2A">Cancelled</div>
<?php else: ?>
  <div class="stamp">Payment <?= e(strtolower(adm_order_payment_label((string) $o['payment_status']))) ?></div>
<?php endif; ?>
  <footer class="foot">
    <p class="thanks">Thank you for shopping with <?= e($store['name']) ?> — <?= e($store['tagline']) ?>.</p>
    <p class="hide-narrow">Exchanges are accepted on unopened bottles within 7 days of delivery — message us on WhatsApp<?= $store['whatsapp'] !== '' ? ' at ' . e($store['whatsapp']) : '' ?> with your order number.</p>
  </footer>
</main>
<?php endforeach; ?>
<script src="<?= e(asset('js/admin-print.js')) ?>" defer></script>
</body>
</html>
