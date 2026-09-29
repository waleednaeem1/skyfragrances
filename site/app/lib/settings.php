<?php
defined('SKYFR') || exit;

function settings_defaults(): array
{
    return [
        'store_name' => 'Sky Fragrances',
        'store_tagline' => 'More Than Just A Scent',
        'logo_path' => 'assets/img/logo.png',
        'favicon_path' => 'assets/img/favicon.png',
        'address_line' => '',
        'footer_blurb' => 'Luxury fragrance, made for Pakistan.',
        'currency_prefix' => 'Rs.',
        'timezone' => 'Asia/Karachi',
        'contact_phone' => '',
        'whatsapp' => '',
        'contact_email' => '',
        'order_notify_email' => '',
        'business_hours' => '',
        'contact_reply_time' => 'one working day',
        'instagram_url' => '',
        'instagram_handle' => '',
        'facebook_url' => '',
        'tiktok_url' => '',
        'youtube_url' => '',
        'whatsapp_reply_template' => 'Assalam o Alaikum {name}, thank you for contacting Sky Fragrances.',
        'announcement_enabled' => '1',
        'announcement_text' => 'Free delivery on orders above Rs. 3,000',
        'announcement_link' => '/shop',
        'hero_heading' => 'More Than Just A Scent',
        'hero_subheading' => 'Long-lasting eau de parfum, crafted for Pakistan and delivered to your door.',
        'hero_cta_label' => 'Explore the Collections',
        'hero_cta_url' => '/collections',
        'hero_image_desktop' => '',
        'hero_image_mobile' => '',
        'hero_trust_line' => 'Cash on delivery · Nationwide',
        'home_bestsellers_count' => '8',
        'home_new_count' => '8',
        'home_reviews_enabled' => '1',
        'quiz_band_enabled' => '1',
        'quiz_band_heading' => 'Not sure where to start?',
        'quiz_band_text' => 'Answer five quick questions and we will match you with your signature scent.',
        'gender_tile_men_image' => '',
        'gender_tile_women_image' => '',
        'gender_tile_unisex_image' => '',
        'newsletter_heading' => 'Join the Sky Circle',
        'newsletter_text' => 'New arrivals, private offers and scent stories, straight to your inbox.',
        'newsletter_cta_label' => 'Subscribe',
        'instagram_tile_1_image' => '', 'instagram_tile_1_url' => '',
        'instagram_tile_2_image' => '', 'instagram_tile_2_url' => '',
        'instagram_tile_3_image' => '', 'instagram_tile_3_url' => '',
        'instagram_tile_4_image' => '', 'instagram_tile_4_url' => '',
        'instagram_tile_5_image' => '', 'instagram_tile_5_url' => '',
        'instagram_tile_6_image' => '', 'instagram_tile_6_url' => '',
        'shipping_fee' => '250.00',
        'free_shipping_threshold' => '3000.00',
        'delivery_time' => '2–4 working days',
        'returns_days' => '7',
        'cod_enabled' => '1',
        'cod_note' => 'Pay the courier when your parcel arrives.',
        'bank_enabled' => '0',
        'bank_name' => '',
        'bank_account_title' => '',
        'bank_account_number' => '',
        'bank_iban' => '',
        'jazzcash_enabled' => '0',
        'jazzcash_account_title' => '',
        'jazzcash_number' => '',
        'easypaisa_enabled' => '0',
        'easypaisa_account_title' => '',
        'easypaisa_number' => '',
        'manual_payment_note' => 'Send your payment screenshot and transaction ID with your order.',
        'meta_description' => 'Luxury perfumes in Pakistan. Long-lasting eau de parfum for him, her and unisex. Cash on delivery nationwide, fast delivery, easy exchange.',
        'og_default_image' => 'assets/img/og-default.jpg',
        'google_verification' => '',
        'gsc_analytics_id' => '',
        'low_stock_threshold' => '5',
        'admin_rows_per_page' => '20',
        'cod_max_total' => '0',
        'manual_hold_hours' => '48',
        'maintenance_mode' => '0',
        'maintenance_message' => 'We are refreshing the store and will be back shortly. Message us on WhatsApp if you need anything in the meantime.',
        'maintenance_bypass' => '',
        'base_url' => '',
        'trusted_proxies' => '',
        'https_permanent' => '0',
        'site_indexable' => '1',
        'images_webp_enabled' => '1',
        'install_completed_at' => '',
    ];
}

function settings_cache_path(): string
{
    return APP_ROOT . '/storage/cache/settings.json';
}

function settings_load(): void
{
    $path = settings_cache_path();
    $rows = null;
    if (is_file($path) && (time() - (int) @filemtime($path)) < 300) {
        $cached = json_decode((string) @file_get_contents($path), true);
        if (is_array($cached)) {
            $rows = $cached;
        }
    }
    if ($rows === null) {
        $rows = db_fetch_pairs('SELECT setting_key, setting_value FROM settings');
        settings_cache_write($rows);
    }
    $GLOBALS['skyfr_settings'] = array_merge(settings_defaults(), $rows);
}

function settings_cache_write(array $rows): void
{
    $dir = dirname(settings_cache_path());
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $temp = $dir . '/settings.' . bin2hex(random_bytes(4)) . '.tmp';
    $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    $written = $json === false ? false : @file_put_contents($temp, $json, LOCK_EX);
    if ($written === false || !@rename($temp, settings_cache_path())) {
        @unlink($temp);
    }
}

function settings_cache_clear(): void
{
    @unlink(settings_cache_path());
    @unlink(APP_ROOT . '/storage/cache/settings.php');
}

function settings_site_url(): string
{
    $candidate = rtrim(trim((string) setting('base_url', '')), '/');
    if ($candidate !== '' && preg_match('#^https?://[a-z0-9.-]+(:\d+)?$#i', $candidate)) {
        return $candidate;
    }
    return rtrim((string) config('base_url', ''), '/');
}

function setting(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['skyfr_settings'][$key] ?? null;
    if ($value === null) {
        return $default;
    }
    $value = (string) $value;
    if (trim($value) === '' || str_contains($value, 'REPLACE ME')) {
        return $default;
    }
    return $value;
}

function setting_int(string $key, int $default = 0): int
{
    $value = setting($key);
    if ($value === null) {
        return $default;
    }
    $int = filter_var(trim((string) $value), FILTER_VALIDATE_INT);
    return $int === false ? $default : $int;
}

function setting_bool(string $key, bool $default = false): bool
{
    $value = setting($key);
    if ($value === null) {
        return $default;
    }
    $bool = filter_var(trim((string) $value), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $bool ?? $default;
}

function setting_money(string $key, string $default = '0.00'): string
{
    $value = setting($key);
    if ($value === null || !preg_match('/^\d{1,8}(\.\d{1,2})?$/', trim((string) $value))) {
        return $default;
    }
    return money_from_paisa(money_paisa(trim((string) $value)));
}

function default_copy(): array
{
    static $copy = null;
    if ($copy !== null) {
        return $copy;
    }
    $file = APP_ROOT . '/app/data/default-copy.php';
    $loaded = is_file($file) ? require $file : null;
    $copy = ['pages' => [], 'settings' => []];
    if (is_array($loaded)) {
        $copy['pages'] = is_array($loaded['pages'] ?? null) ? $loaded['pages'] : [];
        $copy['settings'] = is_array($loaded['settings'] ?? null) ? $loaded['settings'] : [];
    }
    return $copy;
}
