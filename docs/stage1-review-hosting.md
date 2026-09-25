# Stage 1 review — Hostinger shared-hosting portability

Scope: the actual files under `site/` as of 2026-09-25 — `.htaccess` (root, `admin/`, `app/`, `db/`,
`storage/`, `assets/`, `uploads/`), `.user.ini`, `install.php`, `db/schema.sql`, `db/seed.sql`,
`app/bootstrap.php`, `app/lib/db.php`, plus the libs they pull in (`session`, `mail`, `image`,
`upload`, `request`, `url`, `settings`, `auth`) and `admin/index.php`.

Target: LiteSpeed Enterprise + lsphp 8.2 + MariaDB 10.4–11.x, extracted into `public_html` from
hPanel File Manager by a non-developer, no shell, no cron, no Composer.

What already holds up and is not repeated below: no `php_flag`/`php_value` anywhere; every `Require`
is paired with an `<IfModule !mod_authz_core.c>` `Order/Deny` fallback; the HTTPS force has all three
conditions (`%{HTTPS}`, `%{HTTP:X-Forwarded-Proto}`, `%{SERVER_PORT}`) and `[NE]`, and is repeated
verbatim in `admin/.htaccess` so the per-directory `RewriteEngine On` does not drop it; `SET time_zone`
uses `'+05:00'`; `schema.sql` is `utf8mb4_unicode_ci`, no `utf8mb4_0900_ai_ci`, no `JSON`, no `CHECK`,
no `ENUM`, no `CURRENT_TIMESTAMP` defaults, no functional defaults, every DDL statement is one
`CREATE TABLE IF NOT EXISTS` that MariaDB 10.4 and phpMyAdmin both accept; `seed.sql` is one `INSERT`
per line with no `DROP`; `install.php` splits on line-final `;` and no seed value contains one; there is
no `mail()` call — `mail.php` lazy-loads PHPMailer from `app/lib/vendor/PHPMailer/` and writes a
preview file when it is absent; `image.php` checks `imagetypes() & IMG_WEBP` before writing WebP and
`image_url()` falls back to `.jpg`; every path is built from `APP_ROOT`/`__DIR__`, nothing points
outside `public_html`, nothing shells out, no cron is assumed.

## Findings, most severe first

| # | Sev | Where | One line |
|---|---|---|---|
| 1 | blocker | `site/storage/.installed`, `site/config.php`, `site/storage/sessions/sess_*`, `site/dev/`; `install.php:315-319` | The tree that would be zipped already carries a completed local install; on Hostinger `install.php` refuses to run and the storefront shows development stack traces |
| 2 | high | `app/lib/request.php:109-125`, `admin/index.php:45-49`, `app/controllers/api/{cart,search,newsletter}.php:4` | Same-origin is checked against `config['base_url']`; an install on the Hostinger preview host bricks admin login and the cart API once the domain is pointed |
| 3 | high | `install.php:770-815` | Lock and `install_completed_at` are written after up to ~50 s of loopback HTTP probes; if the request dies the database is left in a state the installer calls "occupied" and refuses to touch |
| 4 | medium | `.htaccess:4-5,9,23,27`; `admin/.htaccess:3` | Absolute `/` paths break every pretty URL when the site is in a sub-folder — a case `install.php` and `bootstrap.php` explicitly claim to support |
| 5 | medium | `app/lib/request.php:85-88`; `app/lib/auth.php:150-153,203` | `REMOTE_ADDR` only; behind Hostinger CDN/Cloudflare every visitor shares one IP hash, so ten failed logins by anyone lock the owner out |
| 6 | medium | `.user.ini:5-7`; `app/lib/upload.php:4,128`; `admin/index.php:50-59` | `post_max_size = 48M` but the product form accepts 10 × 8 MB; PHP drops the whole body and the owner sees "This form expired" |
| 7 | medium | `app/lib/upload.php:7-8`; `app/lib/image.php:26-55,57-91,93-104`; `.user.ini:9` | A 40 MP source image needs ~160 MB in GD before the first derivative; the plan's `memory_limit` ceiling, not `.user.ini`, decides whether this is a fatal 500 |
| 8 | medium | `app/bootstrap.php:154-179` | Every storefront request (including every bot hit) creates a session file in a custom `save_path` with no GC probability set — files accumulate toward the inode quota |
| 9 | low | `app/bootstrap.php:140-146` | The error handler throws on `E_DEPRECATED`; the owner picking a newer PHP in hPanel turns one deprecation into a site-wide 500 |
| 10 | low | `robots.txt`, `.htaccess:28`, `app/routes.php:26`, `app/controllers/sitemap.php:51-56` | The static `robots.txt` shadows the dynamic route, so `site_indexable=0` never reaches robots.txt and the hard-coded `Sitemap:` host is wrong on a preview host or sub-folder |

---

### 1. BLOCKER — local install artefacts ship in the tree; `install.php` treats a stale lock as final

**Files.** `site/storage/.installed` (contains `2026-09-25 17:10:56`), `site/config.php`
(`'env' => 'development'`, `127.0.0.1` credentials, mode 0600), 129 files in
`site/storage/sessions/`, `site/dev/router.php`. `.gitignore` excludes `site/config.php`,
`storage/logs/*`, `storage/cache/*` and `uploads/*` — but **not** `storage/.installed`,
`storage/sessions/*` or `dev/`. Nothing is committed yet and there is no packaging script, so
"zip the `site/` folder" is the only build step that exists today.

**What breaks on Hostinger.**
- `install.php:315-319` — `inst_state()` returns `locked` the moment `storage/.installed` is a
  file, before looking at `config.php` or the database. The owner's very first visit shows
  "Sky Fragrances is already installed — delete install.php", with a working "Delete install.php
  for me" button. There is no database, no admin account, and no way forward that does not involve
  File Manager with hidden files switched on.
- If `config.php` ships too, `bootstrap.php:129-137` reads `env=development`, sets
  `display_errors=1`, and `bootstrap_render_500()` (line 68-70) prints the exception class, message,
  file path and full trace to the public — the message is "Database connection failed" against
  `127.0.0.1`, and the trace shows the account's absolute paths.
- The session files are inert but 129 of them land in `storage/sessions/` on day one.

**Fix.**
1. Add a packaging step (a documented exclusion list is enough for now, a small script later)
   that omits `config.php`, `storage/.installed`, `storage/sessions/*`, `storage/logs/*`,
   `storage/cache/*`, `dev/`, `.git`, `.DS_Store`, and keeps only `.gitkeep` inside every
   `storage/*` and `uploads/*` directory. Add `site/storage/.installed`, `site/storage/sessions/*`
   and `site/dev/` to `.gitignore` so a git export is equally clean.
2. Make `inst_state()` resilient: when `INSTALL_LOCK` exists but `config.php` is missing or does
   not connect, treat the lock as stale — `@unlink(INSTALL_LOCK)` and continue as `fresh`, with a
   one-line notice on step 1. A lock is only authoritative when the database it refers to says
   `install_completed_at`.
3. `inst_requirements()` should list "Leftover files from a previous copy" as a `fail` row when
   `config.php` connects to a host that is `127.0.0.1`/`localhost` while `HTTP_HOST` is not.

### 2. HIGH — same-origin is judged against `config['base_url']`, not the request

**Files.** `app/lib/request.php:109-125` (`request_origin_is_foreign()` compares the `Origin` or
`Referer` host with `parse_url(SITE_URL)['host']`); `admin/index.php:45-49` applies it to **every**
admin POST including `/admin/login`; `app/controllers/api/cart.php:4`, `api/search.php:4`,
`api/newsletter.php:4` apply it to the JSON API. `SITE_URL` comes from `config['base_url']`
(`bootstrap.php:131`), which `install.php:592` defaults to the URL the installer was opened on.

**What breaks on Hostinger.** Hostinger's onboarding hands the owner a working
`https://<name>.hostingersite.com` preview before DNS is changed; running `install.php` there is the
natural first move and it will pass every check. `base_url` is then written as the preview host.
After the domain is pointed, the browser sends `Origin: https://skyfragrances.com`; the host compare
fails; `admin/index.php:48` aborts 403 "Cross-site requests are not allowed" on the login POST
itself, and every add-to-cart returns 403. The only repair is editing a 0600 `config.php` in File
Manager. The same happens with the reverse order (install on `http://` before the certificate, or
on the apex and later moving to `www`). Emails, `og:url`, canonical and the sitemap also keep the
preview host.

**Fix.** In `request_origin_is_foreign()` compare against the request's own host
(`$_SERVER['HTTP_HOST']`, lower-cased, port stripped) — same-origin means "matches this request",
not "matches a config string". Keep `SITE_URL` for canonical/OG/email only, and add a
Settings › Advanced "Site address" field with an "opened at https://… — use that" hint so the owner
can fix `base_url` from a phone. `install.php:703-704` should also write `https://` + host whenever
the host is `skyfragrances.com` regardless of the scheme it was opened on.

### 3. HIGH — the install POST does its slow self-tests before writing the lock, and a half-finished install is a dead end

**Files.** `install.php:770-815` (`inst_handle_install`). Order of operations: schema (54
statements) → seed (235) → admin insert → settings → `inst_prepare_dirs()` → `inst_selftest_uploads()`
(one loopback GET, `CURLOPT_CONNECTTIMEOUT 4` + `CURLOPT_TIMEOUT 6`) → `inst_selftest_denied()`
(four loopback HEADs, 6 s each) → **then** `install_completed_at` (line 809) and `storage/.installed`
(line 810). `set_time_limit(120)` at line 8; `.user.ini:8` asks for 60.

**What breaks on Hostinger.** The five loopback requests go out through the account's own domain.
Before DNS points at Hostinger, or while the free certificate is still being issued, each one waits
the full connect/read timeout: 4–10 s × 5 ≈ 20–50 s, on top of ~5 s of SQL on a busy shared box.
`set_time_limit()` is honoured by lsphp only if the plan has not pinned `max_execution_time`, and
LiteSpeed's own external-app timeout can kill the worker regardless. If the request dies after line
787 and before line 809, the owner reloads and `inst_state()` (`install.php:336-340`) finds tables
**and** an admin row → stage `occupied` → "The database is not empty… The installer never deletes
existing tables" → the only exits offered are "enter a different database" or phpMyAdmin.
`inst_requirements()` (line 390) also runs a loopback probe on every step-1 page view, so step 1
itself stalls 10 s whenever DNS is not pointed.

**Fix.**
- Write `install_completed_at` and the lock immediately after the admin insert and settings
  (move lines 808-812 up to after line 798). The install is complete at that point; the probes are
  diagnostics.
- Run the probes from the step-4 page as a separate request (or three small fetches), each with
  `CURLOPT_CONNECTTIMEOUT 2` / `CURLOPT_TIMEOUT 3`, and show them as "Checks" that can be re-run.
- Make "tables + admin + no `install_completed_at`" a resumable state: offer "Finish the earlier
  install" that writes the marker and lock, instead of `occupied`.
- Skip the step-1 rewrite probe when `HTTP_HOST` does not resolve to the server (`gethostbyname()`
  compare against `SERVER_ADDR`), and say so.

### 4. MEDIUM — sub-folder installs are advertised but every `.htaccess` path is absolute

**Files.** `.htaccess:9` `RewriteBase /`; `.htaccess:4-5` `ErrorDocument 404 /index.php`,
`ErrorDocument 403 /index.php`; `.htaccess:23` `RewriteRule ^(.+?)/+$ $1 [L,R=301,NE]` (relative
substitution resolved against `RewriteBase`); `.htaccess:27` `RewriteCond %{REQUEST_URI}
^/(uploads|assets)/`; `admin/.htaccess:3` `RewriteBase /admin/`. Meanwhile `bootstrap.php:131-133`
derives `BASE_PATH`, `request.php:21-23` strips it, `install.php:384-388` explicitly allows a
sub-folder and only warns, and `install.php:790-791` computes `site_indexable` from it.

**What breaks on Hostinger.** In `public_html/shop/` (the obvious way to stage a copy next to a
holding page, or to test on a preview host that already has something at `/`): rule 3.6 rewrites
`/shop/cart` to `/index.php` — the account root, not the shop — so every pretty URL 404s or hits the
wrong site; `/shop/admin/login` rewrites to `/admin/index.php` (wrong file); the trailing-slash rule
301s `/shop/cart/` to `/cart`, dropping the prefix; `ErrorDocument … /index.php` points at a file that
does not exist there, so LiteSpeed emits its default error page (which names the server) instead of
the branded 404; and `/shop/uploads/x.webp` is no longer exempt from the front-controller rewrite
because the condition anchors on `^/uploads/`.

**Fix.** Delete both `RewriteBase` lines (Apache 2.4 and LiteSpeed compute the per-directory
prefix; the relative substitutions `index.php`, `$1`, `admin/index.php` then resolve correctly in
any folder). Change line 27 to `RewriteCond %{REQUEST_URI} /(uploads|assets)/` or, better, drop it
and rely on the `-f`/`-d` conditions that follow. Drop the two `ErrorDocument … /index.php` lines —
rule 3.6 already routes every non-file to the front controller, and `uploads/`/`assets/` override
with the quoted-string form — or have `install.php` rewrite them with `inst_base_path()`. Until
then, `install.php:387` should report a sub-folder as `fail`, not `warn`.

### 5. MEDIUM — `REMOTE_ADDR` is the client IP; behind Hostinger's CDN it is the CDN

**Files.** `app/lib/request.php:85-88` (`request_ip()`), `:90-93` (`request_ip_hash()`);
`app/lib/auth.php:150-153` (`auth_is_locked()`), `:132-148` (`auth_lock_minutes()` — 10 failures per
`ip_hash` in 15 min), `:203`; `admin/controllers/auth.php:45,49`; `contact.php:45`; every future
`rate_limits` row.

**What breaks on Hostinger.** Business-tier accounts have the Hostinger CDN switched on by default
and many owners put Cloudflare in front; in both cases lsphp sees the edge node's address in
`REMOTE_ADDR` and the visitor in `X-Forwarded-For` / `CF-Connecting-IP`. Every visitor then shares
one `ip_hash`: ten wrong passwords from *anyone* — a bot probing `/admin/login` does this in
seconds — lock the owner out for 15 minutes, repeatedly; every public rate limit collapses to a
single site-wide bucket; the `ip_hash` audit columns are worthless.

**Fix.** Implement `client_ip()` as 02b §2.3 specified: trust the rightmost `X-Forwarded-For`
hop (or `CF-Connecting-IP`) **only when** `REMOTE_ADDR` is inside a trusted-proxy list that
`install.php` records from its own probe request (`config['trusted_proxies']`, editable in
Settings › Advanced); otherwise use `REMOTE_ADDR` and ignore the header. Never read the header
unconditionally.

### 6. MEDIUM — `post_max_size` is smaller than one product-image form, and the failure is reported as a CSRF problem

**Files.** `.user.ini:5-7` (`upload_max_filesize = 8M`, `post_max_size = 48M`,
`max_file_uploads = 20`); `app/lib/upload.php:4` (`UPLOAD_MAX_PRODUCT_BYTES = 8388608`), `:128`
(`array_slice($files, 0, 10)` — ten files per request); `admin/index.php:50-59` (CSRF check runs
first on every POST); nothing reads `CONTENT_LENGTH`.

**What breaks on Hostinger.** Ten phone photos at 5–8 MB each is a 50–80 MB multipart body. PHP
does not truncate it — it discards `$_POST` and `$_FILES` entirely and logs a warning the owner
cannot see. `admin/index.php:51` then finds no `_csrf`, flashes "This form expired. Please go back
and try again." and redirects. The owner retries the same ten photos and gets the same message.
`.user.ini` values are also capped by the plan's `php_admin_value`, so 48M may itself be silently
lowered.

**Fix.** Either raise `post_max_size` to at least `88M` (10 × 8M + headroom) and have
`install.php` fail when `ini_get('post_max_size')` is below `UPLOAD_MAX_PRODUCT_BYTES × 10`, or cap
the product form at 5 files. In `admin/index.php`, before `csrf_check()`: if
`CONTENT_LENGTH > ini_get('post_max_size')` (parsed), flash "Your upload was too large — the limit is
X MB per save. Add fewer photos at a time." and redirect back. That is the only place the true cause
is knowable.

### 7. MEDIUM — the 40 MP image cap is a memory bomb on a plan with a lower `memory_limit`

**Files.** `app/lib/upload.php:7-8` (`UPLOAD_MAX_SIDE = 8000`, `UPLOAD_MAX_PIXELS = 40000000`);
`app/lib/image.php:26-55` (`image_decode()`), `:57-91` (`image_cover()` allocates a second
truecolor image), `:93-104` (`image_flatten()` allocates a third per derivative), `:135-148`
(`image_write_sizes()` — three derivatives, each JPEG + WebP); `.user.ini:9` (`memory_limit =
256M`); `install.php:343-398` checks extensions but never reads `memory_limit`.

**What breaks on Hostinger.** GD holds truecolor pixels at 4 bytes each plus libjpeg's scanline
buffers: a 40 MP source is ~160 MB resident before the first `image_cover()`, ~170 MB with the
1400×1750 zoom derivative and its flattened copy in flight. Hostinger pins `memory_limit` per plan
through LiteSpeed's `php_admin_value`; `.user.ini` cannot raise it above that ceiling, and the owner
may not have touched hPanel › PHP Configuration. Below ~192M the request dies with "Allowed memory
size exhausted", `bootstrap_handle_shutdown()` renders the 500 page, and the product row may already
be saved without images. The same photo uploads fine on the developer's laptop.

**Fix.** Before `image_decode()`, estimate `width × height × 5 + 16 MB` from the `getimagesize()`
result already in hand (`upload.php:45-54`) and compare with `ini_get('memory_limit')`; reject
with "This photo is too large for the server to resize (N MP). Please upload one under M MP." Lower
`UPLOAD_MAX_PIXELS` to 20,000,000 (5000×4000) so a 128M plan still works. Add an `install.php`
requirement row that prints the effective `memory_limit` and fails below 128M.

### 8. MEDIUM — unconditional storefront sessions in a custom `save_path` with no garbage collection configured

**Files.** `app/bootstrap.php:154-179` — `session_save_path(APP_ROOT.'/storage/sessions')`,
`ini_set('session.gc_maxlifetime','259200')`, then `session_start()` on every request, GET or POST,
bot or human. Nothing sets `session.gc_probability` / `session.gc_divisor` (grep confirms). The dev
tree already holds 129 files after one afternoon.

**What breaks on Hostinger.** With a custom `save_path` no host-side sweeper ever looks at the
directory; PHP's own probabilistic GC is the only cleaner, and it runs only if
`session.gc_probability > 0`. Many php.ini builds ship `gc_probability = 0` and rely on a cron
that only knows the system session directory. Every Googlebot, Bingbot, uptime check and scraper hit
then leaves a file behind forever. Hostinger plans have an inode quota shared with the whole account;
hitting it takes down email and the site, not just carts.

**Fix.** In the same block: `ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');`. Start the shop session lazily — only when an `SFSHOP` cookie
is present, the method is POST, or the path starts with `/api/cart` — so a first-time GET writes no
file and sets no cookie (this also removes `Set-Cookie` from cacheable HTML). Keep the admin path
unconditional.

### 9. LOW — deprecations are promoted to fatal 500s, and the owner chooses the PHP version

**Files.** `app/bootstrap.php:135-146` — `error_reporting(E_ALL)` then a `set_error_handler` that
throws `ErrorException` for anything the mask allows, including `E_DEPRECATED` and
`E_USER_DEPRECATED`. `.user.ini:4` tries to exclude `E_DEPRECATED` but line 135 overrides it.
`install.php:346-349` only requires `>= 8.2.0`.

**What breaks on Hostinger.** hPanel › PHP Configuration offers 8.2, 8.3 and 8.4 side by side and
Hostinger periodically nudges the default upward. The code passes the two known 8.4 compile-time
checks (both nullable defaults use `?Type`), but any runtime deprecation in a lib or a future
PHPMailer drop-in becomes an exception, and one exception is a site-wide 500 with an incident id
the owner cannot act on.

**Fix.** Exclude `E_DEPRECATED | E_USER_DEPRECATED` from the throwing branch and route them to
`log_write('warning', …)` instead. Have `install.php` show the PHP version as `warn` above 8.3 with
"tested on 8.2 and 8.3".

### 10. LOW — the static `robots.txt` shadows the dynamic one

**Files.** `robots.txt` (static, `Allow: /`, `Sitemap: https://skyfragrances.com/sitemap.xml`);
`.htaccess:28` (`-f` serves it before the front controller); `app/routes.php:26` and
`app/controllers/sitemap.php:51-56` (a route that honours `site_indexable` and `canonical()`);
`install.php:797-801` sets `site_indexable = 0` on any non-canonical host and tells the owner
"Search engines will be told not to index this copy".

**What breaks on Hostinger.** On the preview host or a sub-folder the static file is what crawlers
read: `Allow: /` and a `Sitemap:` line pointing at the production domain. The `<meta name="robots"
content="noindex,nofollow">` from `head-meta.php:15-17` still protects pages, so this is a
consistency and messaging problem, not an indexing leak — but the dynamic route is dead code and
the register's "real static file" ruling and the shipped route contradict each other.

**Fix.** Pick one. The route is already written and correct; delete the static file and amend the
register (or keep the static file and delete the route plus the `Sitemap:` line). If static stays,
`install.php` should rewrite the `Sitemap:` host from `base_url`.

---

## Checked and found acceptable (for the record)

- `db/schema.sql` on MariaDB 10.4: all index prefixes ≤ 760 bytes under `utf8mb4` (DYNAMIC row
  format default), `DECIMAL(10,2) UNSIGNED` and `TINYINT(1)` only raise deprecation *warnings* on
  MySQL 8.0.17+, `ON DELETE SET NULL` targets are all nullable, FK parents are created before
  children and dropped after. `install.php:439` `ON DUPLICATE KEY UPDATE` with distinct placeholders
  is valid on both engines (no `VALUES()` alias syntax).
- `uploads/.htaccess`: `RemoveHandler`/`RemoveType`/`AddType text/plain` are belt-and-braces; the
  `<FilesMatch> Require all denied` block is what LiteSpeed is guaranteed to honour, and
  `install.php:463-480` verifies it at runtime.
- `config.php` written at mode 0600 is readable because lsphp runs as the account user.
- `storage/`, `app/`, `db/` denies use directory-level `Require all denied` plus a per-extension
  `<FilesMatch>`; root `.htaccess:53` additionally denies `.user.ini`, `*.sql`, `*.log`, `*.md` by
  name so a lost sub-directory `.htaccess` does not expose them.
- `install.php` SQL work is small (54 + 235 statements); the time risk is entirely the loopback
  probes (finding 3).
