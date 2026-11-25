<?php
defined('SKYFR') || exit;
$err = static fn (string $key): string => (string) ($errors[$key] ?? '');
$badge = static function (array $b): string {
    ob_start();
    partial_admin('badge.php', $b);
    return (string) ob_get_clean();
};
$tableRows = [];
foreach ($codes as $c) {
    $view = $c['view'];
    $digits = ltrim((string) $c['reward_phone'], '+');
    $firstName = trim(explode(' ', trim((string) $c['customer_name']))[0] ?? '');
    $actions = [['label' => 'Copy code', 'type' => 'button', 'attr' => ['data-copy' => (string) $c['code']]]];
    if ($view['usable']) {
        $actions[] = ['label' => 'Send on WhatsApp', 'href' => 'https://wa.me/' . $digits . '?text=' . rawurlencode(reward_message($view, $firstName)), 'external' => true];
    }
    if ($c['used_order'] === null) {
        $actions[] = ['label' => (int) $c['is_active'] === 1 ? 'Stop code' : 'Restart code', 'post' => '/admin/coupons/' . (int) $c['id'] . '/toggle', 'fields' => ['return' => $returnTo]];
    }
    $customer = '<a href="' . e(url('/admin/customers/' . $digits)) . '">' . e((string) ($c['customer_name'] ?? '')) . '</a><span class="adm-media__meta">' . e((string) ($c['customer_phone'] ?? '')) . '</span>';
    $issued = $c['issued_order'] !== null ? '<a href="' . e(url('/admin/orders/' . $c['issued_order'] . '/view')) . '">' . e((string) $c['issued_order']) . '</a><span class="adm-media__meta">' . e(date_short((string) $c['created_at'])) . '</span>' : e(date_short((string) $c['created_at']));
    $tableRows[] = [
        'id' => (int) $c['id'],
        'cells' => [
            'code' => '<span class="adm-mono">' . e((string) $c['code']) . '</span><span class="adm-media__meta">' . e($view['label']) . '</span>',
            'customer' => $customer,
            'issued' => $issued,
            'expires' => $c['expires_at'] === null ? '<span class="adm-muted">Never</span>' : e(date_short((string) $c['expires_at'])),
            'status' => $badge($c['state']),
        ],
        'card' => [
            'title' => '<span class="adm-mono">' . e((string) $c['code']) . '</span>',
            'chip' => $badge($c['state'] + ['small' => true]),
            'sub' => e((string) ($c['customer_name'] ?? '')) . ' · ' . e((string) ($c['customer_phone'] ?? '')),
            'lines' => [
                ['label' => 'Discount', 'html' => e($view['label'])],
                ['label' => 'Given on', 'html' => $issued],
                ['label' => 'Expires', 'html' => $c['expires_at'] === null ? 'Never' : e(date_short((string) $c['expires_at']))],
            ],
            'inline_actions' => 1,
        ],
        'actions' => $actions,
    ];
}
$conversion = $stats['issued'] > 0 ? round($stats['used'] / $stats['issued'] * 100) : 0;
?>
<?php partial_admin('page-header.php', [
    'title' => 'Thank-you coupon',
    'subtitle' => 'After a customer\'s first website order, the shop gives them a one-time code for their second order. Each code works only for that customer\'s phone number.',
    'back' => '/admin/coupons',
    'back_label' => 'Coupons',
]); ?>
<div class="adm-grid">
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Status</span><span class="adm-kpi__value"><?= $config['enabled'] ? 'On' : 'Off' ?></span><p class="adm-delta adm-delta--flat"><span><?= $config['enabled'] ? e($preview['label']) . ' the second order' : 'No new codes are given' ?></span></p></div>
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Codes given</span><span class="adm-kpi__value"><?= e((string) $stats['issued']) ?></span><p class="adm-delta adm-delta--flat"><span><?= e((string) $stats['waiting']) ?> waiting to be used</span></p></div>
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Second orders</span><span class="adm-kpi__value"><?= e((string) $stats['used']) ?></span><p class="adm-delta adm-delta--flat"><span><?= e((string) $conversion) ?>% of codes used</span></p></div>
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Sales from codes</span><span class="adm-kpi__value"><?= e(money($stats['billed'])) ?></span><p class="adm-delta adm-delta--flat"><span><?= e(money($stats['discount'])) ?> given as discount</span></p></div>
</div>
<div class="adm-duo">
  <section class="adm-card">
    <div class="adm-card__head"><h2 class="adm-card__title">Settings</h2></div>
<?php if ($errors !== []): ?>
    <div class="adm-summary" role="alert">Please check the highlighted fields.</div>
<?php endif; ?>
    <form method="post" action="<?= e(url('/admin/coupons/reward')) ?>" class="adm-form" novalidate>
      <?= csrf_field() ?>
      <?php partial_admin('field.php', ['type' => 'checkbox', 'name' => 'enabled', 'label' => 'Give a thank-you code after a customer\'s first website order', 'value' => $form['enabled'] === '1']); ?>
      <?php partial_admin('field.php', ['type' => 'segment', 'name' => 'type', 'label' => 'Discount', 'value' => $form['type'], 'options' => ['percent' => 'Percent off', 'fixed' => 'Rupees off']]); ?>
      <div class="adm-field--pair">
        <?php partial_admin('field.php', ['type' => 'int', 'name' => 'value', 'label' => 'Discount amount', 'value' => $form['value'], 'required' => true, 'maxlength' => 9, 'error' => $err('value'), 'help' => 'A percent such as 10, or rupees such as 500.']); ?>
        <?php partial_admin('field.php', ['type' => 'int', 'name' => 'valid_days', 'label' => 'Valid for (days)', 'value' => $form['valid_days'], 'required' => true, 'maxlength' => 3, 'error' => $err('valid_days'), 'help' => '0 means the code never expires.']); ?>
      </div>
      <?php partial_admin('field.php', ['type' => 'money', 'name' => 'min_order', 'label' => 'Minimum second order (optional)', 'value' => $form['min_order'], 'error' => $err('min_order'), 'help' => 'Leave empty for no minimum.']); ?>
      <p class="adm-note">Turning this off stops new codes. Codes already given keep working until they expire, unless you stop them below. Changes apply to new codes only.</p>
      <div class="adm-cluster"><?php partial_admin('button.php', ['label' => 'Save', 'type' => 'submit', 'variant' => 'gold']); ?></div>
    </form>
  </section>
  <section class="adm-card">
    <div class="adm-card__head"><h2 class="adm-card__title">What the customer sees</h2><span class="adm-muted">Example</span></div>
    <div class="adm-reward">
      <p class="adm-reward__eyebrow">A thank-you for your first order</p>
      <p class="adm-reward__title"><?= e($preview['label']) ?> your next order</p>
      <p class="adm-reward__code"><?= e($preview['code']) ?></p>
      <p class="adm-reward__terms"><?= e(reward_terms($preview)) ?></p>
    </div>
    <ul class="adm-steps">
      <li>Shown on the order confirmation page, the order tracking page and in the confirmation email.</li>
      <li>One code per phone number, given only once, on the first order placed on the website. Orders you enter yourself do not get one.</li>
      <li>It works once, and only on that phone number's second order. If the second order is placed without it, the code stops working.</li>
      <li>If the first order is cancelled, the code stops working. If the second order is cancelled, the code can be used again.</li>
      <li>Use Send on WhatsApp below to share a code with a customer who did not give an email.</li>
    </ul>
  </section>
</div>
<section class="adm-card adm-card--flush">
  <div class="adm-card__head"><h2 class="adm-card__title">Codes given</h2><span class="adm-muted"><?= e((string) $total) ?> in total</span></div>
<?php partial_admin('table.php', [
    'caption' => 'Thank-you codes',
    'list_id' => 'reward-codes',
    'columns' => [
        ['key' => 'code', 'label' => 'Code'],
        ['key' => 'customer', 'label' => 'Customer'],
        ['key' => 'issued', 'label' => 'First order'],
        ['key' => 'expires', 'label' => 'Expires'],
        ['key' => 'status', 'label' => 'Status'],
    ],
    'rows' => $tableRows,
    'empty' => ['title' => 'No codes given yet', 'text' => $config['enabled'] ? 'The next first-time website order will get one.' : 'Turn the thank-you code on above to start.'],
]); ?>
</section>
<?php partial_admin('pagination.php', ['page' => $page, 'per' => $per, 'total' => $total, 'noun' => 'codes', 'path' => '/admin/coupons/reward', 'query' => []]); ?>
