# Sky Fragrances — Launch build completion report

Written 2026-09-28 (late evening, Karachi) against branch `main` at commit `0f714ea` **plus the
uncommitted working tree** (see §9.1: `site/index.php`, `site/install.php`, `site/app/lib/text.php`,
`site/dev/zip-manifest.php`, `.gitignore`, untracked `README.md` and launch evidence). The ZIP the
owner receives, `dist/skyfragrances-20260928-1906.zip`, was built from that working tree on 2026-09-29
00:06 Karachi (its only difference from the 23:16 `1816` build is `app/tools/reset-password.php`).
Scope: the launch build only — storefront, commerce engine, intro loader, admin panel. The motion
layer on `motion-wip` is reported in §7 as work in progress and counts toward no brief item.

This report replaces the earlier copy written against commit `9728844` at 14:34; everything below
was re-derived from the current files.

Sources read for this report, line by line: `docs/plan/00-brief.md`; `docs/plan/08-decisions-register.md`
(§3 open questions, §4 scope decisions, the C-nn rulings cited below); `docs/launch/storefront-smoke.md`
(run 22:09–22:25); `docs/launch/admin-smoke.md` (run 22:45–23:07, 86 checks); `docs/stage1-run-report.md`;
`docs/stage2-run-report.md`; `docs/HANDOVER.md` (incl. §7 fresh-install rehearsal 23:18–23:24);
`docs/launch/guide-review.md`; `docs/launch/observations-for-polish.md`; `docs/build/admin-orders.md`;
the 24 PNGs + `report.json` in `docs/launch/shots-store/`, the 12 PNGs + `report.json` in
`docs/launch/shots-admin/`, `docs/launch/shots-fresh-install/04-finish.png` + `evidence.json`; the
`motion-wip` commit `b37bd1f` and `docs/motion/00-brief.md` / `PLAN.md` headings; and the shipped
source under `site/` (grepped for every claim marked "code").

## Status vocabulary

| Mark | Meaning |
|---|---|
| **DONE** | Built and exercised by a recorded run (a smoke-report line, a screenshot, or the fresh-install rehearsal). |
| **DONE\*** | Built — the code path exists and was read for this report — but no recorded run exercised it. Treat as untested. |
| **PARTIAL** | Some of the requirement is built and proven; the rest is not. The gap is named. |
| **NOT DONE** | Not built, or built but never proven and the brief's acceptance criterion is unmet. |

Evidence abbreviations: `S1` = stage1-run-report, `S2` = stage2-run-report, `SF` = storefront-smoke,
`AS` = admin-smoke, `HO` = HANDOVER, `OBS` = observations-for-polish, `GR` = guide-review,
`shots-store/…`, `shots-admin/…`, `shots-fresh-install/…` = screenshot files; code paths are
relative to `site/`.

## 0. Summary

- Requirement rows tracked in §1–§6: **102. DONE 90 · DONE\* 4 (built, never exercised) · PARTIAL 7 · NOT DONE 1.**
  (Before the 2026-09-29 gap run: 83 / 7 / 8 / 4.)
- The one NOT DONE item: **5.16** — no Hostinger run is recorded; `OBS` proves a human put the build
  on Hostinger on 2026-09-28 and found three problems there, but no report, screenshot or route table
  from that host exists in the repository.
- Closed on 2026-09-29 (HO §8b): the price-range filter (3.2b), JazzCash and Easypaisa orders
  (3.5d/e, 6.4), proof viewer / mark paid / restore-stock-on-cancel (4.10, 4.11, 4.13), and the
  C-64 password-recovery tool (`app/tools/reset-password.php`, built and exercised over HTTP).
  Lighthouse (5.4) moved from *never measured* to *measured and below target on mobile*: 76–77
  mobile, 98 desktop (`docs/launch/lighthouse.md`).
- The seven PARTIAL rows: hPanel upload (2.3 — rehearsed locally, a live extract dropped the
  dot-files), order emails (3.6b — built, never delivered: no SMTP in any run), Lighthouse (5.4 —
  mobile 76–77 against a 90 target; the intro loader is the whole gap), "never expose errors" (5.15
  — PHP side proven, `.htaccess` side only read, CSP stripped by Hostinger's CDN), MariaDB 10.4+
  (5.20 — proven on 10.11 only), every admin function tested (6.5 — six actions never pressed, three
  of them deliberately because they change shared state), and the go-live guide (6.7 — complete,
  38 review gaps applied, never trialled by a non-developer, no hPanel screenshots).
- The four DONE\* rows are code that exists and was read but that no run touched: HTTPS force
  (2.7), cart drawer open state (3.4a), the uploads PHP block on a real Apache/LiteSpeed (5.14), and
  the push-to-deploy workflow (6.8).
- Nothing in the launch build references `core.js`, `motion.css` or the motion section modules
  (`SF` step 2 grep over responses and sources; `HO` §7 ZIP check). `assets/js/motion/config.js` does
  ship — the intro loader reads `window.SF_MOTION` from it — and is a `required_files` entry in
  `dev/zip-manifest.php`.
- The `dist/` folder now has exactly one current ZIP (`1906`), one unpacked twin and the
  `htaccess-upload/` fallback; the `1816` ZIP, `skyfragrances-update-2026-09-28.zip`, `update-pack/`,
  `admin-ui-fix/` and the stale `public_html-READY-TO-UPLOAD/` are under `dist/old-builds/`. **Layout contradiction to resolve
  before the owner extracts again:** `OBS` says hPanel's extractor drops top-level dot-files and "the ZIP
  now wraps everything in `public_html/`", but the 23:16 ZIP is flat (`favicon.ico`, `install.php`,
  `.user.ini` at the root; zero entries under `public_html/`) and the guide's Part 4 describes the
  flat extraction. If the `OBS` finding is right, extracting this ZIP in hPanel loses `.htaccess` and
  `.user.ini` (see §5.16, §9.1).

---

## 1. Brand (brief lines 3–11)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 1.1 | Name Sky Fragrances, domain skyfragrances.com | DONE | `settings.store_name` seed; `robots.txt` line 14 `Sitemap: https://skyfragrances.com/sitemap.xml`; installer canonical-host rule (S1 review, C-47); "Sky Fragrances" in the header/footer of every `shots-store/*` PNG. |
| 1.2 | Tagline "More Than Just A Scent" | DONE | Hero h1 in `shots-store/home--desktop.png` and `home--mobile.png`; WhatsApp sign-off "Sky Fragrances — More Than Just A Scent" (AS §4). The `<h1>` of `/about` also reads it (`about--desktop.png`). |
| 1.3 | Logo: gold SF monogram + wordmark on black | DONE | `assets/img/brand/*`, `assets/img/logo.png/.svg`; monogram in the header and lockup in the footer of every store shot; Settings › Store shows `logo.png` as current (`shots-admin/admin_settings--desktop.png`). |
| 1.4 | Ultra-luxury minimal look: #0A0A0A black, champagne gold, ivory, Cormorant Garamond headings, Jost body, whitespace | DONE | `assets/fonts/cormorant-garamond-variable.woff2`, `jost-variable.woff2` (self-hosted, OFL licences shipped); serif headings and gold rules visible in `home--desktop.png`, `product_azure_oud--desktop.png`, `faq--desktop.png`. Whether it "looks better than Le Labo / Byredo" is a judgement no run can prove; what the shots show is a consistent dark-gold system with **placeholder bottle art** (§8, `OBS`). |
| 1.5 | Subtle animations: fade/slide on scroll, hover zoom on products | DONE | `assets/js/reveal.js` (`.sf-reveal`, fixed in `0f714ea` for Back/anchor restores — `OBS`); product-card hover image swap `app/partials/product-card.php:166`; gallery "Hover to zoom" `app/partials/gallery.php:38` + `product.js:288`. `prefers-reduced-motion` honoured (`site.css`, 2 blocks; intro `sf-intro-calm` path SF step 5). |
| 1.6 | Market Pakistan, PKR as "Rs. 4,950", Asia/Karachi | DONE | `money('4950.00')` = `Rs. 4,950` (S1 CLI contract); every price in the screenshots; Settings › Store timezone read-only `Asia/Karachi` (`admin_settings--desktop.png`); dashboard dates in Karachi time (`dashboard.php:55`). |

---

## 2. Hosting & tech (brief lines 13–19)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 2.1 | Hostinger shared: PHP 8.x + MySQL only; no Node, Composer, build step | DONE | No `composer.json`, `package.json` or `node_modules` under `site/`; PHPMailer is vendored by hand (`app/lib/vendor/PHPMailer/`, 3 files + licence); `node --check` is dev tooling only (SF step 1). The ZIP has no `dev/` (HO §7). |
| 2.2 | Plain PHP + PDO with prepared statements everywhere; vanilla JS; one CSS file | DONE | `app/lib/db.php` named-placeholder helpers; `db_query()` with a positional placeholder throws (S1 CLI contract). One storefront stylesheet `assets/css/site.css`; the admin has its own `admin.css` (a second file, for a second surface — not a violation of the intent). 10 JS files, no framework. |
| 2.3 | Works by uploading via hPanel File Manager + importing the DB | PARTIAL | Rehearsed locally from the ZIP with a fresh database (HO §7, `shots-fresh-install/04-finish.png`). On the real hPanel a human found that the extractor **drops top-level dot-files** (`OBS`), which the flat ZIP depends on; the workaround files `dist/htaccess-upload/htaccess.txt` + `user.ini` exist and the guide (Part 4, "If you uploaded the folder") covers renaming them. The ZIP layout was not changed to match the `OBS` note (§0). |
| 2.4 | `config.php` for DB credentials | DONE | Written by the installer at mode 0400 (`shots-fresh-install/04-finish.png` "config.php locked"); `config.sample.php` ships; `config.php` excluded from the ZIP (HO §7). |
| 2.5 | One-time `install.php`: creates tables, admin, sample data, tells the owner to delete it | DONE | Four screens, 27 schema + 234 seed statements, admin created, lock written (HO §7; `04-finish.png`). Red "Delete install.php now" panel with a self-delete button on non-production hosts; self-deletes on skyfragrances.com (`install.php:912–934`, HO §5.3). Re-run refused every time (`06-refusal.png`). Installer hardening: owner key inside the form (`c220904`, after a human found the key box outside the form — `OBS`), fail-closed on a dead `config.php` (S1 review). |
| 2.6 | Pretty URLs via `.htaccess` (`/product/azure-oud`, `/shop`, `/cart`) | DONE | Every pretty route 200 through the dev router that mirrors the rewrite (SF step 2, 38 URLs; S2 §3); the real `.htaccess` (`site/.htaccess` lines 2–27) is read, not exercised (§5.16). `GET /__rewrite-probe` lets the installer self-check the rewrite on the real host (S1 fix). |
| 2.7 | Force HTTPS | DONE\* | `.htaccess:21–27` redirects `http` → `https` as a 302 that the installer / Settings › Advanced flips to 301 after a live certificate check (C-52); `www` → apex 301. Cannot run under `php -S`; on Hostinger the guide says to keep hPanel "Force HTTPS" **off** (HO §5.11, GR gap 3). HSTS deliberately off (Q-23). |

---

## 3. Storefront pages (brief lines 21–41)

### 3.1 Home

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.1a | Full-screen hero with logo/tagline + CTA | DONE | `app/views/home.php`; `shots-store/home--desktop.png` / `home--mobile.png` (eyebrow, "More Than Just A Scent", two CTAs). The hero image slot (`hero_image_desktop/_mobile`) is **empty until the owner uploads one** — today the hero is type on black. |
| 3.1b | Announcement bar | DONE | `app/partials/announcement-bar.php`; a settings change shows on `/` and was restored (AS §5). The "STAGE4 ANNOUNCEMENT BANNER TEXT" in every store shot is another agent's dev data, not the seed (SF step 6). |
| 3.1c | Shop by collection | DONE | Five collection cards in `home--desktop.png`; a sixth "Stage4 Collection Edited" is dev data. |
| 3.1d | Best sellers | DONE | Rail in `home--desktop.png`; ordered by `sales_count` (`listing.php:229`) — meaningful only once real orders exist. |
| 3.1e | New arrivals | DONE | Rail in `home--desktop.png` with NEW badges. |
| 3.1f | For Him / For Her / Unisex | DONE | Three tiles in both home shots → `/for-him`, `/for-her`, `/unisex` (all 200, SF step 2). |
| 3.1g | Why-choose-us: long-lasting, COD nationwide, fast delivery, easy exchange | DONE | Four tiles with those promises on the ivory band (`home--desktop.png`); `delivery_time` interpolated from Settings. |
| 3.1h | Newsletter signup | DONE | Section + footer form; `POST /api/newsletter` 200 / 422 / 429 (S1, S1 review); subscriber then visible in admin and exported (AS §5). |
| 3.1i | Instagram section | DONE | `home.php:159–193`; "@skyfragrances" band with six tiles in `home--desktop.png`. Tiles are owner-uploaded images from six settings (register §4: `instagram_posts` table cut); **no live Instagram feed**. |

### 3.2 Shop

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.2a | Filters: collection, gender, scent family | DONE | Sidebar with counts in `shop--desktop.png`; `/shop?collection=golden-hour&family=floral` 200 (S1); mobile "Filter & sort" sheet (`shop--mobile.png`). |
| 3.2b | Filter: price range | DONE | Server side as fixed bands (`listing.php:124–129`, `listing_price_bands()`). Exercised over HTTP 2026-09-29 (HO §8b): `/shop` 132 cards → `?price=-5000` 30 → `?price=9000-` 82; `/for-her` renders its own bands (`-6000` …) with counts; a junk value 301s back to `/shop`. The earlier "empty PRICE group" desktop shot was a screenshot artefact, not a rendering bug. |
| 3.2c | Sort | DONE | "Featured" select in `shop--desktop.png`; `price_asc` → 301 to canonical `price-asc` (S2 fix 6). |
| 3.2d | Search | DONE | `/search?q=oud` 200; header search with `/api/search-suggest` (S1, S2 regression 1 fixed). |
| 3.2e | Pagination | DONE | 24 per page (`listing.php:4`); out-of-range page is 404 by 03 §5 (S2 after-review). With 14 products nothing paginates in the screenshots. |

### 3.3 Product page

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.3a | Image gallery with zoom | DONE | Thumbnails + main image + "Hover to zoom" + lightbox (`gallery.php`, `product.js:288`, `views/product.php:230`); `product_azure_oud--desktop.png`. |
| 3.3b | Size selector, each with price / sale price / stock | DONE | 50ml Rs. 8,950 / 100ml Rs. 13,950 tiles, "In stock", SKU (`product_azure_oud--desktop.png`); sale price charged when lower (SF step 3 Silver Lining 3,950); sold-out size 409 (SF step 3). |
| 3.3c | Scent notes pyramid (top/heart/base) | DONE | "The Composition" block (`notes-pyramid.php`; desktop and mobile shots). |
| 3.3d | Longevity & sillage meters | DONE | "10+ hours" / "Strong" meters (`meters.php`; both product shots). |
| 3.3e | Season / occasion | DONE | Autumn · Winter / Evening · Signature chips (`views/product.php:168–185`). |
| 3.3f | Customer reviews, admin-approved only, no fake reviews | DONE | Only `status = approved` rendered (`controllers/product.php:302, 506`); approve/reject flips the PDP (AS §5). The 8 seeded reviews ship **pending** (C-68) and go with the sample data; "Compliments every single time" in the shot is a review approved on the shared dev DB. |
| 3.3g | Related products | DONE | "You May Also Like" rail (`partials/related.php`; both product shots). |
| 3.3h | Sticky add-to-cart on mobile | DONE | `.sticky-bar` with thumb, size and ADD (`views/product.php:205`); visible in `product_azure_oud--mobile.png`. |
| 3.3i | WhatsApp order button | DONE | "Order on WhatsApp" under Add to Cart (both product shots). |

### 3.4 Cart

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.4a | Slide-out cart drawer | DONE\* | `app/partials/cart-drawer.php`, `assets/js/cart.js`; header cart badge shows "1" in the checkout/cart shots. No screenshot of the drawer open; the tour never opened it. |
| 3.4b | Cart page | DONE | `cart--desktop.png` / `cart--mobile.png`: line, stepper, Remove, Order summary. Mobile overflow fixed at 320/375/414 (S2 after-review regression 3). |
| 3.4c | Free-shipping progress bar | DONE | "Free delivery unlocked" state in the cart shots; the progress track + "You're Rs. X away" branch in `views/cart.php:95–98`; threshold arithmetic proven at the API (S2 §4: Rs. 2,450 → ship 250; Rs. 4,900 → free). |
| 3.4d | Coupon codes | DONE | Coupon box in the cart shots; WELCOME10 applied / removed / below-minimum / expired / exhausted / invalid all exact (S2 §4, SF step 3). |

### 3.5 Checkout

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.5a | Guest checkout: name, phone, email (opt), city, address, notes | DONE | `checkout--desktop.png` / `--mobile.png`; postal code added as optional. Orders placed with those fields (S2 §5, SF step 4, HO §7). |
| 3.5b | Cash on Delivery | DONE | COD orders placed in every run (S2 (a), SF step 4, AS §4, HO §7 `SF-260928-PUNF`). |
| 3.5c | Bank Transfer with account details shown, transaction ID + screenshot upload | DONE | S2 (b) and SF step 4: account snapshot stored on the order, reference saved, PNG proof re-encoded to JPEG under `storage/proofs/`, `payment_status = awaiting_verification`, proof URL 403 to the public. |
| 3.5d | JazzCash | DONE | Order `SF-260928-PSZ4` placed through the real checkout with transaction ID, sender name and a proof upload; confirmation says *awaiting verification*; proof streamed to the admin; marked paid → refunded → cancelled with stock restored (`docs/launch/shots-commerce/`, `results.json`). Radio stays hidden while the seeded `REPLACE ME` details are unset — test values were set for the run and restored byte-for-byte. |
| 3.5e | Easypaisa | DONE | Order `SF-260928-PJ2G` placed the same way (test details set, then restored); marked paid → confirmed → cancelled with stock restored (`docs/launch/shots-commerce/`). A second transfer order from the same IP/phone while one is unverified is refused (C-58), which is why the two methods had to be exercised in sequence. |
| 3.5f | Account details editable in admin | DONE | Settings › Payments: 4 bank keys, JazzCash and Easypaisa titles/numbers, per-method enable toggles; "all four off" refused (AS §5); placeholders listed in HO §2. |

### 3.6 Confirmation, tracking, quiz, pages, WhatsApp

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.6a | Order confirmation page | DONE | `/order/{number}?t={token}`; wrong/absent token → `/track` (C-76). The lower-casing 301 hop on every receipt was removed in the working tree (`site/index.php` diff; HO §8) — `shots-fresh-install/evidence.json` shows `303 /checkout → 200 /order/SF-260928-PUNF`. |
| 3.6b | Email to customer and admin | PARTIAL | Templates `app/emails/order-customer.php`, `order-admin.php`, `status-update.php`, `contact-*.php`; outbox drained on admin loads (≤ 2 / 12 s, C-56) and by optional `cron.php`. **No email has ever left the app** — every run had no SMTP, so every "sent" mail is an HTML preview under `storage/logs/mail-preview/` (S1, S2 §5, SF step 8, HO §7). The dev dashboard says "SMTP is not configured — 23 emails cannot be sent" (`admin--desktop.png`). PHPMailer over `smtp.hostinger.com` is untested; installer's "Send test email" exists but was never pressed with a real mailbox. |
| 3.6c | Track order (number + phone) with status timeline | DONE | Pending → Confirmed → Packing → Shipped → Delivered timeline; wrong phone reveals nothing (S2 §5, SF step 4, HO §7 `09b-track.png`); rate limits 10/15 min per IP, 20/24 h per phone. `track--desktop.png` / `--mobile.png`. |
| 3.6d | Scent Finder quiz (4–5 questions → recommends products) | DONE | 5 questions / 19 options / 31 scores seeded; "Question 1 of 5" in `scent_finder--*.png`; result recommends three perfumes (`controllers/quiz.php`). Questions not editable in admin (register §4). |
| 3.6e | About, Contact (form to DB + WhatsApp), FAQ, Shipping, Returns, Privacy, Terms | DONE | All 200 (SF step 2); contact POST → row → admin list → WhatsApp reply link (AS §5); `contact--*.png`, `faq--*.png` (accordion + FAQPage JSON-LD). **`about--*.png` shows "Stage4 heading / Hello world / bad link good link"** — another agent overwrote the About body on the shared dev DB; the seed copy was restored byte-identical after the admin smoke (AS §5), so the shot is not representative of the shipped text. |
| 3.6f | Custom 404 page | DONE | Full-layout 404, `noindex`, path not echoed (S1); `/nope-404` 404 from the ZIP (HO §7). Not in the launch tour shots (S2 tour had it). |
| 3.6g | Floating WhatsApp button on every page | DONE | Round WhatsApp button at the right edge of every `shots-store/*` PNG; `partials/whatsapp-button.php` builds `wa.me/<digits>?text=…`. |

---

## 4. Admin panel (brief lines 43–61)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 4.1 | Secure login: password_hash, session regeneration, CSRF on every form, login rate limiting | DONE | bcrypt cost 12 with rehash on login (`auth.php:305–318`, S1); `session_regenerate()` after login; CSRF on every admin POST (AS reads the token from every form); 5 fails/username → progressive delay, 10 fails/IP → 429 for 15 min, correct password while locked still 429 (AS §1). Sessions UA-bound, 120 min idle (HO §5.7). |
| 4.2 | Fully mobile-friendly | DONE | Six admin pages × 375 px, 0 overflow, 0 errors (AS §6, `shots-admin/*--mobile.png`): card lists, bottom tab bar, Call/WhatsApp buttons per order. Stat-tile and chip-row defects fixed in `3b078b4` (`OBS`). |
| 4.3 | Dashboard: today/month revenue, order counts by status, low-stock alerts, latest orders, best sellers | DONE | All six blocks in `admin--desktop.png` / `--mobile.png`; revenue = SUM of accepted statuses verified against SQL (AS §1); plus "Needs you" list and the install.php / sample-data warnings. |
| 4.4 | Products add/edit/delete; multiple images (auto-resize, JPG/PNG/WEBP only) | DONE | Create → 303 → edit page; 3 uploads → thumb/card/zoom in webp+jpg + OG; SVG-renamed-PNG 422; 12 MB 422; reorder, set primary, delete removes 8 files; hidden → storefront 404; duplicate; hard-delete only with typed DELETE and no orders; soft-delete/restore when orders exist (AS §2; `admin_products_18--*.png`). Fresh-install: product + JPG from the ZIP (HO §7). |
| 4.5 | Sizes with price / sale / stock / SKU; notes; gender; scent family; featured/new; active/hidden; SEO title & description | DONE | All present in `admin_products_18--desktop.png` and exercised in AS §2 (auto SKU `SF-STO-100`, price edit → PDP `Rs. 5,250`, slug rename → 301). |
| 4.6 | Collections CRUD | DONE | Create with image, edit, replace image, reorder, toggle, delete refused while products inside, delete with DELETE (AS §3). Scent families CRUD added beyond the brief (AS §3). |
| 4.7 | Orders list with filters/search | DONE | Status / method / payment / date / proof / per-page filters, quick chips, order-number search → straight to detail (AS §4; `admin_orders--*.png`). |
| 4.8 | Order detail; status Pending → Confirmed → Packing → Shipped → Delivered / Cancelled | DONE | Transition guard (confirmed → delivered refused; shipped needs a courier) and full timeline (AS §4; `admin_orders_SF_260928_PAQ6--*.png`). |
| 4.9 | Courier + tracking number | DONE | `POST …/shipping courier_name=TCS tracking_number=…` (AS §4); shown with Copy in the list. |
| 4.10 | View payment proof | DONE | Route `admin.orders.proof` (`routes-admin.php:36`), streamed only through the admin, public path 403 (S2 (b)). Opened in a recorded run 2026-09-29: full image 200 `image/jpeg` 58 KB, thumbnail `?w=320` 200 `image/jpeg` 2.9 KB (HO §8b). |
| 4.11 | Mark as paid | DONE | Pressed on both the JazzCash and Easypaisa orders 2026-09-29: flash *Payment recorded. The order status is unchanged*, `payment_status` `awaiting_verification` → `paid`; **Mark refunded** then took the JazzCash order to `refunded` (`docs/launch/shots-commerce/`/admin-mark-paid.png, admin-refund-recorded.png). |
| 4.12 | Print invoice / packing slip | DONE | Both 200 with `admin-print.js`; slip shows "Collect Rs. 5,250 — cash on delivery", prices omitted (Q-14) (AS §4, HO §7). |
| 4.13 | Restore stock on cancel | DONE | `stock_restore_order()` (`stock.php:84`) via `order_transition(…,'cancelled')` with the C-12 once-only guard. Exercised from the admin 2026-09-29: three cancels (two from the order page, one via bulk **Cancel unpaid**) each restored Azure Oud 50 ml 23 → 24 and set `stock_restored_at` (`docs/launch/shots-commerce/`/admin-cancel-stock-restored.png). |
| 4.14 | One-click WhatsApp message to customer | DONE | 8 `wa.me` links per order detail, status-specific templates incl. Shipped with courier + tracking (AS §4). |
| 4.15 | Export to CSV | DONE | BOM, CRLF, phone apostrophe, formula guard, filtered and by-ids variants (AS §4). |
| 4.16 | Coupons: percent/fixed, min order, usage limit, expiry | DONE | Create/edit/toggle/duplicate-code refusal; per-phone limit and start date added; storefront applies it (AS §5; `coupon-form.php:57–61`). |
| 4.17 | Reviews moderation | DONE | Approve / reject / bulk pending; rating counters recomputed (AS §5). |
| 4.18 | Contact messages | DONE | List, read state, WhatsApp reply template, order link, replied/archived (AS §5). |
| 4.19 | Newsletter subscribers + CSV export | DONE | List, search, export with BOM, unsubscribe/resubscribe (AS §5). |
| 4.20 | Settings: store info, logo, phone/WhatsApp/email, socials, announcement, hero text, shipping fee + free threshold, delivery time, per-method payment toggles, bank/JazzCash/Easypaisa details, meta description | DONE | Seven tabs (`settings.php:4`); every tab round-tripped unchanged, then shipping fee / threshold / announcement / WhatsApp / method toggles proven on the storefront and restored; validation messages for bad ints and a 200-char meta description (AS §5; `admin_settings--*.png`). |
| 4.21 | Admin account: change password | DONE | Wrong current / short new / change / change back, session kept (AS §5). |

---

## 5. Quality requirements (brief lines 63–73)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 5.1 | Mobile-first, fully responsive, tested at 375 px | DONE | 12 store paths + 6 admin paths at 375 px, 0 horizontal overflow, 0 console/page errors (SF step 6, AS §6, both `report.json`); cart measured at 320 / 375 / 414 / 480 / 768 / 1440 (S2 regression 3). Full-page captures paint fixed elements mid-page (mobile cart mini-bar, listing "Filter & sort" button, admin bottom tab bar and save bars) — a Playwright artefact of `position: fixed`, not checked on a real phone (§9.4). |
| 5.2 | Fast: lazy-load images | DONE | `loading="lazy"` on every non-hero image (`product-card.php:163–166`, `home.php:186`, cart/track thumbs); first card row `eager` + `fetchpriority="high"`. |
| 5.3 | Minimal JS | DONE | 10 vanilla files, no vendor library on `main`; ~59 KB raw deferred per page (stage-2 mobile review). `product.js` still loads on every page (review note, not fixed). |
| 5.4 | Lighthouse score 90+ target | PARTIAL | First measurement 2026-09-29 (`docs/launch/lighthouse.md`), Lighthouse 12 against `php -S`: mobile Performance **76 / 77 / 76** (home / shop / product), desktop home **98**; Accessibility and Best practices 100 everywhere; SEO 69 only because the dev database is `noindex` (100 once indexing is on). The mobile gap is the intro loader delaying LCP to 4.9–5.3 s, as the motion critique predicted; nothing was changed. Target met on desktop, missed on mobile. |
| 5.5 | SEO: unique titles/meta | DONE | Per-product `seo_title` / `seo_description` seeded and editable; page `<title>`s differ (`Order SF-… | Sky Fragrances`, `Sky Fragrances — Luxury Perfumes in Pakistan`, HO §7). |
| 5.6 | Open Graph tags | DONE | `head-meta.php:92–98` (`og:title`, `og:image`, `twitter:card`); per-product OG JPGs generated on upload (AS §2). |
| 5.7 | Product JSON-LD schema | DONE | `Product` + `Offer`/`AggregateOffer` + `Brand` + `AggregateRating`/`Review` + `BreadcrumbList` (`controllers/product.php`); `lowPrice` follows price edits (AS §2). `FAQPage` and `ContactPoint` too. Never validated with Google's Rich Results test. |
| 5.8 | sitemap.xml | DONE | 200 `application/xml`, well-formed, 43 URLs when `site_indexable=1`; deliberately 404 on any non-canonical host (SF step 2). |
| 5.9 | robots.txt | DONE | Real static file with Disallow list and `Sitemap:` line rewritten by the installer (SF step 2, `robots.txt:14`). |
| 5.10 | Clean URLs | DONE | §2.6; `/Shop` → 301 `/shop`, `/index.php/shop` → 301 (S2 after-review). |
| 5.11 | Alt text | DONE | Product alts generated on upload ("Smoke Test Oud — 50ml and 100ml perfume by Sky Fragrances"), editable per image, rendered ×10 on the PDP (AS §2). |
| 5.12 | Security: prepared statements, output escaping, CSRF tokens | DONE | §2.2; `<b>markup</b>` escaped on the PDP, `<script>`/`onerror`/`javascript:` stripped from page HTML (AS §2, §5); every storefront POST gated centrally (`index.php`, S1 review) — 419 without token, 403 foreign Origin (SF step 3). |
| 5.13 | Upload validation | DONE | MIME sniff + GD re-encode, SVG-as-PNG 422, 6 MB cap, 40 MP / memory budget, 413 branch (AS §2, S1 review). Payment proofs images-only (Q-08). |
| 5.14 | Block PHP execution in /uploads | DONE\* | `uploads/.htaccess` (RemoveHandler/RemoveType, FilesMatch deny), asserted present by the installer, plus a live probe on step 4 that could only report "could not verify" from the CLI server (`04-finish.png`). Never exercised on Apache/LiteSpeed. |
| 5.15 | Never expose errors in production | PARTIAL | `display_errors` off for `APP_ENV=production` (`bootstrap.php:133`) and in `.user.ini`; `X-Powered-By` unset (`.htaccess:69–70`); custom error handler logs. Proven only under `php -S` where `.user.ini`/`.htaccess` are ignored — `X-Powered-By: PHP/8.2.28` is still visible locally (S1). `OBS`: Hostinger's CDN **replaces the PHP-sent Content-Security-Policy** with `upgrade-insecure-requests` only, so the CSP the plan specified does not reach browsers on the live host; undecided. |
| 5.16 | Runs on the real host (Hostinger, LiteSpeed, `.htaccess`) | NOT DONE (as a recorded run) | A human deployed the build to Hostinger on 2026-09-28 (`OBS`) and found: CSP stripped by hcdn; hPanel's extractor drops top-level dot-files; the installer key box sat outside its form (fixed `c220904`). **No route table, screenshot, installer report or smoke from that host is in the repository**, and the ZIP layout `OBS` says was adopted (wrapped in `public_html/`) is not what the 23:16 ZIP contains. Every `.htaccess`, `.user.ini` and LiteSpeed behaviour therefore remains read, not exercised, as far as the evidence goes. |
| 5.17 | Stock decremented on order, can't go negative | DONE | Locked transaction, oversell 409 with the 06b wording, stock 3 → 0 then second session refused (SF step 4; S2 (d)/(d′)). Cart clamps to stock with "Only 3 left" (SF step 3). |
| 5.18 | Prices always recalculated server-side | DONE | `price_token` / `price_total` on the form; every total in S2 §4 and SF step 3 recomputed by the API; idempotent re-submit (C-61) returns the same receipt (S2 (c)). |
| 5.19 | Seed 12 perfumes (sky-themed), 5 collections, WELCOME10 10 % above Rs. 3,000 | DONE | 12 `products` inserts (Azure Oud, Cirrus, Aurora Bloom, …), 5 collections, WELCOME10 percent 10 / min 3,000 / 500 uses / 1 per phone (`db/seed.sql:95–179`); counts confirmed on MySQL and MariaDB (SF step 7). Two extra coupons (`EIDSALE500` expired, `FIRST50` exhausted) ship as examples. |
| 5.20 | SQL runs on MySQL 8+ **and** MariaDB 10.4+ | PARTIAL | Proven on MySQL 9.3 (every run) and MariaDB **10.11** with `--show-warnings` silent (SF step 7, HO §7). MariaDB 10.4 itself was never installed or tested; the brief's floor is unproven. |

---

## 6. How to work / deliverables (brief lines 75–85)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 6.1 | Plan first (folder structure, schema, page list), wait for approval | DONE | `docs/plan/PLAN.md` + 14 spec units + `08-decisions-register.md`; committed `753f2b4` before any site code (`a89a0d8`). |
| 6.2 | Build in stages: core + DB → storefront → checkout → admin → polish | DONE (polish is the open stage) | S1 (core), S2 (storefront + checkout), admin build notes + AS (admin). "Polish" is the stage `OBS` feeds into; it has not run. |
| 6.3 | After each stage: run locally, fix errors, desktop + mobile screenshots | DONE | S1 (curl only, no shots — stage-1 pages were placeholders), S2 (50 shots), launch tour (24 + 12 shots), fresh-install (12 shots). |
| 6.4 | Test orders with every payment method | DONE | COD and bank transfer placed repeatedly in earlier runs; JazzCash and Easypaisa placed 2026-09-29 (§3.5d/e, `docs/launch/shots-commerce/`). |
| 6.5 | Test every admin function | PARTIAL | 86 checks, 0 failing (AS), plus on 2026-09-29: proof viewer, mark paid, refund, confirm, cancel with stock restore, bulk cancel-unpaid POST (CANCEL word, 1 cancelled + stock restored), `cron.php` (key 200, wrong key 404). Still never pressed in a recorded run: reject proof, "parcel received back", make HTTPS permanent (needs a live certificate), maintenance mode, remove-sample-data POST with the right word, regenerate-images batch — the last three change shared state on the dev database and were deliberately left. |
| 6.6 | ZIP ready to upload to Hostinger | DONE (with the layout caveat) | `dist/skyfragrances-20260928-1906.zip` 14,062,456 bytes, 565 files = the 1816 build + `app/tools/reset-password.php`; `dist/public_html/` re-unpacked from it with `.htaccess` and `.user.ini` present; manifest check 0 missing / 0 forbidden / 10 `.htaccess`. The flat-vs-wrapped layout question (§9.1) is unchanged. |
| 6.7 | Non-developer guide: database in hPanel, upload, install.php, SSL, go live | PARTIAL | `docs/GO-LIVE-GUIDE.md` (≈950 lines, 12 parts + quick reference); all 22 gaps from `GR` pass 1 and all 16 from pass 2 applied (labels now match the screens; day-one checklist rewritten; SSL / CDN / cron / Search Console / password-refill gaps closed; Part 10 recovery rewritten around the C-64 tool). Still: never trialled by a non-developer, **no hPanel screenshots** (HO §9.4), and Part 4 describes the flat extraction the `OBS` note says loses dot-files. |
| 6.8 | Push-to-deploy (beyond the brief) | DONE\* | `.github/workflows/deploy.yml` (`9728844`, `5544877`): FTP upload on push to `main`; inert until the FTP secrets exist; never run. |

---

## 7. Motion brief — in progress on branch `motion-wip`, not part of the launch build

`docs/motion/00-brief.md` asks for eight concepts. Branch `motion-wip` (one commit `b37bd1f`,
324 files changed, 5,243 insertions over `main`'s parent) parks the interrupted work. Its own commit
message: *"Not yet [tested]: the foundation, story and micro sections finished and self-verified;
hero, cards, quiz, transitions, the integration pass, reviews and fps/Lighthouse checks were cut off
by the usage limit and must be re-run before this merges."* Nothing below was smoke-tested, reviewed,
or measured; nothing below ships in the ZIP.

| Concept (motion brief) | What exists on `motion-wip` | What does not |
|---|---|---|
| (1) Intro loader < 2 s, monogram draws, gold fill, dissolves; full first visit, quick later | **Shipped on `main`** (`assets/js/intro.js`, `partials/intro.php`): 2,186 / 1,954 ms first visit desktop/mobile, 403 / 385 ms quick fade, 1,234 ms reduced-motion calm, hero CTA clickable at 2.6 s (SF step 5). | The "< 2 s" target is missed by ~0.2 s on desktop; the loader is the predicted LCP cost (§5.4). Mist-dissolve is a CSS fade, not particles. |
| (2) Hero scent trails — Three.js light ribbons reacting to mouse/scroll; masked headline | `motion/hero.js` (283 lines), `motion/ribbons-gl.js` (385), `10-hero.css`, six `assets/img/motion/ribbons-*.webp` fallbacks. | Cut off mid-build; no Three.js vendored (a WebGL shader ribbon was written instead); no desktop/mobile check, no fps. |
| (3) Layered depth parallax of ingredients | Nothing beyond the plan (`PLAN.md` §2.8 marks it cross-cutting). | Not started. |
| (4) Pinned fragrance story: bottle rotates while notes reveal | `motion/story.js` (254), `30-story.css`, hooks in `views/product.php`. | "Finished and self-verified" by its author only; no review, no 360 px pass. |
| (5) Collection fanned cards + 3D tilt; mobile snap carousel | `motion/cards.js` (247), `20-cards.css`, `docs/motion/cards.md`. | Cut off; unverified. |
| (6) Signature-scent quiz with card-flip transitions and reveal | `motion/quiz.js` (259), `50-quiz.css`, `docs/motion/quiz.md`, hooks in `quiz.php` / `quiz-result.php`. | Cut off; unverified. |
| (7) Micro: magnetic buttons, custom cursor, link underline, mist-spray add-to-cart + fly-to-cart, price tick-up | `motion/micro.js` (495), `40-micro.css`, `cart.js` hook. | "Finished and self-verified" only. |
| (8) Lenis smooth scroll + mist page transitions | `motion/transitions.js` (257), `60-transitions.css`, `partials/transition-overlay.php`, `vendor/lenis.min.js`. | Cut off; unverified. |
| Foundation: GSAP + ScrollTrigger + Lenis vendored, one config file, device classes, kill switches | `motion/core.js` (526), `motion/config.js` (extended), `vendor/gsap.min.js`, `ScrollTrigger.min.js`, licences, `00-core.css`, assembled `motion.css` (976 lines), `docs/motion/motion-api.md`, `vendoring.md`. | Byte budget (`PLAN.md` §6.1), Lighthouse ≥ 85 mobile method (§6.2), image work (§6.3) and the kill-switch rehearsal (§6.4) never executed. |
| Hard rules: 60 fps, Lighthouse mobile ≥ 85, reduced-motion fallback, 360 px test, animations never block buying, no cart/checkout regression | — | **None measured.** The branch also carries admin order/settings fixes and regenerated collection/OG derivatives that are unrelated to motion and will need separating before a merge. |

The register's rule for this branch (HO §6): merge as a separate release with its own smoke run,
and add the new asset paths to `dev/zip-manifest.php` `required_files`.

---

## 8. What the owner must still supply

Nothing in the build can stand in for these; each one is either a placeholder in the seed, an
empty setting, or a credential only the owner has.

| Item | Where it goes | State today |
|---|---|---|
| **Real bottle photography** (dark ground, 4:5 or larger; ≥ 3 per perfume) | Admin → Products → Photos (auto-crop to 4:5, WebP + JPG derivatives) | Every product image is generated placeholder art; `OBS` calls real photography "the single biggest visual upgrade available and only the owner can supply it". The dev DB also carries blue/multicolour test art from other agents (`shop--desktop.png`). |
| **Hero images** desktop + mobile, three gender-tile images, six Instagram tiles, optional new logo/favicon/OG image | Settings → Home / Store / SEO | Hero slots empty (type-only hero); gender tiles and Instagram fall back to catalogue images. |
| **Bank, JazzCash, Easypaisa account details** | Settings → Payments | Eight `REPLACE ME` values ship in the seed (HO §2); the dashboard warns until none remains; a bank-transfer checkout would show `0000-0000000-000 (REPLACE ME)` today (GR gap 5). |
| **Hostinger MySQL** database name / user / password | Installer screen 2 | — |
| **SMTP**: `orders@skyfragrances.com` mailbox + password (or any mailbox) | Installer screen 2, later `config.php` → `smtp` | Without it no email is sent (HO §5.5); previews pile up in `storage/logs/mail-preview/` and the outbox. Test-send button exists on screen 3. |
| **DNS / domain**: skyfragrances.com attached to the hosting plan, A record pointing at Hostinger, SSL issued, hPanel "Force HTTPS" **off** | hPanel → Websites / Domains / Security → SSL | Guide Part 1 Steps 0–3. `www` → apex handled by `.htaccess`. |
| **SPF + DKIM** records for the domain | hPanel → Emails → DNS | Guide Part 6 Step 2; until set, WhatsApp is the real confirmation channel (Q-19). |
| **Admin username + 12-char password**, WhatsApp number, contact email, store name | Installer screen 3 | No admin account exists until then. |
| Contact phone, business address, business hours, real Instagram/Facebook URLs, TikTok/YouTube | Settings → Contact & Social / Store | Empty or assumed handles (`https://instagram.com/skyfragrances`). |
| Copy review: About, FAQ, Shipping, Returns, Privacy, Terms, announcement, hero, newsletter text | Admin → Pages / Settings | Launch placeholder copy ships (the FAQ promises "dispatched within one working day" and a 48-hour exchange window — confirm both). |
| Real perfume prices, or removal of the 12 samples once ≥ 4 real ones exist | Products / Tools → Remove sample data | Seeded Rs. 3,950–13,950 (Q-03). Home rails hide below 4 live products (HO §5.2). |
| Google Search Console verification; optional analytics tag | Settings → SEO | Not verified; no analytics ships (C-40). |
| Optional: cron on `cron.php?key=<cron_key>` every 15 min | hPanel → Cron jobs; key in `config.php` | Without it emails go out only while an admin page is being used (≤ 2 per load). |
| Optional: GitHub secrets `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_SERVER_DIR` | Repository settings | Push-to-deploy stays inert. |
| Decisions still open from the register: Q-01 random order numbers, Q-02 no tax line on invoices | — | Defaults built in; changing either later is a migration (register §3.1). |

---

## 9. Known limitations and open items

### 9.1 Repository and build state

1. **Uncommitted code in the working tree that the ZIP contains.** `site/index.php` (no lower-casing
   301 for `/order/…`), `site/install.php` (self-addressed reachability probe, `.install-probe` token,
   `0092` prefix, empty username box, SMTP 465 → SSL, screen-4 screenshot reminder),
   `site/app/lib/text.php` (`00` prefix), `site/dev/zip-manifest.php`, `.gitignore`; untracked
   `README.md`, `docs/launch/observations-for-polish.md`, `docs/launch/shots-fresh-install/`, the four
   `admin_orders_SF_260928_PAQ6` / `admin_products_18` PNGs; from the 2026-09-29 gap run also
   `site/app/tools/reset-password.php` (new), `site/admin/views/tools.php`, `site/dev/zip-manifest.php`,
   `docs/launch/lighthouse.md` and `docs/launch/shots-commerce/`. The 1906 ZIP was built from this tree
   (HO §8, §8b), so **the ZIP is ahead of `main`**. Nothing was committed by any launch agent; the owner of
   the repository decides.
2. **ZIP layout vs the hPanel finding.** `OBS` (23:03): hPanel's extractor drops top-level dot-files
   and "the ZIP now wraps everything in `public_html/`". The ZIP written at 23:16 is flat (0 entries
   under `public_html/`); `GO-LIVE-GUIDE.md` Part 4 and `HO` §7 describe and rehearse the flat layout.
   Either the note is stale or the ZIP is wrong for hPanel; if the note is right, extracting this ZIP
   loses `.htaccess` and `.user.ini` and the shop shows Hostinger's 404 on every pretty URL until the
   `dist/htaccess-upload/` files are uploaded and renamed (the guide's fallback box).
3. **`docs/launch/guide-review.md`** describes the `1816` build it reviewed; its pass-3 status note
   records that the guide now names `1906`.
4. **Shared dev database** `skyfragrances_dev`: the `admin` password hash no longer matches the
   documented value (HO §9.1); `rate_limits` rows keyed on the shared `ip_hash` were left in place;
   `admin_users.known_devices` holds four smoke-session hashes; it carries test orders, products,
   collection and page edits from several agents — never reuse it as a seed source (`OBS`).
5. `dist/old-builds/` keeps every superseded ZIP (`0802` … `1816`, the `update-2026-09-28` pack),
   `update-pack/`, `admin-ui-fix/`, `public_html-READY-TO-UPLOAD/` and `public_html 2/`; the guide
   names the ones most likely to be uploaded by mistake and tells the owner to ignore the folder.

### 9.2 Unverified on the target platform

6. No recorded Hostinger run (§5.16). The three `OBS` findings are the only signal from the live
   host; nothing shows the installer's screen 1–4 output, the HTTPS redirect, the uploads block,
   `.user.ini` limits or the CSP on that host.
7. The CSP the plan specified is replaced by Hostinger's CDN (`OBS`); the decision (accept, or move
   `script-src` to a `<meta http-equiv>`) is open.
8. MariaDB proven at 10.11 only; 10.4 never tested (§5.20). Homebrew's MySQL 9 client cannot
   connect to MariaDB — use `mariadb` or phpMyAdmin (SF step 7).
9. No Lighthouse score (§5.4); the intro loader is the predicted LCP cost.
10. No email has ever been delivered (§3.6b); PHPMailer over `smtp.hostinger.com` untested.
11. The guide was never trialled by a non-developer and has no hPanel screenshots (§6.7).
12. Admin actions never pressed in a recorded run (§6.5), including cancel-with-stock-restore and
    mark-as-paid — the two money-relevant ones.
13. JazzCash and Easypaisa orders never placed (§3.5d/e).
14. The price-range filter's UI is unproven and the two desktop listing shots disagree about it (§3.2b).
15. The screenshot tour force-reveals `.sf-reveal` content, so it cannot catch reveal bugs like the one
    fixed in `0f714ea`; `OBS` asks for a no-force pass that has not been added.
16. The acceptance runs drove forms with hand-built curl POSTs; the installer key-box bug was found
    only by a human in a browser (`OBS`). Only the fresh-install rehearsal used real browser form
    submission.

### 9.3 Deliberate scope decisions the owner should know (register §3–4, HO §5)

17. Password recovery is the C-64 File-Manager tool (`app/tools/reset-password.php`, copied to the
    root when needed, nonce-gated, self-deleting); the phpMyAdmin hash paste stays in guide Part 10 as
    the fallback.
18. One admin account, no roles; admin session bound to the browser user-agent; 120 min idle / 12 h.
19. Home rails hide below 4 active products; `/shop` lists everything regardless.
20. `sitemap.xml` is 404 and pages are `noindex` on any host that is not skyfragrances.com.
21. Sub-folder installs refused (C-71); hPanel "Force HTTPS" must stay off; HSTS off (Q-23).
22. `install.php` self-deletes only on the production host; update ZIPs re-ship `install.php`,
    `.htaccess`, `robots.txt` and the sample images (HO §5.12) — delete `install.php` again after
    every update.
23. Orders are never deleted; `delivered` / `cancelled` are terminal, no undo.
24. Cart caps: 10 per size, 20 lines; one outstanding transfer order per phone/IP (C-58); coupon
    guesses 10/h per IP; checkout 10 per 10 min per IP.
25. Payment proofs: images only (no PDF), re-encoded to JPEG, purged 90 days after delivery /
    cancellation (C-65); receipt hides address/phone 30 days after (C-59).
26. Coupons: percent/fixed only, no free-shipping type (Q-12). Quiz questions not editable in admin.
    No back-in-stock capture (C-67). No verified-purchase chip (C-41). No Google Maps or analytics
    (C-40). No Urdu/RTL (Q-21). No admin-entered phone orders (Q-11).
27. Price filter is a fixed band list, not free min/max. Track lookups count every attempt equally
    (06b's 1/5 weighting not built).
28. `.user.ini` sets `session.cookie_secure=1` — correct on https; a plain-http Apache copy cannot
    keep an admin login (HO §5.13).

### 9.4 Cosmetic / minor, seen while reviewing the screenshots

29. Full-page mobile captures paint fixed elements mid-page: the cart mini checkout bar over the
    Order summary heading (`cart--mobile.png`), a second "Filter & sort" button between card rows
    (`shop--mobile.png`, `for_her--mobile.png`), the admin bottom tab bar and the "Save" bars
    (`shots-admin/*--mobile.png`, `admin_settings--desktop.png`, `admin_orders_SF_260928_PAQ6--desktop.png`).
    This is how Playwright renders `position: fixed` in a full-page shot; not checked in a live
    viewport, so not confirmed as a defect either way.
30. `about--*.png` shows another agent's "Stage4 heading / Hello world" body, not the shipped copy
    (§3.6e). Every store shot also carries "STAGE4 ANNOUNCEMENT BANNER TEXT", "Stage4 tagline",
    "Mon-Sat 10am-8pm (stage4)", "owner@skyfragrances.test", the "Smoke Test Oud" and "Stage4 Test
    Perfume Edited" products and a "Stage4 Collection Edited" with 0 fragrances — dev data, not seed.
31. Mobile "View all new arrivals" CTA sits flush under the last card row (S2 §6, not fixed).
32. `build-zip.php` names the ZIP with UTC time (`1816` = 23:16 Karachi) (HO §9.9).
33. `PHP Warning: Module "imagick" is already loaded` on every local run is this machine's
    `php.ini`, not the site.

---

## 10. Evidence index

| Artefact | Path |
|---|---|
| Client brief | `docs/plan/00-brief.md` |
| Decisions register (overrides every spec) | `docs/plan/08-decisions-register.md` |
| Stage 1 run + review fixes | `docs/stage1-run-report.md`, `docs/stage1-review-{hosting,security}.md` |
| Stage 2+3 run + reviews | `docs/stage2-run-report.md`, `docs/stage2-review-{commerce,design,mobile}.md`, `docs/shots/stage2*/` |
| Stage 4 admin build notes | `docs/admin-catalogue.md`, `docs/admin-marketing-screens.md`, `docs/admin-settings-tools.md`, `docs/build/admin-orders.md` |
| Launch smoke, storefront | `docs/launch/storefront-smoke.md`, `docs/launch/shots-store/` (24 PNG + `report.json`) |
| Launch smoke, admin | `docs/launch/admin-smoke.md`, `docs/launch/shots-admin/` (12 PNG + `report.json`) |
| Fresh-install rehearsal from the ZIP | `docs/HANDOVER.md` §7, `docs/launch/shots-fresh-install/` (12 PNG + `evidence.json`) |
| Live-host observations (the only Hostinger signal) | `docs/launch/observations-for-polish.md` |
| Intro loader timing shots | `docs/shots/intro/` |
| Owner guide + its review | `docs/GO-LIVE-GUIDE.md`, `docs/launch/guide-review.md`, `docs/HANDOVER.md` |
| Deployable build | `dist/skyfragrances-20260928-1906.zip`, `dist/public_html/`, dot-file fallback `dist/htaccess-upload/` (superseded builds and the update pack under `dist/old-builds/`) |
| Commerce + admin actions run (JazzCash, Easypaisa, proof, paid, refund, cancel, bulk cancel, cron) | `docs/HANDOVER.md` §8b, `docs/launch/shots-commerce/` (7 PNG + `results.json`) |
| Lighthouse | `docs/launch/lighthouse.md` |
| ZIP manifest / builder | `site/dev/zip-manifest.php`, `dev-tools/build-zip.php` |
| Push-to-deploy | `.github/workflows/deploy.yml` |
| Motion layer (not shipped) | branch `motion-wip` (`b37bd1f`), `docs/motion/` |
