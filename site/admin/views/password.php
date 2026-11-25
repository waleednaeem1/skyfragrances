<?php
defined('SKYFR') || exit;
$errors = $errors ?? [];
$fieldLabels = ['current_password' => 'Current password', 'new_password' => 'New password', 'confirm_password' => 'Confirm new password'];
$when = static fn (string $at): string => date('j M Y, H:i', strtotime($at));
?>
<?php partial_admin('page-header.php', ['title' => 'Change password', 'subtitle' => 'Signed in as ' . $signedInAs]); ?>
<?php if ($errors !== []): ?>
<div class="adm-summary" role="alert">
  Please fix the following:
  <ul>
<?php foreach ($errors as $field => $message): ?>
    <li><a href="#<?= e($field) ?>"><?= e($fieldLabels[$field] ?? $field) ?></a>: <?= e($message) ?></li>
<?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<div class="adm-split">
  <div class="adm-split__main">
    <form class="adm-card" method="post" action="<?= e(url('/admin/password')) ?>" novalidate data-guard>
      <?= csrf_field() ?>
      <h2 class="adm-card__title">Change password</h2>
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
      <button type="submit" class="adm-btn adm-btn--primary"><?php partial('icon.php', ['name' => 'check', 'size' => 15]); ?>Save new password</button>
    </form>
  </div>
  <aside class="adm-split__side">
    <section class="adm-card">
      <h2 class="adm-card__title">Recent sign-in activity</h2>
<?php if (!empty($lastLoginAt) || !empty($passwordChangedAt)): ?>
      <dl class="adm-dl">
<?php if (!empty($lastLoginAt)): ?>
        <div><dt>Last sign in</dt><dd><?= e($when((string) $lastLoginAt)) ?></dd></div>
<?php endif; ?>
<?php if (!empty($passwordChangedAt)): ?>
        <div><dt>Password last changed</dt><dd><?= e($when((string) $passwordChangedAt)) ?></dd></div>
<?php endif; ?>
      </dl>
<?php endif; ?>
<?php if (empty($recentLogins)): ?>
      <p class="adm-note">No sign-in attempts recorded in the last 7 days.</p>
<?php else: ?>
      <ul class="adm-list">
<?php foreach ($recentLogins as $attempt): $ok = (int) $attempt['was_success'] === 1; ?>
        <li><span class="adm-num"><?= e($when((string) $attempt['attempted_at'])) ?></span><?php partial_admin('badge.php', ['label' => $ok ? 'Success' : 'Failed', 'tone' => $ok ? 'green' : 'red', 'small' => true]); ?></li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </section>
  </aside>
</div>
