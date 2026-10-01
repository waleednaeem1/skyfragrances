<?php
defined('SKYFR') || exit;

const COPY_UPGRADE_VERSION = '2026-10-01';

const COPY_UPGRADE_PAGE_HASHES = [
    'about' => '6f5774447bdb31feaab400b077a66f08df733baa4a9151831bdd05e5281ac65f',
    'faq' => '7e448d610ec720865dbf58ac7b0c201bc7a36f843c77441d4f532103bf189341',
    'shipping' => '67dd24721d8def563d230ca5e0c05951b1dfba7d558484865161484e611ba229',
    'returns' => '5b3a71ddc52176a8183479a72dcd4d16ff8d3ca2fd9ad5d9b6a95a01f4c8ae68',
    'privacy' => 'cbffa291a156458d62056c8c64dff5ba84d9f9e6e19b85b91df4712f3f8c7ccf',
    'terms' => 'ac648dd204e2000e9311701ad67dda5e43d6599316326ec49c8057768716994d',
];

const COPY_UPGRADE_SETTING_HASHES = [
    'cod_note' => '49635db68e89d05fc358612b5605c456b9814164daf5c8827b3b96e001091c48',
    'footer_blurb' => 'fe10f33338247da08209ae683dc13d1bace4d645c169f6d7e08f60c6a6a815b8',
    'hero_trust_line' => '2388f480d662d124c80fe9fef9c9c777a6875f8163e8d4f58f54fd62de59e2f6',
    'maintenance_message' => 'f1ccb68581bd1b1def403f6ef1bf0ee2cdfc70af4148f56805ed6a698f05eff8',
    'manual_payment_note' => '6d2811ba80402cee0dbb1f0d02b023adcd5a4c182a91c78fca0e688a7ad96833',
    'newsletter_heading' => '3fffeb58c5065a1fb2ec6a92f6408d54aaf8a307f27377bb2c63d832c578ed31',
    'newsletter_text' => '107383425706bd0d828b93f61b5c01aafd16c6fa9ff333c20d46a7122e55acbc',
    'quiz_band_text' => '38a3cba8c9aa2f02327a1b9ea8e1ce7d55faa3501f566a9dac1fa986f9d8eb60',
    'whatsapp_reply_template' => '9df4850f14c753d87020e1a4ec408baec6ef7ca7e8cf3d0144ee58c9efbc3179',
];

const COPY_UPGRADE_NEW_SETTINGS = [
    'contact_reply_time' => 'contact',
    'returns_days' => 'shipping',
];

const COPY_UPGRADE_PAGE_COLUMNS = ['title', 'heading', 'body', 'seo_title', 'seo_description'];

function copy_upgrade_maybe_run(): void
{
    if ((string) ($GLOBALS['skyfr_settings']['copy_upgrade_version'] ?? '') === COPY_UPGRADE_VERSION) {
        return;
    }
    $defaults = default_copy();
    if ($defaults['pages'] === [] && $defaults['settings'] === []) {
        return;
    }
    try {
        $report = db_transaction(static fn (): ?array => copy_upgrade_apply($defaults));
    } catch (Throwable $e) {
        log_write('warning', 'Copy upgrade failed', ['error' => $e->getMessage()]);
        return;
    }
    settings_cache_clear();
    settings_load();
    if ($report !== null && ($report['pages'] !== [] || $report['settings'] !== [])) {
        log_write('warning', 'Copy upgrade replaced launch text with the current defaults', $report);
    }
}

function copy_upgrade_apply(array $defaults): ?array
{
    $marker = db_fetch("SELECT setting_value FROM settings WHERE setting_key = 'copy_upgrade_version' FOR UPDATE");
    if ($marker !== null && (string) $marker['setting_value'] === COPY_UPGRADE_VERSION) {
        return null;
    }
    $now = now_karachi();
    $report = ['pages' => [], 'settings' => []];
    foreach (COPY_UPGRADE_PAGE_HASHES as $slug => $launchHash) {
        $default = $defaults['pages'][$slug] ?? null;
        if (!is_array($default) || trim((string) ($default['body'] ?? '')) === '') {
            continue;
        }
        $page = db_fetch('SELECT id, body FROM content_pages WHERE slug = :slug FOR UPDATE', ['slug' => $slug]);
        if ($page === null || !hash_equals($launchHash, hash('sha256', (string) $page['body']))) {
            continue;
        }
        $data = [];
        foreach (COPY_UPGRADE_PAGE_COLUMNS as $column) {
            if (array_key_exists($column, $default)) {
                $data[$column] = $default[$column] === null ? null : (string) $default[$column];
            }
        }
        db_update('content_pages', $data + ['updated_at' => $now], ['id' => (int) $page['id']]);
        db_insert('admin_activity_log', [
            'admin_id' => null,
            'admin_username' => 'system',
            'entity_type' => 'page',
            'entity_id' => (int) $page['id'],
            'action' => 'copy.upgrade',
            'summary' => 'Replaced the launch text on /' . $slug . ' with the current default text',
            'created_at' => $now,
        ]);
        $report['pages'][] = $slug;
    }
    foreach ($defaults['settings'] as $key => $value) {
        $value = (string) $value;
        $row = db_fetch('SELECT setting_value FROM settings WHERE setting_key = :k FOR UPDATE', ['k' => $key]);
        if ($row === null) {
            if (!isset(COPY_UPGRADE_NEW_SETTINGS[$key])) {
                continue;
            }
            db_insert('settings', ['setting_key' => $key, 'setting_value' => $value, 'setting_group' => COPY_UPGRADE_NEW_SETTINGS[$key], 'updated_at' => $now]);
            $report['settings'][] = $key;
            continue;
        }
        $current = (string) $row['setting_value'];
        if ($current === $value || !isset(COPY_UPGRADE_SETTING_HASHES[$key]) || !hash_equals(COPY_UPGRADE_SETTING_HASHES[$key], hash('sha256', $current))) {
            continue;
        }
        db_update('settings', ['setting_value' => $value, 'updated_at' => $now], ['setting_key' => $key]);
        $report['settings'][] = $key;
    }
    if ($report['settings'] !== []) {
        db_insert('admin_activity_log', [
            'admin_id' => null,
            'admin_username' => 'system',
            'entity_type' => 'setting',
            'entity_id' => null,
            'action' => 'copy.upgrade',
            'summary' => mb_substr('Replaced launch wording with the current defaults: ' . implode(', ', $report['settings']), 0, 255),
            'created_at' => $now,
        ]);
    }
    db_query(
        'INSERT INTO settings (setting_key, setting_value, setting_group, updated_at) VALUES (:k, :v, :g, :t) ON DUPLICATE KEY UPDATE setting_value = :v2, updated_at = :t2',
        ['k' => 'copy_upgrade_version', 'v' => COPY_UPGRADE_VERSION, 'g' => 'system', 't' => $now, 'v2' => COPY_UPGRADE_VERSION, 't2' => $now]
    );
    return $report;
}
