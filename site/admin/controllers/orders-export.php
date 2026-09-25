<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/order-actions.php';

const ADM_EXPORT_FLUSH_EVERY = 200;
const ADM_EXPORT_COLUMNS = ['Order Number', 'Placed At', 'Status', 'Customer Name', 'Phone', 'Email', 'City', 'Address', 'Postal Code', 'Items', 'Item Count', 'Subtotal (PKR)', 'Discount (PKR)', 'Coupon Code', 'Shipping (PKR)', 'Grand Total (PKR)', 'Payment Method', 'Payment Status', 'Paid At', 'Transaction ID', 'Courier', 'Tracking Number', 'Customer Notes', 'Admin Notes'];
const ADM_EXPORT_NUMERIC = [10, 11, 12, 14, 15];

function adm_export_text(?string $value): string
{
    $value = str_replace("\0", '', (string) $value);
    $trimmed = ltrim($value, " \t\r\n");
    if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $value;
    }
    return $value;
}

function adm_export_datetime(?string $dt): string
{
    return $dt === null || $dt === '' ? '' : date('Y-m-d H:i', strtotime($dt));
}

function adm_export_row(array $o, array $items, string $adminNotes): array
{
    $cells = [
        $o['order_number'],
        adm_export_datetime((string) $o['created_at']),
        $o['status'],
        $o['customer_name'],
        "'" . preg_replace('/[^0-9+]/', '', (string) $o['customer_phone']),
        (string) ($o['customer_email'] ?? ''),
        $o['city'],
        (string) $o['address'],
        (string) ($o['postal_code'] ?? ''),
        adm_order_items_summary($items),
        (string) (int) $o['item_count'],
        money_attr((string) $o['subtotal']),
        money_attr((string) $o['discount_total']),
        (string) ($o['coupon_code'] ?? ''),
        money_attr((string) $o['shipping_fee']),
        money_attr((string) $o['grand_total']),
        payment_method_label((string) $o['payment_method']),
        $o['payment_status'],
        adm_export_datetime($o['paid_at'] ?? null),
        (string) ($o['payment_reference'] ?? ''),
        (string) ($o['courier_name'] ?? ''),
        (string) ($o['tracking_number'] ?? ''),
        (string) ($o['customer_note'] ?? ''),
        $adminNotes,
    ];
    foreach ($cells as $i => $cell) {
        if ($i === 4 || in_array($i, ADM_EXPORT_NUMERIC, true)) {
            continue;
        }
        $cells[$i] = adm_export_text($cell);
    }
    return $cells;
}

$filters = adm_orders_filters($_GET);
$built = adm_orders_where($filters, 'contains');
$total = (int) db_fetch_column('SELECT COUNT(*) FROM orders o WHERE ' . $built['where'], $built['params']);
if ($total > ADM_ORDER_EXPORT_CAP) {
    flash('error', 'That export would contain ' . number_format($total) . ' orders. Narrow the date range to ' . number_format(ADM_ORDER_EXPORT_CAP) . ' or fewer and try again.');
    redirect(request_return_path('/admin/orders'), 303);
}
$sql = 'SELECT o.* FROM orders o WHERE ' . $built['where'] . ' ORDER BY ' . $built['order'];
$stmt = db_query($sql, $built['params']);
$rows = $stmt->fetchAll();
$itemsByOrder = adm_orders_items_by_order(array_map(static fn (array $r): int => (int) $r['id'], $rows));
$notesByOrder = [];
if ($rows !== []) {
    $marks = [];
    $bind = [];
    foreach (array_values($rows) as $n => $r) {
        $marks[] = ':n' . $n;
        $bind['n' . $n] = (int) $r['id'];
    }
    foreach (db_fetch_all('SELECT order_id, body FROM order_notes WHERE order_id IN (' . implode(',', $marks) . ') ORDER BY order_id ASC, created_at ASC, id ASC', $bind) as $note) {
        $notesByOrder[(int) $note['order_id']][] = str_replace(["\r\n", "\r"], "\n", (string) $note['body']);
    }
}
adm_order_log(null, 'orders.export', 'Orders exported to CSV: ' . count($rows) . ' rows (' . adm_orders_summary_text($filters) . ')');

response_clear_buffers();
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="sky-orders-' . date('Y-m-d') . '.csv"');
header('X-Content-Type-Options: nosniff');
header_no_store();
$out = fopen('php://output', 'wb');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ADM_EXPORT_COLUMNS, ',', '"', '', "\r\n");
$written = 0;
foreach ($rows as $r) {
    fputcsv($out, adm_export_row($r, $itemsByOrder[(int) $r['id']] ?? [], implode("\n\n", $notesByOrder[(int) $r['id']] ?? [])), ',', '"', '', "\r\n");
    if (++$written % ADM_EXPORT_FLUSH_EVERY === 0) {
        fflush($out);
        flush();
    }
}
fclose($out);
exit;
