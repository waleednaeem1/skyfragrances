# Stage 1 run report — 2026-09-25

Local end-to-end run of the stage-1 tree (`site/`) on this machine: PHP 8.2.28 built-in server,
MySQL 9.3.0. Everything below was exercised with `curl` against `http://127.0.0.1:8088`.

## What was run

| Step | Command / action | Result |
|---|---|---|
| Lint | `find site -name '*.php' -exec php -l {} \;` (86 files) | 0 syntax errors |
| Contract | every function each owner "needs" grepped against every `function` definition (210 defined) | no missing function; only `cart_count()` (stage 2) is absent and every caller guards it with `function_exists()` |
| DB reset | `DROP DATABASE skyfragrances_dev; CREATE DATABASE … utf8mb4_unicode_ci` | empty database |
| Stale state | removed a corrupted `config.php`, an empty `storage/cache/settings.php`, a stray session file | clean tree |
| Server | `php -d display_startup_errors=0 -S 127.0.0.1:8088 dev/router.php` (PID recorded, killed at the end) | `GET /` → 503 "not configured yet" before install, as designed |
| Install | GET step 1 → GET step 2 (CSRF `_token`) → POST `db_save` → GET step 3 → POST `install` (admin `admin` / `Admin#Sky2026!`, seed on) → GET step 4 | 54 schema + 235 seed statements, admin created, `site_indexable=0` (host is not skyfragrances.com), `storage/.installed` written |
| Re-run | `GET /install.php` with the install cookie and with a fresh cookie; `GET /install.php?step=3` fresh | Finish/Delete panel with the cookie; "Sky Fragrances is already installed — delete install.php" without it; step 3 is not reachable |
| Routes | every entry in `app/routes.php` and `app/routes-admin.php` (tables below) | see tables |
| Admin auth | wrong password, correct login, logged-in sweep, logout, 6 wrong passwords, correct password while locked | see below |
| CLI contract | scratch script bootstrapping the app and calling the helpers HTTP never reaches | see below |
| Logs | `/tmp/skyfr-php.log`, `storage/logs/*` | no PHP warning / notice / deprecation; no `php-error.log` created |

`.user.ini` and every `.htaccess` are not interpreted by `php -S`; their intent was checked by reading them.

## Seed counts (all match 07 Part A)

| Table | Rows | | Table | Rows |
|---|---|---|---|---|
| settings | 81 (79 seed + `install_completed_at` + `images_webp_enabled` from install.php) | | quiz_questions | 5 |
| admin_users | 1 (`admin`, hash rehashed to bcrypt cost 12 on first login) | | quiz_options | 19 |
| collections | 5 | | quiz_option_scores | 31 |
| scent_families | 5 | | coupons | 3 (WELCOME10, EIDSALE500, FIRST50) |
| products | 12 | | content_pages | 6 |
| product_sizes | 24 | | reviews | 8 (5 approved, 3 pending, all `is_sample=1`) |
| product_images | 36 (`sample/{slug}-{n}.webp`) | | orders / order_items / payment_proofs / … | 0 |

26 tables exist, exactly the register §1.2 list. Product slugs, in id order: azure-oud, cirrus, aurora-bloom,
stratus-noir, eclipse-velvet, zephyr-blanc, silver-lining, cumulus-cashmere, saffron-zenith, halo-rose,
nimbus-rain, vetiver-squall. Collections: dawn-chorus, azure-heights, golden-hour, midnight-meridian,
monsoon-veil. Families: fresh-citrus, floral, amber-spice, oud-smoke, green-earthy.

## Storefront routes

| Route | Status | Note |
|---|---|---|
| GET / | 200 | canonical `/`, robots follows `site_indexable` |
| GET /shop, /shop?page=2&sort=price_asc&gender=him, /shop?collection=golden-hour&family=floral&min=3000&max=9000 | 200 | |
| GET /collections | 200 | |
| GET /for-him, /for-her, /unisex | 200 | |
| GET /new-arrivals, /best-sellers, /sale | 200 | |
| GET /search?q=oud | 200 | |
| GET /scent-finder | 200 | |
| GET /scent-finder/result?q1=a&q2=b&q3=c&q4=a&q5=d | 200 | |
| GET /cart | 200 | SFSHOP cookie: `path=/; HttpOnly; SameSite=Lax` |
| GET /checkout | 200 | |
| POST /checkout (CSRF) | 303 → /checkout | stage-3 stub flashes and redirects |
| GET /track | 200 | |
| POST /track (CSRF, unknown order) | 200 | re-renders; POST without CSRF → 303 back |
| GET /faq, /about, /shipping, /returns, /privacy, /terms | 200 | |
| GET /contact | 200 | |
| POST /contact (CSRF, valid) | 303 → /contact | row in `contact_messages`, success flash shown |
| POST /contact (no CSRF) | 200 | re-renders with "Your session expired…" in `$errors`; logged as warning |
| GET /unsubscribe?e=…&t=… (bad token) | 200 | |
| GET /unsubscribe?e=…&t=… (real token) | 200 | row flips to `unsubscribed` |
| GET /sitemap.xml with `site_indexable=0` | 404 | designed: the installer set 0 off-domain |
| GET /sitemap.xml with `site_indexable=1` | 200 `application/xml` | 40 `<loc>` entries, W3C lastmod, cached to `storage/cache/sitemap.xml`; setting restored to 0 afterwards |
| GET /robots.txt | 200 `text/plain` | static file wins (dev router serves it, as `.htaccess -f` would) |
| GET /api/cart | 501 `{"ok":false,"error":"stage 2"}` | |
| POST /api/cart/add (X-CSRF-Token) | 501 stage 2 | |
| POST /api/cart/add (no token) | 419 `{"ok":false,"error":"csrf"}` | |
| GET /api/search-suggest?q=azure | 200 JSON | |
| POST /api/newsletter (JSON, X-CSRF-Token) | 200 `{"ok":true,…}` | row in `newsletter_subscribers` |
| POST /api/newsletter (bad email) | 422 `{"ok":false,"error":"email",…}` | |
| GET /collections/dawn-chorus | 200 | /collections/nope → 404 |
| GET /scent/oud-smoke | 200 | /scent/nope → 404 |
| GET /product/azure-oud | 200 | /product/nope → 404 |
| POST /product/azure-oud/review (CSRF) | 303 → /product/azure-oud#reviews | pending `reviews` row, `is_sample=0` |
| GET /order/SF-260925-ABCD?t=x | 301 → /order/sf-260925-abcd?t=x → 404 | case-normalising redirect keeps the query; no such order |
| GET /order/bad | 404 | |
| GET /this-does-not-exist | 404 | full layout, `noindex,nofollow`, no canonical tag, path not echoed |
| GET /Shop | 301 → /shop | |
| POST /shop | 405 `Allow: GET` | |
| HEAD / | 200 | |
| GET /__rewrite-probe | 200 `text/plain` `SKYFR-REWRITE-OK` | after fix (was 404); POST stays 404 |

## Admin routes

Logged out, every GET below answered `302 → /admin/login` (with `?next=` for everything except `/admin`, `/admin/`
and `/admin/logout`) and every POST answered `302 → /admin/login`:
`/admin`, `/admin/`, `/admin/logout`, `/admin/password`, `/admin/orders`, `/admin/orders/export.csv`,
`/admin/orders/{order}`, `/admin/orders/{order}/proof`, `/admin/orders/{order}/invoice`, `/admin/products`,
`/admin/products/new`, `/admin/products/1`, `/admin/collections`, `/admin/collections/new`, `/admin/collections/1`,
`/admin/coupons`, `/admin/coupons/new`, `/admin/coupons/1`, `/admin/reviews`, `/admin/messages`, `/admin/messages/1`,
`/admin/subscribers`, `/admin/subscribers/export.csv`, `/admin/pages`, `/admin/pages/about`, `/admin/settings`,
`/admin/tools/remove-sample-data`, `/admin/does-not-exist`; POST `/admin/orders/{order}/status|payment|note`,
`/admin/products/bulk`, `/admin/products/1/delete|images|images/order|images/2/delete`, `/admin/collections/1/delete`,
`/admin/coupons/1/toggle`, `/admin/reviews/bulk`, `/admin/reviews/1/status`, `/admin/messages/1/status`,
`/admin/subscribers/1/status`, `/admin/settings`, `/admin/pages/about`, `/admin/tools/remove-sample-data`.

`GET /admin/login` → 200; SFADMIN cookie `path=/admin; HttpOnly; SameSite=Strict`; `X-Robots-Tag: noindex, nofollow`;
`Cache-Control: no-store`.

| Auth step | Result |
|---|---|
| POST /admin/login wrong password | 200, "Wrong username or password.", `admin_login_attempts` + `admin_activity_log login.fail` |
| POST /admin/login correct | 303 → /admin; `last_login_at` set; hash rehashed `$2y$10$` → `$2y$12$`; `login.ok` logged |
| Logged in: /admin | 200 dashboard, red "install.php is still on the server" banner present |
| Logged in: /admin/password, /admin/orders, /admin/products, /admin/settings | 200 (stub pages for the 37 stage-2+ routes) |
| Logged in: /admin/does-not-exist | 404 inside the admin layout |
| Logged in: /admin/login | 303 → /admin |
| POST /admin/logout | 303 → /admin/login?reason=out; /admin afterwards → 302 login |
| 6 wrong passwords | attempts 1-4 → 200 with the error; attempts 5-6 → 429 "Too many failed attempts. Try again in N minutes." |
| Correct password while locked | 429 (lock is enforced before the password is checked) |

## CLI contract check (helpers HTTP never reaches)

`settings_defaults()` has every seed key (79) plus `install_completed_at` and, after the fix, `images_webp_enabled`;
`setting('contact_phone')` treats the seeded `''` as unset; `setting_money('free_shipping_threshold')` = `3000.00`;
`money('4950.00')` = `Rs. 4,950`, `money('4950.50', true)` = `Rs. 4,950.50`, `money_paisa('4950.50')` = 495050;
`phone_normalize()` gives `+923001234567` for `0300 1234567`, `+92 300 1234567`, `923001234567`;
`truncate_on_word('The quick brown fox…', 20)` = `The quick brown…`; `slugify('Azure Oüd — Édition No. 2')` = `azure-oud-edition-no-2`;
`effective_price()` ignores null / `0` sale prices; `url('')` = `/`, `asset()` appends `?v=filemtime`;
`image_url('sample/azure-oud-1.webp', 'card')` and an unknown stem both fall back to `assets/img/placeholder-4x5.svg`;
a real stem maps to `/uploads/products/1/{stem}-card.webp` and `og` to `/uploads/og/{stem}-og.jpg`;
`mail_transport_available()` = false (no PHPMailer vendored, SMTP host empty), `mail_send()` = true and writes
`storage/logs/mail-preview/{Ymd-His}-{6hex}-contact-admin.html`; invalid recipient → false + warning;
`db/sample-manifest.php` has exactly `products`(12) / `collections`(5) / `coupons`(3) / `settings`(8) and all 12 slugs exist;
`db_query()` with a positional placeholder throws `InvalidArgumentException`; `db_update()` with an empty `$where` throws;
quiz question 1 = gender (a him / b her / c any), question 5 = budget (a / b / c / d any), as 07 A.6.1 says.

## Fixes applied

| File | Change |
|---|---|
| `site/install.php` | `inst_config_source()` now writes `defined('SKYFR') \|\| exit;` before `return [...]` (config.sample.php has it, the generated config.php did not; the ground rules require the guard on every PHP file) |
| `site/config.php` | the guard line added to the copy install.php had already written locally |
| `site/index.php` | answers `GET /__rewrite-probe` with `200 text/plain SKYFR-REWRITE-OK` (+ no-store, noindex) so the installer's pretty-URL self-check can pass on the real host; previously 404 |
| `site/uploads/.htaccess` | made the 02b §5 canonical text: removed `Options -Indexes` (inherited from root; can 500 under a restricted `AllowOverride Options=`), added rule 0 `ErrorDocument 404 "Not found"`; comments omitted by ground rule |
| `site/install.php` | `INSTALL_UPLOADS_HTACCESS` literal replaced with the same text; verified byte-identical to the file |
| `site/assets/.htaccess` | new, per 02b §5 (C-72): `ErrorDocument 404 "Not found"` + the §3.8 immutable-cache header block, so a missing asset never reaches the PHP front controller |
| `site/app/lib/settings.php` | `settings_defaults()` gains `'images_webp_enabled' => '1'` (install.php writes this key to the advanced group; defaults claimed to hold every key) |

Test artefacts were removed afterwards: the contact message, review, subscriber, login attempts, activity log
rows, the mail previews, the settings/sitemap caches and the app log. The database is back to the exact seed
plus the admin row; `site_indexable` is `0`.

## Not fixable or not testable here

- MariaDB 10.4 compatibility of `db/schema.sql` / `db/seed.sql` could not be tested: only MySQL 9.3 is installed locally.
- `.htaccess` behaviour (HTTPS force, deny rules, `X-Powered-By` unset, uploads execution block) can only be read, not exercised, under `php -S`. `X-Powered-By: PHP/8.2.28` is therefore visible locally; the root `.htaccess` lines 105-106 strip it on Apache/LiteSpeed.
- `PHP Warning: Module "imagick" is already loaded` comes from this machine's `/opt/homebrew/etc/php/8.2/php.ini` (lines 1974-1975 load it twice). It is not the site's; the dev server was started with `-d display_startup_errors=0`.
- PHPMailer is not vendored; `mail_send()` degrades to previews as designed.
- The stage-1 page views are placeholders ("Storefront design lands in stage 2"), so validation/CSRF errors the controllers compute are not yet displayed for `/contact` and `/track`; the data flow is correct.
- One transient: a single home-page fetch showed `noindex,nofollow` together with a canonical right after flipping `site_indexable` to 1; it did not reproduce on a clean retry (all three page types then behaved correctly) and is most likely my test ordering against the 300-second settings cache.

## Review fixes — 2026-09-25 (second pass)

Applied after the hosting + security reviews of the stage-1 tree. Every file below was re-linted
(`php -l`, 93 files, clean) and the smoke run repeated end to end: database dropped and recreated,
`php -S 127.0.0.1:8088 -t site site/dev/router.php`, `install.php` driven with `curl`
(`admin` / `Admin#Sky2026!`, seed on), every storefront and admin route re-fetched, then the server
was stopped and all test rows, session files, caches and logs removed. The database holds exactly
the seed plus the admin row again; `robots.txt` was restored to the canonical `Sitemap:` line after
the local install rewrote it.

### Blocker / high

| Finding | Fix |
|---|---|
| Local install artefacts in the deploy tree; stale `storage/.installed` treated as final | `dev-tools/build-zip.php` builds `dist/skyfragrances-<stamp>.zip` from `site/` with an exclusion list (`config.php`, `default.php`, `storage/.installed`, `storage/.install-key`, `storage/MAINTENANCE`, `storage/{logs,cache,sessions,proofs}/*`, `uploads/{products,collections,og,settings}/*`, `dev/`, `.git*`, `.DS_Store`) and then re-opens the ZIP to assert none of them is inside and every required file is (rehearsed: 137 files in, 133 local files left out). `.gitignore` now covers `.installed`, `.install-key`, `MAINTENANCE`, `sessions/*`, `proofs/*`, `default.php`, `dist/`. `inst_state()` no longer trusts the lock file alone: a lock with no `config.php` is deleted with a notice and the install starts fresh; a lock whose database has no `install_completed_at` is deleted and the state comes from the database; a missing lock is re-created when the database says installed. 129 local session files and the local lock were removed. |
| `install.php` accepts `db_save` / `install` unauthenticated; no fail-closed state | When `config.php` exists and its database does not connect the installer shows a "fix the database or delete config.php by hand" page on every step and `inst_handle_db_save()` refuses to overwrite (verified: attacker `db_save` left `config.php` byte-identical). The lock is now written **before** the admin row and the step aborts if it cannot be written. The installer is bound to the owner: the first visit to step 2 or 3 writes 32 random hex characters to `storage/.install-key` (0600); every `db_save` / `install` POST must carry it (`hash_equals`), after which the session remembers it; the key file is deleted when the install completes or the site is already installed. Wrong key → form re-rendered with the error and nothing written (verified). |
| Development `config.php`, lock, sessions and `dev/` ship with no exclusion | Same build script and `.gitignore` as above; `config.sample.php` is verified by the script to still contain `REPLACE_ME` and no 64-hex secret. `site/dev/router.php` stays in git (it is developer tooling) but is excluded from the ZIP and asserted absent — a deliberate deviation from "add `dev/` to `.gitignore`". |
| Same-origin check compared against `config['base_url']` | `request_origin_is_foreign()` now compares the `Origin`/`Referer` host with `request_host()` (`HTTP_HOST`, lower-cased, port stripped, IPv6 brackets kept; falls back to `SITE_URL` only when `HTTP_HOST` is empty). `SITE_URL` remains for canonical/OG/email/sitemap. `install.php` prefills the address with "You opened this page at … — use that unless you know why not" and writes `https://skyfragrances.com` whenever the entered host is the canonical host or its `www.` form, whatever scheme the installer was opened on (C-47). Verified: `Origin: https://evil.example` → 403, same host → accepted; preview-host case simulated in CLI. |
| Install POST runs 20–50 s of loopback probes before the lock | `inst_handle_install()` now does schema → seed → lock → admin → settings → `install_completed_at` → runtime dirs → robots rewrite and redirects (0.24 s locally). The uploads-execution and private-file probes moved to step 4 as a re-runnable **Security checks** form (`action=checks`), stored in the session and shown with the report. Probe timeouts are 2 s connect / 3 s total. Step 1 skips the pretty-URL probe when `gethostbyname(HTTP_HOST)` is not `SERVER_ADDR` and says so. A half-finished install (tables + no admin) already resumes as `partial`; because the lock is now written first and unlinked again when `install_completed_at` is absent, "tables + admin + no completion" can no longer arise from a killed request. |

### Medium

| Finding | Fix |
|---|---|
| Username-only lockout is a DoS on the owner | `auth_lock_minutes()` hard-locks on the **(username, ip_hash) pair** after 5 failures and on the IP alone after 10 (15 min window). The pure-username count only drives the progressive `usleep`. Verified in CLI: `admin` locked from 127.0.0.1 (15 min) while `admin` from 203.0.113.9 reports 0. HTTP: attempts 1–4 → 200, 5–6 → 429, correct password while locked → 429 (unchanged from the first run). |
| `rate_limits` unused on public endpoints | New `app/lib/ratelimit.php` (`rate_limit_hit(bucket, subjectHash, max, windowSeconds)`, `rate_limit_guard()`, `rate_limit_subject()`, 1-in-20 purge of rows older than 30 days, bucket names checked against the register list). `/track` POST: 10 / 15 min and 40 / 24 h per `ip_hash`, 20 / 24 h per normalised phone (06b §6.3), 429 + the spec message; `/contact` 5 / h; `/product/{slug}/review` 5 / h plus the `website` honeypot; `/api/newsletter` 10 / h (429 JSON). Verified: 12 track POSTs → ten 200s then 429 429; newsletter → 429 on the 11th. The 1/5 weighting of successful lookups in 06b §6.3 is not implemented (a successful lookup counts as one attempt). |
| Storefront CSRF per controller; `contact.php` continued after a failure | `site/index.php` now runs `csrf_check(request_csrf_token_sent())` for every POST after routing (unknown routes still 404): `/api/*` → JSON 419, otherwise flash + 303 to the same-origin `Referer` path (fragment kept) or the request path. The six per-controller `try/catch` blocks were deleted; `contact.php` cannot reach the honeypot or insert without a valid token. Verified for `/contact`, `/track`, `/checkout`, `/product/azure-oud/review` (→ `#reviews`), `/api/cart/add`, `/api/newsletter`. |
| Absolute paths in `.htaccess` break sub-folder installs | Register C-71 rules sub-folder installs out of v1, so the root rewrite rules were left as written and `install.php` now reports a sub-folder as **Problem** (fail) with the fix ("move the files up one level"); `db_save` also refuses. `admin/.htaccess` was reduced to headers only per C-54 (the root file already routes `/admin`). `default.php` and a nested `public_html/` are detected on step 1 (C-47). |
| `REMOTE_ADDR` used as the client IP | `client_ip()` in `request.php`: when `REMOTE_ADDR` is inside `config['trusted_proxies']` (CIDR list, IPv4/IPv6) the rightmost `X-Forwarded-For` hop that is not itself a trusted proxy is used, else `CF-Connecting-IP`, else `REMOTE_ADDR`; the header is never read otherwise. `request_ip()` delegates to it, so every `ip_hash` (attempts, contact, reviews, rate limits, CSRF log) follows. `install.php` records `REMOTE_ADDR/32` (or `/128`) as trusted only when its own request already carried a forwarded-for header, writes it to `config['trusted_proxies']` and prints a *Client IP detection* row. Verified in CLI (`203.0.113.0/24` + `10.0.0.0/8` trusted, XFF `198.51.100.7, 10.0.0.1` → `198.51.100.7`; untrusted → `REMOTE_ADDR`). The Settings › Advanced field belongs to the stage-4 owner. |
| `post_max_size` smaller than one product-image form | Per register C-46: `.user.ini` now `upload_max_filesize=10M`, `post_max_size=12M`, `max_file_uploads=3`; `UPLOAD_MAX_PRODUCT_BYTES` 6 MB, `upload_files_from_request()` returns one file per request (`UPLOAD_FILES_PER_REQUEST`). `request_body_too_large()` compares `CONTENT_LENGTH` with the parsed `post_max_size` when PHP dropped `$_POST`/`$_FILES`; both front controllers run it before the CSRF check and answer 413 JSON or flash "Your upload was too large. The limit is X MB per save. Add fewer photos at a time." `install.php` fails step 1 when either limit is below 6M. |
| 40 MP cap needs ~170 MB in GD | `upload_pixel_budget()` = `(memory_limit − 16 MB − current usage) / 5` capped at 40 MP; `upload_validate()` rejects above it with "This photo is too large to resize on this server (N megapixels; the limit is M)". `UPLOAD_MAX_SIDE` is 10,000 (C-46). `install.php` shows the effective `memory_limit` and the resulting megapixel budget and fails below 128M (256M → 40.0 MP; 128M → 23.3 MP in CLI). |
| Unconditional storefront `session_start()`, no GC | `bootstrap_session_start()` sets `gc_probability=1`, `gc_divisor=100`, saves shop sessions in `storage/sessions/shop` (3 days) and admin sessions in `storage/sessions/admin` (12 h); the storefront starts a session only when an `SFSHOP` cookie is present, the method is POST, or the path starts with `/api/cart`, `/api/session`, `/checkout`, `/track`, `/cart`, `/order/`. `csrf_token()`, `csrf_rotate()` and `flash()` call `session_ensure()` so a form page still gets a token; `flash_take()` returns `[]` without a session. The layout's `<meta name="csrf-token">` is empty until a session exists and `SF.fetch()` (ui.js) fetches `GET /api/session` → `{csrf}` before its first non-GET call. Verified: `GET /`, `/shop`, a 404 → no `Set-Cookie`, no file; `/cart` → `SFSHOP` cookie. |

### Low

| Finding | Fix |
|---|---|
| Failed-login usernames written to the log and two tables | `log_write` records `username_hash` (`sha256(app_key|username)`); the activity-log summary carries a 3-character prefix plus 12 hash characters; the plain username stays only in `admin_login_attempts`. |
| Unthrottled current-password oracle | A wrong current password on `/admin/password` is recorded as a failed attempt for the (username, ip) pair and logged as `password.fail`; when the pair lock trips (5 in 15 min) the session is destroyed and the browser lands on `/admin/login?reason=expired`. |
| Installer trusts `Host` | `inst_request_host()` accepts `HTTP_HOST` only when it matches `^[a-z0-9.-]+(:\d+)?$`, else `SERVER_NAME`, else `localhost`; probes only run when the host resolves to this server. |
| Shared session save path | Split into `storage/sessions/shop` and `storage/sessions/admin` (above); both added to `INSTALL_RUNTIME_DIRS`, the ZIP and `.gitkeep`s. |
| HSTS / `'unsafe-inline'` | `script-src` now allows the single bootstrap one-liner by hash (`'sha256-/x7W7R75k8Roq0WaVRQX9blP4OufE5xbAdzklGxsgpw='`) instead of `'unsafe-inline'`. **HSTS rejected** for now: register Q-23 keeps HSTS off at launch (302 → 301 flip first); moving the whole CSP into PHP (C-60) is left for the stage-5 hardening pass. |
| `E_DEPRECATED` becomes a 500 | The error handler routes `E_DEPRECATED` / `E_USER_DEPRECATED` to `log_write('warning', …)` and returns; everything else still throws. `install.php` shows PHP ≥ 8.4 as *Note — tested on 8.2 and 8.3*. |
| Static `robots.txt` shadows the dynamic route | Kept the static file (register: "real static file"), deleted the `robots` route and the dead branch in `sitemap.php`; `install.php` rewrites the `Sitemap:` line from `base_url`. `site_indexable=0` is still enforced by `meta robots` and the 404 on `/sitemap.xml`. |

### Not changed / still open

- MariaDB 10.4 and the real `.htaccess` behaviour remain untestable locally (MySQL 9.3, `php -S`).
- The register's per-request X-Forwarded-For handling behind Cloudflare is only as good as the recorded `trusted_proxies`; the Settings › Advanced editor for it and for `base_url` is stage-4 work.
- Stage-2 JS must call `SF.fetch()` (or `SF.ensureCsrfToken()`) rather than reading the meta tag directly, because the token is now lazy.
- `PHP Warning: Module "imagick" is already loaded` is still this machine's `php.ini`.
