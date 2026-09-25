# Sky Fragrances — Motion plan critique (performance + conversion)

Reviewer brief: mid-range Android (Snapdragon 6xx, 4–6 GB, Adreno 610/612, 1080×2400 at dpr 2.5–3,
often a 90 Hz panel), congested 4G, WhatsApp/Instagram in-app browsers as the main entry point.
Numbers below use Lighthouse's mobile simulation (1.6 Mbps down ≈ 200 KB/s, 150 ms RTT, 4× CPU
slowdown) unless a real-device figure is stated. Read against `PLAN.md`, `codebase-audit.md` and
the code as it is in `site/` on 2026-09-26 (`intro.js`, `intro.php`, `head-meta.php`, `layout.php`,
`site.css`, `cart.js`, `reveal.js`).

What the plan gets right and should keep: hand-written GL instead of Three.js (§8 #1), faux bloom
(§8 #2), no video on mobile (§8 #3), no ScrollTrigger pins (§5.3), prices never tick (§8 #5),
Lenis off on touch (§8 #8), magnetism off the buy buttons (§8 #9), five quiz questions kept (§8 #7),
the `sf:cart-added` event instead of a blind delay (§2.5), the reveal handshake (§5.2), and the
kill-switch list (§6.4). None of that is in question. The findings are about what sits on top.

Ranked: B = blocker (fails a hard rule in the brief), H = high, M = medium, L = low.

---

## B1 · The first-visit choreography moves the LCP element by ~1.6 s on mobile (§2.1, §2.2, §4)

Chrome does not count an element as painted while its opacity is 0 or it is fully clipped; LCP is
recorded the first time it becomes visible, at the size visible then. On the home page the LCP
candidate is `h1.hero__title` (the lockup PNG renders at 72 px; admin hero image is empty today).
The plan hides the h1 three ways on first visit:

- the opaque `.sf-intro` veil (z 600) for 1,300 ms on mobile (1,600 desktop) + 300 ms dissolve;
- the headline lines at `translateY(110%)` inside `overflow:hidden` until GSAP tweens them;
- today's `.hero__enter{opacity:0}` + 600 ms keyframe with 120 ms × `--i` delay, now behind
  `--hero-delay` 0.6 s mobile / 1.2 s desktop.

The veil does not fool Lighthouse (LCP ignores occlusion), but the clip and opacity do. On
simulated 4G, DCL for this page is ~2.0–2.5 s (HTML + 22 KB CSS + two sync scripts + 88–127 KB of
fonts before the display font paints). The loader timeline starts at DCL (`intro.js run()` is bound
to `DOMContentLoaded`, not first paint as §4 says), so the h1 first paints at ≈ DCL + 1.3 s + 0.3 s
≈ 3.6–4.1 s. Lighthouse LCP: good ≤ 2.5 s, poor > 4.0 s; at 3.8 s the LCP audit scores ~0.35 and,
at 25 % weight, caps Performance around 75–80 before anything else is counted. Speed Index (10 %
weight) is hit as well: the viewport is a black rectangle until DCL + 1.6 s, so SI lands at
~4–4.5 s (yellow starts at 3.4 s). Lighthouse always runs a fresh session, so it always measures
Full mode; sessionStorage does not help the score.

Fix (all three are needed):
1. The h1 is visible in its final position at first paint on every device class: no opacity 0,
   no clip. Let the motion happen around it — the gold gradient sweeps across the already visible
   headline, the plates/ribbons fade in behind it, eyebrow/trust fade in beside it.
2. If the client insists on a line-mask reveal, make it desktop-only under `html.motion--desktop`
   (Lighthouse mobile never sees it) and start it at first paint, not after the loader.
3. On mobile the intro is Calm at most (see B2/B3): the monogram fades in over the already visible
   hero and dissolves in ≤ 700 ms total, no opaque veil. Only then can the page reach ≥ 85.

## B2 · The loader plays on every page, for every fresh session, i.e. on most WhatsApp/Instagram product links (§2.2, §4, §5.2)

`layout.php` includes `intro.php` on every storefront page, including `/product/*`, `/cart`,
`/checkout`, `/scent-finder/result`. Mode is keyed on `sessionStorage`. Pakistani traffic arrives
overwhelmingly through WhatsApp and Instagram in-app browsers; every link opened from a chat is a
new webview with an empty sessionStorage. So the shopper who taps a product link in WhatsApp lands
on the PDP, sees 1.3 s of black + particles, then 0.3 s dissolve, then the price. The brief's
"price, Add to Cart and COD info visible immediately" fails on the single most common path into
the store. Two smaller costs ride along: `.sf-intro__mark` is `fetchpriority="high"` on every page,
competing with the PDP gallery image (the PDP's LCP) for the first 15 KB of bandwidth; and Quick
mode still paints a 280 ms black frame on every subsequent page of the session, which on a 4G page
load that already takes 2–3 s reads as lag, exactly the argument the plan makes against the
transitions veil on touch (§2.7).

Fix: render `intro.php` only when `body.home`; drop Quick mode entirely (return visits and
non-home pages get nothing); remember the intro in `localStorage` with a 7-day stamp rather than
sessionStorage, so re-opened in-app webviews do not replay it; remove `fetchpriority="high"` from
the monogram everywhere but the home first visit.

## B3 · Tapping the loader to hurry it clicks whatever is underneath (§2.2 "Stays instant", §4 300 ms row)

The plan makes the veil `pointer-events:none` from 300 ms "so a tap goes through to the hero CTA",
and `intro.js armSkip()` finishes the intro on any `pointerdown`. The current CSS already has
`.sf-intro{pointer-events:none}` at all times. Result: the same tap both skips the intro and
activates the invisible element under an opaque black screen — the hero CTA (`/shop`), the
announcement bar, the header cart icon or the hamburger. A shopper who taps to skip is navigated
away from the page they came for. This is the plan's own description of the behaviour, not a bug
in the implementation.

Fix: veil `pointer-events:auto` until `.is-leaving`; a tap on the veil = skip only; keep the Skip
button. Pointer-through is only acceptable once the veil's opacity is below ~0.2.

## B4 · The hero CTA is invisible for 2.2–2.8 s on first visit; "clickable at first paint" is not the same as visible (§2.1)

Loader 1.3/1.6 s + dissolve 0.3 s + `--hero-delay` 0.6/1.2 s + the 600 ms `hero-enter` fade before
"Shop The Collection" is legible: ≈ 2.2 s on mobile, ≈ 2.8 s on desktop, after DCL. The brief's
rule is that animation never delays the buying path; on the home page the hero CTA is that path.
The plan's defence ("the delay is opacity only, pointer-events untouched") is a Lighthouse
technicality, not a shopper's experience — nobody clicks a button they cannot see.

Fix: `--hero-delay` ≤ 300 ms on mobile and ≤ 600 ms on desktop, measured from first paint; the
"product alone" beat is a desktop-only flourish and must never gate `.hero__actions`; the CTA fades
in concurrently with the headline, not after it.

## H1 · The "Low-power" tier will not catch the target phone; the "Mobile" tier is the wrong default (§2 device table)

Snapdragon 665/680/685/695 phones ship with 4–8 GB RAM (`deviceMemory` reports 4 or 8), 8 cores,
Android Data Saver usually off, and `effectiveType` reads `"4g"` for anything above ~700 kbps and
RTT < 270 ms — i.e. for almost every congested Pakistani 4G connection. None of the Low-power
conditions fire, so the exact device the brief names gets the Full mobile path: 200-particle
canvas, two mist layers, three animated plates, two ingredient layers, GSAP on the PDP and quiz,
0.8-scale loader. The plan's mobile design assumes Low-power protects the weak phones; it protects
only 2 GB phones and Data Saver users.

Fix: invert the defaults. `Mobile` = the lean path (Calm or no intro, one static glow + one drifting
plate, zero hero ingredient layers, zero GSAP, IO/CSS only). Add an opt-in `Mobile-high` class for
`deviceMemory >= 6 && hardwareConcurrency >= 8` plus a passing runtime frame check, and put the
current "Full at 0.8" behaviour there. Keep Low-power for the 2 GB / Data Saver case.

## H2 · Mobile hero: 4–6 full-screen alpha layers on Adreno 610 are the GPU cost the plan says does not exist (§2.1 Mobile, §3 "Plates fallback", §2.8)

Per frame while the hero is on screen: hero photo (if set) + scrim + 3 plates (2 of them
translating/scaling on 18 s infinite `alternate` keyframes, 2 cross-fading opacity over 12 s) + 2
ingredient layers (scroll-driven translate) + `.hero__body` with `background-clip:text`. At
1080×2400 each layer is a 10.4 MB texture (×dpr² — the plan's "720w plates" are upscaled ~1.5–3×
on device); 5–6 of them blended per frame is 50–60 Mpx/frame, ~3–3.6 Gpx/s at 60 fps, which is at
or above Adreno 610's realistic fill rate with alpha blending. Real-device expectation: 40–50 fps
in the hero, visible tearing on the crossfade, and thermal throttling after ~30 s because the
`infinite` keyframes never let the compositor sleep. "Costs no GPU beyond compositing" is the
whole problem: on this class of GPU, compositing is the budget. Bytes: 70 KB plates + 25 KB layers
+ 12 KB wisp = 107 KB ≈ 0.55 s of 4G for decoration, all requested during the load window even
with `loading=lazy` because the hero is above the fold.

Fix: mobile hero = one static glow plate (≤ 12 KB, 540 w) + one ribbon plate (≤ 20 KB, 540 w)
drifting on a single 14 s keyframe, `contain: paint` on `.hero`, `animation-play-state: paused`
when the hero leaves the viewport (one IO in boot) and after 3 cycles; zero ingredient layers in
the mobile hero (at 360 px there is no side room anyway — `.hero__body` fills the width and the
12 % exclusion zone is meaningless); ingredient layers on mobile only in the PDP story, one,
static. Budget: ≤ 32 KB of hero decoration on mobile, ≤ 2 animating layers.

## H3 · The cart flight delays the drawer by 450–650 ms and re-opens the duplicate-add window (§2.5 "Add to Cart burst + flight")

`cart.js add()` today clears `pending` when the response arrives, removes `is-loading`, and opens
the drawer at once; the drawer overlay (z 400) is what stops a second tap. With `openDrawer`
awaiting `flyToCart` (hard cap 650 ms), the button is live, enabled and uncovered for 450–650 ms
after a successful add. A second tap in that window is a second `/api/cart/add` → qty 2 — on a
touch device where impatient double taps are the norm. And the drawer holds the Checkout button:
delaying it is delaying the buying path the brief protects. The flight's destination is also
wrong once the drawer opens: the header icon (z 100) sits under the overlay (z 400), so the clone
lands on something the shopper cannot see.

Fix: never await in `cart.js` — dispatch `sf:cart-added` and open the drawer immediately, as today.
Mobile: burst + count bump only, no flight. Desktop: fly the 28 px clone *toward the drawer panel*
(its header/count, above the overlay) for ≤ 400 ms while the drawer slides in; the drawer's own
slide is the "lands in the cart" moment. Keep `is-loading` on the button until `sf:dialog-open`
fires. Skip everything on forms with `data-cart-reload` (the `/cart` page reloads).

## H4 · GSAP on the mobile PDP and quiz buys nothing CSS does not already do (§2.4, §2.6, §5.1, §6.1)

§2.4 Mobile says "no GSAP (IO only)"; §5.1 and §6.1 load GSAP core on mobile for "the PDP story
when in reach" and on `/scent-finder*`. Take §2.4 at its word and extend it: 27 KB gz is ~70 KB of
JS to parse and compile, ≈ 60–100 ms on a 4×-throttled Snapdragon 6xx main thread, on the two
pages where mobile buyers decide. The mobile quiz effect is `x ±24 px` + opacity (two transitions
and a `transitionend` handoff); the mobile story is note fade-ups the existing `.sf-reveal` already
does and hue cross-fades the plan already does with IO.

Fix: GSAP and ScrollTrigger are desktop-only, full stop. Mobile quiz = class toggles +
`transition: transform .32s, opacity .32s`. Mobile story = `.sf-reveal` + IO hue swap. This also
removes the §5.1/§2.4 contradiction and 27 KB from the mobile budget table.

## H5 · Splitting the headline into lines will mis-split on 4G (§2.1 "How")

The h1 uses `text-wrap: balance`, `clamp(2rem, 11vw, 3.25rem)`, `font-display: swap` Cormorant
(38 KB, arrives after first paint on 4G) and is admin-editable text. Measuring line boxes at boot
means measuring the *fallback* font's wrapping; when Cormorant swaps in, the spans keep the old
breaks and the real font re-wraps inside them — orphaned words, a line that wraps to two, or a
mask that clips a descender. `text-gold-grad` (`background-clip: text`) breaks into a separate
gradient per span, so the gold no longer runs across the headline. Waiting on `document.fonts.ready`
fixes the split but pushes the reveal (and LCP, see B1) behind the font download.

Fix: no per-line split on mobile at all (h1 static, see B1). Desktop: split only after
`fonts.ready` with a 600 ms cap, or reveal the whole h1 with one `clip-path: inset()` tween
(no measurement, gradient intact); if lines are a must, split by `<br>`-free word groups the
designer approves in the admin heading field, not by measured line boxes.

## H6 · Cross-document View Transitions on mobile turn the network wait into a frozen screen (§2.7 Mobile)

With `@view-transition { navigation: auto }` active, Chrome snapshots the old page and holds it
until the new document reaches its first render — on 4G that is the whole 1.5–3 s HTML + CSS
round-trip. The shopper taps a product card and nothing changes: no progress bar, no blank, the old
page just sits there. The reflex is to tap again, which queues a second navigation. The plan
correctly rejects the JS veil on touch for exactly this reason (§8 #11) and then enables the
browser-native version of the same thing.

Fix: wrap the rule in `@media (min-width: 1024px) and (hover: hover) and (pointer: fine) and
(prefers-reduced-motion: no-preference) { @view-transition { navigation: auto } }`. Desktop only.
While there, shorten to 160 / 200 ms; old and new already run concurrently so the total is 200 ms.

## H7 · Quiz auto-advance on radio selection breaks keyboard users and mis-records answers (§2.6 "How")

Arrow keys inside a `radiogroup` *select* each radio as focus moves. Advancing 350 ms after a
radio changes means a keyboard or switch user who presses ↓ once to read the second option has
answered question 1 with option 2 and is now on question 2. The same happens to a touch user who
taps the wrong card and reaches for the right one 400 ms later. Wrong answers → wrong result →
abandoned quiz; the quiz's whole job is to end on an Add to Cart the shopper believes.

Fix: advance only on the Next control, or on a pointer `click` (not `change`) with a 600 ms undo
window and a visible "Next" affordance; never on keyboard-driven `change`; move focus to the new
`legend` only after the transition ends.

## H8 · The reduced-motion story is not complete, and the cascade makes part of it impossible (§2 "Reduced", §7)

- `site.css` line 5221: `*, *::before, *::after { animation-duration: .01ms !important;
  transition-duration: .01ms !important }`. `!important` declarations invert layer order, so a
  later `@layer motion` cannot re-introduce the plan's "calm 250 ms opacity fades" under Reduced
  without also using `!important` — and an `!important` in a later layer *loses* to one in an
  earlier layer. Either accept that Reduced means instant (defensible) and say so, or move the
  fades into `site.css`'s own reduced-motion block. Do not promise 250 ms fades the cascade forbids.
- Calm intro under Reduced today: `html.sf-intro-calm .sf-intro { animation: sf-intro-quick .3s
  ease 1.15s }` — the duration is zeroed by the `*` rule but the 1.15 s *delay* is not, and the
  failsafe keeps its 2.6 s delay. A reduced-motion user gets an opaque black screen for 1.15 s that
  then vanishes with no fade. That is the opposite of calm.
- `prefers-reduced-motion` is one signal; Android's system "Remove animations" toggle maps to it,
  but Data Saver and Battery Saver do not. The Low-power class covers `saveData`; nothing covers
  Battery Saver (no API) — accept, but note it.

Fix: Reduced → no intro veil at all (`sf-intro-*` class never set), plates static, h1 visible;
state in §7 that Reduced is *instant*, not 250 ms, unless `site.css` is edited.

## M1 · The fan's rest pose arrives with GSAP, i.e. late, and boot runs after first paint can happen (§2.3, §5.2)

The plan sets the fanned rest pose with `gsap.set` at boot, but GSAP loads "after LCP when
`#collections` is within 1.5 viewports". Between boot and GSAP the cards sit in their plain grid;
when GSAP lands they snap to the fan, then spread — a visible jump for anyone who has scrolled
there (on `/collections` the grid is at the top of the page). Separately, `boot.js` is `defer`:
deferred scripts run before DCL but the browser may paint before them on a slow device with a long
document, so "strip `.sf-reveal` before first paint" is a race, not a guarantee.

Fix: rest poses in CSS (`html.motion--desktop #collections .collection-card:nth-child(n) {
transform: … }`), keyed on a class the *sync* head gate sets (device class is knowable
synchronously via `matchMedia`); GSAP tweens from the computed transform to `none`. Move device
classification into the gate; `boot.js` only imports modules.

## M2 · Two synchronous head scripts and a second render-blocking stylesheet on every page, checkout included (§5.2)

`config.js` (sync, `<head>`) + `intro-gate.js` (sync) + `motion.css` (render-blocking `<link>`)
= three extra requests in the critical path on every page, including `/cart` and `/checkout` which
receive no motion. The preload scanner fetches them in parallel with `site.css`, so the cost is
one RTT when the CSS is cached and HTML is not (≈ 150 ms on 4G), plus parser blocking on two
script executions. The CSP forbids the usual `media="print" onload=` trick (inline handler).

Fix: inline the 20-line gate with its SHA-256 beside `CSP_BOOTSTRAP_SCRIPT_HASH` (the plan already
allows this — make it the default); ship `SF_MOTION` as a `<script type="application/json">`
data block parsed by boot (not executed, CSP-clean, zero sync scripts); put the ~1 KB of
first-paint motion rules (veil, plates, rest poses) into `site.css` `@layer overrides` and have
`boot.js` insert the rest of `motion.css` only on pages that use it.

## M3 · The transitions veil and cursor touch the checkout path unless excluded (§2.7, §2.5)

The JS veil fallback intercepts "same-origin left-click GET `<a>`" — that includes the drawer's
"View cart" and "Checkout" links, the sticky bar's WhatsApp link (excluded only via `wa.me`), and
footer links on `/checkout`. A 220 ms veil before the checkout page is small, but it is on the one
path the brief says must never be touched, and the plan claims "Nothing in checkout, cart".

Fix: exclusion list in config: `.drawer a`, `.sticky-bar a`, `[href*="/checkout"]`,
`[href*="/cart"]`, `[href*="/track"]`, `body.cart a`, `body.checkout a`. Cursor: never created on
`body.cart`, `body.checkout`, `body.track`.

## M4 · The 200 ms rAF benchmark measures the intro, not the device (§2 device table, §3 Gate)

Frames are counted between `load` and +200 ms — while the particle canvas is running (Full mode),
`hero-enter` keyframes and two mist layers are animating, deferred scripts are executing and images
are decoding. Fast machines read low and get classified Low-power; 90/120 Hz phones read high and
pass. It is noise with a threshold.

Fix: drop the fps benchmark as a *classification* input; keep static heuristics for the class and
rely on the runtime check the plan already has (120-frame mean > 20 ms twice → dispose). If a
benchmark stays, measure the 95th-percentile frame time over 60 frames *after* the intro is gone,
on an idle page, and compare against the display's own refresh interval.

## M5 · Lenis is the lowest value per QA hour on the list (§2.7, §5.3, §9 Stage 7)

4 KB is not the cost; the cost is the eight scroll consumers the audit lists (`lockBody/unlockBody`
with `window.scrollTo`, three `scrollIntoView`s, `rail.scrollTo`, the header rAF listener, hash
links, `scroll-behavior: smooth`), plus scrub lag stacked on lerp (§2.3 risk). The 4 h estimate
for "Transitions + Lenis + anchor/dialog audit" is thin for that surface, and Lenis changes nothing
for the mobile majority. It is also the only item whose failure mode (a drawer that will not scroll,
an anchor that lands 80 px off) is a *functional* regression rather than a missing flourish.

Fix: ship it `flags.lenis: false` and turn it on only after Stage 9 QA passes with it; or drop it
and let `scroll-behavior: smooth` plus expo-out ScrollTrigger scrubs carry the "silky" brief. If
kept, budget 6 h, not 4, and add `SF.scrollTo` to Stage 0 so every consumer is routed before Lenis
exists.

## M6 · Story: three full-section hue layers and a second copy of the largest image (§2.4)

Three `.story__glow--*` radial layers cross-fading on opacity are three compositor layers the size
of `#composition`; on mobile with IO-driven crossfades that is three ~360×1400 textures plus the
cloned `.story__bottle` image (same `srcset`, cache hit, but a second decode and a second layer).
Cheap on desktop, avoidable on mobile.

Fix: two layers (current, next) swapped by class; on mobile one layer whose `background` changes
under a 300 ms opacity dip; on mobile reuse the gallery's own `<img>` (no clone — the rail is
already on screen), or a 480 w thumb.

## M7 · Quiz result: a 600 ms mist over the price after a 302 + full page load (§2.6 "How")

The result is reached by GET → 302 → full navigation; on 4G that is already 2–3 s. Then 600 ms of
mist over a page whose whole purpose is the price and Add to Cart. "Painted at first frame" under a
veil is B4 again.

Fix: no result veil on mobile; the `.product-card__media` scale 1.08→1 + opacity and the heading
gradient are the reveal. Desktop may keep a 400 ms veil that never covers `.split__aside`.

## M8 · Two pre-existing bytes problems outweigh the whole motion budget and the plan only half-fixes them (§6.3)

- `lockup-transparent-400.png` is 76,668 B, rendered at 72 px (mobile) / 96 px (desktop) in the hero
  and as the header logo on every page. `lockup-on-black-800.webp` is 18 KB; a 200 w transparent
  WebP would be ~6 KB. Saving: ~70 KB ≈ 0.35 s of 4G on every page, larger than the plan's entire
  added JS+CSS (~9 KB gz).
- Fonts: `jost-variable` 50 KB + `cormorant-garamond-variable` 38 KB + an italic variable face
  39 KB the design plan excluded = 127 KB, none preloaded (the two `<link rel=preload>`s point at
  files that do not exist, so they are silently skipped). The display font paints the LCP text.
  Fix: subset both to Latin + PKR glyphs (~18–22 KB each), drop the italic or load it only where
  used, preload the two real files. Saving: ~70–90 KB and 300–500 ms off the h1's final paint.

Neither is "motion", but the brief's ≥ 85 depends on them more than on any concept in §2. Put both
in Stage 0.

## L1 · Magnetic pull on the hero primary CTA (§2.5)

The brief exempts only the cart; the hero's `.btn--primary` is the home page's buy path. 8 px
of pull plus GSAP's inline transform also overrides the `.btn:active { translateY(1px) }` press
feedback. Cap at 4 px or limit magnetism to `.btn--ghost`, `.btn--text`, `.section-header__link`.

## L2 · Tick-up twins jitter layout (§2.5)

An `aria-hidden` twin counting 0→12 changes width per frame ("8" vs "12" vs "12 fragrances"),
nudging siblings. `font-variant-numeric: tabular-nums` and a `min-width: Nch` on the twin.

## L3 · Mobile rail tilt clips (§2.3 Mobile)

Rotating outer `.collection-card`s ±3° inside `.rail` (`overflow-x: auto`, `scroll-snap`) clips
their corners and shadows at the rail's block edges. Apply the tilt to an inner wrapper and add
`padding-block` to the rail, or skip it — a snap carousel already reads as a hand.

## L4 · `once: true` with `scrub` (§2.3)

Works, but after the first pass the trigger is killed at progress 1 and a shopper scrolling back
up sees the grid, not the fan. Intentional? Say so in config (`fan.replay: false`).

## L5 · Timeline row "0 ms" is wrong today (§4)

`intro.js run()` binds to `DOMContentLoaded`, so the 1,600 ms clock starts at DCL, not first
paint; on 4G that is 300–800 ms after the veil first shows. Either start the timers at script
execution (the veil is already visible then) or state DCL in the table so the Lighthouse math in
B1 is honest.

---

## What the mobile home page costs, first visit, per the plan vs. per this critique

| | Plan (§6.1, Mobile) | After fixes |
|---|---|---|
| Sync scripts in `<head>` | 2 (config, gate) | 0 (inline hash + JSON block) |
| Render-blocking CSS requests | 2 | 1 |
| Motion JS before `load` | 0.7 KB | 0.4 KB inline |
| Motion JS after `load` | boot 2 KB (+ GSAP 27 KB on PDP/quiz) | boot 2 KB, no GSAP on mobile anywhere |
| Hero decoration bytes | plates 70 + layers 25 = 95 KB | ≤ 32 KB, 2 files |
| Animating full-screen layers in hero | 4–6 | ≤ 2, paused off-screen |
| Opaque veil on first paint | 1.3 s + 0.3 s | 0 (Calm ≤ 0.7 s over a visible hero, home only) |
| h1 first visible paint | ≈ DCL + 1.6 s | first paint |
| Hero CTA legible | ≈ DCL + 2.2 s | ≤ first paint + 0.3 s |
| Predicted Lighthouse mobile | 70–80 (LCP 3.6–4.1 s, SI ~4 s) | ≥ 85 reachable if M8 lands |

## Order of surgery on the plan

1. B1–B4 + H1: rewrite §2.1 Mobile, §2.2, §4 and the device table. This is a small edit to the
   document and the largest change to the outcome.
2. H2, H4, H6: cut mobile decoration bytes/layers, remove GSAP from mobile, media-query the View
   Transition.
3. H3, H7, H8: the three items that would ship a *functional* regression (duplicate adds, wrong
   quiz answers, a black screen for reduced-motion users).
4. M8 into Stage 0. It is the cheapest 100 KB on the table.
5. Everything else as written, with M1/M2 folded into Stage 0 and Lenis behind a flag.
