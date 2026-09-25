<?php
defined('SKYFR') || exit;

const PAYMENT_MANUAL_METHODS = ['bank', 'jazzcash', 'easypaisa'];
const PAYMENT_REFERENCE_PATTERN = '/^[A-Z0-9-]{5,30}$/';
const PAYMENT_PROOF_PER_ORDER_MAX = 3;
const PAYMENT_PROOF_PER_IP_HOUR_MAX = 5;
const PAYMENT_PROOF_PER_ORDER_WINDOW_SECONDS = 86400;
const PAYMENT_REVIEW_APPROVE_FROM = ['unpaid', 'awaiting_verification', 'failed'];
const PAYMENT_REVIEW_REJECT_FROM = ['unpaid', 'awaiting_verification'];
const PAYMENT_DUPLICATE_REF_DAYS = 90;

function payment_method_is_manual(string $method): bool
{
    return in_array($method, PAYMENT_MANUAL_METHODS, true);
}

function payment_method_label(string $method): string
{
    return mail_payment_method_label($method);
}

function payment_method_copy(string $method): string
{
    return match ($method) {
        'cod' => (string) setting('cod_note', 'Pay the courier when your parcel arrives.'),
        'bank' => 'Transfer from any bank app, then share the receipt.',
        'jazzcash' => 'Send from your JazzCash app or any shop, then share the receipt.',
        'easypaisa' => 'Send from your Easypaisa app or any shop, then share the receipt.',
        default => '',
    };
}

function payment_account_lines(string $method): array
{
    return mail_payment_account_lines($method);
}

function payment_method_enabled(string $method): bool
{
    if (!in_array($method, PAYMENT_METHODS, true) || !setting_bool($method . '_enabled', false)) {
        return false;
    }
    return !payment_method_is_manual($method) || payment_account_lines($method) !== [];
}

function payment_methods_enabled(): array
{
    return array_values(array_filter(PAYMENT_METHODS, 'payment_method_enabled'));
}

function payment_methods_for_view(): array
{
    $methods = [];
    foreach (payment_methods_enabled() as $method) {
        $methods[] = [
            'key' => $method,
            'label' => payment_method_label($method),
            'copy' => payment_method_copy($method),
            'is_manual' => payment_method_is_manual($method),
            'account_lines' => payment_account_lines($method),
        ];
    }
    return $methods;
}

function payment_account_snapshot(string $method): ?string
{
    $lines = payment_account_lines($method);
    if ($lines === []) {
        return null;
    }
    $parts = [];
    foreach ($lines as $label => $value) {
        $parts[] = $label . ': ' . $value;
    }
    return mb_substr(payment_method_label($method) . ' — ' . implode(' · ', $parts), 0, 160);
}

function payment_cod_cap_paisa(): int
{
    return money_paisa(setting_money('cod_max_total', '0.00'));
}

function payment_cod_blocked_message(int $grandTotalPaisa): ?string
{
    $cap = payment_cod_cap_paisa();
    if ($cap > 0 && $grandTotalPaisa > $cap) {
        return 'Orders above ' . money(money_from_paisa($cap)) . ' are prepaid only.';
    }
    return null;
}

function payment_reference_normalize(string $raw): string
{
    return strtoupper(trim(preg_replace('/\s+/u', '', $raw) ?? ''));
}

function payment_reference_validate(string $raw): array
{
    $ref = payment_reference_normalize($raw);
    if (!preg_match(PAYMENT_REFERENCE_PATTERN, $ref)) {
        return ['ok' => false, 'value' => $ref, 'message' => 'Please enter the transaction ID from your transfer receipt (5–30 letters or digits).'];
    }
    return ['ok' => true, 'value' => $ref, 'message' => ''];
}

function payment_proof_validate(?array $file): array
{
    if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Please upload a screenshot of your payment.', 'mime' => '', 'width' => 0, 'height' => 0];
    }
    return upload_validate_proof($file);
}

function payment_duplicate_reference(string $ref, int $excludeOrderId): bool
{
    return db_exists(
        'SELECT 1 FROM orders WHERE payment_reference = :ref AND id <> :id AND created_at > :since LIMIT 1',
        ['ref' => $ref, 'id' => $excludeOrderId, 'since' => date('Y-m-d H:i:s', time() - PAYMENT_DUPLICATE_REF_DAYS * 86400)]
    );
}

function payment_proof_can_upload(array $order): bool
{
    return payment_method_is_manual((string) $order['payment_method'])
        && $order['status'] === 'pending'
        && in_array($order['payment_status'], ['unpaid', 'failed'], true);
}

function payment_proof_order_subject(int $orderId): string
{
    return rate_limit_subject('order:' . $orderId);
}

function payment_proof_upload_limited(int $orderId): bool
{
    return rate_limit_count('proof', payment_proof_order_subject($orderId), PAYMENT_PROOF_PER_ORDER_WINDOW_SECONDS) >= PAYMENT_PROOF_PER_ORDER_MAX
        || rate_limit_count('proof', request_ip_hash(), 3600) >= PAYMENT_PROOF_PER_IP_HOUR_MAX;
}

function payment_proof_record_submission(): void
{
    rate_limit_record('proof', request_ip_hash());
}

function payment_proof_record_stored(int $orderId): void
{
    rate_limit_record('proof', payment_proof_order_subject($orderId), true);
}

function payment_proof_reset_budget(int $orderId): void
{
    rate_limit_forget('proof', payment_proof_order_subject($orderId));
}

function payment_proof_store(array $order, string $tmpPath, string $mime, ?string $reference, ?string $senderName, ?string $amountClaimed, string $ipHash): array
{
    $orderId = (int) $order['id'];
    $relPath = null;
    try {
        $relPath = image_store_proof($tmpPath, (string) $order['order_number']);
    } catch (Throwable $e) {
        log_write('warning', 'proof: write failed, order kept', ['order' => $order['order_number'], 'error' => $e->getMessage()]);
    }
    $storedBytes = $relPath === null ? (int) @filesize($tmpPath) : (int) @filesize(APP_ROOT . '/storage/proofs/' . $relPath);
    $sha = $relPath === null ? hash_file('sha256', $tmpPath) : hash_file('sha256', APP_ROOT . '/storage/proofs/' . $relPath);
    $now = now_karachi();
    return db_transaction(static function () use ($order, $orderId, $relPath, $mime, $storedBytes, $sha, $reference, $senderName, $amountClaimed, $ipHash, $now): array {
        $proofId = db_insert('payment_proofs', [
            'order_id' => $orderId,
            'file_path' => $relPath,
            'original_name' => null,
            'mime_type' => $relPath === null ? $mime : 'image/jpeg',
            'byte_size' => max(0, $storedBytes),
            'sha256' => (string) $sha,
            'transaction_ref' => $reference,
            'sender_name' => $senderName,
            'amount_claimed' => $amountClaimed,
            'review_status' => 'pending',
            'uploaded_ip_hash' => $ipHash,
            'created_at' => $now,
        ]);
        $moved = db_query(
            "UPDATE orders SET payment_status = 'awaiting_verification', payment_reference = COALESCE(:ref, payment_reference), updated_at = :now WHERE id = :id AND payment_status IN ('unpaid', 'failed')",
            ['ref' => $reference, 'now' => $now, 'id' => $orderId]
        )->rowCount() === 1;
        if ($moved) {
            order_history_insert($orderId, 'payment_status', (string) $order['payment_status'], 'awaiting_verification', $relPath === null ? 'Proof submitted (file missing)' : 'Proof submitted', ['by' => 'customer'], $now);
        }
        return ['proof_id' => $proofId, 'file_path' => $relPath, 'status_moved' => $moved];
    });
}

function payment_review(int $orderId, ?int $proofId, bool $approve, array $actor, string $note = ''): array
{
    $result = db_transaction(static function () use ($orderId, $proofId, $approve, $actor, $note): array {
        $order = db_fetch('SELECT id, order_number, status, payment_status, customer_email, customer_name FROM orders WHERE id = :id FOR UPDATE', ['id' => $orderId]);
        if ($order === null) {
            return ['ok' => false, 'message' => 'Order not found.', 'order' => null];
        }
        $from = (string) $order['payment_status'];
        if (!in_array($from, $approve ? PAYMENT_REVIEW_APPROVE_FROM : PAYMENT_REVIEW_REJECT_FROM, true)) {
            return ['ok' => false, 'message' => 'This order is already ' . $from . '.', 'order' => $order];
        }
        if (!$approve && trim($note) === '') {
            return ['ok' => false, 'message' => 'A short reason is required when rejecting a payment.', 'order' => $order];
        }
        $now = now_karachi();
        $to = $approve ? 'paid' : 'failed';
        $moved = db_query(
            'UPDATE orders SET payment_status = :to, paid_at = :paid, updated_at = :now WHERE id = :id AND payment_status = :from',
            ['to' => $to, 'paid' => $approve ? $now : null, 'now' => $now, 'id' => $orderId, 'from' => $from]
        )->rowCount() === 1;
        if (!$moved) {
            throw new RuntimeException('payment status changed concurrently');
        }
        if ($proofId !== null) {
            db_update('payment_proofs', [
                'review_status' => $approve ? 'approved' : 'rejected',
                'reviewed_by' => $actor['admin_id'] ?? null,
                'reviewed_by_name' => $actor['admin_username'] ?? null,
                'reviewed_at' => $now,
                'review_note' => $note === '' ? null : mb_substr($note, 0, 255),
            ], ['id' => $proofId, 'order_id' => $orderId]);
        }
        if ($approve) {
            db_query(
                "UPDATE payment_proofs SET review_status = 'superseded', reviewed_at = :now WHERE order_id = :id AND review_status = 'pending' AND id <> :keep",
                ['now' => $now, 'id' => $orderId, 'keep' => $proofId ?? 0]
            );
        } else {
            payment_proof_reset_budget($orderId);
        }
        order_history_insert($orderId, 'payment_status', $from, $to, $approve ? 'Payment approved' : 'Payment rejected: ' . $note, $actor, $now);
        $order['payment_status'] = $to;
        return ['ok' => true, 'message' => $approve ? 'Payment approved.' : 'Payment rejected.', 'order' => $order];
    });
    if ($result['ok']) {
        $approve ? notify_payment_received($orderId) : notify_payment_rejected($orderId, $note);
    }
    return $result;
}

function payment_mark_paid(int $orderId, array $actor, ?string $amountReceived = null, string $note = ''): array
{
    return db_transaction(static function () use ($orderId, $actor, $amountReceived, $note): array {
        $order = db_fetch('SELECT id, payment_status, payment_method, grand_total FROM orders WHERE id = :id FOR UPDATE', ['id' => $orderId]);
        if ($order === null) {
            return ['ok' => false, 'message' => 'Order not found.'];
        }
        if (!in_array($order['payment_status'], ['unpaid', 'awaiting_verification', 'failed'], true)) {
            return ['ok' => false, 'message' => 'This order is already ' . $order['payment_status'] . '.'];
        }
        $now = now_karachi();
        db_query("UPDATE orders SET payment_status = 'paid', paid_at = :now, updated_at = :now2 WHERE id = :id", ['now' => $now, 'now2' => $now, 'id' => $orderId]);
        $history = 'Payment marked as received — ' . payment_method_label((string) $order['payment_method']);
        if ($amountReceived !== null && money_paisa($amountReceived) !== money_paisa((string) $order['grand_total'])) {
            $history .= ' (received ' . money($amountReceived) . ' against ' . money((string) $order['grand_total']) . ')';
        }
        if (trim($note) !== '') {
            $history .= ' — ' . trim($note);
        }
        order_history_insert($orderId, 'payment_status', (string) $order['payment_status'], 'paid', mb_substr($history, 0, 500), $actor, $now);
        return ['ok' => true, 'message' => 'Payment recorded.'];
    });
}
