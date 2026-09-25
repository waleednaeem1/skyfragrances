# Sky Fragrances — Motion layer: codebase audit (2026-09-26)

Scope: `site/` as it is today, read against `docs/motion/00-brief.md`. Paths are relative to `site/`.
Purpose: where each requested concept plugs in, what it must never touch, what the site already
animates, what the CSP allows, and the collisions to design around before writing a line of GSAP.

## 0. Shape of the front-end (what the motion layer inherits)

- Plain PHP views (`app/views/*.php`, partials in `app/partials/`), one stylesheet
  `assets/css/site.css` (5,308 lines, `@layer tokens, reset, base, layout, components, utilities, overrides`),
  five storefront scripts loaded at the end of `<body>` with `defer` in `app/views/layout.php`:
  `reveal.js → ui.js → cart.js → product.js → forms.js`. Order matters: `ui.js` builds `window.SF`,
  the others read it. A motion script must load after `forms.js`.
- One inline script (head-meta.php): `document.documentElement.classList.add('js')`. Every
  JS-dependent hide is gated on `html.js` so no-JS never hides content. Motion must keep this rule.
- `asset()` (`app/lib/url.php`) appends `?v=filemtime` — vendored libraries get cache-busting for
  free; `.htaccess` serves `.js/.css` as `immutable, max-age=1y` and gzips them (no brotli).
- Per-page body classes from `app/routes.php`: `home`, `listing …`, `collections`, `product`,
  `quiz`, `quiz quiz-result`, `cart`, `checkout`, `track`, `confirmation`, `page`, `contact`.
  These are the natural per-page gates for lazy-loading motion modules.
- Motion tokens already in `site.css` `:root`: `--dur-fast 150ms`, `--dur-base 280ms`,
  `--dur-slow 600ms`, `--ease-out cubic-bezier(.16,1,.3,1)` (the brand "lux" curve),
  `--ease-soft`, `--ease-in`; z-index ladder `--z-header 100 / --z-sticky 200 / --z-whatsapp 300 /
  --z-overlay 400 / --z-drawer 410 / --z-modal 420 / --z-toast 500`; `--header-h`, `--sticky-bar-h`.
  Note the plan (04b §17) names them `--sf-dur-1..4` / `--sf-ease-lux`; the shipped CSS uses the
  shorter names above. The ONE motion config file the brief asks for should mirror these values in JS
  and, if it adds CSS tokens, put them in a new last layer (`@layer motion`, declared in a separate
  `motion.css` so it sorts after `overrides`).

## 1. Hook points per concept

| # | Concept | File · element · existing class/attribute |
|---|---|---|
| 1 | Intro loader | `layout.php` `<body class="home">` — nothing exists yet. Monogram assets: `assets/img/brand/monogram-transparent-512.png` (124 KB PNG), `assets/img/logo-mark.svg` (165 KB — a **base64 PNG wrapped in `<svg><image>`**, no vector paths). `sessionStorage` pattern with try/catch already in `ui.js initAnnouncement` (`sf_ann_<hash>`). |
| 2 | Hero scent trails | `home.php` `<section class="hero">` → `.hero__media` (z0, `<picture>` with `.hero__img`, opacity via `--hero-opacity`), `.hero__scrim` (z1), `.hero__body` (z2: `.hero__mark` 400px lockup PNG 77 KB, `.hero__eyebrow`, `h1.hero__title.text-gold-grad#hero-title`, `.hero__sub`, `.hero__actions`, `.hero__trust`), `.hero__cue.js-hero-cue`. Entrance today = CSS `.hero__enter` + inline `style="--i:n"` (keyframe `hero-enter`, 120 ms stagger). `hero--empty` when admin sets no image. `body.home .hero` has `margin-block-start: -var(--header-h)`; header starts `.is-transparent` and flips via `.header-sentinel` (rootMargin -80px) in `reveal.js initHeader`. |
| 3 | Ingredient parallax | No ingredient art exists (`assets/img/icons/` is empty). Candidate hosts: `.hero` (behind `.hero__body`), `#scent-finder.section--glow` (has `--grad-gold-radial`), PDP `#composition`. Only mousemove handler today: `product.js initGallery` (`--mx/--my`, rAF-throttled, gated `(hover:hover) and (pointer:fine)`). |
| 4 | Pinned fragrance story | `product.php` `#composition` → `partials/notes-pyramid.php` `ol.pyramid.sf-reveal > li.pyramid__tier--top/heart/base > .pyramid__label + ul.pyramid__notes > li.pyramid__note`; `partials/meters.php` `.meters > .meter.sf-reveal[role=img] > .meter__seg.is-filled[style=--i]` (fill animates on `.is-visible`); seasons/occasions `.chips`. Bottle = `partials/gallery.php` `.gallery.js-gallery > .gallery__main[data-gallery-main] > .gallery__img` (desktop) / `.gallery__rail` snap rail (mobile). Only photos exist — no turntable frames or 3D model. |
| 5 | Collection fan | Home `#collections .rail.grid--mosaic.sf-reveal--stagger > a.collection-card(--large)`; `/collections` `collections-index.php` `.grid.grid--2.sf-reveal--stagger > article > a.collection-card--wide`; header mega panel `.site-header__panel-item`. Card anatomy (`partials/collection-card.php`): `.collection-card{overflow:hidden;isolation:isolate;aspect-ratio 3/4}` > `.collection-card__media > picture > .collection-card__img` (hover `scale(1.06)`), `.collection-card__scrim`, `.collection-card__body` (`__eyebrow`, `__name`, `__tagline`, `__count`, `__rule` — width animates 1.5→4rem). Mobile swipe carousel already exists: `.rail` (`scroll-snap-type:x mandatory`, `--rail-w`). |
| 6 | Quiz | `quiz.php`: one GET `<form class="form js-validate stack" action="/scent-finder/result">` with 5 `fieldset.form__section#question-N` each holding `.progress` (`--fill`), `legend.form__legend`, `.choice-group--answers[role=radiogroup] > label.choice--answer > input.choice__input[name=qN] + span.choice__card (.choice__icon .choice__title .choice__text)`, `.field__error#err-qN`, hash links `#question-N±1`, one submit `.btn--primary`. Result: `quiz-result.php` (see §6). |
| 7a | Magnetic buttons | `.btn` (`overflow:hidden`, `::before` gold sheen via `background-position`, `:active translateY(1px)`), `.btn--primary` gradient `background-position` hover. Do not add transforms to `.btn__label` on `.js-add-to-cart` while `is-loading` (spinner). |
| 7b | Custom cursor | Global; gate exactly like `product.js`: `matchMedia('(hover: hover) and (pointer: fine)')`. Product hover targets: `.product-card` (link `::after` covers the card, `z-index:1`; `.product-card__action` sits at `z-index:2`), `.collection-card`, `.gallery__open` (`cursor: zoom-in`). |
| 7c | Gold underline draw | Already implemented for `.btn--text` (`background-size 0→100% 1px`) and `.section-header__link`; nav uses `.site-header__link.u-track`. Extend, don't duplicate. |
| 7d | Add-to-cart burst + fly | `cart.js add(sizeId, qty, button, source)` → `request('/api/cart/add')` → on ok `openDrawer(button)` (drawer `#cart-drawer.js-cart-drawer`, overlay `.js-cart-overlay`), `render()` updates `.js-cart-count` and `SF.bump(bubble,'is-bumped')` (`@keyframes count-bump`). Public API `SF.cart = {add, open, refresh, render}`. Events available: `sf:dialog-open` / `sf:dialog-close` (bubbling, on the panel). Target icon: header `a.site-header__cart.js-cart-open > .site-header__count`. Forms: `.js-add-to-cart-form` (PDP `#add-to-cart-form`, product cards with a single size, quiz result hero), button `.js-add-to-cart`; sticky bar button uses `form="add-to-cart-form"`. |
| 7e | Number tick-up | `.price__now` (cards, PDP `[data-price-now]`, sticky `[data-sticky-price]`), `.collection-card__count`, `.stars__count`, `.meter__value`, listing "N fragrances". `product.js applySize` rewrites `[data-price-now]` and fires `document` event `sf:size-change {size, product}`. |
| 8 | Smooth scroll + transitions | Native scroll everywhere; `html{scroll-behavior:smooth}` in `@layer reset` (line 229). Scroll consumers: `reveal.js` (3 IntersectionObservers + rAF scroll listener hiding `.site-header.is-hidden` past 240px), `ui.js lockBody/unlockBody` (`body.is-locked{overflow:hidden}`, saves/restores `pageYOffset` with `window.scrollTo`), `product.js` (`scrollIntoView`, `rail.scrollTo`), `forms.js` (`scrollIntoView` to first invalid), `initHeroCue`. All navigation is full-page; forms POST and redirect (303). |

## 2. What must NOT change

- **Buying path is server-first.** `.js-add-to-cart-form` posts to `/api/cart/add` with a `_csrf`
  field; without JS `api/cart.php` `cart_api_respond()` flashes and 303-redirects back. Cart page
  forms carry `data-cart-reload` (JS reloads instead of re-rendering). Checkout (`/checkout` POST),
  `/track`, `/contact`, `/product/{slug}/review` are plain form POSTs. Never `preventDefault` a
  submit outside the selectors `cart.js`/`forms.js` already own; never delay the request behind an
  animation. Price, size chips, Add to Cart, WhatsApp, COD trust row must be visible at first paint
  (no `.sf-reveal` on `.split__aside`, the sticky bar, `.price`, or anything on `/cart`, `/checkout`,
  `/track`, `/order/*` — 04b §18 rule; cart.php has one `.trust.sf-reveal` today, leave it).
- **CSRF contract.** `<meta name="csrf-token">` in `layout.php`; `SF.fetch` → `SF.ensureCsrfToken`
  (refreshes via `GET /api/session` when the meta is empty on a cached page) → header `X-CSRF-Token`,
  plus `X-Requested-With: fetch` and `Accept: application/json`. Every API JSON reply includes a fresh
  `csrf`. Motion code must call `SF.fetch`, never raw `fetch`, if it ever talks to the server.
- **Dialog contract (`ui.js`).** `SF.openDialog(panel,{trigger,overlay,onClose})` /
  `SF.closeDialog`: `hidden` → reflow → `.is-open`; close waits for `transitionend` with a
  400 ms fallback; focus trap, Escape, `aria-expanded`, body lock. Keep `.drawer`/`.modal`/
  `.nav-mobile` transitions on `transform`/`opacity` and never longer than that fallback.
- **`html.js` gate + no-JS guarantee.** Anything motion hides at rest must be hidden only under
  `html.js` (or a new `html.motion` class set by the motion bootstrap) and shown by
  `prefers-reduced-motion`. `reveal.js` `showAll()` behaviour is the reference.
- **Class contracts other files read:** `.sf-reveal/.is-visible`, `.is-open`, `.is-locked`,
  `.has-sticky-bar`, `.is-raised` (toast region), `.is-solid/.is-transparent/.is-hidden` (header),
  `.is-visible/.is-suppressed` (sticky bar), `.is-loading` (buttons), `[data-size-input]`,
  `#sf-sizes` JSON. Do not rename; add new hooks as `data-motion-*` attributes.
- Response shapes of `/api/cart*`, the size JSON, the quiz URL scheme `?a=1-2-3-4-5`.

## 3. Current byte budget and what already animates

| Asset | Raw | gzip |
|---|---|---|
| `site.css` | 149,375 B | 21,959 B |
| `reveal.js` 3,695 · `ui.js` 19,167 · `cart.js` 13,042 · `product.js` 11,491 · `forms.js` 11,881 | 59,276 B | 16,348 B |
| Fonts (3 variable woff2, incl. an italic the plan excluded) | 127,476 B | — |
| Hero lockup PNG (`lockup-transparent-400.png`, also the header logo) | 76,668 B | — |

The plan's budgets (07 §B.2.7: JS ≤ 15 KB uncompressed, stylesheet ≤ 60 KB) are already exceeded
~4× and ~2.5×; the realistic baseline to protect is the gzip line (~38 KB CSS+JS). Pre-existing
perf gaps worth fixing alongside motion: `head-meta.php` preloads `jost-400.woff2` and
`cormorant-garamond-300.woff2`, files that do not exist (so no font preload actually fires);
`.sf-reveal` sets `will-change` permanently at rest on every reveal node.

Approximate vendored additions (verify on download): GSAP core ~70 KB / ~28 KB gz, ScrollTrigger
~45 KB / ~15 KB gz, Lenis ~10 KB / ~4 KB gz, Three.js module ~680 KB / ~170 KB gz + bloom addons.
Three.js therefore cannot be on the mobile critical path at all (4G target); GSAP+ST roughly
doubles today's JS transfer and must be `defer`red and page-gated.

Already animated (CSS only, 96 transitions / 6 keyframes): hero entrance stagger, hero cue bob,
header solidify/hide, `.sf-reveal` fade-up (600 ms, stagger 70 ms × 8), card/collection/gallery
image zoom on pointer devices, card second-image cross-fade, `.btn` sheen + gradient shift +
text underline draw, drawer/modal/nav slide, toast slide, cart count bump, meter segment fill,
skeleton sheen, announcement marquee, size-chip/choice border+fill. One `@media (prefers-reduced-motion)`
block (line 5221) zeroes all durations and re-enables the static states.

## 4. CSP facts (`app/lib/response.php response_csp()`, sent on every storefront response)

```
default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self';
script-src 'self' 'sha256-/x7W7R75k8Roq0WaVRQX9blP4OufE5xbAdzklGxsgpw='; style-src 'self' 'unsafe-inline';
font-src 'self'; img-src 'self' data:; connect-src 'self'; frame-src 'none'; [+ upgrade-insecure-requests in prod]
```

- Vendored `gsap.min.js`, `ScrollTrigger.min.js`, `lenis.min.js`, `three.module.js` as same-origin
  files under `assets/js/vendor/`: **allowed** (`script-src 'self'`). CDNs: blocked.
- `<script type="module" src="…">` and dynamic `import('./hero-three.js')`: **allowed** (same-origin).
  Relative bare-path imports inside vendored ESM (`import … from 'three'`) are NOT resolvable
  without an import map — rewrite to relative paths when vendoring, or use the UMD/IIFE builds.
- Inline `<script type="importmap">` is treated as a script by CSP: **blocked** unless its exact
  SHA-256 is added next to `CSP_BOOTSTRAP_SCRIPT_HASH`. Feasible but brittle (any byte change breaks
  it). Recommendation: no import map.
- Inline data blocks `<script type="application/json">` (the `#sf-sizes` pattern) are not
  executed and pass CSP — use one for per-page motion data (e.g. note colours, collection meta).
- `'unsafe-eval'`: **not needed**. GSAP, ScrollTrigger, Lenis and Three.js core do not use
  `eval`/`new Function`; WebGL shader compilation is not CSP-governed. Do not add it.
- `style-src 'unsafe-inline'` already present: GSAP's inline `style` writes and `style="--i:n"`
  attributes are fine. `img-src 'self' data:` — Three textures must be same-origin files or data
  URIs (`blob:` is not allowed; avoid `createObjectURL` texture paths). Workers/Wasm fall back to
  `default-src 'self'` — same-origin only. Video fallback for the hero: `media-src` falls back to
  `default-src 'self'` — a same-origin MP4/WebM works.
- Loading order in `layout.php` is the only place to add `<script>` tags; anything inline beyond
  the `html.js` snippet requires a new hash constant.

## 5. Risks and collisions

1. **Two reveal systems.** `reveal.js` + `html.js .sf-reveal` (IO, one-way, 12 % rootMargin) vs
   GSAP ScrollTrigger. Running both on the same node double-animates. Decide per section: either
   ScrollTrigger takes over a node by removing `.sf-reveal` at boot (before first paint of that node
   — do it synchronously in the motion bootstrap, not after load) or ScrollTrigger is used only for
   pinning/scrub and `.sf-reveal` keeps the fades. Never let motion code add `.is-visible` late; the
   sticky-bar and header sentinels share `reveal.js` and must keep running.
2. **Lenis vs sticky header, drawers, body lock, anchors.** Header is `position: sticky` and
   hides via a rAF `scroll` listener — works with Lenis only in native-scroll mode (never the
   wrapper/transform mode, which also breaks `position: fixed` for `.sticky-bar`, `.drawer`,
   `.toast-region`, `.whatsapp-fab`). `body.is-locked{overflow:hidden}` does not stop Lenis wheel
   handling → call `lenis.stop()/start()` on `sf:dialog-open/close`; `unlockBody` uses
   `window.scrollTo` → mirror with `lenis.scrollTo(y,{immediate:true})`. Inner scrollers need
   `data-lenis-prevent`: `.drawer__body`, `.nav-mobile`, `.modal__body`, `.lightbox__rail`,
   `.rail`, `.gallery__rail`, `.suggest`. `html{scroll-behavior:smooth}` fights Lenis — set `auto`
   when Lenis is active. Hash links (`.hero__cue`, quiz Back/Next, `#reviews`, `#sizes`) and
   `scrollIntoView` calls must be routed through Lenis or verified to re-sync. Lenis on touch
   should stay off (native momentum is better on mid-range Android and cheaper).
3. **ScrollTrigger pin vs sticky add-to-cart bar.** `.sticky-bar` is `position: fixed` and shown
   when `.js-sticky-anchor` (the main Add to Cart button) scrolls above the viewport. Pinning
   `#composition` inserts pin-spacers that change page height and, if `pinType: transform` is used
   (Lenis wrapper mode), breaks fixed positioning. Use `pinType: fixed` (native scroll), call
   `ScrollTrigger.refresh()` after accordion toggles, drawer close, `sf:size-change`, image loads;
   never pin anything containing the add-to-cart form or the price. `body.has-sticky-bar` adds
   bottom padding — account for it in `end` calculations.
4. **Custom cursor vs touch.** Must be created only under `(hover:hover) and (pointer:fine)`
   AND not under reduced motion; hybrid devices flip mid-session → listen to the media query
   `change`. Never `cursor:none` on form controls or inside `.drawer/.modal` (focus users);
   `.product-card__link::after` covers the card, so hover targeting should use the card, not the link.
5. **Page transitions vs PHP navigation.** Every route is a full load; POST forms redirect with
   303 (`/checkout`, `/track`, `/contact`, reviews, cart forms). Intercept only same-origin, left
   click, no modifier, `GET`, non-`#`, non-`target=_blank` `<a>` (wa.me and Instagram open new
   tabs), never `.js-cart-open`, `[data-dialog-open]`, `[download]`, `/admin`. Exit veil = opacity
   only, ≤ 300 ms, then `location.href`. bfcache: reset the veil on `pageshow` (`event.persisted`)
   or a back-navigation shows a stuck mist. The entrance veil must not delay LCP (hero image is
   preloaded with `fetchpriority=high` on home; the loader must sit *over* a painted hero).
6. **Loader vs LCP and the no-JS gate.** The stroke-draw needs a real vector monogram — none exists
   (both SVGs are wrapped bitmaps). Trace from `brand/logo-original.jpg` first. Show the loader only
   when `html.js` is set, cap at ~1.2 s, first visit per `sessionStorage` (same key style
   `sf_intro`), skip entirely under reduced motion / `saveData` / low `deviceMemory`.
7. **Cart flight vs drawer.** `add()` opens the drawer immediately on success; the drawer
   (z 410) and overlay (z 400) cover the header icon (z 100). A flight needs `cart.js` to
   await an optional `SF.motion.onAdded(button)` promise (or fire `sf:cart-added` and defer
   `openDrawer` by the flight duration) — a small, explicit edit; also honour the `pending` guard.
8. **Ticking prices vs conversion/accessibility.** Prices are strings ("Rs. 6,500") swapped by
   `product.js` on size change and read by screen readers; animating them delays the number the
   brief says must be visible immediately. Recommend ticking only non-price numbers (counts,
   meter words) or an `aria-hidden` visual duplicate with the real value in the DOM at once.
9. **Hero on 4G Android.** Three.js is ~170 KB gz before textures; load it only after `load` +
   idle, on `min-width: 1024px`, `pointer: fine`, `deviceMemory ≥ 4`, no `saveData`, WebGL2
   available; otherwise the CSS/SVG ribbons. Respect `.hero--empty` and the admin-set overlay
   opacity; keep `<h1>` in DOM (LCP/SEO), canvas `aria-hidden`.
10. **Assets that do not exist yet:** ingredient cutouts, monogram vector, bottle turntable frames,
    per-note mood colours (only `scent_family`, notes text and `longevity/sillage` 1–5 exist).

## 6. Quiz: current structure and data for a dramatic reveal

- `app/controllers/quiz.php`: 5 questions (`QUIZ_QUESTIONS`: who / where / smell / presence /
  when), all rendered on one page; `forms.js` validates (only the first radio of each group carries
  `required`). Submit is GET → controller redirects to `/scent-finder/result?a=1-2-3-4-5` (302,
  regex `^[1-5](?:-[1-5]){4}$` hard-codes five answers; changing the count changes share URLs).
- Scoring is a single SQL expression over `products` (`gender` ±, `occasion` words +5,
  `scent_family` +8, notes text +4, `sillage` distance to target, `best_season` +4, `longevity`
  ±2), in-stock only, top 3, `score > 0`; fillers by `sales_count` when fewer than 3.
  Max theoretical score 33 — the numeric `score` is fetched but not passed to the view (easy to
  expose as a "match strength" for a meter or count-up).
- Result view data (`quiz-result.php`): `heading`, `readout` ("Deep and smoky, made to be
  remembered."), `answers[]`, `answerCode`, `hero` = `{product{name,slug,url,gender,scent_family,
  short_description,collection_name/slug,rating_avg/count}, image_set (thumb/card/zoom jpg+webp),
  alt, notes{top[],heart[],base[]}, meters[{label,value 1–5,word,aria}], size_labels[], sold_out,
  single_size_id, is_sale, price_display, was_display, from}`, `alsoTrying[2]`, `fillers[]`,
  `shareUrl`, `retakeUrl`, `whatsappUrl`. Add to Cart there is the same `.js-add-to-cart-form`
  (single size) or a "Choose a Size" link — already conversion-ready, no change needed.
- Card-flip enhancement can be pure progressive enhancement on `quiz.php`: with JS, show one
  `fieldset.form__section` at a time and flip between them (3D `rotateY` on `.choice-group` or the
  fieldset), Back/Next already exist as anchors; without JS the stacked form still submits. The
  reveal then plays on the result page load (full navigation) using `hero` — a sensible order is
  mist → `.product-card__media` scale/opacity → `text-gold-grad` heading → notes line → meters fill
  (`.meter.sf-reveal` already animates) → price and CTAs visible from the first frame.
