<?php
defined('SKYFR') || exit;
$errors = $errors ?? [];
$fieldLabels = ['current_password' => 'Current password', 'new_password' => 'New password', 'confirm_password' => 'Confirm new password'];
?>
<h1>Change password</h1>
<?php if ($errors !== []): ?>
<div class="adm-summary" role="alert">
  Please fix the following:
  <ul>
<?php foreach ($errors as $field => $message): ?>
    <li><a href="#<?= e($field) ?>" style="color:inherit"><?= e($fieldLabels[$field] ?? $field) ?></a>: <?= e($message) ?></li>
<?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<div class="adm-card adm-form">
  <form method="post" action="<?= e(url('/admin/password')) ?>" novalidate data-guard>
    <?= csrf_field() ?>
<?php foreach ($fieldLabels as $field => $label): ?>
    <div class="adm-field">
      <label for="<?= e($field) ?>"><?= e($label) ?> <span class="req" aria-hidden="true">*</span></label>
      <div class="adm-pw">
        <input class="adm-input" id="<?= e($field) ?>" name="<?= e($field) ?>" type="password" autocomplete="<?= $field === 'current_password' ? 'current-password' : 'new-password' ?>" required aria-required="true"<?= isset($errors[$field]) ? ' aria-invalid="true"' : '' ?>>
        <button type="button" class="adm-pw-toggle" data-pw-toggle aria-controls="<?= e($field) ?>" aria-pressed="false">Show</button>
      </div>
<?php if (isset($errors[$field])): ?>
      <p class="adm-error"><?= e($errors[$field]) ?></p>
<?php endif; ?>
<?php if ($field === 'new_password'): ?>
      <p class="adm-note">At least 12 characters. Length matters more than symbols.</p>
<?php endif; ?>
    </div>
<?php endforeach; ?>
    <button type="submit" class="adm-btn adm-btn-gold adm-btn-block">Save new password</button>
  </form>
</div>
<div class="adm-card">
  <h2>Recent sign-in activity</h2>
<?php if (!empty($lastLoginAt)): ?>
  <p class="adm-muted">Last sign in: <?= e(date('j M Y, H:i', strtotime((string) $lastLoginAt))) ?></p>
<?php endif; ?>
<?php if (!empty($passwordChangedAt)): ?>
  <p class="adm-muted">Password last changed: <?= e(date('j M Y, H:i', strtotime((string) $passwordChangedAt))) ?></p>
<?php endif; ?>
<?php if (empty($recentLogins)): ?>
  <p class="adm-note">No sign-in attempts recorded in the last 7 days.</p>
<?php else: ?>
  <ul class="adm-list">
<?php foreach ($recentLogins as $attempt): ?>
    <li><span><?= e(date('j M Y, H:i', strtotime((string) $attempt['attempted_at']))) ?></span><span><?= (int) $attempt['was_success'] === 1 ? 'success' : 'failed' ?></span></li>
<?php endforeach; ?>
  </ul>
<?php endif; ?>
</div>
