<?php
defined('SKYFR') || exit;
$detailPath = '/admin/orders/' . $order['order_number'];
?>
<?php partial_admin('page-header.php', [
    'title' => 'Edit ' . $order['order_number'],
    'subtitle' => 'Change anything on this order: customer, perfumes, prices, delivery, discount and payment. Change the order status from the order page.',
    'back' => $detailPath,
    'back_label' => (string) $order['order_number'],
]); ?>
<form method="post" action="<?= e(url($detailPath . '/edit')) ?>" class="adm-form" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="idem_key" value="<?= e($form['idem_key']) ?>">
<?php partial_admin('order-form-fields.php', ['form' => $form, 'errors' => $errors, 'problems' => $problems, 'priced' => $priced, 'sizeOptions' => $sizeOptions, 'methods' => $methods, 'cities' => $cities, 'itemsEditable' => true, 'priceEditable' => true, 'paymentStatusOptions' => $paymentStatusOptions, 'showCoupon' => false, 'problemsTitle' => 'Before these changes are saved:']); ?>
<?php if ($fixedItems !== []): ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Kept as they are</h2>
    <p class="adm-field__help">These perfumes were removed from the shop, so they stay on the order unchanged.</p>
    <ul class="adm-list">
<?php foreach ($fixedItems as $item): ?>
      <li><span><?= e((string) $item['product_name']) ?> <span class="adm-muted"><?= e((string) $item['size_label']) ?></span></span><span>&times; <?= (int) $item['quantity'] ?> · <?= e(money((string) $item['line_total'])) ?></span></li>
<?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php partial_admin('action-bar.php', [
    'note' => 'Saved changes show on the order and its invoice straight away.',
    'buttons' => [
        ['label' => 'Save changes', 'variant' => 'gold', 'attr' => ['name' => 'action', 'value' => 'save']],
        ['label' => 'Cancel', 'href' => $detailPath, 'variant' => 'text'],
    ],
]); ?>
</form>
