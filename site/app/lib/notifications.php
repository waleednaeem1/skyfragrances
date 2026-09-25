<?php
defined('SKYFR') || exit;

const NOTIFY_CONTACT_AUTOREPLY_HOURLY_CAP = 30;
const NOTIFY_CONTACT_AUTOREPLY_DEDUPE_SECONDS = 86400;
const NOTIFY_CONTACT_ADMIN_HOURLY_CAP = 30;
const NOTIFY_CONTACT_ADMIN_DIGEST_SUBJECT = 'Contact form: hourly email cap reached';

function notify_admin_email(): string
{
    $email = trim((string) setting('order_notify_email', ''));
    return $email !== '' ? $email : trim((string) setting('contact_email', ''));
}

function notify_order_payload(array $order): array
{
    return [
        'order' => $order,
        'items' => order_items((int) $order['id']),
        'track_url' => SITE_URL . '/track',
        'order_url' => SITE_URL . '/order/' . $order['order_number'] . '?t=' . $order['access_token'],
    ];
}

function notify_order_flags(array $order): array
{
    $flags = [];
    if ($order['payment_reference'] !== null && payment_duplicate_reference((string) $order['payment_reference'], (int) $order['id'])) {
        $flags[] = 'Duplicate transaction ID: another order in the last ' . PAYMENT_DUPLICATE_REF_DAYS . ' days used ' . $order['payment_reference'] . '.';
    }
    if ($order['payment_method'] === 'cod') {
        $byPhone = order_recent_count('phone_normalized', (string) $order['phone_normalized'], 86400);
        if ($byPhone > ORDER_COD_FLAG_PER_PHONE_DAY) {
            $flags[] = 'Repeat COD customer: ' . $byPhone . ' orders from this phone in 24 hours.';
        }
        if ($order['ip_hash'] !== null && order_recent_count('ip_hash', (string) $order['ip_hash'], 86400) > ORDER_COD_FLAG_PER_IP_DAY) {
            $flags[] = 'Many COD orders from one connection in 24 hours.';
        }
    }
    foreach (db_fetch_all('SELECT oi.product_name, oi.size_label, ps.stock FROM order_items oi INNER JOIN product_sizes ps ON ps.id = oi.product_size_id WHERE oi.order_id = :id AND ps.stock <= :low', ['id' => (int) $order['id'], 'low' => setting_int('low_stock_threshold', 5)]) as $row) {
        $flags[] = 'Low stock: ' . $row['product_name'] . ' ' . $row['size_label'] . ' has ' . (int) $row['stock'] . ' left.';
    }
    return $flags;
}

function notify_order_placed(int $orderId): array
{
    $result = ['customer' => null, 'admin' => false];
    try {
        $order = order_find($orderId);
        if ($order === null) {
            return $result;
        }
        $payload = notify_order_payload($order);
        $subject = 'Order ' . $order['order_number'] . ' confirmed — ' . (string) setting('store_name', 'Sky Fragrances');
        $email = trim((string) ($order['customer_email'] ?? ''));
        if ($email !== '') {
            $result['customer'] = mail_deliver('order-customer', $payload, $email, (string) $order['customer_name'], $subject, $orderId);
        }
        $admin = notify_admin_email();
        if ($admin !== '') {
            $flags = notify_order_flags($order);
            $adminSubject = ($flags !== [] ? '[REVIEW] ' : '') . 'New order ' . $order['order_number'] . ' — ' . money((string) $order['grand_total']) . ' — ' . payment_method_label((string) $order['payment_method']) . ' — ' . $order['city'];
            $adminPayload = $payload + [
                'flags' => $flags,
                'admin_url' => SITE_URL . '/admin/orders/' . $order['order_number'],
                'transaction_ref' => (string) ($order['payment_reference'] ?? ''),
                'reply_to_email' => $email,
                'reply_to_name' => (string) $order['customer_name'],
            ];
            mail_queue('order-admin', $adminPayload, $admin, '', $adminSubject, $orderId);
            $result['admin'] = true;
        }
    } catch (Throwable $e) {
        log_write('warning', 'notify: order placed emails failed', ['order_id' => $orderId, 'error' => $e->getMessage()]);
    }
    return $result;
}

function notify_status_subject(array $order, string $status): ?string
{
    $number = $order['order_number'];
    return match ($status) {
        'confirmed' => 'Order ' . $number . ' is confirmed',
        'shipped' => 'Your order ' . $number . ' is on its way',
        'delivered' => 'Delivered — order ' . $number,
        'cancelled' => 'Order ' . $number . ' has been cancelled',
        default => null,
    };
}

function notify_order_status(int $orderId, string $status): bool
{
    try {
        $order = order_find($orderId);
        $subject = $order === null ? null : notify_status_subject($order, $status);
        $email = trim((string) ($order['customer_email'] ?? ''));
        if ($subject === null || $email === '') {
            return false;
        }
        mail_queue('status-update', notify_order_payload($order) + ['status' => $status], $email, (string) $order['customer_name'], $subject, $orderId);
        return true;
    } catch (Throwable $e) {
        log_write('warning', 'notify: status email failed', ['order_id' => $orderId, 'status' => $status, 'error' => $e->getMessage()]);
        return false;
    }
}

function notify_payment_received(int $orderId): bool
{
    try {
        $order = order_find($orderId);
        $email = trim((string) ($order['customer_email'] ?? ''));
        if ($order === null || $email === '') {
            return false;
        }
        $message = 'we have received your payment of ' . money((string) $order['grand_total']) . ($order['payment_reference'] ? ' (transaction ' . $order['payment_reference'] . ')' : '') . '. Your order will be confirmed and dispatched within ' . (string) setting('delivery_time', '2–4 working days') . '.';
        mail_queue('status-update', notify_order_payload($order) + ['status' => 'paid', 'message' => $message], $email, (string) $order['customer_name'], 'Payment received for order ' . $order['order_number'], $orderId);
        return true;
    } catch (Throwable $e) {
        log_write('warning', 'notify: payment received email failed', ['order_id' => $orderId, 'error' => $e->getMessage()]);
        return false;
    }
}

function notify_payment_rejected(int $orderId, string $reason): bool
{
    try {
        $order = order_find($orderId);
        $email = trim((string) ($order['customer_email'] ?? ''));
        if ($order === null || $email === '') {
            return false;
        }
        $message = "we couldn't verify your payment. " . trim($reason) . ' Please check the transfer and upload a new receipt from your order page: ' . SITE_URL . '/order/' . $order['order_number'] . '?t=' . $order['access_token'];
        mail_queue('status-update', notify_order_payload($order) + ['status' => 'payment not verified', 'message' => $message], $email, (string) $order['customer_name'], "We couldn't verify your payment — order " . $order['order_number'], $orderId);
        return true;
    } catch (Throwable $e) {
        log_write('warning', 'notify: payment rejected email failed', ['order_id' => $orderId, 'error' => $e->getMessage()]);
        return false;
    }
}

function notify_contact_autoreply_allowed(string $email): bool
{
    $duplicate = db_exists('SELECT 1 FROM contact_messages WHERE email = :email AND created_at > :since LIMIT 1', ['email' => $email, 'since' => date('Y-m-d H:i:s', time() - NOTIFY_CONTACT_AUTOREPLY_DEDUPE_SECONDS)]);
    if ($duplicate) {
        return false;
    }
    $sentThisHour = (int) db_fetch_column("SELECT COUNT(*) FROM email_outbox WHERE template = 'contact-autoreply' AND created_at > :since", ['since' => date('Y-m-d H:i:s', time() - 3600)]);
    return $sentThisHour < NOTIFY_CONTACT_AUTOREPLY_HOURLY_CAP;
}

function notify_contact_admin_mode(): string
{
    $since = date('Y-m-d H:i:s', time() - 3600);
    $queuedThisHour = (int) db_fetch_column("SELECT COUNT(*) FROM email_outbox WHERE template = 'contact-admin' AND created_at > :since", ['since' => $since]);
    if ($queuedThisHour < NOTIFY_CONTACT_ADMIN_HOURLY_CAP) {
        return 'send';
    }
    $digestQueued = db_exists("SELECT 1 FROM email_outbox WHERE template = 'contact-admin' AND subject = :subject AND created_at > :since LIMIT 1", ['subject' => NOTIFY_CONTACT_ADMIN_DIGEST_SUBJECT, 'since' => $since]);
    return $digestQueued ? 'skip' : 'digest';
}

function notify_contact_admin_digest_payload(): array
{
    return [
        'name' => (string) setting('store_name', 'Sky Fragrances'),
        'subject' => 'Hourly email cap reached',
        'message' => 'More than ' . NOTIFY_CONTACT_ADMIN_HOURLY_CAP . " contact messages arrived in the last hour, so the rest are not being emailed one by one. Read them in the admin panel: " . SITE_URL . '/admin/messages',
        'created_at' => now_karachi(),
    ];
}

function notify_contact(array $message, bool $autoreplyAllowed): array
{
    $result = ['admin' => false, 'autoreply' => false];
    try {
        $admin = notify_admin_email();
        $adminMode = $admin === '' ? 'skip' : notify_contact_admin_mode();
        if ($adminMode === 'send') {
            $payload = $message + ['reply_to_email' => (string) ($message['email'] ?? ''), 'reply_to_name' => (string) ($message['name'] ?? '')];
            mail_queue('contact-admin', $payload, $admin, '', 'Contact form: ' . (($message['subject'] ?? '') !== '' ? $message['subject'] : 'New message'));
            $result['admin'] = true;
        } elseif ($adminMode === 'digest') {
            mail_queue('contact-admin', notify_contact_admin_digest_payload(), $admin, '', NOTIFY_CONTACT_ADMIN_DIGEST_SUBJECT);
            log_write('warning', 'notify: contact-admin hourly cap reached, digest queued');
        }
        $email = trim((string) ($message['email'] ?? ''));
        if ($autoreplyAllowed && $email !== '') {
            mail_queue('contact-autoreply', ['name' => $message['name'] ?? '', 'subject' => $message['subject'] ?? ''], $email, (string) ($message['name'] ?? ''), "We've received your message — " . (string) setting('store_name', 'Sky Fragrances'));
            $result['autoreply'] = true;
        }
    } catch (Throwable $e) {
        log_write('warning', 'notify: contact emails failed', ['error' => $e->getMessage()]);
    }
    return $result;
}
