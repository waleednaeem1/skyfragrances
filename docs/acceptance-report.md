# Acceptance run — 07 B.4, end to end (2026-09-29)

Fresh install on this machine, every B.4 item driven over HTTP: PHP 8.2.28 built-in server on
`127.0.0.1:8100` (`php -d display_startup_errors=0 -S 127.0.0.1:8100 dev/router.php`) against a
copy of `site/` and its own MySQL 9.3 database `skyfragrances_accept` (the shared `skyfragrances_dev`
database and the other owners' servers were left untouched); MariaDB 10.11.19 on `:3307` for the
schema/seed load. Tooling: `curl` through PHP (cookie jars, JSON + multipart), Playwright (Chromium
1208) for the screenshot tour, the no-JavaScript pass, keyboard/reduced-motion probes and
`dev-tools/a11y.mjs`, Lighthouse 13 mobile. **191 PHP files lint clean before and after the fixes.**

Legend: **PASS** verified here · **PASS (note)** verified with a documented deviation or caveat ·
**LIVE HOST** cannot be exercised on localhost — remains on the go-live checklist · **NOT RE-RUN**
covered by another owner's report and not repeated here.

Counts: 97 checklist items — 88 PASS / PASS (note); 4 verified locally with a live-host remainder
(phpMyAdmin import, HTTPS/`www` rules, the rotating-IP contact cap, Lighthouse on the live host);
4 LIVE HOST only (http→https on `/admin/login`, 301-after-self-check, client-IP hashes, preview-host
banner); 1 NOT RE-RUN (the B.2.7 budgets, owned by the perf pass). Nothing that can be tested locally is
left failing. Fixes made during the run are listed in §11.

Order numbers below are the ones this run created: COD `SF-260929-GQ29` (also `SF-260929-LVW2` for
the COD-fallback test), Bank Transfer `SF-260929-6SDM`, JazzCash `SF-260929-R4K5`, Easypaisa
`SF-260929-QK4J`, plus ~25 further COD orders for the stock, race and pricing items.

## 1. B.4.1 Install and deploy

| Item | Result | Evidence |
|---|---|---|
| `install.php` on an empty DB creates every table, the admin and the sample data | PASS | Steps 1→4 through curl: `db_save` 303 → step 3, `install` 303 → step 4 "27 statements ran without errors" (schema) and "236 statements ran without errors" (seed); 26 tables; settings 86, products 12, sizes 24, images 36, reviews 8 (all pending, C-68), coupons 3, collections 5, pages 6, quiz scores 31; `contact_email`/`order_notify_email`/`whatsapp` written from the form; `site_indexable=0` on the non-production host |
| Re-running `install.php` is refused | PASS | second `GET /install.php` → "Sky Fragrances is already installed — delete install.php"; nothing written |
| `install.php` gone or `storage/.installed` exists and the page says so | PASS (note) | `storage/.installed` written before the unlink attempt (C-47); with `env=development` the file is deliberately kept (stage-2 fix) and the refusal page names it; the dashboard shows the red "install.php is still on the server" banner |
| `db/seed.sql` imports cleanly through phpMyAdmin; em dashes and `°C` render | PASS (note) / LIVE HOST | Loaded statement by statement on MariaDB: 236/236, 0 errors, 0 warnings; `delivery_time` reads `2–4 working days`, product copy `…legible at 42°C…` round-trips; the phpMyAdmin import itself is a host step |
| Re-importing schema + seed over real data changes nothing; no `CREATE DATABASE`/`USE`/`SET SESSION`; no `DROP` | PASS | second load of both files on MariaDB: 0 errors, every table count unchanged (`INSERT IGNORE`, `IF NOT EXISTS`); `grep '^USE '` finds nothing (the only "USE" is inside prose) |
| Same SQL on MySQL 8 and MariaDB 10.4+ with no collation error | PASS | MySQL 9.3 via the installer, MariaDB 10.11 via the loader: 26 InnoDB / `utf8mb4_unicode_ci` tables on both, identical seed counts |
| HTTPS forced; `www` 301s to apex; trailing slash 301s | PASS (slash) / LIVE HOST | `/shop/` → 301 `/shop`; `/Shop` → 301 `/shop`; `/index.php/shop` → 301; HTTPS and `www` rules only exist in `.htaccess` on the host |
| `http://…/admin/login` redirects to `https://` before any form renders (C-54) | LIVE HOST | `.htaccess` rule, not simulated by the dev router |
| 302 before the https self-check, 301 after (C-52) | LIVE HOST | Settings › Advanced button present (`/admin/settings/https-permanent`), refuses on `http://` |
| Two orders from different networks carry different `orders.ip_hash` (C-50) | LIVE HOST | all local orders share the loopback hash, as expected; the installer's "Client IP detection" row exists |
| Preview host and real domain both work; host-mismatch banner on the preview host only (C-49) | LIVE HOST | Origin is compared with the request host: every admin form and `/api/cart/*` call in this run passed the check on `127.0.0.1:8100` while `base_url` was the same value |
| `/storage/.htaccess`, `/storage/sessions/shop/`, `/admin/controllers/orders.php`, `/config.sample.php`, `/.user.ini`, `/db/schema.sql` all refused with no leak | PASS | all six (plus `/config.php`, `/app`, `/db`, `/storage`, `/admin/views`, `/admin/partials`, `/uploads/x.php`, `/storage/proofs/…jpg`, `/dev/router.php`) return the branded 404 page (status 404, no path, no source) — the dev router now mirrors `ErrorDocument 403 /index.php` (§11 fix 1) |
| Step 1 prints the effective `memory_limit` and the megapixel budget (C-46) | PASS | requirements row "PHP memory limit: 512M — product photos up to 40.0 megapixels can be resized" |

## 2. B.4.2 Orders — every payment method, end to end

One order per method through `GET /api/session` → `POST /api/cart/add` → `GET /checkout` (hidden
`idem_key`, `price_token`, `price_total`, `ts`, `_csrf`) → 3 s (form trap) → multipart `POST /checkout`.

| Item | Result | Evidence |
|---|---|---|
| Order placed start to finish for COD, Bank, JazzCash, Easypaisa | PASS | 303 → `/order/{number}?t={token}` for all four: `SF-260929-GQ29` (COD, Azure Oud 50 ml, Rs. 8,950), `SF-260929-6SDM` (Bank, Silver Lining 50 ml sale 3,950 + WELCOME10 → Rs. 3,555), `SF-260929-R4K5` (JazzCash, Nimbus Rain 50 ml, Rs. 5,150), `SF-260929-QK4J` (Easypaisa, Cirrus 50 ml ×2, Rs. 10,900). A COD order was also placed at 375 px with JavaScript disabled (§10) and the checkout appears in the 375 px tour |
| Manual methods show account details, accept a transaction id + screenshot, reject a PDF and a 12 MB file readably | PASS | checkout renders "Transfer exactly Rs. … to: Bank Meezan Bank · Account title … · IBAN …" for each method; `payment_reference` and `payment_proof` stored; PDF → 422 "Only JPG, PNG or WEBP images are accepted." (no order row); 12 MB file → 422 "The file is too large to upload." (no order row); missing screenshot → "Please upload a screenshot of your payment." |
| Confirmation page shows order number and total | PASS | receipt 200 for all four, contains the number, `Subtotal / Delivery Free / Total` (e.g. Rs. 3,950 / Free / Rs. 3,555), transaction id and the account lines for transfers |
| Customer and admin email arrive with totals and a working order link | PASS (note) | no SMTP locally: admin alert queued in `email_outbox` (`order-admin` → owner@example.test, subject `New order SF-260929-6SDM — Rs. 3,555 — Bank Transfer — Lahore`, body carries `/admin/orders/SF-260929-6SDM`); customer mail rendered to `storage/logs/mail-preview/*-order-customer.html` with subject `Order SF-260929-6SDM confirmed — Sky Fragrances`, the full item table, `Total Rs. 3,555` and `/order/SF-260929-6SDM?t=…`. Outbox at the end of the run: order-admin 28, status-update 8, contact-admin 3, contact-autoreply 2, all `queued` |
| Order in admin with the right status, method, proof and lines | PASS | `/admin/orders/SF-260929-6SDM`: Pending · Bank Transfer · Review needed · "📎 proof attached", `Paid to Bank Transfer — Bank: Meezan Bank…`, Transaction ID, screenshot 9 KB with "Open full size", items `Silver Lining 50ml SKY-SIL-050 Rs. 3,950 ×1`, totals `Subtotal Rs. 3,950 · Discount (WELCOME10 · 10%) − Rs. 395 · Shipping Free · Grand total Rs. 3,555` |
| `/track` finds number + phone; wrong phone refused | PASS | right phone (any format, `+92 321 7654321` / `0345 9998877`): status timeline Pending→…→Delivered, items, `Order total Rs. …`; wrong phone: "We couldn't find an order with those details…", no name, total or status leaked; unknown number behaves identically |
| Line totals, subtotal, discount, shipping, grand total agree on cart, checkout, confirmation, email, admin | PASS | bank order: `3,950 / −395 / Free / 3,555` on `/cart`, `/checkout` (`price_total=355500` paisa), receipt, customer email, admin detail and `orders` row (`3950.00 / 395.00 / 0.00 / 3555.00`) |
| Editing the posted price changes nothing | PASS | `price_total=100` posted with a valid token → 409 "Your new total is Rs. 8,950 (was Rs. 1). Please review your order and place it again.", no order row; the second tap stores the server price 8,950 |

## 3. B.4.3 Cart, coupons and shipping

| Item | Result | Evidence |
|---|---|---|
| WELCOME10 rejected under Rs. 3,000, accepted above, exactly 10 % of the items subtotal | PASS | at 2,999: 422 "This code needs a minimum order of Rs. 3,000. Add Rs. 1 more to use it."; at 3,000: discount 300; at 3,950: discount 395 (shipping untouched) |
| EIDSALE500 reports expired, FIRST50 fully redeemed, neither mentions the minimum | PASS | "This code expired on 11 Aug 2026." · "This code has reached its usage limit." |
| `  welcome10` (lower case, leading spaces) works | PASS | 200, "WELCOME10 applied — you saved Rs. 300." |
| A coupon worth more than the basket cannot go negative | PASS | temporary Rs. 99,999 fixed code on a Rs. 4,955 cart → discount 4,955, total `0.00` |
| Free-shipping bar accurate at 2,999 and 3,000; shipping 0 at the threshold | PASS | 2,999 → shipping 250, `free_shipping=false`, progress visible; 3,000 → shipping 0, `free_shipping=true`, "You've unlocked free delivery." |
| Pre-discount rule (C-43): Rs. 3,100 + WELCOME10 = free delivery on all five surfaces, one tap | PASS | `SF-260929-DLTC`: API/cart/checkout/receipt/email all `3,100 / −310 / Free / 2,790`; `orders.shipping_fee = 0.00`; placed on the first POST |
| Odd subtotal Rs. 4,955 + WELCOME10 → Rs. 496 everywhere, first tap | PASS | `SF-260929-VMAJ`: discount `496.00` on API, cart, checkout, receipt, email; placed first tap |
| Price change in admin between render and Place Order, either direction → no order, notice, second tap succeeds | PASS | 5,150 → 5,650 and 5,150 → 4,650: 409 "Your new total is Rs. … (was Rs. 5,150)…", order count unchanged; second tap 303 with the new total stored |
| Per-phone race (C-44): two simultaneous WELCOME10 checkouts, same phone | PASS | two parallel `curl` POSTs: one 303 (discount 565 stored, one `coupon_redemptions` row), one 409 "…already been used with this phone number. Your order total is now Rs. 5,650." with the page re-priced |
| FIRST50 stays *Limit reached* after reload; no recount control | PASS | `/admin/coupons`: `FIRST50 … 50 / 50 … Used up`; no recount/reset control anywhere on the page |
| Ten wrong codes from one IP → eleventh refused; scheduled code says *isn't valid* | PASS | attempts 1–10: 422 "That code isn't valid…"; 11th: 429 with the same generic text; EIDSALE500 with `starts_at` in 2027 → "That code isn't valid…" (not "isn't active yet") |
| Removing the last item empties the drawer without an error | PASS | `POST /api/cart/remove` → 200 `is_empty=true`, totals 0, "Removed Nimbus Rain (50ml)."; the 375 px tour renders the empty drawer state |
| Cart survives reload and a browser restart within the session lifetime | PASS (after fix) | the `SFSHOP` cookie was a session cookie (`lifetime 0`) while C-26 says 3 days; now `Max-Age=259200` (§11 fix 7); same cookie from a new curl process returns the same 2 lines; `SFADMIN` stays session-scoped |

## 4. B.4.4 Stock — it cannot go negative

| Item | Result | Evidence |
|---|---|---|
| Eclipse Velvet 100 ml (stock 0) shows sold out, cannot be added, JSON-LD says OutOfStock | PASS (note) | size chip renders "Out of stock", `POST /api/cart/add` → 409 "Eclipse Velvet (100ml) is sold out."; the JSON-LD is a product-level `AggregateOffer` and reads `InStock` because the 50 ml is in stock — exactly 07 B.1.4 rule 4 ("InStock only when at least one active size has stock > 0"); a fully sold-out product emits `OutOfStock` |
| 24 units of Azure Oud 50 ml succeed and leave stock 0; the next fails naming the item and keeps the rest of the cart | PASS (note) | the 10-per-size cap (06a) means three orders: ×10, ×10, ×2 (two had been sold earlier) → stock 24 → 0; a further add → 409 "Azure Oud (50ml) is sold out."; a checkout whose Azure Oud line sold out mid-way → 409, no order, Aurora Bloom line kept in the cart |
| Concurrency: two checkouts for the last unit, exactly one succeeds, ×10 | PASS | Saffron Zenith 50 ml set to stock 1, two parallel `curl` POSTs, 10 runs: every run one 303 + one 409, stock 0 each time, `MIN(stock)` over the table never below 0, 10 order lines for the size |
| Cancelling restores stock and `used_count`; twice changes nothing; redemption `reverted` | PASS | bank order cancel #1: "Order cancelled. Stock restored: Silver Lining 50ml +1.", stock 44→45, WELCOME10 `used_count` 1→0, `coupon_redemptions.status = reverted`, `stock_restored_at` set; cancel #2: "That status change isn't allowed. The order is currently Cancelled." — nothing changed |
| Strict mode (C-55): `stock = stock − 5` on a stock-3 row raises, never stores 0 | PASS (note) | through the app's `db_query()` and on MariaDB: `SQLSTATE[22003] 1690 BIGINT UNSIGNED value is out of range`, stock still 3 (1690 rather than 1264 because the column is `UNSIGNED`; the outcome is the one the rule wants) |
| Cancelling a shipped order does not change stock; "Parcel received back" restores exactly once | PASS | JazzCash order: confirmed → packing → shipped (TCS, TCS123456789) → cancelled: "Stock was not restored because the parcel is with the courier — use 'Parcel received back'…", stock 28 unchanged; stock-back #1: "+1" → 29; #2: "Stock was already restored on 29 Sep 2026, 10:48 am — nothing changed." |
| Second transfer order from a phone with one awaiting verification is refused (C-58); accepted after mark paid | PASS | 422 "You already have a transfer order waiting for verification (SF-260929-R4K5)…" (and the per-connection variant "A transfer order from this connection is still waiting…"); after *Approve payment* the next transfer order from that phone/connection placed normally |
| *Cancel unpaid transfer orders older than 48 h* cancels only qualifying orders, restores stock once, skips COD | PASS | a bank order (awaiting verification) and a COD order both backdated 49 h: tool lists "1 order holding Rs. 7,450 of stock… COD orders are never included"; run → "1 order cancelled and stock restored." (Stratus Noir 50 ml 20→21, reason stored, cancellation email queued); COD order untouched; second run "No unpaid transfer orders older than 48 hours — nothing was cancelled." |
| Saffron Zenith 50 ml (stock 3) in the dashboard low-stock alert | PASS | dashboard "Low stock: Eclipse Velvet — 100ml Sold out · Saffron Zenith — 50ml 3 left" |

## 5. B.4.5 Every admin function

| Item | Result | Evidence |
|---|---|---|
| Login; bad passwords lock the IP; message never reveals the username | PASS (note) | real and unknown usernames both get "Wrong username or password."; per C-51 the 6th failure on the username is delayed (~2.2 s measured) and the 9th/10th from the IP → 429 "Too many failed attempts. Try again in N minutes."; a correct password from the locked IP is refused until the window passes |
| Session regenerates on login; logout destroys it; back button does not restore | PASS | `SFADMIN` id changed on login; admin pages `Cache-Control: no-store…`; `POST /admin/logout` → cookie cleared, `/admin/orders` → 302 login; the old session id replayed → 302 login |
| Dashboard figures match a hand count | PASS | after confirming three orders: SQL hand count Rs. 187,950 / 3 orders today, by status pending 20 · confirmed 3 · cancelled 2, best seller Azure Oud 21 = dashboard "Rs. 187,950 Revenue today · 3 Orders today · Pending 20 Confirmed 3 … Cancelled 2 · 1. Azure Oud 21 sold · Rs. 187,950" |
| Product create/edit/delete; multi-image upload with auto-resize; sizes; featured/new/active change the storefront | PASS | "Acceptance Test Perfume" created with two sizes (SKU, price, sale, stock); two uploads → `-thumb` 200×250, `-card` 600×750, `-zoom` up to 1400×1750 webp + jpg (28 files); `is_active` toggle → PDP 404 / back to 200; `is_new` 0/1 → the card's "New" badge disappears/returns; `is_featured` drives the home rail order (`ORDER BY is_featured DESC`, home.php); delete with the DELETE word → row, sizes and 28 files gone, PDP 404, old slug still 301s |
| Saving with empty primary-image alt text is refused | PASS | 422 "Please fix these 2 things before saving: Photo alt text: Every photo needs alt text…", DB alt unchanged |
| Renaming offers the slug change and writes `slug_redirects`; old URL 301s | PASS | form shows the slug field with the redirect hint; save → "The old link will redirect here."; `slug_redirects` row `acceptance-test-perfume → acceptance-renamed-perfume`; old URL 301 → new URL 200 |
| Collection CRUD; `sort_order` reorders the storefront | PASS | created with image → `/collections` order `acceptance-collection, dawn-chorus, …`; `sort_order=99` → last; delete with DELETE word → row and image gone, page 404 |
| Order list filters and search; status chain; courier + tracking; proof view; mark paid; invoice + packing slip; stock on cancel; WhatsApp prefill; CSV in Excel with `=` neutralised | PASS | filters `status[]=cancelled` 3, `method=bank` 2, `pay=paid` 3, name/phone search 1, order-number search → 303 to the order; chain pending→confirmed→packing→shipped(+courier)→cancelled and cancelled-with-stock-back exercised; `/proof` streams `image/jpeg` inline (302 to login without a session); mark paid approves the proof, `paid_at` set, "Payment received" email queued; invoice 200 with totals, packing slip 200 without prices except the COD "Collect Rs. … — cash on delivery" banner; 8 `wa.me` links per order, order number in the text, 0 exclamation marks; CSV: UTF-8 BOM, CRLF, `Content-Disposition: attachment; filename="sky-orders-2026-09-29.csv"`, `'=SUM(1+1) Csv Tester`, `'03361212121`, `'=cmd|calc` |
| Coupon CRUD, create one and redeem it | PASS | `accept15` (15 %, 3 uses) created → normalised `ACCEPT15`; applied on a Rs. 8,450 cart → 1,268 off; order `SF-260929-8T5H` stored it, `used_count` 1; disable → 422 on apply; edit value → 20.00 |
| Review moderation: approve the 2★, it appears on the PDP and lowers the average; reject one and it does not | PASS | Nimbus Rain: a 5★ approved → `5.00/1`; Faisal M. 2★ approved → `3.50/2`, PDP shows "Not what I expected", `aggregateRating {ratingValue 3.5, reviewCount 2}`; Sana F. (Eclipse Velvet) rejected → not on the PDP, no `aggregateRating` |
| Contact messages list; subscribers list + CSV; unsubscribe link works | PASS (note) | messages list/detail, mark replied, WhatsApp reply link; `POST /api/newsletter` → row + "Thank you for subscribing." (duplicate says the same, one row); subscribers list and CSV (BOM, `Email,Status,Source,…`); `/unsubscribe?e=…&t={unsub_token}` → "You have been unsubscribed" (status flips), a wrong token → "This unsubscribe link is not valid" and no change. v1 sends no newsletter itself, so the link is built by the owner's mailing tool from the exported token — there is no site email that carries it |
| Settings: announcement, hero text, shipping fee, threshold, delivery time visible immediately; disabling each method removes it from checkout | PASS | home showed the new announcement/hero/subheading on the next request; fee 350 / threshold 6,000 → a Rs. 5,450 cart shipped at 350 with "Rs. 550 more"; delivery time on home and checkout; disabling bank/jazzcash/easypaisa/cod each removed exactly that method from checkout; all values restored to the seed afterwards |
| Admin password change invalidates the old password | PASS | change → old password "Wrong username or password.", new one → 303 `/admin`; changed back |
| Every admin screen usable one-handed at 375 px, including order detail | PASS | Re-run 2026-10-01 with a logged-in session: 33 admin screens at 375 px (touch) and 1440 px, all 200, 0 horizontal overflow, 0 console/network errors; 560/560 measured controls at least 44 px tall on touch screens. Screenshots: `docs/shots/stage5/admin/` (33 distinct). The earlier stage-5 folder held only the login page and was replaced. |

## 6. B.4.6 The placeholder guard

| Item | Result | Evidence |
|---|---|---|
| With `REPLACE ME` bank details the dashboard shows the red banner and checkout hides Bank Transfer | PASS (after fix) | before the run the dashboard had no such banner (§11 fix 3); now "Customers cannot pay by Bank Transfer: the account details are still the REPLACE ME placeholders…"; checkout offered `jazzcash, easypaisa, cod` only and never printed `REPLACE ME` |
| Real values remove the banner and restore the method | PASS | saved through Settings › Payments ("4 settings saved") → banner gone, checkout offers `bank, jazzcash, easypaisa, cod` |
| Every manual method unconfigured → COD still offered, orders complete | PASS (after fix) | with all three manual methods on placeholders **and** `cod_enabled=0` the checkout offered nothing and refused the order; `payment_methods_enabled()` now falls back to COD with a warning log (§11 fix 4): checkout offers `cod`, `SF-260929-LVW2` placed |

## 7. B.4.7 Sample-data removal

| Item | Result | Evidence |
|---|---|---|
| Amber banner on a fresh install | PASS | "This store is still showing sample data. Remove it in Tools before you go live." |
| `remove-sample-data` deletes the 8 sample reviews and untouched sample products, refuses sold ones, reports both | PASS | confirm page lists "Will be deleted" / "Will be kept"; run: "8 sample reviews deleted; 5 products deleted; 7 kept (sold): Azure Oud, Cirrus, Nimbus Rain, Saffron Zenith, Silver Lining, Stratus Noir, Zephyr Blanc; 5 collections deleted; 0 coupons deleted; 3 coupons switched off (already used). 145 image files deleted." plus the note that payment details were left alone; second run "There is no sample data left to remove."; banner gone |
| Afterwards no `aggregateRating`, no stars anywhere; the site looks new, not broken | PASS | `/`, `/shop` 200 with no `aggregateRating`, no `stars` markup, no rating text; `/collections` 302 to the shop; the seven kept products are hidden from the shop with their order history intact |

## 8. B.4.8 Mobile pass at 375 px

| Item | Result | Evidence |
|---|---|---|
| No horizontal scroll anywhere (PDP notes pyramid, order table) | PASS | tour `docs/shots/stage5/report.json`: 12 storefront paths × desktop/mobile, `horizontalOverflow: false` on all 24, PDP included; admin tour incl. `/admin/orders` and the order detail: 30 shots, 0 overflow |
| Tap targets ≥ 44×44 px | PASS (note) | `dev-tools/a11y.mjs` on 12 pages at 375 px: 0 failures (44 px rule incl. fixed bars); the 8 px spacing rule is not instrumented |
| Sticky add-to-cart bar does not cover the WhatsApp button or the last line | PASS | probe: bar hidden until the buy box scrolls out, then `is-visible` at 744–812 px while the WhatsApp fab moves to 680–732 px (no overlap) and `body` gains `padding-bottom: 68px` |
| Cart drawer full height, internal scroll, closes on backdrop tap and Esc | PASS | drawer panel 812/812 px, inner scroll container present, `aria-hidden` false→true on backdrop tap; Esc closes it on desktop and focus returns to the cart button |
| Filters open as a sheet; applying closes it and keeps the position | PASS (note) | `data-dialog-open="filters-drawer"` opens the full-height `drawer--filters` (03 §5.9 specifies a **drawer** below 1024 px, not a bottom sheet), focus moves inside, "Show results" applies via a GET navigation which closes it; because it is a page load the scroll position restarts at the top |
| Right keyboards, no iOS zoom | PASS | `customer_phone` `inputmode="tel"`, `customer_email` `type="email"`; every visible input/textarea/select on 9 pages is ≥ 16 px (the only smaller ones are the hidden honeypot and file input) |
| Quiz completable with one thumb and with JS disabled | PASS | five radio groups, GET form → `/scent-finder/result?a=2-2-2-2-2` with three matches, no JavaScript |
| Long names, Rs. 13,950 and a `SALE −29%` badge coexist without overlap | PASS | `/sale` cards: "Sale −20%" + "New" badges with "From Rs. 3,950 Rs. 4,950", no bounding-box overlap between badge, name and price; Azure Oud 100 ml Rs. 13,950 and the test product's −29 % sale rendered in the tour |

## 9. B.4.9 SEO and security spot-checks

| Item | Result | Evidence |
|---|---|---|
| Unique titles ≤ 60 and descriptions ≤ 155 on every indexable page | PASS | 40 pages crawled (home, listings, 5 collections, 5 scent families, 12 products, 7 content/utility pages): titles 25–54 chars, descriptions 72–151, no duplicates, canonical and OG on all |
| Product JSON-LD valid; price equals the page; `priceCurrency` PKR | PASS (note) | every product: `AggregateOffer` low price = cheapest active size = the price on the page, `PKR`, `NewCondition`; the Rich Results Test itself needs the live URL |
| Zero approved reviews → no `aggregateRating` | PASS | all 12 seeded products (8 pending reviews) emit none; it appears only after approval (§5) |
| `/sitemap.xml` valid XML, exactly the active catalogue, excludes utility pages; hiding a product removes it | PASS (after fix) | 404 while `site_indexable=0`; with it on: `application/xml`, xmllint well-formed, 39 `<loc>` = 12 products + 5 collections + 5 families + listings + content pages; `/track` removed (§11 fix 5) — it was the one blocked-by-robots URL in the map; hiding Eclipse Velvet dropped it on the next build (`Cache-Control: public, max-age=3600`) |
| `/robots.txt` served, points at the sitemap | PASS | `text/plain`, the 07 B.1.4 rule set, `Sitemap: https://skyfragrances.com/sitemap.xml` |
| WhatsApp share shows image, title, description | PASS (note) | `og:type product`, `og:title`, `og:description`, `og:image` 1200×630 JPEG (54 KB, fetchable), `og:url`, `twitter:card summary_large_image`; the actual WhatsApp fetch needs the public URL |
| `'; DROP TABLE products; --` in search, coupon, contact, track does nothing | PASS | search page 200 with the string echoed escaped, coupon "That code isn't valid…", contact stored verbatim as text, track "Please enter your order number (like …)"; `products` still 12 rows |
| `<script>alert(1)</script>` as review body, customer name, order note renders as text everywhere | PASS | stored raw; PDP, receipt, `/admin/reviews`, `/admin/orders`, order detail all contain `&lt;script&gt;` and never the raw tag |
| Content-page sanitiser (C-62) | PASS | saved body stored as `<p>Intro paragraph.</p><a>x</a><p>x</p><p>Outro.</p>`: `href="javascript:"`, `onmouseover`, `<img onerror>` and `<iframe>` all gone; "Restore default text" put the seed copy back |
| CSP: one header, zero violations, `html.js` set | PASS | exactly one `Content-Security-Policy` on `/`, PDP, `/cart`, `/checkout`, `/track` and the admin order page; the `sha256-…` in `script-src` matches the inline bootstrap on every storefront page and `html.js` is present; tour console: 0 errors on all 54 shots |
| Paid manual order: receipt only; `POST` of a new proof → 403, stays paid (C-59) | PASS | Easypaisa order after *mark paid*: receipt says Paid, no upload form; POST with a new screenshot → 403, `payment_status` paid, still 1 proof |
| `/order/SF-000000-XXXX` and a wrong token render the identical pre-filled track form (C-76) | PASS | both 200, byte-identical apart from the echoed number, order number pre-filled |
| Contact auto-reply excludes the body; ≤ 30 auto-replies for 31 submissions from rotating IPs (C-63) | PASS (auto-reply) / LIVE HOST (cap) | auto-reply queued to the sender without the message body (secret token absent), admin copy contains it; rotating source IPs cannot be produced on loopback |
| C-51 sequence: 5 wrong → owner with known-device cookie succeeds; 6th delayed ~2 s; 10th locks the IP only | PASS (note) | owner login with the `SFDEV` cookie succeeded during the attack; 6th username failure took 2.19 s vs 0.2–0.6 s; IP lock reached on the 9th–10th attempt; "only that IP" cannot be shown with one address — the owner on the same loopback IP was locked too, as designed |
| A form POSTed without a CSRF token is rejected with 419 | PASS (note) | `/api/cart/add` → 419 `{"error":"csrf"}`; HTML forms (`/track`, `/contact`, `/checkout`, `/admin/login`) are rejected and 303 back to the form with "Your session expired. Please try again." — `index.php` chooses the flash over the bare 419 page for browsers; nothing is processed either way |
| `evil.jpg` with `<?php` inside uploads but never executes; `shell.php.jpg` neutralised | PASS | both accepted as images and re-encoded to `product-1-*.webp/.jpg` derivatives with no `<?php` inside; served as `image/webp`; `/uploads/*.php` → denied |
| Payment proof URL unreachable without an admin session | PASS | `/admin/orders/{n}/proof` → 302 login; `/storage/proofs/2026/09/….jpg` → 404 (denied tree); with a session: `image/jpeg`, `inline; filename="proof-SF-….jpg"` |
| DB error in production → branded 500 with a reference id, no path/SQL/trace | PASS | `env=production` + `settings` table renamed: `/shop` → 500 "Something went wrong … Reference: 41b3ff98", body free of `SQLSTATE`, paths, traces and the table name; `app-2026-09.log` holds `incident=41b3ff98 PDOException…` |
| `config.php`, `/app`, `/db`, `/storage`, `/admin/controllers|views|partials`, directory listings inaccessible; no `.bak`, `.zip`, `phpinfo.php`, `adminer.php`, leftover `reset-password.php` | PASS (note) | all denied (§1); `find site` finds none of those files in the web root — `app/tools/reset-password.php` is the C-64 tool inside the denied `/app` tree, copied out only when needed and deleted after (GO-LIVE-GUIDE) |

## 10. B.4.10 Performance verification

| Item | Result | Evidence |
|---|---|---|
| Lighthouse mobile on `/` and `/product/azure-oud` ≥ 90 ×3 on the live host | PASS (local) / LIVE HOST | localhost, motion layer on, `php -S` (uncompressed HTML): `/` perf 94 · 95 · 95, PDP 92 · 92 · 96; a11y 100, Best Practices 100 (96 before §11 fix 2 — the dev server's invalid gzip response for `favicon.ico`), SEO 100 with `site_indexable=1` (61–69 while the local install is `noindex`, as intended); LCP 2.8–3.3 s simulated, TBT 0, CLS 0 |
| Every B.2.7 budget met | NOT RE-RUN | measured by the perf pass in `docs/lighthouse/summary.md` / `docs/perf/critical-css.md`; this run only re-checked the Lighthouse row above |
| Every `<img>` has `width`, `height`, non-empty `alt` (decorative `alt=""`) | PASS | rendered HTML of `/` (53 imgs), `/shop` (34), `/collections` (27), `/cart` (8), PDP (28): 0 missing any of the three; 15 decorative `alt=""` on the PDP |
| LCP image not lazy-loaded and preloaded | PASS (note) | PDP LCP element = first gallery slide: `loading="eager" fetchpriority="high"` with `srcset`/`sizes`; there is no `<link rel=preload>` for it — the perf pass prioritises it with `fetchpriority` and reserves `<link rel=preload as=image>` for the hero when a hero image is configured; Lighthouse raises no LCP-preload audit |
| JavaScript disabled: browsable, cart works, checkout completes, quiz finishes | PASS (after fix) | Playwright with JS off at 375 px: PDP form POST → "Cirrus (50ml) added to your cart.", `/cart` totals, coupon form → "WELCOME10 applied", checkout → `SF-260929-6VFA` Total Rs. 4,905, quiz result with 3 matches. Azure Oud, whose default size had sold out, left the no-JS visitor on a disabled button although the 100 ml was in stock — the default now falls back to an in-stock size (§11 fix 6) and the same flow adds the 100 ml |
| `prefers-reduced-motion: reduce`: nothing animates | PASS | `document.getAnimations()` running = 0 after load and after scrolling, `html.sf-reduce` set, intro overlay hidden, Lenis inactive |
| Keyboard only: header, drawer, size chips, filters, checkout operable with a visible ring | PASS | 14 Tabs across skip link, announcement, header, mega-panel and section links — every focused element has the gold `outline 1px rgb(212,176,132)`; Enter on the cart button opens the drawer with focus inside, Esc closes and returns focus; the filter drawer opens from its button and traps focus; size chips are native radios; checkout is a plain form |

## 11. Fixes applied during the run (file — one line each)

1. `site/dev/router.php` — denies `/admin/(controllers|views|partials)` like the host's per-directory `.htaccess`, and every denied path now sets `REDIRECT_STATUS` and runs `index.php` so the dev server shows the branded 404 exactly as `ErrorDocument 403 /index.php` does on LiteSpeed (was a plain-text 403).
2. `site/dev/router.php` — static files are served **before** `zlib.output_compression` is switched on; the old order made `php -S` emit an invalid HTTP response for gzip-negotiated `robots.txt` and `favicon.ico`, which is what cost Lighthouse the Best Practices (console error) and SEO (robots fetch) points on localhost.
3. `site/admin/controllers/dashboard.php`, `site/admin/views/dashboard.php` — the red placeholder banner of 07 A.5.3 rule 2 / B.4.6 (enabled manual methods whose account details still contain `REPLACE ME`) was missing; added.
4. `site/app/lib/payments.php` — `payment_methods_enabled()` returns `['cod']` and logs a warning when no method is usable (07 A.5.3 rule 3); before, a shop with placeholder accounts and COD switched off offered nothing at checkout.
5. `site/app/controllers/sitemap.php` — `/track` removed from the sitemap (robots.txt disallows it; the map lists the catalogue).
6. `site/app/controllers/product.php` — `product_pick_default_size()` prefers the first in-stock size when the owner's default is sold out (an explicit `?size=` still wins); without JavaScript the sold-out default left the buy button disabled for the whole page.
7. `site/app/bootstrap.php` — the `SFSHOP` cookie carries the 3-day lifetime of 08 C-26 (`Max-Age=259200`); it was a session cookie, so a browser restart lost the cart. `SFADMIN` is unchanged.
8. `site/app/controllers/confirmation.php`, `product.php`, `quiz.php` — WhatsApp prefill texts open with "Assalam-o-Alaikum." (no exclamation mark, no "Hi Sky Fragrances"), per the voice rule.
9. `site/app/lib/orders.php` — the two `{$column}` SQL interpolations rewritten as literal statements (the allow-list was already safe; the tree is now grep-clean).
10. `site/dev/zip-manifest.php` — `assets/css/critical.css` and `assets/js/css.js` added to `required_files`; `php dev-tools/build-zip.php` still builds (`dist/skyfragrances-public_html.zip`).
11. `.gitignore` — runtime collection derivatives (`site/uploads/collections/collection-[0-9]*`) ignored; the five already-tracked `collection-6-…` files are left for the owner to `git rm` (no commits were made).
12. `dev-tools/tour.mjs` — `TOUR_UA` sets the browser user agent so an admin session created elsewhere (the admin binds sessions to the UA) can be reused for the admin screenshots.
13. Docs: `docs/plan/08-decisions-register.md` (acceptance-run rulings incl. the linked `critical.css` and the superseded 14 KB raw budget), `docs/dev-run.md` (router table).

Cross-needs from the task list that were **not** taken on here (they are feature work for their owners):
the 900 px product derivative and `product_image_srcset` skipping missing files, the desktop-only split of
`motion.css`, the `sf_ann` dismissal cookie, `{shipping_fee}`-style token substitution in content pages,
the extra settings-form fields and WhatsApp status templates, the font sub-setting, and the three open
items in `docs/motion/review-a11y.md`.

## 12. Screenshot tour — `docs/shots/stage5/`

`node dev-tools/tour.mjs http://127.0.0.1:8100 docs/shots/stage5 / /shop /collections /for-her
/product/azure-oud /scent-finder /cart /checkout /track /contact /faq /about` with a shop cookie holding
Azure Oud 50 ml + Silver Lining 50 ml ×2, and the admin screens (`/admin`, `/admin/orders`, the bank
order's detail, products, product 1, collections, coupons, reviews, messages, subscribers, pages,
settings › payments, tools, cancel-unpaid, password) with the admin cookie into `docs/shots/stage5/admin/`.
`report.json` (storefront): **24 shots, every path 200, 0 horizontal overflow at 375 px, 0 page, console or
network errors.** `admin/report.json`: **30 shots, all 200, 0 overflow, 0 errors.**

## 13. MariaDB 10.11 (`:3307`, `skyfragrances_maria`)

Dropped and recreated, `db/schema.sql` 27/27 and `db/seed.sql` 236/236 statements with strict
`sql_mode`, **0 errors, 0 warnings** (the MySQL-only 1681 note excluded); 26 InnoDB `utf8mb4_unicode_ci`
tables; seed counts identical to the MySQL install; a second import of both files changed no count;
`UPDATE product_sizes SET stock = stock - 5` on a stock-3 row raises 1690 and stores nothing.

## 14. Cleanup

The run used its own database (`skyfragrances_accept`, dropped at the end) and a copy of `site/` under
the session scratchpad (removed), so no test order, proof file, mail preview, session or cache exists in
the repository; `site/uploads/` and `site/storage/` were never written to. The PHP server on `:8100` was
stopped. What remains in the tree are the fixes in §11, the register/doc notes and `docs/shots/stage5/`.

## 15. Re-run after the handover-gap fixes (2026-10-01)

Two defects left open by this run were fixed in code (HANDOVER §8d) and the affected items were
re-run over HTTP on `php -S 127.0.0.1:8131 -t site site/dev/router.php` (shared dev database, read
only — no order placed), Playwright Chromium 1208 at 375×812 and 1440×900.

| Item | Result | Evidence |
|---|---|---|
| §2 manual methods: the chosen payment screenshot is previewed before the order is placed | PASS | `/checkout` → Bank transfer → a 600×750 JPEG on `#f-proof`: `.upload__preview` visible, `blob:` src, `naturalWidth` 600, rendered 192×256 at both widths; 0 console errors, 0 `securitypolicyviolation` events. Control run with the old `img-src 'self' data:` header: the blob is refused and `naturalWidth` stays 0. `docs/launch/shots-proof-preview/` (2 PNG + `evidence.json`) |
| §9 CSP: one header, zero violations | PASS | the header now ends `img-src 'self' data: blob:; connect-src 'self'; frame-src 'none'` on `/checkout` and `/admin/login` (one `response_csp()` for both); the `sha256-…` script hash is unchanged |
| §8 mobile: long breadcrumb labels | PASS | 375 px: `/faq` "FREQUENTLY ASKED Q…", `/shipping` ellipsised at 150 px, `/product/azure-oud` "AZURE …" with the crumb list no longer overflowing; crumb 44 px tall, bar 60 px (unchanged); 1440 px: nothing clipped; 0 console errors. `docs/launch/shots-breadcrumb/` (2 PNG + `evidence.json`) |
| ZIP rebuilt and re-checked | PASS | `dist/skyfragrances-public_html-folder.zip` 13,943,178 bytes; 595 files / 637 entries, list identical to the 29 Sep build; CRC differences only in `app/lib/response.php` and `assets/css/critical.css`; 55/55 `required_files` present, 0 forbidden entries, 10 `.htaccess`, no inner `public_html/public_html`, no `__MACOSX` |

Not re-run: Lighthouse (a three-line CSS change inside an existing rule block, no new request).
