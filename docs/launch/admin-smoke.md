# Admin smoke — can the owner run the shop from the panel?

Run: 2026-09-28 22:45–23:07 PKT · branch `main` · `php -S 127.0.0.1:8090 dev/router.php` · shared DB `skyfragrances_dev` · every action driven over HTTP with curl + cookie jars, CSRF token read from each form (`_csrf` field / `<meta name="csrf-token">`), iPhone Safari UA. Evidence lines below are copied from the run logs (`scratchpad/evidence.log`, `ev3/ev4/ev5a/ev5b/ev5c/ev6/ev-cleanup.log`). Product #18, order `SF-260928-PAQ6`, collection #10, scent family #8, coupon #7, review #11, message #4, subscriber #3 — all created and deleted by this run.

**Result: 86 checks, 0 failing. No source fixes were needed.** Three observations for other owners are listed at the end.

## 1. Login, lockout, dashboard

| Action | Result | Evidence |
|---|---|---|
| `GET /admin/login` | 200, "Admin sign in", 64-char CSRF token | `GET /admin/login -> 200 csrf len 64` |
| Wrong password ×5 | 200 each, "Wrong username or password.", 0.19–0.45 s | `wrong#1..5 -> 200` |
| Wrong ×6–9 (username ≥5 fails) | 200, same text, **2.2 s** progressive delay, no username lock (C-51) | `wrong#6 -> 200 2.203s` |
| Wrong ×10 (IP ≥10 fails) | **429 "Too many failed attempts. Try again in 15 minutes."**, username + password + button `disabled` | `wrong#10 -> 429 … disabled=3` |
| Correct password while locked | 429, same text — no validity leak | `locked: correct password -> 429` |
| Unlock | The other agent shares `127.0.0.1` (same `ip_hash`), so instead of waiting 15 min the 10 failed rows this probe wrote were deleted | `deleted my failed attempt rows -> 10` |
| Login `admin` / documented password | 303 → `/admin`, `SFADMIN` + `SFDEV` cookies | `login admin -> 303 …/admin ; cookies: SFADMIN SFDEV` |
| Dashboard | 200; "Revenue this month ▲ +Rs. 9,200 vs Rs. 0 last month" = `SUM(grand_total)` of accepted statuses (9200.00); Revenue today Rs. 0; Orders today 0; Pending 1 | `dashboard KPI … 9,200 ; DB accepted-status revenue: 9200.00` |
| Session UA binding | A session opened with the phone UA is bounced when reused with Playwright's UA, so the tour got its own login (§6) | design 05a §2.3 |

## 2. Product upload — the critical path

`GET /admin/products/new` 200 → `POST` with every field: name, slug blank (auto → `smoke-test-oud`), collection 1, unisex, "Amber & Spice", top/heart/base notes, longevity 4, sillage 3, Winter+Autumn, Evening+Gifting, description with `<b>markup</b>`, SEO title/description, featured+new+active, two sizes → **303 → /admin/products/18**, flash "Smoke Test Oud created. Add photos below…". DB: all fields as posted; size 1 `SF-SMK-050` 4950/sale 4450/stock 10 default; size 2 SKU blank → auto `SF-STO-100`, 7950, stock 3, alert 2.

Images — `POST /admin/products/18/images`, multipart `image=`, `X-Requested-With` + `X-CSRF-Token`, one file per request as `admin-catalogue.js` sends them:

| File | Result |
|---|---|
| `smoke-a.png` 1200×1500 | `{"ok":true,"id":49,…}` 200 |
| `smoke-b.png` 1200×1500 | ok id 50 |
| `smoke-c.jpg` 1600×2000 | ok id 51 (zoom capped to 1400×1750) |
| `evil.png` (SVG with `<script>` renamed) | **422 "Only JPG, PNG or WEBP images are accepted."** — nothing written |
| `big.jpg` 12.0 MB | **422 "The file is larger than 6 MB. Please upload a smaller image."** — nothing written (local `post_max_size` is 100M so the app rule fires; over `post_max_size` on Hostinger the 413 branch in `admin/index.php` answers) |

Rows: 3 × `product_images`, alt "Smoke Test Oud — 50ml and 100ml perfume by Sky Fragrances", width/height, sort 10/20/30, #49 primary. Files: 21 under `uploads/products/18/` (per stem `thumb` 200×250, `card` 600×750, `zoom` 1200×1500 as webp **and** jpg, plus the base `.webp`) + 3 × `uploads/og/product-18-*-og.jpg`. Storefront `GET /product/smoke-test-oud` 200; **18 distinct `src`/`srcset` URLs fetched, 0 non-200**; first image in DOM order = DB primary; alt rendered ×10; `<b>` escaped (`&lt;b&gt;markup` 1, raw 0); `lowPrice 4450.00`.

| Action | Result | Evidence |
|---|---|---|
| Reorder `ids[]=51,49,50` | `{"ok":true,"ids":[51,49,50]}`, DB `51:10:1,49:20:0,50:30:0` | ev |
| Set primary 50 | ids `[50,51,49]`, DB primary 50, PDP first stem `product-18-ae5b222a94` = #50 | ev |
| Delete 49 | `{"ok":true,"ids":[50,51],"deleted":49}`; **0 files left** for that stem (7 derivatives + og); rows 2; repeat → 404 "That photo is no longer on this product." | ev |
| Edit: 4950/4450 → 5250 no sale, alt texts edited | 303, "Smoke Test Oud saved. View on site: …"; DB `SF-SMK-050 5250.00/NULL`; alts updated; PDP `price_display "Rs. 5,250"`, `lowPrice 5250.00`, "bottle on slate" ×4; activity "Updated Smoke Test Oud: … prices (50ml Rs. 4,950 (sale Rs. 4,450) → Rs. 5,250)" | ev |
| Rename slug → `smoke-test-oud-renamed` | 303 "…The old link will redirect here."; `GET /product/smoke-test-oud` → **301** → new slug 200; `slug_redirects smoke-test-oud->18` | ev |
| Toggle hidden | `is_active=0`, storefront **404**, absent from `/shop`; toggle back → 200, on `/shop` | ev |
| Duplicate | #19 "Smoke Test Oud (copy)" `smoke-test-oud-copy`, hidden, SKUs `-COPY`, stock 0, 2 image rows with own files (14) | ev |
| Hard-delete #19 (no orders) | without word → still exists, "Type DELETE to confirm removing Smoke Test Oud (copy)."; with `confirm_word=DELETE` → rows 0/0/0, dir gone, og 0, "… was deleted along with its photos." | ev |
| Soft-delete #18 (has an order line, §4) | "Smoke Test Oud is hidden from the shop. It stays on your past orders."; `deleted_at` set, 2 image rows + 14 files kept, storefront 404, listed under `?status=retired`, order detail still names it, `order_items.product_id` = 18 | ev4 |
| Restore | toggle → `deleted_at NULL`, "restored. It is still hidden until you make it live" (is_active 0, storefront 404); second toggle → live, 200 | ev4/ev5a |
| Bulk on list | unfeature → 0, feature → 1; hide `[18, 999999]` → "1 of 2 products hidden — 1 no longer exist."; live → 1; `?q=Smoke` finds it | ev |

## 3. Collections and scent families

Create (multipart, `image=@coll.jpg` 1600×900) → 303, #10 `smoke-collection`, sort 60, `image=collections/collection-10-6ecf08b52a.webp`; files `card` 800×450 + `zoom` 1600×900 webp+jpg + base (5). `GET /collections/smoke-collection` 200, all 4 image URLs 200, `show_on_home=1` puts it on `/` (2 hits). Edit tagline → "Edited tagline", image kept. Replace image → old stem 0 files, new stem `collection-10-a14a028863` 5 files. Move up → `…4:40,10:50,5:60`; move down → original `…5:50,10:60`. Toggle → 0, storefront 404; toggle → 1. Delete while product #18 inside → refused "1 product is in Smoke Collection. Move them first, or hide the collection instead."; product moved back; delete without word → "Type DELETE to confirm removing Smoke Collection."; with DELETE → "Smoke Collection deleted.", row + 5 files gone, order back to `6:9,1:10,2:20,3:30,4:40,5:50`.

Scent family: `GET /admin/scent-families/new` 200; create "Smoke Family" → #8 `smoke-family`, intro saved; toggle → 0 → 1; appears in the product form select (1) and `/scent/smoke-family` 200.

## 4. Orders

Storefront (own `SFSHOP` jar): `GET /api/cart` → csrf; `POST /api/cart/add {"size_id":35,"qty":1}` ok; `GET /checkout` → hidden `idem_key`, `price_token`, `price_total=525000`, `ts`; radios `cod bank`; after the 3-second trap window `POST /checkout` COD with note `=HYPERLINK("http://x") gift wrap please` → **303 → /order/SF-260928-PAQ6?t=…** (that page 301s to the lower-case order path, then 200 — storefront canonical rule, see Observations). DB: order #17 pending/cod/unpaid, subtotal 5250, shipping 0 (free ≥ 3000), `+923001234567`; one `order_items` row (product 18, size 35, 5250); stock 10 → 9; 1 outbox row (mail preview written, transport unavailable locally).

| Admin action (second `SFADMIN` session) | Result |
|---|---|
| `GET /admin/orders` | 200, order listed |
| `?q=Smoke Tester` | 200, hit |
| `?q=SF-260928-PAQ6` | **303 straight to the detail** (05b §1.4) |
| `?status=pending&method=cod` | listed; `?status=delivered` not listed |
| Detail | 200; customer note shown; **8** `wa.me/923001234567?text=…` links, all 8 carry the order number |
| WhatsApp "Confirmed" text | "Assalam-o-Alaikum Smoke, · Thank you for your order with Sky Fragrances. · Order: SF-260928-PAQ6 · Smoke Test Oud 50ml ×1 · Total: Rs. 5,250 · Your order is confirmed and we are preparing it now. Delivery usually takes 2–4 working days. · You can track it any time at {SITE_URL}/track · Sky Fragrances — More Than Just A Scent" |
| `to=confirmed` | 303 "Order marked Confirmed." |
| `to=delivered` from confirmed | refused "Problem: That status change isn't allowed. The order is currently Confirmed."; status unchanged |
| `to=packing` | "Order marked Packing." |
| `to=shipped` without courier | refused "Add the courier name before marking the order shipped. The tracking number can be added later."; still packing |
| `POST …/shipping courier_name=TCS tracking_number=TCS123456789 mark_shipped=1` | "Order marked Shipped."; DB `shipped/TCS/TCS123456789`; Shipped WhatsApp text: "…has been shipped. · Courier: TCS · Tracking number: TCS123456789 · Amount to pay on delivery: Rs. 5,250 · Please keep your phone available…" |
| `to=delivered` | "Order marked Delivered."; history `pending>confirmed, confirmed>packing, packing>shipped, shipped>delivered` |
| Notes (`action=note body=…`) | "Note added.", shown on detail; a pinned note starting `=SUM(1+1)` saved with `is_pinned=1`; empty body → "Write a note of up to 1,000 characters." |
| Invoice / packing slip | both 200, `<title>Invoice SF-… / Packing slip SF-…`, `admin-print.js` loaded; slip shows "Collect Rs. 5,250 — cash on delivery" |
| `export.csv?q=Smoke+Tester` | 200 `text/csv; charset=UTF-8`, `attachment; filename="sky-orders-2026-09-28.csv"`, **BOM `efbbbf`**, CRLF, 1 data row; phone `'03001234567`; note exported as `'=HYPERLINK(""http://x"") gift wrap please` (formula guard); unguarded formula cells 0 |
| Bulk (my order only) | `packing_slips` → 303 → `…/packing-slip?ids=17` 200; `export` → `export.csv?ids=17` (BOM, only my row); `confirmed` on a delivered order → "0 marked Confirmed, 1 skipped: SF-260928-PAQ6 (delivered)." |
| `/admin/orders/cancel-unpaid`, `/admin/activity` | 200 / 200 |
| Activity | `order.status_change` ×4, `order.invoice_printed` ×3, `order.note_edited` ×2 |

## 5. Coupons, reviews, messages, subscribers, pages, settings, tools, password

- **Coupons:** `smoke10` percent 10 / min 1000 / limit 5 / per phone 1 → #7 stored upper-case `SMOKE10`; duplicate code → 200 "Code: That code already exists."; edit → `fixed/500.00`; toggle 0 → 1; list shows the WhatsApp share links; storefront `/api/cart/coupon SMOKE10` → `ok:true`, `discount 500.00`.
- **Reviews:** storefront `POST /product/…/review` (with `ts`) → 303, row #11 pending; `/admin/reviews` shows title + body; approve → `approved`, `products.rating_avg/count 5.00/1`, PDP shows it; reject → `0.00/0`, PDP hides it; bulk `pending` → pending.
- **Messages:** `/contact` POST → 303 `#sent`, row #4 `new`; dashboard "1 unread message"; `/admin/messages` lists it; opening the detail → `read` + `read_at`; `wa.me/923217654321?text=Assalam o Alaikum Smoke, thank you for contacting Sky Fragrances.` (settings template, `{name}` filled); link to the order (1); `to=replied` + admin note → `replied / Replied on WhatsApp.`; `to=archived`.
- **Subscribers:** `/api/newsletter` → "Thank you for subscribing."; list + `?q=smoke.sub` find it; `subscribers/export.csv` 200, BOM, header `Email,Status,Source,"Subscribed At","Unsubscribed At"`, row present; unsubscribe → resubscribe.
- **Pages:** `/admin/pages` 200; `about` re-posted with `<p>… <strong>bold</strong> <script>alert("xss")</script><img src=x onerror=…><a href="javascript:…">link</a></p>` → stored `<p>Smoke paragraph <strong>bold</strong> <a>link</a></p>`; storefront `/about`: `<script>` 0, `onerror` 0, `javascript:` 0, `<strong>` kept; original body restored **byte-identical** (sha `f7ec14b229e0`, 149 chars, before and after).
- **Settings:** all 7 tabs re-posted unchanged (form parsed from each GET) → "No changes to save." ×7, table identical. Then: `announcement_text` → home shows it / restored; `shipping_fee=777`, `free_shipping_threshold=99999` → `/api/cart` `"shipping":"777.00"`, checkout shows 777 / restored to 250 / 3000, cart back to "Free"; `bank_enabled=0` → checkout radios `cod` only / restored → `cod bank`; all four methods off → **200, refused** "At least one payment method must stay on, or nobody can check out." + "Nothing was saved. Fix the 1 highlighted field", DB unchanged; `whatsapp=0300 9998877` → stored `+923009998877`, storefront `wa.me/923009998877` ×3 / restored `+923001234567`; `low_stock_threshold=abc` → "must be a whole number between 0 and 999"; 200-char meta description → "Keep Site meta description to 155 characters", DB length unchanged (37). Activity rows name keys only ("Settings changed (Payments): bank_enabled"). **Final `settings` table = 23:00 backup** for every key (the one em-dash difference is explained under Observations).
- **Tools:** `/admin/tools` 200; `remove-sample-data` confirm page asks to type DELETE; POST with the wrong word → "Type DELETE in the box to remove the sample data.", counts 14/6/9 before and after; `purge` confirm 200, POST → "purge finished."; `regenerate-images` progress page 200. **Not run on the shared DB:** the destructive sample-data removal and the regeneration batch.
- **Password:** wrong current → "Current password is wrong."; 6-char new → "Use at least 12 characters."; change → 303, session stays signed in, new hash verifies; change back → original verifies; log `password.fail, password.change, password.change`.

## 6. Screenshot tour (`docs/launch/shots-admin/`, `report.json`)

Own `SFADMIN` session logged in with Playwright's exact UA (`HeadlessChrome/145.0.7632.6`, identical for both viewports) because sessions are UA-bound. Six pages × {1440, 375}: `/admin`, `/admin/orders`, `/admin/orders/SF-260928-PAQ6`, `/admin/products`, `/admin/products/18`, `/admin/settings` → **12 × 200, 0 console/page errors, 0 horizontal overflow, `bad=0`**. Stale shots from an aborted earlier attempt (`…KZ28…`, `…products_16…`) were removed so the folder matches `report.json`.

## 7. Logs

`/tmp/skyfr-8090.log` (1,155 lines): 1 `PHP Warning: Module "imagick" is already loaded` at server start — this machine's php.ini double-loads the extension (environment, not the app); no 4xx/5xx or PHP notices from requests. `storage/logs/app-2026-09.log` +32 lines: 10 `warning admin login failed` (my lockout probe), 21 `info housekeeping ran`, 1 `info mail: transport unavailable, preview written` (my order). No `php-error.log`. Nothing to fix in sources.

## Fixes

None. No file under `admin/**`, `app/lib/{images,upload,auth,housekeeping}.php`, `assets/css/admin.css`, `assets/js/admin*.js` was changed by this run (the pre-existing working-tree changes to `site/app/lib/text.php`, `site/install.php`, `site/dev/zip-manifest.php` belong to another agent).

## Observations for other owners (not admin defects)

1. **Storefront order page canonical redirect:** `GET /order/SF-260928-PAQ6?t=…` (the checkout redirect target) answers 301 → `/order/sf-260928-paq6?t=…` → 200. It works, but the confirmation page costs the customer an extra hop; the storefront could emit the lower-case path from checkout, or exempt `/order/{number}` from the lower-casing rule. Owner: storefront.
2. **Dev-DB text through the mysql CLI:** the `mysql` client on this machine defaults to `latin1`. At 23:00 the `announcement_text` value in the dev DB was the mojibake `â€”` (written by another agent's restore between 22:44 and 23:00); my first restore wrote back what the latin1 client showed me, so I re-saved the value from the admin form and it is now the real em dash (`E2 80 94`, storefront renders "—"). Any script writing text to the dev DB should pass `--default-character-set=utf8mb4`. Owner: whoever drives the DB from shell.
3. **Shared-IP side effects:** the lockout probe locks `127.0.0.1` for everyone for up to 15 minutes; I deleted my 10 failed rows immediately. The review and newsletter rate-limit rows are keyed by the same `ip_hash` and were left in place (deleting them would also clear the other agent's counts); `admin_users.known_devices` keeps up to 5 device hashes, four of which are now this run's sessions.

## Cleanup

Logged out all four admin sessions. Deleted: order #17 (+ items, notes, status history, outbox row, mail preview), review #11, message #4, subscriber #3, coupon #7, scent family #8, collection #10 (+ files), products #18/#19 (+ sizes, images, `uploads/products/18`, `uploads/products/19`, og files), slug redirects, 92 activity rows (51 on my entities + 41 login/logout/password/settings/tools/export rows from my UAs), my 4 successful login-attempt rows and the 10 failed ones. Verified: smoke rows anywhere = 0, settings = backup, `about` body sha unchanged, collection order `6:9,1:10,2:20,3:30,4:40,5:50`, admin password verifies, counts back to 13 products / 5 orders / 6 collections / 4 coupons / 8 reviews / 1 admin; the only activity rows added since 22:45 are two `login.ok` from the other agent's sessions. Server on 8090 stopped.
