<?php
defined('SKYFR') || exit;

const SETTINGS_TABS = ['store' => 'Store', 'contact' => 'Contact & Social', 'home' => 'Home', 'shipping' => 'Shipping', 'payments' => 'Payments', 'seo' => 'SEO', 'advanced' => 'Advanced'];
const SETTINGS_IMAGE_RULES = [
    'logo_path' => ['max' => 1200, 'cover' => null],
    'favicon_path' => ['max' => 512, 'cover' => null],
    'hero_image_desktop' => ['max' => 2400, 'cover' => null],
    'hero_image_mobile' => ['max' => 1600, 'cover' => null],
    'og_default_image' => ['max' => 1200, 'cover' => [1200, 630]],
];
const SETTINGS_SECRET_KEYS = ['bank_account_number', 'bank_iban', 'jazzcash_number', 'easypaisa_number', 'maintenance_bypass', 'contact_phone', 'whatsapp', 'contact_email', 'order_notify_email'];
const SETTINGS_WORDING_KEYS = ['hero_trust_line', 'newsletter_heading', 'newsletter_text', 'quiz_band_text', 'cod_note', 'manual_payment_note', 'maintenance_message', 'whatsapp_reply_template', 'contact_reply_time', 'returns_days', 'footer_blurb'];

function settings_definitions(): array
{
    $d = [
        'store_name' => ['store', 'text', 'Store name', ['required' => true, 'maxlength' => 80]],
        'store_tagline' => ['store', 'text', 'Tagline', ['maxlength' => 120]],
        'logo_path' => ['store', 'image', 'Logo', ['help' => 'PNG with a transparent background looks best. Shown in the header, emails and invoices.']],
        'favicon_path' => ['store', 'image', 'Favicon', ['help' => 'Square PNG, at least 64×64.']],
        'address_line' => ['store', 'text', 'Business address', ['maxlength' => 160, 'help' => 'Printed on invoices and the contact page.']],
        'footer_blurb' => ['store', 'textarea', 'Footer blurb', ['maxlength' => 240, 'rows' => 3]],
        'currency_prefix' => ['store', 'text', 'Currency prefix', ['maxlength' => 6, 'required' => true]],
        'timezone' => ['store', 'readonly', 'Timezone', ['help' => 'All dates in the panel and in emails use Pakistan time.']],
        'contact_phone' => ['contact', 'phone', 'Phone number', ['help' => 'Shown to customers exactly as typed.']],
        'whatsapp' => ['contact', 'phone', 'WhatsApp number', ['help' => 'Saved as +92XXXXXXXXXX so the chat links work.']],
        'contact_email' => ['contact', 'email', 'Contact email', ['help' => 'Customers see this and replies go here.']],
        'order_notify_email' => ['contact', 'email', 'Order notifications go to', ['help' => 'Leave blank to use the contact email.']],
        'business_hours' => ['contact', 'text', 'Business hours', ['maxlength' => 120]],
        'contact_reply_time' => ['contact', 'text', 'Reply time promised to customers', ['maxlength' => 60, 'help' => 'Finishes the line "we reply within …" in the email a customer gets after writing to you. Something like "one working day".']],
        'instagram_url' => ['contact', 'url', 'Instagram URL', []],
        'instagram_handle' => ['contact', 'text', 'Instagram handle', ['maxlength' => 40]],
        'facebook_url' => ['contact', 'url', 'Facebook URL', []],
        'tiktok_url' => ['contact', 'url', 'TikTok URL', []],
        'youtube_url' => ['contact', 'url', 'YouTube URL', []],
        'whatsapp_reply_template' => ['contact', 'textarea', 'WhatsApp reply template', ['maxlength' => 400, 'rows' => 3, 'help' => '{name} becomes the customer name.']],
        'announcement_enabled' => ['home', 'bool', 'Show the announcement bar', []],
        'announcement_text' => ['home', 'text', 'Announcement text', ['maxlength' => 120]],
        'announcement_link' => ['home', 'url', 'Announcement link', ['relative' => true, 'help' => 'A page on this site (/shop) or an https:// link.']],
        'hero_heading' => ['home', 'text', 'Hero heading', ['maxlength' => 80]],
        'hero_subheading' => ['home', 'text', 'Hero subheading', ['maxlength' => 200]],
        'hero_cta_label' => ['home', 'text', 'Hero button label', ['maxlength' => 40]],
        'hero_cta_url' => ['home', 'url', 'Hero button link', ['relative' => true]],
        'hero_image_desktop' => ['home', 'image', 'Hero image (desktop)', ['help' => 'Wide, at least 1600 px across.']],
        'hero_image_mobile' => ['home', 'image', 'Hero image (mobile)', ['help' => 'Tall, at least 900 px high.']],
        'hero_trust_line' => ['home', 'text', 'Trust line under the hero', ['maxlength' => 80]],
        'home_bestsellers_count' => ['home', 'int', 'Best sellers shown', ['min' => 4, 'max' => 12]],
        'home_new_count' => ['home', 'int', 'New arrivals shown', ['min' => 4, 'max' => 12]],
        'home_reviews_enabled' => ['home', 'bool', 'Show reviews on the home page', []],
        'quiz_band_enabled' => ['home', 'bool', 'Show the Scent Finder band', []],
        'quiz_band_heading' => ['home', 'text', 'Scent Finder heading', ['maxlength' => 80]],
        'quiz_band_text' => ['home', 'textarea', 'Scent Finder text', ['maxlength' => 240, 'rows' => 2]],
        'gender_tile_men_image' => ['home', 'image', 'For Him tile image', []],
        'gender_tile_women_image' => ['home', 'image', 'For Her tile image', []],
        'gender_tile_unisex_image' => ['home', 'image', 'Unisex tile image', []],
        'newsletter_heading' => ['home', 'text', 'Newsletter heading', ['maxlength' => 80]],
        'newsletter_text' => ['home', 'textarea', 'Newsletter text', ['maxlength' => 240, 'rows' => 2]],
        'newsletter_cta_label' => ['home', 'text', 'Newsletter button label', ['maxlength' => 30]],
        'shipping_fee' => ['shipping', 'money', 'Delivery fee', ['required' => true]],
        'free_shipping_threshold' => ['shipping', 'money', 'Free delivery from', ['required' => true, 'help' => '0 means delivery is always free. Tested on the order subtotal before any coupon.']],
        'delivery_time' => ['shipping', 'text', 'Delivery time shown to customers', ['maxlength' => 60, 'required' => true]],
        'returns_days' => ['shipping', 'int', 'Exchange window in days', ['min' => 1, 'max' => 60, 'help' => 'How many days after delivery a sealed bottle can be exchanged. Quoted in the order and delivery emails; keep it in step with the Returns page.']],
        'cod_enabled' => ['payments', 'bool', 'Cash on delivery', []],
        'cod_max_total' => ['payments', 'money', 'COD limit', ['help' => '0 means no limit. Above this amount customers must pay in advance.']],
        'manual_hold_hours' => ['payments', 'int', 'Hours to hold an unpaid transfer order', ['min' => 6, 'max' => 240, 'help' => 'Unpaid bank, JazzCash and Easypaisa orders older than this get an amber badge and can be cancelled in one go from Orders.']],
        'cod_note' => ['payments', 'textarea', 'COD note at checkout', ['maxlength' => 240, 'rows' => 2]],
        'bank_enabled' => ['payments', 'bool', 'Bank transfer', []],
        'bank_name' => ['payments', 'text', 'Bank name', ['maxlength' => 80]],
        'bank_account_title' => ['payments', 'text', 'Account title', ['maxlength' => 120]],
        'bank_account_number' => ['payments', 'text', 'Account number', ['maxlength' => 40]],
        'bank_iban' => ['payments', 'text', 'IBAN', ['maxlength' => 34]],
        'jazzcash_enabled' => ['payments', 'bool', 'JazzCash', []],
        'jazzcash_account_title' => ['payments', 'text', 'JazzCash account title', ['maxlength' => 120]],
        'jazzcash_number' => ['payments', 'text', 'JazzCash number', ['maxlength' => 40]],
        'easypaisa_enabled' => ['payments', 'bool', 'Easypaisa', []],
        'easypaisa_account_title' => ['payments', 'text', 'Easypaisa account title', ['maxlength' => 120]],
        'easypaisa_number' => ['payments', 'text', 'Easypaisa number', ['maxlength' => 40]],
        'manual_payment_note' => ['payments', 'textarea', 'Note shown for transfer payments', ['maxlength' => 400, 'rows' => 3]],
        'meta_description' => ['seo', 'textarea', 'Site meta description', ['maxlength' => 155, 'rows' => 3, 'help' => 'What Google shows under the shop name. Up to 155 characters.']],
        'og_default_image' => ['seo', 'image', 'Default share image', ['help' => 'Cropped to 1200×630 for WhatsApp and Facebook previews.']],
        'google_verification' => ['seo', 'text', 'Google site verification code', ['maxlength' => 120]],
        'gsc_analytics_id' => ['seo', 'text', 'Analytics ID', ['maxlength' => 40, 'help' => 'Leave blank unless you run analytics.']],
        'low_stock_threshold' => ['advanced', 'int', 'Low-stock alert at', ['min' => 0, 'max' => 999, 'help' => '0 turns the alerts off. Sold-out handling is never off.']],
        'admin_rows_per_page' => ['advanced', 'int', 'Rows per page in lists', ['min' => 10, 'max' => 100]],
        'site_indexable' => ['advanced', 'bool', 'Let search engines index the shop', ['help' => 'Keep off on a preview copy. Turn on once the shop is live at its real address.']],
        'maintenance_mode' => ['advanced', 'bool', 'Maintenance mode', ['help' => 'Customers see a closed page; the panel keeps working. If the panel is unreachable, create an empty file named MAINTENANCE inside storage/.']],
        'maintenance_message' => ['advanced', 'textarea', 'Maintenance message', ['maxlength' => 400, 'rows' => 3]],
        'maintenance_bypass' => ['advanced', 'secret', 'Preview key during maintenance', ['help' => 'Type this on the closed page to see the shop while it is off.']],
        'base_url' => ['advanced', 'baseurl', 'Site address', ['help' => 'Used for links in emails, the sitemap and share previews. Leave blank to use the installed address.']],
        'trusted_proxies' => ['advanced', 'cidr', 'Trusted proxies', ['help' => 'Comma-separated IPs or ranges. Change only if the setup guide tells you to.']],
        'https_permanent' => ['advanced', 'https', 'HTTPS redirect', []],
        'images_webp_enabled' => ['advanced', 'capability', 'WebP images', []],
        'install_completed_at' => ['advanced', 'readonly', 'Installed on', []],
    ];
    $tiles = [];
    for ($i = 1; $i <= 6; $i++) {
        $tiles['instagram_tile_' . $i . '_image'] = ['home', 'image', 'Instagram tile ' . $i . ' image', []];
        $tiles['instagram_tile_' . $i . '_url'] = ['home', 'url', 'Instagram tile ' . $i . ' link', []];
    }
    $out = [];
    foreach ($d + $tiles as $key => [$tab, $type, $label, $opts]) {
        $out[$key] = ['tab' => $tab, 'type' => $type, 'label' => $label] + $opts;
    }
    return $out;
}

function settings_tab_keys(string $tab): array
{
    return array_keys(array_filter(settings_definitions(), static fn (array $def): bool => $def['tab'] === $tab));
}

function settings_wording_keys(string $tab): array
{
    $definitions = settings_definitions();
    $defaults = default_copy()['settings'];
    return array_values(array_filter(SETTINGS_WORDING_KEYS, static fn (string $key): bool => isset($definitions[$key]) && $definitions[$key]['tab'] === $tab && !in_array($key, SETTINGS_SECRET_KEYS, true) && isset($defaults[$key])));
}

function settings_current(): array
{
    return array_merge(settings_defaults(), db_fetch_pairs('SELECT setting_key, setting_value FROM settings'));
}

function settings_url_ok(string $value, bool $relative): bool
{
    if ($relative && $value[0] === '/' && !str_starts_with($value, '//')) {
        return true;
    }
    return filter_var($value, FILTER_VALIDATE_URL) !== false && str_starts_with(strtolower($value), 'https://');
}

function settings_cidr_ok(string $entry): bool
{
    $parts = explode('/', $entry, 2);
    if (filter_var($parts[0], FILTER_VALIDATE_IP) === false) {
        return false;
    }
    if (!isset($parts[1])) {
        return true;
    }
    $maxBits = str_contains($parts[0], ':') ? 128 : 32;
    return ctype_digit($parts[1]) && (int) $parts[1] >= 0 && (int) $parts[1] <= $maxBits;
}

function settings_clean(string $key, array $def, mixed $raw): array
{
    $value = trim((string) (is_string($raw) ? $raw : ''));
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';
    $label = $def['label'];
    if ($value === '') {
        return !empty($def['required']) ? [null, $label . ' is required.'] : ['', null];
    }
    switch ($def['type']) {
        case 'text':
        case 'textarea':
            $max = (int) ($def['maxlength'] ?? ($def['type'] === 'textarea' ? 1000 : 160));
            return mb_strlen($value) > $max ? [null, 'Keep ' . $label . ' to ' . $max . ' characters.'] : [$value, null];
        case 'int':
            $int = filter_var($value, FILTER_VALIDATE_INT);
            $min = (int) ($def['min'] ?? 0);
            $max = (int) ($def['max'] ?? PHP_INT_MAX);
            return ($int === false || $int < $min || $int > $max) ? [null, $label . ' must be a whole number between ' . $min . ' and ' . $max . '.'] : [(string) $int, null];
        case 'money':
            $value = str_replace(',', '', $value);
            return preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value) ? [money_from_paisa(money_paisa($value)), null] : [null, $label . ' must be an amount like 250 or 250.50.'];
        case 'email':
            return filter_var($value, FILTER_VALIDATE_EMAIL) ? [mb_strtolower($value), null] : [null, $label . ' is not a valid email address.'];
        case 'phone':
            if (!preg_match('/^[0-9+\s-]{7,20}$/', $value)) {
                return [null, $label . ' may only contain digits, +, spaces and dashes.'];
            }
            return [$key === 'whatsapp' ? phone_normalize($value) : $value, null];
        case 'url':
            return settings_url_ok($value, !empty($def['relative'])) ? [$value, null] : [null, $label . ' must start with https://' . (!empty($def['relative']) ? ' or be a page on this site like /shop' : '') . '.'];
        case 'baseurl':
            $value = rtrim($value, '/');
            return preg_match('#^https?://[a-z0-9.-]+(:\d+)?$#i', $value) ? [strtolower($value), null] : [null, 'Site address must look like https://www.example.com with nothing after the domain.'];
        case 'cidr':
            $entries = array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $s): bool => $s !== ''));
            foreach ($entries as $entry) {
                if (!settings_cidr_ok($entry)) {
                    return [null, $entry . ' is not a valid IP address or range.'];
                }
            }
            return [implode(',', $entries), null];
    }
    return [null, null];
}

function settings_payment_rules(array $values, array &$errors): void
{
    $on = static fn (string $k): bool => ($values[$k] ?? '0') === '1';
    if (!$on('cod_enabled') && !$on('bank_enabled') && !$on('jazzcash_enabled') && !$on('easypaisa_enabled')) {
        $errors['cod_enabled'] = 'At least one payment method must stay on, or nobody can check out.';
    }
    foreach (['bank' => ['bank_account_number', 'bank_account_title'], 'jazzcash' => ['jazzcash_number', 'jazzcash_account_title'], 'easypaisa' => ['easypaisa_number', 'easypaisa_account_title']] as $method => $needs) {
        if (!$on($method . '_enabled')) {
            continue;
        }
        foreach ($needs as $need) {
            if (trim((string) ($values[$need] ?? '')) === '') {
                $errors[$need] = 'Fill this in before turning on ' . mail_payment_method_label($method) . '.';
            }
        }
    }
}

function settings_image_store(string $key, array $file): array
{
    $check = upload_validate($file, UPLOAD_MAX_PRODUCT_BYTES);
    if (!$check['ok']) {
        return [null, $check['error']];
    }
    $src = image_decode((string) $file['tmp_name']);
    if ($src === null) {
        return [null, 'The image could not be read.'];
    }
    $rule = SETTINGS_IMAGE_RULES[$key] ?? ['max' => 1200, 'cover' => null];
    if ($rule['cover'] !== null) {
        $out = image_cover($src, $rule['cover'][0], $rule['cover'][1]);
        $ext = 'jpg';
    } else {
        $scale = min(1, $rule['max'] / max(imagesx($src), imagesy($src)));
        $out = image_cover($src, max(1, (int) round(imagesx($src) * $scale)), max(1, (int) round(imagesy($src) * $scale)));
        $ext = $check['mime'] === 'image/png' ? 'png' : ($check['mime'] === 'image/webp' && image_webp_supported() ? 'webp' : 'jpg');
    }
    imagedestroy($src);
    $dir = APP_ROOT . '/uploads/settings';
    image_ensure_dir($dir);
    $name = preg_replace('/[^a-z0-9_]/', '', $key) . '-' . bin2hex(random_bytes(5)) . '.' . $ext;
    $ok = match ($ext) {
        'png' => imagepng($out, $dir . '/' . $name, 6),
        'webp' => image_write_webp($out, $dir . '/' . $name, 84),
        default => image_write_jpeg($out, $dir . '/' . $name, 85),
    };
    imagedestroy($out);
    return $ok ? ['uploads/settings/' . $name, null] : [null, 'The image could not be saved. Check that uploads/settings is writable.'];
}

function settings_image_remove(string $path): void
{
    if (preg_match('#^uploads/settings/[a-z0-9_]+-[a-f0-9]{10}\.(jpg|png|webp)$#', $path) && is_file(APP_ROOT . '/' . $path)) {
        @unlink(APP_ROOT . '/' . $path);
    }
}

function settings_write(array $changes, string $tab): void
{
    $now = now_karachi();
    db_transaction(static function () use ($changes, $tab, $now): void {
        foreach ($changes as $key => $value) {
            db_query(
                'INSERT INTO settings (setting_key, setting_value, setting_group, updated_at) VALUES (:k, :v, :g, :t) ON DUPLICATE KEY UPDATE setting_value = :v2, setting_group = :g2, updated_at = :t2',
                ['k' => $key, 'v' => $value, 'g' => $tab, 't' => $now, 'v2' => $value, 'g2' => $tab, 't2' => $now]
            );
        }
    });
    settings_cache_clear();
    settings_load();
}

function settings_log(array $admin, string $action, string $summary, ?array $before, ?array $after): void
{
    $mask = static function (?array $set): ?string {
        if ($set === null) {
            return null;
        }
        foreach ($set as $k => $v) {
            $set[$k] = in_array($k, SETTINGS_SECRET_KEYS, true) ? '(changed)' : mb_substr((string) $v, 0, 120);
        }
        return json_encode($set, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    };
    db_insert('admin_activity_log', [
        'admin_id' => (int) $admin['id'],
        'admin_username' => mb_substr((string) $admin['username'], 0, 64),
        'entity_type' => 'setting',
        'entity_id' => null,
        'action' => $action,
        'summary' => mb_substr($summary, 0, 255),
        'before_json' => $mask($before),
        'after_json' => $mask($after),
        'ip_hash' => request_ip_hash(),
        'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        'created_at' => now_karachi(),
    ]);
}

$admin = auth_user();
$definitions = settings_definitions();
$tabInput = request_method() === 'POST' ? request_post('tab', 'store') : request_query('tab', 'store');
$tab = is_string($tabInput) ? $tabInput : 'store';
if (!isset(SETTINGS_TABS[$tab])) {
    $tab = 'store';
}
$tabUrl = '/admin/settings?tab=' . $tab;

if (($route['name'] ?? '') === 'admin.settings.https_permanent') {
    $outcome = https_make_permanent_if_ready(APP_ROOT, request_host());
    if ($outcome['ok'] && $outcome['flipped']) {
        settings_write(['https_permanent' => '1'], 'advanced');
        settings_log($admin, 'setting.https_permanent', 'HTTPS redirect made permanent (301)', null, null);
        flash('success', $outcome['detail']);
    } else {
        flash('error', 'HTTPS was not made permanent. ' . $outcome['detail']);
    }
    redirect('/admin/settings?tab=advanced', 303);
}

if (($route['name'] ?? '') === 'admin.settings.restore_wording') {
    $restoreTab = request_post('tab', '');
    if (!is_string($restoreTab) || !isset(SETTINGS_TABS[$restoreTab])) {
        flash('error', 'Choose a settings tab before restoring its wording.');
        redirect('/admin/settings', 303);
    }
    $keys = settings_wording_keys($tab);
    if ($keys === []) {
        flash('error', 'The ' . SETTINGS_TABS[$tab] . ' tab has no default wording to restore.');
        redirect($tabUrl, 303);
    }
    $stored = db_fetch_pairs('SELECT setting_key, setting_value FROM settings');
    $defaults = default_copy()['settings'];
    $changes = [];
    foreach ($keys as $key) {
        if (!array_key_exists($key, $stored) || (string) $stored[$key] !== (string) $defaults[$key]) {
            $changes[$key] = (string) $defaults[$key];
        }
    }
    if ($changes === []) {
        flash('info', 'The ' . SETTINGS_TABS[$tab] . ' wording already matches the defaults.');
        redirect($tabUrl, 303);
    }
    settings_write($changes, $tab);
    settings_log($admin, 'setting.restore_wording', 'Default wording restored (' . SETTINGS_TABS[$tab] . '): ' . implode(', ', array_keys($changes)), array_intersect_key($stored, $changes), $changes);
    flash('success', count($changes) === 1 ? '1 wording setting restored to its default.' : count($changes) . ' wording settings restored to their defaults.');
    redirect($tabUrl, 303);
}

$current = settings_current();
$errors = [];
$values = [];
foreach (settings_tab_keys($tab) as $key) {
    $values[$key] = (string) ($current[$key] ?? '');
}

if (request_method() === 'POST') {
    $action = (string) request_post('action', 'save');
    if ($action === 'regenerate_bypass') {
        settings_write(['maintenance_bypass' => bin2hex(random_bytes(8))], 'advanced');
        settings_log($admin, 'setting.update', 'Settings changed: maintenance_bypass', null, null);
        flash('success', 'A new preview key was generated.');
        redirect('/admin/settings?tab=advanced', 303);
    }
    if ($action === 'rescan') {
        $webp = function_exists('imagewebp') && (imagetypes() & IMG_WEBP) === IMG_WEBP ? '1' : '0';
        settings_write(['images_webp_enabled' => $webp], 'advanced');
        settings_log($admin, 'setting.update', 'Settings changed: images_webp_enabled', null, null);
        flash('success', $webp === '1' ? 'WebP is available on this server.' : 'WebP is not available; JPEG only.');
        redirect('/admin/settings?tab=advanced', 303);
    }
    $changes = [];
    $removeFiles = [];
    $newFiles = [];
    foreach (settings_tab_keys($tab) as $key) {
        $def = $definitions[$key];
        $old = (string) ($current[$key] ?? '');
        if (in_array($def['type'], ['readonly', 'secret', 'https', 'capability'], true)) {
            continue;
        }
        if ($def['type'] === 'bool') {
            $new = request_post($key) === '1' ? '1' : '0';
        } elseif ($def['type'] === 'image') {
            $file = $_FILES['image_' . $key] ?? null;
            $new = $old;
            if (is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                [$stored, $error] = settings_image_store($key, $file);
                if ($error !== null) {
                    $errors[$key] = $error;
                } else {
                    $new = (string) $stored;
                    $newFiles[] = $new;
                    $removeFiles[] = $old;
                }
            } elseif (request_post('remove_' . $key) === '1') {
                $new = (string) (settings_defaults()[$key] ?? '');
                $removeFiles[] = $old;
            }
        } else {
            $values[$key] = trim((string) (is_string(request_post($key)) ? request_post($key) : ''));
            [$new, $error] = settings_clean($key, $def, request_post($key));
            if ($error !== null) {
                $errors[$key] = $error;
                continue;
            }
        }
        $values[$key] = (string) $new;
        if ((string) $new !== $old) {
            $changes[$key] = (string) $new;
        }
    }
    if ($tab === 'payments') {
        settings_payment_rules($values, $errors);
    }
    if ($errors !== []) {
        foreach ($newFiles as $path) {
            settings_image_remove($path);
        }
        flash('error', 'Nothing was saved. Fix the ' . count($errors) . ' highlighted field' . (count($errors) === 1 ? '' : 's') . ' and try again.');
    } elseif ($changes === []) {
        flash('info', 'No changes to save.');
        redirect($tabUrl, 303);
    } else {
        settings_write($changes, $tab);
        foreach ($removeFiles as $path) {
            settings_image_remove($path);
        }
        $before = array_intersect_key($current, $changes);
        settings_log($admin, 'setting.update', 'Settings changed (' . SETTINGS_TABS[$tab] . '): ' . implode(', ', array_keys($changes)), $before, $changes);
        flash('success', count($changes) === 1 ? '1 setting saved.' : count($changes) . ' settings saved.');
        redirect($tabUrl, 303);
    }
}

$tabs = [];
foreach (SETTINGS_TABS as $slug => $label) {
    $tabs[] = ['label' => $label, 'href' => '/admin/settings?tab=' . $slug, 'active' => $slug === $tab];
}

render_admin('settings.php', [
    'tab' => $tab,
    'tabLabel' => SETTINGS_TABS[$tab],
    'tabs' => $tabs,
    'fields' => array_intersect_key($definitions, array_flip(settings_tab_keys($tab))),
    'values' => $values,
    'errors' => $errors,
    'wordingKeys' => settings_wording_keys($tab),
    'defaults' => settings_defaults(),
    'panelOrigin' => request_origin(),
    'httpsPermanent' => https_redirect_is_permanent(APP_ROOT),
    'httpsLocal' => in_array(request_host(), ['localhost', '127.0.0.1', '::1'], true) || !request_is_https(),
], ['title' => 'Settings · ' . SETTINGS_TABS[$tab], 'body_class' => ($head['body_class'] ?? 'admin t-light') . ' has-actionbar'] + $head);
