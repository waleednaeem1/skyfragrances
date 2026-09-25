# 5. Every page

## 5.1 Storefront route table

Every public URL the site answers. "Preset" means the shop page with one filter locked on — one code path, not a separate page (see 03 §2). "Editable in admin?" says what the owner can change without a developer.

| URL | Page | What it does | Editable in admin? |
|---|---|---|---|
| `/` | Home | Brand entry: hero, collections, best sellers, new arrivals, gender tiles, Scent Finder band, why-choose-us, reviews, Instagram, newsletter | Hero text/images, announcement, tile images, Instagram tiles, section toggles (Settings › Home) |
| `/shop` | Shop | All products with filters, sort, pagination (24 per page) | Products themselves; optional intro line |
| `/collections` | Collection index | One wide card per collection | Collections CRUD; intro text |
| `/collections/{slug}` | Collection landing | Shop preset to one collection, with its own banner and copy | Collection image, tagline, description, mood, SEO |
| `/for-him`, `/for-her`, `/unisex` | Gender landings | Shop preset to a gender | Banner image and intro paragraph per gender |
| `/scent/{slug}` | Scent-family landing | Shop preset to one scent family (Fresh, Floral, Oud…) | Family intro + SEO fields (from the `scent_families` table) |
| `/new-arrivals` | New arrivals | Shop sorted newest first | Product `published_at` |
| `/best-sellers` | Best sellers | Shop sorted by sales, featured products pinned first | Product "featured" toggle |
| `/sale` | Sale | Only products with a sale price | Sale price per size |
| `/search?q=` | Search results | Keyword search, scored (name > family > notes > description); not indexed | — |
| `/product/{slug}` | Product page | Gallery, sizes, price, add to cart, WhatsApp, notes, meters, reviews, related | Everything on the product form |
| `POST /product/{slug}/review` | Review submit | Saves a review as *pending*; never shown until approved | Approve/reject in Reviews |
| `/scent-finder` | Scent Finder | Five-question quiz | No screen in v1 (questions are seeded; see 08 §4) |
| `/scent-finder/result?a=` | Scent match | Top 3 recommendations; shareable URL; not indexed | — |
| `/cart` | Cart page | Full cart, coupon, free-shipping bar, checkout button | Shipping fee, threshold, coupons |
| `/checkout` (GET, POST) | Checkout | Guest form + payment method; places the order | Which payment methods are on; account details; delivery text |
| `/order/{number}?t=` | Order confirmation | The receipt; only opens with the secret link token | — |
| `/track` (GET, POST) | Track order | Order number + phone → status timeline | Courier/tracking per order |
| `/about`, `/shipping`, `/returns`, `/privacy`, `/terms` | Content pages | Prose pages from the database | Full text + SEO fields (Pages) |
| `/faq` | FAQ | Accordion built from the FAQ page's headings; FAQ schema | Full text (Pages) |
| `/contact` (GET, POST) | Contact | Details + form saved to the database and emailed | Phone, WhatsApp, email, address, hours |
| `/unsubscribe?e=&t=` | Unsubscribe | One-click newsletter opt-out | — |
| `/api/cart`, `/api/cart/add|update|remove|coupon` | Cart API (JSON) | Powers the cart drawer | — |
| `/api/search-suggest`, `/api/newsletter` | JSON helpers | Header autocomplete; newsletter signup | — |
| `/sitemap.xml`, `/robots.txt` | Crawl files | Generated sitemap (1-hour cache); static robots | — |
| any other path | 404 | Custom not-found page, real HTTP 404 | — |

Not built in v1, reserved: `/blog`, `/gift-sets`, `/account`, `/wishlist` (03 §2).

On every page: announcement bar, header, footer, the collapsed cart drawer, the floating WhatsApp button and a toast area (03 §1.5).

## 5.2 What each page shows

**Home** (03 §4). Announcement bar → full-screen hero (logo, "More Than Just A Scent", gold CTA, trust line) → Shop by Collection (5-tile mosaic) → Best Sellers (8) → New Arrivals (8) → For Him / For Her / Unisex tiles → Scent Finder band → Why choose us (ivory band, 4 items) → Customer voices (only real approved reviews, hidden below 3) → Instagram (6 owner-uploaded tiles) → Newsletter → footer. At most 9 database queries.
Mobile (375px): hero uses the portrait image at 88% of the screen height; collections and best sellers become swipe rails; new arrivals is a 2-column grid; gender tiles stack; everything else is single column.

**Shop and every listing** (03 §5). H1 and optional intro → result count + sort dropdown → active-filter chips → 4-column product grid, 24 per page → numbered pagination. Filters: collection, gender, scent family, price bands (computed from live prices), plus "On sale only" and "In stock only" chips. Sold-out products always sort last. Every filter state is a shareable URL and works with JavaScript off.
Mobile: 2-column grid; filters live in a bottom drawer opened by a sticky "Filter & Sort" bar; changes apply only when "Show {n} results" is tapped; pagination shrinks to ← Page 3 of 7 →.

**Product page** (03 §6). Gallery left, buy column right (sticky): collection → name → gender · family → rating (hidden at 0 reviews) → price → short description → size chips (each with its own price, sale price, stock) → quantity → gold Add to Cart → outlined Order on WhatsApp (prefilled message) → trust row → accordions. Below: "The Composition" (notes pyramid, longevity and sillage meters, season/occasion chips), You May Also Like (4, scored), Customer Reviews with submit form. Size changes are instant — no request.
Mobile: swipeable gallery with dots and a tap-to-open lightbox (native pinch-zoom); a sticky bottom Add bar appears once the main button scrolls away; a sold-out size swaps the button for "Notify me".

**Cart** (03 §7). Slide-out drawer on every page (opens on add-to-cart) and a full `/cart` page: lines with quantity steppers, free-shipping progress bar, coupon field, subtotal, Checkout. Prices are always re-read from the database, never from the browser. Caps: 10 per line, 20 lines (08 C-39).
Mobile: drawer is 92% of the screen width; on `/cart` the summary moves under the lines and Checkout repeats as a sticky bottom bar.

**Checkout** (03 §8). One page, guest only. Name, mobile (any Pakistani format, normalised), email (optional), city (free text with suggestions), address, notes → payment method cards (only enabled methods appear: COD, Bank, JazzCash, Easypaisa) → for the manual methods an inline panel with the owner's account details (copy buttons), the exact amount, a transaction ID field and a screenshot upload (JPG/PNG/WEBP, 08 Q-08) → order summary → Place Order (disables itself; a double tap cannot create two orders, 08 C-11).
Mobile: single column; the summary is a collapsed "Order summary — Rs. 9,695" at the top and repeats above the button.

**Order confirmation** (03 §9). Gold check → "Thank you, {first name}" → order number with copy button → email note or "Save this page — it's your receipt" → Message us on WhatsApp → items → money summary → address → payment method. Manual payments get a "We're verifying your payment" panel; COD gets "Please keep Rs. X ready for the courier". Opens only with the token in the link; without it the visitor sees the Track form instead.
Mobile: single column, order number block full-width and tappable.

**Track order** (03 §10). Two fields (order number, phone) → a five-stage timeline Pending → Confirmed → Packing → Shipped → Delivered with dates; Cancelled replaces the timeline with one muted panel; courier + tracking card once shipped. Shows items, total and city only — never the address, phone, email, payment details or admin notes. 8 attempts per 15 minutes, then a cool-down.
Mobile: the timeline runs vertically (horizontal from 768px).

**Scent Finder** (03 §11). One question per screen with a gold progress bar and Back; works as five plain form steps without JavaScript. The result page shows the top match as a large card, two more under "Also worth trying", Share / Retake, and the newsletter block. The exact five questions:

| # | Question | Options |
|---|---|---|
| 1 | Who is this fragrance for? | For him · For her · Doesn't matter — surprise me |
| 2 | Where will you wear it most? | Work and daytime · Evenings and dinners · Weddings and big occasions · Every day, all day |
| 3 | Which of these smells best to you? | Citrus, sea air, clean linen · Rose, jasmine, soft petals · Oud, leather, incense · Vanilla, amber, warm spice · Rain, grass, cut wood |
| 4 | How much presence do you want? | Close to the skin — only people near me notice · Noticeable, not loud · I want to be remembered |
| 5 | When do you wear fragrance most? | Karachi summer heat · Winter and cold evenings · All year round |

Scoring is one database query over existing product fields (gender, scent family, notes, sillage, longevity, season); sold-out products are excluded (03 §11.2).

**About, Shipping, Returns, Privacy, Terms** (03 §12.1). Prose from the `content_pages` table, edited in admin with a small allowed set of tags (paragraphs, headings, lists, links, bold). Narrow reading column (max ~608px, 04a §4.2); "Last updated" shown on Privacy, Terms and Returns. Seeded with real starting copy so no legal page is ever blank.
Mobile: same column, full width.

**FAQ** (03 §12.1, 08 §4). One `content_pages` row; each `<h3>` question and the paragraph after it become an accordion item and a `FAQPage` schema entry from the same source. With JavaScript off every answer is open.

**Contact** (03 §12.2). Short intro → details column (phone, WhatsApp tap-to-chat, email, address, hours — each hidden when empty) → form: name, email, optional phone, subject dropdown, order number (shown for order enquiries), message. Saved to the database first, then emailed; a mail failure never loses the message. No map embed (08 §4).
Mobile: details above the form, one column.

**404** (03 §13). Large muted "404", "This page has drifted off.", a search field, four link buttons (Shop All, Collections, Track Order, Contact) and four best sellers. Real 404 status; the bad path is never echoed. Full header, footer and WhatsApp button — a 404 is still a shop.

Spam protection on every public form, no captcha: hidden honeypot field, a 3-second time-trap, CSRF token, and per-form rate limits in the `rate_limits` table (03 §12.3, 08 C-17).

## 5.3 Admin route table

All under `/admin`, behind the owner's login; every save is a POST that redirects back with a one-line message, so the phone's back button never re-submits (05a §1). One line per screen; the exact POST URLs are in 05a §1 and 05b §0.4.

| Screen | URL | What it does |
|---|---|---|
| Login | `/admin/login` | Username + password; rate-limited; logout is a POST |
| Dashboard | `/admin` | Today/month revenue, orders by status, low stock, latest orders, best sellers |
| Products list | `/admin/products` | Filters, search, bulk actions (activate, hide, feature…) |
| Product form | `/admin/products/new`, `/admin/products/{id}` | All fields, sizes with price/sale/stock/SKU, SEO; delete = hide |
| Product images | `/admin/products/{id}/images…` | Upload (auto-resize, JPG/PNG/WEBP), drag to reorder, delete |
| Collections | `/admin/collections`, `…/new`, `…/{id}` | List, create, edit; delete blocked while products use it |
| Coupons | `/admin/coupons`, `…/new`, `…/{id}` | Percent/fixed, minimum order, usage limit, per-phone limit, expiry, on/off toggle |
| Reviews | `/admin/reviews` | Approve / reject / re-queue, singly or in bulk |
| Messages | `/admin/messages`, `…/{id}` | Contact messages: new / read / replied / archived, internal note |
| Subscribers | `/admin/subscribers`, `…/export.csv` | Newsletter list, unsubscribe/resubscribe, CSV download |
| Orders list | `/admin/orders`, `…/export.csv`, `…/bulk` | Filters, search, attention strip, bulk actions, CSV (≤5,000 rows) |
| Order detail | `/admin/orders/{number}` | Status change, mark paid / reject proof, courier + tracking, notes, restore stock, view proof, WhatsApp message button |
| Invoice / packing slip | `/admin/orders/{number}/invoice`, `…/packing-slip` | Printable pages (packing slip shows no prices, 08 Q-14) |
| Settings | `/admin/settings?tab=` | Tabs: Store · Contact & Social · Home · Shipping · Payments · SEO · Advanced — each tab saves on its own |
| Change password | `/admin/password` | New password; the session is regenerated and the CSRF token rotated on change |
| Sample-data remover | `/admin/tools/remove-sample-data` | One button deletes the seeded demo products, collections and reviews (08 §4) |
| Admin 404 | anything else under `/admin` | Not-found page inside the admin shell |

Admin on a phone (05a §3): a bottom tab bar — Orders (badge = pending + confirmed), Products, a gold "+" create button, Reviews (badge = pending), More. Below 768px every table becomes a stack of cards with one status chip and at most three fields. Desktop gets a 220px left sidebar with all eight sections. Session: 120 minutes idle, 12 hours absolute (08 C-06).

# 6. Design at a glance

The storefront is dark: black ground, ivory text, champagne gold accents. Ivory is used as a page ground only in the admin panel, print views and one "Why choose us" band on the home page (04a §1.4, 08 Q-22). There is no light/dark mode switch — the brand *is* dark (04a §1.4).

## 6.1 Palette and contrast

Contrast ratios are from the WCAG audit in 04a §2 (AA = the accessibility standard; body text needs 4.5:1, large text and UI outlines 3:1).

| Text / element | Hex | On background | Ratio | Result |
|---|---|---|---|---|
| Ivory body text | `#F5F0E8` | Black `#0A0A0A` | 17.45:1 | PASS |
| Muted ivory (captions, "was" prices) | `#9C968C` | Black | 6.75:1 | PASS |
| Brand gold (prices, links, eyebrows) | `#D4B084` | Black | 9.75:1 | PASS |
| Gold hover | `#E7D2AE` | Black | 13.41:1 | PASS |
| Success green ("In stock") | `#6FBF8B` | Black | 8.96:1 | PASS |
| Danger red ("Sold out", errors) | `#E5736B` | Black | 6.60:1 | PASS |
| Ivory text | `#F5F0E8` | Drawer / modal `#1C1A18` | 14.79:1 | PASS |
| Gold | `#D4B084` | Drawer / modal `#1C1A18` | 8.26:1 | PASS |
| Black text | `#0A0A0A` | Ivory `#F5F0E8` | 17.45:1 | PASS |
| Muted text on ivory | `#5A544B` | Ivory | 6.60:1 | PASS |
| Gold ink (links on ivory) | `#7A5A2E` | Ivory | 5.56:1 | PASS |
| Success on ivory ("Paid") | `#1F6B3E` | Ivory | 5.72:1 | PASS |
| Danger on ivory ("Cancelled") | `#A32B22` | Ivory | 6.33:1 | PASS |
| Black text on a gold button | `#0A0A0A` | Gold `#D4B084` | 17.45:1 | PASS |
| **Brand gold on ivory** | `#D4B084` | Ivory | **1.79:1** | **FAIL** |

**The gold rule** (04a §2.4, binding): brand gold `#D4B084` is never placed on ivory or white — not as text, an icon or a border. On ivory it is replaced by the deep "gold ink" `#7A5A2E`; a gold *fill* on ivory (a gold button) always carries black text, which passes. The gold gradient is display-only: allowed on the wordmark, the hero headline and section numerals at 28px and up, never on prices, buttons, links, labels or body copy, and every gradient heading falls back to flat gold (04a §2.5, 04b §0.2).

Two more rules from the audit (04a §2.6): a form field's resting border is decorative — its focus and error states use a full-strength gold or red border plus a ring; and selection (a chosen size, an active filter) is never shown by a gold hairline alone — it also gets a tinted fill, a 2px edge and the proper `aria` state.

## 6.2 Type scale

Two families, two jobs (04a §3.1): **Cormorant Garamond** (light and regular) for headlines, product names and prices; **Jost** (light, regular, medium) for everything else. Cormorant is never used below 20px or on anything clickable; Jost is never used for a product name. Five self-hosted font files, no italics, no bold (04a §3.2, 08 C-32). Sizes scale fluidly with the screen — no font-size media queries (04a §3.5).

| Step | Family / weight | At 375px | Desktop (1440px) | Used for |
|---|---|---|---|---|
| Display | Cormorant 300 | 47px | 88px | Home hero headline only |
| H1 | Cormorant 300 | 35px | 56px | Page titles, product name |
| H2 | Cormorant 400 | 27px | 40px | Section headers |
| H3 | Cormorant 400 | 20px | 26px | Card-group titles, drawer title |
| H4 | Cormorant 400 | 17px | 20px | Product card name, accordion head |
| Lead | Jost 300 | 17px | 20px | Intro paragraph under an H1 |
| Body | Jost 400 | 15px | 16px | Paragraphs, table cells |
| Small | Jost 400 | 14px | 14px | Meta, helper text, "was" price |
| Micro | Jost 400 | 12px | 12px | Legal, footnotes |
| Eyebrow | Jost 500, UPPERCASE, 0.22em tracking | 11px | 11px | Section labels ("CURATED") |
| Label | Jost 500, UPPERCASE, 0.14em | 12px | 12px | Form and tab labels |
| Button | Jost 500, UPPERCASE, 0.16em | 13px | 13px | Every button |
| Price | Cormorant 400 | 18px | 24px | Product-page price |
| Price small | Cormorant 400 | 16px | 16px | Card and cart prices |

Prices and quantities use tabular figures so `Rs. 4,950` and `Rs. 12,400` line up in a column (04a §3.7). The wide-tracked uppercase ("SKY FRAGRANCES" under the monogram) is the signature: never more than four words, never a sentence (04a §3.6).

## 6.3 Components

One stylesheet (`assets/css/site.css`), no framework. Each component is specified with every state (default, hover, focus, active, disabled, loading, error) and its 375px behaviour in 04b Part 1.

| Component | One line |
|---|---|
| Buttons (04b §1) | Gold gradient primary, outlined ghost, underlined text button; 52px tall, uppercase label; full-width on mobile, never two side by side |
| Product card (§2) | 4:5 image, up to two badges, serif name, sizes line, price, rating; hover cross-fades to the second photo |
| Collection card (§3) | 3:4 image under a dark scrim, serif name, tagline, a gold hairline that widens on hover |
| Inputs, select, textarea (§4) | 52px fields, label always above (never a placeholder-as-label), 16px text on mobile so iOS does not zoom; native select |
| Quantity stepper (§5) | − / number / +; the stock ceiling comes from the server on every response |
| Size selector (§6) | Real radio chips showing size and price; sold-out chips stay visible; selection is instant, no request |
| Badges (§7) | SOLD OUT > SALE −n% > NEW, square corners, max two per card |
| Notes pyramid (§8) | Top / Heart / Base as an indented list with a gold connector — a list, not a triangle graphic, so it reads on a phone and to a screen reader |
| Longevity & sillage meters (§9) | Five gold segments plus the word beside them; wipe in on scroll |
| Star rating (§10) | Gold stars at exact percentage; shown only with at least one approved review; interactive stars on the review form |
| Accordion (§11) | FAQ, product panels, mobile footer; all open when JavaScript is off |
| Drawer (§12) | Cart (right) and mobile filters (left); focus trapped, Escape closes, body scroll locked |
| Modal (§13) | Size sheet, image lightbox, admin confirmations; becomes a bottom sheet on mobile |
| Toast (§14) | Bottom-centre on mobile, top-right desktop; gold edge for success, red for error; max three |
| Breadcrumb, pagination, announcement bar (§15) | Real links; pagination is a page load; the bar is hidden when its text is empty |
| Header / mobile nav / footer (§16) | Transparent over the hero then solid; hamburger drawer on mobile; four footer columns → accordions |
| WhatsApp button, sticky add bar, newsletter, skeletons (§16) | 56px green circle on every page; product-page bottom bar; inline signup; three skeletons only |
| Missing-image placeholder (04b §24) | Dark box with a faint SF monogram at the right ratio — never a broken image, never a collapsed grid |

Corners are square everywhere (04a §4.5) — the only round shapes are the cart count bubble and the in-stock dot. Borders are 1px and change colour, never thickness. Every tap target is at least 44×44px (04b Part 4).

## 6.4 Motion rules

- **Four durations, one brand curve.** 120ms for presses, 200ms for hover colours and toasts, 320ms for drawers and accordions, 560ms for scroll reveals and image zoom, all on a long decelerating ease with no bounce. Only opacity, transform and colour are animated — never width, height or position (04b §17).
- **Reveal once, never on the fold.** Sections fade up 18px as they scroll into view and stay there. The header, announcement bar, hero, first product row, anything inside a drawer, and every element on cart, checkout and track never animate (04b §18). Hover zoom is 1.04 on cards, 1.06 on collection tiles, 1.08 on the product image, and only on mouse devices (04b §19).
- **Reduced motion is respected.** When the visitor's device asks for less motion, all movement stops but nothing is hidden: content shows instantly, drawers appear without sliding, meters render filled, the marquee wraps to two lines (04b §21). JavaScript totals about 11KB in five files; filters, pagination, the quiz and every price calculation work without it (04b Part 5).

## 6.5 What "better than Le Labo / Byredo" means here

The brief asks for a site that beats the category leaders. In this design that is four concrete choices, not a mood:

| Quality | What the build does | Where |
|---|---|---|
| Restraint | Two typefaces with five weights and no bold or italic; square corners; borders that change colour, never weight; no carousel library, no icon font, no custom dropdowns; a card with no border — "the image does the work" | 04a §3.2, §4.5; 04b §2, Part 5 |
| Typography | Cormorant for display, Jost for the interface, with a hard boundary between them; the wide-tracked uppercase eyebrow carried from the logo; tabular figures on every price; the trailing letter-space pulled back so right-aligned labels sit exactly on the grid — "the exact kind of error that separates this from the competitors named in the brief" | 04a §3.1, §3.6, §3.7 |
| Whitespace | A fluid type scale whose display end grows faster than the body, so desktop feels dramatic and a phone stays readable; a narrow checkout column "so it feels like a letter, not a form"; the product page's luxury read "comes from the negative space around" the bottle | 04a §3.5, §4.2; 04b §23 |
| Imagery | Bottle on black with one soft key light and a fading shadow; ivory reserved for one editorial band at a time; no gradients, CSS shadows or rounding on product photos; every image sized in advance so nothing shifts; a 4:5 crop enforced at upload so any photo the owner takes fits | 04b §22–24, 08 Q-17 |

Where the two design documents differ on a detail the register did not rule on — the focus-ring colour (gold in 04a §2.7, ivory in 04b Part 4), the meter word labels (03 §6.5 vs 04b §9) and the 2px button radius in 04b versus 04a's square default — 04a is the canonical token source (08 C-33) and the remaining wording is to be decided in stage 2 (storefront) before the first screenshot review.
