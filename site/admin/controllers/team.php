<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/dashboard-period.php';
require_once __DIR__ . '/catalogue.php';
require_once __DIR__ . '/order-actions.php';
require_once APP_ROOT . '/app/lib/orders.php';

function team_member_blank(): array
{
    return ['display_name' => '', 'job_title' => '', 'phone' => '', 'email' => '', 'cnic' => '', 'address' => '', 'joined_on' => '', 'notes' => '', 'username' => '', 'password' => '', 'role' => 'staff', 'permissions' => ['orders', 'customers'], 'is_seller' => '0', 'commission_rate' => '', 'is_active' => '1'];
}

function team_member_from_row(array $row): array
{
    return [
        'display_name' => (string) $row['display_name'],
        'job_title' => (string) ($row['job_title'] ?? ''),
        'phone' => (string) ($row['phone'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'cnic' => (string) ($row['cnic'] ?? ''),
        'address' => (string) ($row['address'] ?? ''),
        'joined_on' => (string) ($row['joined_on'] ?? ''),
        'notes' => (string) ($row['notes'] ?? ''),
        'username' => (string) $row['username'],
        'password' => '',
        'role' => (string) $row['role'],
        'permissions' => team_permissions_decode($row['permissions'] ?? null),
        'is_seller' => $row['commission_rate'] !== null ? '1' : '0',
        'commission_rate' => $row['commission_rate'] !== null ? rtrim(rtrim((string) $row['commission_rate'], '0'), '.') : '',
        'is_active' => (string) (int) $row['is_active'],
    ];
}

function team_member_input(): array
{
    $form = [];
    foreach (team_member_blank() as $key => $default) {
        if ($key === 'permissions') {
            $raw = $_POST['permissions'] ?? [];
            $form[$key] = is_array($raw) ? array_values(array_intersect(array_keys(TEAM_MODULES), array_map('strval', $raw))) : [];
            continue;
        }
        $value = request_post($key, '');
        $form[$key] = is_string($value) ? ($key === 'password' ? $value : trim($value)) : '';
    }
    $form['role'] = $form['role'] === 'owner' ? 'owner' : 'staff';
    $form['is_seller'] = $form['is_seller'] === '1' ? '1' : '0';
    $form['is_active'] = $form['is_active'] === '1' ? '1' : '0';
    return $form;
}

function team_member_validate(array $form, ?array $existing, array $admin): array
{
    $errors = [];
    $id = $existing !== null ? (int) $existing['id'] : 0;
    if (mb_strlen($form['display_name']) < 2 || mb_strlen($form['display_name']) > 100) {
        $errors['display_name'] = 'Enter the full name.';
    }
    if (preg_match('/^[A-Za-z0-9._-]{3,64}$/', $form['username']) !== 1) {
        $errors['username'] = 'Use 3 to 64 letters, numbers, dots, dashes or underscores, with no spaces.';
    } elseif (db_exists('SELECT 1 FROM admin_users WHERE username = :u AND id <> :id', ['u' => $form['username'], 'id' => $id])) {
        $errors['username'] = 'That username is already taken.';
    }
    if ($form['email'] !== '') {
        if (mb_strlen($form['email']) > 190 || filter_var($form['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'That email address does not look right.';
        } elseif (db_exists('SELECT 1 FROM admin_users WHERE email = :e AND id <> :id', ['e' => $form['email'], 'id' => $id])) {
            $errors['email'] = 'Another team member already uses this email.';
        }
    }
    if ($form['phone'] !== '' && preg_match('/^\+?[0-9 ()-]{7,20}$/', $form['phone']) !== 1) {
        $errors['phone'] = 'Enter a phone number such as 0300 1234567.';
    }
    if ($form['cnic'] !== '' && preg_match('/^[0-9]{5}-?[0-9]{7}-?[0-9]$/', $form['cnic']) !== 1) {
        $errors['cnic'] = 'Enter the CNIC as 13 digits, for example 35202-1234567-1.';
    }
    if ($form['joined_on'] !== '') {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $form['joined_on']);
        if ($date === false || $date->format('Y-m-d') !== $form['joined_on']) {
            $errors['joined_on'] = 'Enter a valid date.';
        }
    }
    foreach (['job_title' => 80, 'address' => 255, 'notes' => 1000] as $key => $max) {
        if (mb_strlen($form[$key]) > $max) {
            $errors[$key] = 'Keep this under ' . $max . ' characters.';
        }
    }
    if ($existing === null || $form['password'] !== '') {
        $problem = $form['password'] === '' ? 'Set a password for this person to sign in with.' : auth_password_problem($form['password'], '', $form['username']);
        if ($problem !== null) {
            $errors['password'] = $problem;
        }
    }
    if ($form['is_seller'] === '1') {
        if (preg_match('/^[0-9]{1,3}(\.[0-9]{1,2})?$/', $form['commission_rate']) !== 1 || (float) $form['commission_rate'] <= 0 || (float) $form['commission_rate'] > 100) {
            $errors['commission_rate'] = 'Enter the share of profit as a percent from 0.01 to 100, for example 30.';
        }
    }
    if ($form['role'] === 'staff' && $form['permissions'] === [] && $form['is_seller'] !== '1') {
        $errors['permissions'] = 'Tick at least one section, or give this person a sales share.';
    }
    $isSelf = $id > 0 && $id === (int) $admin['id'];
    if ($isSelf && $form['role'] !== 'owner') {
        $errors['role'] = 'You cannot remove your own super admin access.';
    }
    if ($isSelf && $form['is_active'] !== '1') {
        $errors['is_active'] = 'You cannot switch off your own account.';
    }
    if ($existing !== null && (string) $existing['role'] === 'owner' && ($form['role'] !== 'owner' || $form['is_active'] !== '1')) {
        $owners = (int) db_fetch_column("SELECT COUNT(*) FROM admin_users WHERE role = 'owner' AND is_active = 1 AND id <> :id", ['id' => $id]);
        if ($owners === 0) {
            $errors['role'] = 'There must always be at least one active super admin.';
        }
    }
    return $errors;
}

function team_member_columns(array $form, string $now): array
{
    $cnicDigits = preg_replace('/\D+/', '', $form['cnic']) ?? '';
    $columns = [
        'display_name' => $form['display_name'],
        'username' => $form['username'],
        'email' => $form['email'] === '' ? null : mb_strtolower($form['email']),
        'role' => $form['role'],
        'permissions' => $form['role'] === 'owner' ? null : json_encode($form['permissions']),
        'commission_rate' => $form['is_seller'] === '1' ? number_format((float) $form['commission_rate'], 2, '.', '') : null,
        'job_title' => $form['job_title'] === '' ? null : $form['job_title'],
        'phone' => $form['phone'] === '' ? null : $form['phone'],
        'cnic' => $cnicDigits === '' ? null : substr($cnicDigits, 0, 5) . '-' . substr($cnicDigits, 5, 7) . '-' . substr($cnicDigits, 12, 1),
        'address' => $form['address'] === '' ? null : $form['address'],
        'joined_on' => $form['joined_on'] === '' ? null : $form['joined_on'],
        'notes' => $form['notes'] === '' ? null : $form['notes'],
        'is_active' => (int) $form['is_active'],
        'updated_at' => $now,
    ];
    if ($form['password'] !== '') {
        $columns['password_hash'] = password_hash($form['password'], PASSWORD_BCRYPT, ['cost' => AUTH_BCRYPT_COST]);
        $columns['password_changed_at'] = $now;
    }
    return $columns;
}

function team_log_view(array $columns): array
{
    unset($columns['password_hash'], $columns['password_changed_at'], $columns['updated_at'], $columns['known_devices']);
    return $columns;
}

function team_find(int $id): array
{
    $row = db_fetch('SELECT * FROM admin_users WHERE id = :id', ['id' => $id]);
    if ($row === null) {
        flash('error', 'That team member no longer exists.');
        redirect('/admin/team', 303);
    }
    return $row;
}

function team_return_url(string $fallback): string
{
    $return = (string) request_post('return', request_query('return', ''));
    return preg_match('#^/admin/(team|sales|orders)(/[A-Za-z0-9/_-]*)?(\?[A-Za-z0-9=&%_.+-]*)?$#', $return) === 1 ? $return : $fallback;
}

function team_paid_on(string $value, DateTimeImmutable $now): ?string
{
    $value = $value === '' ? $now->format('Y-m-d') : $value;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $now->getTimezone());
    if ($date === false || $date->format('Y-m-d') !== $value || $value < '2020-01-01' || $date > $now) {
        return null;
    }
    return $value . ' ' . $now->format('H:i:s');
}

function team_mark_paid(array $row, string $paidAt, array $admin): bool
{
    $commission = team_commission($row);
    if (!in_array($commission['state'], ['ready', 'waiting'], true) || $commission['share'] <= 0) {
        return false;
    }
    $updated = db_query(
        'UPDATE orders SET commission_paid_at = :at, commission_paid_amount = :amount, commission_paid_by = :by WHERE id = :id AND commission_paid_at IS NULL',
        ['at' => $paidAt, 'amount' => money_from_paisa($commission['share']), 'by' => mb_substr((string) $admin['username'], 0, 64), 'id' => (int) $row['id']]
    )->rowCount() === 1;
    if ($updated) {
        catalogue_log('order', (int) $row['id'], 'commission.paid', 'Share paid to ' . $row['member_name'] . ' for ' . $row['order_number'] . ': ' . money(money_from_paisa($commission['share'])) . ' on ' . substr($paidAt, 0, 10));
    }
    return $updated;
}

$routeName = (string) ($route['name'] ?? 'admin.team');
$admin = auth_user();
$karachi = new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi'));
$now = now_karachi();

if ($routeName === 'admin.team.assign') {
    $order = db_fetch('SELECT id, order_number, sales_member_id, commission_paid_at FROM orders WHERE order_number = :n', ['n' => order_number_normalize((string) $params['order'])]);
    if ($order === null) {
        flash('error', 'That order no longer exists.');
        redirect('/admin/orders', 303);
    }
    $back = '/admin/orders/' . $order['order_number'] . '/view';
    if ($order['commission_paid_at'] !== null) {
        flash('error', 'The share on this order is already paid. Mark it as not paid before changing the salesperson.');
        redirect($back, 303);
    }
    $memberId = (int) request_post('member_id', 0);
    $member = $memberId > 0 ? db_fetch('SELECT id, display_name, commission_rate FROM admin_users WHERE id = :id AND commission_rate IS NOT NULL', ['id' => $memberId]) : null;
    if ($memberId > 0 && $member === null) {
        flash('error', 'Choose a team member who has a sales share.');
        redirect($back, 303);
    }
    team_assign_order((int) $order['id'], $member === null ? null : (int) $member['id'], $now);
    catalogue_log('order', (int) $order['id'], 'commission.assign', $member === null ? 'Salesperson removed from ' . $order['order_number'] : $order['order_number'] . ' credited to ' . $member['display_name'] . ' at ' . (float) $member['commission_rate'] . '%');
    flash('success', $member === null ? 'No salesperson on ' . $order['order_number'] . ' now.' : $order['order_number'] . ' is now credited to ' . $member['display_name'] . '.');
    redirect($back, 303);
}

if ($routeName === 'admin.team.share') {
    $orderNumber = order_number_normalize((string) $params['order']);
    $orderId = (int) db_fetch_column('SELECT id FROM orders WHERE order_number = :n', ['n' => $orderNumber]);
    $row = $orderId > 0 ? team_commission_for_order($orderId) : null;
    if ($row === null) {
        flash('error', 'That order has no salesperson.');
        redirect('/admin/team', 303);
    }
    $back = team_return_url('/admin/team/' . (int) $row['sales_member_id'] . '/sales');
    $errors = [];
    $form = [
        'rate' => $row['commission_rate'] !== null ? rtrim(rtrim((string) $row['commission_rate'], '0'), '.') : '',
        'override' => $row['commission_override'] !== null ? catalogue_money_field((string) $row['commission_override']) : '',
        'note' => (string) ($row['commission_note'] ?? ''),
        'paid_on' => $karachi->format('Y-m-d'),
    ];
    if (request_method() === 'POST') {
        $action = (string) request_post('action', '');
        if ($action === 'paid') {
            $paidAt = team_paid_on(trim((string) request_post('paid_on', '')), $karachi);
            if ($paidAt === null) {
                flash('error', 'Enter the date the share was paid. It cannot be in the future.');
            } elseif (team_mark_paid($row, $paidAt, $admin)) {
                flash('success', 'Marked as paid: ' . money(money_from_paisa($row['commission']['share'])) . ' to ' . $row['member_name'] . ' for ' . $row['order_number'] . '.');
            } else {
                flash('error', $row['commission']['state'] === 'paid' ? 'This share is already marked as paid.' : 'There is no share to pay on this order.');
            }
            redirect($back, 303);
        }
        if ($action === 'unpaid') {
            if ($row['commission_paid_at'] !== null) {
                db_query('UPDATE orders SET commission_paid_at = NULL, commission_paid_amount = NULL, commission_paid_by = NULL WHERE id = :id', ['id' => (int) $row['id']]);
                catalogue_log('order', (int) $row['id'], 'commission.unpaid', 'Share for ' . $row['order_number'] . ' marked as not paid (was ' . money((string) $row['commission_paid_amount']) . ')');
                flash('success', 'The share on ' . $row['order_number'] . ' is marked as not paid.');
            }
            redirect($back, 303);
        }
        if ($action === 'adjust') {
            $form['rate'] = trim((string) request_post('rate', ''));
            $form['override'] = trim((string) request_post('override', ''));
            $form['note'] = trim((string) request_post('note', ''));
            if ($row['commission_paid_at'] !== null) {
                $errors['rate'] = 'This share is already paid. Mark it as not paid before changing it.';
            }
            if (preg_match('/^[0-9]{1,3}(\.[0-9]{1,2})?$/', $form['rate']) !== 1 || (float) $form['rate'] > 100) {
                $errors['rate'] = 'Enter a percent from 0 to 100.';
            }
            $override = $form['override'] === '' ? '' : catalogue_money_input($form['override']);
            if ($override === null) {
                $errors['override'] = 'Enter an amount in rupees, or leave it empty to use the percent.';
            }
            if (mb_strlen($form['note']) > 255) {
                $errors['note'] = 'Keep the note under 255 characters.';
            }
            if ($errors === []) {
                $before = ['commission_rate' => $row['commission_rate'], 'commission_override' => $row['commission_override'], 'commission_note' => $row['commission_note']];
                $after = ['commission_rate' => number_format((float) $form['rate'], 2, '.', ''), 'commission_override' => $override === '' ? null : money_from_paisa(money_paisa((string) $override)), 'commission_note' => $form['note'] === '' ? null : $form['note']];
                db_update('orders', $after, ['id' => (int) $row['id']]);
                catalogue_log('order', (int) $row['id'], 'commission.adjust', 'Share adjusted on ' . $row['order_number'], $before, $after);
                flash('success', 'Share updated for ' . $row['order_number'] . '.');
                redirect('/admin/team/share/' . $row['order_number'] . '?return=' . rawurlencode($back), 303);
            }
        }
    }
    render_admin('team-share.php', ['row' => $row, 'form' => $form, 'errors' => $errors, 'back' => $back], ['title' => 'Share on ' . $row['order_number'], 'body_class' => (string) $route['body_class'], 'back' => $back]);
}

if ($routeName === 'admin.team.pay_ready') {
    $member = team_find((int) $params['id']);
    $back = team_return_url('/admin/team/' . (int) $member['id'] . '/sales');
    $paidAt = team_paid_on(trim((string) request_post('paid_on', '')), $karachi);
    if ($paidAt === null) {
        flash('error', 'Enter the date the shares were paid. It cannot be in the future.');
        redirect($back, 303);
    }
    $paid = 0;
    $total = 0;
    db_transaction(static function () use ($member, $paidAt, $admin, &$paid, &$total): void {
        foreach (team_commission_rows('o.sales_member_id = :m AND o.commission_paid_at IS NULL', ['m' => (int) $member['id']]) as $row) {
            if ($row['commission']['state'] === 'ready' && team_mark_paid($row, $paidAt, $admin)) {
                $paid++;
                $total += $row['commission']['share'];
            }
        }
    });
    flash($paid > 0 ? 'success' : 'info', $paid > 0 ? 'Marked ' . $paid . ' ' . ($paid === 1 ? 'share' : 'shares') . ' as paid to ' . $member['display_name'] . ': ' . money(money_from_paisa($total)) . '.' : 'Nothing is ready to pay for ' . $member['display_name'] . '.');
    redirect($back, 303);
}

if ($routeName === 'admin.sales' || $routeName === 'admin.team.sales') {
    $member = $routeName === 'admin.sales' ? team_find((int) $admin['id']) : team_find((int) $params['id']);
    if ($routeName === 'admin.sales' && team_is_owner($admin)) {
        redirect('/admin/team', 303);
    }
    $base = $routeName === 'admin.sales' ? '/admin/sales' : '/admin/team/' . (int) $member['id'] . '/sales';
    $firstOrder = db_fetch_column('SELECT MIN(created_at) FROM orders WHERE sales_member_id = :m', ['m' => (int) $member['id']]);
    $firstDay = dashboard_first_day($karachi->setTime(0, 0));
    if (is_string($firstOrder) && $firstOrder < $firstDay->format('Y-m-d H:i:s')) {
        $firstDay = (new DateTimeImmutable($firstOrder, $karachi->getTimezone()))->setTime(0, 0);
    }
    $period = dashboard_period($_GET, $karachi, $firstDay);
    $show = (string) request_query('show', '');
    $show = in_array($show, ['ready', 'waiting', 'paid'], true) ? $show : '';
    $rows = team_commission_rows('o.sales_member_id = :m AND o.created_at >= :from AND o.created_at < :to', ['m' => (int) $member['id'], 'from' => $period['from']->format('Y-m-d H:i:s'), 'to' => $period['to']->format('Y-m-d H:i:s')]);
    $totals = ['orders' => 0, 'net' => 0, 'profit' => 0, 'share' => 0, 'paid' => 0, 'ready' => 0, 'waiting' => 0];
    foreach ($rows as $row) {
        $c = $row['commission'];
        if ($c['state'] === 'void') {
            continue;
        }
        $totals['orders']++;
        $totals['net'] += $c['net'];
        $totals['profit'] += $c['profit'];
        $totals['share'] += $c['share'];
        $totals[$c['state']] += $c['share'];
    }
    $owed = ['ready' => 0, 'ready_count' => 0, 'waiting' => 0];
    foreach (team_commission_rows('o.sales_member_id = :m AND o.commission_paid_at IS NULL', ['m' => (int) $member['id']]) as $row) {
        if ($row['commission']['state'] === 'ready') {
            $owed['ready'] += $row['commission']['share'];
            $owed['ready_count']++;
        } elseif ($row['commission']['state'] === 'waiting') {
            $owed['waiting'] += $row['commission']['share'];
        }
    }
    if ($show !== '') {
        $rows = array_values(array_filter($rows, static fn (array $row): bool => $row['commission']['state'] === $show));
    }
    render_admin('team-sales.php', [
        'member' => $member,
        'isOwnerView' => $routeName === 'admin.team.sales',
        'rows' => $rows,
        'totals' => $totals,
        'owed' => $owed,
        'show' => $show,
        'period' => $period,
        'picker' => dashboard_picker($period, $karachi, $base),
        'base' => $base,
        'today' => $karachi->format('Y-m-d'),
        'returnTo' => request_path() . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''),
    ], ['title' => $routeName === 'admin.sales' ? 'My sales' : $member['display_name'] . ' · Sales', 'body_class' => (string) $route['body_class'], 'back' => $routeName === 'admin.sales' ? null : '/admin/team']);
}

if ($routeName === 'admin.team.new' || $routeName === 'admin.team.edit') {
    $existing = $routeName === 'admin.team.edit' ? team_find((int) $params['id']) : null;
    $form = $existing !== null ? team_member_from_row($existing) : team_member_blank();
    $errors = [];
    if (request_method() === 'POST') {
        $form = team_member_input();
        $errors = team_member_validate($form, $existing, $admin);
        if ($errors === []) {
            $columns = team_member_columns($form, $now);
            if ($existing === null) {
                $id = db_insert('admin_users', $columns + ['created_at' => $now]);
                catalogue_log('team', $id, 'team.create', 'Added ' . $columns['display_name'] . ' (' . $columns['username'] . ')', null, team_log_view($columns));
                flash('success', $columns['display_name'] . ' can now sign in at ' . SITE_URL . '/admin with the username ' . $columns['username'] . ' and the password you set.');
            } else {
                db_update('admin_users', $columns, ['id' => (int) $existing['id']]);
                catalogue_log('team', (int) $existing['id'], 'team.update', 'Updated ' . $columns['display_name'] . (isset($columns['password_hash']) ? ' (new password set)' : ''), team_log_view($existing), team_log_view($columns));
                flash('success', 'Saved ' . $columns['display_name'] . '.' . ((int) $existing['is_active'] === 1 && $columns['is_active'] === 0 ? ' They can no longer sign in.' : ''));
            }
            redirect('/admin/team', 303);
        }
    }
    render_admin('team-form.php', [
        'existing' => $existing,
        'form' => $form,
        'errors' => $errors,
        'isSelf' => $existing !== null && (int) $existing['id'] === (int) $admin['id'],
    ], ['title' => $existing !== null ? 'Edit ' . $existing['display_name'] : 'Add team member', 'body_class' => (string) $route['body_class'], 'back' => '/admin/team']);
}

$members = db_fetch_all('SELECT id, username, email, display_name, role, permissions, commission_rate, job_title, phone, is_active, last_login_at FROM admin_users ORDER BY is_active DESC, role = \'owner\' DESC, display_name ASC');
$owedBy = [];
foreach (team_commission_rows('o.commission_paid_at IS NULL', []) as $row) {
    $memberId = (int) $row['sales_member_id'];
    $owedBy[$memberId] ??= ['ready' => 0, 'waiting' => 0];
    if (in_array($row['commission']['state'], ['ready', 'waiting'], true)) {
        $owedBy[$memberId][$row['commission']['state']] += $row['commission']['share'];
    }
}
$monthStart = $karachi->format('Y-m-01 00:00:00');
$paidMonth = [];
foreach (db_fetch_all('SELECT sales_member_id, COALESCE(SUM(commission_paid_amount), 0) AS paid FROM orders WHERE commission_paid_at >= :m GROUP BY sales_member_id', ['m' => $monthStart]) as $row) {
    $paidMonth[(int) $row['sales_member_id']] = money_paisa((string) $row['paid']);
}
render_admin('team.php', [
    'members' => $members,
    'owedBy' => $owedBy,
    'paidMonth' => $paidMonth,
    'monthLabel' => $karachi->format('F'),
    'selfId' => (int) $admin['id'],
], ['title' => 'Team', 'body_class' => (string) $route['body_class']]);
