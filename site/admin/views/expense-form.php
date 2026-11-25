<?php
defined('SKYFR') || exit;
$action = $existing !== null ? '/admin/expenses/' . (int) $existing['id'] . '/edit' : '/admin/expenses/new';
$err = static fn (string $key): string => (string) ($errors[$key] ?? '');
$groups = $type === 'income'
    ? ['Money in' => FINANCE_INCOME_CATEGORIES, 'Spending' => FINANCE_EXPENSE_CATEGORIES]
    : ['Spending' => FINANCE_EXPENSE_CATEGORIES, 'Money in' => FINANCE_INCOME_CATEGORIES];
?>
<?php partial_admin('page-header.php', [
    'title' => $existing !== null ? 'Edit entry' : ($type === 'income' ? 'Add money in' : 'Add expense'),
    'subtitle' => $type === 'income' ? 'Money that comes in besides shop orders, such as a wholesale sale or new investment.' : 'Anything the business pays for. Stock purchases (perfume oils, bottles, packaging) are shown as cash spent but are not taken off profit twice, because each bottle\'s cost already is.',
    'back' => '/admin/expenses',
    'back_label' => 'Expenses',
]); ?>
<?php if ($errors !== []): ?>
<div class="adm-summary" role="alert">Please check the highlighted fields.</div>
<?php endif; ?>
<form method="post" action="<?= e(url($action)) ?>" class="adm-form" novalidate>
  <?= csrf_field() ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">What</h2>
    <div class="adm-field<?= $err('category') !== '' ? ' adm-field--error' : '' ?>">
      <label class="adm-field__label" for="category">Category <span class="adm-field__req" aria-hidden="true">*</span></label>
      <select class="adm-input adm-select" id="category" name="category" required<?= $err('category') !== '' ? ' aria-invalid="true"' : '' ?>>
        <option value="">Choose…</option>
<?php foreach ($groups as $group => $options): ?>
        <optgroup label="<?= e($group) ?>">
<?php foreach ($options as $value => $label): ?>
          <option value="<?= e($value) ?>"<?= $form['category'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
        </optgroup>
<?php endforeach; ?>
      </select>
<?php if ($err('category') !== ''): ?>
      <p class="adm-field__error"><?= e($err('category')) ?></p>
<?php endif; ?>
    </div>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'title', 'label' => 'What for', 'value' => $form['title'], 'required' => true, 'maxlength' => 160, 'placeholder' => 'e.g. Instagram ads for the Eid collection', 'error' => $err('title')]); ?>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'money', 'name' => 'amount', 'label' => 'Amount', 'value' => $form['amount'], 'required' => true, 'error' => $err('amount')]); ?>
      <?php partial_admin('field.php', ['type' => 'date', 'name' => 'entry_date', 'label' => 'Date', 'value' => $form['entry_date'], 'required' => true, 'error' => $err('entry_date'), 'help' => 'The day it was spent or billed. Statements use this date.']); ?>
    </div>
  </div>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Payment</h2>
    <?php partial_admin('field.php', ['type' => 'segment', 'name' => 'status', 'label' => 'Status', 'value' => $form['status'], 'options' => ['paid' => $type === 'income' ? 'Received' : 'Paid', 'due' => 'Still to pay'], 'error' => $err('status')]); ?>
    <div class="adm-field--pair">
      <div data-show-when="status=paid"><?php partial_admin('field.php', ['type' => 'date', 'name' => 'paid_on', 'label' => $type === 'income' ? 'Received on' : 'Paid on', 'value' => $form['paid_on'], 'error' => $err('paid_on'), 'help' => 'Leave as the date above if it was paid the same day.']); ?></div>
      <div data-show-when="status=due"><?php partial_admin('field.php', ['type' => 'date', 'name' => 'due_date', 'label' => 'Due date', 'value' => $form['due_date'], 'error' => $err('due_date'), 'help' => 'Shown under Bills to pay, and flagged when it passes.']); ?></div>
      <?php partial_admin('field.php', ['type' => 'select', 'name' => 'paid_from', 'label' => $type === 'income' ? 'Received by' : 'Paid with', 'value' => $form['paid_from'], 'options' => ['' => 'Not set'] + FINANCE_PAID_FROM, 'error' => $err('paid_from')]); ?>
    </div>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'vendor', 'label' => $type === 'income' ? 'Received from' : 'Paid to', 'value' => $form['vendor'], 'maxlength' => 120, 'placeholder' => $type === 'income' ? 'e.g. a wholesale buyer' : 'e.g. supplier, courier company, agency', 'error' => $err('vendor')]); ?>
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'reference', 'label' => 'Bill or receipt number', 'value' => $form['reference'], 'maxlength' => 80, 'error' => $err('reference')]); ?>
    </div>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'note', 'label' => 'Note', 'value' => $form['note'], 'rows' => 3, 'maxlength' => 500, 'error' => $err('note')]); ?>
  </div>
<?php partial_admin('action-bar.php', [
    'note' => 'Saved entries count in Statements straight away.',
    'buttons' => array_values(array_filter([
        ['label' => 'Save', 'variant' => 'gold', 'attr' => ['name' => 'action', 'value' => 'save']],
        $existing === null ? ['label' => 'Save and add another', 'variant' => 'ghost', 'attr' => ['name' => 'action', 'value' => 'save_new']] : null,
        ['label' => 'Cancel', 'href' => '/admin/expenses', 'variant' => 'text'],
    ])),
]); ?>
</form>
