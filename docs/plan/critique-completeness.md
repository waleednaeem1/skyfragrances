# Critique — Completeness against the client brief

Lens: delivery manager. Every discrete requirement in `00-brief.md` is listed below and marked
COVERED / PARTIAL / MISSING with the PLAN.md or spec section that handles it. Findings follow
the table, ranked by severity. Reviewed: PLAN.md (all 15 sections), 08-decisions-register.md
(all), and the owning spec sections for each brief line (03, 05a, 05b, 06a, 06b, 02b, 02c, 07).

Reading of the marks:

- COVERED — a builder can implement it from the cited section without guessing, and the plan's
  promise matches the brief.
- PARTIAL — the intent is there but something is contradictory, stale, or left to a later stage.
- MISSING — nothing in PLAN.md or the specs delivers it.

Summary: 104 requirements enumerated. 91 COVERED, 13 PARTIAL, 0 MISSING. No brief line is
absent from the plan; the gaps are unresolved contradictions between specs that the register
did not referee, three items PLAN.md itself defers to a later stage, and a deliverable (the
go-live guide) that no spec owns. Details and fixes are in section 3.

---

## 1. Audit table

### 1.1 Brand (brief §Brand)

| # | Requirement | Mark | Where handled | Note |
|---|---|---|---|---|
| B-01 | Name Sky Fragrances, domain skyfragrances.com | COVERED | PLAN §1, §13 (Q-20 apex canonical) | |
| B-02 | Tagline "More Than Just A Scent" | COVERED | 03 §4.2 `hero_heading` default; 05a §4.9 `store_tagline`; 05b §7.2 sign-off line | |
| B-03 | Logo `./logo.png` (gold SF monogram on black) | COVERED | 02a §2 `assets/img/logo.png`, `logo.svg`, `logo-mark.svg`; 05a §4.9 `logo_path` default | Plan does not say the client's supplied file is the one shipped, but the path and Settings override are specified. |
| B-04 | Palette black `#0A0A0A` + champagne gold `#D4B084` + ivory `#F5F0E8`, gold gradients | COVERED | PLAN §6.1; 04a §1–2 | Gold-on-ivory contrast failure is caught and ruled (04a §2.4). |
| B-05 | Cormorant Garamond headings, Jost body | COVERED | PLAN §6.2; 04a §3; C-32 five self-hosted files | |
| B-06 | Subtle animations: fade/slide on scroll, hover zoom on products | COVERED | PLAN §6.4; 04b §17–19, §21 | |
| B-07 | Lots of whitespace, minimal, "better than Le Labo / Byredo" | COVERED | PLAN §6.5; 04a §3.5, §4.2 | Stated as four concrete choices, so it is checkable. |
| B-08 | Market Pakistan, currency PKR, format `Rs. 4,950` | COVERED | PLAN §4.3 rule 1, C-01, C-02 `money()`; 01c §1.3 | |
| B-09 | Timezone Asia/Karachi | COVERED | PLAN §4.3 rule 5; 01c §4.2; 02b §8.2 `.user.ini date.timezone`; 05a §4.9 read-only `timezone` key | DB session `+05:00` also set. |

### 1.2 Hosting and tech (brief §Hosting & tech)

| # | Requirement | Mark | Where handled | Note |
|---|---|---|---|---|
| H-01 | Hostinger shared: PHP 8.x + MySQL only; no Node, Composer, build step | COVERED | PLAN §2 (whole table), 02a §1, 02b §1 | Installer requires PHP >= 8.2, stricter than "8.x" — the guide must tell the owner to pick 8.2 in hPanel (see F-11). |
| H-02 | Plain PHP + PDO prepared statements everywhere | COVERED | PLAN §10.1 S1; 02c §2; 07 B.3.1 | |
| H-03 | Vanilla JS | COVERED | PLAN §2, C-31 five storefront files + admin.js | 07 B.2.4 still says "one site.js" — stale, not patched. |
| H-04 | One CSS file | COVERED | PLAN §2, C-30 `assets/css/site.css`; critical CSS inlined by hand (08 §4) | Admin and print share the same file (05b §8.4). |
| H-05 | Works by uploading via hPanel File Manager + importing the DB | COVERED | PLAN §1, §3; 02b §7; `db/schema.sql` + `db/seed.sql` for the phpMyAdmin path | |
| H-06 | `config.php` for DB credentials | COVERED | PLAN §3; C-27 array shape; 02c §7.1 | |
| H-07 | One-time `install.php` creates tables, admin account, sample data | COVERED | PLAN §2 Installer row; 02c §7.2 four-step wizard | |
| H-08 | `install.php` tells the owner to delete it afterwards | PARTIAL | 02c §7.2 red "Delete install.php now" panel + dashboard banner; BUT 07 A.7 and 02b §7.4 say it `unlink()`s itself; lock file is `storage/.installed` (02c, PLAN) vs `db/.installed` (07 A.7, B.4.1) | Two behaviours and two lock paths for the same file. See F-13. |
| H-09 | Pretty URLs via `.htaccess` (`/product/azure-oud`, `/shop`, `/cart`) | COVERED | PLAN §2, §5.1; 02b §3 full file | |
| H-10 | Force HTTPS | COVERED | 02b §3.2 proxy-aware rule; PLAN §2 | HSTS deliberately off at launch (Q-23) — disclosed. |
| H-11 | SQL must run on MySQL 8+ and MariaDB 10.4+ (verified-env note) | COVERED | PLAN §2 Database engine row, §4; 02c §7.2 step 2 version assert; 07 B.4.1 | |

### 1.3 Storefront pages (brief §Storefront pages 1–10)

| # | Requirement | Mark | Where handled | Note |
|---|---|---|---|---|
| S-01 | Home: full-screen hero with logo/tagline + CTA | COVERED | PLAN §5.2 Home; 03 §4.2 | |
| S-02 | Home: announcement bar | PARTIAL | 03 §4.1; 04b §15; 05a §4.9 `announcement_*` | 03 §4.1 dismisses via `localStorage` hash-of-text; register Q-15 says `sessionStorage` per session; 03 lists `announcement_bg`/`_fg` keys that 05a §4.9 does not define. See F-14. |
| S-03 | Home: shop by collection | COVERED | 03 §4.3 | |
| S-04 | Home: best sellers | COVERED | 03 §4.4; `home_bestsellers_count` | |
| S-05 | Home: new arrivals | COVERED | 03 §4.5 | |
| S-06 | Home: For Him / For Her / Unisex | COVERED | 03 §4.6; C-29 tokens | |
| S-07 | Home: why-choose-us with exactly long-lasting, COD nationwide, fast delivery, easy exchange | COVERED | 03 §4.8 names the four verbatim (`Long-Lasting Fragrance`, `Cash on Delivery Nationwide`, `Fast Delivery ({delivery_time})`, `Easy Exchange`); 08 §4 hard-codes them (`usp_items` cut) | PLAN §5.2 only says "4 items" — the four words live in 03, not in the client-facing plan. |
| S-08 | Home: newsletter signup | COVERED | 03 §4.11; `/api/newsletter`; `/unsubscribe` | |
| S-09 | Home: Instagram section | COVERED | 03 §4.10 (patched); 08 §4 six `instagram_tile_n_image/_url` settings; PLAN §13 | Static tiles, no live feed — disclosed as an assumption. |
| S-10 | Shop: filters collection, gender, scent family, price range | COVERED | 03 §5.1–5.3; PLAN §5.2 Shop | Price is bands computed from live prices, not a slider — acceptable reading of "price range". |
| S-11 | Shop: sort | COVERED | 03 §5.5 | |
| S-12 | Shop: search | COVERED | 03 §5.15; C-38 scored LIKE | |
| S-13 | Shop: pagination | COVERED | 03 §5.6 (24/page) | |
| S-14 | Product: image gallery with zoom | COVERED | 03 §6.1 desktop 2x hover zoom, mobile lightbox with pinch | Derivative width the zoom reads from is inconsistent across specs — see F-06. |
| S-15 | Product: size selector, each with own price, sale price, stock | COVERED | 03 §6.3; 01a `product_sizes` | |
| S-16 | Product: scent notes pyramid top/heart/base | COVERED | 03 §6.5; 04b §8 | |
| S-17 | Product: longevity and sillage meters | COVERED | 03 §6.5; 04b §9 | Meter word labels differ between 03 §6.5 and 04b §9 — PLAN §6.5 defers to stage 2. |
| S-18 | Product: season / occasion | COVERED | 03 §6.5; 05a §4.3 section 4 | |
| S-19 | Product: customer reviews, admin-approved only, no fake reviews | PARTIAL | 03 §6.7; 05a §4.6, §7.1; 07 A.4 seeds 5 approved + 3 pending `is_sample` reviews | Five approved fake reviews render on the storefront and drive `aggregateRating` until the owner runs the removal tool (07 B.1.4 point 5 admits this). See F-04. |
| S-20 | Product: related products | COVERED | 03 §6.6 (4, scored) | |
| S-21 | Product: sticky add-to-cart on mobile | PARTIAL | 03 §6.8; 04b §16; PLAN §5.2 | Spec and PLAN §5.2 say a sold-out size swaps the button for "Notify me" — but the register cut `restock_alerts` (08 §4) and 03 §6.4 carries no Superseded note. See F-03. |
| S-22 | Product: WhatsApp order button | COVERED | 03 §6.9 exact message text; PLAN §5.2 | |
| S-23 | Slide-out cart drawer + cart page | COVERED | 03 §7.1–7.2; 04b §12 | |
| S-24 | Free-shipping progress bar | PARTIAL | 03 §7.5 (post-discount); 06a §4.1, §4.3 and PLAN §7.3 (pre-discount) | Direct contradiction with no register ruling; 07 B.4.3 test uses the stale Rs. 5,000 threshold. See F-01. |
| S-25 | Coupon codes in cart | COVERED | 03 §7.7; 06a §3 | |
| S-26 | Checkout guest, no account | COVERED | 03 §8; PLAN §2 "Customer accounts — not chosen" | |
| S-27 | Checkout fields: name, phone, email (optional), city, address, notes | COVERED | 03 §8.1–8.3; 08 §2.3 DDL | |
| S-28 | Payment: Cash on Delivery | PARTIAL | 06b §4.1; PLAN §8.1 | Silent addition: COD refused above Rs. 30,000 — a customer-facing block the brief never asked for, and 06b §4.1 calls it `settings.cod_max_total` while 08 Q-06 says a constant. See F-09. |
| S-29 | Payment: Bank / JazzCash / Easypaisa manual, account details shown and editable in admin | COVERED | 03 §8.5; 05a §4.9 payments tab; 07 A.5.3 placeholder guard | |
| S-30 | Manual payment: customer enters transaction ID | COVERED | 03 §8.5 item 3; `orders.payment_reference` | |
| S-31 | Manual payment: customer uploads payment screenshot | COVERED | 03 §8.5 item 4; C-25 `storage/proofs/`; 05b §5 | |
| S-32 | Order confirmation page | COVERED | 03 §9 token-gated | |
| S-33 | Confirmation email to customer | COVERED | 06b §5.1 #1; PLAN §8.4 | Only when an email was given — consistent with "email optional". PLAN §8.1 step 2 says "if an address was given" (typo). |
| S-34 | Confirmation email to admin | PARTIAL | 06b §5.1 #2 → `order_notify_email` falls back to `contact_email` | 05a §4.9 seeds both blank; 07 A.5.1 seeds `orders@skyfragrances.com`, a mailbox that may not exist; 02c §7.2 does not say install.php writes the entered admin email into `order_notify_email`. See F-10. |
| S-35 | Track order by order number + phone, status timeline | COVERED | 03 §10; 06b §6; PLAN §5.2 | Tracking-link source still reads `settings.courier_track_url_{slug}` in 03 §10.1 and 06b §5.1 #6 despite Q-09 — see F-12. |
| S-36 | Scent Finder quiz, 4–5 questions, recommends products | COVERED | 03 §11 five questions + scoring; 07 A.6; PLAN §5.2 | |
| S-37 | About page | COVERED | 03 §12.1 `content_pages` | |
| S-38 | Contact page: form saved to DB + WhatsApp | COVERED | 03 §12.2; 05a §4.7 | |
| S-39 | FAQ page | COVERED | 03 §12.1 (patched to `content_pages` + `<h3>` pairs, 08 §4) | |
| S-40 | Shipping page | COVERED | 03 §12.1 | |
| S-41 | Returns & Exchange page | COVERED | 03 §12.1 `/returns` | |
| S-42 | Privacy Policy page | COVERED | 03 §12.1; 07 B.3.11 privacy statement | |
| S-43 | Terms page | COVERED | 03 §12.1 | |
| S-44 | Custom 404 page | COVERED | 03 §13 real 404 status, path never echoed | |
| S-45 | Floating WhatsApp button on every page | COVERED | 03 §1.5 item 6; 04b §16; PLAN §5.1 | 404 page and admin excluded from admin — fine. |

### 1.4 Admin panel (brief §Admin panel)

| # | Requirement | Mark | Where handled | Note |
|---|---|---|---|---|
| A-01 | Secure login: `password_hash` | COVERED | 05a §2.2 bcrypt cost 12 | |
| A-02 | Session regeneration | COVERED | 05a §2.4 step 7; §4.10 on password change | |
| A-03 | CSRF on every form | COVERED | 05a §2.5; 02c §4; PLAN S3 | |
| A-04 | Login rate limiting | COVERED | 05a §2.6–2.7; PLAN S8 | |
| A-05 | Fully mobile-friendly admin | COVERED | 05a §3.2–3.3 bottom tab bar, cards; PLAN §9 | |
| A-06 | Dashboard: today / month revenue | PARTIAL | 05a §4.1 | Every dashboard query reads `orders.placed_at`; the canonical DDL (08 §2.3) has only `created_at`. 06b was patched, 05a was not. See F-05. |
| A-07 | Dashboard: order counts by status | COVERED | 05a §4.1 query 3 | Scoped to the current month — brief is unqualified; acceptable but say so. |
| A-08 | Dashboard: low-stock alerts | COVERED | 05a §4.1 block 6, §6 | |
| A-09 | Dashboard: latest orders | COVERED | 05a §4.1 block 5 | |
| A-10 | Dashboard: best sellers | COVERED | 05a §4.1 block 7 | |
| A-11 | Products: add / edit / delete | COVERED | 05a §4.3, §7.5 | Delete = hide unless zero order lines; PLAN §5.3 says only "delete = hide", understating 05a. |
| A-12 | Products: multiple image upload, auto-resize, JPG/PNG/WEBP only | PARTIAL | 05a §4.3 section 3; 02c §5 | Size cap and derivative widths contradict across 02b §8.3 (5 MB), 02c §5.1 / 05a (8 MB), and PLAN §2 says "to be decided" while PLAN S4 commits to 8 MB. See F-06, F-07. |
| A-13 | Products: sizes with price / sale price / stock / SKU | COVERED | 05a §4.3 section 2 | |
| A-14 | Products: notes | COVERED | 05a §4.3 section 4 | |
| A-15 | Products: gender | COVERED | 05a §4.3 section 1 | |
| A-16 | Products: scent family | COVERED | 05a §4.3; C-04 select from `scent_families` | |
| A-17 | Products: featured / new toggles | COVERED | 05a §4.3 section 5 | |
| A-18 | Products: active / hidden | COVERED | 05a §4.3 section 5 | |
| A-19 | Products: SEO title and description | COVERED | 05a §4.3 section 6 | |
| A-20 | Collections CRUD | COVERED | 05a §4.4 | Collection image 16:9 in 05a §4.4 vs 3:4 card in 04b §3 / 03 §4.3 — see F-15. |
| A-21 | Orders: list with filters / search | COVERED | 05b §1.3–1.4 | |
| A-22 | Orders: order detail | COVERED | 05b §2 | |
| A-23 | Orders: status chain Pending → Confirmed → Packing → Shipped → Delivered / Cancelled | PARTIAL | 05b §3.1 (delivered terminal); 06b §3.2; PLAN §7.4 (delivered → cancelled with stock restore, delivered → shipped 24 h) | PLAN's own diagram contradicts 05b and defers the ruling to stage 4. See F-02. |
| A-24 | Orders: add courier + tracking number | COVERED | 05b §4; `orders.courier_name`, `tracking_number`, `tracking_url` | |
| A-25 | Orders: view payment proof | COVERED | 05b §5; C-25 | |
| A-26 | Orders: mark as paid | COVERED | 05b §6.1 | |
| A-27 | Orders: print invoice | COVERED | 05b §8.2 | HTML print page, not PDF — disclosed (PLAN §13). |
| A-28 | Orders: print packing slip | COVERED | 05b §8.3; Q-14 | |
| A-29 | Orders: restore stock on cancel | COVERED | 05b §6.2; C-12 guard; PLAN §7.5 | Correct for pending → shipped; wrong if delivered → cancelled is allowed (F-02). |
| A-30 | Orders: one-click WhatsApp message to customer | COVERED | 05b §7 five exact messages | |
| A-31 | Orders: export to CSV | COVERED | 05a §5.3; 05b §1.8 | 05b §1.8 route is `/admin/orders/export`, register renames to `/export.csv` — patched. |
| A-32 | Coupons: percent / fixed | COVERED | 05a §4.5 | |
| A-33 | Coupons: min order | COVERED | 05a §4.5 `min_order_total` | |
| A-34 | Coupons: usage limit | COVERED | 05a §4.5; C-15 | |
| A-35 | Coupons: expiry | COVERED | 05a §4.5 `starts_at` / `expires_at` | |
| A-36 | Reviews moderation | COVERED | 05a §4.6 | |
| A-37 | Contact messages | COVERED | 05a §4.7 | |
| A-38 | Newsletter subscribers with CSV export | COVERED | 05a §4.8, §5.2 | |
| A-39 | Settings: store info | COVERED | 05a §4.9 store tab | |
| A-40 | Settings: logo | COVERED | 05a §4.9 `logo_path` image | |
| A-41 | Settings: phone / WhatsApp / email | COVERED | 05a §4.9 contact tab | |
| A-42 | Settings: social links | COVERED | 05a §4.9 `instagram_url`, `facebook_url`, `tiktok_url`, `youtube_url` | |
| A-43 | Settings: announcement text | COVERED | 05a §4.9 `announcement_text` | |
| A-44 | Settings: hero text | COVERED | 05a §4.9 `hero_heading` etc. | |
| A-45 | Settings: shipping fee + free-shipping threshold | COVERED | 05a §4.9 shipping tab; 06a §4.2 | 06a §4.2 seed still says `5000` — patched only in 07 A.5.2 and 08 §1.4. |
| A-46 | Settings: delivery time | COVERED | 05a §4.9 `delivery_time` | |
| A-47 | Settings: enable / disable each payment method | COVERED | 05a §4.9 `cod_enabled` … ; 03 §8.4 | Last method cannot be disabled — sensible guard. |
| A-48 | Settings: bank / JazzCash / Easypaisa account details | COVERED | 05a §4.9 `bank_*`, `jazzcash_*`, `easypaisa_*` | |
| A-49 | Settings: meta description | COVERED | 05a §4.9 `meta_description` | |
| A-50 | Admin account: change password | COVERED | 05a §4.10 `/admin/password` | PLAN §9 table says it "lives in Settings"; PLAN §5.3 says `/admin/password` — wording only. |

### 1.5 Quality requirements (brief §Quality requirements)

| # | Requirement | Mark | Where handled | Note |
|---|---|---|---|---|
| Q-01 | Mobile-first, fully responsive | COVERED | 04a, 04b Part 4; 03 every section has a 375px line | |
| Q-02 | Test at 375px width | COVERED | 07 B.4.8; PLAN §12 every stage; 03 §18 | |
| Q-03 | Fast: lazy-load images | COVERED | 07 B.2.3; PLAN P8 | |
| Q-04 | Fast: minimal JS | COVERED | 04b Part 5 ~11 KB; PLAN P11 | |
| Q-05 | Lighthouse 90+ target | COVERED | 07 B.2.7, B.4.10; PLAN P12 (mobile, live host, 3 runs) | Verifiable as written. |
| Q-06 | SEO: unique titles / meta | COVERED | 07 B.1.1–B.1.2; PLAN P1 | |
| Q-07 | SEO: Open Graph tags | COVERED | 07 B.1.3; PLAN P2 | |
| Q-08 | SEO: Product JSON-LD | COVERED | 07 B.1.4; 03 §6.10; PLAN P3 | |
| Q-09 | SEO: sitemap.xml | COVERED | 07 B.1.7; PLAN P5 | 02b §8.4 says "no cached file"; 07/PLAN say 1-hour cache — PLAN wins, 02b stale. |
| Q-10 | SEO: robots.txt | COVERED | 07 B.1.7; static file | |
| Q-11 | SEO: clean URLs | COVERED | 02b §3; PLAN P6 | |
| Q-12 | SEO: alt text | COVERED | 07 B.1.8; 05a §4.3 required field; PLAN P7 | |
| Q-13 | Security: prepared statements | COVERED | 07 B.3.1; PLAN S1 | |
| Q-14 | Security: output escaping | COVERED | 07 B.3.2; 02c §3.2 `e()`; PLAN S2 | |
| Q-15 | Security: CSRF tokens | COVERED | 07 B.3.3; PLAN S3 | |
| Q-16 | Security: upload validation | COVERED | 07 B.3.4; 02c §5.1 five-step ladder; PLAN S4 | |
| Q-17 | Security: block PHP execution in `/uploads` | COVERED | 02b §5; C-37; 07 B.4.9 test | |
| Q-18 | Security: never expose errors in production | COVERED | 02c §8.1; 02b §8.2 `.user.ini`; PLAN S10 | |
| Q-19 | Stock decremented on order, cannot go negative | COVERED | 06b §1.5; 01c §2–3; C-03; PLAN §7.5 | |
| Q-20 | Prices always recalculated server-side | COVERED | 06a §1.4, §2; 03 §7.4; PLAN §7 | |
| Q-21 | Seed 12 sample perfumes, sky-themed names | COVERED | 07 A.2; PLAN §11.2 | Azure Oud, Cirrus, Aurora Bloom all present. |
| Q-22 | Seed 5 collections | COVERED | 07 A.1; PLAN §11.1 | |
| Q-23 | Seed coupon WELCOME10, 10% off above Rs. 3,000 | PARTIAL | 07 A.3; PLAN §11.3 | Delivered, but with two undisclosed-in-brief restrictions bolted on: 1 use per phone number and a one-year expiry (PLAN §11.3). See F-16. |

### 1.6 How to work (brief §How to work)

| # | Requirement | Mark | Where handled | Note |
|---|---|---|---|---|
| W-01 | Show folder structure, DB schema, page list first; wait for approval | COVERED | PLAN §3, §4, §5; status line "awaiting client approval — no code has been written" | |
| W-02 | Build in stages core+db → storefront → checkout → admin → polish | COVERED | PLAN §12 stages 1–5 (+ stage 6 package) | |
| W-03 | After each stage run locally (PHP built-in server + MySQL/MariaDB), fix errors | COVERED | PLAN §12 preamble; 02c §9 `router.php` | |
| W-04 | Screenshots on desktop and mobile after each stage | COVERED | PLAN §12 "What you will see" column | |
| W-05 | Finally: test orders with every payment method | COVERED | 07 B.4.2; PLAN §12 stage 3, stage 6 | |
| W-06 | Finally: test every admin function | COVERED | 07 B.4.5; PLAN §12 stage 4, stage 6 | |
| W-07 | Deliverable: ZIP ready to upload to Hostinger | PARTIAL | PLAN §1 "What ships", §3, §12 stage 6; 02b §7.1 warns about dotfiles | No spec says how the ZIP is built (dotfiles kept, `router.php`/`.DS_Store`/`__MACOSX` excluded, `config.php` absent, `storage/` present but empty). See F-08. |
| W-08 | Deliverable: non-developer guide — create DB in hPanel, upload files, run install.php, enable SSL, go live | PARTIAL | PLAN §1 table row, §12 stage 6 one-line list; 02b §9 runbook; 02a §5 RewriteBase note | Named in PLAN, owned by no spec; PLAN's step list omits creating the SMTP mailbox before install.php, selecting PHP 8.2, `config.php` 0600, the `site_indexable` switch, and replacing the logo. See F-11. |

---

## 2. Findings, ranked

Severity scale: **blocker** = cannot start the affected stage without a ruling; **high** = a
brief item will ship wrong or contradictory unless fixed before its stage; **medium** = a
builder must guess or a promise cannot be verified; **low** = wording / stale text.

### F-01 · HIGH · Free-shipping bar: two specs compute it from opposite subtotals

- **Where.** 03 §7.5: *"Computed server-side against the **subtotal after any coupon
  discount** — a coupon that drops the order below the threshold must re-add shipping"*, and
  03 §18 `/cart` done-criterion: *"the progress bar uses the post-discount subtotal"*.
  06a §4.1: *"The free-shipping threshold is tested against the PRE-discount subtotal"*.
  PLAN §7.3 calls the pre-discount rule "the settled rule" — but 08 has no C-nn for it and
  03 §7.5 carries no Superseded note, so the storefront builder working from 03 will build
  the opposite of the checkout builder working from 06a.
- **Also stale.** 06a §4.1 worked example and §4.2 seed use threshold Rs. 5,000; 07 B.4.3
  acceptance test reads *"accurate at Rs. 4,999 and Rs. 5,000"* while the register (08 §1.4)
  and PLAN fix the threshold at Rs. 3,000. The acceptance test as written cannot pass.
- **Fix.** Add C-43 to 08 §2: pre-discount (06a §4.1 wins); patch 03 §7.5, §18 and 07 B.4.3
  (2,999 / 3,000) with Superseded lines. One sentence in PLAN §7.3 citing the new C-43.

### F-02 · HIGH · Order state machine: PLAN's diagram lets a delivered order be cancelled and its stock restored

- **Where.** PLAN §7.4 mermaid: `delivered --> cancelled: admin (stock +qty)` and
  `delivered --> shipped: correction (24h)`; PLAN §7.4 table row *"any → cancelled … yes,
  +qty restored once"*. 05b §3.1: *"`delivered` and `cancelled` are terminal and
  irreversible. No transition leaves either."* PLAN §7.4 last paragraph admits *"06b §3.2 and
  05b §3.1 differ on two edges … to be decided in stage 4."*
- **Why it matters.** "Restore stock on cancel" is a brief line. Restoring stock for a parcel
  the customer already has puts phantom bottles back on the shelf; the brief's Returns &
  Exchange flow is the right home for post-delivery reversals, and that is a note + new
  order today (05b §3.1). Leaving the edge open until stage 4 means the checkout transaction
  (stage 3) and the customer email catalogue (06b §5.1) are built against an unknown graph.
- **Fix.** Rule now, in 08 §2 (C-44): 05b §3.1 table is canonical — `delivered` terminal,
  `confirmed → shipped` allowed. Redraw PLAN §7.4 to match and delete the "to be decided"
  sentence. If the owner wants a 24 h undo, make it a separate question in PLAN §14.

### F-03 · MEDIUM · "Notify me" button promised on the product page, but its backend was cut

- **Where.** PLAN §5.2 Product page mobile: *"a sold-out size swaps the button for 'Notify
  me'"*; 03 §6.4 (no Superseded note): `Notify me when back in stock` form posting to
  `POST /api/restock-alert` writing `restock_alerts`; 03 §6.8 sticky bar reads `Notify Me`;
  03 §18 done-criterion *"out-of-stock shows the notify form"*. 08 §4: `restock_alerts`
  **CUT** — *"Capturing an address with no automation is a promise the owner would have to
  keep by hand."*
- **Fix.** Either (a) reinstate a one-column `restock_alerts` table and a read-only admin list
  under Products (so the owner can act on it), or (b) replace the sold-out CTA with the
  out-of-stock WhatsApp message that 03 §6.9 already defines and strike "Notify me" from
  PLAN §5.2, 03 §6.4, §6.8, §18 and 04b §16. (b) is consistent with the register's reasoning.

### F-04 · MEDIUM · Seeded approved reviews are, for a while, exactly the fake reviews the brief forbids

- **Where.** Brief: *"customer reviews (admin-approved only, no fake reviews)"*. 07 A.4 seeds
  five `approved` sample reviews; 07 B.1.4 point 5: *"Sample reviews (`is_sample = 1`) are
  approved rows and do count while they exist"* in `aggregateRating`; 03 §4.9 "Customer
  voices" home band renders approved reviews. Mitigation is a dashboard banner and a
  go-live-blocking acceptance item (07 B.4.7).
- **Why it still matters.** Screenshots the owner shares during stages 2–5, and any indexing
  of a staging copy, carry invented ratings. The mitigation depends on one button being
  pressed.
- **Fix.** Seed all eight reviews as `pending` (the moderation queue is still exercised, and
  B.4.5 already has the owner approve one to test the PDP), **or** exclude `is_sample = 1`
  rows from every storefront query and JSON-LD so they exist only inside the admin. Either
  keeps the build testable without a fake star ever reaching a customer.

### F-05 · MEDIUM · Dashboard queries read a column that does not exist

- **Where.** 05a §4.1 queries 1, 3, 5, 7 and the closing line *"all indexed on
  `orders.placed_at`"*. The canonical `orders` DDL (08 §2.3) has `created_at` only; 06b was
  patched (`placed_at` → `created_at`, 06b line 45) but 05a §4.1 was not, and the register's
  patch log does not list 05a §4.1.
- **Fix.** One Superseded line under 05a §4.1: `placed_at` → `created_at`. Index
  `idx_orders_status_created` already exists in 08 §2.3.

### F-06 · MEDIUM · Four different image-derivative sets; the builder has to pick one

- **Where.** 02c §5.2: 200×250 / 600×750 / 1400×1750 (+ OG). 03 §1.3: `srcset` 400/600/900w;
  03 §6.1: zoom source *"the 1600px derivative"*, `srcset` 600/900/1600w. 05a §4.3:
  400/600/900/1200w WebP + JPEG at 900w. 07 B.2.3 and PLAN P9: 400/600/900/1400. 08 rules
  on none of these. Image widths feed `srcset`, the LCP budget (03 §4.2), the zoom source
  and the upload memory estimate (02b §8.3) — every one of those is a Lighthouse commitment
  in PLAN §10.2.
- **Fix.** Add C-45: 07 B.2.3's 400/600/900/1400 (matches PLAN P9 and 02c's 1400 zoom);
  patch 02c §5.2, 03 §1.3, §6.1, 05a §4.3.

### F-07 · MEDIUM · Upload size cap: PLAN says "to be decided" and "8 MB" in the same document, and 8 MB equals the ini limit

- **Where.** PLAN §2 PHP-limits row: *"exact cap to be decided in stage 1 — 02b §8.3 and
  02c §5.1 differ"*; PLAN §10.1 S4: *"Size caps: 5 MB for a customer proof, 8 MB for an
  admin image"*; 05a §4.3 reject > 8 MB; 02b §8.3 enforce **5 MB** *"well under the 8 MB ini
  value"* because a POST over `post_max_size` arrives *"with `$_POST` and `$_FILES` empty
  and no error"*. With `.user.ini` `upload_max_filesize = 8M` (02b §8.2) an 8 MB app cap
  sits exactly on the ini line, defeating 02b's own argument; and `post_max_size = 12M`
  makes an 8-file multi-upload (05a §4.3 says one request per file, 02c §5.1 says ≤ 10 per
  request) ambiguous too.
- **Fix.** Rule it now (C-46): app caps 5 MB proof / 6 MB admin image, `.user.ini`
  `upload_max_filesize = 10M`, `post_max_size = 12M`, one image per request. Strike the
  "to be decided" sentence from PLAN §2.

### F-08 · MEDIUM · The ZIP is a brief deliverable with no build recipe

- **Where.** Brief: *"a ZIP ready to upload to Hostinger"*. PLAN §3 says only that
  `router.php` is excluded. 02b §7.1 itself warns: *"Some ZIP tools (and macOS 'Compress' via
  Finder) exclude or hide `.htaccess`. If a subdirectory `.htaccess` is missing, nothing
  visibly breaks and … is world-readable — invisible."* The machine building this ZIP is a
  Mac (brief §Verified local environment). Nothing states the ZIP must contain the four
  `.htaccess` files, `.user.ini`, an empty `storage/` tree with its `index.php` stubs, no
  `config.php`, no `.DS_Store`, no `__MACOSX/`.
- **Fix.** Add a "ZIP manifest" subsection to PLAN §12 stage 6 (owned by 02a): exact
  include/exclude list, built with `zip -r` from a clean export, verified by `unzip -l | grep
  htaccess` returning four lines, and a fresh-install rehearsal from that ZIP (PLAN already
  promises the rehearsal — tie it to the artefact).

### F-09 · MEDIUM · Silent scope addition: COD refused above Rs. 30,000

- **Where.** PLAN §7.5 caps: *"COD ≤ Rs. 30,000"*; PLAN §8.1 step 1: *"Disabled above
  Rs. 30,000 ('Orders above Rs. 30,000 are prepaid only.')"*; 06b §4.1. The brief asks for
  Cash on Delivery with no ceiling. Two 100 ml bottles of Azure Oud at the seeded price
  (Rs. 13,950 × 2 = 27,900) plus a 50 ml is over the cap — a plausible gift order is
  refused at checkout. It is listed under PLAN §14.2 Q-06 as a "later" question, i.e. the
  default is taken without the owner having said yes.
- **Also inconsistent.** 06b §4.1 reads the cap from `settings.cod_max_total`; 08 Q-06 says
  it ships as a constant in `cart.php` / `checkout-submit.php`; 05a §4.9 has no such key.
- **Fix.** Move the cap to PLAN §14.1 (blocking) or default it to *off*; if kept, make it a
  Settings › Payments key with `0 = no cap` so the owner can change it without a developer,
  and patch 06b §4.1 / 08 Q-06 to one answer.

### F-10 · MEDIUM · The admin "new order" email may have nowhere to go on day one

- **Where.** Brief: *"email to customer and admin"*. 06b §5.1 #2 sends to
  `settings.order_notify_email`, falling back to `contact_email` (08 §1.4). 05a §4.9 seeds
  both blank; 07 A.5.1 seeds `contact_email = orders@skyfragrances.com`, a mailbox that
  exists only if the owner creates it. 02c §7.2 step 3 collects *"email, password, store
  name, WhatsApp number"* for the admin account but never says that email is written to
  `order_notify_email`. 05b §3.2 still sends the admin copy to `settings.admin_email` (a
  losing spelling per 08 §1.4, not patched in 05b).
- **Fix.** 02c §7.2 step 3: install.php writes the admin email into `order_notify_email`
  and `contact_email` when they are blank. Patch 05b §3.2. Add "send a test order and confirm
  the owner email arrives" to the guide (B.4.2 already has it for the acceptance run).

### F-11 · MEDIUM · The non-developer go-live guide is named but not specified

- **Where.** Brief: *"a simple step-by-step guide (for a non-developer) to create the
  database in hPanel, upload the files, run install.php, enable SSL and go live."* PLAN §1
  table and §12 stage 6 list it; 02b §9 is a post-deploy runbook of URLs; 02a §5 and 02b §8.2
  each say "the go-live guide documents/repeats this". No spec section owns the guide's
  contents, and PLAN §12 stage 6's list omits steps the specs themselves require:
  1. Create the `@skyfragrances.com` mailbox in hPanel **before** running install.php,
     because step 2 of the installer asks for SMTP credentials (02c §7.1, PLAN §13).
  2. Select PHP 8.2 in hPanel — install.php refuses below 8.2 (02c §7.2 step 1); Hostinger
     accounts commonly default to an older 8.x.
  3. Set `config.php` to 0600 in File Manager (02b §7.2).
  4. Check the four `.htaccess` files survived extraction and delete `__MACOSX` (02b §7.1).
  5. Turn `site_indexable` on if the site was staged on a temporary host (02a §5 item 10).
  6. Replace the `REPLACE ME` bank details and upload the real logo / hero images.
  7. The `RewriteBase` line for a sub-folder install (02a §5 item 5).
- **Fix.** Give the guide an owner (07, as a new Part C) with a numbered outline mirroring
  install.php's screens plus the seven items above, and list that outline in PLAN §12 stage 6
  so the client can approve the deliverable's shape now.

### F-12 · LOW · Tracking link: two specs still read a settings key the register removed

- **Where.** 03 §10.1: *"only if `settings.courier_track_url_{slug}` holds a URL pattern"*;
  06b §5.1 #6: *"courier's own tracking URL if `settings.courier_track_url_{slug}` is set"*.
  08 Q-09 and the `orders` DDL: `orders.tracking_url`, *"No courier registry."* 03 §16 was
  patched; 03 §10.1 and 06b §5.1 were not.
- **Fix.** Superseded lines under 03 §10.1 and 06b §5.1 #6 pointing at `orders.tracking_url`.

### F-13 · LOW · install.php: self-delete or ask-to-delete, and which lock file

- **Where.** 02c §7.2 Finish: red panel *"Delete `install.php` now"* + "Check now" button,
  lock `storage/.installed`; PLAN §2 Installer row: *"refuses to ever run again and tells
  you to delete it"*. 02b §7.4: *"`install.php` must delete itself on success
  (`unlink(__FILE__)`)"*; 07 A.7: *"`install.php` deletes **itself** … or … writes
  `db/.installed`"*; 07 B.4.1: *"`install.php` is gone (or `db/.installed` exists …)"*.
  `db/` is a denied, read-only tree (08 C-24) — a lock there contradicts "only two folders
  are ever written to" (PLAN §3).
- **Fix.** C-47: try `unlink(__FILE__)` first, then show the 02c panel if it failed; lock is
  `storage/.installed` only. Patch 02b §7.4, 07 A.7, 07 B.4.1.

### F-14 · LOW · Announcement bar: dismissal store and two settings keys disagree

- **Where.** 03 §4.1: `localStorage.sf_ann_v` = hash of the text; 08 Q-15 / PLAN §13:
  *"dismissal lasts one browser session"* (`sessionStorage`). 03 §4.1 lists
  `announcement_bg` and `announcement_fg` (hex); 05a §4.9 defines `announcement_enabled`,
  `_text`, `_link` only.
- **Fix.** Q-15 wins (sessionStorage); drop `_bg`/`_fg` from 03 §4.1 (the gold-on-black bar is
  a design token, not a setting).

### F-15 · LOW · Collection image ratio: 16:9 in admin, 3:4 on the storefront

- **Where.** 05a §4.4: *"`image` (single upload … 16:9, used on the collection hero and the
  home tiles)"*. 03 §4.3: home rail tiles at 3:4; 04b §3: *"Collection card … 3:4 image"*;
  PLAN §6.3. One upload cannot serve both without a second crop, and 02c §5.2 generates only
  4:5 and 1.91:1 derivatives.
- **Fix.** Decide one (3:4 for cards; the collection landing hero can reuse it with a
  scrim) and add the derivative to 02c §5.2.

### F-16 · LOW · Silent scope changes to WELCOME10 and to the coupon seed

- **Where.** Brief: *"coupon WELCOME10 (10% off above Rs. 3,000)"*. PLAN §11.3: *"limited
  to 1 use per phone number, valid one year"*; 07 A.3 adds two more coupons (`EIDSALE500`
  expired, `FIRST50` exhausted). The per-phone limit and expiry are sensible but they are
  restrictions on the one coupon the owner specified; the owner may plan to hand WELCOME10
  to repeat customers.
- **Fix.** One line in PLAN §14.2: "WELCOME10 is seeded as one-per-phone and expires in a
  year — say if you want it unlimited." The extra demo coupons are fine as sample data.

### F-17 · LOW · Wording and stale text a builder or the client will trip on

| Where | Text | Problem |
|---|---|---|
| PLAN §8.1 step 2 | *"confirmation email if an address was given"* | Should read "if an email address was given". |
| PLAN §9 table, Settings row | *"Change password … lives here"* | PLAN §5.3 and 05a §4.10 put it at `/admin/password`, not on a Settings tab. |
| PLAN §6.5 last paragraph | focus-ring colour, meter labels, button radius *"to be decided in stage 2"* | Three open design tokens in a plan submitted for approval; 04a is already declared canonical (C-33) — just say 04a wins. |
| 07 B.2.4 | *"One `assets/js/site.js` … under 15 KB"* | C-31 made it five files; 07 not patched. |
| 02b §8.4 | sitemap *"no cron and no cached file"* | 07 B.1.7 / PLAN P5 cache it for an hour. |
| 06b §5.2 items 4–5 | hourly cron drains the outbox; admin-page drain is a *"1-in-20 chance"* safety net | PLAN §2 and §13 say no cron and "retried when you next open the admin panel". State the drain rule once (every admin load, capped at N rows) and patch 06b. |
| 05b §3.2 / 06b §5.2 / 02c §6 | SMTP timeout 6 s / 8 s / 15 s | Pick one; PLAN §8.4 says 8 s. |
| 05a §4.1 query 3 | counts by status *"WHERE placed_at >= :month_start"* | Brief's "order counts by status" is unqualified; say "this month" on the dashboard chip row or drop the date bound. |
| PLAN §5.3 Product form row | *"delete = hide"* | 05a §7.5 does hard-delete a never-ordered product; PLAN understates the behaviour the owner will see. |
| 06a §4.2 | `free_shipping_threshold` seed `5000` | Register says 3000; 06a not patched. |

---

## 3. Scope expansion beyond the brief (for the owner to see in one place)

None of these is wrong; each is a thing the owner did not ask for and is paying build time for.
The register (08 §4) already lists most and gives reasons; PLAN §14 should surface the ones a
customer will feel.

| Addition | Customer-visible? | Disclosed in PLAN §13/§14? | Recommendation |
|---|---|---|---|
| COD ceiling Rs. 30,000 (F-09) | Yes — blocks a checkout | §14.2 Q-06 as a "later" default | Make blocking, or default off. |
| WELCOME10 one-per-phone + one-year expiry (F-16) | Yes | §11.3 only | Add to §14.2. |
| 5 orders/phone/day flagged; 10 per line; 20 lines per cart | Mostly invisible | §13 | Fine. |
| `/scent/{slug}`, `/new-arrivals`, `/best-sellers`, `/sale`, `/collections` landing pages | Yes (more pages) | §5.1 | Fine — SEO goal. |
| Header search suggestions | Yes | §5.1 | Fine. |
| "Customer voices" home band | Yes | 08 §4 only | Add to §5.2 Home as optional (it is: `home_reviews_enabled`). |
| Maintenance mode, activity log, order notes, bulk actions, slug redirects, remove-sample-data tool, password-recovery file | Admin only | §2, §9, §13 | Fine — all serve "manage from my phone". |
| Three seeded coupons instead of one | Admin only | §11.3 | Fine. |
| Eight seeded reviews (F-04) | Yes until removed | §11.4 | Seed as pending. |

## 4. Promises that cannot be verified as written

| Promise | Where | Why unverifiable | Make it checkable |
|---|---|---|---|
| Free-shipping bar accurate at Rs. 4,999 / 5,000 | 07 B.4.3 | Threshold is 3,000 | Change to 2,999 / 3,000. |
| "Every checkbox in 07 B.4 passes on the live host" | PLAN §12 stage 6 | B.4.1 checks `db/.installed`; B.4.9 checks `/includes` and `/logs`, directories that no longer exist (C-23) | Patch B.4.1, B.4.9 to `storage/.installed`, `/app`, `/storage`. |
| "Lighthouse mobile ≥ 90 … measured three times on the live host" | PLAN P12 | Fine as written — keep | — |
| "At most 9 database queries" on the home page | PLAN §5.2 | Fine — countable | — |
| "JavaScript totals about 11 KB in five files" | PLAN §6.4 | 07 B.2.4 says one file under 15 KB | Patch 07 B.2.4. |

## 5. Too vague to build without guessing (not already covered above)

| Item | Where | What is missing |
|---|---|---|
| Email outbox drain on admin page load | PLAN §2, §8.4; 06b §5.2 | Every load or 1-in-20? How many rows per drain? What is the per-request time budget so an admin page does not hang for 8 s × 20 rows? |
| Newsletter no-JS fallback | 03 §4.11 *"posts to `/contact#newsletter`"* | PLAN §5.1 has only `POST /api/newsletter` (JSON). Which route handles the non-JS form post and what does it render? |
| Proof cleanup "90 days after delivery/cancellation by an admin tool" | PLAN S12 | No such tool in the admin route table (§5.3) or 05a/05b; `remove-sample-data` is the only tool. Either add `/admin/tools/purge-proofs` or drop the promise from S12 and the privacy page. |
| `.user.ini` `session.cookie_samesite = "Lax"` vs admin `Strict` | 02b §8.2; 05a §2.3 | `.user.ini` sets one value for both cookies; 05a overrides in PHP before `session_start()` — say so in 02b or a builder will "fix" the ini. |
| Dashboard "red warning if any email failed to send" | PLAN §1 | 06b §5.2 says after 6 attempts; PLAN §8.4 agrees — fine, but the admin route table has no screen for the failed list the banner "links to". |

---

## 6. Verdict

The plan covers every line of the brief; nothing the owner asked for is absent. It is not
yet safe to hand to a builder because the register left five real contradictions un-refereed
(free-shipping rule, state-machine edges, Notify-me vs the cut table, image derivative widths,
upload caps) and PLAN.md itself carries three "to be decided in stage N" sentences plus a
diagram that contradicts its own spec. Fix F-01, F-02, F-03 and F-11 before asking for
approval; the rest are one-line register patches that can land with them.
