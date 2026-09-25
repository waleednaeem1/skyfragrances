# 10. Security and SEO commitments

Two checklists you can hold the build to. Every line is something that can be tested on the
running site, and the acceptance tests in stage 6 tick each one (see 07 B.4).

## 10.1 Security

| # | Commitment | Where the detail lives |
|---|---|---|
| S1 | Every database query uses PDO prepared statements (the database receives the query and the values separately, so typed input can never become SQL). Emulated prepares are switched off. Sort orders come from a fixed list, never from the URL. | 07 B.3.1 |
| S2 | Every value printed into a page goes through one escaping helper, `e()`. Customers can never submit HTML; reviews, notes and addresses are plain text. | 07 B.3.2, 08 §1.5 |
| S3 | Every form and every AJAX call carries a CSRF token (a secret the browser must send back so a hostile site cannot submit forms on a visitor's behalf). A missing token is refused with a 419 and the typed values are kept. Nothing state-changing happens on a GET link. | 07 B.3.3, 08 §1.5 |
| S4 | Uploads (product images, payment screenshots) accept JPG/PNG/WEBP only, decided by inspecting the file bytes, never the filename. Size caps: 5 MB for a customer proof, 8 MB for an admin image. Every image is re-encoded through GD, so the original bytes never reach disk and phone GPS data is stripped. | 07 B.3.4, 08 Q-08 |
| S5 | Files are stored under a random name, never the uploaded one. `/uploads` cannot execute PHP: four independent `.htaccess` layers, tested by uploading a fake `.jpg` containing PHP and confirming it downloads as text. | 07 B.3.5, 02b §5, 08 C-37 |
| S6 | Payment screenshots are never public. They live in the denied `storage/proofs/` tree and are only streamed to a logged-in admin. | 08 C-25 |
| S7 | Security headers are sent from PHP on every page: a Content-Security-Policy that allows only this site's own files (inline styles allowed for the critical CSS, no inline scripts, no frames in or out), `nosniff`, a strict referrer policy and a permissions policy. HSTS (forcing HTTPS for a year) is **off at launch** and switched on once the certificate is proven stable. | 07 B.3.6, 08 C-40, Q-23 |
| S8 | Admin login: passwords stored with `password_hash`; 5 failures from one IP in 15 minutes locks that IP for 15 minutes, 10 failures on one username in an hour locks the username; the error message never reveals whether the username exists. | 07 B.3.9 |
| S9 | Sessions: cookie flags `HttpOnly`, `Secure`, `SameSite`; the session ID is regenerated on login and password change; admin sessions expire after 120 minutes idle or 12 hours absolute; shop and admin use separate cookies. | 07 B.3.7, 08 C-05, C-06, C-26 |
| S10 | Errors never leak: production shows a branded 500 page with a reference number; the full detail goes to a log in the denied `storage/logs/` folder. No `phpinfo`, no `.bak` copies, no test files on the server. `config.php` is denied by name. | 07 B.3.8, 08 §1.1 |
| S11 | Prices are always recalculated on the server. The browser only ever sends product/size IDs and quantities. Stock is decremented inside one database transaction with a row lock and an `UNSIGNED` column, so it cannot go negative even under two simultaneous checkouts. | 07 B.3.10, 08 C-03 |
| S12 | Customer data stored is exactly: name, phone, optional email, city, address, optional note, payment screenshot. No CNIC, no passwords, no card numbers. Order lookup needs order number **and** phone. Order numbers are random, not sequential. CSV exports neutralise spreadsheet formula injection. Proofs are deleted 90 days after delivery/cancellation by an admin tool. The privacy page states all of this. | 07 B.3.11, 08 C-10 |

## 10.2 SEO and performance

| # | Commitment | Where the detail lives |
|---|---|---|
| P1 | Unique title (max 60 characters) and meta description (max 155, always a full sentence) on every indexable page, following a fixed pattern per page type; product and collection descriptions are hand-written in the seed. | 07 B.1.1, B.1.2 |
| P2 | Open Graph and Twitter tags on every page, with a 1200×630 preview image so a WhatsApp share shows a large card. | 07 B.1.3 |
| P3 | Product JSON-LD (structured data Google reads) on every product page, priced from the same function that renders the page, in PKR. **No fake ratings:** a star rating is emitted only when the product has at least one approved review, and it is computed from those reviews. | 07 B.1.4 |
| P4 | Organization and BreadcrumbList JSON-LD on every page, WebSite search markup on the home page only, FAQ markup on `/faq` only. Nothing invented. | 07 B.1.5 |
| P5 | `sitemap.xml` is generated live from the catalogue (cached for an hour), so hiding a product removes it automatically; `robots.txt` is a real file that blocks admin, cart, checkout, search and API paths. | 07 B.1.7, 08 §1.3 |
| P6 | Clean URLs (`/product/azure-oud`, `/shop`, `/for-him`); one canonical URL per page; filter and sort parameters are stripped from canonicals; renamed slugs 301 to the new address. | 07 B.1.6, B.1.8 |
| P7 | Alt text is a required field on every image; the product form refuses to save a product whose primary image has none. | 07 B.1.8 |
| P8 | Every image has explicit width and height (no layout jump), loads lazily except the main hero/product image, and is served in WebP with a JPEG fallback. | 07 B.2.3 |
| P9 | GD generates 400/600/900/1400 px sizes at upload; the page lists them in `srcset` so a phone downloads the small one. | 07 B.2.3 |
| P10 | Fonts are self-hosted (five woff2 files, two preloaded); no Google Fonts, no icon font. | 07 B.2.2, 08 C-32 |
| P11 | Critical CSS inlined by hand (≤14 KB), one stylesheet, five small storefront JS files (~11 KB total) that only enhance a page that already works without them. Zero third-party scripts at launch. | 07 B.2.1, B.2.4, 08 C-31, §4 |
| P12 | Lighthouse **mobile** target: Performance, SEO, Best Practices and Accessibility all 90 or higher on the home page and a product page, measured three times on the live host. Budgets: LCP ≤ 2.5 s, CLS ≤ 0.05, home ≤ 900 KB, product page ≤ 800 KB. | 07 B.2.7, B.4.10 |

# 11. Sample data that will be seeded

Everything in this section is demonstration content so the site and every admin screen have
something realistic to show on day one. It is yours to keep, edit or remove (see 11.5).

## 11.1 The five collections (see 07 A.1)

| Collection | Mood |
|---|---|
| Dawn Chorus | Optimism, a clean start: luminous florals and soft citrus. |
| Azure Heights | Clarity and composure: crisp, weightless freshness that survives the heat. |
| Golden Hour | Indulgence and glow: saffron, amber and rose. |
| Midnight Meridian | Authority and mystery: oud, leather and smoke. |
| Monsoon Veil | Nostalgia and relief: petrichor, vetiver and wet green air. |

## 11.2 The twelve perfumes (see 07 A.2)

Spread: 4 For Him, 4 For Her, 4 Unisex. Prices in Rs., shown as 50 ml / 100 ml.

| Perfume | Collection | For | Scent family | 50 ml | 100 ml | On sale? |
|---|---|---|---|---|---|---|
| Azure Oud | Midnight Meridian | Unisex | Woody Oud | 8,950 | 13,950 | no (featured) |
| Cirrus | Azure Heights | Unisex | Fresh Aromatic Musk | 5,450 | 8,450 | no (featured) |
| Aurora Bloom | Dawn Chorus | Her | Floral Fruity | 5,950 | 7,950 (was 9,450) | **yes** (featured) |
| Stratus Noir | Midnight Meridian | Him | Smoky Leather | 7,450 | 11,450 | no (featured) |
| Eclipse Velvet | Midnight Meridian | Her | Oriental Gourmand | 7,950 | 12,450 | no; 100 ml seeded **out of stock** |
| Zephyr Blanc | Azure Heights | Her | White Floral Musk | 5,650 | 8,950 | no (new) |
| Silver Lining | Azure Heights | Him | Fresh Woody Citrus | 3,950 (was 4,950) | 6,450 (was 7,950) | **yes** (featured) |
| Cumulus Cashmere | Dawn Chorus | Unisex | Soft Musk Powdery | 4,450 (was 5,250) | 6,950 (was 8,250) | **yes** (new) |
| Saffron Zenith | Golden Hour | Him | Spicy Amber Leather | 8,450 | 12,950 | no; 50 ml seeded at **low stock (3)** |
| Halo Rose | Golden Hour | Her | Rose Oud Amber | 7,250 | 9,450 (was 11,250) | **yes** |
| Nimbus Rain | Monsoon Veil | Unisex | Aquatic Green Petrichor | 5,150 | 7,950 | no (new) |
| Vetiver Squall | Monsoon Veil | Him | Woody Aromatic Vetiver | 6,450 | 9,950 | no (new) |

The out-of-stock size and the low-stock size are deliberate, so the "Sold Out" badge and the
dashboard low-stock alert can be seen working before launch. The prices are a placeholder
band (08 Q-03); you edit them in admin before launch.

## 11.3 The three coupons (see 07 A.3)

| Code | Discount | Minimum order | Why it is seeded |
|---|---|---|---|
| `WELCOME10` | 10% off | Rs. 3,000 | The one from your brief. Usable, limited to 1 use per phone number, valid one year. |
| `EIDSALE500` | Rs. 500 off | Rs. 4,000 | Deliberately **expired**, so the admin coupon list shows the grey "Expired" state. |
| `FIRST50` | Rs. 750 off | Rs. 5,000 | Deliberately **fully redeemed** (50 of 50), so the amber "Limit reached" state is visible. |

Also seeded: the five-question Scent Finder quiz with its scoring (07 A.6), store settings
(name, tagline, contact details, shipping fee Rs. 250, free shipping above Rs. 3,000, delivery
time "2–4 working days", all four payment methods enabled) and the copy blocks (announcement
bar, hero heading, footer blurb), every one editable in Settings (07 A.5).

## 11.4 Sample reviews are flagged and must go before launch (see 07 A.7)

Eight sample reviews are seeded (five approved, three awaiting moderation, including a 2-star
one) so the moderation screen and star ratings can be tested. Every one is marked
`is_sample = 1`. Your brief says no fake reviews, so:

- The admin dashboard shows an amber banner, "This store is still showing sample data", until
  they are gone.
- One button, **Admin › Tools › Remove sample data**, deletes all sample reviews and any
  sample product or collection that has never been ordered, and reports what it removed and
  what it kept. It never touches your settings.
- Deleting the sample reviews is a **go-live blocker** in the acceptance tests (07 B.4.7).

## 11.5 Placeholder bank details must be replaced (see 07 A.5.3)

The seeded bank, JazzCash and Easypaisa account details all read `REPLACE ME` (for example
`0000-0000000-000 (REPLACE ME)`). Until you replace them in Settings › Payments:

- the dashboard shows a red banner that cannot be dismissed, naming each affected method;
- checkout **hides** that payment method so no customer is ever asked to pay into a
  placeholder account; if every manual method is unconfigured, Cash on Delivery stays on.

# 12. Build stages

The order your brief asked for. Each stage ends with the site running locally (PHP built-in
server + MySQL/MariaDB), errors fixed, and screenshots at desktop and 375 px mobile width. You
review each stage before the next starts.

| Stage | What gets built | What "done" looks like | What you will see |
|---|---|---|---|
| 1. Core + database | Folder tree (02a), `.htaccess` rules (02b), bootstrap and helper libraries (02c), `config.php` + `install.php`, full schema and seed (01a/01b/01c, 07 A), layout shell with fonts, tokens and critical CSS (04a). | `install.php` runs on an empty database and creates every table, the admin account and all sample data; re-running it is refused; the same SQL imports cleanly on MySQL 8 and MariaDB 10.4; clean URLs and HTTPS redirect work; `/app`, `/db`, `/storage` and `config.php` are unreachable over HTTP (07 B.4.1). | A screenshot of the installer's success page, the table list in phpMyAdmin, and a styled but empty home page. |
| 2. Storefront | Every public page (03): home, shop with filters/sort/search/pagination, collection, gender and scent-family landings, product page (gallery, sizes, notes pyramid, meters, reviews, related), cart drawer + cart page, Scent Finder, content pages, contact, track order, 404; WhatsApp button; SEO head, JSON-LD, sitemap. | All 12 products and 5 collections browsable; filters, search and pagination return the right items; cart add/update/remove and coupon apply work with and without JavaScript; every page passes the 375 px checks (no horizontal scroll, 44 px tap targets). | Desktop and mobile screenshots of every page, and a running local site you can click through yourself. |
| 3. Checkout | Guest checkout form, server-side pricing pipeline and coupon rules (06a), the order transaction with stock locking and double-submit guard (06b), all four payment methods incl. manual-payment account display, transaction ID and screenshot upload, confirmation page, customer + admin emails (via the outbox), order tracking timeline. | A test order completes with each of COD, Bank, JazzCash and Easypaisa; totals agree on cart, checkout, confirmation, email and database; stock cannot go negative under two simultaneous checkouts; a PDF or 12 MB proof is rejected with a readable message (07 B.4.2–B.4.4). | Four test orders (one per method) with their confirmation pages and emails, and the order rows in the database. |
| 4. Admin | Login with rate limiting, dashboard, products (multi-image upload with GD resizing, sizes, SEO fields), collections, orders (list, detail, status chain, courier/tracking, proof viewer, mark paid, invoice/packing slip, cancel with stock restore, WhatsApp message, CSV export), coupons, reviews, messages, subscribers, settings, password change, remove-sample-data tool (05a/05b). | Every admin function in the brief works from a phone at 375 px; 5 bad logins lock the IP; changing a setting is visible on the storefront immediately; the placeholder and sample-data banners behave as in §11 (07 B.4.5–B.4.7). | Screenshots of every admin screen on desktop and mobile, and the stage-3 test orders moved through Pending → Delivered and one cancelled. |
| 5. Polish | SEO/performance/accessibility/mobile pass: title and description audit, JSON-LD validation, image dimensions and alt text grep, keyboard-only and reduced-motion runs, Lighthouse tuning, browser and JS-off checks, design polish against 04a/04b. | Lighthouse mobile ≥ 90 on all four categories for home and a product page; every budget in 07 B.2.7 met; every item in 07 B.4.8–B.4.10 ticked. | Lighthouse reports, a crawl listing every page's title/description with no duplicates, before/after screenshots of anything changed. |
| 6. Package | Fresh-install rehearsal, the upload ZIP, the non-developer go-live guide (create database in hPanel, upload and extract, run `install.php`, delete it, enable SSL, replace bank details, remove sample data, set SPF/DKIM), then the full acceptance run repeated **on the real Hostinger account**. | Every checkbox in 07 B.4 passes on the live host, including a test order per payment method and every admin function. | The ZIP, the step-by-step guide, and the live-host test orders for you to inspect and then cancel. |

# 13. Assumptions

Defaults the build takes now. Each can be changed later without a rewrite; where a change
would need a database migration it says so.

**Hosting and environment**
- Hostinger Premium shared hosting: LiteSpeed, PHP 8.2, `memory_limit` 256M, no cron (08 Q-18). Because no cron is assumed, order emails are queued in an outbox that is sent on the next admin page load, and payment-proof cleanup is a button, not a schedule (08 §4).
- Everything, including logs, sessions and payment proofs, lives inside `public_html` in a denied `storage/` folder, because File Manager cannot reliably place files above it (08 C-24).
- The database is MariaDB on the host and MySQL locally; all SQL is written to run on both (07 §0.1).
- Outgoing mail goes through `smtp.hostinger.com:587` (TLS), entered during `install.php` (08 Q-18). SPF and DKIM are a go-live step for you; until then WhatsApp is the reliable confirmation channel (08 Q-19).
- Canonical address is `skyfragrances.com`; `www` redirects to it (08 Q-20). HSTS is off at launch (08 Q-23).

**Catalogue and content**
- Product photos are 4:5 bottle shots on a dark ground; upload enforces a 4:5 crop so any photo works, but mixed styles will look weaker than the design assumes. Originals are discarded after re-encoding (08 Q-17).
- The seeded prices (Rs. 3,950–13,950) are placeholders you edit before launch (08 Q-03).
- The 12 perfumes and 5 collections stay until you remove them; sample reviews are always removed before go-live (08 Q-24).
- English only, `<html lang="en">`; no Urdu or right-to-left in v1 (08 Q-21).
- The "Why choose us" band is the one light-on-ivory section; one class change makes it dark (08 Q-22).
- Instagram section uses six uploaded thumbnails and links set in Settings; no live Instagram feed (08 §4).

**Commerce**
- Shipping fee Rs. 250 flat, free above Rs. 3,000, both editable in Settings (08 Q-04). No COD surcharge; the column exists at `0.00` for later (08 Q-05).
- Prices are quoted tax-inclusive; no tax line on invoices (08 Q-02; adding one later is a column and a template change).
- Abuse caps as constants: COD orders up to Rs. 30,000, 10 of one size per line, 20 lines per cart, 5 orders per phone per day, 3 proof re-uploads (08 Q-06).
- Unpaid bank/JazzCash/Easypaisa orders are flagged amber after 48 hours and never auto-cancelled (08 Q-07).
- Payment proofs are images only (JPG/PNG/WEBP), no PDF (08 Q-08).
- Courier tracking is a tracking number plus an optional link you paste; no courier integrations (08 Q-09). Invoices and packing slips are print-styled HTML pages, not PDFs (05b §0.1).
- Coupons are percent or fixed amount only; no free-shipping coupon type (08 Q-12).
- A sold-out item in the cart is kept and blocks checkout with a message rather than vanishing (08 Q-13).
- Packing slips show no prices; the COD "collect Rs. X" banner is the only amount printed (08 Q-14).
- Guest carts live 3 days in the session; there is no cross-device cart recovery (08 Q-16).
- Order numbers are random (`SF-260925-K7QF`), not sequential (08 C-10; see Q-01 below).

**Admin**
- One admin account; no staff roles (08 Q-10). No admin-entered phone orders; a "create order" screen is v2 (08 Q-11).
- The admin is the site owner, so content pages (About, FAQ, policies) may contain HTML; a second admin role would need this revisited (07 B.3.2).
- Scent Finder questions are seeded and editable only by SQL; an admin screen for them is v2 (08 §4).
- The announcement-bar dismissal lasts one browser session (08 Q-15).

# 14. Questions for you

Only two answers change what gets built. Everything else has a sensible default already taken
(§13) and can be changed at any time.

## 14.1 Blocking — please answer before stage 3 starts

| # | Question | If you do not answer, I will build |
|---|---|---|
| 1 | **Order numbers: random or sequential?** Proposed: `SF-260925-K7QF` (date + 4 random letters). Some owners want a strictly sequential invoice number for their accountant. Sequential brings back a counter table and lets anyone guess order numbers. | Random (08 Q-01). |
| 2 | **Do invoices need a sales-tax (FBR) line?** Your brief does not mention tax, and retail perfume in Pakistan is quoted tax-inclusive. | No tax line: invoice shows subtotal, discount, shipping, total (08 Q-02). Cheap to add now, a migration later. |

## 14.2 Later — defaults taken, change any time

Answer these whenever convenient; none of them holds up the build.

| # | Question | Default taken |
|---|---|---|
| 3 | Are the seeded prices (Rs. 3,950–13,950) acceptable as placeholders? | Ship as seeded; you edit in admin (Q-03). |
| 4 | Shipping fee Rs. 250 and free shipping above Rs. 3,000? | Yes, editable in Settings (Q-04). |
| 5 | Any COD surcharge? | None (Q-05). |
| 6 | Abuse caps (COD max Rs. 30,000; 10 per line; 20 lines; 5 orders per phone per day; 3 proof re-uploads)? | As listed (Q-06). |
| 7 | Unpaid manual-payment orders: flag after 48 h, never auto-cancel? | Yes (Q-07). |
| 8 | Payment screenshots: images only, or PDF too? | Images only (Q-08). |
| 9 | Courier tracking: number plus optional link, or per-courier templates? | Number + optional link (Q-09). |
| 10 | Second (staff) admin account? | Not in v1 (Q-10). |
| 11 | Will you need to enter phone/WhatsApp orders yourself in admin? | Not in v1 (Q-11). |
| 12 | Free-shipping coupon type? | Not in v1 (Q-12). |
| 13 | Sold-out item in cart: keep and block, or remove silently? | Keep and block (Q-13). |
| 14 | Prices on the packing slip? | Omitted, gift-safe (Q-14). |
| 15 | Which Hostinger plan, and is SMTP `smtp.hostinger.com:587`? | Premium, 256M, no cron, that SMTP host (Q-18). |
| 16 | Will you set SPF and DKIM for the domain at go-live? | Assumed yes, as a guide step (Q-19). |
| 17 | Keep the 12 sample perfumes and 5 collections after launch, or purge them? | Your choice via the admin tool; sample reviews always go (Q-24). |
| 18 | Do you have 4:5 bottle photos on a dark ground? | Assumed yes; any photo still works (Q-17). |
| 19 | Urdu in v1? | No (Q-21). |
