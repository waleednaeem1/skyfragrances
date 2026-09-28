# SEO audit — every storefront route, fetched and asserted

Run on 2026-09-29 against a local server (`php -S 127.0.0.1:8102 dev/router.php`) on the shared
`skyfragrances_dev` database. Harness: a Python script that fetched every GET route in
`app/routes.php` with real slugs, parsed the HTML and asserted the rules of 07 B.1, 03 §1.2 /
§5.3 / §15 and register §2.4. Nothing below is assumed from code reading unless the row says so.

Specs applied: `docs/plan/07-seed-seo-quality.md` B.1.1–B.1.8, `docs/plan/03-storefront-pages.md`
§1.2, §5.3, §5.4, §15, `docs/plan/08-decisions-register.md` (C-04, C-49, C-60, C-68).

## 1. What was asserted per route

| Check | Rule |
|---|---|
| `<title>` | present, ≤60 chars, matches the 03 §2 / 07 B.1.1 pattern, unique across indexable routes |
| meta description | present, ≤155 chars, unique across indexable routes, never a mid-word chop |
| canonical | absolute `SITE_URL`, no trailing slash, no query except `?page=n` and a single indexable filter; `/search` → `/search` with no `q`; none on 404 |
| robots | `index,follow` by default; `noindex,follow` per 03 §15.2; `noindex,nofollow` on `/order/{n}` |
| Open Graph / Twitter | `og:type` (`product` on PDP, `website` elsewhere), `og:title` without the brand suffix, `og:description` = meta description, `og:image` 1200×630 `-og.jpg` with `og:image:width/height`, `og:url` = canonical, `og:site_name`, `og:locale=en_PK`, `twitter:card=summary_large_image` + title/description/image |
| h1 | exactly one |
| JSON-LD | every block parses with `json.loads`; `Organization` on every page; `WebSite`+`SearchAction` on `/` only; `BreadcrumbList` wherever a visible trail exists; `Product` on PDPs with `offers.priceCurrency=PKR`, plain-decimal prices, `availability`, `sku`, `image`; `aggregateRating` only when approved reviews exist; `FAQPage` on `/faq` only |
| images | every `<img>` carries an `alt` attribute; `alt=""` only on decorative/duplicate imagery |
| sitemap | every indexable URL once, `<lastmod>` in W3C format with the `+05:00` offset, no `changefreq`/`priority`, no noindex URL, `application/xml; charset=utf-8` |
| robots.txt | the 07 B.1.7 literal, plus `Disallow: /storage/` per 03 §15.2 |
| redirects | renamed product / collection slugs 301 via `slug_redirects`; filter URLs with a pretty route 301 to it; non-canonical query order / case / trailing slash / `index.php` 301 |
| 404 | unknown paths and unknown slugs return HTTP 404 with `noindex,follow` and no canonical |

## 2. Fixes made (files owned by this task)

| File | Change |
|---|---|
| `app/partials/head-meta.php` | `og:image:width` / `og:image:height` emitted on every page (measured from the local file, 1200×630 fallback). `og:image` is only honoured when it is a `-og.jpg` derivative; anything else falls back to `settings.og_default_image`, so a 4:5 card or zoom image can never be announced as 1200×630. `og:title` always has the ` \| Sky Fragrances` suffix stripped. Title cap now drops the brand suffix first, then cuts on a word boundary with no ellipsis (07 B.1.1). Description cap cuts at the last sentence end inside 155 chars, else on a word boundary, never with an ellipsis (07 B.1.2). |
| `app/controllers/product.php` | `og:title` is the product name. `og:image` is the primary image's `uploads/og/…-og.jpg` derivative (sample and uploaded stems both resolved). Meta description follows the 07 B.1.2 chain: `seo_description` → `short_description` + `{sizes}. Cash on delivery across Pakistan.` only if it fits 155 → `short_description` if it fits → `settings.meta_description`; the old `excerpt(description, 155)` chop is gone. `BreadcrumbList` now includes `Shop` so it matches the visible trail `Home › Shop › {Collection} › {Product}` exactly (07 B.1.5). |
| `app/controllers/listing.php` | `/unisex` titled `Unisex Perfumes \| Sky Fragrances` (03 §2 row 7). `/search` canonicalises to `/search` with no `q` (07 B.1.6). Single-filter indexable listings get a distinct title (`… — Golden Hour`) and a count-led description (07 B.1.2) instead of duplicating the base landing. The collection/gender hero `og:image` override (a zoom crop, not 1200×630) was removed; those pages use the default OG image as 07 B.1.3 specifies. |
| `app/controllers/page.php` | `BreadcrumbList` (`Home › {heading}`) emitted to match the visible trail on every content page. Meta description falls back to whole leading sentences of the body that fit in 155 chars (verified against the seeded About copy → `Sky Fragrances is a Pakistani perfume house.`), and to `settings.meta_description` when no complete sentence fits. |
| `app/controllers/sitemap.php` | `/scent-finder`, `/track`, `/contact` no longer claim `lastmod = now` on every regeneration; they carry the newest of the latest product / latest settings change. |
| `robots.txt` | `Disallow: /storage/` added (03 §15.2). |

No view files needed changes: every `<img>` on every route already carried an `alt` attribute, and
no view sets a meta tag itself — `head-meta.php` is the only emitter.

## 3. Per-route results (final run, after the fixes)

`SITE_URL` in this run is `http://127.0.0.1:8088` (dev `config.php`); canonicals are shown relative
to it. `img` is `total/decorative` (`alt=""`). `JSON-LD` lists the block types found, all parsed.
Redirect rows show the `Location` the server sent (relative, which RFC 7231 permits; production
`.htaccess` keeps the same paths).

| Route | HTTP | Title / Location | robots | canonical | og:type | h1 | JSON-LD | img | Result |
|---|---|---|---|---|---|---|---|---|---|
| `/` | 200 | Sky Fragrances — Luxury Perfumes in Pakistan | index,follow | / | website | 1 | WebSite, Organization | 53/24 | PASS |
| `/shop` | 200 | Shop All Perfumes | Sky Fragrances | index,follow | /shop | website | 1 | BreadcrumbList, ItemList, Organization | 34/19 | PASS |
| `/shop?page=2` | 404 | Page Not Found | Sky Fragrances | noindex,follow | — | website | 1 | Organization | 12/6 | PASS |
| `/collections` | 200 | Our Collections | Sky Fragrances | index,follow | /collections | website | 1 | BreadcrumbList, CollectionPage, Organization | 26/6 | PASS |
| `/collections/dawn-chorus` | 200 | Dawn Chorus Collection | Sky Fragrances | index,follow | /collections/dawn-chorus | website | 1 | BreadcrumbList, ItemList, Organization | 13/9 | PASS |
| `/collections/azure-heights` | 200 | Azure Heights Collection | Sky Fragrances | index,follow | /collections/azure-heights | website | 1 | BreadcrumbList, ItemList, Organization | 17/11 | PASS |
| `/collections/golden-hour` | 200 | Golden Hour Collection | Sky Fragrances | index,follow | /collections/golden-hour | website | 1 | BreadcrumbList, ItemList, Organization | 13/9 | PASS |
| `/collections/midnight-meridian` | 200 | Midnight Meridian Collection | Sky Fragrances | index,follow | /collections/midnight-meridian | website | 1 | BreadcrumbList, ItemList, Organization | 15/10 | PASS |
| `/collections/monsoon-veil` | 200 | Monsoon Veil Collection | Sky Fragrances | index,follow | /collections/monsoon-veil | website | 1 | BreadcrumbList, ItemList, Organization | 13/9 | PASS |
| `/collections/stage4-collection-renamed` | 200 | Stage4 SEO | Sky Fragrances | index,follow | /collections/stage4-collection-renamed | website | 1 | BreadcrumbList, Organization | 25/15 | PASS |
| `/for-him` | 200 | Perfumes For Him | Sky Fragrances | index,follow | /for-him | website | 1 | BreadcrumbList, ItemList, Organization | 16/10 | PASS |
| `/for-her` | 200 | Perfumes For Her | Sky Fragrances | index,follow | /for-her | website | 1 | BreadcrumbList, ItemList, Organization | 16/10 | PASS |
| `/unisex` | 200 | Unisex Perfumes | Sky Fragrances | index,follow | /unisex | website | 1 | BreadcrumbList, ItemList, Organization | 18/11 | PASS |
| `/new-arrivals` | 200 | New Arrivals | Sky Fragrances | index,follow | /new-arrivals | website | 1 | BreadcrumbList, ItemList, Organization | 34/19 | PASS |
| `/best-sellers` | 200 | Best Selling Perfumes | Sky Fragrances | index,follow | /best-sellers | website | 1 | BreadcrumbList, ItemList, Organization | 34/19 | PASS |
| `/sale` | 200 | Sale — Perfume Offers | Sky Fragrances | index,follow | /sale | website | 1 | BreadcrumbList, ItemList, Organization | 18/11 | PASS |
| `/scent/fresh-citrus` | 200 | Fresh & Citrus Perfumes | Sky Fragrances | index,follow | /scent/fresh-citrus | website | 1 | BreadcrumbList, ItemList, Organization | 12/8 | PASS |
| `/scent/floral` | 200 | Floral Perfumes | Sky Fragrances | index,follow | /scent/floral | website | 1 | BreadcrumbList, ItemList, Organization | 16/10 | PASS |
| `/scent/amber-spice` | 200 | Amber & Spice Perfumes | Sky Fragrances | index,follow | /scent/amber-spice | website | 1 | BreadcrumbList, ItemList, Organization | 14/9 | PASS |
| `/scent/oud-smoke` | 200 | Oud & Smoke Perfumes | Sky Fragrances | index,follow | /scent/oud-smoke | website | 1 | BreadcrumbList, ItemList, Organization | 12/8 | PASS |
| `/scent/green-earthy` | 200 | Green & Earthy Perfumes | Sky Fragrances | index,follow | /scent/green-earthy | website | 1 | BreadcrumbList, ItemList, Organization | 12/8 | PASS |
| `/search` | 200 | Search | Sky Fragrances | noindex,follow | /search | website | 1 | BreadcrumbList, Organization | 24/14 | PASS |
| `/search?q=oud` | 200 | Search: "oud" | Sky Fragrances | noindex,follow | /search | website | 1 | BreadcrumbList, Organization | 18/11 | PASS |
| `/shop?collection=golden-hour` | 301 | → /collections/golden-hour | — | — | — | — | — | — | PASS |
| `/shop?page=1` | 301 | → /shop | — | — | — | — | — | — | PASS |
| `/shop?gender=him&sort=PRICE_ASC&foo=bar` | 301 | → /shop?gender=him&sort=price-asc | — | — | — | — | — | — | PASS |
| `/for-him?collection=golden-hour` | 200 | Perfumes For Him — Golden Hour | Sky Fragrances | index,follow | /for-him?collection=golden-hour | website | 1 | BreadcrumbList, ItemList, Organization | 10/7 | PASS |
| `/shop?in_stock=1` | 200 | Shop All Perfumes — In Stock Only | Sky Fragrances | index,follow | /shop?in_stock=1 | website | 1 | BreadcrumbList, ItemList, Organization | 34/19 | PASS |
| `/shop?collection=golden-hour&gender=her` | 200 | Shop All Perfumes | Sky Fragrances | noindex,follow | /shop | website | 1 | BreadcrumbList, Organization | 10/7 | PASS |
| `/shop?sort=price-asc` | 200 | Shop All Perfumes | Sky Fragrances | noindex,follow | /shop | website | 1 | BreadcrumbList, Organization | 34/19 | PASS |
| `/shop?price=5000-8000` | 200 | Shop All Perfumes | Sky Fragrances | noindex,follow | /shop | website | 1 | BreadcrumbList, Organization | 24/14 | PASS |
| `/shop?gender=him` | 301 | → /for-him | — | — | — | — | — | — | PASS |
| `/shop?sort=price-asc&gender=her&collection=golden-hour` | 301 | → /shop?collection=golden-hour&gender=her&sort=price-asc | — | — | — | — | — | — | PASS |
| `/scent-finder` | 200 | Scent Finder — Find Your Perfume | Sky Fragrances | index,follow | /scent-finder | website | 1 | BreadcrumbList, Organization | 8/6 | PASS |
| `/cart` | 200 | Your Cart | Sky Fragrances | noindex,follow | /cart | website | 1 | Organization | 8/6 | PASS |
| `/track` | 200 | Track Your Order | Sky Fragrances | index,follow | /track | website | 1 | Organization | 8/6 | PASS |
| `/faq` | 200 | Frequently Asked Questions | Sky Fragrances | index,follow | /faq | website | 1 | BreadcrumbList, FAQPage, Organization | 8/6 | PASS |
| `/about` | 200 | About Us | Sky Fragrances | index,follow | /about | website | 1 | BreadcrumbList, Organization | 8/6 | PASS (code) — see §5, data residue |
| `/shipping` | 200 | Shipping & Delivery | Sky Fragrances | index,follow | /shipping | website | 1 | BreadcrumbList, Organization | 8/6 | PASS |
| `/returns` | 200 | Returns & Exchange | Sky Fragrances | index,follow | /returns | website | 1 | BreadcrumbList, Organization | 8/6 | PASS |
| `/privacy` | 200 | Privacy Policy | Sky Fragrances | index,follow | /privacy | website | 1 | BreadcrumbList, Organization | 8/6 | PASS |
| `/terms` | 200 | Terms & Conditions | Sky Fragrances | index,follow | /terms | website | 1 | BreadcrumbList, Organization | 8/6 | PASS |
| `/contact` | 200 | Contact Us | Sky Fragrances | index,follow | /contact | website | 1 | Organization | 8/6 | PASS |
| `/unsubscribe` | 200 | Unsubscribed | Sky Fragrances | noindex,follow | /unsubscribe | website | 1 | Organization | 8/6 | PASS |
| `/product/azure-oud` | 200 | Azure Oud Eau de Parfum | Sky Fragrances | index,follow | /product/azure-oud | product | 1 | Product, BreadcrumbList, Organization | 28/15 | PASS |
| `/product/cirrus` | 200 | Cirrus Eau de Parfum — Fresh Unisex | Sky Fragrances | index,follow | /product/cirrus | product | 1 | Product, BreadcrumbList, Organization | 28/15 | PASS |
| `/product/aurora-bloom` | 200 | Aurora Bloom Eau de Parfum for Her | Sky Fragrances | index,follow | /product/aurora-bloom | product | 1 | Product, BreadcrumbList, Organization | 28/15 | PASS |
| `/product/stratus-noir` | 200 | Stratus Noir Eau de Parfum for Him | Sky Fragrances | index,follow | /product/stratus-noir | product | 1 | Product, BreadcrumbList, Organization | 28/15 | PASS |
| `/product/eclipse-velvet` | 200 | Eclipse Velvet Eau de Parfum for Her | Sky Fragrances | index,follow | /product/eclipse-velvet | product | 1 | Product, BreadcrumbList, Organization | 28/15 | PASS |
| `/product/zephyr-blanc` | 200 | Zephyr Blanc Eau de Parfum for Her | Sky Fragrances | index,follow | /product/zephyr-blanc | product | 1 | Product, BreadcrumbList, Organization | 28/15 | PASS |
| `/product/silver-lining` | 200 | Silver Lining Eau de Parfum for Him | Sky Fragrances | index,follow | /product/silver-lining | product | 1 | Product, BreadcrumbList, Organization | 28/15 | PASS |
| `/product/cumulus-cashmere` | 200 | Cumulus Cashmere Eau de Parfum | Sky Fragrances | index,follow | /product/cumulus-cashmere | product | 1 | Product, BreadcrumbList, Organization | 28/15 | PASS |
| `/product/saffron-zenith` | 200 | Saffron Zenith Eau de Parfum for Him | Sky Fragrances | index,follow | /product/saffron-zenith | product | 1 | Product, BreadcrumbList, Organization | 28/15 | PASS |
| `/product/halo-rose` | 200 | Halo Rose Eau de Parfum for Her | Sky Fragrances | index,follow | /product/halo-rose | product | 1 | Product, BreadcrumbList, Organization | 28/15 | PASS |
| `/product/nimbus-rain` | 200 | Nimbus Rain Eau de Parfum — Petrichor | Sky Fragrances | index,follow | /product/nimbus-rain | product | 1 | Product, BreadcrumbList, Organization | 28/15 | PASS |
| `/product/vetiver-squall` | 200 | Vetiver Squall Eau de Parfum for Him | Sky Fragrances | index,follow | /product/vetiver-squall | product | 1 | Product, BreadcrumbList, Organization | 28/15 | PASS |
| `/product/stage4-renamed` | 200 | Stage4 Test Perfume Edited — Floral perfume | Sky Fragrances | index,follow | /product/stage4-renamed | product | 1 | Product, BreadcrumbList, Organization | 24/13 | PASS |
| `/product/stage4-test-perfume` | 301 | → /product/stage4-renamed | — | — | — | — | — | — | PASS |
| `/collections/stage4-collection` | 301 | → /collections/stage4-collection-renamed | — | — | — | — | — | — | PASS |
| `/nope-not-here` | 404 | Page Not Found | Sky Fragrances | noindex,follow | — | website | 1 | Organization | 12/6 | PASS |
| `/product/does-not-exist` | 404 | Page Not Found | Sky Fragrances | noindex,follow | — | website | 1 | Organization | 12/6 | PASS |
| `/collections/does-not-exist` | 404 | Page Not Found | Sky Fragrances | noindex,follow | — | website | 1 | Organization | 12/6 | PASS |
| `/scent/does-not-exist` | 404 | Page Not Found | Sky Fragrances | noindex,follow | — | website | 1 | Organization | 12/6 | PASS |
| `/checkout` | 302 | → /cart | — | — | — | — | — | — | PASS |
| `/scent-finder/result` | 302 | → /scent-finder | — | — | — | — | — | — | PASS |
| `/order/SF-000001-ABCD` | 303 | → /track?order=SF-000001-ABCD | — | — | — | — | — | — | PASS |
| `/robots.txt` | 200 | text/plain; charset=UTF-8 | — | — | — | — | — | — | PASS |
| `/sitemap.xml` | 200 | application/xml; charset=utf-8 | — | — | — | — | — | — | PASS |
`/shop?page=2` is a real 404 here because the catalogue holds 13 products at 24 per page, so page 2
does not exist; `?page=1` 301s to `/shop` (default omitted). The page ≥2 title suffix
(` — Page {n}`) and self-canonical with `?page=n` were verified by reading `listing.php`, not by
fetch — there is no data to reach them.

## 4. Sitemap, robots, redirects, 404

- `/sitemap.xml`: HTTP 200, `Content-Type: application/xml; charset=utf-8`,
  `Cache-Control: public, max-age=3600`, `X-Robots-Tag: noindex`. Parsed with `xml.etree`: 42 `<url>`
  entries, every `<loc>` unique, every entry has a `<lastmod>` in `YYYY-MM-DDTHH:MM:SS+05:00`, no
  `<changefreq>` / `<priority>`. Contents = `/`, `/shop`, `/collections`, `/for-him`, `/for-her`,
  `/unisex`, `/new-arrivals`, `/best-sellers`, `/sale`, `/scent-finder`, `/track`, `/contact`, the 6
  content pages, 6 active collections, 5 scent families (only those with an active product), 13 active
  products. Not present: cart, checkout, order, search, unsubscribe, quiz result, any `/api/`,
  `/admin`, any query-string URL. When `site_indexable` is off the route returns 404 (the file cache
  under `storage/cache/sitemap.xml` is bypassed), which is the intended preview-copy behaviour.
- `/robots.txt`: static file, HTTP 200, matches the 07 B.1.7 literal plus `Disallow: /storage/`.
  The `Sitemap:` line is the production absolute URL by design.
- Slug redirects (rows in `slug_redirects`): `/product/stage4-test-perfume` → 301
  `/product/stage4-renamed`; `/collections/stage4-collection` → 301
  `/collections/stage4-collection-renamed`. Scent-family and page renames use the same
  `listing_redirect_if_renamed()` / `page.php` path (no rows exist to exercise them). Hit counters
  increment.
- Filter → pretty route: `/shop?gender=him` → 301 `/for-him`; `/shop?collection=golden-hour` → 301
  `/collections/golden-hour`. Non-canonical query: `/shop?gender=him&sort=PRICE_ASC&foo=bar` → 301
  `/shop?gender=him&sort=price-asc`; `/shop?collection=golden-hour,dawn-chorus` → 301 with values
  sorted. Case / slash / front controller: `/Product/Azure-Oud`, `/PRODUCT/azure-oud`,
  `/collections/Golden-Hour`, `/shop/`, `/product/azure-oud/`, `/index.php` all 301 to the canonical
  form. `HEAD /` answers 200.
- 404: `/nope-not-here`, `/product/does-not-exist`, `/collections/does-not-exist`,
  `/scent/does-not-exist`, `/shop?page=2` (beyond last page) all return HTTP 404, title
  `Page Not Found | Sky Fragrances`, `noindex,follow` (meta and `X-Robots-Tag`), no canonical, one h1,
  `Organization` JSON-LD from the shared layout.
- Utility redirects: `/checkout` with an empty cart → 302 `/cart`; `/scent-finder/result` without
  answers → 302 `/scent-finder`; `/order/SF-000001-ABCD` without a token → 303 `/track?order=…`.

## 5. Findings outside this task's files (for their owners)

1. **Shared DB test residue, not code.** The stage-4 admin tests rewrote the About page body to
   `<h2>Stage4 heading</h2><p>Hello world</p>…` and nulled its `seo_description`, and set
   `settings.meta_description` to `Stage4 meta description for the shop.`. That is why `/about` shows
   the store default description in this run: the body has no complete sentence, so the new fallback
   correctly refuses to chop it. Against the seeded copy the fallback yields
   `Sky Fragrances is a Pakistani perfume house.` and the seed also ships a hand-written
   `seo_description` for About. Product 13 (`stage4-renamed`) and collection 6 are also test rows.
2. **`site_indexable` was `0` in the shared DB** (set 2026-09-26 by the stage-4 settings test), which
   makes every page `noindex,nofollow` and the sitemap a 404. It was flipped to `1` for this audit and
   restored to `0` afterwards; `storage/cache/settings.json` was cleared each time. Go-live must turn
   it on (Settings › Advanced), as the admin help text already says.
3. **OG derivative for small uploads (image lib).** `uploads/og/product-13-3dc21f2d36-og.jpg` is
   900×473, not 1200×630: `image_cover()` does not upscale or letterbox a source narrower than 1200px.
   The sample derivatives are all 1200×630. `head-meta.php` now emits the measured size so the tags
   stay truthful, but 07 B.1.3 asks for a 1200×630 letterboxed canvas — the upload/image owner should
   pad small sources onto the `#0A0A0A` canvas.
4. **Scent-family titles come from the seed's `seo_title`** (`Fresh & Citrus Perfumes | Sky
   Fragrances`), which overrides the 07 B.1.1 fallback pattern `{family} Perfumes in Pakistan`. The
   record-level override is by design (C-04 gives `scent_families` its own `seo_*` columns, same as
   products and collections); if the seed owner wants the "in Pakistan" phrasing it is a seed edit.
5. `/cart` and `/unsubscribe` set no page-specific meta description (they fall back to the store
   default). Both are `noindex,follow`, so there is no duplicate-content exposure; their controllers
   are outside this task.
6. `/scent-finder/result?a=…` canonicalises to itself including `?a=` (quiz.php). It is
   `noindex,follow` and the URL is the WhatsApp share link, so it is harmless, but it is the one
   canonical on the site that carries a non-pagination query string.
7. Product-card hover images and PDP thumbnails carry `alt=""` deliberately: the card's primary image
   and the gallery's `aria-label`led thumb buttons already announce the product, so the duplicate is
   hidden from screen readers (07 B.1.8 "decorative ≠ missing").

## 6. How to re-run

```
cd site && php -S 127.0.0.1:8102 dev/router.php
```

Then fetch each route in §3 and repeat the assertions in §1. The harness used here lived in the
session scratchpad and is not shipped; it needs only Python 3's standard library.
