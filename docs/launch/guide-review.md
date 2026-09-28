# Go-Live Guide — owner's-eyes review (pass 2)

> **Status 2026-09-29 (pass 3).** All sixteen gaps below and the day-one checklist were applied
> to `docs/GO-LIVE-GUIDE.md` in the hand-over-gap run; the stray ZIP and the `update-pack` /
> `admin-ui-fix` folders were moved to `dist/old-builds`; the build was rebuilt as
> `skyfragrances-20260928-1906.zip` (14,062,456 bytes, 565 files) because `app/tools/
> reset-password.php` (C-64) was added. Byte counts and the `1816` name below describe the build
> this pass reviewed.

> **Status 2026-09-28, second pass.** The first pass (22 gaps) was applied to
> `docs/GO-LIVE-GUIDE.md` and, where it touched code, to `site/install.php` and
> `site/app/lib/text.php`. This pass re-reads the guide as it now stands against the hand-over
> build `dist/skyfragrances-20260928-1816.zip` (14,058,305 bytes, 564 files), the unpacked twin
> `dist/public_html/`, the real inputs and checks in `site/install.php`, and the admin panel's
> actual button and field labels in `site/admin/`. No code was changed; nothing was committed.

Read as the shop owner: runs a business, bought the domain in hPanel, has never put a website
online, and will add the perfumes himself tonight from the laptop with the phone next to him.

**Verdict.** He can finish tonight. Every step says where to click, what to type and what he
should see, the installer catches the dangerous mistakes itself, and the order (SSL → PHP →
database → mailbox → upload → installer → admin) is right and must stay that way. What remains
is sixteen smaller gaps: one stray ZIP that could be uploaded by mistake, one failure mode the
troubleshooting table does not cover, six places where the guide's wording is not what the
screen says, and a handful of unexplained terms. None needs a code change. Each gap below gives
the exact sentence to add or change.

Legend: **RISK** = a likely wrong result or a wasted hour · **CLARITY** = he will hesitate or
click the wrong thing · **NICE** = polish.

---

## A. Gaps, ranked

### 1. RISK — A second ZIP sits next to the right one and is not named

`dist/` now holds `skyfragrances-20260928-1816.zip` (the build) **and**
`skyfragrances-update-2026-09-28.zip` (11,727 bytes: `admin/views/layout.php`,
`assets/css/admin.css`, `assets/js/reveal.js`), plus the folders `update-pack/` and
`admin-ui-fix/`. The guide says "ignore any other ZIP or folder" but never names this one, and
Part 11 tells him that "a new ZIP from the developer" is how updates arrive — so an owner who
sees the newer-sounding word *update* may upload it either instead of, or on top of, the build.
Extracting an 11 KB ZIP into an empty `public_html` gives a site with three files and no
`install.php`; extracting it on top of the build is harmless but pointless, because all three
files are already byte-identical inside the 1816 ZIP (checked with `cmp`).

**Change the ZIP bullet in "Have these ready" — after "Ignore anything in `dist/old-builds`
and any other ZIP or folder." add:**

> In particular do **not** upload `skyfragrances-update-2026-09-28.zip` or the folders
> `update-pack` and `admin-ui-fix`: everything in them is already inside the 1816 ZIP. They are
> developer leftovers, not an update for you.

(For the developer: move those three into `dist/old-builds/` before the hand-over so the
sentence is not needed.)

### 2. RISK — On the wrong PHP version the installer shows a blank page, and the troubleshooting table does not say so

`install.php` uses PHP 8.1+ syntax (`: never` return types, `match`, `str_starts_with`). If the
owner skips Part 1 Step 2 and Hostinger's account is still on PHP 7.4 or 8.0, PHP cannot parse
the file: he sees a **blank white page** or a plain **"HTTP ERROR 500"**, never the dark
installer, and never the friendly "PHP version … needs 8.2" row that Screen 1 would have shown.
Part 10's "During installation" table has rows for the 404 and for "coming soon", but not for
this, so he will hunt for a nested folder that is not there.

**Add a row to Part 10 → "During installation", straight after the first row:**

> | A completely blank white page, or "HTTP ERROR 500" / "This page isn't working", when opening `install.php` | The account is still on an old PHP version, so the installer cannot even start | Part 1, Step 2: Advanced → PHP Configuration → 8.2 → Update. Wait a minute and reload. |

### 3. CLARITY — "Search engines may index this site" is not what the switch says

The Advanced tab's switch is labelled **Let search engines index the shop** (its help text: *Keep
off on a preview copy. Turn on once the shop is live at its real address.*). The guide uses
"Search engines may index this site" seven times (Part 7 Soft launch point 3, Part 7 Step 4
Advanced bullet, Part 9, the day-one checklist three times, and Screen 4's report bullet says
"search-engine setting"). He will look for a switch that does not exist.

**Replace every "Search engines may index this site" with "Let search engines index the
shop"**, and in Part 7 Step 4's Advanced bullet write:

> **Let search engines index the shop** (must be **on** for the live shop; the installer
> switches it on automatically when the address is skyfragrances.com — switch it off only for
> the soft launch and back on when the real perfumes are in),

### 4. CLARITY — Products are "Live" or "Hidden", not "Active"

The product form's **Status** field offers **Live** / **Hidden**; the list's per-row action reads
**Hide** or **Make live**; the bulk actions are **Hide** and **Make live**; the edit page's
toggle reads **Hide from shop** / **Make live**. The guide says "Active" in Part 7 Step 5 point 6
("Status (Active or Hidden)"), Step 5's tip ("quick toggles for Active/Hidden"), Step 6 ("at
least four active ones"), and twice in the checklist ("4 real perfumes Active").

**Change those five places to:**

> - Step 5 point 6: *Status* (**Live** or **Hidden**)
> - Step 5 tip: the list at **Products** has a **Hide** / **Make live** action on every row, plus
>   **Duplicate** for a variant that shares most details.
> - Step 6: When your own perfumes are in (at least four with status **Live**):
> - Checklist, both lines: at least 4 real perfumes **Live** …

### 5. RISK — Each Settings tab is its own page; switching tabs throws away unsaved edits

The Settings page header says *"Each tab saves on its own"* and the button is **Save Payments**,
**Save Store**, and so on, with the note *Saves this tab only* — the guide has the button name
right. What it does not say is that the tabs are separate pages: if he fills in the bank details,
then clicks **Contact & Social** to do the WhatsApp number "while he is at it", the bank details
are gone and no warning appears. On day one he will do exactly this.

**Add to the start of Part 7 Step 4 (before the tab list):**

> Each tab is a separate page with its own **Save …** button at the bottom. Press it **before**
> you click another tab — moving to another tab without saving throws away what you typed, with
> no warning.

### 6. CLARITY — Google verification: what to paste, and where the code comes from

The SEO tab's field is **Google site verification code** (plain text, 120 characters). The shop
prints it inside `<meta name="google-site-verification" content="…">` with no clean-up, so if he
pastes the whole `<meta …>` tag that Search Console shows, the tag is broken and Google will
never verify. The guide also names "Google Search Console" without saying what it is or where.

**Replace the Google bullet in Part 9 with:**

> - **Google.** Google Search Console is Google's free tool for telling it about your site
>   (search.google.com/search-console; you need any Google account). Press **Add property**,
>   choose **URL prefix**, type `https://skyfragrances.com`, then under *Other verification
>   methods* pick **HTML tag**. It shows a line like
>   `<meta name="google-site-verification" content="AbC123…" />` — copy **only the letters
>   between the quotes after `content=`**, not the whole line. In the panel open **Settings →
>   SEO**, paste it into **Google site verification code**, press **Save SEO**, then go back to
>   Search Console and press **Verify**. Finally open **Sitemaps** in Search Console and submit
>   `https://skyfragrances.com/sitemap.xml`. The sitemap updates itself.

### 7. CLARITY — The installer empties every password box whenever a screen comes back

`install.php` never re-fills a password field. Three moments where he will type into a form that
looks complete and get "Please fill in…" or a failed connection:

- Screen 2 comes back with a red *Could not connect* or *install key did not match* message —
  the **Database password** box is empty again.
- Screen 3 comes back after **Send test email to the address above** (success or failure) —
  both **Admin password** boxes are empty again.
- Screen 3 comes back with any red message (username, email, password too short) — same.

**Add to Screen 2, after "If you see a red message:" list:**

> Whenever Screen 2 comes back with a red message, the **Database password** box is empty
> again — type it in once more before pressing the button.

**Add to Screen 3, right after the "Send test email" paragraph:**

> After the test (and after any red message on this screen) both password boxes are empty
> again. Type the admin password twice more before pressing **Create the shop**; everything
> else you typed is still there.

### 8. NICE — "Send test email" needs "Your email address" filled in first

Pressing the test button with an empty email box returns *Enter your email address first, then
press "Send test email"* — harmless, but the guide says "Press it first".

**Change "Press it first: a green box confirms it was sent." to:**

> Fill in **Your email address** first, then press it: a green box confirms it was sent.

### 9. CLARITY — The `.install-key` file does not exist until Screen 2 has been opened

`inst_install_key()` writes `storage/.install-key` the first time Screen 2 is rendered. An owner
who prepares by opening `storage` in File Manager *before* pressing **Continue** on Screen 1 sees
no such file, and File Manager does not refresh itself when the file appears.

**Add to Screen 2 point 1, after "In the File Manager tab, open `public_html → storage`…":**

> The file is created the moment this screen opens, so if the `storage` folder was already open
> in File Manager and shows no `.install-key`, press File Manager's **refresh** (circular arrow)
> icon — or open a different folder and come back — and it appears.

### 10. CLARITY — The cron command needs `/home/uXXXXXXXXX`, which File Manager never shows

Part 8's cron recipe says to replace `/home/uXXXXXXXXX/…/public_html` with "the exact path shown
at the top of File Manager". File Manager's address bar shows `/public_html` or
`/domains/skyfragrances.com/public_html` — never the `/home/u…` part. The `u…` number is the same
prefix hPanel put in front of the database name in Part 2 (the hosting username). Also, Hostinger's
cron form has a **Type** menu whose *PHP* option runs the file with the site's PHP version; the
*Custom* type may use an older command-line PHP.

**Replace the cron paragraph in Part 8 "Check the emails" with:**

> If you want the emails to go out even when nobody has the panel open (recommended once real
> orders arrive): left menu → **Advanced → Cron Jobs**. If the **Type** menu offers **PHP**,
> choose it and enter the file path; otherwise choose **Custom** and enter the full command.
> Interval: **every 15 minutes** (`*/15 * * * *`). The path is
> `/home/u123456789/domains/skyfragrances.com/public_html/cron.php`, where `u123456789` is
> the same `u…` number that hPanel put in front of your database name in Part 2 (it is also
> shown as *Username* under **Hosting → Plan details**). For the Custom type the command is
> `php` followed by a space and that path. Press **Save**. No key is needed when it runs this
> way; if the job is ever set up as a web address instead, it needs `?key=` with the `cron_key`
> from `config.php`. If Hostinger emails you "PHP Parse error" from the job, switch the Type to
> **PHP**.

### 11. CLARITY — What if the padlock never comes, and the `www.` address is never tested

Part 1 Step 1 says "wait" for the certificate but gives no limit and no fallback. Hostinger's free
certificate normally lands within 15 minutes; when it does not, the owner has one lever — support
chat — and the guide should say so rather than leave him refreshing. The certificate also covers
`www.skyfragrances.com`, and the shop's `.htaccess` sends `www.` visitors to the bare domain;
nobody is told to check that once.

**Add to the end of Part 1 Step 1 (after "the padlock on the placeholder page is your signal
to continue"):**

> If two hours pass and **Security → SSL** still does not say *Active*, open hPanel's **Help**
> (bottom-left) → live chat and write: "Please install the free SSL certificate on
> skyfragrances.com." They do it while you wait. Do not buy a certificate.

**Add to Part 6 Step 1, after point 3:**

> 4. Once, on your phone, open `https://www.skyfragrances.com`. It must land on
>    `https://skyfragrances.com` with the padlock — the shop removes the `www.` itself.

### 12. RISK — hPanel's Performance switches (CDN, cache) are not on the "do not touch" list

Newer Hostinger plans show a **Performance** entry in the website dashboard with a **CDN** toggle
and, on some plans, an **Object cache** / **Cache manager** switch, sometimes already on. With the
CDN on, every visitor reaches the shop through Hostinger's edge network: the installer's *Client IP
detection* row turns into a Note, only one edge address gets recorded as a trusted proxy, and the
login lock-out and rate limits can start counting all customers as one person. A page cache would
also serve one customer's cart or checkout page to another. Part 1 Step 3 only forbids WordPress
plugins, which do not apply here; the owner has no reason to think a switch called "CDN" is
dangerous.

**Add a third bullet to Part 1 Step 3:**

> - **Performance → CDN** (and any **cache** switch on that page, if your plan shows one) must
>   be **off** for now. The shop serves its own pages and photos quickly enough; Hostinger's CDN
>   can mix customers' carts up and confuses the shop's fraud protection. A developer can enable
>   it later with the right settings.

**And add to the "Never do this" table in Quick reference:**

> | Switch on **Performance → CDN** or a cache in hPanel | Customers can see each other's cart; the shop's own caching is already on. |

### 13. CLARITY — Five terms are used before they are explained

| Term | Where | Sentence to add (in place, in brackets) |
|---|---|---|
| **nameservers** | Part 1 Step 1 point 1 | "…follow the prompt to use Hostinger's nameservers (the internet's address book entry that says where your site lives). Because you bought the domain at Hostinger, it already uses them and you should not see this prompt." |
| **HSTS** | Part 6 Step 1, last paragraph | "…add the HSTS line to `.htaccess` (a header that makes browsers remember to use https:// for your shop for a year, so they never even try http://)" |
| **SKU** | Part 7 Step 5, Sizes | "*SKU* (your own stock code for that bottle, for example `AZ-50`; suggested for you; keep it unique)" |
| **STARTTLS / TLS / SSL** | Part 3 point 3 table | "`587` (STARTTLS / TLS — the two names for the same secure sending mode; you never have to choose one)" |
| **phpMyAdmin** | Part 2, last line | "Do **not** open phpMyAdmin (the tool that shows the raw database tables) and do not create any tables." |

### 14. NICE — Two Payments-tab labels differ slightly from the screen

In Settings → Payments the fields are **Note shown for transfer payments** and **COD note at
checkout**; the guide's table says "Note shown for manual payments" and "Cash on Delivery:
enabled, note, COD limit".

**Change those two rows of the Part 7 Step 3 table to:**

> | Note shown for transfer payments | The sentence customers see under the account details ("Send your payment screenshot and transaction ID…"). Keep or adjust. |
> | Cash on Delivery: enabled, **COD note at checkout**, **COD limit** | `0` means no limit. Set a limit (for example 15000) if you do not want COD on very large orders — above it customers must pay in advance. |

### 15. NICE — Screen 1 on PHP 8.4 shows a Note the guide does not mention

If Hostinger's default is PHP 8.4, Screen 1 shows a **Note**: *tested on PHP 8.2 and 8.3. If
anything misbehaves, choose PHP 8.3*. It does not block, but an owner who did Part 1 Step 2 will
wonder why.

**Add to the Screen 1 "Note rows" bullet:**

> A Note saying the PHP version is *tested on 8.2 and 8.3* means Part 1 Step 2 was skipped or
> Hostinger put you on 8.4; go back and choose 8.3, then reload.

### 16. NICE — Screen 4 says `/admin/login`, the guide says `/admin`

The installer's green panel and Next steps link to `https://skyfragrances.com/admin/login`; the
guide's Part 6 and Part 7 say `/admin`. Both open the same login page (`/admin` redirects to
`/admin/login` while logged out), but a first-timer compares strings.

**Add to Part 7 Step 1 point 1:** "(the installer's link to `/admin/login` is the same page)".

---

## B. Order of operations — checked again, unchanged

**SSL first, then PHP version, database and mailbox, then upload, then the installer.** The
reasons in the shipped files are the same as in pass 1 and still hold in the 1816 build:

- `public_html/.htaccess` redirects every `http://` request to `https://` (302) before anything
  else; without an active certificate the installer cannot be reached at all.
- `.user.ini` sets `session.cookie_secure = 1`, so nothing would stay logged in over `http://`.
- `inst_https_permanent()` probes `https://skyfragrances.com/robots.txt` with certificate
  verification during **Create the shop**; with a live certificate it flips the redirect to 301
  on the spot and Part 6 Step 1 becomes a one-glance confirmation.
- The database and mailbox come before upload only because the installer asks for them on
  Screen 2 and 3; nothing breaks if he creates them while the ZIP uploads.

Common mistakes and what the guide already does with them: nested `public_html` (Part 4 Step 5 and
Part 10, correct — the installer cannot report it because it is unreachable); ZIP made with Finder
(forbidden in "Have these ready"); Force HTTPS left on (Part 1 Step 3, checked again after Part 4);
`admin` as username (box is empty, guide says so); personal Gmail as the public email (Screen 3
row); skipping email with a half-filled SMTP block (installer clears the host when address and
password are both empty; guide says leave both empty); reload of Screen 4 (screenshot warning on
the page and in the guide); a second `install.php` run (refused, delete button offered).

---

## C. Things he cannot do at all tonight

| Item | Why | What the guide should say (already says, unless noted) |
|---|---|---|
| HSTS header | One `.htaccess` line; he is rightly told never to edit that file. | Part 6: ask the developer after a month — present; add the bracketed explanation from gap 13. |
| Real hPanel screenshots | None exist (Handover §9.4); labels are hedged. | Nothing to add; the "closest match" sentence under "How hPanel is laid out" covers it. |
| Anything on Screen 4 after it is gone | Installer self-deletes on the real domain. | Screenshot instruction — present. |
| Trimmed update ZIPs | Handover §5.12; updates re-ship `install.php`, `.htaccess`, `robots.txt`. | Part 11 points 4–5 — present. Gap 1 keeps tonight's stray update ZIP out of his hands. |
| Enabling Hostinger's CDN safely | Needs `trusted_proxies` set to the CDN's ranges, not one address. | Gap 12: keep it off; developer job. |
| Recovering a lost admin password without phpMyAdmin | No reset email by design (Handover §5.1). | Part 10 procedure — present and re-verified (`password_verify` returns true for the printed hash). |

---

## D. Checked and found correct (no change)

- ZIP root is flat: `.htaccess`, `.user.ini`, `admin/`, `app/`, `assets/`, `config.sample.php`,
  `cron.php`, `db/`, `favicon.ico`, `index.php`, `install.php`, `robots.txt`, `storage/`,
  `uploads/` — exactly Part 4 Step 5's list. No `config.php`, `.install-key`, `__MACOSX` or
  `.DS_Store` inside. `storage/` and `uploads/` each carry their `.htaccess`.
- `dist/htaccess-upload/htaccess.txt` and `user.ini` are byte-identical to the two hidden files,
  so the "uploaded the folder instead" rescue in Part 4 works.
- `dist/public_html/install.php` is identical to `site/install.php`; the guide's Screen 1–4
  descriptions match its rows, messages and buttons word for word (Problem table, key-mismatch
  text, *Could not connect*, server-version text, CREATE-privilege text, *Create config.php by
  hand*, replace-partial tick box, green/red Screen 4 panels, *Delete install.php for me*,
  *Check whether it is gone*).
- Skipping email: `inst_handle_db_save` blanks the host only when **both** mailbox address and
  password are empty — the guide's instruction matches. Port 465 → `ssl`, otherwise `tls`.
- WhatsApp normalisation accepts `0300…`, `+92 300…` and `0092 300…` as the guide says.
- `site_indexable` is set to 1 only for `skyfragrances.com` / `www.skyfragrances.com` at the
  root — the guide's "switched on automatically" is right; `sitemap.xml` is 404 while it is off.
- Admin labels used by the guide exist as written: Make HTTPS permanent, Temporary (302) /
  Permanent (301), Review and remove, Still present, Will be kept, Regenerate all images,
  Emails not yet sent, Cancel unpaid / Review and cancel them, Parcel received back, Stock-back
  audit, Save & mark shipped, Cancel this order — permanent, Mark as paid, Request payment
  proof, Payment not verified, Choose photos, Save & add another, Change password, Duplicate,
  Scent families, Add collection, New coupon, Featured on the home page, Show the NEW badge,
  Low-stock alert at, COD limit, Hours to hold an unpaid transfer order, Save Payments.
- Settings tabs are Store, Contact & Social, Home, Shipping, Payments, SEO, Advanced.
- Photo picker accepts `image/jpeg,image/png,image/webp`; limit 6 MB (6,291,456 bytes).
- Home page product rows appear at `>= 4` live products; the collections strip falls back to
  all active collections when fewer than three are marked for the home page.
- Dashboard banners: sample data, maintenance mode, `install.php` still present, and the
  origin-mismatch note — all four described.
- Lock-out message is *Too many failed attempts. Try again in …*; `/track` asks for
  `order_number` and `phone`; order numbers are `SF-yymmdd-XXXX`; `WELCOME10` is 10 % above
  Rs. 3,000; maintenance fallback file is `storage/MAINTENANCE`; `cron.php` needs no key from
  the command line and 404s on the web without `?key=`.
- Password-reset hash verifies against `Reset-Sky-2026-Temp!`.

---

## E. Printable day-one checklist (updated for this pass)

Print this page. Tick each line. Times are for a first-timer. Wording below matches the screens.

**Before you start (5 min)**
- [ ] Hostinger login ready; notes app open.
- [ ] The ONE file to upload: `skyfragrances-20260928-1816.zip` (14,058,305 bytes). Not the folder, not `skyfragrances-update-2026-09-28.zip`, not anything in `old-builds`, `update-pack` or `admin-ui-fix`, not a ZIP you compressed yourself. Not unzipped on the computer.
- [ ] Bank, JazzCash, Easypaisa details; WhatsApp number as `0300 1234567`; logo PNG; perfume photos as JPG (not HEIC), portrait.

**Part 1 — hPanel (10 min)**
- [ ] Websites → `skyfragrances.com` listed with **Manage**. If not: Add website → Empty website / upload my own files → existing domain. No WordPress, no Builder.
- [ ] `https://skyfragrances.com` shows the Hostinger placeholder **with a padlock** (Security → SSL = Active). If still not Active after 2 hours: Help → live chat, ask for the free SSL. Do not continue without the padlock.
- [ ] Security → SSL → **Force HTTPS is OFF** (turned off if it was on).
- [ ] Performance → **CDN off** (and any cache switch off) if the plan shows that page.
- [ ] Advanced → PHP Configuration → **8.2** (or 8.3) → Update. Nothing else changed.

**Part 2 — Database (5 min)**
- [ ] Databases → Management → name `skyfrag`, user `skyadmin`, generated password → Create.
- [ ] Written down: full `u…_skyfrag`, full `u…_skyadmin`, password. (The `u…` number is also the hosting username you need for the cron job later.)
- [ ] phpMyAdmin NOT opened.

**Part 3 — Mailbox (5 min, optional tonight)**
- [ ] Emails → (free included plan if asked) → `orders@skyfragrances.com` created, password noted, webmail login tested. SMTP `smtp.hostinger.com`, port `587`.
- [ ] OR skipping email tonight → leave Mailbox address AND Mailbox password EMPTY on Screen 2.

**Part 4 — Upload (10 min + 5 min wait)**
- [ ] File Manager inside `/public_html` (via `domains/skyfragrances.com` if needed). Hidden files ON.
- [ ] Deleted `default.php` and everything else there.
- [ ] Uploaded the ZIP → Extract → destination exactly `/public_html` → deleted the ZIP and any `__MACOSX`.
- [ ] `public_html` shows `.htaccess`, `.user.ini`, `admin/ app/ assets/ db/ storage/ uploads/`, `index.php`, `install.php`, `robots.txt`, `cron.php`, `config.sample.php`, `favicon.ico` — no inner `public_html`.
- [ ] Force HTTPS in hPanel still OFF. Waited 5 minutes.

**Part 5 — Installer (10 min)**
- [ ] `https://skyfragrances.com/install.php` shows the dark installer. (Blank page / HTTP 500 = PHP version, Part 1 Step 2. 404 / coming soon = nested folder or `default.php`, Part 4.)
- [ ] Screen 1: no **Problem** rows. "Pretty web addresses — could not be checked" is normal. A PHP "tested on 8.2 and 8.3" Note = choose 8.3 in hPanel. Continue.
- [ ] Screen 2: pressed Continue FIRST, then refreshed File Manager → `storage/.install-key` → pasted the 32 characters; DB name / user / password; Shop address `https://skyfragrances.com`; SMTP filled (587) OR mailbox address and password empty → Test connection and continue. (Red message? Retype the DB password before trying again.)
- [ ] Screen 3: my own username (not `admin`); business email (not personal Gmail); 12+ character password in a password manager; WhatsApp `03…`; sample data ticked. If SMTP set: email box filled → Send test email → green box → retype the password twice → Create the shop.
- [ ] Screen 4: SCREENSHOTS of the whole page; green "deleted itself" panel (or red → Delete install.php for me); opened `/config.php`, `/db/schema.sql`, `/storage/.htaccess`, `/app/bootstrap.php` on the phone — each an error page, never code; `/install.php` now shows the shop's page-not-found.

**Part 6 — HTTPS and email (3 min)**
- [ ] Admin → Settings → Advanced → HTTPS redirect = **Permanent (301)**, or pressed **Make HTTPS permanent**.
- [ ] `https://www.skyfragrances.com` lands on `https://skyfragrances.com` with the padlock.
- [ ] hPanel → Emails shows no "DNS records missing" (or pressed Fix).

**Soft launch — before sharing the link (10 min). Press Save on each Settings tab BEFORE clicking the next tab.**
- [ ] Settings → Payments: real bank / JazzCash / Easypaisa details, OR those three switched off with only COD on — no REPLACE ME visible → **Save Payments**.
- [ ] Products → tick all → **Hide** (or accept sample orders).
- [ ] Settings → Advanced → **Let search engines index the shop: OFF** until real perfumes exist → Save Advanced.
- [ ] Settings → Home: announcement bar changed → Save Home.
- [ ] Settings → Contact & Social: WhatsApp, phone, Instagram, Facebook checked → Save Contact & Social.
- [ ] Shop opened on the phone: loads, padlock, WhatsApp button opens WhatsApp.

**Adding perfumes (as long as it takes)**
- [ ] Collections first.
- [ ] At least 4 real perfumes with status **Live**, each with JPG/PNG/WEBP photos under 6 MB, portrait.
- [ ] Tools → Remove sample data → Review and remove → typed DELETE.
- [ ] Settings → Advanced → **Let search engines index the shop: ON** → Save Advanced.
- [ ] One COD + one transfer test order moved through the statuses, then cancelled.
- [ ] Search Console: URL prefix property → HTML tag → only the code between the quotes pasted into Settings → SEO → Google site verification code → Save SEO → Verify → sitemap `https://skyfragrances.com/sitemap.xml` submitted.
- [ ] Optional: Advanced → Cron Jobs → every 15 minutes → `/home/u…/domains/skyfragrances.com/public_html/cron.php` (Type PHP if offered).

**Never**
- [ ] Never switch Force HTTPS or the CDN on in hPanel. Never edit `.htaccess`, `.user.ini`, `app/`, `admin/`, `db/`. Never delete `storage/` or `uploads/`. Never share `config.php` or the install key. Never leave `install.php` on the server.
