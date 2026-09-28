# Motion layer — foundation API

Paths relative to `site/`. This is the contract the section owners (hero, cards, story, micro,
quiz, transitions) build on. Anything not listed here is private to `assets/js/motion/core.js`.

## Files

| File | Role |
|---|---|
| `assets/js/motion/config.js` | `window.SF_MOTION` — the **one** config: tokens, flags, every section's timings, plus the first-paint device gate (`SF_MOTION.classify()` / `applyClass()`). Sync in `<head>` before `site.css`, so `html.motion--*` exists at first paint and CSS can key on it. |
| `assets/js/intro.js`, `app/partials/intro.php` | Client-approved loader, untouched; reads `SF_MOTION.intro`. |
| `assets/js/motion/core.js` | ES module (`<script type="module" defer data-motion-v>` in `head-meta.php`, executes before `reveal.js` in the deferred list). Device state, event bus, GSAP/ScrollTrigger bootstrap, Lenis bridge, the reveal system, the section loader. |
| `assets/js/reveal.js` | Unchanged behaviour, plus the `window.SFReveal` handshake (`claimed`, `started`, `start()`, `showAll()`); still runs alone when core is absent. |
| `assets/js/vendor/` | GSAP 3.13.0, ScrollTrigger 3.13.0, Lenis 1.3.26 (`vendoring.md`). |
| `assets/css/motion/00-core.css` … `60-transitions.css` | One file per owner, all inside `@layer motion` (sorts after `overrides`, so it wins normal declarations; it cannot beat the `!important` reduced-motion block in `site.css`, which is intended). |
| `assets/css/motion.css` | The assembled stylesheet, linked as `<link id="sf-motion-css">` **only** on `home`, `listing`, `collections`, `product`, `quiz` body classes. Rebuild with `cd assets/css && cat motion/*.css > motion.css` (numeric prefixes give the order). |

CSS assembly order: `00-core`, `10-hero`, `20-cards`, `30-story`, `40-micro`, `50-quiz`,
`60-transitions`. Owners write only their own file and never the assembled `motion.css`.

## Device classes

Set on `<html>` by `config.js` at first paint and re-evaluated by `core.js` on media-query change:

| Class | Meaning | `SF.motion.device` |
|---|---|---|
| `motion--desktop` | `min-width: 1024px` and `(hover: hover) and (pointer: fine)`, not reduced. The only class that ever loads GSAP, ScrollTrigger, Lenis. | `'desktop'` |
| `motion--mobile` | Everything else. IntersectionObserver + CSS only, 0 B of library. | `'mobile'` |
| `motion--mobile-high` | Added later to `motion--mobile` after `deviceMemory ≥ 6`, `hardwareConcurrency ≥ 8`, no `saveData`, and a passing 60-frame p95 check (≤ 1.3× the display interval) once the intro is gone and the page is idle. `SF.motion.mobileHigh === true`, event `device`. | `'mobile'` |
| `motion--low` | Mobile with `saveData`, `effectiveType` slow-2g/2g/3g, `deviceMemory ≤ 2` or `hardwareConcurrency ≤ 2`. | `'low'` |
| `sf-reduce` | `prefers-reduced-motion: reduce`. No `motion--*` class, `allows()` is always false, no section loads, reveals are shown at once. | `'reduced'` |

Extra fields: `SF.motion.touch` (no fine pointer), `SF.motion.slow`, `SF.motion.gpu`
(`'weak'` on desktop when `deviceMemory < 4` or cores `< 4` — the hero turns WebGL off on it),
`SF.motion.commercePage` (`body.cart/checkout/track/confirmation`: no sections, no Lenis, no
`motion.css`). The rAF benchmark is **not** a classification input (PLAN.md §2); use
`SF.motion.frameBudget(frames)` for runtime kills.

## `window.SF.motion`

```js
SF.motion = {
  device, reduced, touch, slow, gpu, mobileHigh, commercePage, version,
  config,            // === window.SF_MOTION
  flags,             // config.flags merged with localStorage.sfMotionFlags (QA override, JSON)
  gsap, ScrollTrigger, lenis,   // null until loaded; desktop only
  sections,          // name → Promise of the init() return values
  on(name, fn) → off, off(name, fn), emit(name, detail),
  allows(flag),      // true | 'desktop' (desktop only) | false; always false under reduced
  snapshot(),        // {device, reduced, touch, slow, gpu, mobileHigh}
  ensureGsap() → Promise<{gsap, ScrollTrigger} | null>,   // idempotent, null on non-desktop or timeout (8 s)
  initLenis() → Promise<Lenis | null>, destroyLenis(),
  loadScript(file) → Promise,           // same-origin file under assets/js/vendor/, versioned
  loadSection(name, roots) → Promise,   // manual trigger, same path as the loader
  scrollTo(target, {offset, immediate, block}),   // Lenis when active, else scrollIntoView/window.scrollTo; also SF.scrollTo
  refresh(delayMs),  // debounced ScrollTrigger.refresh() + 'refresh' event
  frameBudget(frames) → Promise<{mean, p95, interval}>,
  reveal(el)         // run the reveal contract on one node now
};
```

### Events

`SF.motion.on(name, fn)` and, mirrored for non-module scripts, `document` CustomEvents named
`sf:motion:<name>` with the same `detail`.

| Event | Detail | When |
|---|---|---|
| `ready` | snapshot | core booted (DOMContentLoaded). |
| `device` | snapshot | class changed on a media-query `change`, or Mobile-high was granted. |
| `gsap` | `{gsap, ScrollTrigger}` | libraries loaded and registered. |
| `lenis` | `{lenis}` (`null` on destroy) | Lenis created/destroyed. |
| `section` | `{name, roots, instances}` | a section module ran `init()` on every root. |
| `section-error` | `{name, error}` | import or `init()` threw (module missing, network). |
| `reveal` | `{el, engine: 'gsap' \| 'css'}` | one `.sf-reveal` node finished revealing. |
| `refresh` | — | after the debounced `ScrollTrigger.refresh()`. |
| `frame` | `{mean, p95, interval}` | the Mobile-high frame check ran. |

Existing site events core listens to: `sf:dialog-open/close` (Lenis stop/start, refresh),
`sf:size-change`, image `load` (capture), `resize`, clicks on `.js-accordion-trigger`,
`.js-footer-toggle`, `.js-expand` (refresh after 420 ms).

## Flags (`SF_MOTION.flags`)

`reveal: 'desktop'`, `hero: true`, `loader: true`, `fan: 'desktop'`, `story: true`,
`layers: 'desktop'`, `micro: true`, `magnetic: 'desktop'`, `cursor: 'desktop'`,
`cartFly: 'desktop'`, `tick: true`, `quiz: true`, `lenis: false`, `transitions: 'desktop'`.
Values: `true` (every class except reduced), `'desktop'`, `false`. Test with
`SF.motion.allows(SF.motion.flags.cursor)`. Kill-switch order is PLAN.md §6.4. QA can flip any flag
in one browser without a deploy: `localStorage.setItem('sfMotionFlags', '{"lenis":true}')` and
reload (this is how the Stage 9 Lenis audit runs; remove the key afterwards).

## Section modules

One file per section at `assets/js/motion/<name>.js`, `name` ∈ `hero | cards | story | micro |
quiz | transitions` (`SF_MOTION.loader.sections`). The loader finds every element carrying
`data-motion="<name>"` (space-separated tokens allowed, e.g. `<body data-motion="micro transitions">`),
checks the section's flag (`hero→flags.hero`, `cards→flags.fan`, `story→flags.story`,
`micro→flags.micro`, `quiz→flags.quiz`, `transitions→flags.transitions`), then imports the module
when the root nears the viewport (`rootMargin: 60%`) — `hero` instead after `load` + idle
(`requestIdleCallback`, 2 s timeout). On desktop `ensureGsap()` resolves first, so `SF.motion.gsap`
is set when `init` runs; on mobile/low it is `null` and the module must use CSS classes and
IntersectionObserver only (PLAN.md §2: 0 B of library on phones). Nothing loads on commerce pages
or under reduced motion.

```js
export default function init(root, SF) {
  const { device, gsap, ScrollTrigger, config, on } = SF.motion;
  const cfg = config.cards;
  if (device !== 'desktop' || !gsap) { return liteVersion(root, cfg); }
  ...
  return { destroy() {} };   // optional; stored in SF.motion.sections[name]
}
```

Rules every module honours: never hide price, Add to Cart, COD/payment text, nav or forms at rest;
animate only `transform`, `opacity`, `clip-path`, small-element `filter`; add `will-change` only
while a tween is live; call `SF.motion.refresh()` after anything that changes layout; listen to
`device` and tear down when the class changes; put every number in `config.js`, never in the module.

### Data attributes

| Attribute | On | Set by | Meaning |
|---|---|---|---|
| `data-motion="<names>"` | section root(s) | PHP view / partial | registers the root with the loader. Owned containers that GSAP animates are printed **without** `.sf-reveal` (one system per node). |
| `data-motion-v` | the `core.js` tag | `head-meta.php` | version for dynamic `import()` and vendor URLs (`?v=`), since `.htaccess` marks `.js` immutable. |
| `data-reveal-delay="1..5"` | `.sf-reveal` | views (existing) | × 70 ms delay, honoured by both engines. |
| `data-lenis-prevent` | inner scrollers | core at Lenis start (`config.scroll.prevent`) | wheel passes through to `.drawer__body .nav-mobile .modal__body .lightbox__rail .rail .gallery__rail .suggest`. |
| `data-motion-hue-top/heart/base`, `data-match`, `data-motion-step`, `data-motion-slot`, `data-tick` | per section | section owners | reserved names from PLAN.md; documented in each section's own file. |

Never rename the class contracts other scripts read (`.sf-reveal/.is-visible`, `.is-open`,
`.is-locked`, `.is-solid/.is-transparent/.is-hidden`, `.is-loading`); add hooks as `data-motion-*`.

## Reveal system

One observer per page. Under `motion--desktop` on a page that links `motion.css`, core sets
`window.SFReveal.claimed = true` before `reveal.js` runs, so `reveal.js` skips its own observer
(its header, sticky-bar and filter-bar sentinels keep running). Core observes every `.sf-reveal`
with the same `rootMargin: 0 0 -12%` and on entry: with GSAP present → adds `.sf-reveal--gsap`
(kills the CSS transition via `00-core.css`), tweens the node (or each child of
`.sf-reveal--stagger`, 70 ms stagger) from `{opacity: 0, y: 18px}` to rest in 600 ms `expo.out`,
adds `.is-visible` at tween start and clears the inline styles at the end — the DOM ends exactly as
`reveal.js` leaves it. Without GSAP yet (above the fold before idle, or a failed load) it adds
`.is-visible` and the CSS transition plays, identical to today. On every other class `reveal.js`
runs untouched. `SFReveal.showAll()` is the reduced-motion/no-JS reference and core calls it on a
live reduced-motion change.

## Lenis

Native-scroll mode only (`wrapper: window`, no transform wrapper), `lerp 0.1` (0.12 on Windows),
`smoothWheel`, `syncTouch: false`, `anchors: true`, `autoRaf: false` driven by `gsap.ticker`
(`lagSmoothing(0)`), `lenis.on('scroll', ScrollTrigger.update)`, `stop()` on `sf:dialog-open`,
`start()` on the last `sf:dialog-close`; `html.lenis{scroll-behavior:auto}` in `00-core.css`.
Ships **off**; desktop only; never on commerce pages. `SF.scrollTo` is defined by core when `ui.js`
has not defined it, so consumers can be routed through it before the flag flips.

## Verification (what passed on 2026-09-26)

Playwright (Chromium) on `/`, `/shop`, `/product/azure-oud` at 1440×900 and Pixel-5 375×812,
plus `/collections`, reduced-motion emulation, JS disabled, and `/cart`: zero console errors,
page errors or failed requests; every `.sf-reveal` node ends `.is-visible` with no inline styles
left (14/14 home, 8/8 PDP; GSAP engine on desktop, `reveal.js` on mobile); GSAP + ScrollTrigger
present on desktop only; Lenis present only on desktop with the flag on (`html.lenis`, stopped
while the drawer is open, restarted on close); header `is-solid` after scroll and `is-hidden` on
scroll-down; cart drawer opens, traps Tab, closes on Escape, unlocks the body; hero cue anchor
lands (`scrollY` 789 desktop / 690 mobile, with and without Lenis); the intro runs and removes
itself (`sf-intro-done`); with JS off the PDP paints price, Add to Cart and all `.sf-reveal`
content at full opacity and no intro veil.

## Follow-ups outside this foundation's ownership

- `layout.php` + `response.php`: inline the ~20-line gate from the tail of `config.js` with its
  SHA-256 beside `CSP_BOOTSTRAP_SCRIPT_HASH` and inline `SF_MOTION` as JSON — removes the sync
  `config.js` request (PLAN.md §5.2). Until then the gate is the last block of `config.js`.
- `intro.js` refit (home-only, `localStorage` 7-day stamp, Full/Calm, no quick mode) per PLAN §2.2/§4.
- `ui.js`: move `SF.scrollTo` there and route `unlockBody`, hero cue, quiz anchors, `forms.js`,
  `product.js` through it (PLAN §2.7).
- `home.php` / `product.php` / `quiz.php`: add `data-motion` roots and print owned containers
  without `.sf-reveal`.
