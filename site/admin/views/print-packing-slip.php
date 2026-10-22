<?php
defined('SKYFR') || exit;
$giltLogo = is_file(APP_ROOT . '/assets/img/brand/lockup-gilt-800.png') ? asset('img/brand/lockup-gilt-800.png') : $logoUri;
$contacts = array_values(array_filter([$store['site'], $store['whatsapp'] !== '' ? 'WhatsApp ' . $store['whatsapp'] : '', $store['phone']]));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Packing slip <?= e((string) $payloads[0]['order']['order_number']) ?> · <?= e($store['name']) ?></title>
<style>
@font-face { font-family: "Cormorant Garamond"; src: url("<?= e(asset('fonts/cormorant-garamond-variable.woff2')) ?>") format("woff2"); font-weight: 300 700; font-display: swap; }
@font-face { font-family: "Jost"; src: url("<?= e(asset('fonts/jost-variable.woff2')) ?>") format("woff2"); font-weight: 100 900; font-display: swap; }
:root { --ink: #1E1812; --muted: #6E6253; --soft: #8F826E; --bronze: #8A6538; --line: #E6DCCB; --hair: #EFE8DC; --paper: #F6F1E8; --vellum: #FEFCF8; --wash: #FBF8F2; --moss: #2F5D3A; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body { background: var(--paper); color: var(--ink); font: 400 10.5pt/1.45 "Jost", "Helvetica Neue", Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; font-variant-numeric: lining-nums tabular-nums; font-feature-settings: "lnum" 1, "tnum" 1; }
.toolbar { position: sticky; top: 0; z-index: 2; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; padding: 10px 16px; background: #0A0A0A; color: #F5F0E8; font-size: 13px; }
.toolbar a, .toolbar button { color: #D4B084; background: none; border: 1px solid #D4B084; border-radius: 6px; padding: 9px 16px; font: 500 13px/1 "Jost", Arial, sans-serif; letter-spacing: .04em; text-decoration: none; cursor: pointer; min-height: 44px; display: inline-flex; align-items: center; }
.toolbar button { background: #D4B084; color: #0A0A0A; }
.toolbar span { color: #B9AE9C; flex: 1 1 220px; }
.slip { position: relative; background: var(--vellum); width: 210mm; min-height: 297mm; margin: 18px auto; padding: 15mm 15mm 12mm; box-shadow: 0 1px 2px rgba(58,40,22,.06), 0 18px 40px -18px rgba(58,40,22,.28); display: flex; flex-direction: column; }
.slip::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 4px; background: linear-gradient(90deg, #6B4C28, #A27A48 30%, #D8BE92 50%, #A27A48 70%, #6B4C28); }
.head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10mm; }
.logo { width: 38mm; height: auto; display: block; }
.brandname { font-family: "Cormorant Garamond", Georgia, serif; font-size: 20pt; letter-spacing: .14em; text-transform: uppercase; margin: 0; }
.doc { text-align: right; }
.eyebrow { font-size: 7.5pt; font-weight: 500; letter-spacing: .28em; text-transform: uppercase; color: var(--bronze); margin: 0 0 2mm; }
.number { font-family: "Jost", Arial, sans-serif; font-weight: 600; font-size: 22pt; letter-spacing: .04em; line-height: 1; margin: 0 0 3mm; }
.meta { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 1.5mm; }
.chip { display: inline-block; padding: 1mm 2.6mm; border-radius: 999px; border: 1px solid var(--line); background: var(--wash); font-size: 8pt; color: var(--muted); white-space: nowrap; }
.chip--strong { border-color: var(--bronze); color: var(--bronze); font-weight: 600; letter-spacing: .08em; text-transform: uppercase; font-size: 7.4pt; }
.rule { height: 1px; margin: 7mm 0 6mm; background: linear-gradient(90deg, rgba(138,101,56,0), rgba(138,101,56,.55) 20%, rgba(216,190,146,.9) 50%, rgba(138,101,56,.55) 80%, rgba(138,101,56,0)); }
.top { display: grid; grid-template-columns: 1.55fr 1fr; gap: 6mm; margin-bottom: 7mm; }
.label { font-size: 7.2pt; font-weight: 500; letter-spacing: .24em; text-transform: uppercase; color: var(--bronze); margin: 0 0 2.5mm; }
.ship { position: relative; border: 1px solid var(--line); border-radius: 4px; padding: 5mm 6mm 5mm 7mm; background: #fff; }
.ship::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--bronze); border-radius: 4px 0 0 4px; }
.ship .name { font-family: "Cormorant Garamond", Georgia, serif; font-size: 20pt; font-weight: 600; line-height: 1.1; margin: 0 0 2mm; }
.ship .addr { font-size: 12pt; line-height: 1.45; margin: 0; }
.ship .phone { display: inline-block; margin-top: 3.5mm; font-size: 14pt; font-weight: 600; letter-spacing: .06em; padding: 1.2mm 3mm; background: var(--wash); border: 1px solid var(--line); border-radius: 3px; }
.side { display: grid; gap: 4mm; align-content: start; }
.stat { border: 1px solid var(--line); border-radius: 4px; padding: 4mm 5mm; background: #fff; }
.stat .big { font-family: "Cormorant Garamond", Georgia, serif; font-size: 26pt; font-weight: 600; line-height: 1; }
.stat .small { font-size: 8.5pt; color: var(--muted); margin-top: 1mm; }
.collect { border-radius: 4px; padding: 4.5mm 5mm; background: #1E1812; color: #F2E6D2; }
.collect .label { color: #D4B084; margin-bottom: 1.5mm; }
.collect .amount { font-family: "Cormorant Garamond", Georgia, serif; font-size: 24pt; font-weight: 600; line-height: 1; }
.collect .small { font-size: 8.5pt; color: #C9BBA3; margin-top: 1.5mm; }
.prepaid { border: 1px solid rgba(47,93,58,.35); background: rgba(47,93,58,.06); color: var(--moss); border-radius: 4px; padding: 4mm 5mm; font-weight: 600; font-size: 9.5pt; letter-spacing: .04em; }
table.items { width: 100%; border-collapse: collapse; }
.items th { font-size: 7.2pt; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); text-align: left; padding: 0 2mm 2.5mm; border-bottom: 1px solid var(--bronze); }
.items td { padding: 3.6mm 2mm; border-bottom: 1px solid var(--hair); vertical-align: middle; }
.items tr { break-inside: avoid; }
.items thead { display: table-header-group; }
.tick { width: 10mm; }
.box { display: inline-block; width: 5mm; height: 5mm; border: 1.5px solid var(--ink); border-radius: 1.2mm; vertical-align: middle; }
.item-name { font-family: "Cormorant Garamond", Georgia, serif; font-size: 14pt; font-weight: 600; line-height: 1.2; }
.item-sub { font-size: 8.5pt; color: var(--muted); margin-top: .6mm; letter-spacing: .02em; }
.size { font-size: 10.5pt; white-space: nowrap; }
.q { text-align: right; width: 22mm; }
.qty { display: inline-flex; align-items: center; justify-content: center; min-width: 11mm; height: 11mm; padding: 0 2mm; border-radius: 2.5mm; background: #1E1812; color: #F2E6D2; font-size: 15pt; font-weight: 600; }
.qty--one { background: transparent; color: var(--ink); border: 1.5px solid var(--line); }
.count { display: flex; justify-content: flex-end; gap: 6mm; font-size: 9pt; color: var(--muted); margin: 3mm 0 0; }
.count strong { color: var(--ink); }
.note { margin-top: 6mm; border: 1px dashed #CDBFA6; background: var(--wash); border-radius: 4px; padding: 4.5mm 5mm; break-inside: avoid; }
.note p { margin: 0; font-size: 11pt; line-height: 1.5; }
.signoff { margin-top: auto; padding-top: 9mm; display: grid; grid-template-columns: repeat(3, 1fr); gap: 7mm; break-inside: avoid; }
.sign { border-top: 1px solid var(--ink); padding-top: 1.6mm; font-size: 8pt; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); min-height: 14mm; }
.sign .val { display: block; margin-top: 1mm; font-size: 9.5pt; letter-spacing: .02em; text-transform: none; color: var(--ink); }
.brandline { margin: 6mm 0 0; text-align: center; font-size: 8pt; color: var(--soft); letter-spacing: .04em; }
.brandline span + span::before { content: "·"; color: var(--bronze); margin: 0 2.5mm; }
@media screen and (max-width: 820px) {
  .slip { width: auto; min-height: 0; margin: 10px; padding: 22px 16px 16px; overflow-wrap: anywhere; }
  .head { flex-direction: column; gap: 12px; }
  .doc { text-align: left; }
  .meta { justify-content: flex-start; }
  .logo { width: 130px; }
  .top { grid-template-columns: 1fr; }
  .signoff { grid-template-columns: 1fr; gap: 14px; }
  .items .sku-col { display: none; }
}
@media print {
  @page { size: A4; margin: 0; }
  body { background: #fff; }
  .toolbar { display: none !important; }
  .slip { margin: 0; box-shadow: none; width: 210mm; min-height: 297mm; page-break-after: always; }
  .slip:last-of-type { page-break-after: auto; }
}
@media print and (max-width: 100mm) {
  @page { size: 80mm auto; margin: 3mm; }
  body { font-size: 9pt; }
  .slip { width: auto; min-height: 0; padding: 0; }
  .slip::before, .rule, .brandline, .signoff { display: none; }
  .head { flex-direction: column; gap: 2mm; }
  .doc { text-align: left; }
  .logo { width: 34mm; }
  .top { grid-template-columns: 1fr; gap: 3mm; }
  .ship .name { font-size: 14pt; }
  .ship .addr { font-size: 10pt; }
  .items .sku-col, .items .tick { display: none; }
}
</style>
</head>
<body<?= $autoprint ? ' data-autoprint' : '' ?>>
<div class="toolbar">
  <a href="<?= e(url($returnUrl)) ?>">&larr; Back to order<?= count($payloads) > 1 ? 's' : '' ?></a>
  <span><?= count($payloads) > 1 ? e((string) count($payloads)) . ' slips, one per page. No prices are printed.' : 'No prices are printed on the packing slip.' ?></span>
  <button type="button" data-print>Print</button>
</div>
<?php foreach ($payloads as $p): $o = $p['order']; $lineCount = count($p['items']); ?>
<main class="slip">
  <header class="head">
    <div>
<?php if ($giltLogo !== ''): ?>
      <img class="logo" src="<?= e($giltLogo) ?>" alt="<?= e($store['name']) ?>">
<?php else: ?>
      <p class="brandname"><?= e($store['name']) ?></p>
<?php endif; ?>
    </div>
    <div class="doc">
      <p class="eyebrow">Packing slip</p>
      <p class="number"><?= e((string) $o['order_number']) ?></p>
      <div class="meta">
        <span class="chip"><?= e(adm_order_datetime((string) $o['created_at'])) ?></span>
        <span class="chip"><?= e(payment_method_label((string) $o['payment_method'])) ?></span>
<?php if ((string) ($o['source'] ?? '') === 'phone'): ?>
        <span class="chip chip--strong">Phone order</span>
<?php endif; ?>
      </div>
    </div>
  </header>

  <div class="rule"></div>

  <section class="top">
    <div class="ship">
      <p class="label">Ship to</p>
      <p class="name"><?= e((string) $o['customer_name']) ?></p>
      <p class="addr"><?= nl2br(e((string) $o['address'])) ?><br><?= e((string) $o['city']) ?><?= !empty($o['postal_code']) ? ' ' . e((string) $o['postal_code']) : '' ?></p>
      <span class="phone"><?= e((string) $o['customer_phone']) ?></span>
    </div>
    <div class="side">
      <div class="stat">
        <p class="label">In this parcel</p>
        <div class="big"><?= (int) $p['itemCount'] ?> <?= $p['itemCount'] === 1 ? 'bottle' : 'bottles' ?></div>
        <div class="small"><?= $lineCount ?> <?= $lineCount === 1 ? 'fragrance' : 'different fragrances' ?></div>
      </div>
<?php if ($p['collectAmount'] !== null): ?>
      <div class="collect">
        <p class="label">Collect on delivery</p>
        <div class="amount"><?= e($p['collectAmount']) ?></div>
        <div class="small">Write this amount on the courier booking.</div>
      </div>
<?php elseif ($p['isPaid']): ?>
      <div class="prepaid">Paid in full · nothing to collect</div>
<?php endif; ?>
    </div>
  </section>

  <table class="items">
    <thead><tr><th class="tick">Packed</th><th>Fragrance</th><th class="sku-col">SKU</th><th>Size</th><th class="q">Qty</th></tr></thead>
    <tbody>
<?php foreach ($p['items'] as $item): $qty = (int) $item['quantity']; ?>
      <tr>
        <td class="tick"><span class="box" aria-hidden="true"></span></td>
        <td><div class="item-name"><?= e((string) $item['product_name']) ?></div></td>
        <td class="sku-col"><span class="item-sub"><?= e((string) $item['sku']) ?></span></td>
        <td class="size"><?= e((string) $item['size_label']) ?></td>
        <td class="q"><span class="qty<?= $qty === 1 ? ' qty--one' : '' ?>"><?= $qty ?></span></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
  <p class="count"><span><strong><?= (int) $p['itemCount'] ?></strong> <?= $p['itemCount'] === 1 ? 'bottle' : 'bottles' ?> in total</span></p>

<?php if (!empty($o['customer_note'])): ?>
  <div class="note">
    <p class="label">Note</p>
    <p><?= nl2br(e((string) $o['customer_note'])) ?></p>
  </div>
<?php endif; ?>

  <footer class="signoff">
    <div class="sign">Packed by<span class="val">&nbsp;</span></div>
    <div class="sign">Checked by · date<span class="val">&nbsp;</span></div>
    <div class="sign">Courier<span class="val"><?= !empty($o['courier_name']) ? e((string) $o['courier_name']) . (!empty($o['tracking_number']) ? ' · ' . e((string) $o['tracking_number']) : '') : '&nbsp;' ?></span></div>
  </footer>
<?php if ($contacts !== []): ?>
  <p class="brandline"><?php foreach ($contacts as $contact): ?><span><?= e((string) $contact) ?></span><?php endforeach; ?></p>
<?php endif; ?>
</main>
<?php endforeach; ?>
<script src="<?= e(asset('js/admin-print.js')) ?>" defer></script>
</body>
</html>
