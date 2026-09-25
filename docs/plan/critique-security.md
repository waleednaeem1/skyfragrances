# Critique — Application security and commerce correctness

Reviewer lens: adversarial. The specs are assumed to be implemented literally, section by
section, by a builder who reads the register first and then the owning document. Every
finding below names the passage that is wrong or missing, says how it loses money, leaks
data or corrupts an order, and gives the fix. Generic advice the plan already covers (PDO,
`e()`, CSRF, GD re-encode, random filenames) is not repeated.

Ranking: BLOCKER = must be settled before stage 3 (checkout) is written; HIGH = will ship a
real loss or outage if built as specified; MEDIUM = exploitable or wrong, contained impact;
LOW = hardening or a spec contradiction a builder could resolve the wrong way.

Documents read in full: PLAN.md, 08-decisions-register.md. Sections read: 06b §0–§7,
06a §0–§7, 01b §1–§2, §5–§6, §9, 02c §1–§5, §7–§8, 02b §2–§7, 05a §1–§2, §4.3–§4.10, §5,
05b §0.3–§1.8, §2.3–§3.3, §4–§6, §9–§11, 03 §1.1, §6.7, §7.7–§10, §12, 07 A.3, A.7, B.3–B.4.9,
01a §2.2–§2.3, §2.6, §3.6.

---

## BLOCKER

### B-1. 06b Phase B is a second pricing pipeline, and it disagrees with the settled one

06b §1.4 ("Phase B — re-price, authoritative, server-side") re-implements pricing inline
instead of calling the 06a §2.2 function, and it deviates from the rules the register and
PLAN §7 call settled:

| Rule | Settled (06a, PLAN §7) | 06b §1.4 as written |
|---|---|---|
| Free-shipping test | pre-discount subtotal (06a §4.1, PLAN §7.3) | step 7: `subtotal - discount_total >= threshold` (post-discount) |
| Percent rounding | half-up to the whole rupee, the only rounding (06a §2.2 step 4) | step 6: `ROUND(subtotal * value / 100, 2)` — to the paisa |
| Per-phone limit | check 9, inside the transaction under the coupon row lock (06a §3.4, §5.3) | absent from Phase B and Phase C; §7.2 gates it behind `settings.coupon_one_per_phone`, a key that exists nowhere (05a §4.9) |
| Coupon window | PHP-bound Karachi timestamp, never `NOW()` (06a §0) | `NOW()` in Phase B step 6 and Phase C step 5 (server clock, usually UTC on the host) |
| Price change direction | any change → rollback and re-confirm (06a §7.7) | "a favourable change proceeds silently at the better price" |
| Quantity clamp | qty reduced → rollback, re-confirm (06a §5.3 step 4) | §7.5 "the quantity is reduced, the order proceeds" |

01b §1.1's `shipping_fee` row repeats the post-discount test (`subtotal - discount_total >=
free_shipping_threshold`). C-13 patched 06a §0/§5.2 only; neither 06b §1.4 nor 01b §1.1 was
touched.

**How it loses money and orders.** A Rs. 3,100 cart with `WELCOME10`: the cart and checkout
page (06a) show *Free delivery*; Phase B computes 3,100 − 310 = 2,790 < 3,000 and charges
Rs. 250. That is the "unfavourable change" branch, so the customer is bounced to checkout with
"Some details changed" — then Phase B computes the same Rs. 250 again, the re-price token
matches, and the order is written at Rs. 250 shipping the cart promised was free. With an odd
subtotal (Rs. 4,955 × 10 %) the paisa rounding makes `grand_total` differ from the rendered
total by 50 paisa on every attempt, so the 06a §6.2 token never matches and the customer can
never place the order. Both are the exact failure 06a §4.1 was written to prevent.

**Fix.** Strike 06b §1.4 steps 3–8 and replace with "call the one 06a §2.2 pricing function on
the locked rows"; strike the post-discount predicate in 01b §1.1; add per-phone (check 9) to
Phase C between the stock decrements and the coupon `UPDATE`, counted from
`coupon_redemptions WHERE coupon_id=? AND phone_normalized=? AND status='applied'` while the
coupon row is held `FOR UPDATE`; bind `now` from PHP everywhere the specs say `NOW()`; rule
that any change to the agreed total — up or down, quantity or price — rolls back and
re-confirms (06a §7.7 wins over 06b §1.4 and §7.5). Add a C-43 row to 08 §2 so a builder
reading 06b sees it.

### B-2. Coupon limits have two sources of truth, and the seed makes them disagree

- 06b §1.5 step 5: the limit is `UPDATE coupons SET used_count = used_count + 1 WHERE …
  used_count < usage_limit` — the **counter** is authoritative.
- 01b §6: "`used_count` is a cache for the admin list only; every limit decision counts the
  ledger … If a drifted counter ever contradicts the ledger, the ledger wins and the admin
  coupon page offers a one-click recount."
- 06a §5.3 step 7 uses the counter predicate; 07 A.3 uses the counter; PLAN §7.2 cites
  "06a §5.3, 08 C-15" and does not say which.

07 A.3 seeds `FIRST50` with `used_count = 50`, `usage_limit = 50`, `is_active = 1`, expiring
seed + 180 days, Rs. 750 off — and `EIDSALE500` at 137 — with **no** `coupon_redemptions`
rows. Under 01b's rule the ledger count is 0, so `FIRST50` is live for 50 more redemptions
(Rs. 37,500) the moment a builder follows 01b, or the moment the owner presses the recount
button 01b promises (which sets `used_count` to 0). PLAN §11.3 tells the owner the code is
"fully redeemed".

The cancel path is also split three ways: 06a §3.7 deletes the redemption row; 01b §6 sets
`status = 'reverted'`; PLAN §4.2 says "marks the row reverted" while PLAN §7.2 says "the
redemption row is deleted". A builder who deletes rows and counts the ledger loses the audit
trail 01b was written for; one who reverts rows but counts `used_count` has a counter that
drifts on every cancel.

**Fix.** Rule: the guarded `UPDATE coupons` predicate (06b §1.5 step 5) is the *only* global
limit check; `coupon_redemptions` is the audit trail and the *only* per-phone check; cancel
sets `status = 'reverted'` and decrements the counter in one transaction. Delete the "one-click
recount" from 01b §6, or define it as `used_count = COUNT(status='applied')` **and** make the
seed write matching ledger rows (50 for `FIRST50`, 137 for `EIDSALE500`) with a synthetic
`order_id`… which needs orders; simpler: seed `FIRST50` as `is_active = 0` with a note, and
seed `EIDSALE500` with `used_count = 0`. Either way the register must say which is truth.

### B-3. The username lock (05a §2.6) lets anyone lock the owner out of her own shop

05a §2.6 step 4: "If `fails_user >= 5` → locked on the username", window 15 minutes, no CAPTCHA,
no unlock button (§2.7: "there is no admin unlock button … no email unlock link"), and
"Correct credentials never shorten a lock". The username is not secret: it is whatever the
owner typed in `install.php`, and it is guessable (`admin`, the shop name, the email local
part) or readable over her shoulder. A competitor runs a one-line script that posts five wrong
passwords every fourteen minutes; the owner can never log in from any device, cannot confirm
COD orders, cannot verify payments, and the recovery path (§2.8 rename `reset-password.php`)
resets the *password* but does not clear `admin_login_attempts`, so the lock survives it.
This is the single cheapest availability attack on the system and it targets the one thing the
brief cares about most: "manage orders from my phone".

**Fix.** Keep the per-IP hard lock. Turn the per-username limit into (a) a progressive delay
(the §2.6 step 8 `usleep`, extended to 2 s at ≥ 5 fails) rather than a refusal, plus (b) a
"known device" cookie — `bin2hex(random_bytes(32))`, stored hashed in `admin_users`
(`device_tokens`, up to 5) and issued on every successful login — that exempts the request
from the username lock. Have `reset-password.php` also `DELETE FROM admin_login_attempts`
for the username. Record the decision in 08 §2 and update PLAN S8.

---

## HIGH

### H-1. "Client IP" is never defined, and Hostinger fronts the worker with a proxy

02b §2.3 states that Hostinger terminates TLS at an edge layer and the LiteSpeed worker sees
the proxy, which is why the HTTPS rule reads `X-Forwarded-Proto`. Every rate limit in the plan
is keyed on `ip_hash` — checkout 10/10 min (06b §1.3), track 10/15 min and 40/24 h (06b §6.3),
proof 5/h, review 3/day, contact 5/h, newsletter 5/h (03 §12.3), the login IP bucket (05a
§2.6), the 10 orders/IP/24 h abuse flag (06b §7.1) — and `ip_hash` is stored on orders,
reviews, proofs and the audit log. Not one document says where the IP comes from. The only
mention is `client_ip` in 05a §2.6.

- If the builder uses `REMOTE_ADDR` and the worker sees the edge address, every visitor
  shares one bucket: the eleventh checkout in ten minutes site-wide gets "Too many attempts";
  ten bad logins from any bot lock the owner's IP bucket; one abusive reviewer locks reviews
  for everyone. During any promotion this is a self-inflicted outage.
- If the builder reads `X-Forwarded-For` naively, every limit is bypassed by one header, and
  the `ip_hash` audit trail records whatever the attacker typed.

**Fix.** Specify in 02c §1 (bootstrap) one function `client_ip()`: trust `X-Forwarded-For`
(rightmost untrusted hop) **only** when `REMOTE_ADDR` is in a configured list of proxy CIDRs
written by `install.php` from what it observes (`REMOTE_ADDR` vs `X-Forwarded-For` on its own
probe request), else `REMOTE_ADDR`. Add an install-time probe row "Client IP detection —
PASS/WARN" and a settings override. Until this exists, none of the rate-limit commitments in
PLAN §10.1 are testable.

### H-2. Anonymous proof uploads are written to disk before the transaction, and nothing sweeps them

06b §1.6 rule 1: "The uploaded proof image is written to disk **before** `BEGIN` under a
temporary name and only *linked* to the order after `COMMIT`; an orphaned temp file is swept by
the same cron that drains the outbox (§5.6), which deletes unlinked proof files older than 24
hours." There is no §5.6, and 08 Q-18 / PLAN §2 assume **no cron**; the outbox drains on
admin page loads and no document assigns the orphan sweep to that path.

So every checkout POST that carries a valid image and then fails — a stock race, a coupon
race, an unfavourable re-price (B-1 makes this common), a deliberate bad `customer_phone` —
leaves a re-encoded file (up to 5 MB in, up to 1600 px re-encoded out) in `storage/proofs/`
forever. The checkout bucket allows 10 posts per IP per 10 minutes and IPs are free on mobile
data; a script fills the account's disk quota in an afternoon, at which point sessions,
logs, the settings cache and product uploads all fail and Hostinger returns 508. Nothing in
the admin panel lists or deletes orphans.

**Fix.** Do not write the proof before `COMMIT`. PHP keeps `$_FILES['proof']['tmp_name']` for
the life of the request; run the validation ladder in Phase A (as specified), keep the
validated temp path, and perform the GD re-encode + `storage/proofs/` write in the post-commit
step alongside the outbox insert, inside the same `try/catch` as email so a write failure
never loses the order (the admin gets "proof missing — request on WhatsApp"). Add the orphan
sweep to the admin-page-load drain anyway, and surface `storage/` usage on the dashboard.

### H-3. Stock is held indefinitely by unpaid manual orders, and only flags stand in the way

03 §8.5: "Stock is still decremented at placement — the client would rather hold stock for a
few hours than oversell." 08 Q-07: unpaid bank/JazzCash/Easypaisa orders are "flagged amber
after 48 h, never auto-cancelled". 06b §7.1: 5 orders per phone per day and 10 per IP per day
are "still accepted", only flagged. 05b §1.7: "Cancel is deliberately not a bulk action."
Proof validation accepts any valid image and any 5–30-character transaction ID.

Put together: an attacker places bank-transfer orders for the full stock of a product
(10 per line, 20 lines, one order per size) with a screenshot of anything, from a handful of
phone numbers and IPs. The storefront shows *Sold out* within minutes; real customers cannot
buy; the owner has to open each order, type a reason and confirm twice to release it, and the
attacker repeats tomorrow. The same trick removes a competitor's launch from sale for its
first weekend. The plan states the oversell side of the trade-off and never the denial side.

**Fix.** (a) Make the per-phone and per-IP caps *blocking* for manual-payment orders while a
previous manual order from the same phone or IP is still `awaiting_verification` or `failed`
(one outstanding unpaid transfer at a time; COD stays flag-only because the owner calls).
(b) Add a deliberate exception to 05b §1.7: bulk "Cancel unpaid transfer orders older than
{hold_hours}" with one reason applied to all, restoring stock per order under the C-12 guard.
(c) Cap the share of a size's stock that unpaid manual orders may hold (e.g. never let
`awaiting_verification` orders reserve the last unit while a COD order could take it).

### H-4. The customer's confirmation link can reset a paid order to "awaiting verification"

06b §4.2, Reject payment: "inviting a re-upload via `/order/{number}?t={token}`, which
re-opens the upload form while `status = 'pending'`." 06b §4.2 step 6: a proof insert sets
`orders.payment_status = 'awaiting_verification'` — unconditionally. PLAN §8.2: "Mark as
paid … changes nothing else — confirming the order is a separate tap", i.e. the designed
workflow keeps every paid manual order in `status = 'pending'` + `payment_status = 'paid'`
until the owner's second tap, which may be hours later.

In that window anyone holding the confirmation URL (it is in the email, on the screen, and in
any WhatsApp forward — 03 §9: "It never expires") posts a new screenshot and the order drops
from `paid` back to `awaiting_verification`, re-enters the review queue, and the owner
re-verifies a payment she already accepted — or, worse, a second "Duplicate TXN" proof arrives
after the parcel is packed and confuses reconciliation. The customer can also do this three
times (bucket `proof`, 3 per order) after every rejection, and the token-gated GET/POST has no
CSRF-independent replay bound because the token is the credential.

**Fix.** The re-upload form (and its POST) is only reachable when `payment_status ∈
{unpaid, failed}` AND `status = 'pending'`; a proof insert may move `payment_status` to
`awaiting_verification` only from those two states (guarded `UPDATE … WHERE payment_status IN
('unpaid','failed')`, `rowCount() === 1`). Once `paid`, the link renders the receipt only.
Also state in 03 §9 that the confirmation URL stops rendering the full address 30 days after
`delivered`/`cancelled` and shows the track view instead — the token is forwarded far more
often than the plan assumes.

---

## MEDIUM

### M-1. The CSP is unresolved and, as 02b ships it, decorative

Three policies exist. 02b §6 (the `.htaccess` printed "in full") sends `script-src 'self'
'unsafe-inline'`, `style-src … https://fonts.googleapis.com`, `font-src … fonts.gstatic.com`,
`X-Frame-Options SAMEORIGIN`, `frame-ancestors 'self'`. 07 B.3.6 sends `script-src 'self'`,
`frame-ancestors 'none'`, from PHP. PLAN S7 promises "no inline scripts". 08 C-40 patched only
`frame-src` and the GA host. Meanwhile 04b §5 (line 714) requires "one 3-line inline script
that sets `html.js`" in `<head>`, and 02b §6.1 lists "small inline bootstraps (cart count
hydration, the RAIL scroll-snap fallback)".

If 02b is followed, `'unsafe-inline'` means an escaping slip anywhere — the one HTML sink in
M-4, an admin-authored setting rendered unescaped, a review — executes, and the 02b text
admits it ("this policy does not stop an XSS that manages to inject inline script"). If 07 is
followed, the `html.js` bootstrap is blocked and 04b's no-FOUC behaviour silently breaks
(02b's own checklist line 774 warns about exactly this). If both headers are sent (htaccess +
PHP) browsers enforce the intersection, which is stricter than either author intended and
will be "fixed" by widening.

**Fix.** One policy, sent from PHP only (`.htaccess` keeps `nosniff` and framing for static
files). `script-src 'self' 'sha256-<hash of the fixed 3-line html.js snippet>'` — the snippet
is a constant, so its hash is computed once and pasted; no build step. JSON-LD blocks are not
executed and need no allowance. Cart-count hydration reads a `data-count` attribute from
`cart.js`; no other inline script. Drop the Google Fonts hosts (fonts are self-hosted, 08
C-32). Record as C-43/C-44 and add "zero CSP violations in the console on every page" to 07
B.4.9.

### M-2. Cancelling a shipped or delivered order silently inflates stock

PLAN §7.4 state table: `shipped → cancelled` and `delivered → cancelled` "yes, +qty restored
once". 05b §3.1 says `delivered` is terminal; PLAN notes the disagreement and defers it to
stage 4. Whichever way that lands, `shipped → cancelled` is legal in both and 05b §3.2 makes
"restores stock" an automatic side effect of *every* cancel.

A parcel that is with the courier or already refused at the door is not on the shelf. The
restore puts the bottles back into `product_sizes.stock`, the storefront sells them, and the
next customer's order is placed against goods that come back damaged, late, or never. That is
an oversell created by the admin panel, not by a race, and the 07 B.4.4 test ("stock cannot go
negative") does not catch it because the number never goes negative — it goes wrong.

**Fix.** Automatic restore only for `pending`, `confirmed`, `packing` (goods never left). For
`shipped`/`delivered`, cancel records `cancelled_at` + reason and leaves `stock_restored_at`
NULL; the Danger-zone "Restore stock" button (05b §6.2 already specifies it for exactly this
NULL state) becomes the explicit "parcel received back" action, still guarded by C-12. Update
PLAN §7.4 and 05b §3.2 together.

### M-3. The re-price/idempotency contract is written three ways, and the plan picks none

- 03 §8.7: the single-use `order_token` is "re-checked against the session and **deleted
  immediately**" inside the transaction; a later post shows "This order has already been
  placed" with a button to the confirmation page.
- 06b §1.6 rule 3 and §2 step 2: the key "is **not** consumed on failure" — a stock-race loser
  retries with the same key.
- 06b §1.3 step 3 checks only that the posted key "is present and 32 hex chars"; it never
  compares it to `$_SESSION['idem']`.

08 C-11 chose the `orders.idempotency_key` column but did not rule on the semantics. Built
per 03, a customer whose first attempt lost a stock race sees "already placed" for an order
that does not exist. Built per 06b literally, a stale checkout tab (rendered before order A)
submitted after the customer has filled a *new* cart hits the unique key, is 303'd to order A's
receipt, and — because the rollback path never touches the cart — walks away believing the
new items were ordered. The cart survives, nobody is charged, but the order is lost and the
customer will not come back to check.

**Fix.** Phase A: `hash_equals($_SESSION['idem'] ?? '', $_POST['idem_key'])`, else treat as
CSRF failure and re-render with a fresh key. The key is cleared only after `COMMIT` (06b
wins over 03 §8.7). On the duplicate-key replay (§2 step 4) *also* compare the existing
order's line fingerprint to the current cart; if they differ, do not redirect — re-render
checkout with a fresh key and "Your previous order {number} was already placed; this is a
new order." Add to 08 §2 as the semantics of C-11.

### M-4. The one HTML sink has no defined sanitiser

Content pages are the only place raw HTML reaches a customer's browser, and four documents
disagree on what protects it: 01a §2.6 "a fixed tag allow-list applied on save"; 03 §12.1 an
allow-list of `p, h2, h3, ul, ol, li, strong, em, a, br, blockquote`; 07 B.3.2 "stored as-is
and rendered unescaped"; 02c §3.2 defines `sanitize_html()` (`p, br, strong, em, ul, ol, li,
a[href]`) for the *product description*, which 05a §4.3 says is plain text with no HTML.
No document says how the allow-list is enforced. With no Composer, the builder will reach for
`strip_tags($html, '<p><a>…')`, which keeps every attribute — `<a href="javascript:…">`,
`<p onmouseover=…>` — and passes the test in 07 B.4.9 (which only submits `<script>` through
customer fields).

The author is the owner, so this is not customer XSS; it is persistence for anyone who gets
five minutes with the admin session (phone left on the counter, the UA-bound cookie copied
from a shared laptop). A payload in `/privacy` survives a password change, runs on every
visitor with the M-1 `'unsafe-inline'` policy, and is invisible in the admin editor.

**Fix.** Name the implementation: parse with `DOMDocument` (ships with PHP), walk the tree,
drop any element not in the list, drop every attribute except `href` on `a`, and accept `href`
only when `parse_url()` gives scheme `https`/`http`/`mailto` or a site-relative path; force
`rel="nofollow noopener"` on external links; serialise back with `saveHTML()`. Apply it on
save **and** on render (cheap, and it means a database-edited row is still safe). Make the
`<script>`/`onerror`/`javascript:` cases explicit lines in 07 B.4.9. Decide the list once
(03's, which includes `h2/h3/blockquote` the FAQ parser needs) and delete the product-
description row from 02c §3.2.

### M-5. The contact auto-reply turns the shop's SMTP into a relay

06b §5.1 #9 `contact_autoreply`: to "the sender", body includes "a copy of their message".
02c §6.3 limits it to "once per address per hour"; 03 §12.3 limits `POST /contact` to 5 per
IP per hour. The `email` field is attacker-supplied, the `message` field (10–2,000 chars) is
attacker-supplied, and the mail leaves through the owner's authenticated `smtp.hostinger.com`
account with valid SPF/DKIM — the reputation the plan spends a whole section protecting.

A script with rotating IPs and a list of victim addresses gets Sky Fragrances to deliver
arbitrary text (a phishing link, a competitor's promo) under the shop's name, one message per
victim per hour, five per IP per hour, with no ceiling per site. Hostinger suspends outbound
mail for far less, at which point order confirmations stop too.

**Fix.** Do not echo the message body; the auto-reply is a fixed acknowledgement with the
subject line and the expected reply window only. Add a site-wide cap (e.g. 30 auto-replies per
hour, then queue silently), require the honeypot and time-trap to pass before *any* mail is
sent, and never send when `contact_messages` already holds a row from that address in the last
24 hours. Or drop the auto-reply — 05a §4.7 already says the panel sends no outbound mail.

### M-6. Files the exists-guard will serve: the reset script, `.user.ini`, and whatever is left

02b §3.6: "`RewriteCond %{REQUEST_FILENAME} -f` … `RewriteRule ^ - [L]`" — any real file is
served directly. The only root-level deny is `<Files "config*.php">`. So:

- `/admin/reset-password.php.disabled` is a real file with an unknown extension; LiteSpeed
  serves it as text. Its full source — the rate-limit rule, the "refuses if more than one
  admin row" check, the exact URL — is public. When the owner renames it to `.php` for the
  recovery window, 05a §2.8 gives it **no authentication**: "opens it once, sets a new
  password". Anyone who knows the name (it is in this ZIP and in the go-live guide) and hits
  it in that window owns the shop. "It rate-limits itself to one successful use per hour" is
  a race, not a control.
- `/.user.ini` is served as text (`upload_max_filesize`, `memory_limit`, `expose_php`).
- `/install.php` until deleted, `/config.sample.php` (executes, prints nothing), `db/*.sql`
  (denied by `db/.htaccess`, one layer), `app/lib/vendor/PHPMailer/LICENSE` and any stray
  `.md`.

**Fix.** Root `.htaccess`: `<FilesMatch "(^\.|\.(disabled|sql|md|log|bak|dist|sample\.php)$)">
Require all denied` (with the 2.2 fallback). The reset script must require proof of file
access, not just a rename: it reads a nonce the owner must create as `storage/.reset-<nonce>`
(the script prints the exact filename to create), verifies the CSRF token, and deletes the
nonce file on success. Ship it as `reset-password.php.txt`? No — ship it *inside* `app/`
(denied tree) and have the owner copy it out, which is the same File Manager gesture.

### M-7. The privacy promises have no implementation behind them

PLAN S12 / 07 B.3.11: "Proofs are deleted 90 days after delivery/cancellation by an admin
tool … the privacy page states all of this." No such tool exists in 05a §1 (routes 1–37) or
05b §0.4 (routes 1–12); the only tool is `remove-sample-data`. `email_outbox.body_html`
(06b §0.2) stores the fully rendered confirmation email — name, phone, address, items,
account details — for every order; the 60-day deletion of `sent` rows is assigned to "the
cron" (06b §5.2 step 4) that Q-18 says does not exist. Sessions in `storage/sessions/` are
kept 3 days and contain carts only, fine; but `admin_activity_log.before_json/after_json`
snapshot customer PII edits (05b §10) for 24 months, and CSV exports (05a §5.3) carry full
address + phone + email with no audit row in 05a (07 B.3.11 promises one).

A bank screenshot is the most sensitive artefact this site holds — account number, balance,
sometimes CNIC on a JazzCash receipt — and as specified it is kept forever, in two places if
the customer re-uploads.

**Fix.** Add `/admin/tools/purge` (POST, CSRF): deletes proof files + `payment_proofs.file_path`
for orders `delivered`/`cancelled` > 90 days (row kept, path nulled, `purged_at` set), deletes
`email_outbox` rows `sent` > 60 days or replaces `body_html` with a stub, prunes `rate_limits`
and `admin_login_attempts`. Run the same purge on a 1-in-20 admin page load. Log one
`admin_activity_log` row per CSV export with the row count. Keep `before_json` PII out of the
log (store field names only, as 05a §4.9 already does for settings).

### M-8. Coupon codes can be enumerated: no `coupon` bucket, and the messages confirm existence

`POST /api/cart/coupon` is the one public endpoint where a guess is answered with a verdict.
03 §7.7 rate-limits it "10 coupon attempts per **session** per hour" — a session is a cookie
the attacker discards. 03 §12.3 says 10 per IP per hour, but the canonical `rate_limits`
bucket list (08 §1.2: `checkout|track|proof|contact|newsletter|review`) has no `coupon`
bucket, so the register has effectively cut the limit. 06a §3.4 returns `not_started`,
`expired`, `exhausted`, `below_min` and `no_effect` for codes that exist and `invalid` only for
ones that do not; codes are `[A-Z0-9-]` and the seed shows the house style (`EIDSALE500`,
`FIRST50`), so a dictionary of a few thousand guesses finds a scheduled launch code before it
starts, or an influencer's private code.

**Fix.** Add `coupon` to the bucket list (ip_hash, 10/hour, plus 50/24 h), and return `invalid`
for `not_started` (a code that has not started is, to the public, not a code). `expired` and
`exhausted` may stay — they describe codes the customer legitimately held.

---

## LOW

### L-1. The UNSIGNED "second wall" depends on a `sql_mode` nobody sets

08 C-03 justifies `stock INT UNSIGNED` because "strict mode on MySQL 8 and MariaDB 10.4 raises
an out-of-range error, not a clamp". 02c §2.1 lists the PDO options and 01c §4.2 adds
`SET time_zone = '+05:00'` per connection; neither sets `sql_mode`. Hostinger controls the
server default and shared MariaDB images have shipped without `STRICT_TRANS_TABLES`. Without
it, `stock - 5` on a row holding 3 stores 0 with a warning — the silent clamp C-03 says cannot
happen. The `WHERE stock >= :qty` guard is still the real defence, but the plan advertises two
walls and builds one. `DECIMAL … UNSIGNED` is also deprecated since MySQL 8.0.17 and warns on
the local MySQL 9.3 in 00-brief.md. **Fix:** `SET SESSION sql_mode =
'STRICT_ALL_TABLES,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO'` on connect, next to `time_zone`;
add "an `UPDATE` that would drive stock negative raises 1264" to 07 B.4.4.

### L-2. `storage/` has one layer for proofs and session files, not three

02b §4 Layer 1's last-resort `<FilesMatch>` lists code/text extensions only; Layer 2 (stub
`index.php`) does not block direct file URLs; Layer 3 (`SKYFR` guard) only protects PHP. A
proof is `<32hex>.jpg` and a session is extensionless `sess_<id>`; both are protected solely
by `Require all denied` being honoured. The names are unguessable, so this is defence in
depth, not an exposure — but it contradicts "three independent layers". **Fix:** in
`storage/.htaccess` use `<FilesMatch ".">` (everything) for the fallback deny, and have
`install.php` write and probe `storage/.htaccess` the way 02b §7.4 does for `uploads/`.

### L-3. Editing a customer's phone on an order does not say what happens to `phone_normalized`

05b §10 lets the admin edit `customer_phone` until `shipped` via route 7 (`/notes` — "Internal
notes / contact correction"). `phone_normalized` (track lookup, per-phone coupon ledger,
repeat-customer badge) and the WhatsApp `href` (built from `customer_phone` by its own rule in
05b §7.1) are not mentioned. Built literally, the owner fixes a typo and the customer can no
longer track the order, while the coupon ledger still charges the old number. **Fix:** the
handler recomputes `phone_normalized` with the shared `phone_normalize()` and logs both.
Also 05b §1.4 searches `customer_phone LIKE …` assuming a stored `03…` form — search
`phone_normalized` instead. And `orders.tracking_url` (08 Q-09) needs the 05a §4.9 `url`
rule (`https://` only) before it becomes a link on the track page and in emails.

### L-4. The admin panel should never write a `.php` file

02c §8.2: maintenance mode is toggled "from Admin → Settings (which rewrites `config.php`
through the same temp+rename as the installer)". 05a §4.9 has it as a `settings` key. Two
truths, and one of them gives the admin session a write path into a file that is `require`d
on every request — the one capability an attacker with a stolen admin cookie could not
otherwise get. `var_export` escapes correctly, so this is not injectable as specified, but it
is a standing invitation to the next feature that "just needs one more config value".
**Fix:** rule for the DB key (05a wins), `config.php` `0400` after install (02b §7.2 says
`0600`), and the "edit the file in File Manager" path stays for the case the panel is down.

### L-5. Small auth-flow gaps a builder will resolve wrongly

- 05a §2.5: the CSRF token is "created on login". The login form itself (§2.1, §2.4 step 1)
  needs one before login; state that `/admin/bootstrap.php` ensures a pre-auth token in the
  `SFADMIN` session and rotates it on login.
- 02c §8.2 rule 2: the maintenance bypass key travels in `?bypass=`, so it lands in access
  logs, browser history and the `Referer` of the WhatsApp button. Accept it via POST form on
  the maintenance page instead, then set the cookie.
- 03 §9: a wrong token renders the track form "pre-filled with the order number"; behaviour
  for a *non-existent* number is unspecified and `GET /order/{n}` has no rate limit. Both
  cases must render the identical page (prefill regardless), and the GET should count in the
  `track` bucket, or the confirmation route becomes the existence oracle 06b §6.2 closes on
  `/track`.

---

## Summary table

| ID | Sev | One line | Where |
|---|---|---|---|
| B-1 | BLOCKER | 06b Phase B re-implements pricing: post-discount shipping, paisa rounding, no per-phone, `NOW()`, silent favourable change | 06b §1.4–1.5, §7.2, §7.5; 01b §1.1 |
| B-2 | BLOCKER | Counter vs ledger authority unresolved; seed gives `FIRST50` 50 free uses under 01b's rule | 01b §6, 06b §1.5, 07 A.3, PLAN §4.2 vs §7.2 |
| B-3 | BLOCKER | Username lock = anyone can lock the owner out indefinitely; reset script does not clear it | 05a §2.6–2.8 |
| H-1 | HIGH | Client IP undefined behind the Hostinger proxy — every rate limit collapses or is spoofable | 02b §2.3, 05a §2.6, 06b §1.3/§6.3 |
| H-2 | HIGH | Proofs written pre-transaction, sweep assigned to a cron that does not exist — disk-fill DoS | 06b §1.6 rule 1, §5.2 step 4, 08 Q-18 |
| H-3 | HIGH | Unpaid transfer orders hold stock indefinitely; caps only flag; no bulk cancel | 03 §8.5, 06b §7.1, 05b §1.7, 08 Q-06/Q-07 |
| H-4 | HIGH | Confirmation-link re-upload resets a `paid` order to `awaiting_verification` | 06b §4.2 step 6, PLAN §8.2 |
| M-1 | MEDIUM | CSP unresolved; 02b's `'unsafe-inline'` is decorative; 07's blocks 04b's inline `html.js` | 02b §6, 07 B.3.6, 04b Part 5 |
| M-2 | MEDIUM | Cancel after shipped/delivered auto-restores stock for goods not on the shelf | PLAN §7.4, 05b §3.2 |
| M-3 | MEDIUM | Idempotency semantics written three ways; stale tab loses a new order silently | 03 §8.7, 06b §1.3, §1.6, §2 |
| M-4 | MEDIUM | Content-page HTML sanitiser undefined; `strip_tags` keeps `href="javascript:"` | 01a §2.6, 03 §12.1, 07 B.3.2, 02c §3.2 |
| M-5 | MEDIUM | Contact auto-reply echoes attacker text to attacker-chosen address via the shop's SMTP | 06b §5.1 #9, 02c §6.3, 03 §12.3 |
| M-6 | MEDIUM | Exists-guard serves `reset-password.php.disabled`, `.user.ini`; reset script unauthenticated when enabled | 02b §3.6, 05a §2.8 |
| M-7 | MEDIUM | 90-day proof purge tool and outbox PII cleanup do not exist in any route table | 07 B.3.11, PLAN S12, 06b §0.2/§5.2 |
| M-8 | MEDIUM | No `coupon` rate-limit bucket; verdict messages confirm code existence | 08 §1.2, 03 §7.7, 06a §3.4 |
| L-1 | LOW | UNSIGNED second wall needs `sql_mode` the connection never sets | 08 C-03, 02c §2.1 |
| L-2 | LOW | `storage/` proofs and sessions have one deny layer, not three | 02b §4 |
| L-3 | LOW | Admin phone edit must recompute `phone_normalized`; `tracking_url` scheme check | 05b §10, §1.4, §7.1 |
| L-4 | LOW | Admin panel writes `config.php` for maintenance mode | 02c §8.2 vs 05a §4.9 |
| L-5 | LOW | Pre-auth CSRF token, `?bypass=` in URL, `/order/{n}` existence oracle | 05a §2.5, 02c §8.2, 03 §9 |

Verdict: the money core (guarded decrement, snapshots, single rounding step, token-gated
receipt, proof re-encode) is sound where 06a and 01b own it. The losses come from the
seams the register left open — 06b's own copy of the pipeline, the counter/ledger split, and
the operational controls (IP source, lockout, disk, retention) that no document owns. B-1,
B-2 and B-3 need rulings in 08 §2 before stage 3 is written; H-1 and H-2 before stage 3 is
tested; the rest can land in stage 4.
