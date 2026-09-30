# 08 — Decisions Register

Fourteen specification units were written in parallel against `00-brief.md`. They disagree in
places. This document is the referee: every contradiction below has exactly one answer, and the
losing passages in the other documents carry a one-line `Superseded by 08-decisions-register.md
§2 — …` note beneath them. When any spec and this register differ, **this register wins**.

Reading order for a builder: `00-brief.md` → this file → the spec that owns the area being built.

---

## 1. Canonical names

Name → definition → owning document. A name that appears in more than one spec under two
spellings is listed once here with the spelling that survives.

### 1.1 Directories (one tree, inside `public_html`)

| Path | What lives there | Owner |
|---|---|---|
| `index.php` | Storefront front controller — the only routable storefront PHP file | 02a §3 |
| `install.php` | One-time installer; refuses to re-run; deletes itself, else tells the owner to delete it (C-47) | 02c §7.2 |
| `cron.php` | Optional outbox accelerator (CLI or `?key=`); the site never depends on it (C-56) | 06b §5.2 |
| `config.php` | **Root-level**, `return [...]` array (env, base_url, db, smtp, security). Written by `install.php` from `config.sample.php`. Denied by a root `<Files "config*.php">` rule. | 02c §7.1 |
| `.htaccess`, `robots.txt`, `favicon.ico` | Root statics | 02b §3 |
| `app/` | All application PHP. **Denied.** `bootstrap.php`, `routes.php`, `router.php`, `routes-admin.php` | 02a §2 |
| `app/lib/` | First-party function libraries, one file per noun: `db`, `request`, `response`, `view`, `url`, `money`, `text`, `csrf`, `session`, `cart`, `settings`, `seo`, `image`, `upload`, `mail`, `auth`, `flash`, `pagination` | 02a §2 |
| `app/lib/vendor/PHPMailer/` | The only third-party PHP (4 files + LICENSE) | 02a §7 |
| `app/controllers/`, `app/controllers/api/` | One file per route handler | 02a §6 |
| `app/views/`, `app/partials/`, `app/emails/` | Views, shared fragments (incl. `partials/icon.php`), email bodies | 02a §2 |
| `admin/` | Admin area: own `index.php` front controller, routed from the **root** `.htaccess` (C-54); `admin/.htaccess` is headers only. `controllers/`, `views/`, `partials/` are **denied** (deny `.htaccess` + stub + guard); `admin/index.php` is session-gated. | 02a §3.7, 05a §1 |
| `app/tools/reset-password.php` | Password-recovery script; copied to the root by the owner only when needed (C-64) | 05a §2.8 |
| `assets/.htaccess` | `ErrorDocument 404 "Not found"` + cache headers, so a missing asset never boots PHP (C-54) | 02b §5 |
| `assets/css/site.css` | THE stylesheet | 02a, 04a, 07 |
| `assets/js/` | `reveal.js`, `ui.js`, `cart.js`, `product.js`, `forms.js` (storefront, ~11 KB) + `admin.js` | 04b Part 5 |
| `assets/fonts/` | 5 self-hosted woff2: Cormorant Garamond 300/400, Jost 300/400/500 | 04a §3.2–3.3 |
| `assets/img/` | logo, og-default, placeholder SVG, icons | 02a §2 |
| `uploads/` | **Public read, no PHP.** The only public runtime-written tree: `products/{id}/`, `collections/`, `og/`, `settings/` | 02b §5, 02c §5.4 |
| `db/` | `schema.sql`, `seed.sql`, `sample-manifest.php`. **Denied.** | 02a §2, 07 A.7 |
| `storage/` | **Denied, writable.** `logs/`, `cache/` (`settings.json`, sitemap), `sessions/shop/`, `sessions/admin/` (C-53), `proofs/{YYYY}/{MM}/`, `.installed` lock, optional `MAINTENANCE` flag file (C-70). Its `.htaccess` carries a `<FilesMatch ".">` fallback (C-74). | 02a §2, 06a §1.1, 06b §4.2 |

There is **no** `/includes`, no `/lib`, no `/config` directory. The denied directories are exactly
three: `app/`, `db/`, `storage/` (each with its own `.htaccess` + `index.php` stub + the
`defined('SKYFR') || exit;` guard in every PHP file) — plus, since C-54, the three admin
subdirectories `admin/controllers/`, `admin/views/`, `admin/partials/`, which get the same three
layers. `install.php` writes all ten `.htaccess` files from string literals (C-47).

### 1.2 Tables

Catalogue tables are exactly `07 §0.2` plus the `01a §1.4` additions. Commerce tables are `01b`
with the amendments in §2 of this register. The full list, in creation order:

| Table | Notes | DDL owner |
|---|---|---|
| `settings` | PK `setting_key`; no `id` column | 01a §2.1 |
| `admin_users` | 01a DDL **without** the `role` column (single owner account in v1), **plus** `known_devices TEXT NULL` (C-51) | 01a §2.2 |
| `admin_login_attempts` | 07 contract + 05a's three indexes | 07 §0.2, 05a §0.3 |
| `admin_activity_log` | 05b's column set (`admin_id`, `admin_username`, `entity_type`, `entity_id`, `action`, `summary`, `before_json`, `after_json`, `ip_hash`, `user_agent`, `created_at`). Replaces 01a's `activity_log`. | 05b §0.2 |
| `collections`, `scent_families`, `products`, `product_sizes`, `product_images`, `reviews` | Catalogue | 01a §3 |
| `content_pages` | Replaces 03's `pages` | 01a §2.6 |
| `slug_redirects` | `entity_type` ∈ `product\|collection\|scent\|page` | 01a §2.7 |
| `contact_messages` | 05a DDL + `order_number VARCHAR(20) NULL` | 05a §0.3 |
| `newsletter_subscribers` | 05a DDL (`status`, `source`, `unsub_token`) | 05a §0.3 |
| `quiz_questions`, `quiz_options`, `quiz_option_scores` | Scent Finder | 01a §4 |
| `coupons` | 07 contract + `per_phone_limit SMALLINT UNSIGNED NULL` | 01b §6 |
| `orders` | 01b DDL as amended in §2 (C-07…C-12) | 01b §1 |
| `order_items` | 01b DDL; quantity column is `quantity` | 01b §2 |
| `order_status_history` | 01b DDL (`field`, `from_status`, `to_status`, `changed_by`, `admin_id`, `admin_username`) | 01b §3.2 |
| `payment_proofs` | 01b DDL, FK `order_id` directly (the `payments` table is cut); `file_path` nullable and `purged_at DATETIME NULL` (C-57, C-65) | 01b §5.1 |
| `coupon_redemptions` | 01b DDL (`phone_normalized`, `discount_amount`, `order_subtotal`) | 01b §6 |
| `order_notes` | Internal admin annotations | 01b §8 |
| `email_outbox` | Replaces 02c's `email_queue` | 06b §0.2 |
| `rate_limits` | `(id, bucket, subject_hash, attempted_at, was_success)`; buckets `checkout\|track\|proof\|contact\|newsletter\|review\|coupon` (`coupon` added by C-66) | 06b §0.2 |

**Cut** (do not create): `admin_sessions`, `payments`, `checkout_attempts`, `order_number_seq`,
`order_stock_moves`, `cities`, `faqs`, `usp_items`, `instagram_posts`, `restock_alerts`,
`search_queries`, `spam_log`. Reasons in §2 and §4.

Enumerations (short strings, PHP constants, never MySQL `ENUM`):
`products.gender` ∈ `him|her|unisex` · `reviews.status` ∈ `pending|approved|rejected` ·
`coupons.type` ∈ `percent|fixed` · `orders.status` ∈ `pending|confirmed|packing|shipped|delivered|cancelled` ·
`orders.payment_method` ∈ `cod|bank|jazzcash|easypaisa` ·
`orders.payment_status` ∈ `unpaid|awaiting_verification|paid|failed|refunded` ·
`order_status_history.field` ∈ `status|payment_status`, `.changed_by` ∈ `customer|admin|system` ·
`contact_messages.status` ∈ `new|read|replied|archived` · `newsletter_subscribers.status` ∈ `subscribed|unsubscribed`.

### 1.3 Routes that appear in more than one document

| Route | Canonical form | Notes | Owner |
|---|---|---|---|
| `/for-him`, `/for-her`, `/unisex` | preset `gender=him` / `her` / `unisex` | Presets carry the **stored** token; there is no men/women→him/her map anywhere | 03 §2 (patched) |
| `/scent/{slug}` | slug from `scent_families.slug` | Join to products on `scent_families.name = products.scent_family` | 01a §3.2 |
| `/order/{order_number}?t={access_token}` | order number regex `^SF-\d{6}-[A-HJ-NP-Z2-9]{4}$` | Token-gated confirmation page | 06b §0.3 |
| `/track` (GET, POST) | order number + phone, normalised to `+92XXXXXXXXXX` | Rate-limited in `rate_limits` bucket `track` | 01b §9, 06b §6 |
| `/admin/orders/{order_number}/proof` | GET, admin-session-gated stream from `storage/proofs/` | Replaces 02c's `/admin/proof.php?order=` | 05b §0.4 |
| `/admin/orders/export.csv` | one row per order, UTF-8 BOM, ≤5,000 rows | 05b route 2 renamed to match 05a route 36 | 05a §5.3 |
| `/admin/login`, `/admin/logout` (POST) | `admin/controllers/auth.php` | | 05a §1 |
| `/sitemap.xml` | generated by `app/controllers/sitemap.php`, 1-hour file cache in `storage/cache/` | must not exist as a static file | 02a §2, 07 B.1.7 |
| `/robots.txt` | real static file | | 02a §2 |
| `/api/cart`, `/api/cart/add|update|remove|coupon`, `/api/search-suggest`, `/api/newsletter` | JSON only (`/api/newsletter` also answers a non-JS form POST with a 303 — F-17) | | 03 §2 |
| `/admin/tools`, `POST /admin/tools/remove-sample-data`, `POST /admin/tools/purge`, `/admin/tools/regenerate-images` | Tools index, sample-data removal (07 A.7), privacy purge (C-65), batched derivative regeneration (C-73) | | 05a §1 routes 38–41 |
| `POST /admin/orders/cancel-unpaid`, `POST /admin/orders/{order_number}/stock-back` | Bulk cancel of unpaid transfer orders (C-58); explicit stock restore for an order cancelled from `shipped` (C-48) | | 05b §0.4 routes 13–14 |
| `POST /admin/settings/https-permanent` | Flips the root HTTPS redirect from 302 to 301 after a self-check (C-52) | | 05a §1 route 42 |

### 1.4 Settings keys

`05a §4.9` is the authoritative key list, group assignment and defaults. Code-owned defaults live
in `app/lib/settings.php` (not `/includes/settings-defaults.php`). Spellings that lose:

| Loser | Canonical | Seen in |
|---|---|---|
| `whatsapp_number` | `whatsapp` | 01a §5.3 |
| `store_phone`, `phone` | `contact_phone` | 07 A.5.1, 03 §16 |
| `store_email`, `email`, `admin_email` | `contact_email` (customer-facing) / `order_notify_email` (admin notifications, falls back to `contact_email`) | 07, 03, 05b, 06b |
| `pay_cod_enabled`, `pay_bank_enabled`, `pay_jazzcash_enabled`, `pay_easypaisa_enabled` | `cod_enabled`, `bank_enabled`, `jazzcash_enabled`, `easypaisa_enabled` | 03 §16, 06b |
| `payment_instructions_note` | `manual_payment_note` | 06b |
| `delivery_days` | `delivery_time` | 05b |
| `bank_account_details` | the four `bank_*` keys | 01a §5.3 |
| `asset_version` / `ASSET_VERSION` | **cut** — cache-busting is `?v=filemtime()` | 02a, 02b |

Keys **added** to 05a §4.9 by this register: `site_indexable` (advanced, bool, default `1`;
`install.php` sets `0` when the host is not `skyfragrances.com`), `business_hours` (contact, text),
`instagram_tile_1_image … instagram_tile_6_image` and `instagram_tile_1_url … _6_url` (home, image/url).
`free_shipping_threshold` seeds `3000.00` (05a), not `5000.00` (07 A.5.2).

Keys **added** by the hardening review (§2.4), all defined in 05a §4.9: `cod_max_total` (payments,
money, `0` = no cap — C-69), `manual_hold_hours` (payments, int, `48` — C-58), `base_url`
(advanced, url, blank = `config['base_url']` — C-49), `trusted_proxies` (advanced, CIDR list written
by `install.php` — C-50), `maintenance_bypass` (advanced, read-only secret — C-70/C-76),
`https_permanent` (advanced, read-only flag with an action button — C-52). `install.php` writes the
owner's email into `order_notify_email` and `contact_email` when blank (C-47). There are **no**
`announcement_bg` / `announcement_fg` keys (F-14) and no `courier_track_url_*` keys (Q-09).

### 1.5 Helper functions (all in `app/lib/`, plain functions, file-prefixed where noted)

| Function | Signature / behaviour | File | Owner |
|---|---|---|---|
| `e()` | `htmlspecialchars($v, ENT_QUOTES \| ENT_SUBSTITUTE, 'UTF-8')`; every view echo | `text.php` | 02c §3.2 |
| `ejs()`, `eu()` | JSON-in-script and URL-segment escapes | `text.php` | 02c §3.2 |
| `money()` | `money(string $value, bool $withDecimals = false): string` → `Rs.` + NBSP + `number_format(..., 0)`; the single `(float)` cast in the codebase; never rounds | `money.php` | 01c §1.3 |
| `money_attr()` | bare `4950.00` for JSON-LD / `itemprop` | `money.php` | 01c §1.3 |
| `money_paisa()`, `money_from_paisa()` | boundary conversions; all arithmetic is integer paisa | `money.php` | 06a §0 |
| `effective_price()` | `COALESCE(sale_price, price)` in one place | `money.php` | 07 §0.2 |
| `url()`, `asset()`, `canonical()` | the only way to emit a path; `asset()` appends `?v=filemtime` | `url.php` | 02a §5 |
| `csrf_token()`, `csrf_field()`, `csrf_check()` | one token per session, rotated on login/logout/password change | `csrf.php` | 02c §4 |
| `setting()`, `setting_int()`, `setting_bool()`, `setting_money()` | typed accessors over defaults-merged array; `'REPLACE ME'` and `''` count as unset | `settings.php` | 01a §5.3, 07 A.5.3 |
| `phone_normalize()` | digits only → drop leading `0` → drop leading `92` → prefix `+92`; used by checkout, track, coupon-per-phone, WhatsApp links | `text.php` | 01b §1.1 |
| `cart_*()` | session cart: `{size_id, qty}` lines + one coupon code | `cart.php` | 06a §1 |
| `render($view, $data)`, `abort(404)` | view + layout; 404 renders inside full layout | `view.php`, `response.php` | 02a §3.5 |
| `log_write($level, ...)` | `storage/logs/app-YYYY-MM.log`; production threshold is `warning` | `bootstrap.php` | 02c §8.1 |
| `client_ip()` | Rightmost untrusted `X-Forwarded-For` hop only when `REMOTE_ADDR` ∈ `config['trusted_proxies']`, else `REMOTE_ADDR`; the only source for every `ip_hash` (C-50) | `request.php` | 02c §1 step 3a |
| `request_origin()` | Scheme (three-signal HTTPS detection) + `://` + lower-cased `HTTP_HOST`; the only comparand for Origin/Referer checks (C-49) | `request.php` | 02c §4 |
| `cart_price()` | The single pricing pipeline (06a §2.2), called on every render and inside the order transaction (C-43) | `cart.php` | 06a §2.2 |
| `sanitize_html()` | `DOMDocument` allow-list sanitiser for `content_pages.body`, applied on save and on render (C-62) | `text.php` | 02c §3.2 |

No classes, no autoloader, no namespaces for first-party code (02a §6). 02c's `Db::`, `Router::`
and `App\Filters::` are read as the equivalent `db_*`, `router_*` functions.

---

## 2. Conflicts resolved

Each row: the positions taken, the ruling, a one-line reason, and where the losing text was patched.
IDs (C-nn) are what the `Superseded by` notes in the specs refer to. §2.1–2.3 are the original
reconciliation; **§2.4 (C-43 … C-78) records the hardening review** that followed and wins over
§2.1–2.3 where they touch the same point (e.g. C-26's single sessions directory → C-53).

### 2.1 Money, schema and storage shape

| ID | Topic | Positions | RESOLUTION | Reason | Patched |
|---|---|---|---|---|---|
| C-01 | Money storage | 03 §1.4 "unsigned integers in whole rupees" vs `DECIMAL(10,2)` in 01a/01b/01c/02c/05a/05b/06a/06b/07 | **`DECIMAL(10,2) UNSIGNED`**; PHP arithmetic in integer paisa; one rounding step (percent-coupon discount, half-up to the rupee); display `Rs. 4,950` | Seed already ships `.00` literals; SQL DECIMAL is exact; display rule unchanged | 03 §1.4 |
| C-02 | Money helper name/location | 02a `money_fmt()`; 01c `money()` in `/includes/format.php`; 02c `money(string\|int)` | `money(string $value, bool $withDecimals=false)` + `money_attr()` in `app/lib/money.php` | 01c defines the contract; 02a defines the tree | 02a §6, 01c §1.3, 02c §3.4 |
| C-03 | `product_sizes.stock` signedness | 01a signed `INT` ("fail loudly") vs 01c §5.10 / 07 B.3.10 `INT UNSIGNED` | **`INT UNSIGNED`** | Strict mode on MySQL 8 and MariaDB 10.4 raises an out-of-range error, not a clamp — so UNSIGNED *is* the loud failure and a second wall behind the `stock >= :qty` guard | 01a §3.4 |
| C-04 | `scent_families` table vs string | 07 §0.2 string column only; 01a table + string; 03 slug-matches in PHP against DISTINCT | **Both**: `products.scent_family VARCHAR(60)` stays the seed-written display name; `scent_families(name UNIQUE, slug, intro, seo_*)` provides `/scent/{slug}`; admin `<select>` is populated from the table; seed inserts the five family rows before products | Route 8 needs slug + copy + SEO fields; the seed and SEO streams keep writing a string | 03 §5.0 note only |
| C-05 | Admin session storage | 01a `admin_sessions` table (revoke, survive GC) vs 05a PHP-only sessions | **PHP-only sessions**, files in `storage/sessions/`, names `SFADMIN` (path `/admin`) and `SFSHOP` (path `/`) | One operator; an app-owned save path removes the GC-sweep argument; password change already regenerates the id; two fewer queries per request | 01a §1.3, §2.4, §5.4 |
| C-06 | Admin timeouts | 05a 60 min idle / 12 h absolute; 07 2 h / 12 h; 01a 12 h / 14 d | **120 min idle, 12 h absolute** | Owner manages orders from a phone across a working day; 60 min forces several re-logins, 14 days is too long for a shared host | 05a §2.4 |
| C-07 | `orders` header columns | 01b (`phone`, `phone_normalized`, `order_status`, `stock_restored`); 05b (`customer_phone`, `status`, `stock_restored_at`); 06b (`customer_phone`, `phone_hash`, `idempotency_key`); 03/06a variants | **01b DDL with these edits**: `phone`→`customer_phone`, `email`→`customer_email`, `order_status`→`status`, `stock_restored`→`stock_restored_at DATETIME NULL`; add `idempotency_key CHAR(32) NOT NULL UNIQUE`, `payment_reference VARCHAR(64) NULL`, `payment_account_snapshot VARCHAR(160) NULL`, `paid_at DATETIME NULL`; keep `phone_normalized`, `cod_fee`, `item_count`, `coupon_*` snapshots, `source` | 01b owns commerce DDL; the four-doc majority spelling wins on column names; a timestamp is a flag plus an audit in one column | 01b §1.1, 05b §0.2, 06b §0.2, 03 §16, 06a §5.2 |
| C-08 | `payments` table | 01b one-row-per-order `payments` + `payment_proofs`; 05b/06b/03 columns on `orders` | **Cut `payments`**; `payment_proofs.order_id` references `orders` directly and carries `transaction_ref`, `sender_name`, `amount_claimed`, `review_status`, `reviewed_by`, `reviewed_at`, `review_note` | A UNIQUE(order_id) table is a column set wearing a table's clothes; the receiving-account snapshot survives as `orders.payment_account_snapshot` | 01b §5, §10 |
| C-09 | `payment_status` vocabulary | 01b `unpaid\|awaiting_verification\|paid\|failed\|refunded`; 05b `awaiting_review`; 06b `proof_submitted`; 01c `unpaid\|paid\|refunded`; 03 `pending` | **01b's five values** | Richest set; `failed` = proof rejected is a state the admin flow needs | 05b §0.2, 06b §0.2, 01c §5.8, 03 §8.5 |
| C-10 | Order number format | 01b `SF-YY-NNNNNN` sequential via `order_number_seq`; 05b `SF-YYMMDD-NNNN` per-day counter; 06b `SF-YYMMDD-XXXX` random base32; 07 `SKY-YYYYMM-6rand`; 03 `SF-YYMM-5` | **`SF-YYMMDD-XXXX`**, 4 random chars from `A-HJ-NP-Z2-9`, UNIQUE + retry; **cut `order_number_seq`** | 07 B.3.11 requires non-sequential (no volume leak, no enumeration); date prefix still sorts and reads over the phone; no counter table | 01b §0, §4; 05b §0.3; 07 B.3.11; 02a §4.1 |
| C-11 | Double-submit guard | 01b `checkout_attempts` table + state; 06b `orders.idempotency_key UNIQUE` | **`orders.idempotency_key` UNIQUE**; duplicate-key catch redirects to the existing order; **cut `checkout_attempts`** | The order row is its own idempotency record | 01b §7, §10 |
| C-12 | Stock-restore ledger | 01c `order_stock_moves` + `stock_committed`; 01b/05b flag on `orders` | **No ledger**; restore reads `order_items(product_size_id, quantity)`; the guard is `UPDATE orders SET status='cancelled', stock_restored_at=:now WHERE id=:id AND stock_restored_at IS NULL` with `rowCount()===1` | `product_sizes` are soft-deleted (01a §1.3) so `product_size_id` never goes NULL in practice; `order_items` is frozen | 01c §3, §5.12, §7 |
| C-13 | Shipping/tax columns | 06a `shipping_total`, `shipping_is_free`, `tax_total`; 01b `shipping_fee`, `cod_fee` | **`shipping_fee`, `cod_fee` (0.00)**; no `tax_total`, no `shipping_is_free` (fee `0.00` means free) | Tax is out of scope; adding a DECIMAL column later is one ALTER the same as `cod_fee` argued for | 06a §0, §5.2 |
| C-14 | Phone matching column | 01b `phone_normalized` (`+92…`); 06a/06b `phone_hash` HMAC over last 10 digits | **`phone_normalized`**, format `+923001234567` | The admin needs the plaintext anyway; an indexed normalised column serves track, coupon-per-phone and repeat-customer lookups without a pepper | 06b §0.2, §0.3; 06a §3.6 |
| C-15 | `coupons.usage_limit` unlimited sentinel | 01b `NULL`; 07 A.3 `0` | **`NULL` = unlimited** | 01b owns the column; `IS NULL OR used_count < usage_limit` is one predicate | 07 A.3 |
| C-16 | Email queue table | 02c `email_queue`; 06b `email_outbox` | **`email_outbox`** (06b DDL) | 06b ships the full DDL and drain rules | 02c §6.4 |
| C-17 | `rate_limits` shape | 03 `(bucket, ip_hash, window_start, hits)`; 06b `(id, bucket, subject_hash, attempted_at, was_success)` | **06b shape** | Append-only rows share the `admin_login_attempts` pattern and the same opportunistic purge | 03 §16 |
| C-18 | Activity log | 01a `activity_log(admin_user_id, action words)`; 05a/05b `admin_activity_log(admin_id, dotted actions)` | **`admin_activity_log`**, 05b column set, dotted action vocabulary; `admin_id` is deliberately not an FK (01c §5.4 exception noted) | Two admin docs consume it; 05b has the richer before/after JSON | 01a §2.5, 05a §0.3, 01c §5.4 |
| C-19 | Content pages table | 03 `pages`; 01a `content_pages` | **`content_pages`** | 01a owns the DDL | 03 §12.1, §16 |
| C-20 | `contact_messages` / `newsletter_subscribers` | 01a (`is_archived`; `status`), 05a (`status new\|read\|replied\|archived`, `admin_note`; `source`, `unsub_token`), 03 (`order_number`, `is_read`; `token`, `is_active`) | **05a DDL**, plus `contact_messages.order_number VARCHAR(20) NULL` from 03 | The admin screens consume these columns | 01a §2.8–2.9, 03 §16 |
| C-21 | `admin_users.role` | 01a has `owner\|staff`; 05a has no role | **Cut `role`** — one account in v1; staff is a LATER question | Nothing grants or checks it | 01a §2.2 |
| C-22 | `slug_redirects.entity_type` | 01a `product\|collection\|scent\|page`; 01c `product\|collection` | **01a's four values** | Scent families and content pages both have renameable slugs | 01c §5.8 |

### 2.2 Architecture, files and runtime

| ID | Topic | Positions | RESOLUTION | Reason | Patched |
|---|---|---|---|---|---|
| C-23 | Directory layout | 03 §1.1 `/app` + `/includes`; 02b §1.1 `app, includes, config, lib, db` (five denied); 02a `app/` (with `app/lib/`), `db/`, `storage/` (three denied) | **02a tree** (§1.1 above): three denied directories, `config.php` at root | Fewer directories for a File-Manager deploy; one `.htaccess` deny per denied tree; the brief names `config.php` as the file the owner edits | 03 §1.1, 02b §1.1, §4, §10; 01a §5.3; 01c §1.3 |
| C-24 | Writable directories | 02b §7.3 "only `uploads/`, no log/cache dir in the web root"; 02a/02c/05b/06a/06b use `storage/` | **`storage/` is writable and inside `public_html`**, denied by `.htaccess` (2.4 + 2.2 forms) + `index.php` stub + `SKYFR` guard; `install.php` refuses to continue if it is not writable | The ZIP unzips into `public_html`; there is no "above the web root" a non-developer can be asked to manage; sessions, proofs and the mail outbox need a home | 02b §7.3 |
| C-25 | Payment-proof location | 02a/03 `uploads/payments/`; 02c `uploads/payment-proofs/`; 05b `storage/payment-proofs/`; 06b `storage/proofs/`; 01b/07 `uploads/proofs/` | **`storage/proofs/{YYYY}/{MM}/{32hex}.{ext}`**, served only by `GET /admin/orders/{order_number}/proof` | Never under the public `/uploads` rewrite pass-through; one name | 02a §2, 02c §5.4, 03 §8.5, 05b §5.1, 07 B.3.5 |
| C-26 | Session names and lifetimes | 02c `sf_sess`, gc 7200; 05a `SFADMIN`/`SFSHOP`; 06a 3-day cart in `/storage/sessions`; 05a `/home/<user>/sfsessions` | **`SFSHOP`** (path `/`, SameSite=Lax, 3 days, `storage/sessions/`) and **`SFADMIN`** (path `/admin`, SameSite=Strict, C-06 timeouts, same directory) | Both docs agree the cookie names; the save path must be one the app owns | 02c §1 step 3, 05a §2.3 |
| C-27 | `config.php` shape | 02a `define()` constants (`BASE_PATH`, `APP_ENV`, `MAIL_MODE`…); 02c `return [...]` array | **Array** (02c §7.1); `bootstrap.php` derives `BASE_PATH`, `SITE_URL`, `APP_ENV` constants from it | `install.php` can `var_export` an array through temp+rename; a human can diff it | 02a §5 |
| C-28 | First-party classes | 02c `Db::`, `Router::`, `App\Filters::`, autoload shim over `/app/classes`; 02a "no classes, no autoloader" | **Plain functions**, file-prefixed | No autoloader exists; a class buys nothing and costs a `require` | 02c §1 step 5, §0 |
| C-29 | Gender preset mapping | 03 §2 `gender=men/women`; 07/01c/02c `him\|her\|unisex` with a `GENDER_MAP` | **Presets carry the stored token** (`gender=him`); no map | 03 §5.0 already says the filter value is `him`; a map is a second vocabulary to keep in sync | 03 §2, 02c §0 |
| C-30 | Stylesheet name | 04a `style.css`; 02a/02b/04b/07 `site.css` | **`assets/css/site.css`** | Four-to-one | 04a §0, §6 |
| C-31 | JS files | 02a `site.js` + `admin.js`; 04b five storefront files | **Five storefront files** (`reveal, ui, cart, product, forms`) + `admin.js`, all `defer`; no sixth storefront file | 04b allocates behaviour per file with a byte budget; HTTP/2 makes the request count moot | 02a §2, 02b §3.8 |
| C-32 | Font files | 02a 3 files (Cormorant 600, Jost 400/500); 04a 5 files (Cormorant 300/400, Jost 300/400/500) | **04a's five**, two preloaded (`jost-400`, `cormorant-garamond-300`) | 04a owns the type scale and audited the weights | 02a §2 |
| C-33 | Design tokens | 04a `--ink-*`, `--gold-*`, `--surface`, `--text`… ; 04b `--sf-*` shorthand | **04a names are canonical**; 04b's `--sf-*` list is read as aliases of the 04a semantic tokens and is not emitted in CSS | 04b §0.2 itself defers to 04a | 04b §0.2 |
| C-34 | Icon partial path | 04b `/app/views/partials/icon.php`; 02a `app/partials/` | **`app/partials/icon.php`** | 02a owns the tree | 04b §0.1 |
| C-35 | PHPMailer path | 07 §0.1 / 05a §0.1 `/lib/PHPMailer/`; 02a `app/lib/vendor/PHPMailer/` | **`app/lib/vendor/PHPMailer/`**, required lazily inside `mail.php` | One denied tree | 07 §0.1, 05a §0.1 |
| C-36 | Cache-busting | 02a `ASSET_VERSION` constant; 02b `settings.asset_version`; 07 `?v={filemtime}` | **`filemtime()`** in `asset()` | Zero client work after an update; the other two require a hand edit the owner will forget | 02a §5, 02b §3.8, §10 |
| C-37 | `php_flag engine off` in `uploads/.htaccess` | 03 §1.1, 02c §5.4, 06b §4.2, 07 B.3.5 include it; 02b §2.2/§5 says LiteSpeed ignores it and some Apache builds 500 | **Never emitted.** Uploads are protected by `RemoveHandler` + `RemoveType` + `AddType text/plain` + `<FilesMatch> Require all denied` (02b §5) | A directive that is silently ignored is a false claim of protection | 03 §1.1, 02c §5.4 |
| C-38 | Search implementation | 01a `FULLTEXT` + LIKE fallback; 03 §5.15 scored LIKE only, FULLTEXT deferred | **Scored LIKE** (03 §5.15); the `ft_products_search` index is not created | Low-hundreds catalogue; identical behaviour on both engines; no 4-char-token surprise | 01a §3.3 index list |
| C-39 | Cart caps | 06a `MAX_QTY_PER_LINE=10`, `MAX_LINES=30`; 06b 10 per line, 20 items per order | **10 per line, 20 distinct lines per cart**, and never above current stock | One number; "20 items" read as lines so a 100 ml + 50 ml pair is not blocked | 06a §7.3 |
| C-40 | CSP `frame-src` and GA `img-src` | 02b lists `https://www.google.com` (Maps) and `https://www.google-analytics.com` | **Both removed**; `frame-src 'none'`; analytics only if the owner later pastes a tag into `settings.gsc_analytics_id`, at which point `img-src`/`connect-src` are extended in the same change | No Maps embed ships (§4); no GA ships | 02b §6 |
| C-41 | Verified-purchase chip | 03 §4.9 `reviews.is_verified` + name/city match against delivered orders | **Cut** | Needs a fuzzy PII join for a chip; revisit when there is review volume (§3 LATER) | 03 §4.9, §16 |
| C-42 | Track-order rate limit table | 01b "admin_login_attempts-style"; 06b `rate_limits` bucket `track` | **`rate_limits`**; `admin_login_attempts` stays login-only | One general-purpose table for public endpoints | 01b §9 |

### 2.3 The amended `orders` DDL (01b §1 after C-07…C-13) — the copy builders use

```sql
CREATE TABLE orders (
  id                       INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  order_number             VARCHAR(16)   NOT NULL,             -- SF-YYMMDD-XXXX (C-10)
  access_token             CHAR(32)      NOT NULL,             -- gates /order/{order_number}?t=
  idempotency_key          CHAR(32)      NOT NULL,             -- C-11
  status                   VARCHAR(16)   NOT NULL DEFAULT 'pending',
  payment_method           VARCHAR(16)   NOT NULL,             -- cod|bank|jazzcash|easypaisa
  payment_status           VARCHAR(20)   NOT NULL DEFAULT 'unpaid',  -- C-09 vocabulary
  customer_name            VARCHAR(120)  NOT NULL,
  customer_phone           VARCHAR(24)   NOT NULL,             -- as typed
  phone_normalized         VARCHAR(16)   NOT NULL,             -- +92XXXXXXXXXX (C-14)
  customer_email           VARCHAR(190)  NULL,
  city                     VARCHAR(80)   NOT NULL,
  address                  VARCHAR(400)  NOT NULL,
  postal_code              VARCHAR(12)   NULL,
  customer_note            VARCHAR(500)  NULL,
  subtotal                 DECIMAL(10,2) UNSIGNED NOT NULL,
  discount_total           DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  shipping_fee             DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  cod_fee                  DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  grand_total              DECIMAL(10,2) UNSIGNED NOT NULL,
  item_count               SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  coupon_id                INT UNSIGNED  NULL,
  coupon_code              VARCHAR(40)   NULL,
  coupon_type              VARCHAR(10)   NULL,
  coupon_value             DECIMAL(10,2) NULL,
  payment_reference        VARCHAR(64)   NULL,                 -- C-08
  payment_account_snapshot VARCHAR(160)  NULL,                 -- C-08
  paid_at                  DATETIME      NULL,                 -- C-08
  courier_name             VARCHAR(60)   NULL,
  tracking_number          VARCHAR(60)   NULL,
  tracking_url             VARCHAR(255)  NULL,
  shipped_at               DATETIME      NULL,
  delivered_at             DATETIME      NULL,
  cancelled_at             DATETIME      NULL,
  cancel_reason            VARCHAR(200)  NULL,
  stock_restored_at        DATETIME      NULL,                 -- C-12 guard
  ip_hash                  CHAR(64)      NULL,
  user_agent               VARCHAR(255)  NULL,
  source                   VARCHAR(20)   NOT NULL DEFAULT 'web',
  created_at               DATETIME      NOT NULL,
  updated_at               DATETIME      NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_orders_number (order_number),
  UNIQUE KEY uk_orders_idempotency (idempotency_key),
  KEY idx_orders_status_created (status, created_at),
  KEY idx_orders_payment (payment_method, payment_status),
  KEY idx_orders_track (order_number, phone_normalized),
  KEY idx_orders_phone_created (phone_normalized, created_at),
  KEY idx_orders_created (created_at),
  CONSTRAINT fk_orders_coupon_id FOREIGN KEY (coupon_id) REFERENCES coupons (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Invariants: `grand_total = subtotal - discount_total + shipping_fee + cod_fee`;
`subtotal = SUM(order_items.line_total)`; `item_count = SUM(order_items.quantity)`. Every
`DATETIME` is written by PHP in Asia/Karachi. The 01b tables `order_items`, `order_status_history`,
`payment_proofs` (keyed on `order_id`), `coupon_redemptions`, `order_notes` follow unchanged
except where a C-nn row above says otherwise.

### 2.4 Hardening review rulings (C-43 … C-78)

Three critics reviewed the assembled plan (hosting operations, security and commerce,
completeness against the brief). Every finding adopted is a row here; the losing passages in
the specs carry `Superseded by 08-decisions-register.md §2.4 — C-nn` or `Amended by …` notes
exactly as §2.1–2.3 do. Rejected findings are listed in PLAN §16 with reasons. The same
convention applies: when a spec and this table differ, this table wins.

| ID | Topic | Positions | RESOLUTION | Reason | Patched |
|---|---|---|---|---|---|
| C-43 | One pricing pipeline; free-shipping base; re-confirm rule | 06b §1.4 re-implemented pricing inline (post-discount shipping test, `ROUND(…,2)`, SQL `NOW()`, "favourable change proceeds"); 06b §7.5 "quantity reduced, order proceeds"; 03 §7.5/§18 post-discount bar; 06a §2.2/§4.1/§5.3/§7.7 and PLAN §7.3 pre-discount, one function, any change rolls back | **06a wins, everywhere.** One function `cart_price()` (06a §2.2) called on every render and inside the transaction on locked rows; threshold tested on the **pre-discount** subtotal; percent discount rounded half-up to the rupee (the only rounding); `now` bound from PHP; **any** difference from the total the customer saw — favourable, unfavourable, or a quantity clamp — rolls back and re-confirms | A Rs. 3,100 cart with `WELCOME10` showed Free delivery on cart and checkout and was charged Rs. 250 by Phase B; an odd subtotal made the re-price token never match so the order could never be placed | 06b §1.4, §7.5; 03 §7.5, §18; 01b §1.1; 06a §4.2 (seed 3000); 07 B.4.3 |
| C-44 | Coupon limits — sources of truth | 06b §1.5 `used_count` authoritative via guarded UPDATE; 01b §6 ledger `COUNT(*)` for every limit + one-click recount; 06a §3.7 cancel deletes the ledger row; 01b/PLAN cancel sets `reverted`; per-phone check missing from 06b Phase B/C and gated on a non-existent `coupon_one_per_phone` key; 07 A.3 seeds `FIRST50` 50/50 with no ledger rows | **The guarded `UPDATE coupons … used_count < usage_limit` is the only global-limit check.** `coupon_redemptions` is the audit trail and the only per-phone check (`status='applied'`), run inside the transaction with the coupon row locked (06b §1.5 step 5a). Cancel sets `status='reverted'` and decrements the counter in one transaction. **No recount feature.** Seed unchanged | Under the ledger rule, or one press of recount, `FIRST50` reopened for Rs. 37,500 of discounts while the owner was told it was fully redeemed; two concurrent checkouts with one phone both redeemed a 1-per-phone code | 01b §6; 06a §3.4, §3.6, §3.7; 06b §1.5, §7.2; 07 A.3; 05a §7 |
| C-45 | Image derivative widths | 02c §5.2 200/600/1400; 03 §1.3 400/600/900 and §6.1 600/900/1600; 05a §4.3 400/600/900/1200 + JPEG 900; 07 B.2.3 and PLAN P9 400/600/900/1400; collection image 16:9 (05a §4.4) vs 3:4 (03 §4.3, 04b §3) | **400 / 600 / 900 / 1400 px**, each WebP + JPEG, plus the 1200×630 OG JPEG; `w1400` is the zoom and regeneration source. Collection images: **3:4** only, reused under a scrim on the hero | srcset, LCP budget, zoom source and upload memory all depend on one set; 07 B.2.3 matches PLAN P9 and 02c's 1400 zoom | 02c §5.2; 03 §1.3, §6.1; 05a §4.3, §4.4 |
| C-46 | Upload caps, `.user.ini`, memory budget | PLAN §2 "to be decided" vs PLAN S4 8 MB; 02b §8.3 5 MB; 02c §5.1 8 MB ≤ 10 files; 05a one-per-request XHR; `.user.ini` 8M/12M and `expose_php` (PHP_INI_ONLY); pixel cap 8000 px / 40 MP rejects 48 MP phone frames; 256M assumed | App caps **5 MB proof / 6 MB admin image**; `.user.ini` `upload_max_filesize=10M`, `post_max_size=12M`, no `expose_php` line; **one image per request** everywhere (05a XHR is the contract, single-file non-JS fallback); `CONTENT_LENGTH` vs `post_max_size` check before `$_FILES`; edge cap **10,000 px**; pixel budget **computed at runtime** from `ini_get('memory_limit')` and printed on the installer | A multi-file POST over `post_max_size` arrives with `$_FILES` empty and the CSRF field missing → a 419 the owner cannot interpret; 8000×6000 is the default output of the owner's own phone | PLAN §2, S4; 02b §6.2, §8.2, §8.3; 02c §5.1; 05a §4.3 |
| C-47 | `install.php` behaviour | Self-delete (02b §7.4, 07 A.7) vs ask-to-delete (02c §7.2); lock `db/.installed` (07) vs `storage/.installed` (02c, PLAN); probes block Next (02c §7.2) and fail on DNS-not-pointed / preview host / cert pending; `base_url` frozen from the request scheme+host; admin email not written to `order_notify_email`; `default.php` / nested `public_html` undetected; 72 images encoded in one step | `unlink(__FILE__)` first, red panel only if it fails; lock is **`storage/.installed`**; all self-fetch probes **advisory** (5 s curl, no follow, 3xx-to-https = pass, "open this URL on your phone" fallback) plus probes for `/db/schema.sql` and `/storage/.htaccess`; writes every deny `.htaccess` and `uploads/.htaccess` from literals; records `base_url` as `https://` + canonical host regardless of the scheme it was opened on; writes the entered email into `order_notify_email` and `contact_email`; **Send test email** on Step 3; detects `default.php` and `public_html/public_html/`; prints `memory_limit` and the pixel budget; seeds images from the shipped derivatives, batched by `?offset=` | A working install was stuck at Step 1 whenever the host could not fetch itself; two lock paths and two delete behaviours for one file; the "new order" email had no recipient on day one | 02c §7.1, §7.2; 02b §7.4; 07 A.5.1, A.7, B.4.1; PLAN §2, §12 |
| C-48 | Order state machine and stock restore | PLAN §7.4 / 06b §3.2 allow `delivered → cancelled (+stock)` and `delivered → shipped` (24 h) and restore stock on `shipped → cancelled`; 05b §3.1 makes `delivered`/`cancelled` terminal and allows `confirmed → shipped`; PLAN deferred the ruling to stage 4 | **05b §3.1 is canonical.** `delivered` and `cancelled` are terminal; `confirmed → shipped` allowed. Automatic stock restore only on cancel from `pending`/`confirmed`/`packing`. `shipped → cancelled` leaves `stock_restored_at NULL`; stock returns only via the explicit *Parcel received back — restore stock* action (05b §6.2, route 14), still under the C-12 guard | Restoring stock for a parcel the customer holds, or one still with the courier, creates phantom inventory the storefront sells before it returns; the checkout transaction and email catalogue are built against this graph in stage 3, so it cannot wait for stage 4 | PLAN §7.4; 06b §3.2; 05b §3.2, §6.2, §0.4 |
| C-49 | Origin / Referer check and `base_url` | 02c §4 and 05a §2.5 compared `Origin` against `config['base_url']` written once at install; no Settings field or guide step edits it | Compare against the **request's own** scheme + host (`request_origin()`, proxy-aware per 02b §2.3). `base_url` is for canonical/OG/sitemap/email links only, editable on Settings › Advanced with a "you opened this page at https://… — use that" hint; dashboard banner when the hosts differ | In every realistic go-live order (install before SSL, on the preview host, before the DNS switch) the frozen `base_url` was wrong, the cart API rejected every Origin and every admin POST 419'd | 02c §4, §7.1; 05a §2.5, §4.1, §4.9; 07 B.4.1 |
| C-50 | Client IP behind Hostinger's edge proxy | No document defined how `client_ip` / `ip_hash` is derived; 02b §2.3 confirms a proxy is in front | One `client_ip()` (02c §1 step 3a): trust the rightmost untrusted `X-Forwarded-For` hop only when `REMOTE_ADDR` is inside `config['trusted_proxies']` (written by `install.php` from its own probe, overridable in Settings › Advanced); otherwise `REMOTE_ADDR`. Installer row *Client IP detection — PASS/WARN*; live-host test in 07 B.4.1 | With `REMOTE_ADDR` = the proxy every visitor shares one bucket (the 11th site-wide checkout in ten minutes is refused; ten bad logins from any bot lock the owner's IP); with naive `X-Forwarded-For` one header bypasses every limit | 02b §2.3; 02c §1; 05a §2.6; 06b §1.3, §4.2, §6.3; 03 §12.3; 07 B.3.9, B.4.1 |
| C-51 | Admin login lockout | 05a §2.6 hard-locks the username at 5 fails/15 min with no unlock and no CAPTCHA; 02a §3.7 and 07 B.3.9 describe IP-keyed locks with different numbers | **Per-IP hard lock stays** (10 fails / 15 min, `client_ip()`). **No username lock**: from 5 fails the username gets a 2 s progressive delay; a request carrying a valid known-device cookie `SFDEV` (random 32 bytes, up to 5 hashes in `admin_users.known_devices`, issued on each successful login) skips the delay. `reset-password` clears `admin_login_attempts` for the username | The username is guessable; five wrong passwords every 14 minutes kept the owner out of her shop from every device forever, and the reset script did not clear the lock | 05a §2.4, §2.6, §2.7; 01a §2.2; 02a §3.7; 07 B.3.9; PLAN S8, §9 |
| C-52 | HTTPS redirect status | 02b §3.2 ships `R=301` and requires SSL before the first link; PLAN §12 and the brief run `install.php` before SSL | Ship **`R=302`**; `install.php` Finish (or Settings › Advanced › *Make HTTPS permanent*) runs an https self-check (curl `https://{host}/robots.txt`, 5 s, valid cert) and rewrites the line to `R=301`. Guide order fixed: DNS → padlock → upload → install | A 301 cached against a not-yet-issued certificate is a browser-side failure the owner cannot clear remotely; hidden in rehearsal because the preview host already has SSL | 02b §3, §3.2; 02c §7.2; 05a §1, §4.9; 07 B.4.1, Part C |
| C-53 | Session directories and GC | 02c §1 (gc 259200) and 05a §2.3 (gc 43200) share `storage/sessions/`; unconditional `session_start()` on every storefront GET; gc probability at PHP default | **`storage/sessions/shop/`** and **`storage/sessions/admin/`** with their own lifetimes; `gc_probability=1, gc_divisor=100` in both bootstraps; storefront session started **lazily** (cookie present, POST, or `/api/cart/*`, `/checkout`, `/track`); session files past lifetime also removed by the opportunistic purge | PHP's file handler GCs the whole save path with the current request's lifetime, so every admin request silently trimmed the promised 3-day cart to 12 h; crawler hits accumulated files against the inode quota | 02c §1 step 3; 05a §2.3; 02a §2; PLAN §2, S9 |
| C-54 | Root `.htaccess`, admin routing, file denies, `ErrorDocument`, guard list | 02b §3 vs 02a §3.1 (weaker HTTPS rule) vs 05a §1 (`RewriteEngine On` in `admin/.htaccess`); exists-guard served `.disabled`, `.user.ini`, `.sql`, `.md`; `ErrorDocument 404 /index.php` boots PHP for every missing image (02b §3.6 and 02a §3.1 both claimed the opposite); `admin/controllers|views|partials` directly requestable | **02b §3 is the only root text.** `/admin` routed from the root file (rule 3.6a); `admin/.htaccess` carries headers only; rule 3.0 `<FilesMatch>` denies dotfiles, `.disabled|sql|md|log|bak|old|swp|dist|lock` and `config*.php`; `uploads/.htaccess` and a new `assets/.htaccess` carry `ErrorDocument 404 "Not found"`; the deny `.htaccess` + stub + `SKYFR` guard extend to `admin/controllers/`, `admin/views/`, `admin/partials/`; `cron.php` guards itself | A per-directory `RewriteEngine On` replaces the parent rule set on Apache and LiteSpeed, so `http://…/admin/login` posted the password in clear; each stale `srcset` URL after a photo replacement cost one PHP process and one DB connection — the 508 the section was written to prevent | 02b §3, §3.1, §3.6, §4, §5, §9; 02a §2, §3.1, §3.7; 05a §1; 07 B.4.1, B.4.9 |
| C-55 | Shipped SQL and connection modes | 01c §7 `seed.sql` opens with `DROP TABLE IF EXISTS` for every table while PLAN §1 calls the files a phpMyAdmin alternative; 01c §4.1 collation on `CREATE DATABASE`; `SET SESSION` lines in a paste; no connection sets `sql_mode` (C-03 depends on strict mode); `ROW_FORMAT` unstated | **No `DROP`, `CREATE DATABASE`, `USE` or `SET SESSION` in any shipped `.sql`**; `schema.sql` = `CREATE TABLE IF NOT EXISTS … ROW_FORMAT=DYNAMIC`; `seed.sql` = `INSERT IGNORE` keyed on sample slugs/SKUs/codes; every PDO connect runs `SET time_zone='+05:00'` then `SET SESSION sql_mode='STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'`; isolation level set from PHP at checkout | Six months in, re-importing `seed.sql` to get sample products back would have dropped `orders`, `order_items`, `payment_proofs` and `coupon_redemptions`; a permissive MariaDB `sql_mode` turns the UNSIGNED "loud failure" into a silent clamp | 01c §4.1, §4.2, §7; 02c §2.1, §7.2; PLAN §1, §4.3; 07 B.4.1, B.4.4 |
| C-56 | Outbox drain, SMTP timeout, opportunistic purge, cron | 06b §5.2 hourly cron + `LIMIT 20` × 8 s + 1-in-20 admin drain; PLAN "retried when you next open the admin panel"; SMTP timeouts 6 s (05b), 8 s (06b), 10 s (02b), 15 s (02c); `cron.php` allow-listed but absent from 02a's tree; sweeps assigned to a §5.6 that does not exist | Drain on **every admin page load**, after the response is flushed, only due rows, **≤ 2 sends and 12 s per load**, one SMTP timeout **5 s** in the codebase; checkout attempts only the customer email inline; dashboard *"{n} emails waiting"* whenever queued rows exist; the same hook runs the opportunistic purge (sent rows > 60 d, orphan proof files > 24 h, `rate_limits`/`admin_login_attempts` > 30 d, expired sessions); `cron.php` shipped as an optional accelerator | 20 rows × 8 s = 160 s in one admin request; lsphp kills it at 60 s and the owner sees the branded 500 on a random click; a queued confirmation sat for hours while the owner was in the panel | 06b §1.6, §5.2; 02c §6.2, §6.4; 02b §8.4; 05b §3.2; 02a §2; PLAN §2, §8.4, §13 |
| C-57 | Payment-proof file lifecycle | 06b §1.6/§4.2 wrote the proof to disk before `BEGIN` and swept orphans in a non-existent cron §5.6 | Phase A validates the upload but keeps only the PHP temporary path; GD re-encode and the `storage/proofs/` write happen **post-commit** in the same `try/catch` as the outbox insert; a write failure keeps the order (`payment_proofs.file_path = NULL`, admin sees *Proof missing — request on WhatsApp*); orphan sweep in the purge; `storage/` usage on the dashboard | Every failed checkout that carried a valid image left a file forever; at 10 posts/IP/10 min from rotating IPs a script filled the quota in an afternoon and the host returned 508 for everything | 06b §1.3, §1.6, §4.2; 03 §8.5; 01b §5.1; 05a §4.1; PLAN §8.2, §12 |
| C-58 | Manual-payment denial-of-inventory | Stock decremented at placement for transfer orders, never auto-cancelled (Q-07), caps flag-only (06b §7.1), no bulk cancel (05b §1.7) | Per-phone **and** per-IP caps are **blocking for manual methods** while a previous transfer order from the same phone/IP is `pending` with `payment_status IN ('awaiting_verification','failed')` — one outstanding transfer at a time; COD stays flag-only. One deliberate bulk-cancel exception: **Cancel unpaid transfer orders older than `manual_hold_hours`** (route 13), one reason, C-12 guard per order. New settings key `manual_hold_hours` (default 48) | An attacker could place transfer orders for a size's whole stock from a few phones; the storefront showed *Sold out* within minutes and the owner had to release each order with two taps and a typed reason, daily | 06b §1.3, §4.2, §7.1; 05b §0.4, §1.7; 05a §4.9; PLAN §7.5, §8.2, §9, §13, §14 |
| C-59 | Confirmation-link re-upload | 06b §4.2 re-opened the proof form while `status = pending` and the insert set `awaiting_verification` unconditionally; the token never expires | Form and POST reachable only while `payment_status IN ('unpaid','failed') AND status = 'pending'`; the insert moves `payment_status` with a guarded `UPDATE … WHERE payment_status IN ('unpaid','failed')` and `rowCount()===1`; once `paid` the link renders the receipt only; address and phone stop rendering 30 days after `delivered_at`/`cancelled_at` | The designed workflow keeps paid manual orders at `pending` + `paid` for hours; anyone holding a forwarded link could drop a paid order back into the review queue three times | 06b §4.2; 03 §9; PLAN §8.2; 07 B.4.9 |
| C-60 | Content-Security-Policy ownership | `.htaccess` CSP with `script-src 'unsafe-inline'` + Google Fonts hosts (02b §6) **and** a PHP CSP with `script-src 'self'` + HSTS (07 B.3.6); 04b requires one inline `html.js` script; PLAN S7 "no inline scripts" | **One policy, sent from PHP only** (07 B.3.6 canonical): `script-src 'self' 'sha256-<hash of the constant 3-line html.js snippet>'`, no `'unsafe-inline'` for scripts, no Google hosts (fonts self-hosted, C-32), `frame-ancestors 'none'`, **no HSTS from PHP** (Q-23). `.htaccess` sets only static-file headers inside `<FilesMatch>`. Cart-count hydration reads a data attribute; JSON-LD is not executed and needs no allowance. "Zero CSP violations" added to 07 B.4.9 | Two CSP headers are enforced as their intersection, so any future relaxation in PHP is silently ignored; built per 02b any escaping slip executed, built per 07 the no-FOUC bootstrap was blocked | 02b §6, §6.1, §6.2, §9; 07 B.3.6, B.4.9; 04b Part 5; PLAN S7 |
| C-61 | Idempotency key semantics | 03 §8.7 deleted the token inside the transaction and showed "already placed"; 06b §1.3 checked format only, never equality with the session; 06b §1.6/§2 not consumed on failure; replay always 303'd to the old order | `hash_equals($_SESSION['idem'] ?? '', $_POST['idem_key'])` in Phase A else CSRF failure with a fresh key; key cleared **only after `COMMIT`**; on duplicate-key replay compare the existing order's line fingerprint with the current cart — same → cart cleared, 303 to the receipt; different → fresh key and *"Your previous order {number} was already placed. This is a new order."* | Per 03 a stock-race loser was told an order existed that did not; per 06b a stale tab submitted after a new cart landed on the old receipt and the new items were never ordered | 03 §8.7, §18; 06b §1.3, §1.6, §2; PLAN §7.5 |
| C-62 | The HTML sink (content pages) | 01a "allow-list on save"; 03 §12.1 a tag list; 07 B.3.2 "stored as-is, rendered unescaped"; 02c §3.2 `sanitize_html` on the product description (which 05a says is plain text); no implementation named | `sanitize_html()` = `DOMDocument` parse, unwrap elements outside `p, br, strong, em, b, i, ul, ol, li, a, h2, h3, blockquote`, drop every attribute except `href` on `a`, accept `href` only for http/https/mailto or site-relative paths, force `rel="nofollow noopener"`, `saveHTML()`; applied **on save and on render**; the product description is plain text and `e()`d; `javascript:`/`onerror` cases added to 07 B.4.9 | With no Composer the builder would reach for `strip_tags($html, '<p><a>…')`, which keeps every attribute; a planted `javascript:` link in `/privacy` survives a password change | 02c §3.2; 01a §2.6; 03 §12.1; 07 B.4.9 |
| C-63 | Contact auto-reply | 06b §5.1 #9 echoed the sender's message to a sender-chosen address through the shop's authenticated SMTP; per-address and per-IP limits only | Fixed acknowledgement (subject line + reply window), **never the message body**; sent only after honeypot and time-trap pass; skipped when the address already has a `contact_messages` row in 24 h; **site-wide cap 30 auto-replies/hour**, then queued silently | Rotating IPs plus a victim list made Sky Fragrances deliver spam under its own SPF/DKIM-valid name; Hostinger suspends outbound mail for less, and then order confirmations stop | 06b §5.1; 02c §6.3; 07 B.4.9 |
| C-64 | Password recovery script | 05a §2.8 shipped `admin/reset-password.php.disabled` (served as text by the exists-guard) which, once renamed, was unauthenticated | Ships as **`app/tools/reset-password.php`** (denied tree); the owner copies it to the root; it demands proof of File-Manager access (create a nonce file `storage/reset-{16hex}`), then a CSRF-protected form; on success writes the hash, **deletes `admin_login_attempts` for the username**, removes the nonce and itself | The full source and the exact recovery procedure were public; "one successful use per hour" was a race, not a control | 05a §2.8; 02a §2; 02b §3; 07 B.4.9, Part C |
| C-65 | Privacy purge, audit PII, export logging | PLAN S12 / 07 B.3.11 promised a 90-day proof purge "by an admin tool" that no route table contained; `email_outbox.body_html` kept the full rendered confirmation with no cleanup path; CSV exports not audited; `before_json/after_json` carried PII | **`POST /admin/tools/purge`** (05a route 40) + the same routine on a 1-in-20 admin load: proof files deleted and `payment_proofs.file_path` nulled with `purged_at` for orders delivered/cancelled > 90 d; `email_outbox.body_html` stubbed on rows sent > 60 d; `rate_limits`/`admin_login_attempts` > 30 d and expired sessions pruned. One `admin_activity_log` row per CSV export (filters + row count). PII fields recorded as `"(changed)"` in before/after JSON | The privacy page would otherwise publish a promise the software could not keep | 05a §1, §5.1; 05b §0.4; 01b §5.1; 07 B.3.11; PLAN S12, §5.3, §9 |
| C-66 | Coupon-code enumeration | 03 §7.7 limited coupon attempts per session; the canonical bucket list (§1.2) had no `coupon` bucket; `not_started` confirmed a real code before its launch | Bucket **`coupon`** on `ip_hash`, 10/hour and 50/24 h; `not_started` returns the `invalid` message; `expired`/`exhausted` stay (they describe codes the customer legitimately held) | Codes are `[A-Z0-9-]` in an obvious house style; a few thousand guesses found a scheduled launch code or a private influencer code | §1.2 (buckets); 06a §3.4; 03 §7.7, §12.3; 07 B.4.3 |
| C-67 | "Notify me" back-in-stock | PLAN §5.2, 03 §6.4/§6.8/§18 specify a `restock_alerts` form; §4 of this register cut the table | **Struck.** Sold-out size → disabled *Sold out* button; WhatsApp becomes the primary action with the 03 §6.9 out-of-stock message. Back-in-stock capture is a v2 question in PLAN §14 | Capturing an address with no automation is a promise the owner would keep by hand; a builder cannot implement both texts | 03 §6.4, §6.8, §12.3, §18; PLAN §5.2, §14 |
| C-68 | Sample reviews | 07 A.4 seeded five `approved` + three `pending` `is_sample` reviews; B.1.4 admitted they counted in `aggregateRating` until removed | **All eight seeded `pending`**, `approved_at NULL`, `rating_avg/count` seed 0; the developer approves some locally to exercise the star bar (B.4.5); A.7 still removes them before go-live | The brief says no fake reviews; stage screenshots and any staging index carried invented ratings | 07 A.4, B.1.4; PLAN §11.4 |
| C-69 | COD ceiling | 06b §4.1 `settings.cod_max_total` default Rs. 30,000; Q-06 called it a constant; 05a §4.9 had no key; the brief asks for COD with no ceiling | Settings › Payments key **`cod_max_total`, default `0` = no cap**; a non-zero value disables COD above it with the interpolated message. Asked in PLAN §14.2 | A silent scope reduction — two 100 ml Azure Oud plus a 50 ml exceeded Rs. 30,000 and was refused at checkout without the owner ever saying yes | 06b §4.1, §7.6; 05a §4.9; Q-06; PLAN §7.5, §8.1, §13, §14 |
| C-70 | Runtime-written state, `config.php`, maintenance mode | 02c §1 `include`d a `var_export`ed settings cache; 02c §8.2 had Settings rewrite `config.php`; 05a §4.9 had `maintenance_mode` as a settings key; bypass key in the URL | Settings cache is **`storage/cache/settings.json`** (`file_get_contents` + `json_decode`); **`config.php` is `0400` after install and never written again**; maintenance = `settings.maintenance_mode` **or** the flag file `storage/MAINTENANCE` (File-Manager fallback); `security.maintenance*` removed from config; bypass secret is a settings key entered via POST (C-76) | lsphp OPcache kept serving the old opcodes of a rewritten `.php` for up to a minute — the announcement bar stayed old and maintenance-off left the 503 up; a stolen admin cookie had a write path into a file required on every request | 02c §1 step 7, §7.1, §8.2; 02b §7.2; 05a §4.9; PLAN §2, §3 |
| C-71 | Sub-folder installs | PLAN §3 "the single `RewriteBase /` line"; 02a §5 "base-path-safe as written" | **Not supported in v1.** `install.php` refuses when not at `/`; `BASE_PATH` plumbing stays in code at `''`; staging uses the preview host's root (`site_indexable = 0` already protects the real shop) | `RewriteBase` only affects relative substitutions — 02b rules 3.4, 3.5, 3.6, both `ErrorDocument` paths and `admin/.htaccess` are absolute: five edits sold as one | 02a §5; PLAN §3, §13; 07 Part C |
| C-72 | `uploads/.htaccess` text | Three different texts (02b §5, 02c §5.4, 07 B.3.5); the canonical one had unguarded `Header` lines and `-ExecCGI` | **02b §5 is the single text** and the literal embedded in `install.php`; `Header` lines inside `<IfModule mod_headers.c>`; no `-ExecCGI` (`-Indexes` is inherited); `ErrorDocument 404 "Not found"` added; `assets/.htaccess` ships alongside | An unguarded `Header` is a 500 for every product image on a host without `mod_headers`; `-ExecCGI` is "Options not allowed here" under a restricted `AllowOverride Options=` | 02b §5, §5.1, §7.4; 02c §5.4; 07 B.3.5 |
| C-73 | Long-running admin jobs | 02c §5.3 regenerated every derivative in one request; install Step 4 encoded 72 files at once; `max_execution_time = 60`, `set_time_limit()` ineffective under lsphp | *Regenerate all derivatives* and the install seed are batched by `?offset=` with a self-redirect after ~20 s of work; the seed prefers the pre-generated derivatives shipped in the ZIP | 60 images at ~0.4 s per 1400-px WebP already exceed 60 s on a shared core | 02c §5.3, §7.2; 02b §8.4; 05a §1 |
| C-74 | `storage/` deny depth | Layer-1 `<FilesMatch>` listed code/text extensions only; proofs are `<32hex>.jpg`, sessions are extensionless | `storage/.htaccess` uses `<FilesMatch ".">` for the last-resort deny; `install.php` writes and probes it as it does `uploads/.htaccess` | The "three independent layers" claim was one layer for the most sensitive files on the server | 02b §4, §7.4; 02a §2 |
| C-75 | Phone edits, phone search, tracking link | 05b §10 let the admin edit `customer_phone` without recomputing `phone_normalized`; §1.4 searched `customer_phone` assuming a stored `03…` form; `orders.tracking_url` had no scheme rule; 03 §10.1 and 06b §5.1 still read `settings.courier_track_url_{slug}` | Route-7 handler recomputes `phone_normalized` with `phone_normalize()` and logs both; search queries `phone_normalized`; WhatsApp digits come from `phone_normalized`; `tracking_url` rendered only when it begins with `https://`; `courier_track_url_*` references struck (Q-09) | The owner fixed a typo and the customer could no longer track; a pasted `http://` or `javascript:` link would have landed in emails | 05b §1.4, §7.1, §10; 03 §10.1; 06b §5.1 |
| C-76 | Pre-auth CSRF, bypass key, `/order/{n}` oracle | 05a §2.5 created the CSRF token "on login" though the login form needs one; 02c §8.2 took `?bypass=` from the URL; 03 §9 specified wrong-token behaviour but not unknown-number, and `GET /order/{n}` had no rate limit | Token ensured on the first `SFADMIN` request and rotated on login; bypass key entered in a POST form on the maintenance page; `/order/{n}` renders the identical pre-filled track form for wrong token and unknown number alike and counts in the `track` bucket | A 404 for unknown numbers versus a form for real ones is the existence oracle 06b §6.2 closes on `/track` | 05a §2.5; 02c §8.2; 03 §9; 07 B.4.9 |
| C-77 | ZIP manifest | PLAN §3 excluded only `router.php`; 02b §7.1 warned Finder *Compress* drops dotfiles; nothing listed the archive's contents | Built with `zip -r -X` from a clean export, flat root, verified by `unzip -l`: 10 `.htaccess` files, `.user.ini`, `config.sample.php`, `install.php`, `cron.php`, empty `storage/` tree with stubs, shipped derivatives; no `config.php`, `router.php`, `.DS_Store`, `__MACOSX`, `docs/`. Manifest owned by 02a §1.2; the stage-6 rehearsal starts from the ZIP | A missing subdirectory `.htaccess` is invisible yet leaves private files world-readable | 02a §1.2; 02b §7.1; PLAN §1, §3, §12 |
| C-78 | Go-live guide ownership | PLAN named the guide and listed seven steps; no spec owned its content; missing: mailbox before install, PHP 8.2, `.htaccess` survival check, `site_indexable`, `REPLACE ME`, hPanel don'ts | **07 Part C** owns the outline: DNS → padlock → upload → install; "What not to touch in hPanel"; mailbox first; test order per method; recovery and maintenance steps; acceptance = a stranger completes it in under an hour from the ZIP | The deliverable's shape must be approved now, not discovered in stage 6 | 07 Part C; PLAN §1, §12 |

Editorial corrections made in the same pass without a new ruling (each carries a one-line note
in place): 05a §4.1 `placed_at` → `created_at` (F-05); 03 §10.1 and 06b §5.1 #6 tracking link
→ `orders.tracking_url` (F-12, Q-09); 03 §4.1 announcement dismissal → `sessionStorage`, no
`_bg/_fg` keys (F-14, Q-15); 03 §4.11 newsletter non-JS post → `/api/newsletter` (F-17);
07 B.2.4 one `site.js` → five files (C-31); 02b §8.4 sitemap "no cache" → 1-hour cache
(07 B.1.7); 05b §3.2 `admin_email` → `order_notify_email`; 06b §7.6 assumption 1 resolved.

---

## 3. Open questions for the client

Merged from every unit's open-question list and deduplicated. **BLOCKING** = the answer changes
what gets built; **LATER** = a sensible default is taken now, stated here, and can be changed
without a migration or a rewrite. Everything that was purely inter-stream (money type, folder
layout, table names, gender tokens, session storage) is settled in §2 and is not a client question.

### 3.1 BLOCKING

| # | Question | Default we build to | Why it blocks |
|---|---|---|---|
| Q-01 | **Order numbers: random or sequential?** We propose `SF-260925-K7QF` (date + 4 random letters). Some owners want a strictly sequential invoice number for their accountant. | Random (C-10) | Sequential re-introduces the counter table and the enumeration risk; the invoice template and the router regex change. Must be answered before the checkout transaction is written. |
| Q-02 | **Is a sales-tax (FBR) line required on invoices?** The brief does not mention tax; Pakistani retail perfume is quoted tax-inclusive. | No tax line; `orders` has no `tax_total`; invoice shows subtotal, discount, shipping, total | A tax line changes the invoice template, the money invariants in 06a and adds a column — cheap now, a migration later. |

### 3.2 LATER (default taken; revisit any time)

| # | Question | Default |
|---|---|---|
| Q-03 | Seeded prices (Rs. 3,950 – 13,950 across the 12 perfumes) | Ship as seeded; the owner edits prices in admin before launch. Nothing in the build depends on the numbers. |
| Q-04 | Shipping fee and free-shipping threshold | `250` / `3,000`, editable in Settings › Shipping. |
| Q-05 | COD surcharge | `0` (`orders.cod_fee` exists, always `0.00`; no settings key until wanted). |
| Q-06 | Abuse caps: COD maximum Rs. 30,000, 10 per line, 20 lines per cart, 5 orders per phone per day, 3 proof re-uploads | Ship these numbers as constants in `app/lib/cart.php` / `checkout-submit.php`. **Amended by C-69 / C-58:** the COD cap is a Settings key `cod_max_total` that ships **off** (`0`); the other caps stay constants; for manual-payment methods the per-phone/per-IP cap is *blocking* while a previous transfer order is still awaiting verification. |
| Q-07 | Manual-payment hold: unpaid bank/JazzCash/Easypaisa orders flagged amber after 48 h, never auto-cancelled | 48 h (`settings.manual_hold_hours`), no auto-cancel. **Amended by C-58:** one bulk action *Cancel unpaid transfer orders older than 48 h* lets the owner release held stock in one tap. |
| Q-08 | Payment proof: images only, or PDF too? | **JPG/PNG/WEBP only** — every proof is re-encoded through GD, which a PDF cannot be. (03 §8.5 wins over 06b §4.2.) |
| Q-09 | Courier tracking: plain tracking number, or per-courier deep-link templates? | Tracking number + an optional URL the admin pastes (`orders.tracking_url`, stored resolved). No courier registry. |
| Q-10 | Second admin (staff) account | Not in v1; `admin_users` has no `role` column (C-21). |
| Q-11 | Admin-entered phone/WhatsApp orders | Not in v1; `orders.source` stays and is always `web`. A "create order" screen is a v2 item. |
| Q-12 | Free-shipping coupon type | Only `percent` and `fixed` in v1. |
| Q-13 | Sold-out line in the cart: keep it and block checkout, or remove silently? | Keep and block, with the message in 03 §7.6. |
| Q-14 | Packing slip prices | Omitted (gift-safe); the COD "collect Rs. X" banner is the only amount printed. |
| Q-15 | Announcement-bar dismissal | Per browser session (`sessionStorage`); 03 §4.1 patched (F-14). |
| Q-16 | Guest cart lifetime | 3 days, in `storage/sessions/`. No cross-device recovery for guests. |
| Q-17 | Product photography | Build enforces a 4:5 crop at upload, so any photo works; consistent dark-ground bottle shots are still what the design assumes. Originals are discarded after re-encoding; "regenerate derivatives" re-derives from the 1400 px zoom image. |
| Q-18 | Hostinger plan specifics: memory limit (256M vs 384M), cron availability, SMTP host | Assume Premium: `memory_limit 256M` (verified at install and printed, C-46), no cron (the mail outbox drains on **every** admin page load within a 2-send / 12 s budget, C-56; `cron.php` ships as an optional accelerator), `smtp.hostinger.com:587 TLS` entered during `install.php` and test-sent (C-47). |
| Q-19 | Email deliverability | SPF + DKIM for `skyfragrances.com` are a go-live-guide step for the owner; until set, WhatsApp is the real confirmation channel. |
| Q-20 | Canonical host | Apex `skyfragrances.com`; `www` 301s to it. |
| Q-21 | Urdu / RTL | Out of scope for v1 (`<html lang="en">`). |
| Q-22 | Light-on-ivory "Why choose us" band | Kept as the single `.t-light` storefront section; a one-class change to make it dark. |
| Q-23 | HSTS | Off at launch; the HTTPS redirect is in place (as a 302 until the installer's https self-check flips it to 301, C-52). Turning HSTS on is one `.htaccess` line after the certificate has been stable; it is never sent from PHP (C-60). |
| Q-24 | Sample catalogue at launch | The 12 perfumes and 5 collections are the owner's to keep or purge via `/admin/tools/remove-sample-data`; **sample reviews are always deleted before go-live** (brief: no fake reviews) and are seeded unapproved so none ever renders (C-68). |
| Q-25 | `WELCOME10` restrictions (added by the review, F-16) | Seeded one-per-phone with a one-year expiry — two limits the brief did not mention. Ship as seeded; the owner can set `per_phone_limit` blank and clear the expiry in Coupons. |
| Q-26 | Undo on a delivered order (added, C-48) | None — `delivered` is terminal; a wrong tap is corrected by a note. A 24-hour undo is a v2 question. |
| Q-27 | Back-in-stock "Notify me" capture (added, C-67) | Not in v1; the sold-out CTA is WhatsApp. |

---

## 4. Scope decisions — additions beyond the brief

Bias: keep small additions that serve the brief's own goals (conversion, SEO, admin-from-phone);
cut anything that needs a third party, a cron, or ongoing client work with no screen to do it.

| Addition | From | KEEP / CUT | Reason |
|---|---|---|---|
| "Customer voices" band on the home page | 03 §4.9 | **KEEP** | Real approved reviews only, off switch `home_reviews_enabled`, section omitted below 3 qualifying reviews. Strongest conversion element after COD. The Verified-purchase chip is cut (C-41). |
| Google Maps embed on `/contact` | 02b §6 only | **CUT** | 03 §12.2 never included it; a third-party iframe for a business with no walk-in counter. `frame-src` removed (C-40). |
| Google Analytics pixel in CSP | 02b §6 | **CUT** | Not requested; `gsc_analytics_id` setting stays for a future paste-in. |
| `slug_redirects` + 301 on renamed slugs | 01a §2.7, 03 §1.1 | **KEEP** | Protects links the owner has already sent on WhatsApp; one table, no client work. |
| `/scent/{slug}` landing pages + `scent_families` table | 01a §3.2, 03 §2 | **KEEP** | Indexable landing pages for the brief's SEO goal; admin vocabulary stays closed. |
| `/new-arrivals`, `/best-sellers`, `/sale`, `/collections` index | 03 §2 | **KEEP** | Presets on the one listing controller; zero extra code paths. |
| Header search suggestions (`/api/search-suggest`) | 03 §1.6 | **KEEP** | 1.5 KB of JS, degrades to a plain form. |
| Scent Finder stored in `quiz_*` tables | 01a §4 | **KEEP tables, no admin screen** | Seeded once; editing questions is a v2 screen. |
| Instagram section via `instagram_posts` table | 03 §4.10 | **CUT table, KEEP section** | Six `instagram_tile_n_image` / `_url` settings on the Home tab reuse the existing image-setting pipeline; no new table, no new screen. |
| `cities` table (~40 seeded cities) | 03 §8.3 | **CUT** | City is free text with a `<datalist>` from a PHP constant; no CRUD exists for a table. |
| `faqs` table | 03 §16 | **CUT** | `/faq` is a `content_pages` row; `page-faq.php` builds the accordion and `FAQPage` JSON-LD from `<h3>` question / following-paragraph pairs. One editor, no extra screen. |
| `usp_items` table | 03 §16 | **CUT** | The four USPs are the brief's own words, hard-coded; `delivery_time` is interpolated from Settings. |
| `restock_alerts` (back-in-stock email capture) | 03 §6.4 | **CUT** | Capturing an address with no automation is a promise the owner would have to keep by hand. The "Notify me" CTA that depended on it is struck from 03 and PLAN (C-67). |
| `search_queries` (zero-result logging) | 03 §5.15 | **CUT** | No screen reads it. |
| `spam_log` table | 03 §12.3 | **CUT** | Honeypot / time-trap hits go to `log_write('warning', …)` instead. |
| `admin_sessions` table | 01a §2.4 | **CUT** | C-05. |
| `payments`, `checkout_attempts`, `order_number_seq`, `order_stock_moves` | 01b, 01c | **CUT** | C-08, C-11, C-10, C-12. |
| `coupons.per_phone_limit` + `coupon_redemptions` ledger | 01b §6 | **KEEP** | Makes `WELCOME10` a real welcome (1 per phone); one table. |
| `email_outbox` + admin-page-load drain | 06b §5, 02c §6.4 | **KEEP** | An order is never lost to a slow SMTP; cron is optional. Drain rule fixed by C-56 (every admin load, ≤ 2 sends / 12 s, SMTP 5 s); `cron.php` ships as the optional accelerator. |
| `order_notes`, admin bulk actions, WhatsApp message templates, print invoice/packing slip | 05a, 05b | **KEEP** | All serve "manage orders from my phone". |
| Maintenance mode with bypass key | 02c §8.2, 05a §4.9 | **KEEP** | One settings toggle (**the DB key**, plus a `storage/MAINTENANCE` flag file as the File-Manager fallback; `config.php` is never rewritten — C-70); bypass key via POST (C-76). |
| Shipped-disabled `reset-password.php.disabled` | 05a §2.8 | **KEEP, relocated** | Recovery without a mail dependency — now `app/tools/reset-password.php`, copied out by the owner and gated on a File-Manager nonce (C-64). |
| `/admin/tools/remove-sample-data` + `db/sample-manifest.php` | 07 A.7 | **KEEP** | The launch blocker for fake reviews needs a button, not a SQL lesson. |
| `/admin/tools/purge` (privacy purge) | review, C-65 | **KEEP (added)** | The privacy page promises a 90-day proof deletion; the software must be able to keep it. Also runs 1-in-20 admin loads. |
| Bulk *Cancel unpaid transfer orders* | review, C-58 | **KEEP (added)** | The one exception to "never bulk cancel"; releases stock held by never-paid transfer orders in one tap. |
| Known-device cookie for the admin login | review, C-51 | **KEEP (added)** | Lets the username limit be a delay instead of a lock without weakening the password check. |
| `cron.php` | 06b §5.2, 02c §6.4 | **KEEP as optional** | Present in the tree so a cron *can* be pointed at it; nothing requires it. |
| Critical CSS `<style>` block in the layout | 07 B.2.1, 04b §0.1 | **KEEP, hand-maintained** | ≤14 KB sliced at the `site.css` comment banners by hand; no deploy-time tooling. |
| HSTS "add after 14 days" follow-up | 02b §6.3 | **CUT the follow-up** | Client work with no screen; see Q-23. |
| `settings.asset_version` / `ASSET_VERSION` | 02a, 02b | **CUT** | C-36. |

---

## 5. Patch log

Every losing passage now carries `Superseded by 08-decisions-register.md §2 — C-nn: <resolution>`
directly beneath it. Documents touched: 01a, 01b, 01c, 02a, 02b, 02c, 03, 04a, 04b, 05a, 05b, 06a,
06b, 07. Nothing was rewritten; each patch is one inserted line plus, where a table cell or code
literal would otherwise mislead a builder, the minimal in-place edit noted in the C-nn row.

**Hardening pass (2026-09-25, §2.4, C-43 … C-78).** Applied after the three critiques
(`critique-hosting.md`, `critique-security.md`, `critique-completeness.md`). Documents touched:
01a (§2.2, §2.6), 01b (§1.1, §5.1, §6), 01c (§4.1, §4.2, §7), 02a (§1.2 new, §2, §3.1, §3.7, §5),
02b (§2.3, §3, §3.1, §3.2, §3.6, §4, §5, §5.1, §6, §6.1, §6.2, §7.1, §7.2, §7.4, §8.2, §8.3, §8.4,
§9), 02c (§1, §2.1, §3.2, §4, §5.1–5.4, §6.2–6.4, §7.1, §7.2, §8.2), 03 (§1.3, §4.1, §4.11, §6.1,
§6.4, §6.8, §7.5, §7.7, §8.5, §8.7, §9, §10.1, §12.1, §12.3, §18), 04b (Part 5), 05a (§1, §2.3–2.8,
§4.1, §4.3, §4.4, §4.9, §5.1, §7), 05b (§0.4, §1.4, §1.7, §3.2, §6.2, §7.1, §10), 06a (§2.2, §3.4,
§3.6, §3.7, §4.2, §5.3), 06b (§1.3–1.6, §2, §3.2, §4.1, §4.2, §5.1, §5.2, §6.3, §7.1, §7.2, §7.5,
§7.6), 07 (A.3, A.4, A.5.1, A.7, B.1.4, B.2.4, B.3.5, B.3.6, B.3.9, B.3.11, B.4.1, B.4.3, B.4.4,
B.4.9, Part C new). Notes read `Superseded by … §2.4 — C-nn` (losing text), `Amended by …`
(text that stands with an addition) or `Corrected by …` (a factual claim that was wrong). PLAN §16
summarises every ruling in plain English and lists the findings that were reviewed and rejected.

**Acceptance run (2026-09-29, `docs/acceptance-report.md`).** Rulings recorded while running 07 B.4
end to end on a fresh install:

- **Critical CSS ships as a linked file, not an inline block.** `assets/css/critical.css` (~14 KB gz)
  is loaded as a blocking `<link>` with `site.css` preloaded low-priority behind it; `head-meta.php`
  falls back to the single blocking `site.css` link only when `critical.css` is absent. The 14 KB *raw*
  budget in 07 B.2.1 is superseded — the foundation layers are 21 KB raw as built and the measured
  numbers live in `docs/lighthouse/summary.md` and `docs/perf/critical-css.md`. `critical.css` and
  `assets/js/css.js` are `required_files` in `dev/zip-manifest.php` so a build cannot ship without them.
- **COD fallback is enforced in code (07 A.5.3 rule 3).** `payment_methods_enabled()` returns `['cod']`
  and logs a warning when every enabled method is unusable (all manual methods still `REPLACE ME` and COD
  switched off); before this an owner could reach a checkout that offered nothing.
- **Dashboard placeholder banner (07 A.5.3 rule 2, B.4.6)** is the red `adm-banner` on the dashboard
  listing every enabled manual method whose account details still contain `REPLACE ME`.
- **`/track` is not in the sitemap.** 07 B.1.4 already `Disallow`s it in `robots.txt`; listing a blocked
  URL in the sitemap only produces Search Console warnings. B.4.9's "exactly the active catalogue" wins.
- **A sold-out default size never lands the visitor on a dead button.** `product_pick_default_size()`
  keeps the owner's default unless it is out of stock and another size is available, in which case the
  first in-stock size is preselected (the `?size=` request parameter still wins). Without this a
  no-JavaScript visitor could not buy the other size at all (B.4.10).
- **Dev router mirrors the host for denied paths.** `dev/router.php` refuses
  `/admin/(controllers|views|partials)` like the per-directory `.htaccess` files do, renders every denied
  path through `index.php` with `REDIRECT_STATUS` set (the branded 404, exactly what `ErrorDocument 403`
  produces on LiteSpeed), and serves static files before enabling `zlib.output_compression` — the old
  order made `php -S` return an invalid response for gzip-negotiated `robots.txt` and `favicon.ico`,
  which is what cost Lighthouse its Best Practices and SEO points on localhost.
- Voice rule: the WhatsApp prefill texts open with "Assalam-o-Alaikum." (no exclamation mark, no
  "Hi Sky Fragrances").
