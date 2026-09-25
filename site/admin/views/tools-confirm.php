<?php
defined('SKYFR') || exit;
$plural = static fn (int $n, string $one, string $many): string => $n . ' ' . ($n === 1 ? $one : $many);
$isSample = ($kind ?? '') === 'sample';
partial_admin('page-header.php', [
    'title' => $isSample ? 'Remove sample data' : 'Privacy purge',
    'subtitle' => $isSample ? 'Read the two lists, then type DELETE to confirm. This cannot be undone.' : 'Nothing a customer can still see is touched. Orders, products and settings stay exactly as they are.',
    'back' => '/admin/tools',
    'back_label' => 'Tools',
]);
?>
<?php if ($isSample): ?>
<?php if (!$preview['any']): ?>
<?php partial_admin('empty.php', ['title' => 'Nothing left to remove', 'text' => 'The sample products, collections, coupons and reviews are already gone.', 'action' => ['label' => 'Back to Tools', 'href' => '/admin/tools']]); ?>
<?php else: ?>
<div class="adm-card adm-card--danger">
  <div class="adm-card__head"><h2 class="adm-card__title">Will be deleted</h2></div>
  <ul class="adm-list">
<?php if ($preview['reviews'] > 0): ?>
    <li><span><?= e($plural($preview['reviews'], 'sample review', 'sample reviews')) ?></span><span class="adm-note">all of them</span></li>
<?php endif; ?>
<?php foreach ($preview['products'] as $product): if ($product['sold']) { continue; } ?>
    <li><span><?= e((string) $product['name']) ?><br><span class="adm-note">product, its sizes, photos and reviews</span></span><?php partial_admin('badge.php', ['status' => 'cancelled', 'label' => 'Delete', 'small' => true]); ?></li>
<?php endforeach; ?>
<?php foreach ($preview['collections'] as $collection): if ((int) $collection['others'] > 0) { continue; } ?>
    <li><span><?= e((string) $collection['name']) ?><br><span class="adm-note">collection and its image</span></span><?php partial_admin('badge.php', ['status' => 'cancelled', 'label' => 'Delete', 'small' => true]); ?></li>
<?php endforeach; ?>
<?php foreach ($preview['coupons'] as $coupon): if ($coupon['used']) { continue; } ?>
    <li><span><span class="adm-mono"><?= e((string) $coupon['code']) ?></span><br><span class="adm-note">coupon, never used</span></span><?php partial_admin('badge.php', ['status' => 'cancelled', 'label' => 'Delete', 'small' => true]); ?></li>
<?php endforeach; ?>
  </ul>
</div>
<?php
$kept = [];
foreach ($preview['products'] as $product) {
    if ($product['sold']) {
        $kept[] = [(string) $product['name'], 'already sold — hidden from the shop, kept for order history'];
    }
}
foreach ($preview['collections'] as $collection) {
    if ((int) $collection['others'] > 0) {
        $kept[] = [(string) $collection['name'], 'still holds ' . $plural((int) $collection['others'], 'product of your own', 'products of your own')];
    }
}
foreach ($preview['coupons'] as $coupon) {
    if ($coupon['used']) {
        $kept[] = [(string) $coupon['code'], 'already used on an order — switched off instead'];
    }
}
foreach ($preview['settings'] as $key) {
    $kept[] = [str_replace('_', ' ', $key), 'a setting — edit it under Settings › Payments'];
}
?>
<?php if ($kept !== []): ?>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Will be kept</h2></div>
  <ul class="adm-list">
<?php foreach ($kept as [$name, $why]): ?>
    <li><span><?= e($name) ?><br><span class="adm-note"><?= e($why) ?></span></span><?php partial_admin('badge.php', ['status' => 'live', 'label' => 'Keep', 'small' => true]); ?></li>
<?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<form method="post" action="<?= e(url('/admin/tools/remove-sample-data')) ?>" class="adm-form" data-confirm="Remove the sample data now?&#10;Deleted products and reviews cannot be brought back." data-confirm-label="Yes, remove it" data-confirm-danger="1">
  <?= csrf_field() ?>
  <div class="adm-form__section">
<?php partial_admin('field.php', ['type' => 'text', 'name' => 'confirm_word', 'id' => 'confirm_word', 'label' => 'Type DELETE to confirm', 'value' => '', 'required' => true, 'placeholder' => 'DELETE', 'attr' => ['autocomplete' => 'off', 'autocapitalize' => 'characters', 'spellcheck' => 'false']]); ?>
  </div>
<?php partial_admin('action-bar.php', ['buttons' => [['label' => 'Remove sample data', 'variant' => 'danger', 'type' => 'submit']], 'note' => 'Deletes for good']); ?>
</form>
<?php endif; ?>
<?php else: ?>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">What will happen</h2></div>
  <ul class="adm-list">
    <li><span>Payment screenshots for orders delivered or cancelled more than <?= e((string) $preview['proof_days']) ?> days ago<br><span class="adm-note">the file is deleted; the order keeps its reference and the date it was purged</span></span><span class="adm-num"><?= e((string) $preview['proofs']) ?></span></li>
    <li><span>Stored copies of emails sent more than <?= e((string) $preview['body_days']) ?> days ago<br><span class="adm-note">the body is emptied; the record of what was sent stays</span></span><span class="adm-num"><?= e((string) $preview['outbox_bodies']) ?></span></li>
    <li><span>Rate-limit rows older than <?= e((string) $preview['attempt_days']) ?> days</span><span class="adm-num"><?= e((string) $preview['rate_limits']) ?></span></li>
    <li><span>Login attempts older than <?= e((string) $preview['attempt_days']) ?> days</span><span class="adm-num"><?= e((string) $preview['login_attempts']) ?></span></li>
    <li><span>Expired session files</span><span class="adm-num"><?= e((string) $preview['sessions']) ?></span></li>
    <li><span>Proof files no order refers to<br><span class="adm-note">left behind by a checkout that failed after the upload</span></span><span class="adm-note">counted as it runs</span></li>
  </ul>
</div>
<form method="post" action="<?= e(url('/admin/tools/purge')) ?>" class="adm-form" data-confirm="Run the privacy purge now?&#10;Deleted screenshots cannot be recovered. Orders and products are not affected." data-confirm-label="Run the purge">
  <?= csrf_field() ?>
<?php partial_admin('action-bar.php', ['buttons' => [['label' => 'Run the purge', 'variant' => 'gold', 'type' => 'submit']], 'note' => 'Takes a few seconds']); ?>
</form>
<?php endif; ?>
