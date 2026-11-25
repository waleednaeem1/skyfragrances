<?php
defined('SKYFR') || exit;
$reviewed = $priced !== null && $problems === [] && $errors === [];
?>
<?php partial_admin('page-header.php', [
    'title' => 'New phone order',
    'subtitle' => 'For orders taken by phone or WhatsApp. Stock, totals and the invoice work exactly like a website order.',
    'back' => '/admin/orders',
    'back_label' => 'Orders',
]); ?>
<form method="post" action="<?= e(url('/admin/orders/new')) ?>" class="adm-form" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="idem_key" value="<?= e($form['idem_key']) ?>">
  <input type="hidden" name="price_total" value="<?= e($form['price_total']) ?>">
  <input type="hidden" name="price_token" value="<?= e($form['price_token']) ?>">

<?php partial_admin('order-form-fields.php', ['form' => $form, 'errors' => $errors, 'problems' => $problems, 'priced' => $priced, 'sizeOptions' => $sizeOptions, 'methods' => $methods, 'cities' => $cities, 'itemsEditable' => true, 'showCoupon' => true]); ?>
<?php if ($selfRate !== null): ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Salesperson</h2>
    <p class="adm-note">This order will be credited to you at your <?= e($selfRate) ?>% share of its profit.</p>
  </div>
<?php elseif ($sellers !== []): ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Salesperson</h2>
<?php
$sellerOptions = ['0' => 'No salesperson'];
foreach ($sellers as $seller) {
    $sellerOptions[(string) $seller['id']] = $seller['display_name'] . ' · ' . rtrim(rtrim((string) $seller['commission_rate'], '0'), '.') . '% of profit';
}
partial_admin('field.php', ['type' => 'select', 'name' => 'sales_member_id', 'label' => 'Credit this order to', 'value' => (string) $salesMemberId, 'options' => $sellerOptions, 'help' => 'They earn their share of this order\'s profit. You can change it later from the order.']);
?>
  </div>
<?php endif; ?>

<?php partial_admin('action-bar.php', [
    'note' => $reviewed ? 'Check the total with the customer, then create the order.' : 'Review first to see the total.',
    'buttons' => $reviewed
        ? [
            ['label' => 'Create order', 'variant' => 'gold', 'attr' => ['name' => 'action', 'value' => 'create']],
            ['label' => 'Update review', 'variant' => 'ghost', 'attr' => ['name' => 'action', 'value' => 'review']],
        ]
        : [
            ['label' => 'Review order', 'variant' => 'gold', 'attr' => ['name' => 'action', 'value' => 'review']],
        ],
]); ?>
</form>
