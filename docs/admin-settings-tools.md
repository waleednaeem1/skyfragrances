# Admin — Dashboard, Settings, Tools, Activity log

Owner: the admin-settings run. Files: `admin/controllers/{dashboard,settings,tools,activity}.php`,
`admin/views/{dashboard,settings,tools,tools-confirm,tools-progress,activity}.php`, plus one
route line (`admin.activity`) in `app/routes-admin.php`. Specs: 05a §4.1, §4.9, §6; 08 C-49,
C-52, C-56, C-57, C-58, C-64, C-65, C-69, C-70, C-73; 07 A.7; 05b §11 (activity log).

Every screen is rendered through `render_admin()` inside the shared shell, so the 375 px layout
(cards instead of tables, sticky action bar, bottom tab bar) comes from the shell partials. Every
POST passes the front controller's CSRF + Origin gate before a controller runs; controllers never
repeat it. Every mutation ends in `flash()` + `redirect(…, 303)`.

## Dashboard — `GET /admin`

Purpose: "what happened today, what needs me". Read-only.

Data, top to bottom (mobile): banners (installer still present · maintenance on · sample data
still present · panel host ≠ `base_url`), greeting + Karachi date, 2×2 KPI grid, revenue
definition line, "This month" status chip rail, *Needs you* card, latest 5 orders, low stock (8),
best sellers (5, 30 days), last sign-in + Activity log link.

Revenue = `SUM(grand_total)` over `status IN ('confirmed','packing','shipped','delivered')`
(accepted, not collected). All day boundaries are computed in PHP in Asia/Karachi and bound as
strings; MySQL never does date arithmetic on the server timezone.

| # | Query | Index used |
|---|---|---|
| 1 | `SELECT DATE(created_at) d, COUNT(*) c, SUM(grand_total) v FROM orders WHERE status IN (…) AND created_at >= :month_start GROUP BY DATE(created_at)` — folded in PHP into today / yesterday / month | `idx_orders_status_created (status, created_at)` |
| 1b | same shape for yesterday when yesterday falls in the previous month | same |
| 2 | same shape bounded `>= :prev_month_start AND < :month_start` | same |
| 3 | `SELECT status, COUNT(*) FROM orders WHERE created_at >= :month_start GROUP BY status` | `idx_orders_created` |
| 4 | attention counters: `COUNT(*)` on `orders.status='pending'`, `reviews.status='pending'`, `contact_messages.status='new'`, unpaid transfers older than `manual_hold_hours` (`payment_method IN (bank,jazzcash,easypaisa) AND status='pending' AND payment_status IN (unpaid,awaiting_verification,failed) AND created_at < :before`), `email_outbox` queued/failed via `mail_outbox_counts()` | `idx_orders_status_created`, `idx_reviews_queue`, `idx_orders_payment`, `idx_email_outbox_due` |
| 5 | latest 5 orders `ORDER BY created_at DESC, id DESC LIMIT 5` | `idx_orders_created` |
| 6 | low stock — 05a §6.2 with `p.deleted_at IS NULL AND ps.is_active = 1`, `LIMIT 8` | `idx_product_sizes_stock (stock)` |
| 7 | best sellers — `order_items JOIN orders` over accepted statuses in the last 30 days, `GROUP BY product_id`, name from `MAX(product_name)` so deleted products still rank | `idx_orders_status_created`, `idx_order_items_product` |
| 8 | sample-data check: `reviews.is_sample = 1` count, else manifest slugs still present with `deleted_at IS NULL` | `idx_reviews_sample`, `uk_products_slug` |

The badges for the bottom nav are passed through `$head['badges']` so the layout skips its own
two COUNT queries. Storage usage comes from `housekeeping_storage_usage_bytes()` and
`disk_total_space()`; red above 80 %.

Empty state: every tile shows `Rs. 0`, the order list shows "No orders yet — share your shop link
on WhatsApp to get started."

## Settings — `GET|POST /admin/settings?tab=`

Tabs: Store · Contact & Social · Home · Shipping · Payments · SEO · Advanced. The definition array
`settings_definitions()` in the controller is the single source for the form, validation and
group; every key from 08 §1.4 / 05a §4.9 is present (including `business_hours`,
`instagram_tile_1..6_image/url`, `cod_max_total`, `manual_hold_hours`, `base_url`,
`trusted_proxies`, `maintenance_bypass`, `https_permanent`, `site_indexable`, and the
runtime keys `images_webp_enabled`, `install_completed_at`).

A POST saves only the keys of the posted tab (hidden `tab` field). Types and rules:

- `text`/`textarea` — trimmed, control characters stripped, `maxlength` from the definition.
- `int` — `FILTER_VALIDATE_INT` within `min..max`.
- `money` — `^\d{1,8}(\.\d{1,2})?$`, commas stripped, stored with two decimals via paisa helpers.
- `email` — `FILTER_VALIDATE_EMAIL`, lower-cased.
- `phone` — digits, `+`, spaces, dashes; `whatsapp` normalised with `phone_normalize()`, others kept as typed.
- `url` — `https://` only, or a site-relative `/path` where the definition allows it.
- `baseurl` — scheme + host only, lower-cased; hint shows the host the panel was opened on (C-49).
- `cidr` — comma-separated IPs/ranges, each validated.
- `bool` — checkbox → `1`/`0`.
- `image` — `upload_validate()` (type by magic bytes, ≤ 6 MB, pixel budget) → GD resize (OG cover 1200×630) → `uploads/settings/{key}-{10hex}.{ext}`; the old file is unlinked only after the new one is stored; "Remove and go back to the default" checkbox.
- read-only / action rows: `timezone`, `install_completed_at`, `maintenance_bypass` (Copy + *New key*), `https_permanent` (*Make HTTPS permanent* → `POST /admin/settings/https-permanent`), `images_webp_enabled` (*Re-scan*).

Cross-field (Payments tab): at least one method on; enabling bank/JazzCash/Easypaisa requires
its number and account title. `free_shipping_threshold = 0` shows "Delivery is always free".
Errors render an `.adm-summary` list with anchors plus inline field errors; nothing is written
when any field fails (a freshly uploaded image is discarded).

Write path: `settings_write()` upserts each changed key in one transaction, then
`settings_cache_clear()` + `settings_load()` so `storage/cache/settings.json` is rebuilt at once
(C-70). Audit: one `admin_activity_log` row `setting.update` naming the changed keys; values of
phone/email/account keys are recorded as `(changed)`.

Maintenance: the Advanced tab is the switch (`maintenance_mode`); the help text names the
`storage/MAINTENANCE` File-Manager fallback. `config.php` is never written by the panel.

## Tools — `GET /admin/tools`

Read-only index with four KPI tiles (storage/ usage + disk %, proof files on disk, emails not yet
sent, image count + WebP availability) and cards: failed emails (last 5 with their error), Sample
data, Privacy purge, Regenerate images, HTTPS redirect, Stock-back audit, Recent tool runs,
password-recovery hint (C-64). Every action is a POST behind the shared confirm dialog and,
where destructive, a typed word.

### Remove sample data — `GET|POST /admin/tools/remove-sample-data` (07 A.7)

GET lists *Will be deleted* / *Will be kept* built from `db/sample-manifest.php`. POST requires
`confirm_word === 'DELETE'` (visible field, works without JS) and runs one transaction:

1. `DELETE FROM reviews WHERE is_sample = 1`.
2. Each manifest product still live (`deleted_at IS NULL`): if any `order_items.product_id`
   references it → retired (`is_active = 0`, `deleted_at = now`) and reported as kept; else
   `DELETE` (sizes, images, reviews cascade) and its derivative files are queued for unlinking.
3. Each manifest collection: deleted only when no live product still points at it, otherwise kept.
4. Each manifest coupon: deleted when never used (`used_count = 0`, no redemption, no order);
   otherwise switched off (`is_active = 0`) and reported.
5. `products.rating_avg/rating_count` recomputed from approved reviews.

Files are unlinked after COMMIT, only when no remaining `product_images` row still references
the same base name; empty `uploads/products/sample` and `uploads/og/sample` directories are
removed. Settings are never touched — the flash tells the owner to replace placeholder payment
details under Settings › Payments. One `admin_activity_log` row `tool.remove_sample_data`
carries the full report. The dashboard banner and the Tools badge clear once no live manifest
product, collection, unused coupon or sample review remains.

### Privacy purge — `GET|POST /admin/tools/purge` (C-65)

GET shows the counts it will act on. POST: proof files for orders `delivered|cancelled` whose
`COALESCE(delivered_at, cancelled_at, updated_at)` is older than 90 days are unlinked
(`image_proof_path()` validates the stored path) and the row gets `file_path = NULL`,
`purged_at = now` (≤ 500 rows per run); then `housekeeping_run()` prunes sent email bodies
> 60 d, `rate_limits` / `admin_login_attempts` > 30 d, expired session files and orphan proof
files. One `tool.purge` activity row with every count.

### Regenerate images — `GET|POST /admin/tools/regenerate-images` (C-73)

POST (CSRF-gated) creates a session job `{token, started, done, failed, total}` and redirects to
`?offset=0&job={token}`. Each GET with a matching token processes rows for ≤ 20 s (≤ 60 rows),
then renders `tools-progress.php` with a progress bar and `<meta http-equiv="refresh"
content="1;url=…next offset…">` plus a *Continue now* button for browsers that ignore it; the
last batch clears the job, logs `tool.regenerate_images` and redirects to Tools with a flash.
A GET without a valid token renders the idle screen with a Start button. Row order is
`product_images` by id then `collections` with an image; the source is the largest surviving
derivative (`-zoom.jpg` → `-zoom.webp` → base file); outputs are written to a temp name and
renamed so a request mid-write never sees a partial file. WebP is written only when GD supports
it.

### HTTPS redirect (C-52)

Same action as Settings › Advanced: `POST /admin/settings/https-permanent` runs
`https_make_permanent_if_ready()` (curl `https://{host}/robots.txt`, 5 s, verified certificate)
and rewrites `R=302` → `R=301` in the root `.htaccess`; the button is disabled while the panel is
open over plain http or on localhost.

### Stock-back audit (C-48)

Lists `orders WHERE status = 'cancelled' AND stock_restored_at IS NULL` (latest 20, with the
total) — orders cancelled after shipping whose stock is still out. The action itself stays on the
order detail screen (*Parcel received back — restore stock*), so the audit only links there.

## Activity log — `GET /admin/activity`

Filters (all GET, bookmarkable): `q` (summary LIKE, wildcards escaped), `entity`, `id`,
`action`, `from`, `to`. Invalid values are dropped silently. Rows come from
`admin_activity_log` newest first with a `LEFT JOIN orders` for the order number, paginated by
`admin_rows_per_page` (10–100). Indexes: `idx_admin_activity_log_time`, `_entity`, `_admin_time`.
Each row: time, admin, action badge (maroon for delete/fail/cancel/reject, green for
create/ok/paid), a link to the entity where a route exists (order, product, collection, coupon,
message, settings), the summary, and a collapsed *Details* block showing before/after JSON as
`key: value` lines. Rows are never editable.

## Outbox drain (C-56)

The shell hook `admin_after_response()` in `admin/index.php` already runs `mail_outbox_drain()`
(≤ 2 sends, 12 s) and `housekeeping_maybe_run()` (1-in-20) after every admin response; the
dashboard's *Needs you* card surfaces queued/failed counts and Tools lists failed emails with
their last error.

## Needs from other owners

- `app/lib/housekeeping.php`: `housekeeping_prune_proof_orphans()` queries
  `payment_proofs.stored_name`, a column that does not exist (the schema has `file_path`), so the
  orphan sweep silently returns 0 — change the lookup to
  `WHERE file_path = :rel LIMIT 1`. Also add the 90-day proof purge to `housekeeping_run()` so
  the 1-in-20 background run matches the Tools button:
  `function housekeeping_purge_old_proofs(int $days = 90): array` returning
  `['files' => int, 'records' => int]` (the same statement as `tools_purge_proofs()`).
- `app/lib/flash.php`: an optional `flash(type, message, ?link, ?link_label)` would let the
  sample-removal flash link straight to Settings › Payments.
- `admin/controllers/export.php` (05a §5.1): write one `admin_activity_log` row per CSV export
  (`entity_type = 'export'`) — the activity viewer already lists that area.
- `admin/partials/nav-bottom.php` / `nav-side.php`: add an *Activity log* link
  (`/admin/activity`) to the More sheet and the sidebar.
