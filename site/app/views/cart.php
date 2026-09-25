<?php
defined('SKYFR') || exit;
$pageHeading = trim((string) ($heading ?? ''));
if ($pageHeading === '') {
    $pageHeading = 'Your Cart';
}
$cart = is_array($cart ?? null) ? $cart : ['lines' => [], 'messages' => [], 'is_empty' => true, 'item_count' => 0, 'can_checkout' => false, 'coupon' => null, 'coupon_error' => null, 'progress' => ['visible' => false, 'percent' => 0, 'message' => ''], 'free_shipping' => false, 'subtotal_display' => '', 'discount_display' => '', 'shipping_display' => '', 'grand_total_display' => '', 'remaining_display' => '', 'threshold_display' => '', 'has_sold_out' => false];
$paymentMethods = is_array($paymentMethods ?? null) ? $paymentMethods : [];
$deliveryTime = (string) setting('delivery_time', '2–4 working days');
$csrf = csrf_field();
$itemCount = (int) ($cart['item_count'] ?? 0);
$couponError = is_array($cart['coupon_error'] ?? null) ? $cart['coupon_error'] : null;
$minusIcon = '<svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M5 12h14"/></svg>';
$plusIcon = '<svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M12 5v14M5 12h14"/></svg>';
$checkIcon = '<svg class="free-ship__check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
$cartTrustItems = [
    ['banknote', 'Cash on Delivery', 'Pay the courier when your parcel arrives, anywhere in Pakistan.'],
    ['truck', 'Fast Delivery', 'Dispatched within a working day; delivered in ' . $deliveryTime . '.'],
    ['whatsapp', 'WhatsApp Support', 'A real person answers before and after your order.'],
    ['refresh', 'Easy Exchange', 'Damaged or wrong item? Tell us within 48 hours and we replace it.'],
];
?>
<section class="section section--tight">
  <div class="container">
    <header class="page-head">
      <h1 class="page-head__title"><?= e($pageHeading) ?></h1>
<?php if (!$cart['is_empty']): ?>
      <p class="page-head__intro text-muted"><?= e((string) $itemCount) ?> <?= $itemCount === 1 ? 'item' : 'items' ?> · Prices include all taxes. Delivery is calculated below.</p>
<?php endif; ?>
    </header>
<?php if ($cart['messages'] !== []): ?>
    <div class="cart-messages">
<?php foreach ($cart['messages'] as $message): ?>
      <p class="cart-message<?= ($message['type'] ?? 'info') === 'error' ? ' cart-message--error' : '' ?>" role="<?= ($message['type'] ?? 'info') === 'error' ? 'alert' : 'status' ?>"><?= e((string) $message['text']) ?></p>
<?php endforeach; ?>
    </div>
<?php endif; ?>
<?php if ($cart['is_empty']): ?>
    <div class="empty-state">
      <?php partial('icon.php', ['name' => 'cart', 'size' => 40]); ?>
      <p class="empty-state__title">Your cart is empty.</p>
      <p class="empty-state__text">Every bottle ships from Pakistan with cash on delivery, and delivery is free above <?= e((string) $cart['threshold_display']) ?>.</p>
      <div class="empty-state__action btn-row">
        <a class="btn btn--primary" href="<?= e(url('/shop')) ?>"><span class="btn__label">Shop All Fragrances</span></a>
        <a class="btn btn--text" href="<?= e(url('/best-sellers')) ?>"><span class="btn__label">Start with our best sellers</span><?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16]); ?></a>
      </div>
    </div>
<?php else: ?>
    <div class="checkout-layout">
      <div class="stack">
        <ul class="cart-lines">
<?php foreach ($cart['lines'] as $line): ?>
<?php $lineId = (int) $line['size_id']; $maxQty = max(1, (int) $line['max_qty']); $qtyInputId = 'qty-' . $lineId; ?>
          <li class="cart-line cart-line--page<?= $line['in_stock'] ? '' : ' is-unavailable' ?>" data-size-id="<?= e((string) $lineId) ?>">
            <a class="cart-line__media" href="<?= e($line['url']) ?>" tabindex="-1" aria-hidden="true"><img class="cart-line__img" src="<?= e($line['image_url']) ?>" alt="" width="200" height="250" loading="lazy" decoding="async"></a>
            <div class="cart-line__body">
              <a class="cart-line__name" href="<?= e($line['url']) ?>"><?= e($line['product_name']) ?></a>
              <span class="cart-line__total price"><?= $line['in_stock'] ? e($line['line_total_display']) : '—' ?></span>
              <p class="cart-line__meta"><span><?= e($line['size_label']) ?></span><span class="cart-line__unit"><?= e($line['unit_price_display']) ?><?php if ($line['compare_at_display'] !== null): ?> <s class="cart-line__was"><?= e($line['compare_at_display']) ?></s><?php endif; ?></span></p>
<?php if ($line['notice'] !== ''): ?>
              <p class="cart-line__warning" role="status"><?= e($line['notice']) ?></p>
<?php endif; ?>
              <div class="cart-line__controls">
<?php if ($line['in_stock']): ?>
                <form action="<?= e(url('/api/cart/update')) ?>" method="post" data-cart-reload>
                  <?= $csrf ?>
                  <input type="hidden" name="size_id" value="<?= e((string) $lineId) ?>">
                  <div class="qty qty--sm js-qty" data-max="<?= e((string) $maxQty) ?>">
                    <button class="qty__btn" type="button" data-cart-dec aria-label="Decrease quantity of <?= e($line['product_name']) ?>"><?php echo $minusIcon; ?></button>
                    <input class="qty__input" id="<?= e($qtyInputId) ?>" type="text" inputmode="numeric" pattern="[0-9]*" name="qty" value="<?= e((string) $line['qty']) ?>" data-cart-qty aria-label="Quantity of <?= e($line['product_name']) ?> <?= e($line['size_label']) ?>">
                    <button class="qty__btn" type="button" data-cart-inc aria-label="Increase quantity of <?= e($line['product_name']) ?>"<?= (int) $line['qty'] >= $maxQty ? ' disabled' : '' ?>><?php echo $plusIcon; ?></button>
                  </div>
                  <span class="cart-line__max" data-qty-max<?= (int) $line['qty'] >= $maxQty ? '' : ' hidden' ?>>Max <?= e((string) $maxQty) ?></span>
                  <button class="btn btn--ghost btn--xs js-hide-when-enhanced" type="submit"><span class="btn__label">Update</span></button>
                </form>
<?php endif; ?>
                <form action="<?= e(url('/api/cart/remove')) ?>" method="post" data-cart-reload>
                  <?= $csrf ?>
                  <input type="hidden" name="size_id" value="<?= e((string) $lineId) ?>">
                  <button class="cart-line__remove" type="submit" data-cart-remove>Remove</button>
                </form>
              </div>
            </div>
          </li>
<?php endforeach; ?>
        </ul>
        <div class="cluster cluster--between">
          <a class="btn btn--text" href="<?= e(url('/shop')) ?>"><span class="btn__label">Continue shopping</span></a>
          <p class="text-muted text-small">Delivery in <?= e($deliveryTime) ?> · Cash on delivery nationwide</p>
        </div>
      </div>
      <aside class="checkout-layout__summary">
        <div class="summary">
          <h2 class="summary__title">Order summary</h2>
<?php if (!empty($cart['progress']['visible'])): ?>
          <div class="free-ship js-free-ship<?= $cart['free_shipping'] ? ' is-unlocked' : '' ?>" role="status">
            <p class="free-ship__text"><?php if ($cart['free_shipping']): ?><?php echo $checkIcon; ?> Free delivery unlocked.<?php else: ?>You're <span class="free-ship__amount"><?= e((string) $cart['remaining_display']) ?></span> away from free delivery<?php endif; ?></p>
            <div class="free-ship__track"><div class="free-ship__fill" style="--fill:<?= e((string) (int) $cart['progress']['percent']) ?>%"></div></div>
          </div>
<?php endif; ?>
          <div class="summary__coupon">
<?php if ($cart['coupon'] !== null): ?>
            <form class="js-coupon-form" action="<?= e(url('/api/cart/coupon')) ?>" method="post" data-cart-reload>
              <?= $csrf ?>
              <input type="hidden" name="action" value="remove">
              <p class="summary__applied"><span class="summary__code"><?= e((string) $cart['coupon']['code']) ?></span> applied — you saved <?= e((string) $cart['coupon']['discount_display']) ?> <button class="btn btn--text btn--xs" type="submit"><span class="btn__label">Remove</span></button></p>
            </form>
<?php else: ?>
            <form class="js-coupon-form js-validate" action="<?= e(url('/api/cart/coupon')) ?>" method="post" data-cart-reload>
              <?= $csrf ?>
              <input type="hidden" name="action" value="apply">
              <div class="field field--inline<?= $couponError !== null ? ' has-error' : '' ?>">
                <label class="field__label" for="f-code">Coupon</label>
                <div class="field__control"><input class="field__input field__input--code" id="f-code" name="code" type="text" maxlength="40" autocomplete="off" autocapitalize="characters" spellcheck="false" value="<?= e((string) ($couponError['code'] ?? '')) ?>" aria-describedby="err-code"<?= $couponError !== null ? ' aria-invalid="true"' : '' ?>></div>
                <button class="btn btn--ghost btn--sm" type="submit"><span class="btn__label">Apply</span></button>
                <p class="field__error" id="err-code"><?= e((string) ($couponError['message'] ?? '')) ?></p>
              </div>
            </form>
<?php endif; ?>
          </div>
          <div class="summary__rows">
            <p class="summary__row"><span class="summary__label">Subtotal</span><span class="summary__value"><?= e((string) $cart['subtotal_display']) ?></span></p>
<?php if ($cart['coupon'] !== null): ?>
            <p class="summary__row"><span class="summary__label">Discount <span class="text-muted">(<?= e((string) $cart['coupon']['code']) ?>)</span></span><span class="summary__value summary__value--discount">−<?= e((string) $cart['discount_display']) ?></span></p>
<?php endif; ?>
            <p class="summary__row"><span class="summary__label">Delivery</span><span class="summary__value<?= $cart['free_shipping'] ? ' summary__value--free' : '' ?>"><?= e((string) $cart['shipping_display']) ?></span></p>
            <p class="summary__row summary__row--total"><span class="summary__label">Total</span><span class="summary__value summary__value--total"><?= e((string) $cart['grand_total_display']) ?></span></p>
          </div>
          <p class="summary__note">Inclusive of all taxes. Pay on delivery, or by bank transfer, JazzCash or Easypaisa at checkout.</p>
          <div class="summary__actions">
<?php if ($cart['can_checkout']): ?>
            <a class="btn btn--primary btn--block js-sticky-anchor" href="<?= e(url('/checkout')) ?>"><span class="btn__label">Proceed to Checkout</span></a>
<?php else: ?>
            <span class="btn btn--primary btn--block" aria-disabled="true"><span class="btn__label">Proceed to Checkout</span></span>
            <p class="text-muted text-small" role="status"><?= $cart['has_sold_out'] ? 'Remove the sold-out items to continue.' : 'Add an in-stock item to continue.' ?></p>
<?php endif; ?>
          </div>
<?php if ($paymentMethods !== []): ?>
          <p class="summary__methods"><?= e(implode(' · ', array_map(static fn (array $method): string => (string) $method['label'], $paymentMethods))) ?></p>
<?php endif; ?>
        </div>
      </aside>
    </div>
    <div class="trust trust--row sf-reveal sf-reveal--stagger">
<?php foreach ($cartTrustItems as [$trustIcon, $trustTitle, $trustText]): ?>
      <div class="trust__item">
        <?php partial('icon.php', ['name' => $trustIcon, 'size' => 24, 'class' => 'trust__icon']); ?>
        <div>
          <h3 class="trust__title"><?= e($trustTitle) ?></h3>
          <p class="trust__text"><?= e($trustText) ?></p>
        </div>
      </div>
<?php endforeach; ?>
    </div>
<?php if ($cart['can_checkout']): ?>
<div class="sticky-bar js-sticky-bar" data-sticky-when-hidden aria-label="Checkout">
  <div class="sticky-bar__info">
    <div class="sticky-bar__text">
      <span class="sticky-bar__name"><?= e((string) $itemCount) ?> <?= $itemCount === 1 ? 'item' : 'items' ?></span>
      <span class="sticky-bar__price"><?= e((string) $cart['grand_total_display']) ?></span>
    </div>
  </div>
  <a class="btn btn--primary sticky-bar__action" href="<?= e(url('/checkout')) ?>"><span class="btn__label">Checkout</span></a>
</div>
<?php endif; ?>
<?php endif; ?>
  </div>
</section>
