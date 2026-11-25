<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/finance.php';
require_once APP_ROOT . '/app/lib/xlsx.php';

function statements_series(array $buckets, string $field): array
{
    $series = [];
    foreach ($buckets as $key => $bucket) {
        $series[$key] = ['orders' => $bucket['orders'], 'amount' => $bucket[$field]];
    }
    return $series;
}

function statements_rows(array $statement, array $bars): array
{
    $rows = [];
    foreach ($bars as $bar) {
        if ($bar['future']) {
            continue;
        }
        $bucket = $statement['buckets'][$bar['key']] ?? ['orders' => 0, 'net' => 0, 'cogs' => 0, 'profit' => 0, 'expenses' => 0, 'net_profit' => 0];
        $rows[] = ['label' => $bar['tip'], 'href' => $bar['href']] + $bucket;
    }
    return $rows;
}

function statements_money_cell(int $paisa, int $style = XLSX_STYLE_MONEY): array
{
    return ['v' => $paisa / 100, 's' => $style];
}

function statements_workbook(array $period, array $statement, array $rows, array $entries, array $orders, string $store): array
{
    $s = $statement['sales'];
    $e = $statement['expenses'];
    $m = static fn (int $paisa, int $style = XLSX_STYLE_MONEY): array => statements_money_cell($paisa, $style);
    $summary = [
        [['v' => $store . ' — Statement', 's' => XLSX_STYLE_TITLE]],
        ['Period', $period['label']],
        ['Prepared', date('j M Y, g:i a')],
        [],
        [['v' => 'Income statement', 's' => XLSX_STYLE_HEAD], ['v' => 'Rs.', 's' => XLSX_STYLE_HEAD]],
        ['Gross sales (perfumes)', $m($s['gross'])],
        ['Less discounts', $m(-$s['discounts'])],
        [['v' => 'Net sales', 's' => XLSX_STYLE_BOLD], $m($s['net'], XLSX_STYLE_BOLD_MONEY)],
        ['Less cost of goods sold', $m(-$s['cogs'])],
        [['v' => 'Gross profit', 's' => XLSX_STYLE_BOLD], $m($s['profit'], XLSX_STYLE_BOLD_MONEY)],
        ['Gross margin', ['v' => $s['margin'] ?? 0, 's' => XLSX_STYLE_PERCENT]],
        ['Less operating expenses', $m(-$e['operating'])],
        ['Less sales commission', $m(-$statement['commission'])],
        ['Add other income', $m($e['income'])],
        [['v' => 'Net profit', 's' => XLSX_STYLE_BOLD], $m($statement['net_profit'], XLSX_STYLE_BOLD_MONEY)],
        [],
        [['v' => 'Sales detail', 's' => XLSX_STYLE_HEAD], ['v' => '', 's' => XLSX_STYLE_HEAD]],
        ['Orders', $s['orders']],
        ['Bottles sold', $s['bottles']],
        ['Delivery and COD fees charged', $m($s['delivery'])],
        ['Total billed to customers', $m($s['revenue'])],
        ['Cancelled orders', $statement['cancelled']['count']],
        ['Cancelled order value', $m($statement['cancelled']['value'])],
        ['Orders awaiting confirmation', $statement['pending']['count']],
        [],
        [['v' => 'Cash', 's' => XLSX_STYLE_HEAD], ['v' => '', 's' => XLSX_STYLE_HEAD]],
        ['Money collected from customers', $m($statement['cash']['collected'])],
        ['Still to collect on these orders', $m($statement['cash']['outstanding'])],
        ['Paid out for expenses and stock', $m($statement['cash']['spent'])],
        ['Stock bought (not deducted again from profit)', $m($e['stock'])],
        ['Bills still to pay (all open bills)', $m($statement['cash']['bills_due'])],
    ];
    if ($s['uncosted'] > 0) {
        $summary[] = [];
        $summary[] = ['Note', $s['uncosted'] . ' bottles sold have no cost entered, so profit is higher than it really is.'];
    }
    $byPeriod = [[['v' => 'Period', 's' => XLSX_STYLE_HEAD], ['v' => 'Orders', 's' => XLSX_STYLE_HEAD], ['v' => 'Net sales', 's' => XLSX_STYLE_HEAD], ['v' => 'Cost of goods', 's' => XLSX_STYLE_HEAD], ['v' => 'Gross profit', 's' => XLSX_STYLE_HEAD], ['v' => 'Expenses', 's' => XLSX_STYLE_HEAD], ['v' => 'Net profit', 's' => XLSX_STYLE_HEAD]]];
    foreach ($rows as $row) {
        $byPeriod[] = [$row['label'], $row['orders'], $m($row['net']), $m($row['cogs']), $m($row['profit']), $m($row['expenses']), $m($row['net_profit'])];
    }
    $products = [[['v' => 'Perfume', 's' => XLSX_STYLE_HEAD], ['v' => 'Size', 's' => XLSX_STYLE_HEAD], ['v' => 'Bottles', 's' => XLSX_STYLE_HEAD], ['v' => 'Net sales', 's' => XLSX_STYLE_HEAD], ['v' => 'Cost', 's' => XLSX_STYLE_HEAD], ['v' => 'Profit', 's' => XLSX_STYLE_HEAD], ['v' => 'Margin', 's' => XLSX_STYLE_HEAD]]];
    foreach ($statement['products'] as $p) {
        $products[] = [$p['name'], $p['size'], $p['bottles'], $m($p['net']), $m($p['cogs']), $m($p['profit']), ['v' => $p['margin'] ?? 0, 's' => XLSX_STYLE_PERCENT]];
    }
    $expenseSheet = [[['v' => 'Date', 's' => XLSX_STYLE_HEAD], ['v' => 'Type', 's' => XLSX_STYLE_HEAD], ['v' => 'Category', 's' => XLSX_STYLE_HEAD], ['v' => 'Details', 's' => XLSX_STYLE_HEAD], ['v' => 'Paid to / from', 's' => XLSX_STYLE_HEAD], ['v' => 'Amount', 's' => XLSX_STYLE_HEAD], ['v' => 'Status', 's' => XLSX_STYLE_HEAD], ['v' => 'Paid with', 's' => XLSX_STYLE_HEAD], ['v' => 'Reference', 's' => XLSX_STYLE_HEAD]]];
    foreach ($entries as $x) {
        $expenseSheet[] = [date('Y-m-d', strtotime((string) $x['entry_date'])), $x['entry_type'] === 'income' ? 'Income' : 'Expense', finance_category_label((string) $x['entry_type'], (string) $x['category']), (string) $x['title'], (string) ($x['vendor'] ?? ''), $m(money_paisa((string) $x['amount'])), $x['status'] === 'due' ? 'Due' . (!empty($x['due_date']) ? ' ' . $x['due_date'] : '') : ($x['entry_type'] === 'income' ? 'Received' : 'Paid'), FINANCE_PAID_FROM[(string) ($x['paid_from'] ?? '')] ?? '', (string) ($x['reference'] ?? '')];
    }
    $methods = [[['v' => 'Payment method', 's' => XLSX_STYLE_HEAD], ['v' => 'Orders', 's' => XLSX_STYLE_HEAD], ['v' => 'Billed', 's' => XLSX_STYLE_HEAD], ['v' => 'Paid', 's' => XLSX_STYLE_HEAD], ['v' => 'Still due', 's' => XLSX_STYLE_HEAD]]];
    foreach ($statement['methods'] as $x) {
        $methods[] = [$x['label'], $x['orders'], $m($x['revenue']), $m($x['paid']), $m($x['due'])];
    }
    $methods[] = [];
    $methods[] = [['v' => 'City', 's' => XLSX_STYLE_HEAD], ['v' => 'Orders', 's' => XLSX_STYLE_HEAD], ['v' => 'Billed', 's' => XLSX_STYLE_HEAD]];
    foreach ($statement['cities'] as $x) {
        $methods[] = [$x['city'], $x['orders'], $m($x['revenue'])];
    }
    $orderSheet = [[['v' => 'Order', 's' => XLSX_STYLE_HEAD], ['v' => 'Date', 's' => XLSX_STYLE_HEAD], ['v' => 'Status', 's' => XLSX_STYLE_HEAD], ['v' => 'Customer', 's' => XLSX_STYLE_HEAD], ['v' => 'City', 's' => XLSX_STYLE_HEAD], ['v' => 'Payment', 's' => XLSX_STYLE_HEAD], ['v' => 'Payment status', 's' => XLSX_STYLE_HEAD], ['v' => 'Bottles', 's' => XLSX_STYLE_HEAD], ['v' => 'Subtotal', 's' => XLSX_STYLE_HEAD], ['v' => 'Discount', 's' => XLSX_STYLE_HEAD], ['v' => 'Delivery', 's' => XLSX_STYLE_HEAD], ['v' => 'Total', 's' => XLSX_STYLE_HEAD], ['v' => 'Paid', 's' => XLSX_STYLE_HEAD], ['v' => 'Due', 's' => XLSX_STYLE_HEAD], ['v' => 'Cost', 's' => XLSX_STYLE_HEAD], ['v' => 'Profit', 's' => XLSX_STYLE_HEAD]]];
    foreach ($orders as $o) {
        $balance = order_balance($o);
        $net = money_paisa((string) $o['subtotal']) - money_paisa((string) $o['discount_total']);
        $cogs = money_paisa((string) $o['cogs']);
        $orderSheet[] = [(string) $o['order_number'], date('Y-m-d H:i', strtotime((string) $o['created_at'])), adm_order_status_label((string) $o['status']), (string) $o['customer_name'], (string) $o['city'], payment_method_label((string) $o['payment_method']), adm_order_payment_label((string) $o['payment_status']), (int) $o['item_count'], $m(money_paisa((string) $o['subtotal'])), $m(money_paisa((string) $o['discount_total'])), $m(money_paisa((string) $o['shipping_fee']) + money_paisa((string) $o['cod_fee'])), $m($balance['total']), $m($balance['paid']), $m($balance['due']), $m($cogs), $o['status'] === 'cancelled' ? '' : $m($net - $cogs)];
    }
    return [
        ['name' => 'Summary', 'rows' => $summary, 'widths' => [46, 22]],
        ['name' => 'By period', 'rows' => $byPeriod, 'widths' => [24, 10, 16, 16, 16, 16, 16], 'freeze' => 1],
        ['name' => 'Perfumes', 'rows' => $products, 'widths' => [34, 10, 10, 16, 16, 16, 10], 'freeze' => 1],
        ['name' => 'Expenses', 'rows' => $expenseSheet, 'widths' => [12, 10, 32, 36, 24, 14, 16, 14, 16], 'freeze' => 1],
        ['name' => 'Payments & cities', 'rows' => $methods, 'widths' => [24, 10, 16, 16, 16]],
        ['name' => 'Orders', 'rows' => $orderSheet, 'widths' => [14, 17, 12, 24, 14, 18, 15, 9, 12, 11, 11, 12, 12, 12, 12, 12], 'freeze' => 1],
    ];
}

$routeName = (string) ($route['name'] ?? 'admin.statements');
$karachi = new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi'));
$firstDay = dashboard_first_day($karachi->setTime(0, 0));
$period = dashboard_period($_GET, $karachi, $firstDay);
$statement = finance_statement($period);
$base = '/admin/statements';
$salesChart = dashboard_chart_build($period, $karachi, statements_series($statement['buckets'], 'net'), 'Net sales', $base);
$profitChart = dashboard_chart_build($period, $karachi, statements_series($statement['buckets'], 'net_profit'), 'Net profit', $base);
$rows = statements_rows($statement, $salesChart['bars']);
$query = $period['query'];
$store = (string) setting('store_name', 'Sky Fragrances');

if ($routeName === 'admin.statements.export') {
    $entries = db_fetch_all('SELECT * FROM expenses WHERE deleted_at IS NULL AND entry_date >= :from AND entry_date < :to ORDER BY entry_date ASC, id ASC', finance_dates($period['from'], $period['to']));
    $workbook = statements_workbook($period, $statement, $rows, $entries, finance_orders($period), $store);
    $filename = 'sky-fragrances-statement-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($period['label'])) . '.xlsx';
    catalogue_log('statement', null, 'statement.export', 'Downloaded the statement for ' . $period['label'] . ' as Excel');
    response_clear_buffers();
    header_no_store();
    if (!xlsx_available()) {
        flash('error', 'Excel files need the PHP zip extension, which this server does not have. Ask the host to enable "zip" in PHP settings.');
        redirect(dashboard_url($query, $base), 303);
    }
    $bytes = xlsx_build($workbook);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($bytes));
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}

if ($routeName === 'admin.statements.print') {
    adm_print_render('print-statement.php', [
        'store' => adm_print_store(),
        'period' => $period,
        'statement' => $statement,
        'rows' => $rows,
        'printedAt' => adm_order_datetime(now_karachi()),
        'returnUrl' => dashboard_url($query, $base),
    ]);
}

render_admin('statements.php', [
    'picker' => dashboard_picker($period, $karachi, $base),
    'period' => $period,
    'statement' => $statement,
    'salesChart' => $salesChart,
    'profitChart' => $profitChart,
    'rows' => $rows,
    'exportUrl' => dashboard_url($query, $base . '/export.xlsx'),
    'printUrl' => dashboard_url($query, $base . '/print'),
], ['title' => 'Statements', 'body_class' => (string) $route['body_class']]);
