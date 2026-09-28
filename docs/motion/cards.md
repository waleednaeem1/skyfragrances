# Motion — cards section (collection fan, card hover, mobile rails)

Owner files (paths relative to `site/`): `assets/js/motion/cards.js`, `assets/css/motion/20-cards.css`,
hooks in `app/partials/product-card.php`, `app/views/home.php` (collections, best sellers, new arrivals),
`app/views/listing.php` (shop grid). Numbers live in `assets/js/motion/config.js` → `cards` and the
`css` tokens `fan-*`, `card-*`, `rail-thumb`, `reveal-step`. Implements PLAN.md §2.3 with the
critique's M1 (CSS rest pose) and L3 (no mobile tilt) applied.

## Hooks

| Hook | Where | Meaning |
|---|---|---|
| `data-motion="cards"` | `#collections .rail.grid--mosaic`, `#best-sellers .rail--cards`, `#new-arrivals .grid--products`, listing `.grid--products.js-filter-bar-anchor` | root the core loader hands to `cards.js` (flag `fan`, desktop only). Roots keep `.sf-reveal.sf-reveal--stagger` so `reveal.js` still fades them on mobile. |
| `data-motion-card="product"` | `article.product-card` | hover-tilt target; `.collection-card` is targeted by class (partial not owned). |
| `data-cards-state` | set by `cards.js` on the root | absent = CSS rest pose (fanned); `live` = GSAP owns the transforms; `spread` = grid pose, hover enabled. |
| `.sf-rail-progress` | sibling after the two home rails | the gold scroll indicator (≤ 767px, CSS scroll-driven animation only). |

## Desktop (`html.motion--desktop`)

1. First paint: `20-cards.css` puts every direct child of a root in the fan pose
   (`translate3d(--sf-fan-x, --sf-fan-ry × --sf-fan-rise, 0) rotate(--sf-fan-r × --sf-fan-rotate)`,
   origin `50% 110%`). Per-child `--sf-fan-r/-x/-ry` come from `:nth-child` cycles that follow
   `site.css` column counts (mosaic 5 poses; 3-col at 1024–1199; 4-col at ≥ 1200 for `.rail--cards`
   and the home `.grid--products`; the listing grid stays 3-col). The same rule neutralises the
   reveal system for these roots (`opacity: 1; transition: none`), so core's GSAP reveal never
   tweens the children — one system per node.
2. `cards.js` (loaded when the root nears the viewport, after GSAP) adds `.is-visible` to the root
   (core's `revealNode` then bails), kills any stray tween, reads the CSS pose into inline GSAP
   values, sets `data-cards-state="live"` (CSS pose drops in the same frame, no jump) and scrubs
   to `transform: none` with ScrollTrigger (`start`/`end`/`scrub` from config). Rows are grouped by
   `offsetTop`; ≤ `singleGroupRows` rows = one hand, more = one trigger per row (listing).
   On completion the trigger is killed (`replay: false`), inline styles cleared, the root becomes
   `spread`. `scrub: 0` in config = kill-switch: one-shot `expo.out` stagger on enter.
3. Hover: `pointermove` (rAF-throttled) writes `--sf-tilt-x/-y/-lift` on the card; CSS applies
   `perspective() rotateX() rotateY() translateY()` only while `.is-tilting` / `.is-untilting`
   (no permanent 3D layers). `.is-sweeping` runs the `sf-card-sweep` keyframe once on
   `.product-card__media::before` / `.collection-card::after` (gold gradient, transform + opacity).
   Keyboard twin: `:focus-within` lifts the card 8px.
4. Failure paths: GSAP missing → root gets `.is-settling` and `spread`, CSS transitions the pose to
   the grid, hover still works. `core.js` dead → `sf-fan-failsafe` keyframe settles the pose at
   `--sf-fan-failsafe` (6 s). Module init after the failsafe on an in-view root → no fan.
   Device flip → triggers killed, inline styles cleared, hover removed/re-armed on `device`.

## Mobile / low / reduced / no-JS

- Mobile: 0 B of `cards.js`. `.rail` keeps its `scroll-snap-type: x mandatory` carousel with the
  next card peeking (`--rail-w` 62vw / 78vw); the reveal is `reveal.js`'s existing stagger.
  `.sf-rail-progress` shows only under `@supports (animation-timeline: scroll())`: the rail is a
  named `scroll-timeline` (`timeline-scope` on the `.container`), the 2px thumb (`--sf-rail-thumb`
  of the track) translates with the rail. Unsupported browsers see nothing. New arrivals and the
  shop grid stay grids.
- Low: same as mobile. Reduced: no `motion--*` class, no rule applies, grid pose from first paint.
- No JS: no `html.motion--*` class → grid pose, all cards, prices and buttons painted, no indicator.

## Verified 2026-09-26 (Playwright, Chromium 1208, `php -S 127.0.0.1:8091`)

1440×900 `/` and `/shop`, 360×780 touch `/` and `/shop`, reduced motion, JS off, GSAP blocked,
`core.js` blocked, `saveData` (low), 1440→800→1440 flip: fan at first paint, scrub to grid with
no inline styles left, `is-visible` handshake, tilt + sweep on hover, price and Choose Size /
Add to Cart hit-testable while tilted, indicator hidden on desktop and moving on mobile, no console
or page errors from these files. Cost: `cards.js` 2.5 KB gz (desktop only), `20-cards.css` 1.5 KB gz.
