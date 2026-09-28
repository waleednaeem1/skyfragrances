# Sky Fragrances — Go-Live Guide

This guide takes the shop from a ZIP file to a live website at **https://skyfragrances.com**, using
only the Hostinger control panel (hPanel) and your phone or laptop browser. No technical knowledge
is needed. Follow the steps in order; each one tells you what you should see before you move on.

Time needed: about 45 minutes for the technical part (Parts 1–6), then as long as you like for
adding your perfumes.

**Have these ready before you start**

- Your Hostinger login (hpanel.hostinger.com).
- The ZIP file from the developer, named like `skyfragrances-20260928-1500.zip` (about 14 MB).
  Keep it somewhere you can find it, for example your Downloads folder. Do **not** unzip it on
  your computer — hPanel will unzip it on the server.
- A notes app open. You will write down three database values and one mailbox password.
- Your bank, JazzCash and Easypaisa account details, your WhatsApp number, your logo file, and
  your perfume photos (portrait orientation works best — the shop crops every photo to 4:5).

**How hPanel is laid out.** After you log in, click **Websites** in the top menu, find
`skyfragrances.com` and press **Manage** (or **Admin panel**). That opens the website dashboard.
Everything in this guide lives in its left-hand menu: **Files**, **Databases**, **Emails**,
**Domains**, **Security**, **Advanced**. Hostinger changes wording from time to time; if a label
in this guide is slightly different on your screen, look for the closest match.

---

## Part 1 — Before you upload anything

### Step 1. Check the domain is connected

1. In the website dashboard, look at the top: it should say `skyfragrances.com` and show a green
   **Connected** (or **Active**) status. If it says the domain is not pointed to Hostinger, open
   **Domains** in the left menu and follow the prompt to use Hostinger's nameservers. Then wait —
   this can take from a few minutes to several hours.
2. Open `https://skyfragrances.com` on your phone. Before the shop is uploaded you should see a
   Hostinger placeholder page **with a padlock icon** next to the address. That padlock is the
   SSL certificate and Hostinger installs it automatically once the domain is connected.

If you see a "not secure" warning instead of a padlock, go to **Security → SSL** in the left
menu. Your domain should be listed as **Active**. If it shows **Install** or **Setup**, press it
and wait until it says Active (a few minutes). Do not upload the shop until the padlock shows.

### Step 2. Set the PHP version to 8.2

1. Left menu → **Advanced → PHP Configuration**.
2. Under **PHP version**, choose **8.2** (8.3 is also fine). Press **Update**.
3. Leave every other option on that page as it is. The shop brings its own settings file
   (`.user.ini`) with the right values.

### Step 3. Two things NOT to touch in hPanel

- **Security → SSL → Force HTTPS: leave this switch OFF.** The shop forces https:// by itself,
  in the correct way for Hostinger's servers. Hostinger's switch writes a second, competing rule
  into the shop's files and can send visitors round in a loop.
- Do not install WordPress, the Website Builder, or any "LiteSpeed Cache" or "optimisation"
  plugin on this domain. The shop is not WordPress and those tools would break it.

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

Do **not** open phpMyAdmin and do not create any tables. The installer does that in Part 5.

---

## Part 3 — Create the orders mailbox and note its settings

Order confirmations to customers and "new order" alerts to you are sent from a real mailbox on
your domain. Create it before running the installer so the installer can test it.

1. Left menu → **Emails**. If you see a list of domains, press **Manage** next to
   `skyfragrances.com`.
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
   | SMTP port | `587` (STARTTLS / TLS). `465` with SSL also exists; the installer uses 587. |
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
2. You land in a folder view. Double-click **public_html**. This folder is the website: whatever
   is inside it is what the internet sees at skyfragrances.com.
3. Turn on hidden files. Look for the settings icon (a gear, usually top-right) and switch on
   **Show hidden files** (sometimes called "dotfiles"). Files whose names start with a dot, such
   as `.htaccess`, are invisible until you do this — and you need to see them in a moment.

### Step 2. Clear the placeholder

Inside `public_html` you will probably find a file called `default.php` (Hostinger's "website
coming soon" page), sometimes a `.htaccess` too. Select everything already inside `public_html`
and delete it (right-click → **Delete**). The folder should be empty.

### Step 3. Upload the ZIP

1. With `public_html` open, press the **Upload** icon (an arrow pointing up, top-right).
2. Choose **File**, pick the `skyfragrances-….zip` from your computer and wait for the progress
   bar to finish (14 MB takes a minute or so).
3. The ZIP now appears inside `public_html`.

### Step 4. Extract it — into public_html itself

1. Right-click the ZIP → **Extract**.
2. A box asks where to extract. It must say **`public_html`** (or `/public_html`) — the folder
   you are already in. Do **not** add a sub-folder and do not type `skyfragrances`. Press
   **Extract**.
3. When it finishes, right-click the ZIP file and **Delete** it. If a folder called `__MACOSX`
   appeared, delete that too.

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
set the destination to `/public_html`, confirm, then delete the now-empty inner folder. The
installer also detects this and tells you.

**If `.htaccess` or `.user.ini` are missing**, hidden files are probably still switched off
(Step 1). If they really are missing, the installer's first screen will say so and can rewrite
the protective ones; tell the developer, because it means a ZIP tool dropped hidden files.

Wait about five minutes before Part 5. Hostinger reads the new `.user.ini` settings file every
five minutes, and the installer checks values from it.

---

## Part 5 — Run the installer (four screens)

Open **https://skyfragrances.com/install.php** in your browser. A dark page titled
"SKY FRAGRANCES · Installer" appears with four numbered steps across the top: **1 Check server ·
2 Database · 3 Admin & data · 4 Finish**.

### Screen 1 — Check server

A table of checks. Each row is marked **OK**, **Note** or **Problem**.

- **OK** rows need nothing.
- **Note** rows are information only. Before your domain fully points at the server you will
  normally see *Pretty web addresses — could not be checked*; that is expected. Read any other
  Note and do what it says later (for example "test /shop in your browser after installing").
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
  Manager (Part 4, Step 2).

When there are no Problem rows, press **Continue**.

### Screen 2 — Database

The first time you open this screen it starts with a box titled **Prove you own this hosting
account**. This is a safety lock: it stops a stranger who finds `install.php` from pointing your
shop at their own database.

1. The box says the installer wrote a one-time key to `storage/.install-key`. In the File
   Manager tab, open `public_html → storage`, make sure hidden files are showing, and open the
   file `.install-key` (right-click → **Edit** or **View**). It contains one line of 32 letters
   and numbers.
2. Copy that line into the **Install key** field on the installer. You only do this once; the
   installer deletes the key file when it finishes.

Then fill in the rest of the form from your notes (Part 2 and Part 3):

| Field | What to enter |
|---|---|
| Database host | `localhost` (already filled) |
| Database port | `3306` (already filled) |
| Database name | your `u…_skyfrag` value, exactly |
| Database username | your `u…_skyadmin` value, exactly |
| Database password | the database password |
| **Shop address** | Leave the pre-filled value. It should read `https://skyfragrances.com`. The installer fixes the spelling if you typed `www.` |
| **Email sending (optional)** | SMTP host `smtp.hostinger.com`, SMTP port `587`, Mailbox address `orders@skyfragrances.com`, Mailbox password (Part 3), Sender name `Sky Fragrances`. If you skip this, the shop still works; order emails are saved on the server instead of sent until a developer adds the details. |

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
  press the Continue link on that screen.

When the connection succeeds the installer writes `config.php` and moves to Screen 3.

### Screen 3 — Admin account & sample data

This creates the one account you will use at `/admin`.

| Field | What to enter |
|---|---|
| Admin username | Choose something other than `admin`, for example `sky.owner`. Letters, numbers, dots, dashes, underscores. |
| Your email address | Your own address. It receives the **New order** alerts and is shown on the Contact page. You can change both later in Settings. |
| Admin password / Repeat | At least 12 characters. Use a password manager or write it down safely — there is no "forgot password" email. |
| Store name | `Sky Fragrances` |
| WhatsApp number | The number customers will message, for example `0300 1234567`. The installer stores it in international form (+92…). |
| Install 12 sample perfumes… | Leave **ticked**. You will remove the samples from **Tools** once your own perfumes are in (Part 7). They let you see and test a full shop straight away. |

If you entered a mailbox on Screen 2 there is a second button, **Send test email to the address
above**. Press it first: a green box confirms it was sent — check your inbox (and spam) on your
phone. A red box means the mailbox password or host is wrong; press **Back**, then **Change database
details**, and re-enter the email settings.

Press **Create the shop**. This takes a few seconds: the tables are created, the sample data is
loaded, your account is created, protective files are checked and the shop's settings are
written.

If it stops with a red message such as **"Creating the database tables stopped at statement …"**
or **"adding the sample data stopped at statement …"**, take a screenshot of the whole message
and send it to the developer. You can safely reload and try again; the installer tells you when
it will replace an unfinished attempt (a tick box appears on this screen) and never touches a
finished installation.

### Screen 4 — Finish

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

Remember Part 1, Step 3: hPanel's own **Force HTTPS** switch (Security → SSL) must stay **off**.

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
   installer Screen 3.
2. The panel remembers the browser you used. If you later log in from a laptop or a different
   phone, you simply log in again there — nothing else is needed.
3. Five wrong passwords in a row slow the form down; ten lock that connection out for 15
   minutes. The lock clears by itself.

The **Dashboard** shows today's and this month's revenue, orders by status, low-stock alerts,
the latest orders and best sellers. Two yellow banners are normal on day one:
*"This store is still showing sample data"* (Part 7, Step 6 removes it) and — if you opened the
panel from an address different from the site address — a note pointing at Settings → Advanced.

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
| Note shown for manual payments | The sentence customers see under the account details ("Send your payment screenshot and transaction ID…"). Keep or adjust. |
| Cash on Delivery: enabled, note, **COD limit** | `0` means no limit. Set a limit (for example 15000) if you do not want COD on very large orders — above it customers must pay in advance. |
| Hours to hold an unpaid transfer order | How long an unpaid bank/JazzCash/Easypaisa order waits before it appears on the **Orders → Cancel unpaid** list. |
| Enable/disable each method | Switch a method off to hide it at checkout. At least one must stay on. |

Press **Save Payments**. The dashboard's payment warning disappears when no REPLACE ME text remains.

### Step 4. Contact details, socials, logo, texts

Still in **Settings**, go through the other tabs:

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
- **Advanced** — the site address (`https://skyfragrances.com`), **Search engines may index this
  site** (must be **on** for the live shop; the installer switches it on automatically when the
  address is skyfragrances.com), maintenance mode and its preview key, HTTPS redirect, low-stock
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
5. **Sizes** — one row per bottle size: *Label* (`50ml`), *Millilitres*, *SKU* (suggested for
   you; keep it unique), *Price* in rupees (`5450`), optional *Sale price* (must be lower than
   the price — the shop shows the price crossed out), *Stock* (how many bottles you have) and
   *Low-stock alert at*. Press **+ Add size** for a second size and choose which one is the
   **default** shown first. Stock goes down automatically with every order and can never go
   below zero; a cancelled order puts it back.
6. **Visibility** — *Featured on the home page*, *Show the NEW badge*, *Status* (Active or
   Hidden), *Publish date*, *Sort order*.
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

Repeat for every perfume. Tip: the list at **Products** has quick toggles for Active/Hidden,
Featured and NEW, plus **Duplicate** for a variant that shares most details.

### Step 6. Remove the sample data

When your own perfumes are in (at least four active ones):

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

If you want the emails to go out even when nobody has the panel open, a developer can add a
15-minute cron job in **Advanced → Cron Jobs** running `cron.php` — it is optional.

### Tidy up

Delete your test orders' stock effects by cancelling them, or leave them: cancelled orders do not
count in revenue. Test orders cannot be deleted outright — that is deliberate, the order history
is your accounting record.

---

## Part 9 — Going public

- **Announce only after** Part 7 Step 3 (payment details) and Step 6 (sample data removed) are
  done and **Settings → Advanced → Search engines may index this site** is on.
- **Google.** Open **Settings → SEO**, paste the verification code from Google Search Console
  into *Google verification*, save, then verify in Search Console and submit
  `https://skyfragrances.com/sitemap.xml`. The sitemap updates itself.
- **Social previews.** Share a product link on WhatsApp: the image, title and price come from the
  product's photo and SEO fields.

---

## Part 10 — When something goes wrong

### During installation

| What you see | What it means | What to do |
|---|---|---|
| Hostinger's "website coming soon" page instead of the installer | `default.php` is still in `public_html`, or the files are in a sub-folder | Part 4, Steps 2 and 5. |
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
reset is done in the database, in five steps:

1. hPanel → **Databases → phpMyAdmin** → **Enter phpMyAdmin** next to your database.
2. In the left list click the table **admin_users**. You see one row — your account.
3. Press **Edit** (pencil) on that row. In the field **password_hash**, delete the contents and
   paste exactly this line:

   ```
   $2y$12$CWA5M0iI/WqQlVhMb7m4eONj8V9jB8ZlnaLID3xwLIOm8OUzfjPRO
   ```

   Press **Go**. Your password is now temporarily `Reset-Sky-2026-Temp!`
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
| Leave `install.php` on the server | It reveals server details. It refuses to re-run, but delete it. |
| Edit `.htaccess`, `.user.ini` or anything in `app/`, `admin/`, `db/` | These are the shop's engine. Everything you control is in Settings. |
| Delete `storage/` or `uploads/` | Sessions, payment screenshots and photos live there. |
| Share `config.php` or the install key | They give access to your database and mailbox. |
