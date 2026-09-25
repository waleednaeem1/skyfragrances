# Critique — Hostinger shared-hosting deployability

Lens: sceptical shared-hosting operations engineer. Question asked of every line: does this
work after a non-developer extracts the ZIP through hPanel File Manager onto a LiteSpeed +
lsphp 8.2 + MariaDB account with no shell, no cron assumed, and a capped PHP pool?

Read in full: PLAN.md, 08-decisions-register.md, 02b (all of it, including the four `.htaccess`
texts), 02c §1/§2.1/§5/§6/§7/§8/§9, 02a §3/§5, 05a §1/§2.3–2.6/§4.3, 06b §1.6/§5.2, 07 B.2.5,
B.3.5–B.3.8, B.4.1, B.4.9, A.2, 01a §3.5/§6, 01c §4/§7, and the amended `orders` DDL in 08 §2.3.

What the plan already gets right and is NOT repeated below: `php_flag engine off` is out
(C-37); the HTTPS rule is three-condition proxy-aware with `[NE]`; every optional block is
wrapped in `<IfModule>`; `.user.ini` is the mechanism for PHP values, not `.htaccess`;
`storage/` is inside `public_html` and defended three ways; money is `DECIMAL(10,2)`; no
`utf8mb4_0900_ai_ci`, no `JSON`, no triggers, no `ENUM`, no `SET FOREIGN_KEY_CHECKS`; the order
is written before mail is attempted; `install.php` tests the DB login before writing anything
and refuses to re-run. Those are solid. The gaps are what follows.

## Severity table

| # | Sev | Finding | Where |
|---|---|---|---|
| 1 | blocker | `base_url` frozen at install time drives the Origin checks and every absolute URL; the go-live order (install before SSL, or on a preview host) guarantees it is wrong, so the cart API and every admin POST are rejected on the live domain | 02c §7.1/§7.2, §4; 05a §2.5; 02a §5.10; PLAN §12 stage 6 |
| 2 | high | Shipped `.htaccess` 301s to HTTPS before the certificate exists; PLAN's guide order puts `install.php` before "enable SSL", so the installer is unreachable or the 301 is cached against a bad cert | 02b §3.2 vs PLAN §12 stage 6 / brief §"How to work" 4 |
| 3 | high | One `storage/sessions/` directory shared by a 3-day cart lifetime and a 12-hour admin lifetime: whichever request triggers GC deletes the other's files. Plus unconditional `session_start()` writes a file per bot hit | 02c §1 step 3; 05a §2.3; 08 C-26 |
| 4 | high | `admin/.htaccess` turns `RewriteEngine On` in a subdirectory, which discards the root HTTPS/www rules for every `/admin/*` URL — the login form can be posted over plain HTTP. Three incompatible root `.htaccess` texts exist | 05a §1; 02a §3.1; 02b §3 |
| 5 | high | `db/seed.sql` opens with `DROP TABLE IF EXISTS` for every table; a non-developer told "import seed.sql in phpMyAdmin" on a live store loses every order | 01c §7 vs 01a §6 vs PLAN §1 "for phpMyAdmin if you ever prefer" |
| 6 | high | Outbox drain inside an admin page load: `LIMIT 20` × 8 s SMTP timeout = 160 s against a 60 s `max_execution_time`; PLAN and 06b disagree on when the drain even runs | 06b §5.2 step 4–5; PLAN §2 "Email that fails", §8.4 |
| 7 | medium | Subdirectory install needs five edits, not "the single `RewriteBase /` line": rules 3.4, 3.5, 3.6, `ErrorDocument`, and `admin/.htaccess` all carry absolute `/` paths | PLAN §3 table; 02a §5.5–5.6; 02b §3 |
| 8 | medium | `install.php` self-tests (`__rewrite-probe`, `.probe.php`, "Check now") fetch the site over HTTP from the server itself — fails when DNS is not pointed, on a preview host, or while the 301 points at an unissued cert; and the probes never test the `db/` and `storage/` denies, which have no PHP-level guard | 02b §7.4; 02c §7.2 step 1 |
| 9 | medium | `ErrorDocument 404 /index.php` sends every missing image and asset through the full PHP front controller — the exact thing 02b §3.6 and 02a §3.1 say is prevented; a crawler with stale image URLs burns the PHP process pool (508) | 02b §3 preamble vs §3.6; 02a §3.1 |
| 10 | medium | `.user.ini`: `expose_php` is `PHP_INI_ONLY` and is silently ignored there; `post_max_size = 12M` contradicts 02c §5.1's "≤ 10 files per request" at 8 MB each (`$_FILES` arrives empty, CSRF then fails) | 02b §8.2, §8.3; 02c §5.1; 05a §4.3 |
| 11 | medium | GD memory: 40 MP pixel cap × 4 bytes = 160 MB for the source alone, on top of a `memory_limit` the plan may cap below 256M; and 8000-px / 40 MP limits reject every 48–50 MP phone photo the owner will take | 02c §5.1; 02b §8.1/§8.3 |
| 12 | medium | Hostinger's own hPanel "Force HTTPS" toggle and fresh-account `default.php` rewrite/prepend the root `.htaccess`; the ZIP/guide do not tell the owner to leave the toggle off or how the ZIP root must be laid out; dotfiles are hidden in File Manager by default | 02b §7.1, §9; PLAN §12 stage 6 |
| 13 | medium | OPcache: `storage/cache/settings.php` is `include`d and `config.php` is rewritten for maintenance mode; with lsphp OPcache `revalidate_freq` the "visible immediately" promise is false for up to the revalidate window | 02c §1 step 7, §8.2; PLAN §12 stage 4 |
| 14 | medium | Two different CSPs (`.htaccess` with `'unsafe-inline'` scripts and Google Fonts hosts; PHP with `script-src 'self'`) will both be sent; browsers enforce the intersection. 07 B.3.6 also lists HSTS in the PHP block against Q-23 | 02b §6; 07 B.3.6; PLAN S7 |
| 15 | medium | Three different `uploads/.htaccess` texts (02b §5, 02c §5.4, 07 B.3.5); `install.php` writes one "from a string literal" — which? 02b's version has an unguarded `Header set` and `Options -ExecCGI` that its own §1 rule 5 forbids | 02b §5, §7.4; 02c §5.4; 07 B.3.5 |
| 16 | medium | DDL/import: 01c §4.1 declares collation "on `CREATE DATABASE`" — that statement fails on Hostinger; `sql_mode` strictness that C-03 relies on is never set on the connection; `SET SESSION TRANSACTION ISOLATION` in a phpMyAdmin paste | 01c §4.1, §7 phase 1; 08 C-03 |
| 17 | low | Execution time: "Regenerate all image derivatives" walks every product in one request; `install.php` step 4 encodes 12 × 6 files; both need `?offset=` batching under a 60 s cap | 02c §5.3; 02b §8.4 |
| 18 | low | SMTP is never test-sent; the mailbox must be created in hPanel before `install.php`; SPF/DKIM are auto-only when the domain's nameservers are Hostinger's; proof temp-file and `sent`-row cleanup exist only in the cron path | 02c §7.2; 06b §1.6, §5.2; 08 Q-18/Q-19 |
| 19 | low | `admin/controllers/*.php` and `admin/views/*.php` are directly requestable (`!-f` lets them through) and are not on 02b's Layer-3 guard list | 05a §1; 02b §4 Layer 3 |

---
## Findings in detail

### 1. BLOCKER — `base_url` is captured once at install and then trusted for Origin checks

**What the specs say.** 02c §7.1: `'base_url' => 'https://skyfragrances.com' // used for
canonical, OG, email links`, written by `install.php`. 02c §4: the cart API "rejects requests
whose `Origin` header is present and is not the site origin". 05a §2.5: every admin POST
"verifies the `Origin` header (falling back to `Referer`) matches the canonical host". 02a §5.10:
`install.php` sets `site_indexable = 0` "whenever `BASE_PATH` is non-empty or the host is not
`skyfragrances.com`". 02c §7.2 step 1 fetches `{base_url}/__rewrite-probe`. PLAN §3 says
`config.php` is opened "rarely".

**Why it fails on Hostinger.** The owner will run `install.php` in one of three states, and in
all three the recorded `base_url` is not the live origin:

- Before SSL is active (PLAN §12 stage 6 order: "run `install.php`, delete it, enable SSL") —
  the request is `http://skyfragrances.com/install.php`, so `base_url` is written as `http://…`.
  Once the 301 is live every page is `https://…`; the browser sends `Origin: https://skyfragrances.com`;
  the API compares it to `http://skyfragrances.com` and rejects. The cart drawer is dead and every
  admin form 419s on day one, with nothing in the log that a non-developer can act on.
- On the Hostinger preview host (`https://<name>.hostingersite.com`) before the domain is
  pointed — the same mismatch after the DNS switch, plus `site_indexable` stays `0` forever and
  every emailed link and `og:url` points at the preview host.
- In a sub-folder staging copy (02a §5) that is later moved to the root.

Nothing in the plan makes `base_url` editable without File Manager, and the go-live guide
(PLAN §12 stage 6) has no "edit `base_url`" step.

**Fix.**
- The Origin/Referer comparison must be against the *request's own* scheme + host
  (`$_SERVER['HTTP_HOST']`, with the proxy-aware HTTPS detection from 02b §2.3), never against
  `config['base_url']`. Same-origin means "matches this request", not "matches a config string".
- Keep `base_url` for canonical/OG/email/sitemap only, and expose it on Settings › Advanced as
  "Site address" with a live "this page was opened at https://… — use that" hint, so the owner
  can fix it from the phone. The admin dashboard should show a red banner while
  `base_url` host ≠ the host the dashboard was opened on.
- `install.php` should record `base_url` as `https://` + host whenever the host is the canonical
  domain, regardless of the scheme the installer was opened on, and the guide must order SSL
  before install (finding 2).

### 2. HIGH — the shipped 301-to-HTTPS conflicts with the guide's own go-live order

**What the specs say.** 02b §3.2: `[R=301]` is permanent, "which is why §9 lists 'install SSL
before first public link' as a hard ordering constraint: a 301 cached by a browser before the
certificate exists produces an untrusted-certificate error the owner cannot clear remotely."
PLAN §12 stage 6 and brief §"How to work" step 4 order the steps: create database → upload →
run `install.php` → delete it → enable SSL → go live.

**Why it fails.** The moment the ZIP is extracted the root `.htaccess` is live. The owner opens
`http://skyfragrances.com/install.php`; the rule fires; the browser lands on
`https://skyfragrances.com/install.php` with no certificate (Hostinger's free certificate is
issued asynchronously after DNS resolves to the account, typically minutes but sometimes hours).
The owner sees a full-page certificate warning on the very first step and, per 02b's own
warning, the 301 is now cached. Chrome will keep forcing HTTPS on that origin until the cache is
cleared. On the preview host SSL is already valid, which hides this in the developer's rehearsal.

**Fix.** Any one of: (a) reorder the guide — DNS → wait for the padlock in hPanel → upload →
install; (b) ship the redirect as `R=302` and have `install.php` (or a Settings › Advanced
toggle) flip it to 301 after the owner confirms the padlock, mirroring the HSTS staging in
Q-23; (c) have `install.php` write the HTTPS block into `.htaccess` only after its own
`https://` fetch of the site succeeds. (b) is the least work and needs no owner discipline.

### 3. HIGH — one sessions directory, two garbage-collection lifetimes

**What the specs say.** 02c §1 step 3: `session_save_path(APP_ROOT.'/storage/sessions')` and
`ini_set('session.gc_maxlifetime', '259200')` (3 days) before `session_start()` on every
storefront request. 05a §2.3: the admin bootstrap sets `session.gc_maxlifetime = 43200` and
`session.save_path = storage/sessions/` — "same directory" per 08 C-26. Q-16 promises "Guest
carts live 3 days".

**Why it fails.** PHP's file session handler runs GC with the *current request's*
`gc_maxlifetime` over the *whole* `save_path`. Every admin request that wins the
`gc_probability/gc_divisor` lottery deletes every `SFSHOP` file older than 12 hours. The owner
works the panel all day, so the 3-day cart promise silently becomes ~12 hours, and the
customer who comes back the next morning finds an empty cart with no error anywhere.

Second problem in the same block: `session_start()` is unconditional at bootstrap step 3, so
every Googlebot, Bingbot, scraper and uptime-check hit creates a new file under
`storage/sessions/`. With `gc_probability = 1 / gc_divisor = 1000` (php.ini-production
defaults; Hostinger does not run a sweeper cron for a custom save path) a low-traffic shop
accumulates files far faster than they are reaped. Hostinger plans have an inode quota
(hundreds of thousands of files on Premium) shared with 12 products × 6 derivatives and the
outbox, and hitting it takes the whole account down, not just the shop.

**Fix.**
- Two directories: `storage/sessions/shop/` and `storage/sessions/admin/`, each with its own
  `gc_maxlifetime`. `install.php` creates both.
- Set `session.gc_probability = 1`, `session.gc_divisor = 100` explicitly in the bootstrap (not
  `.user.ini`, which local `php -S` may not read).
- Lazy sessions on the storefront: start the session only when a cookie named `SFSHOP` is
  already present or the request is a POST / `/api/cart/*` call. A first-time GET should set
  no cookie and write no file. This also removes a `Set-Cookie` from every cached HTML response.
- Add the sessions directory to the opportunistic purge that already handles `rate_limits`.

### 4. HIGH — `admin/.htaccess` drops the root HTTPS and www rules for every `/admin/*` URL

**What the specs say.** 05a §1 ships `admin/.htaccess` as:

```
RewriteEngine On
RewriteBase /admin/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

02b §2.2 lists `RewriteOptions Inherit` as "not reliable". 02a §3.1 puts a *different* rule in
the root file (`RewriteRule ^admin(/.*)?$ admin/index.php [L,QSA]`) and 02a §3.7 says "admin/
.htaccess rewrites `^admin(/.*)?$`" — a pattern that can never match inside `admin/` because
per-directory rewrites strip the directory prefix first.

**Why it fails.** In both Apache and LiteSpeed, a per-directory `.htaccess` that says
`RewriteEngine On` replaces the parent's rewrite rule set for that subtree unless the parent
uses `RewriteOptions Inherit` (which 02b rules out). So for `http://skyfragrances.com/admin/login`
the root's 3.2 HTTPS rule and 3.3 www rule never run. `session.cookie_secure = 1` keeps the
session cookie off the wire, but the login form itself renders over HTTP and the password POST
goes in clear text — on a mobile network the owner will type `skyfragrances.com/admin` without a
scheme at least once. `X-Forwarded-Proto` does not help because nothing is checking it here.

There are also now three root `.htaccess` texts: 02b §3 (canonical per PLAN §2), 02a §3.1
(no `%{SERVER_PORT}` condition, no `[NE]`, `-MultiViews`, admin rewrite in root), and the 05a
fragment. A builder copying from 02a ships a weaker HTTPS rule than the one PLAN promises.

**Fix.** Declare 02b §3 the only root text and delete the 02a block. Either drop
`admin/.htaccess`'s rewrite entirely and route `/admin` from the root file
(`RewriteRule ^admin(/.*)?$ admin/index.php [L]` placed after 3.6, before 3.7 — the 3.2/3.3
redirects then cover it), keeping `admin/.htaccess` for headers only; or, if the per-directory
file stays, repeat the three-condition HTTPS block and the www block verbatim at the top of it.
Post-deploy check 1 in 02b §9 must add `http://skyfragrances.com/admin/login` → `[https, padlock]`.

### 5. HIGH — `db/seed.sql` starts with `DROP TABLE IF EXISTS` for every table

**What the specs say.** 01c §7: "`db/seed.sql` ships the `DROP TABLE IF EXISTS` statements in
that reverse order at the top of the file". 01a §6: every `CREATE TABLE` is `IF NOT EXISTS` "so a
half-finished import can be re-run by a non-developer without dropping the database first".
PLAN §1: `db/schema.sql` + `db/seed.sql` are shipped "for phpMyAdmin if you ever prefer that
route". 07 B.4.1: "`db/seed.sql` imports cleanly through phpMyAdmin on the real host."

**Why it fails.** These three statements are mutually contradictory, and the dangerous reading
is the one a non-developer will take. Six months in, the owner wants the sample perfumes back
for a screenshot, remembers "seed.sql adds the samples", and pastes it into phpMyAdmin. Every
`orders`, `order_items`, `payment_proofs` and `coupon_redemptions` row is gone; there is no
backup step in the plan and Hostinger's daily backup is a paid restore on lower plans. 01a's
"re-runnable" claim (IF NOT EXISTS) is defeated by the DROPs at the top of the same file.

**Fix.** No `DROP` in any shipped `.sql`. Ship `db/schema.sql` (create-only, `IF NOT EXISTS`) and
`db/seed.sql` (inserts only, every row `INSERT IGNORE` or keyed on the sample slugs/SKUs so a
second run is a no-op). Put the teardown in a separately named `db/DANGER-drop-all.sql` with a
first line comment that says what it does, or leave teardown to the `sf_install_probe`-style
"drop the database in phpMyAdmin by hand" instruction that 02c §7.2 already gives.

### 6. HIGH — outbox drain inside a page request cannot fit the execution-time cap

**What the specs say.** 06b §5.2 step 4: the drain runs `WHERE status='queued' AND next_try_at
<= NOW() ORDER BY id LIMIT 20`, with an SMTP timeout of 8 seconds per send (step 2). Step 5:
"If the hosting plan has no cron, the drain also runs on a 1-in-20 chance at the end of any
admin page load." PLAN §2 and §8.4: "A failed email goes to an outbox and is retried when you
next open the admin panel", "sent at once if SMTP answers within 8 s, else retried on later
admin page loads". `.user.ini` (02b §8.2): `max_execution_time = 60`. 02c §6.2 says `Timeout =
15`; 02c §6.4 says a 15-second timeout; 06b says 8 — three values.

**Why it fails.** SMTP failure is not per-message, it is per-outage. When Hostinger's SMTP is
slow every queued row times out at 8 s, so the drain is 20 × 8 = 160 s inside the request that
triggered it. lsphp kills it at 60 s and the owner gets the branded 500 page on a random admin
click, with no way to know the outbox caused it. At checkout the same block runs inline for the
two order emails (16 s of a customer's "Place Order" tap during an outage). The exponential
`next_try_at` backoff combined with a 1-in-20 trigger means a queued confirmation can sit for
hours while the owner is actively using the panel — the opposite of what PLAN §8.4 promises.

**Fix.** A per-request budget, not a row limit: drain at most 2 rows per admin page load, with a
wall-clock budget of 12 s total (`SMTP Timeout = 5`), and only rows whose `next_try_at` is due.
Run it on *every* admin page load (PLAN's wording), not 1-in-20. At checkout attempt only the
customer's email inline with a 5 s timeout; the owner alert can wait for the drain. Make the
dashboard tile say "N emails waiting" whenever `queued` rows exist, not only after 6 failures.
Keep `cron.php` as the optional accelerator and add it to 02a's tree — 02c §8.2 already
allow-lists `/cron.php` in maintenance mode but the tree never ships the file.

### 7. MEDIUM — a sub-folder install is five edits, not one

**What the specs say.** PLAN §3: "`.htaccess` — Only if the site is installed in a sub-folder…
Change the single `RewriteBase /` line". 02a §5.5: "`RewriteBase /` … is the one line
`install.php` cannot write". 02a §5.6: "Redirect rules 1–3 use `%{REQUEST_URI}` … so they are
base-path-safe as written."

**Why it fails.** In the canonical 02b §3 text:

- 3.4 `RewriteRule ^index\.php(.*)$ /$1` — absolute `/`; in `/shop-new/` it 301s to the root.
- 3.5 `RewriteRule ^(.+?)/+$ /$1` — same; `/shop-new/shop/` becomes `/shop`.
- 3.6 `RewriteCond %{REQUEST_URI} ^/(uploads|assets)/` — never matches `/shop-new/uploads/`, so
  every image goes through the `-f` stat and a missing one hits finding 9's front controller.
- `ErrorDocument 404 /index.php` and `ErrorDocument 403 /index.php` — absolute.
- `admin/.htaccess` has `RewriteBase /admin/` hard-coded (05a §1).

`RewriteBase` only affects *relative* substitutions (3.7's `index.php`); it does nothing to the
four absolute ones. 02a §5.6's claim is true of the conditions but not the substitutions.

**Fix.** Write every substitution relative (`$1` not `/$1`, and use `%{ENV:BASE}` set by a
`RewriteRule .* - [E=BASE:%{REQUEST_URI}]` trick, or accept the limitation). Cheapest honest
option: make 3.4/3.5 substitutions `%{REQUEST_SCHEME}://%{HTTP_HOST}/…` built from a
`RewriteCond %{REQUEST_URI} ^(.*?)/(index\.php.*)$` capture, drop the `/uploads|assets` prefix
short-circuit in favour of plain `-f`/`-d`, use `ErrorDocument 404 "Not found"` (string form,
path-free), and have `install.php` print all the lines that must change, not one. Or state in
PLAN §3 that sub-folder installs are not supported in v1 and remove 02a §5.

### 8. MEDIUM — the installer's self-fetch probes fail exactly when the owner needs them

**What the specs say.** 02b §7.4: `install.php` writes `uploads/.probe.php`, "fetching it over
HTTP through the site's own URL, asserting the response body is not `EXEC`". 02c §7.2 step 1:
rewrites are tested "by fetching `{base_url}/__rewrite-probe`" and "Failures block Next". Finish
screen: a "Check now" button "fetches `/install.php` and reports 404/200". 02b §8.1: "Nothing is
read or written outside `public_html`", `allow_url_fopen` "never relied on".

**Why it fails.** All three are outbound HTTP requests from the server to its own hostname.
On a fresh Hostinger account that is unreliable in every realistic state: DNS not yet pointed
(the request goes to the old host or nowhere), the preview host (a different hostname from
`base_url`, finding 1), the certificate not yet issued (curl refuses the 301 target and the
probe reports FAIL for a rewrite that works), or Hostinger's outbound WAF rate-limiting the
account's own loopback. Because "Failures block Next", a working install is stuck at step 1
with a red line the owner cannot fix. Meanwhile the probes never test what actually matters
after a lost dotfile: `db/schema.sql`, `storage/logs/app-*.log` (order numbers and phone
numbers, 02c §8.1) and `storage/proofs/**.jpg` have no `SKYFR` guard — a dropped
`storage/.htaccess` publishes customer bank screenshots behind a 32-hex name.

**Fix.** Probes are advisory: show PASS / "could not verify — open this URL on your phone and
confirm you see X" with the exact URL, never a blocker. Use curl with `CURLOPT_SSL_VERIFYPEER`
honoured but `CURLOPT_FOLLOWLOCATION` off and a 5 s timeout; treat a 301 to https as a pass for
the rewrite probe. Add two probes that matter: `GET /db/schema.sql` and
`GET /storage/.htaccess` must not return 200 with a body. Have `install.php` write
`app/.htaccess`, `db/.htaccess` and `storage/.htaccess` from string literals exactly as it
already does for `uploads/`, so none of the four depends on the ZIP tool keeping dotfiles.

### 9. MEDIUM — `ErrorDocument 404 /index.php` routes every missing image through PHP

**What the specs say.** 02b §3 preamble: `ErrorDocument 404 /index.php`. 02b §3.6 (same file,
same page): naming `/uploads/` and `/assets/` explicitly "guarantees that a missing image
returns a plain 404 rather than being swallowed by the front controller and rendered as a
full HTML 404 page inside an `<img>` tag". 02a §3.1: assets are matched first "so that a
missing image returns a real 404 from the server instead of rendering a full HTML 404 page
(which would waste a DB connection per broken image on a product grid)". 02b §8.5: the 508
defence is "static assets and uploaded images are served by LiteSpeed directly, never through
PHP".

**Why it fails.** `RewriteRule ^ - [L]` stops rewriting; it does not stop `ErrorDocument`.
When the file is absent LiteSpeed raises a 404 and `ErrorDocument` performs an internal
subrequest to `/index.php`, which boots config, session, settings and the router, then renders
the branded 404 with header, footer and four best sellers (03 §13) — one full PHP process
and (via the best-sellers query) one DB connection per missing image. After a product photo is
replaced, every cached grid page and every crawler holding the old `srcset` URLs does this at
once, and on a `noProcs`-capped account that is the 508 the section was written to avoid.

**Fix.** Put `ErrorDocument 404 "Not found"` (the quoted-string form needs no file and no PHP)
in `uploads/.htaccess` and an `assets/.htaccess`; keep the root `ErrorDocument` for everything
else. Update 02b §3.6 and 02a §3.1 so they stop claiming the opposite of what the preamble does.

### 10. MEDIUM — `.user.ini` contains a directive it cannot set, and a size that contradicts the uploader

**What the specs say.** 02b §8.2 `.user.ini`: `expose_php = Off` ("the real fix" per §6.2),
`upload_max_filesize = 8M`, `post_max_size = 12M`. 02c §5.1: admin images "Size ≤ 8 MB per
file, ≤ 10 files per request". 05a §4.3: "one request per file so a dropped mobile connection
loses one photo, not ten". 02b §8.3: "Admin multi-upload is capped at 8 files per submission";
"a POST that exceeds `post_max_size` arrives with `$_POST` and `$_FILES` empty and no error".

**Why it fails.** `expose_php` is `PHP_INI_ONLY`; it cannot be set from `.user.ini`, `ini_set`
or hPanel — the line is dead and only the two `Header unset` lines do anything. Not dangerous,
but 02b §6.2 calls it "the real fix" and someone will remove the header lines on that basis.
The upload numbers are the real problem: 02c and 02b both describe a multi-file POST (10 or 8
files at up to 8 MB), and `post_max_size = 12M` cannot carry two of them. The symptom is
exactly the one §8.3 warns about: `$_FILES` empty, and — because the CSRF field is in the same
body — a 419 "This form expired" page for an owner who did nothing wrong. 05a's one-file-per-
request XHR design is the only one of the three that fits the ini, and the non-JS fallback of
that form (a plain `<input multiple>` POST) does not.

**Fix.** Remove `expose_php` from `.user.ini` (keep the headers). Decide one uploader contract:
one file per request (05a) with `post_max_size = 10M`, and make the non-JS fallback a single-
file input. Before reading `$_FILES`, compare `$_SERVER['CONTENT_LENGTH']` with
`ini_get('post_max_size')` and return the real "too large" message instead of the CSRF error.
Reconcile 02c §5.1's "≤ 10 files per request" and 02b §8.3's "8 files" with 05a.

### 11. MEDIUM — GD memory arithmetic and the pixel cap reject the owner's own phone

**What the specs say.** 02c §5.1 rule 5: `50 ≤ w,h ≤ 8000`, `w*h ≤ 40_000_000`, "because GD
allocates `w*h*4` bytes". 02b §8.3: "A 4000×5000 JPEG costs roughly 80 MB in GD — comfortably
inside 256 MB for one image". 02b §8.1: `memory_limit` "256M (Premium), 384M (Business)".
Q-18 hedges "256M vs 384M". `.user.ini` asks for `memory_limit = 256M`.

**Why it fails.** Two directions. First, the cap is too *high* for memory: a 40 MP source is
160 MB as a truecolor GD resource; `imagescale()` to `zoom` (1400×1750) allocates another
~10 MB, the JPEG decode itself uses libjpeg scratch, and PHP's own baseline plus the loaded
libraries is 15–30 MB. That is ~200 MB against a 256M limit — fine — but the plan never
verifies the limit is *actually* 256M. If the account is a Single plan, or hPanel's PHP
Configuration caps below what `.user.ini` asks (a `.user.ini` request above the pool's
`php_admin_value` is silently clamped), the first large upload is a white screen from a fatal
that `set_exception_handler` cannot catch (memory exhaustion in `imagecreatefromjpeg` is a
fatal, and the shutdown handler has no memory left to render the branded page). Second, the
cap is too *low* for cameras: 8000 px and 40 MP reject an 8000×6000 (48 MP) frame, which is the
default "high resolution" output of the Samsung and Xiaomi phones the owner is most likely to
photograph bottles with. The error text "this image is too large, please resize it" is
useless advice to a phone user.

**Fix.** Compute the pixel budget at runtime from `ini_get('memory_limit')`: allow
`w*h ≤ (limit − 48 MB) / 5` and show the real number in the installer's requirements screen
("Largest photo this server can process: 32 MP — PASS"). For JPEG sources use libjpeg's
scale-on-decode (`imagecreatefromjpeg` honours `scale_denom` via `ImageJPEG` options only in
Imagick, so instead read the dimensions and, when `w*h` exceeds the budget, reject with copy a
phone user can act on: "Please choose 'Medium' size when sharing from your gallery, or screenshot
the photo"). Raise the hard edge cap to 10000 px. Do not ship `memory_limit = 256M` as a
promise in `.user.ini` without an install-time `ini_get` check that reports the effective value.

### 12. MEDIUM — hPanel writes to the same `.htaccess`, and the ZIP layout is unspecified

**What the specs say.** 02b §7.1 covers dropped dotfiles and `__MACOSX`. 02b §9 is a URL
checklist. PLAN §12 stage 6: guide steps are "create database in hPanel, upload and extract,
run `install.php`, delete it, enable SSL". Nothing else about hPanel's behaviour.

**Why it fails.**
- hPanel → Security → SSL has a **Force HTTPS** toggle. When switched on, Hostinger *prepends*
  its own rewrite block to `public_html/.htaccess`. Its block is the naive `%{HTTPS} off`
  form 02b §2.3 warns about; ahead of ours it either double-redirects or loops behind the
  CDN/proxy, and it is re-applied on some PHP-version changes. The guide must say "leave Force
  HTTPS off — the site does this itself" and the post-deploy check must diff the file.
- A fresh account ships `public_html/default.php` and sometimes a starter `.htaccess`.
  File Manager's Extract merges, so a ZIP whose root is a `public_html/` folder yields
  `public_html/public_html/index.php` — the classic first-upload failure. The ZIP must contain
  the files at its root and the guide must show the expected tree after extraction.
- File Manager hides dotfiles by default. 02b §7.1 tells the runbook to "check for" a missing
  `.htaccess`; the owner cannot see it without toggling "Show hidden files", and the guide does
  not mention the toggle. `.user.ini` has the same problem.
- Hostinger caches `.user.ini` for `user_ini.cache_ttl` (300 s); the first five minutes after
  extraction run with pool defaults, which is when the owner runs `install.php` and its
  `max_execution_time` stage plan.

**Fix.** A guide page titled "What not to touch in hPanel" (Force HTTPS off, PHP Configuration
untouched, no LiteSpeed cache plugin) and a "what you should see" screenshot of File Manager
with hidden files on. Build the ZIP with `zip -r` from inside the folder so the root is flat,
and have `install.php` step 1 detect `default.php` and a nested `public_html/` and say so.

### 13. MEDIUM — OPcache defeats "visible immediately" for the settings cache and `config.php`

**What the specs say.** 02c §1 step 7: settings are "cached as a `var_export`ed PHP array in
`/storage/cache/settings.php` and invalidated by the admin on every settings write (delete the
file) … it is `include`d". 02c §8.2: maintenance mode "rewrites `config.php` through the same
temp+rename". PLAN §12 stage 4 "done": "changing a setting is visible on the storefront
immediately".

**Why it fails.** lsphp on Hostinger runs OPcache with `validate_timestamps = 1` and a
`revalidate_freq` measured in seconds to a minute, and hPanel exposes an OPcache toggle the
owner can flip. `include` of a rewritten file with the same path returns the *cached* opcodes
until the revalidate window passes; delete-then-recreate inside one second can even match the
old mtime. So the announcement bar the owner just changed stays old on the storefront for up
to a minute, and — worse — the `config.php` rewrite that turns maintenance mode *off* keeps the
503 up for that window while the owner reloads and concludes it did not work. Same for the
`.installed` lock only if it were PHP; it is not, so that one is fine.

**Fix.** Do not `include` runtime-written state. Store the settings cache as JSON
(`file_get_contents` + `json_decode`, which OPcache never sees) and keep `config.php` read-only
after install: move `maintenance` and `maintenance_bypass` into the `settings` table with the
File-Manager fallback being a flag file `storage/MAINTENANCE` (its existence is the switch). If
`config.php` must be rewritten, call `opcache_invalidate($path, true)` guarded by
`function_exists`, and document that hPanel's OPcache toggle changes nothing for the app.

### 14. MEDIUM — two CSPs on the wire, and Google Fonts allowed for fonts that are self-hosted

**What the specs say.** 02b §6 emits from `.htaccess`: `script-src 'self' 'unsafe-inline';
style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self'
https://fonts.gstatic.com data:` and calls `'unsafe-inline'` "required". 07 B.3.6: headers are
"sent from PHP", with `script-src 'self'`, `font-src 'self'`, `frame-ancestors 'none'` and
`Strict-Transport-Security` in the same block. PLAN S7: "sent from PHP on every page … no
inline scripts"; HSTS "off at launch" (Q-23). C-32: five self-hosted woff2 files, no Google
Fonts.

**Why it fails.** `Header always set` in `.htaccess` applies to the PHP response too, so every
HTML page carries two `Content-Security-Policy` headers. Browsers enforce both — the effective
policy is the intersection, which is fine by accident today (PHP's stricter `script-src 'self'`
wins) but means any future relaxation in PHP is silently ignored and every debugging session
starts with "which header?". The Google Fonts hosts are dead weight that contradicts C-32, and
02b §9 check 13 still tells the owner "Times New Roman means … the Google Fonts link is
wrong". 07 B.3.6 shipping HSTS from PHP would violate Q-23 on day one.

**Fix.** One owner per header: `.htaccess` sets headers only inside `<FilesMatch>` for static
types; PHP sends CSP/Referrer/Permissions for HTML. Drop the Google hosts and `'unsafe-inline'`
from `script-src` (JSON-LD blocks are not executed and are not subject to `script-src`).
Strike HSTS from 07 B.3.6. Re-word 02b §9 check 13.

### 15. MEDIUM — three `uploads/.htaccess` texts, and the canonical one breaks its own rule 5

**What the specs say.** 02b §5 (the version C-37 makes canonical), 02c §5.4 and 07 B.3.5 each
print a different `uploads/.htaccess`. 02b §7.4: `install.php` "writes `uploads/.htaccess`
itself from a string literal in its own source". 02b §1 fact 5: "every optional block is
wrapped in `<IfModule>`". 02b §1 fact 4: Hostinger's `AllowOverride` is
`FileInfo Indexes Limit Options=...` — a *restricted* Options list.

**Why it fails.** Which literal does the installer embed? The three differ in the extension
list, in whether `AddType text/plain` is guarded, and in whether `Header` lines exist. 02b §5's
own text has `Header set Cache-Control` / `nosniff` / `Content-Disposition` inside a
`<FilesMatch>` with no `<IfModule mod_headers.c>` — the exact unguarded-directive class §1
rule 5 says must never ship, because on a host without the module it is a 500 across
`/uploads/` (every product image). `Options -Indexes -ExecCGI` is only legal if `ExecCGI` is in
the `Options=` allow-list; on Apache with a restricted list that is "Options not allowed here"
→ 500 for the whole tree; LiteSpeed is lenient today, but the doc is written to survive an
Apache migration. The load-bearing protection (`RemoveHandler` + `AddType text/plain` +
`<FilesMatch> Require all denied`) is sound on LiteSpeed, and the install-time `.probe.php`
test is the right way to prove it — but only if the test can run (finding 8).

**Fix.** Delete the 02c §5.4 and 07 B.3.5 texts and point both at 02b §5. In 02b §5 wrap the
`Header` lines in `<IfModule mod_headers.c>`, wrap `AddType` in `<IfModule mod_mime.c>` (it is
already), and drop `-ExecCGI` (CGI is not mapped for these extensions once `RemoveHandler`
runs; `Options -Indexes` alone is what the root already sets and inherits). Name in 02b §7.4
which section is the literal.

### 16. MEDIUM — DDL and import: `CREATE DATABASE`, `sql_mode`, and session statements in a paste

**What the specs say.** 01c §4.1: collation is "declared at three levels … on `CREATE
DATABASE`, on every `CREATE TABLE`, and at the top of every `.sql` file". 01c §7 phase 1: the
files open with `SET NAMES …; SET time_zone = '+05:00'; SET SESSION TRANSACTION ISOLATION
LEVEL READ COMMITTED;`. 08 C-03: `INT UNSIGNED` is the loud failure because "strict mode on
MySQL 8 and MariaDB 10.4 raises an out-of-range error, not a clamp". 02c §2.1 lists the PDO
options; no `sql_mode` is set anywhere.

**Why it fails.**
- A Hostinger database user has no `CREATE DATABASE` privilege and the database name is
  assigned (`u123456789_skyfrag`). If `schema.sql` carries `CREATE DATABASE … COLLATE …` or a
  `USE` line, the phpMyAdmin import that 07 B.4.1 requires stops at line 1 with "Access
  denied". Declare the collation on the two remaining levels and say so in 01c.
- C-03's safety argument depends on `STRICT_TRANS_TABLES`. MariaDB ≥ 10.2.4 defaults include
  it, but Hostinger's `my.cnf` is theirs, not the plan's, and a MySQL-8 local run with a
  different `sql_mode` will pass tests that fail on the host. The connection must execute
  `SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'`
  (both engines accept this list) right after `SET time_zone`, so the guard is the app's, not
  the host's.
- `SET SESSION TRANSACTION ISOLATION LEVEL` in a `.sql` file is harmless in phpMyAdmin but is
  meaningless outside a transaction; keep it in PHP only, keep the `.sql` files to `SET NAMES`.
- The plan promises "every index under the 3072-byte key limit". `orders.idx_orders_track
  (order_number VARCHAR(16), phone_normalized VARCHAR(16))` and friends are fine; but 01a's
  `product_images.filename VARCHAR(255)` and any future index on it would be 1020 bytes — fine
  on InnoDB `DYNAMIC` (the default on both) but the plan should state `ROW_FORMAT=DYNAMIC`
  explicitly, because an older MariaDB `innodb_default_row_format=COMPACT` account (they exist
  on legacy Hostinger servers) caps at 767 bytes and refuses `VARCHAR(190) UNIQUE` on
  `admin_users.email`. One clause per table, no cost.

### 17. LOW — long-running admin and install requests need `?offset=` batching

02c §5.3's "Regenerate all image derivatives" job "walks `product_images` and re-derives" in
one request; at 6 encodes per row and ~0.4 s per 1400-px WebP on a shared core, 60 images is
already past the 60 s `.user.ini` cap, and lsphp's own process-time guard is not something
`set_time_limit()` can raise. 02b §8.4 stages `install.php` by `?step=`, which is right, but
step 4 (seed) still does "image derivative generation for 12 sample products" (72 encodes) in
one step. Batch both by `?offset=` with a self-redirect after ~20 s of work, and make the
install seed use the pre-generated files 07 A.2 already ships instead of encoding at install.

### 18. LOW — SMTP never test-sent, mailbox prerequisite unstated, cron-only cleanups

02c §7.2's installer collects SMTP credentials but never sends a test message; Settings has no
"send test email" either. The first evidence of a typo is a dashboard tile after six failed
retries. The mailbox `orders@skyfragrances.com` must exist in hPanel → Emails *before*
`install.php`, and Hostinger auto-creates SPF/DKIM/DMARC only when the domain uses Hostinger's
nameservers — if the owner's mail is at Google Workspace, `smtp.hostinger.com` cannot
authenticate for that address at all and the guide needs the "use your provider's SMTP" branch.
06b §1.6 and §5.2 leave orphaned proof temp files and 60-day `sent` rows to the cron; with
"no cron" assumed (Q-18) they never go, so add both to the opportunistic purge.

### 19. LOW — `admin/controllers/*.php` are directly requestable and not on the guard list

05a §1's `RewriteCond %{REQUEST_FILENAME} !-f` lets `/admin/controllers/orders.php` and
`/admin/views/*.php` execute directly. 02b §4 Layer 3 lists the `SKYFR` guard for `app/`,
`includes/`, `config/`, `lib/` — not `admin/controllers/`, `admin/views/`, `admin/partials/`.
PLAN §2 promises "Every included file refuses to run unless opened through `index.php`". Add
the three admin subdirectories to the Layer-3 list and to the B.4.9 checklist, and give
`admin/controllers/`, `views/`, `partials/` the same deny `.htaccess` + `index.php` stub.

---

## What I would do first

1. Finding 1 + 2 together: make Origin checks request-relative, ship the HTTPS redirect as 302
   until confirmed, and reorder the guide so SSL precedes install. These are the two that turn
   a correct build into a broken launch.
2. Finding 3: split the sessions directory and lazy-start the storefront session.
3. Finding 5: strip every `DROP` from shipped SQL.
4. Finding 6: budget the outbox drain by time, on every admin load.
5. Finding 4: one root `.htaccess`, admin routed from it.
Everything else is a spec edit that can land during stage 1.
