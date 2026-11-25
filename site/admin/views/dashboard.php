<?php
defined('SKYFR') || exit;
$fm = static fn (int $paisa): string => money(money_from_paisa($paisa));
$short = static fn (int $paisa): string => (string) setting('currency_prefix', 'Rs.') . ' ' . dashboard_axis_label(intdiv($paisa, 100));
$plural = static fn (int $n, string $one, string $many): string => number_format($n) . ' ' . ($n === 1 ? $one : $many);
$range = ['from' => $periodDates['from'], 'to' => $periodDates['to']];
$statusTones = ['pending' => 'c5', 'confirmed' => 'c2', 'packing' => 'c1', 'shipped' => 'c6', 'delivered' => 'c4', 'cancelled' => 'c3'];
$statusLabels = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'packing' => 'Packing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
$methodTones = ['cod' => 'c5', 'bank' => 'c2', 'jazzcash' => 'c3', 'easypaisa' => 'c4'];
$methodRows = [];
foreach ($methods as $row) {
    $methodRows[$row['method']] = $row;
}
$billed = array_sum(array_column($methods, 'revenue'));
$manualDue = array_sum(array_map(static fn (array $r): int => payment_method_is_manual($r['method']) ? $r['due'] : 0, $methods));
$heroDelta = $kpis['revenue']['delta'];
$goalShare = $goal['target'] > 0 ? (int) min(999, round($goal['reached'] / $goal['target'] * 100)) : 0;
$collectTotal = $collection['collected'] + $collection['outstanding'];
$collectShare = $collectTotal > 0 ? (int) round($collection['collected'] / $collectTotal * 100) : 0;
$weekdayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
$heroBars = array_values(array_filter($charts['revenue']['bars'], static fn (array $bar): bool => !$bar['future']));
$lastHero = count($heroBars) - 1;
$busiest = max($weekdays) > 0 ? array_search(max($weekdays), $weekdays, true) : null;
$pipelineTotal = array_sum($statusCounts);
$quickActions = array_values(array_filter([
    team_can('orders') ? ['New phone order', '/admin/orders/new', 'package', 'c1'] : null,
    team_can('products') ? ['Add product', '/admin/products/new', 'droplet', 'c3'] : null,
    team_can('expenses') ? ['Add expense', '/admin/expenses/new', 'wallet', 'c4'] : null,
    team_can('coupons') ? ['New coupon', '/admin/coupons/new', 'tag', 'c5'] : null,
]));
$alerts = [];
if (!empty($attention['outbox']) && team_can('tools')) {
    $alerts[] = ['tone' => $attention['outbox']['level'] === 'error' ? 'c3' : 'c2', 'icon' => 'mail', 'title' => (string) $attention['outbox']['text'], 'text' => 'Emails to customers and to you are waiting.', 'action' => ['Fix in Tools', '/admin/tools']];
}
if ($attention['unpaid_transfers'] > 0 && team_can('orders')) {
    $alerts[] = ['tone' => 'c3', 'icon' => 'banknote', 'title' => $plural($attention['unpaid_transfers'], 'transfer order', 'transfer orders') . ' unpaid', 'text' => 'Waiting more than ' . $attention['hold_hours'] . ' hours for payment.', 'action' => ['Review', '/admin/orders/cancel-unpaid']];
}
if ($attention['pending_orders'] > 0 && team_can('orders')) {
    $alerts[] = ['tone' => 'c5', 'icon' => 'package', 'title' => $plural($attention['pending_orders'], 'order', 'orders') . ' to confirm', 'text' => 'New orders waiting for you to confirm them.', 'action' => ['Open', '/admin/orders?status=pending']];
}
if ($lowStock !== [] && team_can('products')) {
    $first = $lowStock[0];
    $alerts[] = ['tone' => 'c5', 'icon' => 'alert', 'title' => 'Low stock · ' . $plural(count($lowStock), 'size', 'sizes'), 'text' => $first['name'] . ' · ' . $first['size_label'] . ((int) $first['stock'] <= 0 ? ' is sold out.' : ' has ' . (int) $first['stock'] . ' left.'), 'action' => ['Restock', '/admin/products?stock=low']];
}
if ($attention['pending_reviews'] > 0 && team_can('reviews')) {
    $alerts[] = ['tone' => 'c1', 'icon' => 'star', 'title' => $plural($attention['pending_reviews'], 'review', 'reviews') . ' to approve', 'text' => 'Customers are waiting to see them on the shop.', 'action' => ['Open', '/admin/reviews?status=pending']];
}
if ($attention['new_messages'] > 0 && team_can('messages')) {
    $alerts[] = ['tone' => 'c2', 'icon' => 'mail', 'title' => $plural($attention['new_messages'], 'unread message', 'unread messages'), 'text' => 'From the contact form.', 'action' => ['Read', '/admin/messages?status=new']];
}
$activityIcons = ['order' => 'package', 'product' => 'droplet', 'collection' => 'layers', 'coupon' => 'tag', 'review' => 'star', 'setting' => 'settings', 'team' => 'id-card', 'expense' => 'wallet', 'message' => 'mail', 'subscriber' => 'user-plus', 'page' => 'file'];
$activityTones = ['order' => 'c1', 'product' => 'c3', 'collection' => 'c3', 'coupon' => 'c5', 'review' => 'c5', 'setting' => 'c2', 'team' => 'c2', 'expense' => 'c4', 'page' => 'c6'];
$headerActions = array_values(array_filter([
    team_can('statements') ? ['label' => 'Export', 'href' => dashboard_url($period['query'], '/admin/statements/export.xlsx'), 'variant' => 'secondary', 'icon' => 'download'] : null,
    team_can('orders') ? ['label' => 'New phone order', 'href' => '/admin/orders/new', 'variant' => 'primary', 'icon' => 'plus'] : null,
]));
?>
<?php if (!empty($installerPresent)): ?>
<div class="adm-banner" role="alert">install.php is still on the server. Delete it now from hPanel File Manager.</div>
<?php endif; ?>
<?php if (!empty($placeholderMethods)): ?>
<div class="adm-banner" role="alert">Customers cannot pay by <?= e(implode(', ', $placeholderMethods)) ?>: the account details are still the REPLACE ME placeholders, so these methods are hidden at checkout. <a href="<?= e(url('/admin/settings?tab=payments')) ?>">Enter the real account details in Settings › Payments</a>.</div>
<?php endif; ?>
<?php if (!empty($maintenanceOn)): ?>
<div class="adm-banner adm-banner--warn" role="status">The shop is in maintenance mode — customers see the closed page. <a href="<?= e(url('/admin/settings?tab=advanced')) ?>">Turn it off in Settings › Advanced</a>.</div>
<?php endif; ?>
<?php if (!empty($sampleRows)): ?>
<div class="adm-banner adm-banner--warn" role="status">This store is still showing sample data. <a href="<?= e(url('/admin/tools/remove-sample-data')) ?>">Remove it in Tools</a> before you go live.</div>
<?php endif; ?>
<?php if (!empty($originMismatch)): ?>
<div class="adm-banner adm-banner--info" role="status">You opened the panel at <?= e($panelOrigin) ?> but the site address is set to <?= e($siteUrl) ?>. <a href="<?= e(url('/admin/settings?tab=advanced')) ?>">Check Settings › Advanced</a>.</div>
<?php endif; ?>
<?php partial_admin('page-header.php', [
    'title' => $greeting . ($displayName !== '' ? ', ' . $displayName : ''),
    'subtitle' => $today . ' · here’s your shop at a glance.',
    'actions' => $headerActions,
]); ?>
<?php partial_admin('period-picker.php', ['picker' => $picker]); ?>

<div class="adm-bento adm-bento--hero">
  <section class="adm-hero" aria-labelledby="hero-title">
    <div class="adm-hero__top">
      <h2 class="adm-hero__label" id="hero-title"><span class="adm-hero__icon"><?php partial('icon.php', ['name' => 'chart', 'size' => 15]); ?></span>Revenue · <?= e($period['label']) ?></h2>
      <span class="adm-hero__delta adm-delta--<?= e($heroDelta['tone']) ?>"><?= e($heroDelta['text']) ?> vs <?= e($period['compare_label']) ?></span>
    </div>
    <div class="adm-hero__body">
      <div class="adm-hero__figure">
        <p class="adm-hero__value"><?= e($kpis['revenue']['value']) ?></p>
        <p class="adm-hero__sub"><?= e($plural($totals['orders'], 'order', 'orders') . ' · ' . $plural($totals['bottles'], 'bottle', 'bottles') . ' · ' . $kpis['average']['value'] . ' average') ?></p>
      </div>
      <?php partial_admin('vbars.php', ['dark' => true, 'label' => 'Revenue by ' . $period['unit'], 'bars' => array_map(static fn (array $bar, int $i): array => ['label' => $bar['tip'], 'value' => $bar['amount'], 'display' => $bar['value'], 'tone' => 'c1', 'current' => $i === $lastHero], $heroBars, array_keys($heroBars))]); ?>
    </div>
    <dl class="adm-hero__stats">
      <div class="adm-hero__stat adm-tone--c7"><dt>Today</dt><dd><?= e($fm($hero['today'])) ?></dd></div>
      <div class="adm-hero__stat adm-tone--c1"><dt>This week</dt><dd><?= e($fm($hero['week'])) ?></dd></div>
      <div class="adm-hero__stat adm-tone--c2"><dt><?= e($hero['best_label']) ?></dt><dd><?= e($hero['best']) ?></dd></div>
      <div class="adm-hero__stat adm-tone--c3"><dt>Cancelled</dt><dd><?= e((string) $statusCounts['cancelled']) ?></dd></div>
    </dl>
  </section>
  <section class="adm-card adm-card--tint adm-tone--c1 adm-goal" aria-labelledby="goal-title">
    <div class="adm-card__head"><h2 class="adm-card__title adm-card__title--tone" id="goal-title">Monthly goal</h2><?php if (team_can('settings')): ?><a class="adm-btn adm-btn--text adm-btn--sm" href="<?= e(url('/admin/settings?tab=store')) ?>"><?php partial('icon.php', ['name' => 'settings', 'size' => 14]); ?><?= $goal['target'] > 0 ? 'Edit' : 'Set goal' ?></a><?php endif; ?></div>
<?php if ($goal['target'] > 0): ?>
    <?php partial_admin('donut.php', ['segments' => [['label' => 'Reached', 'value' => min($goal['reached'], $goal['target']), 'tone' => 'c1']], 'total' => $goal['target'], 'center' => $goalShare . '%', 'caption' => 'of goal']); ?>
    <p class="adm-goal__text"><strong><?= e($fm($goal['reached'])) ?></strong> of <?= e($fm($goal['target'])) ?> for <?= e($goal['month']) ?></p>
    <p class="adm-goal__hint"><?= $goal['reached'] >= $goal['target'] ? 'Goal reached — ' . e($fm($goal['reached'] - $goal['target'])) . ' over.' : e($plural($goal['days_left'], 'day', 'days')) . ' left · about ' . e($fm(intdiv($goal['target'] - $goal['reached'], max(1, $goal['days_left'])))) . ' a day to hit it' ?></p>
<?php else: ?>
    <p class="adm-goal__hint">Set a monthly sales goal in Settings › Store to see how close this month is to it.</p>
<?php endif; ?>
  </section>
</div>

<div class="adm-grid">
<?php foreach (['revenue' => ['chart', 'c1'], 'orders' => ['package', 'c2'], 'average' => ['tag', 'c3'], 'bottles' => ['droplet', 'c4']] as $key => [$icon, $tone]): ?>
<?php partial_admin('kpi-card.php', ['label' => $kpis[$key]['label'], 'value' => $kpis[$key]['value'], 'delta' => $kpis[$key]['delta'], 'note' => $kpis[$key]['compare'], 'icon' => $icon, 'tone' => $tone, 'spark' => $spark[$key]]); ?>
<?php endforeach; ?>
</div>

<div class="adm-bento adm-bento--wide">
  <section class="adm-card adm-chartswitch" aria-labelledby="sales-title">
    <input class="adm-chartswitch__input u-sr-only" type="radio" name="dash-chart" id="dash-chart-revenue" checked>
    <input class="adm-chartswitch__input u-sr-only" type="radio" name="dash-chart" id="dash-chart-orders">
    <div class="adm-card__head">
      <div><h2 class="adm-card__title" id="sales-title">Sales by <?= e($period['unit']) ?></h2><p class="adm-card__sub">Confirmed, packing, shipped and delivered orders.</p></div>
      <div class="adm-segment" role="group" aria-label="Measure"><label class="adm-segment__label" for="dash-chart-revenue">Revenue</label><label class="adm-segment__label" for="dash-chart-orders">Orders</label></div>
    </div>
    <div class="adm-chartswitch__panels">
      <div class="adm-chartswitch__panel"><?php partial_admin('column-chart.php', ['chart' => $charts['revenue']]); ?></div>
      <div class="adm-chartswitch__panel"><?php partial_admin('column-chart.php', ['chart' => $charts['orders']]); ?></div>
    </div>
  </section>
  <section class="adm-card" aria-labelledby="mix-title">
    <div class="adm-card__head"><div><h2 class="adm-card__title" id="mix-title">Payment mix</h2><p class="adm-card__sub">Billed · <?= e($period['label']) ?></p></div></div>
    <?php partial_admin('donut.php', ['legend' => true, 'center' => $short($billed), 'caption' => 'billed', 'segments' => array_map(static fn (string $method): array => ['label' => payment_method_label($method), 'value' => $methodRows[$method]['revenue'] ?? 0, 'tone' => $methodTones[$method] ?? 'c6'], PAYMENT_METHODS)]); ?>
<?php if ($manualDue > 0): ?>
    <a class="adm-callout adm-callout--danger" href="<?= e(url('/admin/orders?' . http_build_query(['pay' => 'unpaid'] + $range))) ?>"><?php partial('icon.php', ['name' => 'alert', 'size' => 16]); ?><span><?= e($fm($manualDue)) ?> of transfers still unpaid.</span></a>
<?php endif; ?>
  </section>
</div>

<div class="adm-bento adm-bento--three">
  <section class="adm-card adm-card--tint adm-tone--c4" aria-labelledby="collect-title">
    <div class="adm-card__head"><h2 class="adm-card__title adm-card__title--tone" id="collect-title">Money collected</h2><span class="adm-card__figure"><?= e((string) $collectShare) ?>%</span></div>
    <p class="adm-bigfig"><?= e($fm($collection['collected'])) ?></p>
    <p class="adm-card__sub">received · <?= e($period['label']) ?></p>
    <div class="adm-meter" role="img" aria-label="<?= e($collectShare . '% collected, ' . $fm($collection['outstanding']) . ' still to collect') ?>"><span class="adm-meter__fill adm-tone--c4" style="width: <?= e((string) $collectShare) ?>%"></span><span class="adm-meter__fill adm-tone--c3" style="width: <?= e((string) (100 - $collectShare)) ?>%"></span></div>
    <ul class="adm-legend adm-legend--inline"><li class="adm-legend__item"><span class="adm-legend__dot adm-tone--c4" aria-hidden="true"></span>Collected</li><li class="adm-legend__item"><span class="adm-legend__dot adm-tone--c3" aria-hidden="true"></span>To collect <?= e($fm($collection['outstanding'])) ?></li></ul>
<?php if ($collection['outstanding'] > 0 && team_can('orders')): ?>
    <a class="adm-btn adm-btn--primary adm-btn--sm" href="<?= e(url('/admin/orders?' . http_build_query(['pay' => 'unpaid'] + $range))) ?>"><?php partial('icon.php', ['name' => 'whatsapp', 'size' => 15]); ?>Chase payments</a>
<?php endif; ?>
  </section>
<?php if ($quickActions !== []): ?>
  <section class="adm-card" aria-labelledby="quick-title">
    <div class="adm-card__head"><h2 class="adm-card__title" id="quick-title">Quick actions</h2></div>
    <ul class="adm-actions">
<?php foreach ($quickActions as [$label, $href, $icon, $tone]): ?>
      <li><a class="adm-action adm-tone--<?= e($tone) ?>" href="<?= e(url($href)) ?>"><span class="adm-action__icon"><?php partial('icon.php', ['name' => $icon, 'size' => 16]); ?></span><?= e($label) ?></a></li>
<?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>
  <section class="adm-card" aria-labelledby="sizes-title">
    <div class="adm-card__head"><div><h2 class="adm-card__title" id="sizes-title">Bottle sizes</h2><p class="adm-card__sub"><?= e($plural($totals['bottles'], 'bottle', 'bottles')) ?> sold</p></div></div>
    <?php partial_admin('donut.php', ['legend' => true, 'center' => number_format($totals['bottles']), 'caption' => $totals['bottles'] === 1 ? 'bottle' : 'bottles', 'segments' => array_map(static fn (array $row, int $i): array => ['label' => (string) $row['label'], 'value' => (int) $row['qty'], 'tone' => ['c1', 'c7', 'c3', 'c2', 'c4'][$i]], $sizes, array_keys($sizes))]); ?>
  </section>
</div>

<div class="adm-bento adm-bento--wide">
  <section class="adm-card" aria-labelledby="pipe-title">
    <div class="adm-card__head"><div><h2 class="adm-card__title" id="pipe-title">Order pipeline</h2><p class="adm-card__sub"><?= e($period['label']) ?> · tap a stage to open it</p></div><?php if (team_can('orders')): ?><a class="adm-btn adm-btn--text adm-btn--sm" href="<?= e(url('/admin/orders')) ?>">All orders<?php partial('icon.php', ['name' => 'chevron-right', 'size' => 14]); ?></a><?php endif; ?></div>
    <div class="adm-stackbar" aria-hidden="true">
<?php foreach ($statusCounts as $status => $count): if ($count > 0): ?>
      <span class="adm-stackbar__part adm-tone--<?= e($statusTones[$status]) ?>" style="flex-grow: <?= e((string) $count) ?>"></span>
<?php endif; endforeach; ?>
<?php if ($pipelineTotal === 0): ?>
      <span class="adm-stackbar__part adm-stackbar__part--empty"></span>
<?php endif; ?>
    </div>
    <ul class="adm-stages">
<?php foreach ($statusLabels as $status => $label): ?>
      <li><a class="adm-stage adm-tone--<?= e($statusTones[$status]) ?>" href="<?= e(url('/admin/orders?' . http_build_query(['status' => $status] + $range))) ?>"><span class="adm-stage__label"><?= e($label) ?></span><span class="adm-stage__count"><?= e((string) $statusCounts[$status]) ?></span></a></li>
<?php endforeach; ?>
    </ul>
  </section>
  <section class="adm-card" aria-labelledby="days-title">
    <div class="adm-card__head"><div><h2 class="adm-card__title" id="days-title">Busiest days</h2><p class="adm-card__sub">Orders by weekday</p></div></div>
    <?php partial_admin('vbars.php', ['values' => true, 'label' => 'Orders by weekday', 'bars' => array_map(static fn (int $day): array => ['label' => substr($weekdayNames[$day], 0, 3), 'value' => $weekdays[$day]], array_keys($weekdayNames))]); ?>
<?php if ($busiest !== null): ?>
    <p class="adm-note"><?= e($weekdayNames[$busiest]) ?> is your busiest day — a good day for a WhatsApp broadcast.</p>
<?php endif; ?>
  </section>
</div>

<div class="adm-bento adm-bento--three">
  <section class="adm-card" aria-labelledby="cities-title">
    <div class="adm-card__head"><div><h2 class="adm-card__title" id="cities-title">Top cities</h2><p class="adm-card__sub">Billed · <?= e($period['label']) ?></p></div></div>
<?php if ($cities === []): ?>
    <p class="adm-muted">No accepted orders in this period.</p>
<?php else: ?>
    <?php partial_admin('bar-list.php', ['rows' => array_map(static fn (array $c): array => ['label' => $c['city'], 'value' => $c['revenue'], 'display' => $fm($c['revenue']) . ' · ' . $c['orders']], $cities)]); ?>
<?php endif; ?>
  </section>
  <section class="adm-card" aria-labelledby="best-title">
    <div class="adm-card__head"><div><h2 class="adm-card__title" id="best-title">Best sellers</h2><p class="adm-card__sub"><?= e($period['label']) ?></p></div><?php if (team_can('products')): ?><a class="adm-btn adm-btn--text adm-btn--sm" href="<?= e(url('/admin/products')) ?>">Products<?php partial('icon.php', ['name' => 'chevron-right', 'size' => 14]); ?></a><?php endif; ?></div>
<?php if ($bestSellers === []): ?>
    <p class="adm-muted">No accepted orders in this period.</p>
<?php else: ?>
    <ol class="adm-ranked">
<?php foreach ($bestSellers as $i => $row): ?>
      <li class="adm-ranked__item">
        <span class="adm-ranked__rank adm-tone--<?= e(['c7', 'c1', 'c2', 'c6', 'c5'][$i] ?? 'c1') ?>"><?= e((string) ($i + 1)) ?></span>
        <img class="adm-thumb adm-thumb--sm" src="<?= e($row['product_id'] !== null ? catalogue_product_image_url((int) $row['product_id'], $row['image'], 'thumb') : asset('img/placeholder-4x5.svg')) ?>" alt="" width="36" height="44" loading="lazy" decoding="async">
        <span class="adm-ranked__body"><?php if ($row['product_id'] !== null && team_can('products')): ?><a class="adm-ranked__name" href="<?= e(url('/admin/products/' . (int) $row['product_id'])) ?>"><?= e((string) $row['name']) ?></a><?php else: ?><span class="adm-ranked__name"><?= e((string) $row['name']) ?></span><?php endif; ?><span class="adm-media__meta"><?= e((string) $row['qty']) ?> sold</span></span>
        <span class="adm-ranked__value"><?= e(money((string) $row['rev'])) ?></span>
      </li>
<?php endforeach; ?>
    </ol>
<?php endif; ?>
  </section>
  <div class="adm-alerts">
<?php foreach ($alerts as $alert): ?>
    <section class="adm-alert adm-tone--<?= e($alert['tone']) ?>">
      <span class="adm-alert__icon"><?php partial('icon.php', ['name' => $alert['icon'], 'size' => 16]); ?></span>
      <div class="adm-alert__body"><h2 class="adm-alert__title"><?= e($alert['title']) ?></h2><p class="adm-alert__text"><?= e($alert['text']) ?></p><a class="adm-btn adm-btn--secondary adm-btn--sm" href="<?= e(url($alert['action'][1])) ?>"><?= e($alert['action'][0]) ?></a></div>
    </section>
<?php endforeach; ?>
<?php if ($alerts === []): ?>
    <section class="adm-alert adm-tone--c4"><span class="adm-alert__icon"><?php partial('icon.php', ['name' => 'check', 'size' => 16]); ?></span><div class="adm-alert__body"><h2 class="adm-alert__title">Nothing needs you</h2><p class="adm-alert__text">No orders to confirm, no failed emails and no low stock.</p></div></section>
<?php endif; ?>
<?php if ($storage['percent'] !== null): ?>
    <section class="adm-alert adm-tone--<?= !empty($storage['red']) ? 'c3' : 'c2' ?>">
      <span class="adm-alert__icon"><?php partial('icon.php', ['name' => 'tool', 'size' => 16]); ?></span>
      <div class="adm-alert__body"><h2 class="adm-alert__title">Disk <?= e((string) $storage['percent']) ?>% full</h2><div class="adm-meter adm-meter--thin" aria-hidden="true"><span class="adm-meter__fill adm-tone--<?= !empty($storage['red']) ? 'c3' : 'c2' ?>" style="width: <?= e((string) $storage['percent']) ?>%"></span></div><p class="adm-alert__text">storage/ is using <?= e($storage['used_label']) ?><?= !empty($storage['red']) ? ' — run the privacy purge in Tools' : '' ?>.</p></div>
    </section>
<?php endif; ?>
<?php if ($lowThreshold === 0 && team_can('products')): ?>
    <p class="adm-note">Low-stock alerts are off (threshold 0). Sold-out sizes still show.</p>
<?php endif; ?>
  </div>
</div>

<div class="adm-bento adm-bento--wide">
  <section class="adm-card adm-card--flush" aria-labelledby="latest-title">
    <div class="adm-card__head"><h2 class="adm-card__title" id="latest-title">Latest orders</h2><?php if (team_can('orders')): ?><a class="adm-btn adm-btn--text adm-btn--sm" href="<?= e(url('/admin/orders')) ?>">All orders<?php partial('icon.php', ['name' => 'chevron-right', 'size' => 14]); ?></a><?php endif; ?></div>
<?php
$orderRows = [];
foreach ($latestOrders as $order) {
    ob_start();
    partial_admin('badge.php', ['status' => (string) $order['status'], 'small' => true]);
    partial_admin('badge.php', ['status' => (string) $order['payment_status'], 'label' => adm_order_payment_label((string) $order['payment_status']), 'small' => true]);
    $chips = (string) ob_get_clean();
    $orderRows[] = [
        'id' => (int) $order['id'],
        'href' => team_can('orders') ? '/admin/orders/' . $order['order_number'] . '/view' : '',
        'cells' => [
            'number' => '<span class="adm-mono">' . e((string) $order['order_number']) . '</span>',
            'customer' => '<span class="adm-media__title">' . e((string) $order['customer_name']) . '</span><span class="adm-media__meta">' . e((string) $order['city']) . '</span>',
            'status' => '<span class="adm-cluster">' . $chips . '</span>',
            'total' => '<strong class="adm-money">' . e(money((string) $order['grand_total'])) . '</strong>',
            'when' => e(adm_order_datetime((string) $order['created_at'])),
        ],
    ];
}
partial_admin('table.php', [
    'caption' => 'Latest orders',
    'list_id' => 'dash-orders',
    'columns' => [
        ['key' => 'number', 'label' => 'Order'],
        ['key' => 'customer', 'label' => 'Customer'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'total', 'label' => 'Total', 'align' => 'right'],
        ['key' => 'when', 'label' => 'Placed'],
    ],
    'rows' => $orderRows,
    'empty' => ['icon' => 'package', 'title' => 'No orders yet', 'text' => 'Share your shop link on WhatsApp to get started.'],
]);
?>
  </section>
<?php if (team_can('activity')): ?>
  <section class="adm-card" aria-labelledby="feed-title">
    <div class="adm-card__head"><div><h2 class="adm-card__title" id="feed-title">Live activity</h2><p class="adm-card__sub">Latest changes in the admin</p></div><a class="adm-btn adm-btn--text adm-btn--sm" href="<?= e(url('/admin/activity')) ?>">Activity log<?php partial('icon.php', ['name' => 'chevron-right', 'size' => 14]); ?></a></div>
<?php if ($activity === []): ?>
    <p class="adm-muted">Nothing has changed yet.</p>
<?php else: ?>
    <ol class="adm-feed">
<?php foreach ($activity as $row): $type = (string) $row['entity_type']; ?>
      <li class="adm-feed__item"><span class="adm-feed__icon adm-tone--<?= e($activityTones[$type] ?? 'c6') ?>"><?php partial('icon.php', ['name' => $activityIcons[$type] ?? 'activity', 'size' => 14]); ?></span><span class="adm-feed__body"><span class="adm-feed__text"><?= e((string) $row['summary']) ?></span><span class="adm-media__meta">by <?= e((string) ($row['admin_username'] ?? 'system')) ?></span></span><time class="adm-feed__time" datetime="<?= e(str_replace(' ', 'T', (string) $row['created_at'])) ?>"><?= e(adm_order_datetime((string) $row['created_at'])) ?></time></li>
<?php endforeach; ?>
    </ol>
<?php endif; ?>
  </section>
<?php endif; ?>
</div>
<?php if (!empty($lastLoginAt)): ?>
<p class="adm-note">Last sign in <?= e(adm_order_datetime((string) $lastLoginAt)) ?><?php if (team_can('activity')): ?> · <a href="<?= e(url('/admin/activity')) ?>">Activity log</a><?php endif; ?></p>
<?php endif; ?>
