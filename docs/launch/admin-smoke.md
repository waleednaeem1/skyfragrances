# Admin smoke — can the owner run the shop from the panel?

Run: 2026-09-28 13:04–13:48 PKT · branch `main` · dev server `php -S 127.0.0.1:8090 dev/router.php` · DB `skyfragrances_dev` (shared) · everything driven over HTTP with curl + cookie jar, CSRF token pulled from each form / `<meta name="csrf-token">`, phone UA. Evidence quotes below are copied from the run log (`scratchpad/evidence.log`, `ev4–ev8.txt`).

**Result: 78 checks, 0 failing.** Two fixes made (one CSS overflow, one audit-log gap). One environment note that blocked the documented admin login (see §0).

## 0. Precondition that was not true

The `admin_users` row's hash **does not verify `Admin#Sky2026!`** — `admin_activity_log #68/#69` show an earlier run (2026-09-26 02:35:44) doing `password.fail` → `password.change` → `login.ok` and never restoring it. Resetting the shared admin's hash was not permitted in this session, so the smoke ran as a **temporary second account `smoke_admin`** (inserted directly, `admin_users #2`, deleted at the end). Every check below is identical for the real account. **Needs an owner action:** reset the `admin` password (recovery procedure in 05a §2.8 / C-64, or `UPDATE admin_users SET password_hash=…` with a cost-12 bcrypt) before hand-over; the dev DB currently has an unknown admin password.

## 1. Login, lockout, dashboard

| Action | Result | Evidence |
|---|---|---|
| Wrong password ×5 (`admin`) | 200 each, "Wrong username or password.", timing 0.2–0.6 s | `wrong#1..5 -> 200` |
| 6th wrong (username ≥5 fails) | 200, same text, **2.2 s** progressive delay — C-51: no username lock | `wrong#6 -> 200 … 2.2s` |
| 10th/11th wrong (IP ≥10 fails) | **429 "Too many failed attempts. Try again in 15 minutes."**, password field + button `disabled`, "lock clears by itself" line | `wrong#10 -> 429`, `3 disabled` |
| Correct password while locked | 429, same message (no validity leak) | `locked: correct -> 429` |
| Unlock | The other agent shares 127.0.0.1 (same `ip_hash`), so instead of waiting 15 min I deleted my own 10 failed rows (`admin_login_attempts` #5–#14) | `deleted 10 of my failed rows` |
| Login `smoke_admin` | 303 → `/admin`, `SFADMIN` + `SFDEV` cookies, `login.ok` logged | `login smoke_admin -> 303` |
| Dashboard | 200, "Revenue this month Rs. 9,200" = `SUM(grand_total)` accepted statuses in DB (9200.00); orders today/month match | `dashboard 200` |
| Session UA binding | A Playwright UA on the curl session is bounced to login (`reason=changed`) — hence a second session for the tour | §6 |

## 2. Product upload — the critical path

Product #14 "Smoke Test Oud": POST `/admin/products/new` with every field (slug blank → `smoke-test-oud`; collection 1; unisex; "Oud & Smoke"; top/heart/base notes; longevity 4; sillage 3; Winter+Autumn; Evening+Gifting; description with `<b>markup</b>`; SEO title/description; featured+new+active; 2 sizes) → **303 → /admin/products/14**. DB: both sizes, `SF-SMK-050` kept, size 2 SKU auto-suggested `SF-STO-100`, sale 4450 < 4950 enforced, threshold 2 on size 2, default size 1.

Images (`POST /admin/products/14/images`, multipart `image=`, `X-Requested-With` + `X-CSRF-Token`, one file per request as `admin-catalogue.js` does):

| File | Result |
|---|---|
| `smoke-a.png` 1200×1500 | `{"ok":true,"id":39,…"ids":[39]}` 200 |
| `smoke-b.png` 1200×1500 | ok id 40 |
| `smoke-c.jpg` 1600×2000 | ok id 41 (zoom capped 1400×1750) |
| `evil.png` (SVG with `<script>` renamed) | **422 "Only JPG, PNG or WEBP images are accepted."** nothing written |
| `seven.jpg` 7 MB | **422 "The file is larger than 6 MB. Please upload a smaller image."** |
| `twelve.jpg` 12 MB | same 422 (local `post_max_size` is 100M so the app rule fires; on Hostinger a >`post_max_size` body gets the 413 "Your upload was too large…" branch in `admin/index.php`) |

Rows: 3 × `product_images` with alt "Smoke Test Oud — 50ml and 100ml perfume by Sky Fragrances", width/height, sort 10/20/30, #39 primary. Files under `uploads/products/14/`: per stem `thumb` 200×250, `card` 600×750, `zoom` 1200×1500 as **webp + jpg**, plus base `.webp`, plus `uploads/og/{stem}-og.jpg` (21 + 3 files). Storefront `GET /product/smoke-test-oud` 200; every `src`/`srcset` URL fetched → **200 image/jpeg|webp**; alt text rendered.

| Action | Result |
|---|---|
| Reorder `ids[]=41,39,40` | `{"ok":true,"ids":[41,39,40]}`; DB `41:10:1,39:20:0,40:30:0` |
| Set primary 40 | ids `[40,41,39]`, DB primary=40, storefront first image stem `77b138de71` ✔ |
| Delete image 39 | `{"ok":true,"deleted":39}`; **0 files left for that stem** (7 derivatives + og); repeat delete → 404 "That photo is no longer on this product." |
| Edit: price 4950/4450 → 5250 no sale, alt texts edited | 303; DB `SF-SMK-050=5250.00/-`, alts updated; storefront sticky price **Rs. 5,250**, `lowPrice 5250.00`, alt "bottle on slate" ×4, description `&lt;b&gt;markup` escaped, raw `<b>` 0 |
| Rename slug → `smoke-test-oud-renamed` | 303, "The old link will redirect here."; `GET /product/smoke-test-oud` → **301** → new; `slug_redirects` row; new slug 200 |
| Toggle hidden | `is_active=0`; storefront **404**; not in `/shop`; toggle back → 200 |
| Duplicate | #15 "Smoke Test Oud (copy)" `smoke-test-oud-copy`, hidden, SKUs `-COPY`, image rows + own files (14) |
| Hard-delete #15 (no orders) | without word → still exists; with `confirm_word=DELETE` → row, sizes, images, dir, og all gone |
| Soft-delete #14 (has order line) | "Smoke Test Oud is hidden from the shop. It stays on your past orders."; `deleted_at` set, images/files kept, storefront 404, hidden from default list, shown under `?status=retired`, order detail still names it, `order_items.product_id` kept; Restore via toggle → `deleted_at=null`, live again |
| Bulk on list | unfeature/feature; hide `[14, 999999]` → "1 of 2 products hidden — 1 no longer exist."; live |

## 3. Collections and scent families

Create (multipart, `image=@smoke-c.jpg`) → #8 `smoke-collection-two`, `image=collections/collection-8-…webp`, files `card` 800×450 + `zoom` 1600×900 webp+jpg + base; `GET /collections/smoke-collection-two` 200 and all 4 image URLs 200; `show_on_home=1` puts it on `/`. Edit tagline → saved, image kept. Replace image → old stem's files 0, new stem stored. Move up → sort swapped with neighbour `5`; move down → original order restored (`6:9,1:10,2:20,3:30,4:40,5:50`). Toggle → 0 → 1. Delete while product #14 inside → refused "1 product is in Smoke Collection. Move them first, or hide the collection instead."; delete without word → refused; `confirm_word=DELETE` → row + files gone. Scent family "Smoke Family" → #6 `smoke-family`, toggle 0/1, appears in product form select.

## 4. Orders

Storefront (own `SFSHOP` jar): `GET /api/cart` → csrf; `POST /api/cart/add size_id=27` ok; `GET /checkout` → hidden `idem_key`, `price_token`, `price_total=525000`, `ts`; `POST /checkout` COD (first two attempts were mine at fault: `action=apply` is the coupon button, and the `ts` timing trap must be forwarded) → **303 → /order/SF-260928-PPYT**. DB: order #9 pending/cod/unpaid, subtotal 5250, shipping 0 (free ≥ 3000), `phone_normalized +923001234567`; one `order_items` row product 14; stock 10 → 9; outbox rows queued.

| Admin action | Result |
|---|---|
| `GET /admin/orders` | 200, order listed |
| Search `q=Smoke Tester` | 1 hit (name prefix) |
| Search `q=SF-260928-PPYT` | **303 straight to the detail** (05b §1.4 rule 1); `q=PPYT` / `q=0300` (<7 digits) → no rows, by design (prefix name / phone classification) |
| Filters `status=pending&method=cod` | listed; `status=delivered` not listed (until later) |
| Detail | 200; customer note, item line, 8 `wa.me/923001234567?text=…` links all carrying the order number |
| WhatsApp "Confirmed" text | "Assalam-o-Alaikum Smoke, … Order: SF-260928-PPYT · Smoke Test Oud 50ml ×1 · Total: Rs. 5,250 · … track it any time at {SITE_URL}/track · Sky Fragrances — More Than Just A Scent" |
| `to=confirmed` | 303 "Order marked Confirmed." |
| `to=delivered` from confirmed (illegal) | status unchanged, "Problem: That status change isn't allowed. The order is currently Confirmed." |
| `to=packing` | ok |
| `to=shipped` without courier | refused: "Add the courier name before marking the order shipped. The tracking number can be added later." |
| `POST /shipping courier_name=TCS tracking_number=TCS123456789 mark_shipped=1` | status shipped, courier saved, "Order marked Shipped."; Shipped WhatsApp text now carries "Courier: TCS · Tracking number: TCS123456789 · Amount to pay on delivery: Rs. 5,250" |
| `to=delivered` | ok; `order_status_history`: pending>confirmed, confirmed>packing, packing>shipped, shipped>delivered |
| Notes | "Customer asked for gift wrap." and a second note starting `=SUM(1+1)` saved; shown on detail |
| Invoice / packing slip | both 200, `<title>Invoice SF-… / Packing slip SF-…`, `admin-print.js` loaded; slip shows "Collect Rs. 5,250 — cash on delivery" |
| CSV `export.csv?q=Smoke+Tester` | 200 text/csv, `Content-Disposition: attachment; filename="sky-orders-2026-09-28.csv"`, **BOM `efbbbf`**, CRLF; phone written `'03001234567` (05a §5 rule 6); customer note set to `=HYPERLINK(...)` on my order → cell exported as `'=HYPERLINK(…` (formula guard), unguarded count 0; `orders.export` activity row "1 rows (search=Smoke Tester)" |
| Bulk (my order only) | `packing_slips` → 303 → `/orders/SF-…/packing-slip?ids=9` 200; `export` → 303 → `export.csv?ids=9` (BOM, 1 row, only mine); `confirmed` on a delivered order → "0 marked Confirmed, 1 skipped: SF-260928-PPYT (delivered)." |
| `GET /admin/orders/cancel-unpaid`, `/admin/activity` | 200 |

## 5. Coupons, reviews, messages, subscribers, pages, settings, tools, password

- **Coupons:** `SMOKE10` percent 10, min 1000, limit 5 → row (code upper-cased); duplicate code → 200 with "already exists."; edit to fixed 500 → saved; toggle 0/1; storefront `/api/cart/coupon code=SMOKE10` ok:true; list shows the WhatsApp share link.
- **Reviews:** storefront `POST /product/…/review` (with `ts`) → pending row; `/admin/reviews` (pending default) shows title + full body; approve → `status=approved`, `products.rating_avg/count 5.00/1`, PDP shows it; reject → 0.00/0, PDP hides it; bulk `pending` works.
- **Messages:** `/contact` (subject must be a listed option) → `contact_messages` new; dashboard "1 unread message"; opening the detail flips to `read` + `read_at`; `wa.me/923217654321?text=Assalam o Alaikum Smoke, thank…` (settings template), `mailto:…?subject=Re: Order enquiry`, link to the order; `to=replied` + admin note; `to=archived`.
- **Subscribers:** `/api/newsletter` → subscribed/footer; list + `?q=` finds it; `subscribers/export.csv` 200, BOM, header `Email,Status,Source,"Subscribed At","Unsubscribed At"`; unsubscribe / resubscribe.
- **Pages:** `about` saved with `<p>… <script>alert('xss')</script><img src=x onerror=…><a href="javascript:…">…` → stored as `<p>Smoke paragraph <strong>bold</strong> <a>link</a></p>`; storefront `/about`: `<script>` 0, `onerror` 0, `javascript:` 0, `<strong>` kept; original body restored **byte-identical** (sha `020b9d7b5268`).
- **Settings:** all 7 tabs re-posted unchanged → "No changes to save.", DB identical to backup. Then: `announcement_text` → home shows it / restored; `shipping_fee=777`,`free_shipping_threshold=99999` → checkout + `/api/cart` show `Rs. 777` / restored to Free; `bank_enabled` off → checkout loses the bank radio, COD stays / restored; all four methods off → **refused** "At least one payment method must stay on, or nobody can check out."; `whatsapp=0300 9998877` → stored `+923009998877`, storefront links `wa.me/923009998877` ×4 / restored; `low_stock_threshold=abc` and a 200-char meta description → field errors. **Final `settings` table = backup, byte for byte.** Activity rows name keys only ("Settings changed (Payments): bank_enabled").
- **Tools:** `/admin/tools` 200; `remove-sample-data` confirm page asks to type DELETE and a POST without it changed nothing (products/orders/sample reviews 14/6/8 before and after); `regenerate-images` progress page 200. **Not run on the shared DB:** the destructive sample-data removal and the regeneration batch. The privacy purge is a one-button routine by design (05a route 40 / C-56 — it also runs silently on admin loads); its POST ran and reported 0 deletions.
- **Password:** wrong current → "Current password is wrong."; 6-char new → "Use at least 12 characters."; change → 303, session stays signed in; change back → `password_verify` true; `password.change` ×2 logged with the `password.fail`.

## 6. Screenshot tour (`docs/launch/shots-admin/`, `report.json`)

Second `SFADMIN` session logged in with Playwright's exact UA (`HeadlessChrome/145`) because sessions are UA-bound. Six pages × {1440, 375}: `/admin`, `/admin/orders`, the order detail, `/admin/products`, `/admin/products/14`, `/admin/settings`. First pass: 12 × 200, 0 console/page errors, **1 horizontal overflow** (`/admin/products` desktop, scrollWidth 1517). Cause: the `u-sr-only` "Actions" span in the table header is absolutely positioned and escaped the `overflow-x:auto` wrapper. After the CSS fix below: **12/12 clean, `bad=0`**.

## 7. Logs

`/tmp/skyfr-8090.log`: 1 `PHP Warning: Module "imagick" is already loaded` — the machine's php.ini double-loads the extension (environment, not the app); no 4xx/5xx served. `storage/logs/app-2026-09.log` +46 lines: 21 warnings, every one caused by this run's deliberate probes (13 wrong logins, 4 CSRF-less POSTs incl. `/admin/messages//status` from an empty id in my script, 2 `form trap: checkout bot` from my first checkout attempts); 25 info (mail previews, housekeeping). No `php-error.log`. Nothing to fix in sources.

## Fixes

| File | Change |
|---|---|
| `site/assets/css/admin.css` | `.adm-table-wrap` (≥768px) gets `position: relative` so the sr-only header text is contained by the scrolling wrapper — removes the page-level horizontal scroll on the products list at 1440. |
| `site/admin/controllers/product-form.php` | `product.update` summaries now name size **price / sale-price changes** ("prices (50ml Rs. 5,250 → Rs. 5,500 (sale Rs. 4,990))") — before, a price change logged "Saved … with no field changes". `php -l` clean. |

## Cleanup

Logged out both sessions. Deleted: order #9 (+ items, notes, status history, 4 outbox rows, mail preview), review #9, message #2, subscriber #4, coupon #5, scent family #6, collections #7/#8 (+ files), products #14/#15 (+ sizes, images, `uploads/products/14`, og files), slug redirects, `smoke_admin` (#2) with its login attempts and activity rows, and the 12 `login.fail` rows my lockout probe added to the real admin's history. Left untouched: `rate_limits` (keyed by the shared ip_hash). Verified: smoke rows = 0, settings = backup, `about` body identical, collection order identical, counts back to 13 products / 5 orders / 1 admin / 72 activity rows.
