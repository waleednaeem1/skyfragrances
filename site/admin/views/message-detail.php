<?php
defined('SKYFR') || exit;
$id = (int) $message['id'];
$statusPost = '/admin/messages/' . $id . '/status';
$subject = trim((string) ($message['subject'] ?? ''));
$headerActions = [];
if ($whatsappUrl !== '') {
    $headerActions[] = ['label' => 'Reply on WhatsApp', 'href' => $whatsappUrl, 'external' => true, 'variant' => 'gold'];
}
if ($mailtoUrl !== '') {
    $headerActions[] = ['label' => 'Reply by email', 'href' => $mailtoUrl, 'external' => true];
}
?>
<?php partial_admin('page-header.php', [
    'title' => $message['name'],
    'subtitle' => ($subject === '' ? 'No subject' : $subject) . ' · received ' . date_long($message['created_at']),
    'back' => '/admin/messages',
    'back_label' => 'Messages',
    'actions' => $headerActions,
]); ?>
<div class="adm-card">
  <div class="adm-card__head">
    <h2 class="adm-card__title">Message</h2>
    <?php partial_admin('badge.php', ['status' => (string) $message['status']]); ?>
  </div>
  <p style="white-space:pre-wrap;font-size:1.05rem;line-height:1.6"><?= e((string) $message['message']) ?></p>
  <ul class="adm-list">
<?php if (!empty($message['phone'])): ?>
    <li class="adm-list__item adm-cluster"><span class="adm-muted">Phone</span> <a class="adm-mono" href="tel:<?= e(phone_normalize((string) $message['phone'])) ?>"><?= e((string) $message['phone']) ?></a> <button type="button" class="adm-btn adm-btn--text adm-btn--sm" data-copy="<?= e((string) $message['phone']) ?>">Copy</button></li>
<?php endif; ?>
<?php if (!empty($message['email'])): ?>
    <li class="adm-list__item adm-cluster"><span class="adm-muted">Email</span> <span><?= e((string) $message['email']) ?></span> <button type="button" class="adm-btn adm-btn--text adm-btn--sm" data-copy="<?= e((string) $message['email']) ?>">Copy</button></li>
<?php endif; ?>
<?php if (empty($message['phone']) && empty($message['email'])): ?>
    <li class="adm-list__item adm-muted">No phone or email was given, so there is no way to reply.</li>
<?php endif; ?>
<?php if (!empty($message['order_number'])): ?>
    <li class="adm-list__item adm-cluster"><span class="adm-muted">Order</span> <?php if ($orderUrl !== ''): ?><a class="adm-mono" href="<?= e(url($orderUrl)) ?>"><?= e((string) $message['order_number']) ?></a><?php else: ?><span class="adm-mono"><?= e((string) $message['order_number']) ?></span> <span class="adm-muted">(not a valid order number)</span><?php endif; ?></li>
<?php endif; ?>
<?php if ($message['read_at'] !== null): ?>
    <li class="adm-list__item adm-muted">First opened <?= e(date_long($message['read_at'])) ?></li>
<?php endif; ?>
  </ul>
</div>
<div class="adm-stack">
<?php if ($whatsappUrl !== ''): ?>
  <?php partial_admin('button.php', ['label' => 'Reply on WhatsApp', 'href' => $whatsappUrl, 'external' => true, 'variant' => 'gold', 'block' => true]); ?>
<?php endif; ?>
<?php if ($mailtoUrl !== ''): ?>
  <?php partial_admin('button.php', ['label' => 'Reply by email', 'href' => $mailtoUrl, 'external' => true, 'block' => true]); ?>
<?php endif; ?>
<?php if ($message['status'] !== 'replied'): ?>
  <?php partial_admin('button.php', ['label' => 'Mark replied', 'post' => $statusPost, 'fields' => ['to' => 'replied'], 'block' => true]); ?>
<?php endif; ?>
<?php if ($message['status'] !== 'archived'): ?>
  <?php partial_admin('button.php', ['label' => 'Archive', 'post' => $statusPost, 'fields' => ['to' => 'archived'], 'block' => true]); ?>
<?php else: ?>
  <?php partial_admin('button.php', ['label' => 'Move back to New', 'post' => $statusPost, 'fields' => ['to' => 'new'], 'block' => true]); ?>
<?php endif; ?>
</div>
<form method="post" action="<?= e(url($statusPost)) ?>" class="adm-form" data-guard novalidate>
  <?= csrf_field() ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Your note</h2>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'admin_note', 'label' => 'Private note', 'value' => (string) ($message['admin_note'] ?? ''), 'rows' => 4, 'maxlength' => 2000, 'help' => 'Only you see this. What you told them, what to follow up on.']); ?>
    <?php partial_admin('field.php', ['type' => 'select', 'name' => 'to', 'label' => 'Status', 'value' => (string) $message['status'], 'options' => $statusLabels]); ?>
  </div>
  <?php partial_admin('action-bar.php', ['buttons' => [
      ['label' => 'Save note and status', 'variant' => 'gold'],
      ['label' => 'Back to messages', 'href' => '/admin/messages', 'variant' => 'text'],
  ]]); ?>
</form>
<div class="adm-card adm-card--danger">
  <div class="adm-card__head"><h2 class="adm-card__title">Delete this message</h2></div>
  <p class="adm-note">Removes the message and the sender's contact details for good. Use this when someone asks to be forgotten.</p>
  <form method="post" action="<?= e(url($statusPost)) ?>" class="adm-inline-form" data-confirm="Delete this message?&#10;The message from <?= e((string) $message['name']) ?> and their contact details are removed for good." data-confirm-word="DELETE" data-confirm-label="Yes, delete" data-confirm-danger="1">
    <?= csrf_field() ?>
    <input type="hidden" name="to" value="delete">
    <noscript><label class="adm-field__label" for="confirm_word">Type DELETE to confirm</label><input class="adm-input" type="text" id="confirm_word" name="confirm_word" autocomplete="off"></noscript>
    <button type="submit" class="adm-btn adm-btn--danger">Delete message</button>
  </form>
</div>
