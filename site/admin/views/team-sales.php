<?php
defined('SKYFR') || exit;
$fm = static fn (int $paisa): string => ($paisa < 0 ? '−' : '') . money(money_from_paisa(abs($paisa)));
$badge = static function (array $b): string {
    ob_start();
    partial_admin('badge.php', $b);
    return (string) ob_get_clean();
};
$rate = $member['commission_rate'] !== null ? rtrim(rtrim((string) $member['commission_rate'], '0'), '.') . '%' : 'No share';
$canOrders = team_can('orders');
$tableRows = [];
foreach ($rows as $row) {
    $c = $row['commission'];
    $number = (string) $row['order_number'];
    $orderHtml = $canOrders ? '<a href="' . e(url('/admin/orders/' . $number . '/view')) . '">' . e($number) . '</a>' : e($number);
    $state = team_state_badge($c['state']);
    $actions = [];
    if ($isOwnerView) {
        if (in_array($c['state'], ['ready', 'waiting'], true) && $c['share'] > 0) {
            $actions[] = ['label' => 'Mark paid', 'post' => '/admin/team/share/' . $number, 'fields' => ['action' => 'paid', 'paid_on' => $today, 'return' => $returnTo], 'confirm' => 'Mark ' . $fm($c['share']) . ' as paid to ' . $member['display_name'] . ' today for ' . $number . '?' . ($c['state'] === 'waiting' ? "\nThis order is not delivered and fully paid yet." : ''), 'confirm_label' => 'Mark paid'];
        }
        if ($c['state'] === 'paid') {
            $actions[] = ['label' => 'Not paid', 'post' => '/admin/team/share/' . $number, 'fields' => ['action' => 'unpaid', 'return' => $returnTo], 'confirm' => 'Mark the share on ' . $number . ' as not paid?', 'confirm_label' => 'Mark not paid'];
        }
        $actions[] = ['label' => 'Details', 'href' => '/admin/team/share/' . $number . '?return=' . rawurlencode($returnTo)];
    }
    $profitHtml = '<span class="adm-money' . ($c['profit'] < 0 ? ' adm-stock--low' : '') . '">' . e($fm($c['profit'])) . '</span>' . ($c['uncosted'] ? '<span class="adm-media__meta">cost missing</span>' : '');
    $shareHtml = '<strong class="adm-money">' . e($fm($c['share'])) . '</strong>' . ($c['override'] && $c['state'] !== 'paid' ? '<span class="adm-media__meta">set by hand</span>' : '');
    $tableRows[] = [
        'id' => (int) $row['id'],
        'cells' => [
            'order' => $orderHtml . '<span class="adm-media__meta">' . e(date_short((string) $row['created_at'])) . ' · ' . e((string) $row['customer_name']) . '</span>',
            'net' => '<span class="adm-money">' . e($fm($c['net'])) . '</span>',
            'cost' => '<span class="adm-money">' . e($fm($c['cogs'])) . '</span>',
            'profit' => $profitHtml,
            'rate' => e($c['rate'] !== null ? rtrim(rtrim($c['rate'], '0'), '.') . '%' : '—'),
            'share' => $shareHtml,
            'status' => $badge($state) . ($row['commission_paid_at'] !== null ? '<span class="adm-media__meta">' . e(date_short((string) $row['commission_paid_at'])) . '</span>' : '') . ($c['paid_warning'] ? '<span class="adm-media__meta adm-stock--low">Order was cancelled after payment</span>' : ''),
        ],
        'card' => [
            'title' => $orderHtml,
            'chip' => $badge($state + ['small' => true]),
            'sub' => e(date_short((string) $row['created_at'])) . ' · ' . e((string) $row['customer_name']),
            'lines' => [
                ['label' => 'Profit', 'html' => $profitHtml],
                ['label' => 'Share', 'html' => $shareHtml],
            ],
            'inline_actions' => 1,
        ],
        'actions' => $actions,
    ];
}
$chips = ['' => 'All', 'ready' => 'Ready to pay', 'waiting' => 'Waiting', 'paid' => 'Paid'];
?>
<?php partial_admin('page-header.php', [
    'title' => $isOwnerView ? (string) $member['display_name'] : 'My sales',
    'subtitle' => $isOwnerView
        ? $rate . ' of profit' . (!empty($member['job_title']) ? ' · ' . $member['job_title'] : '') . '. Orders credited to them and the share owed on each.'
        : 'Orders credited to you and your ' . $rate . ' share of their profit. The owner marks each share as paid.',
    'back' => $isOwnerView ? '/admin/team' : null,
    'back_label' => 'Team',
    'actions' => $isOwnerView ? [['label' => 'Edit member', 'href' => '/admin/team/' . (int) $member['id'], 'variant' => 'ghost']] : [],
]); ?>
<section class="adm-card adm-promo">
  <div>
    <h2 class="adm-card__title">Ready to pay: <?= e($fm($owed['ready'])) ?></h2>
    <p class="adm-promo__text"><?= e((string) $owed['ready_count']) ?> <?= $owed['ready_count'] === 1 ? 'order is' : 'orders are' ?> delivered and fully paid by the customer, with the share not paid yet. <?= e($fm($owed['waiting'])) ?> more is waiting for delivery or payment. All dates.</p>
  </div>
<?php if ($isOwnerView && $owed['ready_count'] > 0): ?>
  <form method="post" action="<?= e(url('/admin/team/' . (int) $member['id'] . '/pay-ready')) ?>" class="adm-cluster" data-confirm="<?= e('Mark ' . $owed['ready_count'] . ' ready ' . ($owed['ready_count'] === 1 ? 'share' : 'shares') . ' as paid to ' . $member['display_name'] . ': ' . $fm($owed['ready']) . '?') ?>" data-confirm-label="Mark all paid">
    <?= csrf_field() ?>
    <input type="hidden" name="return" value="<?= e($returnTo) ?>">
    <label class="u-sr-only" for="pay-ready-date">Paid on</label>
    <input class="adm-input adm-input--sm" type="date" id="pay-ready-date" name="paid_on" value="<?= e($today) ?>" max="<?= e($today) ?>">
    <?php partial_admin('button.php', ['label' => 'Mark all ready as paid', 'type' => 'submit', 'variant' => 'gold']); ?>
  </form>
<?php endif; ?>
</section>
<?php partial_admin('period-picker.php', ['picker' => $picker]); ?>
<div class="adm-grid">
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Orders</span><span class="adm-kpi__value"><?= e((string) $totals['orders']) ?></span><p class="adm-delta adm-delta--flat"><span><?= e($period['label']) ?></span></p></div>
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Sales</span><span class="adm-kpi__value"><?= e($fm($totals['net'])) ?></span><p class="adm-delta adm-delta--flat"><span>Perfumes, after discounts</span></p></div>
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Profit</span><span class="adm-kpi__value"><?= e($fm($totals['profit'])) ?></span><p class="adm-delta adm-delta--flat"><span>After cost per bottle</span></p></div>
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Share earned</span><span class="adm-kpi__value"><?= e($fm($totals['share'])) ?></span><p class="adm-delta adm-delta--flat"><span><?= e($fm($totals['paid'])) ?> paid · <?= e($fm($totals['ready'] + $totals['waiting'])) ?> not paid</span></p></div>
</div>
<nav class="adm-rail" aria-label="Filter shares">
<?php foreach ($chips as $key => $label): ?>
  <a class="adm-chip<?= $show === $key ? ' is-active' : '' ?>" href="<?= e(url(dashboard_url(array_filter($period['query'] + ['show' => $key], 'strlen'), $base))) ?>"<?= $show === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
</nav>
<section class="adm-card adm-card--flush">
<?php partial_admin('table.php', [
    'caption' => 'Orders and shares',
    'list_id' => 'sales-list',
    'columns' => [
        ['key' => 'order', 'label' => 'Order'],
        ['key' => 'net', 'label' => 'Sales', 'align' => 'right'],
        ['key' => 'cost', 'label' => 'Cost', 'align' => 'right'],
        ['key' => 'profit', 'label' => 'Profit', 'align' => 'right'],
        ['key' => 'rate', 'label' => 'Rate', 'align' => 'right'],
        ['key' => 'share', 'label' => 'Share', 'align' => 'right'],
        ['key' => 'status', 'label' => 'Status'],
    ],
    'rows' => $tableRows,
    'empty' => ['title' => 'No orders in this period', 'text' => $isOwnerView ? 'Orders credited to ' . $member['display_name'] . ' will show here.' : 'Orders credited to you will show here.'],
]); ?>
</section>
