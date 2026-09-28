<?php
defined('SKYFR') || exit;
$cartCount = function_exists('cart_count') ? (int) cart_count() : 0;
?>
<div class="drawer-overlay js-cart-overlay" hidden></div>
<aside id="cart-drawer" class="drawer drawer--cart js-cart-drawer" role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title" aria-hidden="true" hidden>
  <div class="drawer__head">
    <h2 id="cart-drawer-title" class="drawer__title">Your Cart (<span class="js-cart-count"><?= e((string) $cartCount) ?></span>)</h2>
    <button class="drawer__close js-cart-close" type="button" aria-label="Close cart">
      <?php partial('icon.php', ['name' => 'close', 'size' => 20]); ?>
    </button>
  </div>
  <div class="drawer__body js-cart-body" data-count="<?= e((string) $cartCount) ?>"<?= $cartCount > 0 ? ' aria-busy="true"' : '' ?>>
<?php if ($cartCount === 0): ?>
    <p class="drawer__empty">Your cart is empty.</p>
    <p class="drawer__empty-copy">Made for Pakistan, delivered nationwide. Start with the ones people keep coming back to.</p>
    <div class="drawer__empty-action"><a class="btn btn--ghost" href="<?= e(url('/shop')) ?>"><span class="btn__label">Shop All Fragrances</span></a></div>
<?php else: ?>
<?php for ($skeleton = 0; $skeleton < min(3, $cartCount); $skeleton++): ?>
    <div class="skeleton-line" aria-hidden="true"><span class="skeleton skeleton--thumb"></span><span class="skeleton-line__text"><span class="skeleton skeleton--line"></span><span class="skeleton skeleton--line-short"></span></span></div>
<?php endfor; ?>
    <p class="u-sr-only">Loading your cart.</p>
    <p class="drawer__note"><a class="btn btn--text" href="<?= e(url('/cart')) ?>"><span class="btn__label">View your cart</span></a></p>
<?php endif; ?>
  </div>
  <div class="drawer__foot js-cart-foot"<?= $cartCount === 0 ? ' hidden' : '' ?>>
    <p class="drawer__subtotal"><span>Subtotal</span><span class="drawer__subtotal-value js-cart-subtotal"><?= e(money('0')) ?></span></p>
    <a class="btn btn--primary btn--block" href="<?= e(url('/checkout')) ?>"><span class="btn__label">Checkout</span></a>
    <a class="btn btn--text" href="<?= e(url('/cart')) ?>"><span class="btn__label">View Cart</span></a>
    <p class="drawer__note">Delivery and any coupon are worked out at checkout. Cash on delivery available nationwide.</p>
  </div>
  <p class="u-sr-only js-cart-live" aria-live="polite"></p>
</aside>
