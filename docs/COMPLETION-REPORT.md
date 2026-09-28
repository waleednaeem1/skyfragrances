# Sky Fragrances — Launch build completion report

Written 2026-09-28 against branch `main` at `9728844` (working tree carries five uncommitted
file changes and four untracked docs — see §9.1). Scope: the launch build only — storefront,
commerce engine, intro loader, admin panel. The motion layer on `motion-wip` is reported in §7
as work in progress and is not counted toward any brief item.

Sources read for this report: `docs/plan/00-brief.md` (every line), `docs/plan/08-decisions-register.md`,
`docs/launch/storefront-smoke.md`, `docs/launch/admin-smoke.md`, `docs/stage1-run-report.md`,
`docs/stage2-run-report.md`, `docs/HANDOVER.md`, `docs/GO-LIVE-GUIDE.md` (headings), the 36 PNGs and
two `report.json` files under `docs/launch/shots-store` and `docs/launch/shots-admin`, the
`motion-wip` commit message and its `docs/motion/*` notes, and the shipped source under `site/`.

## Status vocabulary

| Mark | Meaning |
|---|---|
| **DONE** | Built and exercised by a recorded run (a smoke report line, a screenshot, or the fresh-install rehearsal). |
| **DONE\*** | Built — the code path exists and was read for this report — but no recorded run exercised it. Treat as untested. |
| **PARTIAL** | Some of the requirement is built and proven; the rest is not. The gap is named. |
| **NOT DONE** | Not built, or built but never proven and the brief's acceptance criterion is unmet. |

Evidence columns cite: `S1` = stage1-run-report, `S2` = stage2-run-report, `SF` = storefront-smoke,
`AS` = admin-smoke, `HO` = HANDOVER, `shots-store/…` and `shots-admin/…` = screenshot files, and
code paths relative to `site/`.

## 0. Summary

- Brief requirements tracked below: 78. **DONE 58 · DONE\* 6 · PARTIAL 10 · NOT DONE 4.**
- The four NOT DONE items: the Lighthouse 90+ target was never measured; the password-recovery
  script the register promised (C-64) was not built; the build was never run on a real Hostinger
  account (every `.htaccess`, `.user.ini` and LiteSpeed behaviour is read, not exercised);
  Easypaisa was never used to place an order.
- Nothing in the launch build references `core.js`, `motion.css` or the motion section modules
  (`SF` step 2, `HO` §7 lint row). `assets/js/motion/config.js` does ship — the intro loader reads
  `window.SF_MOTION` from it — and is listed in `dev/zip-manifest.php` `required_files`.
- The deliverable the owner asked for (a `public_html` folder to upload) exists twice in `dist/`
  and the two copies differ (§6, §9.1). Upload `dist/public_html/` or the `-0917.zip`, not
  `public_html-READY-TO-UPLOAD/`.

---

## 1. Brand (brief lines 3–11)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 1.1 | Name Sky Fragrances, domain skyfragrances.com | DONE | `settings.store_name` seed; `robots.txt` `Sitemap: https://skyfragrances.com/sitemap.xml` (HO §8); installer canonical-host rule (S1 review fix, C-47). |
| 1.2 | Tagline "More Than Just A Scent" | DONE | Hero h1 in `shots-store/home--desktop.png`, `home--mobile.png`; WhatsApp sign-off in AS §4. |
| 1.3 | Logo: gold SF monogram + wordmark on black | DONE | `assets/img/brand/*`, header/footer in every store screenshot; admin Settings › Store shows `logo.png` (`shots-admin/admin_settings--mobile.png`). |
| 1.4 | Ultra-luxury minimal: black #0A0A0A, champagne gold, ivory; Cormorant Garamond + Jost | DONE | `assets/fonts/cormorant-garamond-variable.woff2`, `jost-variable.woff2` (self-hosted, preloaded, `head-meta.php:106`); token layer in `assets/css/site.css`; ivory `.t-light` "Why choose us" band (register Q-22) visible in `home--desktop.png`. |
| 1.5 | Subtle animations: fade/slide on scroll, hover zoom on products | DONE | `assets/js/reveal.js` (`IntersectionObserver` on `.sf-reveal`, line 17–36); `site.css:1631` product-card hover `scale(1.03)`, `:1654` collection-card `scale(1.06)`; `prefers-reduced-motion` block in `site.css` (2 matches). |
| 1.6 | "Must look better than Le Labo, Byredo, J., Scentsation" | PARTIAL | Subjective; no side-by-side review against those brands exists. `docs/stage2-review-design.md` (511 lines) reviewed the design against the spec, not against competitors. The "signature" layer meant to carry this is on `motion-wip` (§7). |
| 1.7 | PKR "Rs. 4,950", Asia/Karachi | DONE | `money('4950.00')` = `Rs. 4,950` (S1 line 118); every PDO connection runs `SET time_zone='+05:00'` (S2 §9 fix 1, register C-55); Settings › Store shows `Asia/Karachi` (`admin_settings--mobile.png`). |

## 2. Hosting & tech — hard constraints (brief lines 13–19)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 2.1 | Hostinger shared: PHP 8.x + MySQL only; no Node, Composer, build step | DONE | 185 first-party PHP files + PHPMailer as 4 plain files under `app/lib/vendor/PHPMailer/`; no `composer.json`, no `package.json` in `site/`; `.github/workflows/deploy.yml` runs only `php -l` before FTP. Node is used only by `dev-tools/tour.mjs` for screenshots (brief line 90) and is asserted absent from the ZIP (HO §7). |
| 2.2 | Plain PHP with PDO prepared statements everywhere, vanilla JS, one CSS file | DONE | `db_query()` throws on a positional placeholder (S1 line 127); named binds throughout `app/lib/*.php`. Storefront = one `assets/css/site.css` (157 KB); the admin has its own `admin.css` (39 KB) — two files, one per surface. 9 vanilla JS files, ~97 KB raw, all `defer`; `product.js` only on product pages (`app/views/layout.php:56`). |
| 2.3 | Works by uploading via hPanel File Manager + importing the DB | PARTIAL | Built for it: ZIP with flat root, 10 `.htaccess` files, `install.php` creates the tables (no manual import needed; `db/schema.sql` + `seed.sql` also load clean on MariaDB 10.11, SF step 7). Rehearsed from the unpacked ZIP on a local `php -S` with a fresh DB (HO §7). **Never run on a real Hostinger account** — see 5.16. |
| 2.4 | `config.php` for DB credentials | DONE | Root `config.php` written by the installer from `config.sample.php`, `return [...]` array, chmod 0400 after install, never rewritten (register C-27, C-70; S2 §2). Denied by `<Files "config*.php">`. |
| 2.5 | One-time `install.php`: creates tables, admin account, sample data; tells the owner to delete it | DONE | Four screens; 27 schema + 234 seed statements; refuses to re-run; self-deletes on the production host, red "Delete install.php now" panel with a delete button elsewhere (S2 §2 and §9 fix 1; HO §7 rows "Installer screen 1–4", "Refusal"). Dashboard banner while the file exists (`shots-admin/admin--mobile.png`). |
| 2.6 | Pretty URLs via `.htaccess` (`/product/azure-oud`, `/shop`, `/cart`) | DONE | Root `.htaccess` rewrite to `index.php`; every pretty route 200 under `dev/router.php` which mirrors the rules (SF step 2). The real file is **read, not exercised** (`php -S` ignores it) — see 5.16. Installer probes `/__rewrite-probe` on the live host (S1 fix). |
| 2.7 | Force HTTPS | DONE\* | `.htaccess:21–24` redirects `http` → `https` with `R=302`; the installer's Finish or Settings › Advanced › "Make HTTPS permanent" flips it to 301 after a certificate self-check (register C-52). `www` → apex 301 at `:27`. Never exercised on Apache/LiteSpeed. hPanel's own "Force HTTPS" must stay off (HO §5.11). |

## 3. Storefront pages (brief lines 21–41)

### 3.1 Home

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.1a | Full-screen hero with logo/tagline + CTA | DONE | `app/views/home.php:31–57`; `shots-store/home--desktop.png` / `home--mobile.png` (tagline, two CTAs, scroll cue). The hero **image is empty until the owner uploads one** (`hero_image_desktop/_mobile` settings); today's hero is type on black. |
| 3.1b | Announcement bar | DONE | `app/partials/announcement-bar.php`; `settings.announcement_text` change reflected on `/` (AS §5 Settings); visible in every store screenshot (the "STAGE4 ANNOUNCEMENT BANNER TEXT" is another agent's dev data, SF step 6). |
| 3.1c | Shop by collection | DONE | `home.php:59–68`; five collection cards in `home--desktop.png`. |
| 3.1d | Best sellers | DONE | `home.php:71–80`; rail in screenshots. Sorted by `sales_count` — meaningful only once real orders exist. |
| 3.1e | New arrivals | DONE | `home.php:83–96`. |
| 3.1f | For Him / For Her / Unisex | DONE | `home.php:99–108`; three tiles in screenshots; link to `/for-him`, `/for-her`, `/unisex`. |
| 3.1g | Why-choose-us: long-lasting, COD nationwide, fast delivery, easy exchange | DONE | `home.php:120–133`; the four tiles with those exact promises in `home--desktop.png` (ivory band). `delivery_time` is interpolated from Settings. |
| 3.1h | Newsletter signup | DONE | `home.php:195–199` + footer; `POST /api/newsletter` 200/422/429 (S1 lines 71–72, review fix "rate_limits"); subscriber appears in admin (AS §5). |
| 3.1i | Instagram section | DONE | `home.php:165–193`; six tiles from `instagram_tile_1..6_image/url` settings, falling back to catalogue images (`@skyfragrances` band in `home--desktop.png`). No live Instagram feed — tiles are owner-uploaded images. |
| 3.1j | (added) Scent Finder band, Customer Voices | DONE | `home.php:111–118`, `:135–157` (real approved reviews only, off switch `home_reviews_enabled`, register §4). |

Caveat for all rails: `app/controllers/home.php` hides best sellers / new arrivals / for-whom rows
and shows an "empty" hero when fewer than 4 active products exist (HO §5.2).

### 3.2 Shop

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.2a | Filters: collection, gender, scent family, price range | DONE | `app/controllers/listing.php:115–129`; sidebar with counts in `shots-store/shop--desktop.png`. Price is a **band** parameter (`price=3000-9000`), not free min/max inputs (S2 §3 row 3). |
| 3.2b | Sort | DONE | Featured / newest / best / price-asc / price-desc (`listing.php:8–9, 165–173`); `price_asc` spelling 301s to canonical (S2 fix 6). |
| 3.2c | Search | DONE | `/search?q=` scored LIKE (register C-38) + `/api/search-suggest` header suggestions (S2 regression fix 1 for thumbnails). |
| 3.2d | Pagination | DONE | `page` param; out-of-range page is 404 by spec (S2 "After review" §Install and routes). Seed has 12 products, so page 2 never renders on the sample data. |

### 3.3 Product page

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.3a | Image gallery with zoom | DONE | `app/partials/gallery.php` ("Hover to zoom", `:38`), `assets/js/product.js:288` toggles `is-zoomed`; zoom derivative 1200×1500 / capped 1400×1750 (AS §2). Mobile shows a swipe rail with dots (`product_azure_oud--mobile.png`). |
| 3.3b | Size selector, each size with own price, sale price, stock | DONE | `app/views/product.php:79–105`; sale shown with struck price; "Out of stock" per size; AS §2 edit 4950/4450 → 5250 reflected on the PDP. |
| 3.3c | Scent notes pyramid (top/heart/base) | DONE | `app/partials/notes-pyramid.php`; "The Composition" in `product_azure_oud--mobile.png`. |
| 3.3d | Longevity & sillage meters | DONE | `app/partials/meters.php`; "10+ hours" / "Strong" bars in the same screenshot. |
| 3.3e | Season / occasion | DONE | `product.php:168–190`; "Autumn · Winter", "Evening · Signature" chips. |
| 3.3f | Customer reviews, admin-approved only, no fake reviews | DONE | Storefront `POST /product/{slug}/review` → `pending`; approve/reject flips `rating_avg/count` and PDP visibility (AS §5). All 8 seeded reviews are `pending` (register C-68) and are deleted by Remove sample data (HO §7). |
| 3.3g | Related products | DONE | `app/partials/related.php`; "You May Also Like" in the mobile screenshot. |
| 3.3h | Sticky add-to-cart on mobile | DONE | `product.php:205` `.sticky-bar`; visible at the top of `product_azure_oud--mobile.png` ("Azure Oud 50ml · Rs. 8,950 · ADD"); price updates on size change (AS §2 "sticky price Rs. 5,250"). |
| 3.3i | WhatsApp order button | DONE | `product.php:122` `js-whatsapp-order` with the product/size in the message; becomes the primary CTA when sold out (register C-67). |

### 3.4 Cart

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.4a | Slide-out cart drawer | DONE | `app/partials/cart-drawer.php`, `assets/js/cart.js` (drawer render, line 76). |
| 3.4b | Cart page | DONE | `app/views/cart.php`; `shots-store/cart--desktop.png`, `cart--mobile.png`; 320–1440 px overflow fixed (S2 regression 3). |
| 3.4c | Free-shipping progress bar | DONE | `cart.php:95–98`, `cart.js:13–23` `renderFreeShip`; threshold on the pre-discount subtotal (register C-43); AS §5 `free_shipping_threshold=99999` → checkout shows Rs. 777 delivery. Note: the seed catalogue has nothing under Rs. 3,950, so the "Rs. X away" state is only provable at unit level (SF step 3). |
| 3.4d | Coupon codes | DONE | `/api/cart/coupon`; WELCOME10 / expired / exhausted / invalid / below-minimum cases (S2 §4, SF step 3); `coupon` rate bucket (C-66). |

### 3.5 Checkout

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.5a | Guest, no account | DONE | No user table for customers; `/checkout` renders with a cart, 302 → `/cart` when empty (SF step 2). |
| 3.5b | Fields: name, phone, email (optional), city, address, notes | DONE | `app/views/checkout.php:11` (`customer_name`, `customer_phone`, `customer_email`, `city`, `address`, `postal_code` optional, `customer_note`); `shots-store/checkout--mobile.png`. City has a `<datalist>`. |
| 3.5c | Cash on Delivery | DONE | COD orders placed end-to-end in S2 §5 (a)(a2)(a3), SF step 4, HO §7, AS §4. |
| 3.5d | Bank Transfer (manual): show account details (admin-editable), customer enters transaction ID, uploads screenshot | DONE | S2 §5 (b): `payment_status=awaiting_verification`, `payment_reference=TXN-…`, `payment_account_snapshot` from the Settings › Payments keys, PNG proof re-encoded to JPEG under `storage/proofs/2026/09/`, direct URL 403; repeated in SF step 4. |
| 3.5e | JazzCash (manual) | PARTIAL | Same code path as bank (`payment_method IN ('bank','jazzcash','easypaisa')`, `checkout.php:141`). Only the **C-58 refusal** was exercised — a JazzCash attempt while a bank order awaited verification → 422 (S2 §5 b2). No JazzCash order was ever placed. |
| 3.5f | Easypaisa (manual) | NOT DONE (as a test) | Code path identical to 3.5d/e; no recorded run ever selected Easypaisa. GO-LIVE-GUIDE Part 8 delegates "one order each" to the owner. |
| 3.5g | Manual methods hidden until real account details exist | DONE (design) | `settings.php` treats the seeded `REPLACE ME` values as unset (S2 §5 intro); `checkout--mobile.png` shows only COD + Bank Transfer because only bank details were filled on the dev DB. |

### 3.6 Confirmation, tracking, quiz, pages, WhatsApp

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.6a | Order confirmation page | DONE | `/order/{number}?t={token}` (`app/controllers/confirmation.php`); wrong/absent token → `/track` pre-filled (register C-76); receipt 200 with and without a session (S2 fix 4). |
| 3.6b | Email to customer and admin | PARTIAL | Built: `app/emails/order-customer.php`, `order-admin.php`, `status-update.php`, `contact-*.php`; outbox table drained on admin page loads (≤ 2 sends / 12 s, C-56) and by optional `cron.php`. **No email has ever left the app** — no SMTP was configured in any run; every "sent" mail is an HTML preview in `storage/logs/mail-preview/` (S1 line 124, S2 §5, SF step 8). Delivery, SPF/DKIM and inbox placement are unproven. The dev dashboard shows "SMTP is not configured — 19 emails cannot be sent" (`admin--mobile.png`). |
| 3.6c | Track order page (order number + phone) with status timeline | DONE | `POST /track` → Pending → Confirmed → Packing → Shipped → Delivered timeline; wrong phone reveals nothing; rate-limited 10/15 min per IP, 20/24 h per phone (S2 §5, S1 review "rate_limits"); `shots-store/track--mobile.png`. The 1-in-5 weighting of successful lookups from 06b §6.3 is not implemented (S1 review). |
| 3.6d | Scent Finder quiz (4–5 questions → recommends products) | DONE | 5 questions / 19 options / 31 score rows seeded; `/scent-finder` step UI "Question 1 of 5" (`scent_finder--mobile.png`); result page recommends three perfumes (`app/controllers/quiz.php`). Questions are not editable in the admin (register §4). |
| 3.6e | About, Contact (form → DB + WhatsApp), FAQ, Shipping, Returns & Exchange, Privacy, Terms | DONE | Six `content_pages` rows + FAQ accordion with `FAQPage` JSON-LD (`app/controllers/page.php:41`); `/contact` writes `contact_messages` (S1 line 60), 5/h rate limit, honeypot; admin message detail carries a `wa.me` reply link (AS §5). Body HTML sanitised on save and render (XSS probe in AS §5). Copy is launch placeholder text the owner should review. |
| 3.6f | Custom 404 page | DONE | Full-layout 404, `noindex`, path not echoed (S1 line 79; S2 tour "unknown path 404"). |
| 3.6g | Floating WhatsApp button on every page | DONE | `app/partials/whatsapp-button.php` in `layout.php`; visible in all 24 store screenshots. Rendered only when `settings.whatsapp` is non-empty. |

## 4. Admin panel (brief lines 43–61)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 4.1 | Secure login: `password_hash`, session regeneration, CSRF on every form, login rate limiting | DONE | bcrypt cost 12 (rehash on first login, S1 line 105); CSRF gate before every admin POST (4 token-less POSTs logged as warnings, AS §7); 5 fails/username → progressive delay, 10 fails/IP → 429 for 15 min, correct password while locked still 429 (AS §1, register C-51); session bound to the browser UA (AS §1 last row); `SFADMIN` cookie `path=/admin; HttpOnly; SameSite=Strict`, `X-Robots-Tag: noindex`, `Cache-Control: no-store` (S1 line 99). |
| 4.2 | Fully mobile-friendly | DONE | 12 admin screenshots at 1440 and 375, `report.json` all 200, 0 overflow after the `.adm-table-wrap` fix (AS §6); bottom tab bar + FAB on phones (`shots-admin/admin--mobile.png`, `admin_orders--mobile.png`). |
| 4.3 | Dashboard: today/month revenue, order counts by status, low-stock alerts, latest orders, best sellers | DONE | `admin/controllers/dashboard.php:18–105`; all five blocks in `admin--mobile.png` ("Revenue today Rs. 5,250", "This month" counts, "Low stock", "Latest orders", "Best sellers · last 30 days"); revenue figure reconciled to `SUM(grand_total)` (AS §1). |
| 4.4 | Products: add/edit/delete | DONE | Create → `#14`, edit prices/alts, rename slug with 301, hide/show, duplicate, hard-delete (no orders, typed `DELETE`), soft-delete/restore when an order line exists, bulk actions (AS §2). |
| 4.5 | Multiple images upload, auto-resize, JPG/PNG/WEBP only | DONE | Three uploads → thumb 200×250 / card 600×750 / zoom 1200×1500 as WebP + JPG + OG 1200×630; SVG renamed `.png` → 422; 7 MB → 422; reorder, set primary, delete removes all derivatives (AS §2). One file per request (register C-46), so "multiple" is sequential through `admin-catalogue.js`. `admin_products_14--desktop.png` shows the photo panel. |
| 4.6 | Sizes with price / sale price / stock / SKU | DONE | Two sizes, SKU kept and auto-suggested, `sale < price` enforced, per-size low-stock threshold, default size (AS §2; screenshot). |
| 4.7 | Notes, gender, scent family, featured/new toggles, active/hidden, SEO title & description | DONE | All in `admin/views/product-form.php` and exercised in AS §2; scent families have their own CRUD (`/admin/scent-families`, AS §3). |
| 4.8 | Collections (categories) CRUD | DONE | Create with image (card 800×450 + zoom), edit, replace image, reorder, toggle, delete refused while products inside, delete with typed word (AS §3). |
| 4.9 | Orders: list with filters/search | DONE | Status chips, method filter, search by number (303 straight to detail), name prefix, phone ≥ 7 digits (AS §4); `admin_orders--mobile.png`. |
| 4.10 | Order detail | DONE | Customer, items, totals, timeline, notes, activity, print links (`admin_orders_SF_260928_PPYT--mobile.png`). |
| 4.11 | Change status Pending → Confirmed → Packing → Shipped → Delivered / Cancelled | DONE | Full chain walked; illegal jump refused with the current status named; `shipped` refused without a courier (AS §4). `delivered`/`cancelled` terminal (register C-48). |
| 4.12 | Add courier + tracking number | DONE | `POST /shipping courier_name=TCS tracking_number=…` (AS §4); `tracking_url` rendered only when `https://` (C-75). |
| 4.13 | View payment proof | DONE\* | Route `GET /admin/orders/{order}/proof` (`app/routes-admin.php:36`) streams from `storage/proofs/`; the storefront side and the 403 on direct access are proven (S2 §5 b). No recorded run opened the proof through the admin route; the order list shows the paperclip marker on proof-bearing orders (`admin_orders--mobile.png`). |
| 4.14 | Mark as paid | DONE\* | `admin/controllers/order-actions.php:241–260` (`mark_paid`, approves a pending proof when the amount matches, else `payment_mark_paid()`); "Mark as paid" button in the order screenshot. Not pressed in any recorded run; `docs/build/admin-orders.md` names the function but records no test. Reject-proof and refund paths likewise DONE\*. |
| 4.15 | Print invoice / packing slip | DONE | Both 200 with `admin-print.js`; slip shows "Collect Rs. 5,250 — cash on delivery" (AS §4). Packing slip omits prices (register Q-14). |
| 4.16 | Restore stock on cancel | DONE\* | `order-actions.php:216–223` + `stock_restore_order()` under the C-12 guard; automatic only from pending/confirmed/packing, explicit "Parcel received back" (`/stock-back`) after shipped (register C-48). Stage 2 cleanup restored stock from demo cancellations, but no recorded admin run pressed Cancel. |
| 4.17 | One-click WhatsApp message to customer | DONE | 8 `wa.me` links per order with status-specific templates; Shipped text carries courier, tracking and amount due (AS §4); "Send 'Delivered' message" in the screenshot. |
| 4.18 | Export to CSV | DONE | UTF-8 BOM, CRLF, phone as `'0300…`, formula guard, audit row per export (AS §4). |
| 4.19 | Coupons: percent/fixed, min order, usage limit, expiry | DONE | `SMOKE10` create/edit/toggle, duplicate refused, storefront apply (AS §5); seed rows carry `min_order_total`, `usage_limit`, `expires_at`, plus `per_phone_limit`. |
| 4.20 | Reviews moderation | DONE | Pending default, approve/reject, bulk (AS §5). |
| 4.21 | Contact messages | DONE | New → read → replied → archived, WhatsApp/mailto reply links, admin note (AS §5). |
| 4.22 | Newsletter subscribers with CSV export | DONE | List, search, `export.csv` with BOM, unsubscribe/resubscribe (AS §5). No "add subscriber" screen (`docs/admin-marketing-screens.md:70`). |
| 4.23 | Settings: store info, logo, phone/WhatsApp/email, socials, announcement, hero text, shipping fee + free threshold, delivery time, enable/disable each payment method, bank/JazzCash/Easypaisa details, meta description | DONE | Seven tabs (`HO` §3 lists every key); all re-posted unchanged → "No changes to save."; announcement, shipping fee, threshold, method toggles, WhatsApp normalisation and validation errors exercised; "at least one payment method" guard (AS §5); `admin_settings--mobile.png`. |
| 4.24 | Admin account: change password | DONE | Wrong current, too short, change, change back, logged (AS §5). Single account; no roles (register C-21). |
| 4.25 | (register C-64) Password-recovery script `app/tools/reset-password.php` | NOT DONE | Not built. Login and Tools pages now point at the phpMyAdmin procedure in GO-LIVE-GUIDE Part 10 (HO §5.1, §8). |

## 5. Quality requirements (brief lines 63–73)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 5.1 | Mobile-first, fully responsive, tested at 375 px | DONE | 24 store + 12 admin shots at 375, 0 overflow, 0 console errors (`shots-store/report.json`, `shots-admin/report.json`); cart measured at 320/375/414/480/768/1440 (S2 regression 3); `docs/stage2-review-mobile.md` pass. |
| 5.2 | Lazy-load images | DONE | 19 `loading="lazy"` sites in `app/`; first two best-seller cards `eager` (`home.php:76`); hero preload with `fetchpriority="high"` (`head-meta.php:109`). |
| 5.3 | Minimal JS | DONE | Five storefront files (`reveal`, `ui`, `cart`, `forms` + `product` on PDP only), all `defer`, no framework, no third-party script. |
| 5.4 | Lighthouse score 90+ | NOT DONE | No Lighthouse run exists in the repository or any report. The only analysis is `docs/motion/plan-critique.md` B1 (on `motion-wip`), which estimates that the **intro loader that ships on `main`** hides the LCP element for ≈ 1.3–1.6 s after DOMContentLoaded on simulated 4G, landing LCP at ≈ 3.6–4.1 s and capping the mobile Performance score around 75–80. That estimate was never measured either way. |
| 5.5 | Unique titles / meta | DONE | Per-product SEO title/description with fallback (`admin_products_14--desktop.png` "How it may look on Google"); collection/scent/page SEO fields. |
| 5.6 | Open Graph tags | DONE | `head-meta.php:92` onward; per-product OG JPEG 1200×630 generated on upload (AS §2). |
| 5.7 | Product JSON-LD | DONE | `app/controllers/product.php:420` (`Product` + `Offer`, `lowPrice` checked in AS §2); also `BreadcrumbList`, `WebSite`, `Organization`, `FAQPage`. |
| 5.8 | sitemap.xml | DONE | Dynamic, 42 URLs, well-formed, 1-hour cache (SF step 2). **404 while `site_indexable=0`**, which the installer sets on any host other than skyfragrances.com (HO §5.4). |
| 5.9 | robots.txt | DONE | Real static file; installer rewrites the `Sitemap:` line; committed copy now points at the production domain (HO §8). |
| 5.10 | Clean URLs | DONE | Case-normalising 301s, trailing-slash strip, `index.php` strip (`.htaccess:30–33`; S1 line 80). One extra hop on every receipt: checkout redirects to the upper-case order URL and the router 301s it to lower-case (S2 "Not regressions"). |
| 5.11 | Alt text | DONE | Required on product images, defaulted to "{name} — {sizes} perfume by Sky Fragrances", editable (AS §2). |
| 5.12 | Prepared statements, output escaping, CSRF tokens | DONE | See 2.2; `e()` everywhere — `<b>markup</b>` in a description renders escaped, `<script>`/`onerror`/`javascript:` stripped from page bodies (AS §2, §5); CSRF on every storefront and admin POST (S1 review "Storefront CSRF per controller"). |
| 5.13 | Upload validation | DONE | MIME sniff + GD re-encode (SVG-as-PNG rejected), 6 MB admin / 5 MB proof caps, pixel budget from `memory_limit`, `CONTENT_LENGTH` vs `post_max_size` 413 branch (AS §2, S1 review C-46). |
| 5.14 | Block PHP execution in /uploads | DONE\* | `uploads/.htaccess`: `RemoveHandler`/`RemoveType`, `AddType text/plain`, `<FilesMatch> Require all denied` (register C-37); `dev/router.php` returns 403 for `/uploads/x.php` (S2 "After review"). The real directive set is unexercised on LiteSpeed; `install.php` screen 4 probes it on the live host. |
| 5.15 | Never expose errors in production | DONE | `app/bootstrap.php:132–134` `display_errors=0` when `env=production`; branded 500 page; `E_DEPRECATED` logged not thrown (S1 review). Log threshold warning in production. |
| 5.16 | (implied by 2.x) Behaviour on the target host | NOT DONE | No run on Hostinger, Apache or LiteSpeed. Unexercised: all 10 `.htaccess` files, `.user.ini`, HTTPS redirect, `X-Powered-By` removal, `mod_deflate`, the installer's live-host probes, `session.cookie_secure`. MariaDB proven at 10.11 only, not the 10.4 floor (SF step 7). |
| 5.17 | Stock decremented on order, can't go negative | DONE | `INT UNSIGNED` + strict `sql_mode` + `stock >= :qty` guard inside a locked transaction; oversell → 409 "sold out while you were checking out. Only 2 left" / "is now sold out" with no order row (S2 §5 d/d′, SF step 4). |
| 5.18 | Prices always recalculated server-side | DONE | One `cart_price()` pipeline on every render and inside the transaction; `price_token` mismatch rolls back (register C-43); sale price and coupon maths verified line by line (S2 §4). |
| 5.19 | Seed 12 sky-named perfumes, 5 collections, WELCOME10 (10 % above Rs. 3,000) | DONE | Azure Oud, Cirrus, Aurora Bloom, Stratus Noir, Eclipse Velvet, Zephyr Blanc, Silver Lining, Cumulus Cashmere, Saffron Zenith, Halo Rose, Nimbus Rain, Vetiver Squall; Dawn Chorus, Azure Heights, Golden Hour, Midnight Meridian, Monsoon Veil (S1 seed table); `db/seed.sql:179` WELCOME10 percent 10, min 3000. Two limits the brief did not ask for are seeded: 1 use per phone and expiry 2027-09-25 (register Q-25). Two extra coupons ship (EIDSALE500 expired, FIRST50 exhausted). Sample product art is placeholder bottle renders, not photographs. |
