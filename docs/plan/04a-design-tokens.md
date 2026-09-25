# 04a — Design Foundations: Colour, Type, Space, Motion Surfaces & CSS Architecture

This document defines the **visual substrate** every storefront and admin screen is built
from: the complete colour system as CSS custom properties, an audited contrast table, the
two-family type system with its fluid scale, the spacing/grid/radius conventions, what
replaces shadow on a near-black UI, and how a single hand-written `site.css` stays
navigable at ~3000 lines with no preprocessor and no build step. It is a *specification* —
token names and values here are a cross-stream contract that `03-storefront-pages.md`,
the admin stream and the component stream all consume by name. Nothing in this document is
application code; the CSS blocks are the token declarations themselves, which are the
deliverable. The governing principle throughout is the client's brief: **restraint,
typography, whitespace and motion do the work — not decoration.** Le Labo and Byredo both
win on near-total colour abstinence plus one strong typographic voice; Sky Fragrances
cannot out-decorate them, so it out-disciplines them, spending its one colour accent
(champagne gold) only where it means something.

---

## 0. Ground rules this document inherits

| Constraint (from `00-brief.md`) | Consequence here |
|---|---|
| No Node, no build step, no preprocessor | No SCSS variables, no nesting, no autoprefixer. Everything is CSS custom properties and modern-but-widely-shipped CSS. No `@layer` reliance for correctness (see §6.1). |
| One CSS file, vanilla JS | All tokens live in one `:root` block at the top of `/assets/css/site.css`. No per-page stylesheets, no CSS-in-JS, no utility framework. |
  Superseded by 08-decisions-register.md §2 — C-30: the file is `site.css` (this document originally said `style.css`).
| Deployed by a non-developer | The file must be safe to open and read. Section banners and a table of contents are load-bearing, not decoration. |
| Mobile-first, tested at 375px | The type scale is authored at 375px and grows; the container padding is defined from the small end up. |
| Lighthouse 90+ | Font payload is budgeted in §3.4 and capped. No icon font, no third-party CSS. |
| Money renders `Rs. 4,950` | Prices use a tabular-figure token (§3.7) so column-aligned totals do not jitter. |

**Assumption stated inline:** the brand palette is exactly the three values in the brief —
`#0A0A0A`, `#D4B084`, `#F5F0E8`. Every other colour in this document is *derived* from those
three by a stated rule, so the system can be regenerated if the client ever revises a brand
hex. No fourth brand hue is introduced; success and danger are the only non-brand hues and
they are deliberately desaturated toward the brand's warmth.

---

## 1. Colour system

### 1.1 How the ramps are derived

Three ramps, one rule each — this is what keeps the palette from drifting into invented
colours over a 3000-line stylesheet:

1. **Ink ramp** (from `#0A0A0A`): lighten toward a *warm* grey, not neutral. Each step adds
   roughly +6 to R, +5 to G, +4 to B, so every surface above the page ground carries a trace
   of the gold's warmth. A neutral `#1A1A1A` next to champagne gold reads blue-ish and
   cheap; `#151311` does not.
2. **Gold ramp** (from `#D4B084`): tints move toward ivory (raise all channels, keep the
   R>G>B spread), shades move toward a burnt bronze (drop B fastest). The spread is what
   makes it read as *metal* rather than as mustard.
3. **Ivory ramp** (from `#F5F0E8`): shades move toward the ink ramp's warmth, never toward
   pure grey, so ivory-on-ivory panels stay in the same family.

### 1.2 Primitive tokens

```css
:root {
  /* --- Ink (near-black, warm) ------------------------------------ */
  --ink-900: #0A0A0A;   /* page ground, brand black                  */
  --ink-850: #0F0E0D;   /* sunken wells, inset fields                */
  --ink-800: #141312;   /* card / raised surface on black            */
  --ink-700: #1C1A18;   /* elevated surface: drawer, modal, sheet    */
  --ink-600: #26231F;   /* hover state of a raised surface           */
  --ink-500: #35302A;   /* strong hairline on black, disabled fill   */
  --ink-400: #4A443C;   /* disabled text on black (non-essential)    */

  /* --- Gold (champagne) ------------------------------------------ */
  --gold-100: #F2E6D2;  /* gradient highlight stop                   */
  --gold-200: #E7D2AE;  /* hover / focus lift of gold text           */
  --gold-300: #DCC29B;  /* gradient upper-mid stop                   */
  --gold-400: #D4B084;  /* BRAND GOLD — the reference value          */
  --gold-500: #C29C6E;  /* pressed state                             */
  --gold-600: #A87F4E;  /* gradient lower stop, bronze turn          */
  --gold-700: #8A6538;  /* deep bronze, borders on ivory             */
  --gold-800: #7A5A2E;  /* GOLD INK — the only gold legal as text
                           on ivory; see §2.4                        */

  /* --- Ivory ------------------------------------------------------ */
  --ivory-050: #FBF8F3;  /* lightest panel on an ivory page          */
  --ivory-100: #F5F0E8;  /* BRAND IVORY — light page ground          */
  --ivory-200: #EAE3D7;  /* hairline / divider on ivory              */
  --ivory-300: #D8CFBF;  /* input border on ivory                    */
  --ivory-400: #9C968C;  /* muted text ON BLACK                      */
  --ivory-500: #5A544B;  /* muted text ON IVORY                      */

  /* --- Functional hues (warm-shifted, two variants each) ---------- */
  --success-on-dark:  #6FBF8B;
  --success-on-light: #1F6B3E;
  --danger-on-dark:   #E5736B;
  --danger-on-light:  #A32B22;
}
```

### 1.3 Hairlines, scrims and the gold gradients

Hairlines are the single most important non-typographic device in this design. Luxury
fragrance sites separate content with a **1px line and 120px of air**, never with a box.
Three hairline weights, and no fourth:

```css
:root {
  --rule-faint:  rgba(245, 240, 232, 0.08);  /* section separator on black      */
  --rule:        rgba(245, 240, 232, 0.14);  /* default border on black         */
  --rule-strong: rgba(212, 176, 132, 0.38);  /* gold hairline: active/selected  */
  --rule-light:  var(--ivory-200);           /* separator on an ivory surface   */
  --rule-light-strong: var(--ivory-300);     /* input border on ivory           */
}
```

Scrims exist so ivory text can sit on an uncontrolled product photograph. Every hero,
collection tile and lookbook image gets one — never raw text on an image.

```css
:root {
  /* bottom-anchored, for text sitting at the foot of an image */
  --scrim-bottom: linear-gradient(
      to top,
      rgba(10, 10, 10, 0.86) 0%,
      rgba(10, 10, 10, 0.62) 28%,
      rgba(10, 10, 10, 0.16) 62%,
      rgba(10, 10, 10, 0) 100%);
  /* full-bleed, for centred hero copy */
  --scrim-full:   linear-gradient(
      to bottom,
      rgba(10, 10, 10, 0.55) 0%,
      rgba(10, 10, 10, 0.35) 45%,
      rgba(10, 10, 10, 0.70) 100%);
  /* behind drawers and modals */
  --scrim-modal:  rgba(10, 10, 10, 0.72);
  /* hover veil on a product card image */
  --scrim-hover:  rgba(10, 10, 10, 0.18);
}
```

**The gold gradients.** A flat `#D4B084` fill reads as beige. Metal reads as metal only when
the gradient has an *asymmetric* highlight — a narrow bright band nearer one end, not a
centred 50% stop. These four are the complete set; nothing else may invent a gold gradient.

```css
:root {
  /* 1. Primary — text/icon fill on black (logo lockup, wordmark, display numerals).
        Diagonal, highlight at 38%, bronze turn at 82%. */
  --grad-gold: linear-gradient(118deg,
      #A87F4E 0%, #D4B084 22%, #F2E6D2 38%, #DCC29B 56%,
      #C29C6E 74%, #8A6538 100%);

  /* 2. Rule — the 1px metallic divider under section headers.
        Fades to nothing at both ends so it never looks like a box edge. */
  --grad-gold-rule: linear-gradient(90deg,
      rgba(212,176,132,0) 0%, rgba(212,176,132,0.10) 8%,
      rgba(242,230,210,0.85) 42%, rgba(212,176,132,0.55) 58%,
      rgba(212,176,132,0.10) 92%, rgba(212,176,132,0) 100%);

  /* 3. Border — for the 1px gradient frame on primary buttons and the
        selected size chip (applied via border-image). */
  --grad-gold-border: linear-gradient(135deg,
      #8A6538 0%, #D4B084 28%, #F2E6D2 50%, #D4B084 72%, #8A6538 100%);

  /* 4. Sheen — the slow highlight sweep on hover of a primary button.
        Translated across the element, never animated as a background-position. */
  --grad-gold-sheen: linear-gradient(105deg,
      rgba(242,230,210,0) 0%, rgba(242,230,210,0) 38%,
      rgba(242,230,210,0.32) 50%, rgba(242,230,210,0) 62%,
      rgba(242,230,210,0) 100%);
}
```

**Rule for gradient-filled text:** `background-image: var(--grad-gold);
background-clip: text; color: transparent;` — always with `color` first set to
`var(--gold-400)` as the pre-`background-clip` fallback, and **never** below 28px. Below
that size the gradient banding is visible and the effective contrast drops below the audited
figure for flat gold. See §2.5.

### 1.4 Semantic tokens

Components reference **only** these. A component that reaches for `--gold-400` directly is a
bug; it reaches for `--accent`. This is what makes the eventual ivory-background admin
screens and the black storefront share one stylesheet.

```css
:root {
  /* dark context — the storefront default */
  --surface:          var(--ink-900);
  --surface-raised:   var(--ink-800);
  --surface-overlay:  var(--ink-700);
  --surface-sunken:   var(--ink-850);
  --surface-hover:    var(--ink-600);

  --text:             var(--ivory-100);
  --text-muted:       var(--ivory-400);
  --text-disabled:    var(--ink-400);
  --text-inverse:     var(--ink-900);

  --border:           var(--rule);
  --border-faint:     var(--rule-faint);
  --border-accent:    var(--rule-strong);

  --accent:           var(--gold-400);
  --accent-hover:     var(--gold-200);
  --accent-active:    var(--gold-500);
  --accent-quiet:     rgba(212, 176, 132, 0.12);  /* accent surface wash */

  --success:          var(--success-on-dark);
  --danger:           var(--danger-on-dark);

  --focus-ring:       var(--gold-400);
  --price:            var(--gold-400);
  --price-was:        var(--ivory-400);
}

/* light context — opt-in, applied to a wrapper, never to :root.
   Used by: admin panel body, invoice/packing-slip print view, the
   "Why Choose Us" ivory band, and the email templates' inlined copy. */
.t-light {
  --surface:          var(--ivory-100);
  --surface-raised:   var(--ivory-050);
  --surface-overlay:  #FFFFFF;
  --surface-sunken:   var(--ivory-200);
  --surface-hover:    var(--ivory-200);

  --text:             var(--ink-900);
  --text-muted:       var(--ivory-500);
  --text-disabled:    #8F877B;
  --text-inverse:     var(--ivory-100);

  --border:           var(--rule-light);
  --border-faint:     rgba(10, 10, 10, 0.06);
  --border-accent:    var(--gold-700);

  --accent:           var(--gold-800);   /* NOT gold-400 — see §2.4 */
  --accent-hover:     var(--gold-700);
  --accent-active:    var(--gold-800);
  --accent-quiet:     rgba(122, 90, 46, 0.10);

  --success:          var(--success-on-light);
  --danger:           var(--danger-on-light);

  --focus-ring:       var(--gold-800);
  --price:            var(--ink-900);
  --price-was:        var(--ivory-500);
}
```

**Decision:** no `prefers-color-scheme` handling. The brand *is* dark; a light-mode
storefront would be a different brand. `.t-light` is a context class, not a user preference.

---

## 2. Contrast audit

Ratios computed with the WCAG 2.1 relative-luminance formula (sRGB, 8-bit, `((c/255 + 0.055)
/ 1.055) ^ 2.4` per channel; L = 0.2126R + 0.7152G + 0.0722B; ratio = (L₁+0.05)/(L₂+0.05)).
Thresholds: **AA body** ≥ 4.5:1; **AA large** (≥24px regular or ≥18.66px semibold) ≥ 3:1;
**AA non-text** (UI borders, focus rings, icon strokes, meter fills) ≥ 3:1.

Reference luminances used throughout: `#0A0A0A` L=0.0030 · `#141312` L=0.0072 ·
`#1C1A18` L=0.0116 · `#F5F0E8` L=0.8756 · `#D4B084` L=0.4671.

### 2.1 Text on the black ground (`--surface` = `#0A0A0A`)

| Fg | Hex | Usage | Ratio | AA body | AA large |
|---|---|---|---|---|---|
| `--text` ivory-100 | `#F5F0E8` | all body copy, H1–H6, nav, buttons | **17.45:1** | PASS | PASS |
| `--text-muted` ivory-400 | `#9C968C` | size lists, "was" prices, meta, captions | **6.75:1** | PASS | PASS |
| `--accent` gold-400 | `#D4B084` | prices, eyebrows, links, active nav | **9.75:1** | PASS | PASS |
| `--accent-hover` gold-200 | `#E7D2AE` | link/price hover | **13.41:1** | PASS | PASS |
| `--accent-active` gold-500 | `#C29C6E` | pressed link | **7.98:1** | PASS | PASS |
| gold-600 | `#A87F4E` | gradient stop only, not flat text | **5.48:1** | PASS | PASS |
| gold-700 | `#8A6538` | gradient stop only | **3.55:1** | FAIL | PASS |
| `--success` | `#6FBF8B` | "In stock", order-delivered pill | **8.96:1** | PASS | PASS |
| `--danger` | `#E5736B` | "Sold out", form errors, cancelled pill | **6.60:1** | PASS | PASS |
| `--text-disabled` ink-400 | `#4A443C` | disabled button label | **1.83:1** | FAIL (intentional) | FAIL |

### 2.2 Text on raised dark surfaces

Raised surfaces are only 2–4 luminance points above the ground, so every ratio above drops by
less than 4%. Worst case is `--surface-overlay` `#1C1A18` (cart drawer, modal, mobile menu):

| Fg on `#1C1A18` | Ratio | AA body |
|---|---|---|
| `--text` `#F5F0E8` | **14.79:1** | PASS |
| `--text-muted` `#9C968C` | **5.72:1** | PASS |
| `--accent` `#D4B084` | **8.26:1** | PASS |
| `--danger` `#E5736B` | **5.60:1** | PASS |
| `--success` `#6FBF8B` | **7.60:1** | PASS |

No pair falls below 4.5:1 anywhere in the dark theme. **This is the whole point of keeping
the ink ramp inside a 20-point luminance band** — it means a component can be moved from the
page ground onto a drawer without re-auditing.

### 2.3 Text on the ivory ground (`.t-light`, `#F5F0E8`)

| Fg | Hex | Usage | Ratio | AA body |
|---|---|---|---|---|
| `--text` ink-900 | `#0A0A0A` | admin body, invoice copy, ivory-band headings | **17.45:1** | PASS |
| `--text-muted` ivory-500 | `#5A544B` | admin helper text, table meta | **6.60:1** | PASS |
| `--accent` gold-800 | `#7A5A2E` | admin links, ivory-band eyebrow | **5.56:1** | PASS |
| `--accent-hover` gold-700 | `#8A6538` | link hover on ivory | **4.78:1** | PASS |
| `--success-on-light` | `#1F6B3E` | "Paid", "Delivered" | **5.72:1** | PASS |
| `--danger-on-light` | `#A32B22` | validation errors, "Cancelled" | **6.33:1** | PASS |

### 2.4 The gold failure — stated plainly

| Pair | Ratio | Verdict |
|---|---|---|
| gold-400 `#D4B084` on ivory-100 `#F5F0E8` | **1.79:1** | **FAIL — fails AA body AND AA large AND the 3:1 non-text threshold.** |
| gold-400 on ivory-050 `#FBF8F3` | **1.87:1** | FAIL |
| gold-300 `#DCC29B` on ivory-100 | **1.53:1** | FAIL |

**Rule (binding): brand gold `#D4B084` is never used on an ivory or white background — not
as text, not as an icon stroke, not as a border, not at any size.** It is a *dark-context*
colour. On ivory:

- text and icons use `--gold-800` `#7A5A2E` (5.56:1, passes body);
- borders and rules use `--gold-700` `#8A6538` (4.78:1 against ivory, clears the 3:1
  non-text bar with margin);
- a gold *fill* on ivory (e.g. a gold-filled button in the admin) must carry ink-900 text,
  which is 17.45:1 on gold-400 — that direction is fine and is the correct way to use brand
  gold in a light context.

Conversely `--gold-800` on black is **3.14:1** — fails body, passes large/non-text only. It
must not travel back into the dark theme as text.

### 2.5 Gradient-filled text

`--grad-gold` spans `#8A6538` (3.55:1 on black) to `#F2E6D2` (14.9:1). WCAG has no defined
method for gradient text, so the governing rule is the **worst stop**: 3.55:1, which clears
AA-large only. Therefore:

- gradient gold text is permitted **only** at ≥28px and only in the `step-display-*` and
  `step-h1` type steps (§3.5) — the wordmark, hero headline, and the section numeral;
- it is **forbidden** on any price, button label, link, form label, badge or body copy;
- every gradient-text element sets flat `color: var(--gold-400)` first, so a browser without
  `background-clip: text` renders a 9.75:1 fallback rather than transparent text.

### 2.6 Non-text contrast

| Element | Colour | Against | Ratio | ≥3:1 |
|---|---|---|---|---|
| Focus ring | `--gold-400` | `#0A0A0A` | 9.75:1 | PASS |
| Focus ring (light) | `--gold-800` | `#F5F0E8` | 5.56:1 | PASS |
| Input border, rest | `--rule` (ivory @14%) on `#0A0A0A` | — | **1.63:1** | **FAIL** |
| Input border, rest (fix) | `--ink-500` `#35302A` + a 1px `--rule-strong` inner edge on focus/error | — | see note | — |
| Selected size chip border | `--rule-strong` (gold @38%) | `#0A0A0A` | **2.42:1** | **FAIL as sole indicator** |
| Meter fill (longevity/sillage) | `--gold-400` | `--ink-600` track | 7.66:1 | PASS |
| Star glyph | `--gold-400` | `#0A0A0A` | 9.75:1 | PASS |

**Consequence — two binding rules.** (a) A form field's *resting* border is decorative; the
field's affordance comes from its label and its `--surface-sunken` fill, and its
focus/error/valid states use a full-opacity 1px `--gold-400` / `--danger` border plus a 2px
offset ring, both above 3:1. (b) Selection state is **never carried by a gold hairline
alone** — the selected size chip additionally gets `--accent-quiet` fill and
`aria-pressed="true"`, and the active filter pill gets a solid gold 2px left edge. Colour is
never the only channel, anywhere.

### 2.7 Focus

One visible focus treatment sitewide, on `:focus-visible` only:

```css
:where(a, button, input, select, textarea, summary, [tabindex]):focus-visible {
  outline: 1px solid var(--focus-ring);
  outline-offset: 3px;
  border-radius: 0;
}
```

Never `outline: none` without a replacement. The 3px offset is what makes a 1px ring legible
on black — the gap does the work a thicker ring would, and stays in the design's hairline
vocabulary.

---

## 3. Type system

### 3.1 The two families and what each is for

| Family | Role | Why |
|---|---|---|
| **Cormorant Garamond** | display: H1–H3, product names, prices, pull quotes, the numeral in section headers | A high-contrast Garamond revival with very fine hairlines. It looks expensive at 48px and thin-to-the-point-of-broken at 14px, which is exactly the discipline this brand needs: it *forces* restraint by being unusable for body text. |
| **Jost** | body: paragraphs, nav, buttons, labels, forms, tables, admin, all UI | A geometric sans in the Futura lineage. Its wide-tracked uppercase is the single strongest typographic tell of the luxury-fragrance category (the Le Labo / Byredo / Aesop register), and it is the family already named in the brief. |

**Binding rule:** Cormorant is never used below 20px and never for any interactive label.
Jost is never used for a product name or a headline. Two families, two jobs, no overlap —
that boundary is most of what makes the design look considered.

### 3.2 Weights loaded — and the ones deliberately refused

| Family | Weights | Styles | Justification |
|---|---|---|---|
| Cormorant Garamond | 300 (Light), 400 (Regular) | roman only | Light for display ≥40px, Regular for 20–39px. **No italic** — a fragrance site has no use for it and it costs ~30KB. **No 600/700** — a bold Garamond reads as a book jacket, not a boutique; emphasis in the display family is achieved by size, not weight. |
| Jost | 300 (Light), 400 (Regular), 500 (Medium) | roman only | 300 for large intro paragraphs, 400 for body/UI, 500 for buttons, active nav and the tracked uppercase eyebrow. **No 600/700** — Jost 500 at 0.16em tracking is already emphatic; bolder breaks the register. **No italic.** |

Total: **5 font files**, woff2, Latin subset only.

### 3.3 Loading

Self-hosting is the correct choice here — the client can upload five `.woff2` files through
File Manager as easily as any image, it removes a third-party DNS lookup and TLS handshake
from the critical path, and it removes a third-party dependency from a site that has none.
**Decision: self-host under `/assets/fonts/`, with the Google Fonts CDN documented only as
the fallback if the client ever loses the files.** The brief's mention of Google Fonts is
satisfied — the *typefaces* are the Google Fonts releases, downloaded once.

```html
<link rel="preload" href="/assets/fonts/jost-400.woff2" as="font"
      type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/cormorant-garamond-300.woff2" as="font"
      type="font/woff2" crossorigin>
```

Preload exactly those two — the two that paint above the fold on the home page. Preloading
all five would contend with the hero image for bandwidth and is a net Lighthouse loss.

```css
@font-face {
  font-family: 'Jost';
  src: url('/assets/fonts/jost-400.woff2') format('woff2');
  font-weight: 400; font-style: normal;
  font-display: swap;
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6,
                 U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191,
                 U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* …four more blocks, identical shape… */
```

`font-display: swap` on all five. The fallback stacks are chosen so the swap moves as little
as possible:

```css
--font-display: 'Cormorant Garamond', 'Hoefler Text', Constantia, 'Times New Roman',
                Georgia, serif;
--font-body:    'Jost', 'Helvetica Neue', Arial, 'Segoe UI', system-ui, sans-serif;
```

**If the CDN path is ever used instead**, it needs both hosts and both hints, in this order,
before the stylesheet link:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400&family=Jost:wght@300;400;500&display=swap">
```

The `crossorigin` on the `gstatic` preconnect is not optional — without it the browser opens
a second connection and the hint is wasted.

### 3.4 Payload budget

| File | Budget |
|---|---|
| jost-300.woff2 | ≤ 22 KB |
| jost-400.woff2 | ≤ 22 KB |
| jost-500.woff2 | ≤ 22 KB |
| cormorant-garamond-300.woff2 | ≤ 26 KB |
| cormorant-garamond-400.woff2 | ≤ 26 KB |
| **Total font payload** | **≤ 118 KB, hard cap** |

Latin subset only (no latin-ext, no Cyrillic, no Vietnamese) — the site is English-only in
v1 per `03-storefront-pages.md` §1.4. Adding a sixth weight requires removing one; the cap
does not move. Fonts are served with `Cache-Control: public, max-age=31536000, immutable`
from `/assets/.htaccess`, and filenames are versioned by hand (`jost-400.v2.woff2`) on the
rare occasion a file changes.

### 3.5 The fluid scale

Authored mobile-first with `clamp()`, so there are **no font-size media queries anywhere in
the stylesheet**. Each step names its family, weight, tracking and leading together — they
are one decision, not four.

| Token | clamp() | Family / wt | Tracking | Line-height | Used for |
|---|---|---|---|---|---|
| `--step-display` | `clamp(2.75rem, 1.6rem + 5.6vw, 5.5rem)` | Cormorant 300 | `-0.02em` | `1.02` | Home hero headline only |
| `--step-h1` | `clamp(2.125rem, 1.45rem + 3.2vw, 3.5rem)` | Cormorant 300 | `-0.015em` | `1.08` | Page H1, PDP product name |
| `--step-h2` | `clamp(1.625rem, 1.25rem + 1.8vw, 2.5rem)` | Cormorant 400 | `-0.01em` | `1.15` | Section headers |
| `--step-h3` | `clamp(1.25rem, 1.1rem + 0.7vw, 1.625rem)` | Cormorant 400 | `0` | `1.25` | Card group titles, drawer title |
| `--step-h4` | `clamp(1.0625rem, 1rem + 0.3vw, 1.25rem)` | Cormorant 400 | `0` | `1.3` | Product card name, accordion head |
| `--step-lead` | `clamp(1.0625rem, 1rem + 0.35vw, 1.25rem)` | Jost 300 | `0.005em` | `1.7` | Intro paragraph under an H1 |
| `--step-body` | `clamp(0.9375rem, 0.9rem + 0.18vw, 1rem)` | Jost 400 | `0.01em` | `1.72` | Default body, `<p>`, table cells |
| `--step-small` | `0.875rem` | Jost 400 | `0.015em` | `1.6` | Meta, helper text, "was" price |
| `--step-micro` | `0.75rem` | Jost 400 | `0.02em` | `1.5` | Legal, footnotes, badge counts |
| `--step-eyebrow` | `0.6875rem` | Jost 500 | `0.22em` | `1.4` | Eyebrows, section labels — UPPERCASE |
| `--step-label` | `0.75rem` | Jost 500 | `0.14em` | `1.4` | Form labels, tab labels — UPPERCASE |
| `--step-button` | `0.8125rem` | Jost 500 | `0.16em` | `1` | All button labels — UPPERCASE |
| `--step-price` | `clamp(1.125rem, 1rem + 0.6vw, 1.5rem)` | Cormorant 400 | `0` | `1.2` | PDP price |
| `--step-price-sm` | `1rem` | Cormorant 400 | `0` | `1.2` | Card price, cart line |

Ratio is roughly 1.22 at 375px widening to 1.28 at 1440px — the display end stretches faster
than the body end, which is why the page feels dramatic on desktop and still readable on a
phone. `--step-body` never drops below 15px; `--step-micro` is the floor at 12px and is
never used for anything a customer must read to transact.

### 3.6 The wide-tracked uppercase treatment

This is the logo lockup's voice ("SKY FRAGRANCES" set wide under the SF monogram) carried
through the whole interface, and it is the design's signature. One mixin-by-convention,
applied by class:

```css
.u-track {
  font-family: var(--font-body);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: var(--track-wide);   /* 0.22em */
  font-size: var(--step-eyebrow);
  line-height: 1.4;
  /* letter-spacing adds trailing space after the last glyph; pull it back
     so the text optically aligns to the grid */
  margin-right: calc(var(--track-wide) * -1);
}
```

```css
:root {
  --track-tight:  -0.02em;  /* display sizes only */
  --track-normal:  0;
  --track-body:    0.01em;  /* Jost needs a hair of positive tracking at 15–16px */
  --track-label:   0.14em;
  --track-button:  0.16em;
  --track-wide:    0.22em;  /* eyebrows, the wordmark */
  --track-widest:  0.32em;  /* the wordmark under the monogram, ≥18px only */
}
```

**Rules:** tracked uppercase is never longer than four words and never wraps to a third line;
it is never used for a sentence; `--track-widest` is legal only at ≥18px because at smaller
sizes the word disintegrates into letters. Every tracked-uppercase element compensates the
trailing letter-space with the negative margin above — without it, a right-aligned "VIEW ALL"
sits visibly inboard of the grid and the whole layout looks slightly off, which is the exact
kind of error that separates this from the competitors named in the brief.

### 3.7 Numerals

```css
:root { --num-tabular: "tnum" 1, "lnum" 1; }
.price, .qty, td.num, .order-total { font-feature-settings: var(--num-tabular); }
```

Prices, quantities, order totals and every admin table column of figures use tabular lining
figures so `Rs. 4,950` and `Rs. 12,400` align on the decimal in a stacked cart summary.
Cormorant's default figures are oldstyle and will bounce without this.

---

## 4. Space, layout, radius and borders

### 4.1 Spacing scale

A 4px base on a modified-doubling ramp. Twelve steps, no arbitrary pixel values anywhere in
the stylesheet — if a value is not on this scale, the layout is wrong, not the scale.

```css
:root {
  --sp-1: 0.25rem;  /*  4px  icon gap                                */
  --sp-2: 0.5rem;   /*  8px  label→field                             */
  --sp-3: 0.75rem;  /* 12px  grid gutter (mobile), chip padding      */
  --sp-4: 1rem;     /* 16px  page gutter (mobile), stack rhythm      */
  --sp-5: 1.5rem;   /* 24px  card padding, grid gutter (desktop)     */
  --sp-6: 2rem;     /* 32px  between related blocks                  */
  --sp-7: 3rem;     /* 48px  block→block                             */
  --sp-8: 4rem;     /* 64px  section padding (mobile)                */
  --sp-9: 6rem;     /* 96px  section padding (tablet)                */
  --sp-10: 8rem;    /* 128px section padding (desktop) — the air     */
  --sp-11: 10rem;   /* 160px hero breathing, editorial break         */
  --sp-12: 12rem;   /* 192px the one deliberate void per page        */

  --section-y: clamp(var(--sp-8), 4rem + 6vw, var(--sp-10));
  --gutter:    clamp(var(--sp-4), 1rem + 1.5vw, var(--sp-6));
}
```

`--section-y` is the whitespace budget the brief asks for. At 1440px it resolves to 128px of
vertical padding per section — roughly double what a generic e-commerce theme uses, and the
single change that most makes a page read as luxury. Sections alternate `--surface` and
`--surface-raised` with no border between them; the *air* is the separator.

### 4.2 Containers

```css
:root {
  --w-prose:  38rem;    /* 608px — legal, about, FAQ body. ~68ch at 16px */
  --w-narrow: 52rem;    /* 832px — forms, checkout, track-order          */
  --w-page:   75rem;    /* 1200px — default grid container               */
  --w-wide:   90rem;    /* 1440px — lookbook, full-bleed-with-margin     */
}
.container { width: 100%; max-width: var(--w-page);
             margin-inline: auto; padding-inline: var(--gutter); }
```

Hero and collection tiles are edge-to-edge (`100vw`, no container). Everything else is
containered. Checkout uses `--w-narrow` deliberately: a wide checkout form feels like a form,
a narrow one feels like a letter.

### 4.3 Breakpoints

Four, min-width only, matching the grid already specified in `03-storefront-pages.md` §1.3.

```css
/* --bp-sm: 480px   large phone: 2-up stays, type nudges       */
/* --bp-md: 768px   tablet: 3-col product grid, footer unstacks */
/* --bp-lg: 1024px  desktop nav replaces hamburger, sticky bits */
/* --bp-xl: 1200px  4-col product grid, mega-panel              */
```

Custom properties cannot be used in media queries, so these live as comments beside the one
`@media` block group in §6.2 and as literals. There are exactly four literals in the file;
any fifth is a bug. 375px is the *design* width, not a breakpoint — it is below `--bp-sm` and
gets the base styles.

### 4.4 Grid

```css
--grid-cols: 12;
--grid-gap:  clamp(var(--sp-3), 0.75rem + 1vw, var(--sp-5));
```

Product grid: 2 / 3 / 4 columns at base / `md` / `xl`, per §1.3 of the storefront doc, via
`grid-template-columns: repeat(auto-fill, minmax(...))` only where the count is not fixed;
fixed counts are written explicitly so an orphan card never stretches. Product imagery is
**4:5** everywhere (`aspect-ratio: 4/5`), hero is `3:4` on mobile and `16:9` from `md` up —
declared as `aspect-ratio` so no CLS is possible.

### 4.5 Radius and borders

```css
:root {
  --radius-0: 0;        /* everything                              */
  --radius-pill: 999px; /* cart count bubble, stock pill ONLY      */
  --border-hair: 1px;
  --border-emphasis: 2px;
}
```

**Decision: the brand is sharp-edged. `--radius-0` is the default on every surface, button,
card, input, drawer, modal and image container.** Justification, since this is the choice
most likely to be questioned: rounded corners are a softness signal borrowed from consumer
software, and the entire luxury-fragrance category — Le Labo, Byredo, Aesop, Diptyque —
renders squared. The physical product references this too: a perfume bottle's shoulder is a
cut edge. A 4px radius would not read as "friendly", it would read as a default nobody
changed. The two exceptions are genuinely circular affordances (the cart count bubble, the
in-stock dot), where a pill is the shape of the thing rather than a softening of a rectangle.

Borders are `1px` and carry colour, never weight — emphasis comes from swapping
`--border` → `--border-accent`, not from thickening. `--border-emphasis` exists solely for
the 2px left edge on an active filter pill (the redundant non-colour selection cue from
§2.6).

---

## 5. Elevation on a near-black UI

A `box-shadow` on `#0A0A0A` is invisible: there is no darker colour to cast. Spending effort
on shadow tokens here would produce tokens that do nothing. **Elevation is therefore
expressed by three non-shadow channels, in this priority order.**

1. **Surface lightness** — the primary channel. Each level up the stack moves one step up the
   ink ramp. Because the whole ramp sits inside a 20-point luminance band, this reads as
   *layering* rather than as a different colour.

   | Level | Token | What sits here |
   |---|---|---|
   | 0 | `--surface` `#0A0A0A` | page ground |
   | 1 | `--surface-raised` `#141312` | product card, panel, ivory-band alternate |
   | 2 | `--surface-overlay` `#1C1A18` | cart drawer, modal, mobile menu, size sheet |
   | 3 | `--surface-hover` `#26231F` | hovered row inside a level-2 surface |
   | −1 | `--surface-sunken` `#0F0E0D` | input fields, meter tracks, code/well |

2. **Hairline + scrim** — an overlay at level 2 gets a `--rule` 1px edge on the side it meets
   the page, and the page behind it gets `--scrim-modal` (`rgba(10,10,10,0.72)`). The scrim,
   not a shadow, is what detaches the drawer. Add `backdrop-filter: blur(2px)` as a
   progressive enhancement only — it is a known performance trap on low-end Android, which is
   most of this audience, so it is capped at 2px and dropped entirely under
   `prefers-reduced-motion`.

3. **Glow, used once** — the only "shadow" token in the file is a gold bloom, and it exists
   for the primary CTA's hover and nothing else:

```css
:root {
  --shadow-none: none;
  --glow-accent: 0 0 0 1px rgba(212,176,132,0.45), 0 8px 32px rgba(212,176,132,0.10);
  --shadow-light: 0 1px 2px rgba(10,10,10,0.06), 0 8px 24px rgba(10,10,10,0.08);
}
```

`--shadow-light` is for `.t-light` contexts only (admin cards, printed invoice), where a real
shadow does read. **There is no `--shadow-sm/md/lg` scale.** Inventing one would be cargo
cult: on this UI it would be five tokens that all render as nothing.

Sticky elements (header, mobile add-to-cart bar) separate from scrolled content with a 1px
`--rule` edge plus a 2px `--scrim-bottom` fade, not a shadow.

---

## 6. How the one CSS file is organised

`/assets/css/site.css`, hand-written, ~3000 lines, no preprocessor, no minifier. Everything
below exists to answer one question: *six months from now, can someone find the rule for the
size chip in under thirty seconds?*

### 6.1 Layer order

Cascade layers are declared once at the top. Every subsequent rule lives inside a layer, so
specificity fights become impossible and the file can be read in any order.

```css
@layer tokens, reset, base, layout, components, utilities, overrides;
```

| Layer | Contents | Lines (est.) |
|---|---|---|
| `tokens` | the `:root` and `.t-light` blocks from §1, §3, §4, §5 | ~180 |
| `reset` | box-sizing, margin zeroing, `img { display:block; max-width:100% }`, form inheritance, `prefers-reduced-motion` global | ~90 |
| `base` | element defaults: `body`, headings mapped to the §3.5 steps, `p`, `a`, `ul`, `table`, `hr`, focus ring | ~200 |
| `layout` | `.container`, the grids, `.section`, page-level scaffolds, the four `@media` groups | ~250 |
| `components` | the bulk — one block per component, alphabetical | ~1900 |
| `utilities` | the small closed set from §6.4 | ~150 |
| `overrides` | print stylesheet, `.t-light` component deltas, the 3–4 genuine exceptions | ~120 |

**Safari support note:** `@layer` has shipped everywhere relevant since 2022, but the file is
written so that *if layers were stripped entirely the cascade would still be correct* —
selectors are single-class, in layer order, with no `!important` and no ID selectors. Layers
are insurance, not load-bearing. That is the one place this document accepts redundancy.

### 6.2 Physical file order and navigation

```
/* ============================================================
   SKY FRAGRANCES — site.css
   1. TOKENS      2. RESET       3. BASE        4. LAYOUT
   5. COMPONENTS  6. UTILITIES   7. OVERRIDES
   Search for "§5.12" to jump to a component.
   ============================================================ */
```

Every section and every component opens with a banner in a fixed shape, because the client's
editor is hPanel's File Manager, which has no outline view and no fuzzy finder — plain text
search is the only navigation available:

```css
/* -- §5.12 · PRODUCT CARD ------------------------------------ */
```

Components are **alphabetical inside `components`**, not grouped by page. Grouping by page
fails the moment a component appears on two pages, which every one of them does.

Media queries are **not** grouped at the bottom. Each component's responsive rules sit
immediately under that component, inside its banner. The cost is repeated `@media` at-rules
(~40 of them, a few hundred bytes gzipped); the benefit is that a component is one contiguous
block you can read and delete as a unit. With no build step to hoist them, contiguity wins.

### 6.3 Naming convention

**Decision: a flattened BEM — `block__element--modifier`, single-class selectors only, plus a
small prefixed set for non-BEM concerns.**

```
.product-card                  block
.product-card__media           element
.product-card__title           element
.product-card--compact         modifier
.is-sold-out / .is-open        state (JS toggles these, CSS never owns them)
.has-scrim                     compound condition
.u-track / .u-sr-only          utility (§6.4)
.t-light                       theme context (§1.4)
.js-add-to-cart                JS hook — NEVER styled
```

Why BEM over utility classes or plain nesting-by-descendant: utilities (Tailwind-style)
require a build step to stay small and are unreadable in a File Manager editor;
descendant selectors (`.product-card .title`) make specificity creep inevitable and make a
component unsafe to move. BEM's verbosity is the price of a flat 0-1-0 specificity across the
entire file, which is what lets a non-expert edit it without breaking something two sections
away. The rule is absolute: **every selector in `components` is exactly one class.** Any
exception is a design error, not a CSS problem.

Three supporting rules:
- **State classes are prefixed `is-`/`has-` and are always paired with an ARIA attribute** —
  `.is-open` on the drawer accompanies `aria-hidden="false"`. If the class and the ARIA state
  can disagree, the ARIA state is the bug.
- **JS hooks are prefixed `js-` and carry no styles.** Renaming a visual class then never
  breaks behaviour.
- **No element selectors inside `components`** — `.cart-line__qty`, never `.cart-line input`.

### 6.4 The closed utility set

Utilities are a slippery slope, so the set is closed and listed here in full. Adding one
requires editing this document.

```css
.u-track       /* §3.6 tracked uppercase                  */
.u-sr-only     /* visually hidden, screen-reader exposed  */
.u-container   /* alias of .container for one-off wrappers*/
.u-stack       /* > * + * { margin-block-start: var(--stack, var(--sp-4)) } */
.u-cluster     /* flex wrap + gap: var(--cluster, var(--sp-3))              */
.u-truncate    /* single-line ellipsis                    */
.u-clamp-2     /* 2-line clamp — product names            */
.u-no-scrollbar/* the RAIL treatment, §1.3 of doc 03      */
.u-hide-md-up  /* the only two responsive-visibility      */
.u-hide-md-down/*   utilities that exist                  */
```

`.u-stack` and `.u-cluster` carry most of the layout work that would otherwise become
hundreds of one-off margin rules; both take a local custom property so a component can retune
the gap without a new class.

### 6.5 What keeps it maintainable at 3000 lines

1. **Tokens are the only place a literal value appears.** A hex, a px size or a duration
   anywhere below `@layer tokens` is a review failure. This is checkable with one grep.
2. **One component = one contiguous block, banner to banner**, including its media queries,
   its `.t-light` delta if small, and its motion. Deleting a feature is deleting a block.
3. **Flat specificity (0-1-0 everywhere, zero `!important`)** means the file has no ordering
   traps; a rule's position never changes its meaning.
4. **Alphabetical components + a fixed banner shape** means plain-text search is a working
   index in an editor with no tooling.
5. **A closed utility set and a closed spacing/type scale** cap the growth rate: new screens
   compose existing tokens rather than adding CSS. The stylesheet grows sub-linearly with the
   number of pages, which is the only way a hand-written file survives an admin panel being
   bolted on later.
6. **Motion is centralised** — three duration and two easing tokens, one global
   `prefers-reduced-motion` block in `reset` that disables transform/opacity transitions
   sitewide, so no component has to remember:

```css
:root {
  --dur-fast: 150ms;  --dur-base: 280ms;  --dur-slow: 600ms;
  --ease-out:  cubic-bezier(0.16, 1, 0.3, 1);      /* UI: drawers, menus   */
  --ease-soft: cubic-bezier(0.33, 0, 0.15, 1);     /* editorial: reveals   */
}
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important; animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important; scroll-behavior: auto !important;
  }
}
```

That block is the file's only `!important`, and it is the correct use of it.

---

## 7. Cross-stream contract

Other documents may rely on these names being stable: every custom property in §1.2, §1.3,
§1.4, §3.3, §3.5, §3.6, §4.1, §4.2, §4.5, §5 and §6.5; the class names `.t-light`, `.u-*` in
§6.4, and the `block__element--modifier` / `is-` / `js-` conventions in §6.3. Anything else
here is guidance. Two rules are non-negotiable and should be treated as acceptance criteria:
**brand gold never appears as text or as a border on an ivory or white background** (§2.4),
and **gradient gold text never appears below 28px** (§2.5).
