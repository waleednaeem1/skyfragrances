# Colour audit: site.css and motion parts

Scope: every colour decision in `site/assets/css/site.css` (2711 lines) and in each part under `site/assets/css/motion/` (`00-core` to `60-transitions`; `motion.css` is only their concatenation, so line numbers below refer to the parts). Read-only audit, no code changed.

Classes used in the tables:

- **THEME**: must follow the site theme. The proposed token is what the declaration should read instead of the literal. Tokens marked *new* do not exist yet.
- **KEEP**: sits on photography or inside a deliberately dark element (image scrim, lightbox chrome, print). Stays the same in both themes. Where a KEEP element currently reads a *semantic* token (so it would silently flip), the proposed token pins it to an on-image value.
- **BRAND**: constant brand gold that reads correctly on both ink and ivory grounds.

Pure `currentColor`, `transparent`, and declarations that already read a semantic token correctly (for example `color: var(--text-muted)` on a surface) are not listed unless they sit somewhere the token would give the wrong answer.

## New tokens proposed by this audit

| Token | Dark value (today) | Light value (proposal) | Replaces |
|---|---|---|---|
| `--track` | `rgba(245,240,232,0.10)` | `rgba(10,10,10,0.08)` | meter, progress, free-ship, review-summary tracks |
| `--fill-accent` | `var(--grad-gold-button)` | `var(--gold-700)` or a deep gold gradient | meter, progress, free-ship, rail thumb fills |
| `--grad-rule` | `var(--grad-gold-rule)` | gold-700/gold-600 stops, centre `rgba(168,127,78,0.7)` | section rules, footer rule, pyramid tiers |
| `--grad-gold-text` | `var(--grad-gold)` | `linear-gradient(118deg,#7A5A2E,#A87F4E 30%,#8A6538 55%,#C29C6E 74%,#7A5A2E)` | hero title, `.text-gold-grad`, quiz sigil |
| `--sheen-text` | `rgba(255,241,205,0.92)` | `rgba(255,250,238,0.85)` | hero title sweep highlight |
| `--glow-radial` | `var(--grad-gold-radial)` | gold-600 at 0.10 fading to transparent | gender card, quiz card back |
| `--hero-glow` | amber radial 0.5 | gold-600 radial 0.22, optional `multiply` | hero glow |
| `--glow-strong` | gold 0.55 radial | gold-600 0.28 radial | quiz reveal glow |
| `--mist-a` / `--mist-b` | ivory 0.5 / gold 0.45 | white 0.85 / gold-600 0.20 | quiz mist |
| `--sf-mist` (remap) | gold 0.14 over `#0A0A0A` | gold 0.10 over `var(--ivory-100)` | page-transition veil |
| `--halo-blend` | `screen` | `multiply` | story halo |
| `--sheen-card` + `--sheen-blend` | ivory sweep, `soft-light` | gold-200 sweep, `overlay` (or white 0.6, `normal`) | product-card media sweep |
| `--shadow-overlay` (remap) | `0 24px 64px rgba(0,0,0,0.55)` | `var(--shadow-light)` or warm `rgba(58,42,20,0.14)` | drawer, modal, toast, nav-mobile, suggest |
| `--shadow-deep` | `0 30px 60px -40px rgba(0,0,0,0.9)` | `0 30px 60px -40px rgba(58,42,20,0.35)` | quiz deck card |
| `--shadow-float` | `0 2px 10px rgba(0,0,0,0.45)` | `0 2px 10px rgba(58,42,20,0.22)` | add-to-cart fly dot |
| `--scrim-modal` (remap) | `rgba(10,10,10,0.72)` | `rgba(26,22,18,0.38)` | drawer and modal overlays |
| `--danger-quiet` | `rgba(229,115,107,0.06-0.08)` | `rgba(163,43,34,0.06)` | error panels and messages |
| `--success-quiet` | `rgba(111,191,139,0.06)` | `rgba(31,107,62,0.06)` | success panel |
| `--star-empty` | `rgba(245,240,232,0.16)` | `rgba(10,10,10,0.12)` | star input, empty stars |
| `--skeleton-sheen` | ivory 0.06 sweep | white 0.6 sweep | skeleton shimmer |
| `--scrollbar-thumb` | `var(--ink-500)` | `var(--ivory-300)` | drawer scrollbar |
| `--border-strong` | `var(--ink-500)` | `var(--ivory-300)` | nav search input, idle timeline dot |
| `--border-accent-soft` / `--border-accent-faint` | gold 0.28 / 0.14 | gold-700 0.30 / 0.14 | quiz frames |
| `--media-dim` | `grayscale(0.4) brightness(0.6)` | `grayscale(0.8) opacity(0.55)` | unavailable cart line image |
| `--on-image`, `--on-image-muted`, `--on-image-accent` | ivory-100, ivory-300, gold-400 | same (pinned) | text on photo scrims |

## site.css

### Cart and order summary

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 173 | `.cart-line.is-unavailable .cart-line__media` | filter | `grayscale(0.4) brightness(0.6)` | THEME | `--media-dim` (new) | Darkening an image on ivory reads as dirty, not unavailable. Light should desaturate and fade instead. |
| 199 | `.cart-message--error` | background | `rgba(229,115,107,0.08)` | THEME | `--danger-quiet` (new) | On-dark danger tint; on ivory it goes salmon-pink. |
| 245 | `.summary__value--free` | color | `var(--gold-300)` | THEME | `--accent` (or `--success`) | gold-300 on ivory is about 1.6:1, fails contrast. |
| 370 | `.free-ship__track` | background | `var(--rule-faint)` | THEME | `--track` (new) | Ivory 0.08 alpha, invisible on ivory. Patched only inside `.t-light` (2619). |
| 376 | `.free-ship__fill` | background | `var(--grad-gold-button)` | THEME | `--fill-accent` (new) | Pale centre stops wash out on ivory; `.t-light` patches to gold-700 (2620). |

### Collection cards (image-backed)

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 535 | `.collection-card__scrim` | background | ink gradient 0.88 to 0 | KEEP | `--scrim-card` (optional alias) | Sits on photography; text legibility depends on it. |
| 607 | `.collection-card:hover .collection-card__scrim` | background | ink gradient 0.94 to 0 | KEEP | same | Hover deepening, photo only. |
| 552 | `.collection-card__eyebrow` | color | `var(--accent)` | KEEP (pin) | `--on-image-accent` | Semantic today, so in light theme it becomes gold-800 on a black scrim (about 2.3:1). Must be pinned. |
| 559 | `.collection-card__name` | color | `var(--text)` | KEEP (pin) | `--on-image` | Would turn ink-900 on the ink scrim: invisible. Biggest silent break in this file. |
| 564 | `.collection-card__tagline` | color | `var(--text-muted)` | KEEP (pin) | `--on-image-muted` | Would turn ivory-500 on black. |
| 572 | `.collection-card__count` | color | `var(--text-muted)` | KEEP (pin) | `--on-image-muted` | Same. |
| 577 | `.collection-card__rule` | background | `var(--accent)` | KEEP (pin) | `--on-image-accent` | Same. |
| 580 | `.collection-card:hover` | color | `var(--text)` | KEEP (pin) | `--on-image` | Same. |
| 602 | `.collection-card--gender` | background-image | `var(--grad-gold-radial)` | THEME | `--glow-radial` (new) | No photo, sits on surface; gold 0.14 glow is fine on ink, faint beige on ivory. Its body text must NOT be pinned (no scrim). |
| 587 | `.collection-card--empty` | border / bg | `--border-accent` / `--surface` | THEME | (already semantic) | OK. `--empty` hides the scrim, so its body text must also stay semantic; pinning has to target `:not(--empty):not(--gender)`, or better a `.t-on-image` scope on the body. |

### Drawer, forms, nav

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 619 | `.drawer-overlay` | background | `var(--scrim-modal)` | THEME | `--scrim-modal` remapped | 0.72 ink over an ivory page is a black curtain. Light wants a warm 0.35 to 0.40 veil. |
| 645 | `.drawer` | box-shadow | `var(--shadow-overlay)` | THEME | `--shadow-overlay` remapped | `.t-light` patch at 2624 already swaps to `--shadow-light`. |
| 708-709 | `.drawer__body` | scrollbar-color | `var(--ink-500) transparent` | THEME | `--scrollbar-thumb` (new) | ink-500 thumb is a hard dark bar on ivory. |
| 762 | `.drawer__error` | background | `rgba(229,115,107,0.08)` | THEME | `--danger-quiet` | |
| 779 | `.drawer__handle` | background | `var(--rule-strong)` | THEME | `--border-accent` | gold 0.38 is fine on ink, too pale on ivory. |
| 866 | `.form__summary` | background | `rgba(229,115,107,0.06)` | THEME | `--danger-quiet` | |
| 1243 | `.nav-mobile` | box-shadow | `var(--shadow-overlay)` | THEME | `--shadow-overlay` remapped | No `.t-light` patch exists: in light it would cast a 0.55 black shadow. |
| 1286 | `.nav-mobile__input` | border | `var(--ink-500)` | THEME | `--border-strong` (new) | Raw ink on ivory: heavy dark outline. |
| 1289 | `.nav-mobile__input` | background-image | SVG data URI, `stroke='%239C968C'` | THEME | mask-image + `--text-muted` | Hard-coded ivory-400 search glyph; about 2.8:1 on white. Use a CSS mask on a pseudo-element coloured by a token, or ship two URIs behind a token. |
| 1293 | `.nav-mobile__input` | caret-color | `var(--accent)` | THEME | (already semantic) | OK, follows. |
| 1298 | `.nav-mobile__input:focus` | box-shadow | `0 0 0 3px var(--accent-halo)` | THEME | (already semantic) | OK. |
| 2710 | `.site-header__search-input:focus` | box-shadow | `0 1px 0 0 var(--accent)` | THEME | (already semantic) | OK. |

### Instagram grid, meter, modal, lightbox

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 993 | `.insta__veil` | background | `rgba(10,10,10,0.25)` | KEEP | (literal is fine) | Veil over photography. |
| 994 | `.insta__veil` | color | `var(--text)` | KEEP (pin) | `--on-image` | Would become ink on a darkened photo. |
| 1036 | `.meter__seg` | background | `rgba(245,240,232,0.10)` | THEME | `--track` | Invisible on ivory; `.t-light` patch 2602. |
| 1043 | `.meter__seg::after` | background | `var(--grad-gold-button)` | THEME | `--fill-accent` | `.t-light` patch 2603 uses gold-700. |
| 1087 | `.modal__overlay` | background | `var(--scrim-modal)` | THEME | `--scrim-modal` remapped | Same as drawer overlay. |
| 1098 | `.modal__panel` | box-shadow | `var(--shadow-overlay)` | THEME | `--shadow-overlay` remapped | `.t-light` patch 2624. |
| 1111 | `.modal__handle` | background | `var(--rule-strong)` | THEME | `--border-accent` | |
| 1155 | `.modal--lightbox .modal__panel` | background | `var(--surface)` | THEME (decision) | `--lightbox-bg` (new) | Follows theme today. Recommendation: let it go ivory in light theme; bottle photography on ivory is the most flattering light-mode moment. If the owner prefers a gallery-black lightbox, pin to ink and scope it `.t-dark`. |
| 1170 | `.modal--lightbox .modal__close` | background | `rgba(10,10,10,0.6)` | KEEP | (literal) | Chip floats over photography. |
| 1171 | `.modal--lightbox .modal__close` | color | `var(--text)` | KEEP (pin) | `--on-image` | Would be ink icon on a 0.6 ink chip. |
| 1179-1182 | `.lightbox__rail` | scrollbar | `none` | KEEP | n/a | Hidden. |

### Pagination, prose, pyramid, reviews, search

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 1441 | `.pagination__link:hover` | background | `rgba(212,176,132,0.10)` | THEME | `--accent-quiet` | Literal copy of the dark accent-quiet; light version exists already. |
| 1452 | `.pagination__gap` | color | `var(--ink-400)` | THEME | `--text-disabled` | On ivory ink-400 is near-black, so the ellipsis would outshout the links. |
| 1503 | `.prose a` | text-decoration-color | `var(--rule-strong)` | THEME | `--border-accent` | Gold 0.38 underline disappears on ivory. |
| 1546-1547 | `.pyramid__tier` | border-image | `var(--grad-gold-rule) 1` | THEME | `--grad-rule` (new) | Champagne centre (`#F2E6D2` 0.85) vanishes on ivory; rule reads as broken. |
| 1678 | `.review-summary__track` | background | `rgba(245,240,232,0.10)` | THEME | `--track` | `.t-light` patch 2619. |
| 1708 | `.star-input__label` | color | `rgba(245,240,232,0.16)` | THEME | `--star-empty` (new) | Empty stars invisible on ivory; no `.t-light` patch for the input (only for `.stars__star--empty`). |
| 1750 | `.suggest` | box-shadow | `var(--shadow-overlay)` | THEME | `--shadow-overlay` remapped | No `.t-light` patch; black 0.55 shadow under the search dropdown. |

### Section rules, footer, skeleton, timeline, toast, progress, panels

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 1883 | `.section-header__rule` | background | `var(--grad-gold-rule)` | THEME | `--grad-rule` | Appears under almost every section title; very visible. |
| 1938 | `.site-footer::before` | background | `var(--grad-gold-rule)` | THEME | `--grad-rule` | Footer crown rule. |
| 2083 | `.skeleton::after` | background | ivory 0 / 0.06 / 0 sweep | THEME | `--skeleton-sheen` (new) | `.t-light` patch 2604-2605. |
| 2164 | `.timeline__dot::after` | background | `var(--ink-500)` | THEME | `--border-strong` | Idle dot is a dark blot on ivory. |
| 2186 | `.timeline__step.is-current .timeline__dot::after` | box-shadow | `0 0 0 4px var(--accent-quiet)` | THEME | (already semantic) | OK. |
| 2247 | `.toast` | box-shadow | `var(--shadow-overlay)` | THEME | `--shadow-overlay` remapped | `.t-light` patch 2624. |
| 2361 | `.progress__track` | background | `var(--rule-faint)` | THEME | `--track` | `.t-light` patch 2619. |
| 2366 | `.progress__fill` | background | `var(--grad-gold-button)` | THEME | `--fill-accent` | `.t-light` patch 2620. |
| 2381 | `.panel--danger` | background | `rgba(229,115,107,0.06)` | THEME | `--danger-quiet` | |
| 2385 | `.panel--success` | background | `rgba(111,191,139,0.06)` | THEME | `--success-quiet` (new) | |

### Reduced motion and print

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 2653-2658 | `.sticky-bar`, `.drawer-overlay` (reduced motion) | backdrop-filter | `none` | KEEP | n/a | The blur itself and its tint live in critical.css (outside this audit). If the sticky bar tint is an ink rgba it needs a `--surface-glass` token. |
| 2672-2673 | `html, body` (print) | background / color | `#FFFFFF` / `#0A0A0A` | KEEP | n/a | Print is always light. |
| 2676-2688 | `body` (print) | token overrides | 13 hex literals | KEEP | n/a | Print re-maps semantic tokens to light values; it already is a light theme in miniature and is a useful reference for the site-wide one. |
| 2701-2702 | `.panel, .summary, .voice` (print) | border / box-shadow | `#D8CFBF` / none | KEEP | n/a | Print only. |

## Motion parts (`site/assets/css/motion/`)

### 00-core.css

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 40 | `html.sf-intro-calm .sf-intro` | background | `transparent` | KEEP | n/a | The intro loader's own ink background is in critical.css (around 2729). It must follow the theme or the light site opens on a black card. The canvas in `js/intro.js` draws `rgb(214,180,136)` with `globalCompositeOperation = 'lighter'`, which is additive and disappears on ivory. |

### 10-hero.css

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 45 | `.hero__glow::before` | background | amber radial `rgba(215,164,102,0.5)` to 0 | THEME | `--hero-glow` (new) | Pre-WebGL fallback glow behind the bottle. 0.5 amber on ivory is a brown stain. Light: gold-600 at about 0.22, optionally `mix-blend-mode: multiply`. |
| 106 | `html.motion--desktop .hero__title` | background-image | sweep `rgba(255,241,205,0.92)` over `var(--grad-gold)` | THEME | `--sheen-text` + `--grad-gold-text` (new) | The headline is gold gradient text. `--grad-gold` passes through `#F2E6D2` and `#DCC29B`, which are near-invisible on ivory-100. Light needs a deeper gradient (gold-800 to gold-600). The sweep highlight still works on the deeper gradient. |
| 51-82 | `.hero__plate` | (image) | raster plate | THEME (asset) | n/a | The plate is a pre-rendered glow image; if it is baked on black it will show a dark rectangle on ivory. Needs a transparent or light variant. |
| 83-96 | `.hero__ribbons` | (WebGL canvas) | `blendFunc(ONE, ONE)`, premultiplied | THEME (JS) | theme flag in `motion/config.js` | Additive light ribbons are designed for a black ground; on ivory they brighten towards white and vanish. Needs normal alpha blending and deeper gold (`#A87F4E`) in light. |

### 20-cards.css

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 97 | `[data-motion-card] .product-card__media::before` | background | ivory sweep 0 / 0.16 / 0.5 | THEME | `--sheen-card` (new) | Product media sits on a surface tile; an ivory sheen in `soft-light` over an ivory tile is a no-op. |
| 98 | same | mix-blend-mode | `soft-light` | THEME | `--sheen-blend` (new) | Light: `overlay` with gold-200, or `normal` with white 0.55. |
| 97-98 | `.collection-card::after` | background / blend | same sweep, `soft-light` | KEEP | n/a | Over photography; works in both themes. Needs splitting from the product-card selector it shares. |
| 134 | `.sf-rail-progress` | background | `rgba(212,176,132,0.18)` | THEME | `--accent-halo` | Existing token already remaps to gold-800 0.18 in light. |
| 150 | `.sf-rail-progress::before` | background | `gold-600 / gold-300 / gold-600` | THEME | `--fill-accent` | gold-300 centre fades on ivory. |

### 30-story.css

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 6-7 | `.story` vars | custom props | `--sf-story-glow-alpha: 0.22`, `--sf-story-wash-alpha: 0.06` | THEME | remap per theme | The alpha, not the hue, is what must change. Light: glow 0.16, wash 0.05. A family-tinted blush on ivory is lovely. |
| 12 | `.story` | `--sf-story-rgb` | `212 176 132`, overwritten by `js/motion/story.js` from `config.js` family hues | BRAND | n/a | Family hues (citrus, floral, amber, oud, green) are brand data and read on both grounds. |
| 26-27 | `.story__glow` | background | family-hue radial + wash | THEME | via the alpha vars above | |
| 84 | `.story__halo` | background | family-hue radial 0.42 | THEME | via alpha var | |
| 85 | `.story__halo` | mix-blend-mode | `screen` | THEME | `--halo-blend` (new) | `screen` over ivory is invisible. Light: `multiply` at about 0.28. |
| 96-97 | `.story__bottle` | mask-image | radial `#000` to transparent | KEEP | n/a | Alpha mask only; colour irrelevant. |
| 120 | `.story .pyramid__label::after` | background | `var(--accent)` | THEME | (already semantic) | OK. |

### 40-micro.css

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 3 | link underline draw | background-image | `var(--accent)` | THEME | (already semantic) | OK. |
| 63-64 | `.sf-spray` | background / box-shadow | `var(--accent)`, `0 0 6px var(--accent)` | THEME | (already semantic) | Glow reads as a shadow on ivory, acceptable; could use `--accent-halo`. |
| 83 | `.sf-fly` | box-shadow | `0 2px 10px rgba(0,0,0,0.45)` | THEME | `--shadow-float` (new) | Pure black 0.45 is harsh on ivory. |
| 115, 124 | `.sf-cursor__ring` | border / background | `var(--accent)` | THEME | (already semantic) | gold-800 cursor becomes hard to see over dark photos in light theme; acceptable. |

### 50-quiz.css

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 21 | `.quiz-deck .form__section` | border | `rgba(212,176,132,0.28)` | THEME | `--border-accent-soft` (new) | |
| 22 | same | background | gold 0.06 wash over `var(--surface-raised)` | THEME | `--accent-quiet` wash | Fine-ish on ivory; tokenise so the tint follows. |
| 23 | same | box-shadow | `0 30px 60px -40px rgba(0,0,0,0.9)` | THEME | `--shadow-deep` (new) | 0.9 black under a floating card on ivory looks like a burn. |
| 30 | `.quiz-deck .form__section::before` | border | `rgba(212,176,132,0.14)` | THEME | `--border-accent-faint` (new) | Inner frame vanishes on ivory. |
| 72 | `.choice__input:checked + .choice__card::after` | background | `var(--grad-gold-button)` | BRAND | n/a | Gold fill with ink label reads on both grounds (same as the primary button). |
| 138 | `.quiz-card__back` | border | `rgba(212,176,132,0.42)` | THEME | `--border-accent` | |
| 139 | same | background | gold 0.18 radial to ink 0, over `--surface-sunken` | THEME | `--glow-radial` | |
| 144 | `.quiz-card__frame` | border | `rgba(212,176,132,0.3)` | THEME | `--border-accent-soft` | |
| 150 | `.quiz-card__frame::before` | border | `rgba(212,176,132,0.14)` | THEME | `--border-accent-faint` | |
| 158-159 | `.quiz-card__sigil` | color / background-image | `var(--gold-400)` / `var(--grad-gold)` clipped to text | THEME | `--grad-gold-text` | Pale gradient text on ivory, same as hero title. |
| 170 | `.quiz-card__glow` | background | gold 0.55 radial | THEME | `--glow-strong` (new) | Muddy blob on ivory at 0.55. |
| 181-183 | `.quiz-card__mist` | background | ivory 0.5, gold 0.45, ivory 0.35 radials | THEME | `--mist-a`, `--mist-b` (new) | Ivory mist over an ivory card is invisible; the reveal loses its magic. Light: white 0.85 plus gold-600 0.2 over `--surface-sunken`. |
| 194 | `body.quiz-result .page-head__title.text-gold-grad` | background (sweep) | `.text-gold-grad` gradient | THEME | `--grad-gold-text` | The result title is gold gradient text too. |

### 60-transitions.css

| Line | Selector | Property | Value | Class | Proposed token | Note |
|---|---|---|---|---|---|---|
| 10 | `:root` | `--sf-mist` | gold 0.14 radial over `#0A0A0A` | THEME | `--sf-mist` remapped per theme | Every desktop page change cross-fades through this. With a light site it flashes black between every page. Highest-impact single line in the motion layer. |
| 13 | `::view-transition` | background | `var(--sf-mist)` | THEME | via remap | |
| 17, 21 | `::view-transition-old/new(root)` | mix-blend-mode | `normal` | KEEP | n/a | Neutral. |
| 41 | `.sf-veil` | background | `var(--sf-mist)` | THEME | via remap | Fallback veil for browsers without view transitions. |

## `.t-light` and `.t-dark` today

There is no `.t-dark` rule anywhere in site CSS. `.t-light` is used in three places:

1. **critical.css 164-187**: a token remap. It re-points every semantic token to ivory surfaces, ink text, gold-800 accent, light success/danger, ink price.
2. **site.css 2570-2627**: component patches under `.t-light` (listed below).
3. **Markup**: `app/views/home.php:132` wraps `#why-us` in `section.t-light` (the ivory band that gives the dark home page rhythm), and every admin page puts `t-light` on `<body>` (`admin/views/layout.php:6-8` forces it).

| Line | Selector | What it patches | Should become |
|---|---|---|---|
| 2570 | `.t-light` | `background: var(--surface); color: var(--text)` | Stays; the scope primitive. |
| 2574-2577 | `.t-light .field__input` | `#FFFFFF` bg, `--rule-light-strong` border | `--control-bg` (new), `--border-strong` |
| 2578 | `.t-light .field__input:disabled` | `--ivory-200` | `--surface-sunken` |
| 2579-2582 | `.t-light .field__checkbox` | `#FFFFFF`, `--ivory-500` | `--control-bg`, `--text-muted` |
| 2583-2586 | `.t-light .btn--primary` | ink text on `--grad-gold-button` | BRAND; could be the default in both themes |
| 2587-2590 | `.t-light .btn--ghost` | gold-700 border, ink text | `--border-accent`, `--text` |
| 2591 | `.t-light .btn--text` | ivory-500 | `--text-muted` |
| 2592-2595 | `.t-light .btn:disabled` | ivory-200 bg | `--surface-sunken`, `--text-disabled` |
| 2596-2600 | `.t-light .panel, .summary, .voice` | overlay bg, `--shadow-light` | `--shadow-surface` (new; none in dark, light shadow in light) |
| 2601 | `.t-light .stars__star--empty/--partial` | ink 0.12 | `--star-empty` |
| 2602-2603 | `.t-light .meter__seg(::after)` | ink 0.08 / gold-700 | `--track` / `--fill-accent` |
| 2604-2605 | `.t-light .skeleton(::after)` | ivory-200, white sheen | `--surface-sunken`, `--skeleton-sheen` |
| 2606-2613 | `.t-light .badge--sale / --sold-out` | gold-400 + ink; ivory-200 + ivory-500 | `--badge-sale-bg`/`-fg` (new), `--surface-sunken`/`--text-muted` |
| 2614 | `.t-light .trust__icon` | gold-800 | `--accent` (already gold-800 in light) |
| 2615-2618 | `.t-light .divider` | gold-700 at 0.4 | `--border-accent` |
| 2619-2620 | `.t-light` tracks and fills | ink 0.08 / gold-700 | `--track` / `--fill-accent` |
| 2621 | `.t-light .status-pill` | rule-light-strong | `--border-strong` |
| 2622 | `.t-light .chip` | surface-overlay | `--chip-bg` (new) or leave |
| 2623 | `.t-light .accordion__body, .prose p/ul/ol` | ivory-500 | `--text-body` (new; ivory-300 in dark if wanted, ivory-500 in light) |
| 2624 | `.t-light .site-header, .toast, .drawer, .modal__panel` | `--shadow-light` | `--shadow-overlay` remapped (then also covers `.nav-mobile` and `.suggest`, which the patch misses) |
| 2625-2627 | `.t-light ::selection` | gold-800 bg, ivory text | `--selection-bg` / `--selection-fg` (new). The dark-ground `::selection` rule lives at critical.css:343. |

### What a site-wide light theme means for these rules

1. **The component patches are descendant selectors.** They only fire under an ancestor with the `t-light` class. If the toggle is implemented as `html[data-theme="light"]` plus a `:root` token remap, none of the 2570-2627 patches apply and fields, tracks, stars, skeletons, badges and shadows stay dark-tuned. Two ways out: (a) the toggle also adds `t-light` to `<body>` (cheap, but it couples theme and band), or (b) the recommended route: convert each patch into a token (the right-hand column above), remap those tokens in both `:root[data-theme="light"]` and `.t-light`, and delete the patches. After (b), `.t-light` is only a token scope, like critical.css 164-187.
2. **The home rhythm band collapses.** On a light page, `#why-us.t-light` is identical to its neighbours and the page loses its dark/ivory cadence. Introduce a contrast-band concept rather than a colour: add `.t-dark` (a scope that restores today's ink token values) and a `.t-band` class the home page uses instead of `.t-light`. `.t-band` resolves to the ivory scope on the dark site and to a deep-ink scope (or, more gently, a warm sand `--ivory-200` band) on the light site. Recommendation: an ink `.t-dark` band for `#why-us` on the light site; a single dark chapter in an ivory page reads as a deliberate editorial moment and keeps the brand's night-time character.
3. **Image-backed components need their own scope.** The collection-card body, the Instagram veil text and the lightbox close button read semantic tokens but sit on dark scrims. Add a `.t-on-image` scope (pinning `--text`, `--text-muted`, `--accent` to ivory-100, ivory-300, gold-400) and put it on those containers. The same `.t-dark` scope would also work.
4. **Admin is unaffected** as long as admin keeps `body.t-light`. Converting patches to tokens (point 1b) keeps the admin identical because `.t-light` will remap the same new tokens.
5. **The print block (2670-2705)** is already a hand-rolled light theme and needs nothing.

## Summary counts

| Class | site.css | motion parts | Total |
|---|---|---|---|
| THEME (needs a token or remap) | 62 (42 component rows + 20 `.t-light` patch rows) | 32 | 94 |
| KEEP (photo, scrim, print, mask, neutral) | 17 | 4 | 21 |
| BRAND (constant gold that works on both) | 1 (`.t-light .btn--primary`) | 2 | 3 |

Counts are per table row, including the `.t-light` patch rows (all THEME) and the pinned on-image rows (KEEP). Rows that already read a correct semantic token are counted as THEME but need no change.

## Hotspots, most visible first

1. `60-transitions.css:10` `--sf-mist` hard-codes `#0A0A0A`: every desktop navigation flashes black on a light site.
2. Gold gradient text (`--grad-gold` via `.text-gold-grad`, hero title `10-hero.css:106`, quiz sigil `50-quiz.css:159`, quiz result title): its champagne stops vanish on ivory; the brand's main headline becomes unreadable.
3. Collection-card body text (`site.css:552-580`) reads `--text`/`--accent` on an ink photo scrim: ink-on-black in light theme.
4. Hero glow (`10-hero.css:45`), hero plate image and WebGL ribbons (additive `ONE, ONE` blend): the hero centrepiece stains or disappears on ivory.
5. `--grad-gold-rule` under section headers (1883), footer crown (1938) and pyramid tiers (1547): rules look broken.
6. Overlays: `--scrim-modal` 0.72 ink (619, 1087) and `--shadow-overlay` 0.55 black on drawer, modal, toast, and unpatched `.nav-mobile` (1243) and `.suggest` (1750).
7. Tracks and meters on ivory-alpha (1036, 1678, 370, 2361) and `.star-input__label` (1708): invisible.
8. Quiz reveal: 0.9 black card shadow (`50-quiz.css:23`), 0.55 gold glow (170), ivory mist (181-183).
9. Story halo `mix-blend-mode: screen` (`30-story.css:85`): no effect on ivory.
10. Product-card sheen sweep, ivory in `soft-light` (`20-cards.css:97-98`): no effect on ivory tiles.
11. Lightbox (1155-1171): panel follows `--surface` while the close chip is a hard-coded ink chip with a semantic icon colour.
12. Nav search data-URI glyph stroke `%239C968C` (1289): low contrast, not themable.
13. `.summary__value--free` gold-300 (245): fails contrast on ivory at checkout.
14. Status tints `rgba(229,115,107,…)` and `rgba(111,191,139,…)` (199, 762, 866, 2381, 2385).
15. Raw ink tokens as UI colours: `.nav-mobile__input` border (1286), timeline idle dot (2164), drawer scrollbar (709), pagination gap (1452).
16. `.cart-line.is-unavailable` `brightness(0.6)` (173).
17. `.t-light` patches are descendant-scoped, and the home `#why-us` band loses its rhythm.
