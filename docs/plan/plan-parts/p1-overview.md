# 1. What you are getting

A finished, self-contained online perfume shop for skyfragrances.com, plus the admin panel that
runs it — delivered as one ZIP you extract into `public_html` through hPanel File Manager, one
database you create in hPanel, and one page (`install.php`) you open in your browser. No
developer tools are involved at any point: nothing to compile, nothing to install, nothing to
schedule. Every page is built for a phone first and tested at 375 px wide (see 07 Part B).

**What a customer experiences**

- A black-and-champagne-gold storefront: full-screen hero, announcement bar, shop-by-collection,
  best sellers, new arrivals, For Him / For Her / Unisex, why-choose-us, newsletter, Instagram
  tiles, and a floating WhatsApp button on every page (see 03 §4).
- A shop page with filters (collection, gender, scent family, price), sort, search with
  suggestions as they type, and pagination; landing pages for `/for-him`, `/for-her`, `/unisex`,
  `/new-arrivals`, `/best-sellers`, `/sale`, each collection and each scent family (see 03 §5).
- A product page with zoomable gallery, size selector (each size with its own price, sale price
  and stock), scent-notes pyramid, longevity and sillage meters, season/occasion, approved
  customer reviews only, related products, a sticky add-to-cart bar on mobile and a WhatsApp
  order button (see 03 §6).
- A slide-out cart drawer, a cart page with a free-shipping progress bar and coupon box.
- Guest checkout — name, phone, email (optional), city, address, note — paying by Cash on
  Delivery, or Bank Transfer / JazzCash / Easypaisa where they see your account details, type
  the transaction ID and upload a screenshot (see 06b §2–4).
- A confirmation page and email, a Track Order page (order number + phone) with a status
  timeline, a Scent Finder quiz, About / Contact / FAQ / Shipping / Returns / Privacy / Terms
  pages, and a branded 404 page.

**What you can do from your phone (the `/admin` panel)**

- Dashboard: today's and this month's revenue, orders by status, low-stock alerts, latest
  orders, best sellers, and a red warning if any email failed to send.
- Products and collections: add, edit, hide, delete; upload several photos at once (auto-resized
  and cropped to the house 4:5 shape); sizes with price, sale price, stock and SKU; notes,
  gender, scent family, featured / new toggles, SEO title and description.
- Orders: filter, search, open one, move it Pending → Confirmed → Packing → Shipped →
  Delivered (or Cancelled, which puts the stock back), add courier and tracking, view the
  payment screenshot, mark as paid, print an invoice or packing slip, send a one-tap WhatsApp
  message from a template, add private notes, export CSV (see 05b).
- Coupons, review moderation, contact-form inbox, newsletter subscribers (CSV export), content
  pages, Settings (store info, logo, contact numbers, socials, hero and announcement text,
  shipping fee and free-shipping threshold, delivery time, each payment method on/off, bank /
  JazzCash / Easypaisa details, meta description, maintenance mode), change password (see 05a).
- A "Remove sample data" button for the launch day (see 07 A.7).

**What ships in the ZIP**

| Item | Detail |
|---|---|
| The site and admin code | Plain PHP, one CSS file, five small JS files, self-hosted fonts |
| `install.php` | One-time installer; creates tables, admin account and sample data, then tells you to delete it |
| `db/schema.sql` + `db/seed.sql` | The same tables and sample data, for phpMyAdmin if you ever prefer that route |
| Sample catalogue | 12 sky-themed perfumes, 5 collections, coupon `WELCOME10` (10% off above Rs. 3,000) |
| Go-live guide | Step-by-step for a non-developer: create the database, upload, run `install.php`, SSL, go live |

# 2. Technology decisions — and why

Every choice below follows from one fact: the site is deployed by extracting a ZIP inside
`public_html` on Hostinger shared hosting, with no shell, no Composer, no Node and no build step
(see 02a §1, 02b §1). LiteSpeed (Hostinger's web server, which reads Apache-style `.htaccess`
files) is assumed throughout.

| Decision | Choice | Why this is right for Hostinger shared hosting |
|---|---|---|
| Language | Plain PHP 8.2, no framework, no classes, no autoloader — small function files, one per topic (see 02a §6) | A framework needs Composer to install and update; plain files are what File Manager can upload and what you can read. Every included file refuses to run unless opened through `index.php`, so a lost `.htaccess` still leaks nothing (see 02b §4 Layer 3). |
| Database access | PDO (PHP's built-in database layer) with prepared statements everywhere; no raw SQL path exists in the code (see 02c §2) | Prepared statements make SQL injection through user input structurally impossible. Money columns are read as exact strings, never floating-point, so `Rs. 4,950` is always `Rs. 4,950`. |
| Database engine | Written for both MySQL 8+ and MariaDB 10.4+ — portable table definitions only, `utf8mb4_unicode_ci`, no MySQL-only features (see 02c §0, §7.2) | Hostinger usually serves MariaDB while your local test uses MySQL; `install.php` checks the server version before creating anything. |
| Front-end | One stylesheet (`assets/css/site.css`), five small vanilla-JavaScript files loaded after the page, five self-hosted font files (see 04a, 04b Part 5) | No bundler, no Google Fonts request, no third-party script. Every page works with JavaScript off; JS only adds the drawer, zoom, search suggestions and animations. |
| Pretty URLs | `.htaccess` rewrites under LiteSpeed: `/product/azure-oud`, `/shop`, `/cart`; forces HTTPS; strips `www` and trailing slashes; images and CSS never pass through PHP (see 02b §3) | LiteSpeed reads `.htaccess` on every request with no restart. The HTTPS rule is written to be loop-proof behind Hostinger's edge proxy (see 02b §2.3). |
| Private code | Three folders (`app/`, `db/`, `storage/`) and `config.php` are blocked by three independent layers: `.htaccess` deny, a blank `index.php` in each, and the in-file guard (see 02b §4) | Nothing can sit "above the web root" when you deploy by File Manager, so the code defends itself in place. |
| Uploaded images | GD (PHP's built-in image library): every upload is validated five ways, re-encoded, EXIF-stripped, cropped 4:5 and saved in three sizes as WebP + JPEG (see 02c §5) | GD ships with Hostinger PHP; no ImageMagick, no external service. Re-encoding means an uploaded file can never contain hidden code, and `/uploads` cannot run PHP on either LiteSpeed or Apache (see 02b §5). |
| Email | PHPMailer (four vendored files) sending over authenticated SMTP to your own `@skyfragrances.com` mailbox (see 02c §6) | Bare PHP `mail()` on shared hosting sends from a `u123456@srv…hostinger.com` account: SPF and DKIM fail and Gmail files it as spam. Authenticated SMTP passes all three checks. |
| Email that fails | Order is saved first; the email is attempted after. A failed email goes to an outbox and is retried when you next open the admin panel (see 06b §5) | An order is never lost to a slow mail server, and no scheduled job is needed. |
| Sessions | PHP file sessions stored in `storage/sessions/`: `SFSHOP` (customer cart, 3 days) and `SFADMIN` (admin, 120 min idle / 12 h maximum) (see 02c §1 step 3, 05a §2) | Hostinger's shared session folder is periodically swept; an app-owned folder keeps a customer's cart alive for the promised 3 days. |
| Scheduled jobs | None required. Sitemap caches itself for an hour; the outbox drains on admin page loads; nothing auto-cancels (see 02c §6.4, 07 B.1.7) | You are not asked to set up cron. If you ever do, it is optional and only speeds email retries. |
| Configuration | One file, `config.php`, a plain PHP array written for you by `install.php` from `config.sample.php` (see 02c §7.1) | A human can open it in File Manager and read every value; the installer can write it safely. |
| Installer | `install.php`: checks PHP version and extensions, tests the database login before writing anything, creates tables, admin account and sample data, then refuses to ever run again and tells you to delete it (see 02c §7.2) | Everything that would otherwise need a terminal happens in four browser screens. The admin dashboard shows a red banner until the file is gone. |
| Cache-busting | Stylesheet and script URLs carry the file's modification time (see 02a §5) | After any update, browsers fetch the new file with zero work from you. |
| Errors in production | `display_errors` off, everything logged to `storage/logs/`, visitors see a branded page with an incident id (see 02c §1 step 2, §8.1) | The brief's "never expose errors"; the log is readable in File Manager. |
| PHP limits | A shipped `.user.ini` sets upload size, memory and timezone; uploads are size-capped in code below the server limit (exact cap to be decided in stage 1 — 02b §8.3 and 02c §5.1 differ), one image processed at a time (see 02b §8) | `.htaccess` cannot set PHP values on LiteSpeed; `.user.ini` can. Staying under the limits avoids white screens and the 508 "resource limit" error. |

**Deliberately not chosen**

| Not chosen | Why |
|---|---|
| Laravel / Symfony / WordPress + WooCommerce | All need Composer or carry plugin-update and security-patch chores; none fit "upload a ZIP and forget". |
| React / Vue / Tailwind / any build step | Needs Node; a plain CSS file and a few KB of JS give a faster page on a Pakistani mobile connection. |
| PHP `mail()` | Lands in spam (above). |
| ImageMagick or an image CDN | Not guaranteed on the plan; GD is. |
| Payment gateway (card checkout) | Not in the brief; COD plus manual transfer with screenshot proof. A gateway is a later addition (see 02c §8.2 for the rule that must accompany it). |
| Customer accounts | Guest checkout only; orders are tracked by number + phone. |
| `php_flag engine off` in `uploads/` | LiteSpeed silently ignores it — a false claim of protection. Handler removal is used instead (see 02b §5.1). |
| Cron dependence, admin sessions table, order counter table, Google Maps / Analytics embeds | Each was proposed by one spec and cut by the decisions register (see 08 §2, §4). |

# 3. Folder structure of the ZIP

The ZIP extracts straight into `public_html`. This is the tree as settled by the decisions
register (see 08 §1.1; the full annotated version is 02a §2). Folders marked **private** are
blocked from the browser; only `uploads/` and `assets/` are ever served directly.

```
public_html/
├── index.php            The storefront's single entry point — every pretty URL lands here
├── install.php          One-time installer. Run once in the browser, then DELETE it
├── config.php           Your database, site address and email login. Written by install.php
├── config.sample.php    The blank template install.php copies from
├── .htaccess            Pretty URLs, force HTTPS, strip www, protect config.php
├── .user.ini            PHP limits (upload size, memory, timezone) — the only way to set them on LiteSpeed
├── robots.txt           Static; tells search engines to skip /admin, /cart, /checkout, /order
├── favicon.ico
│
├── app/                 PRIVATE — all the application code
│   ├── bootstrap.php    Loads config, opens the database, starts the session, sets error handling
│   ├── routes.php       The list of storefront URLs and which file handles each
│   ├── lib/             Helper function files, one per topic (db, cart, money, mail, image …)
│   │   └── vendor/PHPMailer/   The only third-party code: four files for sending email
│   ├── controllers/     One file per page or action (product, checkout, track …)
│   ├── views/           The HTML of each storefront page
│   ├── partials/        Shared fragments: header, footer, product card, cart drawer, WhatsApp button
│   └── emails/          Email bodies: order confirmation, admin alert, status update, contact
│
├── admin/               The admin panel — its own entry point, login-protected, not blocked
│   ├── index.php        Admin entry point: checks you are logged in, then dispatches
│   ├── .htaccess        Sends every /admin URL to admin/index.php
│   ├── controllers/     Dashboard, products, orders, coupons, reviews, settings, account …
│   ├── views/           The HTML of each admin screen
│   └── partials/        Admin header, sidebar, status badges, image uploader
│
├── assets/              Public, cached for a year, never written to by the site
│   ├── css/site.css     THE stylesheet
│   ├── js/              reveal, ui, cart, product, forms (storefront) + admin.js
│   ├── img/             Logo, default share image, placeholder, icons
│   └── fonts/           Cormorant Garamond and Jost, self-hosted
│
├── uploads/             PUBLIC READ, cannot run PHP — the only public folder the site writes to
│   ├── products/{id}/   Product photos in three sizes, WebP + JPEG
│   ├── collections/     Collection images
│   ├── og/              Share-preview images
│   └── settings/        Your logo and other images uploaded from Settings
│
├── db/                  PRIVATE — schema.sql, seed.sql, sample-manifest.php
│
└── storage/             PRIVATE, writable — logs/, cache/, sessions/, proofs/ (payment screenshots), .installed lock
```

Three notes on the tree:

| Point | Detail |
|---|---|
| Payment screenshots are not in `uploads/` | They live in `storage/proofs/` and can only be viewed through the admin panel while logged in (see 08 C-25, 05b §5.1). |
| Only two folders are ever written to | `uploads/` (product images) and `storage/` (logs, cache, sessions, proofs). Everything else is read-only after upload (see 08 C-24). |
| One development-only file is left out | A local `router.php` used to test on a laptop is excluded from the ZIP; it would be inert on Hostinger anyway (see 02c §9). |

**The three files you will ever open**

| File | When | What you do |
|---|---|---|
| `install.php` | Once, right after uploading | Open it in the browser, follow four screens, then delete the file in File Manager. |
| `config.php` | Rarely — if the database password or email login changes, or to switch maintenance mode on when the admin panel itself is unreachable | Edit the one value in File Manager and save. `install.php` writes it for you the first time. |
| `.htaccess` | Only if the site is installed in a sub-folder (for example a staging copy) | Change the single `RewriteBase /` line to the folder name; `install.php` prints the exact line for you (see 02a §5). |

Everything else — products, orders, settings, logo, pages, coupons — is done from the admin
panel, never by editing files.
