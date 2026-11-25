<?php
defined('SKYFR') || exit;
$c = $row['commission'];
$fm = static fn (int $paisa): string => ($paisa < 0 ? '−' : '') . money(money_from_paisa(abs($paisa)));
$err = static fn (string $key): string => (string) ($errors[$key] ?? '');
$state = team_state_badge($c['state']);
$number = (string) $row['order_number'];
?>
<?php partial_admin('page-header.php', [
    'title' => 'Share on ' . $number,
    'subtitle' => 'Credited to ' . $row['member_name'] . '. Order placed ' . date_short((string) $row['created_at']) . ' by ' . $row['customer_name'] . '.',
    'back' => $back,
    'back_label' => 'Back',
    'actions' => [['label' => 'Open order', 'href' => '/admin/orders/' . $number . '/view', 'variant' => 'ghost']],
]); ?>
<div class="adm-duo">
  <section class="adm-card">
    <div class="adm-card__head"><h2 class="adm-card__title">How the share is worked out</h2><?php partial_admin('badge.php', $state + ['small' => true]); ?></div>
    <dl class="adm-facts">
      <dt>Perfumes after discount</dt><dd><?= e($fm($c['net'])) ?></dd>
      <dt>Less cost per bottle</dt><dd>− <?= e($fm($c['cogs'])) ?><?= $c['uncosted'] ? ' <span class="adm-stock--low">(some bottles have no cost set)</span>' : '' ?></dd>
      <dt>Profit</dt><dd><strong><?= e($fm($c['profit'])) ?></strong></dd>
      <dt>Share rate</dt><dd><?= e($c['rate'] !== null ? rtrim(rtrim($c['rate'], '0'), '.') . '%' : '—') ?> = <?= e($fm($c['calculated'])) ?></dd>
<?php if ($c['override']): ?>
      <dt>Set by hand</dt><dd><?= e(money((string) $row['commission_override'])) ?></dd>
<?php endif; ?>
<?php if (!empty($row['commission_note'])): ?>
      <dt>Note</dt><dd><?= e((string) $row['commission_note']) ?></dd>
<?php endif; ?>
      <dt>Share</dt><dd><strong><?= e($fm($c['share'])) ?></strong></dd>
      <dt>Order</dt><dd><?= e(ucfirst((string) $row['status'])) ?> · <?= e(adm_order_payment_label((string) $row['payment_status'])) ?></dd>
<?php if ($row['commission_paid_at'] !== null): ?>
      <dt>Paid</dt><dd><?= e(date_short((string) $row['commission_paid_at'])) ?> by <?= e((string) $row['commission_paid_by']) ?></dd>
<?php endif; ?>
    </dl>
<?php if ($c['paid_warning']): ?>
    <p class="adm-note adm-stock--low">This order was cancelled or refunded after the share was paid. Recover it from the salesperson or keep it as a goodwill payment.</p>
<?php endif; ?>
    <div class="adm-cluster adm-cluster--spaced">
<?php if ($row['commission_paid_at'] === null && $c['state'] !== 'void' && $c['share'] > 0): ?>
      <form method="post" action="<?= e(url('/admin/team/share/' . $number)) ?>" class="adm-cluster">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="paid">
        <input type="hidden" name="return" value="<?= e($back) ?>">
        <label class="adm-note" for="paid-on">Paid on</label>
        <input class="adm-input adm-input--sm" type="date" id="paid-on" name="paid_on" value="<?= e($form['paid_on']) ?>" max="<?= e($form['paid_on']) ?>">
        <?php partial_admin('button.php', ['label' => 'Mark ' . $fm($c['share']) . ' as paid', 'type' => 'submit', 'variant' => 'gold']); ?>
      </form>
<?php elseif ($row['commission_paid_at'] !== null): ?>
      <?php partial_admin('button.php', ['label' => 'Mark as not paid', 'variant' => 'ghost', 'post' => '/admin/team/share/' . $number, 'fields' => ['action' => 'unpaid', 'return' => $back], 'confirm' => 'Mark the share on ' . $number . ' as not paid?', 'confirm_label' => 'Mark not paid']); ?>
<?php endif; ?>
    </div>
<?php if ($c['state'] === 'waiting'): ?>
    <p class="adm-note">Shares are ready to pay once the order is delivered and fully paid by the customer. You can still pay early.</p>
<?php endif; ?>
  </section>
  <section class="adm-card">
    <div class="adm-card__head"><h2 class="adm-card__title">Change this share</h2></div>
<?php if ($row['commission_paid_at'] !== null): ?>
    <p class="adm-muted">This share is paid. Mark it as not paid first if it needs to change.</p>
<?php else: ?>
    <form method="post" action="<?= e(url('/admin/team/share/' . $number)) ?>" class="adm-form" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="adjust">
      <input type="hidden" name="return" value="<?= e($back) ?>">
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'rate', 'label' => 'Share of profit (%) for this order', 'value' => $form['rate'], 'inputmode' => 'decimal', 'error' => $err('rate'), 'help' => 'Only this order changes. The salesperson\'s usual percent stays the same.']); ?>
      <?php partial_admin('field.php', ['type' => 'money', 'name' => 'override', 'label' => 'Or a fixed share', 'value' => $form['override'], 'error' => $err('override'), 'help' => 'Leave empty to use the percent. A fixed amount replaces it.']); ?>
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'note', 'label' => 'Reason', 'value' => $form['note'], 'maxlength' => 255, 'placeholder' => 'e.g. Bulk order, agreed 20%', 'error' => $err('note')]); ?>
      <div class="adm-cluster"><?php partial_admin('button.php', ['label' => 'Save share', 'type' => 'submit', 'variant' => 'gold']); ?></div>
    </form>
<?php endif; ?>
  </section>
</div>
