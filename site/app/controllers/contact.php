<?php
defined('SKYFR') || exit;

require_once APP_ROOT . '/app/lib/orders.php';

const CONTACT_SUBJECTS = ['Order enquiry', 'Product question', 'Return or exchange', 'Wholesale', 'Other'];
const CONTACT_SUCCESS = "Thanks — we've got your message and will reply within one working day.";

$old = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'order_number' => '', 'message' => ''];
$errors = [];

if (request_method() === 'POST') {
    foreach ($old as $key => $unused) {
        $old[$key] = trim(request_string($key, ''));
    }
    $old['order_number'] = strtoupper($old['order_number']);
    $trap = form_trap_status();
    if ($trap === 'bot') {
        form_trap_log('contact', $trap);
        flash('success', CONTACT_SUCCESS);
        redirect('/contact#sent');
    }
    if ($trap === 'expired') {
        $errors['form'] = 'This form expired. Please try again.';
    }
    if (rate_limit_over('contact', request_ip_hash(), 5, 3600)) {
        $errors['form'] = 'Too many messages from this connection. Please wait an hour, or message us on WhatsApp.';
    }
    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 60 || !preg_match('/\p{L}/u', $old['name'])) {
        $errors['name'] = 'Please enter your name.';
    }
    if ($old['email'] === '' || mb_strlen($old['email']) > 120 || filter_var($old['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Please enter a valid email address so we can reply.';
    }
    $phoneNormalized = null;
    if ($old['phone'] !== '') {
        $phoneNormalized = phone_normalize($old['phone']);
        if (!preg_match('/^\+923\d{9}$/', $phoneNormalized)) {
            $errors['phone'] = 'Enter a valid Pakistani mobile number, like 0300 1234567.';
        }
    }
    if (!in_array($old['subject'], CONTACT_SUBJECTS, true)) {
        $errors['subject'] = 'Please choose a subject.';
    }
    if ($old['order_number'] !== '' && !order_number_is_wellformed($old['order_number'])) {
        $errors['order_number'] = 'That order number does not look right. It looks like SF-260925-K7QF.';
    }
    if (mb_strlen($old['message']) < 10 || mb_strlen($old['message']) > 2000) {
        $errors['message'] = 'Please write your message (10 to 2000 characters).';
    }
    if ($errors === []) {
        rate_limit_record('contact', request_ip_hash(), true);
        $email = mb_strtolower($old['email']);
        $autoreplyAllowed = notify_contact_autoreply_allowed($email);
        $row = [
            'name' => $old['name'],
            'email' => $email,
            'phone' => $old['phone'] !== '' ? mb_substr($old['phone'], 0, 32) : null,
            'subject' => $old['subject'],
            'order_number' => $old['order_number'] !== '' ? $old['order_number'] : null,
            'message' => $old['message'],
            'status' => 'new',
            'ip_hash' => request_ip_hash(),
            'created_at' => now_karachi(),
        ];
        db_insert('contact_messages', $row);
        notify_contact($row, $autoreplyAllowed);
        flash('success', CONTACT_SUCCESS);
        redirect('/contact#sent');
    }
    rate_limit_record('contact', request_ip_hash(), false);
    http_status(422);
    $head['robots'] = 'noindex,follow';
}

$head['title'] = 'Contact Us | Sky Fragrances';
$head['meta_description'] = 'Get in touch with Sky Fragrances by WhatsApp, phone or our contact form.';
$head['canonical'] = canonical('/contact');

render($route['view'], [
    'heading' => 'Contact Us',
    'old' => $old,
    'errors' => $errors,
    'subjects' => CONTACT_SUBJECTS,
    'trapField' => form_trap_field(),
    'whatsapp' => setting('whatsapp', ''),
    'whatsappUrl' => order_whatsapp_url('Assalam-o-Alaikum! I have a question about Sky Fragrances.'),
    'contactPhone' => setting('contact_phone', ''),
    'contactEmail' => setting('contact_email', ''),
    'addressLine' => setting('address_line', ''),
    'businessHours' => setting('business_hours', ''),
], $head);
