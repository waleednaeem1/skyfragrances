# Sky Fragrances light theme: the definitive spec

Status: final design spec and build plan, 2026-10-01. It is built from three audits (`audit/`), three prototyped directions (`directions/1-3/`), two judges (`judging/`) and a verification render of this spec.

Companion file: `light-tokens.css`. It holds the complete `html[data-theme="light"]` token block, the night scope and the component overrides the builders will integrate. Where this document and that file disagree, the CSS file wins on values and this document wins on intent.

Verification render: before writing this spec I injected `light-tokens.css`, plus a shim that emulates the token consumers listed in section 6, into the running site (port 8144, reduced motion). Screens are in the scratchpad at `theme/final/shots/`. A text-contrast scan with that injection (`theme/final/scan.mjs`) over `/`, `/product/azure-oud`, `/shop`, `/faq`, `/cart`, `/track`, `/contact`, `/scent-finder` and a 404 found 0 text failures, and no page overflowed at 375 px.

---

## 1. The decision

The judges split. The art-direction judge chose **3 Atelier Parchment** (47/60, against 45 for D1 and 41 for D2). The usability judge chose **1 Porcelain and Gilt** (57/70, against 48 for D3 and 47 for D2). I looked at every key screenshot myself: the three home folds, the side-by-side home and PDP strips in `judge-art2/`, both drawers and both PDP folds.

**Chosen: Direction 3, Atelier Parchment, as the chassis.** It is re-toned toward Direction 1's luminance and discipline and given Direction 2's sky. The result is called **Atelier at Daybreak**.

Why D3 and not D1: the owner asked for the light theme to belong to the same house as the dark one. D3 is the only direction where every signature object of the dark site survives onto the day ground: the ink announcement strip, the ink primary button with a champagne label, the bronze version of the gold display title, a night band mid-page and the `#0A0A0A` footer. D1 is the cleanest page, but it removes the gold, ends each page on light, and at its fold it could belong to any DTC brand.

Where I override D3 to take the usability judge's side (each of these was a usability loss for D3):

| D3 as prototyped | Final | Why |
|---|---|---|
| Parchment `#F3ECE0` ground with laid-paper grain behind all content | Ground lifted to `#F6F1E8`. Grain only in the hero and the scent-finder band, at a lower alpha (`.22`, down from `.34`), and never behind body copy, prices or forms | Usability: grain behind 13 px Jost and speckle on phones. Art: "reads grey-beige at arm's length" |
| Cream vellum mounts `#F8F3EA`, 7 px inset | Near-white vellum `#FEFCF8`. D1's proportions: `clamp(6px, .8vw, 14px)` on cards and `clamp(12px, 1.4vw, 20px)` on the PDP gallery. `--mat: 0` when real white packshots arrive | Usability: "white packshots inside a cream mount on cream paper will look dingy". Art: "7 px reads as a border" |
| Sepia drawer scrim `.46` + 3 px blur | Warm scrim `rgba(33,26,19,.30)` + 4 px blur | The judges disagreed (art wanted D1's frosted veil, usability wanted a warm scrim). This is the middle: the page recedes without going brown, and the drawer edge stays crisp |
| Sticky bar and filter bar at 95% | Opaque paper `#FBF8F2` with a bronze top hairline | Both judges: the name and rating ghosted through the bar |
| Footer Subscribe was ink on `#0A0A0A` (1.13:1) | The footer is a night scope, so its primary button returns to the brand gold gradient with an ink label (8.66:1) | Usability defect |

Grafts taken, and where each one lands:

- **From D2 (Sky).** A daybreak haze at the top of every page. The hero sky runs from a pearl-blue zenith to a peach horizon. A dusk wash deepens into the night footer. The For Him / For Her / Unisex tiles become small horizons. The PDP and cart trust rows become a vellum-to-champagne card.
- **From D1 (Gallery).** Mat proportions. Desaturated inactive thumbnails with an ink frame on the active one. The selected payment choice is a vellum card with an ink border and a bronze left-edge inset.
- **Kept from D3.** Bronze title gradient, ink primary with champagne label, champagne Sale badge, COD seal rules, `#0A0A0A` footer, tokenised meters and tracks, `#766A58` placeholder.

Rejected: D2's gold-gradient primary on the day ground (six gold slabs read as a promo coupon), D1's ink hero title (it gives up the signature gold headline), and D1's stone footer (the page must end at night).

## 2. Concept: Atelier at Daybreak

The dark theme is the house at night. The light theme is the same atelier at first light. The curtains are open, the sky outside is turning from pearl to peach, and the room is warm paper, sepia ink and bronze. Gold stops glowing and becomes bronze leaf catching the morning. The night never disappears. It is the ink of the day theme: the announcement strip greets you first, every primary button is a piece of the night set into the page, one night band sits in the middle of the home page, and every page ends at dusk before falling into the `#0A0A0A` footer. Each page is a day: **daybreak at the top, day through the catalogue, dusk at the newsletter, night in the footer.**

The rules that keep it luxurious:

1. **Gold is scarce on the day ground.** Saturated gold appears only on the champagne button label, the Sale badge, the gilt monogram and inside night scopes. Everything else that was gold becomes bronze (`#7A5530`) or bronze-line (`#9A7442`).
2. **Ink carries the type.** Headings, prices and body text are sepia ink `#1E1812` (15.63:1). Muted text is walnut `#5E5345` (6.67:1).
3. **Dark imagery is framed, never floated.** Every dark render sits in a vellum mat with a bronze hairline, like a specimen plate.
4. **Nothing changes shape.** Typography, spacing, layout, radii (all 0), motion timings and copy are identical to the dark theme. The theme is a change of light, not a redesign.
5. **The dark theme is untouched.** Every value in the dark theme computes exactly as it does today (section 5.2 explains how that is guaranteed).

---

## 3. Final token table: `html[data-theme="light"]`

All ratios are WCAG 2.x, computed (`theme/final/cr.mjs`). "parch" is the page ground `#F6F1E8`, "vellum" is `#FEFCF8` (fields, mats, drawer) and "stone" is `#EDE5D8` (scent-finder band).

### 3.1 Primitives (new, light only, prefixed `--lt-`)

| Token | Value | Name |
|---|---|---|
| `--lt-parchment` | `#F6F1E8` | page ground |
| `--lt-paper` | `#FBF8F2` | raised surfaces, sticky and filter bars, drawer foot |
| `--lt-vellum` | `#FEFCF8` | fields, image mats, drawer sheet, overlays |
| `--lt-stone` | `#EDE5D8` | scent-finder band, sunken |
| `--lt-stone-deep` | `#E4DACA` | hover wash |
| `--lt-dusk` | `#EADFCD` | last stop of the dusk wash, where it meets the footer |
| `--lt-ink` | `#1E1812` | sepia ink |
| `--lt-ink-soft` | `#2A2119` | primary button mid-stop |
| `--lt-walnut` / `--lt-walnut-soft` | `#5E5345` / `#6B5E4E` | muted text / struck price |
| `--lt-disabled` | `#8A7F70` | disabled only |
| `--lt-bronze` / `-deep` / `-dark` | `#7A5530` / `#5E3F20` / `#4A311A` | accent, hover, pressed |
| `--lt-bronze-line` | `#9A7442` | accent borders |
| `--lt-control` | `#8F826E` | control borders |
| `--lt-placeholder` | `#766A58` | input hints |
| `--lt-champagne` | `#E7D2AE` | label on ink |
| `--lt-sale` | `#D4B084` | Sale badge fill (the dark theme's gold-400) |
| `--lt-moss` / `--lt-wax` | `#2F6B45` / `#9E2F24` | success / danger |
| `--lt-night` / `-raised` / `-overlay` | `#17120D` / `#211A13` / `#2A2118` | night scopes |

### 3.2 Semantic tokens (existing names, light values)

| Token | Light value | Contrast | Dark value (unchanged) |
|---|---|---|---|
| `--surface` | parchment `#F6F1E8` | ground | ink-900 |
| `--surface-raised` | paper `#FBF8F2` | ground | ink-800 |
| `--surface-overlay` | vellum `#FEFCF8` | ground | ink-700 |
| `--surface-sunken` | stone `#EDE5D8` | ground | ink-850 |
| `--surface-hover` | `#E4DACA` | ground | ink-600 |
| `--text` | `#1E1812` | 15.63 parch, 17.16 vellum, 14.06 stone, 13.34 dusk | ivory-100 |
| `--text-muted` | `#5E5345` | 6.67 parch, 7.32 vellum, 6.00 stone, 5.69 dusk, 6.12 peach | ivory-400 |
| `--text-disabled` | `#8A7F70` | 3.49 parch (disabled is exempt) | ink-400 |
| `--text-inverse` | vellum `#FEFCF8` | 6.47 on bronze (badge, skip link, count, selection) | ink-900 |
| `--border` | `rgba(30,24,18,.14)` | decorative hairline only | rule |
| `--border-faint` | `rgba(30,24,18,.08)` | decorative | rule-faint |
| `--border-accent` | `#9A7442` | 3.77 parch, 4.14 vellum (ghost button edge, tiles) | rule-strong |
| `--accent` | `#7A5530` | 5.89 parch, 6.25 paper, 6.47 vellum, 5.30 stone, 5.83 zenith, 5.41 peach | gold-400 |
| `--accent-hover` | `#5E3F20` | 8.45 parch | gold-200 |
| `--accent-active` | `#4A311A` | 10.69 parch | gold-500 |
| `--accent-quiet` | `rgba(122,85,48,.08)` | tint | gold .12 |
| `--accent-halo` | `rgba(122,85,48,.18)` | focus halo | gold .18 |
| `--danger-halo` | `rgba(158,47,36,.12)` | halo | red .16 |
| `--success` | `#2F6B45` | 5.64 parch, 6.19 vellum | success-on-dark |
| `--danger` | `#9E2F24` | 6.47 parch, 7.10 vellum | danger-on-dark |
| `--focus-ring` | `#7A5530` | 5.89 parch | gold-400 |
| `--price` | `#1E1812` | 15.63 | gold-400 |
| `--price-was` | `#6B5E4E` | 5.59 parch, 5.94 paper | ivory-400 |

### 3.3 Existing effect tokens remapped in light

| Token | Light value | Purpose |
|---|---|---|
| `--rule-faint` / `--rule` / `--rule-light` | ink `.08` / `.14` / `.14` | hairlines that are no longer ivory-alpha |
| `--rule-strong` | `rgba(122,85,48,.55)` | accent hairline |
| `--rule-light-strong` | `#8F826E` | control edge (legacy name) |
| `--scrim-full` | parchment veil `.62 / .34 / .84` | home hero with an owner photo (section 7.3) |
| `--scrim-modal` | `rgba(33,26,19,.30)` | drawer and modal overlay, with a 4 px blur at the consumer |
| `--grad-gold` | `#3E2914 → #7A5530 → #9A7442 → #7F5A32 → #5E3F20 → #3E2914` | every `.text-gold-grad` title and the hero title. Lightest stop 3.77:1 on parchment, which is enough for display text only |
| `--grad-gold-rule` | bronze, peak `.55` | section-header rules, footer crown (night keeps champagne), pyramid tiers |
| `--grad-gold-sheen` | champagne band `.24` | the `.btn::before` sweep, now a champagne glint across ink |
| `--grad-gold-radial` | bronze `.10 → .035` | ambient glow consumers |
| `--glow-accent` | bronze hairline plus a soft warm glow | primary hover in the dark theme; kept for any other consumer |
| `--shadow-light` / `--shadow-overlay` | brown-tinted `rgba(58,40,22,…)` | panels, drawer, mega menu, toast, nav-mobile, suggest |

`--grad-gold-button` (brand gold fill), `--scrim-bottom` and `--scrim-hover` (on photography) and `--whatsapp-green` are **not** remapped. `--grad-gold-border` is dead (no consumer) and is left alone.

### 3.4 New tokens (light-only, consumed with a dark fallback)

Every token below is declared **only** under `html[data-theme="light"]`. Each consumer reads it as `var(--token, <today's exact expression>)`, so the dark theme computes exactly what it computes today. Values are in `light-tokens.css`.

| Token | Light value | Replaces (dark fallback kept verbatim) | Ratio / note |
|---|---|---|---|
| `--btn-primary-fill` | ink gradient `#1E1812 → #2A2119 → #3B2A1A → #5E3F20` at 200% size | `var(--grad-gold-button)` | the existing hover shifts `background-position` to 100%, so hover warms ink into bronze with no extra rule |
| `--btn-primary-fg` | champagne `#E7D2AE` | `var(--text-inverse)` | 11.91 on ink, 10.70 mid, 6.44 on the bronze end |
| `--btn-primary-shadow` | inset champagne hairline `.26` + short sepia drop | `0 1px 2px rgba(0,0,0,.6)` | |
| `--btn-primary-shadow-hover` | brighter hairline + long bronze drop | `0 12px 32px rgba(0,0,0,.55), var(--glow-accent)` | |
| `--btn-primary-press` | `#4A311A` | `var(--gold-600)` | champagne on it is 8.15 |
| `--tint-ghost-hover` | bronze `.08` | `rgba(212,176,132,.06)` | |
| `--tint-press` | bronze `.12` | `rgba(212,176,132,.14)` | qty press |
| `--tint-selected` | vellum | `rgba(212,176,132,.10)` | selected size chip |
| `--border-accent-hover` | `#7A5530` | `rgba(212,176,132,.5)` | size chip hover |
| `--border-control` | `#8F826E` | `var(--ink-500)` | 3.67 vellum, 3.34 parch |
| `--control-line` | `#8F826E` | `var(--border)` | toolbar select, qty, size chip edges (1.26:1 before) |
| `--text-faint` | `#8F826E` | `var(--ink-500)` / `var(--ink-400)` | breadcrumb slash, idle gallery dots, pagination gap |
| `--scrollbar-thumb` | ink `.28` | `var(--ink-500)` | drawer and filter rails |
| `--field-bg` | vellum | `var(--surface-sunken)` | |
| `--field-placeholder` | `#766A58` | `var(--text-muted)` | 5.16 vellum. Reads as a hint, not typed data |
| `--option-bg` | vellum | `var(--ink-700)` | native `<option>` rows |
| `--icon-chevron` | chevron stroke `%237A5530` | today's `%23D4B084` URI | 6.47 on vellum (was 1.86) |
| `--icon-search` | magnifier stroke `%235E5345` | today's `%239C968C` URI | 7.32 on vellum |
| `--surface-glass` | paper `#FBF8F2`, opaque | `rgba(10,10,10,.94)` | sticky add bar, mobile filter bar |
| `--badge-sold-bg` / `-fg` | stone `.95` / walnut | `rgba(10,10,10,.82)` / `var(--text-muted)` | 5.45 |
| `--star-empty` | ink `.16` | `rgba(245,240,232,.16)` | stars, star input |
| `--stock-dot` / `--price-free` | moss `#2F6B45` | `var(--gold-300)` | green means available or free, and gilt means brand |
| `--track` | ink `.08` | `rgba(245,240,232,.10)` or `var(--rule-faint)` | meters, review, free-ship and progress tracks |
| `--fill-accent` | `#5E3F20 → #9A7442` | `var(--grad-gold-button)` | their fills, plus the rail thumb |
| `--danger-quiet` / `--success-quiet` | wax `.06` / moss `.07` | the `rgba(229,115,107,…)` and `rgba(111,191,139,.06)` literals | error and success panels |
| `--shadow-hairline` | inset white + long sepia drop | `0 1px 2px rgba(0,0,0,.6)` | solid header |
| `--header-float-filter` | `none` | `drop-shadow(0 1px 8px rgba(0,0,0,.6))` | transparent header over the hero |
| `--sold-out-filter` | `grayscale(.55) sepia(.12) opacity(.78)` | `grayscale(.35) brightness(.72)` | faded, not dirtied |
| `--media-dim` | `grayscale(.8) opacity(.55)` | `grayscale(.4) brightness(.6)` | unavailable cart line |
| `--accent-ghost` | bronze `.26` | `rgba(212,176,132,.28)` | placeholder monogram |
| `--title-fallback` | `#7A5530` | `var(--gold-400)` | `.text-gold-grad` colour when `background-clip:text` is unsupported |
| `--hero-img-k` | `1.45` | `1` | multiplier on the admin's inline `--hero-opacity` (section 7.3) |
| `--skeleton-sheen` | white `.6` band | ivory `.06` band | |
| `--shadow-deep` / `--shadow-float` | sepia `.35` / `.22` | black `.9` / `.45` | quiz deck card, add-to-cart flight dot |
| `--glow-hero` + `--glow-hero-blend` | bronze radial `.20` in `multiply` | the amber `.5` radial in `normal` | fallback glow behind the monogram |
| `--sheen-title` | `rgba(214,180,128,.85)` | `rgba(255,241,205,.92)` | champagne glint on the bronze title |
| `--sheen-card` + `--sheen-card-blend` | white `.45` band in `normal` | ivory band in `soft-light` | product media sweep only |
| `--halo-blend` | `multiply` | `screen` | story halo |
| `--quiz-line-strong` / `--quiz-line` / `--quiz-line-faint` | bronze `.42` / `.28` / `.14` | gold `.42` / `.28 or .30` / `.14` | quiz frames |
| `--glow-strong`, `--mist-a`, `--mist-b` | bronze `.28` radial, white `.85`, bronze `.20` | gold `.55`, ivory `.5`, gold `.45` | quiz reveal |
| `--intro-ground`, `--intro-mist-a/-b`, `--intro-tag`, `--intro-skip` | vellum sun on parchment, bronze mists, bronze tag, walnut Skip (6.0 or better anywhere on the intro ground) | `#0A0A0A`, the two mist literals, `rgba(212,176,132,.85)`, `rgba(245,240,232,.72)` | intro loader |
| `--cursor-outline` | 1 px vellum ring `.65` | `none` | cursor reads over dark plates |
| `--spray-glow` | `0 0 6px rgba(122,85,48,.35)` | `0 0 6px var(--accent)` | spray droplets |
| `--grad-daybreak`, `--grad-dusk`, `--grad-sun`, `--grad-lamp-edge`, `--grad-hero-day`, `--grad-horizon` | see CSS | none (light only) | the sky system (section 4) |
| `--paper-grain` | 220 px SVG turbulence tile, alpha `.22` | none | hero and scent-finder band only |
| `--shadow-paper`, `--shadow-lift`, `--mount-line`, `--mat`, `--mat-gallery` | see CSS | none | mounts |

### 3.5 The night scope

Some elements are pieces of the night set into the day page. They get the dark token set, tinted warm, by one selector: `html[data-theme="light"] :where(.t-night, .announcement, .site-footer, .toast, .insta, .collection-card:not(.collection-card--empty):not(.collection-card--gender), .hero--strip:not(.hero--empty), .gallery__zoom-hint, .modal--lightbox .modal__close)`.

| Token | Night value | Contrast on `#17120D` |
|---|---|---|
| `--surface` / `-raised` / `-overlay` | `#17120D` / `#211A13` / `#2A2118` | |
| `--text` | `#F3ECE0` | 15.85 (14.64 on raised) |
| `--text-muted` | `#B3A68F` | 7.77 |
| `--accent`, `--price`, `--focus-ring` | `#D4B084` | 9.16 |
| `--border-control`, `--rule-light-strong` | `#7A6E5E` | 3.74 (3.98 on `#0A0A0A`) |
| `--field-bg` / `--field-placeholder` | `#211A13` / `#A0937D` | placeholder 5.70 |
| `--btn-primary-fg` | `#17120D` | ink on the brand gold fill, 8.66 |
| `--grad-gold`, `--grad-gold-rule`, `--grad-gold-sheen`, `--grad-gold-radial`, `--glow-accent`, `--scrim-full`, `--rule*` | today's dark literals (`--shadow-overlay` keeps its warm light value, so the night toast floats on a light page without a black smear) | |
| every other light-only token from 3.4 | `initial` | |

`initial` on a custom property makes it guaranteed-invalid, so every `var(--token, fallback)` consumer inside a night scope falls back to today's dark expression, resolved against the night semantic tokens. That is how the primary button inside the night band, the footer Subscribe button and the collection-card text all return to the dark theme's exact look without a second set of component rules.

`.site-footer` further sets `--surface: #0A0A0A`, `--surface-raised: #141312`, `--text: #F5F0E8` (17.45) and `--text-muted: #9C968C` (6.75), so the footer is the dark theme's own footer, byte for byte in colour.

The selector uses `:where()` on purpose. Its specificity is (0,1,1), so the footer's own override can win and no component rule is outranked by accident.

---

## 4. Section rhythm on the home page

| # | Section (`app/views/home.php`) | Light treatment | Beat |
|---|---|---|---|
| 0 | Announcement bar | night `#17120D`, champagne `#D9C8A8` text (11.32), faint gold hairline under it | the dark house greets you first, with COD in the highest-contrast line on the page |
| 1 | Header | parchment glass at 90% with a 14 px blur and a bronze `.22` hairline; ink nav, bronze hover; gilt monogram asset | |
| 2 | Hero `#hero` | **daybreak**: pearl-blue zenith `#EEF1F1` → `#F8F4EC` → peach horizon `#F5E6D3` → parchment, a vellum sun `--grad-sun` behind the monogram, stone lamp edges and the grain. Bronze title gradient, walnut sub, ink primary, COD seal between two bronze rules | first light |
| 3 | Shop by Collection `#collections` | sky windows: dark plates in a vellum border mat, outer bronze hairline, paper shadow; scrim ends at 45% so each collection's sky shows | the collections are times of day |
| 4 | Best Sellers, 5 New Arrivals | specimen plates (mounted cards) on parchment, hairline dividers | day |
| 6 | For whom `#for-whom` | horizon tiles: paper with `--grad-horizon` (pearl to peach) inside a bronze-line border | three small skies |
| 7 | Scent finder `#scent-finder` (`section--glow`) | warm stone band with grain and a vellum pool; ink primary | afternoon |
| 8 | Why us `#why-us` | **night band** (`t-night`): `#17120D`, a gold top glow, fading champagne hairlines top and bottom, gold icons | the one deep breath mid-page, carrying the COD promise |
| 9 | Voices | paper cards with a bronze `.18` border and paper shadow | |
| 10 | Instagram | six mounted tiles, then 3 rem of air before the rule | |
| 11 | Join `#join` | **dusk**: the `.site-main` dusk wash deepens to `#EADFCD` behind the newsletter | evening |
| 12 | Footer | `#0A0A0A` night, champagne crown rule, pale-gold lockup on transparent | night |

Every other page gets the same arc from `.site-main`. The top 28 rem carry the daybreak haze at about 60% of Dawn Sky's strength (a pearl zenith under the header on shop, PDP, FAQ, checkout), and the bottom `min(36rem, 60%)` carries the dusk. The footer's `margin-block-start` moves into `.site-main`'s bottom padding (same total space), so the dusk runs unbroken into the footer and there is no parchment-to-black cut.

The home `#why-us` band must emit `t-night` in light and keep `t-light` in dark (section 10). `.t-light` is never used by the storefront in light, which keeps the admin (`body.t-light`) and the site.css `.t-light` patches out of the light theme entirely.

---

## 5. How the theme is built (architecture rules)

### 5.1 Where each block of `light-tokens.css` goes

`light-tokens.css` has five blocks, in order. Blocks 1 and 2 share the file's first `@layer tokens { }` wrapper:

1. `@layer tokens { html[data-theme="light"] {…} }`: goes into **critical.css**, inside its existing `@layer tokens`, directly after the `.t-light` block. It must be in critical.css because site.css is deferred on home, listing and product (`media="print"` swapped by `css.js`). Tokens anywhere else would flash dark.
2. `@layer tokens { …night scope…, .site-footer {…} }`: also **critical.css** `@layer tokens`, after block 1.
3. The first `@layer components {…}` (announcement, header, hero, badges, product card, gallery, size chip, bars, trust row, FAB): **critical.css**, appended at the end of its `@layer components` (before `@layer utilities`).
4. The second `@layer components {…}` (`.site-main` sky, `.t-night`, `.section--glow`, collection cards, insta, drawer, modal, choice, panels): **site.css**, at the end of its components layer, in place of the `.t-light` patch block's position (the patch block itself stays; see 5.4).
5. `@layer motion {…}`: split across the motion parts. `--sf-mist` goes into `60-transitions.css`, the plate and ribbon blend into `10-hero.css`, and the story bottle mount into `30-story.css`. Rebuild `motion.css` with `cat motion/*.css > motion.css`, or the fix does not ship.

The light component overrides live in the **same layer** as the rules they amend. They win by specificity (the `html[data-theme="light"]` prefix adds (0,1,1)), not by a later layer. A later layer would silently beat every `:hover`, `:checked`, `.is-active` and `.has-error` rule, which is the bug every prototype hit. Where an override touches a property that a state rule also sets, the override restates that state. For example, `.gallery__thumb.is-active` border and `.whatsapp-fab:hover` border are both restated in the file, and so is the reduced-motion `backdrop-filter: none` on `.drawer-overlay`.

### 5.2 The dark theme stays pixel-identical: the fallback pattern

- **No new token is declared on `:root`.** Light-only tokens exist only under `html[data-theme="light"]`.
- **Every converted consumer keeps today's value as its fallback**: `color: var(--btn-primary-fg, var(--text-inverse))`, `box-shadow: var(--btn-primary-shadow, 0 1px 2px rgba(0, 0, 0, 0.6))`, `background-image: var(--icon-chevron, url("…%23D4B084…"))`. In the dark theme the token is undefined, so the declaration computes to exactly today's value.
- **Do not create derived aliases on `:root`** (for example `--on-accent: var(--text-inverse)`). A custom property that references another is resolved where it is declared, so an alias on `:root` would freeze the root value and break `.t-light` subtrees such as the admin and the dark site's ivory `#why-us` band. The fallback form resolves at the element and keeps them correct.
- **Existing tokens get light values only under `html[data-theme="light"]`.** Nothing in `:root`, `.t-light`, admin.css or the print block changes.
- **`color-scheme: dark` is NOT added to the dark theme in this release.** It would change the dark site's scrollbars, native popups and autofill (runtime audit 1.4). That is a visible change and would break the pixel-identical criterion. It is logged as a follow-up (section 15). Light declares `color-scheme: light` explicitly, and night scopes declare `dark`.

### 5.3 Why `.t-night` and not reusing `.t-light`

`.t-light` means two things today: a token scope, and the ~20 site.css component patches that assume an ivory band (gold-800 trust icons, white fields, and so on). The admin panel runs on `body.t-light`. Making `.t-light` mean "night" inside a light page would either fight those patches or restyle the admin. So:

- dark theme: `#why-us` keeps `class="section t-light"` (unchanged markup, unchanged pixels);
- light theme: `#why-us` renders `class="section t-night"`;
- `html[data-theme="light"]` is never rendered on admin pages (the admin layout is separate), so no light rule can reach the admin.

### 5.4 The site.css `.t-light` patch block (2570-2627)

Leave it as it is in this release. It only fires under a `.t-light` ancestor, which in light mode exists nowhere in the storefront (and in dark mode only in `#why-us` and the admin, which must stay identical). Converting those patches to tokens is a worthwhile clean-up, but it touches the admin and the dark band and is not needed for the light theme. It is listed as a follow-up.

---

## 6. Consumer conversions: every THEME row from the audits

Pattern: `property: var(--token, <exact current value>)`. Rows marked "remapped" need no edit, because the token they already read gets a light value. Line numbers are from the audits (working tree of 2026-10-01).

### 6.1 critical.css (owner A)

| Line | Selector | Change |
|---|---|---|
| 347 | `::placeholder` | `color: var(--field-placeholder, var(--text-muted))` |
| 407 / 408 | `.text-gold-grad` | colour `var(--title-fallback, var(--gold-400))`; the gradient is remapped |
| 433, 1604 | `.section--glow`, `.hero--empty` | remapped `--grad-gold-radial`, plus the light overrides |
| 565, 877 | `.divider`, `.btn::before` | remapped |
| 674-676 | `.announcement` | night scope plus override |
| 746-747 | `.badge--sold-out` | `var(--badge-sold-bg, rgba(10,10,10,.82))`, `var(--badge-sold-fg, var(--text-muted))` |
| 823 | `.breadcrumb__item::after` | `var(--text-faint, var(--ink-500))` |
| 899, 904 | `.btn--primary` | `color: var(--btn-primary-fg, var(--text-inverse))`, `background-image: var(--btn-primary-fill, var(--grad-gold-button))`, `box-shadow: var(--btn-primary-shadow, 0 1px 2px rgba(0,0,0,.6))` |
| 907 | `.btn--primary:hover` | `color: var(--btn-primary-fg, var(--text-inverse))` |
| 910 | `.btn--primary:active` | `background-color: var(--btn-primary-press, var(--gold-600))` |
| 1004-1005 | `.btn--primary.is-loading` | fill and fg tokens as at 899 |
| 1027 | `.btn--primary:hover` (hover media) | `box-shadow: var(--btn-primary-shadow-hover, 0 12px 32px rgba(0,0,0,.55), var(--glow-accent))` |
| 1032 | `.btn--ghost:hover` | `var(--tint-ghost-hover, rgba(212,176,132,.06))` |
| 1039 | `.btn--danger:hover` | `var(--danger-quiet, rgba(229,115,107,.08))` |
| 1087-1088 | `.field__input` | `background-color: var(--field-bg, var(--surface-sunken))`, `border-color: var(--border-control, var(--ink-500))`. Use `background-color`, never the `background` shorthand, which erased the select chevron in D1 and D2 |
| 1116, 1364 | select chevrons | `background-image: var(--icon-chevron, url("…%23D4B084…"))` |
| 1123 | `option` | `background: var(--option-bg, var(--ink-700))` |
| 1133 | `.field__input--search` | `var(--icon-search, url("…%239C968C…"))` |
| 1360 | `.toolbar__select` | `border-color` via `var(--control-line, var(--border))` |
| 1386, 2400 | `.filter-bar`, `.sticky-bar` | `background: var(--surface-glass, rgba(10,10,10,.94))` |
| 1504 | `.gallery__dot::before` | `var(--text-faint, var(--ink-500))` |
| 1596 | `.hero__img` | `opacity: calc(var(--hero-opacity, 0.6) * var(--hero-img-k, 1))` (values above 1 clamp to 1) |
| 1602 | `.hero__scrim` | remapped `--scrim-full` |
| 1735 | `.placeholder__mark` | `var(--accent-ghost, rgba(212,176,132,.28))` |
| 1873 | sold-out card image | `filter: var(--sold-out-filter, grayscale(0.35) brightness(0.72))` |
| 1916, 2270 | `.qty`, `.size-chip__card` | border colour via `var(--control-line, var(--border))` |
| 1933 | `.qty__btn:active` | `var(--tint-press, rgba(212,176,132,.14))` |
| 1990 | transparent header items | `filter: var(--header-float-filter, drop-shadow(0 1px 8px rgba(0,0,0,.6)))` |
| 1994 | `.site-header.is-solid` | `box-shadow: var(--shadow-hairline, 0 1px 2px rgba(0,0,0,.6))` |
| 2118 | mega panel | remapped `--shadow-overlay` |
| 2314 | chip checked | `background: var(--tint-selected, rgba(212,176,132,.10))` |
| 2335 | chip hover | `border-color: var(--border-accent-hover, rgba(212,176,132,.5))` |
| 2355, 2357 | empty and partial stars | `var(--star-empty, rgba(245,240,232,.16))` |
| 2482 | `.stock-line::before` | `var(--stock-dot, var(--gold-300))` |
| 2734 | `.sf-intro` | `background: var(--intro-ground, #0A0A0A)` |
| 2756 | `.sf-intro__mist` | stops `var(--intro-mist-a, rgba(212,176,132,.16))` and `var(--intro-mist-a-soft, rgba(212,176,132,.05))` |
| 2763 | `.sf-intro__mist--b` | `var(--intro-mist-b, rgba(255,232,190,.12))` |
| 2818 / 2835 | intro tag / Skip | `var(--intro-tag, rgba(212,176,132,.85))` / `var(--intro-skip, rgba(245,240,232,.72))` |

### 6.2 site.css (owner B)

| Line | Selector | Change |
|---|---|---|
| 173 | unavailable cart image | `filter: var(--media-dim, grayscale(0.4) brightness(0.6))` |
| 199, 762 | cart and drawer errors | `var(--danger-quiet, rgba(229,115,107,.08))` |
| 866, 2381 | form summary, `.panel--danger` | `var(--danger-quiet, rgba(229,115,107,.06))` |
| 2385 | `.panel--success` | `var(--success-quiet, rgba(111,191,139,.06))` |
| 245 | `.summary__value--free` | `var(--price-free, var(--gold-300))` |
| 370, 2361 | free-ship and progress tracks | `var(--track, var(--rule-faint))` |
| 1036, 1678 | meter segments, review track | `var(--track, rgba(245,240,232,.10))` |
| 376, 1043, 2366 | fills | `var(--fill-accent, var(--grad-gold-button))` (review fill too) |
| 602 | gender card radial | light override (horizon tile) |
| 619, 1087 / 645, 1098, 1243, 1750, 2247 / 779, 1111, 1503, 1547, 1883, 1938 | overlays / shadows / handles, prose underline, pyramid, rules | remapped (`--scrim-modal`, `--shadow-overlay`, `--rule-strong`, `--grad-gold-rule`) |
| 708-709 | `.drawer__body` scrollbar | `var(--scrollbar-thumb, var(--ink-500))` |
| 1286 / 1289 | `.nav-mobile__input` border / glyph | `var(--border-control, var(--ink-500))` / `var(--icon-search, url(…))` |
| 1441 | `.pagination__link:hover` | `var(--tint-ghost-hover, rgba(212,176,132,.10))` |
| 1452 | `.pagination__gap` | `var(--text-faint, var(--ink-400))` |
| 1708 | `.star-input__label` | `var(--star-empty, rgba(245,240,232,.16))` |
| 2083 | `.skeleton::after` | `background: var(--skeleton-sheen, <today's ivory sweep>)` |
| 2164 | `.timeline__dot::after` | `var(--border-control, var(--ink-500))` |
| 552-580, 994, 1170-1171 | on-image text and chips | no edit: pinned by the night scope |
| 2570-2627 | `.t-light` patches | leave (5.4) |

### 6.3 Motion parts (owner C; then rebuild `motion.css`)

| Part:line | Selector | Change |
|---|---|---|
| 10-hero:45 | `.hero__glow::before` | `background: var(--glow-hero, <amber radial>)` and `mix-blend-mode: var(--glow-hero-blend, normal)` |
| 10-hero:106 | desktop hero title sweep | middle stop `var(--sheen-title, rgba(255,241,205,.92))`; the gradient is remapped |
| 10-hero plates, canvas | `.hero__plate`, `.hero__ribbons` | light `mix-blend-mode: multiply` (block 5) |
| 20-cards:97-98 | shared sweep | split the selector. The product-card media sweep uses `var(--sheen-card, <today>)` and `mix-blend-mode: var(--sheen-card-blend, soft-light)`. `.collection-card::after` keeps today's literal (it sits on a photo: KEEP) |
| 20-cards:134 / 150 | rail track / thumb | `var(--track, rgba(212,176,132,.18))` / `var(--fill-accent, linear-gradient(…gold-600, gold-300, gold-600))` |
| 30-story:85 | `.story__halo` | `mix-blend-mode: var(--halo-blend, screen)` |
| 30-story bottle | `.story__bottle` | light mount replaces the feathered mask (block 5; fixes the "Composition smudge") |
| 40-micro:63-64 | `.sf-spray` | `box-shadow: var(--spray-glow, 0 0 6px var(--accent))` |
| 40-micro:83 | `.sf-fly` | `var(--shadow-float, 0 2px 10px rgba(0,0,0,.45))` |
| 40-micro:115 | `.sf-cursor__ring` | add `box-shadow: var(--cursor-outline, <current box-shadow or none>)` |
| 50-quiz:21, 144 / 30, 150 / 138 | frames | `var(--quiz-line, …)` / `var(--quiz-line-faint, …)` / `var(--quiz-line-strong, …)`, each with its exact current rgba as fallback |
| 50-quiz:22 | deck wash | gold `.06` stop → `var(--tint-ghost-hover, rgba(212,176,132,.06))` |
| 50-quiz:23 | deck shadow | `var(--shadow-deep, 0 30px 60px -40px rgba(0,0,0,.9))` |
| 50-quiz:139 | card back glow | gold `.18` stop → `var(--quiz-line, rgba(212,176,132,.18))`. The `rgba(10,10,10,0)` end is visually transparent in premultiplied interpolation: KEEP |
| 50-quiz:158 | sigil colour | `var(--title-fallback, var(--gold-400))`; the gradient is remapped |
| 50-quiz:170 | `.quiz-card__glow` | `background: var(--glow-strong, <today>)` |
| 50-quiz:181-183 | mist | ivory stops → `var(--mist-a, …)`, gold stop → `var(--mist-b, …)` |
| 60-transitions:10 | `--sf-mist` | light value declared in `@layer motion` on `html[data-theme="light"]` (block 5). It must be in the motion layer, because that layer comes after `tokens` and its `:root` would win |

### 6.4 KEEP (identical in both themes, do not touch)

`--scrim-bottom`, `--scrim-hover`; `.collection-card__scrim` photo gradients (light shortens them, section 7.8); the `.collection-card::after` sweep; `.gallery__zoom-hint` and lightbox-close chip backgrounds `rgba(10,10,10,.6)` (their text is pinned by the night scope); `.insta__veil` over photos; the `.field__error::before` and star-partial masks; `.sf-intro__mark-wrap::after` mask and shimmer; the story bottle alpha mask in dark; 00-core `.sf-intro-calm` transparent; `::view-transition-old/new` blend `normal`; the reduced-motion `backdrop-filter: none` rules; the whole print block (site.css 2670-2705); the bootstrap 500 and unconfigured pages; emails; the admin (layout, prints, `lockup-transparent` use); all `currentColor` icons (`partials/icon.php`, inline SVGs, `placeholder.php`); sample product and collection art; owner uploads; the GL context and clear colour; `groundTooBright()` logic; cards.js, quiz.js, transitions.js, micro.js and core.js (colour lives in CSS).

### 6.5 BRAND (constant in both themes)

`--grad-gold-button` (the gold fill, used inside night scopes and quiz choices), `--whatsapp-green` and the white-on-green FAB hover, `.btn--primary:active` gold-600 in dark, `favicon.ico`, `favicon-32.png`, `apple-touch-icon-180.png`, `og-default.jpg`, `logo.png` (JSON-LD, emails, admin login), and the quiz choice `::after` gold fill.

### 6.6 Audit hotspots: where each one is resolved

| Hotspot (audit) | Resolution |
|---|---|
| Logo monogram about 1.8:1; intro mark (critical) | gilt assets through `brand_asset()` (11); the shimmer mask keeps the original alpha |
| Hero scrim, opacity, header drop-shadow (critical) | daybreak ground when there is no photo; ivory veil with `--hero-img-k` over an owner photo; `--header-float-filter: none` (7.3) |
| Primary CTA text on gold (critical) | `--btn-primary-fg` champagne on an ink fill; gold plus an ink label only in night scopes (7.4) |
| Gold gradient headings, quiz sigil, quiz result (critical, site-motion) | `--grad-gold` remapped to bronze, lightest stop 3.77 (display text only); `--title-fallback` |
| Mobile glass bars (critical) | `--surface-glass` opaque paper plus a hairline (7.16) |
| Intro loader ground, mists, tag, Skip, particles (critical, runtime) | intro tokens plus the `intro.js` light path (9.4) |
| Native select options (critical) | `--option-bg` plus `color-scheme: light` |
| No color-scheme (critical, runtime) | light only in this release (10.6); dark is a follow-up |
| Black shadows (critical) | `--shadow-overlay`/`--shadow-light` remapped; `--btn-primary-shadow*`, `--shadow-hairline`, `--shadow-deep`, `--shadow-float` |
| Data-URI icons (critical, site-motion) | `--icon-chevron`, `--icon-search` URI pairs |
| Hairline tokens (critical) | `--rule*` and `--border*` remapped; `--control-line` for controls |
| Sold-out chip and filter (critical) | `--badge-sold-*`, `--sold-out-filter` |
| Stars and stock dot (critical) | `--star-empty`, `--stock-dot` (moss) |
| Raw ink tokens: breadcrumb, input border, dots, timeline, scrollbar, pagination (critical, site-motion) | `--text-faint`, `--border-control`, `--scrollbar-thumb` |
| Home rhythm band (critical, site-motion, runtime) | `t-night` in light, `t-light` kept in dark (5.3) |
| Selected and hover tints (critical) | `--tint-*`, `--border-accent-hover`, plus the double-rule selected chip |
| `--sf-mist` black page transitions (site-motion) | motion-layer light value (9.5) |
| Collection-card copy on a scrim (site-motion) | night scope; border mat; 45% scrim (7.8) |
| Hero glow, plate, WebGL additive (site-motion, runtime) | `--glow-hero` in multiply; light plates in multiply; `over` blend with the `uLight` pulse (9.2, 9.3) |
| `--grad-gold-rule` rules (site-motion) | remapped bronze; the night footer keeps champagne |
| Overlays, and nav-mobile and suggest shadows (site-motion) | `--scrim-modal` plus a 4 px blur; warm `--shadow-overlay` everywhere |
| Ivory-alpha tracks, star input, skeleton (site-motion) | `--track`, `--fill-accent`, `--star-empty`, `--skeleton-sheen` |
| Quiz reveal (site-motion) | `--quiz-line*`, `--glow-strong`, `--mist-*`, `--shadow-deep` |
| Story halo `screen` (site-motion) | `--halo-blend: multiply`; alphas through config; hue transform; mounted bottle (9.7) |
| Product-card sheen no-op (site-motion) | `--sheen-card` in `normal`; the collection sweep is split off and kept |
| Lightbox close chip (site-motion) | night scope pins the icon ivory on its ink chip |
| Nav search glyph and border (site-motion) | `--icon-search`, `--border-control` |
| `.summary__value--free` gold-300 (site-motion) | `--price-free` moss |
| Status tints (site-motion) | `--danger-quiet`, `--success-quiet` |
| Unavailable cart image (site-motion) | `--media-dim` |
| `layout.php` `<html>`, 404 (runtime) | server-rendered `data-theme` (10.2) |
| theme-color, deferred site.css (runtime) | light theme-color meta; all tokens in critical.css (10.3, 10.5) |
| `abort_plain`, maintenance; 500 stays dark (runtime) | 7.17 |
| Settings tab, `choice` type, defaults, restore allow-lists (runtime) | 12 |
| Footer lockup tile; gallery, related and sticky monogram (runtime) | `lockup-transparent-800.webp` on the night footer; `monogram-gilt-512` |
| Placeholder SVG (runtime) | `placeholder-4x5-light.svg` (8) |
| config.js and story hues, `product.php` hue map, gate.js (runtime) | 9.1, 9.7 (one transform in story.js; product.php unchanged) |
| `render-plates.mjs` forced black ground, manual webp (runtime) | ground and suffix arguments plus scripted cwebp (9.3) |
| Verification tools have no theme parameter; a11y blind spot (runtime) | owner E (14) |

---

## 7. Component rules

### 7.1 Announcement bar
Night strip `#17120D` with champagne text `#D9C8A8` (11.32:1) and a `rgba(212,176,132,.22)` hairline below. The close X and link hover follow the night tokens (ivory `#F3ECE0`). It stays dismissible exactly as today.

### 7.2 Header, and the header on scroll
- **Solid** (every page, and home after scrolling): parchment glass `rgba(246,241,232,.90)` with `saturate(1.15) blur(14px)`, a bronze `.22` bottom hairline and `--shadow-hairline` (inset white top light plus a long, soft sepia drop). This replaces the dark `rgba(0,0,0,.6)` edge.
- **Transparent** (home, over the hero): no background, no drop-shadow filter (`--header-float-filter: none`); ink icons read at 14:1 on the daybreak sky.
- Nav links are ink. Hover and `aria-current` are bronze with the existing underline draw. Cart count: a bronze disc with a vellum numeral (6.47).
- Mega panel: vellum, bronze `.22` border, warm `--shadow-overlay`.
- Monogram: the **gilt asset** (section 11), never a CSS filter.

### 7.3 Hero
- **Home, no owner photo (today's state):** daybreak sky (`--grad-hero-day`), vellum sun behind the monogram (`--grad-sun`), stone lamp edges, grain. The fallback glow is a bronze `.20` radial in `multiply`, sized as today. Eyebrow bronze, title `--grad-gold` bronze (on desktop the sweep is a champagne glint), sub walnut, primary ink, text link ink with a bronze hover.
- **Home, with an owner photo:** ivory-veil treatment. The photo shows at `min(1, admin opacity × 1.45)` (0.6 becomes 0.87) under the parchment `--scrim-full` veil, heavier at the bottom where the copy sits. Ink title, same buttons. The admin's inline `--hero-opacity` is left alone; only the multiplier token changes. If the owner's photo is dark and moody, the admin help text for Appearance advises uploading a light photo for the light theme (section 12).
- **COD seal:** `.hero__trust` is set in ink (was the lightest text above the fold). On desktop it sits between two 2 rem bronze-line rules. On phones there is one 2 rem rule above it and the line is `text-wrap: balance`, so "Rs. 3,000" never orphans (verified at 375 px).
- **Listing and collection heroes (`.hero--strip` with an image)** are night scopes: the collection's own dark sky photo as a full-bleed band with ivory type, like a window. `.hero--strip.hero--empty` gets the daybreak hero ground.

### 7.4 Buttons
| Variant | Rest | Hover | Pressed | Focus |
|---|---|---|---|---|
| Primary (day ground) | ink gradient fill, champagne label 11.91, inset champagne hairline, short sepia shadow | fill slides to its bronze end (existing `background-position` hover), champagne sheen sweeps, long bronze shadow | `#4A311A`, label 8.15 | bronze ring |
| Primary (night scopes: why-us band, footer) | brand gold gradient, ink label 8.66 (the dark theme's button) | as dark | as dark | gold ring |
| Ghost | transparent, `#9A7442` border (3.77), ink label | bronze border and label, bronze `.08` wash | | |
| Text | ink label | bronze with the existing underline draw | | |
| WhatsApp ghost | bronze-line border, ink | moss border (`--success`) | | |
| Disabled | stone fill, `#8A7F70` label | none | | |

The ink primary is the only solid dark object on a light page, so every call to action wins the eye without gold. Gold returns only where the ground is night.

### 7.5 Forms
Vellum fields (`#FEFCF8`) with a `#8F826E` border (3.67:1 on vellum, 3.34 on parchment). There is no hover change (as in the dark theme); focus is a bronze border plus the 3 px `--accent-halo`. Labels are ink, helpers walnut, placeholders `#766A58` (5.16:1; they read as hints, not as typed data). Errors are `#9E2F24` (7.10) with `--danger-halo`, and error panels use `--danger-quiet`. The checkbox is vellum; when checked it is bronze with a vellum tick. Select chevrons are bronze (6.47) via `--icon-chevron`, and native `<option>` rows are vellum with ink text. `color-scheme: light` makes the native popups, date pickers and autofill match. Inside the night footer, fields are `#211A13` with a `#7A6E5E` border (3.98 on `#0A0A0A`) and a `#A0937D` placeholder (5.70).

### 7.6 Size chips, quantity, payment choice
- Size chip: transparent on parchment with a `#8F826E` edge. Hover: bronze edge. Selected: vellum fill, ink 1 px border plus a 1 px ink inset (a double rule) and the existing bronze baseline bar. Disabled keeps the diagonal strike.
- Quantity: `#8F826E` edge (1.26:1 before), bronze `.12` press.
- Payment choice (checkout, D1 graft): selected is a vellum card with an ink border and a 3 px bronze inset on the left edge, and the dot turns ink. It is unmistakable without looking disabled (the D2/D3 taupe fills did).

### 7.7 Product cards and gallery
- Card media is a vellum mat (`--mat`: `clamp(6px, .8vw, 14px)`) with a bronze `.20` inset hairline and `--shadow-paper`. The image is inset by the mat. On hover (fine pointer) the mat lifts (`--shadow-lift`, hairline `.32`) and the existing bronze underline draws. Name is ink display, meta walnut, price ink, sale price bronze, struck price `#6B5E4E`.
- Sold out: the image fades (`grayscale(.55) sepia(.12) opacity(.78)`) rather than darkening. The chip is stone with walnut text (5.45).
- PDP gallery: the main image gets a `--mat-gallery` mat (`clamp(12px, 1.4vw, 20px)`) and the mobile slides get `--mat`. Thumbnails are fully opaque, with a 3 px vellum mount and a bronze hairline. Inactive thumbnails are desaturated (`saturate(.45)`) instead of faded, because 0.6 opacity turned them milky. The active thumbnail has an ink frame. Dots: idle `#8F826E`, active bronze.
- Zoom hint and lightbox close chip: dark chips over the photo, pinned by the night scope. The lightbox panel itself follows the day ground (parchment), which flatters bottle photography.

### 7.8 Collection cards
- Image cards are sky windows: a `--mat` vellum **border** (a border rather than a padded background, so the a11y harness still reads the dark card ground behind the ivory type), an outer bronze `.20` hairline and the paper shadow. Night tokens keep the name, tagline and rule ivory and gold.
- The scrim now ends at **45%** (`.86 → .50 at 26% → 0 at 45%`; hover `.92 → .56 → 0 at 48%`), so the top half of each collection image shows its sky colour and the tiles no longer outweigh the night band.
- If the owner later uploads light collection photography, restore the full-height scrim for those cards. The a11y harness cannot measure text over an `<img>`, so this is a manual check.
- For Him / For Her / Unisex and empty collections are horizon tiles: paper with `--grad-horizon`, a bronze-line border that goes bronze on hover, ink name, bronze rule.

### 7.9 Cart drawer and modals
- Overlay: warm `rgba(33,26,19,.30)` with a 4 px blur (none under reduced motion). The page recedes without going brown or milky.
- Drawer: a vellum sheet, bronze `.18` hairlines on the head and foot, a paper footer and the warm `--shadow-overlay`. Checkout is the ink primary and View cart is the ink text button. The added-to-cart flash uses the paper panel treatment.
- Free-delivery meter: ink `.08` track and a bronze fill (`--fill-accent`).
- The drawer scrollbar thumb is ink `.28`.
- Toast: a night chip (`#2A2118`, ivory text 14.6:1) floating on the warm light shadow.

### 7.10 Checkout
Daybreak at the top of the page and dusk under Place Order. Step numerals are bronze. The order-summary card is paper with a bronze `.18` border and paper shadow. "Free" delivery is moss (5.99 on paper): green means free, and gilt means brand. The total is ink Cormorant. The selected COD choice is shown in 7.6. The WhatsApp FAB is hidden on checkout in light (the summary already offers WhatsApp support, and the FAB covered the email field on phones).

### 7.11 Badges
| Badge | Light | Ratio |
|---|---|---|
| Sale | `#D4B084` champagne fill, ink label | 8.66; it glows against the dark renders it sits on |
| Save | ink fill, champagne label | 11.91 |
| New | vellum `.94`, bronze label, bronze `.55` border | 6.47 |
| Sold out | stone `.95`, walnut label | 5.45 |
| Generic `.badge` | bronze fill, vellum label | 6.47 |

### 7.12 Stars, reviews, meters
Filled stars are bronze (`--accent`, 5.89). Empty and partial star grounds are ink `.16` (`--star-empty`), which also fixes the invisible Write-a-review star input. Review distribution tracks and the Longevity and Sillage meters use `--track` (ink `.08`) with `--fill-accent` (bronze gradient) fills, so the rating histogram reads again (it was invisible in D1 and D2). The review summary sits on a paper panel.

### 7.13 PDP and cart trust row (D2 graft)
`.trust--row` becomes a card: a vellum-to-champagne gradient (`#FEFCF8 → #F8EFE1`), a bronze `.24` hairline and bronze icons (5.81 at the warm end). Cash on Delivery stays first. This is the biggest conversion gain available: COD reads as a promise, not fine print.

### 7.14 Footer
This is the dark theme's own footer: `#0A0A0A` ground, ivory `#F5F0E8` headings (17.45), `#9C968C` links (6.75) and the champagne `--grad-gold-rule` crown. The lockup becomes `lockup-transparent` (pale gold on transparent, served as webp), so no black tile edge shows. Newsletter: a night field and the **gold** Subscribe button (8.66), which fixes D3's 1.13:1 defect. The dusk wash above it means the page settles into night instead of hitting a wall.

### 7.15 Focus and selection
- Focus: the existing `:focus-visible` outline (same width and offset as dark) in `--focus-ring`: bronze `#7A5530` (5.89 on parchment) on the day ground, gold `#D4B084` (9.16) in night scopes. Shape is unchanged.
- Selection: `--accent` ground with `--text-inverse`: bronze with vellum text (6.47) on day, gold with night text (9.16) in night scopes. No new rule; the base `::selection` already reads these tokens.

### 7.16 Everything else
- Breadcrumb: links walnut, current ink, slash `#8F826E`.
- Pagination: links ink, hover bronze `.08`, gap `#8F826E`.
- Accordion (FAQ, PDP): bronze-tinted hairlines via `--border`, trigger hover bronze, body walnut.
- Voices, panels and summary: paper, bronze `.18` border, paper shadow (screen only; print keeps its own block).
- Skeletons: paper ground with a white `.6` sheen.
- Timeline (track page): idle dot `#8F826E`; the current dot keeps its bronze halo.
- Scent finder quiz: vellum and stone cards, bronze frames (`--quiz-line*`), bronze sigil gradient, reveal glow bronze `.28`, mist white `.85` and bronze `.20`, deck shadow sepia `.35`. Answer chips keep the brand gold fill with an ink label.
- Mobile nav: a vellum sheet with the warm overlay shadow; its search field is vellum with a `#8F826E` border and a walnut magnifier.
- Search suggest: vellum and the warm shadow.
- WhatsApp FAB: a vellum disc, bronze glyph (6.47), bronze-line border and a warm shadow. Hover stays WhatsApp green with a white glyph (brand).
- Sticky add bar (mobile PDP) and mobile filter bar: opaque paper, bronze `.24` top hairline, an upward sepia shadow, and the ink ADD button. Nothing ghosts through.
- Instagram strip: each tile gets a vellum mount (`0.75 × --mat`) with a bronze hairline, the hover veil is night `.30` with an ivory glyph, and there is 3 rem of air before the next rule.

### 7.17 Error and maintenance pages
- **404** renders through `layout.php`, so it is fully themed: daybreak, a bronze `text-gold-grad` title, the ink primary, the night footer.
- **`abort_plain()`** (`app/lib/response.php`, bare 4xx/5xx): when the theme is light, swap its three literals to ground `#F6F1E8`, text `#1E1812` and heading `#7A5530`, and add `<meta name="color-scheme" content="light">`. Settings are loaded by the time it runs.
- **Maintenance page** (`app/lib/maintenance.php`): a second palette string used when `$loaded` and the theme is light. Ground `#F6F1E8` with the sun radial as an inline `style` (CSP allows inline styles), ink text, bronze h1, the WhatsApp ghost with a `#9A7442` border, the bypass input vellum with a `#8F826E` border, the bypass button ink with a champagne label, and `.err` `#9E2F24`. It falls back to dark when settings did not load.
- **`bootstrap_render_500()` and the unconfigured page stay dark.** They can run before settings load or while the database is down, so they must not depend on a setting. One dark emergency page is acceptable.
- Emails, invoices and admin prints: unchanged.

---

## 8. Imagery policy

**Today's sample art (dark renders).** Every product, collection and Instagram image is mounted, never floated on the ground: vellum mats with bronze hairlines, like specimen plates on a daylit wall. The renders themselves are not filtered, relit or lightened. The only exceptions are the sold-out fade and the inactive-thumbnail desaturation. The collection scrim is shortened so each collection's sky shows. The story ("The Composition") bottle is mounted on a vellum plate in light instead of being feathered into the page by a radial mask. The mask made the dark render a smudge on parchment; the mount makes it a plate, and the family-hue halo now blooms behind the plate in `multiply`.

**Future real photography on white or neutral backgrounds.** The theme is built for it:

- Set `--mat: 0px` and `--mat-gallery: 0px` for packshots and keep only the inset hairline and hover lift. A white photo in a white mat would otherwise become a double frame with dead margin. The switch is a single token, and the planned hook is `html[data-theme="light"][data-photo="packshot"]`, driven by a future Store setting "Product photography: dark renders / light packshots". It is not built in this release.
- Vellum `#FEFCF8` is close enough to studio white that a packshot's edge disappears into the card. That is why the mats are vellum and not D3's cream.
- The light hero with an owner photo uses the ivory veil (7.3). For best results the owner uploads a light, airy hero image when running the light theme. The Appearance help text says so.
- If light collection photography is uploaded, the collection scrim goes back to full height (7.8).
- The placeholder art becomes `placeholder-4x5-light.svg`: a stone `#EDE5D8` ground, a bronze `rgba(122,85,48,.45)` stroke and walnut text. PHP chooses it server-side (an `<img>` cannot read tokens).

---

## 9. The motion layer on light

### 9.1 How JS learns the theme (no new inline script)
- `gate.js` (blocking, in `<head>`): add `theme: document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark'` to `window.SF_GATE`. The attribute is in the HTML source, so it exists before any script runs.
- `config.js`: `cfg.theme = (window.SF_GATE && SF_GATE.theme) || 'dark'`. Every colour that differs becomes a `{ dark, light }` pair, resolved once by a small `pick()` that returns `obj[cfg.theme] || obj.dark`. Modules keep reading plain values. The dark values are today's numbers, so the dark output is unchanged.
- `intro.js` runs before config.js, so it reads `document.documentElement.dataset.theme` directly.
- `hero.js` passes `blend: cfg.theme === 'light' ? 'over' : 'add'` into the `ribbons-gl.js` options. The module is still loaded by the existing dynamic `import()`, and nothing about loading changes.

### 9.2 WebGL ribbons (`ribbons-gl.js`, `config.js` `hero.ribbons`)
Additive light (`blendFunc(ONE, ONE)`) cannot show on a light ground: it pushes crossings toward white. In light the ribbons become **bronze silk drawn across paper**:

| Parameter | Dark (unchanged) | Light |
|---|---|---|
| blend | `gl.blendFunc(gl.ONE, gl.ONE)` | `gl.blendFunc(gl.ONE, gl.ONE_MINUS_SRC_ALPHA)` (premultiplied over; the shader already outputs `vec4(col * a, a)`) |
| `colors.gold` / `colors.amber` | `#C29C6E` / `#E0A45C` | `#8A6538` / `#5E3F20` (amber becomes a deeper bronze, not a brighter one) |
| pulse | brightens: `* (1 + pulse * w)`, clamp `min(…, uAmber * 1.15)` | deepens: add a `uLight` uniform; when it is 1, `col = max(mix(…) * (1.0 - pulse * w), uAmber * 0.85)`. With `pulse.gain 0.25`, the travelling pulse reads as ink soaking into the silk |
| `gain` | 0.6 | 0.75 (a crisper core line) |
| `halo` | scale 3.4, alpha 0.18 | scale 3.4, alpha 0.06 (a wide halo on paper is a smear) |
| `sprite.opacity` / colour | 0.35 / gold-amber mix | 0.12 / `#C9A676` |
| canvas CSS | normal | `mix-blend-mode: multiply` (block 5), so the silk takes on the paper's warmth |
| `groundMaxOpacity` guard | 0.6 | unchanged logic |

The `hero__glow::before` fallback (before WebGL is ready, or when it fails) is `--glow-hero`, a bronze `.20` radial in `multiply`, sized as today.

### 9.3 Hero plates (re-rendered with `dev-tools/render-plates.mjs`)
Today's plates are opaque gold-on-black. The light set is rendered from the light GL palette on white and shown in `multiply`, so the white drops out and only the sepia ribbons remain, and no ground colour has to match the hero gradient.

1. Extend `render-plates.mjs` with two optional CLI arguments: ground (default `#0A0A0A`) and name suffix (default empty). The script's forced `body { background: … }` uses the ground argument, and the output is `plate-{a,b}{suffix}-raw.png`.
2. Script the conversion that is manual today: `cwebp -q 82 -resize 960 0` and `-resize 540 0` for each raw PNG, into `site/assets/img/motion/ribbons-{a,b}{suffix}-{540,960}.webp`.
3. Light run: flip the local `site_theme` to `light` and delete `site/storage/cache/settings.json`, then `node dev-tools/render-plates.mjs http://127.0.0.1:PORT docs/shots/plates '#FFFFFF' -light`. This produces `ribbons-{a,b}-light-{540,960}.webp`. The dark plates are **not** re-rendered.
4. `home.php` emits the `-light` URLs in `data-plate-*` when the theme is light **and** the files exist (`is_file`). Otherwise it omits the plate attributes, so a light page never shows the dark plates.
5. Sign-off: an art-director review of the hero with motion on (not reduced) on desktop and on a Pixel-class phone, plus the `hero-gl-probe` pixel check (section 13).

### 9.4 Intro loader
It keeps its choreography and timings and changes its light. Ground `--intro-ground` (vellum sun on parchment), mists bronze `.14`/`.04` and `.12`, and the mark is the **gilt monogram asset** (`intro.php`). The shimmer mask is unchanged (alpha only), and its white-gold sweep stays (BRAND), where it reads as light catching foil. Tag bronze, Skip walnut (6.0 or better, so it stays findable). `intro.js`: when the theme is light, `fillStyle = 'rgb(122, 85, 48)'`, `globalCompositeOperation = 'source-over'` and particle alpha ×0.75, so the sparks are bronze dust settling on paper. The dark path is untouched. The intro dissolves into a parchment page, so there is no ink flash.

### 9.5 Page transitions
`--sf-mist` in light is a bronze `.10` radial over `#F6F1E8`, declared in `@layer motion` (60-transitions). View transitions and the `.sf-veil` fallback cross-fade through parchment. The black flash between pages is gone.

### 9.6 Cursor and spray
- Cursor ring: bronze via `--accent`, plus a 1 px vellum `.65` outer ring (`--cursor-outline`) so it still reads over the dark plates and renders. The grow fill is bronze at the existing opacity.
- Spray droplets: bronze with a soft bronze glow (`--spray-glow`). The add-to-cart flight dot is bronze with `--shadow-float`.

### 9.7 Story and cards
- Story ("The Composition"): the halo blends in `multiply`. Glow alpha is 0.16 and wash 0.05 in light, set through `config.js` `css['story-glow-alpha']`/`['story-wash-alpha']` per theme, because `core.js` writes those onto `<html>` inline and a stylesheet cannot override them. Hues are deepened in `story.js` with one transform applied when `cfg.theme === 'light'`: mix each hex 35% toward `#3E2914`. It covers both `config.js` families and the per-collection `$storyHueMap` data attributes from `product.php` (one source, one transform; `product.php` does not change).
- Product-card sweep: a white `.45` band in `normal` (the ivory `soft-light` sweep did nothing on vellum). The collection-card sweep over photos is unchanged.
- Quiz: all colour via the tokens in 6.3.

---

## 10. No-flash mechanism, theme-color and color-scheme

1. **One read point.** A new `site/app/lib/theme.php` (added to the bootstrap lib list after `settings`) provides `storefront_theme(): string`, which returns `'light'` only when `setting('site_theme') === 'light'`, and `'dark'` otherwise: for a missing row, a failed DB read, a blank value or any unknown value. It also provides `brand_asset(string $name): string`, which maps the chrome images to their light variants (section 11).
2. **Server-rendered attribute.** `app/views/layout.php` line 26 becomes `<html lang="en" data-theme="<?= e(storefront_theme()) ?>">`. Every storefront page and the 404 go through it. The admin layout is separate and never gets the attribute.
3. **Light tokens live in critical.css** (blocks 1-3), which is render-blocking on every page. The first paint is already light, and deferred site.css only adds below-the-fold structure (drawer, footer, collection mounts). The token block alone already turns those surfaces light, so even before site.css arrives nothing is dark.
4. **No new inline script.** The only inline script stays `document.documentElement.classList.add('js');`, so its CSP hash (`CSP_BOOTSTRAP_SCRIPT_HASH`) and the `build-zip.php` assertion keep passing. The theme needs no JS at all to paint. JS reads the attribute only to pick canvas and WebGL colours (section 9.1). Plate URLs are chosen in PHP.
5. **`<meta name="theme-color">`** (`head-meta.php` line 132): `#0A0A0A` in dark (unchanged) and `#F6F1E8` in light, so Android's browser chrome continues the parchment header. It is not the night strip, because the strip can be dismissed.
6. **`<meta name="color-scheme">`**: emitted **only in light** as `content="light"`, next to theme-color. The CSS declares `color-scheme: light` on `html[data-theme="light"]` and `dark` on night scopes. The dark theme deliberately gets no color-scheme in this release (5.2).
7. **Cache.** An admin save goes through `settings_write()`, which clears and reloads `storage/cache/settings.json`, so the next storefront request is in the new theme. A direct DB edit shows within the 300 s cache window, or at once after deleting that file. HTML is not cached elsewhere. The back/forward cache can show an old page until reload, which is acceptable.

---

## 11. Logo and monogram on light

The champagne-on-transparent marks are about 1.8:1 on a light ground. CSS filters (all three prototypes) flatten the metallic gradient into mud at 40 px. Ship real **deep-gilt assets**, generated from the existing transparent masters with ImageMagick. The recipe maps luminance through a bronze gradient and keeps the original alpha and the internal shading:

```
magick -size 1x256 gradient:'#2E1D0E'-'#A27A48' clut-gilt.png
magick IN.png \( +clone -alpha extract \) \( -clone 0 -alpha off -colorspace gray -auto-level clut-gilt.png -clut \) -delete 0 +swap -compose copy_opacity -composite OUT.png
cwebp -q 90 OUT.png -o OUT.webp
```

Verified on this machine (ImageMagick 7.1.1-47). The prototype outputs and a preview on parchment at 200 px, 56 px and 40 px are in the scratchpad at `theme/final/` (`mono-gilt-256.png`, `mono-gilt-512.png`, `lockup-gilt-800.png`, `preview2.png`). The mark holds its highlight-to-shadow modelling and reads crisply in the header at 40 px.

| Chrome use | Dark (unchanged) | Light |
|---|---|---|
| Header logo `<picture>` (`partials/header.php` 56-57) | `brand/monogram-transparent-256.webp/.png` | `brand/monogram-gilt-256.webp/.png` |
| Hero object default (`views/home.php` 24, when the `hero_object` setting is empty) and intro `<img>` (`partials/intro.php` 8) | `monogram-transparent-256.webp` | `monogram-gilt-256.webp`. An owner-uploaded hero object is shown as uploaded. The intro shimmer mask `--sf-intro-mark` (`intro.php` 7) keeps the original file, because it uses alpha only |
| Gallery placeholder, related fallback, sticky-bar thumb (`gallery.php` 7, `related.php` 8, `product.php` 31) | `monogram-transparent-512.png` | `monogram-gilt-512.png` |
| Footer (`footer.php` 51) | `lockup-on-black-800.webp` | `lockup-transparent-800.webp` (new webp of the existing pale-gold transparent lockup; the footer is night, so pale gold is right and no tile edge shows) |
| Product placeholder (`product-card.php` 43, 51; `controllers/product.php` 41) | `placeholder-4x5.svg` | `placeholder-4x5-light.svg` |
| `lockup-gilt-800` | n/a | generated for completeness (the light footer does not need it; it is kept for future light surfaces such as a light email header) |
| Favicons, apple-touch, OG image, `logo.png`, emails | unchanged | unchanged (BRAND: off-site) |

All of these go through `brand_asset()`, so the templates carry no theme conditionals of their own.

---

## 12. The admin control: Settings → Appearance

- **Tab:** add `'appearance' => 'Appearance'` to `SETTINGS_TABS` in `site/admin/controllers/settings.php`, directly after `store`. No new route (`admin.settings` handles `?tab=`). Capability: the existing `admin-settings`.
- **Definition:** `'site_theme' => ['appearance', 'choice', 'Storefront theme', ['options' => ['dark' => 'Dark', 'light' => 'Light'], 'help' => 'Dark is the default and the original look. Light switches the whole shop, for every visitor, straight after you save. The admin panel keeps its own look. With Light, a light and airy homepage photo looks best.']]`.
- **Validation:** a new `case 'choice':` in `settings_clean()`. It accepts only keys of `$def['options']`; a blank value means the default (`dark`); anything else returns an error. Without this case the type would fall through to `[null, null]` and save an empty string.
- **Default:** `'site_theme' => 'dark'` in `settings_defaults()` (`app/lib/settings.php`). Optionally, `db/seed.sql` gets `INSERT IGNORE … ('site_theme','dark','appearance',NULL)`. **The dark theme stays the default** for a fresh install, a missing row and a failed read.
- **Control:** `admin/views/settings.php` maps `'choice' => 'segment'` and passes `options`. This is a single two-option segmented toggle (the existing `.adm-segment` radio pills, with `role="radiogroup"` and arrow-key support). Each option carries a small visual swatch, drawn in admin.css only:
  - **Dark**: a 56×36 tile of `#0A0A0A` with a 3 px `#D4B084` bar and an ivory `#F5F0E8` line.
  - **Light**: a 56×36 tile of `#F6F1E8` with a pearl-to-peach top wash, a `#1E1812` bar and a `#7A5530` line.
  - The swatch sits above the label inside the pill. The selected pill gets the admin's existing selected style. The swatches are `aria-hidden`; the labels "Dark" and "Light" carry the meaning.
- **Saved like every other setting:** `settings_write($changes, 'appearance')` upserts, clears and reloads the cache, and `settings_log()` records `Settings changed (Appearance): site_theme dark → light` in the activity log automatically.
- **Never restorable as wording:** `site_theme` must not be added to `SETTINGS_WORDING_KEYS` (admin controller line 13) or `COPY_SETTINGS_ALLOW` (`dev-tools/extract-default-copy.php` line 8). The Appearance tab therefore shows no "Restore default wording" button, and restoring wording elsewhere can never flip the shop's look.
- Out of scope, noted for later: a "Preview light" link for admins before saving (it needs an admin-session-gated override on the storefront).

---

## 13. Acceptance criteria

All criteria run against a local server, first with `site_theme = dark` and then with `site_theme = light` (switch with `dev-tools/theme-switch.php`, section 14 E). Both runs must pass before merge.

1. **The dark theme is pixel-identical to the baseline** in the scratchpad at `theme/baseline-dark/` (17 routes × desktop 1440 and mobile 375, same tour settings as the baseline `report.json`), apart from animation noise. `dev-tools/theme-diff.mjs` masks the hero canvas and plates, the intro and the cursor, then requires 0 differing pixels outside the masks at a per-channel tolerance of 2. As a second guard, it compares computed `color`, `background-color`, `border-color`, `box-shadow`, `filter` and `background-image` for every element on home, PDP, shop and checkout against a pre-change capture, and requires them to be identical.
2. **Admin unchanged:** admin login, dashboard, orders and settings pages are pixel-identical before and after, with both theme values. Admin `<html>` never carries `data-theme`.
3. **Light contrast:** `a11y.mjs` with `EXPECT_THEME=light` reports 0 text contrast failures (below 4.5:1, or 3:1 for large text) on every page it measures, 0 missing focus indicators, and the same tab order as dark. Non-text: every control border is at least 3:1 (selects, qty, chips, fields, ghost buttons), which the token table guarantees.
4. **No horizontal overflow at 375 px** in either theme (tour.mjs overflow report is 0 on all routes).
5. **`motion-check.mjs` passes in both themes** (desktop-1440, mobile-360, reduced-1440 over its 6 pages), with its new assertion that `document.documentElement.dataset.theme` matches the expected theme.
6. **Zero console errors** in both themes across tour, motion-check, intro-check and hero-gl-probe.
7. **Lighthouse performance within 2 points of dark** (mobile preset; home, `/shop`, `/product/azure-oud`; median of 3 runs each). The grain is restricted to two bands and there is no filter on any image, so no regression is expected.
8. **The setting switches the live look:** saving Appearance changes the next storefront response at once, because `settings_write()` clears the cache. A direct DB edit shows within the 300 s settings cache, or at once after deleting `storage/cache/settings.json`. Switching back to Dark restores criterion 1 exactly.
9. **No flash:** the raw HTML response carries `data-theme` on `<html>`. With site.css and motion.css delayed by 3 s (request interception), a screenshot at first paint of home, listing and PDP shows the parchment ground, a light header and ink text.
10. **CSP intact:** `dev-tools/build-zip.php` passes: exactly one inline script, the hash is unchanged, and no inline script anywhere else.
11. **Settings safety:** `site_theme` is absent from `SETTINGS_WORDING_KEYS` and `COPY_SETTINGS_ALLOW`, and the Appearance tab shows no "Restore default wording". Posting an unknown value is rejected with a message, and a blank value saves `dark`. The activity log records the change.
12. **Hero on light:** `hero-gl-probe.mjs` (light) shows the ribbon canvas present and its sampled ribbon pixels darker than the ground (no pale holes). The network log shows `ribbons-*-light-*.webp` and no dark plate URL. A signed-off art review of the hero with motion on (desktop and a Pixel-class phone) is attached to the PR.
13. **Intro and transitions on light:** `intro-check.mjs` frames show a parchment ground, bronze sparks and a Skip link at 4.5:1 or better. A mid-transition desktop screenshot has a mean luminance above 0.8, so it never flashes black.
14. **Every judge must-fix is visibly resolved** (screenshot checklist in the PR): sort select chevron visible and border at least 3:1; select chevron present on contact; review tracks and meters visible; the Write-a-review stars visible; In stock and Free in moss; placeholder hint tone; opaque sticky and filter bars with no ghosting; champagne Sale badge; warm drawer scrim with a crisp panel edge; hero COD line in ink with seal rules and no orphan at 375; FAB hidden on light checkout; Composition bottle mounted (no smudge); collection tiles mounted with a 45% scrim; Instagram tiles mounted with air before the rule; dusk wash into the footer with no hard cut; footer Subscribe gold; gilt header monogram crisp at 40 px; footer lockup with no tile edge.
15. **House rules:** no comments added in any file; `motion.css` byte-equals `cat motion/*.css`; print output is unchanged in both themes; nothing was committed or pushed without the owner's approval.

---

## 14. Build plan: five owners, non-overlapping files

Each file has exactly one owner. Owners write against this spec and `light-tokens.css`, not against each other's work in progress. Everyone follows the house rules: no comments in code, chunked edits, no commits or pushes without the owner's approval, and a server started on your own port and stopped afterwards.

### A. Tokens and first paint
- **Files:** `site/assets/css/critical.css` (only this file).
- **Scope:** insert blocks 1-2 of `light-tokens.css` into `@layer tokens` after `.t-light`, and block 3 at the end of `@layer components`. Apply every conversion in 6.1 using the fallback pattern (5.2). Do not change any value in `:root`, `.t-light`, the font faces or the layer order. Use `background-color`, never the `background` shorthand, on fields.
- **Done when:** criterion 1 holds for the dark computed-style capture of critical-owned selectors, and a light first-paint screenshot (criterion 9) is correct with site.css blocked.

### B. Deferred components
- **Files:** `site/assets/css/site.css` (only this file).
- **Scope:** block 4 of `light-tokens.css` at the end of the components layer; every conversion in 6.2. Leave the `.t-light` patch block (2570-2627) and the print block untouched. Restate state rules wherever an override touches a stateful property (hover scrim, reduced-motion overlay).
- **Done when:** the dark computed-style diff for site-owned selectors is clean, and drawer, checkout, footer, collection mounts, Instagram mounts and reviews match the verification render.

### C. Motion layer, plates and intro
- **Files:** `site/assets/css/motion/00-core.css` … `60-transitions.css`, `site/assets/css/motion.css` (regenerated only, with `cat motion/*.css > motion.css`), `site/assets/js/motion/gate.js`, `config.js`, `ribbons-gl.js`, `hero.js`, `story.js`, `site/assets/js/intro.js`, `dev-tools/render-plates.mjs`, and the new `site/assets/img/motion/ribbons-{a,b}-light-{540,960}.webp`.
- **Scope:** block 5 of `light-tokens.css` split into the parts; every conversion in 6.3; section 9 in full (theme plumbing, the GL blend and `uLight` path, light config pairs, intro particles, story hue transform, plate pipeline). Render the light plates **last**, once A, B and D's `layout.php`/`home.php` changes are in the tree.
- **Done when:** criteria 5, 12 and 13 pass, dark motion frames are unchanged (criterion 1 masks only the animated regions), and `motion.css` byte-equals the concatenation.

### D. PHP runtime, admin Appearance tab, brand assets, standalone pages
- **Files:** new `site/app/lib/theme.php`; `site/app/bootstrap.php` (lib list only); `site/app/views/layout.php`; `site/app/partials/head-meta.php`, `header.php`, `footer.php`, `intro.php`, `gallery.php`, `related.php`, `product-card.php`; `site/app/views/home.php` (the why-us class, the plate URLs, the hero-object default); `site/app/views/product.php` (line 31 only); `site/app/controllers/product.php`; `site/app/lib/response.php`; `site/app/lib/maintenance.php`; `site/app/lib/settings.php`; `site/admin/controllers/settings.php`; `site/admin/views/settings.php`; `site/admin/partials/field.php` (only if the segment markup needs a hook for the swatch); `site/assets/css/admin.css` (swatches only); `site/db/seed.sql` (optional row). New assets: `site/assets/img/brand/monogram-gilt-256.png/.webp`, `monogram-gilt-512.png`, `lockup-transparent-800.webp`, `lockup-gilt-800.png/.webp`, `site/assets/img/placeholder-4x5-light.svg`.
- **Scope:** sections 7.17, 10, 11 and 12. `storefront_theme()` and `brand_asset()` are the only theme reads in PHP. `home.php` emits `t-night` in light and `t-light` in dark, and emits light plate URLs only when those files exist. Generate the gilt assets with the recipe in section 11.
- **Done when:** criteria 8, 10 and 11 pass, the admin is unchanged (criterion 2), the 404, `abort_plain` and maintenance pages render in both palettes, and the 500 page stays dark.

### E. Verification tooling and release gate
- **Files:** `dev-tools/tour.mjs`, `motion-check.mjs`, `a11y.mjs`, `intro-check.mjs`, `hero-gl-probe.mjs`, `hero-mobile-probe.mjs`, `dev-tools/README.md`, the new `dev-tools/theme-switch.php`, `dev-tools/theme-diff.mjs` and `dev-tools/lighthouse-theme.mjs`, and `site/dev/zip-manifest.php` (add every new file above to `required_files`).
- **Scope:**
  - `theme-switch.php dark|light` sets `site_theme` in the local DB (skyfragrances_dev) and deletes `storage/cache/settings.json`. It refuses to run against anything but a local DSN.
  - `TOUR_THEME` / `EXPECT_THEME` labels and theme assertions go into every probe. Output file names carry `--light` so dark and light sets live side by side.
  - `a11y.mjs` also reports elements whose text sits over an `<img>` sibling (collection cards, hero), listed for manual review rather than scored.
  - `hero-gl-probe` gets a pixel sample of the ribbon canvas.
  - `theme-diff.mjs` implements criterion 1 (the pixel diff with masks, plus the computed-style capture).
  - `lighthouse-theme.mjs` implements criterion 7.
- **Done when:** E runs the full criteria list 1-15 against the integrated tree in both themes and publishes the report. E owns the go/no-go.

**Sequencing:** A, D and E start at once. B and C start at once against the spec. C's plate render and E's final gate run last. Integration conflicts are impossible by construction, because no file appears under two owners.

---

## 15. Follow-ups (not part of this build)

- Add `color-scheme: dark` (CSS and meta) to the dark theme. This is a visible change to the dark scrollbars and native popups, so it needs its own approval and baseline.
- Convert the site.css `.t-light` patch block to tokens, which unifies the admin and the dark ivory band. Verify the admin pixel by pixel.
- WhatsApp FAB overlap on the FAQ toggles and the checkout email field in the dark theme (hidden on light checkout only in this build).
- Pre-existing bug: cart drawer lines render no product name in either theme.
- Pre-existing bugs: `intro.php` reads `setting('tagline')` instead of `store_tagline`, and the `favicon_path` setting is never used.
- A "Product photography" setting that drives `data-photo="packshot"` and sets `--mat: 0`.
- An admin-only "Preview light" link.
