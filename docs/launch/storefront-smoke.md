# Storefront smoke — main after the motion layer was removed

Run 2026-09-28 13:03–13:15 PKT against `site/` on branch `main`, PHP built-in server on 127.0.0.1:8088 (`dev/router.php`), shared dev DB `skyfragrances_dev`. Every row and file this run created was deleted afterwards; the one setting it touched (`site_indexable`) was restored.

## Result

| Step | Result |
|---|---|
| 1. Lint | PASS — `php -l` on every `.php` under `site/` (vendor PHPMailer excluded): 0 errors. `node --check` on all 9 JS files incl. `assets/js/motion/config.js`: 0 errors. |
| 2. Routes | PASS — 47 URLs curled; every real route 200, unknown slugs 404, private paths 403, no 500 anywhere, zero references to `core.js` / `motion.css` in any response body. |
| 3. Cart API | PASS — add / update / remove / coupon totals all exact. |
| 4. Checkout | PASS — COD order and bank-transfer order with PNG proof placed through `POST /checkout`; receipt 200, `/track` 200, oversell 409. |
| 5. Intro loader | PASS — first visit gone at 2245 ms (desktop) / 1960 ms (mobile) with hero CTA clickable; second visit quick fade 400 ms; reduced-motion calm 1250 ms. |
| 6. Tour | PASS — 24 shots in `docs/launch/shots-store/`, `report.json` clean (all 200, no console/page/network errors, no horizontal overflow). |
| 7. MariaDB 10.11 | PASS — `schema.sql` + `seed.sql` loaded with `--show-warnings`: zero output, 26 tables. |
| 8. Logs | PASS — no PHP warning/error/fatal from the app, no 5xx. |

No fixes were needed; no code file was changed by this run.

## Step 2 — route detail

Public pages (200): `/ /shop /collections /for-him /for-her /unisex /new-arrivals /best-sellers /sale /search?q=oud /scent-finder /cart /track /faq /about /shipping /returns /privacy /terms /contact /unsubscribe /robots.txt /collections/{azure-heights,dawn-chorus} /scent/{oud-smoke,floral} /product/{azure-oud,aurora-bloom,silver-lining}`. APIs (200 JSON): `/api/session /api/cart /api/search-suggest?q=ou`. Static (200): `/favicon.ico /assets/css/site.css /assets/js/intro.js /uploads/index.html`.

Redirects that are by design: `/checkout` → 302 `/cart` with an empty cart; `/scent-finder/result` → 302 without answers; `/order/SF-…` → 301 to the lowercased path (router normalises case; the receipt then serves 200).

404: `/nope /product/does-not-exist /collections/does-not-exist /scent/does-not-exist`. 403 via `dev/router.php`: `/app/bootstrap.php /config.php /db/schema.sql`.

`/sitemap.xml` returns 404 on this copy **by design**: `install.php` sets `site_indexable=0` on any non-canonical host and `app/controllers/sitemap.php` honours it. With the setting flipped on for one request it returned 200 `application/xml`, well-formed (`xmllint`), 42 `<url>` entries covering every static page, collection, scent family and active product. The setting was restored to `0` (same `updated_at`) and the sitemap cache file removed. On the real domain the installer leaves it on. `robots.txt` is real (Disallow list + `Sitemap:` line).

## Step 3 — cart API detail (`WELCOME10` = 10 %, min order Rs. 3,000; shipping Rs. 250, free from Rs. 3,000)

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
| add sold-out size 10 | 409 | 409 `sold_out` |
| add size 17 ×5 (stock 3) | clamp to 3 | qty 3, max_qty 3, notice "Only 3 left…" |
| add size 99999 | 404 | 404 `unavailable` |
| POST without CSRF | 419 | 419 |
| POST with `Origin: https://evil.example` | 403 | 403 `origin` |

Below Rs. 3,000: no product in the catalogue is priced under Rs. 3,950, so a live cart cannot fall between Rs. 1 and Rs. 2,999. The threshold branch was proven at unit level through the app bootstrap: `coupon_validate(WELCOME10)` at Rs. 2,500 → `below_min` "Add Rs. 500 more", Rs. 2,999 → `below_min` "Add Rs. 1 more", Rs. 3,000 → applied Rs. 300, Rs. 5,450 → applied Rs. 545. Free-shipping threshold likewise cannot be undercut by the catalogue; the "Free" case is verified above.

## Step 4 — checkout detail

All three orders carried `User-Agent: storefront-smoke-8088` and unique phones (`0300 8088001–4`); form-trap timestamp waited ≥ 3 s.

- **COD** — cart Cirrus 50ml ×1. `POST /checkout` → 303 `/order/SF-260928-D2CN?t=…`. Receipt 200 (after the lowercase 301), title `Order SF-260928-D2CN | Sky Fragrances`, method Cash on Delivery. Wrong access token → 303 → `/track?order=…` (200). `POST /track` with the right phone → 200 showing the line, "Pending", Rs. 5,450; with a wrong phone → nothing revealed beyond the echoed input.
- **Bank transfer** — cart Silver Lining 50ml ×1, reference `SMOKE8088REF1`, 480×320 PNG proof. → 303 receipt 200 showing the reference and "Awaiting verification"; `payment_status = awaiting_verification`; `payment_proofs` row with the proof normalised to JPEG under `storage/proofs/2026/09/`.
- **Oversell** — two sessions each holding Saffron Zenith 50ml ×3 (stock 3). Session D placed first (stock → 0); session C then posted with its earlier price token → **409** with "… sold out. Remove it to continue." and no order written.
- Stock was decremented once per line (40→39, 45→44, 3→0) and is back at 40 / 45 / 3. `WELCOME10.used_count` stayed 0 (no coupon on placed orders). Deleted afterwards: orders 6–8 (cascade to items, history, proofs), their 3 `email_outbox` rows, the proof file, the 3 `mail-preview` HTML files, and my 6 session files. `orders` is back to 5 rows, `payment_proofs` to 4, no orphans.

## Step 5 — intro loader detail (Playwright, Chromium)

| Context | html class at load | `#sf-intro` removed at | Hero CTA at 2.6 s |
|---|---|---|---|
| desktop 1440×900, 1st visit | `sf-intro-full` | 2245 ms | clickable → navigated to `/shop` |
| desktop, 2nd navigation | `sf-intro-quick` | 401 ms | clickable |
| mobile 375×812, 1st visit | `sf-intro-full` | 1960 ms | clickable |
| mobile, 2nd navigation | `sf-intro-quick` | 400 ms | clickable |
| reduced motion | `sf-intro-calm` | 1250 ms | clickable |

Zero page errors and zero console errors in every context; `sf-intro-done` set on `<html>` after removal.

## Step 6 — tour

`node dev-tools/tour.mjs http://127.0.0.1:8088 docs/launch/shots-store / /shop /collections /for-her /product/azure-oud /scent-finder /cart /checkout /track /contact /faq /about` with `TOUR_COOKIE=SFSHOP=<session holding one Cirrus 50ml>` so `/checkout` renders the real form instead of bouncing to `/cart`. 12 paths × desktop + mobile = 24 PNGs; `report.json` has 0 errors, 0 overflow, all 200. Spot-checked home desktop, checkout mobile and product mobile by eye: full pages, no broken layout. The "Stage4…" banner/tagline text and the blue placeholder art on two products are another agent's live dev data, not build defects.

## Step 7 — MariaDB

`mariadb 10.11.19` on 127.0.0.1:3307, database `skyfragrances_maria` (tables dropped first with `FOREIGN_KEY_CHECKS=0`). `db/schema.sql` then `db/seed.sql` via `mariadb --show-warnings`: exit 0, empty output for both (no warning, error or note). 26 tables, all InnoDB / utf8mb4. Seed counts: 12 products, 24 sizes, 5 collections, 3 coupons, 79 settings, 5 quiz questions / 19 options, 6 content pages, 5 scent families, 0 admin users (the admin user is created by `install.php`, as designed).

Note for deployers: Homebrew's MySQL 9 client cannot talk to MariaDB (`mysql_native_password` plugin missing); use the `mariadb` client or phpMyAdmin.

## Step 8 — logs

`storage/logs/app-2026-09.log` lines added during the run: one `warning CSRF check failed` (my deliberate no-token request, step 3), three `info mail: transport unavailable, preview written` (expected with no SMTP configured), plus `admin login failed` lines from the other agent's concurrent admin work. No `error` entries. Server log `/tmp/skyfr-8088.log`: no PHP warning/notice/fatal from the app and no 5xx; the only line is the machine-level `Module "imagick" is already loaded` php.ini duplicate at startup, unrelated to the site.
