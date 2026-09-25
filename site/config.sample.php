<?php
defined('SKYFR') || exit;

return [
    'env' => 'production',
    'base_url' => 'https://skyfragrances.com',
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'REPLACE_ME_database_name',
        'user' => 'REPLACE_ME_database_user',
        'pass' => 'REPLACE_ME_database_password',
    ],
    'smtp' => [
        'host' => 'smtp.hostinger.com',
        'port' => 587,
        'secure' => 'tls',
        'user' => 'orders@skyfragrances.com',
        'pass' => 'REPLACE_ME_smtp_password',
        'from_name' => 'Sky Fragrances',
    ],
    'security' => [
        'app_key' => 'REPLACE_ME_with_64_hex_characters',
        'cron_key' => 'REPLACE_ME_with_32_hex_characters',
    ],
    'trusted_proxies' => [],
];
