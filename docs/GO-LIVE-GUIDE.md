# Sky Fragrances — Go-Live Guide

This guide takes the shop from a ZIP file to a live website at **https://skyfragrances.com**, using
only the Hostinger control panel (hPanel) and your phone or laptop browser. No technical knowledge
is needed. Follow the steps in order; each one tells you what you should see before you move on.

Time needed: about 45 minutes for the technical part (Parts 1–6), then as long as you like for
adding your perfumes.

**Have these ready before you start**

- Your Hostinger login (hpanel.hostinger.com).
- **Upload the ZIP, not the folder.** The developer's folder `dist/public_html` and the file
  `dist/skyfragrances-public_html-folder.zip` (14,062,456 bytes) contain exactly the same 565 files.
  The ZIP is the one to upload: hPanel unpacks it on the server in seconds and it keeps the two
  hidden files (`.htaccess`, `.user.ini`) that your Mac hides from you. Use **only** that ZIP.
  Ignore anything in `dist/old-builds` and any other ZIP or folder. In particular do not upload
  `skyfragrances-update-2026-09-28.zip`, any `skyfragrances-public_html-folder.zip`, or the folders
  `update-pack` and `admin-ui-fix` (all now parked in `dist/old-builds`): everything in them is
  already inside the 1906 ZIP. They are developer leftovers, not an update
  for you. Do **not** make your own ZIP
  with Finder → Compress: it wraps everything in an extra folder and adds `__MACOSX`, which is
  exactly the "nested public_html" mistake in Part 4. Keep the ZIP somewhere you can find it,
  for example your Downloads folder, and do **not** unzip it on your computer.
- A notes app open. You will write down three database values and one mailbox password.
- Your bank, JazzCash and Easypaisa account details, your WhatsApp number, your logo file, and
  your perfume photos (portrait orientation works best — the shop crops every photo to 4:5).

**Day-one checklist.** Print this block and tick each line as you go; every line is explained
in the part named.

- BEFORE: Hostinger login ready; notes app open.
- BEFORE: The ONE file to upload is `skyfragrances-public_html-folder.zip` (14,062,456 bytes) — not the
  folder, not `skyfragrances-update-2026-09-28.zip`, not the older `…-1816.zip`, not anything in
  `old-builds` / `update-pack` / `admin-ui-fix`, not a ZIP you compressed yourself; not unzipped on the computer.
- BEFORE: Bank, JazzCash, Easypaisa details; WhatsApp number as `0300 1234567`; logo PNG; perfume
  photos as JPG (not HEIC), portrait.
- PART 1: Websites → `skyfragrances.com` listed with **Manage** (else Add website → Empty website /
  upload my own files → existing domain). No WordPress, no Builder.
- PART 1: `https://skyfragrances.com` shows the Hostinger placeholder WITH a padlock (Security →
  SSL = Active). Still not Active after 2 hours → Help → live chat, ask for the free SSL. Do not
  continue without the padlock.
- PART 1: Security → SSL → **Force HTTPS is OFF** (turned off if it was on).
- PART 1: Performance → CDN off (and any cache switch off) if the plan shows that page.
- PART 1: Advanced → PHP Configuration → 8.2 (or 8.3) → Update. Nothing else changed.
- PART 2: Databases → Management → name `skyfrag`, user `skyadmin`, generated password → Create.
- PART 2: Written down: full `u…_skyfrag`, full `u…_skyadmin`, password. (The `u…` number is also
  the hosting username needed for the cron job later.)
- PART 2: phpMyAdmin NOT opened.
- PART 3: Emails → (free included plan if asked) → `orders@skyfragrances.com` created, password
  noted, webmail login tested; SMTP `smtp.hostinger.com` port 587. OR skipping email tonight →
  leave Mailbox address AND Mailbox password EMPTY on Screen 2.
- PART 4: File Manager inside `/public_html` (via `domains/skyfragrances.com` if needed); hidden
  files ON.
- PART 4: Deleted `default.php` and everything else there.
- PART 4: Uploaded the ZIP → Extract → destination exactly `/public_html` → deleted the ZIP and any
  `__MACOSX`.
- PART 4: `public_html` shows `.htaccess`, `.user.ini`, `admin/ app/ assets/ db/ storage/ uploads/`,
  `index.php`, `install.php`, `robots.txt`, `cron.php`, `config.sample.php`, `favicon.ico` — no
  inner `public_html`.
- PART 4: Force HTTPS in hPanel still OFF. Waited 5 minutes.
- PART 5: `https://skyfragrances.com/install.php` shows the dark installer. (Blank page / HTTP 500
  = PHP version, Part 1 Step 2. 404 / coming soon = nested folder or `default.php`, Part 4.)
- PART 5 Screen 1: no Problem rows; "Pretty web addresses — could not be checked" is normal; a PHP
  "tested on 8.2 and 8.3" Note = choose 8.3 in hPanel → Continue.
- PART 5 Screen 2: pressed Continue FIRST, then refreshed File Manager → `storage/.install-key` →
  pasted the 32 characters; DB name / user / password; Shop address `https://skyfragrances.com`;
  SMTP filled (587) OR mailbox address and password empty → Test connection and continue. (Red
  message? Retype the DB password before trying again.)
- PART 5 Screen 3: my own username (not `admin`); business email (not personal Gmail); 12+
  character password in a password manager; WhatsApp `03…`; sample data ticked. If SMTP set: email
  box filled → Send test email → green box → retype the password twice → Create the shop.
- PART 5 Screen 4: SCREENSHOTS of the whole page; green "deleted itself" panel (or red → Delete
  install.php for me); opened `/config.php`, `/db/schema.sql`, `/storage/.htaccess`,
  `/app/bootstrap.php` on the phone — each an error page, never code; `/install.php` now shows the
  shop's page-not-found.
- PART 6: Admin → Settings → Advanced → HTTPS redirect = Permanent (301), or pressed **Make HTTPS
  permanent**.
- PART 6: `https://www.skyfragrances.com` lands on `https://skyfragrances.com` with the padlock.
- PART 6: hPanel → Emails shows no "DNS records missing" (or pressed Fix).
- SOFT LAUNCH: Press **Save** on each Settings tab BEFORE clicking the next tab.
- SOFT LAUNCH: Settings → Payments: real bank / JazzCash / Easypaisa details, OR those three
  switched off with only COD on — no REPLACE ME visible → Save Payments.
- SOFT LAUNCH: Products → tick all → **Hide** (or accept sample orders).
- SOFT LAUNCH: Settings → Advanced → **Let search engines index the shop**: OFF until real perfumes
  exist → Save Advanced.
- SOFT LAUNCH: Settings → Home: announcement bar changed → Save Home.
- SOFT LAUNCH: Settings → Contact & Social: WhatsApp, phone, Instagram, Facebook checked → Save
  Contact & Social.
- SOFT LAUNCH: Shop opened on the phone: loads, padlock, WhatsApp button opens WhatsApp.
- PERFUMES: Collections first.
- PERFUMES: At least 4 real perfumes with status **Live**, each with JPG/PNG/WEBP photos under 6 MB,
  portrait.
- PERFUMES: Tools → Remove sample data → Review and remove → typed `DELETE`.
- PERFUMES: Settings → Advanced → **Let search engines index the shop**: ON → Save Advanced.
- PERFUMES: One COD + one transfer test order moved through the statuses, then cancelled.
- PERFUMES: Search Console: URL prefix property → HTML tag → only the code between the quotes
  pasted into Settings → SEO → **Google site verification code** → Save SEO → Verify → sitemap
  `https://skyfragrances.com/sitemap.xml` submitted.
- PERFUMES (optional): Advanced → Cron Jobs → every 15 minutes →
  `/home/u…/domains/skyfragrances.com/public_html/cron.php` (Type PHP if offered).
- NEVER: switch Force HTTPS or the CDN on in hPanel; edit `.htaccess`, `.user.ini`, `app/`,
  `admin/`, `db/`; delete `storage/` or `uploads/`; share `config.php` or the install key; leave
  `install.php` on the server.

**How hPanel is laid out.** After you log in, click **Websites** in the top menu, find
`skyfragrances.com` and press **Manage** (or **Admin panel**). That opens the website dashboard.
Everything in this guide lives in its left-hand menu: **Files**, **Databases**, **Emails**,
**Domains**, **Security**, **Advanced**. Hostinger changes wording from time to time; if a label
in this guide is slightly different on your screen, look for the closest match.

---

## Part 1 — Before you upload anything

### Step 0. Make sure skyfragrances.com is a website on your hosting plan

1. Log in at hpanel.hostinger.com → **Websites**. If `skyfragrances.com` is in the list with a
   **Manage** button, skip to Step 1.
2. If it is not, press **Add website** (or **Set up** on your hosting plan). When hPanel asks
   what to build, choose **Empty website** / **Skip, I will upload my own files** — *not*
   WordPress and *not* Website Builder. When it asks for the domain, choose **Use an existing
   domain** → `skyfragrances.com`. Finish the wizard; it takes a minute and then shows the
   website dashboard.
3. If you ever see a page offering to "Install WordPress" or "Create with AI Builder" for this
   domain, close it. Nothing else needs installing — the shop is the ZIP.

### Step 1. Check the domain is connected

1. In the website dashboard, look at the top: it should say `skyfragrances.com` and show a green
   **Connected** (or **Active**) status. If it says the domain is not pointed to Hostinger, open
   **Domains** in the left menu and follow the prompt to use Hostinger's nameservers (the
   internet's address book entry that says where your site lives). Because you bought the domain
   at Hostinger, it already uses them and you should not see this prompt. If you do, wait — this
   can take from a few minutes to several hours.
2. Open `https://skyfragrances.com` on your phone. Before the shop is uploaded you should see a
   Hostinger placeholder page **with a padlock icon** next to the address. That padlock is the
   SSL certificate and Hostinger installs it automatically once the domain is connected.

If you see a "not secure" warning instead of a padlock, go to **Security → SSL** in the left
menu. Your domain should be listed as **Active**. If it shows **Install** or **Setup**, press it
and wait until it says Active (a few minutes). Do not upload the shop until the padlock shows.

**Why this order matters:** the shop's own files force https:// from the first second. If the
certificate is not active when you open `install.php`, your browser shows a red "Your connection
is not private" page with nothing to click through — you would have to wait for the certificate
anyway. Hostinger issues it within about 15 minutes of the domain being connected; the padlock on
the placeholder page is your signal to continue.

If two hours pass and Security → SSL still does not say Active, open hPanel's **Help**
(bottom-left) → live chat and write: "Please install the free SSL certificate on
skyfragrances.com." They do it while you wait. Do not buy a certificate.

### Step 2. Set the PHP version to 8.2

1. Left menu → **Advanced → PHP Configuration**.
2. Under **PHP version**, choose **8.2** (8.3 is also fine). Press **Update**.
3. Leave every other option on that page as it is. The shop brings its own settings file
   (`.user.ini`) with the right values.

### Step 3. Three things NOT to touch in hPanel

- **Security → SSL → Force HTTPS.** Look at the switch. If it is **on** (green), turn it
  **off** now and confirm. If it is already off, leave it. The shop forces https:// by itself,
  in the correct way for Hostinger's servers; Hostinger's switch writes a second, competing rule
  into the shop's files and can send visitors round in a loop. Check this switch again after
  Part 4 — if it turned itself back on, turn it off again.
- Do not install WordPress, the Website Builder, or any "LiteSpeed Cache" or "optimisation"
  plugin on this domain. The shop is not WordPress and those tools would break it.
- **Performance → CDN** (and any **cache** switch on that page, if your plan shows one) must be
  **off** for now. The shop serves its own pages and photos quickly enough; Hostinger's CDN can mix
  customers' carts up and confuses the shop's fraud protection. A developer can enable it later
  with the right settings.

---

## Part 2 — Create the database (write down three values)

The shop stores products, orders and settings in a MySQL database. You create an empty one;
the installer fills it.

1. Left menu → **Databases → Management** (it may be called **MySQL Databases**).
2. You will see a form titled **Create a New MySQL Database and Database User**. Fill in:
   - **MySQL database name**: type `skyfrag`. hPanel adds a prefix in front, so the full name
     becomes something like `u123456789_skyfrag`.
   - **MySQL username**: type `skyadmin`. It also gets the prefix: `u123456789_skyadmin`.
   - **Password**: press the generate button or type a long one. Copy it **now**.
3. Press **Create**.
4. The new database appears in the list below the form. **Write these three values in your
   notes exactly as shown**, including the `u…_` prefix:

   | Value | Example | Where you see it |
   |---|---|---|
   | Database name | `u123456789_skyfrag` | column "MySQL Database" |
   | Database username | `u123456789_skyadmin` | column "MySQL User" |
   | Database password | the one you just copied | not shown again — if you lose it, press the three dots next to the user and **Change password** |

   The **host** is `localhost` and the **port** is `3306`; the installer already has those.

Do **not** open phpMyAdmin (the tool that shows the raw database tables) and do not create any
tables. The installer does that in Part 5.

---

## Part 3 — Create the orders mailbox and note its settings

Order confirmations to customers and "new order" alerts to you are sent from a real mailbox on
your domain. Create it before running the installer so the installer can test it.

1. Left menu → **Emails**. If you see a list of domains, press **Manage** next to
   `skyfragrances.com`.

   If the Emails page shows a **Get started** / **Set up** / **Choose a plan** screen instead of
   a list of mailboxes, pick the free plan that is included with your hosting (it says *Free* or
   *Included*) and confirm with `skyfragrances.com`. Do not buy the paid Business/Enterprise
   tiers — the shop sends a handful of emails per order. The mailbox list appears afterwards.
2. Press **Create email account** (or **Create**).
   - **Email name**: `orders` — so the address is `orders@skyfragrances.com`.
   - **Password**: choose a long one and copy it into your notes as **mailbox password**.
   - Press **Create**.
3. Find the connection settings. In the Emails section look for **Connect apps & devices**,
   **Configuration settings**, **Manual configuration** or a similar link next to the new mailbox.
   You need the **outgoing (SMTP)** settings. On Hostinger they are:

   | Setting | Value |
   |---|---|
   | SMTP host | `smtp.hostinger.com` |
   | SMTP port | `587` (STARTTLS / TLS — the two names for the same secure sending mode; you never have to choose one). If Hostinger's screen shows `465` (SSL) instead, that also works — the installer picks the right encryption from the port. |
   | Username | `orders@skyfragrances.com` |
   | Password | the mailbox password |

   If the screen shows different values, write those down instead and use them in Part 5.
4. Optional but recommended: open **webmail** (the link is in the same Emails section) and log
   in once with the new address and password, so you know they work.

If your email is hosted somewhere else (for example Google Workspace), use *that* provider's
SMTP host, port, username and password instead. Hostinger's mail server cannot send for an
address it does not host.

---

## Part 4 — Upload the ZIP and unpack it into public_html

### Step 1. Open File Manager

1. Left menu → **Files → File Manager**. It opens in a new tab.
2. You land in a folder view. Double-click **public_html**. If you do not see it, open
   **domains** → **skyfragrances.com** → **public_html** instead. The address bar at the top of
   File Manager should now end in `/public_html`. This folder is the website: whatever is inside
   it is what the internet sees at skyfragrances.com.
3. Turn on hidden files. Look for the settings icon (a gear, usually top-right) and switch on
   **Show hidden files** (sometimes called "dotfiles"). Files whose names start with a dot, such
   as `.htaccess`, are invisible until you do this — and you need to see them in a moment.

### Step 2. Clear the placeholder

Inside `public_html` you will probably find a file called `default.php` (Hostinger's "website
coming soon" page), sometimes a `.htaccess` too. Select everything already inside `public_html`
and delete it (right-click → **Delete**). The folder should be empty.

### Step 3. Upload the ZIP — one level above public_html

Use the file named **`skyfragrances-public_html-folder.zip`**. It contains a folder called
`public_html` with the whole shop inside it. This matters: hPanel's extractor silently skips
hidden files (`.htaccess`, `.user.ini`) that sit at the top of a ZIP, but keeps them when they
are inside a folder — so the shop is packed one folder deep on purpose.

1. In File Manager go **up one level** from `public_html` (click the folder path at the top, or
   the `..` entry). You should now see the `public_html` folder itself in the list.
2. Right-click `public_html` → **Rename** → `public_html-old`. (Nothing is lost; you delete it
   once the shop works.)
3. Press the **Upload** icon (arrow pointing up, top-right) → **File** → pick
   `skyfragrances-public_html-folder.zip` and wait for the progress bar (13 MB, about a minute).

### Step 4. Extract it — here, one level above

1. Right-click the ZIP → **Extract**.
2. The small box shows the folder you are in (the one that contains `public_html-old`). Leave it
   as it is — do **not** type `public_html` into it. Press **Extract**. A fresh `public_html`
   folder appears next to `public_html-old` after 10–30 seconds.
3. Right-click the ZIP → **Delete**. If a folder called `__MACOSX` appeared, delete that too.
4. Double-click the new `public_html` to open it and continue with Step 5.

### Step 5. What public_html must look like now

With hidden files showing, `public_html` must contain **exactly these**, directly inside it:

```
public_html/
  .htaccess          ← hidden file, must be here
  .user.ini          ← hidden file, must be here
  admin/
  app/
  assets/
  config.sample.php
  cron.php
  db/
  favicon.ico
  index.php
  install.php
  robots.txt
  storage/
  uploads/
```

Open the `storage` folder and then the `uploads` folder: each must contain its own `.htaccess`
file. Go back to `public_html`.

**The one mistake to avoid — "nested public_html".** If instead you see a single folder called
`public_html` (or `skyfragrances`, or `site`) *inside* `public_html`, the ZIP was extracted one
level too deep. Fix: open that inner folder, select all its contents, right-click → **Move**,
set the destination to `/public_html`, confirm, then delete the now-empty inner folder. If you
get this wrong, `https://skyfragrances.com/install.php` shows a plain "404 Not Found" or
Hostinger's "coming soon" page instead of the dark installer — that is your signal to look for
the nested folder in File Manager and fix it there. Do not wait for an error from the installer:
at that moment the installer cannot be reached.

**If `.htaccess` or `.user.ini` are missing**, hidden files are probably still switched off
(Step 1). If they really are missing, the installer's first screen will say so: upload the two
files `htaccess.txt` and `user.ini` from the developer's `dist/htaccess-upload` folder into
`public_html`, then right-click each → **Rename** to `.htaccess` and `.user.ini` (with the
leading dot) and reload the installer.

**If you uploaded the folder instead of the ZIP.** Open `public_html` in File Manager with
hidden files showing. If `.htaccess` and `.user.ini` are missing: upload the two files
`htaccess.txt` and `user.ini` from the developer's `dist/htaccess-upload` folder into
`public_html`, then right-click each → **Rename** to `.htaccess` and `.user.ini` (with the
leading dot). The installer's Screen 1 will then say "Protection file .htaccess — present". Any
*other* missing file means the upload was incomplete — delete everything and use the ZIP.

Wait about five minutes before Part 5 (make a cup of tea). Hostinger re-reads the shop's
`.user.ini` settings file every five minutes; if you open the installer sooner, Screen 1 may show
"PHP memory limit" or "Upload size limits" with Hostinger's old values. If it does, simply wait
and reload — do not change anything in PHP Configuration.

---

## Part 5 — Run the installer (four screens)

Open **https://skyfragrances.com/install.php** in your browser. A dark page titled
"SKY FRAGRANCES · Installer" appears with four numbered steps across the top: **1 Check server ·
2 Database · 3 Admin & data · 4 Finish**.

### Screen 1 — Check server

A table of checks. Each row is marked **OK**, **Note** or **Problem**.

- **OK** rows need nothing.
- **Note** rows are information only. You may see *Pretty web addresses — could not be checked …
  does not point at this server yet* even though your domain is connected — Hostinger's servers
  sit behind a traffic router and the installer cannot always see its own public address (it
  tries a second, self-addressed check before giving up). Ignore it; it only means the
  *Security checks* on Screen 4 may say "skipped" and the HTTPS redirect may stay temporary,
  both of which this guide handles by hand (Screen 4 bullets and Part 6 Step 1). Read any
  *other* Note and do what it says later (for example "test /shop in your browser after
  installing").
- **Problem** rows stop the installer. Each one says in plain words what to do. The common ones:

  | Problem row | What to do |
  |---|---|
  | PHP version … needs PHP 8.2 | Part 1, Step 2. Then reload. |
  | Folder storage/… cannot be created or written | In File Manager right-click the folder → **Permissions** → set to 755 → reload. |
  | Protection file .htaccess missing | Hidden files were dropped. Re-upload and re-extract the ZIP (Part 4). |
  | Nested public_html | The ZIP was extracted one level too deep — see the fix in Part 4, Step 5. |
  | Sub-folder install | The files are not directly inside `public_html`. Move them up (same fix). |
  | Upload size limits / PHP memory limit | Advanced → PHP Configuration → PHP Options: `memory_limit` at least 128M, `upload_max_filesize` and `post_max_size` at least 6M. Hostinger's defaults already satisfy this. |

  You may also see a **Note** saying `default.php` is still in public_html — delete it in File
  Manager (Part 4, Step 2). A Note saying the PHP version is *tested on 8.2 and 8.3* means Part 1
  Step 2 was skipped or Hostinger put you on 8.4; go back and choose 8.3, then reload.

When there are no Problem rows, press **Continue**.

### Screen 2 — Database

The first time you open this screen it starts with a box titled **Prove you own this hosting
account**. This is a safety lock: it stops a stranger who finds `install.php` from pointing your
shop at their own database.

1. The box says the installer wrote a one-time key to `storage/.install-key`. In the File
   Manager tab, open `public_html → storage`, make sure hidden files are showing, and open the
   file `.install-key` (right-click → **Edit** or **View**). It contains one line of 32 letters
   and numbers. The file is created the moment this screen opens, so if the storage folder was
   already open in File Manager and shows no `.install-key`, press File Manager's **refresh**
   (circular arrow) icon — or open a different folder and come back — and it appears.
2. Copy that line into the **Install key** field on the installer. You only do this once; the
   installer deletes the key file when it finishes.

   If you take a long break and the *Prove you own this hosting account* box appears again on
   Screen 3, the key has **not** changed — paste the same 32 characters from
   `storage/.install-key` again. The file is only deleted when the installation finishes.

Then fill in the rest of the form from your notes (Part 2 and Part 3):

| Field | What to enter |
|---|---|
| Database host | `localhost` (already filled) |
| Database port | `3306` (already filled) |
| Database name | your `u…_skyfrag` value, exactly |
| Database username | your `u…_skyadmin` value, exactly |
| Database password | the database password |
| **Shop address** | Leave the pre-filled value. It should read `https://skyfragrances.com`. The installer fixes the spelling if you typed `www.` |
| **Email sending (optional)** | To send order emails: SMTP host `smtp.hostinger.com` (already filled), SMTP port `587` (or `465` if Hostinger's screen shows that — the installer picks the encryption from the port), Mailbox address `orders@skyfragrances.com`, Mailbox password (Part 3), Sender name `Sky Fragrances`. **To skip email for now: leave Mailbox address and Mailbox password empty.** The pre-filled host is then ignored, the shop saves order emails on the server instead of sending them, and a developer can add the mailbox later (Part 10). Do not enter a host with only half the details. |

Press **Test connection and continue**. Nothing is saved until the database answers.

If you see a red message:

- **"The install key did not match"** — copy the key again from `storage/.install-key`; make
  sure there is no space before or after it.
- **"Could not connect to the database … Access denied"** — the name, username or password is
  wrong. Check them in hPanel → Databases → Management. If the password is lost, use the three
  dots next to the user → **Change password**, then enter the new one here.
- **"This database server is … The shop needs MySQL 8.0 or MariaDB 10.4 or newer"** — contact
  Hostinger support; every current plan meets this.
- **"…this database user is not allowed to create tables"** — in Databases → Management the user
  must have **All privileges** on this database (three dots next to the user → **Permissions**).
- **"Create config.php by hand"** — very rare: the installer shows the exact text of a file
  called `config.php` and asks you to create it in File Manager (New file → paste → save). Then
  press the Continue link on that screen. After the shop is installed, Screen 4 may then show
  *config.php locked — could not be made read-only*; in that case right-click `config.php` in
  File Manager → **Permissions** → type `400` → save.

Whenever Screen 2 comes back with a red message, the **Database password** box is empty again —
type it in once more before pressing the button.

When the connection succeeds the installer writes `config.php` and moves to Screen 3.

### Screen 3 — Admin account & sample data

This creates the one account you will use at `/admin`.

| Field | What to enter |
|---|---|
| Admin username | The box is empty on purpose — type your own, for example `sky.owner`. Do **not** use `admin`: it is the first name an attacker tries and makes the lock-out protection much weaker. Letters, numbers, dots, dashes, underscores; 3 to 64 characters. |
| Your email address | Use a business address you are happy to show to the public, for example `orders@skyfragrances.com` (Part 3) or `info@…`. It appears on the Contact page and receives the **New order** alerts. Not your personal Gmail — you can change both later in Settings → Contact & Social, but it is live from the first minute. |
| Admin password / Repeat | At least 12 characters. Use a password manager or write it down safely — there is no "forgot password" email. |
| Store name | `Sky Fragrances` |
| WhatsApp number | The number customers will message. Type it the way it is dialled inside Pakistan: `0300 1234567` (`+92 300 1234567` and `0092 300 1234567` are understood too). Do not use a non-Pakistani number here — it is always stored as +92…. You can change it later in Settings → Contact & Social. |
| Install 12 sample perfumes… | Leave **ticked**. You will remove the samples from **Tools** once your own perfumes are in (Part 7). They let you see and test a full shop straight away. |

If you entered a mailbox on Screen 2 there is a second button, **Send test email to the address
above**. Fill in **Your email address** first, then press it: a green box confirms it was sent. It goes **to the address in "Your
email address"** on this screen — if that is `orders@skyfragrances.com`, open Hostinger webmail
(Part 3, point 4) to look for it; otherwise check your inbox and spam on your phone. A red box
means the mailbox password or host is wrong; press **Back**, then **Change database details**, and
re-enter the email settings.

After the test (and after any red message on this screen) both password boxes are empty again.
Type the admin password twice more before pressing **Create the shop**; everything else you typed
is still there.

Press **Create the shop**. This takes a few seconds: the tables are created, the sample data is
loaded, your account is created, protective files are checked and the shop's settings are
written.

If it stops with a red message such as **"Creating the database tables stopped at statement …"**
or **"adding the sample data stopped at statement …"**, take a screenshot of the whole message
and send it to the developer. You can safely reload and try again; the installer tells you when
it will replace an unfinished attempt (a tick box appears on this screen) and never touches a
finished installation.

### Screen 4 — Finish

**Screenshot this whole page before you close or reload it** (scroll and take two or three
screenshots). On skyfragrances.com the installer deletes itself the moment this page appears, so
the report cannot be opened a second time — a reload shows the shop's "page not found". The page
itself carries a red reminder saying the same. Send the screenshots to the developer; they are the
record that the installation was clean.

You will see one of two panels at the top:

- **Green — "Your shop is installed and install.php has deleted itself."** This is the normal
  result on skyfragrances.com. Nothing to clean up.
- **Red — "Delete install.php now."** The shop is installed but the installer could not remove
  itself. Press **Delete install.php for me**, then **Check whether it is gone**. If it still
  will not go, open File Manager → public_html and delete `install.php` by hand. Do this before
  anything else: while that file exists, anyone who opens it can read where your database is.

Below the panel:

- **Installation report** — a table of what was done: tables, sample data, admin account, the
  lock file, folders, protection files, search-engine setting, HTTPS redirect, and
  "config.php locked — read-only". Any **Note** here is worth reading; for example, if HTTPS
  could not be confirmed yet, the redirect stays temporary and Part 6 fixes it.
- **Security checks** — the installer asked your own website whether its private files are
  hidden. Rows marked **Note** could not be checked from the server: open each address it names
  on your phone (for example `https://skyfragrances.com/config.php`). Each must show an **error
  page**, never file contents. If any shows readable code, stop and call the developer.
- **Next steps** — a short list; this guide covers all of them.

Finally, open **https://skyfragrances.com/install.php** in a new tab. It must show the shop's
"page not found" page. If it shows the installer again saying **"already installed — delete
install.php"**, press its **Delete install.php for me** button (or delete the file in File
Manager). The installer will never re-run against a finished installation, but the file must go.

---

## Part 6 — HTTPS and email deliverability

### Step 1. Make the HTTPS redirect permanent

The shop already sends every `http://` visitor to `https://`. At install time it does this with
a *temporary* redirect so that nothing gets stuck in browsers if the certificate was not ready.
Once the padlock shows, make it permanent:

1. Open **https://skyfragrances.com/admin** and log in (Part 7 explains the panel; this is one
   button).
2. Left menu → **Settings** → the **Advanced** tab. Find **HTTPS redirect**. If it shows
   **Permanent (301)** you are done. If it shows **Temporary (302)**, press **Make HTTPS
   permanent** and confirm. The button first checks that `https://skyfragrances.com` answers with
   a valid certificate and refuses if it does not — in that case wait an hour and try again.
3. The same status is shown on the **Tools** page.
4. Once, on your phone, open `https://www.skyfragrances.com`. It must land on
   `https://skyfragrances.com` with the padlock — the shop removes the `www.` itself.

Remember Part 1, Step 3: hPanel's own **Force HTTPS** switch (Security → SSL) must stay **off**.

After a month of stable https, ask the developer to add the HSTS line (a header that makes
browsers remember to use https:// for your shop for a year, so they never even try http://) to
`.htaccess` — you cannot do this yourself, and it must not be done before the certificate has proven reliable.

### Step 2. Let email arrive in inboxes (SPF and DKIM)

Mail servers accept email from `orders@skyfragrances.com` only if your domain's DNS says
Hostinger is allowed to send for it. Two records do this: **SPF** and **DKIM**. When your domain
uses Hostinger's nameservers, Hostinger adds them for you — but check:

1. Left menu → **Emails** → **Manage** next to skyfragrances.com. If Hostinger shows a warning
   such as **"DNS records are missing"** or **"Email is not set up correctly"**, press the button
   it offers (**Fix records**, **Add DNS records** or **Set up**). It adds the MX, SPF and DKIM
   records automatically. Wait up to an hour.
2. To look for yourself: left menu → **Domains → DNS / Nameservers** (or **Advanced → DNS Zone
   Editor**). You should find a **TXT** record whose value starts with
   `v=spf1 include:_spf.mail.hostinger.com` and **CNAME** records whose names start with
   `hostingermail-a._domainkey`, `hostingermail-b._domainkey`, `hostingermail-c._domainkey`.
   If they are there, you are done.
3. Test: place a small order later (Part 8) using a Gmail address as the customer, and check
   that the confirmation lands in the inbox, not in spam. Until this is confirmed, WhatsApp is
   your reliable channel to customers — every order screen in the panel has a one-tap WhatsApp
   button.

---

## Part 7 — Your first hour in the admin panel

### Step 1. Log in

1. Open **https://skyfragrances.com/admin**. Enter the username and password you chose on
   installer Screen 3 (the installer's link to `/admin/login` is the same page).
2. The panel remembers the browser you used. If you later log in from a laptop or a different
   phone, you simply log in again there — nothing else is needed.
3. Five wrong passwords in a row slow the form down; ten lock that connection out for 15
   minutes. The lock clears by itself.

The **Dashboard** shows today's and this month's revenue, orders by status, low-stock alerts,
the latest orders and best sellers. Two yellow banners are normal on day one:
*"This store is still showing sample data"* (Part 7, Step 6 removes it) and — if you opened the
panel from an address different from the site address — a note pointing at Settings → Advanced.

### Soft launch — if you want the address to work before the real perfumes are in

You may share the address before everything is ready, but do these four things first (ten
minutes). Without them a visitor can order a sample perfume that does not exist, the transfer
checkout shows `0000-0000000-000 (REPLACE ME)` as your account number, and Google starts listing
"Azure Oud" under your brand.

1. **Payments** — Settings → Payments: fill in your real bank, JazzCash and Easypaisa details
   (Step 3). If you cannot yet, switch **Bank transfer**, **JazzCash** and **Easypaisa** off and
   leave only **Cash on Delivery** on — the placeholder account numbers must never be shown to a
   customer.
2. **Hide the sample perfumes** — Products → tick all → bulk action **Hide**. The shop then
   shows your hero, collections and quiz, and the Shop page says nothing is available until you
   add real products. (Or leave them visible and accept that someone may order a sample you must
   then cancel with an apology — your choice, but say so in the WhatsApp reply.)
3. **Keep Google out until the catalogue is real** — Settings → Advanced → **Let search engines
   index the shop: off**. Switch it back on in Part 9.
4. **Announcement bar** — Settings → Home: change the text to something like *"Launching soon —
   follow us on Instagram for the first drop"* so visitors know the catalogue is coming.

Then do Parts 7 and 8 at your own pace and, when done, flip indexing on and remove the sample
data (Step 6).

### Step 2. Change your password (optional, recommended if anyone watched you type it)

Left menu → **Change password** (bottom of the menu). Enter the current password and the new
one twice (12+ characters). You stay logged in.

### Step 3. Replace the placeholder payment details

Left menu → **Settings** → **Payments** tab. Everything marked **REPLACE ME** is a placeholder
and must be replaced before customers see the checkout:

| Setting | Replace with |
|---|---|
| Bank transfer: Bank name, Account title, Account number, IBAN | Your business account. The IBAN is shown to customers exactly as typed. |
| JazzCash: Account title, Number | Your JazzCash business or personal account. |
| Easypaisa: Account title, Number | Your Easypaisa account. |
| Note shown for transfer payments | The sentence customers see under the account details ("Send your payment screenshot and transaction ID…"). Keep or adjust. |
| Cash on Delivery: enabled, **COD note at checkout**, **COD limit** | `0` means no limit. Set a limit (for example 15000) if you do not want COD on very large orders — above it customers must pay in advance. |
| Hours to hold an unpaid transfer order | How long an unpaid bank/JazzCash/Easypaisa order waits before it appears on the **Orders → Cancel unpaid** list. |
| Enable/disable each method | Switch a method off to hide it at checkout. At least one must stay on. |

Press **Save Payments**. The dashboard's payment warning disappears when no REPLACE ME text remains.

### Step 4. Contact details, socials, logo, texts

Still in **Settings**, go through the other tabs. Each tab is a separate page with its own
**Save …** button at the bottom. Press it **before** you click another tab — moving to another tab
without saving throws away what you typed, with no warning.

- **Store** — store name, tagline, **logo** (upload a PNG with a transparent or black
  background), favicon, address line, footer text.
- **Contact & Social** — phone, **WhatsApp number** (used by every WhatsApp button on the site),
  contact email, the email that receives **New order** alerts, business hours, Instagram,
  Facebook, TikTok and YouTube links (leave a link empty to hide its icon), and the WhatsApp
  reply template used when you answer contact messages.
- **Home** — the announcement bar text and link, hero heading / sub-heading / button, hero
  images for desktop and mobile, the For Him / For Her / Unisex tile images, newsletter text,
  and six optional Instagram tiles (image + link each).
- **Shipping** — delivery fee (`250`), free-delivery threshold (`3000`), delivery time text.
- **SEO** — the site description shown in Google results, the default social-preview image,
  Google Search Console verification code.
- **Advanced** — the site address (`https://skyfragrances.com`), **Let search engines index the
  shop** (must be on for the live shop; the installer switches it on automatically when the
  address is skyfragrances.com — switch it off only for the soft launch and back on when the real
  perfumes are in), maintenance mode and its preview key, HTTPS redirect, low-stock
  alert level, rows per page.

### Step 5. Add your perfumes

Do this **before** removing the sample data: the home page only shows its product rows ("best
sellers", "new arrivals") once at least **four** perfumes are live. With fewer than four, the home
page shows the hero, collections and quiz sections and the **Shop** page lists whatever exists.

**Collections first.** Left menu → **Collections → Add collection**: name, a one-line tagline,
an image (landscape, at least 1600×900), and whether it shows on the home page. Create your real
collections now so each perfume can be filed under one. The five sample collections can be
deleted later with the sample data (a collection that still contains one of your products is
kept automatically).

**Products → Add product.** The form has these parts:

1. **Basics** — *Name* (for example `Azure Oud`); *Link name* is filled in for you from the name
   (it becomes the web address `/product/azure-oud`, leave it unless you have a reason);
   *Collection*; *Gender* (For him / For her / Unisex); *Scent family* (Fresh & Citrus, Floral,
   Amber & Spice, Oud & Smoke, Green & Earthy — the list is editable under Products →
   Scent families).
2. **Descriptions** — a *Short description* (one sentence, up to 200 characters, shown on cards)
   and the full *Description*.
3. **Notes pyramid** — *Top*, *Heart* and *Base* notes as comma-separated lists
   (`Bergamot, Pink pepper`). They draw the pyramid on the product page and feed the Scent Finder.
4. **Character** — *Longevity* and *Sillage* on a 1–5 scale, *Best season* and *Occasion* tick
   boxes.
5. **Sizes** — one row per bottle size: *Label* (`50ml`), *Millilitres*, *SKU* (your own stock
   code for that bottle, for example `AZ-50`; suggested for you; keep it unique), *Price* in rupees (`5450`), optional *Sale price* (must be lower than
   the price — the shop shows the price crossed out), *Stock* (how many bottles you have) and
   *Low-stock alert at*. Press **+ Add size** for a second size and choose which one is the
   **default** shown first. Stock goes down automatically with every order and can never go
   below zero; a cancelled order puts it back.
6. **Visibility** — *Featured on the home page*, *Show the NEW badge*, *Status* (**Live** or
   **Hidden**), *Publish date*, *Sort order*.
7. **SEO** — *SEO title* (up to 60 characters) and *SEO description* (up to 155). If left empty
   the shop composes sensible ones.

Press **Create product** (or **Save & add another**). The page reopens — now with a **Photos** box (when you edit a product later, the button reads **Save**):

- Press **Choose photos** and pick one or several JPG, PNG or WEBP files, up to 6 MB each. Each
  photo is resized on the server to the sizes the shop needs and converted to fast WebP; you do
  not need to prepare sizes yourself. Photos are cropped to a 4:5 portrait — shoot upright.
- The first photo is the main one, shown on cards and in search results. Each photo has its
  own controls to make it the main one, edit its short description (for screen readers and
  Google) or remove it, and you can drag photos into a different order.
- Open **View on site** to check the product page on your phone.
- **iPhone users:** photos taken with the default settings are HEIC files, which the shop does
  not accept (the picker greys them out or the upload is refused). Either set **Settings →
  Camera → Formats → Most Compatible** before shooting, or share each photo to yourself as a
  JPEG (Photos → Share → Options → *Most Compatible*). Android phones already save JPG.

Repeat for every perfume. Tip: the list at **Products** has a **Hide** / **Make live** action on
every row, plus **Duplicate** for a variant that shares most details; the edit page has the same
button as **Hide from shop** / **Make live**.

### Step 6. Remove the sample data

When your own perfumes are in (at least four with status **Live**):

1. Left menu → **Tools**. The **Sample data** card says *Still present* and lists what will go:
   12 products, 5 collections, 3 coupons, 8 reviews.
2. Press **Review and remove**. Read the two lists — **Will be deleted** and **Will be kept**.
   Anything a customer has already bought is kept (hidden from the shop, still on the order),
   a sample collection that contains one of your products is kept, and the placeholder bank /
   JazzCash / Easypaisa text is **not** touched (you replaced it in Step 3).
3. Type `DELETE` in the box and press the button. A green line reports exactly what was removed;
   the dashboard banner disappears.

The sample reviews are always deleted here — they were installed unapproved, so no customer ever
saw them, and the shop must never show reviews that are not real.

### Step 7. Coupons

Left menu → **Coupons → New coupon**: code (customers type it at the cart), percent or fixed
amount, minimum order, usage limit, expiry date, on/off. The sample `WELCOME10` (10 % above
Rs. 3,000) is removed with the sample data; create your own launch code if you want one.

---

## Part 8 — Place a real test order with every payment method

Do this from your phone, as a customer would, before you announce the shop. Use a real phone
number you can read WhatsApp on and, for one order, a Gmail address to check email delivery.

### Cash on delivery

1. Open the shop, choose a perfume and size, **Add to cart**, open the cart, **Checkout**.
2. Fill in name, phone, city, address; leave the payment method on **Cash on Delivery**; **Place
   order**. You land on the confirmation page with an order number like `SF-260928-RF2H`.
3. Open **/track** on the site, enter the order number and the phone number: the status timeline
   shows *Pending*.
4. In the panel: **Orders** → the order is listed → open it. Press **Mark confirmed**. Then
   **Mark packing**. Then **Mark shipped…** — the panel asks for the **courier name** first (TCS,
   Leopards, M&P…) and an optional tracking number; press **Save & mark shipped**. Then
   **Mark delivered**. Each step has a **Send "…"
   message** WhatsApp button with the text pre-written; press one to see it open WhatsApp.
5. Open **Invoice** and **Packing slip** from the order page: both print from the phone.

### Bank transfer, JazzCash and Easypaisa (one order each)

1. Checkout as above but choose **Bank transfer** (then JazzCash, then Easypaisa). The page
   shows the account details you entered in Settings → Payments and asks for the **Transaction
   ID**, the **Sender name** and a **Payment screenshot**. Upload any image for the test.
2. The confirmation page says the payment is **awaiting verification**.
3. In the panel, open the order: the screenshot is shown under **Payment proof**. Press **Mark as
   paid** once you have checked it in your banking app, then move it through Confirmed →
   Packing → Shipped → Delivered as before. Two more WhatsApp texts exist here: **Request payment
   proof** and **Payment not verified**.
4. When a transfer order stays unpaid past the hold time from Settings, the **Orders** page shows
   a notice with a **Review and cancel them** link (the page is `/admin/orders/cancel-unpaid`);
   cancelling from there puts the stock back.

### Cancel one and check stock

Open one of your test orders and press **Cancel this order — permanent** and give a reason. Then open that perfume under
**Products** — its stock count is back to what it was. If an order is cancelled *after* it was
shipped, stock returns only when you press **Parcel received back** on the order (the **Tools →
Stock-back audit** card lists any such orders).

### Check the emails

Each order queues two emails: the confirmation to the customer and a **New order** alert to the
address in Settings → Contact & Social. They are sent from the `orders@` mailbox whenever you
open the admin panel (a couple per page load), so open the **Dashboard** after placing the test
orders and look at your inbox. **Tools → Emails not yet sent** shows anything still waiting or
failed. If everything stays *waiting*, the mailbox details need checking (Part 10).

If you want the emails to go out even when nobody has the panel open (recommended once real
orders arrive): left menu → **Advanced → Cron Jobs**. If the **Type** menu offers **PHP**, choose it
and enter the file path; otherwise choose **Custom** and enter the full command. Interval: **every
15 minutes** (`*/15 * * * *`). The path is
`/home/u123456789/domains/skyfragrances.com/public_html/cron.php`, where `u123456789` is the same
`u…` number hPanel put in front of your database name in Part 2 (also shown as **Username** under
Hosting → Plan details). For the Custom type the command is `php` followed by a space and that
path. Press **Save**. No key is needed when it runs this way; if the job is ever set up as a web
address instead, it needs `?key=` with the `cron_key` from `config.php`. If Hostinger emails you
"PHP Parse error" from the job, switch the Type to **PHP**.

### Tidy up

Delete your test orders' stock effects by cancelling them, or leave them: cancelled orders do not
count in revenue. Test orders cannot be deleted outright — that is deliberate, the order history
is your accounting record.

---

## Part 9 — Going public

- **Announce only after** Part 7 Step 3 (payment details) and Step 6 (sample data removed) are
  done and **Settings → Advanced → Let search engines index the shop** is on.
- **Google.** Google Search Console is Google's free tool for telling it about your site
  (search.google.com/search-console; any Google account). Press **Add property** → **URL prefix**
  → type `https://skyfragrances.com` → under *Other verification methods* pick **HTML tag**. It
  shows `<meta name="google-site-verification" content="AbC123…" />` — copy **only the letters
  between the quotes after `content=`**, not the whole line. In the panel open **Settings → SEO**,
  paste it into **Google site verification code**, press **Save SEO**, then press **Verify** in
  Search Console. Finally open **Sitemaps** there and submit `https://skyfragrances.com/sitemap.xml`.
  The sitemap updates itself.
- **Social previews.** Share a product link on WhatsApp: the image, title and price come from the
  product's photo and SEO fields.

---

## Part 10 — When something goes wrong

### During installation

| What you see | What it means | What to do |
|---|---|---|
| Hostinger's "website coming soon" page or a plain "404 Not Found" instead of the installer | `default.php` is still in `public_html`, or the files are in a sub-folder / nested `public_html` | Part 4, Steps 2 and 5. The installer cannot tell you this itself — it is not reachable at that moment. |
| A completely blank white page, or "HTTP ERROR 500" / "This page isn't working", when opening `install.php` | The account is still on an old PHP version, so the installer cannot even start | Part 1, Step 2: Advanced → PHP Configuration → 8.2 → Update. Wait a minute and reload. |
| "This form has expired" on any installer screen | You were on the page for a long time or opened it twice | Press **Start again**; nothing was changed. |
| "The install key did not match" | Wrong or partial key | Copy the whole line from `storage/.install-key` again. |
| "Could not connect to the database … Access denied" | Wrong database name, user or password | Check Databases → Management; reset the user's password if needed. |
| "The database is not empty" | You pointed the installer at a database that already has tables | Create a new empty database (Part 2) or, if it is an old copy of this shop you do not need, empty it in phpMyAdmin first. The installer never deletes tables. |
| "An earlier attempt did not finish" | A previous run stopped half-way | Continue; tick the box on Screen 3 that allows the unfinished tables to be replaced. |
| "config.php exists but its database does not answer" | The database was deleted or its password changed after installing | Recreate/repair the database in hPanel, or delete `config.php` in File Manager and run the installer again with the new details. |
| "The installer hit an unexpected problem" | Something unusual on the server | Reload once. If it repeats, screenshot the message for the developer. |
| Red "Delete install.php now" panel after installing | The shop is fine; the file could not remove itself | Press **Delete install.php for me**, or delete it in File Manager. |

### After installation

| What you see | What to do |
|---|---|
| A black page saying **"Something went wrong"** with a short **incident code** | The shop hit an error and logged it. Note the code and the time, tell the developer. Customers see a polite page, never technical details. |
| **"We are making a few improvements…"** on the whole site | Maintenance mode is on. Turn it off in Settings → Advanced, or delete the file `storage/MAINTENANCE` in File Manager if the panel itself is closed. While it is on, you can preview the shop by typing the **preview key** (Settings → Advanced) on the closed page. |
| **"This page has expired"** / "form expired" when saving | The browser tab was open for a long time. Reload the page and repeat the action. |
| **"Too many failed attempts. Try again in 15 minutes."** on the login | The lock clears by itself. Wait, then use the right password. |
| Product photos missing or broken after a re-upload | **Tools → Regenerate all images**. It rebuilds every size from the largest copy and keeps going in the tab until done. |
| Order emails not arriving | Open **Tools**: if emails show as *failed*, the mailbox password or host is wrong (see "Changing the mailbox password" below). If they show as *waiting*, open the Dashboard a couple of times. If they arrive in spam, do Part 6 Step 2. |
| `/shop` or `/product/…` shows "page not found" on the server's own error page (not the shop's black one) | The `.htaccess` file is missing from `public_html`. Re-extract the ZIP (Part 4) — extraction does not touch your photos, orders or settings. |

### Forgotten admin password

There is no "forgot password" email by design (the shop must not depend on email working). The
reset is done with a small tool that ships inside the shop, in ten minutes, and it asks you to
prove you can reach the server's files before it changes anything:

1. hPanel → **Files → File Manager** → `public_html` → `app` → `tools`. Right-click
   `reset-password.php` → **Copy** → destination `/public_html` → confirm. (Copy, not move — the
   original stays in `app/tools` for next time.)
2. Open **https://skyfragrances.com/reset-password.php** in your browser. It shows a file name
   like `reset-3f9a1c…` (16 letters and numbers).
3. Back in File Manager open `public_html` → `storage` (hidden files on), press **New file**,
   name it exactly as shown, leave it empty and save.
4. Reload the browser tab. It now shows **Choose a new admin password**: type a new password of
   12+ characters twice and press the button. Your account is unlocked at the same time (any
   lock-out from the wrong guesses is cleared).
5. The tool deletes itself and the file you created. Reload the address once more: it must show
   the shop's page-not-found. If it still shows the tool, delete `public_html/reset-password.php`
   in File Manager by hand. Then log in at `/admin`.

Only the browser that first opened the tool can finish the reset; if another device opened it,
or you took more than an hour, delete `storage/.reset-pending` and the `storage/reset-…` file in
File Manager and start again from step 2. The tool refuses to run if the shop somehow has more
than one admin account — in that case, or if anything else goes wrong, use the database method
below or call the developer.

**Fallback — in the database (only if the tool cannot be used).**

1. hPanel → **Databases → phpMyAdmin** → **Enter phpMyAdmin** next to your database.
2. In the left list click the table **admin_users**. You see one row — your account.
3. Press **Edit** (pencil) on that row. In the field **password_hash**, delete the contents and
   paste exactly this line:

   ```
   $2y$12$CWA5M0iI/WqQlVhMb7m4eONj8V9jB8ZlnaLID3xwLIOm8OUzfjPRO
   ```

   Leave the **Function** dropdown next to the field empty (blank). Do not tick any box or
   choose MD5/PASSWORD — paste the line exactly and press **Go**. Also check that the field
   **is_active** in the same row is `1`. Your password is now temporarily `Reset-Sky-2026-Temp!`
4. Still in phpMyAdmin, click the table **admin_login_attempts** and, if it has rows, use
   **Empty** (Operations → Empty the table) so a lock-out from the forgotten password is cleared.
5. Log in at `/admin` with the temporary password, then **immediately** go to **Change
   password** and set your own. The temporary password is printed in this guide, so it must not
   stay in use.

### Changing the mailbox password (or adding email later)

The email settings live in `public_html/config.php`, a file the installer locks read-only so
nothing on the site can change it. To edit it:

1. File Manager → `public_html` → right-click `config.php` → **Permissions** → set to `0600`
   (owner read and write) → save.
2. Right-click `config.php` → **Edit**. Find the block that starts with `'smtp' =>`. Change the
   value after `'pass' =>` (keep the quotes) — and `'host'`, `'user'` if you are adding email
   for the first time (`smtp.hostinger.com`, `orders@skyfragrances.com`). Save.
3. Set the permissions back to `0400`.
4. Place a small test order or open the Dashboard: the waiting emails go out.

If the site shows a black error page right after you save, you removed a quote mark or comma.
Press **Edit** again and compare the block with `config.sample.php` in the same folder — every
value sits between two quotes and every line ends with a comma.

Everything else the shop needs is in **Settings**; `config.php` holds only the database and
mailbox connection and two random security keys. Never share it.

---

## Part 11 — Updating the shop later

When the developer sends a new ZIP:

1. **Settings → Advanced → Maintenance mode: on.** Customers see a polite "back shortly" page;
   you can still preview with the preview key.
2. Back up first (Part 12).
3. File Manager → `public_html` → **Upload** the new ZIP → right-click → **Extract** into
   `public_html` (same as Part 4). Existing code files are replaced; your `config.php`, your
   photos in `uploads/`, and everything in `storage/` are **not** in the ZIP and stay as they
   are. Delete the ZIP afterwards.
4. The ZIP contains a fresh `install.php`. It refuses to run against an installed shop, but
   **delete it** anyway (right-click → Delete).
5. Open **Settings → Advanced**: if **HTTPS redirect** says *Temporary (302)* again, press **Make
   HTTPS permanent**. (The new ZIP's files reset it.)
6. Check the shop on your phone, then **Maintenance mode: off**.

If the developer says the update includes database changes, they will give you a one-time
address to open or do it for you; the ZIP alone never changes the database.

---

## Part 12 — Backing up

Hostinger keeps automatic weekly backups (and daily on higher plans), but take your own before
any update and every month or so:

1. **Files:** hPanel → **Files → Backups** → **Files backups** → **Select** the latest → **Download
   all files** (or **Generate new backup** first). The important folders are `uploads/` (photos),
   `storage/proofs/` (customers' payment screenshots) and `config.php`.
2. **Database:** on the same Backups page → **Database backups** → your database → **Download**.
   Or: **Databases → phpMyAdmin** → **Export** → **Go**, which downloads a `.sql` file.
3. Keep both files together, named with the date, somewhere off the server (your computer plus
   a cloud drive).

To restore, a developer imports the `.sql` in phpMyAdmin and re-uploads `uploads/` and
`storage/proofs/` through File Manager.

---

## Quick reference

| Where | Address |
|---|---|
| Shop | https://skyfragrances.com |
| Admin panel | https://skyfragrances.com/admin |
| Order tracking for customers | https://skyfragrances.com/track |
| Sitemap (for Google) | https://skyfragrances.com/sitemap.xml |
| Hostinger control panel | https://hpanel.hostinger.com |

| Never do this | Why |
|---|---|
| Switch on **Force HTTPS** in hPanel | The shop already redirects; two rules can loop. |
| Switch on **Performance → CDN** or a cache in hPanel | Customers can see each other's cart; the shop's own caching is already on. |
| Leave `install.php` on the server | It reveals server details. It refuses to re-run, but delete it. |
| Edit `.htaccess`, `.user.ini` or anything in `app/`, `admin/`, `db/` | These are the shop's engine. Everything you control is in Settings. |
| Delete `storage/` or `uploads/` | Sessions, payment screenshots and photos live there. |
| Share `config.php` or the install key | They give access to your database and mailbox. |
