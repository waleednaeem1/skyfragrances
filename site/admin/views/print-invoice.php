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
@font-face { font-family: "Geist"; src: url("<?= e(asset('fonts/geist-variable.woff2')) ?>") format("woff2"); font-weight: 100 900; font-display: swap; }
@font-face { font-family: "Geist Mono"; src: url("<?= e(asset('fonts/geist-mono-variable.woff2')) ?>") format("woff2"); font-weight: 100 900; font-display: swap; }
:root { --ink: #0B0B0C; --ink-2: #3A3A37; --muted: #64645E; --soft: #8A8A83; --line: #E6E6E1; --hair: #EFEFEB; --canvas: #E9E9E4; --paper: #FFFFFF; --accent: #D4F84B; --ok: #13693A; --ok-soft: #E5F6EA; --warn: #8A5200; --warn-soft: #FFF2DB; --bad: #B42318; --bad-soft: #FDECEA; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body { background: var(--canvas); color: var(--ink); font: 400 10pt/1.5 "Geist", "Helvetica Neue", Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; font-variant-numeric: tabular-nums; }
.toolbar { position: sticky; top: 0; z-index: 2; display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; padding: 12px 20px; background: var(--ink); color: #C9C9C2; font-size: 13px; }
.toolbar a, .toolbar button { display: inline-flex; align-items: center; min-height: 36px; padding: 0 14px; border: 1px solid #3A3A37; border-radius: 10px; background: none; color: #FFFFFF; font: 500 13px/1 "Geist", Arial, sans-serif; text-decoration: none; cursor: pointer; }
.toolbar button { border-color: #B9DD2A; background: var(--accent); color: var(--ink); }
.toolbar span { flex: 1 1 260px; }
.sheet { position: relative; display: flex; flex-direction: column; width: 210mm; min-height: 297mm; margin: 24px auto; padding: 16mm 15mm 14mm; background: var(--paper); box-shadow: 0 12px 32px rgba(11, 11, 12, .10); }
.head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12mm; }
.brandmark { display: flex; align-items: center; gap: 3.5mm; margin-top: 8mm; }
.mark { display: flex; align-items: center; justify-content: center; flex: none; width: 12mm; height: 12mm; border-radius: 3mm; background: var(--ink); color: #FFFFFF; font: 600 12pt/1 "Geist", Arial, sans-serif; letter-spacing: -0.04em; }
.mark span { color: var(--accent); }
.brandname { margin: 0; font: 600 13pt/1.2 "Geist", Arial, sans-serif; letter-spacing: -0.01em; }
.brandsub { margin: .5mm 0 0; color: var(--muted); font-size: 8.8pt; }
.doc { text-align: right; }
.eyebrow { margin: 0 0 1mm; color: var(--muted); font-size: 7.5pt; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; }
.doc h1 { margin: 0 0 3mm; font: 700 24pt/1 "Geist Mono", ui-monospace, monospace; letter-spacing: -0.02em; }
.docmeta { display: grid; grid-template-columns: auto auto; gap: 1.2mm 6mm; justify-content: end; margin: 0; color: var(--muted); font-size: 8.8pt; }
.docmeta dt { text-align: right; }
.docmeta dd { margin: 0; color: var(--ink); text-align: right; font-weight: 500; }
.rule { height: 1px; margin: 8mm 0 7mm; background: var(--line); }
.parties { display: grid; grid-template-columns: 1.1fr 1.1fr 1fr; gap: 8mm; margin-bottom: 8mm; }
.label { margin: 0 0 2mm; color: var(--muted); font-size: 7.2pt; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; }
.party strong { display: block; margin-bottom: .8mm; font-size: 10.5pt; font-weight: 600; line-height: 1.3; }
.party p { margin: 0; color: var(--ink-2); }
.party .muted { color: var(--muted); }
.pill { display: inline-flex; align-items: center; gap: 1.5mm; margin-top: 2mm; padding: 1mm 2.6mm; border-radius: 99px; font-size: 8pt; font-weight: 500; }
.pill::before { content: ""; width: 1.6mm; height: 1.6mm; border-radius: 99px; background: currentColor; }
.pill--paid { background: var(--ok-soft); color: var(--ok); }
.pill--due { background: var(--warn-soft); color: var(--warn); }
.pill--void { background: var(--bad-soft); color: var(--bad); }
table.items { width: 100%; border-collapse: collapse; }
.items th { padding: 0 2mm 2.5mm; border-bottom: 1.5px solid var(--ink); color: var(--muted); font-size: 7.2pt; font-weight: 600; letter-spacing: .12em; text-align: left; text-transform: uppercase; }
.items td { padding: 3.2mm 2mm; border-bottom: 1px solid var(--hair); vertical-align: top; }
.items .n { width: 8mm; color: var(--soft); font: 400 8.5pt/1.5 "Geist Mono", ui-monospace, monospace; }
.items td.n { padding-top: 3.6mm; }
.items .c { text-align: center; }
.items .r { text-align: right; white-space: nowrap; }
.item-name { font-size: 10.5pt; font-weight: 600; line-height: 1.3; }
.item-sub { margin-top: .6mm; color: var(--muted); font: 400 8.3pt/1.4 "Geist Mono", ui-monospace, monospace; }
.was { margin-right: 1.5mm; color: var(--soft); font-size: 8.3pt; text-decoration: line-through; }
.items tr { break-inside: avoid; }
.items thead { display: table-header-group; }
.bottom { display: grid; grid-template-columns: 1fr 76mm; gap: 10mm; align-items: start; margin-top: 7mm; break-inside: avoid; }
.note { padding: 4.5mm 5mm; border: 1px solid var(--line); border-radius: 3mm; background: #F8F8F6; }
.note h2 { margin: 0 0 2mm; color: var(--muted); font-size: 7.2pt; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; }
.note p { margin: 0 0 1.5mm; }
.acct { display: grid; grid-template-columns: auto 1fr; gap: .8mm 4mm; margin: 2mm 0 0; font-size: 9.2pt; }
.acct dt { color: var(--muted); }
.acct dd { margin: 0; font-weight: 500; word-break: break-word; }
.totals { width: 100%; border-collapse: collapse; }
.totals td { padding: 2mm 0; border-bottom: 1px solid var(--hair); }
.totals td:last-child { text-align: right; white-space: nowrap; }
.totals .free { color: var(--ok); font-weight: 500; }
.totals .grand td { padding: 3.5mm 0; border-top: 1px solid var(--ink); border-bottom: 1.5px solid var(--ink); }
.totals .grand td:first-child { color: var(--ink); font-size: 8pt; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; vertical-align: middle; }
.totals .grand td:last-child { font-size: 20pt; font-weight: 700; letter-spacing: -0.03em; line-height: 1; }
.totals .sub td { color: var(--ink-2); font-size: 9pt; }
.totals .due td { font-weight: 600; }
.stamp { display: inline-flex; align-items: center; gap: 2mm; margin-top: 2mm; padding: 2.5mm 4mm; border: 1.5px solid var(--ok); border-radius: 2.5mm; color: var(--ok); font: 600 8.5pt/1 "Geist Mono", ui-monospace, monospace; letter-spacing: .12em; text-transform: uppercase; transform: rotate(-2deg); }
.stamp::before { content: "✓"; }
.foot { display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; margin-top: auto; padding-top: 8mm; border-top: 1px solid var(--line); color: var(--muted); font-size: 8.5pt; }
.thanks { margin: 0 0 1mm; color: var(--ink-2); }
.policy { margin: 0 0 1mm; text-align: right; }
.contacts { grid-column: 2; margin: 0; color: var(--ink-2); text-align: right; }
.contacts span + span::before { content: "·"; margin: 0 2mm; color: var(--soft); }
.fine { grid-column: 1; grid-row: 2; margin: 0; color: var(--soft); font-size: 7.5pt; }
@media screen and (max-width: 820px) {
  .sheet { width: auto; min-height: 0; margin: 10px; padding: 22px 18px 18px; overflow-wrap: anywhere; }
  .doc h1 { font-size: 22pt; }
  .head { flex-direction: column; gap: 14px; }
  .brandmark { margin-top: 0; }
  .doc { text-align: left; }
  .docmeta { justify-content: start; }
  .docmeta dt, .docmeta dd { text-align: left; }
  .parties { grid-template-columns: 1fr; gap: 16px; }
  .bottom { grid-template-columns: 1fr; gap: 16px; }
  .foot { grid-template-columns: 1fr; }
  .policy, .contacts { grid-column: 1; text-align: left; }
  .items .n, .items th.n { display: none; }
  .items td, .items th { padding-left: 1mm; padding-right: 1mm; }
}
@media print {
  @page { size: A4; margin: 0; }
  body { background: #FFFFFF; }
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
      <div class="brandmark">
        <span class="mark" aria-hidden="true">S<span>F</span></span>
        <div><p class="brandname"><?= e($store['name']) ?></p><p class="brandsub"><?= e(implode(' · ', array_filter([$store['tagline'], $store['site']]))) ?></p></div>
      </div>
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
<?php elseif ($o['payment_status'] === 'partial'): ?>
      <span class="pill pill--due">Part paid · <?= e((string) $p['balanceDue']) ?> due</span>
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
<?php if (!$isCancelled && $o['payment_status'] === 'partial' && $p['balanceDue'] !== null): ?>
      <div class="note">
        <h2>Balance due</h2>
        <p>We have received <strong><?= e($p['amountPaid']) ?></strong> of <?= e(money((string) $o['grand_total'])) ?>. <strong><?= e($p['balanceDue']) ?></strong> is still to be paid<?= $p['collectAmount'] !== null && $o['payment_method'] === 'cod' ? ' on delivery' : '' ?>.</p>
      </div>
<?php elseif (!$isCancelled && !$p['isPaid'] && $p['collectAmount'] !== null): ?>
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
<?php if (!$isCancelled && $o['payment_status'] !== 'refunded'): ?>
<?php if (order_balance($o)['paid'] > 0): ?>
      <tr class="sub"><td>Paid</td><td><?= e($p['amountPaid']) ?></td></tr>
<?php endif; ?>
      <tr class="sub due"><td><?= order_balance($o)['paid'] > 0 ? 'Balance due' : 'Amount due' ?></td><td><?= e($p['balanceDue'] ?? money('0.00')) ?></td></tr>
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
