<?php
defined('SKYFR') || exit;
$pageHeading = trim((string) ($heading ?? ''));
if ($pageHeading === '') {
    $pageHeading = 'Contact Us';
}
$old = array_merge(['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'order_number' => '', 'message' => ''], is_array($old ?? null) ? $old : []);
$errors = is_array($errors ?? null) ? $errors : [];
$subjects = is_array($subjects ?? null) ? $subjects : [];
$whatsappDigits = preg_replace('/\D+/', '', (string) ($whatsapp ?? ''));
$whatsappUrl = (string) ($whatsappUrl ?? '');
$contactPhone = trim((string) ($contactPhone ?? ''));
$contactEmail = trim((string) ($contactEmail ?? ''));
$addressLine = trim((string) ($addressLine ?? ''));
$businessHours = trim((string) ($businessHours ?? ''));
$hasDetails = $contactPhone !== '' || $whatsappDigits !== '' || $contactEmail !== '' || $addressLine !== '' || $businessHours !== '';
$fieldClass = static fn (string $key): string => 'field' . (isset($errors[$key]) ? ' has-error' : '');
?>
<section class="section section--tight" id="sent">
  <div class="container">
    <header class="page-head">
      <h1 class="page-head__title"><?= e($pageHeading) ?></h1>
      <p class="page-head__intro">Questions about an order, a scent, or a gift? Message us on WhatsApp for the fastest reply, or send the form and we will answer within one working day.</p>
    </header>
    <div class="split">
<?php if ($hasDetails): ?>
      <aside class="split__aside">
        <div class="stack">
<?php if ($whatsappDigits !== '' && $whatsappUrl !== ''): ?>
          <a class="btn btn--primary btn--whatsapp btn--block" href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener"><?php partial('icon.php', ['name' => 'whatsapp', 'size' => 20]); ?><span class="btn__label">Chat on WhatsApp</span></a>
<?php endif; ?>
          <dl class="data-list">
<?php if ($contactPhone !== ''): ?>
            <div class="data-list__row"><dt class="data-list__key">Phone</dt><dd class="data-list__val"><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $contactPhone)) ?>"><?= e($contactPhone) ?></a></dd></div>
<?php endif; ?>
<?php if ($contactEmail !== ''): ?>
            <div class="data-list__row"><dt class="data-list__key">Email</dt><dd class="data-list__val"><a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></dd></div>
<?php endif; ?>
<?php if ($addressLine !== ''): ?>
            <div class="data-list__row"><dt class="data-list__key">Address</dt><dd class="data-list__val"><?= e($addressLine) ?></dd></div>
<?php endif; ?>
<?php if ($businessHours !== ''): ?>
            <div class="data-list__row"><dt class="data-list__key">Hours</dt><dd class="data-list__val"><?= e($businessHours) ?></dd></div>
<?php endif; ?>
          </dl>
          <p class="text-muted text-small">We are online only. For help choosing a fragrance before you order, WhatsApp is the quickest way to reach us.</p>
        </div>
      </aside>
<?php endif; ?>
      <div>
        <form class="form js-validate" method="post" action="<?= e(url('/contact')) ?>">
          <?= csrf_field() ?>
          <?php echo $trapField ?? ''; ?>
          <div class="form__summary js-form-summary" role="alert"<?= isset($errors['form']) ? '' : ' hidden' ?>><?= isset($errors['form']) ? e($errors['form']) : '' ?></div>
          <div class="form__row">
            <div class="<?= e($fieldClass('name')) ?>">
              <label class="field__label" for="f-name">Your name</label>
              <div class="field__control"><input class="field__input" id="f-name" name="name" type="text" value="<?= e($old['name']) ?>" required minlength="2" maxlength="60" autocomplete="name" aria-describedby="err-name"<?= isset($errors['name']) ? ' aria-invalid="true"' : '' ?>></div>
              <p class="field__error" id="err-name"><?= e($errors['name'] ?? '') ?></p>
            </div>
            <div class="<?= e($fieldClass('email')) ?>">
              <label class="field__label" for="f-email">Email</label>
              <div class="field__control"><input class="field__input" id="f-email" name="email" type="email" value="<?= e($old['email']) ?>" required maxlength="120" autocomplete="email" inputmode="email" aria-describedby="err-email"<?= isset($errors['email']) ? ' aria-invalid="true"' : '' ?>></div>
              <p class="field__hint">We reply here.</p>
              <p class="field__error" id="err-email"><?= e($errors['email'] ?? '') ?></p>
            </div>
          </div>
          <div class="form__row">
            <div class="<?= e($fieldClass('phone')) ?>">
              <label class="field__label" for="f-phone">Phone <span class="field__optional">optional</span></label>
              <div class="field__control"><input class="field__input" id="f-phone" name="phone" type="tel" value="<?= e($old['phone']) ?>" maxlength="20" autocomplete="tel" inputmode="tel" placeholder="0300 1234567" aria-describedby="err-phone"<?= isset($errors['phone']) ? ' aria-invalid="true"' : '' ?>></div>
              <p class="field__error" id="err-phone"><?= e($errors['phone'] ?? '') ?></p>
            </div>
            <div class="<?= e($fieldClass('subject')) ?>">
              <label class="field__label" for="f-subject">Subject</label>
              <div class="field__control">
                <select class="field__input field__input--select" id="f-subject" name="subject" required aria-describedby="err-subject"<?= isset($errors['subject']) ? ' aria-invalid="true"' : '' ?>>
                  <option value=""<?= $old['subject'] === '' ? ' selected' : '' ?>>Choose a subject</option>
<?php foreach ($subjects as $subject): ?>
                  <option value="<?= e($subject) ?>"<?= $old['subject'] === $subject ? ' selected' : '' ?>><?= e($subject) ?></option>
<?php endforeach; ?>
                </select>
              </div>
              <p class="field__error" id="err-subject"><?= e($errors['subject'] ?? '') ?></p>
            </div>
          </div>
          <div class="<?= e($fieldClass('order_number')) ?>">
            <label class="field__label" for="f-order">Order number <span class="field__optional">optional</span></label>
            <div class="field__control"><input class="field__input field__input--code" id="f-order" name="order_number" type="text" value="<?= e($old['order_number']) ?>" maxlength="16" placeholder="SF-260925-K7QF" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-describedby="err-order"<?= isset($errors['order_number']) ? ' aria-invalid="true"' : '' ?>></div>
            <p class="field__hint">If your message is about an order, it is on your confirmation page and email.</p>
            <p class="field__error" id="err-order"><?= e($errors['order_number'] ?? '') ?></p>
          </div>
          <div class="<?= e($fieldClass('message')) ?>">
            <label class="field__label" for="f-message">Message</label>
            <div class="field__control"><textarea class="field__input field__input--textarea" id="f-message" name="message" rows="6" required minlength="10" maxlength="2000" data-maxlength-count="cnt-message" aria-describedby="err-message cnt-message"<?= isset($errors['message']) ? ' aria-invalid="true"' : '' ?>><?= e($old['message']) ?></textarea></div>
            <p class="field__error" id="err-message"><?= e($errors['message'] ?? '') ?></p>
            <p class="field__count" id="cnt-message"></p>
          </div>
          <div class="form__actions">
            <button class="btn btn--primary" type="submit"><span class="btn__label">Send message</span></button>
            <p class="text-muted text-small">We reply within one working day. Nothing you send here is shared.</p>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
