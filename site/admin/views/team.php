<?php
defined('SKYFR') || exit;
$badge = static function (array $b): string {
    ob_start();
    partial_admin('badge.php', $b);
    return (string) ob_get_clean();
};
$tableRows = [];
foreach ($members as $m) {
    $id = (int) $m['id'];
    $isOwner = (string) $m['role'] === 'owner';
    $isSeller = $m['commission_rate'] !== null;
    $modules = team_permissions_decode($m['permissions'] ?? null);
    $access = $isOwner ? 'Everything' : ($modules === [] ? 'My sales only' : implode(', ', array_map(static fn (string $k): string => TEAM_MODULES[$k][0], $modules)));
    $owed = $owedBy[$id] ?? ['ready' => 0, 'waiting' => 0];
    $share = $isSeller ? '<strong>' . e(rtrim(rtrim((string) $m['commission_rate'], '0'), '.')) . '%</strong><span class="adm-media__meta">of profit</span>' : '<span class="adm-muted">None</span>';
    $toPay = $isSeller ? '<strong class="adm-money">' . e(money(money_from_paisa($owed['ready']))) . '</strong><span class="adm-media__meta">' . e(money(money_from_paisa($owed['waiting']))) . ' waiting · ' . e(money(money_from_paisa($paidMonth[$id] ?? 0))) . ' paid in ' . e($monthLabel) . '</span>' : '<span class="adm-muted">—</span>';
    $status = (int) $m['is_active'] === 1 ? ['label' => $isOwner ? 'Super admin' : 'Active', 'tone' => $isOwner ? 'champagne' : 'green'] : ['label' => 'Switched off', 'tone' => 'muted'];
    $actions = [['label' => 'Edit', 'href' => '/admin/team/' . $id]];
    if ($isSeller) {
        $actions[] = ['label' => 'Sales & shares', 'href' => '/admin/team/' . $id . '/sales'];
    }
    $tableRows[] = [
        'id' => $id,
        'href' => '/admin/team/' . $id,
        'cells' => [
            'name' => '<strong>' . e((string) $m['display_name']) . '</strong>' . ($id === $selfId ? ' <span class="adm-muted">(you)</span>' : '') . '<span class="adm-media__meta">' . e(trim((string) ($m['job_title'] ?? '') . ' · @' . $m['username'], ' ·')) . '</span>',
            'access' => '<span class="adm-note">' . e($access) . '</span>',
            'share' => $share,
            'owed' => $toPay,
            'login' => $m['last_login_at'] !== null ? e(date_short((string) $m['last_login_at'])) : '<span class="adm-muted">Never</span>',
            'status' => $badge($status),
        ],
        'card' => [
            'title' => e((string) $m['display_name']),
            'chip' => $badge($status + ['small' => true]),
            'sub' => e(trim((string) ($m['job_title'] ?? '') . ' · @' . $m['username'], ' ·')),
            'lines' => array_values(array_filter([
                ['label' => 'Can open', 'html' => e($access)],
                $isSeller ? ['label' => 'Share', 'html' => $share] : null,
                $isSeller ? ['label' => 'Ready to pay', 'html' => e(money(money_from_paisa($owed['ready'])))] : null,
            ])),
            'inline_actions' => 1,
        ],
        'actions' => $actions,
    ];
}
$sellers = count(array_filter($members, static fn (array $m): bool => $m['commission_rate'] !== null && (int) $m['is_active'] === 1));
$readyTotal = array_sum(array_map(static fn (array $o): int => $o['ready'], $owedBy));
?>
<?php partial_admin('page-header.php', [
    'title' => 'Team',
    'subtitle' => 'Everyone who can sign in to this admin, what they can open, and the profit share owed to salespeople.',
    'actions' => [['label' => 'Add team member', 'href' => '/admin/team/new', 'variant' => 'gold']],
]); ?>
<div class="adm-grid">
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Team members</span><span class="adm-kpi__value"><?= e((string) count(array_filter($members, static fn (array $m): bool => (int) $m['is_active'] === 1))) ?></span><p class="adm-delta adm-delta--flat"><span>Active accounts</span></p></div>
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Salespeople</span><span class="adm-kpi__value"><?= e((string) $sellers) ?></span><p class="adm-delta adm-delta--flat"><span>Earn a share of profit</span></p></div>
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Ready to pay</span><span class="adm-kpi__value"><?= e(money(money_from_paisa($readyTotal))) ?></span><p class="adm-delta adm-delta--flat"><span>Delivered and fully paid orders</span></p></div>
  <div class="adm-kpi adm-kpi--stat"><span class="adm-kpi__label">Paid in <?= e($monthLabel) ?></span><span class="adm-kpi__value"><?= e(money(money_from_paisa(array_sum($paidMonth)))) ?></span><p class="adm-delta adm-delta--flat"><span>Shares paid to salespeople</span></p></div>
</div>
<section class="adm-card adm-card--flush">
<?php partial_admin('table.php', [
    'caption' => 'Team members',
    'list_id' => 'team-list',
    'columns' => [
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'access', 'label' => 'Can open'],
        ['key' => 'share', 'label' => 'Share'],
        ['key' => 'owed', 'label' => 'Ready to pay', 'align' => 'right'],
        ['key' => 'login', 'label' => 'Last sign-in'],
        ['key' => 'status', 'label' => 'Status'],
    ],
    'rows' => $tableRows,
    'empty' => ['title' => 'No team members yet', 'text' => 'Add the people who help run the shop.', 'action' => ['label' => 'Add team member', 'href' => '/admin/team/new']],
]); ?>
</section>
