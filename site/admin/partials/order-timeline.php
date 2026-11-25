<?php
defined('SKYFR') || exit;
$badge = static function (string $status, array $extra = []): string {
    ob_start();
    partial_admin('badge.php', ['status' => $status] + $extra);
    return (string) ob_get_clean();
};
?>
<section class="adm-card" id="timeline">
  <div class="adm-card__head"><h2 class="adm-card__title">Timeline</h2></div>
  <ol class="adm-timeline">
<?php foreach ($history as $entry): $isCoin = $entry['field'] === 'payment_status'; ?>
    <li class="adm-timeline__item<?= $isCoin ? ' adm-timeline__item--coin' : '' ?>">
      <div class="adm-timeline__head"><?= $isCoin ? $badge((string) $entry['to_status'], ['label' => adm_order_payment_label((string) $entry['to_status']), 'small' => true]) : $badge((string) $entry['to_status'], ['label' => adm_order_status_label((string) $entry['to_status']), 'small' => true]) ?> <span class="adm-timeline__time"><?= e(adm_order_datetime((string) $entry['created_at'])) ?></span></div>
      <p class="adm-timeline__meta">by <?= $entry['changed_by'] === 'admin' ? 'Admin' . (!empty($entry['admin_username']) ? ' (' . e((string) $entry['admin_username']) . ')' : '') : ucfirst(e((string) $entry['changed_by'])) ?></p>
<?php if (!empty($entry['note'])): ?>
      <p class="adm-timeline__note">&ldquo;<?= e((string) $entry['note']) ?>&rdquo;</p>
<?php endif; ?>
    </li>
<?php endforeach; ?>
  </ol>
</section>
