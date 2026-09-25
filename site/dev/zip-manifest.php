<?php
return [
    'exclude_exact' => [
        'config.php',
        'default.php',
        'storage/.installed',
        'storage/.install-key',
        'storage/MAINTENANCE',
        'robots.txt.bak',
    ],
    'exclude_prefixes' => [
        'dev/',
        'storage/logs/',
        'storage/cache/',
        'storage/sessions/',
        'storage/proofs/',
        'uploads/products/',
        'uploads/collections/',
        'uploads/og/',
        'uploads/settings/',
        '.git',
    ],
    'exclude_basenames' => ['.DS_Store', 'Thumbs.db', '.gitignore', '__MACOSX'],
    'keep_inside_excluded_dirs' => ['.gitkeep', 'index.php', '.htaccess'],
    'empty_dirs' => [
        'storage/logs', 'storage/cache', 'storage/sessions', 'storage/sessions/shop', 'storage/sessions/admin', 'storage/proofs',
        'uploads/products', 'uploads/collections', 'uploads/og', 'uploads/settings',
    ],
    'required_files' => [
        'index.php', 'install.php', 'cron.php', '.htaccess', '.user.ini', 'config.sample.php', 'robots.txt', 'favicon.ico',
        'app/.htaccess', 'db/.htaccess', 'storage/.htaccess', 'uploads/.htaccess', 'assets/.htaccess', 'admin/.htaccess',
        'admin/controllers/.htaccess', 'admin/views/.htaccess', 'admin/partials/.htaccess',
        'app/bootstrap.php', 'admin/index.php', 'db/schema.sql', 'db/seed.sql', 'db/sample-manifest.php',
        'storage/index.php', 'db/index.php', 'admin/controllers/index.php', 'admin/views/index.php', 'admin/partials/index.php',
        'app/lib/vendor/PHPMailer/PHPMailer.php', 'app/lib/vendor/PHPMailer/SMTP.php', 'app/lib/vendor/PHPMailer/Exception.php', 'app/lib/vendor/PHPMailer/LICENSE',
    ],
    'required_htaccess_count' => 10,
    'forbidden_patterns' => [
        '#^(dev/|\.git|storage/sessions/[^/]+/sess_|storage/logs/.+\.log$|storage/cache/.+\.(json|xml|php)$|storage/proofs/\d)#',
        '#(^|/)(config\.php|\.DS_Store|\.installed|\.install-key|MAINTENANCE)$#',
        '#(^|/)__MACOSX(/|$)#',
    ],
];
