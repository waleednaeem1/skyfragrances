# Stage 1 — Application-security review

Scope: everything under `site/` as of 2026-09-25 (bootstrap, router, `app/lib/*`, storefront
controllers, `admin/`, `install.php`, `.htaccess` set, JS). Read as an attacker; only concrete,
reproducible findings are listed. Ranked by severity.

What held up well (no finding): every SQL statement goes through `db_query()` with named
placeholders and `db_identifier()` whitelisting; `LIMIT`/`ORDER BY` only take code constants;
every template output goes through `e()`/`ejs()`; every included PHP file starts with
`defined('SKYFR') || exit;`; admin gate runs before routing on every non-login path
(`admin/index.php:41`); admin CSRF is checked centrally for every POST before dispatch
(`admin/index.php:45-60`); login uses `password_verify` against a dummy hash for unknown users
(`auth.php:214`), regenerates the session and rotates the CSRF token on login, logout and
password change; uploads are validated by `finfo` + `getimagesize` type agreement and always
re-encoded through GD (`upload.php:36-51`, `image.php`); proof paths are regex-pinned
(`image.php:226`); `redirect()` cannot be fed `//host` because the path normaliser collapses
slashes and rejects backslashes; production `display_errors` is off and the 500 page only prints
a trace when `APP_ENV !== 'production'`.

---

## 1. HIGH — `install.php` accepts `db_save` and `install` without any authentication or state proof

**Where:** `site/install.php:314-340` (`inst_state`), `:669-729` (`inst_handle_db_save`),
`:731-815` (`inst_handle_install`), `:840-845` (dispatch).

**Attack.** The installer is world-reachable and its only guard is `inst_state()`. Two paths:

- *Unfinished-install window.* From the moment the ZIP is unzipped until step 3 completes,
  anyone who requests `/install.php` can `POST action=db_save` with **their own** MySQL host
  (`db_host` is unrestricted, `inst_pdo` connects anywhere), then `POST action=install` and
  create the admin account. The owner then sees "already installed" and is locked out; every
  order, customer phone and address goes to the attacker's database.
- *After install.* `inst_state()` returns `locked` only if `storage/.installed` exists, and
  `installed` only if the DB answers. Lock-file write failure is a *warning* (`:809-811`). So
  with the lock missing and the DB down or its password rotated, the state is `config_bad`,
  `$finished` is false, and `inst_handle_db_save` (which never checks `$state['stage']`)
  happily rewrites `config.php` — again pointing the live site at an attacker-controlled DB,
  whose `admin_users` row logs the attacker into `/admin`. The dashboard banner
  (`admin/controllers/dashboard.php:12`) shows install.php is *expected* to linger for a while.

**Fix.**
1. Fail closed: when `config.php` exists and the DB cannot be reached, show "cannot verify
   installation state — fix the database or delete config.php by hand" and refuse `db_save`.
2. Make the lock a hard requirement: write `storage/.installed` *before* inserting the admin
   row and abort the install if it cannot be written.
3. Bind the installer to the owner: at first hit, write `storage/.install-key` containing a
   random token and print "open File Manager, read this file, paste it here"; require it on
   every POST. This is the standard non-developer-safe pattern and costs one screen.
4. `inst_handle_install` must also refuse unless `$state['stage']` is `resume`/`partial` (it
   does) **and** the same key is present.

## 2. HIGH — Development artefacts sit inside the deploy tree and nothing excludes them

**Where:** `site/config.php` (env `development`, dev DB password, real `app_key`),
`site/storage/.installed`, 129 files in `site/storage/sessions/sess_*`, `site/dev/router.php`;
`.gitignore` covers only `site/config.php`, `site/uploads/*`, `storage/logs/*`, `storage/cache/*`.

**Attack / effect.** A ZIP built from `site/` as it stands ships:
- `config.php` with `'env' => 'development'` → `bootstrap.php:129,136` turns `display_errors`
  on and `bootstrap_render_500()` (`:68-70`) prints class, message, file:line **and the full
  stack trace** to every visitor. The first request fails to reach `127.0.0.1` MySQL, so the
  production home page is a stack trace on day one.
- `storage/.installed` → `inst_state()` returns `locked` and the installer refuses to run, so
  the owner cannot even fix the above through the UI.
- `storage/sessions/sess_*` → live session ids + CSRF tokens from the dev machine become valid
  session files on the server (`use_strict_mode` accepts any id whose file exists).

**Fix.** Add a build step/script that produces the ZIP from an allowlist and asserts
`config.php`, `storage/.installed`, `storage/sessions/*`, `storage/logs/*`, `storage/cache/*`,
`storage/proofs/*`, `dev/`, `.git*` are absent; add those paths to `.gitignore` now. Keep
`config.sample.php` as the only config in the archive (it is clean — no real secrets).

## 3. MEDIUM — Admin username lockout is a one-request denial of service

**Where:** `site/app/lib/auth.php:132-148` (`auth_lock_minutes`), `:7` (`AUTH_LOCK_USER_FAILURES = 5`),
`site/admin/controllers/auth.php:45-52`.

**Attack.** The username lock is keyed on the username alone. Five wrong passwords for `admin`
(the installer's prefilled default, `install.php:610`) from any IP — or five IPs — return 429
for **everyone** for 15 minutes, and the attacker can repeat forever with a fresh CSRF token
from `GET /admin/login`. The owner, who manages orders from a phone, is locked out at will.

**Fix.** Hard-lock on the `(username, ip_hash)` pair (add the pair to `admin_login_attempts`
lookups); keep the pure-username counter only for the progressive `usleep` (`:207-209`) and
for a warning on the dashboard. Keep the per-IP hard lock at 10. Note also that
`request_ip()` (`request.php:85`) is `REMOTE_ADDR` only — fine behind LiteSpeed directly, but
if Cloudflare is ever put in front, one attacker's 10 failures lock every visitor's login.

## 4. MEDIUM — `rate_limits` table exists but no public endpoint uses it

**Where:** `site/db/schema.sql:547` defines the table; no reference anywhere under `app/`.
Endpoints: `site/app/controllers/track.php:10-35`, `contact.php:36-47`,
`review-submit.php:38-49`, `api/newsletter.php:26-37`.

**Attack.** `/track` answers order number + phone with name, city, total, courier and tracking
number. Order numbers are `SF-YYMMDD-XXXX` (date is guessable, 4 chars over a 32-symbol
alphabet ≈ 1M); with a known phone number the whole space is enumerable in hours with no
throttle. `/contact` and `/product/{slug}/review` accept unlimited rows (review has no
honeypot either) → cheap DB-fill and moderation-queue flood. The register (§1, C-42, Q-06)
requires buckets `track|contact|newsletter|review`.

**Fix.** Add `rate_limit_hit(string $bucket, string $subjectHash, int $max, int $windowSeconds)`
in a new `app/lib/ratelimit.php` mirroring `auth_recent_failures`; call it at the top of the
four POST paths keyed on `request_ip_hash()` (track additionally on `sha256(order_number)`),
and answer `abort(429)`. Purge opportunistically like `auth_purge_attempts()`.

## 5. MEDIUM — Storefront CSRF depends on each controller remembering to call `csrf_check()`

**Where:** `site/index.php` (no central check); checks live in `contact.php:11-15`,
`track.php:11-16`, `review-submit.php:10-15`, `checkout-submit.php:4-9`, `api/cart.php:8-14`,
`api/newsletter.php:8-12`. `contact.php:11-15` catches the failure and *continues*, treating it
as a validation error.

**Attack.** The admin side is safe because `admin/index.php:45-60` verifies every POST before
dispatch; the storefront has the exact "per-route call a new page could forget" shape. Stage 3
adds `checkout-submit`, proof upload and coupon POSTs. In `contact.php` a forged request today
reaches the honeypot branch (`:16-20`) and only the final `$errors === []` guard prevents the
insert; any side effect added above that line (mail, logging) would run unauthenticated.

**Fix.** Move the check into `site/index.php` right after routing: for `POST` (any path)
verify `csrf_check(request_csrf_token_sent())`, answering JSON 419 for `/api/*` and a flash +
303 back to the referring path otherwise — identical to the admin block. Delete the
per-controller try/catch blocks. Make `csrf_check` failure in `contact.php` terminal.

## 6. LOW — Failed-login usernames are written to disk and DB

**Where:** `site/app/lib/auth.php:218-220` (`auth_log_activity`, `log_write('warning', ...,
['username' => $username])`), `:157-163` (`admin_login_attempts.username`).

**Attack.** The commonest failed login is the password typed into the username box. It ends up
in `storage/logs/app-YYYY-MM.log` and in two tables, readable by anyone who later gets a DB
dump or the log file. Log lines also survive the 7-day attempt purge.

**Fix.** Log `sha256(app_key | username)` (or nothing) in `log_write`; keep the plain
username only in `admin_login_attempts` where it is needed for the lock, and truncate it to the
first 3 characters + hash for the activity log summary.

## 7. LOW — Unthrottled current-password oracle on the password-change form

**Where:** `site/admin/controllers/password.php:7-28`, `site/app/lib/auth.php:281-292`.

**Attack.** A stolen or shoulder-surfed admin session can guess the current password without
limit at bcrypt speed; a hit lets the attacker set a new password and lock the owner out
permanently instead of merely riding the session until it expires.

**Fix.** Count wrong current-password attempts in `admin_login_attempts` (same username, IP)
and apply the same lock; log out the session after 5 failures.

## 8. LOW — Installer trusts the `Host` header

**Where:** `site/install.php:87-91` (`inst_request_base_url`), used at `:389`, `:470`, `:485`,
`:591`, `:626`, `:661`.

**Attack.** While install.php is live, `Host: attacker.tld` makes the server fetch
`http://attacker.tld/__rewrite-probe`, `/uploads/.probe-….php` and `/install.php` (blind
SSRF, leaks the probe filename), and prefills `base_url` in the form with the attacker's host;
an owner who clicks through gets `SITE_URL` (canonical, OG, emails, `redirect()` absolute
URLs) pointing off-site.

**Fix.** Derive the default `base_url` from `SERVER_NAME` with `UseCanonicalName On` in
`.htaccess`, or only accept `HTTP_HOST` values matching `^[a-z0-9.-]+(:\d+)?$` and require the
owner to confirm it explicitly; run the self-probes against `127.0.0.1` with a `Host` header
instead of the public URL.

## 9. LOW — Admin and shop sessions share one save path

**Where:** `site/app/bootstrap.php:168-172` (single `storage/sessions` for both `SFSHOP` and
`SFADMIN`).

**Attack.** With `session.use_strict_mode`, PHP accepts any id whose file exists in the save
path. Any visitor's `SFSHOP` id is therefore a valid pre-auth `SFADMIN` id. Not a takeover —
`auth_attempt()` regenerates the id on success — but it defeats the point of strict mode for
the admin cookie and lets a guest session's CSRF token be replayed against `/admin/login`.

**Fix.** `session_save_path(APP_ROOT . '/storage/sessions/admin')` when `SKYFR_ADMIN` is
defined (create the directory in `install.php`'s `INSTALL_RUNTIME_DIRS`).

## 10. LOW — Missing HSTS; CSP allows inline scripts

**Where:** `site/.htaccess:98-106`, `site/app/partials/head-meta.php:98`.

**Attack.** HTTPS is forced by 301 only; the first request of every visit is plaintext and
strippable. `script-src 'self' 'unsafe-inline'` exists for one line
(`document.documentElement.classList.add('js')`), so any future HTML injection executes.

**Fix.** Add `Header always set Strict-Transport-Security "max-age=31536000" env=HTTPS`; move
the one-liner into `assets/js/reveal.js` (first line, not deferred, or add the class in CSS via
`@supports`) and drop `'unsafe-inline'` from `script-src`. JSON-LD blocks are not executed and
do not need it.

---

### Not security, but noticed on the way

- `install.php:558/618` promise that unfinished tables "will be replaced", but `schema.sql`
  uses `CREATE TABLE IF NOT EXISTS`; only `rate_limits`, `email_outbox`, `order_notes` are
  dropped (`schema.sql:4-6`). Either drop-and-recreate on `partial`, or change the wording.
- `install.php:786` hashes with `PASSWORD_DEFAULT` (cost 10); `auth.php:223` re-hashes to
  cost 12 on first login — works, just be aware the first login is slower.
