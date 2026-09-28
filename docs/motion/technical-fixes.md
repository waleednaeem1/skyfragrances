# Motion layer — performance and accessibility fixes (2026-09-28)

Applied against `review-perf.md` and `review-a11y.md` on the `motion-wip` worktree. Paths are
relative to `site/`. Verification: `node dev-tools/motion-check.mjs` (three profiles, six pages,
all green), a per-finding Playwright probe (desktop / mobile 360 / reduced / 3g-low profiles) and
Lighthouse mobile through the gzip proxy (`lighthouse/summary.md`, last section).

## What changed and why

| Finding | Change | Where |
|---|---|---|
| Intro runs on every page and device; `flags.loader` dead; veil covers price/Add to Cart on the WhatsApp → product path; reduced-motion users get a 0.9 s veil | The intro decision moved into the new first-paint gate. It sets `sf-intro-full` (desktop) or `sf-intro-calm` only when the gate tag carries `data-page="home"`, the visitor is not reduced-motion, `flags.loader` allows the device (`'desktop'` shipped — kill-switch #5 now works, also via `localStorage.sfMotionFlags.loader`), and `sessionStorage.sfIntro` is absent and writable. Quick mode is gone (PLAN §2.2). `intro.php` is printed on `home` only. | `assets/js/motion/gate.js`, `app/partials/head-meta.php`, `app/views/layout.php`, `assets/js/intro.js` |
| Tap to skip lands on what is under the opaque veil | `site.css` gives the veil `pointer-events:auto` while a mode class is set; the skip listener is on the veil, not `window`; `intro.js` releases pointer-events at 80 % of the dissolve (`intro.passThroughAt`), so the skip tap's own `click` is swallowed and pass-through starts only under ~0.2 opacity. Verified: a tap on the hero CTA position at +250 ms skips and stays on `/`. | `assets/css/site.css`, `assets/js/intro.js`, `config.js` |
| Monogram downloaded twice per page (img `?v=` vs bare CSS mask URL) | Both the intro `<img>` and the shimmer mask (`--sf-intro-mark`, printed inline by `intro.php`) now use the same `asset()` URL as the header logo, so the intro adds no image bytes; the mask is a cache hit. Not re-encoded at 200w: at q75 it is 10.3 KB, not 6, and the intro is desktop-only where the mark renders at up to 280 css px. | `app/partials/intro.php`, `assets/css/site.css` |
| Permanent `will-change` on `.sf-reveal` (16 compositor layers at rest on mobile home) | Removed from `site.css`; `reveal.js` sets an inline `will-change` one frame before `is-visible` on non-stagger nodes and clears it on `transitionend` (1.4 s fallback). Stagger containers get no hint (their children animate, they do not). | `assets/css/site.css`, `assets/js/reveal.js` |
| Two sync head scripts (6.8 KB gz) before `site.css` | `gate.js` (1.1 KB gz, sync) holds classification, device thresholds (`SF_GATE.rules`), the loader flag and the intro decision; `config.js` is data only and `defer`; it re-exports the gate's `classify`/`applyClass`/`rules` on `SF_MOTION`. `intro.js` is no longer linked by PHP: the gate injects it only when it set a mode class, so phones, return visits and reduced-motion visitors never download it. | `gate.js`, `config.js`, `head-meta.php`, `intro.js` |
| Section modules import inside the LCP window on phones; `low` loads modules that do nothing | On `mobile`/`low` the section IntersectionObservers are created after `load` + idle; on `low`, `hero` and `micro` are skipped (`loader.skipOnLow`). Desktop unchanged. | `assets/js/motion/core.js`, `config.js` |
| Stuck `sf-intro-*` class pauses hero keyframes forever | `intro.js` clears the class when `#sf-intro` is missing; `core.js` `guardIntro()` removes a mode class still present `intro.failsafeMs + intro.guardMs` after boot and drops the veil. `whenIntroGone()` now keys on the class, not the element (the partial no longer exists off-home). | `intro.js`, `core.js`, `config.js` |
| Quiz auto-advance is an unannounced change of context; "Question 1 of 5" never heard | `quiz.js` seeds the live region with the current position, prefixes each legend with a visually hidden "Question N of 5.", adds a hidden flow hint referenced by every radiogroup's `aria-describedby`, and announces "Answer saved. Next question in a moment; press any key to stay." when an answer arms the 600 ms undo window. | `assets/js/motion/quiz.js` |
| Plates animate for 84 s | `css['plate-cycles']` and `hero.plates.cycles` are 2 (even, so `alternate both` ends at rest). | `config.js` |
| Small consistency items | `intro.js` no longer re-classifies the device (reads the gate's class); `ribbons-gl.js` asks for `low-power`; `hero.js` decodes plates with `img.decode()` before `drawImage`; `micro.js`'s GSAP-less tick writes every other frame; magnetic pull excludes `.js-quiz, form`. | `intro.js`, `ribbons-gl.js`, `hero.js`, `micro.js`, `config.js` |
| Skip control | Focusable (no `tabindex="-1"`), `aria-label="Skip intro"`, colour `rgba(245,240,232,.72)` (≈ 8:1 on `#0A0A0A`). | `intro.php`, `site.css` |

Dev tooling: `dev/router.php` now serves images and fonts with the same immutable `Cache-Control`
Apache sets, so local request counts distinguish cache hits from fetches; `dev-tools/motion-check.mjs`
expects the intro only on the desktop profile, checks the veil is not displayed on the phone
profile, and accepts a buy-path hit on the live desktop veil at `load` (the intro check then
asserts the CTA is clickable after it ends).

## Not applied (with reasons)

- **`motion.css` split into core + `motion-desktop.css`** — restructures five owner-held
  `assets/css/motion/*.css` files and the assembly step; zero behaviour change but a cross-owner
  refactor. Next lever per Lighthouse (each render-blocking file costs one RTT, not bytes).
- **Font subsetting** — `pyftsubset` is not installed and downloads are restricted to the named
  libraries; it is also PLAN open question 8 (client approval to subset and drop the italic).
- **Product-card hover images / gallery zoom renditions** — pre-existing, in `product-card.php`
  and `gallery.php`; the exact fixes are in `review-perf.md`.
- **"Pause animations" footer toggle** — needs a footer control and every reduced-motion CSS
  block re-keyed on `html.sf-reduce` alone (today they sit inside the media query), across all
  owners. Plate cycles were cut to 2 as the partial mitigation.
- **`localStorage` 7-day intro stamp** — PLAN §2.2 asks for it; the review marks it PLAN-level
  (client nod) because it changes how often the client-approved loader replays. One line in
  `gate.js` (`introMode`) when approved.
- **Hero paused-state → `animation-delay` rewrite, `.hero__actions` at .85** — `10-hero.css` is
  the hero owner's; the JS guard above closes the stuck-class case that motivated it.
- **Cursor `forced-colors` guard, fanned focus `z-index`, rail focus padding, WhatsApp FAB over
  quiz cards, sticky size button height, GSAP ticker sleep, per-deploy motion version cache** —
  low, in files owned by others (`40-micro.css`, `20-cards.css`, `site.css`, `product.php`) or
  accepted as-is (ticker ≈ 0.05 ms/frame; 12 `filemtime()` calls ≈ 0.05 ms/request).
