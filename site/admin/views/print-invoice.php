<?php
defined('SKYFR') || exit;
$couponLabel = static function (array $o): string {
    if (empty($o['coupon_code'])) {
        return 'Discount';
    }
    $pct = $o['coupon_type'] === 'percent' ? ' · ' . rtrim(rtrim((string) $o['coupon_value'], '0'), '.') . '%' : '';
    return 'Discount (' . $o['coupon_code'] . $pct . ')';
};
$accountLines = static function (?string $snapshot): array {
    $snapshot = trim((string) $snapshot);
    if ($snapshot === '') {
        return [];
    }
    $body = str_contains($snapshot, ' — ') ? substr($snapshot, strpos($snapshot, ' — ') + strlen(' — ')) : $snapshot;
    $lines = [];
    foreach (explode(' · ', $body) as $part) {
        $pair = explode(': ', $part, 2);
        $lines[] = count($pair) === 2 ? ['label' => trim($pair[0]), 'value' => trim($pair[1])] : ['label' => '', 'value' => trim($part)];
    }
    return $lines;
};
$giltLogo = is_file(APP_ROOT . '/assets/img/brand/lockup-gilt-800.png') ? asset('img/brand/lockup-gilt-800.png') : $logoUri;
$returnsDays = (int) setting('returns_days', '7');
$instagram = trim((string) setting('instagram_handle', ''));
$contacts = array_values(array_filter([
    $store['site'],
    $store['whatsapp'] !== '' ? 'WhatsApp ' . $store['whatsapp'] : '',
    $store['email'],
    $instagram,
]));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Invoice <?= e((string) $payloads[0]['order']['order_number']) ?> · <?= e($store['name']) ?></title>
<style>
@font-face { font-family: "Cormorant Garamond"; src: url("<?= e(asset('fonts/cormorant-garamond-variable.woff2')) ?>") format("woff2"); font-weight: 300 700; font-display: swap; }
@font-face { font-family: "Jost"; src: url("<?= e(asset('fonts/jost-variable.woff2')) ?>") format("woff2"); font-weight: 100 900; font-display: swap; }
:root { --ink: #1E1812; --muted: #6E6253; --soft: #8F826E; --bronze: #8A6538; --bronze-deep: #6B4C28; --line: #E6DCCB; --hair: #EFE8DC; --paper: #F6F1E8; --vellum: #FEFCF8; --moss: #2F5D3A; --maroon: #7A2E2E; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body { background: var(--paper); color: var(--ink); font: 400 10pt/1.5 "Jost", "Helvetica Neue", Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; font-variant-numeric: lining-nums tabular-nums; font-feature-settings: "lnum" 1, "tnum" 1; }
.toolbar { position: sticky; top: 0; z-index: 2; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; padding: 10px 16px; background: #0A0A0A; color: #F5F0E8; font-size: 13px; }
.toolbar a, .toolbar button { color: #D4B084; background: none; border: 1px solid #D4B084; border-radius: 6px; padding: 9px 16px; font: 500 13px/1 "Jost", Arial, sans-serif; letter-spacing: .04em; text-decoration: none; cursor: pointer; min-height: 44px; display: inline-flex; align-items: center; }
.toolbar button { background: #D4B084; color: #0A0A0A; }
.toolbar span { color: #B9AE9C; flex: 1 1 260px; }
.sheet { position: relative; background: var(--vellum); width: 210mm; min-height: 297mm; margin: 18px auto; padding: 16mm 16mm 14mm; box-shadow: 0 1px 2px rgba(58,40,22,.06), 0 18px 40px -18px rgba(58,40,22,.28); display: flex; flex-direction: column; }
.sheet::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 4px; background: linear-gradient(90deg, #6B4C28, #A27A48 30%, #D8BE92 50%, #A27A48 70%, #6B4C28); }
.head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12mm; }
.logo { width: 44mm; height: auto; display: block; }
.brandname { font-family: "Cormorant Garamond", Georgia, serif; font-size: 22pt; letter-spacing: .14em; text-transform: uppercase; margin: 0; }
.doc { text-align: right; }
.eyebrow { font-size: 7.5pt; font-weight: 500; letter-spacing: .28em; text-transform: uppercase; color: var(--bronze); margin: 0 0 2mm; }
.doc h1 { font-family: "Cormorant Garamond", Georgia, serif; font-weight: 500; font-size: 30pt; line-height: 1; letter-spacing: .02em; margin: 0 0 3mm; }
.docmeta { display: grid; grid-template-columns: auto auto; gap: 1mm 6mm; justify-content: end; font-size: 8.8pt; color: var(--muted); }
.docmeta dt { text-align: right; }
.docmeta dd { margin: 0; color: var(--ink); text-align: right; font-weight: 500; }
.rule { height: 1px; margin: 8mm 0 7mm; background: linear-gradient(90deg, rgba(138,101,56,0), rgba(138,101,56,.55) 20%, rgba(216,190,146,.9) 50%, rgba(138,101,56,.55) 80%, rgba(138,101,56,0)); }
.parties { display: grid; grid-template-columns: 1.1fr 1.1fr 1fr; gap: 8mm; margin-bottom: 8mm; }
.label { font-size: 7.2pt; font-weight: 500; letter-spacing: .24em; text-transform: uppercase; color: var(--bronze); margin: 0 0 2mm; }
.party strong { display: block; font-family: "Cormorant Garamond", Georgia, serif; font-size: 14pt; font-weight: 600; line-height: 1.2; margin-bottom: 1mm; }
.party p { margin: 0; color: var(--ink); }
.party .muted { color: var(--muted); }
.pill { display: inline-block; margin-top: 2mm; padding: 1.2mm 3mm; border-radius: 999px; font-size: 7.5pt; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; border: 1px solid currentColor; }
.pill--paid { color: var(--moss); background: rgba(47,93,58,.07); }
.pill--due { color: var(--bronze-deep); background: rgba(138,101,56,.08); }
.pill--void { color: var(--maroon); background: rgba(122,46,46,.07); }
table.items { width: 100%; border-collapse: collapse; }
.items th { font-size: 7.2pt; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); text-align: left; padding: 0 2mm 2.5mm; border-bottom: 1px solid var(--bronze); }
.items td { padding: 3.2mm 2mm; border-bottom: 1px solid var(--hair); vertical-align: top; }
.items .n { width: 8mm; color: var(--soft); font-size: 8.5pt; }
.items td.n { padding-top: 3.8mm; }
.items .c { text-align: center; }
.items .r { text-align: right; white-space: nowrap; }
.item-name { font-family: "Cormorant Garamond", Georgia, serif; font-size: 13pt; font-weight: 600; line-height: 1.25; }
.item-sub { font-size: 8.3pt; color: var(--muted); margin-top: .6mm; }
.was { color: var(--soft); text-decoration: line-through; font-size: 8.3pt; margin-right: 1.5mm; }
.items tr { break-inside: avoid; }
.items thead { display: table-header-group; }
.bottom { display: grid; grid-template-columns: 1fr 74mm; gap: 10mm; margin-top: 7mm; align-items: start; break-inside: avoid; }
.note { border: 1px solid var(--line); background: #FBF8F2; border-radius: 3px; padding: 4.5mm 5mm; }
.note h2 { font-size: 7.2pt; font-weight: 500; letter-spacing: .24em; text-transform: uppercase; color: var(--bronze); margin: 0 0 2mm; }
.note p { margin: 0 0 1.5mm; }
.acct { display: grid; grid-template-columns: auto 1fr; gap: .8mm 4mm; margin: 2mm 0 0; font-size: 9.2pt; }
.acct dt { color: var(--muted); }
.acct dd { margin: 0; font-weight: 500; word-break: break-word; }
.totals { width: 100%; border-collapse: collapse; }
.totals td { padding: 1.6mm 0; }
.totals td:last-child { text-align: right; white-space: nowrap; }
.totals .free { color: var(--moss); font-weight: 500; }
.totals .grand td { border-top: 1px solid var(--bronze); padding-top: 3.5mm; }
.totals .grand td:first-child { font-size: 8pt; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); vertical-align: middle; }
.totals .grand td:last-child { font-family: "Cormorant Garamond", Georgia, serif; font-size: 21pt; font-weight: 600; line-height: 1; }
.totals .sub td { color: var(--muted); font-size: 9pt; }
.totals .due td { font-weight: 600; }
.stamp { display: inline-block; margin-top: 4mm; padding: 2mm 4mm; border: 1.5px solid var(--moss); color: var(--moss); border-radius: 3px; font-size: 8.5pt; font-weight: 600; letter-spacing: .18em; text-transform: uppercase; transform: rotate(-2deg); }
.foot { margin-top: auto; padding-top: 10mm; text-align: center; }
.thanks { font-family: "Cormorant Garamond", Georgia, serif; font-style: italic; font-size: 15pt; margin: 0 0 2mm; color: var(--ink); }
.policy { font-size: 8.5pt; color: var(--muted); margin: 0 auto 3mm; max-width: 150mm; }
.contacts { font-size: 8.5pt; color: var(--ink); letter-spacing: .04em; margin: 0 0 3mm; }
.contacts span + span::before { content: "·"; color: var(--bronze); margin: 0 2.5mm; }
.fine { font-size: 7.5pt; color: var(--soft); margin: 0; }
@media screen and (max-width: 820px) {
  .sheet { width: auto; min-height: 0; margin: 10px; padding: 22px 18px 18px; overflow-wrap: anywhere; }
  .doc h1 { font-size: 26pt; }
  .head { flex-direction: column; gap: 14px; }
  .doc { text-align: left; }
  .docmeta { justify-content: start; }
  .docmeta dt, .docmeta dd { text-align: left; }
  .logo { width: 150px; }
  .parties { grid-template-columns: 1fr; gap: 16px; }
  .bottom { grid-template-columns: 1fr; gap: 16px; }
  .items .n, .items th.n { display: none; }
  .items td, .items th { padding-left: 1mm; padding-right: 1mm; }
}
@media print {
  @page { size: A4; margin: 0; }
  body { background: #fff; }
  .toolbar { display: none !important; }
  .sheet { margin: 0; box-shadow: none; width: 210mm; min-height: 297mm; page-break-after: always; }
  .sheet:last-of-type { page-break-after: auto; }
  a { color: inherit; text-decoration: none; }
}
</style>
</head>
<body<?= $autoprint ? ' data-autoprint' : '' ?>>
<div class="toolbar">
  <a href="<?= e(url($returnUrl)) ?>">&larr; Back to order</a>
  <span>To send it to the customer, choose "Save as PDF" in the print window, then share the PDF on WhatsApp.</span>
  <button type="button" data-print>Print or save as PDF</button>
</div>
<?php foreach ($payloads as $p): $o = $p['order']; $isCancelled = (string) $o['status'] === 'cancelled'; $isManual = payment_method_is_manual((string) $o['payment_method']); ?>
<main class="sheet">
  <header class="head">
    <div>
<?php if ($giltLogo !== ''): ?>
      <img class="logo" src="<?= e($giltLogo) ?>" alt="<?= e($store['name']) ?>">
<?php else: ?>
      <p class="brandname"><?= e($store['name']) ?></p>
<?php endif; ?>
    </div>
    <div class="doc">
      <p class="eyebrow">Invoice</p>
      <h1><?= e((string) $o['order_number']) ?></h1>
      <dl class="docmeta">
        <dt>Invoice date</dt><dd><?= e(adm_order_datetime((string) $o['created_at'])) ?></dd>
        <dt>Payment</dt><dd><?= e(payment_method_label((string) $o['payment_method'])) ?></dd>
<?php if ((string) ($o['source'] ?? '') === 'phone'): ?>
        <dt>Order</dt><dd>Taken by phone</dd>
<?php endif; ?>
      </dl>
    </div>
  </header>

  <div class="rule"></div>

  <section class="parties">
    <div class="party">
      <p class="label">Billed to</p>
      <strong><?= e((string) $o['customer_name']) ?></strong>
      <p><?= e((string) $o['customer_phone']) ?></p>
<?php if (!empty($o['customer_email'])): ?>
      <p class="muted"><?= e((string) $o['customer_email']) ?></p>
<?php endif; ?>
    </div>
    <div class="party">
      <p class="label">Deliver to</p>
      <p><?= nl2br(e((string) $o['address'])) ?></p>
      <p><?= e((string) $o['city']) ?><?= !empty($o['postal_code']) ? ' ' . e((string) $o['postal_code']) : '' ?></p>
<?php if (!empty($o['courier_name'])): ?>
      <p class="muted"><?= e((string) $o['courier_name']) ?><?= !empty($o['tracking_number']) ? ' · ' . e((string) $o['tracking_number']) : '' ?></p>
<?php endif; ?>
    </div>
    <div class="party">
      <p class="label">Status</p>
      <p><?= e(adm_order_status_label((string) $o['status'])) ?></p>
<?php if ($isCancelled): ?>
      <span class="pill pill--void">Cancelled</span>
<?php elseif ($p['isPaid']): ?>
      <span class="pill pill--paid">Paid</span>
<?php elseif ($p['collectAmount'] !== null): ?>
      <span class="pill pill--due">Pay on delivery</span>
<?php else: ?>
      <span class="pill pill--due">Payment due</span>
<?php endif; ?>
<?php if (!empty($o['payment_reference'])): ?>
      <p class="muted" style="margin-top:2mm">Ref <?= e((string) $o['payment_reference']) ?></p>
<?php endif; ?>
    </div>
  </section>

  <table class="items">
    <thead>
      <tr><th class="n">#</th><th>Item</th><th class="c">Qty</th><th class="r">Unit price</th><th class="r">Amount</th></tr>
    </thead>
    <tbody>
<?php foreach ($p['items'] as $i => $item): $charged = (string) $item['unit_price_charged']; $list = (string) $item['unit_price']; ?>
      <tr>
        <td class="n"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></td>
        <td>
          <div class="item-name"><?= e((string) $item['product_name']) ?></div>
          <div class="item-sub"><?= e((string) $item['size_label']) ?><?= !empty($item['sku']) ? ' · ' . e((string) $item['sku']) : '' ?></div>
        </td>
        <td class="c"><?= (int) $item['quantity'] ?></td>
        <td class="r"><?php if (money_paisa($list) > money_paisa($charged)): ?><span class="was"><?= e(money($list)) ?></span><?php endif; ?><?= e(money($charged)) ?></td>
        <td class="r"><?= e(money((string) $item['line_total'])) ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>

  <section class="bottom">
    <div>
<?php if (!$isCancelled && !$p['isPaid'] && $p['collectAmount'] !== null): ?>
      <div class="note">
        <h2>Cash on delivery</h2>
        <p>Please keep <strong><?= e($p['collectAmount']) ?></strong> ready for the courier. The rider will call before delivery.</p>
      </div>
<?php elseif (!$isCancelled && !$p['isPaid'] && $isManual): ?>
      <div class="note">
        <h2>How to pay</h2>
        <p>Please transfer <strong><?= e(money((string) $o['grand_total'])) ?></strong> by <?= e(payment_method_label((string) $o['payment_method'])) ?> and send us the screenshot on WhatsApp with your order number.</p>
<?php $lines = $accountLines($o['payment_account_snapshot'] ?? null); if ($lines !== []): ?>
        <dl class="acct">
<?php foreach ($lines as $line): ?>
          <dt><?= e($line['label']) ?></dt><dd><?= e($line['value']) ?></dd>
<?php endforeach; ?>
        </dl>
<?php endif; ?>
      </div>
<?php elseif ($p['isPaid']): ?>
      <div class="stamp">Paid<?= !empty($o['paid_at']) ? ' · ' . e(adm_order_datetime((string) $o['paid_at'])) : '' ?></div>
<?php endif; ?>
    </div>
    <table class="totals">
      <tr><td>Subtotal</td><td><?= e(money((string) $o['subtotal'])) ?></td></tr>
<?php if (money_paisa((string) $o['discount_total']) > 0): ?>
      <tr><td><?= e($couponLabel($o)) ?></td><td>&minus; <?= e(money((string) $o['discount_total'])) ?></td></tr>
<?php endif; ?>
      <tr><td>Delivery</td><td><?= money_paisa((string) $o['shipping_fee']) === 0 ? '<span class="free">Free</span>' : e(money((string) $o['shipping_fee'])) ?></td></tr>
<?php if (money_paisa((string) $o['cod_fee']) > 0): ?>
      <tr><td>Cash on delivery fee</td><td><?= e(money((string) $o['cod_fee'])) ?></td></tr>
<?php endif; ?>
      <tr class="grand"><td>Total</td><td><?= e(money((string) $o['grand_total'])) ?></td></tr>
<?php if ($p['isPaid']): ?>
      <tr class="sub"><td>Paid</td><td><?= e(money((string) $o['grand_total'])) ?></td></tr>
      <tr class="sub due"><td>Balance due</td><td><?= e(money('0.00')) ?></td></tr>
<?php elseif (!$isCancelled): ?>
      <tr class="sub due"><td>Amount due</td><td><?= e(money((string) $o['grand_total'])) ?></td></tr>
<?php endif; ?>
    </table>
  </section>

  <footer class="foot">
    <p class="thanks">Thank you for choosing <?= e($store['name']) ?>.</p>
    <p class="policy">Sealed, unopened bottles can be exchanged within <?= $returnsDays > 0 ? $returnsDays : 7 ?> days of delivery. Message us with your order number and we will arrange it.</p>
<?php if ($contacts !== []): ?>
    <p class="contacts"><?php foreach ($contacts as $contact): ?><span><?= e((string) $contact) ?></span><?php endforeach; ?></p>
<?php endif; ?>
    <p class="fine">This invoice was generated electronically and is valid without a signature.</p>
  </footer>
</main>
<?php endforeach; ?>
<script src="<?= e(asset('js/admin-print.js')) ?>" defer></script>
</body>
</html>
