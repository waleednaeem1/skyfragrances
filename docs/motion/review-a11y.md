# Motion layer — mobile UX + accessibility review (2026-09-28)

Scope: the 360 captures in `docs/shots/motion/`, the motion code (`assets/js/motion/*`, `assets/js/intro.js`,
`assets/css/motion/*`, the touched views/partials) and live probes against `php -S 127.0.0.1:8091`
(Playwright, Chromium 1208: 1440×900, 1024×640, Pixel-5 360×740 touch, `reducedMotion: 'reduce'`,
fresh contexts so the intro runs). Paths are relative to `site/`. Ranked most severe first.

## Findings

### 1. HIGH — The intro veil covers price, Add to Cart and the COD row on every non-home page (first visit)
- `app/views/layout.php:37` prints `intro.php` on every storefront page; `assets/js/intro.js:18-19` sets
  `html.sf-intro-full` (or `-calm`) on every page; `assets/css/site.css:5421-5434` makes the veil opaque
  `#0A0A0A`.
- Verified on `/product/azure-oud` at 360×740 in a fresh context: veil `opacity 1` at 100, 700 and
  1,300 ms, `is-leaving` only at 1,900 ms. The WhatsApp → product-page path (the most common entry,
  PLAN §8 #14) gets ~1.5–1.9 s of black over the price and the buy column.
- PLAN §2.2 / §4: home only. The brief: "price, Add to Cart, COD info visible immediately".
- Fix (not a redesign, two lines): print `data-page="home"` on `<html>` in `layout.php` when
  `$isHome`, and in `intro.js` `if (root.dataset.page !== 'home') { return; }` before line 18 (the
  script runs in `<head>`, so `document.body` is not available; the `<html>` attribute is). Keep
  `intro.php` printed only under the same condition so the lazy monogram is not fetched elsewhere.

### 2. HIGH — A tap to skip the intro lands on whatever is under the black veil
- `assets/css/site.css:5427` `.sf-intro { pointer-events: none }` while `intro.js:60` skips on any
  `window` `pointerdown`. The veil is opaque, so the shopper cannot see the target, but the same tap
  reaches it.
- Verified on `/` at 360×740: a tap at the hero CTA's centre 250 ms into the intro skipped the intro
  **and** navigated to `/shop`. Over the header it would open the cart drawer or the menu.
- PLAN §2.2: "a click or tap on the veil skips only and never reaches the CTA … pointer-through is
  allowed only once veil opacity < 0.2".
- Fix: `html.sf-intro-full .sf-intro, html.sf-intro-calm .sf-intro { pointer-events: auto }` and
  `.sf-intro.is-leaving { pointer-events: none }` in the intro block; keep the skip listener on the
  veil element rather than `window` (`el.addEventListener('pointerdown', skip)`), so a tap on the
  veil is consumed by the veil and never reaches the page.

### 3. HIGH — Reduced-motion users still get an opaque veil for ~0.9 s on every first page
- `assets/js/intro.js:14,18` maps `prefers-reduced-motion` to mode `calm`; `site.css:5546` keeps the
  calm veil for 1.15 s before its keyframe; `site.css:5343` shortens the keyframe to 0.01 ms but not
  the delay; JS `finish()` runs at ~820 ms.
- Verified at 1440×900 with `reducedMotion: 'reduce'` on `/product/azure-oud`: `html.sf-reduce
  sf-intro-calm`, veil `rgb(10,10,10)` at opacity 1 at 50 and 400 ms, opacity 0 at 900 ms, removed at
  1,300 ms. The price and Add to Cart are hit-testable through it (finding 2) but invisible.
- PLAN §2.2 and §7: Reduced → `sf-intro-*` is never set, the partial stays `display:none`.
- Fix: in `intro.js` return before adding any class when `reduced` (treat it like
  `cfg.enabled === false`); belt and braces in `assets/css/motion/00-core.css` reduced block:
  `html.sf-reduce .sf-intro { display: none }`.

### 4. MEDIUM — Quiz auto-advance is an unannounced change of context after selecting a radio
- `assets/js/motion/quiz.js:118-129` (`arm`) and `157-172` (`onChoiceClick`): 600 ms after a pointer
  click on an answer the card flips and focus is moved to the next `legend` (`finish`, 52-55). The
  only cue is the 2 px gold line under the chosen card (`50-quiz.css:63-76`); the live region only
  speaks after the move ("Question 3 of 5").
- Screen-reader users on touch (TalkBack double-tap dispatches `pointerup` + `click` on the label,
  so `recent` is true) are moved while the selection is still being announced; low-vision users
  with a mouse get no notice either. WCAG 3.2.2 (On Input).
- Verified at 1440×900: mouse click on an answer → `is-armed`, live region still "Question 2 of 5",
  focus on the radio; 1.4 s later the active step is `question-3` with focus on its legend.
- Fix (keeps the plan's undo window): at `arm()` write the notice into `.js-quiz-live` —
  "Answer saved. Next question in a moment; press any key to stay." — and add a sr-only hint on
  each `role="radiogroup"` (`aria-describedby` already points at `err-qN`; append a second id) saying
  "Choosing an answer moves to the next question." Keyboard selection stays unaffected (already
  never advances on `change`).

### 5. MEDIUM — Screen readers never hear "Question 1 of 5"
- `app/views/quiz.php:23` puts `aria-hidden="true"` on the whole `.progress` block, including the
  `Question N of 5 · 20%` label; the live region (`quiz.js:56-58`) only fills after a flip. With the
  one-card-at-a-time flow the first card gives no position, and Back/Next are the only landmarks.
- Fix: keep `aria-hidden` on `.progress__track` only, or prefix the legend with
  `<span class="u-sr-only">Question N of 5. </span>`; also seed `.js-quiz-live` with
  "Question 1 of 5" in `initSteps` so the count is in the accessibility tree from the start.

### 6. MEDIUM — Auto-playing motion longer than 5 s has no user pause control
- Mobile hero plate: `10-hero.css:56-63` runs `sf-plate-drift` 14 s × `--sf-plate-cycles` (6) = 84 s;
  desktop ribbons run continuously (`hero.js:146-183`); intro mist loops (`site.css:5453-5454`).
  Only the OS `prefers-reduced-motion` setting stops them (WCAG 2.2.2 Pause, Stop, Hide expects an
  on-page mechanism for decorative motion that runs beside content for more than 5 s).
- Fix: one "Pause animations" toggle in the footer that writes
  `localStorage.sfMotionFlags = {"hero":false,"fan":false,"story":false,"micro":false,"quiz":false,"transitions":false}`
  and adds `html.sf-reduce` (core.js already merges `sfMotionFlags` and every module checks
  `motion.reduced` / `allows()`); or cut `plate-cycles` to 2 in `config.js` so the drift ends inside
  ~30 s.

### 7. MEDIUM — A stuck `sf-intro-*` class leaves the hero eyebrow, subheading, CTA and trust line paused
- `intro.js:19` adds the mode class synchronously in `<head>`; only `finish()` (34-53) removes it, and
  `run()` (189-193) returns silently when `#sf-intro` is absent. `10-hero.css:103-121` keeps
  `.hero__enter` (opacity 0 keyframe start), `.hero__actions` (opacity .6) and `.hero__trust` in
  `animation-play-state: paused` while `html.motion--desktop.sf-intro-full`, and the h1 sweep paused.
- Today the partial is printed everywhere so it only bites if finding 1 is fixed by gating the
  partial alone, or if `intro.js` throws after line 19. The CSS failsafe (`site.css:5434`) hides the
  veil but never un-pauses the hero.
- Fix: in `run()` remove `sf-intro-full/calm/quick` when `!el`; in `10-hero.css` replace the paused
  state with an `animation-delay` equal to the intro length (`--sf-intro-length`, add to `config.js`
  `css`) so the beat completes with JS dead, and keep `.hero__actions` at opacity 1 rather than .6
  while paused (`sf-hero-settle` `from { opacity: .85 }`).

### 8. LOW — Custom cursor removes the native pointer over whole cards, no forced-colors guard
- `40-micro.css:88-93` sets `cursor: none` on `.product-card`, `.product-card a`,
  `.collection-card`, `.gallery__open`; the replacement is a 22 px ring with a 1 px border
  (`40-micro.css:115-123`). For low-vision and motor-impaired mouse users the ring is a weaker
  pointer than the arrow, and under Windows High Contrast the ring's `var(--accent)` border is
  overridden while `cursor: none` stays.
- Fix: draw the ring as an addition and drop `cursor: none` (the grow-on-hover still reads), or at
  minimum `@media (forced-colors: active) { html.sf-cursor-on * { cursor: auto } .sf-cursor { display: none } }`.

### 9. LOW — Magnetic pull applies to the quiz's Back/Next links
- `config.js:198` `micro.magnetic.targets` includes `.btn--text`; `never` excludes buttons in the
  drawer/modal/sticky bar but not `form a`, so the quiz Back/Next anchors (`quiz.php:50,55`) and the
  no-JS footer nav move up to 6 px under the pointer. PLAN §8 #9 keeps magnetism off controls a
  person is completing a task with.
- Fix: add `.js-quiz, form` to `micro.magnetic.never`.

### 10. LOW — Intro Skip control: not focusable, 3.9:1 contrast
- `app/partials/intro.php:12` `tabindex="-1"`; `site.css:5516-5531` colour
  `rgba(245,240,232,.45)` on `#0A0A0A` ≈ 3.9:1 at 0.68 rem uppercase. Any key skips (`intro.js:61`),
  so keyboard users are not trapped, but the only visible control is neither reachable nor readable.
- Fix: colour `rgba(245,240,232,.72)` (≈ 8:1), drop `tabindex="-1"`, give it `aria-label="Skip intro"`.

### 11. LOW — Focus ring can sit under a neighbouring card while a row is still fanned
- `20-cards.css:17-21` fans direct children with `translate3d(±40%)`; later siblings paint over
  earlier ones; the `:focus-within` lift exists only under `[data-cards-state="spread"]`
  (`20-cards.css:116-120`). Tabbing into the listing grid before it spreads (or during the 6 s
  failsafe) puts the ring partly behind the next card.
- Verified on `/` at 1440×900: the first collection card focused while `data-cards-state="live"`,
  rotated 8°, ring visible, and 400 ms later the browser's scroll had spread it — fine for the home
  mosaic (first child on top); the risk is the 3-/4-column rows on `/shop`.
- Fix: `html.motion--desktop [data-motion~="cards"] > :focus-within { z-index: 2; }` for every state.

### 12. LOW (pre-existing, on motion-touched cards) — Rail clips the card focus ring on mobile
- `site.css:494-505` `.rail { overflow-x: auto }` with no block padding; `.collection-card:focus-visible`
  has `outline-offset: 4px` (`site.css:1634`). Verified at 360×740: card and rail share top/bottom
  (1023/1398 px), so the top and bottom edges of the ring are clipped.
- Fix: `.rail { padding-block: 6px; margin-block: -6px; }`.

### 13. LOW (pre-existing, now on the quiz's tap path) — FAB over answer cards, sticky size button 28 px
- WhatsApp FAB is 52 px fixed at (292, 672) on 360×740 and overlaps the bottom-right answer card of
  the active quiz step (`elementsFromPoint` under the FAB: `form__section.is-active`); with the quiz
  now advancing on a tap, a mis-hit here opens WhatsApp instead of answering. Sticky-bar size button
  is 106×28 px (`product.php:231`), below the 44 px target the rest of the bar uses.
- Fix: add `body.quiz .whatsapp-fab { display: none }` (the quiz page has no WhatsApp order path), or
  give `.quiz-deck` `padding-block-end: calc(var(--fab-size) + var(--sp-4))`; `.sticky-bar__size { min-height: var(--tap) }`.

## What holds (keep)

- The split headline is never split: `h1.hero__title` is one text node, never `opacity: 0`, only a
  `clip-path` wipe on desktop and static on mobile; screen readers get the full heading at once.
- Nothing sellable or readable lives in a canvas: ribbons, plates, intro particles, the story glow,
  the quiz card back ("SF" sigil) and the result mist are all inside `aria-hidden` containers with
  `pointer-events: none`.
- Ticking numbers are `aria-hidden` twins with the real text kept sr-only (`micro.js:80-118`,
  `quiz-result.php:43`); prices never tick (`config.js:202` `tick.never`).
- Quiz keyboard path verified at 1440×900: arrow keys select without advancing, Tab reaches Next,
  Enter flips, focus lands on the new `legend` with a visible gold ring (`:focus-visible` true),
  the live region reads "Question 2 of 5", no inline styles are left on the fieldsets, and an
  unanswered Next falls through to `forms.js` validation. Mobile answer cards are 128×208 / 128×272,
  Next is 91×48.
- Fanned cards keep DOM order and tab order; Tab into a card scrolls it into view and ScrollTrigger
  spreads it with the ring still visible; `cursor: none` never touches keyboard focus.
- Sticky bar vs pinned story: no overlap — the bar is `display: none` ≥ 1024 (`site.css:4579`) where
  the figure is sticky, and the figure is `display: none` < 1024 (`30-story.css:36`).
- Contrast of gold on the new backgrounds (pixel-sampled from live pages): `--gold-400` on the
  brightest hero-glow point beside the eyebrow ≈ 7.7:1; on the story glow beside "Top notes" ≈ 8.3:1;
  hero trust line and quiz legend (`ivory-400`) ≥ 5.4:1.
- Reduced motion, apart from the intro (finding 3): `html.sf-reduce`, no module loads, no cursor,
  burst, flight or veil, plates absent, h1 unclipped, actions/trust at opacity 1, story hue static,
  quiz stacked by `forms.js`, View Transitions excluded by the media query.
- After the intro, nothing motion-owned can cover the buy path: burst, flight clone, cursor and veil
  are `pointer-events: none`; the result-page mist is confined to `.split__media`; `.split__aside`,
  `.sticky-bar` and `#add-to-cart-form` carry no motion class.

## Probe scripts

`scratchpad/a11y-check.mjs` and `scratchpad/focus-clip.mjs` (session scratchpad; results in
`a11y-out.json`). The 8091 server was reused, not restarted.
