# Motion layer — performance review (mid-range Android on 4G)

Reviewer: performance, 2026-09-28. Scope: `docs/motion/run-report.md`, `lighthouse/*.report.json`,
`motion-check.json`, `assets/js/motion/*.js`, `assets/js/intro.js`, `assets/css/motion/*.css`,
vendor sizes, plus a Playwright probe (Pixel 5 profile, 360×780, DPR 3, and a 1440 desktop) against
`php -S 127.0.0.1:8091`. Review only; nothing edited. Sizes are gzip -9 unless marked raw.

## 0. Numbers

| Asset | raw | gz | reaches phones? |
|---|---|---|---|
| `vendor/gsap.min.js` 3.13.0 | 72,435 | 28,182 | no (verified: never requested on the mobile profile) |
| `vendor/ScrollTrigger.min.js` 3.13.0 | 44,157 | 17,841 | no |
| `vendor/lenis.min.js` | 18,722 | 5,431 | no (flag `false`, desktop-only guard) |
| `motion/ribbons-gl.js` (in place of Three.js 168 KB gz) | 13,584 | 4,334 | no |
| `motion/config.js` (sync, `<head>`) | 11,133 | 4,113 | **yes, parser-blocking, every page** |
| `intro.js` (sync, `<head>`) | 6,675 | 2,287 | **yes, parser-blocking, every page** |
| `css/motion.css` (render-blocking on motion pages) | 31,931 | 6,122 | yes |
| `motion/core.js` (module, defer) | 16,454 | 5,149 | yes |
| `motion/micro.js` | 15,780 | 4,673 | yes, every non-commerce page |
| `motion/hero.js` | 8,848 | 2,815 | yes (home) |
| `motion/story.js` | 7,013 | 2,256 | yes (PDP) |
| `motion/quiz.js` | 8,759 | 2,783 | yes (quiz) |
| `img/motion/ribbons-a-540.webp` | 14,508 | — | yes (home, after load) |
| `img/motion/ribbons-b-540.webp` | 14,372 | — | mobile-high only |
| `img/brand/monogram-transparent-256.webp` | 14,822 | — | **yes, twice, every page** |

Mobile home, first visit, motion-added bytes: before first paint 13.8 KB (config 4.5 + intro 2.7 +
motion.css 6.6; PLAN §6.1 budget ≈ 5.7); after load 13.8 KB JS (PLAN ≈ 2.3); images 44.7 KB
(plate 14.7 + monogram 15 × 2; PLAN plates ≤ 32 + monogram 6, home only). Total ≈ 72 KB
(87 KB on mobile-high) against a planned ≈ 46 KB. Mobile PDP: ≈ 57 KB added, of which 33 KB is the
intro (monogram twice + intro.js) that PLAN §2.2 says should not exist on a product page.

Lighthouse mobile (gzip server): `/` 98, LCP 2.4 s (element = the intro monogram), FCP 1.2 s,
TBT 0, 387 KB / 41 requests; `/product/azure-oud` 96, LCP 2.7 s (gallery zoom image), TBT 0,
466 KB / 30 requests. Render-blocking insight: site.css 24.2 KB, motion.css 6.6 KB, config.js
4.5 KB, intro.js 2.7 KB (est. 303–603 ms each, overlapping). Main thread: intro.js 280 ms total /
112 ms scripting at 4× CPU throttle; one 65 ms long task at 608 ms on `/`.

## 1. Findings, ranked

### 1. HIGH — The intro runs in `full` mode on every page and every device; the `loader` kill switch is dead

Verified on the Pixel profile: `html.motion--mobile sf-intro-full` on `/` **and** `/product/azure-oud`;
`#sf-intro` (opaque `#0A0A0A`, z 600) is removed 1,940 ms after `load`. With
`localStorage.sfMotionFlags = {"loader":"desktop"}` nothing changes — `intro.js` reads
`SF_MOTION.intro` only, never `SF_MOTION.flags.loader`, so PLAN §6.4 switch #5 cannot be pulled.
`layout.php` includes `intro.php` unconditionally, so a first visit that lands on `/cart` or
`/checkout` (WhatsApp/Instagram deep links, the most common Pakistani entry) also gets ~2 s of
black before the form. On `/` the monogram is the LCP element at 2.4 s with 0.1 s of headroom;
on a real Snapdragon 6xx the 260-particle canvas loop (112 ms scripting under 4× throttle) lands
on the same main thread as the hero paint. The intro itself is client-approved; what follows is
tuning, not redesign.
- Fix (config-level, allowed): in `intro.js`, after computing `mode`, read
  `motion.flags && motion.flags.loader`; if it is `'desktop'` and
  `!matchMedia('(min-width:1024px) and (hover:hover) and (pointer:fine)').matches`, use `calm`
  (or return before adding any `sf-intro-*` class); if `false`, return. Ship `flags.loader: 'desktop'`
  in `config.js`. Expected: mobile LCP on `/` moves back to the h1 (painted at first paint,
  `opacity 1`, verified), monogram + intro.js + canvas work disappear from every phone.
- Fix (PLAN §2.2, needs the client's nod): render `intro.php` only on `body.home`; `sessionStorage`
  → `localStorage` 7-day stamp so in-app webviews do not replay it per link.

### 2. HIGH — The monogram is downloaded twice on every page (30 KB, PLAN budgeted 6 KB on home only)

`intro.php` prints `<img src="…/monogram-transparent-256.webp?v=…">`; `site.css`
`.sf-intro__mark-wrap::after` uses `mask: url('../img/brand/monogram-transparent-256.webp')`
(bare URL). Different URL → different cache entry → both fetched (Lighthouse: 14,968 B at 25 ms
Medium and 14,968 B at 50 ms High, both pages; the probe shows the same pair). The `::after`
exists as soon as any `sf-intro-*` class is set, so the mask fetch happens even in `quick` mode,
i.e. on every page of every session, including PDP and checkout. The file is also 256 px /
14.8 KB where PLAN §6.3 asked for 200w ≈ 6 KB.
- Fix: one URL for both (either drop `?v=` on the `<img>` — the file is `immutable, max-age=1y`
  and a redesign would be a new filename anyway — or move the shimmer mask to an inline
  `style` attribute in `intro.php` using `asset()`); re-encode at 200w q≈75. Saves 15–24 KB
  per page on 4G (≈ 0.1 s at 1.6 Mbps) and one High-priority request during the LCP window.

### 3. HIGH — `.sf-reveal` keeps `will-change: transform, opacity` at rest: 16 permanent compositor layers on the mobile home page

`site.css` line 5225 (`html.js .sf-reveal { … will-change: transform, opacity }`) still ships;
PLAN §6.1 explicitly lists removing it. On the Pixel profile 16 `.sf-reveal` containers are
promoted before they ever animate, including `rail grid--mosaic` (360×374), `rail rail--cards`
(360×452), `grid grid--products` (317×732, 8 product cards with images), `grid grid--tiles`
(317×507), `trust` (317×433). At DPR 3 their combined backing would be ≈ 49 MB if fully
rasterized; Chrome tiles and prunes, but the layer count, the promoted image-heavy grids and
the invalidations on every lazy image decode are exactly the Adreno 610 memory/jank profile
the plan set out to avoid. `.sf-reveal--stagger` containers get the layer too although they
never animate themselves (`opacity:1; transform:none; transition:none`).
- Fix (site.css, one rule): drop `will-change` from `.sf-reveal`; a 600 ms opacity/transform
  transition is promoted by the compositor for its own duration. If a hint is wanted, have
  `reveal.js` add a class one frame before `is-visible` and remove it on `transitionend`.

### 4. MEDIUM-HIGH — Two synchronous scripts in `<head>` before `site.css`, 6.8 KB gz, on every page

`config.js` (11.1 KB raw / 4.5 KB gz) is ~80 % desktop-only data (ribbon paths, GSAP eases,
cursor, magnetic, quiz flips, hue tables) that a phone never reads before `load`; only
`classify()` + `applyClass()` (≈ 40 lines) must run before first paint. `intro.js` (2.7 KB gz)
only needs to add `sf-intro-<mode>` synchronously; the particle code can be deferred. Lighthouse
attributes 303 ms of simulated blocking to each and 112 ms of scripting to intro.js. PLAN §5.2
wanted a ≤ 0.4 KB hashed inline gate + a JSON block.
- Fix: split `config.js` into `gate.js` (classify, applyClass, intro-mode class; ≤ 0.6 KB gz,
  sync) and `config.js` (data, `defer`, or `<script type="application/json">`); make `intro.js`
  `defer` and read the mode class the gate set. Saves ≈ 6 KB gz and two parser-blocking
  executions per page; on a 150 ms RTT link that is the difference between first paint at the
  first and the second CSS round trip when the preload scanner is late.

### 5. MEDIUM — `motion.css` is render-blocking on phones but ~2/3 of it is desktop-only

Of 31.9 KB raw, roughly 20 KB sits under `html.motion--desktop`, `@media (min-width: 1024px)`
or the view-transition media query (fan poses, tilt/sweep, cursor, magnetic, story desktop
layout, `@view-transition`, veil). A phone downloads and parses all of it before first paint
(insight: 6.6 KB gz, est. 453 ms).
- Fix: assemble two files from `assets/css/motion/*.css`: `motion.css` (core, plates, hero settle,
  rail progress, story lite, quiz slide, tick/burst) and `motion-desktop.css` linked with
  `media="(min-width:1024px) and (hover:hover) and (pointer:fine)"` — a non-matching media
  stylesheet is fetched at low priority and never blocks rendering. ≈ 4 KB gz off the mobile
  critical path, zero behaviour change.

### 6. MEDIUM — Fonts: 88 KB, High priority, before LCP (PLAN Stage 0 asked for ≈ 40 KB)

`jost-variable.woff2` 50.5 KB + `cormorant-garamond-variable.woff2` 37.9 KB are preloaded
(correctly now, once) but not subset. They are the two largest requests on both mobile pages and
sit in the same High queue as `site.css`. Pre-existing, but the biggest single byte lever left:
`pyftsubset --unicodes="U+0000-00FF,U+20A8,U+2013-2014,U+2018-201D,U+2026" --layout-features=*`
gets each to ≈ 18–22 KB → ≈ 48 KB saved ≈ 0.25 s at 1.6 Mbps.

### 7. MEDIUM — 130–245 KB of images phones never see

- Home mobile: eight `product-card__img--alt` hover images (`*-2-card.jpg`, 15–19 KB each,
  ≈ 130 KB total) are lazy-loaded within Chrome's lazy threshold on a touch device where the
  hover cross-fade can never fire. Fix: `<picture><source media="(hover:none)"
  srcset="data:image/gif;base64,R0lGODlhAQABAAAAACw=">` around the alt image, or let the card
  module set `src` on desktop only.
- PDP mobile: `azure-oud-2-zoom.webp` 58 KB + `-3-zoom.webp` 57.5 KB (rail slides off to the right)
  load Low at 44 ms; the LCP slide is the 1400w zoom (52.8 KB) because `sizes="100vw"` at
  DPR 3 = 1080 px > 600w. Fix: `loading="lazy"` + `fetchpriority="low"` on slides 2+, and a
  1000w rendition so 360 px phones pick ≈ 30 KB instead of 53 KB for LCP.
- These are pre-existing and not motion's, but they are 30–50 % of the mobile page weight.

### 8. MEDIUM — Non-hero section modules load at DOMContentLoaded, not after `load` + idle, on phones

`scheduleSections()` observes each `[data-motion]` root with a 60 % rootMargin; `body`
(`data-motion="micro"`) and a PDP `#composition` a viewport away intersect immediately, so
`micro.js` (5.1 KB gz, High priority) and `story.js` are fetched during the LCP window
(Lighthouse: micro.js at 117 ms on `/`, 66 ms on PDP, before `load`). PLAN §5.2: "import()s per
page after load + requestIdleCallback". On `low` devices `micro.js` still loads to do nothing
(`burst.low: 0`, tick only) and `hero.js` loads and adds no plate.
- Fix: in `scheduleSections`, wrap the IntersectionObserver creation in `afterLoadIdle` when
  `motion.device !== 'desktop'`; skip `micro` and `hero` entirely for `device === 'low'`.

### 9. LOW-MEDIUM — Hero plates animate for 84 s, not 3 cycles

`config.js css['plate-cycles'] = '6'` feeds `--sf-plate-cycles`, so `sf-plate-drift` runs
6 × 14 s = 84 s and `sf-plate-fade` 6 × 12 s = 72 s while the hero is ≥ 10 % visible (verified
`running` on the Pixel profile, `paused` when scrolled away — the IO pause works). PLAN §2.1 says
3 cycles so "the compositor can sleep"; `hero.plates.cycles: 3` exists in config but nothing reads it.
- Fix: `'plate-cycles': '3'` in `config.js css` (keep it even so `alternate both` ends at rest).

### 10. LOW — Desktop: the GSAP ticker never sleeps once ScrollTrigger is registered

Probe: 124 rAF calls/s on `/` and on the PDP while idle with the hero in `plates` state, all
tweens complete and the fan triggers killed; `ScrollTrigger` keeps `gsap.ticker.add(...)` alive.
Cost is ≈ 0.05 ms/frame but it stops the renderer from going idle on battery. Desktop only.
- Accept, or on home call `ScrollTrigger.disable()` once the fan has settled and the hero has
  ended (PDP keeps 4 live triggers legitimately). `pins: 0` everywhere — verified.

### 11. LOW — Small consistency items

- `intro.js` re-classifies the device (`/2g/` misses `3g`, which `config.device.slowTypes`
  counts as slow) instead of reading the `motion--low` class `config.js` already set → a 3G
  visitor gets the full particle intro. Read `root.classList.contains('motion--low')`.
- `ribbons-gl.js` asks for `powerPreference: 'high-performance'`: on dual-GPU laptops that wakes
  the discrete GPU for a background flourish. Default or `'low-power'` renders four tubes fine.
- `hero.js` `addPlate` draws the WebP into a `<canvas>` via `drawImage` (synchronous decode on
  the main thread); `img.decode().then(...)` or a plain `<img>` moves decoding off-thread.
- `micro.js` tick: each ticked number runs its own 900 ms rAF loop writing `textContent` every
  frame (bounded; 116 rAF/s observed for ~1 s after the counts scroll in). Fine, but on mobile
  the gsap-less fallback could skip frames at 30 fps for the same look.
- `head-meta.php` runs two `glob()`s and ~12 `filemtime()`s per request to build the module
  version; cache it in the settings table or compute once per deploy.

## 2. Kill-switch audit (PLAN §6.4)

| # | Switch | Where | Works? | Runtime override via `localStorage.sfMotionFlags`? |
|---|---|---|---|---|
| 1 | `hero.webgl` | `config.hero.webgl` | yes — `hero.js` falls back to plates | no (nested key; needs a deploy) |
| 2 | `layers` | `flags.layers` | inert — no layers module exists yet | n/a |
| 3 | `lenis` | `flags.lenis` | yes (ships `false`; `initLenis` also guards `device !== 'desktop'`) | yes |
| 4 | `fan.scrub` / `fan` | `config.cards.fan.scrub` / `flags.fan` | yes; with `fan:false` the CSS rest pose sits fanned for 6 s then `sf-fan-failsafe` settles it | scrub: no; fan: yes |
| 5 | `loader` | `flags.loader` | **no — never read by `intro.js`** (finding 1) | would be, once wired |
| 6 | `cursor`, `magnetic` | `flags.*` | yes (`micro.js` `allows()`) | yes |
| 7 | `transitions` | `flags.transitions` | yes for the JS veil; `@view-transition` CSS stays (as specified) | yes |
| 8 | `story.tilt` | `config.story.tilt.enabled` | yes | no |
| 9 | `hero.plates.mobileHigh` | `config.hero.plates.mobileHigh` | yes | no |

Also verified: `flags.hero:false` / `flags.micro:false` stop those modules from being fetched at
all on mobile (probe request list: config, intro, motion.css, core, monogram ×2 only).

## 3. Verified and kept

- Nothing heavy reaches phones: GSAP, ScrollTrigger, Lenis and `ribbons-gl.js` are never
  requested on the mobile profile (probe + Lighthouse request lists agree). Commerce pages
  (`cart`, `checkout`, `track`, `confirmation`) load no section module.
- No ScrollTrigger pins anywhere (PDP: 4 triggers, 0 pins; story figure is CSS `sticky`);
  one `ScrollTrigger.refresh()` during a three-step PDP scroll, debounced 150 ms.
- Lenis: desktop-only guard plus `flags.lenis:false`, `syncTouch:false`, `data-lenis-prevent`
  on inner scrollers, stop/start on `sf:dialog-open/close`. Cursor: created only under
  `motion--desktop` (hover + fine pointer), ignores non-mouse `pointerType`, torn down on the
  `device` event. Magnetic never touches `.btn--primary`, `.js-add-to-cart`, the sticky bar or
  cart/checkout links.
- Hero GL: dpr ≤ 1.5, `antialias:false`, no depth/stencil, IO pause below 5 %, `visibilitychange`
  and `pagehide` end it, 120-frame mean > 20 ms twice → dispose, `webglcontextlost` → dispose;
  `dispose()` deletes VAOs/VBOs/IBOs/programs and calls `WEBGL_lose_context`. rAF is 0/s on
  phones once the intro is gone (probe), and the mobile plate animations pause off-screen.
- Passive where it matters: `pointermove`, `scroll`, `resize`, `wheel`, `touchstart`,
  `pointerdown` are all `{passive:true}`; the only layout reads on pointer paths are
  rAF-throttled `getBoundingClientRect` on one card / a cached rect set.
- TBT 0 ms on both mobile pages; CLS 0; h1 painted at `opacity:1` in place at first paint.
- Same-size `canvas.width` reassignment on `refresh` does not clear the WebGL buffer in
  Chrome (tested), so the per-image-load `layout()` in `hero.js` is cheap.

## 4. Suggested order

1 (wire `flags.loader`, ship `'desktop'`) → 2 (single monogram URL, 200w) → 3 (drop
`will-change` at rest) → 4 + 5 (sync gate split, desktop CSS behind `media=`) → 6 + 7
(fonts, hover/gallery images) → 8 → 9 → 10/11. Items 1–3 need no design change and together
remove ≈ 45 KB and two long main-thread stretches from every mobile first visit.
