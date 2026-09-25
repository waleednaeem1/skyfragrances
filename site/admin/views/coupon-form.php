<?php
defined('SKYFR') || exit;
$formAction = $isNew ? '/admin/coupons/new' : '/admin/coupons/' . (int) $coupon['id'];
$fieldLabels = [
    'code' => 'Code', 'type' => 'Type', 'value' => 'Discount value', 'min_order_total' => 'Minimum order',
    'usage_limit' => 'Usage limit', 'per_phone_limit' => 'Per-phone limit', 'starts_at' => 'Starts', 'expires_at' => 'Expires',
];
$headerActions = [];
if (!$isNew) {
    $headerActions[] = ['label' => 'Copy code', 'type' => 'button', 'attr' => ['data-copy' => (string) $coupon['code']]];
    $headerActions[] = ['label' => 'Share on WhatsApp', 'href' => $shareUrl, 'external' => true];
    $headerActions[] = ['label' => (int) $coupon['is_active'] === 1 ? 'Disable' : 'Enable', 'post' => '/admin/coupons/' . (int) $coupon['id'] . '/toggle', 'fields' => ['return' => '/admin/coupons']];
}
$isPercent = $form['type'] === 'percent';
?>
<?php partial_admin('page-header.php', [
    'title' => $isNew ? 'New coupon' : $coupon['code'],
    'subtitle' => $isNew ? 'A code customers type at checkout.' : 'Used ' . $usedCount . ' time' . ($usedCount === 1 ? '' : 's') . ' so far.',
    'back' => '/admin/coupons',
    'back_label' => 'Coupons',
    'actions' => $headerActions,
]); ?>
<?php if ($errors !== []): ?>
<div class="adm-summary" role="alert">
  <p>Please fix the following:</p>
  <ul>
<?php foreach ($errors as $field => $message): ?>
    <li><a href="#<?= e($field) ?>"><?= e($fieldLabels[$field] ?? $field) ?>: <?= e($message) ?></a></li>
<?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<form method="post" action="<?= e(url($formAction)) ?>" class="adm-form" data-guard novalidate>
  <?= csrf_field() ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Code and discount</h2>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'code', 'label' => 'Code', 'value' => $form['code'], 'required' => true, 'maxlength' => 32, 'error' => $errors['code'] ?? '', 'help' => 'Letters, numbers and dashes. Saved in capitals.', 'attr' => ['autocapitalize' => 'characters', 'autocomplete' => 'off', 'spellcheck' => 'false', 'style' => 'text-transform:uppercase']]); ?>
    <?php partial_admin('field.php', ['type' => 'segment', 'name' => 'type', 'label' => 'Type', 'value' => $form['type'], 'options' => ['percent' => 'Percent off', 'fixed' => 'Fixed amount'], 'error' => $errors['type'] ?? '']); ?>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', $isPercent
          ? ['type' => 'int', 'name' => 'value', 'label' => 'Percent off', 'value' => $form['value'], 'required' => true, 'placeholder' => '10', 'error' => $errors['value'] ?? '', 'help' => '1 to 90, whole numbers.']
          : ['type' => 'money', 'name' => 'value', 'label' => 'Amount off', 'value' => $form['value'], 'required' => true, 'error' => $errors['value'] ?? '', 'help' => 'Up to Rs. 100,000.']); ?>
      <?php partial_admin('field.php', ['type' => 'money', 'name' => 'min_order_total', 'label' => 'Minimum order', 'value' => $form['min_order_total'], 'error' => $errors['min_order_total'] ?? '', 'help' => 'Items subtotal needed before the code works. 0 for none.']); ?>
    </div>
    <p class="adm-note">Percent and amount both apply to the items subtotal, never to shipping. Switching the type re-reads the value on save, so check it after changing.</p>
  </div>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Limits</h2>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'int', 'name' => 'usage_limit', 'label' => 'Total uses', 'value' => $form['usage_limit'], 'placeholder' => 'Unlimited', 'error' => $errors['usage_limit'] ?? '', 'help' => $isNew ? 'Blank means unlimited.' : 'Blank means unlimited. Already used ' . $usedCount . ' times.']); ?>
      <?php partial_admin('field.php', ['type' => 'int', 'name' => 'per_phone_limit', 'label' => 'Uses per phone', 'value' => $form['per_phone_limit'], 'placeholder' => 'No limit', 'error' => $errors['per_phone_limit'] ?? '', 'help' => 'Set 1 for a one-time welcome code.']); ?>
    </div>
<?php if (!$isNew): ?>
    <div class="adm-field">
      <span class="adm-field__label">Used so far</span>
      <p class="adm-num adm-mono" id="used_count"><?= e((string) $usedCount) ?><?= $form['usage_limit'] !== '' ? ' / ' . e($form['usage_limit']) : '' ?></p>
      <p class="adm-field__help">Counted by the system when orders are placed and released when they are cancelled. It cannot be edited.</p>
    </div>
<?php endif; ?>
  </div>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Active window</h2>
    <div class="adm-field--pair">
      <div class="adm-field<?= isset($errors['starts_at']) ? ' adm-field--error' : '' ?>">
        <label class="adm-field__label" for="starts_at">Starts</label>
        <input class="adm-input" type="datetime-local" id="starts_at" name="starts_at" value="<?= e($form['starts_at']) ?>" step="60"<?= isset($errors['starts_at']) ? ' aria-invalid="true" aria-describedby="starts_at-error"' : ' aria-describedby="starts_at-help"' ?>>
<?php if (isset($errors['starts_at'])): ?>
        <p class="adm-field__error" id="starts_at-error"><?= e($errors['starts_at']) ?></p>
<?php endif; ?>
        <p class="adm-field__help" id="starts_at-help">Blank starts now. Pakistan time.</p>
      </div>
      <div class="adm-field<?= isset($errors['expires_at']) ? ' adm-field--error' : '' ?>">
        <label class="adm-field__label" for="expires_at">Expires</label>
        <input class="adm-input" type="datetime-local" id="expires_at" name="expires_at" value="<?= e($form['expires_at']) ?>" step="60"<?= isset($errors['expires_at']) ? ' aria-invalid="true" aria-describedby="expires_at-error"' : ' aria-describedby="expires_at-help"' ?>>
<?php if (isset($errors['expires_at'])): ?>
        <p class="adm-field__error" id="expires_at-error"><?= e($errors['expires_at']) ?></p>
<?php endif; ?>
        <p class="adm-field__help" id="expires_at-help">Blank never expires.</p>
      </div>
    </div>
    <?php partial_admin('field.php', ['type' => 'checkbox', 'name' => 'is_active', 'label' => 'Active — customers can apply this code', 'value' => $form['is_active']]); ?>
  </div>
<?php if ($ledger !== null): ?>
  <div class="adm-card">
    <div class="adm-card__head"><h2 class="adm-card__title">Usage from the ledger</h2></div>
    <div class="adm-grid">
      <div class="adm-kpi"><span class="adm-kpi__value"><?= e((string) $ledger['applied']) ?></span><span class="adm-kpi__label">Orders using it</span></div>
      <div class="adm-kpi"><span class="adm-kpi__value"><?php partial_admin('money.php', ['value' => $ledger['discount_total']]); ?></span><span class="adm-kpi__label">Discount given</span></div>
      <div class="adm-kpi"><span class="adm-kpi__value"><?php partial_admin('money.php', ['value' => $ledger['subtotal_total']]); ?></span><span class="adm-kpi__label">Items sold with it</span></div>
      <div class="adm-kpi"><span class="adm-kpi__value"><?= e((string) $ledger['phones']) ?></span><span class="adm-kpi__label">Different phones</span></div>
    </div>
    <p class="adm-note"><?= $ledger['reverted'] > 0 ? e((string) $ledger['reverted']) . ' use' . ($ledger['reverted'] === 1 ? '' : 's') . ' released by cancelled orders. ' : '' ?><?= $ledger['last_used_at'] !== null ? 'Last used ' . e(date_long($ledger['last_used_at'])) . '.' : 'Not used yet.' ?></p>
<?php if ($ledger['recent'] !== []): ?>
    <ul class="adm-list">
<?php foreach ($ledger['recent'] as $use): ?>
      <li class="adm-cluster">
        <a class="adm-mono" href="<?= e(url('/admin/orders/' . $use['order_number'])) ?>"><?= e((string) $use['order_number']) ?></a>
        <span class="adm-muted"><?= e(date_short($use['created_at'])) ?></span>
        <?php partial_admin('money.php', ['value' => (string) $use['discount_amount'], 'negative' => true, 'muted' => $use['status'] !== 'applied']); ?>
<?php if ($use['status'] !== 'applied'): ?>
        <?php partial_admin('badge.php', ['status' => 'reverted', 'label' => 'Released', 'tone' => 'muted', 'small' => true]); ?>
<?php endif; ?>
      </li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </div>
<?php endif; ?>
  <?php partial_admin('action-bar.php', ['buttons' => [
      ['label' => $isNew ? 'Create coupon' : 'Save coupon', 'variant' => 'gold'],
      ['label' => 'Cancel', 'href' => '/admin/coupons', 'variant' => 'text'],
  ]]); ?>
</form>
