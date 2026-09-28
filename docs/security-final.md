# Security — final hardening pass (stage 5)

Date: 2026-09-29. Tree: `site/` at the state described by `git status` on that day (motion layer
included). Local harness: PHP 8.2.28 built-in server, `php -d display_startup_errors=0 -S
127.0.0.1:8105 dev/router.php` (the shared MySQL database was read, never reset), plus three
throw-away servers of my own on 8106–8108 for the tests that the router cannot express. Every
`.htaccess` claim below is a reading of the file, because `php -S` does not interpret them; every
PHP-sent header is a `curl -D -` capture. Register `08-decisions-register.md` §2.4 rules (C-46,
C-47, C-52, C-53, C-54, C-60, C-64, C-70, C-72, C-74, C-77) are the reference; where a spec
section says something else, the register wins.

## 0. What the earlier reviews deferred to this pass

| Deferred item | Source | Outcome |
|---|---|---|
| Move the whole CSP into PHP (C-60) | stage1-run-report "Low → HSTS / unsafe-inline" | Already done in `app/lib/response.php` (`response_security_headers()`, called from `bootstrap.php` for every non-CLI request, admin included). Verified on the wire (§1) and hash-checked (§2). `.htaccess` carries no CSP, Referrer-Policy, Permissions-Policy or X-Frame-Options. |
| HSTS | stage1-review-security #10, register Q-23 | Deliberately still absent from PHP and `.htaccess` (302→301 flip first, then one `.htaccess` line after 14 days of padlock, 02b §6.3). Nothing to do here. |
| Settings › Advanced editors for `trusted_proxies` / `base_url` | stage1-run-report "still open" | Owned by the admin run; `request_trusted_proxies()` reads `settings.trusted_proxies` first and falls back to `config['trusted_proxies']`. |
| Router must refuse the private trees like the host does | this task | Router 403s them (§3); two gaps in `dev/router.php` are listed in §9 for its owner. |

## 1. Security headers — PHP-sent, captured with curl on :8105

`GET /`, `/shop`, `/this-does-not-exist` (404), `/api/session` (JSON) and `/admin/login` all carry:

```
Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none';
  form-action 'self'; script-src 'self' 'sha256-/x7W7R75k8Roq0WaVRQX9blP4OufE5xbAdzklGxsgpw=';
  style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data:; connect-src 'self'; frame-src 'none'
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: strict-origin-when-cross-origin      (admin: same-origin)
Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), interest-cohort=()
```

`upgrade-insecure-requests` is appended only when `APP_ENV === 'production'` (confirmed on the
production-mode copy in §4, where it is present). `X-Powered-By` is absent on every PHP response
(`header_remove()` in `response_security_headers()`; `php -S` would otherwise add it — the bare
probe in §4 shows the header when bootstrap is bypassed, proving the removal is ours). No
`Strict-Transport-Security` anywhere. Admin responses add `Cache-Control: no-store, no-cache,
must-revalidate, private` and `X-Robots-Tag: noindex, nofollow`; the `SFADMIN` cookie is
`path=/admin; HttpOnly; SameSite=Strict`, the `SFSHOP` cookie `path=/; HttpOnly; SameSite=Lax`;
`Secure` is added by `session_set_cookie_params()` when `APP_ENV` is production (and by
`.user.ini` `session.cookie_secure = 1` as belt and braces). A first GET of `/` or `/shop` sets no
cookie at all (lazy session, C-53).

Static responses are the server's job. Reading `site/.htaccess`: inside `<IfModule mod_headers.c>`
a `<FilesMatch "\.(css|js|woff2|svg|png|jpe?g|webp|ico|txt|xml)$">` block sends
`X-Content-Type-Options: nosniff` and `Cross-Origin-Resource-Policy: same-origin` (`always`), the
cache block sends `Cache-Control: public, max-age=31536000, immutable` for the same extensions,
and both `Header unset X-Powered-By` / `Header always unset X-Powered-By` are present. That is
exactly the C-60 split: one owner per header, no CSP in `.htaccess`.

## 2. The CSP hash and the inline-script inventory

`printf '%s' "document.documentElement.classList.add('js');" | openssl dgst -sha256 -binary | base64`
→ `/x7W7R75k8Roq0WaVRQX9blP4OufE5xbAdzklGxsgpw=`, identical to `CSP_BOOTSTRAP_SCRIPT_HASH` in
`app/lib/response.php` and to the `<script>` on `app/partials/head-meta.php:122`.

Tree-wide grep (`app admin index.php install.php cron.php`, vendor excluded):

- `<script>` without `src=`: only the hashed bootstrap and `product.php:263`
  `<script type="application/json" id="sf-sizes">` (data block, not executed, printed through `ejs()`).
  JSON-LD blocks are `type="application/ld+json"` and `ejs()`-encoded.
- Inline event handlers (`onclick=` …): none in the app or admin. One `onclick="this.select()"`
  on the installer's config-source textarea (`install.php:1116`); the installer sends no CSP and
  deletes itself, so it is not governed by the policy.
- `javascript:` URLs: none. External `src=`/`href=` resources: none (fonts and images are
  self-hosted, C-32). No `eval`, `new Function`, `blob:` or Workers in `assets/js/**` including the
  vendored GSAP/ScrollTrigger/Lenis and `motion/*.js`, so `script-src 'self'` plus the one hash
  covers the motion layer without `'unsafe-eval'`.
- Inline `style=` attributes exist (product hero, meters); `style-src 'unsafe-inline'` is the
  documented allowance (07 B.3.6).

`dev-tools/build-zip.php` now recomputes the hash from `head-meta.php` and refuses to build if
`response.php` does not carry it, and refuses any view/partial that grows a second inline
`<script>` (§7).

## 3. The private trees are refused by all three layers

**Layer 1 — `.htaccess` (read).** Root rule 3.0 `<FilesMatch "(^\.|\.(disabled|sql|md|log|bak|old|swp|dist|lock)$|^config.*\.php$)">` denies every dotfile (`.user.ini`, `.htaccess`, `.installed`, `.gitkeep`), every dump/backup/doc extension and `config*.php` regardless of directory. `app/.htaccess`, `db/.htaccess`, `admin/controllers/.htaccess`, `admin/views/.htaccess`, `admin/partials/.htaccess` are the C-54 deny text (`Require all denied` + `Order/Deny` fallback + extension `<FilesMatch>`); `storage/.htaccess` uses `<FilesMatch ".">` (C-74) so extensionless `sess_*` files and `<32hex>.jpg` proofs are caught. `uploads/.htaccess` is the C-72 text (§5). `admin/.htaccess` carries headers only — no `RewriteEngine`, so the root HTTPS/www rules still apply to `/admin/*`. `ErrorDocument 403 /index.php` turns every deny into the branded 404; `index.php` now treats a `REDIRECT_STATUS` of `403` exactly like `404` (`/__missing__`), so the error subrequest never re-enters routing, never 301-canonicalises a mixed-case probe and never echoes the probed path. Simulated on :8108 with a router that sets `REDIRECT_STATUS=403` before handing the request to `index.php`, against the real tree:

```
/app/bootstrap.php                 404 Not Found  no Location  title "Page Not Found"  path not in body
/App/Bootstrap.php                 404 (previously a 301 to the lower-case path)
/storage/proofs/2026/09/abc.jpg    404
/config.php  /.user.ini  /db/schema.sql  /admin/controllers/dashboard.php   all 404
/api/cart                          200 (API paths keep their JSON behaviour, unchanged)
```

**Layer 2 — the dev router, which stands in for the host on `php -S` (:8105).** All plain-text `403 Forbidden`, 9 bytes, nothing of the file: `/app/bootstrap.php`, `/app/`, `/app`, `/app/lib/db.php`, `/db/schema.sql`, `/db/seed.sql`, `/db/`, `/db/sample-manifest.php`, `/storage/.htaccess`, `/storage/`, `/storage/logs/app-2026-09.log`, `/storage/.installed`, `/storage/sessions/shop/`, `/config.php`, `/config.sample.php`, `/.user.ini`, `/.htaccess`, `/.gitignore`, `/uploads/.htaccess`, `/uploads/x.php`, `/admin/.htaccess`, `/dev/router.php`, `/dev/zip-manifest.php`, `/app/tools/reset-password.php`, `/README.md`, `/app/../config.php`. `/%2e%2e/config.php` → 404 (decoded `..` is refused before anything else). `/cron.php` and `/cron.php?key=wrong` → 404 (`hash_equals` against `config['security']['cron_key']`). `/uploads/shell.php.jpg` → 404 (does not exist; on the host `RemoveHandler .php` plus GD re-encoding and server-generated names make such a file impossible anyway). Two router gaps are recorded in §9: it answers 403 rather than the host's branded 404, and it routes `/admin/controllers/*.php` into the admin front controller (302 → login) instead of denying it — never executing the file, but not the same shape as `admin/controllers/.htaccess`.

**Layer 3 — the `SKYFR` guard, with no `.htaccess` and no router at all** (bare `php -S 127.0.0.1:8106` on the tree — the "AllowOverride lost" case). Direct requests to `app/bootstrap.php`, `app/lib/db.php`, `app/lib/auth.php`, `app/controllers/checkout-submit.php`, `app/views/layout.php`, `app/partials/head-meta.php`, `admin/controllers/dashboard.php`, `admin/views/layout.php`, `admin/partials/table.php`, `db/sample-manifest.php`, `app/emails/order-customer.php`, `app/routes.php`, `app/router.php`, `config.php` and `config.sample.php` each return **200 with a 0-byte body** — `defined('SKYFR') || exit;` runs before any include, connection or output. The five stubs (`app/`, `db/`, `storage/`, `admin/controllers|views|partials/index.php`) return 404 with an empty body. In that same lost-`.htaccess` scenario `db/schema.sql`, `.user.ini` and `storage/.htaccess` *are* served (200) — which is precisely why layers 1 and 2 exist and why `install.php` probes them (Step 4 "Private file" rows now include `.user.ini`). `app/tools/reset-password.php` has no guard by design (C-64: it is copied to the root and run standalone); requested in place it renders its own 403 "create the nonce file in storage/" page and reveals nothing an attacker without File Manager can use.

Guard coverage scan: every `.php` under `app/**`, `admin/controllers|views|partials/**` and `db/sample-manifest.php` (vendor and `app/tools/` excepted) starts with the guard — `intro.php` carries it inline on line 1; the only files without it are the six stubs and `reset-password.php`. `build-zip.php` now fails the build if that ever changes (§7).

## 4. Production mode: `display_errors` off and the friendly 500

Done on a copy of the tree in the scratchpad (never on the shared tree): `config.php` rewritten with `env => production` and a deliberately wrong database user/password, served by `dev/router.php` on :8107.

- `GET /` → **HTTP 500**, `Cache-Control: no-store`, the full header set of §1 plus `upgrade-insecure-requests`. Body 783 bytes: "Something went wrong … Reference: `e3342f74`". Grep for `trace|/Users/|/private/|PDOException|RuntimeException|SQLSTATE|<pre>|.php:[0-9]|Access denied` → **0 hits**. The same request in development mode prints the class, message, file:line and trace inside `<pre>` (`bootstrap_render_500()`, guarded by `APP_ENV !== 'production'`).
- `storage/logs/app-2026-09.log` in the copy received one `error incident=e3342f74 RuntimeException: Database connection failed | {"file":…,"line":…,"trace":…}` line — the detail lands in the denied `storage/` tree, keyed by the reference shown to the visitor. `php-error.log` was not created because the handler converts every notice/warning to `ErrorException` before PHP's own logger sees it; `ini_set('error_log', APP_ROOT.'/storage/logs/php-error.log')` covers whatever escapes.
- A bare probe outside bootstrap with `ini_set('display_errors','0')`: an undefined variable and an undefined function produce a 500 with **no** warning text and **no** fatal text in the body; flipping the probe to `display_errors=1` prints both with absolute paths — the contrast proves the switch is what hides them. `.user.ini` ships `display_errors = Off`, `display_startup_errors = Off`, `log_errors = On`; `bootstrap.php` sets the same three with `ini_set()` from `config['env']` so the outcome does not depend on the host honouring `.user.ini` (300 s cache, pool overrides).
- `bootstrap_handle_shutdown()` renders the same page for `E_ERROR|E_PARSE|E_CORE_ERROR|E_COMPILE_ERROR|E_USER_ERROR` and for a controller that returned without output; `E_DEPRECATED` is logged, not thrown (stage-1 hosting #9).

## 5. `uploads/.htaccess` — canonical text, byte-identical everywhere

- `sha256(site/uploads/.htaccess)` = `36dbea19158d9d0fdcb20cc2ce1fad44245cd00671847930889e5401efee9afb`, 966 bytes.
- The `INSTALL_UPLOADS_HTACCESS` heredoc in `install.php` extracted by regex: same 966 bytes, same digest — **byte-identical**.
- 02b §5 (the C-72 canonical block) with its `#` comment lines stripped and blank lines collapsed diffs clean against the file (`diff -B -w` → no output): `ErrorDocument 404 "Not found"`, `RemoveHandler`/`RemoveType` for 14 extensions, `<IfModule mod_mime.c> AddType text/plain …`, the case-insensitive deny `<FilesMatch>` including `htaccess|htpasswd`, and the image allow-list with its `Header` lines inside `<IfModule mod_headers.c>`; no `Options`, no `-ExecCGI`, no `php_flag`.
- The other eight literals in `install.php` (`INSTALL_DENY_HTACCESS` ×5, `INSTALL_STORAGE_HTACCESS`, `INSTALL_ASSETS_HTACCESS`, `INSTALL_ADMIN_HTACCESS`) are each identical to the shipped file. `inst_write_protection_files(true)` rewrites all nine on install, so a ZIP tool that drops dotfiles is repaired, not merely reported.
- `build-zip.php` re-does this comparison against the ZIP's own entries on every build (§7).

## 6. Source scans

**Unprepared SQL.** Every statement goes through `db_query()` (named placeholders only — a positional one throws; `PDO::ATTR_EMULATE_PREPARES => false`) or `db_insert/update/delete`, whose identifiers pass `db_identifier()` (`^[a-z_][a-z0-9_]{0,63}$`). Interpolation grep (`{$…}` or `' . $var` on lines carrying `SELECT|INSERT|UPDATE|DELETE|WHERE|ORDER BY|LIMIT`) found:

| Site | Verdict |
|---|---|
| `app/lib/auth.php:136` `WHERE {$col} = :value{$pairClause}` | **Rewritten**: two literal statements chosen by `match`, unknown column throws; `auth_recent_logins()` now binds `LIMIT :limit` as an int instead of concatenating. Exercised read-only against the shared DB (username / ip_hash / pair clause / bound LIMIT / refused column) — all correct. |
| `app/lib/orders.php:142,164` `WHERE {$column} = :value` | Safe, not mine: `$column` is a literal from an in-code array (`phone_normalized` / `ip_hash`) or forced to `phone_normalized` by `in_array`. Flagged in §9 so its owner can make it grep-clean the same way. |
| `ORDER BY ' . $orderBy` / `LIMIT ' . $per` in the admin list controllers, `listing.php:193`, `home.php:67-68`, `product.php`, `quiz.php`, `mail.php:297` | Safe: every `$orderBy`/`$order` is the result of a `match`/allow-list, every limit is a clamped `(int)` or a `const`; `IN (…)` lists are built from `count()` (`listing_in_list`, `order-actions.php:598/627`). |
| `install.php`, `app/tools/reset-password.php` | Their own PDO with prepared statements; `$pdo->exec()` only for the shipped SQL files and fixed `SET` lines. |

**Output not wrapped in `e()`.** `e()` is `htmlspecialchars(ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML5)`; `ejs()` is `json_encode` with `JSON_HEX_TAG|AMP|APOS|QUOT`. Every `<?= … ?>` and `<?php echo …` that is not `e()`/`ejs()`/`money()`/`csrf_field()` was traced:

| Raw echo | Why it is safe |
|---|---|
| `$csrf` (cart/checkout), `$trapField`, `$checkIcon`, `$starPath`, `$seasonIcons[…]` | code-built strings (`csrf_field()`, `form_trap_field()`, SVG constants, fixed icon table) |
| `$attrLines(…)` (product.php) | closure = `str_replace("\n", '&#10;', e($text))` |
| `$invalid(…)` (checkout.php), `$badge(…)`, `$moneyHtml(…)` (admin) | closures that emit fixed attribute strings / partials over `e()`d values |
| `$headingTag`, `$titleTag`, `$tag` | tag names forced through `in_array([...h1,h2,h3 / a,span])` |
| `$content` (both layouts), `$extra` / `$attrs` (admin field/button), `card.chip` / `card.sub` (admin table) | rendered HTML assembled from `e()`d parts by the producing view/partial |
| `$bodyHtml` (page.php, page-faq.php), `$item['answer']` | `sanitize_html()` on render (C-62) — allow-listed tags, `href` only, `rel="nofollow noopener"`; FAQ pairs are split from that sanitised body |
| `mail_button/mail_items_table/mail_totals_table` | build their HTML with `e()` on every dynamic value |
| `sitemap.php` `echo $body`, `maintenance.php` `echo $html`, `order-print.php` `echo $html` | XML/HTML assembled from `e()`d values |

No `echo`/`print` of a raw request or database value exists outside those. CSV exports prefix `= + - @ \t \r` cells (`orders-export.php:14`, `subscribers.php:45`).

**SKYFR guard.** See §3 — complete, and enforced by the build.

## 7. Secrets, `.gitignore`, the ZIP

- `config.sample.php`: six `REPLACE_ME` values (db name/user/pass, smtp pass, `app_key`, `cron_key`), `env => production`, `trusted_proxies => []`, guard line present; the only non-placeholder strings are the public SMTP host and the `orders@` address. `install.php` writes real keys with `inst_random_hex(32)` / `inst_random_hex(16)`, guard + `return`, then `chmod 0400` (C-70); its error re-render strips `db_pass`, `smtp_pass`, `admin_password*`, `_token`, `install_key` from the echoed form values.
- Tracked files: no 64-hex string in any tracked `.php`/`.ini`; `site/config.php`, `storage/.installed`, `.install-key`, `.install-probe`, `MAINTENANCE`, `storage/{logs,cache,sessions,proofs}/*`, `uploads/products/*` (except `sample/`), `uploads/og/*` (except `sample/`), `uploads/settings`, `site/default.php`, `dist/`, `*.zip`, `.DS_Store`, `dev-tools/node_modules/` are all ignored (`git check-ignore -v` confirmed each). `site/dev/` is tracked on purpose (developer tooling) and excluded from the ZIP.
- `php dev-tools/build-zip.php` → `dist/skyfragrances-20260928-1938.zip` (+ the `public_html` flat and folder-wrapped copies), 595 files added, 277 local files left out, **638 entries**. `unzip -l`: exactly **10** `.htaccess` (root, `app`, `db`, `storage`, `uploads`, `assets`, `admin`, `admin/controllers|views|partials`), `.user.ini`, `config.sample.php`, `install.php`, `cron.php`, `robots.txt`, `storage/` with its stubs and empty `logs cache sessions/shop sessions/admin proofs`, the sample derivatives, and the whole motion layer (`assets/js/motion/*` ×10, `assets/js/vendor/*`, `assets/css/motion.css` + `assets/css/motion/*`, `assets/js/intro.js`). Absent: `config.php`, `dev/`, `router.php`, `.installed`, `.install-key`, `sess_*`, `*.log`, `*.md`, `.DS_Store`, `__MACOSX`, any `.php` under `uploads/`. The extracted archive greps clean for `'pass' =>` (only the two `REPLACE_ME` lines and the installer's own form handling) and for the local database names / dev ports.
- New assertions in `build-zip.php`, each rehearsed with a planted file and seen to abort with the ZIP deleted: (1) every `.htaccess` in the ZIP equals its `install.php` literal; (2) `head-meta.php` has exactly one inline `<script>` and its SHA-256 is the constant in `response.php`; (3) no view/partial other than `head-meta.php` carries an inline `<script>`; (4) every guarded-tree `.php` starts with the `SKYFR` guard; (5) no entry ends in `.md .log .bak .orig .tmp .swp .old .dist ~`, none contains `sess_`, no `.php`-like file under `uploads/`, no `.sql` outside `db/`. Negative tests: `uploads/zz.php` → "must never ship"; `app/lib/zz.php` without the guard → "missing the SKYFR guard"; a partial with `<script>alert(1)</script>` → "inline <script> the CSP would block". Clean build afterwards.

## 8. Cross-check against 02c §1/§4/§8 and 07 B.3 (read, not re-tested unless stated)

- Bootstrap order (02c §1): config fail-closed 503 → error handling before anything can throw → libs → headers → maintenance gate (flag file or setting, bypass by POST form + HMAC cookie, C-70/C-76) → settings → lazy session. Sessions: `SFSHOP`/`SFADMIN`, `use_strict_mode`, `use_only_cookies`, `cookie_httponly`, separate `storage/sessions/shop` (3 d) and `/admin` (12 h), `gc_probability 1/100`, regeneration only at admin login, password change and the 30-minute storefront rule (`session_regenerate()` also rotates the CSRF token). Admin idle 2 h / absolute 12 h / UA-hash checks in `auth_session_expiry_reason()`.
- CSRF (02c §4): one `random_bytes(32)` token per session, `hash_equals`, checked centrally in `index.php` (every POST after routing; JSON 419 for `/api/*`, flash + 303 otherwise) and in `admin/index.php` (foreign-origin 403 → `request_body_too_large` 413 → CSRF 419), origin compared with the request's own scheme+host (C-49).
- Rate limits: buckets `checkout track proof contact newsletter review coupon` (register §1.2 + C-66), subject `sha256(app_key|value)`, 1-in-20 purge at 30 days; `client_ip()` trusts `X-Forwarded-For` only from `trusted_proxies` (C-50).
- Uploads (07 B.3.4): 6 MB product / 5 MB proof, `finfo` + `getimagesize` type agreement, 50–10,000 px, 40 MP cap and a runtime pixel budget from `memory_limit`, one file per request, proof ≤ 4000 px; re-encoding, random names and `storage/proofs/` streaming are in `image.php`/`orders.php` (stage-2 run report: `GET /storage/proofs/…jpg` → 403).
- Admin brute force (07 B.3.9 as amended by C-51): IP hard lock 10/15 min, username 2 s delay from 5 failures unless the `SFDEV` device cookie is known, dummy-hash `password_verify` for unknown users, bcrypt cost 12 rehash, username never logged in clear.
- `.user.ini` is the register text (C-46) plus `max_file_uploads = 3`; no `expose_php`, no `php_flag` anywhere.

## 9. Changes made in this pass (owned files only)

| File | Change |
|---|---|
| `site/app/lib/auth.php` | `auth_recent_failures()` uses two literal statements instead of `{$col}` interpolation and throws on an unknown column; `auth_recent_logins()` binds `LIMIT :limit`. |
| `site/index.php` | `REDIRECT_STATUS` `403` is handled like `404` (error subrequests render the branded 404 without routing or redirecting). |
| `site/install.php` | Step-4 "Private file" probes include `.user.ini`; installer pages send `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: no-referrer`. |
| `dev-tools/build-zip.php` | The five assertions in §7. |

Left as found after review: every `.htaccess`, `.user.ini`, `bootstrap.php`, `session.php`, `csrf.php`, `ratelimit.php`, `upload.php`, `config.sample.php`. `app/lib/errors.php` and `app/lib/log.php` named in the ownership list do not exist — the handlers and `log_write()` live in `bootstrap.php` and were reviewed there.

## 10. Residual risks and hand-offs

1. **`dev/router.php` (not owned here):** answers `403 Forbidden` in plain text where the host produces the branded 404, and routes `/admin/controllers|views|partials/*.php` into `admin/index.php` (302 → login) instead of refusing them. Suggested one-line additions: add `^/admin/(controllers|views|partials)(/|$)` to the deny regex, and have `dev_deny()` for those paths set `$_SERVER['REDIRECT_STATUS']='403'` and require `index.php` so the dev server mirrors `ErrorDocument 403`.
2. **`app/lib/orders.php:142,164`** (commerce owner): allow-listed `{$column}` interpolation — safe, but the same `match` rewrite as `auth.php` would make the tree grep-clean.
3. **HSTS** stays off until 14 days after the padlock (Q-23); the go-live guide owns that step.
4. **`site/uploads/collections/`** is re-included by `.gitignore`, so runtime-generated derivatives (`collection-6-b6afa67b82-*`) are already committed; hygiene, not security.
5. **`install.php` is present on the dev tree** (development installs keep it, C-47); production self-deletes on Finish and the dashboard banner nags until it is gone.
6. `.htaccess` behaviour, MariaDB and the LiteSpeed edge proxy remain untestable locally; Step 4 of the installer and 07 B.4.1 re-run the deny probes on the real host.
