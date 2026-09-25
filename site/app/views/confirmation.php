<?php
defined('SKYFR') || exit;
$pageHeading = trim((string) ($heading ?? ''));
if ($pageHeading === '') {
    $pageHeading = 'Thank you.';
}
$orderNumber = (string) ($orderNumber ?? ($order['order_number'] ?? ''));
$items = is_array($items ?? null) ? $items : [];
$totals = is_array($totals ?? null) ? $totals : ['subtotal' => '', 'discount' => null, 'coupon_code' => null, 'shipping' => 'Free', 'cod_fee' => null, 'grand_total' => ''];
$address = is_array($address ?? null) ? $address : ['name' => '', 'phone' => '', 'address' => '', 'city' => '', 'postal_code' => '', 'note' => ''];
$accountLines = is_array($accountLines ?? null) ? $accountLines : [];
$latestProof = is_array($latestProof ?? null) ? $latestProof : null;
$proofOld = array_merge(['payment_reference' => '', 'sender_name' => ''], is_array($proofOld ?? null) ? $proofOld : []);
$proofErrors = is_array($proofErrors ?? null) ? $proofErrors : [];
$notice = is_array($notice ?? null) ? $notice : null;
$isManual = !empty($isManual);
$isCod = !empty($isCod);
$isPaid = !empty($isPaid);
$isCancelled = (string) ($status ?? '') === 'cancelled';
$showAddress = !empty($showAddress);
$canUploadProof = !empty($canUploadProof);
$emailSent = !empty($emailSent) && !empty($emailAddress);
$whatsappUrl = (string) ($whatsappUrl ?? '');
$trackUrl = (string) ($trackUrl ?? url('/track'));
$confirmationUrl = (string) ($confirmationUrl ?? '');
$accessToken = (string) ($order['access_token'] ?? '');
$manualPaymentNote = trim((string) ($manualPaymentNote ?? ''));
$deliveryTime = (string) ($deliveryTime ?? '2–4 working days');
$statusPillClass = $isCancelled ? 'status-pill--danger' : (in_array((string) ($status ?? ''), ['shipped', 'delivered'], true) ? 'status-pill--success' : 'status-pill--accent');
$paymentPillClass = $isPaid ? 'status-pill--success' : ((string) ($paymentStatus ?? '') === 'failed' ? 'status-pill--danger' : 'status-pill--muted');
$paymentStatusLabel = match ((string) ($paymentStatus ?? '')) {
    'paid' => 'Paid',
    'awaiting_verification' => 'Awaiting verification',
    'failed' => 'Receipt rejected',
    'refunded' => 'Refunded',
    default => $isCod ? 'Pay on delivery' : 'Unpaid',
};
$copyIcon = '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="9" y="9" width="11" height="11" rx="1.5"/><path d="M15 9V5.5A1.5 1.5 0 0 0 13.5 4h-8A1.5 1.5 0 0 0 4 5.5v8A1.5 1.5 0 0 0 5.5 15H9"/></svg>';
$checkIcon = '<svg class="callout__icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
?>
<section class="section section--tight">
  <div class="container container--narrow">
<?php if ($notice !== null): ?>
    <div class="callout<?= ($notice['type'] ?? 'info') === 'error' ? ' callout--danger' : ' callout--success' ?>" role="<?= ($notice['type'] ?? 'info') === 'error' ? 'alert' : 'status' ?>"><p><?= e((string) $notice['text']) ?></p></div>
<?php endif; ?>
    <header class="page-head page-head--center">
      <svg class="icon text-gold-grad" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><path d="M7.5 12.5l3 3 6-6.5"/></svg>
      <h1 class="page-head__title"><?= e($pageHeading) ?></h1>
      <p class="page-head__intro"><?= $isCancelled ? 'This order was cancelled.' : 'Your order is in. Here is everything you need to keep.' ?></p>
      <p class="order-number"><?= e($orderNumber) ?> <button class="copy-btn js-copy" type="button" data-copy="<?= e($orderNumber) ?>" aria-label="Copy order number"><?php echo $copyIcon; ?></button></p>
      <p class="text-muted text-small">Placed <?= e((string) ($placedAt ?? '')) ?> · <span class="status-pill <?= e($statusPillClass) ?>"><?= e((string) ($statusLabel ?? '')) ?></span> <span class="status-pill <?= e($paymentPillClass) ?>"><?= e($paymentStatusLabel) ?></span></p>
<?php if ($emailSent): ?>
      <p class="text-muted">We've sent a confirmation to <strong><?= e((string) $emailAddress) ?></strong>.</p>
<?php else: ?>
      <p class="text-muted">Save this page — it's your receipt. <?= $confirmationUrl !== '' ? 'The link works any time.' : '' ?></p>
<?php endif; ?>
      <div class="btn-row">
<?php if ($whatsappUrl !== ''): ?>
        <a class="btn btn--primary btn--whatsapp" href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener"><?php partial('icon.php', ['name' => 'whatsapp', 'size' => 20]); ?><span class="btn__label">Message us about this order</span></a>
<?php endif; ?>
        <a class="btn btn--ghost" href="<?= e($trackUrl) ?>"><span class="btn__label">Track your order</span></a>
      </div>
    </header>
<?php if ($isCod && !$isCancelled): ?>
    <div class="callout callout--success" role="status"><?php echo $checkIcon; ?><p>Please keep <strong class="price"><?= e((string) ($grandTotalDisplay ?? '')) ?></strong> ready for the courier. Delivery usually takes <?= e($deliveryTime) ?>.</p></div>
<?php endif; ?>
<?php if ($isManual && !$isCancelled): ?>
    <div class="panel <?= $isPaid ? 'panel--success' : ((string) ($paymentStatus ?? '') === 'failed' ? 'panel--danger' : 'panel--accent') ?>">
      <p class="panel__eyebrow"><?= e((string) ($paymentMethodLabel ?? 'Payment')) ?></p>
<?php if ($isPaid): ?>
      <h2 class="panel__title">Payment received — thank you.</h2>
      <p class="text-muted">Your order is confirmed and will be dispatched within a working day.</p>
<?php elseif ((string) ($paymentStatus ?? '') === 'failed'): ?>
      <h2 class="panel__title">We couldn't verify that receipt.</h2>
<?php if ($latestProof !== null && trim((string) ($latestProof['review_note'] ?? '')) !== ''): ?>
      <p><?= e((string) $latestProof['review_note']) ?></p>
<?php endif; ?>
      <p class="text-muted">Please upload a clear screenshot of the completed transfer below, or message us on WhatsApp.</p>
<?php else: ?>
      <h2 class="panel__title">We're verifying your payment.</h2>
      <p class="text-muted">You'll get a confirmation once it clears — usually within a few hours.</p>
<?php endif; ?>
<?php if (!empty($transactionRef)): ?>
      <dl class="data-list">
        <div class="data-list__row"><dt class="data-list__key">Transaction ID</dt><dd class="data-list__val"><?= e((string) $transactionRef) ?></dd></div>
<?php if ($latestProof !== null): ?>
        <div class="data-list__row"><dt class="data-list__key">Receipt</dt><dd class="data-list__val"><?= !empty($latestProof['file_missing']) ? 'Not received — please upload it again or send it on WhatsApp' : 'Uploaded ' . e((string) $latestProof['created_at']) ?></dd></div>
<?php endif; ?>
      </dl>
<?php endif; ?>
<?php if (!$isPaid && $accountLines !== []): ?>
      <p class="text-small text-muted">Not paid yet? Transfer exactly <strong class="price"><?= e((string) ($grandTotalDisplay ?? '')) ?></strong> to:</p>
      <dl class="data-list">
<?php foreach ($accountLines as $label => $value): ?>
        <div class="data-list__row"><dt class="data-list__key"><?= e((string) $label) ?></dt><dd class="data-list__val data-list__val--copy"><?= e((string) $value) ?> <button class="copy-btn js-copy" type="button" data-copy="<?= e((string) $value) ?>" aria-label="Copy <?= e((string) $label) ?>"><?php echo $copyIcon; ?></button></dd></div>
<?php endforeach; ?>
      </dl>
<?php if ($manualPaymentNote !== ''): ?>
      <p class="text-muted text-small"><?= e($manualPaymentNote) ?></p>
<?php endif; ?>
<?php endif; ?>
    </div>
<?php endif; ?>
<?php if ($canUploadProof): ?>
    <form class="form js-validate" method="post" action="<?= e(url('/order/' . eu($orderNumber))) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="t" value="<?= e($accessToken) ?>">
      <?php echo $trapField ?? ''; ?>
      <fieldset class="form__section">
        <legend class="form__section-title">Upload your payment receipt</legend>
        <div class="form__summary js-form-summary" role="alert" hidden></div>
        <div class="field<?= isset($proofErrors['payment_reference']) ? ' has-error' : '' ?>">
          <label class="field__label" for="f-ref">Transaction ID</label>
          <div class="field__control"><input class="field__input field__input--code" id="f-ref" name="payment_reference" type="text" value="<?= e((string) $proofOld['payment_reference']) ?>" required maxlength="30" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-describedby="err-ref"<?= isset($proofErrors['payment_reference']) ? ' aria-invalid="true"' : '' ?>></div>
          <p class="field__hint">From your payment receipt, 5 to 30 letters and digits.</p>
          <p class="field__error" id="err-ref"><?= e($proofErrors['payment_reference'] ?? '') ?></p>
        </div>
        <div class="field">
          <label class="field__label" for="f-sender">Sender name <span class="field__optional">optional</span></label>
          <div class="field__control"><input class="field__input" id="f-sender" name="sender_name" type="text" value="<?= e((string) $proofOld['sender_name']) ?>" maxlength="120" autocomplete="off" aria-describedby="err-sender"></div>
          <p class="field__error" id="err-sender"></p>
        </div>
        <div class="field<?= isset($proofErrors['payment_proof']) ? ' has-error' : '' ?>">
          <span class="field__label" id="lbl-proof">Payment screenshot</span>
          <label class="upload" for="f-proof">
            <input class="upload__input js-upload-input" id="f-proof" name="payment_proof" type="file" accept="image/jpeg,image/png,image/webp" required aria-labelledby="lbl-proof" aria-describedby="err-proof"<?= isset($proofErrors['payment_proof']) ? ' aria-invalid="true"' : '' ?>>
            <img class="upload__preview" alt="" hidden>
            <span class="upload__name"></span>
            Tap to add your payment screenshot
          </label>
          <p class="field__hint">JPG, PNG or WebP, under 5 MB.</p>
          <p class="field__error" id="err-proof"><?= e($proofErrors['payment_proof'] ?? '') ?></p>
        </div>
        <div class="form__actions">
          <button class="btn btn--primary" type="submit"><span class="btn__label">Send receipt</span></button>
        </div>
      </fieldset>
    </form>
<?php endif; ?>
    <div class="summary">
      <h2 class="summary__title">Your order</h2>
      <div class="summary__lines">
<?php foreach ($items as $item): ?>
        <div class="summary__line">
          <span class="summary__line-media"><a href="<?= e((string) $item['url']) ?>" tabindex="-1" aria-hidden="true"><img class="summary__line-img" src="<?= e((string) $item['image_url']) ?>" alt="" width="200" height="250" loading="lazy" decoding="async"></a></span>
          <span><a class="summary__line-name" href="<?= e((string) $item['url']) ?>"><?= e((string) $item['product_name']) ?></a><br><span class="summary__line-meta"><?= e((string) $item['size_label']) ?> × <?= e((string) $item['quantity']) ?> · <?= e((string) $item['unit_price_display']) ?><?php if ($item['compare_at_display'] !== null): ?> <s><?= e((string) $item['compare_at_display']) ?></s><?php endif; ?></span></span>
          <span class="summary__line-total"><?= e((string) $item['line_total_display']) ?></span>
        </div>
<?php endforeach; ?>
      </div>
      <div class="summary__rows">
        <p class="summary__row"><span class="summary__label">Subtotal</span><span class="summary__value"><?= e((string) $totals['subtotal']) ?></span></p>
<?php if ($totals['discount'] !== null): ?>
        <p class="summary__row"><span class="summary__label">Discount<?= $totals['coupon_code'] !== null ? ' <span class="text-muted">(' . e((string) $totals['coupon_code']) . ')</span>' : '' ?></span><span class="summary__value summary__value--discount">−<?= e((string) $totals['discount']) ?></span></p>
<?php endif; ?>
        <p class="summary__row"><span class="summary__label">Delivery</span><span class="summary__value<?= $totals['shipping'] === 'Free' ? ' summary__value--free' : '' ?>"><?= e((string) $totals['shipping']) ?></span></p>
<?php if ($totals['cod_fee'] !== null): ?>
        <p class="summary__row"><span class="summary__label">COD fee</span><span class="summary__value"><?= e((string) $totals['cod_fee']) ?></span></p>
<?php endif; ?>
        <p class="summary__row summary__row--total"><span class="summary__label">Total</span><span class="summary__value summary__value--total"><?= e((string) $totals['grand_total']) ?></span></p>
      </div>
      <p class="summary__note">Inclusive of all taxes. Paid by <?= e((string) ($paymentMethodLabel ?? '')) ?>.</p>
    </div>
<?php if ($showAddress): ?>
    <div class="panel">
      <p class="panel__eyebrow">Delivering to</p>
      <dl class="data-list">
        <div class="data-list__row"><dt class="data-list__key">Name</dt><dd class="data-list__val"><?= e((string) $address['name']) ?></dd></div>
        <div class="data-list__row"><dt class="data-list__key">Phone</dt><dd class="data-list__val"><?= e((string) $address['phone']) ?></dd></div>
        <div class="data-list__row"><dt class="data-list__key">Address</dt><dd class="data-list__val"><?= nl2br(e((string) $address['address'])) ?><br><?= e((string) $address['city']) ?><?= trim((string) $address['postal_code']) !== '' ? ' ' . e((string) $address['postal_code']) : '' ?></dd></div>
<?php if (trim((string) $address['note']) !== ''): ?>
        <div class="data-list__row"><dt class="data-list__key">Your note</dt><dd class="data-list__val"><?= nl2br(e((string) $address['note'])) ?></dd></div>
<?php endif; ?>
      </dl>
      <p class="text-muted text-small">Need to change anything? Message us on WhatsApp before the parcel ships.</p>
    </div>
<?php endif; ?>
    <div class="cluster cluster--center">
      <a class="btn btn--text" href="<?= e(url('/shop')) ?>"><span class="btn__label">Continue shopping</span><?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16]); ?></a>
    </div>
  </div>
</section>
