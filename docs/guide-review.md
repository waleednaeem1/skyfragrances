# Go-Live Guide: owner's-eyes review (pass 5, 30 Sep 2026)

I read `GO-LIVE-GUIDE.md` and `HANDOVER.md` as the shop owner would: he runs a business, has
bought a domain in hPanel and has never put a website online. He plans to follow the guide
tonight with `dist/skyfragrances-public_html-folder.zip` (13,943,159 bytes, 637 entries, built
29 Sep 11:41). I checked every step against these sources:

- the ZIP's real contents (`unzip -l`)
- every input and check in `site/install.php` (all 1,324 lines)
- `dist/htaccess-upload/`
- `.github/workflows/deploy.yml`
- the live site, using read-only requests on 30 Sep 2026 (`curl` for pages and headers, DNS
  over HTTPS)

I did not change any code and did not commit anything. This file replaces pass 4. The guide has
not changed since pass 4 (the guide is dated 12:00 on 29 Sep and pass 4 was 12:08), so the pass-4
points that still hold are carried over below.

## Verdict

**Do not follow Parts 1 to 5 tonight. The shop is already installed and live on
skyfragrances.com.** Here is what the live site returns today:

- the home page answers 200 with the 12 sample perfumes
- `/install.php` returns 404, so the installer has already run and deleted itself
- `/admin/login` answers 200
- `robots` says `index,follow`, and `/sitemap.xml` lists 40 addresses, including
  `/product/azure-oud`
- the WhatsApp button points to `wa.me/923203271071`
- SPF, DKIM, MX and DMARC records all exist

The guide never asks "is it already installed?". If the owner follows it tonight:

- Part 1 tells him he will see Hostinger's placeholder page, but he will see the shop.
- Part 4 has him rename the live `public_html`, which takes the shop offline at once.
- Part 5 then either stops at "The database is not empty" or, with a new database, creates a
  second empty shop that has its own admin. The sample data and settings in the first shop stay
  orphaned in the old database.

Everything else in this review only matters for a future re-install. It is still worth fixing,
because the guide is also the disaster-recovery manual.

Two more facts from the live site contradict the guide's "Never do this" table:

1. Traffic passes through Hostinger's CDN (`server: hcdn`, `x-hcdn-cache-status: MISS` on
   `site.css`).
2. Hostinger's own edge, not the shop, answers `http://` → `https://` and `www` → apex with a 301.
   The response carries `platform: hostinger` and `content-security-policy:
   upgrade-insecure-requests`.

So the CDN and a Hostinger HTTPS redirect are on, and the shop works. The owner cannot settle
this. The developer has to.

**The step order is right for a fresh install and should stay as it is:** padlock (SSL), then
Force HTTPS off, then PHP 8.2, then the database, then the mailbox, then upload, then the
installer, then admin. SSL has to come before `install.php` for two reasons:

- the root `.htaccess` (lines 21–24) redirects every http request to https from the very first
  request
- the installer's session cookie is marked `secure` when it runs over https

The installer itself catches the dangerous mistakes with plain-words messages: wrong key, wrong
password, missing privileges, non-empty database, nested folder, sub-folder, missing `.htaccess`.

Legend:

- **BLOCKER**: following the guide tonight damages the live shop.
- **RISK**: a wrong result or a lost hour.
- **CLARITY**: the owner hesitates or clicks the wrong thing.
- **NICE**: polish.

---
## Findings

### 1. BLOCKER: The guide has no "already installed?" check, and the shop is already live

*Where:* the top of the guide, before Part 1.

*Gap:* See the verdict for what the live site returns. The guide assumes an empty hosting account.
Part 1 Step 1 tells him to expect Hostinger's placeholder page. Part 4 Step 3 has him rename
`public_html`. Parts 2 and 5 create a new database and a new admin. On a live shop that means
downtime, then a confusing installer message, then possibly a second shop.

*Fix (add as the first section, "Part 0: Is your shop already installed?"):*

> Open https://skyfragrances.com on your phone. **If you see the Sky Fragrances shop (black page,
> perfumes, a WhatsApp button), the technical setup is already done: skip Parts 1 to 5 completely,
> do not upload anything and do not create a database.** Your developer gives you the admin
> username; set your own password with Part 10 → "Forgotten admin password" (ten minutes, File
> Manager only), then continue at "Soft launch" in Part 7 and do Part 6 afterwards. Only if you
> see Hostinger's "coming soon" page or an error do you start at Part 1.

In HANDOVER.md, add under the build line:

> **State of the live server on 30 Sep 2026:** skyfragrances.com is installed (install.php gone,
> 12 sample perfumes live, indexable, sitemap 40 URLs, WhatsApp +92 320 3271071, SPF/DKIM/MX/DMARC
> present). The owner must not run Parts 1–5 of the guide; he starts at Part 0.

### 2. BLOCKER: The owner has no admin login for the live shop, and neither document says where it comes from

*Where:* HANDOVER §1, the credentials table, and guide Part 7 Step 1.

*Gap:* The table lists "Admin username + password: created at installer screen 3". Screen 3 was
filled in by whoever installed the live shop, not by the owner. Part 7 Step 1 says "enter the
username and password you chose on installer Screen 3", and he chose neither.

*Fix:* In HANDOVER §1 add a row:

> | Admin login on the live shop | Created by the developer at install | `/admin/login` | The developer tells the owner the **username only**; the owner sets his own password with `app/tools/reset-password.php` (guide Part 10) so nobody else ever knows it. |

In guide Part 7 Step 1 add:

> If the developer installed the shop for you, you were given only the username. Set your password
> first with Part 10 → "Forgotten admin password", then log in here.

### 3. BLOCKER: The live shop is public and indexable with sample perfumes, so the soft launch is urgent

*Where:* Part 7, "Soft launch", which is written as optional ("if you want the address to work
before…").

*Gap:* The address already works. `robots` says `index,follow` and the sitemap offers Google 40
addresses, including sample perfumes such as Azure Oud that do not exist. Anyone can add one to
the cart and order it tonight.

*Fix (replace the soft-launch intro sentence):*

> **The shop is already public. Do these four things tonight, before anything else in the admin
> panel** (ten minutes). Without them a visitor can order a sample perfume that does not exist,
> and Google keeps listing "Azure Oud" under your brand.

Add a fifth item:

> 5. If Google has already listed sample perfumes, they drop out by themselves within a few weeks
> once the products are hidden and indexing is off. You do not need to do anything in Search
> Console for this.

### 4. BLOCKER for the developer: the live site runs with the CDN and a Hostinger HTTPS redirect that the guide forbids

*Where:* Part 1 Step 3, the checklist "NEVER" line, the Quick-reference "Never do this" table, and
Part 6 Step 1.

*Gap:* See the verdict. The guide says the CDN "can mix customers' carts up" and Force HTTPS "can
send visitors round in a loop". The live site runs with both and shows neither symptom: HTML comes
back `x-hcdn-cache-status: DYNAMIC`, so pages are not cached. The owner reads "must be off", finds
them on, and either switches them off on a working shop or wonders whether the guide can be trusted.

There is also a side effect only the developer can judge. Behind a CDN, `REMOTE_ADDR` is a CDN
edge. `install.php` records one edge address as trusted (`inst_trusted_proxies()`), not the whole
edge range. If the app then keys rate limits and the "one unverified transfer per IP" rule (C-58)
on the edge address, one customer's pending bank transfer could block another customer's.

*Fix:* The developer decides and the guide states the decision. Until then, replace the first
bullet of Part 1 Step 3 and the CDN bullet with:

> **Security → SSL → Force HTTPS** and **Performance → CDN**: on a fresh install, turn both off as
> described. **If your shop is already live and working (Part 0), leave both exactly as they are
> and do not change them yourself** — the developer has checked the live settings.

In HANDOVER §9 add open item 13:

> Live site answers through Hostinger's CDN (`server: hcdn`) and Hostinger's edge does the http→https
> and www→apex 301 (`platform: hostinger`). Decide whether the guide's "CDN off / Force HTTPS off"
> rule still stands; confirm `trusted_proxies` covers the CDN edge range so rate limits and C-58
> key on the customer, not the edge.

### 5. RISK: The day-one checklist contradicts Part 4 and causes the nested-folder mistake

*Where:* three checklist lines. "File Manager inside `/public_html` …", "Deleted `default.php` and
everything else there.", and "Uploaded the ZIP → Extract → destination exactly `/public_html`".

*Gap:* Every entry in the ZIP starts with `public_html/`, which I confirmed with `unzip -l`.
Extracting it **into** `/public_html` produces `/public_html/public_html/index.php`. That is the
exact failure Part 4 Step 5 warns about, and at that point the installer cannot be reached. The
body of Part 4 says the opposite: go up one level, rename, and extract there. The guide tells him
to print and follow the checklist, so the checklist wins.

*Fix:* Replace the three lines with the Part 4 lines of the checklist at the end of this review.

### 6. RISK: Part 4 starts at the wrong `public_html`

*Where:* Part 4 Step 1, point 2 ("Double-click **public_html**. If you do not see it, open
**domains** → …").

*Gap:* The website's real folder is `domains/skyfragrances.com/public_html`. That is the path the
deploy workflow writes to (`REMOTE_DIR` default). A `public_html` at the top of File Manager can be
the account-level shortcut. Going "up one level" from there (Step 3.1) lands in the account's home
folder. Renaming there and extracting there creates a folder the website never serves.

*Fix (replace point 2):*

> Open **domains** → **skyfragrances.com**. Always go this way, even if you also see a
> `public_html` at the top level. The address bar must now end in `/domains/skyfragrances.com`,
> and you should see a folder called `public_html` in the list. This `public_html` is the website:
> whatever is inside it is what the internet sees at skyfragrances.com.

Change Step 3.1 to:

> Stay in `domains/skyfragrances.com` (the address bar ends in `/domains/skyfragrances.com`). You
> see the `public_html` folder itself in the list.

### 7. RISK: Steps 2 and 3 do the same job twice, and there is no plan if Rename is refused

*Where:* Part 4 Step 2 (empty the folder) and then Step 3.2 (rename the folder you just emptied).

*Gap:* Emptying `public_html` and then renaming it is redundant, and it deletes `default.php` that
the rename would have kept. More importantly, nothing says what to do if hPanel refuses to rename
`public_html` (some panels protect it).

*Fix:* Delete Step 2 as a separate step and make Step 3.2 read:

> Right-click `public_html` → **Rename** → `public_html-old`. If hPanel refuses to rename it,
> open `public_html` instead, select everything inside (hidden files on), **Delete**, go back up,
> and continue with point 3 — the ZIP's `public_html` folder will then be extracted over the empty
> one.

### 8. RISK: The Extract box may not default to "the folder you are in"

*Where:* Part 4 Step 4.2 ("Leave it as it is").

*Gap:* File-manager extractors often offer a new folder named after the ZIP. Here that would be
`skyfragrances-public_html-folder`, which produces `…/skyfragrances-public_html-folder/public_html/`.
The site would show "coming soon" or a 404, and the guide's nested-folder fix does not describe
this layout.

*Fix (replace 4.2):*

> Look at the path in the box. It must end in `/domains/skyfragrances.com` (or be empty, or `.`).
> If it ends with a new name such as `skyfragrances-public_html-folder`, delete that last part so
> it ends in `/domains/skyfragrances.com`. Do **not** type `public_html` into it. Press
> **Extract**. A fresh `public_html` folder appears next to `public_html-old` after 10–30 seconds.

### 9. RISK: The guide never says how the ZIP reaches the owner, and Safari silently unpacks it

*Where:* "Have these ready", first bullet.

*Gap:* The guide keeps saying "the developer's folder `dist/…`", which lives on the developer's
Mac. The owner does not have it. If the developer sends the ZIP by Google Drive or WeTransfer and
the owner downloads it in Safari, Safari's default "Open 'safe' files after downloading" unpacks
it and moves the ZIP to the Bin. He then uploads a folder, loses `.htaccess` and `.user.ini`, and
lands in "If you uploaded the folder instead of the ZIP". Nothing tells him how to confirm he has
the right file.

*Fix (add at the start of the bullet):*

> Your developer sends you this one file (WhatsApp as a *document*, Google Drive or WeTransfer).
> Download it with **Chrome**, or in Safari first switch off **Safari → Settings → General → Open
> "safe" files after downloading** — otherwise Safari unpacks it for you and the ZIP is gone. On a
> Mac, click the file once and press **⌘I**: the size must read **13,943,159 bytes** and the name
> must end in `.zip`. If it shows a folder icon instead, ask for the file again.

### 10. RISK: The `.htaccess` fallback points at files the owner does not have, and they are older than the ZIP

*Where:* Part 4 Step 5, "If `.htaccess` or `.user.ini` are missing", and "If you uploaded the
folder instead of the ZIP".

*Gap:* There are three problems.

1. `dist/htaccess-upload/` is on the developer's Mac.
2. `htaccess.txt` there is dated 28 Sep 14:17 and differs from the ZIP's `.htaccess`. It lacks
   the `text/javascript` and icon expiry lines, the `Vary: Accept-Encoding` block, the explicit JS
   and CSS `AddType`, and `text/javascript` compression.
3. The guide says "If they really are missing, the installer's first screen will say so". That is
   only true for `.htaccess`. `inst_requirements()` never checks `.user.ini`. A missing
   `.user.ini` passes silently on Hostinger's defaults, and the shop then runs without
   `session.cookie_secure`, `display_errors = Off` and the other settings in it.

*Fix (replace both paragraphs):*

> **If `.htaccess` or `.user.ini` are missing** even with hidden files showing, the upload went
> wrong: delete the new `public_html`, rename `public_html-old` back to `public_html`, and repeat
> Steps 3–4 with the ZIP. The installer warns you about a missing `.htaccess`, but **not** about a
> missing `.user.ini` — so check for both yourself here. Do not create either file by hand.

Developer action: delete `dist/htaccess-upload/`, or regenerate it on every build.

### 11. RISK: A failed test email sends him back to a form with the database password blank

*Where:* Part 5, Screen 3, "A red box means the mailbox password or host is wrong; press **Back**,
then **Change database details**, and re-enter the email settings."

*Gap:* On Screen 2 `inst_page_db_form()` never refills the **Database password** box (the field is
rendered with `''`). The owner retypes only the mailbox password, presses **Test connection and
continue**, and gets "Could not connect … Access denied". He then assumes the database is broken.

*Fix (replace the sentence):*

> A red box means the mailbox password or host is wrong. Press **Back**, then **Change database
> details**. On that screen **type the database password again** (from your notes, Part 2) as well
> as the mailbox password — both boxes are empty — then press **Test connection and continue**.
> You return to this screen; send the test again.

### 12. RISK: Part 8b "Move → Replace" collides with the new ZIP's own `storage` and `uploads`, and forgets the HTTPS step

*Where:* Part 8b steps 4–6.

*Gap:* There are three problems.

1. The new ZIP already contains `storage/` and `uploads/` (with `.htaccess`, `.gitkeep` and the
   sample images). Moving the old folders onto `/public_html` makes the File Manager either
   refuse ("already exists"), merge, or replace, depending on the dialog. The owner cannot tell
   which one happened. If it refuses, `storage/.installed` never arrives and the site says "not
   configured".
2. Part 11 has a "Make HTTPS permanent" step, but Part 8b does not. HANDOVER §5.12 says both
   kinds of update need it.
3. `public_html-old` from install night is never scheduled for deletion. It still contains the
   placeholder page.

*Fix:*

Insert before step 4:

> 3b. Open the new `public_html` (hidden files on) and **delete its `storage` and `uploads`
> folders**. They are empty copies; yours come across in the next step.

Add a step 7:

> 7. Open **Settings → Advanced**: if **HTTPS redirect** says *Temporary (302)*, press **Make
> HTTPS permanent**.

In Part 5, after Screen 4, add:

> Once the shop opens and you can log in at /admin, go back to File Manager →
> `domains/skyfragrances.com` and delete `public_html-old`.

### 13. RISK: Part 11 says to extract "into public_html (same as Part 4)", but Part 4 extracts one level above

*Where:* Part 11 step 3.

*Gap:* The update pack is flat (no wrapper folder), so it must be extracted **inside**
`public_html`. The words "same as Part 4" now point at the opposite recipe. The flat pack also has
`.htaccess` at its top level, and the guide itself says hPanel's extractor skips top-level hidden
files. So the pack's `.htaccess` may never land, and nobody would notice.

*Fix (replace step 3):*

> File Manager → `domains/skyfragrances.com/public_html` (open it). **Upload** the update ZIP
> here → right-click → **Extract** → the box must end in `/public_html` → **Extract**. (This is the
> opposite of Part 4: the update pack has no folder around it.) Existing code files are replaced;
> `config.php`, `uploads/` and `storage/` are not in it and stay as they are. Delete the ZIP.

### 14. RISK: Every developer push quietly resets the HTTPS redirect to temporary, and the owner is never told updates arrive by themselves

*Where:* Part 11 intro, HANDOVER "Deploy over SSH".

*Gap:* `deploy.yml` rsyncs `site/` with `--checksum` and does not exclude `.htaccess`. The
shipped `.htaccess` has `R=302` on line 24. So every push to `main` that touches `site/` puts the
302 back. Settings → Advanced and Tools read the file (`https_redirect_is_permanent()`), so
after a developer push they show *Temporary (302)* again with no explanation, even though the
owner already pressed the button. The live site hides this today only because Hostinger's edge
does the redirect (finding 4). Part 11 also reads
as if every update is the owner's job, when normally the developer's push applies it within a
minute.

*Fix (add as the first paragraph of Part 11):*

> Normally you do nothing: when the developer changes the shop, the change reaches
> skyfragrances.com by itself within a minute. The two ZIP methods below are only for when the
> developer asks you to use them.

Developer action: exclude `.htaccess` from the rsync, or re-run the permanent flip after the
rsync in the same SSH step.

### 15. CLARITY: The installer and the guide give different ways to skip email

*Where:* Part 5, Screen 2, the "Email sending (optional)" row.

*Gap:* The installer's own hint on screen says "Leave the host empty to skip for now". The guide
says leave **Mailbox address and Mailbox password** empty. Both work. `inst_handle_db_save()`
clears the host when the address and password are both empty, and an empty host means nothing is
sent. But the owner sees two instructions and does not know which one to trust.

*Fix (add to the row):*

> The screen itself says "leave the host empty" — that works too. Either way is fine; just do not
> fill in only some of the four boxes.

### 16. CLARITY: "Each must show an error page" does not tell him that the shop's own "page not found" is the correct result

*Where:* Part 5, Screen 4, the "Security checks" bullet, and the checklist line for Screen 4.

*Gap:* On the live site, `/config.php`, `/db/schema.sql`, `/storage/.htaccess` and
`/app/bootstrap.php` all return the **shop's branded 404 page**, with the logo and menu. An owner
expecting a grey "error" page may think the shop "opened the file".

*Fix (add after "never file contents"):*

> The shop's own black "page not found" page, with the logo and menu, is the correct result. The
> only wrong result is a page full of code or text starting with `<?php`.

### 17. CLARITY: Screen 1 may show a "Client IP detection" Note that the guide never mentions

*Where:* Part 5, Screen 1, the Notes paragraph.

*Gap:* Behind Hostinger's CDN (finding 4), `inst_requirements()` adds the Note *"requests arrive
through a proxy (…); it will be recorded as trusted"*. The guide tells him to "read any other Note
and do what it says later". This Note asks him to do nothing, and he will look for something to do.

*Fix (add):*

> A Note *Client IP detection — requests arrive through a proxy* is normal on Hostinger; there is
> nothing to do.

### 18. CLARITY: The payment button label differs from the guide

*Where:* Part 8, "Bank transfer…", step 3 ("Press **Mark as paid**").

*Gap:* For an order that is awaiting verification (the case in this step), the button reads
**Approve payment — mark as paid** (`admin/views/order-detail.php:91`).

*Fix:*

> Press **Approve payment — mark as paid** once you have checked it in your banking app.

### 19. CLARITY: Terms used without explanation

*Where:* throughout the guide.

*Fix (add these half-sentences where each term first appears):*

- CDN (Part 1 Step 3): "Hostinger's CDN (copies of your site kept on servers around the world)".
- Webmail (Part 3.4): "webmail (Hostinger's website for reading the mailbox, like Gmail in a
  browser)".
- Permissions `755` / `400` / `0600` (Part 5, Part 10): "a three- or four-digit number that says
  who may read or change a file; type it exactly as written".
- Cron job (Part 8): "a Cron Job (a timer on the server that runs a task on its own every
  15 minutes)".
- Sitemap (Part 9): "the sitemap (a list of every page, made for Google)".
- DNS (Part 6 Step 2): "DNS (the domain's address-book entries)".
- In the checklist, write "HTTPS redirect = Permanent" rather than "(301)"; the number means
  nothing to him.

### 20. CLARITY: The time estimate and the device are wrong for tonight

*Where:* the introduction ("about 45 minutes", "your phone or laptop browser").

*Gap:* The SSL certificate can take up to 2 hours (Part 1). There is also a 5-minute wait for
`.user.ini`. Parts 2 to 5 need copying a 32-character key out of File Manager and uploading a
14 MB file, which is painful on a phone.

*Fix:*

> Time needed: about 45 minutes of your own work, plus waiting for the padlock (usually 15
> minutes, up to 2 hours) and one 5-minute pause in Part 4. Do Parts 1–5 on a laptop; the phone is
> for checking the result.

### 21. CLARITY: "A long time" should be a number

*Where:* Part 10 ("This form has expired … You were on the page for a long time").

*Gap:* The installer uses PHP's default session, which is usually removed after about 24 minutes
without activity. After "Start again", the installer lands on Screen 1. If `config.php` was
already written, **Continue** jumps straight to Screen 3, and the owner does not know that is
expected.

*Fix:*

> You left an installer screen open for more than about 20 minutes. Press **Start again**; nothing
> was lost. If you had already passed Screen 2, **Continue** takes you straight to Screen 3 — that
> is expected.

### 22. NICE: The cron path can be written out in full

*Where:* Part 8, the cron paragraph, and the checklist.

*Gap:* HANDOVER's deploy section already names the hosting user, `u561152958`. The owner does not
have to work out the `u…` number.

*Fix:*

> The path is `/home/u561152958/domains/skyfragrances.com/public_html/cron.php`. For the Custom
> type the command is `php /home/u561152958/domains/skyfragrances.com/public_html/cron.php`.

`cron.php` needs no key from the command line (`PHP_SAPI === 'cli'`).

### 23. NICE: Part 6 Step 2 (SPF/DKIM) is already done

*Where:* Part 6 Step 2.

*Gap:* Public DNS on 30 Sep shows everything this step asks for:

- MX: `mx1` and `mx2.hostinger.com`
- SPF: `v=spf1 include:_spf.mail.hostinger.com ~all`
- DKIM: `hostingermail-a._domainkey` → `hostingermail-a.dkim.mail.hostinger.com`
- DMARC: `p=none`

*Fix (add at the start of the step):*

> On skyfragrances.com these records already exist (checked 30 Sep 2026). You only need the Gmail
> test in point 3.

### 24. NICE: Part 10 "Changing the mailbox password" misses the port 465 case and should send him to the developer first

*Where:* Part 10, point 2.

*Gap:* When email is added for the first time on port 465, `'port'` must become `465` and
`'secure'` must become `'ssl'`. The guide only names `pass`, `host` and `user`. Also, one missing
comma in `config.php` takes the whole shop offline.

*Fix (add):*

> If Hostinger's settings say port 465, also change `'port' => 587` to `'port' => 465` and
> `'secure' => 'tls'` to `'secure' => 'ssl'`. If you are not comfortable editing this file, send
> the new password to your developer instead — a single missing comma takes the whole shop offline
> until it is fixed.

---

## Common mistakes: what actually happens

| Mistake | What the owner sees | Covered by the guide? |
|---|---|---|
| Runs Parts 1–5 on the already-live shop | Shop offline after the rename; then "The database is not empty", or a second empty shop | **No** (findings 1–3) |
| Extracts the ZIP into `/public_html` (as the checklist says) | Hostinger 404 or "coming soon" at `/install.php` | Body yes, checklist causes it (finding 5) |
| Extract box defaults to a new folder named after the ZIP | 404 or coming soon; layout not described | **No** (finding 8) |
| Downloads with Safari, which unzips it, then uploads the folder | `.htaccess` Problem row; `.user.ini` missing silently | Partly (findings 9–10) |
| Uploads `skyfragrances-public_html.zip` (flat) | Top-level dot-files skipped → `.htaccess` Problem row | Yes |
| Types `skyfrag` without the `u…_` prefix | "Could not connect … Access denied" | Yes |
| Reuses a database that has tables | "The database is not empty" | Yes |
| Presses Continue on Screen 1, looks for `.install-key` before Screen 2 has loaded | File not there yet | Yes (refresh advice) |
| Red test email → Back → re-enters only the mailbox password | "Access denied" on the database | **No** (finding 11) |
| Uses `admin` as the username | Accepted by the installer (the regex allows it); weaker lock-out | Guide warns |
| Closes Screen 4 without screenshots | Report is gone for good (install.php deleted itself) | Yes |
| Leaves a Settings tab without pressing Save | Changes lost, no warning | Yes |
| iPhone HEIC photos | Picker greys them out, or the upload is refused | Yes |
| Part 8b: Move `storage`/`uploads` onto the new folder | Refused, merged or replaced, and he cannot tell which; possibly "not configured" | **No** (finding 12) |

## Things a non-developer cannot do at all with the guide as written

1. **Get the ZIP.** It lives in `dist/` on the developer's Mac. The developer has to send it
   (finding 9).
2. **Use the `.htaccess` fallback.** `dist/htaccess-upload/` is also on the developer's Mac, and
   it is out of date (finding 10).
3. **Log in to the live shop.** The admin account was created by whoever installed it (finding 2).
4. **Decide about the CDN and Force HTTPS.** The live settings contradict the guide (finding 4).
5. **HSTS, restoring a backup (`.sql` import), and database changes in updates.** The guide
   already says these are the developer's job, which is correct.

---

## Day-one checklist (print this)

**If the shop is already live** (skyfragrances.com shows the Sky Fragrances shop):

- [ ] Do NOT upload anything, create a database, or touch Force HTTPS or the CDN.
- [ ] Get the admin **username** from the developer.
- [ ] Set my own password: File Manager → `domains/skyfragrances.com/public_html/app/tools` →
      copy `reset-password.php` to `public_html` → open `skyfragrances.com/reset-password.php`
      → create the named empty file in `storage` → reload → new password (12+ characters) → saved
      in a password manager.
- [ ] Logged in at `skyfragrances.com/admin`.
- [ ] Settings → Payments: real bank / JazzCash / Easypaisa details, OR those three switched off
      with only COD on. No "REPLACE ME" anywhere → **Save Payments**.
- [ ] Products → tick all → **Hide**.
- [ ] Settings → Advanced → **Let search engines index the shop**: OFF → **Save Advanced**.
- [ ] Settings → Home: announcement text changed → **Save Home**.
- [ ] Settings → Contact & Social: WhatsApp, phone, email, Instagram, Facebook checked → **Save**.
- [ ] Press **Save** on each Settings tab before clicking the next tab.
- [ ] Phone check: shop loads, padlock shows, WhatsApp button opens my WhatsApp.

**Only if the shop is NOT live** (Hostinger placeholder page or an error):

- [ ] ZIP received from the developer, downloaded with Chrome, **13,943,159 bytes** (⌘I), not
      unzipped. Working on a laptop.
- [ ] Padlock on `https://skyfragrances.com` (Security → SSL = Active). Force HTTPS OFF. CDN OFF.
      PHP 8.2 (or 8.3).
- [ ] Database `skyfrag`, user `skyadmin` created. Full `u…_` names and password written down.
- [ ] Mailbox `orders@skyfragrances.com` created, password written down. Or: skipping email
      tonight.
- [ ] File Manager → **domains → skyfragrances.com** (address ends in `/domains/skyfragrances.com`),
      hidden files ON.
- [ ] Rename `public_html` → `public_html-old`.
- [ ] Upload the ZIP **here** → Extract → path ends in `/domains/skyfragrances.com` → fresh
      `public_html` appears → delete the ZIP.
- [ ] Inside the new `public_html`: `.htaccess`, `.user.ini`, `admin app assets db storage
      uploads`, `index.php install.php` and the rest. No inner `public_html`. Waited 5 minutes.
- [ ] `skyfragrances.com/install.php` → Screen 1 has no Problem rows → Continue.
- [ ] Screen 2: key from `storage/.install-key`, database details, shop address
      `https://skyfragrances.com`, mailbox (or mailbox address and password empty).
- [ ] Screen 3: own username (not `admin`), business email, 12+ character password typed twice,
      WhatsApp `03…`, samples ticked. Test email → green → retype the password twice → Create the
      shop.
- [ ] Screen 4: **screenshots of the whole page**. Green "deleted itself" panel. `/config.php`
      and `/db/schema.sql` show the shop's "page not found".
- [ ] Logged in at `/admin` → then do the "already live" list above from **Settings → Payments**
      onward.
- [ ] Settings → Advanced → HTTPS redirect = Permanent (or press **Make HTTPS permanent**).
- [ ] Deleted `public_html-old`.

**Later, before announcing:** at least 4 real perfumes Live (JPG, portrait, under 6 MB) → Tools →
Remove sample data (type `DELETE`) → indexing ON → one COD and one transfer test order, then
cancel them → Google Search Console + sitemap. Optional cron:
`/home/u561152958/domains/skyfragrances.com/public_html/cron.php` every 15 minutes.

**Never:** edit `.htaccess`, `.user.ini`, `app/`, `admin/` or `db/`; delete `storage/` or
`uploads/`; share `config.php`; run `install.php` on a shop that has orders.
