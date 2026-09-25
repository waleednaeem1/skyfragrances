# Admin — coupons, reviews, messages, subscribers, pages

Stage-4 notes for the five non-order admin sections owned by the marketing run. The spec is
`docs/plan/05a-admin-core.md` §4.5–4.8 and §5 as amended by `08-decisions-register.md`
(C-44 coupon limits, C-62 HTML sink, C-65 export logging, C-68 sample reviews).

Every screen renders through `render_admin()` inside the stage-3 shell: cards below 768px, a
table above, filters as plain GET so URLs are bookmarkable, one status chip per row, and row
actions collapsing into the `⋮` bottom sheet on a phone. Every mutation is a POST that passes
the front controller's CSRF + origin gate, writes one `admin_activity_log` row through
`catalogue_log()` (PII recorded as `"(changed)"`), sets a flash and answers 303.

## Coupons — `admin/controllers/coupons.php`, `coupon-form.php`

- List: code, discount (`10% off` / `Rs. 500 off`), min order, `used / limit` (`∞` when
  unlimited), window, computed chip Active · Scheduled · Expired · Used up · Disabled.
  Filters: status, type, search on code; sorts on code / used / expiry.
- Form: code (≤32, `[A-Z0-9-]`, forced upper, unique → *That code already exists.*), type,
  value (percent 1–90 integer; fixed > 0 ≤ 100,000), min order, total uses (blank = unlimited,
  never below `used_count`), uses per phone, starts/expires (Karachi, expiry > start), active.
  `used_count` is read-only; the usage card reads `coupon_redemptions` (applied vs reverted,
  discount given, distinct phones, last eight orders).
- Actions: edit, enable/disable (one POST, no confirm), copy code, WhatsApp share
  (`wa.me/?text=` with the coupon's own terms). There is no delete route in v1 — a used code
  would orphan `orders.coupon_*` snapshots; disabling is the reversible answer.

## Reviews — `admin/controllers/reviews.php`

- Default view is `status = pending`, oldest first. Tabs Pending / Approved / Rejected / All;
  filters product, rating, *Sample data only*, search; sort by date or rating.
- Each row shows the product with its thumbnail (links to the product form), customer and
  city, five star glyphs plus the number, title, the **full** body, the received time as a
  relative phrase with the absolute date in `title=`, the status chip and a *Sample* badge.
- `POST /reviews/{id}/status` with `to` ∈ `approved | rejected | pending | delete`. Delete
  needs `confirm_word=DELETE`. Every change goes through `reviews_set_status()`, which uses a
  guarded `UPDATE … WHERE id AND status = :old` and then `reviews_recompute_rating()`, so
  `products.rating_avg / rating_count` only ever count approved rows.
- `POST /reviews/bulk` with `action` ∈ `approve | reject | pending` and `ids[]`, or
  `delete_sample` + `confirm_word=DELETE`, which removes every `is_sample = 1` row (the C-68
  launch blocker) and recomputes each touched product.
- The admin never creates a review.

## Contact messages — `admin/controllers/messages.php`

- List newest first, default tab New (Read / Replied / Archived / All), search on name, email,
  phone (digits only too), subject and body. The card clamps the body to two lines; the detail
  shows it whole with `white-space: pre-wrap`.
- Opening a message that is `new` sets `status = read` and `read_at` with a guarded update.
- Detail actions: *Reply on WhatsApp* (`wa.me/92…` from `phone_normalize()`, text from the
  `whatsapp_reply_template` setting with `{name}` = first name), *Reply by email*
  (`mailto:` with `Re:` subject), Mark replied, Archive, Move back to New, and Delete (typed
  `DELETE`; a `<noscript>` input covers JS-off). A private `admin_note` saves together with the
  status select. `order_number` links to the order when it matches the SF-YYMMDD-XXXX shape.
- No mail is ever sent from this screen.

## Newsletter subscribers — `admin/controllers/subscribers.php`

- List with status chip, source, subscribed / unsubscribed times; counters in the subtitle;
  filters status, source, search on email.
- `POST /subscribers/{id}/status` with `to` ∈ `subscribed | unsubscribed | delete` (typed
  `DELETE`). Unsubscribing keeps the row and stamps `unsubscribed_at`.
- `GET /subscribers/export.csv` honours the list filters; default scope is
  `status = subscribed`, `?status=all` exports everything and names the file
  `sky-subscribers-all-YYYY-MM-DD.csv`. Rules from 05a §5.1: UTF-8 BOM, `,` and `"`, CRLF,
  formula-injection prefix `'` on `= + - @ TAB CR`, dates `YYYY-MM-DD HH:MM`, NUL stripped,
  unbuffered PDO cursor, one `subscribers.export` activity row with filter summary and count.
- The `export.php` controller shared with the orders export is a route-name dispatcher: it
  requires `subscribers.php` for `admin.subscribers.export` and `orders-export.php` for
  `admin.orders.export` when that file exists.
- *Add subscriber* from 05a §4.8 has no route in `routes-admin.php` and is not built.

## Content pages — `admin/controllers/pages.php`

- List of `content_pages` ordered by `sort_order`; edit, view on site.
- `GET|POST /pages/{slug}`: title (≤160, required), heading (≤160), body, live flag
  (system pages stay live), SEO title (≤160, counter warns at 60) and SEO description (≤255,
  warns at 160). The slug is fixed because the storefront routes are fixed.
- Body always passes `sanitize_html()` on save (C-62); the storefront applies it again on
  render. A body that is empty after sanitising is rejected.
- The `page-faq` template edits question/answer pairs in a repeater instead of raw HTML. On
  load the body is parsed with the same `<h3>…</h3>` regex `app/controllers/page.php` uses; on
  save each pair becomes `<h3>Question</h3><p>…</p>` (blank line = new paragraph, plain text
  escaped, existing inline `<strong>/<em>/<a>` kept), then sanitised. Blank pairs are dropped;
  a half-filled pair is an error; at least one pair is required.

## Local verification

Run through the built-in server (`docs/dev-run.md`) against the dev database: every GET
screen rendered 200 with no PHP warnings; approve / reject / pending / delete / bulk
approve / delete-sample recomputed the product cache correctly; message open-marks-read,
note + status, delete; subscriber unsubscribe / resubscribe / delete; CSV BOM, CRLF and
apostrophe prefix; page save with `<script>`, `onerror` and `javascript:` payloads stored
only `<p>Hi <a>bad</a> <a href="https://…" rel="nofollow noopener">ok</a></p><h2>Sec</h2>`.
