<?php
defined('SKYFR') || exit;
$pageHeading = trim((string) ($heading ?? ''));
if ($pageHeading === '') {
    $pageHeading = 'Checkout';
}
$cart = is_array($cart ?? null) ? $cart : ['lines' => [], 'messages' => [], 'coupon' => null, 'coupon_error' => null, 'free_shipping' => false, 'subtotal_display' => '', 'discount_display' => '', 'shipping_display' => '', 'grand_total_display' => '', 'can_checkout' => false];
$paymentMethods = is_array($paymentMethods ?? null) ? $paymentMethods : [];
$orderingPaused = !empty($orderingPaused);
$codBlockedMessage = isset($codBlockedMessage) && is_string($codBlockedMessage) && $codBlockedMessage !== '' ? $codBlockedMessage : null;
$old = array_merge(['customer_name' => '', 'customer_phone' => '', 'customer_email' => '', 'city' => '', 'address' => '', 'postal_code' => '', 'customer_note' => '', 'payment_method' => '', 'payment_reference' => '', 'sender_name' => ''], is_array($old ?? null) ? $old : []);
$errors = is_array($errors ?? null) ? $errors : [];
$notice = is_array($notice ?? null) ? $notice : null;
$cities = is_array($cities ?? null) ? $cities : [];
$manualPaymentNote = trim((string) ($manualPaymentNote ?? ''));
$deliveryTime = (string) ($deliveryTime ?? '2–4 working days');
$whatsappDigits = preg_replace('/\D+/', '', (string) ($whatsapp ?? ''));
$whatsappHref = $whatsappDigits === '' ? '' : 'https://wa.me/' . $whatsappDigits . '?text=' . rawurlencode("Assalam-o-Alaikum, I would like to place an order with Sky Fragrances.");
$hasManual = false;
foreach ($paymentMethods as $method) {
    if (!empty($method['is_manual'])) {
        $hasManual = true;
    }
}
$couponError = is_array($cart['coupon_error'] ?? null) ? $cart['coupon_error'] : null;
$csrf = csrf_field();
$flaggedSizeId = (int) ($notice['size_id'] ?? 0);
$itemCount = 0;
foreach ($cart['lines'] as $line) {
    $itemCount += (int) ($line['qty'] ?? 0);
}
$fieldClass = static fn (string $key): string => 'field' . (isset($errors[$key]) ? ' has-error' : '');
$invalid = static fn (string $key): string => isset($errors[$key]) ? ' aria-invalid="true"' : '';
$copyIcon = '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="9" y="9" width="11" height="11" rx="1.5"/><path d="M15 9V5.5A1.5 1.5 0 0 0 13.5 4h-8A1.5 1.5 0 0 0 4 5.5v8A1.5 1.5 0 0 0 5.5 15H9"/></svg>';
?>
<section class="section section--tight">
  <div class="container">
    <header class="page-head">
      <h1 class="page-head__title"><?= e($pageHeading) ?></h1>
      <p class="page-head__intro text-muted">No account needed. Delivery in <?= e($deliveryTime) ?>, anywhere in Pakistan.</p>
    </header>
<?php if ($orderingPaused): ?>
    <div class="panel panel--accent">
      <p class="panel__eyebrow">Ordering</p>
      <h2 class="panel__title">Online ordering is paused. Please order on WhatsApp.</h2>
      <p class="text-muted">Send us your cart and delivery details and we will confirm your order straight away.</p>
      <div class="btn-row">
<?php if ($whatsappHref !== ''): ?>
        <a class="btn btn--primary btn--whatsapp" href="<?= e($whatsappHref) ?>" target="_blank" rel="noopener"><?php partial('icon.php', ['name' => 'whatsapp', 'size' => 20]); ?><span class="btn__label">Order on WhatsApp</span></a>
<?php endif; ?>
        <a class="btn btn--text" href="<?= e(url('/cart')) ?>"><span class="btn__label">Back to cart</span></a>
      </div>
    </div>
  </div>
</section>
<?php return; ?>
<?php endif; ?>
<?php if ($notice !== null): ?>
    <div class="callout callout--danger" role="alert"><p><?= e((string) $notice['text']) ?></p></div>
<?php endif; ?>
<?php foreach ($cart['messages'] as $message): ?>
<?php if (($message['key'] ?? '') === 'sold_out' && $notice !== null) { continue; } ?>
    <div class="callout<?= ($message['type'] ?? 'info') === 'error' ? ' callout--danger' : '' ?>" role="<?= ($message['type'] ?? 'info') === 'error' ? 'alert' : 'status' ?>"><p><?= e((string) $message['text']) ?></p></div>
<?php endforeach; ?>
    <div class="checkout-layout">
      <details class="summary-peek">
        <summary class="summary-peek__toggle">
          <span class="summary-peek__label"><?= e((string) $itemCount) ?> <?= $itemCount === 1 ? 'item' : 'items' ?> · <span class="summary-peek__total"><?= e((string) $cart['grand_total_display']) ?></span></span>
          <span class="summary-peek__cue summary-peek__cue--closed">Show order</span>
          <span class="summary-peek__cue summary-peek__cue--open">Hide</span>
        </summary>
        <div class="summary-peek__body">
          <?php partial('summary-lines.php', ['lines' => $cart['lines'], 'flaggedSizeId' => $flaggedSizeId]); ?>
        </div>
      </details>
      <form class="form js-validate" id="checkout-form" method="post" action="<?= e(url('/checkout')) ?>" enctype="multipart/form-data">
        <?= $csrf ?>
        <input type="hidden" name="idem_key" value="<?= e((string) ($idemKey ?? '')) ?>">
        <input type="hidden" name="price_token" value="<?= e((string) ($priceToken ?? '')) ?>">
        <input type="hidden" name="price_total" value="<?= e((string) (int) ($priceTotal ?? 0)) ?>">
        <?php echo $trapField ?? ''; ?>
        <div class="form__summary js-form-summary" role="alert"<?= isset($errors['form']) ? '' : ' hidden' ?>><?= isset($errors['form']) ? e($errors['form']) : '' ?></div>
        <fieldset class="form__section">
          <legend class="form__section-title"><span class="form__section-num">1</span>Contact</legend>
          <div class="<?= e($fieldClass('customer_name')) ?>">
            <label class="field__label" for="f-name">Full name</label>
            <div class="field__control"><input class="field__input" id="f-name" name="customer_name" type="text" value="<?= e($old['customer_name']) ?>" required minlength="2" maxlength="120" autocomplete="name" aria-describedby="err-name"<?= $invalid('customer_name') ?>></div>
            <p class="field__error" id="err-name"><?= e($errors['customer_name'] ?? '') ?></p>
          </div>
          <div class="form__row">
            <div class="<?= e($fieldClass('customer_phone')) ?>">
              <label class="field__label" for="f-phone">Mobile number</label>
              <div class="field__control"><input class="field__input" id="f-phone" name="customer_phone" type="tel" value="<?= e($old['customer_phone']) ?>" required maxlength="20" inputmode="tel" autocomplete="tel" placeholder="0300 1234567" aria-describedby="err-phone"<?= $invalid('customer_phone') ?>></div>
              <p class="field__hint">We confirm every order by call or WhatsApp.</p>
              <p class="field__error" id="err-phone"><?= e($errors['customer_phone'] ?? '') ?></p>
            </div>
            <div class="<?= e($fieldClass('customer_email')) ?>">
              <label class="field__label" for="f-email">Email <span class="field__optional">optional</span></label>
              <div class="field__control"><input class="field__input" id="f-email" name="customer_email" type="email" value="<?= e($old['customer_email']) ?>" maxlength="190" inputmode="email" autocomplete="email" aria-describedby="err-email"<?= $invalid('customer_email') ?>></div>
              <p class="field__hint">For your receipt and shipping updates.</p>
              <p class="field__error" id="err-email"><?= e($errors['customer_email'] ?? '') ?></p>
            </div>
          </div>
        </fieldset>
        <fieldset class="form__section">
          <legend class="form__section-title"><span class="form__section-num">2</span>Delivery</legend>
          <div class="form__row">
            <div class="<?= e($fieldClass('city')) ?>">
              <label class="field__label" for="f-city">City</label>
              <div class="field__control"><input class="field__input" id="f-city" name="city" type="text" value="<?= e($old['city']) ?>" required minlength="2" maxlength="80" list="city-list" autocomplete="address-level2" aria-describedby="err-city"<?= $invalid('city') ?>></div>
              <datalist id="city-list">
<?php foreach ($cities as $city): ?>
                <option value="<?= e((string) $city) ?>"></option>
<?php endforeach; ?>
              </datalist>
              <p class="field__error" id="err-city"><?= e($errors['city'] ?? '') ?></p>
            </div>
            <div class="<?= e($fieldClass('postal_code')) ?>">
              <label class="field__label" for="f-postal">Postal code <span class="field__optional">optional</span></label>
              <div class="field__control"><input class="field__input" id="f-postal" name="postal_code" type="text" value="<?= e($old['postal_code']) ?>" maxlength="12" inputmode="numeric" autocomplete="postal-code" aria-describedby="err-postal"<?= $invalid('postal_code') ?>></div>
              <p class="field__error" id="err-postal"><?= e($errors['postal_code'] ?? '') ?></p>
            </div>
          </div>
          <div class="<?= e($fieldClass('address')) ?>">
            <label class="field__label" for="f-address">Delivery address</label>
            <div class="field__control"><textarea class="field__input field__input--textarea" id="f-address" name="address" rows="3" required minlength="10" maxlength="400" autocomplete="street-address" placeholder="House, street, area" aria-describedby="err-address"<?= $invalid('address') ?>><?= e($old['address']) ?></textarea></div>
            <p class="field__error" id="err-address"><?= e($errors['address'] ?? '') ?></p>
          </div>
          <div class="<?= e($fieldClass('customer_note')) ?>">
            <label class="field__label" for="f-note">Order notes <span class="field__optional">optional</span></label>
            <div class="field__control"><textarea class="field__input field__input--textarea" id="f-note" name="customer_note" rows="2" maxlength="500" data-maxlength-count="cnt-note" placeholder="Landmark, delivery time, gift message" aria-describedby="err-note cnt-note"<?= $invalid('customer_note') ?>><?= e($old['customer_note']) ?></textarea></div>
            <p class="field__error" id="err-note"><?= e($errors['customer_note'] ?? '') ?></p>
            <p class="field__count" id="cnt-note"></p>
          </div>
        </fieldset>
        <fieldset class="form__section">
          <legend class="form__section-title"><span class="form__section-num">3</span>Payment</legend>
          <div class="<?= e($fieldClass('payment_method')) ?>">
          <div class="choice-group" role="radiogroup" aria-describedby="err-payment">
<?php foreach ($paymentMethods as $method): ?>
<?php $key = (string) $method['key']; $isCod = $key === 'cod'; $disabled = $isCod && $codBlockedMessage !== null; $checked = $old['payment_method'] === $key && !$disabled; ?>
            <label class="choice">
              <input class="choice__input" id="pm-<?= e($key) ?>" type="radio" name="payment_method" value="<?= e($key) ?>"<?= $checked ? ' checked' : '' ?><?= $disabled ? ' disabled' : '' ?> required<?= $invalid('payment_method') ?>>
              <span class="choice__card">
                <span class="choice__dot" aria-hidden="true"></span>
                <span class="choice__title"><?= e((string) $method['label']) ?></span>
                <span class="choice__text"><?= $disabled ? e($codBlockedMessage) : e((string) $method['copy']) ?></span>
<?php if (!empty($method['is_manual'])): ?>
                <span class="choice__badge status-pill status-pill--muted">Prepaid</span>
                <span class="choice__detail">
                  <span class="text-small">Transfer exactly <strong class="price"><?= e((string) $cart['grand_total_display']) ?></strong> to:</span>
                  <dl class="data-list">
<?php foreach ($method['account_lines'] as $label => $value): ?>
                    <div class="data-list__row"><dt class="data-list__key"><?= e((string) $label) ?></dt><dd class="data-list__val data-list__val--copy"><?= e((string) $value) ?> <button class="copy-btn js-copy" type="button" data-copy="<?= e((string) $value) ?>" aria-label="Copy <?= e((string) $label) ?>"><?php echo $copyIcon; ?></button></dd></div>
<?php endforeach; ?>
                  </dl>
<?php if ($manualPaymentNote !== ''): ?>
                  <span class="text-muted text-small"><?= e($manualPaymentNote) ?></span>
<?php endif; ?>
                </span>
<?php endif; ?>
              </span>
            </label>
<?php endforeach; ?>
          </div>
          <p class="field__error" id="err-payment"><?= e($errors['payment_method'] ?? '') ?></p>
          </div>
<?php if ($hasManual): ?>
          <div class="panel panel--sunken js-manual-panel"<?= $old['payment_method'] === 'cod' ? ' hidden' : '' ?>>
            <p class="panel__eyebrow">Paying by transfer?</p>
            <p class="text-muted text-small">Only for Bank Transfer, JazzCash or Easypaisa. Make the payment first, then add the transaction ID and a screenshot of the receipt here. Skip this for Cash on Delivery.</p>
            <div class="<?= e($fieldClass('payment_reference')) ?>">
              <label class="field__label" for="f-ref">Transaction ID</label>
              <div class="field__control"><input class="field__input field__input--code" id="f-ref" name="payment_reference" type="text" value="<?= e($old['payment_reference']) ?>" maxlength="30" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-describedby="err-ref"<?= $invalid('payment_reference') ?>></div>
              <p class="field__hint">From your payment receipt, 5 to 30 letters and digits.</p>
              <p class="field__error" id="err-ref"><?= e($errors['payment_reference'] ?? '') ?></p>
            </div>
            <div class="field">
              <label class="field__label" for="f-sender">Sender name <span class="field__optional">optional</span></label>
              <div class="field__control"><input class="field__input" id="f-sender" name="sender_name" type="text" value="<?= e($old['sender_name']) ?>" maxlength="120" autocomplete="off" aria-describedby="err-sender"></div>
              <p class="field__hint">If the account you paid from is in a different name.</p>
              <p class="field__error" id="err-sender"></p>
            </div>
            <div class="<?= e($fieldClass('payment_proof')) ?>">
              <span class="field__label" id="lbl-proof">Payment screenshot</span>
              <label class="upload" for="f-proof">
                <input class="upload__input js-upload-input" id="f-proof" name="payment_proof" type="file" accept="image/jpeg,image/png,image/webp" aria-labelledby="lbl-proof" aria-describedby="err-proof"<?= $invalid('payment_proof') ?>>
                <img class="upload__preview" alt="" hidden>
                <span class="upload__name"></span>
                Tap to add your payment screenshot
              </label>
              <p class="field__hint">JPG, PNG or WebP, under 5 MB.</p>
              <p class="field__error" id="err-proof"><?= e($errors['payment_proof'] ?? '') ?></p>
            </div>
            <p class="text-muted text-small">Your order is confirmed once we verify the payment — usually within a few hours.</p>
          </div>
<?php endif; ?>
        </fieldset>
        <div class="form__actions">
          <button class="btn btn--primary btn--block" type="submit"<?= $cart['can_checkout'] ? '' : ' disabled' ?>><span class="btn__label">Place Order · <?= e((string) $cart['grand_total_display']) ?></span></button>
          <p class="text-muted text-small">By placing your order you agree to our <a href="<?= e(url('/terms')) ?>">terms</a> and <a href="<?= e(url('/returns')) ?>">exchange policy</a>. Delivery in <?= e($deliveryTime) ?>.</p>
        </div>
      </form>
      <aside class="checkout-layout__summary">
        <div class="summary">
          <h2 class="summary__title">Order summary</h2>
          <?php partial('summary-lines.php', ['lines' => $cart['lines'], 'flaggedSizeId' => $flaggedSizeId]); ?>
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
          <p class="summary__note">Inclusive of all taxes. <a href="<?= e(url('/cart')) ?>">Edit cart</a></p>
          <p class="summary__methods">Cash on delivery · Delivery in <?= e($deliveryTime) ?> · WhatsApp support</p>
        </div>
      </aside>
    </div>
  </div>
</section>
