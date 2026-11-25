<?php
defined('SKYFR') || exit;
$locked = ($lockMinutes ?? 0) > 0;
$host = (string) (parse_url(SITE_URL, PHP_URL_HOST) ?: 'skyfragrances.com');
?>
<div class="adm-login-card">
  <span class="adm-brand__mark adm-login-card__mark" aria-hidden="true">S<span>F</span></span>
<?php if (($mode ?? 'login') === 'logout'): ?>
  <h1>Log out</h1>
  <p class="adm-login-card__sub">Do you want to end your admin session?</p>
  <form method="post" action="<?= e(url('/admin/logout')) ?>">
    <?= csrf_field() ?>
    <button type="submit" class="adm-btn adm-btn--primary adm-btn--block">Log out</button>
  </form>
  <p class="adm-note"><a href="<?= e(url('/admin')) ?>">Back to dashboard</a></p>
<?php else: ?>
  <h1>Sign in to <?= e((string) setting('store_name', 'Sky Fragrances')) ?></h1>
  <p class="adm-login-card__sub">Admin panel · <?= e($host) ?></p>
<?php if (!empty($notice)): ?>
  <div class="adm-banner adm-banner--info" role="status"><?= e($notice) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="adm-summary" role="alert"><?= e($error) ?></div>
<?php endif; ?>
  <form method="post" action="<?= e(url('/admin/login')) ?>" novalidate>
    <?= csrf_field() ?>
<?php if (!empty($next)): ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
<?php endif; ?>
    <div class="adm-field">
      <label class="adm-field__label" for="username">Username</label>
      <input class="adm-input" id="username" name="username" type="text" value="<?= e($username ?? '') ?>" autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false" inputmode="text" required<?= $locked ? ' disabled' : '' ?>>
    </div>
    <div class="adm-field">
      <label class="adm-field__label" for="password">Password</label>
      <div class="adm-pw">
        <input class="adm-input" id="password" name="password" type="password" autocomplete="current-password" required<?= $locked ? ' disabled' : '' ?>>
        <button type="button" class="adm-pw-toggle" data-pw-toggle aria-controls="password" aria-pressed="false">Show</button>
      </div>
    </div>
    <button type="submit" class="adm-btn adm-btn--primary adm-btn--block adm-login-card__submit"<?= $locked ? ' disabled' : '' ?>>Sign in<?php partial('icon.php', ['name' => 'chevron-right', 'size' => 15]); ?></button>
  </form>
<?php if ($locked): ?>
  <p class="adm-note">If you are locked out, you can wait. The lock clears by itself.</p>
<?php endif; ?>
  <p class="adm-note">Forgot your password? See “Forgotten admin password” in your GO-LIVE guide.</p>
<?php endif; ?>
</div>
