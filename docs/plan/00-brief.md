# Sky Fragrances — Client Brief (verbatim, source of truth)

## Brand
- Name: Sky Fragrances — domain: skyfragrances.com
- Tagline: "More Than Just A Scent"
- Logo: ./logo.png (champagne-gold "SF" monogram + "SKY FRAGRANCES" on a black background)
- Visual style: ultra-luxury, minimal, black (#0A0A0A) + champagne gold (#D4B084, gold
  gradients) + ivory (#F5F0E8). Elegant serif headings (Cormorant Garamond), clean sans
  body (Jost). Subtle animations (fade/slide on scroll, hover zoom on products), lots of
  whitespace. Must look better than top perfume brands (Le Labo, Byredo, J. and Scentsation).
- Market: Pakistan. Currency: PKR (format "Rs. 4,950"). Timezone Asia/Karachi.

## Hosting & tech (hard constraints)
- Hostinger SHARED hosting: PHP 8.x + MySQL only. No Node, no Composer, no build step.
- Plain PHP with PDO (prepared statements everywhere), vanilla JS, one CSS file.
- Must work by uploading files via hPanel File Manager + importing the DB.
- Provide config.php for DB credentials and a one-time install.php that creates tables,
  the admin account and sample data (and tells me to delete it afterwards).
- Pretty URLs via .htaccess (e.g. /product/azure-oud, /shop, /cart). Force HTTPS.

## Storefront pages
1. Home: full-screen hero with logo/tagline + CTA, announcement bar, shop by collection,
   best sellers, new arrivals, "For Him / For Her / Unisex", why-choose-us (long-lasting,
   COD nationwide, fast delivery, easy exchange), newsletter signup, Instagram section.
2. Shop: filters (collection, gender, scent family, price range), sort, search, pagination.
3. Product page: image gallery with zoom, size selector (e.g. 50ml/100ml, each with
   its own price, sale price and stock), scent notes pyramid (top/heart/base),
   longevity & sillage meters, season/occasion, customer reviews (admin-approved only,
   no fake reviews), related products, sticky add-to-cart on mobile, WhatsApp order button.
4. Slide-out cart drawer + cart page, free-shipping progress bar, coupon codes.
5. Checkout (guest, no account needed): name, phone, email (optional), city, address, notes.
   Payment methods:
   - Cash on Delivery
   - Bank Transfer / JazzCash / Easypaisa (manual): show my account details (editable
     in admin), customer enters transaction ID and uploads payment screenshot.
6. Order confirmation page + email to customer and admin.
7. Track order page (order number + phone number) showing status timeline.
8. Scent Finder quiz (4-5 questions -> recommends products).
9. About, Contact (form saved to DB + WhatsApp), FAQ, Shipping, Returns & Exchange,
   Privacy Policy, Terms. Custom 404 page.
10. Floating WhatsApp button on every page.

## Admin panel (/admin)
- Secure login (password_hash, session regeneration, CSRF on every form,
  login rate limiting). Fully mobile-friendly so I can manage orders from my phone.
- Dashboard: today/month revenue, order counts by status, low-stock alerts,
  latest orders, best sellers.
- Products: add/edit/delete, multiple images upload (auto-resize, JPG/PNG/WEBP only),
  sizes with price/sale price/stock/SKU, notes, gender, scent family, featured/new
  toggles, active/hidden, SEO title & description.
- Collections (categories) CRUD.
- Orders: list with filters/search, order detail, change status (Pending -> Confirmed ->
  Packing -> Shipped -> Delivered / Cancelled), add courier + tracking number, view
  payment proof, mark as paid, print invoice/packing slip, restore stock on cancel,
  one-click WhatsApp message to customer, export to CSV.
- Coupons (percent/fixed, min order, usage limit, expiry).
- Reviews moderation, contact messages, newsletter subscribers (CSV export).
- Settings: store info, logo, phone/WhatsApp/email, social links, announcement text,
  hero text, shipping fee + free-shipping threshold, delivery time, enable/disable each
  payment method, bank/JazzCash/Easypaisa account details, meta description.
- Admin account: change password.

## Quality requirements
- Mobile-first and fully responsive (most customers use phones). Test at 375px width.
- Fast: lazy-load images, minimal JS, Lighthouse score 90+ target.
- SEO: unique titles/meta, Open Graph tags, Product JSON-LD schema, sitemap.xml,
  robots.txt, clean URLs, alt text.
- Security: prepared statements, output escaping, CSRF tokens, upload validation,
  block PHP execution in /uploads, never expose errors in production.
- Stock is decremented on order and can't go negative; prices always recalculated
  server-side (never trust the browser).
- Seed 12 realistic sample perfumes (sky-themed names like Azure Oud, Cirrus,
  Aurora Bloom), 5 collections, and coupon WELCOME10 (10% off above Rs. 3,000).

## How to work
1. First show me the folder structure, database schema and page list as a plan. Wait
   for my approval before coding.
2. Build in stages: core + database -> storefront -> checkout -> admin -> polish.
3. After each stage, run it locally (PHP built-in server + MySQL/MariaDB), fix errors,
   and take screenshots on desktop and mobile to check the design.
4. Finally, place test orders with every payment method, test every admin function, and
   give me:
   - a ZIP ready to upload to Hostinger
   - a simple step-by-step guide (for a non-developer) to create the database in
     hPanel, upload the files, run install.php, enable SSL and go live.

## Verified local environment (checked by Claude, 2026-09-24)
- PHP 8.2.28 (Homebrew) with pdo_mysql, gd, mbstring, intl, openssl, fileinfo, curl, zip.
- MySQL 9.3.0 running locally via brew services.
- Node v23.7.0 available for SCREENSHOT TOOLING ONLY (never shipped to Hostinger).
- CRITICAL: Hostinger shared hosting normally serves MariaDB (10.x/11.x) and LiteSpeed
  (reads .htaccess). All SQL must run on BOTH MySQL 8+ and MariaDB 10.4+.
