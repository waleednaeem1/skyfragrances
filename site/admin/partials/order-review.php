<?php
defined('SKYFR') || exit;
$ready = $priced !== null && $errors === [];
$fm = static fn (int $paisa): string => money(money_from_paisa($paisa));
?>
<section class="adm-card adm-summarycard" aria-live="polite" aria-labelledby="order-summary-title">
  <div class="adm-card__head"><h2 class="adm-card__title" id="order-summary-title">Summary</h2><?php partial_admin('badge.php', ['label' => $ready ? 'Reviewed' : 'Draft', 'tone' => $ready ? 'green' : 'gray', 'small' => true]); ?></div>
<?php if ($ready): ?>
  <dl class="adm-dl">
<?php foreach ($priced['lines'] as $line): ?>
    <dt><?= e($line['product_name'] . ' · ' . $line['size_label'] . ' × ' . (int) $line['qty']) ?></dt><dd><?= e($fm((int) $line['line_total_paisa'])) ?></dd>
<?php endforeach; ?>
    <dt>Subtotal</dt><dd><?= e($fm((int) $priced['subtotal_paisa'])) ?></dd>
<?php if ((int) $priced['discount_paisa'] > 0): ?>
    <dt>Discount<?= $priced['coupon'] !== null ? ' (' . e((string) $priced['coupon']['code']) . ')' : '' ?></dt><dd>− <?= e($fm((int) $priced['discount_paisa'])) ?></dd>
<?php endif; ?>
    <dt>Delivery</dt><dd><?= (int) $priced['shipping_paisa'] === 0 ? 'Free' : e($fm((int) $priced['shipping_paisa'])) ?></dd>
  </dl>
  <p class="adm-summarycard__total"><span>Total to collect</span><strong><?= e($fm((int) $priced['grand_total_paisa'])) ?></strong></p>
<?php else: ?>
  <p class="adm-muted">Review first to see the final total. Nothing is saved until you confirm.</p>
<?php endif; ?>
</section>
