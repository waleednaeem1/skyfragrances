# Stage 2 — global chrome, home page, 404, simple page

## Partial contracts (app/partials)

| Partial | Data | Notes |
|---|---|---|
| `header.php` | `isHome` bool, `collections` (≤8 rows: name, slug, image) | Mega panel `#collections-panel`, inline search overlay `#site-search` with `js-suggest-input` → `#suggest-hdr`. `layout.php` fetches the collections once and passes them here and to `nav-mobile.php`. |
| `nav-mobile.php` | `collections` | Search field with suggestions (`#suggest-nav`), collections sub-list, `aria-current`. |
| `footer.php` | — | Three collapsible columns (`js-footer-toggle`, ids `fc-shop/fc-help/fc-about`), compact newsletter in About, payment strip from enabled `*_enabled` settings. |
| `cart-drawer.php` | — | `data-count` on `.js-cart-body`; server renders the empty state, or skeleton lines + `aria-busy` when the cart has items (cart.js fetches `GET /api/cart` on first open). |
| `product-card.php` | `product` (id, name, slug, scent_family, rating_avg, rating_count, is_new, published_at, image, image_alt, image_alt_filename, sizes[] **or** size_labels/stock_total/price_from/compare_at/single_size_id), `eager`, `compact`, `sizes` | Calling `partial('product-card.php', [])` only defines the `product_card_*` helpers. Card srcset lists thumb + card derivatives only (zoom stays for the PDP). |
| `collection-card.php` | `collection` (name, slug, tagline, image, product_count?, image_alt?), `variant` ("large", "wide", "gender", space-separated), `href`, `eyebrow`, `sizes` | Also defines `collection_image_*` and `setting_image_url()`; empty call defines helpers only. |
| `section-header.php` | eyebrow, title, sub, linkHref, linkLabel, center, number, reveal, titleId, titleTag | Emits `sf-reveal` unless `reveal => false`. |
| `stars.php` | rating, count, size, href, showCount, showAvg | Read-only rating. |
| `placeholder.php` | label, ratio (3x4/1x1/16x9), class | Inline SVG monogram, `role="img"`. |
| `breadcrumb.php` | items[{label, url}] | Last item is `aria-current`; JSON-LD is the controller's job via `$head['jsonld']`. |
| `newsletter.php` | variant (band/compact), source, heading, text, ctaLabel, headingTag, headingId | Posts to `/api/newsletter`; honeypot `website`. |
| `icon.php` | name, size, label, class | Names: cart search menu close package chevron-down chevron-right arrow-right check star droplet banknote truck refresh shield sparkle compass quote mail external whatsapp instagram facebook tiktok youtube. |

## Sessions and CSRF in partials

`csrf_field()` starts the shop session, and sessions are lazy (08 C-53). Forms rendered on
every page (newsletter, card add-to-cart) therefore emit the hidden `_csrf` field only when a
session is already active; with JS, `SF.fetch` gets the token from `GET /api/session`. A
no-JS visitor without a session gets the "session expired" flash once and succeeds on retry.

## Image resolution

Product `filename` values are either the seeded `sample/{stem}.webp` (served from
`/uploads/products/sample/`) or a `product-{id}-{hash}` stem (served from `/uploads/products/{id}/`).
Derivatives follow `app/lib/image.php`: `-thumb` 200, `-card` 600, `-zoom` 1400, each webp + jpg,
falling back to the base file when a derivative is missing. Collection images resolve the same way
(`-card` 800, `-zoom` 1600). Image-type settings (hero, gender tiles, Instagram tiles) hold a
site-root-relative path or an absolute URL.

## Home page data (app/controllers/home.php)

Nine queries at most: home collections (fallback to all active when fewer than three are flagged),
per-collection product counts, best sellers (featured pinned first, then sales, rating, id), new
arrivals, one sizes query and one images query for both product sets, approved reviews (rating ≥ 4,
body ≥ 60 chars, exactly three or the band is omitted). Best sellers and new arrivals are both
omitted when fewer than four active products exist. Instagram tiles come from
`instagram_tile_{1..6}_image/_url`; with fewer than three set, the six primary product images stand
in and link to their product pages. The four "why choose us" items are hard-coded from the brief with
`delivery_time` interpolated. `WebSite` + `SearchAction` JSON-LD is added here; `Organization` comes
from `head-meta.php`.

## 404 and simple

`404.php` renders inside the full layout via `abort()`; it fetches four best sellers itself
(guarded, so a database outage still renders the page) because `abort()` passes no product data.
The requested path is never echoed. `simple.php` renders `heading`, `message` and optional
`actions[[path, label]]` in a centred panel (used by `/unsubscribe`).
