# Direction 2: Dawn Sky

## Concept
The dark theme is the night sky, so the light theme is the moment the sky turns: warm ivory ground, a pearl-to-peach haze at the top of every page, and a low sun of champagne light behind the monogram. Gold stops being a glow and becomes bronze leaf catching first light, while ink carries the type, so the site reads as the same house with the curtains opened. The night never disappears. It returns as ink bands and as the footer, so each page runs from daybreak at the top to dusk just above the footer and ends in night.

Prototype: `prototype.css` (all rules scoped to `html[data-theme="light"]`, no comments). Final screenshots: scratchpad `theme/directions/2/shots/`.

## Palette (with contrast ratios)
| Token | Value | Checked against | Ratio |
|---|---|---|---|
| --surface | #F7F2EA warm ivory | ground | - |
| --surface-raised | #FBF8F3 linen | - | - |
| --surface-overlay | #FFFDF9 pearl (drawer, summary, fields) | - | - |
| --surface-sunken | #EFE8DC sand | - | - |
| --surface-hover | #E6DDCE stone | - | - |
| --text | #14120F ink | surface / overlay / sunken | 16.78 / 18.40 / 15.36 |
| --text-muted | #5C554B slate | surface / overlay / sunken | 6.60 / 7.24 / 6.04 |
| --text-disabled | #8F877B | surface | 3.18 (disabled only) |
| --text-inverse | #FFFDF9 (text on the accent) | on #82603A | 5.61 |
| --accent (eyebrows, links, sale) | #82603A dawn gold | surface / sunken / peach haze / overlay | 5.12 / 4.68 / 4.70 / 5.61 |
| --accent-hover / --focus-ring | #6E5028 bronze | surface | 6.63 |
| --price (totals, drawer) | #6E5028 bronze | surface / overlay | 6.63 / 7.28 |
| .price__now (cards, PDP) | ink via --text | surface | 16.78 |
| --price-was | #5C554B | surface | 6.60 |
| --border | rgba(20,18,15,.14) hairline | decorative | - |
| --border-accent | rgba(138,101,56,.55) | decorative | - |
| --border-field (new) | #8F8474 | overlay / surface | 3.61 / 3.29 |
| stars | #A87F4E | surface / overlay | 3.24 / 3.56 |
| primary button label | ink on gradient #B38A58 to #E2C89E | darkest / lightest stop | 5.96 / 11.56 |
| badge sale/save | #E7D2AE on ink | - | 12.67 |
| badge new | #6E5028 on pearl | - | 7.28 |
| --success / --danger | #1F6B3E / #A32B22 | surface | 5.83 / 6.44 |
| hero subline | #4E483F on peach haze #F6E7D6 | - | 7.46 |
| hero title gradient | #5E4422 to #A27A4A | lightest stop on sun / on peach | 3.78 / 3.20 (large text) |
| announcement | #D9CDB8 on #14120F | - | 11.91 |
| night band text / muted / accent | #F5F0E8 / #A8A195 / #D4B084 on #12100E | - | 16.73 / 7.41 / 9.35 |
| night band field border | #6B6358 on #0D0C0A | - | 3.31 |

The atmosphere tokens are all new: `--grad-dawn` (#EDF0F1 pearl-blue zenith, #F5F1EE, #FAF6EF, #F6E7D6 peach horizon, ivory), `--grad-dawn-sun` (a radial of #FFFCF5 fading to champagne), `--grad-horizon` (the same sky, compressed for tiles and bands), `--shadow-card`, `--shadow-button` and `--field-bg`. `--grad-gold` is redrawn with no stop lighter than #A27A4A, because the dark theme's #F2E6D2 highlight disappears on ivory.

## Component decisions
- **Announcement bar:** ink #14120F with champagne text. It is a thin strip of the night that frames the sunrise below it and pairs with the ink footer.
- **Header:** linen at 86% with a 14px backdrop blur, ink links, a 1px gold hairline instead of a shadow, and the active link in dawn gold. The transparent state loses the dark drop-shadow filter. The cart count is an ink disc.
- **Hero:** the dawn sky gradient with a low sun radial behind the monogram, fading to ivory over the bottom 28% so the hero joins the page with no visible edge. The title keeps the gold gradient clip, deepened to bronze. The primary CTA is the gold bar with an ink label and a soft bronze under-shadow, which gives the page its only saturated moment.
- **Page tops:** every page's `.site-main` gets the same haze (pearl-blue, then linen, then peach, fading out by 28rem). Shop, FAQ and checkout get daybreak too, so the theme is more than a hero trick.
- **Section rhythm:** the gold-rule divider replaces the grey border on `.section--divided`. `.section--glow` gets a sun plus horizon. **The ivory `.t-light` bands turn into ink night bands** (#12100E with a faint gold radial and gold hairlines top and bottom), using the full dark token set, so "Why choose us" becomes the night passage in the middle of the day. The last section of every page gets a dusk wash (peach deepening over its last 26rem) that leads into the night footer.
- **Footer:** #0A0A0A, matching the lockup-on-black asset, with dark tokens, gold column heads and dark fields.
- **Product cards:** transparent card on ivory. The media is framed like a print, with a 1px inset bronze outline at 18% and a long soft shadow (`--shadow-card`), so the dark sample bottles read as night windows hung on a daylit wall. Names are ink, meta is slate, price is ink, and the sale price is bronze.
- **Collection cards with images** keep their dark tokens and scrim, so the text over photos stays ivory. Empty tiles (For Him/Her/Unisex) become pearl panes with the horizon gradient and a bronze hairline.
- **Buttons:** the primary is the gold gradient with an ink label and `--shadow-button`. The ghost is an ink hairline at 55% that turns fully ink on hover. The text button is ink and turns gold on hover. The WhatsApp outline uses the same ink hairline.
- **Forms:** pearl fields, a #8F8474 border (3.61:1), a slate border on hover, and on focus a bronze border plus a 3px gold halo. The checkbox is ink when checked. Error and valid states are restated, because unlayered overrides would otherwise mask them (see below).
- **Size chips:** pearl, and when selected a warm #FBF3E6 fill, gold border, 2px gold baseline and a small glow. Quantity uses pearl with the field border.
- **Cart drawer and mobile nav:** a pearl panel, a linen foot, `--shadow-overlay`, and a light ink scrim at 24% with a 2px blur. The drawer subtotal is bronze and Checkout is the gold bar.
- **Sticky bar and filter bar:** linen glass with an upward soft shadow.
- **Badges:** sale and save are ink with champagne text, which carries the most weight on the photo. New and sold-out are pearl chips.
- **Stars:** #A87F4E (3.24:1) with a 14% ink empty star.
- **Focus and selection:** the focus ring is bronze #6E5028. Selection is #E7D2AE with ink text.
- **Cash on Delivery trust:** on the PDP the trust row becomes a pearl-to-champagne card with a bronze hairline, so COD reads as a promise rather than fine print. In the hero it stays a quiet slate line (6.06:1 on the haze). In checkout, the selected COD option already picks up the gold quiet fill. The "Free" delivery label moves to success green.
- **WhatsApp FAB:** a pearl disc with a bronze glyph, a bronze hairline and a soft shadow.

## Motion and brand pieces on light
- **WebGL hero ribbons (`ribbons-gl.js`):** these are drawn additively (`gl.blendFunc(ONE, ONE)`), and additive light cannot show on ivory because it only brightens toward white. On light, switch to premultiplied normal blending (`ONE, ONE_MINUS_SRC_ALPHA`) and make them silk in daylight: `uGold` becomes #A27A4A and `uAmber` becomes #C9A472 at about 35% alpha, plus a faint pearl-blue ribbon (#C9D3DA) for the zenith. The ribbons should read as high cirrus catching the sun rather than glowing gas. Feed the colours from `rc.colors` according to the theme so the shader does not fork. The prototype applies `mix-blend-mode: multiply` to `.hero__ribbons` as a stopgap.
- **Hero plates (`ribbons-a/b-*.webp`):** these are baked on black, so the light theme needs its own pair rendered on transparent or ivory, with bronze and pearl silk (`dev-tools/render-plates.mjs` with a light palette). Until that pair exists, the prototype multiplies them at 22% with a sepia shift so they tint the dawn slightly instead of punching black holes. `.hero__glow::before` is repainted as a white-gold sun.
- **Intro loader (`intro.js`, `.sf-intro`):** the curtain becomes the dawn sky (sun radial over `--grad-dawn`) instead of #0A0A0A. The mist becomes champagne at 34%, the tag becomes dawn gold and the skip link becomes slate. The canvas particle fill (`rgb(214,180,136)` in intro.js line 142) needs a theme value of #A27A4A, or it will be invisible. The mark uses the deepened monogram (below). Keep the choreography; only the light changes.
- **Custom cursor:** the ring becomes a 1px #6E5028 bronze ring and the filled state is bronze at 90%, which is visible on ivory and over the dark product media.
- **Logo:** the gold monogram washes out on ivory in its pale highlights. The prototype uses `filter: brightness(.74) contrast(1.28) saturate(1.25)`, which holds up at 40px and in the hero. For production, export a `monogram-on-light` asset with the same letterform, shaded from #5E4422 to #A27A4A with no stop lighter than #B38A58, and swap it with `<picture>` or by theme class. The footer keeps `lockup-on-black` because the footer stays night. It is set to #0A0A0A to match, though a faint edge still shows, so a transparent lockup would be cleaner.

## What I would NOT change
- Typography: Cormorant Garamond display at weight 300/400, Jost body, the tracking scale and uppercase labels. The sibling likeness depends on the type.
- The sharp corners (radius 0), hairline borders, spacing scale, section rhythm, grid, card proportions (4:5) and the header layout.
- The gold gradient as the primary button and as the hero title clip. Only its stops move darker.
- The logo letterform, the gold identity and the dark theme itself. Nothing here touches `:root`. Everything lives under `html[data-theme="light"]`.
- Motion timings and easings. The light theme changes colour, not choreography.
- Product and collection photography treatment. It is framed, not filtered.

## Hard-coded colours seen bleeding through (to tokenise before shipping)
- `critical.css` `.badge--sold-out` rgba(10,10,10,.82); `.btn--primary` shadow rgba(0,0,0,.6); `.btn--primary:hover` rgba(0,0,0,.55); `.btn--primary:active` var(--gold-600).
- `critical.css` `.site-header.is-transparent` drop-shadow rgba(0,0,0,.6); `.site-header.is-solid` box-shadow rgba(0,0,0,.6) (a grey band under the header on ivory).
- `critical.css` `.filter-bar` and `.sticky-bar` rgba(10,10,10,.94); `.gallery__zoom-hint` rgba(10,10,10,.6); `.placeholder__mark` rgba(212,176,132,.28).
- `critical.css` `.field__input` border var(--ink-500); `.field__input--select option` var(--ink-700); `.breadcrumb__item::after` var(--ink-500); `.gallery__dot::before` var(--ink-500); `.stock-line::before` var(--gold-300) (a pale dot on ivory).
- `critical.css` `.stars__star--empty` / `--partial` rgba(245,240,232,.16) (invisible on ivory); `.size-chip__input:checked + .size-chip__card` rgba(212,176,132,.10); `.size-chip__card:hover` rgba(212,176,132,.5); `.qty__btn:active` rgba(212,176,132,.14).
- `critical.css` `.gallery__thumb` opacity .6 (reads washed grey on ivory; 0.82 in the prototype).
- `critical.css` `.sf-intro` #0A0A0A, `.sf-intro__mist` gold radials, `.sf-intro__tag` and the ivory skip colour.
- `site.css` `.summary__value--free` var(--gold-300) (1.9:1 on ivory); `.collection-card__scrim` rgba(10,10,10,.88) (kept deliberately on image cards); `.drawer__body` scrollbar var(--ink-500); `.drawer__handle` / `.modal__handle` var(--rule-strong); `.nav-mobile__input` border var(--ink-500); `.pagination__gap` var(--ink-400); `.prose a` underline var(--rule-strong); `.timeline__dot::after` var(--ink-500); the lines at 993, 1036, 1170, 1678, 1708 and 2083 (rgba(10,10,10) and rgba(245,240,232) washes).
- All `.t-light ...` rules in `site.css` (2578-2627) assume ivory bands on a dark page. On a light page they must invert to the night set.
- `motion/10-hero.css` `.hero__glow::before` rgba(215,164,102) radial and the desktop title sheen rgba(255,241,205); `ribbons-gl.js` additive blend and #C29C6E / #E0A45C defaults; `intro.js` rgb(214,180,136).
- Assets: `lockup-on-black-800.webp` (footer), `monogram-*` (header, hero, intro), `ribbons-a/b-*.webp` (baked on black).

## Implementation notes
- The prototype is injected unlayered, so it beats every `@layer` rule regardless of specificity. That also masks state rules (hover, `:checked + card`, `.is-active`, `.has-error`), which I had to restate. In production, put the light token set in `@layer tokens` as `html[data-theme="light"]` and the few component deltas in `@layer overrides`, so existing state rules keep working. Most of this file then reduces to tokens plus about 15 component rules.
- New tokens that the base theme should also define (with dark values): `--border-field`, `--field-bg`, `--shadow-card`, `--shadow-button`, `--grad-dawn`, `--grad-dawn-sun`, `--grad-horizon`, and a night-band token set (the `.t-light` inversion) as a reusable `.t-night` class.
- Set `data-theme` server-side on `<html>` from the admin setting, so there is no flash of the dark theme. Also set `color-scheme: light` and `<meta name="theme-color" content="#F7F2EA">` (#14120F while the announcement bar is visible on mobile).
