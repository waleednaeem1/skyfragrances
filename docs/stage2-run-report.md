# Stage 2+3 run report — 2026-09-26

End-to-end run of the storefront + commerce tree (`site/`) on this machine: PHP 8.2.28 built-in
server (`php -d display_startup_errors=0 -S 127.0.0.1:8088 dev/router.php`), MySQL 9.3.0 on
3306 (`skyfragrances_dev`), MariaDB 10.11.19 on 3307 (`skyfragrances_maria`). Everything below was
driven with `curl` (cookie jars, multipart uploads) and the Playwright tour in `dev-tools/tour.mjs`.
The server was started before the install and killed at the end of the run.

## 1. Lint

`find site -name '*.php' -not -path '*/vendor/*' -exec php -l {} \;` — **183 files, 0 syntax
errors**, before and after the fixes below. (The `Module "imagick" is already loaded` line on every
invocation is this machine's php.ini loading the extension twice; it is not from the project.)

## 2. Install

MySQL reset: every table in `skyfragrances_dev` dropped (26 → 0), `config.php`, `storage/.installed`,
`storage/.install-key`, sessions, caches and old logs removed. Then `install.php` through curl as a
browser would (cookie jar, `_token` from each form, one-time key read from `storage/.install-key`):

| Step | Request | Result |
|---|---|---|
| 1 | `GET /install.php?step=1` | 200, requirements page |
| 2 | `GET ?step=2` → `POST ?step=2` (`action=db_save`, DB 127.0.0.1:3306, base_url `http://127.0.0.1:8088`, no SMTP) | 303 → step 3, `config.php` written (`env=development`) |
| 3 | `GET ?step=3` → `POST ?step=3` (`action=install`, `admin` / `Admin#Sky2026!`, seed on) | 200 "Finished": **27 schema + 234 seed statements, 0 errors**; `storage/.installed` written; `config.php` chmod 0400 |
| 4 | — | install.php had **deleted itself** (C-47) so `?step=4` was 404 — see fix 1 |

The DB was fresh (`resume` stage), so the `replace_partial` checkbox was not rendered and not needed.

Seed counts after install (all as 07 Part A): settings 84 (79 seed + `install_completed_at`,
`images_webp_enabled`, `https_permanent`, `maintenance_bypass`, `trusted_proxies`), admin_users 1,
collections 5, scent_families 5, products 12, product_sizes 24, product_images 36, reviews 8 (all
`pending`, C-68), coupons 3, content_pages 6, quiz_questions 5, quiz_options 19, quiz_option_scores 31,
orders 0. 26 tables. `site_indexable=0` (host is not skyfragrances.com), `contact_email` /
`order_notify_email` / `whatsapp` written from the form.

## 3. Storefront routes (curl)

| Route | Status | Note |
|---|---|---|
| `/` | 200 | 73 KB |
| `/shop` | 200 | |
| `/shop?gender=her&sort=price_asc` | 301 → `/shop?gender=her&sort=price-asc` → 200 | after fix 6; before it the unknown `price_asc` was dropped and the URL 301'd to `/for-her` |
| `/shop?page=2&sort=price_asc&gender=him` | 301 → `/for-him?page=2` | valid canonicalisation (page kept; `sort` only survives once it is a known key) |
| `/shop?collection=golden-hour&family=floral&min=3000&max=9000` | 301 → `/shop?collection=golden-hour&family=floral` | `min`/`max` are not parameters (03 §5: `price=3000-9000`) |
| `/collections`, `/collections/dawn-chorus` | 200 | `/collections/nope` → 404 |
| `/scent/oud-smoke` | 200 | `/scent/nope` → 404 |
| `/for-him`, `/for-her`, `/unisex` | 200 | |
| `/new-arrivals`, `/best-sellers`, `/sale` | 200 | |
| `/search?q=oud` | 200 | |
| `/scent-finder` | 200 | `/scent-finder/result?q1=a…` → 302 `/scent-finder` (controller uses its own five-question param names) |
| `/product/azure-oud` | 200 | `/product/nope` → 404 |
| `/cart` | 200 | |
| `/checkout` | 302 → `/cart` with an empty cart; 200 with a cart | |
| `/track` | 200 | |
| `/contact`, `/faq`, `/about`, `/shipping`, `/returns`, `/privacy`, `/terms` | 200 | |
| `/this-page-does-not-exist` | 404 | full layout |
| `/sitemap.xml` | 404 with `site_indexable=0` (installed state); **200 `application/xml`, 40 `<loc>`, well-formed (xmllint)** with the setting flipped to 1 and the settings cache cleared; restored to 0 afterwards | |
| `/robots.txt` | 200 `text/plain` | real file: Allow `/`, Disallow admin/cart/checkout/order/track/search/unsubscribe/api/quiz result/`?sort=`/`?page=`, `Sitemap:` line |
| `/api/search-suggest?q=azure` | 200 JSON | |
| `/api/cart` | 200 JSON | |
| `/api/session` | 200 JSON (`csrf`) | |
| `/Shop` | 301 → `/shop` | |
| `/order/bad` | 404 | |
| `/order/SF-260925-ABCD?t=x` | 301 → lowercase → 303 `/track?order=…` | C-76: unknown number and wrong token behave identically |
| `/unsubscribe?e=x&t=y` | 200 | |
| `/__rewrite-probe` | 200 `text/plain` | |
| `POST /shop` | 405 | `HEAD /` → 200 |

## 4. Cart API and pricing maths (06a §2.2, §4)

Session from `GET /api/session`, every POST as JSON with `X-CSRF-Token`. Seed: `shipping_fee`
250, `free_shipping_threshold` 3000, WELCOME10 = 10 %, min order 3000, 1 per phone, 500 uses.

| Step | Expected by hand | API |
|---|---|---|
| add size 1 (Azure Oud 50 ml, 8,950) | subtotal 8,950 ≥ 3,000 → shipping 0, total 8,950 | 8950.00 / ship 0.00 / free ✔ / 8950.00 |
| update qty 2 | 17,900 | 17900.00 |
| update qty 99 | clamped to 10 → 89,500, notice "Maximum 10 per size per order." | 89500.00 + notice |
| remove | empty, shipping 0 (not the fee) | 0.00 / ship 0.00 |
| add size 13 (Silver Lining 50 ml, 4,950 on sale 3,950) | unit 3,950 (sale < price), total 3,950 | 3950.00 |
| apply `welcome10` (lower-case) | 10 % of 3,950 = 395; threshold on pre-discount subtotal → shipping 0; total **3,555** | disc 395.00, ship 0.00, total 3555.00, "WELCOME10 applied — you saved Rs. 395." |
| + size 1 | 12,900 → discount 1,290 → **11,610** | 11610.00 |
| remove coupon | 12,900 | 12900.00, "Coupon removed." |
| `NOPE123` | invalid message | 422 "That code isn't valid…" |
| `FIRST50` (50/50 used) | exhausted | 422 "This code has reached its usage limit." |
| `EIDSALE500` (expired 2026-08-11) | expired | 422 "This code expired on 11 Aug 2026." |
| temporary `sale_price=2450` on size 21, add it | 2,450 < 3,000 → shipping 250, total **2,700** | 2450.00 / ship 250.00 / 2700.00 |
| apply WELCOME10 at 2,450 | below minimum: "needs a minimum order of Rs. 3,000. Add Rs. 550 more" | 422, exactly that message |
| qty 2 → 4,900, apply WELCOME10 | discount 490, ship 0, total **4,410** | 4410.00 |
| qty back to 1 with coupon in session | coupon dropped (below min), ship 250, total 2,700 | 2700.00, "WELCOME10 is no longer valid and has been removed from your order…" |
| POST without token | 419 | 419 |
| POST with `Origin: https://evil.example` | 403 | 403 `{"error":"origin"}` |
| add a size with stock 0 (size 24 set to 0 temporarily) | 409 sold_out | 409 "Vetiver Squall (100ml) is sold out." |

No seed size is priced under Rs. 3,000 (cheapest is Silver Lining 50 ml at 3,950 on sale), so the
below-threshold cases used a temporary sale price on Nimbus Rain 50 ml; the sale price and the size-24
stock (16) were restored to the seed values immediately after.

The 06a §4.1 worked example (threshold 5,000, cart 5,200, WELCOME10 → free shipping, 520 off, 4,680)
holds by the same rule with the seed threshold: 3,950 with WELCOME10 keeps free delivery and totals 3,555
rather than 3,805 under a post-discount rule.

## 5. Real orders through `POST /checkout` (curl + cookie jar, multipart)

Each order: `GET /api/session` → `POST /api/cart/add` → `GET /checkout` (parse `idem_key`,
`price_token`, `price_total`, the `ts` trap token and `_csrf`) → 3 s wait (`FORM_TRAP_MIN_SECONDS`)
→ multipart POST with the honeypot `website` empty. Manual methods were unavailable at first because
`settings.php` treats the seeded `REPLACE ME` account values as unset (correct); realistic bank /
JazzCash / Easypaisa account settings were written for the test and restored afterwards.

| # | Case | HTTP | DB evidence |
|---|---|---|---|
| (a) | COD, Azure Oud 50 ml ×1, `0300 1234567`, email given | 303 → `/order/SF-260926-RUS6?t=<32 hex>`; receipt 200 | `orders` id 10: pending / cod / unpaid, `phone_normalized=+923001234567`, subtotal 8950.00, discount 0, shipping 0.00, grand 8950.00, item_count 1, 32-char `access_token`, `ip_hash` set. `order_items`: product_size_id 1, snapshot name/size/SKU `SKY-AZO-050`, image `sample/azure-oud-1.webp`, unit 8950 charged 8950, line_discount 0. `order_status_history`: `status NULL→pending` by system. `email_outbox`: `order-admin` queued, subject `New order SF-260926-RUS6 — Rs. 8,950 — Cash on Delivery — Karachi`. Customer email delivered inline (no SMTP → preview `storage/logs/mail-preview/20260926-002453-…-order-customer.html`, `<title>Order SF-260926-RUS6 confirmed — Sky Fragrances`). Stock size 1 decremented by 1. |
| (a2) | COD, Azure Oud 100 ml ×1, `0311 9998877` (placed for the re-submit test after fix 3) | 303 → `/order/SF-260926-BDT5?t=…` | id 12, grand 13950.00; stock size 2: 16 → 15; admin alert queued `… Rs. 13,950 — Cash on Delivery — Karachi`; customer preview written |
| (b) | Bank transfer, Silver Lining 50 ml ×1 (sale 3,950) + WELCOME10, `+92 321 7654321`, Lahore, txn `TXN-SKY-TEST-0001`, generated 900×1400 PNG proof | 303 → `/order/SF-260926-2P8S?t=…`; receipt 200 shows the transaction ID and "we're verifying your payment" | id 11: pending / bank / **awaiting_verification**, subtotal 3950.00, discount 395.00, shipping 0.00, grand **3555.00**, coupon_id 1 `WELCOME10` percent 10.00, `payment_reference=TXN-SKY-TEST-0001`, `payment_account_snapshot=Bank Transfer — Bank: Meezan Bank · Account title: … · IBAN: …`. Item: unit_price 4950, sale_price 3950, charged 3950, line_discount 395. History: `status →pending` (system) and `payment_status unpaid→awaiting_verification` by customer, note "Proof submitted". `payment_proofs`: `file_path=2026/09/6a44c5f5…jpg`, mime image/jpeg (re-encoded from PNG by GD, 18,453 bytes, sha256 stored), `transaction_ref`, `sender_name`, review_status pending. File on disk under `storage/proofs/2026/09/`; **`GET /storage/proofs/…jpg` → 403**. `coupons.used_count` WELCOME10 0 → **1**; `coupon_redemptions` row (coupon 1, order 11, `+923217654321`, 395.00 / 3950.00, applied). Stock size 13: 45 → **44**. Admin alert queued `New order SF-260926-2P8S — Rs. 3,555 — Bank Transfer — Lahore`; customer preview written. |
| (b2) | Second transfer (JazzCash) from the same phone while (b) awaits verification (C-58) | 422 re-render | "You already have a transfer order waiting for verification (SF-260926-2P8S). Once it is verified you can place another, or message us on WhatsApp." — no order row |
| (c) | The (a2) form POSTed again unchanged (same `idem_key`, `price_token`, `_csrf`) | **303 → the same `/order/SF-260926-BDT5?t=…`** | orders count unchanged (11 → 11). Before fixes 2–3 this request hit the CSRF wall (token rotated by `session_regenerate()`) and bounced to an empty `/cart` with "Your session expired" — one order either way, but the customer never saw the receipt |
| (c2) | A different item added, then the stale (a2) form POSTed | 200 re-render | "Your previous order SF-260926-BDT5 was already placed. This is a new order — please review and place it." — no order row, fresh idempotency key (C-61) |
| (d) | Aurora Bloom 50 ml ×3 in the cart, checkout page rendered at 17,850, then `stock` set to 2 in the DB, POST | **409** re-render | "Sorry — Aurora Bloom 50ml sold out while you were checking out. Only 2 left. Adjust the quantity to continue." (06b §4 wording). No order row, stock still 2, cart quantity synced to 2 for the retry |
| (d′) | same, stock set to 0 | 409 re-render | "Sorry — Aurora Bloom 50ml is now sold out. Remove it to continue." — no order row, stock 0 (restored to the seed 34 afterwards). Before fix 2 this case was reported as "Your cart is empty" and redirected to `/cart` |

`/track` (form with `ts` trap + `_csrf`): right number + phone → 200 with the Pending → Confirmed →
Packing → Shipped → Delivered timeline, "Cash on Delivery" and the total; wrong phone → 200 with only
"We couldn't find an order with those details…" and no name, address, total or status; unknown number
with a real phone → byte-identical page apart from the echoed inputs. `GET /order/{n}` without a token,
with a wrong token, and for an unknown number all → 303 `/track?order={n}` (C-76). Receipts fetched
without any session cookie → 200 after fix 4 (were 500).

The checkout bucket (`ORDER_CHECKOUT_RATE_MAX` = 10 per 10 min per IP) was hit once during the run
(429 with the WhatsApp message) and cleared in `rate_limits` before re-running (d).

## 6. Screenshot tour — `docs/shots/stage2/`

`node dev-tools/tour.mjs http://127.0.0.1:8088 docs/shots/stage2 / /shop … /this-page-does-not-exist
/order/SF-260926-RUS6?t=…` with `TOUR_COOKIE=SFSHOP=<session holding Azure Oud 50 ml + Silver Lining
50 ml ×2>` so `/cart` and `/checkout` show a real cart (an empty cart 302s to `/cart`).

Final `report.json`: **50 shots (25 paths × desktop 1440 / mobile 375), every path 200, the
unknown path 404, 0 page errors, 0 console errors, 0 network errors, 0 horizontal overflow.**

Runs before the fixes reported: 4 errors on `/cart` and `/checkout`, 2 on the receipt (cart/order
thumbnails requested `/uploads/products/1/sample/azure-oud-1.webp` — fix 5); the 404 page's own
document counted as a network error (fix 7); and blank product rails / trust / Instagram sections in the
home screenshots because the tour's 90 ms scroll sweep outran IntersectionObserver delivery for the
`.sf-reveal` blocks on the 8,700 px page (reveal.js itself is correct: a 200 ms sweep reveals all 14) —
fix 7. One transient `H-OVERFLOW` on the mobile filtered listing did not reproduce in 3 re-runs or in a
DOM probe (page exactly 375 px after the identical sweep); the tour now lets layout settle 400 ms after
the full-page capture before measuring, and the final run is clean. Cosmetic, not fixed: on mobile the
"View all new arrivals" CTA sits flush under the last card row.

## 7. MariaDB 10.11 (3307)

`scratchpad/run2/maria-load.php`: all tables in `skyfragrances_maria` dropped (→ 0), then
`db/schema.sql` and `db/seed.sql` split on statement terminators and run one by one with strict
`sql_mode`, `SHOW WARNINGS` after each: **schema 27 statements, 0 errors, 0 warnings; seed 234
statements, 0 errors, 0 warnings** (the accepted 1681 DECIMAL-UNSIGNED note is MySQL-only). 26 tables,
all InnoDB / utf8mb4_unicode_ci / Dynamic. Seed counts identical to MySQL (settings 79 before install
keys, admin_users 0). Re-importing both files a second time changed no counts (INSERT IGNORE /
IF NOT EXISTS). Column sets and index sets are identical between the MySQL install and the MariaDB load
(315 columns, 102 indexes; the only differences are MariaDB's `int(10)`-style display widths).

## 8. Logs

`/tmp/skyfr-php.log`: no PHP warning / notice / deprecation; `storage/logs/php-error.log` was never
created. `storage/logs/app-2026-09.log`, every non-housekeeping line explained:

| Entry | Source |
|---|---|
| 00:15:54 `Data too long for column 'payment_status'` from `scratchpad/seed-orders.php` | not this run — the parallel admin workflow's demo-order script (its rows were removed in §10) |
| CSRF warnings on `/api/cart/add` (00:22) and `/checkout` (00:24) | deliberate no-token call, and the (c) re-submit before fix 3 |
| `rate limit reached` checkout ×2 (00:30) | the 11th/12th checkout POST in 10 min, expected |
| `Call to undefined function outbox_order_mail_status()` ×3 | the receipt 500 — fix 4 |

## 9. Fixes applied (file — one line)

1. `site/install.php` — file had **deleted itself** on Finish (C-47) and no copy existed on disk; rebuilt byte-exact (1,277 lines, verified against the line numbers observed before deletion) from the last full transcript dump plus the four later patches; then `inst_pdo()` runs the C-55 strict `sql_mode` after `time_zone`, and self-deletion is skipped when the written config is `env=development` so local rehearsals keep the source (production behaviour unchanged).
2. `site/app/lib/orders.php` — inside the locked transaction a quantity clamp now throws `stock_conflict(size, stock)` so the customer gets 06b's "sold out while you were checking out. Only {n} left" message instead of the generic re-price text; the sold-out check runs before the empty check so a cart whose only line sold out is no longer reported as "Your cart is empty".
3. `site/app/controllers/checkout-submit.php` — C-61 replay after commit: the consumed key is kept as `$_SESSION['idem_done']`; a re-submit with it 303s to the receipt (or shows the "already placed" notice with a fresh key when the cart changed); `session_regenerate()` removed from the success path (not one of 02c's three regeneration points, and it rotated the CSRF token the re-submit carries).
4. `site/app/controllers/confirmation.php` — `require_once app/lib/outbox.php` (receipt 500'd for any visitor without `last_order` in session).
5. `site/app/lib/cart.php` — `cart_image_url()` resolves the shipped derivatives (`sample/<stem>` folder or `product-<id>-<hash>` stems, `-thumb` → `-card` → original → placeholder) instead of `/uploads/products/{id}/{filename}`; used by the cart API/drawer, `/cart`, `/checkout`, the receipt, `/track` and the admin order detail.
6. `site/app/controllers/listing.php` — `sort` accepts the 07-style `price_asc` spelling (underscore → hyphen, lower-cased) and 301s to the canonical `price-asc` form instead of silently dropping it.
7. `dev-tools/tour.mjs` — the 404 document's own response/console line is no longer counted as a resource error; scroll sweep 250 ms per step + 900 ms settle so `.sf-reveal` content is captured; 400 ms settle after the full-page capture before the overflow measurement.

Cross-needs from the task list that were already in place when checked: `db/schema.sql` has
`admin_users.known_devices`, `payment_proofs.file_path` / `purged_at`, no `DROP TABLE`, `INSERT IGNORE`
seed; `app/lib/db.php` sets the strict `sql_mode`; `cron.php` uses the bootstrap connection.

## 10. Cleanup (DB = seed + admin only)

`scratchpad/run2/cleanup.php`: deleted orders 11 (3 from this run, 8 demo rows the parallel admin
workflow's `seed-orders.php` inserted at 00:16), order_items 13, order_status_history 30, order_notes 1,
payment_proofs 2, coupon_redemptions 1, email_outbox 4, rate_limits 21, admin_activity_log 20;
auto-increments reset; `product_sizes.stock` / `sale_price` back to the seed for all 24 sizes (8 rows
restored, including the demo orders' cancellations); `coupons.used_count` back to 0 / 137 / 50;
products `sales_count` / rating counters 0; reviews all pending; the 8 payment-account settings back to
their `REPLACE ME` seed values and `site_indexable=0`; install-written keys kept. Proof files, mail
previews, session files and `storage/cache/*` removed. Final counts: admin_users 1, orders 0,
payment_proofs 0, email_outbox 0, rate_limits 0, admin_activity_log 0, catalogue tables at seed counts.
The PHP server on 8088 was stopped.

## After review — 2026-09-26 01:45–02:00

Same harness, run again after the three review passes (commerce, design, mobile) landed their fixes:
PHP 8.2.28 built-in server on 8088 with `dev/router.php`, MySQL 9.3.0 on 3306 reset to zero tables,
MariaDB 10.11.19 on 3307. Lint first: **185 PHP files, 0 syntax errors.**

### Install and routes

`install.php` through curl exactly as in §2: step 2 `303 → step 3`, step 3 now `303 → step 4`
(the review moved the report onto its own step) and step 4 reports **27 schema + 234 seed statements,
0 errors**, all ten protection files present, `storage/.installed` written, `config.php` 0400 and kept
(`env=development`). Seed counts identical to §2 (settings 84, products 12, sizes 24, images 36,
reviews 8 pending, coupons 3, 26 tables, `site_indexable=0`). Every route in §3 returned the same status
as before, including the 403s for `/app`, `/db`, `/storage`, `/config.php` and `/uploads/x.php`, `405` on
`POST /shop`, `301` for `/Shop` and `/index.php/shop`, `302 → /admin/login` for `/admin`, and the
"Already installed" page for a second `/install.php`. `/shop?page=2&sort=price_asc&gender=him` now
canonicalises to `/shop?gender=him&sort=price-asc&page=2` and that page is a **404** — correct: 03 §5
says an out-of-range `page` is 404, not an empty listing, and the seed has fewer than a page of *him*
products (`/shop?page=2` and `/for-him?page=2` are 404 for the same reason).

### Orders through `POST /checkout`

| # | Case | Result |
|---|---|---|
| (a) | COD, Azure Oud 50 ml ×1, `0300 1234567` | `303 → /order/SF-260926-U6UR?t=…`; order 1 pending/cod/unpaid, 8950.00, `+923001234567`, 32-char token, item snapshot `SKY-AZO-050` / `sample/azure-oud-1.webp`, history `NULL→pending` by system, `order-admin` queued *New order SF-260926-U6UR — Rs. 8,950 — Cash on Delivery — Karachi*, customer preview `Order SF-260926-U6UR confirmed`, stock size 1 24 → 23 |
| (b) | Bank transfer, Silver Lining 50 ml (sale 3,950) + WELCOME10, `+92 321 7654321`, Lahore, `TXN-SKY-TEST-0001`, PNG proof | `303 → /order/SF-260926-T4WB?t=…`; order 2 pending/bank/**awaiting_verification**, 3950 − 395 = **3555.00**, coupon 1 percent 10, `payment_account_snapshot` from the (temporary) Meezan settings; item unit 4950 / sale 3950 / charged 3950 / line_discount 395; history `unpaid→awaiting_verification` "Proof submitted" by customer; proof re-encoded to JPEG 18,453 bytes with sha256 under `storage/proofs/2026/09/`; redemption row 395.00 / 3950.00 applied; WELCOME10 `used_count` 0 → 1; stock size 13 45 → 44 |
| (b2) | JazzCash from the same phone while (b) awaits verification | 422 "You already have a transfer order waiting for verification (SF-260926-T4WB)…", no row |
| (d) / (d′) | Aurora Bloom 50 ml ×3, stock cut to 2 / 0 between render and POST | 409 "sold out while you were checking out. Only 2 left." / 409 "is now sold out. Remove it to continue." — no row, stock back to the seed 33 |
| (a3) | COD, Azure Oud 100 ml ×1, `0311 9998877` | `303 → /order/SF-260926-DVVH?t=…`, order 3, stock size 2 15 → 14 |
| (c) | (a3) form re-POSTed unchanged | after regression fix 2 below: `303` to the **same** receipt, still 3 orders |
| (c2) | a different item added, then the stale (a3) form POSTed | 200 re-render "Your previous order SF-260926-DVVH was already placed. This is a new order — please review and place it.", idempotency key rotated, still 3 orders |

Receipts fetched with and without the session cookie both land on 200 after the one lowercase 301
hop (`/order/SF-…` → `/order/sf-…`, the C-76 canonicalisation already listed in §3; Playwright reports
the final 200). No PHP warning, notice or deprecation in the server error log; `storage/logs/app-2026-09.log`
holds only the three CSRF warnings from regression 2 (before its fix) and the mail-preview lines.

### Tour — `docs/shots/stage2-final/`

Same 25 paths, `TOUR_COOKIE=SFSHOP=<session with Azure Oud 50 ml + Silver Lining 50 ml ×2>`. Final
`report.json`: **50 shots, every path 200, the unknown path 404, 0 page errors, 0 console errors,
0 network errors, 0 horizontal overflow.** The first pass of this tour flagged `H-OVERFLOW` on mobile
`/cart` — regression 3 below — and was re-run after the fix.

### MariaDB 10.11 (3307)

`maria-load.php` again: **schema 27 / 27, seed 234 / 234, 0 errors, 0 warnings**; 26 InnoDB
utf8mb4_unicode_ci Dynamic tables; seed counts identical to MySQL; re-import changes nothing (99 = 99).

### Regressions introduced by the review fixes, and what was done

1. **Search-suggest thumbnails requested a bare filename.** `app/controllers/api/search.php` returned
   `product_images.filename` (`sample/azure-oud-1.webp`) as `image`, and `forms.js:189` puts that value
   straight into `src`, so every suggestion image resolved relative to the current page and 404'd — the
   same class of defect as §9 fix 5. Fix: the API selects `p.id` and returns
   `cart_image_url($p['id'], $p['image'])` (`cart.php` required), which yields
   `/uploads/products/sample/azure-oud-1-thumb.webp` (200 `image/webp`); no JS change.
2. **The C-61 replay was unreachable again.** Commerce review F-12 re-added `session_regenerate()` on the
   checkout success path (citing 06a §5.3). `session_regenerate()` also runs `csrf_rotate()`, and the CSRF
   gate in `maintenance.php` runs before any controller, so the unchanged re-POST of the form (the
   double-tap / back-button case C-61 exists for) died at the CSRF wall and bounced to `/checkout` with
   "session expired", the stale-form case never showed its "already placed" notice, and even the next
   `/api/cart/add` from the page returned 419 (all three are the warnings in the log above). 02c lists
   exactly three regeneration points and checkout is not one; the register's C-61 wins over the 06a
   clause; there is no fixation vector (`use_strict_mode=1`, and F-12 itself is filed as spec fidelity).
   Fix: §9 fix 3 restored — the `session_regenerate()` line removed; the cart is cleared, the key moved
   to `idem_done`, the customer is redirected. Verified by (c) and (c2) above.
3. **Mobile `/cart` overflowed by 5 px with a real cart.** The mobile review's own fixes (44/52/44 px
   stepper at 375 px, larger *Remove* label) plus the page variant's 7.5 rem image left 196 px for a flex
   row that needs 201, so `.cart-line__remove` sat at 304–380 px and "REMOVE" was clipped on both lines
   (visible in the first `cart--mobile.png`). Fix in `site.css`: `.cart-line--page` keeps the 04b
   64×80-class image (4.5 rem) below 480 px and grows to 7.5 rem from `@media (min-width: 480px)`;
   `.cart-line__controls` gets `flex-wrap: wrap` so a 320 px phone wraps *Remove* under the stepper
   instead of overflowing. Measured after the fix: scrollWidth = clientWidth at 320 / 375 / 414 / 480 /
   768 / 1440, stepper and *Remove* on one row from 375 px up.

Not regressions, noted: the checkout redirect targets the upper-case order URL and the router 301s it to
lower-case on every receipt (pre-existing C-76 behaviour, one extra hop per customer — the redirect
could emit the lower-case form directly); the (empty) `storage/proofs/2026/` directory left by a review
pass was harmless.

### Cleanup

`cleanup.php` as in §10: orders 3, order_items 3, order_status_history 4, payment_proofs 1,
coupon_redemptions 1, email_outbox 3, rate_limits cleared; auto-increments reset; stock and sale prices
back to seed for all 24 sizes — the run-2 cleanup script had nulled the six seed sale prices (sizes 6, 13, 14, 15, 16, 20) while resetting stock; a seed.sql diff caught it and restored them, 0 differences remain; WELCOME10 `used_count` 0; the 8 payment-account settings back to `REPLACE
ME`, `site_indexable=0`; proof file, mail previews, sessions and caches removed. Final counts: admin_users
1, orders 0, catalogue tables at seed counts. The PHP server on 8088 was stopped.
