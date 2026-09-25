<?php
defined('SKYFR') || exit;
$badge = static function (string $status, array $extra = []): string {
    ob_start();
    partial_admin('badge.php', ['status' => $status] + $extra);
    return (string) ob_get_clean();
};
$moneyHtml = static function (string $value, array $extra = []): string {
    ob_start();
    partial_admin('money.php', ['value' => $value] + $extra);
    return (string) ob_get_clean();
};
$tableRows = [];
foreach ($rows as $r) {
    $age = max(1, (int) floor((time() - strtotime((string) $r['created_at'])) / 86400));
    $tableRows[] = [
        'id' => (int) $r['id'],
        'href' => '/admin/orders/' . $r['order_number'],
        'cells' => [
            'order' => '<span class="adm-mono">' . e((string) $r['order_number']) . '</span>',
            'placed' => e(adm_order_datetime((string) $r['created_at'])) . ' <span class="adm-muted">(' . $age . 'd ago)</span>',
            'customer' => e((string) $r['customer_name']) . ' <span class="adm-muted">· ' . e((string) $r['city']) . '</span>',
            'payment' => $badge((string) $r['payment_method'], ['label' => payment_method_label((string) $r['payment_method']), 'small' => true]) . ' ' . $badge((string) $r['payment_status'], ['label' => adm_order_payment_label((string) $r['payment_status']), 'small' => true]),
            'total' => $moneyHtml((string) $r['grand_total']),
        ],
        'card' => [
            'title' => '<span class="adm-mono">' . e((string) $r['order_number']) . '</span>',
            'chip' => $badge((string) $r['payment_status'], ['label' => adm_order_payment_label((string) $r['payment_status']), 'small' => true]),
            'sub' => e(adm_order_date_compact((string) $r['created_at'])) . ' · ' . $age . 'd ago',
            'lines' => [['html' => e((string) $r['customer_name']) . ' · ' . e((string) $r['city'])]],
            'money' => $moneyHtml((string) $r['grand_total']),
            'aside' => $badge((string) $r['payment_method'], ['label' => payment_method_label((string) $r['payment_method']), 'small' => true]),
        ],
    ];
}
?>
<?php partial_admin('page-header.php', [
    'title' => 'Cancel unpaid transfer orders',
    'subtitle' => 'Bank, JazzCash and Easypaisa orders still pending and unpaid after ' . $hours . ' hours',
    'back' => '/admin/orders',
    'back_label' => 'Orders',
]); ?>

<?php if ($rows === []): ?>
<?php partial_admin('empty.php', ['title' => 'Nothing to cancel', 'text' => 'Every transfer order placed more than ' . $hours . ' hours ago has been paid, confirmed or cancelled already.', 'action' => ['label' => 'Back to orders', 'href' => '/admin/orders']]); ?>
<?php else: ?>
<div class="adm-banner adm-banner--warn" role="status"><strong><?= e((string) count($rows)) ?> order<?= count($rows) === 1 ? '' : 's' ?></strong> holding <?= e(money(money_from_paisa($holdTotal))) ?> of stock will be cancelled. Stock goes back on the shelf once per order, the coupon use is returned, and each customer with an email gets the cancellation email. COD orders are never included. This cannot be undone.</div>

<?php partial_admin('table.php', [
    'caption' => 'Unpaid transfer orders',
    'list_id' => 'cancel-unpaid-list',
    'columns' => [
        ['key' => 'order', 'label' => 'Order'],
        ['key' => 'placed', 'label' => 'Placed'],
        ['key' => 'customer', 'label' => 'Customer'],
        ['key' => 'payment', 'label' => 'Payment'],
        ['key' => 'total', 'label' => 'Total', 'align' => 'right', 'class' => 'adm-table__td--money'],
    ],
    'rows' => $tableRows,
]); ?>

<form method="post" action="<?= e(url('/admin/orders/cancel-unpaid')) ?>" class="adm-form adm-card" data-guard>
  <?= csrf_field() ?>
  <?php partial_admin('field.php', ['type' => 'text', 'name' => 'reason', 'id' => 'cancel-reason', 'label' => 'One reason for all of them', 'value' => 'Payment not received within ' . $hours . ' hours', 'required' => true, 'maxlength' => 200, 'help' => 'Written on every order and in the customer email.']); ?>
  <noscript>
  <?php partial_admin('field.php', ['type' => 'text', 'name' => 'confirm_word', 'id' => 'cancel-word', 'label' => 'Type CANCEL to confirm', 'value' => '', 'maxlength' => 10, 'attr' => ['autocapitalize' => 'characters', 'autocomplete' => 'off']]); ?>
  </noscript>
  <?php partial_admin('action-bar.php', ['static' => true, 'buttons' => [
      ['label' => 'Cancel ' . count($rows) . ' order' . (count($rows) === 1 ? '' : 's') . ' — permanent', 'variant' => 'danger', 'confirm' => "Cancel " . count($rows) . " unpaid transfer order" . (count($rows) === 1 ? '' : 's') . "?\nThis cannot be undone. Stock is restored once per order and each customer is emailed.", 'confirm_word' => 'CANCEL', 'confirm_label' => 'Yes, cancel them'],
      ['label' => 'Keep them', 'variant' => 'text', 'href' => '/admin/orders'],
  ]]); ?>
</form>
<?php endif; ?>
