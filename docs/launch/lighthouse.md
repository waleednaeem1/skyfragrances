# Lighthouse — launch build, local run 2026-09-29

Lighthouse 12 (dev-tools, `npm install --no-save lighthouse`) against `php -S 127.0.0.1:8099
dev/router.php` on the shared dev database, Chrome headless, one page per run, default mobile
throttling unless marked desktop. No `.htaccess` / `.user.ini` (php -S ignores both), so
compression and cache headers are the built-in server's, not LiteSpeed's. Raw JSON was not
committed (≈0.5 MB per page).

| Page | Preset | Performance | Accessibility | Best practices | SEO | LCP | CLS | TBT |
|---|---|---|---|---|---|---|---|---|
| `/` | mobile | **76** | 100 | 100 | 69* | 4.9 s | 0 | 70 ms |
| `/shop` | mobile | **77** | 100 | 100 | 69* | 5.2 s | 0.001 | 0 ms |
| `/product/azure-oud` | mobile | **76** | 100 | 100 | 69* | 5.3 s | 0.001 | 0 ms |
| `/` | desktop | **98** | 100 | 100 | 69* | 1.0 s | 0.007 | 0 ms |

\* SEO 69 is one failing audit, `is-crawlable`: the dev database has **Let search engines index
the shop** switched off, so every page carries `noindex`. On skyfragrances.com the installer
switches it on (Screen 4 report row "search-engine setting"); with that audit passing the SEO
score is 100 on all four pages.

## Reading the 76–77 mobile score

- The mobile Performance score is below the brief's 90+ target on every storefront page. The
  cost is the LCP (4.9–5.3 s simulated 4G): the intro loader keeps the hero image / first card
  off-screen for the first ≈1.3–1.6 s, exactly what the `motion-wip` critique predicted. FCP is
  2.9 s, TBT ≤ 70 ms, CLS ≈ 0 — the page is not heavy, it is deliberately late.
- Desktop is 98 with LCP 1.0 s; the same pages without throttling have nothing else to fix.
- Other flagged audits: `unminified-css` (≈32 KiB saving) and `unminified-javascript` (≈13 KiB)
  — the one stylesheet and the vanilla scripts ship unminified by design (no build step); on the
  live host LiteSpeed's gzip/brotli removes most of that gap.

## What would move the mobile score to 90+

1. Skip the intro loader for Lighthouse-like first paints: show the hero immediately and run the
   monogram animation over it, or cap the loader at ≈600 ms. This is a design decision the owner
   has not been asked yet; it is the whole gap.
2. Minify `site.css` and the storefront scripts once (a one-off, committed output — no build step
   needed at deploy time).

Not done in this run: no change was made to the intro loader; the numbers above are the measured
state of the launch build.
