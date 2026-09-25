# Running Sky Fragrances locally

The shop is plain PHP; locally it runs on PHP's built-in web server with a router script
that mimics the Hostinger `.htaccess` rewrites (`php -S` does not read `.htaccess`).

## Requirements

- PHP 8.2 or newer with `pdo_mysql`, `gd`, `mbstring`, `fileinfo`, `openssl`, `curl`
- MySQL 8+ or MariaDB 10.4+ running locally (`brew services start mysql`)
- An empty database and a user that may create tables:

```sql
CREATE DATABASE skyfragrances CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'skyfr'@'localhost' IDENTIFIED BY 'skyfr';
GRANT ALL PRIVILEGES ON skyfragrances.* TO 'skyfr'@'localhost';
```

## Start the server

From the repository root (the folder that contains `site/`):

```bash
php -S 127.0.0.1:8080 -t site site/dev/router.php
```

Then open <http://127.0.0.1:8080/install.php> and follow the four steps. The installer
detects the local host name and writes `config.php` with `'env' => 'development'`, so errors
are displayed and every log level is written. On Hostinger the same file is written with
`'env' => 'production'`.

## What the router does

`site/dev/router.php` reproduces the production rewrite rules:

| Request | Behaviour |
|---|---|
| `/app/...`, `/db/...`, `/storage/...`, `/dev/...`, `/config.php`, `/.user.ini`, `/.htaccess`, `*.sql`, `*.md`, `*.log` | 403 (the same paths are denied by `.htaccess` on Hostinger) |
| `/index.php/anything` | 301 to `/anything` |
| `/shop/` (trailing slash, not a real directory) | 301 to `/shop` |
| `/uploads/*.php` | 403; other existing files under `/uploads/` and `/assets/` are served as-is |
| `/admin`, `/admin/...` | `site/admin/index.php` |
| Any other existing file (`/robots.txt`, `/install.php`, `/favicon.ico`) | served directly |
| Everything else | `site/index.php` (the storefront front controller) |

HTTPS and `www` redirects are not simulated; they only exist on the live server.

## Re-installing locally

The installer refuses to run twice. To start over: drop and recreate the database, delete
`site/config.php` and `site/storage/.installed`, then open `/install.php` again. Steps 2 and 3 ask
for the one-time key the installer writes to `site/storage/.install-key`; read it with
`cat site/storage/.install-key` and paste it once.

## Building the upload ZIP

`php dev-tools/build-zip.php` writes `dist/skyfragrances-<date>.zip` from `site/` without
`config.php`, the install lock and key, sessions, logs, caches, proofs, uploaded images and `dev/`,
and refuses to finish if any of those slipped in.

## Files that never ship

`site/dev/router.php` is development-only. It is harmless if uploaded (LiteSpeed never
invokes it, and `/dev/` is not a routed path) but should be left out of the deployment ZIP.
