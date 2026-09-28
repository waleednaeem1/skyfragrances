# Observations from the live launch (2026-09-28) — feed into the polish stage

- Hostinger's CDN (hcdn) replaces the PHP-sent Content-Security-Policy with only
  `upgrade-insecure-requests`; the CSP the plan specified is not reaching browsers. Decide: accept,
  or deliver via <meta http-equiv> for the parts that matter (script-src, frame-ancestors is
  header-only anyway).
- hPanel's ZIP extractor drops top-level dot-files; the ZIP now wraps everything in public_html/.
  The GO-LIVE guide must describe the rename-old / extract-one-level-up flow, and the install-key
  step (copy from the CURRENT public_html/storage/.install-key).
- install.php form bug (key box outside the form) was found only by a human in a browser: the
  acceptance run must submit real forms with Playwright, not hand-built curl POSTs.
- Admin: stat-value specificity bug, desktop chip-row bleed, and text "SF" logo — fixed 3b078b4.
  Add a desktop AND phone screenshot review of every admin screen to the polish critics.
- Storefront: scroll-reveal left content invisible after Back/anchor restores — fixed 0f714ea.
  The screenshot tour force-reveals, so it cannot catch reveal bugs; add a no-force pass.
- Product imagery is still the generated placeholder art; real bottle photography on black is the
  single biggest visual upgrade available and only the owner can supply it.
- Local dev DB carries test orders/products from the verification runs (harmless locally; never
  reuse it as a seed source).
