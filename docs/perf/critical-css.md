# Critical CSS — how the stylesheet is split and loaded

Owner: perf. Measured in `docs/lighthouse/summary.md`.

## The two files

`assets/css/site.css` used to be one 157 KB file. It is now two files with the same
`@layer tokens, reset, base, layout, components, utilities, overrides;` order statement at the top,
so the cascade is identical whether the browser has one of them or both:

| File | Contents | Size (raw / gzip) |
|---|---|---|
| `assets/css/critical.css` | tokens, `@font-face`, reset, base, layout; the components that are inside the first screen of the home, listing and product templates on a 412×823 phone and a 1440×900 desktop: announcement, badge, breadcrumb, btn, field, filters, toolbar, filter-bar, flash, gallery, hero, icon, placeholder, price, product-card, qty, site-header, size-picker, size-chip, stars, split, sticky-bar, stock-line, trust, whatsapp-fab; the whole `utilities` layer; the `overrides` rules `[hidden]`, `html.js …` (reveal, toolbar submit, accordion) and `body.…`; the `sf-intro` block | 83 KB / 14 KB |
| `assets/css/site.css` | every other component (accordion, cart, checkout summary, drawer, nav-mobile, modal, lightbox, footer, newsletter, pagination, prose, pyramid, reviews, story, suggest, toast, timeline, quiz, admin-facing panels…), the `.drawer[hidden]`-style overlay overrides, `.t-light`, print | 75 KB / 11 KB |

The list was not guessed: `dev-tools/split-css.php` slices a monolithic `site.css` at component
boundaries found by selector (`^  .announcement`, `^  .cart-line`, …), never by line number, and
refuses to write anything unless the multiset of lines in (critical + remainder) equals the
source. `php dev-tools/split-css.php site.css critical.css remainder.css [--dry]`.
The component set came from a Playwright scan of which class roots intersect the first viewport
on `/`, `/shop`, `/for-him`, `/product/azure-oud`, `/search`, `/collections` at both sizes.

Rules that only exist to keep a deferred component hidden (`.drawer[hidden] { display:flex;
visibility:hidden }`, same for `.nav-mobile` and `.modal`) must travel with that component: with
those overrides in the critical file and the component's `position:fixed` deferred, the off-canvas
menu became an invisible in-flow box that pushed the hero down ~340 px until the second file
arrived. The reset layer's `[hidden] { display:none }` keeps every overlay hidden until then.

## How the head loads them (`app/partials/head-meta.php`)

Home, listing (all landings and merch pages) and product pages, i.e. the templates whose first
screen is fully covered by `critical.css`:

```html
<link rel="stylesheet" href="/assets/css/critical.css?v=…">
<link rel="preload" href="/assets/css/site.css?v=…" as="style" fetchpriority="low">
<link rel="stylesheet" href="/assets/css/site.css?v=…" media="print" data-media="all">
<noscript><link rel="stylesheet" href="/assets/css/site.css?v=…"></noscript>
…
<script src="/assets/js/css.js?v=…" async></script>
```

`critical.css` is the only render-blocking stylesheet. `site.css` is fetched without blocking
(a non-matching `media` is not render-blocking; the low-priority preload keeps it behind the LCP
image and the fonts but ahead of lazy images) and `assets/js/css.js` switches `media` to the value
in `data-media` as soon as the sheet has loaded (or immediately if it already has). No inline event
handler is used because the CSP has no `'unsafe-inline'` for scripts. With JavaScript off the
`<noscript>` link loads it the old way.

`/search`, `/collections`, cart, checkout, quiz, content and utility pages link both files as plain
blocking stylesheets: their first screens use deferred components (forms, cart lines, quiz), so
deferring would flash unstyled content there for no measurable gain.

If `assets/css/critical.css` is missing, the head falls back to the original single blocking
`site.css` link, so an upload of an older monolithic `site.css` cannot break the site.

## Why the critical block is linked, not inlined

07 B.2.1 asks for the critical block to be inlined in a `<style>` tag with a 14 KB budget. Both
were tried against the file as built:

- The foundation layers alone (tokens 8 KB, fonts 1.6 KB, reset 1 KB, base 3 KB, layout 7.7 KB)
  are 21 KB before a single component. The smallest FOUC-free critical set is 83 KB raw / 14 KB
  gzip. A 14 KB raw budget is not reachable without rewriting the stylesheet.
- Inlining that block grew every heavy HTML from 10.6 KB to ~25 KB gzip and, under Lighthouse's
  4× CPU throttle, added ~600 ms of element render delay on the product page (style
  recalculation of an 83 KB inline block). Mobile Lighthouse went from 98/97/97 to 97/96/94.
  Linking it instead (cached for a year, 14 KB gzip) measured 98/97–98/96–97. Numbers and
  reports: `docs/lighthouse/summary.md`, `docs/lighthouse/variants/`.

## Maintenance

- A new rule for a first-screen component goes in `critical.css`; everything else in `site.css`.
  A rule in the wrong file still works (both files declare the same layers), it only paints one
  request later on the three heavy templates.
- Both files must keep the `@layer …;` order statement as their first line.
- Never inline `critical.css` by hand: `@font-face` and the intro monogram use `url('../fonts/…')`
  relative to the stylesheet's own URL, which breaks when the text is moved into the HTML.
- Cache busting is unchanged: `asset()` appends `?v=filemtime`, and `.htaccess` keeps CSS immutable
  for a year.
