<?php
defined('SKYFR') || exit;
$itemsEditable = !empty($itemsEditable);
$showCoupon = !empty($showCoupon);
$priceEditable = !empty($priceEditable);
$paymentStatusOptions = is_array($paymentStatusOptions ?? null) ? $paymentStatusOptions : [];
$lockNote = (string) ($lockNote ?? '');
$lockedItems = is_array($lockedItems ?? null) ? $lockedItems : [];
$fieldLabels = ['payment_status' => 'Payment status', 'paid_at' => 'Payment received on', 'received_at' => 'Order received', 'delivery_fee' => 'Delivery fee', 'discount' => 'Discount', 
    'customer_name' => 'Name', 'customer_phone' => 'Mobile number', 'customer_email' => 'Email', 'city' => 'City',
    'address' => 'Address', 'postal_code' => 'Postal code', 'customer_note' => 'Note', 'payment_method' => 'Payment',
];
$methodOptions = [];
foreach ($methods as $method) {
    $methodOptions[$method] = payment_method_label($method);
}
?>
<?php if ($errors !== []): ?>
<div class="adm-summary" role="alert">
  <p>Please fix the following:</p>
  <ul>
<?php foreach ($errors as $field => $message): ?>
    <li><a href="#<?= e(str_starts_with($field, 'line_') ? 'lines' : $field) ?>"><?= e($fieldLabels[$field] ?? 'Perfumes') ?>: <?= e($message) ?></a></li>
<?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<?php if ($problems !== []): ?>
<div class="adm-summary" role="alert">
  <p><?= e((string) ($problemsTitle ?? 'Before this order can be created:')) ?></p>
  <ul>
<?php foreach ($problems as $problem): ?>
    <li><?= e($problem) ?></li>
<?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Order</h2>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'datetime', 'name' => 'received_at', 'label' => 'Order received', 'value' => (string) ($form['received_at'] ?? ''), 'required' => true, 'error' => $errors['received_at'] ?? '', 'help' => 'When the customer placed the order. Reports and the customer ledger use this date.', 'attr' => ['max' => order_datetime_input()]]); ?>
<?php if (!empty($salesField) && $salesField['mode'] === 'choose'): ?>
      <?php partial_admin('field.php', ['type' => 'select', 'name' => 'sales_member_id', 'label' => 'Sold by', 'value' => $salesField['value'], 'options' => $salesField['options'], 'help' => $salesField['help']]); ?>
<?php elseif (!empty($salesField)): ?>
      <div class="adm-field"><span class="adm-field__label">Sold by</span><p class="adm-field__static"><?= e((string) $salesField['text']) ?></p><p class="adm-field__help"><?= e((string) $salesField['help']) ?></p></div>
<?php endif; ?>
    </div>
  </div>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Customer</h2>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'customer_name', 'label' => 'Name', 'value' => $form['customer_name'], 'required' => true, 'maxlength' => 120, 'error' => $errors['customer_name'] ?? '']); ?>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'phone', 'name' => 'customer_phone', 'label' => 'Mobile number', 'value' => $form['customer_phone'], 'required' => true, 'error' => $errors['customer_phone'] ?? '', 'help' => 'e.g. 0300 1234567']); ?>
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'customer_email', 'label' => 'Email (optional)', 'value' => $form['customer_email'], 'maxlength' => 190, 'error' => $errors['customer_email'] ?? '', 'help' => 'If given, the customer gets the order email.', 'attr' => ['inputmode' => 'email', 'autocomplete' => 'off']]); ?>
    </div>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'city', 'label' => 'City', 'value' => $form['city'], 'required' => true, 'maxlength' => 80, 'error' => $errors['city'] ?? '', 'attr' => ['list' => 'order-city-list', 'autocomplete' => 'off']]); ?>
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'postal_code', 'label' => 'Postal code (optional)', 'value' => $form['postal_code'], 'maxlength' => 12, 'error' => $errors['postal_code'] ?? '']); ?>
    </div>
    <datalist id="order-city-list">
<?php foreach ($cities as $city): ?>
      <option value="<?= e((string) $city) ?>"></option>
<?php endforeach; ?>
    </datalist>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'address', 'label' => 'Delivery address', 'value' => $form['address'], 'required' => true, 'maxlength' => 400, 'error' => $errors['address'] ?? '', 'help' => 'House, street, area.']); ?>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'customer_note', 'label' => 'Note (optional)', 'value' => $form['customer_note'], 'maxlength' => 500, 'error' => $errors['customer_note'] ?? '', 'help' => 'Shown on the order and the packing slip.']); ?>
  </div>

<?php if ($itemsEditable): ?>
  <div class="adm-form__section" id="lines">
    <h2 class="adm-form__section-title">Perfumes</h2>
    <p class="adm-field__help"><?= $priceEditable ? 'Change perfumes, quantities or prices. Leave the price empty to use the shop price. Set a line to "No perfume" to remove it.' : 'Choose up to ' . (int) ORDER_FORM_LINES . ' lines. Leave a line on "No perfume" to skip it.' ?></p>
<?php foreach ($form['lines'] as $i => $line): ?>
    <div class="<?= $priceEditable ? 'adm-order-line' : 'adm-field--pair' ?>">
      <div class="adm-field">
        <label class="adm-field__label" for="line_size_<?= (int) $i ?>">Line <?= (int) $i + 1 ?></label>
        <select class="adm-input" id="line_size_<?= (int) $i ?>" name="line_size[]"<?= isset($errors['line_' . $i]) ? ' aria-invalid="true"' : '' ?>>
          <option value="0">No perfume</option>
<?php foreach ($sizeOptions as $sizeId => $option): ?>
          <option value="<?= (int) $sizeId ?>"<?= $line['size_id'] === $sizeId ? ' selected' : '' ?><?= !$option['in_stock'] && $line['size_id'] !== $sizeId ? ' disabled' : '' ?>><?= e($option['label']) ?></option>
<?php endforeach; ?>
        </select>
<?php if (isset($errors['line_' . $i])): ?>
        <p class="adm-field__error"><?= e($errors['line_' . $i]) ?></p>
<?php endif; ?>
      </div>
      <div class="adm-field">
        <label class="adm-field__label" for="line_qty_<?= (int) $i ?>">Quantity</label>
        <input class="adm-input" type="number" id="line_qty_<?= (int) $i ?>" name="line_qty[]" min="1" max="<?= (int) CART_MAX_QTY_PER_LINE ?>" step="1" inputmode="numeric" value="<?= (int) $line['qty'] ?>">
      </div>
<?php if ($priceEditable): ?>
      <div class="adm-field">
        <label class="adm-field__label" for="line_price_<?= (int) $i ?>">Price each (Rs.)</label>
        <input class="adm-input" type="text" id="line_price_<?= (int) $i ?>" name="line_price[]" inputmode="decimal" autocomplete="off" placeholder="Shop price" value="<?= e((string) ($line['price'] ?? '')) ?>">
      </div>
<?php endif; ?>
    </div>
<?php endforeach; ?>
  </div>

<?php else: ?>
  <div class="adm-form__section" id="lines">
    <h2 class="adm-form__section-title">Perfumes</h2>
    <p class="adm-field__help"><?= e($lockNote) ?></p>
    <ul class="adm-list">
<?php foreach ($lockedItems as $item): ?>
      <li><span><?= e((string) $item['product_name']) ?> <span class="adm-muted"><?= e((string) $item['size_label']) ?></span></span><span>&times; <?= (int) $item['quantity'] ?></span></li>
<?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if ($itemsEditable): ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Payment</h2>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'select', 'name' => 'payment_method', 'label' => 'How the customer pays', 'value' => $form['payment_method'], 'options' => $methodOptions, 'required' => true, 'error' => $errors['payment_method'] ?? '', 'help' => 'Mark it paid from the order page once the money arrives.']); ?>
<?php if ($priceEditable): ?>
      <?php partial_admin('field.php', ['type' => 'select', 'name' => 'payment_status', 'label' => 'Payment status', 'value' => (string) ($form['payment_status'] ?? ''), 'options' => $paymentStatusOptions, 'error' => $errors['payment_status'] ?? '']); ?>
    </div>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'datetime', 'name' => 'paid_at', 'label' => 'Payment received on', 'value' => (string) ($form['paid_at'] ?? ''), 'error' => $errors['paid_at'] ?? '', 'help' => 'Used when the payment status is Paid. Set the day the money actually arrived.', 'attr' => ['max' => order_datetime_input()]]); ?>
    </div>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'delivery_fee', 'label' => 'Delivery fee (Rs.)', 'value' => (string) ($form['delivery_fee'] ?? ''), 'error' => $errors['delivery_fee'] ?? '', 'help' => '0 for free delivery.', 'attr' => ['inputmode' => 'decimal', 'autocomplete' => 'off']]); ?>
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'discount', 'label' => 'Discount (Rs.)', 'value' => (string) ($form['discount'] ?? ''), 'error' => $errors['discount'] ?? '', 'help' => 'Taken off the perfumes total.', 'attr' => ['inputmode' => 'decimal', 'autocomplete' => 'off']]); ?>
<?php endif; ?>
<?php if ($showCoupon): ?>
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'coupon_code', 'label' => 'Coupon code (optional)', 'value' => $form['coupon_code'], 'maxlength' => 32, 'attr' => ['autocomplete' => 'off']]); ?>
<?php endif; ?>
    </div>
  </div>

<?php endif; ?>

