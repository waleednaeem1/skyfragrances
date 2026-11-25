<?php
defined('SKYFR') || exit;

const SCHEMA_UPGRADE_VERSION = '2026-11-25-payments';
const SCHEMA_UPGRADE_LOCK = 'skyfr_schema_upgrade';

function schema_upgrade_maybe_run(): void
{
    if ((string) ($GLOBALS['skyfr_settings']['schema_version'] ?? '') === SCHEMA_UPGRADE_VERSION) {
        return;
    }
    try {
        if ((int) db_fetch_column('SELECT GET_LOCK(:lock, 10)', ['lock' => SCHEMA_UPGRADE_LOCK]) !== 1) {
            return;
        }
        try {
            if ((string) db_fetch_column("SELECT setting_value FROM settings WHERE setting_key = 'schema_version'") !== SCHEMA_UPGRADE_VERSION) {
                schema_upgrade_steps();
                $now = now_karachi();
                db_query(
                    'INSERT INTO settings (setting_key, setting_value, setting_group, updated_at) VALUES (:k, :v, :g, :t) ON DUPLICATE KEY UPDATE setting_value = :v2, updated_at = :t2',
                    ['k' => 'schema_version', 'v' => SCHEMA_UPGRADE_VERSION, 'g' => 'system', 't' => $now, 'v2' => SCHEMA_UPGRADE_VERSION, 't2' => $now]
                );
            }
        } finally {
            db_query('SELECT RELEASE_LOCK(:lock)', ['lock' => SCHEMA_UPGRADE_LOCK]);
        }
    } catch (Throwable $e) {
        log_write('error', 'Schema upgrade failed', ['error' => $e->getMessage()]);
        return;
    }
    settings_cache_clear();
    settings_load();
}

function schema_upgrade_steps(): void
{
    schema_upgrade_add_column('product_sizes', 'display_stock', 'SMALLINT UNSIGNED NULL DEFAULT NULL AFTER stock');
    schema_upgrade_add_column('orders', 'amount_paid', 'DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00 AFTER grand_total');
    db_query('CREATE TABLE IF NOT EXISTS order_payments (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id INT UNSIGNED NOT NULL,
        amount DECIMAL(10,2) UNSIGNED NOT NULL,
        paid_at DATETIME NOT NULL,
        note VARCHAR(255) NULL DEFAULT NULL,
        admin_id INT UNSIGNED NULL DEFAULT NULL,
        admin_name VARCHAR(64) NULL DEFAULT NULL,
        created_at DATETIME NOT NULL,
        voided_at DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (id),
        KEY idx_order_payments_order (order_id, voided_at),
        CONSTRAINT fk_order_payments_order_id FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    db_query("UPDATE orders SET amount_paid = grand_total WHERE payment_status = 'paid' AND amount_paid = 0");
    db_query(
        "INSERT INTO order_payments (order_id, amount, paid_at, note, created_at)
         SELECT o.id, o.grand_total, COALESCE(o.paid_at, o.updated_at, o.created_at), 'Recorded as fully paid before part payments were tracked', :now
         FROM orders o WHERE o.payment_status IN ('paid', 'refunded') AND NOT EXISTS (SELECT 1 FROM order_payments p WHERE p.order_id = o.id)",
        ['now' => now_karachi()]
    );
}

function schema_upgrade_add_column(string $table, string $column, string $definition): void
{
    if (db_exists('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c', ['t' => $table, 'c' => $column])) {
        return;
    }
    try {
        db_query('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) !== 1060) {
            throw $e;
        }
    }
}
