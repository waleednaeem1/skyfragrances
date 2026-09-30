# Sky Fragrances: completion report

Written 2026-10-01, 00:10 Karachi. This replaces the copy from 2026-09-28. That copy was written
before the motion layer merge, the acceptance run and the stage-5 screenshots, so its numbers and
statuses no longer apply.

**What this report covers**

- **The ZIP the owner receives.** `dist/skyfragrances-public_html-folder.zip` is 13,943,403 bytes
  and holds 595 files. It was built on 2026-09-29 at 11:41 from `main` at `49cd8f1` plus the
  uncommitted acceptance-run fixes. The build is still current: all nine files the acceptance run
  changed are byte-identical in `dist/public_html/`, and nothing under `site/` is newer than the
  ZIP.
- **The live site.** https://skyfragrances.com runs `49cd8f1`, which is what was pushed. The
  acceptance fixes are not on it (see §9, item 2). I checked the live site read-only on
  2026-10-01 between 00:00 and 00:10: `curl` for status codes and headers, plus five Lighthouse
  runs.
- **No code was changed and nothing was committed.**
- **Update, 2026-10-01 00:25 Karachi (handover-gap run, HO §8d, AR §15).** Two code fixes:
  `app/lib/response.php` (CSP `img-src` now allows `blob:`, so the proof preview renders) and
  `assets/css/critical.css` (long breadcrumbs end in "…" instead of being cut mid-word). The ZIP
  was rebuilt: `dist/skyfragrances-public_html-folder.zip` is now **13,943,403 bytes**, 595 files,
  the same list as before, only those two files differ. The guide-review pass 5 findings were
  applied to `docs/GO-LIVE-GUIDE.md` and `docs/HANDOVER.md`. Rows 1.5, 2.5, 3.5c, 6.6 and 6.7 and
  §9.2 items 7–8 below are updated; the counts do not change. Still nothing committed.

**Sources I read in full**

- `docs/plan/00-brief.md`
- `docs/acceptance-report.md` (97 checklist items, run on 2026-09-29)
- `docs/plan/08-decisions-register.md` (§3 open questions, §4 scope, §5 acceptance-run rulings)
- `docs/HANDOVER.md` (§5 limitations, §7b rehearsal of the ZIP, §9 open items)
- `docs/lighthouse/summary.md`, `docs/lighthouse/fresh-install-zip/summary.md` and
  `docs/perf/critical-css.md`
- the headings and findings of `docs/guide-review.md`. Pass 5 of that file was being written by
  another session while I worked (its timestamp is 2026-10-01 00:08), so I quote only what it
  said at that time.
- the motion documents: `docs/motion/00-brief.md`, `run-report.md`, `technical-fixes.md` and the
  headings of `review-a11y.md`
- `docs/a11y-audit.md` and `docs/security-final.md` §10
- `docs/launch/observations-for-polish.md`

**Screenshots I opened**

- All 24 storefront PNGs in `docs/shots/stage5/`. Each was cut into full-width strips, and I read
  every strip covering the top of each page.
- All 33 screenshots in `docs/shots/stage5/admin/` (replaced on 2026-10-01, see §10 finding 1).
- Three screenshots from other runs, where stage 5 had no usable shot:
  `docs/launch/shots-fresh-install-zip/26-admin-order-transfer-detail.png`,
  `docs/launch/shots-admin/admin_orders_SF_260928_PAQ6--mobile.png` and
  `docs/launch/shots-admin/admin--mobile.png`.

## Status vocabulary

| Mark | Meaning |
|---|---|
| **DONE** | Built and proven by a recorded run, a screenshot or a live probe. The evidence is named. |
| **PARTIAL** | Part of the requirement is built and proven, and part is not. The missing part is named. It is a PARTIAL even when the gap is "never proven" rather than "known broken". |
| **NOT DONE** | Not built, or built but failing the brief. |

**How evidence is cited**

- `AR §n`: `docs/acceptance-report.md`
- `HO`: `docs/HANDOVER.md`
- `GR5 #n`: finding n in `docs/guide-review.md` pass 5
- `LH`: `docs/lighthouse/summary.md`
- `LIVE`: my probes of skyfragrances.com on 2026-10-01
- File names ending in `.png` are in `docs/shots/stage5/` unless another folder is named.
- Code paths are relative to `site/`.

---

## 0. Summary

**Counts.** The brief breaks down into 82 requirement rows in §1–§6: **74 DONE, 8 PARTIAL,
0 NOT DONE.**

**The nine PARTIAL rows**

| Row | Requirement | What is missing |
|---|---|---|
| 1.5 | "Better than Le Labo / Byredo" | Every product image is generated placeholder bottle art. No real photography exists yet. (The mobile breadcrumb cut is fixed; the hero plate's straight edge is still unconfirmed.) |
| 2.4 | One CSS file | The storefront loads 2 or 3 stylesheets and the admin loads 2. |
| 2.5 | Works by upload through hPanel File Manager | No recorded run of a File-Manager upload on Hostinger. (The guide's checklist no longer causes the nested-folder mistake.) |
| 3.6b | Emails to the customer and the admin | No email has been delivered in any run. They have only been queued or rendered. |
| 4.9 | Settings | Logo and image uploads and the social links were never exercised. |
| 5.9 | MySQL 8 and MariaDB 10.4+ | Proven only on MySQL 9.3 and MariaDB 10.11. |
| 6.5 | Test every admin function | Same gap as 4.9. |
| 6.7 | Non-developer guide | Pass 5's 4 BLOCKER and 10 RISK items are now applied, including Part 0 for the already-live shop. It was never tried by a non-developer and has no hPanel screenshots. |

**Things the owner must know now**

1. **The shop is already live, public and indexable, and it still shows the sample catalogue.**
   - `robots` says `index,follow` and `/sitemap.xml` lists 40 addresses.
   - The 12 sample perfumes can be put in the cart and ordered.
   - The go-live guide assumes an empty hosting account. Its Parts 1–5 would take the live shop
     offline (GR5 #1).
   - Do the guide's Part 0 and the **LIVE** lines of its day-one checklist (`docs/GO-LIVE-GUIDE.md`,
     top) before anything else:
     hide the products, turn indexing off and fix the payment details.
2. **Lighthouse on the live host meets the 90+ target:** 99 / 99 / 99 on `/`, `/shop` and
   `/product/azure-oud` (mobile, simulated 4G, motion layer on). `/` and the product page also
   score 100 for accessibility, best practices and SEO. Details in §7.
3. **The acceptance run's fixes are in the ZIP but not on the live site.** Examples: the COD
   fallback, the red dashboard banner for placeholder payment details, and the 3-day cart cookie.
   The live sitemap still lists `/track`, which fix 5 removed. Because a push to `main`
   auto-deploys (`.github/workflows/deploy.yml`), they reach the live site only when the owner
   approves a commit and a push.
4. **Two of the acceptance report's claims rested on screenshots that did not show what it says (resolved 2026-10-01, see 4.2).**
   Its §12 says the stage-5 tour captured 15 admin screens at 375 px with 0 overflow (the §5 last
   row and §8 rely on this). In fact all 14 desktop PNGs are byte-identical, and so are all 14
   mobile PNGs: every one is the **admin login page** (§10, finding 1). The admin works (the ZIP
   rehearsal shows it at 1280 px, and the 28 Sep tour shows it at 375 px). But no screenshot of
   the final build proves the admin on a phone.

---

## 1. Brand (brief lines 3–11)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 1.1 | Name Sky Fragrances, domain skyfragrances.com | DONE | LIVE: `https://skyfragrances.com/` 200, `/robots.txt` 200; the header monogram and footer lockup are in every storefront shot (`home--desktop.png`, `contact--desktop.png`). |
| 1.2 | Tagline "More Than Just A Scent" | DONE | It is the hero `<h1>` in `home--desktop.png` and `home--mobile.png`, and the footer line under the logo in every page shot. |
| 1.3 | Logo: champagne-gold "SF" monogram plus "SKY FRAGRANCES" on black | DONE | Monogram in the header of every shot; full lockup on the admin login (`admin/admin--mobile.png`) and in the footer; files are in `assets/img/`. |
| 1.4 | Ultra-luxury minimal look: #0A0A0A, champagne gold, ivory, Cormorant Garamond headings, Jost body, lots of whitespace | DONE | Self-hosted variable fonts in `assets/fonts/`. Serif headings, gold rules, gold-gradient buttons and the single ivory "Why choose us" band (register Q-22) are in `home--desktop.png`, `product_azure_oud--desktop.png` and `faq--mobile.png`. |
| 1.5 | "Must look better than top perfume brands (Le Labo, Byredo, J. and Scentsation)" | PARTIAL | The layout, type and motion are consistent and hold up at both sizes in every stage-5 shot. But **every product, collection and Instagram image is generated placeholder art**: the same outlined bottle on a coloured glow (`shop--desktop.png`, `home--desktop.png`). No brand at that level sells with placeholder art, and only the owner's photography closes the gap (`docs/launch/observations-for-polish.md`). Two visual flaws are visible in the shots: (a) the hero's ribbon plate ends in a straight horizontal edge across the subheading at both widths (`home--mobile.png`, and the same in `docs/shots/live/home--mobile.png`), which no review has confirmed or ruled out on a real device; (b) mobile breadcrumbs were cut mid-word with no ellipsis ("AZURE OU" in `product_azure_oud--mobile.png`, "FREQUENTLY ASKED QUE" in `faq--mobile.png`) — **fixed 2026-10-01** in `assets/css/critical.css`: they now end in "…" ("AZURE …", "FREQUENTLY ASKED Q…"; `docs/launch/shots-breadcrumb/`, AR §15). The row stays PARTIAL for the placeholder art and the unconfirmed hero edge. |
| 1.6 | Subtle animations (fade/slide on scroll, hover zoom on products) | DONE | `assets/js/reveal.js` and the motion layer (`assets/js/motion/**`, `assets/css/motion.css`); card hover and gallery zoom (`app/partials/product-card.php`, `app/partials/gallery.php`). With reduced motion nothing animates: 0 running animations, no intro, Lenis off (AR §10). |
| 1.7 | Pakistan, PKR shown as "Rs. 4,950", Asia/Karachi | DONE | Every price in the shots uses that format ("From Rs. 3,950 ~~Rs. 4,950~~" in `shop--desktop.png`); `money()`; `/track` and the admin show dates in Karachi time (AR §2, §4). |

## 2. Hosting and tech (brief lines 13–19)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 2.1 | Hostinger shared hosting: PHP 8.x and MySQL only; no Node, no Composer, no build step | DONE | No `composer.json`, `package.json` or `node_modules` under `site/`. PHPMailer is vendored by hand. The ZIP has no `dev/` (HO §7b). LIVE: the site is served by Hostinger (`platform: hostinger`). `motion.css` is made by `cat motion/*.css` on the developer's machine and ships already assembled, so the owner never runs it. |
| 2.2 | Plain PHP with PDO, prepared statements everywhere | DONE | `app/lib/db.php`. The last two `{$column}` interpolations were rewritten as literal statements (AR §11 fix 9); source scans are in `docs/security-final.md` §6. `'; DROP TABLE products; --` in search, coupon, contact and track did nothing (AR §9). |
| 2.3 | Vanilla JS | DONE | No framework. The client's own motion brief (`docs/motion/00-brief.md`, 2026-09-26) later asked for GSAP + ScrollTrigger and Lenis. These are vendored in `assets/js/vendor/` with licences, and phones never load them (HO §6). **Correction to the task text: there is no Three.js in the tree.** The hero ribbons are a hand-written WebGL module (`assets/js/motion/ribbons-gl.js`, 13.6 KB), as decided in `docs/motion/PLAN.md` §8 row 1. |
| 2.4 | One CSS file | PARTIAL | The storefront loads `assets/css/critical.css` (blocking) plus `site.css` (deferred) on every page, and `motion.css` as well on home, listing, collections, product and quiz. The admin loads `admin.css` plus the shared foundation sheet (commit `78efea4`). The critical/site split is a register ruling (08 §5, acceptance run) that bought Lighthouse points; `motion.css` came with the client's motion brief. There is still no build step and all the CSS is hand-written. But "one CSS file" is literally not what ships: it is 4 files. |
| 2.5 | Must work by uploading through hPanel File Manager and importing the DB | PARTIAL | **Proven locally:** the ZIP was unzipped, installed through `install.php` and walked end to end (HO §7b, 27 screenshots in `docs/launch/shots-fresh-install-zip/`); `db/schema.sql` + `db/seed.sql` load cleanly on MariaDB 10.11 (AR §13). **Not proven:** (a) no File-Manager upload and extract on Hostinger is recorded, although the live site proves an install happened there somehow; (b) the phpMyAdmin import itself (AR §1, LIVE HOST); (c) ~~the guide's printed day-one checklist still says "Extract → destination exactly `/public_html`"~~ — fixed 2026-10-01: the checklist and Part 4 now extract in `domains/skyfragrances.com` (path must end there) and check for `.htaccess`, `.user.ini` and no inner `public_html` (GR5 #5 applied). (a) and (b) remain. |
| 2.6 | `config.php` for DB credentials | DONE | Written by the installer with mode 0400 and never rewritten; `config.sample.php` ships; `config.php` is not in the ZIP (HO §7b). LIVE: `/config.php` returns 404. |
| 2.7 | One-time `install.php` creates the tables, the admin account and sample data, and tells the owner to delete it | DONE | 27 schema + 236 seed statements, admin created, a re-run is refused, a red "Delete install.php now" panel with a delete button off production, and it deletes itself on the real domain (AR §1; HO §7b `04-finish.png`, `30-install-refused.png`, `31-install-deleted.png`). LIVE: `/install.php` returns 404. |
| 2.8 | Pretty URLs through `.htaccess` (`/product/azure-oud`, `/shop`, `/cart`) | DONE | LIVE: `/shop`, `/product/azure-oud`, `/cart`, `/collections`, `/for-him`, `/scent-finder` and `/track` all return 200. Trailing slash and case variants 301 (AR §1). |
| 2.9 | Force HTTPS | DONE | LIVE: `http://skyfragrances.com/` → 301 to https; `http://…/admin/login` → 301 to `https://…/admin/login` before any form is sent; `www` → 301 to apex. **Caveat:** on the live host it is Hostinger's edge (`platform: hostinger`) that sends these 301s, not the site's own `.htaccess` rule. HSTS is deliberately off (Q-23). |

## 3. Storefront (brief lines 21–41)

### 3.1 Home

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.1a | Full-screen hero with logo, tagline and CTA | DONE | `home--desktop.png`: monogram, eyebrow, "More Than Just A Scent", "Explore the collections" and "Take the scent finder", with the trust line inside the first 900 px. `home--mobile.png` is the same at 375 px. See 1.5 for the plate edge. The hero image slots stay empty until the owner uploads one. |
| 3.1b | Announcement bar | DONE | "Free delivery on orders above Rs. 3,000 — cash on delivery nationwide" in every shot and live. It is editable in Settings, and a change showed on the next request (AR §5). |
| 3.1c | Shop by collection | DONE | A mosaic of five tiles (Dawn Chorus, Azure Heights, Golden Hour, Midnight Meridian, Monsoon Veil) in `home--desktop.png`. |
| 3.1d | Best sellers | DONE | The "Loved most / Best Sellers" rail of 8 in `home--desktop.png`. It is ordered by sales, so it only means something once real orders exist. |
| 3.1e | New arrivals | DONE | The "Just landed / New Arrivals" rail with NEW and SALE badges in `home--desktop.png`. |
| 3.1f | For Him / For Her / Unisex | DONE | Three tiles ("For Him, For Her, For Everyone") in `home--desktop.png` and `home--mobile.png`. LIVE: `/for-him`, `/for-her` and `/unisex` all return 200. |
| 3.1g | Why choose us (long-lasting, COD nationwide, fast delivery, easy exchange) | DONE | The ivory band with exactly those four promises in `home--desktop.png`. The delivery time comes from Settings. |
| 3.1h | Newsletter signup | DONE | "Join the Sky List" on home and in the footer. `POST /api/newsletter` stores a row, a duplicate stores nothing new, and the list appears in admin and the CSV (AR §5). |
| 3.1i | Instagram section | DONE | "@skyfragrances" band with six tiles and a Follow link (`home--desktop.png`). The tiles are six owner-set image + link settings (register §4). **There is no live Instagram feed**, and today the tiles show the placeholder bottles. |

### 3.2 Shop

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.2a | Filters: collection, gender, scent family | DONE | Sidebar with counts in `shop--desktop.png`; "Filter & sort" drawer on mobile (`shop--mobile.png`; AR §8 keyboard and focus trap). |
| 3.2b | Filter: price range | DONE | Four computed bands (`listing_price_bands()` in `app/controllers/listing.php`), exercised over HTTP (HO §8b). LIVE: the `/shop` HTML has the Price and Availability groups. In `shop--desktop.png` both groups sit below the sidebar's own scroll, so the shot does not show them. |
| 3.2c | Sort | DONE | "Featured" select in `shop--desktop.png`; old sort keys 301 to the canonical ones (stage-2 run report). |
| 3.2d | Search | DONE | Header search with suggestions; the injection string is echoed escaped (AR §9); searches are included in the SEO crawl (AR §9). |
| 3.2e | Pagination | DONE | 24 per page. `/shop?page=2` is a 404 when there is no page 2 (`docs/stage2-run-report.md` line 218). With 12 products no shot shows a second page. |

### 3.3 Product page

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.3a | Image gallery with zoom | DONE | Thumbnails, main image and lightbox in `product_azure_oud--desktop.png`; swipe dots on mobile; the lightbox keyboard test is in `docs/a11y-audit.md`. |
| 3.3b | Size selector; each size has its own price, sale price and stock | DONE | 50 ml Rs. 8,950 and 100 ml Rs. 13,950 tiles with SKU and stock line (`product_azure_oud--desktop.png`). A sold-out size cannot be added (409), and the sale price is what gets charged (AR §2, §4). **The stage-5 PDP shots show 50 ml selected and "Sold out"**: they were taken at 11:11, before AR §11 fix 6 (11:22) made the default fall back to an in-stock size. No shot of the fixed state exists; the fix was proven over HTTP only (AR §10). |
| 3.3c | Scent notes pyramid (top, heart, base) | DONE | "Inside the bottle / The Composition" with Top, Heart and Base tiers (`product_azure_oud--desktop.png`, mobile likewise). |
| 3.3d | Longevity and sillage meters | DONE | "Longevity 10+ hours" and "Sillage Strong" meters (`product_azure_oud--desktop.png`). |
| 3.3e | Season and occasion | DONE | "Best season: Autumn, Winter" and "Occasion: Evening, Signature" chips (same shot). |
| 3.3f | Customer reviews: admin-approved only, no fake reviews | DONE | Only approved reviews render. Approving a 2★ changed the average and `aggregateRating`; a rejected review never appeared (AR §5). The 8 seeded reviews ship unapproved and are removed with the sample data (C-68, AR §7). `product_azure_oud--desktop.png` shows "No reviews yet" and the write-a-review form ("Reviews are checked by our team before they appear"). |
| 3.3g | Related products | DONE | `app/partials/related.php`, a "You may also like" rail. Its `srcset` was fixed so it no longer pulls the 1400 px image (LH "What changed" 2). |
| 3.3h | Sticky add-to-cart on mobile | DONE | Probe: the bar shows only once the buy box has scrolled away, the WhatsApp button moves above it, and the body gains 68 px of padding (AR §8). In the full-page `product_azure_oud--mobile.png` the bar is drawn mid-page; that is how full-page capture draws a fixed element, not a layout bug. |
| 3.3i | WhatsApp order button | DONE | "Ask on WhatsApp" under the buy button (`product_azure_oud--desktop.png`). The prefilled text starts "Assalam-o-Alaikum." (AR §11 fix 8). |

### 3.4 Cart

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.4a | Slide-out cart drawer | DONE | Full-height drawer with internal scroll; it closes on backdrop tap and Esc and focus returns to the cart button (AR §8). Removing the last item empties it cleanly (AR §3). No stage-5 shot shows it open. |
| 3.4b | Cart page | DONE | `cart--mobile.png` and `cart--desktop.png`: line, quantity stepper, Remove, order summary. |
| 3.4c | Free-shipping progress bar | DONE | Exact at Rs. 2,999 (shipping 250, bar showing) and at Rs. 3,000 ("You've unlocked free delivery"); the threshold is tested before discount (C-43, AR §3). `cart--mobile.png` shows the free state. |
| 3.4d | Coupon codes | DONE | WELCOME10 below and above the Rs. 3,000 minimum, expired and used-up codes, lower case with leading spaces, the never-negative total, and a lock-out after 10 wrong codes (AR §3). |

### 3.5 Checkout

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.5a | Guest checkout: name, phone, email (optional), city, address, notes | DONE | No account needed (`checkout--mobile.png`, `checkout--desktop.png`). Phone uses `inputmode=tel` and every input is at least 16 px, so iOS does not zoom (AR §8). |
| 3.5b | Cash on Delivery | DONE | `SF-260929-GQ29`, and a COD order placed with JavaScript off at 375 px (AR §2, §10). |
| 3.5c | Bank Transfer / JazzCash / Easypaisa: show the account details (editable in admin), customer enters a transaction ID and uploads a screenshot | DONE | `SF-260929-6SDM` (bank), `SF-260929-R4K5` (JazzCash) and `SF-260929-QK4J` (Easypaisa). The checkout shows the account lines; a PDF or a 12 MB file is refused in plain words; a method still on `REPLACE ME` is hidden (AR §2, §6). All four radios are in `checkout--mobile.png`. The little preview of the chosen screenshot used to be blocked by the CSP (`img-src 'self' data:` against the `blob:` URL in `assets/js/forms.js:271`); **fixed 2026-10-01** — `app/lib/response.php:107` now allows `blob:`, and at 375 and 1440 px the preview renders with 0 console errors and 0 CSP violations (AR §15, `docs/launch/shots-proof-preview/`, HO §8d). |

### 3.6 Confirmation and emails

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.6a | Order confirmation page | DONE | 200 for all four methods, with the number, the totals and, for transfers, the account lines (AR §2); `docs/launch/shots-fresh-install-zip/11-confirmation-bank.png` and `11-confirmation-cod.png`. |
| 3.6b | Email to the customer and the admin | PARTIAL | **Built:** an outbox that drains on admin page loads; a customer email with the item table, total and tracking link; an admin email with a link to the order (AR §2). **Never delivered:** no run had SMTP. Every email ended as `queued` in `email_outbox` (28 admin order emails, 8 status updates) or as a preview under `storage/logs/mail-preview/`. Whether the live site has SMTP configured is not recorded anywhere I read. |

### 3.7–3.10 Other pages

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3.7 | Track order (number + phone) with a status timeline | DONE | A right phone in any format shows the timeline; a wrong phone and an unknown number give the same message and reveal nothing (AR §2); `track--mobile.png`; `docs/launch/shots-fresh-install-zip/12-track-shipped.png`. |
| 3.8 | Scent Finder quiz (4–5 questions → recommendations) | DONE | 5 questions, "Question 1 of 5" (`scent_finder--mobile.png`); `/scent-finder/result?a=2-2-2-2-2` returns 3 matches with JavaScript off (AR §8). |
| 3.9a | About | DONE | `about--desktop.png` and `about--mobile.png`; LIVE `/about` 200. |
| 3.9b | Contact: form saved to the DB, plus WhatsApp | DONE | `contact--desktop.png` (a "Chat on WhatsApp" button and the form). Messages are stored and listed in admin with a WhatsApp reply link; the auto-reply leaves out the message body (AR §5, §9). |
| 3.9c | FAQ | DONE | Accordion (`faq--mobile.png`); `FAQPage` JSON-LD is built from the page body (register §4). |
| 3.9d | Shipping, Returns & Exchange, Privacy Policy, Terms | DONE | LIVE: `/shipping`, `/returns`, `/privacy` and `/terms` all return 200; included in the 40-page SEO crawl (AR §9). The copy is seeded launch text, so the owner should read the legal pages (§8). |
| 3.9e | Custom 404 page | DONE | LIVE: `/this-does-not-exist` returns 404 with title "Page Not Found \| Sky Fragrances". Denied paths also render the branded 404 (AR §1). |
| 3.10 | Floating WhatsApp button on every page | DONE | Present in all 24 storefront shots. It moves above the sticky bar on the product page (AR §8). LIVE link: `wa.me/923203271071`. |

## 4. Admin panel (brief lines 43–64)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 4.1 | Secure login: `password_hash`, session regeneration, CSRF on every form, login rate limiting | DONE | The session id changes at login; logout destroys it; replaying the old id goes back to login; admin pages send `Cache-Control: no-store` (AR §5; LIVE header on `/admin/login`). A POST with no token is refused (AR §9). Wrong passwords: from the 6th on the same username each attempt is slowed by about 2 s; after 10 from one IP that IP gets 429. The error message never reveals whether the username exists (C-51, AR §5, §9). LIVE: `/admin/login` sends `X-Frame-Options: DENY`. |
| 4.2 | Fully mobile-friendly, so orders can be managed from a phone | DONE | **Final build, 2026-10-01:** a Playwright sweep of 33 admin screens (dashboard, orders list and detail, invoice, packing slip, products, collections, coupons, reviews, messages, subscribers, pages, scent families, all seven settings tabs, tools, activity, password) at 375 px (touch) and 1440 px: all 200, 0 horizontal overflow, 0 console or network errors, no bounce to login, no collapsed checkbox. Screenshots of every screen at 375 px: `docs/shots/stage5/admin/` (33 distinct images). Tap targets measured on 18 of those screens at 375 px: 560 of 560 controls are at least 44 px tall on touch screens (prose links and the off-screen skip link excluded; row cards count as one stretched target). The sweep found and fixed the invoice and packing slip overflowing a phone by 44 px and 10 px. |
| 4.3 | Dashboard: revenue today and this month, order counts by status, low-stock alerts, latest orders, best sellers | DONE | The figures matched a hand count in SQL (Rs. 187,950 / 3 orders today, pending 20, confirmed 3, cancelled 2, best seller Azure Oud 21). The low-stock alert names Eclipse Velvet 100 ml (sold out) and Saffron Zenith 50 ml (3 left) (AR §4, §5). `docs/launch/shots-fresh-install-zip/20-admin-dashboard.png`. |
| 4.4 | Products: add, edit, delete; multiple images with auto-resize (JPG/PNG/WEBP only); sizes with price, sale price, stock and SKU; notes; gender; scent family; featured and new toggles; active/hidden; SEO title and description | DONE | Created with two sizes, two uploads resized into thumb/card/zoom in WebP and JPEG; the active, new and featured switches each changed the storefront; delete removed the rows and the files; a rename writes a slug redirect and the old URL 301s; empty alt text is refused (AR §5). SEO title and description were posted and stored (`docs/launch/admin-smoke.md` line 23). A file with `<?php` inside is re-encoded and never executes (AR §9). |
| 4.5 | Collections CRUD | DONE | Create with an image, reorder with `sort_order`, delete (AR §5). |
| 4.6 | Orders: list with filters and search; detail; status chain Pending → Confirmed → Packing → Shipped → Delivered / Cancelled; courier + tracking; view payment proof; mark paid; print invoice and packing slip; restore stock on cancel; one-click WhatsApp; CSV export | DONE | Every part is covered in AR §4–§5: the filters, search that jumps straight to an order, the full chain, courier TCS with tracking, the proof streamed only to a logged-in admin, mark paid with `paid_at`, invoice and a price-free packing slip, stock restored exactly once, "Parcel received back", 8 `wa.me` links per order, and a CSV with a BOM and `=` formulas neutralised. Order-detail screenshots from the final ZIP: `docs/launch/shots-fresh-install-zip/22`, `25`, `26`, `27`, `28-*.png`. |
| 4.7 | Coupons: percent or fixed, minimum order, usage limit, expiry | DONE | `ACCEPT15` created, redeemed on `SF-260929-8T5H`, disabled and edited; FIRST50 stays "Used up" (AR §3, §5). |
| 4.8 | Review moderation, contact messages, newsletter subscribers with CSV export | DONE | Approve and reject change the product page; messages list and detail with "mark replied" and a WhatsApp reply; the subscriber list and CSV; the unsubscribe token works (AR §5). **Note:** the site never sends a newsletter itself. The owner exports the list to a mailing tool. |
| 4.9 | Settings: store info, logo, phone/WhatsApp/email, social links, announcement text, hero text, shipping fee and free-shipping threshold, delivery time, turn each payment method on or off, bank/JazzCash/Easypaisa details, meta description | PARTIAL | **Exercised:** the announcement, hero text and subheading, shipping fee and threshold, and delivery time all show on the next request; switching each of the four methods off removes exactly that one; the payment details (AR §5, §6); the WhatsApp number (normalised to `+92…`); meta-description length validation; "No changes to save" on all 7 tabs (`docs/launch/admin-smoke.md` line 89). **Never exercised in any recorded run:** uploading an image setting (logo, favicon, OG image, hero images, the six Instagram tiles), whose code is `settings_image_store()` in `admin/controllers/settings.php:210`; saving the social links (Instagram, Facebook, TikTok, YouTube); and saving a new meta description, as opposed to having a too-long one refused. |
| 4.10 | Admin account: change password | DONE | The old password stops working and the new one works (AR §5). Recovery without email: `app/tools/reset-password.php` (C-64, HO §1). |

## 5. Quality requirements (brief lines 66–78 and 90–92)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 5.1 | Mobile-first, fully responsive, tested at 375 px | DONE | Storefront: 12 paths at 375 px with no horizontal overflow and no console or network errors (`docs/shots/stage5/report.json`). `dev-tools/a11y.mjs`: tap targets of at least 44 px on 12 pages with 0 failures; the 8 px spacing rule was not measured (AR §8). For the admin, see 4.2. |
| 5.2 | Fast: lazy-loaded images | DONE | Every `<img>` on `/`, `/shop`, `/collections`, `/cart` and the product page has width, height and alt; below-the-fold images are lazy; the LCP image is `loading=eager fetchpriority=high` (AR §10). |
| 5.3 | Fast: minimal JS | DONE | Phones load no GSAP, Lenis or WebGL (HO §6). The site's own scripts are 0.3–6.2 KB gzipped each. Desktop adds GSAP 72 KB, ScrollTrigger 44 KB and Lenis 19 KB (minified, before gzip), which the client asked for in the motion brief. TBT is 0 ms on every Lighthouse run (§7). |
| 5.4 | Lighthouse 90+ | DONE | **Live host, 2026-10-01:** Performance 99 on `/`, `/shop` and `/product/azure-oud`. On `/` and the product page: Accessibility 100, Best Practices 100, SEO 100. Locally, on the production-equivalent server, 96–98 (LH), and 98 / 98 / 98 for the ZIP as installed. All with the motion layer on. Method and caveats in §7. |
| 5.5 | SEO: unique titles and meta, Open Graph, Product JSON-LD, `sitemap.xml`, `robots.txt`, clean URLs, alt text | DONE | 40 pages crawled: titles 25–54 characters, descriptions 72–151, no duplicates; canonical and OG on every page; `AggregateOffer` in PKR matching the page price; `aggregateRating` only once a review is approved; the sitemap is valid XML with exactly the catalogue; `robots.txt` points at it (AR §9). LIVE: SEO 100. **Not done:** Google's Rich Results Test and a real WhatsApp link preview. Both can now be run against the live URL. |
| 5.6 | Security: prepared statements, output escaping, CSRF tokens, upload validation, no PHP execution in `/uploads`, errors never shown in production | DONE | AR §9: `<script>` renders as text everywhere; a POST without a token is refused; uploads are checked by magic bytes and re-encoded; `/uploads/*.php` is denied; production mode shows a branded 500 page with a reference id and no path, SQL or trace. **Live-host caveats, all in §9:** Hostinger's CDN replaces the site's Content-Security-Policy with `upgrade-insecure-requests` (LIVE header), and the PHP block in `uploads/` has never been tested on LiteSpeed with a real `.php` file. |
| 5.7 | Stock decremented on order and never negative; prices always recalculated on the server | DONE | 10 runs of two parallel checkouts for the last unit: every time exactly one succeeds and stock never drops below 0. A `stock − 5` on a row holding 3 is refused by the database. A tampered `price_total` is refused with "Your new total is Rs. 8,950 (was Rs. 1)". A price change between page load and Place Order is caught (AR §2–§4). |
| 5.8 | Seed: 12 sky-themed perfumes, 5 collections, WELCOME10 (10% off above Rs. 3,000) | DONE | 12 products / 24 sizes / 5 collections / 3 coupons on both MySQL and MariaDB; WELCOME10 is refused at Rs. 2,999 and takes exactly 10% at Rs. 3,000 and up (AR §1, §3). The seed adds two limits the brief does not mention: one use per phone and a one-year expiry (Q-25). |
| 5.9 | Every SQL statement runs on MySQL 8+ and MariaDB 10.4+ | PARTIAL | Proven on **MySQL 9.3** (the installer) and **MariaDB 10.11** (strict `sql_mode`, 0 errors, 0 warnings, and a second import changes nothing) (AR §1, §13). **Never run on MySQL 8.0 or MariaDB 10.4**, the two floors the brief names. The live install worked on whatever Hostinger runs, which no document records. |

## 6. How to work and deliverables (brief lines 80–88)

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 6.1 | Show the folder structure, schema and page list as a plan first, and wait for approval | DONE | `docs/plan/PLAN.md` plus the 01–08 specs. **Note:** line 3 of `PLAN.md` still reads "Status: awaiting client approval — no code has been written". The approval itself is not recorded in the repository. |
| 6.2 | Build in stages: core + DB → storefront → checkout → admin → polish | DONE | `docs/stage1-run-report.md` and `docs/stage2-run-report.md`; `docs/build/`; the commerce and admin smoke tests in `docs/launch/`; the polish pass (`docs/a11y-audit.md`, `docs/seo-audit.md`, `docs/security-final.md`, LH). |
| 6.3 | After each stage run it locally, fix errors, and screenshot desktop and mobile | DONE | `docs/shots/stage2/`, `stage2-final/`, `docs/launch/shots-store/`, `shots-admin/`, `shots-commerce/` and `docs/shots/stage5/`. The stage-5 admin shots were broken and were replaced on 2026-10-01 (§10). |
| 6.4 | Place test orders with every payment method | DONE | COD, Bank Transfer, JazzCash and Easypaisa each placed end to end and processed in admin (AR §2). Done locally. PLAN stage 6 also asked for the same on the real Hostinger account, and no such run is recorded. |
| 6.5 | Test every admin function | PARTIAL | AR §5 exercised nearly every screen and action, and the 2026-10-01 sweep loaded all 33 admin screens at 375 and 1440 px (4.2). Not exercised: image uploads in Settings and the social links (4.9). |
| 6.6 | A ZIP ready to upload to Hostinger | DONE | `dist/skyfragrances-public_html-folder.zip` (13,943,403 bytes, rebuilt 2026-10-01 00:23 with the two fixes of AR §15; 595 files, everything under `public_html/`). The file list is checked against the manifest in `site/dev/zip-manifest.php`: 0 required files missing and 0 forbidden entries, and the motion layer is included. The ZIP was rehearsed by a fresh install (HO §7b). It matches the current working tree (checked for this report). |
| 6.7 | A simple step-by-step guide for a non-developer: database, upload, `install.php`, SSL, go live | PARTIAL | `docs/GO-LIVE-GUIDE.md` has 12 parts, a day-one checklist, troubleshooting and a quick reference. **Applied 2026-10-01:** every pass-5 finding of `docs/guide-review.md` (4 BLOCKERs, 10 RISKs and the smaller items): Part 0 "Is your shop already installed?", the owner's route to the live admin login (username from the developer, own password via the reset tool), an urgent soft launch, the CDN / Force HTTPS rule for a live shop (decision left to the developer, HO §9 item 13), and a live-aware day-one checklist that no longer causes the nested-folder mistake (GR5 #5). The wrapped-ZIP flow and the install-key step are unchanged. **Still against it:** (c) it has no hPanel screenshots (HO §9 item 4); (d) no non-developer has ever followed it, which is the success test in PLAN stage 6 and 07 C.9. |

---

## 7. Lighthouse and performance numbers

Every run in this section is the **mobile** form factor, with simulated 4G and a 4× CPU slowdown,
and **the motion layer on**. None of them entered the kill-switch order in
`docs/motion/PLAN.md` §6.4.

### 7.1 Live host (skyfragrances.com, 2026-10-01 00:06–00:09 Karachi)

**How it was run**

- Lighthouse 13.5.0 from `dev-tools/node_modules`, Chrome for Testing 145, headless.
- Command: `lighthouse <url> --form-factor=mobile --screenEmulation.mobile --throttling-method=simulate`.
  The first three runs added `--preset=perf`; the last two ran all categories.
- **One run per row.** The JSON is in this session's scratchpad and is not kept in the repo; to
  regenerate it, run the same command against the live site.
- The live site is served through Hostinger's CDN (`server: hcdn`) and runs `49cd8f1`, which does
  not include the acceptance-run fixes. None of those fixes touches performance.

| Page | Perf | LCP | FCP | TBT | CLS | Speed Index | Transfer | A11y · BP · SEO |
|---|---|---|---|---|---|---|---|---|
| `/` | **99** | 1.6 s | 1.3 s | 0 ms | 0.001 | 2.8 s | 284 KiB | 100 · 100 · 100 |
| `/shop` | **99** | 1.8 s | 1.2 s | 0 ms | 0.001 | 2.9 s | 282 KiB | not run |
| `/product/azure-oud` | **99** | 2.1 s | 1.3 s | 0 ms | 0 | 2.4 s | 399 KiB | 100 · 100 · 100 |

Four diagnostics still score below 0.9 on both full runs. None of them affects the score:
`unminified-css` (the CSS is hand-written and not minified, by the no-build rule),
`image-delivery-insight`, `network-dependency-tree-insight` and `render-blocking-insight`.

### 7.2 Local, production-equivalent (`docs/lighthouse/summary.md`, 2026-09-29)

The local server is `php -S` behind `dev-tools/gzip-proxy.mjs`, so the HTML is compressed the way
Hostinger's `mod_deflate` compresses it. "Median of 3" means the median of three runs.

| Page | Before the perf pass | After the perf pass (median of 3) |
|---|---|---|
| `/` | 98 · LCP 2.3 s | **98** · LCP 2.4 s · FCP 1.1 s |
| `/shop` | 97 · LCP 2.5 s | **98** · LCP 2.5 s · FCP 1.1 s |
| `/product/azure-oud` | 97 · LCP 2.6 s | **96–98** · LCP 2.5–2.9 s · FCP 1.1 s |

- TBT was 0 ms and CLS 0 or 0.001 on every run.
- The one render-blocking stylesheet is now `critical.css` (14 KB gzipped), down from 24 KB
  (`docs/perf/critical-css.md`).
- Four ways of loading the critical CSS were measured: three were rejected and one shipped. The
  reports are in `docs/lighthouse/variants/`.

### 7.3 The ZIP as installed (`docs/lighthouse/fresh-install-zip/summary.md`, 2026-09-29 11:58)

One run per page.

| Page | Server | Perf | LCP | TBT | CLS |
|---|---|---|---|---|---|
| `/` | production-equivalent (gzip) | **98** | 2.3 s | 0 ms | 0 |
| `/shop` | production-equivalent (gzip) | **98** | 2.3 s | 0 ms | 0.001 |
| `/product/azure-oud` | production-equivalent (gzip) | **98** | 2.5 s | 0 ms | 0 |
| `/` | raw `php -S`, HTML not compressed | 95 | 2.9 s | 0 ms | 0 |

### 7.4 Acceptance run (AR §10)

- Raw `php -S`: `/` scored 94 · 95 · 95 and the product page 92 · 92 · 96.
- Accessibility 100 and Best Practices 100. SEO 100 once `site_indexable=1`.
- LCP 2.8–3.3 s.

### 7.5 Budgets and levers not applied

- The B.2.7 budgets were **not re-run** by the acceptance pass (AR §10, the "NOT RE-RUN" row).
- The 14 KB *raw* budget for inlined critical CSS was superseded: the file is 83 KB raw and 14 KB
  gzipped, and it is linked rather than inlined (register §5).
- Levers the perf pass left open (LH "Still open"):
  - a 900 px product image, so the phone's LCP image drops from 52 KB to about 20 KB;
  - a desktop-only `motion.css` (6.5 KB gzipped, which still blocks rendering on phones);
  - inlining `gate.js`;
  - a cookie for the announcement-bar dismissal, which today shifts the layout on every later page
    in the same session;
  - font subsetting (about 20 KB), which waits for client approval.

---

## 8. What the owner must still supply or decide

### 8.1 Urgent: the shop is public tonight (GR5 #1–#4)

1. **The admin username** for the live shop, from whoever installed it. Then set your own
   password with `app/tools/reset-password.php` (guide Part 10, "Forgotten admin password").
   Nobody else should ever know that password.
2. **Either** real payment details **or** switch those methods off. The seed ships these
   placeholders: `bank_name`, `bank_account_title`, `bank_account_number`, `bank_iban`,
   `jazzcash_account_title`, `jazzcash_number`, `easypaisa_account_title` and
   `easypaisa_number` (HO §2). The seeded IBAN placeholder is 37 characters against a 34-character
   limit, so the Payments tab shows a red counter until you replace it.
3. **Hide the sample perfumes and turn indexing off** (Settings → Advanced) until the real
   catalogue is in.
4. **A decision, made with the developer, on Hostinger's CDN and Force HTTPS.** Both are on in
   production, although the guide says they must be off. The CDN is what strips the site's CSP
   (§9 item 3).

### 8.2 Content only the owner has

5. **Product photographs**, one set per perfume: portrait, JPG or PNG (not HEIC), ideally a
   bottle on a dark background. The upload crops every photo to 4:5 (Q-17). This is the biggest
   visual upgrade left (1.5).
6. **The real catalogue**: names, sizes, prices, sale prices, stock, SKUs, notes, longevity and
   sillage, season and occasion, collections. At least 4 live perfumes are needed before
   *Tools → Remove sample data*; below that the home rows hide (HO §5 item 2). The seeded prices
   (Rs. 3,950–13,950) are placeholders (Q-03).
7. **Images for Settings**: optional hero images for desktop and mobile, and six Instagram tile
   images with their links. Confirm the logo, favicon and OG image or replace them.
8. **Contact and social details**:
   - Confirm `instagram_url`, `instagram_handle` and `facebook_url`, which are guesses at
     `skyfragrances`.
   - Fill in `contact_phone`, `address_line` and `business_hours`, plus `tiktok_url` and
     `youtube_url` if you use them.
   - The live footer shows `admin@skyfragrances.com`; confirm that is the address customers
     should see.
9. **A read of the legal and policy pages.** `/shipping`, `/returns`, `/privacy`, `/terms` and
   `/faq` are seeded launch copy, not legal advice. The privacy page promises that payment proofs
   are deleted after 90 days, and the purge tool keeps that promise (C-65).

### 8.3 Email and DNS

10. **SMTP**: the password of the `orders@skyfragrances.com` mailbox, set in `config.php` → `smtp`
    (guide Part 10). Until it is set, no order email reaches anyone (3.6b), and WhatsApp is the
    real confirmation channel.
11. **DNS**: the domain already resolves to Hostinger with a valid certificate (LIVE). Pass 5
    found SPF, DKIM, MX and DMARC records in place (GR5 verdict). Nothing more is needed unless the
    host changes. HSTS is one `.htaccess` line for the developer to add once the certificate has
    been stable for a while (Q-23).
12. **Optional**: a Google Search Console verification code (Settings → SEO), and a 15-minute
    cron on `cron.php` so emails do not wait for an admin page load (HO §5 item 6).

### 8.4 Questions still open in the register

Each was built to a default, and each can still change.

- **Q-01:** random order numbers (`SF-260929-6SDM`) or sequential ones for an accountant.
- **Q-02:** whether an FBR sales-tax line is needed on invoices. None is built.
- **Q-25:** keep the one-per-phone limit and the one-year expiry on WELCOME10?
- **Motion (`docs/motion/technical-fixes.md`, "Not applied"):** whether the desktop intro should
  replay at most once every 7 days, and whether fonts may be subset with the italic dropped.

### 8.5 Commit approval

13. **Approval to commit and push** the uncommitted acceptance-run changes (AR §11): 11 files
    under `site/`, plus build tooling and docs. A push to `main`
    deploys to the live site automatically.

---

## 9. Known limitations

### 9.1 On the live site today

1. **The live site is public, indexable and orderable with sample products.** `robots` says
   `index,follow`, `/sitemap.xml` lists 40 addresses including `/product/azure-oud`, and the home
   page shows the 12 sample perfumes with placeholder art (LIVE; GR5 #3).
2. **The live code is behind the ZIP.** Production runs `49cd8f1`; the acceptance fixes are in the
   working tree and the ZIP but were never committed or pushed. Until they are, live has:
   - no COD fallback when every method is unusable;
   - no red dashboard banner for placeholder payment details;
   - a cart cookie that dies when the browser closes (instead of lasting 3 days);
   - a sold-out default size that leaves no-JavaScript visitors on a dead button;
   - a sitemap that still lists `/track` (confirmed LIVE).
3. **Hostinger's CDN replaces the site's Content-Security-Policy with `upgrade-insecure-requests`**
   (LIVE header on `/` and `/admin/login`). So the script, frame and form restrictions the site
   sets are not reaching browsers. The other headers do arrive, for example `X-Frame-Options: DENY`
   and `Referrer-Policy`.
4. **Behind the CDN, every visitor may appear to come from one CDN address** (GR5 #4). The rules
   keyed on the visitor's IP could then treat different customers as one:
   - the per-IP rate limits;
   - the rule that blocks a second transfer order while one awaits verification (C-58);
   - the 10-failure admin login lock.

   Whether `trusted_proxies` covers the CDN's whole edge range has not been checked.
5. **Every push resets the HTTPS redirect to temporary.** `deploy.yml` rsyncs `.htaccess` with its
   `R=302` line, so the permanent flip is undone and Settings → Advanced shows "Temporary (302)"
   again (GR5 #14). On the live site today this is hidden, because Hostinger's own edge does the
   301.
6. **No order email has ever been delivered in any run** (3.6b).

### 9.2 Built, with a known flaw

7. ~~**Checkout proof preview**: the thumbnail of the chosen payment screenshot never appears (CSP
   blocks `blob:`)~~ — fixed 2026-10-01 in `app/lib/response.php`, which also covers the admin
   product-photo uploader (AR §15, HO §8d).
8. **Visual observations from the stage-5 shots, not confirmed on a real phone:**
   - the hero plate's straight bottom edge across the subheading;
   - ~~mobile breadcrumbs cut mid-word~~ — fixed 2026-10-01, they now end in "…" (AR §15);
   - card meta cut with "…" at 375 px ("50ML · 1…" in `shop--mobile.png`).
9. **Announcement dismissal** is kept in `sessionStorage`, so the header jumps up by the bar's
   height on every later page in that session (LH "Still open").
10. **Motion accessibility:** there is no "Pause animations" control for hero movement longer than
    5 seconds (WCAG 2.2.2). The only mitigation is that the plates stop after 2 cycles
    (`docs/motion/technical-fixes.md`, "Not applied"). `docs/a11y-audit.md` also lists the quiz
    announcement and the reduced-motion veil as open; `technical-fixes.md` and AR §10 show both
    fixed since.
11. **The motion layer differs from the motion brief in three ways, each a documented plan
    decision** (`docs/motion/PLAN.md` §8):
    - hand-written WebGL instead of Three.js;
    - a particle "mist gathers into the monogram" intro instead of a stroke draw, because no vector
      monogram exists;
    - ingredient parallax limited to desktop hero and story, because no ingredient art exists.
12. **Perf levers left open:** a 900 px product image, a desktop-only `motion.css`, inlining
    `gate.js`, and font subsetting (§7.5).
13. **The seeded IBAN placeholder is longer than the field allows** (37 characters against 34).

### 9.3 Deliberately not in this version

These come from the register and HO §5.

14. **Password recovery is a File-Manager tool, not an email link.** There is one admin account
    and no staff roles (Q-10). The admin session is tied to the browser, so a different browser
    means logging in again.
15. **No cron is needed.** Emails go out when an admin page loads, at most 2 per load.
    `cron.php` is optional.
16. **The shop does not do these things:**
    - Orders are never hard-deleted, and "Delivered" is final (Q-26).
    - No back-in-stock alerts (Q-27).
    - No free-shipping coupon type (Q-12).
    - No orders entered by the admin (Q-11).
    - No Urdu or right-to-left layout (Q-21).
    - No live Instagram feed.
    - No newsletter sending (the list is exported instead).
17. **Hosting limits:**
    - Sub-folder installs are refused (C-71).
    - `/sitemap.xml` returns 404 while indexing is off.
    - `.user.ini` sets secure cookies, so an admin on plain http will not stay logged in.
    - HSTS is off (Q-23).

### 9.4 Never tested

18. **Databases:** MySQL 8.0 and MariaDB 10.4 (5.9).
19. **Uploads and install on Hostinger:** the phpMyAdmin import, and the hPanel File-Manager
    upload and extract (2.5).
20. **Live-host acceptance checks** (AR §1): two orders from different networks recording
    different IP hashes, the http→https self-check that flips 302 to 301, the host-mismatch banner,
    and the contact-form cap under rotating IPs.
21. **Checks needing a public URL:** Google's Rich Results Test and a real WhatsApp link preview.
22. **Admin tap targets** at 44 px; only the storefront was measured.
23. **The go-live guide** has never been followed by a non-developer and has no hPanel
    screenshots (6.7).

---

## 10. Problems in the evidence found during this review

1. **The stage-5 admin screenshots were all the login page. Resolved 2026-10-01:** the folder now holds 33 real 375 px screenshots from a logged-in sweep (4.2).
   - `md5` of the 28 PNGs in `docs/shots/stage5/admin/` gives exactly two hashes:
     `fb67a35a…` for all 14 desktop files (35,300 bytes each) and `39f53196…` for all 14 mobile
     files (77,433 bytes each). Both images are "Admin sign in".
   - `admin/report.json` still records every path as HTTP 200 with no overflow, because the tour
     followed the redirect to `/admin/login`.
   - So AR §5's last row ("15 admin screens … 0 horizontal overflow") and the admin part of AR §8
     and §12 are **not supported** by these files. AR §11 fix 12 (`TOUR_UA`) was meant to reuse an
     admin session. The shots show it did not.
2. **The tour never captured an order-detail page.** `admin/report.json` lists the path
   `/admin/orders/` (the order number is empty), and that shot overwrote `admin_orders--*.png`.
   The only order-detail screenshots of the final code are the desktop ones from the ZIP
   rehearsal: `docs/launch/shots-fresh-install-zip/22`, `25`–`28`.
3. **The stage-5 product-page shots predate a fix.** They were taken at 11:11, and
   `app/controllers/product.php` (AR §11 fix 6) is dated 11:22. They therefore show the sold-out
   default size the fix removed.
4. **`docs/guide-review.md` changed while this report was written.** It went from pass 4 to
   pass 5, dated 2026-10-01 00:08. This report cites pass 5 as it stood then.
5. **The previous `COMPLETION-REPORT.md` (2026-09-28) is superseded.** Its motion statement
   ("counts toward no brief item") and its Lighthouse row (76–77 mobile) described a build that no
   longer exists.

**Re-shoot command for the admin screenshots, once a working admin cookie is available:**

```
node dev-tools/tour.mjs <url> docs/shots/stage5/admin /admin /admin/orders /admin/orders/<number> …
```

After it runs, check that the md5 values differ from file to file before trusting the shots.
