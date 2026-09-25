# Stage 2 review — design (art direction)

Reviewer scope: every PNG in `docs/shots/stage2/` (25 pages × desktop 1440 px and mobile
375 px @2x, full-page captures), each one cropped to viewport-height bands and read at 1:1,
then traced back to `assets/css/site.css`, `app/views/*.php` and `app/partials/*.php`. Bar:
Le Labo, Byredo, Diptyque, Parfums de Marly — the brief says "better than these". Specs
consulted: 08 register (C-32, C-33, C-45, Q-17, Q-22), 04a §2.4–2.5 (gold rule), §3 (type),
§3.6 (tracked caps), §4. Line numbers are as of 2026-09-26.

Severity: **Blocker** = visibly broken on a core path, on every page or every viewport ·
**High** = a composition or craft fault a fragrance-house art director would send back ·
**Medium** = reads as template / off-register · **Low** = polish.

The short verdict: the *register* is right — Cormorant 300 display over Jost tracked caps,
hairlines, no radius, gold held to eyebrows, prices, rules and the two gradient display
moments. What separates this from the four houses today is not palette or type but
**finish**: a resting sheen artefact on every outlined button, an upside-down notes pyramid,
three empty bordered boxes on the home page, a footer whose columns do not line up, and a
mobile bar that leaks onto desktop. Fix the list below and the site clears the bar.

---

## Keep — the three things that are already excellent

1. **The section-header system** (`site.css:3702–3796`, `partials/section-header.php`).
   Gold tracked eyebrow → Cormorant H2 → muted Jost sub, with the "VIEW ALL →" link sat on
   the H2 baseline at the right edge (`grid-template-areas "eyebrow eyebrow" "title link"
   "sub sub"`, `align-items: end`, trailing-tracking compensation `margin-right:
   calc(var(--track-wide) * -1)`). This is exactly the Byredo/Le Labo cadence and it is
   consistent on all 25 pages. Do not touch it.

2. **The empty states and the 404** (`views/404.php`, `.empty-state` `site.css:1783`,
   `.h-display.text-gold-grad` `:348`). "404 / This page has drifted off." in gradient
   Cormorant 300 with a 9.75:1 flat-gold fallback, and "No reviews yet. / Be the first to
   review Azure Oud." are the two best pieces of copy-and-type on the site. The voice is
   right; keep both verbatim.

3. **The commerce chrome as craft**: the numbered checkout sections ("1 Contact ————",
   `.section-header--numbered`), the size tiles with gold border + `--accent-quiet` tint, the
   five-segment gold meters with staggered reveal (`.meter` `:2627–2680`), and the collection
   mosaic with its gold hairline under the count (`.collection-card__rule`, widening on
   hover). These are objects, not widgets — the level everything else should be raised to.

Also worth protecting: the gold discipline holds. I found **no** body copy, link, label or
form control set in gold; gold-on-ivory never appears in the `.t-light` band (icons and
rules there use `--gold-800`/`--gold-700` as 04a §2.4 demands).

---

## Blocker

### B1. Every outlined, text and card button carries a resting ivory sliver at its left edge

Seen on: hero "TAKE THE SCENT FINDER", all 16 "CHOOSE SIZE" cards, "CONTINUE SHOPPING",
"NEED HELP? MESSAGE US", "ORDER ON WHATSAPP", "APPLY", "NEXT →"/"← BACK", the four 404
buttons, "EXPLORE DAWN CHORUS →", "CONTACT US", "FILTER & SORT" — desktop and mobile alike.
Zoomed, it is a 6–8 %-wide tilted band, slightly lighter than the button's ground, with a
soft right edge.

- `assets/css/site.css:943–953` — `.btn::before { background-image: var(--grad-gold-sheen);
  background-size: 250% 100%; background-position: 100% 0; opacity: 0; }`
- `assets/css/site.css:1090` — `@media (hover: hover) and (pointer: fine) { .btn::before
  { opacity: 1; } }`
- `assets/css/site.css:49` — the sheen's highlight sits at gradient stops 38→50→62 %.

Cause: a 250 % tile parked at `100% 0` leaves the *right 40 %* of the gradient inside the
box, i.e. stops 60–100 %. The highlight's tail (50→62 %) therefore starts inside the button's
left edge, and because desktop Chrome matches `(hover: hover) and (pointer: fine)` the layer
is at `opacity: 1` at rest. It is the sheen, not a border or a shadow.

Fix (exact): delete line 1090; change line 1091 to
`.btn:hover::before { opacity: 1; background-position: 0% 0; }`; on line 952 make the
transition `background-position var(--dur-slow) var(--ease-out), opacity var(--dur-base)
var(--ease-out)`. The sweep still plays on hover, nothing shows at rest. (Alternative with
the same result: `background-size: 300% 100%` at rest, hover to `50% 0`.)

### B2. The mobile sticky add-to-cart bar renders on desktop

`product_azure_oud--desktop.png`, y≈900: "Azure Oud · 50ml · Rs. 8,950 · [ADD]" pinned across
the bottom of a 1440 px viewport, on top of the buy box's own trust row.

- `assets/css/site.css:4410–4430` — `.sticky-bar { position: fixed; … }` and
  `.sticky-bar.is-visible { transform: translateY(0); }` — no breakpoint hides it.
- `assets/js/product.js` — the observer that adds `.is-visible` is not gated by viewport.

Fix: add to the `@media (min-width: 1024px)` block (the aside is already sticky there,
`site.css:582–585`): `.sticky-bar { display: none; }`. Belt and braces in `product.js`:
skip the observer when `matchMedia('(min-width: 1024px)').matches`.

### B3. Announcement bar on mobile: the text is pushed under the ✕ ("FREE D ✕")

Every `*--mobile.png`, top 36 px. The message starts at x≈660 of 750 and runs beneath the
dismiss button.

- `assets/css/site.css:782–786` — `.announcement--long .announcement__text { animation:
  announcement-marquee 40s linear infinite; padding-inline-start: 100%; }` — frame 0 of
  the marquee is "text entirely to the right of the box", which is what a paused
  animation, `prefers-reduced-motion`, and the first paint all show.
- `assets/css/site.css:764–776` — `.announcement__dismiss` is absolutely positioned with
  no background, so anything under it shows through.

Fix: remove the marquee (lines 782–798 and the reduced-motion copy near `:5233–5242`). Let
the text wrap: `.announcement__text { white-space: normal; text-wrap: balance; padding-block:
var(--sp-2); }` and put the dismiss in flow: `.announcement__inner { display: grid;
grid-template-columns: var(--tap) minmax(0,1fr) var(--tap); }` with the text in column 2. A
scrolling ticker is also the single most "template" thing a luxury site can do — see M1 for
the bar's colour.

---

## High

### H1. The notes pyramid is upside-down, and the base tier wraps

`product_azure_oud--desktop.png`, "The Composition": top notes get the widest row, base
notes the narrowest, so "Amberwood · Cedarwood / Labdanum · Vetiver" folds into a 2×2 block
and the whole figure reads as a funnel. In perfumery the pyramid has the volatile top notes
at the apex and the base as the foundation — the widest tier. On mobile the tiers are
left-aligned tag lists and no pyramid exists at all.

- `assets/css/site.css:3404–3406` — `.pyramid__tier--top { --tier-w: 100%; }
  .pyramid__tier--heart { --tier-w: 76%; } .pyramid__tier--base { --tier-w: 56%; }`
- `assets/css/site.css:3377–3393` — `.pyramid__notes { justify-content: flex-start }`,
  `.pyramid__note { border: 1px solid var(--border-accent); padding-inline: var(--sp-4);
  font-size: 1.1875rem; }`
- `assets/css/site.css:3394–3396` — `.pyramid__tier:hover { background-color:
  var(--surface-raised); }` — a hover tint on something that is not a link.

Fix:
1. Invert the widths: `--top: 60%; --heart: 80%; --base: 100%` and centre everything:
   `.pyramid__tier { justify-items: center; text-align: center; }`,
   `.pyramid__notes { justify-content: center; }`. At ≥768 drop the two-column
   `8.5rem 1fr` grid (`:3398–3403`) and stack label above notes — the label-column stagger is
   what produced the diagonal.
2. Lose the boxes. Notes are not filters: `.pyramid__note { border: 0; padding: 0;
   min-height: 0; font-size: 1.375rem; line-height: 1.2; }` and separate with a gold interpunct:
   `.pyramid__note + .pyramid__note::before { content: "·"; color: var(--accent);
   margin-inline: var(--sp-3); }`. Cormorant at 22 px in a centred line under a tracked
   "TOP NOTES" eyebrow is how Le Labo and Byredo set notes.
3. Delete the `:hover` rule at 3394–3396.
4. Keep the shrinking gold hairlines between tiers (`border-image: var(--grad-gold-rule)`)
   — with the widths inverted they now draw the pyramid for free.

### H2. "For Him / For Her / Unisex" are three empty bordered boxes

Desktop: three 362×482 px hairline rectangles with a word in the middle; mobile: three
662×375 px ones stacked. It is the emptiest 1,000 px on the site and reads as a placeholder
that never received its image.

- `app/views/home.php` (For-whom section, `variant => 'wide gender'`) with no image seeded →
  `.collection-card--empty` (`site.css:1576–1581`) draws only a hairline border.
- `assets/css/site.css:1597` — `@media (min-width: 768px) { .collection-card--wide
  { aspect-ratio: 3 / 4; } }` turns the "wide" tile into a portrait.

Fix (pick one, the first is cheaper):
- Make them typographic bands: after line 1597 add `.collection-card--gender { aspect-ratio:
  16 / 9; background-image: var(--grad-gold-radial); }` (≈362×204 on desktop, 662×372 → set
  `aspect-ratio: 2 / 1` below 768), keep the centred Cormorant name at `--step-h2`, the
  one-line tagline and the 24 px gold rule. Byredo does this exact band.
- Or seed `genderTiles[].image` with the collection art (C-45 reuse) and let `--scrim-bottom`
  do the work — then the 3:4 is right.

### H3. Footer columns do not line up

`home--desktop.png` footer: heading "SHOP" at y=159, "HELP" at y=169, "ABOUT" at y=122;
link pitch 50 / 55 / 40 px per column.

- `assets/css/site.css:3853–3857` — `.site-footer__col { display: grid; gap: var(--sp-3); }`
- `assets/css/site.css:3925–3928` — `.site-footer__inner { grid-template-columns: 1.4fr
  repeat(3, minmax(0,1fr)); }` with default `align-items: stretch`.

Cause: each column is stretched to the tallest column's height and, being a grid with only
`auto` rows, distributes the spare height between its own rows (`align-content: normal`
stretches auto tracks). Fix: `.site-footer__col, .site-footer__links { align-content:
start; }` — one line. Then `.site-footer__inner { align-items: start; }` for safety.

### H4. Cart-page trust row is broken (icon left, title flush right, copy under the icon)

Desktop and mobile `cart--*.png`. Also flagged in `stage2-review-mobile.md` H1; recorded
here because it is the most visibly broken component in the set.

- `app/views/cart.php:142–146` — each `.trust__item` has three children: an `<svg>` from
  `icon.php` **without** the `trust__icon` class, an `<h3>`, a `<p>`.
- `assets/css/site.css:4710–4715` — `.trust--row .trust__item { grid-template-columns: auto
  minmax(0,1fr); }` — the `<p>` lands in column 1 / row 2, so the `auto` column grows to the
  paragraph's width and shoves the title to the far right.

Fix: `.trust--row .trust__text { grid-column: 2; }` and `.trust--row .trust__item > .icon
{ grid-row: 1 / span 2; align-self: start; }`; pass `'class' => 'trust__icon'` on lines
143–146. Better: delete the block. A cart page at Le Labo has the lines, the summary and one
sentence — four icons under it is Shopify-theme furniture, and the summary already lists
the payment methods and the delivery time.

### H5. The wrong PDP column is sticky

Desktop PDP: the gallery ends at y≈650 and leaves ~700 px of black under it while the buy
box, accordions and composition scroll past.

- `assets/css/site.css:582–585` — `.split__aside { position: sticky; top: … }` — the aside
  is the *taller* column, so sticky never engages; the short gallery is the one that should
  ride along (Byredo, Diptyque and Le Labo all pin the image).

Fix: `.split--pdp .split__media { position: sticky; top: calc(var(--header-h-solid) +
var(--sp-5)); }` and remove `position: sticky` from `.split__aside` inside `.split--pdp`.

### H6. Listing grid: four 184 px cards in the results column, and price rows drift

`shop--desktop.png`: the results column is 808 px; `.grid--products` still goes to four
columns, so cards are 184 px and the meta line ("FRESH & CITRUS 50ML · 100ML") wraps on
some cards and not others — the "From Rs." row sits 23 px lower on Cirrus and Silver Lining
than on their neighbours.

- `assets/css/site.css:631` — `@media (min-width: 1200px) { .grid--products
  { grid-template-columns: repeat(4, …) } }` applies inside `.listing__results` too.
- `assets/css/site.css:3235–3245` — `.product-card__meta { display: flex; flex-wrap: wrap; }`

Fix: `@media (min-width: 1200px) { .listing__results .grid--products { grid-template-columns:
repeat(3, minmax(0,1fr)); } }` (cards ≈250 px, the home grid's 266 px cousin), and make the
meta a single clipped line: `.product-card__meta { display: block; white-space: nowrap;
overflow: hidden; text-overflow: ellipsis; }` with the family and sizes joined by " · " in
`partials/product-card.php:183–186`.

### H7. Mobile sections carry two CTAs and the second one touches the grid

`home--mobile.png`: "ALL COLLECTIONS →" text link in the header **and** a full-width ghost
"ALL COLLECTIONS" button under the rail; same for "VIEW ALL →" / "VIEW ALL NEW ARRIVALS". The
button's top edge is 3 px from the last card / the last CHOOSE SIZE button.

- `app/views/home.php:71–73` and `:102–104` — `<div class="btn-row u-hide-md-up">…`
- `partials/section-header.php` — the `.section-header__link` is never hidden below 768.
- `assets/css/site.css:1084–1088` — `.btn-row` has no `margin-block-start`.

Fix: delete the two `btn-row u-hide-md-up` blocks and keep the quiet header link — that is
the register the brief asks for. If a bottom button must stay, `.section > .container >
.btn-row { margin-block-start: var(--sp-6); }`.

### H8. Instagram strip and footer sit on a different grid from every section above them

Desktop: sections run 152→1288 (`.container`, 75 rem); the Instagram tiles and the footer's
bottom rule run 120→1320 (`.container--wide`, 90 rem). The strip is also cropped — bottle
bases are cut off — because the tiles are 1:1 over 4:5 art.

- `app/views/home.php:80` — `<div class="container container--flush">` for the strip; the
  footer partial uses `container--wide`.
- `assets/css/site.css:2597–2603` — `.insta { aspect-ratio: 1 / 1; }`, `.insta__img
  { object-fit: cover; }` (centre crop of a 4:5 loses 10 % top and bottom).

Fix: one grid or true full-bleed, never the in-between. Cheapest: footer inner → `.container`
(drop `--wide`), Instagram wrapper → `.container` (drop `--flush`), and `.insta { aspect-ratio:
4 / 5; }` (they are product art, not Instagram squares; six 4:5 tiles at 176 px fit the
1136 px content width with `--sp-2` gaps). If full-bleed is wanted instead: `.grid--instagram
{ margin-inline: calc(50% - 50vw); }` and keep the footer on `.container`.

---

## Medium

### M1. The announcement bar is a solid gold band on every page

36 px of `--grad-gold-button` with ink text is the loudest element on the site and the first
thing every visitor sees; it is the admin "primary button" treatment stretched across the
viewport. Le Labo, Byredo and Diptyque run a black hairline bar with small tracked ivory text.

- `assets/css/site.css:725–738` — `.announcement { background: var(--grad-gold-button);
  color: var(--text-inverse); }`

Fix: `background: var(--surface-raised); color: var(--text-muted); border-bottom:
var(--border-hair) solid var(--border-faint);` and, if any accent is wanted, only the amount
("Rs. 3,000") in `--gold-300`. Pair with B3.

### M2. The WhatsApp FAB is the only saturated colour on the site

`#25D366` circle, white glyph, 56 px, 32 px black shadow, on every page and both viewports;
on mobile it overlaps the hero's CTA column and the quiz cards.

- `assets/css/site.css:4750–4770` — `.whatsapp-fab { background: var(--whatsapp-green);
  color: #FFFFFF; box-shadow: 0 12px 32px rgba(0,0,0,.55); }`

Fix: rest in the palette — `background: var(--surface-overlay); color: var(--accent);
border: var(--border-hair) solid var(--border-accent); box-shadow: none; width/height:
3.25rem;` — and let WhatsApp green appear only on `:hover`/`:active` (`background:
var(--whatsapp-green); color: #fff; border-color: transparent`). Same family: the green "In
stock" dot (`.stock-line::before` `:4489`) and green "Free" (`.summary__value--free`) —
use `--gold-300` for both; keep `--success` for the confirmation callout only.

### M3. Hero: seven stacked layers and the wordmark twice within 250 px

Desktop hero = 96 px lockup, eyebrow, two-line display, lead, primary + text CTA, "Cash on
delivery · Nationwide" — directly under a header that already carries the lockup. And the
hero is bare black with a radial glow although C-45 reserves a collection image under a scrim
for it. The four houses use at most four layers (image, one line, one sub, one CTA).

- `app/views/home.php:31–58` — `.hero.hero--empty` with `.hero__mark`, `.hero__eyebrow`,
  `.hero__title`, `.hero__sub`, `.hero__actions` (2), `.hero__trust`.
- `assets/css/site.css:2493–2497` — `.hero__mark { width: 4.5rem }` → `6rem` at ≥768.
- `assets/css/site.css:2466` — `.hero--under-header` exists but is unused.

Fix: drop `.hero__mark` (or make the header transparent over the hero with
`.hero--under-header` + `.site-header.is-transparent`, so the lockup appears once); drop
`.hero__trust` (the announcement bar 80 px above says it); seed `hero.image_desktop` with the
"Midnight Meridian" or "Dawn Chorus" 1400 px derivative at `--hero-opacity: .45` under
`--scrim-full`. Result: eyebrow, title, lead, CTA pair — and a picture.

### M4. "CHOOSE SIZE" on all sixteen home cards; eight of them are duplicates

Every product card ends in a full-width outlined tracked-caps button; on the home page that
is 16 identical buttons, and Best Sellers and New Arrivals share four products (Vetiver
Squall, Nimbus Rain, Saffron Zenith, Silver Lining). Le Labo/Byredo/Diptyque cards are image,
name, price; the action lives on hover or on the PDP.

- `app/partials/product-card.php:216` — `<a class="btn btn--ghost btn--block btn--card">`
- `assets/css/site.css:3255–3260` — `.product-card__action { margin-block-start: auto }`

Fix: at `@media (hover: hover) and (pointer: fine)` add `.product-card__action { opacity: 0;
transform: translateY(4px); transition: opacity var(--dur-base), transform var(--dur-base); }
.product-card:hover .product-card__action, .product-card:focus-within .product-card__action
{ opacity: 1; transform: none; }`. On touch keep it, but as `.btn--text` ("Choose size →",
left-aligned, no box). And cap both home rows at four (one row on desktop), excluding from
New Arrivals anything already in Best Sellers — that is a home-controller change; state it to
that owner.

### M5. Checkout payment cards and the transfer panel

- "Prepaid" floats mid-card beside the description rather than beside the title:
  `assets/css/site.css:1452–1456` `.choice__badge { grid-column: 3; align-self: center; }`
  → `align-self: start; grid-row: 1;`.
- Inside "Paying by transfer?" the three fields have no vertical rhythm — the hint of one
  field touches the label of the next (`checkout--desktop.png` band 3, mobile band 5):
  `app/views/checkout.php:153–183` puts `.field`s directly in `.panel--sunken`, and
  `site.css:4888` only spaces `p + p`. Fix: `.panel > * + * { margin-block-start:
  var(--sp-4); } .panel > .field + .field { margin-block-start: var(--sp-5); }` (or wrap the
  fields in `.stack` in the view).
- The summary's payment list ("Cash on delivery / Delivery in 2–4 working days / WhatsApp
  support") is centred with 56 px pitch under the button: `cart.php:133–136` and the checkout
  equivalent use `.trust.trust--inline` whose items are grids. Set it as one muted
  `--step-micro` line joined with " · ".

### M6. Confirmation header: icon top-left, buttons left, title centred

`order_…--desktop.png`: the 56 px check sits at x=348 (left edge of the narrow container),
the title is centred, and the "MESSAGE US / TRACK YOUR ORDER" pair is left-aligned.

- `app/views/confirmation.php:47` — the `<svg class="icon text-gold-grad">` is a block in a
  container whose `.page-head--center` (`site.css:452`) only sets `text-align: center`.
- `assets/css/site.css:1117–1121` — `.btn-row` becomes `flex-direction: row` at ≥768 with
  no `justify-content`.

Fix: `.page-head--center > .icon { display: block; margin-inline: auto; }` and
`.page-head--center .btn-row { justify-content: center; }`. On mobile the two status pills
wrap to a second line under "Placed …" — give the pills their own line
(`<p class="status-row">` with `display:flex; gap; justify-content:center`).

### M7. Review form: the stars sit 550 px away from their label

- `assets/css/site.css` `.star-input { display: inline-flex; flex-direction: row-reverse; }`
  is a child of `.field { display: grid }` → stretched to full width, so `row-reverse` packs
  the five stars at the right edge.

Fix: `.star-input { justify-self: start; }`. Also put the "0 / 1500" counter on the hint's
line (`.field__hint-row { display: flex; justify-content: space-between; }`) instead of a
third line.

### M8. Landing heroes (For Him / For Her / Unisex / collections) are a full viewport of black

`for_him--desktop.png`: 850 px from header to the first card; mobile: the title lands at
y=790 of a 1300 px band.

- `assets/css/site.css:2569–2572` — the home hero's `min-height: calc(100svh -
  var(--announcement-h))` applies to every `.hero`.

Fix: a modifier for listing heroes: `.hero--landing { min-height: 0; max-height: none;
padding-block: var(--sp-9); }` (≈420 px desktop, ≈300 px mobile) and use it in `listing.php`.

### M9. Scent Finder: all five questions render at once, with five progress bars

`scent_finder--*.png`: "Question 1 of 5 · 20 %" … "Question 5 of 5 · 100 %" stacked down one
page, each with its own NEXT/BACK — the progress chrome is decoration over a long form.

- `app/views/quiz.php:20–54` — every `<fieldset class="form__section">` renders; nothing in
  `site.css` or `forms.js` steps them.
- Answer cards: the icon (`quiz.php:36`, `.choice__icon` has **no** CSS rule) sits at the
  card's top-left at 22 px while title/text are centred (`site.css:1472–1476`); cards in a
  row differ in height because `.choice` is `display: block` so `.choice__card` cannot
  stretch (`:1390–1392`).

Fix (either): step it — `html.js .form__section:not(.is-active) { display: none; }` plus a
20-line handler in `forms.js` that toggles `.is-active` on NEXT/BACK and fills one progress
bar; or present it honestly as one form with the checkout's numbered-section treatment and
no per-question progress. Then `.choice--answer .choice__icon { justify-self: center;
margin-block-end: var(--sp-2); color: var(--accent); }` (or drop the icons) and
`.choice--answer { display: grid; } .choice--answer .choice__card { height: 100%; }`.

### M10. Search results page: H1, then eyebrow, then input, then a lone button

`search_q_oud--desktop.png`: "Search results for "oud"" → "SEARCH THE CATALOGUE" → a
1136 px input → a gold SEARCH button alone on the next line, left.

- `app/views/listing.php:135–144` — `.form` + `.form__actions` around the search field.

Fix: use `.field--inline` (`site.css:1931`) so the button sits at the input's right end,
make the label `u-sr-only` (the H1 already says it), and cap the field at `max-width: 40rem`.

### M11. Header logo is the full lockup at 44 px

`.site-header__logo img { max-height: calc(var(--header-h) - var(--sp-3)); }`
(`site.css:4032–4038`) renders the 353×400 lockup at 44 px tall, so "SKY FRAGRANCES" and the
tagline become 3–4 px hairlines (desktop header, x=728–775).

Fix: `partials/header.php:54` → `img/brand/monogram-transparent-512.png` (the SF mark) at
`height: 2.25rem`; the full lockup belongs to the hero and the footer only.

### M12. Active-filter chip touches the product grid

`shop_gender_her_sort_price_asc--desktop.png`: the "For Her ×" chip's bottom edge is on the
first card's top edge.

- `app/views/listing.php:187` — `.chips` between `.toolbar` and the grid; `site.css:2143`
  `.chips` has no margin. Fix: `.listing__results .chips { margin-block-end: var(--sp-5); }`.

---

## Low

### L1. "Why choose us" titles sit at different heights

Desktop: "Fast Delivery" is 8 px lower than its three neighbours; mobile the same. The truck
glyph has a different intrinsic height, and `.trust__item { display: grid; gap }`
(`site.css:4685–4689`) lets the icon row vary. Fix: `.trust__item { grid-template-rows: 2rem
auto auto; }` and `.trust__icon { display: block; }`.

### L2. PDP trust row under the WhatsApp button wraps to four lines per column

`.trust--row` goes to three 160 px columns at ≥768 (`site.css:4747`) inside the buy box;
"Free delivery / above Rs. 3,000 / Nationwide, / tracked to your" wraps four deep. Fix:
`.split__aside .trust--row { grid-template-columns: 1fr; }` — the mobile stack (icon + two
short lines) is the better composition on desktop too.

### L3. PDP main image renders square at 1440

`.gallery__main` declares `aspect-ratio: 4 / 5` (`site.css:2313`) but the desktop capture
shows 488×490. Either the seeded derivatives are 1:1 or something in `.gallery--desktop`
constrains the box; cards are 4:5, so the PDP should be too. Verify in DevTools after B2/H5.

### L4. Track page: the order-number input is taller than the phone input beside it

`.field__input--code` (`site.css:1876`) sets tracking and a larger size; the two inputs end
up 54 px and 48 px. Give `--code` the same `min-height` and only change `letter-spacing`
and `font-feature-settings`.

### L5. 404 search input truncates its own placeholder

`.empty-state { justify-items: center }` shrinks the `.field--inline` form to content, so
the input is 210 px and shows "Search perfumes, not". Fix: `.empty-state .form { width:
min(100%, 28rem); }` and shorten the placeholder to "Search perfumes".

### L6. Contact page key/value list leaves a 200 px gap

`.data-list__row { grid-template-columns: minmax(0,1fr) minmax(0,1.5fr) }` on a 544 px
column puts "EMAIL" at x=152 and the address at x=377. Fix: `grid-template-columns: 6rem
minmax(0,1fr);` and stack (`1fr`) under 480 px.

### L7. Footer newsletter input collapses to "You"

`.newsletter--compact .newsletter__form { grid-template-columns: minmax(0,1fr) auto }`
(`site.css:3018`) in a 205 px footer column leaves the input 56 px wide. On the home page it
is also the second identical form within 900 px of "Join the inner circle". Fix: stack it
(`grid-template-columns: 1fr`) and suppress `.site-footer__newsletter` on pages that render
the band (a `$hasNewsletterBand` flag from the layout).

### L8. Mobile breadcrumb clips the current item with a hard edge ("AZURE OU")

`.breadcrumb__list { overflow-x: auto }` (`site.css:879–888`) with no fade. Fix:
`.breadcrumb__current { max-width: 40vw; overflow: hidden; text-overflow: ellipsis; }` or a
right-edge `mask-image: linear-gradient(to right, #000 85%, transparent)` on the list.

### L9. Mobile shop toolbar: a SORT select and a FILTER & SORT button side by side

`listing.php:172–182` renders both; the drawer sorts too. Hide the inline select below 1024
(`.toolbar__sort { display: none }` + the drawer's sort), leaving count + one button. (The
pinned second button is covered in `stage2-review-mobile.md` H5.)

### L10. Cormorant italic is shipped and used for the collection pull-quote

`collections-index.php` sets the one-line mood in italic and `assets/fonts/
cormorant-garamond-italic-variable.woff2` is loaded; 04a §3.2 / C-32 refuse italic. It
looks fine and is one use — record the deviation in the register, or set the line in
Cormorant roman 300 at `--step-h4` and drop the file (≈30 KB).

### L11. `.pyramid__tier:hover` and `.choice__card:hover` tints

Hover states on non-interactive tiers (H1) and on already-selected cards read as broken
buttons. Remove the first; keep the second only for unselected cards.

---

## What still reads as "template" (checklist for the fixer)

In order of visibility: the gold announcement band (M1/B3) · the resting sheen sliver on
every outlined button (B1) · "CHOOSE SIZE" on every card and duplicated rows (M4) · the
three empty bordered gender boxes (H2) · the green FAB (M2) · boxed note chips and the
inverted pyramid (H1) · four-icon trust rows under the cart and PDP (H4/L2) · five stacked
quiz progress bars (M9) · two newsletter forms on one page (L7) · the wordmark twice in the
first 250 px (M3). None of these needs a new component; each is a value change or a
deletion.

## Needs from other owners

- Home controller: cap Best Sellers and New Arrivals at four each and exclude overlap (M4).
- Seed / admin: a hero image (`hero.image_desktop`, C-45 collection reuse) and either images
  or the band treatment for the three gender tiles (H2, M3).
- Register: decide L10 (italic) so the font manifest and 04a agree.
