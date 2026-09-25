<?php
defined('SKYFR') || exit;

require_once __DIR__ . '/catalogue.php';

const MESSAGES_STATUSES = ['new', 'read', 'replied', 'archived'];
const MESSAGES_STATUS_LABELS = ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'archived' => 'Archived'];

function messages_find(int $id): ?array
{
    return db_fetch('SELECT id, name, email, phone, subject, order_number, message, status, admin_note, created_at, read_at FROM contact_messages WHERE id = :id', ['id' => $id]);
}

function messages_whatsapp_url(array $message): string
{
    $phone = trim((string) ($message['phone'] ?? ''));
    if ($phone === '') {
        return '';
    }
    $digits = ltrim(phone_normalize($phone), '+');
    if (strlen($digits) < 11) {
        return '';
    }
    $template = (string) setting('whatsapp_reply_template', 'Assalam o Alaikum {name}, thank you for contacting Sky Fragrances.');
    $firstName = trim(explode(' ', trim((string) $message['name']))[0] ?? '');
    $text = str_replace(['{name}', '{subject}', '{order}'], [$firstName === '' ? 'there' : $firstName, (string) ($message['subject'] ?? ''), (string) ($message['order_number'] ?? '')], $template);
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode(trim($text));
}

function messages_mailto_url(array $message): string
{
    $email = trim((string) ($message['email'] ?? ''));
    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return '';
    }
    $subject = trim((string) ($message['subject'] ?? ''));
    $subject = 'Re: ' . ($subject === '' ? 'Your message to Sky Fragrances' : $subject);
    return 'mailto:' . rawurlencode($email) . '?subject=' . rawurlencode($subject);
}

function messages_order_url(array $message): string
{
    $number = strtoupper(trim((string) ($message['order_number'] ?? '')));
    return preg_match('/^SF-\d{6}-[A-HJ-NP-Z2-9]{4}$/', $number) === 1 ? '/admin/orders/' . $number : '';
}

function messages_return_url(): string
{
    return request_return_path('/admin/messages');
}

$routeName = (string) ($route['name'] ?? '');
$now = now_karachi();

if ($routeName === 'admin.messages.status') {
    $message = messages_find((int) $params['id']);
    if ($message === null) {
        flash('error', 'That message no longer exists.');
        redirect('/admin/messages', 303);
    }
    $to = (string) request_post('to', '');
    if ($to === 'delete') {
        if ((string) request_post('confirm_word', '') !== 'DELETE') {
            flash('error', 'Type DELETE to confirm removing the message from ' . $message['name'] . '.');
            redirect('/admin/messages/' . (int) $message['id'], 303);
        }
        db_delete('contact_messages', ['id' => (int) $message['id']]);
        catalogue_log('message', (int) $message['id'], 'message.delete', 'Deleted message from ' . $message['name']);
        flash('success', 'Message from ' . $message['name'] . ' deleted.');
        redirect('/admin/messages', 303);
    }
    if (!in_array($to, MESSAGES_STATUSES, true)) {
        flash('error', 'Choose a status for this message.');
        redirect('/admin/messages/' . (int) $message['id'], 303);
    }
    $data = ['status' => $to];
    if ($to === 'new') {
        $data['read_at'] = null;
    } elseif ($message['read_at'] === null) {
        $data['read_at'] = $now;
    }
    $noteChanged = false;
    if (request_post('admin_note') !== null) {
        $note = catalogue_text(request_post('admin_note'), 2000);
        if ((string) ($note ?? '') !== (string) ($message['admin_note'] ?? '')) {
            $data['admin_note'] = $note;
            $noteChanged = true;
        }
    }
    db_update('contact_messages', $data, ['id' => (int) $message['id']]);
    $statusChanged = $to !== $message['status'];
    if ($statusChanged || $noteChanged) {
        catalogue_log('message', (int) $message['id'], 'message.' . $to, ($statusChanged ? 'Marked ' . $to : 'Note updated') . ' — message from ' . $message['name'], ['status' => $message['status']], ['status' => $to, 'note' => $noteChanged ? '(changed)' : null]);
    }
    if ($statusChanged) {
        flash('success', match ($to) {
            'replied' => 'Marked as replied. ' . $message['name'] . ' has been answered.',
            'archived' => 'Message from ' . $message['name'] . ' archived.',
            'new' => 'Message from ' . $message['name'] . ' is back in New.',
            default => 'Marked as read.',
        });
    } elseif ($noteChanged) {
        flash('success', 'Note saved.');
    } else {
        flash('info', 'No changes to save.');
    }
    $return = (string) request_post('return', '');
    redirect(preg_match('~^/admin/messages(?:\?[^#\s]*)?$~', $return) ? $return : '/admin/messages/' . (int) $message['id'], 303);
}

if ($routeName === 'admin.messages.show') {
    $message = messages_find((int) $params['id']);
    if ($message === null) {
        flash('error', 'That message no longer exists.');
        redirect('/admin/messages', 303);
    }
    if ($message['status'] === 'new') {
        db_update('contact_messages', ['status' => 'read', 'read_at' => $now], ['id' => (int) $message['id'], 'status' => 'new']);
        $message['status'] = 'read';
        $message['read_at'] = $now;
    }
    render_admin('message-detail.php', [
        'message' => $message,
        'whatsappUrl' => messages_whatsapp_url($message),
        'mailtoUrl' => messages_mailto_url($message),
        'orderUrl' => messages_order_url($message),
        'statusLabels' => MESSAGES_STATUS_LABELS,
    ], ['title' => 'Message from ' . $message['name'], 'body_class' => (string) $route['body_class'], 'back' => '/admin/messages']);
}

$per = max(5, min(100, setting_int('admin_rows_per_page', 20)));
$page = max(1, (int) request_query('page', 1));
$statusFilter = (string) request_query('status', 'new');
if (!in_array($statusFilter, ['new', 'read', 'replied', 'archived', 'all'], true)) {
    $statusFilter = 'new';
}
$q = mb_substr(trim((string) request_query('q', '')), 0, 60);
$where = ['1 = 1'];
$bind = [];
if ($statusFilter !== 'all') {
    $where[] = 'status = :status';
    $bind['status'] = $statusFilter;
}
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $bind['q1'] = $bind['q2'] = $bind['q3'] = $bind['q4'] = $bind['q5'] = $like;
    $search = ['name LIKE :q1', 'email LIKE :q2', 'phone LIKE :q3', 'subject LIKE :q4', 'message LIKE :q5'];
    $digits = preg_replace('/\D+/', '', $q) ?? '';
    if (strlen($digits) >= 4) {
        $bind['q6'] = '%' . $digits . '%';
        $search[] = "REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') LIKE :q6";
    }
    $where[] = '(' . implode(' OR ', $search) . ')';
}
$whereSql = ' WHERE ' . implode(' AND ', $where);
$total = (int) db_fetch_column('SELECT COUNT(*) FROM contact_messages' . $whereSql, $bind);
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);
$messages = db_fetch_all('SELECT id, name, email, phone, subject, order_number, message, status, created_at FROM contact_messages' . $whereSql . ' ORDER BY created_at DESC, id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $bind);

$counts = ['all' => 0];
foreach (db_fetch_all('SELECT status, COUNT(*) AS n FROM contact_messages GROUP BY status') as $row) {
    $counts[(string) $row['status']] = (int) $row['n'];
    $counts['all'] += (int) $row['n'];
}
foreach (MESSAGES_STATUSES as $statusKey) {
    $counts[$statusKey] = $counts[$statusKey] ?? 0;
}
$query = array_filter(['status' => $statusFilter === 'new' ? '' : $statusFilter, 'q' => $q], static fn ($v): bool => $v !== '');
$tabs = [];
foreach (MESSAGES_STATUS_LABELS + ['all' => 'All'] as $key => $label) {
    $tabQuery = $query;
    unset($tabQuery['status']);
    if ($key !== 'new') {
        $tabQuery = ['status' => $key] + $tabQuery;
    }
    $tabs[] = ['label' => $label, 'href' => '/admin/messages' . ($tabQuery === [] ? '' : '?' . http_build_query($tabQuery)), 'active' => $statusFilter === $key, 'count' => $counts[$key]];
}
$activeChips = $q === '' ? [] : [['label' => 'Search: ' . $q, 'remove_url' => '/admin/messages' . ($statusFilter === 'new' ? '' : '?status=' . $statusFilter)]];

render_admin('messages.php', [
    'messages' => $messages,
    'total' => $total,
    'page' => $page,
    'per' => $per,
    'filters' => ['status' => $statusFilter, 'q' => $q],
    'query' => $query,
    'tabs' => $tabs,
    'counts' => $counts,
    'activeChips' => $activeChips,
    'returnTo' => '/admin/messages' . ($query === [] ? '' : '?' . http_build_query($query)),
], ['title' => 'Messages', 'body_class' => (string) $route['body_class']]);
