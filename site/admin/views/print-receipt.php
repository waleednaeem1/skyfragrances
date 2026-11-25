<?php
defined('SKYFR') || exit;
$o = $p['order'];
$isCancelled = (string) $o['status'] === 'cancelled';
$balance = order_balance($o);
$narrow = $width === 58;
$paper = $width;
$inner = $width - 8;
$logo = is_file(APP_ROOT . '/assets/img/brand/lockup-transparent-400.png') ? asset('img/brand/lockup-transparent-400.png') : '';
$returnsDays = (int) setting('returns_days', '7');
$instagram = trim((string) setting('instagram_handle', ''));
$contacts = array_values(array_filter([
    $store['phone'] !== '' ? 'Call ' . $store['phone'] : '',
    $store['whatsapp'] !== '' ? 'WhatsApp ' . $store['whatsapp'] : '',
    $instagram,
    $store['site'],
]));
$couponText = empty($o['coupon_code']) ? 'Discount' : 'Discount ' . $o['coupon_code'];
$widthUrl = static fn (int $w): string => url('/admin/orders/' . $o['order_number'] . '/receipt') . '?w=' . $w;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Receipt <?= e((string) $o['order_number']) ?> · <?= e($store['name']) ?></title>
<style>
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body { background: #E9E4DC; color: #000; font-family: "Helvetica Neue", Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.toolbar { position: sticky; top: 0; z-index: 2; display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 10px 16px; padding: 10px 16px; background: #0B0B0B; color: #EDE6DA; font-size: 14px; }
.toolbar a { color: #EDE6DA; }
.toolbar .sizes { display: inline-flex; border: 1px solid #5A534A; border-radius: 999px; overflow: hidden; }
.toolbar .sizes a { padding: 6px 14px; text-decoration: none; }
.toolbar .sizes a.is-on { background: #D4B084; color: #0B0B0B; }
.toolbar button { font: inherit; font-weight: 600; padding: 8px 18px; border: 0; border-radius: 999px; background: #D4B084; color: #0B0B0B; cursor: pointer; }
.paper { width: <?= $paper ?>mm; margin: 18px auto; background: #fff; box-shadow: 0 10px 30px -12px rgba(0,0,0,.35); }
.receipt { width: <?= $paper ?>mm; padding: 4mm 4mm 6mm; font-size: <?= $narrow ? '10.5px' : '12px' ?>; line-height: 1.35; }
.c { text-align: center; }
.logo { display: block; width: <?= $narrow ? '24mm' : '30mm' ?>; height: auto; margin: 0 auto 1.5mm; filter: grayscale(1) brightness(0); }
.store { font-size: 1.35em; font-weight: 700; letter-spacing: .04em; margin: 0 0 1mm; }
.small { font-size: .88em; }
p { margin: 0; }
.dash { border: 0; border-top: 1px dashed #000; margin: 2.5mm 0; }
.title { font-size: 1.05em; font-weight: 700; letter-spacing: .18em; margin: 0 0 1mm; }
.number { font-size: 1.6em; font-weight: 800; letter-spacing: .04em; margin: 0 0 1mm; }
.row { display: flex; justify-content: space-between; gap: 2mm; }
.row > span:last-child { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.item { margin: 0 0 1.8mm; }
.item-name { font-weight: 700; }
.item .row { font-size: .95em; }
.total { font-size: 1.35em; font-weight: 800; margin: 1mm 0; }
.status { display: block; border: 1.5px solid #000; padding: 1.5mm; margin: 2.5mm 0 0; text-align: center; font-weight: 800; letter-spacing: .12em; }
.gift { border: 1px dashed #000; padding: 2mm; margin: 2.5mm 0 0; text-align: center; }
.gift strong { display: block; font-size: 1.2em; letter-spacing: .1em; margin: 1mm 0; }
.foot { margin-top: 3mm; }
.foot p + p { margin-top: 1mm; }
@media print {
  @page { size: <?= $paper ?>mm 200mm; margin: 0; }
  body { background: #fff; }
  .toolbar { display: none !important; }
  .paper { margin: 0; box-shadow: none; }
}
</style>
</head>
<body data-receipt="<?= $paper ?>"<?= $autoprint ? ' data-autoprint' : '' ?>>
<div class="toolbar">
  <a href="<?= e(url($returnUrl)) ?>">&larr; Back to order</a>
  <span class="sizes" aria-label="Paper width"><a href="<?= e($widthUrl(80)) ?>" data-receipt-width="80" class="<?= $paper === 80 ? 'is-on' : '' ?>">80 mm</a><a href="<?= e($widthUrl(58)) ?>" data-receipt-width="58" class="<?= $paper === 58 ? 'is-on' : '' ?>">58 mm</a></span>
  <button type="button" data-print>Print receipt</button>
</div>
<div class="paper">
<main class="receipt">
  <header class="c">
<?php if ($logo !== ''): ?>
    <img class="logo" src="<?= e($logo) ?>" alt="<?= e($store['name']) ?>">
<?php else: ?>
    <p class="store"><?= e($store['name']) ?></p>
<?php endif; ?>
<?php if ($store['address'] !== ''): ?>
    <p class="small"><?= e($store['address']) ?></p>
<?php endif; ?>
  </header>
  <hr class="dash">
  <div class="c">
    <p class="title"><?= $isCancelled ? 'CANCELLED' : 'RECEIPT' ?></p>
    <p class="number"><?= e((string) $o['order_number']) ?></p>
    <p class="small"><?= e(adm_order_datetime((string) $o['created_at'])) ?></p>
  </div>
  <hr class="dash">
  <div class="row"><span>Customer</span><span><?= e((string) $o['customer_name']) ?></span></div>
<?php if (!empty($o['customer_phone'])): ?>
  <div class="row"><span>Phone</span><span><?= e((string) $o['customer_phone']) ?></span></div>
<?php endif; ?>
  <div class="row"><span>Payment</span><span><?= e(payment_method_label((string) $o['payment_method'])) ?></span></div>
<?php if ($servedBy !== ''): ?>
  <div class="row"><span>Served by</span><span><?= e($servedBy) ?></span></div>
<?php endif; ?>
  <hr class="dash">
<?php foreach ($p['items'] as $item): ?>
  <div class="item">
    <p class="item-name"><?= e((string) $item['product_name']) ?></p>
    <div class="row"><span><?= e(trim((string) $item['size_label'])) ?> · <?= (int) $item['quantity'] ?> &times; <?= e(money((string) $item['unit_price_charged'])) ?></span><span><?= e(money((string) $item['line_total'])) ?></span></div>
  </div>
<?php endforeach; ?>
  <hr class="dash">
  <div class="row"><span>Subtotal · <?= (int) $p['itemCount'] ?> <?= (int) $p['itemCount'] === 1 ? 'item' : 'items' ?></span><span><?= e(money((string) $o['subtotal'])) ?></span></div>
<?php if (money_paisa((string) $o['discount_total']) > 0): ?>
  <div class="row"><span><?= e($couponText) ?></span><span>&minus;<?= e(money((string) $o['discount_total'])) ?></span></div>
<?php endif; ?>
<?php if (money_paisa((string) $o['shipping_fee']) > 0): ?>
  <div class="row"><span>Delivery</span><span><?= e(money((string) $o['shipping_fee'])) ?></span></div>
<?php endif; ?>
<?php if (money_paisa((string) $o['cod_fee']) > 0): ?>
  <div class="row"><span>COD fee</span><span><?= e(money((string) $o['cod_fee'])) ?></span></div>
<?php endif; ?>
  <div class="row total"><span>TOTAL</span><span><?= e(money((string) $o['grand_total'])) ?></span></div>
<?php if (!$isCancelled && $o['payment_status'] !== 'refunded'): ?>
<?php if ($balance['paid'] > 0): ?>
  <div class="row"><span>Paid</span><span><?= e($p['amountPaid']) ?></span></div>
<?php endif; ?>
<?php if ($balance['due'] > 0): ?>
  <div class="row"><strong>Balance due</strong><strong><?= e((string) $p['balanceDue']) ?></strong></div>
<?php endif; ?>
<?php endif; ?>
<?php if ($isCancelled): ?>
  <span class="status">CANCELLED</span>
<?php elseif ($o['payment_status'] === 'refunded'): ?>
  <span class="status">REFUNDED</span>
<?php elseif ($p['isPaid']): ?>
  <span class="status">PAID</span>
<?php elseif ($o['payment_status'] === 'partial'): ?>
  <span class="status">PART PAID</span>
<?php elseif ($p['collectAmount'] !== null): ?>
  <span class="status">PAY ON DELIVERY</span>
<?php else: ?>
  <span class="status">PAYMENT DUE</span>
<?php endif; ?>
<?php if (is_array($reward) && !empty($reward['usable']) && !$isCancelled): ?>
  <div class="gift">
    <p>Thank you for your first order</p>
    <p><?= e((string) $reward['label']) ?> your next order with code</p>
    <strong><?= e((string) $reward['code']) ?></strong>
    <p class="small"><?= e((string) $reward['terms']) ?></p>
  </div>
<?php endif; ?>
  <footer class="foot c">
    <hr class="dash">
    <p><strong>Thank you for choosing <?= e($store['name']) ?></strong></p>
    <p class="small">Sealed, unopened bottles can be exchanged within <?= $returnsDays > 0 ? $returnsDays : 7 ?> days with this receipt.</p>
<?php if ($contacts !== []): ?>
    <p class="small"><?= e(implode(' · ', $contacts)) ?></p>
<?php endif; ?>
    <p class="small">Printed <?= e($printedAt) ?></p>
  </footer>
</main>
</div>
<script src="<?= e(asset('js/admin-print.js')) ?>" defer></script>
</body>
</html>
