# Storefront smoke — main after the motion layer was removed (re-run)

Run 2026-09-28 22:09–22:25 PKT against `site/` on branch `main` (working tree also carries uncommitted edits to `app/lib/text.php`, `install.php`, `dev/zip-manifest.php` and docs), PHP 8.2.28 built-in server on 127.0.0.1:8088 via `dev/router.php`, shared dev DB `skyfragrances_dev`. Every row and file this run created was deleted afterwards; the one setting it touched (`site_indexable`) was restored to its original value and `updated_at`.

## Result

| Step | Result |
|---|---|
| 1. Lint | PASS — `php -l` on all 188 `.php` files under `site/` (vendor PHPMailer included): 0 syntax errors. `node --check` on all 10 JS files incl. `assets/js/motion/config.js`: 0 errors. |
| 2. Routes | PASS — 38 URLs curled; every real route 200, unknown slugs 404, private paths 403, no 500 anywhere, zero references to `core.js` / `motion.css` in any response body or in any `.php`/`.js`/`.css` source file. |
| 3. Cart API | PASS — add / update / remove / coupon totals all exact; WELCOME10 proven above and below Rs. 3,000. |
| 4. Checkout | PASS — COD order and bank-transfer order with a generated 480×320 PNG proof placed through `POST /checkout`; receipt 200, `/track` 200 with the right phone and nothing revealed with the wrong phone; oversell 409. |
| 5. Intro loader | PASS — first visit `#sf-intro` gone at 2186 ms (desktop) / 1954 ms (mobile) with the hero CTA clickable at 2.6 s; second navigation quick fade 403 / 385 ms; reduced-motion calm 1234 ms. |
| 6. Tour | PASS — 24 shots in `docs/launch/shots-store/`, `report.json` clean (all 200, no console/page/network errors, no horizontal overflow). |
| 7. MariaDB 10.11 | PASS — `schema.sql` + `seed.sql` loaded with `--show-warnings`: zero output, exit 0, 26 tables. |
| 8. Logs | PASS — no PHP warning/notice/fatal from the app, no 5xx in 858 served requests. |

No fixes were needed; no code file was changed by this run. `storefront-smoke.md` and the `shots-store/` PNGs + `report.json` are the only files written.

## Step 2 — route detail

200: `/ /shop /collections /for-him /for-her /unisex /new-arrivals /best-sellers /sale /search?q=oud /scent-finder /cart /track /faq /about /shipping /returns /privacy /terms /contact /unsubscribe /robots.txt`; APIs (JSON) `/api/session /api/cart /api/search-suggest?q=ou`; static `/favicon.ico /assets/css/site.css /assets/js/intro.js`. Real slugs: `/collections/{azure-heights,dawn-chorus}`, `/scent/{oud-smoke,floral}`, `/product/{azure-oud,cirrus,silver-lining}` all 200 (covered by the sitemap list below and the tour).

By-design redirects: `/checkout` → 302 `/cart` with an empty cart; `/scent-finder/result` → 302 `/scent-finder` without answers; `/order/SF-…` → 301 to the lowercased path, then 200.

404: `/nope /product/does-not-exist /collections/does-not-exist /scent/does-not-exist`. 403 via `dev/router.php`: `/app/bootstrap.php /config.php /db/schema.sql`.

`/sitemap.xml` is 404 on this copy **by design** — `site_indexable = 0` (the installer switches it off on any non-canonical host) and `app/controllers/sitemap.php` honours it. Note the app reads settings through `storage/cache/settings.json` (5-minute TTL), so a raw SQL flip alone does nothing; with the setting set to 1 and that cache cleared, one request returned 200 `application/xml; charset=utf-8`, well-formed (`xmllint --noout`), 43 `<url>` entries: 9 listing pages, `/scent-finder /track /contact`, 6 content pages, 6 collections, 5 scent families, 14 products. The setting was restored to `0` with its original `updated_at` (`2026-09-26 01:45:13`), the settings cache and `storage/cache/sitemap.xml` removed, and `/sitemap.xml` confirmed 404 again. `robots.txt` is real (text/plain, Disallow list + `Sitemap:` line).

## Step 3 — cart API detail (`WELCOME10` = 10 %, min order Rs. 3,000; shipping Rs. 250, free from Rs. 3,000)

All POSTs as JSON with `X-CSRF-Token` from `GET /api/cart`.

| Call | Expected | Got |
|---|---|---|
| coupon on empty cart | 422 | 422 "Add something to your cart before applying a code." |
| add size 3 (Cirrus 50ml, 5,450) ×1 | 5,450 / ship Free | subtotal 5450.00, shipping 0.00, total 5450.00 |
| coupon `welcome10` (lower-case) | −545 | discount 545.00, total 4905.00, code normalised to WELCOME10 |
| update size 3 → 2 | 10,900 −1,090 | 9810.00 |
| add size 13 (Silver Lining 50ml, sale 3,950) | 14,850 −1,485 | 13365.00 (sale price used) |
| remove size 13 | back to 9,810 | 9810.00 |
| coupon remove | 10,900 | 10900.00 |
| update size 3 → 0 | empty | count 0 |
| add sold-out size 10 | 409 | 409 `sold_out` "Eclipse Velvet (100ml) is sold out." |
| add size 17 ×5 (stock 3) | clamp to 3 | qty 3, max_qty 3, "Only 3 left … we've updated your quantity." |
| add size 99999 | 404 | 404 `unavailable` |
| POST without CSRF | 419 | 419 |
| POST with `Origin: https://evil.example` | 403 | 403 `origin` "Request blocked." |

Below Rs. 3,000: the cheapest active size is Rs. 3,950, so a live cart cannot sit between Rs. 1 and Rs. 2,999. The threshold branch was proven at unit level through the app bootstrap (`coupon_validate(coupon_find('WELCOME10'), …)`): Rs. 2,500 → `below_min` "…Add Rs. 500 more to use it.", Rs. 2,999 → `below_min` "…Add Rs. 1 more…", Rs. 3,000 → applied Rs. 300, Rs. 5,450 → applied Rs. 545. The free-shipping threshold likewise cannot be undercut by the catalogue; the "Free" branch is verified above.

## Step 4 — checkout detail

All orders carried `User-Agent: storefront-smoke-8088` and unique phones (`0300 8088101–104`); the form-trap `ts` token was taken from `GET /checkout` and each POST waited ≥ 3 s. Multipart POST with `_csrf`, `ts`, empty `website` honeypot, `idem_key`, `price_token`, `price_total`.

- **COD** — cart Cirrus 50ml ×1. `POST /checkout` → 303 `/order/SF-260928-29UY?t=…`. Receipt 200 (after the lowercase 301), title `Order SF-260928-29UY | Sky Fragrances`, "Cash on Delivery". DB: `status pending`, `payment_status unpaid`, subtotal 5450 / shipping 0 / grand_total 5450, `phone_normalized +923008088101`. Wrong access token → 301 → 200 at `/track?order=SF-260928-29UY` (no receipt shown). `POST /track` with `ts` + `_csrf` and the right phone → 200, title `Order SF-260928-29UY — Status`, shows Cirrus 50ml, Pending, Cash on Delivery; with a wrong phone → 200 plain track page, zero order details leaked. (My first two `/track` POSTs omitted `ts` and were correctly rejected as `form trap: track bot` — harness error, logged as designed.)
- **Bank transfer** — cart Silver Lining 50ml ×1 (sale price 3,950 charged, `unit_price 4950 / sale_price 3950 / unit_price_charged 3950` on the line), reference `SMOKE8088REF1`, sender "Smoke Sender", 480×320 PNG proof (`image/png`, GD-generated). → 303, receipt 200 showing "Awaiting verification" and the reference; DB `payment_status = awaiting_verification`; `payment_proofs` row `review_status pending`, proof normalised to JPEG (3,158 bytes) under `storage/proofs/2026/09/`.
- **Oversell** — two sessions each holding Saffron Zenith 50ml ×3 (stock 3), both with a `GET /checkout` price token. Session D placed first (303, order SF-260928-ZXBR, stock → 0); session C then posted → **409** with "… sold out. Remove it to continue." and no order written. The Rs. 25,350 order was flagged `[REVIEW]` in the owner email subject, as designed.
- Stock decremented once per line (40→39, 45→44, 3→0) and restored to 40 / 45 / 3. `WELCOME10.used_count` stayed 0, `coupon_redemptions` 0. Deleted afterwards: orders 13–15 (FK cascade cleared `order_items`, `order_status_history`, `payment_proofs`; verified 0 remaining), their 3 `email_outbox` rows (26–28), my 11 `rate_limits` rows (33–43), the proof JPEG, the 3 `mail-preview` HTML files, and 12 `storage/sessions/shop` files (7 by cookie-jar id, 5 by exact mtime of my cookie-less curls and the intro probe). `orders` is back to the other agent's 6 rows (5 seed + their `SF-260928-KZ28`), `payment_proofs` to 4, no orphans.

## Step 5 — intro loader detail (Playwright Chromium)

| Context | html class at load | `#sf-intro` removed at | Hero CTA at 2.6 s |
|---|---|---|---|
| desktop 1440×900, 1st visit | `motion--desktop sf-intro-full js` | 2186 ms | clickable → click navigated to `/shop` |
| desktop, 2nd navigation | `sf-intro-quick` | 403 ms | clickable |
| mobile 375×812, 1st visit | `motion--mobile sf-intro-full js` | 1954 ms | clickable |
| mobile, 2nd navigation | `sf-intro-quick` | 385 ms | clickable |
| reduced motion 1280×800 | `sf-reduce sf-intro-calm js` | 1234 ms | clickable |

Zero page errors and zero console errors in every context; `sf-intro-done` on `<html>` after removal. "Clickable" = `elementFromPoint` at the CTA centre resolves to the CTA itself.

## Step 6 — tour

`TOUR_COOKIE=SFSHOP=<fresh session holding Cirrus 50ml ×1> node dev-tools/tour.mjs http://127.0.0.1:8088 docs/launch/shots-store / /shop /collections /for-her /product/azure-oud /scent-finder /cart /checkout /track /contact /faq /about` (the cookie makes `/checkout` render the real form instead of bouncing to `/cart`). The previous run's PNGs were removed first; 12 paths × desktop + mobile = 24 PNGs; `report.json` has 24 entries, 0 errors, 0 overflow, all 200. Home desktop and checkout mobile checked by eye: complete pages, correct layout; the "STAGE4 ANNOUNCEMENT BANNER TEXT", "Stage4 tagline", "Stage4 Bank", the blue placeholder art and the "Smoke Test Oud" product are the other agent's live dev data, not build defects.

## Step 7 — MariaDB

`mariadb 10.11.19` on 127.0.0.1:3307 (client `/opt/homebrew/opt/mariadb@10.11/bin/mariadb`), database `skyfragrances_maria`: all 26 existing tables dropped first with `FOREIGN_KEY_CHECKS=0`, then `db/schema.sql` and `db/seed.sql` piped through `mariadb --show-warnings`: exit 0 and empty output for both (no warning, error or note). 26 tables, all InnoDB / `utf8mb4_unicode_ci`. Seed counts: 12 products, 24 sizes, 5 collections, 3 coupons, 79 settings, 5 quiz questions / 19 options, 6 content pages, 5 scent families, 0 admin users (the admin user is created by `install.php`, as designed).

Deployer note: Homebrew's MySQL 9 client cannot talk to MariaDB (`mysql_native_password` plugin missing); use the `mariadb` client or phpMyAdmin.

## Step 8 — logs

`storage/logs/app-2026-09.log` lines added during the run: 15 `warning CSRF check failed` (14 from my first cart script whose token variable was polluted by the machine's `imagick` startup notice, 1 deliberate no-token request), 2 `warning form trap: track bot` (my `/track` POSTs without `ts`), 3 `info mail: transport unavailable, preview written` (expected with no SMTP configured), plus `admin login failed` and `housekeeping ran` lines from the other agent's concurrent work. No `error` entries. Server log `/tmp/skyfr-8088.log`: 858 requests, 0 lines with a 5xx status, no PHP warning/notice/fatal from the app; the only non-request line is the machine-level `Module "imagick" is already loaded` php.ini duplicate at startup, unrelated to the site. Server PID killed at the end; port 8088 free.
