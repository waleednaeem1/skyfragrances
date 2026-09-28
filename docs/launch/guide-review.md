# Go-Live Guide — owner's-eyes review

Reviewed 2026-09-28 against `docs/GO-LIVE-GUIDE.md`, `docs/HANDOVER.md`, the real inputs and
checks in `site/install.php`, and the unpacked build in `dist/public_html/`. Read as the shop
owner: runs a business, bought the domain in hPanel, has never deployed a website, will add the
perfumes himself tonight, and — per his own message — wants the site publicly visible *now* and
will finish it while it is live.

Verdict: the guide is unusually complete for a non-developer and the installer catches most
mistakes itself. Twenty-two gaps below, ranked; the first six would actually stop or embarrass
the owner tonight. No code was changed. Each gap gives the exact text to add or change.

Legend: **BLOCKER** = he cannot finish tonight without it · **RISK** = likely wrong result or
embarrassment · **CLARITY** = he will hesitate or click the wrong thing · **NICE** = polish.

---

## A. Gaps that stop or embarrass him tonight

### 1. BLOCKER — He is holding a *folder*, the guide only knows a *ZIP*

He asked for "the complete public_html folder so that I can upload it". The guide (Part 4) is
written for `skyfragrances-….zip` and says "Do not unzip it on your computer". `dist/` currently
holds four ZIPs (`…-0802`, `…-0906`, `…-0917`, `skyfragrances-public_html.zip`) and two unpacked
folders (`public_html/` and an older `public_html-READY-TO-UPLOAD/` whose `robots.txt`,
`login.php` and `tools.php` are stale). He will pick one at random.

What goes wrong with a folder on a Mac:

- Finder hides `.htaccess` and `.user.ini`. If he drags the folder's *contents* into File
  Manager, the two hidden files are left behind — the site then shows Hostinger's own 404 on
  `/shop` and the installer's Screen 1 reports *Protection file .htaccess missing*.
- If he right-clicks the folder → **Compress**, macOS makes `public_html.zip` whose root is a
  `public_html/` folder, plus a `__MACOSX/` folder and `.DS_Store` files. Extracting it in
  hPanel produces exactly the "nested public_html" mistake the guide warns about.
- Uploading 564 loose files through a browser takes 10–20 minutes and any dropped connection
  leaves a half-site with no warning.

`dist/htaccess-upload/htaccess.txt` and `user.ini` already exist and are byte-identical to the
two hidden files — the workaround was prepared but never mentioned in the guide.

**Add to "Have these ready" (replace the ZIP bullet):**

> - **Upload the ZIP, not the folder.** The developer's folder `dist/public_html` and the file
>   `dist/skyfragrances-20260928-0917.zip` contain exactly the same 564 files; the ZIP is the
>   one to upload because hPanel unpacks it on the server in seconds and keeps the two hidden
>   files (`.htaccess`, `.user.ini`) that your Mac hides from you. Use **only** the newest ZIP,
>   `skyfragrances-20260928-0917.zip` (14,057,392 bytes). Ignore `public_html-READY-TO-UPLOAD`
>   and the older ZIPs. Do **not** make your own ZIP with Finder → Compress: it wraps everything
>   in an extra folder and adds `__MACOSX`.

**Add to Part 4 after Step 5, a new box "If you uploaded the folder instead of the ZIP":**

> Open `public_html` in File Manager with hidden files showing. If `.htaccess` and `.user.ini`
> are missing: upload the two files `htaccess.txt` and `user.ini` from the developer's
> `dist/htaccess-upload` folder into `public_html`, then right-click each → **Rename** to
> `.htaccess` and `.user.ini` (with the leading dot). The installer's Screen 1 will confirm
> "Protection file .htaccess — present". Every other missing file means the upload was
> incomplete — delete everything and use the ZIP.

### 2. BLOCKER — Nothing tells him to attach the domain to the hosting plan or to skip Hostinger's setup wizard

A domain "bought in hPanel" is not automatically a website. On a fresh plan hPanel shows an
onboarding wizard (*"Let's set up your website"* → WordPress / Website Builder / Migrate /
Empty). Part 1 Step 3 says "do not install WordPress" but does not say what **to** choose, and
Part 1 Step 1 assumes `skyfragrances.com` is already listed under **Websites**. If it is not,
he has nowhere to click **Manage** and Part 1 fails at line one.

**Add as Step 0 in Part 1:**

> ### Step 0. Make sure skyfragrances.com is a website on your hosting plan
>
> 1. Log in at hpanel.hostinger.com → **Websites**. If `skyfragrances.com` is in the list with a
>    **Manage** button, skip to Step 1.
> 2. If it is not, press **Add website** (or **Set up** on your hosting plan). When hPanel asks
>    what to build, choose **Empty website** / **Skip, I will upload my own files** — *not*
>    WordPress and *not* Website Builder. When it asks for the domain, choose
>    **Use an existing domain** → `skyfragrances.com`. Finish the wizard; it takes a minute and
>    then shows the website dashboard.
> 3. If you ever see a page offering to "Install WordPress" or "Create with AI Builder" for this
>    domain, close it. Nothing else needs installing — the shop is the ZIP.

### 3. BLOCKER — hPanel's *Force HTTPS* is often already ON, and the guide only says "leave it off"

On current Hostinger plans the SSL page enables **Force HTTPS** by default once the certificate
is issued. Part 1 Step 3 tells him to *leave* the switch off; if it is already on he will do
nothing. Hostinger's switch writes its own redirect block into `public_html/.htaccess`; when he
deletes the placeholder files (Part 4 Step 2) that block goes, but the switch stays "on" and
Hostinger may re-write it on top of the shop's `.htaccess`, breaking the shop's own redirect and
the **Make HTTPS permanent** button (which looks for its exact 302 line).

**Change Part 1 Step 3, first bullet, to:**

> - **Security → SSL → Force HTTPS.** Look at the switch. If it is **on** (green), turn it
>   **off** now and confirm. If it is already off, leave it. The shop forces https:// by itself,
>   in the correct way for Hostinger's servers; Hostinger's switch writes a second, competing
>   rule into the shop's files and can send visitors round in a loop. Check this switch again
>   after Part 4 — if it turned itself back on, turn it off again.

### 4. RISK — Screen 2 pre-fills the SMTP host, so "skipping email" needs an extra action

The guide's Screen 2 table says *"If you skip this, the shop still works"*. In `install.php`
the **SMTP host** field is pre-filled with `smtp.hostinger.com` and **SMTP port** with `587`
(`inst_page_db_form`). Skipping by simply not typing a password leaves host set and user/pass
empty: `config.php` records a half-configured mailbox, Screen 3 shows a **Send test email**
button that fails, and later every order email is attempted, fails and lands in *Tools → Emails
not yet sent* as **failed** instead of quietly waiting. The installer itself says "Leave the host
empty to skip", the guide does not.

**Change the "Email sending (optional)" row of the Screen 2 table to:**

> | **Email sending (optional)** | To send order emails: SMTP host `smtp.hostinger.com`, SMTP port `587` (always 587 here — the installer only speaks STARTTLS; do not type 465 even if Hostinger's screen shows it), Mailbox address `orders@skyfragrances.com`, Mailbox password (Part 3), Sender name `Sky Fragrances`. **To skip email for now: delete the pre-filled text in the SMTP host box so it is empty** — the shop then saves order emails on the server instead of sending them, and a developer can add the mailbox later (Part 10). |

### 5. RISK — He wants to be live *now* with 12 fake perfumes and "REPLACE ME" bank details

His message: "show the world that our website is live … in the meantime we will complete it …
I will upload the perfumes". Part 9 says announce only after payment details and sample removal
— but he has said he will not wait. With the sample data on, a real visitor can order "Azure
Oud" cash-on-delivery, and a bank-transfer checkout displays `0000-0000000-000 (REPLACE ME)` as
the account number. Google is also allowed to index the sample catalogue from minute one
(`site_indexable` is set to 1 automatically on skyfragrances.com). The guide has all the tools
(bulk **Hide**, payment toggles, indexing switch) but never assembles them into a "soft launch".

**Add a new section after Part 7 Step 1, "Soft launch — if you want the address to work before the real perfumes are in":**

> You may share the address before everything is ready, but do these four things first (ten
> minutes):
>
> 1. **Payments** — Settings → Payments: fill in your real bank, JazzCash and Easypaisa details
>    (Step 3). If you cannot yet, switch **Bank transfer**, **JazzCash** and **Easypaisa** off
>    and leave only **Cash on Delivery** on — the placeholder account numbers must never be
>    shown to a customer.
> 2. **Hide the sample perfumes** — Products → tick all → bulk action **Hide**. The shop then
>    shows your hero, collections and quiz, and the Shop page says nothing is available until
>    you add real products. (Or leave them visible and accept that someone may order a sample
>    you must then cancel with an apology — your choice, but say so in the WhatsApp reply.)
> 3. **Keep Google out until the catalogue is real** — Settings → Advanced → **Search engines
>    may index this site: off**. Switch it back on in Part 9. Without this, Google can list
>    "Azure Oud" under your brand for weeks.
> 4. **Announcement bar** — Settings → Home: change the text to something like *"Launching soon
>    — follow us on Instagram for the first drop"* so visitors know the catalogue is coming.
>
> Then do Parts 7 and 8 at your own pace and, when done, flip indexing on and remove the sample
> data (Step 6).

### 6. RISK — Screen 4 is shown exactly once on the real domain and then disappears

On skyfragrances.com the installer deletes itself *before* rendering Screen 4
(`inst_handle_install` → `@unlink(__FILE__)` → `inst_page_finish`). The page in the browser is
the only copy of the **Installation report** and **Security checks**; a reload gives the shop's
404 and *Run the checks again* is not offered once the file is gone. The guide tells him to
"read any Note" but not that he has one chance.

**Add at the top of "Screen 4 — Finish":**

> **Screenshot this whole page before you close or reload it** (scroll and take two or three
> screenshots). On skyfragrances.com the installer deletes itself the moment this page appears,
> so the report cannot be opened a second time. Send the screenshots to the developer — they
> are the record that the installation was clean.

---

## B. Order of operations — SSL before or after install?

**The guide has it right, and it must stay that way: SSL first, then upload, then install.**
Three facts from the shipped files make the order non-negotiable:

- `public_html/.htaccess` redirects every `http://` request to `https://` (302) unconditionally.
  If the certificate is not active, opening `http://skyfragrances.com/install.php` lands on a
  browser certificate warning; he cannot reach the installer at all.
- `.user.ini` sets `session.cookie_secure = 1`, so even if he clicked through the warning the
  admin login would not stick over plain http.
- The installer's **HTTPS redirect** step (`inst_https_permanent`) probes
  `https://skyfragrances.com/robots.txt` with certificate verification; with a valid
  certificate it flips the redirect to 301 during install and Part 6 Step 1 becomes a no-op.

Two clarifications the guide should add:

### 7. CLARITY — "Padlock before upload" is stated, but not why, and not what happens if he ignores it

**Add to the end of Part 1 Step 1:**

> Why this order matters: the shop's own files force https:// from the first second. If the
> certificate is not active when you open `install.php`, your browser will show a red
> "Your connection is not private" page and there is nothing to click through — you would have
> to wait for the certificate anyway. Hostinger issues it within about 15 minutes of the domain
> being connected; the padlock on the placeholder page is your signal to continue.

### 8. CLARITY — The five-minute wait in Part 4 is unexplained and easy to skip

**Change the last paragraph of Part 4 to:**

> Wait about five minutes before Part 5 (make a cup of tea). Hostinger re-reads the shop's
> `.user.ini` settings file every five minutes; if you open the installer sooner, Screen 1 may
> show "PHP memory limit" or "Upload size limits" with Hostinger's old values. If it does,
> simply wait and reload — do not change anything in PHP Configuration.

---

## C. Step-by-step gaps (in guide order)

### 9. CLARITY — Part 4 Step 1: where `public_html` actually is in File Manager

Current hPanel File Manager opens at the account root, which shows `domains/` and a `public_html`
shortcut; on multi-site plans only `domains/skyfragrances.com/public_html` exists.

**Change Part 4 Step 1, point 2 to:**

> 2. You land in a folder view. Double-click **public_html**. If you do not see it, open
>    **domains** → **skyfragrances.com** → **public_html** instead. The address bar at the top
>    of File Manager should now end in `/public_html`. This folder is the website: whatever is
>    inside it is what the internet sees at skyfragrances.com.

### 10. CLARITY — Part 4 Step 4: what the Extract box looks like and the field to check

**Change Part 4 Step 4, point 2 to:**

> 2. A small box appears with one text field, pre-filled with the folder you are in. It must
>    read exactly `/public_html` (or `public_html`) — nothing after it. If the field shows
>    `/public_html/skyfragrances-20260928-0917` delete everything after `public_html`. Press
>    **Extract**. Extraction takes 10–30 seconds; the file list refreshes by itself.

### 11. RISK — The nested-folder case is described as if the installer will tell him; it usually cannot

If the ZIP lands in `public_html/public_html/`, opening `skyfragrances.com/install.php` shows
Hostinger's own "not found" page — the installer is not reachable at that address, so its
"Nested public_html" message (which only fires when someone opens
`/public_html/install.php`) is never seen. Part 4 Step 5 says "The installer also detects this
and tells you", which will send him looking for a message that never appears.

**Replace that sentence in Part 4 Step 5 with:**

> If you get this wrong, `https://skyfragrances.com/install.php` shows a plain "404 Not Found"
> or Hostinger's "coming soon" page instead of the dark installer — that is your signal to look
> for the nested folder in File Manager. Fix it there as described above; do not look for an
> error from the installer, because at that moment the installer cannot be reached.

### 12. CLARITY — Screen 1: "Pretty web addresses — could not be checked" will very likely appear even on the connected domain

The check compares `gethostbyname('skyfragrances.com')` with `SERVER_ADDR`. On Hostinger the
public address is a load-balanced/proxied IP while `SERVER_ADDR` is an internal one, so the row
shows **Note** regardless — and the same test disables the installer's **Security checks** and
the **HTTPS redirect** flip (they say "skipped because … does not point at this server yet").
The guide frames this as "before your domain fully points" only, so he will think his domain
is broken and wait.

**Change the "Note rows" bullet in Screen 1 to:**

> - **Note** rows are information only. You will almost always see *Pretty web addresses —
>   could not be checked … does not point at this server yet* even though your domain is
>   connected — Hostinger's servers sit behind a traffic router and the installer cannot see
>   its own public address. Ignore it; it also means the *Security checks* on Screen 4 will be
>   "skipped" and the HTTPS redirect will stay temporary, both of which the guide handles by
>   hand (Screen 4 bullets and Part 6 Step 1). Read any *other* Note and do what it says.

### 13. RISK — Screen 3 pre-fills the username with `admin`

`inst_page_admin_form` sets the **Admin username** default to `admin`; the guide says "choose
something other than `admin`" but not that the box already contains it. He will leave it.

**Change the Admin username row of the Screen 3 table to:**

> | Admin username | The box already says `admin` — **delete it** and type your own, for example `sky.owner`. Letters, numbers, dots, dashes, underscores; 3 to 64 characters. A guessable username makes the lock-out protection much weaker. |

### 14. CLARITY — Screen 3: the WhatsApp box only understands Pakistani numbers written one way

`inst_phone_normalize` strips one leading `0`, then a leading `92`, then prefixes `+92`. `0300
1234567` and `+92 300 1234567` both work; `0092 300 1234567` becomes `+92092…`, and a non-Pakistani
number is silently turned into a wrong `+92` number.

**Change the WhatsApp row of the Screen 3 table to:**

> | WhatsApp number | Type it the way it is dialled inside Pakistan: `0300 1234567`. Do not type `0092`, and do not use a non-Pakistani number here (the installer always stores it as +92…). You can change it later in Settings → Contact & Social. |

### 15. CLARITY — "Your email address" on Screen 3 becomes public

The installer copies `admin_email` into `contact_email` (shown on the Contact page and in
page footers) and `order_notify_email`. The guide mentions this in passing; a personal Gmail
will be on the public website within the hour.

**Change the "Your email address" row to:**

> | Your email address | Use a business address you are happy to show to the public, for example `orders@skyfragrances.com` (Part 3) or `info@…`; it appears on the Contact page and receives the **New order** alerts. Not your personal Gmail — you can change both later in Settings → Contact & Social, but it is live from the first minute. |

### 16. CLARITY — The install key box can appear a second time on Screen 3

The key is checked once per browser session (`$_SESSION['inst_key_ok']`); PHP's default session
lifetime is about 24 minutes. If he goes to make the mailbox between screens and comes back,
Screen 3 shows the *Prove you own this hosting account* box again (or "This form has expired").
The guide covers the expiry but not the key's reappearance, and he may think the key changed.

**Add to Screen 2, after "You only do this once":**

> If you take a long break and the *Prove you own this hosting account* box appears again on
> Screen 3, the key has **not** changed — paste the same 32 characters from
> `storage/.install-key` again. The file is only deleted when the installation finishes.

### 17. NICE — Screen 2 "Create config.php by hand" tells him to click Continue but not which permissions

If the folder is not writable (rare on Hostinger), he pastes the file and continues; the
installer later tries `chmod 0400` and reports a **Note** if it fails. Fine — but say that the
Note is expected in this path and how to fix it.

**Append to the "Create config.php by hand" bullet:**

> After the shop is installed, Screen 4 may show *config.php locked — could not be made
> read-only*; in that case right-click `config.php` in File Manager → **Permissions** → type
> `400` → save.

### 18. CLARITY — Part 3: Hostinger may first ask him to "set up" email

On many current plans the **Emails** page shows an *"Get started with Hostinger Email"* screen
before any mailbox can be created; the included plan is free but must be chosen. The guide goes
straight to "Create email account".

**Add before Part 3 point 2:**

> If the Emails page shows a **Get started** / **Set up** / **Choose a plan** screen instead of a
> list of mailboxes, pick the free plan that is included with your hosting (it says *Free* or
> *Included*) and confirm with `skyfragrances.com`. Do not buy the paid Business/Enterprise
> tiers — the shop sends a handful of emails per order. The mailbox list appears afterwards.

### 19. NICE — Part 5, Screen 3 "Send test email" button: say where it sends

The installer sends the test to the **Your email address** box, not to the mailbox. If he typed
`orders@skyfragrances.com` there (as gap 15 recommends) he must open webmail to see it.

**Change the sentence "check your inbox (and spam) on your phone" to:**

> It is sent **to the address in "Your email address"** on this screen. If that is
> `orders@skyfragrances.com`, open Hostinger webmail (Part 3 point 4) to look for it; otherwise
> check your inbox and spam on your phone.

### 20. CLARITY — Part 7 Step 5 photos: iPhone photos are HEIC and will be refused

The uploader accepts `image/jpeg`, `image/png`, `image/webp` only (`app/lib/upload.php`).
iPhones save HEIC by default; the file picker greys them out or the upload is refused with no
hint about the cause.

**Add to the Photos bullet list:**

> - **iPhone users:** photos taken with the default settings are HEIC files, which the shop does
>   not accept. Either set **Settings → Camera → Formats → Most Compatible** before shooting, or
>   share each photo to yourself as a JPEG (Photos → Share → Options → *Most Compatible*).
>   Android phones already save JPG.

### 21. NICE — Part 8: give the cron recipe so he is not blocked on a developer

Handover §5.6 says CLI cron needs no key. The guide says "a developer can add a 15-minute cron
job" — the owner can do it himself in two minutes if told the exact command; the path is visible
in File Manager's address bar.

**Replace the cron paragraph in Part 8 "Check the emails" with:**

> If you want the emails to go out even when nobody has the panel open (recommended once real
> orders arrive): left menu → **Advanced → Cron Jobs**. Choose **Custom**, set it to run **every
> 15 minutes** (`*/15 * * * *`), and in the command box type
> `php /home/uXXXXXXXXX/domains/skyfragrances.com/public_html/cron.php` — replace the
> `/home/uXXXXXXXXX/…/public_html` part with the exact path shown at the top of File Manager
> when you are inside `public_html`. Press **Save**. No key is needed when it runs this way.

### 22. CLARITY — Part 10 "Forgotten admin password" — verified, but two details are missing

The printed hash does verify against `Reset-Sky-2026-Temp!` (checked with `password_verify`).
Two things a first-timer will trip on: phpMyAdmin's Edit form shows a **Function** dropdown next
to each field (it must stay blank — choosing MD5/PASSWORD there ruins the hash), and the row's
`is_active` must still be 1.

**Add to step 3 of that procedure:**

> Leave the **Function** dropdown next to the field empty (blank). Do not tick any box or
> choose MD5/PASSWORD — paste the line exactly and press **Go**. Also check that the field
> **is_active** in the same row is `1`.

---

## D. Things the owner cannot do at all (needs the developer)

| Item | Why the guide cannot get him there | Suggested one-liner to add |
|---|---|---|
| HSTS header | One `.htaccess` line the guide forbids him from editing (rightly). | Part 6: "After a month of stable https, ask the developer to add the HSTS line — you cannot do this yourself." |
| Trimmed update ZIPs | Handover §5.12: updates re-ship `install.php`, `.htaccess`, `robots.txt`. Part 11 handles it manually. | Fine as is; keep Part 11 point 4–5. |
| Real hPanel screenshots | Handover §9.2: none taken. Labels are hedged well. | Nothing to add; note for the developer. |
| Anything printed on Screen 4 after it is gone | See gap 6. | Screenshot instruction. |
| Adding SMTP later | Part 10 explains the `config.php` edit and it is doable, but a wrong quote breaks the whole site with a black "Something went wrong" page. | Add to Part 10 "Changing the mailbox password": "If the site shows a black error page right after you save, you removed a quote mark or comma. Press **Edit** again and compare with `config.sample.php` in the same folder." |

---

## E. Things checked and found correct (no change)

- Part 4 Step 5 file list matches `dist/public_html/` exactly (564 files; `.gitkeep` files are
  harmless and invisible without hidden files).
- ZIP root is flat (`favicon.ico`, `install.php`, `.user.ini` at the top of `unzip -l`), so
  extracting into `/public_html` gives the right layout.
- Screen 1 "Problem" table matches every `fail` row the installer can produce (PHP < 8.2,
  memory < 128M, upload limits < 6M, missing extensions, unwritable folders, missing root
  `.htaccess`, sub-folder, nested `public_html`, missing `schema.sql`).
- Screen 2 red messages match the installer's exact wording for key mismatch, connection
  failure, server version, and missing CREATE privilege.
- Screen 3 validations (username regex, email, 12-char password, match, store name,
  replace_partial tick) are all described.
- `install.php` self-deletes only when `env !== 'development'`; on skyfragrances.com env is
  `production`, so the green panel is the expected result.
- Photos upload one file per request in `admin.js`, so `.user.ini`'s `max_file_uploads = 3`
  does not limit a multi-select.
- "At least one payment method must stay on" is enforced in `admin/controllers/settings.php`.
- Products list has bulk **Hide** / **Make live** (used by gap 5).
- SPF/DKIM record names quoted in Part 6 match Hostinger's current `hostingermail-a/b/c`.
- Password-reset hash verifies.

---

## F. Printable day-one checklist

Print this page. Tick each line. Times are for a first-timer.

**Before you start (5 min)**
- [ ] Laptop or phone with the Hostinger login; notes app open.
- [ ] The ONE file to upload: `skyfragrances-20260928-0917.zip` (14,057,392 bytes). Not the folder, not an older ZIP, not one you compressed yourself.
- [ ] Bank, JazzCash, Easypaisa details; WhatsApp number in `0300 1234567` form; logo PNG; perfume photos as JPG (not HEIC).

**Part 1 — hPanel (10 min)**
- [ ] Websites → `skyfragrances.com` listed with **Manage**. If not: Add website → Empty website → existing domain.
- [ ] Setup wizard: chose **Skip / upload my own files**. No WordPress, no Builder.
- [ ] `https://skyfragrances.com` shows Hostinger placeholder **with padlock**. (Security → SSL says Active.)
- [ ] Security → SSL → **Force HTTPS is OFF** (turned it off if it was on).
- [ ] Advanced → PHP Configuration → **8.2** (or 8.3) → Update. Nothing else changed.

**Part 2 — Database (5 min)**
- [ ] Databases → Management → name `skyfrag`, user `skyadmin`, generated password → Create.
- [ ] Written down: full DB name `u…_skyfrag`, full user `u…_skyadmin`, password.
- [ ] Did NOT open phpMyAdmin.

**Part 3 — Mailbox (5 min, optional tonight)**
- [ ] Emails → (chose free included plan if asked) → created `orders@skyfragrances.com`, password written down.
- [ ] Logged in to webmail once. SMTP: `smtp.hostinger.com`, **587**.
- [ ] OR decided to skip email tonight → remember to EMPTY the SMTP host box on Screen 2.

**Part 4 — Upload (10 min + 5 min wait)**
- [ ] File Manager → inside `/public_html` (via `domains/skyfragrances.com` if needed). Hidden files ON.
- [ ] Deleted `default.php` and anything else already there.
- [ ] Uploaded the ZIP → Extract → destination exactly `/public_html` → deleted the ZIP and any `__MACOSX`.
- [ ] `public_html` shows `.htaccess`, `.user.ini`, `admin/ app/ assets/ db/ storage/ uploads/`, `index.php`, `install.php`, `robots.txt`, `cron.php`, `config.sample.php`, `favicon.ico` — no inner `public_html` folder.
- [ ] Force HTTPS in hPanel still OFF.
- [ ] Waited 5 minutes.

**Part 5 — Installer (10 min)**
- [ ] `https://skyfragrances.com/install.php` → dark installer page (not a 404, not "coming soon").
- [ ] Screen 1: no **Problem** rows. "Pretty web addresses — could not be checked" is normal. Continue.
- [ ] Screen 2: pasted the key from `storage/.install-key`; DB name/user/password; Shop address `https://skyfragrances.com`; SMTP filled in (587) OR host box emptied. Test connection and continue.
- [ ] Screen 3: replaced `admin` with my own username; business email; 12+ char password saved in password manager; WhatsApp `03…`; sample data ticked; (Send test email first if SMTP set). Create the shop.
- [ ] Screen 4: **screenshots taken of the whole page**. Green panel seen (or red → Delete install.php for me).
- [ ] Opened the addresses named in the Security checks Notes (`/config.php`, `/db/schema.sql`, `/storage/.htaccess`, `/app/bootstrap.php`): each shows an error page, never code.
- [ ] `https://skyfragrances.com/install.php` now shows the shop's "page not found".

**Part 6 — HTTPS (2 min)**
- [ ] Admin → Settings → Advanced → HTTPS redirect says **Permanent (301)** or pressed **Make HTTPS permanent**.
- [ ] Emails → no "DNS records missing" warning (or pressed Fix).

**Soft launch — before sharing the link (10 min)**
- [ ] Settings → Payments: real bank/JazzCash/Easypaisa details, OR those three switched off and only COD on. No "REPLACE ME" left visible.
- [ ] Products → select all → **Hide** (or accepted that sample orders may arrive).
- [ ] Settings → Advanced → Search engines may index: **OFF** until real perfumes are in.
- [ ] Settings → Home: announcement bar text changed.
- [ ] Settings → Contact & Social: WhatsApp, phone, Instagram, Facebook checked.
- [ ] Opened the shop on the phone: home page loads, padlock shown, WhatsApp button opens WhatsApp.

**Adding perfumes (as long as it takes)**
- [ ] Collections created first.
- [ ] At least 4 real perfumes Active, each with photos (JPG/PNG/WEBP, under 6 MB, portrait).
- [ ] Tools → Remove sample data → typed DELETE.
- [ ] Settings → Advanced → Search engines may index: **ON**.
- [ ] One COD test order + one transfer test order placed and moved through the statuses; then cancelled.
- [ ] Search Console verification pasted; sitemap `https://skyfragrances.com/sitemap.xml` submitted.

**Never**
- [ ] Never switch Force HTTPS on in hPanel. Never edit `.htaccess`, `.user.ini`, `app/`, `admin/`, `db/`. Never delete `storage/` or `uploads/`. Never share `config.php` or the install key. Never leave `install.php` on the server.
