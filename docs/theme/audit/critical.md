# Colour audit: `site/assets/css/critical.css`

Source read in full: 2,872 lines, audited 2026-10-01 against the working tree. Read-only audit; nothing under `site/` was changed.

## How to read this

| Class | Meaning |
|---|---|
| **THEME** | Must follow the site theme. Today it is either a raw literal, a raw palette token, or a value that silently assumes a dark ground. Proposed token given. |
| **KEEP** | Sits on photography, an image scrim, or a deliberately dark element. Stays identical in both themes (sometimes with its text pinned so it does not flip). |
| **BRAND** | Constant brand colour (gold fill, WhatsApp green) that reads on both grounds. No theme variant. |

Counting rule: every raw literal, raw palette use, gradient, shadow, filter, data-URI and dark-assumption in the file is one row. The 256 uses of already-semantic tokens (`var(--surface)`, `var(--text)`, `var(--border)`, `var(--accent)` and friends) are NOT listed row by row; they flip automatically once the theme redefines the semantic layer. Where one of those semantic uses is still wrong in light (usually `--text-inverse` on a gold fill, or `--text-muted` on a dark chip) it is listed.

Proposed light values are a first cut for the prototype task; contrast figures are approximate WCAG ratios against ivory-100 `#F5F0E8` unless stated.

Totals: **THEME 54 · KEEP 8 · BRAND 9** (plus 26 palette primitives that are definitions, not decisions).

---

## 1. Palette primitives (lines 5-33): definitions, no change

These are the raw ramps. They do not change between themes; only the semantic layer that points at them does. Listed so nobody "fixes" them.

| Line | Token | Value | Note |
|---|---|---|---|
| 5-11 | `--ink-900..400` | `#0A0A0A` `#0F0E0D` `#141312` `#1C1A18` `#26231F` `#35302A` `#4A443C` | Dark ramp. Used directly (bypassing semantics) at 823, 1088, 1123, 1504. |
| 13-20 | `--gold-100..800` | `#F2E6D2` ... `#7A5A2E` | Gold ramp. 100-400 are "gold on ink"; 600-800 are "gold on ivory". The light theme must lean on 600-800 for anything that is text, a line or an icon. |
| 22-27 | `--ivory-050..500` | `#FBF8F3` ... `#5A544B` | Light ramp. Missing a true "paper white"; `.t-light` hardcodes `#FFFFFF` (167). Proposed: add `--ivory-000: #FFFDF9` (warm white; pure white looks clinical next to ivory). |
| 29-32 | `--success/danger-on-dark/light` | `#6FBF8B` `#1F6B3E` `#E5736B` `#A32B22` | Already paired per ground. Good pattern to copy. |
| 33 | `--whatsapp-green` | `#25D366` | BRAND (counted below at 2582). |

Missing primitive: `#8F877B` is hardcoded at 172 as light `--text-disabled`. Promote to `--ivory-450`.

---

## 2. Token layer (`:root`, lines 35-137)

| Line | Selector | Property | Value | Class | Proposed token / light value | Note |
|---|---|---|---|---|---|---|
| 35 | `:root` | `--rule-faint` | `rgba(245,240,232,0.08)` | THEME | stays behind `--border-faint`; light `rgba(10,10,10,0.06)` | Ivory-alpha hairline: invisible on ivory. Only reach it through `--border-faint`. |
| 36 | `:root` | `--rule` | `rgba(245,240,232,0.14)` | THEME | `--border`; light `var(--ivory-200)` or `rgba(10,10,10,0.10)` | Classic "white-alpha border assumes dark". |
| 37 | `:root` | `--rule-strong` | `rgba(212,176,132,0.38)` | THEME | `--border-accent`; light `rgba(138,101,56,0.45)` | Gold-400 at 38% on ivory is about 1.3:1, reads as a smudge. `.t-light` uses solid gold-700, which is heavier than the dark look; a 45% gold-700 keeps the same whisper. |
| 41 | `:root` | `--scrim-bottom` | ink gradient 0.86 to 0 | KEEP | `--scrim-bottom` unchanged | Sits on photography (collection/story cards in site.css) with ivory text on top. Those texts must be pinned (see risks). |
| 42 | `:root` | `--scrim-full` | ink gradient 0.55/0.35/0.70 | THEME | new `--scrim-hero`; light `linear-gradient(to bottom, rgba(245,240,232,0.70) 0%, rgba(245,240,232,0.35) 45%, rgba(245,240,232,0.85) 100%)` | Only consumer is `.hero__scrim` (1602). Hero text uses `--text`, so either the scrim flips with it or the hero is pinned dark. Recommendation in section 6. |
| 43 | `:root` | `--scrim-modal` | `rgba(10,10,10,0.72)` | THEME | `--scrim-modal`; light `rgba(26,22,17,0.38)` plus `backdrop-filter: blur(6px)` at the consumer | 72% ink over an ivory page is a black-out, not a veil. Also used by `admin.css:87` and site.css 619/1087: admin must not change, see risks. |
| 44 | `:root` | `--scrim-hover` | `rgba(10,10,10,0.18)` | KEEP | unchanged | Darkens a photo on hover; works on any image. |
| 46 | `:root` | `--grad-gold` | champagne 6-stop, peaks at `#F2E6D2` | THEME | new `--grad-gold-text`; light `linear-gradient(118deg, #7A5A2E 0%, #A87F4E 30%, #C29C6E 48%, #A87F4E 66%, #7A5A2E 100%)` | Only used as text fill (`.text-gold-grad`, 408). The 38% stop is near-ivory: on an ivory page the middle of every gold heading vanishes. |
| 47 | `:root` | `--grad-gold-rule` | fades through `rgba(242,230,210,0.85)` | THEME | `--grad-gold-rule`; light peak `rgba(138,101,56,0.55)`, shoulders gold-600 at 0.10 | Centre highlight is lighter than ivory, so the rule looks broken in the middle. site.css `.t-light .divider` papers over it with a flat line and loses the shimmer. |
| 48 | `:root` | `--grad-gold-border` | gold 5-stop | BRAND | unchanged | Dark ends (`#8A6538`) anchor it on both grounds. No consumer found in any CSS or PHP: dead token. |
| 49 | `:root` | `--grad-gold-sheen` | `rgba(242,230,210,0.32)` band | THEME | `--grad-gold-sheen`; light `rgba(255,253,249,0.65)` band on filled buttons, `rgba(168,127,78,0.10)` on ghost | Sweeps across every `.btn` on hover (877). Pale-on-pale is invisible on ghost buttons in light. Consider splitting into `--sheen-on-fill` and `--sheen-on-ground`. |
| 50 | `:root` | `--grad-gold-button` | gold 4-stop | BRAND | unchanged | Gold fill with ink text works on both. On ivory the button edge is only about 1.6:1 against the page, so the light theme needs `--shadow-lift` or a gold-700 hairline under it. |
| 51 | `:root` | `--grad-gold-radial` | gold 0.14 to 0.04 to transparent ink | THEME | `--glow-ambient`; light `radial-gradient(ellipse 70% 55% at 50% 40%, rgba(194,156,110,0.16) 0%, rgba(194,156,110,0.05) 45%, rgba(245,240,232,0) 75%)` | A gold glow reads as light on ink; on ivory it needs to be a warmer, slightly darker tint (like sun through a bottle). Consumers: 433, 1604, site.css 602. |
| 53-75 | `:root` | semantic block | `--surface` ... `--price-was` | THEME | already semantic; light values = `.t-light` block (164-187) with the tweaks below | This is the lever. A site theme is one more block that redefines these 23 tokens, not a rewrite of components. |
| 68-70 | `:root` | `--accent-quiet`, `--accent-halo`, `--danger-halo` | gold/red literals | THEME | already semantic; light values exist at 180-182 | Fine. |
| 135 | `:root` | `--glow-accent` | `0 0 0 1px rgba(212,176,132,0.45), 0 8px 32px rgba(212,176,132,0.10)` | THEME | `--glow-accent`; light `0 0 0 1px rgba(138,101,56,0.35), 0 10px 30px rgba(168,127,78,0.18)` | A light-gold glow on ivory is invisible; the halo needs a warmer, denser tint to register. |
| 136 | `:root` | `--shadow-light` | `0 1px 2px rgba(10,10,10,.06), 0 8px 24px rgba(10,10,10,.08)` | THEME | rename `--shadow-soft`; dark `none` (or an ink hairline), light as today but tinted `rgba(40,28,12,…)` | Only used under `.t-light` in site.css. Neutral black shadows on warm ivory look grey; brown-tinted shadows look like paper. |
| 137 | `:root` | `--shadow-overlay` | `0 24px 64px rgba(0,0,0,0.55)` | THEME | `--shadow-overlay`; light `0 2px 6px rgba(40,28,12,0.06), 0 28px 64px rgba(40,28,12,0.16)` | Used by mega-menu (2118) and five site.css overlays (drawer, modal, toast...). 55% black on ivory is a smear. |

### `.t-light` block (lines 164-187)

| Line | Token | Value | Class | Note |
|---|---|---|---|---|
| 167 | `--surface-overlay` | `#FFFFFF` | THEME | Literal. Use new primitive `--ivory-000: #FFFDF9`; pure white fights the warm ivory. |
| 172 | `--text-disabled` | `#8F877B` | THEME | Literal. Promote to `--ivory-450`. About 3.1:1 on ivory-100: OK for disabled. |
| 175 | `--border-faint` | `rgba(10,10,10,0.06)` | THEME | Fine as a light value. |
| 176 | `--border-accent` | `var(--gold-700)` | THEME | Solid gold-700 is heavier than the dark-mode 38% whisper. Suggest `rgba(138,101,56,0.45)`. |
| 177-179 | `--accent*` | gold-800 / gold-700 / gold-800 | THEME | Correct instinct (gold-800 on ivory is about 5.0:1). Hover goes lighter (gold-700), which is the reverse of dark mode's "hover brightens"; fine, but make it deliberate. |
| 186 | `--price` | `var(--ink-900)` | THEME | Price turns ink in light. Luxury-correct (gold prices on ivory look cheap); keep. |

Missing from `.t-light` and needed for a site-wide light theme: `color-scheme: light`, `--scrim-modal`, `--shadow-overlay`, `--glow-accent`, `--grad-gold-*` light variants, `--on-accent` (see 899), `--surface-glass` (see 1386), `--border-control` (see 1088), `--icon-chevron` (see 1116). Without them the tokens flip but the effects stay dark.

---

## 3. Base layer (lines 285-414)

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 286-292 | `html`, `body` | background / color | `--surface` / `--text` | THEME (semantic, OK) | none | Flips cleanly. |
| n/a | `:root` / `html` | `color-scheme` | **absent** | THEME | add `color-scheme: dark` to `:root`, `light` in the light theme | Nothing in critical.css or site.css declares it. Native scrollbars, `<select>` popups, date pickers, autofill and form controls follow the OS, not the site. Today the dark site already gets light scrollbars; a light theme without it will get dark ones on dark-mode Macs. |
| 343-346 | `::selection` | background / color | `--accent` / `--text-inverse` | THEME (semantic, OK) | none | In light: gold-800 with ivory text, about 5:1. site.css 2623 duplicates this for `.t-light`; the duplicate can go once the tokens are right. |
| 347-350 | `::placeholder` | color | `--text-muted` | THEME (semantic, OK) | none | ivory-500 on white field: about 7:1. |
| 351-354 | `:focus-visible` | outline | `--focus-ring` | THEME (semantic, OK) | none | gold-800 in light: good. |
| 407 | `.text-gold-grad` | color (fallback) | `var(--gold-400)` | THEME | `--accent` | Fallback when `background-clip:text` is unsupported; gold-400 on ivory is about 1.9:1. |
| 408 | `.text-gold-grad` | background-image | `var(--grad-gold)` | THEME | `--grad-gold-text` | See line 46. Also used by motion/50-quiz.css. Headline hotspot. |

---

## 4. Layout layer (lines 416-667)

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 429 | `.section--raised` | background | `--surface-raised` | THEME (semantic, OK) | none | In light: ivory-050, a gentle lift. |
| 433 | `.section--glow` | background-image | `var(--grad-gold-radial)` | THEME | `--glow-ambient` | See 51. |
| 502, 646 | `.rail`, `.listing__filters` | scrollbar-width | none / thin | THEME | `scrollbar-color: var(--border-accent) transparent` + `color-scheme` | No colour set in critical; site.css 709 uses `var(--ink-500)`, a raw dark token (near-black thumb in light is harsh). |
| 527-528 | `.skip-link` | bg / color | `--accent` / `--text-inverse` | THEME (semantic, OK) | none | |
| 549-552 | `.band--ivory` | bg / color | `--surface` / `--text` | THEME | rename `.band--contrast` | Named "ivory" but only ivory when a `.t-light` parent exists. In a light site it is ivory-on-ivory. Name describes a colour, not a role. |
| 565 | `.divider` | background | `var(--grad-gold-rule)` | THEME | `--grad-gold-rule` (light variant) | See 47. |
| 568 | `.divider--plain` | background | `--border` | THEME (semantic, OK) | none | |

---

## 5. Components (lines 669-2604)

### Announcement, badges, breadcrumb (670-843)

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 674-676 | `.announcement` | bg / color / border | semantic | THEME (semantic, OK) | none | In light: ivory-050 strip. Consider `--surface-sunken` (ivory-200) so the bar still reads as a band above a light header. |
| 732-733 | `.badge` | color / bg | `--text-inverse` / `--accent` | THEME (semantic, OK) | none | Light: ivory text on gold-800, about 5:1. |
| 746 | `.badge--sold-out` | background | `rgba(10,10,10,0.82)` | THEME | new `--badge-veil`; light `rgba(251,248,243,0.90)` | Sits on the product photo (`.badge-stack` is absolute over media). |
| 747 | `.badge--sold-out` | color | `var(--text-muted)` | THEME | pair with `--badge-veil`: `--on-badge-veil` | **Bug in light:** text flips to ivory-500 while the chip stays 82% ink: about 2:1. site.css `.t-light` patches it; a token pair fixes it for good. |
| 823 | `.breadcrumb__item::after` | color | `var(--ink-500)` | THEME | new `--text-faint`; dark ink-500, light ivory-300 | Raw dark token. In light the "/" separator turns near-black and louder than the links. |

### Buttons (845-1055)

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 877 | `.btn::before` | background-image | `var(--grad-gold-sheen)` | THEME | `--grad-gold-sheen` light variant | See 49. |
| 899 | `.btn--primary` | color | `var(--text-inverse)` | THEME | new `--on-accent-fill: var(--ink-900)` in both themes | **Bug in light:** text-inverse becomes ivory on a pale gold gradient, about 1.7:1. The fill is BRAND and constant, so the text on it must be constant too. site.css `.t-light .btn--primary` already forces ink, which proves the point. |
| 904 | `.btn--primary` | box-shadow | `0 1px 2px rgba(0,0,0,0.6)` | THEME | new `--shadow-press`; light `0 1px 2px rgba(60,40,15,0.18)` | 60% black edge on ivory draws a dirty line under every CTA. |
| 907 | `.btn--primary:hover` | color | `var(--text-inverse)` | THEME | `--on-accent-fill` | Same as 899. |
| 910 | `.btn--primary:active` | background-color | `var(--gold-600)` | BRAND | unchanged | Solid gold press state; ink text on gold-600 about 6:1, works on both. |
| 1004 | `.btn--primary.is-loading` | background-image | `var(--grad-gold-button)` | BRAND | unchanged | |
| 1005 | `.btn--primary.is-loading` | color | `var(--text-inverse)` | THEME | `--on-accent-fill` | Spinner uses currentColor, so an ivory spinner on gold in light. |
| 1027 | `.btn--primary:hover` | box-shadow | `0 12px 32px rgba(0,0,0,0.55), var(--glow-accent)` | THEME | new `--shadow-lift`; light `0 14px 30px -10px rgba(122,90,46,0.40), 0 2px 6px rgba(40,28,12,0.10)` | The hover "lift" on the hero CTA. On ivory, a gold-tinted drop shadow looks like the bottle is casting light: the moment to make beautiful. |
| 1032 | `.btn--ghost:hover` | background-color | `rgba(212,176,132,0.06)` | THEME | `--accent-quiet` | Literal duplicate of an existing token idea. |
| 1039 | `.btn--danger:hover` | background-color | `rgba(229,115,107,0.08)` | THEME | `--danger-halo` or new `--danger-wash` | Tinted from danger-on-dark; on ivory use danger-on-light. |

### Fields, filters, toolbar (1057-1398)

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 1087 | `.field__input` | background | `--surface-sunken` | THEME (semantic) | light: `--ivory-000` field on ivory page | site.css `.t-light` forces `#FFFFFF`. White fields on ivory are the right look; express as a token (`--field-bg`). |
| 1088 | `.field__input` | border | `var(--ink-500)` | THEME | new `--border-control`; dark ink-500, light ivory-300 | Raw dark token. In light: near-black box around every input. |
| 1090 | `.field__input` | caret-color | `--accent` | THEME (semantic, OK) | none | |
| 1116 | `.field__input--select` | background-image | data-URI chevron `stroke='%23D4B084'` | THEME | new `--icon-chevron` (two data URIs, one per theme) or switch to a masked pseudo-element in `currentColor` | Gold-400 stroke on a white field: about 1.9:1. Data URIs cannot read CSS variables. |
| 1123 | `.field__input--select option` | background | `var(--ink-700)` | THEME | `--surface-overlay` | **Bug in light:** option text is `--text` (ink) on an ink-700 background: invisible native dropdown rows. |
| 1133 | `.field__input--search` | background-image | data-URI magnifier `stroke='%239C968C'` | THEME | `--icon-search` pair or mask + `--text-muted` | Ivory-400 is mid-grey; readable on both (about 2.7:1 on ivory) but not tuned. |
| 1178-1179 | `.field__error::before` | mask | data-URI `stroke='%23000'` | KEEP | none | Mask only: colour comes from `currentColor` (`--danger`). Theme-proof. |
| 1184 | `.field.has-error .field__input` | box-shadow | `--danger-halo` | THEME (semantic, OK) | none | |
| 1216-1217 | `.field__checkbox::after` | border | `--text-inverse` | THEME (semantic, OK) | none | Tick on accent fill; in light ivory on gold-800, fine. |
| 1364 | `.toolbar__select` | background-image | data-URI chevron `%23D4B084` | THEME | `--icon-chevron` | Same as 1116. |
| 1386 | `.filter-bar` | background | `rgba(10,10,10,0.94)` | THEME | new `--surface-glass`; light `rgba(251,248,243,0.92)` + blur | Mobile bottom bar on listing pages. **In light:** an ink slab at the bottom of an ivory page while its buttons flip to light-theme colours. |

### Gallery, hero, placeholder (1420-1739)

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 1429, 1473, 1518 | `.gallery__main/__rail/__thumb` | background | `--surface-raised` | THEME (semantic, OK) | none | Letterbox behind PDP images; in light ivory-050. Product shots on white/ivory studio backgrounds will blend nicely. |
| 1453 | `.gallery__zoom-hint` | color | `var(--text-muted)` | KEEP (pin) | new `--on-image-muted: var(--ivory-300)` constant | Text on a dark chip over the photo. Must not flip: in light it would be ivory-500 on 60% ink. |
| 1454 | `.gallery__zoom-hint` | background | `rgba(10,10,10,0.6)` | KEEP | unchanged | Chip over photography. |
| 1504 | `.gallery__dot::before` | background | `var(--ink-500)` | THEME | `--text-faint` or `--border-control` | Inactive dots become near-black in light; should be ivory-300. |
| 1575-1576 | `.hero` | color / bg | `--text` / `--surface` | THEME (semantic) | see section 6 | Flips, which drags the photo scrim decision with it. |
| 1596 | `.hero__img` | opacity | `var(--hero-opacity, 0.6)` (inline per page: home setting, listing 0.55) | THEME | new `--hero-image-opacity`; light around 0.85-0.9 | Not a colour but the biggest colour effect on the page: the photo is blended with `--surface`. At 0.6 over ink it looks moody; at 0.6 over ivory it looks washed out and milky. The inline style on home.php / listing.php overrides the fallback, so the light value must be applied via a multiplier or a separate admin setting. |
| 1602 | `.hero__scrim` | background | `var(--scrim-full)` | THEME | `--scrim-hero` | See 42. |
| 1604 | `.hero--empty` | background-image | `var(--grad-gold-radial)` | THEME | `--glow-ambient` | No-image hero fallback. |
| 1619, 1658 | `.hero__eyebrow`, `.hero__cue` | color | `--accent` | THEME (semantic, OK) | none | gold-800 on ivory veil: fine. |
| 1735 | `.placeholder__mark` | color | `rgba(212,176,132,0.28)` | THEME | new `--accent-ghost`; light `rgba(122,90,46,0.22)` | Monogram watermark in empty image slots. |

### Price, product card, qty (1741-1972)

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 1750-1778 | `.price*` | color | `--price`, `--text`, `--price-was`, `--accent` | THEME (semantic, OK) | none | Light: ink prices, gold-800 sale, ivory-500 struck-through. |
| 1788-1803 | `.product-card*` | bg / border | semantic | THEME (semantic, OK) | none | |
| 1873 | `.product-card.is-sold-out .product-card__img` | filter | `grayscale(0.35) brightness(0.72)` | THEME | new `--sold-out-filter`; light `grayscale(0.6) contrast(0.9) brightness(1.04) opacity(0.72)` | Darkening a photo to 72% reads as "dimmed" on ink but as "dirty" on ivory. Light needs "faded", not "dimmed". |
| 1933 | `.qty__btn:active` | background | `rgba(212,176,132,0.14)` | THEME | `--accent-quiet` | Literal. |

### Site header and mega menu (1974-2224)

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 1979-1981 | `.site-header` | bg / color / border | semantic | THEME (semantic, OK) | none | |
| 1990 | `.site-header.is-transparent …` | filter | `drop-shadow(0 1px 8px rgba(0,0,0,0.6))` | THEME | new `--header-float-shadow`; light `drop-shadow(0 1px 6px rgba(245,240,232,0.8))` or `none` | Lifts icons off the hero photo. If the light hero is an ivory veil with ink icons, a black halo around ink icons looks smudged. If the hero is pinned dark, this becomes KEEP. |
| 1994 | `.site-header.is-solid` | box-shadow | `0 1px 2px rgba(0,0,0,0.6)` | THEME | new `--shadow-hairline`; light `0 1px 0 rgba(10,10,10,0.04), 0 10px 30px -12px rgba(40,28,12,0.12)` | Heavy black edge under a white header. |
| 2062-2065 | `.site-header__logo img` | (asset) | `monogram-transparent-256.webp/png`, gold on transparent | THEME | new asset `monogram-ink-256` or a deeper-gold variant, swapped via `<picture>` media or a theme class | **Not a CSS colour, but the most visible miss.** The champagne monogram on ivory is low contrast (about 1.8:1). Needs a gold-700/800 or ink-gold variant. Same file masks the intro shimmer (2801) and the loader mark. |
| 2095-2096 | `.site-header__count` | bg / color | `--accent` / `--text-inverse` | THEME (semantic, OK) | none | |
| 2116-2117 | `.site-header__panel` | bg / border | semantic | THEME (semantic, OK) | none | White panel in light. |
| 2118 | `.site-header__panel` | box-shadow | `var(--shadow-overlay)` | THEME | `--shadow-overlay` light variant | See 137. |
| 2174-2197 | `.site-header__search*` | bg / color / border / caret | semantic | THEME (semantic, OK) | none | |

### Size picker, stars, sticky bar, stock (2226-2487)

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 2314 | `.size-chip__input:checked + .size-chip__card` | background | `rgba(212,176,132,0.10)` | THEME | `--accent-quiet` | Selected size tint; invisible on ivory as written. |
| 2325 | `.size-chip__input:disabled + …` | background-image | diagonal strike in `--text-muted` | THEME (semantic, OK) | none | |
| 2335 | `.size-chip__card:hover` | border-color | `rgba(212,176,132,0.5)` | THEME | new `--border-accent-hover`; light `rgba(122,90,46,0.55)` | |
| 2349 | `.stars__row` | color | `--accent` | THEME (semantic, OK) | consider `--star-fill` = gold-600 in light | gold-800 stars on ivory are correct but read brown; gold-600 (about 3.4:1) is a warmer "gold star" and still passes non-text contrast. |
| 2355 | `.stars__star--empty` | color | `rgba(245,240,232,0.16)` | THEME | new `--star-empty`; light `rgba(10,10,10,0.12)` | Ivory-alpha: invisible on ivory. site.css patches it under `.t-light`. |
| 2357 | `.stars__star--partial` | color | `rgba(245,240,232,0.16)` | THEME | `--star-empty` | Same. |
| 2361 | `.stars__star--partial` | `--star-mask` | data-URI path, no fill | KEEP | none | Mask; theme-proof. |
| 2400 | `.sticky-bar` | background | `rgba(10,10,10,0.94)` | THEME | `--surface-glass` | Mobile PDP buy bar. Same bug as 1386. |
| 2401-2402 | `.sticky-bar` | backdrop-filter | `blur(8px)` | THEME (with 2400) | keep blur, add `saturate(1.2)` in light | Glass on ivory looks best slightly saturated. |
| 2482 | `.stock-line::before` | background | `var(--gold-300)` | THEME | new `--stock-dot`; light gold-600 | Raw palette. Pale gold dot on ivory about 1.5:1: the "In stock" dot disappears. |

### Trust, WhatsApp FAB (2489-2603)

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 2505 | `.trust__icon` | color | `--accent` | THEME (semantic, OK) | none | site.css forces gold-800 under `.t-light`, which the token already does. Duplicate. |
| 2575-2577 | `.whatsapp-fab` | bg / color / border | semantic | THEME (semantic, OK) | none | Light: warm-white disc, gold-800 glyph. Add `--shadow-soft` so it floats. |
| 2582 | `.whatsapp-fab:hover` | background | `var(--whatsapp-green)` | BRAND | unchanged | Third-party brand colour. |
| 2583 | `.whatsapp-fab:hover` | border-color | `var(--whatsapp-green)` | BRAND | unchanged | |
| 2584 | `.whatsapp-fab:hover` | color | `#FFFFFF` | BRAND | unchanged (optionally `--ivory-000`) | White on WhatsApp green on both grounds. |

### Intro loader (2728-2871)

The intro is a full-screen cover shown before the page. On the dark site it dissolves ink into ink, which is seamless. On an ivory site an ink curtain that fades into ivory is a flash. Recommendation: the intro follows the theme (ivory curtain, deep-gold mist), so it dissolves into the page it reveals. `site/assets/js/intro.js` draws on `.sf-intro__canvas` and almost certainly hardcodes particle colours; that is out of this file's scope but must be checked (see risks).

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 2734 | `.sf-intro` | background | `#0A0A0A` | THEME | `--surface` (or new `--intro-bg`) | Literal copy of ink-900. |
| 2756 | `.sf-intro__mist` | background | radial gold 0.16 / 0.05 | THEME | new `--intro-mist`; light `rgba(168,127,78,0.14)` / `0.04` | Gold light on ink becomes gold shadow on ivory: needs a darker gold. |
| 2763 | `.sf-intro__mist--b` | background | radial `rgba(255,232,190,0.12)` | THEME | `--intro-mist-b`; light `rgba(194,156,110,0.12)` | Warm white on ivory: invisible. |
| 2798 | `.sf-intro__mark-wrap::after` | background | shimmer `rgba(255,241,205,0.8)` | BRAND | unchanged | Masked to the monogram, so it is always a highlight on gold. Works on both. |
| 2801-2802 | `.sf-intro__mark-wrap::after` | mask | monogram webp | KEEP | none | Alpha mask; theme-proof. The visible mark image itself has the logo contrast problem (2062). |
| 2818 | `.sf-intro__tag` | color | `rgba(212,176,132,0.85)` | THEME | `--accent` | Gold-400 tagline on ivory about 1.8:1. |
| 2835 | `.sf-intro__skip` | color | `rgba(245,240,232,0.72)` | THEME | `--text-muted` | Ivory "Skip" on an ivory curtain: invisible, and it is the only way out of the full intro. Accessibility-critical. |

---

## 6. `.t-light` and `.t-dark`: what a site-wide light theme means for them

### What exists today

| Where | Rule | Purpose |
|---|---|---|
| critical.css 164-188 | `.t-light { …23 semantic tokens… }` in `@layer tokens` | Remaps the semantic layer for any subtree. |
| site.css 2570-2626 | `.t-light { background; color }` + about 20 component patches (`.t-light .btn--primary`, `.field__input`, `.stars__star--empty`, `.badge--sold-out`, `.divider`, `.meter__seg`, `.skeleton`, `.panel`, `::selection` …) | Fixes the places where components hardcode dark assumptions (most of the THEME rows above). |
| `app/views/home.php:132` | `<section class="section t-light" id="why-us">` | The only storefront use: one ivory band for rhythm on the dark home page. |
| `admin/views/layout.php:6-8`, `app/routes-admin.php:13`, every admin controller | `body class="admin t-light …"` | **The admin panel is already light**, via `.t-light` on `<body>`. |
| anywhere | `.t-dark` | **Does not exist.** There is no way to pin a subtree dark. |

### What changes

1. **`.t-light` is doing two jobs.** It means "this subtree is light" (admin, why-us band) and, through the site.css patches, "fix the components that assume dark". A site-wide light theme needs the second job moved into tokens, so the patches become unnecessary rather than duplicated under a new selector. Every site.css `.t-light .x` patch in the list above maps to a THEME row in this audit (on-accent text, star-empty, sold-out chip, field white, divider, shadow-soft).
2. **Introduce the theme at `html`, not `body`.** Proposed: `html[data-theme="light"] { …light semantic block… }` in `@layer tokens`, with `color-scheme: light`. Specificity (0,1,1) beats `:root` (0,1,0) and ties with nothing else. The server writes the attribute from the admin setting, so there is no flash and no JS needed for first paint.
3. **Keep the admin untouched.** The admin stays on `body.t-light` and must not receive `data-theme`. Admin CSS also consumes `--scrim-modal` (`admin.css:87`), so any new light value for it lands only under `html[data-theme="light"]`, never in `.t-light`.
4. **Add `.t-dark`.** A mirror of the `:root` dark semantic block (plus `color-scheme: dark`). Needed for: the hero if it is pinned dark, the lightbox, image cards with ivory text on `--scrim-bottom`, and the inverted rhythm band below.
5. **Rhythm on the home page.** On the dark site, why-us is the one ivory band. On a light site, `.t-light` there becomes ivory-on-ivory and the rhythm is lost. The band's real meaning is "the opposite of the page". Proposed: rename the home usage to `.t-contrast`, defined as light under the default theme and dark (ink-850 with gold-400 accents) under `data-theme="light"`. An ink band midway down an ivory page is the most luxurious move available (the Byredo / Le Labo pattern) and gives gold its night-time glow back once per page.
6. **`.band--ivory` (549)** is the same idea with a colour name; fold it into `.t-contrast`.
7. **Hero.** Two options for the light theme:
   - **Pin dark** (`.hero.t-dark`): simplest, keeps the WebGL/photo drama, header icons over it stay ivory with drop-shadow (1990 becomes KEEP). But the first screen of a "light" site is black, which the owner may read as "the toggle did nothing".
   - **Ivory veil** (recommended): photo at `--hero-image-opacity` around 0.9 under `--scrim-hero` (ivory gradient, heavier at the bottom where the text sits), ink title, gold-800 eyebrow. Reads as an editorial magazine cover. Requires the WebGL hero and motion parts to accept a light clear colour (check `site/assets/js/motion/`).

---

## 7. New tokens this audit proposes

| Token | Dark (today's value) | Light (first cut) | Replaces |
|---|---|---|---|
| `--ivory-000` | n/a (primitive) | `#FFFDF9` | `#FFFFFF` at 167, site.css field whites |
| `--ivory-450` | n/a (primitive) | `#8F877B` | literal at 172 |
| `--on-accent-fill` | `var(--ink-900)` | `var(--ink-900)` | `--text-inverse` on gold gradients (899, 907, 1005) |
| `--text-faint` | `var(--ink-500)` | `var(--ivory-300)` | 823, 1504 |
| `--border-control` | `var(--ink-500)` | `var(--ivory-300)` | 1088, site.css 709 scrollbar |
| `--border-accent-hover` | `rgba(212,176,132,0.5)` | `rgba(122,90,46,0.55)` | 2335 |
| `--field-bg` | `var(--surface-sunken)` | `var(--ivory-000)` | 1087 + site.css patch |
| `--surface-glass` | `rgba(10,10,10,0.94)` | `rgba(251,248,243,0.92)` | 1386, 2400 |
| `--badge-veil` / `--on-badge-veil` | `rgba(10,10,10,0.82)` / ivory-400 | `rgba(251,248,243,0.90)` / ivory-500 | 746-747 |
| `--on-image-muted` | `var(--ivory-300)` | `var(--ivory-300)` (constant) | 1453 |
| `--accent-ghost` | `rgba(212,176,132,0.28)` | `rgba(122,90,46,0.22)` | 1735 |
| `--star-empty` | `rgba(245,240,232,0.16)` | `rgba(10,10,10,0.12)` | 2355, 2357 |
| `--stock-dot` | `var(--gold-300)` | `var(--gold-600)` | 2482 |
| `--glow-ambient` | current `--grad-gold-radial` | warm gold-500 radial into ivory | 51, 433, 1604 |
| `--grad-gold-text` | current `--grad-gold` | gold-800 to gold-500 to gold-800 | 46, 408 |
| `--shadow-press` | `0 1px 2px rgba(0,0,0,0.6)` | `0 1px 2px rgba(60,40,15,0.18)` | 904 |
| `--shadow-lift` | `0 12px 32px rgba(0,0,0,0.55)` + glow | gold-tinted drop | 1027 |
| `--shadow-hairline` | `0 1px 2px rgba(0,0,0,0.6)` | brown-tinted hairline + soft drop | 1994 |
| `--shadow-soft` | `none` | today's `--shadow-light`, brown-tinted | 136 rename |
| `--header-float-shadow` | `drop-shadow(0 1px 8px rgba(0,0,0,0.6))` | `none` or ivory halo | 1990 |
| `--scrim-hero` | current `--scrim-full` | ivory veil gradient | 42, 1602 |
| `--hero-image-opacity` | 0.6 (admin-set) | about 0.9 | 1596 |
| `--sold-out-filter` | `grayscale(0.35) brightness(0.72)` | `grayscale(0.6) contrast(0.9) brightness(1.04) opacity(0.72)` | 1873 |
| `--icon-chevron` / `--icon-search` | gold / grey data URIs | gold-800 / ivory-500 data URIs | 1116, 1364, 1133 |
| `--intro-mist`, `--intro-mist-b` | gold / warm-white radials | gold-600 / gold-500 radials | 2756, 2763 |

Tokens that already exist but need a light value added: `--scrim-modal`, `--shadow-overlay`, `--glow-accent`, `--grad-gold-rule`, `--grad-gold-sheen`, and `color-scheme`.

---

## 8. Hotspots (ordered by visual impact)

1. **Logo monogram** (2062, 2801): champagne mark on ivory is about 1.8:1. Needs a deep-gold asset. Most visible miss, and not fixable in CSS alone.
2. **Hero** (42, 1596, 1602, 1990): photo opacity blends into `--surface`, dark scrim, header drop-shadow. Decide ivory-veil vs pinned-dark first; four rows depend on it.
3. **Primary CTA text** (899, 907, 1005): `--text-inverse` turns ivory on the gold gradient, about 1.7:1. Every "Add to bag" breaks.
4. **Gold gradient headings** (46, 407-408): the near-ivory 38% stop erases the middle of every `.text-gold-grad` headline.
5. **Mobile glass bars** (1386, 2400): ink slabs at 94% on an ivory page (sticky buy bar, filter bar).
6. **Intro loader** (2734, 2818, 2835): ink curtain flashes into ivory; ivory "Skip" link becomes invisible.
7. **Native select options** (1123): ink text on ink-700 rows, unreadable dropdowns (sort, size, checkout city).
8. **Missing `color-scheme`** (global): native controls, scrollbars and autofill ignore the theme.
9. **Shadows** (904, 1027, 1994, 137): 55-60% black shadows smear on ivory; the light theme lives or dies on warm, soft, brown-tinted shadows.
10. **Select/search icons** (1116, 1364, 1133): data-URI strokes cannot follow tokens.
11. **Hairlines** (35-37): ivory-alpha borders vanish; gold-alpha accent borders become smudges.
12. **Sold-out treatment** (746-747, 1873): dark chip with flipped muted text (about 2:1) and "dirty" darkened photo.
13. **Stars and stock dot** (2355, 2357, 2482): empty stars and in-stock dot disappear.
14. **Raw dark tokens** (823, 1088, 1504): breadcrumb slash, every input border and gallery dots turn near-black.
15. **Home rhythm band** (home.php:132 `.t-light`): becomes ivory-on-ivory; needs `.t-contrast` inversion.
16. **Selected-state tints** (1032, 1933, 2314, 2335): 6-14% champagne tints are invisible on ivory; selected size looks unselected.

---

## 9. Risks

- **Admin collision.** The admin already runs on `body.t-light` and shares these tokens (and `admin.css` consumes `--scrim-modal`). Changing `.t-light` values to make the storefront light also restyles the admin. Put storefront light values under `html[data-theme="light"]` only, and never render that attribute on admin pages.
- **site.css `.t-light` patches duplicate the fix.** About 20 component patches live there. If the light theme is built as tokens and the patches stay, they fight inside `.t-light` subtrees (admin, why-us) and cascade-layer order decides the winner. Plan to delete each patch as its token lands, and verify the admin visually after each.
- **Cascade layers.** `.t-light` lives in `@layer tokens`; component patches live in `@layer components` in site.css. A theme selector defined in `tokens` loses to any component rule that hardcodes a colour, which is exactly the THEME rows here. Tokens alone are not enough until those rows are converted.
- **Inline hero opacity.** `--hero-opacity` is written inline by `home.php` (admin setting) and `listing.php` (0.55). A theme token cannot override an inline custom property without a second variable (for example `opacity: calc(var(--hero-opacity) * var(--hero-opacity-k))`) or a separate light setting.
- **JS colours outside CSS.** `intro.js:142` draws with `rgb(214,180,136)`; `motion/config.js` (lines 132, 174-179) and `motion/ribbons-gl.js` (229-230) hardcode gold and scent-family colours for the WebGL hero. These were tuned for additive light on black; on ivory they will look pale or muddy. They need a theme-aware palette read from CSS custom properties at init.
- **Data URIs cannot read variables.** Chevron and search icons (1116, 1133, 1364) need either paired URIs per theme or a mask-image + `currentColor` refactor.
- **Brand assets.** The monogram PNG/WebP is champagne-on-transparent. A light theme needs a second asset; also check favicon, OG images and email templates if "everything" is taken literally.
- **Contrast of gold as text.** gold-400 on ivory is about 1.9:1 and gold-600 about 3.4:1; only gold-700 (about 4.4:1) and gold-800 (about 5.0:1) pass for body text. Any gold text left on a raw token fails WCAG in light.
- **First-paint flash.** The theme must be rendered server-side on `<html>` (from the admin setting) inside critical.css scope; a JS-applied class would flash the dark theme on every load.
- **Dead token.** `--grad-gold-border` (48) has no consumer; do not spend design time on its light variant.
- **Scope of this audit.** critical.css only. site.css, motion parts and admin.css need their own passes; site.css 709 (`scrollbar-color: var(--ink-500)`) was spotted in passing.
