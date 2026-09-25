# 04b — Component & Motion Specifications

This document specifies every interface component of the Sky Fragrances storefront and
admin panel: its anatomy, its complete state set (default, hover, focus-visible, active,
disabled, loading, error), and its behaviour at 375px. It then specifies the motion
system, the imagery art direction, the non-negotiable accessibility rules, and the total
JavaScript budget. It is written against the design tokens in `04a-design-tokens.md`;
where this document names a token that 04a does not define, **04a wins and the name here
must be corrected to match it**. Nothing here is application code — it is the contract a
single hand-written `assets/css/site.css` and a small set of vanilla JS files must
satisfy on plain PHP 8.2 with no build step.

---

## 0. What this document assumes

### 0.1 Inherited hard constraints

| Constraint | Consequence for components |
|---|---|
| One CSS file, hand-written, no preprocessor | Every component is a flat BEM-ish block (`.sf-btn`, `.sf-btn--ghost`, `.sf-btn.is-loading`). No nesting-dependent selectors, no `@layer`, no CSS modules. |
| No bundler, no npm | No component library, no icon package. Icons are **inline SVG partials** in `app/partials/icon.php` (08 C-34), drawn at 24×24 on a 24-unit viewBox, `currentColor` only. |
| Vanilla JS only | No framework re-render. Every component renders correct and usable **server-side**; JS only enhances. |
| Non-developer deployment | No component may require a build artefact (no sprite sheet generation, no critical-CSS extraction step). Critical CSS, if used, is a hand-maintained `<style>` block in the layout. |
| Money is `DECIMAL(10,2)` PKR, displayed `Rs. 4,950` | Every price element uses the same `.sf-price` structure so alignment and tabular figures are consistent everywhere. |

### 0.2 Token contract (cross-stream)

These token names are used throughout this document. They are the contract with
`04a-design-tokens.md` and with the CSS stream.

> Superseded by 08-decisions-register.md §2 — C-33: 04a's token names (`--ink-*`, `--gold-*`, `--ivory-*`, `--surface*`, `--text*`, `--accent*`, `--sp-*`, `--dur-*`, `--ease-*`, `--radius-*`) are canonical; read every `--sf-*` below as an alias for the matching 04a semantic token and do not emit `--sf-*` in `site.css`. Easing values, z-index ladder and class contract here are unchanged.

```
Colour     --sf-ink            #0A0A0A   page ground
           --sf-ink-2          #131313   raised surface (cards, drawer)
           --sf-ink-3          #1C1C1C   hover surface / input field
           --sf-gold           #D4B084   primary accent
           --sf-gold-hi        #E8CFAE   accent hover / gradient stop
           --sf-gold-lo        #A8845C   accent pressed / gradient stop
           --sf-ivory          #F5F0E8   primary text on dark
           --sf-ivory-dim      rgba(245,240,232,.66)  secondary text
           --sf-ivory-mute     rgba(245,240,232,.42)  tertiary / struck price
           --sf-line           rgba(212,176,132,.22)  hairline borders
           --sf-danger         #E2735F   error text & borders
           --sf-ok             #7FA98A   success / in-stock
Radius     --sf-r-0 0 · --sf-r-1 2px · --sf-r-2 4px · --sf-r-pill 999px
Space      --sf-s-1 4 · -2 8 · -3 12 · -4 16 · -5 24 · -6 32 · -7 48 · -8 64 · -9 96 (px)
Type       --sf-serif 'Cormorant Garamond', Georgia, serif
           --sf-sans  'Jost', 'Helvetica Neue', Arial, sans-serif
Elevation  --sf-shadow-1  0 1px 2px rgba(0,0,0,.6)
           --sf-shadow-2  0 12px 32px rgba(0,0,0,.55)
           --sf-shadow-3  0 24px 64px rgba(0,0,0,.65)
Motion     --sf-dur-1 120ms · --sf-dur-2 200ms · --sf-dur-3 320ms · --sf-dur-4 560ms
           --sf-ease-out   cubic-bezier(.22,.61,.36,1)
           --sf-ease-in    cubic-bezier(.55,.06,.68,.19)
           --sf-ease-soft  cubic-bezier(.4,0,.2,1)
           --sf-ease-lux   cubic-bezier(.16,1,.3,1)
Z-index    --sf-z-header 100 · --sf-z-sticky 200 · --sf-z-wa 300
           --sf-z-overlay 400 · --sf-z-drawer 410 · --sf-z-modal 420 · --sf-z-toast 500
```

**Gold gradient** (used only on primary buttons and the logo lockup, never on text):
`linear-gradient(103deg, var(--sf-gold-lo) 0%, var(--sf-gold) 38%, var(--sf-gold-hi) 62%, var(--sf-gold) 100%)`.

### 0.3 The state model every interactive component obeys

1. **default** — resting.
2. **hover** — only inside `@media (hover:hover) and (pointer:fine)`. Touch devices never
   get a hover style, because a sticky `:hover` after tap is the classic mobile bug.
3. **focus-visible** — `:focus-visible` only, never bare `:focus`. Styling in §4.1.
4. **active** — pressed. Always a 1px downward translate plus a darker surface; never a
   scale-down (scale on a 375px button looks like a glitch, not a press).
5. **disabled** — `disabled` attribute on real controls, `aria-disabled="true"` on links
   that act as controls. 38% opacity, `cursor:not-allowed`, no hover, still focusable when
   it is an `aria-disabled` link so the reason can be announced.
6. **loading** — `.is-loading` + `aria-busy="true"`. Label stays in the DOM (for width
   stability) at `opacity:0`; a 16px gold ring spins centred. The control is
   `disabled` while loading so double-submits are impossible.
7. **error** — `.has-error` on the field wrapper, `aria-invalid="true"` on the control,
   and a message element referenced by `aria-describedby`. Never colour-only.

Minimum tap target is **44×44 CSS px** for every interactive element on every breakpoint
(§4.2). Where the visual control is smaller, the target is enlarged with padding or an
`::after` overlay, not by growing the visual.

---

# PART 1 — Components

## 1. Buttons

One element, three variants, three sizes. Rendered as `<button>` when it acts, `<a>` when
it navigates. An `<a>` styled as a button still needs `role` left alone — a link that looks
like a button is fine; a link with `role="button"` that navigates is not.

### 1.1 Primary (gold)

**Anatomy** — `<button class="sf-btn sf-btn--primary">` › optional leading inline SVG icon
(18px, `currentColor`) › label span › optional trailing count.
Height 52px desktop / 52px mobile (48px for the `--sm` size), horizontal padding
`--sf-s-5`, radius `--sf-r-1` (2px — luxury reads as near-square, never pill),
label in `--sf-sans` 13px, `letter-spacing:.14em`, `text-transform:uppercase`,
`font-weight:500`, colour `--sf-ink` on the gold gradient.

| State | Specification |
|---|---|
| default | Gold gradient background, `--sf-ink` label, no border, `--sf-shadow-1`. |
| hover | Background-position shifts 100% across the gradient over `--sf-dur-2 --sf-ease-out`; label unchanged; `--sf-shadow-2`. No scale. |
| focus-visible | 2px `--sf-ivory` outline, 2px offset (see §4.1). Hover styling is *not* implied. |
| active | `translateY(1px)`, gradient replaced by flat `--sf-gold-lo`, shadow drops to none, `--sf-dur-1`. |
| disabled | `background: var(--sf-ink-3)`, label `--sf-ivory-mute`, no shadow, `cursor:not-allowed`. Used for `Sold Out`. |
| loading | Label `opacity:0`, 18px ring spinner in `--sf-ink` centred, width frozen by the still-present label, `aria-busy="true"`. |
| error | Buttons do not carry error state. The error appears in a toast (§14) or on the form field. |

**375px** — a primary button inside a form or the sticky bar is `width:100%`. Two primary
buttons never sit side by side on mobile; they stack with `--sf-s-3` between them. Label
letter-spacing drops to `.10em` so `ADD TO CART — RS. 4,950` does not wrap.

### 1.2 Ghost

Transparent background, 1px `--sf-line` border, `--sf-ivory` label, same metrics as primary.
**hover** — border `--sf-gold`, label `--sf-gold`, background `rgba(212,176,132,.06)`.
**active** — background `rgba(212,176,132,.12)`, `translateY(1px)`.
**disabled** — border and label at `--sf-ivory-mute`.
**loading** — spinner in `--sf-gold`.
Used for secondary actions: `Continue Shopping`, `View All`, `Order on WhatsApp`
(which additionally carries the WhatsApp glyph and turns its border `--sf-ok` on hover).

### 1.3 Text button

No box. `--sf-ivory-dim` label, 13px, with a 1px underline drawn as a `background-image`
gradient so it can animate. **hover** — colour `--sf-gold`, underline wipes in from left
over `--sf-dur-2`. **focus-visible** — full outline ring (the underline alone is not a
focus indicator). **active** — colour `--sf-gold-lo`. Minimum 44px tap height is achieved
with `padding-block` even though the text is 13px.

## 2. Product card (`PRODUCT_CARD`)

Specified behaviourally in `03-storefront-pages.md` §1.3; this is its visual and state
contract.

**Anatomy** — `<article class="sf-card">` containing:
1. `.sf-card__media` — 4:5 `<a>` wrapping `<picture>`; `aspect-ratio:4/5`, `overflow:hidden`,
   background `--sf-ink-2` (so the box is visible before the image paints).
2. `.sf-card__badges` — absolute top-left stack, max two badges (§7).
3. `.sf-card__body` — name (serif, 18px, 2-line clamp), meta line (`--sf-ivory-mute`, 11px,
   `.08em` tracking: `Oriental · 50ml · 100ml`), rating (§10), price.
4. `.sf-card__action` — the button, full width of the card.

| State | Specification |
|---|---|
| default | No border. `--sf-ink` ground. The card is flat; the image does the work. |
| hover (pointer only) | Second image cross-fades over 350ms `--sf-ease-soft`; `img` scales `1.04` over `--sf-dur-4 --sf-ease-lux`; name colour → `--sf-gold`; a 1px `--sf-line` bottom border wipes in under the media. |
| focus-visible | The outline goes on the wrapping `<a>` and is drawn **around the whole card** using `outline-offset:4px`, not just the image. |
| active | Media scales to `1.02` (settling), `--sf-dur-1`. |
| disabled / sold out | Media at `filter:grayscale(.35) brightness(.72)`, `SOLD OUT` badge, action button disabled. The card is still a link — sold-out products remain browsable and indexable. |
| loading | Only as a skeleton (§23); a rendered card never loads. Its *button* can be `.is-loading` while an add-to-cart POST is in flight. |
| error | Add-to-cart failure does not mark the card. It raises an error toast and the button returns to default. |

**375px** — two per row, 12px gutter. Name clamps to 2 lines; the meta line drops the scent
family and shows sizes only. The action button becomes 44px tall with 12px label. Hover
effects are absent entirely; the second image is therefore **not** preloaded on touch
(`<img>` for image 2 only rendered when `(hover:hover)` — practically: it is always in the
DOM but `loading="lazy"` and never revealed, which costs nothing because it is never
fetched until visible and it never becomes visible).

## 3. Collection card

**Anatomy** — full-bleed 3:4 image, a 60%-height bottom scrim
(`linear-gradient(to top, rgba(10,10,10,.88), transparent)`), collection name in serif
26px ivory, one-line tagline in `--sf-ivory-dim` 12px, and a gold hairline that is 24px
wide at rest.
**hover** — image scales `1.06` over `--sf-dur-4 --sf-ease-lux`; the hairline grows to 64px
over `--sf-dur-3`; the scrim deepens to `.94`.
**focus-visible** — outline around the whole tile, offset 4px.
**active** — scale settles to `1.03`.
**disabled** — not applicable; inactive collections are not rendered.
**375px** — one per row full width at 3:4, or a `RAIL` of 78vw tiles on the home page.
Name drops to 22px.

## 4. Text input, select, textarea

One field wrapper so checkout, contact, newsletter, review and admin forms are identical.

**Anatomy** — `.sf-field` › `<label>` (11px, `.10em` tracking, `--sf-ivory-dim`, always
visible — no placeholder-as-label anywhere) › control › `.sf-field__hint` (11px,
`--sf-ivory-mute`) or `.sf-field__error` (11px, `--sf-danger`, prefixed by a 12px warning
glyph). The error element carries a stable id, `id="err-{name}"`, and the control carries
`aria-describedby="err-{name}"` **whether or not** the error is currently shown, so screen
readers pick it up on the first re-render (§4.4).

Control metrics: height 52px (textarea min-height 132px), padding `0 --sf-s-4`, background
`--sf-ink-3`, 1px `--sf-line` border, radius `--sf-r-1`, text `--sf-ivory` 15px
`--sf-sans`, placeholder `--sf-ivory-mute`. **16px minimum font-size on mobile inputs** —
anything smaller makes iOS Safari zoom on focus, which breaks the checkout layout.

| State | Specification |
|---|---|
| default | As above. `caret-color: var(--sf-gold)`. |
| hover | Border → `rgba(212,176,132,.38)`. Background unchanged. |
| focus-visible | Border `--sf-gold`, plus `box-shadow: 0 0 0 3px rgba(212,176,132,.18)`. Because an input's focus ring must read on a dark field, this replaces the generic ivory outline — it is the one documented exception in §4.1. |
| active | Not distinct from focus. |
| disabled | Background `--sf-ink-2`, text `--sf-ivory-mute`, border dimmed, `cursor:not-allowed`. Readonly (e.g. order number on the confirmation page) looks the same but keeps full-contrast text. |
| loading | Only on the newsletter and coupon fields: the submit button loads; the field itself becomes `readonly`, not disabled, so the typed value stays announced. |
| error | Border `--sf-danger`, `box-shadow: 0 0 0 3px rgba(226,115,95,.16)`, `aria-invalid="true"`, error text below. Never red text on a red border with no icon — the glyph carries the meaning for colour-blind users. |

**Select** — native `<select>` only (a custom listbox is a JS budget we are not spending).
Appearance reset, a 12px chevron drawn as an inline SVG background, `padding-right:40px`.
The option list is the OS's; on dark Android this renders light and that is acceptable.
**Textarea** — `resize:vertical` only; horizontal resize breaks the mobile layout.

**375px** — labels stay above (never floating, never inline). Fields are full-width and
stack with `--sf-s-4`. City is a `<select>` on mobile as well; a datalist is not used
because Android support is inconsistent.

## 5. Quantity stepper

**Anatomy** — `.sf-qty` group: `−` button, `<input type="text" inputmode="numeric"
pattern="[0-9]*">`, `+` button, wrapped in a 1px `--sf-line` box, radius `--sf-r-1`,
total height 44px, input width 48px, centred tabular figures.
`type="text"` + `inputmode="numeric"` rather than `type="number"` deliberately: it removes
the spinner arrows and the scroll-wheel-changes-quantity accident.

| State | Specification |
|---|---|
| default | Buttons `--sf-ivory-dim`, input `--sf-ivory`. |
| hover | Button glyph → `--sf-gold`, button cell background `--sf-ink-3`. |
| focus-visible | Ring on the individual button or input, not the group. |
| active | Button cell `rgba(212,176,132,.14)`. |
| disabled | `−` disables at qty 1; `+` disables at `min(stock, 10)`. Disabled glyph `--sf-ivory-mute`. The stock ceiling comes from the server on every cart response — the browser never decides it. |
| loading | The whole group gets `aria-busy`, opacity .6, pointer-events none while the cart POST is in flight; the optimistic new number is already shown. |
| error | If the server rejects the quantity (stock changed), the number snaps back to the server value and an error toast names the real available stock. |

**375px** — 44px tall, buttons 44×44, input 52px wide. In the cart drawer the stepper sits
on the same row as the line-remove text button, right-aligned.

## 6. Size selector

**Anatomy** — a `role="radiogroup"` of `<label>`-wrapped `<input type="radio">` chips, one
per row of `product_sizes`. Each chip shows `50ml` (15px) above `Rs. 4,950` (12px,
`--sf-ivory-dim`); if `sale_price` is set, the sale price shows in `--sf-gold` with the
regular struck in `--sf-ivory-mute`. Minimum chip size 72×56, radius `--sf-r-1`.

| State | Specification |
|---|---|
| default | 1px `--sf-line` border, transparent background. |
| hover | Border `rgba(212,176,132,.5)`. |
| focus-visible | Ring on the chip. Arrow keys move between chips (native radio behaviour — do not intercept). |
| checked | Border 1px `--sf-gold`, background `rgba(212,176,132,.10)`, size label `--sf-gold`. A 2px gold underline is added so "selected" is not communicated by colour alone. |
| disabled (out of stock) | 38% opacity, a diagonal hairline through the chip, `Out of stock` as the price line, radio `disabled`. |
| loading | Not applicable — sizes are rendered server-side and price/stock swap is pure DOM. |
| error | If the visitor submits with no size chosen (only possible with JS off and no default), the field wrapper takes `.has-error` and the message is `Choose a size.` |

Selecting a size updates the PDP price block, the SKU line, the stock line, the stepper
ceiling and the sticky bar label — all from `data-` attributes already in the DOM. **No
network request.** The default-checked chip is `product_sizes.is_default`, falling back to
the first in-stock size by `sort_order`.
**375px** — chips wrap in a 2-per-row grid, each 48% wide, 56px tall.

## 7. Badges

`<span class="sf-badge sf-badge--sale">`. 10px, `.12em` tracking, uppercase, 20px tall,
`padding:0 8px`, radius `--sf-r-0` (square corners — a pill badge cheapens the card).

| Variant | Appearance | Rule |
|---|---|---|
| `SALE −24%` | `--sf-gold` ground, `--sf-ink` text | any active size has `sale_price < price`; the percentage is the deepest discount across sizes, rounded down |
| `NEW` | transparent, 1px `--sf-gold` border, `--sf-gold` text | `products.is_new = 1` or created within 30 days |
| `SOLD OUT` | `rgba(10,10,10,.82)` ground, `--sf-ivory-dim` text, 1px `--sf-line` | every active size has `stock = 0` |

Priority `SOLD OUT` > `SALE` > `NEW`, maximum two stacked with 4px between. Badges are
plain text, not `aria-hidden` — "Sold out" must be readable by a screen reader reaching the
card. No state set: badges are not interactive.
**375px** — 9px text, 18px tall, still top-left, 8px inset.

## 8. Notes pyramid

The scent structure on the PDP. Three tiers: Top, Heart, Base, from
`products.notes_top / notes_heart / notes_base` (comma-separated strings).

**Anatomy** — `<ol class="sf-pyramid">` of three `<li>` rows, each with a left rail
(11px uppercase tier label in `--sf-gold`, `.14em`), a 1px vertical `--sf-line` connector
running the height of the list, an 8px gold dot on the connector at each tier, and the
note names as a serif 19px line. Rendered as a **list, not a triangle graphic** — a real
pyramid SVG cannot hold Pakistani-length note names legibly at 375px, and it is unreadable
to a screen reader. The "pyramid" reading comes from indentation: Top is flush left, Heart
is indented 16px, Base 32px, with the connector drawing the descent.

States: static. **hover** on a tier row raises it to `--sf-ink-2` (a pure affordance hint,
no action). **Empty state:** if a tier is empty the row is omitted entirely; if all three
are empty the whole component is omitted and the PDP shows the description only.
**375px** — indentation drops to 0/8/16px, note line to 17px, tier label above the notes
rather than beside them.

## 9. Longevity & sillage meters

Two meters from `products.longevity` and `products.sillage` (TINYINT 1–5).

**Anatomy** — a label row (`Longevity` left, the word value right: 1=`Light`, 2=`Moderate`,
3=`Good`, 4=`Long lasting`, 5=`Very long lasting`; sillage: `Intimate` / `Close` /
`Moderate` / `Strong` / `Enormous`), then a 4px track of 5 segments with 4px gaps.
Filled segments carry the gold gradient; empty segments are `rgba(245,240,232,.10)`.

Markup is `<div role="img" aria-label="Longevity: long lasting, 4 out of 5">` — a `<meter>`
element is not used because it is unstyleable in Safari. Segments are `aria-hidden`.

States: static, no hover, no focus. On scroll into view the filled segments wipe from
0 to their width, staggered 60ms apart, `--sf-dur-4 --sf-ease-lux` (§17.2). With reduced
motion they render filled immediately.
**375px** — the two meters stack; segment height stays 4px; the word value moves under the
label if the combined width exceeds the column.

## 10. Star rating

**Anatomy** — `<span class="sf-stars" role="img" aria-label="Rated 4.6 out of 5 from 12
reviews">` containing 5 inline SVG stars and a visually-separate `(12)` count in
`--sf-ivory-mute`. Partial fill is done with a `linear-gradient` clip on the last star at
percentage precision, not by rounding to halves.
Empty stars are `rgba(245,240,232,.16)`; filled are `--sf-gold`. 14px on the PDP, 12px on
cards. Shown only when `rating_count >= 1` (from approved reviews only).

**Interactive variant** — the review submit form only. 5 radio inputs, visually stars,
32×32 each with a 44px tap target. **hover/focus** fills that star and all before it in
`--sf-gold-hi`; **checked** fills in `--sf-gold`; **focus-visible** shows the ring on the
individual star; **error** (no rating chosen) puts `.has-error` on the group and
`Choose a rating from 1 to 5.` below it. Keyboard: native arrow-key radio traversal.
**375px** — 36×36 stars so the tap target is comfortable; the count moves to its own line.

## 11. Accordion

Used for the FAQ page, the PDP detail panels (Description / Notes / Delivery & Returns),
and the mobile footer columns.

**Anatomy** — `<h3><button aria-expanded="false" aria-controls="p-3" id="t-3">` with the
question in serif 18px and a 16px `+`/`−` glyph right-aligned; the panel is
`<div id="p-3" role="region" aria-labelledby="t-3" hidden>`.

| State | Specification |
|---|---|
| default | 1px `--sf-line` bottom border per row, 20px vertical padding, glyph `--sf-gold`. |
| hover | Title → `--sf-gold`; the row background lifts to `--sf-ink-2`. |
| focus-visible | Ring on the `<button>`, inset by 2px so it does not clip against the row above. |
| active | Glyph rotates 45° (`+` → `×` feel) over `--sf-dur-1`. |
| expanded | `aria-expanded="true"`, `hidden` removed, glyph is `−`, panel text `--sf-ivory-dim` 15px/1.7. |
| disabled | Not applicable. |
| error | Not applicable. |

Open/close animates `grid-template-rows: 0fr → 1fr` over `--sf-dur-3 --sf-ease-soft` — this
animates to auto height without measuring in JS. `hidden` is toggled at the end of the
close transition so the content is never focusable while collapsed. FAQ accordions allow
multiple open; PDP panels allow multiple open; nothing here is an exclusive accordion.
**With JS off** every panel renders open and the glyphs are hidden — the FAQ must be
readable and indexable regardless (it carries FAQPage schema).
**375px** — identical; 44px minimum row height, title drops to 16px.

## 12. Drawer (cart, and mobile filters)

One component, two instances. Right-side for the cart, left-side for mobile filters.
Rendered in the layout on every page, collapsed, so the cart is correct before JS runs.

**Anatomy** — `.sf-overlay` (`rgba(10,10,10,.72)`, `backdrop-filter:blur(2px)` where
supported) + `<aside class="sf-drawer" role="dialog" aria-modal="true"
aria-labelledby="cart-title" hidden>` › header (title serif 22px + 44×44 close button) ›
scrollable body › sticky footer (free-shipping progress bar, subtotal, coupon field,
`Checkout` primary, `Continue Shopping` text button).
Width `min(420px, 92vw)`, ground `--sf-ink-2`, 1px `--sf-line` on the inner edge,
`--sf-shadow-3`.

| State | Specification |
|---|---|
| closed | `hidden`, `translateX(100%)`, overlay `opacity:0`, `pointer-events:none`. |
| opening | `hidden` removed, then on the next frame `translateX(0)` over `--sf-dur-3 --sf-ease-lux`; overlay fades `--sf-dur-2`. `<body>` gets `overflow:hidden` and a scrollbar-width padding compensation. |
| open | Focus moved to the close button; focus trapped (§4.5); `Escape` closes; overlay click closes; `aria-hidden="true"` is **not** set on the rest of the page (`aria-modal` handles it). |
| closing | `translateX(100%)` + overlay fade, then `hidden` on `transitionend` and focus returned to the trigger. |
| loading | Body gets 3 line skeletons while `/api/cart` is in flight (only when opened before the first sync). |
| empty | Serif `Your cart is empty`, one line of `--sf-ivory-dim` copy, a ghost `Shop All Perfumes` button. |
| error | An inline `--sf-danger` strip above the footer: `We could not update your cart. Please try again.` Lines are re-rendered from the last good server response. |

Each cart line: 64×80 image (4:5), name (2-line clamp), size label, unit price, stepper,
line total right-aligned, `Remove` text button.
**375px** — drawer is `92vw`; the footer is sticky with `env(safe-area-inset-bottom)`
padding so the `Checkout` button clears the iOS home indicator. Filters drawer is the same
component mirrored, with `Apply filters (12)` as its sticky primary and `Clear all` as a
text button; applying navigates (a normal GET with query params), it does not fetch.

## 13. Modal

Used for: the size sheet on desktop, the image lightbox, and admin confirm-destructive
dialogs. Same dialog semantics as the drawer — `role="dialog"`, `aria-modal="true"`,
focus trap, `Escape`, overlay click — but centred, `max-width:560px`,
`max-height:86vh`, radius `--sf-r-2`, ground `--sf-ink-2`, `--sf-shadow-3`.
**opening** — `opacity 0→1` over `--sf-dur-2` and `translateY(12px)→0` +
`scale(.98)→1` over `--sf-dur-3 --sf-ease-lux`. **closing** — reverse at `--sf-dur-2
--sf-ease-in`. **error/loading** states live on the controls inside, not the shell.
**375px** — the modal becomes a **bottom sheet**: full width, top corners `--sf-r-2`,
`translateY(100%)→0`, `max-height:80vh`, with a 36×4 gold-dim grab handle. The lightbox is
full-screen with a 44×44 close button in the top-right safe area.

## 14. Toast

`aria-live="polite"` region always present in the DOM (a region injected at message time is
not announced). `role="status"` per toast, `role="alert"` for errors.
Max width 380px, ground `--sf-ink-3`, 1px `--sf-line`, 3px left edge in `--sf-gold`
(success) or `--sf-danger` (error), 14px message, optional text-button action
(`View cart`), 44×44 dismiss.
**in** — `translateY(8px)→0` + fade, `--sf-dur-2 --sf-ease-out`. **out** — fade only,
`--sf-dur-2`, after 5s (success) or 8s (error). Hover or focus inside pauses the timer.
Maximum 3 stacked, oldest evicted first.
**375px** — bottom-centre, full width minus 16px, stacked above the sticky add-to-cart bar
and the WhatsApp button; desktop is top-right under the header.

## 15. Breadcrumb, pagination, announcement bar

**Breadcrumb** — `<nav aria-label="Breadcrumb"><ol>`, 11px `.10em` uppercase,
`--sf-ivory-mute`, `/` separators as `::after` content with `aria-hidden`. Last crumb is
`aria-current="page"` and is not a link. Hover on links → `--sf-gold`. Emits
`BreadcrumbList` JSON-LD from the same array. **375px** — one line, horizontally
scrollable, no wrap, first crumb may scroll out of view; it is never truncated with an
ellipsis because the collapsed middle is worse than a scroll on a 375px screen.

**Pagination** — `<nav aria-label="Pagination">` with `Prev`, numbered links, `Next`.
Numbers 40×40 minimum with a 44px tap target; current page is `aria-current="page"`, gold
text with a 1px gold border; hover fills `rgba(212,176,132,.10)`; disabled `Prev`/`Next` at
the ends are rendered as `<span>`, not disabled links. Window: first, last, current ±2,
with `…` as an `aria-hidden` span. Real `<a href>` with query params — pagination is a page
load, not JS. `rel="prev"/"next"` in the head. **375px** — `Prev`/`Next` only plus
`Page 2 of 7`; the number strip is hidden.

**Announcement bar** — 36px, gold gradient ground, `--sf-ink` text, 11px `.12em` uppercase,
centred, from `settings.announcement_text`; hidden entirely when that setting is empty.
Optional 44×44 dismiss that sets a `sessionStorage` flag (session only — the client wants
it back on the next visit). If the text overflows at 375px it scrolls as a single marquee
loop at 40s duration; **the marquee is disabled under reduced motion and falls back to
2-line wrap with the bar growing to 48px.**

## 16. Header, mobile navigation, footer, WhatsApp, sticky bar, newsletter, skeletons

**16.1 Header — transparent-over-hero → solid-on-scroll.**
Two classes on `<header>`: `.is-transparent` (home page only, applied server-side so there
is no first-paint flash) and `.is-solid`. One `IntersectionObserver` watches an empty 80px
sentinel `<div>` placed directly after the announcement bar; when it leaves the viewport the
header takes `.is-solid`. No scroll listener.
Transparent: no background, no border, logo and icons in `--sf-ivory` with
`filter: drop-shadow(0 1px 8px rgba(0,0,0,.6))` for legibility over imagery.
Solid: `--sf-ink` ground, 1px `--sf-line` bottom border, `--sf-shadow-1`.
Transition: `background-color`, `border-color`, `box-shadow` over `--sf-dur-3
--sf-ease-soft`. Height 80px desktop / 56px mobile, shrinking to 64px desktop when solid.
Show-on-scroll-up uses the same observer's `IntersectionObserverEntry` direction check plus
a `translateY(-100%)` when scrolling down past 240px.
Nav link states: **hover** gold with a 1px underline wiping from left over `--sf-dur-2`;
**focus-visible** full ring; **active page** gold with a persistent underline and
`aria-current="page"`. The cart bubble is an 18px gold disc with `--sf-ink` tabular digits,
`aria-label="Cart, 3 items"`; at 0 items the bubble is not rendered.

**16.2 Mobile navigation.** A left drawer (same component as §12) opened by a 44×44
hamburger with `aria-expanded` and `aria-controls`. Contents in order: search field, the
six primary links (serif 22px, 56px rows, 1px `--sf-line` between), a `Collections`
accordion listing active collections, then `Track Order`, `Contact`, and a tap-to-chat
WhatsApp row with the number. Rows: **hover/active** background `--sf-ink-3`, label gold.
The hamburger glyph morphs to `×` over `--sf-dur-2`. With JS off, the hamburger is a plain
`<a href="#site-nav">` to a footer-rendered nav list — navigation never depends on JS.

**16.3 Footer.** Four columns ≥1024px; at 375px columns 2–4 become accordions (§11) with
column 1 (brand) always open and not collapsible. Headings 11px `.12em` uppercase gold;
links 14px `--sf-ivory-dim`, hover gold. Social icons 24px in 44×44 targets, rendered only
for non-empty settings, each with a real `aria-label` ("Sky Fragrances on Instagram").
Payment strip icons are `aria-hidden` with a visually-hidden "We accept: ..." sentence.

**16.4 Floating WhatsApp button.** 56px circle, `--sf-ok` ground, white glyph,
`--sf-shadow-2`, fixed bottom-right 16px + `env(safe-area-inset-bottom)`, `--sf-z-wa`.
**hover** scales `1.06`; **active** `0.96`; **focus-visible** ring offset 3px.
On PDPs it sits 72px higher to clear the sticky bar. Hidden (`opacity:0;
pointer-events:none`) while any drawer or modal is open. It is an `<a href="https://wa.me/…">`
with `aria-label="Order on WhatsApp"` — never an icon with no name.

**16.5 Sticky mobile add-to-cart bar.** <1024px, PDP only. Fixed bottom, 68px +
safe-area, ground `rgba(10,10,10,.94)` with `backdrop-filter:blur(8px)`, 1px top
`--sf-line`. Left: selected size label over the live price. Right: full-height primary
`Add to Cart` (min 44px tall, 55% width). It appears once the in-page add-to-cart button
scrolls out of view (the same `IntersectionObserver`, watching that button), sliding up
`translateY(100%)→0` over `--sf-dur-3 --sf-ease-lux`. **loading** and **disabled**
(sold out) states are the button's own. When the sticky bar is visible, `<body>` gets
`padding-bottom` equal to its height so the footer is never occluded.

**16.6 Newsletter form.** Inline: a single email field + primary `Subscribe` (side by side
≥768px, stacked at 375px), plus 11px consent copy. Honeypot field is visually hidden with
`autocomplete="off"` and `tabindex="-1"`. **loading** — button spinner. **success** — the
form is replaced in place by a gold-bordered `Thank you — you're on the list.` panel,
focus moved to it, `role="status"`. **error** — field error via `aria-describedby`
(`Enter a valid email address.`) or a toast for server failures. With JS off it is a normal
POST to `/api/newsletter` that redirects back with a flash message.

**16.7 Skeletons.** Only three exist: cart-drawer lines, search-suggest rows, and the
listing grid during a filter transition. A skeleton is a `--sf-ink-3` block at
`--sf-r-1` with a 1.4s left-to-right sheen (`linear-gradient` background-position
animation, `rgba(245,240,232,.06)` highlight). Containers carry `aria-busy="true"` and the
skeleton blocks are `aria-hidden`. **Under reduced motion the sheen is removed and the
blocks render as static tinted boxes.** Product imagery never skeletons — it uses a
solid `--sf-ink-2` box of the correct `aspect-ratio`, which reserves layout and costs no JS.

---

# PART 2 — Motion system

## 17. Durations, easings, and what each is for

| Token | Value | Used for |
|---|---|---|
| `--sf-dur-1` | 120ms | Presses, glyph rotations, colour-only swaps |
| `--sf-dur-2` | 200ms | Hover colours, underline wipes, toasts, overlay fades |
| `--sf-dur-3` | 320ms | Drawers, modals, accordions, header solidify |
| `--sf-dur-4` | 560ms | Scroll reveals, image zoom, meter fills |

| Easing | Curve | Used for |
|---|---|---|
| `--sf-ease-out` | `cubic-bezier(.22,.61,.36,1)` | Things entering or responding to a pointer |
| `--sf-ease-in` | `cubic-bezier(.55,.06,.68,.19)` | Things leaving (modal close, toast dismiss) |
| `--sf-ease-soft` | `cubic-bezier(.4,0,.2,1)` | Symmetric changes: accordion height, header background |
| `--sf-ease-lux` | `cubic-bezier(.16,1,.3,1)` | The brand curve — long, decelerating, no overshoot. Scroll reveals, drawers, image zoom, meter fills. |

Never animate `width`, `height`, `top`, `left`, or `box-shadow` spread. Animate `opacity`,
`transform`, `background-position`, `background-color`, `border-color`, and the
`grid-template-rows: 0fr→1fr` accordion trick. Anything that reveals content sets
`will-change: transform, opacity` only while the class is active, never permanently.

## 18. Scroll reveal — the exact pattern

One file, `assets/js/reveal.js`, ~25 lines, no dependencies.

**Class contract**
- Author markup: `<section class="sf-reveal" data-reveal-delay="1">`. `data-reveal-delay`
  is an integer 0–5, multiplied by 70ms, applied as `transition-delay`.
- For a stagger over children: `<div class="sf-reveal sf-reveal--stagger">` — the CSS
  assigns each direct child an increasing delay via `:nth-child(n)` up to 8 children;
  JS never touches children.
- Resting style: `.sf-reveal { opacity:0; transform: translateY(18px); transition:
  opacity var(--sf-dur-4) var(--sf-ease-lux), transform var(--sf-dur-4) var(--sf-ease-lux); }`
- Revealed: `.sf-reveal.is-visible { opacity:1; transform:none; }`

**Algorithm**
1. If `matchMedia('(prefers-reduced-motion: reduce)').matches` → add `is-visible` to every
   `.sf-reveal` immediately and return, registering no observer.
2. If `IntersectionObserver` is undefined (very old browser) → same as step 1.
3. Otherwise create one observer with `{ rootMargin: '0px 0px -12% 0px', threshold: 0.12 }`.
4. On an entry with `isIntersecting`, add `is-visible` and **`unobserve` that element** —
   reveals are one-way; nothing re-hides on scroll up.
5. Observe every `.sf-reveal` found at `DOMContentLoaded`. Elements injected later (none
   currently are) would need a manual re-scan; this is noted so it is not forgotten.

**The no-JS guarantee.** The CSS rule that hides `.sf-reveal` lives inside
`html.js .sf-reveal { … }`, and `document.documentElement.classList.add('js')` runs in a
tiny inline script in `<head>`. With JS disabled or broken, nothing is ever hidden.

**What is revealed:** section headers, product/collection grids (staggered), the why-choose-us
row, the Instagram grid, the notes pyramid, the meters. **What is never revealed:** the
header, the announcement bar, the hero (it is above the fold — hiding it costs LCP), the
first product grid row on a listing page, anything inside a drawer or modal, and every
element on `/checkout`, `/cart` and `/track` — a checkout must never animate.

## 19. Hover zoom on product imagery

`.sf-card__media img { transition: transform var(--sf-dur-4) var(--sf-ease-lux); }` and
`.sf-card:hover img { transform: scale(1.04); }`, with the media box clipping via
`overflow:hidden`. The PDP main image uses `scale(1.08)` and, on pointer devices only, a
`transform-origin` following the cursor (two `mousemove`-set CSS custom properties,
`--mx`/`--my`, throttled with `requestAnimationFrame`) — this is the one mousemove handler
in the codebase. Collection tiles use `scale(1.06)`. All of it is wrapped in
`@media (hover:hover) and (pointer:fine)`, so touch devices download and run none of it.
On touch, the PDP gallery zoom is the lightbox modal (tap to open), not pinch-on-page.

## 20. Drawer and modal transitions

Both follow: `hidden` removed → force a reflow → add `.is-open` on the next animation frame
→ transition runs → on close, remove `.is-open`, listen once for `transitionend` on the
panel, then set `hidden` and restore focus. Guard the `transitionend` with a
`setTimeout(…, 400)` fallback so a dropped event can never leave the page in a trapped
state. Overlay fades `--sf-dur-2` in both directions; the panel uses `--sf-dur-3
--sf-ease-lux` in and `--sf-dur-2 --sf-ease-in` out (leaving is always faster than
arriving).

## 21. `prefers-reduced-motion: reduce`

Reduced motion does not mean "no feedback". It means "no movement".

| Disabled | Kept |
|---|---|
| Scroll reveal translate + fade (content shows instantly) | All colour and border transitions at `--sf-dur-1`/`--sf-dur-2` |
| Hover zoom and cursor-following origin on all imagery | Hover colour changes, underline wipe replaced by an instant underline |
| Card second-image cross-fade (second image simply never shows) | Focus rings (never animated anyway) |
| Drawer/modal slide and scale (they appear/disappear at full opacity, no transform) | Overlay presence, focus trap, Escape, all dialog semantics |
| Meter fill animation, skeleton sheen, announcement marquee, sticky-bar slide-up | Meter final values, skeleton boxes, marquee text wrapped to 2 lines, sticky bar appearing |
| Toast slide-in | Toast fade at `--sf-dur-1` and its `aria-live` announcement |

Implemented as **one** `@media (prefers-reduced-motion: reduce)` block near the end of
`site.css` that sets `animation-duration:.01ms !important; animation-iteration-count:1
!important; transition-duration:.01ms !important; scroll-behavior:auto !important;` on
`*`, followed by the handful of explicit re-enables in the right-hand column above. JS
reads the same media query in `reveal.js` (§18 step 1) and before starting the sticky-bar
slide, and it re-checks on `change` so a visitor who flips the OS setting mid-session is
respected without a reload.

---

# PART 3 — Imagery art direction

## 22. Aspect ratios and derivatives

Every derivative is produced by **GD at upload time** (no CLI, no build step) and stored
under `/uploads/products/{id}/`. `product_images.filename` holds the base name; the
suffixes below are implied, and a missing derivative falls back to the base file.

| Context | Ratio | Rendered widths | Derivatives (`srcset`) |
|---|---|---|---|
| Home hero | 16:9 desktop, 4:5 mobile | 100vw | 1920, 1440, 1024 (desktop art) + 828, 640 (mobile art, separate `<source media>`) |
| Product card | 4:5 | 400–560 | 400, 600, 900 |
| PDP gallery main | 1:1 | up to 720 | 720, 1080, 1440 |
| PDP thumbnail | 1:1 | 72 | 144 only |
| Collection tile | 3:4 | 380–620 | 400, 800 |
| Instagram grid | 1:1 | 180–300 | 300 only |
| OG image | 1200×630 | — | generated once per product at upload |

Always `<picture>` with a WebP `<source>` and a JPEG `<img>` fallback, explicit `width`
and `height` attributes (or `aspect-ratio` in CSS) so nothing shifts, `loading="lazy"`
everywhere except the hero and the first card row (`loading="eager"` +
`fetchpriority="high"`), and `decoding="async"`. `alt` follows the pattern in
`03-storefront-pages.md` §1.3; decorative imagery gets `alt=""`.

## 23. Treatment

- **Product photography is shot and rendered on `--sf-ink`** — bottle on black, a single
  soft key from upper-left, one specular edge on the glass, a shadow that fades to nothing.
  This is the primary treatment for cards, PDP gallery and collection tiles.
- **Ivory is used for the lifestyle/editorial frame only** — the About page portrait, the
  why-choose-us row, and at most one home-page band. An ivory band is a deliberate breath
  between black sections; two in a row destroys the effect.
- A product image is never placed on a gradient, never given a drop shadow in CSS, and
  never rounded beyond `--sf-r-1`. The luxury read comes from the negative space around it.
- Instagram tiles are square, ungraded, and carry a `rgba(10,10,10,.25)` hover scrim with a
  centred glyph. They are static `<img>` links to the post URL — **no embed script**.

## 24. Missing-image placeholder

When a product has no row in `product_images`, render a CSS-only placeholder at the
context's aspect ratio: `--sf-ink-2` ground, a 1px `--sf-line` inset border at 12px, the
"SF" monogram from the logo as a single inline SVG at 22% of the box width, centred, in
`rgba(212,176,132,.28)`, and no text. It carries `role="img"` with
`aria-label="{product name} — image coming soon"`. It must never render as a broken-image
glyph and never collapse the grid — it reserves exactly the same box as a real image.
The same placeholder, at 3:4, covers a collection with no `image`.

---

# PART 4 — Non-negotiable accessibility rules

**4.1 Focus-visible on a dark UI.** One global rule:
`:focus-visible { outline: 2px solid var(--sf-ivory); outline-offset: 2px; border-radius:
var(--sf-r-1); }`. Ivory, not gold — gold on `--sf-ink` clears text contrast but is too
close to the accent colour to read as *focus* when the focused thing is already gold.
`outline: none` without a replacement is banned outright. The one exception is the form
control (§4), which uses a gold border plus a 3px gold-tinted halo, because an outline
around a 52px field on a dark ground reads as a second border. Focus is never hidden by
`overflow:hidden` — every scroll container uses `outline-offset:-2px` on its children.

**4.2 Tap targets.** 44×44 CSS px minimum for everything tappable, at every breakpoint,
including the announcement dismiss, social icons, breadcrumb links, pagination numbers,
star radios, quantity buttons and the card's second-image hotspot. Adjacent targets keep
8px of clear space. Verified at 375px, not just in a desktop responsive preview.

**4.3 Skip link.** First focusable element in `<body>`:
`<a class="sf-skip" href="#main">Skip to content</a>`, positioned off-screen with
`transform: translateY(-140%)` and brought to `translateY(0)` on `:focus` — never
`display:none`, which removes it from the tab order entirely. It lands on
`<main id="main" tabindex="-1">`. A second skip link, `Skip to filters`, appears on listing
pages only.

**4.4 Form error association.** Every control has a persistent `id`; its error element is
`id="err-{name}"` and is referenced by `aria-describedby` on the control **at all times**,
empty when there is no error. On a failed submit the control also gets
`aria-invalid="true"`, and focus moves to the first invalid control. A summary
`role="alert"` block at the top of the form lists each error as a link to its field. Errors
are never conveyed by border colour alone — icon plus text, always.

**4.5 Dialog focus trap and Escape.** On open: record the trigger, move focus to the close
button, and trap `Tab`/`Shift+Tab` within the panel by querying focusable descendants at
the moment of each keydown (not cached — the cart's contents change). `Escape` closes from
anywhere inside, including from a text field. On close, focus returns to the recorded
trigger; if that element is gone, focus goes to `<main>`. `aria-modal="true"` plus
`inert`-free markup; `<body>` scroll is locked while open and its scroll position restored
on close.

**4.6 Heading order.** Exactly one `<h1>` per page (the hero heading on home, the product
name on a PDP, the page title elsewhere). Section headings are `<h2>`, sub-blocks `<h3>`.
Levels are never skipped and never chosen for size — size comes from a class
(`.sf-h1`…`.sf-h4`) applied to the semantically correct tag. The visually-hidden class
`.sf-sr` exists for headings a sighted user does not need (e.g. `<h2 class="sf-sr">Cart
summary</h2>`).

**4.7 Reduced-motion contract.** §21 is a requirement, not a nicety: no reveal may leave
content permanently hidden when motion is reduced, and no dialog may depend on a
`transitionend` that will never fire because its transition was reduced to 0.01ms — hence
the timeout guard in §20.

**4.8 Contrast floor.** Body text `--sf-ivory` on `--sf-ink` is ~17:1. `--sf-ivory-dim` is
the floor for body copy (~11:1). `--sf-ivory-mute` is permitted **only** for struck-through
prices, separators and decorative meta ≤12px that is duplicated elsewhere. Gold on ink is
~9:1 and is safe for text; `--sf-ink` on gold (button labels) is ~9:1 and is safe.

---

# PART 5 — JavaScript budget

Total: **five files, no dependencies, no bundler, ~11 KB unminified**, all loaded with
`defer` at the end of `<head>`, plus one 3-line inline script that sets `html.js`.

> 08 §2.4 — C-60: that 3-line snippet is the **only** inline script on the site and is a byte-for-byte constant (`<script>document.documentElement.classList.add('js')</script>` plus the two surrounding lines, no whitespace variation, no interpolation). Its SHA-256 is computed once and shipped as the `'sha256-…'` source in the PHP-sent `Content-Security-Policy` (`script-src 'self' 'sha256-…'`, 07 B.3.6), so the policy needs no `'unsafe-inline'` and no nonce. Any change to the snippet changes the hash — the pre-launch checklist recomputes it. The cart count on first paint is server-rendered into `data-cart-count`, not an inline script; JSON-LD blocks are `type="application/ld+json"` and are not executed, so they need no allowance.

| File | ~Size | Behaviours it owns |
|---|---|---|
| `reveal.js` | 0.6 KB | §18 scroll reveal; the header transparent→solid sentinel; the sticky add-to-cart bar sentinel. One shared `IntersectionObserver` factory. |
| `ui.js` | 3.5 KB | Drawer + modal open/close, focus trap, Escape, body scroll lock; accordions; mobile nav; toasts; announcement dismiss; tabs on the PDP. |
| `cart.js` | 3 KB | Add / update / remove / coupon `fetch` calls to `/api/cart*`, drawer re-render, count bubble, free-shipping progress, error toasts. |
| `product.js` | 2.5 KB | Size-chip selection and the DOM swap of price/SKU/stock/stepper ceiling/sticky-bar label; gallery thumbnail switching and lightbox; hover-zoom origin (pointer devices only). |
| `forms.js` | 1.5 KB | Client-side validation hints, `aria-invalid` toggling, newsletter and contact submit states, search-suggest debounce. |

**Everything else is server-rendered or CSS.** Explicitly requiring **no** JavaScript:
filtering and sorting (GET with query params, full page load), pagination, the scent-finder
quiz (each question is a form POST/GET step), the mobile filter drawer's *apply* (a link),
the FAQ and footer accordions' readability (open by default without JS), currency
formatting, the free-shipping threshold on first paint, the cart count on first paint,
every price calculation (server-side, always — §00 brief), the WhatsApp button, the
Instagram grid, breadcrumbs, and all hover, zoom and reveal styling that is not gated by a
class. No carousel library: horizontal sets use the CSS `RAIL` pattern with
`scroll-snap-type`. No date picker, no custom select, no icon font, no analytics tag beyond
whatever the client later pastes into `settings`.
