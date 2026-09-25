<?php
defined('SKYFR') || exit;

const MAIL_SMTP_TIMEOUT = 5;
const MAIL_OUTBOX_MAX_ATTEMPTS = 6;
const MAIL_DRAIN_MAX_SENDS = 2;
const MAIL_DRAIN_BUDGET_SECONDS = 12;
const MAIL_OUTBOX_BODY_RETENTION_DAYS = 60;
const MAIL_OUTBOX_LEASE_SECONDS = 60;
const MAIL_PREVIEW_DIR = '/storage/logs/mail-preview';

function mail_send(string $template, array $data, string $toEmail, string $toName, string $subject): bool
{
    try {
        $toEmail = trim($toEmail);
        if (filter_var($toEmail, FILTER_VALIDATE_EMAIL) === false) {
            log_write('warning', 'mail: invalid recipient', ['template' => $template]);
            return false;
        }
        $html = mail_render($template, $data, $subject);
        $text = mail_text_alternative($html);
        $replyTo = mail_reply_to($data);

        if (!mail_transport_available()) {
            if (!mail_preview_mode()) {
                log_write('warning', 'mail: transport unavailable, not sent', ['template' => $template]);
                return false;
            }
            return mail_preview_write($template, $toEmail, $subject, $html);
        }
        return mail_send_smtp($toEmail, $toName, $subject, $html, $text, $replyTo);
    } catch (Throwable $e) {
        log_write('warning', 'mail: send failed', ['template' => $template, 'error' => $e->getMessage()]);
        return false;
    }
}

function mail_render(string $template, array $data, string $subject): string
{
    if (!preg_match('/^[a-z0-9-]+$/', $template)) {
        throw new RuntimeException('Bad mail template name');
    }
    $file = APP_ROOT . '/app/emails/' . $template . '.php';
    if (!is_file($file)) {
        throw new RuntimeException('Mail template missing: ' . $template);
    }
    $body = (static function (string $__file, array $data): string {
        ob_start();
        try {
            include $__file;
        } finally {
            $out = (string) ob_get_clean();
        }
        return $out;
    })($file, $data);
    return mail_wrap($body, $subject);
}

function mail_wrap(string $body, string $subject): string
{
    $store = (string) setting('store_name', 'Sky Fragrances');
    $tagline = (string) setting('store_tagline', 'More Than Just A Scent');
    $phone = (string) setting('contact_phone', '');
    $address = (string) setting('address_line', '');
    $footerBits = array_filter([$address, $phone]);
    $footer = e(implode(' · ', $footerBits));
    $siteUrl = e(SITE_URL);
    $logo = e(SITE_URL . '/assets/img/logo.png');
    return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width">'
        . '<title>' . e($subject) . '</title></head>'
        . '<body style="margin:0;padding:0;background:#f5f0e8;font-family:Georgia,\'Times New Roman\',serif;color:#0a0a0a;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0e8;padding:24px 0;"><tr><td align="center">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;">'
        . '<tr><td align="center" style="background:#0a0a0a;padding:28px 24px;">'
        . '<a href="' . $siteUrl . '" style="text-decoration:none;"><img src="' . $logo . '" alt="' . e($store) . '" width="140" style="display:block;border:0;max-width:140px;height:auto;"></a>'
        . '<div style="color:#d4b084;font-size:12px;letter-spacing:3px;text-transform:uppercase;padding-top:12px;">' . e($tagline) . '</div>'
        . '</td></tr>'
        . '<tr><td style="padding:32px 24px;font-size:16px;line-height:1.6;">' . $body . '</td></tr>'
        . '<tr><td style="background:#0a0a0a;color:#d4b084;padding:20px 24px;font-size:12px;line-height:1.6;text-align:center;">'
        . '<div style="letter-spacing:2px;text-transform:uppercase;">' . e($store) . '</div>'
        . ($footer !== '' ? '<div style="color:#bfb3a3;padding-top:6px;">' . $footer . '</div>' : '')
        . '<div style="color:#bfb3a3;padding-top:6px;"><a href="' . $siteUrl . '" style="color:#d4b084;text-decoration:none;">' . $siteUrl . '</a></div>'
        . '</td></tr></table></td></tr></table></body></html>';
}

function mail_text_alternative(string $html): string
{
    $text = preg_replace('~<style\b[^>]*>.*?</style>~is', '', $html) ?? $html;
    $text = preg_replace('~<a\b[^>]*href="([^"]+)"[^>]*>(.*?)</a>~is', '$2 ($1)', $text) ?? $text;
    $text = preg_replace('~</(p|div|tr|h[1-6]|li|table)>|<br\s*/?>~i', "\n", $text) ?? $text;
    $text = preg_replace('~</t[dh]>~i', "\t", $text) ?? $text;
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('~[ \t]+~', ' ', $text) ?? $text;
    $text = preg_replace('~\n{3,}~', "\n\n", $text) ?? $text;
    return trim($text);
}

function mail_reply_to(array $data): array
{
    $email = trim((string) ($data['reply_to_email'] ?? ''));
    $name = trim((string) ($data['reply_to_name'] ?? ''));
    if ($email === '') {
        $email = (string) setting('order_notify_email', '');
        if ($email === '') {
            $email = (string) setting('contact_email', '');
        }
        $name = (string) setting('store_name', 'Sky Fragrances');
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return ['', ''];
    }
    return [$email, $name];
}

function mail_vendor_files(): array
{
    $dir = APP_ROOT . '/app/lib/vendor/PHPMailer/';
    return [$dir . 'Exception.php', $dir . 'PHPMailer.php', $dir . 'SMTP.php'];
}

function mail_transport_available(): bool
{
    if (trim((string) config('smtp.host', '')) === '') {
        return false;
    }
    foreach (mail_vendor_files() as $file) {
        if (!is_file($file)) {
            return false;
        }
    }
    return true;
}

function mail_preview_mode(): bool
{
    return APP_ENV !== 'production';
}

function mail_preview_prune(int $retentionDays = MAIL_OUTBOX_BODY_RETENTION_DAYS): int
{
    $dir = APP_ROOT . MAIL_PREVIEW_DIR;
    if (!is_dir($dir)) {
        return 0;
    }
    $cutoff = time() - $retentionDays * 86400;
    $removed = 0;
    foreach (glob($dir . '/*.html') ?: [] as $file) {
        if (is_file($file) && (int) @filemtime($file) < $cutoff && @unlink($file)) {
            $removed++;
        }
    }
    return $removed;
}

function mail_preview_write(string $template, string $toEmail, string $subject, string $html): bool
{
    $dir = APP_ROOT . MAIL_PREVIEW_DIR;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        log_write('warning', 'mail: preview dir not writable', ['template' => $template]);
        return false;
    }
    $file = $dir . '/' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '-' . $template . '.html';
    $banner = '<!-- to: ' . e($toEmail) . ' | subject: ' . e($subject) . ' -->' . "\n";
    if (@file_put_contents($file, $banner . $html, LOCK_EX) === false) {
        log_write('warning', 'mail: preview write failed', ['template' => $template]);
        return false;
    }
    log_write('info', 'mail: transport unavailable, preview written', ['template' => $template, 'file' => basename($file)]);
    return true;
}

function mail_send_smtp(string $toEmail, string $toName, string $subject, string $html, string $text, array $replyTo): bool
{
    $mailer = mail_build($toEmail, $toName, $subject, $html, $text, $replyTo);
    return $mailer->send();
}

function mail_build(string $toEmail, string $toName, string $subject, string $html, string $text, array $replyTo): PHPMailer\PHPMailer\PHPMailer
{
    foreach (mail_vendor_files() as $file) {
        require_once $file;
    }
    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->SMTPAuth = true;
    $mailer->Host = (string) config('smtp.host', '');
    $mailer->Port = (int) config('smtp.port', 587);
    $secure = strtolower(trim((string) config('smtp.secure', 'tls')));
    $mailer->SMTPSecure = $secure === 'ssl' ? 'ssl' : ($secure === 'tls' ? 'tls' : '');
    $mailer->SMTPAutoTLS = $secure !== '';
    $mailer->Username = (string) config('smtp.user', '');
    $mailer->Password = (string) config('smtp.pass', '');
    $mailer->Timeout = MAIL_SMTP_TIMEOUT;
    $mailer->CharSet = 'UTF-8';
    $mailer->Encoding = 'base64';

    $fromName = trim((string) config('smtp.from_name', ''));
    if ($fromName === '') {
        $fromName = (string) setting('store_name', 'Sky Fragrances');
    }
    $mailer->setFrom($mailer->Username, $fromName, false);
    if ($replyTo[0] !== '') {
        $mailer->addReplyTo($replyTo[0], $replyTo[1]);
    }
    $mailer->addAddress($toEmail, $toName);
    $mailer->isHTML(true);
    $mailer->Subject = $subject;
    $mailer->Body = $html;
    $mailer->AltBody = $text;
    return $mailer;
}

function mail_transport_selftest(): array
{
    if (!mail_transport_available()) {
        return ['ok' => false, 'detail' => mail_preview_mode() ? 'PHPMailer or the SMTP host is missing; emails are written to storage/logs/mail-preview instead.' : 'PHPMailer or the SMTP host is missing; emails stay queued in the outbox until SMTP is configured.'];
    }
    try {
        $mailer = mail_build('selftest@example.com', 'Self test', 'Self test', '<p>Self test</p>', 'Self test', ['', '']);
        $mailer->Timeout = MAIL_SMTP_TIMEOUT;
        return $mailer->preSend()
            ? ['ok' => true, 'detail' => 'PHPMailer ' . PHPMailer\PHPMailer\PHPMailer::VERSION . ' built a message; SMTP timeout ' . MAIL_SMTP_TIMEOUT . ' s.']
            : ['ok' => false, 'detail' => $mailer->ErrorInfo];
    } catch (Throwable $e) {
        return ['ok' => false, 'detail' => $e->getMessage()];
    }
}

function mail_queue(string $template, array $data, string $toEmail, string $toName, string $subject, ?int $orderId = null, int $attempts = 0, string $lastError = ''): int
{
    $html = mail_render($template, $data, $subject);
    $now = now_karachi();
    return db_insert('email_outbox', [
        'template' => mb_substr($template, 0, 40),
        'recipient' => mb_substr(trim($toEmail), 0, 190),
        'subject' => mb_substr($subject, 0, 190),
        'body_html' => $html,
        'order_id' => $orderId,
        'status' => 'queued',
        'attempts' => $attempts,
        'last_error' => $lastError === '' ? null : mb_substr($lastError, 0, 255),
        'next_try_at' => $attempts === 0 ? $now : mail_outbox_next_try($attempts),
        'created_at' => $now,
    ]);
}

function mail_deliver(string $template, array $data, string $toEmail, string $toName, string $subject, ?int $orderId = null): bool
{
    $toEmail = trim($toEmail);
    if (filter_var($toEmail, FILTER_VALIDATE_EMAIL) === false) {
        log_write('warning', 'mail: invalid recipient', ['template' => $template]);
        return false;
    }
    if (!mail_transport_available()) {
        if (mail_preview_mode()) {
            return mail_send($template, $data, $toEmail, $toName, $subject);
        }
        mail_queue($template, $data, $toEmail, $toName, $subject, $orderId, 0, 'SMTP not configured');
        return false;
    }
    try {
        $html = mail_render($template, $data, $subject);
        if (mail_send_smtp($toEmail, $toName, $subject, $html, mail_text_alternative($html), mail_reply_to($data))) {
            return true;
        }
        $error = 'send() returned false';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    log_write('warning', 'mail: inline send failed, queued', ['template' => $template, 'error' => $error]);
    mail_queue($template, $data, $toEmail, $toName, $subject, $orderId, 1, $error);
    return false;
}

function mail_outbox_next_try(int $attempts): string
{
    return date('Y-m-d H:i:s', time() + 5 * (2 ** min($attempts, 8)) * 60);
}

function mail_outbox_lease(int $id): bool
{
    $now = now_karachi();
    return db_query(
        "UPDATE email_outbox SET next_try_at = :lease WHERE id = :id AND status = 'queued' AND next_try_at <= :now",
        ['lease' => date('Y-m-d H:i:s', time() + MAIL_OUTBOX_LEASE_SECONDS), 'id' => $id, 'now' => $now]
    )->rowCount() === 1;
}

function mail_outbox_drain(int $maxSends = MAIL_DRAIN_MAX_SENDS, int $budgetSeconds = MAIL_DRAIN_BUDGET_SECONDS): array
{
    $result = ['sent' => 0, 'failed' => 0, 'retried' => 0];
    if (!mail_transport_available()) {
        return $result;
    }
    $started = microtime(true);
    $rows = db_fetch_all(
        'SELECT id, recipient, subject, body_html, attempts FROM email_outbox WHERE status = :status AND next_try_at <= :now ORDER BY next_try_at ASC, id ASC LIMIT ' . max(1, $maxSends),
        ['status' => 'queued', 'now' => now_karachi()]
    );
    foreach ($rows as $row) {
        if (microtime(true) - $started > $budgetSeconds - MAIL_SMTP_TIMEOUT) {
            break;
        }
        if (!mail_outbox_lease((int) $row['id'])) {
            continue;
        }
        $attempts = (int) $row['attempts'] + 1;
        try {
            $ok = mail_send_smtp((string) $row['recipient'], '', (string) $row['subject'], (string) $row['body_html'], mail_text_alternative((string) $row['body_html']), mail_reply_to([]));
            $error = $ok ? '' : 'send() returned false';
        } catch (Throwable $e) {
            $ok = false;
            $error = $e->getMessage();
        }
        if ($ok) {
            db_update('email_outbox', ['status' => 'sent', 'attempts' => $attempts, 'last_error' => null, 'sent_at' => now_karachi()], ['id' => (int) $row['id']]);
            $result['sent']++;
            continue;
        }
        $exhausted = $attempts >= MAIL_OUTBOX_MAX_ATTEMPTS;
        db_update('email_outbox', [
            'status' => $exhausted ? 'failed' : 'queued',
            'attempts' => $attempts,
            'last_error' => mb_substr($error, 0, 255),
            'next_try_at' => mail_outbox_next_try($attempts),
        ], ['id' => (int) $row['id']]);
        $result[$exhausted ? 'failed' : 'retried']++;
        log_write('warning', 'mail: outbox send failed', ['id' => (int) $row['id'], 'attempts' => $attempts, 'error' => $error]);
    }
    return $result;
}

function mail_outbox_counts(): array
{
    $rows = db_fetch_all("SELECT status, COUNT(*) AS n FROM email_outbox WHERE status IN ('queued', 'failed') GROUP BY status");
    $counts = ['queued' => 0, 'failed' => 0];
    foreach ($rows as $row) {
        $counts[(string) $row['status']] = (int) $row['n'];
    }
    return $counts;
}

function mail_outbox_stub_old_bodies(): int
{
    return db_query(
        "UPDATE email_outbox SET body_html = '' WHERE status = 'sent' AND sent_at < :before AND body_html <> ''",
        ['before' => date('Y-m-d H:i:s', time() - MAIL_OUTBOX_BODY_RETENTION_DAYS * 86400)]
    )->rowCount();
}

function mail_button(string $url, string $label): string
{
    return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0;"><tr><td style="background:#0a0a0a;border:1px solid #d4b084;">'
        . '<a href="' . e($url) . '" style="display:inline-block;padding:12px 28px;color:#d4b084;font-size:13px;letter-spacing:2px;text-transform:uppercase;text-decoration:none;">' . e($label) . '</a>'
        . '</td></tr></table>';
}

function mail_row(string $label, string $value, bool $strong = false): string
{
    $weight = $strong ? 'font-weight:bold;font-size:17px;' : '';
    return '<tr><td style="padding:6px 0;color:#6b6257;font-size:14px;">' . e($label) . '</td>'
        . '<td align="right" style="padding:6px 0;font-size:14px;' . $weight . '">' . e($value) . '</td></tr>';
}

function mail_items_table(array $items): string
{
    $rows = '';
    foreach ($items as $item) {
        $name = (string) ($item['product_name'] ?? '');
        $size = (string) ($item['size_label'] ?? '');
        $qty = (string) (int) ($item['quantity'] ?? 0);
        $unit = (string) ($item['unit_price_charged'] ?? '0.00');
        $line = (string) ($item['line_total'] ?? '0.00');
        $rows .= '<tr>'
            . '<td style="padding:10px 0;border-bottom:1px solid #eee4d6;font-size:14px;">' . e($name)
            . ($size !== '' ? '<div style="color:#6b6257;font-size:12px;">' . e($size) . '</div>' : '') . '</td>'
            . '<td align="center" style="padding:10px 8px;border-bottom:1px solid #eee4d6;font-size:14px;">' . e($qty) . '</td>'
            . '<td align="right" style="padding:10px 0;border-bottom:1px solid #eee4d6;font-size:14px;white-space:nowrap;">' . e(money($unit)) . '</td>'
            . '<td align="right" style="padding:10px 0;border-bottom:1px solid #eee4d6;font-size:14px;white-space:nowrap;">' . e(money($line)) . '</td>'
            . '</tr>';
    }
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;">'
        . '<tr><th align="left" style="padding:6px 0;border-bottom:2px solid #0a0a0a;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#6b6257;">Item</th>'
        . '<th align="center" style="padding:6px 8px;border-bottom:2px solid #0a0a0a;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#6b6257;">Qty</th>'
        . '<th align="right" style="padding:6px 0;border-bottom:2px solid #0a0a0a;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#6b6257;">Price</th>'
        . '<th align="right" style="padding:6px 0;border-bottom:2px solid #0a0a0a;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#6b6257;">Total</th></tr>'
        . $rows . '</table>';
}

function mail_totals_table(array $order): string
{
    $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 16px;">'
        . mail_row('Subtotal', money((string) ($order['subtotal'] ?? '0.00')));
    $discount = (string) ($order['discount_total'] ?? '0.00');
    if (money_paisa($discount) > 0) {
        $code = trim((string) ($order['coupon_code'] ?? ''));
        $html .= mail_row('Discount' . ($code !== '' ? ' (' . $code . ')' : ''), '- ' . money($discount));
    }
    $shipping = (string) ($order['shipping_fee'] ?? '0.00');
    $html .= mail_row('Shipping', money_paisa($shipping) > 0 ? money($shipping) : 'Free');
    $cod = (string) ($order['cod_fee'] ?? '0.00');
    if (money_paisa($cod) > 0) {
        $html .= mail_row('COD fee', money($cod));
    }
    $html .= mail_row('Total', money((string) ($order['grand_total'] ?? '0.00')), true);
    return $html . '</table>';
}

function mail_payment_method_label(string $method): string
{
    return match ($method) {
        'cod' => 'Cash on Delivery',
        'bank' => 'Bank Transfer',
        'jazzcash' => 'JazzCash',
        'easypaisa' => 'Easypaisa',
        default => ucfirst($method),
    };
}

function mail_payment_account_lines(string $method): array
{
    return match ($method) {
        'bank' => array_filter([
            'Bank' => (string) setting('bank_name', ''),
            'Account title' => (string) setting('bank_account_title', ''),
            'Account number' => (string) setting('bank_account_number', ''),
            'IBAN' => (string) setting('bank_iban', ''),
        ]),
        'jazzcash' => array_filter([
            'Account title' => (string) setting('jazzcash_account_title', ''),
            'JazzCash number' => (string) setting('jazzcash_number', ''),
        ]),
        'easypaisa' => array_filter([
            'Account title' => (string) setting('easypaisa_account_title', ''),
            'Easypaisa number' => (string) setting('easypaisa_number', ''),
        ]),
        default => [],
    };
}

function mail_status_sentence(string $status): string
{
    return match ($status) {
        'confirmed' => 'Your order is confirmed and will be dispatched within ' . (string) setting('delivery_time', '2–4 working days') . '.',
        'packing' => 'Your order is being packed with care.',
        'shipped' => 'Your order is on its way.',
        'delivered' => 'Your order has been delivered. We hope you love it.',
        'cancelled' => 'Your order has been cancelled. Nothing is owed.',
        default => 'Your order is being processed.',
    };
}

function mail_whatsapp_url(): string
{
    $number = preg_replace('/\D+/', '', (string) setting('whatsapp', '')) ?? '';
    return $number === '' ? '' : 'https://wa.me/' . $number;
}
