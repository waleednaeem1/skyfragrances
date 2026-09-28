# Motion layer — design review (Awwwards-jury pass), 2026-09-28

Reviewed against `00-brief.md` (dark, luxurious, mysterious, sensual; restraint; one hero moment
per screen; nothing competes with buying) and `PLAN.md`. Sources: the 12 full-page shots in
`docs/shots/motion/`, plus 40 mid-state captures taken on the running dev server
(`php -S 127.0.0.1:8091`, Playwright Chromium 1208, 1440×900 and 375×780 @2x touch) for the
states a full-page shot cannot show: the intro at 300–2300 ms, the fan rest pose and mid-scrub,
card hover at 220/720 ms, the cursor over a product, Top→Heart→Base on the PDP, the cart burst
at 140/360 ms, the quiz flip at 250/500 ms, and the result reveal at 300/800/1200 ms. Code read:
`assets/css/motion/*.css`, `assets/js/motion/*.js`, `assets/js/intro.js`, `app/partials/intro.php`,
`app/views/{layout,home,product,quiz,quiz-result}.php`, `app/partials/{collection-card,product-card,notes-pyramid}.php`.
Paths below are relative to `site/`. Line numbers are from the files as reviewed.

Verdict in one line: the buy path is untouched everywhere (verified), the quiz and the story are
jury-grade, the ease vocabulary is disciplined — but the hero ribbons read as neon light-trails
rather than scent, the intro veils product pages on every first visit, and the fan is repeated on
every grid until it looks like a template. Fix the blocker and the three highs and this is a
signature site.

## Ranked findings

### 1. BLOCKER — The intro veils every page on first visit, including the product page, on desktop and mobile

Observed: fresh context → `/product/azure-oud` at 1440: `html.sf-intro-full`, opaque `#0A0A0A`
veil, monogram at 900 ms, still `is-leaving` at 1500 ms, gone at 2300 ms. Price, Add to Cart and
the COD row are under black for ~2.1 s. At 375 touch: the same `sf-intro-full` (particle canvas,
opaque veil), gone at ~1.6 s. PLAN §2.2 / §8 #14 exist precisely to prevent this ("WhatsApp link →
product page got 1.3 s of black before the price"): home only, first visit in 7 days via
`localStorage`, mobile = transparent 700 ms calm breath, no particles, no canvas.

Cause:
- `app/views/layout.php:33` prints `partial('intro.php')` on every storefront page.
- `assets/js/intro.js:12` reads `sessionStorage` (empty in every WhatsApp/Instagram webview, so
  it replays per link), `:18` picks `full` unless reduced/low-power (mobile is never `calm`),
  `:19` adds the class on every page with no page check.

Change (tuning, not a redesign — the approved look is untouched):
- `layout.php:33` → `<?php if ($isHome) { partial('intro.php'); } ?>` (`$isHome` is already
  passed to `header.php` two lines later).
- `intro.js:10-19` → decide the mode as
  `var home = !!document.currentScript && document.currentScript.dataset.intro === 'home'`
  (print `data-intro="home"` on the script tag in `head-meta.php:117` only when `home` is in
  `$bodyClasses`); if `!home` return before adding any class. Replace `sessionStorage` with
  `localStorage.sfIntro` holding a timestamp, intro only when absent or older than 7 days;
  a throwing `localStorage` = seen.
- `intro.js:18` → `mode = mobile ? 'calm' : 'full'` (mobile = `matchMedia('(max-width:767px), (hover:none)')`);
  `calm` must set `pointer-events:none` and a transparent veil: add
  `html.sf-intro-calm .sf-intro { background: transparent; }` next to `site.css:5544`.
  Delete the `quick` branch (`:18`, `:52`, `:195-198`, `site.css:5545`).

### 2. HIGH — The ribbons read as neon light-trails, not scent

Observed at 1440 (`hero-idle`, `ribbon-zoom`): four hard-edged tubes with a white-hot core
(≈ #FFF6DC), aliased edges, six to eight bright crossings sweeping the full width and passing
~40 px above "More Than". On 375 the same streaks are a pre-rendered plate. Both look like the
stock "golden light wave" PNG every template ships — the one thing the brief said not to be. The
brightest pixel on the page is white-yellow, not the brand gold, so the headline's gradient loses
the luminance contest and the gold discipline ("gold only at the edges") breaks in the hero.

Cause: `ribbons-gl.js:45` `col * (1.0 + pulse * uPulse.w)` with `uPulse.w = 0.5`, additive
blending (`:268`) and `uGain` push the core past 1.0 → clipped to white; `:46` alpha falloff
`sin(vUv.y·π)` gives a flat bright band; `:222` `antialias:false` with dpr ≤ 1.5 on 6-segment tubes;
`config.js` `hero.ribbons.speed [0.6,1.6]` and `freq [9,15]` make a fast running shimmer, which is
light, not smoke.

Change (all in `config.js` + two shader lines):
- `ribbons-gl.js:45` → `col = mix(col, mix(uGold, uAmber, uPulse.z), pulse * 0.5); col = min(col * (1.0 + pulse * uPulse.w), uAmber * 1.15);`
- `ribbons-gl.js:46` → `... * pow(sin(vUv.y * 3.14159265), 1.8)` so the tube has a soft centre and no rim.
- `ribbons-gl.js:222` → `antialias: true`.
- `config.js hero.ribbons`: `radii [0.05,0.032,0.022,0.015]` → `[0.038,0.026,0.018,0.012]`;
  `speed [0.6,1.6]` → `[0.22,0.55]`; `freq [9,15]` → `[4,7]`; `pulse.gain 0.5` → `0.2`;
  `halo { scale 3.0, alpha 0.14 }` → `{ scale 4.5, alpha 0.2 }`; `gain 1.0` → `0.72`;
  `breathe.amp 0.06` → `0.1`. Drop `paths[2]` (the third companion) — three ribbons, two
  crossings, reads as one gesture instead of a light show.
- Keep every ribbon at `y ≤ -1.3` for `|x| < 3.4` so nothing comes within a line-height of the
  headline (today `paths[0][3]` and `paths[2][3]` sit at y 0.45/0.8 over the object and dive past
  the eyebrow).
- Re-render the four plates from the corrected scene, with a 1.5 px gaussian blur baked in
  (this also fixes #8).

### 3. HIGH — The story hue fights the product's own colour

Observed on `/product/azure-oud` (`story-top`, `story-heart`, `story-base`): the sticky bottle is
a cold blue render with its own baked-in blue aura; the section glow and the halo behind it go
tan → rust → brown (`oud-smoke` row of the hue table). A warm-brown room around an ice-blue
bottle reads as two designs on one screen, and the halo is invisible against the render's blue
glow — the "note changes the light" idea does not land, which is the section's one hero moment.

Cause: `story.js:14-30` picks hues by `data-story-family` (`product.php:161`); the family table
in `config.js story.hues.families` has no relation to the product art or the collection
("Midnight Meridian" = deep blue, "Azure Oud" = cold blue sky).

Change:
- Print the collection's colour as the override PLAN §2.4 already allows:
  `product.php:161` add `data-motion-hue-top/-heart/-base` from a `collection → hues` map
  (Midnight Meridian `#5E7FB4 / #8C6FA6 / #B08458`, Azure Heights `#7FB3D5 / #D8DCC2 / #C4AE8E`,
  Golden Hour `#E0A45C / #C8703A / #96502E`, Dawn Chorus `#E8C2B4 / #D9A3A0 / #B98A91`,
  Monsoon Veil `#8AA07C / #6A7554 / #5E3B33`) — the arc should be "the bottle's own sky warming
  into its base note", so the base hue may stay warm.
- `30-story.css:117-119` raise the halo so it shows against the render:
  `rgb(var(--sf-story-rgb) / 0.30)` → `0.42`, and move it in front of the render's aura with
  `mix-blend-mode: screen` on `.story__halo`.

### 4. HIGH — One fan became four: the trick repeats on every grid and rotates prices

Observed (`bestsellers-fan`, `fan-entering`, shop page): the collections fan (PLAN §2.3, "the
five collection cards") is also applied to Best Sellers, New Arrivals and every 3-card row of
`/shop`. On the home page a shopper sees the same hand-of-cards gesture three times in 2,000 px;
on `/shop` it plays on every row for as long as they scroll. Repetition is what makes motion look
like a template. It also rotates product names and prices by up to 11° while they are read —
PLAN §1 says price is never part of the choreography.

Cause: `home.php:85` (`.rail--cards`) and `:98` (`.grid--products`), `listing.php:198` carry
`data-motion="cards"`; `20-cards.css:57-99` supplies poses for `.rail--cards` and `.grid--products`.

Change:
- Remove `data-motion="cards"` from `home.php:85`, `home.php:98` and `listing.php:198` — they keep
  `.sf-reveal.sf-reveal--stagger` and get the 600 ms expo-out stagger every other section gets.
- Delete `20-cards.css:57-99` (the `.rail--cards` / `.grid--products` pose cycles) and the
  `body.home .grid--products` branch at `:74-95`.
- Keep the hover tilt + lift on product cards (it is `data-motion-card`, not the fan): move
  `enableHover` so it runs for `[data-motion-card]` roots without a fan — in `cards.js:15-19`
  call `markSpread(root); enableHover(state)` when the root has no `.grid--mosaic`.

### 5. MEDIUM — The mosaic fan itself: the 2:1 hero card tilted 8° reads as a fallen billboard

Observed (`fan-entering`, `fan-mid`): the large "Dawn Chorus" card (half the row wide) rotates
−8° and slides 12 % while the four small cards fan. A hand of cards needs cards of one size; a
tilted billboard next to four tilted postcards looks like a layout bug for the 600 ms it lasts.

Change: `20-cards.css:30-33` `:nth-child(1)` → `--sf-fan-r: 0; --sf-fan-x: 0%; --sf-fan-ry: 1.5;`
(it rises into place while the four small cards fan around it). Optionally tighten the small
cards' spread so they overlap the big card less: `:nth-child(3)` `--sf-fan-r: 0.9` → `0.7`.

### 6. MEDIUM — Intro timeline runs 2.1 s and uses generic `ease`

Observed: `config.js intro` → `dissolveAt 1780 + dissolveMs 340 + 60` = 2.18 s to gone (brief:
< 2 s; PLAN §4: 1.6 s to dissolve, 2.0 s hard cap), clock starting at DCL, so on 4G the real black
time is longer still. The veil's exit (`site.css:5533-5541`) and the calm/quick fades
(`:5545-5546`) use CSS `ease`, the only place on the site that does — the dissolve is the one
moment that hands over to the hero and it should share the hero's curve.

Change (`config.js intro`): `markInAt 1000 → 950`, `shimmerAt 1220 → 1150`, `tagAt 1280 → 1200`,
`dissolveAt 1780 → 1600`, `dissolveMs 340 → 300`, `failsafeMs 2600 → 2000`, `particles.desktop
680 → 520`, and `site.css:5434` `2.6s → 2s`. `site.css:5535, 5540, 5545, 5546`: `ease` →
`var(--ease-out)`.

### 7. MEDIUM — The headline's "J" descender is clipped in every hero shot

Observed (`title-zoom`, all four home shots, desktop and mobile): "Just" ends in a truncated
tail; a stray fragment of the J sits below the baseline. It is the most-seen word on the site.

Cause: `.hero__title` `line-height: 1.02` (`site.css:2593`) with `background-clip: text`
(`:406-411`) paints no gradient outside the line box, and `10-hero.css:100-103` keeps
`clip-path: inset(0 0 0 0)` forever (`animation-fill-mode: both`), which also cuts ink outside
the border box.

Change: `10-hero.css` add `html.motion--desktop .hero__title, .hero__title { padding-block: 0.06em 0.16em; margin-block: -0.06em -0.16em; }`
and end `@keyframes sf-hero-clip` at `inset(-0.1em 0 -0.2em 0)` (start at `inset(-0.1em 0 100% 0)`).
The same padding fixes the mobile (static) headline, so it belongs in `site.css:2588` rather than
the motion file if the site owner agrees.

### 8. MEDIUM — Mobile hero plates are upscaled ~4× and show sawtooth edges

Observed (`home--mobile.png`, `m-plate-zoom`, `m-hero` @2x): the ribbon edges are stair-stepped.
`ribbons-a-540.webp` is 540×270; `10-hero.css:44-46` renders it at `--hero-object × 13.33` ≈
1,100 CSS px wide at 375 px, i.e. 2,200–3,300 device px at dpr 2–3.

Change: bake a 1.5 px blur into the 540 plates when re-rendering for #2 (soft ribbons hide the
upscale and read as mist), and reduce the multiplier `13.33 / 6.67` → `10 / 5` in
`10-hero.css:23-24` and `:44-45`; if the budget allows, add a `(min-resolution: 2dppx)`
`data-plate-a-2x` pointing at the 960 file (37 KB) for mobile-high only.

### 9. MEDIUM — Two cursors, and a 66 px gold disc over the bottle

Observed (`hero-pointer-left`, `product-hover-cursor`, `quiz-armed`): the 22 px ring trails the
native arrow everywhere (`cursor:none` only on cards), so the shopper sees two cursors; over a
product card it grows to a 66 px disc with a 0.3 fill that sits on the product image. A ring that
follows the arrow around form pages and the quiz is the classic "agency demo" tell.

Change: `micro.js:387-402` show the ring only while over `cursor.targets` (`is-visible` toggled
with `is-grown`, fade 280 ms out on leave) and never elsewhere; `config.js micro.cursor`
`grow 3 → 2`, `fillOpacity 0.3 → 0.12`; `40-micro.css:121-131` restrict `cursor:none` to
`.product-card__media` and `.collection-card__media` so the name and price keep the arrow.

### 10. MEDIUM — The Add-to-Cart spray is lost behind the drawer

Observed: DOM sampling after the click — 14 `.sf-spray` spans exist from ~140 ms, the drawer is
already `is-open` and the overlay at opacity 1 in the same frame; in the 140/360 ms captures the
burst is not perceptible. The eye follows the 26 rem panel sliding in and the page dimming; the
4–8 px dots at the button are invisible in practice, so the one "mist" gesture the brief asked
for on the buy button never registers. (PLAN's rule that the drawer is never delayed is right —
the spray is in the wrong place, not the drawer.)

Change: `micro.js:255-273` — on desktop emit the spray from the drawer's new line thumbnail
(`.js-cart-drawer .cart-line:first-child img`, after `sf:dialog-open` + 120 ms) instead of the
button, and keep the count bump; on mobile keep the button origin but spray upward only
(`burstSpread.arcDeg 150 → 90`). Alternative with zero JS: `site.css:1673` `.drawer-overlay.is-open { transition-delay: 160ms; }`
so the page holds its light for the burst's first half.

### 11. MEDIUM — The quiz flip is a 90° door on a 760 px card

Observed (`quiz-flip-250`): the whole 760 px fieldset swings `rotationY −90°` with
`transformPerspective 1400`; at mid-turn the far edge flies to 1,180 px and the copy shears —
this is a slide-deck "cube" transition. A tarot flip works on card-sized objects; at this width it
needs a shallower turn.

Change (`config.js quiz`): `flipOut.rotate −90 → −34`, `flipIn.rotate 90 → 34`,
`perspective 1400 → 2600`, and add `x: dir × −48 / +48` to the two tweens in `quiz.js:69-70`
(`{ rotationY, x, autoAlpha }`) so the card "turns and slides" instead of "opens". Keep 380/520 ms.

### 12. LOW — The card hover sweep is invisible on bright cards

Observed (`card-hover-220`, `card-hover-720` on Golden Hour): no sweep is visible at either
frame; the 0.16–0.30 gradient disappears on a golden image.

Change: `20-cards.css:122-129` peak `rgba(212,176,132,0.30)` → `rgba(242,230,210,0.5)` with
`mix-blend-mode: soft-light`, or remove the sweep and keep the tilt (one hover gesture is enough).

### 13. LOW — Counts tick 0 → 2

Observed: "2 fragrances" counts 0, 1, 2 over 900 ms; a number that small ticking reads as a
glitch, not a reveal.

Change: `micro.js:88` `if (!(value > 0) || el.children.length)` → `if (!(value >= 10) || el.children.length)`.

### 14. LOW — The story bottle's mask shows the render's rectangle

Observed (`story-mask-zoom`): the render's blue aura stops in a soft rectangle inside the mask.

Change: `30-story.css:135-136` `ellipse 62% 72% ... #000 52%, transparent 98%` →
`ellipse 46% 60% at 50% 50%, #000 34%, transparent 82%`.

### 15. LOW — Result-page notes rise as three separate groups inside one wrapped line

Observed (`result-1200`): the three `.quiz-notes__group` spans rise 60 ms apart, so a wrapped
line has uneven baselines mid-animation.

Change: `50-quiz.css:174-177` animate `.quiz-notes` as one block (drop `display:inline-block`
and `--i` on the groups; keep the 1.25 s delay), or stagger opacity only.

### 16. LOW — `.hero__enter` legacy stagger and the motion delay both apply

`site.css:2634-2638` still translates the eyebrow 1.5 rem with `--dur-slow`, and
`10-hero.css:92-94` re-times it under `motion--desktop`. Two owners of one keyframe; move the
rule into `10-hero.css` so the delay math is in one place (PLAN §2.1 wants ≤ 600 ms desktop /
≤ 300 ms mobile from first paint, which the mobile branch does not currently set).

## Cross-cutting judgement

- Timing and easing: consistent. Site 150/280/600 ms, motion 400/600/700/900/1,100 ms; every
  ease is expo-out or power3-out (`--sf-ease-out`, `--sf-ease-silk`), the only `power3.in` is the
  leaving side of the flip, the only bounce is the cart count. Two strays: the intro's generic
  `ease` (#6) and the quiz "armed" line, which is `linear` on purpose (it is a timer) — correct.
- Gold discipline: good below the fold (one gold rule under the current tier, one gold match
  line under the chosen answer, gold underline draws, gold ring cursor); broken in the hero by the
  white-hot ribbons (#2) and stretched by the 66 px cursor fill (#9).
- Ribbons as scent vs neon: neon today (#2). The plates make it worse by freezing the streak.
- The fan: right idea, wrong cast and wrong frequency (#4, #5).
- Story pacing: the best-paced section on the site — sticky bottle, 30 vh tier bands, 60 ms note
  stagger, 900 ms silk hue cross-fade, gold rule draw. Only the hue choice is wrong (#3).
- Quiz reveal: excellent. Card back → 900 ms turn → mist clearing mid-turn → gold sweep →
  notes → match tick → meters, CSS-driven from first paint; price and CTA never wait.
- Cursor: too present (#9). Magnetism is scoped correctly (ghost/text links only, ≤ 6 px).
- Template/demo tells: double cursor (#9), 90° flip (#11), fan on every grid (#4), 0→2 tick (#13),
  stock-style light streaks (#2). Remove these five and nothing here looks borrowed.
- Competes with buying: the intro on product pages (#1). Nothing else: on every profile the price,
  Add to Cart, COD row, sticky bar, drawer and checkout are painted at first frame and never
  under a veil; `/cart` and `/checkout` load no motion at all.

## What is excellent — keep

1. Buy-path discipline. Verified in every capture: price, Add to Cart, WhatsApp, COD row, sticky
   bar, drawer, cart and checkout carry no motion class, no veil, no delay, on desktop, mobile
   and reduced. This is the hardest brief rule and it is met.
2. The quiz card (`50-quiz.css:13-30`): gold hairline frame + inner frame, raised surface, the
   700 ms deal on load, and the armed gold line that fills for the 600 ms undo window
   (`:64-75`) — the undo affordance is the smartest detail in the build.
3. The result reveal (`50-quiz.css:97-172`): tarot back with the SF sigil, turn, mist, sweep,
   notes, match tick, meters — one narrative in 2.7 s that never covers the price.
4. The story section: sticky column, no pins, two-layer 900 ms hue cross-fade, ±6° tilt, gold
   rule draw under the current tier, note stagger. Pacing is luxurious.
5. Hero typography and reveal: eyebrow / gold display / lead / CTA / trust hierarchy, h1 never
   hidden or split, one clip wipe + one sweep, CTA legible within 400 ms. Restraint done right.
6. Ease and duration vocabulary: one family (expo/power3 out), tokens in one config, CSS and JS
   reading the same numbers.
7. Mobile discipline: zero GSAP / GL / cursor / magnetism on phones, two plates, IO-only hue
   swap, and the scroll-timeline rail progress thumb (`20-cards.css:154-195`) is a graceful,
   zero-JS touch.
8. Page transitions: 160/200 ms native view transitions, desktop only, skipped for cart,
   checkout, forms and reloads — mist between pages without a single dead tap on 4G.
9. Reduced motion is instant, and every module has a lifecycle (context loss, frame kill, device
   flip, pagehide) — invisible to the jury, but it is why the site will still be fast in a year.

## Capture set

`scratchpad/jury/*.png` (this run) and `docs/shots/motion/*.png`. Scripts: `scratchpad/jury-shots.mjs`,
`scratchpad/jury-shots2.mjs`. No file under `site/` was changed by this review.
