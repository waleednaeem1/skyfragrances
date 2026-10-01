# Direction 1: Porcelain and Gilt

## Concept

The light Sky Fragrances is a gallery, not a boutique: cool-warm porcelain grounds, true ink type and hairline rules, with the air of a Byredo or Aesop room. Gilt appears the way foil does on a box, in the monogram, the eyebrow, the numerals, one hairline under the primary button and nowhere else, so each touch of gold still means something. It stays a sibling of the dark site: same Cormorant and Jost, same monogram, same uppercase tracking, and one ink "lacquer lid" band per page that carries the dark theme's gold into the light one.

Prototype: `prototype.css` (scoped to `html[data-theme="light"]`, 444 lines, no comments). Final screenshots are in the scratchpad at `theme/directions/1/shots/`.

## Palette and contrast (WCAG 2.x)

| Token | Value | Role | Ratio on porcelain #F7F5F0 | Other grounds |
|---|---|---|---|---|
| --surface | #F7F5F0 porcelain | page ground | n/a | n/a |
| --surface-raised | #FCFBF8 | drawer, voice cards, summary | n/a | n/a |
| --surface-overlay | #FFFFFF | inputs, image mats, chips | n/a | n/a |
| --surface-sunken | #EFECE5 stone | footer | n/a | n/a |
| --surface-hover | #E8E4DC | hover rows | n/a | n/a |
| --text | #121110 true ink | body, headings, price | 17.31 | white 18.86, stone 15.99 |
| --text-muted | #5F5A52 | secondary copy, meta | 6.28 | white 6.84, stone 5.80 |
| --text-disabled | #9A948A | disabled only (exempt) | 2.76 | n/a |
| --text-inverse | #F7F5F0 | label on ink buttons | 17.31 on ink | n/a |
| --accent | #6E5027 gilt-700 | eyebrows, step numerals, sale price, links | 6.79 | white 7.39, stone 6.27 |
| --accent-hover | #54391A gilt-800 | hover | 9.75 | n/a |
| --accent-active | #121110 | pressed | 17.31 | n/a |
| gilt-600 | #8A6538 | stars, trust icons, gilt underlines | 4.82 | white 5.25 |
| --border | rgba(18,17,16,.11) | decorative rules only | about 1.3 (decorative) | n/a |
| --rule-control | #8A847A stone-500 | input, qty, chip, ghost borders | 3.40 | white 3.71 |
| --border-accent | rgba(110,80,39,.42) | quiet gilt edge | decorative | n/a |
| --focus-ring | #6E5027 | 1px outline, 4px offset | 6.79 | n/a |
| --price / --price-was | #121110 / #5F5A52 | price | 17.31 / 6.28 | n/a |
| --success / --danger | #1F6B3E / #A32B22 | status | 5.97 / 6.59 | n/a |
| announcement | #D9D2C5 on #121110 | top bar | 12.55 | n/a |
| night band text / muted / gold | #F7F5F0 / #A39D93 / #D4B084 on #121110 | .t-light bands | 17.31 / 7.01 / 9.29 | n/a |
| hero gilt gradient, lightest stop | #A87F4E | large text only (.text-gold-grad) | 3.32 | n/a |

Body text and prices clear 4.5:1 on every ground they sit on, and control borders clear 3:1. Decorative hairlines are deliberately below 3:1: they separate, they are not controls.

## Component decisions

- **Announcement bar:** true ink with warm ivory text. It anchors the top edge, reads as the one dark sibling cue, and keeps the Cash on Delivery promise the most prominent sentence on every page.
- **Header:** porcelain at 92% with a 14px backdrop blur, a faint rule, and a soft 30px shadow once solid. The transparent state's dark `drop-shadow` is removed because it looks like dirt on light. Nav is ink and hover is gilt. The cart count is an ink disc.
- **Logo:** the gold raster monogram is deepened with `filter: brightness(.52) contrast(1.5) saturate(1.2)` in the header, where it is small and needs weight, and `brightness(.7)` in the hero, where it can stay luminous. It reads as antique gilt foil. The footer's `lockup-on-black` would render as a black square, so the prototype swaps it to `lockup-transparent-400.png` through `content:url()`.
- **Hero:** paper-white to porcelain vertical wash, with one small warm halo (a 5.5 x 4 monogram-sized radial, peak alpha .26) behind the gilt monogram. The title is set in **true ink**, not gold: at display size, gilt on white looks like brass, while ink looks like a museum wall label. The gilt lives in the monogram and eyebrow. The trust line gets a 1px rule above it, so COD reads as a signed promise.
- **Section rhythm:** full-bleed hairlines between sections. `.section--glow` becomes a white bloom (light through porcelain) instead of the gold radial, because the radial banded visibly on light.
- **The ivory `.t-light` bands invert into ink night bands.** "Why choose us" becomes a lacquered lid on #121110, with gold icons and a fading champagne hairline at the top and bottom edges. That gives one dark beat per page, which carries the rhythm and the brand memory. The night token set is shared with `.collection-card` so its copy over imagery stays ivory.
- **Product cards and imagery:** every product image is mounted in a white passe-partout mat (`--mat`, clamp 6-14px, 3.2%) with an inset hairline, so the dark sample renders read as framed prints on a gallery wall rather than holes in the page. The PDP gallery gets a 12-20px mat. Hover lifts the mat with a soft warm shadow. Thumbnails keep full opacity (a 0.6 opacity goes milky on light), are desaturated when inactive, and get an ink border when active.
- **Buttons:** primary is true ink with a porcelain label and a 1px gilt inset underline (foil edge); on hover the underline brightens and the button casts a warm shadow. Ghost uses a 3.4:1 stone border and goes ink on hover. Text buttons are ink with a gilt underline draw. Inside night bands the primary returns to the original gold gradient.
- **Price:** ink in Cormorant, as the brand already does on ivory. Sale price is gilt-700 with the struck price muted. The `.summary__value--free` hard-coded gold-300 is remapped to accent.
- **Forms:** white fields with a 3.4:1 stone border that turns ink on hover and focus, plus a 3px gilt halo on focus. Size chips and payment choices share one selected language: white card, ink border and a gilt inset (bottom edge for chips, left edge for payment choices). The taupe `accent-quiet` fill is avoided because it reads as grey.
- **Cart drawer:** raised porcelain sheet, white footer, ink Checkout. The overlay is a frosted porcelain veil (`rgba(239,236,229,.62)` with 8px blur) instead of a dark scrim, so the page recedes into mist rather than going grey.
- **Badges:** Sale/Save are ink with champagne text (15.29:1); New is a gilt outline on white; Sold out is muted on white.
- **Stars:** gilt-600 at 4.82:1, empty stars ink at 14% alpha.
- **Focus:** a 1px gilt-700 outline with a 4px offset. **Selection:** ink with champagne text. In night bands the selection is champagne with ink text.
- **WhatsApp FAB:** a white disc with an ink glyph and a warm shadow.
- **Footer:** stone #EFECE5 with an ink heading, gilt gradient hairline on top and a gilt lockup.

## Motion and brand assets on light

- **WebGL hero ribbons (`motion/ribbons-gl.js`):** the shader blends additively (`gl.blendFunc(ONE, ONE)`), which adds light and works only on black. On porcelain it would blow out to white. On light, switch to premultiplied alpha blending (`ONE, ONE_MINUS_SRC_ALPHA`), draw the ribbons as a thin smoke of `#8A6538`/`#C29C6E` at 20-30% peak alpha, and composite the canvas with `mix-blend-mode: multiply`. The result is ink-and-gilt silk drifting through milk rather than glowing light. Pass the colours through `config.js` (`hero.ribbons.colors`) per theme instead of hard-coding them.
- **Hero plates (`ribbons-a/b-*.webp`):** these are light-on-black renders. On light, render a second pair as dark-on-transparent (or invert and warm them), and show them with `mix-blend-mode: multiply` at about .5 opacity. The prototype sets that blend, but the current plates need the re-render to look right.
- **Intro loader (`intro.js`, `.sf-intro`):** a porcelain ground, the deepened gilt monogram, and the mist as a pale champagne bloom. The canvas `fillStyle = 'rgb(214, 180, 136)'` particle sparks should become `rgb(138, 101, 56)` with multiply compositing, and the gold sweep on the mark (critical.css `rgba(255,241,205,.8)`) should become a white sheen, which reads as light catching foil. `--sf-mist` in `60-transitions.css` hard-codes `#0A0A0A` and must become `var(--surface)`, or every page transition flashes black.
- **Custom cursor:** an ink 1px ring whose fill on grow is gilt-500 at the existing 0.3 opacity. The prototype sets this.
- **Logo:** ship real assets instead of CSS filters: `monogram-gilt-on-light.webp` (foil deepened to #6E5027-#A87F4E) and a `lockup-gilt-on-light` for the footer. Choose them in PHP with `<picture>` keyed on the theme, or with a `data-theme`-scoped `content:`. The filters are a prototype stand-in and cost a paint.

## What I would NOT change

- Typography: Cormorant Garamond display at weight 300, Jost body, all tracking values and the uppercase label system.
- Layout, spacing scale, grid, radii (still 0), section heights and the header structure.
- The monogram shape and lockup, and the logo centred in the header.
- The dark theme itself: every rule in the prototype is under `html[data-theme="light"]`, and the dark `:root` is untouched.
- Collection cards stay full-bleed imagery with ivory type on an ink scrim, which is the one place the night mood is allowed to be large.
- The gold gradient primary button inside night bands, so it keeps the brand's signature object.

## Hard-coded colours that bleed through (need tokenising before shipping)

- `critical.css` `.site-header.is-transparent ...` `filter: drop-shadow(0 1px 8px rgba(0,0,0,.6))`.
- `critical.css` `.site-header.is-solid` and `.btn--primary` `box-shadow ... rgba(0,0,0,.6)`, and the `.btn--primary:hover` `rgba(0,0,0,.55)`.
- `critical.css` `.btn--ghost:hover` `rgba(212,176,132,.06)`; `.qty__btn:active` `rgba(212,176,132,.14)`; `.size-chip__input:checked + .size-chip__card` `rgba(212,176,132,.10)`; `.size-chip__card:hover` `rgba(212,176,132,.5)`.
- `critical.css` `.badge--sold-out` `rgba(10,10,10,.82)`; `.filter-bar` and `.sticky-bar` `rgba(10,10,10,.94)`; `.gallery__zoom-hint` `rgba(10,10,10,.6)`.
- `critical.css` `.stars__star--empty/--partial` `rgba(245,240,232,.16)` (invisible on light); `.stock-line::before` `var(--gold-300)`; `.placeholder__mark` `rgba(212,176,132,.28)`; `.text-gold-grad` fallback `var(--gold-400)`.
- `critical.css` `.sf-intro` `#0A0A0A`, its mist radials and sheen (`rgba(212,176,132,.16)`, `rgba(255,232,190,.12)`, `rgba(255,241,205,.8)`), `.sf-intro__tag` `rgba(212,176,132,.85)`, `.sf-intro__skip` `rgba(245,240,232,.72)`; `.whatsapp-fab:hover` `#FFFFFF`.
- `site.css` `.collection-card__scrim` and its hover (`rgba(10,10,10,.88/.94)`); these are fine on imagery but should be `--scrim-bottom`.
- `site.css` `.summary__value--free` `var(--gold-300)` (1.9:1 on porcelain; the worst offender); `.insta__veil` `rgba(10,10,10,.25)`; `.meter__seg` and `.review-summary__track` `rgba(245,240,232,.10)`; `.star-input__label` `rgba(245,240,232,.16)`; `.skeleton::after` ivory shimmer; `.pagination__link:hover` `rgba(212,176,132,.10)`; `.modal--lightbox .modal__close` `rgba(10,10,10,.6)`; `.callout--danger/--success` fills.
- `site.css` `.t-light ...` block (lines ~2570-2630): it assumes an ivory band (gold-800 trust icons, white inputs, gold-400 sale badge). Once `.t-light` becomes a night band on the light page, each of these needs a light-theme counter-rule. The prototype adds them.
- `site.css` `.site-footer__logo` uses `lockup-on-black-800.webp`, a black square on light (asset, not CSS).
- `motion/10-hero.css` `.hero__glow::before` gold radial (bands visibly on light at its full 13x size) and the title sheen `rgba(255,241,205,.92)`; `motion/20-cards.css` card sheen `rgba(242,230,210,.5)` and `rgba(212,176,132,.18)`; `motion/40-micro.css` `rgba(0,0,0,.45)` shadow; `motion/50-quiz.css` gold borders, radials and a `rgba(0,0,0,.9)` shadow; `motion/60-transitions.css` `--sf-mist` `#0A0A0A`.
- JS: `intro.js` canvas `rgb(214,180,136)`; `motion/config.js` ribbon colours `#C29C6E` / `#E0A45C` and story note palettes (tuned for glow on black); `ribbons-gl.js` additive blend.

## Implementation notes

- The site's CSS is in `@layer`s, and the injected prototype is unlayered, which is why its short selectors win. When shipping, put the token block in `@layer tokens` as `:root[data-theme="light"]` and the component overrides in `@layer overrides`.
- Set `data-theme` on `<html>` server-side from the admin setting, so the first paint is never dark-then-light, and give `critical.css` the light token block because it paints first.
- Aside: the cart drawer shows no product name on the line in both themes (`.cart-line__name` renders empty). This is a separate, pre-existing bug.
