<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/order-actions.php';

const ADM_PRINT_BULK_MAX = 50;

function adm_print_orders(array $params): array
{
    $first = adm_order_load($params);
    $ids = [];
    foreach (explode(',', (string) request_query('ids', '')) as $raw) {
        $raw = trim($raw);
        if (ctype_digit($raw) && (int) $raw > 0) {
            $ids[(int) $raw] = (int) $raw;
        }
    }
    if ($ids === []) {
        return [$first];
    }
    $ids = array_slice(array_values($ids), 0, ADM_PRINT_BULK_MAX);
    $orders = [];
    foreach ($ids as $id) {
        $order = order_find($id);
        if ($order !== null) {
            $orders[(int) $order['id']] = $order;
        }
    }
    if (!isset($orders[(int) $first['id']])) {
        $orders = [(int) $first['id'] => $first] + $orders;
    }
    return array_values($orders);
}

$routeName = (string) ($route['name'] ?? '');
if ($routeName === 'admin.orders.receipt') {
    $order = adm_order_load($params);
    $width = request_query('w', '80') === '58' ? 58 : 80;
    adm_order_log($order, 'order.receipt_printed', 'Small receipt (' . $width . ' mm) opened for printing');
    $sales = $order['sales_member_id'] !== null ? team_commission_for_order((int) $order['id']) : null;
    adm_print_render('print-receipt.php', [
        'p' => adm_order_print_payload($order),
        'store' => adm_print_store(),
        'width' => $width,
        'servedBy' => $sales !== null ? (string) $sales['member_name'] : '',
        'reward' => reward_for_order((int) $order['id']),
        'printedAt' => adm_order_datetime(now_karachi()),
        'autoprint' => request_query('print', '1') !== '0',
        'returnUrl' => '/admin/orders/' . $order['order_number'] . '/view',
    ]);
}
$isInvoice = $routeName === 'admin.orders.invoice';
$orders = $isInvoice ? [adm_order_load($params)] : adm_print_orders($params);
$itemsByOrder = adm_orders_items_by_order(array_map(static fn (array $o): int => (int) $o['id'], $orders));
$payloads = [];
foreach ($orders as $order) {
    $payloads[] = adm_order_print_payload($order, $itemsByOrder[(int) $order['id']] ?? []);
    adm_order_log($order, 'order.invoice_printed', ($isInvoice ? 'Invoice' : 'Packing slip') . ' opened for printing' . (count($orders) > 1 ? ' (batch of ' . count($orders) . ')' : ''));
}
$store = adm_print_store();

adm_print_render($isInvoice ? 'print-invoice.php' : 'print-packing-slip.php', [
    'payloads' => $payloads,
    'store' => $store,
    'logoUri' => $store['logo'],
    'printedAt' => adm_order_datetime(now_karachi()),
    'autoprint' => request_query('print', '1') !== '0',
    'returnUrl' => '/admin/orders/' . $orders[0]['order_number'],
    'deliveryTime' => (string) setting('delivery_time', '2–4 working days'),
]);
