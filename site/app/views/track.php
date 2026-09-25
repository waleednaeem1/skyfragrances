<?php
defined('SKYFR') || exit;
$pageHeading = trim((string) ($heading ?? ''));
if ($pageHeading === '') {
    $pageHeading = 'Track Your Order';
}
$order = is_array($order ?? null) ? $order : null;
$orderNumberInput = (string) ($orderNumberInput ?? '');
$phoneInput = (string) ($phoneInput ?? '');
$error = isset($error) && is_string($error) && $error !== '' ? $error : null;
$prefilled = !empty($prefilled);
$whatsappUrl = (string) ($whatsappUrl ?? '');
$formHasError = $error !== null;
$checkIcon = '<svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
$copyIcon = '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="9" y="9" width="11" height="11" rx="1.5"/><path d="M15 9V5.5A1.5 1.5 0 0 0 13.5 4h-8A1.5 1.5 0 0 0 4 5.5v8A1.5 1.5 0 0 0 5.5 15H9"/></svg>';
?>
<section class="section section--tight">
  <div class="container container--narrow">
    <header class="page-head page-head--center">
      <h1 class="page-head__title"><?= e($pageHeading) ?></h1>
      <p class="page-head__intro">Enter your order number and the phone number you ordered with. Both are on your confirmation page.</p>
    </header>
<?php if ($prefilled && $order === null && $error === null): ?>
    <div class="callout" role="status"><?php echo $checkIcon; ?><p>We have filled in your order number. Add the phone number you used when ordering to see its progress.</p></div>
<?php endif; ?>
<?php if ($error !== null): ?>
    <div class="callout callout--danger" role="alert"><p><?= e($error) ?></p></div>
<?php endif; ?>
    <form class="form js-validate" method="post" action="<?= e(url('/track')) ?>">
      <?= csrf_field() ?>
      <?php echo $trapField ?? ''; ?>
      <div class="form__summary js-form-summary" role="alert" hidden></div>
      <div class="form__row">
        <div class="field<?= $formHasError ? ' has-error' : '' ?>">
          <label class="field__label" for="f-order">Order number</label>
          <div class="field__control"><input class="field__input field__input--code" id="f-order" name="order_number" type="text" value="<?= e($orderNumberInput) ?>" required maxlength="16" pattern="[Ss][Ff]-[0-9]{6}-[A-Za-z0-9]{4}" placeholder="SF-260925-K7QF" autocomplete="off" autocapitalize="characters" spellcheck="false" data-msg-pattern="Order numbers look like SF-260925-K7QF." aria-describedby="err-order"<?= $formHasError ? ' aria-invalid="true"' : '' ?><?= $prefilled || $order !== null ? '' : ' data-autofocus' ?>></div>
          <p class="field__error" id="err-order"></p>
        </div>
        <div class="field<?= $formHasError ? ' has-error' : '' ?>">
          <label class="field__label" for="f-phone">Phone number</label>
          <div class="field__control"><input class="field__input" id="f-phone" name="phone" type="tel" value="<?= e($phoneInput) ?>" required maxlength="20" inputmode="tel" autocomplete="tel" placeholder="0300 1234567" aria-describedby="err-phone"<?= $formHasError ? ' aria-invalid="true"' : '' ?>></div>
          <p class="field__hint">Any format works: 0300 1234567 or +92 300 1234567.</p>
          <p class="field__error" id="err-phone"></p>
        </div>
      </div>
      <div class="form__actions">
        <button class="btn btn--primary btn--block" type="submit"><span class="btn__label"><?= $order === null ? 'Track order' : 'Check again' ?></span></button>
<?php if ($whatsappUrl !== ''): ?>
        <a class="btn btn--text btn--whatsapp" href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener"><?php partial('icon.php', ['name' => 'whatsapp', 'size' => 18]); ?><span class="btn__label">Need help? Message us</span></a>
<?php endif; ?>
      </div>
    </form>
<?php if ($order !== null): ?>
    <div class="divider"></div>
    <section class="stack" aria-labelledby="track-result-title">
      <header class="page-head page-head--center">
        <p class="u-track text-muted">Order status</p>
        <h2 class="page-head__title h2" id="track-result-title"><?= $order['first_name'] !== '' ? 'Hello, ' . e($order['first_name']) . '.' : 'Your order' ?></h2>
        <p class="order-number"><?= e($order['order_number']) ?></p>
        <p class="text-muted text-small">Placed <?= e($order['placed_at']) ?> · <?= e((string) $order['item_count']) ?> <?= $order['item_count'] === 1 ? 'item' : 'items' ?> · Delivery to <?= e($order['city']) ?></p>
      </header>
<?php if ($order['is_cancelled']): ?>
      <div class="panel panel--sunken">
        <p class="panel__eyebrow"><span class="status-pill status-pill--danger">Cancelled</span></p>
        <h3 class="panel__title">This order was cancelled<?= $order['cancelled_at'] !== '' ? ' on ' . e($order['cancelled_at']) : '' ?>.</h3>
        <p class="text-muted">If this is unexpected, or you would like to place the order again, message us and we will sort it out.</p>
<?php if ($whatsappUrl !== ''): ?>
        <div class="btn-row"><a class="btn btn--ghost btn--whatsapp" href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener"><?php partial('icon.php', ['name' => 'whatsapp', 'size' => 18]); ?><span class="btn__label">Message us on WhatsApp</span></a></div>
<?php endif; ?>
      </div>
<?php else: ?>
      <ol class="timeline">
<?php foreach ($order['timeline'] as $step): ?>
        <li class="timeline__step<?= $step['state'] === 'done' ? ' is-done' : ($step['state'] === 'current' ? ' is-current' : '') ?>"<?= $step['state'] === 'current' ? ' aria-current="step"' : '' ?>>
          <span class="timeline__dot" aria-hidden="true"></span>
          <span class="timeline__label"><?= e($step['label']) ?></span>
<?php if ($step['date'] !== ''): ?>
          <span class="timeline__date"><?= e($step['date']) ?></span>
<?php endif; ?>
        </li>
<?php endforeach; ?>
      </ol>
<?php if ($order['status_copy'] !== ''): ?>
      <div class="callout" role="status"><?php echo $checkIcon; ?><p><?= e($order['status_copy']) ?><?= $order['status'] === 'shipped' && $order['delivery_time'] !== '' ? ' Delivery usually takes ' . e($order['delivery_time']) . '.' : '' ?></p></div>
<?php endif; ?>
<?php if ($order['courier_name'] !== null || $order['tracking_number'] !== null): ?>
      <div class="panel panel--accent">
        <p class="panel__eyebrow">Courier</p>
        <dl class="data-list">
<?php if ($order['courier_name'] !== null && $order['courier_name'] !== ''): ?>
          <div class="data-list__row"><dt class="data-list__key">Courier</dt><dd class="data-list__val"><?= e($order['courier_name']) ?></dd></div>
<?php endif; ?>
<?php if ($order['tracking_number'] !== null && $order['tracking_number'] !== ''): ?>
          <div class="data-list__row"><dt class="data-list__key">Tracking number</dt><dd class="data-list__val data-list__val--copy"><?= e($order['tracking_number']) ?> <button class="copy-btn js-copy" type="button" data-copy="<?= e($order['tracking_number']) ?>" aria-label="Copy tracking number"><?php echo $copyIcon; ?></button></dd></div>
<?php endif; ?>
        </dl>
<?php if ($order['tracking_url'] !== null): ?>
        <div class="btn-row"><a class="btn btn--ghost btn--sm" href="<?= e($order['tracking_url']) ?>" target="_blank" rel="noopener noreferrer"><span class="btn__label">Track with <?= e($order['courier_name'] ?: 'the courier') ?></span><?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16]); ?></a></div>
<?php endif; ?>
      </div>
<?php endif; ?>
<?php endif; ?>
      <div class="summary">
        <h3 class="summary__title">In this order</h3>
        <div class="summary__lines">
<?php foreach ($order['items'] as $item): ?>
          <div class="summary__line">
            <span class="summary__line-media"><img class="summary__line-img" src="<?= e($item['image_url']) ?>" alt="<?= e($item['product_name']) ?>" width="200" height="250" loading="lazy" decoding="async"></span>
            <span><span class="summary__line-name"><?= e($item['product_name']) ?></span><br><span class="summary__line-meta"><?= e($item['size_label']) ?> × <?= e((string) $item['quantity']) ?></span></span>
          </div>
<?php endforeach; ?>
        </div>
        <div class="summary__rows">
          <p class="summary__row summary__row--total"><span class="summary__label">Order total</span><span class="summary__value summary__value--total"><?= e($order['grand_total_display']) ?></span></p>
        </div>
        <p class="summary__note">Your confirmation page holds the full receipt with prices and delivery details.</p>
      </div>
    </section>
<?php endif; ?>
  </div>
</section>
