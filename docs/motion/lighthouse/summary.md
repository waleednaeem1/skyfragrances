# Lighthouse mobile — motion integration run (2026-09-28)

Method: `lighthouse 13.5.0 --preset=perf --form-factor=mobile --screenEmulation.mobile
--throttling-method=simulate --only-categories=performance`, Chrome for Testing 145 (Playwright
chromium-1208), one page per run, fresh profile (so `/` includes the first-visit intro).

Two servers were measured, same worktree, same code:

| Server | What it represents |
|---|---|
| `php -S 127.0.0.1:8091 dev/router.php` before this run | nothing compressed — php -S serves raw bytes (`site.css` 157 KB, HTML 79 KB) |
| `php -S` + gzip (`dev/router.php` now gzips css/js/json/svg; `dev-tools/gzip-proxy.mjs` on :8093 gzips the HTML) | production: Hostinger Apache `mod_deflate` + the `.htaccess` `AddOutputFilterByType DEFLATE` rules compress exactly these types |

## Results

| Page | Server | Performance | LCP | FCP | TBT | CLS | Speed Index |
|---|---|---|---|---|---|---|---|
| `/` | raw php -S | 72 | 5.4 s | 3.5 s | 80 ms | 0.001 | 3.5 s |
| `/product/azure-oud` | raw php -S | 73 | 5.5 s | 3.2 s | 0 ms | 0.001 | 3.2 s |
| `/` | gzip (production-equivalent) | **98** | 2.4 s | 1.2 s | 0 ms | 0 | 1.5 s |
| `/product/azure-oud` | gzip (production-equivalent) | **96** | 2.7 s | 1.2 s | 0 ms | 0 | 1.4 s |

Reports: `home-mobile.report.{json,html}`, `product-mobile.report.{json,html}` (gzip run);
`raw-php-s/` holds the uncompressed baseline.

## What the two runs say

- The 72/73 was not the motion layer: 157 KB of uncompressed `site.css` in the render-blocking
  path plus a 79 KB HTML document. Compressed they are 24 KB and 11 KB. Same code, 98/96.
- Render-blocking on the compressed run (`render-blocking-insight`, est. 450–470 ms total):
  `site.css` 24.2 KB gz, `motion.css` 6.6 KB gz, `config.js` 4.5 KB gz, `intro.js` 2.7 KB gz.
  The two sync scripts are the class gate the CSS keys on at first paint; PLAN §5.2 wanted them
  inlined behind a CSP hash, which would remove two requests (~300 ms simulated). Not done here.
- LCP on `/` is still the intro monogram (`.sf-intro__mark`, element render delay 1.6 s): the
  built intro runs "full" on mobile and fades the mark in at ~860 ms after DCL, so on a first
  visit the mark (larger than the h1 at 360 px) becomes the LCP element. 2.4 s is inside the
  "good" band but with 0.1 s to spare; if it slips on real 4G, kill-switch #5
  (`flags.loader: 'desktop'`) is the next lever and is not wired into `intro.js` yet.
- LCP on the product page is the gallery image (52.8 KB `-zoom.webp` at 360 px, pre-existing).
- Fonts were downloaded twice on every page (preload `?v=…` URL vs the bare `@font-face` URL):
  88 KB per page. Fixed in `head-meta.php` (preload now uses the bare `/assets/fonts/` URL).
- The intro mark had `fetchpriority="high"`; PLAN §2.2/§11 removes it. It is now `loading="lazy"`
  with no priority hint, so it is not fetched when the veil is `display:none`.

## After the performance/accessibility fixes (same method, gzip server, Lighthouse 13.4.1)

| Page | Performance | LCP | FCP | TBT | CLS | Speed Index | LCP element |
|---|---|---|---|---|---|---|---|
| `/` | **98** | 2.3 s | 1.2 s | 0 ms | 0 | 1.2 s | `h1#hero-title` (was the intro monogram) |
| `/product/azure-oud` | **97** | 2.6 s | 1.2 s | 0 ms | 0 | 1.2 s | first gallery slide |

Reports: `home-mobile-after-fixes.report.json`, `product-mobile-after-fixes.report.json`.
What changed on the critical path: one 1.5 KB sync `gate.js` instead of `config.js` + `intro.js`
(6.8 KB gz, two parser-blocking executions); no intro veil, no intro script and no monogram
request on phones (the monogram on the wire is the header logo, same URL as the intro's);
`hero.js` / `micro.js` import after `load` (120 / 137 ms in the trace vs 66–117 ms before
`load` previously); no `will-change` layers at rest. The render-blocking insight still lists
`site.css` (24 KB), `motion.css` (6.6 KB) and `gate.js` — each one RTT on simulated 4G, which is
why the desktop-only split of `motion.css` (still open) is the next lever, not bytes.
