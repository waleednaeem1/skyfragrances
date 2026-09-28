# Lighthouse mobile — perf pass (2026-09-29)

Method: `npx lighthouse@13.5.0 --preset=perf --form-factor=mobile --screenEmulation.mobile
--throttling-method=simulate`, Chrome for Testing 145 (Playwright chromium-1208), fresh profile
per run, server `php -S 127.0.0.1:8101 dev/router.php`.

Two servers per page, because `php -S` cannot compress the HTML (the app clears every output
buffer before it echoes, which also removes `zlib.output_compression`), while Hostinger's
`.htaccess` `mod_deflate` block does:

| Column | Server | What it represents |
|---|---|---|
| raw | `:8101` | HTML uncompressed (80 KB), css/js gzipped by `dev/router.php` |
| gzip | `dev-tools/gzip-proxy.mjs` on `:8112` → `:8101` | production-equivalent: HTML 10.6 KB gzip |

## Before → after

| Page | raw before | raw after | gzip before | gzip after (median of 3) |
|---|---|---|---|---|
| `/` | 95 · LCP 2.8 s · FCP 1.6 s | 96 · LCP 2.8 s · FCP 1.5 s | 98 · LCP 2.3 s · FCP 1.1 s | 98 · LCP 2.4 s · FCP 1.1 s |
| `/shop` | 95 · LCP 2.8 s · FCP 1.5 s | 95 · LCP 2.9 s · FCP 1.4 s | 97 · LCP 2.5 s · FCP 1.2 s | 98 · LCP 2.5 s · FCP 1.1 s |
| `/product/azure-oud` | 95 · LCP 2.9 s · FCP 1.5 s | 96 · LCP 2.8 s · FCP 1.4 s | 97 · LCP 2.6 s · FCP 1.2 s | 96–98 · LCP 2.5–2.9 s · FCP 1.1 s |

TBT 0 ms and CLS 0 / 0.001 on every run, before and after. Transfer: home 397 → 399 KiB, shop
440 → 443 KiB, product 470 → 352 KiB (gzip runs). Render-blocking set went from
`gate.js + motion.css + site.css (24 KB gz)` to `gate.js + motion.css + critical.css (14 KB gz)`.
The product page's simulated LCP swings 2.5–2.9 s between identical runs (`runs/`); the LCP
element is the first gallery slide, a 52 KB `-zoom.webp` (1400 px) chosen because the derivative
set has no width between 600 and 1400 — that image, not CSS, is the remaining lever on that page.

Reports: `{home,shop,product}-before.json`, `-before-gzip.json`, `-after.json`, `-after-gzip.json`,
`runs/*-run2.json`, `runs/*-run3.json`.

## What changed

1. `site.css` split into `critical.css` (first-screen rules, blocking) and `site.css` (the rest,
   deferred on home/listing/product with a low-priority preload, `media="print"` and a
   `data-media` flip in `assets/js/css.js`; `<noscript>` fallback; plain links elsewhere).
   Rationale and the rejected inline variant: `docs/perf/critical-css.md`.
2. Related-products cards (`app/partials/related.php`): the `srcset` listed the 1400 px `-zoom`
   derivative, so any DPR-2 desktop fetched ~168 KB per card; they now use a `<picture>` with a
   WebP source and only the `thumb`/`card` widths with a sizes attribute that matches the card
   grid. Home, listing and Instagram cards already did this. The gallery's main desktop image,
   the lightbox and the collection hero legitimately keep `-zoom`.
3. `.htaccess`: `text/javascript` and `image/vnd.microsoft.icon` added to the expires and deflate
   lists, explicit `AddType` for `.js`/`.css`, `Vary: Accept-Encoding` on compressible static
   types.
4. Fonts were already self-hosted, `font-display: swap`, metric-matched fallbacks, exactly the two
   variable files preloaded with the same bare URL `@font-face` uses; no change.

## Variants measured (gzip server)

| Variant | `/` | `/shop` | `/product/azure-oud` |
|---|---|---|---|
| inline `<style>` critical + high-priority preload + `css.js` fetchpriority=high | 97 · 2.5 s | 96 · 2.7 s | 94 · 3.1 s |
| linked critical + high-priority preload + `css.js` fetchpriority=high | 91 · 2.4 s | 97 · 2.5 s | 96 · 2.8 s |
| inline critical + low-priority preload + plain async `css.js` | 98 · 2.4 s | 97 · 2.6 s | 97 · 2.6 s |
| **linked critical + low-priority preload + plain async `css.js` (shipped)** | 98 · 2.3 s | 98 · 2.5 s | 97 · 2.6 s |

Reports in `variants/`.

## Checks that back the split

- Parsed rule count: 1274 rules in the monolith, 1277 in critical + remainder (the three extra are
  the duplicated `@layer` statement and two wrapper blocks); the splitter also proves the line
  multiset is identical.
- FOUC coverage: each heavy template rendered with `site.css` blocked versus fully styled, at
  412×823 and 1440×900, reduced motion: 0 differing pixels on `/shop`, `/for-him`,
  `/product/azure-oud`, `/collections/golden-hour`; 80 px on `/` (the animated scroll cue).
- A/B against the monolith on the same code, 10 pages × 2 sizes × fold/full/motion: 55 of 60
  screenshots pixel-identical; the 5 differences are lazy images loading at different moments
  during full-page capture (the same page differs from itself by the same boxes) and the related
  cards now being WebP.
- `css.js` activates the deferred sheet on every heavy page (`link.media === 'all'` after load),
  no console errors, no 4xx.

## Still open (not perf-owned files)

- Product LCP image: add a 900 px product derivative (C-45 already lists 400/600/900/1400; the
  code writes 200/600/1400) and regenerate the sample set, then the mobile slide picks ~20 KB
  instead of 52 KB.
- `motion.css` (6.5 KB gz) stays render-blocking on motion pages because it carries first-paint
  layout for the hero object on every device class; a desktop-only split (`media` on the link)
  would take it off the phone's critical path.
- `gate.js` (1.4 KB) is a sync request in `<head>`; inlining it needs a new CSP hash in
  `app/lib/response.php`.
- Announcement dismissal is read from `sessionStorage` after first paint, so every later page in
  that session shifts the header up by the bar's height (Lighthouse's fresh profile never sees
  it). Fix: `ui.js` sets a session cookie on dismiss and `announcement-bar.php` skips the bar
  when it matches.
- The two variable fonts (87 KB) could drop ~20 KB with partial instancing to the used weight
  ranges (Jost 300–500, Cormorant 300–400) via fontTools.
