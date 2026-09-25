<?php
defined('SKYFR') || exit;
$plural = static fn (int $n, string $one, string $many): string => $n . ' ' . ($n === 1 ? $one : $many);
partial_admin('page-header.php', ['title' => 'Tools', 'subtitle' => 'Housekeeping for the server and the data. Every button here asks before it acts.']);
?>
<?php if (!empty($installerPresent)): ?>
<div class="adm-banner" role="alert">install.php is still on the server. Delete it now from hPanel File Manager.</div>
<?php endif; ?>
<?php if (!empty($maintenanceFlag)): ?>
<div class="adm-banner adm-banner--warn" role="status">The shop is closed by the <span class="adm-mono">storage/MAINTENANCE</span> file. Delete that file in File Manager to reopen; the Settings switch cannot override it.</div>
<?php elseif (!empty($maintenanceSetting)): ?>
<div class="adm-banner adm-banner--warn" role="status">Maintenance mode is on. <a href="<?= e(url('/admin/settings?tab=advanced')) ?>">Turn it off in Settings › Advanced</a>.</div>
<?php endif; ?>
<div class="adm-grid">
  <div class="adm-kpi">
    <span class="adm-kpi__value<?= !empty($storage['red']) ? ' adm-stock--low' : '' ?>"><?= e($storage['used_label']) ?></span>
    <span class="adm-kpi__label">storage/ in use</span>
    <p class="adm-note"><?= $storage['percent'] !== null ? 'Disk ' . e((string) $storage['percent']) . '% full' : 'Disk quota not reported' ?><?= !empty($storage['red']) ? ' — run the privacy purge' : '' ?></p>
  </div>
  <div class="adm-kpi">
    <span class="adm-kpi__value"><?= e((string) $storage['proof_files']) ?></span>
    <span class="adm-kpi__label">Payment proofs on disk</span>
    <p class="adm-note"><?= e($storage['proof_label']) ?></p>
  </div>
  <div class="adm-kpi">
    <span class="adm-kpi__value<?= $outbox['failed'] > 0 ? ' adm-stock--low' : '' ?>"><?= e((string) ($outbox['queued'] + $outbox['failed'])) ?></span>
    <span class="adm-kpi__label">Emails not yet sent</span>
    <p class="adm-note"><?= e((string) $outbox['queued']) ?> waiting · <?= e((string) $outbox['failed']) ?> failed</p>
  </div>
  <div class="adm-kpi">
    <span class="adm-kpi__value"><?= e((string) $regen['total']) ?></span>
    <span class="adm-kpi__label">Images on the site</span>
    <p class="adm-note"><?= $webp ? 'WebP available' : 'JPEG only on this server' ?></p>
  </div>
</div>
<?php if ($outboxFailed !== []): ?>
<div class="adm-card adm-card--danger">
  <div class="adm-card__head"><h2 class="adm-card__title">Emails that could not be sent</h2></div>
  <p class="adm-muted">The panel retries queued emails on every page you open. These have run out of retries; check the SMTP details in config.php and the mailbox password in hPanel.</p>
  <ul class="adm-list">
<?php foreach ($outboxFailed as $row): ?>
    <li><span><?= e((string) $row['subject']) ?><br><span class="adm-note">To <?= e((string) $row['recipient']) ?> · <?= e((string) $row['attempts']) ?> tries</span><br><span class="adm-note"><?= e((string) ($row['last_error'] ?? '')) ?></span></span><?php partial_admin('badge.php', ['status' => 'failed', 'small' => true]); ?></li>
<?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<div class="adm-card<?= $sampleCount > 0 ? ' adm-card--danger' : '' ?>">
  <div class="adm-card__head"><h2 class="adm-card__title">Sample data</h2><?php partial_admin('badge.php', ['status' => $sampleCount > 0 ? 'pending' : 'live', 'label' => $sampleCount > 0 ? 'Still present' : 'Removed', 'small' => true]); ?></div>
<?php if ($sampleCount > 0): ?>
  <p class="adm-muted">The shop still shows the demonstration products, collections, coupons and reviews it was installed with. Remove them before customers see the site. Products that have already been sold are kept for order history and only hidden.</p>
  <p class="adm-note"><?= e($plural(count($sample['products']), 'product', 'products')) ?> · <?= e($plural(count($sample['collections']), 'collection', 'collections')) ?> · <?= e($plural(count($sample['coupons']), 'coupon', 'coupons')) ?> · <?= e($plural($sample['reviews'], 'review', 'reviews')) ?></p>
  <div class="adm-cluster"><?php partial_admin('button.php', ['label' => 'Review and remove', 'variant' => 'danger', 'href' => '/admin/tools/remove-sample-data']); ?></div>
<?php else: ?>
  <p class="adm-muted">No sample products, collections, coupons or reviews remain.</p>
<?php if ($sample['settings'] !== []): ?>
  <p class="adm-note">Check the payment account details under <a href="<?= e(url('/admin/settings?tab=payments')) ?>">Settings › Payments</a> — the installer filled some of them with placeholders.</p>
<?php endif; ?>
<?php endif; ?>
</div>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Privacy purge</h2></div>
  <p class="adm-muted">Keeps the promise on the privacy page. Deletes payment screenshots for orders delivered or cancelled more than <?= e((string) $purge['proof_days']) ?> days ago, empties the stored copy of emails sent more than <?= e((string) $purge['body_days']) ?> days ago, and prunes old rate-limit rows, login attempts and expired sessions. A smaller version of this runs on its own in the background.</p>
  <p class="adm-note">Right now: <?= e($plural($purge['proofs'], 'proof', 'proofs')) ?> · <?= e($plural($purge['outbox_bodies'], 'email body', 'email bodies')) ?> · <?= e($plural($purge['rate_limits'] + $purge['login_attempts'], 'old attempt row', 'old attempt rows')) ?> · <?= e($plural($purge['sessions'], 'expired session', 'expired sessions')) ?></p>
  <div class="adm-cluster"><?php partial_admin('button.php', ['label' => 'Review and purge', 'variant' => 'ghost', 'href' => '/admin/tools/purge']); ?></div>
</div>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Regenerate images</h2></div>
  <p class="adm-muted">Rebuilds every thumbnail, card, zoom and share image from the largest copy on disk. Use it after moving hosts or when WebP becomes available. It works in short batches, so keep this tab open until it says it has finished.</p>
  <p class="adm-note"><?= e($plural($regen['images'], 'product photo', 'product photos')) ?> · <?= e($plural($regen['collections'], 'collection image', 'collection images')) ?> · <?= $webp ? 'JPEG + WebP will be written' : 'JPEG only — this server has no WebP support' ?></p>
  <div class="adm-cluster"><?php partial_admin('button.php', ['label' => 'Regenerate all images', 'variant' => 'ghost', 'post' => '/admin/tools/regenerate-images', 'disabled' => $regen['total'] === 0, 'confirm' => "Rebuild every image on the site?\nThe shop keeps working while it runs. It takes about a second per photo and continues automatically in this tab.", 'confirm_label' => 'Start']); ?></div>
</div>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">HTTPS redirect</h2><?php partial_admin('badge.php', ['status' => $httpsPermanent ? 'active' : 'pending', 'label' => $httpsPermanent ? 'Permanent (301)' : 'Temporary (302)', 'small' => true]); ?></div>
<?php if ($httpsPermanent): ?>
  <p class="adm-muted">Browsers remember to use https:// for this shop. Nothing to do.</p>
<?php else: ?>
  <p class="adm-muted">Once the padlock shows in your browser, make the redirect permanent so browsers stop asking. The button checks the certificate first and refuses if https:// does not answer.</p>
  <div class="adm-cluster">
<?php partial_admin('button.php', ['label' => 'Make HTTPS permanent', 'variant' => 'gold', 'post' => '/admin/settings/https-permanent', 'disabled' => $httpsLocal, 'confirm' => "Make the HTTPS redirect permanent?\nOnly do this when https:// already works with a valid padlock. It cannot be undone from the panel.", 'confirm_label' => 'Check and switch']); ?>
<?php if ($httpsLocal): ?><span class="adm-note">Open the panel over https:// on the live address first.</span><?php endif; ?>
  </div>
<?php endif; ?>
</div>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Stock-back audit</h2><?php partial_admin('badge.php', ['status' => $stock['pending_total'] > 0 ? 'pending' : 'live', 'label' => $stock['pending_total'] > 0 ? $stock['pending_total'] . ' open' : 'Clear', 'small' => true]); ?></div>
  <p class="adm-muted">Orders cancelled after they were shipped keep their stock out until the parcel actually comes back. Open each one and press <em>Parcel received back — restore stock</em> when it arrives. <?= e((string) $stock['restored_total']) ?> cancelled <?= $stock['restored_total'] === 1 ? 'order has' : 'orders have' ?> had stock restored.</p>
<?php if ($stock['pending'] === []): ?>
  <p class="adm-note">Every cancelled order has its stock back on the shelf.</p>
<?php else: ?>
<?php
$auditRows = [];
foreach ($stock['pending'] as $order) {
    ob_start();
    partial_admin('money.php', ['value' => (string) $order['grand_total'], 'strong' => true]);
    $moneyHtml = ob_get_clean();
    ob_start();
    partial_admin('badge.php', ['status' => 'cancelled', 'small' => true]);
    $chip = ob_get_clean();
    $when = e(date_long((string) $order['cancelled_at']));
    $reason = trim((string) ($order['cancel_reason'] ?? ''));
    $auditRows[] = [
        'id' => (int) $order['id'],
        'href' => '/admin/orders/' . $order['order_number'],
        'cells' => [
            'number' => '<span class="adm-mono">' . e((string) $order['order_number']) . '</span>',
            'customer' => e((string) $order['customer_name']) . ' <span class="adm-muted">' . e((string) $order['city']) . '</span>',
            'items' => e((string) $order['item_count']),
            'cancelled' => $when . ($reason !== '' ? '<br><span class="adm-note">' . e($reason) . '</span>' : ''),
            'total' => $moneyHtml,
        ],
        'card' => [
            'title' => '<span class="adm-mono">' . e((string) $order['order_number']) . '</span>',
            'chip' => $chip,
            'sub' => e((string) $order['customer_name']) . ' · ' . e((string) $order['city']),
            'lines' => [['label' => 'Cancelled', 'html' => $when], ['label' => 'Items', 'html' => e((string) $order['item_count'])]],
            'money' => $moneyHtml,
        ],
    ];
}
partial_admin('table.php', [
    'caption' => 'Cancelled orders whose stock is still out',
    'list_id' => 'stock-audit',
    'columns' => [
        ['key' => 'number', 'label' => 'Order'],
        ['key' => 'customer', 'label' => 'Customer'],
        ['key' => 'items', 'label' => 'Items', 'align' => 'right'],
        ['key' => 'cancelled', 'label' => 'Cancelled'],
        ['key' => 'total', 'label' => 'Total', 'align' => 'right', 'class' => 'adm-table__td--money'],
    ],
    'rows' => $auditRows,
]);
?>
<?php if ($stock['pending_total'] > count($stock['pending'])): ?>
  <p class="adm-note">Showing the latest <?= e((string) count($stock['pending'])) ?> of <?= e((string) $stock['pending_total']) ?>. <a href="<?= e(url('/admin/orders?status=cancelled')) ?>">See all cancelled orders</a>.</p>
<?php endif; ?>
<?php endif; ?>
</div>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Recent tool runs</h2><a class="adm-btn adm-btn--text adm-btn--sm" href="<?= e(url('/admin/activity?entity=tool')) ?>">Activity log</a></div>
<?php if ($lastRuns === []): ?>
  <p class="adm-muted">No tool has been run yet.</p>
<?php else: ?>
  <ul class="adm-list">
<?php foreach ($lastRuns as $run): ?>
    <li><span><?= e((string) $run['summary']) ?><br><span class="adm-note"><?= e((string) $run['action']) ?> · <?= e(date_long((string) $run['created_at'])) ?></span></span></li>
<?php endforeach; ?>
  </ul>
<?php endif; ?>
</div>
<p class="adm-note">Locked out with no email? Copy <span class="adm-mono">app/tools/reset-password.php</span> to the site root in File Manager and open it — the setup guide has the steps.</p>
