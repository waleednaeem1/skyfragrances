<?php
defined('SKYFR') || exit;
$detailPath = '/admin/orders/' . $order['order_number'];
?>
<?php partial_admin('page-header.php', [
    'title' => 'Edit ' . $order['order_number'],
    'subtitle' => $itemsEditable ? 'Change the customer details, perfumes or payment. Changing perfumes re-prices the order at today\'s prices and adjusts stock.' : 'Update the customer details for this order.',
    'back' => $detailPath,
    'back_label' => (string) $order['order_number'],
]); ?>
<form method="post" action="<?= e(url($detailPath . '/edit')) ?>" class="adm-form" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="idem_key" value="<?= e($form['idem_key']) ?>">
  <input type="hidden" name="price_total" value="<?= e($form['price_total']) ?>">
  <input type="hidden" name="price_token" value="<?= e($form['price_token']) ?>">
<?php partial_admin('order-form-fields.php', ['form' => $form, 'errors' => $errors, 'problems' => $problems, 'priced' => $priced, 'sizeOptions' => $sizeOptions, 'methods' => $methods, 'cities' => $cities, 'itemsEditable' => $itemsEditable, 'showCoupon' => false, 'problemsTitle' => 'Before these changes are saved:', 'lockNote' => $lockNote, 'lockedItems' => $lockedItems]); ?>

<?php partial_admin('action-bar.php', [
    'note' => $priced !== null ? 'Check the new total, then save.' : 'Saved changes show on the order and its invoice straight away.',
    'buttons' => [
        ['label' => 'Save changes', 'variant' => 'gold', 'attr' => ['name' => 'action', 'value' => 'save']],
        ['label' => 'Cancel', 'href' => $detailPath, 'variant' => 'text'],
    ],
]); ?>
</form>
