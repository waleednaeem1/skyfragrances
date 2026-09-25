# Stage 2 review — commerce correctness and security

Scope: `app/lib/{cart,pricing,coupons,orders,stock,payments,rate_limit,ratelimit,track,outbox,notifications,upload}.php`, the checkout / cart / track / contact / confirmation controllers, `app/controllers/api/*`, plus the callers they depend on (`admin/index.php` after-response hook, `admin/controllers/order-actions.php` proof stream, `mail.php`, `housekeeping.php`, `request.php`, `csrf.php`, `bootstrap.php`). Read against 06a, 06b and 08 §2.4 (C-43 … C-78). Review only; no edits were made.

Method: adversarial. For each attack class in the brief the code path was traced end to end and a concrete break was attempted. Findings list file:line, the attack, and the fix, ranked by severity. Section 3 records what held up so it is not "fixed" by accident later.

---

## 1. Findings (ranked)

### F-01 · HIGH · Outbox drain can send the same email twice, and any anonymous visitor can trigger it

**Where:** `app/lib/mail.php:257-287` (`mail_outbox_drain`), `admin/index.php:28-49, 62-64` (`admin_after_response`, registered before `auth_require()` runs).

**Attack.** `mail_outbox_drain` does `SELECT … WHERE status='queued' AND next_try_at <= :now LIMIT n` and then sends each row; the row is only marked `sent` after the SMTP round-trip (up to 5 s). Nothing claims the row first. Two overlapping drains — two admin tabs, one admin POST plus its 303 GET (both run the shutdown hook), or `cron.php` overlapping an admin click — both read the same due row and both send it. The customer receives two "Order confirmed" emails; the owner receives two admin alerts.

Worse, the hook is registered with `register_shutdown_function` at line 49, *before* `auth_require()` at line 63, so it also runs on every unauthenticated `GET /admin/login` and on every 404 under `/admin/`. An attacker who wants to make the shop's SMTP account send mail, or hold PHP workers for ~10 s each, requests `/admin/login` in a loop while a due row exists (a contact-form submission or a checkout puts one there). On a LiteSpeed shared plan with a small PHP worker pool that is a cheap denial of service against the storefront.

**Fix.**
1. Lease the row before sending, with a guarded statement and `rowCount() === 1`:
   `UPDATE email_outbox SET next_try_at = :lease WHERE id = :id AND status = 'queued' AND next_try_at <= :now` where `:lease` is now + 60 s. Skip the row if 0 rows matched. The lease doubles as the retry back-off floor so a crashed sender retries after a minute instead of never.
2. Gate the hook: in `admin_after_response`, return early unless `auth_user() !== null` (or set a flag after `auth_require()` succeeds and check it). C-56 says "every admin page load"; an unauthenticated login page is not an admin page load.
3. Optional: skip the drain on `redirect()` responses so one click drains once, not twice.

---

### F-02 · MEDIUM · C-58 cap leaks another customer's order number and blocks strangers behind the same NAT

**Where:** `app/lib/orders.php:119-125` (`order_outstanding_transfer`), `app/controllers/checkout-submit.php:122-125`.

**Attack.** The query matches `phone_normalized = :phone OR ip_hash = :ip` and the error interpolates the matched `order_number` into the customer-facing message: *"You already have a transfer order waiting for verification (SF-260925-K7QF)…"*. On an IP-only match that number belongs to someone else. Pakistani mobile carriers put thousands of subscribers behind one CGNAT address, and offices and campuses share one egress IP, so this is the common case, not the edge: customer B at the same café as customer A is (a) refused a bank-transfer order and (b) handed A's order number — the first half of the two-secret track key, and a real order number to social-engineer support with on WhatsApp.

**Fix.** Return which key matched. Include the order number only when the match was by phone (it is the customer's own); on an IP-only match use a generic sentence with no number, or downgrade the IP match to a flag on the admin alert (the phone match stays blocking). Add `LIMIT 1` selection order by phone match first so a phone match wins when both hit.

---

### F-03 · MEDIUM · C-58 cap has a gap and a race

**Where:** `app/lib/orders.php:122` (payment_status list), `app/controllers/checkout-submit.php:121-126` (check runs in Phase A, outside the transaction) and `:156-162` (post-commit proof step).

**Attack.**
- *Gap.* The cap counts only `payment_status IN ('awaiting_verification','failed')`. A manual-method order whose post-commit `payment_proof_store` throws (GD failure, disk full, DB blip — the code deliberately keeps the order in that case) stays `pending` / `unpaid`. It holds stock but does not count toward the cap, so the same phone/IP can keep placing transfer orders while that failure mode persists. `order_cancel_unpaid_transfers` at `orders.php:472` already treats `unpaid` as an unpaid transfer order; the two lists disagree.
- *Race.* The check runs before `BEGIN`. Two submits from the same phone in the same second both pass and both commit, so "one outstanding transfer at a time" is best-effort. Acceptable for a soft cap, but it should be stated in docs rather than implied to be exact.

**Fix.** Add `'unpaid'` to the list for manual methods (matches the bulk-cancel query). If the race matters, re-run `order_outstanding_transfer` inside `order_place_attempt` after the size rows are locked — the query is indexed on `(phone_normalized, created_at)` and cheap.

---

### F-04 · MEDIUM · With SMTP unconfigured the site claims "We've sent a confirmation" and writes full customer PII to disk forever

**Where:** `app/lib/mail.php:228-230` (`mail_deliver` → `mail_send` → preview), `:129-144` (`mail_preview_write`), `app/controllers/confirmation.php:18-22`, `app/controllers/checkout-submit.php:163-164`.

**Attack / failure.** When `config['smtp']['host']` is blank (a fresh install where the owner skipped the mailbox step, or a typo), `mail_transport_available()` is false, `mail_deliver` returns `mail_send(...)`, which writes an HTML file to `storage/logs/mail-preview/` and returns **true**. Consequences: (1) `$_SESSION['last_order']['email_sent']` is true and the receipt page tells the customer the confirmation was emailed when nothing left the server; (2) the admin alert is `mail_queue`d and never drained (drain returns early without transport), so the owner learns of the order only from the dashboard counter; (3) every preview file holds the customer's name, phone, street address and the tokenised order URL, and nothing purges that directory — `housekeeping.php` stubs `email_outbox` bodies after 60 days (C-65) but never touches `mail-preview/`.

**Fix.** In production `mail_deliver` should return `false` (and queue) when the transport is unavailable — preview mode is a development convenience, gate it on `APP_ENV !== 'production'`. Add `storage/logs/mail-preview/` to `housekeeping_run` with the same 60-day retention. Have the dashboard banner say "SMTP not configured" when `mail_transport_available()` is false and queued rows exist.

---

### F-05 · MEDIUM · Access token and cron key land in the application log

**Where:** `app/lib/csrf.php:37` (`'path' => $_SERVER['REQUEST_URI']`), `app/bootstrap.php:78, 95, 104` (exception and shutdown handlers log `REQUEST_URI`), `admin/index.php:68, 83`.

**Attack.** `REQUEST_URI` includes the query string. Any exception while rendering `/order/SF-…?t=<32hex>` (a view typo, a DB hiccup, the "controller returned without rendering" shutdown path) writes the customer's access token — the only thing gating their full street address and phone for 30 days — into `storage/logs/app-YYYY-MM.log`. The same applies to `cron.php?key=<secret>` on any failure inside the drain. Logs live in a denied directory, but they are the first thing copied into a support ticket, pasted into a chat, or left in a backup ZIP; C-65 already treats logged PII as a thing to avoid ("(changed)" in audit JSON). The log line cap is 4 KB, so nothing truncates a 32-char token.

**Fix.** One helper `log_path()` that returns `REQUEST_URI` with the query string stripped (or with `t` and `key` redacted), used by every `log_write` context that records a path. Cheap, mechanical, closes the class.

---

### F-06 · MEDIUM · `payment_review` can move a refunded order back to paid, and a reject can un-pay a paid order

**Where:** `app/lib/payments.php:181-196`.

**Attack.** `payment_review` locks the order and then guards only `approve && payment_status === 'paid'`. Approve from `refunded` → `paid` (with a fresh `paid_at`), and reject from `paid` → `failed` (with `paid_at = NULL`). The admin UI route `adm_order_post_payment` (`order-actions.php:241-277`) calls it whenever a proof with `review_status='pending'` exists, and a pending proof can outlive the order's payment state: a customer uploads twice before review, the admin approves one (the other stays pending), later refunds, then clicks *Mark paid* again — the order flips back to paid and the ledger now disagrees with the bank. Admin-only, so not an external attack, but it is the one place in the money flow with no state guard; every other transition has one.

**Fix.** Mirror `payment_mark_paid`: approve only from `unpaid|awaiting_verification|failed`; reject only from `awaiting_verification` (or `unpaid`). Return the existing "already …" message otherwise. Consider marking sibling pending proofs `superseded` when one is approved.

---

### F-07 · MEDIUM · Proof re-upload budget is spent before validation and by the initial checkout

**Where:** `app/controllers/confirmation.php:88-106` (`payment_proof_record_attempt` at :92 runs before `payment_reference_validate`/`payment_proof_validate`), `app/controllers/checkout-submit.php:157` (checkout also records an attempt), `app/lib/payments.php:128-138` (3 per order in a **30-day** window).

**Failure.** Spec: "re-uploads are limited to 3 per order". Here the initial upload consumes one, and each failed validation (wrong ref format, HEIC from an iPhone, file over 5 MB) consumes one more without storing anything. Realistic path: customer uploads at checkout (1), tries a HEIC (2), tries a 6 MB photo (3) — now `payment_proof_upload_limited()` is true, the form disappears from the receipt for 30 days, and the only remaining channel is WhatsApp. A rejected proof (admin `failed`) then cannot be re-uploaded at all. This is a conversion leak on the highest-value path in the shop, and it also punishes the exact customer the manual flow exists for.

**Fix.** Record the attempt after validation passes (count stored proofs, not form submissions), do not count the checkout-time upload, and shorten the window to 24 h or reset it when an admin rejects a proof. Keep the per-IP hourly limit where it is — that one is the abuse guard.

---

### F-08 · MEDIUM · `client_ip()` trusts `CF-Connecting-IP` behind any trusted proxy

**Where:** `app/lib/request.php:150-158`.

**Attack.** When `REMOTE_ADDR` is inside `trusted_proxies`, the candidate list is: rightmost untrusted `X-Forwarded-For` hop, then `HTTP_CF_CONNECTING_IP`. If the proxy does not set XFF for some requests (some edges strip it on HTTP/2 or only add it for certain paths), or if the XFF chain is entirely trusted addresses, the code falls through to a header the client supplied. Hostinger's edge is not Cloudflare and passes unknown headers through. Every `ip_hash`-keyed control — checkout 10/10 min, coupon 10/h, track 10/15 min, proof 5/h, contact, newsletter, and the C-58 per-IP cap — then keys on an attacker-chosen string, i.e. no limit at all.

**Fix.** Only consult `CF-Connecting-IP` when a config flag says the edge is Cloudflare; otherwise, when XFF yields no untrusted hop, fall back to `REMOTE_ADDR`. Reject candidates that are private/reserved ranges (`FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`).

---

### F-09 · LOW · Array-typed parameters turn into 500s and log lines

**Where:** every `(string) request_input(...)` / `(string) request_post(...)` cast — `api/cart.php:114, 119`, `checkout-submit.php:37, 54, 144-145`, `track.php:7, 14`, `contact.php:14`, `confirmation.php:64, 78`, `api/newsletter.php:21-22`.

**Attack.** `code[]=X` (form) or `{"code":["X"]}` (JSON) makes `(string)` emit "Array to string conversion", which the strict error handler in `bootstrap.php` converts to an `ErrorException` → incident page → one 4 KB `error` line per request with a 2 KB trace. Ten requests a second fills `storage/logs/` on a shared plan; each also runs the shutdown handler. No integrity impact, just a free log-flood and 500 generator on unauthenticated endpoints.

**Fix.** One helper `request_string(string $key, string $default = ''): string` that returns the default for non-scalar input, used everywhere a string is expected. Rate-limit `log_write('error', …)` per incident class if the log is to survive a scanner.

---

### F-10 · LOW · Form trap's 3-second floor fires on legitimate re-submits

**Where:** `app/lib/rate_limit.php:4` (`FORM_TRAP_MIN_SECONDS = 3`), `app/controllers/checkout-submit.php:43-47`.

**Failure.** On a validation error the checkout re-renders with a fresh `ts`. A customer who fixes one field and presses *Place Order* inside three seconds is told "Your session expired" and logged as `form trap: checkout bot`. Same on `/track` and `/contact`. The trap also blocks a fast customer on first submit when the address was auto-filled.

**Fix.** Carry the original `ts` through re-renders (accept the posted token as the field value when its signature is valid and it is not expired), and apply the 3-second floor only to the first render. Alternatively drop the minimum to 1 s on re-renders.

---

### F-11 · LOW · The "(was Rs. N)" figure comes from an unverified client number

**Where:** `app/controllers/checkout-submit.php:24-33`.

**Attack.** `price_total` is read and shown back as "was Rs. N" whenever it differs from the new total. The server never trusts it for the order — `order_seen_matches` at `orders.php:242-251` requires both the HMAC and equality — so there is no money impact. It is only used for a screenshot-able claim ("was Rs. 0") in the customer's own browser.

**Fix.** Show the "was" figure only when `pricing_reprice_token((int) $seen, cart_fingerprint(...))` matches the posted `price_token`; otherwise omit the parenthesis.

---

### F-12 · LOW · Session id is not regenerated after COMMIT

**Where:** `app/controllers/checkout-submit.php:166-169`.

**Gap.** 06a §5.3 ends with "clear `$_SESSION['cart']`, regenerate the session id, redirect 303". The cart and idem key are cleared but `session_regenerate()` is not called, so the pre-checkout `SFSHOP` id (which was live while the customer typed their address) continues for three days. There is no fixation vector (`use_strict_mode=1`), so this is spec fidelity rather than an exploit.

**Fix.** Call `session_regenerate()` after `cart_clear()`; it also rotates the CSRF token, which is what C-76 expects at trust-boundary moments.

---

### F-13 · LOW · Housekeeping deletes admin proof thumbnails as "orphans"

**Where:** `app/lib/housekeeping.php:58-73` (glob `*.*`, referenced only if `file_path` equals the path or basename), `admin/controllers/order-actions.php:467-488` (writes `<32hex>-320.jpg` beside the proof).

**Failure.** The thumbnail has no `payment_proofs` row, so 24 h after it is written the purge deletes it; the next admin view decodes the full proof through GD again and rewrites it. Harmless churn, but it means a GD decode of a customer-supplied file on every admin list view older than a day, and the purge report over-counts "orphans".

**Fix.** Exclude `-320.jpg` from the orphan glob (or store thumbnails under `storage/cache/proof-thumbs/` and let them expire on their own).

---

### F-14 · LOW · Exhausted-under-lock coupon gets the wrong sentence

**Where:** `app/lib/orders.php:228-230` (any `coupon_error` other than `phone_limit` becomes `ORDER_ERR_COUPON_DROPPED`), `:324-326`.

**Gap.** 06b §1.7 / §7.2 and C-44: the customer who loses the last use of a limited code is told *"Coupon {CODE} has just reached its usage limit. Your order total is now Rs. X."* Because `cart_resolve` re-validates on the `FOR UPDATE`-locked coupon row before `coupon_claim_use` runs, the `exhausted` case is almost always caught by the precheck and mapped to the generic *"{code} is no longer valid and has been removed from your order"*. The behaviour (rollback, coupon dropped, re-confirm) is correct; only the copy differs from spec. Same for `expired`.

**Fix.** Map `coupon_error.key` to the matching order error (`exhausted` → `ORDER_ERR_COUPON_EXHAUSTED`, `expired` → a dated expired message) instead of collapsing everything except `phone_limit`.

---

### F-15 · LOW · Parallel helpers and a file in the wrong noun

**Where:** `app/lib/rate_limit.php` vs `app/lib/ratelimit.php`; `csrf_submitted_token()` (`csrf.php:17-29`) vs `request_csrf_token_sent()` (`request.php:290-299`); `outbox_drain_after_response()` (`outbox.php:22-41`, no callers) vs `admin_after_response()` (`admin/index.php:28-49`).

**Gap.** The ground rules say one file per noun and no parallel helpers. Two rate-limit files with different prefixes (`rate_limit_*` in both, but only one is bootstrapped) will drift; `rate_limit.php` also hosts the form-trap functions, which are not rate limiting. The unused outbox drainer duplicates the admin hook and will be the one someone "fixes" next time.

**Fix.** Fold `rate_limit.php` into `ratelimit.php` (or rename to match), move `form_trap_*` to `app/lib/forms.php`, delete `outbox_drain_after_response` and have `admin/index.php` call a single `outbox_drain_after_response()` that carries the F-01 auth gate.

---

### F-16 · LOW · Percent coupon value is rounded instead of rejected

**Where:** `app/lib/pricing.php:31`.

**Gap.** `(int) round((float) $coupon['value'])` turns a hand-edited `12.50` into 13 %. The admin form stores integers only (`coupon-form.php:160-163`), so nothing drifts today; but the storefront is the last line and 06a §3.2 says the value *is* an integer. A rounded percentage is a silent discount change.

**Fix.** If `money_paisa($value) % 100 !== 0` treat the coupon as `invalid` (and log a warning) rather than rounding.

---

### F-17 · LOW · Contact admin notifications are uncapped site-wide

**Where:** `app/controllers/contact.php:26, 52` (bucket counts successes only, 5/h per IP), `app/lib/notifications.php:151-160` (admin mail always queued).

**Attack.** C-63 caps *auto-replies* at 30/h. The admin copy has no site-wide cap, so a scanner with rotating IPs (5 valid submissions each) fills the owner's inbox and the outbox with `contact-admin` rows, each carrying an attacker-controlled `Reply-To`. Every queued row also costs a drain slot, delaying real order alerts behind it.

**Fix.** Apply the same hourly site-wide cap to `contact-admin` (queue a single digest row beyond it), and count failed validations toward the `contact` bucket so a probe cannot retry for free.

---

## 2. Attack classes tried and what happened

| Class | Result |
|---|---|
| Client number reaching a total | Blocked. Only `size_id`, `qty`, `code`, `action`, customer fields and `payment_method` are read. `price_total`/`price_token` are compared, never used (`orders.php:242-251`). See F-11 for the cosmetic echo. |
| Rounding drift | None. Integer paisa end to end; the only rounding is `pricing_percent_discount` half-up to the rupee (`pricing.php:25-28`); `pricing_apportion_discount` is largest-remainder and sums exactly. |
| Coupon stacking / reuse | One `coupon_code` slot in the session; replace-not-stack. Cancel sets `reverted` and decrements with a signed floor (`coupons.php:127-140`). |
| Per-phone bypass by formatting | `phone_normalize` collapses `0300…`, `+92 300…`, `92300…`, `300…` to one form; every other shape fails `^\+923\d{9}$` and is rejected, not accepted differently. |
| Exhausted mid-checkout | Coupon row `FOR UPDATE` (after size rows), re-validated on the locked row, then guarded `UPDATE … used_count < usage_limit`. Correct; copy drift only (F-14). |
| Stock race / oversell | `cart_load_rows(..., true)` locks sizes in PK order; `stock = stock - :qty WHERE stock >= :qty` with `rowCount() === 1`; a clamp inside the transaction is a rollback, not a silent reduce (C-43). |
| Double decrement / restore twice | Lines deduped in `cart_get`; restore guarded by `stock_restored_at IS NULL` in the same `UPDATE` (C-12); explicit stock-back requires `cancelled`. |
| Idempotency replay → another customer's order | Key must `hash_equals` the session's `idem` or `idem_done`; the row's UNIQUE index is the record; fingerprint compared before redirect. PHP session locking serialises the double-tap. |
| Transfer-order caps | Enforced pre-transaction; see F-02 (leak / CGNAT) and F-03 (gap / race). |
| Proof upload | `is_uploaded_file`, `finfo` + `getimagesize` type agreement, pixel budget, GD re-encode to JPEG, random name, denied `storage/`, `realpath`-anchored admin stream with `nosniff` and `default-src 'none'`. No SVG/HTML/double-extension path exists. |
| Track oracle / timing | One miss sentence, same 200 for hit and miss, weighted limits on `ip_hash` and phone, 250 ms floor; `/order/{n}` wrong-token and unknown-number are indistinguishable and counted (C-76). |
| Spoofable IP | Trusted-proxy model is right; the `CF-Connecting-IP` fallback is the one soft spot (F-08). |
| CSRF on API | Global POST check in `index.php` (header or body token) plus Origin/Referer check in every API controller; cookies are `SameSite=Lax`. No gap found. |
| Outbox unbounded | Bounded by `LIMIT` and wall-clock; back-off and `failed` after 6. The problem is duplication and the unauthenticated trigger (F-01). |
| PII in logs | Order-level logs carry order numbers and `ip_hash` only; the leaks are `REQUEST_URI` query strings (F-05) and preview mail (F-04). |
| Prepared statements | Every statement uses named bound params; identifiers pass `db_identifier`; the two dynamic fragments (`order_recent_count` column, drain `LIMIT`) are whitelisted / integer. |

---

## 3. Keep (verified correct — do not "fix")

- `cart_price()` is the single pipeline, called on every render and inside the transaction on locked rows; free shipping is tested on the pre-discount subtotal; `grand_total < 0` throws.
- `order_seen_matches` requires HMAC **and** equality with the recomputed total — a favourable change also re-confirms (C-43).
- Lock order: size rows ascending, then the coupon row; `order_is_deadlock` covers `40001`, `1213` and `1205`; retries are bounded.
- Duplicate `uk_orders_number` retries inside the transaction; duplicate `uk_orders_idempotency` is a replay, not an error.
- `coupon_revert_for_order` uses `GREATEST(CAST(used_count AS SIGNED) - 1, 0)` — the cast is what keeps strict-mode MySQL 8 / MariaDB from raising on the unsigned column.
- `payment_proof_store` writes the file post-commit, keeps the order when the write fails, and moves `payment_status` with a guarded `UPDATE … IN ('unpaid','failed')` (C-57, C-59).
- `PDO::MYSQL_ATTR_FOUND_ROWS => true` is compatible with every `rowCount() === 1` guard here because each guarded `UPDATE` changes a column whenever it matches.
- Track shows first name, city, totals, status, courier once shipped, `https://` tracking URLs only; never address, phone, email, token or proof.
- Newsletter and contact responses are identical for new and existing addresses; auto-reply is deduplicated per address for 24 h and capped at 30/h (C-63).
