<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/orders.php';

const STOCK_ERR_CONFLICT = 4001;

function stock_conflict(int $sizeId, int $available): RuntimeException
{
    return new RuntimeException($sizeId . ':' . $available, STOCK_ERR_CONFLICT);
}

function stock_conflict_details(Throwable $error): ?array
{
    if ($error->getCode() !== STOCK_ERR_CONFLICT || !preg_match('/^(\d+):(\d+)$/', $error->getMessage(), $m)) {
        return null;
    }
    return ['size_id' => (int) $m[1], 'available' => (int) $m[2]];
}

function stock_sort_lines(array $lines): array
{
    usort($lines, static fn (array $a, array $b): int => $a['size_id'] <=> $b['size_id']);
    return $lines;
}

function stock_decrement_lines(array $lines): void
{
    foreach (stock_sort_lines($lines) as $line) {
        $qty = (int) $line['qty'];
        $sizeId = (int) $line['size_id'];
        $affected = db_query(
            'UPDATE product_sizes SET stock = stock - :qty WHERE id = :id AND stock >= :guard',
            ['qty' => $qty, 'id' => $sizeId, 'guard' => $qty]
        )->rowCount();
        if ($affected !== 1) {
            $available = (int) db_fetch_column('SELECT stock FROM product_sizes WHERE id = :id', ['id' => $sizeId]);
            throw stock_conflict($sizeId, $available);
        }
    }
}

function stock_restorable_items(int $orderId): array
{
    return db_fetch_all(
        'SELECT product_size_id, product_name, size_label, sku, quantity FROM order_items WHERE order_id = :order ORDER BY product_size_id ASC',
        ['order' => $orderId]
    );
}

function stock_claim_restore(int $orderId, string $now): bool
{
    return db_query(
        'UPDATE orders SET stock_restored_at = :now, updated_at = :updated WHERE id = :id AND stock_restored_at IS NULL',
        ['now' => $now, 'updated' => $now, 'id' => $orderId]
    )->rowCount() === 1;
}

function stock_return_items(array $items): array
{
    $returned = [];
    $skipped = [];
    foreach ($items as $item) {
        if ($item['product_size_id'] === null) {
            $skipped[] = $item['product_name'] . ' ' . $item['size_label'];
            continue;
        }
        db_query('UPDATE product_sizes SET stock = stock + :qty WHERE id = :id', ['qty' => (int) $item['quantity'], 'id' => (int) $item['product_size_id']]);
        $returned[] = ['size_id' => (int) $item['product_size_id'], 'name' => $item['product_name'] . ' ' . $item['size_label'], 'sku' => $item['sku'], 'qty' => (int) $item['quantity']];
    }
    return ['returned' => $returned, 'skipped' => $skipped];
}

function stock_restore_summary(array $result): string
{
    $parts = array_map(static fn (array $r): string => $r['name'] . ' +' . $r['qty'], $result['returned']);
    $text = $parts === [] ? 'nothing to restore' : implode(', ', $parts);
    if ($result['skipped'] !== []) {
        $text .= ' (skipped, size deleted: ' . implode(', ', $result['skipped']) . ')';
    }
    return $text;
}

function stock_restore_order(int $orderId, array $actor, string $note = 'Parcel received back — stock restored'): array
{
    return db_transaction(static function () use ($orderId, $actor, $note): array {
        $order = db_fetch('SELECT id, status, stock_restored_at FROM orders WHERE id = :id FOR UPDATE', ['id' => $orderId]);
        if ($order === null) {
            return ['ok' => false, 'reason' => 'not_found', 'summary' => '', 'restored_at' => null];
        }
        if ($order['stock_restored_at'] !== null) {
            return ['ok' => false, 'reason' => 'already_restored', 'summary' => '', 'restored_at' => $order['stock_restored_at']];
        }
        if ($order['status'] !== 'cancelled') {
            return ['ok' => false, 'reason' => 'not_cancelled', 'summary' => '', 'restored_at' => null];
        }
        $now = now_karachi();
        if (!stock_claim_restore($orderId, $now)) {
            return ['ok' => false, 'reason' => 'already_restored', 'summary' => '', 'restored_at' => $now];
        }
        $result = stock_return_items(stock_restorable_items($orderId));
        $summary = stock_restore_summary($result);
        order_history_insert($orderId, 'status', 'cancelled', 'cancelled', $note . ': ' . $summary, $actor, $now);
        return ['ok' => true, 'reason' => 'restored', 'summary' => $summary, 'restored_at' => $now, 'returned' => $result['returned'], 'skipped' => $result['skipped']];
    });
}
