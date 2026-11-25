<?php
defined('SKYFR') || exit;
$viewer = auth_user();
$seesShare = !empty($sales) && (team_is_owner() || (int) $sales['sales_member_id'] === (int) ($viewer['id'] ?? 0));
?>
<?php if (!empty($sales) || team_is_owner()): ?>
<section class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Sold by</h2><?php if ($seesShare): ?><?php partial_admin('badge.php', team_state_badge($sales['commission']['state']) + ['small' => true]); ?><?php endif; ?></div>
<?php if (!empty($sales)): ?>
  <p class="adm-view__name"><?= e((string) $sales['member_name']) ?></p>
<?php if ($seesShare): ?>
  <p><?= e($sales['commission']['rate'] !== null ? rtrim(rtrim($sales['commission']['rate'], '0'), '.') . '%' : '') ?> of <?= e(money(money_from_paisa($sales['commission']['profit']))) ?> profit · share <strong><?= e(money(money_from_paisa($sales['commission']['share']))) ?></strong><?= $sales['commission_paid_at'] !== null ? ' · paid ' . e(date_short((string) $sales['commission_paid_at'])) : '' ?></p>
<?php endif; ?>
<?php if (team_is_owner()): ?>
  <p><a href="<?= e(url('/admin/team/share/' . $order['order_number'] . '?return=' . rawurlencode('/admin/orders/' . $order['order_number'] . '/view'))) ?>">Share details and payment</a></p>
<?php endif; ?>
<?php else: ?>
  <p class="adm-muted"><?= e(TEAM_COMPANY_LABEL) ?></p>
<?php endif; ?>
<?php if (team_is_owner() && $sellers !== [] && (empty($sales) || $sales['commission_paid_at'] === null)): ?>
  <form method="post" action="<?= e(url('/admin/orders/' . $order['order_number'] . '/salesperson')) ?>" class="adm-cluster">
    <?= csrf_field() ?>
    <label class="u-sr-only" for="sales-member">Credit this order to</label>
    <select class="adm-input adm-select adm-input--sm" id="sales-member" name="member_id">
      <option value="0">Company (100% of profit)</option>
<?php foreach ($sellers as $seller): ?>
      <option value="<?= e((string) $seller['id']) ?>"<?= !empty($sales) && (int) $sales['sales_member_id'] === (int) $seller['id'] ? ' selected' : '' ?><?= (int) $seller['is_active'] !== 1 ? ' disabled' : '' ?>><?= e((string) $seller['display_name'] . ' · ' . rtrim(rtrim((string) $seller['commission_rate'], '0'), '.') . '%') ?></option>
<?php endforeach; ?>
    </select>
    <?php partial_admin('button.php', ['label' => 'Save', 'type' => 'submit', 'variant' => 'ghost', 'size' => 'sm']); ?>
  </form>
<?php endif; ?>
</section>
<?php endif; ?>
