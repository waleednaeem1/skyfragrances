<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/finance.php';

function expenses_blank(string $type): array
{
    $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi')))->format('Y-m-d');
    return ['category' => $type === 'income' ? 'other_income' : '', 'title' => '', 'amount' => '', 'entry_date' => $today, 'status' => 'paid', 'due_date' => '', 'paid_on' => $today, 'paid_from' => 'cash', 'vendor' => '', 'reference' => '', 'note' => ''];
}

function expenses_from_row(array $row): array
{
    return [
        'category' => (string) $row['category'],
        'title' => (string) $row['title'],
        'amount' => catalogue_money_field((string) $row['amount']),
        'entry_date' => (string) $row['entry_date'],
        'status' => (string) $row['status'],
        'due_date' => (string) ($row['due_date'] ?? ''),
        'paid_on' => (string) ($row['paid_on'] ?? ''),
        'paid_from' => (string) ($row['paid_from'] ?? ''),
        'vendor' => (string) ($row['vendor'] ?? ''),
        'reference' => (string) ($row['reference'] ?? ''),
        'note' => (string) ($row['note'] ?? ''),
    ];
}

function expenses_input(): array
{
    $form = [];
    foreach (array_keys(expenses_blank('expense')) as $key) {
        $value = request_post($key, '');
        $form[$key] = is_string($value) ? trim($value) : '';
    }
    return $form;
}

function expenses_date(string $value): ?string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value && $value >= '2020-01-01' && $value <= '2100-12-31' ? $value : null;
}

function expenses_validate(array $form): array
{
    $errors = [];
    if (!isset(FINANCE_EXPENSE_CATEGORIES[$form['category']]) && !isset(FINANCE_INCOME_CATEGORIES[$form['category']])) {
        $errors['category'] = 'Choose what this is for.';
    }
    if (mb_strlen($form['title']) < 2 || mb_strlen($form['title']) > 160) {
        $errors['title'] = 'Describe it in a few words, for example "Instagram ads for Eid".';
    }
    $amount = catalogue_money_input($form['amount']);
    if ($amount === null || $amount === '' || money_paisa($amount) <= 0) {
        $errors['amount'] = 'Enter the amount in rupees.';
    }
    if (expenses_date($form['entry_date']) === null) {
        $errors['entry_date'] = 'Enter the date.';
    }
    if (!in_array($form['status'], ['paid', 'due'], true)) {
        $errors['status'] = 'Choose paid or still to pay.';
    }
    if ($form['due_date'] !== '' && expenses_date($form['due_date']) === null) {
        $errors['due_date'] = 'Enter a valid due date or leave it empty.';
    }
    if ($form['status'] === 'paid' && $form['paid_on'] !== '' && expenses_date($form['paid_on']) === null) {
        $errors['paid_on'] = 'Enter a valid date.';
    }
    if ($form['paid_from'] !== '' && !isset(FINANCE_PAID_FROM[$form['paid_from']])) {
        $errors['paid_from'] = 'Choose how it was paid.';
    }
    foreach (['vendor' => 120, 'reference' => 80, 'note' => 500] as $key => $max) {
        if (mb_strlen($form[$key]) > $max) {
            $errors[$key] = 'Keep this under ' . $max . ' characters.';
        }
    }
    return $errors;
}

function expenses_columns(array $form): array
{
    $type = isset(FINANCE_INCOME_CATEGORIES[$form['category']]) ? 'income' : 'expense';
    $paid = $form['status'] === 'paid';
    return [
        'entry_type' => $type,
        'category' => $form['category'],
        'title' => $form['title'],
        'amount' => (string) catalogue_money_input($form['amount']),
        'entry_date' => $form['entry_date'],
        'status' => $form['status'],
        'due_date' => $paid || $form['due_date'] === '' ? null : $form['due_date'],
        'paid_on' => $paid ? ($form['paid_on'] !== '' ? $form['paid_on'] : $form['entry_date']) : null,
        'paid_from' => $form['paid_from'] === '' ? null : $form['paid_from'],
        'vendor' => $form['vendor'] === '' ? null : $form['vendor'],
        'reference' => $form['reference'] === '' ? null : $form['reference'],
        'note' => $form['note'] === '' ? null : $form['note'],
    ];
}

function expenses_find(int $id): array
{
    $row = db_fetch('SELECT * FROM expenses WHERE id = :id AND deleted_at IS NULL', ['id' => $id]);
    if ($row === null) {
        flash('error', 'That entry no longer exists.');
        redirect('/admin/expenses', 303);
    }
    return $row;
}

function expenses_summary(array $row): string
{
    return finance_category_label((string) $row['entry_type'], (string) $row['category']) . ' — ' . $row['title'] . ', ' . money((string) $row['amount']);
}

$routeName = (string) ($route['name'] ?? 'admin.expenses');
$admin = auth_user();
$now = now_karachi();

if (in_array($routeName, ['admin.expenses.paid', 'admin.expenses.delete'], true)) {
    $row = expenses_find((int) $params['id']);
    if ($routeName === 'admin.expenses.paid') {
        if ($row['status'] !== 'due') {
            flash('info', 'That bill is already paid.');
        } else {
            $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi')))->format('Y-m-d');
            db_update('expenses', ['status' => 'paid', 'paid_on' => $today, 'updated_at' => $now], ['id' => (int) $row['id']]);
            catalogue_log('expense', (int) $row['id'], 'expense.paid', 'Marked paid: ' . expenses_summary($row));
            flash('success', 'Marked as paid today: ' . $row['title'] . '.');
        }
    } else {
        db_update('expenses', ['deleted_at' => $now, 'updated_at' => $now], ['id' => (int) $row['id']]);
        catalogue_log('expense', (int) $row['id'], 'expense.delete', 'Removed: ' . expenses_summary($row));
        flash('success', 'Removed: ' . $row['title'] . '. It no longer counts in statements.');
    }
    $return = (string) request_post('return', '');
    redirect(preg_match('#^/admin/expenses(\?[A-Za-z0-9=&%_.+-]*)?$#', $return) === 1 ? $return : '/admin/expenses', 303);
}

if (in_array($routeName, ['admin.expenses.new', 'admin.expenses.edit'], true)) {
    $existing = $routeName === 'admin.expenses.edit' ? expenses_find((int) $params['id']) : null;
    $type = $existing !== null ? (string) $existing['entry_type'] : ((string) request_query('type', 'expense') === 'income' ? 'income' : 'expense');
    $form = $existing !== null ? expenses_from_row($existing) : expenses_blank($type);
    $errors = [];
    if (request_method() === 'POST') {
        $form = expenses_input();
        $errors = expenses_validate($form);
        if ($errors === []) {
            $columns = expenses_columns($form);
            if ($existing === null) {
                $id = db_insert('expenses', $columns + ['created_by' => isset($admin['username']) ? mb_substr((string) $admin['username'], 0, 64) : null, 'created_at' => $now]);
                catalogue_log('expense', $id, 'expense.create', 'Added: ' . expenses_summary($columns));
                flash('success', ($columns['entry_type'] === 'income' ? 'Money in recorded: ' : 'Expense recorded: ') . $columns['title'] . ', ' . money($columns['amount']) . '.');
            } else {
                db_update('expenses', $columns + ['updated_at' => $now], ['id' => (int) $existing['id']]);
                catalogue_log('expense', (int) $existing['id'], 'expense.update', 'Updated: ' . expenses_summary($columns), expenses_from_row($existing), $form);
                flash('success', 'Saved: ' . $columns['title'] . '.');
            }
            redirect(request_post('action', '') === 'save_new' ? '/admin/expenses/new?type=' . $columns['entry_type'] : '/admin/expenses', 303);
        }
        $type = isset(FINANCE_INCOME_CATEGORIES[$form['category']]) ? 'income' : 'expense';
    }
    render_admin('expense-form.php', [
        'existing' => $existing,
        'type' => $type,
        'form' => $form,
        'errors' => $errors,
    ], ['title' => $existing !== null ? 'Edit entry' : ($type === 'income' ? 'Add money in' : 'Add expense'), 'body_class' => (string) $route['body_class'], 'back' => '/admin/expenses']);
}

$karachi = new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi'));
$firstEntry = db_fetch_column('SELECT MIN(entry_date) FROM expenses WHERE deleted_at IS NULL');
$firstDay = dashboard_first_day($karachi->setTime(0, 0));
if (is_string($firstEntry) && $firstEntry < $firstDay->format('Y-m-d')) {
    $firstDay = new DateTimeImmutable($firstEntry, $karachi->getTimezone());
}
$period = dashboard_period($_GET, $karachi, $firstDay);
$dates = finance_dates($period['from'], $period['to']);
$filter = (string) request_query('show', '');
$filter = in_array($filter, ['expense', 'income', 'due'], true) ? $filter : '';
$where = 'deleted_at IS NULL AND entry_date >= :from AND entry_date < :to';
if ($filter === 'due') {
    $where .= " AND status = 'due'";
} elseif ($filter !== '') {
    $where .= ' AND entry_type = :type';
    $dates['type'] = $filter;
}
$entries = db_fetch_all('SELECT * FROM expenses WHERE ' . $where . ' ORDER BY entry_date DESC, id DESC', $dates);
$bills = db_fetch_all("SELECT * FROM expenses WHERE deleted_at IS NULL AND entry_type = 'expense' AND status = 'due' ORDER BY COALESCE(due_date, entry_date) ASC, id ASC LIMIT 20");
$totals = finance_expenses($period['from'], $period['to']);

render_admin('expenses.php', [
    'picker' => dashboard_picker($period, $karachi, '/admin/expenses'),
    'period' => $period,
    'entries' => $entries,
    'bills' => $bills,
    'totals' => $totals,
    'filter' => $filter,
    'today' => $karachi->format('Y-m-d'),
    'returnTo' => request_path() . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''),
], ['title' => 'Expenses', 'body_class' => (string) $route['body_class']]);
