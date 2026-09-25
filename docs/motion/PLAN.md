# Sky Fragrances — Motion Plan (for approval)

Motion director's plan, 2026-09-26. Built on `00-brief.md`, `ref-kumo.md`, `ref-tarot.md`,
`ref-austensor.md` and `codebase-audit.md`. Paths are relative to `site/`. Nothing below is
implemented yet except the first-cut loader (`assets/js/intro.js`, `assets/js/motion/config.js`,
`app/partials/intro.php`), which this plan refits rather than discards. Revised after the
performance/conversion critique in `plan-critique.md`; §11 records what was rejected or adjusted.

## 1. Direction

"Signature" here is scent made visible. A perfume cannot be shown, so the site shows what it does
to a room: warm light that drifts, mist that gathers and clears, gold that catches the eye only at
the edges. Every effect is one of those three materials — light (the ribbons, the glow behind the
bottle, the sweep across a card), mist (the loader dissolve, page veils, smoke wisps) and gold (the
monogram, one headline, one underline). Three rules keep it luxurious instead of loud:
**restraint** (gold is scarce, one accent, nothing bouncy except the cart count, all eases
expo/power3 out and slow); **one hero moment per screen** (each section has exactly one thing that
moves for the eye — ribbons in the hero, the fan in collections, the note change in the story, the
flip in the quiz — and everything else settles to `transform:none` and stays still); **silence
between** (no motion between sections, no ambient loops below the hero, the page must read as
finished at 360px on 4G with every script blocked). The product, its price, Add to Cart and the
COD line are never part of the choreography: they are painted in the first frame, and the
animation happens around them.

## 2. Per section, in implementation order

Device classes used everywhere below:

| Class | Rule (evaluated in the inline `<head>` gate so CSS can key on it at first paint; `boot.js` re-checks on media-query `change`) |
|---|---|
| **Mobile** (the default) | `max-width: 768px` or any touch-primary device → `html.motion--mobile`. Lean path: no opaque veil, one static glow plate + one drifting plate in the hero, zero hero ingredient layers, zero GSAP/ScrollTrigger/Lenis, IntersectionObserver + CSS transitions only. This *is* the Snapdragon 6xx / 4G target (4–8 GB, 8 cores, `effectiveType` "4g" on every congested connection), so it is the default, not the exception. `saveData` or `effectiveType` 2g/3g additionally removes the intro and the drifting plate. |
| **Mobile-high** (opt-in) | Mobile and `deviceMemory >= 6` and `hardwareConcurrency >= 8` and no `saveData`, plus a passing runtime frame check (p95 frame time over 60 frames on the idle page after the intro is gone, ≤ 1.3× the display's own refresh interval) → adds `html.motion--mobile-high`: one extra hero plate with a crossfade, one static ingredient layer in the PDP story, the 14-span cart burst. Nothing else. |
| **Desktop** | `min-width: 1024px` and `(hover:hover) and (pointer:fine)` and not Reduced → `html.motion--desktop`. The only class that ever loads GSAP, ScrollTrigger, Lenis, WebGL, the cursor, magnetism or the transitions veil. `deviceMemory <= 2` or `hardwareConcurrency <= 2` on desktop only turns WebGL off (§3 gate). |
| **Reduced** | `prefers-reduced-motion: reduce` → `html.sf-reduce`; no `motion--*` class is set, no module runs, no veil exists, plates are static, the fan sits in its grid, the h1 is visible. Reduced means **instant**, not "calm fades" (§7 explains why the cascade allows nothing else). |

The 200 ms rAF benchmark is **not** a classification input: measured between `load` and +200 ms
it counts frames while deferred scripts execute and images decode, so fast machines read low and
90/120 Hz phones read high. Frame timing is used only to opt *in* to Mobile-high (above) and to
kill desktop ribbons at runtime (§3 lifecycle).

### 2.1 Hero — scent trails (`home.php section.hero`)

| Aspect | Plan |
|---|---|
| What | Bottle/lockup still at centre; headline, eyebrow and CTA painted in their final position in the first frame on every device class. Desktop: 4 gold-amber light ribbons flow behind the lockup, brightening where they cross; a gold sweep crosses the headline; eyebrow and trust line settle in after the product has been alone for 0.6 s (Kumo rule, desktop only). Mobile: one ribbon plate drifts behind the lockup, nothing else moves. |
| How | Headline: `h1.hero__title` is never `opacity:0`, never translated, never clipped at rest and **never split into lines** (`text-wrap:balance` plus a swapped display font make measured line boxes wrong the moment Cormorant arrives; the gradient would also split per span). Desktop reveal = one CSS keyframe under `html.motion--desktop` (class set by the inline head gate, so it applies at first paint): `clip-path: inset(0 0 100% 0) → inset(0)` over 1.1 s `--ease-out`, starting at first paint, gradient intact, no measurement, no JS; a gold sweep (`background-position` of `.text-gold-grad`, 900 ms) plays when the loader dissolves or, without a loader, at +300 ms. Mobile: h1 static from first paint, no keyframe. `.hero__actions` is taken **out** of `.hero__enter` and fades `opacity .6 → 1` in 400 ms concurrently with the headline. `.hero__enter` stagger stays for eyebrow and trust line only, with `--hero-delay` ≤ 300 ms mobile / ≤ 600 ms desktop measured from first paint (0 under Reduced). Ribbons: WebGL canvas `.hero__ribbons[aria-hidden]` inserted between `.hero__scrim` (z1) and `.hero__body` (z2) — detail in §3. |
| Desktop | Live ribbons (Three.js or raw WebGL, see §3 and §8), pointer coupling ±3° group rotation with lerp 0.06, scroll progress 0→1 across the hero fades ribbons out and rotates them 0.15 rad; dpr capped 1.5; paused off-screen or tab hidden. |
| Mobile | No canvas, no video, **no ingredient layers** (at 360 px `.hero__body` fills the width; there is no room beside the type). Two plates only: `ribbons-glow.webp` static (≤ 12 KB, 540w) and `ribbons-a.webp` (≤ 20 KB, 540w) on a single 14 s `transform: translate3d/scale` keyframe; `.hero{contain:paint}`; `animation-play-state:paused` when the hero is < 10 % visible (one IntersectionObserver) and after 3 cycles (`animationiteration` count) so the compositor can sleep. Mobile-high adds `ribbons-b.webp` with a 12 s opacity crossfade. Hard budget ≤ 32 KB and ≤ 2 animating layers. |
| Reduced | Static glow plate, h1 / eyebrow / CTA / trust visible at once, nothing fades or drifts. |
| Stays instant | `h1` text painted at full opacity in its final position at first paint on every class (LCP element: no opacity, clip or transform gate ever sits on it on mobile; the desktop clip reveal starts at first paint and is > 0 area within one frame). `.hero__actions` legible within 400 ms of first paint and clickable throughout; the "product alone" beat gates only eyebrow and trust line, never the CTA. Header cart icon, nav. `.hero--empty` and admin overlay opacity respected. |
| Cost | Mobile: 2 plates ≤ 32 KB (`loading=lazy`, after the h1 in source, `fetchpriority=low`), +0 JS, ~0.5 KB CSS in `site.css` overrides. Desktop: Three.js ~168 KB gz (or ~8 KB gz raw WebGL) loaded after `load` + idle + in-view; ~4k triangles; target ≤ 3 ms GPU/frame at 1440p dpr 1.5. |
| Risk / fallback | Desktop frame budget missed (120-frame mean > 20 ms twice) → dispose canvas, show the plates. WebGL context lost → same. Mobile-high crossfade tears on Adreno 610 → drop to the mobile default (one flag). Hero photo later uploaded by admin: ribbons need dark ground — see open question 2. |

### 2.2 Loader (`app/partials/intro.php`, `assets/js/intro.js`)

| Aspect | Plan |
|---|---|
| What | **Home page, first visit in 7 days, only.** Desktop: gold mist gathers into the SF monogram, a shimmer crosses it, the tagline appears, the veil dissolves to mist and hands straight into the hero's "product alone" beat. Mobile: the monogram fades in over the already-visible hero and dissolves again, ≤ 700 ms, no opaque veil. |
| How | Keep the built particle-gather for desktop (there is no vector monogram, so "stroke draws" is not possible honestly; particles converging *is* the mist-to-mark idea). Refit: `intro.php` is rendered only when `body.home` (today it is on every storefront page, so the most common entry — WhatsApp link → product page — got 1.3 s of black before the price). Mode is decided by the inline head gate (§5.2) from `localStorage.sfIntro` (7-day stamp; `sessionStorage` is empty in every WhatsApp/Instagram in-app webview) and the device class → `html.sf-intro-full` (desktop) or `html.sf-intro-calm` (mobile); no class → the partial stays `display:none`. **Quick mode is deleted**: return visits and non-home pages get nothing. Particle/timeline code moves to `motion/intro.js` (`defer`), its clock starting at script execution. `fetchpriority=high` is removed from `.sf-intro__mark` (the h1 and hero image must win; see §11). Exact timeline in §4. |
| Desktop | Full: 1,600 ms end to end, 520 particles, opaque veil. |
| Mobile | Calm only: the veil is transparent; the monogram (≤ 6 KB, 200w) fades in 250 ms over the painted hero, holds 200 ms, fades 250 ms = 700 ms. No particles, no mist layers, no canvas. Mobile-high: same. `saveData` / 2g/3g: no intro. |
| Reduced | No veil at all: `sf-intro-*` is never set and the partial stays `display:none`. |
| Stays instant | The hero is fully painted underneath from the first frame (h1 never hidden, §2.1). Desktop veil is `pointer-events:auto` until `.is-leaving`: a click or tap on the veil **skips only** and never reaches the CTA, announcement bar, cart icon or hamburger beneath; pointer-through is allowed only once veil opacity < 0.2; the Skip button stays; Escape and any key skip. Mobile Calm veil is `pointer-events:none` throughout — it is transparent, the hero is tappable at once. |
| Cost | Desktop ~1.8 KB gz JS + monogram 6 KB. Mobile 0 JS, ~0.3 KB CSS, monogram 6 KB, home first visit only. Fixes a real regression: today two sync scripts load *before* `site.css` — they go, replaced by the hashed inline gate. |
| Risk / fallback | Timer drift on a busy main thread → failsafe CSS keyframe removes the veil at 2.0 s (desktop) / 0.9 s (mobile) regardless of JS. `localStorage` throwing (private mode) → treated as already seen, no intro. |

### 2.3 Collection fan (`home.php #collections .rail.grid--mosaic`, `/collections`)

| Aspect | Plan |
|---|---|
| What | The five collection cards arrive as a fanned hand (rotate ±16°, overlapping, rising 24 px) and spread into their grid as the section scrolls in; on hover a card lifts, tilts toward the pointer and a gold light sweeps across it once. |
| How | Rest pose lives in **CSS, not JS**: `html.motion--desktop #collections .collection-card:nth-child(n){transform: rotate(±16deg) translate(x, 24px)}` so the fan is there at first paint (the inline gate sets the class in `<head>`; no snap from grid to fan when GSAP arrives late; `/collections` has its grid at the top). PHP prints the owned container without `.sf-reveal` when motion is on (one reveal system per node, no runtime strip race). ScrollTrigger `scrub: 0.6`, `start: 'top 85%'`, `end: 'top 35%'`, tweens from the computed transform to `none`; `fan.replay:false` in config — the trigger is killed at progress 1 and scrolling back keeps the grid, by design. Hover: `quickTo` `rotateX/rotateY` ≤ 6°, `y: -8`, `perspective` on the parent; sweep = `.collection-card__sweep` gradient span translated `x: -120% → 120%` in 700 ms `power3.out`. The `.collection-card__img` scale hover that already exists stays. |
| Desktop | As above. |
| Mobile | The existing `.rail` snap carousel *is* the fan: cards keep their scroll-snap and the existing `.sf-reveal--stagger` fade-up. No resting tilt (rotated corners and shadows clip against the rail's block edges), no hover tilt, no sweep; a snap carousel already reads as a hand. |
| Reduced | Grid pose from the first frame, no fade. |
| Stays instant | Links are real `<a>`s at all times; `.collection-card__count` text present at first paint (tick-up in §2.5 is on an `aria-hidden` duplicate). |
| Cost | Desktop: GSAP core + ScrollTrigger ≈ 41 KB gz, loaded after LCP when `#collections` is within 1.5 viewports; ~0.4 KB CSS rest poses in `site.css` overrides. Mobile: +0 JS, +0 CSS. |
| Risk / fallback | Scrub feels laggy (with Lenis on) → drop to a one-shot `from()` with stagger on enter. Grid reflow on image load → `ScrollTrigger.refresh()` on `load` of each `.collection-card__img`. |

### 2.4 Pinned fragrance story (`product.php #composition`, `partials/notes-pyramid.php`, `partials/meters.php`)

| Aspect | Plan |
|---|---|
| What | The bottle stays in view on the left while Top → Heart → Base reveal on the right; as each tier becomes current the bottle tilts a few degrees, its glow changes to that tier's hue and the ingredient wisps around it swap. |
| How | No pin-spacers. `#composition` becomes a two-column `.story` where the bottle column is `position: sticky; top: var(--header-h)` (CSS, works with native scroll and Lenis native mode, never fights `.sticky-bar`). ScrollTrigger only *scrubs state*: one trigger per `li.pyramid__tier` sets `data-note` = `top`, `heart` or `base` on the section. Hue = **two** radial layers `.story__glow` (current, next) swapped by class and cross-faded on opacity over 900 ms — never three, never a `background-color` tween. Bottle (desktop) = `.story__bottle` `<img>` at 480–640w `srcset` of the main render (a second, smaller decode; the gallery stays as is), tilt `rotateY ±6°, rotateX ±2°` and `scale 1.03` with `power3.out` 900 ms per state; `perspective: 1200px` on the column. Notes: each `.pyramid__note` `from({y:16, autoAlpha:0})` stagger 60 ms as its tier becomes current; `.meter` keeps its own `.is-visible` fill. Per-tier hue comes from a `scent_family → hue` table in config (open question 5) and can be overridden per product by `data-motion-hue-*` on the section, printed from admin later. |
| Desktop | As above, ScrollTrigger scrub for state, `pinType` irrelevant (no pin). |
| Mobile | No bottle column and no clone (the gallery above is the bottle; there is no side room at 360 px); tiers stack and reveal with the existing `.sf-reveal` fade; **one** `.story__glow` layer whose `background` swaps under a 300 ms opacity dip, driven by an IntersectionObserver at 50 % of each tier (`reveal.js` style, no GSAP); no tilt, no wisp. Mobile-high: one static wisp. |
| Reduced | Static "top" hue, all three tiers visible, meters filled. |
| Stays instant | `.split__aside` (price, size chips, Add to Cart, WhatsApp, COD trust row), `.sticky-bar`, `#add-to-cart-form` are never inside the story container and receive no motion class. `ScrollTrigger.refresh()` after `sf:size-change`, accordion toggles, drawer close, gallery image `load`. |
| Cost | Desktop: GSAP+ST already loaded on PDP for this; 2 gradient layers (CSS, 0 bytes of image); wisps 2 × ~12 KB WebP; bottle 480w ~20 KB. Mobile: +0 JS, +0 images, ~0.6 KB CSS (Mobile-high: 1 wisp ~12 KB). |
| Risk / fallback | Sticky column jitter under Lenis on Windows → `lenis.lerp` 0.12 and `will-change: transform` only on the bottle while the section is active (added/removed by the trigger, never at rest). Brief asked for a rotating bottle — see §8 (no turntable asset exists). |

### 2.5 Micro-interactions (site-wide, `motion/micro.js`, `motion/cursor.js`, `motion/cart-fly.js`)

| Effect | How | Desktop | Mobile | Reduced | Stays instant |
|---|---|---|---|---|---|
| Magnetic buttons | `quickTo` x/y ≤ 6 px toward the pointer within a 1.6× hit radius, `power3.out` 0.45 s, release to 0; on `.btn--ghost`, `.btn--text`, `.section-header__link` only | On | Off | Off | Never on `.btn--primary` anywhere (the hero primary is the home buy path, and GSAP's inline transform would override the `.btn:active translateY(1px)` press), `.js-add-to-cart`, `.sticky-bar`, checkout/cart buttons. Skipped while `.is-loading`. |
| Custom cursor | 22 px gold ring `.sf-cursor[aria-hidden]` following via `quickTo` (0.18 s), grows to 3× with 0.3 opacity fill over `.product-card`, `.collection-card`, `.gallery__open`; `cursor:none` only on those targets | On, created only under `(hover:hover) and (pointer:fine)`, destroyed on media `change` | Never created | Never created | Never created on `body.cart`, `body.checkout`, `body.track`, `body.confirmation`. Native cursor kept on all form controls and inside `.drawer/.modal/.nav-mobile`; ring hides on `sf:dialog-open`, returns on close. |
| Gold underline draw | Already CSS on `.btn--text`, `.section-header__link`; extend the same `background-size` pattern to `.site-header__link` and footer links in `motion.css` | On | On (tap = focus state draws) | Instant underline | Pure CSS, no JS. |
| Add to Cart burst + flight | `cart.js` edit: after a successful `/api/cart/add`, dispatch `sf:cart-added {button, count, thumb}` and call `openDrawer` **immediately, as today — nothing is awaited**; the button keeps `.is-loading` until `sf:dialog-open` so a second tap in the gap cannot fire a second add. Burst: 14 `<i class="sf-spray">` spans from the button, `translate` + `scale` + opacity, 420 ms `power3.out`. Flight (desktop only): a 28 px clone of the thumb flies toward the drawer panel's header count (the panel sits above the z 400 overlay and is sliding in) for ≤ 400 ms `power3.in`, then the existing `count-bump` fires there (the only bounce on the site). Skipped on forms with `data-cart-reload`. | Burst + flight | Burst 8 spans + count bump only, no flight (the header icon is under the drawer overlay the moment it opens) | Count bump only | The request and the drawer are never delayed; the `pending` guard is honoured; the flight is fire-and-forget. |
| Numbers tick up | `.collection-card__count`, `.stars__count`, listing "N fragrances", quiz match strength: real text stays in DOM, an `aria-hidden` twin with `font-variant-numeric: tabular-nums` and `min-width: Nch` (N = final length, so siblings never nudge) counts 0→N over 900 ms `expo.out` on first `.is-visible` | On | On (IO, no GSAP) | Off | **Prices never tick** (`.price__now`, `[data-price-now]`, `[data-sticky-price]`) — see §8. |

### 2.6 Signature-scent quiz (`quiz.php`, `quiz-result.php`)

| Aspect | Plan |
|---|---|
| What | One question card at a time; answering flips the card to the next; the result page opens on mist that clears to reveal the recommended bottle, its name in gold, notes, a match-strength meter, price and Add to Cart. |
| How | Progressive enhancement over the existing single GET form (all five `fieldset.form__section` stay in DOM, untouched for no-JS). Under `html.motion--*`: fieldsets get `data-motion-step`, only the current one is visible; advancing happens on the **Next control**, or on a pointer `click` on a `.choice--answer` (never on `change` — arrow keys select as focus moves through a radiogroup) after a 600 ms undo window with the Next button visible throughout; the existing `#question-N` Back/Next anchors are intercepted *only inside the quiz form*. Desktop flip = current fieldset `rotateY(0→-90°)` + `autoAlpha 1→0` (380 ms `power3.in`), next `rotateY(90→0°)` + `autoAlpha 0→1` (520 ms `expo.out`), `perspective 1400px`, `backface-visibility hidden`. Progress bar `--fill` already exists. Result: `.product-card__media` `scale 1.08→1` + opacity and the `text-gold-grad` heading sweep *are* the reveal → notes stagger → `.meter` fill; the numeric `score` (max 33) is exposed from the controller as `data-match` for a "94% match" tick-up. Desktop only: a 400 ms mist veil over `.split__main` that never covers `.split__aside`. |
| Desktop | 3D flip as above, GSAP. |
| Mobile | No GSAP, no 3D (Adreno 6xx tears on `rotateY` over large cards): class toggles `is-leaving` / `is-entering` with `transition: transform .32s, opacity .32s` (`translateX ±24 px`), `transitionend` handoff with a 400 ms fallback. No result veil. Radios stay 44 px tall. |
| Reduced | All five questions stacked as today; result page painted as is. |
| Stays instant | Submit button always present and enabled; result page price, size, Add to Cart, WhatsApp, COD line have no motion class, are painted at first frame and are never under a veil on any device. |
| Cost | Desktop: GSAP core on `/scent-finder*` (27 KB gz, lazy on idle) + ~3 KB gz module. Mobile: ~1.2 KB gz module, no library. |
| Risk / fallback | Focus management: focus moves to the new `legend` only after the transition ends; screen readers get `aria-live="polite"` "Question 3 of 5". Five questions kept — the `?a=1-2-3-4-5` share URL contract is unchanged (§8). |

### 2.7 Smooth scroll + page transitions (`motion/lenis-bridge.js`, `motion/transitions.js`, `motion.css`)

| Aspect | Plan |
|---|---|
| What | Silky wheel scrolling on desktop; leaving a page pulls a soft mist over it, the next page clears from mist. |
| How | Lenis 1.x in **native scroll mode** (no wrapper transform), `lerp 0.1`, `wheelMultiplier 1`, `smoothTouch false`, **shipped with `flags.lenis:false`** and switched on only after Stage 9 QA passes with it on; `html.lenis{scroll-behavior:auto}` overrides line 229; `lenis.on('scroll', ScrollTrigger.update)`; `lenis.stop()/start()` on `sf:dialog-open/close`; `data-lenis-prevent` on `.drawer__body .nav-mobile .modal__body .lightbox__rail .rail .gallery__rail .suggest`; `SF.scrollTo(target)` helper lands in `ui.js` in **Stage 0** and every consumer (`unlockBody`, hero cue, quiz anchors, `forms.js` first-invalid scroll, `product.js` `scrollIntoView` / `rail.scrollTo`) is routed through it before Lenis exists. Transitions: **cross-document View Transitions**, desktop only: `@media (min-width:1024px) and (hover:hover) and (pointer:fine) and (prefers-reduced-motion:no-preference) { @view-transition { navigation: auto } }`, `::view-transition-old(root)` 160 ms fade to a mist tint, `::view-transition-new(root)` 200 ms clear (they run concurrently, 200 ms total) — zero JS, forms and 303 redirects untouched, Chrome/Edge 126+. Fallback where unsupported (desktop only): `.sf-veil` opacity 0→1 in 200 ms on same-origin left-click GET `<a>` only (excludes modifiers, `#`, `target`, `download`, `.js-cart-open`, `[data-dialog-open]`, `.drawer a`, `.sticky-bar a`, `[href*="/checkout"]`, `[href*="/cart"]`, `[href*="/track"]`, `/admin`, `wa.me`, Instagram; never installed on `body.cart/checkout/track/confirmation`) then `location.href`; veil reset on `pageshow` (`persisted`) and `pagehide`. No SPA router, no fetch of pages. |
| Desktop | Lenis behind its flag, transitions on. |
| Mobile | Lenis **off** (native momentum is smoother and free on mid-range Android); **no View Transitions** (with `navigation:auto` Chrome holds the old page's snapshot for the whole 1.5–3 s 4G HTML round-trip — the tap appears to do nothing and shoppers tap again); no JS veil. |
| Reduced | Lenis off, `@view-transition` excluded by the media query, no veil. |
| Stays instant | `position:fixed` for `.sticky-bar .drawer .toast-region .whatsapp-fab` unaffected in native mode; header `is-hidden` rAF listener keeps working; hash links land on the right element; drawer "View cart" / "Checkout" links never see a veil. |
| Cost | Lenis 4 KB gz desktop only, off by default; transitions ≈ 0.6 KB CSS + 1.5 KB gz JS fallback (desktop only). |
| Risk / fallback | Lenis is the one item whose failure mode is functional (a drawer that will not scroll, an anchor 80 px off) for a desktop-only gain; the flag stays `false` until the Stage 9 anchor/dialog audit passes with it on, and if two consumers remain unfixable it is dropped — `scroll-behavior:smooth` plus expo-out scrubs carry "silky". bfcache stuck veil → `pageshow` reset and a 900 ms CSS failsafe on `.sf-veil`. |

### 2.8 Ingredient depth layers (cross-cutting: hero, `#composition`, `#scent-finder`)

| Aspect | Plan |
|---|---|
| What | Rose petal, oud chip, citrus slice, smoke wisp cut-outs at three depths around the product; near ones larger and lower, far ones smaller and higher (Kumo); drift speed encodes depth. |
| How | `<img class="sf-layer" data-depth="0.2..1">` positioned with percent `top/left/width` per breakpoint (Tarot), shadows baked into the image, no CSS `filter`. Desktop: `quickTo` x/y from pointer (`±depth × 18 px`, lerp 0.08) plus ScrollTrigger scrub `y: depth × -120 px` across the host section. Assets do not exist yet — produced in Stage 8 as AVIF + WebP, 2 sizes (≤ 480w mobile, ≤ 960w desktop), q≈72, ~10–18 KB each. |
| Desktop / Mobile | Desktop: up to 4 layers in the hero, 3 in the story, cropped by the viewport edge for free depth. Mobile: **none** in the hero (at 360 px the type fills the width and every extra alpha layer is compositing budget on Adreno 610), none in the story by default; Mobile-high: one static layer in the PDP story only. Reduced: static, no drift. |
| Stays instant / Cost / Risk | Layers are `aria-hidden`, `loading=lazy`, sit below `.hero__body` in z; never overlap the headline or CTAs (positions keep a 12 % exclusion zone on desktop). ~50 KB per page desktop, 0 KB mobile (12 KB on a Mobile-high PDP). Risk: art that looks like clip-art cheapens the brand → one approval round on the cut-outs before they ship. |

## 3. The hero in depth

**Scene** (`motion/hero-ribbons.js`, loaded only on Desktop, after `load` → `requestIdleCallback`
→ hero ≥ 50% in view; there is no pre-benchmark — the runtime kill in the lifecycle row is the
only frame-time judge):

| Element | Spec |
|---|---|
| Ribbons | 4: one lead (radius 0.028) + three companions (0.016, 0.011, 0.008); each a `CatmullRomCurve3` through 6 control points hand-placed around the bottle silhouette (x −3.6..3.6, y −1.1..1.4, z −1.0..0.8), so they pass *behind* the bottle slot and never over the headline zone (y > 1.6 excluded). |
| Geometry | `TubeGeometry(curve, 72 tubular, radius, 6 radial, open)` → ≈ 3.5k triangles total. Built once; control points never change (motion comes from the shader and group transform). |
| Material | `ShaderMaterial`, `transparent`, `depthWrite:false`, `AdditiveBlending`. Fragment: `color = mix(gold #C29C6E, amber #E0A45C, sin(uv.x*freq − uTime*speed)*.5+.5)`; travelling pulse `exp(−d²·40)` at `fract(uTime*0.06 + idx*.25)` tinted mid-mix at 0.5 (never white); `alpha = smoothstep(0,.15,uv.x)·smoothstep(1,.85,uv.x)·sin(uv.y·π)`; output ×1.35. Per-ribbon `speed` 0.6–1.6 and `freq` 9–15 from config. Vertex: `pos.y += sin(pos.x*1.4 + uTime*0.7 + idx)*0.06` so the ribbons breathe without audio. |
| Faux bloom | No `EffectComposer`, no `UnrealBloomPass`. Each ribbon is drawn twice: the sharp tube and a 2.6× radius copy at alpha ×0.16 (same shader, `uHalo=1`) — 8 draw calls; plus one 512 px radial-gradient sprite (`SpriteMaterial`, additive, opacity 0.35) behind the bottle slot. Crossings brighten by additive maths on the true `--ink-900` ground. Real bloom is allowed only as a config flag (`hero.bloom: false`) for a desktop A/B after Stage 1 proves the frame budget with it (≤ 6 ms GPU at 1440p). |
| Camera / coupling | Fixed `PerspectiveCamera(fov 50, z 5.2)`, no controls. Pointer target: `rot.y = mx·0.05, rot.x = 0.12 + my·0.03` rad, `lerp 0.06/frame`. Scroll: hero progress `p` → `group.rotation.x += p·0.15`, `group.position.y −= p·0.6`, material `uFade = 1−p` so the ribbons are gone before `#collections`. Scroll/wheel never captured. |
| Renderer | `antialias:false` (dpr covers it), `alpha:true` over the CSS ground so the admin overlay/photo stays, `powerPreference:'high-performance'`, `dpr = min(devicePixelRatio, 1.5)`, canvas sized to the hero not the window. |
| Lifecycle | `IntersectionObserver` on `.hero` → `renderer.setAnimationLoop(null)` when < 5% visible; `visibilitychange` pauses; `webglcontextlost` → dispose + plates; `pagehide` and the transitions veil → `dispose()` (geometry, material, renderer, `forceContextLoss`). rAF budget check every 120 frames: mean > 20 ms twice in a row → dispose + plates. |
| Gate | `min-width 1024`, `pointer:fine`, `hover:hover`, `deviceMemory` ≥ 4 or undefined, `hardwareConcurrency` ≥ 4, no `saveData`, WebGL2, not Reduced, not `.hero--empty`-with-photo-ground darker than L 12 (checked once from the admin overlay value), `config.hero.webgl === true`. |

**Library.** The brief names Three.js. Vendored `three.module.min.js` (r17x) is ~168 KB gz for
four tubes; §8 recommends a hand-written WebGL2 module (~8 KB gz, same shader, same curve math)
and keeps Three.js as the fallback if the client prefers the library. Either way it is one file
under `assets/js/vendor/` or `assets/js/motion/`, `import()`-ed by `boot.js`, CSP-clean.

**The bottle, honestly.** There is no photograph. What exists: the gold lockup PNG already in the
hero, and twelve *generated* renders of twelve fictional bottles used as product images. Putting
one of those in the hero would present an invented object as *the* Sky Fragrances bottle. Until
photography arrives the hero object is the **monogram lockup** (real, brand-owned): the ribbons
wrap it as they will later wrap a bottle. The markup reserves `.hero__object[data-motion-slot]`,
and the admin hero image setting (`hero_image_desktop/mobile`, today empty) is extended with
`hero_object` — a transparent WebP of the real bottle drops in with zero code change. On the PDP
the generated renders stay where the client already accepted them (gallery, story, quiz result).

**Plates fallback (Mobile, desktop gate failures, context loss).** Transparent WebPs rendered
from the desktop scene: `ribbons-glow` (static, ≤ 12 KB, 540w), `ribbons-a` (≤ 20 KB, 540w) and,
for Mobile-high and desktop fallback only, `ribbons-b` (≤ 20 KB). `.hero__plate` layers; one 14 s
CSS keyframe on `transform: translate3d/scale` (`ease-in-out`, alternate) for A; the A↔B opacity
crossfade (12 s) exists only where B is present. `.hero{contain:paint}`; playback paused when the
hero is < 10 % visible and after 3 cycles. One static layer under Reduced. Compositing *is* the
budget on this GPU: at 1080×2400 every full-screen alpha layer is ~10 Mpx per frame, so the mobile
hero never exceeds two animating layers and the infinite loop is not infinite. Reads as the same
scene and works with scripts blocked.

## 4. Loader timeline

Full mode (home, first visit in 7 days, Desktop only). The clock starts when `motion/intro.js`
executes (`defer`, ≈ DOMContentLoaded, 300–800 ms after first paint on 4G); the veil has been
visible since first paint through the head gate, so "first paint + 1.6 s" is *not* what the user
sees — it is DCL + 1.6 s, and the LCP maths in §6 is done on that honest figure.

| ms | Event |
|---|---|
| 0 | Inline head gate (hashed, after `site.css`) has already set `html.sf-intro-full`; veil `.sf-intro` is opaque `--ink-900` at first paint with `pointer-events:auto`; the hero and header are fully painted beneath it, h1 at full opacity. |
| 0–1,000 | Particles (520, dpr ≤ 1.5, `lighter` composite) gather from a ring into the monogram alpha map; two mist layers drift. |
| 300 | Skip button visible. Any `pointerdown` **on the veil**, any `keydown`, or Escape finishes the intro; the tap never reaches what is underneath. |
| 950 | Monogram WebP fades in under the particles (`is-mark`). |
| 1,150 | Gold shimmer sweep (`is-shimmer`, 600 ms, `background-position`). |
| 1,200 | Tagline "More Than Just A Scent" fades in (`is-tag`). |
| 1,600 | Dissolve (`is-leaving`): veil opacity → 0 in 300 ms, mist layers scale 1.15; the hero gold sweep across the already-painted h1 starts; eyebrow and trust line follow at +600 ms (`--hero-delay`); `.hero__actions` is already at full opacity. |
| ~1,840 | Veil opacity < 0.2 → `pointer-events:none`. |
| 1,960 | Veil removed from DOM, `html.sf-intro-done`, `localStorage.sfIntro = now`. |
| 2,000 | **Hard cap**: CSS `sf-intro-failsafe` keyframe hides the veil even if JS died. |

Calm mode (home, first visit in 7 days, Mobile and Mobile-high): transparent veil,
`pointer-events:none` throughout; monogram in 250 ms → hold 200 ms → out 250 ms = **700 ms**;
no canvas, no mist; CSS-only (the gate sets `html.sf-intro-calm`, keyframes do the rest, the
`defer` script only writes the stamp); failsafe 900 ms.

Rules: `localStorage.sfIntro` holds a timestamp; older than 7 days or absent → intro; anything
else, any non-home page, Reduced, `saveData`, 2g/3g, or a throwing `localStorage` → **no veil
at all** (no `sf-intro-*` class, partial stays `display:none`). Quick mode no longer exists. LCP
protection: the h1 is painted at full opacity in its final position at first paint on every class
(a background-only `div` is not an LCP candidate, so the desktop veil does not move LCP; the
mobile veil is transparent); the veil carries no image except the 6 KB monogram, which has no
`fetchpriority`; the gate does no layout work; the particle canvas is created after DCL.
Lighthouse's fresh profile therefore gets the mobile Calm path with nothing opaque over the LCP
element.

## 5. Architecture

### 5.1 Vendored files (`assets/js/vendor/`, served same-origin, cache-busted by `asset()`)

| File | Version | gz | Licence | Loaded |
|---|---|---|---|---|
| `gsap.min.js` | 3.13.x | ~27 KB | GSAP Standard (free for all use since 3.13, Webflow) | **Desktop only**, after LCP on idle (home, collections, PDP, quiz); never on any mobile class |
| `ScrollTrigger.min.js` | 3.13.x | ~14.5 KB | same | Desktop only (home, collections, PDP) |
| `lenis.min.js` | 1.3.x | ~4 KB | MIT | Desktop only, behind `flags.lenis` (ships `false`) |
| `three.module.min.js` **or** `motion/ribbons-gl.js` | r17x / in-house | ~168 KB / ~8 KB | MIT / ours | Desktop hero only, post-load, in view (§8 #1) |

No import maps, no CDN, no `unsafe-eval`. Vendored ESM must import nothing bare; the UMD/IIFE
builds of GSAP and Lenis are used and expose globals, Three (if kept) is a single self-contained
module file.

### 5.2 Load strategy

| Step | Mechanism |
|---|---|
| Config | `assets/js/motion/config.json` is the **one** file: durations, eases, amplitudes, breakpoints, particle counts, hue table and per-section flags (`flags.hero/loader/fan/story/micro/cursor/cartFly/tick/quiz/lenis/transitions`, each `true`, `false` or `'desktop'`). PHP inlines it as `<script type="application/json" id="sf-motion">` (not executed, CSP-clean, no request) and prints its `css` sub-object as `:root{--sf-…}` custom properties in an inline `<style>` (`style-src` already allows inline), so CSS keyframes and JS read the same numbers. No `config.js` script tag, nothing blocking. |
| Sync gate | ~20 lines **inlined** in `<head>` after `site.css`, its SHA-256 added beside `CSP_BOOTSTRAP_SCRIPT_HASH` (the default, not the fallback; zero requests). It parses the JSON block, classifies the device with `matchMedia` + `navigator` (Desktop / Mobile / Mobile-high / Reduced, §2), sets `html.motion--*` or `html.sf-reduce`, and on `body.home` decides the intro mode from `localStorage`. Nothing else runs synchronously. |
| Boot | `motion/boot.js` `defer`, last in `layout.php` after `forms.js`; **only imports**: reads the class the gate set and the `body` page class, registers `SF.motion = {refresh}`, runs the Mobile-high frame check once the intro is gone, then `import()`s per page after `load` + `requestIdleCallback` (2 s timeout): home desktop → `hero`, `fan`, `layers`; home mobile → `plates` (IO pause only); product desktop → `story`, `layers`; product mobile → `story-lite` (IO hue swap); quiz → `quiz` (desktop) / `quiz-lite` (mobile); site-wide desktop → `micro`, `cursor`, `cart-fly`, `transitions`, `lenis-bridge` (flag); site-wide mobile → `cart-burst`, `tick`. No classification, no reveal stripping, no DOM writes before `load`. |
| Reveal handshake | Rule: **one system per node**, decided server-side: PHP prints owned containers (`#collections .rail`, `#composition .pyramid`) without `.sf-reveal` when `motion_enabled()` (a config flag read by PHP), and their desktop rest poses live in CSS under `html.motion--desktop`, so there is no runtime strip and no race with first paint. `reveal.js` keeps every other `.sf-reveal`; header/sticky-bar sentinels are never touched. `showAll()` stays the no-JS/Reduced reference. |
| CSS | ~1 KB of first-paint rules (veil, plates, fan rest poses, h1 clip reveal, `.hero__actions` opacity, `contain:paint`) go into `site.css @layer overrides` — one stylesheet, no extra RTT. `assets/css/motion.css` (`@layer motion`, ~4 KB gz: cursor, sweep, spray, story glows, quiz steps, underline extensions, one `prefers-reduced-motion` block) is linked by PHP **only** on `home`, `listing`, `collections`, `product`, `quiz`, `quiz-result`; `/cart`, `/checkout`, `/track`, `/order/*` never load it. |
| Per-page data | `<script type="application/json" id="sf-motion-data">` (the `#sf-sizes` pattern) for hue overrides, product thumb for the flight, quiz `data-match`. |
| Server edits | `layout.php` (inline gate, JSON block, `:root` style, conditional `intro.php` and `motion.css`), `response.php` (+1 hash constant), `head-meta.php` (font preloads fixed, logo swap), `intro.php` (home only, no `fetchpriority`), `home.php` (object slot, 2 plates, `.hero__actions` out of `.hero__enter`, owned rail without `.sf-reveal`), `product.php` (story wrapper), `quiz.php`/`quiz-result.php` (`data-motion-step`, `data-match`, Next control), `cart.js` (event + `.is-loading` until `sf:dialog-open`), `ui.js` (`SF.scrollTo`). Nothing in `api/`, checkout, cart, track, order. |

### 5.3 Collisions resolved

| Collision | Resolution |
|---|---|
| Lenis vs sticky header / drawers / body lock / anchors | Ships off. `SF.scrollTo` lands in Stage 0 and all eight scroll consumers are routed through it before Lenis exists; native-scroll mode only; `lenis.stop/start` on `sf:dialog-open/close`; `data-lenis-prevent` on inner scrollers; `html.lenis{scroll-behavior:auto}`; never on touch; enabled only after the Stage 9 audit passes with it on. |
| ScrollTrigger pin vs `.sticky-bar` | No pins anywhere. Story uses CSS sticky; ScrollTrigger only scrubs state and tweens. `ScrollTrigger.refresh()` on `sf:size-change`, accordion toggles, dialog close, image `load`, `resize` (debounced). |
| Cart flight vs drawer | `cart.js` dispatches `sf:cart-added` and opens the drawer immediately, nothing awaited; the button stays `.is-loading` until `sf:dialog-open` (closes the double-tap window); desktop flight targets the drawer panel above the overlay; mobile has no flight. |
| Page transitions vs PHP navigation | CSS cross-document View Transitions inside a desktop-only media query; JS veil only on plain same-origin GET links, desktop only, with the cart/checkout/drawer exclusion list; forms, POST/303, dialogs, external links untouched. |
| Loader vs LCP / no-JS / in-app browsers | Partial rendered on `body.home` only; veil exists only under `html.sf-intro-*`; mode from `localStorage` (7 days) so in-app webviews do not replay it per link; mobile veil transparent; h1 never hidden; 2,000 / 900 ms CSS failsafes. |
| CSP | All files same-origin; two hashed inline scripts (bootstrap + gate); config and per-page data as non-executed JSON blocks; textures are same-origin files; `img-src data:` covers the sprite gradient; no workers, no blob URLs, no import map. |

## 6. Performance plan

### 6.1 Byte budget (gzip, added on top of today's ~38 KB CSS+JS)

| Device class | Before LCP | After load, on idle | Images (lazy) | Never |
|---|---|---|---|---|
| Mobile | inline gate 0.4 + JSON 0.3 (in the HTML) + 1 KB overrides inside `site.css` ≈ **1.7 KB**; motion.css 4 on motion pages only | boot 1.5 + per-page lite module ≤ 1.2 (quiz) / 0.6 (story) / 0.8 (cart-burst + tick) | plates ≤ 32 (home), monogram 6 (home first visit) | GSAP, ScrollTrigger, Lenis, Three/GL, cursor, magnetic, veil JS, View Transitions, hero ingredient layers, wisps |
| Mobile-high | same | same | + plate B 20 (home), + 1 wisp 12 (PDP) | same list |
| Desktop | same 1.7 KB + motion.css 4 | boot 1.5 + GSAP 27 + ST 14.5 + modules ~14 ≈ **57 KB** (+ Lenis 4 when the flag is on) | plates (fallback only), layers 50, sprite 6, bottle 480w 20 | Three 168 KB / GL 8 KB only after load + idle + in-view |

Pre-existing bytes removed in Stage 0 (§6.3): ~70 KB of hero/header logo PNG and ~60–70 KB of
font weight on every page — more than the whole added mobile budget above, and the reason the
≥ 85 target is reachable at all.

Frame budget: ≤ 8 ms scripting + ≤ 4 ms GPU per frame while any section animates; ribbons and
layers on `transform`/`opacity` only; `will-change` added by the trigger while active and removed
at rest (also fixes the permanent `will-change` on `.sf-reveal`).

### 6.2 Lighthouse ≥ 85 mobile — method

`npx lighthouse <url> --preset=perf --form-factor=mobile --screenEmulation.mobile
--throttling-method=simulate` (dev machine has Node; production does not need it), 3 runs,
median, against (a) `php -S` local and (b) the Hostinger staging URL, on `/`, `/product/{slug}`,
`/scent-finder`, `/cart`. Run before Stage 0 (baseline) and after every stage; a stage does not
close below 85 or with LCP > 2.5 s / CLS > 0.05 / TBT > 200 ms. Real-device check each stage on a
mid-range Android (Snapdragon 6xx class, Chrome, 4G throttled in DevTools) at 360 px.

### 6.3 Image work

Stage 0, before any concept ships — the two pre-existing byte problems outweigh the whole motion
budget: (1) `lockup-transparent-400.png` (76,668 B) is rendered at 72/96 px as the hero mark and
as the header logo on every page → one 200w transparent WebP (~6 KB) replaces it in both places
(~70 KB, ~0.35 s of 4G, per page). (2) Fonts: Jost 50 KB + Cormorant 38 KB + a 39 KB italic the
design excluded, none preloaded because both `<link rel=preload>` tags in `head-meta.php` point at
files that do not exist → subset both variable fonts to Latin + `₨`/PKR glyphs with `pyftsubset`
(~18–22 KB each), drop the italic (or `font-display:optional` if a page still needs it), and
preload the two real files. Together ~140–160 KB and 300–500 ms off the h1's final paint.
Then: ingredient layers and wisps as AVIF + WebP `<picture>`, ≤ 480w / ≤ 960w, q≈72, baked
shadows. Hero plates: WebP 540w for mobile (≤ 12 / ≤ 20 KB), 960w for the desktop fallback, q≈75
(AVIF too if smaller). Monogram: 200w WebP (~6 KB), no preload, no `fetchpriority`.

### 6.4 Kill-switch order (each is one flag in `config.js`; applied top-down until the budget holds)

1. `hero.webgl` → plates on desktop too. 2. `layers.pointer` → scroll-only, then `layers` → static.
3. `lenis` off (already the shipped state). 4. `fan.scrub` → one-shot stagger, then `fan` → CSS
only. 5. `loader` → `'desktop'` (mobile Calm off), then `false`. 6. `cursor`, `magnetic` off.
7. `transitions` → CSS View Transitions only. 8. `story.tilt` off (hue cross-fade stays).
9. `hero.plates.mobileHigh` off (one drifting plate everywhere). Nothing on this list touches
price or Add to Cart.

## 7. Accessibility

| Area | Rule |
|---|---|
| Canvas / plates / layers / veils / cursor | `aria-hidden="true"`; `pointer-events:none` for all of them except the desktop intro veil while opaque (it must swallow the skip tap); nothing sellable or readable lives in them; the h1 is one unsplit text node. |
| Focus | Custom cursor never hides focus rings; `:focus-visible` gold 2 px outline on all controls; loader and veils are not focusable and do not trap focus; the quiz moves focus to the new `legend` and announces "Question N of 5" via `aria-live="polite"`; fanned cards keep DOM order and tab order. |
| Keyboard | Every hover effect has a focus twin (underline, card lift, cursor irrelevant); Escape and Tab behaviour of drawers/modals unchanged; Back/Next anchors keep working with Enter. |
| Reduced motion | `html.sf-reduce` → no `motion--*` class, no module runs, no intro veil (`sf-intro-*` never set), plates static, fan in grid pose, h1 visible, hue static, quiz stacked, Lenis off, View Transitions excluded by the media query. **Reduced means instant**: `site.css` line 5221 zeroes every animation and transition with `!important`, and a later `@layer motion` `!important` cannot beat an earlier-layer `!important`, so "calm 250 ms fades" are not promised anywhere. Battery Saver has no web signal; only `saveData` is honoured. |
| Contrast | `--gold-400 #D4B084` on `#0A0A0A` ≈ 9.6:1; `--gold-600 #A87F4E` ≈ 5:1 (large text only). Headline never sits over the brightest glow (12 % exclusion zone; sprite opacity ≤ 0.35); ribbons fade to `uFade 0` before body copy. |
| Screen readers | Ticking numbers are `aria-hidden` twins of the real text; loader tagline is decorative (`aria-hidden` on `.sf-intro`); cart flight is silent, the drawer's existing `aria-live` region announces the add. |

## 8. Concepts I recommend changing, and why

| # | Brief asked | Recommendation | Why |
|---|---|---|---|
| 1 | Three.js for the hero ribbons | Hand-written WebGL2 module (`ribbons-gl.js`, ~8 KB gz: Catmull-Rom sampling, tube mesh builder, one shader pair) with Three.js kept as the opt-in alternative | Four tubes and one sprite do not need 168 KB gz of library; on Hostinger shared hosting with no build step that file cannot be tree-shaken; every desktop first visit would pay it. Same look, 20× smaller, disposes cleanly. Client's call; both are planned. |
| 2 | Subtle bloom (UnrealBloomPass) | Faux bloom: halo tube copies + one radial sprite; real bloom behind a desktop-only flag for an A/B after Stage 1 | Full-screen HDR target + mip passes is the frame-time killer on integrated GPUs and on every phone; additive maths on a black ground already gives the luminous crossings. |
| 3 | Mobile hero = CSS/SVG **or short looping video** | Two pre-rendered WebP plates (one static, one drifting), no video | A ≥ 400–600 KB autoplaying MP4 on 4G competes with the LCP image, often fails autoplay on Android data-saver, and stalls the first scroll. Two plates cost ≤ 32 KB lazily and stay inside the Adreno 610 compositing budget; three or more do not. |
| 4 | Bottle slowly rotating/tilting in the story | Sticky bottle with a ≤ 6° tilt, note-coloured glow and swapping wisps; turntable frames added later | There is no 3D model or turntable photography, and none of the bottles are real. A convincing rotation from one flat render is not achievable honestly; the tilt + state change (Kumo's flavour switch) carries the idea. |
| 5 | Numbers **and prices** tick up | Tick counts and match strength only; prices are static text from the server | A price that is still counting is a price the customer cannot read; on size change `product.js` swaps the string instantly and a tween would show wrong numbers for ~900 ms. Conversion and honesty both say no. |
| 6 | Ingredient parallax on mouse **and** scroll everywhere | Desktop: pointer + scroll in hero and story only. Mobile: none in the hero, one static layer in the story on Mobile-high only, no gyroscope | No art exists yet; every layer is a full-screen compositing surface on Adreno 6xx and at 360 px there is no room beside the type; gyroscope needs a permission prompt on iOS and interrupts the first tap. |
| 7 | Quiz with 3–4 questions | Keep 5 questions, show one card at a time | The result URL `?a=1-2-3-4-5` and its regex hard-code five answers; changing the count breaks every shared result link and the scoring weights. One-card-at-a-time makes five feel shorter than four stacked. |
| 8 | Lenis smooth scroll | Desktop only, shipped **off** until the anchor/dialog audit passes with it on | On mid-range Android native momentum scrolling is smoother and free; on desktop it is the one item whose failure is functional (drawer that will not scroll, anchor off by the header height) for a feel that `scroll-behavior:smooth` and expo-out scrubs already approximate. |
| 9 | Magnetic buttons | Not on Add to Cart, the sticky bar or any checkout/cart control | The button people pay through must not move under the pointer; magnetism stays on the hero CTA and ghost/text links where it is a flourish, not a target. |
| 10 | Loader "monogram stroke draws" | Mist gathers into the monogram (particles), shimmer, dissolve; total 1.6 s | No vector monogram exists (`logo-mark.svg` wraps a bitmap); a stroke draw would need a hand-traced SVG (open question 1). The particle gather is already built and is truer to "mist". Cut from today's 2.1 s to 1.6 s to honour "< 2 s". |
| 11 | Mist page transitions | CSS cross-document View Transitions **desktop only** (media-query gated, 200 ms), JS veil fallback desktop-only, nothing on touch | Zero-JS path cannot break forms or redirects; on mobile even the native version holds the old page's snapshot for the whole 4G round-trip, so a tap looks dead and gets repeated. |
| 12 | Custom cursor over products | Keep, but tiny (22 px ring), destroyed on hybrid devices, hidden in dialogs and on form controls, never on cart/checkout/track | The brief's version is fine on desktop; the guards are what stop it hurting hybrids, keyboard users and the checkout. |
| 13 | Headline revealed line-by-line with a mask | Desktop: one clip reveal of the whole h1 from first paint + a gold sweep; mobile: static | Measured line boxes break when the display font swaps in on 4G and the gradient splits per span; any opacity/clip gate on the h1 moves LCP past 4 s on mobile. The wipe reads as the same gesture. |
| 14 | Loader on first visit per session, quick fade after | Home page only, first visit in 7 days (`localStorage`), nothing otherwise; mobile version is a 700 ms transparent monogram fade | Most Pakistani traffic arrives from WhatsApp/Instagram links into a fresh in-app webview with empty `sessionStorage`, straight onto a product page: the brief's rule would put a black screen before the price on the most common path. |
| 15 | Item flies to the cart icon, then the drawer | Burst + drawer at once; the flight (desktop only) goes to the drawer's own count | Waiting for a flight leaves Add to Cart tappable for a second add and delays the Checkout button; the header icon is under the drawer overlay anyway. |
| 16 | Auto-advance the quiz when an answer is chosen | Next control, or a pointer click with a 600 ms undo; never on keyboard `change` | Arrow keys select radios as focus moves, so keyboard users would answer by reading; a mis-tap on touch would be recorded before it can be corrected. Wrong answers end in a result nobody believes. |
| 17 | Numbers tick up "on reveal" | Only counts and the match score, on an `aria-hidden` twin | Covered under #5; layout is held with tabular figures so nothing shifts. |

## 9. Implementation stages

| Stage | Work | Est. | What the client sees after it |
|---|---|---|---|
| 0 Foundation | `config.json` + PHP inlining, hashed inline gate + device classes, `boot.js` importer, first-paint rules in `site.css`, `motion.css` page-gated, vendoring GSAP/ST/Lenis, `SF.scrollTo` + routing all eight scroll consumers, server-side reveal handshake, **logo WebP + font subsetting + real preloads**, Lighthouse baseline | 9 h | No motion yet; Lighthouse mobile already up (~140–160 KB lighter per page); baseline scores and the flag list. |
| 1 Hero | h1 clip reveal + sweep (desktop), `.hero__actions` decoupled, product-alone beat (desktop), `.hero__object` slot, 2-plate mobile hero with IO pause, WebGL ribbons (GL module or Three), gates, lifecycle, pointer/scroll coupling | 13 h | Live hero on desktop with ribbons; one drifting plate on the phone with the headline and CTA there from the first frame; before/after fps + Lighthouse. |
| 2 Loader refit | Home-only partial, `localStorage` stamp, Full (desktop) / Calm (mobile) modes, veil tap = skip, timeline, failsafes, hand-off into the hero beat | 4 h | The 1.6 s intro once per week on desktop, a 700 ms monogram breath on the phone, nothing on product pages. |
| 3 Collection fan | CSS rest poses, scrub spread, hover tilt + sweep, `fan.replay` flag | 5 h | Fanned collections on home and `/collections` (desktop); the rail as is on mobile. |
| 4 Story | Sticky bottle column, two hue layers, tilt, note stagger, wisp slots, refresh hooks; mobile IO hue swap | 7 h | Top → Heart → Base on any product page; nothing moved in the buy column. |
| 5 Micro | Magnetic (ghost/text only), cursor with page exclusions, underline extension, cart burst + drawer-targeted flight (`cart.js` event, `.is-loading` hold), count tick with tabular twins | 7 h | Add to Cart burst on every product card and the PDP, flight on desktop; cursor on desktop. |
| 6 Quiz | One-card flow with Next control + undo window, desktop flips / mobile CSS slides, focus + live region, result reveal, `data-match` | 8 h | The full quiz to result experience on both devices. |
| 7 Transitions + Lenis | Desktop-only View Transitions, veil fallback with exclusions, Lenis bridge behind its flag, anchor/dialog audit | 6 h | Mist between pages on desktop; Lenis switched on only if the audit is clean. |
| 8 Assets | Ingredient cut-outs (4), wisps (2), hero plates (3 renders, 2 sizes), AVIF/WebP pipeline, one approval round | 5 h | The depth layers appear in the desktop hero and story. |
| 9 QA per stage | Desktop + 360 px + Mobile-high + reduced-motion + no-JS passes, Lighthouse and real Snapdragon 6xx runs, kill-switch rehearsal, cart/checkout/in-app-browser regression, Lenis go/no-go | 9 h (spread) | Short report after each stage, as the brief asks. |

**Total ≈ 73 h** (range 64–82 h depending on Three.js vs GL and asset approval rounds). Order is
the client's: hero → loader → collection → story → micro → quiz → transitions; Stage 0 must come
first (it carries the byte savings the ≥ 85 target depends on) and Stage 8 runs alongside 1 and 4.

## 10. Open questions (each changes the work)

1. **Vector monogram**: can the designer supply an SVG/AI of the SF mark? Yes → a true stroke-draw
   loader (+3 h) and sharper header assets; no → the particle gather stays.
2. **Hero object until photography**: approve the lockup as the hero object with a reserved bottle
   slot, or supply a real bottle photograph now (transparent, front-lit, ≥ 1600 px tall)? The
   ribbons are tuned around a vertical silhouette either way, but the admin `hero_object` setting
   is only worth building if a photo is coming.
3. **Hero background**: the ribbons need a near-black ground. If a hero photo will be uploaded via
   the admin setting, agree that it is dark (overlay ≥ 60 %) or the WebGL gate turns ribbons off.
4. **Three.js or the in-house GL module** for the ribbons (§8 #1). Changes ~160 KB of desktop
   transfer and ~2 h of work.
5. **Per-note hues**: approve a `scent_family → hue` table (e.g. citrus → pale gold, floral → rose
   gold, oriental/oud → amber-red, woody → deep bronze, fresh → cool champagne) as the default,
   with per-product overrides added to the admin later?
6. **Ingredient art**: commissioned photography of real petals/oud/citrus on black, or generated
   cut-outs approved by the client? Decides Stage 8's cost and one approval round.
7. **Quiz**: confirm keeping five questions (share links unchanged) rather than the brief's 3–4.
8. **Fonts**: approve subsetting Jost and Cormorant to Latin + `₨` and dropping the italic. Any
   Urdu copy added later needs a separate font, not a re-subset of these.

## 11. Reviewed and rejected or adjusted (from `plan-critique.md`)

Every blocker and high finding is applied above, and every medium and low as well. Four were
adjusted rather than taken verbatim:

| Finding | What was asked | What the plan does instead | Why |
|---|---|---|---|
| B2, `fetchpriority=high` "except on the home first visit" | Keep the hint on the first visit | Removed everywhere | The first-visit decision lives in `localStorage`; the server that prints the `<img>` cannot see it. Dropping the hint costs the desktop intro nothing measurable (the veil is opaque for 950 ms before the mark is needed) and guarantees the h1 and hero image win on every load. |
| M2, "boot.js inserts motion.css only on pages that use it" | Inject the stylesheet from JS | PHP links it only on motion pages | A JS-inserted stylesheet arrives after first paint and re-styles below-fold sections in view (flash on `/collections`). Server-side page gating gives the same zero cost on cart/checkout/track with no late style; on motion pages the request is discovered by the preload scanner alongside `site.css`, so the extra RTT is overlapped rather than added. |
| H5, "split on designer-approved word groups if lines are required" | Word-group split as the fallback | No split at all; one clip reveal of the whole h1 | Approved groups still break differently at 360 px, 768 px and with admin-edited copy; the wipe carries the "revealed" gesture with none of the maintenance. |
| L3, "tilt an inner wrapper and pad the rail" | Keep a tilt via a wrapper | Tilt dropped on mobile | The wrapper plus padding buys a ±3° pose nobody asked for on a carousel that already reads as a hand; less CSS, no clipping to test. |

Rejected outright: none. The brief's own items the plan declines are in §8.
