# Direction 3: Atelier Parchment

## Concept
The light theme is the perfumer's atelier by day: warm parchment and stone paper with a faint laid-paper grain, sepia ink for type, and deep bronze where the dark theme uses champagne gold. The dark theme does not disappear, it becomes the ink of the light one: the announcement strip, the primary buttons, the "Promise" band and the footer are pieces of the night set onto the page like a stamped label on a paper box. Every dark product photograph is mounted on a paper passe-partout, so the sample imagery reads as a specimen plate in an archive rather than a black hole in a light page.

Prototype: `prototype.css` (all rules under `html[data-theme="light"]`, unlayered so it beats the `@layer` stack; no comments per house rule).

## Palette and contrast (WCAG 2.x, computed)

| Token | Value | Role | Ratio (on) |
|---|---|---|---|
| `--surface` | `#F3ECE0` parchment | page ground | - |
| `--surface-raised` | `#F8F3EA` paper | cards, mounts, voice, summary | - |
| `--surface-overlay` | `#FBF7F0` vellum | drawer, modal, inputs | - |
| `--surface-sunken` | `#E9E0D0` warm stone | quiz band, disabled, sunken | - |
| `--surface-hover` | `#E2D7C4` | hover wash | - |
| `--text` | `#1E1812` sepia ink | body, names, prices | 14.97 parchment, 13.43 stone, 15.90 paper |
| `--text-muted` | `#5E5345` walnut | meta, subs, helper | 6.39 parchment, 5.73 stone, 6.79 paper |
| `--text-disabled` | `#8A7F70` | disabled only | 3.34 parchment (exempt) |
| `--text-inverse` | `#F3ECE0` | text on ink | 14.97 on `#1E1812` |
| `--accent` | `#7A5530` bronze | eyebrows, links, stars, sale price | 5.64 parchment, 5.06 stone, 6.20 vellum |
| `--accent-hover` | `#5E3F20` | hover | 8.10 (parchment on it) |
| `--accent-active` | `#4A311A` | pressed | - |
| `--border-accent` | `#9A7442` bronze line | ghost buttons, gender tiles, rules | 3.61 parchment, 3.84 paper |
| `--border-control` (new) | `#8F826E` | inputs, checkboxes, size chips, qty | 3.20 parchment, 3.52 vellum |
| `--border` | `rgba(30,24,18,.16)` | decorative dividers only | decorative |
| `--border-faint` | `rgba(30,24,18,.08)` | section rules | decorative |
| `--price` | `#1E1812` | price | 14.97 |
| `--price-was` | `#6B5E4E` | struck price | 5.36 |
| `--success` | `#2F6B45` moss | stock, "Free" | 5.40 parchment, 5.94 vellum |
| `--danger` | `#9E2F24` sealing-wax | errors | 6.20 |
| `--focus-ring` | `#7A5530` | 2px outline, 3px offset | 5.64 |
| primary button | `#1E1812` fill, `#E7D2AE` label | Add to Cart, Checkout | 11.91 |
| sale badge | `#D4B084` fill, `#1E1812` label | on dark imagery | 8.66 |
| hero title gradient | `#3E2914` to `#9A7442` | display text | lightest stop 3.61 (large text) |
| placeholder | `#766A58` on vellum | input hints | 4.95 |
| ink bands | `#17120D` ground, `#F3ECE0` text, `#B3A68F` muted, `#D4B084` gold | Promise band, announcement | 15.85 / 7.77 / 9.16 |
| announcement | `#D9C8A8` on `#17120D` | strip | 11.32 |
| footer | `#0A0A0A` (the dark theme's own ink) | footer | dark theme values |

Supporting tokens added: `--paper-grain` (inline SVG feTurbulence tile, alpha about 0.3, tinted bronze), `--grad-lamp` (radial, brighter vellum centre over parchment, darker stone edge), `--shadow-paper` (inset white top edge plus a long soft sepia drop), `--night`, `--night-raised`. Gold gradients (`--grad-gold`, `-rule`, `-border`, `-sheen`, `-radial`) are redefined in bronze so every existing consumer turns bronze for free; `--grad-gold-button` becomes an ink gradient.

## Component decisions
- **Announcement bar:** an ink strip (`#17120D`, champagne text `#D9C8A8`, gold hairline below). The first thing a customer sees is the dark house, then the page opens into daylight. It keeps "Cash on Delivery nationwide" in the highest-contrast place on the page.
- **Header:** parchment at 92% with a 14px backdrop blur, a bronze hairline underneath (`rgba(122,85,48,.22)`), and a long sepia drop shadow when solid. Nav in sepia ink; hover and current page in bronze with the existing underline. Cart count is an ink pill with a champagne numeral.
- **Logo:** the transparent gold monogram is darkened in CSS to bronze (`brightness(.52) saturate(1.3) contrast(1.25)` in the header, `.62` plus a soft sepia drop shadow in the hero). The pale highlights of the gold PNG vanish on parchment otherwise. Ship a real bronze asset (see below).
- **Hero:** lamp-lit paper: `--grad-lamp` under the grain, a brighter vellum pool behind the monogram and title, stone at the edges. The title keeps `text-gold-grad`, now a bronze gradient (dark umber edges, `#9A7442` highlight). The desktop sweep highlight becomes a warm champagne glint. The subline is walnut. The Cash on Delivery trust line gets bronze hairlines on both sides on desktop (a seal), and one short rule above it on mobile where the text wraps.
- **Section rhythm:** parchment is the default page. The ivory `.t-light` band ("Why choose us") inverts to an **ink night band** with a gold top glow and gold hairlines at top and bottom, so the light page has one deep breath in the middle, exactly where the four promises (including COD) sit. The quiz band (`section--glow`) becomes warm stone with a vellum pool, one step darker than the page. The footer is the dark theme's own `#0A0A0A`, like a leather back cover.
- **Product cards:** transparent card on parchment; the 4:5 media becomes a **paper mount**: paper ground, a bronze inset hairline, `--shadow-paper`, and the image inset 7px (5px on phones). The dark sample photography stays exactly as shot and reads as framed specimens. Name in ink display, meta in walnut, price in ink, sale price in bronze, struck price in `#6B5E4E`.
- **Collection cards with images:** kept as dark plates (dark token scope inside them, so text stays ivory over the scrim) with a thin bronze outline and paper shadow. The gender tiles (no image) become paper tiles with a bronze line border and a faint bronze radial.
- **Buttons:** primary = sepia-ink block, champagne label `#E7D2AE`, a 1px inner gold hairline and a short sepia shadow; hover turns bronze-deep with a vellum label; the existing gold sheen `::before` still sweeps across. Inside the ink bands the primary reverts to the original gold gradient with ink label, so a button always contrasts with its ground. Ghost = transparent with `#9A7442` border and ink label; hover gets `--accent-quiet`. Text buttons = ink label, bronze underline on hover. WhatsApp ghost keeps the bronze border; hover tints moss.
- **Forms:** vellum inputs, `#8F826E` border (3.2:1), walnut on hover, bronze border plus an 18% bronze halo on focus. Checked checkboxes are ink with a champagne tick. The COD payment choice keeps the existing selected-card treatment and now reads as stone with a bronze border.
- **Size chips:** vellum, control border; selected = ink double border (1px border plus 1px inset) with the bronze underline bar. Clearer than a tint on paper.
- **Cart drawer:** clean vellum sheet (no grain: the drawer is a fresh sheet of letter paper), bronze hairline dividers, paper footer, an ink Checkout button. Overlay = `rgba(30,24,18,.46)` with a 3px blur, so the page reads as paper behind glass rather than a black curtain.
- **Badges:** Sale = champagne gold with an ink label, because badges sit on the dark imagery, not on parchment. New = vellum label with a bronze border. Sold out = stone with a walnut label. Save = ink with a champagne label.
- **Stars:** bronze; empty stars `rgba(30,24,18,.16)`.
- **Focus and selection:** 2px bronze outline at 3px offset everywhere; selection is bronze with vellum text.
- **Sticky add bar and mobile filter bar:** paper at 95% with blur, bronze top hairline, upward sepia shadow.
- **Gallery:** the main image and the mobile slides get the same paper mount; thumbnails stay fully opaque (at 0.6 opacity the dark thumbs turn a dirty grey on parchment) with a bronze-hairline mount; the active thumb gets an ink frame. Dots are ink.

## Motion and brand pieces on light
- **WebGL hero ribbons:** they blend additively (`gl.blendFunc(ONE, ONE)`), which adds light and disappears on a light ground. On light, `ribbons-gl.js` should switch to premultiplied normal blending (`ONE, ONE_MINUS_SRC_ALPHA`), pass bronze uniforms (`uGold` about `#8A6538`, `uAmber` about `#5E3F20`, lower `uGain`), and the canvas gets `mix-blend-mode: multiply` (already in the prototype). The ribbons become ink strokes drawn across paper instead of light in the dark.
- **Hero plates** (`ribbons-a/b-*.webp`, gold on black, no alpha): the prototype applies `filter: invert(1) hue-rotate(180deg) saturate(.8) contrast(1.1)` with `mix-blend-mode: multiply`. Black becomes white and drops out, and gold becomes a sepia wash. For production, bake two light plates (sepia ribbons on transparent) so the result does not depend on a filter chain on mobile GPUs. The static `hero__glow::before` becomes a 20% bronze halo in multiply.
- **Intro loader:** keep the choreography, change the ground. `.sf-intro` and the `--sf-mist` in `60-transitions.css` are hard-coded `#0A0A0A`. On light, use parchment with the lamp radial, a bronze monogram, and a thin bronze sweep instead of the white-gold glint (`rgba(255,241,205,.8)`). The page transition overlay uses the same parchment mist.
- **Custom cursor:** the ring and fill already use `--accent`, so they turn bronze. Add a vellum 1px outer ring so it still reads over the dark product plates.
- **Logo:** commission bronze exports of `monogram-transparent-*` and `lockup-transparent-*` (a deeper gradient: `#4E331A` to `#9A7442` to `#5E3F20`) and swap them via `<picture>` or a `data-theme` switch in `header.php` and the hero. The CSS filter works but slightly flattens the metallic gradient. The footer stays ink, so `lockup-on-black-800.webp` still works; in the prototype `mix-blend-mode: lighten` removes its visible black square.

## What I would NOT change
- Typography: Cormorant Garamond display plus Jost body, all sizes, tracking, uppercase eyebrows and button labels. The hierarchy already works on paper.
- Layout, grid, spacing tokens, section padding, the square (radius 0) geometry, the hairline language.
- The product photography itself. Mount it; do not relight it or lighten it with filters.
- The dark theme: nothing under `:root` changes; every light rule lives under `html[data-theme="light"]`.
- The motion timings and easings, the reveal system, the sticky bar behaviour.
- The WhatsApp green on hover (a brand signal customers recognise).

## Hard-coded colours seen bleeding through (to tokenise before shipping)
From the stylesheets, confirmed or predicted in the prototype runs:
- `critical.css`: `.badge--sold-out` `rgba(10,10,10,.82)`; `.btn--primary` shadow `rgba(0,0,0,.6)` and `:active` `var(--gold-600)`; `.btn:hover` glow `rgba(0,0,0,.55)`; `.btn--ghost:hover` `rgba(212,176,132,.06)`; `.filter-bar` `rgba(10,10,10,.94)` (it showed as a black band with invisible text on mobile /shop until overridden); `.gallery__zoom-hint` `rgba(10,10,10,.6)`; `.placeholder__mark` `rgba(212,176,132,.28)`; `.qty__btn:active` and `.size-chip` checked `rgba(212,176,132,.10/.14)`, `.size-chip__card:hover` `rgba(212,176,132,.5)`; `.stars__star--empty/partial` `rgba(245,240,232,.16)`; `.sticky-bar` `rgba(10,10,10,.94)`; `.site-header.is-solid` shadow `rgba(0,0,0,.6)` and the `.is-transparent` black drop-shadow filter; `.whatsapp-fab:hover` `#FFFFFF`; `.sf-intro` `#0A0A0A` plus its mist and glint gradients and `rgba(245,240,232,.72)` text; `.field__input` border `var(--ink-500)`; `.field__input--select option` `var(--ink-700)`; `.breadcrumb__item::after` `var(--ink-500)`; `.gallery__dot::before` `var(--ink-500)`; `.stock-line::before` `var(--gold-300)` (a pale dot next to "In stock"); `.text-gold-grad` fallback `var(--gold-400)`.
- `site.css`: `.collection-card__scrim` and its hover `rgba(10,10,10,…)` (fine, the plate stays dark); `.insta__veil` `rgba(10,10,10,.25)`; `.meter__seg` and `.review-summary__track` fills `rgba(245,240,232,.10)`; `.star-input__label` `rgba(245,240,232,.16)`; the `.skeleton::after` shimmer (`rgba(245,240,232,.06)`, invisible on paper); the lightbox close `rgba(10,10,10,.6)`; `.summary__value--free` `var(--gold-300)` ("Free" at about 1.7:1 on paper, visible on /checkout); `.drawer__body` scrollbar `var(--ink-500)`; `.nav-mobile__input` border `var(--ink-500)`; `.pagination__gap` `var(--ink-400)`; `.timeline__dot::after` `var(--ink-500)`; the error and success tints `rgba(229,115,107,…)` and `rgba(111,191,139,…)` (they need light-tuned twins); the whole legacy `.t-light` block (lines 2570 to 2628) hard-codes `#FFFFFF`, `--gold-400/700/800` and `--ivory-*` and would fight the inverted night band if left in.
- `motion/*.css`: `10-hero.css` glow radial `rgba(215,164,102,.5)` and the title sweep `rgba(255,241,205,.92)`; `20-cards.css` sheen `rgba(242,230,210,…)` and the rail progress `--gold-600/300`; `40-micro.css` cursor shadow `rgba(0,0,0,.45)`; `50-quiz.css` about a dozen gold and ink literals, including `rgba(10,10,10,0)` gradient ends and the `--gold-400` sigil; `60-transitions.css` `--sf-mist` ending in `#0A0A0A`.
- JS: `ribbons-gl.js` additive blend and gold uniforms; `hero.js` plate compositing assumes a black ground.
- Assets: `lockup-on-black-800.webp` (footer; fine only while the footer stays ink), the gold `monogram-transparent-*` PNG/WebP (too pale on parchment without a bronze variant).

## Risks and notes
- The paper grain is one 220px SVG tile, rasterised once. It is cheap, but check it on low-end Android. Drop it below `(prefers-reduced-data)` or on `motion--low` if needed.
- `mix-blend-mode` on the hero plates and canvas creates a compositing layer. That is fine on the hero only.
- Screenshots in the scratchpad `shots/` folder were taken with `reducedMotion: 'reduce'`, so ribbons and plates were not exercised. The plate filter is a recommendation that I did not verify.
